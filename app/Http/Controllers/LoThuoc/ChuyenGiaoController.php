<?php

namespace App\Http\Controllers\LoThuoc;

use App\Enums\OrganizationApprovalStatus;
use App\Enums\OrganizationType;
use App\Http\Controllers\Controller;
use App\Models\BlockchainTransaction;
use App\Models\LoThuoc;
use App\Models\ToChuc;
use App\Services\ChuoiCungUng\ChuyenGiaoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChuyenGiaoController extends Controller
{
    public function __construct(private ChuyenGiaoService $chuyenGiaoService) {}

    /**
     * API: Danh sách Nhà phân phối đã duyệt (CSDL thật)
     */
    public function danhSachNhaPhanPhoi()
    {
        $danhSach = ToChuc::query()
            ->where('loaiToChuc', OrganizationType::nhaPhanPhoi->value)
            ->where('trangThaiDuyet', OrganizationApprovalStatus::daDuyet->value)
            ->orderBy('tenToChuc')
            ->get(['id', 'tenToChuc', 'maSoThue']);

        return response()->json(['success' => true, 'data' => $danhSach]);
    }

    /**
     * NSX khởi tạo chuyển giao lô → NPP
     * 1. Validate quyền + tồn kho khả dụng
     * 2. Tạo bản ghi chuyenGiao (PENDING / IN_TRANSIT)
     * 3. Tạo blockchainTransaction PENDING
     * 4. Dispatch job ký on-chain
     */
    public function store(Request $request)
    {
        $request->validate([
            'loThuocId' => 'required|integer|exists:loThuoc,id',
            'benNhanId' => 'required|integer|exists:toChuc,id',
            'soLuong' => 'required|integer|min:1',
        ], [
            'loThuocId.required' => 'Thiếu mã lô thuốc.',
            'benNhanId.required' => 'Vui lòng chọn nhà phân phối đã được xét duyệt.',
            'soLuong.required' => 'Thiếu số lượng chuyển giao.',
            'soLuong.min' => 'Số lượng chuyển giao tối thiểu là 1.',
        ]);

        $user = Auth::user();
        $toChuc = $user?->toChuc;
        if (! $toChuc) {
            return response()->json(['success' => false, 'message' => 'Tài khoản chưa gắn với tổ chức.'], 403);
        }

        $benNhan = ToChuc::query()
            ->whereKey($request->integer('benNhanId'))
            ->where('loaiToChuc', OrganizationType::nhaPhanPhoi->value)
            ->where('trangThaiDuyet', OrganizationApprovalStatus::daDuyet->value)
            ->first();

        if (! $benNhan) {
            return response()->json([
                'success' => false,
                'message' => 'Nhà phân phối không tồn tại, chưa được xét duyệt hoặc chưa có ví Blockchain ACTIVE.',
            ], 422);
        }

        try {
            $chuyenGiao = $this->chuyenGiaoService->tao($toChuc, [
                'loThuocId' => $request->integer('loThuocId'),
                'benNhanId' => (int) $benNhan->id,
                'soLuong' => $request->integer('soLuong'),
            ], $user, $request->ip());

            $txHash = (string) BlockchainTransaction::query()
                ->where('chuyenGiaoId', $chuyenGiao->id)
                ->where('eventName', 'TransferSubmitted')
                ->value('txHash');

            if (! preg_match('/^0x[a-fA-F0-9]{64}$/', $txHash)) {
                throw new \RuntimeException('Thiếu txHash Sepolia hợp lệ sau khi tạo chuyển giao.');
            }

            return response()->json([
                'success' => true,
                'message' => 'Đã ký và xác nhận chuyển giao trên Ethereum Sepolia. Dữ liệu chỉ được lưu sau khi có txHash thật.',
                'data' => [
                    'chuyenGiaoId' => $chuyenGiao->id,
                    'trangThai' => $chuyenGiao->trangThai,
                    'soLuong' => $chuyenGiao->soLuong,
                    'benNhan' => [
                        'id' => $benNhan->id,
                        'tenToChuc' => $benNhan->tenToChuc,
                    ],
                    'txHash' => $txHash,
                    'explorerUrl' => 'https://sepolia.etherscan.io/tx/' . $txHash,
                ],
            ]);
        } catch (\App\Exceptions\NghiepVuException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Không thể hoàn tất ký chuyển giao trên Sepolia. CSDL chưa ghi nhận chuyển giao.',
            ], 502);
        }
    }

    /** Bên gửi yêu cầu hủy lệnh chuyển giao đang PENDING. */
    public function huy(Request $request, int $id)
    {
        $user = Auth::user();
        $toChuc = $user?->toChuc;
        if (! $toChuc) {
            return response()->json(['success' => false, 'message' => 'Tài khoản chưa gắn tổ chức.'], 403);
        }

        $chuyenGiao = \App\Models\ChuyenGiao::query()->find($id);
        if (! $chuyenGiao) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy giao dịch chuyển giao.'], 404);
        }

        try {
            $this->chuyenGiaoService->huy($chuyenGiao, $toChuc, $user, $request->ip());
            return response()->json([
                'success' => true,
                'message' => 'Đã gửi yêu cầu hủy. Hệ thống đang chờ TransferCancelled được xác nhận trên blockchain.',
            ]);
        } catch (\App\Exceptions\NghiepVuException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Không thể gửi yêu cầu hủy chuyển giao.'], 500);
        }
    }

}

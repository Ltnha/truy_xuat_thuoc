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

        return response()->json([
            'success' => true,
            'data' => $danhSach,
        ]);
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
            'loThuocId'   => 'required|integer|exists:loThuoc,id',
            'benNhanId'   => 'required|integer|exists:toChuc,id',
            'soLuong'     => 'required|integer|min:1',
            'ghiChu'      => 'nullable|string|max:500',
        ], [
            'loThuocId.required' => 'Thiếu mã lô thuốc.',
            'benNhanId.required' => 'Vui lòng chọn nhà phân phối tiếp nhận.',
            'soLuong.required'   => 'Vui lòng nhập số lượng chuyển giao.',
            'soLuong.min'        => 'Số lượng chuyển giao tối thiểu là 1.',
        ]);

        $user = Auth::user();
        $toChuc = $user->toChuc;

        if (!$toChuc) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản chưa gắn với tổ chức.',
            ], 403);
        }

        $loThuoc = LoThuoc::where('id', $request->loThuocId)
            ->where('toChucId', $toChuc->id)
            ->first();

        if (!$loThuoc) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy lô thuốc hoặc lô không thuộc nhà sản xuất của bạn.',
            ], 404);
        }

        // Chỉ cho chuyển khi đã có giao dịch on-chain (tránh chuyển lô chưa neo)
        $txOk = BlockchainTransaction::where('loThuocId', $loThuoc->id)
            ->where(function ($q) {
                $q->whereIn('trangThai', ['CONFIRMED', 'SUCCESS'])
                    ->orWhere(function ($q2) {
                        $q2->whereNotNull('txHash')
                            ->where('txHash', 'not like', 'PENDING_%');
                    });
            })
            ->exists();

        if (!$txOk) {
            return response()->json([
                'success' => false,
                'message' => 'Lô thuốc chưa được ghi nhận On-chain thành công. Vui lòng đợi đồng bộ xong trước khi chuyển giao.',
            ], 422);
        }

        // Nhà phân phối thật, đã duyệt
        $benNhan = ToChuc::where('id', $request->benNhanId)
            ->where('loaiToChuc', OrganizationType::nhaPhanPhoi->value)
            ->where('trangThaiDuyet', OrganizationApprovalStatus::daDuyet->value)
            ->first();

        if (!$benNhan) {
            return response()->json([
                'success' => false,
                'message' => 'Nhà phân phối không hợp lệ hoặc chưa được phê duyệt.',
            ], 422);
        }

        if ($benNhan->id === $toChuc->id) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể chuyển giao cho chính tổ chức của bạn.',
            ], 422);
        }

        $soLuong = (int) $request->soLuong;
        try {
            $chuyenGiao = $this->chuyenGiaoService->tao($toChuc, [
                'loThuocId' => (int) $loThuoc->id,
                'benNhanId' => (int) $benNhan->id,
                'soLuong' => $soLuong,
            ], $user, $request->ip());
        } catch (\App\Exceptions\NghiepVuException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã khởi tạo chuyển giao. Hệ thống đang gửi giao dịch submitTransfer lên Ethereum Sepolia.',
            'data' => [
                'chuyenGiaoId' => $chuyenGiao->id,
                'trangThai'    => $chuyenGiao->trangThai,
                'soLuong'      => $chuyenGiao->soLuong,
                'benNhan'      => [
                    'id' => $benNhan->id,
                    'tenToChuc' => $benNhan->tenToChuc,
                ],
            ],
        ]);
    }
    /** API: Nhà sản xuất hủy yêu cầu chuyển giao đang chờ. */
    public function huy(Request $request, int $id)
    {
        $user = Auth::user();
        $toChuc = $user?->toChuc;
        if (!$toChuc) {
            return response()->json(['success' => false, 'message' => 'Tài khoản chưa gắn tổ chức.'], 403);
        }

        $chuyenGiao = \App\\Models\\ChuyenGiao::query()->find($id);
        if (!$chuyenGiao) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy giao dịch.'], 404);
        }

        try {
            $this->chuyenGiaoService->huy($chuyenGiao, $toChuc, $user, $request->ip(), $request->input('lyDo'));
            return response()->json(['success' => true, 'message' => 'Đã gửi yêu cầu hủy. Trạng thái chỉ đổi sau khi blockchain xác nhận.']);
        } catch (\App\\Exceptions\\NghiepVuException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Không thể gửi yêu cầu hủy.'], 500);
        }
    }

}

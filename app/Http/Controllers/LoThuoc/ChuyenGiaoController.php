<?php

namespace App\Http\Controllers\LoThuoc;

use App\Enums\OrganizationApprovalStatus;
use App\Enums\OrganizationType;
use App\Enums\TransferStatus;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessBatchTransferJob;
use App\Models\BlockchainIdentity;
use App\Models\BlockchainTransaction;
use App\Models\ChuyenGiao;
use App\Models\LoThuoc;
use App\Models\ToChuc;
use App\Services\ChuoiCungUng\TonKhoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChuyenGiaoController extends Controller
{
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

        // Ví on-chain bên nhận nên ACTIVE (khuyến nghị)
        $identityNhan = BlockchainIdentity::where('toChucId', $benNhan->id)
            ->where('trangThai', 'ACTIVE')
            ->first();

        $soLuong = (int) $request->soLuong;
        $tonKhoService = app(TonKhoService::class);
        $khaDung = $tonKhoService->soLuongKhaDung($loThuoc->id, $toChuc->id);

        if ($soLuong > $khaDung) {
            return response()->json([
                'success' => false,
                'message' => "Số lượng chuyển ({$soLuong}) vượt quá số khả dụng ({$khaDung}).",
            ], 422);
        }

        $maChuyenGiao = (string) Str::uuid();

        $chuyenGiao = DB::transaction(function () use (
            $request, $loThuoc, $toChuc, $benNhan, $soLuong, $maChuyenGiao, $user
        ) {
            $cg = ChuyenGiao::create([
                'maChuyenGiao'    => $maChuyenGiao,
                'loThuocId'       => $loThuoc->id,
                'benGuiId'        => $toChuc->id,
                'benNhanId'       => $benNhan->id,
                'soLuong'         => $soLuong,
                'trangThai'       => TransferStatus::pending->value, // đã xuất, chờ NPP nhận
                'thoiGianKhoiTao' => now(),
            ]);

            // Ghi blockchain_transaction PENDING gắn chuyenGiao
            BlockchainTransaction::create([
                'txHash'           => 'PENDING_CG_' . str_replace('-', '', $maChuyenGiao),
                'blockNumber'      => null,
                'eventName'        => 'BatchEventRecorded',
                'doiTuongToChucId' => $toChuc->id,
                'businessId'       => $cg->maChuyenGiao,
                'network'          => config('truyXuat.blockchain.network', 'Ethereum Sepolia Testnet'),
                'trangThai'        => 'PENDING',
                'toChucId'         => $toChuc->id,
                'sanPhamId'        => $loThuoc->sanPhamId,
                'loThuocId'        => $loThuoc->id,
                'chuyenGiaoId'     => $cg->id,
                'thoiGian'         => now(),
            ]);

            // Cập nhật trạng thái nghiệp vụ lô (đang phân phối) nếu còn hàng chuyển
            if ($loThuoc->trangThai === 'CREATED') {
                $loThuoc->update(['trangThai' => 'IN_TRANSIT']);
            }

            // Audit log
            if (class_exists(\App\Models\NhatKyHeThong::class)) {
                \App\Models\NhatKyHeThong::create([
                    'taiKhoanId' => $user->id,
                    'doiTuongId' => $cg->id,
                    'hanhDong'   => 'KHOI_TAO_CHUYEN_GIAO',
                    'ketQua'     => 'THANH_CONG',
                    'chiTiet'    => "NSX chuyển {$soLuong} hộp lô [{$loThuoc->maLoNghiepVu}] → NPP [{$benNhan->tenToChuc}]. Chờ ký On-chain.",
                ]);
            }

            return $cg;
        });

        ProcessBatchTransferJob::dispatch($chuyenGiao->id, $user->id)->afterCommit();

        return response()->json([
            'success' => true,
            'message' => 'Đã khởi tạo chuyển giao. Hệ thống đang ký giao dịch On-chain.',
            'data' => [
                'chuyenGiaoId' => $chuyenGiao->id,
                'trangThai'    => $chuyenGiao->trangThai,
                'soLuong'      => $chuyenGiao->soLuong,
                'benNhan'      => [
                    'id' => $benNhan->id,
                    'tenToChuc' => $benNhan->tenToChuc,
                ],
                'canhBaoVi' => $identityNhan ? null : 'Nhà phân phối chưa có ví On-chain ACTIVE. Vẫn ghi off-chain; on-chain có thể dùng ví Admin.',
            ],
        ]);
    }
}

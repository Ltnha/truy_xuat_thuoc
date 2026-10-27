<?php

namespace App\Http\Controllers\LoThuoc;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessBatchRegistrationJob;
use App\Models\BlockchainTransaction;
use App\Models\LoThuoc;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChiTietLoThuocController extends Controller
{
    /**
     * Trang chi tiết lô thuốc (giao diện mới - kết nối CSDL thật)
     */
    public function show(int $id)
    {
        $user = Auth::user();

        $loThuoc = LoThuoc::with(['sanPham', 'blockchainTransactions'])
            ->where('id', $id)
            ->where('toChucId', $user->toChucId ?? 0)
            ->firstOrFail();

        return view('nhaSanXuat.chiTietLoThuoc', compact('loThuoc'));
    }

    /**
     * API lấy dữ liệu chi tiết từ CSDL thật để JS render
     */
    public function getChiTietAPI(int $id)
    {
        $user = Auth::user();

        $loThuoc = LoThuoc::with([
                'sanPham',
                'blockchainTransactions',
                'chuyenGiao.benNhan',
            ])
            ->where('id', $id)
            ->where('toChucId', $user->toChucId ?? 0)
            ->first();

        if (!$loThuoc) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy lô thuốc hoặc bạn không có quyền xem.',
            ], 404);
        }

        // Lấy transaction mới nhất (theo id giảm dần)
        $tx = $loThuoc->blockchainTransactions
            ->sortByDesc('id')
            ->first();

        // Chuẩn hóa dữ liệu chuyển giao
        $danhSachChuyenGiao = [];
        if ($loThuoc->relationLoaded('chuyenGiao') && $loThuoc->chuyenGiao) {
            foreach ($loThuoc->chuyenGiao as $cg) {
                $danhSachChuyenGiao[] = [
                    'id'            => $cg->id,
                    'soLuong'       => $cg->soLuong ?? null,
                    'trangThai'     => $cg->trangThai?->value ?? (string) $cg->trangThai,
                    'thoiGianKhoiTao' => $cg->thoiGianKhoiTao,
                    'thoiGianXacNhan' => $cg->thoiGianXacNhan,
                    'benNhan'       => $cg->benNhan ? [
                        'id'        => $cg->benNhan->id,
                        'tenToChuc' => $cg->benNhan->tenToChuc ?? 'N/A',
                    ] : null,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id'                     => $loThuoc->id,
                'maLo'                   => $loThuoc->maLo,
                'maLoNghiepVu'           => $loThuoc->maLoNghiepVu,
                'soLuong'                => $loThuoc->soLuong,
                'ngaySanXuat'            => $loThuoc->ngaySanXuat,
                'hanSuDung'              => $loThuoc->hanSuDung,
                'trangThai'              => $loThuoc->trangThai,
                'created_at'             => $loThuoc->thoiGianTao ?? null, // bảng không có timestamps
                'san_pham'               => $loThuoc->sanPham ? [
                    'id'          => $loThuoc->sanPham->id,
                    'tenSanPham'  => $loThuoc->sanPham->tenSanPham,
                    'soDangKy'    => $loThuoc->sanPham->soDangKy,
                ] : null,
                'blockchain_transaction' => $tx ? [
                    'id'          => $tx->id,
                    'txHash'      => $tx->txHash,
                    'blockNumber' => $tx->blockNumber,
                    'eventName'   => $tx->eventName,
                    'trangThai'   => $tx->trangThai,
                    'network'     => $tx->network,
                    'thoiGian'    => $tx->thoiGian,
                    'businessId'  => $tx->businessId,
                ] : null,
                'chuyen_giao'            => $danhSachChuyenGiao,
            ],
        ]);
    }

    /**
     * API: Gửi / Gửi lại lô lên Blockchain (đẩy vào Queue thật)
     */
    public function guiBlockchain(int $id)
    {
        $user = Auth::user();

        $loThuoc = LoThuoc::where('id', $id)
            ->where('toChucId', $user->toChucId ?? 0)
            ->first();

        if (!$loThuoc) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy lô thuốc.',
            ], 404);
        }

        // Kiểm tra đã confirmed chưa
        $txHienTai = BlockchainTransaction::where('loThuocId', $loThuoc->id)
            ->orderByDesc('id')
            ->first();

        if ($txHienTai) {
            $st = strtoupper((string) ($txHienTai->trangThai ?? ''));
            $hash = (string) ($txHienTai->txHash ?? '');

            if (in_array($st, ['CONFIRMED', 'SUCCESS'], true) || ($hash && !str_starts_with($hash, 'PENDING_'))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lô thuốc này đã được ghi nhận On-chain thành công.',
                ], 422);
            }

            // Nếu đang PENDING thì không tạo record mới
            if ($st === 'PENDING' || str_starts_with($hash, 'PENDING_')) {
                // Vẫn dispatch lại job để retry
                ProcessBatchRegistrationJob::dispatch($loThuoc->id, $user->id);

                return response()->json([
                    'success' => true,
                    'message' => 'Đã yêu cầu xử lý lại hàng đợi Blockchain.',
                ]);
            }
        }

        // Tạo record PENDING mới
        BlockchainTransaction::create([
            'txHash'           => 'PENDING_' . str_replace('-', '', (string) $loThuoc->maLo),
            'blockNumber'      => null,
            'eventName'        => 'BatchEventRecorded',
            'doiTuongToChucId' => $loThuoc->toChucId,
            'businessId'       => $loThuoc->maLo,
            'network'          => config('truyXuat.blockchain.mang', 'Ethereum Sepolia'),
            'trangThai'        => 'PENDING',
            'toChucId'         => $loThuoc->toChucId,
            'sanPhamId'        => $loThuoc->sanPhamId,
            'loThuocId'        => $loThuoc->id,
            'thoiGian'         => now(),
        ]);

        // Đẩy vào Queue thật
        ProcessBatchRegistrationJob::dispatch($loThuoc->id, $user->id);

        return response()->json([
            'success' => true,
            'message' => 'Đã đưa yêu cầu gửi Blockchain vào hàng đợi.',
        ]);
    }
}

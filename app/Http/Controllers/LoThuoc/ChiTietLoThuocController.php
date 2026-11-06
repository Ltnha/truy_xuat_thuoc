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
        $loThuoc = LoThuoc::query()
            ->whereKey($id)
            ->where('toChucId', $user->toChucId ?? 0)
            ->first();

        if (! $loThuoc) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy lô thuốc.'], 404);
        }

        $identity = \App\Models\BlockchainIdentity::query()
            ->where('toChucId', $loThuoc->toChucId)
            ->where('trangThai', 'ACTIVE')
            ->whereNotNull('signerKeyId')
            ->first();

        if (! $identity || trim((string) $identity->signerKeyId) === '') {
            return response()->json(['success' => false, 'message' => 'Nhà sản xuất chưa có ví Signer ACTIVE.'], 422);
        }

        $tx = BlockchainTransaction::query()
            ->where('loThuocId', $loThuoc->id)
            ->where('eventName', 'BatchEventRecorded')
            ->orderByDesc('id')
            ->first();

        if ($tx && strtoupper((string) $tx->trangThai) === 'CONFIRMED'
            && preg_match('/^0x[a-fA-F0-9]{64}$/', (string) $tx->txHash)) {
            return response()->json(['success' => false, 'message' => 'Lô thuốc đã được ghi nhận On-chain thành công.'], 422);
        }

        if ($tx && strtoupper((string) $tx->trangThai) === 'PENDING') {
            return response()->json(['success' => true, 'message' => 'Yêu cầu ghi nhận lô đang được xử lý. Vui lòng chờ kết quả.']);
        }

        if (! $tx) {
            $tx = BlockchainTransaction::query()->create([
                'txHash' => null,
                'blockNumber' => null,
                'eventName' => 'BatchEventRecorded',
                'doiTuongToChucId' => $loThuoc->toChucId,
                'businessId' => $loThuoc->maLo,
                'network' => config('truyXuat.blockchain.network', 'Ethereum Sepolia Testnet'),
                'trangThai' => 'PENDING',
                'toChucId' => $loThuoc->toChucId,
                'sanPhamId' => $loThuoc->sanPhamId,
                'loThuocId' => $loThuoc->id,
                'thoiGian' => now(),
            ]);
        } else {
            $tx->update(['txHash' => null, 'blockNumber' => null, 'trangThai' => 'PENDING', 'thoiGian' => now()]);
        }

        ProcessBatchRegistrationJob::dispatch($loThuoc->id, $user->id)->afterCommit();

        return response()->json([
            'success' => true,
            'message' => 'Đã đưa yêu cầu đăng ký lô vào hàng đợi. Hệ thống sẽ ký bằng ví của Nhà sản xuất.',
        ]);
    }
}

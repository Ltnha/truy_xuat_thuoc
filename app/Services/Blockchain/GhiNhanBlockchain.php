<?php

namespace App\Services\Blockchain;

use App\Exceptions\NghiepVuException;
use App\Models\BlockchainTransaction;
use App\Models\LoThuoc;
use App\Models\ToChuc;
use Illuminate\Support\Facades\Log;

class GhiNhanBlockchain
{
    public function __construct(protected BlockchainGateway $gateway) {}

    public function ghi(
        string $tenNghiepVu,
        string $businessId,
        ToChuc $toChuc,
        LoThuoc $loThuoc,
        array $thamChieu = [],
    ): string {
        $transactionId = $thamChieu['blockchainTransactionId'] ?? null;
        unset($thamChieu['blockchainTransactionId']);

        $loThuoc->loadMissing('sanPham');
        $ketQua = $this->gateway->ghiNhan($tenNghiepVu, [
            'businessId' => $businessId,
            'maGiaoDich' => $businessId,
            'toChucId' => $toChuc->id,
            'loThuocId' => $loThuoc->id,
            'maLo' => $loThuoc->maLoNghiepVu ?? $loThuoc->maLo,
            'maLoId' => (string) $loThuoc->maLo,
            'maSanPham' => (string) ($loThuoc->sanPham?->maSanPham ?? ''),
            ...$thamChieu,
        ]);

        $txHash = $ketQua['txHash'] ?? null;
        if (! is_string($txHash) || ! preg_match('/^0x[a-fA-F0-9]{64}$/', $txHash)) {
            throw new NghiepVuException('Blockchain không trả về Ethereum transaction hash hợp lệ.');
        }

        $data = [
            'txHash' => $txHash,
            'blockNumber' => $ketQua['blockNumber'] ?? null,
            'eventName' => $tenNghiepVu,
            'doiTuongToChucId' => $toChuc->id,
            'businessId' => $businessId,
            'payloadHash' => $ketQua['payloadHash'] ?? null,
            'thoiGian' => now(),
            'network' => config('truyXuat.blockchain.mang', 'Ethereum Sepolia Testnet'),
            'trangThai' => 'CONFIRMED',
            'toChucId' => $toChuc->id,
            'sanPhamId' => $loThuoc->sanPhamId,
            'loThuocId' => $loThuoc->id,
            'chuyenGiaoId' => $thamChieu['chuyenGiaoId'] ?? null,
            'banLeId' => $thamChieu['banLeId'] ?? null,
            'chiTietBanLeId' => $thamChieu['chiTietBanLeId'] ?? null,
        ];

        if ($transactionId) {
            BlockchainTransaction::query()->whereKey($transactionId)->update($data);
        } else {
            BlockchainTransaction::query()->create($data);
        }

        Log::info('Blockchain event confirmed: ' . $tenNghiepVu, [
            'businessId' => $businessId,
            'txHash' => $txHash,
        ]);

        return $txHash;
    }

    public function diaChiVi(ToChuc $toChuc): string
    {
        $diaChi = $toChuc->blockchainIdentity?->diaChiVi;
        if (! is_string($diaChi) || $diaChi === '') {
            throw new NghiepVuException('Tổ chức chưa được cấp định danh blockchain.');
        }
        return $diaChi;
    }
}

<?php
namespace App\Services\Blockchain;

use App\Exceptions\NghiepVuException;
use App\Models\BlockchainTransaction;
use App\Models\LoThuoc;
use App\Models\ToChuc;
use Illuminate\Support\Facades\Log;

class GhiNhanBlockchain
{
    public function __construct(
        protected BlockchainGateway $gateway
    ) {
    }

    public function ghi(
        string $tenNghiepVu,
        string $businessId,
        ToChuc $toChuc,
        LoThuoc $loThuoc,
        array $thamChieu = [],
    ): string
    {
        $ketQua = $this->gateway->ghiNhan($tenNghiepVu, [
            'businessId' => $businessId,
            'toChucId' => $toChuc->id,
            'loThuocId' => $loThuoc->id,
            ...$thamChieu,
        ]);
        $txHash = $ketQua['txHash'] ?? null;

        if (! is_string($txHash) || $txHash === '') {
            throw new NghiepVuException('Blockchain không trả về mã giao dịch hợp lệ.');
        }

        BlockchainTransaction::create([
            'txHash' => $txHash,
            'blockNumber' => $ketQua['blockNumber'] ?? null,
            'eventName' => $tenNghiepVu,
            'businessId' => $businessId,
            'thoiGian' => now(),
            'network' => config('truyXuat.blockchain.mang'),
            'trangThai' => $ketQua['status'] === 'submitted' ? 'PENDING' : 'CONFIRMED',
            'toChucId' => $toChuc->id,
            'loThuocId' => $loThuoc->id,
            'chuyenGiaoId' => $thamChieu['chuyenGiaoId'] ?? null,
            'donViSanPhamId' => $thamChieu['donViSanPhamId'] ?? null,
            'banLeId' => $thamChieu['banLeId'] ?? null,
        ]);

        Log::info('Blockchain submission recorded: '.$tenNghiepVu, ['businessId' => $businessId, 'txHash' => $txHash]);

        return $txHash;
    }

    public function diaChiVi(ToChuc $toChuc): string
    {
        $diaChi = $toChuc->blockchainIdentity?->address;

        if (! is_string($diaChi) || $diaChi === '') {
            throw new NghiepVuException('Tổ chức chưa được cấp định danh blockchain.');
        }

        return $diaChi;
    }
}
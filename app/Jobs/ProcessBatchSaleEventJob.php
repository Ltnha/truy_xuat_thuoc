<?php

namespace App\Jobs;

use App\Models\BlockchainTransaction;
use App\Models\ToChuc;
use App\Services\Blockchain\GhiNhanBlockchain;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessBatchSaleEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public array $backoff = [10, 30, 60, 120, 300];

    public function __construct(public int $blockchainTransactionId) {}

    public function handle(GhiNhanBlockchain $ghiNhanBlockchain): void
    {
        $tx = BlockchainTransaction::with(['loThuoc.sanPham', 'banLe', 'chiTietBanLe'])
            ->findOrFail($this->blockchainTransactionId);

        if (strtoupper((string) $tx->trangThai) === 'CONFIRMED'
            && is_string($tx->txHash)
            && preg_match('/^0x[a-fA-F0-9]{64}$/', $tx->txHash)) {
            return;
        }

        if (! $tx->loThuoc || ! $tx->banLe || ! $tx->chiTietBanLe) {
            throw new \RuntimeException('Giao dịch SOLD thiếu hóa đơn, chi tiết hóa đơn hoặc lô thuốc.');
        }

        $organization = ToChuc::findOrFail($tx->toChucId);
        $detail = $tx->chiTietBanLe;
        $invoice = $tx->banLe;

        $tx->update([
            'soLanThu' => (int) $tx->soLanThu + 1,
            'lanThuCuoi' => now(),
            'trangThai' => 'PENDING',
            'loiCuoi' => null,
        ]);

        try {
            $ghiNhanBlockchain->ghi('SOLD', (string) $tx->businessId, $organization, $tx->loThuoc, [
                'blockchainTransactionId' => $tx->id,
                'banLeId' => $invoice->id,
                'chiTietBanLeId' => $detail->id,
                'maHoaDon' => $invoice->maHoaDon,
                'ngayBan' => (string) $invoice->ngayBan,
                'soLuong' => (int) $detail->soLuong,
                'donGia' => (string) $detail->donGia,
                'thanhTien' => (string) $detail->thanhTien,
            ]);
        } catch (Throwable $exception) {
            $tx->refresh();
            if (strtoupper((string) $tx->trangThai) !== 'CONFIRMED') {
                $tx->update([
                    'trangThai' => 'FAILED',
                    'loiCuoi' => mb_substr($exception->getMessage(), 0, 60000),
                    'lanThuCuoi' => now(),
                ]);
            }
            Log::warning('[ProcessBatchSaleEventJob] Không thể đồng bộ SOLD lên Sepolia.', [
                'blockchainTransactionId' => $tx->id,
                'maHoaDon' => $invoice->maHoaDon,
                'reason' => $exception->getMessage(),
            ]);
            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $tx = BlockchainTransaction::find($this->blockchainTransactionId);
        if ($tx && strtoupper((string) $tx->trangThai) !== 'CONFIRMED') {
            $tx->update([
                'trangThai' => 'FAILED',
                'loiCuoi' => mb_substr((string) ($exception?->getMessage() ?? 'Không rõ lỗi.'), 0, 60000),
                'lanThuCuoi' => now(),
            ]);
        }
    }
}

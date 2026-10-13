<?php

namespace App\Services\Blockchain;

use App\Exceptions\NghiepVuException;
use App\Models\BanLe;
use App\Models\BlockchainOutbox;
use App\Models\BlockchainTransaction;
use App\Models\ChuyenGiao;
use App\Models\DonViSanPham;
use App\Models\LoThuoc;
use App\Models\SanPham;
use App\Models\ThuHoiLoHang;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Throwable;

class BlockchainOutboxProcessor
{
    public function __construct(private BlockchainSigner $signer) {}

    public function process(BlockchainOutbox $outbox): void
    {
        try {
            if ($outbox->trangThai === 'QUEUED' || $outbox->trangThai === 'RETRY') {
                $txHash = $this->signer->submit(
                    $outbox->payload['data'],
                    $outbox->idempotencyKey,
                );
                $outbox->forceFill([
                    'txHash' => $txHash,
                    'trangThai' => 'SUBMITTED',
                    'soLanThu' => $outbox->soLanThu + 1,
                    'thoiGianGuiGanNhat' => now(),
                    'loiGanNhat' => null,
                    'thoiGianThuLai' => null,
                ])->save();
            }

            if ($outbox->fresh()->trangThai === 'SUBMITTED') {
                $this->checkReceipt($outbox->fresh());
            }
        } catch (Throwable $exception) {
            $isSubmitted = $outbox->trangThai === 'SUBMITTED';
            $attempts = $outbox->soLanThu + ($isSubmitted ? 0 : 1);
            $failed = $attempts >= (int) config('truyXuat.blockchain.maxAttempts', 8);
            $outbox->forceFill([
                'trangThai' => $isSubmitted ? 'SUBMITTED' : ($failed ? 'FAILED' : 'RETRY'),
                'soLanThu' => $attempts,
                'loiGanNhat' => mb_substr($exception->getMessage(), 0, 4000),
                'thoiGianThuLai' => $failed ? null : now()->addSeconds(min(3600, 15 * (2 ** min(max(0, $attempts - 1), 8)))),
            ])->save();

            report($exception);
        }
    }

    private function checkReceipt(BlockchainOutbox $outbox): void
    {
        $this->assertSepoliaConfiguration();
        $receipt = $this->rpc('eth_getTransactionReceipt', [$outbox->txHash]);

        if ($receipt === null) {
            return;
        }
        if (! is_array($receipt)
            || strtolower((string) ($receipt['transactionHash'] ?? '')) !== strtolower((string) $outbox->txHash)
            || strtolower((string) ($receipt['to'] ?? '')) !== strtolower((string) config('truyXuat.blockchain.diaChiHopDong'))
        ) {
            throw new NghiepVuException('Receipt không khớp transaction hoặc contract đã cấu hình.');
        }
        if (($receipt['status'] ?? null) !== '0x1') {
            $outbox->forceFill([
                'trangThai' => 'FAILED',
                'loiGanNhat' => 'Transaction đã được mine nhưng contract đã revert.',
                'blockNumber' => isset($receipt['blockNumber']) ? hexdec($receipt['blockNumber']) : null,
            ])->save();

            return;
        }

        $blockNumberHex = $receipt['blockNumber'] ?? null;
        if (! is_string($blockNumberHex) || ! preg_match('/^0x[a-fA-F0-9]+$/', $blockNumberHex)) {
            throw new NghiepVuException('Receipt không có block number hợp lệ.');
        }

        $this->assertExpectedEvent($outbox, $receipt['logs'] ?? null);
        $head = $this->rpc('eth_blockNumber', []);
        if (! is_string($head) || ! preg_match('/^0x[a-fA-F0-9]+$/', $head)) {
            throw new NghiepVuException('RPC trả về block hiện tại không hợp lệ.');
        }

        $blockNumber = hexdec($blockNumberHex);
        $confirmations = max(0, hexdec($head) - $blockNumber + 1);
        $required = max(1, (int) config('truyXuat.blockchain.confirmations', 3));
        if ($confirmations < $required) {
            $outbox->forceFill([
                'soConfirmations' => $confirmations,
                'blockNumber' => $blockNumber,
            ])->save();

            return;
        }

        $this->confirm($outbox, $blockNumber, $confirmations);
    }

    private function assertExpectedEvent(BlockchainOutbox $outbox, mixed $logs): void
    {
        if (! is_array($logs)) {
            throw new NghiepVuException('Receipt không có danh sách event logs hợp lệ.');
        }

        $expectedTopic = strtolower((string) ($outbox->payload['eventTopic'] ?? ''));
        $expectedIndexed = array_map('strtolower', $outbox->payload['indexedTopics'] ?? []);
        foreach ($logs as $log) {
            if (! is_array($log)
                || strtolower((string) ($log['address'] ?? '')) !== strtolower((string) config('truyXuat.blockchain.diaChiHopDong'))
                || ! is_array($log['topics'] ?? null)
                || strtolower((string) ($log['topics'][0] ?? '')) !== $expectedTopic
            ) {
                continue;
            }

            $actualIndexed = array_map('strtolower', array_slice($log['topics'], 1));
            if (count($actualIndexed) < count($expectedIndexed)) {
                continue;
            }
            foreach ($expectedIndexed as $index => $expected) {
                if (($actualIndexed[$index] ?? null) !== $expected) {
                    continue 2;
                }
            }

            return;
        }

        throw new NghiepVuException('Receipt không chứa event blockchain đúng với nghiệp vụ đã gửi.');
    }

    private function confirm(BlockchainOutbox $outbox, int $blockNumber, int $confirmations): void
    {
        DB::transaction(function () use ($outbox, $blockNumber, $confirmations): void {
            $locked = BlockchainOutbox::query()->whereKey($outbox->id)->lockForUpdate()->firstOrFail();
            if ($locked->trangThai === 'CONFIRMED') {
                return;
            }

            if ($locked->loThuocId === null && $locked->toChucId === null) {
                throw new NghiepVuException('Không thể liên kết giao dịch blockchain với bản ghi nghiệp vụ.');
            }

            BlockchainTransaction::query()->firstOrCreate(
                ['txHash' => $locked->txHash],
                [
                    'blockNumber' => $blockNumber,
                    'eventName' => $locked->eventName,
                    'businessId' => $locked->businessId,
                    'thoiGian' => now(),
                    'network' => $locked->network,
                    'trangThai' => 'CONFIRMED',
                    'toChucId' => $locked->toChucId,
                    'loThuocId' => $locked->loThuocId,
                    'chuyenGiaoId' => $locked->chuyenGiaoId,
                    'donViSanPhamId' => $locked->donViSanPhamId,
                    'banLeId' => $locked->banLeId,
                ],
            );

            $locked->forceFill([
                'trangThai' => 'CONFIRMED',
                'blockNumber' => $blockNumber,
                'soConfirmations' => $confirmations,
                'thoiGianXacNhan' => now(),
                'loiGanNhat' => null,
            ])->save();

            $this->synchronizeBusinessHash($locked);
            $this->finalizeReceivedTransfer($locked);
        });
    }

    private function synchronizeBusinessHash(BlockchainOutbox $outbox): void
    {
        if ($outbox->loThuocId !== null && Schema::hasColumn('loThuoc', 'txHash') && $outbox->eventName === 'BatchRegistered') {
            LoThuoc::query()->whereKey($outbox->loThuocId)->update(['txHash' => $outbox->txHash]);
        }
        if ($outbox->chuyenGiaoId !== null && Schema::hasColumn('chuyenGiao', 'txHash')) {
            ChuyenGiao::query()->whereKey($outbox->chuyenGiaoId)->update(['txHash' => $outbox->txHash]);
        }
        if ($outbox->donViSanPhamId !== null && Schema::hasColumn('donViSanPham', 'txHash')) {
            DonViSanPham::query()->whereKey($outbox->donViSanPhamId)->update(['txHash' => $outbox->txHash]);
        }
        if ($outbox->banLeId !== null && Schema::hasColumn('banLe', 'txHash')) {
            BanLe::query()->whereKey($outbox->banLeId)->update(['txHash' => $outbox->txHash]);
        }
        if ($outbox->eventName === 'BatchRecalled' && $outbox->loThuocId !== null) {
            $recall = ThuHoiLoHang::query()->where('loThuocId', $outbox->loThuocId)->latest('id')->first();
            if ($recall && Schema::hasColumn('thuHoiLoHang', 'txHash')) {
                $recall->update(['txHash' => $outbox->txHash, 'trangThai' => 'CONFIRMED']);
            }
        }
        if (in_array($outbox->eventName, ['ProductApproved', 'ProductRejected'], true)
            && Schema::hasColumn('sanPham', 'txHash')
        ) {
            SanPham::query()->whereKey($outbox->payload['references']['productId'] ?? 0)->update(['txHash' => $outbox->txHash]);
        }
    }

    private function finalizeReceivedTransfer(BlockchainOutbox $outbox): void
    {
        if ($outbox->eventName !== 'TransferReceived' || $outbox->chuyenGiaoId === null) {
            return;
        }

        $transfer = ChuyenGiao::query()->whereKey($outbox->chuyenGiaoId)->lockForUpdate()->firstOrFail();
        if ($transfer->trangThai === \App\Enums\TransferStatus::received) {
            return;
        }

        $quantity = (int) $transfer->soLuong;
        $inventory = \App\Models\TonKhoLo::query()
            ->where('loThuocId', $transfer->loThuocId)
            ->where('toChucId', $transfer->benGuiId)
            ->lockForUpdate()
            ->first();
        if (! $inventory || (int) $inventory->soLuong < $quantity) {
            throw new NghiepVuException('Không đủ tồn kho bên gửi khi xác nhận chuyển giao blockchain.');
        }

        app(\App\Services\ChuoiCungUng\TonKhoService::class)->tru($transfer->loThuocId, $transfer->benGuiId, $quantity);
        app(\App\Services\ChuoiCungUng\TonKhoService::class)->cong($transfer->loThuocId, $transfer->benNhanId, $quantity);
        $transfer->update([
            'trangThai' => \App\Enums\TransferStatus::received,
            'thoiGianXacNhan' => now(),
            'txHash' => $outbox->txHash,
        ]);
    }

    private function assertSepoliaConfiguration(): void
    {
        if ((int) config('truyXuat.blockchain.chainId') !== 11155111) {
            throw new NghiepVuException('BLOCKCHAIN_CHAIN_ID phải là Ethereum Sepolia (11155111).');
        }

        $url = config('truyXuat.blockchain.rpcUrl');
        $contract = config('truyXuat.blockchain.diaChiHopDong');
        if (! is_string($url) || ! filter_var($url, FILTER_VALIDATE_URL)
            || ! is_string($contract) || ! preg_match('/^0x[a-fA-F0-9]{40}$/', $contract)
        ) {
            throw new NghiepVuException('Cấu hình RPC hoặc địa chỉ contract Sepolia không hợp lệ.');
        }

        $chainId = $this->rpc('eth_chainId', []);
        if (! is_string($chainId) || hexdec($chainId) !== 11155111) {
            throw new NghiepVuException('RPC đang kết nối không phải Ethereum Sepolia.');
        }
    }

    private function rpc(string $method, array $params): mixed
    {
        $response = Http::acceptJson()
            ->timeout(12)
            ->post(config('truyXuat.blockchain.rpcUrl'), [
                'jsonrpc' => '2.0',
                'method' => $method,
                'params' => $params,
                'id' => 1,
            ]);

        if (! $response->successful()) {
            throw new NghiepVuException("RPC Sepolia trả HTTP {$response->status()}.");
        }

        $body = $response->json();
        if (! is_array($body) || isset($body['error']) || ! array_key_exists('result', $body)) {
            $message = is_array($body) ? ($body['error']['message'] ?? 'phản hồi JSON-RPC không hợp lệ') : 'phản hồi JSON-RPC không hợp lệ';
            throw new NghiepVuException('Lỗi RPC Sepolia: '.$message);
        }

        return $body['result'];
    }
}

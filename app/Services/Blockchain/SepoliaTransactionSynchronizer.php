<?php

namespace App\Services\Blockchain;

use App\Exceptions\BlockchainRejectedException;
use App\Models\BlockchainTransaction;
use App\Models\LichSuLoThuoc;
use App\Models\LoThuoc;
use App\Models\ToChuc;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use kornrunner\Keccak;
use Throwable;

class SepoliaTransactionSynchronizer
{
    public function __construct(private SepoliaBatchEvent $batchEvent) {}

    public function synchronizeBatchRegistration(LoThuoc $loThuoc, ToChuc $toChuc, string $txHash): array
    {
        $txHash = strtolower($txHash);
        if (! preg_match('/^0x[a-f0-9]{64}$/', $txHash)) {
            throw new BlockchainRejectedException('Transaction hash không đúng định dạng.');
        }

        $this->assertConfiguration();

        if ((int) config('truyXuat.blockchain.chainId') !== 11155111) {
            throw new BlockchainRejectedException('Cấu hình chain ID phải là Ethereum Sepolia (11155111).');
        }

        $rpcChainId = $this->rpc('eth_chainId', []);
        if (! is_string($rpcChainId) || hexdec($rpcChainId) !== 11155111) {
            throw new BlockchainRejectedException('RPC đang kết nối không phải Ethereum Sepolia.');
        }

        $receipt = $this->rpc('eth_getTransactionReceipt', [$txHash]);
        if (! is_array($receipt)) {
            throw new BlockchainRejectedException('Chưa tìm thấy receipt. Hãy đợi transaction được mine rồi thử lại.');
        }

        if (strtolower((string) ($receipt['transactionHash'] ?? '')) !== $txHash) {
            throw new BlockchainRejectedException('RPC trả về receipt không khớp transaction hash đã nhập.');
        }

        if (strtolower((string) ($receipt['to'] ?? '')) !== strtolower(config('truyXuat.blockchain.diaChiHopDong'))) {
            throw new BlockchainRejectedException('Transaction không gọi đúng contract Sepolia đang cấu hình trong ứng dụng.');
        }

        if (($receipt['status'] ?? null) !== '0x1') {
            throw new BlockchainRejectedException('Transaction trên blockchain đã thất bại; không thể lưu như giao dịch thành công.');
        }

        if (
            ! is_string($receipt['blockNumber'] ?? null)
            || ! preg_match('/^0x[a-fA-F0-9]+$/', $receipt['blockNumber'])
        ) {
            throw new BlockchainRejectedException('Receipt không có block number hợp lệ.');
        }

        $logs = $receipt['logs'] ?? null;
        if (! is_array($logs)) {
            throw new BlockchainRejectedException('Receipt không chứa danh sách event logs hợp lệ.');
        }

        $expected = $this->batchEvent->inputs($loThuoc);
        $actor = $this->findMatchingActor($logs, $expected);
        $sender = strtolower((string) ($receipt['from'] ?? ''));

        if ($actor === null || ! preg_match('/^0x[a-f0-9]{40}$/', $sender) || $actor !== $sender) {
            throw new BlockchainRejectedException('Receipt không có event đăng ký đúng lô hoặc địa chỉ gửi không hợp lệ.');
        }

        $this->assertWriterAtBlock($actor, (string) $receipt['blockNumber']);

        $blockNumber = hexdec($receipt['blockNumber']);
        $headBlock = $this->rpc('eth_blockNumber', []);
        if (! is_string($headBlock) || ! preg_match('/^0x[a-fA-F0-9]+$/', $headBlock)) {
            throw new BlockchainRejectedException('RPC trả về block hiện tại không hợp lệ.');
        }
        $currentBlock = hexdec($headBlock);
        $confirmations = max(0, $currentBlock - $blockNumber + 1);
        $requiredConfirmations = max(1, (int) config('truyXuat.blockchain.confirmations', 3));
        $status = $confirmations >= $requiredConfirmations ? 'CONFIRMED' : 'PENDING';

        return DB::transaction(function () use ($loThuoc, $toChuc, $txHash, $blockNumber, $status, $confirmations, $requiredConfirmations): array {
            $transaction = BlockchainTransaction::query()->where('txHash', $txHash)->lockForUpdate()->first();
            $created = $transaction === null;
            $transaction ??= new BlockchainTransaction;

            if (! $created && (
                (int) $transaction->loThuocId !== (int) $loThuoc->id
                || (int) $transaction->toChucId !== (int) $toChuc->id
                || $transaction->eventName !== 'BatchRegistered'
            )) {
                throw new BlockchainRejectedException('Transaction này đã được liên kết với một dữ liệu nghiệp vụ khác.');
            }

            $transaction->fill([
                'txHash' => $txHash,
                'blockNumber' => $blockNumber,
                'eventName' => 'BatchRegistered',
                'businessId' => $loThuoc->maLo,
                'thoiGian' => now(),
                'network' => 'Ethereum Sepolia',
                'trangThai' => $status,
                'toChucId' => $toChuc->id,
                'loThuocId' => $loThuoc->id,
            ]);
            $transaction->save();

            if ($created) {
                LichSuLoThuoc::query()->create([
                    'loThuocId' => $loThuoc->id,
                    'suKien' => 'ON_CHAIN_BATCH_REGISTERED',
                    'moTa' => 'Đã xác minh event đăng ký lô trên Ethereum Sepolia.',
                    'txHash' => $txHash,
                ]);
            }

            return [
                'status' => $status,
                'confirmations' => $confirmations,
                'requiredConfirmations' => $requiredConfirmations,
                'txHash' => $txHash,
            ];
        });
    }

    private function assertConfiguration(): void
    {
        $rpcUrl = config('truyXuat.blockchain.rpcUrl');
        $contractAddress = config('truyXuat.blockchain.diaChiHopDong');

        if (! is_string($rpcUrl) || ! filter_var($rpcUrl, FILTER_VALIDATE_URL)) {
            throw new BlockchainRejectedException('Chưa cấu hình BLOCKCHAIN_RPC_URL trong .env.');
        }

        if (! is_string($contractAddress) || ! preg_match('/^0x[a-fA-F0-9]{40}$/', $contractAddress)) {
            throw new BlockchainRejectedException('Hãy cấu hình địa chỉ contract Sepolia hợp lệ trong BLOCKCHAIN_CONTRACT_ADDRESS.');
        }
    }

    private function findMatchingActor(array $logs, array $expected): ?string
    {
        foreach ($logs as $log) {
            if (! is_array($log)) {
                continue;
            }

            $topics = $log['topics'] ?? [];
            $data = $log['data'] ?? null;
            if (
                ! is_array($topics)
                || count($topics) !== 4
                || strtolower((string) ($log['address'] ?? '')) !== strtolower(config('truyXuat.blockchain.diaChiHopDong'))
                || strtolower((string) ($topics[0] ?? '')) !== strtolower($this->batchEvent->eventTopic())
                || strtolower((string) ($topics[1] ?? '')) !== strtolower($expected['batchId'])
                || strtolower((string) ($topics[2] ?? '')) !== strtolower($expected['businessId'])
                || ! is_string($topics[3] ?? null)
                || ! preg_match('/^0x0{24}[a-fA-F0-9]{40}$/', $topics[3])
                || ! is_string($data)
                || ! preg_match('/^0x[a-fA-F0-9]{192}$/', $data)
            ) {
                continue;
            }

            $eventType = '0x'.substr($data, 2, 64);
            $payloadHash = '0x'.substr($data, 66, 64);
            if (
                strtolower($eventType) === strtolower($expected['eventType'])
                && strtolower($payloadHash) === strtolower($expected['payloadHash'])
            ) {
                return '0x'.substr($topics[3], -40);
            }
        }

        return null;
    }

    private function assertWriterAtBlock(string $actor, string $blockNumber): void
    {
        $selector = substr(Keccak::hash('writers(address)', 256), 0, 8);
        $argument = str_pad(substr($actor, 2), 64, '0', STR_PAD_LEFT);
        $isWriter = $this->rpc('eth_call', [[
            'to' => config('truyXuat.blockchain.diaChiHopDong'),
            'data' => '0x'.$selector.$argument,
        ], $blockNumber]);

        if (! is_string($isWriter) || strtolower($isWriter) !== '0x'.str_repeat('0', 63).'1') {
            throw new BlockchainRejectedException('Ví gửi transaction chưa được contract cấp quyền writer tại block đó.');
        }
    }

    private function rpc(string $method, array $params): mixed
    {
        try {
            $response = Http::acceptJson()
                ->timeout(12)
                ->post(config('truyXuat.blockchain.rpcUrl'), [
                    'jsonrpc' => '2.0',
                    'method' => $method,
                    'params' => $params,
                    'id' => 1,
                ]);
        } catch (Throwable $exception) {
            throw new BlockchainRejectedException('Không kết nối được RPC Sepolia: '.$exception->getMessage(), previous: $exception);
        }

        if (! $response->successful()) {
            throw new BlockchainRejectedException("RPC Sepolia trả HTTP {$response->status()}.");
        }

        $body = $response->json();
        if (! is_array($body) || isset($body['error']) || ! array_key_exists('result', $body)) {
            $message = is_array($body) ? ($body['error']['message'] ?? 'phản hồi JSON-RPC không hợp lệ') : 'phản hồi JSON-RPC không hợp lệ';
            throw new BlockchainRejectedException('Lỗi RPC Sepolia: '.$message);
        }

        return $body['result'];
    }
}

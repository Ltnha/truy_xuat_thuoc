<?php

namespace App\Services\Blockchain;

use Illuminate\Support\Facades\Http;
use Exception;

class SignerClient
{
    protected string $baseUrl;
    protected string $apiToken;

    public function __construct()
    {
        $baseUrl = (string) config('truyXuat.blockchain.signerUrl', '');
        if ($baseUrl === '') {
            $baseUrl = (string) env('SIGNER_SERVICE_URL', 'http://127.0.0.1:3000');
        }
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiToken = (string) config('truyXuat.blockchain.signerApiToken', env('SIGNER_API_TOKEN', ''));
    }

    /**
     * Yêu cầu Signer Service sinh cặp khóa ví mới cho tổ chức
     */
    public function taoVi(int $toChucId): array
    {
        $this->assertConfigured();
        $response = Http::withToken($this->apiToken)->post("{$this->baseUrl}/wallets", [
            'toChucId' => $toChucId,
        ]);

        if ($response->failed() || !$response->json('ok')) {
            throw new Exception("Lỗi Signer [taoVi]: " . ($response->json('reason') ?? $response->body()));
        }

        return [
            'keyId' => $response->json('keyId'),
            'diaChiVi' => $response->json('diaChiVi'),
        ];
    }

    /**
     * Yêu cầu Ví kho (Treasury) nạp Gas tự động cho ví mục tiêu
     */
    public function ensureGas(string $targetAddress): void
    {
        $this->assertConfigured();

        // Kiểm tra địa chỉ ví Ethereum
        if (! preg_match('/^0x[a-fA-F0-9]{40}$/', $targetAddress)) {
            throw new Exception(
                'Địa chỉ ví Ethereum không hợp lệ: ' . $targetAddress
            );
        }

        $response = Http::withToken($this->apiToken)->post(
            "{$this->baseUrl}/gas/ensure",
            [
                'target' => $targetAddress,
            ]
        );

        if ($response->failed() || ! $response->json('ok')) {
            throw new Exception(
                "Lỗi Signer [ensureGas]: " .
                ($response->json('reason') ?? $response->body())
            );
        }
    }

    /**
     * Ký và gửi giao dịch On-chain bất kỳ thuộc danh sách trắng
     */
    public function tx(string $keyId, string $action, array $params): array
    {
        $this->assertConfigured();
        $response = Http::withToken($this->apiToken)->post("{$this->baseUrl}/tx", [
            'keyId'  => $keyId,
            'action' => $action,
            'params' => $params,
        ]);

        if ($response->failed()) {
            return [
                'ok' => false,
                'reason' => $response->json('reason') ?? 'Signer Service không phản hồi',
            ];
        }

        return $response->json();
    }

    /**
     * Phương thức bổ trợ tương thích với DuyetSanPhamController & Queue Job
     */
    public function sendTransaction(array $payload): array
    {
        $keyId  = $payload['keyId'] ?? env('SIGNER_DEFAULT_KEY_ID', 'ADMIN_MASTER_KEY');
        $action = $payload['action'] ?? '';
        $params = $payload['params'] ?? [];

        $result = $this->tx($keyId, $action, $params);

        if (isset($result['ok']) && $result['ok'] === false) {
            throw new Exception("Lỗi Signer [sendTransaction]: " . ($result['reason'] ?? 'Giao dịch thất bại'));
        }

        return $result;
    }

    /**
     * Kiểm tra trạng thái vai trò On-chain
     */
    public function hasRole(string $roleHash, string $accountAddress): bool
    {
        $this->assertConfigured();
        $response = Http::withToken($this->apiToken)->get("{$this->baseUrl}/roles/check", [
            'role'    => $roleHash,
            'account' => $accountAddress,
        ]);

        return $response->successful() && $response->json('hasRole') === true;
    }

    /**
     * Yêu cầu Signer Service xoay ví (Rotate Wallet) sinh cặp khóa ví mới
     */
    public function doiViMoi(int $toChucId): array
    {
        $this->assertConfigured();
        $response = Http::withToken($this->apiToken)->post("{$this->baseUrl}/wallets/rotate", [
            'toChucId' => $toChucId,
        ]);

        if ($response->failed() || !$response->json('ok')) {
            throw new \Exception("Lỗi Signer [doiViMoi]: " . ($response->json('reason') ?? $response->body()));
        }

        return [
            'keyId' => $response->json('keyId'),
            'diaChiVi' => $response->json('diaChiVi'),
        ];
    }

    /**
     * Kiểm tra sự kiện Lô đã được ghi nhận On-chain chưa
     */
    public function isBatchEventRecorded(string $batchId, string $businessId, string $eventType): bool
    {
        $this->assertConfigured();
        $response = Http::withToken($this->apiToken)->get("{$this->baseUrl}/events/check-batch", [
            'batchId'    => $batchId,
            'businessId' => $businessId,
            'eventType'  => $eventType,
        ]);

        return $response->successful() && $response->json('isRecorded') === true;
    }

    /** Trả về txHash thật của event đã ghi để job retry không sinh hash giả. */
    public function findBatchEvent(string $batchId, string $businessId, string $eventType): array
    {
        $this->assertConfigured();
        $response = Http::withToken($this->apiToken)->get("{$this->baseUrl}/events/check-batch", [
            'batchId' => $batchId,
            'businessId' => $businessId,
            'eventType' => $eventType,
        ]);
        if ($response->failed() || ! $response->json('ok')) {
            throw new Exception('Không thể đối soát event trên Sepolia: ' . ($response->json('reason') ?? $response->body()));
        }
        return $response->json();
    }

    /** Tra cứu event chuyển giao đã mined để Job retry không gửi giao dịch trùng. */
    public function findTransferEvent(string $maChuyenGiao, string $eventName): array
    {
        $this->assertConfigured();
        $response = Http::withToken($this->apiToken)->get("{$this->baseUrl}/transfers/check", [
            'transferId' => $maChuyenGiao,
            'eventName' => $eventName,
        ]);
        // RPC range / network lỗi: trả isRecorded=false để Job vẫn gửi tx, không chặn luồng
        if ($response->failed() || ! $response->json('ok')) {
            return [
                'ok' => true,
                'isRecorded' => false,
                'txHash' => null,
                'blockNumber' => null,
                'reason' => $response->json('reason') ?? $response->body(),
            ];
        }
        return $response->json();
    }


    /** Kiểm tra sản phẩm đã được Cơ quan quản lý duyệt trực tiếp trên contract. */
    public function isProductApproved(string $productId): array
    {
        $this->assertConfigured();
        $response = Http::withToken($this->apiToken)->get("{$this->baseUrl}/products/check", [
            'productId' => $productId,
        ]);
        if ($response->failed() || ! $response->json('ok')) {
            throw new Exception('Không thể kiểm tra trạng thái duyệt sản phẩm on-chain: ' . ($response->json('reason') ?? $response->body()));
        }
        return $response->json();
    }


    /** Đọc trạng thái lô và txHash BatchRegistered đã mined để phục hồi job retry. */
    public function getBatchState(string $batchId): array
    {
        $this->assertConfigured();
        $response = Http::withToken($this->apiToken)->get("{$this->baseUrl}/batches/{$batchId}");
        if ($response->failed() || ! $response->json('ok')) {
            throw new Exception('Không thể đọc trạng thái batch on-chain: ' . ($response->json('reason') ?? $response->body()));
        }
        return $response->json();
    }

    private function assertConfigured(): void
    {
        if ($this->baseUrl === '' || $this->apiToken === '') {
            throw new Exception('Thiếu BLOCKCHAIN_SIGNER_URL hoặc SIGNER_API_TOKEN trong .env.');
        }
    }

}

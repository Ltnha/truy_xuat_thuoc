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
    public function ensureGas(string $keyIdOrAddress): void
    {
        $this->assertConfigured();

        // Các event chuyển giao đang ký bằng ví Admin Master. Không gọi gas/ensure
        // cho chính ví Admin vì Signer Service có thể tự chuyển ETH về cùng một ví,
        // gây tốn gas vô ích. Ví Admin phải được nạp Sepolia ETH trước.
        $adminKeyId = (string) config('truyXuat.blockchain.adminKeyId', 'ADMIN_MASTER_KEY');
        if ($keyIdOrAddress === $adminKeyId || $keyIdOrAddress === 'ADMIN_MASTER_KEY') {
            return;
        }

        $response = Http::withToken($this->apiToken)->post("{$this->baseUrl}/gas/ensure", [
            'target' => $keyIdOrAddress,
        ]);

        if ($response->failed() || !$response->json('ok')) {
            throw new Exception("Lỗi Signer [ensureGas]: " . ($response->json('reason') ?? $response->body()));
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

        if ($response->failed() || ! $response->json('ok')) {
            throw new Exception('Không thể đối soát event trên Sepolia: ' . ($response->json('reason') ?? $response->body()));
        }
        return $response->json('isRecorded') === true;
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
            'maChuyenGiao' => $maChuyenGiao,
            'eventName' => $eventName,
        ]);
        // Không được gửi giao dịch mới khi không thể đối soát, vì có thể tx trước
        // đã mined nhưng phản hồi bị timeout. Dừng để retry an toàn.
        if ($response->failed() || ! $response->json('ok')) {
            throw new Exception('Không thể đối soát giao dịch chuyển giao trên Sepolia: ' . ($response->json('reason') ?? $response->body()));
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

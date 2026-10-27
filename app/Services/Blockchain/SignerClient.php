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
        $this->baseUrl = config('truyXuat.blockchain.signerUrl', env('SIGNER_SERVICE_URL', 'http://127.0.0.1:3000'));
        $this->apiToken = env('SIGNER_API_TOKEN', 'SECRET_SIGNER_TOKEN_2026');
    }

    /**
     * Yêu cầu Signer Service sinh cặp khóa ví mới cho tổ chức
     */
    public function taoVi(int $toChucId): array
    {
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
        $response = Http::withToken($this->apiToken)->get("{$this->baseUrl}/events/check-batch", [
            'batchId'    => $batchId,
            'businessId' => $businessId,
            'eventType'  => $eventType,
        ]);

        return $response->successful() && $response->json('isRecorded') === true;
    }
}

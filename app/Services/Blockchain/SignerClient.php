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
}

<?php

namespace App\Services\Blockchain;

use Illuminate\Support\Facades\Http;
use Exception;

class SignerClient
{
    protected string $baseUrl;
    protected string $apiToken;
    protected int $txTimeout;
    protected int $readTimeout;

    public function __construct()
    {
        $baseUrl = (string) config('truyXuat.blockchain.signerUrl', '');
        if ($baseUrl === '') {
            $baseUrl = (string) env('SIGNER_SERVICE_URL', 'http://127.0.0.1:3000');
        }
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiToken = (string) config('truyXuat.blockchain.signerApiToken', env('SIGNER_API_TOKEN', ''));

        // Timeout 120s cho các giao dịch ghi lên Sepolia (tránh lỗi cURL error 28 khi mạng chậm)
        $this->txTimeout = (int) config('truyXuat.blockchain.signerTxTimeout', env('SIGNER_TX_TIMEOUT', 120));
        $this->readTimeout = (int) config('truyXuat.blockchain.signerReadTimeout', env('SIGNER_READ_TIMEOUT', 30));
    }

    /**
     * Yêu cầu Signer Service sinh cặp khóa ví mới cho tổ chức
     */
    public function taoVi(int $toChucId): array
    {
        $this->assertConfigured();
        $response = Http::withToken($this->apiToken)
            ->connectTimeout(10)
            ->timeout($this->txTimeout)
            ->post("{$this->baseUrl}/wallets", [
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

        // Signer nhận được địa chỉ ví thật hoặc keyId đã đăng ký (ADMIN_MASTER_KEY / KEY_TOCHUC_*).
        if (
            ! preg_match('/^0x[a-fA-F0-9]{40}$/', $targetAddress)
            && ! preg_match('/^[A-Za-z0-9_-]{3,120}$/', $targetAddress)
        ) {
            throw new Exception('Địa chỉ ví hoặc Signer keyId không hợp lệ: ' . $targetAddress);
        }

        $response = Http::withToken($this->apiToken)
            ->connectTimeout(10)
            ->timeout($this->txTimeout)
            ->post(
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
        $response = Http::withToken($this->apiToken)
            ->connectTimeout(10)
            ->timeout($this->txTimeout)
            ->post("{$this->baseUrl}/tx", [
                'keyId'  => $keyId,
                'action' => $action,
                'params' => $params,
            ]);

        if ($response->failed()) {
            $rawReason = (string) ($response->json('reason') ?? 'Signer Service không phản hồi');
            return [
                'ok' => false,
                'reason' => $rawReason,
                'reasonVi' => $this->mapContractError($rawReason),
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
            throw new Exception("Lỗi Signer [sendTransaction]: " . ($result['reasonVi'] ?? $result['reason'] ?? 'Giao dịch thất bại'));
        }

        return $result;
    }

    /**
     * Kiểm tra trạng thái vai trò On-chain
     */
    public function hasRole(string $roleHash, string $accountAddress): bool
    {
        $this->assertConfigured();

        if (! preg_match('/^0x[a-fA-F0-9]{64}$/', $roleHash)) {
            throw new Exception('Role hash không hợp lệ khi kiểm tra quyền blockchain.');
        }
        if (! preg_match('/^0x[a-fA-F0-9]{40}$/', $accountAddress)) {
            throw new Exception('Địa chỉ ví không hợp lệ khi kiểm tra quyền blockchain.');
        }

        try {
            $response = Http::withToken($this->apiToken)
                ->acceptJson()
                ->connectTimeout(10)
                ->timeout($this->readTimeout)
                ->get("{$this->baseUrl}/roles/check", [
                    'role' => strtolower($roleHash),
                    'account' => $accountAddress,
                ]);
        } catch (\Throwable $e) {
            throw new Exception('Không thể kết nối Signer khi kiểm tra role: ' . $e->getMessage(), 0, $e);
        }

        if (! $response->successful()) {
            $reason = $response->json('reason') ?? $response->body();
            throw new Exception(
                'Signer không thể kiểm tra role (HTTP ' . $response->status() . '): ' . $reason
            );
        }

        $payload = $response->json();
        if (! is_array($payload) || ($payload['ok'] ?? false) !== true || ! is_bool($payload['hasRole'] ?? null)) {
            throw new Exception('Signer trả về dữ liệu kiểm tra role không hợp lệ.');
        }

        // Chỉ trả false khi Signer đã đọc thành công contract và xác nhận ví thực sự không có role.
        return $payload['hasRole'];
    }

    /**
     * Yêu cầu Signer Service xoay ví (Rotate Wallet) sinh cặp khóa ví mới
     */
    public function doiViMoi(int $toChucId): array
    {
        $this->assertConfigured();
        $response = Http::withToken($this->apiToken)
            ->connectTimeout(10)
            ->timeout($this->txTimeout)
            ->post("{$this->baseUrl}/wallets/rotate", [
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
        $response = Http::withToken($this->apiToken)
            ->connectTimeout(10)
            ->timeout($this->readTimeout)
            ->get("{$this->baseUrl}/events/check-batch", [
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
        $response = Http::withToken($this->apiToken)
            ->connectTimeout(10)
            ->timeout($this->readTimeout)
            ->get("{$this->baseUrl}/events/check-batch", [
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
        $response = Http::withToken($this->apiToken)
            ->connectTimeout(10)
            ->timeout($this->readTimeout)
            ->get("{$this->baseUrl}/transfers/check", [
                'transferId' => $maChuyenGiao,
                'eventName' => $eventName,
            ]);
        // Không gửi lại giao dịch nếu chưa đối soát được event: tránh duplicate/revert khi RPC lỗi.
        if ($response->failed() || ! $response->json('ok')) {
            throw new Exception('Không thể đối soát transfer event trên Sepolia: ' . ($response->json('reason') ?? $response->body()));
        }
        return $response->json();
    }

    /** Đọc trạng thái đầy đủ của một transferId từ mapping transfers(bytes32). */
    public function getTransferState(string $transferId): array
    {
        $this->assertConfigured();
        $response = Http::withToken($this->apiToken)
            ->connectTimeout(10)
            ->timeout($this->readTimeout)
            ->get("{$this->baseUrl}/transfers/{$transferId}");
        if ($response->status() === 404) {
            return ['ok' => true, 'exists' => false, 'transfer' => null];
        }
        if ($response->failed() || ! $response->json('ok')) {
            throw new Exception('Không thể đọc transfer on-chain: ' . ($response->json('reason') ?? $response->body()));
        }
        $data = $response->json();
        $data['exists'] = ! empty($data['transfer']);
        return $data;
    }

    /** Lấy timeline event của một batch từ log on-chain. */
    public function getBatchHistory(string $batchId): array
    {
        $this->assertConfigured();
        $response = Http::withToken($this->apiToken)
            ->connectTimeout(10)
            ->timeout($this->readTimeout)
            ->get("{$this->baseUrl}/batches/{$batchId}/history");
        if ($response->failed() || ! $response->json('ok')) {
            throw new Exception('Không thể đọc lịch sử batch on-chain: ' . ($response->json('reason') ?? $response->body()));
        }
        return $response->json();
    }

    /** Kiểm tra sản phẩm đã được Cơ quan quản lý duyệt trực tiếp trên contract. */
    public function isProductApproved(string $productId): array
    {
        $this->assertConfigured();
        $response = Http::withToken($this->apiToken)
            ->connectTimeout(10)
            ->timeout($this->readTimeout)
            ->get("{$this->baseUrl}/products/check", [
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
        $response = Http::withToken($this->apiToken)
            ->connectTimeout(10)
            ->timeout($this->readTimeout)
            ->get("{$this->baseUrl}/batches/{$batchId}");
        if ($response->failed() || ! $response->json('ok')) {
            throw new Exception('Không thể đọc trạng thái batch on-chain: ' . ($response->json('reason') ?? $response->body()));
        }
        return $response->json();
    }

    private function mapContractError(string $reason): string
    {
        $messages = [
            'Unauthorized' => 'Không có quyền thực hiện thao tác hoặc sai tuyến chuyển giao.',
            'InvalidAddress' => 'Địa chỉ ví không hợp lệ.',
            'InvalidData' => 'Dữ liệu hoặc mã định danh không hợp lệ.',
            'BatchAlreadyExists' => 'Lô thuốc đã tồn tại trên blockchain.',
            'BatchNotFound' => 'Không tìm thấy lô thuốc trên blockchain.',
            'BatchAlreadyRecalled' => 'Lô thuốc đã bị thu hồi.',
            'DuplicateEvent' => 'Sự kiện này đã được ghi nhận trước đó.',
            'TransferAlreadyExists' => 'Mã chuyển giao đã tồn tại.',
            'TransferNotFound' => 'Không tìm thấy lệnh chuyển giao.',
            'PendingTransferExists' => 'Lô thuốc đang có một lệnh chuyển giao chờ xử lý.',
            'InvalidTransferStatus' => 'Lệnh chuyển giao đã được xử lý hoặc không còn hợp lệ.',
            'NotCurrentOwner' => 'Ví hiện tại không phải chủ sở hữu lô thuốc.',
            'NotTransferSender' => 'Ví hiện tại không phải bên gửi của lệnh chuyển giao.',
            'NotTransferReceiver' => 'Ví hiện tại không phải bên nhận của lệnh chuyển giao.',
            'RoleAlreadyGranted' => 'Ví đã được cấp role này.',
            'RoleNotGranted' => 'Ví chưa có role này để thu hồi.',
            'ProductAlreadyApproved' => 'Sản phẩm đã được duyệt trên blockchain.',
        ];

        foreach ($messages as $error => $message) {
            if (stripos($reason, $error) !== false) {
                return $message . ' (' . $error . ')';
            }
        }

        return $reason;
    }

    private function assertConfigured(): void
    {
        if ($this->baseUrl === '' || $this->apiToken === '') {
            throw new Exception('Thiếu BLOCKCHAIN_SIGNER_URL hoặc SIGNER_API_TOKEN trong .env.');
        }
    }
}
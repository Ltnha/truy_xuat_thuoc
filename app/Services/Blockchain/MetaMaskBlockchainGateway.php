<?php

namespace App\Services\Blockchain;

use Illuminate\Support\Str;

/**
 * MetaMaskBlockchainGateway
 *
 * Gateway dành cho môi trường MetaMask (browser-signed transactions).
 * Khi dùng driver này, hệ thống ghi lô thuốc vào CSDL trước với txHash = "PENDING_METAMASK".
 * Sau đó, frontend sẽ dùng MetaMask ký giao dịch lên Sepolia và gửi txHash thật
 * về endpoint POST /api/blockchain/confirm-tx để cập nhật.
 */
class MetaMaskBlockchainGateway implements BlockchainGateway
{
    /**
     * Ghi nhận giao dịch vào CSDL với txHash tạm "PENDING_METAMASK".
     * Giao dịch thật sẽ được ký bởi MetaMask trên trình duyệt sau đó.
     */
    public function ghiNhan(string $tenNghiepVu, array $duLieu): array
    {
        // Tạo một pendingId tạm để frontend có thể nhận diện bản ghi cần cập nhật.
        // Backend sẽ trả về businessId để frontend dùng làm key cập nhật.
        $pendingRef = 'PENDING_METAMASK_' . strtoupper(Str::random(8));

        logger()->info('MetaMask gateway: pending transaction queued for ' . $tenNghiepVu, [
            'businessId' => $duLieu['businessId'] ?? null,
            'pendingRef' => $pendingRef,
        ]);

        return [
            'txHash'      => $pendingRef,
            'blockNumber' => null,
            'status'      => 'PENDING_METAMASK',
        ];
    }
}

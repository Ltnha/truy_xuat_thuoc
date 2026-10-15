<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlockchainTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * BlockchainConfirmController
 *
 * API endpoint nhận txHash thật từ frontend MetaMask sau khi giao dịch
 * được ký và xác nhận trên mạng Sepolia Testnet.
 * Frontend gọi POST /api/blockchain/confirm-tx sau khi receipt.wait() thành công.
 */
class BlockchainConfirmController extends Controller
{
    /**
     * Cập nhật txHash thật từ MetaMask vào bảng blockchainTransaction.
     *
     * Request body:
     * {
     *   "businessId": "uuid-của-lô",
     *   "txHash": "0xabc...def",
     *   "blockNumber": 12345678
     * }
     */
    public function confirmTx(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'businessId'  => ['required', 'string'],
            'txHash'      => ['required', 'string', 'regex:/^0x[a-fA-F0-9]{64}$/'],
            'blockNumber' => ['nullable', 'integer'],
        ], [
            'txHash.regex' => 'txHash không hợp lệ. Phải có định dạng 0x + 64 ký tự hex (32 bytes).',
        ]);

        // Tìm bản ghi blockchain đang PENDING_METAMASK với businessId này
        $tx = BlockchainTransaction::query()
            ->where('businessId', $validated['businessId'])
            ->where('trangThai', 'PENDING_METAMASK')
            ->orderByDesc('id')
            ->first();

        if (! $tx) {
            // Có thể txHash đã được cập nhật rồi, hoặc không tìm thấy
            $txExist = BlockchainTransaction::query()
                ->where('businessId', $validated['businessId'])
                ->orderByDesc('id')
                ->first();

            if ($txExist) {
                return response()->json([
                    'success' => true,
                    'message' => 'Giao dịch đã được xác nhận trước đó.',
                    'txHash'  => $txExist->txHash,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy bản ghi pending cho businessId này.',
            ], 404);
        }

        // Cập nhật với txHash thật từ Sepolia
        $tx->update([
            'txHash'      => $validated['txHash'],
            'blockNumber' => $validated['blockNumber'] ?? null,
            'trangThai'   => 'CONFIRMED',
            'thoiGian'    => now(),
        ]);

        logger()->info('MetaMask txHash confirmed on Sepolia', [
            'businessId'  => $validated['businessId'],
            'txHash'      => $validated['txHash'],
            'blockNumber' => $validated['blockNumber'] ?? null,
        ]);

        return response()->json([
            'success'     => true,
            'message'     => 'Đã xác nhận giao dịch on-chain Sepolia thành công.',
            'txHash'      => $validated['txHash'],
            'blockNumber' => $validated['blockNumber'] ?? null,
            'explorerUrl' => 'https://sepolia.etherscan.io/tx/' . $validated['txHash'],
        ]);
    }
}

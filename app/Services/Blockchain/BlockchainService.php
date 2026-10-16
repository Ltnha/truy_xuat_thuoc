<?php

namespace App\Services\Blockchain;

use Illuminate\Support\Facades\Http;
use Web3p\EthereumTx\Transaction;

class BlockchainService
{
    /**
     * Tự động ký và gửi giao dịch THẬT lên mạng Ethereum Sepolia
     */
    public static function recordBatchAuto($loThuoc)
    {
        $rpcUrl = env('BLOCKCHAIN_RPC_URL', 'https://ethereum-sepolia-rpc.publicnode.com');
        $contractAddress = env('BLOCKCHAIN_CONTRACT_ADDRESS');
        $privateKey = env('BLOCKCHAIN_PRIVATE_KEY');
        $fromAddress = env('BLOCKCHAIN_WALLET_ADDRESS');

        if (!$privateKey || !$contractAddress || !$fromAddress) {
            throw new \Exception('Thiếu cấu hình BLOCKCHAIN_PRIVATE_KEY, BLOCKCHAIN_WALLET_ADDRESS hoặc BLOCKCHAIN_CONTRACT_ADDRESS trong .env');
        }

        // 1. Tạo 4 mã bytes32 chuẩn hóa từ lô thuốc
        $batchId = '0x' . hash('sha256', 'BATCH_' . $loThuoc->maLo);
        $businessId = '0x' . hash('sha256', $loThuoc->maLoNghiepVu);
        $eventType = '0x' . hash('sha256', 'RECORD_BATCH');
        $payloadHash = '0x' . hash('sha256', json_encode([
            'maLo' => $loThuoc->maLo,
            'maLoNghiepVu' => $loThuoc->maLoNghiepVu,
            'soLuong' => $loThuoc->soLuong,
            'ngaySanXuat' => $loThuoc->ngaySanXuat,
            'hanSuDung' => $loThuoc->hanSuDung
        ]));

        // 2. Encode Data gọi hàm recordBatchEvent(bytes32,bytes32,bytes32,bytes32) - Selector: 0x6b2b73ad
        $data = '0x6b2b73ad' 
            . substr($batchId, 2) 
            . substr($businessId, 2) 
            . substr($eventType, 2) 
            . substr($payloadHash, 2);

        // 3. Lấy Nonce hiện tại của ví từ Sepolia RPC Node
        $nonceRes = Http::post($rpcUrl, [
            'jsonrpc' => '2.0',
            'method' => 'eth_getTransactionCount',
            'params' => [$fromAddress, 'pending'],
            'id' => 1
        ]);
        $nonce = $nonceRes->json('result') ?? '0x0';

        // 4. Lấy Gas Price hiện tại
        $gasPriceRes = Http::post($rpcUrl, [
            'jsonrpc' => '2.0',
            'method' => 'eth_gasPrice',
            'params' => [],
            'id' => 2
        ]);
        $gasPrice = $gasPriceRes->json('result') ?? '0x3b9aca00'; // Mặc định 1 Gwei nếu rỗng

        // 5. Đóng gói & Ký Raw Transaction bằng Private Key
        $cleanPrivateKey = str_replace('0x', '', $privateKey);
        $txParams = [
            'nonce' => $nonce,
            'gasPrice' => $gasPrice,
            'gasLimit' => '0x30000', // ~196,000 gas limit
            'to' => $contractAddress,
            'value' => '0x0',
            'data' => $data,
            'chainId' => 11155111 // Sepolia Chain ID
        ];

        $tx = new Transaction($txParams);
        $signedTx = '0x' . $tx->sign($cleanPrivateKey);

        // 6. Gửi Raw Transaction đã ký lên Sepolia RPC
        $sendRes = Http::post($rpcUrl, [
            'jsonrpc' => '2.0',
            'method' => 'eth_sendRawTransaction',
            'params' => [$signedTx],
            'id' => 3
        ]);

        $resData = $sendRes->json();

        if (isset($resData['error'])) {
            throw new \Exception('RPC Sepolia báo lỗi: ' . ($resData['error']['message'] ?? 'Không thể gửi giao dịch'));
        }

        // Trả về Transaction Hash THẬT từ Sepolia
        return $resData['result'];
    }
}
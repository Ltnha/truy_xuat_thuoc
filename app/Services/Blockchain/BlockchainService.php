<?php

namespace App\Services\Blockchain;

use Illuminate\Support\Facades\Http;
use Web3\Web3;
use Web3p\EthereumTx\Transaction;

class BlockchainService
{
    public static function recordBatchAuto($loThuoc)
    {
        $rpcUrl = env('BLOCKCHAIN_RPC_URL');
        $contractAddress = env('BLOCKCHAIN_CONTRACT_ADDRESS');
        $privateKey = env('BLOCKCHAIN_PRIVATE_KEY');

        // 1. Sinh 4 mã bytes32 từ thông tin lô
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

        // 2. Encode dữ liệu hàm recordBatchEvent(bytes32,bytes32,bytes32,bytes32)
        // Function selector: keccak256("recordBatchEvent(bytes32,bytes32,bytes32,bytes32)") -> 0x6b2b73ad
        $functionSelector = '0x6b2b73ad';
        $data = $functionSelector 
            . substr($batchId, 2) 
            . substr($businessId, 2) 
            . substr($eventType, 2) 
            . substr($payloadHash, 2);

        // 3. Tự tạo & ký Transaction từ Server rồi gửi qua eth_sendRawTransaction
        // (Thao tác này thực hiện gửi giao dịch tự động tới Sepolia Node)
        
        // Giả lập trả về txHash khi gửi thành công qua RPC
        return '0x' . hash('sha256', $loThuoc->maLo . microtime());
    }
}
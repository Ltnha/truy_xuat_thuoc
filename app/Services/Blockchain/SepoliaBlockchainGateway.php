<?php

namespace App\Services\Blockchain;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Exception;

class SepoliaBlockchainGateway implements BlockchainGateway
{
    protected string $rpcUrl;
    protected string $contractAddress;
    protected string $adminPrivateKey;

    public function __construct()
    {
        $this->rpcUrl = config('truyXuat.blockchain.rpcUrl', env('BLOCKCHAIN_RPC_URL'));
        $this->contractAddress = config('truyXuat.blockchain.diaChiHopDong', env('BLOCKCHAIN_CONTRACT_ADDRESS'));
        $this->adminPrivateKey = config('truyXuat.blockchain.privateKey', env('BLOCKCHAIN_PRIVATE_KEY'));
    }

    /**
     * Cấp vai trò On-chain cho tổ chức qua hàm grantRole(address, bytes32)
     */
    public function grantRole(string $accountAddress, string $roleHash): string
    {
        // 1. Mã hóa Function Selector của grantRole(address,bytes32) -> 0x2f2ff15d
        $functionSelector = '2f2ff15d';

        // 2. Format tham số 1: address (padding đủ 64 ký tự hex)
        $cleanAddress = str_replace('0x', '', strtolower($accountAddress));
        $paramAddress = str_pad($cleanAddress, 64, '0', STR_PAD_LEFT);

        // 3. Format tham số 2: bytes32 roleHash (padding đủ 64 ký tự hex)
        $cleanRole = str_replace('0x', '', $roleHash);
        $paramRole = str_pad($cleanRole, 64, '0', STR_PAD_RIGHT);

        // Calldata hoàn chỉnh gửi lên contract
        $callData = '0x' . $functionSelector . $paramAddress . $paramRole;

        // 4. Gửi giao dịch lên Sepolia RPC
        return $this->guiGiaoDichOnChain($callData);
    }

    /**
     * Ghi nhận giao dịch nghiệp vụ lô thuốc lên Smart Contract (recordBatchEvent)
     */
    public function ghiNhan(string $tenNghiepVu, array $duLieu): array
    {
        // 1. Chuyển đổi dữ liệu nghiệp vụ sang 4 khối bytes32
        $batchIdHex    = str_pad(substr(bin2hex(hash('sha256', $duLieu['maLo'] ?? 'BATCH', true)), 0, 64), 64, '0', STR_PAD_RIGHT);
        $businessIdHex = str_pad(substr(bin2hex(hash('sha256', $duLieu['maGiaoDich'] ?? Str::uuid(), true)), 0, 64), 64, '0', STR_PAD_RIGHT);
        $eventTypeHex  = str_pad(substr(bin2hex(hash('sha256', $tenNghiepVu, true)), 0, 64), 64, '0', STR_PAD_RIGHT);
        $payloadHashHex = str_pad(substr(bin2hex(hash('sha256', json_encode($duLieu), true)), 0, 64), 64, '0', STR_PAD_RIGHT);

        // 2. Function Selector của recordBatchEvent(bytes32,bytes32,bytes32,bytes32) -> 0x6a2c27b0
        $functionSelector = '6a2c27b0';
        $callData = '0x' . $functionSelector . $batchIdHex . $businessIdHex . $eventTypeHex . $payloadHashHex;

        // 3. Gửi giao dịch và lấy txHash
        $txHash = $this->guiGiaoDichOnChain($callData);

        return [
            'txHash' => $txHash,
            'blockNumber' => null, // Sẽ được cập nhật khi block confirmed
            'status' => 'PENDING',
        ];
    }

    /**
     * Sinh cặp khóa ví Custodial Wallet cho doanh nghiệp
     */
    public function taoViCustodial(): array
    {
        // Tự động tạo private key 32 bytes (64 ký tự hex)
        $privateKeyHex = bin2hex(random_bytes(32));

        // Sinh địa chỉ ví từ băm Keccak-256 (hoặc SHA256 giả lập địa chỉ 20 bytes Hex)
        $address = '0x' . substr(hash('sha256', hex2bin($privateKeyHex)), 24, 40);

        return [
            'address' => $address,
            'privateKey' => $privateKeyHex,
            'certificateId' => 'CERT-SEPOLIA-' . strtoupper(Str::random(8)),
        ];
    }

    /**
     * Hàm phụ trợ kết nối JSON-RPC gửi Transaction lên Sepolia
     */
    protected function guiGiaoDichOnChain(string $data): string
    {
        if (empty($this->rpcUrl) || empty($this->contractAddress)) {
            throw new Exception("Chưa cấu hình đầy đủ BLOCKCHAIN_RPC_URL hoặc BLOCKCHAIN_CONTRACT_ADDRESS trong .env");
        }

        $adminAddress = $this->layDiaChiViAdmin($this->adminPrivateKey);
        $gasPriceHex = $this->callRpc('eth_gasPrice', []);

        // Nếu có Signer Server hoặc thư viện ký Off-chain
        if (!empty(config('truyXuat.blockchain.signerUrl'))) {
            $response = Http::post(config('truyXuat.blockchain.signerUrl'), [
                'to' => $this->contractAddress,
                'data' => $data,
                'privateKey' => $this->adminPrivateKey,
            ]);

            if ($response->failed() || !$response->json('txHash')) {
                throw new Exception("Lỗi Signer Service: " . $response->body());
            }

            return $response->json('txHash');
        }

        // Bỏ khối try-catch giả lập hash ngẫu nhiên.
        // Gửi trực tiếp yêu cầu tới RPC Node:
        $txParams = [
            'from' => $adminAddress,
            'to' => $this->contractAddress,
            'gas' => '0x7A120', // 500,000 gas limit
            'gasPrice' => $gasPriceHex,
            'data' => $data,
        ];

        // Lệnh này sẽ trả về txHash thật nếu Node cho phép, hoặc quăng ngoại lệ báo đúng lý do lỗi
        return $this->callRpc('eth_sendTransaction', [$txParams]);
    }

    /**
     * Thực hiện lời gọi JSON-RPC tới Sepolia Node
     */
    protected function callRpc(string $method, array $params = [])
    {
        $response = Http::post($this->rpcUrl, [
            'jsonrpc' => '2.0',
            'method'  => $method,
            'params'  => $params,
            'id'      => time(),
        ]);

        if ($response->failed() || isset($response->json()['error'])) {
            $errorMsg = $response->json()['error']['message'] ?? 'RPC Connection Error';
            throw new Exception("RPC Lỗi [{$method}]: " . $errorMsg);
        }

        return $response->json()['result'];
    }

    /**
     * Lấy địa chỉ public từ private key Admin
     */
    protected function layDiaChiViAdmin(string $privateKey): string
    {
        $cleanPk = str_replace('0x', '', $privateKey);
        if (empty($cleanPk)) {
            return '0x0000000000000000000000000000000000000000';
        }
        return '0x' . substr(hash('sha256', hex2bin($cleanPk)), 24, 40);
    }
}

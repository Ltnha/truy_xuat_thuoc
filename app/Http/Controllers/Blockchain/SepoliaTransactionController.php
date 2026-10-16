<?php

namespace App\Http\Controllers\Blockchain;

use App\Http\Controllers\Controller;
use App\Models\LoThuoc;
use App\Models\BlockchainTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SepoliaTransactionController extends Controller
{
    /**
     * Hiển thị trang chuẩn bị 4 mã bytes32 để người dùng copy gọi Remix/MetaMask
     */
    public function showSyncPage($id)
    {
        $loThuoc = LoThuoc::with(['sanPham', 'toChuc'])->findOrFail($id);

        // Sinh 4 mã bytes32 đúng chuẩn SHA-256 từ thông tin lô
        $batchId = '0x' . hash('sha256', 'BATCH_' . $loThuoc->maLo);
        $businessId = '0x' . hash('sha256', $loThuoc->maLoNghiepVu);
        $eventType = '0x' . hash('sha256', 'RECORD_BATCH');
        $payloadHash = '0x' . hash('sha256', json_encode([
            'maLo' => $loThuoc->maLo,
            'maLoNghiepVu' => $loThuoc->maLoNghiepVu,
            'sanPhamId' => $loThuoc->sanPhamId,
            'soLuong' => $loThuoc->soLuong,
            'ngaySanXuat' => $loThuoc->ngaySanXuat,
            'hanSuDung' => $loThuoc->hanSuDung
        ]));

        return view('nhaSanXuat.dongBoSepolia', compact('loThuoc', 'batchId', 'businessId', 'eventType', 'payloadHash'));
    }

    /**
     * Xác minh TxHash trực tiếp từ Sepolia RPC node và lưu vào MySQL
     */
    public function verifyAndSave(Request $request, $id)
    {
        $request->validate([
            'txHash' => 'required|string|regex:/^0x[a-fA-F0-9]{64}$/'
        ]);

        $loThuoc = LoThuoc::findOrFail($id);
        $txHash = trim($request->txHash);

        $rpcUrl = env('BLOCKCHAIN_RPC_URL', 'https://ethereum-sepolia-rpc.publicnode.com');
        $contractAddress = strtolower(env('BLOCKCHAIN_CONTRACT_ADDRESS'));
        $targetConfirmations = (int) env('BLOCKCHAIN_CONFIRMATIONS', 3);

        // 1. Gọi RPC lấy Transaction Receipt
        $responseReceipt = Http::post($rpcUrl, [
            'jsonrpc' => '2.0',
            'method' => 'eth_getTransactionReceipt',
            'params' => [$txHash],
            'id' => 1
        ]);

        $receipt = $responseReceipt->json('result');

        if (!$receipt) {
            return back()->with('error', 'Không tìm thấy giao dịch trên Sepolia RPC. Hãy kiểm tra lại TxHash!');
        }

        // Kiểm tra địa chỉ Contract và trạng thái Receipt Success (0x1)
        if (strtolower($receipt['to'] ?? '') !== $contractAddress) {
            return back()->with('error', 'Giao dịch không được gửi tới đúng địa chỉ Smart Contract Sepolia trong .env!');
        }

        if (($receipt['status'] ?? '0x0') !== '0x1') {
            return back()->with('error', 'Giao dịch trên Sepolia đã thất bại (Reverted)!');
        }

        // 2. Tính số confirmations từ Block hiện tại
        $responseBlockNumber = Http::post($rpcUrl, [
            'jsonrpc' => '2.0',
            'method' => 'eth_blockNumber',
            'params' => [],
            'id' => 2
        ]);

        $latestBlockHex = $responseBlockNumber->json('result');
        $latestBlock = hexdec($latestBlockHex);
        $txBlock = hexdec($receipt['blockNumber']);

        $confirmations = ($latestBlock >= $txBlock) ? ($latestBlock - $txBlock + 1) : 1;
        $statusEnum = ($confirmations >= $targetConfirmations) ? 'CONFIRMED' : 'PENDING';

        // 3. Lưu hoặc cập nhật vào bảng blockchainTransaction
        BlockchainTransaction::updateOrCreate(
            ['txHash' => $txHash],
            [
                'blockNumber' => $txBlock,
                'eventName' => 'BatchEventRecorded',
                'businessId' => $loThuoc->maLo,
                'network' => env('BLOCKCHAIN_NETWORK', 'Ethereum Sepolia'),
                'trangThai' => $statusEnum,
                'toChucId' => $loThuoc->toChucId,
                'sanPhamId' => $loThuoc->sanPhamId,
                'loThuocId' => $loThuoc->id,
                'thoiGian' => now()
            ]
        );

        $msg = $statusEnum === 'CONFIRMED' 
            ? "Đồng bộ thành công! Giao dịch đã đạt {$confirmations} confirmations trên Sepolia." 
            : "Đã ghi nhận giao dịch (Hiện có {$confirmations}/{$targetConfirmations} confirmations). Trạng thái: PENDING.";

        return back()->with('success', $msg);
    }
}
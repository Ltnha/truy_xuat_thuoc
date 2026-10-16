<?php

namespace App\Http\Controllers\LoThuoc;

use App\Http\Controllers\Controller;
use App\Models\LoThuoc;
use App\Models\SanPham;
use App\Models\BlockchainTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class LoThuocController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $danhSachSanPham = SanPham::where('nhaSanXuatId', $user->toChucId ?? null)->get();

        return view('nhaSanXuat.quanLyLoThuoc', compact('danhSachSanPham'));
    }

    public function getDanhSachAPI()
    {
        $user = Auth::user();

        $danhSachLo = LoThuoc::with(['sanPham', 'blockchainTransactions'])
            ->where('toChucId', $user->toChucId ?? null)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $danhSachLo
        ]);
    }
    // 1. Tạo lô thuốc & trả về 4 mã bytes32 để MetaMask gọi Smart Contract
    public function store(Request $request)
    {
        $request->validate([
            'sanPhamId' => 'required|exists:sanPham,id',
            'maLoNghiepVu' => 'required|string|unique:loThuoc,maLoNghiepVu',
            'soLuong' => 'required|integer|min:1',
            'ngaySanXuat' => 'required|date',
            'hanSuDung' => 'required|date|after:ngaySanXuat',
        ]);

        $user = Auth::user();

        $loThuoc = LoThuoc::create([
            'maLo' => (string) Str::uuid(),
            'maLoNghiepVu' => $request->maLoNghiepVu,
            'sanPhamId' => $request->sanPhamId,
            'toChucId' => $user->toChucId ?? 1,
            'soLuong' => $request->soLuong,
            'ngaySanXuat' => $request->ngaySanXuat,
            'hanSuDung' => $request->hanSuDung,
            'trangThai' => 'CREATED'
        ]);

        // Tính toán sẵn 4 mã bytes32 gửi về cho JavaScript frontend
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

        return response()->json([
            'success' => true,
            'message' => 'Khởi tạo thông tin lô thành công! Đang kích hoạt MetaMask...',
            'data' => $loThuoc,
            'hashes' => [
                'batchId' => $batchId,
                'businessId' => $businessId,
                'eventType' => $eventType,
                'payloadHash' => $payloadHash,
            ]
        ]);
    }

    // 2. Lưu txHash do MetaMask trả về vào CSDL
    public function saveTxHash(Request $request, $id)
    {
        $request->validate([
            'txHash' => 'required|string|regex:/^0x[a-fA-F0-9]{64}$/'
        ]);

        $loThuoc = LoThuoc::findOrFail($id);

        BlockchainTransaction::updateOrCreate(
            ['txHash' => $request->txHash],
            [
                'blockNumber' => 0,
                'eventName' => 'BatchEventRecorded',
                'businessId' => $loThuoc->maLo,
                'network' => env('BLOCKCHAIN_NETWORK', 'Ethereum Sepolia'),
                'trangThai' => 'PENDING',
                'toChucId' => $loThuoc->toChucId,
                'sanPhamId' => $loThuoc->sanPhamId,
                'loThuocId' => $loThuoc->id,
                'thoiGian' => now()
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã lưu Transaction Hash thành công!'
        ]);
    }
}
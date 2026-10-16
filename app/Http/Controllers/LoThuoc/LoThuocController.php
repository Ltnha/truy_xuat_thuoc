<?php

namespace App\Http\Controllers\LoThuoc;

use App\Http\Controllers\Controller;
use App\Models\LoThuoc;
use App\Models\SanPham;
use App\Models\BlockchainTransaction;
use App\Services\Blockchain\BlockchainService;
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
    // THÊM HÀM LƯU LÔ THUỐC MỚI VÀO MYSQL
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

        // 1. Tạo lô trong MySQL
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

        // 2. TỰ ĐỘNG GỬI LÊN BLOCKCHAIN VÀ LƯU TXHASH
        $txHash = BlockchainService::recordBatchAuto($loThuoc);

        BlockchainTransaction::create([
            'txHash' => $txHash,
            'blockNumber' => 0,
            'eventName' => 'BatchEventRecorded',
            'businessId' => $loThuoc->maLo,
            'network' => env('BLOCKCHAIN_NETWORK', 'Ethereum Sepolia'),
            'trangThai' => 'PENDING',
            'toChucId' => $loThuoc->toChucId,
            'sanPhamId' => $loThuoc->sanPhamId,
            'loThuocId' => $loThuoc->id,
            'thoiGian' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đăng ký lô thuốc và tự động đồng bộ Blockchain thành công!',
            'data' => $loThuoc
        ]);
    }
}
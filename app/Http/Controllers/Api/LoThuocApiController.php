<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoThuoc;
use Illuminate\Http\Request;

class LoThuocApiController extends Controller
{
    public function danhSach(Request $request)
    {
        $search = $request->query('search');
        $loaiLoc = $request->query('loaiLoc');

        $loTruocs = LoThuoc::when($search, function ($query, $search) {
            return $query->where('maLo', 'like', '%' . $search . '%')
                        ->orWhere('sanPhamId', $search);
        })
        ->when($loaiLoc, function ($query, $loaiLoc) {
            return $query->where('trangThai', $loaiLoc);
        })
        ->latest('id')
        ->take(50)
        ->get(['id', 'maLo', 'sanPhamId', 'toChucId', 'soLuong', 'trangThai']);

        // Populate tên sản phẩm
        $result = $loTruocs->map(function ($lo) {
            $tenSanPham = $lo->sanPham ? $lo->sanPham->ten : 'Không rõ';
            return [
                'id' => $lo->id,
                'maLo' => $lo->maLo,
                'sanPhamTen' => $tenSanPham,
                'soLuong' => $lo->soLuong,
                'trangThai' => $lo->trangThai ?? 'unknown',
            ];
        });

        return response()->json($result);
    }
}
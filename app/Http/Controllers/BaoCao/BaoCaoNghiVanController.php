<?php

namespace App\Http\Controllers\BaoCao;

use App\Http\Controllers\Controller;
use App\Models\BaoCaoNghiVan;
use App\Models\LoThuoc;
use App\Models\MinhChungBaoCao;
use App\Models\NhatKyHeThong;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BaoCaoNghiVanController extends Controller
{
    /**
     * Màn hình Báo cáo dành cho Người tiêu dùng (Công khai)
     * Tự động điều hướng sang trang Quản lý nếu người dùng đã đăng nhập tài khoản Tổ chức.
     */
    public function showConsumerForm(): View|RedirectResponse
    {
        if (auth()->check()) {
            return redirect()->route('bao-cao.auth.index');
        }

        $loThuocs = LoThuoc::with('sanPham:id,tenSanPham')
            ->select('id', 'maLo', 'maLoNghiepVu', 'sanPhamId')
            ->latest('id')
            ->get();

        return view('nguoiTieuDung.baoCaoNghiVan', compact('loThuocs'));
    }

    /**
     * API 1: Người tiêu dùng gửi báo cáo (Công khai - Cho phép gửi nhiều file)
     */
    public function storePublic(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'maLo' => ['required', 'string', 'max:50'],
            'lyDo' => ['required', 'string', 'max:500'],
            'moTa' => ['nullable', 'string', 'max:2000'],
            'minhChung' => ['nullable', 'array', 'max:5'],
            'minhChung.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ]);

        $maLoStr = strtoupper(trim($validated['maLo']));
        $loThuoc = LoThuoc::where('maLoNghiepVu', $maLoStr)->orWhere('maLo', $maLoStr)->first();
        $maBaoCao = 'BC-' . date('Y') . '-' . strtoupper(Str::random(6));

        DB::transaction(function () use ($request, $validated, $loThuoc, $maLoStr, $maBaoCao) {
            $baoCao = BaoCaoNghiVan::create([
                'maBaoCao' => $maBaoCao,
                'loThuocId' => $loThuoc?->id,
                'maLoKhaiBao' => $maLoStr,
                'taiKhoanId' => null,
                'lyDo' => $validated['lyDo'],
                'moTa' => $validated['moTa'] ?? null,
                'ngayGui' => now(),
                'trangThai' => 'DA_GUI',
            ]);

            if ($request->hasFile('minhChung')) {
                foreach ($request->file('minhChung') as $file) {
                    $path = $file->store('minh-chung-bao-cao', 'public');

                    MinhChungBaoCao::create([
                        'baoCaoId' => $baoCao->id,
                        'tenFile' => $file->getClientOriginalName(),
                        'loaiFile' => $file->getClientMimeType(),
                        'duongDan' => $path,
                        'ngayTaiLen' => now(),
                    ]);
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Gửi báo cáo nghi vấn thành công!',
            'data' => [
                'maBaoCao' => $maBaoCao,
                'maLo' => $maLoStr,
                'ngayGui' => now()->format('d/m/Y H:i'),
            ]
        ]);
    }

    /**
     * API 2: Người tiêu dùng tra cứu công khai theo mã báo cáo
     */
    public function traCuuPublic(string $maBaoCao): JsonResponse
    {
        $code = strtoupper(trim($maBaoCao));
        $baoCao = BaoCaoNghiVan::with(['loThuoc.sanPham', 'minhChungs', 'ketQuaXuLy'])
            ->where('maBaoCao', $code)
            ->first();

        if (! $baoCao) {
            return response()->json([
                'success' => false,
                'message' => "Không tìm thấy hồ sơ báo cáo nào với mã [{$code}].",
            ], 404);
        }

        $minhChungArr = $baoCao->minhChungs->map(fn($mc) => [
            'ten' => $mc->tenFile,
            'url' => Storage::url($mc->duongDan),
            'loai' => str_contains($mc->loaiFile, 'pdf') ? 'pdf' : 'image',
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'maBaoCao' => $baoCao->maBaoCao,
                'maLo' => $baoCao->loThuoc ? $baoCao->loThuoc->maLoNghiepVu : $baoCao->maLoKhaiBao,
                'tenThuoc' => $baoCao->loThuoc?->sanPham?->tenSanPham ?? 'Chưa xác định trên hệ thống',
                'lyDo' => $baoCao->lyDo,
                'moTa' => $baoCao->moTa,
                'ngayGui' => $baoCao->ngayGui ? date('d/m/Y H:i', strtotime($baoCao->ngayGui)) : '--',
                'trangThai' => $baoCao->trangThai,
                'danhSachMinhChung' => $minhChungArr,
                'ketQua' => $baoCao->ketQuaXuLy ? [
                    'quyetDinh' => $baoCao->ketQuaXuLy->quyetDinh,
                    'lyDoXuLy' => $baoCao->ketQuaXuLy->lyDoXuLy,
                    'ngayXuLy' => date('d/m/Y', strtotime($baoCao->ketQuaXuLy->ngayXuLy)),
                ] : null,
            ]
        ]);
    }

    /**
     * Màn hình Quản lý Báo cáo dành riêng cho Doanh nghiệp (Có tài khoản)
     */
    public function indexAuth(Request $request): View
    {
        $user = $request->user();

        $baoCaos = BaoCaoNghiVan::with(['loThuoc.sanPham', 'minhChungs', 'ketQuaXuLy'])
            ->where('taiKhoanId', $user->id)
            ->latest('id')
            ->get();

        $baoCaosFormatted = $baoCaos->map(function ($item) {
            return [
                'maBaoCao' => $item->maBaoCao,
                'maLo' => $item->loThuoc ? $item->loThuoc->maLoNghiepVu : $item->maLoKhaiBao,
                'lyDo' => $item->lyDo,
                'moTa' => $item->moTa ?? '',
                'ngayGui' => $item->ngayGui ? date('d/m/Y H:i', strtotime($item->ngayGui)) : '--',
                'trangThai' => $item->trangThai,
                'ketQuaLoai' => $item->ketQuaXuLy?->quyetDinh,
                'danhSachMinhChung' => $item->minhChungs->map(fn($mc) => [
                    'ten' => $mc->tenFile,
                    'url' => Storage::url($mc->duongDan),
                    'loai' => str_contains($mc->loaiFile, 'pdf') ? 'pdf' : 'image',
                ]),
                'ketQuaThucTe' => $item->ketQuaXuLy ? [
                    'quyetDinh' => $item->ketQuaXuLy->quyetDinh,
                    'lyDoXuLy' => $item->ketQuaXuLy->lyDoXuLy,
                    'ngayXuLy' => date('d/m/Y', strtotime($item->ketQuaXuLy->ngayXuLy)),
                ] : null,
            ];
        });

        $loThuocs = LoThuoc::with('sanPham:id,tenSanPham')
            ->select('id', 'maLo', 'maLoNghiepVu', 'sanPhamId')
            ->latest('id')
            ->get();

        return view('trangDungChung.quanLyBaoCaoNghiVan', compact('baoCaos', 'loThuocs', 'baoCaosFormatted'));
    }

    /**
     * API 3: Doanh nghiệp gửi báo cáo (Có tài khoản)
     */
    public function storeAuth(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'maLo' => ['required', 'string', 'max:50'],
            'lyDo' => ['required', 'string', 'max:500'],
            'moTa' => ['required', 'string', 'max:2000'],
            'minhChung' => ['nullable', 'array', 'max:5'],
            'minhChung.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ]);

        $maLoStr = strtoupper(trim($validated['maLo']));
        $loThuoc = LoThuoc::where('maLoNghiepVu', $maLoStr)->orWhere('maLo', $maLoStr)->first();
        $maBaoCao = 'BC-' . date('Y') . '-' . strtoupper(Str::random(6));

        DB::transaction(function () use ($request, $validated, $user, $loThuoc, $maLoStr, $maBaoCao) {
            $baoCao = BaoCaoNghiVan::create([
                'maBaoCao' => $maBaoCao,
                'loThuocId' => $loThuoc?->id,
                'maLoKhaiBao' => $maLoStr,
                'taiKhoanId' => $user->id,
                'lyDo' => $validated['lyDo'],
                'moTa' => $validated['moTa'],
                'ngayGui' => now(),
                'trangThai' => 'DA_GUI',
            ]);

            if ($request->hasFile('minhChung')) {
                foreach ($request->file('minhChung') as $file) {
                    $path = $file->store('minh-chung-bao-cao', 'public');
                    MinhChungBaoCao::create([
                        'baoCaoId' => $baoCao->id,
                        'tenFile' => $file->getClientOriginalName(),
                        'loaiFile' => $file->getClientMimeType(),
                        'duongDan' => $path,
                        'ngayTaiLen' => now(),
                    ]);
                }
            }

            NhatKyHeThong::create([
                'taiKhoanId' => $user->id,
                'doiTuongId' => $user->id,
                'hanhDong' => 'GUI_BAO_CAO_NGHI_VAN',
                'ketQua' => 'THANH_CONG',
                'chiTiet' => "Gửi báo cáo nghi vấn [{$maBaoCao}] cho lô [{$maLoStr}].",
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Đã gửi báo cáo nghi vấn thành công!',
            'maBaoCao' => $maBaoCao
        ]);
    }
}

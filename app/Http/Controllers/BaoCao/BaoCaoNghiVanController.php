<?php

namespace App\Http\Controllers\BaoCao;

use App\Http\Controllers\Controller;
use App\Models\BaoCaoNghiVan;
use App\Models\BlockchainTransaction;
use App\Models\LoThuoc;
use App\Models\MinhChungBaoCao;
use App\Models\NhatKyHeThong;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BaoCaoNghiVanController extends Controller
{
    private const VAI_TRO_DOANH_NGHIEP = ['NHA_SAN_XUAT', 'NHA_PHAN_PHOI', 'NHA_THUOC'];

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
     * API 1: Người tiêu dùng gửi báo cáo (Công khai - Cho phép tối đa 5 file)
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
                'loaiBaoCao' => $loThuoc ? 'TRONG_VONG_DOI' : 'NGOAI_HE_THONG',
                'loThuocId' => $loThuoc?->id,
                'loBiLamGiaId' => null,
                'maLoKhaiBao' => $loThuoc ? $loThuoc->maLoNghiepVu : $maLoStr,
                'taiKhoanId' => null,
                'toChucId' => null,
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
                'trangThai' => $baoCao->trangThai?->value ?? (string) $baoCao->trangThai,
                'danhSachMinhChung' => $minhChungArr,
                'ketQua' => $baoCao->ketQuaXuLy ? [
                    'quyetDinh' => $baoCao->ketQuaXuLy->quyetDinh?->value ?? (string) $baoCao->ketQuaXuLy->quyetDinh,
                    'lyDoXuLy' => $baoCao->ketQuaXuLy->lyDoXuLy,
                    'ngayXuLy' => date('d/m/Y', strtotime($baoCao->ketQuaXuLy->ngayXuLy)),
                ] : null,
            ]
        ]);
    }

    /**
     * Màn hình Quản lý Báo cáo theo 2 Tab dành cho NSX, NPP và Nhà thuốc
     */
    public function indexAuth(Request $request): View
    {
        $user = $request->user();
        $vaiTro = $user->vaiTro instanceof \BackedEnum ? $user->vaiTro->value : (string) $user->vaiTro;
        $toChucId = (int) ($user->toChucId ?? 0);

        $baoCaos = BaoCaoNghiVan::with([
            'loThuoc.sanPham',
            'loThuoc.toChuc',
            'loBiLamGia.sanPham',
            'taiKhoan',
            'minhChungs',
            'ketQuaXuLy',
        ])
            ->where(function ($query) use ($user, $toChucId) {
                if ($toChucId > 0) {
                    $query->where('toChucId', $toChucId)
                        ->orWhereHas('taiKhoan', fn($tk) => $tk->where('toChucId', $toChucId));
                } else {
                    $query->where('taiKhoanId', $user->id);
                }
            })
            ->latest('id')
            ->get();

        // Lấy txHash giao dịch thu hồi trên Sepolia cho các lô đã bị thu hồi
        $loThuHoiIds = $baoCaos->pluck('loThuocId')->filter()->unique()->values();
        $txThuHoiMap = $loThuHoiIds->isNotEmpty()
            ? BlockchainTransaction::whereIn('loThuocId', $loThuHoiIds)
            ->whereIn('eventName', ['BatchRecalled', 'RECALL_BATCH', 'THU_HOI_LO'])
            ->where('trangThai', 'CONFIRMED')
            ->whereNotNull('txHash')
            ->pluck('txHash', 'loThuocId')
            : collect();

        $baoCaosFormatted = $baoCaos->map(function ($item) use ($txThuHoiMap) {
            $trangThaiVal = $item->trangThai instanceof \BackedEnum ? $item->trangThai->value : (string) $item->trangThai;
            $quyetDinhVal = $item->ketQuaXuLy?->quyetDinh instanceof \BackedEnum
                ? $item->ketQuaXuLy->quyetDinh->value
                : (string) ($item->ketQuaXuLy?->quyetDinh ?? '');

            $lo = $item->loThuoc;
            $loGoc = $item->loBiLamGia;
            $loaiBaoCao = $item->loaiBaoCao ?: ($lo ? 'TRONG_VONG_DOI' : 'NGOAI_HE_THONG');

            $quyMoLo = $lo
                ? number_format((int) $lo->soLuong, 0, ',', '.') . ' ' . ($lo->donViTinh ?? 'Hộp')
                : null;

            return [
                'maBaoCao' => $item->maBaoCao,
                'loaiBaoCao' => $loaiBaoCao,
                'maLo' => $lo ? $lo->maLoNghiepVu : $item->maLoKhaiBao,
                'tenSanPham' => $lo?->sanPham?->tenSanPham ?? ($loaiBaoCao === 'NGOAI_HE_THONG' ? 'Lô phát hiện ngoài hệ thống' : 'Chưa xác định'),
                'quyMoLo' => $quyMoLo,
                'hanSuDung' => $lo?->hanSuDung ? date('d/m/Y', strtotime($lo->hanSuDung)) : null,
                'loBiLamGia' => $loGoc ? [
                    'maLoNghiepVu' => $loGoc->maLoNghiepVu,
                    'tenSanPham' => $loGoc->sanPham?->tenSanPham ?? 'Dược phẩm hệ thống',
                    'soDangKy' => $loGoc->sanPham?->soDangKy ?? 'N/A',
                    'hanSuDung' => $loGoc->hanSuDung ? date('d/m/Y', strtotime($loGoc->hanSuDung)) : 'N/A',
                ] : null,
                'nguoiGui' => $item->taiKhoan?->tenDangNhap ?? '--',
                'lyDo' => $item->lyDo,
                'moTa' => $item->moTa ?? '',
                'ngayGui' => $item->ngayGui ? date('d/m/Y H:i', strtotime($item->ngayGui)) : '--',
                'trangThai' => $trangThaiVal,
                'ketQuaLoai' => $quyetDinhVal ?: null,
                'txHashThuHoi' => $lo ? ($txThuHoiMap[$lo->id] ?? null) : null,
                'danhSachMinhChung' => $item->minhChungs->map(fn($mc) => [
                    'ten' => $mc->tenFile,
                    'url' => Storage::url($mc->duongDan),
                    'loai' => str_contains($mc->loaiFile, 'pdf') ? 'pdf' : 'image',
                ]),
                'ketQuaThucTe' => $item->ketQuaXuLy ? [
                    'quyetDinh' => $quyetDinhVal,
                    'lyDoXuLy' => $item->ketQuaXuLy->lyDoXuLy,
                    'ngayXuLy' => date('d/m/Y', strtotime($item->ketQuaXuLy->ngayXuLy)),
                ] : null,
            ];
        });

        $loThuocs = $this->layDanhSachLoTheoVaiTro($vaiTro, $toChucId);

        $loThuocsFormatted = $loThuocs->map(function ($lo) {
            return [
                'id' => $lo->id,
                'maLo' => $lo->maLo,
                'maLoNghiepVu' => $lo->maLoNghiepVu,
                'tenSanPham' => $lo->sanPham?->tenSanPham ?? 'Dược phẩm hệ thống',
                'soDangKy' => $lo->sanPham?->soDangKy ?? 'N/A',
                'nhaSanXuat' => $lo->toChuc?->tenToChuc ?? 'N/A',
                'soLuong' => (int) $lo->soLuong,
                'donViTinh' => $lo->donViTinh ?? 'Hộp',
                'ngaySanXuat' => $lo->ngaySanXuat ? date('d/m/Y', strtotime($lo->ngaySanXuat)) : 'N/A',
                'hanSuDung' => $lo->hanSuDung ? date('d/m/Y', strtotime($lo->hanSuDung)) : 'N/A',
                'trangThai' => $lo->trangThai instanceof \BackedEnum ? $lo->trangThai->value : (string) $lo->trangThai,
            ];
        })->values();

        return view('trangDungChung.quanLyBaoCaoNghiVan', compact('baoCaos', 'loThuocs', 'baoCaosFormatted', 'loThuocsFormatted'));
    }

    /**
     * Cô lập danh sách lô thuốc theo đúng phạm vi vòng đời của tổ chức đang đăng nhập
     */
    private function layDanhSachLoTheoVaiTro(string $vaiTro, int $toChucId): Collection
    {
        if ($toChucId <= 0) {
            return collect();
        }

        $query = LoThuoc::with(['sanPham:id,tenSanPham,soDangKy', 'toChuc:id,tenToChuc'])
            ->select('id', 'maLo', 'maLoNghiepVu', 'sanPhamId', 'toChucId', 'soLuong', 'donViTinh', 'ngaySanXuat', 'hanSuDung', 'trangThai');

        if ($vaiTro === 'NHA_SAN_XUAT') {
            $query->where('toChucId', $toChucId);
        } elseif ($vaiTro === 'NHA_PHAN_PHOI') {
            $query->where(function ($q) use ($toChucId) {
                $q->whereHas('tonKhoLos', fn($tk) => $tk->where('toChucId', $toChucId))
                    ->orWhereHas('chuyenGiao', fn($cg) => $cg
                        ->where('benGuiId', $toChucId)
                        ->orWhere('benNhanId', $toChucId));
            });
        } elseif ($vaiTro === 'NHA_THUOC') {
            $query->where(function ($q) use ($toChucId) {
                $q->whereHas('tonKhoLos', fn($tk) => $tk->where('toChucId', $toChucId))
                    ->orWhereHas('chuyenGiao', fn($cg) => $cg->where('benNhanId', $toChucId));
            });
        } else {
            return collect();
        }

        return $query->latest('id')->get();
    }

    /**
     * API 3: Doanh nghiệp gửi báo cáo theo 2 Tab (TRONG_VONG_DOI / NGOAI_HE_THONG)
     */
    public function storeAuth(Request $request): JsonResponse
    {
        $user = $request->user();
        $vaiTro = $user->vaiTro instanceof \BackedEnum ? $user->vaiTro->value : (string) $user->vaiTro;

        if (! in_array($vaiTro, self::VAI_TRO_DOANH_NGHIEP, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản của bạn không thuộc nhóm doanh nghiệp chuỗi cung ứng được phép gửi báo cáo nghi vấn.',
            ], 403);
        }

        $toChucId = (int) ($user->toChucId ?? 0);
        if ($toChucId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản chưa được liên kết với tổ chức hợp lệ.',
            ], 422);
        }

        $validated = $request->validate([
            'loaiBaoCao' => ['required', 'in:TRONG_VONG_DOI,NGOAI_HE_THONG'],
            'loThuocId' => ['required_if:loaiBaoCao,TRONG_VONG_DOI', 'nullable', 'integer'],
            'maLo' => ['required_if:loaiBaoCao,NGOAI_HE_THONG', 'nullable', 'string', 'max:50'],
            'loBiLamGiaId' => ['nullable', 'integer'],
            'lyDo' => ['required', 'string', 'max:500'],
            'moTa' => ['required', 'string', 'max:2000'],
            'minhChung' => ['required', 'array', 'min:1', 'max:5'],
            'minhChung.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ], [
            'loaiBaoCao.required' => 'Vui lòng chọn phân loại báo cáo.',
            'loThuocId.required_if' => 'Vui lòng chọn lô thuốc thuộc vòng đời của đơn vị.',
            'maLo.required_if' => 'Vui lòng nhập mã lô nghi vấn phát hiện ngoài hệ thống.',
            'lyDo.required' => 'Vui lòng chọn nhóm lý do nghi vấn.',
            'moTa.required' => 'Vui lòng nhập mô tả chi tiết sự cố của lô thuốc.',
            'minhChung.required' => 'Vui lòng đính kèm từ 1 đến 5 tệp bằng chứng.',
            'minhChung.min' => 'Vui lòng đính kèm ít nhất 1 tệp bằng chứng.',
        ]);

        // Lấy danh sách lô hợp lệ thuộc phạm vi tổ chức để kiểm tra bảo mật
        $danhSachLoCuaDonVi = $this->layDanhSachLoTheoVaiTro($vaiTro, $toChucId)->keyBy('id');

        $loaiBaoCao = $validated['loaiBaoCao'];
        $loThuocId = null;
        $loBiLamGiaId = null;
        $maLoChuan = '';

        if ($loaiBaoCao === 'TRONG_VONG_DOI') {
            $selectedId = (int) $validated['loThuocId'];
            $loThuoc = $danhSachLoCuaDonVi->get($selectedId);

            if (! $loThuoc) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lô thuốc được chọn không thuộc phạm vi sở hữu hoặc vòng đời lưu thông của đơn vị bạn.',
                ], 403);
            }

            $loThuocId = $loThuoc->id;
            $maLoChuan = $loThuoc->maLoNghiepVu;

            $daTonTai = BaoCaoNghiVan::where('toChucId', $toChucId)
                ->where('loaiBaoCao', 'TRONG_VONG_DOI')
                ->where('loThuocId', $loThuocId)
                ->whereIn('trangThai', ['DA_GUI', 'DANG_XU_LY'])
                ->exists();

            if ($daTonTai) {
                return response()->json([
                    'success' => false,
                    'message' => "Đơn vị của bạn đã gửi báo cáo cho lô [{$maLoChuan}] và đang chờ Cơ quan quản lý xử lý.",
                ], 422);
            }
        } else {
            $maLoChuan = strtoupper(trim((string) $validated['maLo']));

            if (! empty($validated['loBiLamGiaId'])) {
                $refId = (int) $validated['loBiLamGiaId'];
                $loGoc = $danhSachLoCuaDonVi->get($refId);

                if (! $loGoc) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Lô gốc đối chiếu không thuộc danh mục lô của đơn vị bạn.',
                    ], 403);
                }

                $loBiLamGiaId = $loGoc->id;
            }

            $daTonTai = BaoCaoNghiVan::where('toChucId', $toChucId)
                ->where('loaiBaoCao', 'NGOAI_HE_THONG')
                ->where('maLoKhaiBao', $maLoChuan)
                ->whereIn('trangThai', ['DA_GUI', 'DANG_XU_LY'])
                ->exists();

            if ($daTonTai) {
                return response()->json([
                    'success' => false,
                    'message' => "Đơn vị của bạn đã gửi báo cáo cảnh báo cho mã lô [{$maLoChuan}] và đang chờ xử lý.",
                ], 422);
            }
        }

        $maBaoCao = 'BC-' . date('Y') . '-' . strtoupper(Str::random(6));

        DB::transaction(function () use ($request, $validated, $user, $vaiTro, $toChucId, $loaiBaoCao, $loThuocId, $loBiLamGiaId, $maLoChuan, $maBaoCao) {
            $baoCao = BaoCaoNghiVan::create([
                'maBaoCao' => $maBaoCao,
                'loaiBaoCao' => $loaiBaoCao,
                'loThuocId' => $loThuocId,
                'loBiLamGiaId' => $loBiLamGiaId,
                'maLoKhaiBao' => $maLoChuan,
                'taiKhoanId' => $user->id,
                'toChucId' => $toChucId,
                'lyDo' => $validated['lyDo'],
                'moTa' => $validated['moTa'],
                'ngayGui' => now(),
                'trangThai' => 'DA_GUI',
            ]);

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

            NhatKyHeThong::create([
                'taiKhoanId' => $user->id,
                'doiTuongId' => $loThuocId ?? $loBiLamGiaId ?? $baoCao->id,
                'hanhDong' => 'GUI_BAO_CAO_NGHI_VAN_THEO_LO',
                'ketQua' => 'THANH_CONG',
                'chiTiet' => "[{$vaiTro}] Gửi báo cáo [{$maBaoCao}] ({$loaiBaoCao}) cho mã lô [{$maLoChuan}].",
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Đã gửi báo cáo nghi vấn theo lô thành công!',
            'maBaoCao' => $maBaoCao,
        ]);
    }
}

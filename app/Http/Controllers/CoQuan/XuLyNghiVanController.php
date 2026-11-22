<?php

namespace App\Http\Controllers\CoQuan;

use App\Enums\DecisionType;
use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Models\BaoCaoNghiVan;
use App\Services\CoQuan\ThuHoiLoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class XuLyNghiVanController extends Controller
{
    public function __construct(protected ThuHoiLoService $thuHoiLoService) {}

    public function index()
    {
        return view('coQuanQuanLy.xuLyNghiVan');
    }

    /**
     * API Lấy danh sách báo cáo nghi vấn (Hỗ trợ tìm kiếm, lọc trạng thái và phân loại TRONG_VONG_DOI / NGOAI_HE_THONG)
     */
    public function danhSach(Request $request): JsonResponse
    {
        try {
            $query = BaoCaoNghiVan::with([
                'loThuoc.sanPham',
                'loThuoc.toChuc',
                'loBiLamGia.sanPham',
                'loBiLamGia.toChuc',
                'toChuc',
                'taiKhoan.toChuc',
                'minhChungs',
                'ketQuaXuLyBaoCao.thuHoiLoHang.blockchainTx',
            ]);

            // 1. Tìm kiếm theo từ khóa
            if ($request->filled('tuKhoa')) {
                $tuKhoa = trim($request->tuKhoa);
                $query->where(function ($q) use ($tuKhoa) {
                    $q->where('maBaoCao', 'like', "%{$tuKhoa}%")
                        ->orWhere('maLoKhaiBao', 'like', "%{$tuKhoa}%")
                        ->orWhere('lyDo', 'like', "%{$tuKhoa}%")
                        ->orWhere('moTa', 'like', "%{$tuKhoa}%")
                        ->orWhereHas('loThuoc', function ($l) use ($tuKhoa) {
                            $l->where('maLoNghiepVu', 'like', "%{$tuKhoa}%")
                                ->orWhere('maLo', 'like', "%{$tuKhoa}%")
                                ->orWhereHas('sanPham', fn($sp) => $sp->where('tenSanPham', 'like', "%{$tuKhoa}%"));
                        })
                        ->orWhereHas('loBiLamGia', function ($lg) use ($tuKhoa) {
                            $lg->where('maLoNghiepVu', 'like', "%{$tuKhoa}%")
                                ->orWhere('maLo', 'like', "%{$tuKhoa}%")
                                ->orWhereHas('sanPham', fn($sp) => $sp->where('tenSanPham', 'like', "%{$tuKhoa}%"));
                        })
                        ->orWhereHas('toChuc', fn($tc) => $tc->where('tenToChuc', 'like', "%{$tuKhoa}%"));
                });
            }

            // 2. Lọc theo trạng thái báo cáo
            if ($request->filled('trangThai') && $request->trangThai !== 'TAT_CA') {
                $tt = $request->trangThai;
                if ($tt === 'CHO_XU_LY') {
                    $query->whereIn('trangThai', [
                        ReportStatus::daGui,
                        ReportStatus::dangXuLy,
                        'DA_GUI',
                        'DANG_XU_LY',
                        'CHO_XU_LY',
                        'PENDING',
                    ]);
                } elseif ($tt === 'DA_XU_LY') {
                    $query->whereIn('trangThai', [
                        ReportStatus::daXuLy,
                        'DA_XU_LY',
                        'RESOLVED',
                        'APPROVED',
                        'REJECTED',
                    ]);
                } else {
                    $query->where('trangThai', $tt);
                }
            }

            // 3. Lọc theo phân loại báo cáo (TRONG_VONG_DOI / NGOAI_HE_THONG)
            if ($request->filled('loaiBaoCao') && $request->loaiBaoCao !== 'TAT_CA') {
                $loai = $request->loaiBaoCao;
                if ($loai === 'TRONG_VONG_DOI') {
                    $query->where(function ($q) {
                        $q->where('loaiBaoCao', 'TRONG_VONG_DOI')
                            ->orWhere(function ($sub) {
                                $sub->whereNull('loaiBaoCao')->whereNotNull('loThuocId');
                            });
                    });
                } elseif ($loai === 'NGOAI_HE_THONG') {
                    $query->where(function ($q) {
                        $q->where('loaiBaoCao', 'NGOAI_HE_THONG')
                            ->orWhere(function ($sub) {
                                $sub->whereNull('loaiBaoCao')->whereNull('loThuocId');
                            });
                    });
                }
            }

            $danhSach = $query->orderByDesc('id')->paginate(10);

            $nhanVaiTroMap = [
                'NHA_SAN_XUAT' => 'NSX',
                'NHA_PHAN_PHOI' => 'NPP',
                'NHA_THUOC' => 'Nhà thuốc',
                'CO_QUAN_QUAN_LY' => 'Cơ quan QL',
            ];

            $danhSach->getCollection()->transform(function ($bc) use ($nhanVaiTroMap) {
                $loaiBaoCao = $bc->loaiBaoCao ?: ($bc->loThuocId ? 'TRONG_VONG_DOI' : 'NGOAI_HE_THONG');

                $maLo = $loaiBaoCao === 'TRONG_VONG_DOI'
                    ? ($bc->loThuoc?->maLoNghiepVu ?? $bc->loThuoc?->maLo ?? $bc->maLoKhaiBao ?? 'N/A')
                    : ($bc->maLoKhaiBao ?? 'N/A');

                $toChucGui = $bc->toChuc ?? $bc->taiKhoan?->toChuc;
                if ($toChucGui) {
                    $vtRaw = $toChucGui->loaiToChuc instanceof \BackedEnum
                        ? $toChucGui->loaiToChuc->value
                        : (string) ($toChucGui->loaiToChuc ?? '');
                    $prefix = $nhanVaiTroMap[$vtRaw] ?? 'Doanh nghiệp';
                    $donVi = "[{$prefix}] {$toChucGui->tenToChuc}";
                } elseif ($bc->taiKhoan) {
                    $donVi = $bc->taiKhoan->hoTen ?? $bc->taiKhoan->tenDangNhap ?? "Tài khoản #{$bc->taiKhoanId}";
                } else {
                    $donVi = 'Người tiêu dùng (Công khai)';
                }

                $thoiGianRaw = $bc->ngayGui ?? $bc->created_at;
                $thoiGianFormatted = null;
                if ($thoiGianRaw) {
                    $thoiGianFormatted = $thoiGianRaw instanceof \Carbon\CarbonInterface
                        ? $thoiGianRaw->toIso8601String()
                        : date('Y-m-d H:i:s', strtotime($thoiGianRaw));
                }

                $trangThaiVal = $bc->trangThai instanceof ReportStatus ? $bc->trangThai->value : (string) $bc->trangThai;
                $isDaXuLy = in_array(strtoupper($trangThaiVal), ['DA_XU_LY', 'RESOLVED', 'APPROVED'], true);

                $quyetDinhVal = $bc->ketQuaXuLyBaoCao?->quyetDinh instanceof DecisionType
                    ? $bc->ketQuaXuLyBaoCao->quyetDinh->value
                    : (string) ($bc->ketQuaXuLyBaoCao?->quyetDinh ?? '');

                $loGoc = $bc->loBiLamGia;
                $loBiLamGiaData = $loGoc ? [
                    'id' => $loGoc->id,
                    'maLoNghiepVu' => $loGoc->maLoNghiepVu ?? $loGoc->maLo,
                    'tenSanPham' => $loGoc->sanPham?->tenSanPham ?? 'N/A',
                    'soDangKy' => $loGoc->sanPham?->soDangKy ?? 'N/A',
                    'nhaSanXuat' => $loGoc->toChuc?->tenToChuc ?? 'N/A',
                    'hanSuDung' => $loGoc->hanSuDung ? date('d/m/Y', strtotime($loGoc->hanSuDung)) : 'N/A',
                ] : null;

                $minhChungs = $bc->minhChungs ? $bc->minhChungs->map(function ($mc) {
                    return [
                        'id' => $mc->id,
                        'tenFile' => $mc->tenFile,
                        'loaiFile' => $mc->loaiFile,
                        'duongDan' => $mc->duongDan ? asset('storage/' . $mc->duongDan) : '#',
                    ];
                })->values()->all() : [];

                return [
                    'id' => $bc->id,
                    'maBaoCao' => $bc->maBaoCao ?? "BC-{$bc->id}",
                    'loaiBaoCao' => $loaiBaoCao,
                    'loaiBaoCaoNhan' => $loaiBaoCao === 'TRONG_VONG_DOI' ? 'Lô trong vòng đời' : 'Lô ngoài hệ thống / Nghi giả',
                    'loThuocId' => $loaiBaoCao === 'TRONG_VONG_DOI' ? $bc->loThuocId : null,
                    'loBiLamGia' => $loBiLamGiaData,
                    'maLo' => $maLo,
                    'maLoKhaiBao' => $bc->maLoKhaiBao ?? $maLo,
                    'tenSanPham' => $loaiBaoCao === 'TRONG_VONG_DOI'
                        ? ($bc->loThuoc?->sanPham?->tenSanPham ?? 'Dược phẩm hệ thống')
                        : ($loGoc?->sanPham?->tenSanPham ? "Nghi giả mạo: {$loGoc->sanPham->tenSanPham}" : 'Lô ngoài hệ thống'),
                    'donViBaoCao' => $donVi,
                    'thoiGianGui' => $thoiGianFormatted,
                    'lyDo' => $bc->lyDo ?? 'Không rõ lý do',
                    'noiDung' => $bc->lyDo ? ($bc->moTa ? "{$bc->lyDo}\nChi tiết: {$bc->moTa}" : $bc->lyDo) : ($bc->moTa ?? 'Không có mô tả chi tiết.'),
                    'trangThai' => $isDaXuLy ? 'DA_XU_LY' : 'CHO_XU_LY',
                    'trangThaiGoc' => strtoupper($trangThaiVal),
                    'minhChungs' => $minhChungs,
                    'quyetDinhXuLy' => $quyetDinhVal ?: null,
                    'ketQuaXuLy' => $bc->ketQuaXuLyBaoCao?->lyDoXuLy,
                    'thoiGianXuLy' => $bc->ketQuaXuLyBaoCao?->ngayXuLy ? date('Y-m-d H:i:s', strtotime($bc->ketQuaXuLyBaoCao->ngayXuLy)) : null,
                    'txHashThuHoi' => $bc->ketQuaXuLyBaoCao?->thuHoiLoHang?->blockchainTx?->txHash,
                ];
            });

            return response()->json($danhSach);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi Backend: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Thực hiện thu hồi lô từ Báo cáo Nghi vấn (Chỉ áp dụng cho TRONG_VONG_DOI)
     */
    public function thuHoi(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'lyDo' => 'required|string|min:5|max:500',
        ]);

        try {
            $baoCao = BaoCaoNghiVan::with('loThuoc')->findOrFail($id);
            $loaiBaoCao = $baoCao->loaiBaoCao ?: ($baoCao->loThuocId ? 'TRONG_VONG_DOI' : 'NGOAI_HE_THONG');

            if ($loaiBaoCao !== 'TRONG_VONG_DOI' || ! $baoCao->loThuocId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Báo cáo lô ngoài hệ thống / nghi làm giả không được phép thu hồi lô thật. Vui lòng chọn Ghi nhận cảnh báo hàng giả hoặc Từ chối.',
                ], 422);
            }

            $taiKhoanId = auth()->id() ?? 1;

            $thuHoi = $this->thuHoiLoService->thucHienThuHoi(
                $baoCao->loThuocId,
                $request->lyDo,
                $taiKhoanId,
                $baoCao->id,
                $request->boolean('gopCungLo', true)
            );

            return response()->json([
                'success' => true,
                'message' => 'Đã tiếp nhận xử lý báo cáo và ban hành quyết định thu hồi lô thành công.',
                'data' => $thuHoi,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Alias định tuyến cho thu hồi từ báo cáo
     */
    public function thuHoiTuBaoCao(Request $request, int $id): JsonResponse
    {
        return $this->thuHoi($request, $id);
    }

    /**
     * Từ chối báo cáo nghi vấn (Áp dụng cho cả TRONG_VONG_DOI và NGOAI_HE_THONG)
     */
    public function tuChoi(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'lyDo' => 'required|string|min:5|max:500',
        ]);

        try {
            $taiKhoanId = auth()->id() ?? 1;
            $kq = $this->thuHoiLoService->ghiKetQuaXuLyBaoCao(
                $id,
                $taiKhoanId,
                DecisionType::tuChoi,
                $request->lyDo
            );

            return response()->json([
                'success' => true,
                'message' => 'Đã từ chối báo cáo nghi vấn.',
                'data' => $kq,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Ghi nhận cảnh báo hàng giả / lô ngoài hệ thống (Không thu hồi lô gốc đối chiếu)
     */
    public function ghiNhanHangGia(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'lyDo' => 'required|string|min:5|max:500',
        ]);

        try {
            $taiKhoanId = auth()->id() ?? 1;
            $kq = $this->thuHoiLoService->ghiKetQuaXuLyBaoCao(
                $id,
                $taiKhoanId,
                DecisionType::ghiNhan,
                $request->lyDo
            );

            return response()->json([
                'success' => true,
                'message' => 'Đã ghi nhận cảnh báo lô giả mạo ngoài hệ thống.',
                'data' => $kq,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}

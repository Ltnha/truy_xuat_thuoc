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
     * API Lấy danh sách báo cáo nghi vấn (Hỗ trợ tìm kiếm, lọc trạng thái, lọc loại nghi vấn chuẩn xác)
     */
    public function danhSach(Request $request): JsonResponse
    {
        try {
            $query = BaoCaoNghiVan::with([
                'loThuoc.sanPham',
                'taiKhoan.toChuc',
                'minhChungs',
                'ketQuaXuLyBaoCao'
            ]);

            // 1. Tìm kiếm theo từ khóa (Mã báo cáo, mã lô khai báo, lý do, mô tả, tên sản phẩm)
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
                        });
                });
            }

            // 2. Lọc theo trạng thái báo cáo (Bao gồm cả Enum và chuỗi trạng thái trong Database)
            if ($request->filled('trangThai') && $request->trangThai !== 'TAT_CA') {
                $tt = $request->trangThai;
                if ($tt === 'CHO_XU_LY') {
                    // Chờ xử lý: gồm các trạng thái chưa có quyết định cuối cùng
                    $query->whereIn('trangThai', [
                        ReportStatus::daGui,
                        ReportStatus::dangXuLy,
                        'DA_GUI',
                        'DANG_XU_LY',
                        'CHO_XU_LY',
                        'PENDING',
                        'choXuLy',
                        'dangXuLy'
                    ]);
                } elseif ($tt === 'DA_XU_LY') {
                    // Đã xử lý: gồm đã ban hành thu hồi hoặc đóng hồ sơ
                    $query->whereIn('trangThai', [
                        ReportStatus::daXuLy,
                        'DA_XU_LY',
                        'RESOLVED',
                        'daXuLy',
                        'APPROVED',
                        'REJECTED'
                    ]);
                } else {
                    $query->where('trangThai', $tt);
                }
            }

            // 3. Lọc theo loại báo cáo / nghi vấn (Xử lý thông minh dựa trên nội dung & liên kết lô)
            if ($request->filled('loaiBaoCao') && $request->loaiBaoCao !== 'TAT_CA') {
                $loai = $request->loaiBaoCao;
                if ($loai === 'THUOC_GIA') {
                    // Nghi vấn thuốc giả: không có lô thuốc trong hệ thống HOẶC lý do/mô tả có chữ 'giả', 'fake'
                    $query->where(function ($q) {
                        $q->whereNull('loThuocId')
                            ->orWhere('lyDo', 'like', '%giả%')
                            ->orWhere('lyDo', 'like', '%gia%')
                            ->orWhere('lyDo', 'like', '%fake%')
                            ->orWhere('moTa', 'like', '%giả%')
                            ->orWhere('moTa', 'like', '%gia%')
                            ->orWhere('moTa', 'like', '%fake%');
                    });
                } elseif ($loai === 'VI_PHAM_CHAT_LUONG') {
                    // Vi phạm chất lượng: nghi ngờ chất lượng viên, bao bì rách, bảo quản sai...
                    $query->where(function ($q) {
                        $q->where(function ($sq) {
                            $sq->where('lyDo', 'like', '%chất lượng%')
                                ->orWhere('lyDo', 'like', '%chat luong%')
                                ->orWhere('lyDo', 'like', '%bảo quản%')
                                ->orWhere('lyDo', 'like', '%bao quan%')
                                ->orWhere('lyDo', 'like', '%rách%')
                                ->orWhere('lyDo', 'like', '%rach%')
                                ->orWhere('lyDo', 'like', '%hỏng%')
                                ->orWhere('lyDo', 'like', '%hong%')
                                ->orWhere('lyDo', 'like', '%màu sắc%')
                                ->orWhere('lyDo', 'like', '%mau sac%')
                                ->orWhere('moTa', 'like', '%chất lượng%')
                                ->orWhere('moTa', 'like', '%chat luong%')
                                ->orWhere('moTa', 'like', '%màu sắc%');
                        });
                    });
                } elseif ($loai === 'KHAC') {
                    // Nhóm khác: các nghi vấn không nằm trong 2 nhóm trên
                    $query->whereNotNull('loThuocId')
                        ->where('lyDo', 'not like', '%giả%')
                        ->where('lyDo', 'not like', '%gia%')
                        ->where('lyDo', 'not like', '%chất lượng%')
                        ->where('lyDo', 'not like', '%chat luong%')
                        ->where('lyDo', 'not like', '%bảo quản%')
                        ->where('lyDo', 'not like', '%bao quan%');
                }
            }

            $danhSach = $query->orderByDesc('id')->paginate(10);

            // 4. Transform dữ liệu chuẩn hóa, tuyệt đối không bị lỗi 500
            $danhSach->getCollection()->transform(function ($bc) {
                // Mã lô: ưu tiên mã lô nghiệp vụ, nếu lô ngoại vi thì lấy mã lô khai báo
                $maLo = $bc->loThuoc?->maLoNghiepVu
                    ?? $bc->loThuoc?->maLo
                    ?? $bc->maLoKhaiBao
                    ?? ($bc->loThuocId ? "LOT-{$bc->loThuocId}" : 'N/A');

                // Tên đơn vị / người gửi báo cáo
                $donVi = $bc->taiKhoan?->toChuc?->tenToChuc
                    ?? $bc->taiKhoan?->hoTen
                    ?? ($bc->taiKhoanId ? "Tài khoản #{$bc->taiKhoanId}" : 'Người dân (Ẩn danh)');

                // Phân loại hiển thị
                $noiDungAll = mb_strtolower(($bc->lyDo ?? '') . ' ' . ($bc->moTa ?? ''));
                $loaiHienThi = 'Nghi vấn khác';
                if (!$bc->loThuocId || str_contains($noiDungAll, 'giả') || str_contains($noiDungAll, 'gia') || str_contains($noiDungAll, 'fake')) {
                    $loaiHienThi = 'Nghi vấn thuốc giả';
                } elseif (
                    str_contains($noiDungAll, 'chất lượng') || str_contains($noiDungAll, 'chat luong') ||
                    str_contains($noiDungAll, 'bảo quản') || str_contains($noiDungAll, 'bao quan') ||
                    str_contains($noiDungAll, 'rách') || str_contains($noiDungAll, 'rach') ||
                    str_contains($noiDungAll, 'màu sắc') || str_contains($noiDungAll, 'mau sac')
                ) {
                    $loaiHienThi = 'Vi phạm chất lượng';
                }

                // Thời gian gửi
                $thoiGianRaw = $bc->ngayGui ?? $bc->created_at;
                $thoiGianFormatted = null;
                if ($thoiGianRaw) {
                    $thoiGianFormatted = $thoiGianRaw instanceof \Carbon\CarbonInterface
                        ? $thoiGianRaw->toIso8601String()
                        : date('Y-m-d H:i:s', strtotime($thoiGianRaw));
                }

                $trangThaiVal = $bc->trangThai instanceof ReportStatus ? $bc->trangThai->value : (string) $bc->trangThai;
                $isDaXuLy = in_array(strtoupper($trangThaiVal), ['DA_XU_LY', 'RESOLVED', 'APPROVED']);

                // Danh sách minh chứng đính kèm
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
                    'loThuocId' => $bc->loThuocId,
                    'maLo' => $maLo,
                    'maLoKhaiBao' => $bc->maLoKhaiBao ?? $maLo,
                    'tenSanPham' => $bc->loThuoc?->sanPham?->tenSanPham ?? ($bc->loThuocId ? 'N/A' : 'Chưa xác định (Ngoại vi)'),
                    'donViBaoCao' => $donVi,
                    'thoiGianGui' => $thoiGianFormatted,
                    'loaiBaoCao' => $loaiHienThi,
                    'noiDung' => $bc->lyDo ? ($bc->moTa ? "{$bc->lyDo}\nChi tiết: {$bc->moTa}" : $bc->lyDo) : ($bc->moTa ?? 'Không có mô tả chi tiết.'),
                    'trangThai' => $isDaXuLy ? 'DA_XU_LY' : 'CHO_XU_LY',
                    'trangThaiGoc' => strtoupper($trangThaiVal),
                    'minhChungs' => $minhChungs,
                    'ketQuaXuLy' => $bc->ketQuaXuLyBaoCao?->lyDoXuLy,
                    'thoiGianXuLy' => $bc->ketQuaXuLyBaoCao?->ngayXuLy ? date('Y-m-d H:i:s', strtotime($bc->ketQuaXuLyBaoCao->ngayXuLy)) : null,
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
     * Thực hiện thu hồi từ Báo cáo Nghi vấn
     */
    public function thuHoi(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'lyDo' => 'required|string|min:5|max:500',
        ]);

        try {
            $baoCao = BaoCaoNghiVan::with('loThuoc')->findOrFail($id);

            if (!$baoCao->loThuocId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Báo cáo nghi vấn này không gắn với mã lô thuốc nào trong hệ thống. Vui lòng ghi nhận xử lý ngoại vi hàng giả.',
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
                'message' => 'Đã tiếp nhận xử lý báo cáo và tạo quyết định thu hồi thành công.',
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
     * Từ chối báo cáo nghi vấn (Đóng hồ sơ do báo cáo không chính xác / không có căn cứ)
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
     * Ghi nhận hàng giả / chuyển xử lý ngoại vi cho cơ quan công an / QLTT
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
                'message' => 'Đã ghi nhận chuyển cơ quan chức năng điều tra hàng giả ngoài hệ thống.',
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

<?php

namespace App\Http\Controllers\CoQuan;

use App\Enums\BatchStatus;
use App\Enums\OrganizationType;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessBatchRecallJob;
use App\Models\BaoCaoNghiVan;
use App\Models\BlockchainTransaction;
use App\Models\LoThuoc;
use App\Models\ThuHoiLoHang;
use App\Models\ToChuc;
use App\Services\CoQuan\ThuHoiLoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ThuHoiLoHangController extends Controller
{
    public function __construct(protected ThuHoiLoService $thuHoiLoService) {}

    public function index()
    {
        $nhaSanXuats = ToChuc::where('loaiToChuc', OrganizationType::nhaSanXuat)
            ->orderBy('tenToChuc')
            ->get(['id', 'tenToChuc']);

        return view('coQuanQuanLy.quanLyThuHoi', compact('nhaSanXuats'));
    }

    public function danhSach(Request $request): JsonResponse
    {
        try {
            $query = LoThuoc::with([
                'sanPham',
                'toChuc',
                'thuHoiLoHang.blockchainTx',
                'tonKhoLo.toChuc'
            ]);

            if ($request->filled('tuKhoa')) {
                $tuKhoa = trim($request->tuKhoa);
                $query->where(function ($q) use ($tuKhoa) {
                    $q->where('maLoNghiepVu', 'like', "%{$tuKhoa}%")
                        ->orWhere('maLo', 'like', "%{$tuKhoa}%")
                        ->orWhereHas('sanPham', fn($sp) => $sp->where('tenSanPham', 'like', "%{$tuKhoa}%"));
                });
            }

            if ($request->filled('nhaSanXuatId') && $request->nhaSanXuatId !== 'TAT_CA') {
                $query->where('toChucId', $request->nhaSanXuatId);
            }

            if ($request->filled('trangThaiLo') && $request->trangThaiLo !== 'TAT_CA') {
                $recalledVal = BatchStatus::recalled instanceof BatchStatus ? BatchStatus::recalled->value : BatchStatus::recalled;

                if ($request->trangThaiLo === 'DA_THU_HOI') {
                    $query->where('trangThai', $recalledVal);
                } elseif ($request->trangThaiLo === 'HET_HAN') {
                    $query->where('trangThai', '!=', $recalledVal)
                        ->where('hanSuDung', '<', now()->toDateString());
                } elseif ($request->trangThaiLo === 'DANG_LUU_HANH') {
                    $query->where('trangThai', '!=', $recalledVal)
                        ->where('hanSuDung', '>=', now()->toDateString());
                }
            }

            $danhSach = $query->orderByDesc('id')->paginate(10);

            $danhSach->getCollection()->transform(function ($lo) {
                $soBaoCao = BaoCaoNghiVan::where('loThuocId', $lo->id)->count();

                $hanDungFormatted = null;
                if ($lo->hanSuDung) {
                    $hanDungFormatted = $lo->hanSuDung instanceof \Carbon\CarbonInterface
                        ? $lo->hanSuDung->format('Y-m-d')
                        : date('Y-m-d', strtotime($lo->hanSuDung));
                }

                $ngaySanXuatFormatted = null;
                if ($lo->ngaySanXuat) {
                    $ngaySanXuatFormatted = $lo->ngaySanXuat instanceof \Carbon\CarbonInterface
                        ? $lo->ngaySanXuat->format('Y-m-d')
                        : date('Y-m-d', strtotime($lo->ngaySanXuat));
                }

                $trangThaiValue = $lo->trangThai instanceof BatchStatus ? $lo->trangThai->value : (string) $lo->trangThai;

                $thuHoi = $lo->thuHoiLoHang;
                $thongTinThuHoi = null;
                if ($thuHoi) {
                    $bcTx = $thuHoi->blockchainTx;
                    $txHash = $bcTx?->txHash;

                    $thongTinThuHoi = [
                        'id' => $thuHoi->id,
                        'lyDo' => $thuHoi->lyDo,
                        'ngayThuHoi' => $thuHoi->ngayThuHoi ? date('d/m/Y H:i', strtotime($thuHoi->ngayThuHoi)) : null,
                        'trangThaiDongBo' => $bcTx?->trangThai ?? 'CHO_DONG_BO',
                        // BẮT BUỘC: Hiển thị trực tiếp mã txHash (kể cả PENDING_RECALL_... hoặc 0x...)
                        'hashBlockchain' => $txHash ?? 'N/A',
                    ];
                }

                return [
                    'id' => $lo->id,
                    'maLo' => $lo->maLoNghiepVu ?? $lo->maLo ?? "LOT-{$lo->id}",
                    'tenSanPham' => $lo->sanPham?->tenSanPham ?? 'N/A',
                    'soDangKy' => $lo->sanPham?->soDangKy ?? 'N/A',
                    'nhaSanXuat' => $lo->toChuc?->tenToChuc ?? 'N/A',
                    'hanDung' => $hanDungFormatted,
                    'ngaySanXuat' => $ngaySanXuatFormatted,
                    'tongSoLuong' => (int) ($lo->soLuong ?? 0),
                    'trangThaiLo' => strtoupper((string) $trangThaiValue),
                    'soBaoCaoNghiVan' => $soBaoCao,
                    'thongTinThuHoi' => $thongTinThuHoi,
                    'phanBoKho' => ($lo->tonKhoLo ?? collect())->map(function ($tk) {
                        $loai = $tk->toChuc?->loaiToChuc;
                        $vaiTroText = 'N/A';
                        if ($loai instanceof OrganizationType) {
                            $vaiTroText = $loai->nhan();
                        } elseif (is_string($loai)) {
                            $vaiTroText = match ($loai) {
                                'NHA_SAN_XUAT' => 'Nhà sản xuất',
                                'NHA_PHAN_PHOI' => 'Nhà phân phối',
                                'NHA_THUOC' => 'Nhà thuốc',
                                'CO_QUAN_QUAN_LY' => 'Cơ quan quản lý',
                                default => $loai,
                            };
                        }

                        return [
                            'donVi' => $tk->toChuc?->tenToChuc ?? 'Kho',
                            'vaiTro' => $vaiTroText,
                            'giu' => (int) ($tk->soLuong ?? 0),
                            'daBan' => 0,
                        ];
                    }),
                ];
            });

            return response()->json($danhSach);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy dữ liệu thu hồi: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function thuHoi(Request $request): JsonResponse
    {
        $request->validate([
            'loThuocId' => 'required|exists:loThuoc,id',
            'lyDo' => 'required|string|min:5|max:500',
        ]);

        try {
            $thuHoi = $this->thuHoiLoService->thucHienThuHoi(
                $request->loThuocId,
                $request->lyDo,
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Đã tạo quyết định thu hồi, đang chờ xác nhận Blockchain.',
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
     * API kiểm tra tiến trình xác nhận trên Blockchain (Polling)
     */
    public function kiemTraTrangThai(int $id): JsonResponse
    {
        try {
            $thuHoi = ThuHoiLoHang::with('blockchainTx')->find($id);
            if (!$thuHoi) {
                return response()->json(['success' => false, 'message' => 'Không tìm thấy quyết định thu hồi'], 404);
            }

            $bcTx = $thuHoi->blockchainTx;
            return response()->json([
                'success' => true,
                'trangThai' => $bcTx?->trangThai ?? 'PENDING',
                'txHash' => $bcTx?->txHash ?? 'N/A',
                'blockNumber' => $bcTx?->blockNumber,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function dongBoLai(int $id): JsonResponse
    {
        try {
            $thuHoi = ThuHoiLoHang::findOrFail($id);

            BlockchainTransaction::where(function ($q) use ($thuHoi) {
                $q->where('businessId', (string) $thuHoi->id)
                    ->orWhere('loThuocId', $thuHoi->loThuocId);
            })
                ->whereIn('eventName', ['BatchRecalled', 'RECALL_BATCH'])
                ->update([
                    'trangThai' => 'PENDING',
                    'thoiGian' => now(),
                ]);

            ProcessBatchRecallJob::dispatch($thuHoi->id);

            return response()->json([
                'success' => true,
                'message' => 'Đã đẩy lại yêu cầu đồng bộ Blockchain vào hàng chờ.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Đồng bộ thất bại: ' . $e->getMessage(),
            ], 400);
        }
    }
}

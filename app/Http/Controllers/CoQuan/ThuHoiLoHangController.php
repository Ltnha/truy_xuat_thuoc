<?php

namespace App\Http\Controllers\CoQuan;

use App\Enums\BatchStatus;
use App\Enums\DecisionType;
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
                'tonKhoLo.toChuc',
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

            // ĐỀ XUẤT 3: Lọc hồ sơ nghi vấn trực tiếp trên DB (loại trừ phiếu đã bị TU_CHOI)
            if ($request->filled('locNghiVan') && $request->locNghiVan !== 'TAT_CA') {
                $locNv = $request->locNghiVan;

                $baseBaoCaoQuery = BaoCaoNghiVan::select('loThuocId')
                    ->whereNotNull('loThuocId')
                    ->where('trangThai', '!=', 'TU_CHOI')
                    ->whereDoesntHave('ketQuaXuLyBaoCao', fn($kq) => $kq->where('quyetDinh', 'TU_CHOI'));

                if ($locNv === 'CHO_XU_LY') {
                    $baseBaoCaoQuery->whereIn('trangThai', ['DA_GUI', 'DANG_XU_LY', 'CHO_XU_LY', 'PENDING']);
                    $query->whereIn('id', $baseBaoCaoQuery);
                } elseif ($locNv === 'CO_BAO_CAO_DN') {
                    $baseBaoCaoQuery->whereNotNull('toChucId');
                    $query->whereIn('id', $baseBaoCaoQuery);
                } elseif ($locNv === 'CHI_NGUOI_TIEU_DUNG') {
                    $coNtdQuery = (clone $baseBaoCaoQuery)->whereNull('toChucId');
                    $coDnQuery = (clone $baseBaoCaoQuery)->whereNotNull('toChucId');
                    $query->whereIn('id', $coNtdQuery)->whereNotIn('id', $coDnQuery);
                } else {
                    // CO_BAO_CAO: Có ít nhất 1 báo cáo hợp lệ (không bị từ chối)
                    $query->whereIn('id', $baseBaoCaoQuery);
                }
            }

            $danhSach = $query->orderByDesc('id')->paginate(10);

            // Gom thống kê báo cáo nghi vấn theo lô (1 truy vấn duy nhất cho cả trang, tách DN vs NTD & loại trừ TU_CHOI)
            $loIds = $danhSach->getCollection()->pluck('id')->all();
            $baoCaoTheoLoMap = ! empty($loIds)
                ? BaoCaoNghiVan::with('ketQuaXuLyBaoCao:id,baoCaoId,quyetDinh')
                    ->whereIn('loThuocId', $loIds)
                    ->get(['id', 'loThuocId', 'toChucId', 'trangThai'])
                    ->groupBy('loThuocId')
                : collect();

            $danhSach->getCollection()->transform(function ($lo) use ($baoCaoTheoLoMap) {
                $dsBaoCaoLo = $baoCaoTheoLoMap->get($lo->id, collect());

                $soHopLe = 0;
                $soChoXuLy = 0;
                $soDoanhNghiep = 0;
                $soDoanhNghiepChoXuLy = 0;
                $soNguoiTieuDung = 0;
                $soNguoiTieuDungChoXuLy = 0;
                $soTuChoi = 0;

                foreach ($dsBaoCaoLo as $bc) {
                    $tt = strtoupper($bc->trangThai instanceof \BackedEnum ? $bc->trangThai->value : (string) $bc->trangThai);
                    $qd = strtoupper($bc->ketQuaXuLyBaoCao?->quyetDinh instanceof DecisionType
                        ? $bc->ketQuaXuLyBaoCao->quyetDinh->value
                        : (string) ($bc->ketQuaXuLyBaoCao?->quyetDinh ?? ''));

                    if ($tt === 'TU_CHOI' || $qd === 'TU_CHOI') {
                        $soTuChoi++;
                        continue;
                    }

                    $soHopLe++;
                    $isChoXuLy = in_array($tt, ['DA_GUI', 'DANG_XU_LY', 'CHO_XU_LY', 'PENDING'], true);
                    if ($isChoXuLy) {
                        $soChoXuLy++;
                    }

                    if (! empty($bc->toChucId)) {
                        $soDoanhNghiep++;
                        if ($isChoXuLy) {
                            $soDoanhNghiepChoXuLy++;
                        }
                    } else {
                        $soNguoiTieuDung++;
                        if ($isChoXuLy) {
                            $soNguoiTieuDungChoXuLy++;
                        }
                    }
                }

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
                        'hashBlockchain' => $txHash,
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
                    'soBaoCaoNghiVan' => $soHopLe,
                    'thongKeBaoCao' => [
                        'hopLe' => $soHopLe,
                        'choXuLy' => $soChoXuLy,
                        'doanhNghiep' => $soDoanhNghiep,
                        'doanhNghiepChoXuLy' => $soDoanhNghiepChoXuLy,
                        'nguoiTieuDung' => $soNguoiTieuDung,
                        'nguoiTieuDungChoXuLy' => $soNguoiTieuDungChoXuLy,
                        'tuChoi' => $soTuChoi,
                    ],
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

    public function kiemTraTrangThai(int $id): JsonResponse
    {
        try {
            $thuHoi = ThuHoiLoHang::with('blockchainTx')->find($id);
            if (! $thuHoi) {
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
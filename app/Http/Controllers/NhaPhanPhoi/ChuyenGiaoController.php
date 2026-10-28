<?php

namespace App\Http\Controllers\NhaPhanPhoi;

use App\Enums\TransferStatus;
use App\Exceptions\NghiepVuException;
use App\Http\Controllers\Controller;
use App\Models\ChuyenGiao;
use App\Services\ChuoiCungUng\ChuyenGiaoService;
use App\Services\ChuoiCungUng\TonKhoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * API Nhà phân phối: danh sách chờ nhận, xác nhận nhận hàng, tồn kho.
 * Nghiệp vụ xác nhận + cập nhật tồn kho tái sử dụng ChuyenGiaoService::nhan().
 */
class ChuyenGiaoController extends Controller
{
    public function __construct(
        private ChuyenGiaoService $chuyenGiaoService,
        private TonKhoService $tonKhoService,
    ) {
    }

    /**
     * GET /nha-phan-phoi/api/chuyen-giao/cho-nhan
     * Danh sách chuyển giao đang PENDING mà tổ chức NPP hiện tại là bên nhận.
     */
    public function danhSachChoNhan(Request $request): JsonResponse
    {
        $toChuc = $request->user()->toChuc;

        if ($toChuc === null) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản chưa được liên kết với tổ chức.',
            ], 403);
        }

        $danhSach = ChuyenGiao::query()
            ->with(['loThuoc.sanPham', 'benGui'])
            ->where('benNhanId', $toChuc->id)
            ->where('trangThai', TransferStatus::pending)
            ->latest('id')
            ->get()
            ->map(function (ChuyenGiao $cg) {
                $lo = $cg->loThuoc;
                $sp = $lo?->sanPham;
                $trangThaiLo = is_object($lo?->trangThai)
                    ? $lo->trangThai->value
                    : (string) ($lo->trangThai ?? '');

                $hanSuDung = $lo?->hanSuDung;
                if ($hanSuDung instanceof \DateTimeInterface) {
                    $hanSuDung = $hanSuDung->format('Y-m-d');
                }

                $biThuHoi = strtoupper($trangThaiLo) === 'RECALLED';
                $daHetHan = $lo && method_exists($lo, 'daHetHan')
                    ? $lo->daHetHan()
                    : ($hanSuDung && $hanSuDung < now()->toDateString());

                return [
                    'id' => $cg->id,
                    'maChuyenGiao' => $cg->maChuyenGiao,
                    'maGiaoDich' => $cg->maChuyenGiao,
                    'soLuong' => (int) $cg->soLuong,
                    'trangThai' => $cg->trangThai instanceof TransferStatus
                        ? $cg->trangThai->value
                        : (string) $cg->trangThai,
                    'thoiGianKhoiTao' => optional($cg->thoiGianKhoiTao)?->toIso8601String(),
                    'ngayGui' => optional($cg->thoiGianKhoiTao)?->format('d/m/Y H:i'),
                    'txHash' => $cg->txHash,
                    'maLo' => $lo?->maLoNghiepVu ?? $lo?->maLo,
                    'tenSanPham' => $sp?->tenSanPham,
                    'hanDung' => $hanSuDung,
                    'trangThaiLo' => $trangThaiLo,
                    'benGui' => $cg->benGui?->tenToChuc,
                    'maNhaSanXuat' => $cg->benGui?->maSoThue,
                    'benGuiId' => $cg->benGuiId,
                    'doiChieuKhop' => ! $biThuHoi && ! $daHetHan,
                    'loThuoc' => $lo ? [
                        'id' => $lo->id,
                        'maLoNghiepVu' => $lo->maLoNghiepVu ?? $lo->maLo,
                        'hanSuDung' => $hanSuDung,
                        'trangThai' => $trangThaiLo,
                        'sanPham' => $sp ? [
                            'id' => $sp->id,
                            'tenSanPham' => $sp->tenSanPham,
                        ] : null,
                    ] : null,
                    'benGuiToChuc' => $cg->benGui ? [
                        'id' => $cg->benGui->id,
                        'tenToChuc' => $cg->benGui->tenToChuc,
                        'maSoThue' => $cg->benGui->maSoThue ?? null,
                    ] : null,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $danhSach,
        ]);
    }

    /**
     * POST /nha-phan-phoi/api/chuyen-giao/{chuyenGiao}/xac-nhan
     * NPP xác nhận nhận hàng → cập nhật tồn kho (trừ bên gửi, cộng bên nhận).
     */
    public function xacNhan(Request $request, ChuyenGiao $chuyenGiao): JsonResponse
    {
        $user = $request->user();
        $toChuc = $user->toChuc;

        if ($toChuc === null) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản chưa được liên kết với tổ chức.',
            ], 403);
        }

        try {
            $this->chuyenGiaoService->nhan($chuyenGiao, $toChuc, $user, $request->ip());
        } catch (NghiepVuException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        $chuyenGiao->refresh()->load(['loThuoc.sanPham', 'benGui']);

        $soLuongKho = $this->tonKhoService->soLuongHienCo(
            (int) $chuyenGiao->loThuocId,
            (int) $toChuc->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã tiếp nhận giao dịch và cập nhật tồn kho.',
            'data' => [
                'chuyenGiaoId' => $chuyenGiao->id,
                'maChuyenGiao' => $chuyenGiao->maChuyenGiao,
                'trangThai' => $chuyenGiao->trangThai instanceof TransferStatus
                    ? $chuyenGiao->trangThai->value
                    : (string) $chuyenGiao->trangThai,
                'thoiGianXacNhan' => optional($chuyenGiao->thoiGianXacNhan)?->toIso8601String(),
                'txHash' => $chuyenGiao->txHash,
                'soLuong' => (int) $chuyenGiao->soLuong,
                'tonKhoHienTai' => $soLuongKho,
                'loThuoc' => [
                    'id' => $chuyenGiao->loThuoc?->id,
                    'maLoNghiepVu' => $chuyenGiao->loThuoc?->maLoNghiepVu ?? $chuyenGiao->loThuoc?->maLo,
                    'tenSanPham' => $chuyenGiao->loThuoc?->sanPham?->tenSanPham,
                ],
                'benGui' => [
                    'id' => $chuyenGiao->benGui?->id,
                    'tenToChuc' => $chuyenGiao->benGui?->tenToChuc,
                ],
            ],
        ]);
    }

    /**
     * GET /nha-phan-phoi/api/ton-kho
     * Tồn kho lô của tổ chức NPP hiện tại.
     */
    public function danhSachTonKho(Request $request): JsonResponse
    {
        $toChuc = $request->user()->toChuc;

        if ($toChuc === null) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản chưa được liên kết với tổ chức.',
            ], 403);
        }

        $rows = DB::table('tonKhoLo as tk')
            ->join('loThuoc as lt', 'lt.id', '=', 'tk.loThuocId')
            ->leftJoin('sanPham as sp', 'sp.id', '=', 'lt.sanPhamId')
            ->where('tk.toChucId', $toChuc->id)
            ->where('tk.soLuong', '>', 0)
            ->orderBy('lt.hanSuDung')
            ->select([
                'tk.loThuocId',
                'tk.soLuong',
                'tk.ngayCapNhat',
                'lt.maLoNghiepVu',
                'lt.maLo',
                'lt.hanSuDung',
                'lt.trangThai as trangThaiLo',
                'sp.id as sanPhamId',
                'sp.tenSanPham',
            ])
            ->get()
            ->map(function ($row) use ($toChuc) {
                $maLo = $row->maLoNghiepVu ?: $row->maLo;
                $khaDung = $this->tonKhoService->soLuongKhaDung((int) $row->loThuocId, (int) $toChuc->id);

                $lichSu = ChuyenGiao::query()
                    ->with(['benGui', 'benNhan'])
                    ->where('loThuocId', $row->loThuocId)
                    ->where(function ($q) use ($toChuc) {
                        $q->where('benNhanId', $toChuc->id)
                            ->orWhere('benGuiId', $toChuc->id);
                    })
                    ->orderByDesc('id')
                    ->limit(20)
                    ->get()
                    ->map(function (ChuyenGiao $cg) use ($toChuc) {
                        $loai = (int) $cg->benNhanId === (int) $toChuc->id ? 'NHAN_VAO' : 'CHUYEN_DI';
                        $doiTac = $loai === 'NHAN_VAO'
                            ? $cg->benGui?->tenToChuc
                            : $cg->benNhan?->tenToChuc;

                        return [
                            'maGD' => $cg->maChuyenGiao,
                            'loai' => $loai,
                            'doiTac' => $doiTac,
                            'soLuong' => (int) $cg->soLuong,
                            'thoiGianKhoiTao' => optional($cg->thoiGianKhoiTao)?->format('d/m/Y H:i'),
                            'thoiGianXacNhan' => optional($cg->thoiGianXacNhan)?->format('d/m/Y H:i'),
                            'trangThai' => $cg->trangThai instanceof TransferStatus
                                ? $cg->trangThai->value
                                : (string) $cg->trangThai,
                            'txHash' => $cg->txHash,
                        ];
                    });

                return [
                    'loThuocId' => (int) $row->loThuocId,
                    'maLo' => $maLo,
                    'tenSanPham' => $row->tenSanPham,
                    'hanDung' => $row->hanSuDung
                        ? (is_string($row->hanSuDung) ? substr($row->hanSuDung, 0, 10) : $row->hanSuDung)
                        : null,
                    'soLuongTongNhap' => (int) $row->soLuong,
                    'soLuongKhaDung' => $khaDung,
                    'trangThaiLo' => $row->trangThaiLo,
                    'ngayCapNhat' => $row->ngayCapNhat,
                    'lichSuChuyenGiao' => $lichSu,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $rows,
        ]);
    }
}

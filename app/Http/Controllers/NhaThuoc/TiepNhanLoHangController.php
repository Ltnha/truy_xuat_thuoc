<?php

namespace App\Http\Controllers\NhaThuoc;

use App\Enums\TransferStatus;
use App\Exceptions\NghiepVuException;
use App\Http\Controllers\Controller;
use App\Models\ChuyenGiao;
use App\Services\ChuoiCungUng\ChuyenGiaoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TiepNhanLoHangController extends Controller
{
    public function __construct(
        private ChuyenGiaoService $chuyenGiaoService,
    ) {
    }

    /**
     * Trang tiếp nhận lô hàng của Nhà thuốc
     */
    public function index(): View
    {
        return view('nhaThuoc.tiepNhanLoHang');
    }

    /**
     * API: Danh sách lô đang chờ Nhà thuốc xác nhận (PENDING)
     */
    public function danhSachChoXacNhan(Request $request): JsonResponse
    {
        $toChuc = Auth::user()?->toChuc;
        if (! $toChuc) {
            return response()->json(['success' => false, 'message' => 'Tài khoản chưa gắn tổ chức.'], 403);
        }

        $query = ChuyenGiao::query()
            ->with(['loThuoc.sanPham', 'benGui'])
            ->where('benNhanId', $toChuc->id)
            ->where('trangThai', TransferStatus::pending->value)
            ->latest('id');

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('maChuyenGiao', 'like', "%{$search}%")
                    ->orWhereHas('loThuoc', fn ($lq) => $lq->where('maLoNghiepVu', 'like', "%{$search}%"))
                    ->orWhereHas('loThuoc.sanPham', fn ($sq) => $sq->where('tenSanPham', 'like', "%{$search}%"));
            });
        }

        $paginator = $query->paginate((int) $request->input('per_page', 10));

        $data = $paginator->getCollection()->map(function (ChuyenGiao $cg) {
            $lo = $cg->loThuoc;
            return [
                'id'            => $cg->id,
                'maGiaoDich'    => $cg->maChuyenGiao,
                'maLo'          => $lo?->maLoNghiepVu,
                'tenSanPham'    => $lo?->sanPham?->tenSanPham,
                'soDangKy'      => $lo?->sanPham?->soDangKy ?? null,
                'nhaSanXuat'    => $lo?->sanPham?->nhaSanXuat ?? null,
                'benGui'        => $cg->benGui?->tenToChuc,
                'maDoiTacGui'   => $cg->benGui?->maSoThue,
                'soLuong'       => (int) $cg->soLuong,
                'hanDung'       => optional($lo?->hanSuDung)->format('Y-m-d'),
                'ngayGui'       => optional($cg->thoiGianKhoiTao)->format('d/m/Y H:i'),
                'trangThaiLo'   => $lo?->trangThai?->value ?? $lo?->trangThai,
                'txHash'        => $cg->txHash,
                'trangThai'     => $cg->trangThai?->value ?? $cg->trangThai,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $data,
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }

    /**
     * API: Lịch sử đã tiếp nhận (RECEIVED)
     */
    public function danhSachDaTiepNhan(Request $request): JsonResponse
    {
        $toChuc = Auth::user()?->toChuc;
        if (! $toChuc) {
            return response()->json(['success' => false, 'message' => 'Tài khoản chưa gắn tổ chức.'], 403);
        }

        $query = ChuyenGiao::query()
            ->with(['loThuoc.sanPham', 'benGui'])
            ->where('benNhanId', $toChuc->id)
            ->where('trangThai', TransferStatus::received->value)
            ->latest('thoiGianXacNhan');

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('maChuyenGiao', 'like', "%{$search}%")
                    ->orWhereHas('loThuoc', fn ($lq) => $lq->where('maLoNghiepVu', 'like', "%{$search}%"))
                    ->orWhereHas('loThuoc.sanPham', fn ($sq) => $sq->where('tenSanPham', 'like', "%{$search}%"));
            });
        }

        $paginator = $query->paginate((int) $request->input('per_page', 10));

        $data = $paginator->getCollection()->map(function (ChuyenGiao $cg) {
            $lo = $cg->loThuoc;
            return [
                'id'            => $cg->id,
                'maGiaoDich'    => $cg->maChuyenGiao,
                'maLo'          => $lo?->maLoNghiepVu,
                'tenSanPham'    => $lo?->sanPham?->tenSanPham,
                'soDangKy'      => $lo?->sanPham?->soDangKy ?? null,
                'nhaSanXuat'    => $lo?->sanPham?->nhaSanXuat ?? null,
                'benGui'        => $cg->benGui?->tenToChuc,
                'soLuong'       => (int) $cg->soLuong,
                'hanDung'       => optional($lo?->hanSuDung)->format('Y-m-d'),
                'ngayGui'       => optional($cg->thoiGianKhoiTao)->format('d/m/Y H:i'),
                'ngayNhan'      => optional($cg->thoiGianXacNhan)->format('d/m/Y H:i'),
                'trangThaiLo'   => $lo?->trangThai?->value ?? $lo?->trangThai,
                'txHash'        => $cg->txHash,
                'trangThai'     => $cg->trangThai?->value ?? $cg->trangThai,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $data,
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }

    /**
     * API: Nhà thuốc xác nhận tiếp nhận lô
     */
    public function xacNhan(Request $request, int $id): JsonResponse
    {
        $user   = Auth::user();
        $toChuc = $user?->toChuc;

        if (! $toChuc) {
            return response()->json(['success' => false, 'message' => 'Tài khoản chưa gắn tổ chức.'], 403);
        }

        $chuyenGiao = ChuyenGiao::find($id);
        if (! $chuyenGiao) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy giao dịch.'], 404);
        }

        try {
            $this->chuyenGiaoService->nhan($chuyenGiao, $toChuc, $user, $request->ip());

            return response()->json([
                'success' => true,
                'message' => 'Đã tiếp nhận lô hàng và cập nhật tồn kho thành công.',
            ]);
        } catch (NghiepVuException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Có lỗi hệ thống, vui lòng thử lại.'], 500);
        }
    }
}

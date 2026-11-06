<?php

namespace App\Http\Controllers\NhaPhanPhoi;

use App\Enums\OrganizationApprovalStatus;
use App\Enums\OrganizationType;
use App\Enums\TransferStatus;
use App\Exceptions\NghiepVuException;
use App\Http\Controllers\Controller;
use App\Models\ChuyenGiao;
use App\Models\ToChuc;
use App\Models\TonKhoLo;
use App\Services\ChuoiCungUng\ChuyenGiaoService;
use App\Services\ChuoiCungUng\TonKhoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class QuanLyLoHangController extends Controller
{
    public function __construct(
        private ChuyenGiaoService $chuyenGiaoService,
        private TonKhoService $tonKhoService,
    ) {
    }

    /**
     * Trang quản lý lô hàng của Nhà phân phối (Blade)
     */
    public function index(): View
    {
        return view('nhaPhanPhoi.quanLyLoHang');
    }

    /**
     * API: Danh sách lô hàng đang chờ NPP tiếp nhận (PENDING, benNhan = tổ chức hiện tại)
     */
    public function danhSachChoTiepNhan(Request $request): JsonResponse
    {
        $toChuc = Auth::user()?->toChuc;
        if (! $toChuc) {
            return response()->json(['success' => false, 'message' => 'Tài khoản chưa gắn tổ chức.'], 403);
        }

        $query = ChuyenGiao::query()
            ->with(['loThuoc.sanPham', 'benGui', 'blockchainTransactions'])
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
            $latestTx = $cg->blockchainTransactions->sortByDesc('id')->first();
            return [
                'id'            => $cg->id,
                'maGiaoDich'    => $cg->maChuyenGiao,
                'maLo'          => $lo?->maLoNghiepVu,
                'tenSanPham'    => $lo?->sanPham?->tenSanPham,
                'benGui'        => $cg->benGui?->tenToChuc,
                'maBenGui'      => $cg->benGui?->maSoThue,
                'soLuong'       => (int) $cg->soLuong,
                'ngayGui'       => optional($cg->thoiGianKhoiTao)->format('d/m/Y H:i'),
                'hanDung'       => optional($lo?->hanSuDung)->format('Y-m-d'),
                'trangThaiLo'   => $lo?->trangThai?->value ?? $lo?->trangThai,
                'txHash'        => $latestTx?->txHash,
                'trangThaiBlockchain' => $latestTx?->trangThai,
                'dangXuLyBlockchain' => $latestTx && strtoupper((string) $latestTx->trangThai) === 'PENDING',
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
     * API: Tồn kho hiện có của NPP (có thể chuyển tiếp)
     */
    public function danhSachKho(Request $request): JsonResponse
    {
        $toChuc = Auth::user()?->toChuc;
        if (! $toChuc) {
            return response()->json(['success' => false, 'message' => 'Tài khoản chưa gắn tổ chức.'], 403);
        }

        $query = TonKhoLo::query()
            ->with(['loThuoc.sanPham'])
            ->where('toChucId', $toChuc->id)
            ->where('soLuong', '>', 0)
            ->whereHas('loThuoc', function ($q) {
                $q->where('trangThai', '!=', 'RECALLED')
                    ->whereDate('hanSuDung', '>=', today());
            });

        if ($search = $request->input('q')) {
            $query->whereHas('loThuoc', function ($q) use ($search) {
                $q->where('maLoNghiepVu', 'like', "%{$search}%")
                    ->orWhereHas('sanPham', fn ($sq) => $sq->where('tenSanPham', 'like', "%{$search}%"));
            });
        }

        $items = $query->get()->map(function (TonKhoLo $tk) use ($toChuc) {
            $lo = $tk->loThuoc;
            $khaDung = $this->tonKhoService->soLuongKhaDung($lo->id, $toChuc->id);

            return [
                'loThuocId'      => $lo->id,
                'maLo'           => $lo->maLoNghiepVu,
                'tenSanPham'     => $lo->sanPham?->tenSanPham,
                'hanDung'        => optional($lo->hanSuDung)->format('Y-m-d'),
                'soLuongHienCo'  => (int) $tk->soLuong,
                'soLuongKhaDung' => $khaDung,
                'trangThaiLo'    => $lo->trangThai instanceof \App\Enums\BatchStatus ? $lo->trangThai->value : (string) ($lo->trangThai ?? ''),
            ];
        })->filter(fn ($item) => $item['soLuongKhaDung'] > 0)->values();

        return response()->json([
            'success' => true,
            'data'    => $items,
        ]);
    }

    /**
     * API: Danh sách Nhà thuốc đã duyệt (để chọn khi chuyển tiếp)
     */
    public function danhSachNhaThuoc(): JsonResponse
    {
        $danhSach = ToChuc::query()
            ->where('loaiToChuc', OrganizationType::nhaThuoc->value)
            ->where('trangThaiDuyet', OrganizationApprovalStatus::daDuyet->value)
            ->orderBy('tenToChuc')
            ->get(['id', 'tenToChuc', 'maSoThue', 'diaChi']);

        return response()->json([
            'success' => true,
            'data'    => $danhSach,
        ]);
    }

    /**
     * API: NPP tạo yêu cầu chuyển giao sang Nhà thuốc
     * + Tạo BlockchainTransaction PENDING
     * + Dispatch ProcessBatchTransferJob để ký on-chain thật (giống luồng NSX → NPP)
     */
    public function taoChuyenGiao(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'loThuocId' => 'required|integer|exists:loThuoc,id',
            'benNhanId' => 'required|integer|exists:toChuc,id',
            'soLuong' => 'required|integer|min:1',
        ]);

        $user = Auth::user();
        $toChuc = $user?->toChuc;
        if (! $toChuc || $toChuc->loaiToChuc !== OrganizationType::nhaPhanPhoi) {
            return response()->json(['success' => false, 'message' => 'Chỉ Nhà phân phối mới được thực hiện thao tác này.'], 403);
        }

        try {
            $chuyenGiao = $this->chuyenGiaoService->tao($toChuc, $validated, $user, $request->ip());
            return response()->json([
                'success' => true,
                'message' => 'Đã tạo yêu cầu chuyển giao. Hệ thống đang gửi giao dịch submitTransfer lên Ethereum Sepolia.',
                'data' => [
                    'id' => $chuyenGiao->id,
                    'maGiaoDich' => $chuyenGiao->maChuyenGiao,
                    'trangThai' => $chuyenGiao->trangThai instanceof TransferStatus ? $chuyenGiao->trangThai->value : (string) $chuyenGiao->trangThai,
                ],
            ]);
        } catch (NghiepVuException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Có lỗi hệ thống khi tạo chuyển giao.'], 500);
        }
    }

    /**
     * API: NPP xác nhận tiếp nhận lô từ NSX
     */
    public function tiepNhan(Request $request, int $id): JsonResponse
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
                'message' => 'Đã gửi yêu cầu tiếp nhận. Tồn kho sẽ cập nhật sau khi blockchain xác nhận.',
            ]);
        } catch (NghiepVuException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Có lỗi hệ thống, vui lòng thử lại.'], 500);
        }
    }
    
    /**
     * API: NPP từ chối tiếp nhận lô từ NSX
     * POST /nha-phan-phoi/api/tu-choi/{id}
     */
    public function tuChoi(Request $request, int $id): JsonResponse
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

        $lyDo = $request->input('lyDo', 'Nhà phân phối từ chối nhận hàng');

        try {
            $this->chuyenGiaoService->tuChoi($chuyenGiao, $toChuc, $user, $request->ip(), $lyDo);

            return response()->json([
                'success' => true,
                'message' => 'Đã gửi yêu cầu từ chối. Số lượng sẽ được giải phóng sau khi blockchain xác nhận.',
            ]);
        } catch (NghiepVuException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Có lỗi hệ thống, vui lòng thử lại.'], 500);
        }
    }
    /** API: Nhà phân phối hủy yêu cầu chuyển giao đang chờ Nhà thuốc. */
    public function huyChuyenGiao(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $toChuc = $user?->toChuc;
        if (! $toChuc || $toChuc->loaiToChuc !== OrganizationType::nhaPhanPhoi) {
            return response()->json(['success' => false, 'message' => 'Chỉ Nhà phân phối mới được hủy chuyển giao.'], 403);
        }

        $chuyenGiao = ChuyenGiao::query()->find($id);
        if (! $chuyenGiao) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy giao dịch.'], 404);
        }

        try {
            $this->chuyenGiaoService->huy($chuyenGiao, $toChuc, $user, $request->ip(), $request->input('lyDo'));
            return response()->json(['success' => true, 'message' => 'Đã gửi yêu cầu hủy. Trạng thái sẽ cập nhật sau khi blockchain xác nhận.']);
        } catch (NghiepVuException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Không thể gửi yêu cầu hủy.'], 500);
        }
    }

}

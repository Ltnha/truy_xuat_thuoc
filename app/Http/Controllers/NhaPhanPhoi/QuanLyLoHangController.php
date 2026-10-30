<?php

namespace App\Http\Controllers\NhaPhanPhoi;

use App\Enums\OrganizationApprovalStatus;
use App\Enums\OrganizationType;
use App\Enums\TransferStatus;
use App\Exceptions\NghiepVuException;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessBatchTransferJob;
use App\Models\BlockchainIdentity;
use App\Models\BlockchainTransaction;
use App\Models\ChuyenGiao;
use App\Models\LoThuoc;
use App\Models\NhatKyHeThong;
use App\Models\ToChuc;
use App\Models\TonKhoLo;
use App\Services\ChuoiCungUng\ChuyenGiaoService;
use App\Services\ChuoiCungUng\TonKhoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
                'benGui'        => $cg->benGui?->tenToChuc,
                'maBenGui'      => $cg->benGui?->maSoThue,
                'soLuong'       => (int) $cg->soLuong,
                'ngayGui'       => optional($cg->thoiGianKhoiTao)->format('d/m/Y H:i'),
                'hanDung'       => optional($lo?->hanSuDung)->format('Y-m-d'),
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
                'trangThaiLo'    => $lo->trangThai?->value ?? $lo->trangThai,
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
        $request->validate([
            'loThuocId' => 'required|integer|exists:loThuoc,id',
            'benNhanId' => 'required|integer|exists:toChuc,id',
            'soLuong'   => 'required|integer|min:1',
        ], [
            'loThuocId.required' => 'Thiếu mã lô thuốc.',
            'benNhanId.required' => 'Vui lòng chọn nhà thuốc tiếp nhận.',
            'soLuong.required'   => 'Vui lòng nhập số lượng chuyển.',
            'soLuong.min'        => 'Số lượng tối thiểu là 1.',
        ]);

        $user   = Auth::user();
        $toChuc = $user?->toChuc;

        if (! $toChuc || $toChuc->loaiToChuc !== OrganizationType::nhaPhanPhoi) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ Nhà phân phối mới được thực hiện thao tác này.',
            ], 403);
        }

        $loThuoc = LoThuoc::find($request->loThuocId);
        if (! $loThuoc) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy lô thuốc.'], 404);
        }

        // Kiểm tra tồn kho khả dụng
        $soLuong = (int) $request->soLuong;
        $khaDung = $this->tonKhoService->soLuongKhaDung($loThuoc->id, $toChuc->id);
        if ($soLuong > $khaDung) {
            return response()->json([
                'success' => false,
                'message' => "Số lượng chuyển ({$soLuong}) vượt quá số khả dụng ({$khaDung}).",
            ], 422);
        }

        // Nhà thuốc thật, đã duyệt
        $benNhan = ToChuc::where('id', $request->benNhanId)
            ->where('loaiToChuc', OrganizationType::nhaThuoc->value)
            ->where('trangThaiDuyet', OrganizationApprovalStatus::daDuyet->value)
            ->first();

        if (! $benNhan) {
            return response()->json([
                'success' => false,
                'message' => 'Nhà thuốc không hợp lệ hoặc chưa được phê duyệt.',
            ], 422);
        }

        if ($benNhan->id === $toChuc->id) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể chuyển giao cho chính tổ chức của bạn.',
            ], 422);
        }

        // Ví on-chain bên nhận (khuyến nghị)
        $identityNhan = BlockchainIdentity::where('toChucId', $benNhan->id)
            ->where('trangThai', 'ACTIVE')
            ->first();

        $maChuyenGiao = (string) Str::uuid();

        try {
            $chuyenGiao = DB::transaction(function () use (
                $loThuoc, $toChuc, $benNhan, $soLuong, $maChuyenGiao, $user
            ) {
                $cg = ChuyenGiao::create([
                    'maChuyenGiao'    => $maChuyenGiao,
                    'loThuocId'       => $loThuoc->id,
                    'benGuiId'        => $toChuc->id,
                    'benNhanId'       => $benNhan->id,
                    'soLuong'         => $soLuong,
                    'trangThai'       => TransferStatus::pending->value,
                    'thoiGianKhoiTao' => now(),
                ]);

                // Ghi blockchain_transaction PENDING (job sẽ cập nhật thành CONFIRMED)
                BlockchainTransaction::create([
                    'txHash'           => 'PENDING_CG_' . str_replace('-', '', $maChuyenGiao),
                    'blockNumber'      => null,
                    'eventName'        => 'BatchEventRecorded',
                    'doiTuongToChucId' => $toChuc->id,
                    'businessId'       => $cg->maChuyenGiao,
                    'network'          => config('truyXuat.blockchain.mang', 'Ethereum Sepolia'),
                    'trangThai'        => 'PENDING',
                    'toChucId'         => $toChuc->id,
                    'sanPhamId'        => $loThuoc->sanPhamId,
                    'loThuocId'        => $loThuoc->id,
                    'chuyenGiaoId'     => $cg->id,
                    'thoiGian'         => now(),
                ]);

                // Audit log
                if (class_exists(NhatKyHeThong::class)) {
                    NhatKyHeThong::create([
                        'taiKhoanId' => $user->id,
                        'doiTuongId' => $cg->id,
                        'hanhDong'   => 'KHOI_TAO_CHUYEN_GIAO',
                        'ketQua'     => 'THANH_CONG',
                        'chiTiet'    => "NPP chuyển {$soLuong} đơn vị lô [{$loThuoc->maLoNghiepVu}] → Nhà thuốc [{$benNhan->tenToChuc}]. Chờ ký On-chain.",
                        'thoiGian'   => now(),
                        'diaChiIP'   => request()->ip(),
                    ]);
                }

                return $cg;
            });

            // ★ Tự động ký on-chain thật (giống luồng NSX → NPP)
            ProcessBatchTransferJob::dispatch($chuyenGiao->id, $user->id)->afterCommit();

            return response()->json([
                'success' => true,
                'message' => 'Đã gửi yêu cầu chuyển giao. Hệ thống đang ký giao dịch On-chain.',
                'data'    => [
                    'id'         => $chuyenGiao->id,
                    'maGiaoDich' => $chuyenGiao->maChuyenGiao,
                    'trangThai'  => $chuyenGiao->trangThai,
                    'canhBaoVi'  => $identityNhan
                        ? null
                        : 'Nhà thuốc chưa có ví On-chain ACTIVE. Vẫn ghi off-chain; on-chain có thể dùng ví Admin.',
                ],
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi hệ thống, vui lòng thử lại.',
            ], 500);
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

<?php

namespace App\Http\Controllers\LoThuoc;

use App\Enums\OrganizationApprovalStatus;
use App\Enums\OrganizationType;
use App\Enums\ProductApprovalStatus;
use App\Enums\QRType;
use App\Enums\TransferStatus;
use App\Exceptions\NghiepVuException;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoThuoc\LoThuocRequest;
use App\Models\BlockchainTransaction;
use App\Models\LoThuoc;
use App\Models\SanPham;
use App\Models\ToChuc;
use App\Services\ChuoiCungUng\ChuyenGiaoService;
use App\Services\ChuoiCungUng\TonKhoService;
use App\Services\LoThuoc\LoThuocService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoThuocController extends Controller
{
    public function __construct(
        protected LoThuocService $loThuocService,
        protected TonKhoService $tonKhoService,
    ) {
    }

    public function index(Request $request): View
    {
        $toChuc = $request->user()->toChuc;
        abort_if($toChuc === null, 403, 'Tài khoản chưa được liên kết với tổ chức.');

        $sanPhams = SanPham::query()
            ->where('nhaSanXuatId', $toChuc->id)
            ->where('trangThaiDuyet', ProductApprovalStatus::daDuyet)
            ->orderBy('tenSanPham')
            ->get();

        $nhaPhanPhois = ToChuc::query()
            ->with('blockchainIdentity')
            ->where('loaiToChuc', OrganizationType::nhaPhanPhoi)
            ->where('trangThaiDuyet', OrganizationApprovalStatus::daDuyet)
            ->orderBy('tenToChuc')
            ->get();

        $loThuocs = LoThuoc::query()
            ->with([
                'sanPham',
                'tonKhoLos' => fn ($q) => $q->where('toChucId', $toChuc->id),
                'qrCodes',
                'chuyenGiao.benNhan',
                'blockchainTransactions',
            ])
            ->where('toChucId', $toChuc->id)
            ->latest('id')
            ->get();

        $danhSachLo = $loThuocs->map(function (LoThuoc $lo) use ($toChuc) {
            return $this->chuanHoaLoThuocChoFrontend($lo, $toChuc);
        })->values()->all();

        $blockchainConfig = [
            'network' => config('truyXuat.blockchain.mang', 'Ethereum Sepolia'),
            'contractAddress' => config('truyXuat.blockchain.diaChiHopDong', '0x07487C8C08832A0141992B0229d7667c2b2B7127'),
            'explorerUrl' => 'https://sepolia.etherscan.io/tx/',
            'tongSoLo' => $loThuocs->count(),
            'tongSoGiaoDich' => BlockchainTransaction::query()->where('toChucId', $toChuc->id)->count(),
        ];

        return view('nhaSanXuat.quanLyLoThuoc', [
            'sanPhams' => $sanPhams,
            'nhaPhanPhois' => $nhaPhanPhois,
            'danhSachLo' => $danhSachLo,
            'blockchainConfig' => $blockchainConfig,
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $toChuc = $request->user()->toChuc;
        abort_if($toChuc === null, 403, 'Tài khoản chưa được liên kết với tổ chức.');

        $validated = $request->validate([
            'sanPhamId' => ['required', 'integer', 'exists:sanPham,id'],
            'maLoNghiepVu' => ['required', 'string', 'max:50', 'unique:loThuoc,maLoNghiepVu'],
            'soLuong' => ['required', 'integer', 'min:1'],
            'ngaySanXuat' => ['required', 'date'],
            'hanSuDung' => ['required', 'date', 'after:ngaySanXuat'],
        ]);

        try {
            $loThuoc = $this->loThuocService->dangKy($toChuc, $validated, $request->user(), $request->ip());
        } catch (NghiepVuException $exception) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $exception->getMessage(),
                ], 422);
            }

            return back()->withInput()->with('loi', $exception->getMessage());
        }

        $loThuoc->load([
            'sanPham',
            'tonKhoLos' => fn ($q) => $q->where('toChucId', $toChuc->id),
            'qrCodes',
            'chuyenGiao.benNhan',
            'blockchainTransactions',
        ]);

        $loData = $this->chuanHoaLoThuocChoFrontend($loThuoc, $toChuc);

        if ($request->expectsJson() || $request->ajax()) {
            $driver = config('truyXuat.blockchain.driver', 'fake');
            $isMetaMask = ($driver === 'metamask');

            return response()->json([
                'success'          => true,
                'message'          => 'Đã đăng ký lô thuốc, sinh mã QR và ghi nhận transaction on-chain.',
                'loThuoc'          => $loData,
                // Cần khi driver = metamask: frontend sẽ ký MetaMask rồi gọi confirm-tx
                'metamaskRequired' => $isMetaMask,
                'businessId'       => $loThuoc->maLo, // UUID dùng làm key cập nhật txHash
                'contractAddress'  => config('truyXuat.blockchain.diaChiHopDong'),
                'chainId'          => config('truyXuat.blockchain.chainId', 11155111),
                'confirmTxUrl'     => route('api.blockchain.confirm-tx'),
            ]);
        }

        return back()->with('thanhCong', 'Đã đăng ký lô thuốc, sinh mã truy xuất và ghi nhận giao dịch.');
    }

    public function phanPhoi(Request $request, ChuyenGiaoService $chuyenGiaoService): JsonResponse|RedirectResponse
    {
        $toChuc = $request->user()->toChuc;
        abort_if($toChuc === null, 403, 'Tài khoản chưa được liên kết với tổ chức.');

        $validated = $request->validate([
            'maLo' => ['required', 'string'],
            'benNhanId' => ['required', 'integer', 'exists:toChuc,id'],
            'soLuong' => ['required', 'integer', 'min:1'],
        ]);

        $loThuoc = LoThuoc::query()
            ->where('toChucId', $toChuc->id)
            ->where(function ($q) use ($validated): void {
                $q->where('maLoNghiepVu', $validated['maLo'])
                    ->orWhere('maLo', $validated['maLo']);
            })
            ->first();

        if (! $loThuoc) {
            $msg = 'Không tìm thấy lô thuốc tương ứng với mã cung cấp.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 404);
            }
            return back()->with('loi', $msg);
        }

        try {
            $chuyenGiao = $chuyenGiaoService->tao($toChuc, [
                'loThuocId' => $loThuoc->id,
                'benNhanId' => $validated['benNhanId'],
                'soLuong' => $validated['soLuong'],
            ], $request->user(), $request->ip());
        } catch (NghiepVuException $exception) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $exception->getMessage()], 422);
            }
            return back()->with('loi', $exception->getMessage());
        }

        $benNhan = ToChuc::find($validated['benNhanId']);
        $khaDungMoi = $this->tonKhoService->soLuongKhaDung($loThuoc->id, $toChuc->id);

        // Đổi trạng thái lô sang IN_TRANSIT nếu đang CREATED
        if ($loThuoc->trangThai === \App\Enums\BatchStatus::created) {
            $loThuoc->update(['trangThai' => \App\Enums\BatchStatus::inTransit]);
        }

        $cgItem = [
            'id' => $chuyenGiao->id,
            'benNhan' => $benNhan?->tenToChuc ?? 'Chưa rõ',
            'soLuong' => (int) $chuyenGiao->soLuong,
            'thoiGianKhoiTao' => $chuyenGiao->thoiGianKhoiTao ? $chuyenGiao->thoiGianKhoiTao->format('d/m/Y H:i') : now()->format('d/m/Y H:i'),
            'thoiGianXacNhan' => null,
            'trangThai' => 'PENDING',
        ];

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Đã gửi yêu cầu chuyển giao {$chuyenGiao->soLuong} hộp đến {$benNhan->tenToChuc}! Trạng thái: PENDING.",
                'chuyenGiao' => $cgItem,
                'soLuongKhaDung' => $khaDungMoi,
                'trangThai' => 'IN_TRANSIT',
            ]);
        }

        return back()->with('thanhCong', 'Đã khởi tạo yêu cầu chuyển giao lô thuốc.');
    }

    private function chuanHoaLoThuocChoFrontend(LoThuoc $lo, ToChuc $toChuc): array
    {
        $tonKhoHienCo = $lo->tonKhoLos->firstWhere('toChucId', $toChuc->id)?->soLuong ?? 0;

        $dangPending = $lo->chuyenGiao
            ->where('benGuiId', $toChuc->id)
            ->where('trangThai', TransferStatus::pending)
            ->sum('soLuong');

        $khaDung = max(0, $tonKhoHienCo - $dangPending);

        $txDangKy = $lo->blockchainTransactions
            ->where('eventName', 'BatchRegistered')
            ->sortByDesc('id')
            ->first();

        $txHash = $txDangKy?->txHash
            ?? $lo->blockchainTransactions->sortByDesc('id')->first()?->txHash
            ?? null;

        $qrLo = $lo->qrCodes->firstWhere('loaiQR', QRType::batch->value)?->giaTriQR ?? $lo->maLo;

        $chuyenGiaoList = $lo->chuyenGiao->sortByDesc('id')->map(function ($cg) {
            return [
                'id' => $cg->id,
                'benNhan' => $cg->benNhan?->tenToChuc ?? 'Chưa rõ',
                'soLuong' => (int) $cg->soLuong,
                'thoiGianKhoiTao' => $cg->thoiGianKhoiTao ? $cg->thoiGianKhoiTao->format('d/m/Y H:i') : '',
                'thoiGianXacNhan' => $cg->thoiGianXacNhan ? $cg->thoiGianXacNhan->format('d/m/Y H:i') : null,
                'trangThai' => $cg->trangThai instanceof \BackedEnum ? $cg->trangThai->value : (string) $cg->trangThai,
            ];
        })->values()->all();

        return [
            'id' => $lo->id,
            'maLo' => $lo->maLoNghiepVu,
            'maBusinessId' => $lo->maLo,
            'tenSanPham' => $lo->sanPham?->tenSanPham ?? 'Chưa rõ',
            'soDangKy' => $lo->sanPham?->soDangKy ?? '',
            'sanPhamId' => $lo->sanPhamId,
            'ngaySX' => $lo->ngaySanXuat ? $lo->ngaySanXuat->format('Y-m-d') : '',
            'hanDung' => $lo->hanSuDung ? $lo->hanSuDung->format('Y-m-d') : '',
            'soLuongTong' => (int) $lo->soLuong,
            'soLuongKhaDung' => $khaDung,
            'trangThai' => $lo->trangThai instanceof \BackedEnum ? $lo->trangThai->value : (string) $lo->trangThai,
            'txHash' => $txHash,
            'maQr' => $qrLo,
            'chuyenGiao' => $chuyenGiaoList,
        ];
    }
}

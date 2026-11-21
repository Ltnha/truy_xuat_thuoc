<?php

namespace App\Http\Controllers\LoThuoc;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessBatchRegistrationJob;
use App\Models\BlockchainIdentity;
use App\Models\BlockchainTransaction;
use App\Models\ChuyenGiao;
use App\Models\LoThuoc;
use App\Models\SanPham;
use App\Models\ToChuc;
use App\Enums\OrganizationApprovalStatus;
use App\Enums\OrganizationType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LoThuocController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $danhSachSanPham = SanPham::where('nhaSanXuatId', $user->toChucId ?? null)
            ->where('trangThaiDuyet', 'DA_DUYET')
            ->get();

        $danhSachNhaPhanPhoi = ToChuc::query()
            ->where('loaiToChuc', OrganizationType::nhaPhanPhoi->value ?? 'NHA_PHAN_PHOI')
            ->where('trangThaiDuyet', OrganizationApprovalStatus::daDuyet->value ?? 'DA_DUYET')
            ->orderBy('tenToChuc')
            ->get(['id', 'tenToChuc', 'maSoThue']);

        return view('nhaSanXuat.quanLyLoThuoc', compact('danhSachSanPham', 'danhSachNhaPhanPhoi'));
    }

    public function getDanhSachAPI()
    {
        $user = Auth::user();
        $toChucId = (int) ($user->toChucId ?? 0);

        // Lấy các lô đang có tồn kho, đang chờ chuyển giao, hoặc vừa khởi tạo (PENDING/FAILED) chưa vào tồn kho
        $danhSachLo = LoThuoc::with(['sanPham', 'blockchainTransactions', 'toChuc', 'tonKhoLos'])
            ->where(function ($query) use ($toChucId) {
                $query->whereHas('tonKhoLos', fn($tonKho) => $tonKho->where('toChucId', $toChucId))
                    ->orWhereHas('chuyenGiao', fn($chuyenGiao) => $chuyenGiao
                        ->where('benGuiId', $toChucId)
                        ->where('trangThai', 'PENDING'))
                    ->orWhere(function ($q) use ($toChucId) {
                        $q->where('toChucId', $toChucId)
                            ->whereHas('blockchainTransactions', fn($tx) => $tx
                                ->where('eventName', 'BatchRegistered')
                                ->whereIn('trangThai', ['PENDING', 'FAILED']));
                    });
            })
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($lo) use ($toChucId) {
                $tongDangChuyen = (int) ChuyenGiao::where('loThuocId', $lo->id)
                    ->where('benGuiId', $toChucId)
                    ->where('trangThai', 'PENDING')
                    ->sum('soLuong');

                $dongTonKho = DB::table('tonKhoLo')
                    ->where('loThuocId', $lo->id)
                    ->where('toChucId', $toChucId)
                    ->first();

                $hienCo = $dongTonKho
                    ? (int) $dongTonKho->soLuong
                    : ($tongDangChuyen > 0 ? $tongDangChuyen : (int) $lo->soLuong);

                $lo->soLuongHienCo = $hienCo;
                $lo->soLuongDangChuyenGiao = $tongDangChuyen;
                $lo->soLuongKhaDung = max(0, $hienCo - $tongDangChuyen);
                $lo->coChuyenGiaoCho = $tongDangChuyen > 0;
                $lo->trangThaiKho = $tongDangChuyen > 0 ? 'DANG_CHUYEN_GIAO' : 'SAN_SANG';

                $cgGanNhat = ChuyenGiao::where('loThuocId', $lo->id)
                    ->where('benGuiId', $toChucId)
                    ->orderByDesc('id')
                    ->first();

                if ($cgGanNhat) {
                    $st = $cgGanNhat->trangThai instanceof \App\Enums\TransferStatus
                        ? $cgGanNhat->trangThai->value
                        : (string) $cgGanNhat->trangThai;
                    $lo->chuyenGiaoGanNhat = [
                        'id'        => $cgGanNhat->id,
                        'trangThai' => strtoupper($st),
                        'soLuong'   => (int) $cgGanNhat->soLuong,
                    ];
                } else {
                    $lo->chuyenGiaoGanNhat = null;
                }

                return $lo;
            });

        return response()->json([
            'success' => true,
            'data' => $danhSachLo
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'sanPhamId'    => 'required|integer|exists:sanPham,id',
            'maLoNghiepVu' => 'required|string|max:50|unique:loThuoc,maLoNghiepVu',
            'soLuong'      => 'required|integer|min:1',
            'donViTinh'    => 'required|string|max:50',
            'ngaySanXuat'  => 'required|date|before_or_equal:today',
            'hanSuDung'    => 'required|date|after:ngaySanXuat',
            'benNhanId'    => 'nullable|integer|exists:toChuc,id',
        ], [
            'sanPhamId.required'          => 'Vui lòng chọn sản phẩm dược.',
            'sanPhamId.exists'            => 'Sản phẩm đã chọn không tồn tại.',
            'maLoNghiepVu.required'       => 'Mã lô thuốc không được để trống.',
            'maLoNghiepVu.unique'         => 'Mã lô thuốc nghiệp vụ này đã tồn tại trên hệ thống.',
            'soLuong.required'            => 'Vui lòng nhập số lượng sản xuất.',
            'soLuong.min'                 => 'Số lượng sản xuất tối thiểu là 1 đơn vị.',
            'donViTinh.required'          => 'Vui lòng chọn hoặc nhập đơn vị tính.',
            'donViTinh.max'               => 'Đơn vị tính không được vượt quá 50 ký tự.',
            'ngaySanXuat.required'        => 'Ngày sản xuất không được để trống.',
            'ngaySanXuat.before_or_equal' => 'Ngày sản xuất không thể lớn hơn ngày hiện tại.',
            'hanSuDung.required'          => 'Hạn sử dụng không được để trống.',
            'hanSuDung.after'             => 'Hạn sử dụng phải sau ngày sản xuất.',
        ]);

        $user = Auth::user();
        $toChuc = $user->toChuc;

        if ($request->filled('benNhanId')) {
            $nppDuocChon = ToChuc::query()
                ->whereKey($request->integer('benNhanId'))
                ->where('loaiToChuc', OrganizationType::nhaPhanPhoi->value)
                ->where('trangThaiDuyet', OrganizationApprovalStatus::daDuyet->value)
                ->exists();

            if (! $nppDuocChon) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nhà phân phối được chọn không hợp lệ hoặc chưa được Cơ quan quản lý xét duyệt.',
                ], 422);
            }
        }

        $trangThaiToChuc = $toChuc?->trangThaiDuyet instanceof \BackedEnum
            ? $toChuc->trangThaiDuyet->value
            : (string) $toChuc?->trangThaiDuyet;

        if (!$toChuc || $trangThaiToChuc !== 'DA_DUYET') {
            return response()->json([
                'success' => false,
                'message' => 'Tổ chức của bạn chưa được cơ quan quản lý phê duyệt hoặc đang bị tạm khóa!'
            ], 403);
        }

        $identity = BlockchainIdentity::where('toChucId', $toChuc->id)->where('trangThai', 'ACTIVE')->first();
        if (!$identity || $identity->trangThai !== 'ACTIVE') {
            return response()->json([
                'success' => false,
                'message' => 'Ví Blockchain của tổ chức chưa ở trạng thái ACTIVE (chưa được cấp vai trò On-chain). Vui lòng liên hệ Cơ quan quản lý!'
            ], 403);
        }

        $sanPham = SanPham::where('id', $request->sanPhamId)
            ->where('nhaSanXuatId', $toChuc->id)
            ->first();

        if (!$sanPham || $sanPham->trangThaiDuyet !== 'DA_DUYET') {
            return response()->json([
                'success' => false,
                'message' => 'Sản phẩm chưa được cơ quan quản lý phê duyệt hoặc không thuộc nhà sản xuất của bạn!'
            ], 422);
        }

        $maBusinessId = (string) Str::uuid();

        $loThuoc = DB::transaction(function () use ($request, $toChuc, $sanPham, $maBusinessId) {
            $lo = LoThuoc::create([
                'maLo'         => $maBusinessId,
                'maLoNghiepVu' => trim($request->maLoNghiepVu),
                'sanPhamId'    => $sanPham->id,
                'toChucId'     => $toChuc->id,
                'soLuong'      => (int) $request->soLuong,
                'donViTinh'    => trim($request->donViTinh),
                'ngaySanXuat'  => $request->ngaySanXuat,
                'hanSuDung'    => $request->hanSuDung,
                'trangThai'    => 'CREATED',
            ]);

            BlockchainTransaction::create([
                'txHash'           => null,
                'blockNumber'      => null,
                'eventName'        => 'BatchRegistered',
                'doiTuongToChucId' => $toChuc->id,
                'businessId'       => $maBusinessId,
                'network'          => config('truyXuat.blockchain.mang', 'Ethereum Sepolia Testnet'),
                'trangThai'        => 'PENDING',
                'toChucId'         => $toChuc->id,
                'sanPhamId'        => $sanPham->id,
                'loThuocId'        => $lo->id,
                'thoiGian'         => now(),
            ]);

            return $lo;
        });

        ProcessBatchRegistrationJob::dispatch($loThuoc->id, $user->id)->afterCommit();

        return response()->json([
            'success' => true,
            'message' => 'Đã tiếp nhận yêu cầu tạo lô thuốc thành công! Hệ thống đang xử lý và đồng bộ lên Blockchain.',
            'data'    => $loThuoc->load(['sanPham', 'blockchainTransactions']),
        ]);
    }
}

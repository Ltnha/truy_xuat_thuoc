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

        $danhSachLo = LoThuoc::with(['sanPham', 'blockchainTransactions', 'toChuc'])
            ->where('toChucId', $user->toChucId ?? null)
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($lo) {
                // Tính tổng số lượng đang nằm trong các đơn chuyển giao chờ xác nhận (PENDING)
                $tongDangChuyen = ChuyenGiao::where('loThuocId', $lo->id)
                    ->where('trangThai', 'PENDING')
                    ->sum('soLuong');

                // Số lượng khả dụng = Tổng số lượng ban đầu - Số lượng đang PENDING
                $lo->soLuongKhaDung = max(0, (int)$lo->soLuong - (int)$tongDangChuyen);

                return $lo;
            });

        return response()->json([
            'success' => true,
            'data' => $danhSachLo
        ]);
    }

    /**
     * Quy trình tạo lô thuốc mới theo kiến trúc Microservice Custodial Signer:
     * 1. Kiểm tra sản phẩm đã được duyệt.
     * 2. Kiểm tra tổ chức đang hoạt động và ví đang ACTIVE.
     * 3. Kiểm tra ngày sản xuất, hạn sử dụng và số lượng.
     * 4. Tạo maBusinessId cho lô.
     * 5. Lưu yêu cầu tạo lô ở trạng thái chờ blockchain (DB transaction).
     * 6. Dispatch Job sau commit.
     */
    public function store(Request $request)
    {
        // 3. Kiểm tra ngày sản xuất, hạn sử dụng và số lượng
        $request->validate([
            'sanPhamId'    => 'required|integer|exists:sanPham,id',
            'maLoNghiepVu' => 'required|string|max:50|unique:loThuoc,maLoNghiepVu',
            'soLuong'      => 'required|integer|min:1',
            'ngaySanXuat'  => 'required|date|before_or_equal:today',
            'hanSuDung'    => 'required|date|after:ngaySanXuat',
        ], [
            'sanPhamId.required'          => 'Vui lòng chọn sản phẩm dược.',
            'sanPhamId.exists'            => 'Sản phẩm đã chọn không tồn tại.',
            'maLoNghiepVu.required'       => 'Mã lô thuốc không được để trống.',
            'maLoNghiepVu.unique'         => 'Mã lô thuốc nghiệp vụ này đã tồn tại trên hệ thống.',
            'soLuong.required'            => 'Vui lòng nhập số lượng sản xuất.',
            'soLuong.min'                 => 'Số lượng sản xuất tối thiểu là 1 đơn vị.',
            'ngaySanXuat.required'        => 'Ngày sản xuất không được để trống.',
            'ngaySanXuat.before_or_equal' => 'Ngày sản xuất không thể lớn hơn ngày hiện tại.',
            'hanSuDung.required'          => 'Hạn sử dụng không được để trống.',
            'hanSuDung.after'             => 'Hạn sử dụng phải sau ngày sản xuất.',
        ]);

        $user = Auth::user();
        $toChuc = $user->toChuc;

        // 2. Kiểm tra tổ chức đang hoạt động
        $trangThaiToChuc = $toChuc?->trangThaiDuyet instanceof \BackedEnum 
            ? $toChuc->trangThaiDuyet->value 
            : (string) $toChuc?->trangThaiDuyet;

        if (!$toChuc || $trangThaiToChuc !== 'DA_DUYET') {
            return response()->json([
                'success' => false,
                'message' => 'Tổ chức của bạn chưa được cơ quan quản lý phê duyệt hoặc đang bị tạm khóa!'
            ], 403);
        }

        // 2b. Kiểm tra ví blockchain của tổ chức đang ACTIVE
        $identity = BlockchainIdentity::where('toChucId', $toChuc->id)->first();
        if (!$identity || $identity->trangThai !== 'ACTIVE') {
            return response()->json([
                'success' => false,
                'message' => 'Ví Blockchain của tổ chức chưa ở trạng thái ACTIVE (chưa được cấp vai trò On-chain). Vui lòng liên hệ Cơ quan quản lý!'
            ], 403);
        }

        // 1. Kiểm tra sản phẩm đã được duyệt và thuộc tổ chức
        $sanPham = SanPham::where('id', $request->sanPhamId)
            ->where('nhaSanXuatId', $toChuc->id)
            ->first();

        if (!$sanPham || $sanPham->trangThaiDuyet !== 'DA_DUYET') {
            return response()->json([
                'success' => false,
                'message' => 'Sản phẩm chưa được cơ quan quản lý phê duyệt hoặc không thuộc nhà sản xuất của bạn!'
            ], 422);
        }

        // 4. Tạo maBusinessId cho lô (UUID v4)
        $maBusinessId = (string) Str::uuid();

        // 5. Lưu yêu cầu tạo lô ở trạng thái chờ blockchain trong MySQL
        $loThuoc = DB::transaction(function () use ($request, $toChuc, $sanPham, $maBusinessId) {
            $lo = LoThuoc::create([
                'maLo'         => $maBusinessId,
                'maLoNghiepVu' => trim($request->maLoNghiepVu),
                'sanPhamId'    => $sanPham->id,
                'toChucId'     => $toChuc->id,
                'soLuong'      => (int) $request->soLuong,
                'ngaySanXuat'  => $request->ngaySanXuat,
                'hanSuDung'    => $request->hanSuDung,
                'trangThai'    => 'CREATED',
            ]);

            BlockchainTransaction::create([
                'txHash'           => 'PENDING_' . str_replace('-', '', $maBusinessId),
                'blockNumber'      => null,
                'eventName'        => 'BatchEventRecorded',
                'doiTuongToChucId' => $toChuc->id,
                'businessId'       => $maBusinessId,
                'network'          => config('truyXuat.blockchain.network', 'Ethereum Sepolia Testnet'),
                'trangThai'        => 'PENDING',
                'toChucId'         => $toChuc->id,
                'sanPhamId'        => $sanPham->id,
                'loThuocId'        => $lo->id,
                'thoiGian'         => now(),
            ]);

            return $lo;
        });

        // 6. Dispatch Job sau commit
        ProcessBatchRegistrationJob::dispatch($loThuoc->id, $user->id)->afterCommit();

        return response()->json([
            'success' => true,
            'message' => 'Đã tiếp nhận yêu cầu tạo lô thuốc thành công! Hệ thống đang xử lý và đồng bộ lên Blockchain.',
            'data'    => $loThuoc->load(['sanPham', 'blockchainTransactions']),
        ]);
    }
}

<?php

namespace App\Http\Controllers\CoQuan;

use App\Http\Controllers\Controller;
use App\Models\BlockchainIdentity;
use App\Models\BlockchainTransaction;
use App\Models\NhatKyHeThong;
use App\Models\SanPham;
use App\Models\ToChuc;
use App\Models\YeuCauDangKySanPham;
use App\Services\Blockchain\SignerClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DuyetSanPhamController extends Controller
{
    /**
     * Hiển thị danh sách hồ sơ chưa duyệt / bị từ chối và danh mục sản phẩm đã duyệt
     */
    public function index(): View
    {
        // TAB 1: Chỉ lấy các hồ sơ CHỜ DUYỆT hoặc BỊ TỪ CHỐI (Ẩn hoàn toàn sản phẩm đã duyệt)
        $yeuCauDangKys = YeuCauDangKySanPham::with(['sanPham.nhaSanXuat', 'nguoiXuLy'])
            ->whereIn('trangThai', ['CHO_DUYET', 'TU_CHOI'])
            ->latest('id')
            ->get();

        // TAB 2: Danh mục sản phẩm ĐÃ DUYỆT (Lưu hành & On-Chain)
        $sanPhamDaDuyets = SanPham::with(['nhaSanXuat', 'blockchainTransactions' => fn($q) => $q->where('eventName', 'ProductRegistered')])
            ->where('trangThaiDuyet', 'DA_DUYET')
            ->latest('id')
            ->get();

        return view('coQuanQuanLy.xetDuyetSanPham', compact('yeuCauDangKys', 'sanPhamDaDuyets'));
    }

    /**
     * Phê duyệt hồ sơ sản phẩm: Ghi Blockchain trước -> Mới lưu CSDL
     */
    public function pheDuyet(Request $request, YeuCauDangKySanPham $yeuCau, SignerClient $signerClient)
    {
        $user = $request->user();

        if ($yeuCau->trangThai !== 'CHO_DUYET') {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Hồ sơ này không ở trạng thái chờ duyệt.'], 400);
            }
            return back()->with('loi', 'Hồ sơ này không ở trạng thái chờ duyệt.');
        }

        $sanPham = $yeuCau->sanPham;

        // BƯỚC 1: XÁC ĐỊNH TỔ CHỨC CƠ QUAN QUẢN LÝ
        $toChucCoQuan = $user->toChuc
            ?? ToChuc::where('loaiToChuc', 'CO_QUAN_QUAN_LY')->first();

        if (! $toChucCoQuan) {
            $thongBaoLoi = 'Không tìm thấy thông tin Tổ chức Cơ quan Quản lý trong CSDL.';
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $thongBaoLoi], 400);
            }
            return back()->with('loi', $thongBaoLoi);
        }

        // BƯỚC 2: XÁC ĐỊNH KEY ID KÝ GIAO DỊCH BLOCKCHAIN
        $identity = BlockchainIdentity::where('toChucId', $toChucCoQuan->id)->first();

        $keyId = env('ADMIN_MASTER_KEY_ID', 'ADMIN_MASTER_KEY');
        if ($identity && !empty($identity->signerKeyId) && !str_starts_with($identity->signerKeyId, 'KEY-DEMO-')) {
            $keyId = $identity->signerKeyId;
        }

        // BƯỚC 3: ĐỊNH DẠNG DỮ LIỆU VÀ GỬI GIAO DỊCH LÊN ETHEREUM SEPOLIA BLOCKCHAIN
        $batchIdBytes32 = '0x' . hash('sha256', $sanPham->maSanPham);
        $businessIdBytes32 = '0x' . hash('sha256', $sanPham->soDangKy);
        $eventTypeBytes32 = '0x' . hash('sha256', 'PRODUCT_REGISTERED');
        $payloadHashBytes32 = '0x' . hash('sha256', json_encode([
            'tenSanPham' => $sanPham->tenSanPham,
            'soDangKy' => $sanPham->soDangKy,
            'thanhPhan' => $sanPham->thanhPhan,
            'hamLuong' => $sanPham->hamLuong,
            'dangBaoChe' => $sanPham->dangBaoChe,
            'nhaSanXuatId' => $sanPham->nhaSanXuatId,
        ]));

        try {
            $response = $signerClient->sendTransaction([
                'keyId'  => $keyId,
                'action' => 'recordBatchEvent',
                'params' => [
                    'batchId' => $batchIdBytes32,
                    'businessId' => $businessIdBytes32,
                    'eventType' => $eventTypeBytes32,
                    'payloadHash' => $payloadHashBytes32,
                ],
            ]);

            $txHash = $response['txHash'] ?? null;

            if (! $txHash) {
                throw new \RuntimeException('Signer Service không trả về mã giao dịch txHash.');
            }

            // BƯỚC 4: BLOCKCHAIN THÀNH CÔNG -> TIẾN HÀNH LƯU VÀO CSDL
            DB::transaction(function () use ($yeuCau, $sanPham, $user, $txHash): void {
                $now = now();

                $yeuCau->update([
                    'trangThai' => 'DA_DUYET',
                    'nguoiXuLyId' => $user->id,
                    'ngayXuLy' => $now,
                ]);

                $sanPham->update([
                    'trangThaiDuyet' => 'DA_DUYET',
                ]);

                BlockchainTransaction::updateOrCreate(
                    [
                        'sanPhamId' => $sanPham->id,
                        'eventName' => 'ProductRegistered',
                    ],
                    [
                        'txHash' => $txHash,
                        'businessId' => $sanPham->maSanPham,
                        'toChucId' => $sanPham->nhaSanXuatId,
                        'network' => 'Ethereum Sepolia Testnet',
                        'trangThai' => 'CONFIRMED',
                        'thoiGian' => $now,
                    ]
                );

                NhatKyHeThong::create([
                    'taiKhoanId' => $user->id,
                    'doiTuongId' => $user->id,
                    'hanhDong' => 'PHE_DUYET_SAN_PHAM',
                    'ketQua' => 'THANH_CONG',
                    'chiTiet' => "Đã phê duyệt & ghi Blockchain thành công cho thuốc [{$sanPham->tenSanPham}] - TxHash: {$txHash}",
                ]);
            });

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Phê duyệt & Đồng bộ On-Chain thành công!",
                    'txHash' => $txHash,
                    'tenSanPham' => $sanPham->tenSanPham,
                ]);
            }

            return back()->with('thanhCong', "Đã phê duyệt sản phẩm [{$sanPham->tenSanPham}] thành công lên Blockchain. TxHash: {$txHash}");
        } catch (\Throwable $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lỗi ghi Blockchain: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('loi', 'Lỗi đồng bộ Blockchain sản phẩm: ' . $e->getMessage());
        }
    }

    /**
     * Từ chối hồ sơ đăng ký lưu hành sản phẩm
     */
    public function tuChoi(Request $request, YeuCauDangKySanPham $yeuCau): RedirectResponse
    {
        $user = $request->user();

        if ($yeuCau->trangThai !== 'CHO_DUYET') {
            return back()->with('loi', 'Hồ sơ này không ở trạng thái chờ duyệt.');
        }

        $duLieu = $request->validate([
            'lyDoTuChoi' => ['required', 'string', 'max:500'],
        ]);

        $sanPham = $yeuCau->sanPham;

        DB::transaction(function () use ($yeuCau, $sanPham, $duLieu, $user): void {
            $now = now();

            $yeuCau->update([
                'trangThai' => 'TU_CHOI',
                'lyDoTuChoi' => $duLieu['lyDoTuChoi'],
                'nguoiXuLyId' => $user->id,
                'ngayXuLy' => $now,
            ]);

            $sanPham->update([
                'trangThaiDuyet' => 'TU_CHOI',
            ]);

            NhatKyHeThong::create([
                'taiKhoanId' => $user->id,
                'doiTuongId' => $user->id,
                'hanhDong' => 'TU_CHOI_SAN_PHAM',
                'ketQua' => 'THANH_CONG',
                'chiTiet' => "Từ chối hồ sơ đăng ký thuốc [{$sanPham->tenSanPham}]. Lý do: {$duLieu['lyDoTuChoi']}",
            ]);
        });

        return back()->with('thanhCong', "Đã từ chối hồ sơ đăng ký của thuốc [{$sanPham->tenSanPham}].");
    }
}

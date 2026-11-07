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
use App\Services\Blockchain\RoleMap;
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
        // TAB 1: Chỉ lấy các hồ sơ CHỜ DUYỆT hoặc BỊ TỪ CHỐI
        $yeuCauDangKys = YeuCauDangKySanPham::with(['sanPham.nhaSanXuat', 'nguoiXuLy'])
            ->whereIn('trangThai', ['CHO_DUYET', 'TU_CHOI'])
            ->latest('id')
            ->get();

        // TAB 2: Danh mục sản phẩm ĐÃ DUYỆT (Lưu hành & On-Chain)
        $sanPhamDaDuyets = SanPham::with(['nhaSanXuat', 'blockchainTransactions' => fn($q) => $q->whereIn('eventName', ['ProductApproved', 'ProductRegistered'])])
            ->where('trangThaiDuyet', 'DA_DUYET')
            ->latest('id')
            ->get();

        return view('coQuanQuanLy.xetDuyetSanPham', compact('yeuCauDangKys', 'sanPhamDaDuyets'));
    }

    /**
     * Phê duyệt hồ sơ sản phẩm: Ghi nhận phê duyệt trên Blockchain trước -> Mới lưu CSDL
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
        $identity = BlockchainIdentity::where('toChucId', $toChucCoQuan->id)->latest('id')->first();

        if (! $identity || empty($identity->signerKeyId) || str_starts_with($identity->signerKeyId, 'KEY-DEMO-')) {
            $thongBaoLoi = 'Tổ chức CQQL chưa có ví Signer hợp lệ. Vui lòng cấp/tạo ví cho tổ chức trước khi duyệt sản phẩm.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $thongBaoLoi], 422);
            }
            return back()->with('loi', $thongBaoLoi);
        }

        if (empty($identity->diaChiVi) || ! preg_match('/^0x[a-fA-F0-9]{40}$/', $identity->diaChiVi)) {
            $thongBaoLoi = 'Địa chỉ ví CQQL trong blockchainIdentity không hợp lệ.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $thongBaoLoi], 422);
            }
            return back()->with('loi', $thongBaoLoi);
        }

        $keyId = $identity->signerKeyId;
        $adminKeyId = env('ADMIN_MASTER_KEY_ID', 'ADMIN_MASTER_KEY');

        // BƯỚC 3: Đảm bảo ví CQQL có ROLE_CO_QUAN trên contract hiện tại.
        // Chỉ ví Owner/Admin được grantRole; việc duyệt sản phẩm vẫn do ví CQQL ký.
        if (! $signerClient->hasRole(RoleMap::ROLE_CO_QUAN, $identity->diaChiVi)) {
            $signerClient->sendTransaction([
                'keyId' => $adminKeyId,
                'action' => 'grantRole',
                'params' => [
                    'account' => $identity->diaChiVi,
                    'role' => RoleMap::ROLE_CO_QUAN,
                ],
            ]);
        }

        if (! $signerClient->hasRole(RoleMap::ROLE_CO_QUAN, $identity->diaChiVi)) {
            throw new \\RuntimeException('Không thể xác nhận ROLE_CO_QUAN cho ví CQQL trên Smart Contract.');
        }

        // BƯỚC 4: Chuẩn hóa ID sản phẩm, nhà sản xuất và dữ liệu sản phẩm thành bytes32.
        $productIdBytes32 = '0x' . hash('sha256', (string) $sanPham->maSanPham);
        $manufacturerIdBytes32 = '0x' . hash('sha256', (string) $sanPham->nhaSanXuatId);
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
                'keyId' => $keyId,
                'action' => 'approveProduct',
                'params' => [
                    'productId' => $productIdBytes32,
                    'manufacturerId' => $manufacturerIdBytes32,
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
                        'eventName' => 'ProductApproved',
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
                    'chiTiet' => "Đã phê duyệt sản phẩm và ghi nhận on-chain thành công cho thuốc [{$sanPham->tenSanPham}] - TxHash: {$txHash}",
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
    public function tuChoi(Request $request, YeuCauDangKySanPham $yeuCau)
    {
        $user = $request->user();

        if ($yeuCau->trangThai !== 'CHO_DUYET') {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Hồ sơ này không ở trạng thái chờ duyệt.'], 400);
            }
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

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Đã từ chối hồ sơ đăng ký của thuốc [{$sanPham->tenSanPham}].",
                'tenSanPham' => $sanPham->tenSanPham,
            ]);
        }

        return back()->with('thanhCong', "Đã từ chối hồ sơ đăng ký của thuốc [{$sanPham->tenSanPham}].");
    }
}

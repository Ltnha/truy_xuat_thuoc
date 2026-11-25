<?php

namespace App\Http\Controllers\TraCuu;

use App\Http\Controllers\Controller;
use App\Services\Blockchain\SignerClient;
use App\Support\BatchPayloadHasher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class TraCuuController extends Controller
{
    public function __construct(private SignerClient $signer) {}

    public function home(): View
    {
        return view('truyXuat.trangChu', [
            'soLo' => DB::table('loThuoc')->count(),
            'soDonVi' => (int) DB::table('loThuoc')->sum('soLuong'),
            'soGiaoDich' => DB::table('blockchainTransaction')->count(),
            'soLoThuHoi' => DB::table('loThuoc')->where('trangThai', 'RECALLED')->count(),
            'loThuHoi' => DB::table('loThuoc as lt')
                ->join('sanPham as sp', 'sp.id', '=', 'lt.sanPhamId')
                ->join('toChuc as tc', 'tc.id', '=', 'lt.toChucId')
                ->leftJoin('thuHoiLoHang as th', 'th.loThuocId', '=', 'lt.id')
                ->leftJoin('blockchainTransaction as btx', function ($join) {
                    $join->on('btx.loThuocId', '=', 'lt.id')
                        ->where('btx.eventName', '=', 'BatchRecalled')
                        ->where('btx.trangThai', '=', 'CONFIRMED');
                })
                ->where('lt.trangThai', 'RECALLED')
                ->select('lt.maLoNghiepVu', 'lt.id', 'sp.tenSanPham', 'sp.soDangKy', 'tc.tenToChuc', 'th.lyDo', 'btx.txHash')
                ->latest('lt.id')->limit(10)->get(),
        ]);
    }

    public function show(Request $request, ?string $ma = null): View
    {
        $ma = trim((string) ($ma ?: $request->query('maDinhDanh', '')));
        $ketQua = $ma === '' ? null : $this->timTheoMa($ma);
        return view('truyXuat.ketQua', compact('ma', 'ketQua'));
    }

    public function api(string $ma): JsonResponse
    {
        $ketQua = $this->timTheoMa(trim($ma));
        unset($ketQua['loThuocId']);
        return response()->json($ketQua);
    }

    private function timTheoMa(string $ma): array
    {
        $loId = DB::table('qrCode')->where('giaTriQR', $ma)->where('trangThai', 'ACTIVE')->value('loThuocId');
        $loId = $loId ?: DB::table('loThuoc')
            ->where('maLoNghiepVu', $ma)->orWhere('maLo', $ma)->value('id');

        if (! $loId) {
            return [
                'trangThai' => 'NOT_FOUND',
                'loai' => 'MÃ LÔ THUỐC',
                'tenThuoc' => 'Không tìm thấy mã QR hoặc mã lô trong hệ thống.',
                'maLo' => $ma,
                'hanhTrinh' => [],
            ];
        }

        $lo = DB::table('loThuoc as lt')
            ->join('sanPham as sp', 'sp.id', '=', 'lt.sanPhamId')
            ->join('toChuc as tc', 'tc.id', '=', 'lt.toChucId')
            ->where('lt.id', $loId)
            ->select(
                'lt.*',
                'sp.maSanPham',
                'sp.tenSanPham',
                'sp.soDangKy',
                'sp.thanhPhan as hoatChat',
                'sp.hamLuong',
                'sp.dangBaoChe',
                'sp.quyCachDongGoi',
                'sp.nhaSanXuatId',
                'sp.hashHoSo',
                'sp.trangThaiDuyet as trangThaiSanPham',
                'tc.tenToChuc'
            )
            ->first();

        if (! $lo) return ['trangThai' => 'NOT_FOUND', 'loai' => 'MÃ LÔ THUỐC', 'tenThuoc' => 'Không tìm thấy lô.', 'maLo' => $ma, 'hanhTrinh' => []];

        $batchId = '0x' . hash('sha256', 'BATCH_' . (string) $lo->maLo);
        $productId = '0x' . hash('sha256', 'PRODUCT_' . (string) $lo->maSanPham);
        $batch = null;
        $history = [];
        $approval = null;
        $productIntegrity = 'UNVERIFIED';
        $batchIntegrity = 'UNVERIFIED';
        $chainReadStatus = 'UNAVAILABLE';
        $batchExists = false;
        $chainRecalled = false;

        $payload = [
            'maSanPham' => (string) $lo->maSanPham,
            'tenSanPham' => (string) $lo->tenSanPham,
            'soDangKy' => (string) $lo->soDangKy,
            'thanhPhan' => $lo->hoatChat,
            'hamLuong' => $lo->hamLuong,
            'dangBaoChe' => $lo->dangBaoChe,
            'quyCachDongGoi' => $lo->quyCachDongGoi,
            'nhaSanXuatId' => (int) $lo->nhaSanXuatId,
        ];
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
        $expectedHash = $json === false ? null : '0x' . hash('sha256', $json);
        $expectedBatchHash = BatchPayloadHasher::hash($lo);

        // Tier 1: Đối soát trực tuyến qua Signer Microservice (Node.js)
        try {
            $batch = $this->signer->getBatchState($batchId);
            $approval = $this->signer->isProductApproved($productId);
            try {
                $history = $this->signer->getBatchHistory($batchId)['events'] ?? [];
            } catch (\Throwable $hEx) {
                $history = [];
            }
            $chainReadStatus = 'AVAILABLE';
        } catch (\Throwable $exception) {
            Log::warning("[TraCuuController] Không thể xác minh trạng thái trực tiếp từ Signer/Sepolia: " . $exception->getMessage());
        }

        if ($chainReadStatus === 'AVAILABLE') {
            $batchExists = ! empty($batch['batch']['exists']);
            $chainRecalled = ! empty($batch['batch']['recalled']);

            // 1. Đối chiếu toàn vẹn hồ sơ sản phẩm
            $onChainHash = strtolower((string) ($approval['payloadHash'] ?? ''));
            $expHash = strtolower((string) ($expectedHash ?? ''));
            if (empty($approval['isApproved']) || $onChainHash === '0x' . str_repeat('0', 64)) {
                $productIntegrity = 'NOT_APPROVED_ON_CHAIN';
            } elseif ($expectedHash !== null && hash_equals($onChainHash, $expHash)) {
                $productIntegrity = 'MATCH';
            } else {
                $productIntegrity = 'MISMATCH';
            }

            // 2. Đối chiếu toàn vẹn thông tin lô thuốc (maLo, NSX, HSD, ĐVT...)
            $onChainBatchHash = strtolower((string) ($batch['batch']['payloadHash'] ?? ''));
            $expBatchHash = strtolower($expectedBatchHash);
            if (! $batchExists || $onChainBatchHash === '' || $onChainBatchHash === '0x' . str_repeat('0', 64)) {
                $batchIntegrity = 'UNVERIFIED';
            } elseif (hash_equals($onChainBatchHash, $expBatchHash)) {
                $batchIntegrity = 'MATCH';
            } else {
                $batchIntegrity = 'MISMATCH';
            }
        }

        // 3. Đối chiếu chủ sở hữu hiện tại (ưu tiên bản ghi đang giữ tồn kho > 0)
        $stockOwner = DB::table('tonKhoLo as tk')
            ->join('blockchainIdentity as bi', function ($join) {
                $join->on('bi.toChucId', '=', 'tk.toChucId')
                    ->where('bi.trangThai', '=', 'ACTIVE');
            })
            ->where('tk.loThuocId', $lo->id)
            ->orderByRaw('CASE WHEN tk.soLuong > 0 THEN 0 ELSE 1 END ASC')
            ->orderByDesc('tk.id')
            ->first(['tk.toChucId', 'bi.diaChiVi']);

        $chainOwner = strtolower((string) ($batch['batch']['currentOwner'] ?? ''));
        $databaseOwnerAddress = strtolower((string) ($stockOwner->diaChiVi ?? ''));
        $ownerIntegrity = 'UNVERIFIED';

        if (
            $chainReadStatus === 'AVAILABLE'
            && $batchExists
            && preg_match('/^0x[a-f0-9]{40}$/', $chainOwner)
            && preg_match('/^0x[a-f0-9]{40}$/', $databaseOwnerAddress)
        ) {
            $ownerIntegrity = hash_equals($databaseOwnerAddress, $chainOwner) ? 'MATCH' : 'MISMATCH';
        }

        // 4. Kiểm tra hết hạn theo múi giờ Việt Nam (<= hôm nay là hết hạn)
        $homNay = now('Asia/Ho_Chi_Minh')->toDateString();
        $expired = empty($lo->hanSuDung) || (string) $lo->hanSuDung <= $homNay;

        // 5. Kết luận trạng thái (ưu tiên bắt chỉnh sửa trái phép MISMATCH trước EXPIRED)
        $tampered = ($productIntegrity === 'MISMATCH')
            || ($batchIntegrity === 'MISMATCH')
            || ($ownerIntegrity === 'MISMATCH');

        $status = ($chainReadStatus === 'AVAILABLE' && $chainRecalled) ? 'RECALLED'
            : ($chainReadStatus !== 'AVAILABLE' ? 'UNAVAILABLE'
                : (! $batchExists ? 'NOT_REGISTERED_ON_CHAIN'
                    : ($tampered ? 'INVALID'
                        : ($expired ? 'EXPIRED'
                            : ($lo->trangThaiSanPham === 'DA_DUYET'
                                && $productIntegrity === 'MATCH'
                                && $batchIntegrity === 'MATCH'
                                && $ownerIntegrity === 'MATCH' ? 'VALID' : 'INVALID')))));

        $donViTinh = $lo->donViTinh ?: 'Hộp';

        // 1. Sự kiện sản xuất lô thuốc ban đầu
        $prodTx = DB::table('blockchainTransaction')
            ->where('loThuocId', $lo->id)
            ->whereIn('eventName', ['BatchRegistered', 'PRODUCTION'])
            ->latest('id')
            ->first();

        $prodStep = [
            'tieuDe' => 'PRODUCTION',
            'donVi' => "{$lo->tenToChuc} sản xuất lô {$lo->maLoNghiepVu} (" . number_format((int) $lo->soLuong) . " {$donViTinh})",
            'thoiGian' => $prodTx?->thoiGian ?: $lo->ngaySanXuat,
            'canhBao' => false,
            'txHash' => $prodTx?->txHash,
            'blockNumber' => $prodTx?->blockNumber,
        ];

        // 2. Các chuyển giao và tiếp nhận
        $transfers = DB::table('chuyenGiao as cg')
            ->join('toChuc as bg', 'bg.id', '=', 'cg.benGuiId')
            ->join('toChuc as bn', 'bn.id', '=', 'cg.benNhanId')
            ->where('cg.loThuocId', $lo->id)->orderBy('cg.id')
            ->get(['cg.id', 'cg.trangThai', 'cg.soLuong', 'cg.thoiGianKhoiTao', 'cg.thoiGianXacNhan', 'bg.tenToChuc as benGui', 'bn.tenToChuc as benNhan'])
            ->map(function ($row) use ($donViTinh) {
                $cgTx = DB::table('blockchainTransaction')
                    ->where('chuyenGiaoId', $row->id)
                    ->where('trangThai', 'CONFIRMED')
                    ->latest('id')
                    ->first();

                return [
                    'tieuDe' => 'CHUYEN_GIAO_' . $row->trangThai,
                    'donVi' => $row->benGui . ' → ' . $row->benNhan . ' (' . number_format((int) $row->soLuong) . " {$donViTinh})",
                    'thoiGian' => $row->thoiGianXacNhan ?: $row->thoiGianKhoiTao,
                    'canhBao' => in_array($row->trangThai, ['REJECTED', 'CANCELLED']),
                    'txHash' => $cgTx?->txHash,
                    'blockNumber' => $cgTx?->blockNumber,
                ];
            })->all();

        // 3. Bán lẻ (nếu có)
        $sales = DB::table('chiTietBanLe as ct')
            ->join('banLe as bl', 'bl.id', '=', 'ct.banLeId')
            ->where('ct.loThuocId', $lo->id)->where('bl.trangThai', 'DA_HOAN_TAT')
            ->orderBy('bl.ngayBan')->get(['bl.maHoaDon', 'bl.ngayBan', 'ct.soLuong'])
            ->map(fn($row) => [
                'tieuDe' => 'SOLD',
                'donVi' => "Hóa đơn {$row->maHoaDon}: bán " . number_format((int) $row->soLuong) . " {$donViTinh}.",
                'thoiGian' => $row->ngayBan,
                'canhBao' => false,
                'txHash' => null,
                'blockNumber' => null,
            ])->all();

        // 4. Các sự kiện on-chain từ Signer / Sepolia contract
        $chainSteps = array_map(function (array $event): array {
            $eventName = (string) ($event['eventName'] ?? 'BlockchainEvent');
            $args = $event['args'] ?? [];
            return [
                'tieuDe' => $eventName,
                'donVi' => $eventName === 'BatchOwnerChanged'
                    ? (($args['oldOwner'] ?? '—') . ' → ' . ($args['newOwner'] ?? '—'))
                    : 'Sự kiện được ghi nhận trên Ethereum Sepolia.',
                'thoiGian' => ! empty($event['timestamp']) ? \Illuminate\Support\Carbon::createFromTimestamp((int) $event['timestamp'])->toDateTimeString() : null,
                'timestamp' => (int) ($event['timestamp'] ?? 0),
                'canhBao' => $eventName === 'BatchRecalled',
                'txHash' => $event['txHash'] ?? null,
                'blockNumber' => $event['blockNumber'] ?? null,
            ];
        }, $history);

        // Tổng hợp và sắp xếp hành trình theo thời gian
        $timeline = [$prodStep, ...$transfers, ...$sales, ...$chainSteps];
        usort($timeline, fn($a, $b) => strtotime((string) ($a['thoiGian'] ?? '1970-01-01')) <=> strtotime((string) ($b['thoiGian'] ?? '1970-01-01')));

        $soldQuantity = (int) DB::table('chiTietBanLe as ct')
            ->join('banLe as bl', 'bl.id', '=', 'ct.banLeId')
            ->where('ct.loThuocId', $lo->id)->where('bl.trangThai', 'DA_HOAN_TAT')->sum('ct.soLuong');

        $batchTx = DB::table('blockchainTransaction')->where('loThuocId', $lo->id)
            ->whereIn('eventName', ['BatchRegistered', 'PRODUCTION'])
            ->where('trangThai', 'CONFIRMED')
            ->latest('id')
            ->value('txHash');

        return [
            'trangThai' => $status,
            'loai' => 'MÃ LÔ THUỐC',
            'tenThuoc' => $lo->tenSanPham,
            'hamLuongDangBaoChe' => $lo->quyCachDongGoi ?: '—',
            'hoatChat' => $lo->hoatChat ?: '—',
            'soDangKy' => $lo->soDangKy,
            'nhaSanXuat' => $lo->tenToChuc,
            'maLo' => $lo->maLoNghiepVu,
            'ngaySanXuat' => $lo->ngaySanXuat,
            'hanSuDung' => $lo->hanSuDung,
            'batchIdHash' => $batchId,
            'txHash' => $batchTx ?: ($batch['registerTxHash'] ?? 'Chưa có giao dịch blockchain'),
            'productIntegrity' => $productIntegrity,
            'batchIntegrity' => $batchIntegrity,
            'chainReadStatus' => $chainReadStatus,
            'batchExistsOnChain' => $batchExists,
            'chainOwner' => $batch['batch']['currentOwner'] ?? null,
            'databaseOwnerAddress' => $databaseOwnerAddress ?: null,
            'ownerIntegrity' => $ownerIntegrity,
            'chainRecalled' => $chainRecalled,
            'chainTransferIds' => $batch['transferIds'] ?? [],
            'chainTransfers' => [],
            'chainTransferReadStatus' => $chainReadStatus,
            'chainHistory' => $history,
            'isDispensed' => "Đã bán {$soldQuantity} {$donViTinh} từ lô",
            'lyDoThuHoi' => DB::table('thuHoiLoHang')->where('loThuocId', $lo->id)->value('lyDo'),
            'loThuocId' => (int) $lo->id,
            'hanhTrinh' => $timeline,
        ];
    }
}

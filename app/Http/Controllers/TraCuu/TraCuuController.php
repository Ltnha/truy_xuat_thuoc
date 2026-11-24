<?php

namespace App\Http\Controllers\TraCuu;

use App\Http\Controllers\Controller;
use App\Services\Blockchain\SignerClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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
            ->select('lt.*', 'sp.maSanPham', 'sp.tenSanPham', 'sp.soDangKy',
                'sp.thanhPhan as hoatChat', 'sp.hamLuong', 'sp.dangBaoChe',
                'sp.quyCachDongGoi', 'sp.nhaSanXuatId', 'sp.hashHoSo', 'sp.trangThaiDuyet as trangThaiSanPham',
                'tc.tenToChuc')
            ->first();

        if (! $lo) return ['trangThai' => 'NOT_FOUND', 'loai' => 'MÃ LÔ THUỐC', 'tenThuoc' => 'Không tìm thấy lô.', 'maLo' => $ma, 'hanhTrinh' => []];

        $batchId = '0x' . hash('sha256', 'BATCH_' . (string) $lo->maLo);
        $productId = '0x' . hash('sha256', 'PRODUCT_' . (string) $lo->maSanPham);
        $batch = null;
        $history = [];
        $approval = null;
        $productIntegrity = 'UNVERIFIED';
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

        // Tier 1: Thử đối soát trực tuyến qua Signer Microservice (Node.js)
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
            Log::info("[TraCuuController] Signer microservice không phản hồi, chuyển sang đối soát RPC Sepolia: " . $exception->getMessage());
        }

        // Chỉ xem dữ liệu là đã xác minh khi Signer đọc thành công trực tiếp từ contract.
        // Không dùng DB hoặc RPC tự giải mã ABI làm bằng chứng thay thế cho trạng thái on-chain.
        if ($chainReadStatus === 'AVAILABLE') {
            $batchExists = ! empty($batch['batch']['exists']);
            $chainRecalled = ! empty($batch['batch']['recalled']);

            $onChainHash = strtolower((string) ($approval['payloadHash'] ?? ''));
            $expHash = strtolower((string) ($expectedHash ?? ''));
            $dbHash = strtolower((string) ($lo->hashHoSo ?? ''));

            if (empty($approval['isApproved']) || $onChainHash === '0x' . str_repeat('0', 64)) {
                $productIntegrity = 'NOT_APPROVED_ON_CHAIN';
            } elseif ($expectedHash !== null && $onChainHash === $expHash) {
                $productIntegrity = 'MATCH';
            } else {
                $productIntegrity = 'MISMATCH';
            }
        }


        // Chủ sở hữu hiện tại phải khớp giữa tồn kho MySQL và ví ACTIVE trên blockchain.
        $stockOwner = DB::table('tonKhoLo as tk')
            ->join('blockchainIdentity as bi', function ($join) {
                $join->on('bi.toChucId', '=', 'tk.toChucId')
                    ->where('bi.trangThai', '=', 'ACTIVE');
            })
            ->where('tk.loThuocId', $lo->id)
            ->orderByDesc('bi.id')
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

        $expired = (string) $lo->hanSuDung < today()->toDateString();
        $status = ($lo->trangThai === 'RECALLED' || $chainRecalled) ? 'RECALLED'
            : ($expired ? 'EXPIRED'
                : ($chainReadStatus !== 'AVAILABLE' ? 'UNAVAILABLE'
                    : (! $batchExists ? 'NOT_REGISTERED_ON_CHAIN'
                        : ($lo->trangThaiSanPham === 'DA_DUYET'
                            && $productIntegrity === 'MATCH'
                            && $ownerIntegrity === 'MATCH' ? 'VALID' : 'INVALID'))));

        // 1. Sự kiện sản xuất lô thuốc ban đầu
        $prodTx = DB::table('blockchainTransaction')
            ->where('loThuocId', $lo->id)
            ->whereIn('eventName', ['BatchRegistered', 'PRODUCTION'])
            ->latest('id')
            ->first();

        $prodStep = [
            'tieuDe' => 'PRODUCTION',
            'donVi' => "{$lo->tenToChuc} sản xuất lô {$lo->maLoNghiepVu} (" . number_format((int)$lo->soLuong) . " {$lo->donViTinh})",
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
            ->map(function ($row) {
                $cgTx = DB::table('blockchainTransaction')
                    ->where('chuyenGiaoId', $row->id)
                    ->where('trangThai', 'CONFIRMED')
                    ->latest('id')
                    ->first();

                return [
                    'tieuDe' => 'CHUYEN_GIAO_' . $row->trangThai,
                    'donVi' => $row->benGui . ' → ' . $row->benNhan . ' (' . number_format((int)$row->soLuong) . ' đơn vị)',
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
            ->map(fn ($row) => [
                'tieuDe' => 'SOLD',
                'donVi' => "Hóa đơn {$row->maHoaDon}: bán " . number_format((int)$row->soLuong) . " đơn vị.",
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
        usort($timeline, fn ($a, $b) => strtotime((string) ($a['thoiGian'] ?? '1970-01-01')) <=> strtotime((string) ($b['thoiGian'] ?? '1970-01-01')));

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
            'isDispensed' => "Đã bán {$soldQuantity} đơn vị từ lô",
            'lyDoThuHoi' => DB::table('thuHoiLoHang')->where('loThuocId', $lo->id)->value('lyDo'),
            'loThuocId' => (int) $lo->id,
            'hanhTrinh' => $timeline,
        ];
    }

}

<?php

namespace App\Http\Controllers\TraCuu;

use App\Http\Controllers\Controller;
use App\Models\TraCuu;
use App\Services\Blockchain\SignerClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TraCuuController extends Controller
{
    public function __construct(private SignerClient $signer) {}

    public function home(): View
    {
        return view('truyXuat.trangChu', [
            'soLo' => DB::table('loThuoc')->count(),
            'soDonVi' => DB::table('donViSanPham')->count(),
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
                ->latest('lt.id')
                ->limit(10)
                ->get(),
        ]);
    }

    public function show(Request $request, ?string $ma = null): View
    {
        $ma = trim((string) ($ma ?: $request->query('maDinhDanh', '')));
        $ketQua = $ma === '' ? null : $this->timTheoMa($ma);

        if ($ketQua && $ketQua['trangThai'] !== 'NOT_FOUND') {
            TraCuu::query()->create([
                'loThuocId' => $ketQua['loThuocId'],
                'maQRHoacSerial' => mb_substr($ma, 0, 255),
                'thoiGianTraCuu' => now(),
                'diaChiIP' => $request->ip(),
            ]);
        }

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
        $unit = DB::table('donViSanPham as dv')
            ->join('loThuoc as lt', 'lt.id', '=', 'dv.loThuocId')
            ->where(function ($query) use ($ma): void {
                $query->where('dv.maDonVi', $ma)
                    ->orWhereExists(function ($qrQuery) use ($ma): void {
                        $qrQuery->selectRaw('1')
                            ->from('qrCode as qr')
                            ->whereColumn('qr.donViSanPhamId', 'dv.id')
                            ->where('qr.giaTriQR', $ma);
                    });
            })
            ->select('dv.id as donViId', 'dv.maDonVi', 'dv.trangThai as trangThaiDonVi', 'dv.ngayBan', 'lt.id as loThuocId')
            ->first();

        $loThuocId = $unit?->loThuocId;
        if ($loThuocId === null) {
            $qrLo = DB::table('qrCode')
                ->where('giaTriQR', $ma)
                ->where('loaiQR', 'BATCH')
                ->value('loThuocId');
            $loThuocId = $qrLo ?: DB::table('loThuoc')
                ->where('maLoNghiepVu', $ma)
                ->orWhere('maLo', $ma)
                ->value('id');
        }

        if ($loThuocId === null) {
            return [
                'trangThai' => 'NOT_FOUND',
                'loai' => 'KHÔNG TÌM THẤY',
                'tenThuoc' => 'Không tìm thấy mã trong hệ thống.',
                'hamLuongDangBaoChe' => '—',
                'hoatChat' => '—',
                'soDangKy' => '—',
                'nhaSanXuat' => '—',
                'maLo' => $ma,
                'ngaySanXuat' => '—',
                'hanSuDung' => '—',
                'batchIdHash' => '—',
                'txHash' => '—',
                'isDispensed' => 'Không xác định',
                'hanhTrinh' => [],
            ];
        }

        $lo = DB::table('loThuoc as lt')
            ->join('sanPham as sp', 'sp.id', '=', 'lt.sanPhamId')
            ->join('toChuc as tc', 'tc.id', '=', 'lt.toChucId')
            ->where('lt.id', $loThuocId)
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
                'sp.trangThaiDuyet as trangThaiSanPham',
                'tc.tenToChuc'
            )
            ->first();

        $thuHoi = DB::table('thuHoiLoHang')->where('loThuocId', $lo->id)->latest('id')->first();
        $batchTxHash = DB::table('blockchainTransaction')
            ->where('loThuocId', $lo->id)
            ->where('eventName', 'BatchRegistered')
            ->where('trangThai', 'CONFIRMED')
            ->latest('id')
            ->value('txHash');
        $unitTxHash = $unit
            ? DB::table('blockchainTransaction')
                ->where('donViSanPhamId', $unit->donViId)
                ->where('trangThai', 'CONFIRMED')
                ->latest('id')
                ->value('txHash')
            : null;
        $steps = DB::table('lichSuLoThuoc')
            ->where('loThuocId', $lo->id)
            ->orderBy('thoiGian')
            ->get()
            ->map(fn ($step): array => [
                'tieuDe' => $step->suKien,
                'donVi' => $step->moTa ?: 'Lịch sử lô thuốc',
                'thoiGian' => $step->thoiGian,
                'canhBao' => str_contains($step->suKien, 'THU_HOI'),
            ])
            ->all();
        $transferSteps = DB::table('chuyenGiao as cg')
            ->join('toChuc as bg', 'bg.id', '=', 'cg.benGuiId')
            ->join('toChuc as bn', 'bn.id', '=', 'cg.benNhanId')
            ->where('cg.loThuocId', $lo->id)
            ->where('cg.trangThai', 'RECEIVED')
            ->orderBy('cg.thoiGianXacNhan')
            ->get()
            ->map(fn ($transfer): array => [
                'tieuDe' => 'CHUYEN_GIAO_DA_TIEP_NHAN',
                'donVi' => $transfer->benGui.' → '.$transfer->benNhan.' ('.$transfer->soLuong.' đơn vị)',
                'thoiGian' => $transfer->thoiGianXacNhan,
                'canhBao' => false,
            ])
            ->all();

        $batchIdBytes32 = '0x' . hash('sha256', 'BATCH_' . (string) $lo->maLo);
        $productIdBytes32 = '0x' . hash('sha256', 'PRODUCT_' . (string) $lo->maSanPham);
        $chainReadStatus = 'UNAVAILABLE';
        $chainTransferReadStatus = 'UNAVAILABLE';
        $chainBatch = null;
        $chainHistory = [];
        $chainTransfers = [];
        $productIntegrity = 'UNVERIFIED';

        try {
            $chainBatch = $this->signer->getBatchState($batchIdBytes32);
            $historyResponse = $this->signer->getBatchHistory($batchIdBytes32);
            $chainHistory = $historyResponse['events'] ?? [];

            $chainTransferReadStatus = 'AVAILABLE';
            foreach (($chainBatch['transferIds'] ?? []) as $transferId) {
                $transferResponse = $this->signer->getTransferState((string) $transferId);
                if (! empty($transferResponse['transfer'])) {
                    $chainTransfers[] = $transferResponse['transfer'];
                }
            }

            $approval = $this->signer->isProductApproved($productIdBytes32);
            $productPayload = [
                'maSanPham' => (string) $lo->maSanPham,
                'tenSanPham' => (string) $lo->tenSanPham,
                'soDangKy' => (string) $lo->soDangKy,
                'thanhPhan' => $lo->hoatChat,
                'hamLuong' => $lo->hamLuong,
                'dangBaoChe' => $lo->dangBaoChe,
                'quyCachDongGoi' => $lo->quyCachDongGoi,
                'nhaSanXuatId' => (int) $lo->nhaSanXuatId,
            ];
            $productPayloadJson = json_encode($productPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
            $expectedProductHash = $productPayloadJson === false ? null : '0x' . hash('sha256', $productPayloadJson);
            $productIntegrity = empty($approval['isApproved'])
                ? 'NOT_APPROVED_ON_CHAIN'
                : (strtolower((string) ($approval['payloadHash'] ?? '')) === strtolower((string) $expectedProductHash)
                    ? 'MATCH'
                    : 'MISMATCH');
            $chainReadStatus = 'AVAILABLE';
        } catch (\\Throwable $exception) {
            report($exception);
        }

        $chainRecalled = ! empty($chainBatch['batch']['recalled']);
        $batchExistsOnChain = ! empty($chainBatch['batch']['exists']);
        $ngayHetHan = (string) $lo->hanSuDung;
        $status = ($lo->trangThai === 'RECALLED' || $chainRecalled)
            ? 'RECALLED'
            : ($chainReadStatus !== 'AVAILABLE'
                ? 'UNAVAILABLE'
                : (! $batchExistsOnChain
                    ? 'NOT_REGISTERED_ON_CHAIN'
                    : ($ngayHetHan < today()->toDateString() ? 'EXPIRED' : ($lo->trangThaiSanPham === 'DA_DUYET' && $productIntegrity === 'MATCH' ? 'VALID' : 'INVALID'))));

        $chainSteps = array_map(function (array $event): array {
            $args = $event['args'] ?? [];
            $eventName = (string) ($event['eventName'] ?? 'BlockchainEvent');
            $timestamp = (int) ($event['timestamp'] ?? 0);
            $details = match ($eventName) {
                'BatchRegistered' => 'Ví nhà sản xuất: ' . ($args['manufacturer'] ?? '—'),
                'BatchEventRecorded' => 'Ví thực hiện: ' . ($args['actor'] ?? '—') . ' · eventType: ' . ($args['eventType'] ?? '—'),
                'BatchOwnerChanged' => ($args['oldOwner'] ?? '—') . ' → ' . ($args['newOwner'] ?? '—'),
                'TransferSubmitted' => ($args['sender'] ?? '—') . ' → ' . ($args['receiver'] ?? '—'),
                'TransferReceived' => 'Bên nhận: ' . ($args['receiver'] ?? '—'),
                'TransferRejected' => 'Bên nhận: ' . ($args['receiver'] ?? '—') . ' · reasonHash: ' . ($args['reasonHash'] ?? '—'),
                'TransferCancelled' => 'Bên gửi: ' . ($args['sender'] ?? '—'),
                'BatchRecalled' => 'Ví thu hồi: ' . ($args['actor'] ?? '—'),
                default => 'Sự kiện được ghi nhận trên Ethereum Sepolia',
            };
            return [
                'tieuDe' => $eventName,
                'donVi' => $details,
                'thoiGian' => $timestamp > 0 ? \\Illuminate\\Support\\Carbon::createFromTimestamp($timestamp)->toDateTimeString() : null,
                'timestamp' => $timestamp,
                'canhBao' => $eventName === 'BatchRecalled',
                'txHash' => $event['txHash'] ?? null,
                'blockNumber' => $event['blockNumber'] ?? null,
            ];
        }, $chainHistory);

        $hanhTrinh = [...$steps, ...$transferSteps, ...$chainSteps];
        usort($hanhTrinh, function (array $a, array $b): int {
            $ta = isset($a['timestamp']) ? (int) $a['timestamp'] : (isset($a['thoiGian']) ? strtotime((string) $a['thoiGian']) : 0);
            $tb = isset($b['timestamp']) ? (int) $b['timestamp'] : (isset($b['thoiGian']) ? strtotime((string) $b['thoiGian']) : 0);
            return $ta <=> $tb;
        });

        return [
            'trangThai' => $status,
            'loai' => $unit ? 'MÃ ĐƠN VỊ HỘP LẺ' : 'MÃ LÔ THUỐC',
            'tenThuoc' => $lo->tenSanPham.($unit ? ' · '.$unit->maDonVi : ''),
            'hamLuongDangBaoChe' => $lo->quyCachDongGoi ?: '—',
            'hoatChat' => $lo->hoatChat ?: '—',
            'soDangKy' => $lo->soDangKy,
            'nhaSanXuat' => $lo->tenToChuc,
            'maLo' => $lo->maLoNghiepVu,
            'ngaySanXuat' => $lo->ngaySanXuat,
            'hanSuDung' => $ngayHetHan,
            'batchIdHash' => $batchIdBytes32,
            'txHash' => ($unitTxHash ?: $batchTxHash) ?: 'Chưa có giao dịch blockchain',
            'productIntegrity' => $productIntegrity,
            'chainReadStatus' => $chainReadStatus,
            'batchExistsOnChain' => $batchExistsOnChain,
            'chainOwner' => $chainBatch['batch']['currentOwner'] ?? null,
            'chainRecalled' => $chainRecalled,
            'chainTransferIds' => $chainBatch['transferIds'] ?? [],
            'chainTransfers' => $chainTransfers,
            'chainTransferReadStatus' => $chainTransferReadStatus,
            'chainHistory' => $chainHistory,
            'isDispensed' => ! $unit ? 'N/A (cấp lô)' : ($unit->trangThaiDonVi === 'DISPENSED' ? 'ĐÃ BÁN' : 'CHƯA BÁN'),
            'lyDoThuHoi' => $thuHoi?->lyDo,
            'loThuocId' => (int) $lo->id,
            'hanhTrinh' => $hanhTrinh,
        ];
    }
}

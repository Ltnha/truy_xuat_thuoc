<?php

namespace App\Services\ChuoiCungUng;

use App\Enums\BatchStatus;
use App\Enums\OrganizationType;
use App\Exceptions\NghiepVuException;
use App\Jobs\ProcessBatchSaleEventJob;
use App\Models\BanLe;
use App\Models\BlockchainIdentity;
use App\Models\BlockchainTransaction;
use App\Models\ChiTietBanLe;
use App\Models\ChuyenGiao;
use App\Models\LoThuoc;
use App\Models\NhatKyHeThong;
use App\Models\TaiKhoan;
use App\Models\ToChuc;
use App\Models\TonKhoLo;
use App\Models\ThuHoiLoHang;
use App\Services\Blockchain\SignerClient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BanLeService
{
    public function __construct(
        private TonKhoService $tonKhoService,
        private SignerClient $signer,
    ) {}

    public function danhSachTonKho(ToChuc $nhaThuoc, ?string $tuKhoa = null): Collection
    {
        return DB::table('tonKhoLo as tk')
            ->join('loThuoc as lt', 'lt.id', '=', 'tk.loThuocId')
            ->join('sanPham as sp', 'sp.id', '=', 'lt.sanPhamId')
            ->leftJoin('thuHoiLoHang as th', 'th.loThuocId', '=', 'lt.id')
            ->where('tk.toChucId', $nhaThuoc->id)
            ->where('tk.soLuong', '>', 0)
            ->when($tuKhoa, function ($query) use ($tuKhoa) {
                $query->where(function ($sub) use ($tuKhoa) {
                    $sub->where('lt.maLoNghiepVu', 'like', "%{$tuKhoa}%")
                        ->orWhere('sp.tenSanPham', 'like', "%{$tuKhoa}%");
                });
            })
            ->select(
                'lt.id as loThuocId', 'lt.maLoNghiepVu', 'lt.maLo', 'lt.hanSuDung',
                'lt.trangThai as trangThaiLo', 'sp.tenSanPham', 'sp.soDangKy',
                'sp.hamLuong', 'tk.soLuong', 'th.id as thuHoiId'
            )
            ->orderBy('lt.hanSuDung')
            ->get();
    }

    public function traCuuLo(string $maDinhDanh, ToChuc $nhaThuoc): array
    {
        $maDinhDanh = trim($maDinhDanh);
        $loId = DB::table('qrCode')->where('giaTriQR', $maDinhDanh)->where('trangThai', 'ACTIVE')->value('loThuocId');
        $loId = $loId ?: DB::table('loThuoc')
            ->where('maLoNghiepVu', $maDinhDanh)
            ->orWhere('maLo', $maDinhDanh)
            ->value('id');

        if (! $loId) {
            throw new NghiepVuException('Không tìm thấy mã QR hoặc mã lô thuốc trong hệ thống.');
        }

        $lo = LoThuoc::with('sanPham')->whereKey($loId)->firstOrFail();
        $tonKho = TonKhoLo::query()->where('loThuocId', $lo->id)
            ->where('toChucId', $nhaThuoc->id)->first();

        if (! $tonKho) {
            throw new NghiepVuException('Lô thuốc hiện không thuộc quyền sở hữu hoặc không nằm trong kho của nhà thuốc này.');
        }

        $kiemTra = $this->kiemTraDieuKienOnChain($lo, $nhaThuoc);
        $chainHistory = [];
        try {
            $batchIdForHistory = '0x' . hash('sha256', 'BATCH_' . (string) $lo->maLo);
            $chainHistory = $this->signer->getBatchHistory($batchIdForHistory)['events'] ?? [];
        } catch (\Throwable $exception) {
            // Nếu RPC không đọc được lịch sử log, timeline DB vẫn hiển thị các txHash đã xác nhận.
        }
        $trangThaiLo = $lo->trangThai instanceof BatchStatus ? $lo->trangThai->value : (string) $lo->trangThai;
        $biThuHoiDb = $trangThaiLo === 'RECALLED'
            || ThuHoiLoHang::query()->where('loThuocId', $lo->id)->exists();
        $hetHan = $lo->hanSuDung?->toDateString() < today()->toDateString();
        $dangChuyen = ChuyenGiao::query()->where('loThuocId', $lo->id)
            ->where('trangThai', 'PENDING')->exists();

        $lyDo = [];
        if ($biThuHoiDb || $kiemTra['recalled']) $lyDo[] = 'Lô thuốc đã bị thu hồi.';
        if ($hetHan) $lyDo[] = 'Lô thuốc đã hết hạn sử dụng.';
        if ($lo->sanPham?->trangThaiDuyet !== 'DA_DUYET') $lyDo[] = 'Sản phẩm chưa được Cơ quan quản lý duyệt trong hệ thống.';
        if (! $kiemTra['exists']) $lyDo[] = 'Lô chưa được đăng ký thành công trên Blockchain.';
        if (! $kiemTra['ownerMatches']) $lyDo[] = 'Ví nhà thuốc không phải chủ sở hữu hiện tại trên Blockchain.';
        if (! $kiemTra['productIntegrity']) $lyDo[] = 'Hồ sơ sản phẩm không khớp dữ liệu đã được duyệt trên Blockchain.';
        if ($dangChuyen) $lyDo[] = 'Lô đang có giao dịch chuyển giao chờ xử lý.';

        return [
            'loThuocId' => (int) $lo->id,
            'maLo' => $lo->maLoNghiepVu,
            'maLoId' => $lo->maLo,
            'tenSanPham' => $lo->sanPham?->tenSanPham,
            'soDangKy' => $lo->sanPham?->soDangKy,
            'hamLuong' => $lo->sanPham?->hamLuong,
            'ngaySanXuat' => (string) $lo->ngaySanXuat,
            'hanSuDung' => (string) $lo->hanSuDung,
            'soLuongTon' => (int) $tonKho->soLuong,
            'qrCode' => DB::table('qrCode')->where('loThuocId', $lo->id)->value('giaTriQR'),
            'blockchain' => [
                'batchExists' => $kiemTra['exists'],
                'recalled' => $kiemTra['recalled'] || $biThuHoiDb,
                'currentOwner' => $kiemTra['currentOwner'],
                'ownerMatches' => $kiemTra['ownerMatches'],
                'productIntegrity' => $kiemTra['productIntegrity'],
                'registerTxHash' => $kiemTra['registerTxHash'],
            ],
            'canSell' => count($lyDo) === 0 && (int) $tonKho->soLuong > 0,
            'lyDoKhongTheBan' => $lyDo,
            'timeline' => $this->timeline($lo, $chainHistory),
        ];
    }

    public function banLe(array $items, ToChuc $nhaThuoc, TaiKhoan $taiKhoan, ?string $ghiChu = null): BanLe
    {
        if ($nhaThuoc->loaiToChuc !== OrganizationType::nhaThuoc) {
            throw new NghiepVuException('Chỉ nhà thuốc được phép lập hóa đơn bán lẻ.');
        }

        $result = DB::transaction(function () use ($items, $nhaThuoc, $taiKhoan, $ghiChu): array {
            $prepared = [];
            $tongTien = 0.0;
            $sortedItems = collect($items)->sortBy(fn ($item) => (int) $item['loThuocId']);

            foreach ($sortedItems as $item) {
                $lo = LoThuoc::with('sanPham')->whereKey((int) $item['loThuocId'])->lockForUpdate()->firstOrFail();
                $tonKho = TonKhoLo::query()->where('loThuocId', $lo->id)
                    ->where('toChucId', $nhaThuoc->id)->lockForUpdate()->first();

                $soLuong = (int) $item['soLuong'];
                $donGia = round((float) $item['donGia'], 2);
                if (! $tonKho || $soLuong < 1 || $soLuong > (int) $tonKho->soLuong) {
                    throw new NghiepVuException("Tồn kho lô {$lo->maLoNghiepVu} không đủ số lượng yêu cầu.");
                }
                if (ChuyenGiao::query()->where('loThuocId', $lo->id)->where('trangThai', 'PENDING')->exists()) {
                    throw new NghiepVuException("Lô {$lo->maLoNghiepVu} đang chờ chuyển giao, không thể bán.");
                }

                $this->assertDuDieuKienBan($lo, $nhaThuoc);
                $thanhTien = round($soLuong * $donGia, 2);
                $tongTien += $thanhTien;
                $prepared[] = compact('lo', 'tonKho', 'soLuong', 'donGia', 'thanhTien');
            }

            $maHoaDon = (string) Str::uuid();
            $banLe = BanLe::query()->create([
                'maHoaDon' => $maHoaDon,
                'toChucId' => $nhaThuoc->id,
                'taiKhoanId' => $taiKhoan->id,
                'tongTien' => round($tongTien, 2),
                'trangThai' => 'DA_HOAN_TAT',
                'ngayBan' => now(),
                'ghiChu' => $ghiChu,
            ]);

            $transactionIds = [];
            foreach ($prepared as $row) {
                $chiTiet = ChiTietBanLe::query()->create([
                    'banLeId' => $banLe->id,
                    'loThuocId' => $row['lo']->id,
                    'soLuong' => $row['soLuong'],
                    'donGia' => $row['donGia'],
                    'thanhTien' => $row['thanhTien'],
                ]);

                $row['tonKho']->decrement('soLuong', $row['soLuong']);
                $row['tonKho']->update(['ngayCapNhat' => now()]);

                $tx = BlockchainTransaction::query()->create([
                    'txHash' => null,
                    'blockNumber' => null,
                    'eventName' => 'SOLD',
                    'doiTuongToChucId' => $nhaThuoc->id,
                    'businessId' => $maHoaDon,
                    'payloadHash' => null,
                    'thoiGian' => now(),
                    'network' => config('truyXuat.blockchain.mang', 'Ethereum Sepolia Testnet'),
                    'trangThai' => 'PENDING',
                    'soLanThu' => 0,
                    'toChucId' => $nhaThuoc->id,
                    'sanPhamId' => $row['lo']->sanPhamId,
                    'loThuocId' => $row['lo']->id,
                    'banLeId' => $banLe->id,
                    'chiTietBanLeId' => $chiTiet->id,
                ]);
                $transactionIds[] = $tx->id;
            }

            NhatKyHeThong::query()->create([
                'taiKhoanId' => $taiKhoan->id,
                'doiTuongId' => $banLe->id,
                'hanhDong' => 'BAN_LE_THEO_LO',
                'ketQua' => 'THANH_CONG',
                'thoiGian' => now(),
                'chiTiet' => "Lập hóa đơn {$maHoaDon}, tổng tiền {$banLe->tongTien}.",
            ]);

            return ['banLeId' => $banLe->id, 'transactionIds' => $transactionIds];
        }, 3);

        foreach ($result['transactionIds'] as $transactionId) {
            ProcessBatchSaleEventJob::dispatch($transactionId)->afterCommit();
        }

        return BanLe::with(['chiTietBanLes.loThuoc.sanPham', 'blockchainTransactions'])
            ->findOrFail($result['banLeId']);
    }

    public function guiLaiDongBo(BanLe $banLe): int
    {
        $count = 0;
        foreach ($banLe->blockchainTransactions as $transaction) {
            if (in_array(strtoupper((string) $transaction->trangThai), ['PENDING', 'FAILED'], true)) {
                ProcessBatchSaleEventJob::dispatch($transaction->id)->afterCommit();
                $count++;
            }
        }
        return $count;
    }

    private function assertDuDieuKienBan(LoThuoc $lo, ToChuc $nhaThuoc): void
    {
        $status = $lo->trangThai instanceof BatchStatus ? $lo->trangThai->value : (string) $lo->trangThai;
        if ($lo->sanPham?->trangThaiDuyet !== 'DA_DUYET') {
            throw new NghiepVuException('Sản phẩm chưa được Cơ quan quản lý duyệt trong hệ thống.');
        }
        if ($status === 'RECALLED' || ThuHoiLoHang::query()->where('loThuocId', $lo->id)->exists()) {
            throw new NghiepVuException("Lô {$lo->maLoNghiepVu} đã bị thu hồi.");
        }
        if ($lo->hanSuDung?->toDateString() < today()->toDateString()) {
            throw new NghiepVuException("Lô {$lo->maLoNghiepVu} đã hết hạn sử dụng.");
        }

        $kiemTra = $this->kiemTraDieuKienOnChain($lo, $nhaThuoc);
        if (! $kiemTra['exists']) throw new NghiepVuException('Lô chưa được đăng ký trên Blockchain.');
        if ($kiemTra['recalled']) throw new NghiepVuException('Lô đã bị thu hồi trên Blockchain.');
        if (! $kiemTra['ownerMatches']) throw new NghiepVuException('Ví nhà thuốc không phải chủ sở hữu hiện tại trên Blockchain.');
        if (! $kiemTra['productIntegrity']) throw new NghiepVuException('Hồ sơ sản phẩm không khớp dữ liệu đã duyệt trên Blockchain.');
    }

    private function kiemTraDieuKienOnChain(LoThuoc $lo, ToChuc $nhaThuoc): array
    {
        try {
            $identity = BlockchainIdentity::query()->where('toChucId', $nhaThuoc->id)
                ->where('trangThai', 'ACTIVE')->whereNotNull('diaChiVi')->first();
            if (! $identity) throw new NghiepVuException('Nhà thuốc chưa có ví Blockchain ACTIVE.');

            $batchId = '0x' . hash('sha256', 'BATCH_' . (string) $lo->maLo);
            $batch = $this->signer->getBatchState($batchId);
            $approval = $this->signer->isProductApproved('0x' . hash('sha256', 'PRODUCT_' . (string) $lo->sanPham?->maSanPham));

            $payload = [
                'maSanPham' => (string) $lo->sanPham?->maSanPham,
                'tenSanPham' => (string) $lo->sanPham?->tenSanPham,
                'soDangKy' => (string) $lo->sanPham?->soDangKy,
                'thanhPhan' => $lo->sanPham?->thanhPhan,
                'hamLuong' => $lo->sanPham?->hamLuong,
                'dangBaoChe' => $lo->sanPham?->dangBaoChe,
                'quyCachDongGoi' => $lo->sanPham?->quyCachDongGoi,
                'nhaSanXuatId' => (int) $lo->sanPham?->nhaSanXuatId,
            ];
            $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
            $expectedHash = $json === false ? null : '0x' . hash('sha256', $json);
            $actualHash = strtolower((string) ($approval['payloadHash'] ?? ''));

            return [
                'exists' => ! empty($batch['batch']['exists']),
                'recalled' => ! empty($batch['batch']['recalled']),
                'currentOwner' => $batch['batch']['currentOwner'] ?? null,
                'ownerMatches' => ! empty($batch['batch']['exists'])
                    && strtolower((string) ($batch['batch']['currentOwner'] ?? '')) === strtolower((string) $identity->diaChiVi),
                'productIntegrity' => ! empty($approval['isApproved'])
                    && $expectedHash !== null
                    && $actualHash === strtolower($expectedHash)
                    && (empty($lo->sanPham?->hashHoSo) || strtolower((string) $lo->sanPham->hashHoSo) === strtolower($expectedHash)),
                'registerTxHash' => $batch['registerTxHash'] ?? null,
            ];
        } catch (\Throwable $exception) {
            if ($exception instanceof NghiepVuException) throw $exception;
            throw new NghiepVuException('Không thể xác minh trạng thái lô trên Sepolia. Vui lòng thử lại: ' . $exception->getMessage());
        }
    }

    private function timeline(LoThuoc $lo, array $chainHistory = []): array
    {
        $transfers = DB::table('chuyenGiao as cg')
            ->join('toChuc as bg', 'bg.id', '=', 'cg.benGuiId')
            ->join('toChuc as bn', 'bn.id', '=', 'cg.benNhanId')
            ->where('cg.loThuocId', $lo->id)
            ->orderBy('cg.id')
            ->get(['cg.maChuyenGiao', 'cg.trangThai', 'cg.soLuong', 'cg.thoiGianKhoiTao', 'cg.thoiGianXacNhan', 'bg.tenToChuc as benGui', 'bn.tenToChuc as benNhan'])
            ->map(fn ($row) => [
                'suKien' => 'CHUYEN_GIAO_' . $row->trangThai,
                'moTa' => $row->benGui . ' → ' . $row->benNhan . ' · ' . $row->soLuong . ' đơn vị',
                'thoiGian' => $row->thoiGianXacNhan ?: $row->thoiGianKhoiTao,
            ])->all();

        $sales = DB::table('chiTietBanLe as ct')
            ->join('banLe as bl', 'bl.id', '=', 'ct.banLeId')
            ->where('ct.loThuocId', $lo->id)
            ->where('bl.trangThai', 'DA_HOAN_TAT')
            ->orderBy('bl.ngayBan')
            ->get(['bl.maHoaDon', 'bl.ngayBan', 'ct.soLuong'])
            ->map(fn ($row) => [
                'suKien' => 'SOLD',
                'moTa' => "Hóa đơn {$row->maHoaDon}: bán {$row->soLuong} đơn vị.",
                'thoiGian' => $row->ngayBan,
            ])->all();

        $chain = array_map(fn (array $event) => [
            'suKien' => (string) ($event['eventName'] ?? 'BLOCKCHAIN_EVENT'),
            'moTa' => 'Đã ghi nhận trên Ethereum Sepolia · TxHash: ' . (string) ($event['txHash'] ?? 'đang đối soát'),
            'thoiGian' => ! empty($event['timestamp']) ? date('Y-m-d H:i:s', (int) $event['timestamp']) : null,
            'txHash' => $event['txHash'] ?? null,
            'blockNumber' => $event['blockNumber'] ?? null,
        ], $chainHistory);

        $databaseProofs = DB::table('blockchainTransaction')
            ->where('loThuocId', $lo->id)
            ->where('trangThai', 'CONFIRMED')
            ->whereNotNull('txHash')
            ->orderBy('thoiGian')
            ->get(['eventName', 'txHash', 'blockNumber', 'thoiGian'])
            ->map(fn ($tx) => [
                'suKien' => $tx->eventName,
                'moTa' => 'Giao dịch đã xác nhận · TxHash: ' . $tx->txHash,
                'thoiGian' => $tx->thoiGian,
                'txHash' => $tx->txHash,
                'blockNumber' => $tx->blockNumber,
            ])->all();

        return [...$transfers, ...$sales, ...$databaseProofs, ...$chain];
    }
}

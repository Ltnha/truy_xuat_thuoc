<?php

namespace App\Services\ChuoiCungUng;

use App\Enums\BatchStatus;
use App\Enums\OrganizationApprovalStatus;
use App\Enums\OrganizationType;
use App\Enums\TransferStatus;
use App\Exceptions\NghiepVuException;
use App\Jobs\ProcessBatchTransferJob;
use App\Models\BlockchainIdentity;
use App\Models\BlockchainTransaction;
use App\Models\ChuyenGiao;
use App\Models\LoThuoc;
use App\Models\NhatKyHeThong;
use App\Models\TaiKhoan;
use App\Models\ToChuc;
use App\Models\TonKhoLo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class ChuyenGiaoService
{
    public function __construct(
        private TonKhoService $tonKhoService,
    ) {
    }

    /**
     * Tạo chuyển giao duy nhất dùng chung cho hai luồng hợp lệ:
     * NSX -> NPP và NPP -> Nhà thuốc. Tồn kho được khóa trong transaction
     * để tránh hai yêu cầu đồng thời cùng giữ vượt số lượng.
     */
    public function tao(ToChuc $benGui, array $duLieu, TaiKhoan $taiKhoan, ?string $ip = null): ChuyenGiao
    {
        [$chuyenGiao, $blockchainTransaction] = DB::transaction(function () use ($benGui, $duLieu, $taiKhoan, $ip): array {
            $loThuoc = LoThuoc::query()->whereKey($duLieu['loThuocId'])->lockForUpdate()->firstOrFail();
            $benNhan = ToChuc::query()->whereKey($duLieu['benNhanId'])->firstOrFail();

            $duyetNhan = $benNhan->trangThaiDuyet instanceof OrganizationApprovalStatus
                ? $benNhan->trangThaiDuyet
                : OrganizationApprovalStatus::tryFrom((string) $benNhan->trangThaiDuyet);
            if ($benGui->id === $benNhan->id || $duyetNhan !== OrganizationApprovalStatus::daDuyet) {
                throw new NghiepVuException('Đơn vị nhận không hợp lệ hoặc chưa được phê duyệt.');
            }

            $loaiGui = $benGui->loaiToChuc instanceof OrganizationType ? $benGui->loaiToChuc : OrganizationType::tryFrom((string) $benGui->loaiToChuc);
            $loaiNhan = $benNhan->loaiToChuc instanceof OrganizationType ? $benNhan->loaiToChuc : OrganizationType::tryFrom((string) $benNhan->loaiToChuc);
            $hopLe = ($loaiGui === OrganizationType::nhaSanXuat && $loaiNhan === OrganizationType::nhaPhanPhoi)
                || ($loaiGui === OrganizationType::nhaPhanPhoi && $loaiNhan === OrganizationType::nhaThuoc);

            if (! $hopLe) {
                throw new NghiepVuException('Chỉ cho phép chuyển giao theo luồng Nhà sản xuất → Nhà phân phối → Nhà thuốc.');
            }

            // Cả hai tổ chức cần có ví ACTIVE để submit/receive được ký bằng đúng chủ thể.
            // Không fallback sang ví Admin vì sẽ làm sai actor/benNhan trên contract.
            foreach ([$benGui->id, $benNhan->id] as $toChucIdCanVi) {
                $identity = BlockchainIdentity::query()
                    ->where('toChucId', $toChucIdCanVi)
                    ->where('trangThai', 'ACTIVE')
                    ->whereNotNull('signerKeyId')
                    ->whereNotNull('diaChiVi')
                    ->first();
                if (! $identity || trim((string) $identity->signerKeyId) === '' || trim((string) $identity->diaChiVi) === '') {
                    throw new NghiepVuException("Tổ chức #{$toChucIdCanVi} chưa có ví Blockchain ACTIVE đầy đủ. Không thể tạo chuyển giao on-chain.");
                }
            }

            if ($loaiGui === OrganizationType::nhaSanXuat && (int) $loThuoc->toChucId !== (int) $benGui->id) {
                throw new NghiepVuException('Lô thuốc không thuộc nhà sản xuất đang đăng nhập.');
            }

            $trangThaiLo = $loThuoc->trangThai instanceof BatchStatus
                ? $loThuoc->trangThai
                : BatchStatus::tryFrom((string) $loThuoc->trangThai);
            $hanSuDung = $loThuoc->hanSuDung ? Carbon::parse($loThuoc->hanSuDung)->startOfDay() : null;
            if ($trangThaiLo === BatchStatus::recalled || ($hanSuDung && $hanSuDung->lt(today()))) {
                throw new NghiepVuException('Không thể chuyển giao lô thuốc đã thu hồi hoặc hết hạn.');
            }

            $tonKho = TonKhoLo::query()
                ->where('loThuocId', $loThuoc->id)
                ->where('toChucId', $benGui->id)
                ->lockForUpdate()
                ->first();

            // Tương thích các lô NSX cũ được tạo trước khi hệ thống khởi tạo tonKhoLo.
            // Chỉ seed tồn ban đầu khi chưa từng có chuyển giao để không cộng lại hàng đã xuất.
            if (! $tonKho && $loaiGui === OrganizationType::nhaSanXuat) {
                $daTungChuyen = ChuyenGiao::query()->where('loThuocId', $loThuoc->id)->exists();
                if (! $daTungChuyen) {
                    DB::table('tonKhoLo')->insertOrIgnore([
                        'loThuocId' => $loThuoc->id,
                        'toChucId' => $benGui->id,
                        'soLuong' => (int) $loThuoc->soLuong,
                        'ngayCapNhat' => now(),
                    ]);
                    $tonKho = TonKhoLo::query()
                        ->where('loThuocId', $loThuoc->id)
                        ->where('toChucId', $benGui->id)
                        ->lockForUpdate()
                        ->first();
                }
            }

            $soLuong = (int) ($duLieu['soLuong'] ?? 0);
            $khaDung = $this->tonKhoService->soLuongKhaDung((int) $loThuoc->id, (int) $benGui->id);

            if (! $tonKho || $soLuong < 1 || $soLuong > $khaDung) {
                throw new NghiepVuException("Số lượng chuyển không hợp lệ hoặc vượt tồn kho khả dụng ({$khaDung}).");
            }

            $maChuyenGiao = (string) Str::uuid();
            $cg = ChuyenGiao::query()->create([
                'maChuyenGiao' => $maChuyenGiao,
                'loThuocId' => $loThuoc->id,
                'benGuiId' => $benGui->id,
                'benNhanId' => $benNhan->id,
                'soLuong' => $soLuong,
                'thoiGianKhoiTao' => now(),
                'trangThai' => TransferStatus::pending,
            ]);

            // Chưa có txHash cho tới khi Signer gửi giao dịch thật lên Sepolia.
            // Cột txHash phải nullable; tuyệt đối không ghi mã PENDING giả vào đây.
            $tx = BlockchainTransaction::query()->create([
                'txHash' => null,
                'blockNumber' => null,
                'eventName' => 'TransferSubmitted',
                'doiTuongToChucId' => $benGui->id,
                'businessId' => $maChuyenGiao,
                'network' => config('truyXuat.blockchain.mang', 'Ethereum Sepolia'),
                'trangThai' => 'PENDING',
                'toChucId' => $benGui->id,
                'sanPhamId' => $loThuoc->sanPhamId,
                'loThuocId' => $loThuoc->id,
                'chuyenGiaoId' => $cg->id,
                'thoiGian' => now(),
            ]);

            $trangThaiLoHienTai = $loThuoc->trangThai instanceof BatchStatus
                ? $loThuoc->trangThai
                : BatchStatus::tryFrom((string) $loThuoc->trangThai);
            if ($loaiGui === OrganizationType::nhaSanXuat && $trangThaiLoHienTai === BatchStatus::created) {
                $loThuoc->update(['trangThai' => BatchStatus::inTransit->value]);
            }

            NhatKyHeThong::query()->create([
                'taiKhoanId' => $taiKhoan->id,
                'doiTuongId' => $cg->id,
                'hanhDong' => 'KHOI_TAO_CHUYEN_GIAO',
                'ketQua' => 'THANH_CONG',
                'thoiGian' => now(),
                'chiTiet' => "{$benGui->tenToChuc} chuyển {$soLuong} đơn vị lô [{$loThuoc->maLoNghiepVu}] đến {$benNhan->tenToChuc}. Chờ ghi nhận Sepolia." . ($ip ? " IP: {$ip}" : ''),
            ]);

            return [$cg, $tx];
        });

        // Chỉ dispatch sau khi dữ liệu nghiệp vụ và tx PENDING đã commit.
        ProcessBatchTransferJob::dispatch(
            $chuyenGiao->id,
            $taiKhoan->id,
            $blockchainTransaction->id,
            'TransferSubmitted',
            'TRANSFER_BATCH'
        )->afterCommit();

        return $chuyenGiao;
    }

    public function nhan(ChuyenGiao $chuyenGiao, ToChuc $benNhan, TaiKhoan $taiKhoan, ?string $ip): void
    {
        $blockchainTransaction = DB::transaction(function () use ($chuyenGiao, $benNhan, $taiKhoan, $ip): BlockchainTransaction {
            $chuyenGiao = ChuyenGiao::query()->whereKey($chuyenGiao->id)->lockForUpdate()->firstOrFail();
            if ((int) $chuyenGiao->benNhanId !== (int) $benNhan->id || $chuyenGiao->trangThai !== TransferStatus::pending) {
                throw new NghiepVuException('Giao dịch không thuộc đơn vị nhận hoặc không còn chờ tiếp nhận.');
            }

            $dangXuLyNhan = BlockchainTransaction::query()
                ->where('chuyenGiaoId', $chuyenGiao->id)
                ->where('eventName', 'TransferReceived')
                ->where('trangThai', 'PENDING')
                ->exists();
            if ($dangXuLyNhan) {
                throw new NghiepVuException('Yêu cầu tiếp nhận đang được xử lý trên blockchain.');
            }

            $loThuoc = LoThuoc::query()->whereKey($chuyenGiao->loThuocId)->lockForUpdate()->firstOrFail();
            $trangThaiLo = $loThuoc->trangThai instanceof BatchStatus ? $loThuoc->trangThai : BatchStatus::tryFrom((string) $loThuoc->trangThai);
            if ($trangThaiLo === BatchStatus::recalled || ($loThuoc->hanSuDung && Carbon::parse($loThuoc->hanSuDung)->startOfDay()->lt(today()))) {
                throw new NghiepVuException('Không thể tiếp nhận lô thuốc đã thu hồi hoặc hết hạn.');
            }

            // Chỉ tạo yêu cầu blockchain. Job sẽ chuyển kho và đổi trạng thái
            // sau khi receipt TransferReceived được xác nhận trên Sepolia.
            $tx = $this->taoGiaoDichBlockchain($chuyenGiao, $benNhan, $loThuoc, 'TransferReceived', 'TRANSFER_RECEIVED');

            NhatKyHeThong::query()->create([
                'taiKhoanId' => $taiKhoan->id,
                'doiTuongId' => $chuyenGiao->id,
                'hanhDong' => 'TIEP_NHAN_CHUYEN_GIAO',
                'ketQua' => 'THANH_CONG',
                'thoiGian' => now(),
                'chiTiet' => "{$benNhan->tenToChuc} tiếp nhận {$chuyenGiao->soLuong} đơn vị lô [{$loThuoc->maLoNghiepVu}]." . ($ip ? " IP: {$ip}" : ''),
            ]);
            return $tx;
        });

        ProcessBatchTransferJob::dispatch($chuyenGiao->id, $taiKhoan->id, $blockchainTransaction->id, 'TransferReceived', 'TRANSFER_RECEIVED')->afterCommit();
    }

    public function tuChoi(ChuyenGiao $chuyenGiao, ToChuc $benNhan, TaiKhoan $taiKhoan, ?string $ip, ?string $lyDo = null): void
    {
        $blockchainTransaction = DB::transaction(function () use ($chuyenGiao, $benNhan, $taiKhoan, $ip, $lyDo): BlockchainTransaction {
            $chuyenGiao = ChuyenGiao::query()->whereKey($chuyenGiao->id)->lockForUpdate()->firstOrFail();
            if ((int) $chuyenGiao->benNhanId !== (int) $benNhan->id || $chuyenGiao->trangThai !== TransferStatus::pending) {
                throw new NghiepVuException('Giao dịch không thuộc đơn vị nhận hoặc không còn chờ tiếp nhận.');
            }

            $dangXuLyTuChoi = BlockchainTransaction::query()
                ->where('chuyenGiaoId', $chuyenGiao->id)
                ->where('eventName', 'TransferRejected')
                ->where('trangThai', 'PENDING')
                ->exists();
            if ($dangXuLyTuChoi) {
                throw new NghiepVuException('Yêu cầu từ chối đang được xử lý trên blockchain.');
            }

            $loThuoc = LoThuoc::query()->whereKey($chuyenGiao->loThuocId)->lockForUpdate()->firstOrFail();
            $lyDoTuChoi = trim((string) $lyDo) ?: 'Đơn vị nhận từ chối tiếp nhận lô hàng';
            // Chỉ lưu lý do và tạo yêu cầu. Giải phóng reservation khi job
            // xác nhận TransferRejected trên chain.
            $chuyenGiao->update(['lyDoTuChoi' => $lyDoTuChoi]);
            $tx = $this->taoGiaoDichBlockchain($chuyenGiao, $benNhan, $loThuoc, 'TransferRejected', 'TRANSFER_REJECTED');

            NhatKyHeThong::query()->create([
                'taiKhoanId' => $taiKhoan->id,
                'doiTuongId' => $chuyenGiao->id,
                'hanhDong' => 'TU_CHOI_CHUYEN_GIAO',
                'ketQua' => 'THANH_CONG',
                'thoiGian' => now(),
                'chiTiet' => "{$benNhan->tenToChuc} từ chối chuyển giao {$chuyenGiao->maChuyenGiao}. Lý do: {$lyDoTuChoi}." . ($ip ? " IP: {$ip}" : ''),
            ]);
            return $tx;
        });

        ProcessBatchTransferJob::dispatch($chuyenGiao->id, $taiKhoan->id, $blockchainTransaction->id, 'TransferRejected', 'TRANSFER_REJECTED')->afterCommit();
    }

    /**
     * Bên gửi hủy chuyển giao khi giao dịch vẫn đang chờ bên nhận.
     * Trạng thái chỉ chuyển CANCELLED sau receipt TransferCancelled.
     */
    public function huy(ChuyenGiao $chuyenGiao, ToChuc $benGui, TaiKhoan $taiKhoan, ?string $ip, ?string $lyDo = null): void
    {
        $blockchainTransaction = DB::transaction(function () use ($chuyenGiao, $benGui, $taiKhoan, $ip, $lyDo): BlockchainTransaction {
            $chuyenGiao = ChuyenGiao::query()->whereKey($chuyenGiao->id)->lockForUpdate()->firstOrFail();
            if ((int) $chuyenGiao->benGuiId !== (int) $benGui->id || $chuyenGiao->trangThai !== TransferStatus::pending) {
                throw new NghiepVuException('Chỉ bên gửi mới được hủy giao dịch đang chờ tiếp nhận.');
            }

            $dangXuLyHuy = BlockchainTransaction::query()
                ->where('chuyenGiaoId', $chuyenGiao->id)
                ->where('eventName', 'TransferCancelled')
                ->where('trangThai', 'PENDING')
                ->exists();
            if ($dangXuLyHuy) {
                throw new NghiepVuException('Yêu cầu hủy đang được xử lý trên blockchain.');
            }

            $loThuoc = LoThuoc::query()->whereKey($chuyenGiao->loThuocId)->lockForUpdate()->firstOrFail();
            $lyDoHuy = trim((string) $lyDo) ?: 'Bên gửi chủ động hủy chuyển giao';

            $chuyenGiao->update(['lyDoTuChoi' => $lyDoHuy]);
            $tx = $this->taoGiaoDichBlockchain($chuyenGiao, $benGui, $loThuoc, 'TransferCancelled', 'TRANSFER_CANCELLED');

            NhatKyHeThong::query()->create([
                'taiKhoanId' => $taiKhoan->id,
                'doiTuongId' => $chuyenGiao->id,
                'hanhDong' => 'HUY_CHUYEN_GIAO',
                'ketQua' => 'THANH_CONG',
                'thoiGian' => now(),
                'chiTiet' => "{$benGui->tenToChuc} gửi yêu cầu hủy {$chuyenGiao->maChuyenGiao}. Lý do: {$lyDoHuy}." . ($ip ? " IP: {$ip}" : ''),
            ]);
            return $tx;
        });

        ProcessBatchTransferJob::dispatch($chuyenGiao->id, $taiKhoan->id, $blockchainTransaction->id, 'TransferCancelled', 'TRANSFER_CANCELLED')->afterCommit();
    }

    private function taoGiaoDichBlockchain(
        ChuyenGiao $chuyenGiao,
        ToChuc $toChuc,
        LoThuoc $loThuoc,
        string $eventName,
        string $eventType
    ): BlockchainTransaction {
        return BlockchainTransaction::query()->create([
            'txHash' => null,
            'blockNumber' => null,
            'eventName' => $eventName,
            'doiTuongToChucId' => $toChuc->id,
            'businessId' => $chuyenGiao->maChuyenGiao,
            'network' => config('truyXuat.blockchain.mang', 'Ethereum Sepolia'),
            'trangThai' => 'PENDING',
            'toChucId' => $toChuc->id,
            'sanPhamId' => $loThuoc->sanPhamId,
            'loThuocId' => $loThuoc->id,
            'chuyenGiaoId' => $chuyenGiao->id,
            'thoiGian' => now(),
        ]);
    }
}

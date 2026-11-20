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
use App\Models\ThuHoiLoHang;
use App\Services\Blockchain\RoleMap;
use App\Services\Blockchain\SignerClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class ChuyenGiaoService
{
    public function __construct(
        private TonKhoService $tonKhoService,
        private SignerClient $signer,
    ) {
    }

    /**
     * Tạo chuyển giao duy nhất dùng chung cho hai luồng hợp lệ:
     * NSX -> NPP và NPP -> Nhà thuốc. Tồn kho được khóa trong transaction
     * để tránh hai yêu cầu đồng thời cùng giữ vượt số lượng.
     */
    /**
     * Tạo chuyển giao NSX -> NPP hoặc NPP -> Nhà thuốc.
     * Giao dịch submitTransfer phải được ký, mined và đối soát event trên Sepolia
     * trước khi tạo bất kỳ bản ghi chuyenGiao/blockchainTransaction nào.
     */
    public function tao(ToChuc $benGui, array $duLieu, TaiKhoan $taiKhoan, ?string $ip = null): ChuyenGiao
    {
        return DB::transaction(function () use ($benGui, $duLieu, $taiKhoan, $ip): ChuyenGiao {
            $loThuoc = LoThuoc::query()->whereKey($duLieu['loThuocId'])->lockForUpdate()->firstOrFail();
            $benNhan = ToChuc::query()->whereKey($duLieu['benNhanId'])->firstOrFail();

            $duyetNhan = $benNhan->trangThaiDuyet instanceof OrganizationApprovalStatus
                ? $benNhan->trangThaiDuyet
                : OrganizationApprovalStatus::tryFrom((string) $benNhan->trangThaiDuyet);
            if ((int) $benGui->id === (int) $benNhan->id || $duyetNhan !== OrganizationApprovalStatus::daDuyet) {
                throw new NghiepVuException('Nhà phân phối không hợp lệ hoặc chưa được cơ quan quản lý xét duyệt.');
            }

            $loaiGui = $benGui->loaiToChuc instanceof OrganizationType ? $benGui->loaiToChuc : OrganizationType::tryFrom((string) $benGui->loaiToChuc);
            $loaiNhan = $benNhan->loaiToChuc instanceof OrganizationType ? $benNhan->loaiToChuc : OrganizationType::tryFrom((string) $benNhan->loaiToChuc);
            $hopLe = ($loaiGui === OrganizationType::nhaSanXuat && $loaiNhan === OrganizationType::nhaPhanPhoi)
                || ($loaiGui === OrganizationType::nhaPhanPhoi && $loaiNhan === OrganizationType::nhaThuoc);
            if (! $hopLe) {
                throw new NghiepVuException('Chỉ cho phép chuyển giao theo luồng Nhà sản xuất → Nhà phân phối → Nhà thuốc.');
            }

            $identityGui = BlockchainIdentity::query()
                ->where('toChucId', $benGui->id)
                ->where('trangThai', 'ACTIVE')
                ->whereNotNull('signerKeyId')
                ->whereNotNull('diaChiVi')
                ->first();
            $identityNhan = BlockchainIdentity::query()
                ->where('toChucId', $benNhan->id)
                ->where('trangThai', 'ACTIVE')
                ->whereNotNull('signerKeyId')
                ->whereNotNull('diaChiVi')
                ->first();

            if (! $identityGui || ! $identityNhan
                || trim((string) $identityGui->signerKeyId) === ''
                || trim((string) $identityNhan->signerKeyId) === ''
                || ! preg_match('/^0x[a-fA-F0-9]{40}$/', (string) $identityGui->diaChiVi)
                || ! preg_match('/^0x[a-fA-F0-9]{40}$/', (string) $identityNhan->diaChiVi)) {
                throw new NghiepVuException('Nhà sản xuất hoặc nhà phân phối chưa có ví Blockchain ACTIVE hợp lệ.');
            }

            if (! $this->signer->hasRole(RoleMap::cho($benGui->loaiToChuc), (string) $identityGui->diaChiVi)) {
                throw new NghiepVuException('Ví nhà sản xuất chưa được cấp đúng role trên blockchain.');
            }
            if (! $this->signer->hasRole(RoleMap::cho($benNhan->loaiToChuc), (string) $identityNhan->diaChiVi)) {
                throw new NghiepVuException('Ví nhà phân phối chưa được cấp đúng role trên blockchain.');
            }

            if ($loaiGui === OrganizationType::nhaSanXuat && (int) $loThuoc->toChucId !== (int) $benGui->id) {
                throw new NghiepVuException('Lô thuốc không thuộc nhà sản xuất đang đăng nhập.');
            }

            $trangThaiLo = $loThuoc->trangThai instanceof BatchStatus
                ? $loThuoc->trangThai
                : BatchStatus::tryFrom((string) $loThuoc->trangThai);
            $coQuyetDinhThuHoi = ThuHoiLoHang::query()->where('loThuocId', $loThuoc->id)->exists();
            $hanSuDung = $loThuoc->hanSuDung ? Carbon::parse($loThuoc->hanSuDung)->startOfDay() : null;
            if ($coQuyetDinhThuHoi || $trangThaiLo === BatchStatus::recalled || ($hanSuDung && $hanSuDung->lt(today()))) {
                throw new NghiepVuException('Không thể chuyển giao lô thuốc đã thu hồi hoặc hết hạn.');
            }

            $coLenhChoOffchain = ChuyenGiao::query()
                ->where('loThuocId', $loThuoc->id)
                ->where('trangThai', TransferStatus::pending->value)
                ->exists();
            if ($coLenhChoOffchain) {
                throw new NghiepVuException('Lô thuốc đang có một lệnh chuyển giao chờ xử lý.');
            }

            $batchIdBytes32 = '0x' . hash('sha256', 'BATCH_' . (string) $loThuoc->maLo);
            $onChainBatch = $this->signer->getBatchState($batchIdBytes32);
            if (empty($onChainBatch['batch']['exists'])) {
                throw new NghiepVuException('Lô thuốc chưa được registerBatch thành công trên blockchain.');
            }
            if (! empty($onChainBatch['batch']['recalled'])) {
                throw new NghiepVuException('Lô thuốc đã bị thu hồi trên blockchain.');
            }

            $zeroBytes32 = '0x' . str_repeat('0', 64);
            if (strtolower((string) ($onChainBatch['pendingTransferId'] ?? $zeroBytes32)) !== $zeroBytes32) {
                throw new NghiepVuException('Lô thuốc đang có một lệnh chuyển giao PENDING trên blockchain.');
            }
            if (strtolower((string) ($onChainBatch['batch']['currentOwner'] ?? '')) !== strtolower((string) $identityGui->diaChiVi)) {
                throw new NghiepVuException('Ví nhà sản xuất không phải chủ sở hữu hiện tại của lô trên blockchain.');
            }

            $tonKho = TonKhoLo::query()
                ->where('loThuocId', $loThuoc->id)
                ->where('toChucId', $benGui->id)
                ->lockForUpdate()
                ->first();

            // Seed tồn kho cũ trong cùng DB transaction; nếu ký on-chain lỗi thì thay đổi này cũng rollback.
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

            // Contract hiện tại chuyển quyền sở hữu TOÀN BỘ batch, không có tham số quantity.
            // Không cho phép MySQL ghi một phần số lượng trong khi blockchain chuyển cả lô.
            if ($soLuong !== $khaDung) {
                throw new NghiepVuException("Hợp đồng Sepolia hiện chuyển toàn bộ lô. Vui lòng nhập đúng số lượng khả dụng: {$khaDung}.");
            }

            // Sinh transferId trước khi ký; chỉ đưa vào CSDL sau khi có event + txHash đã mined.
            $maChuyenGiao = (string) Str::uuid();
            $transferId = '0x' . hash('sha256', $maChuyenGiao);

            try {
                $this->signer->ensureGas((string) $identityGui->diaChiVi);
                $result = $this->signer->tx((string) $identityGui->signerKeyId, 'submitTransfer', [
                    'transferId' => $transferId,
                    'batchId' => $batchIdBytes32,
                    'receiver' => (string) $identityNhan->diaChiVi,
                ]);

                $txHash = (string) ($result['txHash'] ?? '');
                if (empty($result['ok']) || ! preg_match('/^0x[a-fA-F0-9]{64}$/', $txHash)) {
                    throw new NghiepVuException('Không thể ký/gửi submitTransfer lên Sepolia: ' . ($result['reasonVi'] ?? $result['reason'] ?? 'Signer không trả về txHash hợp lệ.'));
                }

                // Signer chỉ trả ok=true sau khi tx.wait(BLOCKCHAIN_CONFIRMATIONS) nhận receipt status=1.
                // Receipt chứa txHash và blockNumber thật từ Ethereum Sepolia.
                if (! isset($result['blockNumber']) || (int) $result['blockNumber'] < 1) {
                    throw new NghiepVuException('Signer chưa trả về blockNumber của giao dịch đã mined. Không ghi chuyển giao vào CSDL.');
                }
            } catch (NghiepVuException $e) {
                throw $e;
            } catch (\Throwable $e) {
                throw new NghiepVuException('Ký xác thực on-chain thất bại. CSDL chưa tạo chuyển giao: ' . $e->getMessage());
            }

            // Chỉ đến đây mới ghi CSDL: txHash phải là hash thật đã được Sepolia xác nhận.
            $chuyenGiao = ChuyenGiao::query()->create([
                'maChuyenGiao' => $maChuyenGiao,
                'loThuocId' => $loThuoc->id,
                'benGuiId' => $benGui->id,
                'benNhanId' => $benNhan->id,
                'soLuong' => $soLuong,
                'thoiGianKhoiTao' => now(),
                'trangThai' => TransferStatus::pending,
            ]);

            BlockchainTransaction::query()->create([
                'txHash' => $txHash,
                'blockNumber' => (int) $result['blockNumber'],
                'eventName' => 'TransferSubmitted',
                'doiTuongToChucId' => $benGui->id,
                'businessId' => $maChuyenGiao,
                'network' => config('truyXuat.blockchain.mang', 'Ethereum Sepolia'),
                'trangThai' => 'CONFIRMED',
                'toChucId' => $benGui->id,
                'sanPhamId' => $loThuoc->sanPhamId,
                'loThuocId' => $loThuoc->id,
                'chuyenGiaoId' => $chuyenGiao->id,
                'thoiGian' => ! empty($result['blockTimestamp'])
                    ? Carbon::createFromTimestamp((int) $result['blockTimestamp'])
                    : now(),
            ]);

            NhatKyHeThong::query()->create([
                'taiKhoanId' => $taiKhoan->id,
                'doiTuongId' => $chuyenGiao->id,
                'hanhDong' => 'KHOI_TAO_CHUYEN_GIAO_ONCHAIN',
                'ketQua' => 'THANH_CONG',
                'thoiGian' => now(),
                'chiTiet' => "{$benGui->tenToChuc} đã ký và xác nhận TransferSubmitted trên Ethereum Sepolia. TxHash: {$txHash}. Chờ {$benNhan->tenToChuc} tiếp nhận." . ($ip ? " IP: {$ip}" : ''),
            ]);

            return $chuyenGiao;
        }, 3);
    }

    public function nhan(ChuyenGiao $chuyenGiao, ToChuc $benNhan, TaiKhoan $taiKhoan, ?string $ip): void
    {
        $blockchainTransaction = DB::transaction(function () use ($chuyenGiao, $benNhan, $taiKhoan, $ip): BlockchainTransaction {
            $chuyenGiao = ChuyenGiao::query()->whereKey($chuyenGiao->id)->lockForUpdate()->firstOrFail();
            if ((int) $chuyenGiao->benNhanId !== (int) $benNhan->id || $chuyenGiao->trangThai !== TransferStatus::pending) {
                throw new NghiepVuException('Giao dịch không thuộc đơn vị nhận hoặc không còn chờ tiếp nhận.');
            }
            $this->assertNoPendingRecall((int) $chuyenGiao->loThuocId);
            $this->assertNoPendingTransferAction((int) $chuyenGiao->id);

            $loThuoc = LoThuoc::query()->whereKey($chuyenGiao->loThuocId)->lockForUpdate()->firstOrFail();
            $trangThaiLo = $loThuoc->trangThai instanceof BatchStatus ? $loThuoc->trangThai : BatchStatus::tryFrom((string) $loThuoc->trangThai);
            if ($trangThaiLo === BatchStatus::recalled || ($loThuoc->hanSuDung && Carbon::parse($loThuoc->hanSuDung)->startOfDay()->lt(today()))) {
                throw new NghiepVuException('Không thể tiếp nhận lô thuốc đã thu hồi hoặc hết hạn.');
            }

            $tx = $this->taoGiaoDichBlockchain($chuyenGiao, $benNhan, $loThuoc, 'TransferReceived', 'TRANSFER_RECEIVED');

            NhatKyHeThong::query()->create([
                'taiKhoanId' => $taiKhoan->id,
                'doiTuongId' => $chuyenGiao->id,
                'hanhDong' => 'TIEP_NHAN_CHUYEN_GIAO',
                'ketQua' => 'THANH_CONG',
                'thoiGian' => now(),
                'chiTiet' => "{$benNhan->tenToChuc} gửi yêu cầu tiếp nhận {$chuyenGiao->soLuong} đơn vị lô [{$loThuoc->maLoNghiepVu}]. Chờ xác nhận on-chain." . ($ip ? " IP: {$ip}" : ''),
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
            $this->assertNoPendingTransferAction((int) $chuyenGiao->id);

            $loThuoc = LoThuoc::query()->whereKey($chuyenGiao->loThuocId)->lockForUpdate()->firstOrFail();
            $lyDoTuChoi = trim((string) $lyDo) ?: 'Đơn vị nhận từ chối tiếp nhận lô hàng';
            // Chỉ lưu lý do trước; trạng thái REJECTED được chốt sau khi TransferRejected mined.
            $chuyenGiao->update(['lyDoTuChoi' => $lyDoTuChoi]);
            $tx = $this->taoGiaoDichBlockchain($chuyenGiao, $benNhan, $loThuoc, 'TransferRejected', 'TRANSFER_REJECTED');


            NhatKyHeThong::query()->create([
                'taiKhoanId' => $taiKhoan->id,
                'doiTuongId' => $chuyenGiao->id,
                'hanhDong' => 'TU_CHOI_CHUYEN_GIAO',
                'ketQua' => 'THANH_CONG',
                'thoiGian' => now(),
                'chiTiet' => "{$benNhan->tenToChuc} gửi yêu cầu từ chối chuyển giao {$chuyenGiao->maChuyenGiao}; chờ xác nhận on-chain. Lý do: {$lyDoTuChoi}." . ($ip ? " IP: {$ip}" : ''),
            ]);
            return $tx;
        });

        ProcessBatchTransferJob::dispatch($chuyenGiao->id, $taiKhoan->id, $blockchainTransaction->id, 'TransferRejected', 'TRANSFER_REJECTED')->afterCommit();
    }

    /** Bên gửi được hủy lệnh khi giao dịch vẫn đang chờ người nhận. */
    public function huy(ChuyenGiao $chuyenGiao, ToChuc $benGui, TaiKhoan $taiKhoan, ?string $ip = null): void
    {
        $blockchainTransaction = DB::transaction(function () use ($chuyenGiao, $benGui, $taiKhoan, $ip): BlockchainTransaction {
            $cg = ChuyenGiao::query()->whereKey($chuyenGiao->id)->lockForUpdate()->firstOrFail();
            if ((int) $cg->benGuiId !== (int) $benGui->id || $cg->trangThai !== TransferStatus::pending) {
                throw new NghiepVuException('Chỉ bên gửi mới được hủy lệnh đang chờ tiếp nhận.');
            }
            $this->assertNoPendingRecall((int) $cg->loThuocId);
            $this->assertNoPendingTransferAction((int) $cg->id);

            $loThuoc = LoThuoc::query()->whereKey($cg->loThuocId)->lockForUpdate()->firstOrFail();
            $tx = $this->taoGiaoDichBlockchain($cg, $benGui, $loThuoc, 'TransferCancelled', 'TRANSFER_CANCELLED');

            NhatKyHeThong::query()->create([
                'taiKhoanId' => $taiKhoan->id,
                'doiTuongId' => $cg->id,
                'hanhDong' => 'YEU_CAU_HUY_CHUYEN_GIAO',
                'ketQua' => 'THANH_CONG',
                'thoiGian' => now(),
                'chiTiet' => "Bên gửi yêu cầu hủy chuyển giao {$cg->maChuyenGiao}; chờ TransferCancelled được xác nhận on-chain." . ($ip ? " IP: {$ip}" : ''),
            ]);

            return $tx;
        });

        ProcessBatchTransferJob::dispatch(
            $chuyenGiao->id,
            $taiKhoan->id,
            $blockchainTransaction->id,
            'TransferCancelled',
            'TRANSFER_CANCELLED'
        )->afterCommit();
    }

    private function assertNoPendingRecall(int $loThuocId): void
    {
        if (ThuHoiLoHang::query()->where('loThuocId', $loThuocId)->exists()) {
            throw new NghiepVuException('Lô thuốc đã có quyết định thu hồi; không thể xử lý chuyển giao.');
        }
    }

    private function assertNoPendingTransferAction(int $chuyenGiaoId): void
    {
        $dangXuLy = BlockchainTransaction::query()
            ->where('chuyenGiaoId', $chuyenGiaoId)
            ->whereIn('eventName', ['TransferReceived', 'TransferRejected', 'TransferCancelled'])
            ->where('trangThai', 'PENDING')
            ->exists();

        if ($dangXuLy) {
            throw new NghiepVuException('Yêu cầu xử lý chuyển giao trước đó vẫn đang chờ xác nhận blockchain.');
        }
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

<?php

namespace App\Services\ChuoiCungUng;

use App\Enums\BatchStatus;
use App\Enums\OrganizationApprovalStatus;
use App\Enums\OrganizationType;
use App\Enums\TransferStatus;
use App\Exceptions\NghiepVuException;
use App\Models\BlockchainTransaction;
use App\Models\ChuyenGiao;
use App\Models\LoThuoc;
use App\Models\NhatKyHeThong;
use App\Models\TaiKhoan;
use App\Models\ToChuc;
use App\Models\TonKhoLo;
use App\Services\Blockchain\GhiNhanBlockchain;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChuyenGiaoService
{
    public function __construct(
        private TonKhoService $tonKhoService,
        private GhiNhanBlockchain $ghiNhanBlockchain,
    ) {}

    /**
     * Tạo đơn chuyển giao lô hàng
     */
    public function tao(ToChuc $benGui, array $duLieu, TaiKhoan $taiKhoan, ?string $ip): ChuyenGiao
    {
        return DB::transaction(function () use ($benGui, $duLieu, $taiKhoan, $ip): ChuyenGiao {
            $loThuoc = LoThuoc::query()->whereKey($duLieu['loThuocId'])->lockForUpdate()->firstOrFail();
            $benNhan = ToChuc::query()->whereKey($duLieu['benNhanId'])->firstOrFail();

            if ($benGui->id === $benNhan->id || $benNhan->trangThaiDuyet !== OrganizationApprovalStatus::daDuyet) {
                throw new NghiepVuException('Đơn vị nhận không hợp lệ hoặc chưa được duyệt.');
            }

            $loaiGui = $benGui->loaiToChuc;
            $loaiNhan = $benNhan->loaiToChuc;
            $hopLe = match ($loaiGui) {
                OrganizationType::nhaSanXuat => $loaiNhan === OrganizationType::nhaPhanPhoi,
                OrganizationType::nhaPhanPhoi => in_array($loaiNhan, [OrganizationType::nhaPhanPhoi, OrganizationType::nhaThuoc], true),
                default => false,
            };

            if (! $hopLe) {
                throw new NghiepVuException('Luồng chuyển giao giữa hai loại tổ chức này không được phép.');
            }

            // Guard: Chặn tạo chuyển giao nếu lô bị thu hồi hoặc hết hạn
            if ($loThuoc->trangThai === BatchStatus::recalled || (is_string($loThuoc->trangThai) && strtoupper($loThuoc->trangThai) === 'RECALLED') || $loThuoc->daHetHan()) {
                throw new NghiepVuException('Lô thuốc đã bị thu hồi hoặc hết hạn. Không thể khởi tạo chuyển giao.');
            }

            $tonKho = TonKhoLo::query()
                ->where('loThuocId', $loThuoc->id)
                ->where('toChucId', $benGui->id)
                ->lockForUpdate()
                ->first();
            $khaDung = $this->tonKhoService->soLuongKhaDung($loThuoc->id, $benGui->id);

            if (! $tonKho || $duLieu['soLuong'] > $khaDung) {
                throw new NghiepVuException('Số lượng chuyển vượt quá tồn kho khả dụng.');
            }

            $chuyenGiao = ChuyenGiao::query()->create([
                'maChuyenGiao' => (string) Str::uuid(),
                'loThuocId' => $loThuoc->id,
                'benGuiId' => $benGui->id,
                'benNhanId' => $benNhan->id,
                'soLuong' => $duLieu['soLuong'],
                'thoiGianKhoiTao' => now(),
                'trangThai' => TransferStatus::pending,
            ]);

            $this->ghiNhanBlockchain->ghi(
                'TransferSubmitted',
                $chuyenGiao->maChuyenGiao,
                $benGui,
                $loThuoc,
                ['chuyenGiaoId' => $chuyenGiao->id, 'benNhanId' => $benNhan->id, 'soLuong' => $chuyenGiao->soLuong],
            );

            NhatKyHeThong::query()->create([
                'taiKhoanId' => $taiKhoan->id,
                'doiTuongId' => $chuyenGiao->id,
                'hanhDong' => 'TAO_CHUYEN_GIAO',
                'ketQua' => 'THANH_CONG',
                'thoiGian' => now(),
                'chiTiet' => "Tạo chuyển giao {$chuyenGiao->maChuyenGiao} (Lô: {$loThuoc->maLoNghiepVu}). Chuyển {$chuyenGiao->soLuong} đơn vị từ {$benGui->tenToChuc} đến {$benNhan->tenToChuc}." . ($ip ? " IP: {$ip}" : ''),
            ]);

            return $chuyenGiao;
        });
    }

    /**
     * Tiếp nhận chuyển giao lô hàng
     */
    public function nhan(ChuyenGiao $chuyenGiao, ToChuc $benNhan, TaiKhoan $taiKhoan, ?string $ip): void
    {
        DB::transaction(function () use ($chuyenGiao, $benNhan, $taiKhoan, $ip): void {
            $chuyenGiao = ChuyenGiao::query()->whereKey($chuyenGiao->id)->lockForUpdate()->firstOrFail();

            if ((int) $chuyenGiao->benNhanId !== (int) $benNhan->id || $chuyenGiao->trangThai !== TransferStatus::pending) {
                throw new NghiepVuException('Giao dịch không thuộc đơn vị nhận hoặc không còn chờ tiếp nhận.');
            }

            $loThuoc = LoThuoc::query()->whereKey($chuyenGiao->loThuocId)->lockForUpdate()->firstOrFail();

            // Guard: Chặn xác nhận tiếp nhận nếu lô đã bị Cơ quan quản lý thu hồi hoặc hết hạn
            if ($loThuoc->trangThai === BatchStatus::recalled || (is_string($loThuoc->trangThai) && strtoupper($loThuoc->trangThai) === 'RECALLED') || $loThuoc->daHetHan()) {
                throw new NghiepVuException('Lô thuốc đã bị Cơ quan quản lý ban hành quyết định thu hồi hoặc đã hết hạn. Không thể tiếp nhận.');
            }

            $this->tonKhoService->tru($loThuoc->id, $chuyenGiao->benGuiId, (int) $chuyenGiao->soLuong);
            $this->tonKhoService->cong($loThuoc->id, $benNhan->id, (int) $chuyenGiao->soLuong);

            $this->ghiNhanBlockchain->ghi(
                'TransferReceived',
                $chuyenGiao->maChuyenGiao,
                $benNhan,
                $loThuoc,
                ['chuyenGiaoId' => $chuyenGiao->id, 'benGuiId' => $chuyenGiao->benGuiId, 'soLuong' => $chuyenGiao->soLuong],
            );

            $chuyenGiao->update([
                'trangThai' => TransferStatus::received,
                'thoiGianXacNhan' => now(),
            ]);

            NhatKyHeThong::query()->create([
                'taiKhoanId' => $taiKhoan->id,
                'doiTuongId' => $chuyenGiao->id,
                'hanhDong' => 'TIEP_NHAN_CHUYEN_GIAO',
                'ketQua' => 'THANH_CONG',
                'thoiGian' => now(),
                'chiTiet' => "Tiếp nhận chuyển giao {$chuyenGiao->maChuyenGiao} (Lô: {$loThuoc->maLoNghiepVu}). {$benNhan->tenToChuc} đã tiếp nhận {$chuyenGiao->soLuong} đơn vị." . ($ip ? " IP: {$ip}" : ''),
            ]);
        });
    }

    /**
     * Từ chối tiếp nhận chuyển giao lô hàng
     */
    public function tuChoi(ChuyenGiao $chuyenGiao, ToChuc $benNhan, TaiKhoan $taiKhoan, ?string $ip, ?string $lyDo = null): void
    {
        DB::transaction(function () use ($chuyenGiao, $benNhan, $taiKhoan, $ip, $lyDo): void {
            $chuyenGiao = ChuyenGiao::query()->whereKey($chuyenGiao->id)->lockForUpdate()->firstOrFail();

            if ((int) $chuyenGiao->benNhanId !== (int) $benNhan->id || $chuyenGiao->trangThai !== TransferStatus::pending) {
                throw new NghiepVuException('Giao dịch không thuộc đơn vị nhận hoặc không ở trạng thái chờ tiếp nhận.');
            }

            $loThuoc = LoThuoc::query()->whereKey($chuyenGiao->loThuocId)->lockForUpdate()->firstOrFail();
            $lyDoTuChoi = $lyDo ?: 'Nhà phân phối từ chối tiếp nhận đơn chuyển giao';

            $this->ghiNhanBlockchain->ghi(
                'TransferRejected',
                $chuyenGiao->maChuyenGiao,
                $benNhan,
                $loThuoc,
                [
                    'chuyenGiaoId' => $chuyenGiao->id,
                    'benGuiId' => $chuyenGiao->benGuiId,
                    'soLuong' => $chuyenGiao->soLuong,
                    'lyDo' => $lyDoTuChoi,
                ],
            );

            $chuyenGiao->update([
                'trangThai' => TransferStatus::cancelled,
                'thoiGianXacNhan' => now(),
            ]);

            BlockchainTransaction::where('chuyenGiaoId', $chuyenGiao->id)
                ->where('txHash', 'like', 'PENDING_CG_%')
                ->where('trangThai', 'PENDING')
                ->update([
                    'trangThai' => 'FAILED',
                    'thoiGian'  => now(),
                ]);

            $conPending = ChuyenGiao::query()
                ->where('loThuocId', $loThuoc->id)
                ->where('trangThai', TransferStatus::pending)
                ->exists();

            if (! $conPending && (string) ($loThuoc->trangThai?->value ?? $loThuoc->trangThai) === 'IN_TRANSIT') {
                $loThuoc->update(['trangThai' => BatchStatus::created]);
            }

            NhatKyHeThong::query()->create([
                'taiKhoanId' => $taiKhoan->id,
                'doiTuongId' => $chuyenGiao->id,
                'hanhDong' => 'TU_CHOI_CHUYEN_GIAO',
                'ketQua' => 'THANH_CONG',
                'thoiGian' => now(),
                'chiTiet' => "Từ chối tiếp nhận chuyển giao {$chuyenGiao->maChuyenGiao} (Lô: {$loThuoc->maLoNghiepVu}). Lý do: {$lyDoTuChoi}." . ($ip ? " IP: {$ip}" : ''),
            ]);
        });
    }
}

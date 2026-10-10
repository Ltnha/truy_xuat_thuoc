<?php

namespace App\Services\ChuoiCungUng;

use App\Enums\BatchStatus;
use App\Enums\OrganizationType;
use App\Enums\TransferStatus;
use App\Exceptions\NghiepVuException;
use App\Models\ChuyenGiao;
use App\Models\LichSuLoThuoc;
use App\Models\LoThuoc;
use App\Models\NhatKyHeThong;
use App\Models\TaiKhoan;
use App\Models\ToChuc;
use App\Services\Blockchain\GhiNhanBlockchain;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChuyenGiaoService
{
    public function __construct(
        private TonKhoService $tonKhoService,
        private GhiNhanBlockchain $ghiNhanBlockchain,
    ) {
    }

    public function tao(ToChuc $benGui, array $duLieu, TaiKhoan $taiKhoan, ?string $ip): ChuyenGiao
    {
        return DB::transaction(function () use ($benGui, $duLieu, $taiKhoan, $ip): ChuyenGiao {
            $loThuoc = LoThuoc::query()->whereKey($duLieu['loThuocId'])->lockForUpdate()->firstOrFail();
            $benNhan = ToChuc::query()->whereKey($duLieu['benNhanId'])->firstOrFail();

            if ($benGui->id === $benNhan->id || $benNhan->trangThaiDuyet !== \App\Enums\OrganizationApprovalStatus::daDuyet) {
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

            if ($loThuoc->trangThai === BatchStatus::recalled || $loThuoc->daHetHan()) {
                throw new NghiepVuException('Không thể chuyển giao lô đã thu hồi hoặc hết hạn.');
            }

            $tonKho = \App\Models\TonKhoLo::query()
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

            $txHash = $this->ghiNhanBlockchain->ghi(
                'TransferSubmitted',
                $chuyenGiao->maChuyenGiao,
                $benGui,
                $loThuoc,
                ['chuyenGiaoId' => $chuyenGiao->id, 'benNhanId' => $benNhan->id, 'soLuong' => $chuyenGiao->soLuong],
            );
            $chuyenGiao->update(['txHash' => $txHash]);

            LichSuLoThuoc::query()->create([
                'loThuocId' => $loThuoc->id,
                'suKien' => 'CHUYEN_GIAO_CHO_NHAN',
                'moTa' => "Chuyển {$chuyenGiao->soLuong} đơn vị đến {$benNhan->tenToChuc}.",
                'txHash' => $txHash,
            ]);
            NhatKyHeThong::query()->create([
                'taiKhoanId' => $taiKhoan->id,
                'hanhDong' => 'TAO_CHUYEN_GIAO',
                'thoiGian' => now(),
                'noiDung' => "Tạo giao dịch {$chuyenGiao->maChuyenGiao}.",
                'diaChiIP' => $ip,
            ]);

            return $chuyenGiao;
        });
    }

    public function nhan(ChuyenGiao $chuyenGiao, ToChuc $benNhan, TaiKhoan $taiKhoan, ?string $ip): void
    {
        DB::transaction(function () use ($chuyenGiao, $benNhan, $taiKhoan, $ip): void {
            $chuyenGiao = ChuyenGiao::query()->whereKey($chuyenGiao->id)->lockForUpdate()->firstOrFail();

            if ((int) $chuyenGiao->benNhanId !== (int) $benNhan->id || $chuyenGiao->trangThai !== TransferStatus::pending) {
                throw new NghiepVuException('Giao dịch không thuộc đơn vị nhận hoặc không còn chờ tiếp nhận.');
            }

            $loThuoc = LoThuoc::query()->whereKey($chuyenGiao->loThuocId)->lockForUpdate()->firstOrFail();
            $this->tonKhoService->tru($loThuoc->id, $chuyenGiao->benGuiId, (int) $chuyenGiao->soLuong);
            $this->tonKhoService->cong($loThuoc->id, $benNhan->id, (int) $chuyenGiao->soLuong);

            $txHash = $this->ghiNhanBlockchain->ghi(
                'TransferReceived',
                $chuyenGiao->maChuyenGiao,
                $benNhan,
                $loThuoc,
                ['chuyenGiaoId' => $chuyenGiao->id, 'benGuiId' => $chuyenGiao->benGuiId, 'soLuong' => $chuyenGiao->soLuong],
            );
            $chuyenGiao->update([
                'trangThai' => TransferStatus::received,
                'thoiGianXacNhan' => now(),
                'txHash' => $txHash,
            ]);

            LichSuLoThuoc::query()->create([
                'loThuocId' => $loThuoc->id,
                'suKien' => 'DA_TIEP_NHAN_CHUYEN_GIAO',
                'moTa' => "{$benNhan->tenToChuc} đã tiếp nhận {$chuyenGiao->soLuong} đơn vị.",
                'txHash' => $txHash,
            ]);
            NhatKyHeThong::query()->create([
                'taiKhoanId' => $taiKhoan->id,
                'hanhDong' => 'TIEP_NHAN_CHUYEN_GIAO',
                'thoiGian' => now(),
                'noiDung' => "Tiếp nhận giao dịch {$chuyenGiao->maChuyenGiao}.",
                'diaChiIP' => $ip,
            ]);
        });
    }
}

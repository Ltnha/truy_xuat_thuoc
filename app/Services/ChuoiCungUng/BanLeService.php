<?php

namespace App\Services\ChuoiCungUng;

use App\Enums\BatchStatus;
use App\Enums\OrganizationType;
use App\Enums\UnitStatus;
use App\Exceptions\NghiepVuException;
use App\Models\BanLe;
use App\Models\LichSuLoThuoc;
use App\Models\LoThuoc;
use App\Models\NhatKyHeThong;
use App\Models\TaiKhoan;
use App\Models\ToChuc;
use App\Models\DonViSanPham;
use App\Services\Blockchain\GhiNhanBlockchain;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BanLeService
{
    public function __construct(
        private TonKhoService $tonKhoService,
        private GhiNhanBlockchain $ghiNhanBlockchain,
    ) {
    }

    public function banLe(string $maDonVi, ToChuc $nhaThuoc, TaiKhoan $taiKhoan, ?string $ip): BanLe
    {
        return DB::transaction(function () use ($maDonVi, $nhaThuoc, $taiKhoan, $ip): BanLe {
            if ($nhaThuoc->loaiToChuc !== OrganizationType::nhaThuoc) {
                throw new NghiepVuException('Chỉ nhà thuốc được phép ghi nhận bán lẻ.');
            }

            $donVi = DonViSanPham::query()
                ->where('maDonVi', $maDonVi)
                ->orWhere('serialNumber', $maDonVi)
                ->lockForUpdate()
                ->first();

            if (! $donVi || $donVi->trangThai !== UnitStatus::available) {
                throw new NghiepVuException('Không tìm thấy đơn vị thuốc khả dụng hoặc đơn vị đã được bán.');
            }

            $loThuoc = LoThuoc::query()->whereKey($donVi->loThuocId)->lockForUpdate()->firstOrFail();
            if ($loThuoc->trangThai === BatchStatus::recalled || $loThuoc->daHetHan()) {
                throw new NghiepVuException('Không thể bán thuốc đã thu hồi hoặc hết hạn.');
            }

            $banLe = BanLe::query()->create([
                'maBanLe' => (string) Str::uuid(),
                'toChucId' => $nhaThuoc->id,
                'ngayBan' => now(),
                'soLuong' => 1,
                'trangThai' => 'CONFIRMED',
            ]);

            $this->tonKhoService->tru($loThuoc->id, $nhaThuoc->id, 1);
            $donVi->update([
                'banLeId' => $banLe->id,
                'trangThai' => UnitStatus::dispensed,
                'ngayBan' => now(),
            ]);

            $txHash = $this->ghiNhanBlockchain->ghi(
                'UnitDispensed',
                $banLe->maBanLe,
                $nhaThuoc,
                $loThuoc,
                ['banLeId' => $banLe->id, 'donViSanPhamId' => $donVi->id],
            );
            $banLe->update(['txHash' => $txHash]);
            $donVi->update(['txHash' => $txHash]);

            LichSuLoThuoc::query()->create([
                'loThuocId' => $loThuoc->id,
                'suKien' => 'BAN_LE',
                'moTa' => "{$nhaThuoc->tenToChuc} đã bán đơn vị {$donVi->maDonVi}.",
                'txHash' => $txHash,
            ]);
            NhatKyHeThong::query()->create([
                'taiKhoanId' => $taiKhoan->id,
                'hanhDong' => 'BAN_LE_DON_VI_THUOC',
                'thoiGian' => now(),
                'noiDung' => "Ghi nhận bán lẻ {$donVi->maDonVi} từ lô {$loThuoc->maLoNghiepVu}.",
                'diaChiIP' => $ip,
            ]);

            return $banLe;
        });
    }
}

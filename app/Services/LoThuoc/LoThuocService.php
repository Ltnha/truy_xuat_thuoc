<?php

namespace App\Services\LoThuoc;

use App\Enums\BatchStatus;
use App\Enums\OrganizationType;
use App\Enums\ProductApprovalStatus;
use App\Exceptions\NghiepVuException;
use App\Models\LichSuLoThuoc;
use App\Models\LoThuoc;
use App\Models\NhatKyHeThong;
use App\Models\TaiKhoan;
use App\Models\ToChuc;
use App\Services\Blockchain\GhiNhanBlockchain;
use App\Services\ChuoiCungUng\TonKhoService;
use App\Services\Concerns\ChayNghiepVu;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class LoThuocService
{
    use ChayNghiepVu;

    public function __construct(
        private DonViSanPhamService $donViSanPhamService,
        private QrService $qrService,
        private TonKhoService $tonKhoService,
        private GhiNhanBlockchain $ghiNhanBlockchain,
    ) {
    }

    public function danhSachChoNhaSanXuat(ToChuc $nhaSanXuat, array $boLoc = []): LengthAwarePaginator
    {
        return LoThuoc::query()
            ->with(['sanPham', 'tonKhoLos', 'qrCodes', 'chuyenGiao'])
            ->where('toChucId', $nhaSanXuat->id)
            ->when($boLoc['tuKhoa'] ?? null, function ($query, string $tuKhoa): void {
                $query->where(function ($subQuery) use ($tuKhoa): void {
                    $subQuery->where('maLoNghiepVu', 'like', '%'.$tuKhoa.'%')
                        ->orWhereHas('sanPham', fn ($productQuery) => $productQuery->where('tenSanPham', 'like', '%'.$tuKhoa.'%'));
                });
            })
            ->when($boLoc['sanPhamId'] ?? null, fn ($query, $sanPhamId) => $query->where('sanPhamId', $sanPhamId))
            ->when(
                isset($boLoc['trangThai']) && $boLoc['trangThai'] !== 'TAT_CA',
                fn ($query) => $query->where('trangThai', $boLoc['trangThai'])
            )
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
    }

    public function danhSachDuDieuKienPhanPhoi(ToChuc $toChuc): LengthAwarePaginator
    {
        return LoThuoc::query()
            ->with(['sanPham', 'tonKhoLos' => fn ($query) => $query->where('toChucId', $toChuc->id)])
            ->where('trangThai', '!=', BatchStatus::recalled->value)
            ->whereDate('hanSuDung', '>', today())
            ->whereHas('tonKhoLos', fn ($query) => $query->where('toChucId', $toChuc->id)->where('soLuong', '>', 0))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
    }

    public function chiTietPerToChuc(int $loThuocId, ToChuc $toChuc): LoThuoc
    {
        return LoThuoc::query()
            ->with(['sanPham', 'toChuc', 'tonKhoLos', 'donViSanPhams.qrCode', 'qrCodes', 'chuyenGiao.benGui', 'chuyenGiao.benNhan', 'lichSus'])
            ->whereKey($loThuocId)
            ->where(function ($query) use ($toChuc): void {
                $query->where('toChucId', $toChuc->id)
                    ->orWhereHas('tonKhoLos', fn ($inventoryQuery) => $inventoryQuery->where('toChucId', $toChuc->id));
            })
            ->firstOrFail();
    }

    public function dangKy(ToChuc $nhaSanXuat, array $duLieu, TaiKhoan $nguoiDung, ?string $diaChiIP = null): LoThuoc
    {
        return $this->chayNghiepVu('DANG_KY_LO', $nguoiDung, function () use ($nhaSanXuat, $duLieu, $nguoiDung, $diaChiIP): LoThuoc {
            if ($nhaSanXuat->loaiToChuc !== OrganizationType::nhaSanXuat) {
                throw new NghiepVuException('Chỉ nhà sản xuất được đăng ký lô thuốc.');
            }

            $sanPham = $nhaSanXuat->sanPhams()
                ->whereKey($duLieu['sanPhamId'])
                ->where('trangThaiDuyet', ProductApprovalStatus::daDuyet->value)
                ->first();

            if (! $sanPham) {
                throw new NghiepVuException('Sản phẩm chưa được duyệt hoặc không thuộc nhà sản xuất của bạn.');
            }

            $loThuoc = LoThuoc::query()->create([
                'maLo' => (string) Str::uuid(),
                'maLoNghiepVu' => $duLieu['maLoNghiepVu'],
                'sanPhamId' => $sanPham->id,
                'toChucId' => $nhaSanXuat->id,
                'soLuong' => $duLieu['soLuong'],
                'ngaySanXuat' => $duLieu['ngaySanXuat'],
                'hanSuDung' => $duLieu['hanSuDung'],
                'trangThai' => BatchStatus::created,
            ]);

            $this->tonKhoService->cong($loThuoc->id, $nhaSanXuat->id, $loThuoc->soLuong);
            $this->donViSanPhamService->sinhHangLoat($loThuoc);
            $this->qrService->sinhChoLo($loThuoc);

            $txHash = $this->ghiNhanBlockchain->ghi('BatchRegistered', $loThuoc->maLo, $nhaSanXuat, $loThuoc);
            LichSuLoThuoc::query()->create([
                'loThuocId' => $loThuoc->id,
                'suKien' => 'DANG_KY_LO',
                'moTa' => "Nhà sản xuất {$nhaSanXuat->tenToChuc} đăng ký lô {$loThuoc->maLoNghiepVu}",
                'txHash' => $txHash,
            ]);

            NhatKyHeThong::query()->create([
                'taiKhoanId' => $nguoiDung->id,
                'hanhDong' => 'DANG_KY_LO',
                'thoiGian' => now(),
                'noiDung' => "Đăng ký lô {$loThuoc->maLoNghiepVu}; giao dịch {$txHash}.",
                'diaChiIP' => $diaChiIP,
            ]);

            return $loThuoc;
        }, $diaChiIP);
    }
}

<?php

namespace App\Services\ChuoiCungUng;

use App\Enums\BatchStatus;
use App\Enums\OrganizationType;
use App\Enums\TransferStatus;
use App\Exceptions\NghiepVuException;
use App\Models\ChuyenGiao;
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

            // Đã xóa câu lệnh LichSuLoThuoc::query()->create(...) gây lỗi CSDL

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

            // Đã xóa câu lệnh LichSuLoThuoc::query()->create(...) gây lỗi CSDL

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

    public function tuChoi(ChuyenGiao $chuyenGiao, ToChuc $benNhan, TaiKhoan $taiKhoan, ?string $ip, ?string $lyDo = null): void
    {
        DB::transaction(function () use ($chuyenGiao, $benNhan, $taiKhoan, $ip, $lyDo): void {
            $chuyenGiao = ChuyenGiao::query()->whereKey($chuyenGiao->id)->lockForUpdate()->firstOrFail();

            if ((int) $chuyenGiao->benNhanId !== (int) $benNhan->id || $chuyenGiao->trangThai !== TransferStatus::pending) {
                throw new NghiepVuException('Giao dịch không thuộc đơn vị nhận hoặc không ở trạng thái chờ tiếp nhận.');
            }

            $loThuoc = LoThuoc::query()->whereKey($chuyenGiao->loThuocId)->lockForUpdate()->firstOrFail();
            $lyDoTuChoi = $lyDo ?: 'Nhà phân phối từ chối tiếp nhận đơn chuyển giao';

            // Kích hoạt gọi GhiNhanBlockchain::ghi() với sự kiện TransferRejected lên mạng Sepolia
            $txHash = $this->ghiNhanBlockchain->ghi(
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

            // Chuyển trạng thái đơn chuyenGiao sang CANCELLED / REJECTED
            // → soLuongKhaDung bên gửi được giải phóng (chỉ trừ PENDING)
            $chuyenGiao->update([
                'trangThai' => TransferStatus::cancelled,
                'thoiGianXacNhan' => now(),
                'txHash' => $txHash,
            ]);

            // Cập nhật blockchainTransaction PENDING của CG này → FAILED
            // (NSX không còn thấy "Đang xử lý Queue" cho giao dịch chuyển này)
            \App\Models\BlockchainTransaction::where('chuyenGiaoId', $chuyenGiao->id)
                ->where('trangThai', 'PENDING')
                ->update([
                    'trangThai' => 'FAILED',
                    'thoiGian'  => now(),
                ]);

            // Nếu lô không còn đơn PENDING nào khác → trả trạng thái lô về CREATED
            // để NSX thấy nút Chuyển giao và badge hợp lý
            $conPending = ChuyenGiao::query()
                ->where('loThuocId', $loThuoc->id)
                ->where('trangThai', TransferStatus::pending)
                ->exists();

            if (! $conPending && (string) ($loThuoc->trangThai?->value ?? $loThuoc->trangThai) === 'IN_TRANSIT') {
                $loThuoc->update(['trangThai' => 'CREATED']);
            }

            // Ghi nhật ký NhatKyHeThong hành động TU_CHOI_CHUYEN_GIAO đầy đủ doiTuongId, chiTiet, IP
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
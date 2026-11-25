<?php

namespace App\Services\CoQuan;

use App\Enums\BatchStatus;
use App\Enums\DecisionType;
use App\Enums\ReportStatus;
use App\Exceptions\NghiepVuException;
use App\Jobs\ProcessBatchRecallJob;
use App\Models\BaoCaoNghiVan;
use App\Models\BlockchainTransaction;
use App\Models\KetQuaXuLyBaoCao;
use App\Models\LoThuoc;
use App\Models\NhatKyHeThong;
use App\Models\ThuHoiLoHang;
use Illuminate\Support\Facades\DB;

class ThuHoiLoService
{
    /**
     * Thực thi quy trình thu hồi lô thuốc chính thức
     */
    public function thucHienThuHoi(int $loThuocId, string $lyDo, int $taiKhoanId, ?int $baoCaoNghiVanId = null, bool $gopCungLo = true): ThuHoiLoHang
    {
        return DB::transaction(function () use ($loThuocId, $lyDo, $taiKhoanId, $baoCaoNghiVanId, $gopCungLo) {
            $loThuoc = LoThuoc::where('id', $loThuocId)->lockForUpdate()->firstOrFail();
            $maLo = $loThuoc->maLoNghiepVu ?? $loThuoc->maLo ?? (string) $loThuoc->id;

            // 1. Kiểm tra trạng thái thu hồi
            $trangThaiHienTai = $loThuoc->trangThai instanceof BatchStatus ? $loThuoc->trangThai->value : (string) $loThuoc->trangThai;
            if ($trangThaiHienTai === BatchStatus::recalled->value) {
                throw new NghiepVuException("Lô thuốc [{$maLo}] đã được ban hành quyết định thu hồi trước đó.");
            }

            // 2. Quyết định thu hồi có hiệu lực nghiệp vụ ngay khi được ban hành.
            // Không chờ giao dịch Sepolia xác nhận mới cập nhật trạng thái lô trong hệ thống.
            $loThuoc->trangThai = BatchStatus::recalled;
            $loThuoc->save();

            // 3. Nếu xuất phát từ Báo cáo nghi vấn -> kiểm tra đúng loại và ghi nhận kết quả.
            $ketQuaXuLyId = null;
            if ($baoCaoNghiVanId) {
                $baoCaoGoc = BaoCaoNghiVan::findOrFail($baoCaoNghiVanId);
                if ($baoCaoGoc->loaiBaoCao === 'NGOAI_HE_THONG') {
                    throw new NghiepVuException('Báo cáo lô ngoài hệ thống / nghi làm giả không áp dụng lệnh thu hồi lô thật.');
                }

                $kq = $this->ghiKetQuaXuLyBaoCao($baoCaoNghiVanId, $taiKhoanId, DecisionType::thuHoi, $lyDo);
                $ketQuaXuLyId = $kq->id;
            }

            // 4. Tạo bản ghi ThuHoiLoHang chuẩn schema SQL
            $thuHoi = ThuHoiLoHang::create([
                'loThuocId' => $loThuoc->id,
                'coQuanQuyetDinhId' => $taiKhoanId,
                'ketQuaXuLyBaoCaoId' => $ketQuaXuLyId,
                'lyDo' => $lyDo,
                'ngayThuHoi' => now(),
            ]);

            // 5. Đồng bộ đóng toàn bộ báo cáo TRONG_VONG_DOI đang chờ của lô này (kể cả khi thu hồi trực tiếp từ trang Quản lý thu hồi)
            if ($gopCungLo || $baoCaoNghiVanId === null) {
                $cacBaoCaoCungLo = BaoCaoNghiVan::where('loThuocId', $loThuoc->id)
                    ->where(function ($q) {
                        $q->where('loaiBaoCao', 'TRONG_VONG_DOI')
                            ->orWhereNull('loaiBaoCao');
                    })
                    ->when($baoCaoNghiVanId, fn($q) => $q->where('id', '!=', $baoCaoNghiVanId))
                    ->where('trangThai', '!=', ReportStatus::daXuLy)
                    ->get();

                $lyDoGop = $baoCaoNghiVanId
                    ? "Xử lý gộp theo Quyết định thu hồi lô #{$thuHoi->id}: {$lyDo}"
                    : "Đã ban hành Quyết định thu hồi lô #{$thuHoi->id}: {$lyDo}";

                foreach ($cacBaoCaoCungLo as $bc) {
                    $this->ghiKetQuaXuLyBaoCao($bc->id, $taiKhoanId, DecisionType::thuHoi, $lyDoGop);
                }
            }

            // 6. Tạo bản ghi BlockchainTransaction (PENDING)
            BlockchainTransaction::create([
                'txHash' => null,
                'eventName' => 'BatchRecalled',
                'businessId' => (string) $thuHoi->id,
                'doiTuongToChucId' => $loThuoc->toChucId,
                'thoiGian' => now(),
                'network' => config('truyXuat.blockchain.mang', config('truyXuat.blockchain.network', 'Ethereum Sepolia Testnet')),
                'trangThai' => 'PENDING',
                'toChucId' => $loThuoc->toChucId,
                'sanPhamId' => $loThuoc->sanPhamId,
                'loThuocId' => $loThuoc->id,
            ]);

            // 7. Ghi nhật ký hệ thống
            NhatKyHeThong::create([
                'taiKhoanId' => $taiKhoanId,
                'doiTuongId' => $loThuoc->id,
                'hanhDong' => 'BAN_HANH_THU_HOI_LO',
                'ketQua' => 'THANH_CONG',
                'thoiGian' => now(),
                'chiTiet' => "Ban hành quyết định thu hồi lô thuốc [{$maLo}]. Lý do: {$lyDo}",
            ]);

            // 8. Dispatch Queue Job đồng bộ Blockchain
            DB::afterCommit(function () use ($thuHoi) {
                ProcessBatchRecallJob::dispatch($thuHoi->id);
            });

            return $thuHoi;
        });
    }

    /**
     * Cập nhật kết quả xử lý cho báo cáo nghi vấn
     */
    public function ghiKetQuaXuLyBaoCao(int $baoCaoId, int $taiKhoanId, DecisionType|string $quyetDinh, string $lyDo, ?int $thuHoiId = null): KetQuaXuLyBaoCao
    {
        $baoCao = BaoCaoNghiVan::findOrFail($baoCaoId);
        $baoCao->trangThai = ReportStatus::daXuLy;
        $baoCao->save();

        $quyetDinhValue = $quyetDinh instanceof DecisionType ? $quyetDinh->value : $quyetDinh;

        return KetQuaXuLyBaoCao::updateOrCreate(
            ['baoCaoId' => $baoCao->id],
            [
                'coQuanXuLyId' => $taiKhoanId,
                'quyetDinh' => $quyetDinhValue,
                'lyDoXuLy' => $lyDo,
                'ngayXuLy' => now(),
            ]
        );
    }
}

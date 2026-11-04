<?php

namespace App\Services\CoQuan;

use App\Enums\BatchStatus;
use App\Enums\DecisionType;
use App\Enums\ReportStatus;
use App\Enums\TransferStatus;
use App\Exceptions\NghiepVuException;
use App\Jobs\ProcessBatchRecallJob;
use App\Models\BaoCaoNghiVan;
use App\Models\BlockchainTransaction;
use App\Models\ChuyenGiao;
use App\Models\KetQuaXuLyBaoCao;
use App\Models\LoThuoc;
use App\Models\NhatKyHeThong;
use App\Models\ThuHoiLoHang;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ThuHoiLoService
{
    /**
     * Thực thi quy trình thu hồi lô thuốc chính thức
     */
    public function thucHienThuHoi(int $loThuocId, string $lyDo, int $taiKhoanId, ?int $baoCaoNghiVanId = null, bool $gopCungLo = false): ThuHoiLoHang
    {
        return DB::transaction(function () use ($loThuocId, $lyDo, $taiKhoanId, $baoCaoNghiVanId, $gopCungLo) {
            $loThuoc = LoThuoc::where('id', $loThuocId)->lockForUpdate()->firstOrFail();
            $maLo = $loThuoc->maLoNghiepVu ?? $loThuoc->maLo ?? (string) $loThuoc->id;

            // 1. Kiểm tra trạng thái thu hồi
            $trangThaiHienTai = $loThuoc->trangThai instanceof BatchStatus ? $loThuoc->trangThai->value : (string) $loThuoc->trangThai;
            if ($trangThaiHienTai === BatchStatus::recalled->value) {
                throw new NghiepVuException("Lô thuốc [{$maLo}] đã được ban hành quyết định thu hồi trước đó.");
            }

            // 2. Chuyển trạng thái lô sang RECALLED
            $loThuoc->trangThai = BatchStatus::recalled;
            $loThuoc->save();

            // 3. Nếu xuất phát từ Báo cáo nghi vấn -> Ghi nhận kết quả báo cáo
            $ketQuaXuLyId = null;
            if ($baoCaoNghiVanId) {
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

            // 5. Tự động hủy các đơn ChuyenGiao PENDING của lô này
            ChuyenGiao::where('loThuocId', $loThuoc->id)
                ->where('trangThai', TransferStatus::pending)
                ->update([
                    'trangThai' => TransferStatus::cancelled,
                    'lyDoTuChoi' => "Giao dịch bị hủy tự động do Lô thuốc bị Cơ quan quản lý thu hồi (Mã QĐ: #{$thuHoi->id})",
                    'thoiGianXacNhan' => now(),
                ]);

            // 6. Xử lý gộp các báo cáo nghi vấn cùng lô nếu có
            if ($baoCaoNghiVanId && $gopCungLo) {
                $cacBaoCaoCungLo = BaoCaoNghiVan::where('loThuocId', $loThuoc->id)
                    ->where('id', '!=', $baoCaoNghiVanId)
                    ->where('trangThai', '!=', ReportStatus::daXuLy)
                    ->get();

                foreach ($cacBaoCaoCungLo as $bc) {
                    $this->ghiKetQuaXuLyBaoCao($bc->id, $taiKhoanId, DecisionType::thuHoi, "Xử lý gộp theo Quyết định thu hồi lô #{$thuHoi->id}");
                }
            }

            // 7. Tạo bản ghi BlockchainTransaction (PENDING)
            $placeholderTx = 'PENDING_RECALL_' . $thuHoi->id . '_' . Str::random(8);
            BlockchainTransaction::create([
                'txHash' => $placeholderTx,
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

            // 8. Ghi nhật ký hệ thống
            NhatKyHeThong::create([
                'taiKhoanId' => $taiKhoanId,
                'doiTuongId' => $loThuoc->id,
                'hanhDong' => 'BAN_HANH_THU_HOI_LO',
                'ketQua' => 'THANH_CONG',
                'thoiGian' => now(),
                'chiTiet' => "Ban hành quyết định thu hồi lô thuốc [{$maLo}]. Lý do: {$lyDo}",
            ]);

            // 9. Dispatch Queue Job đồng bộ Blockchain
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

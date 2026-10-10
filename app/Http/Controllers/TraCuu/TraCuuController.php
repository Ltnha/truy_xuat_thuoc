<?php

namespace App\Http\Controllers\TraCuu;

use App\Http\Controllers\Controller;
use App\Models\TraCuu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TraCuuController extends Controller
{
    public function home(): View
    {
        return view('truyXuat.trangChu', [
            'soLo' => DB::table('loThuoc')->count(),
            'soDonVi' => DB::table('donViSanPham')->count(),
            'soGiaoDich' => DB::table('blockchainTransaction')->count(),
            'soLoThuHoi' => DB::table('loThuoc')->where('trangThai', 'RECALLED')->count(),
            'loThuHoi' => DB::table('loThuoc as lt')
                ->join('sanPham as sp', 'sp.id', '=', 'lt.sanPhamId')
                ->join('toChuc as tc', 'tc.id', '=', 'lt.toChucId')
                ->leftJoin('thuHoiLoHang as th', 'th.loThuocId', '=', 'lt.id')
                ->where('lt.trangThai', 'RECALLED')
                ->select('lt.maLoNghiepVu', 'lt.id', 'sp.tenSanPham', 'sp.soDangKy', 'tc.tenToChuc', 'th.lyDo', 'th.txHash')
                ->latest('lt.id')
                ->limit(10)
                ->get(),
        ]);
    }

    public function show(Request $request, ?string $ma = null): View
    {
        $ma = trim((string) ($ma ?: $request->query('maDinhDanh', '')));
        $ketQua = $ma === '' ? null : $this->timTheoMa($ma);

        if ($ketQua && $ketQua['trangThai'] !== 'NOT_FOUND') {
            TraCuu::query()->create([
                'loThuocId' => $ketQua['loThuocId'],
                'maQRHoacSerial' => mb_substr($ma, 0, 255),
                'thoiGianTraCuu' => now(),
                'diaChiIP' => $request->ip(),
            ]);
        }

        return view('truyXuat.ketQua', compact('ma', 'ketQua'));
    }

    public function api(string $ma): JsonResponse
    {
        $ketQua = $this->timTheoMa(trim($ma));
        unset($ketQua['loThuocId']);

        return response()->json($ketQua);
    }

    private function timTheoMa(string $ma): array
    {
        $unit = DB::table('donViSanPham as dv')
            ->join('loThuoc as lt', 'lt.id', '=', 'dv.loThuocId')
            ->where(function ($query) use ($ma): void {
                $query->where('dv.maDonVi', $ma)
                    ->orWhere('dv.serialNumber', $ma)
                    ->orWhereExists(function ($qrQuery) use ($ma): void {
                        $qrQuery->selectRaw('1')
                            ->from('qrCode as qr')
                            ->whereColumn('qr.donViSanPhamId', 'dv.id')
                            ->where('qr.giaTriQR', $ma);
                    });
            })
            ->select('dv.id as donViId', 'dv.maDonVi', 'dv.serialNumber', 'dv.trangThai as trangThaiDonVi', 'dv.ngayBan', 'dv.txHash', 'lt.id as loThuocId')
            ->first();

        $loThuocId = $unit?->loThuocId;
        if ($loThuocId === null) {
            $qrLo = DB::table('qrCode')
                ->where('giaTriQR', $ma)
                ->where('loaiQR', 'BATCH')
                ->value('loThuocId');
            $loThuocId = $qrLo ?: DB::table('loThuoc')
                ->where('maLoNghiepVu', $ma)
                ->orWhere('maLo', $ma)
                ->value('id');
        }

        if ($loThuocId === null) {
            return [
                'trangThai' => 'NOT_FOUND',
                'loai' => 'KHÔNG TÌM THẤY',
                'tenThuoc' => 'Không tìm thấy mã trong hệ thống.',
                'hamLuongDangBaoChe' => '—',
                'hoatChat' => '—',
                'soDangKy' => '—',
                'nhaSanXuat' => '—',
                'maLo' => $ma,
                'ngaySanXuat' => '—',
                'hanSuDung' => '—',
                'batchIdHash' => '—',
                'txHash' => '—',
                'isDispensed' => 'Không xác định',
                'hanhTrinh' => [],
            ];
        }

        $lo = DB::table('loThuoc as lt')
            ->join('sanPham as sp', 'sp.id', '=', 'lt.sanPhamId')
            ->join('toChuc as tc', 'tc.id', '=', 'lt.toChucId')
            ->where('lt.id', $loThuocId)
            ->select('lt.*', 'sp.tenSanPham', 'sp.soDangKy', 'sp.hoatChat', 'sp.quyCachDongGoi', 'sp.trangThaiDuyet as trangThaiSanPham', 'tc.tenToChuc')
            ->first();

        $thuHoi = DB::table('thuHoiLoHang')->where('loThuocId', $lo->id)->latest('id')->first();
        $batchTxHash = DB::table('blockchainTransaction')
            ->where('loThuocId', $lo->id)
            ->where('eventName', 'BatchRegistered')
            ->value('txHash');
        $steps = DB::table('lichSuLoThuoc')
            ->where('loThuocId', $lo->id)
            ->orderBy('thoiGian')
            ->get()
            ->map(fn ($step): array => [
                'tieuDe' => $step->suKien,
                'donVi' => $step->moTa ?: 'Lịch sử lô thuốc',
                'thoiGian' => $step->thoiGian,
                'canhBao' => str_contains($step->suKien, 'THU_HOI'),
            ])
            ->all();
        $transferSteps = DB::table('chuyenGiao as cg')
            ->join('toChuc as bg', 'bg.id', '=', 'cg.benGuiId')
            ->join('toChuc as bn', 'bn.id', '=', 'cg.benNhanId')
            ->where('cg.loThuocId', $lo->id)
            ->where('cg.trangThai', 'RECEIVED')
            ->orderBy('cg.thoiGianXacNhan')
            ->get()
            ->map(fn ($transfer): array => [
                'tieuDe' => 'CHUYEN_GIAO_DA_TIEP_NHAN',
                'donVi' => $transfer->benGui.' → '.$transfer->benNhan.' ('.$transfer->soLuong.' đơn vị)',
                'thoiGian' => $transfer->thoiGianXacNhan,
                'canhBao' => false,
            ])
            ->all();

        $ngayHetHan = (string) $lo->hanSuDung;
        $status = $lo->trangThai === 'RECALLED'
            ? 'RECALLED'
            : ($ngayHetHan < today()->toDateString() ? 'EXPIRED' : ($lo->trangThaiSanPham === 'DA_DUYET' ? 'VALID' : 'INVALID'));

        return [
            'trangThai' => $status,
            'loai' => $unit ? 'MÃ ĐƠN VỊ HỘP LẺ' : 'MÃ LÔ THUỐC',
            'tenThuoc' => $lo->tenSanPham.($unit ? ' · '.$unit->maDonVi : ''),
            'hamLuongDangBaoChe' => $lo->quyCachDongGoi ?: '—',
            'hoatChat' => $lo->hoatChat ?: '—',
            'soDangKy' => $lo->soDangKy,
            'nhaSanXuat' => $lo->tenToChuc,
            'maLo' => $lo->maLoNghiepVu,
            'ngaySanXuat' => $lo->ngaySanXuat,
            'hanSuDung' => $ngayHetHan,
            'batchIdHash' => $lo->maLo,
            'txHash' => ($unit?->txHash ?: $batchTxHash) ?: 'Chưa có giao dịch blockchain',
            'isDispensed' => ! $unit ? 'N/A (cấp lô)' : ($unit->trangThaiDonVi === 'DISPENSED' ? 'ĐÃ BÁN' : 'CHƯA BÁN'),
            'lyDoThuHoi' => $thuHoi?->lyDo,
            'loThuocId' => (int) $lo->id,
            'hanhTrinh' => [...$steps, ...$transferSteps],
        ];
    }
}

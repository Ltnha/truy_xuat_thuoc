<?php

namespace App\Http\Controllers\CoQuan;

use App\Http\Controllers\Controller;
use App\Models\BaoCaoNghiVan;
use App\Models\LoThuoc;
use App\Models\SanPham;
use App\Models\ThuHoiLoHang;
use App\Models\ToChuc;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ThongKeBaoCaoController extends Controller
{
    public function index()
    {
        return view('coQuanQuanLy.thongKeBaoCao');
    }

    /**
     * API Lấy toàn bộ chỉ số KPI, Biểu đồ và 2 Bảng Điểm nóng theo khoảng thời gian
     */
    public function duLieuAPI(Request $request): JsonResponse
    {
        try {
            $range = $request->input('range', '30_NGAY');
            $thang = $request->input('thang');
            $nam = $request->input('nam', date('Y'));
            $tuNgay = $request->input('tuNgay');
            $denNgay = $request->input('denNgay');

            // 1. Xác định mốc thời gian lọc (start, end)
            $now = Carbon::now();
            if ($range === '7_NGAY') {
                $start = $now->copy()->subDays(6)->startOfDay();
                $end = $now->copy()->endOfDay();
            } elseif ($range === 'THANG' && $thang) {
                $start = Carbon::createFromDate($nam, $thang, 1)->startOfDay();
                $end = Carbon::createFromDate($nam, $thang, 1)->endOfMonth()->endOfDay();
            } elseif ($range === 'TUY_CHON' && $tuNgay && $denNgay) {
                $start = Carbon::parse($tuNgay)->startOfDay();
                $end = Carbon::parse($denNgay)->endOfDay();
            } else { // 30_NGAY mặc định
                $range = '30_NGAY';
                $start = $now->copy()->subDays(29)->startOfDay();
                $end = $now->copy()->endOfDay();
            }

            // 2. Truy vấn dữ liệu KPI
            $tongLoDangKy = LoThuoc::whereBetween('ngaySanXuat', [$start->toDateString(), $end->toDateString()])->count();
            $tongLoSystem = LoThuoc::count();

            $tongToChucHoatDong = ToChuc::where('trangThaiDuyet', 'DA_DUYET')->count();
            $loDangLuuHanh = LoThuoc::whereIn('trangThai', ['CREATED', 'IN_TRANSIT'])->count();
            $loDaThuHoi = ThuHoiLoHang::whereBetween('ngayThuHoi', [$start, $end])->count();
            $loDaThuHoiSystem = ThuHoiLoHang::count();

            $nghiVanChoXuLy = BaoCaoNghiVan::whereIn('trangThai', ['DA_GUI', 'DANG_XU_LY'])->count();

            $hoSoToChucChoDuyet = ToChuc::where('trangThaiDuyet', 'CHO_DUYET')->count();
            $hoSoSanPhamChoDuyet = SanPham::where('trangThaiDuyet', 'CHO_DUYET')->count();
            $hoSoChoDuyet = $hoSoToChucChoDuyet + $hoSoSanPhamChoDuyet;

            // 3. Dữ liệu Biểu đồ (Tạo mốc theo khoảng thời gian)
            $chartLabels = [];
            $chartLoData = [];
            $chartThuHoiData = [];

            if ($range === '7_NGAY') {
                for ($i = 6; $i >= 0; $i--) {
                    $day = $now->copy()->subDays($i);
                    $dayStr = $day->toDateString();
                    $chartLabels[] = $day->format('d/m');
                    $chartLoData[] = LoThuoc::whereDate('ngaySanXuat', $dayStr)->count();
                    $chartThuHoiData[] = ThuHoiLoHang::whereDate('ngayThuHoi', $dayStr)->count();
                }
            } elseif ($range === 'THANG') {
                $daysInMonth = $start->daysInMonth;
                for ($d = 1; $d <= $daysInMonth; $d++) {
                    $day = Carbon::createFromDate($nam, $thang, $d);
                    $dayStr = $day->toDateString();
                    $chartLabels[] = "Ngày $d";
                    $chartLoData[] = LoThuoc::whereDate('ngaySanXuat', $dayStr)->count();
                    $chartThuHoiData[] = ThuHoiLoHang::whereDate('ngayThuHoi', $dayStr)->count();
                }
            } elseif ($range === '30_NGAY') {
                for ($i = 29; $i >= 0; $i -= 5) {
                    $day = $now->copy()->subDays($i);
                    $daySubEnd = $day->copy()->addDays(4)->endOfDay();
                    $chartLabels[] = $day->format('d/m') . ' - ' . $daySubEnd->format('d/m');
                    $chartLoData[] = LoThuoc::whereBetween('ngaySanXuat', [$day->toDateString(), $daySubEnd->toDateString()])->count();
                    $chartThuHoiData[] = ThuHoiLoHang::whereBetween('ngayThuHoi', [$day->startOfDay(), $daySubEnd])->count();
                }
            } else { // TUY_CHON
                $diffDays = $start->diffInDays($end) + 1;
                $step = max(1, ceil($diffDays / 7));
                $curr = $start->copy();
                while ($curr <= $end) {
                    $next = $curr->copy()->addDays($step - 1)->endOfDay();
                    if ($next > $end) $next = $end->copy();
                    $chartLabels[] = $curr->format('d/m') . ($step > 1 ? '-' . $next->format('d/m') : '');
                    $chartLoData[] = LoThuoc::whereBetween('ngaySanXuat', [$curr->toDateString(), $next->toDateString()])->count();
                    $chartThuHoiData[] = ThuHoiLoHang::whereBetween('ngayThuHoi', [$curr, $next])->count();
                    $curr = $next->copy()->addDay()->startOfDay();
                }
            }

            // 4. BẢNG 1: Top Lô Thuốc bị báo cáo nhiều nhất
            $topLoThuocRaw = BaoCaoNghiVan::select('loThuocId', DB::raw('COUNT(*) as soLuotBaoCao'))
                ->whereNotNull('loThuocId')
                ->whereBetween('ngayGui', [$start, $end])
                ->groupBy('loThuocId')
                ->orderByDesc('soLuotBaoCao')
                ->limit(10)
                ->get();

            if ($topLoThuocRaw->isEmpty()) {
                $topLoThuocRaw = BaoCaoNghiVan::select('loThuocId', DB::raw('COUNT(*) as soLuotBaoCao'))
                    ->whereNotNull('loThuocId')
                    ->groupBy('loThuocId')
                    ->orderByDesc('soLuotBaoCao')
                    ->limit(10)
                    ->get();
            }

            $topLoThuoc = [];
            $sttLo = 1;
            foreach ($topLoThuocRaw as $item) {
                $lo = LoThuoc::with(['sanPham', 'toChuc'])->find($item->loThuocId);
                if (!$lo) continue;

                $latestBaoCao = BaoCaoNghiVan::where('loThuocId', $lo->id)->latest('id')->first();
                $soLuot = $item->soLuotBaoCao;
                $mucDoRuiRo = ($soLuot >= 5) ? 'NGUY_CAP' : (($soLuot >= 3) ? 'CAO' : 'TRUNG_BINH');

                $topLoThuoc[] = [
                    'hang' => $sttLo++,
                    'loThuocId' => $lo->id,
                    'maLo' => $lo->maLoNghiepVu ?? $lo->maLo,
                    'tenSanPham' => $lo->sanPham?->tenSanPham ?? 'N/A',
                    'soDangKy' => $lo->sanPham?->soDangKy ?? 'N/A',
                    'toChucPhuTrach' => $lo->toChuc?->tenToChuc ?? 'Chưa xác định',
                    'soLuotBaoCao' => $soLuot,
                    'hanhViChuYeu' => $latestBaoCao?->lyDo ?? 'Không có thông tin lý do.',
                    'mucDoRuiRo' => $mucDoRuiRo,
                ];
            }

            // 5. BẢNG 2: Top Tổ Chức bị báo cáo nhiều nhất (thông qua lô thuốc liên quan)
            $topToChucRaw = BaoCaoNghiVan::join('loThuoc', 'baoCaoNghiVan.loThuocId', '=', 'loThuoc.id')
                ->select('loThuoc.toChucId', DB::raw('COUNT(baoCaoNghiVan.id) as soLuotBaoCao'))
                ->whereBetween('baoCaoNghiVan.ngayGui', [$start, $end])
                ->groupBy('loThuoc.toChucId')
                ->orderByDesc('soLuotBaoCao')
                ->limit(10)
                ->get();

            if ($topToChucRaw->isEmpty()) {
                $topToChucRaw = BaoCaoNghiVan::join('loThuoc', 'baoCaoNghiVan.loThuocId', '=', 'loThuoc.id')
                    ->select('loThuoc.toChucId', DB::raw('COUNT(baoCaoNghiVan.id) as soLuotBaoCao'))
                    ->groupBy('loThuoc.toChucId')
                    ->orderByDesc('soLuotBaoCao')
                    ->limit(10)
                    ->get();
            }

            $topToChuc = [];
            $sttTC = 1;
            foreach ($topToChucRaw as $item) {
                $tc = ToChuc::find($item->toChucId);
                if (!$tc) continue;

                $latestBaoCao = BaoCaoNghiVan::join('loThuoc', 'baoCaoNghiVan.loThuocId', '=', 'loThuoc.id')
                    ->where('loThuoc.toChucId', $tc->id)
                    ->select('baoCaoNghiVan.*')
                    ->latest('baoCaoNghiVan.id')
                    ->first();

                $soLuot = $item->soLuotBaoCao;
                $mucDoRuiRo = ($soLuot >= 5) ? 'NGUY_CAP' : (($soLuot >= 3) ? 'CAO' : 'TRUNG_BINH');

                $topToChuc[] = [
                    'hang' => $sttTC++,
                    'toChucId' => $tc->id,
                    'tenToChuc' => $tc->tenToChuc,
                    'loaiToChuc' => $tc->loaiToChuc,
                    'maSoThue' => $tc->maSoThue ?? $tc->soGiayPhepDuoc ?? 'N/A',
                    'soLuotBaoCao' => $soLuot,
                    'hanhViChuYeu' => $latestBaoCao?->lyDo ?? 'Ghi nhận các vi phạm liên quan đến lô thuốc.',
                    'mucDoRuiRo' => $mucDoRuiRo,
                ];
            }

            return response()->json([
                'success' => true,
                'kpi' => [
                    'tongLoDangKy' => $tongLoDangKy > 0 ? $tongLoDangKy : $tongLoSystem,
                    'tongToChuc' => $tongToChucHoatDong,
                    'luuHanh' => $loDangLuuHanh,
                    'thuHoi' => $loDaThuHoi > 0 ? $loDaThuHoi : $loDaThuHoiSystem,
                    'nghiVan' => $nghiVanChoXuLy,
                    'choDuyet' => $hoSoChoDuyet,
                ],
                'chart' => [
                    'labels' => $chartLabels,
                    'loData' => $chartLoData,
                    'thuHoiData' => $chartThuHoiData,
                ],
                'topLoThuoc' => $topLoThuoc,
                'topToChuc' => $topToChuc,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi Backend: ' . $e->getMessage(),
            ], 500);
        }
    }
}

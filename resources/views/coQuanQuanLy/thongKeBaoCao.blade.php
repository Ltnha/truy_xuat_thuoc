<?php
// Vai trò Cơ quan quản lý (Tông màu chủ đạo: Indigo / Chàm đậm)
$vaiTroHienTai = 'CO_QUAN_QUAN_LY';
$tieuDeTrang = "Thống Kê Báo Cáo – Cổng Cơ Quan Quản Lý";
$tieuDePhanHe = "Thống kê báo cáo";
$fileCssRieng = "thongKeBaoCao.css";
$fileJsRieng = "thongKeBaoCao.js";
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $tieuDeTrang; ?></title>

    <!-- Google Font Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        nenChinh: '#f2faf5',
                    }
                }
            }
        }
    </script>

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <link rel="stylesheet" href="thongKeBaoCao.css">
</head>

<body class="bg-nenChinh text-slate-900 font-sans min-h-screen flex antialiased">

    <!-- 1. Thanh Sidebar dùng chung cố định -->
    <?php include_once '../trangDungChung/layoutDungChung/sidebarDungChung.php'; ?>

    <!-- 2. Cột nội dung trang bên phải -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Thanh Topbar dùng chung -->
        <?php include_once '../trangDungChung/layoutDungChung/topbarDungChung.php'; ?>

        <main class="flex-1 p-6 sm:p-8 space-y-6 max-w-7xl w-full mx-auto">

            <!-- THANH ĐIỀU KHIỂN: BỘ LỌC THỜI GIAN + NÚT XUẤT BÁO CÁO -->
            <div class="bg-white p-5 rounded-2xl border border-emerald-900/10 bong-xanh flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">

                <div class="flex flex-wrap items-center gap-3">
                    <span class="text-xs font-bold text-slate-700 uppercase tracking-wide flex items-center gap-1.5">
                        <i class="fa-regular fa-calendar-check text-indigo-700"></i> Khoảng Thời Gian:
                    </span>

                    <div class="inline-flex p-1 bg-nenChinh rounded-xl border border-emerald-900/15 text-xs font-semibold">
                        <button type="button" class="btn-loc-thoigian px-3.5 py-1.5 rounded-lg transition-all text-slate-600 hover:text-indigo-900" data-range="7_NGAY">
                            7 ngày qua
                        </button>
                        <button type="button" class="btn-loc-thoigian px-3.5 py-1.5 rounded-lg transition-all bg-indigo-700 text-white shadow-2xs font-bold" data-range="30_NGAY">
                            30 ngày qua
                        </button>
                        <button type="button" class="btn-loc-thoigian px-3.5 py-1.5 rounded-lg transition-all text-slate-600 hover:text-indigo-900" data-range="TUY_CHON">
                            Tùy chọn...
                        </button>
                    </div>

                    <!-- Khung chọn ngày tùy chọn -->
                    <div id="khungChonNgayTuyChon" class="hidden flex items-center gap-2 text-xs">
                        <input type="date" id="inputNgayBatDau" class="px-2.5 py-1.5 bg-nenChinh border border-slate-300 rounded-lg outline-none font-mono text-xs">
                        <span class="text-slate-400">đến</span>
                        <input type="date" id="inputNgayKetThuc" class="px-2.5 py-1.5 bg-nenChinh border border-slate-300 rounded-lg outline-none font-mono text-xs">
                        <button type="button" id="nutApDungKhoangNgay" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-lg transition-all">
                            Lọc
                        </button>
                    </div>
                </div>

                <!-- Nút Xuất file báo cáo -->
                <button type="button" id="nutXuatBaoCao" class="px-4 py-2.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-800 border border-indigo-200 rounded-xl text-xs font-bold transition-all shadow-2xs flex items-center justify-center gap-2 shrink-0">
                    <i class="fa-solid fa-file-arrow-down text-sm text-indigo-700"></i> Xuất Báo Cáo Giám Sát (CSV / PDF)
                </button>
            </div>

            <!-- HÀNG 6 Ô ĐIỀU HƯỚNG: ĐÃ BỎ HẾT THÔNG TIN PHỤ, CHỈ GIỮ LẠI SỐ CHÍNH -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3.5">

                <!-- Thẻ 1: Tổng số lô đã đăng ký -->
                <a href="../coQuanQuanLy/giamSatLuuThong.php" class="the-chi-so bg-white p-4 rounded-2xl border border-emerald-900/10 bong-xanh hover:border-indigo-300 hover:shadow-md transition-all flex flex-col justify-between group">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Tổng lô đăng ký</span>
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-700 flex items-center justify-center text-sm group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-boxes-stacked"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div id="kpiTongLoDangKy" class="text-2xl font-black text-slate-900 font-mono">1.250</div>
                    </div>
                </a>

                <!-- Thẻ 2: Số tổ chức đang hoạt động -->
                <a href="../coQuanQuanLy/quanLyQuyenHan.php" class="the-chi-so bg-white p-4 rounded-2xl border border-emerald-900/10 bong-xanh hover:border-indigo-300 hover:shadow-md transition-all flex flex-col justify-between group">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Tổ chức hoạt động</span>
                        <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-700 flex items-center justify-center text-sm group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-building-circle-check"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div id="kpiTongToChuc" class="text-2xl font-black text-slate-900 font-mono">84</div>
                    </div>
                </a>

                <!-- Thẻ 3: Số lô đang lưu hành -->
                <a href="../coQuanQuanLy/giamSatLuuThong.php" class="the-chi-so bg-white p-4 rounded-2xl border border-emerald-900/10 bong-xanh hover:border-emerald-300 hover:shadow-md transition-all flex flex-col justify-between group">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Lô đang lưu hành</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center text-sm group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-truck-fast"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div id="kpiLoDangLuuHanh" class="text-2xl font-black text-emerald-800 font-mono">1.185</div>
                    </div>
                </a>

                <!-- Thẻ 4: Số lô đã thu hồi -->
                <a href="../coQuanQuanLy/quanLyThuHoi.php" class="the-chi-so bg-white p-4 rounded-2xl border border-emerald-900/10 bong-xanh hover:border-red-300 hover:shadow-md transition-all flex flex-col justify-between group">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Lô đã thu hồi</span>
                        <div class="w-8 h-8 rounded-lg bg-red-50 text-red-700 flex items-center justify-center text-sm group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-ban"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div id="kpiLoDaThuHoi" class="text-2xl font-black text-red-700 font-mono">18</div>
                    </div>
                </a>

                <!-- Thẻ 5: Báo cáo nghi vấn chờ xử lý -->
                <a href="../coQuanQuanLy/xuLyNghiVan.php" class="the-chi-so bg-white p-4 rounded-2xl border border-emerald-900/10 bong-xanh hover:border-amber-300 hover:shadow-md transition-all flex flex-col justify-between group">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Nghi vấn chờ xử lý</span>
                        <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center text-sm group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div id="kpiNghiVanChoXuLy" class="text-2xl font-black text-amber-700 font-mono">5</div>
                    </div>
                </a>

                <!-- Thẻ 6: Hồ sơ đăng ký đang chờ duyệt -->
                <a href="../coQuanQuanLy/pheDuyetTaiKhoanToChuc.php" class="the-chi-so bg-white p-4 rounded-2xl border border-emerald-900/10 bong-xanh hover:border-cyan-300 hover:shadow-md transition-all flex flex-col justify-between group">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Hồ sơ chờ duyệt</span>
                        <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-800 flex items-center justify-center text-sm group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-file-signature"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div id="kpiHoSoChoDuyet" class="text-2xl font-black text-cyan-900 font-mono">3</div>
                    </div>
                </a>

            </div>

            <!-- KHỐI 2: HAI BIỂU ĐỒ (ĐÃ BỎ CÁC DÒNG CHÚ THÍCH PHỤ DƯỚI TIÊU ĐỀ) -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Biểu đồ đường: Số lô đăng ký mới theo tháng -->
                <div class="bg-white p-5 rounded-2xl border border-emerald-900/10 bong-xanh space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                        <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                            <i class="fa-solid fa-chart-line text-indigo-700"></i> Số Lô Thuốc Đăng Ký Mới Theo Tháng
                        </h3>
                        <span class="text-[11px] font-bold font-mono text-indigo-800 bg-indigo-50 px-2.5 py-0.5 rounded border border-indigo-200">Năm 2026</span>
                    </div>
                    <div class="h-64 relative">
                        <canvas id="bieuDoDuongDangKy"></canvas>
                    </div>
                </div>

                <!-- Biểu đồ cột: Số vụ thu hồi theo tháng -->
                <div class="bg-white p-5 rounded-2xl border border-emerald-900/10 bong-xanh space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                        <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                            <i class="fa-solid fa-chart-column text-red-600"></i> Số Vụ Thu Hồi Lô Thuốc Theo Tháng
                        </h3>
                        <span class="text-[11px] font-bold font-mono text-red-700 bg-red-50 px-2.5 py-0.5 rounded border border-red-200">Năm 2026</span>
                    </div>
                    <div class="h-64 relative">
                        <canvas id="bieuDoCotThuHoi"></canvas>
                    </div>
                </div>

            </div>

            <!-- KHỐI 3: BẢNG XẾP HẠNG ĐIỂM NÓNG (ĐÃ BỎ DÒNG MÔ TẢ PHỤ) -->
            <div class="bg-white rounded-2xl border border-emerald-900/10 bong-xanh overflow-hidden">
                <div class="p-4 bg-nenChinh border-b border-emerald-900/10 flex items-center justify-between text-xs">
                    <span class="font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
                        <i class="fa-solid fa-fire text-amber-600"></i> Điểm Nóng Giám Sát: Lô Thuốc & Tổ Chức Bị Báo Cáo Nhiều Nhất
                    </span>
                    <span class="text-[11px] font-mono font-bold text-slate-600">Xếp hạng theo mức độ rủi ro</span>
                </div>

                <div class="w-full overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 text-slate-600 uppercase tracking-wider border-b border-slate-100 text-[11px]">
                                <th class="py-3 px-3 font-bold text-center whitespace-nowrap">Hạng</th>
                                <th class="py-3 px-3 font-bold whitespace-nowrap">Đối Tượng Giám Sát</th>
                                <th class="py-3 px-2.5 font-bold text-center whitespace-nowrap">Phân Loại</th>
                                <th class="py-3 px-3 font-bold">Đơn Vị Trực Thuộc / Phụ Trách</th>
                                <th class="py-3 px-2.5 font-bold text-center whitespace-nowrap">Số Lượt Báo Cáo</th>
                                <th class="py-3 px-3 font-bold">Hành Vi Nghi Vấn Chủ Yếu</th>
                                <th class="py-3 px-2.5 font-bold text-center whitespace-nowrap">Mức Độ Rủi Ro</th>
                                <th class="py-3 px-3 font-bold text-center whitespace-nowrap">Hành Động</th>
                            </tr>
                        </thead>
                        <tbody id="thanBangDiemNong" class="divide-y divide-slate-100 font-medium">
                            <!-- Render bằng JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- Script thực thi biểu đồ & dashboard -->
    <script src="thongKeBaoCao.js"></script>
</body>

</html>
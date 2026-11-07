@extends('layoutDungChung.master')

@section('title', 'Thống kê báo cáo - PharmaChain')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/coQuanQuanLy/thongKeBaoCao.css') }}">
@endpush

@section('content')
<div class="space-y-6 max-w-7xl w-full mx-auto">

    <!-- THANH ĐIỀU KHIỂN: BỘ LỌC THỜI GIAN + NÚT XUẤT BÁO CÁO -->
    <div class="bg-white p-5 rounded-2xl border border-emerald-900/10 bong-xanh flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">

        <div class="flex flex-wrap items-center gap-3">
            <span class="text-xs font-bold text-slate-700 uppercase tracking-wide flex items-center gap-1.5">
                <i class="fa-regular fa-calendar-check text-indigo-700"></i> Khoảng Thời Gian:
            </span>

            <div class="inline-flex p-1 bg-nenChinh rounded-xl border border-emerald-900/15 text-xs font-semibold">
                <button type="button" class="btn-loc-thoigian px-3 py-1.5 rounded-lg transition-all text-slate-600 hover:text-indigo-900" data-range="7_NGAY">
                    7 ngày qua
                </button>
                <button type="button" class="btn-loc-thoigian px-3 py-1.5 rounded-lg transition-all bg-indigo-700 text-white shadow-2xs font-bold" data-range="30_NGAY">
                    30 ngày qua
                </button>
                <button type="button" class="btn-loc-thoigian px-3 py-1.5 rounded-lg transition-all text-slate-600 hover:text-indigo-900" data-range="THANG">
                    Theo tháng
                </button>
                <button type="button" class="btn-loc-thoigian px-3 py-1.5 rounded-lg transition-all text-slate-600 hover:text-indigo-900" data-range="TUY_CHON">
                    Tùy chọn...
                </button>
            </div>

            <!-- Khung chọn Tháng / Năm -->
            <div id="khungChonThang" class="hidden flex items-center gap-2 text-xs">
                <select id="selectThang" class="px-2.5 py-1.5 bg-nenChinh border border-slate-300 rounded-lg outline-none font-semibold text-slate-700">
                    <option value="1">Tháng 1</option>
                    <option value="2">Tháng 2</option>
                    <option value="3">Tháng 3</option>
                    <option value="4">Tháng 4</option>
                    <option value="5">Tháng 5</option>
                    <option value="6">Tháng 6</option>
                    <option value="7">Tháng 7</option>
                    <option value="8">Tháng 8</option>
                    <option value="9">Tháng 9</option>
                    <option value="10">Tháng 10</option>
                    <option value="11" selected>Tháng 11</option>
                    <option value="12">Tháng 12</option>
                </select>
                <select id="selectNam" class="px-2.5 py-1.5 bg-nenChinh border border-slate-300 rounded-lg outline-none font-semibold text-slate-700 font-mono">
                    <option value="2026" selected>2026</option>
                    <option value="2025">2025</option>
                </select>
                <button type="button" id="nutApDungThang" class="px-3 py-1.5 bg-indigo-700 hover:bg-indigo-800 text-white font-bold rounded-lg transition-all">
                    Xem
                </button>
            </div>

            <!-- Khung chọn Ngày tùy chọn -->
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
        <button type="button" id="nutXuatBaoCao" class="px-4 py-2.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-800 border border-indigo-200 rounded-xl text-xs font-bold transition-all shadow-2xs flex items-center justify-center gap-2 shrink-0 cursor-pointer">
            <i class="fa-solid fa-file-arrow-down text-sm text-indigo-700"></i> Xuất Báo Cáo Giám Sát (CSV)
        </button>
    </div>

    <!-- HÀNG 6 THẺ CHỈ SỐ KPI -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3.5">

        <!-- Thẻ 1: Tổng số lô đã đăng ký -->
        <a href="{{ url('/co-quan/phe-duyet-tai-khoan') }}" class="the-chi-so bg-white p-4 rounded-2xl border border-emerald-900/10 bong-xanh hover:border-indigo-300 hover:shadow-md transition-all flex flex-col justify-between group">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Tổng lô đăng ký</span>
                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-700 flex items-center justify-center text-sm group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
            </div>
            <div class="mt-3">
                <div id="kpiTongLoDangKy" class="text-2xl font-black text-slate-900 font-mono">0</div>
            </div>
        </a>

        <!-- Thẻ 2: Số tổ chức đang hoạt động -->
        <a href="{{ url('/co-quan/quan-ly-quyen-han') }}" class="the-chi-so bg-white p-4 rounded-2xl border border-emerald-900/10 bong-xanh hover:border-indigo-300 hover:shadow-md transition-all flex flex-col justify-between group">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Tổ chức hoạt động</span>
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-700 flex items-center justify-center text-sm group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-building-circle-check"></i>
                </div>
            </div>
            <div class="mt-3">
                <div id="kpiTongToChuc" class="text-2xl font-black text-slate-900 font-mono">0</div>
            </div>
        </a>

        <!-- Thẻ 3: Số lô đang lưu hành -->
        <a href="{{ url('/co-quan/quan-ly-thu-hoi') }}" class="the-chi-so bg-white p-4 rounded-2xl border border-emerald-900/10 bong-xanh hover:border-emerald-300 hover:shadow-md transition-all flex flex-col justify-between group">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Lô đang lưu hành</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center text-sm group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-truck-fast"></i>
                </div>
            </div>
            <div class="mt-3">
                <div id="kpiLoDangLuuHanh" class="text-2xl font-black text-emerald-800 font-mono">0</div>
            </div>
        </a>

        <!-- Thẻ 4: Số lô đã thu hồi -->
        <a href="{{ url('/co-quan/quan-ly-thu-hoi') }}" class="the-chi-so bg-white p-4 rounded-2xl border border-emerald-900/10 bong-xanh hover:border-red-300 hover:shadow-md transition-all flex flex-col justify-between group">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Lô đã thu hồi</span>
                <div class="w-8 h-8 rounded-lg bg-red-50 text-red-700 flex items-center justify-center text-sm group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-ban"></i>
                </div>
            </div>
            <div class="mt-3">
                <div id="kpiLoDaThuHoi" class="text-2xl font-black text-red-700 font-mono">0</div>
            </div>
        </a>

        <!-- Thẻ 5: Báo cáo nghi vấn chờ xử lý -->
        <a href="{{ url('/co-quan/xu-ly-nghi-van') }}" class="the-chi-so bg-white p-4 rounded-2xl border border-emerald-900/10 bong-xanh hover:border-amber-300 hover:shadow-md transition-all flex flex-col justify-between group">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Nghi vấn chờ xử lý</span>
                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center text-sm group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
            </div>
            <div class="mt-3">
                <div id="kpiNghiVanChoXuLy" class="text-2xl font-black text-amber-700 font-mono">0</div>
            </div>
        </a>

        <!-- Thẻ 6: Hồ sơ đăng ký đang chờ duyệt -->
        <a href="{{ url('/co-quan/phe-duyet-tai-khoan') }}" class="the-chi-so bg-white p-4 rounded-2xl border border-emerald-900/10 bong-xanh hover:border-cyan-300 hover:shadow-md transition-all flex flex-col justify-between group">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Hồ sơ chờ duyệt</span>
                <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-800 flex items-center justify-center text-sm group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-file-signature"></i>
                </div>
            </div>
            <div class="mt-3">
                <div id="kpiHoSoChoDuyet" class="text-2xl font-black text-cyan-900 font-mono">0</div>
            </div>
        </a>

    </div>

    <!-- KHỐI 2: HAI BIỂU ĐỒ -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Biểu đồ đường: Số lô đăng ký mới -->
        <div class="bg-white p-5 rounded-2xl border border-emerald-900/10 bong-xanh space-y-3">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-chart-line text-indigo-700"></i> Số Lô Thuốc Đăng Ký Mới
                </h3>
                <span id="nhanBieuDoDuong" class="text-[11px] font-bold font-mono text-indigo-800 bg-indigo-50 px-2.5 py-0.5 rounded border border-indigo-200">30 ngày qua</span>
            </div>
            <div class="h-64 relative">
                <canvas id="bieuDoDuongDangKy"></canvas>
            </div>
        </div>

        <!-- Biểu đồ cột: Số vụ thu hồi -->
        <div class="bg-white p-5 rounded-2xl border border-emerald-900/10 bong-xanh space-y-3">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-chart-column text-red-600"></i> Số Vụ Thu Hồi Lô Thuốc
                </h3>
                <span id="nhanBieuDoCot" class="text-[11px] font-bold font-mono text-red-700 bg-red-50 px-2.5 py-0.5 rounded border border-red-200">30 ngày qua</span>
            </div>
            <div class="h-64 relative">
                <canvas id="bieuDoCotThuHoi"></canvas>
            </div>
        </div>

    </div>

    <!-- KHỐI 3: TÁCH RIÊNG 2 BẢNG THỐNG KÊ ĐIỂM NÓNG -->

    <!-- BẢNG 1: TOP LÔ THUỐC BỊ BÁO CÁO NHIỀU NHẤT -->
    <div class="bg-white rounded-2xl border border-emerald-900/10 bong-xanh overflow-hidden">
        <div class="p-4 bg-nenChinh border-b border-emerald-900/10 flex items-center justify-between text-xs">
            <span class="font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
                <i class="fa-solid fa-boxes-packing text-indigo-700"></i> Top Lô Thuốc Bị Báo Cáo Nghi Vấn Nhiều Nhất
            </span>
            <span class="text-[11px] font-mono font-bold text-slate-500">Xếp hạng theo số lượt nghi vấn</span>
        </div>

        <div class="w-full overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 uppercase tracking-wider border-b border-slate-100 text-[11px]">
                        <th class="py-3.5 px-3 font-bold text-center whitespace-nowrap">Hạng</th>
                        <th class="py-3.5 px-3 font-bold whitespace-nowrap">Mã Lô Thuốc</th>
                        <th class="py-3.5 px-3 font-bold">Tên Sản Phẩm / Số ĐK</th>
                        <th class="py-3.5 px-3 font-bold">Đơn Vị Sản Xuất / Quản Lý</th>
                        <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Số Lượt Báo Cáo</th>
                        <th class="py-3.5 px-3 font-bold">Nội Dung Nghi Vấn Chủ Yếu</th>
                        <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Mức Độ Rủi Ro</th>
                        <th class="py-3.5 px-3 font-bold text-center whitespace-nowrap">Hành Động</th>
                    </tr>
                </thead>
                <tbody id="thanBangLoThuocNghiVan" class="divide-y divide-slate-100 font-medium">
                    <!-- Render bằng JavaScript -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- BẢNG 2: TOP TỔ CHỨC BỊ BÁO CÁO NHIỀU NHẤT -->
    <div class="bg-white rounded-2xl border border-emerald-900/10 bong-xanh overflow-hidden">
        <div class="p-4 bg-nenChinh border-b border-emerald-900/10 flex items-center justify-between text-xs">
            <span class="font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
                <i class="fa-solid fa-building-circle-exclamation text-amber-600"></i> Top Tổ Chức Bị Báo Cáo Nghi Vấn Nhiều Nhất
            </span>
            <span class="text-[11px] font-mono font-bold text-slate-500">Xếp hạng theo số lượt nghi vấn liên quan</span>
        </div>

        <div class="w-full overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 uppercase tracking-wider border-b border-slate-100 text-[11px]">
                        <th class="py-3.5 px-3 font-bold text-center whitespace-nowrap">Hạng</th>
                        <th class="py-3.5 px-3 font-bold whitespace-nowrap">Tên Tổ Chức</th>
                        <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Loại Tổ Chức</th>
                        <th class="py-3.5 px-3 font-bold">Mã Số Thuế / Giấy Phép</th>
                        <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Số Lượt Báo Cáo</th>
                        <th class="py-3.5 px-3 font-bold">Hành Vi Nghi Vấn Gần Nhất</th>
                        <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Mức Độ Rủi Ro</th>
                        <th class="py-3.5 px-3 font-bold text-center whitespace-nowrap">Hành Động</th>
                    </tr>
                </thead>
                <tbody id="thanBangToChucNghiVan" class="divide-y divide-slate-100 font-medium">
                    <!-- Render bằng JavaScript -->
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="{{ asset('assets/js/coQuanQuanLy/thongKeBaoCao.js') }}"></script>
@endpush
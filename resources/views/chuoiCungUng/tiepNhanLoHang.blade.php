@extends('layouts.app')
@section('title', 'Tiếp Nhận Lô Hàng – Cổng Nhà Thuốc')
@section('panelTitle', 'Phân Hệ Tiếp Nhận Lô Hàng & Nhập Kho Bán Lẻ')

@section('content')
<!-- THANH CÔNG CỤ: TÌM KIẾM + BỘ LỌC + THÔNG BÁO QUY TRÌNH -->
            <div class="bg-white p-5 rounded-2xl border border-emerald-900/10 bong-xanh flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4">

                <div class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <!-- Ô tìm kiếm -->
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 text-xs pointer-events-none">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </span>
                        <input type="text" id="oTimKiemNhan" placeholder="Tìm theo mã giao dịch, mã lô, tên thuốc..." class="w-full pl-9 pr-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-medium text-slate-900 focus:bg-white focus:border-cyan-700 outline-none">
                    </div>

                    <!-- Lọc theo Nhà phân phối gửi -->
                    <select id="locNhaPhanPhoi" class="px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-medium text-slate-700 focus:bg-white focus:border-cyan-700 outline-none cursor-pointer">
                        <option value="TAT_CA">-- Tất cả Nhà phân phối --</option>
                        <option value="CPC1">Dược Phẩm CPC1 Hà Nội</option>
                        <option value="VINAPHARM">Tổng Công ty Vinapharm</option>
                        <option value="VIMEDIMEX">Dược Phẩm Vimedimex TP.HCM</option>
                    </select>

                    <!-- Lọc theo Hạn sử dụng -->
                    <select id="locHanDung" class="px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-medium text-slate-700 focus:bg-white focus:border-cyan-700 outline-none cursor-pointer">
                        <option value="TAT_CA">-- Hạn sử dụng --</option>
                        <option value="CON_HAN">Còn hạn dài (> 12 tháng)</option>
                        <option value="CAN_HAN">Cận date (dưới 6 tháng)</option>
                    </select>
                </div>

                <!-- Chú thích nghiệp vụ -->
                <div class="p-2.5 bg-cyan-50 rounded-xl border border-cyan-800/20 text-xs text-cyan-950 flex items-center gap-2 shrink-0">
                    <i class="fa-solid fa-circle-info text-cyan-800 shrink-0"></i>
                    <span>Kiểm tra niêm phong thùng thuốc trước khi xác nhận nhập kho bán lẻ.</span>
                </div>
            </div>

            <!-- BẢNG DANH SÁCH LÔ THUỐC CHỜ TIẾP NHẬN -->
            <div class="bg-white rounded-2xl border border-emerald-900/10 bong-xanh overflow-hidden">
                <div class="p-4 bg-nenChinh border-b border-emerald-900/10 flex items-center justify-between text-xs">
                    <span class="font-bold text-slate-700 uppercase tracking-wide flex items-center gap-2">
                        <i class="fa-solid fa-truck-ramp-box text-cyan-700"></i> Danh Sách Lô Thuốc Đang Chờ Nhà Thuốc Nhận Hàng
                    </span>
                    <span id="tongSoLôHienThi" class="font-bold text-slate-500 font-mono">Hiển thị: 0 lô</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 text-slate-600 uppercase tracking-wider border-b border-slate-100">
                                <th class="py-3.5 px-4 font-bold">Mã Giao Dịch</th>
                                <th class="py-3.5 px-4 font-bold">Mã Lô Thuốc</th>
                                <th class="py-3.5 px-4 font-bold">Tên Thuốc / Sản Phẩm</th>
                                <th class="py-3.5 px-4 font-bold">Nhà Phân Phối Gửi</th>
                                <th class="py-3.5 px-4 font-bold text-right">Số Lượng Kiện</th>
                                <th class="py-3.5 px-4 font-bold">Hạn Dùng</th>
                                <th class="py-3.5 px-4 font-bold">Ngày Khởi Tạo</th>
                                <th class="py-3.5 px-4 font-bold text-center">Trạng Thái</th>
                                <th class="py-3.5 px-4 font-bold text-center">Hành Động</th>
                            </tr>
                        </thead>
                        <tbody id="thanBangTiepNhan" class="divide-y divide-slate-100 font-medium">
                            <!-- Render bằng file JS -->
                        </tbody>
                    </table>
                </div>

                <!-- PHÂN TRANG BẢNG TIẾP NHẬN -->
                <div class="p-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                    <span id="thongTinPhanTrang" class="text-slate-500">Đang hiển thị...</span>
                    <div class="flex items-center gap-1.5" id="cumNutPhanTrang">
                        <!-- Nút phân trang render qua JS -->
                    </div>
                </div>
            </div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/chuoiCungUng/tiepNhanLoHang.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/js/chuoiCungUng/tiepNhanLoHang.js') }}" defer></script>
@endpush

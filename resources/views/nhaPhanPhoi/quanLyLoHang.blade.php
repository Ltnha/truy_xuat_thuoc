@extends('layoutDungChung.master')

@section('title', 'Quản lý lô hàng - PharmaChain')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/nhaPhanPhoi/quanLyLoHang.css') }}">
@endpush

@section('content')
<div class="space-y-6 max-w-7xl w-full mx-auto">

    <!-- CỤM CHUYỂN TAB CHÍNH CỦA NHÀ PHÂN PHỐI (THUẦN VIỆT HÓA) -->
    <div class="bg-white rounded-2xl border border-emerald-900/10 bong-xanh p-2 flex gap-2">
        <!-- Tab 1: Chờ tiếp nhận -->
        <button type="button" id="tabChoTiepNhan" class="flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all bg-blue-700 text-white shadow-sm">
            <i class="fa-solid fa-inbox text-base"></i>
            <span>Lô Hàng Chờ Tiếp Nhận (Chờ xác nhận)</span>
            <span id="badgeSoLuongPending" class="ml-1 px-2 py-0.5 rounded-full text-[11px] font-black bg-white text-blue-700">0</span>
        </button>

        <!-- Tab 2: Kho lưu giữ & Chuyển tiếp -->
        <button type="button" id="tabKhoLuuGiu" class="flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all text-slate-600 hover:text-blue-700 hover:bg-blue-50">
            <i class="fa-solid fa-warehouse text-base"></i>
            <span>Kho Hàng Đang Lưu Giữ & Chuyển Tiếp</span>
            <span id="badgeSoLuongKho" class="ml-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600">0</span>
        </button>
    </div>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- PHÂN HỆ 1: LÔ HÀNG CHỜ TIẾP NHẬN (chuyenGiao: PENDING)    -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <section id="khungTabChoTiepNhan" class="space-y-6">
        <!-- Thanh công cụ: TÌM KIẾM + BỘ LỌC + NÚT QUÉT QR THÙNG -->
        <div class="bg-white p-5 rounded-2xl border border-emerald-900/10 bong-xanh flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
            <div class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-3">
                <!-- Ô tìm kiếm -->
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 text-xs pointer-events-none">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" id="oTimKiemPending" placeholder="Tìm theo mã GD, mã lô, tên thuốc..." class="w-full pl-9 pr-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-medium text-slate-900 focus:bg-white focus:border-blue-700 outline-none">
                </div>

                <!-- Bộ lọc theo Đơn vị gửi (Nhà sản xuất) -->
                <select id="locBenGuiPending" class="px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-medium text-slate-700 focus:bg-white focus:border-blue-700 outline-none cursor-pointer">
                    <option value="TAT_CA">-- Tất cả nhà sản xuất gửi --</option>
                    <option value="MEDIPHARCO">Dược Phẩm Medipharco</option>
                    <option value="OPV">Dược Phẩm OPV</option>
                    <option value="PHARBACO">Dược Phẩm Pharbaco</option>
                </select>

                <!-- Bộ lọc theo Hạn sử dụng -->
                <select id="locHanDungPending" class="px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-medium text-slate-700 focus:bg-white focus:border-blue-700 outline-none cursor-pointer">
                    <option value="TAT_CA">-- Thời hạn sử dụng --</option>
                    <option value="CON_HAN">Còn hạn lưu hành</option>
                    <option value="CAN_HAN">Cận date (dưới 6 tháng)</option>
                    <option value="HET_HAN">Đã hết hạn</option>
                </select>
            </div>

            <!-- Nút Quét nhanh QR lô thùng -->
            <button type="button" id="nutQuetQRNhanHang" class="px-4 py-2.5 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 rounded-xl text-xs font-bold transition-all shadow-2xs flex items-center justify-center gap-2 shrink-0">
                <i class="fa-solid fa-qrcode text-sm"></i> Quét QR Thùng Nhận Hàng
            </button>
        </div>

        <!-- Bảng danh sách hàng chờ tiếp nhận -->
        <div class="bg-white rounded-2xl border border-emerald-900/10 bong-xanh overflow-hidden">
            <div class="p-4 bg-nenChinh border-b border-emerald-900/10 flex items-center justify-between text-xs">
                <span class="font-bold text-slate-700 uppercase tracking-wide flex items-center gap-2">
                    <i class="fa-solid fa-dolly text-blue-700"></i> Danh Sách Lô Hàng Đang Chờ Nhập Kho
                </span>
                <span id="soLuongPendingHienThi" class="font-bold text-slate-500 font-mono">Hiển thị: 0 lô</span>
            </div>

            <div class="w-full overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 uppercase tracking-wider border-b border-slate-100 text-[11px]">
                            <th class="py-3 px-3 font-bold whitespace-nowrap">Mã GD</th>
                            <th class="py-3 px-3 font-bold whitespace-nowrap">Mã Lô</th>
                            <th class="py-3 px-3 font-bold">Tên Sản Phẩm</th>
                            <th class="py-3 px-3 font-bold">Nhà Sản Xuất Gửi</th>
                            <th class="py-3 px-2.5 font-bold text-center whitespace-nowrap">Hạn Dùng</th>
                            <th class="py-3 px-3 font-bold text-right whitespace-nowrap">Số Lượng</th>
                            <th class="py-3 px-2.5 font-bold text-center whitespace-nowrap">Thời Điểm Gửi</th>
                            <th class="py-3 px-2.5 font-bold text-center whitespace-nowrap">Trạng Thái</th>
                            <th class="py-3 px-3 font-bold text-center whitespace-nowrap">Hành Động</th>
                        </tr>
                    </thead>
                    <tbody id="thanBangPending" class="divide-y divide-slate-100 font-medium">
                        <!-- Render qua JS -->
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                <span id="thongTinPhanTrangPending" class="text-slate-500">Đang hiển thị...</span>
                <div class="flex items-center gap-1.5" id="cumNutPhanTrangPending"></div>
            </div>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- PHÂN HỆ 2: KHO HÀNG ĐANG LƯU GIỮ & CHUYỂN TIẾP (tonKhoLo)  -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <section id="khungTabKhoLuuGiu" class="hidden space-y-6">
        <!-- Thanh công cụ Tab 2 -->
        <div class="bg-white p-5 rounded-2xl border border-emerald-900/10 bong-xanh">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 text-xs pointer-events-none">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" id="oTimKiemKho" placeholder="Tìm theo mã lô hoặc tên thuốc..." class="w-full pl-9 pr-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-medium text-slate-900 focus:bg-white focus:border-blue-700 outline-none">
                </div>

                <!-- Bộ lọc trạng thái thuần Việt -->
                <select id="locTrangThaiKho" class="px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-medium text-slate-700 focus:bg-white focus:border-blue-700 outline-none cursor-pointer">
                    <option value="TAT_CA">-- Tất cả trạng thái kho --</option>
                    <option value="DANG_GIU">Đang giữ</option>
                    <option value="DA_CHUYEN_HET">Đã chuyển hết</option>
                    <option value="HET_HAN">Đã hết hạn sử dụng</option>
                    <option value="RECALLED">Đã thu hồi</option>
                </select>

                <select id="locHanDungKho" class="px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-medium text-slate-700 focus:bg-white focus:border-blue-700 outline-none cursor-pointer">
                    <option value="TAT_CA">-- Hạn sử dụng --</option>
                    <option value="CON_HAN">Còn hạn lưu hành</option>
                    <option value="DA_HET_HAN">Đã quá hạn</option>
                </select>
            </div>
        </div>

        <!-- Bảng danh sách kho hàng -->
        <div class="bg-white rounded-2xl border border-emerald-900/10 bong-xanh overflow-hidden">
            <div class="p-4 bg-nenChinh border-b border-emerald-900/10 flex items-center justify-between text-xs">
                <span class="font-bold text-slate-700 uppercase tracking-wide flex items-center gap-2">
                    <i class="fa-solid fa-boxes-stacked text-blue-700"></i> Danh Sách Lô Hàng Khả Dụng Trong Kho
                </span>
                <span id="soLuongKhoHienThi" class="font-bold text-slate-500 font-mono">Hiển thị: 0 lô</span>
            </div>

            <div class="w-full overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 uppercase tracking-wider border-b border-slate-100 text-[11px]">
                            <th class="py-3 px-3 font-bold whitespace-nowrap">Mã Lô</th>
                            <th class="py-3 px-2 font-bold text-center whitespace-nowrap">Tem QR Thùng</th>
                            <th class="py-3 px-3 font-bold">Sản Phẩm</th>
                            <th class="py-3 px-2.5 font-bold text-center whitespace-nowrap">Hạn Dùng</th>
                            <th class="py-3 px-3 font-bold text-right whitespace-nowrap">Tổng Nhập</th>
                            <th class="py-3 px-3 font-bold text-right whitespace-nowrap">Khả Dụng</th>
                            <th class="py-3 px-2.5 font-bold text-center whitespace-nowrap">Trạng Thái</th>
                            <th class="py-3 px-3 font-bold text-center whitespace-nowrap">Hành Động</th>
                        </tr>
                    </thead>
                    <tbody id="thanBangKho" class="divide-y divide-slate-100 font-medium">
                        <!-- Render qua JS -->
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                <span id="thongTinPhanTrangKho" class="text-slate-500">Đang hiển thị...</span>
                <div class="flex items-center gap-1.5" id="cumNutPhanTrangKho"></div>
            </div>
        </div>
    </section>

</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!-- MODAL 1: ĐỐI CHIẾU THÔNG TIN & XÁC NHẬN NHẬN HÀNG (FR08)  -->
<!-- ══════════════════════════════════════════════════════════ -->
<div id="modalXacNhanNhan" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden bg-black/60">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 sm:p-8 space-y-5 bong-xanh">
        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-clipboard-check text-blue-700"></i> Đối Chiếu Thông Tin & Xác Nhận Nhận Hàng
            </h3>
            <button type="button" class="dong-modal-nhan text-slate-400 hover:text-slate-700 text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="space-y-4 text-xs">
            <div class="p-4 bg-nenChinh rounded-xl border border-emerald-900/10 space-y-2.5">
                <div class="flex justify-between items-center">
                    <span class="text-slate-500">Mã giao dịch chuyển giao:</span>
                    <div class="flex items-center gap-1.5">
                        <span id="nhanMaGiaoDich" class="font-mono font-bold text-cyan-800"></span>
                        <button type="button" id="nutCopyMaGDNhan" class="text-slate-400 hover:text-cyan-800 text-xs p-1" title="Sao chép mã giao dịch">
                            <i class="fa-regular fa-copy"></i>
                        </button>
                    </div>
                </div>

                <div class="flex justify-between items-center pt-1 border-t border-slate-200/60">
                    <span class="text-slate-700 font-bold">Đối chiếu blockchain:</span>
                    <span id="nhanKetQuaDoiChieu"></span>
                </div>

                <div class="flex justify-between">
                    <span class="text-slate-500">Mã lô thuốc:</span>
                    <span id="nhanMaLo" class="font-mono font-bold text-slate-900 text-sm"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Tên thuốc:</span>
                    <span id="nhanTenSanPham" class="font-bold text-slate-900"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Nhà sản xuất gửi hàng:</span>
                    <span id="nhanBenGui" class="font-semibold text-slate-800"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Số lượng bàn giao:</span>
                    <span id="nhanSoLuong" class="font-bold text-blue-700 font-mono text-sm"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Hạn sử dụng:</span>
                    <span id="nhanHanDung" class="font-semibold text-slate-700 font-mono"></span>
                </div>
            </div>

            <div class="p-3 bg-blue-50 rounded-xl border border-blue-200 text-[11px] text-blue-900 leading-relaxed">
                <i class="fa-solid fa-cube text-blue-700 mr-1"></i> Hệ thống sẽ ghi nhận bạn đã nhận hàng lên blockchain và cộng số lượng vào kho của bạn.
            </div>

            <div class="pt-1 text-center">
                <a id="linkBaoCaoTuNhanHang" href="#" class="inline-flex items-center gap-1.5 text-amber-700 hover:text-amber-900 font-bold hover:underline text-xs">
                    <i class="fa-solid fa-triangle-exclamation"></i> Hàng không khớp thực tế? Báo cáo nghi vấn ngay
                </a>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
            <button type="button" class="dong-modal-nhan px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-all">
                Đối chiếu lại
            </button>
            <button type="button" id="nutXacNhanNhapKho" class="px-6 py-2.5 bg-blue-700 hover:bg-blue-800 text-white font-bold rounded-xl shadow-md transition-all flex items-center gap-2">
                <i class="fa-solid fa-check-double"></i> Xác Nhận Nhập Kho
            </button>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!-- MODAL 2: XEM CHI TIẾT LÔ HÀNG ĐANG CHỜ TIẾP NHẬN          -->
<!-- ══════════════════════════════════════════════════════════ -->
<div id="modalChiTietPending" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden bg-black/60">
    <div class="bg-white rounded-2xl max-w-xl w-full p-6 sm:p-8 space-y-5 bong-xanh max-h-[90vh] overflow-y-auto">
        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-base font-bold text-slate-900">Chi Tiết Lô Hàng Chờ Nhận:</span>
                <span id="pendingModalMaLo" class="font-mono font-bold text-blue-800 bg-blue-50 px-2.5 py-0.5 rounded text-xs border border-blue-200"></span>
            </div>
            <button type="button" class="dong-modal-pending-detail text-slate-400 hover:text-slate-700 text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="grid grid-cols-2 gap-3 p-4 bg-nenChinh rounded-xl border border-emerald-900/10 text-xs">
            <div>
                <span class="text-slate-500 block">Sản phẩm:</span>
                <span id="pendingModalSanPham" class="font-bold text-slate-900"></span>
            </div>
            <div>
                <span class="text-slate-500 block">Nhà sản xuất gửi:</span>
                <span id="pendingModalBenGui" class="font-bold text-slate-800"></span>
            </div>
            <div>
                <span class="text-slate-500 block">Số lượng bàn giao:</span>
                <span id="pendingModalSoLuong" class="font-bold text-blue-700 font-mono text-sm"></span>
            </div>
            <div>
                <span class="text-slate-500 block">Hạn dùng:</span>
                <span id="pendingModalHanDung" class="font-semibold text-slate-800 font-mono"></span>
            </div>
            <div>
                <span class="text-slate-500 block">Thời điểm gửi:</span>
                <span id="pendingModalNgayGui" class="font-semibold text-slate-700 font-mono"></span>
            </div>
            <div>
                <span class="text-slate-500 block">Trạng thái:</span>
                <span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300">
                    <i class="fa-solid fa-clock"></i> Chờ xác nhận
                </span>
            </div>
        </div>

        <div class="space-y-1.5 text-xs">
            <span class="font-bold text-slate-700">Mã giao dịch ghi nhận lô trên blockchain:</span>
            <div class="flex items-center gap-2 p-2.5 bg-slate-50 border border-slate-200 rounded-xl">
                <a id="pendingModalTxLink" href="#" target="_blank" class="flex-1 font-mono text-[11px] text-cyan-800 hover:underline break-all flex items-center gap-1.5">
                    <span id="pendingModalTxHash">0x...</span>
                    <i class="fa-solid fa-arrow-up-right-from-square text-[9px] text-slate-400 shrink-0"></i>
                </a>
                <button type="button" id="nutCopyPendingTx" class="px-2.5 py-1 bg-white hover:bg-slate-100 border border-slate-300 text-slate-700 rounded-lg text-[11px] font-bold shrink-0">
                    <i class="fa-regular fa-copy"></i> Sao chép
                </button>
            </div>
        </div>

        <div class="pt-3 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
            <a id="linkBaoCaoTuPendingDetail" href="#" class="text-xs text-amber-700 hover:text-amber-900 font-bold hover:underline flex items-center gap-1.5">
                <i class="fa-solid fa-triangle-exclamation"></i> Báo cáo nghi vấn lô này
            </a>

            <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                <button type="button" class="dong-modal-pending-detail px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-all">
                    Đóng
                </button>
                <button type="button" id="nutNhanHangTuDetail" class="px-5 py-2 bg-blue-700 hover:bg-blue-800 text-white text-xs font-bold rounded-xl shadow-md transition-all flex items-center gap-1.5">
                    <i class="fa-solid fa-check"></i> Chuyển Sang Nhận Hàng
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!-- MODAL 3: KHỞI TẠO CHUYỂN TIẾP LÔ HÀNG (FR07)              -->
<!-- ══════════════════════════════════════════════════════════ -->
<div id="modalChuyenTiep" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden bg-black/60">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 sm:p-8 space-y-5 bong-xanh">
        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-truck-arrow-right text-blue-700"></i> Khởi Tạo Chuyển Tiếp Lô Hàng
            </h3>
            <button type="button" class="dong-modal-chuyen text-slate-400 hover:text-slate-700 text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="formSubmitChuyenTiep" class="space-y-4 text-xs">
            <div class="p-3 bg-nenChinh rounded-xl border border-emerald-900/10 space-y-1.5">
                <div class="flex justify-between">
                    <span class="text-slate-500">Mã lô xuất kho:</span>
                    <span id="chuyenMaLoText" class="font-mono font-bold text-slate-900"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Số lượng khả dụng tại kho:</span>
                    <span id="chuyenSoLuongKhaDungText" class="font-bold text-emerald-700 font-mono"></span>
                </div>
            </div>

            <div class="space-y-1">
                <label for="chonDoiTacNhan" class="font-bold text-slate-700">Chọn Nhà Thuốc Tiếp Nhận (Đã Phê Duyệt) <span class="text-red-500">*</span></label>
                <select id="chonDoiTacNhan" required class="w-full px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl font-medium text-slate-900 focus:bg-white focus:border-blue-700 outline-none">
                    <!-- Render danh sách qua JS -->
                </select>
            </div>

            <div class="space-y-1">
                <label for="nhapSoLuongChuyen" class="font-bold text-slate-700">Số Lượng Chuyển Tiếp (hộp) <span class="text-red-500">*</span></label>
                <input type="number" id="nhapSoLuongChuyen" required min="1" placeholder="Nhập số lượng xuất..." class="w-full px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl font-semibold text-slate-900 focus:bg-white focus:border-blue-700 outline-none">
                <span id="loiSoLuongChuyen" class="text-[11px] text-red-600 hidden font-bold">Số lượng xuất không được vượt quá số khả dụng trong kho!</span>
            </div>

            <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-[11px] text-amber-800 leading-relaxed">
                <i class="fa-solid fa-triangle-exclamation text-amber-600 mr-1"></i> Giao dịch chuyển tiếp sẽ khởi tạo trạng thái <strong>Chờ xác nhận</strong>. Lượng hàng này sẽ tạm trừ khỏi số khả dụng để tránh phân phối trùng lặp.
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                <button type="button" class="dong-modal-chuyen px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-all">
                    Hủy
                </button>
                <button type="submit" class="px-6 py-2.5 bg-blue-700 hover:bg-blue-800 text-white font-bold rounded-xl shadow-md transition-all flex items-center gap-2">
                    <i class="fa-solid fa-paper-plane"></i> Gửi Yêu Cầu Chuyển Tiếp
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!-- MODAL 4: XEM CHI TIẾT LÔ HÀNG TRONG KHO & DÒNG THỜI GIAN   -->
<!-- ══════════════════════════════════════════════════════════ -->
<div id="modalChiTietKho" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden bg-black/60">
    <div class="bg-white rounded-2xl max-w-2xl w-full p-6 sm:p-8 space-y-6 bong-xanh max-h-[90vh] overflow-y-auto">
        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-base font-bold text-slate-900">Chi Tiết Lô Hàng Trong Kho:</span>
                <span id="modalKhoMaLoTitle" class="font-mono font-bold text-blue-800 bg-blue-50 px-2.5 py-0.5 rounded text-xs border border-blue-200"></span>
            </div>
            <button type="button" class="dong-modal-chitiet text-slate-400 hover:text-slate-700 text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-4 bg-nenChinh rounded-xl border border-emerald-900/10 text-xs">
            <div>
                <span class="text-slate-500 block">Sản phẩm:</span>
                <span id="modalKhoSanPham" class="font-bold text-slate-900"></span>
            </div>
            <div>
                <span class="text-slate-500 block">Hạn dùng:</span>
                <span id="modalKhoHanDung" class="font-semibold text-slate-800 font-mono"></span>
            </div>
            <div>
                <span class="text-slate-500 block">Tổng nhập:</span>
                <span id="modalKhoTongNhap" class="font-bold text-slate-800 font-mono"></span>
            </div>
            <div>
                <span class="text-slate-500 block">Khả dụng còn lại:</span>
                <span id="modalKhoKhaDung" class="font-bold text-emerald-700 font-mono"></span>
            </div>
        </div>

        <div class="space-y-1.5 text-xs">
            <span class="font-bold text-slate-700">Mã giao dịch ghi nhận lô trên blockchain:</span>
            <div class="flex items-center gap-2 p-2.5 bg-slate-50 border border-slate-200 rounded-xl">
                <a id="modalKhoTxLink" href="#" target="_blank" class="flex-1 font-mono text-[11px] text-cyan-800 hover:underline break-all flex items-center gap-1.5">
                    <span id="modalKhoTxHash">0x...</span>
                    <i class="fa-solid fa-arrow-up-right-from-square text-[9px] text-slate-400 shrink-0"></i>
                </a>
                <button type="button" id="nutCopyModalKhoTx" class="px-2.5 py-1 bg-white hover:bg-slate-100 border border-slate-300 text-slate-700 rounded-lg text-[11px] font-bold shrink-0">
                    <i class="fa-regular fa-copy"></i> Sao chép
                </button>
            </div>
        </div>

        <div class="space-y-3 text-xs pt-1">
            <h4 class="font-bold uppercase tracking-wider text-slate-700 flex items-center gap-2">
                <i class="fa-solid fa-timeline text-blue-700"></i> Lịch Sử Giao Nhận & Chuyển Tiếp Tại Kho
            </h4>
            <div id="danhSachTimelineKho" class="relative pl-6 border-l-2 border-blue-900/20 space-y-4"></div>
        </div>

        <div class="pt-3 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
            <a id="linkBaoCaoTuKhoDetail" href="#" class="text-xs text-amber-700 hover:text-amber-900 font-bold hover:underline flex items-center gap-1.5">
                <i class="fa-solid fa-triangle-exclamation"></i> Báo cáo nghi vấn lô này
            </a>
            <button type="button" class="dong-modal-chitiet px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-all">
                Đóng Cửa Sổ
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/nhaPhanPhoi/quanLyLoHang.js') }}"></script>
@endpush
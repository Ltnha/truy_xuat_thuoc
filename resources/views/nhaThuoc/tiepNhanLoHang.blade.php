@extends('layoutDungChung.master')

@section('title', $tieuDeTrang ?? 'Tiếp nhận lô hàng - PharmaChain')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/nhaThuoc/tiepNhanLoHang.css') }}">
@endpush

@section('content')
<div class="space-y-6 max-w-7xl w-full mx-auto">

    <!-- CỤM CHUYỂN TAB 2 BƯỚC: CHỜ XÁC NHẬN & ĐÃ TIẾP NHẬN -->
    <div class="bg-white rounded-2xl border border-emerald-900/10 bong-xanh p-2 flex gap-2">
        <button type="button" id="tabChoXacNhan" class="flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all bg-cyan-700 text-white shadow-sm">
            <i class="fa-solid fa-inbox text-base"></i>
            <span>Lô Hàng Chờ Xác Nhận</span>
            <span id="badgeSoLuongChoXacNhan" class="ml-1 px-2 py-0.5 rounded-full text-[11px] font-black bg-white text-cyan-800">0</span>
        </button>

        <button type="button" id="tabDaTiepNhan" class="flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all text-slate-600 hover:text-cyan-700 hover:bg-cyan-50">
            <i class="fa-solid fa-clock-rotate-left text-base"></i>
            <span>Lịch Sử Đã Tiếp Nhận</span>
            <span id="badgeSoLuongDaNhan" class="ml-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600">0</span>
        </button>
    </div>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- TAB 1: DANH SÁCH LÔ HÀNG CHỜ XÁC NHẬN                    -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <section id="khungTabChoXacNhan" class="space-y-6">
        <!-- Thanh công cụ lọc & tìm kiếm -->
        <div class="bg-white p-5 rounded-2xl border border-emerald-900/10 bong-xanh flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
            <div class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 text-xs pointer-events-none">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" id="oTimKiemNhan" placeholder="Tìm theo mã GD, mã lô, tên thuốc..." class="w-full pl-9 pr-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-medium text-slate-900 focus:bg-white focus:border-cyan-700 outline-none">
                </div>

                <select id="locNhaPhanPhoi" class="px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-medium text-slate-700 focus:bg-white focus:border-cyan-700 outline-none cursor-pointer">
                    <option value="TAT_CA">-- Tất cả nhà phân phối --</option>
                    <option value="VINAPHARM">Vinapharm Tổng Kho</option>
                    <option value="CPC1">Dược Phẩm CPC1 Hà Nội</option>
                    <option value="VIMEDIMEX">Dược Phẩm Vimedimex</option>
                </select>

                <select id="locHanDung" class="px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-medium text-slate-700 focus:bg-white focus:border-cyan-700 outline-none cursor-pointer">
                    <option value="TAT_CA">-- Hạn sử dụng --</option>
                    <option value="CON_HAN">Còn hạn lưu hành</option>
                    <option value="HET_HAN">Đã hết hạn</option>
                </select>
            </div>

            <button type="button" id="nutQuetQRNhanHang" class="px-4 py-2.5 bg-cyan-50 hover:bg-cyan-100 text-cyan-800 border border-cyan-200 rounded-xl text-xs font-bold transition-all shadow-2xs flex items-center justify-center gap-2 shrink-0">
                <i class="fa-solid fa-qrcode text-sm"></i> Quét QR Thùng
            </button>
        </div>

        <!-- Bảng danh sách chờ nhận -->
        <div class="bg-white rounded-2xl border border-emerald-900/10 bong-xanh overflow-hidden">
            <div class="p-4 bg-nenChinh border-b border-emerald-900/10 flex items-center justify-between text-xs">
                <span class="font-bold text-slate-700 uppercase tracking-wide flex items-center gap-2">
                    <i class="fa-solid fa-truck-ramp-box text-cyan-700"></i> Danh Sách Lô Thuốc Đang Chờ Xác Nhận
                </span>
                <span id="tongSoLoHienThi" class="font-bold text-slate-500 font-mono">Hiển thị: 0 lô</span>
            </div>

            <div class="w-full overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 uppercase tracking-wider border-b border-slate-100 text-[11px]">
                            <th class="py-3.5 px-3 font-bold whitespace-nowrap">Mã GD</th>
                            <th class="py-3.5 px-3 font-bold whitespace-nowrap">Mã Lô</th>
                            <th class="py-3.5 px-3 font-bold">Tên Thuốc</th>
                            <th class="py-3.5 px-3 font-bold">Nhà Phân Phối</th>
                            <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Hạn Dùng</th>
                            <th class="py-3.5 px-3 font-bold text-right whitespace-nowrap">Số Lượng</th>
                            <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Ngày Gửi</th>
                            <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Trạng Thái</th>
                            <th class="py-3.5 px-3 font-bold text-center whitespace-nowrap">Hành Động</th>
                        </tr>
                    </thead>
                    <tbody id="thanBangTiepNhan" class="divide-y divide-slate-100 font-medium">
                        <!-- Render bằng JS -->
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                <span id="thongTinPhanTrang" class="text-slate-500">Đang hiển thị...</span>
                <div class="flex items-center gap-1.5" id="cumNutPhanTrang"></div>
            </div>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- TAB 2: DANH SÁCH LÔ HÀNG ĐÃ TIẾP NHẬN THÀNH CÔNG          -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <section id="khungTabDaTiepNhan" class="hidden space-y-6">
        <div class="bg-white rounded-2xl border border-emerald-900/10 bong-xanh overflow-hidden">
            <div class="p-4 bg-nenChinh border-b border-emerald-900/10 flex items-center justify-between text-xs">
                <span class="font-bold text-slate-700 uppercase tracking-wide flex items-center gap-2">
                    <i class="fa-solid fa-boxes-stacked text-emerald-700"></i> Lịch Sử Lô Thuốc Đã Tiếp Nhận Thành Công
                </span>
                <span id="soLuongDaTiepNhanHienThi" class="font-bold text-slate-500 font-mono">Hiển thị: 0 lô</span>
            </div>

            <div class="w-full overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 uppercase tracking-wider border-b border-slate-100 text-[11px]">
                            <th class="py-3.5 px-3 font-bold whitespace-nowrap">Mã GD</th>
                            <th class="py-3.5 px-3 font-bold whitespace-nowrap">Mã Lô</th>
                            <th class="py-3.5 px-3 font-bold">Tên Thuốc</th>
                            <th class="py-3.5 px-3 font-bold">Nhà Phân Phối</th>
                            <th class="py-3.5 px-3 font-bold text-right whitespace-nowrap">Số Lượng Nhận</th>
                            <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Thời Gian Nhận</th>
                            <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Trạng Thái</th>
                            <th class="py-3.5 px-3 font-bold text-center whitespace-nowrap">Hành Động</th>
                        </tr>
                    </thead>
                    <tbody id="thanBangDaNhan" class="divide-y divide-slate-100 font-medium">
                        <!-- Render bằng JS (Có nút Chi Tiết cho từng dòng) -->
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                <span id="thongTinPhanTrangDaNhan" class="text-slate-500">Đang hiển thị...</span>
                <div class="flex items-center gap-1.5" id="cumNutPhanTrangDaNhan"></div>
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
                <i class="fa-solid fa-clipboard-check text-cyan-700"></i> Đối Chiếu Thông Tin & Xác Nhận Nhận Hàng
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
                    <span class="text-slate-500">Nhà phân phối giao hàng:</span>
                    <span id="nhanBenGui" class="font-semibold text-slate-800"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Số lượng bàn giao:</span>
                    <span id="nhanSoLuong" class="font-bold text-cyan-800 font-mono text-sm"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Hạn sử dụng:</span>
                    <span id="nhanHanDung" class="font-semibold text-slate-700 font-mono"></span>
                </div>
            </div>

            <div class="p-3 bg-cyan-50 rounded-xl border border-cyan-800/20 text-[11px] text-cyan-950 leading-relaxed">
                <i class="fa-solid fa-cube text-cyan-800 mr-1"></i> <span id="loiDanXacNhanNhan">Hệ thống sẽ ghi nhận bạn đã nhận hàng lên blockchain và cộng số lượng vào kho của bạn.</span>
            </div>

            <div id="canhBaoHetHanModal" class="hidden p-2.5 bg-amber-50 rounded-xl border border-amber-200 text-[11px] text-amber-800 flex items-start gap-2">
                <i class="fa-solid fa-triangle-exclamation text-amber-600 mt-0.5 shrink-0"></i>
                <span><strong>Lưu ý:</strong> Lô thuốc đã quá hạn dùng. Bạn vẫn có thể xác nhận nhận hàng từ nhà phân phối nhưng hệ thống sẽ chặn không cho phép bán lẻ.</span>
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
            <button type="button" id="nutXacNhanNhapKho" class="px-6 py-2.5 bg-cyan-700 hover:bg-cyan-800 text-white font-bold rounded-xl shadow-md transition-all flex items-center gap-2">
                <i class="fa-solid fa-check"></i> Xác nhận đã nhận
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
                <span id="detailMaLoTitle" class="font-mono font-bold text-cyan-800 bg-cyan-50 px-2.5 py-0.5 rounded text-xs border border-cyan-200"></span>
            </div>
            <button type="button" class="dong-modal-detail text-slate-400 hover:text-slate-700 text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="grid grid-cols-2 gap-3 p-4 bg-nenChinh rounded-xl border border-emerald-900/10 text-xs">
            <div>
                <span class="text-slate-500 block">Tên thuốc:</span>
                <span id="detailSanPham" class="font-bold text-slate-900"></span>
            </div>
            <div>
                <span class="text-slate-500 block">Số đăng ký lưu hành:</span>
                <span id="detailSoDK" class="font-mono font-bold text-cyan-800"></span>
            </div>
            <div>
                <span class="text-slate-500 block">Nhà sản xuất:</span>
                <span id="detailNhaSanXuat" class="font-semibold text-slate-800"></span>
            </div>
            <div>
                <span class="text-slate-500 block">Nhà phân phối giao:</span>
                <span id="detailBenGui" class="font-bold text-slate-800"></span>
            </div>
            <div>
                <span class="text-slate-500 block">Số lượng bàn giao:</span>
                <span id="detailSoLuong" class="font-bold text-cyan-800 font-mono text-sm"></span>
            </div>
            <div>
                <span class="text-slate-500 block">Hạn dùng:</span>
                <span id="detailHanDung" class="font-semibold text-slate-800 font-mono"></span>
            </div>
            <div>
                <span class="text-slate-500 block">Thời điểm gửi:</span>
                <span id="detailNgayGui" class="font-semibold text-slate-700 font-mono"></span>
            </div>
            <div>
                <span class="text-slate-500 block">Trạng thái:</span>
                <span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300">
                    <i class="fa-solid fa-clock"></i> Chờ xác nhận
                </span>
            </div>
        </div>

        <div class="space-y-1.5 text-xs">
            <span class="font-bold text-slate-700">Mã giao dịch ghi nhận trên blockchain (txHash):</span>
            <div class="flex items-center gap-2 p-2.5 bg-slate-50 border border-slate-200 rounded-xl">
                <a id="detailTxLink" href="#" target="_blank" class="flex-1 font-mono text-[11px] text-cyan-800 hover:underline break-all flex items-center gap-1.5">
                    <span id="detailTxHash">0x...</span>
                    <i class="fa-solid fa-arrow-up-right-from-square text-[9px] text-slate-400 shrink-0"></i>
                </a>
                <button type="button" id="nutCopyDetailTx" class="px-2.5 py-1 bg-white hover:bg-slate-100 border border-slate-300 text-slate-700 rounded-lg text-[11px] font-bold shrink-0">
                    <i class="fa-regular fa-copy"></i> Sao chép
                </button>
            </div>
        </div>

        <div class="pt-3 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
            <a id="linkBaoCaoTuDetail" href="#" class="text-xs text-amber-700 hover:text-amber-900 font-bold hover:underline flex items-center gap-1.5">
                <i class="fa-solid fa-triangle-exclamation"></i> Báo cáo nghi vấn lô này
            </a>

            <div class="flex items-center gap-2">
                <button type="button" class="dong-modal-detail px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-all">
                    Đóng
                </button>
                <button type="button" id="nutChuyenSangNhanHang" class="px-5 py-2 bg-cyan-700 hover:bg-cyan-800 text-white text-xs font-bold rounded-xl shadow-md transition-all flex items-center gap-1.5">
                    <i class="fa-solid fa-check"></i> Chuyển Sang Nhận Hàng
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!-- MODAL 3: XEM CHI TIẾT LÔ HÀNG ĐÃ TIẾP NHẬN THÀNH CÔNG      -->
<!-- ══════════════════════════════════════════════════════════ -->
<div id="modalChiTietDaTiepNhan" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden bg-black/60">
    <div class="bg-white rounded-2xl max-w-2xl w-full p-6 sm:p-8 space-y-5 bong-xanh max-h-[90vh] overflow-y-auto">
        <!-- Header Modal -->
        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-base font-bold text-slate-900">Chi Tiết Lô Đã Tiếp Nhận:</span>
                <span id="daNhanModalMaLoTitle" class="font-mono font-bold text-emerald-800 bg-emerald-50 px-2.5 py-0.5 rounded text-xs border border-emerald-300"></span>
            </div>
            <button type="button" class="dong-modal-danhan-detail text-slate-400 hover:text-slate-700 text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- CẢNH BÁO NẾU LÔ BỊ THU HỒI HOẶC HẾT HẠN -->
        <div id="daNhanBannerThuHoi" class="hidden p-3.5 bg-red-50 border border-red-200 rounded-xl text-xs space-y-1">
            <div class="font-bold text-red-700 flex items-center gap-1.5">
                <i class="fa-solid fa-ban text-sm"></i> CẢNH BÁO KHẨN CẤP: LÔ THUỐC ĐÃ CÓ QUYẾT ĐỊNH THU HỒI
            </div>
            <p id="daNhanLyDoThuHoiText" class="text-red-600 leading-relaxed text-[11px]"></p>
            <div class="text-[10px] text-red-500 font-mono pt-0.5" id="daNhanNgayThuHoiText"></div>
            <div class="text-[11px] text-red-800 font-semibold pt-1">
                * Hệ thống đã tự động khóa, không cho phép bán bất kỳ hộp thuốc nào thuộc lô này tại quầy!
            </div>
        </div>

        <div id="daNhanBannerHetHan" class="hidden p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-900 flex items-start gap-2">
            <i class="fa-solid fa-triangle-exclamation text-amber-600 mt-0.5 shrink-0"></i>
            <div>
                <strong>Lô thuốc đã hết hạn sử dụng:</strong> Sản phẩm không còn đủ điều kiện lưu hành. Hệ thống sẽ chặn bán lẻ tại quầy.
            </div>
        </div>

        <!-- Thông tin thuốc chi tiết của lần chuyển giao này -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-4 bg-nenChinh rounded-xl border border-emerald-900/10 text-xs">
            <div>
                <span class="text-slate-500 block">Tên thuốc:</span>
                <span id="daNhanModalTenThuoc" class="font-bold text-slate-900"></span>
            </div>
            <div>
                <span class="text-slate-500 block">Số ĐK lưu hành:</span>
                <span id="daNhanModalSoDK" class="font-mono font-bold text-cyan-800"></span>
            </div>
            <div>
                <span class="text-slate-500 block">Nhà sản xuất:</span>
                <span id="daNhanModalNhaSanXuat" class="font-semibold text-slate-800"></span>
            </div>
            <div>
                <span class="text-slate-500 block">Nhà phân phối giao:</span>
                <span id="daNhanModalBenGiao" class="font-semibold text-slate-800"></span>
            </div>
            <div>
                <span class="text-slate-500 block">Hạn dùng:</span>
                <div class="flex items-center gap-1.5 flex-wrap">
                    <span id="daNhanModalHanDung" class="font-semibold text-slate-800 font-mono"></span>
                    <span id="daNhanModalHetHanTag" class="hidden badge-chuan px-1.5 py-0.2 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300">Hết hạn</span>
                </div>
            </div>
            <div>
                <span class="text-slate-500 block">Số lượng đợt này:</span>
                <span id="daNhanModalSoLuongDot" class="font-bold text-cyan-800 font-mono text-sm"></span>
            </div>
            <div>
                <span class="text-slate-500 block">Thời điểm gửi:</span>
                <span id="daNhanModalThoiGianGui" class="font-semibold text-slate-700 font-mono"></span>
            </div>
            <div>
                <span class="text-slate-500 block">Thời điểm xác nhận:</span>
                <div class="flex items-center gap-1.5 flex-wrap pt-0.5">
                    <span id="daNhanModalThoiGianXacNhan" class="font-bold text-slate-800 font-mono"></span>
                    <span class="badge-chuan px-2 py-0.5 rounded text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300">
                        <i class="fa-solid fa-check-double text-[9px]"></i> Đã nhận
                    </span>
                </div>
            </div>
        </div>

        <!-- KHỐI TÌNH TRẠNG CỦA LÔ NÀY TẠI NHÀ THUỐC -->
        <div class="p-4 bg-cyan-50/70 rounded-xl border border-cyan-800/20 space-y-2.5 text-xs">
            <div class="flex items-center justify-between flex-wrap gap-2">
                <span class="font-bold text-cyan-950 uppercase tracking-wide flex items-center gap-1.5">
                    <i class="fa-solid fa-boxes-stacked text-cyan-800"></i> Tình trạng của lô này tại nhà thuốc (tính cho tất cả các lần nhận):
                </span>
                <a id="linkSangQuanLyBanHang" href="quanLyBanHang.php" class="inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-cyan-700 hover:bg-cyan-800 text-white font-bold text-[11px] shadow-2xs transition-all">
                    Xem trong Quản lý bán hàng <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1">
                <div class="p-2.5 bg-white rounded-lg border border-slate-200">
                    <span class="text-slate-500 block text-[11px]">Tổng đã nhận:</span>
                    <span id="daNhanTongNhanLo" class="font-mono font-bold text-slate-800 text-sm">0 hộp</span>
                </div>
                <div class="p-2.5 bg-white rounded-lg border border-slate-200">
                    <span class="text-slate-500 block text-[11px]">Đã bán cho người tiêu dùng:</span>
                    <span id="daNhanSoLuongDaBan" class="font-mono font-bold text-slate-800 text-sm">0 hộp</span>
                </div>
                <div class="p-2.5 bg-white rounded-lg border border-slate-200">
                    <span class="text-slate-500 block text-[11px]">Còn bán được:</span>
                    <span id="daNhanSoLuongConBan" class="font-mono font-bold text-emerald-700 text-sm">0 hộp</span>
                </div>
            </div>

            <p class="text-[10px] text-slate-500 italic pt-0.5 leading-relaxed">
                * Ghi chú: Số liệu tính theo cả lô, không tách theo từng lần nhận.
            </p>
        </div>

        <!-- BẰNG CHỨNG BLOCKCHAIN -->
        <div class="space-y-1.5 text-xs">
            <span class="font-bold text-slate-700 flex items-center gap-1.5">
                <i class="fa-solid fa-link text-emerald-700"></i> Mã giao dịch chuyển giao trên blockchain (hashBlockchain):
            </span>
            <div class="flex items-center gap-2 p-2.5 bg-slate-50 border border-slate-200 rounded-xl">
                <a id="daNhanModalTxLink" href="#" target="_blank" class="flex-1 font-mono text-[11px] text-cyan-800 hover:underline break-all flex items-center gap-1.5">
                    <span id="daNhanModalTxHash">0x...</span>
                    <i class="fa-solid fa-arrow-up-right-from-square text-[9px] text-slate-400 shrink-0"></i>
                </a>
                <button type="button" id="nutCopyDaNhanTx" class="px-2.5 py-1 bg-white hover:bg-slate-100 border border-slate-300 text-slate-700 rounded-lg text-[11px] font-bold shrink-0 shadow-2xs">
                    <i class="fa-regular fa-copy"></i> Sao chép
                </button>
            </div>
        </div>

        <div class="pt-3 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
            <a id="linkBaoCaoTuDaNhan" href="#" class="text-xs text-amber-700 hover:text-amber-900 font-bold hover:underline flex items-center gap-1.5">
                <i class="fa-solid fa-triangle-exclamation"></i> Phát hiện bất thường? Báo cáo nghi vấn lô này
            </a>

            <button type="button" class="dong-modal-danhan-detail px-6 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-all">
                Đóng
            </button>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!-- MODAL 4: TRẠNG THÁI GHI BLOCKCHAIN                        -->
<!-- ══════════════════════════════════════════════════════════ -->
<div id="modalLoadingBlockchain" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden bg-black/60">
    <div class="bg-white rounded-2xl max-w-sm w-full p-8 text-center space-y-4 bong-xanh">
        <div id="khungLoadingIcon">
            <div class="w-14 h-14 mx-auto rounded-full bg-cyan-100 text-cyan-800 flex items-center justify-center text-2xl">
                <i class="fa-solid fa-circle-notch fa-spin"></i>
            </div>
            <h4 class="text-sm font-bold text-slate-900 mt-3">Đang Ghi Nhận Lên Blockchain...</h4>
            <p class="text-xs text-slate-500 mt-1">Đang ký xác nhận giao dịch RECEIVED và đồng bộ kho hàng.</p>
        </div>

        <div id="khungSuccessIcon" class="hidden space-y-2">
            <div class="w-14 h-14 mx-auto rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-2xl">
                <i class="fa-solid fa-check-double"></i>
            </div>
            <h4 class="text-base font-bold text-slate-900">Tiếp Nhận Lô Hàng Thành Công!</h4>
            <p class="text-xs text-slate-600" id="successMessageDetail"></p>
            <div class="pt-2">
                <button type="button" id="nutDongModalSuccess" class="w-full py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-xl shadow-md transition-all">
                    Hoàn Tất
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/nhaThuoc/tiepNhanLoHang.js') }}"></script>
@endpush
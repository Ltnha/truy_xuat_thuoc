@extends('layoutDungChung.master')

@section('title', 'Xử lý nghi vấn - PharmaChain')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/coQuanQuanLy/xuLyNghiVan.css') }}">
@endpush

@section('content')
<div class="space-y-6 max-w-7xl w-full mx-auto">

    <!-- THANH CÔNG CỤ TÌM KIẾM VÀ BỘ LỌC -->
    <div class="bg-white p-5 rounded-2xl border border-emerald-900/10 bong-xanh">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 text-xs pointer-events-none">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" id="oTimKiemNghiVan" placeholder="Tìm theo mã báo cáo, mã lô hoặc tên sản phẩm..." class="w-full pl-9 pr-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-medium text-slate-900 focus:bg-white focus:border-indigo-700 outline-none">
            </div>

            <select id="locTrangThaiNghiVan" class="px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:border-indigo-700 outline-none cursor-pointer">
                <option value="TAT_CA">-- Tất cả trạng thái --</option>
                <option value="CHO_XU_LY">Chờ xử lý</option>
                <option value="DA_XU_LY">Đã xử lý</option>
            </select>

            <select id="locLoaiBaoCao" class="px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:border-indigo-700 outline-none cursor-pointer">
                <option value="TAT_CA">-- Tất cả loại nghi vấn --</option>
                <option value="THUOC_GIA">Nghi vấn thuốc giả</option>
                <option value="VI_PHAM_CHAT_LUONG">Vi phạm chất lượng</option>
                <option value="KHAC">Nghi vấn khác</option>
            </select>
        </div>
    </div>

    <!-- BẢNG DANH SÁCH BÁO CÁO NGHI VẤN -->
    <div class="bg-white rounded-2xl border border-emerald-900/10 bong-xanh overflow-hidden">
        <div class="p-4 bg-nenChinh border-b border-emerald-900/10 flex items-center justify-between text-xs">
            <span class="font-bold text-slate-700 uppercase tracking-wide flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation text-amber-600"></i> Danh Sách Báo Cáo Nghi Vấn Thuốc
            </span>
            <span id="soLuongNghiVanBadge" class="font-bold text-slate-500 font-mono">Hiển thị: 0 báo cáo</span>
        </div>

        <div class="w-full overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 uppercase tracking-wider border-b border-slate-100 text-[11px]">
                        <th class="py-3.5 px-3 font-bold whitespace-nowrap">Mã Báo Cáo</th>
                        <th class="py-3.5 px-3 font-bold whitespace-nowrap">Mã Lô Thuốc</th>
                        <th class="py-3.5 px-3 font-bold">Sản Phẩm</th>
                        <th class="py-3.5 px-3 font-bold">Đơn Vị Báo Cáo</th>
                        <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Thời Gian Gui</th>
                        <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Trạng Thái</th>
                        <th class="py-3.5 px-3 font-bold text-center whitespace-nowrap">Hành Động</th>
                    </tr>
                </thead>
                <tbody id="thanBangNghiVan" class="divide-y divide-slate-100 font-medium"></tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
            <span id="thongTinPhanTrangNghiVan" class="text-slate-500">Đang hiển thị...</span>
            <div class="flex items-center gap-1.5" id="cumNutPhanTrangNghiVan"></div>
        </div>
    </div>

</div>

<!-- MODAL 1: CHI TIẾT BÁO CÁO NGHI VẤN -->
<div id="modalChiTietNghiVan" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-3 sm:p-5 hidden">
    <div class="bg-white rounded-2xl max-w-2xl w-full p-5 sm:p-6 space-y-4 bong-xanh max-h-[92vh] flex flex-col">
        <div class="border-b border-slate-100 pb-2.5 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2.5 flex-wrap">
                <span class="text-base font-bold text-slate-900">Chi Tiết Báo Cáo:</span>
                <span id="detailMaBaoCaoTitle" class="font-mono font-bold text-amber-900 bg-amber-50 px-2.5 py-0.5 rounded text-xs border border-amber-200"></span>
                <span id="detailTrangThaiBadge"></span>
            </div>
            <button type="button" class="dong-modal-detail-nghivan text-slate-400 hover:text-slate-700 text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto space-y-3.5 text-xs pr-1">
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 p-3 bg-nenChinh rounded-xl border border-emerald-900/10">
                <div>
                    <span class="text-slate-500 block text-[11px]">Mã lô liên quan:</span>
                    <span id="detailMaLoThuoc" class="font-mono font-bold text-indigo-900"></span>
                </div>
                <div>
                    <span class="text-slate-500 block text-[11px]">Tên sản phẩm:</span>
                    <span id="detailTenSanPham" class="font-bold text-slate-900"></span>
                </div>
                <div>
                    <span class="text-slate-500 block text-[11px]">Đơn vị gửi báo cáo:</span>
                    <span id="detailDonViBaoCao" class="font-semibold text-slate-800"></span>
                </div>
                <div>
                    <span class="text-slate-500 block text-[11px]">Thời gian gửi:</span>
                    <span id="detailThoiGianGui" class="font-mono text-slate-700"></span>
                </div>
                <div>
                    <span class="text-slate-500 block text-[11px]">Loại nghi vấn:</span>
                    <span id="detailLoaiBaoCao" class="font-bold text-amber-800"></span>
                </div>
            </div>

            <div class="space-y-1 bg-slate-50 p-3 rounded-xl border border-slate-200">
                <span class="font-bold text-slate-700 uppercase tracking-wide block text-[11px]">
                    <i class="fa-solid fa-align-left text-slate-500 mr-1"></i> Nội dung báo cáo nghi vấn:
                </span>
                <p id="detailNoiDungBaoCao" class="text-slate-800 font-medium leading-relaxed whitespace-pre-line"></p>
            </div>

            <div id="detailKhungMinhChung" class="hidden space-y-1.5 bg-slate-50 p-3 rounded-xl border border-slate-200">
                <span class="font-bold text-slate-700 uppercase tracking-wide block text-[11px]">
                    <i class="fa-solid fa-paperclip text-slate-500 mr-1"></i> Tệp minh chứng kèm theo:
                </span>
                <div id="detailDanhSachMinhChung" class="flex flex-wrap gap-2 pt-1"></div>
            </div>

            <div id="detailKhungKetQuaXuLy" class="hidden space-y-1 bg-emerald-50 p-3 rounded-xl border border-emerald-200 text-emerald-950">
                <span class="font-bold text-emerald-900 uppercase tracking-wide block text-[11px]">
                    <i class="fa-solid fa-gavel text-emerald-700 mr-1"></i> Kết quả xử lý từ Cơ quan quản lý:
                </span>
                <p id="detailNoiDungKetQua" class="font-semibold text-emerald-900 leading-relaxed"></p>
                <div class="text-[10px] text-emerald-700 pt-1 font-mono" id="detailThoiGianXuLy"></div>
            </div>
        </div>

        <div class="border-t border-slate-100 pt-3 flex items-center justify-between shrink-0">
            <button type="button" class="dong-modal-detail-nghivan px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs">
                Đóng
            </button>
            <div id="detailKhungNutXuLyNhanh"></div>
        </div>
    </div>
</div>

<!-- MODAL 2: XỬ LÝ BÁO CÁO NGHI VẤN (POPUP 2 TRẠNG THÁI) -->
<div id="modalXuLyThuHoiNghiVan" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-lg w-full p-5 sm:p-7 space-y-4 bong-xanh max-h-[92vh] flex flex-col">
        <!-- HEADER MODAL -->
        <div class="border-b border-slate-100 pb-2.5 flex items-center justify-between shrink-0">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-shield-cat text-amber-600"></i> Quyết Định Xử Lý Báo Cáo Nghi Vấn
            </h3>
            <button type="button" class="dong-modal-nghivan text-slate-400 hover:text-slate-700 text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- MÀN HÌNH 0: FORM NHẬP LÝ DO THU HỒI / XỬ LÝ -->
        <div id="manHinhFormNghiVan" class="flex-1 overflow-y-auto space-y-3.5 text-xs pr-1">
            <div class="p-3 bg-nenChinh rounded-xl border border-emerald-900/10 space-y-1">
                <div class="flex justify-between items-center">
                    <span class="text-slate-500">Mã báo cáo:</span>
                    <strong id="modalNghiVanMaBaoCao" class="font-mono text-slate-900 text-sm"></strong>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-500">Lô thuốc liên quan:</span>
                    <strong id="modalNghiVanMaLo" class="font-mono text-red-700"></strong>
                </div>
            </div>

            <div class="space-y-1">
                <div class="flex items-center justify-between">
                    <label for="nhapLyDoXuLyNghiVan" class="font-bold text-slate-700 uppercase tracking-wide block text-[11px]">
                        Lý Do Xử Lý / Căn Cứ Thu Hồi <span class="text-red-500">*</span>
                    </label>
                    <span id="demKyTuNghiVan" class="text-[10px] text-slate-400 font-mono">0/500</span>
                </div>
                <textarea id="nhapLyDoXuLyNghiVan" maxlength="500" rows="3" required placeholder="Nhập lý do ban hành quyết định thu hồi lô thuốc từ báo cáo nghi vấn này..." class="w-full p-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl font-medium text-slate-900 focus:bg-white focus:border-red-600 outline-none resize-y"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100">
                <button type="button" class="dong-modal-nghivan px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-all">
                    Hủy
                </button>
                <button type="button" id="nutXacNhanThuHoiNghiVan" disabled class="px-6 py-2 bg-slate-200 text-slate-400 font-bold rounded-xl transition-all flex items-center gap-1.5 cursor-not-allowed">
                    <i class="fa-solid fa-ban"></i> Ban Hành Thu Hồi Lô
                </button>
            </div>
        </div>

        <!-- TRẠNG THÁI 1: ĐANG THỰC THI (LOADING POPUP) -->
        <div id="manHinhDangGhiNghiVan" class="hidden text-center py-8 space-y-4">
            <div class="w-16 h-16 mx-auto rounded-full bg-amber-50 text-amber-600 flex items-center justify-center text-3xl">
                <i class="fa-solid fa-circle-notch fa-spin"></i>
            </div>
            <div class="space-y-1">
                <h4 class="text-base font-bold text-slate-900">Đang xử lý báo cáo...</h4>
                <p class="text-xs text-slate-500 max-w-xs mx-auto">Đang cập nhật hồ sơ nghi vấn và kích hoạt Job đồng bộ quyết định Thu hồi lên Blockchain.</p>
            </div>
        </div>

        <!-- TRẠNG THÁI 2: PHẢN HỒI KẾT QUẢ (SUCCESS / ERROR POPUP) -->
        <div id="manHinhKetQuaNghiVan" class="hidden text-center py-6 space-y-4">
            <div id="iconKetQuaNghiVan" class="w-16 h-16 mx-auto rounded-full flex items-center justify-center text-3xl"></div>

            <div class="space-y-1.5">
                <h4 id="tieuDeKetQuaNghiVan" class="text-base font-bold"></h4>
                <p id="noiDungKetQuaNghiVan" class="text-xs text-slate-600 leading-relaxed max-w-xs mx-auto"></p>
            </div>

            <div class="pt-2 border-t border-slate-100 flex justify-center">
                <button type="button" class="dong-modal-nghivan px-6 py-2 bg-indigo-700 hover:bg-indigo-800 text-white font-bold rounded-xl text-xs transition-all shadow-md">
                    <i class="fa-solid fa-check mr-1"></i> Hoàn Tất & Đóng
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/coQuanQuanLy/xuLyNghiVan.js') }}"></script>
@endpush
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
                <input type="text" id="oTimKiemNghiVan" placeholder="Tìm theo mã báo cáo, mã lô, tên thuốc hoặc đơn vị..." class="w-full pl-9 pr-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-medium text-slate-900 focus:bg-white focus:border-indigo-700 outline-none">
            </div>

            <select id="locTrangThaiNghiVan" class="px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:border-indigo-700 outline-none cursor-pointer">
                <option value="TAT_CA">-- Tất cả trạng thái --</option>
                <option value="CHO_XU_LY">Chờ xử lý</option>
                <option value="DA_XU_LY">Đã xử lý</option>
            </select>

            <select id="locLoaiBaoCao" class="px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:border-indigo-700 outline-none cursor-pointer">
                <option value="TAT_CA">-- Tất cả phân loại báo cáo --</option>
                <option value="TRONG_VONG_DOI">Lô trong vòng đời (Có thể thu hồi)</option>
                <option value="NGOAI_HE_THONG">Lô ngoài hệ thống / Nghi giả</option>
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
                        <th class="py-3.5 px-3 font-bold whitespace-nowrap">Phân Loại</th>
                        <th class="py-3.5 px-3 font-bold whitespace-nowrap">Mã Lô Khai Báo</th>
                        <th class="py-3.5 px-3 font-bold">Sản Phẩm / Đối Chiếu</th>
                        <th class="py-3.5 px-3 font-bold">Đơn Vị Báo Cáo</th>
                        <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Thời Gian Gửi</th>
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
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-base font-bold text-slate-900">Chi Tiết Báo Cáo:</span>
                <span id="detailMaBaoCaoTitle" class="font-mono font-bold text-amber-900 bg-amber-50 px-2.5 py-0.5 rounded text-xs border border-amber-200"></span>
                <span id="detailLoaiBaoCaoBadge"></span>
                <span id="detailTrangThaiBadge"></span>
            </div>
            <button type="button" class="dong-modal-detail-nghivan text-slate-400 hover:text-slate-700 text-lg cursor-pointer">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto space-y-3.5 text-xs pr-1">
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 p-3 bg-nenChinh rounded-xl border border-emerald-900/10">
                <div>
                    <span class="text-slate-500 block text-[11px]">Mã lô khai báo:</span>
                    <span id="detailMaLoThuoc" class="font-mono font-bold text-indigo-900"></span>
                </div>
                <div>
                    <span class="text-slate-500 block text-[11px]">Sản phẩm liên quan:</span>
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
                <div class="col-span-2">
                    <span class="text-slate-500 block text-[11px]">Phân loại thẩm định:</span>
                    <span id="detailLoaiBaoCao" class="font-bold text-slate-800"></span>
                </div>
            </div>

            <!-- KHUNG LÔ GỐC ĐỐI CHIẾU (CHỈ HIỆN VỚI BÁO CÁO NGOAI_HE_THONG CÓ CHỌN LÔ GỐC) -->
            <div id="detailKhungLoBiLamGia" class="hidden p-3 bg-amber-50/80 rounded-xl border border-amber-300 text-amber-950 space-y-1">
                <div class="font-bold text-amber-900 flex items-center gap-1.5 text-[11px] uppercase">
                    <i class="fa-solid fa-magnifying-glass-chart text-amber-700"></i>
                    Lô Thật Của Đơn Vị Bị Nghi Sao Chép (Chỉ Đối Chiếu — Không Thu Hồi):
                </div>
                <p id="detailThongTinLoBiLamGia" class="font-semibold text-xs leading-relaxed"></p>
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

            <div id="detailKhungKetQuaXuLy" class="hidden space-y-1.5 p-3 rounded-xl border">
                <span id="detailTieuDeKetQua" class="font-bold uppercase tracking-wide block text-[11px]"></span>
                <p id="detailNoiDungKetQua" class="font-semibold leading-relaxed"></p>
                <div id="detailTxThuHoiWrap" class="hidden pt-1 border-t border-red-200/80 flex items-center justify-between gap-2 text-[11px]">
                    <span id="detailTxThuHoiText" class="font-mono truncate"></span>
                    <a id="detailTxThuHoiLink" href="#" target="_blank" class="font-bold underline shrink-0">Sepolia Etherscan</a>
                </div>
                <div class="text-[10px] opacity-80 pt-0.5 font-mono" id="detailThoiGianXuLy"></div>
            </div>
        </div>

        <div class="border-t border-slate-100 pt-3 flex items-center justify-between gap-2 shrink-0">
            <button type="button" class="dong-modal-detail-nghivan px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs cursor-pointer">
                Đóng
            </button>
            <div id="detailKhungNutXuLyNhanh" class="flex items-center gap-2 flex-wrap justify-end"></div>
        </div>
    </div>
</div>

<!-- MODAL 2: XỬ LÝ BÁO CÁO NGHI VẤN (HỖ TRỢ THU_HOI / GHI_NHAN / TU_CHOI) -->
<div id="modalXuLyThuHoiNghiVan" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-lg w-full p-5 sm:p-7 space-y-4 bong-xanh max-h-[92vh] flex flex-col">
        <!-- HEADER MODAL -->
        <div class="border-b border-slate-100 pb-2.5 flex items-center justify-between shrink-0">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-gavel text-indigo-700"></i> Quyết Định Xử Lý Báo Cáo Nghi Vấn
            </h3>
            <button type="button" class="dong-modal-nghivan text-slate-400 hover:text-slate-700 text-lg cursor-pointer">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- MÀN HÌNH 0: FORM CHỌN HÌNH THỨC & NHẬP LÝ DO -->
        <div id="manHinhFormNghiVan" class="flex-1 overflow-y-auto space-y-3.5 text-xs pr-1">
            <div class="p-3 bg-nenChinh rounded-xl border border-emerald-900/10 space-y-1">
                <div class="flex justify-between items-center">
                    <span class="text-slate-500">Mã báo cáo:</span>
                    <strong id="modalNghiVanMaBaoCao" class="font-mono text-slate-900 text-sm"></strong>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-500">Mã lô khai báo:</span>
                    <strong id="modalNghiVanMaLo" class="font-mono text-indigo-900"></strong>
                </div>
            </div>

            <!-- Chọn hướng quyết định phù hợp với phân loại báo cáo -->
            <div class="space-y-1.5">
                <span class="font-bold text-slate-700 uppercase tracking-wide block text-[11px]">Hình Thức Quyết Định:</span>
                <div id="khungChonCheDoXuLy" class="grid grid-cols-2 gap-2"></div>
                <p id="ghiChuCheDoXuLy" class="text-[11px] text-slate-500 leading-relaxed"></p>
            </div>

            <div class="space-y-1">
                <div class="flex items-center justify-between">
                    <label for="nhapLyDoXuLyNghiVan" class="font-bold text-slate-700 uppercase tracking-wide block text-[11px]">
                        Căn Cứ / Kết Luận Thẩm Định <span class="text-red-500">*</span>
                    </label>
                    <span id="demKyTuNghiVan" class="text-[10px] text-slate-400 font-mono">0/500</span>
                </div>
                <textarea id="nhapLyDoXuLyNghiVan" maxlength="500" rows="3" required placeholder="Nhập nội dung kết luận thẩm định hoặc căn cứ ban hành quyết định..." class="w-full p-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl font-medium text-slate-900 focus:bg-white focus:border-indigo-700 outline-none resize-y"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100">
                <button type="button" class="dong-modal-nghivan px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-all cursor-pointer">
                    Hủy
                </button>
                <button type="button" id="nutXacNhanThuHoiNghiVan" disabled class="px-6 py-2 bg-slate-200 text-slate-400 font-bold rounded-xl transition-all flex items-center gap-1.5 cursor-not-allowed">
                    <i class="fa-solid fa-check"></i> Xác Nhận Quyết Định
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
                <p id="moTaDangXuLyNghiVan" class="text-xs text-slate-500 max-w-xs mx-auto">Đang cập nhật hồ sơ thẩm định lên hệ thống.</p>
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
                <button type="button" class="dong-modal-nghivan px-6 py-2 bg-indigo-700 hover:bg-indigo-800 text-white font-bold rounded-xl text-xs transition-all shadow-md cursor-pointer">
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
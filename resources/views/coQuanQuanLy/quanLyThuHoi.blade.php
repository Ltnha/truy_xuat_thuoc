@extends('layoutDungChung.master')

@section('title', 'Quản lý thu hồi - PharmaChain')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/coQuanQuanLy/quanLyThuHoi.css') }}">
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
                <input type="text" id="oTimKiemThuHoi" placeholder="Tìm theo mã lô hoặc tên sản phẩm..." class="w-full pl-9 pr-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-medium text-slate-900 focus:bg-white focus:border-indigo-700 outline-none">
            </div>

            <select id="locNhaSanXuat" class="px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:border-indigo-700 outline-none cursor-pointer">
                <option value="TAT_CA">-- Tất cả nhà sản xuất --</option>
                @if(isset($nhaSanXuats))
                @foreach($nhaSanXuats as $nsx)
                <option value="{{ $nsx->id }}">{{ $nsx->tenToChuc }}</option>
                @endforeach
                @endif
            </select>

            <select id="locTrangThaiLo" class="px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:border-indigo-700 outline-none cursor-pointer">
                <option value="TAT_CA">-- Tất cả trạng thái lô --</option>
                <option value="DANG_LUU_HANH">Đang lưu thông</option>
                <option value="DA_THU_HOI">Đã thu hồi</option>
                <option value="HET_HAN">Hết hạn</option>
            </select>
        </div>
    </div>

    <!-- BẢNG DANH SÁCH LÔ THUỐC -->
    <div class="bg-white rounded-2xl border border-emerald-900/10 bong-xanh overflow-hidden">
        <div class="p-4 bg-nenChinh border-b border-emerald-900/10 flex items-center justify-between text-xs">
            <span class="font-bold text-slate-700 uppercase tracking-wide flex items-center gap-2">
                <i class="fa-solid fa-boxes-stacked text-indigo-700"></i> Danh Sách Lô Thuốc Toàn Hệ Thống
            </span>
            <span id="soLuongLoBadge" class="font-bold text-slate-500 font-mono">Hiển thị: 0 lô</span>
        </div>

        <div class="w-full overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 uppercase tracking-wider border-b border-slate-100 text-[11px]">
                        <th class="py-3.5 px-3 font-bold whitespace-nowrap">Mã Lô</th>
                        <th class="py-3.5 px-3 font-bold">Sản Phẩm</th>
                        <th class="py-3.5 px-3 font-bold">Nhà Sản Xuất</th>
                        <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Hạn Dùng</th>
                        <th class="py-3.5 px-3 font-bold text-right whitespace-nowrap">Tổng Số Lượng</th>
                        <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Trạng Thái Lô</th>
                        <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Báo Cáo Nghi Vấn</th>
                        <th class="py-3.5 px-3 font-bold text-center whitespace-nowrap">Hành Động</th>
                    </tr>
                </thead>
                <tbody id="thanBangThuHoi" class="divide-y divide-slate-100 font-medium"></tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
            <span id="thongTinPhanTrang" class="text-slate-500">Đang hiển thị...</span>
            <div class="flex items-center gap-1.5" id="cumNutPhanTrang"></div>
        </div>
    </div>

</div>

<!-- MODAL 1: CHI TIẾT LÔ THUỐC -->
<div id="modalChiTietLo" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-3 sm:p-5 hidden">
    <div class="bg-white rounded-2xl max-w-3xl w-full p-5 sm:p-6 space-y-4 bong-xanh max-h-[92vh] flex flex-col">
        <div class="border-b border-slate-100 pb-2.5 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2.5 flex-wrap">
                <span class="text-base font-bold text-slate-900">Chi Tiết Lô Thuốc:</span>
                <span id="detailMaLoTitle" class="font-mono font-bold text-indigo-900 bg-indigo-50 px-2.5 py-0.5 rounded text-xs border border-indigo-200"></span>
                <span id="detailTrangThaiLoBadge"></span>
            </div>
            <button type="button" class="dong-modal-detail text-slate-400 hover:text-slate-700 text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div id="detailBannerDaThuHoi" class="hidden p-3 bg-red-50 border border-red-200 rounded-xl text-xs space-y-1.5 shrink-0">
            <div class="flex items-center justify-between">
                <span class="font-bold text-red-700 flex items-center gap-1.5 text-xs">
                    <i class="fa-solid fa-ban text-sm"></i> LÔ THUỐC ĐÃ CÓ QUYẾT ĐỊNH THU HỒI
                </span>
                <span class="badge-chuan px-2 py-0.5 rounded text-[10px] font-extrabold bg-red-200 text-red-800 border border-red-300">Đã thu hồi</span>
            </div>
            <div>
                <span class="text-slate-500 block text-[11px]">Lý do thu hồi:</span>
                <p id="detailLyDoThuHoiText" class="font-bold text-red-900 leading-relaxed"></p>
            </div>
            <div class="grid grid-cols-2 gap-2 text-[11px] pt-1 border-t border-red-200 text-slate-600">
                <div>Thời điểm thu hồi: <strong id="detailNgayThuHoiText" class="font-mono text-slate-800"></strong></div>
                <div>Trạng thái neo: <strong id="detailDongBoText" class="font-mono text-slate-800"></strong></div>
            </div>
            <div class="pt-1 space-y-1">
                <span class="text-slate-500 block text-[10px]">Mã giao dịch thu hồi on-chain:</span>
                <div class="flex items-center gap-2 p-1.5 bg-white border border-red-200 rounded-lg">
                    <a id="detailTxHashThuHoiLink" href="#" target="_blank" class="flex-1 font-mono text-[11px] text-cyan-800 hover:underline break-all truncate">
                        <span id="detailTxHashThuHoiText">0x...</span>
                    </a>
                    <button type="button" id="nutCopyDetailTxThuHoi" class="px-2 py-0.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-[10px] font-bold shrink-0">
                        <i class="fa-regular fa-copy"></i>
                    </button>
                </div>
            </div>
        </div>

        <div id="detailBannerGoiYThuHoiHetHan" class="hidden p-2.5 bg-amber-50 border border-amber-300 rounded-xl text-xs text-amber-950 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation text-amber-600 text-base"></i>
                <span><strong>Lô thuốc đã hết hạn sử dụng.</strong> Cân nhắc ban hành quyết định thu hồi chính thức.</span>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto space-y-3 pr-1 text-xs">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 p-3 bg-nenChinh rounded-xl border border-emerald-900/10 text-xs">
                <div>
                    <span class="text-slate-500 block text-[11px]">Sản phẩm:</span>
                    <span id="detailTenThuoc" class="font-bold text-slate-900"></span>
                </div>
                <div>
                    <span class="text-slate-500 block text-[11px]">Số ĐK lưu hành:</span>
                    <span id="detailSoDK" class="font-mono font-bold text-cyan-800"></span>
                </div>
                <div>
                    <span class="text-slate-500 block text-[11px]">Nhà sản xuất:</span>
                    <span id="detailNhaSanXuat" class="font-semibold text-slate-800"></span>
                </div>
                <div>
                    <span class="text-slate-500 block text-[11px]">Hạn dùng:</span>
                    <span id="detailHanDung" class="font-mono font-bold text-slate-800"></span>
                </div>
                <div>
                    <span class="text-slate-500 block text-[11px]">Tổng số lượng:</span>
                    <span id="detailTongSoLuong" class="font-mono font-bold text-indigo-900"></span>
                </div>
                <div>
                    <span class="text-slate-500 block text-[11px]">Đang lưu thông:</span>
                    <span id="detailDangLuuThong" class="font-mono font-bold text-emerald-700"></span>
                </div>
                <div>
                    <span class="text-slate-500 block text-[11px]">Ngày sản xuất:</span>
                    <span id="detailNgaySanXuat" class="font-mono text-slate-700"></span>
                </div>
                <div>
                    <span class="text-slate-500 block text-[11px]">Báo cáo nghi vấn:</span>
                    <div class="flex items-center gap-1.5 pt-0.5">
                        <span id="detailSoBaoCaoNghiVan" class="font-bold text-amber-800 font-mono">0 báo cáo</span>
                        <a id="detailLinkSangNghiVan" href="{{ route('coQuan.nghiVan.index') }}" class="text-cyan-800 hover:underline font-bold text-[11px] flex items-center gap-1">
                            [Xem]
                        </a>
                    </div>
                </div>
            </div>

            <div class="space-y-1.5">
                <span class="font-bold text-slate-700 uppercase tracking-wide flex items-center gap-1.5 text-[11px]">
                    <i class="fa-solid fa-warehouse text-slate-500"></i> Phân Bổ Tồn Kho Trong Chuỗi Cung Ứng:
                </span>
                <div class="border border-slate-200 rounded-xl overflow-hidden">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead class="bg-slate-50 text-slate-600 border-b border-slate-200 text-[11px]">
                            <tr>
                                <th class="p-2.5 font-bold">Đơn Vị Lưu Giữ</th>
                                <th class="p-2.5 font-bold text-center">Vai Trò</th>
                                <th class="p-2.5 font-bold text-right">Số Lượng Giữ</th>
                                <th id="thCotBanLe" class="p-2.5 font-bold text-right">Đã Xuất Bán Lẻ</th>
                            </tr>
                        </thead>
                        <tbody id="detailThanBangPhanBoKho" class="divide-y divide-slate-100 font-mono"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="border-t border-slate-100 pt-3 flex items-center justify-between shrink-0">
            <button type="button" class="dong-modal-detail px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition-all">
                Đóng
            </button>
            <div id="detailKhungNutThuHoiNhanh"></div>
        </div>
    </div>
</div>

<!-- MODAL 2: THU HỒI LÔ HÀNG VỚI POPUP 2 TRẠNG THÁI -->
<div id="modalThuHoiLoHang" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-lg w-full p-5 sm:p-7 space-y-4 bong-xanh max-h-[92vh] flex flex-col">
        <!-- HEADER MODAL -->
        <div class="border-b border-slate-100 pb-2.5 flex items-center justify-between shrink-0">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-ban text-red-600"></i> Quyết Định Thu Hồi Lô Thuốc
            </h3>
            <button type="button" class="dong-modal-thuhoi text-slate-400 hover:text-slate-700 text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- MÀN HÌNH 0: FORM NHẬP LÝ DO -->
        <div id="manHinhFormThuHoi" class="flex-1 overflow-y-auto space-y-3.5 text-xs pr-1">
            <div class="p-3 bg-nenChinh rounded-xl border border-emerald-900/10 space-y-1.5">
                <div class="flex justify-between items-center">
                    <span class="text-slate-500">Mã lô thu hồi:</span>
                    <span id="thuHoiModalMaLo" class="font-mono font-bold text-red-700 text-sm"></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-500">Tên thuốc:</span>
                    <span id="thuHoiModalTenThuoc" class="font-bold text-slate-900 text-right"></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-500">Nhà sản xuất:</span>
                    <span id="thuHoiModalNSX" class="font-semibold text-slate-800"></span>
                </div>
                <div class="pt-1 border-t border-slate-200">
                    <span class="text-slate-500 block text-[11px] mb-1">Số lượng đang giữ tại các đơn vị:</span>
                    <div id="thuHoiModalDanhSachBenGiu" class="space-y-1 bg-white p-2 rounded-lg border border-slate-200 text-[11px] font-mono"></div>
                </div>
            </div>

            <div id="thuHoiKhungChuyenGiaoTreo" class="hidden p-2.5 bg-amber-50 rounded-xl border border-amber-300 text-amber-950 text-[11px] space-y-1">
                <div class="font-bold flex items-center gap-1 text-amber-800">
                    <i class="fa-solid fa-triangle-exclamation"></i> Chuyển giao đang chờ xác nhận:
                </div>
                <p id="thuHoiChuyenGiaoTreoText"></p>
            </div>

            <div class="space-y-1">
                <div class="flex items-center justify-between">
                    <label for="nhapLyDoThuHoiLo" class="font-bold text-slate-700 uppercase tracking-wide block text-[11px]">
                        Lý Do Thu Hồi <span class="text-red-500">*</span>
                    </label>
                    <span id="demKyTuThuHoi" class="text-[10px] text-slate-400 font-mono">0/500</span>
                </div>
                <textarea id="nhapLyDoThuHoiLo" maxlength="500" rows="3" required placeholder="Nêu rõ căn cứ pháp lý / chuyên môn thu hồi..." class="w-full p-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl font-medium text-slate-900 focus:bg-white focus:border-red-600 outline-none resize-y"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100">
                <button type="button" class="dong-modal-thuhoi px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-all">
                    Hủy
                </button>
                <button type="button" id="nutXacNhanThuHoiBlockchain" disabled class="px-6 py-2 bg-slate-200 text-slate-400 font-bold rounded-xl transition-all flex items-center gap-1.5 cursor-not-allowed">
                    <i class="fa-solid fa-ban"></i> Ban Hành Thu Hồi
                </button>
            </div>
        </div>

        <!-- TRẠNG THÁI 1: ĐANG THỰC THI (LOADING POPUP) -->
        <div id="manHinhDangGhiBlockchain" class="hidden text-center py-8 space-y-4">
            <div class="w-16 h-16 mx-auto rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center text-3xl">
                <i class="fa-solid fa-circle-notch fa-spin"></i>
            </div>
            <div class="space-y-1">
                <h4 class="text-base font-bold text-slate-900">Đang thực thi thu hồi...</h4>
                <p class="text-xs text-slate-500 max-w-xs mx-auto">Hệ thống đang cập nhật dữ liệu và kích hoạt Job đồng bộ Blockchain vào hàng chờ Queue.</p>
            </div>
        </div>

        <!-- TRẠNG THÁI 2: PHẢN HỒI KẾT QUẢ (SUCCESS / ERROR POPUP) -->
        <div id="manHinhKetQuaThuHoi" class="hidden text-center py-6 space-y-4">
            <div id="iconKetQuaThuHoi" class="w-16 h-16 mx-auto rounded-full flex items-center justify-center text-3xl"></div>

            <div class="space-y-1.5">
                <h4 id="tieuDeKetQuaThuHoi" class="text-base font-bold"></h4>
                <p id="noiDungKetQuaThuHoi" class="text-xs text-slate-600 leading-relaxed max-w-xs mx-auto"></p>
            </div>

            <div class="pt-2 border-t border-slate-100 flex justify-center">
                <button type="button" class="dong-modal-thuhoi px-6 py-2 bg-indigo-700 hover:bg-indigo-800 text-white font-bold rounded-xl text-xs transition-all shadow-md">
                    <i class="fa-solid fa-check mr-1"></i> Hoàn Tất & Đóng
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/coQuanQuanLy/quanLyThuHoi.js') }}"></script>
@endpush
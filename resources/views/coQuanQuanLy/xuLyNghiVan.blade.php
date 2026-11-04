@extends('layoutDungChung.master')

@section('title', 'Xử lý nghi vấn - PharmaChain')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/coQuanQuanLy/xuLyNghiVan.css') }}">
@endpush

@section('content')
<div class="space-y-6 max-w-7xl w-full mx-auto">

    <!-- THANH CÔNG CỤ -->
    <div class="bg-white p-5 rounded-2xl border border-emerald-900/10 bong-xanh">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 text-xs pointer-events-none">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" id="oTimKiemNghiVan" placeholder="Tìm theo mã báo cáo hoặc mã lô..." class="w-full pl-9 pr-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-medium text-slate-900 focus:bg-white focus:border-indigo-700 outline-none">
            </div>

            <select id="locTrangThaiBaoCao" class="px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:border-indigo-700 outline-none cursor-pointer">
                <option value="TAT_CA">-- Tất cả trạng thái --</option>
                <option value="DA_GUI">Đã gửi (Chờ tiếp nhận)</option>
                <option value="DANG_XU_LY">Đang xử lý</option>
                <option value="DA_XU_LY">Đã xử lý</option>
            </select>

            <select id="locNguonBaoCao" class="px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:border-indigo-700 outline-none cursor-pointer">
                <option value="TAT_CA">-- Tất cả nguồn gửi --</option>
                <option value="TO_CHUC">Tổ chức có tài khoản</option>
                <option value="NGUOI_TIEU_DUNG">Người tiêu dùng</option>
            </select>
        </div>
    </div>

    <!-- BẢNG DANH SÁCH BÁO CÁO NGHI VẤN -->
    <div class="bg-white rounded-2xl border border-emerald-900/10 bong-xanh overflow-hidden">
        <div class="p-4 bg-nenChinh border-b border-emerald-900/10 flex items-center justify-between text-xs">
            <span class="font-bold text-slate-700 uppercase tracking-wide flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation text-amber-600"></i> Báo Cáo Nghi Vấn Lô Thuốc
            </span>
            <span id="soLuongBaoCaoBadge" class="font-bold text-slate-500 font-mono">Hiển thị: 0 báo cáo</span>
        </div>

        <div class="w-full overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 uppercase tracking-wider border-b border-slate-100 text-[11px]">
                        <th class="py-3.5 px-3 font-bold whitespace-nowrap">Mã Báo Cáo</th>
                        <th class="py-3.5 px-3 font-bold whitespace-nowrap">Mã Lô Khai Báo</th>
                        <th class="py-3.5 px-3 font-bold">Người Báo Cáo</th>
                        <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Ngày Gửi</th>
                        <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Trạng Thái</th>
                        <th class="py-3.5 px-3 font-bold text-center whitespace-nowrap">Hành Động</th>
                    </tr>
                </thead>
                <tbody id="thanBangNghiVan" class="divide-y divide-slate-100 font-medium"></tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
            <span id="thongTinPhanTrang" class="text-slate-500">Đang hiển thị...</span>
            <div class="flex items-center gap-1.5" id="cumNutPhanTrang"></div>
        </div>
    </div>

</div>

<!-- PANEL MODAL CHÍNH XỬ LÝ BÁO CÁO -->
<div id="modalXuLyBaoCao" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-3 sm:p-5 hidden">
    <div class="bg-white rounded-2xl max-w-4xl w-full p-5 sm:p-7 space-y-4 bong-xanh max-h-[92vh] flex flex-col">
        <div class="border-b border-slate-100 pb-3 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2.5 flex-wrap">
                <span class="text-base font-bold text-slate-900">Xử Lý Báo Cáo Nghi Vấn:</span>
                <span id="panelMaBaoCaoTitle" class="font-mono font-bold text-cyan-900 bg-cyan-50 px-2.5 py-0.5 rounded text-xs border border-cyan-200"></span>
                <span id="panelTrangThaiBadge"></span>
            </div>
            <button type="button" class="dong-modal-xuly text-slate-400 hover:text-slate-700 text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div id="panelTheKetQuaDaXuLy" class="hidden p-3 rounded-xl border text-xs shrink-0"></div>

        <div class="flex-1 overflow-y-auto space-y-4 pr-1 text-xs">
            <div class="bg-nenChinh p-4 rounded-xl border border-emerald-900/10 space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <span class="text-slate-500 block text-[11px]">Người báo cáo:</span>
                        <span id="panelNguoiGuiText" class="font-bold text-slate-900"></span>
                    </div>
                    <div>
                        <span class="text-slate-500 block text-[11px]">Mã lô khai báo:</span>
                        <span id="panelMaLoText" class="font-mono font-bold text-red-700"></span>
                    </div>
                    <div>
                        <span class="text-slate-500 block text-[11px]">Thời điểm gửi:</span>
                        <span id="panelThoiDiemGui" class="font-mono font-semibold text-slate-800"></span>
                    </div>
                </div>

                <div>
                    <span class="text-slate-500 block text-[11px] mb-0.5">Lý do nghi vấn:</span>
                    <div id="panelLyDoNghiVan" class="p-2 bg-white rounded-lg border border-slate-200 font-semibold text-slate-900"></div>
                </div>

                <div>
                    <span class="text-slate-500 block text-[11px] mb-0.5">Mô tả hiện trường:</span>
                    <div id="panelMoTaChiTiet" class="p-2.5 bg-white rounded-lg border border-slate-200 text-slate-800 leading-relaxed font-medium"></div>
                </div>

                <div id="panelMinhChungContainer" class="hidden">
                    <span class="text-slate-500 block text-[11px] mb-1 font-semibold">Tài liệu / Minh chứng đính kèm:</span>
                    <div id="panelDanhSachMinhChung" class="grid grid-cols-2 sm:grid-cols-3 gap-2"></div>
                </div>
            </div>
        </div>

        <div class="border-t border-slate-100 pt-3 flex items-center justify-between shrink-0">
            <button type="button" class="dong-modal-xuly px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs">
                Đóng
            </button>

            <div id="panelCumNutQuyetDinh" class="flex items-center gap-2 flex-wrap">
                <button type="button" id="nutMoModalTuChoiBaoCao" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 font-bold rounded-xl text-xs">
                    <i class="fa-solid fa-circle-xmark"></i> Từ Chối
                </button>
                <button type="button" id="nutMoModalGhiNhanHangGia" class="hidden px-5 py-2 bg-indigo-700 hover:bg-indigo-800 text-white font-bold rounded-xl text-xs shadow-md">
                    <i class="fa-solid fa-shield-cat"></i> Ghi Nhận Hàng Giả (Ngoại Vi)
                </button>
                <button type="button" id="nutMoModalThuHoiTuBaoCao" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl text-xs shadow-md">
                    <i class="fa-solid fa-ban"></i> Thu Hồi Lô Hàng
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL THU HỒI TỪ BÁO CÁO -->
<div id="modalXacNhanThuHoiTuBaoCao" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 bong-xanh">
        <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
            <i class="fa-solid fa-ban text-red-600"></i> Ban Hành Thu Hồi Lô Thuốc
        </h3>
        <div class="space-y-3 text-xs">
            <div class="space-y-1">
                <label for="nhapLyDoThuHoiTuBC" class="font-bold text-slate-700 block">Lý do thu hồi:</label>
                <textarea id="nhapLyDoThuHoiTuBC" rows="3" class="w-full p-2.5 border rounded-xl outline-none focus:border-red-600"></textarea>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" class="dong-modal-thuhoi-bc px-4 py-2 bg-slate-100 rounded-xl">Hủy</button>
                <button type="button" id="nutXacNhanKyThuHoiOnChain" class="px-5 py-2 bg-red-600 text-white font-bold rounded-xl">Xác Nhận</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL TỪ CHỐI -->
<div id="modalXacNhanTuChoiBaoCao" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 bong-xanh">
        <h3 class="text-base font-bold text-slate-900">Từ Chối Báo Cáo Nghi Vấn</h3>
        <div class="space-y-3 text-xs">
            <textarea id="nhapLyDoTuChoiBC" rows="3" placeholder="Nhập lý do từ chối..." class="w-full p-2.5 border rounded-xl outline-none"></textarea>
            <div class="flex justify-end gap-2">
                <button type="button" class="dong-modal-tuchoi-bc px-4 py-2 bg-slate-100 rounded-xl">Hủy</button>
                <button type="button" id="nutXacNhanTuChoiBCOffChain" class="px-5 py-2 bg-slate-800 text-white font-bold rounded-xl">Xác Nhận Từ Chối</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL GHI NHẬN HÀNG GIẢ NGOẠI VI -->
<div id="modalXacNhanGhiNhanHangGia" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 bong-xanh">
        <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
            <i class="fa-solid fa-shield-cat text-indigo-700"></i> Ghi Nhận Hàng Giả Ngoại Vi
        </h3>
        <div class="space-y-3 text-xs">
            <p class="text-slate-600">Lô này không tồn tại trên hệ thống hoặc là hàng giả mạo ngoại vi chuỗi cung ứng. Nhập kết luận xử lý:</p>
            <textarea id="nhapGhiChuHangGia" rows="3" placeholder="Nhập kết luận / ghi chú hồ sơ..." class="w-full p-2.5 border rounded-xl outline-none"></textarea>
            <div class="flex justify-end gap-2">
                <button type="button" class="dong-modal-ghinhan-bc px-4 py-2 bg-slate-100 rounded-xl">Hủy</button>
                <button type="button" id="nutXacNhanGhiNhanHangGia" class="px-5 py-2 bg-indigo-700 text-white font-bold rounded-xl">Xác Nhận Ghi Nhận</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/coQuanQuanLy/xuLyNghiVan.js') }}"></script>
@endpush
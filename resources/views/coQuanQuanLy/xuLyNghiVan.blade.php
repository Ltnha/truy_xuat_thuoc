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
                <option value="TAT_CA">-- Tất cả trạng thái hồ sơ --</option>
                <option value="DA_GUI">Đã gửi (Chờ tiếp nhận)</option>
                <option value="DANG_XU_LY">Đang xử lý (Đang tra soát)</option>
                <option value="DA_XU_LY">Đã xử lý (Đã có quyết định)</option>
            </select>

            <select id="locNguonBaoCao" class="px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:border-indigo-700 outline-none cursor-pointer">
                <option value="TAT_CA">-- Tất cả nguồn phản ánh --</option>
                <option value="TO_CHUC">Tổ chức có tài khoản (NSX, NPP, Nhà thuốc)</option>
                <option value="NGUOI_TIEU_DUNG">Người tiêu dùng (Tra cứu không tài khoản)</option>
            </select>
        </div>
    </div>

    <!-- BẢNG DANH SÁCH BÁO CÁO NGHI VẤN -->
    <div class="bg-white rounded-2xl border border-emerald-900/10 bong-xanh overflow-hidden">
        <div class="p-4 bg-nenChinh border-b border-emerald-900/10 flex items-center justify-between text-xs">
            <span class="font-bold text-slate-700 uppercase tracking-wide flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation text-amber-600"></i> Danh Sách Báo Cáo Nghi Vấn Lô Thuốc Toàn Hệ Thống
            </span>
            <span id="soLuongBaoCaoBadge" class="font-bold text-slate-500 font-mono">Hiển thị: 0 báo cáo</span>
        </div>

        <div class="w-full overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 uppercase tracking-wider border-b border-slate-100 text-[11px]">
                        <th class="py-3.5 px-3 font-bold whitespace-nowrap">Mã Báo Cáo</th>
                        <th class="py-3.5 px-3 font-bold whitespace-nowrap">Mã Lô Khai Báo</th>
                        <th class="py-3.5 px-3 font-bold">Sản Phẩm</th>
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

<!-- PANEL CHÍNH: XỬ LÝ BÁO CÁO -->
<div id="modalXuLyBaoCao" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-3 sm:p-5 hidden">
    <div class="bg-white rounded-2xl max-w-5xl w-full p-5 sm:p-7 space-y-4 bong-xanh max-h-[92vh] flex flex-col">

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
                <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                    <span class="font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2 text-[11px]">
                        <i class="fa-solid fa-file-circle-exclamation text-amber-600"></i> Nội Dung Báo Cáo Nghi Vấn Từ Hiện Trường
                    </span>
                    <div class="flex items-center gap-2">
                        <span class="text-slate-500">Nguồn gửi:</span>
                        <span id="panelNguonGuiBadge"></span>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <span class="text-slate-500 block text-[11px]">Người / Đơn vị phản ánh:</span>
                        <span id="panelNguoiGuiText" class="font-bold text-slate-900"></span>
                    </div>
                    <div>
                        <span class="text-slate-500 block text-[11px]">Thời điểm tiếp nhận:</span>
                        <span id="panelThoiDiemGui" class="font-mono font-semibold text-slate-800"></span>
                    </div>
                    <div>
                        <span class="text-slate-500 block text-[11px]">Mã lô khai báo:</span>
                        <span id="panelMaLoText" class="font-mono font-bold text-red-700"></span>
                    </div>
                </div>

                <div>
                    <span class="text-slate-500 block text-[11px] mb-0.5">Nhóm lý do nghi vấn:</span>
                    <div id="panelLyDoNghiVan" class="p-2 bg-white rounded-lg border border-slate-200 font-semibold text-slate-900"></div>
                </div>

                <div>
                    <span class="text-slate-500 block text-[11px] mb-0.5">Mô tả hiện trường phản ánh:</span>
                    <div id="panelMoTaChiTiet" class="p-2.5 bg-white rounded-lg border border-slate-200 text-slate-800 leading-relaxed whitespace-pre-wrap font-medium"></div>
                </div>

                <div class="space-y-1.5 pt-1">
                    <span class="font-bold text-slate-700 block text-[11px]">Tệp minh chứng đính kèm:</span>
                    <div id="panelDanhSachMinhChung" class="grid grid-cols-1 sm:grid-cols-2 gap-2"></div>
                </div>
            </div>

            <div class="bg-indigo-50/70 p-4 rounded-xl border border-indigo-200 space-y-3">
                <div class="flex items-center justify-between border-b border-indigo-200 pb-2">
                    <span class="font-bold text-indigo-950 uppercase tracking-wide flex items-center gap-2 text-[11px]">
                        <i class="fa-solid fa-link text-indigo-700"></i> Tra Soát Lịch Sử Luân Chuyển Của Lô Trên Blockchain
                    </span>
                    <span id="panelTrangThaiOnChainLoBadge"></span>
                </div>

                <div id="panelCanhBaoLoKhongTonTai" class="hidden p-3 bg-red-100 border border-red-300 rounded-xl text-red-950 space-y-1">
                    <div class="font-bold flex items-center gap-1.5 text-red-700 text-xs">
                        <i class="fa-solid fa-triangle-exclamation"></i> Không tìm thấy lô này trên blockchain, có thể là hàng giả
                    </div>
                    <p class="text-[11px] leading-relaxed">
                        Mã lô khai báo chưa từng được khởi tạo trên Smart Contract hoặc cấp phép lưu hành. Đây là dấu hiệu của <strong>hàng giả, hàng nhái nhãn hiệu hoặc trôi nổi không rõ nguồn gốc</strong>.
                    </p>
                </div>

                <div id="panelKhungThongTinLoOnChain" class="grid grid-cols-2 sm:grid-cols-5 gap-2.5 p-3 bg-white rounded-xl border border-indigo-100 text-[11px]">
                    <div>
                        <span class="text-slate-500 block">Sản phẩm đăng ký:</span>
                        <span id="panelOnChainTenSP" class="font-bold text-slate-900"></span>
                    </div>
                    <div>
                        <span class="text-slate-500 block">Số ĐK lưu hành:</span>
                        <span id="panelOnChainSoDK" class="font-mono font-bold text-cyan-800"></span>
                    </div>
                    <div>
                        <span class="text-slate-500 block">Nhà sản xuất:</span>
                        <span id="panelOnChainNSX" class="font-semibold text-slate-800"></span>
                    </div>
                    <div>
                        <span class="text-slate-500 block">Hạn dùng on-chain:</span>
                        <span id="panelOnChainHanDung" class="font-mono font-bold text-slate-800"></span>
                    </div>
                    <div>
                        <span class="text-slate-500 block">Đã xuất bán lẻ (DISPENSED):</span>
                        <span id="panelOnChainSoLuongDaBanLe" class="font-mono font-bold text-red-600">0 hộp</span>
                    </div>
                </div>

                <div id="panelKhungTimelineChuyenGiao" class="space-y-2">
                    <span class="font-bold text-slate-700 block text-[11px]">Nhật ký luân chuyển qua các mắt xích:</span>
                    <div id="panelDanhSachTimeline" class="space-y-2 max-h-56 overflow-y-auto pr-1"></div>
                </div>
            </div>

            <div class="border-t border-slate-200 pt-3 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-slate-700 uppercase tracking-wide flex items-center gap-2 text-[11px]">
                        <i class="fa-solid fa-list-check text-slate-500"></i> Các Báo Cáo Khác Cùng Mã Lô Này
                    </span>
                    <span id="panelSoLuongCungLoNote" class="text-[10px] text-slate-500 font-mono"></span>
                </div>
                <div id="panelDanhSachBaoCaoCungLo" class="space-y-1.5"></div>
            </div>

        </div>

        <div class="border-t border-slate-100 pt-3 flex items-center justify-between shrink-0">
            <button type="button" class="dong-modal-xuly px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition-all">
                Đóng
            </button>

            <div id="panelCumNutQuyetDinh" class="flex items-center gap-2 flex-wrap">
                <button type="button" id="nutGopXuLyCungLo" class="hidden px-3.5 py-2 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 font-bold rounded-xl text-xs transition-all flex items-center gap-1.5">
                    <i class="fa-solid fa-layer-group"></i> Xử lý gộp cùng các báo cáo khác của lô này
                </button>
                <button type="button" id="nutMoModalTuChoiBaoCao" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 font-bold rounded-xl text-xs transition-all flex items-center gap-1.5">
                    <i class="fa-solid fa-circle-xmark"></i> Từ Chối Báo Cáo
                </button>
                <button type="button" id="nutMoModalGhiNhanHangGia" class="hidden px-5 py-2 bg-indigo-700 hover:bg-indigo-800 text-white font-bold rounded-xl text-xs transition-all flex items-center gap-1.5 shadow-md">
                    <i class="fa-solid fa-shield-cat"></i> Ghi Nhận (Chuyển Xử Lý Ngoài Hệ Thống)
                </button>
                <button type="button" id="nutXacNhanGhiNhanLoDaThuHoi" class="hidden px-5 py-2 bg-red-700 hover:bg-red-800 text-white font-bold rounded-xl text-xs transition-all flex items-center gap-1.5 shadow-md">
                    <i class="fa-solid fa-check-double"></i> Ghi Nhận (Lô Đã Thu Hồi)
                </button>
                <button type="button" id="nutMoModalThuHoiTuBaoCao" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl text-xs transition-all flex items-center gap-1.5 shadow-md">
                    <i class="fa-solid fa-ban"></i> Thu Hồi Lô Hàng
                </button>
            </div>
        </div>

    </div>
</div>

<!-- MODAL CON 1: THU HỒI LÔ HÀNG -->
<div id="modalXacNhanThuHoiTuBaoCao" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-lg w-full p-5 sm:p-7 space-y-4 bong-xanh max-h-[92vh] flex flex-col">
        <div class="border-b border-slate-100 pb-2.5 flex items-center justify-between shrink-0">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-ban text-red-600"></i> Ban Hành Thu Hồi Lô Thuốc
            </h3>
            <button type="button" class="dong-modal-thuhoi-bc text-slate-400 hover:text-slate-700 text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div id="manHinhFormThuHoiBC" class="flex-1 overflow-y-auto space-y-3.5 text-xs pr-1">
            <div class="p-3 bg-nenChinh rounded-xl border border-emerald-900/10 space-y-1.5">
                <div class="flex justify-between items-center">
                    <span class="text-slate-500">Mã lô thu hồi:</span>
                    <span id="modalThuHoiMaLo" class="font-mono font-bold text-red-700"></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-500">Căn cứ báo cáo:</span>
                    <span id="modalThuHoiCanCuBC" class="font-mono font-bold text-cyan-800"></span>
                </div>
                <div class="pt-1 border-t border-slate-200">
                    <span class="text-slate-500 block text-[11px] mb-1">Hiện trạng tồn kho phân bổ trong chuỗi:</span>
                    <div id="modalThuHoiPhanBoKho" class="space-y-1 bg-white p-2 rounded-lg border border-slate-200 font-mono text-[11px]"></div>
                </div>
            </div>

            <div class="p-3 bg-red-50 rounded-xl border border-red-200 text-red-950 space-y-1 leading-relaxed text-[11px]">
                <div class="font-bold flex items-center gap-1 text-red-700">
                    <i class="fa-solid fa-triangle-exclamation"></i> Phạm vi ảnh hưởng:
                </div>
                <ul class="list-disc list-inside space-y-0.5 text-slate-700 text-[10px]">
                    <li>Nhà thuốc không thể bán các đơn vị thuộc lô này nữa, người tiêu dùng tra cứu sẽ thấy "Đã thu hồi".</li>
                    <li>Quyết định được <strong>ghi lên blockchain, không thể hoàn tác</strong>.</li>
                </ul>
            </div>

            <div class="space-y-1">
                <label for="nhapLyDoThuHoiTuBC" class="font-bold text-slate-700 uppercase tracking-wide block text-[11px]">
                    Lý Do Thu Hồi Lô Thuốc <span class="text-red-500">*</span>
                </label>
                <textarea id="nhapLyDoThuHoiTuBC" rows="3" required placeholder="Nhập lý do thu hồi..." class="w-full p-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl font-medium text-slate-900 focus:bg-white focus:border-red-600 outline-none resize-y"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100">
                <button type="button" class="dong-modal-thuhoi-bc px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-all">
                    Hủy
                </button>
                <button type="button" id="nutXacNhanKýThuHoiOnChain" class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl shadow-md transition-all flex items-center gap-1.5">
                    <i class="fa-solid fa-ban"></i> Xác Nhận Thu Hồi
                </button>
            </div>
        </div>

        <div id="manHinhDangGhiBlockchainThuHoi" class="hidden text-center py-6 space-y-4">
            <div class="w-14 h-14 mx-auto rounded-full bg-red-100 text-red-600 flex items-center justify-center text-2xl">
                <i class="fa-solid fa-cube fa-bounce"></i>
            </div>
            <div class="space-y-1">
                <h4 class="text-sm font-bold text-slate-900">Đang ghi nhận trên blockchain...</h4>
                <p class="text-xs text-slate-500">Đang ký giao dịch recallBatch() bằng ví Cơ quan quản lý.</p>
            </div>
        </div>

        <div id="manHinhLoiBlockchainThuHoi" class="hidden text-center py-4 space-y-4">
            <div class="w-12 h-12 mx-auto rounded-full bg-red-100 text-red-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div class="space-y-1 text-xs">
                <h4 class="text-sm font-bold text-red-700">Giao Dịch Blockchain Thất Bại!</h4>
                <p class="text-slate-600">Lỗi kết nối RPC Node Polygon Amoy. Trạng thái báo cáo được giữ nguyên.</p>
            </div>
            <div class="flex items-center justify-center gap-3 pt-2">
                <button type="button" class="dong-modal-thuhoi-bc px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs">
                    Đóng
                </button>
                <button type="button" id="nutThuLaiThuHoiBlockchain" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl text-xs flex items-center gap-1.5">
                    <i class="fa-solid fa-rotate-right"></i> Thử Lại
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL CON 2: TỪ CHỐI BÁO CÁO -->
<div id="modalXacNhanTuChoiBaoCao" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 sm:p-7 space-y-4 bong-xanh">
        <div class="border-b border-slate-100 pb-2.5 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-circle-xmark text-slate-700"></i> Từ Chối Báo Cáo Nghi Vấn
            </h3>
            <button type="button" class="dong-modal-tuchoi-bc text-slate-400 hover:text-slate-700 text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="space-y-3.5 text-xs">
            <div class="p-3 bg-nenChinh rounded-xl border border-emerald-900/10 space-y-1">
                <div class="flex justify-between">
                    <span class="text-slate-500">Mã báo cáo:</span>
                    <span id="modalTuChoiMaBC" class="font-mono font-bold text-cyan-900"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Mã lô khai báo:</span>
                    <span id="modalTuChoiMaLo" class="font-mono font-bold text-slate-900"></span>
                </div>
            </div>

            <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200 text-slate-700 text-[11px] leading-relaxed">
                <i class="fa-solid fa-circle-info text-slate-500 mr-1"></i> Lý do sẽ hiển thị cho người báo cáo khi họ xem lại báo cáo.
            </div>

            <div class="space-y-1">
                <div class="flex items-center justify-between">
                    <label for="nhapLyDoTuChoiBC" class="font-bold text-slate-700 uppercase tracking-wide block text-[11px]">
                        Lý Do Từ Chối Báo Cáo <span class="text-red-500">*</span>
                    </label>
                    <span id="demKyTuTuChoiBC" class="text-[10px] text-slate-400 font-mono">0/500</span>
                </div>
                <textarea id="nhapLyDoTuChoiBC" maxlength="500" rows="3" required placeholder="Nêu rõ căn cứ từ chối..." class="w-full p-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl font-medium text-slate-900 focus:bg-white focus:border-slate-800 outline-none resize-y"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100">
                <button type="button" class="dong-modal-tuchoi-bc px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-all">
                    Hủy
                </button>
                <button type="button" id="nutXacNhanTuChoiBCOffChain" disabled class="px-6 py-2 bg-slate-200 text-slate-400 font-bold rounded-xl transition-all flex items-center gap-1.5 cursor-not-allowed shadow-none">
                    <i class="fa-solid fa-circle-xmark"></i> Xác Nhận Từ Chối
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL CON 3: GHI NHẬN HÀNG GIẢ -->
<div id="modalXacNhanGhiNhanHangGia" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 sm:p-7 space-y-4 bong-xanh">
        <div class="border-b border-slate-100 pb-2.5 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-shield-cat text-indigo-700"></i> Ghi Nhận Xử Lý Hàng Giả / Ngoài Hệ Thống
            </h3>
            <button type="button" class="dong-modal-hanggia-bc text-slate-400 hover:text-slate-700 text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="space-y-3.5 text-xs">
            <div class="p-3 bg-red-50 rounded-xl border border-red-200 text-red-950 space-y-1 leading-relaxed text-[11px]">
                <div class="font-bold flex items-center gap-1 text-red-700">
                    <i class="fa-solid fa-triangle-exclamation"></i> Chuyển hồ sơ cơ quan chức năng:
                </div>
                <p class="text-slate-700 text-[10px]">
                    Lô thuốc không tồn tại trên sổ cái blockchain. Báo cáo này sẽ được lưu với kết quả <strong>"Ghi nhận, chuyển xử lý ngoài hệ thống" (GHI_NHAN)</strong> để chuyển Công an / Quản lý thị trường thụ lý điều tra.
                </p>
            </div>

            <div class="space-y-1">
                <label for="nhapGhiChuHangGiaBC" class="font-bold text-slate-700 uppercase tracking-wide block text-[11px]">
                    Ghi Chú Xử Lý Ngoại Vi <span class="text-red-500">*</span>
                </label>
                <textarea id="nhapGhiChuHangGiaBC" rows="3" required placeholder="Nhập ghi chú..." class="w-full p-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl font-medium text-slate-900 focus:bg-white focus:border-indigo-700 outline-none resize-y"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100">
                <button type="button" class="dong-modal-hanggia-bc px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-all">
                    Hủy
                </button>
                <button type="button" id="nutXacNhanGhiNhanHangGiaSubmit" class="px-6 py-2 bg-indigo-700 hover:bg-indigo-800 text-white font-bold rounded-xl shadow-md transition-all flex items-center gap-1.5">
                    <i class="fa-solid fa-check"></i> Xác Nhận Chuyển Xử Lý
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/coQuanQuanLy/xuLyNghiVan.js') }}"></script>
@endpush
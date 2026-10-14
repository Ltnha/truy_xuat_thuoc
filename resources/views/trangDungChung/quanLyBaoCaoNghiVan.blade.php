@extends('layoutDungChung.master')

@section('title', $tieuDeTrang ?? 'Quản lý báo cáo nghi vấn - PharmaChain')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/trangDungChung/quanLyBaoCaoNghiVan.css') }}">
@endpush

@section('content')
<div class="space-y-6 max-w-7xl w-full mx-auto">

    <!-- THANH TRÊN: TÌM KIẾM + BỘ LỌC + NÚT GỬI BÁO CÁO MỚI -->
    <div class="bg-white p-5 rounded-2xl border border-emerald-900/10 bong-xanh flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">

        <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-3 max-w-lg">
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 text-xs pointer-events-none">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" id="oTimKiemBaoCao" placeholder="Tìm theo mã báo cáo, mã lô..." class="w-full pl-9 pr-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-medium text-slate-900 focus:bg-white focus:border-emerald-700 outline-none">
            </div>

            <!-- Lọc 3 trạng thái chuẩn hóa -->
            <select id="locTrangThaiBaoCao" class="px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:border-emerald-700 outline-none cursor-pointer">
                <option value="TAT_CA">-- Tất cả trạng thái --</option>
                <option value="DA_GUI">Đã gửi (Chờ xem xét)</option>
                <option value="DANG_XU_LY">Đang xử lý (Đang xác minh)</option>
                <option value="DA_XU_LY">Đã xử lý (Có quyết định)</option>
            </select>
        </div>

        <button type="button" id="nutMoModalGuiBaoCao" class="px-5 py-2.5 {{ $role['btnClass'] ?? 'bg-emerald-700 hover:bg-emerald-800 text-white' }} rounded-xl text-xs font-bold shadow-md transition-all flex items-center justify-center gap-2 shrink-0">
            <i class="fa-solid fa-file-circle-exclamation text-sm"></i> Gửi Báo Cáo Mới
        </button>
    </div>

    <!-- BẢNG DANH SÁCH BÁO CÁO -->
    <div class="bg-white rounded-2xl border border-emerald-900/10 bong-xanh overflow-hidden">
        <div class="p-4 bg-nenChinh border-b border-emerald-900/10 flex items-center justify-between text-xs">
            <span class="font-bold text-slate-700 uppercase tracking-wide flex items-center gap-2">
                <i class="fa-solid fa-list-check text-cyan-800"></i> Lịch Sử Báo Cáo Nghi Vấn Của Tổ Chức
            </span>
            <span id="soLuongBaoCaoBadge" class="font-bold text-slate-500 font-mono">Hiển thị: 0 báo cáo</span>
        </div>

        <div class="w-full overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 uppercase tracking-wider border-b border-slate-100 text-[11px]">
                        <th class="py-3.5 px-3 font-bold whitespace-nowrap">Mã Báo Cáo</th>
                        <th class="py-3.5 px-3 font-bold whitespace-nowrap">Mã Lô Liên Quan</th>
                        <th class="py-3.5 px-3 font-bold">Lý Do Nghi Vấn</th>
                        <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Ngày Gửi</th>
                        <th class="py-3.5 px-3 font-bold text-center whitespace-nowrap">Trạng Thái & Kết Quả</th>
                        <th class="py-3.5 px-3 font-bold text-center whitespace-nowrap">Thao Tác</th>
                    </tr>
                </thead>
                <tbody id="thanBangBaoCao" class="divide-y divide-slate-100 font-medium">
                    <!-- Dữ liệu render qua file JS -->
                </tbody>
            </table>
        </div>

        <!-- Phân trang -->
        <div class="p-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
            <span id="thongTinPhanTrang" class="text-slate-500">Đang hiển thị...</span>
            <div class="flex items-center gap-1.5" id="cumNutPhanTrang"></div>
        </div>
    </div>

</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!-- MODAL 1: GỬI BÁO CÁO NGHI VẤN (FR20 / FR22)              -->
<!-- ══════════════════════════════════════════════════════════ -->
<div id="modalGuiBaoCaoMoi" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden bg-black/60">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 sm:p-8 space-y-5 bong-xanh max-h-[90vh] overflow-y-auto">

        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-file-circle-exclamation text-amber-600"></i> Gửi Báo Cáo Nghi Vấn
            </h3>
            <button type="button" class="dong-modal-gui text-slate-400 hover:text-slate-700 text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="formSubmitBaoCao" class="space-y-4 text-xs">
            <!-- 1. Mã lô -->
            <div class="space-y-1 relative" id="khungChonMaLo">
                <label for="nhapMaLoLienQuan" class="block font-bold text-slate-700 uppercase tracking-wide text-xs">
                    Mã Lô Thuốc Nghi Vấn <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 text-xs pointer-events-none">
                        <i class="fa-solid fa-barcode"></i>
                    </span>
                    <input type="text" id="nhapMaLoLienQuan" required autocomplete="off" placeholder="Nhập mã lô nghi vấn (cho phép nhập cả mã nghi giả)..." class="w-full pl-9 pr-9 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl font-mono uppercase font-semibold text-slate-900 focus:bg-white focus:border-emerald-700 outline-none">
                    <button type="button" id="nutMoMenuLo" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-emerald-700 text-xs">
                        <i class="fa-solid fa-chevron-down" id="iconMuiTenLo"></i>
                    </button>
                </div>

                <!-- Bảng gợi ý các lô trong kho -->
                <div id="menuGoiYMaLo" class="hidden absolute left-0 right-0 top-full mt-1 bg-white border border-emerald-900/15 rounded-xl shadow-xl z-50 max-h-52 overflow-y-auto divide-y divide-slate-100 bong-xanh">
                    <div class="item-lo p-2.5 hover:bg-emerald-50 cursor-pointer flex items-center justify-between" data-malo="LOT-PARA-2026">
                        <div>
                            <div class="font-mono font-bold text-cyan-900 text-xs">LOT-PARA-2026</div>
                            <div class="text-[11px] text-slate-500">Paracetamol 500mg</div>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">Kho hàng</span>
                    </div>
                    <div class="item-lo p-2.5 hover:bg-emerald-50 cursor-pointer flex items-center justify-between" data-malo="LOT-DES-2026-01">
                        <div>
                            <div class="font-mono font-bold text-cyan-900 text-xs">LOT-DES-2026-01</div>
                            <div class="text-[11px] text-slate-500">Deslotid OPV 5mg</div>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-cyan-100 text-cyan-800">Đang lưu giữ</span>
                    </div>
                    <div class="item-lo p-2.5 hover:bg-emerald-50 cursor-pointer flex items-center justify-between" data-malo="LOT-AMOX-2025-09">
                        <div>
                            <div class="font-mono font-bold text-cyan-900 text-xs">LOT-AMOX-2025-09</div>
                            <div class="text-[11px] text-slate-500">Amoxicillin 500mg</div>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">Lô đang giữ</span>
                    </div>
                </div>
            </div>

            <!-- 2. Lý do nghi vấn -->
            <div class="space-y-1">
                <label for="chonLyDoNghiVan" class="font-bold text-slate-700 uppercase tracking-wide">
                    Lý Do Nghi Vấn <span class="text-red-500">*</span>
                </label>
                <select id="chonLyDoNghiVan" required class="w-full px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:border-emerald-700 outline-none cursor-pointer">
                    <option value="">-- Chọn nhóm lý do nghi vấn --</option>
                    <option value="Bao bì có dấu hiệu làm giả / in đè nhãn">Bao bì có dấu hiệu làm giả / in đè nhãn</option>
                    <option value="Hạn sử dụng bị sửa đổi / tẩy xóa">Hạn sử dụng bị sửa đổi / tẩy xóa</option>
                    <option value="Mã QR không quét được hoặc không hợp lệ">Mã QR không quét được hoặc không hợp lệ</option>
                    <option value="Thuốc có dấu hiệu biến chất / đổi màu / ẩm mốc">Thuốc có dấu hiệu biến chất / đổi màu / ẩm mốc</option>
                    <option value="Khác">Lý do bất thường khác</option>
                </select>
            </div>

            <!-- 3. Mô tả chi tiết -->
            <div class="space-y-1">
                <label for="nhapMoTaChiTiet" class="font-bold text-slate-700 uppercase tracking-wide">
                    Mô Tả Chi Tiết Hiện Trường <span class="text-red-500">*</span>
                </label>
                <textarea id="nhapMoTaChiTiet" rows="3" required placeholder="Mô tả cụ thể vị trí phát hiện, dấu hiệu mờ nhòe, sai lệch niêm phong, nghi vấn xuất xứ..." class="w-full p-3 bg-nenChinh border border-emerald-900/15 rounded-xl font-medium text-slate-900 focus:bg-white focus:border-emerald-700 outline-none resize-y"></textarea>
            </div>

            <!-- 4. Tải tệp bằng chứng -->
            <div class="space-y-1.5">
                <label class="font-bold text-slate-700 uppercase tracking-wide flex justify-between items-center">
                    <span>Đính Kèm Bằng Chứng (Tối đa 5 tệp) <span class="text-red-500">*</span></span>
                    <span id="soLuongTepDaChon" class="text-[11px] text-slate-400 font-normal">0/5 tệp</span>
                </label>

                <div id="khungChonTepMinhChung" class="border-2 border-dashed border-emerald-900/20 hover:border-emerald-700 rounded-xl p-3.5 text-center bg-nenChinh cursor-pointer transition-all">
                    <input type="file" id="tepMinhChungInput" accept="image/jpeg,image/png,application/pdf" multiple class="hidden">
                    <div class="space-y-1">
                        <i class="fa-solid fa-cloud-arrow-up text-xl text-emerald-700"></i>
                        <div class="text-xs text-slate-700 font-semibold">Nhấp để chọn ảnh chụp (JPG, PNG) hoặc biên bản kiểm nghiệm (PDF)</div>
                        <p class="text-[10px] text-slate-400">Tối đa 5 tệp, dung lượng không quá 10MB mỗi tệp</p>
                    </div>
                </div>

                <div id="danhSachTepDaChon" class="space-y-1.5 pt-1"></div>
            </div>

            <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-[11px] text-amber-900 space-y-1 leading-relaxed">
                <div class="font-bold flex items-center gap-1.5">
                    <i class="fa-solid fa-circle-info text-amber-600"></i> Quy định xử lý báo cáo nghi vấn:
                </div>
                <ul class="list-disc list-inside space-y-0.5 text-slate-700 text-[10px]">
                    <li>Báo cáo sau khi gửi <strong>không thể rút lại</strong>.</li>
                    <li>Cơ quan quản lý sẽ tiếp nhận để tra soát lịch sử luân chuyển của lô trên blockchain.</li>
                    <li>Bạn theo dõi kết quả thẩm định trực tiếp tại trang này, hệ thống <strong>không gửi thông báo riêng</strong>.</li>
                </ul>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                <button type="button" class="dong-modal-gui px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-all">
                    Hủy
                </button>
                <button type="submit" class="px-6 py-2.5 {{ $role['btnClass'] ?? 'bg-emerald-700 hover:bg-emerald-800 text-white' }} font-bold rounded-xl shadow-md transition-all flex items-center gap-2">
                    <i class="fa-solid fa-paper-plane"></i> Gửi Báo Cáo
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!-- MODAL 2: XEM CHI TIẾT BÁO CÁO                            -->
<!-- ══════════════════════════════════════════════════════════ -->
<div id="modalChiTietBaoCao" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden bg-black/60">
    <div class="bg-white rounded-2xl max-w-xl w-full p-6 sm:p-8 space-y-5 bong-xanh max-h-[90vh] overflow-y-auto">

        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-base font-bold text-slate-900">Chi Tiết Báo Cáo:</span>
                <span id="chiTietMaBaoCao" class="font-mono font-bold text-cyan-800 bg-cyan-50 px-2.5 py-0.5 rounded text-xs border border-cyan-200"></span>
            </div>
            <button type="button" id="nutDongChiTiet" class="text-slate-400 hover:text-slate-700 text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="space-y-4 text-xs">
            <div class="grid grid-cols-2 gap-3 p-3.5 bg-nenChinh rounded-xl border border-emerald-900/10">
                <div>
                    <span class="text-slate-500 block">Mã lô phản ánh:</span>
                    <span id="chiTietMaLo" class="font-mono font-bold text-slate-900 text-sm"></span>
                </div>
                <div>
                    <span class="text-slate-500 block">Ngày gửi báo cáo:</span>
                    <span id="chiTietNgayGui" class="font-semibold text-slate-700 font-mono"></span>
                </div>
                <div>
                    <span class="text-slate-500 block">Đơn vị gửi báo cáo:</span>
                    <span class="font-bold text-slate-800">{{ $role['tenToChucMacDinh'] ?? 'Doanh nghiệp dược phẩm' }}</span>
                </div>
                <div>
                    <span class="text-slate-500 block">Trạng thái hồ sơ:</span>
                    <div id="chiTietTrangThaiBadge" class="pt-0.5"></div>
                </div>
            </div>

            <div>
                <span class="text-slate-600 font-bold block mb-1">Nhóm lý do nghi vấn:</span>
                <div id="chiTietLyDo" class="p-2.5 bg-slate-50 rounded-lg text-slate-900 font-semibold border border-slate-200"></div>
            </div>

            <div>
                <span class="text-slate-600 font-bold block mb-1">Mô tả chi tiết hiện trường:</span>
                <p id="chiTietMoTa" class="p-3 bg-slate-50 rounded-xl text-slate-800 leading-relaxed border border-slate-200 break-words whitespace-pre-wrap"></p>
            </div>

            <div>
                <span class="text-slate-600 font-bold block mb-1">Bằng chứng đính kèm (minhChungBaoCao):</span>
                <div id="chiTietDanhSachMinhChung" class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <!-- Render ảnh hoặc tài liệu PDF qua JS -->
                </div>
            </div>

            <div class="border-t border-slate-100 pt-3 space-y-2">
                <span class="text-slate-700 font-bold block flex items-center gap-1.5 text-xs">
                    <i class="fa-solid fa-building-columns text-indigo-700"></i> Kết Quả Xem Xét Của Cơ Quan Quản Lý:
                </span>
                <div id="noiDungKetQuaXuLy"></div>
            </div>
        </div>

        <div class="pt-2 border-t border-slate-100 flex justify-end">
            <button type="button" id="nutDongChiTietBottom" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-all">
                Đóng
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/trangDungChung/quanLyBaoCaoNghiVan.js') }}"></script>
@endpush
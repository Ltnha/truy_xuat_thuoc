@extends('layoutNguoiTieuDung.main')

@section('title', 'Báo cáo nghi vấn chất lượng thuốc - PharmaChain')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/nguoiTieuDung/baoCaoNghiVan.css') }}">
@endpush

@section('content')
<main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="max-w-4xl w-full mx-auto space-y-6">

        <!-- THANH CHUYỂN TAB CÔNG KHAI -->
        <div class="bg-white p-1.5 rounded-2xl border border-emerald-900/10 bong-xanh flex items-center gap-2">
            <button type="button" id="tabGuiBaoCao" class="flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all bg-emerald-700 text-white shadow-xs cursor-pointer">
                <i class="fa-solid fa-file-circle-exclamation text-base"></i> Gửi Báo Cáo Nghi Vấn
            </button>
            <button type="button" id="tabLichSuBaoCao" class="flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all text-slate-600 hover:text-emerald-700 hover:bg-emerald-50 cursor-pointer">
                <i class="fa-solid fa-clock-rotate-left text-base"></i> Lịch Sử & Tra Cứu
            </button>
        </div>

        <!-- KHUNG THÔNG BÁO TRẠNG THÁI INLINE -->
        <div id="thongBaoPublic" class="hidden p-3.5 rounded-xl border text-xs font-semibold flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <i id="iconThongBaoPublic" class="fa-solid fa-circle-info"></i>
                <span id="noiDungThongBaoPublic"></span>
            </div>
            <button type="button" id="dongThongBaoPublic" class="opacity-70 hover:opacity-100 cursor-pointer">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- TAB 1: FORM GỬI BÁO CÁO NGHI VẤN -->
        <div id="khungTabGuiBaoCao" class="bg-white rounded-2xl p-6 sm:p-8 border border-emerald-900/10 bong-xanh space-y-5">
            <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-shield-exclamation text-amber-600"></i> Báo Cáo Nghi Vấn Thuốc Kém Chất Lượng / Giả Mạo
                </h2>
            </div>

            <form id="formGuiBaoCao" class="space-y-4 text-xs" enctype="multipart/form-data">
                @csrf

                <!-- 1. Mã Lô Thuốc + Nút Tải Ảnh QR -->
                <div class="space-y-1.5">
                    <label for="maLoBaoCao" class="block font-bold text-slate-700 uppercase">
                        Mã Lô Thuốc Nghi Vấn (In Trên Vỏ Hộp) <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-stretch gap-2">
                        <div class="relative flex-1">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 text-xs pointer-events-none">
                                <i class="fa-solid fa-barcode"></i>
                            </span>
                            <input type="text" id="maLoBaoCao" name="maLo" required autocomplete="off" placeholder="Nhập mã lô hoặc tải ảnh mã QR trên vỏ hộp..." class="w-full pl-9 pr-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl font-mono font-semibold text-slate-900 focus:bg-white focus:border-emerald-700 outline-none">
                        </div>
                        <label for="inputAnhQR" class="px-4 py-2.5 bg-cyan-800 hover:bg-cyan-900 text-white font-bold text-xs rounded-xl transition-all flex items-center gap-1.5 shrink-0 shadow-2xs cursor-pointer" title="Tải ảnh chụp hoặc tệp SVG mã QR (JPG, PNG, WEBP, SVG)">
                            <i class="fa-solid fa-file-arrow-up text-sm"></i>
                            <span>Tải ảnh QR</span>
                            <input type="file" id="inputAnhQR" accept="image/jpeg,image/png,image/webp,image/svg+xml,.svg" class="hidden">
                        </label>
                    </div>
                    <div id="qrFileHiddenReader" class="hidden"></div>
                    <!-- Dòng nhận diện nhanh mã lô -->
                    <div id="trangThaiNhanDienLo" class="hidden text-[11px] font-semibold px-3 py-1.5 rounded-lg border"></div>
                </div>

                <!-- 2. Nhóm Lý Do Nghi Vấn -->
                <div class="space-y-1">
                    <label for="chonLyDoPublic" class="block font-bold text-slate-700 uppercase">
                        Lý Do Nghi Vấn <span class="text-red-500">*</span>
                    </label>
                    <select id="chonLyDoPublic" name="lyDo" required class="w-full px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:border-emerald-700 outline-none cursor-pointer">
                        <option value="">-- Chọn nhóm lý do nghi vấn --</option>
                        <option value="Bao bì có dấu hiệu làm giả / in đè nhãn">Bao bì có dấu hiệu làm giả / in đè nhãn</option>
                        <option value="Hạn sử dụng bị sửa đổi / tẩy xóa">Hạn sử dụng bị sửa đổi / tẩy xóa</option>
                        <option value="Mã QR không quét được hoặc không hợp lệ">Mã QR không quét được hoặc không hợp lệ</option>
                        <option value="Thuốc có dấu hiệu biến chất / đổi màu / ẩm mốc">Thuốc có dấu hiệu biến chất / đổi màu / ẩm mốc</option>
                        <option value="Khác">Lý do bất thường khác</option>
                    </select>
                </div>

                <!-- 3. Mô Tả Chi Tiết -->
                <div class="space-y-1">
                    <label for="moTaChiTietPublic" class="block font-bold text-slate-700 uppercase">
                        Mô Tả Chi Tiết Hiện Trường
                    </label>
                    <textarea id="moTaChiTietPublic" name="moTa" rows="3" placeholder="Nhập thêm chi tiết dấu hiệu bất thường, địa điểm nhà thuốc đã mua..." class="w-full p-3 bg-nenChinh border border-emerald-900/15 rounded-xl text-slate-900 focus:bg-white focus:border-emerald-700 outline-none resize-y"></textarea>
                </div>

                <!-- 4. Đính Kèm Bằng Chứng (Tối đa 5 tệp) -->
                <div class="space-y-1">
                    <div class="flex justify-between items-center font-bold text-slate-700 uppercase">
                        <span>Đính Kèm Bằng Chứng (Ảnh/PDF &lt; 10MB)</span>
                        <span id="soLuongTepDaChonPublic" class="text-[11px] text-slate-400 font-normal">0/5 tệp</span>
                    </div>
                    <div id="khungKeoThaMinhChung" class="border-2 border-dashed border-emerald-900/20 hover:border-emerald-700 rounded-xl p-3 text-center bg-nenChinh cursor-pointer transition-all">
                        <input type="file" id="tepMinhChung" name="minhChung[]" accept="image/jpeg,image/png,application/pdf" multiple class="hidden">
                        <div id="tenTepMinhChung" class="text-xs text-slate-600 font-semibold flex items-center justify-center gap-2">
                            <i class="fa-solid fa-cloud-arrow-up text-base text-emerald-700"></i>
                            <span>Nhấp để chọn tệp bằng chứng (Tối đa 5 tệp: JPG, PNG, PDF)</span>
                        </div>
                    </div>
                    <div id="danhSachTepDaChonPublic" class="space-y-1 pt-1"></div>
                </div>

                <button type="submit" id="nutGuiBaoCaoSubmit" class="w-full py-3 bg-emerald-700 hover:bg-emerald-800 text-white font-bold rounded-xl shadow-md transition-all flex items-center justify-center gap-2 cursor-pointer text-xs mt-2">
                    <i class="fa-solid fa-paper-plane"></i> Gửi Báo Cáo Nghi Vấn
                </button>
            </form>
        </div>

        <!-- TAB 2: LỊCH SỬ & TRA CỨU MÃ BÁO CÁO -->
        <div id="khungTabLichSuBaoCao" class="space-y-6 hidden">
            <div class="bg-white p-5 rounded-2xl border border-emerald-900/10 bong-xanh space-y-3">
                <span class="font-bold text-slate-700 uppercase text-xs block">
                    <i class="fa-solid fa-magnifying-glass text-emerald-700 mr-1"></i> Tra Cứu Báo Cáo Nghi Vấn
                </span>
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="flex-1 relative">
                        <input type="text" id="oTraCuuThuCong" placeholder="Nhập mã tra cứu (Ví dụ: BC-2026-XXXXXX)..." class="w-full pl-3 pr-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl font-mono uppercase font-semibold text-xs text-slate-900 focus:bg-white focus:border-emerald-700 outline-none">
                    </div>
                    <button type="button" id="nutTraCuuThuCong" class="px-5 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs rounded-xl shadow-md transition-all flex items-center justify-center gap-2 cursor-pointer">
                        <i class="fa-solid fa-search"></i> Tra Cứu
                    </button>
                    <label class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl border border-slate-300 transition-all flex items-center justify-center gap-2 cursor-pointer shrink-0">
                        <i class="fa-solid fa-file-arrow-up text-cyan-800"></i> Nhập Từ File .txt
                        <input type="file" id="inputUploadFileTxt" accept=".txt" class="hidden">
                    </label>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-emerald-900/10 bong-xanh overflow-hidden">
                <div class="p-4 bg-nenChinh border-b border-emerald-900/10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 text-xs">
                    <span class="font-bold text-slate-700 uppercase flex items-center gap-2">
                        <i class="fa-solid fa-list-check text-cyan-800"></i> Lịch Sử Báo Cáo Đã Gửi
                    </span>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-bold text-slate-500 font-mono mr-1">Tổng: <span id="soLuongBaoCaoBadge">0</span> báo cáo</span>

                        <label class="px-2.5 py-1 text-[11px] font-bold text-emerald-800 hover:text-emerald-900 hover:bg-emerald-50 rounded-lg border border-emerald-200 transition-all cursor-pointer flex items-center gap-1">
                            <i class="fa-solid fa-file-import"></i> Nhập Lịch Sử
                            <input type="file" id="inputNhapLichSuTxt" accept=".txt" class="hidden">
                        </label>

                        <button type="button" id="nutXuatLichSuPublic" class="px-2.5 py-1 text-[11px] font-bold text-cyan-800 hover:text-cyan-900 hover:bg-cyan-50 rounded-lg border border-cyan-200 transition-all cursor-pointer flex items-center gap-1">
                            <i class="fa-solid fa-file-arrow-down"></i> Xuất Lịch Sử
                        </button>

                        <button type="button" id="nutXoaLichSuPublic" class="px-2.5 py-1 text-[11px] font-bold text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg border border-red-200 transition-all cursor-pointer flex items-center gap-1">
                            <i class="fa-solid fa-trash-can"></i> Xóa Lịch Sử
                        </button>
                    </div>
                </div>

                <div id="khungBaoCaoRong" class="p-8 text-center text-slate-400 text-xs hidden space-y-2">
                    <i class="fa-solid fa-folder-open text-3xl text-slate-300 block"></i>
                    <div>Bạn chưa thực hiện gửi báo cáo nghi vấn nào.</div>
                </div>

                <div id="khungBangBaoCao" class="w-full overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 text-slate-600 uppercase border-b border-slate-100 text-[11px]">
                                <th class="py-3 px-4 font-bold">Mã Tra Cứu</th>
                                <th class="py-3 px-4 font-bold">Mã Lô Khai Báo</th>
                                <th class="py-3 px-4 font-bold">Trạng Thái</th>
                                <th class="py-3 px-4 font-bold">Thời Gian Gửi</th>
                                <th class="py-3 px-4 font-bold text-center">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody id="thanBangBaoCao" class="divide-y divide-slate-100 font-medium"></tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</main>

<!-- MODAL: GỬI THÀNH CÔNG + XUẤT PHIẾU .TXT -->
<div id="modalThanhCongBaoCao" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 text-center space-y-4 bong-xanh">
        <div class="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center text-emerald-600 text-2xl mx-auto">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <div>
            <h3 class="text-base font-bold text-slate-900">Gửi Báo Cáo Thành Công!</h3>
            <p class="text-xs text-slate-500 mt-1">Lưu lại Mã Báo Cáo bên dưới để phục vụ theo dõi tiến trình xử lý.</p>
        </div>
        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-1">
            <span class="text-[11px] text-slate-400 font-bold uppercase">Mã Tra Cứu Hồ Sơ</span>
            <div id="maBaoCaoSinhRa" class="font-mono font-extrabold text-cyan-900 text-lg"></div>
        </div>
        <div class="grid grid-cols-2 gap-2 text-xs">
            <button type="button" id="nutSaoChepMa" class="py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl border border-slate-300 cursor-pointer">
                <i class="fa-regular fa-copy"></i> Sao chép mã
            </button>
            <button type="button" id="nutTaiPhieuMa" class="py-2.5 bg-cyan-800 hover:bg-cyan-900 text-white font-bold rounded-xl cursor-pointer">
                <i class="fa-solid fa-file-arrow-down"></i> Tải file .txt
            </button>
        </div>
        <div class="pt-2 border-t border-slate-100 flex justify-between gap-2 text-xs">
            <button type="button" id="nutDongModalThanhCong" class="px-4 py-2 text-slate-500 font-bold hover:text-slate-800 cursor-pointer">
                Đóng
            </button>
            <button type="button" id="nutChuyenSangLichSu" class="px-4 py-2 text-emerald-700 font-bold hover:underline cursor-pointer">
                Xem lịch sử tra cứu &rarr;
            </button>
        </div>
    </div>
</div>

<!-- MODAL: CHI TIẾT CÔNG KHAI (PHƯƠNG ÁN A: BẢO MẬT TỆP BẰNG CHỨNG) -->
<div id="modalChiTietBaoCao" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 space-y-4 bong-xanh max-h-[90vh] overflow-y-auto">
        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-sm font-bold text-slate-900">Chi Tiết Hồ Sơ:</span>
                <span id="chiTietMaBaoCao" class="font-mono font-bold text-cyan-800 bg-cyan-50 px-2 py-0.5 rounded text-xs border border-cyan-200"></span>
                <span id="chiTietLoaiBaoCaoBadge"></span>
            </div>
            <button type="button" id="nutDongChiTiet" class="text-slate-400 hover:text-slate-700 text-lg cursor-pointer">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="space-y-3.5 text-xs">
            <div class="grid grid-cols-2 gap-2.5 p-3 bg-nenChinh rounded-xl border border-emerald-900/10">
                <div>
                    <span class="text-slate-500 block">Mã lô khai báo:</span>
                    <span id="chiTietMaLo" class="font-mono font-bold text-slate-900"></span>
                </div>
                <div>
                    <span class="text-slate-500 block">Thời gian gửi:</span>
                    <span id="chiTietNgayGui" class="font-semibold text-slate-700 font-mono"></span>
                </div>
                <div class="col-span-2">
                    <span class="text-slate-500 block">Thông tin dược phẩm:</span>
                    <span id="chiTietTenThuoc" class="font-bold text-emerald-800"></span>
                </div>
                <div>
                    <span class="text-slate-500 block">Trạng thái xử lý:</span>
                    <div id="chiTietTrangThaiBadge" class="pt-0.5"></div>
                </div>
            </div>

            <div>
                <span class="text-slate-600 font-bold block mb-1">Nhóm lý do nghi vấn:</span>
                <div id="chiTietLyDo" class="p-2.5 bg-slate-50 rounded-lg text-slate-800 font-medium border border-slate-200"></div>
            </div>

            <div>
                <span class="text-slate-600 font-bold block mb-1">Trạng thái bằng chứng đính kèm:</span>
                <div id="chiTietAnhMinhChung"></div>
            </div>

            <div class="border-t border-slate-100 pt-3 space-y-1">
                <span class="text-slate-700 font-bold block flex items-center gap-1.5">
                    <i class="fa-solid fa-building-columns text-indigo-700"></i> Kết Quả Thẩm Định Của Cơ Quan Quản Lý:
                </span>
                <div id="noiDungKetQuaXuLy"></div>
            </div>
        </div>

        <div class="pt-2 border-t border-slate-100 flex justify-end">
            <button type="button" id="nutDongChiTietBottom" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl cursor-pointer">
                Đóng
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="{{ asset('assets/js/nguoiTieuDung/baoCaoNghiVan.js') }}"></script>
@endpush
@extends('layoutDungChung.master')

@section('title', 'Quản lý báo cáo nghi vấn theo lô - PharmaChain')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/trangDungChung/quanLyBaoCaoNghiVan.css') }}">
@endpush

@section('content')
<div class="space-y-6">
    <!-- TIÊU ĐỀ & NÚT THAO TÁC -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
        <div>
            <h1 class="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-clipboard-list text-emerald-700"></i> Quản Lý Báo Cáo Nghi Vấn Theo Lô
            </h1>
            <p class="text-xs text-slate-500 mt-1">Theo dõi và lập báo cáo sự cố lô thuốc trong vòng đời hoặc cảnh báo lô giả mạo gửi Cơ quan quản lý.</p>
        </div>
        <button type="button" id="nutMoModalGuiBaoCao" class="px-4 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs rounded-xl shadow-md transition-all flex items-center gap-2 cursor-pointer self-start sm:self-auto shrink-0">
            <i class="fa-solid fa-plus-circle"></i> Gửi Báo Cáo Lô Nghi Vấn
        </button>
    </div>

    <!-- THANH LỌC & BẢNG DỮ LIỆU -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 space-y-3">
            <div class="relative w-full">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 pointer-events-none">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </span>
                <input type="text" id="oTimKiemBaoCao" placeholder="Tìm theo mã báo cáo, mã lô hoặc tên thuốc..." class="w-full pl-9 pr-3 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:bg-white focus:border-emerald-700 outline-none">
            </div>

            <div class="flex flex-wrap items-center justify-between gap-2 text-xs pt-1">
                <div class="flex flex-wrap items-center gap-2">
                    <select id="locLoaiBaoCao" class="px-3 py-1.5 bg-white border border-slate-200 rounded-xl font-semibold text-slate-700 focus:border-emerald-700 outline-none cursor-pointer">
                        <option value="TAT_CA">Tất cả phân loại</option>
                        <option value="TRONG_VONG_DOI">Lô trong vòng đời đơn vị</option>
                        <option value="NGOAI_HE_THONG">Lô ngoài hệ thống / Nghi giả</option>
                    </select>
                    <select id="locTrangThaiBaoCao" class="px-3 py-1.5 bg-white border border-slate-200 rounded-xl font-semibold text-slate-700 focus:border-emerald-700 outline-none cursor-pointer">
                        <option value="TAT_CA">Tất cả trạng thái</option>
                        <option value="DA_GUI">Đã gửi</option>
                        <option value="DANG_XU_LY">Đang xử lý</option>
                        <option value="DA_XU_LY">Đã xử lý</option>
                    </select>
                </div>
                <span id="soLuongBaoCaoBadge" class="font-bold text-slate-500 font-mono text-[11px]">Hiển thị: 0 báo cáo</span>
            </div>
        </div>

        <!-- BẢNG DANH SÁCH BÁO CÁO -->
        <div class="w-full overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/80 text-slate-600 uppercase border-b border-slate-100 text-[11px]">
                        <th class="py-3 px-4 font-bold">Mã Hồ Sơ</th>
                        <th class="py-3 px-4 font-bold">Phân Loại</th>
                        <th class="py-3 px-4 font-bold">Lô Thuốc Báo Cáo</th>
                        <th class="py-3 px-4 font-bold">Lý Do Nghi Vấn</th>
                        <th class="py-3 px-4 font-bold text-center">Thời Gian Gửi</th>
                        <th class="py-3 px-4 font-bold text-center">Trạng Thái</th>
                        <th class="py-3 px-4 font-bold text-center">Thao Tác</th>
                    </tr>
                </thead>
                <tbody id="thanBangBaoCao" class="divide-y divide-slate-100 font-medium"></tbody>
            </table>
        </div>

        <!-- PHÂN TRANG -->
        <div class="p-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs bg-slate-50/30">
            <span id="thongTinPhanTrang" class="text-slate-500 font-medium"></span>
            <div id="cumNutPhanTrang" class="flex items-center gap-1"></div>
        </div>
    </div>
</div>

<!-- MODAL: FORM GỬI BÁO CÁO MỚI THEO 2 TAB -->
<div id="modalGuiBaoCaoMoi" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-xl w-full p-6 space-y-4 shadow-xl max-h-[90vh] overflow-y-auto">
        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-file-circle-plus text-emerald-700"></i> Lập Báo Cáo Nghi Vấn Theo Lô
            </h3>
            <button type="button" class="dong-modal-gui text-slate-400 hover:text-slate-700 text-lg cursor-pointer">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- THANH CHUYỂN 2 TAB -->
        <div class="grid grid-cols-2 gap-1.5 p-1 bg-slate-100 rounded-xl text-xs font-bold">
            <button type="button" id="tabBtnTrongVongDoi" class="py-2 px-3 rounded-lg bg-white text-emerald-800 shadow-xs transition-all cursor-pointer flex items-center justify-center gap-1.5">
                <i class="fa-solid fa-boxes-stacked"></i>
                <span>Lô Trong Vòng Đời</span>
            </button>
            <button type="button" id="tabBtnNgoaiHeThong" class="py-2 px-3 rounded-lg text-slate-600 hover:text-slate-900 transition-all cursor-pointer flex items-center justify-center gap-1.5">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>Lô Ngoài Hệ Thống / Giả Mạo</span>
            </button>
        </div>

        <form id="formSubmitBaoCao" class="space-y-4 text-xs" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="loaiBaoCao" id="inputLoaiBaoCao" value="TRONG_VONG_DOI">

            <!-- NỘI DUNG TAB 1: LÔ TRONG VÒNG ĐỜI CỦA ĐƠN VỊ -->
            <div id="panelTrongVongDoi" class="space-y-2">
                <label for="selectLoTrongVongDoi" class="block font-bold text-slate-700 uppercase">
                    Chọn Lô Thuốc Thuộc Đơn Vị <span class="text-red-500">*</span>
                </label>
                <select id="selectLoTrongVongDoi" name="loThuocId" required class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:bg-white focus:border-emerald-700 outline-none cursor-pointer">
                    <option value="">-- Chọn lô thuốc đang sở hữu hoặc từng lưu thông --</option>
                    @foreach($loThuocs as $lo)
                    <option value="{{ $lo->id }}">
                        {{ $lo->maLoNghiepVu }} — {{ $lo->sanPham?->tenSanPham ?? 'Dược phẩm' }} ({{ number_format((int) $lo->soLuong, 0, ',', '.') }} {{ $lo->donViTinh ?? 'Hộp' }})
                    </option>
                    @endforeach
                </select>

                <!-- Thẻ đối soát thông tin lô Tab 1 -->
                <div id="khungThongTinLoTab1" class="hidden p-3 rounded-xl border border-emerald-200 bg-emerald-50/60 text-xs space-y-1.5"></div>
            </div>

            <!-- NỘI DUNG TAB 2: LÔ NGOÀI HỆ THỐNG / NGHI LÀM GIẢ -->
            <div id="panelNgoaiHeThong" class="space-y-3 hidden">
                <div class="space-y-1">
                    <label for="inputMaLoNgoaiHeThong" class="block font-bold text-slate-700 uppercase">
                        Mã Lô Nghi Giả Phát Hiện Trên Thị Trường <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="inputMaLoNgoaiHeThong" name="maLo" autocomplete="off" placeholder="Nhập mã lô in trên vỏ hộp/thùng thuốc phát hiện..." class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-mono font-semibold uppercase text-slate-900 focus:bg-white focus:border-amber-600 outline-none">
                </div>

                <div class="space-y-1.5">
                    <label for="selectLoBiLamGia" class="block font-bold text-slate-700 uppercase">
                        Lô Thật Của Đơn Vị Bị Nghi Làm Giả <span class="text-slate-400 font-normal normal-case">(Tùy chọn để đối chiếu)</span>
                    </label>
                    <select id="selectLoBiLamGia" name="loBiLamGiaId" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:border-amber-600 outline-none cursor-pointer">
                        <option value="">-- Không chọn / Không xác định được lô gốc bị sao chép --</option>
                        @foreach($loThuocs as $lo)
                        <option value="{{ $lo->id }}">
                            {{ $lo->maLoNghiepVu }} — {{ $lo->sanPham?->tenSanPham ?? 'Dược phẩm' }} (HSD: {{ $lo->hanSuDung ? date('d/m/Y', strtotime($lo->hanSuDung)) : 'N/A' }})
                        </option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-slate-500">Chỉ hiển thị danh mục lô thuộc đơn vị của bạn. Chọn lô gốc giúp Cơ quan quản lý đối chiếu bao bì, không làm thu hồi nhầm lô thật.</p>
                    <div id="khungThongTinLoTab2" class="hidden p-3 rounded-xl border border-amber-200 bg-amber-50/60 text-xs space-y-1.5"></div>
                </div>
            </div>

            <!-- Nhóm lý do nghi vấn (tự động đổi danh sách theo Tab) -->
            <div class="space-y-1">
                <label for="nhapLyDo" class="block font-bold text-slate-700 uppercase">
                    Nhóm Lý Do Nghi Vấn <span class="text-red-500">*</span>
                </label>
                <select id="nhapLyDo" name="lyDo" required class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:border-emerald-700 outline-none cursor-pointer"></select>
            </div>

            <!-- Mô tả -->
            <div class="space-y-1">
                <label for="nhapMoTa" class="block font-bold text-slate-700 uppercase">
                    Mô Tả Chi Tiết Hiện Trường <span class="text-red-500">*</span>
                </label>
                <textarea id="nhapMoTa" name="moTa" rows="3" required placeholder="Mô tả cụ thể tình trạng lô thuốc, số lượng ảnh hưởng, thời điểm và địa điểm phát hiện..." class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:bg-white focus:border-emerald-700 outline-none resize-y"></textarea>
            </div>

            <!-- Đính kèm từ 1 đến 5 tệp -->
            <div class="space-y-1">
                <div class="flex justify-between items-center font-bold text-slate-700 uppercase">
                    <span>Đính Kèm Bằng Chứng (1–5 tệp &lt; 10MB) <span class="text-red-500">*</span></span>
                    <span id="soLuongTepDaChon" class="text-[11px] text-slate-400 font-normal">0/5 tệp</span>
                </div>
                <div id="khungChonTepMinhChung" class="border-2 border-dashed border-slate-300 hover:border-emerald-700 rounded-xl p-3 text-center bg-slate-50 cursor-pointer transition-all">
                    <input type="file" id="tepMinhChungInput" accept="image/jpeg,image/png,application/pdf" multiple class="hidden">
                    <div class="text-xs text-slate-600 font-semibold flex items-center justify-center gap-2">
                        <i class="fa-solid fa-cloud-arrow-up text-base text-emerald-700"></i>
                        <span>Nhấp để tải ảnh chụp thùng/nhãn lô hoặc biên bản (JPG, PNG, PDF)</span>
                    </div>
                </div>
                <div id="danhSachTepDaChon" class="space-y-1 pt-1"></div>
            </div>

            <div class="pt-2 border-t border-slate-100 flex justify-end gap-2">
                <button type="button" class="dong-modal-gui px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl cursor-pointer">
                    Hủy bỏ
                </button>
                <button type="submit" id="btnSubmitFormAuth" class="px-5 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white font-bold rounded-xl shadow-md transition-all cursor-pointer">
                    <i class="fa-solid fa-paper-plane mr-1"></i> Gửi Báo Cáo
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: XEM CHI TIẾT BÁO CÁO -->
<div id="modalChiTietBaoCao" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 space-y-4 shadow-xl max-h-[90vh] overflow-y-auto">
        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-sm font-bold text-slate-900">Chi Tiết Báo Cáo:</span>
                <span id="chiTietMaBaoCao" class="font-mono font-bold text-cyan-800 bg-cyan-50 px-2 py-0.5 rounded text-xs border border-cyan-200"></span>
            </div>
            <button type="button" id="nutDongChiTiet" class="text-slate-400 hover:text-slate-700 text-lg cursor-pointer">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="space-y-3.5 text-xs">
            <div class="grid grid-cols-2 gap-2.5 p-3 bg-slate-50 rounded-xl border border-slate-200">
                <div>
                    <span class="text-slate-500 block">Mã lô khai báo:</span>
                    <span id="chiTietMaLo" class="font-mono font-bold text-slate-900"></span>
                </div>
                <div>
                    <span class="text-slate-500 block">Thời gian gửi:</span>
                    <span id="chiTietNgayGui" class="font-semibold text-slate-700 font-mono"></span>
                </div>
                <div class="col-span-2">
                    <span class="text-slate-500 block">Thông tin lô / Đối chiếu:</span>
                    <span id="chiTietSanPhamLo" class="font-bold text-slate-800"></span>
                </div>
                <div class="col-span-2 flex items-center justify-between gap-2 pt-1 border-t border-slate-200/70">
                    <div id="chiTietLoaiBadge"></div>
                    <div id="chiTietTrangThaiBadge"></div>
                </div>
            </div>

            <div>
                <span class="text-slate-600 font-bold block mb-1">Lý do báo cáo:</span>
                <div id="chiTietLyDo" class="p-2.5 bg-slate-50 rounded-lg text-slate-800 font-semibold border border-slate-200"></div>
            </div>

            <div>
                <span class="text-slate-600 font-bold block mb-1">Mô tả hiện trường:</span>
                <div id="chiTietMoTa" class="p-2.5 bg-slate-50 rounded-lg text-slate-700 border border-slate-200 whitespace-pre-wrap leading-relaxed"></div>
            </div>

            <div>
                <span class="text-slate-600 font-bold block mb-1">Tệp bằng chứng đính kèm:</span>
                <div id="chiTietDanhSachMinhChung" class="grid grid-cols-2 gap-2"></div>
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
<script>
    window.danhSachBaoCaoGoc = @json($baoCaosFormatted ?? []);
    window.danhSachLoThuocGoc = @json($loThuocsFormatted ?? []);
</script>
<script src="{{ asset('assets/js/trangDungChung/quanLyBaoCaoNghiVan.js') }}"></script>
@endpush
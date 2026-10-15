@extends('layoutNguoiTieuDung.main')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/nguoiTieuDung/dangKyToChuc.css') }}">
@endpush

@section('content')

<main class="flex-1 py-12 px-4 sm:px-6 lg:px-8 max-w-4xl mx-auto w-full space-y-8">
    <div class="text-center space-y-2">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-emerald-700 tracking-tight">Đăng Ký Tài Khoản Tổ Chức Doanh Nghiệp</h1>
        <p class="text-xs sm:text-sm text-slate-600 max-w-2xl mx-auto">
            Dành cho Nhà sản xuất, Nhà phân phối và Nhà thuốc tham gia mạng lưới dược phẩm trên Sepolia Testnet.
        </p>
    </div>

    <!-- Hiển thị lỗi Validation nếu có -->
    @if ($errors->any())
    <div class="p-4 bg-red-50 border border-red-200 rounded-2xl text-xs text-red-800 font-medium space-y-1">
        <div class="font-bold text-red-900 flex items-center gap-2">
            <i class="fa-solid fa-triangle-exclamation text-red-600"></i>
            <span>Thông tin chưa hợp lệ:</span>
        </div>
        <ul class="list-disc list-inside space-y-1 pl-2">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form id="formDangKyToChuc" action="{{ route('dangKyToChuc.store') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-2xl border border-emerald-900/10 p-6 sm:p-10 bong-xanh space-y-10">
        @csrf

        <!-- NHÓM 1: CHỌN LOẠI TỔ CHỨC -->
        <section class="space-y-4">
            <div class="border-b border-slate-100 pb-3 flex items-center gap-2.5">
                <span class="w-6 h-6 rounded-full bg-emerald-50 text-emerald-700 text-xs font-extrabold flex items-center justify-center">1</span>
                <h2 class="text-base font-bold text-slate-900">Chọn Loại Tổ Chức Tham Gia</h2>
                <span class="text-red-500 text-xs">*</span>
            </div>

            <input type="hidden" name="loaiToChuc" id="giaTriLoaiToChuc" value="{{ old('loaiToChuc', 'NHA_SAN_XUAT') }}">

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4" id="danhSachLoaiToChuc">
                <div class="the-loai-to-chuc cursor-pointer p-5 rounded-2xl border-2 {{ old('loaiToChuc', 'NHA_SAN_XUAT') === 'NHA_SAN_XUAT' ? 'border-emerald-700 bg-emerald-50/50' : 'border-slate-200 bg-white' }} flex flex-col items-center text-center gap-3 transition-all" data-loai="NHA_SAN_XUAT">
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-2xl font-bold">
                        <i class="fa-solid fa-industry"></i>
                    </div>
                    <div>
                        <div class="font-bold text-sm text-slate-900">Nhà Sản Xuất</div>
                        <div class="text-[11px] text-slate-500 mt-1">Đăng ký sản phẩm & khởi tạo lô thuốc</div>
                    </div>
                    <i class="fa-solid {{ old('loaiToChuc', 'NHA_SAN_XUAT') === 'NHA_SAN_XUAT' ? 'fa-circle-check text-emerald-700' : 'fa-regular fa-circle text-slate-300' }} text-lg mt-auto icon-check"></i>
                </div>

                <div class="the-loai-to-chuc cursor-pointer p-5 rounded-2xl border-2 {{ old('loaiToChuc') === 'NHA_PHAN_PHOI' ? 'border-emerald-700 bg-emerald-50/50' : 'border-slate-200 bg-white' }} flex flex-col items-center text-center gap-3 transition-all" data-loai="NHA_PHAN_PHOI">
                    <div class="w-12 h-12 rounded-xl bg-cyan-50 text-cyan-800 flex items-center justify-center text-2xl font-bold">
                        <i class="fa-solid fa-truck-moving"></i>
                    </div>
                    <div>
                        <div class="font-bold text-sm text-slate-900">Nhà Phân Phối</div>
                        <div class="text-[11px] text-slate-500 mt-1">Giao nhận kho tổng & chuyển tiếp lô hàng</div>
                    </div>
                    <i class="fa-solid {{ old('loaiToChuc') === 'NHA_PHAN_PHOI' ? 'fa-circle-check text-emerald-700' : 'fa-regular fa-circle text-slate-300' }} text-lg mt-auto icon-check"></i>
                </div>

                <div class="the-loai-to-chuc cursor-pointer p-5 rounded-2xl border-2 {{ old('loaiToChuc') === 'NHA_THUOC' ? 'border-emerald-700 bg-emerald-50/50' : 'border-slate-200 bg-white' }} flex flex-col items-center text-center gap-3 transition-all" data-loai="NHA_THUOC">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-2xl font-bold">
                        <i class="fa-solid fa-prescription-bottle-medical"></i>
                    </div>
                    <div>
                        <div class="font-bold text-sm text-slate-900">Nhà Thuốc</div>
                        <div class="text-[11px] text-slate-500 mt-1">Tiếp nhận lô hàng lẻ & xác nhận bán ra</div>
                    </div>
                    <i class="fa-solid {{ old('loaiToChuc') === 'NHA_THUOC' ? 'fa-circle-check text-emerald-700' : 'fa-regular fa-circle text-slate-300' }} text-lg mt-auto icon-check"></i>
                </div>
            </div>
        </section>

        <!-- NHÓM 2: THÔNG TIN PHÁP LÝ TỔ CHỨC -->
        <section class="space-y-4">
            <div class="border-b border-slate-100 pb-3 flex items-center gap-2.5">
                <span class="w-6 h-6 rounded-full bg-emerald-50 text-emerald-700 text-xs font-extrabold flex items-center justify-center">2</span>
                <h2 class="text-base font-bold text-slate-900">Thông Tin Pháp Lý Doanh Nghiệp</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div class="sm:col-span-2 space-y-1">
                    <label for="tenToChuc" class="font-bold text-slate-700">Tên tổ chức / doanh nghiệp <span class="text-red-500">*</span></label>
                    <input type="text" id="tenToChuc" name="tenToChuc" value="{{ old('tenToChuc') }}" required placeholder="VD: Công ty Cổ phần Dược phẩm Medipharco" class="w-full px-4 py-2.5 bg-[#f2faf5] border border-emerald-900/15 rounded-xl text-sm font-medium text-slate-900 focus:bg-white focus:border-emerald-700 outline-none transition-all">
                </div>

                <div class="space-y-1">
                    <label for="maSoThue" class="font-bold text-slate-700">Mã số thuế doanh nghiệp <span class="text-red-500">*</span></label>
                    <input type="text" id="maSoThue" name="maSoThue" value="{{ old('maSoThue') }}" required placeholder="VD: 0102345678" class="w-full px-4 py-2.5 bg-[#f2faf5] border border-emerald-900/15 rounded-xl text-sm font-medium text-slate-900 focus:bg-white focus:border-emerald-700 outline-none transition-all">
                </div>

                <div class="space-y-1">
                    <label for="soDienThoai" class="font-bold text-slate-700">Số điện thoại liên hệ <span class="text-red-500">*</span></label>
                    <input type="tel" id="soDienThoai" name="soDienThoai" value="{{ old('soDienThoai') }}" required placeholder="VD: 02923888999" class="w-full px-4 py-2.5 bg-[#f2faf5] border border-emerald-900/15 rounded-xl text-sm font-medium text-slate-900 focus:bg-white focus:border-emerald-700 outline-none transition-all">
                </div>

                <div class="sm:col-span-2 space-y-1">
                    <label for="diaChi" class="font-bold text-slate-700">Địa chỉ trụ sở pháp lý <span class="text-red-500">*</span></label>
                    <input type="text" id="diaChi" name="diaChi" value="{{ old('diaChi') }}" required placeholder="Số nhà, đường, phường/xã, tỉnh/thành..." class="w-full px-4 py-2.5 bg-[#f2faf5] border border-emerald-900/15 rounded-xl text-sm font-medium text-slate-900 focus:bg-white focus:border-emerald-700 outline-none transition-all">
                </div>

                <!-- TÊN INPUT CHUẨN SO VỚI CONTROLLER -->
                <div class="space-y-1">
                    <label for="soGiayPhepDuoc" class="font-bold text-slate-700">Số giấy phép kinh doanh dược <span class="text-red-500">*</span></label>
                    <input type="text" id="soGiayPhepDuoc" name="soGiayPhepDuoc" value="{{ old('soGiayPhepDuoc') }}" required placeholder="VD: 123/ĐKKDD-BYT" class="w-full px-4 py-2.5 bg-[#f2faf5] border border-emerald-900/15 rounded-xl text-sm font-medium text-slate-900 focus:bg-white focus:border-emerald-700 outline-none transition-all">
                </div>

                <div class="space-y-1">
                    <label for="coQuanCap" class="font-bold text-slate-700">Cơ quan cấp phép <span class="text-red-500">*</span></label>
                    <input type="text" id="coQuanCap" name="coQuanCap" value="{{ old('coQuanCap') }}" required placeholder="VD: Cục Quản lý Dược - Bộ Y Tế" class="w-full px-4 py-2.5 bg-[#f2faf5] border border-emerald-900/15 rounded-xl text-sm font-medium text-slate-900 focus:bg-white focus:border-emerald-700 outline-none transition-all">
                </div>

                <div class="space-y-1">
                    <label for="ngayCapGiayPhep" class="font-bold text-slate-700">Ngày cấp phép <span class="text-red-500">*</span></label>
                    <input type="date" id="ngayCapGiayPhep" name="ngayCapGiayPhep" value="{{ old('ngayCapGiayPhep') }}" required class="w-full px-4 py-2.5 bg-[#f2faf5] border border-emerald-900/15 rounded-xl text-sm font-medium text-slate-900 focus:bg-white focus:border-emerald-700 outline-none transition-all">
                </div>

                <div class="space-y-1">
                    <label for="ngayHetHanGiayPhep" class="font-bold text-slate-700">Ngày hết hạn phép (nếu có)</label>
                    <input type="date" id="ngayHetHanGiayPhep" name="ngayHetHanGiayPhep" value="{{ old('ngayHetHanGiayPhep') }}" class="w-full px-4 py-2.5 bg-[#f2faf5] border border-emerald-900/15 rounded-xl text-sm font-medium text-slate-900 focus:bg-white focus:border-emerald-700 outline-none transition-all">
                </div>
            </div>
        </section>

        <!-- NHÓM 3: HỒ SƠ ĐÍNH KÈM -->
        <section class="space-y-4">
            <div class="border-b border-slate-100 pb-3 flex items-center gap-2.5">
                <span class="w-6 h-6 rounded-full bg-emerald-50 text-emerald-700 text-xs font-extrabold flex items-center justify-center">3</span>
                <h2 class="text-base font-bold text-slate-900">Hồ Sơ Minh Chứng Đính Kèm</h2>
                <span class="text-red-500 text-xs">*</span>
            </div>

            <div class="space-y-2">
                <label class="block text-xs font-bold text-slate-700">Bản scan giấy phép kinh doanh dược (PDF, JPG, PNG)</label>
                <div id="khuVucKeoThaFile" class="border-2 border-dashed border-emerald-900/20 hover:border-emerald-700 rounded-2xl p-6 text-center bg-[#f2faf5] cursor-pointer transition-all">
                    <!-- TÊN INPUT CHUẨN taiLieu[] -->
                    <input type="file" id="tepDinhKem" name="taiLieu[]" multiple required accept=".pdf,image/png,image/jpeg" class="hidden">
                    <div class="space-y-2">
                        <i class="fa-solid fa-cloud-arrow-up text-3xl text-emerald-700"></i>
                        <div class="text-xs text-slate-700 font-semibold" id="tenTepHienThi">
                            Kéo thả tập tin vào đây hoặc <span class="text-emerald-700 underline font-bold">Duyệt file</span>
                        </div>
                        <p class="text-[11px] text-slate-500">Dung lượng tối đa: 10MB/tệp</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- NHÓM 4: THÔNG TIN TÀI KHOẢN -->
        <section class="space-y-4">
            <div class="border-b border-slate-100 pb-3 flex items-center gap-2.5">
                <span class="w-6 h-6 rounded-full bg-emerald-50 text-emerald-700 text-xs font-extrabold flex items-center justify-center">4</span>
                <h2 class="text-base font-bold text-slate-900">Tài Khoản Đăng Nhập Quản Trị Viên</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div class="space-y-1">
                    <label for="tenDangNhapToChuc" class="font-bold text-slate-700">Tên đăng nhập <span class="text-red-500">*</span></label>
                    <input type="text" id="tenDangNhapToChuc" name="tenDangNhap" value="{{ old('tenDangNhap') }}" required placeholder="VD: admin_medipharco" class="w-full px-4 py-2.5 bg-[#f2faf5] border border-emerald-900/15 rounded-xl text-sm font-medium text-slate-900 focus:bg-white focus:border-emerald-700 outline-none transition-all">
                </div>

                <div class="space-y-1">
                    <label for="emailToChuc" class="font-bold text-slate-700">Email hệ thống <span class="text-red-500">*</span></label>
                    <input type="email" id="emailToChuc" name="email" value="{{ old('email') }}" required placeholder="VD: contact@medipharco.vn" class="w-full px-4 py-2.5 bg-[#f2faf5] border border-emerald-900/15 rounded-xl text-sm font-medium text-slate-900 focus:bg-white focus:border-emerald-700 outline-none transition-all">
                </div>

                <div class="space-y-1">
                    <label for="matKhauToChuc" class="font-bold text-slate-700">Mật khẩu <span class="text-red-500">*</span></label>
                    <input type="password" id="matKhauToChuc" name="matKhau" required placeholder="Tối thiểu 8 ký tự..." class="w-full px-4 py-2.5 bg-[#f2faf5] border border-emerald-900/15 rounded-xl text-sm font-medium text-slate-900 focus:bg-white focus:border-emerald-700 outline-none transition-all">
                </div>

                <!-- TÊN INPUT CHUẨN matKhau_confirmation -->
                <div class="space-y-1">
                    <label for="xacNhanMatKhau" class="font-bold text-slate-700">Xác nhận mật khẩu <span class="text-red-500">*</span></label>
                    <input type="password" id="xacNhanMatKhau" name="matKhau_confirmation" required placeholder="Nhập lại mật khẩu..." class="w-full px-4 py-2.5 bg-[#f2faf5] border border-emerald-900/15 rounded-xl text-sm font-medium text-slate-900 focus:bg-white focus:border-emerald-700 outline-none transition-all">
                </div>
            </div>
        </section>

        <div id="loiFormDangKy" class="hidden p-3.5 bg-red-50 border border-red-200 rounded-xl text-xs text-red-700 font-medium flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation shrink-0 text-red-500"></i>
            <span id="loiFormChiTiet">Vui lòng điền đầy đủ thông tin.</span>
        </div>

        <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
            <a href="{{ route('login') }}" class="text-xs font-semibold text-slate-500 hover:text-emerald-700 transition-colors">
                <i class="fa-solid fa-arrow-left mr-1"></i> Đã có tài khoản? Đăng nhập ngay
            </a>
            <button type="submit" class="w-full sm:w-auto px-8 py-3.5 bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-sm rounded-xl shadow-md transition-all cursor-pointer">
                <i class="fa-solid fa-paper-plane mr-1.5"></i> Gửi Yêu Cầu Đăng Ký
            </button>
        </div>
    </form>
</main>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/nguoiTieuDung/dangKyToChuc.js') }}"></script>
@endpush
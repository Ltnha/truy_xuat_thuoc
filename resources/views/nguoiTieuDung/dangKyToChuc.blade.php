@extends('layoutNguoiTieuDung.main')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/nguoiTieuDung/dangKyToChuc.css') }}">
@endpush

@section('content')

<main class="flex-1 py-12 px-4 sm:px-6 lg:px-8 max-w-4xl mx-auto w-full space-y-8">

    <!-- Tiêu đề trang -->
    <div class="text-center space-y-2">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-emerald-700 tracking-tight">Đăng Ký Tài Khoản Tổ Chức Doanh Nghiệp</h1>
        <p class="text-xs sm:text-sm text-slate-600 max-w-2xl mx-auto">
            Dành cho Nhà sản xuất, Nhà phân phối và Nhà thuốc đăng ký tham gia mạng lưới chuỗi cung ứng dược phẩm trên Blockchain.
        </p>
    </div>

    <!-- KHUNG FORM ĐĂNG KÝ (4 NHÓM RÕ RÀNG) -->
    <form id="formDangKyToChuc" action="{{ url('/dang-ky-to-chuc') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-2xl border border-emerald-900/10 p-6 sm:p-10 bong-xanh space-y-10">
        @csrf

        <!-- NHÓM 1: CHỌN LOẠI TỔ CHỨC (loaiToChuc) -->
        <section class="space-y-4">
            <div class="border-b border-slate-100 pb-3 flex items-center gap-2.5">
                <span class="w-6 h-6 rounded-full bg-emerald-50 text-emerald-700 text-xs font-extrabold flex items-center justify-center">1</span>
                <h2 class="text-base font-bold text-slate-900">Chọn Loại Tổ Chức Tham Gia</h2>
                <span class="text-red-500 text-xs">*</span>
            </div>

            <!-- Input ẩn chứa giá trị enum gửi lên server -->
            <input type="hidden" name="loaiToChuc" id="giaTriLoaiToChuc" value="NHA_SAN_XUAT">

            <!-- 3 nút lớn dạng thẻ -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4" id="danhSachLoaiToChuc">

                <!-- Thẻ 1: Nhà sản xuất -->
                <div class="the-loai-to-chuc cursor-pointer p-5 rounded-2xl border-2 border-emerald-700 bg-emerald-50/50 flex flex-col items-center text-center gap-3 transition-all" data-loai="NHA_SAN_XUAT">
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-2xl font-bold">
                        <i class="fa-solid fa-industry"></i>
                    </div>
                    <div>
                        <div class="font-bold text-sm text-slate-900">Nhà Sản Xuất</div>
                        <div class="text-[11px] text-slate-500 mt-1">Đăng ký sản phẩm, khởi tạo lô thuốc & sinh mã QR</div>
                    </div>
                    <i class="fa-solid fa-circle-check text-emerald-700 text-lg mt-auto icon-check"></i>
                </div>

                <!-- Thẻ 2: Nhà phân phối -->
                <div class="the-loai-to-chuc cursor-pointer p-5 rounded-2xl border-2 border-slate-200 hover:border-emerald-700/60 bg-white flex flex-col items-center text-center gap-3 transition-all" data-loai="NHA_PHAN_PHOI">
                    <div class="w-12 h-12 rounded-xl bg-cyan-50 text-cyan-800 flex items-center justify-center text-2xl font-bold">
                        <i class="fa-solid fa-truck-moving"></i>
                    </div>
                    <div>
                        <div class="font-bold text-sm text-slate-900">Nhà Phân Phối</div>
                        <div class="text-[11px] text-slate-500 mt-1">Giao nhận kho tổng, phân bổ & chuyển tiếp lô hàng</div>
                    </div>
                    <i class="fa-regular fa-circle text-slate-300 text-lg mt-auto icon-check"></i>
                </div>

                <!-- Thẻ 3: Nhà thuốc -->
                <div class="the-loai-to-chuc cursor-pointer p-5 rounded-2xl border-2 border-slate-200 hover:border-emerald-700/60 bg-white flex flex-col items-center text-center gap-3 transition-all" data-loai="NHA_THUOC">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-2xl font-bold">
                        <i class="fa-solid fa-prescription-bottle-medical"></i>
                    </div>
                    <div>
                        <div class="font-bold text-sm text-slate-900">Nhà Thuốc / Quầy Thuốc</div>
                        <div class="text-[11px] text-slate-500 mt-1">Tiếp nhận lô hàng lẻ & xác nhận bán ra cho người dân</div>
                    </div>
                    <i class="fa-regular fa-circle text-slate-300 text-lg mt-auto icon-check"></i>
                </div>
            </div>
        </section>

        <!-- NHÓM 2: THÔNG TIN PHÁP LÝ TỔ CHỨC (Bảng toChuc) -->
        <section class="space-y-4">
            <div class="border-b border-slate-100 pb-3 flex items-center gap-2.5">
                <span class="w-6 h-6 rounded-full bg-emerald-50 text-emerald-700 text-xs font-extrabold flex items-center justify-center">2</span>
                <h2 class="text-base font-bold text-slate-900">Thông Tin Pháp Lý Doanh Nghiệp</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">

                <!-- tenToChuc -->
                <div class="sm:col-span-2 space-y-1">
                    <label for="tenToChuc" class="font-bold text-slate-700">Tên tổ chức / doanh nghiệp <span class="text-red-500">*</span></label>
                    <input type="text" id="tenToChuc" name="tenToChuc" required placeholder="VD: Công ty Cổ phần Dược phẩm Medipharco" class="w-full px-4 py-2.5 bg-[#f2faf5] border border-emerald-900/15 rounded-xl text-sm font-medium text-slate-900 focus:bg-white focus:border-emerald-700 outline-none transition-all">
                </div>

                <!-- maSoThue -->
                <div class="space-y-1">
                    <label for="maSoThue" class="font-bold text-slate-700">Mã số thuế doanh nghiệp <span class="text-red-500">*</span></label>
                    <input type="text" id="maSoThue" name="maSoThue" required placeholder="VD: 0102345678" class="w-full px-4 py-2.5 bg-[#f2faf5] border border-emerald-900/15 rounded-xl text-sm font-medium text-slate-900 focus:bg-white focus:border-emerald-700 outline-none transition-all">
                </div>

                <!-- soDienThoai -->
                <div class="space-y-1">
                    <label for="soDienThoai" class="font-bold text-slate-700">Số điện thoại liên hệ <span class="text-red-500">*</span></label>
                    <input type="tel" id="soDienThoai" name="soDienThoai" required placeholder="VD: 0292 3888 999" class="w-full px-4 py-2.5 bg-[#f2faf5] border border-emerald-900/15 rounded-xl text-sm font-medium text-slate-900 focus:bg-white focus:border-emerald-700 outline-none transition-all">
                </div>

                <!-- diaChi -->
                <div class="sm:col-span-2 space-y-1">
                    <label for="diaChi" class="font-bold text-slate-700">Địa chỉ trụ sở pháp lý <span class="text-red-500">*</span></label>
                    <input type="text" id="diaChi" name="diaChi" required placeholder="Số nhà, tên đường, phường/xã, quận/huyện, tỉnh/thành phố..." class="w-full px-4 py-2.5 bg-[#f2faf5] border border-emerald-900/15 rounded-xl text-sm font-medium text-slate-900 focus:bg-white focus:border-emerald-700 outline-none transition-all">
                </div>

                <!-- soGiayPhepKinhDoanhDuoc -->
                <div class="space-y-1">
                    <label for="soGiayPhepKinhDoanhDuoc" class="font-bold text-slate-700">Số giấy phép kinh doanh dược <span class="text-red-500">*</span></label>
                    <input type="text" id="soGiayPhepKinhDoanhDuoc" name="soGiayPhepKinhDoanhDuoc" required placeholder="VD: 123/ĐKKDD-BYT" class="w-full px-4 py-2.5 bg-[#f2faf5] border border-emerald-900/15 rounded-xl text-sm font-medium text-slate-900 focus:bg-white focus:border-emerald-700 outline-none transition-all">
                </div>

                <!-- coQuanCapPhep -->
                <div class="space-y-1">
                    <label for="coQuanCapPhep" class="font-bold text-slate-700">Cơ quan cấp phép <span class="text-red-500">*</span></label>
                    <input type="text" id="coQuanCapPhep" name="coQuanCapPhep" required placeholder="VD: Cục Quản lý Dược - Bộ Y Tế" class="w-full px-4 py-2.5 bg-[#f2faf5] border border-emerald-900/15 rounded-xl text-sm font-medium text-slate-900 focus:bg-white focus:border-emerald-700 outline-none transition-all">
                </div>

                <!-- ngayCapPhep -->
                <div class="space-y-1">
                    <label for="ngayCapPhep" class="font-bold text-slate-700">Ngày cấp phép <span class="text-red-500">*</span></label>
                    <input type="date" id="ngayCapPhep" name="ngayCapPhep" required class="w-full px-4 py-2.5 bg-[#f2faf5] border border-emerald-900/15 rounded-xl text-sm font-medium text-slate-900 focus:bg-white focus:border-emerald-700 outline-none transition-all">
                </div>

                <!-- ngayHetHanPhep -->
                <div class="space-y-1">
                    <label for="ngayHetHanPhep" class="font-bold text-slate-700">Ngày hết hạn phép (nếu có)</label>
                    <input type="date" id="ngayHetHanPhep" name="ngayHetHanPhep" class="w-full px-4 py-2.5 bg-[#f2faf5] border border-emerald-900/15 rounded-xl text-sm font-medium text-slate-900 focus:bg-white focus:border-emerald-700 outline-none transition-all">
                </div>
            </div>
        </section>

        <!-- NHÓM 3: HỒ SƠ ĐÍNH KÈM (Bảng hoSoDinhKemDangKy) -->
        <section class="space-y-4">
            <div class="border-b border-slate-100 pb-3 flex items-center gap-2.5">
                <span class="w-6 h-6 rounded-full bg-emerald-50 text-emerald-700 text-xs font-extrabold flex items-center justify-center">3</span>
                <h2 class="text-base font-bold text-slate-900">Hồ Sơ Minh Chứng Đính Kèm</h2>
                <span class="text-red-500 text-xs">*</span>
            </div>

            <div class="space-y-2">
                <label class="block text-xs font-bold text-slate-700">Bản scan giấy phép kinh doanh dược (PDF, JPG, PNG)</label>
                <div id="khuVucKeoThaFile" class="border-2 border-dashed border-emerald-900/20 hover:border-emerald-700 rounded-2xl p-6 text-center bg-[#f2faf5] cursor-pointer transition-all">
                    <input type="file" id="tepDinhKem" name="tepDinhKem" required accept=".pdf,image/png,image/jpeg" class="hidden">
                    <div class="space-y-2">
                        <i class="fa-solid fa-cloud-arrow-up text-3xl text-emerald-700"></i>
                        <div class="text-xs text-slate-700 font-semibold" id="tenTepHienThi">
                            Kéo thả tập tin vào đây hoặc <span class="text-emerald-700 underline font-bold">Duyệt file</span>
                        </div>
                        <p class="text-[11px] text-slate-500">Dung lượng tối đa: 10MB (bắt buộc để đối chiếu giấy phép thực tế)</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- NHÓM 4: THÔNG TIN TÀI KHOẢN ĐĂNG NHẬP (Bảng taiKhoan) -->
        <section class="space-y-4">
            <div class="border-b border-slate-100 pb-3 flex items-center gap-2.5">
                <span class="w-6 h-6 rounded-full bg-emerald-50 text-emerald-700 text-xs font-extrabold flex items-center justify-center">4</span>
                <h2 class="text-base font-bold text-slate-900">Tài Khoản Đăng Nhập Quản Trị Viên Tổ Chức</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">

                <!-- tenDangNhap -->
                <div class="space-y-1">
                    <label for="tenDangNhapToChuc" class="font-bold text-slate-700">Tên đăng nhập <span class="text-red-500">*</span></label>
                    <input type="text" id="tenDangNhapToChuc" name="tenDangNhap" required autocomplete="username" placeholder="VD: admin_medipharco" class="w-full px-4 py-2.5 bg-[#f2faf5] border border-emerald-900/15 rounded-xl text-sm font-medium text-slate-900 focus:bg-white focus:border-emerald-700 outline-none transition-all">
                </div>

                <!-- email -->
                <div class="space-y-1">
                    <label for="emailToChuc" class="font-bold text-slate-700">Email nhận kết quả xác minh <span class="text-red-500">*</span></label>
                    <input type="email" id="emailToChuc" name="email" required autocomplete="email" placeholder="VD: contact@medipharco.vn" class="w-full px-4 py-2.5 bg-[#f2faf5] border border-emerald-900/15 rounded-xl text-sm font-medium text-slate-900 focus:bg-white focus:border-emerald-700 outline-none transition-all">
                </div>

                <!-- matKhau -->
                <div class="space-y-1">
                    <label for="matKhauToChuc" class="font-bold text-slate-700">Mật khẩu <span class="text-red-500">*</span></label>
                    <input type="password" id="matKhauToChuc" name="matKhau" required autocomplete="new-password" placeholder="Tối thiểu 8 ký tự..." class="w-full px-4 py-2.5 bg-[#f2faf5] border border-emerald-900/15 rounded-xl text-sm font-medium text-slate-900 focus:bg-white focus:border-emerald-700 outline-none transition-all">
                </div>

                <!-- xacNhanMatKhau -->
                <div class="space-y-1">
                    <label for="xacNhanMatKhau" class="font-bold text-slate-700">Xác nhận mật khẩu <span class="text-red-500">*</span></label>
                    <input type="password" id="xacNhanMatKhau" name="xacNhanMatKhau" required autocomplete="new-password" placeholder="Nhập lại mật khẩu..." class="w-full px-4 py-2.5 bg-[#f2faf5] border border-emerald-900/15 rounded-xl text-sm font-medium text-slate-900 focus:bg-white focus:border-emerald-700 outline-none transition-all">
                </div>
            </div>
        </section>

        <!-- Thông báo lỗi kiểm tra client -->
        <div id="loiFormDangKy" class="hidden p-3 bg-red-50 border border-red-200 rounded-xl text-xs text-red-600 font-medium flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation shrink-0"></i>
            <span id="loiFormChiTiet">Vui lòng điền đầy đủ và chính xác các thông tin.</span>
        </div>

        <!-- Nút Gửi yêu cầu đăng ký -->
        <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
            <a href="dangNhap.php" class="text-xs font-semibold text-slate-500 hover:text-emerald-700 transition-colors">
                <i class="fa-solid fa-arrow-left mr-1"></i> Đã có tài khoản? Đăng nhập ngay
            </a>
            <button type="submit" id="nutGuiYeuCau" class="w-full sm:w-auto px-8 py-3.5 bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-sm rounded-xl shadow-md transition-all flex items-center justify-center gap-2">
                <i class="fa-solid fa-paper-plane"></i> Gửi Yêu Cầu Đăng Ký
            </button>
        </div>
    </form>

    <!-- MODAL XÁC NHẬN: HƯỚNG DẪN UX ĐĂNG NHẬP CHỦ ĐỘNG ĐỂ BIẾT KẾT QUẢ -->
    <div id="modalThanhCong" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden bg-black/60">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 sm:p-8 text-center space-y-5 bong-xanh transform transition-all scale-100">

            <!-- Icon trạng thái đồng hồ cát / chờ duyệt -->
            <div class="w-16 h-16 mx-auto rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-3xl shadow-sm">
                <i class="fa-solid fa-file-circle-check"></i>
            </div>

            <!-- Nội dung thông báo chuẩn UX -->
            <div class="space-y-3">
                <h3 class="text-xl font-bold text-slate-900">
                    Yêu Cầu Đăng Ký Đã Được Gửi Thành Công!
                </h3>

                <p class="text-xs text-slate-600 leading-relaxed">
                    Cơ quan quản lý thường xem xét và đối soát hồ sơ pháp lý trong vòng <strong class="text-slate-800">1 – 3 ngày làm việc</strong>.
                </p>

                <!-- Khối hướng dẫn hành vi kiểm tra chủ động (Pull UX) -->
                <div class="p-4 bg-[#f2faf5] rounded-xl border border-emerald-900/15 text-xs text-slate-700 text-left space-y-2">
                    <div class="font-bold text-emerald-800 flex items-center gap-1.5">
                        <i class="fa-solid fa-circle-info text-cyan-800"></i> Hướng Dẫn Theo Dõi Kết Quả:
                    </div>
                    <p class="leading-relaxed text-[11px] text-slate-600">
                        Để biết kết quả thẩm định, vui lòng <strong>đăng nhập lại bằng tài khoản vừa tạo</strong> — hệ thống sẽ tự động hiển thị trạng thái xử lý mới nhất (<em>Đang xử lý, Đã duyệt</em> hoặc <em>Lý do từ chối</em> nếu hồ sơ cần bổ sung).
                    </p>
                </div>
            </div>

            <!-- Cụm nút hành động: Nút 'Đăng nhập ngay' làm nút chính -->
            <div class="space-y-2 pt-2">
                <a href="dangNhap.php" class="w-full inline-flex items-center justify-center gap-2 py-3.5 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-xl shadow-md transition-all">
                    <i class="fa-solid fa-right-to-bracket"></i> Đăng Nhập Ngay
                </a>
                <a href="trangChu.php" class="w-full inline-block py-2.5 text-slate-500 hover:text-slate-800 text-xs font-semibold transition-colors">
                    Quay về Trang chủ
                </a>
            </div>
        </div>
    </div>
</main>

@endsection

@push('scripts')
    <script src="{{ asset('assets/js/nguoiTieuDung/dangKyToChuc.js') }}"></script>
@endpush
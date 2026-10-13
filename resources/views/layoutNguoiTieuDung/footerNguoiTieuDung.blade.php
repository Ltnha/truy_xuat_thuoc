<footer class="bg-white border-t border-emerald-900/10 mt-auto">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">

            <!-- Cột 1: Giới thiệu hệ thống -->
            <div class="md:col-span-1 space-y-3">
                <div class="flex items-center gap-2 text-emerald-700 font-bold text-lg">
                    <i class="fa-solid fa-shield-halved text-cyan-800 text-2xl"></i>
                    <span>PharmaChain</span>
                </div>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Hệ thống truy xuất nguồn gốc dược phẩm và kiểm soát chuỗi cung ứng bằng công nghệ Blockchain phi tập trung.
                </p>
                <div class="pt-2 flex items-center gap-2 text-xs font-semibold text-cyan-800">
                    <i class="fa-brands fa-ethereum text-sm"></i> Mạng: Polygon Testnet (EVM)
                </div>
            </div>

            <!-- Cột 2: Dành cho người dân -->
            <div>
                <h4 class="text-xs font-bold text-emerald-700 uppercase tracking-wider mb-4">Dành Cho Người Dân</h4>
                <ul class="space-y-2.5 text-xs text-slate-600 font-medium">
                    <li><a href="{{ url('/tra-cuu') }}" class="hover:text-emerald-700"><i class="fa-solid fa-angle-right mr-1.5"></i> Quét mã QR thuốc</a></li>
                    <li><a href="{{ url('/lich-su-tra-cuu') }}" class="hover:text-emerald-700"><i class="fa-solid fa-angle-right mr-1.5"></i> Lịch sử kiểm tra gần đây</a></li>
                    <li><a href="{{ url('/bao-cao-nghi-van') }}" class="hover:text-emerald-700"><i class="fa-solid fa-angle-right mr-1.5"></i> Gửi cảnh báo thuốc nghi giả</a></li>
                    <li><a href="{{ url('/#canhBaoThuoc') }}" class="hover:text-emerald-700"><i class="fa-solid fa-angle-right mr-1.5"></i> Danh sách thuốc bị thu hồi</a></li>
                </ul>
            </div>

            <!-- Cột 3: Mắt xích chuỗi cung ứng -->
            <div>
                <h4 class="text-xs font-bold text-emerald-700 uppercase tracking-wider mb-4">Mắt Xích Chuỗi Cung Ứng</h4>
                <ul class="space-y-2.5 text-xs text-slate-600 font-medium">
                    <li><a href="{{ url('/dang-ky-to-chuc') }}" class="hover:text-cyan-800"><i class="fa-solid fa-industry mr-1.5"></i> Nhà Sản Xuất Dược</a></li>
                    <li><a href="{{ url('/dang-ky-to-chuc') }}" class="hover:text-cyan-800"><i class="fa-solid fa-truck-ramp-box mr-1.5"></i> Nhà Phân Phối Tổng</a></li>
                    <li><a href="{{ url('/dang-ky-to-chuc') }}" class="hover:text-cyan-800"><i class="fa-solid fa-prescription-bottle-medical mr-1.5"></i> Nhà Thuốc / Bệnh Viện</a></li>
                    <li><a href="{{ url('/dang-nhap') }}" class="hover:text-cyan-800"><i class="fa-solid fa-building-columns mr-1.5"></i> Cổng Đăng Nhập Hệ Thống</a></li>
                </ul>
            </div>

            <!-- Cột 4: Cam kết & Pháp lý -->
            <div class="space-y-3">
                <h4 class="text-xs font-bold text-emerald-700 uppercase tracking-wider mb-4">Cam Kết Minh Bạch</h4>
                <div class="p-3 bg-emerald-50 rounded-lg border border-emerald-900/10 text-xs text-emerald-700 font-medium bong-xanh">
                    <i class="fa-solid fa-lock mr-1.5"></i> Dữ liệu sau khi kích hoạt lên Smart Contract là bất biến, không thể tẩy xóa hoặc can thiệp.
                </div>
                <p class="text-[11px] text-slate-500">
                    Phù hợp định hướng chuyển đổi số y tế và tiêu chuẩn dữ liệu phân phối thuốc an toàn GSP/GPP.
                </p>
            </div>
        </div>

        <div class="mt-8 pt-6 border-t border-emerald-900/10 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-2">
            <div>© 2026 PharmaChain. Đồ án Hệ thống truy xuất nguồn gốc dược phẩm bằng Blockchain.</div>
            <div class="flex gap-4">
                <span class="hover:underline cursor-pointer">Chính sách bảo mật</span>
                <span class="hover:underline cursor-pointer">Điều khoản sử dụng</span>
            </div>
        </div>
    </div>
</footer>

<script src="{{ asset('assets/js/layoutNguoiTieuDung/navbarNguoiTieuDung.js') }}"></script>
<script src="{{ asset('assets/js/layoutNguoiTieuDung/footerNguoiTieuDung.js') }}"></script>
<?php if (isset($fileJsRieng)) { ?>
    <script src="{{ asset($fileJsRieng) }}"></script>
<?php } ?>
@stack('scripts')
</body>

</html>
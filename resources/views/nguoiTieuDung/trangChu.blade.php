@extends('layoutNguoiTieuDung.main') {{-- Hoặc layout dùng chung phù hợp --}}

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/nguoiTieuDung/trangChu.css') }}">
@endpush

@section('content')
<main class="flex-1 pb-16">
    <!-- KHỐI HERO: Banner chính & Ô tra cứu nhanh -->
    <section class="bg-gradient-to-br from-emerald-700 via-[#1E5C3A] to-cyan-800 text-white py-16 px-4 sm:px-6 lg:px-8 shadow-md relative overflow-hidden">
        <div class="max-w-4xl mx-auto text-center relative z-10 space-y-6">
            <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/10 backdrop-blur-md text-xs font-semibold tracking-wide border border-white/20">
                <i class="fa-solid fa-shield-virus text-emerald-300"></i> Công nghệ Blockchain bảo vệ người tiêu dùng
            </span>
            <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight leading-tight">
                Xác Thực Nguồn Gốc Dược Phẩm<br class="hidden sm:inline"> Minh Bạch Trong Từng Hộp Thuốc
            </h1>
            <p class="text-sm sm:text-base text-emerald-100/90 max-w-2xl mx-auto leading-relaxed">
                Ghi nhận lịch sử chuyển giao từ Nhà sản xuất qua Nhà phân phối đến tận Nhà thuốc trên sổ cái bất biến, loại bỏ hoàn toàn nguy cơ thuốc giả và sửa đổi hạn dùng.
            </p>

            <!-- Ô tra cứu nhanh -->
            <div class="pt-4 max-w-2xl mx-auto">
                <form action="traCuuNguonGoc.php" method="GET" class="bg-white p-2 rounded-2xl bong-xanh flex flex-col sm:flex-row gap-2 border border-emerald-900/10">
                    <div class="flex-1 flex items-center px-4 gap-3 bg-[#f2faf5] rounded-xl border border-emerald-900/10 focus-within:border-emerald-700 focus-within:bg-white transition-all">
                        <i class="fa-solid fa-barcode text-slate-500 text-lg"></i>
                        <input type="text" name="maDinhDanh" placeholder="Nhập mã lô (VD: LOT-PARA-2026) hoặc mã hộp thuốc..." class="w-full py-3 bg-transparent text-sm text-slate-900 outline-none placeholder-slate-400 font-medium">
                    </div>
                    <button type="submit" class="bg-emerald-700 hover:bg-emerald-800 text-white px-7 py-3.5 rounded-xl font-bold text-sm shadow-sm transition-all flex items-center justify-center gap-2">
                        <i class="fa-solid fa-magnifying-glass"></i> Tra Cứu
                    </button>
                    <a href="traCuuNguonGoc.php?cheDo=quet" class="bg-cyan-800 hover:bg-cyan-900 text-white px-5 py-3.5 rounded-xl font-bold text-sm shadow-sm transition-all flex items-center justify-center gap-2" title="Quét mã QR bằng Camera">
                        <i class="fa-solid fa-qrcode text-base"></i> Quét QR
                    </a>
                </form>
            </div>
        </div>
    </section>

    <!-- KHỐI CHỈ SỐ MINH BẠCH ON-CHAIN -->
    <section class="max-w-6xl mx-auto px-4 -mt-8 relative z-20">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 bg-white p-6 rounded-2xl border border-emerald-900/10 bong-xanh">
            <div class="text-center p-3 border-r border-slate-100 last:border-none">
                <div class="text-2xl sm:text-3xl font-extrabold text-emerald-700" id="soLuongLo">12,850+</div>
                <div class="text-xs font-semibold text-slate-600 mt-1">Lô thuốc đã xác thực</div>
            </div>
            <div class="text-center p-3 md:border-r border-slate-100 last:border-none">
                <div class="text-2xl sm:text-3xl font-extrabold text-cyan-800" id="soDonViBan">1,420,000+</div>
                <div class="text-xs font-semibold text-slate-600 mt-1">Đơn vị thuốc lưu hành</div>
            </div>
            <div class="text-center p-3 border-r border-slate-100 last:border-none">
                <div class="text-2xl sm:text-3xl font-extrabold text-emerald-700">100%</div>
                <div class="text-xs font-semibold text-slate-600 mt-1">Giao dịch có Hash On-chain</div>
            </div>
            <div class="text-center p-3">
                <div class="text-2xl sm:text-3xl font-extrabold text-red-600">48</div>
                <div class="text-xs font-semibold text-slate-600 mt-1">Lô thuốc thu hồi kịp thời</div>
            </div>
        </div>
    </section>

    <!-- KHỐI QUY TRÌNH 4 MẮT XÍCH CHUỖI CUNG ỨNG -->
    <section class="max-w-6xl mx-auto px-4 py-16">
        <div class="text-center max-w-2xl mx-auto mb-12 space-y-2">
            <h2 class="text-2xl sm:text-3xl font-extrabold text-emerald-700">Vòng Đời Thuốc Khép Kín</h2>
            <p class="text-xs sm:text-sm text-slate-600">Hệ thống áp dụng hợp đồng thông minh Smart Contract để kiểm soát từng bước chuyển giao hàng hóa.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 relative">
            <!-- Bước 1 -->
            <div class="bg-white p-6 rounded-2xl border border-emerald-900/10 bong-xanh bong-xanh-hover text-center space-y-3">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-2xl font-bold">
                    <i class="fa-solid fa-industry"></i>
                </div>
                <div class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700">BƯỚC 1</div>
                <h3 class="text-base font-bold text-slate-900">Nhà Sản Xuất</h3>
                <p class="text-xs text-slate-600 leading-relaxed">Đăng ký sản phẩm được duyệt, sinh mã QR cấp lô và mã định danh duy nhất (serial) cho từng hộp thuốc.</p>
            </div>

            <!-- Bước 2 -->
            <div class="bg-white p-6 rounded-2xl border border-emerald-900/10 bong-xanh bong-xanh-hover text-center space-y-3">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-cyan-50 text-cyan-800 flex items-center justify-center text-2xl font-bold">
                    <i class="fa-solid fa-truck-moving"></i>
                </div>
                <div class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-cyan-50 text-cyan-800">BƯỚC 2</div>
                <h3 class="text-base font-bold text-slate-900">Nhà Phân Phối</h3>
                <p class="text-xs text-slate-600 leading-relaxed">Xác nhận nhận hàng hai bước (PENDING → RECEIVED) và khởi tạo phân phối theo đúng số lượng thực tế.</p>
            </div>

            <!-- Bước 3 -->
            <div class="bg-white p-6 rounded-2xl border border-emerald-900/10 bong-xanh bong-xanh-hover text-center space-y-3">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-2xl font-bold">
                    <i class="fa-solid fa-prescription-bottle-medical"></i>
                </div>
                <div class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700">BƯỚC 3</div>
                <h3 class="text-base font-bold text-slate-900">Nhà Thuốc Bán Lẻ</h3>
                <p class="text-xs text-slate-600 leading-relaxed">Nhận hàng từ nhà phân phối và kích hoạt sự kiện bán lẻ (Dispense) từng đơn vị thuốc tới tay người bệnh.</p>
            </div>

            <!-- Bước 4 -->
            <div class="bg-white p-6 rounded-2xl border-2 border-cyan-800 bong-xanh bong-xanh-hover text-center space-y-3">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-cyan-800 text-white flex items-center justify-center text-2xl font-bold shadow-md">
                    <i class="fa-solid fa-user-check"></i>
                </div>
                <div class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-cyan-800 text-white">BƯỚC 4</div>
                <h3 class="text-base font-bold text-cyan-800">Người Tiêu Dùng</h3>
                <p class="text-xs text-slate-600 leading-relaxed">Quét mã QR công khai để tự mình kiểm tra lịch sử lưu hành mà không cần phụ thuộc vào bất kỳ máy chủ trung gian nào.</p>
            </div>
        </div>
    </section>

    <!-- KHỐI CẢNH BÁO THUỐC THU HỒI TỪ CỤC QUẢN LÝ DƯỢC -->
    <section id="canhBaoThuoc" class="max-w-6xl mx-auto px-4 pb-8">
        <div class="bg-white rounded-2xl border border-emerald-900/10 p-6 sm:p-8 bong-xanh space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Cảnh Báo Thu hồi Dược Phẩm Khẩn Cấp</h2>
                        <p class="text-xs text-slate-600">Dữ liệu được đồng bộ trực tiếp từ lệnh Smart Contract của Cục Quản lý Dược</p>
                    </div>
                </div>
                <a href="../congKhai/baoCaoNghiVan.php" class="inline-flex items-center gap-2 px-4 py-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg text-xs font-bold transition-all self-start sm:self-auto">
                    <i class="fa-solid fa-flag"></i> Báo Cáo Nghi Vấn Lô Thuốc
                </a>
            </div>

            <!-- Bảng dữ liệu cảnh báo -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-nenChinh text-slate-600 uppercase tracking-wider border-b border-emerald-900/10">
                            <th class="py-3 px-4 font-bold">Mã Lô</th>
                            <th class="py-3 px-4 font-bold">Tên Thuốc / Sản Phẩm</th>
                            <th class="py-3 px-4 font-bold">Nhà Sản Xuất</th>
                            <th class="py-3 px-4 font-bold">Lý Do Thu Hồi</th>
                            <th class="py-3 px-4 font-bold">Mã Giao Dịch (txHash)</th>
                            <th class="py-3 px-4 font-bold text-center">Hành Động</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        <tr class="hover:bg-red-50/40 transition-colors">
                            <td class="py-3.5 px-4 font-bold text-red-600">LOT-AMOX-2025-09</td>
                            <td class="py-3.5 px-4 font-semibold text-slate-900">Amoxicillin 500mg</td>
                            <td class="py-3.5 px-4 text-slate-600">Dược Phẩm Pharbaco</td>
                            <td class="py-3.5 px-4 text-red-600">Không đạt tiêu chuẩn độ hòa tan theo công văn 458/QLD</td>
                            <td class="py-3.5 px-4 font-mono text-cyan-800 text-[11px]">0x71a4...9f2b</td>
                            <td class="py-3.5 px-4 text-center">
                                <a href="traCuuNguonGoc.php?maDinhDanh=LOT-AMOX-2025-09" class="px-3 py-1.5 rounded bg-emerald-50 hover:bg-emerald-700 hover:text-white text-emerald-700 font-bold text-[11px] transition-all">
                                    Chi tiết
                                </a>
                            </td>
                        </tr>
                        <tr class="hover:bg-red-50/40 transition-colors">
                            <td class="py-3.5 px-4 font-bold text-red-600">LOT-CEFA-2026-01</td>
                            <td class="py-3.5 px-4 font-semibold text-slate-900">Cefalexin 500mg</td>
                            <td class="py-3.5 px-4 text-slate-600">Dược Hậu Giang (DHG)</td>
                            <td class="py-3.5 px-4 text-red-600">Nghi vấn tem nhãn bị tẩy xóa hạn sử dụng tại kênh lẻ</td>
                            <td class="py-3.5 px-4 font-mono text-cyan-800 text-[11px]">0x3bc1...e890</td>
                            <td class="py-3.5 px-4 text-center">
                                <a href="traCuuNguonGoc.php?maDinhDanh=LOT-CEFA-2026-01" class="px-3 py-1.5 rounded bg-emerald-50 hover:bg-emerald-700 hover:text-white text-emerald-700 font-bold text-[11px] transition-all">
                                    Chi tiết
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</main>

@endsection

@push('scripts')
    <script src="{{ asset('assets/js/nguoiTieuDung/trangChu.js') }}"></script>
@endpush
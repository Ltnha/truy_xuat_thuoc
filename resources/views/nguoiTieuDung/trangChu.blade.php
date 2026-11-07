@extends('layoutNguoiTieuDung.main')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/nguoiTieuDung/trangChu.css') }}">
@endpush

@section('content')
<main class="flex-1 pb-16">
    <!-- 1. KHỐI HERO: BANNER CHÍNH & Ô TRA CỨU NHANH -->
    <section class="bg-gradient-to-br from-emerald-700 via-[#1E5C3A] to-cyan-800 text-white py-14 px-4 sm:px-6 lg:px-8 shadow-md relative overflow-hidden">
        <div class="max-w-4xl mx-auto text-center relative z-10 space-y-5">
            <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/10 backdrop-blur-md text-xs font-semibold tracking-wide border border-white/20">
                <i class="fa-solid fa-shield-virus text-emerald-300"></i> Công nghệ Blockchain bảo vệ người tiêu dùng
            </span>
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight leading-tight">
                Xác Thực Nguồn Gốc Dược Phẩm<br class="hidden sm:inline"> Minh Bạch Trong Từng Hộp Thuốc
            </h1>
            <p class="text-xs sm:text-sm text-emerald-100/90 max-w-2xl mx-auto leading-relaxed">
                Ghi nhận lịch sử chuyển giao từ Nhà sản xuất qua Nhà phân phối đến tận Nhà thuốc trên sổ cái bất biến, giúp phát hiện và giảm thiểu nguy cơ thuốc giả, sửa đổi hạn dùng.
            </p>

            <!-- Ô tra cứu nhanh -->
            <div class="pt-3 max-w-2xl mx-auto">
                <form action="{{ url('/tra-cuu-nguon-goc') }}" method="GET" class="bg-white p-2 rounded-2xl bong-xanh flex flex-col sm:flex-row gap-2 border border-emerald-900/10">
                    <div class="flex-1 flex items-center px-4 gap-3 bg-[#f2faf5] rounded-xl border border-emerald-900/10 focus-within:border-emerald-700 focus-within:bg-white transition-all">
                        <i class="fa-solid fa-barcode text-slate-500 text-base"></i>
                        <input type="text" name="maDinhDanh" placeholder="Nhập mã lô (VD: LOT-PARA-2026) hoặc mã hộp thuốc..." class="w-full py-3 bg-transparent text-xs sm:text-sm text-slate-900 outline-none placeholder-slate-400 font-medium">
                    </div>
                    <button type="submit" class="bg-emerald-700 hover:bg-emerald-800 text-white px-6 py-3 rounded-xl font-bold text-xs sm:text-sm shadow-sm transition-all flex items-center justify-center gap-2 cursor-pointer">
                        <i class="fa-solid fa-magnifying-glass"></i> Tra Cứu
                    </button>
                    <a href="{{ url('/tra-cuu-nguon-goc?cheDo=quet') }}" class="bg-cyan-800 hover:bg-cyan-900 text-white px-4 py-3 rounded-xl font-bold text-xs sm:text-sm shadow-sm transition-all flex items-center justify-center gap-2" title="Quét mã QR bằng Camera">
                        <i class="fa-solid fa-qrcode text-base"></i> Quét QR
                    </a>
                </form>
            </div>
        </div>
    </section>

    <!-- 2. KHỐI QUY TRÌNH 4 MẮT XÍCH (VÒNG ĐỜI THUỐC KHÉP KÍN - ĐẨY LÊN TRÊN) -->
    <section class="max-w-6xl mx-auto px-4 pt-12 pb-8">
        <div class="text-center max-w-2xl mx-auto mb-8 space-y-1.5">
            <h2 class="text-2xl sm:text-3xl font-extrabold text-emerald-800 flex items-center justify-center gap-2">
                <i class="fa-solid fa-arrows-spin text-emerald-600"></i> Vòng Đời Thuốc Khép Kín
            </h2>
            <p class="text-xs sm:text-sm text-slate-600">Hệ thống áp dụng hợp đồng thông minh Smart Contract để kiểm soát minh bạch từng bước lưu thông.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 relative">
            <div class="bg-white p-5 rounded-2xl border border-emerald-900/10 bong-xanh text-center space-y-2.5 hover:border-emerald-300 transition-all">
                <div class="w-12 h-12 mx-auto rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-xl font-bold">
                    <i class="fa-solid fa-industry"></i>
                </div>
                <div class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700">BƯỚC 1</div>
                <h3 class="text-sm font-bold text-slate-900">Nhà Sản Xuất</h3>
                <p class="text-xs text-slate-600 leading-relaxed">Đăng ký sản phẩm được duyệt, khởi tạo lô thuốc và kích hoạt mã định danh QR trên hệ thống.</p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-emerald-900/10 bong-xanh text-center space-y-2.5 hover:border-cyan-300 transition-all">
                <div class="w-12 h-12 mx-auto rounded-xl bg-cyan-50 text-cyan-800 flex items-center justify-center text-xl font-bold">
                    <i class="fa-solid fa-truck-moving"></i>
                </div>
                <div class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-cyan-50 text-cyan-800">BƯỚC 2</div>
                <h3 class="text-sm font-bold text-slate-900">Nhà Phân Phối</h3>
                <p class="text-xs text-slate-600 leading-relaxed">Xác nhận nhận hàng hai bước (PENDING → RECEIVED) và tiếp nhận phân phối lô thuốc chuẩn xác.</p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-emerald-900/10 bong-xanh text-center space-y-2.5 hover:border-emerald-300 transition-all">
                <div class="w-12 h-12 mx-auto rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-xl font-bold">
                    <i class="fa-solid fa-prescription-bottle-medical"></i>
                </div>
                <div class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700">BƯỚC 3</div>
                <h3 class="text-sm font-bold text-slate-900">Nhà Thuốc Bán Lẻ</h3>
                <p class="text-xs text-slate-600 leading-relaxed">Nhận hàng từ nhà phân phối và quét mã xác nhận bán lẻ từng đơn vị thuốc tới tay người tiêu dùng.</p>
            </div>

            <div class="bg-white p-5 rounded-2xl border-2 border-cyan-800 bong-xanh text-center space-y-2.5 shadow-xs">
                <div class="w-12 h-12 mx-auto rounded-xl bg-cyan-800 text-white flex items-center justify-center text-xl font-bold shadow-md">
                    <i class="fa-solid fa-user-check"></i>
                </div>
                <div class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-cyan-800 text-white">BƯỚC 4</div>
                <h3 class="text-sm font-bold text-cyan-900">Người Tiêu Dùng</h3>
                <p class="text-xs text-slate-600 leading-relaxed">Quét mã QR công khai để kiểm tra thông tin xuất xứ và trạng thái thu hồi của sản phẩm.</p>
            </div>
        </div>
    </section>

    <!-- 3. KHỐI 5 CHỈ SỐ THỐNG KÊ (GỌN GÀNG, BỐ TRÍ CÂN BẰNG NẰM TRÊN 1 HÀNG) -->
    <section class="max-w-6xl mx-auto px-4 pb-12">
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-emerald-900/10 bong-xanh">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 divide-y sm:divide-y-0 sm:divide-x divide-slate-100">
                <div class="text-center p-2">
                    <div class="text-2xl sm:text-3xl font-extrabold text-emerald-700 font-mono leading-none">{{ number_format($tongLoDangKy) }}</div>
                    <div class="text-xs font-semibold text-slate-600 mt-2">Lô thuốc đã đăng ký</div>
                </div>
                <div class="text-center p-2 pt-4 sm:pt-2">
                    <div class="text-2xl sm:text-3xl font-extrabold text-indigo-700 font-mono leading-none">{{ number_format($tongSanPhamDuyet) }}</div>
                    <div class="text-xs font-semibold text-slate-600 mt-2">Sản phẩm đã cấp phép</div>
                </div>
                <div class="text-center p-2 pt-4 sm:pt-2">
                    <div class="text-2xl sm:text-3xl font-extrabold text-blue-700 font-mono leading-none">{{ number_format($tongToChucDuyet) }}</div>
                    <div class="text-xs font-semibold text-slate-600 mt-2">Cơ sở đã xác minh</div>
                </div>
                <div class="text-center p-2 pt-4 sm:pt-2">
                    <div class="text-2xl sm:text-3xl font-extrabold text-cyan-800 font-mono leading-none">{{ number_format($tongDonViDaBan) }}</div>
                    <div class="text-xs font-semibold text-slate-600 mt-2">Đơn vị đã bán lẻ</div>
                </div>
                <div class="text-center p-2 col-span-2 sm:col-span-1 pt-4 sm:pt-2">
                    <div class="text-2xl sm:text-3xl font-extrabold text-red-600 font-mono leading-none">{{ number_format($tongLoThuHoi) }}</div>
                    <div class="text-xs font-semibold text-slate-600 mt-2">Lô thuốc bị thu hồi</div>
                </div>
            </div>
        </div>
    </section>

    <!-- 4. KHỐI CẢNH BÁO THU HỒI DƯỢC PHẨM KHẨN CẤP (CÓ PHÂN TRANG & NÚT CHI TIẾT THỰC TẾ) -->
    <section id="canhBaoThuoc" class="max-w-6xl mx-auto px-4 pb-12">
        <div class="bg-white rounded-2xl border border-emerald-900/10 p-5 sm:p-6 bong-xanh space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center text-lg shrink-0">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div>
                        <h2 class="text-base sm:text-lg font-bold text-slate-900">Cảnh Báo Thu Hồi Dược Phẩm Khẩn Cấp</h2>
                        <p class="text-xs text-slate-500">Do Cơ quan quản lý công bố, có bằng chứng xác minh trên Blockchain</p>
                    </div>
                </div>
                <a href="{{ url('/bao-cao-nghi-van') }}" class="inline-flex items-center gap-2 px-3.5 py-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-xl text-xs font-bold transition-all border border-red-200 self-start sm:self-auto shrink-0">
                    <i class="fa-solid fa-flag"></i> Báo Cáo Nghi Vấn Thuốc
                </a>
            </div>

            <!-- Bảng dữ liệu cảnh báo -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 uppercase tracking-wider border-b border-slate-200 text-[11px]">
                            <th class="py-3 px-3.5 font-bold whitespace-nowrap">Mã Lô</th>
                            <th class="py-3 px-3.5 font-bold">Tên Thuốc / Sản Phẩm</th>
                            <th class="py-3 px-3.5 font-bold">Nhà Sản Xuất</th>
                            <th class="py-3 px-3.5 font-bold">Lý Do Thu Hồi</th>
                            <th class="py-3 px-3.5 font-bold whitespace-nowrap">Ngày Thu Hồi</th>
                            <th class="py-3 px-3.5 font-bold whitespace-nowrap">Bằng Chứng On-chain</th>
                            <th class="py-3 px-3.5 font-bold text-center whitespace-nowrap">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody id="thanBangThuHoiPublic" class="divide-y divide-slate-100 font-medium">
                        @forelse($danhSachThuHoi as $th)
                        <tr class="dong-thu-hoi-item hover:bg-red-50/40 transition-colors">
                            <td class="py-3 px-3.5 font-bold text-red-600 font-mono whitespace-nowrap">{{ $th['maLo'] }}</td>
                            <td class="py-3 px-3.5 font-bold text-slate-900 leading-tight">{{ $th['tenThuoc'] }}</td>
                            <td class="py-3 px-3.5 text-slate-700 leading-tight">{{ $th['nhaSanXuat'] }}</td>
                            <td class="py-3 px-3.5 text-red-600 leading-relaxed max-w-xs">{{ $th['lyDo'] }}</td>
                            <td class="py-3 px-3.5 text-slate-500 whitespace-nowrap font-mono text-[11px]">{{ \Carbon\Carbon::parse($th['ngayThuHoi'])->format('d/m/Y') }}</td>
                            <td class="py-3 px-3.5 font-mono text-[11px] whitespace-nowrap">
                                @if($th['txHash'])
                                <a href="https://sepolia.etherscan.io/tx/{{ $th['txHash'] }}" target="_blank" class="text-indigo-700 hover:underline inline-flex items-center gap-1 font-bold" title="Xem giao dịch trên Sepolia Etherscan">
                                    {{ Str::limit($th['txHash'], 12) }}
                                    <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
                                </a>
                                @else
                                <span class="text-slate-400 italic">Đang đồng bộ</span>
                                @endif
                            </td>
                            <td class="py-3 px-3.5 text-center whitespace-nowrap">
                                <a href="{{ url('/tra-cuu-nguon-goc?maDinhDanh=' . $th['maLo']) }}" class="px-3 py-1.5 rounded-lg bg-red-50 hover:bg-red-600 hover:text-white text-red-700 font-bold text-[11px] transition-all inline-flex items-center gap-1.5 border border-red-200 shadow-2xs">
                                    <i class="fa-solid fa-magnifying-glass-location"></i> Tra cứu lô
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400 italic">
                                Chưa ghi nhận lô thuốc nào bị thu hồi trên hệ thống.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Thanh phân trang bảng thu hồi -->
            <div id="thanhPhanTrangThuHoi" class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2 text-xs text-slate-500 border-t border-slate-100">
                <span id="thongTinTrangThuHoi" class="font-medium">Hiển thị 0 / 0 lô thu hồi</span>
                <div class="inline-flex items-center gap-1.5" id="nutPhanTrangThuHoi">
                    <!-- Dynamic buttons bằng JS -->
                </div>
            </div>
        </div>
    </section>

    <!-- 5. KHỐI DANH MỤC THUỐC ĐÃ CẤP PHÉP (CÓ TÌM KIẾM & PHÂN TRANG) -->
    <section class="max-w-6xl mx-auto px-4 pb-12">
        <div class="bg-white rounded-2xl border border-emerald-900/10 p-5 sm:p-6 bong-xanh space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center text-lg shrink-0">
                        <i class="fa-solid fa-pills"></i>
                    </div>
                    <div>
                        <h2 class="text-base sm:text-lg font-bold text-slate-900">Danh Mục Dược Phẩm Đã Cấp Phép</h2>
                        <p class="text-xs text-slate-500">Đối chiếu danh sách thuốc đã được phê duyệt lưu hành chính thức</p>
                    </div>
                </div>

                <!-- Ô tìm kiếm thuốc -->
                <div class="relative w-full sm:w-72">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 text-xs pointer-events-none">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" id="timKiemThuocPublic" placeholder="Tìm tên thuốc, số ĐK, thành phần..." class="w-full pl-8 pr-3 py-2 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-medium outline-none focus:bg-white focus:border-indigo-600 transition-all">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 uppercase tracking-wider border-b border-slate-200 text-[11px]">
                            <th class="py-3 px-3.5 font-bold">Tên Thuốc</th>
                            <th class="py-3 px-3.5 font-bold whitespace-nowrap">Số ĐK Lưu Hành</th>
                            <th class="py-3 px-3.5 font-bold">Thành Phần</th>
                            <th class="py-3 px-3 font-bold whitespace-nowrap">Hàm Lượng</th>
                            <th class="py-3 px-3 font-bold whitespace-nowrap">Dạng Bào Chế</th>
                            <th class="py-3 px-3.5 font-bold">Nhà Sản Xuất</th>
                        </tr>
                    </thead>
                    <tbody id="thanBangThuocPublic" class="divide-y divide-slate-100 font-medium">
                        @forelse($danhSachSanPham as $sp)
                        <tr class="dong-thuoc-item hover:bg-slate-50 transition-colors">
                            <td class="py-3 px-3.5 font-bold text-slate-900 ten-thuoc">{{ $sp['tenSanPham'] }}</td>
                            <td class="py-3 px-3.5 font-mono font-bold text-cyan-800 whitespace-nowrap so-dk">{{ $sp['soDangKy'] }}</td>
                            <td class="py-3 px-3.5 text-slate-700 thanh-phan leading-relaxed">{{ $sp['thanhPhan'] }}</td>
                            <td class="py-3 px-3 text-slate-700 whitespace-nowrap">{{ $sp['hamLuong'] }}</td>
                            <td class="py-3 px-3 text-slate-700 whitespace-nowrap">{{ $sp['dangBaoChe'] }}</td>
                            <td class="py-3 px-3.5 text-slate-800 font-semibold leading-tight">{{ $sp['nhaSanXuat'] }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400 italic">
                                Chưa có sản phẩm dược phẩm nào được duyệt trên hệ thống.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Thanh phân trang bảng dược phẩm -->
            <div id="thanhPhanTrangThuoc" class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2 text-xs text-slate-500 border-t border-slate-100">
                <span id="thongTinTrangThuoc" class="font-medium">Hiển thị 0 / 0 sản phẩm</span>
                <div class="inline-flex items-center gap-1.5" id="nutPhanTrangThuoc">
                    <!-- Dynamic buttons bằng JS -->
                </div>
            </div>
        </div>
    </section>

    <!-- 6. KHỐI DANH SÁCH CƠ SỞ DƯỢC PHẨM ĐÃ XÁC MINH (CÓ LỌC LOẠI HÌNH & PHÂN TRANG) -->
    <section class="max-w-6xl mx-auto px-4 pb-8">
        <div class="bg-white rounded-2xl border border-emerald-900/10 p-5 sm:p-6 bong-xanh space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center text-lg shrink-0">
                        <i class="fa-solid fa-building-circle-check"></i>
                    </div>
                    <div>
                        <h2 class="text-base sm:text-lg font-bold text-slate-900">Danh Sách Cơ Sở Dược Phẩm Đã Xác Minh</h2>
                        <p class="text-xs text-slate-500">Giúp người dân tra cứu các đơn vị chuỗi cung ứng đã được cấp phép</p>
                    </div>
                </div>

                <!-- Ô lọc loại cơ sở & Tìm kiếm -->
                <div class="flex flex-wrap items-center gap-2.5 w-full sm:w-auto">
                    <select id="locLoaiCoSoPublic" class="px-3 py-2 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-bold text-slate-700 outline-none focus:bg-white focus:border-blue-600 transition-all">
                        <option value="ALL">-- Tất cả loại hình --</option>
                        <option value="NHA_SAN_XUAT">Nhà sản xuất</option>
                        <option value="NHA_PHAN_PHOI">Nhà phân phối</option>
                        <option value="NHA_THUOC">Nhà thuốc bán lẻ</option>
                    </select>

                    <div class="relative flex-1 sm:w-60">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 text-xs pointer-events-none">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </span>
                        <input type="text" id="timKiemCoSoPublic" placeholder="Tìm tên cơ sở, địa chỉ..." class="w-full pl-8 pr-3 py-2 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-medium outline-none focus:bg-white focus:border-blue-600 transition-all">
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 uppercase tracking-wider border-b border-slate-200 text-[11px]">
                            <th class="py-3 px-3.5 font-bold">Tên Cơ Sở / Đơn Vị</th>
                            <th class="py-3 px-3.5 font-bold text-center whitespace-nowrap">Loại Hình Cơ Sở</th>
                            <th class="py-3 px-3.5 font-bold">Địa Chỉ Công Bố</th>
                            <th class="py-3 px-3.5 font-bold text-center whitespace-nowrap">Trạng Thái Xác Minh</th>
                        </tr>
                    </thead>
                    <tbody id="thanBangCoSoPublic" class="divide-y divide-slate-100 font-medium">
                        @forelse($danhSachToChuc as $tc)
                        <tr class="dong-co-so-item hover:bg-slate-50 transition-colors" data-loai="{{ $tc->loaiToChuc }}">
                            <td class="py-3 px-3.5 font-bold text-slate-900 ten-co-so leading-tight">{{ $tc->tenToChuc }}</td>
                            <td class="py-3 px-3.5 text-center whitespace-nowrap">
                                @if($tc->loaiToChuc === 'NHA_SAN_XUAT')
                                <span class="px-2.5 py-0.5 bg-emerald-100 text-emerald-800 rounded-full font-bold text-[10px] border border-emerald-200">Nhà sản xuất</span>
                                @elseif($tc->loaiToChuc === 'NHA_PHAN_PHOI')
                                <span class="px-2.5 py-0.5 bg-cyan-100 text-cyan-800 rounded-full font-bold text-[10px] border border-cyan-200">Nhà phân phối</span>
                                @elseif($tc->loaiToChuc === 'NHA_THUOC')
                                <span class="px-2.5 py-0.5 bg-indigo-100 text-indigo-800 rounded-full font-bold text-[10px] border border-indigo-200">Nhà thuốc bán lẻ</span>
                                @else
                                <span class="px-2.5 py-0.5 bg-slate-100 text-slate-700 rounded-full font-bold text-[10px]">Cơ quan quản lý</span>
                                @endif
                            </td>
                            <td class="py-3 px-3.5 text-slate-600 dia-chi-co-so leading-relaxed">{{ $tc->diaChi ?? 'Chưa cập nhật' }}</td>
                            <td class="py-3 px-3.5 text-center whitespace-nowrap">
                                <span class="px-2.5 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full font-bold text-[10px] inline-flex items-center gap-1">
                                    <i class="fa-solid fa-circle-check"></i> Đã duyệt
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-slate-400 italic">
                                Chưa có cơ sở dược phẩm nào được xác minh trên hệ thống.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Thanh phân trang bảng cơ sở -->
            <div id="thanhPhanTrangCoSo" class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2 text-xs text-slate-500 border-t border-slate-100">
                <span id="thongTinTrangCoSo" class="font-medium">Hiển thị 0 / 0 cơ sở</span>
                <div class="inline-flex items-center gap-1.5" id="nutPhanTrangCoSo">
                    <!-- Dynamic buttons bằng JS -->
                </div>
            </div>
        </div>
    </section>
</main>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/nguoiTieuDung/trangChu.js') }}"></script>
@endpush
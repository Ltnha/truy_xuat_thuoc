@extends('layoutNguoiTieuDung.main')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/nguoiTieuDung/traCuuNguonGoc.css') }}">
@endpush

@section('content')
<main class="flex-1 py-10 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto w-full space-y-8">

    <!-- TIÊU ĐỀ TRANG CĂN CHỈNH MỚI + NÚT XEM LỊCH SỬ -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-emerald-900/10 pb-5">
        <div class="flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-xl shrink-0 shadow-sm border border-emerald-900/10">
                <i class="fa-solid fa-qrcode"></i>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-emerald-700 tracking-tight">
                Tra Cứu & Xác Thực Nguồn Gốc Thuốc
            </h1>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ url('/lich-su-tra-cuu') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200/80 text-xs font-bold transition-all shadow-sm">
                <i class="fa-solid fa-clock-rotate-left"></i> Xem Lịch Sử
            </a>
        </div>
    </div>

    <!-- KHỐI FORM NHẬP LIỆU & TẢI ẢNH MÃ QR -->
    <div class="bg-white rounded-2xl border border-emerald-900/10 p-6 sm:p-8 bong-xanh space-y-6">

        <div class="flex border-b border-slate-200">
            <button id="tabNhapMa" class="pb-3 px-6 text-sm font-bold border-b-2 border-emerald-700 text-emerald-700 flex items-center gap-2 transition-all">
                <i class="fa-solid fa-keyboard"></i> Nhập Mã Định Danh
            </button>
            <button id="tabQuetQr" class="pb-3 px-6 text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-emerald-700 flex items-center gap-2 transition-all">
                <i class="fa-solid fa-cloud-arrow-up"></i> Tải Ảnh Mã QR
            </button>
        </div>

        <!-- Khung 1: Nhập mã bằng bàn phím -->
        <div id="khungNhapMa" class="space-y-4">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1 relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 text-sm pointer-events-none">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" id="oNhapMa" value="<?php echo htmlspecialchars($_GET['maDinhDanh'] ?? $_GET['maLo'] ?? $_GET['ma'] ?? ''); ?>" placeholder="Nhập mã lô nghiệp vụ hoặc quét mã QR đại diện cho lô thuốc..." class="w-full pl-10 pr-4 py-3 bg-[#f2faf5] border border-emerald-900/15 rounded-xl text-sm font-medium text-slate-900 focus:bg-white focus:border-emerald-700 focus:ring-2 focus:ring-emerald-100 outline-none transition-all">
                </div>
                <button type="button" id="nutXacNhanTraCuu" class="bg-emerald-700 hover:bg-emerald-800 text-white px-8 py-3 rounded-xl font-bold text-sm shadow-sm transition-all flex items-center justify-center gap-2">
                    <i class="fa-solid fa-shield-halved"></i> Xác Thực Ngay
                </button>
            </div>
        </div>

        <!-- Khung 2: Tải ảnh mã QR lên thay cho quét camera -->
        <div id="khungQuetCamera" class="hidden space-y-5 py-2">
            <div id="khungTaiAnhQR" class="w-full max-w-2xl mx-auto border-2 border-dashed border-emerald-900/25 hover:border-emerald-700 rounded-2xl p-6 sm:p-8 bg-[#f2faf5] hover:bg-emerald-50/50 transition-all cursor-pointer text-center relative group">
                <input type="file" id="tepAnhQRInput" accept="image/*" class="hidden">

                <!-- Trạng thái 1: Chưa chọn ảnh -->
                <div id="khungChuaChonAnh" class="space-y-3">
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-emerald-100/80 text-emerald-700 flex items-center justify-center text-3xl group-hover:scale-110 transition-transform shadow-xs">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                    </div>
                    <div>
                        <div class="text-base font-bold text-slate-800">Kéo & Thả hoặc Nhấp Để Tải Ảnh Mã QR Lên</div>
                        <p class="text-xs text-slate-500 mt-1">Hỗ trợ định dạng JPG, PNG, WEBP chụp từ điện thoại hoặc máy tính</p>
                    </div>
                    <div class="pt-1">
                        <span class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold shadow-sm transition-all">
                            <i class="fa-solid fa-folder-open"></i> Chọn Ảnh Từ Thiết Bị
                        </span>
                    </div>
                </div>

                <!-- Trạng thái 2: Đang xử lý / Phân tích ảnh -->
                <div id="khungDangXuLyAnh" class="hidden py-6 space-y-3">
                    <div class="w-12 h-12 mx-auto rounded-full border-3 border-emerald-700 border-t-transparent animate-spin"></div>
                    <div class="text-sm font-bold text-emerald-800">Đang nhận diện & giải mã mã QR từ hình ảnh...</div>
                    <p class="text-xs text-slate-500">Vui lòng chờ trong giây lát</p>
                </div>

                <!-- Trạng thái 3: Đã chọn ảnh & nhận diện thành công -->
                <div id="khungDaChonAnh" class="hidden space-y-4">
                    <div class="flex flex-col sm:flex-row items-center gap-4 bg-white p-4 rounded-xl border border-emerald-900/15 text-left shadow-2xs">
                        <div class="relative group/preview shrink-0">
                            <img id="anhQRPreview" src="" alt="Ảnh QR Preview" class="w-20 h-20 object-contain rounded-lg border border-slate-200 bg-slate-50 p-1">
                            <div class="absolute inset-0 bg-black/40 rounded-lg opacity-0 group-hover/preview:opacity-100 flex items-center justify-center text-white text-xs font-bold transition-opacity">
                                <i class="fa-solid fa-eye"></i>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0 space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[11px] font-bold inline-flex items-center gap-1">
                                    <i class="fa-solid fa-circle-check"></i> Đã nhận diện mã
                                </span>
                                <span id="dungLuongAnhQR" class="text-[11px] text-slate-400 font-mono"></span>
                            </div>
                            <h4 id="tenAnhQRDangChon" class="font-bold text-sm text-slate-900 truncate"></h4>
                            <div class="text-xs text-slate-600 flex items-center gap-2">
                                <span>Mã giải mã được:</span>
                                <span id="maNhanDienDuoc" class="font-mono font-bold text-cyan-800 bg-cyan-50 px-2 py-0.5 rounded border border-cyan-800/15"></span>
                            </div>
                        </div>
                        <button type="button" id="nutXoaAnhQR" class="p-2.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-xl text-sm transition-all" title="Hủy ảnh">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </div>
                    <div class="flex flex-wrap items-center justify-center gap-3">
                        <button type="button" id="nutChonAnhKhac" class="px-4 py-2 rounded-xl border border-slate-300 hover:bg-slate-100 text-slate-700 text-xs font-bold transition-all">
                            <i class="fa-solid fa-arrow-rotate-right mr-1.5"></i> Chọn ảnh khác
                        </button>
                        <button type="button" id="nutTraCuuTuAnh" class="px-6 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold transition-all shadow-sm">
                            <i class="fa-solid fa-shield-halved mr-1.5"></i> Xác thực lại nguồn gốc
                        </button>
                    </div>
                </div>
            </div>

            <div class="max-w-2xl mx-auto flex items-start gap-2.5 p-3.5 bg-cyan-50/80 rounded-xl border border-cyan-800/15 text-xs text-cyan-900 text-left">
                <i class="fa-solid fa-circle-info text-cyan-800 mt-0.5 shrink-0"></i>
                <div class="leading-relaxed">
                    <strong>Hướng dẫn:</strong> Chụp ảnh mã QR đại diện cho toàn bộ lô thuốc. Hệ thống sẽ tự động quét, giải mã và đối chiếu nguồn gốc trên Blockchain tức thì.
                </div>
            </div>
        </div>
    </div>

    <!-- KHU VỰC HIỂN THỊ KẾT QUẢ XÁC THỰC -->
    <div id="khuVucKetQua" class="hidden space-y-6">

        <!-- TRẠNG THÁI ĐANG XÁC THỰC -->
        <div id="khungDangXacThuc" class="hidden p-8 sm:p-12 bg-white rounded-2xl border border-emerald-900/10 text-center space-y-4 bong-xanh">
            <div class="relative w-16 h-16 mx-auto">
                <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-3xl shadow-xs border border-emerald-200">
                    <i class="fa-solid fa-shield-halved animate-pulse"></i>
                </div>
                <div class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-white flex items-center justify-center shadow-xs">
                    <i class="fa-solid fa-circle-notch fa-spin text-emerald-700 text-sm"></i>
                </div>
            </div>
            <div class="space-y-1.5">
                <h3 class="text-base sm:text-lg font-bold text-slate-900">Đang Tiến Hành Xác Thực Trên Blockchain...</h3>
                <p class="text-xs text-slate-500 max-w-lg mx-auto leading-relaxed">
                    Hệ thống đang truy vấn dữ liệu on-chain Ethereum Sepolia, kiểm tra bằng chứng chữ ký số, tình trạng thu hồi và đối chiếu tính toàn vẹn hồ sơ của lô thuốc.
                </p>
            </div>
            <div class="flex items-center justify-center gap-2 text-[11px] text-emerald-800 font-mono font-semibold pt-1">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                Đang kết nối RPC Sepolia Node & xác minh Smart Contract...
            </div>
        </div>

        <div id="noiDungKetQuaXacThuc" class="space-y-6">

            <!-- BANNER TRẠNG THÁI CHÍNH -->
            <div id="bannerTrangThai" class="p-6 rounded-2xl border flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bong-xanh">
                <div class="flex items-center gap-4">
                    <div id="bieuTuongTrangThai" class="w-14 h-14 rounded-xl flex items-center justify-center text-3xl shrink-0"></div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span id="theTrangThaiText" class="px-3 py-1 rounded-full text-xs font-extrabold uppercase tracking-wide"></span>
                            <span id="loaiMaKiemTra" class="text-xs text-slate-500 font-semibold"></span>
                        </div>
                        <h2 id="tieuDeTrangThai" class="text-lg sm:text-xl font-bold mt-1"></h2>
                        <p id="moTaTrangThai" class="text-xs text-slate-600 mt-0.5"></p>
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    <button type="button" id="nutInKetQuaTraCuu" onclick="window.print()" class="px-4 py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs transition-all flex items-center gap-2 shrink-0 shadow-2xs">
                        <i class="fa-solid fa-print text-cyan-800"></i> In Kết Quả
                    </button>
                    <a id="nutBaoCaoNghiVan" href="{{ route('bao-cao.public.form') }}" class="px-4 py-2.5 rounded-xl border border-amber-600 text-amber-700 hover:bg-amber-50 font-bold text-xs transition-all flex items-center gap-2 shrink-0">
                        <i class="fa-solid fa-triangle-exclamation text-amber-600"></i> Báo Cáo Bất Thường
                    </a>
                </div>
            </div>

            <!-- CHI TIẾT THÔNG TIN DƯỢC PHẨM & CHỨNG THỰC ON-CHAIN -->
            <div id="khoiChiTietThuoc" class="grid grid-cols-1 md:grid-cols-3 gap-6">

                <!-- Cột trái: Thông tin Dược lý & Xuất xứ -->
                <div class="md:col-span-2 bg-white rounded-2xl border border-emerald-900/10 p-6 bong-xanh space-y-6">
                    <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                        <h3 class="font-bold text-base text-emerald-700 flex items-center gap-2">
                            <i class="fa-solid fa-pills text-cyan-800"></i> Thông Tin Sản Phẩm Dược
                        </h3>
                        <span id="soDkLuuHanh" class="text-xs font-mono font-semibold text-cyan-800 bg-cyan-50 px-2.5 py-1 rounded border border-cyan-800/15"></span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <div>
                            <span class="text-slate-500 block mb-1">Tên biệt dược:</span>
                            <span id="tenThuoc" class="font-bold text-sm text-slate-900"></span>
                        </div>
                        <div>
                            <span class="text-slate-500 block mb-1">Dạng bào chế & Hàm lượng:</span>
                            <span id="hamLuongDangBaoChe" class="font-semibold text-slate-900"></span>
                        </div>
                        <div>
                            <span class="text-slate-500 block mb-1">Hoạt chất / Thành phần chính:</span>
                            <span id="hoatChat" class="font-semibold text-slate-900"></span>
                        </div>
                        <div>
                            <span class="text-slate-500 block mb-1">Nhà sản xuất:</span>
                            <span id="tenNhaSanXuat" class="font-semibold text-slate-900"></span>
                        </div>
                        <div>
                            <span class="text-slate-500 block mb-1">Ngày sản xuất:</span>
                            <span id="ngaySanXuat" class="font-semibold text-slate-900"></span>
                        </div>
                        <div>
                            <span class="text-slate-500 block mb-1">Hạn sử dụng:</span>
                            <span id="hanSuDung" class="font-semibold text-slate-900"></span>
                        </div>
                    </div>

                    <!-- BẢN ĐỒ HÀNH TRÌNH CHUỖI CUNG ỨNG -->
                    <div class="border-t border-slate-100 pt-5 space-y-4">
                        <h4 class="font-bold text-xs uppercase tracking-wider text-slate-500">Hành Trình Chuỗi Cung Ứng (Chain of Custody)</h4>
                        <div id="dongThoiGianHanhTrinh" class="relative pl-6 border-l-2 border-emerald-900/15 space-y-6"></div>
                    </div>
                </div>

                <!-- Cột phải: Dữ liệu Bất biến On-Chain -->
                <div class="md:col-span-1 bg-white rounded-2xl border border-emerald-900/10 p-6 bong-xanh space-y-5">
                    <div class="border-b border-slate-100 pb-3 flex items-center gap-2 text-cyan-800 font-bold text-sm">
                        <i class="fa-solid fa-cube"></i> Bằng Chứng Blockchain
                    </div>

                    <div class="space-y-4 text-xs">
                        <div>
                            <span class="text-slate-500 block mb-1">Mã Lô (Batch ID Hash):</span>
                            <div id="batchIdHash" class="p-2 bg-[#f2faf5] border border-emerald-900/15 rounded font-mono text-[11px] break-all text-cyan-800 font-semibold"></div>
                        </div>
                        <div>
                            <span class="text-slate-500 block mb-1">Mã giao dịch khởi tạo (txHash):</span>
                            <div id="txHashKhoiTao" class="p-2 bg-[#f2faf5] border border-emerald-900/15 rounded font-mono text-[11px] break-all text-emerald-700 font-semibold"></div>
                        </div>
                        <div>
                            <span class="text-slate-500 block mb-1">Mạng Smart Contract:</span>
                            <span class="font-semibold text-slate-900 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Ethereum Sepolia Testnet
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-500 block mb-1">Chủ sở hữu hiện tại (on-chain):</span>
                            <div id="chainOwner" class="font-mono text-[11px] break-all text-slate-800"></div>
                        </div>
                        <div>
                            <span class="text-slate-500 block mb-1">Đối chiếu chủ sở hữu với tồn kho hệ thống:</span>
                            <span id="ownerIntegrity" class="font-bold px-2.5 py-1 rounded text-[11px] inline-block"></span>
                        </div>
                        <div>
                            <span class="text-slate-500 block mb-1">Đối chiếu hash hồ sơ sản phẩm:</span>
                            <span id="productIntegrity" class="font-bold px-2.5 py-1 rounded text-[11px] inline-block"></span>
                        </div>
                        <div>
                            <span class="text-slate-500 block mb-1">Đối chiếu hash thông tin lô (NSX, HSD, ĐVT):</span>
                            <span id="batchIntegrity" class="font-bold px-2.5 py-1 rounded text-[11px] inline-block"></span>
                        </div>
                        <div>
                            <span class="text-slate-500 block mb-1">Trạng thái lô trên blockchain:</span>
                            <span id="chainBatchStatus" class="font-semibold text-slate-900"></span>
                        </div>
                        <div>
                            <span class="text-slate-500 block mb-1">Số lượng đã bán từ lô:</span>
                            <span id="coBanLe" class="font-bold px-2 py-0.5 rounded text-[11px] inline-block"></span>
                        </div>
                    </div>

                    <div class="p-3 bg-cyan-50 rounded-xl border border-cyan-800/15 text-[11px] text-cyan-900 space-y-1">
                        <div class="font-bold flex items-center gap-1.5">
                            <i class="fa-solid fa-circle-check text-cyan-800"></i> Xác minh độc lập
                        </div>
                        <p class="leading-relaxed">Dữ liệu trên đã được đối chiếu trực tiếp từ địa chỉ Smart Contract chính thức của cơ quan quản lý dược phẩm.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection

@push('scripts')
<script src="{{ asset('assets/vendor/jsqr/jsQR.min.js') }}"></script>
<script src="{{ asset('assets/js/nguoiTieuDung/traCuuNguonGoc.js') }}"></script>
@endpush
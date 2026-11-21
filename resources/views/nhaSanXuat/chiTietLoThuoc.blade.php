@extends('layoutDungChung.master')

@section('title', 'Chi tiết lô thuốc - PharmaChain')

@section('content')
<div class="max-w-5xl mx-auto space-y-6" id="chiTietLoContainer" data-lo-id="{{ $loThuoc->id }}">

    {{-- HEADER --}}
    <div class="bg-white p-5 rounded-2xl border border-emerald-900/10 bong-xanh flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <a href="{{ route('nha-san-xuat.lo-thuoc.index') }}" class="text-slate-400 hover:text-emerald-700 transition-colors">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <h2 class="text-base font-bold text-slate-900">Chi tiết lô thuốc</h2>
            </div>
            <p class="text-xs text-slate-500 font-mono ml-7">
                Mã lô: <strong class="text-emerald-700">{{ $loThuoc->maLoNghiepVu }}</strong>
            </p>
        </div>
        <div id="badgeTrangThaiTong" class="shrink-0">
            <span class="px-2.5 py-1 bg-slate-100 text-slate-500 border border-slate-200 rounded-full text-[11px] font-bold">Đang tải...</span>
        </div>
    </div>

    {{-- 1. THÔNG TIN LÔ THUỐC (lấy từ CSDL thật) --}}
    <div class="bg-white p-6 rounded-2xl border border-emerald-900/10 bong-xanh">
        <h3 class="text-xs font-bold uppercase tracking-wide text-slate-700 border-b border-slate-100 pb-3 mb-4 flex items-center gap-2">
            <i class="fa-solid fa-box text-emerald-700"></i> Thông tin lô thuốc
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-xs">
            <div>
                <span class="text-slate-500 block mb-0.5">Sản phẩm</span>
                <span class="font-bold text-slate-900">{{ $loThuoc->sanPham->tenSanPham ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="text-slate-500 block mb-0.5">Số đăng ký</span>
                <span class="font-mono font-semibold text-slate-800">{{ $loThuoc->sanPham->soDangKy ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="text-slate-500 block mb-0.5">Ngày sản xuất</span>
                <span class="font-mono text-slate-800">
                    {{ $loThuoc->ngaySanXuat ? \Carbon\Carbon::parse($loThuoc->ngaySanXuat)->format('d/m/Y') : 'N/A' }}
                </span>
            </div>
            <div>
                <span class="text-slate-500 block mb-0.5">Hạn sử dụng</span>
                <span class="font-mono text-slate-800">
                    {{ $loThuoc->hanSuDung ? \Carbon\Carbon::parse($loThuoc->hanSuDung)->format('d/m/Y') : 'N/A' }}
                </span>
            </div>
            <div>
                <span class="text-slate-500 block mb-0.5">Tổng số lượng</span>
                <span class="font-mono font-bold text-slate-900">{{ number_format($loThuoc->soLuong ?? 0) }}</span>
            </div>
            <div>
                <span class="text-slate-500 block mb-0.5">Trạng thái nghiệp vụ</span>
                <span class="font-semibold text-slate-800">{{ $loThuoc->trangThai ?? 'CREATED' }}</span>
            </div>
        </div>
    </div>

    {{-- 2. TRẠNG THÁI BLOCKCHAIN (đọc từ bảng blockchainTransaction) --}}
    <div class="bg-white p-6 rounded-2xl border border-emerald-900/10 bong-xanh">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
            <h3 class="text-xs font-bold uppercase tracking-wide text-slate-700 flex items-center gap-2">
                <i class="fa-solid fa-link text-emerald-700"></i> Trạng thái Blockchain
            </h3>
            <button type="button" id="btnLamMoiTrangThai"
                class="px-3 py-1.5 bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 text-[11px] font-bold rounded-lg transition-all flex items-center gap-1.5">
                <i class="fa-solid fa-rotate"></i> Làm mới
            </button>
        </div>

        <div id="khuVucTrangThaiBlockchain" class="space-y-4">
            <div class="text-center py-8 text-slate-400 text-xs">
                <i class="fa-solid fa-spinner fa-spin text-lg mb-2"></i>
                <p>Đang tải trạng thái từ cơ sở dữ liệu...</p>
            </div>
        </div>
    </div>

    {{-- 3. MÃ QR ĐẠI DIỆN CHO LÔ THUỐC --}}
    <div class="bg-white p-6 rounded-2xl border border-emerald-900/10 bong-xanh">
        <h3 class="text-xs font-bold uppercase tracking-wide text-slate-700 border-b border-slate-100 pb-3 mb-4 flex items-center gap-2">
            <i class="fa-solid fa-qrcode text-emerald-700"></i> Mã QR truy xuất nguồn gốc
        </h3>
        @if($qrDaSanSang)
            <div class="flex flex-col sm:flex-row items-center gap-5">
                <div class="shrink-0 rounded-2xl border border-emerald-100 bg-white p-3 shadow-sm">
                    <img
                        src="{{ route('nha-san-xuat.lo-thuoc.qr', $loThuoc->id) }}"
                        alt="Mã QR lô {{ $loThuoc->maLoNghiepVu }}"
                        class="w-52 h-52 object-contain"
                    >
                </div>
                <div class="flex-1 space-y-3 text-xs">
                    <div>
                        <p class="font-bold text-slate-900">Mã QR đã sẵn sàng</p>
                        <p class="mt-1 text-slate-500 leading-relaxed">
                            Đây là mã QR duy nhất đại diện cho toàn bộ lô thuốc. Khi quét, hệ thống mở trang tra cứu nguồn gốc và kiểm tra dữ liệu lô trên Ethereum Sepolia.
                        </p>
                    </div>
                    <div class="font-mono text-[11px] text-slate-600 break-all">
                        Mã lô: {{ $loThuoc->maLoNghiepVu }}
                    </div>
                    <a
                        href="{{ route('nha-san-xuat.lo-thuoc.qr', ['id' => $loThuoc->id, 'download' => 1]) }}"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold transition-all"
                    >
                        <i class="fa-solid fa-download"></i> Tải mã QR về máy (.SVG)
                    </a>
                    <p class="text-[11px] text-slate-400">Có thể in ảnh SVG để dán lên bao bì hoặc tem đại diện của lô.</p>
                </div>
            </div>
        @else
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-800 flex items-start gap-2">
                <i class="fa-solid fa-clock mt-0.5"></i>
                <span>Mã QR sẽ hiển thị và cho phép tải xuống sau khi giao dịch đăng ký lô được xác nhận thành công trên Ethereum Sepolia.</span>
            </div>
        @endif
    </div>

    {{-- 4. LỊCH SỬ SỰ KIỆN --}}
    <div class="bg-white p-6 rounded-2xl border border-emerald-900/10 bong-xanh">
        <h3 class="text-xs font-bold uppercase tracking-wide text-slate-700 border-b border-slate-100 pb-3 mb-4 flex items-center gap-2">
            <i class="fa-solid fa-timeline text-emerald-700"></i> Lịch sử sự kiện
        </h3>
        <div id="timelineSuKien" class="relative pl-6 border-l-2 border-emerald-900/15 space-y-5">
            <div class="text-xs text-slate-400">Đang tải lịch sử từ cơ sở dữ liệu...</div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/nhaSanXuat/chiTietLoThuoc.js') }}"></script>
@endpush

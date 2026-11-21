@extends('layoutDungChung.master')

@section('title', 'Bán hàng theo lô - PharmaChain')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/nhaThuoc/quanLyBanHang.css') }}">
@endpush

@section('content')
<div id="banLeApp" class="max-w-7xl w-full mx-auto space-y-6"
     data-lookup-url="{{ route('nha-thuoc.api.tra-cuu-lo-ban-le') }}"
     data-stock-url="{{ route('nha-thuoc.api.ton-kho-ban-le') }}"
     data-sale-url="{{ route('nha-thuoc.api.ban-le') }}"
     data-history-url="{{ route('nha-thuoc.api.lich-su-ban-le') }}"
     data-retry-url="{{ url('/nha-thuoc/api/ban-le') }}">
    <section class="bg-white rounded-2xl border border-emerald-900/10 p-5 sm:p-6 space-y-4 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-3">
            <div>
                <h1 class="text-lg font-extrabold text-slate-900">Bán thuốc theo lô</h1>
                <p class="text-xs text-slate-500 mt-1">Một QR đại diện cho một lô. Nhập số lượng bán, không quản lý serial từng hộp.</p>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-cyan-50 text-cyan-800">Ethereum Sepolia</span>
        </div>
        <form id="formTraCuuLo" class="flex flex-col sm:flex-row gap-2">
            <input id="maQRInput" type="text" autocomplete="off" required
                   placeholder="Quét QR lô bằng máy quét hoặc nhập mã lô nghiệp vụ..."
                   class="flex-1 min-w-0 px-4 py-3 rounded-xl border border-slate-300 bg-slate-50 text-sm focus:bg-white focus:border-cyan-700 outline-none">
            <button class="px-5 py-3 rounded-xl bg-cyan-800 hover:bg-cyan-900 text-white text-sm font-bold" type="submit">
                <i class="fa-solid fa-magnifying-glass mr-1"></i> Kiểm tra lô
            </button>
            <button id="btnMoCamera" class="px-5 py-3 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-sm font-bold" type="button"><i class="fa-solid fa-camera mr-1"></i> Quét camera</button>
        </form>
        <div id="qrCameraWrap" class="hidden rounded-xl border border-slate-200 p-3 space-y-2">
            <video id="qrCameraVideo" class="w-full max-h-72 rounded-lg bg-black object-contain" playsinline muted></video>
            <button id="btnTatCamera" type="button" class="px-3 py-2 rounded-lg bg-slate-100 text-slate-700 text-xs font-bold">Tắt camera</button>
        </div>
        <div id="thongBaoBanLe" class="hidden rounded-xl p-3 text-sm" role="status"></div>
        <div id="ketQuaLo" class="hidden rounded-xl border border-slate-200 p-4 space-y-3">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div class="text-xs text-slate-500">Sản phẩm</div>
                    <h2 id="loTenSanPham" class="font-extrabold text-slate-900"></h2>
                    <div id="loMaLo" class="font-mono text-xs text-slate-500 mt-1"></div>
                </div>
                <span id="loTrangThai" class="rounded-full px-3 py-1 text-xs font-bold"></span>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                <div><div class="text-xs text-slate-500">Tồn kho</div><strong id="loTonKho"></strong></div>
                <div><div class="text-xs text-slate-500">Hạn sử dụng</div><strong id="loHanSuDung"></strong></div>
                <div><div class="text-xs text-slate-500">Số đăng ký</div><strong id="loSoDangKy"></strong></div>
                <div><div class="text-xs text-slate-500">Toàn vẹn hồ sơ</div><strong id="loIntegrity"></strong></div>
            </div>
            <ul id="loLyDo" class="text-xs text-red-700 list-disc pl-5"></ul>
            <div class="flex justify-end">
                <button id="btnThemLo" type="button" class="hidden px-4 py-2 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold">Thêm lô vào hóa đơn</button>
            </div>
        </div>
    </section>

    <section class="bg-white rounded-2xl border border-emerald-900/10 p-5 sm:p-6 space-y-4 shadow-sm">
        <div class="flex flex-wrap justify-between items-center gap-2">
            <h2 class="font-extrabold text-slate-900"><i class="fa-solid fa-cart-shopping text-cyan-800 mr-2"></i>Hóa đơn đang lập</h2>
            <span id="cartCount" class="text-xs font-bold text-cyan-800">0 lô</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-600 text-xs uppercase">
                    <tr><th class="p-3">Lô / Sản phẩm</th><th class="p-3">Tồn</th><th class="p-3">Số lượng bán</th><th class="p-3">Đơn giá</th><th class="p-3 text-right">Thành tiền</th><th class="p-3"></th></tr>
                </thead>
                <tbody id="cartRows"><tr><td colspan="6" class="p-5 text-center text-slate-400">Chưa có lô thuốc trong hóa đơn.</td></tr></tbody>
            </table>
        </div>
        <div class="flex flex-col sm:flex-row items-end justify-between gap-3 border-t border-slate-100 pt-4">
            <input id="ghiChuHoaDon" maxlength="500" placeholder="Ghi chú hóa đơn (không bắt buộc)" class="w-full sm:max-w-md px-3 py-2 rounded-lg border border-slate-300 text-sm">
            <div class="text-right">
                <div class="text-xs text-slate-500">Tổng thanh toán</div>
                <div id="cartTotal" class="text-2xl font-extrabold text-slate-900">0 ₫</div>
                <button id="btnXacNhanBan" type="button" disabled class="mt-2 px-5 py-2.5 rounded-xl bg-slate-200 text-slate-400 font-bold text-sm">Hoàn tất hóa đơn</button>
            </div>
        </div>
    </section>

    <section class="bg-white rounded-2xl border border-emerald-900/10 p-5 sm:p-6 space-y-4 shadow-sm">
        <div class="flex flex-wrap justify-between items-center gap-3">
            <h2 class="font-extrabold text-slate-900"><i class="fa-solid fa-boxes-stacked text-cyan-800 mr-2"></i>Tồn kho theo lô</h2>
            <input id="timKiemTonKho" placeholder="Tìm mã lô hoặc tên thuốc..." class="px-3 py-2 rounded-lg border border-slate-300 text-sm">
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-600 text-xs uppercase">
                    <tr><th class="p-3">Mã lô</th><th class="p-3">Sản phẩm</th><th class="p-3">Hạn dùng</th><th class="p-3 text-right">Còn trong kho</th><th class="p-3"></th></tr>
                </thead>
                <tbody id="stockRows"><tr><td colspan="5" class="p-5 text-center text-slate-400">Đang tải tồn kho...</td></tr></tbody>
            </table>
        </div>
    </section>

    <section class="bg-white rounded-2xl border border-emerald-900/10 p-5 sm:p-6 space-y-4 shadow-sm">
        <h2 class="font-extrabold text-slate-900"><i class="fa-solid fa-clock-rotate-left text-cyan-800 mr-2"></i>Lịch sử hóa đơn</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-600 text-xs uppercase">
                    <tr><th class="p-3">Mã hóa đơn</th><th class="p-3">Ngày bán</th><th class="p-3 text-right">Tổng tiền</th><th class="p-3">Đồng bộ Blockchain</th><th class="p-3"></th></tr>
                </thead>
                <tbody id="historyRows"><tr><td colspan="5" class="p-5 text-center text-slate-400">Đang tải lịch sử...</td></tr></tbody>
            </table>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/nhaThuoc/quanLyBanHang.js') }}" defer></script>
@endpush

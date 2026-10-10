@php
    $vaiTro = auth()->user()->vaiTro?->value ?? '';
    $tenVaiTro = [
        'NHA_SAN_XUAT' => 'Nhà Sản Xuất',
        'NHA_PHAN_PHOI' => 'Nhà Phân Phối',
        'NHA_THUOC' => 'Nhà Thuốc',
        'CO_QUAN_QUAN_LY' => 'Cơ Quan Quản Lý',
        'QUAN_TRI_VIEN' => 'Quản Trị Viên',
    ][$vaiTro] ?? 'Tài khoản';
@endphp

<header class="min-h-20 bg-white border-b border-emerald-900/10 px-5 sm:px-8 py-4 flex items-center justify-between gap-4 sticky top-0 z-20 shadow-sm">
    <h1 class="text-base sm:text-lg font-extrabold text-slate-800 tracking-tight">
        @yield('panelTitle', 'Không gian làm việc')
    </h1>
    <div class="flex items-center gap-3">
        <span class="hidden sm:inline-flex px-3.5 py-1.5 rounded-full text-xs font-bold border border-emerald-200 bg-emerald-50 text-emerald-800">
            <i class="fa-solid fa-user mr-1.5"></i>{{ $tenVaiTro }}
        </span>
        <span class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold text-xs">
            {{ mb_substr(auth()->user()->tenDangNhap ?? 'U', 0, 1) }}
        </span>
    </div>
</header>

@php
    $vaiTro = auth()->user()->vaiTro?->value ?? '';
    $cauHinh = [
        'NHA_SAN_XUAT' => ['ten' => 'Nhà Sản Xuất', 'icon' => 'fa-industry', 'nen' => 'bg-emerald-50', 'chu' => 'text-emerald-700'],
        'NHA_PHAN_PHOI' => ['ten' => 'Nhà Phân Phối', 'icon' => 'fa-truck-moving', 'nen' => 'bg-blue-50', 'chu' => 'text-blue-700'],
        'NHA_THUOC' => ['ten' => 'Nhà Thuốc', 'icon' => 'fa-prescription-bottle-medical', 'nen' => 'bg-cyan-50', 'chu' => 'text-cyan-700'],
        'CO_QUAN_QUAN_LY' => ['ten' => 'Cơ Quan Quản Lý', 'icon' => 'fa-building-columns', 'nen' => 'bg-indigo-50', 'chu' => 'text-indigo-700'],
        'QUAN_TRI_VIEN' => ['ten' => 'Quản Trị Viên', 'icon' => 'fa-user-shield', 'nen' => 'bg-slate-100', 'chu' => 'text-slate-700'],
    ];
    $thongTinVaiTro = $cauHinh[$vaiTro] ?? ['ten' => 'Tài khoản', 'icon' => 'fa-user', 'nen' => 'bg-slate-100', 'chu' => 'text-slate-700'];
    $toChuc = auth()->user()->toChuc ?? null;
    $cacMucMenu = match ($vaiTro) {
        'NHA_SAN_XUAT' => [
            ['route' => 'nsx.sanPham.index', 'ten' => 'Quản lý sản phẩm', 'icon' => 'fa-pills'],
            ['route' => 'nsx.loThuoc.index', 'ten' => 'Quản lý lô thuốc', 'icon' => 'fa-boxes-stacked'],
            ['route' => 'chung.chuyenGiao.di', 'ten' => 'Chuyển giao', 'icon' => 'fa-truck'],
            ['route' => 'chung.tonKho', 'ten' => 'Tồn kho', 'icon' => 'fa-warehouse'],
        ],
        'NHA_PHAN_PHOI' => [
            ['route' => 'chung.chuyenGiao.den', 'ten' => 'Lô hàng đến', 'icon' => 'fa-inbox'],
            ['route' => 'chung.chuyenGiao.di', 'ten' => 'Chuyển giao', 'icon' => 'fa-truck'],
            ['route' => 'chung.tonKho', 'ten' => 'Tồn kho', 'icon' => 'fa-warehouse'],
            ['route' => 'chung.baoCao.danhSach', 'ten' => 'Báo cáo nghi vấn', 'icon' => 'fa-file-shield'],
        ],
        'NHA_THUOC' => [
            ['route' => 'nt.tiepNhanLoHang', 'ten' => 'Tiếp nhận lô hàng', 'icon' => 'fa-inbox'],
            ['route' => 'chung.tonKho', 'ten' => 'Tồn kho', 'icon' => 'fa-warehouse'],
            ['route' => 'nt.banLe', 'ten' => 'Bán lẻ', 'icon' => 'fa-cash-register'],
            ['route' => 'chung.baoCao.danhSach', 'ten' => 'Báo cáo nghi vấn', 'icon' => 'fa-file-shield'],
        ],
        'CO_QUAN_QUAN_LY' => [
            ['route' => 'cq.duyetHoSo', 'ten' => 'Duyệt hồ sơ', 'icon' => 'fa-file-circle-check'],
            ['route' => 'cq.baoCao.danhSach', 'ten' => 'Xử lý báo cáo', 'icon' => 'fa-file-shield'],
            ['route' => 'coQuan.thongKe', 'ten' => 'Thống kê', 'icon' => 'fa-chart-pie'],
        ],
        'QUAN_TRI_VIEN' => [
            ['route' => 'admin.taiKhoan.danhSach', 'ten' => 'Quản lý tài khoản', 'icon' => 'fa-users-gear'],
            ['route' => 'admin.nhatKy.danhSach', 'ten' => 'Nhật ký hệ thống', 'icon' => 'fa-clock-rotate-left'],
        ],
        default => [],
    };
@endphp

<aside class="w-64 bg-white border-r border-emerald-900/10 flex flex-col shrink-0 sticky top-0 h-screen overflow-y-auto shadow-sm z-30">
    <a href="{{ url('/') }}" class="h-20 px-6 flex items-center gap-3 border-b border-emerald-900/10 shrink-0">
        <i class="fa-solid fa-shield-halved text-cyan-800 text-2xl"></i>
        <span class="text-lg font-extrabold text-emerald-700">PharmaChain</span>
    </a>
    <div class="p-4 m-4 rounded-xl {{ $thongTinVaiTro['nen'] }} border border-slate-200/80 space-y-2">
        <div class="text-xs font-extrabold {{ $thongTinVaiTro['chu'] }} flex items-center gap-2">
            <i class="fa-solid {{ $thongTinVaiTro['icon'] }}"></i>
            <span>{{ $thongTinVaiTro['ten'] }}</span>
        </div>
        <div class="text-[11px] text-slate-600 truncate">{{ $toChuc?->tenToChuc ?? 'Tài khoản quản trị' }}</div>
    </div>
    <nav class="px-4 pb-4 space-y-1 flex-1">
        @foreach ($cacMucMenu as $muc)
            @if (Route::has($muc['route']))
                <a href="{{ route($muc['route']) }}"
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all {{ request()->routeIs($muc['route']) ? 'bg-emerald-700 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                    <i class="fa-solid {{ $muc['icon'] }} w-5 text-center"></i>
                    <span>{{ $muc['ten'] }}</span>
                </a>
            @endif
        @endforeach
    </nav>
    <div class="p-4 border-t border-slate-100 space-y-2">
        @if (Route::has('hoSo'))
            <a href="{{ route('hoSo') }}" class="block px-3.5 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">
                <i class="fa-solid fa-user-gear w-5 text-center mr-2"></i>Hồ sơ cá nhân
            </a>
        @endif
        @if (Route::has('xacThuc.dangXuat'))
            <form method="POST" action="{{ route('xacThuc.dangXuat') }}">
                @csrf
                <button class="w-full text-left px-3.5 py-2.5 rounded-xl text-xs font-bold text-red-600 hover:bg-red-50" type="submit">
                    <i class="fa-solid fa-right-from-bracket w-5 text-center mr-2"></i>Đăng xuất
                </button>
            </form>
        @endif
    </div>
</aside>

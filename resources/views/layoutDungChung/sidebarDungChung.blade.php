<?php

use App\Enums\Role;

$user = auth()->user();
$vaiTroVal = $user ? ($user->vaiTro instanceof \BackedEnum ? $user->vaiTro->value : $user->vaiTro) : 'NHA_SAN_XUAT';

$cauHinhVaiTro = [
    'NHA_SAN_XUAT' => ['ten' => 'Nhà Sản Xuất', 'icon' => 'fa-industry', 'textClass' => 'text-emerald-700', 'bgNhanClass' => 'bg-emerald-50', 'btnClass' => 'bg-emerald-700 hover:bg-emerald-800 text-white'],
    'NHA_PHAN_PHOI' => ['ten' => 'Nhà Phân Phối', 'icon' => 'fa-truck-moving', 'textClass' => 'text-blue-700', 'bgNhanClass' => 'bg-blue-50', 'btnClass' => 'bg-blue-700 hover:bg-blue-800 text-white'],
    'NHA_THUOC' => ['ten' => 'Nhà Thuốc', 'icon' => 'fa-prescription-bottle-medical', 'textClass' => 'text-cyan-700', 'bgNhanClass' => 'bg-cyan-50', 'btnClass' => 'bg-cyan-700 hover:bg-cyan-800 text-white'],
    'QUAN_TRI_VIEN' => ['ten' => 'Quản Trị Viên', 'icon' => 'fa-user-shield', 'textClass' => 'text-slate-700', 'bgNhanClass' => 'bg-slate-100', 'btnClass' => 'bg-slate-800 hover:bg-slate-900 text-white'],
    'CO_QUAN_QUAN_LY' => ['ten' => 'Cơ Quan Quản Lý', 'icon' => 'fa-building-columns', 'textClass' => 'text-indigo-700', 'bgNhanClass' => 'bg-indigo-50', 'btnClass' => 'bg-indigo-700 hover:bg-indigo-800 text-white']
];

$role = $cauHinhVaiTro[$vaiTroVal] ?? $cauHinhVaiTro['NHA_SAN_XUAT'];
$tenToChucHienThi = $user?->toChuc?->tenToChuc ?? ($user?->tenDangNhap ?? 'Trung Tâm Quản Trị Hệ Thống');

$danhSachMenuTheoVaiTro = [
    'NHA_SAN_XUAT' => [
        ['link' => url('/nha-san-xuat/quan-ly-lo-thuoc'), 'icon' => 'fa-boxes-stacked', 'ten' => 'Quản Lý Lô Thuốc', 'pattern' => 'nha-san-xuat/quan-ly-lo-thuoc*'],
        ['link' => url('/nha-san-xuat/quan-ly-san-pham'), 'icon' => 'fa-pills', 'ten' => 'Quản Lý Sản Phẩm', 'pattern' => 'nha-san-xuat/quan-ly-san-pham*']
    ],
    'NHA_PHAN_PHOI' => [
        ['link' => url('/nha-phan-phoi/quan-ly-lo-hang'), 'icon' => 'fa-dolly', 'ten' => 'Quản Lý Lô Hàng', 'pattern' => 'nha-phan-phoi/quan-ly-lo-hang*'],
        ['link' => url('/bao-cao-nghi-van'), 'icon' => 'fa-file-shield', 'ten' => 'Báo Cáo Nghi Vấn', 'pattern' => 'bao-cao-nghi-van*']
    ],
    'NHA_THUOC' => [
        ['link' => url('/nha-thuoc/tiep-nhan-lo-hang'), 'icon' => 'fa-truck-ramp-box', 'ten' => 'Tiếp Nhận Lô Hàng', 'pattern' => 'nha-thuoc/tiep-nhan-lo-hang*'],
        ['link' => url('/nha-thuoc/quan-ly-ban-hang'), 'icon' => 'fa-cash-register', 'ten' => 'Quản Lý Bán Hàng', 'pattern' => 'nha-thuoc/quan-ly-ban-hang*'],
        ['link' => url('/bao-cao-nghi-van'), 'icon' => 'fa-file-shield', 'ten' => 'Báo Cáo Nghi Vấn', 'pattern' => 'bao-cao-nghi-van*']
    ],
    'QUAN_TRI_VIEN' => [
        ['link' => url('/quan-tri/quan-ly-tai-khoan'), 'icon' => 'fa-users-gear', 'ten' => 'Quản Lý Tài Khoản', 'pattern' => 'quan-tri/quan-ly-tai-khoan*'],
        ['link' => url('/quan-tri/nhat-ky-he-thong'), 'icon' => 'fa-clock-rotate-left', 'ten' => 'Nhật Ký Hệ Thống', 'pattern' => 'quan-tri/nhat-ky-he-thong*']
    ],
    'CO_QUAN_QUAN_LY' => [
        ['link' => url('/co-quan/phe-duyet-tai-khoan'), 'icon' => 'fa-user-check', 'ten' => 'Phê Duyệt Tổ Chức', 'pattern' => 'co-quan/phe-duyet-tai-khoan*'],
        ['link' => url('/co-quan/xet-duyet-san-pham'), 'icon' => 'fa-file-circle-check', 'ten' => 'Xét Duyệt Sản Phẩm', 'pattern' => 'co-quan/xet-duyet-san-pham*'],
        ['link' => url('/co-quan/quan-ly-quyen-han'), 'icon' => 'fa-user-slash', 'ten' => 'Quản Lý Quyền Hạn', 'pattern' => 'co-quan/quan-ly-quyen-han*'],
        ['link' => url('/co-quan/quan-ly-thu-hoi'), 'icon' => 'fa-ban', 'ten' => 'Quản Lý Thu Hồi', 'pattern' => 'co-quan/quan-ly-thu-hoi*'],
        ['link' => url('/co-quan/thong-ke-bao-cao'), 'icon' => 'fa-chart-pie', 'ten' => 'Thống Kê Báo Cáo', 'pattern' => 'co-quan/thong-ke-bao-cao*'],
        ['link' => url('/co-quan/xu-ly-nghi-van'), 'icon' => 'fa-file-shield', 'ten' => 'Xử Lý Nghi Vấn', 'pattern' => 'co-quan/xu-ly-nghi-van*']
    ]
];

$menuHienTai = $danhSachMenuTheoVaiTro[$vaiTroVal] ?? [];
?>

<aside class="w-64 bg-white border-r border-emerald-900/10 flex flex-col shrink-0 sticky top-0 h-screen overflow-y-auto z-30">
    <div class="h-20 px-6 flex items-center gap-3 border-b border-emerald-900/10 shrink-0">
        <div class="w-10 h-10 rounded-xl bg-emerald-700 flex items-center justify-center text-white text-xl shadow-md">
            <i class="fa-solid fa-cubes-stacked"></i>
        </div>
        <div>
            <span class="block text-lg font-bold text-emerald-700 leading-none">PharmaChain</span>
            <span class="block text-[11px] font-semibold text-slate-500 mt-1 uppercase tracking-wider">Cổng Doanh Nghiệp</span>
        </div>
    </div>

    <div class="p-4 m-4 rounded-xl {{ $role['bgNhanClass'] }} border border-slate-200/80 space-y-1.5 shrink-0">
        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Tài Khoản Đăng Nhập</div>
        <div class="text-xs font-extrabold {{ $role['textClass'] }} flex items-center gap-1.5 truncate">
            <i class="fa-solid {{ $role['icon'] }}"></i>
            <span>{{ $role['ten'] }}</span>
        </div>
        <div class="text-[11px] font-bold text-slate-700 truncate" title="{{ $tenToChucHienThi }}">
            {{ $tenToChucHienThi }}
        </div>
    </div>

    <nav class="flex-1 px-4 space-y-1 text-xs">
        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider px-3 mb-2">QUẢN LÝ NGHIỆP VỤ</div>

        @foreach ($menuHienTai as $menu)
        @php $dangChon = request()->is($menu['pattern']); @endphp
        <a href="{{ $menu['link'] }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-bold transition-all {{ $dangChon ? $role['btnClass'] . ' shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
            <i class="fa-solid {{ $menu['icon'] }} text-sm w-5 text-center"></i>
            <span>{{ $menu['ten'] }}</span>
        </a>
        @endforeach

        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider px-3 pt-3 mb-2">HỆ THỐNG</div>
        <a href="{{ url('/thong-tin-ca-nhan') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-bold transition-all {{ request()->is('thong-tin-ca-nhan*') ? $role['btnClass'] . ' shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
            <i class="fa-solid fa-id-card text-sm w-5 text-center"></i>
            <span>Thông Tin Cá Nhân</span>
        </a>
    </nav>

    <div class="p-4 border-t border-emerald-900/10 shrink-0">
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl border border-red-200 text-red-600 hover:bg-red-50 text-xs font-bold transition-all cursor-pointer">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> Đăng Xuất
            </button>
        </form>
    </div>
</aside>
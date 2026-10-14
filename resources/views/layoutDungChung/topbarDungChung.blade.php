<?php

use App\Enums\Role;

$taiKhoanHienTai = auth()->user();
$vaiTroVal = $taiKhoanHienTai ? ($taiKhoanHienTai->vaiTro instanceof \BackedEnum ? $taiKhoanHienTai->vaiTro->value : $taiKhoanHienTai->vaiTro) : 'NHA_SAN_XUAT';

$cauHinhBadge = [
    'NHA_SAN_XUAT' => ['ten' => 'Nhà Sản Xuất', 'icon' => 'fa-industry', 'class' => 'bg-emerald-100 text-emerald-800 border-emerald-300'],
    'NHA_PHAN_PHOI' => ['ten' => 'Nhà Phân Phối', 'icon' => 'fa-truck-moving', 'class' => 'bg-blue-100 text-blue-800 border-blue-300'],
    'NHA_THUOC' => ['ten' => 'Nhà Thuốc', 'icon' => 'fa-prescription-bottle-medical', 'class' => 'bg-cyan-100 text-cyan-800 border-cyan-300'],
    'QUAN_TRI_VIEN' => ['ten' => 'Quản Trị Viên', 'icon' => 'fa-user-shield', 'class' => 'bg-slate-200 text-slate-800 border-slate-400'],
    'CO_QUAN_QUAN_LY' => ['ten' => 'Cơ Quan Quản Lý', 'icon' => 'fa-building-columns', 'class' => 'bg-indigo-100 text-indigo-800 border-indigo-300'],
];

$badge = $cauHinhBadge[$vaiTroVal] ?? $cauHinhBadge['NHA_SAN_XUAT'];
?>
<header class="h-20 bg-white border-b border-emerald-900/10 px-8 flex items-center justify-between sticky top-0 z-20 bong-xanh">
    <div class="flex items-center gap-3">
        <h2 class="text-lg font-extrabold text-slate-800 tracking-tight">
            {{ $tieuDePhanHe ?? 'Hệ Thống Quản Trị Dược Phẩm Blockchain' }}
        </h2>
    </div>

    <!-- Thông tin tài khoản thực tế -->
    <div class="flex items-center gap-4">
        @if ($taiKhoanHienTai)
        <div class="hidden sm:flex flex-col text-right">
            <span class="text-xs font-bold text-slate-800">{{ $taiKhoanHienTai->toChuc?->tenToChuc ?? $taiKhoanHienTai->tenDangNhap }}</span>
            <span class="text-[11px] text-slate-500 font-mono">{{ $taiKhoanHienTai->email }}</span>
        </div>
        @endif

        <span class="px-3.5 py-1.5 rounded-full text-xs font-bold border {{ $badge['class'] }} flex items-center gap-1.5 shadow-2xs">
            <i class="fa-solid {{ $badge['icon'] }}"></i>
            <span>{{ $badge['ten'] }}</span>
        </span>
    </div>
</header>
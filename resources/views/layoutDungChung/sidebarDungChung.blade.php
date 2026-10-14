<?php
// 1. Lấy vai trò hiện tại từ request, mặc định là Nhà Sản Xuất nếu chưa có
$vaiTroHienTai = request()->input('vaiTro', 'NHA_SAN_XUAT');

// 2. Mảng cấu hình giao diện theo từng vai trò
$cauHinhVaiTro = [
    'NHA_SAN_XUAT' => [
        'ten' => 'Nhà Sản Xuất', 'icon' => 'fa-industry', 'tenToChucMacDinh' => 'Công ty CP Dược Phẩm Medipharco', 'textClass' => 'text-emerald-700', 'bgNhanClass' => 'bg-emerald-50', 'btnClass' => 'bg-emerald-700 hover:bg-emerald-800 text-white', 'badgeClass' => 'bg-emerald-100 text-emerald-800 border-emerald-300'
    ],
    'NHA_PHAN_PHOI' => [
        'ten' => 'Nhà Phân Phối', 'icon' => 'fa-truck-moving', 'tenToChucMacDinh' => 'Tổng Công ty Dược Việt Nam - Vinapharm', 'textClass' => 'text-blue-700', 'bgNhanClass' => 'bg-blue-50', 'btnClass' => 'bg-blue-700 hover:bg-blue-800 text-white', 'badgeClass' => 'bg-blue-100 text-blue-800 border-blue-300'
    ],
    'NHA_THUOC' => [
        'ten' => 'Nhà Thuốc', 'icon' => 'fa-prescription-bottle-medical', 'tenToChucMacDinh' => 'Nhà Thuốc PharmaCare Cần Thơ', 'textClass' => 'text-cyan-700', 'bgNhanClass' => 'bg-cyan-50', 'btnClass' => 'bg-cyan-700 hover:bg-cyan-800 text-white', 'badgeClass' => 'bg-cyan-100 text-cyan-800 border-cyan-300'
    ],
    'QUAN_TRI_VIEN' => [
        'ten' => 'Quản Trị Viên', 'icon' => 'fa-user-shield', 'tenToChucMacDinh' => 'Trung Tâm Quản Trị Hệ Thống', 'textClass' => 'text-slate-700', 'bgNhanClass' => 'bg-slate-100', 'btnClass' => 'bg-slate-800 hover:bg-slate-900 text-white', 'badgeClass' => 'bg-slate-200 text-slate-800 border-slate-400'
    ],
    'CO_QUAN_QUAN_LY' => [
        'ten' => 'Cơ Quan Quản Lý', 'icon' => 'fa-building-columns', 'tenToChucMacDinh' => 'Cục Quản Lý Dược - Bộ Y Tế', 'textClass' => 'text-indigo-700', 'bgNhanClass' => 'bg-indigo-50', 'btnClass' => 'bg-indigo-700 hover:bg-indigo-800 text-white', 'badgeClass' => 'bg-indigo-100 text-indigo-800 border-indigo-300'
    ]
];

$role = $cauHinhVaiTro[$vaiTroHienTai] ?? $cauHinhVaiTro['NHA_SAN_XUAT'];

// 3. Danh sách menu sử dụng hoàn toàn URL Laravel Route kèm giữ lại tham số vai trò để test
$danhSachMenuTheoVaiTro = [
    'NHA_SAN_XUAT' => [
        ['link' => url('/nha-san-xuat/quan-ly-lo-thuoc?vaiTro=' . $vaiTroHienTai), 'icon' => 'fa-boxes-stacked', 'ten' => 'Quản Lý Lô Thuốc', 'pattern' => 'nha-san-xuat/quan-ly-lo-thuoc'],
        ['link' => url('/nha-san-xuat/quan-ly-san-pham?vaiTro=' . $vaiTroHienTai), 'icon' => 'fa-pills', 'ten' => 'Quản Lý Sản Phẩm', 'pattern' => 'nha-san-xuat/quan-ly-san-pham']
    ],
    'NHA_PHAN_PHOI' => [
        ['link' => url('/nha-phan-phoi/quan-ly-lo-hang?vaiTro=' . $vaiTroHienTai), 'icon' => 'fa-dolly', 'ten' => 'Quản Lý Lô Hàng', 'pattern' => 'nha-phan-phoi/quan-ly-lo-hang'],
        ['link' => url('/bao-cao-nghi-van?vaiTro=' . $vaiTroHienTai), 'icon' => 'fa-file-shield', 'ten' => 'Báo Cáo Nghi Vấn', 'pattern' => 'bao-cao-nghi-van']
    ],
    'NHA_THUOC' => [
        ['link' => url('/nha-thuoc/tiep-nhan-lo-hang?vaiTro=' . $vaiTroHienTai), 'icon' => 'fa-truck-ramp-box', 'ten' => 'Tiếp Nhận Lô Hàng', 'pattern' => 'nha-thuoc/tiep-nhan-lo-hang'],
        ['link' => url('/nha-thuoc/quan-ly-ban-hang?vaiTro=' . $vaiTroHienTai), 'icon' => 'fa-cash-register', 'ten' => 'Quản Lý Bán Hàng', 'pattern' => 'nha-thuoc/quan-ly-ban-hang'],
        ['link' => url('/bao-cao-nghi-van?vaiTro=' . $vaiTroHienTai), 'icon' => 'fa-file-shield', 'ten' => 'Báo Cáo Nghi Vấn', 'pattern' => 'bao-cao-nghi-van']
    ],
    'QUAN_TRI_VIEN' => [
        ['link' => url('/quan-tri/quan-ly-tai-khoan?vaiTro=' . $vaiTroHienTai), 'icon' => 'fa-users-gear', 'ten' => 'Quản Lý Tài Khoản', 'pattern' => 'quan-tri/quan-ly-tai-khoan'],
        ['link' => url('/quan-tri/nhat-ky-he-thong?vaiTro=' . $vaiTroHienTai), 'icon' => 'fa-clock-rotate-left', 'ten' => 'Nhật Ký Hệ Thống', 'pattern' => 'quan-tri/nhat-ky-he-thong']
    ],
    'CO_QUAN_QUAN_LY' => [
        ['link' => url('/co-quan/phe-duyet-tai-khoan?vaiTro=' . $vaiTroHienTai), 'icon' => 'fa-user-check', 'ten' => 'Phê Duyệt Tổ Chức', 'pattern' => 'co-quan/phe-duyet-tai-khoan'],
        ['link' => url('/co-quan/xet-duyet-san-pham?vaiTro=' . $vaiTroHienTai), 'icon' => 'fa-file-circle-check', 'ten' => 'Xét Duyệt Sản Phẩm', 'pattern' => 'co-quan/xet-duyet-san-pham'],
        ['link' => url('/co-quan/quan-ly-quyen-han?vaiTro=' . $vaiTroHienTai), 'icon' => 'fa-user-slash', 'ten' => 'Quản Lý Quyền Hạn', 'pattern' => 'co-quan/quan-ly-quyen-han'],
        ['link' => url('/co-quan/quan-ly-thu-hoi?vaiTro=' . $vaiTroHienTai), 'icon' => 'fa-ban', 'ten' => 'Quản Lý Thu Hồi', 'pattern' => 'co-quan/quan-ly-thu-hoi'],
        ['link' => url('/co-quan/thong-ke-bao-cao?vaiTro=' . $vaiTroHienTai), 'icon' => 'fa-chart-pie', 'ten' => 'Thống Kê Báo Cáo', 'pattern' => 'co-quan/thong-ke-bao-cao'],
        ['link' => url('/co-quan/xu-ly-nghi-van?vaiTro=' . $vaiTroHienTai), 'icon' => 'fa-file-shield', 'ten' => 'Xử Lý Nghi Vấn', 'pattern' => 'co-quan/xu-ly-nghi-van']
    ]
];

$menuHienTai = $danhSachMenuTheoVaiTro[$vaiTroHienTai] ?? [];
?>

<aside class="w-64 bg-white border-r border-emerald-900/10 flex flex-col shrink-0 sticky top-0 h-screen overflow-y-auto z-30">
    <!-- Logo -->
    <div class="h-20 px-6 flex items-center gap-3 border-b border-emerald-900/10 shrink-0">
        <div class="w-10 h-10 rounded-xl bg-emerald-700 flex items-center justify-center text-white text-xl shadow-md">
            <i class="fa-solid fa-cubes-stacked"></i>
        </div>
        <div>
            <span class="block text-lg font-bold text-emerald-700 leading-none">PharmaChain</span>
            <span class="block text-[11px] font-semibold text-slate-500 mt-1 uppercase tracking-wider">Cổng Doanh Nghiệp</span>
        </div>
    </div>

    <!-- Thông tin vai trò -->
    <div class="p-4 m-4 rounded-xl {{ $role['bgNhanClass'] }} border border-slate-200/80 space-y-1.5 shrink-0">
        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Vai Trò Đăng Nhập</div>
        <div class="text-xs font-extrabold {{ $role['textClass'] }} flex items-center gap-1.5 truncate">
            <i class="fa-solid {{ $role['icon'] }}"></i>
            <span>{{ $role['ten'] }}</span>
        </div>
        <div class="text-[11px] font-bold text-slate-700 truncate">
            {{ $role['tenToChucMacDinh'] }}
        </div>
    </div>

    <!-- Danh sách menu điều hướng -->
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
        <a href="{{ url('/thong-tin-ca-nhan?vaiTro=' . $vaiTroHienTai) }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-bold transition-all {{ request()->is('thong-tin-ca-nhan') ? $role['btnClass'] . ' shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
            <i class="fa-solid fa-id-card text-sm w-5 text-center"></i>
            <span>Thông Tin Cá Nhân</span>
        </a>
    </nav>

    <!-- Đăng xuất -->
    <div class="p-4 border-t border-emerald-900/10 shrink-0">
        <a href="{{ url('/dang-nhap') }}" class="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl border border-red-200 text-red-600 hover:bg-red-50 text-xs font-bold transition-all">
            <i class="fa-solid fa-arrow-right-from-bracket"></i> Đăng Xuất
        </a>
    </div>
</aside>
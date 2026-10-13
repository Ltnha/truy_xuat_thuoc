<?php
if (!isset($vaiTroHienTai)) {
    $vaiTroHienTai = 'NHA_SAN_XUAT';
}

$cauHinhVaiTro = [
    'NHA_SAN_XUAT' => [
        'ten' => 'Nhà Sản Xuất',
        'icon' => 'fa-industry',
        'tenToChucMacDinh' => 'Công ty CP Dược Phẩm Medipharco',
        'textClass' => 'text-emerald-700',
        'bgNhanClass' => 'bg-emerald-50',
        'btnClass' => 'bg-emerald-700 hover:bg-emerald-800 text-white',
        'badgeClass' => 'bg-emerald-100 text-emerald-800 border-emerald-300'
    ],
    'NHA_PHAN_PHOI' => [
        'ten' => 'Nhà Phân Phối',
        'icon' => 'fa-truck-moving',
        'tenToChucMacDinh' => 'Tổng Công ty Dược Việt Nam - Vinapharm',
        'textClass' => 'text-blue-700',
        'bgNhanClass' => 'bg-blue-50',
        'btnClass' => 'bg-blue-700 hover:bg-blue-800 text-white',
        'badgeClass' => 'bg-blue-100 text-blue-800 border-blue-300'
    ],
    'NHA_THUOC' => [
        'ten' => 'Nhà Thuốc',
        'icon' => 'fa-prescription-bottle-medical',
        'tenToChucMacDinh' => 'Nhà Thuốc PharmaCare Cần Thơ',
        'textClass' => 'text-cyan-700',
        'bgNhanClass' => 'bg-cyan-50',
        'btnClass' => 'bg-cyan-700 hover:bg-cyan-800 text-white',
        'badgeClass' => 'bg-cyan-100 text-cyan-800 border-cyan-300'
    ],
    'QUAN_TRI_VIEN' => [
        'ten' => 'Quản Trị Viên',
        'icon' => 'fa-user-shield',
        'tenToChucMacDinh' => 'Trung Tâm Quản Trị Hệ Thống',
        'textClass' => 'text-slate-700',
        'bgNhanClass' => 'bg-slate-100',
        'btnClass' => 'bg-slate-800 hover:bg-slate-900 text-white',
        'badgeClass' => 'bg-slate-200 text-slate-800 border-slate-400'
    ],
    'CO_QUAN_QUAN_LY' => [
        'ten' => 'Cơ Quan Quản Lý',
        'icon' => 'fa-building-columns',
        'tenToChucMacDinh' => 'Cục Quản Lý Dược - Bộ Y Tế',
        'textClass' => 'text-indigo-700',
        'bgNhanClass' => 'bg-indigo-50',
        'btnClass' => 'bg-indigo-700 hover:bg-indigo-800 text-white',
        'badgeClass' => 'bg-indigo-100 text-indigo-800 border-indigo-300'
    ]
];

$role = isset($cauHinhVaiTro[$vaiTroHienTai]) ? $cauHinhVaiTro[$vaiTroHienTai] : $cauHinhVaiTro['NHA_SAN_XUAT'];

$danhSachMenuTheoVaiTro = [
    'NHA_SAN_XUAT' => [
        ['link' => '../nhaSanXuat/quanLyLoThuoc.php', 'icon' => 'fa-boxes-stacked', 'ten' => 'Quản Lý Lô Thuốc', 'file' => 'quanLyLoThuoc.php'],
        ['link' => '../nhaSanXuat/quanLySanPham.php', 'icon' => 'fa-pills', 'ten' => 'Quản Lý Sản Phẩm', 'file' => 'quanLySanPham.php']
    ],
    'NHA_PHAN_PHOI' => [
        ['link' => '../nhaPhanPhoi/quanLyLoHang.php', 'icon' => 'fa-dolly', 'ten' => 'Quản Lý Lô Hàng', 'file' => 'quanLyLoHang.php'],
        ['link' => '../nhaPhanPhoi/baoCaoNghiVan.php', 'icon' => 'fa-file-shield', 'ten' => 'Báo Cáo Nghi Vấn', 'file' => 'baoCaoNghiVan.php']
    ],
    'NHA_THUOC' => [
        ['link' => '../nhaThuoc/tiepNhanLoHang.php', 'icon' => 'fa-truck-ramp-box', 'ten' => 'Tiếp Nhận Lô Hàng', 'file' => 'tiepNhanLoHang.php'],
        ['link' => '../nhaThuoc/quanLyBanHang.php', 'icon' => 'fa-cash-register', 'ten' => 'Quản Lý Bán Hàng', 'file' => 'quanLyBanHang.php'],
        ['link' => '../nhaThuoc/baoCaoNghiVan.php', 'icon' => 'fa-file-shield', 'ten' => 'Báo Cáo Nghi Vấn', 'file' => 'baoCaoNghiVan.php']
    ],
    'QUAN_TRI_VIEN' => [
        ['link' => '../quanTriVien/quanLyTaiKhoan.php', 'icon' => 'fa-users-gear', 'ten' => 'Quản Lý Tài Khoản', 'file' => 'quanLyTaiKhoan.php'],
        ['link' => '../quanTriVien/nhatKyHeThong.php', 'icon' => 'fa-clock-rotate-left', 'ten' => 'Nhật Ký Hệ Thống', 'file' => 'nhatKyHeThong.php']
    ],
    'CO_QUAN_QUAN_LY' => [
        ['link' => '../coQuanQuanLy/pheDuyetTaiKhoanToChuc.php', 'icon' => 'fa-user-check', 'ten' => 'Phê Duyệt Tổ Chức', 'file' => 'pheDuyetTaiKhoanToChuc.php'],
        ['link' => '../coQuanQuanLy/xetDuyetSanPham.php', 'icon' => 'fa-file-circle-check', 'ten' => 'Xét Duyệt Sản Phẩm', 'file' => 'xetDuyetSanPham.php'],
        ['link' => '../coQuanQuanLy/quanLyQuyenHan.php', 'icon' => 'fa-user-slash', 'ten' => 'Quản Lý Quyền Hạn', 'file' => 'quanLyQuyenHan.php'],
        ['link' => '../coQuanQuanLy/quanLyThuHoi.php', 'icon' => 'fa-ban', 'ten' => 'Quản Lý Thu Hồi', 'file' => 'quanLyThuHoi.php'],
        ['link' => '../coQuanQuanLy/thongKeBaoCao.php', 'icon' => 'fa-chart-pie', 'ten' => 'Thống Kê Báo Cáo', 'file' => 'thongKeBaoCao.php'],
        ['link' => '../coQuanQuanLy/xuLyNghiVan.php', 'icon' => 'fa-file-shield', 'ten' => 'Xử Lý Nghi Vấn', 'file' => 'xuLyNghiVan.php']
    ]
];

$menuHienTai = isset($danhSachMenuTheoVaiTro[$vaiTroHienTai]) ? $danhSachMenuTheoVaiTro[$vaiTroHienTai] : [];
$fileHienTai = basename($_SERVER['PHP_SELF']);
?>

<aside class="w-64 bg-white border-r border-emerald-900/10 flex flex-col shrink-0 sticky top-0 h-screen overflow-y-auto bong-xanh z-30 self-start">
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

    <!-- Thẻ vai trò đầu Sidebar -->
    <div class="p-4 m-4 rounded-xl <?php echo $role['bgNhanClass']; ?> border border-slate-200/80 space-y-1.5 shrink-0">
        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Vai Trò Đăng Nhập</div>
        <div class="text-xs font-extrabold <?php echo $role['textClass']; ?> flex items-center gap-1.5 truncate">
            <i class="fa-solid <?php echo $role['icon']; ?>"></i>
            <span><?php echo $role['ten']; ?></span>
        </div>
        <div class="text-[11px] font-bold text-slate-700 truncate">
            <?php echo $role['tenToChucMacDinh']; ?>
        </div>

        <?php if ($vaiTroHienTai !== 'QUAN_TRI_VIEN') { ?>
            <div class="flex items-center gap-1.5 text-[10px] text-slate-500 font-mono pt-0.5 border-t border-slate-200/60">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Ví: 0x89a...F2b1</span>
            </div>
        <?php } else { ?>
            <div class="text-[10px] text-slate-400 italic pt-0.5 border-t border-slate-200/60">
                Quản trị kỹ thuật Off-chain
            </div>
        <?php } ?>
    </div>

    <!-- Danh sách menu: ĐỒNG BỘ 2 NHÓM "QUẢN LÝ NGHIỆP VỤ" VÀ "HỆ THỐNG" -->
    <nav class="flex-1 px-4 space-y-1 text-xs">
        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider px-3 mb-2">QUẢN LÝ NGHIỆP VỤ</div>

        <?php foreach ($menuHienTai as $menu) {
            $dangChon = ($fileHienTai == $menu['file']);
        ?>
            <a href="<?php echo $menu['link']; ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-bold transition-all <?php echo $dangChon ? $role['btnClass'] . ' shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'; ?>">
                <i class="fa-solid <?php echo $menu['icon']; ?> text-sm w-5 text-center"></i>
                <span><?php echo $menu['ten']; ?></span>
            </a>
        <?php } ?>

        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider px-3 pt-3 mb-2">HỆ THỐNG</div>

        <a href="../trangDungChung/thongTinCaNhan.php?vaiTro=<?php echo $vaiTroHienTai; ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-bold transition-all <?php echo ($fileHienTai == 'thongTinCaNhan.php') ? $role['btnClass'] . ' shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'; ?>">
            <i class="fa-solid fa-id-card text-sm w-5 text-center"></i>
            <span>Thông Tin Cá Nhân</span>
        </a>
    </nav>

    <!-- Nút Đăng xuất -->
    <div class="p-4 border-t border-emerald-900/10 shrink-0">
        <a href="../nguoiTieuDung/dangNhap.php" class="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl border border-red-200 text-red-600 hover:bg-red-50 text-xs font-bold transition-all">
            <i class="fa-solid fa-arrow-right-from-bracket"></i> Đăng Xuất
        </a>
    </div>
</aside>
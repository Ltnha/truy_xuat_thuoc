<header class="h-20 bg-white border-b border-emerald-900/10 px-8 flex items-center justify-between sticky top-0 z-20 bong-xanh">
    <div class="flex items-center gap-3">
        <h2 class="text-lg font-extrabold text-slate-800 tracking-tight">
            <?php echo isset($tieuDePhanHe) ? $tieuDePhanHe : 'Hồ Sơ & Tài Khoản Cá Nhân'; ?>
        </h2>
    </div>

    <!-- Nhãn vai trò & Switcher thử nghiệm -->
    <div class="flex items-center gap-4">
        <!-- Chuyển vai trò test giao diện -->
        <div class="hidden sm:flex items-center gap-2 text-xs text-slate-500">
            <span>Thử vai trò:</span>
            <select onchange="location.href='thongTinCaNhan.php?vaiTro=' + this.value" class="px-2.5 py-1.5 bg-nenChinh border border-slate-200 rounded-lg font-semibold text-slate-700 outline-none text-xs cursor-pointer">
                <option value="NHA_SAN_XUAT" <?php echo $vaiTroHienTai == 'NHA_SAN_XUAT' ? 'selected' : ''; ?>>Nhà sản xuất (Xanh lá)</option>
                <option value="NHA_PHAN_PHOI" <?php echo $vaiTroHienTai == 'NHA_PHAN_PHOI' ? 'selected' : ''; ?>>Nhà phân phối (Xanh dương)</option>
                <option value="NHA_THUOC" <?php echo $vaiTroHienTai == 'NHA_THUOC' ? 'selected' : ''; ?>>Nhà thuốc (Xanh ngọc)</option>
                <option value="QUAN_TRI_VIEN" <?php echo $vaiTroHienTai == 'QUAN_TRI_VIEN' ? 'selected' : ''; ?>>Quản trị viên (Xám đậm)</option>
                <option value="CO_QUAN_QUAN_LY" <?php echo $vaiTroHienTai == 'CO_QUAN_QUAN_LY' ? 'selected' : ''; ?>>Cơ quan quản lý (Xanh tím/Navy)</option>
            </select>
        </div>

        <!-- Nhãn vai trò trên Topbar với màu nhấn -->
        <span class="px-3.5 py-1.5 rounded-full text-xs font-bold border <?php echo $role['badgeClass']; ?> flex items-center gap-1.5 shadow-2xs">
            <i class="fa-solid <?php echo $role['icon']; ?>"></i>
            <span><?php echo $role['ten']; ?></span>
        </span>
    </div>
</header>
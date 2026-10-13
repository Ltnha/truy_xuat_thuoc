<?php
// Mô phỏng vai trò nhận được từ Session đăng nhập (Mặc định: NHA_SAN_XUAT)
$vaiTroHienTai = isset($_GET['vaiTro']) ? $_GET['vaiTro'] : 'NHA_SAN_XUAT';

// Cấu hình màu nhấn và vai trò
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

// Tiêu đề phân hệ gọn gàng (Đã bỏ hậu tố vai trò thừa)
$tieuDePhanHe = "Hồ Sơ & Tài Khoản Cá Nhân";

// Cờ điều khiển hiển thị theo biến thể vai trò
$coThePhapLy = in_array($vaiTroHienTai, ['NHA_SAN_XUAT', 'NHA_PHAN_PHOI', 'NHA_THUOC']);
$coDiaChiVi = in_array($vaiTroHienTai, ['NHA_SAN_XUAT', 'NHA_PHAN_PHOI', 'NHA_THUOC', 'CO_QUAN_QUAN_LY']);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hồ Sơ & Tài Khoản Cá Nhân – PharmaChain</title>

    <!-- Google Font Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        nenChinh: '#f2faf5',
                    }
                }
            }
        }
    </script>

    <link rel="stylesheet" href="thongTinCaNhan.css">
</head>

<body class="bg-nenChinh text-slate-900 font-sans min-h-screen flex antialiased">

    <!-- SIDEBAR DÙNG CHUNG CỐ ĐỊNH -->
    <?php include_once 'layoutDungChung/sidebarDungChung.php'; ?>

    <!-- KHU VỰC NỘI DUNG CHÍNH -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- TOPBAR DÙNG CHUNG -->
        <?php include_once 'layoutDungChung/topbarDungChung.php'; ?>

        <main class="flex-1 p-6 sm:p-8 space-y-6 max-w-5xl w-full mx-auto">

            <!-- ══════════════════════════════════════════════════════════ -->
            <!-- PHẦN 1: THÔNG TIN LIÊN HỆ & TÀI KHOẢN (TẤT CẢ VAI TRÒ)   -->
            <!-- ══════════════════════════════════════════════════════════ -->
            <section class="bg-white rounded-2xl border border-emerald-900/10 p-6 sm:p-8 bong-xanh space-y-6">

                <!-- TIÊU ĐỀ ĐÃ CĂN CHỈNH GỌN GÀNG -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
                    <div class="flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-xl <?php echo $role['bgNhanClass']; ?> <?php echo $role['textClass']; ?> flex items-center justify-center text-xl shrink-0 shadow-2xs border border-slate-200/60">
                            <i class="fa-solid fa-address-card"></i>
                        </div>
                        <h3 class="text-base sm:text-lg font-bold text-slate-900 tracking-tight">
                            Thông Tin Liên Hệ & Tài Khoản
                        </h3>
                    </div>

                    <!-- Nút Mở modal Đổi Mật Khẩu -->
                    <button type="button" id="nutMoModalDoiPass" class="px-5 py-2.5 rounded-xl text-xs font-bold transition-all shadow-md flex items-center justify-center gap-2 shrink-0 <?php echo $role['btnClass']; ?>">
                        <i class="fa-solid fa-key"></i> Đổi Mật Khẩu
                    </button>
                </div>

                <!-- Lưới thông tin liên hệ và hai tầng trạng thái -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                    <div class="p-4 rounded-xl bg-nenChinh border border-emerald-900/10 space-y-1">
                        <span class="text-slate-500 font-medium block">Tên đăng nhập:</span>
                        <span class="font-bold text-sm text-slate-900 font-mono">admin_user</span>
                    </div>

                    <div class="p-4 rounded-xl bg-nenChinh border border-emerald-900/10 space-y-1">
                        <span class="text-slate-500 font-medium block">Email nhận thông báo:</span>
                        <span class="font-bold text-sm text-slate-900">contact@organization.vn</span>
                    </div>

                    <div class="p-4 rounded-xl bg-nenChinh border border-emerald-900/10 space-y-1">
                        <span class="text-slate-500 font-medium block">Số điện thoại liên hệ:</span>
                        <span class="font-bold text-sm text-slate-900 font-mono">0292 3888 999</span>
                    </div>

                    <!-- TẦNG TRẠNG THÁI 1: Trạng thái tài khoản (taiKhoan.trangThai) -->
                    <div class="p-4 rounded-xl bg-nenChinh border border-emerald-900/10 space-y-1.5">
                        <span class="text-slate-500 font-medium block">Trạng thái tài khoản:</span>
                        <span class="inline-flex items-center gap-1.5 font-bold text-emerald-700 bg-emerald-100/90 px-2.5 py-1 rounded-full text-[11px] border border-emerald-300">
                            <i class="fa-solid fa-circle-check"></i> HOẠT ĐỘNG (ACTIVE)
                        </span>
                        <div class="text-[10px] text-slate-400">Kiểm soát khả năng đăng nhập hệ thống</div>
                    </div>

                    <!-- TẦNG TRẠNG THÁI 2: Trạng thái tổ chức (toChuc.trangThaiPheDuyet) -->
                    <?php if ($coThePhapLy) { ?>
                        <div class="p-4 rounded-xl bg-nenChinh border border-emerald-900/10 space-y-1.5 sm:col-span-2">
                            <span class="text-slate-500 font-medium block">Trạng thái phê duyệt tổ chức:</span>
                            <span class="inline-flex items-center gap-1.5 font-bold text-cyan-800 bg-cyan-100/90 px-2.5 py-1 rounded-full text-[11px] border border-cyan-300">
                                <i class="fa-solid fa-shield-check"></i> ĐÃ DUYỆT (DA_DUYET)
                            </span>
                            <div class="text-[10px] text-slate-500">
                                Kiểm soát quyền thực hiện nghiệp vụ (tạo lô, chuyển giao, bán lẻ trên chuỗi cung ứng)
                            </div>
                        </div>
                    <?php } else { ?>
                        <div class="p-4 rounded-xl bg-nenChinh border border-emerald-900/10 space-y-1.5 sm:col-span-2">
                            <span class="text-slate-500 font-medium block">Đơn vị quản lý:</span>
                            <span class="font-bold text-slate-800 text-xs">Cơ quan / Quản trị nội bộ hệ thống</span>
                        </div>
                    <?php } ?>

                    <!-- ĐỊA CHỈ VÍ BLOCKCHAIN (CUSTODIAL WALLET) -->
                    <?php if ($coDiaChiVi) { ?>
                        <div class="sm:col-span-3 p-4 rounded-xl bg-nenChinh border border-emerald-900/10 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-600 font-bold block flex items-center gap-1.5">
                                    <i class="fa-solid fa-wallet text-cyan-800"></i> Địa chỉ ví Blockchain ký giao dịch (Custodial Wallet):
                                </span>
                                <button type="button" id="nutSaoChepVi" class="px-2.5 py-1 bg-white hover:bg-slate-100 border border-slate-300 text-slate-700 rounded-lg text-[11px] font-bold transition-all shadow-2xs flex items-center gap-1">
                                    <i class="fa-regular fa-copy"></i> <span>Sao chép ví</span>
                                </button>
                            </div>
                            <div id="giaTriDiaChiVi" class="font-mono font-bold text-xs text-cyan-800 break-all select-all p-2.5 bg-white rounded-lg border border-slate-200">
                                0x89aF2b1C35E479a02FbE4A5cD6E7f8A9b0C1D2E3
                            </div>
                            <p class="text-[11px] text-slate-500 italic">
                                * Ghi chú: Khóa riêng do hệ thống lưu trữ bảo mật (Custodial Wallet), người dùng không cần quản lý ví.
                            </p>
                        </div>
                    <?php } ?>
                </div>
            </section>

            <!-- ══════════════════════════════════════════════════════════ -->
            <!-- PHẦN 2: THÔNG TIN PHÁP LÝ TỔ CHỨC (CHỈ ĐỌC - READ ONLY)    -->
            <!-- (Chỉ hiển thị cho Nhà sản xuất, Nhà phân phối, Nhà thuốc)  -->
            <!-- ══════════════════════════════════════════════════════════ -->
            <?php if ($coThePhapLy) { ?>
                <section class="bg-white rounded-2xl border border-emerald-900/10 p-6 sm:p-8 bong-xanh space-y-6">

                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
                        <div class="flex items-center gap-3.5">
                            <div class="w-11 h-11 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center text-xl shrink-0 border border-slate-200/80">
                                <i class="fa-solid fa-building-shield"></i>
                            </div>
                            <h3 class="text-base sm:text-lg font-bold text-slate-900 tracking-tight">
                                Thông Tin Định Danh Pháp Lý Tổ Chức
                            </h3>
                        </div>

                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200 self-start sm:self-auto">
                            <i class="fa-solid fa-lock mr-1"></i> Bất biến (Chỉ đọc)
                        </span>
                    </div>

                    <!-- CÂU CẢNH BÁO ĐÃ SỬA CHUẨN XÁC KIẾN TRÚC OFF-CHAIN -->
                    <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-900 flex items-start gap-3 leading-relaxed">
                        <i class="fa-solid fa-circle-info text-amber-600 text-base mt-0.5 shrink-0"></i>
                        <div>
                            Thông tin định danh pháp lý đã được Cơ quan quản lý thẩm duyệt và không thể chỉnh sửa trực tuyến. Nếu cần thay đổi (gia hạn giấy phép, đổi địa chỉ...), vui lòng liên hệ trực tiếp Cơ quan quản lý để xử lý ngoài hệ thống.
                        </div>
                    </div>

                    <!-- CÁC TRƯỜNG CHỈ ĐỌC (DISABLED / READ-ONLY) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <div class="sm:col-span-2 space-y-1">
                            <label class="text-slate-500 font-semibold">Tên tổ chức / doanh nghiệp:</label>
                            <input type="text" value="<?php echo $role['tenToChucMacDinh']; ?>" readonly disabled class="w-full px-4 py-2.5 bg-slate-100/80 border border-slate-200 rounded-xl font-bold text-slate-800 cursor-not-allowed">
                        </div>

                        <div class="space-y-1">
                            <label class="text-slate-500 font-semibold">Mã số thuế:</label>
                            <input type="text" value="0102345678" readonly disabled class="w-full px-4 py-2.5 bg-slate-100/80 border border-slate-200 rounded-xl font-mono font-bold text-slate-800 cursor-not-allowed">
                        </div>

                        <div class="space-y-1">
                            <label class="text-slate-500 font-semibold">Số giấy phép kinh doanh dược:</label>
                            <input type="text" value="123/ĐKKDD-BYT" readonly disabled class="w-full px-4 py-2.5 bg-slate-100/80 border border-slate-200 rounded-xl font-mono font-bold text-slate-800 cursor-not-allowed">
                        </div>

                        <div class="space-y-1">
                            <label class="text-slate-500 font-semibold">Cơ quan cấp phép:</label>
                            <input type="text" value="Cục Quản lý Dược - Bộ Y Tế" readonly disabled class="w-full px-4 py-2.5 bg-slate-100/80 border border-slate-200 rounded-xl font-semibold text-slate-800 cursor-not-allowed">
                        </div>

                        <div class="space-y-1">
                            <label class="text-slate-500 font-semibold">Thời hạn hiệu lực giấy phép:</label>
                            <input type="text" value="15/01/2024 — 15/01/2029" readonly disabled class="w-full px-4 py-2.5 bg-slate-100/80 border border-slate-200 rounded-xl font-mono font-semibold text-slate-800 cursor-not-allowed">
                        </div>

                        <div class="sm:col-span-2 space-y-1">
                            <label class="text-slate-500 font-semibold">Địa chỉ trụ sở pháp lý:</label>
                            <input type="text" value="Số 123 Đường Nguyễn Trãi, Phường An Bình, Quận Ninh Kiều, TP. Cần Thơ" readonly disabled class="w-full px-4 py-2.5 bg-slate-100/80 border border-slate-200 rounded-xl font-medium text-slate-800 cursor-not-allowed">
                        </div>

                        <!-- LIÊN KẾT XEM HỒ SƠ ĐĂNG KÝ ĐÃ NỘP (hoSoDinhKemDangKy) -->
                        <div class="sm:col-span-2 pt-2 border-t border-slate-100 flex items-center justify-between">
                            <span class="text-slate-500 font-medium">Hồ sơ minh chứng đã lưu:</span>
                            <a href="javascript:void(0)" onclick="alert('Mở xem bản scan giấy phép kinh doanh dược đã thẩm duyệt (giay_phep_kinh_doanh_duoc.pdf)')" class="inline-flex items-center gap-1.5 text-cyan-800 hover:text-cyan-950 font-bold hover:underline">
                                <i class="fa-solid fa-file-pdf text-red-500 text-sm"></i> Xem hồ sơ đăng ký (Bản scan giấy phép)
                            </a>
                        </div>
                    </div>

                </section>
            <?php } ?>

        </main>
    </div>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- MODAL CHUYÊN BIỆT: CHỈ ĐỔI MẬT KHẨU                      -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <div id="modalDoiMatKhau" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden bg-black/60">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 sm:p-8 space-y-5 bong-xanh">

            <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-key <?php echo $role['textClass']; ?>"></i> Đổi Mật Khẩu Đăng Nhập
                </h3>
                <button type="button" id="nutDongModalDoiPass" class="text-slate-400 hover:text-slate-700 text-lg">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Form chuyên biệt chỉ đổi mật khẩu -->
            <form id="formDoiMatKhau" class="space-y-4 text-xs">
                <div class="space-y-1">
                    <label for="matKhauHienTai" class="font-bold text-slate-700">Mật khẩu hiện tại <span class="text-red-500">*</span></label>
                    <input type="password" id="matKhauHienTai" required placeholder="Nhập mật khẩu đang dùng..." class="w-full px-3.5 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-slate-900 focus:bg-white focus:border-emerald-700 outline-none">
                </div>

                <div class="space-y-1">
                    <label for="matKhauMoi" class="font-bold text-slate-700">Mật khẩu mới <span class="text-red-500">*</span></label>
                    <input type="password" id="matKhauMoi" required minlength="8" placeholder="Tối thiểu 8 ký tự..." class="w-full px-3.5 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-slate-900 focus:bg-white focus:border-emerald-700 outline-none">
                </div>

                <div class="space-y-1">
                    <label for="xacNhanMatKhauMoi" class="font-bold text-slate-700">Xác nhận mật khẩu mới <span class="text-red-500">*</span></label>
                    <input type="password" id="xacNhanMatKhauMoi" required minlength="8" placeholder="Nhập lại mật khẩu mới..." class="w-full px-3.5 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-slate-900 focus:bg-white focus:border-emerald-700 outline-none">
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" id="nutHuyDoiPass" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-all">
                        Hủy
                    </button>
                    <button type="submit" class="px-6 py-2.5 <?php echo $role['btnClass']; ?> font-bold rounded-xl shadow-md transition-all flex items-center gap-2">
                        <i class="fa-solid fa-lock"></i> Cập Nhật Mật Khẩu
                    </button>
                </div>
            </form>

        </div>
    </div>

    <script src="thongTinCaNhan.js"></script>
</body>

</html>
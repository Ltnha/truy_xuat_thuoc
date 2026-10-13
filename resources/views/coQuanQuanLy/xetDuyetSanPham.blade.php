<?php
// Vai trò Cơ quan quản lý (Cục Quản lý Dược)
$vaiTroHienTai = 'CO_QUAN_QUAN_LY';
$tieuDeTrang = "Xét Duyệt Sản Phẩm – Cổng Cơ Quan Quản Lý";
$tieuDePhanHe = "Xét duyệt sản phẩm";
$fileCssRieng = "xetDuyetSanPham.css";
$fileJsRieng = "xetDuyetSanPham.js";
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $tieuDeTrang; ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

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

    <link rel="stylesheet" href="xetDuyetSanPham.css">
</head>

<body class="bg-nenChinh text-slate-900 font-sans min-h-screen flex antialiased">

    <!-- 1. Thanh Sidebar dùng chung cố định -->
    <?php include_once '../trangDungChung/layoutDungChung/sidebarDungChung.php'; ?>

    <!-- 2. Cột nội dung trang bên phải -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Thanh Topbar dùng chung -->
        <?php include_once '../trangDungChung/layoutDungChung/topbarDungChung.php'; ?>

        <main class="flex-1 p-6 sm:p-8 space-y-6 max-w-7xl w-full mx-auto">

            <!-- CỤM 2 TAB CHÍNH: HỒ SƠ ĐĂNG KÝ (TAB 1) & SẢN PHẨM ĐÃ DUYỆT (TAB 2) -->
            <div class="bg-white rounded-2xl border border-emerald-900/10 bong-xanh p-2 flex gap-2">
                <button type="button" id="tabHoSoDangKy" class="flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all bg-indigo-700 text-white shadow-sm">
                    <i class="fa-solid fa-file-signature text-base"></i>
                    <span>Hồ Sơ Đăng Ký Lưu Hành</span>
                    <span id="badgeTabHoSoChoDuyet" class="ml-1 px-2 py-0.5 rounded-full text-[11px] font-black bg-amber-400 text-indigo-950">0</span>
                </button>

                <button type="button" id="tabSanPhamDaDuyet" class="flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all text-slate-600 hover:text-indigo-700 hover:bg-indigo-50">
                    <i class="fa-solid fa-certificate text-base"></i>
                    <span>Sản Phẩm Đã Duyệt (Danh Mục On-Chain)</span>
                    <span id="badgeTabSanPhamDaDuyet" class="ml-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700">0</span>
                </button>
            </div>

            <!-- ══════════════════════════════════════════════════════════ -->
            <!-- TAB 1: DANH SÁCH HỒ SƠ ĐĂNG KÝ LƯU HÀNH THUỐC              -->
            <!-- ══════════════════════════════════════════════════════════ -->
            <div id="khungTabHoSo" class="space-y-4">
                <!-- Thanh công cụ lọc Tab 1 -->
                <div class="bg-white p-5 rounded-2xl border border-emerald-900/10 bong-xanh">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 text-xs pointer-events-none">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </span>
                            <input type="text" id="oTimKiemSP" placeholder="Tìm theo tên thuốc, số ĐK, hoạt chất, NSX..." class="w-full pl-9 pr-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-medium text-slate-900 focus:bg-white focus:border-indigo-700 outline-none">
                        </div>

                        <select id="locNhaSanXuat" class="px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:border-indigo-700 outline-none cursor-pointer">
                            <option value="TAT_CA">-- Tất cả nhà sản xuất --</option>
                            <option value="MEDIPHARCO">Dược Phẩm Medipharco</option>
                            <option value="OPV">Dược Phẩm OPV</option>
                            <option value="PHARBACO">Dược Phẩm Pharbaco</option>
                            <option value="DHG">Dược Hậu Giang (DHG Pharma)</option>
                        </select>

                        <!-- Đủ 4 giá trị trạng thái thuần Việt, mặc định Chờ duyệt -->
                        <select id="locTrangThaiYeuCauSP" class="px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:border-indigo-700 outline-none cursor-pointer">
                            <option value="CHO_DUYET" selected>Chờ duyệt</option>
                            <option value="DA_DUYET">Đã duyệt</option>
                            <option value="TU_CHOI">Bị từ chối</option>
                            <option value="TAT_CA">Tất cả</option>
                        </select>
                    </div>
                </div>

                <!-- Bảng danh sách Tab 1 -->
                <div class="bg-white rounded-2xl border border-emerald-900/10 bong-xanh overflow-hidden">
                    <div class="p-4 bg-nenChinh border-b border-emerald-900/10 flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-700 uppercase tracking-wide flex items-center gap-2">
                            <i class="fa-solid fa-capsules text-indigo-700"></i> Danh Sách Hồ Sơ Đăng Ký Lưu Hành Thuốc
                        </span>
                        <span id="soLuongYeuCauSPBadge" class="font-bold text-slate-500 font-mono">Hiển thị: 0 hồ sơ</span>
                    </div>

                    <div class="w-full overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50 text-slate-600 uppercase tracking-wider border-b border-slate-100 text-[11px]">
                                    <th class="py-3.5 px-3 font-bold whitespace-nowrap">Tên Thuốc</th>
                                    <th class="py-3.5 px-3 font-bold whitespace-nowrap">Số ĐK Lưu Hành</th>
                                    <th class="py-3.5 px-3 font-bold">Nhà Sản Xuất</th>
                                    <th class="py-3.5 px-3 font-bold">Hoạt Chất</th>
                                    <th class="py-3.5 px-2.5 font-bold whitespace-nowrap">Hàm Lượng</th>
                                    <th class="py-3.5 px-2.5 font-bold whitespace-nowrap">Dạng Bào Chế</th>
                                    <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Ngày Nộp</th>
                                    <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Lần Nộp</th>
                                    <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Trạng Thái</th>
                                    <th class="py-3.5 px-3 font-bold text-center whitespace-nowrap">Hành Động</th>
                                </tr>
                            </thead>
                            <tbody id="thanBangYeuCauSP" class="divide-y divide-slate-100 font-medium"></tbody>
                        </table>
                    </div>

                    <div class="p-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                        <span id="thongTinPhanTrangSP" class="text-slate-500">Đang hiển thị...</span>
                        <div class="flex items-center gap-1.5" id="cumNutPhanTrangSP"></div>
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════════════════ -->
            <!-- TAB 2: SẢN PHẨM ĐÃ DUYỆT (CHỈ ĐỌC, KHÔNG CÓ NÚT QUYẾT ĐỊNH)-->
            <!-- ══════════════════════════════════════════════════════════ -->
            <div id="khungTabDaDuyet" class="space-y-4 hidden">
                <!-- Thanh công cụ lọc Tab 2 -->
                <div class="bg-white p-5 rounded-2xl border border-emerald-900/10 bong-xanh">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-w-xl">
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 text-xs pointer-events-none">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </span>
                            <input type="text" id="oTimKiemDaDuyet" placeholder="Tìm theo tên thuốc hoặc số đăng ký..." class="w-full pl-9 pr-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-medium text-slate-900 focus:bg-white focus:border-indigo-700 outline-none">
                        </div>

                        <select id="locNhaSanXuatDaDuyet" class="px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:border-indigo-700 outline-none cursor-pointer">
                            <option value="TAT_CA">-- Tất cả nhà sản xuất --</option>
                            <option value="MEDIPHARCO">Dược Phẩm Medipharco</option>
                            <option value="OPV">Dược Phẩm OPV</option>
                            <option value="PHARBACO">Dược Phẩm Pharbaco</option>
                            <option value="DHG">Dược Hậu Giang (DHG Pharma)</option>
                        </select>
                    </div>
                </div>

                <!-- Bảng danh sách Tab 2 -->
                <div class="bg-white rounded-2xl border border-emerald-900/10 bong-xanh overflow-hidden">
                    <div class="p-4 bg-nenChinh border-b border-emerald-900/10 flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-700 uppercase tracking-wide flex items-center gap-2">
                            <i class="fa-solid fa-certificate text-emerald-700"></i> Danh Mục Thuốc Đã Cấp Phép Lưu Hành Toàn Quốc
                        </span>
                        <span id="soLuongDaDuyetBadge" class="font-bold text-slate-500 font-mono">Hiển thị: 0 sản phẩm</span>
                    </div>

                    <div class="w-full overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50 text-slate-600 uppercase tracking-wider border-b border-slate-100 text-[11px]">
                                    <th class="py-3.5 px-3 font-bold whitespace-nowrap">Tên Thuốc</th>
                                    <th class="py-3.5 px-3 font-bold whitespace-nowrap">Số ĐK Lưu Hành</th>
                                    <th class="py-3.5 px-3 font-bold">Hoạt Chất</th>
                                    <th class="py-3.5 px-2.5 font-bold whitespace-nowrap">Hàm Lượng</th>
                                    <th class="py-3.5 px-2.5 font-bold whitespace-nowrap">Dạng Bào Chế</th>
                                    <th class="py-3.5 px-3 font-bold">Nhà Sản Xuất</th>
                                    <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Ngày Duyệt</th>
                                    <th class="py-3.5 px-3 font-bold whitespace-nowrap">Mã Giao Dịch Blockchain</th>
                                    <th class="py-3.5 px-3 font-bold text-center whitespace-nowrap">Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody id="thanBangDaDuyet" class="divide-y divide-slate-100 font-medium"></tbody>
                        </table>
                    </div>

                    <div class="p-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                        <span id="thongTinPhanTrangDaDuyet" class="text-slate-500">Đang hiển thị...</span>
                        <div class="flex items-center gap-1.5" id="cumNutPhanTrangDaDuyet"></div>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- MODAL DÙNG CHUNG: CHI TIẾT SẢN PHẨM / THẨM ĐỊNH HỒ SƠ     -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <div id="modalXemHoSoSP" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-3 sm:p-5 hidden bg-black/60">
        <div class="bg-white rounded-2xl max-w-5xl w-full p-5 sm:p-7 space-y-4 bong-xanh max-h-[92vh] flex flex-col">

            <!-- Header Modal -->
            <div class="border-b border-slate-100 pb-3 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-2.5 flex-wrap">
                    <span id="panelTieuDeChinh" class="text-base font-bold text-slate-900">Chi Tiết Sản Phẩm:</span>
                    <span id="panelTenThuocTitle" class="font-bold text-indigo-900 bg-indigo-50 px-2.5 py-0.5 rounded text-xs border border-indigo-200"></span>
                    <span id="panelTrangThaiSPBadge"></span>
                </div>
                <button type="button" class="dong-modal-hoso-sp text-slate-400 hover:text-slate-700 text-lg">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- CẢNH BÁO TRÙNG SỐ ĐĂNG KÝ (KÈM LIÊN KẾT XEM SẢN PHẨM TRÙNG) -->
            <div id="modalCanhBaoTrungSoDK" class="hidden p-3 rounded-xl border border-amber-300 bg-amber-50 text-amber-950 text-xs shrink-0 space-y-1.5">
                <div class="font-bold flex items-center justify-between">
                    <span class="flex items-center gap-1.5 text-amber-800">
                        <i class="fa-solid fa-triangle-exclamation"></i> CẢNH BÁO TRÙNG SỐ ĐĂNG KÝ LƯU HÀNH:
                    </span>
                    <button type="button" id="nutXemChiTietSPTrung" class="text-indigo-800 hover:underline font-bold text-[11px] flex items-center gap-1">
                        Xem sản phẩm đã duyệt này <i class="fa-solid fa-arrow-right text-[9px]"></i>
                    </button>
                </div>
                <p id="modalCanhBaoTrungSoDKText" class="text-[11px] leading-relaxed"></p>
            </div>

            <!-- CẢNH BÁO NHÀ SẢN XUẤT ĐANG BỊ THU HỒI QUYỀN -->
            <div id="modalCanhBaoNSXBiThuHoi" class="hidden p-3 rounded-xl border border-red-300 bg-red-50 text-red-950 text-xs shrink-0 space-y-1">
                <div class="font-bold flex items-center gap-1.5 text-red-700">
                    <i class="fa-solid fa-ban"></i> CẢNH BÁO: NHÀ SẢN XUẤT ĐANG BỊ THU HỒI QUYỀN TỔ CHỨC
                </div>
                <p class="text-[11px] leading-relaxed text-red-800">
                    Nhà sản xuất nộp hồ sơ hiện đang ở trạng thái <strong>Bị thu hồi quyền</strong> trên hệ thống. Dù sản phẩm đã được cấp phép, nhà sản xuất này hiện tại <strong>không thể đăng ký khởi tạo thêm bất kỳ lô thuốc mới nào</strong> cho đến khi được Cục phục hồi quyền hạn.
                </p>
            </div>

            <!-- Body Modal -->
            <div class="flex-1 overflow-y-auto space-y-4 pr-1 text-xs">

                <!-- KHỐI VẬN HÀNH & BLOCKCHAIN (CHỈ HIỆN KHI Ở CHẾ ĐỘ ĐÃ DUYỆT) -->
                <div id="khungThongKeVanHanhDaDuyet" class="hidden grid grid-cols-1 sm:grid-cols-2 gap-3 p-3.5 bg-emerald-50/70 border border-emerald-200 rounded-xl">
                    <div class="space-y-1">
                        <span class="text-slate-500 block text-[11px]">Tình trạng lưu thông các lô thuốc:</span>
                        <div id="thongKeSoLoSanPham" class="font-bold text-slate-800 text-sm"></div>
                    </div>
                    <div class="space-y-1">
                        <span class="text-slate-500 block text-[11px]">Bằng chứng phê duyệt Blockchain (txHash):</span>
                        <div class="flex items-center gap-2 p-1.5 bg-white border border-emerald-200 rounded-lg">
                            <a id="linkTxHashDaDuyet" href="#" target="_blank" class="flex-1 font-mono text-[11px] text-cyan-800 hover:underline truncate">
                                <span id="textTxHashDaDuyet">0x...</span>
                                <i class="fa-solid fa-arrow-up-right-from-square text-[9px] text-slate-400 ml-1"></i>
                            </a>
                            <button type="button" id="nutCopyTxDaDuyet" class="px-2 py-0.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-[10px] font-bold shrink-0">
                                <i class="fa-regular fa-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">

                    <!-- KHỐI 1: THÔNG TIN SẢN PHẨM (5 TRƯỜNG CSDL CHUẨN, HIGHLIGHT DIFF NẾU LẦN >= 2) -->
                    <div class="lg:col-span-7 bg-nenChinh p-4 rounded-xl border border-emerald-900/10 space-y-3">
                        <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                            <span class="font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2">
                                <i class="fa-solid fa-pills text-indigo-700"></i> Thông Tin Sản Phẩm Kê Khai
                            </span>
                            <span id="panelGhiChuSoSanh" class="hidden text-[10px] text-amber-800 font-bold bg-amber-100 px-2 py-0.5 rounded border border-amber-300">
                                <i class="fa-solid fa-pen-to-square"></i> Có thay đổi so với lần 1
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-2.5">
                            <div class="col-span-2 p-2 rounded-lg" id="khungTenThuoc">
                                <span class="text-slate-500 block text-[11px]">Tên thuốc / Biệt dược:</span>
                                <span id="panelTenThuoc" class="font-bold text-slate-900 text-sm"></span>
                                <div id="diffTenThuoc" class="hidden text-[10px] text-amber-800 font-semibold mt-0.5"></div>
                            </div>

                            <div class="p-2 rounded-lg" id="khungSoDK">
                                <span class="text-slate-500 block text-[11px]">Số đăng ký lưu hành:</span>
                                <span id="panelSoDK" class="font-mono font-bold text-cyan-800 text-xs"></span>
                                <div id="diffSoDK" class="hidden text-[10px] text-amber-800 font-semibold mt-0.5"></div>
                            </div>

                            <div class="p-2 rounded-lg" id="khungHoatChat">
                                <span class="text-slate-500 block text-[11px]">Hoạt chất chính:</span>
                                <span id="panelHoatChat" class="font-semibold text-slate-900"></span>
                                <div id="diffHoatChat" class="hidden text-[10px] text-amber-800 font-semibold mt-0.5"></div>
                            </div>

                            <div class="p-2 rounded-lg" id="khungHamLuong">
                                <span class="text-slate-500 block text-[11px]">Hàm lượng:</span>
                                <span id="panelHamLuong" class="font-semibold text-slate-800 font-mono"></span>
                                <div id="diffHamLuong" class="hidden text-[10px] text-amber-800 font-semibold mt-0.5"></div>
                            </div>

                            <div class="p-2 rounded-lg" id="khungDangBaoChe">
                                <span class="text-slate-500 block text-[11px]">Dạng bào chế:</span>
                                <span id="panelDangBaoChe" class="font-semibold text-slate-800"></span>
                                <div id="diffDangBaoChe" class="hidden text-[10px] text-amber-800 font-semibold mt-0.5"></div>
                            </div>
                        </div>
                    </div>

                    <!-- KHỐI 2: THÔNG TIN NHÀ SẢN XUẤT -->
                    <div class="lg:col-span-5 bg-indigo-50/60 p-4 rounded-xl border border-indigo-200 space-y-3">
                        <span class="font-bold text-indigo-950 uppercase tracking-wide flex items-center gap-2 border-b border-indigo-200 pb-2">
                            <i class="fa-solid fa-industry text-indigo-700"></i> Thông Tin Nhà Sản Xuất
                        </span>

                        <div class="space-y-2">
                            <div>
                                <span class="text-slate-500 block">Tên cơ sở sản xuất:</span>
                                <span id="panelTenNSX" class="font-bold text-slate-900 text-xs"></span>
                            </div>
                            <div>
                                <span class="text-slate-500 block">Mã số thuế:</span>
                                <span id="panelMstNSX" class="font-mono font-bold text-slate-800"></span>
                            </div>
                            <div>
                                <span class="text-slate-500 block">Số giấy phép kinh doanh dược:</span>
                                <span id="panelGiayPhepNSX" class="font-mono font-semibold text-indigo-900"></span>
                            </div>
                            <div>
                                <span class="text-slate-500 block">Trạng thái tổ chức:</span>
                                <div id="panelTrangThaiToChucNSX" class="pt-0.5"></div>
                            </div>
                            <div>
                                <span class="text-slate-500 block">Địa chỉ:</span>
                                <span id="panelDiaChiNSX" class="text-slate-700 leading-tight block"></span>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- KHỐI 3: LỊCH SỬ CÁC LẦN NỘP HỒ SƠ ĐĂNG KÝ (THU GỌN) -->
                <div class="border-t border-slate-200 pt-3 space-y-2">
                    <span class="font-bold text-slate-700 uppercase tracking-wide flex items-center gap-2 text-[11px]">
                        <i class="fa-solid fa-clock-rotate-left text-slate-500"></i> Lịch Sử Các Lần Nộp Hồ Sơ Đăng Ký
                    </span>

                    <div id="panelDanhSachLichSuNopSP" class="space-y-1.5"></div>
                </div>

            </div>

            <!-- Footer Modal: Chỉ hiện nút Phê duyệt / Từ chối khi ở chế độ CHỜ DUYỆT -->
            <div class="border-t border-slate-100 pt-3 flex items-center justify-between shrink-0">
                <button type="button" class="dong-modal-hoso-sp px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition-all">
                    Đóng
                </button>

                <div id="panelCumNutHanhDongSP" class="flex items-center gap-2.5">
                    <button type="button" id="panelNutMoModalTuChoiSP" class="px-5 py-2 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 font-bold rounded-xl text-xs transition-all flex items-center gap-1.5 shadow-2xs">
                        <i class="fa-solid fa-circle-xmark"></i> Từ Chối Đăng Ký
                    </button>
                    <button type="button" id="panelNutMoModalDuyetSP" class="px-6 py-2 bg-indigo-700 hover:bg-indigo-800 text-white font-bold rounded-xl text-xs transition-all flex items-center gap-1.5 shadow-md">
                        <i class="fa-solid fa-circle-check"></i> Phê Duyệt Lưu Hành
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- MODAL XÁC NHẬN PHÊ DUYỆT (TÓM TẮT, HẬU QUẢ, ON-CHAIN)     -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <div id="modalXacNhanDuyetSP" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden bg-black/60">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 sm:p-8 space-y-5 bong-xanh">
            <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-circle-check text-indigo-700"></i> Phê Duyệt Lưu Hành Thuốc
                </h3>
                <button type="button" class="dong-modal-duyet-sp text-slate-400 hover:text-slate-700 text-lg">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div id="manHinhFormDuyetSP" class="space-y-4 text-xs">
                <div class="p-3.5 bg-nenChinh rounded-xl border border-emerald-900/10 space-y-1.5">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Tên thuốc:</span>
                        <span id="duyetModalTenThuoc" class="font-bold text-slate-900 text-right"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Số ĐK lưu hành:</span>
                        <span id="duyetModalSoDK" class="font-mono font-bold text-cyan-800"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Nhà sản xuất:</span>
                        <span id="duyetModalNSX" class="font-bold text-slate-800"></span>
                    </div>
                </div>

                <div class="p-3 bg-indigo-50 rounded-xl border border-indigo-200 text-indigo-950 space-y-1 leading-relaxed text-[11px]">
                    <div class="font-bold flex items-center gap-1.5 text-indigo-800">
                        <i class="fa-solid fa-cube"></i> Hậu quả pháp lý & hệ thống:
                    </div>
                    <ul class="list-disc list-inside space-y-0.5 text-slate-700 text-[10px]">
                        <li>Sản phẩm chuyển sang trạng thái <strong>Đã duyệt</strong>.</li>
                        <li>Thông tin sản phẩm được <strong>ký ghi lên blockchain</strong> Polygon Amoy và lưu mã băm (txHash).</li>
                        <li>Nhà sản xuất được phép <strong>chọn sản phẩm này khi khởi tạo lô thuốc mới</strong>.</li>
                    </ul>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" class="dong-modal-duyet-sp px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-all">
                        Hủy
                    </button>
                    <button type="button" id="nutXacNhanDuyetSPGhiBlockchain" class="px-6 py-2.5 bg-indigo-700 hover:bg-indigo-800 text-white font-bold rounded-xl shadow-md transition-all flex items-center gap-1.5">
                        <i class="fa-solid fa-shield-halved"></i> Xác Nhận & Ký Blockchain
                    </button>
                </div>
            </div>

            <div id="manHinhDangGhiBlockchainSP" class="hidden text-center py-6 space-y-4">
                <div class="w-14 h-14 mx-auto rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-2xl">
                    <i class="fa-solid fa-cube fa-bounce"></i>
                </div>
                <div class="space-y-1">
                    <h4 class="text-sm font-bold text-slate-900">Đang ghi nhận trên blockchain...</h4>
                    <p class="text-xs text-slate-500">Đang ký xác thực số đăng ký lưu hành thuốc vào Smart Contract.</p>
                </div>
                <div class="text-[10px] text-slate-400 font-mono">Vui lòng chờ xác thực giao dịch on-chain...</div>
            </div>

            <div id="manHinhLoiBlockchainSP" class="hidden text-center py-4 space-y-4">
                <div class="w-12 h-12 mx-auto rounded-full bg-red-100 text-red-600 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div class="space-y-1 text-xs">
                    <h4 class="text-sm font-bold text-red-700">Giao Dịch Blockchain Thất Bại!</h4>
                    <p class="text-slate-600">Lỗi kết nối RPC Node Polygon Amoy. Trạng thái hồ sơ được giữ nguyên.</p>
                </div>
                <div class="flex items-center justify-center gap-3 pt-2">
                    <button type="button" class="dong-modal-duyet-sp px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs">
                        Đóng
                    </button>
                    <button type="button" id="nutThuLaiDuyetSPBlockchain" class="px-5 py-2 bg-indigo-700 hover:bg-indigo-800 text-white font-bold rounded-xl text-xs flex items-center gap-1.5">
                        <i class="fa-solid fa-rotate-right"></i> Thử Lại
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- MODAL XÁC NHẬN TỪ CHỐI (TỐI ĐA 500 KT, NÚT SÁNG KHI NHẬP)  -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <div id="modalTuChoiSP" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden bg-black/60">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 sm:p-8 space-y-5 bong-xanh">
            <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-circle-xmark text-red-600"></i> Từ Chối Đăng Ký Lưu Hành
                </h3>
                <button type="button" class="dong-modal-tuchoi-sp text-slate-400 hover:text-slate-700 text-lg">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="space-y-4 text-xs">
                <div class="p-3 bg-nenChinh rounded-xl border border-emerald-900/10 space-y-1">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Tên thuốc:</span>
                        <span id="tuChoiModalTenThuoc" class="font-bold text-slate-900"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Số ĐK lưu hành:</span>
                        <span id="tuChoiModalSoDK" class="font-mono font-bold text-slate-900"></span>
                    </div>
                </div>

                <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-amber-950 space-y-1 leading-relaxed text-[11px]">
                    <div class="font-bold flex items-center gap-1.5 text-amber-800">
                        <i class="fa-solid fa-circle-exclamation"></i> Quy định từ chối:
                    </div>
                    <ul class="list-disc list-inside space-y-0.5 text-slate-700 text-[10px]">
                        <li>Thao tác từ chối chỉ lưu vào cơ sở dữ liệu, <strong>không ghi lên blockchain</strong>.</li>
                        <li><strong>Lý do từ chối sẽ hiển thị cho nhà sản xuất</strong> tại trang Quản lý sản phẩm để họ chỉnh sửa và nộp lại.</li>
                    </ul>
                </div>

                <div class="space-y-1">
                    <div class="flex items-center justify-between">
                        <label for="nhapLyDoTuChoiSP" class="font-bold text-slate-700 uppercase tracking-wide block text-[11px]">
                            Lý Do Từ Chối Đăng Ký <span class="text-red-500">*</span>
                        </label>
                        <span id="demKyTuTuChoiSP" class="text-[10px] text-slate-400 font-mono">0/500</span>
                    </div>
                    <textarea id="nhapLyDoTuChoiSP" maxlength="500" rows="3" required placeholder="Nêu rõ lý do: Số đăng ký lưu hành không trùng khớp danh mục cấp phép, hoạt chất chưa rõ ràng..." class="w-full p-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl font-medium text-slate-900 focus:bg-white focus:border-red-600 outline-none resize-y"></textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                <button type="button" class="dong-modal-tuchoi-sp px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-all">
                    Hủy
                </button>
                <button type="button" id="nutXacNhanTuChoiSPOffChain" disabled class="px-6 py-2.5 bg-slate-200 text-slate-400 font-bold rounded-xl transition-all flex items-center gap-1.5 cursor-not-allowed shadow-none">
                    <i class="fa-solid fa-ban"></i> Xác Nhận Từ Chối
                </button>
            </div>
        </div>
    </div>

    <script src="xetDuyetSanPham.js"></script>
</body>

</html>
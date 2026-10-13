<?php
// Vai trò Cơ quan quản lý (Cục Quản lý Dược - Tông màu Indigo / Chàm)
$vaiTroHienTai = 'CO_QUAN_QUAN_LY';
$tieuDeTrang = "Thu Hồi Lô Hàng – Cổng Cơ Quan Quản Lý";
$tieuDePhanHe = "Thu hồi lô hàng";
$fileCssRieng = "quanLyThuHoi.css";
$fileJsRieng = "quanLyThuHoi.js";
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $tieuDeTrang; ?></title>

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

    <link rel="stylesheet" href="quanLyThuHoi.css">
</head>

<body class="bg-nenChinh text-slate-900 font-sans min-h-screen flex antialiased">

    <!-- 1. Thanh Sidebar dùng chung cố định -->
    <?php include_once '../trangDungChung/layoutDungChung/sidebarDungChung.php'; ?>

    <!-- 2. Cột nội dung trang bên phải -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Thanh Topbar dùng chung -->
        <?php include_once '../trangDungChung/layoutDungChung/topbarDungChung.php'; ?>

        <main class="flex-1 p-6 sm:p-8 space-y-6 max-w-7xl w-full mx-auto">

            <!-- THANH CÔNG CỤ: TÌM KIẾM + LỌC NHÀ SẢN XUẤT + BỘ LỌC 4 TRẠNG THÁI -->
            <div class="bg-white p-5 rounded-2xl border border-emerald-900/10 bong-xanh">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <!-- Ô tìm kiếm -->
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 text-xs pointer-events-none">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </span>
                        <input type="text" id="oTimKiemThuHoi" placeholder="Tìm theo mã lô hoặc tên sản phẩm..." class="w-full pl-9 pr-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-medium text-slate-900 focus:bg-white focus:border-indigo-700 outline-none">
                    </div>

                    <!-- Lọc theo Nhà sản xuất -->
                    <select id="locNhaSanXuat" class="px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:border-indigo-700 outline-none cursor-pointer">
                        <option value="TAT_CA">-- Tất cả nhà sản xuất --</option>
                        <option value="PHARBACO">Dược Phẩm Pharbaco</option>
                        <option value="MEDIPHARCO">Dược Phẩm Medipharco</option>
                        <option value="OPV">Dược Phẩm OPV</option>
                        <option value="DHG">Dược Hậu Giang (DHG Pharma)</option>
                    </select>

                    <!-- Lọc trạng thái lô -->
                    <select id="locTrangThaiLo" class="px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:border-indigo-700 outline-none cursor-pointer">
                        <option value="TAT_CA">-- Tất cả trạng thái lô --</option>
                        <option value="DANG_LUU_HANH">Đang lưu thông</option>
                        <option value="DA_THU_HOI">Đã thu hồi</option>
                        <option value="HET_HAN">Hết hạn</option>
                    </select>
                </div>
            </div>

            <!-- BẢNG DANH SÁCH LÔ THUỐC TOÀN HỆ THỐNG -->
            <div class="bg-white rounded-2xl border border-emerald-900/10 bong-xanh overflow-hidden">
                <div class="p-4 bg-nenChinh border-b border-emerald-900/10 flex items-center justify-between text-xs">
                    <span class="font-bold text-slate-700 uppercase tracking-wide flex items-center gap-2">
                        <i class="fa-solid fa-boxes-stacked text-indigo-700"></i> Danh Sách Lô Thuốc Toàn Hệ Thống
                    </span>
                    <span id="soLuongLoBadge" class="font-bold text-slate-500 font-mono">Hiển thị: 0 lô</span>
                </div>

                <div class="w-full overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 text-slate-600 uppercase tracking-wider border-b border-slate-100 text-[11px]">
                                <th class="py-3.5 px-3 font-bold whitespace-nowrap">Mã Lô</th>
                                <th class="py-3.5 px-3 font-bold">Sản Phẩm</th>
                                <th class="py-3.5 px-3 font-bold">Nhà Sản Xuất</th>
                                <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Hạn Dùng</th>
                                <th class="py-3.5 px-3 font-bold text-right whitespace-nowrap">Tổng Số Lượng</th>
                                <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Trạng Thái Lô</th>
                                <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Báo Cáo Nghi Vấn</th>
                                <th class="py-3.5 px-3 font-bold text-center whitespace-nowrap">Hành Động</th>
                            </tr>
                        </thead>
                        <tbody id="thanBangThuHoi" class="divide-y divide-slate-100 font-medium"></tbody>
                    </table>
                </div>

                <!-- Phân trang -->
                <div class="p-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                    <span id="thongTinPhanTrang" class="text-slate-500">Đang hiển thị...</span>
                    <div class="flex items-center gap-1.5" id="cumNutPhanTrang"></div>
                </div>
            </div>

        </main>
    </div>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- MODAL 1: CHI TIẾT LÔ THUỐC & HIỆN TRẠNG PHÂN BỔ          -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <div id="modalChiTietLo" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-3 sm:p-5 hidden">
        <div class="bg-white rounded-2xl max-w-3xl w-full p-5 sm:p-6 space-y-4 bong-xanh max-h-[92vh] flex flex-col">

            <div class="border-b border-slate-100 pb-2.5 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-2.5 flex-wrap">
                    <span class="text-base font-bold text-slate-900">Chi Tiết Lô Thuốc:</span>
                    <span id="detailMaLoTitle" class="font-mono font-bold text-indigo-900 bg-indigo-50 px-2.5 py-0.5 rounded text-xs border border-indigo-200"></span>
                    <span id="detailTrangThaiLoBadge"></span>
                </div>
                <button type="button" class="dong-modal-detail text-slate-400 hover:text-slate-700 text-lg">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- BANNER ĐỎ NẾU LÔ ĐÃ THU HỒI -->
            <div id="detailBannerDaThuHoi" class="hidden p-3 bg-red-50 border border-red-200 rounded-xl text-xs space-y-1.5 shrink-0">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-red-700 flex items-center gap-1.5 text-xs">
                        <i class="fa-solid fa-ban text-sm"></i> LÔ THUỐC ĐÃ CÓ QUYẾT ĐỊNH THU HỒI TRÊN TOÀN HỆ THỐNG
                    </span>
                    <span class="badge-chuan px-2 py-0.5 rounded text-[10px] font-extrabold bg-red-200 text-red-800 border border-red-300">Đã thu hồi</span>
                </div>
                <div>
                    <span class="text-slate-500 block text-[11px]">Lý do thu hồi:</span>
                    <p id="detailLyDoThuHoiText" class="font-bold text-red-900 leading-relaxed"></p>
                </div>
                <div class="grid grid-cols-2 gap-2 text-[11px] pt-1 border-t border-red-200 text-slate-600">
                    <div>Thời điểm thu hồi: <strong id="detailNgayThuHoiText" class="font-mono text-slate-800"></strong></div>
                    <div>Người ban hành: <strong id="detailNguoiQuyetDinhText" class="font-mono text-slate-800">inspector_01</strong></div>
                </div>
                <div class="pt-1 space-y-1">
                    <span class="text-slate-500 block text-[10px]">Mã giao dịch thu hồi on-chain:</span>
                    <div class="flex items-center gap-2 p-1.5 bg-white border border-red-200 rounded-lg">
                        <a id="detailTxHashThuHoiLink" href="#" target="_blank" class="flex-1 font-mono text-[11px] text-cyan-800 hover:underline break-all truncate">
                            <span id="detailTxHashThuHoiText">0x...</span>
                            <i class="fa-solid fa-arrow-up-right-from-square text-[9px] text-slate-400 ml-1"></i>
                        </a>
                        <button type="button" id="nutCopyDetailTxThuHoi" class="px-2 py-0.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-[10px] font-bold shrink-0">
                            <i class="fa-regular fa-copy"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- GỢI Ý THU HỒI NẾU LÔ ĐÃ HẾT HẠN SỬ DỤNG NHƯNG CHƯA THU HỒI -->
            <div id="detailBannerGoiYThuHoiHetHan" class="hidden p-2.5 bg-amber-50 border border-amber-300 rounded-xl text-xs text-amber-950 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation text-amber-600 text-base"></i>
                    <span><strong>Lô thuốc đã hết hạn sử dụng.</strong> Cân nhắc ban hành quyết định thu hồi chính thức để khóa lưu thông trên chuỗi.</span>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto space-y-3 pr-1 text-xs">

                <!-- THÔNG TIN TỔNG QUAN LÔ THUỐC -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 p-3 bg-nenChinh rounded-xl border border-emerald-900/10 text-xs">
                    <div>
                        <span class="text-slate-500 block text-[11px]">Sản phẩm:</span>
                        <span id="detailTenThuoc" class="font-bold text-slate-900"></span>
                    </div>
                    <div>
                        <span class="text-slate-500 block text-[11px]">Số ĐK lưu hành:</span>
                        <span id="detailSoDK" class="font-mono font-bold text-cyan-800"></span>
                    </div>
                    <div>
                        <span class="text-slate-500 block text-[11px]">Nhà sản xuất:</span>
                        <span id="detailNhaSanXuat" class="font-semibold text-slate-800"></span>
                    </div>
                    <div>
                        <span class="text-slate-500 block text-[11px]">Hạn dùng:</span>
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span id="detailHanDung" class="font-mono font-bold text-slate-800"></span>
                            <span id="detailHanDungTag" class="hidden"></span>
                        </div>
                    </div>
                    <div>
                        <span class="text-slate-500 block text-[11px]">Tổng số lượng:</span>
                        <span id="detailTongSoLuong" class="font-mono font-bold text-indigo-900"></span>
                    </div>
                    <div>
                        <span class="text-slate-500 block text-[11px]">Đang lưu thông:</span>
                        <span id="detailDangLuuThong" class="font-mono font-bold text-emerald-700"></span>
                    </div>
                    <div>
                        <span class="text-slate-500 block text-[11px]">Ngày sản xuất:</span>
                        <span id="detailNgaySanXuat" class="font-mono text-slate-700"></span>
                    </div>
                    <div>
                        <span class="text-slate-500 block text-[11px]">Báo cáo nghi vấn:</span>
                        <div class="flex items-center gap-1.5 pt-0.5">
                            <span id="detailSoBaoCaoNghiVan" class="font-bold text-amber-800 font-mono">0 báo cáo</span>
                            <a id="detailLinkSangNghiVan" href="../coQuanQuanLy/xuLyNghiVan.php" class="text-cyan-800 hover:underline font-bold text-[11px] flex items-center gap-1">
                                [Xem báo cáo <i class="fa-solid fa-arrow-right text-[9px]"></i>]
                            </a>
                        </div>
                    </div>
                </div>

                <!-- BẢNG PHÂN BỔ TỒN KHO TẠI CÁC BÊN GIỮ -->
                <div class="space-y-1.5">
                    <span class="font-bold text-slate-700 uppercase tracking-wide flex items-center gap-1.5 text-[11px]">
                        <i class="fa-solid fa-warehouse text-slate-500"></i> Hiện Trạng Phân Bổ Tồn Kho Trong Chuỗi Cung Ứng:
                    </span>
                    <div class="border border-slate-200 rounded-xl overflow-hidden">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead class="bg-slate-50 text-slate-600 border-b border-slate-200 text-[11px]">
                                <tr>
                                    <th class="p-2.5 font-bold">Đơn Vị Đang Lưu Giữ</th>
                                    <th class="p-2.5 font-bold text-center">Vai Trò</th>
                                    <th class="p-2.5 font-bold text-right">Số Lượng Giữ</th>
                                    <th id="thCotBanLe" class="p-2.5 font-bold text-right">Đã Xuất Bán Lẻ</th>
                                </tr>
                            </thead>
                            <tbody id="detailThanBangPhanBoKho" class="divide-y divide-slate-100 font-mono"></tbody>
                        </table>
                    </div>
                </div>

            </div>

            <div class="border-t border-slate-100 pt-3 flex items-center justify-between shrink-0">
                <button type="button" class="dong-modal-detail px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition-all">
                    Đóng
                </button>
                <div id="detailKhungNutThuHoiNhanh"></div>
            </div>

        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- MODAL 2: THU HỒI LÔ HÀNG (KÝ SMART CONTRACT RECALLBATCH)  -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <div id="modalThuHoiLoHang" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-2xl max-w-lg w-full p-5 sm:p-7 space-y-4 bong-xanh max-h-[92vh] flex flex-col">
            <div class="border-b border-slate-100 pb-2.5 flex items-center justify-between shrink-0">
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-ban text-red-600"></i> Ban Hành Quyết Định Thu Hồi Lô Thuốc
                </h3>
                <button type="button" class="dong-modal-thuhoi text-slate-400 hover:text-slate-700 text-lg">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Form thu hồi -->
            <div id="manHinhFormThuHoi" class="flex-1 overflow-y-auto space-y-3.5 text-xs pr-1">

                <div class="p-3 bg-nenChinh rounded-xl border border-emerald-900/10 space-y-1.5">
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500">Mã lô thu hồi:</span>
                        <span id="thuHoiModalMaLo" class="font-mono font-bold text-red-700 text-sm"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500">Tên thuốc:</span>
                        <span id="thuHoiModalTenThuoc" class="font-bold text-slate-900 text-right"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500">Nhà sản xuất:</span>
                        <span id="thuHoiModalNSX" class="font-semibold text-slate-800"></span>
                    </div>
                    <div class="pt-1 border-t border-slate-200">
                        <span class="text-slate-500 block text-[11px] mb-1">Số lượng đang giữ tại các đơn vị:</span>
                        <div id="thuHoiModalDanhSachBenGiu" class="space-y-1 bg-white p-2 rounded-lg border border-slate-200 text-[11px] font-mono"></div>
                    </div>
                </div>

                <!-- CẢNH BÁO CHUYỂN GIAO ĐANG CHỜ XÁC NHẬN CỦA LÔ NÀY -->
                <div id="thuHoiKhungChuyenGiaoTreo" class="hidden p-2.5 bg-amber-50 rounded-xl border border-amber-300 text-amber-950 text-[11px] leading-relaxed space-y-1">
                    <div class="font-bold flex items-center gap-1 text-amber-800">
                        <i class="fa-solid fa-triangle-exclamation"></i> Chuyển giao đang chờ xác nhận:
                    </div>
                    <p id="thuHoiChuyenGiaoTreoText"></p>
                    <p class="text-[10px] text-amber-900 font-semibold italic">
                        * Giới hạn: Khi lô thu hồi, các giao dịch chuyển giao đang chờ sẽ không còn hiệu lực nhận hàng và do Cơ quan quản lý xử lý thủ công.
                    </p>
                </div>

                <!-- CẢNH BÁO HẬU QUẢ PHÁP LÝ & KỸ THUẬT -->
                <div class="p-3 bg-red-50 rounded-xl border border-red-200 text-red-950 space-y-1.5 leading-relaxed text-[11px]">
                    <div class="font-bold flex items-center gap-1.5 text-red-700">
                        <i class="fa-solid fa-triangle-exclamation"></i> Cảnh báo phạm vi hậu quả:
                    </div>
                    <ul class="list-disc list-inside space-y-0.5 text-slate-700 text-[10px]">
                        <li>Trạng thái của lô sẽ chuyển thành <strong>Đã thu hồi</strong>.</li>
                        <li><strong>Nhà thuốc không thể bán các đơn vị thuộc lô này nữa</strong>, người tiêu dùng tra cứu sẽ thấy "Đã thu hồi".</li>
                        <li>Quyết định được <strong>ghi lên blockchain (Polygon Amoy), không thể hoàn tác</strong>.</li>
                    </ul>
                </div>

                <!-- Ô nhập lý do thu hồi bắt buộc -->
                <div class="space-y-1">
                    <div class="flex items-center justify-between">
                        <label for="nhapLyDoThuHoiLo" class="font-bold text-slate-700 uppercase tracking-wide block text-[11px]">
                            Lý Do Thu Hồi Lô Thuốc <span class="text-red-500">*</span>
                        </label>
                        <span id="demKyTuThuHoi" class="text-[10px] text-slate-400 font-mono">0/500</span>
                    </div>
                    <textarea id="nhapLyDoThuHoiLo" maxlength="500" rows="3" required placeholder="Nêu rõ căn cứ: Công văn số của Cục, kiểm nghiệm độ hòa tan không đạt..." class="w-full p-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl font-medium text-slate-900 focus:bg-white focus:border-red-600 outline-none resize-y"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100">
                    <button type="button" class="dong-modal-thuhoi px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-all">
                        Hủy
                    </button>
                    <button type="button" id="nutXacNhanThuHoiBlockchain" disabled class="px-6 py-2 bg-slate-200 text-slate-400 font-bold rounded-xl transition-all flex items-center gap-1.5 cursor-not-allowed shadow-none">
                        <i class="fa-solid fa-ban"></i> Xác Nhận Thu Hồi
                    </button>
                </div>
            </div>

            <!-- Màn hình chờ ghi nhận trên blockchain -->
            <div id="manHinhDangGhiBlockchain" class="hidden text-center py-6 space-y-4">
                <div class="w-14 h-14 mx-auto rounded-full bg-red-100 text-red-600 flex items-center justify-center text-2xl">
                    <i class="fa-solid fa-cube fa-bounce"></i>
                </div>
                <div class="space-y-1">
                    <h4 class="text-sm font-bold text-slate-900">Đang ghi nhận trên blockchain...</h4>
                    <p class="text-xs text-slate-500">Đang thực thi hàm recallBatch() và ký giao dịch bằng ví thẩm định của Cục.</p>
                </div>
                <div class="text-[10px] text-slate-400 font-mono">Vui lòng chờ xác thực giao dịch on-chain...</div>
            </div>

            <!-- Màn hình lỗi và cho phép thử lại -->
            <div id="manHinhLoiBlockchain" class="hidden text-center py-4 space-y-4">
                <div class="w-12 h-12 mx-auto rounded-full bg-red-100 text-red-600 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div class="space-y-1 text-xs">
                    <h4 class="text-sm font-bold text-red-700">Giao Dịch Blockchain Thất Bại!</h4>
                    <p class="text-slate-600">Lỗi kết nối RPC Node Polygon Amoy. Trạng thái lô thuốc được giữ nguyên.</p>
                </div>
                <div class="flex items-center justify-center gap-3 pt-2">
                    <button type="button" class="dong-modal-thuhoi px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs">
                        Đóng
                    </button>
                    <button type="button" id="nutThuLaiThuHoiBlockchain" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl text-xs flex items-center gap-1.5">
                        <i class="fa-solid fa-rotate-right"></i> Thử Lại
                    </button>
                </div>
            </div>

        </div>
    </div>

    <script src="quanLyThuHoi.js"></script>
</body>

</html>
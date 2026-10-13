<?php
// Khai báo vai trò Cơ quan quản lý (Cục Quản lý Dược)
$vaiTroHienTai = 'CO_QUAN_QUAN_LY';
$tieuDeTrang = "Phê Duyệt Tổ Chức – Cổng Cơ Quan Quản Lý";
$tieuDePhanHe = "Phê duyệt tổ chức";
$fileCssRieng = "pheDuyetTaiKhoanToChuc.css";
$fileJsRieng = "pheDuyetTaiKhoanToChuc.js";
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

    <link rel="stylesheet" href="pheDuyetTaiKhoanToChuc.css">
</head>

<body class="bg-nenChinh text-slate-900 font-sans min-h-screen flex antialiased">

    <!-- 1. Thanh Sidebar dùng chung cố định -->
    <?php include_once '../trangDungChung/layoutDungChung/sidebarDungChung.php'; ?>

    <!-- 2. Cột nội dung trang bên phải -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Thanh Topbar dùng chung -->
        <?php include_once '../trangDungChung/layoutDungChung/topbarDungChung.php'; ?>

        <main class="flex-1 p-6 sm:p-8 space-y-6 max-w-7xl w-full mx-auto">

            <!-- THANH CÔNG CỤ: TÌM KIẾM + LỌC LOẠI TỔ CHỨC + LỌC TRẠNG THÁI YÊU CẦU -->
            <div class="bg-white p-5 rounded-2xl border border-emerald-900/10 bong-xanh">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <!-- Ô tìm kiếm -->
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 text-xs pointer-events-none">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </span>
                        <input type="text" id="oTimKiemToChuc" placeholder="Tìm theo tên tổ chức hoặc mã số thuế..." class="w-full pl-9 pr-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-medium text-slate-900 focus:bg-white focus:border-indigo-700 outline-none">
                    </div>

                    <!-- Lọc theo loại hình tổ chức -->
                    <select id="locLoaiToChuc" class="px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:border-indigo-700 outline-none cursor-pointer">
                        <option value="TAT_CA">-- Tất cả loại hình tổ chức --</option>
                        <option value="NHA_SAN_XUAT">Nhà sản xuất</option>
                        <option value="NHA_PHAN_PHOI">Nhà phân phối</option>
                        <option value="NHA_THUOC">Nhà thuốc</option>
                    </select>

                    <!-- Lọc theo trạng thái yêu cầu (Mặc định: Chờ xử lý) -->
                    <select id="locTrangThaiYeuCau" class="px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:border-indigo-700 outline-none cursor-pointer">
                        <option value="CHO_XU_LY" selected>Chờ xử lý (Chưa duyệt)</option>
                        <option value="DA_DUYET">Đã phê duyệt</option>
                        <option value="TU_CHOI">Bị từ chối</option>
                        <option value="TAT_CA">Toàn bộ trạng thái</option>
                    </select>
                </div>
            </div>

            <!-- BẢNG DANH SÁCH YÊU CẦU ĐĂNG KÝ TỔ CHỨC -->
            <div class="bg-white rounded-2xl border border-emerald-900/10 bong-xanh overflow-hidden">
                <div class="p-4 bg-nenChinh border-b border-emerald-900/10 flex items-center justify-between text-xs">
                    <span class="font-bold text-slate-700 uppercase tracking-wide flex items-center gap-2">
                        <i class="fa-solid fa-building-circle-check text-indigo-700"></i> Danh Sách Hồ Sơ Đăng Ký Tổ Chức
                    </span>
                    <span id="soLuongYeuCauBadge" class="font-bold text-slate-500 font-mono">Hiển thị: 0 hồ sơ</span>
                </div>

                <div class="w-full overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 text-slate-600 uppercase tracking-wider border-b border-slate-100 text-[11px]">
                                <th class="py-3.5 px-3 font-bold">Tên Tổ Chức</th>
                                <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Loại Hình</th>
                                <th class="py-3.5 px-3 font-bold whitespace-nowrap">Mã Số Thuế</th>
                                <th class="py-3.5 px-3 font-bold whitespace-nowrap">Số Giấy Phép Kinh Doanh</th>
                                <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Ngày Nộp</th>
                                <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Lần Nộp</th>
                                <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Trạng Thái</th>
                                <th class="py-3.5 px-3 font-bold text-center whitespace-nowrap">Hành Động</th>
                            </tr>
                        </thead>
                        <tbody id="thanBangYeuCau" class="divide-y divide-slate-100 font-medium">
                            <!-- Render qua JavaScript -->
                        </tbody>
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
    <!-- PANEL XEM HỒ SƠ CHI TIẾT (ĐÃ TINH GỌN HOÀN TOÀN)          -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <div id="modalXemHoSo" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-3 sm:p-5 hidden bg-black/60">
        <div class="bg-white rounded-2xl max-w-5xl w-full p-5 sm:p-7 space-y-4 bong-xanh max-h-[92vh] flex flex-col">

            <!-- Header Panel -->
            <div class="border-b border-slate-100 pb-3 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-2.5 flex-wrap">
                    <span class="text-base font-bold text-slate-900">Hồ Sơ Đăng Ký Tổ Chức:</span>
                    <span id="panelTenToChucTitle" class="font-bold text-indigo-900 bg-indigo-50 px-2.5 py-0.5 rounded text-xs border border-indigo-200"></span>
                    <span id="panelTrangThaiBadge"></span>
                </div>
                <button type="button" class="dong-modal-hoso text-slate-400 hover:text-slate-700 text-lg">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Thẻ thông báo kết quả (Chỉ hiện khi hồ sơ đã xử lý ở chế độ chỉ đọc) -->
            <div id="panelTheKetQuaDaXuLy" class="hidden p-3 rounded-xl border text-xs shrink-0"></div>

            <!-- Body Panel: Cuộn được, chia 2 khối cạnh nhau -->
            <div class="flex-1 overflow-y-auto space-y-4 pr-1 text-xs">

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">

                    <!-- KHỐI BÊN TRÁI: THÔNG TIN PHÁP LÝ KÊ KHAI (ĐÃ BỎ ĐÁNH SỐ 1.) -->
                    <div class="lg:col-span-7 bg-nenChinh p-4 rounded-xl border border-emerald-900/10 space-y-3">
                        <span class="font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2 border-b border-slate-200 pb-2">
                            <i class="fa-solid fa-id-card text-indigo-700"></i> Thông Tin Pháp Lý Kê Khai Hiện Tại
                        </span>

                        <div class="grid grid-cols-2 gap-2.5">
                            <div class="col-span-2">
                                <span class="text-slate-500 block">Tên doanh nghiệp / Cơ sở:</span>
                                <span id="panelTenToChuc" class="font-bold text-slate-900 text-sm"></span>
                            </div>
                            <div>
                                <span class="text-slate-500 block">Loại hình tổ chức:</span>
                                <div id="panelLoaiHinh" class="pt-0.5"></div>
                            </div>
                            <div>
                                <span class="text-slate-500 block">Tên đăng nhập:</span>
                                <span id="panelTenDangNhap" class="font-mono font-bold text-slate-900 text-xs"></span>
                            </div>
                            <div>
                                <span class="text-slate-500 block">Mã số thuế:</span>
                                <span id="panelMaSoThue" class="font-mono font-bold text-slate-900"></span>
                            </div>
                            <div>
                                <span class="text-slate-500 block">Số điện thoại liên hệ:</span>
                                <span id="panelSoDienThoai" class="font-mono font-semibold text-slate-800"></span>
                            </div>
                            <div>
                                <span class="text-slate-500 block">Số giấy phép kinh doanh dược:</span>
                                <span id="panelSoGiayPhep" class="font-mono font-bold text-indigo-800"></span>
                            </div>
                            <div>
                                <span class="text-slate-500 block">Cơ quan cấp giấy phép:</span>
                                <span id="panelCoQuanCap" class="font-semibold text-slate-800"></span>
                            </div>
                            <div>
                                <span class="text-slate-500 block">Ngày cấp phép:</span>
                                <span id="panelNgayCap" class="font-mono text-slate-700"></span>
                            </div>
                            <div>
                                <span class="text-slate-500 block">Ngày hết hạn giấy phép:</span>
                                <div class="flex items-center gap-1.5 flex-wrap pt-0.5">
                                    <span id="panelNgayHetHan" class="font-mono font-bold text-slate-800"></span>
                                    <span id="panelCanhBaoPhepBadge" class="hidden"></span>
                                </div>
                            </div>
                            <div class="col-span-2">
                                <span class="text-slate-500 block">Địa chỉ trụ sở đăng ký:</span>
                                <span id="panelDiaChi" class="font-medium text-slate-800 leading-tight"></span>
                            </div>
                        </div>
                    </div>

                    <!-- KHỐI BÊN PHẢI: HỒ SƠ MINH CHỨNG (ĐÃ BỎ ĐÁNH SỐ 2. VÀ DÒNG CHỮ HƯỚNG DẪN) -->
                    <div class="lg:col-span-5 bg-indigo-50/60 p-4 rounded-xl border border-indigo-200 space-y-3">
                        <div class="flex items-center justify-between border-b border-indigo-200 pb-2">
                            <span class="font-bold text-indigo-950 uppercase tracking-wide flex items-center gap-2">
                                <i class="fa-solid fa-file-shield text-indigo-700"></i> Hồ Sơ Minh Chứng (Căn Cứ Thẩm Định)
                            </span>
                            <span id="panelSoLuongTepMinhChung" class="text-[11px] font-bold text-indigo-800 font-mono">0 tệp tài liệu</span>
                        </div>

                        <!-- Danh sách tệp đính kèm -->
                        <div id="panelDanhSachTepMinhChung" class="space-y-2 max-h-72 overflow-y-auto pr-1">
                            <!-- Render qua JavaScript -->
                        </div>
                    </div>

                </div>

                <!-- KHỐI DƯỚI: LỊCH SỬ CÁC LẦN NỘP (ĐÃ BỎ ĐÁNH SỐ 3. VÀ BỎ GHI CHÚ ĐẾM LẦN) -->
                <div class="border-t border-slate-200 pt-3 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-slate-700 uppercase tracking-wide flex items-center gap-2 text-[11px]">
                            <i class="fa-solid fa-clock-rotate-left text-slate-500"></i> Lịch Sử Các Lần Nộp Hồ Sơ Đăng Ký
                        </span>
                    </div>

                    <div id="panelDanhSachLichSuNop" class="space-y-1.5">
                        <!-- Render qua JavaScript -->
                    </div>
                </div>

            </div>

            <!-- Footer Panel -->
            <div class="border-t border-slate-100 pt-3 flex items-center justify-between shrink-0">
                <button type="button" class="dong-modal-hoso px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition-all">
                    Đóng
                </button>

                <!-- Cụm nút Duyệt / Từ chối (Chỉ hiện khi trạng thái là CHO_XU_LY) -->
                <div id="panelCumNutHanhDong" class="flex items-center gap-2.5">
                    <button type="button" id="panelNutMoModalTuChoi" class="px-5 py-2 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 font-bold rounded-xl text-xs transition-all flex items-center gap-1.5 shadow-2xs">
                        <i class="fa-solid fa-circle-xmark"></i> Từ Chối Hồ Sơ
                    </button>
                    <button type="button" id="panelNutMoModalDuyet" class="px-6 py-2 bg-indigo-700 hover:bg-indigo-800 text-white font-bold rounded-xl text-xs transition-all flex items-center gap-1.5 shadow-md">
                        <i class="fa-solid fa-circle-check"></i> Phê Duyệt Hồ Sơ
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- MODAL 2: XÁC NHẬN PHÊ DUYỆT (CẢNH BÁO GIẤY PHÉP & THỨ TỰ ON-CHAIN) -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <div id="modalXacNhanDuyet" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden bg-black/60">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 sm:p-8 space-y-5 bong-xanh">
            <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-circle-check text-indigo-700"></i> Xác Nhận Phê Duyệt Tổ Chức
                </h3>
                <button type="button" class="dong-modal-duyet text-slate-400 hover:text-slate-700 text-lg">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Form xác nhận & cảnh báo -->
            <div id="manHinhFormDuyet" class="space-y-4 text-xs">

                <!-- Cảnh báo giấy phép hết hạn hoặc sắp hết hạn -->
                <div id="modalDuyetKhungCanhBaoPhep" class="hidden p-3 rounded-xl border text-[11px] leading-relaxed space-y-1"></div>

                <div class="p-3.5 bg-nenChinh rounded-xl border border-emerald-900/10 space-y-1.5">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Tổ chức được phê duyệt:</span>
                        <span id="duyetModalTenToChuc" class="font-bold text-slate-900 text-right"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Mã số thuế:</span>
                        <span id="duyetModalMST" class="font-mono font-bold text-slate-900"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Vai trò được cấp:</span>
                        <span id="duyetModalVaiTro" class="font-bold text-indigo-800"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Tài khoản quản trị:</span>
                        <span id="duyetModalTenDangNhap" class="font-mono font-bold text-slate-900"></span>
                    </div>
                </div>

                <!-- Thứ tự xử lý chuẩn kiến trúc -->
                <div class="p-3 bg-indigo-50 rounded-xl border border-indigo-200 text-indigo-950 space-y-1 leading-relaxed text-[11px]">
                    <div class="font-bold flex items-center gap-1.5 text-indigo-800">
                        <i class="fa-solid fa-list-ol"></i> Trình tự xử lý hệ thống:
                    </div>
                    <ol class="list-decimal list-inside space-y-0.5 text-slate-700 text-[10px]">
                        <li>Khởi tạo ví blockchain (Custodial Wallet Address) cho tổ chức.</li>
                        <li>Ký giao dịch Smart Contract cấp vai trò nghiệp vụ on-chain.</li>
                        <li>Nếu ghi chuỗi thành công, chuyển tài khoản sang <strong>Hoạt động</strong> và tổ chức sang <strong>Đã duyệt</strong>.</li>
                    </ol>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" class="dong-modal-duyet px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-all">
                        Hủy
                    </button>
                    <button type="button" id="nutXacNhanDuyetGhiBlockchain" class="px-6 py-2.5 bg-indigo-700 hover:bg-indigo-800 text-white font-bold rounded-xl shadow-md transition-all flex items-center gap-1.5">
                        <i class="fa-solid fa-shield-halved"></i> Xác Nhận & Cấp Vai Trò
                    </button>
                </div>
            </div>

            <!-- Màn hình trạng thái đang ghi nhận trên blockchain -->
            <div id="manHinhDangGhiBlockchain" class="hidden text-center py-6 space-y-4">
                <div class="w-14 h-14 mx-auto rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-2xl">
                    <i class="fa-solid fa-cube fa-bounce"></i>
                </div>
                <div class="space-y-1">
                    <h4 class="text-sm font-bold text-slate-900">Đang ghi nhận trên blockchain...</h4>
                    <p class="text-xs text-slate-500">Đang khởi tạo ví và phân quyền Smart Contract cho tổ chức.</p>
                </div>
                <div class="text-[10px] text-slate-400 font-mono">Vui lòng không đóng trình duyệt trong khi xác thực...</div>
            </div>

            <!-- Màn hình khi ghi chuỗi thất bại -->
            <div id="manHinhLoiBlockchain" class="hidden text-center py-4 space-y-4">
                <div class="w-12 h-12 mx-auto rounded-full bg-red-100 text-red-600 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div class="space-y-1 text-xs">
                    <h4 class="text-sm font-bold text-red-700">Giao Dịch Blockchain Thất Bại!</h4>
                    <p class="text-slate-600" id="loiBlockchainChiTietText">Lỗi timeout kết nối RPC Polygon Amoy. Trạng thái hồ sơ được giữ nguyên.</p>
                </div>
                <div class="flex items-center justify-center gap-3 pt-2">
                    <button type="button" class="dong-modal-duyet px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs">
                        Đóng
                    </button>
                    <button type="button" id="nutThuLaiGhiBlockchain" class="px-5 py-2 bg-indigo-700 hover:bg-indigo-800 text-white font-bold rounded-xl text-xs flex items-center gap-1.5">
                        <i class="fa-solid fa-rotate-right"></i> Thử Lại
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- MODAL 3: XÁC NHẬN TỪ CHỐI (GIỚI HẠN 500 KÝ TỰ, NÚT SÁNG KHI NHẬP) -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <div id="modalTuChoiHoSo" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden bg-black/60">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 sm:p-8 space-y-5 bong-xanh">
            <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-circle-xmark text-red-600"></i> Từ Chối Hồ Sơ Đăng Ký
                </h3>
                <button type="button" class="dong-modal-tuchoi text-slate-400 hover:text-slate-700 text-lg">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="space-y-4 text-xs">
                <div class="p-3 bg-nenChinh rounded-xl border border-emerald-900/10 space-y-1">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Tổ chức bị từ chối:</span>
                        <span id="tuChoiModalTenToChuc" class="font-bold text-slate-900"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Mã số thuế:</span>
                        <span id="tuChoiModalMST" class="font-mono font-bold text-slate-900"></span>
                    </div>
                </div>

                <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-amber-950 space-y-1 leading-relaxed text-[11px]">
                    <div class="font-bold flex items-center gap-1.5 text-amber-800">
                        <i class="fa-solid fa-circle-exclamation"></i> Quy định từ chối hồ sơ:
                    </div>
                    <ul class="list-disc list-inside space-y-0.5 text-slate-700 text-[10px]">
                        <li>Thao tác từ chối chỉ lưu vào cơ sở dữ liệu off-chain, <strong>không ghi lên blockchain</strong>.</li>
                        <li><strong>Lý do sẽ hiển thị cho tổ chức khi họ đăng nhập</strong> để họ khắc phục và nộp lại hồ sơ bổ sung.</li>
                    </ul>
                </div>

                <!-- Ô nhập lý do từ chối bắt buộc (Tối đa 500 ký tự) -->
                <div class="space-y-1">
                    <div class="flex items-center justify-between">
                        <label for="nhapLyDoTuChoiToChuc" class="font-bold text-slate-700 uppercase tracking-wide block text-[11px]">
                            Lý Do Từ Chối Phê Duyệt <span class="text-red-500">*</span>
                        </label>
                        <span id="demKyTuTuChoi" class="text-[10px] text-slate-400 font-mono">0/500</span>
                    </div>
                    <textarea id="nhapLyDoTuChoiToChuc" maxlength="500" rows="3" required placeholder="Nêu rõ lý do giấy phép hết hạn, mã số thuế không khớp, tệp đính kèm không hợp lệ..." class="w-full p-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl font-medium text-slate-900 focus:bg-white focus:border-red-600 outline-none resize-y"></textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                <button type="button" class="dong-modal-tuchoi px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-all">
                    Hủy
                </button>
                <button type="button" id="nutXacNhanTuChoiOffChain" disabled class="px-6 py-2.5 bg-slate-200 text-slate-400 font-bold rounded-xl transition-all flex items-center gap-1.5 cursor-not-allowed shadow-none">
                    <i class="fa-solid fa-ban"></i> Xác Nhận Từ Chối
                </button>
            </div>
        </div>
    </div>

    <!-- Script thực thi trang -->
    <script src="pheDuyetTaiKhoanToChuc.js"></script>
</body>

</html>
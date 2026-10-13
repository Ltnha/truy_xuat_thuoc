@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/coQuanQuanLy/quanLyQuyenHan.css') }}">
@endpush

@section('content')
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

    <link rel="stylesheet" href="quanLyQuyenHan.css">
</head>

<body class="bg-nenChinh text-slate-900 font-sans min-h-screen flex antialiased">

    <!-- 1. Thanh Sidebar dùng chung cố định -->
    <?php include_once '../trangDungChung/layoutDungChung/sidebarDungChung.php'; ?>

    <!-- 2. Cột nội dung trang bên phải -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Thanh Topbar dùng chung -->
        <?php include_once '../trangDungChung/layoutDungChung/topbarDungChung.php'; ?>

        <main class="flex-1 p-6 sm:p-8 space-y-6 max-w-7xl w-full mx-auto">

            <!-- THANH CÔNG CỤ: TÌM KIẾM + LỌC LOẠI HÌNH + LỌC TRẠNG THÁI + LỌC HẠN GIẤY PHÉP -->
            <div class="bg-white p-5 rounded-2xl border border-emerald-900/10 bong-xanh">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <!-- Ô tìm kiếm -->
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 text-xs pointer-events-none">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </span>
                        <input type="text" id="oTimKiemQuyenHan" placeholder="Tìm theo tên hoặc mã số thuế..." class="w-full pl-9 pr-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-medium text-slate-900 focus:bg-white focus:border-indigo-700 outline-none">
                    </div>

                    <!-- Lọc theo Loại hình -->
                    <select id="locLoaiHinhToChuc" class="px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:border-indigo-700 outline-none cursor-pointer">
                        <option value="TAT_CA">-- Tất cả loại hình --</option>
                        <option value="NHA_SAN_XUAT">Nhà sản xuất</option>
                        <option value="NHA_PHAN_PHOI">Nhà phân phối</option>
                        <option value="NHA_THUOC">Nhà thuốc</option>
                    </select>

                    <!-- Lọc trạng thái phê duyệt -->
                    <select id="locTrangThaiPheDuyet" class="px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:border-indigo-700 outline-none cursor-pointer">
                        <option value="TAT_CA">-- Tất cả trạng thái --</option>
                        <option value="DA_DUYET">Đã duyệt (Đang hoạt động)</option>
                        <option value="BI_THU_HOI">Bị thu hồi</option>
                    </select>

                    <!-- Lọc hạn giấy phép kinh doanh dược -->
                    <select id="locHanGiayPhep" class="px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:border-indigo-700 outline-none cursor-pointer">
                        <option value="TAT_CA">-- Tất cả thời hạn phép --</option>
                        <option value="CON_HAN">Còn hạn (> 90 ngày)</option>
                        <option value="SAP_HET_HAN">Sắp hết hạn (≤ 90 ngày)</option>
                        <option value="HET_HAN">Đã hết hạn</option>
                    </select>
                </div>
            </div>

            <!-- BẢNG DANH SÁCH QUYỀN HẠN TỔ CHỨC -->
            <div class="bg-white rounded-2xl border border-emerald-900/10 bong-xanh overflow-hidden">
                <div class="p-4 bg-nenChinh border-b border-emerald-900/10 flex items-center justify-between text-xs">
                    <span class="font-bold text-slate-700 uppercase tracking-wide flex items-center gap-2">
                        <i class="fa-solid fa-shield-halved text-indigo-700"></i> Giám Sát Quyền Hạn Tổ Chức Tham Gia Mạng Lưới
                    </span>
                    <span id="soLuongToChucBadge" class="font-bold text-slate-500 font-mono">Hiển thị: 0 tổ chức</span>
                </div>

                <div class="w-full overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 text-slate-600 uppercase tracking-wider border-b border-slate-100 text-[11px]">
                                <th class="py-3.5 px-3 font-bold whitespace-nowrap">Tên Tổ Chức</th>
                                <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Loại Hình</th>
                                <th class="py-3.5 px-3 font-bold whitespace-nowrap">Mã Số Thuế</th>
                                <th class="py-3.5 px-3 font-bold whitespace-nowrap">Số Giấy Phép</th>
                                <th class="py-3.5 px-3 font-bold text-center whitespace-nowrap">Hạn Giấy Phép</th>
                                <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Trạng Thái Phê Duyệt</th>
                                <th class="py-3.5 px-3 font-bold text-center whitespace-nowrap">Hành Động</th>
                            </tr>
                        </thead>
                        <tbody id="thanBangQuyenHan" class="divide-y divide-slate-100 font-medium"></tbody>
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
    <!-- MODAL 1: CHI TIẾT QUYỀN HẠN TỔ CHỨC                      -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <div id="modalChiTietToChuc" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-3 sm:p-5 hidden bg-black/60">
        <div class="bg-white rounded-2xl max-w-4xl w-full p-5 sm:p-7 space-y-4 bong-xanh max-h-[92vh] flex flex-col">

            <div class="border-b border-slate-100 pb-3 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-2.5 flex-wrap">
                    <span class="text-base font-bold text-slate-900">Chi Tiết Quyền Hạn Tổ Chức:</span>
                    <span id="detailTenToChucTitle" class="font-bold text-indigo-900 bg-indigo-50 px-2.5 py-0.5 rounded text-xs border border-indigo-200"></span>
                    <span id="detailTrangThaiBadge"></span>
                </div>
                <button type="button" class="dong-modal-detail text-slate-400 hover:text-slate-700 text-lg">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- BANNER ĐỎ NẾU TỔ CHỨC ĐÃ BỊ THU HỒI QUYỀN HẠN -->
            <div id="detailBannerDaThuHoi" class="hidden p-3.5 bg-red-50 border border-red-200 rounded-xl text-xs space-y-1.5 shrink-0">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-red-700 flex items-center gap-1.5">
                        <i class="fa-solid fa-ban text-sm"></i> TỔ CHỨC ĐÃ BỊ THU HỒI QUYỀN HOẠT ĐỘNG
                    </span>
                    <span class="badge-chuan px-2 py-0.5 rounded text-[10px] font-extrabold bg-red-200 text-red-800">Bị thu hồi</span>
                </div>
                <div>
                    <span class="text-slate-500 block text-[11px]">Lý do thu hồi quyền:</span>
                    <p id="detailLyDoThuHoiText" class="font-bold text-red-900 leading-relaxed"></p>
                </div>
                <div class="grid grid-cols-2 gap-2 text-[11px] pt-1 border-t border-red-200 text-slate-600">
                    <div>Người thu hồi: <strong id="detailNguoiThuHoiText" class="font-mono text-slate-800"></strong></div>
                    <div>Thời điểm thu hồi: <strong id="detailThoiGianThuHoiText" class="font-mono text-slate-800"></strong></div>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto space-y-3.5 pr-1 text-xs">

                <!-- KHỐI QUY MÔ HOẠT ĐỘNG (GỌN GÀNG, TÁCH THEO NSX VÀ NPP/NT) -->
                <div class="p-3 bg-indigo-50/60 border border-indigo-200 rounded-xl space-y-2">
                    <span class="font-bold text-indigo-950 uppercase tracking-wide flex items-center gap-1.5 text-[11px]">
                        <i class="fa-solid fa-chart-pie text-indigo-700"></i> Quy Mô Hoạt Động
                    </span>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="p-2.5 bg-white rounded-lg border border-slate-200">
                            <span id="detailNhanSoLo" class="text-slate-500 block text-[11px]">Số lô:</span>
                            <span id="detailGiaTriSoLo" class="font-mono font-bold text-slate-900 text-sm">0 lô</span>
                        </div>
                        <div class="p-2.5 bg-white rounded-lg border border-slate-200">
                            <span class="text-slate-500 block text-[11px]">Số lượng đang giữ (tonKhoLo):</span>
                            <span id="detailSoDonViLuuThong" class="font-mono font-bold text-indigo-700 text-sm">0 hộp</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-3.5 items-start">

                    <!-- THÔNG TIN PHÁP LÝ TỔ CHỨC -->
                    <div class="lg:col-span-7 bg-nenChinh p-3.5 rounded-xl border border-emerald-900/10 space-y-2">
                        <span class="font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2 border-b border-slate-200 pb-1.5 text-[11px]">
                            <i class="fa-solid fa-id-card text-indigo-700"></i> Thông Tin Pháp Lý Doanh Nghiệp
                        </span>

                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div class="col-span-2">
                                <span class="text-slate-500 block">Tên doanh nghiệp:</span>
                                <span id="detailTenToChuc" class="font-bold text-slate-900"></span>
                            </div>
                            <div>
                                <span class="text-slate-500 block">Loại hình:</span>
                                <div id="detailLoaiHinh" class="pt-0.5"></div>
                            </div>
                            <div>
                                <span class="text-slate-500 block">Mã số thuế:</span>
                                <span id="detailMaSoThue" class="font-mono font-bold text-slate-900"></span>
                            </div>
                            <div>
                                <span class="text-slate-500 block">Số giấy phép:</span>
                                <span id="detailSoGiayPhep" class="font-mono font-bold text-indigo-900"></span>
                            </div>
                            <div>
                                <span class="text-slate-500 block">Hạn giấy phép:</span>
                                <div class="flex items-center gap-1.5 flex-wrap pt-0.5">
                                    <span id="detailNgayHetHan" class="font-mono font-bold text-slate-800"></span>
                                    <span id="detailCanhBaoPhepBadge"></span>
                                </div>
                            </div>
                            <div>
                                <span class="text-slate-500 block">Cơ quan cấp:</span>
                                <span id="detailCoQuanCap" class="font-semibold text-slate-800"></span>
                            </div>
                            <div>
                                <span class="text-slate-500 block">Số điện thoại (toChuc.soDienThoai):</span>
                                <span id="detailSoDienThoai" class="font-mono text-slate-800"></span>
                            </div>
                            <div class="col-span-2">
                                <span class="text-slate-500 block">Địa chỉ trụ sở:</span>
                                <span id="detailDiaChi" class="text-slate-800 leading-tight block"></span>
                            </div>
                        </div>

                        <!-- ĐỊA CHỈ VÍ BLOCKCHAIN (RÚT GỌN KÈM COPY & LINK EXPLORER) -->
                        <div class="pt-2 border-t border-slate-200 space-y-1">
                            <span class="text-slate-500 block text-[11px]">Địa chỉ ví định danh on-chain:</span>
                            <div class="flex items-center gap-2 p-1.5 bg-white rounded-lg border border-slate-200">
                                <a id="detailLinkViExplorer" href="#" target="_blank" class="font-mono text-[11px] text-cyan-800 hover:underline flex-1 truncate flex items-center gap-1">
                                    <span id="detailDiaChiViOnChain">0x...</span>
                                    <i class="fa-solid fa-arrow-up-right-from-square text-[9px] text-slate-400 shrink-0"></i>
                                </a>
                                <button type="button" id="nutCopyDiaChiViDetail" class="px-2 py-0.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-[10px] font-bold shrink-0">
                                    <i class="fa-regular fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- CỘT PHẢI: LỊCH SỬ QUYỀN HẠN & HỒ SƠ PHÁP LÝ -->
                    <div class="lg:col-span-5 space-y-3">
                        <!-- LỊCH SỬ QUYỀN HẠN CỦA TỔ CHỨC -->
                        <div class="bg-nenChinh p-3 rounded-xl border border-emerald-900/10 space-y-1.5">
                            <span class="font-bold text-slate-800 uppercase tracking-wide flex items-center gap-1.5 text-[11px] border-b border-slate-200 pb-1">
                                <i class="fa-solid fa-clock-rotate-left text-slate-500"></i> Lịch Sử Quyền Của Tổ Chức
                            </span>
                            <div class="space-y-1 text-slate-700 text-[11px]">
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Ngày phê duyệt:</span>
                                    <strong id="detailNgayDuyetToChuc" class="font-mono text-slate-800"></strong>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Người phê duyệt:</span>
                                    <strong id="detailNguoiDuyetToChuc" class="font-mono text-slate-800"></strong>
                                </div>
                            </div>
                        </div>

                        <!-- HỒ SƠ MINH CHỨNG -->
                        <div class="bg-indigo-50/60 p-3 rounded-xl border border-indigo-200 space-y-1.5">
                            <span class="font-bold text-indigo-950 uppercase tracking-wide flex items-center gap-1.5 text-[11px] border-b border-indigo-200 pb-1">
                                <i class="fa-solid fa-file-shield text-indigo-700"></i> Hồ Sơ Pháp Lý Đính Kèm
                            </span>
                            <div id="detailDanhSachMinhChung" class="space-y-1.5 max-h-36 overflow-y-auto pr-1"></div>
                        </div>
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
    <!-- MODAL 2: THU HỒI QUYỀN HẠN TỔ CHỨC (REVOKE ON-CHAIN)     -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <div id="modalThuHoiQuyen" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden bg-black/60">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 sm:p-8 space-y-5 bong-xanh max-h-[92vh] overflow-y-auto">
            <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-ban text-red-600"></i> Thu Hồi Quyền Hạn Tổ Chức
                </h3>
                <button type="button" class="dong-modal-thuhoi text-slate-400 hover:text-slate-700 text-lg">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Form thu hồi -->
            <div id="manHinhFormThuHoi" class="space-y-4 text-xs">

                <!-- Tóm tắt tổ chức -->
                <div class="p-3.5 bg-nenChinh rounded-xl border border-emerald-900/10 space-y-1.5">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Tổ chức bị thu hồi:</span>
                        <span id="thuHoiModalTenToChuc" class="font-bold text-slate-900 text-right"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Mã số thuế:</span>
                        <span id="thuHoiModalMST" class="font-mono font-bold text-slate-900"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Vai trò hiện tại:</span>
                        <span id="thuHoiModalVaiTro" class="font-bold text-indigo-900"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500">Địa chỉ ví on-chain:</span>
                        <div class="inline-flex items-center gap-1 font-mono font-bold text-cyan-900 text-[11px]">
                            <span id="thuHoiModalDiaChiVi">0x...</span>
                            <button type="button" id="nutCopyViModalThuHoi" class="text-slate-400 hover:text-slate-700 text-[10px] p-0.5">
                                <i class="fa-regular fa-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- CẢNH BÁO CHUYỂN GIAO ĐANG CHỜ XÁC NHẬN (NẾU CÓ) -->
                <div id="thuHoiKhungChuyenGiaoTreo" class="hidden p-3 bg-amber-50 rounded-xl border border-amber-300 text-amber-950 text-[11px] leading-relaxed space-y-1">
                    <div class="font-bold flex items-center gap-1 text-amber-800">
                        <i class="fa-solid fa-triangle-exclamation"></i> CẢNH BÁO CHUYỂN GIAO ĐANG TREO:
                    </div>
                    <p id="thuHoiChuyenGiaoTreoText"></p>
                    <p class="text-[10px] text-amber-900 font-semibold italic">
                        * Giới hạn: Sau khi thu hồi, tổ chức sẽ không thể xác nhận nhận hàng. Các giao dịch chuyển giao đang chờ sẽ do Cơ quan quản lý xử lý thủ công.
                    </p>
                </div>

                <!-- CẢNH BÁO PHẠM VI HẬU QUẢ (ĐẶT LƯU Ý LÊN ĐẦU, NÓI RÕ TÀI KHOẢN VẪN ĐĂNG NHẬP ĐƯỢC) -->
                <div class="p-3 bg-red-50 rounded-xl border border-red-200 text-red-950 space-y-1.5 leading-relaxed text-[11px]">
                    <!-- ĐẶT LÊN ĐẦU KHUNG CẢNH BÁO -->
                    <div class="p-2 bg-white rounded-lg border border-red-300 text-red-800 font-bold text-[11px] flex items-start gap-1.5">
                        <i class="fa-solid fa-circle-exclamation mt-0.5 shrink-0 text-red-600"></i>
                        <span>Lưu ý quan trọng: Thu hồi quyền tổ chức KHÔNG thu hồi các lô thuốc đang lưu thông trên thị trường (việc thu hồi thuốc được xử lý độc lập tại phân hệ Quản lý thu hồi).</span>
                    </div>

                    <ul class="list-disc list-inside space-y-0.5 text-slate-700 text-[10px] pt-1">
                        <li><strong>Vai trò của tổ chức trên blockchain sẽ bị thu hồi.</strong></li>
                        <li>Tổ chức mất quyền thực hiện mọi chức năng nghiệp vụ (không thể tạo lô mới, không thể tiếp nhận chuyển giao, không thể bán lẻ).</li>
                        <li><strong>Phạm vi tài khoản:</strong> Thu hồi chỉ chuyển <code>toChuc.trangThaiPheDuyet</code> thành "Bị thu hồi" và gỡ vai trò on-chain. Các tài khoản người dùng của tổ chức <strong>vẫn đăng nhập được</strong> để xem lý do và tra cứu dữ liệu lịch sử.</li>
                        <li>Dữ liệu và các giao dịch đã xác nhận trước đó vẫn được lưu trữ vĩnh viễn trên sổ cái blockchain.</li>
                    </ul>
                </div>

                <!-- Ô nhập lý do thu hồi bắt buộc (tối đa 500 ký tự) -->
                <div class="space-y-1">
                    <div class="flex items-center justify-between">
                        <label for="nhapLyDoThuHoiQuyen" class="font-bold text-slate-700 uppercase tracking-wide block text-[11px]">
                            Lý Do Thu Hồi Quyền Hạn <span class="text-red-500">*</span>
                        </label>
                        <span id="demKyTuThuHoi" class="text-[10px] text-slate-400 font-mono">0/500</span>
                    </div>
                    <textarea id="nhapLyDoThuHoiQuyen" maxlength="500" rows="3" required placeholder="Nêu rõ căn cứ pháp lý: Giấy phép hết hạn không gia hạn, đình chỉ hoạt động theo quyết định xử phạt..." class="w-full p-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl font-medium text-slate-900 focus:bg-white focus:border-red-600 outline-none resize-y"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" class="dong-modal-thuhoi px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-all">
                        Hủy
                    </button>
                    <button type="button" id="nutXacNhanThuHoiOnChain" disabled class="px-6 py-2.5 bg-slate-200 text-slate-400 font-bold rounded-xl transition-all flex items-center gap-1.5 cursor-not-allowed shadow-none">
                        <i class="fa-solid fa-ban"></i> Xác Nhận Thu Hồi
                    </button>
                </div>
            </div>

            <!-- Màn hình trạng thái đang ghi nhận trên blockchain -->
            <div id="manHinhDangThuHoiBlockchain" class="hidden text-center py-6 space-y-4">
                <div class="w-14 h-14 mx-auto rounded-full bg-red-100 text-red-600 flex items-center justify-center text-2xl">
                    <i class="fa-solid fa-shield-halved fa-bounce"></i>
                </div>
                <div class="space-y-1">
                    <h4 class="text-sm font-bold text-slate-900">Đang ghi nhận trên blockchain...</h4>
                    <p class="text-xs text-slate-500">Đang thực hiện thu hồi vai trò của tổ chức trên Smart Contract.</p>
                </div>
                <div class="text-[10px] text-slate-400 font-mono">Vui lòng chờ xác thực giao dịch on-chain...</div>
            </div>

            <!-- Màn hình khi ghi chuỗi thất bại -->
            <div id="manHinhLoiThuHoiBlockchain" class="hidden text-center py-4 space-y-4">
                <div class="w-12 h-12 mx-auto rounded-full bg-red-100 text-red-600 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div class="space-y-1 text-xs">
                    <h4 class="text-sm font-bold text-red-700">Thu Hồi Vai Trò On-Chain Thất Bại!</h4>
                    <p class="text-slate-600">Lỗi kết nối RPC Node Polygon Amoy. Trạng thái của tổ chức được giữ nguyên.</p>
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

    <script src="quanLyQuyenHan.js"></script>
</body>

</html>

@endsection

@push('scripts')
    <script src="{{ asset('assets/js/coQuanQuanLy/quanLyQuyenHan.js') }}"></script>
@endpush
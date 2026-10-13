@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/nhaThuoc/quanLyBanHang.css') }}">
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

    <link rel="stylesheet" href="quanLyBanHang.css">
</head>

<body class="bg-nenChinh text-slate-900 font-sans min-h-screen flex antialiased">

    <!-- 1. Thanh Sidebar dùng chung cố định (Sticky top-0) -->
    <?php include_once '../trangDungChung/layoutDungChung/sidebarDungChung.php'; ?>

    <!-- 2. Cột nội dung trang bên phải (Tự do cuộn) -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Thanh Topbar dùng chung -->
        <?php include_once '../trangDungChung/layoutDungChung/topbarDungChung.php'; ?>

        <main class="flex-1 p-6 sm:p-8 space-y-6 max-w-7xl w-full mx-auto">

            <!-- ══════════════════════════════════════════════════════════ -->
            <!-- KHỐI 1: CHỌN HÀNG ĐỂ BÁN (TẢI ẢNH QR + NHẬP SERIAL)      -->
            <!-- ══════════════════════════════════════════════════════════ -->
            <section class="bg-white rounded-2xl border border-emerald-900/10 p-5 sm:p-6 bong-xanh space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-sm sm:text-base font-bold text-slate-900 flex items-center gap-2">
                        <i class="fa-solid fa-barcode text-cyan-800"></i> Chọn Thuốc Để Bán Lẻ
                    </h3>
                    <span class="text-[11px] text-slate-500 font-semibold">Tải ảnh mã QR hoặc nhập số serial để kiểm tra</span>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-stretch">

                    <!-- 1. CỤM NHẬN DIỆN MÃ QR: TẢI ẢNH MÃ QR LÊN (ĐÃ BỎ TIA LASER CHUYỂN ĐỘNG) -->
                    <div class="lg:col-span-5 p-4 bg-slate-900 rounded-2xl text-white flex flex-col justify-between shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-cyan-400 flex items-center gap-1.5">
                                <i class="fa-solid fa-qrcode"></i> Nhận Diện Mã QR Hộp Thuốc
                            </span>
                            <span class="text-[10px] text-slate-400">Tải ảnh chụp tem QR</span>
                        </div>

                        <!-- Khung chọn và xem trước ảnh mã QR tải lên (Tĩnh, không chuyển động) -->
                        <div id="khungTaiAnhQR" class="my-3 p-3 bg-slate-800/90 rounded-xl border-2 border-dashed border-cyan-500/50 hover:border-cyan-400 cursor-pointer flex flex-col items-center justify-center transition-colors min-h-[125px]">
                            <input type="file" id="tepAnhQRInput" accept="image/*" class="hidden">

                            <div id="khungChuaChonAnh" class="text-center space-y-1">
                                <i class="fa-solid fa-cloud-arrow-up text-3xl text-cyan-400"></i>
                                <div class="text-xs text-slate-200 font-bold">Nhấp để tải ảnh mã QR lên</div>
                                <p class="text-[10px] text-slate-400">Hỗ trợ ảnh chụp JPG, PNG từ máy ảnh / điện thoại</p>
                            </div>

                            <div id="khungDaChonAnh" class="hidden flex items-center gap-3 w-full">
                                <img id="anhQRPreview" src="" alt="Ảnh QR" class="w-14 h-14 object-cover rounded-lg border border-slate-600 bg-white p-0.5 shrink-0">
                                <div class="flex-1 truncate text-left text-xs">
                                    <span id="tenAnhQRDangChon" class="font-bold text-cyan-300 block truncate"></span>
                                    <span class="text-[10px] text-slate-400">Đã đọc và nhận diện thành công</span>
                                </div>
                                <button type="button" id="nutXoaAnhQR" class="text-slate-400 hover:text-red-400 p-1.5 text-sm" title="Hủy ảnh">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Các nút test nhanh các kịch bản thực tế -->
                        <div class="space-y-1">
                            <span class="text-[10px] text-slate-400 block font-semibold">Hoặc chọn mẫu kiểm tra nhanh:</span>
                            <div class="grid grid-cols-2 gap-1.5 text-[10px] font-bold">
                                <button type="button" class="btn-demo-quet py-1.5 px-2 bg-emerald-700 hover:bg-emerald-600 rounded-lg text-white transition-all text-left truncate" data-serial="UNIT-LOT-PARA-2026-00142">
                                    <i class="fa-solid fa-check mr-1"></i> Hợp lệ (Para)
                                </button>
                                <button type="button" class="btn-demo-quet py-1.5 px-2 bg-slate-700 hover:bg-slate-600 rounded-lg text-slate-200 transition-all text-left truncate" data-serial="UNIT-LOT-PARA-2026-00001">
                                    <i class="fa-solid fa-clock-rotate-left mr-1"></i> Đã bán trước đó
                                </button>
                                <button type="button" class="btn-demo-quet py-1.5 px-2 bg-red-700 hover:bg-red-600 rounded-lg text-white transition-all text-left truncate" data-serial="UNIT-LOT-AMOX-2025-00088">
                                    <i class="fa-solid fa-ban mr-1"></i> Lô đã thu hồi
                                </button>
                                <button type="button" class="btn-demo-quet py-1.5 px-2 bg-amber-700 hover:bg-amber-600 rounded-lg text-white transition-all text-left truncate" data-serial="UNIT-LOT-CEFA-2024-00015">
                                    <i class="fa-solid fa-triangle-exclamation mr-1"></i> Đã hết hạn
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 2. CỤM NHẬP SERIAL THỦ CÔNG & CHỌN TỪ TỒN KHO -->
                    <div class="lg:col-span-7 flex flex-col justify-between space-y-4">
                        <form id="formNhapSerialThuCong" class="space-y-3">
                            <div class="space-y-1">
                                <label for="nhapSerialInput" class="font-bold text-slate-700 text-xs block">
                                    Nhập Mã Serial Đơn Vị (Dùng khi tem QR bị mờ hoặc rách)
                                </label>
                                <div class="flex gap-2">
                                    <div class="relative flex-1">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 text-xs pointer-events-none">
                                            <i class="fa-solid fa-keyboard"></i>
                                        </span>
                                        <input type="text" id="nhapSerialInput" placeholder="VD: UNIT-LOT-PARA-2026-00143..." class="w-full pl-9 pr-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl font-mono text-xs uppercase font-semibold text-slate-900 focus:bg-white focus:border-cyan-700 outline-none">
                                    </div>
                                    <button type="submit" class="px-5 py-2.5 bg-cyan-700 hover:bg-cyan-800 text-white rounded-xl text-xs font-bold transition-all shadow-sm flex items-center gap-1.5 shrink-0">
                                        <i class="fa-solid fa-plus"></i> Kiểm Tra & Thêm
                                    </button>
                                </div>
                            </div>
                        </form>

                        <!-- Thông báo phản hồi trực tiếp khi kiểm tra đơn vị -->
                        <div id="khungThongBaoKiemTra" class="p-3.5 rounded-xl border text-xs leading-relaxed hidden"></div>

                        <!-- Lối tắt cuộn xuống ngăn tồn kho -->
                        <div class="p-3 bg-nenChinh rounded-xl border border-emerald-900/10 flex items-center justify-between text-xs">
                            <span class="text-slate-600 font-medium">Hoặc bạn muốn duyệt tồn kho theo từng lô thuốc?</span>
                            <button type="button" id="nutCuonXuongTonKho" class="px-3.5 py-1.5 bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 rounded-lg font-bold text-[11px] transition-all shadow-2xs flex items-center gap-1.5">
                                <i class="fa-solid fa-arrow-down text-cyan-800"></i> Xem Ngăn Tồn Kho
                            </button>
                        </div>
                    </div>

                </div>
            </section>

            <!-- ══════════════════════════════════════════════════════════ -->
            <!-- KHỐI 2: GIỎ BÁN HÀNG (DANH SÁCH ĐƠN VỊ ĐÃ CHỌN ĐỂ BÁN)   -->
            <!-- ══════════════════════════════════════════════════════════ -->
            <section class="bg-white rounded-2xl border border-emerald-900/10 p-5 sm:p-6 bong-xanh space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm sm:text-base font-bold text-slate-900 flex items-center gap-2">
                            <i class="fa-solid fa-cart-shopping text-cyan-800"></i> Giỏ Bán Hàng Hiện Tại
                        </h3>
                        <span id="badgeTongSoGioHang" class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-cyan-100 text-cyan-800 border border-cyan-300">
                            0 hộp
                        </span>
                    </div>

                    <!-- Nút xác nhận bán -->
                    <button type="button" id="nutXacNhanBanHang" disabled class="px-6 py-2.5 bg-slate-200 text-slate-400 font-bold rounded-xl text-xs transition-all flex items-center gap-2 cursor-not-allowed shadow-none">
                        <i class="fa-solid fa-cash-register"></i> Xác Nhận Bán & Ký Blockchain
                    </button>
                </div>

                <!-- Bảng danh sách đơn vị trong giỏ -->
                <div class="w-full overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 text-slate-600 uppercase tracking-wider border-b border-slate-100 text-[11px]">
                                <th class="py-3 px-3 font-bold whitespace-nowrap">Mã Serial Đơn Vị</th>
                                <th class="py-3 px-3 font-bold">Tên Thuốc</th>
                                <th class="py-3 px-3 font-bold whitespace-nowrap">Mã Lô</th>
                                <th class="py-3 px-2.5 font-bold text-center whitespace-nowrap">Hạn Dùng</th>
                                <th class="py-3 px-2.5 font-bold text-center whitespace-nowrap">Trạng Thái</th>
                                <th class="py-3 px-2.5 font-bold text-center whitespace-nowrap">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody id="thanBangGioHang" class="divide-y divide-slate-100 font-medium">
                            <!-- Render bằng JS -->
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- ══════════════════════════════════════════════════════════ -->
            <!-- KHỐI 3: HAI TAB (TỒN KHO THEO LÔ & LỊCH SỬ BÁN)          -->
            <!-- ══════════════════════════════════════════════════════════ -->
            <section id="khungNganDuoi" class="space-y-4">
                <div class="bg-white rounded-2xl border border-emerald-900/10 bong-xanh p-2 flex gap-2">
                    <button type="button" id="tabTonKhoLo" class="flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all bg-cyan-700 text-white shadow-sm">
                        <i class="fa-solid fa-boxes-stacked text-base"></i>
                        <span>Tồn Kho Theo Lô Tại Nhà Thuốc</span>
                        <span id="badgeSoLuongTonKho" class="ml-1 px-2 py-0.5 rounded-full text-[11px] font-black bg-white text-cyan-800">0</span>
                    </button>

                    <button type="button" id="tabLichSuBan" class="flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all text-slate-600 hover:text-cyan-700 hover:bg-cyan-50">
                        <i class="fa-solid fa-clock-rotate-left text-base"></i>
                        <span>Lịch Sử Đã Xuất Bán (Ghi Nhận Blockchain)</span>
                        <span id="badgeSoLuongDaBan" class="ml-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600">0</span>
                    </button>
                </div>

                <!-- TAB 1: TỒN KHO THEO LÔ -->
                <div id="noiDungTabTonKho" class="bg-white rounded-2xl border border-emerald-900/10 bong-xanh overflow-hidden space-y-4 p-5">
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                        <div class="relative max-w-sm w-full">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 text-xs pointer-events-none">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </span>
                            <input type="text" id="oTimKiemTonKho" placeholder="Tìm theo mã lô hoặc tên thuốc..." class="w-full pl-9 pr-3 py-2 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-medium text-slate-900 focus:bg-white focus:border-cyan-700 outline-none">
                        </div>
                        <div class="text-[11px] text-slate-500 font-medium">
                            * Tồn kho được tổng hợp theo từng mã lô (tính gộp tất cả các đợt tiếp nhận).
                        </div>
                    </div>

                    <div class="w-full overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50 text-slate-600 uppercase tracking-wider border-b border-slate-100 text-[11px]">
                                    <th class="py-3 px-3 font-bold whitespace-nowrap">Mã Lô</th>
                                    <th class="py-3 px-3 font-bold">Sản Phẩm</th>
                                    <th class="py-3 px-2.5 font-bold text-center whitespace-nowrap">Hạn Dùng</th>
                                    <th class="py-3 px-3 font-bold text-right whitespace-nowrap">Đã Nhận</th>
                                    <th class="py-3 px-3 font-bold text-right whitespace-nowrap">Đã Bán</th>
                                    <th class="py-3 px-3 font-bold text-right whitespace-nowrap">Còn Bán Được</th>
                                    <th class="py-3 px-2.5 font-bold text-center whitespace-nowrap">Trạng Thái</th>
                                    <th class="py-3 px-3 font-bold text-center whitespace-nowrap">Hành Động</th>
                                </tr>
                            </thead>
                            <tbody id="thanBangTonKhoLo" class="divide-y divide-slate-100 font-medium">
                                <!-- Render qua JS -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 2: LỊCH SỬ ĐÃ BÁN -->
                <div id="noiDungTabLichSuBan" class="hidden bg-white rounded-2xl border border-emerald-900/10 bong-xanh overflow-hidden space-y-4 p-5">
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                        <div class="relative max-w-sm w-full">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 text-xs pointer-events-none">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </span>
                            <input type="text" id="oTimKiemLichSuBan" placeholder="Tìm theo mã serial, tên thuốc..." class="w-full pl-9 pr-3 py-2 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-medium text-slate-900 focus:bg-white focus:border-cyan-700 outline-none">
                        </div>
                    </div>

                    <div class="w-full overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50 text-slate-600 uppercase tracking-wider border-b border-slate-100 text-[11px]">
                                    <th class="py-3 px-3 font-bold whitespace-nowrap">Mã Serial Đơn Vị</th>
                                    <th class="py-3 px-3 font-bold">Tên Thuốc</th>
                                    <th class="py-3 px-3 font-bold whitespace-nowrap">Mã Lô</th>
                                    <th class="py-3 px-2.5 font-bold text-center whitespace-nowrap">Thời Gian Bán</th>
                                    <th class="py-3 px-3 font-bold whitespace-nowrap">Mã Giao Dịch Blockchain</th>
                                    <th class="py-3 px-2.5 font-bold text-center whitespace-nowrap">Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody id="thanBangLichSuBan" class="divide-y divide-slate-100 font-medium">
                                <!-- Render qua JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- MODAL 1: KẾT QUẢ BÁN HÀNG & GHI NHẬN BLOCKCHAIN TỪNG ĐƠN VỊ -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <div id="modalKetQuaBanHang" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden bg-black/60">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 sm:p-8 space-y-5 bong-xanh max-h-[90vh] overflow-y-auto">
            <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-receipt text-cyan-800"></i> Kết Quả Bán Hàng & Ký Blockchain
                </h3>
                <button type="button" class="dong-modal-ket-qua text-slate-400 hover:text-slate-700 text-lg">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div id="khungTienDoBlockchain" class="p-3.5 bg-nenChinh rounded-xl border border-emerald-900/10 space-y-2">
                <div class="flex justify-between text-xs font-bold">
                    <span class="text-slate-700 flex items-center gap-1.5">
                        <i class="fa-solid fa-circle-check text-cyan-800"></i> Tiến độ ghi nhận on-chain:
                    </span>
                    <span id="textTienDoOnChain" class="font-mono text-cyan-800">0/0 đơn vị</span>
                </div>
                <div class="w-full bg-slate-200 h-2 rounded-full overflow-hidden">
                    <div id="thanhProgressOnChain" class="bg-cyan-700 h-full w-0"></div>
                </div>
            </div>

            <div class="space-y-2">
                <span class="text-xs font-bold text-slate-700 block">Chi tiết trạng thái từng hộp thuốc:</span>
                <div id="danhSachKetQuaDonVi" class="space-y-2 max-h-60 overflow-y-auto pr-1 text-xs"></div>
            </div>

            <div class="pt-2 border-t border-slate-100 flex justify-end">
                <button type="button" id="nutHoanTatGiaoDichBan" class="px-6 py-2.5 bg-cyan-700 hover:bg-cyan-800 text-white text-xs font-bold rounded-xl shadow-md transition-all">
                    Hoàn Tất & Đóng
                </button>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- MODAL 2: CHI TIẾT ĐƠN VỊ THUỐC                            -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <div id="modalChiTietDonVi" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden bg-black/60">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 sm:p-8 space-y-5 bong-xanh">
            <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-base font-bold text-slate-900">Chi Tiết Đơn Vị Thuốc:</span>
                    <span id="modalDonViSerialTitle" class="font-mono font-bold text-cyan-800 bg-cyan-50 px-2 py-0.5 rounded text-xs border border-cyan-200"></span>
                </div>
                <button type="button" class="dong-modal-donvi text-slate-400 hover:text-slate-700 text-lg">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="p-3.5 bg-nenChinh rounded-xl border border-emerald-900/10 space-y-2 text-xs">
                <div class="flex justify-between">
                    <span class="text-slate-500">Mã lô sản xuất:</span>
                    <span id="modalDonViMaLo" class="font-mono font-bold text-slate-900"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Tên thuốc:</span>
                    <span id="modalDonViTenThuoc" class="font-bold text-slate-900"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Hạn sử dụng:</span>
                    <span id="modalDonViHanDung" class="font-semibold text-slate-800 font-mono"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Trạng thái hiện tại:</span>
                    <span id="modalDonViTrangThai"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Thời gian bán:</span>
                    <span id="modalDonViNgayBan" class="font-mono text-slate-700"></span>
                </div>
            </div>

            <div class="space-y-1.5 text-xs">
                <span class="font-bold text-slate-700">Mã giao dịch bán trên blockchain (txHash):</span>
                <div class="flex items-center gap-2 p-2 bg-slate-50 border border-slate-200 rounded-lg">
                    <a id="modalDonViTxLink" href="#" target="_blank" class="flex-1 font-mono text-[11px] text-cyan-800 hover:underline break-all">
                        <span id="modalDonViTxHash">0x...</span>
                        <i class="fa-solid fa-arrow-up-right-from-square text-[9px] text-slate-400"></i>
                    </a>
                    <button type="button" id="nutCopyDonViTx" class="px-2 py-1 bg-white hover:bg-slate-100 border border-slate-300 text-slate-700 rounded text-[11px] font-bold shrink-0">
                        <i class="fa-regular fa-copy"></i>
                    </button>
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                <a id="linkBaoCaoTuDonViModal" href="#" class="text-xs text-amber-700 hover:text-amber-900 font-bold hover:underline flex items-center gap-1.5">
                    <i class="fa-solid fa-triangle-exclamation"></i> Báo cáo nghi vấn đơn vị này
                </a>
                <button type="button" class="dong-modal-donvi px-5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-all">
                    Đóng
                </button>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- MODAL 3: CHI TIẾT TỒN KHO LÔ VÀ DANH SÁCH SERIAL CỦA LÔ    -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <div id="modalChiTietTonKhoLo" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden bg-black/60">
        <div class="bg-white rounded-2xl max-w-2xl w-full p-6 sm:p-8 space-y-5 bong-xanh max-h-[90vh] overflow-y-auto">
            <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-base font-bold text-slate-900">Chi Tiết Tồn Kho Lô:</span>
                    <span id="modalTonKhoMaLoTitle" class="font-mono font-bold text-cyan-800 bg-cyan-50 px-2.5 py-0.5 rounded text-xs border border-cyan-200"></span>
                </div>
                <button type="button" class="dong-modal-tonkho text-slate-400 hover:text-slate-700 text-lg">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div id="modalTonKhoBannerCanhBao" class="hidden p-3.5 rounded-xl border text-xs leading-relaxed space-y-1"></div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-3.5 bg-nenChinh rounded-xl border border-emerald-900/10 text-xs">
                <div>
                    <span class="text-slate-500 block">Sản phẩm:</span>
                    <span id="modalTonKhoSanPham" class="font-bold text-slate-900"></span>
                </div>
                <div>
                    <span class="text-slate-500 block">Hạn dùng:</span>
                    <span id="modalTonKhoHanDung" class="font-semibold text-slate-800 font-mono"></span>
                </div>
                <div>
                    <span class="text-slate-500 block">Tổng đã nhận:</span>
                    <span id="modalTonKhoTongNhan" class="font-bold text-slate-900 font-mono"></span>
                </div>
                <div>
                    <span class="text-slate-500 block">Còn bán được:</span>
                    <span id="modalTonKhoConBan" class="font-bold text-emerald-700 font-mono"></span>
                </div>
            </div>

            <div class="space-y-3 pt-1">
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                    <span class="text-xs font-bold text-slate-700 uppercase tracking-wide">
                        Danh Sách Serial Đơn Vị Của Lô:
                    </span>
                    <div class="flex items-center gap-2">
                        <input type="text" id="oTimKiemSerialLo" placeholder="Tìm theo số serial..." class="px-2.5 py-1.5 bg-nenChinh border border-slate-300 rounded-lg text-xs font-mono outline-none">
                        <select id="locTrangThaiSerialLo" class="px-2 py-1.5 bg-nenChinh border border-slate-300 rounded-lg text-xs font-semibold outline-none cursor-pointer">
                            <option value="TAT_CA">Tất cả</option>
                            <option value="AVAILABLE">Chưa bán</option>
                            <option value="SOLD">Đã bán</option>
                        </select>
                    </div>
                </div>

                <div class="border border-slate-200 rounded-xl overflow-hidden max-h-56 overflow-y-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead class="bg-slate-50 sticky top-0 text-[11px] text-slate-600 border-b border-slate-200">
                            <tr>
                                <th class="p-2.5 font-bold">Mã Serial</th>
                                <th class="p-2.5 font-bold text-center">Trạng Thái</th>
                                <th class="p-2.5 font-bold text-center">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody id="thanBangSerialLo" class="divide-y divide-slate-100 font-mono">
                            <!-- Render bằng JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                <a id="linkBaoCaoTuModalTonKho" href="#" class="text-xs text-amber-700 hover:text-amber-900 font-bold hover:underline flex items-center gap-1.5">
                    <i class="fa-solid fa-triangle-exclamation"></i> Báo cáo nghi vấn lô này
                </a>
                <button type="button" class="dong-modal-tonkho px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-all">
                    Đóng
                </button>
            </div>
        </div>
    </div>

    <script src="quanLyBanHang.js"></script>
</body>

</html>


@endsection

@push('scripts')
    <script src="{{ asset('assets/js/nhaThuoc/quanLyBanHang.js')"></script>
@endpush
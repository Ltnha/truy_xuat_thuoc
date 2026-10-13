<?php
$tieuDeTrang = "Báo Cáo Nghi Vấn Dược Phẩm – PharmaChain";
$fileCssRieng = "baoCaoNghiVan.css";
$fileJsRieng = "baoCaoNghiVan.js";

include_once 'layoutNguoiTieuDung/headerNguoiTieuDung.php';
include_once 'layoutNguoiTieuDung/navbarNguoiTieuDung.php';

// Tự động nhận mã lô nếu được điều hướng từ trang tra cứu thuốc
$maLoNhanDuoc = isset($_GET['maLo']) ? htmlspecialchars($_GET['maLo']) : (isset($_GET['maDinhDanh']) ? htmlspecialchars($_GET['maDinhDanh']) : '');
?>

<main class="flex-1 py-10 px-4 sm:px-6 lg:px-8 max-w-4xl mx-auto w-full space-y-6">

    <!-- TIÊU ĐỀ TRANG CĂN CHỈNH GỌN GÀNG (ĐÃ BỎ DÒNG PHỤ ĐỀ) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-emerald-900/10 pb-5">
        <div class="flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shrink-0 shadow-sm border border-amber-200/60">
                <i class="fa-solid fa-file-shield"></i>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-emerald-700 tracking-tight">
                Báo Cáo Nghi Vấn Dược Phẩm
            </h1>
        </div>

        <a href="traCuuNguonGoc.php" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200/80 text-xs font-bold transition-all shadow-sm self-start sm:self-auto">
            <i class="fa-solid fa-qrcode"></i> Tra Cứu Thuốc
        </a>
    </div>

    <!-- KHỐI CHUYỂN TAB (ĐÃ BỎ CHỮ "TAB 1", "TAB 2") -->
    <div class="bg-white rounded-2xl border border-emerald-900/10 bong-xanh p-2 flex gap-2">
        <button type="button" id="tabGuiBaoCao" class="flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all bg-emerald-700 text-white shadow-sm">
            <i class="fa-solid fa-paper-plane"></i> Gửi Báo Cáo Mới
        </button>
        <button type="button" id="tabLichSuBaoCao" class="flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all text-slate-600 hover:text-emerald-700 hover:bg-emerald-50">
            <i class="fa-solid fa-clock-rotate-left"></i> Lịch Sử Báo Cáo
        </button>
    </div>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- KHUNG GỬI BÁO CÁO MỚI                                     -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <section id="khungTabGuiBaoCao" class="bg-white rounded-2xl border border-emerald-900/10 p-6 sm:p-8 bong-xanh space-y-6">

        <!-- Header form (Đã bỏ dòng chữ 'Không yêu cầu đăng nhập tài khoản') -->
        <div class="border-b border-slate-100 pb-3">
            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-file-pen text-amber-600"></i> Điền Thông Tin Phản Ánh
            </h2>
        </div>

        <form id="formGuiBaoCao" class="space-y-5">
            <!-- 1. Ô nhập mã lô -->
            <div class="space-y-1.5">
                <label for="maLoBaoCao" class="block text-xs font-bold text-slate-700 uppercase tracking-wide">
                    Mã Lô Thuốc Nghi Vấn <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 text-sm pointer-events-none">
                        <i class="fa-solid fa-barcode"></i>
                    </span>
                    <input type="text" id="maLoBaoCao" name="maLo" required value="<?php echo $maLoNhanDuoc; ?>" placeholder="VD: LOT-AMOX-2025-09 hoặc quét trên bao bì..." class="w-full pl-10 pr-4 py-3 bg-nenChinh border border-emerald-900/15 rounded-xl text-sm font-medium text-slate-900 focus:bg-white focus:border-emerald-700 focus:ring-2 focus:ring-emerald-100 outline-none transition-all font-mono">
                </div>
                <p class="text-[11px] text-slate-500">Mã lô thường được in nổi hoặc in mực sắc nét tại mép hộp/vỉ thuốc.</p>
            </div>

            <!-- 2. Ô nhập lý do nghi vấn -->
            <div class="space-y-1.5">
                <label for="lyDoNghiVan" class="block text-xs font-bold text-slate-700 uppercase tracking-wide">
                    Lý Do Nghi Vấn & Mô Tả Chi Tiết <span class="text-red-500">*</span>
                </label>
                <textarea id="lyDoNghiVan" name="lyDo" rows="4" required placeholder="Mô tả cụ thể dấu hiệu bất thường: Bao bì mờ nhòe, tem chống giả bị rách dán đè, vỉ thuốc đổi màu, nơi mua hoặc nghi vấn sửa đổi hạn dùng..." class="w-full p-3.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-sm font-medium text-slate-900 focus:bg-white focus:border-emerald-700 focus:ring-2 focus:ring-emerald-100 outline-none transition-all resize-y"></textarea>
            </div>

            <!-- 3. Ô tải file bằng chứng -->
            <div class="space-y-1.5">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide">
                    Đính Kèm Bằng Chứng Hình Ảnh <span class="text-red-500">*</span>
                </label>
                <div id="khungKeoThaMinhChung" class="border-2 border-dashed border-emerald-900/20 hover:border-emerald-700 rounded-2xl p-6 text-center bg-nenChinh cursor-pointer transition-all">
                    <input type="file" id="tepMinhChung" name="minhChung" accept="image/*" class="hidden">
                    <div class="space-y-2">
                        <i class="fa-solid fa-camera text-3xl text-emerald-700"></i>
                        <div class="text-xs text-slate-700 font-semibold" id="tenTepMinhChung">
                            Chụp ảnh / Kéo thả ảnh bao bì, hóa đơn vào đây hoặc <span class="text-emerald-700 underline font-bold">Duyệt ảnh</span>
                        </div>
                        <p class="text-[11px] text-slate-500">Hỗ trợ định dạng JPG, PNG (tối đa 10MB)</p>
                    </div>
                </div>
            </div>

            <!-- Nút gửi báo cáo -->
            <div class="pt-2">
                <button type="submit" id="nutGuiBaoCaoSubmit" class="w-full py-3.5 bg-amber-600 hover:bg-amber-700 text-white font-bold text-sm rounded-xl shadow-md transition-all flex items-center justify-center gap-2">
                    <i class="fa-solid fa-paper-plane"></i> Gửi Báo Cáo Nghi Vấn
                </button>
            </div>
        </form>
    </section>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- KHUNG XEM LỊCH SỬ BÁO CÁO                                 -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <section id="khungTabLichSuBaoCao" class="hidden space-y-6">

        <!-- Khung tra cứu mã thủ công -->
        <div class="bg-white rounded-2xl border border-emerald-900/10 p-5 bong-xanh space-y-3">
            <label for="oTraCuuThuCong" class="block text-xs font-bold text-slate-700 uppercase tracking-wide">
                <i class="fa-solid fa-magnifying-glass text-cyan-800 mr-1"></i> Tra Cứu Báo Cáo Thủ Công (Khi đổi thiết bị)
            </label>
            <div class="flex flex-col sm:flex-row gap-3">
                <input type="text" id="oTraCuuThuCong" placeholder="Nhập mã báo cáo đã lưu (VD: BC-2026-9481)..." class="flex-1 px-4 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-sm font-mono font-semibold text-slate-900 focus:bg-white focus:border-emerald-700 outline-none transition-all uppercase">
                <button type="button" id="nutTraCuuThuCong" class="px-6 py-2.5 bg-cyan-800 hover:bg-cyan-900 text-white text-xs font-bold rounded-xl transition-all shadow-sm flex items-center justify-center gap-2">
                    <i class="fa-solid fa-search"></i> Kiểm Tra Mã
                </button>
            </div>
        </div>

        <!-- Bảng danh sách báo cáo đã gửi -->
        <div class="bg-white rounded-2xl border border-emerald-900/10 bong-xanh overflow-hidden">
            <div class="p-4 bg-nenChinh border-b border-emerald-900/10 flex items-center justify-between">
                <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wide flex items-center gap-2">
                    <i class="fa-solid fa-list-check text-emerald-700"></i> Báo Cáo Đã Gửi Trên Thiết Bị Này
                </h3>
                <span id="soLuongBaoCaoBadge" class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800">0</span>
            </div>

            <div id="khungBangBaoCao" class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 uppercase tracking-wider border-b border-slate-100">
                            <th class="py-3.5 px-4 font-bold">Mã Báo Cáo</th>
                            <th class="py-3.5 px-4 font-bold">Mã Lô Thuốc</th>
                            <th class="py-3.5 px-4 font-bold">Trạng Thái Xử Lý</th>
                            <th class="py-3.5 px-4 font-bold">Thời Điểm Gửi</th>
                            <th class="py-3.5 px-4 font-bold text-center">Chi Tiết</th>
                        </tr>
                    </thead>
                    <tbody id="thanBangBaoCao" class="divide-y divide-slate-100 font-medium">
                        <!-- Render dòng qua JS -->
                    </tbody>
                </table>
            </div>

            <div id="khungBaoCaoRong" class="hidden p-12 text-center space-y-3">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center text-2xl">
                    <i class="fa-regular fa-clipboard"></i>
                </div>
                <h4 class="text-sm font-bold text-slate-800">Chưa có báo cáo nghi vấn nào được lưu trên máy</h4>
                <p class="text-xs text-slate-500 max-w-sm mx-auto">Khi bạn gửi báo cáo nghi vấn về lô thuốc bất thường, mã tra cứu sẽ được ghi nhận tại đây.</p>
            </div>
        </div>

    </section>

</main>

<!-- ══════════════════════════════════════════════════════════ -->
<!-- MODAL THÀNH CÔNG: SAO CHÉP MÃ & NÚT TẢI PHIẾU LƯU MÃ .TXT  -->
<!-- ══════════════════════════════════════════════════════════ -->
<div id="modalThanhCongBaoCao" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden bg-black/60">
    <div class="bg-white rounded-2xl max-w-md w-full p-8 text-center space-y-5 bong-xanh">
        <div class="w-16 h-16 mx-auto rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-3xl">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <div class="space-y-2">
            <h3 class="text-xl font-bold text-slate-900">Gửi Báo Cáo Thành Công!</h3>
            <p class="text-xs text-slate-600">Hệ thống đã tiếp nhận thông tin phản ánh và chuyển tiếp đến Cơ quan quản lý Dược.</p>

            <!-- Hộp mã báo cáo nổi bật -->
            <div class="p-4 bg-nenChinh rounded-xl border border-emerald-900/15 my-3 space-y-2.5">
                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Mã Báo Cáo Của Bạn</div>
                <div id="maBaoCaoSinhRa" class="text-2xl font-black font-mono text-cyan-800 tracking-wider">BC-2026-XXXX</div>

                <!-- Cụm nút: Sao chép mã & Tải phiếu lưu về máy -->
                <div class="flex items-center justify-center gap-2 pt-1 flex-wrap">
                    <button type="button" id="nutSaoChepMa" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-emerald-50 text-emerald-700 rounded-lg border border-emerald-300 text-[11px] font-bold transition-all shadow-2xs">
                        <i class="fa-regular fa-copy"></i> Sao chép mã
                    </button>
                    <button type="button" id="nutTaiPhieuMa" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-cyan-50 hover:bg-cyan-100 text-cyan-800 rounded-lg border border-cyan-300 text-[11px] font-bold transition-all shadow-2xs">
                        <i class="fa-solid fa-download"></i> Tải phiếu lưu mã (.txt)
                    </button>
                </div>
            </div>

            <!-- Dòng nhắc nhở -->
            <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-800 text-left flex items-start gap-2">
                <i class="fa-solid fa-triangle-exclamation text-amber-600 mt-0.5 shrink-0"></i>
                <span><strong>Lưu ý:</strong> Vui lòng lưu lại mã này hoặc tải file phiếu lưu để tra cứu kết quả xử lý khi đổi thiết bị.</span>
            </div>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="button" id="nutChuyenSangLichSu" class="flex-1 py-3 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-xl shadow-md transition-all">
                Xem Trong Lịch Sử
            </button>
            <button type="button" id="nutDongModalThanhCong" class="py-3 px-5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-all">
                Đóng
            </button>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!-- MODAL XEM CHI TIẾT BÁO CÁO & KẾT QUẢ XỬ LÝ                -->
<!-- ══════════════════════════════════════════════════════════ -->
<div id="modalChiTietBaoCao" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden bg-black/60">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 sm:p-8 space-y-5 bong-xanh max-h-[90vh] overflow-y-auto">

        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-sm font-bold text-slate-900">Chi Tiết Báo Cáo:</span>
                <span id="chiTietMaBaoCao" class="font-mono font-bold text-cyan-800 bg-cyan-50 px-2 py-0.5 rounded text-xs"></span>
            </div>
            <button type="button" id="nutDongChiTiet" class="text-slate-400 hover:text-slate-700 text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="space-y-4 text-xs">
            <div class="grid grid-cols-2 gap-3 p-3 bg-nenChinh rounded-xl border border-emerald-900/10">
                <div>
                    <span class="text-slate-500 block">Lô thuốc phản ánh:</span>
                    <span id="chiTietMaLo" class="font-mono font-bold text-slate-900 text-sm"></span>
                </div>
                <div>
                    <span class="text-slate-500 block">Thời điểm gửi:</span>
                    <span id="chiTietNgayGui" class="font-semibold text-slate-700"></span>
                </div>
            </div>

            <div>
                <span class="text-slate-500 font-bold block mb-1">Trạng thái hiện tại:</span>
                <div id="chiTietTrangThaiBadge"></div>
            </div>

            <div>
                <span class="text-slate-500 font-bold block mb-1">Nội dung phản ánh:</span>
                <p id="chiTietLyDo" class="p-3 bg-slate-50 rounded-xl text-slate-800 leading-relaxed border border-slate-100"></p>
            </div>

            <div>
                <span class="text-slate-500 font-bold block mb-1">Hình ảnh minh chứng đính kèm:</span>
                <div id="chiTietAnhMinhChung" class="w-full h-44 rounded-xl border border-slate-200 bg-slate-100 flex items-center justify-center overflow-hidden">
                    <!-- Ảnh hiển thị qua JS -->
                </div>
            </div>

            <!-- Kết quả xử lý từ Cơ quan quản lý -->
            <div id="khungKetQuaXuLy" class="border-t border-slate-100 pt-3 space-y-2">
                <span class="text-slate-700 font-bold block flex items-center gap-1.5 text-xs">
                    <i class="fa-solid fa-building-columns text-cyan-800"></i> Kết Quả Xử Lý Của Cơ Quan Quản Lý:
                </span>
                <div id="noiDungKetQuaXuLy" class="p-3.5 rounded-xl border text-xs leading-relaxed">
                    <!-- Quyết định Thu hồi / Từ chối render qua JS -->
                </div>
            </div>
        </div>

        <div class="pt-2">
            <button type="button" id="nutDongChiTietBottom" class="w-full py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-all">
                Đóng Cửa Sổ
            </button>
        </div>
    </div>
</div>

<?php include_once 'layoutNguoiTieuDung/footerNguoiTieuDung.php'; ?>
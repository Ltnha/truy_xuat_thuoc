<?php
$tieuDeTrang = "Lịch Sử Tra Cứu – PharmaChain";
$fileCssRieng = "lichSuTraCuu.css";
$fileJsRieng = "lichSuTraCuu.js";

include_once 'layoutNguoiTieuDung/headerNguoiTieuDung.php';
include_once 'layoutNguoiTieuDung/navbarNguoiTieuDung.php';
?>

<main class="flex-1 py-10 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto w-full space-y-6">

    <!-- TIÊU ĐỀ TRANG CĂN CHỈNH MỚI + CỤM NÚT HÀNH ĐỘNG -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-emerald-900/10 pb-5">
        <!-- Tiêu đề trang (gọn gàng, không còn phụ đề) -->
        <div class="flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-xl shrink-0 shadow-sm border border-emerald-900/10">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-emerald-700 tracking-tight">
                Lịch Sử Tra Cứu
            </h1>
        </div>

        <!-- Cụm nút hành động bên phải -->
        <div class="flex items-center gap-3">
            <!-- Nút Quay lại trang tra cứu -->
            <a href="traCuuNguonGoc.php" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200/80 text-xs font-bold transition-all shadow-sm">
                <i class="fa-solid fa-arrow-left"></i> Quay Lại Tra Cứu
            </a>

            <!-- Nút Xóa lịch sử (Desktop) -->
            <button type="button" id="nutXoaLichSu" class="hidden sm:inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-red-300 text-red-600 hover:bg-red-50 text-xs font-bold transition-all shadow-sm">
                <i class="fa-solid fa-trash-can"></i> Xóa Lịch Sử
            </button>
        </div>
    </div>

    <!-- BANNER CHÚ THÍCH CƠ CHẾ BẢO VỆ DỮ LIỆU -->
    <div class="p-4 rounded-xl bg-cyan-50 border border-cyan-800/15 flex items-start gap-3 bong-xanh">
        <i class="fa-solid fa-circle-info text-cyan-800 text-base mt-0.5 shrink-0"></i>
        <div class="text-xs text-cyan-900 leading-relaxed">
            <strong>Cơ chế cập nhật trạng thái thời gian thực:</strong> Ứng dụng chỉ lưu mã tham chiếu trong bộ nhớ cục bộ (<code class="font-mono font-bold">localStorage</code>). Mỗi lần bạn mở trang này, hệ thống sẽ tự động gọi đối soát lại trực tiếp với Smart Contract nhằm đảm bảo nếu lô thuốc vừa có lệnh thu hồi thì bạn sẽ được cảnh báo ngay lập tức.
        </div>
    </div>

    <!-- KHUNG CHỨA DANH SÁCH LỊCH SỬ HOẶC THÔNG BÁO RỖNG -->
    <div class="bg-white rounded-2xl border border-emerald-900/10 bong-xanh overflow-hidden">

        <!-- Trạng thái đang tải dữ liệu (Loading skeleton) -->
        <div id="khungDangTai" class="p-8 text-center space-y-3">
            <i class="fa-solid fa-circle-notch fa-spin text-3xl text-emerald-700"></i>
            <p class="text-xs font-semibold text-slate-600">Đang đối soát trạng thái mới nhất từ Blockchain...</p>
        </div>

        <!-- Bảng danh sách khi có dữ liệu -->
        <div id="khungBangLichSu" class="hidden overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-nenChinh text-slate-600 uppercase tracking-wider border-b border-emerald-900/10">
                        <th class="py-3.5 px-4 font-bold">Tên Thuốc / Sản Phẩm</th>
                        <th class="py-3.5 px-4 font-bold">Mã Đã Tra</th>
                        <th class="py-3.5 px-4 font-bold">Trạng Thái Hiện Tại</th>
                        <th class="py-3.5 px-4 font-bold">Thời Điểm Đã Tra</th>
                        <th class="py-3.5 px-4 font-bold text-center">Hành Động</th>
                    </tr>
                </thead>
                <tbody id="thanBangLichSu" class="divide-y divide-slate-100 font-medium">
                    <!-- Các dòng sẽ được JavaScript render tại đây -->
                </tbody>
            </table>
        </div>

        <!-- MÀN HÌNH THÔNG BÁO KHI DANH SÁCH RỖNG -->
        <div id="khungLichSuRong" class="hidden p-12 text-center space-y-4">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-3xl">
                <i class="fa-regular fa-folder-open"></i>
            </div>
            <div class="space-y-1">
                <h3 class="text-base font-bold text-slate-800">Chưa có lịch sử tra cứu nào trên thiết bị này</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto">
                    Bạn chưa thực hiện quét mã QR hay nhập mã lô thuốc nào trên trình duyệt. Mọi lượt kiểm tra của bạn sẽ xuất hiện tại đây.
                </p>
            </div>
            <div class="pt-2">
                <a href="traCuuNguonGoc.php" class="inline-flex items-center gap-2 px-6 py-3 bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs rounded-xl shadow-md transition-all">
                    <i class="fa-solid fa-qrcode"></i> Tra Cứu Thuốc Ngay
                </a>
            </div>
        </div>

    </div>

    <!-- Nút Xóa lịch sử dành riêng cho màn hình Mobile (nằm cuối trang) -->
    <div class="sm:hidden text-center">
        <button type="button" id="nutXoaLichSuMobile" class="hidden w-full py-3 rounded-xl border border-red-300 text-red-600 hover:bg-red-50 text-xs font-bold transition-all shadow-sm">
            <i class="fa-solid fa-trash-can mr-1.5"></i> Xóa Tất Cả Lịch Sử
        </button>
    </div>

</main>

<!-- MODAL XÁC NHẬN XÓA LỊCH SỬ -->
<div id="modalXacNhanXoa" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden bg-black/60">
    <div class="bg-white rounded-2xl max-w-sm w-full p-6 text-center space-y-4 bong-xanh">
        <div class="w-12 h-12 mx-auto rounded-full bg-red-100 text-red-600 flex items-center justify-center text-xl">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div class="space-y-1">
            <h3 class="text-base font-bold text-slate-900">Xóa Lịch Sử Tra Cứu?</h3>
            <p class="text-xs text-slate-500">
                Toàn bộ danh sách các lần tra cứu được lưu cục bộ trên thiết bị này sẽ bị xóa vĩnh viễn. Bạn có chắc chắn muốn xóa?
            </p>
        </div>
        <div class="flex items-center gap-3 pt-2">
            <button type="button" id="nutHuyXoa" class="flex-1 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-all">
                Hủy Bỏ
            </button>
            <button type="button" id="nutDongYXoa" class="flex-1 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-xl transition-all shadow-sm">
                Xác Nhận Xóa
            </button>
        </div>
    </div>
</div>

<?php include_once 'layoutNguoiTieuDung/footerNguoiTieuDung.php'; ?>
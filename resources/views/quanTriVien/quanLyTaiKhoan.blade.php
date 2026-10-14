@extends('layoutDungChung.master')

@section('title', 'Quản lý tài khoản - PharmaChain')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/quanTriVien/quanLyTaiKhoan.css') }}">
@endpush

@section('content')
<div class="space-y-6 max-w-7xl w-full mx-auto">

    <!-- THANH CÔNG CỤ: TÌM KIẾM + LỌC 5 VAI TRÒ + LỌC ĐỦ 4 TRẠNG THÁI -->
    <div class="bg-white p-5 rounded-2xl border border-emerald-900/10 bong-xanh">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <!-- Ô tìm kiếm -->
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 text-xs pointer-events-none">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" id="oTimKiemTaiKhoan" placeholder="Tìm theo tên đăng nhập, email, tổ chức..." class="w-full pl-9 pr-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-medium text-slate-900 focus:bg-white focus:border-slate-800 outline-none">
            </div>

            <!-- Lọc theo 5 vai trò -->
            <select id="locVaiTro" class="px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-medium text-slate-700 focus:bg-white focus:border-slate-800 outline-none cursor-pointer">
                <option value="TAT_CA">-- Tất cả vai trò (5 vai trò) --</option>
                <option value="QUAN_TRI_VIEN">Quản trị viên</option>
                <option value="CO_QUAN_QUAN_LY">Cơ quan quản lý</option>
                <option value="NHA_SAN_XUAT">Nhà sản xuất</option>
                <option value="NHA_PHAN_PHOI">Nhà phân phối</option>
                <option value="NHA_THUOC">Nhà thuốc</option>
            </select>

            <!-- Lọc đủ 4 trạng thái tài khoản thuần Việt -->
            <select id="locTrangThaiTK" class="px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-medium text-slate-700 focus:bg-white focus:border-slate-800 outline-none cursor-pointer">
                <option value="TAT_CA">-- Tất cả trạng thái --</option>
                <option value="ACTIVE">Hoạt động</option>
                <option value="LOCKED">Đã khóa</option>
                <option value="PENDING">Chờ xác minh</option>
                <option value="REVOKED">Bị thu hồi</option>
            </select>
        </div>
    </div>

    <!-- BẢNG DANH SÁCH TÀI KHOẢN (VỪA KHÍT 100%, KHÔNG BỊ TRÀN NGANG) -->
    <div class="bg-white rounded-2xl border border-emerald-900/10 bong-xanh overflow-hidden">
        <div class="p-4 bg-nenChinh border-b border-emerald-900/10 flex items-center justify-between text-xs">
            <span class="font-bold text-slate-700 uppercase tracking-wide flex items-center gap-2">
                <i class="fa-solid fa-users-gear text-slate-700"></i> Danh Sách Tài Khoản Người Dùng Toàn Mạng Lưới
            </span>
            <span id="soLuongTaiKhoanBadge" class="font-bold text-slate-500 font-mono">Hiển thị: 0 tài khoản</span>
        </div>

        <div class="w-full overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 uppercase tracking-wider border-b border-slate-100 text-[11px]">
                        <th class="py-3.5 px-3 font-bold whitespace-nowrap">Tên Đăng Nhập</th>
                        <th class="py-3.5 px-3 font-bold whitespace-nowrap">Email</th>
                        <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Vai Trò</th>
                        <th class="py-3.5 px-3 font-bold whitespace-nowrap">Tổ Chức</th>
                        <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Trạng Thái Tài Khoản</th>
                        <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Trạng Thái Tổ Chức</th>
                        <th class="py-3.5 px-3 font-bold text-center whitespace-nowrap">Hành Động</th>
                    </tr>
                </thead>
                <tbody id="thanBangTaiKhoan" class="divide-y divide-slate-100 font-medium">
                    <!-- Render qua JavaScript -->
                </tbody>
            </table>
        </div>

        <!-- Phân trang -->
        <div class="p-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
            <span id="thongTinPhanTrangTK" class="text-slate-500">Đang hiển thị...</span>
            <div class="flex items-center gap-1.5" id="cumNutPhanTrangTK"></div>
        </div>
    </div>

</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!-- MODAL 1: KHÓA TÀI KHOẢN (FR13)                           -->
<!-- ══════════════════════════════════════════════════════════ -->
<div id="modalKhoaTaiKhoan" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden bg-black/60">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 sm:p-8 space-y-5 bong-xanh">
        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-user-lock text-red-600"></i> Xác Nhận Khóa Tài Khoản
            </h3>
            <button type="button" class="dong-modal-khoa text-slate-400 hover:text-slate-700 text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="space-y-4 text-xs">
            <!-- Tóm tắt tài khoản -->
            <div class="p-3.5 bg-nenChinh rounded-xl border border-emerald-900/10 space-y-1.5">
                <div class="flex justify-between">
                    <span class="text-slate-500">Tên đăng nhập:</span>
                    <span id="khoaTenDangNhap" class="font-bold text-slate-900 font-mono text-sm"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Vai trò:</span>
                    <span id="khoaVaiTro" class="font-semibold text-slate-800"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Tổ chức trực thuộc:</span>
                    <span id="khoaToChuc" class="font-semibold text-slate-800"></span>
                </div>
            </div>

            <!-- Cảnh báo hệ quả -->
            <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-amber-900 space-y-1 leading-relaxed text-[11px]">
                <div class="font-bold flex items-center gap-1.5 text-amber-800">
                    <i class="fa-solid fa-triangle-exclamation"></i> Cảnh báo hệ quả khi khóa:
                </div>
                <ul class="list-disc list-inside space-y-0.5 text-slate-700 text-[10px]">
                    <li>Tài khoản sẽ bị ngắt quyền đăng nhập ngay lập tức, phiên làm việc đang mở mất hiệu lực.</li>
                    <li>Dữ liệu lịch sử và các giao dịch chuyển giao đang chờ <strong>không bị ảnh hưởng</strong>.</li>
                    <li>Việc này <strong>không thay đổi</strong> quyền hoặc trạng thái của tổ chức (quyền do Cơ quan quản lý quản lý).</li>
                    <li>Khóa một tài khoản <strong>không ảnh hưởng đến các tài khoản khác</strong> của cùng tổ chức.</li>
                </ul>
            </div>

            <!-- Biến thể cảnh báo mạnh riêng cho Cơ quan quản lý -->
            <div id="canhBaoManhCoQuanQuanLy" class="hidden p-3 bg-red-50 rounded-xl border border-red-300 text-red-900 space-y-1 leading-relaxed text-[11px]">
                <div class="font-bold flex items-center gap-1.5 text-red-700">
                    <i class="fa-solid fa-shield-cat text-sm"></i> CẢNH BÁO ĐẶC BIỆT: TÀI KHOẢN CƠ QUAN QUẢN LÝ
                </div>
                <p class="text-[10px] text-red-800" id="canhBaoManhCoQuanQuanLyText">
                    Khóa tài khoản này sẽ chặn các thao tác duyệt, từ chối và thu hồi mà tài khoản đảm nhận!
                </p>
            </div>

            <!-- Ô lý do khóa bắt buộc (tối đa 500 ký tự) -->
            <div class="space-y-1">
                <div class="flex items-center justify-between">
                    <label for="nhapLyDoKhoa" class="font-bold text-slate-700 uppercase tracking-wide block text-[11px]">
                        Lý Do Khóa Tài Khoản <span class="text-red-500">*</span>
                    </label>
                    <span id="demKyTuKhoa" class="text-[10px] text-slate-400 font-mono">0/500</span>
                </div>
                <textarea id="nhapLyDoKhoa" maxlength="500" rows="3" required placeholder="Nhập lý do cụ thể để ghi nhận vào Nhật ký hệ thống..." class="w-full p-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl font-medium text-slate-900 focus:bg-white focus:border-slate-800 outline-none resize-y"></textarea>
                <p class="text-[10px] text-slate-400 italic">Lý do sẽ được lưu vĩnh viễn vào Nhật ký hệ thống để phục vụ đối soát.</p>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
            <button type="button" class="dong-modal-khoa px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-all">
                Hủy
            </button>
            <button type="button" id="nutXacNhanKhoa" disabled class="px-6 py-2.5 bg-slate-200 text-slate-400 font-bold rounded-xl transition-all flex items-center gap-1.5 cursor-not-allowed shadow-none">
                <i class="fa-solid fa-lock"></i> Xác nhận khóa
            </button>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!-- MODAL 2: MỞ KHÓA TÀI KHOẢN (FR13)                         -->
<!-- ══════════════════════════════════════════════════════════ -->
<div id="modalMoKhoaTaiKhoan" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden bg-black/60">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 sm:p-8 space-y-5 bong-xanh">
        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-lock-open text-emerald-700"></i> Xác Nhận Mở Khóa Tài Khoản
            </h3>
            <button type="button" class="dong-modal-mokhoa text-slate-400 hover:text-slate-700 text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="space-y-4 text-xs">
            <!-- Tóm tắt tài khoản -->
            <div class="p-3.5 bg-nenChinh rounded-xl border border-emerald-900/10 space-y-1.5">
                <div class="flex justify-between">
                    <span class="text-slate-500">Tên đăng nhập:</span>
                    <span id="moKhoaTenDangNhap" class="font-bold text-slate-900 font-mono text-sm"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Vai trò:</span>
                    <span id="moKhoaVaiTro" class="font-semibold text-slate-800"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Tổ chức trực thuộc:</span>
                    <span id="moKhoaToChuc" class="font-semibold text-slate-800"></span>
                </div>
            </div>

            <!-- Thông tin lần khóa trước -->
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-[11px] space-y-1">
                <div class="font-bold text-slate-700 flex items-center gap-1.5">
                    <i class="fa-solid fa-clock-rotate-left text-slate-500"></i> Căn cứ lần khóa trước đó:
                </div>
                <div class="text-slate-600">Lý do khóa: <strong id="moKhoaLyDoCuText" class="text-slate-800 font-semibold"></strong></div>
                <div class="text-[10px] text-slate-500 flex justify-between pt-0.5">
                    <span>Người khóa: <strong id="moKhoaNguoiKhoaCuText"></strong></span>
                    <span id="moKhoaThoiGianCuText" class="font-mono"></span>
                </div>
            </div>

            <!-- Lời dẫn -->
            <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-200 text-emerald-900 text-[11px] leading-relaxed">
                <i class="fa-solid fa-circle-check text-emerald-700 mr-1"></i> Tài khoản sẽ được chuyển lại trạng thái <strong>Hoạt động</strong> và đăng nhập được. Quyền thực hiện chức năng nghiệp vụ vẫn theo trạng thái của tổ chức (do Cơ quan quản lý quản lý).
            </div>

            <!-- Banner cảnh báo nếu tổ chức đang ở trạng thái BỊ THU HỒI -->
            <div id="moKhoaCanhBaoToChucThuHoi" class="hidden p-3 bg-amber-50 rounded-xl border border-amber-300 text-amber-950 text-[11px] leading-relaxed space-y-1">
                <div class="font-bold flex items-center gap-1.5 text-amber-800">
                    <i class="fa-solid fa-triangle-exclamation"></i> LƯU Ý VỀ QUYỀN HẠN TỔ CHỨC:
                </div>
                <p>
                    Tổ chức của tài khoản này hiện đang ở trạng thái <strong>Bị thu hồi</strong>. Sau khi mở khóa, tài khoản chỉ có thể đăng nhập xem dữ liệu lịch sử chứ <strong>không thể thực hiện các thao tác giao dịch nghiệp vụ</strong> cho đến khi Cơ quan quản lý phục hồi quyền cho tổ chức.
                </p>
            </div>

            <!-- Ô lý do mở khóa bắt buộc -->
            <div class="space-y-1">
                <div class="flex items-center justify-between">
                    <label for="nhapLyDoMoKhoa" class="font-bold text-slate-700 uppercase tracking-wide block text-[11px]">
                        Lý Do Mở Khóa Tài Khoản <span class="text-red-500">*</span>
                    </label>
                    <span id="demKyTuMoKhoa" class="text-[10px] text-slate-400 font-mono">0/500</span>
                </div>
                <textarea id="nhapLyDoMoKhoa" maxlength="500" rows="3" required placeholder="Nhập lý do hoặc căn cứ mở khóa để ghi nhận vào Nhật ký hệ thống..." class="w-full p-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl font-medium text-slate-900 focus:bg-white focus:border-slate-800 outline-none resize-y"></textarea>
                <p class="text-[10px] text-slate-400 italic">Lý do sẽ được lưu vĩnh viễn vào Nhật ký hệ thống.</p>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
            <button type="button" class="dong-modal-mokhoa px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-all">
                Hủy
            </button>
            <button type="button" id="nutXacNhanMoKhoa" disabled class="px-6 py-2.5 bg-slate-200 text-slate-400 font-bold rounded-xl transition-all flex items-center gap-1.5 cursor-not-allowed shadow-none">
                <i class="fa-solid fa-lock-open"></i> Xác nhận mở khóa
            </button>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!-- MODAL 3: CHI TIẾT TÀI KHOẢN & HOẠT ĐỘNG GẦN ĐÂY          -->
<!-- ══════════════════════════════════════════════════════════ -->
<div id="modalChiTietTaiKhoan" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden bg-black/60">
    <div class="bg-white rounded-2xl max-w-2xl w-full p-6 sm:p-8 space-y-5 bong-xanh max-h-[90vh] overflow-y-auto">
        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-base font-bold text-slate-900">Chi Tiết Tài Khoản:</span>
                <span id="detailTenDangNhapTitle" class="font-mono font-bold text-slate-900 bg-slate-100 px-2.5 py-0.5 rounded text-xs border border-slate-200"></span>
            </div>
            <button type="button" class="dong-modal-detail text-slate-400 hover:text-slate-700 text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Khối thông tin khóa gần nhất -->
        <div id="detailKhungKhoaGanNhat" class="hidden p-3 bg-red-50 border border-red-200 rounded-xl text-xs space-y-1">
            <div class="font-bold text-red-700 flex items-center gap-1.5">
                <i class="fa-solid fa-lock"></i> Tài khoản hiện đang bị khóa:
            </div>
            <div class="text-slate-700">Lý do khóa: <strong id="detailLyDoKhoaText" class="text-red-900"></strong></div>
            <div class="text-[10px] text-slate-500 flex justify-between pt-0.5">
                <span>Người thực hiện khóa: <strong id="detailNguoiKhoaText"></strong></span>
                <span id="detailThoiGianKhoaText" class="font-mono"></span>
            </div>
        </div>

        <!-- Khung thông tin tổng quan -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-4 bg-nenChinh rounded-xl border border-emerald-900/10 text-xs">
            <div>
                <span class="text-slate-500 block">Tên đăng nhập:</span>
                <span id="detailTenDangNhap" class="font-mono font-bold text-slate-900"></span>
            </div>
            <div>
                <span class="text-slate-500 block">Email liên hệ:</span>
                <span id="detailEmail" class="font-semibold text-slate-800 break-all"></span>
            </div>
            <div>
                <span class="text-slate-500 block">Vai trò hệ thống:</span>
                <div id="detailVaiTro" class="pt-0.5"></div>
            </div>
            <div>
                <span class="text-slate-500 block">Ngày tạo tài khoản:</span>
                <span id="detailNgayTao" class="font-mono text-slate-700"></span>
            </div>
            <div>
                <span class="text-slate-500 block">Trạng thái tài khoản:</span>
                <div id="detailTrangThaiTK" class="pt-0.5"></div>
            </div>
            <div>
                <span class="text-slate-500 block">Trạng thái tổ chức:</span>
                <div id="detailTrangThaiToChuc" class="pt-0.5"></div>
            </div>
            <div>
                <span class="text-slate-500 block">Tổ chức trực thuộc:</span>
                <span id="detailTenToChuc" class="font-semibold text-slate-800"></span>
            </div>
            <div>
                <span class="text-slate-500 block">Loại hình tổ chức:</span>
                <span id="detailLoaiHinhToChuc" class="font-semibold text-slate-800"></span>
            </div>
        </div>

        <!-- Khối hoạt động gần đây -->
        <div class="space-y-3 pt-1 text-xs">
            <div class="flex items-center justify-between">
                <span class="font-bold text-slate-700 uppercase tracking-wide flex items-center gap-1.5">
                    <i class="fa-solid fa-clock-rotate-left text-slate-600"></i> Hoạt Động Gần Đây (10 Bản Ghi Gần Nhất)
                </span>
                <a id="linkSangNhatKyHeThong" href="nhatKyHeThong.php" class="text-cyan-800 hover:text-cyan-950 font-bold hover:underline flex items-center gap-1 text-[11px]">
                    Xem toàn bộ trong Nhật ký hệ thống <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
                </a>
            </div>

            <div class="border border-slate-200 rounded-xl overflow-hidden max-h-56 overflow-y-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead class="bg-slate-50 sticky top-0 text-[11px] text-slate-600 border-b border-slate-200">
                        <tr>
                            <th class="p-2.5 font-bold whitespace-nowrap">Thời Gian</th>
                            <th class="p-2.5 font-bold whitespace-nowrap">Hành Động</th>
                            <th class="p-2.5 font-bold">Chi Tiết Tóm Tắt</th>
                        </tr>
                    </thead>
                    <tbody id="thanBangHoatDongGanDay" class="divide-y divide-slate-100">
                        <!-- Render bằng JS -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Chân modal -->
        <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
            <div id="khungNutThaoTacModalDetail">
                <!-- Nút Khóa / Mở khóa nhanh render qua JS -->
            </div>
            <button type="button" class="dong-modal-detail px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-all">
                Đóng
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/quanTriVien/quanLyTaiKhoan.js') }}"></script>
@endpush

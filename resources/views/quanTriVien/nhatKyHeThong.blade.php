@extends('layoutDungChung.master')

@section('title', 'Nhật ký hệ thống - PharmaChain')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/quanTriVien/nhatKyHeThong.css') }}">
@endpush

@section('content')
<div class="space-y-6 max-w-7xl w-full mx-auto">

    <!-- THANH CÔNG CỤ: KHOẢNG THỜI GIAN + HÀNH ĐỘNG + KẾT QUẢ + TÌM KIẾM -->
    <div class="bg-white p-5 rounded-2xl border border-emerald-900/10 bong-xanh space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">

            <!-- 1. Khoảng thời gian (Mặc định: 7 ngày gần nhất) -->
            <div>
                <label for="locKhoangThoiGian" class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Thời gian</label>
                <select id="locKhoangThoiGian" class="w-full px-3 py-2 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:border-slate-800 outline-none cursor-pointer">
                    <option value="7_NGAY" selected>7 ngày gần nhất</option>
                    <option value="HOM_NAY">Hôm nay</option>
                    <option value="30_NGAY">30 ngày gần nhất</option>
                    <option value="TUY_CHON">Tùy chọn khoảng ngày...</option>
                    <option value="TAT_CA">Toàn bộ thời gian</option>
                </select>
            </div>

            <!-- 2. Loại hành động kỹ thuật & bảo mật thuần Việt -->
            <div>
                <label for="locLoaiHanhDong" class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Loại hành động</label>
                <select id="locLoaiHanhDong" class="w-full px-3 py-2 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:border-slate-800 outline-none cursor-pointer">
                    <option value="TAT_CA">-- Tất cả hành động --</option>
                    <option value="DANG_NHAP">Đăng nhập</option>
                    <option value="DANG_NHAP_THAT_BAI">Đăng nhập thất bại</option>
                    <option value="KHOA_TAI_KHOAN">Khóa tài khoản</option>
                    <option value="MO_KHOA_TAI_KHOAN">Mở khóa</option>
                    <option value="DOI_MAT_KHAU">Đổi mật khẩu</option>
                    <option value="GIAO_DICH_TU_CHOI">Giao dịch bị từ chối</option>
                    <option value="LOI_HE_THONG">Lỗi hệ thống / Blockchain</option>
                </select>
            </div>

            <!-- 3. Kết quả (3 nhóm màu) -->
            <div>
                <label for="locKetQua" class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Kết quả</label>
                <select id="locKetQua" class="w-full px-3 py-2 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:border-slate-800 outline-none cursor-pointer">
                    <option value="TAT_CA">-- Tất cả kết quả --</option>
                    <option value="THANH_CONG">Thành công</option>
                    <option value="BI_TU_CHOI">Bị từ chối</option>
                    <option value="LOI">Lỗi</option>
                </select>
            </div>

            <!-- 4. Tìm kiếm từ khóa / tài khoản -->
            <div>
                <label for="oTimKiemNhatKy" class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Từ khóa tìm kiếm</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 text-xs pointer-events-none">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" id="oTimKiemNhatKy" placeholder="Tìm tên đăng nhập, chi tiết..." class="w-full pl-8 pr-3 py-2 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-medium text-slate-900 focus:bg-white focus:border-slate-800 outline-none">
                </div>
            </div>
        </div>

        <!-- Khung chọn khoảng ngày tùy chọn -->
        <div id="khungNgayTuyChon" class="hidden p-3 bg-nenChinh rounded-xl border border-emerald-900/15 flex flex-wrap items-center gap-3 text-xs">
            <span class="font-bold text-slate-700 flex items-center gap-1.5">
                <i class="fa-regular fa-calendar-days text-slate-600"></i> Chọn khoảng ngày:
            </span>
            <div class="flex items-center gap-1.5">
                <span class="text-slate-500">Từ ngày:</span>
                <input type="date" id="ngayBatDauInput" class="px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-mono outline-none">
            </div>
            <div class="flex items-center gap-1.5">
                <span class="text-slate-500">Đến ngày:</span>
                <input type="date" id="ngayKetThucInput" class="px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-mono outline-none">
            </div>
            <button type="button" id="nutApDungKhoangNgay" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-900 text-white rounded-lg font-bold text-[11px] transition-all">
                Áp Dụng
            </button>
        </div>

        <!-- Ghi chú phạm vi chuẩn hóa -->
        <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200 text-[11px] text-slate-600 flex items-center gap-2">
            <i class="fa-solid fa-user-shield text-slate-500 shrink-0"></i>
            <span>Nhật ký ghi các sự kiện kỹ thuật và bảo mật của hệ thống. Hành vi tra cứu công khai của người tiêu dùng không được ghi nhận để bảo vệ quyền riêng tư.</span>
        </div>
    </div>

    <!-- BẢNG DANH SÁCH NHẬT KÝ HỆ THỐNG (CHỈ ĐỌC) -->
    <div class="bg-white rounded-2xl border border-emerald-900/10 bong-xanh overflow-hidden">
        <div class="p-4 bg-nenChinh border-b border-emerald-900/10 flex items-center justify-between text-xs">
            <span class="font-bold text-slate-700 uppercase tracking-wide flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-slate-700"></i> Nhật Ký Hoạt Động & Kiểm Toán Hệ Thống (Audit Logs)
            </span>
            <span id="soLuongNhatKyBadge" class="font-bold text-slate-500 font-mono">Hiển thị: 0 bản ghi</span>
        </div>

        <div class="w-full overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 uppercase tracking-wider border-b border-slate-100 text-[11px]">
                        <th class="py-3.5 px-3 font-bold whitespace-nowrap">Thời Gian</th>
                        <th class="py-3.5 px-3 font-bold whitespace-nowrap">Tài Khoản</th>
                        <th class="py-3.5 px-3 font-bold whitespace-nowrap">Hành Động</th>
                        <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Kết Quả</th>
                        <th class="py-3.5 px-3 font-bold">Chi Tiết Tóm Tắt</th>
                        <th class="py-3.5 px-3 font-bold text-center whitespace-nowrap">Hành Động</th>
                    </tr>
                </thead>
                <tbody id="thanBangNhatKy" class="divide-y divide-slate-100 font-medium">
                    <!-- Render bằng JavaScript -->
                </tbody>
            </table>
        </div>

        <!-- Phân trang bắt buộc -->
        <div class="p-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
            <span id="thongTinPhanTrangLog" class="text-slate-500">Đang hiển thị...</span>
            <div class="flex items-center gap-1.5" id="cumNutPhanTrangLog"></div>
        </div>
    </div>

</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!-- MODAL: XEM CHI TIẾT NHẬT KÝ HỆ THỐNG                     -->
<!-- ══════════════════════════════════════════════════════════ -->
<div id="modalChiTietLog" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden bg-black/60">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 sm:p-8 space-y-5 bong-xanh max-h-[90vh] overflow-y-auto">
        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-base font-bold text-slate-900">Chi Tiết Nhật Ký:</span>
                <span id="modalLogId" class="font-mono font-bold text-slate-800 bg-slate-100 px-2.5 py-0.5 rounded text-xs border border-slate-200"></span>
            </div>
            <button type="button" class="dong-modal-log text-slate-400 hover:text-slate-700 text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="space-y-4 text-xs">
            <!-- Thông tin meta -->
            <div class="grid grid-cols-2 gap-3 p-3.5 bg-nenChinh rounded-xl border border-emerald-900/10">
                <div>
                    <span class="text-slate-500 block">Thời gian phát sinh:</span>
                    <span id="modalLogThoiGian" class="font-mono font-semibold text-slate-800"></span>
                </div>
                <div>
                    <span class="text-slate-500 block">Tài khoản thực hiện:</span>
                    <span id="modalLogTaiKhoan" class="font-bold text-slate-900 font-mono"></span>
                </div>
                <div>
                    <span class="text-slate-500 block">Hành động:</span>
                    <div id="modalLogHanhDong" class="pt-0.5 font-bold text-slate-800"></div>
                </div>
                <div>
                    <span class="text-slate-500 block">Kết quả ghi nhận:</span>
                    <div id="modalLogKetQuaBadge" class="pt-0.5"></div>
                </div>
            </div>

            <!-- Tài khoản bị tác động -->
            <div id="modalLogKhungTaiKhoanBiTacDong" class="hidden p-3 bg-cyan-50 border border-cyan-200 rounded-xl flex items-center justify-between text-xs">
                <div>
                    <span class="text-cyan-900 font-bold block">Tài khoản bị tác động:</span>
                    <span id="modalLogTaiKhoanBiTacDongText" class="font-mono font-bold text-slate-900"></span>
                </div>
                <a id="modalLogLinkSangTaiKhoan" href="quanLyTaiKhoan.php" class="px-3 py-1.5 bg-white border border-cyan-300 text-cyan-800 hover:bg-cyan-100 rounded-lg font-bold text-[11px] transition-all flex items-center gap-1 shadow-2xs">
                    <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i> Xem tài khoản
                </a>
            </div>

            <!-- Cảnh báo an toàn thông tin với đăng nhập thất bại -->
            <div id="modalLogCanhBaoDangNhap" class="hidden p-3 bg-red-50 border border-red-200 rounded-xl text-red-900 space-y-1 text-[11px] leading-relaxed">
                <div class="font-bold flex items-center gap-1.5 text-red-700">
                    <i class="fa-solid fa-shield-halved"></i> Ghi nhận an toàn thông tin:
                </div>
                <p>
                    Hệ thống chỉ lưu vết tên đăng nhập đã thử (<strong id="modalLogTenDangNhapThuText" class="font-mono"></strong>) và địa chỉ IP kết nối. Mật khẩu không được lưu lại dưới bất kỳ hình thức nào để bảo vệ bí mật thông tin người dùng.
                </p>
            </div>

            <!-- Nội dung chi tiết sự kiện -->
            <div class="space-y-1">
                <span class="text-slate-700 font-bold block">Nội dung chi tiết sự kiện:</span>
                <div id="modalLogMoTaTiengViet" class="p-3 bg-slate-50 border border-slate-200 rounded-xl font-medium text-slate-800 leading-relaxed break-words whitespace-pre-wrap"></div>
            </div>
        </div>

        <div class="pt-2 border-t border-slate-100 flex justify-end">
            <button type="button" class="dong-modal-log px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-all">
                Đóng
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/quanTriVien/nhatKyHeThong.js') }}"></script>
@endpush
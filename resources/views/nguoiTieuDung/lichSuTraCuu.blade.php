@extends('layoutNguoiTieuDung.main')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/nguoiTieuDung/lichSuTraCuu.css') }}">
@endpush

@section('content')

<main class="flex-1 py-10 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto w-full space-y-6">

    <!-- TIÊU ĐỀ TRANG + NÚT QUAY LẠI TRA CỨU -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-emerald-900/10 pb-5">
        <div class="flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-xl shrink-0 shadow-sm border border-emerald-900/10">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-emerald-700 tracking-tight">
                    Lịch Sử Tra Cứu
                </h1>
                <p class="text-xs text-slate-500 mt-0.5">Theo dõi lịch sử và cập nhật trạng thái mới nhất của các lô thuốc đã kiểm tra trên Blockchain</p>
            </div>
        </div>

        <!-- Cụm nút quay lại trang tra cứu -->
        <div class="flex items-center gap-3">
            <a href="{{ route('tra-cuu') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200/80 text-xs font-bold transition-all shadow-sm">
                <i class="fa-solid fa-arrow-left"></i> Quay Lại Tra Cứu
            </a>
        </div>
    </div>

    <!-- KHUNG THÔNG BÁO TRẠNG THÁI INLINE -->
    <div id="thongBaoPublic" class="hidden p-3.5 rounded-xl border text-xs font-semibold flex items-center justify-between gap-3 transition-all">
        <div class="flex items-center gap-2">
            <i id="iconThongBaoPublic" class="fa-solid fa-circle-info"></i>
            <span id="noiDungThongBaoPublic"></span>
        </div>
        <button type="button" id="dongThongBaoPublic" class="opacity-70 hover:opacity-100 cursor-pointer">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <!-- BANNER CHÚ THÍCH CƠ CHẾ BẢO VỆ DỮ LIỆU -->
    <div class="p-4 rounded-xl bg-cyan-50 border border-cyan-800/15 flex items-start gap-3 bong-xanh">
        <i class="fa-solid fa-circle-info text-cyan-800 text-base mt-0.5 shrink-0"></i>
        <div class="text-xs text-cyan-900 leading-relaxed">
            <strong>Cơ chế cập nhật trạng thái thời gian thực:</strong> Mọi mã tham chiếu trong bộ nhớ cục bộ (<code class="font-mono font-bold">localStorage</code>) đều được đối soát tự động trực tiếp với dữ liệu Smart Contract trên mạng Blockchain mỗi khi tải trang. Nếu lô thuốc phát sinh lệnh thu hồi hoặc hết hạn, trạng thái sẽ được cập nhật và cảnh báo ngay lập tức.
        </div>
    </div>

    <!-- KHUNG 1: TRA CỨU NHANH & NHẬP TỪ FILE .TXT (ĐỒNG BỘ VỚI LỊCH SỬ BÁO CÁO) -->
    <div class="bg-white p-5 rounded-2xl border border-emerald-900/10 bong-xanh space-y-3">
        <span class="font-bold text-slate-700 uppercase text-xs block">
            <i class="fa-solid fa-magnifying-glass text-emerald-700 mr-1"></i> Tra Cứu Nhanh Mã Lô / Mã Định Danh
        </span>
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1 relative">
                <input type="text" id="oTraCuuThuCong" placeholder="Nhập mã lô thuốc hoặc mã định danh (Ví dụ: LOT-VN-..., MED-...)..." class="w-full pl-3 pr-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl font-mono uppercase font-semibold text-xs text-slate-900 focus:bg-white focus:border-emerald-700 outline-none">
            </div>
            <button type="button" id="nutTraCuuThuCong" class="px-5 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs rounded-xl shadow-md transition-all flex items-center justify-center gap-2 cursor-pointer">
                <i class="fa-solid fa-search"></i> Tra Cứu
            </button>
            <label class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl border border-slate-300 transition-all flex items-center justify-center gap-2 cursor-pointer shrink-0">
                <i class="fa-solid fa-file-arrow-up text-cyan-800"></i> Nhập Từ File .txt
                <input type="file" id="inputUploadFileTxt" accept=".txt" class="hidden">
            </label>
        </div>
    </div>

    <!-- KHUNG 2: BẢNG LỊCH SỬ TRA CỨU KÈM THANH CÔNG CỤ NHẬP / XUẤT / XÓA -->
    <div class="bg-white rounded-2xl border border-emerald-900/10 bong-xanh overflow-hidden">
        <div class="p-4 bg-nenChinh border-b border-emerald-900/10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 text-xs">
            <span class="font-bold text-slate-700 uppercase flex items-center gap-2">
                <i class="fa-solid fa-list-check text-cyan-800"></i> Danh Sách Lịch Sử Tra Cứu
            </span>
            <div class="flex items-center gap-2 flex-wrap">
                <span class="font-bold text-slate-500 font-mono mr-1">Tổng: <span id="soLuongTraCuuBadge">0</span> lượt</span>

                <label class="px-2.5 py-1 text-[11px] font-bold text-emerald-800 hover:text-emerald-900 hover:bg-emerald-50 rounded-lg border border-emerald-200 transition-all cursor-pointer flex items-center gap-1">
                    <i class="fa-solid fa-file-import"></i> Nhập Lịch Sử
                    <input type="file" id="inputNhapLichSuTxt" accept=".txt" class="hidden">
                </label>

                <button type="button" id="nutXuatLichSuPublic" class="px-2.5 py-1 text-[11px] font-bold text-cyan-800 hover:text-cyan-900 hover:bg-cyan-50 rounded-lg border border-cyan-200 transition-all cursor-pointer flex items-center gap-1">
                    <i class="fa-solid fa-file-arrow-down"></i> Xuất Lịch Sử
                </button>

                <button type="button" id="nutXoaLichSuPublic" class="px-2.5 py-1 text-[11px] font-bold text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg border border-red-200 transition-all cursor-pointer flex items-center gap-1">
                    <i class="fa-solid fa-trash-can"></i> Xóa Lịch Sử
                </button>
            </div>
        </div>

        <!-- Trạng thái đang tải dữ liệu (Loading skeleton) -->
        <div id="khungDangTai" class="p-8 text-center space-y-3">
            <i class="fa-solid fa-circle-notch fa-spin text-3xl text-emerald-700"></i>
            <p class="text-xs font-semibold text-slate-600">Đang đối soát trạng thái mới nhất từ Blockchain...</p>
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
                <a href="{{ route('tra-cuu') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs rounded-xl shadow-md transition-all">
                    <i class="fa-solid fa-qrcode"></i> Tra Cứu Thuốc Ngay
                </a>
            </div>
        </div>

        <!-- Bảng danh sách khi có dữ liệu -->
        <div id="khungBangLichSu" class="hidden overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 uppercase tracking-wider border-b border-slate-100 text-[11px]">
                        <th class="py-3 px-4 font-bold">Tên Thuốc / Sản Phẩm</th>
                        <th class="py-3 px-4 font-bold">Mã Đã Tra</th>
                        <th class="py-3 px-4 font-bold">Trạng Thái Hiện Tại</th>
                        <th class="py-3 px-4 font-bold">Thời Điểm Đã Tra</th>
                        <th class="py-3 px-4 font-bold text-center">Hành Động</th>
                    </tr>
                </thead>
                <tbody id="thanBangLichSu" class="divide-y divide-slate-100 font-medium"></tbody>
            </table>
        </div>
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

@endsection

@push('scripts')
    <script src="{{ asset('assets/js/nguoiTieuDung/lichSuTraCuu.js') }}"></script>
@endpush

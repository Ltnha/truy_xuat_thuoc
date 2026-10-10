@extends('layouts.cong')
@section('title', 'Lịch Sử Tra Cứu – PharmaChain')

@section('content')
<main data-url-tra-cuu="{{ route('cong.truyXuat.xem') }}" class="flex-1 py-10 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto w-full space-y-6"><!-- TIÊU ĐỀ TRANG CĂN CHỈNH MỚI + CỤM NÚT HÀNH ĐỘNG -->
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
            <a href="{{ route('cong.truyXuat.trangChu') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200/80 text-xs font-bold transition-all shadow-sm">
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
            <strong>Lịch sử trên thiết bị này:</strong> Mã tra cứu được lưu trong trình duyệt. Khi mở lại, trang sẽ truy vấn trạng thái hiện có trong hệ thống; lịch sử không phải bằng chứng giao dịch blockchain độc lập.
        </div>
    </div>

    <!-- KHUNG CHỨA DANH SÁCH LỊCH SỬ HOẶC THÔNG BÁO RỖNG -->
    <div class="bg-white rounded-2xl border border-emerald-900/10 bong-xanh overflow-hidden">
        <div id="loiTaiLichSu" class="hidden m-4 rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-800" role="alert"></div>

        <!-- Trạng thái đang tải dữ liệu (Loading skeleton) -->
        <div id="khungDangTai" class="p-8 text-center space-y-3">
            <i class="fa-solid fa-circle-notch fa-spin text-3xl text-emerald-700"></i>
            <p class="text-xs font-semibold text-slate-600">Đang cập nhật trạng thái từ hệ thống...</p>
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
                <a href="{{ route('cong.truyXuat.trangChu') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs rounded-xl shadow-md transition-all">
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
    </div></main>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/cong/lichSuTraCuu.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/js/cong/lichSuTraCuu.js') }}" defer></script>
@endpush

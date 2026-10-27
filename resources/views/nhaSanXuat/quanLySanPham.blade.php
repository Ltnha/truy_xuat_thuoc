@extends('layoutDungChung.master')

@section('title', $tieuDeTrang ?? 'Quản lý sản phẩm - PharmaChain')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/nhaSanXuat/quanLySanPham.css') }}">
@endpush

@section('content')
<div class="space-y-6 max-w-7xl w-full mx-auto">

    <!-- THANH TRÊN: TÌM KIẾM + BỘ LỌC TRẠNG THÁI + NÚT ĐĂNG KÝ SẢN PHẨM MỚI -->
    <div class="bg-white p-5 rounded-2xl border border-emerald-900/10 bong-xanh flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">

        <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-3 max-w-2xl">
            <!-- Ô tìm kiếm -->
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 text-xs pointer-events-none">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" id="oTimKiemSP" placeholder="Tìm theo tên thuốc, số ĐK, thành phần..." class="w-full pl-9 pr-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-medium text-slate-900 focus:bg-white focus:border-emerald-700 outline-none">
            </div>

            <!-- Bộ lọc trạng thái duyệt -->
            <div class="relative">
                <select id="locTrangThaiSP" class="w-full px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:border-emerald-700 outline-none cursor-pointer">
                    <option value="TAT_CA">-- Tất cả trạng thái duyệt --</option>
                    <option value="DA_DUYET">Đã duyệt (Lưu hành)</option>
                    <option value="CHO_DUYET">Chờ duyệt (Đang xử lý)</option>
                    <option value="TU_CHOI">Bị từ chối (Cần sửa đổi)</option>
                </select>
            </div>
        </div>

        <!-- Nút Đăng ký sản phẩm mới -->
        <button type="button" id="nutMoModalDangKySP" class="px-5 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl text-xs font-bold shadow-md transition-all flex items-center justify-center gap-2 shrink-0">
            <i class="fa-solid fa-file-circle-plus text-sm"></i> Đăng Ký Sản Phẩm Mới
        </button>
    </div>

    <!-- BẢNG DANH SÁCH SẢN PHẨM -->
    <div class="bg-white rounded-2xl border border-emerald-900/10 bong-xanh overflow-hidden">
        <div class="p-4 bg-nenChinh border-b border-emerald-900/10 flex items-center justify-between text-xs">
            <span class="font-bold text-slate-700 uppercase tracking-wide flex items-center gap-2">
                <i class="fa-solid fa-capsules text-cyan-800"></i> Danh Sách Sản Phẩm Dược Phẩm Đăng Ký
            </span>
            <span id="soLuongSPBadge" class="font-bold text-slate-500 font-mono">Hiển thị: 0 sản phẩm</span>
        </div>

        <div class="w-full overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 uppercase tracking-wider border-b border-slate-100 text-[11px]">
                        <th class="py-3.5 px-3 font-bold">Tên Thuốc</th>
                        <th class="py-3.5 px-3 font-bold whitespace-nowrap">Số ĐK Lưu Hành</th>
                        <th class="py-3.5 px-3 font-bold">Thành Phần</th>
                        <th class="py-3.5 px-2.5 font-bold whitespace-nowrap">Hàm Lượng</th>
                        <th class="py-3.5 px-2.5 font-bold whitespace-nowrap">Dạng Bào Chế</th>
                        <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Trạng Thái Duyệt</th>
                        <th class="py-3.5 px-3 font-bold text-center whitespace-nowrap">Hành Động</th>
                    </tr>
                </thead>
                <tbody id="thanBangSanPham" class="divide-y divide-slate-100 font-medium">
                    <!-- Render bằng JavaScript -->
                </tbody>
            </table>
        </div>

        <!-- PHÂN TRANG BẢNG SẢN PHẨM -->
        <div class="p-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
            <span id="thongTinPhanTrangSP" class="text-slate-500">Đang hiển thị...</span>
            <div class="flex items-center gap-1.5" id="cumNutPhanTrangSP">
                <!-- Nút phân trang render qua JS -->
            </div>
        </div>
    </div>

</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!-- MODAL 1: ĐĂNG KÝ SẢN PHẨM MỚI / NỘP LẠI KHI BỊ TỪ CHỐI    -->
<!-- ══════════════════════════════════════════════════════════ -->
<div id="modalDangKySP" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden bg-black/60">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 sm:p-8 space-y-5 bong-xanh max-h-[90vh] overflow-y-auto">
        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2" id="tieuDeModalSP">
                <i class="fa-solid fa-pills text-emerald-700"></i> Nộp Đơn Đăng Ký Sản Phẩm Dược Mới
            </h3>
            <button type="button" class="dong-modal-sp text-slate-400 hover:text-slate-700 text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Khung cảnh báo lý do từ chối (Chỉ hiện khi nộp lại) -->
        <div id="khungCanhBaoTuChoiCu" class="hidden p-3.5 bg-red-50 border border-red-200 rounded-xl text-xs space-y-1">
            <div class="font-bold text-red-700 flex items-center gap-1.5">
                <i class="fa-solid fa-triangle-exclamation"></i> Lý Do Bị Từ Chối Lần Gần Nhất:
            </div>
            <p id="noiDungLyDoTuChoiCu" class="text-red-600 leading-relaxed"></p>
            <span class="text-[10px] text-slate-500 italic block pt-1">Hệ thống đã tự động điền dữ liệu lần nộp trước. Vui lòng sửa lại mục không hợp lệ trước khi gửi lại.</span>
        </div>

        <!-- Form Đăng ký sản phẩm mới -->
        <form id="formSubmitSP" action="{{ route('nha-san-xuat.sanPham.store') }}" method="POST" class="space-y-4 text-xs">
            @csrf
            <input type="hidden" id="spIdDangChon" name="sp_id" value="">

            <div class="space-y-1">
                <label for="nhapTenThuoc" class="font-bold text-slate-700">Tên Biệt Dược / Tên Thuốc <span class="text-red-500">*</span></label>
                <input type="text" name="tenSanPham" id="nhapTenThuoc" required placeholder="VD: Paracetamol 500mg" class="w-full px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl font-semibold text-slate-900 outline-none">
            </div>

            <div class="space-y-1">
                <label for="nhapSoDK" class="font-bold text-slate-700">Số Đăng Ký Lưu Hành <span class="text-red-500">*</span></label>
                <input type="text" name="soDangKy" id="nhapSoDK" required placeholder="VD: VD-28491-18" class="w-full px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl font-mono uppercase font-bold text-slate-900 outline-none">
            </div>

            <div class="space-y-1">
                <label for="nhapThanhPhan" class="font-bold text-slate-700">Thành Phần <span class="text-red-500">*</span></label>
                <input type="text" name="thanhPhan" id="nhapThanhPhan" required placeholder="VD: Paracetamol" class="w-full px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-slate-900 outline-none">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1">
                    <label for="nhapHamLuong" class="font-bold text-slate-700">Hàm Lượng <span class="text-red-500">*</span></label>
                    <input type="text" name="hamLuong" id="nhapHamLuong" required placeholder="VD: 500mg" class="w-full px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-slate-900 outline-none">
                </div>

                <div class="space-y-1">
                    <label for="nhapDangBaoChe" class="font-bold text-slate-700">Dạng Bào Chế <span class="text-red-500">*</span></label>
                    <input type="text" name="dangBaoChe" id="nhapDangBaoChe" required placeholder="VD: Viên nén bao phim" class="w-full px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-slate-900 outline-none">
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                <button type="button" class="dong-modal-sp px-4 py-2.5 bg-slate-100 text-slate-700 font-bold rounded-xl">Hủy</button>
                <button type="submit" class="px-6 py-2.5 bg-emerald-700 text-white font-bold rounded-xl shadow-md"><i class="fa-solid fa-paper-plane mr-1"></i> Gửi Đăng Ký</button>
            </div>
        </form>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!-- MODAL 2: CHI TIẾT SẢN PHẨM & LỊCH SỬ THẨM DUYỆT            -->
<!-- ══════════════════════════════════════════════════════════ -->
<div id="modalChiTietSP" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden bg-black/60">
    <div class="bg-white rounded-2xl max-w-2xl w-full p-6 sm:p-8 space-y-5 bong-xanh max-h-[90vh] overflow-y-auto">
        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-circle-info text-emerald-700"></i> Chi Tiết Sản Phẩm & Lịch Sử Thẩm Duyệt
                </h3>
                <span id="tenSPChiTietTitle" class="text-xs text-slate-500 font-bold"></span>
            </div>
            <button type="button" class="dong-modal-chitiet-sp text-slate-400 hover:text-slate-700 text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- PHẦN TRÊN: THÔNG TIN HIỆN TẠI CỦA SẢN PHẨM -->
        <div class="space-y-3">
            <span class="text-xs font-bold text-slate-700 uppercase tracking-wide block">
                <i class="fa-solid fa-capsules text-cyan-800 mr-1"></i> Thông Tin Sản Phẩm Hiện Tại:
            </span>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 p-3.5 bg-nenChinh rounded-xl border border-emerald-900/10 text-xs">
                <div>
                    <span class="text-slate-500 block">Tên thuốc:</span>
                    <span id="modalChiTietTenThuoc" class="font-bold text-slate-900"></span>
                </div>
                <div>
                    <span class="text-slate-500 block">Số ĐK lưu hành:</span>
                    <span id="modalChiTietSoDK" class="font-mono font-bold text-cyan-800"></span>
                </div>
                <div>
                    <span class="text-slate-500 block">Trạng thái:</span>
                    <div id="modalChiTietTrangThai" class="pt-0.5"></div>
                </div>
                <div>
                    <span class="text-slate-500 block">Thành phần:</span>
                    <span id="modalChiTietThanhPhan" class="font-semibold text-slate-800"></span>
                </div>
                <div>
                    <span class="text-slate-500 block">Hàm lượng:</span>
                    <span id="modalChiTietHamLuong" class="font-semibold text-slate-800"></span>
                </div>
                <div>
                    <span class="text-slate-500 block">Dạng bào chế:</span>
                    <span id="modalChiTietDangBaoChe" class="font-semibold text-slate-800"></span>
                </div>
            </div>

            <!-- KHỐI CHỨNG CỨ BLOCKCHAIN -->
            <div id="khungChungCuBlockchainSP" class="hidden space-y-1.5 text-xs pt-1">
                <span class="font-bold text-slate-700 flex items-center gap-1.5">
                    <i class="fa-solid fa-link text-emerald-700"></i> Bằng chứng phê duyệt on-chain (Polygon Testnet Explorer):
                </span>
                <div class="flex items-center gap-2 p-2.5 bg-slate-50 border border-slate-200 rounded-xl">
                    <a id="modalChiTietTxLinkSP" href="#" target="_blank" class="flex-1 font-mono text-[11px] text-cyan-800 hover:underline break-all flex items-center gap-1.5">
                        <span id="modalChiTietTxHashSP">0x...</span>
                        <i class="fa-solid fa-arrow-up-right-from-square text-[9px] text-slate-400 shrink-0"></i>
                    </a>
                    <button type="button" id="nutSaoChepTxSP" class="px-2.5 py-1 bg-white hover:bg-slate-100 border border-slate-300 text-slate-700 rounded-lg text-[11px] font-bold shrink-0 shadow-2xs">
                        <i class="fa-regular fa-copy"></i> Sao chép
                    </button>
                </div>
            </div>
        </div>

        <!-- PHẦN DƯỚI: LỊCH SỬ CÁC LẦN NỘP HỒ SƠ -->
        <div class="space-y-2 pt-2 border-t border-slate-100">
            <span class="text-xs font-bold text-slate-700 uppercase tracking-wide block">
                <i class="fa-solid fa-timeline text-emerald-700 mr-1"></i> Lịch Sử Các Lần Nộp Hồ Sơ Đăng Ký:
            </span>
            <div id="khungDanhSachLichSuNop" class="space-y-3 text-xs max-h-72 overflow-y-auto pr-1">
                <!-- Render các thẻ lịch sử qua JavaScript -->
            </div>
        </div>

        <div class="pt-2 border-t border-slate-100 flex justify-end">
            <button type="button" class="dong-modal-chitiet-sp px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-all">
                Đóng
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Truyền trực tiếp danh sách sản phẩm từ Controller sang JavaScript
    window.danhSachSanPham = @json($sanPhams);
</script>
<script src="{{ asset('assets/js/nhaSanXuat/quanLySanPham.js') }}"></script>
@endpush
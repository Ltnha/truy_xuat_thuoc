@extends('layoutDungChung.master')

@section('title', 'Xét duyệt sản phẩm - PharmaChain')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/coQuanQuanLy/xetDuyetSanPham.css') }}">
@endpush

@section('content')
<div class="space-y-6 max-w-7xl w-full mx-auto">

    <!-- THÔNG BÁO FLASH -->
    @if(session('thanhCong'))
    <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl text-xs font-semibold flex items-center gap-2">
        <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
        <span>{{ session('thanhCong') }}</span>
    </div>
    @endif

    @if(session('loi'))
    <div class="p-4 bg-red-50 border border-red-200 text-red-800 rounded-2xl text-xs font-semibold flex items-center gap-2">
        <i class="fa-solid fa-triangle-exclamation text-red-600 text-base"></i>
        <span>{{ session('loi') }}</span>
    </div>
    @endif

    <!-- CỤM 2 TAB CHÍNH -->
    <div class="bg-white rounded-2xl border border-emerald-900/10 bong-xanh p-2 flex gap-2">
        <button type="button" id="tabHoSoDangKy" class="flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all bg-indigo-700 text-white shadow-sm">
            <i class="fa-solid fa-file-signature text-base"></i>
            <span>Hồ Sơ Đăng Ký Lưu Hành (Chưa Duyệt / Từ Chối)</span>
            <span id="badgeTabHoSoChoDuyet" class="ml-1 px-2 py-0.5 rounded-full text-[11px] font-black bg-amber-400 text-indigo-950">
                {{ $yeuCauDangKys->where('trangThai', 'CHO_DUYET')->count() }}
            </span>
        </button>

        <button type="button" id="tabSanPhamDaDuyet" class="flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all text-slate-600 hover:text-indigo-700 hover:bg-indigo-50">
            <i class="fa-solid fa-certificate text-base"></i>
            <span>Sản Phẩm Đã Duyệt (Danh Mục On-Chain)</span>
            <span id="badgeTabSanPhamDaDuyet" class="ml-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700">
                {{ $sanPhamDaDuyets->count() }}
            </span>
        </button>
    </div>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- TAB 1: DANH SÁCH HỒ SƠ ĐĂNG KÝ (CHỈ CHỜ DUYỆT & TỪ CHỐI)   -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <div id="khungTabHoSo" class="space-y-4">
        <!-- Bộ lọc Tab 1 -->
        <div class="bg-white p-5 rounded-2xl border border-emerald-900/10 bong-xanh">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 text-xs pointer-events-none">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" id="oTimKiemSP" placeholder="Tìm theo tên thuốc, số ĐK, hoạt chất..." class="w-full pl-9 pr-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-medium text-slate-900 focus:bg-white focus:border-indigo-700 outline-none">
                </div>

                <select id="locNhaSanXuat" class="px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:border-indigo-700 outline-none cursor-pointer">
                    <option value="TAT_CA">-- Tất cả nhà sản xuất --</option>
                    @foreach($yeuCauDangKys->pluck('sanPham.nhaSanXuat')->unique('id') as $nsx)
                    @if($nsx)
                    <option value="{{ $nsx->id }}">{{ $nsx->tenToChuc }}</option>
                    @endif
                    @endforeach
                </select>

                <select id="locTrangThaiYeuCauSP" class="px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:border-indigo-700 outline-none cursor-pointer">
                    <option value="CHO_DUYET" selected>Chờ duyệt</option>
                    <option value="TU_CHOI">Bị từ chối</option>
                    <option value="TAT_CA">Tất cả (Chờ duyệt & Từ chối)</option>
                </select>
            </div>
        </div>

        <!-- Bảng Tab 1 -->
        <div class="bg-white rounded-2xl border border-emerald-900/10 bong-xanh overflow-hidden">
            <div class="p-4 bg-nenChinh border-b border-emerald-900/10 flex items-center justify-between text-xs">
                <span class="font-bold text-slate-700 uppercase tracking-wide flex items-center gap-2">
                    <i class="fa-solid fa-capsules text-indigo-700"></i> Danh Sách Hồ Sơ Đăng Ký Lưu Hành Thuốc
                </span>
                <span id="soLuongYeuCauSPBadge" class="font-bold text-slate-500 font-mono">Hiển thị: 0 hồ sơ</span>
            </div>

            <div class="w-full overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 uppercase tracking-wider border-b border-slate-100 text-[11px]">
                            <th class="py-3.5 px-3 font-bold whitespace-nowrap">Tên Thuốc</th>
                            <th class="py-3.5 px-3 font-bold whitespace-nowrap">Số ĐK Lưu Hành</th>
                            <th class="py-3.5 px-3 font-bold">Nhà Sản Xuất</th>
                            <th class="py-3.5 px-3 font-bold">Hoạt Chất</th>
                            <th class="py-3.5 px-2.5 font-bold whitespace-nowrap">Hàm Lượng</th>
                            <th class="py-3.5 px-2.5 font-bold whitespace-nowrap">Dạng Bào Chế</th>
                            <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Ngày Nộp</th>
                            <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Lần Nộp</th>
                            <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Trạng Thái</th>
                            <th class="py-3.5 px-3 font-bold text-center whitespace-nowrap">Hành Động</th>
                        </tr>
                    </thead>
                    <tbody id="thanBangYeuCauSP" class="divide-y divide-slate-100 font-medium"></tbody>
                </table>
            </div>

            <!-- PHÂN TRANG THU GỌN TAB 1 -->
            <div class="p-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                <span id="thongTinPhanTrangTab1" class="text-slate-500">Đang hiển thị...</span>
                <div class="flex items-center gap-1.5" id="cumNutPhanTrangTab1"></div>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- TAB 2: SẢN PHẨM ĐÃ DUYỆT (CÓ TÌM KIẾM, BỘ LỌC, NÚT SAO CHÉP) -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <div id="khungTabDaDuyet" class="space-y-4 hidden">
        <!-- Bộ lọc Tab 2 -->
        <div class="bg-white p-5 rounded-2xl border border-emerald-900/10 bong-xanh">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 text-xs pointer-events-none">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" id="oTimKiemTab2" placeholder="Tìm tên thuốc đã duyệt, số ĐK, hoạt chất..." class="w-full pl-9 pr-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-medium text-slate-900 focus:bg-white focus:border-indigo-700 outline-none">
                </div>

                <select id="locNSXTab2" class="px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:border-indigo-700 outline-none cursor-pointer">
                    <option value="TAT_CA">-- Tất cả nhà sản xuất --</option>
                    @foreach($sanPhamDaDuyets->pluck('nhaSanXuat')->unique('id') as $nsx)
                    @if($nsx)
                    <option value="{{ $nsx->id }}">{{ $nsx->tenToChuc }}</option>
                    @endif
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Bảng Tab 2 -->
        <div class="bg-white rounded-2xl border border-emerald-900/10 bong-xanh overflow-hidden">
            <div class="p-4 bg-nenChinh border-b border-emerald-900/10 flex items-center justify-between text-xs">
                <span class="font-bold text-slate-700 uppercase tracking-wide flex items-center gap-2">
                    <i class="fa-solid fa-certificate text-emerald-700"></i> Danh Mục Thuốc Đã Cấp Phép Lưu Hành Toàn Quốc (On-Chain)
                </span>
                <span id="soLuongDaDuyetBadge" class="font-bold text-slate-500 font-mono">Hiển thị: 0 sản phẩm</span>
            </div>

            <div class="w-full overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 uppercase tracking-wider border-b border-slate-100 text-[11px]">
                            <th class="py-3.5 px-3 font-bold whitespace-nowrap">Tên Thuốc</th>
                            <th class="py-3.5 px-3 font-bold whitespace-nowrap">Số ĐK Lưu Hành</th>
                            <th class="py-3.5 px-3 font-bold">Hoạt Chất</th>
                            <th class="py-3.5 px-2.5 font-bold whitespace-nowrap">Hàm Lượng</th>
                            <th class="py-3.5 px-2.5 font-bold whitespace-nowrap">Dạng Bào Chế</th>
                            <th class="py-3.5 px-3 font-bold">Nhà Sản Xuất</th>
                            <th class="py-3.5 px-3 font-bold whitespace-nowrap">Mã Giao Dịch Blockchain</th>
                            <th class="py-3.5 px-3 font-bold text-center whitespace-nowrap">Hành Động</th>
                        </tr>
                    </thead>
                    <tbody id="thanBangDaDuyet" class="divide-y divide-slate-100 font-medium"></tbody>
                </table>
            </div>

            <!-- PHÂN TRANG THU GỌN TAB 2 -->
            <div class="p-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                <span id="thongTinPhanTrangTab2" class="text-slate-500">Đang hiển thị...</span>
                <div class="flex items-center gap-1.5" id="cumNutPhanTrangTab2"></div>
            </div>
        </div>
    </div>

</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!-- MODAL 1: CHI TIẾT SẢN PHẨM & THẨM ĐỊNH HỒ SƠ (TAB 1)      -->
<!-- ══════════════════════════════════════════════════════════ -->
<div id="modalXemHoSoSP" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-3 sm:p-5 hidden bg-black/60">
    <div class="bg-white rounded-2xl max-w-5xl w-full p-5 sm:p-7 space-y-4 bong-xanh max-h-[92vh] flex flex-col">
        <div class="border-b border-slate-100 pb-3 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2.5 flex-wrap">
                <span id="panelTieuDeChinh" class="text-base font-bold text-slate-900">Chi Tiết Sản Phẩm:</span>
                <span id="panelTenThuocTitle" class="font-bold text-indigo-900 bg-indigo-50 px-2.5 py-0.5 rounded text-xs border border-indigo-200"></span>
                <span id="panelTrangThaiSPBadge"></span>
            </div>
            <button type="button" class="dong-modal-hoso-sp text-slate-400 hover:text-slate-700 text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto space-y-4 pr-1 text-xs">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
                <div class="lg:col-span-7 bg-nenChinh p-4 rounded-xl border border-emerald-900/10 space-y-3">
                    <span class="font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2 border-b border-slate-200 pb-2">
                        <i class="fa-solid fa-pills text-indigo-700"></i> Thông Tin Sản Phẩm Kê Khai
                    </span>
                    <div class="grid grid-cols-2 gap-2.5">
                        <div class="col-span-2 p-2 rounded-lg bg-white border border-slate-100">
                            <span class="text-slate-500 block text-[11px]">Tên thuốc / Biệt dược:</span>
                            <span id="panelTenThuoc" class="font-bold text-slate-900 text-sm"></span>
                        </div>
                        <div class="p-2 rounded-lg bg-white border border-slate-100">
                            <span class="text-slate-500 block text-[11px]">Số đăng ký lưu hành:</span>
                            <span id="panelSoDK" class="font-mono font-bold text-cyan-800 text-xs"></span>
                        </div>
                        <div class="p-2 rounded-lg bg-white border border-slate-100">
                            <span class="text-slate-500 block text-[11px]">Hoạt chất chính:</span>
                            <span id="panelHoatChat" class="font-semibold text-slate-900"></span>
                        </div>
                        <div class="p-2 rounded-lg bg-white border border-slate-100">
                            <span class="text-slate-500 block text-[11px]">Hàm lượng:</span>
                            <span id="panelHamLuong" class="font-semibold text-slate-800 font-mono"></span>
                        </div>
                        <div class="p-2 rounded-lg bg-white border border-slate-100">
                            <span class="text-slate-500 block text-[11px]">Dạng bào chế:</span>
                            <span id="panelDangBaoChe" class="font-semibold text-slate-800"></span>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-5 bg-indigo-50/60 p-4 rounded-xl border border-indigo-200 space-y-3">
                    <span class="font-bold text-indigo-950 uppercase tracking-wide flex items-center gap-2 border-b border-indigo-200 pb-2">
                        <i class="fa-solid fa-industry text-indigo-700"></i> Thông Tin Nhà Sản Xuất
                    </span>
                    <div class="space-y-2">
                        <div>
                            <span class="text-slate-500 block text-[11px]">Tên cơ sở sản xuất:</span>
                            <span id="panelTenNSX" class="font-bold text-slate-900 text-xs"></span>
                        </div>
                        <div>
                            <span class="text-slate-500 block text-[11px]">Mã số thuế:</span>
                            <span id="panelMstNSX" class="font-mono font-bold text-slate-800"></span>
                        </div>
                        <div>
                            <span class="text-slate-500 block text-[11px]">Số giấy phép kinh doanh dược:</span>
                            <span id="panelGiayPhepNSX" class="font-mono font-semibold text-indigo-900"></span>
                        </div>
                        <div>
                            <span class="text-slate-500 block text-[11px]">Địa chỉ:</span>
                            <span id="panelDiaChiNSX" class="text-slate-700 leading-tight block"></span>
                        </div>
                        <div>
                            <span class="text-slate-500 block text-[11px]">Trạng thái tổ chức:</span>
                            <span id="panelTrangThaiToChucNSX"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div id="modalCanhBaoNSXBiThuHoi" class="hidden p-3 bg-red-50 border border-red-300 rounded-xl text-xs flex items-start gap-2 text-red-800">
                <i class="fa-solid fa-triangle-exclamation mt-0.5 shrink-0"></i>
                <span><strong>Cảnh báo:</strong> Nhà sản xuất này đang <strong>bị thu hồi quyền hoạt động</strong> trên hệ thống.</span>
            </div>

            <div class="space-y-2 pt-2 border-t border-slate-100">
                <span class="text-xs font-bold text-slate-700 uppercase tracking-wide block">
                    <i class="fa-solid fa-timeline text-indigo-700 mr-1"></i> Lịch Sử Các Lần Nộp Hồ Sơ:
                </span>
                <div id="panelDanhSachLichSuNopSP" class="space-y-2 max-h-48 overflow-y-auto pr-1"></div>
            </div>
        </div>

        <div class="border-t border-slate-100 pt-3 flex items-center justify-between shrink-0">
            <button type="button" class="dong-modal-hoso-sp px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition-all">Đóng</button>
            <div id="panelCumNutHanhDongSP" class="flex items-center gap-2.5">
                <button type="button" id="panelNutMoModalTuChoiSP" class="px-5 py-2 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 font-bold rounded-xl text-xs transition-all flex items-center gap-1.5 shadow-2xs">
                    <i class="fa-solid fa-circle-xmark"></i> Từ Chối Đăng Ký
                </button>
                <button type="button" id="panelNutMoModalDuyetSP" class="px-6 py-2 bg-indigo-700 hover:bg-indigo-800 text-white font-bold rounded-xl text-xs transition-all flex items-center gap-1.5 shadow-md">
                    <i class="fa-solid fa-circle-check"></i> Phê Duyệt Lưu Hành
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!-- MODAL 2: XEM CHI TIẾT SẢN PHẨM ĐÃ DUYỆT (TAB 2)           -->
<!-- ══════════════════════════════════════════════════════════ -->
<div id="modalChiTietDaDuyet" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-3 sm:p-5 hidden bg-black/60">
    <div class="bg-white rounded-2xl max-w-3xl w-full p-5 sm:p-7 space-y-4 bong-xanh max-h-[92vh] flex flex-col">
        <div class="border-b border-slate-100 pb-3 flex items-center justify-between shrink-0">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-certificate text-emerald-700"></i> Thông Tin Thuốc Đã Cấp Phép Lưu Hành
            </h3>
            <button type="button" class="dong-modal-daduyet text-slate-400 hover:text-slate-700 text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto space-y-4 pr-1 text-xs">
            <div class="p-4 bg-emerald-50/50 rounded-xl border border-emerald-200 space-y-2">
                <div class="flex justify-between items-center">
                    <span id="dtTenThuoc" class="font-bold text-slate-900 text-base"></span>
                    <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 rounded-full font-bold text-[11px]"><i class="fa-solid fa-check-circle"></i> Đã Phê Duyệt</span>
                </div>
                <div class="text-slate-600">Số đăng ký: <span id="dtSoDK" class="font-mono font-bold text-cyan-800"></span></div>
            </div>

            <div class="grid grid-cols-2 gap-3 p-3 bg-nenChinh rounded-xl border border-emerald-900/10">
                <div><span class="text-slate-500 block">Hoạt chất:</span> <span id="dtHoatChat" class="font-semibold text-slate-800"></span></div>
                <div><span class="text-slate-500 block">Hàm lượng:</span> <span id="dtHamLuong" class="font-mono font-semibold text-slate-800"></span></div>
                <div><span class="text-slate-500 block">Dạng bào chế:</span> <span id="dtDangBaoChe" class="font-semibold text-slate-800"></span></div>
                <div><span class="text-slate-500 block">Nhà sản xuất:</span> <span id="dtTenNSX" class="font-semibold text-slate-900"></span></div>
            </div>

            <!-- Khung Mã Giao Dịch Blockchain + Nút Sao Chép -->
            <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                <span class="font-bold text-slate-700 flex items-center gap-1.5">
                    <i class="fa-solid fa-link text-indigo-700"></i> Bằng Chứng Lưu Hàng On-Chain (Ethereum Sepolia):
                </span>
                <div class="flex items-center gap-2 bg-white p-2.5 rounded-lg border border-slate-200">
                    <a id="dtTxLink" href="#" target="_blank" class="flex-1 font-mono text-[11px] font-bold text-cyan-800 hover:underline break-all"></a>
                    <button type="button" id="btnSaoChepModalTx" class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg text-xs font-bold shrink-0 transition-all flex items-center gap-1">
                        <i class="fa-regular fa-copy"></i> Sao chép
                    </button>
                </div>
            </div>
        </div>

        <div class="border-t border-slate-100 pt-3 flex justify-end shrink-0">
            <button type="button" class="dong-modal-daduyet px-5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition-all">Đóng</button>
        </div>
    </div>
</div>

<!-- MODAL XÁC NHẬN PHÊ DUYỆT -->
<div id="modalXacNhanDuyetSP" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden bg-black/60">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 sm:p-8 space-y-5 bong-xanh">
        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-indigo-700"></i> Phê Duyệt Lưu Hành Thuốc
            </h3>
            <button type="button" class="dong-modal-duyet-sp text-slate-400 hover:text-slate-700 text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="formPheDuyetSPReal" action="" method="POST" class="space-y-4 text-xs">
            @csrf
            <div class="p-3.5 bg-nenChinh rounded-xl border border-emerald-900/10 space-y-1.5">
                <div class="flex justify-between">
                    <span class="text-slate-500">Tên thuốc:</span>
                    <span id="duyetModalTenThuoc" class="font-bold text-slate-900 text-right"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Số ĐK lưu hành:</span>
                    <span id="duyetModalSoDK" class="font-mono font-bold text-cyan-800"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Nhà sản xuất:</span>
                    <span id="duyetModalNSX" class="font-semibold text-slate-800 text-right"></span>
                </div>
            </div>

            <div class="p-3 bg-indigo-50 rounded-xl border border-indigo-200 text-indigo-950 space-y-1 leading-relaxed text-[11px]">
                <div class="font-bold flex items-center gap-1.5 text-indigo-800">
                    <i class="fa-solid fa-cube"></i> Tiến trình phê duyệt 2 bước On-Chain:
                </div>
                <p class="text-slate-700 text-[10px]">
                    Hệ thống sẽ mã hóa và ký ghi nhận lên Ethereum Sepolia Testnet trước. Khi Blockchain xác nhận thành công mới chuyển sản phẩm sang Danh Mục Đã Duyệt.
                </p>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                <button type="button" class="dong-modal-duyet-sp px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-all">Hủy</button>
                <button type="submit" id="nutXacNhanDuyetSPOnChain" class="px-6 py-2.5 bg-indigo-700 hover:bg-indigo-800 text-white font-bold rounded-xl shadow-md transition-all flex items-center gap-1.5">
                    <i class="fa-solid fa-shield-halved"></i> Xác Nhận Phê Duyệt
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL XÁC NHẬN TỪ CHỐI -->
<div id="modalTuChoiSP" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden bg-black/60">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 sm:p-8 space-y-5 bong-xanh">
        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-circle-xmark text-red-600"></i> Từ Chối Đăng Ký Lưu Hành
            </h3>
            <button type="button" class="dong-modal-tuchoi-sp text-slate-400 hover:text-slate-700 text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="formTuChoiSPReal" action="" method="POST" class="space-y-4 text-xs">
            @csrf
            <div class="p-3 bg-nenChinh rounded-xl border border-emerald-900/10 space-y-1 mb-1">
                <div class="flex justify-between">
                    <span class="text-slate-500">Tên thuốc:</span>
                    <span id="tuChoiModalTenThuoc" class="font-bold text-slate-900 text-right"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Số ĐK:</span>
                    <span id="tuChoiModalSoDK" class="font-mono font-bold text-cyan-800"></span>
                </div>
            </div>
            <div class="space-y-1">
                <label for="nhapLyDoTuChoiSP" class="font-bold text-slate-700 uppercase tracking-wide block text-[11px]">
                    Lý Do Từ Chối Đăng Ký <span class="text-red-500">*</span>
                </label>
                <textarea name="lyDoTuChoi" id="nhapLyDoTuChoiSP" maxlength="500" rows="3" required placeholder="Nêu rõ lý do từ chối..." class="w-full p-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl font-medium text-slate-900 focus:bg-white focus:border-red-600 outline-none resize-y"></textarea>
                <div class="text-right text-[11px] text-slate-400" id="demKyTuTuChoiSP">0/500</div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                <button type="button" class="dong-modal-tuchoi-sp px-4 py-2.5 bg-slate-100 text-slate-700 font-bold rounded-xl transition-all">Hủy</button>
                <button type="submit" id="nutXacNhanTuChoiSPOffChain" class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl shadow-md transition-all flex items-center gap-1.5">
                    <i class="fa-solid fa-ban"></i> Xác Nhận Từ Chối
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL THÔNG BÁO TIẾN TRÌNH BLOCKCHAIN -->
<div id="modalTienTrinhBlockchain" class="fixed inset-0 bg-black/70 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-sm w-full p-6 space-y-4 text-center bong-xanh">
        <div id="iconTienTrinh" class="text-4xl text-indigo-600 animate-spin mx-auto">
            <i class="fa-solid fa-circle-notch"></i>
        </div>
        <h4 id="tieuDeTienTrinh" class="text-sm font-bold text-slate-900">Đang khởi tạo giao dịch...</h4>
        <p id="noiDungTienTrinh" class="text-xs text-slate-600 leading-relaxed">Đang gửi yêu cầu và chờ mạng Ethereum Sepolia xác nhận...</p>
        <div id="cumNutDongTienTrinh" class="hidden pt-2">
            <button type="button" onclick="location.reload()" class="w-full py-2 bg-emerald-700 text-white font-bold rounded-xl text-xs">Hoàn Thành & Tải Lại Trang</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.danhSachYeuCau = @json($yeuCauDangKys);
    window.danhSachDaDuyet = @json($sanPhamDaDuyets);
</script>
<script src="{{ asset('assets/js/coQuanQuanLy/xetDuyetSanPham.js') }}"></script>
@endpush
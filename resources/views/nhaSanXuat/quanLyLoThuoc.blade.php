@extends('layoutDungChung.master')

@section('title', 'Quản lý lô thuốc - PharmaChain')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/nhaSanXuat/quanLyLoThuoc.css') }}">
@endpush

@section('content')
<div class="space-y-6 max-w-7xl w-full mx-auto">

    <!-- THANH TRÊN: TÌM KIẾM + BỘ LỌC + NÚT ĐĂNG KÝ LÔ MỚI -->
    <div class="bg-white p-5 rounded-2xl border border-emerald-900/10 bong-xanh flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4">

        <div class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 text-xs pointer-events-none">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" id="oTimKiemLo" placeholder="Tìm theo mã lô hoặc tên thuốc..." class="w-full pl-9 pr-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-medium text-slate-900 focus:bg-white focus:border-emerald-700 outline-none">
            </div>

            <!-- Lọc trạng thái thuần Việt -->
            <select id="locTrangThaiLo" class="px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-medium text-slate-700 focus:bg-white focus:border-emerald-700 outline-none cursor-pointer">
                <option value="TAT_CA">-- Tất cả trạng thái --</option>
                <option value="CREATED">Đã tạo (Mới lưu kho)</option>
                <option value="IN_TRANSIT">Đang phân phối</option>
                <option value="HET_HAN">Đã hết hạn sử dụng</option>
                <option value="RECALLED">Đã thu hồi</option>
            </select>

            <!-- Lọc theo hạn sử dụng -->
            <select id="locHanDung" class="px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-medium text-slate-700 focus:bg-white focus:border-emerald-700 outline-none cursor-pointer">
                <option value="TAT_CA">-- Thời hạn sử dụng --</option>
                <option value="CON_HAN">Còn hạn lưu hành</option>
                <option value="CAN_HAN">Cận date (dưới 6 tháng)</option>
                <option value="DA_HET_HAN">Đã quá hạn</option>
            </select>
        </div>

        <button type="button" id="nutMoModalDangKyLo" class="px-5 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl text-xs font-bold shadow-md transition-all flex items-center justify-center gap-2 shrink-0">
            <i class="fa-solid fa-plus-circle text-sm"></i> Đăng Ký Lô Mới
        </button>
    </div>

    <!-- BẢNG DANH SÁCH LÔ THUỐC -->
    <div class="bg-white rounded-2xl border border-emerald-900/10 bong-xanh overflow-hidden">
        <div class="p-4 bg-nenChinh border-b border-emerald-900/10 flex items-center justify-between text-xs">
            <span class="font-bold text-slate-700 uppercase tracking-wide flex items-center gap-2">
                <i class="fa-solid fa-table-list text-emerald-700"></i> Danh Sách Lô Thuốc Sản Xuất & Mã On-chain
            </span>
            <span id="tongSoLoHienThi" class="font-bold text-slate-500 font-mono">Hiển thị: 0 lô</span>
        </div>

        <div class="w-full overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 uppercase tracking-wider border-b border-slate-100 text-[11px]">
                        <th class="py-3 px-2.5 font-bold whitespace-nowrap">Mã Lô</th>
                        <th class="py-3 px-2 font-bold whitespace-nowrap">Mã On-chain (txHash)</th>
                        <th class="py-3 px-2.5 font-bold">Sản Phẩm</th>
                        <th class="py-3 px-2 font-bold text-center whitespace-nowrap">Hạn Dùng</th>
                        <th class="py-3 px-2 font-bold text-right whitespace-nowrap">Tổng</th>
                        <th class="py-3 px-2 font-bold text-right whitespace-nowrap">Khả Dụng</th>
                        <th class="py-3 px-2 font-bold text-center whitespace-nowrap">Trạng Thái</th>
                        <th class="py-3 px-2.5 font-bold text-center whitespace-nowrap">Hành Động</th>
                    </tr>
                </thead>
                <tbody id="thanBangLoThuoc" class="divide-y divide-slate-100 font-medium">
                    <!-- Render bằng JavaScript -->
                </tbody>
            </table>
        </div>

        <!-- Phân trang -->
        <div class="p-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
            <span id="thongTinPhanTrangLo" class="text-slate-500">Đang hiển thị...</span>
            <div class="flex items-center gap-1.5" id="cumNutPhanTrangLo"></div>
        </div>
    </div>

</div>

<!-- MODAL 1: ĐĂNG KÝ LÔ MỚI & SINH MÃ QR 2 CẤP -->
<div id="modalDangKyLo" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden bg-black/60">
    <div class="bg-white rounded-2xl max-w-2xl w-full p-6 sm:p-8 space-y-6 bong-xanh max-h-[90vh] overflow-y-auto">

        <div id="manHinhFormDangKyLo" class="space-y-5">
            <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-box-open text-emerald-700"></i> Khởi Tạo Lô Thuốc Mới & Đồng Bộ Blockchain
                </h3>
                <button type="button" class="dong-modal-lo text-slate-400 hover:text-slate-700 text-lg">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="formSubmitDangKyLo" data-url="{{ route('nha-san-xuat.lo-thuoc.store') }}" class="space-y-4 text-xs">
                @csrf
                <div class="space-y-1">
                    <label for="chonSanPhamDaDuyet" class="font-bold text-slate-700">Chọn Sản Phẩm Đã Được Cơ Quan Quản Lý Phê Duyệt <span class="text-red-500">*</span></label>
                    <select id="chonSanPhamDaDuyet" required class="w-full px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl font-medium text-slate-900 focus:bg-white focus:border-emerald-700 outline-none">
                        <option value="">-- Chọn sản phẩm dược hợp lệ --</option>
                        @if(isset($danhSachSanPham) && count($danhSachSanPham) > 0)
                            @foreach($danhSachSanPham as $sp)
                                <option value="{{ $sp->id }}" data-ten="{{ $sp->tenSanPham }}">{{ $sp->tenSanPham }} (SĐK: {{ $sp->soDangKy }}) – [Đã duyệt]</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label for="nhapMaLo" class="font-bold text-slate-700">Mã Lô Thuốc <span class="text-red-500">*</span></label>
                        <input type="text" id="nhapMaLo" required placeholder="VD: LOT-PARA-2026-03" class="w-full px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl font-mono uppercase font-semibold text-slate-900 focus:bg-white focus:border-emerald-700 outline-none">
                    </div>
                    <div class="space-y-1">
                        <label for="nhapSoLuong" class="font-bold text-slate-700">Tổng Số Lượng (hộp) <span class="text-red-500">*</span></label>
                        <input type="number" id="nhapSoLuong" required min="10" placeholder="VD: 5000" class="w-full px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl font-semibold text-slate-900 focus:bg-white focus:border-emerald-700 outline-none">
                    </div>
                    <div class="space-y-1">
                        <label for="nhapNgaySX" class="font-bold text-slate-700">Ngày Sản Xuất <span class="text-red-500">*</span></label>
                        <input type="date" id="nhapNgaySX" required class="w-full px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-slate-900 focus:bg-white focus:border-emerald-700 outline-none">
                    </div>
                    <div class="space-y-1">
                        <label for="nhapHanDung" class="font-bold text-slate-700">Hạn Sử Dụng <span class="text-red-500">*</span></label>
                        <input type="date" id="nhapHanDung" required class="w-full px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-slate-900 focus:bg-white focus:border-emerald-700 outline-none">
                    </div>
                </div>

                <div class="p-3 bg-cyan-50 rounded-xl border border-cyan-800/15 text-[11px] text-cyan-900 flex items-start gap-2">
                    <i class="fa-solid fa-shield-halved text-cyan-800 mt-0.5 shrink-0"></i>
                    <span>Hệ thống sử dụng kiến trúc <strong>Custodial Signer Service</strong>. Khi gửi yêu cầu, hệ thống sẽ tự động đưa vào hàng đợi xử lý ngầm (Queue Worker) để ký On-chain, tạo mã QR kép và khởi tạo kho mà không cần thao tác ví cá nhân MetaMask.</span>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" class="dong-modal-lo px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-all">
                        Hủy
                    </button>
                    <button type="submit" id="btnXacNhanDangKyLo" class="px-6 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white font-bold rounded-xl shadow-md transition-all flex items-center gap-2">
                        <i class="fa-solid fa-check"></i> Xác nhận đăng ký
                    </button>
                </div>
            </form>
        </div>

        <div id="manHinhKetQuaSinhQR" class="hidden space-y-5">
            <div class="text-center space-y-2">
                <div class="w-12 h-12 mx-auto rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-2xl">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <h3 class="text-base font-bold text-slate-900">Đã Tiếp Nhận Yêu Cầu Tạo Lô!</h3>
                <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-amber-50 text-amber-800 border border-amber-200 rounded-full text-xs font-semibold">
                    <i class="fa-solid fa-spinner fa-spin text-amber-600"></i>
                    <span>Hàng đợi (Queue Job) đang thực hiện ký On-chain & sinh mã QR...</span>
                </div>
                <p class="text-xs text-slate-500 font-mono" id="hienThiTxHashTaoLo">Mã lô: <strong id="hienThiMaLoQR" class="text-emerald-700 font-bold"></strong></p>
            </div>

            <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-2 text-xs text-slate-600">
                <div class="flex items-center gap-2 text-emerald-800 font-bold">
                    <i class="fa-solid fa-circle-info"></i> Quy trình tự động đang được tiến hành ngầm:
                </div>
                <ul class="list-disc list-inside space-y-1 text-[11px] pl-1 text-slate-700">
                    <li>Signer Service gọi Smart Contract đăng ký lô thuốc trên Sepolia.</li>
                    <li>Khởi tạo mã QR kép (tem thùng và tem hộp).</li>
                    <li>Khởi tạo số lượng tồn kho ban đầu chính xác một lần.</li>
                    <li>Ghi vết Audit Log và Transaction Hash vào hệ thống.</li>
                </ul>
            </div>

            <div class="pt-2">
                <button type="button" class="dong-modal-lo w-full py-3 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-xl shadow-md transition-all">
                    Đã hiểu & Xem Danh Sách Lô
                </button>
            </div>
        </div>

    </div>
</div>

<!-- MODAL 2: PHÂN PHỐI LÔ THUỐC -->
<div id="modalPhanPhoiLo" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden bg-black/60">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 sm:p-8 space-y-5 bong-xanh">
        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-truck-fast text-cyan-800"></i> Khởi Tạo Giao Dịch Chuyển Giao (Transfer)
            </h3>
            <button type="button" id="nutDongModalPhanPhoi" class="text-slate-400 hover:text-slate-700 text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="formSubmitPhanPhoi" class="space-y-4 text-xs">
            <div class="p-3 bg-nenChinh rounded-xl border border-emerald-900/10 space-y-1.5">
                <div class="flex justify-between">
                    <span class="text-slate-500">Mã lô chuyển giao:</span>
                    <span id="phanPhoiMaLoText" class="font-mono font-bold text-slate-900"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Số lượng khả dụng xuất kho:</span>
                    <span id="phanPhoiSoLuongConLaiText" class="font-bold text-emerald-700 font-mono"></span>
                </div>
                <div class="text-[10px] text-slate-400 italic">
                    (Khả dụng = Tồn kho thực trừ đi các đơn chuyển giao đang chờ PENDING)
                </div>
            </div>

            <!-- ID lô thuốc ẩn để gửi API -->
            <input type="hidden" id="phanPhoiLoThuocId" value="">

            <div class="space-y-1">
                <label for="chonNhaPhanPhoi" class="font-bold text-slate-700">
                    Đơn Vị Tiếp Nhận (Nhà Phân Phối Đã Duyệt) <span class="text-red-500">*</span>
                </label>
                <select id="chonNhaPhanPhoi" required
                    class="w-full px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl font-medium text-slate-900 focus:bg-white focus:border-emerald-700 outline-none">
                    <option value="">-- Chọn nhà phân phối đã duyệt --</option>
                    @forelse($danhSachNhaPhanPhoi ?? [] as $npp)
                        <option value="{{ $npp->id }}">
                            {{ $npp->tenToChuc }}
                            @if(!empty($npp->maSoThue))
                                (MST: {{ $npp->maSoThue }})
                            @endif
                        </option>
                    @empty
                        <option value="" disabled>Chưa có nhà phân phối nào được phê duyệt trong hệ thống</option>
                    @endforelse
                </select>
                <p class="text-[11px] text-slate-500">Danh sách lấy từ CSDL (tổ chức loại Nhà phân phối, trạng thái Đã duyệt).</p>
            </div>

            <div class="space-y-1">
                <label for="soLuongPhanPhoi" class="font-bold text-slate-700">Số Lượng Chuyển Giao <span class="text-red-500">*</span></label>
                <input type="number" id="soLuongPhanPhoi" required min="1" placeholder="Nhập số lượng xuất kho..." class="w-full px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl font-semibold text-slate-900 focus:bg-white focus:border-emerald-700 outline-none">
                <span id="loiSoLuongPhanPhoi" class="text-[11px] text-red-600 hidden font-bold">Số lượng chuyển giao không được vượt quá số khả dụng!</span>
            </div>

            <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-[11px] text-amber-800 leading-relaxed">
                <i class="fa-solid fa-triangle-exclamation text-amber-600 mr-1"></i> Giao dịch khởi tạo sẽ ở trạng thái <strong>PENDING</strong>. Số lượng này sẽ được trừ khỏi số khả dụng ngay lập tức.
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                <button type="button" id="nutHuyPhanPhoi" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-all">
                    Hủy
                </button>
                <button type="submit" class="px-6 py-2.5 bg-cyan-800 hover:bg-cyan-900 text-white font-bold rounded-xl shadow-md transition-all flex items-center gap-2">
                    <i class="fa-solid fa-paper-plane"></i> Gửi Yêu Cầu Chuyển Giao
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 3: XEM CHI TIẾT LÔ THUỐC -->
<div id="modalChiTietLo" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden bg-black/60">
    <div class="bg-white rounded-2xl max-w-2xl w-full p-6 sm:p-8 space-y-6 bong-xanh max-h-[90vh] overflow-y-auto">
        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-base font-bold text-slate-900">Chi Tiết Lô Thuốc:</span>
                <span id="modalMaLoTitle" class="font-mono font-bold text-emerald-800 bg-emerald-50 px-2.5 py-0.5 rounded text-xs border border-emerald-200"></span>
            </div>
            <button type="button" id="nutDongModalChiTietLo" class="text-slate-400 hover:text-slate-700 text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-4 bg-nenChinh rounded-xl border border-emerald-900/10 text-xs">
            <div>
                <span class="text-slate-500 block">Sản phẩm:</span>
                <span id="modalChiTietSanPham" class="font-bold text-slate-900"></span>
            </div>
            <div>
                <span class="text-slate-500 block">Ngày sản xuất:</span>
                <span id="modalChiTietNgaySX" class="font-semibold text-slate-800 font-mono"></span>
            </div>
            <div>
                <span class="text-slate-500 block">Hạn dùng:</span>
                <span id="modalChiTietHanDung" class="font-semibold text-slate-800 font-mono"></span>
            </div>
            <div>
                <span class="text-slate-500 block">Tổng / Khả dụng:</span>
                <span id="modalChiTietSoLuong" class="font-bold text-cyan-800 font-mono"></span>
            </div>
        </div>

        <div class="space-y-1.5 text-xs">
            <span class="font-bold text-slate-700">Bằng chứng Blockchain (Polygon Testnet Explorer):</span>
            <div class="flex items-center gap-2 p-2.5 bg-slate-50 border border-slate-200 rounded-xl">
                <a id="modalChiTietTxLink" href="#" target="_blank" class="flex-1 font-mono text-[11px] text-cyan-800 hover:underline break-all flex items-center gap-1.5">
                    <span id="modalChiTietTxHash">0x...</span>
                    <i class="fa-solid fa-arrow-up-right-from-square text-[10px] shrink-0 text-slate-400"></i>
                </a>
                <button type="button" id="nutSaoChepModalTx" class="px-2.5 py-1 bg-white hover:bg-slate-100 border border-slate-300 text-slate-700 rounded-lg text-[11px] font-bold shrink-0">
                    <i class="fa-regular fa-copy"></i> Sao chép
                </button>
            </div>
        </div>

        <div class="p-4 bg-cyan-50/70 rounded-xl border border-cyan-800/20 space-y-3">
            <div class="flex items-center justify-between">
                <div>
                    <h5 class="font-bold text-xs text-cyan-950 uppercase tracking-wide">Quản Lý & Tải Mã QR Lô Hàng</h5>
                    <p class="text-[11px] text-slate-600 mt-0.5">Xuất bộ mã để in tem nhãn thùng lớn hoặc in lên từng hộp/vỉ thuốc lẻ.</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2 pt-1">
                <button type="button" id="nutXuatQRThungModal" class="px-3.5 py-2 bg-white hover:bg-emerald-50 border border-emerald-300 text-emerald-800 font-bold text-xs rounded-xl transition-all flex items-center gap-1.5 shadow-2xs" title="Tải QR lô">
                    <i class="fa-solid fa-qrcode"></i> Tải Mã QR Lô (Thùng)
                </button>
                <button type="button" id="nutXuatQRDonViZipModal" class="px-3.5 py-2 bg-cyan-800 hover:bg-cyan-900 text-white font-bold text-xs rounded-xl transition-all flex items-center gap-1.5 shadow-2xs" title="Tải QR đơn vị (ZIP)">
                    <i class="fa-solid fa-file-zipper"></i> Xuất Trọn Bộ QR Đơn Vị (.ZIP)
                </button>
                <button type="button" id="nutXuatQRDonViCsvModal" class="px-3.5 py-2 bg-white hover:bg-cyan-50 border border-cyan-300 text-cyan-900 font-bold text-xs rounded-xl transition-all flex items-center gap-1.5 shadow-2xs">
                    <i class="fa-solid fa-file-csv"></i> Xuất Danh Sách Serial (.CSV)
                </button>
            </div>
        </div>

        <div class="space-y-3 text-xs pt-1">
            <h4 class="font-bold uppercase tracking-wider text-slate-700 flex items-center gap-2">
                <i class="fa-solid fa-timeline text-emerald-700"></i> Lịch Sử Chuyển Giao Lô Hàng
            </h4>
            <div id="danhSachTimelineChuyenGiao" class="relative pl-6 border-l-2 border-emerald-900/20 space-y-4"></div>
        </div>

        <div class="pt-3 border-t border-slate-100 flex justify-end">
            <button type="button" id="nutDongModalChiTietLoBottom" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-all">
                Đóng
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/nhaSanXuat/quanLyLoThuoc.js') }}"></script>
@endpush
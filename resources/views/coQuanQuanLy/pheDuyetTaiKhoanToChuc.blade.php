@extends('layoutDungChung.master')

@section('title', 'Phê duyệt tài khoản tổ chức - PharmaChain')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/coQuanQuanLy/pheDuyetTaiKhoanToChuc.css') }}">
@endpush

@section('content')
<div class="space-y-6 max-w-7xl w-full mx-auto">

    @if (session('thanhCong'))
    <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-xs font-bold text-emerald-800 flex items-center gap-2 shadow-sm">
        <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
        <span>{{ session('thanhCong') }}</span>
    </div>
    @endif

    <!-- BỘ LỌC DỮ LIỆU -->
    <form method="GET" action="{{ route('co-quan.phe-duyet-tai-khoan') }}" id="formBoLocToChuc" class="bg-white p-5 rounded-2xl border border-emerald-900/10 bong-xanh">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 text-xs pointer-events-none">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" name="tuKhoa" value="{{ $tuKhoa }}" placeholder="Tìm theo tên tổ chức, MST, Số GPKD..." class="w-full pl-9 pr-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-medium text-slate-900 focus:bg-white focus:border-indigo-700 outline-none">
            </div>

            <select name="loaiToChuc" onchange="this.form.submit()" class="px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:border-indigo-700 outline-none cursor-pointer">
                <option value="TAT_CA" {{ $loaiToChuc === 'TAT_CA' ? 'selected' : '' }}>-- Tất cả loại hình tổ chức --</option>
                <option value="NHA_SAN_XUAT" {{ $loaiToChuc === 'NHA_SAN_XUAT' ? 'selected' : '' }}>Nhà sản xuất</option>
                <option value="NHA_PHAN_PHOI" {{ $loaiToChuc === 'NHA_PHAN_PHOI' ? 'selected' : '' }}>Nhà phân phối</option>
                <option value="NHA_THUOC" {{ $loaiToChuc === 'NHA_THUOC' ? 'selected' : '' }}>Nhà thuốc</option>
            </select>

            <select name="trangThaiDuyet" onchange="this.form.submit()" class="px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:border-indigo-700 outline-none cursor-pointer">
                <option value="CHO_DUYET" {{ $trangThaiDuyet === 'CHO_DUYET' ? 'selected' : '' }}>Chờ xử lý (Chưa duyệt)</option>
                <option value="DA_DUYET" {{ $trangThaiDuyet === 'DA_DUYET' ? 'selected' : '' }}>Đã phê duyệt</option>
                <option value="TU_CHOI" {{ $trangThaiDuyet === 'TU_CHOI' ? 'selected' : '' }}>Bị từ chối</option>
                <option value="TAT_CA" {{ $trangThaiDuyet === 'TAT_CA' ? 'selected' : '' }}>Toàn bộ trạng thái</option>
            </select>
        </div>
    </form>

    <!-- BẢNG DANH SÁCH -->
    <div class="bg-white rounded-2xl border border-emerald-900/10 bong-xanh overflow-hidden">
        <div class="p-4 bg-nenChinh border-b border-emerald-900/10 flex items-center justify-between text-xs">
            <span class="font-bold text-slate-700 uppercase tracking-wide flex items-center gap-2">
                <i class="fa-solid fa-building-circle-check text-indigo-700"></i> Danh Sách Hồ Sơ Đăng Ký Tổ Chức
            </span>
            <span class="font-bold text-slate-500 font-mono">Hiển thị: {{ $toChucs->total() }} hồ sơ</span>
        </div>

        <div class="w-full overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 uppercase tracking-wider border-b border-slate-100 text-[11px]">
                        <th class="py-3.5 px-3 font-bold">Tên Tổ Chức</th>
                        <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Loại Hình</th>
                        <th class="py-3.5 px-3 font-bold whitespace-nowrap">Mã Số Thuế</th>
                        <th class="py-3.5 px-3 font-bold whitespace-nowrap">Số GPKD Dược</th>
                        <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Ngày Nộp</th>
                        <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Lần Nộp</th>
                        <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Trạng Thái</th>
                        <th class="py-3.5 px-3 font-bold text-center whitespace-nowrap">Hành Động</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse ($toChucs as $toChuc)
                    @php
                    $yeuCauMoiNhat =$toChuc->yeuCauDangKys->sortByDesc('id')->first();
                    $taiKhoanDaiDien =$toChuc->taiKhoans->first();
                    $soLanNop =$toChuc->yeuCauDangKys->max('versionHoSo') ?? 1;
                    $diaChiViIdentity =$toChuc->blockchainIdentity?->address;
                    @endphp
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3 px-3">
                            <div class="font-bold text-slate-900">{{ $toChuc->tenToChuc }}</div>
                            <div class="text-[11px] text-slate-500">{{ $toChuc->diaChi }}</div>
                        </td>
                        <td class="py-3 px-2.5 text-center">
                            @if($toChuc->loaiToChuc->value === 'NHA_SAN_XUAT')
                            <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold text-[10px]">Nhà Sản Xuất</span>
                            @elseif($toChuc->loaiToChuc->value === 'NHA_PHAN_PHOI')
                            <span class="px-2 py-0.5 rounded-full bg-cyan-100 text-cyan-800 font-bold text-[10px]">Nhà Phân Phối</span>
                            @else
                            <span class="px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-800 font-bold text-[10px]">Nhà Thuốc</span>
                            @endif
                        </td>
                        <td class="py-3 px-3 font-mono text-slate-800">{{ $toChuc->maSoThue }}</td>
                        <td class="py-3 px-3 font-mono font-bold text-indigo-900">{{ $toChuc->soGiayPhepDuoc }}</td>
                        <td class="py-3 px-2.5 text-center font-mono text-slate-600">
                            {{ $yeuCauMoiNhat ? \Carbon\Carbon::parse($yeuCauMoiNhat->ngayGui)->format('d/m/Y') : 'N/A' }}
                        </td>
                        <td class="py-3 px-2.5 text-center">
                            <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-800 font-mono font-bold text-[11px] border border-slate-200">
                                Lần {{ $soLanNop }}
                            </span>
                        </td>
                        <td class="py-3 px-2.5 text-center">
                            @if($toChuc->trangThaiDuyet->value === 'CHO_DUYET')
                            <span class="px-2.5 py-1 rounded-full bg-amber-100 text-amber-800 font-bold text-[10px] inline-flex items-center gap-1">
                                <i class="fa-solid fa-clock"></i> Chờ duyệt
                            </span>
                            @elseif($toChuc->trangThaiDuyet->value === 'DA_DUYET')
                            <span class="px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 font-bold text-[10px] inline-flex items-center gap-1">
                                <i class="fa-solid fa-circle-check"></i> Đã duyệt
                            </span>
                            @else
                            <span class="px-2.5 py-1 rounded-full bg-red-100 text-red-800 font-bold text-[10px] inline-flex items-center gap-1">
                                <i class="fa-solid fa-circle-xmark"></i> Bị từ chối
                            </span>
                            @endif
                        </td>
                        <td class="py-3 px-3 text-center">
                            <button type="button" class="btn-xem-ho-so px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold rounded-lg border border-indigo-200 transition-all text-[11px] cursor-pointer"
                                data-id="{{ $toChuc->id }}"
                                data-loai-to-chuc="{{ $toChuc->loaiToChuc->value }}"
                                data-dia-chi-vi="{{ $diaChiViIdentity }}"
                                data-tendangnhap="{{ $taiKhoanDaiDien?->tenDangNhap }}"
                                data-trangthai="{{ $toChuc->trangThaiDuyet->value }}"
                                data-lichsu="{{ json_encode($toChuc->yeuCauDangKys->sortByDesc('id')->map(fn($y) => [
                                        'phienBan' => $y->versionHoSo,
                                        'ngayGui' => \Carbon\Carbon::parse($y->ngayGui)->format('H:i d/m/Y'),
                                        'trangThai' => $y->trangThai,
                                        'lyDoTuChoi' => $y->lyDoTuChoi,
                                        'ngayXuLy' => $y->ngayXuLy ? \Carbon\Carbon::parse($y->ngayXuLy)->format('H:i d/m/Y') : null,
                                        'tenToChuc' => $y->tenToChuc ?? $toChuc->tenToChuc,
                                        'maSoThue' => $y->maSoThue ?? $toChuc->maSoThue,
                                        'soDienThoai' => $y->soDienThoai ?? $toChuc->soDienThoai,
                                        'diaChi' => $y->diaChi ?? $toChuc->diaChi,
                                        'soGiayPhepDuoc' => $y->soGiayPhepDuoc ?? $toChuc->soGiayPhepDuoc,
                                        'coQuanCap' => $y->coQuanCap ?? $toChuc->coQuanCap,
                                        'ngayCapGiayPhep' => $y->ngayCapGiayPhep ? \Carbon\Carbon::parse($y->ngayCapGiayPhep)->format('d/m/Y') : 'N/A',
                                        'taiLieus' => $y->taiLieus->map(fn($t) => ['id' => $t->id, 'ten' =>$t->tenTaiLieu])->values(),
                                    ])->values()) }}">
                                <i class="fa-solid fa-eye mr-1"></i> Xem hồ sơ
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-8 text-center text-slate-400">
                            <i class="fa-solid fa-folder-open text-3xl mb-2 block"></i>
                            Không tìm thấy hồ sơ đăng ký tổ chức nào phù hợp.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100 flex items-center justify-between text-xs">
            {{ $toChucs->links() }}
        </div>
    </div>
</div>

<!-- MODAL XEM HỒ SƠ -->
<div id="modalXemHoSo" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-3 sm:p-5 hidden">
    <div class="bg-white rounded-2xl max-w-5xl w-full p-5 sm:p-7 space-y-4 bong-xanh max-h-[92vh] flex flex-col">
        <div class="border-b border-slate-100 pb-3 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2.5 flex-wrap">
                <span class="text-base font-bold text-slate-900">Hồ Sơ Đăng Ký Tổ Chức:</span>
                <span id="panelTenToChucTitle" class="font-bold text-indigo-900 bg-indigo-50 px-2.5 py-0.5 rounded text-xs border border-indigo-200"></span>
            </div>
            <button type="button" class="dong-modal-hoso text-slate-400 hover:text-slate-700 text-lg cursor-pointer">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto space-y-4 pr-1 text-xs">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
                <div class="lg:col-span-7 bg-nenChinh p-4 rounded-xl border border-emerald-900/10 space-y-3">
                    <span class="font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2 border-b border-slate-200 pb-2">
                        <i class="fa-solid fa-id-card text-indigo-700"></i> Thông Tin Pháp Lý Kê Khai (<span id="panelHeaderVersionLabel" class="text-indigo-700 font-bold">Lần mới nhất</span>)
                    </span>
                    <div class="grid grid-cols-2 gap-2.5">
                        <div class="col-span-2"><span class="text-slate-500 block">Tên doanh nghiệp:</span><span id="panelTenToChuc" class="font-bold text-slate-900 text-sm"></span></div>
                        <div><span class="text-slate-500 block">Mã số thuế:</span><span id="panelMaSoThue" class="font-mono font-bold text-slate-900"></span></div>
                        <div><span class="text-slate-500 block">Tên đăng nhập:</span><span id="panelTenDangNhap" class="font-mono font-bold text-slate-900"></span></div>
                        <div><span class="text-slate-500 block">Số điện thoại:</span><span id="panelSoDienThoai" class="font-mono text-slate-800"></span></div>
                        <div><span class="text-slate-500 block">Số GPKD dược:</span><span id="panelSoGiayPhep" class="font-mono font-bold text-indigo-800"></span></div>
                        <div><span class="text-slate-500 block">Cơ quan cấp:</span><span id="panelCoQuanCap" class="font-semibold text-slate-800"></span></div>
                        <div><span class="text-slate-500 block">Ngày cấp:</span><span id="panelNgayCap" class="font-mono text-slate-700"></span></div>
                        <div class="col-span-2"><span class="text-slate-500 block">Địa chỉ trụ sở:</span><span id="panelDiaChi" class="font-medium text-slate-800"></span></div>
                    </div>
                </div>

                <div class="lg:col-span-5 bg-indigo-50/60 p-4 rounded-xl border border-indigo-200 space-y-3">
                    <div class="flex items-center justify-between border-b border-indigo-200 pb-2">
                        <span class="font-bold text-indigo-950 uppercase tracking-wide flex items-center gap-1.5">
                            <i class="fa-solid fa-file-shield text-indigo-700"></i> Hồ Sơ Minh Chứng
                        </span>
                        <span id="panelViewingVersionBadge" class="px-2 py-0.5 rounded bg-indigo-100 text-indigo-800 font-bold text-[10px]">
                            Lần nộp mới nhất
                        </span>
                    </div>
                    <div id="panelDanhSachTepMinhChung" class="space-y-2 max-h-56 overflow-y-auto pr-1"></div>
                </div>
            </div>

            <div class="border-t border-slate-200 pt-3 space-y-2">
                <span class="font-bold text-slate-700 uppercase tracking-wide flex items-center gap-2 text-[11px]">
                    <i class="fa-solid fa-clock-rotate-left text-indigo-700"></i> Lịch Sử Các Lần Nộp Hồ Sơ Đăng Ký
                </span>
                <div id="panelDanhSachLichSuNop" class="space-y-2"></div>
            </div>
        </div>

        <div class="border-t border-slate-100 pt-3 flex items-center justify-between shrink-0">
            <button type="button" class="dong-modal-hoso px-4 py-2 bg-slate-100 text-slate-700 font-bold rounded-xl text-xs cursor-pointer">Đóng</button>
            <div id="panelCumNutHanhDong" class="flex items-center gap-2.5">
                <button type="button" id="panelNutMoModalTuChoi" class="px-5 py-2 bg-red-50 text-red-600 border border-red-200 font-bold rounded-xl text-xs flex items-center gap-1.5 cursor-pointer hover:bg-red-100">
                    <i class="fa-solid fa-circle-xmark"></i> Từ Chối Hồ Sơ
                </button>
                <button type="button" id="panelNutMoModalDuyet" class="px-6 py-2 bg-indigo-700 text-white font-bold rounded-xl text-xs flex items-center gap-1.5 shadow-md cursor-pointer hover:bg-indigo-800">
                    <i class="fa-solid fa-circle-check"></i> Phê Duyệt Hồ Sơ
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DUYỆT -->
<div id="modalXacNhanDuyet" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 sm:p-8 space-y-5 bong-xanh">
        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-indigo-700"></i> Phê Duyệt & Cấp Ví Sepolia
            </h3>
            <button type="button" class="dong-modal-duyet text-slate-400 hover:text-slate-700 text-lg cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form id="formActionDuyetToChuc" method="POST" action="" class="space-y-4 text-xs">
            @csrf
            <input type="hidden" name="trangThaiDuyet" value="DA_DUYET">
            <input type="hidden" name="txHash" id="duyetModalTxHash" value="">
            <input type="hidden" name="diaChiVi" id="duyetModalDiaChiVi" value="">

            <div class="p-3.5 bg-nenChinh rounded-xl border border-emerald-900/10 space-y-1.5">
                <div class="flex justify-between"><span class="text-slate-500">Tổ chức:</span><span id="duyetModalTenToChuc" class="font-bold text-slate-900"></span></div>
                <div class="flex justify-between"><span class="text-slate-500">Mã số thuế:</span><span id="duyetModalMST" class="font-mono font-bold text-slate-900"></span></div>
                <div class="flex justify-between"><span class="text-slate-500">Địa chỉ ví On-chain:</span><span id="duyetModalAddressText" class="font-mono text-indigo-900 font-bold truncate max-w-[180px]">Tự động khởi tạo</span></div>
            </div>

            <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-[11px] text-amber-800 flex items-start gap-2">
                <i class="fa-solid fa-triangle-exclamation text-amber-600 text-sm mt-0.5"></i>
                <span>Thao tác này sẽ yêu cầu xác nhận giao dịch qua ví **MetaMask** của bạn để cấp vai trò On-chain trên mạng Sepolia.</span>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                <button type="button" class="dong-modal-duyet px-4 py-2.5 bg-slate-100 text-slate-700 font-bold rounded-xl cursor-pointer">Hủy</button>
                <button type="submit" id="btnSubmitConfirmDuyet" class="px-6 py-2.5 bg-indigo-700 hover:bg-indigo-800 text-white font-bold rounded-xl shadow-md flex items-center gap-1.5 cursor-pointer">
                    <i class="fa-solid fa-shield-halved"></i> Phê Duyệt qua MetaMask
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL TỪ CHỐI -->
<div id="modalTuChoiHoSo" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 sm:p-8 space-y-5 bong-xanh">
        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2"><i class="fa-solid fa-circle-xmark text-red-600"></i> Từ Chối Hồ Sơ</h3>
            <button type="button" class="dong-modal-tuchoi text-slate-400 hover:text-slate-700 text-lg cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form id="formActionTuChoiToChuc" method="POST" action="" class="space-y-4 text-xs">
            @csrf
            <input type="hidden" name="trangThaiDuyet" value="TU_CHOI">
            <div class="space-y-1">
                <label for="nhapLyDoTuChoiToChuc" class="font-bold text-slate-700 uppercase tracking-wide block text-[11px]">Lý Do Từ Chối <span class="text-red-500">*</span></label>
                <textarea id="nhapLyDoTuChoiToChuc" name="lyDo" required maxlength="500" rows="3" placeholder="Nhập chi tiết lý do từ chối hồ sơ pháp lý..." class="w-full p-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl font-medium text-slate-900 outline-none focus:border-red-500"></textarea>
            </div>
            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                <button type="button" class="dong-modal-tuchoi px-4 py-2.5 bg-slate-100 text-slate-700 font-bold rounded-xl cursor-pointer">Hủy</button>
                <button type="submit" id="nutXacNhanTuChoi" disabled class="px-6 py-2.5 bg-slate-200 text-slate-400 font-bold rounded-xl cursor-not-allowed">Xác Nhận Từ Chối</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/ethers/6.7.0/ethers.umd.min.js"></script>
<script src="{{ asset('assets/js/coQuanQuanLy/pheDuyetTaiKhoanToChuc.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const CONTRACT_ADDRESS = "{{ config('truyXuat.blockchain.diaChiHopDong') }}";
        const CONTRACT_ABI = [
            "function grantRole(address account, bytes32 role) external"
        ];

        const ROLE_HASHES = {
            'NHA_SAN_XUAT': '0x2c68e1a6136d0a7a03009653a1a1f0d3674681729f64bf501b4c8030f81d1134',
            'NHA_PHAN_PHOI': '0xed2090967332f14aa52a132e49c71e84d436efb3d1bc848f0701198544b6c318',
            'NHA_THUOC': '0xb23b33379ec8f121df4c6b63d9178db7ae4045f36e89eeff0ee0279d6f0be20d'
        };

        const formDuyet = document.getElementById('formActionDuyetToChuc');
        const inputTxHash = document.getElementById('duyetModalTxHash');
        const inputDiaChiVi = document.getElementById('duyetModalDiaChiVi');
        const btnSubmitDuyet = document.getElementById('btnSubmitConfirmDuyet');

        if (formDuyet) {
            formDuyet.addEventListener('submit', async function(e) {
                // Nếu đã ký thành công và có txHash thì cho submit về server
                if (inputTxHash && inputTxHash.value) {
                    return true;
                }

                e.preventDefault();

                if (typeof window.ethereum === 'undefined') {
                    alert('Vui lòng cài đặt tiện ích MetaMask trên trình duyệt để phê duyệt On-chain!');
                    return;
                }

                const loaiToChuc = formDuyet.getAttribute('data-loai-to-chuc') || 'NHA_SAN_XUAT';
                const roleHash = ROLE_HASHES[loaiToChuc];

                if (!roleHash) {
                    alert('Loại hình tổ chức không hợp lệ để cấp vai trò On-chain!');
                    return;
                }

                try {
                    if (btnSubmitDuyet) {
                        btnSubmitDuyet.disabled = true;
                        btnSubmitDuyet.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Đang chờ MetaMask...';
                    }

                    // 1. Kết nối ví MetaMask
                    const provider = new ethers.BrowserProvider(window.ethereum);
                    await provider.send("eth_requestAccounts", []);

                    // 2. Kiểm tra & chuyển sang mạng Sepolia (ChainID: 11155111 = 0xaa36a7)
                    const network = await provider.getNetwork();
                    if (network.chainId !== 11155111n) {
                        try {
                            await window.ethereum.request({
                                method: 'wallet_switchEthereumChain',
                                params: [{
                                    chainId: '0xaa36a7'
                                }],
                            });
                        } catch (switchErr) {
                            alert('Vui lòng chuyển MetaMask sang mạng Ethereum Sepolia Testnet!');
                            if (btnSubmitDuyet) {
                                btnSubmitDuyet.disabled = false;
                                btnSubmitDuyet.innerHTML = '<i class="fa-solid fa-shield-halved mr-1"></i> Phê Duyệt qua MetaMask';
                            }
                            return;
                        }
                    }

                    const signer = await provider.getSigner();

                    // 3. Đảm bảo có địa chỉ ví cho Tổ chức (Nếu chưa có thì tự sinh ngẫu nhiên)
                    let targetAddress = inputDiaChiVi ? inputDiaChiVi.value : '';
                    if (!targetAddress || targetAddress.length !== 42) {
                        const randomWallet = ethers.Wallet.createRandom();
                        targetAddress = randomWallet.address;
                        if (inputDiaChiVi) inputDiaChiVi.value = targetAddress;
                    }

                    // 4. Gọi hàm grantRole trên Smart Contract
                    const contract = new ethers.Contract(CONTRACT_ADDRESS, CONTRACT_ABI, signer);
                    console.log(`Gửi giao dịch grantRole: Address=${targetAddress}, RoleHash=${roleHash}`);

                    const tx = await contract.grantRole(targetAddress, roleHash);

                    // Gán mã TxHash thu được
                    if (inputTxHash) inputTxHash.value = tx.hash;

                    if (btnSubmitDuyet) {
                        btnSubmitDuyet.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Đang lưu CSDL...';
                    }

                    // Gửi form về Laravel Backend
                    formDuyet.submit();

                } catch (error) {
                    console.error("Lỗi giao dịch MetaMask:", error);
                    if (btnSubmitDuyet) {
                        btnSubmitDuyet.disabled = false;
                        btnSubmitDuyet.innerHTML = '<i class="fa-solid fa-shield-halved mr-1"></i> Phê Duyệt qua MetaMask';
                    }

                    if (error.code === 4001 || error.code === 'ACTION_REJECTED') {
                        alert('Bạn đã hủy/từ chối xác nhận giao dịch trên MetaMask.');
                    } else {
                        alert('Giao dịch Blockchain thất bại: ' + (error.reason || error.message));
                    }
                }
            });
        }
    });
</script>
@endpush
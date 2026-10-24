@extends('layoutDungChung.master')

@section('title', 'Quản lý quyền hạn - PharmaChain')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/coQuanQuanLy/quanLyQuyenHan.css') }}">
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
    <form method="GET" action="{{ route('co-quan.quan-ly-quyen-han') }}" class="bg-white p-5 rounded-2xl border border-emerald-900/10 bong-xanh">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 text-xs pointer-events-none">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" name="tuKhoa" value="{{ $tuKhoa }}" placeholder="Tìm theo tên tổ chức, MST, GPKD..." class="w-full pl-9 pr-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-medium text-slate-900 focus:bg-white focus:border-indigo-700 outline-none">
            </div>

            <select name="loaiToChuc" onchange="this.form.submit()" class="px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:border-indigo-700 outline-none cursor-pointer">
                <option value="TAT_CA" {{ $loaiToChuc === 'TAT_CA' ? 'selected' : '' }}>-- Tất cả loại hình --</option>
                <option value="NHA_SAN_XUAT" {{ $loaiToChuc === 'NHA_SAN_XUAT' ? 'selected' : '' }}>Nhà sản xuất</option>
                <option value="NHA_PHAN_PHOI" {{ $loaiToChuc === 'NHA_PHAN_PHOI' ? 'selected' : '' }}>Nhà phân phối</option>
                <option value="NHA_THUOC" {{ $loaiToChuc === 'NHA_THUOC' ? 'selected' : '' }}>Nhà thuốc</option>
            </select>

            <select name="trangThaiPheDuyet" onchange="this.form.submit()" class="px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:border-indigo-700 outline-none cursor-pointer">
                <option value="TAT_CA" {{ $trangThaiPheDuyet === 'TAT_CA' ? 'selected' : '' }}>-- Tất cả trạng thái --</option>
                <option value="DA_DUYET" {{ $trangThaiPheDuyet === 'DA_DUYET' ? 'selected' : '' }}>Đã duyệt (Đang hoạt động)</option>
                <option value="BI_THU_HOI" {{ $trangThaiPheDuyet === 'BI_THU_HOI' ? 'selected' : '' }}>Bị thu hồi</option>
            </select>
        </div>
    </form>

    <!-- BẢNG DANH SÁCH TỔ CHỨC -->
    <div class="bg-white rounded-2xl border border-emerald-900/10 bong-xanh overflow-hidden">
        <div class="p-4 bg-nenChinh border-b border-emerald-900/10 flex items-center justify-between text-xs">
            <span class="font-bold text-slate-700 uppercase tracking-wide flex items-center gap-2">
                <i class="fa-solid fa-shield-halved text-indigo-700"></i> Giám Sát Quyền Hạn & Tài Khoản Tổ Chức
            </span>
            <span class="font-bold text-slate-500 font-mono">Hiển thị: {{ $toChucs->total() }} tổ chức</span>
        </div>

        <div class="w-full overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 uppercase tracking-wider border-b border-slate-100 text-[11px]">
                        <th class="py-3.5 px-3 font-bold whitespace-nowrap">Tên Tổ Chức</th>
                        <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Loại Hình</th>
                        <th class="py-3.5 px-3 font-bold whitespace-nowrap">Mã Số Thuế</th>
                        <th class="py-3.5 px-3 font-bold whitespace-nowrap">Ví On-Chain Active</th>
                        <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Trạng Thái</th>
                        <th class="py-3.5 px-3 font-bold text-center whitespace-nowrap">Hành Động</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse ($toChucs as $toChuc)
                    @php
                    $viIdentity = $toChuc->blockchainIdentity;
                    $taiKhoan = $toChuc->taiKhoans->first();
                    $yeuCau = $toChuc->yeuCauDangKys->first();
                    $taiLieus = $yeuCau ? $yeuCau->taiLieus : collect();
                    @endphp
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3 px-3">
                            <div class="font-bold text-slate-900">{{ $toChuc->tenToChuc }}</div>
                            <div class="text-[11px] text-slate-500">{{ $toChuc->diaChi }}</div>
                        </td>
                        <td class="py-3 px-2.5 text-center">
                            @if(($toChuc->loaiToChuc->value ?? $toChuc->loaiToChuc) === 'NHA_SAN_XUAT')
                            <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold text-[10px]">Nhà Sản Xuất</span>
                            @elseif(($toChuc->loaiToChuc->value ?? $toChuc->loaiToChuc) === 'NHA_PHAN_PHOI')
                            <span class="px-2 py-0.5 rounded-full bg-cyan-100 text-cyan-800 font-bold text-[10px]">Nhà Phân Phối</span>
                            @else
                            <span class="px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-800 font-bold text-[10px]">Nhà Thuốc</span>
                            @endif
                        </td>
                        <td class="py-3 px-3 font-mono text-slate-800">{{ $toChuc->maSoThue }}</td>
                        <td class="py-3 px-3 font-mono text-[11px]">
                            @if($viIdentity && $viIdentity->diaChiVi)
                            <a href="https://sepolia.etherscan.io/address/{{ $viIdentity->diaChiVi }}" target="_blank" class="text-indigo-700 hover:underline flex items-center gap-1 font-bold">
                                <span>{{ Str::limit($viIdentity->diaChiVi, 12, '...') }}</span>
                                <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
                            </a>
                            @else
                            <span class="text-slate-400 italic">Chưa cấp ví</span>
                            @endif
                        </td>
                        <td class="py-3 px-2.5 text-center">
                            @if(($toChuc->trangThaiDuyet->value ?? $toChuc->trangThaiDuyet) === 'DA_DUYET')
                            <span class="px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 font-bold text-[10px] inline-flex items-center gap-1">
                                <i class="fa-solid fa-circle-check"></i> Đang hoạt động
                            </span>
                            @elseif(($toChuc->trangThaiDuyet->value ?? $toChuc->trangThaiDuyet) === 'BI_THU_HOI')
                            <span class="px-2.5 py-1 rounded-full bg-red-100 text-red-800 font-bold text-[10px] inline-flex items-center gap-1">
                                <i class="fa-solid fa-ban"></i> Bị thu hồi
                            </span>
                            @else
                            <span class="px-2.5 py-1 rounded-full bg-amber-100 text-amber-800 font-bold text-[10px] inline-flex items-center gap-1">
                                <i class="fa-solid fa-clock"></i> Chờ duyệt
                            </span>
                            @endif
                        </td>
                        <td class="py-3 px-3 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                {{-- NÚT XEM CHI TIẾT TÀI KHOẢN & TỔ CHỨC --}}
                                <button type="button"
                                    class="btn-xem-chi-tiet-to-chuc px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-800 font-bold rounded-lg border border-indigo-200 transition-all text-[11px] flex items-center gap-1 cursor-pointer"
                                    data-tochuc="{{ json_encode([
                                        'id' => $toChuc->id,
                                        'tenToChuc' => $toChuc->tenToChuc,
                                        'loaiToChuc' => $toChuc->loaiToChuc->value ?? $toChuc->loaiToChuc,
                                        'loaiToChucLabel' => match($toChuc->loaiToChuc->value ?? $toChuc->loaiToChuc) {
                                            'NHA_SAN_XUAT' => 'Nhà Sản Xuất',
                                            'NHA_PHAN_PHOI' => 'Nhà Phân Phối',
                                            'NHA_THUOC' => 'Nhà Thuốc',
                                            default => 'Tổ Chức'
                                        },
                                        'maSoThue' => $toChuc->maSoThue,
                                        'soGiayPhepDuoc' => $toChuc->soGiayPhepDuoc,
                                        'ngayHetHanGiayPhep' => $toChuc->ngayHetHanGiayPhep ? \Carbon\Carbon::parse($toChuc->ngayHetHanGiayPhep)->format('d/m/Y') : '--',
                                        'coQuanCapGiayPhep' => $toChuc->coQuanCapGiayPhep ?? 'Cục Quản lý Dược',
                                        'soDienThoai' => $toChuc->soDienThoai ?? '--',
                                        'diaChi' => $toChuc->diaChi ?? '--',
                                        'trangThaiDuyet' => $toChuc->trangThaiDuyet->value ?? $toChuc->trangThaiDuyet,
                                        'emailTaiKhoan' => $taiKhoan?->email ?? '--',
                                        'tenDangNhap' => $taiKhoan?->tenDangNhap ?? $taiKhoan?->email ?? '--',
                                        'hoTenDaiDien' => $taiKhoan?->hoTen ?? $toChuc->nguoiDaiDien ?? '--',
                                        'trangThaiTaiKhoan' => $taiKhoan?->trangThai->value ?? $taiKhoan?->trangThai ?? 'INACTIVE',
                                        'diaChiVi' => $viIdentity?->diaChiVi ?? '',
                                        'soLo' => $toChuc->loThuocs()->count(),
                                        'soLuuThong' => (int) $toChuc->tonKhoLos()->sum('soLuong'),
                                        'ngayDuyet' => $yeuCau?->ngayXuLy ? \Carbon\Carbon::parse($yeuCau->ngayXuLy)->format('d/m/Y H:i') : '--',
                                        'nguoiDuyet' => 'Cơ quan Quản lý Dược',
                                        'taiLieus' => $taiLieus->map(fn($tl) => [
                                            'id' => $tl->id,
                                            'tenTaiLieu' => $tl->tenTaiLieu ?? 'Giấy phép kinh doanh dược',
                                            'duongDan' => route('ho-so.downloadDocument', $tl->id)
                                        ])->values()
                                    ]) }}">
                                    <i class="fa-solid fa-eye text-indigo-700"></i> Chi tiết
                                </button>

                                {{-- NÚT THU HỒI --}}
                                @if(($toChuc->trangThaiDuyet->value ?? $toChuc->trangThaiDuyet) === 'DA_DUYET')
                                <form method="POST" action="{{ route('co-quan.thu-hoi-quyen', $toChuc->id) }}" onsubmit="return confirm('Thu hồi quyền On-chain của tổ chức [{{ $toChuc->tenToChuc }}]?')">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 font-bold rounded-lg border border-red-200 transition-all text-[11px] cursor-pointer" title="Thu hồi quyền">
                                        <i class="fa-solid fa-user-slash"></i> Thu hồi
                                    </button>
                                </form>

                                {{-- NÚT ĐỔI VÍ --}}
                                <form method="POST" action="{{ route('co-quan.doi-vi-moi', $toChuc->id) }}" onsubmit="return confirm('Cấp lại ví mới cho tổ chức [{{ $toChuc->tenToChuc }}]? Ví cũ sẽ bị tước quyền.')">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold rounded-lg border border-indigo-200 transition-all text-[11px] cursor-pointer" title="Đổi ví mới">
                                        <i class="fa-solid fa-arrows-rotate"></i> Đổi ví
                                    </button>
                                </form>
                                @endif

                                {{-- NÚT KHÔI PHỤC --}}
                                @if(($toChuc->trangThaiDuyet->value ?? $toChuc->trangThaiDuyet) === 'BI_THU_HOI')
                                <form method="POST" action="{{ route('co-quan.khoi-phuc-quyen', $toChuc->id) }}" onsubmit="return confirm('Khôi phục quyền On-chain cho tổ chức [{{ $toChuc->tenToChuc }}]?')">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold rounded-lg border border-emerald-200 transition-all text-[11px] cursor-pointer" title="Khôi phục quyền">
                                        <i class="fa-solid fa-user-check"></i> Khôi phục
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-400">
                            <i class="fa-solid fa-folder-open text-3xl mb-2 block"></i>
                            Không có dữ liệu tổ chức phù hợp.
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

<!-- MODAL CHI TIẾT TÀI KHOẢN & TỔ CHỨC -->
<div id="modalChiTietToChuc" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-3 sm:p-5 hidden">
    <div class="bg-white rounded-2xl max-w-4xl w-full p-5 sm:p-7 space-y-4 bong-xanh max-h-[92vh] flex flex-col">

        <!-- HEADER MODAL -->
        <div class="border-b border-slate-100 pb-3 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2.5 flex-wrap">
                <span class="text-base font-bold text-slate-900">Chi Tiết Hồ Sơ & Tài Khoản:</span>
                <span id="detailTenToChucTitle" class="font-bold text-indigo-900 bg-indigo-50 px-2.5 py-0.5 rounded text-xs border border-indigo-200"></span>
                <span id="detailTrangThaiBadge"></span>
            </div>
            <button type="button" class="dong-modal-detail text-slate-400 hover:text-slate-700 text-lg cursor-pointer">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- BANNER BỊ THU HỒI -->
        <div id="detailBannerDaThuHoi" class="hidden p-3.5 bg-red-50 border border-red-200 rounded-xl text-xs space-y-1 shrink-0">
            <div class="flex items-center justify-between">
                <span class="font-bold text-red-700 flex items-center gap-1.5">
                    <i class="fa-solid fa-ban text-sm"></i> TỔ CHỨC VÀ TÀI KHOẢN ĐÃ BỊ TẠM ĐÌNH CHỈ
                </span>
                <span class="badge-chuan px-2 py-0.5 rounded text-[10px] font-extrabold bg-red-200 text-red-800">Bị thu hồi</span>
            </div>
            <p class="text-slate-600 leading-relaxed text-[11px]">Tài khoản đại diện đã bị khóa đăng nhập và địa chỉ ví On-chain đã bị tước quyền thực thi các giao dịch chuỗi cung ứng.</p>
        </div>

        <!-- NỘI DUNG NHAU -->
        <div class="flex-1 overflow-y-auto space-y-3.5 pr-1 text-xs">

            <!-- THỐNG KÊ HOẠT ĐỘNG -->
            <div class="p-3 bg-indigo-50/60 border border-indigo-200 rounded-xl space-y-2">
                <span class="font-bold text-indigo-950 uppercase tracking-wide flex items-center gap-1.5 text-[11px]">
                    <i class="fa-solid fa-chart-pie text-indigo-700"></i> Quy Mô Hoạt Động Doanh Nghiệp
                </span>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="p-2.5 bg-white rounded-lg border border-slate-200">
                        <span class="text-slate-500 block text-[11px]">Tổng số lô thuốc đã phát hành:</span>
                        <span id="detailGiaTriSoLo" class="font-mono font-bold text-slate-900 text-sm">0 lô</span>
                    </div>
                    <div class="p-2.5 bg-white rounded-lg border border-slate-200">
                        <span class="text-slate-500 block text-[11px]">Số lượng sản phẩm tồn kho / lưu thông:</span>
                        <span id="detailSoDonViLuuThong" class="font-mono font-bold text-indigo-700 text-sm">0 hộp</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-3.5 items-start">

                <!-- CỘT BÊN TRÁI: TÀI KHOẢN + PHÁP LÝ -->
                <div class="lg:col-span-7 space-y-3.5">

                    <!-- PHẦN 1: TÀI KHOẢN ĐĂNG NHẬP DẠNG CỤC BỘ -->
                    <div class="bg-nenChinh p-3.5 rounded-xl border border-emerald-900/10 space-y-2">
                        <span class="font-bold text-slate-800 uppercase tracking-wide flex items-center justify-between border-b border-slate-200 pb-1.5 text-[11px]">
                            <span class="flex items-center gap-1.5"><i class="fa-solid fa-user-gear text-indigo-700"></i> Tài Khoản Đăng Nhập Đại Diện</span>
                            <span id="detailBadgeTaiKhoan"></span>
                        </span>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div>
                                <span class="text-slate-500 block">Tên đăng nhập:</span>
                                <span id="detailTenDangNhap" class="font-mono font-bold text-slate-900"></span>
                            </div>
                            <div>
                                <span class="text-slate-500 block">Email hệ thống:</span>
                                <span id="detailEmailTaiKhoan" class="font-mono font-bold text-indigo-900"></span>
                            </div>
                            <div class="col-span-2">
                                <span class="text-slate-500 block">Người đại diện pháp luật:</span>
                                <span id="detailHoTenDaiDien" class="font-bold text-slate-800"></span>
                            </div>
                        </div>
                    </div>

                    <!-- PHẦN 2: PHÁP LÝ DOANH NGHIỆP -->
                    <div class="bg-nenChinh p-3.5 rounded-xl border border-emerald-900/10 space-y-2">
                        <span class="font-bold text-slate-800 uppercase tracking-wide flex items-center gap-2 border-b border-slate-200 pb-1.5 text-[11px]">
                            <i class="fa-solid fa-id-card text-indigo-700"></i> Thông Tin Pháp Lý Doanh Nghiệp
                        </span>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div class="col-span-2">
                                <span class="text-slate-500 block">Tên doanh nghiệp:</span>
                                <span id="detailTenToChuc" class="font-bold text-slate-900"></span>
                            </div>
                            <div>
                                <span class="text-slate-500 block">Loại hình:</span>
                                <div id="detailLoaiHinh" class="pt-0.5 font-semibold text-slate-800"></div>
                            </div>
                            <div>
                                <span class="text-slate-500 block">Mã số thuế:</span>
                                <span id="detailMaSoThue" class="font-mono font-bold text-slate-900"></span>
                            </div>
                            <div>
                                <span class="text-slate-500 block">Số giấy phép dược:</span>
                                <span id="detailSoGiayPhep" class="font-mono font-bold text-indigo-900"></span>
                            </div>
                            <div>
                                <span class="text-slate-500 block">Hạn giấy phép:</span>
                                <span id="detailNgayHetHan" class="font-mono font-bold text-slate-800"></span>
                            </div>
                            <div>
                                <span class="text-slate-500 block">Cơ quan cấp:</span>
                                <span id="detailCoQuanCap" class="font-semibold text-slate-800"></span>
                            </div>
                            <div>
                                <span class="text-slate-500 block">Số điện thoại liên hệ:</span>
                                <span id="detailSoDienThoai" class="font-mono text-slate-800"></span>
                            </div>
                            <div class="col-span-2">
                                <span class="text-slate-500 block">Địa chỉ trụ sở:</span>
                                <span id="detailDiaChi" class="text-slate-800 leading-tight block"></span>
                            </div>
                        </div>

                        <!-- VÍ ON-CHAIN -->
                        <div class="pt-2 border-t border-slate-200 space-y-1">
                            <span class="text-slate-500 block text-[11px]">Địa chỉ ví định danh On-Chain (Active):</span>
                            <div class="flex items-center gap-2 p-1.5 bg-white rounded-lg border border-slate-200">
                                <a id="detailLinkViExplorer" href="#" target="_blank" class="font-mono text-[11px] text-cyan-800 hover:underline flex-1 truncate flex items-center gap-1">
                                    <span id="detailDiaChiViOnChain">0x...</span>
                                    <i class="fa-solid fa-arrow-up-right-from-square text-[9px] text-slate-400 shrink-0"></i>
                                </a>
                                <button type="button" id="nutCopyDiaChiViDetail" class="px-2 py-0.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-[10px] font-bold shrink-0 cursor-pointer">
                                    <i class="fa-regular fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CỘT BÊN PHẢI: LỊCH SỬ DUYỆT & MINH CHỨNG -->
                <div class="lg:col-span-5 space-y-3.5">
                    <div class="bg-nenChinh p-3 rounded-xl border border-emerald-900/10 space-y-1.5">
                        <span class="font-bold text-slate-800 uppercase tracking-wide flex items-center gap-1.5 text-[11px] border-b border-slate-200 pb-1">
                            <i class="fa-solid fa-clock-rotate-left text-slate-500"></i> Lịch Sử Xét Duyệt Hệ Thống
                        </span>
                        <div class="space-y-1 text-slate-700 text-[11px]">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Thời điểm phê duyệt:</span>
                                <strong id="detailNgayDuyetToChuc" class="font-mono text-slate-800"></strong>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Cơ quan xử lý:</span>
                                <strong id="detailNguoiDuyetToChuc" class="font-mono text-slate-800"></strong>
                            </div>
                        </div>
                    </div>

                    <div class="bg-indigo-50/60 p-3 rounded-xl border border-indigo-200 space-y-1.5">
                        <span class="font-bold text-indigo-950 uppercase tracking-wide flex items-center gap-1.5 text-[11px] border-b border-indigo-200 pb-1">
                            <i class="fa-solid fa-file-shield text-indigo-700"></i> Hồ Sơ Pháp Lý Đính Kèm
                        </span>
                        <div id="detailDanhSachMinhChung" class="space-y-1.5 max-h-48 overflow-y-auto pr-1"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="border-t border-slate-100 pt-3 flex items-center justify-between shrink-0">
            <button type="button" class="dong-modal-detail px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition-all cursor-pointer">
                Đóng
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/coQuanQuanLy/quanLyQuyenHan.js') }}"></script>
@endpush
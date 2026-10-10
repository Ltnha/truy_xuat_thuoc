@extends('layouts.app')
@section('title', 'Hồ Sơ & Tài Khoản Cá Nhân – PharmaChain')
@section('panelTitle', 'Hồ Sơ & Tài Khoản Cá Nhân')
@php
$taiKhoan = auth()->user();
$toChuc = $taiKhoan->toChuc;
$vaiTroHienTai = $taiKhoan->vaiTro?->value ?? 'NHA_SAN_XUAT';
$taiLieuDangKy = $toChuc?->yeuCauDangKys()->with('taiLieus')->latest('id')->first()?->taiLieus ?? collect();

// Cấu hình màu nhấn và vai trò
$cauHinhVaiTro = [
    'NHA_SAN_XUAT' => [
        'ten' => 'Nhà Sản Xuất',
        'icon' => 'fa-industry',
        'tenToChucMacDinh' => 'Công ty CP Dược Phẩm Medipharco',
        'textClass' => 'text-emerald-700',
        'bgNhanClass' => 'bg-emerald-50',
        'btnClass' => 'bg-emerald-700 hover:bg-emerald-800 text-white',
        'badgeClass' => 'bg-emerald-100 text-emerald-800 border-emerald-300'
    ],
    'NHA_PHAN_PHOI' => [
        'ten' => 'Nhà Phân Phối',
        'icon' => 'fa-truck-moving',
        'tenToChucMacDinh' => 'Tổng Công ty Dược Việt Nam - Vinapharm',
        'textClass' => 'text-blue-700',
        'bgNhanClass' => 'bg-blue-50',
        'btnClass' => 'bg-blue-700 hover:bg-blue-800 text-white',
        'badgeClass' => 'bg-blue-100 text-blue-800 border-blue-300'
    ],
    'NHA_THUOC' => [
        'ten' => 'Nhà Thuốc',
        'icon' => 'fa-prescription-bottle-medical',
        'tenToChucMacDinh' => 'Nhà Thuốc PharmaCare Cần Thơ',
        'textClass' => 'text-cyan-700',
        'bgNhanClass' => 'bg-cyan-50',
        'btnClass' => 'bg-cyan-700 hover:bg-cyan-800 text-white',
        'badgeClass' => 'bg-cyan-100 text-cyan-800 border-cyan-300'
    ],
    'QUAN_TRI_VIEN' => [
        'ten' => 'Quản Trị Viên',
        'icon' => 'fa-user-shield',
        'tenToChucMacDinh' => 'Trung Tâm Quản Trị Hệ Thống',
        'textClass' => 'text-slate-700',
        'bgNhanClass' => 'bg-slate-100',
        'btnClass' => 'bg-slate-800 hover:bg-slate-900 text-white',
        'badgeClass' => 'bg-slate-200 text-slate-800 border-slate-400'
    ],
    'CO_QUAN_QUAN_LY' => [
        'ten' => 'Cơ Quan Quản Lý',
        'icon' => 'fa-building-columns',
        'tenToChucMacDinh' => 'Cục Quản Lý Dược - Bộ Y Tế',
        'textClass' => 'text-indigo-700',
        'bgNhanClass' => 'bg-indigo-50',
        'btnClass' => 'bg-indigo-700 hover:bg-indigo-800 text-white',
        'badgeClass' => 'bg-indigo-100 text-indigo-800 border-indigo-300'
    ]
];

$role = isset($cauHinhVaiTro[$vaiTroHienTai]) ? $cauHinhVaiTro[$vaiTroHienTai] : $cauHinhVaiTro['NHA_SAN_XUAT'];

// Tiêu đề phân hệ gọn gàng (Đã bỏ hậu tố vai trò thừa)
$tieuDePhanHe = "Hồ Sơ & Tài Khoản Cá Nhân";

// Cờ điều khiển hiển thị theo biến thể vai trò
$coThePhapLy = in_array($vaiTroHienTai, ['NHA_SAN_XUAT', 'NHA_PHAN_PHOI', 'NHA_THUOC']);
$coDiaChiVi = in_array($vaiTroHienTai, ['NHA_SAN_XUAT', 'NHA_PHAN_PHOI', 'NHA_THUOC', 'CO_QUAN_QUAN_LY']);
@endphp

@section('content')
<!-- ══════════════════════════════════════════════════════════ -->
            <!-- PHẦN 1: THÔNG TIN LIÊN HỆ & TÀI KHOẢN (TẤT CẢ VAI TRÒ)   -->
            <!-- ══════════════════════════════════════════════════════════ -->
            <section class="bg-white rounded-2xl border border-emerald-900/10 p-6 sm:p-8 bong-xanh space-y-6">

                <!-- TIÊU ĐỀ ĐÃ CĂN CHỈNH GỌN GÀNG -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
                    <div class="flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-xl {{ $role['bgNhanClass'] }} {{ $role['textClass'] }} flex items-center justify-center text-xl shrink-0 shadow-2xs border border-slate-200/60">
                            <i class="fa-solid fa-address-card"></i>
                        </div>
                        <h3 class="text-base sm:text-lg font-bold text-slate-900 tracking-tight">
                            Thông Tin Liên Hệ & Tài Khoản
                        </h3>
                    </div>

                    <!-- Nút Mở modal Đổi Mật Khẩu -->
                    <button type="button" id="nutMoModalDoiPass" class="px-5 py-2.5 rounded-xl text-xs font-bold transition-all shadow-md flex items-center justify-center gap-2 shrink-0 {{ $role['btnClass'] }}">
                        <i class="fa-solid fa-key"></i> Đổi Mật Khẩu
                    </button>
                </div>

                <!-- Lưới thông tin liên hệ và hai tầng trạng thái -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                    <div class="p-4 rounded-xl bg-nenChinh border border-emerald-900/10 space-y-1">
                        <span class="text-slate-500 font-medium block">Tên đăng nhập:</span>
                        <span class="font-bold text-sm text-slate-900 font-mono">{{ auth()->user()->tenDangNhap }}</span>
                    </div>

                    <div class="sm:col-span-2 rounded-xl border border-emerald-900/10 bg-nenChinh p-4">
                        <form action="{{ route('hoSo.capNhat') }}" method="POST" class="grid gap-3 sm:grid-cols-2">
                            @csrf
                            @method('PATCH')
                            <label class="space-y-1 font-medium text-slate-600">Email nhận thông báo
                                <input type="email" name="email" value="{{ old('email', $taiKhoan->email) }}" required maxlength="100" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 font-normal text-slate-900">
                            </label>
                            <label class="space-y-1 font-medium text-slate-600">Số điện thoại liên hệ
                                <input type="tel" name="soDienThoai" value="{{ old('soDienThoai', $taiKhoan->soDienThoai) }}" maxlength="20" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 font-mono font-normal text-slate-900">
                            </label>
                            <div class="sm:col-span-2 text-right">
                                <button type="submit" class="rounded-lg bg-emerald-700 px-4 py-2 text-xs font-bold text-white">Lưu thông tin liên hệ</button>
                            </div>
                        </form>
                    </div>

                    <!-- TẦNG TRẠNG THÁI 1: Trạng thái tài khoản (taiKhoan.trangThai) -->
                    <div class="p-4 rounded-xl bg-nenChinh border border-emerald-900/10 space-y-1.5">
                        <span class="text-slate-500 font-medium block">Trạng thái tài khoản:</span>
                        <span class="inline-flex items-center gap-1.5 font-bold text-emerald-700 bg-emerald-100/90 px-2.5 py-1 rounded-full text-[11px] border border-emerald-300">
                            <i class="fa-solid fa-circle-check"></i> {{ $taiKhoan->trangThai?->value }}
                        </span>
                        <div class="text-[10px] text-slate-400">Kiểm soát khả năng đăng nhập hệ thống</div>
                    </div>

                    <!-- TẦNG TRẠNG THÁI 2: Trạng thái tổ chức (toChuc.trangThaiPheDuyet) -->
                    @if ($coThePhapLy)
                        <div class="p-4 rounded-xl bg-nenChinh border border-emerald-900/10 space-y-1.5 sm:col-span-2">
                            <span class="text-slate-500 font-medium block">Trạng thái phê duyệt tổ chức:</span>
                            <span class="inline-flex items-center gap-1.5 font-bold text-cyan-800 bg-cyan-100/90 px-2.5 py-1 rounded-full text-[11px] border border-cyan-300">
                                <i class="fa-solid fa-shield-check"></i> {{ $toChuc?->trangThaiDuyet?->value ?? 'Chưa có tổ chức' }}
                            </span>
                            <div class="text-[10px] text-slate-500">
                                Kiểm soát quyền thực hiện nghiệp vụ (tạo lô, chuyển giao, bán lẻ trên chuỗi cung ứng)
                            </div>
                        </div>
                    @else
                        <div class="p-4 rounded-xl bg-nenChinh border border-emerald-900/10 space-y-1.5 sm:col-span-2">
                            <span class="text-slate-500 font-medium block">Đơn vị quản lý:</span>
                            <span class="font-bold text-slate-800 text-xs">Cơ quan / Quản trị nội bộ hệ thống</span>
                        </div>
                    @endif

                    <!-- ĐỊA CHỈ VÍ BLOCKCHAIN (CUSTODIAL WALLET) -->
                    @if ($coDiaChiVi)
                        <div class="sm:col-span-3 p-4 rounded-xl bg-nenChinh border border-emerald-900/10 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-600 font-bold block flex items-center gap-1.5">
                                    <i class="fa-solid fa-wallet text-cyan-800"></i> Địa chỉ ví Blockchain ký giao dịch (Custodial Wallet):
                                </span>
                                <button type="button" id="nutSaoChepVi" class="px-2.5 py-1 bg-white hover:bg-slate-100 border border-slate-300 text-slate-700 rounded-lg text-[11px] font-bold transition-all shadow-2xs flex items-center gap-1">
                                    <i class="fa-regular fa-copy"></i> <span>Sao chép ví</span>
                                </button>
                            </div>
                            <div id="giaTriDiaChiVi" class="font-mono font-bold text-xs text-cyan-800 break-all select-all p-2.5 bg-white rounded-lg border border-slate-200">
                                {{ $toChuc?->blockchainIdentity?->address ?? 'Chưa khởi tạo ví' }}
                            </div>
                            <p class="text-[11px] text-slate-500 italic">
                                * Ghi chú: Khóa riêng do hệ thống lưu trữ bảo mật (Custodial Wallet), người dùng không cần quản lý ví.
                            </p>
                        </div>
                    @endif
                </div>
            </section>

            <!-- ══════════════════════════════════════════════════════════ -->
            <!-- PHẦN 2: THÔNG TIN PHÁP LÝ TỔ CHỨC (CHỈ ĐỌC - READ ONLY)    -->
            <!-- (Chỉ hiển thị cho Nhà sản xuất, Nhà phân phối, Nhà thuốc)  -->
            <!-- ══════════════════════════════════════════════════════════ -->
            @if ($coThePhapLy)
                <section class="bg-white rounded-2xl border border-emerald-900/10 p-6 sm:p-8 bong-xanh space-y-6">

                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
                        <div class="flex items-center gap-3.5">
                            <div class="w-11 h-11 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center text-xl shrink-0 border border-slate-200/80">
                                <i class="fa-solid fa-building-shield"></i>
                            </div>
                            <h3 class="text-base sm:text-lg font-bold text-slate-900 tracking-tight">
                                Thông Tin Định Danh Pháp Lý Tổ Chức
                            </h3>
                        </div>

                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200 self-start sm:self-auto">
                            <i class="fa-solid fa-lock mr-1"></i> Bất biến (Chỉ đọc)
                        </span>
                    </div>

                    <!-- CÂU CẢNH BÁO ĐÃ SỬA CHUẨN XÁC KIẾN TRÚC OFF-CHAIN -->
                    <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-900 flex items-start gap-3 leading-relaxed">
                        <i class="fa-solid fa-circle-info text-amber-600 text-base mt-0.5 shrink-0"></i>
                        <div>
                            Thông tin định danh pháp lý đã được Cơ quan quản lý thẩm duyệt và không thể chỉnh sửa trực tuyến. Nếu cần thay đổi (gia hạn giấy phép, đổi địa chỉ...), vui lòng liên hệ trực tiếp Cơ quan quản lý để xử lý ngoài hệ thống.
                        </div>
                    </div>

                    <!-- CÁC TRƯỜNG CHỈ ĐỌC (DISABLED / READ-ONLY) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <div class="sm:col-span-2 space-y-1">
                            <label class="text-slate-500 font-semibold">Tên tổ chức / doanh nghiệp:</label>
                            <input type="text" value="{{ $toChuc?->tenToChuc ?? 'Chưa cập nhật' }}" readonly disabled class="w-full px-4 py-2.5 bg-slate-100/80 border border-slate-200 rounded-xl font-bold text-slate-800 cursor-not-allowed">
                        </div>

                        <div class="space-y-1">
                            <label class="text-slate-500 font-semibold">Mã số thuế:</label>
                            <input type="text" value="{{ $toChuc?->maSoThue ?? 'Chưa cập nhật' }}" readonly disabled class="w-full px-4 py-2.5 bg-slate-100/80 border border-slate-200 rounded-xl font-mono font-bold text-slate-800 cursor-not-allowed">
                        </div>

                        <div class="space-y-1">
                            <label class="text-slate-500 font-semibold">Số giấy phép kinh doanh dược:</label>
                            <input type="text" value="{{ $toChuc?->soGiayPhepDuoc ?? 'Chưa cập nhật' }}" readonly disabled class="w-full px-4 py-2.5 bg-slate-100/80 border border-slate-200 rounded-xl font-mono font-bold text-slate-800 cursor-not-allowed">
                        </div>

                        <div class="space-y-1">
                            <label class="text-slate-500 font-semibold">Cơ quan cấp phép:</label>
                            <input type="text" value="{{ $toChuc?->coQuanCap ?? 'Chưa cập nhật' }}" readonly disabled class="w-full px-4 py-2.5 bg-slate-100/80 border border-slate-200 rounded-xl font-semibold text-slate-800 cursor-not-allowed">
                        </div>

                        <div class="space-y-1">
                            <label class="text-slate-500 font-semibold">Thời hạn hiệu lực giấy phép:</label>
                            <input type="text" value="{{ $toChuc?->ngayCapGiayPhep ?? 'Chưa cập nhật' }} — {{ $toChuc?->ngayHetHanGiayPhep ?? 'Chưa cập nhật' }}" readonly disabled class="w-full px-4 py-2.5 bg-slate-100/80 border border-slate-200 rounded-xl font-mono font-semibold text-slate-800 cursor-not-allowed">
                        </div>

                        <div class="sm:col-span-2 space-y-1">
                            <label class="text-slate-500 font-semibold">Địa chỉ trụ sở pháp lý:</label>
                            <input type="text" value="{{ $toChuc?->diaChi ?? 'Chưa cập nhật' }}" readonly disabled class="w-full px-4 py-2.5 bg-slate-100/80 border border-slate-200 rounded-xl font-medium text-slate-800 cursor-not-allowed">
                        </div>

                        <!-- LIÊN KẾT XEM HỒ SƠ ĐĂNG KÝ ĐÃ NỘP (hoSoDinhKemDangKy) -->
                        <div class="sm:col-span-2 pt-2 border-t border-slate-100">
                            <span class="text-slate-500 font-medium">Hồ sơ minh chứng đã lưu:</span>
                            <ul class="mt-2 space-y-2">
                                @forelse ($taiLieuDangKy as $taiLieu)
                                    <li>
                                        <a href="{{ route('hoSo.taiLieu', $taiLieu) }}" class="inline-flex items-center gap-1.5 text-cyan-800 hover:text-cyan-950 font-bold hover:underline">
                                            <i class="fa-solid fa-file-arrow-down text-cyan-700 text-sm"></i>{{ $taiLieu->tenTaiLieu }}
                                        </a>
                                    </li>
                                @empty
                                    <li class="text-slate-500">Chưa có tài liệu đính kèm.</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>

                </section>
            @endif

            <div id="modalDoiMatKhau" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
                <section class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-lg font-bold">Đổi mật khẩu</h2>
                        <button type="button" id="nutDongModalDoiPass" class="rounded-lg px-3 py-2 text-slate-500" aria-label="Đóng">×</button>
                    </div>
                    <form id="formDoiMatKhau" action="{{ route('hoSo.matKhau') }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PATCH')
                        <label class="block space-y-1 text-xs font-semibold">Mật khẩu hiện tại
                            <input id="matKhauHienTai" name="matKhauHienTai" type="password" required autocomplete="current-password" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </label>
                        <label class="block space-y-1 text-xs font-semibold">Mật khẩu mới
                            <input id="matKhauMoi" name="matKhauMoi" type="password" required minlength="8" autocomplete="new-password" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </label>
                        <label class="block space-y-1 text-xs font-semibold">Xác nhận mật khẩu mới
                            <input id="xacNhanMatKhauMoi" name="matKhauMoi_confirmation" type="password" required minlength="8" autocomplete="new-password" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </label>
                        <div class="flex justify-end gap-2">
                            <button type="button" id="nutHuyDoiPass" class="rounded-lg border border-slate-300 px-4 py-2 text-xs font-bold">Hủy</button>
                            <button type="submit" class="rounded-lg bg-emerald-700 px-4 py-2 text-xs font-bold text-white">Cập nhật mật khẩu</button>
                        </div>
                    </form>
                </section>
            </div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/xacThuc/thongTinCaNhan.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/js/xacThuc/thongTinCaNhan.js') }}" defer></script>
@endpush

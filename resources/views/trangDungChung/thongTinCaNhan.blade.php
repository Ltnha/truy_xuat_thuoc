@extends('layoutDungChung.master')

@section('title', 'Hồ Sơ & Tài Khoản Cá Nhân – PharmaChain')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/trangDungChung/thongTinCaNhan.css') }}">
@endpush

@section('content')
<div class="space-y-6 max-w-5xl w-full mx-auto">

    <!-- THÔNG BÁO TỪ SYSTEM -->
    @if (session('thanhCong'))
    <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-xs font-bold text-emerald-800 flex items-center gap-2 shadow-sm">
        <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
        <span>{{ session('thanhCong') }}</span>
    </div>
    @endif

    @if (session('loi'))
    <div class="p-4 bg-red-50 border border-red-200 rounded-2xl text-xs font-bold text-red-800 flex items-center gap-2 shadow-sm">
        <i class="fa-solid fa-triangle-exclamation text-red-600 text-sm"></i>
        <span>{{ session('loi') }}</span>
    </div>
    @endif

    @if ($errors->any())
    <div class="p-4 bg-red-50 border border-red-200 rounded-2xl text-xs font-bold text-red-800 space-y-1 shadow-sm">
        <div class="flex items-center gap-2 text-red-900">
            <i class="fa-solid fa-circle-xmark text-red-600"></i>
            <span>Đã xảy ra lỗi vui lòng kiểm tra lại:</span>
        </div>
        <ul class="list-disc list-inside font-medium pl-4 text-red-700">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- PHẦN 1: THÔNG TIN LIÊN HỆ & TÀI KHOẢN (TẤT CẢ VAI TRÒ)   -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <section class="bg-white rounded-2xl border border-emerald-900/10 p-6 sm:p-8 bong-xanh space-y-6">

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl {{ $roleConfig['bgNhanClass'] ?? 'bg-emerald-100' }} {{ $roleConfig['textClass'] ?? 'text-emerald-800' }} flex items-center justify-center text-xl shrink-0 shadow-2xs border border-slate-200/60">
                    <i class="fa-solid fa-address-card"></i>
                </div>
                <div>
                    <h3 class="text-base sm:text-lg font-bold text-slate-900 tracking-tight">
                        Thông Tin Liên Hệ & Tài Khoản
                    </h3>
                    <p class="text-slate-500 text-xs font-medium">{{ $roleConfig['ten'] ?? '' }}</p>
                </div>
            </div>

            <!-- Nút Mở modal Đổi Mật Khẩu -->
            <button type="button" id="nutMoModalDoiPass" class="px-5 py-2.5 rounded-xl text-xs font-bold transition-all shadow-md flex items-center justify-center gap-2 shrink-0 {{ $roleConfig['btnClass'] ?? 'bg-emerald-700 hover:bg-emerald-800 text-white' }} cursor-pointer">
                <i class="fa-solid fa-key"></i> Đổi Mật Khẩu
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
            <div class="p-4 rounded-xl bg-nenChinh border border-emerald-900/10 space-y-1">
                <span class="text-slate-500 font-medium block">Tên đăng nhập:</span>
                <span class="font-bold text-sm text-slate-900 font-mono">{{ $user->tenDangNhap }}</span>
            </div>

            <div class="p-4 rounded-xl bg-nenChinh border border-emerald-900/10 space-y-1">
                <span class="text-slate-500 font-medium block">Email nhận thông báo:</span>
                <span class="font-bold text-sm text-slate-900">{{ $user->email }}</span>
            </div>

            <div class="p-4 rounded-xl bg-nenChinh border border-emerald-900/10 space-y-1">
                <span class="text-slate-500 font-medium block">Số điện thoại liên hệ:</span>
                <span class="font-bold text-sm text-slate-900 font-mono">
                    {{ $user->toChuc?->soDienThoai ?? '— (Chưa cập nhật)' }}
                </span>
            </div>

            <div class="p-4 rounded-xl bg-nenChinh border border-emerald-900/10 space-y-1.5">
                <span class="text-slate-500 font-medium block">Trạng thái tài khoản:</span>
                @php
                $stVal = is_object($user->trangThai) ? $user->trangThai->value : $user->trangThai;
                @endphp
                @if($stVal === 'ACTIVE')
                <span class="inline-flex items-center gap-1.5 font-bold text-emerald-700 bg-emerald-100/90 px-2.5 py-1 rounded-full text-[11px] border border-emerald-300">
                    <i class="fa-solid fa-circle-check"></i> HOẠT ĐỘNG (ACTIVE)
                </span>
                @else
                <span class="inline-flex items-center gap-1.5 font-bold text-amber-800 bg-amber-100 px-2.5 py-1 rounded-full text-[11px] border border-amber-300">
                    <i class="fa-solid fa-clock"></i> {{ $stVal }}
                </span>
                @endif
                <div class="text-[10px] text-slate-400">Quyền đăng nhập & thao tác ứng dụng</div>
            </div>

            @if(!empty($roleConfig['coThePhapLy']) && $user->toChuc)
            @php
            $stToChuc = is_object($user->toChuc->trangThaiDuyet) ? $user->toChuc->trangThaiDuyet->value : $user->toChuc->trangThaiDuyet;
            @endphp
            <div class="p-4 rounded-xl bg-nenChinh border border-emerald-900/10 space-y-1.5 sm:col-span-2">
                <span class="text-slate-500 font-medium block">Trạng thái phê duyệt tổ chức:</span>
                @if($stToChuc === 'DA_DUYET')
                <span class="inline-flex items-center gap-1.5 font-bold text-cyan-800 bg-cyan-100/90 px-2.5 py-1 rounded-full text-[11px] border border-cyan-300">
                    <i class="fa-solid fa-shield-check"></i> ĐÃ DUYỆT PHÁP LÝ (DA_DUYET)
                </span>
                @else
                <span class="inline-flex items-center gap-1.5 font-bold text-amber-800 bg-amber-100 px-2.5 py-1 rounded-full text-[11px] border border-amber-300">
                    <i class="fa-solid fa-hourglass-half"></i> {{ $stToChuc }}
                </span>
                @endif
                <div class="text-[10px] text-slate-500">
                    Kiểm soát quyền thực hiện nghiệp vụ chuỗi cung ứng
                </div>
            </div>
            @else
            <div class="p-4 rounded-xl bg-nenChinh border border-emerald-900/10 space-y-1.5 sm:col-span-2">
                <span class="text-slate-500 font-medium block">Đơn vị quản lý:</span>
                <span class="font-bold text-slate-800 text-xs">Cơ quan nhà nước / Quản trị hệ thống</span>
            </div>
            @endif

            @if(!empty($roleConfig['coDiaChiVi']))
            <div class="sm:col-span-3 p-4 rounded-xl bg-nenChinh border border-emerald-900/10 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-slate-600 font-bold block flex items-center gap-1.5">
                        <i class="fa-solid fa-wallet text-cyan-800"></i> Địa chỉ ví Blockchain ký giao dịch (Custodial Wallet):
                    </span>
                    <button type="button" id="nutSaoChepVi" class="px-2.5 py-1 bg-white hover:bg-slate-100 border border-slate-300 text-slate-700 rounded-lg text-[11px] font-bold transition-all shadow-2xs flex items-center gap-1 cursor-pointer">
                        <i class="fa-regular fa-copy"></i> <span>Sao chép ví</span>
                    </button>
                </div>
                <div id="giaTriDiaChiVi" class="font-mono font-bold text-xs text-cyan-800 break-all select-all p-2.5 bg-white rounded-lg border border-slate-200">
                    {{ $user->toChuc?->diaChiVi ?? '0x' . md5($user->id . $user->tenDangNhap) }}
                </div>
                <p class="text-[11px] text-slate-500 italic">
                    * Ghi chú: Khóa riêng (Private key) do hệ thống lưu trữ bảo mật (Custodial Wallet), người dùng không cần quản lý ví.
                </p>
            </div>
            @endif
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- PHẦN 2: THÔNG TIN PHÁP LÝ TỔ CHỨC (CHỈ ĐỌC - READ ONLY)    -->
    <!-- ══════════════════════════════════════════════════════════ -->
    @if(!empty($roleConfig['coThePhapLy']) && $user->toChuc)
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

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <div class="sm:col-span-2 space-y-1">
                <label class="text-slate-500 font-semibold">Tên tổ chức / doanh nghiệp:</label>
                <input type="text" value="{{ $user->toChuc->tenToChuc }}" readonly disabled class="w-full px-4 py-2.5 bg-slate-100/80 border border-slate-200 rounded-xl font-bold text-slate-800 cursor-not-allowed">
            </div>

            <div class="space-y-1">
                <label class="text-slate-500 font-semibold">Mã số thuế:</label>
                <input type="text" value="{{ $user->toChuc->maSoThue }}" readonly disabled class="w-full px-4 py-2.5 bg-slate-100/80 border border-slate-200 rounded-xl font-mono font-bold text-slate-800 cursor-not-allowed">
            </div>

            <div class="space-y-1">
                <label class="text-slate-500 font-semibold">Số giấy phép kinh doanh dược:</label>
                <input type="text" value="{{ $user->toChuc->soGiayPhepDuoc ?? $user->toChuc->soGiayPhepDDK ?? '—' }}" readonly disabled class="w-full px-4 py-2.5 bg-slate-100/80 border border-slate-200 rounded-xl font-mono font-bold text-slate-800 cursor-not-allowed">
            </div>

            <div class="space-y-1">
                <label class="text-slate-500 font-semibold">Cơ quan cấp phép:</label>
                <input type="text" value="{{ $user->toChuc->coQuanCap ?? $user->toChuc->coQuanCapPhep ?? 'Sở Y Tế / Cục Quản Lý Dược' }}" readonly disabled class="w-full px-4 py-2.5 bg-slate-100/80 border border-slate-200 rounded-xl font-semibold text-slate-800 cursor-not-allowed">
            </div>

            <div class="space-y-1">
                <label class="text-slate-500 font-semibold">Địa chỉ trụ sở pháp lý:</label>
                <input type="text" value="{{ $user->toChuc->diaChi }}" readonly disabled class="w-full px-4 py-2.5 bg-slate-100/80 border border-slate-200 rounded-xl font-medium text-slate-800 cursor-not-allowed">
            </div>

            @if($user->toChuc->taiLieuDangKys && $user->toChuc->taiLieuDangKys->isNotEmpty())
            <div class="sm:col-span-2 pt-3 border-t border-slate-100 space-y-2.5">
                <span class="text-slate-600 font-bold block">Hồ sơ minh chứng đã lưu:</span>
                <div class="flex flex-wrap gap-2.5">
                    @foreach($user->toChuc->taiLieuDangKys as $tl)
                    @php
                    $tenHienThi = $tl->tenTaiLieu ?: match($tl->loaiTaiLieu) {
                    'HO_SO_DOANH_NGHIEP' => 'Hồ sơ pháp lý doanh nghiệp',
                    'GIAY_PHEP_KINH_DOANH' => 'Giấy phép ĐKKD / Dược',
                    'GIAY_CHUNG_NHAN_GPP_GMP' => 'Giấy chứng nhận GPP / GMP',
                    default => 'Hồ sơ minh chứng (' . ($tl->loaiTaiLieu ?? 'PDF') . ')'
                    };
                    @endphp
                    <a href="{{ route('ho-so.downloadDocument', $tl->id) }}" target="_blank" class="inline-flex items-center gap-2 px-3.5 py-2 bg-slate-50 hover:bg-emerald-50 border border-slate-200 hover:border-emerald-300 rounded-xl text-slate-700 hover:text-emerald-800 font-semibold text-xs transition-all shadow-2xs">
                        <i class="fa-solid fa-file-pdf text-red-500 text-sm"></i>
                        <span class="truncate max-w-[280px]" title="{{ $tenHienThi }}">{{ $tenHienThi }}</span>
                        <i class="fa-solid fa-arrow-down-to-line text-[10px] text-slate-400 ml-1"></i>
                    </a>
                    @endforeach
                </div>
            </div>
            @else
            <div class="sm:col-span-2 pt-3 border-t border-slate-100">
                <span class="text-slate-500 font-medium italic">Chưa có tài liệu minh chứng nào được lưu.</span>
            </div>
            @endif
        </div>

    </section>
    @endif

</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!-- MODAL CHUYÊN BIỆT: CHỈ ĐỔI MẬT KHẨU                      -->
<!-- ══════════════════════════════════════════════════════════ -->
<div id="modalDoiMatKhau" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 sm:p-8 space-y-5 bong-xanh">

        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-key {{ $roleConfig['textClass'] ?? 'text-emerald-800' }}"></i> Đổi Mật Khẩu Đăng Nhập
            </h3>
            <button type="button" id="nutDongModalDoiPass" class="text-slate-400 hover:text-slate-700 text-lg cursor-pointer">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="formDoiMatKhau" method="POST" action="{{ route('ho-so.updatePassword') }}" class="space-y-4 text-xs">
            @csrf
            @method('PUT')

            <div class="space-y-1">
                <label for="matKhauHienTai" class="font-bold text-slate-700">Mật khẩu hiện tại <span class="text-red-500">*</span></label>
                <input type="password" id="matKhauHienTai" name="matKhauHienTai" required placeholder="Nhập mật khẩu đang dùng..." class="w-full px-3.5 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-slate-900 focus:bg-white focus:border-emerald-700 outline-none">
            </div>

            <div class="space-y-1">
                <label for="matKhauMoi" class="font-bold text-slate-700">Mật khẩu mới <span class="text-red-500">*</span></label>
                <input type="password" id="matKhauMoi" name="matKhauMoi" required minlength="8" placeholder="Tối thiểu 8 ký tự..." class="w-full px-3.5 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-slate-900 focus:bg-white focus:border-emerald-700 outline-none">
            </div>

            <div class="space-y-1">
                <label for="xacNhanMatKhauMoi" class="font-bold text-slate-700">Xác nhận mật khẩu mới <span class="text-red-500">*</span></label>
                <input type="password" id="xacNhanMatKhauMoi" name="xacNhanMatKhauMoi" required minlength="8" placeholder="Nhập lại mật khẩu mới..." class="w-full px-3.5 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-slate-900 focus:bg-white focus:border-emerald-700 outline-none">
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                <button type="button" id="nutHuyDoiPass" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-all cursor-pointer">
                    Hủy
                </button>
                <button type="submit" class="px-6 py-2.5 {{ $roleConfig['btnClass'] ?? 'bg-emerald-700 hover:bg-emerald-800 text-white' }} font-bold rounded-xl shadow-md transition-all flex items-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-lock"></i> Cập Nhật Mật Khẩu
                </button>
            </div>
        </form>

    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/trangDungChung/thongTinCaNhan.js') }}"></script>
@endpush
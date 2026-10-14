@extends('layoutDungChung.master')

@section('title', 'Hồ Sơ & Tài Khoản Cá Nhân – PharmaChain')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/trangDungChung/thongTinCaNhan.css') }}">
@endpush

@section('content')
<div class="space-y-6 max-w-5xl w-full mx-auto">

    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- PHẦN 1: THÔNG TIN LIÊN HỆ & TÀI KHOẢN (TẤT CẢ VAI TRÒ)   -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <section class="bg-white rounded-2xl border border-emerald-900/10 p-6 sm:p-8 bong-xanh space-y-6">

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl {{ $role['bgNhanClass'] ?? 'bg-emerald-100' }} {{ $role['textClass'] ?? 'text-emerald-800' }} flex items-center justify-center text-xl shrink-0 shadow-2xs border border-slate-200/60">
                    <i class="fa-solid fa-address-card"></i>
                </div>
                <h3 class="text-base sm:text-lg font-bold text-slate-900 tracking-tight">
                    Thông Tin Liên Hệ & Tài Khoản
                </h3>
            </div>

            <!-- Nút Mở modal Đổi Mật Khẩu -->
            <button type="button" id="nutMoModalDoiPass" class="px-5 py-2.5 rounded-xl text-xs font-bold transition-all shadow-md flex items-center justify-center gap-2 shrink-0 {{ $role['btnClass'] ?? 'bg-emerald-700 hover:bg-emerald-800 text-white' }}">
                <i class="fa-solid fa-key"></i> Đổi Mật Khẩu
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
            <div class="p-4 rounded-xl bg-nenChinh border border-emerald-900/10 space-y-1">
                <span class="text-slate-500 font-medium block">Tên đăng nhập:</span>
                <span class="font-bold text-sm text-slate-900 font-mono">admin_user</span>
            </div>

            <div class="p-4 rounded-xl bg-nenChinh border border-emerald-900/10 space-y-1">
                <span class="text-slate-500 font-medium block">Email nhận thông báo:</span>
                <span class="font-bold text-sm text-slate-900">contact@organization.vn</span>
            </div>

            <div class="p-4 rounded-xl bg-nenChinh border border-emerald-900/10 space-y-1">
                <span class="text-slate-500 font-medium block">Số điện thoại liên hệ:</span>
                <span class="font-bold text-sm text-slate-900 font-mono">0292 3888 999</span>
            </div>

            <div class="p-4 rounded-xl bg-nenChinh border border-emerald-900/10 space-y-1.5">
                <span class="text-slate-500 font-medium block">Trạng thái tài khoản:</span>
                <span class="inline-flex items-center gap-1.5 font-bold text-emerald-700 bg-emerald-100/90 px-2.5 py-1 rounded-full text-[11px] border border-emerald-300">
                    <i class="fa-solid fa-circle-check"></i> HOẠT ĐỘNG (ACTIVE)
                </span>
                <div class="text-[10px] text-slate-400">Kiểm soát khả năng đăng nhập hệ thống</div>
            </div>

            @if(isset($coThePhapLy) && $coThePhapLy)
                <div class="p-4 rounded-xl bg-nenChinh border border-emerald-900/10 space-y-1.5 sm:col-span-2">
                    <span class="text-slate-500 font-medium block">Trạng thái phê duyệt tổ chức:</span>
                    <span class="inline-flex items-center gap-1.5 font-bold text-cyan-800 bg-cyan-100/90 px-2.5 py-1 rounded-full text-[11px] border border-cyan-300">
                        <i class="fa-solid fa-shield-check"></i> ĐÃ DUYỆT (DA_DUYET)
                    </span>
                    <div class="text-[10px] text-slate-500">
                        Kiểm soát quyền thực hiện nghiệp vụ trên chuỗi cung ứng
                    </div>
                </div>
            @else
                <div class="p-4 rounded-xl bg-nenChinh border border-emerald-900/10 space-y-1.5 sm:col-span-2">
                    <span class="text-slate-500 font-medium block">Đơn vị quản lý:</span>
                    <span class="font-bold text-slate-800 text-xs">Cơ quan / Quản trị nội bộ hệ thống</span>
                </div>
            @endif

            @if(isset($coDiaChiVi) && $coDiaChiVi)
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
                        0x89aF2b1C35E479a02FbE4A5cD6E7f8A9b0C1D2E3
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
    <!-- ══════════════════════════════════════════════════════════ -->
    @if(isset($coThePhapLy) && $coThePhapLy)
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

            <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-900 flex items-start gap-3 leading-relaxed">
                <i class="fa-solid fa-circle-info text-amber-600 text-base mt-0.5 shrink-0"></i>
                <div>
                    Thông tin định danh pháp lý đã được Cơ quan quản lý thẩm duyệt và không thể chỉnh sửa trực tuyến. Nếu cần thay đổi, vui lòng liên hệ trực tiếp Cơ quan quản lý.
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div class="sm:col-span-2 space-y-1">
                    <label class="text-slate-500 font-semibold">Tên tổ chức / doanh nghiệp:</label>
                    <input type="text" value="{{ $role['tenToChucMacDinh'] ?? 'Doanh nghiệp dược phẩm' }}" readonly disabled class="w-full px-4 py-2.5 bg-slate-100/80 border border-slate-200 rounded-xl font-bold text-slate-800 cursor-not-allowed">
                </div>

                <div class="space-y-1">
                    <label class="text-slate-500 font-semibold">Mã số thuế:</label>
                    <input type="text" value="0102345678" readonly disabled class="w-full px-4 py-2.5 bg-slate-100/80 border border-slate-200 rounded-xl font-mono font-bold text-slate-800 cursor-not-allowed">
                </div>

                <div class="space-y-1">
                    <label class="text-slate-500 font-semibold">Số giấy phép kinh doanh dược:</label>
                    <input type="text" value="123/ĐKKDD-BYT" readonly disabled class="w-full px-4 py-2.5 bg-slate-100/80 border border-slate-200 rounded-xl font-mono font-bold text-slate-800 cursor-not-allowed">
                </div>

                <div class="space-y-1">
                    <label class="text-slate-500 font-semibold">Cơ quan cấp phép:</label>
                    <input type="text" value="Cục Quản lý Dược - Bộ Y Tế" readonly disabled class="w-full px-4 py-2.5 bg-slate-100/80 border border-slate-200 rounded-xl font-semibold text-slate-800 cursor-not-allowed">
                </div>

                <div class="space-y-1">
                    <label class="text-slate-500 font-semibold">Thời hạn hiệu lực giấy phép:</label>
                    <input type="text" value="15/01/2024 — 15/01/2029" readonly disabled class="w-full px-4 py-2.5 bg-slate-100/80 border border-slate-200 rounded-xl font-mono font-semibold text-slate-800 cursor-not-allowed">
                </div>

                <div class="sm:col-span-2 space-y-1">
                    <label class="text-slate-500 font-semibold">Địa chỉ trụ sở pháp lý:</label>
                    <input type="text" value="Số 123 Đường Nguyễn Trãi, Phường An Bình, Quận Ninh Kiều, TP. Cần Thơ" readonly disabled class="w-full px-4 py-2.5 bg-slate-100/80 border border-slate-200 rounded-xl font-medium text-slate-800 cursor-not-allowed">
                </div>

                <div class="sm:col-span-2 pt-2 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-slate-500 font-medium">Hồ sơ minh chứng đã lưu:</span>
                    <a href="javascript:void(0)" onclick="alert('Mở xem bản scan giấy phép kinh doanh dược đã thẩm duyệt')" class="inline-flex items-center gap-1.5 text-cyan-800 hover:text-cyan-950 font-bold hover:underline">
                        <i class="fa-solid fa-file-pdf text-red-500 text-sm"></i> Xem hồ sơ đăng ký (Bản scan giấy phép)
                    </a>
                </div>
            </div>

        </section>
    @endif

</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!-- MODAL CHUYÊN BIỆT: CHỈ ĐỔI MẬT KHẨU                      -->
<!-- ══════════════════════════════════════════════════════════ -->
<div id="modalDoiMatKhau" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 hidden bg-black/60">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 sm:p-8 space-y-5 bong-xanh">

        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-key {{ $role['textClass'] ?? 'text-emerald-800' }}"></i> Đổi Mật Khẩu Đăng Nhập
            </h3>
            <button type="button" id="nutDongModalDoiPass" class="text-slate-400 hover:text-slate-700 text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="formDoiMatKhau" class="space-y-4 text-xs">
            <div class="space-y-1">
                <label for="matKhauHienTai" class="font-bold text-slate-700">Mật khẩu hiện tại <span class="text-red-500">*</span></label>
                <input type="password" id="matKhauHienTai" required placeholder="Nhập mật khẩu đang dùng..." class="w-full px-3.5 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-slate-900 focus:bg-white focus:border-emerald-700 outline-none">
            </div>

            <div class="space-y-1">
                <label for="matKhauMoi" class="font-bold text-slate-700">Mật khẩu mới <span class="text-red-500">*</span></label>
                <input type="password" id="matKhauMoi" required minlength="8" placeholder="Tối thiểu 8 ký tự..." class="w-full px-3.5 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-slate-900 focus:bg-white focus:border-emerald-700 outline-none">
            </div>

            <div class="space-y-1">
                <label for="xacNhanMatKhauMoi" class="font-bold text-slate-700">Xác nhận mật khẩu mới <span class="text-red-500">*</span></label>
                <input type="password" id="xacNhanMatKhauMoi" required minlength="8" placeholder="Nhập lại mật khẩu mới..." class="w-full px-3.5 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-slate-900 focus:bg-white focus:border-emerald-700 outline-none">
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                <button type="button" id="nutHuyDoiPass" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-all">
                    Hủy
                </button>
                <button type="submit" class="px-6 py-2.5 {{ $role['btnClass'] ?? 'bg-emerald-700 hover:bg-emerald-800 text-white' }} font-bold rounded-xl shadow-md transition-all flex items-center gap-2">
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
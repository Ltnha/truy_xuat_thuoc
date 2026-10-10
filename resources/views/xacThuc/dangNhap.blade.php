@extends('layouts.cong')
@section('title', 'Đăng Nhập Hệ Thống – PharmaChain')

@section('content')
<main class="flex-1 flex items-center justify-center py-14 px-4 sm:px-6"><!-- KHUNG ĐĂNG NHẬP CĂN GIỮA - 1 FORM DUY NHẤT -->
    <div class="w-full max-w-md bg-white rounded-2xl border border-emerald-900/10 p-8 bong-xanh space-y-6">

        <!-- Header form -->
        <div class="text-center space-y-2">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-2xl font-bold shadow-sm">
                <i class="fa-solid fa-right-to-bracket"></i>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Đăng Nhập Hệ Thống</h1>
            <p class="text-xs text-slate-500">Cổng đăng nhập dùng chung cho các đơn vị trong chuỗi cung ứng & quản trị</p>
        </div>

        <!-- Thông báo lỗi (ẩn mặc định, kích hoạt qua JS/PHP) -->
        <div id="thongBaoLoi" class="hidden p-3 bg-red-50 border border-red-200 rounded-xl text-xs text-red-600 font-medium flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation shrink-0"></i>
            <span id="noiDungLoi">Tên đăng nhập hoặc mật khẩu không chính xác.</span>
        </div>

        <!-- Form đăng nhập -->
        <form id="formDangNhap" action="{{ route('dangNhap.store') }}" method="POST" class="space-y-4">
@csrf
<!-- Ô Tên đăng nhập (taiKhoan.tenDangNhap) -->
            <div class="space-y-1.5">
                <label for="tenDangNhap" class="block text-xs font-bold text-slate-700 uppercase tracking-wide">
                    Tên đăng nhập <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 text-sm pointer-events-none">
                        <i class="fa-solid fa-user"></i>
                    </span>
                    <input type="text" id="tenDangNhap" name="tenDangNhap" value="{{ old('tenDangNhap') }}" required autocomplete="username" placeholder="Nhập tên đăng nhập của bạn..." class="w-full pl-10 pr-4 py-3 bg-[#f2faf5] border border-emerald-900/15 rounded-xl text-sm font-medium text-slate-900 focus:bg-white focus:border-emerald-700 focus:ring-2 focus:ring-emerald-100 outline-none transition-all">
                </div>
                @error('tenDangNhap')
                    <p class="text-xs text-red-600" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <!-- Ô Mật khẩu (taiKhoan.matKhau) -->
            <div class="space-y-1.5">
                <label for="matKhau" class="block text-xs font-bold text-slate-700 uppercase tracking-wide">
                    Mật khẩu <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 text-sm pointer-events-none">
                        <i class="fa-solid fa-lock"></i>
                    </span>
                    <input type="password" id="matKhau" name="matKhau" required autocomplete="current-password" placeholder="Nhập mật khẩu..." class="w-full pl-10 pr-11 py-3 bg-[#f2faf5] border border-emerald-900/15 rounded-xl text-sm font-medium text-slate-900 focus:bg-white focus:border-emerald-700 focus:ring-2 focus:ring-emerald-100 outline-none transition-all">
                    <!-- Nút ẩn/hiện mật khẩu -->
                    <button type="button" id="nutAnHienMatKhau" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-emerald-700 text-sm transition-colors" title="Ẩn/hiện mật khẩu">
                        <i class="fa-solid fa-eye" id="iconAnHien"></i>
                    </button>
                </div>
            </div>

            <!-- Nút Đăng nhập full-width -->
            <button type="submit" id="nutDangNhap" class="w-full py-3.5 bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-sm rounded-xl shadow-md transition-all flex items-center justify-center gap-2">
                <i class="fa-solid fa-arrow-right-to-bracket"></i> Đăng Nhập
            </button>
        </form>

        <!-- Dòng link phụ bên dưới -->
        <div class="pt-4 border-t border-slate-100 text-center">
            <p class="text-xs text-slate-600">
                Chưa có tài khoản?
                <a href="{{ route('dangKyToChuc') }}" class="font-bold text-cyan-800 hover:text-cyan-900 hover:underline transition-colors ml-1">
                    Đăng ký tài khoản tổ chức
                </a>
            </p>
            <a href="{{ route('dangKyToChuc.trangThai') }}" class="mt-2 inline-block text-xs font-bold text-slate-600 hover:text-emerald-700">Kiểm tra trạng thái hồ sơ</a>
        </div>

    </div></main>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/xacThuc/dangNhap.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/js/xacThuc/dangNhap.js') }}" defer></script>
@endpush

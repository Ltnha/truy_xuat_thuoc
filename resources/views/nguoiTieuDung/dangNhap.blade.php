@extends('layoutNguoiTieuDung.main')

@section('content')
<main class="flex-1 py-12 px-4 sm:px-6 lg:px-8 max-w-md mx-auto w-full space-y-6">
    <div class="bg-white rounded-2xl border border-emerald-900/10 p-6 sm:p-8 bong-xanh space-y-6">

        <div class="text-center space-y-2">
            <div class="w-12 h-12 mx-auto rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-xl font-bold">
                <i class="fa-solid fa-right-to-bracket"></i>
            </div>
            <h1 class="text-xl font-bold text-slate-900">Đăng Nhập Hệ Thống</h1>
            <p class="text-xs text-slate-500">Dành cho Doanh nghiệp & Cơ quan quản lý</p>
        </div>

        @if (session('thanhCong'))
        <div class="p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-emerald-800 font-medium flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-emerald-600 shrink-0"></i>
            <span>{{ session('thanhCong') }}</span>
        </div>
        @endif

        @if ($errors->has('tenDangNhap'))
        <div class="p-4 bg-red-50 border border-red-200 rounded-2xl text-xs text-red-800 space-y-3">
            <div class="font-bold flex items-start gap-2 text-red-900 leading-relaxed">
                <i class="fa-solid fa-circle-exclamation text-red-600 shrink-0 mt-0.5"></i>
                <span>{{ $errors->first('tenDangNhap') }}</span>
            </div>

            {{-- Nút Sửa & Nộp lại hồ sơ khi bị từ chối --}}
            @if (session('toChucTuChoiId'))
            <div class="pt-2 border-t border-red-200/60 flex justify-end">
                <a href="{{ route('dangKyToChuc.resubmitForm', session('toChucTuChoiId')) }}" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl text-xs transition-all flex items-center gap-1.5 shadow-sm">
                    <i class="fa-solid fa-pen-to-square"></i> Sửa & Nộp Lại Hồ Sơ
                </a>
            </div>
            @endif
        </div>
        @endif

        <form action="{{ route('login.store') }}" method="POST" class="space-y-4 text-xs">
            @csrf

            <div class="space-y-1">
                <label for="tenDangNhap" class="font-bold text-slate-700">Tên đăng nhập</label>
                <input type="text" id="tenDangNhap" name="tenDangNhap" value="{{ old('tenDangNhap') }}" required placeholder="Nhập tên đăng nhập..." class="w-full px-4 py-2.5 bg-[#f2faf5] border border-emerald-900/15 rounded-xl text-sm font-medium text-slate-900 focus:bg-white focus:border-emerald-700 outline-none transition-all">
            </div>

            <div class="space-y-1">
                <label for="matKhau" class="font-bold text-slate-700">Mật khẩu</label>
                <input type="password" id="matKhau" name="matKhau" required placeholder="Nhập mật khẩu..." class="w-full px-4 py-2.5 bg-[#f2faf5] border border-emerald-900/15 rounded-xl text-sm font-medium text-slate-900 focus:bg-white focus:border-emerald-700 outline-none transition-all">
            </div>

            <button type="submit" class="w-full py-3 bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-sm rounded-xl shadow-md transition-all cursor-pointer">
                Đăng Nhập Hệ Thống
            </button>
        </form>

        <div class="text-center pt-2 border-t border-slate-100 text-xs">
            <span class="text-slate-500">Chưa có tài khoản doanh nghiệp?</span>
            <a href="{{ route('dangKyToChuc.form') }}" class="font-bold text-emerald-700 hover:underline ml-1">Đăng ký ngay</a>
        </div>
    </div>
</main>
@endsection
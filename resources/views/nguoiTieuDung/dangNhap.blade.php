@extends('layoutNguoiTieuDung.main')

@section('content')
<div class="max-w-xl mx-auto my-12 p-8 bg-white rounded-2xl shadow-xl border border-emerald-900/10">
    <div class="text-center mb-6">
        <div class="w-12 h-12 rounded-xl bg-emerald-700 text-white flex items-center justify-center mx-auto mb-3 text-2xl shadow-md">
            <i class="fa-solid fa-right-to-bracket"></i>
        </div>
        <h2 class="text-xl font-bold text-slate-800">Đăng Nhập Hệ Thống</h2>
        <p class="text-xs text-slate-500 mt-1">Dành cho Doanh nghiệp & Cơ quan quản lý</p>
    </div>

    <!-- Thông báo lỗi đăng nhập từ Backend -->
    @if ($errors->has('tenDangNhap'))
    <div class="mb-4 p-3.5 bg-red-50 border border-red-200 text-red-800 text-xs font-medium rounded-xl leading-relaxed">
        <i class="fa-solid fa-circle-exclamation text-red-600 mr-1.5"></i>
        {!! nl2br(e($errors->first('tenDangNhap'))) !!}
    </div>
    @endif

    <!-- Thông báo thành công (Ví dụ vừa đăng ký) -->
    @if (session('thanhCong'))
    <div class="mb-4 p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium rounded-xl">
        <i class="fa-solid fa-circle-check text-emerald-600 mr-1.5"></i>
        {{ session('thanhCong') }}
    </div>
    @endif

    <form id="formDangNhap" action="{{ route('login.store') }}" method="POST" class="space-y-4">
        @csrf
        <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Tên đăng nhập</label>
            <input type="text" name="tenDangNhap" id="tenDangNhap" value="{{ old('tenDangNhap') }}" required
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none focus:border-emerald-700">
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Mật khẩu</label>
            <div class="relative">
                <input type="password" name="matKhau" id="matKhau" required
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none focus:border-emerald-700">
                <button type="button" id="nutAnHienMatKhau" class="absolute right-3 top-3 text-slate-400 hover:text-slate-600">
                    <i id="iconAnHien" class="fa-regular fa-eye"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="w-full py-3 bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs rounded-xl shadow-md transition-all cursor-pointer">
            Đăng Nhập Hệ Thống
        </button>
    </form>
</div>
@endsection
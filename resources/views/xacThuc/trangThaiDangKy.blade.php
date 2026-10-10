@extends('layouts.cong')
@section('title', 'Kiểm Tra Trạng Thái Đăng Ký')

@section('content')
<main class="mx-auto w-full max-w-xl flex-1 space-y-5 px-4 py-12">
    <header>
        <h1 class="text-2xl font-extrabold text-emerald-800">Kiểm tra trạng thái hồ sơ</h1>
        <p class="mt-2 text-sm text-slate-600">Nhập tài khoản và mật khẩu đã đăng ký để xem tiến độ xét duyệt.</p>
    </header>

    @if (session('thanhCong'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900" role="status">{{ session('thanhCong') }}</div>
    @endif

    @isset($trangThaiToChuc)
        <section class="space-y-2 rounded-xl border border-slate-200 bg-white p-4 text-sm">
            <p>Trạng thái tổ chức: <strong>{{ $trangThaiToChuc }}</strong></p>
            <p>Trạng thái tài khoản: <strong>{{ $trangThaiTaiKhoan }}</strong></p>
            @if ($yeuCauDangKy?->lyDoTuChoi)
                <p class="text-red-700">Lý do từ chối: {{ $yeuCauDangKy->lyDoTuChoi }}</p>
            @endif
            @if ($yeuCauDangKy?->ngayGui)
                <p class="text-xs text-slate-500">Ngày gửi hồ sơ: {{ $yeuCauDangKy->ngayGui }}</p>
            @endif
        </section>
    @endisset

    <form method="POST" action="{{ route('dangKyToChuc.kiemTraTrangThai') }}" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        <label class="block text-xs font-bold">Tên đăng nhập
            <input name="tenDangNhap" value="{{ old('tenDangNhap') }}" required autocomplete="username" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5">
        </label>
        <label class="block text-xs font-bold">Mật khẩu
            <input name="matKhau" type="password" required autocomplete="current-password" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5">
        </label>
        <button class="w-full rounded-lg bg-emerald-700 px-4 py-3 text-sm font-bold text-white">Kiểm tra</button>
        <a href="{{ route('dangNhap') }}" class="block text-center text-xs font-bold text-cyan-800">Quay lại đăng nhập</a>
    </form>
</main>
@endsection

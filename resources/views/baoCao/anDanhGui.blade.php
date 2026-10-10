@extends('layouts.cong')
@section('title', 'Báo Cáo Nghi Vấn Dược Phẩm')

@section('content')
<main class="mx-auto w-full max-w-3xl space-y-5 px-4 py-10">
    <div>
        <h1 class="text-2xl font-extrabold text-emerald-800">Báo cáo nghi vấn dược phẩm</h1>
        <p class="mt-2 text-sm text-slate-600">Báo cáo được lưu vào hệ thống để cơ quan quản lý xác minh. Bạn có thể gửi ẩn danh.</p>
    </div>
    @if (session('maBaoCao'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900" role="status">
            Mã tra cứu báo cáo: <strong class="font-mono">{{ session('maBaoCao') }}</strong> — hãy lưu lại mã này để theo dõi tiến độ xử lý.
        </div>
    @endif
    <form method="GET" action="{{ route('cong.baoCao.trangThai') }}" class="flex gap-2 rounded-xl border border-slate-200 bg-white p-4">
        <input name="maBaoCao" required maxlength="50" placeholder="Mã tra cứu báo cáo" class="min-w-0 flex-1 rounded-lg border border-slate-300 px-3 py-2 font-mono text-sm">
        <button class="rounded-lg bg-cyan-800 px-4 py-2 text-xs font-bold text-white">Kiểm tra tiến độ</button>
    </form>
    <form method="POST" action="{{ route('cong.baoCao.store') }}" enctype="multipart/form-data" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        <label class="block text-xs font-bold text-slate-700">Mã lô thuốc
            <input name="maLo" value="{{ old('maLo', request('maLo', request('maDinhDanh'))) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 font-mono text-sm">
        </label>
        <label class="block text-xs font-bold text-slate-700">Lý do nghi vấn
            <input name="lyDo" value="{{ old('lyDo') }}" required maxlength="5000" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
        </label>
        <label class="block text-xs font-bold text-slate-700">Mô tả chi tiết
            <textarea name="moTa" rows="4" maxlength="10000" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">{{ old('moTa') }}</textarea>
        </label>
        <label class="block text-xs font-bold text-slate-700">Ảnh bằng chứng (tối đa 5 ảnh, JPG/PNG, 10 MB mỗi ảnh)
            <input type="file" name="minhChung[]" accept="image/jpeg,image/png" multiple class="mt-1 block w-full rounded-lg border border-slate-300 p-2 text-sm">
        </label>
        <button class="w-full rounded-xl bg-amber-600 px-4 py-3 text-sm font-bold text-white hover:bg-amber-700">Gửi báo cáo</button>
    </form>
</main>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/baoCao/baoCaoNghiVan.css') }}">
@endpush

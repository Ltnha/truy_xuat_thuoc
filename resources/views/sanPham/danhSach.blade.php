@extends('layouts.app')
@section('title', 'Quản Lý Sản Phẩm Dược')
@section('panelTitle', 'Danh Sách Sản Phẩm Đăng Ký')

@section('content')
<section class="bg-white rounded-2xl border border-emerald-900/10 p-5 shadow-sm">
    <h2 class="text-sm font-bold text-slate-800">Gửi hồ sơ sản phẩm mới</h2>
    <p class="mt-1 text-xs text-slate-500">Hồ sơ sẽ ở trạng thái chờ cơ quan quản lý duyệt. Thông tin đã có lô hoặc lịch sử hồ sơ không thể xóa.</p>
    <form method="POST" action="{{ route('nsx.sanPham.store') }}" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
        @csrf
        <label class="text-xs font-semibold text-slate-700">Tên sản phẩm
            <input name="tenSanPham" value="{{ old('tenSanPham') }}" required maxlength="255" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </label>
        <label class="text-xs font-semibold text-slate-700">Số đăng ký
            <input name="soDangKy" value="{{ old('soDangKy') }}" required maxlength="50" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </label>
        <label class="text-xs font-semibold text-slate-700">Hoạt chất
            <input name="hoatChat" value="{{ old('hoatChat') }}" maxlength="255" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </label>
        <label class="text-xs font-semibold text-slate-700">Quy cách đóng gói
            <input name="quyCachDongGoi" value="{{ old('quyCachDongGoi') }}" maxlength="255" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </label>
        <div class="md:col-span-2">
            <button class="rounded-lg bg-emerald-700 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-800" type="submit">Gửi đăng ký sản phẩm</button>
        </div>
    </form>
</section>

<section class="mt-6 overflow-hidden rounded-2xl border border-emerald-900/10 bg-white shadow-sm">
    <div class="border-b bg-slate-50 px-4 py-3 text-xs font-bold text-slate-700">Sản phẩm của tổ chức</div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-white text-[11px] uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Tên sản phẩm / Số đăng ký</th>
                    <th class="px-4 py-3">Hoạt chất</th>
                    <th class="px-4 py-3">Quy cách</th>
                    <th class="px-4 py-3">Trạng thái</th>
                    <th class="px-4 py-3">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($sanPhams as $sanPham)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-bold text-slate-800">{{ $sanPham->tenSanPham }}</div>
                            <div class="mt-1 font-mono text-slate-500">{{ $sanPham->soDangKy }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $sanPham->hoatChat ?: '—' }}</td>
                        <td class="px-4 py-3">{{ $sanPham->quyCachDongGoi ?: '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full bg-slate-100 px-2 py-1 font-semibold">{{ $sanPham->trangThaiDuyet->value }}</span>
                            @if ($sanPham->yeuCauDangKySanPhams->isNotEmpty())
                                <div class="mt-1 text-[10px] text-slate-500">Hồ sơ #{{ $sanPham->yeuCauDangKySanPhams->first()->versionHoSo }}</div>
                                @if ($sanPham->yeuCauDangKySanPhams->first()->lyDoTuChoi)
                                    <div class="mt-1 max-w-xs text-red-700">{{ $sanPham->yeuCauDangKySanPhams->first()->lyDoTuChoi }}</div>
                                @endif
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($sanPham->trangThaiDuyet->value === 'TU_CHOI')
                                <details>
                                    <summary class="cursor-pointer font-bold text-amber-700">Chỉnh sửa / nộp lại</summary>
                                    <form method="POST" action="{{ route('nsx.sanPham.update', $sanPham) }}" class="mt-2 min-w-64 space-y-2">
                                        @csrf
                                        @method('PUT')
                                        <input name="tenSanPham" value="{{ $sanPham->tenSanPham }}" required class="w-full rounded border px-2 py-1">
                                        <input name="soDangKy" value="{{ $sanPham->soDangKy }}" required class="w-full rounded border px-2 py-1">
                                        <input name="hoatChat" value="{{ $sanPham->hoatChat }}" placeholder="Hoạt chất" class="w-full rounded border px-2 py-1">
                                        <input name="quyCachDongGoi" value="{{ $sanPham->quyCachDongGoi }}" placeholder="Quy cách đóng gói" class="w-full rounded border px-2 py-1">
                                        <button class="rounded bg-amber-600 px-3 py-1.5 font-bold text-white">Lưu và nộp lại</button>
                                    </form>
                                </details>
                            @elseif (!$sanPham->loThuocs()->exists() && !$sanPham->yeuCauDangKySanPhams()->exists())
                                <form method="POST" action="{{ route('nsx.sanPham.destroy', $sanPham) }}" onsubmit="return confirm('Xóa bản nháp sản phẩm này?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="font-bold text-red-600">Xóa bản nháp</button>
                                </form>
                            @else
                                <span class="text-slate-400">Được bảo vệ theo lịch sử truy xuất</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">Chưa có sản phẩm nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection

@extends('layouts.cong')
@section('title', 'Tra Cứu Nguồn Gốc Dược Phẩm')

@section('content')
<main class="mx-auto w-full max-w-5xl space-y-6 px-4 py-10">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-emerald-900/10 pb-5">
        <h1 class="text-2xl font-extrabold text-emerald-800">Tra cứu nguồn gốc thuốc</h1>
        <a href="{{ route('cong.truyXuat.lichSu') }}" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-xs font-bold text-emerald-800">Lịch sử tra cứu</a>
    </div>

    <form action="{{ route('cong.truyXuat.xem') }}" method="GET" class="flex flex-col gap-2 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row">
        <input name="maDinhDanh" value="{{ $ma }}" required placeholder="Mã lô, mã đơn vị, serial hoặc mã QR" class="min-w-0 flex-1 rounded-lg border border-slate-300 px-4 py-3 font-mono text-sm">
        <button class="rounded-lg bg-emerald-700 px-6 py-3 text-sm font-bold text-white hover:bg-emerald-800">Tra cứu</button>
    </form>

    @if ($ketQua)
        @php
            $statusClasses = match ($ketQua['trangThai']) {
                'VALID' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
                'RECALLED' => 'border-red-200 bg-red-50 text-red-800',
                'EXPIRED' => 'border-amber-200 bg-amber-50 text-amber-800',
                default => 'border-slate-200 bg-slate-50 text-slate-800',
            };
        @endphp
        <section class="rounded-2xl border p-5 {{ $statusClasses }}" role="status">
            <div class="text-xs font-extrabold uppercase tracking-wider">{{ $ketQua['trangThai'] }}</div>
            <h2 class="mt-2 text-lg font-bold">{{ $ketQua['tenThuoc'] }}</h2>
            @if ($ketQua['lyDoThuHoi'] ?? null)<p class="mt-2 text-sm">{{ $ketQua['lyDoThuHoi'] }}</p>@endif
            @if ($ketQua['trangThai'] === 'NOT_FOUND')<p class="mt-2 text-sm">Không tìm thấy mã tra cứu này trong dữ liệu hiện tại.</p>@endif
        </section>

        @if ($ketQua['trangThai'] !== 'NOT_FOUND')
            <div class="grid gap-5 lg:grid-cols-3">
                <section class="space-y-3 rounded-2xl border border-slate-200 bg-white p-5 text-sm lg:col-span-2">
                    <h2 class="font-bold text-emerald-800">Thông tin sản phẩm và lô</h2>
                    <dl class="grid gap-3 sm:grid-cols-2">
                        <div><dt class="text-xs text-slate-500">Số đăng ký</dt><dd class="font-semibold">{{ $ketQua['soDangKy'] }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Nhà sản xuất</dt><dd class="font-semibold">{{ $ketQua['nhaSanXuat'] }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Hoạt chất</dt><dd class="font-semibold">{{ $ketQua['hoatChat'] }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Quy cách</dt><dd class="font-semibold">{{ $ketQua['hamLuongDangBaoChe'] }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Mã lô</dt><dd class="font-mono font-semibold">{{ $ketQua['maLo'] }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Trạng thái đơn vị</dt><dd class="font-semibold">{{ $ketQua['isDispensed'] }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Ngày sản xuất</dt><dd class="font-semibold">{{ $ketQua['ngaySanXuat'] }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Hạn sử dụng</dt><dd class="font-semibold">{{ $ketQua['hanSuDung'] }}</dd></div>
                    </dl>
                    <div class="border-t pt-4">
                        <h3 class="mb-3 text-xs font-bold uppercase text-slate-500">Lịch sử chuỗi cung ứng</h3>
                        <ol class="space-y-3">
                            @forelse ($ketQua['hanhTrinh'] as $buoc)
                                <li class="border-l-2 border-emerald-600 pl-3">
                                    <div class="text-xs font-bold">{{ $buoc['tieuDe'] }}</div>
                                    <div class="mt-1 text-xs text-slate-600">{{ $buoc['donVi'] }}</div>
                                    <div class="mt-1 text-[11px] text-slate-400">{{ $buoc['thoiGian'] }}</div>
                                </li>
                            @empty
                                <li class="text-xs text-slate-500">Chưa có lịch sử chuyển giao.</li>
                            @endforelse
                        </ol>
                    </div>
                </section>
                <aside class="space-y-3 rounded-2xl border border-cyan-200 bg-white p-5 text-xs">
                    <h2 class="font-bold text-cyan-800">Dữ liệu truy xuất</h2>
                    <div><div class="text-slate-500">Mã định danh lô</div><div class="break-all font-mono">{{ $ketQua['batchIdHash'] }}</div></div>
                    <div><div class="text-slate-500">Mã tham chiếu giao dịch hệ thống</div><div class="break-all font-mono">{{ $ketQua['txHash'] }}</div></div>
                    <a href="{{ route('cong.baoCao.gui', ['maLo' => $ketQua['maLo']]) }}" class="inline-block rounded-lg bg-amber-600 px-3 py-2 font-bold text-white">Báo cáo bất thường</a>
                </aside>
            </div>
        @endif
    @endif
</main>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/cong/traCuuNguonGoc.css') }}">
@endpush

@if ($ketQua && $ketQua['trangThai'] !== 'NOT_FOUND')
    @push('scripts')
    <script>
        (() => {
            try {
                const key = 'pharma_lich_su_tra_cuu';
                const history = JSON.parse(localStorage.getItem(key) || '[]');
                const entry = {
                    ma: @json($ma),
                    tenThuoc: @json($ketQua['tenThuoc']),
                    trangThai: @json($ketQua['trangThai']),
                    thoiGian: new Date().toLocaleString('vi-VN')
                };
                const updated = [entry, ...history.filter(item => item.ma !== entry.ma)].slice(0, 20);
                localStorage.setItem(key, JSON.stringify(updated));
            } catch (error) {
                console.error('Không thể lưu lịch sử tra cứu trên trình duyệt.', error);
            }
        })();
    </script>
    @endpush
@endif

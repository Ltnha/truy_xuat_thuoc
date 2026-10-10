@extends('layouts.app')
@section('title', 'Duyệt Hồ Sơ')
@section('panelTitle', 'Xét Duyệt Tổ Chức & Sản Phẩm')

@section('content')
<section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b bg-slate-50 px-4 py-3 text-xs font-bold text-slate-700">Tổ chức chờ xét duyệt</div>
    <div class="divide-y divide-slate-100">
        @forelse ($toChucs as $toChuc)
            <article class="grid gap-4 p-4 lg:grid-cols-[1fr_auto]">
                <div class="text-xs">
                    <h2 class="font-bold text-slate-900">{{ $toChuc->tenToChuc }}</h2>
                    <p class="mt-1 text-slate-600">{{ $toChuc->loaiToChuc->value }} · MST {{ $toChuc->maSoThue ?: '—' }}</p>
                    <p class="mt-1 text-slate-600">{{ $toChuc->diaChi }} · {{ $toChuc->soDienThoai }}</p>
                    @if ($toChuc->yeuCauDangKys->isNotEmpty())
                        <p class="mt-1 text-slate-500">Gửi ngày {{ $toChuc->yeuCauDangKys->first()->ngayGui }}</p>
                    @endif
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <form method="POST" action="{{ route('cq.toChuc.approve', $toChuc) }}" class="flex flex-wrap gap-2">
                        @csrf
                        @method('PATCH')
                        <input name="lyDo" placeholder="Lý do nếu từ chối" class="rounded border px-2 py-1.5 text-xs">
                        <button name="trangThaiDuyet" value="DA_DUYET" class="rounded bg-emerald-700 px-3 py-1.5 text-xs font-bold text-white">Duyệt</button>
                        <button name="trangThaiDuyet" value="TU_CHOI" class="rounded bg-red-600 px-3 py-1.5 text-xs font-bold text-white">Từ chối</button>
                    </form>
                </div>
            </article>
        @empty
            <p class="p-6 text-center text-xs text-slate-500">Không có tổ chức chờ xét duyệt.</p>
        @endforelse
    </div>
    <div class="border-t p-4">{{ $toChucs->links() }}</div>
</section>

<section class="mt-6 rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b bg-slate-50 px-4 py-3 text-xs font-bold text-slate-700">Sản phẩm chờ xét duyệt</div>
    <div class="divide-y divide-slate-100">
        @forelse ($sanPhams as $sanPham)
            <article class="grid gap-4 p-4 lg:grid-cols-[1fr_auto]">
                <div class="text-xs">
                    <h2 class="font-bold text-slate-900">{{ $sanPham->tenSanPham }} · {{ $sanPham->soDangKy }}</h2>
                    <p class="mt-1 text-slate-600">{{ $sanPham->nhaSanXuat->tenToChuc }}</p>
                    <p class="mt-1 text-slate-600">{{ $sanPham->hoatChat }} · {{ $sanPham->quyCachDongGoi }}</p>
                </div>
                <form method="POST" action="{{ route('cq.sanPham.approve', $sanPham) }}" class="flex flex-wrap items-center gap-2">
                    @csrf
                    @method('PATCH')
                    <input name="lyDo" placeholder="Lý do nếu từ chối" class="rounded border px-2 py-1.5 text-xs">
                    <button name="trangThaiDuyet" value="DA_DUYET" class="rounded bg-emerald-700 px-3 py-1.5 text-xs font-bold text-white">Duyệt</button>
                    <button name="trangThaiDuyet" value="TU_CHOI" class="rounded bg-red-600 px-3 py-1.5 text-xs font-bold text-white">Từ chối</button>
                </form>
            </article>
        @empty
            <p class="p-6 text-center text-xs text-slate-500">Không có sản phẩm chờ xét duyệt.</p>
        @endforelse
    </div>
    <div class="border-t p-4">{{ $sanPhams->links() }}</div>
</section>
@endsection

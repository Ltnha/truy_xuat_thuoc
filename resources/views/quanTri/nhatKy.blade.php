@extends('layouts.app')
@section('title', 'Nhật Ký Hệ Thống')
@section('panelTitle', 'Nhật Ký Kiểm Toán')

@section('content')
<section class="overflow-hidden rounded-2xl border border-emerald-900/10 bg-white shadow-sm">
    <div class="border-b bg-slate-50 px-4 py-3 text-xs font-bold text-slate-700">Nhật ký nghiệp vụ · {{ $nhatKys->total() }} bản ghi</div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-white text-[11px] uppercase text-slate-500">
                <tr><th class="px-4 py-3">Mã</th><th class="px-4 py-3">Thời gian</th><th class="px-4 py-3">Tài khoản</th><th class="px-4 py-3">Hành động</th><th class="px-4 py-3">Chi tiết</th><th class="px-4 py-3">IP</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($nhatKys as $item)
                    <tr>
                        <td class="px-4 py-3 font-mono">{{ $item->id }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $item->thoiGian }}</td>
                        <td class="px-4 py-3 font-semibold">{{ $item->tenDangNhap }}</td>
                        <td class="px-4 py-3 font-mono">{{ $item->hanhDong }}</td>
                        <td class="max-w-xl px-4 py-3">{{ $item->noiDung }}</td>
                        <td class="px-4 py-3 font-mono">{{ $item->diaChiIP }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Chưa có nhật ký hệ thống.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="border-t p-4">{{ $nhatKys->links() }}</div>
</section>
@endsection

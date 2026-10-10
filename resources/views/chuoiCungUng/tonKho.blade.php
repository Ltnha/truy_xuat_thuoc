@extends('layouts.app')
@section('title', 'Tồn Kho')
@section('panelTitle', 'Tồn Kho Theo Tổ Chức')

@section('content')
<section class="overflow-hidden rounded-2xl border border-emerald-900/10 bg-white shadow-sm">
    <div class="border-b bg-slate-50 px-4 py-3 text-xs font-bold text-slate-700">Các lô đang được ghi nhận trong kho của tổ chức</div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-white text-[11px] uppercase text-slate-500">
                <tr><th class="px-4 py-3">Mã lô</th><th class="px-4 py-3">Sản phẩm</th><th class="px-4 py-3">Hạn dùng</th><th class="px-4 py-3">Số lượng tồn</th><th class="px-4 py-3">Trạng thái lô</th><th class="px-4 py-3">Cập nhật lúc</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($tonKho as $item)
                    <tr>
                        <td class="px-4 py-3 font-mono font-bold">{{ $item->maLoNghiepVu }}</td>
                        <td class="px-4 py-3">{{ $item->tenSanPham }}</td>
                        <td class="px-4 py-3">{{ $item->hanSuDung }}</td>
                        <td class="px-4 py-3 font-bold">{{ number_format($item->soLuong) }}</td>
                        <td class="px-4 py-3">{{ $item->trangThai }}</td>
                        <td class="px-4 py-3">{{ $item->ngayCapNhat }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Kho chưa có lô thuốc.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="border-t p-4">{{ $tonKho->links() }}</div>
</section>
@endsection

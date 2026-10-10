@extends('layouts.app')
@section('title', 'Bán lẻ')
@section('panelTitle', 'Ghi Nhận Bán Lẻ Thuốc')

@section('content')
<section class="mb-6 rounded-2xl border border-emerald-900/10 bg-white p-5 shadow-sm">
    <h2 class="text-sm font-bold text-slate-800">Quét mã đơn vị đã bán</h2>
    <p class="mt-1 text-xs text-slate-500">Mỗi lần xác nhận sẽ ghi nhận một đơn vị đã bán, trừ một đơn vị tồn kho và lưu dấu vết giao dịch.</p>
    <form method="POST" action="{{ route('nt.banLe.store') }}" class="mt-4 flex flex-col gap-2 sm:flex-row">
        @csrf
        <input name="maDinhDanh" value="{{ old('maDinhDanh') }}" required autofocus placeholder="Mã đơn vị hoặc serial" class="min-w-0 flex-1 rounded-lg border border-slate-300 px-3 py-2 font-mono text-sm">
        <button class="rounded-lg bg-cyan-700 px-5 py-2 text-xs font-bold text-white">Xác nhận đã bán</button>
    </form>
</section>

<section class="mb-6 overflow-hidden rounded-2xl border border-emerald-900/10 bg-white shadow-sm">
    <h2 class="border-b bg-slate-50 px-4 py-3 text-xs font-bold">Tồn kho khả dụng</h2>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="text-[11px] uppercase text-slate-500"><tr><th class="px-4 py-3">Sản phẩm</th><th class="px-4 py-3">Mã lô</th><th class="px-4 py-3">Hạn dùng</th><th class="px-4 py-3">Tồn</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($tonKho as $item)
                    <tr><td class="px-4 py-3">{{ $item->tenSanPham }}</td><td class="px-4 py-3 font-mono">{{ $item->maLoNghiepVu }}</td><td class="px-4 py-3">{{ $item->hanSuDung }}</td><td class="px-4 py-3 font-bold">{{ number_format($item->soLuong) }}</td></tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">Không có tồn kho khả dụng.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="overflow-hidden rounded-2xl border border-emerald-900/10 bg-white shadow-sm">
    <h2 class="border-b bg-slate-50 px-4 py-3 text-xs font-bold">Bán lẻ gần đây</h2>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="text-[11px] uppercase text-slate-500"><tr><th class="px-4 py-3">Thời điểm</th><th class="px-4 py-3">Sản phẩm / lô</th><th class="px-4 py-3">Mã đơn vị</th><th class="px-4 py-3">Giao dịch</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($banLes as $banLe)
                    <tr><td class="px-4 py-3">{{ $banLe->ngayBan }}</td><td class="px-4 py-3">{{ $banLe->tenSanPham }} · <span class="font-mono">{{ $banLe->maLoNghiepVu }}</span></td><td class="px-4 py-3 font-mono">{{ $banLe->maDonVi }}</td><td class="max-w-48 break-all px-4 py-3 font-mono">{{ $banLe->txHash }}</td></tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">Chưa có giao dịch bán lẻ.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection

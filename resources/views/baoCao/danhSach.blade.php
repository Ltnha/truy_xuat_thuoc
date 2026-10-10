@extends('layouts.app')
@section('title', 'Báo Cáo Nghi Vấn')
@section('panelTitle', 'Báo Cáo Nghi Vấn & Theo Dõi Xử Lý')

@section('content')
<section class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-emerald-900/10 bg-white p-5 shadow-sm">
    <div>
        <h2 class="text-sm font-bold text-slate-800">Báo cáo liên quan đến tài khoản</h2>
        <p class="mt-1 text-xs text-slate-500">Cơ quan quản lý xem toàn bộ báo cáo; đơn vị báo cáo chỉ xem báo cáo do mình gửi.</p>
    </div>
    <a href="{{ route('cong.baoCao.gui') }}" class="rounded-lg bg-amber-600 px-4 py-2 text-xs font-bold text-white">Gửi báo cáo mới</a>
</section>
<section class="overflow-hidden rounded-2xl border border-emerald-900/10 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 text-[11px] uppercase text-slate-500">
                <tr><th class="px-4 py-3">Mã báo cáo</th><th class="px-4 py-3">Mã lô / sản phẩm</th><th class="px-4 py-3">Lý do</th><th class="px-4 py-3">Ngày gửi</th><th class="px-4 py-3">Trạng thái</th><th class="px-4 py-3">Xử lý</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($baoCaos as $baoCao)
                    <tr>
                        <td class="px-4 py-3 font-mono font-bold">{{ $baoCao->maBaoCao }}</td>
                        <td class="px-4 py-3"><div class="font-mono">{{ $baoCao->loThuoc->maLoNghiepVu }}</div><div class="mt-1 text-slate-500">{{ $baoCao->loThuoc->sanPham->tenSanPham }}</div></td>
                        <td class="max-w-md px-4 py-3">
                            <div>{{ $baoCao->lyDo }}</div>
                            @if ($baoCao->moTa)<div class="mt-1 text-slate-500">{{ $baoCao->moTa }}</div>@endif
                            @if ($baoCao->minhChungs->isNotEmpty())
                                <div class="mt-2 space-y-1">
                                    @foreach ($baoCao->minhChungs as $minhChung)
                                        <a class="block font-semibold text-cyan-800 underline" href="{{ route('baoCao.minhChung', $minhChung) }}">{{ $minhChung->tenFile }}</a>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $baoCao->ngayGui->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 font-bold">{{ $baoCao->trangThai->value }}</td>
                        <td class="px-4 py-3">
                            @if (auth()->user()->vaiTro === \App\Enums\Role::coQuanQuanLy && in_array($baoCao->trangThai->value, ['PENDING', 'PROCESSING'], true))
                                <form method="POST" action="{{ route('cq.baoCao.xuLy', $baoCao) }}" class="min-w-56 space-y-2">
                                    @csrf
                                    @method('PATCH')
                                    <input name="lyDoXuLy" maxlength="500" placeholder="Kết luận / lý do" class="w-full rounded border px-2 py-1.5">
                                    <div class="flex flex-wrap gap-1">
                                        <button name="quyetDinh" value="PROCESSING" class="rounded bg-cyan-700 px-2 py-1.5 font-bold text-white">Đang xác minh</button>
                                        <button name="quyetDinh" value="THU_HOI" class="rounded bg-red-700 px-2 py-1.5 font-bold text-white">Thu hồi lô</button>
                                        <button name="quyetDinh" value="TU_CHOI" class="rounded bg-slate-600 px-2 py-1.5 font-bold text-white">Từ chối</button>
                                    </div>
                                </form>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Chưa có báo cáo nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="border-t p-4">{{ $baoCaos->links() }}</div>
</section>
@endsection

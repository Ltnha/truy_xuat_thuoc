@extends('layouts.cong')
@section('title', 'Tiến Độ Xử Lý Báo Cáo')

@section('content')
<main class="mx-auto w-full max-w-2xl flex-1 space-y-5 px-4 py-10">
    <header>
        <h1 class="text-2xl font-extrabold text-emerald-800">Tiến độ xử lý báo cáo</h1>
        <p class="mt-2 text-sm text-slate-600">Mã báo cáo: <strong class="font-mono">{{ $baoCao->maBaoCao }}</strong></p>
    </header>
    <section class="space-y-3 rounded-2xl border border-slate-200 bg-white p-5 text-sm">
        <p>Trạng thái: <strong>{{ $baoCao->trangThai->value }}</strong></p>
        <p>Mã lô: <strong class="font-mono">{{ $baoCao->loThuoc->maLoNghiepVu }}</strong></p>
        <p>Ngày gửi: {{ $baoCao->ngayGui->format('d/m/Y') }}</p>
        @if ($baoCao->ketQuaXuLy)
            <p>Kết quả xử lý: <strong>{{ $baoCao->ketQuaXuLy->quyetDinh }}</strong></p>
            @if ($baoCao->ketQuaXuLy->lyDoXuLy)
                <p>Kết luận: {{ $baoCao->ketQuaXuLy->lyDoXuLy }}</p>
            @endif
        @endif
    </section>
    <a href="{{ route('cong.baoCao.gui') }}" class="inline-block text-xs font-bold text-cyan-800">Quay lại gửi báo cáo / kiểm tra mã khác</a>
</main>
@endsection

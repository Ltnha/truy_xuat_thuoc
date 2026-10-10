@extends('layouts.app')
@section('title', $huong === 'di' ? 'Chuyển Giao Lô Hàng' : 'Lô Hàng Đến')
@section('panelTitle', $huong === 'di' ? 'Gửi Chuyển Giao' : 'Tiếp Nhận Lô Hàng')

@section('content')
@if ($huong === 'di')
    <section class="rounded-2xl border border-emerald-900/10 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-bold text-slate-800">Tạo yêu cầu chuyển giao</h2>
        <p class="mt-1 text-xs text-slate-500">Số lượng được giữ chỗ khi chờ xác nhận; tồn kho chỉ chuyển sang đơn vị nhận sau khi họ tiếp nhận.</p>
        <form method="POST" action="{{ route('chung.chuyenGiao.di') }}" class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-3">
            @csrf
            <label class="text-xs font-semibold">Lô thuốc
                <select name="loThuocId" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Chọn lô</option>
                    @foreach ($loThuocs as $loThuoc)
                        <option value="{{ $loThuoc->id }}" @selected(old('loThuocId') == $loThuoc->id)>
                            {{ $loThuoc->maLoNghiepVu }} · {{ $loThuoc->sanPham->tenSanPham }} · khả dụng {{ number_format($loThuoc->soLuongKhaDung) }}
                        </option>
                    @endforeach
                </select>
            </label>
            <label class="text-xs font-semibold">Đơn vị nhận
                <select name="benNhanId" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Chọn tổ chức</option>
                    @foreach ($doiTacs as $doiTac)
                        <option value="{{ $doiTac->id }}" @selected(old('benNhanId') == $doiTac->id)>{{ $doiTac->tenToChuc }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-xs font-semibold">Số lượng
                <input type="number" min="1" name="soLuong" value="{{ old('soLuong') }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </label>
            <div class="md:col-span-3"><button class="rounded-lg bg-cyan-800 px-4 py-2 text-xs font-bold text-white hover:bg-cyan-900">Gửi yêu cầu</button></div>
        </form>
    </section>
@endif

<section class="mt-6 overflow-hidden rounded-2xl border border-emerald-900/10 bg-white shadow-sm">
    <div class="border-b bg-slate-50 px-4 py-3 text-xs font-bold text-slate-700">
        {{ $huong === 'di' ? 'Lịch sử giao dịch đã gửi' : 'Giao dịch gửi đến tổ chức của bạn' }}
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-white text-[11px] uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Mã giao dịch / lô</th>
                    <th class="px-4 py-3">Sản phẩm</th>
                    <th class="px-4 py-3">{{ $huong === 'di' ? 'Bên nhận' : 'Bên gửi' }}</th>
                    <th class="px-4 py-3">Số lượng</th>
                    <th class="px-4 py-3">Khởi tạo</th>
                    <th class="px-4 py-3">Trạng thái</th>
                    @if ($huong === 'den')<th class="px-4 py-3">Thao tác</th>@endif
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($giaoDich as $item)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-mono font-bold">{{ $item->maChuyenGiao }}</div>
                            <div class="mt-1 text-slate-500">{{ $item->loThuoc->maLoNghiepVu }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $item->loThuoc->sanPham->tenSanPham }}</td>
                        <td class="px-4 py-3">{{ $huong === 'di' ? $item->benNhan->tenToChuc : $item->benGui->tenToChuc }}</td>
                        <td class="px-4 py-3">{{ number_format($item->soLuong) }}</td>
                        <td class="px-4 py-3">{{ $item->thoiGianKhoiTao?->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">{{ $item->trangThai->value }}</td>
                        @if ($huong === 'den')
                            <td class="px-4 py-3">
                                @if ($item->trangThai->value === 'PENDING')
                                    <form method="POST" action="{{ route('chung.chuyenGiao.nhan', $item) }}" onsubmit="return confirm('Xác nhận tiếp nhận và cập nhật tồn kho?')">
                                        @csrf
                                        @method('PATCH')
                                        <button class="rounded bg-emerald-700 px-3 py-1.5 font-bold text-white">Xác nhận nhận</button>
                                    </form>
                                @else
                                    <span class="text-slate-400">Đã xử lý</span>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $huong === 'di' ? 6 : 7 }}" class="px-4 py-8 text-center text-slate-500">Không có giao dịch chuyển giao.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="border-t p-4">{{ $giaoDich->links() }}</div>
</section>
@endsection

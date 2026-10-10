@extends('layouts.app')
@section('title', 'Quản Lý Lô Thuốc')
@section('panelTitle', 'Lô Thuốc & Truy Xuất')

@section('content')
<section class="rounded-2xl border border-emerald-900/10 bg-white p-5 shadow-sm">
    <h2 class="text-sm font-bold text-slate-800">Đăng ký lô thuốc</h2>
    <p class="mt-1 text-xs text-slate-500">Chỉ sản phẩm đã được duyệt của tổ chức hiện tại mới được dùng. Khi đăng ký, hệ thống tạo mã đơn vị/QR, cập nhật tồn kho và ghi nhật ký.</p>
    <form method="POST" action="{{ route('nsx.loThuoc.store') }}" class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
        @csrf
        <label class="text-xs font-semibold">Sản phẩm đã duyệt
            <select name="sanPhamId" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Chọn sản phẩm</option>
                @foreach ($sanPhams as $sanPham)
                    <option value="{{ $sanPham->id }}" @selected(old('sanPhamId') == $sanPham->id)>{{ $sanPham->tenSanPham }} · {{ $sanPham->soDangKy }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-xs font-semibold">Mã lô nghiệp vụ
            <input name="maLoNghiepVu" value="{{ old('maLoNghiepVu') }}" required maxlength="50" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </label>
        <label class="text-xs font-semibold">Số lượng
            <input name="soLuong" type="number" min="1" required value="{{ old('soLuong') }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </label>
        <div class="grid grid-cols-2 gap-3">
            <label class="text-xs font-semibold">Ngày sản xuất
                <input name="ngaySanXuat" type="date" required value="{{ old('ngaySanXuat') }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </label>
            <label class="text-xs font-semibold">Hạn sử dụng
                <input name="hanSuDung" type="date" required value="{{ old('hanSuDung') }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </label>
        </div>
        <div class="md:col-span-2"><button class="rounded-lg bg-emerald-700 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-800">Đăng ký lô thuốc</button></div>
    </form>
</section>

<section class="mt-6 overflow-hidden rounded-2xl border border-emerald-900/10 bg-white shadow-sm">
    <div class="border-b bg-slate-50 px-4 py-3 text-xs font-bold text-slate-700">Lô thuốc của tổ chức</div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-white text-[11px] uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Mã lô</th><th class="px-4 py-3">Sản phẩm</th><th class="px-4 py-3">Số lượng</th>
                    <th class="px-4 py-3">Ngày sản xuất</th><th class="px-4 py-3">Hạn sử dụng</th><th class="px-4 py-3">Trạng thái</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($loThuocs as $loThuoc)
                    <tr>
                        <td class="px-4 py-3 font-mono font-bold">{{ $loThuoc->maLoNghiepVu }}</td>
                        <td class="px-4 py-3">{{ $loThuoc->sanPham->tenSanPham }}</td>
                        <td class="px-4 py-3">{{ number_format($loThuoc->soLuong) }}</td>
                        <td class="px-4 py-3">{{ $loThuoc->ngaySanXuat->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">{{ $loThuoc->hanSuDung->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">{{ $loThuoc->trangThai->value }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Chưa có lô thuốc.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="border-t p-4">{{ $loThuocs->links() }}</div>
</section>
@endsection

@extends('layouts.app')
@section('title', 'Quản Lý Tài Khoản')
@section('panelTitle', 'Quản Lý Tài Khoản & Kiểm Soát Truy Cập')

@section('content')
<div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-900">
    Tài khoản có liên kết nhật ký hoặc nghiệp vụ không bị xóa vật lý; quản trị viên có thể khóa hoặc mở khóa và bắt buộc ghi lý do.
</div>
<section class="overflow-hidden rounded-2xl border border-emerald-900/10 bg-white shadow-sm">
    <div class="border-b bg-slate-50 px-4 py-3 text-xs font-bold text-slate-700">Tài khoản toàn hệ thống · {{ $taiKhoans->total() }} tài khoản</div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-white text-[11px] uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Tên đăng nhập / email</th>
                    <th class="px-4 py-3">Vai trò / tổ chức</th>
                    <th class="px-4 py-3">Ngày tạo</th>
                    <th class="px-4 py-3">Trạng thái</th>
                    <th class="px-4 py-3">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($taiKhoans as $taiKhoan)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-mono font-bold">{{ $taiKhoan->tenDangNhap }}</div>
                            <div class="mt-1 text-slate-500">{{ $taiKhoan->email ?: '—' }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div>{{ $taiKhoan->vaiTro->value }}</div>
                            <div class="mt-1 text-slate-500">{{ $taiKhoan->toChuc?->tenToChuc ?? 'Tài khoản hệ thống' }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $taiKhoan->ngayTao }}</td>
                        <td class="px-4 py-3"><span class="rounded-full bg-slate-100 px-2 py-1 font-bold">{{ $taiKhoan->trangThai->value }}</span></td>
                        <td class="px-4 py-3">
                            @if ($taiKhoan->id === auth()->id())
                                <span class="text-slate-400">Tài khoản hiện tại</span>
                            @elseif (in_array($taiKhoan->trangThai->value, ['ACTIVE', 'LOCKED'], true))
                                <form method="POST" action="{{ route('admin.taiKhoan.updateStatus', $taiKhoan) }}" class="flex min-w-56 flex-col gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <input name="lyDo" required maxlength="500" placeholder="Lý do thay đổi trạng thái" class="rounded border px-2 py-1.5">
                                    <button name="trangThai" value="{{ $taiKhoan->trangThai->value === 'ACTIVE' ? 'LOCKED' : 'ACTIVE' }}" class="rounded px-3 py-1.5 font-bold text-white {{ $taiKhoan->trangThai->value === 'ACTIVE' ? 'bg-red-600' : 'bg-emerald-700' }}">
                                        {{ $taiKhoan->trangThai->value === 'ACTIVE' ? 'Khóa tài khoản' : 'Mở khóa tài khoản' }}
                                    </button>
                                </form>
                            @else
                                <span class="text-slate-400">Chỉ tài khoản ACTIVE/LOCKED mới đổi trạng thái</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">Chưa có tài khoản nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="border-t p-4">{{ $taiKhoans->links() }}</div>
</section>
@endsection

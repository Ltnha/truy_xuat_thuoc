@extends('layoutDungChung.master')

@section('title', 'Quản lý tài khoản - PharmaChain')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/quanTriVien/quanLyTaiKhoan.css') }}">
@endpush

@section('content')
<div class="space-y-6 max-w-7xl w-full mx-auto">

    @if (session('thanhCong'))
    <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-xs font-bold text-emerald-800 flex items-center gap-2 shadow-sm">
        <i class="fa-solid fa-circle-check text-emerald-600"></i>
        <span>{{ session('thanhCong') }}</span>
    </div>
    @endif
    @if (session('loi'))
    <div class="p-4 bg-red-50 border border-red-200 rounded-2xl text-xs font-bold text-red-800 flex items-center gap-2 shadow-sm">
        <i class="fa-solid fa-triangle-exclamation text-red-600"></i>
        <span>{{ session('loi') }}</span>
    </div>
    @endif

    <!-- BỘ LỌC TÀI KHOẢN -->
    <form method="GET" action="{{ route('quan-tri.tai-khoan') }}" class="bg-white p-5 rounded-2xl border border-emerald-900/10 bong-xanh">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 text-xs pointer-events-none">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" name="tuKhoa" value="{{ $tuKhoa ?? '' }}" placeholder="Tìm theo tên đăng nhập, email, tổ chức..." class="w-full pl-9 pr-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-medium outline-none">
            </div>

            <select name="vaiTro" onchange="this.form.submit()" class="px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-medium text-slate-700 outline-none cursor-pointer">
                <option value="TAT_CA" {{ ($vaiTro ?? 'TAT_CA') === 'TAT_CA' ? 'selected' : '' }}>-- Tất cả vai trò --</option>
                <option value="QUAN_TRI_VIEN" {{ ($vaiTro ?? '') === 'QUAN_TRI_VIEN' ? 'selected' : '' }}>Quản trị viên</option>
                <option value="CO_QUAN_QUAN_LY" {{ ($vaiTro ?? '') === 'CO_QUAN_QUAN_LY' ? 'selected' : '' }}>Cơ quan quản lý</option>
                <option value="NHA_SAN_XUAT" {{ ($vaiTro ?? '') === 'NHA_SAN_XUAT' ? 'selected' : '' }}>Nhà sản xuất</option>
                <option value="NHA_PHAN_PHOI" {{ ($vaiTro ?? '') === 'NHA_PHAN_PHOI' ? 'selected' : '' }}>Nhà phân phối</option>
                <option value="NHA_THUOC" {{ ($vaiTro ?? '') === 'NHA_THUOC' ? 'selected' : '' }}>Nhà thuốc</option>
            </select>

            <select name="trangThai" onchange="this.form.submit()" class="px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-medium text-slate-700 outline-none cursor-pointer">
                <option value="TAT_CA" {{ ($trangThai ?? 'TAT_CA') === 'TAT_CA' ? 'selected' : '' }}>-- Tất cả trạng thái --</option>
                <option value="ACTIVE" {{ ($trangThai ?? '') === 'ACTIVE' ? 'selected' : '' }}>Hoạt động</option>
                <option value="LOCKED" {{ ($trangThai ?? '') === 'LOCKED' ? 'selected' : '' }}>Đã khóa</option>
                <option value="PENDING" {{ ($trangThai ?? '') === 'PENDING' ? 'selected' : '' }}>Chờ xác minh</option>
            </select>
        </div>
    </form>

    <!-- BẢNG DANH SÁCH -->
    <div class="bg-white rounded-2xl border border-emerald-900/10 bong-xanh overflow-hidden">
        <div class="p-4 bg-nenChinh border-b border-emerald-900/10 flex items-center justify-between text-xs">
            <span class="font-bold text-slate-700 uppercase tracking-wide flex items-center gap-2">
                <i class="fa-solid fa-users-gear text-slate-700"></i> Danh Sách Tài Khoản Toàn Mạng Lưới
            </span>
            <span class="font-bold text-slate-500 font-mono">Hiển thị: {{ $taiKhoans->total() }} tài khoản</span>
        </div>

        <div class="w-full overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 uppercase tracking-wider border-b border-slate-100 text-[11px]">
                        <th class="py-3.5 px-3 font-bold">Tên Đăng Nhập</th>
                        <th class="py-3.5 px-3 font-bold">Email</th>
                        <th class="py-3.5 px-2.5 font-bold text-center">Vai Trò</th>
                        <th class="py-3.5 px-3 font-bold">Tổ Chức</th>
                        <th class="py-3.5 px-2.5 font-bold text-center">Trạng Thái</th>
                        <th class="py-3.5 px-3 font-bold text-center">Hành Động</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse ($taiKhoans as $tk)
                    @php
                    $stVal = is_object($tk->trangThai) ? $tk->trangThai->value :$tk->trangThai;
                    $vtVal = is_object($tk->vaiTro) ? $tk->vaiTro->value :$tk->vaiTro;
                    @endphp
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-3 font-mono font-bold text-slate-900">
                            {{ $tk->tenDangNhap }}
                            @if($tk->id === Auth::id())
                            <span class="text-[10px] text-cyan-800 bg-cyan-50 px-1 rounded border border-cyan-200 ml-1">(Tôi)</span>
                            @endif
                        </td>
                        <td class="py-3 px-3 text-slate-600">{{ $tk->email }}</td>
                        <td class="py-3 px-2.5 text-center">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold border bg-slate-100 border-slate-300">
                                {{ $vtVal }}
                            </span>
                        </td>
                        <td class="py-3 px-3 font-semibold text-slate-800">{{ $tk->toChuc?->tenToChuc ?? '—' }}</td>
                        <td class="py-3 px-2.5 text-center">
                            @if($stVal === 'ACTIVE')
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300"><i class="fa-solid fa-circle-check"></i> Hoạt động</span>
                            @elseif($stVal === 'LOCKED')
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-red-100 text-red-700 border border-red-300"><i class="fa-solid fa-lock"></i> Đã khóa</span>
                            @else
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300"><i class="fa-solid fa-clock"></i> {{ $stVal }}</span>
                            @endif
                        </td>
                        <td class="py-3 px-3 text-center">
                            @if($tk->id !== Auth::id())
                            @if($stVal === 'ACTIVE')
                            <button type="button" onclick="moModalKhoa({{ $tk->id }}, '{{$tk->tenDangNhap }}')" class="px-2.5 py-1 bg-red-50 hover:bg-red-100 text-red-600 font-bold rounded-lg border border-red-200 text-xs cursor-pointer"><i class="fa-solid fa-lock text-[10px]"></i> Khóa</button>
                            @elseif($stVal === 'LOCKED')
                            <button type="button" onclick="moModalMoKhoa({{ $tk->id }}, '{{$tk->tenDangNhap }}')" class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold rounded-lg border border-emerald-300 text-xs cursor-pointer"><i class="fa-solid fa-lock-open text-[10px]"></i> Mở khóa</button>
                            @endif
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-8 text-slate-400 italic">Không tìm thấy tài khoản nào.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100 flex items-center justify-between text-xs">
            {{ $taiKhoans->links() }}
        </div>
    </div>
</div>

<!-- MODAL KHÓA -->
<div id="modalKhoa" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 bong-xanh">
        <h3 class="text-base font-bold text-slate-900"><i class="fa-solid fa-user-lock text-red-600"></i> Khóa Tài Khoản: <span id="tenTkKhoa" class="font-mono text-red-600"></span></h3>
        <form id="formKhoa" method="POST" action="" class="space-y-3 text-xs">
            @csrf
            <div class="space-y-1">
                <label for="lyDoKhoa" class="font-bold text-slate-700 block">Lý do khóa <span class="text-red-500">*</span></label>
                <textarea id="lyDoKhoa" name="lyDo" required rows="3" placeholder="Nhập chi tiết lý do khóa tài khoản..." class="w-full p-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl font-medium outline-none"></textarea>
            </div>
            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button" onclick="dongModals()" class="px-4 py-2 bg-slate-100 text-slate-700 font-bold rounded-xl cursor-pointer">Hủy</button>
                <button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl cursor-pointer">Xác nhận khóa</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL MỞ KHÓA -->
<div id="modalMoKhoa" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 bong-xanh">
        <h3 class="text-base font-bold text-slate-900"><i class="fa-solid fa-lock-open text-emerald-700"></i> Mở Khóa Tài Khoản: <span id="tenTkMoKhoa" class="font-mono text-emerald-700"></span></h3>
        <form id="formMoKhoa" method="POST" action="" class="space-y-3 text-xs">
            @csrf
            <div class="space-y-1">
                <label for="lyDoMoKhoa" class="font-bold text-slate-700 block">Lý do mở khóa <span class="text-red-500">*</span></label>
                <textarea id="lyDoMoKhoa" name="lyDo" required rows="3" placeholder="Nhập lý do mở khóa tài khoản..." class="w-full p-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl font-medium outline-none"></textarea>
            </div>
            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button" onclick="dongModals()" class="px-4 py-2 bg-slate-100 text-slate-700 font-bold rounded-xl cursor-pointer">Hủy</button>
                <button type="submit" class="px-5 py-2 bg-emerald-700 hover:bg-emerald-800 text-white font-bold rounded-xl cursor-pointer">Xác nhận mở khóa</button>
            </div>
        </form>
    </div>
</div>

<script>
    function dongModals() {
        document.getElementById('modalKhoa').classList.add('hidden');
        document.getElementById('modalMoKhoa').classList.add('hidden');
    }

    function moModalKhoa(id, ten) {
        document.getElementById('tenTkKhoa').textContent = ten;
        document.getElementById('formKhoa').action = `/quan-tri/tai-khoan/${id}/khoa`;
        document.getElementById('modalKhoa').classList.remove('hidden');
    }

    function moModalMoKhoa(id, ten) {
        document.getElementById('tenTkMoKhoa').textContent = ten;
        document.getElementById('formMoKhoa').action = `/quan-tri/tai-khoan/${id}/mo-khoa`;
        document.getElementById('modalMoKhoa').classList.remove('hidden');
    }
</script>
@endsection
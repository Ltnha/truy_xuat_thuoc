@extends('layoutDungChung.master')

@section('title', 'Nhật ký hệ thống - PharmaChain')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/quanTriVien/nhatKyHeThong.css') }}">
@endpush

@section('content')
<div class="space-y-6 max-w-7xl w-full mx-auto">

    <!-- BỘ LỌC NHẬT KÝ -->
    <form method="GET" action="{{ route('quan-tri.nhat-ky') }}" class="bg-white p-5 rounded-2xl border border-emerald-900/10 bong-xanh space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div>
                <label for="khoangThoiGian" class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Thời gian</label>
                <select name="khoangThoiGian" id="khoangThoiGian" onchange="toggleTuyChonNgay(this.value)" class="w-full px-3 py-2 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-semibold text-slate-700 outline-none cursor-pointer">
                    <option value="7_NGAY" {{ $khoangThoiGian === '7_NGAY' ? 'selected' : '' }}>7 ngày gần nhất</option>
                    <option value="HOM_NAY" {{ $khoangThoiGian === 'HOM_NAY' ? 'selected' : '' }}>Hôm nay</option>
                    <option value="30_NGAY" {{ $khoangThoiGian === '30_NGAY' ? 'selected' : '' }}>30 ngày gần nhất</option>
                    <option value="TUY_CHON" {{ $khoangThoiGian === 'TUY_CHON' ? 'selected' : '' }}>Tùy chọn khoảng ngày...</option>
                    <option value="TAT_CA" {{ $khoangThoiGian === 'TAT_CA' ? 'selected' : '' }}>Toàn bộ thời gian</option>
                </select>
            </div>

            <div>
                <label for="hanhDong" class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Loại hành động</label>
                <select name="hanhDong" onchange="this.form.submit()" class="w-full px-3 py-2 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-semibold text-slate-700 outline-none cursor-pointer">
                    <option value="TAT_CA" {{ $hanhDong === 'TAT_CA' ? 'selected' : '' }}>-- Tất cả hành động --</option>
                    <option value="DANG_NHAP" {{ $hanhDong === 'DANG_NHAP' ? 'selected' : '' }}>Đăng nhập</option>
                    <option value="DANG_NHAP_THAT_BAI" {{ $hanhDong === 'DANG_NHAP_THAT_BAI' ? 'selected' : '' }}>Đăng nhập thất bại</option>
                    <option value="KHOA_TAI_KHOAN" {{ $hanhDong === 'KHOA_TAI_KHOAN' ? 'selected' : '' }}>Khóa tài khoản</option>
                    <option value="MO_KHOA_TAI_KHOAN" {{ $hanhDong === 'MO_KHOA_TAI_KHOAN' ? 'selected' : '' }}>Mở khóa</option>
                    <option value="DOI_MAT_KHAU" {{ $hanhDong === 'DOI_MAT_KHAU' ? 'selected' : '' }}>Đổi mật khẩu</option>
                    <option value="GIAO_DICH_TU_CHOI" {{ $hanhDong === 'GIAO_DICH_TU_CHOI' ? 'selected' : '' }}>Giao dịch bị từ chối</option>
                    <option value="LOI_HE_THONG" {{ $hanhDong === 'LOI_HE_THONG' ? 'selected' : '' }}>Lỗi hệ thống / Blockchain</option>
                </select>
            </div>

            <div>
                <label for="ketQua" class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Kết quả</label>
                <select name="ketQua" onchange="this.form.submit()" class="w-full px-3 py-2 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-semibold text-slate-700 outline-none cursor-pointer">
                    <option value="TAT_CA" {{ $ketQua === 'TAT_CA' ? 'selected' : '' }}>-- Tất cả kết quả --</option>
                    <option value="THANH_CONG" {{ $ketQua === 'THANH_CONG' ? 'selected' : '' }}>Thành công</option>
                    <option value="BI_TU_CHOI" {{ $ketQua === 'BI_TU_CHOI' ? 'selected' : '' }}>Bị từ chối</option>
                    <option value="LOI" {{ $ketQua === 'LOI' ? 'selected' : '' }}>Lỗi</option>
                </select>
            </div>

            <div>
                <label for="tuKhoa" class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Từ khóa tìm kiếm</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 text-xs pointer-events-none">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" name="tuKhoa" value="{{ $tuKhoa }}" placeholder="Tìm tên đăng nhập, chi tiết..." class="w-full pl-8 pr-3 py-2 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-medium outline-none">
                </div>
            </div>
        </div>

        <div id="khungNgayTuyChon" class="{{ $khoangThoiGian === 'TUY_CHON' ? '' : 'hidden' }} p-3 bg-nenChinh rounded-xl border border-emerald-900/15 flex flex-wrap items-center gap-3 text-xs">
            <span class="font-bold text-slate-700 flex items-center gap-1.5"><i class="fa-regular fa-calendar-days text-slate-600"></i> Chọn khoảng ngày:</span>
            <div class="flex items-center gap-1.5"><span class="text-slate-500">Từ ngày:</span><input type="date" name="tuNgay" value="{{ $tuNgay }}" class="px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-mono"></div>
            <div class="flex items-center gap-1.5"><span class="text-slate-500">Đến ngày:</span><input type="date" name="denNgay" value="{{ $denNgay }}" class="px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-mono"></div>
            <button type="submit" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-900 text-white rounded-lg font-bold text-[11px]">Áp Dụng</button>
        </div>
    </form>

    <!-- BẢNG NHẬT KÝ HỆ THỐNG -->
    <div class="bg-white rounded-2xl border border-emerald-900/10 bong-xanh overflow-hidden">
        <div class="p-4 bg-nenChinh border-b border-emerald-900/10 flex items-center justify-between text-xs">
            <span class="font-bold text-slate-700 uppercase tracking-wide flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-slate-700"></i> Nhật Ký Hoạt Động & Kiểm Toán Hệ Thống (Audit Logs)
            </span>
            <span class="font-bold text-slate-500 font-mono">Hiển thị: {{ $nhatKys->total() }} bản ghi</span>
        </div>

        <div class="w-full overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 uppercase tracking-wider border-b border-slate-100 text-[11px]">
                        <th class="py-3.5 px-3 font-bold whitespace-nowrap">Thời Gian</th>
                        <th class="py-3.5 px-3 font-bold whitespace-nowrap">Tài Khoản Thao Tác</th>
                        <th class="py-3.5 px-3 font-bold whitespace-nowrap">Tài Khoản Tác Động</th>
                        <th class="py-3.5 px-3 font-bold whitespace-nowrap">Hành Động</th>
                        <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Kết Quả</th>
                        <th class="py-3.5 px-3 font-bold">Chi Tiết Tóm Tắt</th>
                        <th class="py-3.5 px-3 font-bold text-center whitespace-nowrap">Chi Tiết</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse ($nhatKys as $log)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-3 font-mono text-[11px] text-slate-600 whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($log->thoiGian)->format('d/m/Y H:i:s') }}
                        </td>
                        <td class="py-3 px-3 font-mono font-bold text-slate-900 whitespace-nowrap">
                            {{ $log->taiKhoan?->tenDangNhap ?? '— (Hệ thống)' }}
                        </td>
                        <td class="py-3 px-3 font-mono text-slate-700 whitespace-nowrap">
                            {{ $log->doiTuong?->tenDangNhap ?? '—' }}
                        </td>
                        <td class="py-3 px-3 font-semibold text-slate-800 whitespace-nowrap">
                            @switch($log->hanhDong)
                            @case('DANG_NHAP') Đăng nhập @break
                            @case('DANG_NHAP_THAT_BAI') Đăng nhập thất bại @break
                            @case('KHOA_TAI_KHOAN') Khóa tài khoản @break
                            @case('MO_KHOA_TAI_KHOAN') Mở khóa @break
                            @case('DOI_MAT_KHAU') Đổi mật khẩu @break
                            @case('GIAO_DICH_TU_CHOI') Giao dịch bị từ chối @break
                            @default {{ $log->hanhDong }}
                            @endswitch
                        </td>
                        <td class="py-3 px-2.5 text-center whitespace-nowrap">
                            @if($log->ketQua === 'THANH_CONG')
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300"><i class="fa-solid fa-circle-check"></i> Thành công</span>
                            @elseif($log->ketQua === 'BI_TU_CHOI')
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-red-100 text-red-700 border border-red-300"><i class="fa-solid fa-ban"></i> Bị từ chối</span>
                            @else
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300"><i class="fa-solid fa-triangle-exclamation"></i> Lỗi</span>
                            @endif
                        </td>
                        <td class="py-3 px-3 text-slate-600 truncate max-w-[280px]" title="{{ $log->chiTiet }}">{{ $log->chiTiet }}</td>
                        <td class="py-3 px-3 text-center whitespace-nowrap">
                            <button type="button" class="btn-xem-log px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs border border-slate-300 cursor-pointer"
                                data-id="LOG-{{ $log->id }}"
                                data-thoigian="{{ \Carbon\Carbon::parse($log->thoiGian)->format('d/m/Y H:i:s') }}"
                                data-taikhoan="{{ $log->taiKhoan?->tenDangNhap ?? '—' }}"
                                data-doituong="{{ $log->doiTuong?->tenDangNhap ?? '—' }}"
                                data-mota="{{ $log->chiTiet }}">
                                <i class="fa-solid fa-eye text-[10px]"></i> Xem
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-8 text-slate-400 italic">Không tìm thấy bản ghi nhật ký nào.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100 flex items-center justify-between text-xs">
            {{ $nhatKys->links() }}
        </div>
    </div>
</div>

<!-- MODAL CHI TIẾT NHẬT KÝ -->
<div id="modalChiTietLog" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 space-y-4 bong-xanh">
        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
            <span class="text-base font-bold text-slate-900">Chi Tiết Nhật Ký: <span id="modalLogId" class="font-mono text-slate-700 bg-slate-100 px-2 py-0.5 rounded text-xs"></span></span>
            <button type="button" onclick="dongModalLog()" class="text-slate-400 hover:text-slate-700 text-lg cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="space-y-3 text-xs">
            <div class="p-3 bg-nenChinh rounded-xl border border-emerald-900/10 grid grid-cols-2 gap-2">
                <div><span class="text-slate-500 block">Thời gian:</span><span id="modalLogThoiGian" class="font-mono font-bold"></span></div>
                <div><span class="text-slate-500 block">Tài khoản thao tác:</span><span id="modalLogTaiKhoan" class="font-bold"></span></div>
                <div class="col-span-2"><span class="text-slate-500 block">Tài khoản bị tác động:</span><span id="modalLogDoiTuong" class="font-mono font-bold text-indigo-900"></span></div>
            </div>
            <div class="space-y-1">
                <span class="font-bold text-slate-700 block">Nội dung sự kiện:</span>
                <div id="modalLogMoTa" class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-slate-800 leading-relaxed"></div>
            </div>
        </div>
        <div class="pt-2 border-t border-slate-100 flex justify-end">
            <button type="button" onclick="dongModalLog()" class="px-5 py-2 bg-slate-100 text-slate-700 text-xs font-bold rounded-xl cursor-pointer">Đóng</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function toggleTuyChonNgay(val) {
        document.getElementById('khungNgayTuyChon').classList.toggle('hidden', val !== 'TUY_CHON');
        if (val !== 'TUY_CHON') document.getElementById('khoangThoiGian').form.submit();
    }

    function dongModalLog() {
        document.getElementById('modalChiTietLog').classList.add('hidden');
    }
    document.querySelectorAll('.btn-xem-log').forEach(b => {
        b.addEventListener('click', function() {
            document.getElementById('modalLogId').textContent = this.dataset.id;
            document.getElementById('modalLogThoiGian').textContent = this.dataset.thoigian;
            document.getElementById('modalLogTaiKhoan').textContent = this.dataset.taikhoan;
            document.getElementById('modalLogDoiTuong').textContent = this.dataset.doituong;
            document.getElementById('modalLogMoTa').textContent = this.dataset.mota;
            document.getElementById('modalChiTietLog').classList.remove('hidden');
        });
    });
</script>
@endpush
@endsection
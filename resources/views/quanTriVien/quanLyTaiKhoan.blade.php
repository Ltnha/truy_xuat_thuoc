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
                <i class="fa-solid fa-users-gear text-slate-700"></i> DANH SÁCH TÀI KHOẢN NGƯỜI DÙNG TOÀN MẠNG LƯỚI
            </span>
            <span class="font-bold text-slate-500 font-mono">Hiển thị: {{ $taiKhoans->count() }} / {{$taiKhoans->total() }} tài khoản</span>
        </div>

        <div class="w-full overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 uppercase tracking-wider border-b border-slate-100 text-[11px]">
                        <th class="py-3.5 px-3 font-bold whitespace-nowrap">Tên Đăng Nhập</th>
                        <th class="py-3.5 px-3 font-bold whitespace-nowrap">Email</th>
                        <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Vai Trò</th>
                        <th class="py-3.5 px-3 font-bold whitespace-nowrap">Tổ Chức</th>
                        <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Trạng Thái Tài Khoản</th>
                        <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Trạng Thái Tổ Chức</th>
                        <th class="py-3.5 px-3 font-bold text-center whitespace-nowrap">Hành Động</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse ($taiKhoans as $tk)
                    @php
                    $stVal = is_object($tk->trangThai) ? $tk->trangThai->value :$tk->trangThai;
                    $vtVal = is_object($tk->vaiTro) ? $tk->vaiTro->value :$tk->vaiTro;

                    // Ánh xạ vai trò sang Tiếng Việt & CSS
                    $vtInfo = match($vtVal) {
                    'NHA_SAN_XUAT' => ['ten' => 'Nhà sản xuất', 'class' => 'bg-emerald-50 text-emerald-800 border-emerald-300'],
                    'NHA_PHAN_PHOI' => ['ten' => 'Nhà phân phối', 'class' => 'bg-blue-50 text-blue-800 border-blue-300'],
                    'NHA_THUOC' => ['ten' => 'Nhà thuốc', 'class' => 'bg-cyan-50 text-cyan-800 border-cyan-300'],
                    'CO_QUAN_QUAN_LY' => ['ten' => 'Cơ quan quản lý', 'class' => 'bg-indigo-50 text-indigo-800 border-indigo-300'],
                    'QUAN_TRI_VIEN' => ['ten' => 'Quản trị viên', 'class' => 'bg-slate-100 text-slate-800 border-slate-300'],
                    default => ['ten' => $vtVal, 'class' => 'bg-slate-100 text-slate-700 border-slate-200']
                    };

                    // Trạng thái tổ chức
                    $stToChuc = $tk->toChuc?->trangThaiDuyet ? (is_object($tk->toChuc->trangThaiDuyet) ? $tk->toChuc->trangThaiDuyet->value :$tk->toChuc->trangThaiDuyet) : null;

                    $chiTietData = [
                    'id' => $tk->id,
                    'tenDangNhap' => $tk->tenDangNhap,
                    'email' => $tk->email,
                    'vaiTro' => $vtInfo['ten'],
                    'tenToChuc' => $tk->toChuc?->tenToChuc ?? '—',
                    'trangThai' => $stVal,
                    'ngayTao' => $tk->ngayTao ? \Carbon\Carbon::parse($tk->ngayTao)->format('d/m/Y H:i') : '—',
                    'lyDoKhoa' => $tk->lyDoKhoa,
                    'nguoiKhoa' => $tk->nguoiKhoa?->tenDangNhap ?? 'Hệ thống',
                    'thoiGianKhoa' => $tk->thoiGianKhoa ? \Carbon\Carbon::parse($tk->thoiGianKhoa)->format('d/m/Y H:i') : null,
                    'nhatKys' => $tk->nhatKys->map(function($log) {
                    return [
                    'thoiGian' => \Carbon\Carbon::parse($log->thoiGian)->format('d/m/Y H:i'),
                    'hanhDong' => $log->hanhDong,
                    'chiTiet' => $log->chiTiet,
                    ];
                    })->toArray()
                    ];
                    @endphp
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-3 font-mono font-bold text-slate-900 whitespace-nowrap">
                            {{ $tk->tenDangNhap }}
                            @if($tk->id === Auth::id())
                            <span class="text-[10px] text-cyan-800 bg-cyan-50 px-1 rounded border border-cyan-200 ml-1">(Tôi)</span>
                            @endif
                        </td>
                        <td class="py-3 px-3 text-slate-600 whitespace-nowrap">
                            <span class="truncate block max-w-[140px]" title="{{ $tk->email }}">{{ $tk->email }}</span>
                        </td>
                        <td class="py-3 px-2.5 text-center whitespace-nowrap">
                            <span class="px-2.5 py-0.5 rounded-md text-[11px] font-bold border {{ $vtInfo['class'] }}">
                                {{ $vtInfo['ten'] }}
                            </span>
                        </td>
                        <td class="py-3 px-3 font-semibold text-slate-800 whitespace-nowrap">
                            @if($tk->toChuc?->tenToChuc)
                            <span class="truncate block max-w-[180px]" title="{{ $tk->toChuc->tenToChuc }}">{{ $tk->toChuc->tenToChuc }}</span>
                            @else
                            <span class="text-slate-400 font-mono font-bold block text-center">—</span>
                            @endif
                        </td>
                        <td class="py-3 px-2.5 text-center whitespace-nowrap">
                            @if($stVal === 'ACTIVE')
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300"><i class="fa-solid fa-circle-check"></i> Hoạt động</span>
                            @elseif($stVal === 'LOCKED')
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-red-100 text-red-700 border border-red-300"><i class="fa-solid fa-lock"></i> Đã khóa</span>
                            @else
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300"><i class="fa-solid fa-clock"></i> PENDING</span>
                            @endif
                        </td>
                        <td class="py-3 px-2.5 text-center whitespace-nowrap">
                            @if($stToChuc === 'DA_DUYET')
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Đã duyệt</span>
                            @elseif($stToChuc === 'CHO_DUYET' ||$stToChuc === 'PENDING')
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Chờ duyệt</span>
                            @elseif($stToChuc === 'BI_TU_CHOI')
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-red-50 text-red-700 border border-red-200">Bị từ chối</span>
                            @else
                            <span class="text-slate-400 font-mono font-bold block text-center">—</span>
                            @endif
                        </td>
                        <td class="py-3 px-3 text-center whitespace-nowrap">
                            <div class="inline-flex items-center justify-center gap-1.5">
                                @if($tk->id !== Auth::id())
                                @if($stVal === 'ACTIVE')
                                <button type="button" onclick="moModalKhoa({{ $tk->id }}, '{{$tk->tenDangNhap }}')" class="px-2.5 py-1 bg-red-50 hover:bg-red-100 text-red-600 font-bold rounded-lg border border-red-200 text-xs cursor-pointer"><i class="fa-solid fa-lock text-[10px]"></i> Khóa</button>
                                @elseif($stVal === 'LOCKED')
                                <button type="button" onclick="moModalMoKhoa({{ $tk->id }}, '{{$tk->tenDangNhap }}', '{{ addslashes($tk->lyDoKhoa ?? '') }}', '{{ $tk->nguoiKhoa?->tenDangNhap ?? 'Hệ thống' }}', '{{ $tk->thoiGianKhoa ? \Carbon\Carbon::parse($tk->thoiGianKhoa)->format('d/m/Y H:i') : '—' }}')" class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold rounded-lg border border-emerald-300 text-xs cursor-pointer"><i class="fa-solid fa-lock-open text-[10px]"></i> Mở khóa</button>
                                @endif
                                @endif
                                <button type="button" onclick='moModalChiTiet(@json($chiTietData))' class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg border border-slate-300 text-xs cursor-pointer">
                                    <i class="fa-solid fa-eye text-[10px]"></i> Chi tiết
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-8 text-slate-400 italic">Không tìm thấy tài khoản nào.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- CỤM PHÂN TRANG CHUẨN TIẾNG VIỆT CỦA BẢN DEMO -->
        <div class="p-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500 font-medium">
            <div>
                Đang hiển thị {{ $taiKhoans->firstItem() ?? 0 }} đến {{ $taiKhoans->lastItem() ?? 0 }} trên tổng số {{ $taiKhoans->total() }} tài khoản
            </div>
            <div class="flex items-center gap-1.5">
                {{-- Nút trang trước --}}
                @if ($taiKhoans->onFirstPage())
                <span class="w-7 h-7 rounded-lg border border-slate-200 text-slate-300 flex items-center justify-center text-xs cursor-not-allowed"><i class="fa-solid fa-angle-left"></i></span>
                @else
                <a href="{{ $taiKhoans->previousPageUrl() }}" class="w-7 h-7 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-100 flex items-center justify-center text-xs"><i class="fa-solid fa-angle-left"></i></a>
                @endif

                {{-- Các số trang --}}
                @foreach ($taiKhoans->getUrlRange(1,$taiKhoans->lastPage()) as $page =>$url)
                @if ($page ==$taiKhoans->currentPage())
                <span class="w-7 h-7 rounded-lg bg-slate-800 text-white font-bold flex items-center justify-center text-xs shadow-2xs">{{ $page }}</span>
                @else
                <a href="{{ $url }}" class="w-7 h-7 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-100 font-bold flex items-center justify-center text-xs">{{ $page }}</a>
                @endif
                @endforeach

                {{-- Nút trang sau --}}
                @if ($taiKhoans->hasMorePages())
                <a href="{{ $taiKhoans->nextPageUrl() }}" class="w-7 h-7 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-100 flex items-center justify-center text-xs"><i class="fa-solid fa-angle-right"></i></a>
                @else
                <span class="w-7 h-7 rounded-lg border border-slate-200 text-slate-300 flex items-center justify-center text-xs cursor-not-allowed"><i class="fa-solid fa-angle-right"></i></span>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- MODAL KHÓA TÀI KHOẢN -->
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

<!-- MODAL MỞ KHÓA TÀI KHOẢN -->
<div id="modalMoKhoa" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 bong-xanh">
        <h3 class="text-base font-bold text-slate-900"><i class="fa-solid fa-lock-open text-emerald-700"></i> Mở Khóa Tài Khoản: <span id="tenTkMoKhoa" class="font-mono text-emerald-700"></span></h3>

        <!-- THÔNG TIN LẦN KHÓA TRƯỚC -->
        <div class="p-3 bg-red-50 rounded-xl border border-red-200 text-xs space-y-1">
            <span class="font-bold text-red-800 block"><i class="fa-solid fa-circle-info"></i> Thông tin lần khóa trước:</span>
            <div class="text-slate-700">Lý do: <span id="moKhoaLyDoCu" class="font-medium"></span></div>
            <div class="text-slate-500 text-[11px]">Người khóa: <span id="moKhoaNguoiCu" class="font-mono font-bold"></span> | Thời gian: <span id="moKhoaThoiGianCu" class="font-mono"></span></div>
        </div>

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

<!-- MODAL XEM CHI TIẾT TÀI KHOẢN -->
<div id="modalChiTietTaiKhoan" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-2xl w-full p-6 space-y-4 bong-xanh max-h-[90vh] overflow-y-auto">
        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
            <span class="text-base font-bold text-slate-900">Chi Tiết Tài Khoản: <span id="detailTenDangNhapTitle" class="font-mono text-emerald-800"></span></span>
            <button type="button" onclick="dongModals()" class="text-slate-400 hover:text-slate-700 text-lg cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div class="space-y-4 text-xs">
            <!-- Khung thông tin chung -->
            <div class="p-3 bg-nenChinh rounded-xl border border-emerald-900/10 grid grid-cols-2 gap-3">
                <div><span class="text-slate-500 block">Tên đăng nhập:</span><span id="detailTenDangNhap" class="font-mono font-bold text-slate-900"></span></div>
                <div><span class="text-slate-500 block">Email:</span><span id="detailEmail" class="font-medium text-slate-800"></span></div>
                <div><span class="text-slate-500 block">Vai trò:</span><span id="detailVaiTro" class="font-bold"></span></div>
                <div><span class="text-slate-500 block">Trạng thái:</span><span id="detailTrangThai"></span></div>
                <div><span class="text-slate-500 block">Tổ chức liên kết:</span><span id="detailTenToChuc" class="font-bold text-slate-800"></span></div>
                <div><span class="text-slate-500 block">Ngày tạo:</span><span id="detailNgayTao" class="font-mono text-slate-700"></span></div>
            </div>

            <!-- Khối chi tiết khóa (chỉ hiện khi bị khóa) -->
            <div id="detailKhungKhoa" class="p-3 bg-red-50 rounded-xl border border-red-200 hidden space-y-1">
                <span class="font-bold text-red-800 block"><i class="fa-solid fa-lock text-red-600"></i> Thông tin khóa tài khoản:</span>
                <p class="text-slate-700">Lý do: <span id="detailLyDoKhoa" class="font-medium"></span></p>
                <p class="text-slate-500 text-[11px]">Người khóa: <span id="detailNguoiKhoa" class="font-mono font-bold"></span> | Thời gian: <span id="detailThoiGianKhoa" class="font-mono"></span></p>
            </div>

            <!-- Lịch sử nhật ký gần đây -->
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-slate-800 uppercase tracking-wide">Nhật ký hoạt động gần đây</span>
                    <a id="linkSangNhatKy" href="#" class="text-emerald-700 hover:underline text-[11px] font-bold"><i class="fa-solid fa-arrow-up-right-from-square"></i> Mở trang Nhật ký</a>
                </div>
                <div class="border border-slate-200 rounded-xl overflow-hidden">
                    <table class="w-full text-left border-collapse text-[11px]">
                        <thead class="bg-slate-50 text-slate-600 border-b border-slate-200">
                            <tr>
                                <th class="p-2 font-bold whitespace-nowrap">Thời gian</th>
                                <th class="p-2 font-bold whitespace-nowrap">Hành động</th>
                                <th class="p-2 font-bold">Chi tiết</th>
                            </tr>
                        </thead>
                        <tbody id="thanBangHoatDong" class="divide-y divide-slate-100">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="pt-3 border-t border-slate-100 flex justify-end">
            <button type="button" onclick="dongModals()" class="px-5 py-2 bg-slate-100 text-slate-700 text-xs font-bold rounded-xl cursor-pointer">Đóng</button>
        </div>
    </div>
</div>

<script>
    function dongModals() {
        document.getElementById('modalKhoa').classList.add('hidden');
        document.getElementById('modalMoKhoa').classList.add('hidden');
        document.getElementById('modalChiTietTaiKhoan').classList.add('hidden');
    }

    function moModalKhoa(id, ten) {
        document.getElementById('tenTkKhoa').textContent = ten;
        document.getElementById('formKhoa').action = `/quan-tri/tai-khoan/${id}/khoa`;
        document.getElementById('modalKhoa').classList.remove('hidden');
    }

    function moModalMoKhoa(id, ten, lyDo, nguoiKhoa, thoiGian) {
        document.getElementById('tenTkMoKhoa').textContent = ten;
        document.getElementById('moKhoaLyDoCu').textContent = lyDo || 'Không có';
        document.getElementById('moKhoaNguoiCu').textContent = nguoiKhoa || 'Hệ thống';
        document.getElementById('moKhoaThoiGianCu').textContent = thoiGian || '—';
        document.getElementById('formMoKhoa').action = `/quan-tri/tai-khoan/${id}/mo-khoa`;
        document.getElementById('modalMoKhoa').classList.remove('hidden');
    }

    function moModalChiTiet(data) {
        document.getElementById('detailTenDangNhapTitle').textContent = data.tenDangNhap;
        document.getElementById('detailTenDangNhap').textContent = data.tenDangNhap;
        document.getElementById('detailEmail').textContent = data.email;
        document.getElementById('detailVaiTro').textContent = data.vaiTro;
        document.getElementById('detailTrangThai').textContent = data.trangThai === 'ACTIVE' ? 'Hoạt động' : (data.trangThai === 'LOCKED' ? 'Đã khóa' : data.trangThai);
        document.getElementById('detailTenToChuc').textContent = data.tenToChuc;
        document.getElementById('detailNgayTao').textContent = data.ngayTao;

        const khungKhoa = document.getElementById('detailKhungKhoa');
        if (data.trangThai === 'LOCKED') {
            khungKhoa.classList.remove('hidden');
            document.getElementById('detailLyDoKhoa').textContent = data.lyDoKhoa || 'Khóa bảo mật';
            document.getElementById('detailNguoiKhoa').textContent = data.nguoiKhoa;
            document.getElementById('detailThoiGianKhoa').textContent = data.thoiGianKhoa || '—';
        } else {
            khungKhoa.classList.add('hidden');
        }

        const thanBang = document.getElementById('thanBangHoatDong');
        thanBang.innerHTML = '';
        if (data.nhatKys && data.nhatKys.length > 0) {
            data.nhatKys.forEach(log => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="p-2 font-mono text-slate-500 whitespace-nowrap">${log.thoiGian}</td>
                    <td class="p-2 font-bold text-slate-800 whitespace-nowrap">${log.hanhDong}</td>
                    <td class="p-2 text-slate-600">${log.chiTiet}</td>
                `;
                thanBang.appendChild(tr);
            });
        } else {
            thanBang.innerHTML = `<tr><td colspan="3" class="p-4 text-center text-slate-400 italic">Chưa có nhật ký hoạt động nào.</td></tr>`;
        }

        document.getElementById('linkSangNhatKy').href = `/quan-tri/nhat-ky?tuKhoa=${encodeURIComponent(data.tenDangNhap)}`;
        document.getElementById('modalChiTietTaiKhoan').classList.remove('hidden');
    }
</script>
@endsection
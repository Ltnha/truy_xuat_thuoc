@extends('layoutDungChung.master')

@section('title', 'Quản lý quyền hạn - PharmaChain')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/coQuanQuanLy/quanLyQuyenHan.css') }}">
@endpush

@section('content')
<div class="space-y-6 max-w-7xl w-full mx-auto">

    @if (session('thanhCong'))
    <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-xs font-bold text-emerald-800 flex items-center gap-2 shadow-sm">
        <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
        <span>{{ session('thanhCong') }}</span>
    </div>
    @endif

    <!-- BỘ LỌC DỮ LIỆU -->
    <form method="GET" action="{{ route('co-quan.quan-ly-quyen-han') }}" class="bg-white p-5 rounded-2xl border border-emerald-900/10 bong-xanh">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 text-xs pointer-events-none">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" name="tuKhoa" value="{{ $tuKhoa }}" placeholder="Tìm theo tên tổ chức, MST, GPKD..." class="w-full pl-9 pr-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-medium text-slate-900 focus:bg-white focus:border-indigo-700 outline-none">
            </div>

            <select name="loaiToChuc" onchange="this.form.submit()" class="px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:border-indigo-700 outline-none cursor-pointer">
                <option value="TAT_CA" {{ $loaiToChuc === 'TAT_CA' ? 'selected' : '' }}>-- Tất cả loại hình --</option>
                <option value="NHA_SAN_XUAT" {{ $loaiToChuc === 'NHA_SAN_XUAT' ? 'selected' : '' }}>Nhà sản xuất</option>
                <option value="NHA_PHAN_PHOI" {{ $loaiToChuc === 'NHA_PHAN_PHOI' ? 'selected' : '' }}>Nhà phân phối</option>
                <option value="NHA_THUOC" {{ $loaiToChuc === 'NHA_THUOC' ? 'selected' : '' }}>Nhà thuốc</option>
            </select>

            <select name="trangThaiPheDuyet" onchange="this.form.submit()" class="px-3 py-2.5 bg-nenChinh border border-emerald-900/15 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:border-indigo-700 outline-none cursor-pointer">
                <option value="TAT_CA" {{ $trangThaiPheDuyet === 'TAT_CA' ? 'selected' : '' }}>-- Tất cả trạng thái --</option>
                <option value="DA_DUYET" {{ $trangThaiPheDuyet === 'DA_DUYET' ? 'selected' : '' }}>Đã duyệt (Đang hoạt động)</option>
                <option value="BI_THU_HOI" {{ $trangThaiPheDuyet === 'BI_THU_HOI' ? 'selected' : '' }}>Bị thu hồi</option>
            </select>
        </div>
    </form>

    <!-- BẢNG DANH SÁCH -->
    <div class="bg-white rounded-2xl border border-emerald-900/10 bong-xanh overflow-hidden">
        <div class="p-4 bg-nenChinh border-b border-emerald-900/10 flex items-center justify-between text-xs">
            <span class="font-bold text-slate-700 uppercase tracking-wide flex items-center gap-2">
                <i class="fa-solid fa-shield-halved text-indigo-700"></i> Giám Sát Quyền Hạn Tổ Chức
            </span>
            <span class="font-bold text-slate-500 font-mono">Hiển thị: {{ $toChucs->total() }} tổ chức</span>
        </div>

        <div class="w-full overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 uppercase tracking-wider border-b border-slate-100 text-[11px]">
                        <th class="py-3.5 px-3 font-bold whitespace-nowrap">Tên Tổ Chức</th>
                        <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Loại Hình</th>
                        <th class="py-3.5 px-3 font-bold whitespace-nowrap">Mã Số Thuế</th>
                        <th class="py-3.5 px-3 font-bold whitespace-nowrap">Ví On-Chain Active</th>
                        <th class="py-3.5 px-2.5 font-bold text-center whitespace-nowrap">Trạng Thái</th>
                        <th class="py-3.5 px-3 font-bold text-center whitespace-nowrap">Hành Động Quyền Hạn</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse ($toChucs as $toChuc)
                    @php
                    $viIdentity = $toChuc->blockchainIdentity;
                    @endphp
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3 px-3">
                            <div class="font-bold text-slate-900">{{ $toChuc->tenToChuc }}</div>
                            <div class="text-[11px] text-slate-500">{{ $toChuc->diaChi }}</div>
                        </td>
                        <td class="py-3 px-2.5 text-center">
                            @if($toChuc->loaiToChuc->value === 'NHA_SAN_XUAT')
                            <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold text-[10px]">Nhà Sản Xuất</span>
                            @elseif($toChuc->loaiToChuc->value === 'NHA_PHAN_PHOI')
                            <span class="px-2 py-0.5 rounded-full bg-cyan-100 text-cyan-800 font-bold text-[10px]">Nhà Phân Phối</span>
                            @else
                            <span class="px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-800 font-bold text-[10px]">Nhà Thuốc</span>
                            @endif
                        </td>
                        <td class="py-3 px-3 font-mono text-slate-800">{{ $toChuc->maSoThue }}</td>
                        <td class="py-3 px-3 font-mono text-[11px]">
                            @if($viIdentity && $viIdentity->diaChiVi)
                            <a href="https://sepolia.etherscan.io/address/{{ $viIdentity->diaChiVi }}" target="_blank" class="text-indigo-700 hover:underline flex items-center gap-1 font-bold">
                                <span>{{ Str::limit($viIdentity->diaChiVi, 12, '...') }}</span>
                                <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
                            </a>
                            @else
                            <span class="text-slate-400 italic">Chưa cấp ví</span>
                            @endif
                        </td>
                        <td class="py-3 px-2.5 text-center">
                            @if($toChuc->trangThaiDuyet->value === 'DA_DUYET')
                            <span class="px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 font-bold text-[10px] inline-flex items-center gap-1">
                                <i class="fa-solid fa-circle-check"></i> Đang hoạt động
                            </span>
                            @elseif($toChuc->trangThaiDuyet->value === 'BI_THU_HOI')
                            <span class="px-2.5 py-1 rounded-full bg-red-100 text-red-800 font-bold text-[10px] inline-flex items-center gap-1">
                                <i class="fa-solid fa-ban"></i> Bị thu hồi
                            </span>
                            @else
                            <span class="px-2.5 py-1 rounded-full bg-amber-100 text-amber-800 font-bold text-[10px] inline-flex items-center gap-1">
                                <i class="fa-solid fa-clock"></i> Chờ duyệt
                            </span>
                            @endif
                        </td>
                        <td class="py-3 px-3 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                {{-- 1. NÚT THU HỒI --}}
                                @if($toChuc->trangThaiDuyet->value === 'DA_DUYET')
                                <form method="POST" action="{{ route('co-quan.thu-hoi-quyen', $toChuc->id) }}" onsubmit="return confirm('Thu hồi quyền On-chain của tổ chức [{{ $toChuc->tenToChuc }}]?')">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 font-bold rounded-lg border border-red-200 transition-all text-[11px] cursor-pointer" title="Thu hồi quyền">
                                        <i class="fa-solid fa-user-slash"></i> Thu hồi
                                    </button>
                                </form>

                                {{-- 2. NÚT ĐỔI VÍ MỚI (ROTATE WALLET) --}}
                                <form method="POST" action="{{ route('co-quan.doi-vi-moi', $toChuc->id) }}" onsubmit="return confirm('Cấp lại ví mới cho tổ chức [{{ $toChuc->tenToChuc }}]? Ví cũ sẽ bị tước quyền.')">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold rounded-lg border border-indigo-200 transition-all text-[11px] cursor-pointer" title="Đổi ví mới">
                                        <i class="fa-solid fa-arrows-rotate"></i> Đổi ví
                                    </button>
                                </form>
                                @endif

                                {{-- 3. NÚT KHÔI PHỤC QUYỀN --}}
                                @if($toChuc->trangThaiDuyet->value === 'BI_THU_HOI')
                                <form method="POST" action="{{ route('co-quan.khoi-phuc-quyen', $toChuc->id) }}" onsubmit="return confirm('Khôi phục quyền On-chain cho tổ chức [{{ $toChuc->tenToChuc }}]?')">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold rounded-lg border border-emerald-200 transition-all text-[11px] cursor-pointer" title="Khôi phục quyền">
                                        <i class="fa-solid fa-user-check"></i> Khôi phục
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-400">
                            <i class="fa-solid fa-folder-open text-3xl mb-2 block"></i>
                            Không có dữ liệu tổ chức phù hợp.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100 flex items-center justify-between text-xs">
            {{ $toChucs->links() }}
        </div>
    </div>
</div>
@endsection
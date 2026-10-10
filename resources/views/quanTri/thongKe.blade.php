@extends('layouts.app')

@section('title', 'Thống Kê Hệ Thống – Cổng Quản Trị')
@section('panelTitle', 'Thống Kê & Tổng Quan Hệ Thống')

@section('content')
    <div id="thongBaoThongKe" class="mb-4 hidden rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800" role="alert"></div>
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-emerald-900/10 p-5 bong-xanh">
            <div class="text-xs uppercase tracking-[0.2em] text-slate-500">Tổng tài khoản</div>
            <div id="cardTaiKhoan" class="mt-4 text-3xl font-black text-slate-900">0</div>
        </div>
        <div class="bg-white rounded-2xl border border-emerald-900/10 p-5 bong-xanh">
            <div class="text-xs uppercase tracking-[0.2em] text-slate-500">Tổ chức</div>
            <div id="cardToChuc" class="mt-4 text-3xl font-black text-slate-900">0</div>
        </div>
        <div class="bg-white rounded-2xl border border-emerald-900/10 p-5 bong-xanh">
            <div class="text-xs uppercase tracking-[0.2em] text-slate-500">Sản phẩm</div>
            <div id="cardSanPham" class="mt-4 text-3xl font-black text-slate-900">0</div>
        </div>
        <div class="bg-white rounded-2xl border border-emerald-900/10 p-5 bong-xanh">
            <div class="text-xs uppercase tracking-[0.2em] text-slate-500">Lô thuốc</div>
            <div id="cardLoThuoc" class="mt-4 text-3xl font-black text-slate-900">0</div>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-white rounded-2xl border border-emerald-900/10 p-5 bong-xanh">
            <div class="text-xs uppercase tracking-[0.2em] text-slate-500">Tài khoản hoạt động</div>
            <div id="cardActive" class="mt-4 text-3xl font-black text-emerald-700">0</div>
        </div>
        <div class="bg-white rounded-2xl border border-emerald-900/10 p-5 bong-xanh">
            <div class="text-xs uppercase tracking-[0.2em] text-slate-500">Lô đang phân phối</div>
            <div id="cardPhanPhoi" class="mt-4 text-3xl font-black text-cyan-700">0</div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', async function () {
        const map = {
            taiKhoan: 'cardTaiKhoan',
            toChuc: 'cardToChuc',
            sanPham: 'cardSanPham',
            loThuoc: 'cardLoThuoc',
            taiKhoanActive: 'cardActive',
            loDangPhanPhoi: 'cardPhanPhoi',
        };

        try {
            const response = await fetch('/api/thong-ke', {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error('Không thể tải thống kê');
            }

            const data = await response.json();
            Object.entries(map).forEach(([key, id]) => {
                const el = document.getElementById(id);
                if (!el) return;
                el.textContent = Number(data[key] ?? 0).toLocaleString('vi-VN');
            });
        } catch (error) {
            const thongBao = document.getElementById('thongBaoThongKe');
            thongBao.textContent = error.message || 'Không tải được số liệu thống kê.';
            thongBao.classList.remove('hidden');
        }
    });
</script>
@endpush

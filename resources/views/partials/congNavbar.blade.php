<header class="bg-white border-b border-emerald-900/10">
    <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between gap-4">
        <a href="{{ Route::has('cong.truyXuat.trangChu') ? route('cong.truyXuat.trangChu') : url('/') }}" class="flex items-center gap-2 text-emerald-700 font-extrabold text-lg shrink-0">
            <i class="fa-solid fa-shield-halved text-cyan-800 text-2xl"></i>
            <span>PharmaChain</span>
        </a>
        <div class="flex items-center gap-3 sm:gap-6 text-xs font-bold text-slate-600">
            <a class="hover:text-emerald-700" href="{{ Route::has('cong.truyXuat.trangChu') ? route('cong.truyXuat.trangChu') : url('/') }}">Trang chủ</a>
            @if (Route::has('cong.truyXuat.lichSu'))
                <a class="hidden sm:inline hover:text-emerald-700" href="{{ route('cong.truyXuat.lichSu') }}">Lịch sử tra cứu</a>
            @endif
            @if (Route::has('dangNhap'))
                <a class="px-4 py-2 rounded-lg bg-emerald-700 text-white hover:bg-emerald-800" href="{{ route('dangNhap') }}">Đăng nhập</a>
            @endif
        </div>
    </nav>
</header>

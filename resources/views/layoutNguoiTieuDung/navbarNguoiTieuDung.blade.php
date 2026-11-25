<header class="bg-white border-b border-emerald-900/10 sticky top-0 z-50 bong-xanh">
  <div class="max-w-7xl mx-auto px-3 sm:px-5 lg:px-6 h-16 lg:h-18 flex items-center justify-between gap-3">

    <!-- Logo thương hiệu -->
    <a href="{{ url('/') }}" class="flex items-center gap-2.5 group shrink-0 min-w-0">
      <div class="w-10 h-10 rounded-xl bg-emerald-700 flex items-center justify-center text-white text-xl shadow-md group-hover:bg-emerald-800 transition-all shrink-0">
        <i class="fa-solid fa-cubes-stacked"></i>
      </div>
      <div class="min-w-0">
        <span class="block text-lg font-bold text-emerald-700 tracking-tight leading-none truncate">PharmaChain</span>
        <span class="hidden sm:block text-[10px] font-semibold text-cyan-800 mt-0.5 leading-tight truncate">Truy xuất Blockchain</span>
      </div>
    </a>

    <!-- Menu Desktop -->
    <nav class="hidden lg:flex items-center gap-1 xl:gap-2 flex-1 justify-center min-w-0">
      <a href="{{ url('/') }}" class="whitespace-nowrap px-2.5 py-1.5 rounded-md text-[13px] font-semibold hover:text-emerald-700 hover:bg-emerald-50 transition-colors {{ Request::is('/') ? 'text-emerald-700 bg-emerald-50' : 'text-slate-600' }}">
        <i class="fa-solid fa-house-chimney mr-1 opacity-80"></i>Trang chủ
      </a>
      <a href="{{ url('/mang-luoi') }}" class="whitespace-nowrap px-2.5 py-1.5 rounded-md text-[13px] font-semibold hover:text-emerald-700 hover:bg-emerald-50 transition-colors {{ Request::is('mang-luoi') ? 'text-emerald-700 bg-emerald-50' : 'text-slate-600' }}">
        <i class="fa-solid fa-network-wired mr-1 opacity-80"></i>Mạng lưới
      </a>
      <a href="{{ url('/tra-cuu-nguon-goc') }}" class="whitespace-nowrap px-2.5 py-1.5 rounded-md text-[13px] font-semibold hover:text-emerald-700 hover:bg-emerald-50 transition-colors {{ Request::is('tra-cuu-nguon-goc') ? 'text-emerald-700 bg-emerald-50' : 'text-slate-600' }}">
        <i class="fa-solid fa-qrcode mr-1 text-cyan-800 opacity-90"></i>Tra cứu
      </a>
      <a href="{{ url('/lich-su-tra-cuu') }}" class="whitespace-nowrap px-2.5 py-1.5 rounded-md text-[13px] font-semibold hover:text-emerald-700 hover:bg-emerald-50 transition-colors {{ Request::is('lich-su-tra-cuu') ? 'text-emerald-700 bg-emerald-50' : 'text-slate-600' }}">
        <i class="fa-solid fa-clock-rotate-left mr-1 opacity-80"></i>Lịch sử
      </a>
      <a href="{{ url('/bao-cao-nghi-van') }}" class="whitespace-nowrap px-2.5 py-1.5 rounded-md text-[13px] font-semibold hover:text-amber-600 hover:bg-amber-50 transition-colors {{ Request::is('bao-cao-nghi-van') ? 'text-amber-600 bg-amber-50' : 'text-slate-600' }}">
        <i class="fa-solid fa-file-shield mr-1 text-amber-600 opacity-90"></i>Báo cáo
      </a>
    </nav>

    <!-- Nút hành động -->
    <div class="hidden lg:flex items-center gap-2 shrink-0">
      <a href="{{ url('/dang-ky-to-chuc') }}" class="whitespace-nowrap px-3 py-2 rounded-lg border border-cyan-800 text-cyan-800 text-[11px] font-bold hover:bg-cyan-50 transition-all">
        <i class="fa-solid fa-hospital-user mr-1"></i>Đăng ký
      </a>
      <a href="{{ url('/dang-nhap') }}" class="whitespace-nowrap px-3.5 py-2 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white text-[11px] font-bold shadow-sm transition-all inline-flex items-center gap-1.5">
        <i class="fa-solid fa-right-to-bracket"></i>Đăng nhập
      </a>
    </div>

    <!-- Nút Menu Mobile / Tablet -->
    <div class="flex items-center lg:hidden shrink-0">
      <button id="nutMenuDiDong" type="button" class="p-2 text-slate-600 hover:text-emerald-700 text-xl" aria-label="Mở menu">
        <i class="fa-solid fa-bars"></i>
      </button>
    </div>
  </div>

  <!-- Menu Drawer Mobile -->
  <div id="hopMenuDiDong" class="hidden lg:hidden border-t border-emerald-900/10 bg-white px-4 pt-3 pb-6 space-y-1">
    <a href="{{ url('/') }}" class="flex items-center gap-3 py-2.5 px-2 rounded-lg text-sm font-semibold text-slate-900 hover:text-emerald-700 hover:bg-emerald-50">
      <i class="fa-solid fa-house-chimney w-5 text-center text-emerald-700"></i> Trang chủ
    </a>
    <a href="{{ url('/mang-luoi') }}" class="flex items-center gap-3 py-2.5 px-2 rounded-lg text-sm font-semibold text-slate-900 hover:text-emerald-700 hover:bg-emerald-50">
      <i class="fa-solid fa-network-wired w-5 text-center text-emerald-700"></i> Mạng lưới
    </a>
    <a href="{{ url('/tra-cuu-nguon-goc') }}" class="flex items-center gap-3 py-2.5 px-2 rounded-lg text-sm font-semibold text-slate-900 hover:text-emerald-700 hover:bg-emerald-50">
      <i class="fa-solid fa-qrcode w-5 text-center text-cyan-800"></i> Tra cứu thuốc
    </a>
    <a href="{{ url('/lich-su-tra-cuu') }}" class="flex items-center gap-3 py-2.5 px-2 rounded-lg text-sm font-semibold text-slate-900 hover:text-emerald-700 hover:bg-emerald-50">
      <i class="fa-solid fa-clock-rotate-left w-5 text-center text-slate-500"></i> Lịch sử tra cứu
    </a>
    <a href="{{ url('/bao-cao-nghi-van') }}" class="flex items-center gap-3 py-2.5 px-2 rounded-lg text-sm font-semibold text-amber-600 hover:bg-amber-50">
      <i class="fa-solid fa-file-shield w-5 text-center text-amber-600"></i> Báo cáo nghi vấn
    </a>
    <div class="pt-3 mt-2 border-t border-emerald-900/10 flex flex-col gap-2">
      <a href="{{ url('/dang-ky-to-chuc') }}" class="w-full text-center py-2.5 rounded-lg border border-cyan-800 text-cyan-800 font-bold text-xs">
        Đăng ký tổ chức
      </a>
      <a href="{{ url('/dang-nhap') }}" class="w-full text-center py-2.5 rounded-lg bg-emerald-700 text-white font-bold text-xs">
        Đăng nhập
      </a>
    </div>
  </div>
</header>

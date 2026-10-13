<header class="bg-white border-b border-emerald-900/10 sticky top-0 z-50 bong-xanh">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">

    <!-- Logo thương hiệu -->
    <a href="{{ url('/') }}" class="flex items-center gap-3 group">
      <div class="w-12 h-12 rounded-xl bg-emerald-700 flex items-center justify-center text-white text-2xl shadow-md group-hover:bg-emerald-800 transition-all">
        <i class="fa-solid fa-cubes-stacked"></i>
      </div>
      <div>
        <span class="block text-xl font-bold text-emerald-700 tracking-tight leading-none">PharmaChain</span>
        <span class="block text-xs font-semibold text-cyan-800 mt-1">Truy Xuất Nguồn Gốc Blockchain</span>
      </div>
    </a>

    <!-- Menu Desktop -->
    <nav class="hidden md:flex items-center gap-8">
      <a href="{{ url('/') }}" class="text-sm font-semibold hover:text-emerald-700 transition-colors {{ Request::is('/') ? 'text-emerald-700 border-b-2 border-emerald-700 pb-1' : 'text-slate-600' }}">
        <i class="fa-solid fa-house-chimney mr-1.5"></i> Trang Chủ
      </a>
      <a href="{{ url('/tra-cuu-nguon-goc') }}" class="text-sm font-semibold hover:text-emerald-700 transition-colors {{ Request::is('tra-cuu-nguon-goc') ? 'text-emerald-700 border-b-2 border-emerald-700 pb-1' : 'text-slate-600' }}">
        <i class="fa-solid fa-qrcode mr-1.5 text-cyan-800"></i> Tra Cứu Thuốc
      </a>
      <a href="{{ url('/lich-su-tra-cuu') }}" class="text-sm font-semibold hover:text-emerald-700 transition-colors {{ Request::is('lich-su-tra-cuu') ? 'text-emerald-700 border-b-2 border-emerald-700 pb-1' : 'text-slate-600' }}">
        <i class="fa-solid fa-clock-rotate-left mr-1.5"></i> Lịch Sử Tra Cứu
      </a>
      <a href="{{ url('/bao-cao-nghi-van') }}" class="text-sm font-semibold hover:text-amber-600 transition-colors {{ Request::is('bao-cao-nghi-van') ? 'text-amber-600 border-b-2 border-amber-600 pb-1' : 'text-slate-600' }}">
        <i class="fa-solid fa-file-shield mr-1.5 text-amber-600"></i> Báo Cáo Nghi Vấn
      </a>
    </nav>

    <!-- Nút hành động -->
    <div class="hidden lg:flex items-center gap-3">
      <a href="{{ url('/dang-ky-to-chuc') }}" class="px-4 py-2.5 rounded-lg border border-cyan-800 text-cyan-800 text-xs font-bold hover:bg-cyan-50 transition-all">
        <i class="fa-solid fa-hospital-user mr-1.5"></i> Đăng Ký Tổ Chức
      </a>
      <a href="{{ url('/dang-nhap') }}" class="px-5 py-2.5 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold shadow-sm transition-all flex items-center gap-2">
        <i class="fa-solid fa-right-to-bracket"></i> Đăng Nhập
      </a>
    </div>

    <!-- Nút Menu Mobile -->
    <div class="flex items-center md:hidden">
      <button id="nutMenuDiDong" class="p-2 text-slate-600 hover:text-emerald-700 text-xl">
        <i class="fa-solid fa-bars"></i>
      </button>
    </div>
  </div>

  <!-- Menu Drawer Mobile -->
  <div id="hopMenuDiDong" class="hidden md:hidden border-t border-emerald-900/10 bg-white px-4 pt-3 pb-6 space-y-3">
    <a href="{{ url('/') }}" class="block py-2 text-sm font-semibold text-slate-900 hover:text-emerald-700">
      <i class="fa-solid fa-house-chimney w-6 text-emerald-700"></i> Trang Chủ
    </a>
    <a href="{{ url('/tra-cuu-nguon-goc') }}" class="block py-2 text-sm font-semibold text-slate-900 hover:text-emerald-700">
      <i class="fa-solid fa-qrcode w-6 text-cyan-800"></i> Tra Cứu Thuốc
    </a>
    <a href="{{ url('/lich-su-tra-cuu') }}" class="block py-2 text-sm font-semibold text-slate-900 hover:text-emerald-700">
      <i class="fa-solid fa-clock-rotate-left w-6 text-slate-500"></i> Lịch Sử Tra Cứu
    </a>
    <a href="{{ url('/bao-cao-nghi-van') }}" class="block py-2 text-sm font-semibold text-amber-600">
      <i class="fa-solid fa-file-shield w-6 text-amber-600"></i> Báo Cáo Nghi Vấn
    </a>
    <div class="pt-3 border-t border-emerald-900/10 flex flex-col gap-2">
      <a href="{{ url('/dang-ky-to-chuc') }}" class="w-full text-center py-2.5 rounded-lg border border-cyan-800 text-cyan-800 font-bold text-xs">
        Đăng Ký Tổ Chức
      </a>
      <a href="{{ url('/dang-nhap') }}" class="w-full text-center py-2.5 rounded-lg bg-emerald-700 text-white font-bold text-xs">
        Đăng Nhập Hệ Thống
      </a>
    </div>
  </div>
</header>
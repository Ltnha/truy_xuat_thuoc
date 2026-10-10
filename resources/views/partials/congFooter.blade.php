<footer class="bg-white border-t border-emerald-900/10 mt-auto">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="space-y-3">
                <div class="flex items-center gap-2 text-emerald-700 font-bold text-lg">
                    <i class="fa-solid fa-shield-halved text-cyan-800 text-2xl"></i>
                    <span>PharmaChain</span>
                </div>
                <p class="text-xs text-slate-600 leading-relaxed">Hệ thống truy xuất nguồn gốc dược phẩm và kiểm soát chuỗi cung ứng.</p>
            </div>
            <div>
                <h2 class="text-xs font-bold text-emerald-700 uppercase tracking-wider mb-3">Tra cứu</h2>
                <div class="space-y-2 text-xs text-slate-600">
                    @if (Route::has('cong.truyXuat.trangChu'))
                        <a class="block hover:text-emerald-700" href="{{ route('cong.truyXuat.trangChu') }}">Tra cứu thuốc</a>
                    @endif
                    @if (Route::has('cong.truyXuat.lichSu'))
                        <a class="block hover:text-emerald-700" href="{{ route('cong.truyXuat.lichSu') }}">Lịch sử kiểm tra gần đây</a>
                    @endif
                </div>
            </div>
            <div class="space-y-2">
                <h2 class="text-xs font-bold text-emerald-700 uppercase tracking-wider">Minh bạch chuỗi cung ứng</h2>
                <p class="text-xs text-slate-600 leading-relaxed">Thông tin truy xuất được cung cấp để hỗ trợ kiểm tra nguồn gốc dược phẩm.</p>
            </div>
        </div>
        <div class="mt-8 pt-5 border-t border-emerald-900/10 text-xs text-slate-500">© {{ date('Y') }} PharmaChain.</div>
    </div>
</footer>

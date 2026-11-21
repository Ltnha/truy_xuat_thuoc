<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="contract-address" content="{{ env('BLOCKCHAIN_CONTRACT_ADDRESS') }}">
    <title>@yield('title', 'Quản Trị Hệ Thống - PharmaChain')</title>
    <!-- Tối ưu kết nối sớm tới các CDN -->
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Tải CSS Tailwind nội bộ từ bản build tĩnh (tốc độ cao) hoặc fallback CDN -->
    @if (file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif
    @stack('styles')
</head>
<body class="bg-slate-50 text-slate-900 font-sans">
    <div class="flex min-h-screen">
        <!-- Sidebar dùng chung bên trái -->
        @include('layoutDungChung.sidebarDungChung')

        <!-- Khu vực nội dung bên phải -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Topbar dùng chung phía trên -->
            @include('layoutDungChung.topbarDungChung')

            <!-- Nội dung thay đổi của từng trang quản lý -->
            <main class="flex-1 p-6">
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Popup dùng chung cho các thao tác ký Blockchain và thông báo hệ thống -->
    <div id="pharmaPopupRoot" class="hidden fixed inset-0 z-[10000] flex items-center justify-center bg-slate-950/60 p-4" role="presentation">
        <section class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="pharmaPopupTitle">
            <div class="flex items-start gap-3">
                <div id="pharmaPopupIcon" class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-cyan-100 text-cyan-700 text-xl">
                    <i class="fa-solid fa-circle-info"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <h3 id="pharmaPopupTitle" class="text-base font-extrabold text-slate-900">Thông báo</h3>
                    <p id="pharmaPopupMessage" class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600"></p>
                </div>
            </div>
            <div id="pharmaPopupInputWrap" class="hidden mt-4">
                <textarea id="pharmaPopupInput" rows="3" maxlength="500" class="w-full rounded-xl border border-slate-300 p-3 text-sm outline-none focus:border-cyan-700" placeholder="Nhập nội dung..."></textarea>
            </div>
            <a id="pharmaPopupTxLink" class="hidden mt-4 block break-all rounded-xl bg-slate-50 p-3 text-xs font-semibold text-blue-700 underline" href="#" target="_blank" rel="noopener noreferrer">Xem giao dịch trên Sepolia</a>
            <div class="mt-6 flex justify-end gap-2">
                <button id="pharmaPopupCancel" type="button" class="hidden rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-50">Hủy</button>
                <button id="pharmaPopupConfirm" type="button" class="rounded-xl bg-cyan-800 px-5 py-2.5 text-sm font-bold text-white hover:bg-cyan-900">Đóng</button>
            </div>
        </section>
    </div>

    <script>
        (function () {
            const root = document.getElementById('pharmaPopupRoot');
            if (!root) return;
            const titleEl = document.getElementById('pharmaPopupTitle');
            const messageEl = document.getElementById('pharmaPopupMessage');
            const iconEl = document.getElementById('pharmaPopupIcon');
            const txLink = document.getElementById('pharmaPopupTxLink');
            const inputWrap = document.getElementById('pharmaPopupInputWrap');
            const input = document.getElementById('pharmaPopupInput');
            const cancelButton = document.getElementById('pharmaPopupCancel');
            const confirmButton = document.getElementById('pharmaPopupConfirm');
            let settle = null;
            let mode = 'notice';

            const styles = {
                success: { icon: 'fa-circle-check', box: 'bg-emerald-100 text-emerald-700' },
                error: { icon: 'fa-circle-xmark', box: 'bg-rose-100 text-rose-700' },
                warning: { icon: 'fa-triangle-exclamation', box: 'bg-amber-100 text-amber-700' },
                info: { icon: 'fa-circle-info', box: 'bg-cyan-100 text-cyan-700' }
            };

            function close(value) {
                root.classList.add('hidden');
                root.onclick = null;
                document.onkeydown = null;
                if (settle) settle(value);
                settle = null;
            }

            function open(options, nextMode) {
                const config = options || {};
                mode = nextMode || 'notice';
                const type = styles[config.type] ? config.type : 'info';
                const style = styles[type];
                titleEl.textContent = config.title || (type === 'success' ? 'Thành công' : type === 'error' ? 'Không thành công' : 'Thông báo');
                messageEl.textContent = config.message || '';
                iconEl.className = 'flex h-12 w-12 shrink-0 items-center justify-center rounded-full text-xl ' + style.box;
                iconEl.innerHTML = '<i class="fa-solid ' + style.icon + '"></i>';

                const hash = String(config.txHash || '');
                if (/^0x[a-fA-F0-9]{64}$/.test(hash)) {
                    txLink.href = 'https://sepolia.etherscan.io/tx/' + hash;
                    txLink.textContent = 'Xem giao dịch trên Sepolia · ' + hash;
                    txLink.classList.remove('hidden');
                } else {
                    txLink.removeAttribute('href');
                    txLink.classList.add('hidden');
                }

                inputWrap.classList.toggle('hidden', mode !== 'prompt');
                input.value = config.defaultValue || '';
                cancelButton.classList.toggle('hidden', mode === 'notice');
                cancelButton.textContent = config.cancelText || 'Hủy';
                confirmButton.textContent = config.confirmText || (mode === 'notice' ? 'Đóng' : 'Xác nhận');
                root.classList.remove('hidden');

                return new Promise(resolve => {
                    settle = resolve;
                    confirmButton.onclick = () => close(mode === 'prompt' ? input.value.trim() : true);
                    cancelButton.onclick = () => close(mode === 'prompt' ? null : false);
                    root.onclick = event => {
                        if (event.target === root && mode === 'notice') close(true);
                    };
                    document.onkeydown = event => {
                        if (event.key !== 'Escape') return;
                        close(mode === 'prompt' ? null : mode === 'confirm' ? false : true);
                    };
                    if (mode === 'prompt') setTimeout(() => input.focus(), 0);
                    else confirmButton.focus();
                });
            }

            window.PharmaPopup = {
                show: options => open(options, 'notice'),
                confirm: options => open(options, 'confirm'),
                prompt: options => open(options, 'prompt')
            };
        })();
    </script>

    @stack('scripts')
</body>
</html>
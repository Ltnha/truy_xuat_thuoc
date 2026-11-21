document.addEventListener('DOMContentLoaded', () => {
    const app = document.getElementById('banLeApp');
    if (!app) return;

    const urls = {
        lookup: app.dataset.lookupUrl,
        stock: app.dataset.stockUrl,
        sale: app.dataset.saleUrl,
        history: app.dataset.historyUrl,
        retryBase: app.dataset.retryUrl
    };
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const cart = new Map();
    let currentLot = null;
    let stockCache = [];
    let cameraStream = null;
    let cameraFrame = null;
    let historyPollTimer = null;
    let historyLoading = false;
    const invoiceSyncStates = new Map();

    const el = (id) => document.getElementById(id);
    const money = (value) => new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 }).format(Number(value || 0));
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    }[char]));

    async function request(url, options = {}) {
        const response = await fetch(url, {
            ...options,
            headers: {
                'Accept': 'application/json',
                ...(options.body ? { 'Content-Type': 'application/json' } : {}),
                ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                ...(options.headers || {})
            }
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok || data.success === false) throw new Error(data.message || 'Không thể xử lý yêu cầu.');
        return data;
    }

    function notify(message, type = 'info', txHash = null) {
        window.PharmaPopup?.show({
            type,
            title: type === 'error' ? 'Thao tác không thành công' : type === 'success' ? 'Thành công' : 'Thông báo',
            message,
            txHash
        });
    }

    async function stopCamera() {
        if (cameraFrame !== null) cancelAnimationFrame(cameraFrame);
        cameraFrame = null;
        if (cameraStream) cameraStream.getTracks().forEach((track) => track.stop());
        cameraStream = null;
        const video = el('qrCameraVideo');
        if (video) video.srcObject = null;
        el('qrCameraWrap')?.classList.add('hidden');
    }

    async function startCamera() {
        if (!('BarcodeDetector' in window)) {
            notify('Trình duyệt chưa hỗ trợ quét QR bằng camera. Dùng máy quét QR dạng bàn phím hoặc nhập mã lô.', 'error');
            return;
        }
        try {
            const detector = new BarcodeDetector({ formats: ['qr_code'] });
            cameraStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false });
            const video = el('qrCameraVideo');
            video.srcObject = cameraStream;
            el('qrCameraWrap').classList.remove('hidden');
            await video.play();

            const scan = async () => {
                if (!cameraStream) return;
                try {
                    const codes = await detector.detect(video);
                    if (codes.length && codes[0].rawValue) {
                        const value = codes[0].rawValue.trim();
                        await stopCamera();
                        el('maQRInput').value = value;
                        await lookupLot(value);
                        return;
                    }
                } catch (_) {}
                cameraFrame = requestAnimationFrame(scan);
            };
            cameraFrame = requestAnimationFrame(scan);
        } catch (error) {
            await stopCamera();
            notify('Không thể mở camera: ' + (error.message || 'Vui lòng kiểm tra quyền camera.'), 'error');
        }
    }

    async function lookupLot(identifier) {
        const data = await request(urls.lookup + '?ma=' + encodeURIComponent(identifier));
        currentLot = data.data;
        renderLookup(currentLot);
    }

    function renderLookup(lot) {
        el('ketQuaLo').classList.remove('hidden');
        el('loTenSanPham').textContent = lot.tenSanPham || 'Chưa có tên sản phẩm';
        el('loMaLo').textContent = 'Mã lô: ' + lot.maLo;
        el('loTonKho').textContent = Number(lot.soLuongTon).toLocaleString('vi-VN');
        el('loHanSuDung').textContent = lot.hanSuDung;
        el('loSoDangKy').textContent = lot.soDangKy || '—';
        el('loIntegrity').textContent = lot.blockchain.productIntegrity ? 'Khớp' : 'Không khớp';
        const badge = el('loTrangThai');
        badge.textContent = lot.canSell ? 'Đủ điều kiện bán' : 'Không được bán';
        badge.className = 'rounded-full px-3 py-1 text-xs font-bold ' + (lot.canSell ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800');
        const reasons = el('loLyDo');
        reasons.innerHTML = '';
        (lot.lyDoKhongTheBan || []).forEach((reason) => {
            const li = document.createElement('li');
            li.textContent = reason;
            reasons.appendChild(li);
        });
        el('btnThemLo').classList.toggle('hidden', !lot.canSell || Number(lot.soLuongTon) < 1);
    }

    function addCurrentLot() {
        if (!currentLot?.canSell) return;
        const id = Number(currentLot.loThuocId);
        if (!cart.has(id)) {
            cart.set(id, {
                loThuocId: id,
                maLo: currentLot.maLo,
                tenSanPham: currentLot.tenSanPham,
                tonKho: Number(currentLot.soLuongTon),
                soLuong: 1,
                donGia: 0
            });
        }
        renderCart();
        notify('Đã thêm lô vào hóa đơn. Nhập số lượng và đơn giá bán.', 'success');
    }

    function renderCart() {
        const tbody = el('cartRows');
        tbody.innerHTML = '';
        if (!cart.size) {
            tbody.innerHTML = '<tr><td colspan="6" class="p-5 text-center text-slate-400">Chưa có lô thuốc trong hóa đơn.</td></tr>';
        }
        let total = 0;
        cart.forEach((item, id) => {
            const lineTotal = item.soLuong * item.donGia;
            total += lineTotal;
            const tr = document.createElement('tr');
            tr.className = 'dong-ban-le border-b border-slate-100';
            tr.innerHTML = `
                <td class="p-3"><div class="font-bold text-slate-800">${escapeHtml(item.tenSanPham)}</div><div class="text-xs font-mono text-slate-500">${escapeHtml(item.maLo)}</div></td>
                <td class="p-3">${item.tonKho.toLocaleString('vi-VN')}</td>
                <td class="p-3"><input class="cart-qty w-24 border rounded-lg px-2 py-1" type="number" min="1" max="${item.tonKho}" value="${item.soLuong}" data-id="${id}"></td>
                <td class="p-3"><input class="cart-price w-32 border rounded-lg px-2 py-1" type="number" min="0" step="100" value="${item.donGia}" data-id="${id}"></td>
                <td class="p-3 text-right font-bold">${money(lineTotal)}</td>
                <td class="p-3 text-right"><button class="cart-remove text-red-600 font-bold" type="button" data-id="${id}" aria-label="Xóa lô">Xóa</button></td>`;
            tbody.appendChild(tr);
        });
        el('cartCount').textContent = cart.size + ' lô';
        el('cartTotal').textContent = money(total);
        el('btnXacNhanBan').disabled = !cart.size;
        el('btnXacNhanBan').className = 'mt-2 px-5 py-2.5 rounded-xl font-bold text-sm ' + (cart.size ? 'bg-cyan-800 hover:bg-cyan-900 text-white' : 'bg-slate-200 text-slate-400');
    }

    el('cartRows').addEventListener('change', (event) => {
        const id = Number(event.target.dataset.id);
        const item = cart.get(id);
        if (!item) return;
        if (event.target.classList.contains('cart-qty')) {
            item.soLuong = Math.max(1, Math.min(item.tonKho, Number(event.target.value) || 1));
        }
        if (event.target.classList.contains('cart-price')) {
            item.donGia = Math.max(0, Number(event.target.value) || 0);
        }
        renderCart();
    });
    el('cartRows').addEventListener('click', (event) => {
        if (!event.target.classList.contains('cart-remove')) return;
        cart.delete(Number(event.target.dataset.id));
        renderCart();
    });

    async function loadStock() {
        const q = el('timKiemTonKho').value.trim();
        const data = await request(urls.stock + '?q=' + encodeURIComponent(q));
        stockCache = data.data || [];
        const tbody = el('stockRows');
        tbody.innerHTML = '';
        if (!stockCache.length) {
            tbody.innerHTML = '<tr><td colspan="5" class="p-5 text-center text-slate-400">Kho chưa có lô đủ điều kiện tồn.</td></tr>';
            return;
        }
        stockCache.forEach((item) => {
            const tr = document.createElement('tr');
            tr.className = 'dong-ban border-b border-slate-100';
            tr.innerHTML = `
                <td class="p-3 font-mono text-xs">${escapeHtml(item.maLoNghiepVu)}</td>
                <td class="p-3 font-semibold">${escapeHtml(item.tenSanPham)}</td>
                <td class="p-3">${escapeHtml(item.hanSuDung)}</td>
                <td class="p-3 text-right font-bold">${Number(item.soLuong).toLocaleString('vi-VN')}</td>
                <td class="p-3 text-right"><button type="button" class="stock-select text-cyan-800 font-bold" data-code="${escapeHtml(item.maLoNghiepVu)}">Kiểm tra</button></td>`;
            tbody.appendChild(tr);
        });
    }

    el('stockRows').addEventListener('click', async (event) => {
        const button = event.target.closest('.stock-select');
        if (!button) return;
        el('maQRInput').value = button.dataset.code;
        try { await lookupLot(button.dataset.code); } catch (error) { notify(error.message, 'error'); }
    });

    async function loadHistory() {
        if (historyLoading) return;
        historyLoading = true;
        try {
            const data = await request(urls.history);
            const invoices = data.data || [];
            const tbody = el('historyRows');
            tbody.innerHTML = '';
            let hasPending = false;

            if (!invoices.length) {
                tbody.innerHTML = '<tr><td colspan="5" class="p-5 text-center text-slate-400">Chưa có hóa đơn bán hàng.</td></tr>';
            }

            invoices.forEach((invoice) => {
                const blockchain = invoice.blockchain || [];
                const states = blockchain.map((tx) => String(tx.trangThai || '').toUpperCase());
                const allConfirmed = states.length > 0 && states.every((state) => state === 'CONFIRMED');
                const hasFailed = states.includes('FAILED');
                const syncState = allConfirmed ? 'CONFIRMED' : hasFailed ? 'FAILED' : 'PENDING';
                const previous = invoiceSyncStates.get(Number(invoice.id));

                if (previous === 'PENDING' && allConfirmed) {
                    const proof = blockchain.find((tx) => tx.trangThai === 'CONFIRMED' && /^0x[a-fA-F0-9]{64}$/.test(String(tx.txHash || '')));
                    notify('Hóa đơn ' + invoice.maHoaDon + ' đã được ký và ghi nhận sự kiện SOLD trên Ethereum Sepolia.', 'success', proof?.txHash || null);
                } else if (previous === 'PENDING' && hasFailed) {
                    const failed = blockchain.find((tx) => tx.trangThai === 'FAILED');
                    notify(failed?.loiCuoi || ('Không thể đồng bộ sự kiện SOLD của hóa đơn ' + invoice.maHoaDon + '.'), 'error');
                }
                invoiceSyncStates.set(Number(invoice.id), syncState);
                if (syncState === 'PENDING') hasPending = true;

                const status = allConfirmed ? 'Đã xác nhận' : hasFailed ? 'Có lỗi đồng bộ' : 'Đang chờ đồng bộ';
                const tr = document.createElement('tr');
                tr.className = 'dong-ban border-b border-slate-100';
                tr.innerHTML = `
                    <td class="p-3 font-mono text-xs">${escapeHtml(invoice.maHoaDon)}</td>
                    <td class="p-3">${escapeHtml(invoice.ngayBan)}</td>
                    <td class="p-3 text-right font-bold">${money(invoice.tongTien)}</td>
                    <td class="p-3"><span class="badge-ban-le ${allConfirmed ? 'bg-emerald-100 text-emerald-800' : hasFailed ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800'}">${status}</span></td>
                    <td class="p-3 text-right">${allConfirmed ? '' : `<button type="button" class="retry-invoice text-cyan-800 font-bold" data-id="${invoice.id}">Đồng bộ lại</button>`}</td>`;
                tbody.appendChild(tr);
            });

            if (hasPending && !historyPollTimer) {
                historyPollTimer = setInterval(() => loadHistory().catch((error) => console.error(error)), 5000);
            } else if (!hasPending && historyPollTimer) {
                clearInterval(historyPollTimer);
                historyPollTimer = null;
            }
        } finally {
            historyLoading = false;
        }
    }

    el('historyRows').addEventListener('click', async (event) => {
        const button = event.target.closest('.retry-invoice');
        if (!button) return;
        try {
            await request(urls.retryBase + '/' + button.dataset.id + '/dong-bo-lai', { method: 'POST' });
            notify('Đã yêu cầu đồng bộ lại các sự kiện Blockchain.', 'success');
            await loadHistory();
        } catch (error) { notify(error.message, 'error'); }
    });

    el('formTraCuuLo').addEventListener('submit', async (event) => {
        event.preventDefault();
        const code = el('maQRInput').value.trim();
        if (!code) return;
        try { await lookupLot(code); }
        catch (error) { currentLot = null; el('ketQuaLo').classList.add('hidden'); notify(error.message, 'error'); }
    });
    el('btnThemLo').addEventListener('click', addCurrentLot);
    el('btnMoCamera')?.addEventListener('click', startCamera);
    el('btnTatCamera')?.addEventListener('click', stopCamera);
    el('timKiemTonKho').addEventListener('input', () => {
        clearTimeout(window.__banLeSearch);
        window.__banLeSearch = setTimeout(() => loadStock().catch((error) => notify(error.message, 'error')), 250);
    });

    el('btnXacNhanBan').addEventListener('click', async () => {
        if (!cart.size) return;
        const button = el('btnXacNhanBan');
        button.disabled = true;
        button.textContent = 'Đang lưu hóa đơn...';
        try {
            const data = await request(urls.sale, {
                method: 'POST',
                body: JSON.stringify({
                    items: [...cart.values()].map((item) => ({
                        loThuocId: item.loThuocId,
                        soLuong: item.soLuong,
                        donGia: item.donGia
                    })),
                    ghiChu: el('ghiChuHoaDon').value.trim() || null
                })
            });
            cart.clear();
            el('ghiChuHoaDon').value = '';
            renderCart();
            const invoice = data.data || {};
            const invoiceTxs = invoice.blockchain || [];
            const alreadyConfirmed = invoiceTxs.length > 0 && invoiceTxs.every((tx) => tx.trangThai === 'CONFIRMED');
            if (invoice.id) invoiceSyncStates.set(Number(invoice.id), alreadyConfirmed ? 'CONFIRMED' : 'PENDING');
            notify(
                alreadyConfirmed
                    ? (data.message || 'Hóa đơn đã được lưu và ghi nhận trên Blockchain.')
                    : 'Hóa đơn đã được lưu. Các sự kiện SOLD đang chờ Signer đồng bộ lên Sepolia.',
                alreadyConfirmed ? 'success' : 'info',
                alreadyConfirmed ? invoiceTxs.find((tx) => tx.txHash)?.txHash : null
            );
            await Promise.all([loadStock(), loadHistory()]);
        } catch (error) {
            notify(error.message, 'error');
        } finally {
            button.textContent = 'Hoàn tất hóa đơn';
            renderCart();
        }
    });

    renderCart();
    loadStock().catch((error) => notify(error.message, 'error'));
    loadHistory().catch((error) => notify(error.message, 'error'));
});

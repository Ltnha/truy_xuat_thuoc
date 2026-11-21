/**
 * tiepNhanLoHang.js - Nhà thuốc
 * Kết nối CSDL thật qua API (thay thế hoàn toàn dữ liệu giả)
 */
document.addEventListener('DOMContentLoaded', function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content
        || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || '';

    function getCookie(name) {
        const m = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/([.$?*|{}()[\]\\/+^])/g, '\\$1') + '=([^;]*)'));
        return m ? decodeURIComponent(m[1]) : '';
    }

    const tabCho = document.getElementById('tabChoXacNhan');
    const tabDa = document.getElementById('tabDaTiepNhan');
    const khungCho = document.getElementById('khungTabChoXacNhan');
    const khungDa = document.getElementById('khungTabDaTiepNhan');

    let currentPageCho = 1;
    let currentPageDa = 1;
    let danhSachChoXacNhanHienTai = [];
    let maQRNhanHangDaGiaiMa = '';
    let urlAnhQRNhanHang = null;

    function dinhDangNgay(ngayYMD) {
        if (!ngayYMD) return '--';
        const p = ngayYMD.split('-');
        return p.length === 3 ? `${p[2]}/${p[1]}/${p[0]}` : ngayYMD;
    }

    function toast(msg, type = 'success', txHash = null) {
        window.PharmaPopup?.show({
            type,
            title: type === 'error' ? 'Thao tác không thành công' : type === 'success' ? 'Thành công' : 'Thông báo',
            message: msg,
            txHash
        });
    }

    async function fetchJSON(url, options = {}) {
        const xsrf = getCookie('XSRF-TOKEN');
        const headers = {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': CSRF,
            ...(xsrf ? { 'X-XSRF-TOKEN': xsrf } : {}),
            ...(options.headers || {}),
        };

        // Đảm bảo POST/PUT có body JSON tối thiểu để Laravel đọc được session/CSRF ổn định
        let body = options.body;
        if ((options.method || 'GET').toUpperCase() !== 'GET' && body === undefined) {
            body = JSON.stringify({ _token: CSRF });
            headers['Content-Type'] = headers['Content-Type'] || 'application/json';
        }

        const res = await fetch(url, {
            credentials: 'same-origin',
            ...options,
            headers,
            body,
        });
        const text = await res.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            if (res.status === 419) {
                throw new Error('Phiên làm việc hết hạn (CSRF 419). Bấm F5 tải lại trang rồi thử lại.');
            }
            if (text.trim().startsWith('<!DOCTYPE') || text.trim().startsWith('<html')) {
                throw new Error('API trả về HTML (HTTP ' + res.status + '). Kiểm tra route/đăng nhập hoặc cột DB lyDoTuChoi: ' + url);
            }
            throw new Error('Phản hồi không phải JSON (HTTP ' + res.status + ') từ: ' + url);
        }
        if (!res.ok || data.success === false) {
            if (res.status === 419) {
                throw new Error('Phiên làm việc hết hạn (CSRF 419). Bấm F5 tải lại trang rồi thử lại.');
            }
            throw new Error(data.message || ('Có lỗi xảy ra (HTTP ' + res.status + ')'));
        }
        return data;
    }

    // ========== TAB CHỜ XÁC NHẬN ==========
    async function loadChoXacNhan(page = 1) {
        currentPageCho = page;
        const q = document.getElementById('oTimKiemNhan')?.value || '';
        const tbody = document.getElementById('thanBangTiepNhan') || document.querySelector('#khungTabChoXacNhan tbody');
        if (!tbody) return;

        tbody.innerHTML = `<tr><td colspan="9" class="py-8 text-center text-slate-400">Đang tải dữ liệu...</td></tr>`;

        try {
            const res = await fetchJSON(`/nha-thuoc/api/cho-xac-nhan?page=${page}&per_page=10&q=${encodeURIComponent(q)}`);
            const list = res.data || [];
            danhSachChoXacNhanHienTai = list;
            const meta = res.meta || {};

            const badge = document.getElementById('badgeSoLuongChoXacNhan');
            if (badge) badge.textContent = meta.total || 0;

            const tong = document.getElementById('tongSoLoHienThi');
            if (tong) tong.textContent = `Hiển thị: ${list.length} / ${meta.total || 0} lô`;

            if (list.length === 0) {
                tbody.innerHTML = `<tr><td colspan="9" class="py-10 text-center text-slate-400">Không có lô hàng nào đang chờ xác nhận</td></tr>`;
                return;
            }

            tbody.innerHTML = list.map(item => {
                const isRecalled = item.trangThaiLo === 'RECALLED';
                const isExpired = item.hanDung && item.hanDung < new Date().toISOString().slice(0, 10);
                let badgeClass = 'bg-cyan-100 text-cyan-800';
                let badgeText = 'CHỜ XÁC NHẬN';
                if (isRecalled) {
                    badgeClass = 'bg-red-100 text-red-700';
                    badgeText = 'THU HỒI';
                } else if (isExpired) {
                    badgeClass = 'bg-amber-100 text-amber-700';
                    badgeText = 'HẾT HẠN';
                }

                return `
                <tr class="hover:bg-slate-50/80">
                    <td class="py-3.5 px-3 font-mono text-[11px]">${(item.maGiaoDich || '').substring(0, 13)}…</td>
                    <td class="py-3.5 px-3 font-semibold">${item.maLo || '--'}</td>
                    <td class="py-3.5 px-3">${item.tenSanPham || '--'}</td>
                    <td class="py-3.5 px-3">${item.benGui || '--'}</td>
                    <td class="py-3.5 px-2.5 text-center ${isExpired ? 'text-amber-600 font-bold' : ''}">${dinhDangNgay(item.hanDung)}</td>
                    <td class="py-3.5 px-3 text-right font-bold">${Number(item.soLuong).toLocaleString('vi-VN')}</td>
                    <td class="py-3.5 px-2.5 text-center text-[11px]">${item.ngayGui || '--'}</td>
                    <td class="py-3.5 px-2.5 text-center">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold ${badgeClass}">${badgeText}</span>
                    </td>
                    <td class="py-3.5 px-3 text-center">
                        <div class="flex items-center justify-center gap-1.5">
                            ${isRecalled || isExpired
                                ? '<span class="text-[11px] text-amber-600 font-medium">Không thể nhận</span>'
                                : `<button type="button" data-id="${item.id}" class="btn-xac-nhan px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-[11px] font-bold transition">Xác nhận nhận hàng</button>`
                            }
                            <button type="button" data-id="${item.id}" class="btn-tu-choi px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-[11px] font-bold transition">Từ chối</button>
                        </div>
                    </td>
                </tr>`;
            }).join('');

            tbody.querySelectorAll('.btn-xac-nhan').forEach(btn => {
                btn.addEventListener('click', async function () {
                    const approved = await window.PharmaPopup.confirm({
                        type: 'warning',
                        title: 'Xác nhận nhận hàng',
                        message: 'Xác nhận tiếp nhận lô này vào kho Nhà thuốc? Hệ thống sẽ ký TransferReceived trên Ethereum Sepolia.',
                        confirmText: 'Xác nhận nhận'
                    });
                    if (!approved) return;

                    const modal = document.getElementById('modalLoadingBlockchain');
                    const loading = document.getElementById('khungLoadingIcon');
                    const success = document.getElementById('khungSuccessIcon');
                    const failure = document.getElementById('khungFailureIcon');
                    const title = loading?.querySelector('h4');
                    const detail = loading?.querySelector('p');
                    const failText = document.getElementById('failureMessageDetail');

                    btn.disabled = true;
                    btn.textContent = 'Đang ký...';
                    if (loading) loading.classList.remove('hidden');
                    if (success) success.classList.add('hidden');
                    if (failure) failure.classList.add('hidden');
                    if (title) title.textContent = 'Đang ký giao dịch tiếp nhận...';
                    if (detail) detail.textContent = 'Đang chờ Signer ký và Ethereum Sepolia xác nhận.';
                    modal?.classList.remove('hidden');

                    try {
                        const result = await fetchJSON(`/nha-thuoc/api/xac-nhan/${btn.dataset.id}`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                        });
                        const txHash = result.data?.txHash;
                        if (!/^0x[a-fA-F0-9]{64}$/.test(String(txHash || ''))) {
                            throw new Error('Máy chủ chưa trả về txHash Sepolia đã xác nhận.');
                        }

                        modal?.classList.add('hidden');
                        await window.PharmaPopup.show({
                            type: 'success',
                            title: 'Nhà thuốc tiếp nhận thành công',
                            message: result.message || 'TransferReceived đã xác nhận. Quyền sở hữu và tồn kho lô đã chuyển sang Nhà thuốc.',
                            txHash
                        });
                        await Promise.all([loadChoXacNhan(currentPageCho), loadDaTiepNhan(1)]);
                    } catch (err) {
                        modal?.classList.add('hidden');
                        if (failText) failText.textContent = err.message || 'Không thể hoàn tất giao dịch tiếp nhận.';
                        toast(err.message || 'Không thể hoàn tất giao dịch tiếp nhận.', 'error');
                        btn.disabled = false;
                        btn.textContent = 'Xác nhận nhận hàng';
                    }
                });
            });

            tbody.querySelectorAll('.btn-tu-choi').forEach(btn => {
                btn.addEventListener('click', async function () {
                    const approved = await window.PharmaPopup.confirm({
                        type: 'warning',
                        title: 'Từ chối nhận hàng',
                        message: 'Bạn có chắc muốn từ chối lô hàng này?',
                        confirmText: 'Tiếp tục từ chối'
                    });
                    if (!approved) return;

                    const lyDo = await window.PharmaPopup.prompt({
                        type: 'info',
                        title: 'Lý do từ chối',
                        message: 'Nhập lý do từ chối (có thể để trống).',
                        defaultValue: 'Nhà thuốc từ chối tiếp nhận lô hàng',
                        confirmText: 'Ký từ chối'
                    });
                    if (lyDo === null) return;

                    const oldText = btn.textContent;
                    btn.disabled = true;
                    btn.textContent = 'Đang ký...';
                    try {
                        const result = await fetchJSON(`/nha-thuoc/api/tu-choi/${btn.dataset.id}`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ lyDo }),
                        });
                        const txHash = result.data?.txHash;
                        if (!/^0x[a-fA-F0-9]{64}$/.test(String(txHash || ''))) {
                            throw new Error('Máy chủ chưa trả về txHash Sepolia đã xác nhận.');
                        }
                        await window.PharmaPopup.show({
                            type: 'success',
                            title: 'Đã ký từ chối nhận hàng',
                            message: result.message || 'TransferRejected đã xác nhận. Số lượng khả dụng đã được hoàn lại cho Nhà phân phối.',
                            txHash
                        });
                        await loadChoXacNhan(currentPageCho);
                    } catch (err) {
                        toast(err.message, 'error');
                        btn.disabled = false;
                        btn.textContent = oldText || 'Từ chối';
                    }
                });
            });

            renderPagination('cumNutPhanTrang', meta, loadChoXacNhan);
        } catch (err) {
            tbody.innerHTML = `<tr><td colspan="9" class="py-8 text-center text-red-500">${err.message}</td></tr>`;
        }
    }

    // ========== TAB ĐÃ TIẾP NHẬN ==========
    async function loadDaTiepNhan(page = 1) {
        currentPageDa = page;
        const q = document.getElementById('oTimKiemDaNhan')?.value || '';
        const tbody = document.getElementById('thanBangDaNhan') || document.querySelector('#khungTabDaTiepNhan tbody');
        if (!tbody) return;

        tbody.innerHTML = `<tr><td colspan="8" class="py-8 text-center text-slate-400">Đang tải lịch sử...</td></tr>`;

        try {
            const res = await fetchJSON(`/nha-thuoc/api/da-tiep-nhan?page=${page}&per_page=10&q=${encodeURIComponent(q)}`);
            const list = res.data || [];
            const meta = res.meta || {};

            const badge = document.getElementById('badgeSoLuongDaNhan');
            if (badge) badge.textContent = meta.total || 0;

            if (list.length === 0) {
                tbody.innerHTML = `<tr><td colspan="8" class="py-10 text-center text-slate-400">Chưa có lịch sử tiếp nhận</td></tr>`;
                return;
            }

            tbody.innerHTML = list.map(item => `
                <tr class="hover:bg-slate-50/80">
                    <td class="py-3 px-3 font-mono text-[11px]">${(item.maGiaoDich || '').substring(0, 13)}…</td>
                    <td class="py-3 px-3 font-semibold">${item.maLo || '--'}</td>
                    <td class="py-3 px-3">${item.tenSanPham || '--'}</td>
                    <td class="py-3 px-3">${item.benGui || '--'}</td>
                    <td class="py-3 px-3 text-right font-bold">${Number(item.soLuong).toLocaleString('vi-VN')}</td>
                    <td class="py-3 px-2.5 text-center text-[11px]">${item.ngayGui || '--'}</td>
                    <td class="py-3 px-2.5 text-center text-[11px]">${item.ngayNhan || '--'}</td>
                    <td class="py-3 px-2.5 text-center">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700">ĐÃ NHẬN</span>
                    </td>
                </tr>
            `).join('');

            renderPagination('cumNutPhanTrangDaNhan', meta, loadDaTiepNhan);
        } catch (err) {
            tbody.innerHTML = `<tr><td colspan="8" class="py-8 text-center text-red-500">${err.message}</td></tr>`;
        }
    }

    function renderPagination(containerId, meta, callback) {
        const el = document.getElementById(containerId);
        if (!el || !meta || meta.last_page <= 1) {
            if (el) el.innerHTML = '';
            return;
        }
        let html = '';
        for (let i = 1; i <= meta.last_page; i++) {
            html += `<button type="button" class="px-2.5 py-1 rounded ${i === meta.current_page ? 'bg-cyan-600 text-white' : 'bg-slate-100'} text-xs font-bold" data-page="${i}">${i}</button>`;
        }
        el.innerHTML = html;
        el.querySelectorAll('button').forEach(b => {
            b.onclick = () => callback(parseInt(b.dataset.page, 10));
        });
    }

    // ========== TAB SWITCH ==========
    function showTab(tab) {
        if (tab === 'cho') {
            tabCho?.classList.add('bg-cyan-700', 'text-white', 'shadow-sm');
            tabCho?.classList.remove('text-slate-600');
            tabDa?.classList.remove('bg-cyan-700', 'text-white', 'shadow-sm');
            tabDa?.classList.add('text-slate-600');
            khungCho?.classList.remove('hidden');
            khungDa?.classList.add('hidden');
            loadChoXacNhan();
        } else {
            tabDa?.classList.add('bg-cyan-700', 'text-white', 'shadow-sm');
            tabDa?.classList.remove('text-slate-600');
            tabCho?.classList.remove('bg-cyan-700', 'text-white', 'shadow-sm');
            tabCho?.classList.add('text-slate-600');
            khungDa?.classList.remove('hidden');
            khungCho?.classList.add('hidden');
            loadDaTiepNhan();
        }
    }

    tabCho?.addEventListener('click', () => showTab('cho'));
    tabDa?.addEventListener('click', () => showTab('da'));

    document.getElementById('oTimKiemNhan')?.addEventListener('input', debounce(() => loadChoXacNhan(1), 400));
    document.getElementById('oTimKiemDaNhan')?.addEventListener('input', debounce(() => loadDaTiepNhan(1), 400));

    document.getElementById('nutDongModalSuccess')?.addEventListener('click', () => {
        document.getElementById('modalLoadingBlockchain')?.classList.add('hidden');
        loadChoXacNhan(currentPageCho);
        loadDaTiepNhan(1);
    });

    document.getElementById('nutDongModalFailure')?.addEventListener('click', () => {
        document.getElementById('modalLoadingBlockchain')?.classList.add('hidden');
    });


    // ========== TẢI ẢNH QR VÀ ĐỐI CHIẾU NGUỒN GỐC ==========
    const nutTaiQR = document.getElementById('nutQuetQRNhanHang');
    const tepQR = document.getElementById('tepAnhQRNhanHang');
    const khungQR = document.getElementById('khungKetQuaQRNhanHang');
    const anhQR = document.getElementById('anhQRNhanHangPreview');
    const tenTepQR = document.getElementById('tenTepQRNhanHang');
    const maQRText = document.getElementById('maQRNhanHangDaGiaiMa');
    const nutKiemTraQR = document.getElementById('nutKiemTraQRNhanHang');
    const nutXoaQR = document.getElementById('nutXoaQRNhanHang');
    const ketQuaQR = document.getElementById('ketQuaXacThucQRNhanHang');

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, char => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        })[char]);
    }

    function chuanHoaMaQR(rawValue) {
        const raw = String(rawValue || '').trim();
        if (!raw) return '';
        try {
            const parsed = new URL(raw);
            const token = parsed.searchParams.get('maDinhDanh');
            if (token) return token.trim();
            const parts = parsed.pathname.split('/').filter(Boolean);
            const traceIndex = parts.lastIndexOf('tra-cuu-nguon-goc');
            if (traceIndex >= 0 && parts[traceIndex + 1]) {
                return decodeURIComponent(parts[traceIndex + 1]).trim();
            }
        } catch (error) {
            // QR có thể chứa trực tiếp mã định danh thay vì URL.
        }
        return raw;
    }

    function xoaAnhQRNhanHang() {
        if (urlAnhQRNhanHang) {
            URL.revokeObjectURL(urlAnhQRNhanHang);
            urlAnhQRNhanHang = null;
        }
        if (tepQR) tepQR.value = '';
        if (anhQR) {
            anhQR.removeAttribute('src');
            anhQR.classList.add('hidden');
        }
        if (tenTepQR) tenTepQR.textContent = 'Chưa chọn ảnh';
        if (maQRText) maQRText.textContent = '—';
        if (ketQuaQR) {
            ketQuaQR.innerHTML = '';
            ketQuaQR.classList.add('hidden');
        }
        maQRNhanHangDaGiaiMa = '';
        if (nutKiemTraQR) nutKiemTraQR.disabled = true;
        khungQR?.classList.add('hidden');
    }

    async function docMaQRTuAnh(file) {
        if (!file || !file.type.startsWith('image/')) {
            throw new Error('Vui lòng chọn tệp ảnh PNG, JPG, WEBP hoặc GIF.');
        }
        if (file.size > 10 * 1024 * 1024) {
            throw new Error('Ảnh QR vượt quá 10 MB. Vui lòng chọn ảnh nhỏ hơn.');
        }
        if (typeof window.jsQR !== 'function') {
            throw new Error('Không tải được thư viện giải mã QR. Kiểm tra kết nối Internet rồi tải lại trang.');
        }

        const imageUrl = URL.createObjectURL(file);
        const image = new Image();
        try {
            await new Promise((resolve, reject) => {
                image.onload = resolve;
                image.onerror = () => reject(new Error('Không thể đọc tệp ảnh đã chọn.'));
                image.src = imageUrl;
            });

            const maxSide = 1800;
            const scale = Math.min(1, maxSide / Math.max(image.naturalWidth, image.naturalHeight));
            const canvas = document.createElement('canvas');
            canvas.width = Math.max(1, Math.round(image.naturalWidth * scale));
            canvas.height = Math.max(1, Math.round(image.naturalHeight * scale));
            const context = canvas.getContext('2d', { willReadFrequently: true });
            context.drawImage(image, 0, 0, canvas.width, canvas.height);
            const pixels = context.getImageData(0, 0, canvas.width, canvas.height);
            const decoded = window.jsQR(pixels.data, canvas.width, canvas.height, {
                inversionAttempts: 'attemptBoth'
            });
            if (!decoded || !decoded.data) {
                throw new Error('Không tìm thấy mã QR đọc được trong ảnh. Hãy chụp rõ, đủ sáng và không cắt mất viền QR.');
            }
            return { ma: chuanHoaMaQR(decoded.data), url: imageUrl };
        } catch (error) {
            URL.revokeObjectURL(imageUrl);
            throw error;
        }
    }

    async function kiemTraNguonGocQR() {
        if (!maQRNhanHangDaGiaiMa) return;
        if (nutKiemTraQR) {
            nutKiemTraQR.disabled = true;
            nutKiemTraQR.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Đang kiểm tra...';
        }
        if (ketQuaQR) {
            ketQuaQR.classList.remove('hidden');
            ketQuaQR.innerHTML = '<p class="text-xs text-slate-500"><i class="fa-solid fa-spinner fa-spin mr-1"></i> Đang tra cứu dữ liệu lô và đối chiếu trạng thái Blockchain...</p>';
        }

        try {
            const response = await fetch('/api/tra-cuu-nguon-goc/' + encodeURIComponent(maQRNhanHangDaGiaiMa), {
                headers: { 'Accept': 'application/json' }
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Không thể kiểm tra mã QR.');

            const status = String(data.trangThai || 'UNKNOWN').toUpperCase();
            const statusMap = {
                VALID: ['HỢP LỆ', 'bg-emerald-100 text-emerald-800 border-emerald-200'],
                RECALLED: ['ĐÃ THU HỒI', 'bg-rose-100 text-rose-800 border-rose-200'],
                EXPIRED: ['ĐÃ HẾT HẠN', 'bg-amber-100 text-amber-800 border-amber-200'],
                INVALID: ['THÔNG TIN KHÔNG HỢP LỆ', 'bg-rose-100 text-rose-800 border-rose-200'],
                NOT_FOUND: ['KHÔNG TÌM THẤY LÔ', 'bg-slate-100 text-slate-700 border-slate-200'],
                NOT_REGISTERED_ON_CHAIN: ['CHƯA GHI NHẬN ON-CHAIN', 'bg-amber-100 text-amber-800 border-amber-200'],
                UNAVAILABLE: ['CHƯA ĐỌC ĐƯỢC BLOCKCHAIN', 'bg-amber-100 text-amber-800 border-amber-200']
            };
            const [statusLabel, statusClass] = statusMap[status] || ['KHÔNG XÁC ĐỊNH', 'bg-slate-100 text-slate-700 border-slate-200'];
            const maLo = String(data.maLo || '').trim();
            const khopDonCho = maLo && danhSachChoXacNhanHienTai.some(item =>
                String(item.maLo || '').trim().toLowerCase() === maLo.toLowerCase()
            );
            const doiChieuDon = khopDonCho
                ? '<p class="mt-2 text-xs font-semibold text-emerald-700"><i class="fa-solid fa-circle-check mr-1"></i> Mã QR khớp với lô đang chờ Nhà thuốc xác nhận.</p>'
                : '<p class="mt-2 text-xs font-semibold text-amber-700"><i class="fa-solid fa-circle-info mr-1"></i> Không tìm thấy lô này trong danh sách chuyển giao đang chờ Nhà thuốc xác nhận.</p>';
            const txHash = /^0x[a-fA-F0-9]{64}$/.test(String(data.txHash || '')) ? data.txHash : '';
            const txHtml = txHash
                ? '<a class="mt-2 inline-block break-all text-[11px] font-mono text-cyan-800 underline" target="_blank" rel="noopener" href="https://sepolia.etherscan.io/tx/' + encodeURIComponent(txHash) + '">' + escapeHtml(txHash) + '</a>'
                : '<p class="mt-2 text-[11px] text-slate-500">Chưa có txHash đăng ký lô được xác nhận.</p>';

            const typeClass = status === 'VALID' ? 'border-emerald-200 bg-emerald-50/50' : 'border-slate-200 bg-slate-50';
            ketQuaQR.innerHTML = `
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full border px-3 py-1 text-[11px] font-extrabold ${statusClass}">${statusLabel}</span>
                    <span class="text-[11px] text-slate-500">Trạng thái đối chiếu Blockchain: ${escapeHtml(data.chainReadStatus || 'N/A')}</span>
                </div>
                <div class="mt-3 rounded-xl border ${typeClass} p-4 grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <div><span class="block text-slate-500">Tên thuốc</span><strong class="text-slate-900">${escapeHtml(data.tenThuoc || 'Không có dữ liệu')}</strong></div>
                    <div><span class="block text-slate-500">Mã lô</span><strong class="font-mono text-slate-900">${escapeHtml(maLo || 'Không xác định')}</strong></div>
                    <div><span class="block text-slate-500">Nhà sản xuất</span><strong class="text-slate-900">${escapeHtml(data.nhaSanXuat || 'Không có dữ liệu')}</strong></div>
                    <div><span class="block text-slate-500">Hạn sử dụng</span><strong class="font-mono text-slate-900">${escapeHtml(dinhDangNgay(data.hanSuDung || ''))}</strong></div>
                    <div><span class="block text-slate-500">Toàn vẹn hồ sơ sản phẩm</span><strong class="text-slate-900">${escapeHtml(data.productIntegrity || 'N/A')}</strong></div>
                    <div><span class="block text-slate-500">Lô đã tồn tại trên Blockchain</span><strong class="text-slate-900">${data.batchExistsOnChain ? 'Có' : 'Chưa xác nhận'}</strong></div>
                </div>
                ${doiChieuDon}
                ${txHtml}
                <p class="mt-3 text-[11px] text-slate-500">Kết quả kiểm tra chỉ phục vụ đối chiếu. Nhà thuốc vẫn phải kiểm tra hàng thực tế và bấm xác nhận tiếp nhận riêng.</p>
            `;
        } catch (error) {
            ketQuaQR.classList.remove('hidden');
            ketQuaQR.innerHTML = '<p class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs font-semibold text-rose-700">' + escapeHtml(error.message || 'Không thể kiểm tra mã QR.') + '</p>';
        } finally {
            if (nutKiemTraQR) {
                nutKiemTraQR.disabled = !maQRNhanHangDaGiaiMa;
                nutKiemTraQR.innerHTML = '<i class="fa-solid fa-shield-halved mr-1"></i> Kiểm tra mã QR';
            }
        }
    }

    nutTaiQR?.addEventListener('click', () => tepQR?.click());
    tepQR?.addEventListener('change', async () => {
        const file = tepQR.files?.[0];
        if (!file) return;
        xoaAnhQRNhanHang();
        khungQR?.classList.remove('hidden');
        if (tenTepQR) tenTepQR.textContent = 'Đang đọc ảnh...';
        try {
            const decoded = await docMaQRTuAnh(file);
            urlAnhQRNhanHang = decoded.url;
            maQRNhanHangDaGiaiMa = decoded.ma;
            if (!maQRNhanHangDaGiaiMa) throw new Error('Mã QR không chứa mã định danh hợp lệ.');
            if (anhQR) {
                anhQR.src = urlAnhQRNhanHang;
                anhQR.classList.remove('hidden');
            }
            if (tenTepQR) tenTepQR.textContent = file.name;
            if (maQRText) maQRText.textContent = maQRNhanHangDaGiaiMa;
            if (nutKiemTraQR) nutKiemTraQR.disabled = false;
        } catch (error) {
            if (tenTepQR) tenTepQR.textContent = error.message || 'Không thể đọc mã QR.';
            if (ketQuaQR) {
                ketQuaQR.classList.remove('hidden');
                ketQuaQR.innerHTML = '<p class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs font-semibold text-rose-700">' + escapeHtml(error.message) + '</p>';
            }
        }
    });
    nutKiemTraQR?.addEventListener('click', kiemTraNguonGocQR);
    nutXoaQR?.addEventListener('click', xoaAnhQRNhanHang);

    showTab('cho');

    function debounce(fn, delay) {
        let t;
        return (...args) => {
            clearTimeout(t);
            t = setTimeout(() => fn(...args), delay);
        };
    }
});

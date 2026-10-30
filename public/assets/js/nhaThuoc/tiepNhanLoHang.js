/**
 * tiepNhanLoHang.js - Nhà thuốc
 * Kết nối CSDL thật qua API (thay thế hoàn toàn dữ liệu giả)
 */
document.addEventListener('DOMContentLoaded', function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const tabCho = document.getElementById('tabChoXacNhan');
    const tabDa = document.getElementById('tabDaTiepNhan');
    const khungCho = document.getElementById('khungTabChoXacNhan');
    const khungDa = document.getElementById('khungTabDaTiepNhan');

    let currentPageCho = 1;
    let currentPageDa = 1;

    function dinhDangNgay(ngayYMD) {
        if (!ngayYMD) return '--';
        const p = ngayYMD.split('-');
        return p.length === 3 ? `${p[2]}/${p[1]}/${p[0]}` : ngayYMD;
    }

    function toast(msg, type = 'success') {
        alert((type === 'error' ? '❌ ' : '✅ ') + msg);
    }

    async function fetchJSON(url, options = {}) {
        const res = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'X-Requested-With': 'XMLHttpRequest',
                ...(options.headers || {}),
            },
            ...options,
        });
        const data = await res.json();
        if (!res.ok || data.success === false) {
            throw new Error(data.message || 'Có lỗi xảy ra');
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
                        ${isRecalled
                            ? '<span class="text-[11px] text-red-500 font-medium">Không thể nhận</span>'
                            : `<button type="button" data-id="${item.id}" class="btn-xac-nhan px-3 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-[11px] font-bold transition">
                                Xác nhận nhận hàng
                               </button>`
                        }
                    </td>
                </tr>`;
            }).join('');

            tbody.querySelectorAll('.btn-xac-nhan').forEach(btn => {
                btn.addEventListener('click', async function () {
                    if (!confirm('Xác nhận tiếp nhận lô hàng này vào kho?')) return;
                    btn.disabled = true;
                    btn.textContent = 'Đang xử lý...';
                    try {
                        await fetchJSON(`/nha-thuoc/api/xac-nhan/${btn.dataset.id}`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                        });
                        toast('Tiếp nhận thành công!');
                        loadChoXacNhan(currentPageCho);
                        // Cập nhật badge lịch sử nếu đang mở
                    } catch (err) {
                        toast(err.message, 'error');
                        btn.disabled = false;
                        btn.textContent = 'Xác nhận nhận hàng';
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

    showTab('cho');

    function debounce(fn, delay) {
        let t;
        return (...args) => {
            clearTimeout(t);
            t = setTimeout(() => fn(...args), delay);
        };
    }
});

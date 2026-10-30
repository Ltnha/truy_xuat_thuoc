/**
 * quanLyLoHang.js - Nhà phân phối
 * Kết nối CSDL thật qua API (thay thế hoàn toàn dữ liệu giả)
 */
document.addEventListener('DOMContentLoaded', function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const EXPLORER_BASE = 'https://sepolia.etherscan.io/tx/';

    // Tabs
    const tabCho = document.getElementById('tabChoTiepNhan');
    const tabKho = document.getElementById('tabKhoLuuGiu');
    const khungCho = document.getElementById('khungTabChoTiepNhan');
    const khungKho = document.getElementById('khungTabKhoLuuGiu');

    let currentPagePending = 1;
    let currentPageKho = 1;

    // ========== HELPERS ==========
    function dinhDangNgay(ngayYMD) {
        if (!ngayYMD) return '--';
        const p = ngayYMD.split('-');
        return p.length === 3 ? `${p[2]}/${p[1]}/${p[0]}` : ngayYMD;
    }

    function toast(msg, type = 'success') {
        // Dùng alert tạm, có thể thay bằng toast UI của project
        if (type === 'error') {
            alert('❌ ' + msg);
        } else {
            alert('✅ ' + msg);
        }
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

    // ========== TAB CHỜ TIẾP NHẬN ==========
    async function loadPending(page = 1) {
        currentPagePending = page;
        const q = document.getElementById('oTimKiemPending')?.value || '';
        const tbody = document.getElementById('thanBangPending');
        if (!tbody) return;

        tbody.innerHTML = `<tr><td colspan="9" class="py-8 text-center text-slate-400">Đang tải dữ liệu...</td></tr>`;

        try {
            const res = await fetchJSON(`/nha-phan-phoi/api/cho-tiep-nhan?page=${page}&per_page=10&q=${encodeURIComponent(q)}`);
            const list = res.data || [];
            const meta = res.meta || {};

            document.getElementById('badgeSoLuongPending').textContent = meta.total || 0;
            document.getElementById('soLuongPendingHienThi').textContent = `Hiển thị: ${list.length} / ${meta.total || 0} lô`;

            if (list.length === 0) {
                tbody.innerHTML = `<tr><td colspan="9" class="py-10 text-center text-slate-400">Không có lô hàng nào đang chờ tiếp nhận</td></tr>`;
                return;
            }

            tbody.innerHTML = list.map(item => {
                const isRecalled = item.trangThaiLo === 'RECALLED';
                const hanDung = dinhDangNgay(item.hanDung);
                return `
                <tr class="hover:bg-slate-50/80">
                    <td class="py-3 px-3 font-mono text-[11px]">${item.maGiaoDich?.substring(0, 13) || '--'}…</td>
                    <td class="py-3 px-3 font-semibold">${item.maLo || '--'}</td>
                    <td class="py-3 px-3">${item.tenSanPham || '--'}</td>
                    <td class="py-3 px-3">${item.benGui || '--'}</td>
                    <td class="py-3 px-2.5 text-center">${hanDung}</td>
                    <td class="py-3 px-3 text-right font-bold">${Number(item.soLuong).toLocaleString('vi-VN')}</td>
                    <td class="py-3 px-2.5 text-center text-[11px]">${item.ngayGui || '--'}</td>
                    <td class="py-3 px-2.5 text-center">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold ${isRecalled ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700'}">
                            ${isRecalled ? 'THU HỒI' : 'CHỜ NHẬN'}
                        </span>
                    </td>
                    <td class="py-3 px-3 text-center">
                        ${isRecalled
                            ? '<span class="text-[11px] text-red-500 font-medium">Không thể nhận</span>'
                            : `<button type="button" data-id="${item.id}" class="btn-tiep-nhan px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-[11px] font-bold transition">
                                Tiếp nhận
                               </button>`
                        }
                    </td>
                </tr>`;
            }).join('');

            // Bind nút tiếp nhận
            tbody.querySelectorAll('.btn-tiep-nhan').forEach(btn => {
                btn.addEventListener('click', async function () {
                    if (!confirm('Xác nhận tiếp nhận lô hàng này?')) return;
                    btn.disabled = true;
                    btn.textContent = 'Đang xử lý...';
                    try {
                        await fetchJSON(`/nha-phan-phoi/api/tiep-nhan/${btn.dataset.id}`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                        });
                        toast('Tiếp nhận thành công!');
                        loadPending(currentPagePending);
                        loadKho(); // cập nhật luôn tab kho
                    } catch (err) {
                        toast(err.message, 'error');
                        btn.disabled = false;
                        btn.textContent = 'Tiếp nhận';
                    }
                });
            });

            // Phân trang đơn giản
            renderPagination('cumNutPhanTrangPending', meta, loadPending);
        } catch (err) {
            tbody.innerHTML = `<tr><td colspan="9" class="py-8 text-center text-red-500">${err.message}</td></tr>`;
        }
    }

    // ========== TAB KHO + CHUYỂN TIẾP ==========
    async function loadKho() {
        const tbody = document.getElementById('thanBangKho');
        if (!tbody) return;

        tbody.innerHTML = `<tr><td colspan="7" class="py-8 text-center text-slate-400">Đang tải tồn kho...</td></tr>`;

        try {
            const res = await fetchJSON('/nha-phan-phoi/api/kho');
            const list = res.data || [];

            document.getElementById('badgeSoLuongKho').textContent = list.length;

            if (list.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" class="py-10 text-center text-slate-400">Kho trống hoặc không có lô khả dụng</td></tr>`;
                return;
            }

            tbody.innerHTML = list.map(item => `
                <tr class="hover:bg-slate-50/80">
                    <td class="py-3 px-3 font-semibold">${item.maLo}</td>
                    <td class="py-3 px-3">${item.tenSanPham || '--'}</td>
                    <td class="py-3 px-3 text-center">${dinhDangNgay(item.hanDung)}</td>
                    <td class="py-3 px-3 text-right">${Number(item.soLuongHienCo).toLocaleString('vi-VN')}</td>
                    <td class="py-3 px-3 text-right font-bold text-emerald-700">${Number(item.soLuongKhaDung).toLocaleString('vi-VN')}</td>
                    <td class="py-3 px-3 text-center">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700">KHẢ DỤNG</span>
                    </td>
                    <td class="py-3 px-3 text-center">
                        <button type="button"
                            class="btn-chuyen-tiep px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-[11px] font-bold transition"
                            data-lo-id="${item.loThuocId}"
                            data-ma-lo="${item.maLo}"
                            data-ten="${item.tenSanPham}"
                            data-kha-dung="${item.soLuongKhaDung}">
                            Chuyển sang Nhà thuốc
                        </button>
                    </td>
                </tr>
            `).join('');

            // Bind nút chuyển
            tbody.querySelectorAll('.btn-chuyen-tiep').forEach(btn => {
                btn.addEventListener('click', () => openModalChuyen(btn.dataset));
            });
        } catch (err) {
            tbody.innerHTML = `<tr><td colspan="7" class="py-8 text-center text-red-500">${err.message}</td></tr>`;
        }
    }

    // ========== MODAL CHUYỂN GIAO ==========
    async function openModalChuyen(data) {
        // Tạo modal đơn giản nếu chưa có
        let modal = document.getElementById('modalChuyenGiao');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'modalChuyenGiao';
            modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black/40';
            modal.innerHTML = `
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 p-6 space-y-4">
                    <h3 class="text-lg font-bold text-slate-800">Chuyển lô sang Nhà thuốc</h3>
                    <div class="text-sm text-slate-600 space-y-1">
                        <p><strong>Mã lô:</strong> <span id="modalMaLo"></span></p>
                        <p><strong>Sản phẩm:</strong> <span id="modalTenSP"></span></p>
                        <p><strong>Khả dụng:</strong> <span id="modalKhaDung" class="font-bold text-emerald-700"></span></p>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Chọn Nhà thuốc</label>
                        <select id="modalBenNhan" class="w-full border rounded-xl px-3 py-2.5 text-sm"></select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Số lượng chuyển</label>
                        <input type="number" id="modalSoLuong" min="1" class="w-full border rounded-xl px-3 py-2.5 text-sm" />
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" id="modalHuy" class="flex-1 py-2.5 rounded-xl border text-sm font-bold">Hủy</button>
                        <button type="button" id="modalXacNhan" class="flex-1 py-2.5 rounded-xl bg-emerald-600 text-white text-sm font-bold">Xác nhận chuyển</button>
                    </div>
                </div>`;
            document.body.appendChild(modal);

            document.getElementById('modalHuy').onclick = () => modal.remove();
        }

        document.getElementById('modalMaLo').textContent = data.maLo;
        document.getElementById('modalTenSP').textContent = data.ten;
        document.getElementById('modalKhaDung').textContent = Number(data.khaDung).toLocaleString('vi-VN');
        document.getElementById('modalSoLuong').value = data.khaDung;
        document.getElementById('modalSoLuong').max = data.khaDung;

        // Load danh sách nhà thuốc
        const select = document.getElementById('modalBenNhan');
        select.innerHTML = '<option value="">Đang tải...</option>';
        try {
            const res = await fetchJSON('/nha-phan-phoi/api/nha-thuoc');
            select.innerHTML = '<option value="">-- Chọn nhà thuốc --</option>' +
                (res.data || []).map(nt => `<option value="${nt.id}">${nt.tenToChuc}</option>`).join('');
        } catch (e) {
            select.innerHTML = '<option value="">Lỗi tải danh sách</option>';
        }

        document.getElementById('modalXacNhan').onclick = async function () {
            const benNhanId = select.value;
            const soLuong = parseInt(document.getElementById('modalSoLuong').value, 10);

            if (!benNhanId) return toast('Vui lòng chọn nhà thuốc', 'error');
            if (!soLuong || soLuong < 1) return toast('Số lượng không hợp lệ', 'error');
            if (soLuong > parseInt(data.khaDung, 10)) return toast('Vượt quá số lượng khả dụng', 'error');

            this.disabled = true;
            this.textContent = 'Đang gửi...';

            try {
                await fetchJSON('/nha-phan-phoi/api/chuyen-giao', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        loThuocId: parseInt(data.loId, 10),
                        benNhanId: parseInt(benNhanId, 10),
                        soLuong: soLuong,
                    }),
                });
                toast('Đã gửi yêu cầu chuyển giao thành công!');
                modal.remove();
                loadKho();
            } catch (err) {
                toast(err.message, 'error');
                this.disabled = false;
                this.textContent = 'Xác nhận chuyển';
            }
        };
    }

    function renderPagination(containerId, meta, callback) {
        const el = document.getElementById(containerId);
        if (!el || !meta || meta.last_page <= 1) {
            if (el) el.innerHTML = '';
            return;
        }
        let html = '';
        for (let i = 1; i <= meta.last_page; i++) {
            html += `<button type="button" class="px-2.5 py-1 rounded ${i === meta.current_page ? 'bg-blue-600 text-white' : 'bg-slate-100'} text-xs font-bold" data-page="${i}">${i}</button>`;
        }
        el.innerHTML = html;
        el.querySelectorAll('button').forEach(b => {
            b.onclick = () => callback(parseInt(b.dataset.page, 10));
        });
    }

    // ========== TAB SWITCH ==========
    function showTab(tab) {
        if (tab === 'cho') {
            tabCho?.classList.add('bg-blue-700', 'text-white', 'shadow-sm');
            tabCho?.classList.remove('text-slate-600');
            tabKho?.classList.remove('bg-blue-700', 'text-white', 'shadow-sm');
            tabKho?.classList.add('text-slate-600');
            khungCho?.classList.remove('hidden');
            khungKho?.classList.add('hidden');
            loadPending();
        } else {
            tabKho?.classList.add('bg-blue-700', 'text-white', 'shadow-sm');
            tabKho?.classList.remove('text-slate-600');
            tabCho?.classList.remove('bg-blue-700', 'text-white', 'shadow-sm');
            tabCho?.classList.add('text-slate-600');
            khungKho?.classList.remove('hidden');
            khungCho?.classList.add('hidden');
            loadKho();
        }
    }

    tabCho?.addEventListener('click', () => showTab('cho'));
    tabKho?.addEventListener('click', () => showTab('kho'));

    // Tìm kiếm
    document.getElementById('oTimKiemPending')?.addEventListener('input', debounce(() => loadPending(1), 400));

    // Init
    showTab('cho');

    function debounce(fn, delay) {
        let t;
        return (...args) => {
            clearTimeout(t);
            t = setTimeout(() => fn(...args), delay);
        };
    }
});

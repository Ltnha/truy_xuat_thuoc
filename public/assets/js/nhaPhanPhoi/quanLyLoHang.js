/**
 * quanLyLoHang.js - Nhà phân phối
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
                throw new Error('API trả về HTML (HTTP ' + res.status + '). Kiểm tra route/đăng nhập: ' + url);
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
                const isExpired = item.hanDung && item.hanDung < new Date().toISOString().slice(0, 10);
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
                        <div class="flex items-center justify-center gap-1.5 flex-wrap">
                            <button type="button" data-id="${item.id}" class="btn-chi-tiet-pending px-2.5 py-1 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 rounded-lg text-[11px] font-bold transition shadow-2xs" title="Xem chi tiết lô thuốc">
                                <i class="fa-solid fa-eye me-1"></i> Chi tiết
                            </button>
                            ${isRecalled || isExpired
                                ? '<span class="text-[11px] text-amber-600 font-medium">Không thể tiếp nhận</span>'
                                : `<button type="button" data-id="${item.id}" class="btn-tiep-nhan px-2.5 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-[11px] font-bold transition shadow-2xs">
                                    Tiếp nhận
                                </button>`
                            }
                            <button type="button" data-id="${item.id}" class="btn-tu-choi px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-[11px] font-bold transition shadow-2xs">
                                Từ chối
                            </button>
                        </div>
                    </td>
                </tr>`;
            }).join('');

            // Xem chi tiết lô hàng chờ tiếp nhận
            tbody.querySelectorAll('.btn-chi-tiet-pending').forEach(btn => {
                btn.addEventListener('click', function () {
                    const item = list.find(x => String(x.id) === String(this.dataset.id));
                    if (item) moModalChiTietPending(item);
                });
            });

            // Xác nhận tiếp nhận: API chỉ trả thành công sau khi TransferReceived đã mined.
            tbody.querySelectorAll('.btn-tiep-nhan').forEach(btn => {
                btn.addEventListener('click', async function () {
                    const approved = await window.PharmaPopup.confirm({
                        type: 'warning',
                        title: 'Xác nhận tiếp nhận',
                        message: 'Xác nhận nhận lô hàng này vào kho? Thao tác sẽ ký giao dịch TransferReceived trên Sepolia.',
                        confirmText: 'Tiếp nhận'
                    });
                    if (!approved) return;

                    const oldText = btn.textContent;
                    btn.disabled = true;
                    btn.textContent = 'Đang ký...';
                    try {
                        const result = await fetchJSON(`/nha-phan-phoi/api/tiep-nhan/${btn.dataset.id}`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                        });
                        const txHash = result.data?.txHash;
                        if (!/^0x[a-fA-F0-9]{64}$/.test(String(txHash || ''))) {
                            throw new Error('Máy chủ chưa trả về txHash Sepolia đã xác nhận.');
                        }
                        await window.PharmaPopup.show({
                            type: 'success',
                            title: 'Tiếp nhận lô hàng thành công',
                            message: result.message || 'TransferReceived đã được xác nhận. Lô hàng đã vào kho Nhà phân phối.',
                            txHash
                        });
                        await Promise.all([loadPending(currentPagePending), loadKho()]);
                    } catch (err) {
                        toast(err.message, 'error');
                        btn.disabled = false;
                        btn.textContent = oldText || 'Tiếp nhận';
                    }
                });
            });

            // Từ chối: chỉ thông báo thành công khi TransferRejected có txHash thật.
            tbody.querySelectorAll('.btn-tu-choi').forEach(btn => {
                btn.addEventListener('click', async function () {
                    const approved = await window.PharmaPopup.confirm({
                        type: 'warning',
                        title: 'Từ chối tiếp nhận',
                        message: 'Bạn có chắc muốn từ chối lô hàng này?',
                        confirmText: 'Tiếp tục từ chối'
                    });
                    if (!approved) return;

                    const lyDo = await window.PharmaPopup.prompt({
                        type: 'info',
                        title: 'Lý do từ chối',
                        message: 'Nhập lý do từ chối (có thể để trống).',
                        defaultValue: 'Nhà phân phối từ chối nhận hàng',
                        confirmText: 'Ký từ chối'
                    });
                    if (lyDo === null) return;

                    const oldText = btn.textContent;
                    btn.disabled = true;
                    btn.textContent = 'Đang ký...';
                    try {
                        const result = await fetchJSON(`/nha-phan-phoi/api/tu-choi/${btn.dataset.id}`, {
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
                            title: 'Đã ký từ chối tiếp nhận',
                            message: result.message || 'TransferRejected đã được xác nhận. Quyền sở hữu lô vẫn thuộc bên gửi.',
                            txHash
                        });
                        await Promise.all([loadPending(currentPagePending), loadKho()]);
                    } catch (err) {
                        toast(err.message, 'error');
                        btn.disabled = false;
                        btn.textContent = oldText || 'Từ chối';
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

            const badgeKho = document.getElementById('badgeSoLuongKho');
            if (badgeKho) badgeKho.textContent = list.length;
            const soLuongKho = document.getElementById('soLuongKhoHienThi');
            if (soLuongKho) soLuongKho.textContent = 'Hiển thị: ' + list.length + ' lô';

            if (list.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" class="py-10 text-center text-slate-400">Kho trống hoặc không có lô khả dụng</td></tr>`;
                return;
            }

            tbody.innerHTML = list.map(item => {
                const khaDung = Number(item.soLuongKhaDung || 0);
                const dangChuyen = Number(item.soLuongDangChuyenGiao || 0);
                const daGiuCho = dangChuyen > 0;
                const badge = daGiuCho
                    ? '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">ĐANG CHUYỂN GIAO</span>'
                    : khaDung > 0
                        ? '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700">KHẢ DỤNG</span>'
                        : '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">HẾT KHẢ DỤNG</span>';
                const action = `
                    <div class="flex items-center justify-center gap-1.5 flex-wrap">
                        <button type="button" class="btn-chi-tiet-kho px-2.5 py-1 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 rounded-lg text-[11px] font-bold transition shadow-2xs" data-lo-id="${item.loThuocId}" title="Xem chi tiết lô hàng trong kho">
                            <i class="fa-solid fa-eye me-1"></i> Chi tiết
                        </button>
                        ${khaDung > 0 ? `
                            <button type="button" class="btn-chuyen-tiep px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-[11px] font-bold transition shadow-2xs" data-lo-id="${item.loThuocId}" data-ma-lo="${item.maLo}" data-ten="${item.tenSanPham || ''}" data-kha-dung="${khaDung}">
                                <i class="fa-solid fa-truck-fast me-1"></i> Chuyển tiếp
                            </button>
                        ` : `
                            <span class="text-[11px] font-semibold text-amber-700">Đang giữ</span>
                        `}
                    </div>`;

                return `
                    <tr class="hover:bg-slate-50/80">
                        <td class="py-3 px-3 font-semibold whitespace-nowrap">${item.maLo || '--'}</td>
                        <td class="py-3 px-3">${item.tenSanPham || '--'}</td>
                        <td class="py-3 px-2.5 text-center whitespace-nowrap">${dinhDangNgay(item.hanDung)}</td>
                        <td class="py-3 px-3 text-right whitespace-nowrap">${Number(item.soLuongHienCo || 0).toLocaleString('vi-VN')}</td>
                        <td class="py-3 px-3 text-right font-bold ${khaDung > 0 ? 'text-emerald-700' : 'text-amber-700'} whitespace-nowrap">${khaDung.toLocaleString('vi-VN')}</td>
                        <td class="py-3 px-2.5 text-center">${badge}</td>
                        <td class="py-3 px-3 text-center whitespace-nowrap">${action}</td>
                    </tr>`;
            }).join('');

            // Bind nút xem chi tiết kho
            tbody.querySelectorAll('.btn-chi-tiet-kho').forEach(btn => {
                btn.addEventListener('click', function () {
                    const item = list.find(x => String(x.loThuocId) === String(this.dataset.loId));
                    if (item) moModalChiTietKho(item);
                });
            });

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
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-slate-600">Số lượng chuyển</label>
                            <span class="text-[11px] text-slate-400 font-medium flex items-center gap-1"><i class="fa-solid fa-lock text-[10px]"></i> Cố định (Không cho phép sửa)</span>
                        </div>
                        <input type="number" id="modalSoLuong" readonly min="1" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm bg-slate-100 text-slate-700 font-bold cursor-not-allowed select-none outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" />
                        <p class="text-[11px] text-slate-500 mt-1 italic">Hệ thống chuyển giao toàn bộ số lượng khả dụng của lô sang nhà thuốc.</p>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" id="modalHuy" class="flex-1 py-2.5 rounded-xl border text-sm font-bold">Hủy</button>
                        <button type="button" id="modalXacNhan" class="flex-1 py-2.5 rounded-xl bg-emerald-600 text-white text-sm font-bold">Xác nhận chuyển</button>
                    </div>
                </div>`;
            document.body.appendChild(modal);

            document.getElementById('modalHuy').onclick = () => modal.remove();

            // Chặn tăng giảm số lượng bằng phím mũi tên hoặc cuộn chuột
            const inputSoLuong = document.getElementById('modalSoLuong');
            if (inputSoLuong) {
                inputSoLuong.addEventListener('keydown', function (e) {
                    if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
                        e.preventDefault();
                    }
                });
                inputSoLuong.addEventListener('wheel', function (e) {
                    e.preventDefault();
                }, { passive: false });
            }
        }

        document.getElementById('modalMaLo').textContent = data.maLo;
        document.getElementById('modalTenSP').textContent = data.ten;
        document.getElementById('modalKhaDung').textContent = Number(data.khaDung).toLocaleString('vi-VN');
        const inputModalSL = document.getElementById('modalSoLuong');
        if (inputModalSL) {
            inputModalSL.value = data.khaDung;
            inputModalSL.max = data.khaDung;
            inputModalSL.readOnly = true;
        }

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
                const result = await fetchJSON('/nha-phan-phoi/api/chuyen-giao', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        loThuocId: parseInt(data.loId, 10),
                        benNhanId: parseInt(benNhanId, 10),
                        soLuong: soLuong,
                    }),
                });
                const txHash = result.data?.txHash;
                if (!/^0x[a-fA-F0-9]{64}$/.test(String(txHash || ''))) {
                    throw new Error('Máy chủ chưa trả về txHash Sepolia đã xác nhận.');
                }
                modal.remove();
                await window.PharmaPopup.show({
                    type: 'success',
                    title: 'Đã ký chuyển giao sang Nhà thuốc',
                    message: result.message || 'Lô vẫn được lưu trong kho Nhà phân phối; số lượng khả dụng đã được giữ chỗ cho đến khi Nhà thuốc nhận hoặc từ chối.',
                    txHash
                });
                await loadKho();
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

    // ========== MODAL CHI TIẾT LÔ HÀNG CHỜ TIẾP NHẬN ==========
    function moModalChiTietPending(item) {
        const modal = document.getElementById('modalChiTietPending');
        if (!modal) return;

        const maLo = item.maLo || '--';
        document.getElementById('pendingModalMaLo').textContent = maLo;
        document.getElementById('pendingModalSanPham').textContent = item.tenSanPham || '--';
        document.getElementById('pendingModalBenGui').textContent = item.benGui || '--';
        document.getElementById('pendingModalSoLuong').textContent = Number(item.soLuong || 0).toLocaleString('vi-VN') + ' hộp';
        document.getElementById('pendingModalHanDung').textContent = dinhDangNgay(item.hanDung);
        document.getElementById('pendingModalNgayGui').textContent = item.ngayGui || '--';

        const txHash = item.txHash || '';
        const txLink = document.getElementById('pendingModalTxLink');
        const txText = document.getElementById('pendingModalTxHash');
        if (txHash && txHash.startsWith('0x')) {
            if (txText) txText.textContent = txHash;
            if (txLink) {
                txLink.href = EXPLORER_BASE + txHash;
                txLink.classList.remove('pointer-events-none', 'text-slate-400');
                txLink.classList.add('text-cyan-800');
            }
        } else {
            if (txText) txText.textContent = 'Chưa có giao dịch on-chain';
            if (txLink) {
                txLink.removeAttribute('href');
                txLink.classList.add('pointer-events-none', 'text-slate-400');
                txLink.classList.remove('text-cyan-800');
            }
        }

        const nutCopy = document.getElementById('nutCopyPendingTx');
        if (nutCopy) {
            nutCopy.onclick = () => {
                if (txHash) {
                    navigator.clipboard.writeText(txHash);
                    toast('Đã sao chép mã giao dịch vào bộ nhớ tạm.');
                }
            };
        }

        const linkBaoCao = document.getElementById('linkBaoCaoTuPendingDetail');
        if (linkBaoCao) {
            linkBaoCao.href = `/bao-cao-nghi-van?maLo=${encodeURIComponent(maLo)}`;
        }

        const linkTraCuu = document.getElementById('pendingModalTraCuuLink');
        if (linkTraCuu) {
            linkTraCuu.href = `/tra-cuu-nguon-goc?maDinhDanh=${encodeURIComponent(maLo)}`;
        }

        const nutNhan = document.getElementById('nutNhanHangTuDetail');
        if (nutNhan) {
            nutNhan.onclick = () => {
                modal.classList.add('hidden');
                const targetBtn = document.querySelector(`.btn-tiep-nhan[data-id="${item.id}"]`);
                if (targetBtn) targetBtn.click();
            };
        }

        modal.classList.remove('hidden');
    }

    // ========== MODAL CHI TIẾT LÔ HÀNG TRONG KHO ==========
    function moModalChiTietKho(item) {
        const modal = document.getElementById('modalChiTietKho');
        if (!modal) return;

        const maLo = item.maLo || '--';
        document.getElementById('modalKhoMaLoTitle').textContent = maLo;
        document.getElementById('modalKhoSanPham').textContent = item.tenSanPham || '--';
        document.getElementById('modalKhoHanDung').textContent = dinhDangNgay(item.hanDung);
        document.getElementById('modalKhoTongNhap').textContent = Number(item.soLuongHienCo || 0).toLocaleString('vi-VN') + ' hộp';
        document.getElementById('modalKhoKhaDung').textContent = Number(item.soLuongKhaDung || 0).toLocaleString('vi-VN') + ' hộp';

        const txHash = item.txHash || '';
        const txLink = document.getElementById('modalKhoTxLink');
        const txText = document.getElementById('modalKhoTxHash');
        if (txHash && txHash.startsWith('0x')) {
            if (txText) txText.textContent = txHash;
            if (txLink) {
                txLink.href = EXPLORER_BASE + txHash;
                txLink.classList.remove('pointer-events-none', 'text-slate-400');
                txLink.classList.add('text-cyan-800');
            }
        } else {
            if (txText) txText.textContent = 'Chưa có giao dịch on-chain';
            if (txLink) {
                txLink.removeAttribute('href');
                txLink.classList.add('pointer-events-none', 'text-slate-400');
                txLink.classList.remove('text-cyan-800');
            }
        }

        const nutCopy = document.getElementById('nutCopyModalKhoTx');
        if (nutCopy) {
            nutCopy.onclick = () => {
                if (txHash) {
                    navigator.clipboard.writeText(txHash);
                    toast('Đã sao chép mã giao dịch vào bộ nhớ tạm.');
                }
            };
        }

        const timeline = document.getElementById('danhSachTimelineKho');
        if (timeline) {
            const dangChuyen = Number(item.soLuongDangChuyenGiao || 0);
            timeline.innerHTML = `
                <div class="relative pl-2 space-y-1">
                    <span class="absolute -left-[31px] top-1 w-3 h-3 rounded-full bg-blue-600 border-2 border-white ring-2 ring-blue-100"></span>
                    <div class="font-bold text-slate-800">Tiếp nhận nhập kho Nhà phân phối</div>
                    <div class="text-[11px] text-slate-500 font-mono">Tổng lượng đã nhập: <strong class="text-blue-700">${Number(item.soLuongHienCo || 0).toLocaleString('vi-VN')} hộp</strong></div>
                </div>
                ${dangChuyen > 0 ? `
                <div class="relative pl-2 space-y-1">
                    <span class="absolute -left-[31px] top-1 w-3 h-3 rounded-full bg-amber-500 border-2 border-white ring-2 ring-amber-100"></span>
                    <div class="font-bold text-amber-800">Đang khởi tạo chuyển tiếp sang Nhà thuốc</div>
                    <div class="text-[11px] text-slate-500 font-mono">Đang tạm khóa để chờ xác nhận: <strong class="text-amber-700">${dangChuyen.toLocaleString('vi-VN')} hộp</strong></div>
                </div>` : ''}
                <div class="relative pl-2 space-y-1">
                    <span class="absolute -left-[31px] top-1 w-3 h-3 rounded-full bg-emerald-500 border-2 border-white ring-2 ring-emerald-100"></span>
                    <div class="font-bold text-emerald-800">Sẵn sàng phân phối tiếp</div>
                    <div class="text-[11px] text-slate-500 font-mono">Lượng khả dụng: <strong class="text-emerald-700">${Number(item.soLuongKhaDung || 0).toLocaleString('vi-VN')} hộp</strong></div>
                </div>
            `;
        }

        const linkBaoCao = document.getElementById('linkBaoCaoTuKhoDetail');
        if (linkBaoCao) {
            linkBaoCao.href = `/bao-cao-nghi-van?maLo=${encodeURIComponent(maLo)}`;
        }

        const linkTraCuu = document.getElementById('modalKhoTraCuuLink');
        if (linkTraCuu) {
            linkTraCuu.href = `/tra-cuu-nguon-goc?maDinhDanh=${encodeURIComponent(maLo)}`;
        }

        modal.classList.remove('hidden');
    }

    // Đóng modals chi tiết
    document.querySelectorAll('.dong-modal-pending-detail').forEach(btn => {
        btn?.addEventListener('click', () => {
            document.getElementById('modalChiTietPending')?.classList.add('hidden');
        });
    });

    document.querySelectorAll('.dong-modal-chitiet').forEach(btn => {
        btn?.addEventListener('click', () => {
            document.getElementById('modalChiTietKho')?.classList.add('hidden');
        });
    });

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

    // Chặn tăng giảm số lượng trên ô nhập chuyển tiếp (modal chuyển blade nếu có)
    const inpNhapChuyen = document.getElementById('nhapSoLuongChuyen');
    if (inpNhapChuyen) {
        inpNhapChuyen.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
                e.preventDefault();
            }
        });
        inpNhapChuyen.addEventListener('wheel', function (e) {
            e.preventDefault();
        }, { passive: false });
    }

    function debounce(fn, delay) {
        let t;
        return (...args) => {
            clearTimeout(t);
            t = setTimeout(() => fn(...args), delay);
        };
    }
});

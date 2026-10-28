/**
 * Quản lý lô hàng – Nhà phân phối
 * Kết nối API thật:
 *  - GET  /nha-phan-phoi/api/chuyen-giao/cho-nhan
 *  - POST /nha-phan-phoi/api/chuyen-giao/{id}/xac-nhan
 *  - GET  /nha-phan-phoi/api/ton-kho
 */
document.addEventListener('DOMContentLoaded', function () {
    const EXPLORER_BASE_URL = 'https://sepolia.etherscan.io/tx/';
    const ITEMS_PER_PAGE = 5;
    const API_CHO_NHAN = '/nha-phan-phoi/api/chuyen-giao/cho-nhan';
    const API_TON_KHO = '/nha-phan-phoi/api/ton-kho';
    const API_XAC_NHAN = (id) => `/nha-phan-phoi/api/chuyen-giao/${id}/xac-nhan`;

    let trangHienTaiPending = 1;
    let trangHienTaiKho = 1;
    let danhSachPending = [];
    let danhSachKho = [];
    let dangTai = false;
    let itemPendingDangChon = null;

    function csrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function dinhDangNgay(ngayYMD) {
        if (!ngayYMD) return '--';
        const s = String(ngayYMD).substring(0, 10);
        const parts = s.split('-');
        if (parts.length === 3) return `${parts[2]}/${parts[1]}/${parts[0]}`;
        return ngayYMD;
    }

    function layNgayHienTai() {
        return new Date().toISOString().split('T')[0];
    }

    async function apiGet(url) {
        const res = await fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });
        const json = await res.json().catch(() => ({}));
        if (!res.ok || json.success === false) {
            throw new Error(json.message || `Lỗi HTTP ${res.status}`);
        }
        return json;
    }

    async function apiPost(url, body = {}) {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken(),
            },
            credentials: 'same-origin',
            body: JSON.stringify(body),
        });
        const json = await res.json().catch(() => ({}));
        if (!res.ok || json.success === false) {
            throw new Error(json.message || `Lỗi HTTP ${res.status}`);
        }
        return json;
    }

    // ── Tabs ──
    const tabChoTiepNhan = document.getElementById('tabChoTiepNhan');
    const tabKhoLuuGiu = document.getElementById('tabKhoLuuGiu');
    const khungTabChoTiepNhan = document.getElementById('khungTabChoTiepNhan');
    const khungTabKhoLuuGiu = document.getElementById('khungTabKhoLuuGiu');
    const badgePending = document.getElementById('badgeSoLuongPending');
    const badgeKho = document.getElementById('badgeSoLuongKho');

    function activeTab(tabBtn, otherBtn, showEl, hideEl) {
        tabBtn.className = 'flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all bg-blue-700 text-white shadow-sm';
        otherBtn.className = 'flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all text-slate-600 hover:text-blue-700 hover:bg-blue-50';
        showEl.classList.remove('hidden');
        hideEl.classList.add('hidden');
    }

    if (tabChoTiepNhan && tabKhoLuuGiu) {
        tabChoTiepNhan.addEventListener('click', () => {
            activeTab(tabChoTiepNhan, tabKhoLuuGiu, khungTabChoTiepNhan, khungTabKhoLuuGiu);
        });
        tabKhoLuuGiu.addEventListener('click', () => {
            activeTab(tabKhoLuuGiu, tabChoTiepNhan, khungTabKhoLuuGiu, khungTabChoTiepNhan);
        });
    }

    // ── Load API ──
    async function taiDanhSachChoNhan() {
        try {
            const json = await apiGet(API_CHO_NHAN);
            danhSachPending = Array.isArray(json.data) ? json.data : [];
        } catch (e) {
            console.error(e);
            danhSachPending = [];
            alert('Không tải được danh sách chờ nhận: ' + e.message);
        }
        renderBangPending();
        doBoLocBenGui();
    }

    async function taiDanhSachTonKho() {
        try {
            const json = await apiGet(API_TON_KHO);
            danhSachKho = Array.isArray(json.data) ? json.data : [];
        } catch (e) {
            console.error(e);
            danhSachKho = [];
            alert('Không tải được tồn kho: ' + e.message);
        }
        renderBangKho();
    }

    async function taiTatCa() {
        if (dangTai) return;
        dangTai = true;
        try {
            await Promise.all([taiDanhSachChoNhan(), taiDanhSachTonKho()]);
        } finally {
            dangTai = false;
        }
    }

    function tinhKhaDungKho(item) {
        if (typeof item.soLuongKhaDung === 'number') return item.soLuongKhaDung;
        if (String(item.trangThaiLo).toUpperCase() === 'RECALLED') return 0;
        const homNay = layNgayHienTai();
        if (item.hanDung && item.hanDung < homNay) return 0;
        return item.soLuongTongNhap || 0;
    }

    function layTrangThaiKho(item) {
        if (String(item.trangThaiLo).toUpperCase() === 'RECALLED') {
            return { text: 'Đã thu hồi', cls: 'bg-red-100 text-red-700' };
        }
        const homNay = layNgayHienTai();
        if (item.hanDung && item.hanDung < homNay) {
            return { text: 'Hết hạn', cls: 'bg-amber-100 text-amber-800' };
        }
        const khaDung = tinhKhaDungKho(item);
        if (khaDung <= 0) {
            return { text: 'Hết hàng', cls: 'bg-slate-100 text-slate-600' };
        }
        return { text: 'Còn hàng', cls: 'bg-emerald-100 text-emerald-700' };
    }

    // ── Render Pending ──
    const thanBangPending = document.getElementById('thanBangPending');
    const oTimKiemPending = document.getElementById('oTimKiemPending');
    const locBenGuiPending = document.getElementById('locBenGuiPending');
    const locHanDungPending = document.getElementById('locHanDungPending');
    const thongTinPhanTrangPending = document.getElementById('thongTinPhanTrangPending');
    const cumNutPhanTrangPending = document.getElementById('cumNutPhanTrangPending');
    const soLuongPendingHienThi = document.getElementById('soLuongPendingHienThi');

    function doBoLocBenGui() {
        if (!locBenGuiPending) return;
        const current = locBenGuiPending.value || 'TAT_CA';
        const set = new Map();
        danhSachPending.forEach((item) => {
            const key = item.maNhaSanXuat || item.benGuiId || item.benGui;
            if (key && item.benGui) set.set(String(key), item.benGui);
        });
        locBenGuiPending.innerHTML = '<option value="TAT_CA">-- Tất cả bên gửi --</option>';
        set.forEach((ten, ma) => {
            const opt = document.createElement('option');
            opt.value = ma;
            opt.textContent = ten;
            locBenGuiPending.appendChild(opt);
        });
        locBenGuiPending.value = current;
    }

    function renderBangPending() {
        if (!thanBangPending) return;

        const tuKhoa = (oTimKiemPending?.value || '').trim().toLowerCase();
        const benGuiLoc = locBenGuiPending?.value || 'TAT_CA';
        const hanDungLoc = locHanDungPending?.value || 'TAT_CA';
        const homNay = layNgayHienTai();

        const danhSachLoc = danhSachPending.filter((item) => {
            const haystack = [
                item.maGiaoDich,
                item.maChuyenGiao,
                item.maLo,
                item.tenSanPham,
                item.benGui,
            ].join(' ').toLowerCase();
            const matchTuKhoa = !tuKhoa || haystack.includes(tuKhoa);

            let matchBenGui = true;
            if (benGuiLoc !== 'TAT_CA') {
                matchBenGui =
                    String(item.maNhaSanXuat) === benGuiLoc ||
                    String(item.benGuiId) === benGuiLoc ||
                    item.benGui === benGuiLoc;
            }

            let matchHanDung = true;
            if (hanDungLoc === 'HET_HAN') matchHanDung = item.hanDung && item.hanDung < homNay;
            else if (hanDungLoc === 'CON_HAN') matchHanDung = !item.hanDung || item.hanDung >= homNay;

            return matchTuKhoa && matchBenGui && matchHanDung;
        });

        if (badgePending) badgePending.textContent = String(danhSachPending.length);
        if (soLuongPendingHienThi) soLuongPendingHienThi.textContent = `Hiển thị: ${danhSachLoc.length} lô`;

        const tongSoTrang = Math.ceil(danhSachLoc.length / ITEMS_PER_PAGE) || 1;
        if (trangHienTaiPending > tongSoTrang) trangHienTaiPending = tongSoTrang;

        const batDau = (trangHienTaiPending - 1) * ITEMS_PER_PAGE;
        const ketThuc = batDau + ITEMS_PER_PAGE;
        const duLieuTrang = danhSachLoc.slice(batDau, ketThuc);

        thanBangPending.innerHTML = '';

        if (duLieuTrang.length === 0) {
            thanBangPending.innerHTML =
                '<tr><td colspan="9" class="text-center py-8 text-slate-400 italic">Không có lô hàng chờ tiếp nhận.</td></tr>';
            if (thongTinPhanTrangPending) thongTinPhanTrangPending.textContent = 'Không có bản ghi nào';
            if (cumNutPhanTrangPending) cumNutPhanTrangPending.innerHTML = '';
            return;
        }

        if (thongTinPhanTrangPending) {
            thongTinPhanTrangPending.textContent =
                `Đang hiển thị ${batDau + 1} đến ${Math.min(ketThuc, danhSachLoc.length)} trên tổng số ${danhSachLoc.length} lô`;
        }

        duLieuTrang.forEach((item) => {
            const daHetHan = item.hanDung && item.hanDung < homNay;
            const biThuHoi = String(item.trangThaiLo).toUpperCase() === 'RECALLED';
            const coTheNhan = !biThuHoi && !daHetHan && item.doiChieuKhop !== false;
            let lyDoKhoaNhan = '';
            if (biThuHoi) lyDoKhoaNhan = 'Lô đã bị thu hồi';
            else if (daHetHan) lyDoKhoaNhan = 'Lô đã hết hạn sử dụng';
            else if (item.doiChieuKhop === false) lyDoKhoaNhan = 'Đối chiếu blockchain không khớp';

            let hanDungHtml = dinhDangNgay(item.hanDung);
            if (daHetHan) {
                hanDungHtml = `<span class="text-red-600 font-bold">${dinhDangNgay(item.hanDung)}</span>`;
            }

            let nhanTrangThaiHtml =
                '<span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-700">Chờ nhận</span>';
            if (biThuHoi) {
                nhanTrangThaiHtml =
                    '<span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700">Thu hồi</span>';
            } else if (daHetHan) {
                nhanTrangThaiHtml =
                    '<span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">Hết hạn</span>';
            }

            const magd = item.maGiaoDich || item.maChuyenGiao;
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-slate-50/80 transition-colors';
            tr.innerHTML = `
                <td class="py-3 px-3 font-mono font-bold text-cyan-900 whitespace-nowrap text-[11px]" title="${magd}">${String(magd).slice(0, 13)}…</td>
                <td class="py-3 px-3 font-mono font-bold text-slate-900 whitespace-nowrap">${item.maLo || '--'}</td>
                <td class="py-3 px-3 font-semibold text-slate-900">${item.tenSanPham || '--'}</td>
                <td class="py-3 px-3 text-slate-700">${item.benGui || '--'}</td>
                <td class="py-3 px-2.5 text-center whitespace-nowrap">${hanDungHtml}</td>
                <td class="py-3 px-3 text-right font-mono font-bold text-blue-700 whitespace-nowrap">${Number(item.soLuong || 0).toLocaleString()}</td>
                <td class="py-3 px-2.5 text-center text-slate-500 font-mono text-[11px] whitespace-nowrap">${item.ngayGui || '--'}</td>
                <td class="py-3 px-2.5 text-center whitespace-nowrap">${nhanTrangThaiHtml}</td>
                <td class="py-3 px-3 text-center whitespace-nowrap">
                    <div class="inline-flex items-center gap-1.5">
                        <button type="button" class="btn-mo-nhan-hang px-2.5 py-1.5 rounded-lg text-white font-bold text-xs transition-all shadow-2xs flex items-center gap-1 ${coTheNhan ? 'bg-blue-700 hover:bg-blue-800' : 'bg-slate-300 text-slate-500 cursor-not-allowed'}"
                            data-id="${item.id}" data-magd="${magd}" ${!coTheNhan ? 'disabled' : ''} title="${lyDoKhoaNhan}">
                            <i class="fa-solid fa-check"></i> Nhận Hàng
                        </button>
                    </div>
                </td>
            `;
            thanBangPending.appendChild(tr);
        });

        renderPhanTrang(cumNutPhanTrangPending, trangHienTaiPending, tongSoTrang, (trang) => {
            trangHienTaiPending = trang;
            renderBangPending();
        });

        ganSuKienNutPending();
    }

    // ── Render Kho ──
    const thanBangKho = document.getElementById('thanBangKho');
    const oTimKiemKho = document.getElementById('oTimKiemKho');
    const locTrangThaiKho = document.getElementById('locTrangThaiKho');
    const locHanDungKho = document.getElementById('locHanDungKho');
    const thongTinPhanTrangKho = document.getElementById('thongTinPhanTrangKho');
    const cumNutPhanTrangKho = document.getElementById('cumNutPhanTrangKho');

    function renderBangKho() {
        if (!thanBangKho) return;

        const tuKhoa = (oTimKiemKho?.value || '').trim().toLowerCase();
        const ttLoc = locTrangThaiKho?.value || 'TAT_CA';
        const hanLoc = locHanDungKho?.value || 'TAT_CA';
        const homNay = layNgayHienTai();

        const danhSachLoc = danhSachKho.filter((item) => {
            const haystack = [item.maLo, item.tenSanPham].join(' ').toLowerCase();
            const matchTuKhoa = !tuKhoa || haystack.includes(tuKhoa);
            const st = layTrangThaiKho(item);

            let matchTt = true;
            if (ttLoc === 'CON_HANG') matchTt = st.text === 'Còn hàng';
            else if (ttLoc === 'HET_HANG') matchTt = st.text === 'Hết hàng';
            else if (ttLoc === 'THU_HOI') matchTt = st.text === 'Đã thu hồi';
            else if (ttLoc === 'HET_HAN') matchTt = st.text === 'Hết hạn';

            let matchHan = true;
            if (hanLoc === 'HET_HAN') matchHan = item.hanDung && item.hanDung < homNay;
            else if (hanLoc === 'CON_HAN') matchHan = !item.hanDung || item.hanDung >= homNay;

            return matchTuKhoa && matchTt && matchHan;
        });

        if (badgeKho) badgeKho.textContent = String(danhSachKho.length);

        const tongSoTrang = Math.ceil(danhSachLoc.length / ITEMS_PER_PAGE) || 1;
        if (trangHienTaiKho > tongSoTrang) trangHienTaiKho = tongSoTrang;

        const batDau = (trangHienTaiKho - 1) * ITEMS_PER_PAGE;
        const ketThuc = batDau + ITEMS_PER_PAGE;
        const duLieuTrang = danhSachLoc.slice(batDau, ketThuc);

        thanBangKho.innerHTML = '';

        if (duLieuTrang.length === 0) {
            thanBangKho.innerHTML =
                '<tr><td colspan="7" class="text-center py-8 text-slate-400 italic">Kho chưa có lô hàng.</td></tr>';
            if (thongTinPhanTrangKho) thongTinPhanTrangKho.textContent = 'Không có bản ghi nào';
            if (cumNutPhanTrangKho) cumNutPhanTrangKho.innerHTML = '';
            return;
        }

        if (thongTinPhanTrangKho) {
            thongTinPhanTrangKho.textContent =
                `Đang hiển thị ${batDau + 1} đến ${Math.min(ketThuc, danhSachLoc.length)} trên tổng số ${danhSachLoc.length} lô`;
        }

        duLieuTrang.forEach((item) => {
            const st = layTrangThaiKho(item);
            const khaDung = tinhKhaDungKho(item);
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-slate-50/80 transition-colors';
            tr.innerHTML = `
                <td class="py-3 px-3 font-mono font-bold text-slate-900 whitespace-nowrap">${item.maLo || '--'}</td>
                <td class="py-3 px-3 font-semibold text-slate-900">${item.tenSanPham || '--'}</td>
                <td class="py-3 px-2.5 text-center whitespace-nowrap">${dinhDangNgay(item.hanDung)}</td>
                <td class="py-3 px-3 text-right font-mono font-bold text-slate-800">${Number(item.soLuongTongNhap || 0).toLocaleString()}</td>
                <td class="py-3 px-3 text-right font-mono font-bold text-blue-700">${Number(khaDung).toLocaleString()}</td>
                <td class="py-3 px-2.5 text-center whitespace-nowrap">
                    <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold ${st.cls}">${st.text}</span>
                </td>
                <td class="py-3 px-3 text-center whitespace-nowrap">
                    <button type="button" class="btn-chi-tiet-kho px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs border border-slate-300" data-malo="${item.maLo}">
                        <i class="fa-solid fa-eye"></i> Chi tiết
                    </button>
                </td>
            `;
            thanBangKho.appendChild(tr);
        });

        renderPhanTrang(cumNutPhanTrangKho, trangHienTaiKho, tongSoTrang, (trang) => {
            trangHienTaiKho = trang;
            renderBangKho();
        });

        document.querySelectorAll('.btn-chi-tiet-kho').forEach((btn) => {
            btn.addEventListener('click', function () {
                const malo = this.getAttribute('data-malo');
                const item = danhSachKho.find((x) => x.maLo === malo);
                if (item) moChiTietKho(item);
            });
        });
    }

    function renderPhanTrang(container, trangHienTai, tongSoTrang, onPageChange) {
        if (!container) return;
        container.innerHTML = '';
        if (tongSoTrang <= 1) return;

        const mk = (label, page, disabled, active) => {
            const b = document.createElement('button');
            b.type = 'button';
            b.textContent = label;
            b.disabled = !!disabled;
            b.className = active
                ? 'px-2.5 py-1 rounded-lg bg-blue-700 text-white text-xs font-bold'
                : 'px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold disabled:opacity-40';
            b.addEventListener('click', () => onPageChange(page));
            container.appendChild(b);
        };

        mk('‹', Math.max(1, trangHienTai - 1), trangHienTai <= 1, false);
        for (let i = 1; i <= tongSoTrang; i++) {
            mk(String(i), i, false, i === trangHienTai);
        }
        mk('›', Math.min(tongSoTrang, trangHienTai + 1), trangHienTai >= tongSoTrang, false);
    }

    // ── Modal nhận hàng ──
    const modalXacNhanNhan = document.getElementById('modalXacNhanNhan');
    const nutXacNhanNhapKho = document.getElementById('nutXacNhanNhapKho');
    const nhanKetQuaDoiChieu = document.getElementById('nhanKetQuaDoiChieu');
    const linkBaoCaoTuNhanHang = document.getElementById('linkBaoCaoTuNhanHang');

    function moModalNhanHang(item) {
        itemPendingDangChon = item;
        const setText = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.textContent = val ?? '--';
        };
        setText('nhanMaGiaoDich', item.maGiaoDich || item.maChuyenGiao);
        setText('nhanMaLo', item.maLo);
        setText('nhanTenSanPham', item.tenSanPham);
        setText('nhanBenGui', item.benGui);
        setText('nhanSoLuong', `${Number(item.soLuong || 0).toLocaleString()} hộp`);
        setText('nhanHanDung', dinhDangNgay(item.hanDung));

        if (nhanKetQuaDoiChieu) {
            if (item.doiChieuKhop !== false) {
                nhanKetQuaDoiChieu.className = 'inline-flex items-center gap-1.5 font-bold text-emerald-700 text-xs';
                nhanKetQuaDoiChieu.innerHTML = '<i class="fa-solid fa-circle-check"></i> Hợp lệ (có thể tiếp nhận)';
                if (nutXacNhanNhapKho) {
                    nutXacNhanNhapKho.disabled = false;
                    nutXacNhanNhapKho.className =
                        'px-6 py-2.5 bg-blue-700 hover:bg-blue-800 text-white font-bold rounded-xl shadow-md transition-all flex items-center gap-2';
                }
            } else {
                nhanKetQuaDoiChieu.className = 'inline-flex items-center gap-1.5 font-bold text-red-600 text-xs';
                nhanKetQuaDoiChieu.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Không hợp lệ / Lô thu hồi hoặc hết hạn';
                if (nutXacNhanNhapKho) {
                    nutXacNhanNhapKho.disabled = true;
                    nutXacNhanNhapKho.className =
                        'px-6 py-2.5 bg-slate-300 text-slate-500 font-bold rounded-xl transition-all flex items-center gap-2 cursor-not-allowed';
                }
            }
        }

        if (linkBaoCaoTuNhanHang) {
            linkBaoCaoTuNhanHang.href = `/bao-cao-nghi-van?maLo=${encodeURIComponent(item.maLo || '')}`;
        }

        modalXacNhanNhan?.classList.remove('hidden');
    }

    function ganSuKienNutPending() {
        document.querySelectorAll('.btn-mo-nhan-hang').forEach((btn) => {
            btn.addEventListener('click', function () {
                if (this.disabled) return;
                const id = Number(this.getAttribute('data-id'));
                const item = danhSachPending.find((x) => Number(x.id) === id);
                if (item) moModalNhanHang(item);
            });
        });
    }

    // [1. XỬ LÝ NÚT XÁC NHẬN NHẬP KHO]
    if (nutXacNhanNhapKho) {
        nutXacNhanNhapKho.addEventListener('click', async function () {
            if (!itemPendingDangChon || this.disabled) return;

            const id = itemPendingDangChon.id;
            const originalHtml = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Đang xử lý...';

            try {
                const json = await apiPost(API_XAC_NHAN(id));
                alert(json.message || 'Xác nhận nhận hàng thành công!');
                modalXacNhanNhan?.classList.add('hidden');
                itemPendingDangChon = null;
                await taiTatCa();
            } catch (e) {
                alert('Xác nhận thất bại: ' + e.message);
            } finally {
                this.disabled = false;
                this.innerHTML = originalHtml;
            }
        });
    }

    // [2. SỰ KIỆN ĐÓNG MODAL QUA DẤU X VÀ CÁC NÚT HỦY]
    document.querySelectorAll('#modalXacNhanNhan [data-close-modal], #modalXacNhanNhan .fa-xmark, #modalXacNhanNhan button.dong-modal').forEach((btn) => {
        btn.addEventListener('click', () => {
            modalXacNhanNhan?.classList.add('hidden');
        });
    });

    // [3. SỰ KIỆN NÚT "ĐỐI CHIẾU LẠI"]
    document.querySelectorAll('#modalXacNhanNhan button').forEach((btn) => {
        if (btn.textContent.trim().toLowerCase().includes('đối chiếu')) {
            btn.addEventListener('click', function () {
                if (itemPendingDangChon) {
                    moModalNhanHang(itemPendingDangChon);
                    alert('Đã tải lại thông tin đối chiếu!');
                }
            });
        }
    });

    function moChiTietKho(item) {
        const modal = document.getElementById('modalChiTietKho');
        const body = document.getElementById('noiDungChiTietKho');
        if (!modal || !body) {
            const ls = (item.lichSuChuyenGiao || [])
                .map(
                    (cg) =>
                        `• ${cg.loai} | ${cg.maGD} | ${cg.doiTac || ''} | SL ${cg.soLuong} | ${cg.trangThai}`
                )
                .join('\n');
            alert(
                `Lô: ${item.maLo}\nSP: ${item.tenSanPham}\nTồn: ${item.soLuongTongNhap}\nKhả dụng: ${tinhKhaDungKho(item)}\n\nLịch sử:\n${ls || '(trống)'}`
            );
            return;
        }

        const lsHtml = (item.lichSuChuyenGiao || [])
            .map(
                (cg) => `
            <div class="border border-slate-100 rounded-xl p-3 text-xs space-y-1">
                <div class="font-bold text-slate-800">${cg.loai === 'NHAN_VAO' ? 'Nhận vào' : 'Chuyển đi'} — ${cg.maGD}</div>
                <div class="text-slate-600">Đối tác: ${cg.doiTac || '--'}</div>
                <div class="text-slate-600">Số lượng: ${Number(cg.soLuong || 0).toLocaleString()}</div>
                <div class="text-slate-500">Tạo: ${cg.thoiGianKhoiTao || '--'} · Xác nhận: ${cg.thoiGianXacNhan || 'Chưa xác nhận'}</div>
                <div class="text-slate-500">TT: ${cg.trangThai || '--'}</div>
                ${cg.txHash ? `<a class="text-blue-700 underline break-all" target="_blank" href="${EXPLORER_BASE_URL}${cg.txHash}">${cg.txHash}</a>` : ''}
            </div>`
            )
            .join('');

        body.innerHTML = `
            <div class="space-y-2 text-sm">
                <div><span class="text-slate-500">Mã lô:</span> <strong class="font-mono">${item.maLo}</strong></div>
                <div><span class="text-slate-500">Sản phẩm:</span> <strong>${item.tenSanPham || '--'}</strong></div>
                <div><span class="text-slate-500">Hạn dùng:</span> ${dinhDangNgay(item.hanDung)}</div>
                <div><span class="text-slate-500">Tồn:</span> ${Number(item.soLuongTongNhap || 0).toLocaleString()} · Khả dụng: ${Number(tinhKhaDungKho(item)).toLocaleString()}</div>
            </div>
            <div class="mt-4 space-y-2">
                <div class="text-xs font-bold text-slate-700 uppercase tracking-wide">Lịch sử chuyển giao</div>
                ${lsHtml || '<div class="text-slate-400 italic text-xs">Chưa có lịch sử.</div>'}
            </div>
        `;
        modal.classList.remove('hidden');
    }

    oTimKiemPending?.addEventListener('input', () => {
        trangHienTaiPending = 1;
        renderBangPending();
    });
    locBenGuiPending?.addEventListener('change', () => {
        trangHienTaiPending = 1;
        renderBangPending();
    });
    locHanDungPending?.addEventListener('change', () => {
        trangHienTaiPending = 1;
        renderBangPending();
    });
    oTimKiemKho?.addEventListener('input', () => {
        trangHienTaiKho = 1;
        renderBangKho();
    });
    locTrangThaiKho?.addEventListener('change', () => {
        trangHienTaiKho = 1;
        renderBangKho();
    });
    locHanDungKho?.addEventListener('change', () => {
        trangHienTaiKho = 1;
        renderBangKho();
    });

    document.getElementById('nutQuetQRNhanHang')?.addEventListener('click', function () {
        const maNhap = prompt('Nhập / quét mã lô để tìm trong danh sách chờ nhận:', '');
        if (!maNhap) return;
        const item = danhSachPending.find(
            (x) => String(x.maLo || '').toUpperCase() === maNhap.trim().toUpperCase()
        );
        if (!item) {
            alert('Không tìm thấy lô chờ nhận với mã: ' + maNhap);
            return;
        }
        moModalNhanHang(item);
    });

    // Khởi chạy
    taiTatCa();
});
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
    const EXPLORER_BASE = 'https://sepolia.etherscan.io/tx/';

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
                        <div class="flex items-center justify-center gap-1.5 flex-wrap">
                            <button type="button" data-id="${item.id}" class="btn-chi-tiet-pending px-2.5 py-1 bg-cyan-50 hover:bg-cyan-100 text-cyan-800 border border-cyan-200 rounded-lg text-[11px] font-bold transition shadow-2xs" title="Xem chi tiết lô thuốc">
                                <i class="fa-solid fa-eye me-1"></i> Chi tiết
                            </button>
                            ${isRecalled || isExpired
                        ? '<span class="text-[11px] text-amber-600 font-medium">Không thể nhận</span>'
                        : `<button type="button" data-id="${item.id}" class="btn-xac-nhan px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-[11px] font-bold transition shadow-2xs">Xác nhận nhận hàng</button>`
                    }
                            <button type="button" data-id="${item.id}" class="btn-tu-choi px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-[11px] font-bold transition shadow-2xs">Từ chối</button>
                        </div>
                    </td>
                </tr>`;
            }).join('');

            tbody.querySelectorAll('.btn-chi-tiet-pending').forEach(btn => {
                btn.addEventListener('click', function () {
                    const item = list.find(x => String(x.id) === String(this.dataset.id));
                    if (item) moModalChiTietPending(item);
                });
            });

            tbody.querySelectorAll('.btn-xac-nhan').forEach(btn => {
                btn.addEventListener('click', function () {
                    thucHienTiepNhan(this.dataset.id);
                });
            });

            tbody.querySelectorAll('.btn-tu-choi').forEach(btn => {
                btn.addEventListener('click', function () {
                    thucHienTuChoi(this.dataset.id);
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
                    <td class="py-3 px-2.5 text-center text-[11px]">${item.ngayNhan || item.ngayGui || '--'}</td>
                    <td class="py-3 px-2.5 text-center">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700">ĐÃ NHẬN</span>
                    </td>
                    <td class="py-3 px-3 text-center whitespace-nowrap">
                        <button type="button" data-id="${item.id}" class="btn-chi-tiet-danhan px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-lg text-[11px] font-bold transition shadow-2xs" title="Xem chi tiết lô đã tiếp nhận">
                            <i class="fa-solid fa-eye me-1"></i> Chi tiết
                        </button>
                    </td>
                </tr>
            `).join('');

            tbody.querySelectorAll('.btn-chi-tiet-danhan').forEach(btn => {
                btn.addEventListener('click', function () {
                    const item = list.find(x => String(x.id) === String(this.dataset.id));
                    if (item) moModalChiTietDaNhan(item);
                });
            });

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

    // ========== MODAL CHI TIẾT LÔ HÀNG CHỜ TIẾP NHẬN ==========
    function moModalChiTietPending(item) {
        try {
            const modal = document.getElementById('modalChiTietPending');
            if (!modal) return;

            const setTxt = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.textContent = (val !== null && val !== undefined && val !== '') ? val : '--';
            };

            const maLo = item.maLo || '--';
            setTxt('detailMaLoTitle', maLo);
            setTxt('detailSanPham', item.tenSanPham);
            setTxt('detailSoDK', item.soDangKy);
            setTxt('detailNhaSanXuat', item.nhaSanXuat);
            setTxt('detailBenGui', item.benGui);
            setTxt('detailSoLuong', Number(item.soLuong || 0).toLocaleString('vi-VN') + ' hộp');
            setTxt('detailHanDung', dinhDangNgay(item.hanDung));
            setTxt('detailNgayGui', item.ngayGui);

            const txHash = item.txHash || '';
            const txLink = document.getElementById('detailTxLink');
            const txText = document.getElementById('detailTxHash');
            if (txHash && String(txHash).startsWith('0x')) {
                if (txText) txText.textContent = txHash;
                if (txLink) {
                    txLink.href = (EXPLORER_BASE || 'https://sepolia.etherscan.io/tx/') + txHash;
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

            const nutCopy = document.getElementById('nutCopyDetailTx');
            if (nutCopy) {
                nutCopy.onclick = () => {
                    if (txHash) {
                        navigator.clipboard.writeText(txHash);
                        toast('Đã sao chép mã giao dịch vào bộ nhớ tạm.');
                    }
                };
            }

            const linkBaoCao = document.getElementById('linkBaoCaoTuDetail');
            if (linkBaoCao) {
                linkBaoCao.href = `/bao-cao-nghi-van?maLo=${encodeURIComponent(maLo)}`;
            }

            const linkTraCuu = document.getElementById('detailTraCuuLink');
            if (linkTraCuu) {
                linkTraCuu.href = `/tra-cuu-nguon-goc?maDinhDanh=${encodeURIComponent(maLo)}`;
            }

            const nutNhan = document.getElementById('nutChuyenSangNhanHang');
            if (nutNhan) {
                nutNhan.onclick = () => {
                    modal.classList.add('hidden');
                    thucHienTiepNhan(item.id);
                };
            }

            modal.classList.remove('hidden');
        } catch (err) {
            console.error('Lỗi khi mở modal chi tiết chờ tiếp nhận:', err);
        }
    }

    // ========== MODAL CHI TIẾT LÔ HÀNG ĐÃ TIẾP NHẬN ==========
    function moModalChiTietDaNhan(item) {
        try {
            const modal = document.getElementById('modalChiTietDaTiepNhan');
            if (!modal) return;

            const setTxt = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.textContent = (val !== null && val !== undefined && val !== '') ? val : '--';
            };

            const maLo = item.maLo || '--';
            setTxt('daNhanModalMaLoTitle', maLo);
            setTxt('daNhanModalTenThuoc', item.tenSanPham);
            setTxt('daNhanModalSoDK', item.soDangKy);
            setTxt('daNhanModalNhaSanXuat', item.nhaSanXuat);
            setTxt('daNhanModalBenGiao', item.benGui);
            setTxt('daNhanModalHanDung', dinhDangNgay(item.hanDung));
            setTxt('daNhanModalSoLuongDot', Number(item.soLuong || 0).toLocaleString('vi-VN') + ' hộp');
            setTxt('daNhanModalThoiGianGui', item.ngayGui);
            setTxt('daNhanModalThoiGianXacNhan', item.ngayNhan);

            const isRecalled = item.trangThaiLo === 'RECALLED';
            const isExpired = item.hanDung && item.hanDung < new Date().toISOString().slice(0, 10);

            const bannerThuHoi = document.getElementById('daNhanBannerThuHoi');
            if (bannerThuHoi) {
                if (isRecalled) {
                    bannerThuHoi.classList.remove('hidden');
                    setTxt('daNhanLyDoThuHoiText', item.lyDoThuHoi || 'Lô thuốc bị thu hồi theo quyết định của Cơ quan quản lý.');
                } else {
                    bannerThuHoi.classList.add('hidden');
                }
            }

            const bannerHetHan = document.getElementById('daNhanBannerHetHan');
            if (bannerHetHan) {
                bannerHetHan.classList.toggle('hidden', !isExpired);
            }

            const tagHetHan = document.getElementById('daNhanModalHetHanTag');
            if (tagHetHan) {
                tagHetHan.classList.toggle('hidden', !isExpired);
            }

            // Tình trạng tại nhà thuốc
            const tongNhan = Number(item.tongNhanLo || item.soLuong || 0);
            const daBan = Number(item.daBan || 0);
            const conBan = Number(item.soLuongConBan !== undefined ? item.soLuongConBan : (item.soLuong || 0));

            setTxt('daNhanTongNhanLo', tongNhan.toLocaleString('vi-VN') + ' hộp');
            setTxt('daNhanSoLuongDaBan', daBan.toLocaleString('vi-VN') + ' hộp');
            setTxt('daNhanSoLuongConBan', conBan.toLocaleString('vi-VN') + ' hộp');

            const txHash = item.txHash || '';
            const txLink = document.getElementById('daNhanModalTxLink');
            const txText = document.getElementById('daNhanModalTxHash');
            if (txHash && String(txHash).startsWith('0x')) {
                if (txText) txText.textContent = txHash;
                if (txLink) {
                    txLink.href = (EXPLORER_BASE || 'https://sepolia.etherscan.io/tx/') + txHash;
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

            const nutCopy = document.getElementById('nutCopyDaNhanTx');
            if (nutCopy) {
                nutCopy.onclick = () => {
                    if (txHash) {
                        navigator.clipboard.writeText(txHash);
                        toast('Đã sao chép mã giao dịch vào bộ nhớ tạm.');
                    }
                };
            }

            const linkBaoCao = document.getElementById('linkBaoCaoTuDaNhan');
            if (linkBaoCao) {
                linkBaoCao.href = `/bao-cao-nghi-van?maLo=${encodeURIComponent(maLo)}`;
            }

            const linkTraCuu = document.getElementById('daNhanModalTraCuuLink');
            if (linkTraCuu) {
                linkTraCuu.href = `/tra-cuu-nguon-goc?maDinhDanh=${encodeURIComponent(maLo)}`;
            }

            modal.classList.remove('hidden');
        } catch (err) {
            console.error('Lỗi khi mở modal chi tiết đã nhận:', err);
        }
    }

    // Đóng modals chi tiết
    document.querySelectorAll('.dong-modal-detail').forEach(btn => {
        btn?.addEventListener('click', () => {
            document.getElementById('modalChiTietPending')?.classList.add('hidden');
        });
    });

    document.querySelectorAll('.dong-modal-danhan-detail').forEach(btn => {
        btn?.addEventListener('click', () => {
            document.getElementById('modalChiTietDaTiepNhan')?.classList.add('hidden');
        });
    });

    // Đóng modal khi click nền mờ bên ngoài
    ['modalChiTietPending', 'modalChiTietDaTiepNhan'].forEach(mId => {
        const mEl = document.getElementById(mId);
        mEl?.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.getElementById('modalChiTietPending')?.classList.add('hidden');
            document.getElementById('modalChiTietDaTiepNhan')?.classList.add('hidden');
        }
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

    async function thucHienTiepNhan(id) {
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

        if (loading) loading.classList.remove('hidden');
        if (success) success.classList.add('hidden');
        if (failure) failure.classList.add('hidden');
        if (title) title.textContent = 'Đang ký giao dịch tiếp nhận...';
        if (detail) detail.textContent = 'Đang chờ Signer ký và Ethereum Sepolia xác nhận.';
        modal?.classList.remove('hidden');

        try {
            const result = await fetchJSON(`/nha-thuoc/api/xac-nhan/${id}`, {
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
            if (maQRNhanHangDaGiaiMa) {
                kiemTraNguonGocQR();
            }
        } catch (err) {
            modal?.classList.add('hidden');
            if (failText) failText.textContent = err.message || 'Không thể hoàn tất giao dịch tiếp nhận.';
            toast(err.message || 'Không thể hoàn tất giao dịch tiếp nhận.', 'error');
        }
    }

    async function thucHienTuChoi(id) {
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

        try {
            const result = await fetchJSON(`/nha-thuoc/api/tu-choi/${id}`, {
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
            if (maQRNhanHangDaGiaiMa) {
                kiemTraNguonGocQR();
            }
        } catch (err) {
            toast(err.message, 'error');
        }
    }

    window.xuLyTiepNhanTuQR = thucHienTiepNhan;
    window.xuLyTuChoiTuQR = thucHienTuChoi;

    async function docMaQRTuAnh(file) {
        const isSvg = file && (file.type === 'image/svg+xml' || file.name.toLowerCase().endsWith('.svg'));
        if (!file || (!file.type.startsWith('image/') && !isSvg)) {
            throw new Error('Vui lòng chọn tệp ảnh PNG, JPG, WEBP hoặc SVG.');
        }
        if (file.size > 10 * 1024 * 1024) {
            throw new Error('Ảnh QR vượt quá 10 MB. Vui lòng chọn ảnh nhỏ hơn.');
        }

        let imageUrl = '';
        if (isSvg) {
            const svgText = await file.text();
            const svgBlob = new Blob([svgText], { type: 'image/svg+xml;charset=utf-8' });
            imageUrl = URL.createObjectURL(svgBlob);

            // 1. Kiểm tra nhanh nếu SVG có metadata QR được nhúng (data-qr-target hoặc comment PharmaChain-QR)
            const matchDataTarget = svgText.match(/data-qr-target="([^"]+)"/i)
                || svgText.match(/<!--\s*PharmaChain-QR:\s*([^|>\s]+)/i)
                || svgText.match(/data-ma-lo="([^"]+)"/i);

            if (matchDataTarget && matchDataTarget[1]) {
                return { ma: chuanHoaMaQR(matchDataTarget[1]), url: imageUrl };
            }
        } else {
            imageUrl = URL.createObjectURL(file);
        }

        if (typeof window.jsQR !== 'function') {
            throw new Error('Không tải được thư viện giải mã QR. Kiểm tra kết nối Internet rồi tải lại trang.');
        }

        const image = new Image();
        try {
            await new Promise((resolve, reject) => {
                image.onload = resolve;
                image.onerror = () => reject(new Error('Không thể đọc tệp ảnh đã chọn.'));
                image.src = imageUrl;
            });

            const canvas = document.createElement('canvas');
            const targetSize = 600;
            canvas.width = targetSize;
            canvas.height = targetSize;
            const context = canvas.getContext('2d', { willReadFrequently: true });

            // Luôn phủ nền trắng tránh lỗi QR nền trong suốt
            context.fillStyle = '#ffffff';
            context.fillRect(0, 0, targetSize, targetSize);
            context.drawImage(image, 0, 0, targetSize, targetSize);

            const pixels = context.getImageData(0, 0, canvas.width, canvas.height);
            let decoded = window.jsQR(pixels.data, canvas.width, canvas.height, {
                inversionAttempts: 'attemptBoth'
            });

            // Thử scale theo tỷ lệ ảnh gốc nếu ảnh lớn
            if (!decoded && (image.naturalWidth > 0 || image.naturalHeight > 0)) {
                const maxSide = 1800;
                const scale = Math.min(1, maxSide / Math.max(image.naturalWidth || 600, image.naturalHeight || 600));
                const canvas2 = document.createElement('canvas');
                canvas2.width = Math.max(1, Math.round((image.naturalWidth || 600) * scale));
                canvas2.height = Math.max(1, Math.round((image.naturalHeight || 600) * scale));
                const ctx2 = canvas2.getContext('2d', { willReadFrequently: true });
                ctx2.fillStyle = '#ffffff';
                ctx2.fillRect(0, 0, canvas2.width, canvas2.height);
                ctx2.drawImage(image, 0, 0, canvas2.width, canvas2.height);
                const pixels2 = ctx2.getImageData(0, 0, canvas2.width, canvas2.height);
                decoded = window.jsQR(pixels2.data, canvas2.width, canvas2.height, {
                    inversionAttempts: 'attemptBoth'
                });
            }

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
            ketQuaQR.innerHTML = '<p class="text-xs text-slate-500 py-3"><i class="fa-solid fa-spinner fa-spin mr-1"></i> Đang tra cứu dữ liệu lô và đối chiếu trạng thái Blockchain Sepolia...</p>';
        }

        try {
            const response = await fetch('/api/tra-cuu-nguon-goc/' + encodeURIComponent(maQRNhanHangDaGiaiMa), {
                headers: { 'Accept': 'application/json' }
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Không thể kiểm tra mã QR.');

            const status = String(data.trangThai || 'UNKNOWN').toUpperCase();
            const statusMap = {
                VALID: ['HỢP LỆ (ĐÃ XÁC THỰC ON-CHAIN)', 'bg-emerald-100 text-emerald-800 border-emerald-300'],
                RECALLED: ['ĐÃ BỊ THU HỒI', 'bg-rose-100 text-rose-800 border-rose-300'],
                EXPIRED: ['ĐÃ HẾT HẠN SỬ DỤNG', 'bg-amber-100 text-amber-800 border-amber-300'],
                INVALID: ['THÔNG TIN KHÔNG HỢP LỆ', 'bg-rose-100 text-rose-800 border-rose-300'],
                NOT_FOUND: ['KHÔNG TÌM THẤY LÔ', 'bg-slate-100 text-slate-700 border-slate-300'],
                NOT_REGISTERED_ON_CHAIN: ['CHƯA GHI NHẬN ON-CHAIN', 'bg-amber-100 text-amber-800 border-amber-300'],
                UNAVAILABLE: ['CHƯA ĐỌC ĐƯỢC BLOCKCHAIN', 'bg-amber-100 text-amber-800 border-amber-300']
            };
            const [statusLabel, statusClass] = statusMap[status] || ['KHÔNG XÁC ĐỊNH', 'bg-slate-100 text-slate-700 border-slate-200'];
            const maLo = String(data.maLo || '').trim();

            // Đối chiếu với danh sách chờ nhận của Nhà thuốc
            const donCho = maLo ? danhSachChoXacNhanHienTai.find(item =>
                String(item.maLo || '').trim().toLowerCase() === maLo.toLowerCase()
            ) : null;

            let doiChieuDonHtml = '';
            if (donCho) {
                doiChieuDonHtml = `
                    <div class="mt-3 p-3 bg-emerald-50 border border-emerald-200 rounded-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs">
                        <div>
                            <span class="font-bold text-emerald-800 flex items-center gap-1.5">
                                <i class="fa-solid fa-circle-check text-emerald-600"></i> Khớp với phiếu chuyển giao đang chờ Nhà thuốc tiếp nhận!
                            </span>
                            <span class="text-slate-600 text-[11px] block mt-0.5">
                                Bên gửi: <strong>${escapeHtml(donCho.benGui || 'Nhà phân phối')}</strong> • Số lượng: <strong>${Number(donCho.soLuong).toLocaleString('vi-VN')} đơn vị</strong>
                            </span>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <button type="button" onclick="window.xuLyTiepNhanTuQR(${donCho.id})" class="px-3.5 py-1.5 bg-emerald-700 hover:bg-emerald-800 text-white rounded-lg font-bold text-xs shadow-xs transition-all flex items-center gap-1.5">
                                <i class="fa-solid fa-check"></i> Tiếp nhận ngay
                            </button>
                            <button type="button" onclick="window.xuLyTuChoiTuQR(${donCho.id})" class="px-3 py-1.5 bg-white hover:bg-rose-50 text-rose-700 border border-rose-200 rounded-lg font-bold text-xs shadow-xs transition-all flex items-center gap-1.5">
                                <i class="fa-solid fa-xmark"></i> Từ chối
                            </button>
                        </div>
                    </div>`;
            } else {
                doiChieuDonHtml = `
                    <div class="mt-3 p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-600 flex items-center gap-2">
                        <i class="fa-solid fa-circle-info text-slate-400"></i>
                    </div>`;
            }

            const txHash = /^0x[a-fA-F0-9]{64}$/.test(String(data.txHash || '')) ? data.txHash : '';
            const txHtml = txHash
                ? `<div class="mt-2 text-xs text-slate-600 flex items-center gap-2 flex-wrap">
                        <span class="font-semibold text-slate-700">Giao dịch gốc (Sepolia):</span>
                        <a class="inline-block break-all text-[11px] font-mono text-cyan-800 font-bold hover:underline" target="_blank" rel="noopener" href="https://sepolia.etherscan.io/tx/${encodeURIComponent(txHash)}">
                            ${escapeHtml(txHash)} <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
                        </a>
                   </div>`
                : '<p class="mt-2 text-[11px] text-slate-500">Chưa có txHash đăng ký lô được xác nhận.</p>';

            // Sơ đồ luồng đi (hành trình chuyển giao)
            const timeline = Array.isArray(data.hanhTrinh) ? data.hanhTrinh : [];
            let timelineHtml = '';

            if (timeline.length === 0) {
                timelineHtml = `
                    <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl text-center text-xs text-slate-500">
                        Chưa có lịch sử sự kiện luồng đi nào được ghi nhận cho lô này.
                    </div>`;
            } else {
                const timelineItems = timeline.map((step, idx) => {
                    const tieuDeRaw = String(step.tieuDe || '');
                    let tieuDeText = 'Sự kiện lưu thông';
                    let iconHtml = '<i class="fa-solid fa-circle-dot text-cyan-600"></i>';

                    if (tieuDeRaw.includes('PRODUCTION') || tieuDeRaw.includes('BatchRegistered') || tieuDeRaw.includes('REGISTER')) {
                        tieuDeText = '🏭 Khởi tạo & Sản xuất lô thuốc (Nhà sản xuất)';
                        iconHtml = '<i class="fa-solid fa-industry text-emerald-700"></i>';
                    } else if (tieuDeRaw.includes('RECEIVED') || tieuDeRaw.includes('TransferReceived')) {
                        tieuDeText = '📦 Tiếp nhận vào kho thành công';
                        iconHtml = '<i class="fa-solid fa-boxes-packing text-teal-700"></i>';
                    } else if (tieuDeRaw.includes('PENDING') || tieuDeRaw.includes('SUBMITTED') || tieuDeRaw.includes('TransferSubmitted')) {
                        tieuDeText = '🚚 Chuyển giao lô thuốc trong chuỗi cung ứng';
                        iconHtml = '<i class="fa-solid fa-truck-fast text-cyan-700"></i>';
                    } else if (tieuDeRaw.includes('REJECTED') || tieuDeRaw.includes('TransferRejected')) {
                        tieuDeText = '❌ Từ chối tiếp nhận chuyển giao';
                        iconHtml = '<i class="fa-solid fa-ban text-rose-600"></i>';
                    } else if (tieuDeRaw.includes('CANCELLED') || tieuDeRaw.includes('TransferCancelled')) {
                        tieuDeText = '🚫 Bên gửi đã hủy chuyển giao';
                        iconHtml = '<i class="fa-solid fa-xmark text-slate-600"></i>';
                    } else if (tieuDeRaw.includes('RECALL') || tieuDeRaw.includes('BatchRecalled')) {
                        tieuDeText = '⚠️ Thu hồi lô thuốc';
                        iconHtml = '<i class="fa-solid fa-triangle-exclamation text-red-600"></i>';
                    } else if (tieuDeRaw.includes('SOLD')) {
                        tieuDeText = '💊 Bán lẻ đến người bệnh';
                        iconHtml = '<i class="fa-solid fa-prescription-bottle-medical text-indigo-700"></i>';
                    }

                    const txStepHash = String(step.txHash || '');
                    const hasTx = /^0x[a-fA-F0-9]{64}$/.test(txStepHash);
                    const txStepHtml = hasTx
                        ? `<div class="mt-1 flex items-center gap-1.5 font-mono text-[11px] text-cyan-800">
                                <span>txHash:</span>
                                <a href="https://sepolia.etherscan.io/tx/${escapeHtml(txStepHash)}" target="_blank" rel="noopener" class="underline hover:text-cyan-950 font-bold truncate max-w-xs">
                                    ${escapeHtml(txStepHash.substring(0, 10))}...${escapeHtml(txStepHash.substring(txStepHash.length - 8))}
                                </a>
                                <i class="fa-solid fa-arrow-up-right-from-square text-[9px] text-slate-400"></i>
                           </div>`
                        : '';

                    return `
                        <div class="relative pl-7 pb-4 ${idx === timeline.length - 1 ? '' : 'border-l-2 border-emerald-900/15'}">
                            <div class="absolute -left-2.5 top-0 w-5 h-5 rounded-full bg-white border-2 border-emerald-600 flex items-center justify-center text-[10px] shadow-xs">
                                ${iconHtml}
                            </div>
                            <div class="space-y-1">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="font-bold text-slate-900 text-xs">${escapeHtml(tieuDeText)}</span>
                                    <span class="text-[11px] font-mono text-slate-500">${escapeHtml(step.thoiGian || '')}</span>
                                </div>
                                <p class="text-xs text-slate-600 leading-relaxed font-medium">${escapeHtml(step.donVi || '')}</p>
                                ${txStepHtml}
                            </div>
                        </div>
                    `;
                }).join('');

                timelineHtml = `
                    <div class="mt-4 pt-4 border-t border-slate-200">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800 flex items-center gap-2 mb-3">
                            <i class="fa-solid fa-route text-cyan-700"></i> Luồng đi & Lịch sử lưu thông lô thuốc
                        </h4>
                        <div class="space-y-1 bg-white p-4 rounded-xl border border-slate-200/80">
                            ${timelineItems}
                        </div>
                    </div>`;
            }

            const typeClass = status === 'VALID' ? 'border-emerald-300 bg-emerald-50/40' : 'border-slate-200 bg-slate-50';
            ketQuaQR.innerHTML = `
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="rounded-full border px-3 py-1 text-[11px] font-extrabold ${statusClass}">${statusLabel}</span>
                        <span class="text-[11px] text-slate-500">Đối chiếu Blockchain: <strong class="text-slate-700">${escapeHtml(data.chainReadStatus || 'N/A')}</strong></span>
                    </div>
                    <span class="text-[11px] text-emerald-800 font-semibold bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">
                        Ethereum Sepolia Verified
                    </span>
                </div>
                <div class="mt-3 rounded-xl border ${typeClass} p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 text-xs">
                    <div><span class="block text-slate-500">Tên sản phẩm</span><strong class="text-slate-900">${escapeHtml(data.tenThuoc || 'Không có dữ liệu')}</strong></div>
                    <div><span class="block text-slate-500">Mã lô thuốc</span><strong class="font-mono text-slate-900">${escapeHtml(maLo || 'Không xác định')}</strong></div>
                    <div><span class="block text-slate-500">Nhà sản xuất</span><strong class="text-slate-900">${escapeHtml(data.nhaSanXuat || 'Không có dữ liệu')}</strong></div>
                    <div><span class="block text-slate-500">Hạn sử dụng</span><strong class="font-mono text-slate-900">${escapeHtml(dinhDangNgay(data.hanSuDung || ''))}</strong></div>
                    <div><span class="block text-slate-500">Hồ sơ sản phẩm</span><strong class="text-slate-900">${data.productIntegrity === 'MATCH' ? '<span class="text-emerald-700 font-bold">Khớp hồ sơ duyệt</span>' : escapeHtml(data.productIntegrity || 'N/A')}</strong></div>
                    <div><span class="block text-slate-500">Đã ghi nhận on-chain</span><strong class="text-slate-900">${data.batchExistsOnChain ? '<span class="text-emerald-700 font-bold">Đã xác nhận</span>' : 'Chưa ghi nhận'}</strong></div>
                </div>
                ${doiChieuDonHtml}
                ${txHtml}
                ${timelineHtml}
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
            // Tự động kiểm tra nguồn gốc và hiển thị luồng đi ngay sau khi giải mã QR thành công
            await kiemTraNguonGocQR();
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

document.addEventListener('DOMContentLoaded', function () {
    // 1. LẤY CÁC THÀNH PHẦN GIAO DIỆN
    const thanBang = document.getElementById('thanBangLoThuoc');
    const tongSoLoText = document.getElementById('tongSoLoHienThi');
    const thongTinPhanTrangText = document.getElementById('thongTinPhanTrangLo');
    const cumNutPhanTrang = document.getElementById('cumNutPhanTrangLo');

    const oTimKiem = document.getElementById('oTimKiemLo');
    const locTrangThai = document.getElementById('locTrangThaiLo');
    const locHanDung = document.getElementById('locHanDung');

    let tatCaLoThuoc = [];
    let danhSachDaLoc = [];
    let trangHienTai = 1;
    const soDongTrenTrang = 10;
    const SO_COT = 8;

    let pollingInterval = null;
    const batchSigningStates = new Map();
    let dangTheoDoiLoTaoId = null;

    function showPopup(message, type = 'info', title = 'Thông báo', txHash = null) {
        window.PharmaPopup?.show({ type, title, message, txHash });
    }

    // ================== HELPERS ==================
    function getTxList(item) {
        const list = item.blockchain_transactions || item.blockchainTransactions || [];
        return Array.isArray(list) ? list : [];
    }

    function getLatestTx(item) {
        const list = getTxList(item);
        if (list.length === 0) return null;
        return [...list].sort((a, b) => (b.id || 0) - (a.id || 0))[0];
    }

    function getBatchRegistrationTx(item) {
        const list = getTxList(item);
        if (list.length === 0) return null;

        const reg = list.filter(tx => {
            const ev = String(tx.eventName || tx.event_name || '').toUpperCase();
            const hasCg = tx.chuyenGiaoId != null || tx.chuyen_giao_id != null;
            if (hasCg) return false;
            if (ev.includes('TRANSFER')) return false;
            return true;
        });

        if (reg.length > 0) {
            return [...reg].sort((a, b) => (b.id || 0) - (a.id || 0))[0];
        }

        const confirmed = list.filter(tx => {
            const st = String(tx.trangThai || '').toUpperCase();
            const hash = String(tx.txHash || '');
            return (st === 'CONFIRMED' || st === 'SUCCESS') && /^0x[a-fA-F0-9]{64}$/.test(hash);
        });
        if (confirmed.length > 0) {
            return [...confirmed].sort((a, b) => (a.id || 0) - (b.id || 0))[0];
        }
        return null;
    }

    function capNhatTrangThaiModalTaoLo(trangThai, item = null, tx = null) {
        const iconBox = document.getElementById('iconTrangThaiTaoLo');
        const tieuDe = document.getElementById('tieuDeTrangThaiTaoLo');
        const badge = document.getElementById('badgeTrangThaiTaoLo');
        const boxTx = document.getElementById('boxTxHashTaoLo');
        const linkTx = document.getElementById('linkTxHashTaoLo');

        if (!iconBox || !tieuDe || !badge) return;

        if (trangThai === 'CONFIRMED' || trangThai === 'SUCCESS') {
            iconBox.className = 'w-12 h-12 mx-auto rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-2xl';
            iconBox.innerHTML = '<i class="fa-solid fa-circle-check"></i>';

            tieuDe.textContent = 'Đăng Ký Lô Thuốc Thành Công!';

            badge.className = 'inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-full text-xs font-semibold';
            badge.innerHTML = '<i class="fa-solid fa-check-circle text-emerald-600"></i><span>Đã xác nhận giao dịch thành công trên Ethereum Sepolia</span>';

            const hash = String(tx?.txHash || '');
            if (boxTx && linkTx && /^0x[a-fA-F0-9]{64}$/.test(hash)) {
                linkTx.href = `https://sepolia.etherscan.io/tx/${hash}`;
                linkTx.textContent = `${hash.substring(0, 10)}...${hash.substring(hash.length - 8)}`;
                boxTx.classList.remove('hidden');
            }
        } else if (['FAILED', 'REJECTED', 'CANCELLED'].includes(trangThai)) {
            iconBox.className = 'w-12 h-12 mx-auto rounded-full bg-red-100 text-red-600 flex items-center justify-center text-2xl';
            iconBox.innerHTML = '<i class="fa-solid fa-circle-xmark"></i>';

            tieuDe.textContent = 'Đăng Ký Lô Thuốc Thất Bại';

            badge.className = 'inline-flex items-center gap-1.5 px-3 py-1 bg-red-50 text-red-700 border border-red-200 rounded-full text-xs font-semibold';
            badge.innerHTML = `<i class="fa-solid fa-triangle-exclamation text-red-600"></i><span>${escapeHtml(tx?.loiCuoi || 'Giao dịch trên Blockchain không thành công.')}</span>`;

            boxTx?.classList.add('hidden');
        } else {
            iconBox.className = 'w-12 h-12 mx-auto rounded-full bg-amber-100 text-amber-600 flex items-center justify-center text-2xl';
            iconBox.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

            tieuDe.textContent = 'Đang Xử Lý Đăng Ký Lô Thuốc...';

            badge.className = 'inline-flex items-center gap-1.5 px-3 py-1 bg-amber-50 text-amber-800 border border-amber-200 rounded-full text-xs font-semibold';
            badge.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-amber-600"></i><span>Đang ký và xác nhận giao dịch trên Ethereum Sepolia...</span>';

            boxTx?.classList.add('hidden');
        }
    }

    function watchBatchRegistration(items) {
        const modalDangKy = document.getElementById('modalDangKyLo');
        const dangMoModal = modalDangKy && !modalDangKy.classList.contains('hidden');

        (items || []).forEach(item => {
            const tx = getBatchRegistrationTx(item);
            if (!tx) return;
            const id = Number(item.id);
            const status = String(tx.trangThai || '').toUpperCase();
            const previous = batchSigningStates.get(id);
            const hasValidHash = /^0x[a-fA-F0-9]{64}$/.test(String(tx.txHash || ''));

            // Cập nhật trực tiếp màn hình thông báo ngay khi job terminal hoàn tất
            if (dangTheoDoiLoTaoId === id) {
                if ((status === 'CONFIRMED' || status === 'SUCCESS') && hasValidHash) {
                    capNhatTrangThaiModalTaoLo('CONFIRMED', item, tx);
                    dangTheoDoiLoTaoId = null;
                    if (!dangMoModal) {
                        showPopup(
                            `Lô thuốc ${item.maLoNghiepVu || ''} đã được ký và xác nhận thành công trên Ethereum Sepolia. QR của lô đã sẵn sàng.`,
                            'success',
                            'Đăng ký lô thuốc thành công',
                            tx.txHash
                        );
                    }
                } else if (['FAILED', 'REJECTED', 'CANCELLED'].includes(status)) {
                    capNhatTrangThaiModalTaoLo('FAILED', item, tx);
                    dangTheoDoiLoTaoId = null;
                    if (!dangMoModal) {
                        showPopup(
                            tx.loiCuoi || 'Giao dịch đăng ký lô không thể hoàn tất trên Blockchain.',
                            'error',
                            'Đăng ký lô thất bại'
                        );
                    }
                }
            } else if (previous === 'PENDING' && (status === 'CONFIRMED' || status === 'SUCCESS') && hasValidHash) {
                showPopup(
                    `Lô thuốc ${item.maLoNghiepVu || ''} đã được ký và xác nhận thành công trên Ethereum Sepolia. QR của lô đã sẵn sàng.`,
                    'success',
                    'Đăng ký lô thuốc thành công',
                    tx.txHash
                );
            } else if (previous === 'PENDING' && ['FAILED', 'REJECTED', 'CANCELLED'].includes(status)) {
                showPopup(
                    tx.loiCuoi || 'Giao dịch đăng ký lô không thể hoàn tất trên Blockchain.',
                    'error',
                    'Đăng ký lô thất bại'
                );
            }

            batchSigningStates.set(id, status);
        });
    }

    function getLatestTransferTx(item) {
        const list = getTxList(item).filter(tx =>
            tx.chuyenGiaoId != null || tx.chuyen_giao_id != null
            || String(tx.eventName || '').toUpperCase().includes('TRANSFER')
        );
        if (list.length === 0) return null;
        return [...list].sort((a, b) => (b.id || 0) - (a.id || 0))[0];
    }

    function getKhaDung(item) {
        if (item.soLuongKhaDung != null) return Number(item.soLuongKhaDung);
        return Number(item.soLuong || 0);
    }

    const NPP_INTENT_PREFIX = 'pharmachain:npp-intent:';

    function getNppIntent(item) {
        try {
            const key = NPP_INTENT_PREFIX + String(item.maLoNghiepVu || '');
            const raw = sessionStorage.getItem(key);
            return raw ? JSON.parse(raw) : null;
        } catch (_) {
            return null;
        }
    }

    function isTxPending(tx) {
        if (!tx) return false;
        const st = String(tx.trangThai || '').toUpperCase();
        if (['FAILED', 'REJECTED', 'CANCELLED', 'CONFIRMED', 'SUCCESS'].includes(st)) {
            return false;
        }
        return st === 'PENDING';
    }

    function isTxConfirmed(tx) {
        if (!tx || isTxPending(tx)) return false;
        const st = String(tx.trangThai || '').toUpperCase();
        const hash = String(tx.txHash || '');
        if (st === 'FAILED' || st === 'REJECTED' || st === 'CANCELLED') return false;
        return (st === 'CONFIRMED' || st === 'SUCCESS') && /^0x[a-fA-F0-9]{64}$/.test(hash);
    }

    function isTxFailedOrRejected(tx) {
        if (!tx) return false;
        const st = String(tx.trangThai || '').toUpperCase();
        return st === 'FAILED' || st === 'REJECTED' || st === 'CANCELLED';
    }

    function escapeHtml(str) {
        if (str == null) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function formatDate(dateStr) {
        if (!dateStr) return 'N/A';
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return String(dateStr);
        return d.toLocaleDateString('vi-VN');
    }

    // ================== 2. TẢI DỮ LIỆU TỪ CSDL ==================
    async function loadDataFromDB() {
        try {
            const response = await fetch('/nha-san-xuat/api/danh-sach-lo-thuoc', {
                cache: 'no-store',
                headers: { 'Accept': 'application/json' }
            });
            const result = await response.json();

            if (result.success) {
                tatCaLoThuoc = result.data || [];
                watchBatchRegistration(tatCaLoThuoc);
                apDungBoLocVaphanTrang();

                const coPending = dangTheoDoiLoTaoId !== null || tatCaLoThuoc.some(item =>
                    isTxPending(getLatestTx(item)) || isTxPending(getBatchRegistrationTx(item))
                );

                if (coPending) {
                    if (!pollingInterval) {
                        pollingInterval = setInterval(loadDataFromDB, 1500);
                    }
                } else if (pollingInterval) {
                    clearInterval(pollingInterval);
                    pollingInterval = null;
                }
            } else if (thanBang && tatCaLoThuoc.length === 0) {
                thanBang.innerHTML = `<tr><td colspan="${SO_COT}" class="py-6 text-center text-red-500 font-bold">Không thể lấy dữ liệu lô thuốc!</td></tr>`;
            }
        } catch (error) {
            console.error('Lỗi khi tải dữ liệu CSDL:', error);
            if (thanBang && tatCaLoThuoc.length === 0) {
                thanBang.innerHTML = `<tr><td colspan="${SO_COT}" class="py-6 text-center text-red-500 font-bold">Lỗi kết nối máy chủ CSDL!</td></tr>`;
            }
        }
    }

    window.loadDataFromDB = loadDataFromDB;

    // ================== 3. LỌC & PHÂN TRANG ==================
    function apDungBoLocVaphanTrang() {
        const tuKhoa = oTimKiem ? oTimKiem.value.toLowerCase().trim() : '';
        const tt = locTrangThai ? locTrangThai.value : 'TAT_CA';
        const hd = locHanDung ? locHanDung.value : 'TAT_CA';

        const today = new Date();
        const sixMonthsLater = new Date();
        sixMonthsLater.setMonth(today.getMonth() + 6);

        danhSachDaLoc = tatCaLoThuoc.filter(item => {
            const maLo = (item.maLoNghiepVu || '').toLowerCase();
            const tenThuoc = (item.san_pham?.tenSanPham || item.sanPham?.tenSanPham || '').toLowerCase();
            const khopTuKhoa = maLo.includes(tuKhoa) || tenThuoc.includes(tuKhoa);

            let khopTrangThai = true;
            if (tt !== 'TAT_CA' && tt !== 'HET_HAN') {
                khopTrangThai = item.trangThai === tt;
            }

            let khopHanDung = true;
            if (item.hanSuDung) {
                const ngayHetHan = new Date(item.hanSuDung);
                if (hd === 'CON_HAN' || tt === 'HET_HAN') {
                    if (tt === 'HET_HAN') khopHanDung = ngayHetHan <= today;
                    else if (hd === 'CON_HAN') khopHanDung = ngayHetHan > today;
                }
                if (hd === 'CAN_HAN') khopHanDung = ngayHetHan > today && ngayHetHan <= sixMonthsLater;
                else if (hd === 'DA_HET_HAN') khopHanDung = ngayHetHan <= today;
                else if (hd === 'CON_HAN') khopHanDung = ngayHetHan > today;
            }

            return khopTuKhoa && khopTrangThai && khopHanDung;
        });

        trangHienTai = 1;
        renderBangAndPhanTrang();
    }

    // ================== 4. RENDER BẢNG ==================
    function renderBangAndPhanTrang() {
        const tongSoRecord = danhSachDaLoc.length;
        if (tongSoLoText) tongSoLoText.innerText = `Hiển thị: ${tongSoRecord} lô`;

        if (!thanBang) return;

        if (tongSoRecord === 0) {
            thanBang.innerHTML = `<tr><td colspan="${SO_COT}" class="py-8 text-center text-slate-400">Không tìm thấy lô thuốc nào phù hợp.</td></tr>`;
            if (thongTinPhanTrangText) thongTinPhanTrangText.innerText = 'Đang hiển thị 0 lô';
            if (cumNutPhanTrang) cumNutPhanTrang.innerHTML = '';
            return;
        }

        const tongSoTrang = Math.ceil(tongSoRecord / soDongTrenTrang);
        if (trangHienTai > tongSoTrang) trangHienTai = tongSoTrang;

        const batDau = (trangHienTai - 1) * soDongTrenTrang;
        const ketThuc = Math.min(batDau + soDongTrenTrang, tongSoRecord);
        const danhSachTrang = danhSachDaLoc.slice(batDau, ketThuc);

        thanBang.innerHTML = danhSachTrang.map(item => {
            const batchTx = getBatchRegistrationTx(item);
            const transferTx = getLatestTransferTx(item);
            const latestTx = getLatestTx(item);

            const batchConfirmed = isTxConfirmed(batchTx);
            const batchPending = isTxPending(batchTx);

            const cgInfo = item.chuyenGiaoGanNhat || item.chuyen_giao_gan_nhat || null;
            const cgStatus = cgInfo ? String(cgInfo.trangThai || '').toUpperCase() : '';
            const coChuyenGiaoCho = !!(item.coChuyenGiaoCho || item.co_chuyen_giao_cho);
            const transferPending = coChuyenGiaoCho || (isTxPending(transferTx) && cgStatus === 'PENDING');
            const transferFailed = ['CANCELLED', 'REJECTED', 'FAILED'].includes(cgStatus)
                || isTxFailedOrRejected(transferTx);

            let txHtml = `<span class="text-slate-400 italic text-[11px]">Chưa gửi</span>`;
            const displayTx = batchTx || latestTx;
            const displayHash = displayTx ? displayTx.txHash : null;

            if (batchPending) {
                txHtml = `<span class="inline-flex items-center gap-1.5 px-2 py-0.5 bg-amber-50 text-amber-800 border border-amber-200 rounded text-[11px] font-semibold">
                    <i class="fa-solid fa-spinner fa-spin text-amber-600"></i> Đang xử lý Queue
                </span>`;
            } else if (batchTx && isTxFailedOrRejected(batchTx)) {
                txHtml = `<span class="inline-flex items-center gap-1 px-2 py-0.5 bg-red-50 text-red-700 border border-red-200 rounded text-[11px] font-semibold">Ghi lô thất bại</span>`;
            } else if (batchConfirmed && displayHash && /^0x[a-fA-F0-9]{64}$/.test(String(displayHash))) {
                const short = displayHash.length > 14
                    ? `${displayHash.substring(0, 8)}...${displayHash.substring(displayHash.length - 6)}`
                    : displayHash;
                txHtml = `<a href="https://sepolia.etherscan.io/tx/${displayHash}" target="_blank" rel="noopener"
                    class="text-cyan-800 bg-cyan-50 px-2 py-0.5 rounded border border-cyan-200 hover:underline font-mono text-[11px]"
                    title="${escapeHtml(displayHash)}">${escapeHtml(short)}</a>`;
                if (transferFailed) {
                    txHtml += `<div class="mt-0.5 text-[10px] text-rose-600 font-semibold">Chuyển giao bị từ chối</div>`;
                } else if (transferPending) {
                    txHtml += `<div class="mt-0.5 text-[10px] text-amber-600 font-semibold">Đang chuyển giao...</div>`;
                }
            } else if (transferFailed) {
                txHtml = `<span class="inline-flex items-center gap-1 px-2 py-0.5 bg-rose-50 text-rose-700 border border-rose-200 rounded text-[11px] font-semibold">CG bị từ chối</span>`;
            }

            let badgeStatusHtml = renderBadgeTrangThai(item.trangThai);
            if (batchPending) {
                badgeStatusHtml = `<span class="px-2 py-0.5 bg-amber-50 text-amber-700 border border-amber-200 rounded-full text-[10px] font-bold">Chờ On-chain</span>`;
            } else if (transferPending) {
                badgeStatusHtml = `<span class="px-2 py-0.5 bg-cyan-50 text-cyan-800 border border-cyan-200 rounded-full text-[10px] font-bold">Đang chờ NPP nhận</span>`;
            } else if (transferFailed && getKhaDung(item) > 0) {
                badgeStatusHtml = `<span class="px-2 py-0.5 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-full text-[10px] font-bold">Sẵn sàng chuyển</span>`;
            }

            const tenSp = item.san_pham?.tenSanPham || item.sanPham?.tenSanPham || 'N/A';
            const soDk = item.san_pham?.soDangKy || item.sanPham?.soDangKy || 'N/A';
            const dvt = item.donViTinh || item.don_vi_tinh || 'Hộp';
            const soLuong = Number(item.soLuongHienCo ?? item.soLuong ?? 0);
            const soLuongDangChuyenGiao = Number(item.soLuongDangChuyenGiao || 0);
            const khaDung = getKhaDung(item);

            const canTransfer = batchConfirmed && khaDung > 0
                && item.trangThai !== 'RECALLED'
                && !coChuyenGiaoCho;

            return `
                <tr class="hover:bg-slate-50/80 transition-colors">
                    <td class="py-3 px-2.5 font-mono font-bold text-slate-900 whitespace-nowrap">${escapeHtml(item.maLoNghiepVu || '')}</td>
                    <td class="py-3 px-2 whitespace-nowrap font-mono text-[11px]">${txHtml}</td>
                    <td class="py-3 px-2.5">
                        <div class="font-bold text-slate-800">${escapeHtml(tenSp)}</div>
                        <div class="text-[10px] text-slate-400 font-mono">SĐK: ${escapeHtml(soDk)}</div>
                    </td>
                    <td class="py-3 px-2 text-center font-mono text-slate-700 whitespace-nowrap">${formatDate(item.hanSuDung)}</td>
                    <td class="py-3 px-2 text-right font-mono font-bold text-slate-800 whitespace-nowrap">
                        ${soLuong.toLocaleString('vi-VN')} <span class="text-[11px] font-normal text-slate-500">${escapeHtml(dvt)}</span>
                    </td>
                    <td class="py-3 px-2 text-right font-mono whitespace-nowrap">
                        <div class="font-bold ${soLuongDangChuyenGiao > 0 ? 'text-amber-700' : 'text-emerald-700'}">
                            ${khaDung.toLocaleString('vi-VN')} <span class="text-[11px] font-normal text-slate-500">${escapeHtml(dvt)}</span>
                        </div>
                        ${soLuongDangChuyenGiao > 0 ? `<div class="mt-0.5 text-[10px] font-semibold text-amber-600">Đang giữ: ${soLuongDangChuyenGiao.toLocaleString('vi-VN')} ${escapeHtml(dvt)}</div>` : ''}
                    </td>
                    <td class="py-3 px-2 text-center whitespace-nowrap">${badgeStatusHtml}</td>
                    <td class="py-3 px-2.5 text-center whitespace-nowrap">
                        <div class="flex items-center justify-center gap-1.5">
                            <a href="/nha-san-xuat/lo-thuoc/${item.id}/chi-tiet"
                               class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-lg text-[11px] font-bold transition-all"
                               title="Xem chi tiết lô thuốc">
                                <i class="fa-solid fa-eye me-1"></i> Chi tiết
                            </a>
                            ${canTransfer ? `
                                <button type="button"
                                    onclick="moModalPhanPhoiDirect(${Number(item.id)}, ${getNppIntent(item)?.id ? Number(getNppIntent(item).id) : 'null'})"
                                    class="px-2.5 py-1 bg-cyan-800 hover:bg-cyan-900 text-white rounded-lg text-[11px] font-bold shadow-sm transition-all"
                                    title="Khởi tạo chuyển giao lô">
                                    <i class="fa-solid fa-truck-fast me-1"></i> ${getNppIntent(item)?.name ? 'Gửi đến ' + escapeHtml(getNppIntent(item).name) : 'Chuyển giao'}
                                </button>
                            ` : ''}
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        if (thongTinPhanTrangText) {
            thongTinPhanTrangText.innerText = `Đang hiển thị ${batDau + 1} đến ${ketThuc} trong tổng số ${tongSoRecord} lô`;
        }

        renderNutPhanTrang(tongSoTrang);
    }

    // ================== 5. MỞ MODAL CHUYỂN GIAO ==================
    async function taiDanhSachNhaPhanPhoi(select) {
        if (!select) return;
        select.innerHTML = '<option value="">Đang tải nhà phân phối đã xét duyệt...</option>';
        select.disabled = true;
        try {
            const response = await fetch('/nha-san-xuat/api/nha-phan-phoi', {
                headers: { 'Accept': 'application/json' }
            });
            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Không tải được danh sách nhà phân phối.');
            }

            const items = Array.isArray(result.data) ? result.data : [];
            select.innerHTML = '<option value="">-- Chọn nhà phân phối đã duyệt --</option>';
            if (items.length === 0) {
                select.innerHTML += '<option value="" disabled>Chưa có nhà phân phối nào được cơ quan quản lý xét duyệt</option>';
            } else {
                items.forEach(item => {
                    const option = document.createElement('option');
                    option.value = String(item.id);
                    option.textContent = item.maSoThue
                        ? item.tenToChuc + ' (MST: ' + item.maSoThue + ')'
                        : item.tenToChuc;
                    select.appendChild(option);
                });
            }
        } catch (error) {
            console.error(error);
            select.innerHTML = '<option value="">Không tải được danh sách nhà phân phối</option>';
            showPopup(error.message || 'Không thể tải danh sách nhà phân phối đã duyệt.', 'error', 'Không tải được danh sách');
        } finally {
            select.disabled = false;
        }
    }

    window.moModalPhanPhoiDirect = async function (id, nppDuocChon = null) {
        const item = tatCaLoThuoc.find(l => Number(l.id) === Number(id));
        if (!item) {
            showPopup('Không tìm thấy thông tin lô thuốc.', 'error', 'Không tìm thấy lô thuốc');
            return;
        }

        const batchTx = getBatchRegistrationTx(item);
        if (!isTxConfirmed(batchTx)) {
            showPopup('Lô thuốc chưa được ghi nhận On-chain thành công. Vui lòng đợi đồng bộ xong trước khi chuyển giao.', 'warning', 'Lô chưa sẵn sàng');
            return;
        }
        if (item.coChuyenGiaoCho || item.co_chuyen_giao_cho) {
            showPopup('Lô này đang có yêu cầu chuyển giao chờ Nhà phân phối xác nhận.', 'warning', 'Lô đang chuyển giao');
            return;
        }

        const textMaLo = document.getElementById('phanPhoiMaLoText');
        const textSoLuong = document.getElementById('phanPhoiSoLuongConLaiText');
        const hid = document.getElementById('phanPhoiLoThuocId');
        const inputSl = document.getElementById('soLuongPhanPhoi');
        const loiSl = document.getElementById('loiSoLuongPhanPhoi');
        const modalPP = document.getElementById('modalPhanPhoiLo');
        const selectNpp = document.getElementById('chonNhaPhanPhoi');
        const khaDung = getKhaDung(item);
        const dvt = item.donViTinh || item.don_vi_tinh || 'Hộp';

        if (textMaLo) textMaLo.innerText = item.maLoNghiepVu || '';
        if (textSoLuong) textSoLuong.innerText = khaDung.toLocaleString('vi-VN') + ' ' + dvt;
        if (hid) hid.value = item.id;
        if (inputSl) {
            inputSl.value = String(khaDung);
            inputSl.max = String(khaDung);
            inputSl.min = String(khaDung);
            inputSl.readOnly = true;
        }
        if (loiSl) loiSl.classList.add('hidden');
        if (selectNpp) selectNpp.value = '';

        if (modalPP) modalPP.classList.remove('hidden');
        await taiDanhSachNhaPhanPhoi(selectNpp);
        if (selectNpp && nppDuocChon) selectNpp.value = String(nppDuocChon);
    };

    // ================== 6. SUBMIT CHUYỂN GIAO (API THẬT) ==================
    const formPP = document.getElementById('formSubmitPhanPhoi');
    if (formPP) {
        formPP.addEventListener('submit', async function (e) {
            e.preventDefault();

            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const loThuocId = document.getElementById('phanPhoiLoThuocId')?.value;
            const benNhanId = document.getElementById('chonNhaPhanPhoi')?.value;
            const soLuong = Number(document.getElementById('soLuongPhanPhoi')?.value);
            const loiSl = document.getElementById('loiSoLuongPhanPhoi');
            const btn = formPP.querySelector('button[type="submit"]');
            const original = btn ? btn.innerHTML : '';

            if (!loThuocId || !benNhanId || !Number.isInteger(soLuong) || soLuong < 1) {
                showPopup('Vui lòng chọn nhà phân phối đã duyệt.', 'warning', 'Thiếu thông tin chuyển giao');
                return;
            }
            if (loiSl) loiSl.classList.add('hidden');

            try {
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Đang ký và chờ Sepolia xác nhận...';
                }

                const res = await fetch('/nha-san-xuat/api/chuyen-giao', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({
                        loThuocId: Number(loThuocId),
                        benNhanId: Number(benNhanId),
                        soLuong: soLuong,
                    }),
                });

                const json = await res.json();
                if (!res.ok || !json.success) {
                    throw new Error(json.message || 'Ký chuyển giao thất bại.');
                }

                const txHash = json.data?.txHash || '';
                if (!/^0x[a-fA-F0-9]{64}$/.test(txHash)) {
                    throw new Error('Máy chủ không trả về txHash Sepolia hợp lệ. Không thể xác nhận chuyển giao.');
                }

                const loDangChuyen = tatCaLoThuoc.find(item => Number(item.id) === Number(loThuocId));
                if (loDangChuyen?.maLoNghiepVu) {
                    sessionStorage.removeItem(NPP_INTENT_PREFIX + String(loDangChuyen.maLoNghiepVu));
                }
                showPopup(json.message || 'Chuyển giao đã được xác nhận on-chain.', 'success', 'Đã ký chuyển giao thành công', txHash);
                document.getElementById('modalPhanPhoiLo')?.classList.add('hidden');
                await loadDataFromDB();
            } catch (err) {
                console.error(err);
                showPopup(err.message || 'Không thể hoàn tất ký chuyển giao. CSDL chưa ghi nhận chuyển giao.', 'error', 'Ký chuyển giao thất bại');
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = original;
                }
            }
        });
    }

    // ================== 7. PHÂN TRANG & BADGE ==================
    function renderNutPhanTrang(tongSoTrang) {
        if (!cumNutPhanTrang) return;
        let htmlButtons = '';

        htmlButtons += `
            <button type="button" class="px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50 font-bold disabled:opacity-40 disabled:cursor-not-allowed"
                ${trangHienTai === 1 ? 'disabled' : ''} data-page="${trangHienTai - 1}">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
        `;

        for (let i = 1; i <= tongSoTrang; i++) {
            if (i === trangHienTai) {
                htmlButtons += `<button type="button" class="px-3 py-1.5 bg-emerald-700 text-white rounded-lg font-bold">${i}</button>`;
            } else {
                htmlButtons += `<button type="button" class="px-3 py-1.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-lg font-bold" data-page="${i}">${i}</button>`;
            }
        }

        htmlButtons += `
            <button type="button" class="px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50 font-bold disabled:opacity-40 disabled:cursor-not-allowed"
                ${trangHienTai === tongSoTrang ? 'disabled' : ''} data-page="${trangHienTai + 1}">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        `;

        cumNutPhanTrang.innerHTML = htmlButtons;

        cumNutPhanTrang.querySelectorAll('button[data-page]').forEach(btn => {
            btn.addEventListener('click', function () {
                const p = parseInt(this.getAttribute('data-page'), 10);
                if (!isNaN(p) && p >= 1) {
                    trangHienTai = p;
                    renderBangAndPhanTrang();
                }
            });
        });
    }

    function renderBadgeTrangThai(trangThai) {
        switch (trangThai) {
            case 'CREATED':
                return `<span class="px-2 py-0.5 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-full text-[10px] font-bold">Đã tạo (Mới)</span>`;
            case 'IN_TRANSIT':
                return `<span class="px-2 py-0.5 bg-cyan-50 text-cyan-800 border border-cyan-200 rounded-full text-[10px] font-bold">Đang phân phối</span>`;
            case 'RECALLED':
                return `<span class="px-2 py-0.5 bg-red-50 text-red-700 border border-red-200 rounded-full text-[10px] font-bold">Đã thu hồi</span>`;
            default:
                return `<span class="px-2 py-0.5 bg-slate-100 text-slate-700 border border-slate-200 rounded-full text-[10px] font-bold">${escapeHtml(trangThai || 'Khởi tạo')}</span>`;
        }
    }

    // ================== 8. SỰ KIỆN LỌC ==================
    if (oTimKiem) oTimKiem.addEventListener('input', apDungBoLocVaphanTrang);
    if (locTrangThai) locTrangThai.addEventListener('change', apDungBoLocVaphanTrang);
    if (locHanDung) locHanDung.addEventListener('change', apDungBoLocVaphanTrang);

    // ================== 9. ĐÓNG MODAL PHÂN PHỐI / CHI TIẾT ==================
    document.querySelectorAll('#nutDongModalPhanPhoi, #nutHuyPhanPhoi').forEach(btn => {
        btn?.addEventListener('click', () => {
            document.getElementById('modalPhanPhoiLo')?.classList.add('hidden');
        });
    });

    document.querySelectorAll('#nutDongModalChiTietLo, #nutDongModalChiTietLoBottom').forEach(btn => {
        btn?.addEventListener('click', () => {
            document.getElementById('modalChiTietLo')?.classList.add('hidden');
        });
    });

    // ================== 10. MODAL ĐĂNG KÝ LÔ MỚI ==================
    const nutMoModal = document.getElementById('nutMoModalDangKyLo');
    const modalDangKy = document.getElementById('modalDangKyLo');
    const cacNutDongModal = document.querySelectorAll('.dong-modal-lo');
    const manHinhForm = document.getElementById('manHinhFormDangKyLo');
    const manHinhKetQua = document.getElementById('manHinhKetQuaSinhQR');

    function resetModalView() {
        if (manHinhForm) manHinhForm.classList.remove('hidden');
        if (manHinhKetQua) manHinhKetQua.classList.add('hidden');
    }

    if (nutMoModal && modalDangKy) {
        nutMoModal.addEventListener('click', async () => {
            resetModalView();
            modalDangKy.classList.remove('hidden');
            await taiDanhSachNhaPhanPhoi(document.getElementById('chonNhaPhanPhoiKhiTaoLo'));
        });
    }

    cacNutDongModal.forEach(btn => {
        btn.addEventListener('click', () => {
            if (modalDangKy) modalDangKy.classList.add('hidden');
            resetModalView();
        });
    });

    // ================== 11. SUBMIT TẠO LÔ ==================
    const formDangKyLo = document.getElementById('formSubmitDangKyLo');
    if (formDangKyLo) {
        formDangKyLo.addEventListener('submit', async function (e) {
            e.preventDefault();

            const btnSubmit = document.getElementById('btnXacNhanDangKyLo') || this.querySelector('button[type="submit"]');
            const originalBtnContent = btnSubmit ? btnSubmit.innerHTML : 'Xác nhận đăng ký';

            const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';

            const payload = {
                sanPhamId: document.getElementById('chonSanPhamDaDuyet')?.value,
                maLoNghiepVu: document.getElementById('nhapMaLo')?.value,
                soLuong: document.getElementById('nhapSoLuong')?.value,
                donViTinh: document.getElementById('nhapDonViTinh')?.value,
                ngaySanXuat: document.getElementById('nhapNgaySX')?.value,
                hanSuDung: document.getElementById('nhapHanDung')?.value,
                benNhanId: document.getElementById('chonNhaPhanPhoiKhiTaoLo')?.value || null
            };

            try {
                if (btnSubmit) {
                    btnSubmit.disabled = true;
                    btnSubmit.classList.add('opacity-75', 'cursor-not-allowed');
                    btnSubmit.innerHTML = `<i class="fa-solid fa-spinner fa-spin me-2"></i> Đang gửi yêu cầu...`;
                }

                const targetUrl = this.getAttribute('data-url') || '/nha-san-xuat/api/tao-lo-thuoc';
                const response = await fetch(targetUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Không thể tạo yêu cầu lô thuốc!');
                }

                const maLoCuaYeuCau = result.data?.maLoNghiepVu || payload.maLoNghiepVu;
                if (result.data?.id) {
                    dangTheoDoiLoTaoId = Number(result.data.id);
                    batchSigningStates.set(dangTheoDoiLoTaoId, 'PENDING');
                }
                capNhatTrangThaiModalTaoLo('PENDING');

                const selectNppTaoLo = document.getElementById('chonNhaPhanPhoiKhiTaoLo');
                if (payload.benNhanId && selectNppTaoLo) {
                    const selectedOption = selectNppTaoLo.options[selectNppTaoLo.selectedIndex];
                    sessionStorage.setItem(NPP_INTENT_PREFIX + String(maLoCuaYeuCau), JSON.stringify({
                        id: Number(payload.benNhanId),
                        name: selectedOption?.textContent?.trim() || 'Nhà phân phối đã chọn'
                    }));
                }
                const hienThiMaLo = document.getElementById('hienThiMaLoQR');
                if (hienThiMaLo) {
                    hienThiMaLo.textContent = maLoCuaYeuCau;
                }
                const boxNppDuKien = document.getElementById('hienThiNppDuKienTaoLo');
                const tenNppDuKien = document.getElementById('tenNppDuKienTaoLo');
                if (payload.benNhanId && selectNppTaoLo) {
                    if (tenNppDuKien) tenNppDuKien.textContent = selectNppTaoLo.options[selectNppTaoLo.selectedIndex]?.textContent?.trim() || 'Nhà phân phối đã chọn';
                    boxNppDuKien?.classList.remove('hidden');
                } else {
                    boxNppDuKien?.classList.add('hidden');
                }

                if (manHinhForm) manHinhForm.classList.add('hidden');
                if (manHinhKetQua) manHinhKetQua.classList.remove('hidden');

                formDangKyLo.reset();
                await loadDataFromDB();
            } catch (error) {
                console.error('Lỗi khi gửi yêu cầu tạo lô:', error);
                showPopup('Thao tác thất bại: ' + (error.message || 'Đã xảy ra lỗi kết nối!'), 'error', 'Không thể tạo lô thuốc');
            } finally {
                if (btnSubmit) {
                    btnSubmit.disabled = false;
                    btnSubmit.classList.remove('opacity-75', 'cursor-not-allowed');
                    btnSubmit.innerHTML = originalBtnContent;
                }
            }
        });
    }

    // ================== 12. NÚT QR TRONG MODAL ==================
    document.getElementById('nutXuatQRThungModal')?.addEventListener('click', () => {
        showPopup('QR duy nhất của lô được tạo sau khi registerBatch xác nhận thành công.', 'info', 'Mã QR theo lô');
    });

    // Khởi chạy
    loadDataFromDB();
});
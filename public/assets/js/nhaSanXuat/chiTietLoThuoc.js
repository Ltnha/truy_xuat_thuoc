document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('chiTietLoContainer');
    if (!container) return;

    const loId = container.dataset.loId;
    const khuVucBlockchain = document.getElementById('khuVucTrangThaiBlockchain');
    const badgeTong = document.getElementById('badgeTrangThaiTong');
    const timelineEl = document.getElementById('timelineSuKien');
    const btnLamMoi = document.getElementById('btnLamMoiTrangThai');

    let pollingTimer = null;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // ================== GỌI API THẬT ==================
    async function fetchChiTiet() {
        try {
            const res = await fetch(`/nha-san-xuat/api/lo-thuoc/${loId}/chi-tiet`, {
                headers: { 'Accept': 'application/json' }
            });
            const json = await res.json();

            if (!json.success) {
                showError(json.message || 'Không lấy được dữ liệu từ máy chủ');
                return;
            }
            renderAll(json.data);
        } catch (err) {
            console.error(err);
            showError('Lỗi kết nối máy chủ. Vui lòng thử lại.');
        }
    }

    function showError(msg) {
        if (khuVucBlockchain) {
            khuVucBlockchain.innerHTML = `
                <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl text-xs font-bold">
                    ${escapeHtml(msg)}
                </div>`;
        }
    }

    // ================== RENDER TỪ DỮ LIỆU CSDL ==================
    function renderAll(data) {
        const tx = data.blockchain_transaction || null;
        renderBadgeTong(tx);
        renderBlockchainCard(tx);
        renderTimeline(data);
        handlePolling(tx);
    }

    function getStatus(tx) {
        if (!tx) {
            return {
                key: 'NOT_SENT',
                badgeHtml: `<span class="px-2.5 py-1 bg-slate-100 text-slate-600 border border-slate-200 rounded-full text-[11px] font-bold">Chưa gửi lên Blockchain</span>`
            };
        }

        const st = String(tx.trangThai || '').toUpperCase();
        const hash = String(tx.txHash || '');

        if (st === 'PENDING' || hash.startsWith('PENDING_')) {
            return {
                key: 'PENDING',
                badgeHtml: `<span class="px-2.5 py-1 bg-amber-50 text-amber-800 border border-amber-200 rounded-full text-[11px] font-bold"><i class="fa-solid fa-spinner fa-spin mr-1"></i>Đang xử lý Queue</span>`
            };
        }
        if (st === 'FAILED' || st === 'REJECTED') {
            return {
                key: 'FAILED',
                badgeHtml: `<span class="px-2.5 py-1 bg-red-50 text-red-700 border border-red-200 rounded-full text-[11px] font-bold">Gửi thất bại</span>`
            };
        }
        // CONFIRMED / SUCCESS hoặc có txHash thật
        if (st === 'CONFIRMED' || st === 'SUCCESS' || (hash && !hash.startsWith('PENDING_'))) {
            return {
                key: 'CONFIRMED',
                badgeHtml: `<span class="px-2.5 py-1 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-full text-[11px] font-bold"><i class="fa-solid fa-circle-check mr-1"></i>Đã ghi nhận On-chain</span>`
            };
        }

        return {
            key: 'NOT_SENT',
            badgeHtml: `<span class="px-2.5 py-1 bg-slate-100 text-slate-600 border border-slate-200 rounded-full text-[11px] font-bold">Chưa gửi lên Blockchain</span>`
        };
    }

    function renderBadgeTong(tx) {
        if (!badgeTong) return;
        badgeTong.innerHTML = getStatus(tx).badgeHtml;
    }

    function renderBlockchainCard(tx) {
        if (!khuVucBlockchain) return;
        const status = getStatus(tx);

        if (status.key === 'NOT_SENT') {
            khuVucBlockchain.innerHTML = `
                <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl text-xs space-y-3">
                    <p class="text-slate-600">Lô thuốc này chưa được gửi lên Blockchain.</p>
                    <button type="button" id="btnGuiLenBlockchain"
                        class="px-5 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white font-bold rounded-xl text-xs shadow-md transition-all flex items-center gap-2">
                        <i class="fa-solid fa-paper-plane"></i> Gửi lên Blockchain
                    </button>
                </div>`;
            document.getElementById('btnGuiLenBlockchain')?.addEventListener('click', guiBlockchain);
            return;
        }

        if (status.key === 'PENDING') {
            khuVucBlockchain.innerHTML = `
                <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl text-xs space-y-2">
                    <div class="flex items-center gap-2 text-amber-800 font-bold">
                        <i class="fa-solid fa-spinner fa-spin"></i>
                        Đang chờ Queue / Signer Service xử lý...
                    </div>
                    <p class="text-amber-700">Hệ thống đang ký và gửi giao dịch lên Ethereum Sepolia. Trang sẽ tự cập nhật khi có kết quả.</p>
                    <p class="font-mono text-[11px] text-slate-500">Mã tạm: ${escapeHtml(tx.txHash || '—')}</p>
                </div>`;
            return;
        }

        if (status.key === 'FAILED') {
            khuVucBlockchain.innerHTML = `
                <div class="p-4 bg-red-50 border border-red-200 rounded-xl text-xs space-y-3">
                    <div class="flex items-center gap-2 text-red-700 font-bold">
                        <i class="fa-solid fa-circle-xmark"></i> Gửi thất bại
                    </div>
                    <p class="text-red-600">Giao dịch không thành công. Bạn có thể thử gửi lại.</p>
                    <button type="button" id="btnGuiLaiBlockchain"
                        class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl text-xs shadow-md transition-all flex items-center gap-2">
                        <i class="fa-solid fa-rotate-right"></i> Gửi lại lên Blockchain
                    </button>
                </div>`;
            document.getElementById('btnGuiLaiBlockchain')?.addEventListener('click', guiBlockchain);
            return;
        }

        // CONFIRMED
        const txHash = tx.txHash || '';
        const shortHash = txHash.length > 18
            ? `${txHash.substring(0, 10)}...${txHash.substring(txHash.length - 8)}`
            : txHash;
        const etherscanUrl = `https://sepolia.etherscan.io/tx/${txHash}`;
        const blockNumber = tx.blockNumber != null ? Number(tx.blockNumber).toLocaleString('vi-VN') : '—';
        const thoiGian = formatDateTime(tx.thoiGian);

        khuVucBlockchain.innerHTML = `
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div>
                    <span class="text-slate-500 block mb-0.5">Trạng thái</span>
                    <span class="px-2.5 py-1 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-full text-[11px] font-bold">
                        <i class="fa-solid fa-circle-check mr-1"></i>Đã xác nhận (Confirmed)
                    </span>
                </div>
                <div>
                    <span class="text-slate-500 block mb-0.5">Network</span>
                    <span class="font-semibold text-slate-800">${escapeHtml(tx.network || 'Ethereum Sepolia')}</span>
                </div>
                <div class="sm:col-span-2">
                    <span class="text-slate-500 block mb-0.5">Transaction Hash</span>
                    <div class="flex flex-wrap items-center gap-2 mt-1">
                        <code class="font-mono text-[12px] bg-slate-50 border border-slate-200 px-2 py-1 rounded-lg text-slate-800">${escapeHtml(shortHash)}</code>
                        <button type="button" data-hash="${escapeHtml(txHash)}"
                            class="btn-copy-hash px-2 py-1 bg-slate-100 hover:bg-slate-200 rounded-lg text-[11px] font-bold text-slate-700" title="Copy">
                            <i class="fa-regular fa-copy"></i>
                        </button>
                        <a href="${etherscanUrl}" target="_blank" rel="noopener"
                            class="px-2.5 py-1 bg-cyan-50 hover:bg-cyan-100 border border-cyan-200 text-cyan-800 rounded-lg text-[11px] font-bold">
                            <i class="fa-solid fa-arrow-up-right-from-square mr-1"></i>Etherscan
                        </a>
                    </div>
                </div>
                <div>
                    <span class="text-slate-500 block mb-0.5">Block Number</span>
                    <span class="font-mono font-semibold text-slate-800">${blockNumber}</span>
                </div>
                <div>
                    <span class="text-slate-500 block mb-0.5">Thời gian xác nhận</span>
                    <span class="font-mono text-slate-800">${thoiGian}</span>
                </div>
            </div>`;

        document.querySelectorAll('.btn-copy-hash').forEach(btn => {
            btn.addEventListener('click', function () {
                const h = this.getAttribute('data-hash');
                if (h) navigator.clipboard.writeText(h).then(() => {
                    this.innerHTML = '<i class="fa-solid fa-check"></i>';
                    setTimeout(() => { this.innerHTML = '<i class="fa-regular fa-copy"></i>'; }, 1500);
                });
            });
        });
    }

    function renderTimeline(data) {
        if (!timelineEl) return;
        const events = [];

        // Sự kiện tạo lô (dùng thoiGian của tx hoặc ngày sản xuất)
        events.push({
            time: data.ngaySanXuat || data.created_at,
            title: 'Tạo lô thuốc',
            desc: `Mã lô <strong>${escapeHtml(data.maLoNghiepVu || '')}</strong> được khởi tạo trong hệ thống.`
        });

        const tx = data.blockchain_transaction;
        if (tx) {
            const st = getStatus(tx);
            if (st.key === 'PENDING') {
                events.push({
                    time: tx.thoiGian,
                    title: 'Đưa vào hàng đợi Blockchain',
                    desc: 'Đang chờ Signer Service ký và gửi giao dịch lên Sepolia.'
                });
            } else if (st.key === 'CONFIRMED') {
                events.push({
                    time: tx.thoiGian,
                    title: 'Ghi nhận On-chain thành công',
                    desc: `Transaction Hash: <code class="text-[11px]">${escapeHtml(tx.txHash || '')}</code>`
                });
            } else if (st.key === 'FAILED') {
                events.push({
                    time: tx.thoiGian,
                    title: 'Gửi Blockchain thất bại',
                    desc: 'Có lỗi xảy ra khi gửi giao dịch. Có thể thử gửi lại.'
                });
            }
        }

        // Lịch sử chuyển giao từ CSDL
        if (Array.isArray(data.chuyen_giao)) {
            data.chuyen_giao.forEach(cg => {
                const tenBenNhan = cg.benNhan?.tenToChuc || 'Đối tác';
                events.push({
                    time: cg.thoiGianXacNhan || cg.thoiGianKhoiTao,
                    title: `Chuyển giao → ${escapeHtml(tenBenNhan)}`,
                    desc: `Số lượng: ${cg.soLuong != null ? Number(cg.soLuong).toLocaleString('vi-VN') : '—'} | Trạng thái: ${escapeHtml(String(cg.trangThai || ''))}`
                });
            });
        }

        if (events.length === 0) {
            timelineEl.innerHTML = `<div class="text-xs text-slate-400">Chưa có sự kiện nào.</div>`;
            return;
        }

        events.sort((a, b) => new Date(b.time || 0) - new Date(a.time || 0));

        timelineEl.innerHTML = events.map(ev => `
            <div class="relative">
                <div class="absolute -left-[25px] top-1 w-3 h-3 rounded-full bg-emerald-600 border-2 border-white shadow"></div>
                <div class="text-[11px] text-slate-400 font-mono mb-0.5">${formatDateTime(ev.time)}</div>
                <div class="font-bold text-slate-800 text-xs">${ev.title}</div>
                <div class="text-[11px] text-slate-600 mt-0.5">${ev.desc}</div>
            </div>
        `).join('');
    }

    // ================== GỬI LÊN BLOCKCHAIN (API THẬT) ==================
    async function guiBlockchain() {
        const btn = document.getElementById('btnGuiLenBlockchain') || document.getElementById('btnGuiLaiBlockchain');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang gửi...';
        }

        try {
            const res = await fetch(`/nha-san-xuat/api/lo-thuoc/${loId}/gui-blockchain`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });
            const json = await res.json();

            if (json.success) {
                await fetchChiTiet();
            } else {
                alert(json.message || 'Gửi thất bại');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Gửi lên Blockchain';
                }
            }
        } catch (e) {
            console.error(e);
            alert('Lỗi kết nối máy chủ');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Gửi lên Blockchain';
            }
        }
    }

    // ================== POLLING ==================
    function handlePolling(tx) {
        const status = getStatus(tx);
        if (status.key === 'PENDING') {
            if (!pollingTimer) {
                pollingTimer = setInterval(fetchChiTiet, 5000);
            }
        } else if (pollingTimer) {
            clearInterval(pollingTimer);
            pollingTimer = null;
        }
    }

    if (btnLamMoi) {
        btnLamMoi.addEventListener('click', function () {
            this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang làm mới';
            fetchChiTiet().finally(() => {
                this.innerHTML = '<i class="fa-solid fa-rotate"></i> Làm mới';
            });
        });
    }

    // ================== HELPERS ==================
    function formatDateTime(val) {
        if (!val) return '—';
        const d = new Date(val);
        if (isNaN(d.getTime())) return String(val);
        return d.toLocaleString('vi-VN', {
            day: '2-digit', month: '2-digit', year: 'numeric',
            hour: '2-digit', minute: '2-digit'
        });
    }

    function escapeHtml(str) {
        if (str == null) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // Khởi chạy – lấy dữ liệu thật từ CSDL
    fetchChiTiet();
});

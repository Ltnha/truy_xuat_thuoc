document.addEventListener("DOMContentLoaded", function () {
    // 1. CHUẨN HÓA DỮ LIỆU BAN ĐẦU
    const yeuCauGoc = (window.danhSachYeuCau || []).map(yc => {
        const sp = yc.san_pham || {};
        const nsx = sp.nha_san_xuat || {};
        return {
            yeuCauId: yc.id,
            sanPhamId: sp.id,
            tenThuoc: yc.tenSanPham,
            soDangKy: yc.soDangKy,
            hoatChat: yc.thanhPhan,
            hamLuong: yc.hamLuong,
            dangBaoChe: yc.dangBaoChe,
            quyCachDongGoi: yc.quyCachDongGoi,
            tenNSX: nsx.tenToChuc || '--',
            mstNSX: nsx.maSoThue || '--',
            giayPhepNSX: nsx.soGiayPhepDuoc || '--',
            trangThaiToChucNSX: nsx.trangThaiDuyet || 'CHO_DUYET',
            diaChiNSX: nsx.diaChi || '--',
            versionHoSo: yc.versionHoSo,
            ngayNop: yc.ngayGui ? new Date(yc.ngayGui).toLocaleDateString('vi-VN') : '--',
            trangThai: yc.trangThai || 'CHO_DUYET',
            lyDoTuChoi: yc.lyDoTuChoi,
            nsxId: nsx.id || null,
            maSanPham: sp.maSanPham || '--',
            urlPheDuyet: `/co-quan/xet-duyet-san-pham/${yc.id}/phe-duyet`,
            urlTuChoi: `/co-quan/xet-duyet-san-pham/${yc.id}/tu-choi`
        };
    });

    const daDuyetGoc = (window.danhSachDaDuyet || []).map(sp => {
        const nsx = sp.nha_san_xuat || {};
        const bctx = (sp.blockchain_transactions && sp.blockchain_transactions.length > 0) ? sp.blockchain_transactions[0] : null;
        return {
            tenThuoc: sp.tenSanPham,
            soDangKy: sp.soDangKy,
            hoatChat: sp.thanhPhan,
            hamLuong: sp.hamLuong,
            dangBaoChe: sp.dangBaoChe,
            tenNSX: nsx.tenToChuc || '--',
            nsxId: nsx.id || null,
            maSanPham: sp.maSanPham || '--',
            txHash: bctx ? bctx.txHash : null
        };
    });

    let danhSachYeuCauLoc = [...yeuCauGoc];
    let danhSachDaDuyetLoc = [...daDuyetGoc];

    let trangTab1 = 1;
    let trangTab2 = 1;
    const soDongMoiTrang = 10;

    // DOM ELEMENTS
    const tabHoSoDangKy = document.getElementById("tabHoSoDangKy");
    const tabSanPhamDaDuyet = document.getElementById("tabSanPhamDaDuyet");
    const khungTabHoSo = document.getElementById("khungTabHoSo");
    const khungTabDaDuyet = document.getElementById("khungTabDaDuyet");

    const thanBangYeuCau = document.getElementById("thanBangYeuCauSP");
    const badgeTab1 = document.getElementById("soLuongYeuCauSPBadge");
    const txtPhanTrangTab1 = document.getElementById("thongTinPhanTrangTab1");
    const cumNutPhanTrangTab1 = document.getElementById("cumNutPhanTrangTab1");

    const thanBangDaDuyet = document.getElementById("thanBangDaDuyet");
    const badgeTab2 = document.getElementById("soLuongDaDuyetBadge");
    const txtPhanTrangTab2 = document.getElementById("thongTinPhanTrangTab2");
    const cumNutPhanTrangTab2 = document.getElementById("cumNutPhanTrangTab2");

    // Lọc Tab 1
    const oTimKiemTab1 = document.getElementById("oTimKiemSP");
    const locNSXTab1 = document.getElementById("locNhaSanXuat");
    const locTTTab1 = document.getElementById("locTrangThaiYeuCauSP");

    // Lọc Tab 2
    const oTimKiemTab2 = document.getElementById("oTimKiemTab2");
    const locNSXTab2 = document.getElementById("locNSXTab2");

    // Modals
    const modalXemHoSoSP = document.getElementById("modalXemHoSoSP");
    const panelCumNutHanhDong = document.getElementById("panelCumNutHanhDongSP");
    const modalDuyet = document.getElementById("modalXacNhanDuyetSP");
    const formDuyet = document.getElementById("formPheDuyetSPReal");

    const modalChiTietDaDuyet = document.getElementById("modalChiTietDaDuyet");

    const modalTuChoi = document.getElementById("modalTuChoiSP");
    const formTuChoi = document.getElementById("formTuChoiSPReal");
    const nhapLyDo = document.getElementById("nhapLyDoTuChoiSP");
    const demKyTu = document.getElementById("demKyTuTuChoiSP");
    const nutXacNhanTuChoi = document.getElementById("nutXacNhanTuChoiSPOffChain");

    const modalTienTrinh = document.getElementById("modalTienTrinhBlockchain");
    const iconTienTrinh = document.getElementById("iconTienTrinh");
    const tieuDeTienTrinh = document.getElementById("tieuDeTienTrinh");
    const noiDungTienTrinh = document.getElementById("noiDungTienTrinh");
    const cumNutDongTienTrinh = document.getElementById("cumNutDongTienTrinh");

    let hoSoDangChon = null;

    // 2. CHUYỂN TAB
    if (tabHoSoDangKy) {
        tabHoSoDangKy.addEventListener("click", function () {
            tabHoSoDangKy.className = "flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all bg-indigo-700 text-white shadow-sm";
            tabSanPhamDaDuyet.className = "flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all text-slate-600 hover:text-indigo-700 hover:bg-indigo-50";
            khungTabHoSo.classList.remove("hidden");
            khungTabDaDuyet.classList.add("hidden");
        });
    }
    if (tabSanPhamDaDuyet) {
        tabSanPhamDaDuyet.addEventListener("click", function () {
            tabSanPhamDaDuyet.className = "flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all bg-indigo-700 text-white shadow-sm";
            tabHoSoDangKy.className = "flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all text-slate-600 hover:text-indigo-700 hover:bg-indigo-50";
            khungTabDaDuyet.classList.remove("hidden");
            khungTabHoSo.classList.add("hidden");
        });
    }

    // 3. HÀM RENDER PHÂN TRANG THU GỌN (DẠNG 1 ... 4 5 6 ... 10)
    function renderNutPhanTrang(container, tongSoTrang, trangHienTai, callback) {
        if (!container) return;
        container.innerHTML = '';
        if (tongSoTrang <= 1) return;

        // Nút Trước
        const btnTruoc = document.createElement('button');
        btnTruoc.className = `px-3 py-1.5 rounded-lg border text-xs font-bold transition-all ${trangHienTai === 1 ? 'border-slate-200 text-slate-300 cursor-not-allowed' : 'border-slate-300 text-slate-700 hover:bg-slate-100'}`;
        btnTruoc.innerHTML = `<i class="fa-solid fa-chevron-left"></i>`;
        btnTruoc.disabled = trangHienTai === 1;
        btnTruoc.addEventListener('click', () => callback(trangHienTai - 1));
        container.appendChild(btnTruoc);

        // Danh sách số trang thu gọn
        let pages = [];
        if (tongSoTrang <= 7) {
            for (let i = 1; i <= tongSoTrang; i++) pages.push(i);
        } else {
            pages.push(1);
            if (trangHienTai > 3) pages.push('...');

            let start = Math.max(2, trangHienTai - 1);
            let end = Math.min(tongSoTrang - 1, trangHienTai + 1);

            if (trangHienTai <= 3) {
                start = 2;
                end = 4;
            } else if (trangHienTai >= tongSoTrang - 2) {
                start = tongSoTrang - 3;
                end = tongSoTrang - 1;
            }

            for (let i = start; i <= end; i++) pages.push(i);

            if (trangHienTai < tongSoTrang - 2) pages.push('...');
            pages.push(tongSoTrang);
        }

        pages.forEach(p => {
            if (p === '...') {
                const span = document.createElement('span');
                span.className = 'px-2 py-1.5 text-xs text-slate-400 font-bold';
                span.textContent = '...';
                container.appendChild(span);
            } else {
                const btnSo = document.createElement('button');
                btnSo.className = `px-3 py-1.5 rounded-lg text-xs font-bold transition-all ${p === trangHienTai ? 'bg-indigo-700 text-white shadow-xs' : 'border border-slate-300 text-slate-700 hover:bg-slate-100'}`;
                btnSo.textContent = p;
                btnSo.addEventListener('click', () => callback(p));
                container.appendChild(btnSo);
            }
        });

        // Nút Sau
        const btnSau = document.createElement('button');
        btnSau.className = `px-3 py-1.5 rounded-lg border text-xs font-bold transition-all ${trangHienTai === tongSoTrang ? 'border-slate-200 text-slate-300 cursor-not-allowed' : 'border-slate-300 text-slate-700 hover:bg-slate-100'}`;
        btnSau.innerHTML = `<i class="fa-solid fa-chevron-right"></i>`;
        btnSau.disabled = trangHienTai === tongSoTrang;
        btnSau.addEventListener('click', () => callback(trangHienTai + 1));
        container.appendChild(btnSau);
    }

    // 4. HÀM SAO CHÉP MÃ HASH
    function ganSuKienSaoChep(buttonElement, txHash) {
        if (!buttonElement || !txHash) return;
        buttonElement.addEventListener("click", function () {
            navigator.clipboard.writeText(txHash).then(() => {
                const originalHtml = buttonElement.innerHTML;
                buttonElement.innerHTML = `<i class="fa-solid fa-check text-emerald-600"></i> Đã chép`;
                setTimeout(() => {
                    buttonElement.innerHTML = originalHtml;
                }, 2000);
            });
        });
    }

    // 5. RENDER BẢNG TAB 1 (HỒ SƠ ĐĂNG KÝ)
    function renderTab1() {
        if (!thanBangYeuCau) return;
        thanBangYeuCau.innerHTML = '';

        const tongSo = danhSachYeuCauLoc.length;
        if (badgeTab1) badgeTab1.textContent = `Hiển thị: ${tongSo} hồ sơ`;

        if (tongSo === 0) {
            thanBangYeuCau.innerHTML = `<tr><td colspan="10" class="text-center py-8 text-slate-400 italic">Không tìm thấy hồ sơ đăng ký nào phù hợp.</td></tr>`;
            if (txtPhanTrangTab1) txtPhanTrangTab1.textContent = 'Trang 0 / 0';
            if (cumNutPhanTrangTab1) cumNutPhanTrangTab1.innerHTML = '';
            return;
        }

        const tongSoTrang = Math.ceil(tongSo / soDongMoiTrang);
        if (trangTab1 > tongSoTrang) trangTab1 = tongSoTrang;
        if (trangTab1 < 1) trangTab1 = 1;

        const batDau = (trangTab1 - 1) * soDongMoiTrang;
        const ketThuc = Math.min(batDau + soDongMoiTrang, tongSo);
        const danhSachTrang = danhSachYeuCauLoc.slice(batDau, ketThuc);

        danhSachTrang.forEach(item => {
            const tr = document.createElement('tr');
            tr.className = 'dong-yeu-cau-sp hover:bg-slate-50 transition-colors border-b border-slate-100';

            let badgeHtml = item.trangThai === 'TU_CHOI'
                ? `<span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-red-100 text-red-700 border border-red-300"><i class="fa-solid fa-circle-xmark"></i> Bị từ chối</span>`
                : `<span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300"><i class="fa-solid fa-clock"></i> Chờ duyệt</span>`;

            tr.innerHTML = `
                <td class="py-3 px-3 font-semibold text-slate-900 leading-tight">
                    <span class="block font-bold text-slate-900">${item.tenThuoc}</span>
                    <span class="font-mono text-[10px] text-slate-400">UUID: ${item.maSanPham}</span>
                </td>
                <td class="py-3 px-3 font-mono font-bold text-cyan-800 whitespace-nowrap">${item.soDangKy}</td>
                <td class="py-3 px-3 text-slate-800 font-semibold leading-tight">${item.tenNSX}</td>
                <td class="py-3 px-3 text-slate-700 leading-tight">${item.hoatChat}</td>
                <td class="py-3 px-2.5 text-slate-600 font-mono whitespace-nowrap">${item.hamLuong}</td>
                <td class="py-3 px-2.5 text-slate-600 whitespace-nowrap">${item.dangBaoChe}</td>
                <td class="py-3 px-2.5 text-center font-mono text-[11px] text-slate-600 whitespace-nowrap">${item.ngayNop}</td>
                <td class="py-3 px-2.5 text-center whitespace-nowrap">
                    <span class="font-mono font-bold ${item.versionHoSo > 1 ? 'text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200' : 'text-slate-600'}">Lần ${item.versionHoSo}</span>
                </td>
                <td class="py-3 px-2.5 text-center whitespace-nowrap">${badgeHtml}</td>
                <td class="py-3 px-3 text-center whitespace-nowrap">
                    <button type="button" class="btn-xem-ho-so-sp px-3 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-800 font-bold text-xs transition-all border border-indigo-200 shadow-2xs flex items-center justify-center gap-1.5 mx-auto" data-id="${item.yeuCauId}">
                        <i class="fa-solid fa-eye text-indigo-700 text-[11px]"></i> Xem
                    </button>
                </td>
            `;

            thanBangYeuCau.appendChild(tr);
        });

        if (txtPhanTrangTab1) txtPhanTrangTab1.textContent = `Hiển thị từ ${batDau + 1} đến ${ketThuc} trên tổng số ${tongSo} hồ sơ (Trang ${trangTab1}/${tongSoTrang})`;
        renderNutPhanTrang(cumNutPhanTrangTab1, tongSoTrang, trangTab1, p => { trangTab1 = p; renderTab1(); });

        document.querySelectorAll(".btn-xem-ho-so-sp").forEach(btn => {
            btn.addEventListener("click", function() {
                const id = parseInt(this.getAttribute("data-id"));
                const target = yeuCauGoc.find(x => x.yeuCauId === id);
                if (target) moModalXemHoSo(target);
            });
        });
    }

    // 6. RENDER BẢNG TAB 2 (SẢN PHẨM ĐÃ DUYỆT)
    function renderTab2() {
        if (!thanBangDaDuyet) return;
        thanBangDaDuyet.innerHTML = '';

        const tongSo = danhSachDaDuyetLoc.length;
        if (badgeTab2) badgeTab2.textContent = `Hiển thị: ${tongSo} sản phẩm`;

        if (tongSo === 0) {
            thanBangDaDuyet.innerHTML = `<tr><td colspan="8" class="text-center py-8 text-slate-400 italic">Chưa có sản phẩm nào được phê duyệt lưu hành.</td></tr>`;
            if (txtPhanTrangTab2) txtPhanTrangTab2.textContent = 'Trang 0 / 0';
            if (cumNutPhanTrangTab2) cumNutPhanTrangTab2.innerHTML = '';
            return;
        }

        const tongSoTrang = Math.ceil(tongSo / soDongMoiTrang);
        if (trangTab2 > tongSoTrang) trangTab2 = tongSoTrang;
        if (trangTab2 < 1) trangTab2 = 1;

        const batDau = (trangTab2 - 1) * soDongMoiTrang;
        const ketThuc = Math.min(batDau + soDongMoiTrang, tongSo);
        const danhSachTrang = danhSachDaDuyetLoc.slice(batDau, ketThuc);

        danhSachTrang.forEach((sp, idx) => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-slate-50 transition-colors border-b border-slate-100';

            const txHtml = sp.txHash ? `
                <div class="flex items-center gap-1.5">
                    <a href="https://sepolia.etherscan.io/tx/${sp.txHash}" target="_blank" class="font-mono text-[11px] font-bold text-cyan-800 hover:underline">
                        ${sp.txHash.substring(0, 10)}...${sp.txHash.substring(sp.txHash.length - 4)}
                    </a>
                    <button type="button" class="btn-sao-chep-table-tx p-1 text-slate-400 hover:text-indigo-700 text-xs transition-all" data-hash="${sp.txHash}" title="Sao chép TxHash">
                        <i class="fa-regular fa-copy"></i>
                    </button>
                </div>
            ` : `<span class="text-amber-600 italic text-[11px]">Chưa ghi nhận</span>`;

            tr.innerHTML = `
                <td class="py-3 px-3 font-semibold text-slate-900 leading-tight">
                    <span class="block font-bold text-slate-900">${sp.tenThuoc}</span>
                    <span class="font-mono text-[10px] text-slate-400">UUID: ${sp.maSanPham}</span>
                </td>
                <td class="py-3 px-3 font-mono font-bold text-cyan-800 whitespace-nowrap">${sp.soDangKy}</td>
                <td class="py-3 px-3 text-slate-700 leading-tight">${sp.hoatChat}</td>
                <td class="py-3 px-2.5 text-slate-600 font-mono whitespace-nowrap">${sp.hamLuong}</td>
                <td class="py-3 px-2.5 text-slate-600 whitespace-nowrap">${sp.dangBaoChe}</td>
                <td class="py-3 px-3 text-slate-800 font-semibold leading-tight">${sp.tenNSX}</td>
                <td class="py-3 px-3 whitespace-nowrap">${txHtml}</td>
                <td class="py-3 px-3 text-center whitespace-nowrap">
                    <button type="button" class="btn-xem-daduyet px-3 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold text-xs transition-all border border-emerald-200 shadow-2xs flex items-center justify-center gap-1 mx-auto" data-index="${batDau + idx}">
                        <i class="fa-solid fa-eye text-emerald-700"></i> Xem
                    </button>
                </td>
            `;

            thanBangDaDuyet.appendChild(tr);
        });

        if (txtPhanTrangTab2) txtPhanTrangTab2.textContent = `Hiển thị từ ${batDau + 1} đến ${ketThuc} trên tổng số ${tongSo} sản phẩm (Trang ${trangTab2}/${tongSoTrang})`;
        renderNutPhanTrang(cumNutPhanTrangTab2, tongSoTrang, trangTab2, p => { trangTab2 = p; renderTab2(); });

        // Gán sự kiện Sao chép mã Hash trong bảng
        document.querySelectorAll(".btn-sao-chep-table-tx").forEach(btn => {
            const hash = btn.getAttribute("data-hash");
            ganSuKienSaoChep(btn, hash);
        });

        // Gán sự kiện Xem chi tiết Tab 2
        document.querySelectorAll(".btn-xem-daduyet").forEach(btn => {
            btn.addEventListener("click", function() {
                const idx = parseInt(this.getAttribute("data-index"));
                const sp = danhSachDaDuyetLoc[idx];
                if (sp) moModalChiTietDaDuyet(sp);
            });
        });
    }

    // 7. HÀM MỞ MODAL XEM CHI TIẾT SẢN PHẨM ĐÃ DUYỆT (TAB 2)
    function moModalChiTietDaDuyet(sp) {
        if (!modalChiTietDaDuyet) return;

        document.getElementById("dtTenThuoc").textContent = sp.tenThuoc;
        document.getElementById("dtSoDK").textContent = sp.soDangKy;
        document.getElementById("dtHoatChat").textContent = sp.hoatChat || '---';
        document.getElementById("dtHamLuong").textContent = sp.hamLuong || '---';
        document.getElementById("dtDangBaoChe").textContent = sp.dangBaoChe || '---';
        document.getElementById("dtTenNSX").textContent = sp.tenNSX || '---';

        const linkEl = document.getElementById("dtTxLink");
        const btnCopy = document.getElementById("btnSaoChepModalTx");

        if (sp.txHash) {
            linkEl.textContent = sp.txHash;
            linkEl.href = `https://sepolia.etherscan.io/tx/${sp.txHash}`;
            btnCopy.style.display = 'inline-flex';
            ganSuKienSaoChep(btnCopy, sp.txHash);
        } else {
            linkEl.textContent = 'Chưa có dữ liệu On-Chain';
            linkEl.href = '#';
            btnCopy.style.display = 'none';
        }

        modalChiTietDaDuyet.classList.remove("hidden");
    }

    document.querySelectorAll(".dong-modal-daduyet").forEach(b => b.addEventListener("click", () => modalChiTietDaDuyet.classList.add("hidden")));

    // 8. LỌC TAB 1 & TAB 2
    function locTab1() {
        const tuKhoa = oTimKiemTab1 ? oTimKiemTab1.value.trim().toLowerCase() : '';
        const nsxChon = locNSXTab1 ? locNSXTab1.value : 'TAT_CA';
        const ttChon = locTTTab1 ? locTTTab1.value : 'CHO_DUYET';

        danhSachYeuCauLoc = yeuCauGoc.filter(item => {
            const matchTuKhoa = !tuKhoa ||
                item.tenThuoc.toLowerCase().includes(tuKhoa) ||
                item.soDangKy.toLowerCase().includes(tuKhoa) ||
                item.hoatChat.toLowerCase().includes(tuKhoa) ||
                item.tenNSX.toLowerCase().includes(tuKhoa);

            const matchNSX = nsxChon === 'TAT_CA' || String(item.nsxId) === nsxChon;
            const matchTT = ttChon === 'TAT_CA' || item.trangThai === ttChon;

            return matchTuKhoa && matchNSX && matchTT;
        });

        trangTab1 = 1;
        renderTab1();
    }

    function locTab2() {
        const tuKhoa = oTimKiemTab2 ? oTimKiemTab2.value.trim().toLowerCase() : '';
        const nsxChon = locNSXTab2 ? locNSXTab2.value : 'TAT_CA';

        danhSachDaDuyetLoc = daDuyetGoc.filter(sp => {
            const matchTuKhoa = !tuKhoa ||
                sp.tenThuoc.toLowerCase().includes(tuKhoa) ||
                sp.soDangKy.toLowerCase().includes(tuKhoa) ||
                sp.hoatChat.toLowerCase().includes(tuKhoa) ||
                sp.tenNSX.toLowerCase().includes(tuKhoa);

            const matchNSX = nsxChon === 'TAT_CA' || String(sp.nsxId) === nsxChon;

            return matchTuKhoa && matchNSX;
        });

        trangTab2 = 1;
        renderTab2();
    }

    if (oTimKiemTab1) oTimKiemTab1.addEventListener("input", locTab1);
    if (locNSXTab1) locNSXTab1.addEventListener("change", locTab1);
    if (locTTTab1) locTTTab1.addEventListener("change", locTab1);

    if (oTimKiemTab2) oTimKiemTab2.addEventListener("input", locTab2);
    if (locNSXTab2) locNSXTab2.addEventListener("change", locTab2);

    // 9. XỬ LÝ MODAL XEM HỒ SƠ (TAB 1)
    function moModalXemHoSo(item) {
        hoSoDangChon = item;
        const el = id => document.getElementById(id);

        el("panelTieuDeChinh").textContent = "Thẩm Định Hồ Sơ Thuốc:";
        el("panelTenThuocTitle").textContent = item.tenThuoc + " (" + item.soDangKy + ")";

        let badgeHtml = item.trangThai === 'TU_CHOI'
            ? `<span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-red-100 text-red-700 border border-red-300"><i class="fa-solid fa-circle-xmark"></i> Bị từ chối</span>`
            : `<span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300"><i class="fa-solid fa-clock"></i> Chờ duyệt</span>`;

        el("panelTrangThaiSPBadge").innerHTML = badgeHtml;
        el("panelTenThuoc").textContent = item.tenThuoc;
        el("panelSoDK").textContent = item.soDangKy;
        el("panelHoatChat").textContent = item.hoatChat || "--";
        el("panelHamLuong").textContent = item.hamLuong || "--";
        el("panelDangBaoChe").textContent = item.dangBaoChe || "--";
        el("panelTenNSX").textContent = item.tenNSX || "--";
        el("panelMstNSX").textContent = item.mstNSX || "--";
        el("panelGiayPhepNSX").textContent = item.giayPhepNSX || "--";
        el("panelDiaChiNSX").textContent = item.diaChiNSX || "--";

        el("panelTrangThaiToChucNSX").innerHTML = (item.trangThaiToChucNSX === "DA_DUYET")
            ? `<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">Đã duyệt</span>`
            : `<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-700 border border-red-300">Bị thu hồi quyền</span>`;

        const canhBao = el("modalCanhBaoNSXBiThuHoi");
        if (canhBao) {
            if (item.trangThaiToChucNSX === "BI_THU_HOI") canhBao.classList.remove("hidden");
            else canhBao.classList.add("hidden");
        }

        const lich = el("panelDanhSachLichSuNopSP");
        if (lich) {
            let html = "";
            if (item.versionHoSo > 1) {
                html += `<div class="p-2 bg-amber-50 border border-amber-200 rounded-xl text-[11px] text-amber-800"><i class="fa-solid fa-rotate-right mr-1"></i>Đây là lần nộp thứ <strong>${item.versionHoSo}</strong>.</div>`;
            } else {
                html += `<div class="p-2 bg-slate-50 border border-slate-200 rounded-xl text-[11px] text-slate-500 italic"><i class="fa-solid fa-circle-info mr-1"></i>Đây là lần nộp hồ sơ đăng ký đầu tiên.</div>`;
            }
            if (item.lyDoTuChoi) {
                html += `<div class="p-2 bg-red-50 border border-red-200 rounded-xl text-[11px] text-red-800"><strong>Lý do từ chối gần nhất:</strong> ${item.lyDoTuChoi}</div>`;
            }
            lich.innerHTML = html;
        }

        if (item.trangThai === "CHO_DUYET") panelCumNutHanhDong.classList.remove("hidden");
        else panelCumNutHanhDong.classList.add("hidden");

        modalXemHoSoSP.classList.remove("hidden");
    }

    document.querySelectorAll(".dong-modal-hoso-sp").forEach(b => b.addEventListener("click", () => modalXemHoSoSP.classList.add("hidden")));

    // 10. PHÊ DUYỆT (AJAX 2 BƯỚC)
    const nutMoDuyet = document.getElementById("panelNutMoModalDuyetSP");
    if (nutMoDuyet) {
        nutMoDuyet.addEventListener("click", function () {
            if (!hoSoDangChon) return;
            document.getElementById("duyetModalTenThuoc").textContent = hoSoDangChon.tenThuoc;
            document.getElementById("duyetModalSoDK").textContent = hoSoDangChon.soDangKy;
            document.getElementById("duyetModalNSX").textContent = hoSoDangChon.tenNSX || "--";
            formDuyet.action = hoSoDangChon.urlPheDuyet;
            modalDuyet.classList.remove("hidden");
        });
    }
    document.querySelectorAll(".dong-modal-duyet-sp").forEach(b => b.addEventListener("click", () => modalDuyet.classList.add("hidden")));

    if (formDuyet) {
        formDuyet.addEventListener("submit", function (e) {
            e.preventDefault();
            modalDuyet.classList.add("hidden");
            if (modalXemHoSoSP) modalXemHoSoSP.classList.add("hidden");

            iconTienTrinh.className = "text-4xl text-indigo-600 animate-spin mx-auto";
            iconTienTrinh.innerHTML = `<i class="fa-solid fa-circle-notch"></i>`;
            tieuDeTienTrinh.textContent = "Bước 1/2: Đang kết nối Ethereum Sepolia...";
            noiDungTienTrinh.innerHTML = `Đang mã hóa dữ liệu hồ sơ và chờ Smart Contract ký ghi nhận On-Chain. Vui lòng không tắt trình duyệt...`;
            cumNutDongTienTrinh.classList.add("hidden");
            modalTienTrinh.classList.remove("hidden");

            const formData = new FormData(formDuyet);

            fetch(formDuyet.action, {
                method: "POST",
                body: formData,
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    iconTienTrinh.className = "text-5xl text-emerald-600 mx-auto animate-bounce";
                    iconTienTrinh.innerHTML = `<i class="fa-solid fa-circle-check"></i>`;
                    tieuDeTienTrinh.textContent = "Bước 2/2: Phê Duyệt & Ghi On-Chain Thành Công!";
                    noiDungTienTrinh.innerHTML = `
                        Thuốc <strong>${data.tenSanPham}</strong> đã được phê duyệt lưu hành toàn quốc.<br>
                        <span class="font-mono text-[11px] text-cyan-800 block pt-1 font-bold">TxHash: ${data.txHash}</span>
                    `;
                    cumNutDongTienTrinh.classList.remove("hidden");
                } else {
                    iconTienTrinh.className = "text-5xl text-red-600 mx-auto";
                    iconTienTrinh.innerHTML = `<i class="fa-solid fa-circle-xmark"></i>`;
                    tieuDeTienTrinh.textContent = "Thất Bại: Không Thể Ghi Blockchain!";
                    noiDungTienTrinh.innerHTML = `<span class="text-red-600 font-semibold">${data.message}</span><br><span class="text-slate-500 text-[11px]">Hồ sơ vẫn được giữ nguyên ở trạng thái Chờ Duyệt trong CSDL.</span>`;
                    cumNutDongTienTrinh.classList.remove("hidden");
                }
            })
            .catch(err => {
                iconTienTrinh.className = "text-5xl text-red-600 mx-auto";
                iconTienTrinh.innerHTML = `<i class="fa-solid fa-circle-xmark"></i>`;
                tieuDeTienTrinh.textContent = "Lỗi Kết Nối Máy Chủ!";
                noiDungTienTrinh.textContent = err.message || "Không thể kết nối đến máy chủ Signer Blockchain.";
                cumNutDongTienTrinh.classList.remove("hidden");
            });
        });
    }

    // 11. TỪ CHỐI (OFF-CHAIN)
    const nutMoTuChoi = document.getElementById("panelNutMoModalTuChoiSP");
    if (nutMoTuChoi) {
        nutMoTuChoi.addEventListener("click", function () {
            if (!hoSoDangChon) return;
            document.getElementById("tuChoiModalTenThuoc").textContent = hoSoDangChon.tenThuoc;
            document.getElementById("tuChoiModalSoDK").textContent = hoSoDangChon.soDangKy;
            nhapLyDo.value = "";
            demKyTu.textContent = "0/500";
            capNhatNutTuChoi();
            formTuChoi.action = hoSoDangChon.urlTuChoi;
            modalTuChoi.classList.remove("hidden");
        });
    }
    document.querySelectorAll(".dong-modal-tuchoi-sp").forEach(b => b.addEventListener("click", () => modalTuChoi.classList.add("hidden")));

    function capNhatNutTuChoi() {
        const len = nhapLyDo ? nhapLyDo.value.trim().length : 0;
        if (demKyTu) demKyTu.textContent = `${len}/500`;
        if (nutXacNhanTuChoi) {
            if (len > 0) {
                nutXacNhanTuChoi.disabled = false;
                nutXacNhanTuChoi.className = "px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl shadow-md transition-all flex items-center gap-1.5 cursor-pointer";
            } else {
                nutXacNhanTuChoi.disabled = true;
                nutXacNhanTuChoi.className = "px-6 py-2.5 bg-slate-200 text-slate-400 font-bold rounded-xl transition-all flex items-center gap-1.5 cursor-not-allowed";
            }
        }
    }

    if (nhapLyDo) nhapLyDo.addEventListener("input", capNhatNutTuChoi);

    // CHẠY HÀM KHỞI TẠO BẢNG
    locTab1();
    locTab2();
});
document.addEventListener('DOMContentLoaded', function () {
    const EXPLORER_BASE_URL = "https://amoy.polygonscan.com/tx/";
    const ITEMS_PER_PAGE = 3;
    let trangHienTai = 1;

    // ══ DỮ LIỆU MÔ PHỎNG CÁC LÔ HÀNG ĐANG CHUYỂN ĐẾN NHÀ THUỐC (PENDING) ══
    let danhSachTiepNhan = [
        {
            maGiaoDich: 'TRF-NT-2026-101',
            maLo: 'LOT-PARA-2026',
            tenSanPham: 'Paracetamol 500mg',
            soDangKy: 'VD-28491-18',
            nhaSanXuat: 'Công ty Cổ phần Dược phẩm Medipharco',
            benGui: 'Tổng Công ty Dược Việt Nam - Vinapharm',
            maDoiTacGui: 'VINAPHARM',
            soLuong: 1500,
            hanDung: '2029-01-15',
            ngayGui: '2026-02-08 14:30',
            txHash: '0x3a4b5c6d7e8f9a0b1c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b'
        },
        {
            maGiaoDich: 'TRF-NT-2026-102',
            maLo: 'LOT-DES-2026-01',
            tenSanPham: 'Deslotid OPV 5mg',
            soDangKy: 'VD-19234-15',
            nhaSanXuat: 'Công ty Cổ phần Dược phẩm OPV',
            benGui: 'Dược Phẩm CPC1 Hà Nội',
            maDoiTacGui: 'CPC1',
            soLuong: 800,
            hanDung: '2028-02-05',
            ngayGui: '2026-02-09 10:15',
            txHash: '0x88f2a1b9c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0'
        },
        {
            maGiaoDich: 'TRF-NT-2026-103',
            maLo: 'LOT-CEFA-2026-02',
            tenSanPham: 'Cefalexin 500mg',
            soDangKy: 'VD-33012-20',
            nhaSanXuat: 'Dược Hậu Giang (DHG Pharma)',
            benGui: 'Dược Phẩm Vimedimex TP.HCM',
            maDoiTacGui: 'VIMEDIMEX',
            soLuong: 1200,
            hanDung: '2027-11-20',
            ngayGui: '2026-02-09 16:00',
            txHash: '0x71a49f2b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4e5'
        },
        {
            maGiaoDich: 'TRF-NT-2026-104',
            maLo: 'LOT-ASTEX-2026',
            tenSanPham: 'Siro Ho Astex-S 90ml',
            soDangKy: 'VD-22991-17',
            nhaSanXuat: 'Dược Phẩm OPC',
            benGui: 'Tổng Công ty Dược Việt Nam - Vinapharm',
            maDoiTacGui: 'VINAPHARM',
            soLuong: 600,
            hanDung: '2026-06-30', // Cận date (< 6 tháng)
            ngayGui: '2026-02-10 09:00',
            txHash: '0x99a8b7c6d5e4f3a2b1c0d9e8f7a6b5c4d3e2f1a0b9c8d7e6f5a4b3c2d1e0f9a'
        }
    ];

    const thanBang = document.getElementById('thanBangTiepNhan');
    const oTimKiem = document.getElementById('oTimKiemNhan');
    const locNhaPhanPhoi = document.getElementById('locNhaPhanPhoi');
    const locHanDung = document.getElementById('locHanDung');
    const tongSoBadge = document.getElementById('tongSoLôHienThi');
    const thongTinPhanTrang = document.getElementById('thongTinPhanTrang');
    const cumNutPhanTrang = document.getElementById('cumNutPhanTrang');

    // ══ 1. RENDER BẢNG TIẾP NHẬN & PHÂN TRANG ══
    function renderBangTiepNhan() {
        const tuKhoa = oTimKiem.value.trim().toLowerCase();
        const nppLoc = locNhaPhanPhoi.value;
        const hanDungLoc = locHanDung.value;

        const danhSachLoc = danhSachTiepNhan.filter(item => {
            const matchTuKhoa = item.maGiaoDich.toLowerCase().includes(tuKhoa) ||
                               item.maLo.toLowerCase().includes(tuKhoa) ||
                               item.tenSanPham.toLowerCase().includes(tuKhoa) ||
                               item.benGui.toLowerCase().includes(tuKhoa);

            let matchNPP = true;
            if (nppLoc !== 'TAT_CA') matchNPP = (item.maDoiTacGui === nppLoc);

            let matchHanDung = true;
            if (hanDungLoc === 'CAN_HAN') matchHanDung = (item.hanDung <= '2026-08-01');
            else if (hanDungLoc === 'CON_HAN') matchHanDung = (item.hanDung > '2026-08-01');

            return matchTuKhoa && matchNPP && matchHanDung;
        });

        tongSoBadge.textContent = `Hiển thị: ${danhSachLoc.length} lô`;

        const tongSoTrang = Math.ceil(danhSachLoc.length / ITEMS_PER_PAGE) || 1;
        if (trangHienTai > tongSoTrang) trangHienTai = tongSoTrang;

        const batDau = (trangHienTai - 1) * ITEMS_PER_PAGE;
        const ketThuc = batDau + ITEMS_PER_PAGE;
        const duLieuTrang = danhSachLoc.slice(batDau, ketThuc);

        thanBang.innerHTML = '';

        if (duLieuTrang.length === 0) {
            thanBang.innerHTML = `<tr><td colspan="9" class="text-center py-8 text-slate-400 italic">Không có lô hàng nào phù hợp với bộ lọc tìm kiếm.</td></tr>`;
            thongTinPhanTrang.textContent = "Không có bản ghi nào";
            cumNutPhanTrang.innerHTML = '';
            return;
        }

        thongTinPhanTrang.textContent = `Đang hiển thị ${batDau + 1} đến ${Math.min(ketThuc, danhSachLoc.length)} trên tổng số ${danhSachLoc.length} lô`;

        duLieuTrang.forEach(item => {
            const tr = document.createElement('tr');
            tr.className = 'dong-tiep-nhan transition-colors';
            tr.innerHTML = `
                <td class="py-3.5 px-4 font-mono font-bold text-cyan-900 whitespace-nowrap">${item.maGiaoDich}</td>
                <td class="py-3.5 px-4 font-mono font-bold text-slate-900">${item.maLo}</td>
                <td class="py-3.5 px-4 font-semibold text-slate-900">${item.tenSanPham}</td>
                <td class="py-3.5 px-4 text-slate-700">${item.benGui}</td>
                <td class="py-3.5 px-4 text-right font-mono font-bold text-cyan-800">${item.soLuong.toLocaleString()}</td>
                <td class="py-3.5 px-4 font-mono text-slate-600 text-[11px]">${item.hanDung}</td>
                <td class="py-3.5 px-4 text-slate-500 font-mono text-[11px]">${item.ngayGui}</td>
                <td class="py-3.5 px-4 text-center">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300">
                        <i class="fa-solid fa-clock mr-1"></i> PENDING
                    </span>
                </td>
                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                    <div class="inline-flex items-center gap-1.5">
                        <button type="button" class="btn-xem-chi-tiet px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-all flex items-center gap-1" data-magd="${item.maGiaoDich}">
                            <i class="fa-solid fa-eye"></i> Chi Tiết
                        </button>
                        <button type="button" class="btn-nhan-hang-row px-2.5 py-1.5 rounded-lg bg-cyan-700 hover:bg-cyan-800 text-white font-bold text-xs transition-all shadow-2xs flex items-center gap-1" data-magd="${item.maGiaoDich}">
                            <i class="fa-solid fa-check"></i> Nhận Hàng
                        </button>
                    </div>
                </td>
            `;
            thanBang.appendChild(tr);
        });

        renderPhanTrang(tongSoTrang);
        ganSuKienNutBang();
    }

    // ══ 2. CỤM NÚT PHÂN TRANG ══
    function renderPhanTrang(tongSoTrang) {
        cumNutPhanTrang.innerHTML = '';
        if (tongSoTrang <= 1) return;

        const btnPrev = document.createElement('button');
        btnPrev.className = `px-2.5 py-1 rounded-lg border text-xs font-bold transition-all ${trangHienTai === 1 ? 'border-slate-200 text-slate-300 cursor-not-allowed' : 'border-slate-300 text-slate-700 hover:bg-slate-100'}`;
        btnPrev.innerHTML = `<i class="fa-solid fa-angle-left"></i>`;
        btnPrev.disabled = (trangHienTai === 1);
        btnPrev.addEventListener('click', () => { if (trangHienTai > 1) { trangHienTai--; renderBangTiepNhan(); } });
        cumNutPhanTrang.appendChild(btnPrev);

        for (let i = 1; i <= tongSoTrang; i++) {
            const btnPage = document.createElement('button');
            btnPage.className = `w-7 h-7 rounded-lg text-xs font-bold transition-all ${trangHienTai === i ? 'bg-cyan-700 text-white shadow-2xs' : 'border border-slate-200 text-slate-700 hover:bg-slate-100'}`;
            btnPage.textContent = i;
            btnPage.addEventListener('click', () => { trangHienTai = i; renderBangTiepNhan(); });
            cumNutPhanTrang.appendChild(btnPage);
        }

        const btnNext = document.createElement('button');
        btnNext.className = `px-2.5 py-1 rounded-lg border text-xs font-bold transition-all ${trangHienTai === tongSoTrang ? 'border-slate-200 text-slate-300 cursor-not-allowed' : 'border-slate-300 text-slate-700 hover:bg-slate-100'}`;
        btnNext.innerHTML = `<i class="fa-solid fa-angle-right"></i>`;
        btnNext.disabled = (trangHienTai === tongSoTrang);
        btnNext.addEventListener('click', () => { if (trangHienTai < tongSoTrang) { trangHienTai++; renderBangTiepNhan(); } });
        cumNutPhanTrang.appendChild(btnNext);
    }

    // ══ 3. XỬ LÝ MODAL: CHI TIẾT & XÁC NHẬN NHẬP KHO ══
    const modalXacNhanNhan = document.getElementById('modalXacNhanNhan');
    const modalChiTietPending = document.getElementById('modalChiTietPending');
    const nutXacNhanNhapKho = document.getElementById('nutXacNhanNhapKho');
    let itemDangChon = null;

    function ganSuKienNutBang() {
        // Mở modal xác nhận nhận hàng
        document.querySelectorAll('.btn-nhan-hang-row').forEach(btn => {
            btn.addEventListener('click', function () {
                const magd = this.getAttribute('data-magd');
                itemDangChon = danhSachTiepNhan.find(x => x.maGiaoDich === magd);
                if (!itemDangChon) return;

                document.getElementById('nhanMaGiaoDich').textContent = itemDangChon.maGiaoDich;
                document.getElementById('nhanMaLo').textContent = itemDangChon.maLo;
                document.getElementById('nhanTenSanPham').textContent = itemDangChon.tenSanPham;
                document.getElementById('nhanBenGui').textContent = itemDangChon.benGui;
                document.getElementById('nhanSoLuong').textContent = `${itemDangChon.soLuong.toLocaleString()} kiện/hộp`;
                document.getElementById('nhanHanDung').textContent = itemDangChon.hanDung;

                modalXacNhanNhan.classList.remove('hidden');
            });
        });

        // Mở modal chi tiết lô thuốc
        document.querySelectorAll('.btn-xem-chi-tiet').forEach(btn => {
            btn.addEventListener('click', function () {
                const magd = this.getAttribute('data-magd');
                itemDangChon = danhSachTiepNhan.find(x => x.maGiaoDich === magd);
                if (!itemDangChon) return;

                document.getElementById('detailMaLoTitle').textContent = itemDangChon.maLo;
                document.getElementById('detailSanPham').textContent = itemDangChon.tenSanPham;
                document.getElementById('detailSoDK').textContent = itemDangChon.soDangKy;
                document.getElementById('detailNhaSanXuat').textContent = itemDangChon.nhaSanXuat;
                document.getElementById('detailBenGui').textContent = itemDangChon.benGui;
                document.getElementById('detailSoLuong').textContent = `${itemDangChon.soLuong.toLocaleString()} hộp`;
                document.getElementById('detailHanDung').textContent = itemDangChon.hanDung;
                document.getElementById('detailNgayGui').textContent = itemDangChon.ngayGui;
                document.getElementById('detailTxHash').textContent = itemDangChon.txHash;
                document.getElementById('detailTxLink').href = `${EXPLORER_BASE_URL}${itemDangChon.txHash}`;

                modalChiTietPending.classList.remove('hidden');
            });
        });
    }

    // Đóng modal
    document.querySelectorAll('.dong-modal-nhan').forEach(btn => {
        btn.addEventListener('click', () => modalXacNhanNhan.classList.add('hidden'));
    });

    document.querySelectorAll('.dong-modal-detail').forEach(btn => {
        btn.addEventListener('click', () => modalChiTietPending.classList.add('hidden'));
    });

    // Chuyển từ modal chi tiết sang nhận hàng
    document.getElementById('nutChuyenSangNhanHang').addEventListener('click', function () {
        modalChiTietPending.classList.add('hidden');
        if (itemDangChon) {
            document.getElementById('nhanMaGiaoDich').textContent = itemDangChon.maGiaoDich;
            document.getElementById('nhanMaLo').textContent = itemDangChon.maLo;
            document.getElementById('nhanTenSanPham').textContent = itemDangChon.tenSanPham;
            document.getElementById('nhanBenGui').textContent = itemDangChon.benGui;
            document.getElementById('nhanSoLuong').textContent = `${itemDangChon.soLuong.toLocaleString()} kiện/hộp`;
            document.getElementById('nhanHanDung').textContent = itemDangChon.hanDung;
            modalXacNhanNhan.classList.remove('hidden');
        }
    });

    // Sao chép txHash
    document.getElementById('nutCopyDetailTx').addEventListener('click', function () {
        if (!itemDangChon) return;
        navigator.clipboard.writeText(itemDangChon.txHash).then(() => {
            this.innerHTML = `<i class="fa-solid fa-check text-emerald-600"></i> Đã sao chép`;
            setTimeout(() => { this.innerHTML = `<i class="fa-regular fa-copy"></i> Sao chép`; }, 1800);
        });
    });

    // Xác nhận nhập kho bán lẻ
    nutXacNhanNhapKho.addEventListener('click', function () {
        if (!itemDangChon) return;

        // Xóa lô khỏi danh sách chờ PENDING
        danhSachTiepNhan = danhSachTiepNhan.filter(x => x.maGiaoDich !== itemDangChon.maGiaoDich);

        alert(`Xác nhận nhập kho thành công! Đã ghi nhận chuyển giao RECEIVED trên Blockchain và kích hoạt ${itemDangChon.soLuong.toLocaleString()} đơn vị hộp ${itemDangChon.tenSanPham} sang trạng thái sẵn sàng bán lẻ (AVAILABLE) tại quầy thuốc.`);
        modalXacNhanNhan.classList.add('hidden');
        renderBangTiepNhan();
    });

    // ══ 4. LẮNG NGHE SỰ KIỆN TÌM KIẾM & BỘ LỌC ══
    oTimKiem.addEventListener('input', () => { trangHienTai = 1; renderBangTiepNhan(); });
    locNhaPhanPhoi.addEventListener('change', () => { trangHienTai = 1; renderBangTiepNhan(); });
    locHanDung.addEventListener('change', () => { trangHienTai = 1; renderBangTiepNhan(); });

    renderBangTiepNhan();
});
document.addEventListener('DOMContentLoaded', function () {
    const EXPLORER_BASE_URL = "https://amoy.polygonscan.com/tx/";
    const ITEMS_PER_PAGE = 3;
    let trangHienTaiCho = 1;
    let trangHienTaiDaNhan = 1;

    // Helper: Lấy ngày hiện tại YYYY-MM-DD để so sánh
    function layNgayHienTai() {
        return new Date().toISOString().split('T')[0];
    }

    // Helper: Chuyển đổi định dạng YYYY-MM-DD sang dd/mm/yyyy
    function dinhDangNgay(ngayYMD) {
        if (!ngayYMD) return '--';
        const parts = ngayYMD.split('-');
        if (parts.length === 3) {
            return `${parts[2]}/${parts[1]}/${parts[0]}`;
        }
        return ngayYMD;
    }

    // ══════════════════════════════════════════════════════════════════
    // 1. DỮ LIỆU MÔ PHỎNG TAB 1: LÔ HÀNG ĐANG CHỜ XÁC NHẬN (PENDING)
    // ══════════════════════════════════════════════════════════════════
    let danhSachChoXacNhan = [
        {
            maGiaoDich: 'CG-000101',
            maLo: 'LOT-PARA-2026',
            tenSanPham: 'Paracetamol 500mg',
            soDangKy: 'VD-28491-18',
            nhaSanXuat: 'Công ty Cổ phần Dược phẩm Medipharco',
            benGui: 'Tổng Công ty Dược Việt Nam - Vinapharm',
            maDoiTacGui: 'VINAPHARM',
            soLuong: 1500,
            hanDung: '2029-01-15',
            ngayGui: '08/02/2026 14:30',
            trangThaiLo: 'CREATED',
            doiChieuKhop: true,
            txHash: '0x3a4b5c6d7e8f9a0b1c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b'
        },
        {
            maGiaoDich: 'CG-000102',
            maLo: 'LOT-DES-2026-01',
            tenSanPham: 'Deslotid OPV 5mg',
            soDangKy: 'VD-19234-15',
            nhaSanXuat: 'Công ty Cổ phần Dược phẩm OPV',
            benGui: 'Dược Phẩm CPC1 Hà Nội',
            maDoiTacGui: 'CPC1',
            soLuong: 800,
            hanDung: '2028-02-05',
            ngayGui: '09/02/2026 10:15',
            trangThaiLo: 'CREATED',
            doiChieuKhop: true,
            txHash: '0x88f2a1b9c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0'
        },
        {
            // Lô đã bị thu hồi: Smart Contract từ chối nhận, khóa nút nhận hàng
            maGiaoDich: 'CG-000103',
            maLo: 'LOT-AMOX-2025-09',
            tenSanPham: 'Amoxicillin 500mg',
            soDangKy: 'VD-19234-15',
            nhaSanXuat: 'Dược Phẩm Pharbaco',
            benGui: 'Dược Phẩm Vimedimex TP.HCM',
            maDoiTacGui: 'VIMEDIMEX',
            soLuong: 1200,
            hanDung: '2028-09-10',
            ngayGui: '09/02/2026 16:00',
            trangThaiLo: 'RECALLED', // BỊ THU HỒI
            doiChieuKhop: false,
            txHash: '0x71a49f2b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4e5'
        },
        {
            // Lô đã hết hạn sử dụng: Cho nhận nhưng hiện cảnh báo vàng
            maGiaoDich: 'CG-000104',
            maLo: 'LOT-CEFA-2024-EX',
            tenSanPham: 'Cefalexin 500mg',
            soDangKy: 'VD-33012-20',
            nhaSanXuat: 'Dược Hậu Giang (DHG Pharma)',
            benGui: 'Tổng Công ty Dược Việt Nam - Vinapharm',
            maDoiTacGui: 'VINAPHARM',
            soLuong: 500,
            hanDung: '2025-01-01', // QUÁ HẠN
            trangThaiLo: 'CREATED',
            doiChieuKhop: true,
            txHash: '0x12a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4e5f6a7b8c9d0e1f2a3'
        }
    ];

    // ══════════════════════════════════════════════════════════════════
    // 2. DỮ LIỆU MÔ PHỎNG TAB 2: LỊCH SỬ ĐÃ TIẾP NHẬN (RECEIVED)
    // ══════════════════════════════════════════════════════════════════
    let danhSachDaTiepNhan = [
        {
            maGiaoDich: 'CG-000078',
            maLo: 'LOT-PARA-2026',
            tenSanPham: 'Paracetamol 500mg',
            soDangKy: 'VD-28491-18',
            nhaSanXuat: 'Công ty Cổ phần Dược phẩm Medipharco',
            benGui: 'Tổng Công ty Dược Việt Nam - Vinapharm',
            soLuong: 1000,
            hanDung: '2029-01-15',
            thoiGianGui: '05/02/2026 10:00',
            thoiGianXacNhan: '05/02/2026 14:20',
            trangThaiLoHienTai: 'CREATED',
            tongDaNhanLo: 2500, // Tổng đã nhận của cả lô qua tất cả các đợt
            daBan: 850,         // Đã bán cho người tiêu dùng
            conLaiTaiKho: 1650, // Còn bán được
            txHash: '0x8f2d5e1b4a3c79a2f60e1d8b5c9a4e2f1a3b5c7d9e1f2a4b6c8d0e2f4a6b8c0',
            lyDoThuHoi: '',
            ngayThuHoi: ''
        },
        {
            // Minh họa lô đã nhận nhưng sau đó Cục Quản lý Dược ra quyết định thu hồi
            maGiaoDich: 'CG-000065',
            maLo: 'LOT-AMOX-2025-09',
            tenSanPham: 'Amoxicillin 500mg',
            soDangKy: 'VD-19234-15',
            nhaSanXuat: 'Dược Phẩm Pharbaco',
            benGui: 'Dược Phẩm Vimedimex TP.HCM',
            soLuong: 500,
            hanDung: '2028-09-10',
            thoiGianGui: '15/01/2026 08:30',
            thoiGianXacNhan: '15/01/2026 11:15',
            trangThaiLoHienTai: 'RECALLED', // BỊ THU HỒI
            tongDaNhanLo: 500,
            daBan: 120,
            conLaiTaiKho: 380, // Đã bị khóa bán
            txHash: '0x71a49f2b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4e5',
            lyDoThuHoi: 'Không đạt tiêu chuẩn độ hòa tan theo công văn 458/QLD của Cục Quản lý Dược.',
            ngayThuHoi: '05/02/2026 16:45'
        }
    ];

    // ══════════════════════════════════════════════════════════════════
    // 3. CHUYỂN ĐỔI TAB GIAO DIỆN
    // ══════════════════════════════════════════════════════════════════
    const tabChoXacNhan = document.getElementById('tabChoXacNhan');
    const tabDaTiepNhan = document.getElementById('tabDaTiepNhan');
    const khungTabChoXacNhan = document.getElementById('khungTabChoXacNhan');
    const khungTabDaTiepNhan = document.getElementById('khungTabDaTiepNhan');

    tabChoXacNhan.addEventListener('click', () => {
        tabChoXacNhan.className = "flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all bg-cyan-700 text-white shadow-sm";
        tabDaTiepNhan.className = "flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all text-slate-600 hover:text-cyan-700 hover:bg-cyan-50";
        khungTabChoXacNhan.classList.remove('hidden');
        khungTabDaTiepNhan.classList.add('hidden');
        renderBangChoXacNhan();
    });

    tabDaTiepNhan.addEventListener('click', () => {
        tabDaTiepNhan.className = "flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all bg-cyan-700 text-white shadow-sm";
        tabChoXacNhan.className = "flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all text-slate-600 hover:text-cyan-700 hover:bg-cyan-50";
        khungTabDaTiepNhan.classList.remove('hidden');
        khungTabChoXacNhan.classList.add('hidden');
        renderBangDaTiepNhan();
    });

    // ══════════════════════════════════════════════════════════════════
    // 4. RENDER TAB 1: DANH SÁCH CHỜ XÁC NHẬN & PHÂN TRANG
    // ══════════════════════════════════════════════════════════════════
    const thanBangTiepNhan = document.getElementById('thanBangTiepNhan');
    const oTimKiem = document.getElementById('oTimKiemNhan');
    const locNhaPhanPhoi = document.getElementById('locNhaPhanPhoi');
    const locHanDung = document.getElementById('locHanDung');
    const tongSoBadge = document.getElementById('tongSoLoHienThi');
    const badgeSoLuongChoXacNhan = document.getElementById('badgeSoLuongChoXacNhan');
    const thongTinPhanTrang = document.getElementById('thongTinPhanTrang');
    const cumNutPhanTrang = document.getElementById('cumNutPhanTrang');

    function renderBangChoXacNhan() {
        const tuKhoa = oTimKiem.value.trim().toLowerCase();
        const nppLoc = locNhaPhanPhoi.value;
        const hanDungLoc = locHanDung.value;
        const homNay = layNgayHienTai();

        const danhSachLoc = danhSachChoXacNhan.filter(item => {
            const matchTuKhoa = item.maGiaoDich.toLowerCase().includes(tuKhoa) ||
                               item.maLo.toLowerCase().includes(tuKhoa) ||
                               item.tenSanPham.toLowerCase().includes(tuKhoa) ||
                               item.benGui.toLowerCase().includes(tuKhoa);

            let matchNPP = true;
            if (nppLoc !== 'TAT_CA') matchNPP = (item.maDoiTacGui === nppLoc);

            let matchHanDung = true;
            if (hanDungLoc === 'HET_HAN') matchHanDung = (item.hanDung < homNay);
            else if (hanDungLoc === 'CON_HAN') matchHanDung = (item.hanDung >= homNay);

            return matchTuKhoa && matchNPP && matchHanDung;
        });

        badgeSoLuongChoXacNhan.textContent = danhSachChoXacNhan.length;
        tongSoBadge.textContent = `Hiển thị: ${danhSachLoc.length} lô`;

        const tongSoTrang = Math.ceil(danhSachLoc.length / ITEMS_PER_PAGE) || 1;
        if (trangHienTaiCho > tongSoTrang) trangHienTaiCho = tongSoTrang;

        const batDau = (trangHienTaiCho - 1) * ITEMS_PER_PAGE;
        const ketThuc = batDau + ITEMS_PER_PAGE;
        const duLieuTrang = danhSachLoc.slice(batDau, ketThuc);

        thanBangTiepNhan.innerHTML = '';

        if (duLieuTrang.length === 0) {
            thanBangTiepNhan.innerHTML = `<tr><td colspan="9" class="text-center py-8 text-slate-400 italic">Không có lô thuốc nào đang chờ xác nhận phù hợp với bộ lọc.</td></tr>`;
            thongTinPhanTrang.textContent = "Không có bản ghi nào";
            cumNutPhanTrang.innerHTML = '';
            return;
        }

        thongTinPhanTrang.textContent = `Đang hiển thị ${batDau + 1} đến ${Math.min(ketThuc, danhSachLoc.length)} trên tổng số ${danhSachLoc.length} lô`;

        duLieuTrang.forEach(item => {
            const daHetHan = (item.hanDung < homNay);
            const biThuHoi = (item.trangThaiLo === 'RECALLED');

            const coTheNhan = !biThuHoi && item.doiChieuKhop;
            let tooltipKhoa = '';
            if (biThuHoi) tooltipKhoa = 'Lô thuốc đã bị thu hồi - Smart Contract từ chối nhận';
            else if (!item.doiChieuKhop) tooltipKhoa = 'Đối chiếu blockchain không khớp';

            let hanDungHtml = `<span class="font-mono text-slate-700 text-[11px] whitespace-nowrap">${dinhDangNgay(item.hanDung)}</span>`;
            if (daHetHan) {
                hanDungHtml += `<span class="badge-chuan ml-1 px-1.5 py-0.2 rounded text-[10px] font-bold bg-amber-100 text-amber-800">Hết hạn</span>`;
            }

            let nhanTrangThaiHtml = '';
            if (biThuHoi) {
                nhanTrangThaiHtml = `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-red-100 text-red-700 border border-red-300"><i class="fa-solid fa-ban"></i> Đã thu hồi</span>`;
            } else {
                nhanTrangThaiHtml = `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300"><i class="fa-solid fa-clock"></i> Chờ xác nhận</span>`;
            }

            const tr = document.createElement('tr');
            tr.className = 'dong-tiep-nhan transition-colors';
            tr.innerHTML = `
                <td class="py-3 px-3 font-mono font-bold text-cyan-900 whitespace-nowrap">${item.maGiaoDich}</td>
                <td class="py-3 px-3 font-mono font-bold text-slate-900 whitespace-nowrap">${item.maLo}</td>
                <td class="py-3 px-3 font-semibold text-slate-900 leading-tight">${item.tenSanPham}</td>
                <td class="py-3 px-3 text-slate-700 leading-tight">${item.benGui}</td>
                <td class="py-3 px-2.5 text-center whitespace-nowrap">${hanDungHtml}</td>
                <td class="py-3 px-3 text-right font-mono font-bold text-cyan-800 whitespace-nowrap">${item.soLuong.toLocaleString()} hộp</td>
                <td class="py-3 px-2.5 text-center text-slate-500 font-mono text-[11px] whitespace-nowrap">${item.ngayGui}</td>
                <td class="py-3 px-2.5 text-center whitespace-nowrap">${nhanTrangThaiHtml}</td>
                <td class="py-3 px-3 text-center whitespace-nowrap">
                    <div class="inline-flex items-center justify-center gap-1.5">
                        <button type="button" class="btn-xem-chi-tiet px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-all flex items-center gap-1 border border-slate-300 shadow-2xs" data-magd="${item.maGiaoDich}">
                            <i class="fa-solid fa-eye"></i> Chi Tiết
                        </button>
                        <button type="button" class="btn-nhan-hang-row px-2.5 py-1.5 rounded-lg text-white font-bold text-xs transition-all shadow-2xs flex items-center gap-1 ${coTheNhan ? 'bg-cyan-700 hover:bg-cyan-800' : 'bg-slate-300 text-slate-500 cursor-not-allowed'}" data-magd="${item.maGiaoDich}" ${!coTheNhan ? 'disabled' : ''} title="${tooltipKhoa}">
                            <i class="fa-solid fa-check"></i> Nhận Hàng
                        </button>
                    </div>
                </td>
            `;
            thanBangTiepNhan.appendChild(tr);
        });

        renderPhanTrang(cumNutPhanTrang, trangHienTaiCho, tongSoTrang, (trang) => {
            trangHienTaiCho = trang;
            renderBangChoXacNhan();
        });

        ganSuKienNutBang();
    }

    // ══════════════════════════════════════════════════════════════════
    // 5. RENDER TAB 2: LỊCH SỬ ĐÃ TIẾP NHẬN
    // ══════════════════════════════════════════════════════════════════
    const thanBangDaNhan = document.getElementById('thanBangDaNhan');
    const badgeSoLuongDaNhan = document.getElementById('badgeSoLuongDaNhan');
    const soLuongDaTiepNhanHienThi = document.getElementById('soLuongDaTiepNhanHienThi');
    const thongTinPhanTrangDaNhan = document.getElementById('thongTinPhanTrangDaNhan');
    const cumNutPhanTrangDaNhan = document.getElementById('cumNutPhanTrangDaNhan');

    function renderBangDaTiepNhan() {
        badgeSoLuongDaNhan.textContent = danhSachDaTiepNhan.length;
        soLuongDaTiepNhanHienThi.textContent = `Hiển thị: ${danhSachDaTiepNhan.length} lô`;

        const tongSoTrang = Math.ceil(danhSachDaTiepNhan.length / ITEMS_PER_PAGE) || 1;
        if (trangHienTaiDaNhan > tongSoTrang) trangHienTaiDaNhan = tongSoTrang;

        const batDau = (trangHienTaiDaNhan - 1) * ITEMS_PER_PAGE;
        const ketThuc = batDau + ITEMS_PER_PAGE;
        const duLieuTrang = danhSachDaTiepNhan.slice(batDau, ketThuc);

        thanBangDaNhan.innerHTML = '';

        if (duLieuTrang.length === 0) {
            thanBangDaNhan.innerHTML = `<tr><td colspan="8" class="text-center py-8 text-slate-400 italic">Chưa có lô thuốc nào được tiếp nhận.</td></tr>`;
            thongTinPhanTrangDaNhan.textContent = "Không có bản ghi nào";
            cumNutPhanTrangDaNhan.innerHTML = '';
            return;
        }

        thongTinPhanTrangDaNhan.textContent = `Đang hiển thị ${batDau + 1} đến ${Math.min(ketThuc, danhSachDaTiepNhan.length)} trên tổng số ${danhSachDaTiepNhan.length} lô`;

        duLieuTrang.forEach(item => {
            const tr = document.createElement('tr');
            tr.className = 'dong-da-nhan transition-colors';
            tr.innerHTML = `
                <td class="py-3 px-3 font-mono font-bold text-cyan-900 whitespace-nowrap">${item.maGiaoDich}</td>
                <td class="py-3 px-3 font-mono font-bold text-slate-900 whitespace-nowrap">${item.maLo}</td>
                <td class="py-3 px-3 font-semibold text-slate-900 leading-tight">${item.tenSanPham}</td>
                <td class="py-3 px-3 text-slate-700 leading-tight">${item.benGui}</td>
                <td class="py-3 px-3 text-right font-mono font-bold text-emerald-700 whitespace-nowrap">${item.soLuong.toLocaleString()} hộp</td>
                <td class="py-3 px-2.5 text-center text-slate-500 font-mono text-[11px] whitespace-nowrap">${item.thoiGianXacNhan}</td>
                <td class="py-3 px-2.5 text-center whitespace-nowrap">
                    <span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300">
                        <i class="fa-solid fa-check-double"></i> Đã nhận
                    </span>
                </td>
                <td class="py-3 px-3 text-center whitespace-nowrap">
                    <button type="button" class="btn-xem-chi-tiet-danhan px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-all flex items-center gap-1 border border-slate-300 shadow-2xs mx-auto" data-magd="${item.maGiaoDich}">
                        <i class="fa-solid fa-circle-info text-cyan-800"></i> Chi Tiết
                    </button>
                </td>
            `;
            thanBangDaNhan.appendChild(tr);
        });

        renderPhanTrang(cumNutPhanTrangDaNhan, trangHienTaiDaNhan, tongSoTrang, (trang) => {
            trangHienTaiDaNhan = trang;
            renderBangDaTiepNhan();
        });

        ganSuKienNutDaNhan();
    }

    // ══ HÀM RENDER PHÂN TRANG ══
    function renderPhanTrang(container, trangHienTai, tongSoTrang, onPageChange) {
        container.innerHTML = '';
        if (tongSoTrang <= 1) return;

        const btnPrev = document.createElement('button');
        btnPrev.className = `px-2.5 py-1 rounded-lg border text-xs font-bold transition-all ${trangHienTai === 1 ? 'border-slate-200 text-slate-300 cursor-not-allowed' : 'border-slate-300 text-slate-700 hover:bg-slate-100'}`;
        btnPrev.innerHTML = `<i class="fa-solid fa-angle-left"></i>`;
        btnPrev.disabled = (trangHienTai === 1);
        btnPrev.addEventListener('click', () => { if (trangHienTai > 1) onPageChange(trangHienTai - 1); });
        container.appendChild(btnPrev);

        for (let i = 1; i <= tongSoTrang; i++) {
            const btnPage = document.createElement('button');
            btnPage.className = `w-7 h-7 rounded-lg text-xs font-bold transition-all ${trangHienTai === i ? 'bg-cyan-700 text-white shadow-2xs' : 'border border-slate-200 text-slate-700 hover:bg-slate-100'}`;
            btnPage.textContent = i;
            btnPage.addEventListener('click', () => onPageChange(i));
            container.appendChild(btnPage);
        }

        const btnNext = document.createElement('button');
        btnNext.className = `px-2.5 py-1 rounded-lg border text-xs font-bold transition-all ${trangHienTai === tongSoTrang ? 'border-slate-200 text-slate-300 cursor-not-allowed' : 'border-slate-300 text-slate-700 hover:bg-slate-100'}`;
        btnNext.innerHTML = `<i class="fa-solid fa-angle-right"></i>`;
        btnNext.disabled = (trangHienTai === tongSoTrang);
        btnNext.addEventListener('click', () => { if (trangHienTai < tongSoTrang) onPageChange(trangHienTai + 1); });
        container.appendChild(btnNext);
    }

    // ══════════════════════════════════════════════════════════════════
    // 6. MODAL 1: ĐỐI CHIẾU & XÁC NHẬN NHẬN HÀNG (FR08)
    // ══════════════════════════════════════════════════════════════════
    const modalXacNhanNhan = document.getElementById('modalXacNhanNhan');
    const nutXacNhanNhapKho = document.getElementById('nutXacNhanNhapKho');
    const nhanKetQuaDoiChieu = document.getElementById('nhanKetQuaDoiChieu');
    const linkBaoCaoTuNhanHang = document.getElementById('linkBaoCaoTuNhanHang');
    const canhBaoHetHanModal = document.getElementById('canhBaoHetHanModal');
    let itemDangChon = null;

    function moModalNhanHang(item) {
        itemDangChon = item;
        const homNay = layNgayHienTai();
        const daHetHan = (item.hanDung < homNay);

        document.getElementById('nhanMaGiaoDich').textContent = item.maGiaoDich;
        document.getElementById('nhanMaLo').textContent = item.maLo;
        document.getElementById('nhanTenSanPham').textContent = item.tenSanPham;
        document.getElementById('nhanBenGui').textContent = item.benGui;
        document.getElementById('nhanSoLuong').textContent = `${item.soLuong.toLocaleString()} hộp`;
        document.getElementById('nhanHanDung').textContent = dinhDangNgay(item.hanDung);

        // Lời dẫn cập nhật chuẩn xác theo thiết kế kiến trúc
        document.getElementById('loiDanXacNhanNhan').textContent = 
            `Hệ thống sẽ ghi nhận bạn đã nhận hàng lên blockchain và cộng ${item.soLuong.toLocaleString()} hộp vào kho của bạn.`;

        // Đối chiếu Blockchain
        if (item.doiChieuKhop && item.trangThaiLo !== 'RECALLED') {
            nhanKetQuaDoiChieu.className = "inline-flex items-center gap-1.5 font-bold text-emerald-700 text-xs";
            nhanKetQuaDoiChieu.innerHTML = `<i class="fa-solid fa-circle-check"></i> Khớp (Dữ liệu hợp lệ on-chain)`;
            nutXacNhanNhapKho.disabled = false;
            nutXacNhanNhapKho.className = "px-6 py-2.5 bg-cyan-700 hover:bg-cyan-800 text-white font-bold rounded-xl shadow-md transition-all flex items-center gap-2";
        } else {
            nhanKetQuaDoiChieu.className = "inline-flex items-center gap-1.5 font-bold text-red-600 text-xs";
            nhanKetQuaDoiChieu.innerHTML = `<i class="fa-solid fa-circle-xmark"></i> Không khớp / Lô đã bị thu hồi`;
            nutXacNhanNhapKho.disabled = true;
            nutXacNhanNhapKho.className = "px-6 py-2.5 bg-slate-300 text-slate-500 font-bold rounded-xl transition-all flex items-center gap-2 cursor-not-allowed";
        }

        // Cảnh báo nếu lô hết hạn
        if (daHetHan) {
            canhBaoHetHanModal.classList.remove('hidden');
        } else {
            canhBaoHetHanModal.classList.add('hidden');
        }

        // Tự động điền mã lô vào liên kết báo cáo nghi vấn
        linkBaoCaoTuNhanHang.href = `../trangDungChung/quanLyBaoCaoNghiVan.php?vaiTro=NHA_THUOC&maLo=${encodeURIComponent(item.maLo)}`;

        modalXacNhanNhan.classList.remove('hidden');
    }

    function ganSuKienNutBang() {
        document.querySelectorAll('.btn-nhan-hang-row').forEach(btn => {
            btn.addEventListener('click', function () {
                const magd = this.getAttribute('data-magd');
                const item = danhSachChoXacNhan.find(x => x.maGiaoDich === magd);
                if (item) moModalNhanHang(item);
            });
        });

        document.querySelectorAll('.btn-xem-chi-tiet').forEach(btn => {
            btn.addEventListener('click', function () {
                const magd = this.getAttribute('data-magd');
                const item = danhSachChoXacNhan.find(x => x.maGiaoDich === magd);
                if (!item) return;

                itemDangChon = item;
                document.getElementById('detailMaLoTitle').textContent = item.maLo;
                document.getElementById('detailSanPham').textContent = item.tenSanPham;
                document.getElementById('detailSoDK').textContent = item.soDangKy;
                document.getElementById('detailNhaSanXuat').textContent = item.nhaSanXuat;
                document.getElementById('detailBenGui').textContent = item.benGui;
                document.getElementById('detailSoLuong').textContent = `${item.soLuong.toLocaleString()} hộp`;
                document.getElementById('detailHanDung').textContent = dinhDangNgay(item.hanDung);
                document.getElementById('detailNgayGui').textContent = item.ngayGui;
                document.getElementById('detailTxHash').textContent = item.txHash;
                document.getElementById('detailTxLink').href = `${EXPLORER_BASE_URL}${item.txHash}`;
                document.getElementById('linkBaoCaoTuDetail').href = `../trangDungChung/quanLyBaoCaoNghiVan.php?vaiTro=NHA_THUOC&maLo=${encodeURIComponent(item.maLo)}`;

                document.getElementById('modalChiTietPending').classList.remove('hidden');
            });
        });
    }

    // ══════════════════════════════════════════════════════════════════
    // 7. XỬ LÝ MODAL CHI TIẾT LÔ ĐÃ TIẾP NHẬN (TAB 2)
    // ══════════════════════════════════════════════════════════════════
    const modalChiTietDaTiepNhan = document.getElementById('modalChiTietDaTiepNhan');
    let itemDaNhanDangChon = null;

    function ganSuKienNutDaNhan() {
        document.querySelectorAll('.btn-xem-chi-tiet-danhan').forEach(btn => {
            btn.addEventListener('click', function () {
                const magd = this.getAttribute('data-magd');
                itemDaNhanDangChon = danhSachDaTiepNhan.find(x => x.maGiaoDich === magd);
                if (!itemDaNhanDangChon) return;

                const homNay = layNgayHienTai();
                const daHetHan = (itemDaNhanDangChon.hanDung < homNay);
                const biThuHoi = (itemDaNhanDangChon.trangThaiLoHienTai === 'RECALLED');

                // 1. Thông tin thuốc cơ bản
                document.getElementById('daNhanModalMaLoTitle').textContent = itemDaNhanDangChon.maLo;
                document.getElementById('daNhanModalTenThuoc').textContent = itemDaNhanDangChon.tenSanPham;
                document.getElementById('daNhanModalSoDK').textContent = itemDaNhanDangChon.soDangKy;
                document.getElementById('daNhanModalNhaSanXuat').textContent = itemDaNhanDangChon.nhaSanXuat;
                document.getElementById('daNhanModalBenGiao').textContent = itemDaNhanDangChon.benGui;
                document.getElementById('daNhanModalHanDung').textContent = dinhDangNgay(itemDaNhanDangChon.hanDung);
                document.getElementById('daNhanModalSoLuongDot').textContent = `${itemDaNhanDangChon.soLuong.toLocaleString()} hộp`;
                document.getElementById('daNhanModalThoiGianGui').textContent = itemDaNhanDangChon.thoiGianGui;
                document.getElementById('daNhanModalThoiGianXacNhan').textContent = itemDaNhanDangChon.thoiGianXacNhan;

                // 2. Banner cảnh báo thu hồi / hết hạn
                const bannerThuHoi = document.getElementById('daNhanBannerThuHoi');
                const bannerHetHan = document.getElementById('daNhanBannerHetHan');
                const tagHetHan = document.getElementById('daNhanModalHetHanTag');

                if (biThuHoi) {
                    bannerThuHoi.classList.remove('hidden');
                    document.getElementById('daNhanLyDoThuHoiText').textContent = itemDaNhanDangChon.lyDoThuHoi;
                    document.getElementById('daNhanNgayThuHoiText').textContent = `Thời điểm quyết định thu hồi: ${itemDaNhanDangChon.ngayThuHoi}`;
                } else {
                    bannerThuHoi.classList.add('hidden');
                }

                if (daHetHan && !biThuHoi) {
                    bannerHetHan.classList.remove('hidden');
                    tagHetHan.classList.remove('hidden');
                } else {
                    bannerHetHan.classList.add('hidden');
                    tagHetHan.classList.add('hidden');
                }

                // 3. Hiển thị 3 ô chỉ số tính theo cả lô
                const tongNhan = itemDaNhanDangChon.tongDaNhanLo || itemDaNhanDangChon.soLuong;
                document.getElementById('daNhanTongNhanLo').textContent = `${tongNhan.toLocaleString()} hộp`;
                document.getElementById('daNhanSoLuongDaBan').textContent = `${itemDaNhanDangChon.daBan.toLocaleString()} hộp`;
                
                const conBanBox = document.getElementById('daNhanSoLuongConBan');
                if (biThuHoi) {
                    conBanBox.textContent = `0 hộp (Đã khóa bán)`;
                    conBanBox.className = "font-mono font-bold text-red-600 text-sm";
                } else if (daHetHan) {
                    conBanBox.textContent = `0 hộp (Đã hết hạn)`;
                    conBanBox.className = "font-mono font-bold text-amber-700 text-sm";
                } else {
                    conBanBox.textContent = `${itemDaNhanDangChon.conLaiTaiKho.toLocaleString()} hộp`;
                    conBanBox.className = "font-mono font-bold text-emerald-700 text-sm";
                }

                // 4. Cầu nối sang trang Quản lý bán hàng
                document.getElementById('linkSangQuanLyBanHang').href = `quanLyBanHang.php?maLo=${encodeURIComponent(itemDaNhanDangChon.maLo)}`;

                // 5. Mã giao dịch chuyển giao trên Blockchain (hashBlockchain)
                document.getElementById('daNhanModalTxHash').textContent = itemDaNhanDangChon.txHash;
                document.getElementById('daNhanModalTxLink').href = `${EXPLORER_BASE_URL}${itemDaNhanDangChon.txHash}`;

                // 6. Liên kết báo cáo nghi vấn
                document.getElementById('linkBaoCaoTuDaNhan').href = `../trangDungChung/quanLyBaoCaoNghiVan.php?vaiTro=NHA_THUOC&maLo=${encodeURIComponent(itemDaNhanDangChon.maLo)}`;

                modalChiTietDaTiepNhan.classList.remove('hidden');
            });
        });
    }

    document.querySelectorAll('.dong-modal-danhan-detail').forEach(btn => {
        btn.addEventListener('click', () => modalChiTietDaTiepNhan.classList.add('hidden'));
    });

    document.getElementById('nutCopyDaNhanTx').addEventListener('click', function () {
        if (!itemDaNhanDangChon) return;
        navigator.clipboard.writeText(itemDaNhanDangChon.txHash).then(() => {
            this.innerHTML = `<i class="fa-solid fa-check text-emerald-600"></i> Đã sao chép`;
            setTimeout(() => { this.innerHTML = `<i class="fa-regular fa-copy"></i> Sao chép`; }, 1500);
        });
    });

    // ══════════════════════════════════════════════════════════════════
    // 8. ĐÓNG / MỞ CÁC MODAL KHÁC
    // ══════════════════════════════════════════════════════════════════
    document.querySelectorAll('.dong-modal-nhan').forEach(btn => {
        btn.addEventListener('click', () => modalXacNhanNhan.classList.add('hidden'));
    });

    document.querySelectorAll('.dong-modal-detail').forEach(btn => {
        btn.addEventListener('click', () => document.getElementById('modalChiTietPending').classList.add('hidden'));
    });

    document.getElementById('nutChuyenSangNhanHang').addEventListener('click', function () {
        document.getElementById('modalChiTietPending').classList.add('hidden');
        if (itemDangChon) moModalNhanHang(itemDangChon);
    });

    document.getElementById('nutCopyMaGDNhan').addEventListener('click', function () {
        if (!itemDangChon) return;
        navigator.clipboard.writeText(itemDangChon.maGiaoDich).then(() => {
            this.innerHTML = `<i class="fa-solid fa-check text-emerald-600"></i>`;
            setTimeout(() => { this.innerHTML = `<i class="fa-regular fa-copy"></i>`; }, 1500);
        });
    });

    document.getElementById('nutCopyDetailTx').addEventListener('click', function () {
        if (!itemDangChon) return;
        navigator.clipboard.writeText(itemDangChon.txHash).then(() => {
            this.innerHTML = `<i class="fa-solid fa-check text-emerald-600"></i> Đã sao chép`;
            setTimeout(() => { this.innerHTML = `<i class="fa-regular fa-copy"></i> Sao chép`; }, 1500);
        });
    });

    // ══════════════════════════════════════════════════════════════════
    // 9. XÁC NHẬN NHẬP KHO & HIỆN TRẠNG THÁI GHI BLOCKCHAIN
    // ══════════════════════════════════════════════════════════════════
    const modalLoading = document.getElementById('modalLoadingBlockchain');
    const khungLoadingIcon = document.getElementById('khungLoadingIcon');
    const khungSuccessIcon = document.getElementById('khungSuccessIcon');
    const successMessageDetail = document.getElementById('successMessageDetail');

    nutXacNhanNhapKho.addEventListener('click', function () {
        if (!itemDangChon) return;

        modalXacNhanNhan.classList.add('hidden');
        khungLoadingIcon.classList.remove('hidden');
        khungSuccessIcon.classList.add('hidden');
        modalLoading.classList.remove('hidden');

        setTimeout(() => {
            const now = new Date();
            const thoiGianXacNhanStr = `${String(now.getDate()).padStart(2,'0')}/${String(now.getMonth()+1).padStart(2,'0')}/${now.getFullYear()} ${String(now.getHours()).padStart(2,'0')}:${String(now.getMinutes()).padStart(2,'0')}`;

            danhSachDaTiepNhan.unshift({
                maGiaoDich: itemDangChon.maGiaoDich,
                maLo: itemDangChon.maLo,
                tenSanPham: itemDangChon.tenSanPham,
                soDangKy: itemDangChon.soDangKy,
                nhaSanXuat: itemDangChon.nhaSanXuat,
                benGui: itemDangChon.benGui,
                soLuong: itemDangChon.soLuong,
                hanDung: itemDangChon.hanDung,
                thoiGianGui: itemDangChon.ngayGui,
                thoiGianXacNhan: thoiGianXacNhanStr,
                trangThaiLoHienTai: itemDangChon.trangThaiLo,
                tongDaNhanLo: itemDangChon.soLuong,
                daBan: 0,
                conLaiTaiKho: itemDangChon.soLuong,
                txHash: itemDangChon.txHash,
                lyDoThuHoi: '',
                ngayThuHoi: ''
            });

            danhSachChoXacNhan = danhSachChoXacNhan.filter(x => x.maGiaoDich !== itemDangChon.maGiaoDich);

            khungLoadingIcon.classList.add('hidden');
            khungSuccessIcon.classList.remove('hidden');
            successMessageDetail.textContent = `Đã ghi nhận ${itemDangChon.soLuong.toLocaleString()} hộp ${itemDangChon.tenSanPham} (Lô ${itemDangChon.maLo}) vào kho nhà thuốc.`;

            renderBangChoXacNhan();
            renderBangDaTiepNhan();
        }, 700);
    });

    document.getElementById('nutDongModalSuccess').addEventListener('click', () => {
        modalLoading.classList.add('hidden');
    });

    // ══════════════════════════════════════════════════════════════════
    // 10. QUÉT QR THÙNG NHẬP NHANH
    // ══════════════════════════════════════════════════════════════════
    document.getElementById('nutQuetQRNhanHang').addEventListener('click', function () {
        const maNhap = prompt("Mô phỏng máy quét mã QR tem thùng lô thuốc:\nVui lòng nhập mã lô (VD: LOT-PARA-2026):", "LOT-PARA-2026");
        if (maNhap) {
            const item = danhSachChoXacNhan.find(x => x.maLo.toUpperCase() === maNhap.trim().toUpperCase());
            if (item) {
                moModalNhanHang(item);
            } else {
                alert(`Không tìm thấy đơn hàng chờ xác nhận nào có mã lô "${maNhap}".`);
            }
        }
    });

    // Lắng nghe tìm kiếm & lọc
    oTimKiem.addEventListener('input', () => { trangHienTaiCho = 1; renderBangChoXacNhan(); });
    locNhaPhanPhoi.addEventListener('change', () => { trangHienTaiCho = 1; renderBangChoXacNhan(); });
    locHanDung.addEventListener('change', () => { trangHienTaiCho = 1; renderBangChoXacNhan(); });

    // Khởi chạy khi nạp trang
    renderBangChoXacNhan();
    renderBangDaTiepNhan();
});
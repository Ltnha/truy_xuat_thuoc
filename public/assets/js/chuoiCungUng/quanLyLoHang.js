document.addEventListener('DOMContentLoaded', function () {
    const EXPLORER_BASE_URL = "https://amoy.polygonscan.com/tx/";
    const ITEMS_PER_PAGE = 3;
    let trangHienTaiPending = 1;
    let trangHienTaiKho = 1;

    // ══ 1. DỮ LIỆU MÔ PHỎNG TAB 1: LÔ HÀNG CHỜ TIẾP NHẬN (chuyenGiao: PENDING) ══
    let danhSachPending = [
        {
            maGiaoDich: 'TRF-NSX-2026-081',
            maLo: 'LOT-PARA-2026',
            tenSanPham: 'Paracetamol 500mg',
            benGui: 'Nhà Máy Dược Medipharco',
            loaiBenGui: 'NSX',
            soLuong: 2000,
            ngayGui: '2026-02-08 14:15',
            hanDung: '2029-01-15',
            txHash: '0x4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0b1c2d3e4f5a6b7c8d9e0f1a2b3c4d5e'
        },
        {
            maGiaoDich: 'TRF-NSX-2026-094',
            maLo: 'LOT-DES-2026-01',
            tenSanPham: 'Deslotid OPV 5mg',
            benGui: 'Công ty Cổ phần Dược phẩm OPV',
            loaiBenGui: 'NSX',
            soLuong: 3000,
            ngayGui: '2026-02-09 09:30',
            hanDung: '2028-02-05',
            txHash: '0x88f2a1b9c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0'
        },
        {
            maGiaoDich: 'TRF-NPP-2026-012',
            maLo: 'LOT-AMOX-2025-09',
            tenSanPham: 'Amoxicillin 500mg',
            benGui: 'Dược Phẩm CPC1 Hà Nội',
            loaiBenGui: 'NPP',
            soLuong: 1500,
            ngayGui: '2026-02-10 11:20',
            hanDung: '2028-09-10',
            txHash: '0x71a49f2b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4e5'
        },
        {
            maGiaoDich: 'TRF-NPP-2026-015',
            maLo: 'LOT-CEFA-2024-EX',
            tenSanPham: 'Cefalexin 500mg',
            benGui: 'Chi Nhánh Vinapharm Miền Tây',
            loaiBenGui: 'NPP',
            soLuong: 1000,
            ngayGui: '2026-02-10 16:00',
            hanDung: '2025-01-01', // Lô hết hạn
            txHash: '0x12a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4e5f6a7b8c9d0e1f2a3'
        }
    ];

    // ══ 2. DỮ LIỆU MÔ PHỎNG TAB 2: KHO HÀNG ĐANG LƯU GIỮ (tonKhoLo) ══
    let danhSachKho = [
        {
            maLo: 'LOT-PARA-2026',
            tenSanPham: 'Paracetamol 500mg',
            hanDung: '2029-01-15',
            soLuongTongNhap: 4000,
            trangThaiLô: 'IN_TRANSIT',
            txHash: '0x3a4b5c6d7e8f9a0b1c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b',
            chuyenGiaoDi: [
                { benNhan: 'Nhà Thuốc PharmaCare Cần Thơ', soLuong: 1500, thoiGian: '2026-02-05 10:00', trangThai: 'RECEIVED' },
                { benNhan: 'Nhà Thuốc An Khang Ninh Kiều', soLuong: 500, thoiGian: '2026-02-08 16:30', trangThai: 'PENDING' }
            ]
        },
        {
            maLo: 'LOT-AMOX-2025-09',
            tenSanPham: 'Amoxicillin 500mg',
            hanDung: '2028-09-10',
            soLuongTongNhap: 5000,
            trangThaiLô: 'RECALLED',
            txHash: '0x71a49f2b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4e5',
            chuyenGiaoDi: []
        },
        {
            maLo: 'LOT-CEFA-2024-EX',
            tenSanPham: 'Cefalexin 500mg',
            hanDung: '2025-01-01',
            soLuongTongNhap: 2000,
            trangThaiLô: 'IN_TRANSIT',
            txHash: '0x12a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4e5f6a7b8c9d0e1f2a3',
            chuyenGiaoDi: []
        }
    ];

    const doiTacNhaThuoc = [
        "Nhà Thuốc PharmaCare Cần Thơ (Ví: 0x5a...91B)",
        "Nhà Thuốc Long Châu - Chi nhánh 38 (Ví: 0x1c...42C)",
        "Nhà Thuốc An Khang Ninh Kiều (Ví: 0x9f...10D)",
        "Kho Dược Bệnh Viện Đa Khoa TP (Ví: 0x2e...88E)"
    ];

    const doiTacNhaPhanPhoi = [
        "Công ty Cổ phần Dược phẩm CPC1 Hà Nội (Ví: 0x3a...11C)",
        "Công ty Dược phẩm Vimedimex TP.HCM (Ví: 0x1e...44E)",
        "Chi Nhánh Vinapharm Miền Tây (Ví: 0x7c...88D)"
    ];

    function layNgayHienTai() {
        return new Date().toISOString().split('T')[0];
    }

    function tinhKhaDungKho(item) {
        const daXuatDi = item.chuyenGiaoDi.reduce((sum, cg) => sum + cg.soLuong, 0);
        return Math.max(0, item.soLuongTongNhap - daXuatDi);
    }

    function layTrangThaiKho(item) {
        const homNay = layNgayHienTai();
        if (item.trangThaiLô === 'RECALLED') {
            return { nhan: 'Đã thu hồi', badgeClass: 'bg-red-100 text-red-700', choPhepChuyen: false };
        }
        if (item.hanDung < homNay) {
            return { nhan: 'Đã hết hạn', badgeClass: 'bg-amber-100 text-amber-800 border border-amber-300', choPhepChuyen: false };
        }
        const khaDung = tinhKhaDungKho(item);
        if (item.chuyenGiaoDi.length > 0) {
            return { nhan: 'Đang chuyển tiếp', badgeClass: 'bg-cyan-100 text-cyan-800', choPhepChuyen: khaDung > 0 };
        }
        return { nhan: 'Đang lưu giữ', badgeClass: 'bg-emerald-100 text-emerald-800', choPhepChuyen: khaDung > 0 };
    }

    // ══ 3. CHUYỂN ĐỔI TAB ══
    const tabChoTiepNhan = document.getElementById('tabChoTiepNhan');
    const tabKhoLuuGiu = document.getElementById('tabKhoLuuGiu');
    const khungTabChoTiepNhan = document.getElementById('khungTabChoTiepNhan');
    const khungTabKhoLuuGiu = document.getElementById('khungTabKhoLuuGiu');

    tabChoTiepNhan.addEventListener('click', () => {
        tabChoTiepNhan.className = "flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all bg-blue-700 text-white shadow-sm";
        tabKhoLuuGiu.className = "flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all text-slate-600 hover:text-blue-700 hover:bg-blue-50";
        khungTabChoTiepNhan.classList.remove('hidden');
        khungTabKhoLuuGiu.classList.add('hidden');
        renderBangPending();
    });

    tabKhoLuuGiu.addEventListener('click', () => {
        tabKhoLuuGiu.className = "flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all bg-blue-700 text-white shadow-sm";
        tabChoTiepNhan.className = "flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all text-slate-600 hover:text-blue-700 hover:bg-blue-50";
        khungTabKhoLuuGiu.classList.remove('hidden');
        khungTabChoTiepNhan.classList.add('hidden');
        renderBangKho();
    });

    // ══ 4. RENDER TAB 1: DANH SÁCH CHỜ TIẾP NHẬN (CÓ BỘ LỌC + XEM CHI TIẾT + PHÂN TRANG) ══
    const thanBangPending = document.getElementById('thanBangPending');
    const oTimKiemPending = document.getElementById('oTimKiemPending');
    const locBenGuiPending = document.getElementById('locBenGuiPending');
    const locHanDungPending = document.getElementById('locHanDungPending');
    const badgePending = document.getElementById('badgeSoLuongPending');
    const soLuongPendingHienThi = document.getElementById('soLuongPendingHienThi');
    const thongTinPhanTrangPending = document.getElementById('thongTinPhanTrangPending');
    const cumNutPhanTrangPending = document.getElementById('cumNutPhanTrangPending');

    function renderBangPending() {
        const tuKhoa = oTimKiemPending.value.trim().toLowerCase();
        const benGuiLoc = locBenGuiPending.value;
        const hanDungLoc = locHanDungPending.value;
        const homNay = layNgayHienTai();

        const danhSachLoc = danhSachPending.filter(item => {
            const matchTuKhoa = item.maGiaoDich.toLowerCase().includes(tuKhoa) ||
                               item.maLo.toLowerCase().includes(tuKhoa) ||
                               item.tenSanPham.toLowerCase().includes(tuKhoa) ||
                               item.benGui.toLowerCase().includes(tuKhoa);

            let matchBenGui = true;
            if (benGuiLoc === 'NSX') matchBenGui = (item.loaiBenGui === 'NSX');
            else if (benGuiLoc === 'NPP') matchBenGui = (item.loaiBenGui === 'NPP');

            let matchHanDung = true;
            if (hanDungLoc === 'HET_HAN') matchHanDung = (item.hanDung < homNay);
            else if (hanDungLoc === 'CON_HAN') matchHanDung = (item.hanDung >= homNay);

            return matchTuKhoa && matchBenGui && matchHanDung;
        });

        badgePending.textContent = danhSachPending.length;
        soLuongPendingHienThi.textContent = `Hiển thị: ${danhSachLoc.length} lô`;

        const tongSoTrang = Math.ceil(danhSachLoc.length / ITEMS_PER_PAGE) || 1;
        if (trangHienTaiPending > tongSoTrang) trangHienTaiPending = tongSoTrang;

        const batDau = (trangHienTaiPending - 1) * ITEMS_PER_PAGE;
        const ketThuc = batDau + ITEMS_PER_PAGE;
        const duLieuTrang = danhSachLoc.slice(batDau, ketThuc);

        thanBangPending.innerHTML = '';

        if (duLieuTrang.length === 0) {
            thanBangPending.innerHTML = `<tr><td colspan="8" class="text-center py-8 text-slate-400 italic">Không có lô hàng nào phù hợp với bộ lọc tìm kiếm.</td></tr>`;
            thongTinPhanTrangPending.textContent = "Không có bản ghi nào";
            cumNutPhanTrangPending.innerHTML = '';
            return;
        }

        thongTinPhanTrangPending.textContent = `Đang hiển thị ${batDau + 1} đến ${Math.min(ketThuc, danhSachLoc.length)} trên tổng số ${danhSachLoc.length} lô`;

        duLieuTrang.forEach(item => {
            const tr = document.createElement('tr');
            tr.className = 'dong-hang-pending transition-colors';
            tr.innerHTML = `
                <td class="py-3.5 px-4 font-mono font-bold text-cyan-900 whitespace-nowrap">${item.maGiaoDich}</td>
                <td class="py-3.5 px-4 font-mono font-bold text-slate-900">${item.maLo}</td>
                <td class="py-3.5 px-4 font-semibold text-slate-900">${item.tenSanPham}</td>
                <td class="py-3.5 px-4 text-slate-700">
                    <span class="inline-block">${item.benGui}</span>
                    <span class="ml-1 text-[10px] font-bold px-1.5 py-0.2 rounded ${item.loaiBenGui === 'NSX' ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800'}">
                        ${item.loaiBenGui}
                    </span>
                </td>
                <td class="py-3.5 px-4 text-right font-mono font-bold text-blue-700">${item.soLuong.toLocaleString()}</td>
                <td class="py-3.5 px-4 text-slate-500 font-mono text-[11px]">${item.ngayGui}</td>
                <td class="py-3.5 px-4 text-center">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300">
                        <i class="fa-solid fa-clock mr-1"></i> PENDING
                    </span>
                </td>
                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                    <div class="inline-flex items-center gap-1.5">
                        <!-- NÚT XEM CHI TIẾT LÔ CHỜ TIẾP NHẬN MỚI THÊM -->
                        <button type="button" class="btn-xem-chi-tiet-pending px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-all flex items-center gap-1" data-magd="${item.maGiaoDich}">
                            <i class="fa-solid fa-eye"></i> Chi Tiết
                        </button>
                        <!-- Nút Xác nhận nhận hàng -->
                        <button type="button" class="btn-mo-nhan-hang px-2.5 py-1.5 rounded-lg bg-blue-700 hover:bg-blue-800 text-white font-bold text-xs transition-all shadow-2xs flex items-center gap-1" data-magd="${item.maGiaoDich}">
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

    // ══ 5. RENDER TAB 2: DANH SÁCH KHO HÀNG LƯU GIỮ ══
    const thanBangKho = document.getElementById('thanBangKho');
    const oTimKiemKho = document.getElementById('oTimKiemKho');
    const locTrangThaiKho = document.getElementById('locTrangThaiKho');
    const locHanDungKho = document.getElementById('locHanDungKho');
    const badgeKho = document.getElementById('badgeSoLuongKho');
    const soLuongKhoHienThi = document.getElementById('soLuongKhoHienThi');
    const thongTinPhanTrangKho = document.getElementById('thongTinPhanTrangKho');
    const cumNutPhanTrangKho = document.getElementById('cumNutPhanTrangKho');

    function renderBangKho() {
        const tuKhoa = oTimKiemKho.value.trim().toLowerCase();
        const trangThaiLoc = locTrangThaiKho.value;
        const hanDungLoc = locHanDungKho.value;
        const homNay = layNgayHienTai();

        const danhSachLoc = danhSachKho.filter(item => {
            const matchTuKhoa = item.maLo.toLowerCase().includes(tuKhoa) || item.tenSanPham.toLowerCase().includes(tuKhoa);
            const tt = layTrangThaiKho(item);

            let matchTrangThai = true;
            if (trangThaiLoc === 'DANG_GIU') matchTrangThai = (tt.nhan === 'Đang lưu giữ');
            else if (trangThaiLoc === 'IN_TRANSIT') matchTrangThai = (tt.nhan === 'Đang chuyển tiếp');
            else if (trangThaiLoc === 'HET_HAN') matchTrangThai = (tt.nhan === 'Đã hết hạn');
            else if (trangThaiLoc === 'RECALLED') matchTrangThai = (tt.nhan === 'Đã thu hồi');

            let matchHanDung = true;
            if (hanDungLoc === 'DA_HET_HAN') matchHanDung = item.hanDung < homNay;
            else if (hanDungLoc === 'CON_HAN') matchHanDung = item.hanDung >= homNay;

            return matchTuKhoa && matchTrangThai && matchHanDung;
        });

        badgeKho.textContent = danhSachKho.length;
        soLuongKhoHienThi.textContent = `Hiển thị: ${danhSachLoc.length} lô`;

        const tongSoTrang = Math.ceil(danhSachLoc.length / ITEMS_PER_PAGE) || 1;
        if (trangHienTaiKho > tongSoTrang) trangHienTaiKho = tongSoTrang;

        const batDau = (trangHienTaiKho - 1) * ITEMS_PER_PAGE;
        const ketThuc = batDau + ITEMS_PER_PAGE;
        const duLieuTrang = danhSachLoc.slice(batDau, ketThuc);

        thanBangKho.innerHTML = '';

        if (duLieuTrang.length === 0) {
            thanBangKho.innerHTML = `<tr><td colspan="8" class="text-center py-8 text-slate-400 italic">Không tìm thấy lô hàng nào trong kho phù hợp với bộ lọc.</td></tr>`;
            thongTinPhanTrangKho.textContent = "Không có bản ghi nào";
            cumNutPhanTrangKho.innerHTML = '';
            return;
        }

        thongTinPhanTrangKho.textContent = `Đang hiển thị ${batDau + 1} đến ${Math.min(ketThuc, danhSachLoc.length)} trên tổng số ${danhSachLoc.length} lô`;

        duLieuTrang.forEach(item => {
            const khaDung = tinhKhaDungKho(item);
            const tt = layTrangThaiKho(item);
            const coTheChuyen = tt.choPhepChuyen && khaDung > 0;

            const tr = document.createElement('tr');
            tr.className = 'dong-hang-kho transition-colors';
            tr.innerHTML = `
                <td class="py-3.5 px-4 font-mono font-bold text-slate-900">${item.maLo}</td>
                <td class="py-3.5 px-4 text-center">
                    <button type="button" class="btn-tai-qr-thung p-1.5 bg-nenChinh hover:bg-emerald-50 text-emerald-800 rounded-lg border border-emerald-900/10 text-xs font-bold transition-all shadow-2xs" data-malo="${item.maLo}" title="Tải tem QR thùng">
                        <i class="fa-solid fa-qrcode mr-1"></i> Tải QR
                    </button>
                </td>
                <td class="py-3.5 px-4 font-semibold text-slate-900">${item.tenSanPham}</td>
                <td class="py-3.5 px-4 font-mono text-slate-600 text-[11px]">${item.hanDung}</td>
                <td class="py-3.5 px-4 text-right font-mono font-semibold">${item.soLuongTongNhap.toLocaleString()}</td>
                <td class="py-3.5 px-4 text-right font-mono font-bold ${khaDung > 0 ? 'text-emerald-700' : 'text-slate-400'}">${khaDung.toLocaleString()}</td>
                <td class="py-3.5 px-4 text-center">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold ${tt.badgeClass}">
                        ${tt.nhan}
                    </span>
                </td>
                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                    <div class="inline-flex items-center gap-1.5">
                        <button type="button" class="btn-mo-chuyen-tiep px-2.5 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1 ${coTheChuyen ? 'bg-blue-700 hover:bg-blue-800 text-white shadow-2xs' : 'bg-slate-100 text-slate-400 cursor-not-allowed'}" data-malo="${item.maLo}" ${!coTheChuyen ? 'disabled' : ''}>
                            <i class="fa-solid fa-truck-arrow-right"></i> Chuyển Tiếp
                        </button>
                        <button type="button" class="btn-xem-chi-tiet-kho px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-all flex items-center gap-1" data-malo="${item.maLo}">
                            <i class="fa-solid fa-eye"></i> Chi Tiết
                        </button>
                    </div>
                </td>
            `;
            thanBangKho.appendChild(tr);
        });

        renderPhanTrang(cumNutPhanTrangKho, trangHienTaiKho, tongSoTrang, (trang) => {
            trangHienTaiKho = trang;
            renderBangKho();
        });

        ganSuKienNutKho();
    }

    // Hàm render các nút bấm phân trang
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
            btnPage.className = `w-7 h-7 rounded-lg text-xs font-bold transition-all ${trangHienTai === i ? 'bg-blue-700 text-white shadow-2xs' : 'border border-slate-200 text-slate-700 hover:bg-slate-100'}`;
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

    // ══ 6. XỬ LÝ MODAL: CHI TIẾT LÔ CHỜ TIẾP NHẬN & XÁC NHẬN NHẬP KHO ══
    const modalXacNhanNhan = document.getElementById('modalXacNhanNhan');
    const modalChiTietPending = document.getElementById('modalChiTietPending');
    const nutXacNhanNhapKho = document.getElementById('nutXacNhanNhapKho');
    let itemPendingDangChon = null;

    function ganSuKienNutPending() {
        // Nút Mở modal Xác nhận nhận hàng
        document.querySelectorAll('.btn-mo-nhan-hang').forEach(btn => {
            btn.addEventListener('click', function () {
                const magd = this.getAttribute('data-magd');
                itemPendingDangChon = danhSachPending.find(x => x.maGiaoDich === magd);
                if (!itemPendingDangChon) return;

                document.getElementById('nhanMaGiaoDich').textContent = itemPendingDangChon.maGiaoDich;
                document.getElementById('nhanMaLo').textContent = itemPendingDangChon.maLo;
                document.getElementById('nhanTenSanPham').textContent = itemPendingDangChon.tenSanPham;
                document.getElementById('nhanBenGui').textContent = itemPendingDangChon.benGui;
                document.getElementById('nhanSoLuong').textContent = `${itemPendingDangChon.soLuong.toLocaleString()} hộp`;
                document.getElementById('nhanHanDung').textContent = itemPendingDangChon.hanDung;

                modalXacNhanNhan.classList.remove('hidden');
            });
        });

        // Nút Mở modal Chi tiết lô chờ tiếp nhận (MỚI THÊM)
        document.querySelectorAll('.btn-xem-chi-tiet-pending').forEach(btn => {
            btn.addEventListener('click', function () {
                const magd = this.getAttribute('data-magd');
                const item = danhSachPending.find(x => x.maGiaoDich === magd);
                if (!item) return;

                itemPendingDangChon = item; // Giữ để nếu người dùng bấm nhận hàng từ modal chi tiết
                document.getElementById('pendingModalMaLo').textContent = item.maLo;
                document.getElementById('pendingModalSanPham').textContent = item.tenSanPham;
                document.getElementById('pendingModalBenGui').textContent = item.benGui;
                document.getElementById('pendingModalSoLuong').textContent = `${item.soLuong.toLocaleString()} hộp`;
                document.getElementById('pendingModalHanDung').textContent = item.hanDung;
                document.getElementById('pendingModalNgayGui').textContent = item.ngayGui;
                document.getElementById('pendingModalTxHash').textContent = item.txHash;
                document.getElementById('pendingModalTxLink').href = `${EXPLORER_BASE_URL}${item.txHash}`;

                modalChiTietPending.classList.remove('hidden');
            });
        });
    }

    // Đóng modal chi tiết pending
    document.querySelectorAll('.dong-modal-pending-detail').forEach(btn => {
        btn.addEventListener('click', () => modalChiTietPending.classList.add('hidden'));
    });

    // Nút chuyển sang xác nhận nhận từ modal chi tiết
    document.getElementById('nutNhanHangTuDetail').addEventListener('click', function () {
        modalChiTietPending.classList.add('hidden');
        if (itemPendingDangChon) {
            document.getElementById('nhanMaGiaoDich').textContent = itemPendingDangChon.maGiaoDich;
            document.getElementById('nhanMaLo').textContent = itemPendingDangChon.maLo;
            document.getElementById('nhanTenSanPham').textContent = itemPendingDangChon.tenSanPham;
            document.getElementById('nhanBenGui').textContent = itemPendingDangChon.benGui;
            document.getElementById('nhanSoLuong').textContent = `${itemPendingDangChon.soLuong.toLocaleString()} hộp`;
            document.getElementById('nhanHanDung').textContent = itemPendingDangChon.hanDung;
            modalXacNhanNhan.classList.remove('hidden');
        }
    });

    document.querySelectorAll('.dong-modal-nhan').forEach(btn => {
        btn.addEventListener('click', () => modalXacNhanNhan.classList.add('hidden'));
    });

    // Sao chép txHash trên modal pending
    document.getElementById('nutCopyPendingTx').addEventListener('click', function () {
        if (!itemPendingDangChon) return;
        navigator.clipboard.writeText(itemPendingDangChon.txHash).then(() => {
            this.innerHTML = `<i class="fa-solid fa-check text-emerald-600"></i> Đã sao chép`;
            setTimeout(() => { this.innerHTML = `<i class="fa-regular fa-copy"></i> Sao chép`; }, 1800);
        });
    });

    // Xác nhận nhập kho
    nutXacNhanNhapKho.addEventListener('click', function () {
        if (!itemPendingDangChon) return;

        const daCoTrongKho = danhSachKho.find(x => x.maLo === itemPendingDangChon.maLo);
        if (daCoTrongKho) {
            daCoTrongKho.soLuongTongNhap += itemPendingDangChon.soLuong;
        } else {
            danhSachKho.unshift({
                maLo: itemPendingDangChon.maLo,
                tenSanPham: itemPendingDangChon.tenSanPham,
                hanDung: itemPendingDangChon.hanDung,
                soLuongTongNhap: itemPendingDangChon.soLuong,
                trangThaiLô: 'CREATED',
                txHash: itemPendingDangChon.txHash,
                chuyenGiaoDi: []
            });
        }

        danhSachPending = danhSachPending.filter(x => x.maGiaoDich !== itemPendingDangChon.maGiaoDich);

        alert(`Xác nhận nhận hàng thành công! Đã ghi nhận chuyển trạng thái RECEIVED trên Smart Contract và nhập ${itemPendingDangChon.soLuong.toLocaleString()} hộp ${itemPendingDangChon.tenSanPham} vào kho.`);
        modalXacNhanNhan.classList.add('hidden');
        renderBangPending();
        renderBangKho();
    });

    // ══ 7. XỬ LÝ MODAL 3: KHỞI TẠO CHUYỂN TIẾP (FR07) ══
    const modalChuyenTiep = document.getElementById('modalChuyenTiep');
    const formChuyenTiep = document.getElementById('formSubmitChuyenTiep');
    const chonLoaiDoiTac = document.getElementById('chonLoaiDoiTac');
    const chonDoiTacNhan = document.getElementById('chonDoiTacNhan');
    const nhapSoLuongChuyen = document.getElementById('nhapSoLuongChuyen');
    const loiSoLuongChuyen = document.getElementById('loiSoLuongChuyen');
    let itemKhoDangChon = null;

    function capNhatDropdownDoiTac() {
        chonDoiTacNhan.innerHTML = '';
        const loai = chonLoaiDoiTac.value;
        const ds = (loai === 'NHA_THUOC') ? doiTacNhaThuoc : doiTacNhaPhanPhoi;
        ds.forEach(dt => {
            const opt = document.createElement('option');
            opt.value = dt;
            opt.textContent = dt;
            chonDoiTacNhan.appendChild(opt);
        });
    }

    chonLoaiDoiTac.addEventListener('change', capNhatDropdownDoiTac);

    function ganSuKienNutKho() {
        document.querySelectorAll('.btn-mo-chuyen-tiep').forEach(btn => {
            btn.addEventListener('click', function () {
                const malo = this.getAttribute('data-malo');
                itemKhoDangChon = danhSachKho.find(x => x.maLo === malo);
                if (!itemKhoDangChon) return;

                const khaDung = tinhKhaDungKho(itemKhoDangChon);
                document.getElementById('chuyenMaLoText').textContent = itemKhoDangChon.maLo;
                document.getElementById('chuyenSoLuongKhaDungText').textContent = `${khaDung.toLocaleString()} hộp`;

                nhapSoLuongChuyen.max = khaDung;
                nhapSoLuongChuyen.value = '';
                loiSoLuongChuyen.classList.add('hidden');

                capNhatDropdownDoiTac();
                modalChuyenTiep.classList.remove('hidden');
            });
        });

        document.querySelectorAll('.btn-xem-chi-tiet-kho').forEach(btn => {
            btn.addEventListener('click', function () {
                const malo = this.getAttribute('data-malo');
                moChiTietKho(malo);
            });
        });

        document.querySelectorAll('.btn-tai-qr-thung').forEach(btn => {
            btn.addEventListener('click', function () {
                const malo = this.getAttribute('data-malo');
                const blob = new Blob([`QR THÙNG LÔ THUỐC: ${malo}\nTrạng thái: Lưu trữ kho phân phối`], { type: 'text/plain' });
                const a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                a.download = `QR-THUNG-${malo}.txt`;
                a.click();
            });
        });
    }

    document.querySelectorAll('.dong-modal-chuyen').forEach(btn => {
        btn.addEventListener('click', () => modalChuyenTiep.classList.add('hidden'));
    });

    formChuyenTiep.addEventListener('submit', function (e) {
        e.preventDefault();
        const slXuat = parseInt(nhapSoLuongChuyen.value);
        const khaDung = tinhKhaDungKho(itemKhoDangChon);

        if (slXuat > khaDung) {
            loiSoLuongChuyen.classList.remove('hidden');
            return;
        }

        const tenDoiTac = chonDoiTacNhan.value;
        const thoiGian = new Date().toISOString().replace('T', ' ').substring(0, 16);

        itemKhoDangChon.chuyenGiaoDi.push({
            benNhan: tenDoiTac,
            soLuong: slXuat,
            thoiGian: thoiGian,
            trangThai: 'PENDING'
        });

        alert(`Đã khởi tạo chuyển tiếp ${slXuat.toLocaleString()} hộp lô ${itemKhoDangChon.maLo} đến ${tenDoiTac}! Đơn hàng đang ở trạng thái PENDING.`);
        modalChuyenTiep.classList.add('hidden');
        renderBangKho();
    });

    // ══ 8. XỬ LÝ MODAL 4: XEM CHI TIẾT & TIMELINE KHO ══
    const modalChiTietKho = document.getElementById('modalChiTietKho');
    let loDangXemChiTiet = null;

    function moChiTietKho(malo) {
        loDangXemChiTiet = danhSachKho.find(x => x.maLo === malo);
        if (!loDangXemChiTiet) return;

        const khaDung = tinhKhaDungKho(loDangXemChiTiet);
        document.getElementById('modalKhoMaLoTitle').textContent = loDangXemChiTiet.maLo;
        document.getElementById('modalKhoSanPham').textContent = loDangXemChiTiet.tenSanPham;
        document.getElementById('modalKhoHanDung').textContent = loDangXemChiTiet.hanDung;
        document.getElementById('modalKhoTongNhap').textContent = `${loDangXemChiTiet.soLuongTongNhap.toLocaleString()} hộp`;
        document.getElementById('modalKhoKhaDung').textContent = `${khaDung.toLocaleString()} hộp`;

        document.getElementById('modalKhoTxHash').textContent = loDangXemChiTiet.txHash;
        document.getElementById('modalKhoTxLink').href = `${EXPLORER_BASE_URL}${loDangXemChiTiet.txHash}`;

        const timelineBox = document.getElementById('danhSachTimelineKho');
        timelineBox.innerHTML = '';

        if (loDangXemChiTiet.chuyenGiaoDi.length === 0) {
            timelineBox.innerHTML = `<p class="text-slate-400 italic">Chưa phát sinh giao dịch chuyển tiếp nào từ lô này.</p>`;
        } else {
            loDangXemChiTiet.chuyenGiaoDi.forEach(cg => {
                const div = document.createElement('div');
                div.className = 'relative space-y-1';
                div.innerHTML = `
                    <span class="absolute -left-[31px] top-1 w-3.5 h-3.5 rounded-full ${cg.trangThai === 'RECEIVED' ? 'bg-emerald-600' : 'bg-amber-500'} ring-4 ring-white"></span>
                    <div class="font-bold text-slate-800">Chuyển tiếp đến: ${cg.benNhan}</div>
                    <div class="text-slate-600 font-mono">Số lượng: <strong>${cg.soLuong.toLocaleString()}</strong> hộp – Trạng thái: <span class="font-bold ${cg.trangThai === 'RECEIVED' ? 'text-emerald-700' : 'text-amber-600'}">${cg.trangThai}</span></div>
                    <div class="text-[10px] text-slate-400 font-mono"><i class="fa-regular fa-clock mr-1"></i> ${cg.thoiGian}</div>
                `;
                timelineBox.appendChild(div);
            });
        }

        modalChiTietKho.classList.remove('hidden');
    }

    document.querySelectorAll('.dong-modal-chitiet').forEach(btn => {
        btn.addEventListener('click', () => modalChiTietKho.classList.add('hidden'));
    });

    document.getElementById('nutCopyModalKhoTx').addEventListener('click', function () {
        if (!loDangXemChiTiet) return;
        navigator.clipboard.writeText(loDangXemChiTiet.txHash).then(() => {
            this.innerHTML = `<i class="fa-solid fa-check text-emerald-600"></i> Đã sao chép`;
            setTimeout(() => { this.innerHTML = `<i class="fa-regular fa-copy"></i> Sao chép`; }, 1800);
        });
    });

    // ══ 9. LẮNG NGHE SỰ KIỆN TÌM KIẾM & BỘ LỌC ══
    // Tab 1: Chờ tiếp nhận
    oTimKiemPending.addEventListener('input', () => { trangHienTaiPending = 1; renderBangPending(); });
    locBenGuiPending.addEventListener('change', () => { trangHienTaiPending = 1; renderBangPending(); });
    locHanDungPending.addEventListener('change', () => { trangHienTaiPending = 1; renderBangPending(); });

    // Tab 2: Kho lưu giữ
    oTimKiemKho.addEventListener('input', () => { trangHienTaiKho = 1; renderBangKho(); });
    locTrangThaiKho.addEventListener('change', () => { trangHienTaiKho = 1; renderBangKho(); });
    locHanDungKho.addEventListener('change', () => { trangHienTaiKho = 1; renderBangKho(); });

    // Khởi chạy khi nạp trang
    renderBangPending();
    renderBangKho();
});
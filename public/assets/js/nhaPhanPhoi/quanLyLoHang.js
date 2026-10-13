document.addEventListener('DOMContentLoaded', function () {
    const EXPLORER_BASE_URL = "https://amoy.polygonscan.com/tx/";
    const ITEMS_PER_PAGE = 3;
    let trangHienTaiPending = 1;
    let trangHienTaiKho = 1;

    // Helper định dạng ngày sang dd/mm/yyyy
    function dinhDangNgay(ngayYMD) {
        if (!ngayYMD) return '--';
        const parts = ngayYMD.split('-');
        if (parts.length === 3) {
            return `${parts[2]}/${parts[1]}/${parts[0]}`;
        }
        return ngayYMD;
    }

    function layNgayHienTai() {
        return new Date().toISOString().split('T')[0];
    }

    // ══ 1. DỮ LIỆU MÔ PHỎNG TAB 1: LÔ HÀNG CHỜ TIẾP NHẬN (chuyenGiao: PENDING) ══
    // Quy chuẩn: Bên gửi 100% là Nhà sản xuất; Mã GD dạng CG-xxxxxx
    let danhSachPending = [
        {
            maGiaoDich: 'CG-000081',
            maLo: 'LOT-PARA-2026',
            tenSanPham: 'Paracetamol 500mg',
            benGui: 'Công ty CP Dược Phẩm Medipharco',
            maNhaSanXuat: 'MEDIPHARCO',
            soLuong: 2000,
            ngayGui: '08/02/2026 14:15',
            hanDung: '2029-01-15',
            trangThaiLo: 'CREATED', // Bình thường
            doiChieuKhop: true,
            txHash: '0x4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0b1c2d3e4f5a6b7c8d9e0f1a2b3c4d5e'
        },
        {
            maGiaoDich: 'CG-000094',
            maLo: 'LOT-DES-2026-01',
            tenSanPham: 'Deslotid OPV 5mg',
            benGui: 'Công ty Cổ phần Dược phẩm OPV',
            maNhaSanXuat: 'OPV',
            soLuong: 3000,
            ngayGui: '09/02/2026 09:30',
            hanDung: '2028-02-05',
            trangThaiLo: 'CREATED', // Bình thường
            doiChieuKhop: true,
            txHash: '0x88f2a1b9c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0'
        },
        {
            // Dòng 3 đã sửa: Bên gửi là NSX Pharbaco, Lô này đã bị thu hồi (RECALLED)
            maGiaoDich: 'CG-000105',
            maLo: 'LOT-AMOX-2025-09',
            tenSanPham: 'Amoxicillin 500mg',
            benGui: 'Dược Phẩm Pharbaco (Nhà Sản Xuất)',
            maNhaSanXuat: 'PHARBACO',
            soLuong: 1500,
            ngayGui: '10/02/2026 11:20',
            hanDung: '2028-09-10',
            trangThaiLo: 'RECALLED', // ĐÃ BỊ THU HỒI
            doiChieuKhop: false, // Smart contract từ chối xác nhận lô RECALLED
            txHash: '0x71a49f2b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4e5'
        },
        {
            maGiaoDich: 'CG-000115',
            maLo: 'LOT-CEFA-2024-EX',
            tenSanPham: 'Cefalexin 500mg',
            benGui: 'Công ty CP Dược Phẩm Medipharco',
            maNhaSanXuat: 'MEDIPHARCO',
            soLuong: 1000,
            ngayGui: '10/02/2026 16:00',
            hanDung: '2025-01-01', // Lô đã hết hạn
            trangThaiLo: 'CREATED',
            doiChieuKhop: true,
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
            trangThaiLo: 'CREATED',
            txHash: '0x3a4b5c6d7e8f9a0b1c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b',
            // Lịch sử giao nhận: Có cả mốc Nhận từ NSX và Chuyển đi
            lichSuChuyenGiao: [
                {
                    maGD: 'CG-000042',
                    loai: 'NHAN_VAO',
                    doiTac: 'Công ty CP Dược Phẩm Medipharco',
                    soLuong: 4000,
                    thoiGianKhoiTao: '20/01/2026 09:30',
                    thoiGianXacNhan: '20/01/2026 15:45',
                    trangThai: 'RECEIVED',
                    txHash: '0x3a4b5c6d7e8f9a0b1c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b'
                },
                {
                    maGD: 'CG-000078',
                    loai: 'CHUYEN_DI',
                    doiTac: 'Nhà Thuốc PharmaCare Cần Thơ',
                    soLuong: 1500,
                    thoiGianKhoiTao: '05/02/2026 10:00',
                    thoiGianXacNhan: '05/02/2026 14:20',
                    trangThai: 'RECEIVED',
                    txHash: '0x8f2d5e1b4a3c79a2f60e1d8b5c9a4e2f1a3b5c7d9e1f2a4b6c8d0e2f4a6b8c0'
                },
                {
                    maGD: 'CG-000091',
                    loai: 'CHUYEN_DI',
                    doiTac: 'Nhà Thuốc An Khang Ninh Kiều',
                    soLuong: 500,
                    thoiGianKhoiTao: '08/02/2026 16:30',
                    thoiGianXacNhan: null, // Chưa xác nhận
                    trangThai: 'PENDING',
                    txHash: '0x1c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0b1c2'
                }
            ]
        },
        {
            maLo: 'LOT-AMOX-2025-09',
            tenSanPham: 'Amoxicillin 500mg',
            hanDung: '2028-09-10',
            soLuongTongNhap: 5000,
            trangThaiLo: 'RECALLED', // Lô bị thu hồi khẩn cấp -> khả dụng 0
            txHash: '0x71a49f2b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4e5',
            lichSuChuyenGiao: [
                {
                    maGD: 'CG-000021',
                    loai: 'NHAN_VAO',
                    doiTac: 'Dược Phẩm Pharbaco',
                    soLuong: 5000,
                    thoiGianKhoiTao: '15/09/2025 10:00',
                    thoiGianXacNhan: '16/09/2025 08:30',
                    trangThai: 'RECEIVED',
                    txHash: '0x71a49f2b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4e5'
                }
            ]
        },
        {
            maLo: 'LOT-CEFA-2024-EX',
            tenSanPham: 'Cefalexin 500mg',
            hanDung: '2025-01-01', // Lô đã hết hạn sử dụng -> không cho chuyển
            soLuongTongNhap: 2000,
            trangThaiLo: 'CREATED',
            txHash: '0x12a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4e5f6a7b8c9d0e1f2a3',
            lichSuChuyenGiao: [
                {
                    maGD: 'CG-000008',
                    loai: 'NHAN_VAO',
                    doiTac: 'Công ty CP Dược Phẩm Medipharco',
                    soLuong: 2000,
                    thoiGianKhoiTao: '10/01/2023 11:00',
                    thoiGianXacNhan: '10/01/2023 16:20',
                    trangThai: 'RECEIVED',
                    txHash: '0x12a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4e5f6a7b8c9d0e1f2a3'
                }
            ]
        }
    ];

    const doiTacNhaThuoc = [
        "Nhà Thuốc PharmaCare Cần Thơ (Ví: 0x5a...91B)",
        "Nhà Thuốc Long Châu - Chi nhánh 38 (Ví: 0x1c...42C)",
        "Nhà Thuốc An Khang Ninh Kiều (Ví: 0x9f...10D)",
        "Kho Dược Bệnh Viện Đa Khoa TP (Ví: 0x2e...88E)"
    ];

    // Tính khả dụng: Nếu RECALLED hoặc hết hạn thì trả về 0; ngược lại = Tổng nhập - các lần chuyển đi
    function tinhKhaDungKho(item) {
        if (item.trangThaiLo === 'RECALLED') return 0;
        const homNay = layNgayHienTai();
        if (item.hanDung < homNay) return 0;

        const daXuatDi = item.lichSuChuyenGiao
            .filter(cg => cg.loai === 'CHUYEN_DI')
            .reduce((sum, cg) => sum + cg.soLuong, 0);

        return Math.max(0, item.soLuongTongNhap - daXuatDi);
    }

    // Nhãn trạng thái kho: Đang giữ / Đã chuyển hết / Đã hết hạn / Đã thu hồi
    function layTrangThaiKho(item) {
        const homNay = layNgayHienTai();
        if (item.trangThaiLo === 'RECALLED') {
            return { nhan: 'Đã thu hồi', badgeClass: 'bg-red-100 text-red-700 border-red-300', choPhepChuyen: false, lyDoKhoa: 'Lô thuốc đã bị cơ quan quản lý thu hồi' };
        }
        if (item.hanDung < homNay) {
            return { nhan: 'Đã hết hạn', badgeClass: 'bg-amber-100 text-amber-800 border-amber-300', choPhepChuyen: false, lyDoKhoa: 'Lô thuốc đã hết hạn sử dụng' };
        }
        const khaDung = tinhKhaDungKho(item);
        if (khaDung === 0) {
            return { nhan: 'Đã chuyển hết', badgeClass: 'bg-slate-100 text-slate-700 border-slate-300', choPhepChuyen: false, lyDoKhoa: 'Hết số lượng khả dụng' };
        }
        return { nhan: 'Đang giữ', badgeClass: 'bg-emerald-100 text-emerald-800 border-emerald-300', choPhepChuyen: true, lyDoKhoa: '' };
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

    // ══ 4. RENDER TAB 1: DANH SÁCH CHỜ TIẾP NHẬN ══
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
            if (benGuiLoc !== 'TAT_CA') matchBenGui = (item.maNhaSanXuat === benGuiLoc);

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
            thanBangPending.innerHTML = `<tr><td colspan="9" class="text-center py-8 text-slate-400 italic">Không có lô hàng nào phù hợp với bộ lọc tìm kiếm.</td></tr>`;
            thongTinPhanTrangPending.textContent = "Không có bản ghi nào";
            cumNutPhanTrangPending.innerHTML = '';
            return;
        }

        thongTinPhanTrangPending.textContent = `Đang hiển thị ${batDau + 1} đến ${Math.min(ketThuc, danhSachLoc.length)} trên tổng số ${danhSachLoc.length} lô`;

        duLieuTrang.forEach(item => {
            const daHetHan = (item.hanDung < homNay);
            const biThuHoi = (item.trangThaiLo === 'RECALLED');
            
            // Khóa nhận hàng nếu lô đã bị thu hồi hoặc hết hạn
            const coTheNhan = !biThuHoi && !daHetHan && item.doiChieuKhop;
            let lyDoKhoaNhan = '';
            if (biThuHoi) lyDoKhoaNhan = 'Lô thuốc đã bị thu hồi - Smart Contract từ chối nhận';
            else if (daHetHan) lyDoKhoaNhan = 'Lô thuốc đã hết hạn sử dụng - Từ chối nhập kho';
            else if (!item.doiChieuKhop) lyDoKhoaNhan = 'Đối chiếu blockchain không khớp';

            // Nhãn hạn dùng
            let hanDungHtml = `<span class="font-mono text-slate-700 text-[11px]">${dinhDangNgay(item.hanDung)}</span>`;
            if (daHetHan) {
                hanDungHtml += `<span class="badge-chuan ml-1 px-1.5 py-0.2 rounded text-[10px] font-bold bg-amber-100 text-amber-800">Hết hạn</span>`;
            }

            // Nhãn trạng thái hiển thị
            let nhanTrangThaiHtml = '';
            if (biThuHoi) {
                nhanTrangThaiHtml = `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-red-100 text-red-700 border border-red-300">Đã thu hồi</span>`;
            } else {
                nhanTrangThaiHtml = `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300"><i class="fa-solid fa-clock"></i> Chờ xác nhận</span>`;
            }

            const tr = document.createElement('tr');
            tr.className = 'dong-hang-pending transition-colors';
            tr.innerHTML = `
                <td class="py-3 px-3 font-mono font-bold text-cyan-900 whitespace-nowrap">${item.maGiaoDich}</td>
                <td class="py-3 px-3 font-mono font-bold text-slate-900 whitespace-nowrap">${item.maLo}</td>
                <td class="py-3 px-3 font-semibold text-slate-900">${item.tenSanPham}</td>
                <td class="py-3 px-3 text-slate-700">${item.benGui}</td>
                <td class="py-3 px-2.5 text-center whitespace-nowrap">${hanDungHtml}</td>
                <td class="py-3 px-3 text-right font-mono font-bold text-blue-700 whitespace-nowrap">${item.soLuong.toLocaleString()}</td>
                <td class="py-3 px-2.5 text-center text-slate-500 font-mono text-[11px] whitespace-nowrap">${item.ngayGui}</td>
                
                <!-- Nhãn trạng thái không rớt chữ -->
                <td class="py-3 px-2.5 text-center whitespace-nowrap">
                    ${nhanTrangThaiHtml}
                </td>

                <td class="py-3 px-3 text-center whitespace-nowrap">
                    <div class="inline-flex items-center gap-1.5">
                        <button type="button" class="btn-xem-chi-tiet-pending px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-all flex items-center gap-1 border border-slate-300 shadow-2xs" data-magd="${item.maGiaoDich}">
                            <i class="fa-solid fa-eye"></i> Chi Tiết
                        </button>
                        <button type="button" class="btn-mo-nhan-hang px-2.5 py-1.5 rounded-lg text-white font-bold text-xs transition-all shadow-2xs flex items-center gap-1 ${coTheNhan ? 'bg-blue-700 hover:bg-blue-800' : 'bg-slate-300 text-slate-500 cursor-not-allowed'}" data-magd="${item.maGiaoDich}" ${!coTheNhan ? 'disabled' : ''} title="${lyDoKhoaNhan}">
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
            if (trangThaiLoc === 'DANG_GIU') matchTrangThai = (tt.nhan === 'Đang giữ');
            else if (trangThaiLoc === 'DA_CHUYEN_HET') matchTrangThai = (tt.nhan === 'Đã chuyển hết');
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
                <td class="py-3 px-3 font-mono font-bold text-slate-900 whitespace-nowrap">${item.maLo}</td>
                <td class="py-3 px-2 text-center whitespace-nowrap">
                    <button type="button" class="btn-tai-qr-thung p-1.5 bg-nenChinh hover:bg-emerald-50 text-emerald-800 rounded-lg border border-emerald-900/10 text-xs font-bold transition-all shadow-2xs" data-malo="${item.maLo}" title="Tải tem QR thùng">
                        <i class="fa-solid fa-qrcode mr-1"></i> Tải QR
                    </button>
                </td>
                <td class="py-3 px-3 font-semibold text-slate-900 leading-tight">${item.tenSanPham}</td>
                <td class="py-3 px-2.5 text-center font-mono text-slate-600 text-[11px] whitespace-nowrap">${dinhDangNgay(item.hanDung)}</td>
                <td class="py-3 px-3 text-right font-mono font-semibold whitespace-nowrap">${item.soLuongTongNhap.toLocaleString()}</td>
                
                <!-- Lô thu hồi hiện 0 khả dụng -->
                <td class="py-3 px-3 text-right font-mono font-bold whitespace-nowrap ${khaDung > 0 ? 'text-emerald-700' : 'text-slate-400'}">
                    ${khaDung.toLocaleString()}
                </td>

                <td class="py-3 px-2.5 text-center whitespace-nowrap">
                    <span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border ${tt.badgeClass}">
                        ${tt.nhan}
                    </span>
                </td>

                <td class="py-3 px-3 text-center whitespace-nowrap">
                    <div class="inline-flex items-center gap-1.5">
                        <button type="button" class="btn-mo-chuyen-tiep px-2.5 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1 ${coTheChuyen ? 'bg-blue-700 hover:bg-blue-800 text-white shadow-2xs' : 'bg-slate-100 text-slate-400 cursor-not-allowed'}" data-malo="${item.maLo}" ${!coTheChuyen ? 'disabled' : ''} title="${tt.lyDoKhoa}">
                            <i class="fa-solid fa-truck-arrow-right"></i> Chuyển Tiếp
                        </button>
                        <button type="button" class="btn-xem-chi-tiet-kho px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-all flex items-center gap-1 border border-slate-300 shadow-2xs" data-malo="${item.maLo}">
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

    // ══ 6. XỬ LÝ MODAL 1: ĐỐI CHIẾU & XÁC NHẬN NHẬP KHO (FR08) ══
    const modalXacNhanNhan = document.getElementById('modalXacNhanNhan');
    const nutXacNhanNhapKho = document.getElementById('nutXacNhanNhapKho');
    const nhanKetQuaDoiChieu = document.getElementById('nhanKetQuaDoiChieu');
    const linkBaoCaoTuNhanHang = document.getElementById('linkBaoCaoTuNhanHang');
    let itemPendingDangChon = null;

    function moModalNhanHang(item) {
        itemPendingDangChon = item;
        document.getElementById('nhanMaGiaoDich').textContent = item.maGiaoDich;
        document.getElementById('nhanMaLo').textContent = item.maLo;
        document.getElementById('nhanTenSanPham').textContent = item.tenSanPham;
        document.getElementById('nhanBenGui').textContent = item.benGui;
        document.getElementById('nhanSoLuong').textContent = `${item.soLuong.toLocaleString()} hộp`;
        document.getElementById('nhanHanDung').textContent = dinhDangNgay(item.hanDung);

        // Hiển thị kết quả đối chiếu Blockchain
        if (item.doiChieuKhop) {
            nhanKetQuaDoiChieu.className = "inline-flex items-center gap-1.5 font-bold text-emerald-700 text-xs";
            nhanKetQuaDoiChieu.innerHTML = `<i class="fa-solid fa-circle-check"></i> Khớp (Dữ liệu hợp lệ on-chain)`;
            nutXacNhanNhapKho.disabled = false;
            nutXacNhanNhapKho.className = "px-6 py-2.5 bg-blue-700 hover:bg-blue-800 text-white font-bold rounded-xl shadow-md transition-all flex items-center gap-2";
        } else {
            nhanKetQuaDoiChieu.className = "inline-flex items-center gap-1.5 font-bold text-red-600 text-xs";
            nhanKetQuaDoiChieu.innerHTML = `<i class="fa-solid fa-circle-xmark"></i> Không khớp / Lô đã bị thu hồi`;
            nutXacNhanNhapKho.disabled = true;
            nutXacNhanNhapKho.className = "px-6 py-2.5 bg-slate-300 text-slate-500 font-bold rounded-xl transition-all flex items-center gap-2 cursor-not-allowed";
        }

        // Cập nhật link báo cáo nghi vấn kèm mã lô
        linkBaoCaoTuNhanHang.href = `../trangDungChung/quanLyBaoCaoNghiVan.php?vaiTro=NHA_PHAN_PHOI&maLo=${encodeURIComponent(item.maLo)}`;

        modalXacNhanNhan.classList.remove('hidden');
    }

    function ganSuKienNutPending() {
        document.querySelectorAll('.btn-mo-nhan-hang').forEach(btn => {
            btn.addEventListener('click', function () {
                const magd = this.getAttribute('data-magd');
                const item = danhSachPending.find(x => x.maGiaoDich === magd);
                if (item) moModalNhanHang(item);
            });
        });

        document.querySelectorAll('.btn-xem-chi-tiet-pending').forEach(btn => {
            btn.addEventListener('click', function () {
                const magd = this.getAttribute('data-magd');
                const item = danhSachPending.find(x => x.maGiaoDich === magd);
                if (!item) return;

                itemPendingDangChon = item;
                document.getElementById('pendingModalMaLo').textContent = item.maLo;
                document.getElementById('pendingModalSanPham').textContent = item.tenSanPham;
                document.getElementById('pendingModalBenGui').textContent = item.benGui;
                document.getElementById('pendingModalSoLuong').textContent = `${item.soLuong.toLocaleString()} hộp`;
                document.getElementById('pendingModalHanDung').textContent = dinhDangNgay(item.hanDung);
                document.getElementById('pendingModalNgayGui').textContent = item.ngayGui;
                document.getElementById('pendingModalTxHash').textContent = item.txHash;
                document.getElementById('pendingModalTxLink').href = `${EXPLORER_BASE_URL}${item.txHash}`;
                document.getElementById('linkBaoCaoTuPendingDetail').href = `../trangDungChung/quanLyBaoCaoNghiVan.php?vaiTro=NHA_PHAN_PHOI&maLo=${encodeURIComponent(item.maLo)}`;

                document.getElementById('modalChiTietPending').classList.remove('hidden');
            });
        });
    }

    document.querySelectorAll('.dong-modal-pending-detail').forEach(btn => {
        btn.addEventListener('click', () => document.getElementById('modalChiTietPending').classList.add('hidden'));
    });

    document.getElementById('nutNhanHangTuDetail').addEventListener('click', function () {
        document.getElementById('modalChiTietPending').classList.add('hidden');
        if (itemPendingDangChon) moModalNhanHang(itemPendingDangChon);
    });

    document.querySelectorAll('.dong-modal-nhan').forEach(btn => {
        btn.addEventListener('click', () => modalXacNhanNhan.classList.add('hidden'));
    });

    document.getElementById('nutCopyMaGDNhan').addEventListener('click', function () {
        if (!itemPendingDangChon) return;
        navigator.clipboard.writeText(itemPendingDangChon.maGiaoDich).then(() => {
            this.innerHTML = `<i class="fa-solid fa-check text-emerald-600"></i>`;
            setTimeout(() => { this.innerHTML = `<i class="fa-regular fa-copy"></i>`; }, 1500);
        });
    });

    document.getElementById('nutCopyPendingTx').addEventListener('click', function () {
        if (!itemPendingDangChon) return;
        navigator.clipboard.writeText(itemPendingDangChon.txHash).then(() => {
            this.innerHTML = `<i class="fa-solid fa-check text-emerald-600"></i> Đã sao chép`;
            setTimeout(() => { this.innerHTML = `<i class="fa-regular fa-copy"></i> Sao chép`; }, 1500);
        });
    });

    nutXacNhanNhapKho.addEventListener('click', function () {
        if (!itemPendingDangChon) return;

        const now = new Date();
        const thoiGianXacNhanStr = `${String(now.getDate()).padStart(2,'0')}/${String(now.getMonth()+1).padStart(2,'0')}/${now.getFullYear()} ${String(now.getHours()).padStart(2,'0')}:${String(now.getMinutes()).padStart(2,'0')}`;

        // Nhập vào kho
        const daCoTrongKho = danhSachKho.find(x => x.maLo === itemPendingDangChon.maLo);
        if (daCoTrongKho) {
            daCoTrongKho.soLuongTongNhap += itemPendingDangChon.soLuong;
            daCoTrongKho.lichSuChuyenGiao.unshift({
                maGD: itemPendingDangChon.maGiaoDich,
                loai: 'NHAN_VAO',
                doiTac: itemPendingDangChon.benGui,
                soLuong: itemPendingDangChon.soLuong,
                thoiGianKhoiTao: itemPendingDangChon.ngayGui,
                thoiGianXacNhan: thoiGianXacNhanStr,
                trangThai: 'RECEIVED',
                txHash: itemPendingDangChon.txHash
            });
        } else {
            danhSachKho.unshift({
                maLo: itemPendingDangChon.maLo,
                tenSanPham: itemPendingDangChon.tenSanPham,
                hanDung: itemPendingDangChon.hanDung,
                soLuongTongNhap: itemPendingDangChon.soLuong,
                trangThaiLo: 'CREATED',
                txHash: itemPendingDangChon.txHash,
                lichSuChuyenGiao: [
                    {
                        maGD: itemPendingDangChon.maGiaoDich,
                        loai: 'NHAN_VAO',
                        doiTac: itemPendingDangChon.benGui,
                        soLuong: itemPendingDangChon.soLuong,
                        thoiGianKhoiTao: itemPendingDangChon.ngayGui,
                        thoiGianXacNhan: thoiGianXacNhanStr,
                        trangThai: 'RECEIVED',
                        txHash: itemPendingDangChon.txHash
                    }
                ]
            });
        }

        danhSachPending = danhSachPending.filter(x => x.maGiaoDich !== itemPendingDangChon.maGiaoDich);

        alert(`Xác nhận nhận hàng thành công! Đã ghi nhận bạn đã nhận hàng lên blockchain và cộng ${itemPendingDangChon.soLuong.toLocaleString()} hộp vào kho.`);
        modalXacNhanNhan.classList.add('hidden');
        renderBangPending();
        renderBangKho();
    });

    // ══ 7. NÚT QUÉT QR THÙNG NHẬP NHANH ══
    document.getElementById('nutQuetQRNhanHang').addEventListener('click', function () {
        const maNhap = prompt("Mô phỏng máy quét mã QR thùng lô thuốc (quét camera/đầu đọc):\nVui lòng nhập mã lô (VD: LOT-PARA-2026):", "LOT-PARA-2026");
        if (maNhap) {
            const item = danhSachPending.find(x => x.maLo.toUpperCase() === maNhap.trim().toUpperCase());
            if (item) {
                moModalNhanHang(item);
            } else {
                alert(`Không tìm thấy lô hàng nào có mã "${maNhap}" trong danh sách chờ tiếp nhận.`);
            }
        }
    });

    // ══ 8. XỬ LÝ MODAL 3: CHUYỂN TIẾP CHO NHÀ THUỐC ══
    const modalChuyenTiep = document.getElementById('modalChuyenTiep');
    const formChuyenTiep = document.getElementById('formSubmitChuyenTiep');
    const chonDoiTacNhan = document.getElementById('chonDoiTacNhan');
    const nhapSoLuongChuyen = document.getElementById('nhapSoLuongChuyen');
    const loiSoLuongChuyen = document.getElementById('loiSoLuongChuyen');
    let itemKhoDangChon = null;

    function doDanhSachNhaThuoc() {
        chonDoiTacNhan.innerHTML = '';
        doiTacNhaThuoc.forEach(dt => {
            const opt = document.createElement('option');
            opt.value = dt;
            opt.textContent = dt;
            chonDoiTacNhan.appendChild(opt);
        });
    }

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

                doDanhSachNhaThuoc();
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
        const now = new Date();
        const thoiGianKhoiTaoStr = `${String(now.getDate()).padStart(2,'0')}/${String(now.getMonth()+1).padStart(2,'0')}/${now.getFullYear()} ${String(now.getHours()).padStart(2,'0')}:${String(now.getMinutes()).padStart(2,'0')}`;
        const maGDMoi = 'CG-' + String(Math.floor(100000 + Math.random() * 900000));

        itemKhoDangChon.lichSuChuyenGiao.push({
            maGD: maGDMoi,
            loai: 'CHUYEN_DI',
            doiTac: tenDoiTac,
            soLuong: slXuat,
            thoiGianKhoiTao: thoiGianKhoiTaoStr,
            thoiGianXacNhan: null, // Dòng chờ xác nhận
            trangThai: 'PENDING',
            txHash: '0x' + Array.from({length: 64}, () => Math.floor(Math.random()*16).toString(16)).join('')
        });

        alert(`Đã khởi tạo chuyển tiếp ${slXuat.toLocaleString()} hộp lô ${itemKhoDangChon.maLo} đến ${tenDoiTac}! Mã GD: ${maGDMoi} (Trạng thái: Chờ xác nhận).`);
        modalChuyenTiep.classList.add('hidden');
        renderBangKho();
    });

    // ══ 9. XỬ LÝ MODAL 4: XEM CHI TIẾT KHO & TIMELINE ══
    const modalChiTietKho = document.getElementById('modalChiTietKho');
    let loDangXemChiTiet = null;

    function moChiTietKho(malo) {
        loDangXemChiTiet = danhSachKho.find(x => x.maLo === malo);
        if (!loDangXemChiTiet) return;

        const khaDung = tinhKhaDungKho(loDangXemChiTiet);
        document.getElementById('modalKhoMaLoTitle').textContent = loDangXemChiTiet.maLo;
        document.getElementById('modalKhoSanPham').textContent = loDangXemChiTiet.tenSanPham;
        document.getElementById('modalKhoHanDung').textContent = dinhDangNgay(loDangXemChiTiet.hanDung);
        document.getElementById('modalKhoTongNhap').textContent = `${loDangXemChiTiet.soLuongTongNhap.toLocaleString()} hộp`;
        document.getElementById('modalKhoKhaDung').textContent = `${khaDung.toLocaleString()} hộp`;

        document.getElementById('modalKhoTxHash').textContent = loDangXemChiTiet.txHash;
        document.getElementById('modalKhoTxLink').href = `${EXPLORER_BASE_URL}${loDangXemChiTiet.txHash}`;
        document.getElementById('linkBaoCaoTuKhoDetail').href = `../trangDungChung/quanLyBaoCaoNghiVan.php?vaiTro=NHA_PHAN_PHOI&maLo=${encodeURIComponent(loDangXemChiTiet.maLo)}`;

        // Render Timeline đầy đủ nguồn nhận từ NSX và các lần chuyển đi
        const timelineBox = document.getElementById('danhSachTimelineKho');
        timelineBox.innerHTML = '';

        loDangXemChiTiet.lichSuChuyenGiao.forEach(cg => {
            const div = document.createElement('div');
            div.className = 'relative space-y-1.5 p-3 rounded-xl bg-nenChinh border border-slate-200/60';
            
            const laNhanVao = (cg.loai === 'NHAN_VAO');
            const dotColor = laNhanVao ? 'bg-blue-600' : (cg.trangThai === 'RECEIVED' ? 'bg-emerald-600' : 'bg-amber-500');
            const tieuDe = laNhanVao ? `Tiếp nhận từ: ${cg.doiTac}` : `Chuyển tiếp đến: ${cg.doiTac}`;
            const nhanTrangThai = (cg.trangThai === 'RECEIVED') ? 'Đã nhận' : 'Chờ xác nhận';
            const badgeClass = (cg.trangThai === 'RECEIVED') ? 'text-emerald-700 bg-emerald-100' : 'text-amber-700 bg-amber-100';
            const xacNhanText = cg.thoiGianXacNhan ? cg.thoiGianXacNhan : 'Chưa xác nhận';

            div.innerHTML = `
                <span class="absolute -left-[31px] top-4 w-3.5 h-3.5 rounded-full ${dotColor} ring-4 ring-white"></span>
                <div class="flex items-center justify-between">
                    <span class="font-bold text-slate-800 text-xs">${tieuDe}</span>
                    <span class="badge-chuan px-2 py-0.5 rounded text-[10px] font-extrabold ${badgeClass}">${nhanTrangThai}</span>
                </div>
                <div class="flex items-center justify-between text-slate-600 font-mono text-[11px]">
                    <span>Số lượng: <strong>${cg.soLuong.toLocaleString()}</strong> hộp</span>
                    <div class="flex items-center gap-1">
                        <span>Mã GD: <strong>${cg.maGD}</strong></span>
                        <button type="button" class="btn-copy-timeline-gd text-slate-400 hover:text-cyan-800 p-0.5" data-magd="${cg.maGD}" title="Sao chép mã giao dịch">
                            <i class="fa-regular fa-copy"></i>
                        </button>
                    </div>
                </div>
                <div class="text-[10px] text-slate-500 font-mono flex flex-wrap gap-x-4 gap-y-0.5 pt-1 border-t border-slate-200/40">
                    <span><i class="fa-regular fa-clock mr-1"></i> Khởi tạo: ${cg.thoiGianKhoiTao}</span>
                    <span><i class="fa-solid fa-check-double mr-1"></i> Xác nhận: ${xacNhanText}</span>
                </div>
            `;
            timelineBox.appendChild(div);
        });

        // Gắn sự kiện sao chép mã GD trong timeline
        document.querySelectorAll('.btn-copy-timeline-gd').forEach(btn => {
            btn.addEventListener('click', function () {
                const magd = this.getAttribute('data-magd');
                navigator.clipboard.writeText(magd).then(() => {
                    this.innerHTML = `<i class="fa-solid fa-check text-emerald-600"></i>`;
                    setTimeout(() => { this.innerHTML = `<i class="fa-regular fa-copy"></i>`; }, 1500);
                });
            });
        });

        modalChiTietKho.classList.remove('hidden');
    }

    document.querySelectorAll('.dong-modal-chitiet').forEach(btn => {
        btn.addEventListener('click', () => modalChiTietKho.classList.add('hidden'));
    });

    document.getElementById('nutCopyModalKhoTx').addEventListener('click', function () {
        if (!loDangXemChiTiet) return;
        navigator.clipboard.writeText(loDangXemChiTiet.txHash).then(() => {
            this.innerHTML = `<i class="fa-solid fa-check text-emerald-600"></i> Đã sao chép`;
            setTimeout(() => { this.innerHTML = `<i class="fa-regular fa-copy"></i> Sao chép`; }, 1500);
        });
    });

    // ══ 10. LẮNG NGHE SỰ KIỆN TÌM KIẾM & BỘ LỌC ══
    oTimKiemPending.addEventListener('input', () => { trangHienTaiPending = 1; renderBangPending(); });
    locBenGuiPending.addEventListener('change', () => { trangHienTaiPending = 1; renderBangPending(); });
    locHanDungPending.addEventListener('change', () => { trangHienTaiPending = 1; renderBangPending(); });

    oTimKiemKho.addEventListener('input', () => { trangHienTaiKho = 1; renderBangKho(); });
    locTrangThaiKho.addEventListener('change', () => { trangHienTaiKho = 1; renderBangKho(); });
    locHanDungKho.addEventListener('change', () => { trangHienTaiKho = 1; renderBangKho(); });

    renderBangPending();
    renderBangKho();
});
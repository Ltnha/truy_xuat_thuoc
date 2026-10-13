document.addEventListener('DOMContentLoaded', function () {
    const EXPLORER_BASE_URL = "https://amoy.polygonscan.com/tx/";
    const ITEMS_PER_PAGE = 4;
    let trangHienTaiTab1 = 1;
    let trangHienTaiTab2 = 1;

    // Tài khoản Cơ quan quản lý đang thẩm định
    const TAI_KHOAN_CQQL_DANG_NHAP = 'dav_inspector_01';

    // ══ 1. CSDL MÔ PHỎNG TAB 1: YÊU CẦU ĐĂNG KÝ (yeuCauDangKySanPham) ══
    let danhSachYeuCauSP = [
        {
            id: 'YC-SP-001',
            tenThuoc: 'Paracetamol 500mg',
            soDangKy: 'VD-28491-18',
            maNSX: 'MEDIPHARCO',
            tenNSX: 'Công ty CP Dược Phẩm Medipharco',
            mstNSX: '3300101568',
            giayPhepNSX: 'GMP-WHO-2024/045',
            trangThaiToChucNSX: 'DA_DUYET',
            diaChiNSX: 'Số 170 Đường Nguyễn Huệ, Thành Phố Huế, Thừa Thiên Huế',
            hoatChat: 'Paracetamol',
            hamLuong: '500mg',
            dangBaoChe: 'Viên nén bao phim',
            ngayNopHienTai: '01/02/2026',
            trangThai: 'CHO_DUYET',
            lichSuNop: [
                {
                    lan: 1,
                    ngayNop: '10/01/2026',
                    trangThai: 'TU_CHOI',
                    nguoiXuLy: 'dav_inspector_02',
                    ngayXuLy: '12/01/2026',
                    lyDoTuChoi: 'Số đăng ký lưu hành không trùng khớp danh mục cấp phép của Cục Quản lý Dược (đã nộp: VD-28491-17).',
                    snapshot: {
                        tenThuoc: 'Paracetamol 500mg',
                        soDangKy: 'VD-28491-17',
                        hoatChat: 'Acetaminophen (Paracetamol)',
                        hamLuong: '500mg',
                        dangBaoChe: 'Viên nén bao phim'
                    }
                },
                {
                    lan: 2,
                    ngayNop: '01/02/2026',
                    trangThai: 'CHO_DUYET',
                    nguoiXuLy: null,
                    ngayXuLy: null,
                    lyDoTuChoi: null,
                    snapshot: {
                        tenThuoc: 'Paracetamol 500mg',
                        soDangKy: 'VD-28491-18', // Sửa so với lần 1
                        hoatChat: 'Paracetamol',   // Sửa so với lần 1
                        hamLuong: '500mg',
                        dangBaoChe: 'Viên nén bao phim'
                    }
                }
            ]
        },
        {
            id: 'YC-SP-002',
            tenThuoc: 'Deslotid OPV 5mg',
            // Trùng số ĐK với sản phẩm Cefalexin 500mg (SP-001) đã duyệt ở Tab 2
            soDangKy: 'VD-33012-20', 
            maNSX: 'OPV',
            tenNSX: 'Công ty Cổ phần Dược phẩm OPV',
            mstNSX: '0301449988',
            giayPhepNSX: 'GMP-WHO-2023/110',
            trangThaiToChucNSX: 'BI_THU_HOI', // Minh họa NSX đang bị thu hồi quyền
            diaChiNSX: 'Khu Công Nghiệp Biên Hòa 2, Đồng Nai',
            hoatChat: 'Desloratadine',
            hamLuong: '5mg',
            dangBaoChe: 'Siro uống',
            ngayNopHienTai: '05/02/2026',
            trangThai: 'CHO_DUYET',
            lichSuNop: [
                {
                    lan: 1,
                    ngayNop: '05/02/2026',
                    trangThai: 'CHO_DUYET',
                    nguoiXuLy: null,
                    ngayXuLy: null,
                    lyDoTuChoi: null,
                    snapshot: {
                        tenThuoc: 'Deslotid OPV 5mg',
                        soDangKy: 'VD-33012-20',
                        hoatChat: 'Desloratadine',
                        hamLuong: '5mg',
                        dangBaoChe: 'Siro uống'
                    }
                }
            ]
        },
        {
            id: 'YC-SP-003',
            tenThuoc: 'Siro Ho Astex-S',
            soDangKy: 'VD-22991-17',
            maNSX: 'OPV',
            tenNSX: 'Công ty Cổ phần Dược phẩm OPV',
            mstNSX: '0301449988',
            giayPhepNSX: 'GMP-WHO-2023/110',
            trangThaiToChucNSX: 'BI_THU_HOI',
            diaChiNSX: 'Khu Công Nghiệp Biên Hòa 2, Đồng Nai',
            hoatChat: 'Tần dày lá, Núc nác, Cineol',
            hamLuong: '90ml',
            dangBaoChe: 'Siro thảo dược',
            ngayNopHienTai: '20/01/2026',
            trangThai: 'TU_CHOI',
            lichSuNop: [
                {
                    lan: 1,
                    ngayNop: '20/01/2026',
                    trangThai: 'TU_CHOI',
                    nguoiXuLy: 'dav_inspector_01',
                    ngayXuLy: '22/01/2026',
                    lyDoTuChoi: 'Dạng bào chế và hàm lượng dược chất chưa đồng nhất với danh mục giấy phép lưu hành gốc.',
                    snapshot: {
                        tenThuoc: 'Siro Ho Astex-S',
                        soDangKy: 'VD-22991-17',
                        hoatChat: 'Tần dày lá, Núc nác, Cineol',
                        hamLuong: '90ml',
                        dangBaoChe: 'Siro thảo dược'
                    }
                }
            ]
        }
    ];

    // ══ 2. CSDL MÔ PHỎNG TAB 2: SẢN PHẨM ĐÃ DUYỆT (sanPham + yeuCauDangKySanPham) ══
    let danhSachSanPhamDaDuyet = [
        {
            id: 'SP-001',
            tenThuoc: 'Cefalexin 500mg',
            soDangKy: 'VD-33012-20',
            hoatChat: 'Cefalexin Monohydrate',
            hamLuong: '500mg',
            dangBaoChe: 'Viên nang cứng',
            maNSX: 'MEDIPHARCO',
            tenNSX: 'Công ty CP Dược Phẩm Medipharco',
            mstNSX: '3300101568',
            giayPhepNSX: 'GMP-WHO-2024/045',
            trangThaiToChucNSX: 'DA_DUYET',
            diaChiNSX: 'Số 170 Đường Nguyễn Huệ, Thành Phố Huế, Thừa Thiên Huế',
            ngayDuyet: '18/01/2026', // Lấy từ ngayXuLy của lần duyệt
            nguoiDuyet: 'dav_inspector_01',
            txHash: '0x4f8a2b1c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f1a',
            soLoDaTao: 3,
            soLoBiThuHoi: 0,
            lichSuNop: [
                {
                    lan: 1,
                    ngayNop: '15/01/2026',
                    trangThai: 'DA_DUYET',
                    nguoiXuLy: 'dav_inspector_01',
                    ngayXuLy: '18/01/2026',
                    lyDoTuChoi: null,
                    snapshot: {
                        tenThuoc: 'Cefalexin 500mg',
                        soDangKy: 'VD-33012-20',
                        hoatChat: 'Cefalexin Monohydrate',
                        hamLuong: '500mg',
                        dangBaoChe: 'Viên nang cứng'
                    }
                }
            ]
        },
        {
            id: 'SP-002',
            tenThuoc: 'Amoxicillin 500mg',
            soDangKy: 'VD-19234-15',
            hoatChat: 'Amoxicillin Trihydrate',
            hamLuong: '500mg',
            dangBaoChe: 'Viên nang',
            maNSX: 'PHARBACO',
            tenNSX: 'Dược Phẩm Pharbaco',
            mstNSX: '0100108999',
            giayPhepNSX: 'GMP-WHO-2023/078',
            trangThaiToChucNSX: 'DA_DUYET',
            diaChiNSX: 'Số 160 Tôn Đức Thắng, Đống Đa, Hà Nội',
            ngayDuyet: '10/01/2026',
            nguoiDuyet: 'dav_inspector_02',
            txHash: '0x88f2a1b9c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0',
            soLoDaTao: 4,
            soLoBiThuHoi: 1, // Minh họa có 1 lô bị thu hồi (LOT-AMOX-2025-09)
            lichSuNop: [
                {
                    lan: 1,
                    ngayNop: '08/01/2026',
                    trangThai: 'DA_DUYET',
                    nguoiXuLy: 'dav_inspector_02',
                    ngayXuLy: '10/01/2026',
                    lyDoTuChoi: null,
                    snapshot: {
                        tenThuoc: 'Amoxicillin 500mg',
                        soDangKy: 'VD-19234-15',
                        hoatChat: 'Amoxicillin Trihydrate',
                        hamLuong: '500mg',
                        dangBaoChe: 'Viên nang'
                    }
                }
            ]
        }
    ];

    function taoBadgeTrangThai(status) {
        if (status === 'CHO_DUYET') {
            return `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300"><i class="fa-solid fa-clock"></i> Chờ duyệt</span>`;
        } else if (status === 'DA_DUYET') {
            return `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300"><i class="fa-solid fa-circle-check"></i> Đã duyệt</span>`;
        } else {
            return `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-red-100 text-red-700 border border-red-300"><i class="fa-solid fa-circle-xmark"></i> Bị từ chối</span>`;
        }
    }

    // ══ 3. CHUYỂN ĐỔI TAB ══
    const tabHoSoDangKy = document.getElementById('tabHoSoDangKy');
    const tabSanPhamDaDuyet = document.getElementById('tabSanPhamDaDuyet');
    const khungTabHoSo = document.getElementById('khungTabHoSo');
    const khungTabDaDuyet = document.getElementById('khungTabDaDuyet');

    tabHoSoDangKy.addEventListener('click', () => {
        tabHoSoDangKy.className = "flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all bg-indigo-700 text-white shadow-sm";
        tabSanPhamDaDuyet.className = "flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all text-slate-600 hover:text-indigo-700 hover:bg-indigo-50";
        khungTabHoSo.classList.remove('hidden');
        khungTabDaDuyet.classList.add('hidden');
        renderBangTab1();
    });

    tabSanPhamDaDuyet.addEventListener('click', () => {
        tabSanPhamDaDuyet.className = "flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all bg-indigo-700 text-white shadow-sm";
        tabHoSoDangKy.className = "flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all text-slate-600 hover:text-indigo-700 hover:bg-indigo-50";
        khungTabDaDuyet.classList.remove('hidden');
        khungTabHoSo.classList.add('hidden');
        renderBangTab2();
    });

    // ══ 4. RENDER TAB 1: HỒ SƠ ĐĂNG KÝ (4 TRẠNG THÁI) ══
    const thanBangTab1 = document.getElementById('thanBangYeuCauSP');
    const oTimKiemTab1 = document.getElementById('oTimKiemSP');
    const locNSXTab1 = document.getElementById('locNhaSanXuat');
    const locTrangThaiTab1 = document.getElementById('locTrangThaiYeuCauSP');
    const soLuongTab1Badge = document.getElementById('soLuongYeuCauSPBadge');
    const badgeTabHoSoChoDuyet = document.getElementById('badgeTabHoSoChoDuyet');
    const thongTinPhanTrangTab1 = document.getElementById('thongTinPhanTrangSP');
    const cumNutPhanTrangTab1 = document.getElementById('cumNutPhanTrangSP');

    function renderBangTab1() {
        const tuKhoa = oTimKiemTab1.value.trim().toLowerCase();
        const nsxChon = locNSXTab1.value;
        const trangThaiChon = locTrangThaiTab1.value;

        const danhSachLoc = danhSachYeuCauSP.filter(item => {
            const matchTuKhoa = item.tenThuoc.toLowerCase().includes(tuKhoa) ||
                                item.soDangKy.toLowerCase().includes(tuKhoa) ||
                                item.tenNSX.toLowerCase().includes(tuKhoa) ||
                                item.hoatChat.toLowerCase().includes(tuKhoa);
            const matchNSX = (nsxChon === 'TAT_CA') || (item.maNSX === nsxChon);
            const matchTrangThai = (trangThaiChon === 'TAT_CA') || (item.trangThai === trangThaiChon);
            return matchTuKhoa && matchNSX && matchTrangThai;
        });

        const soChoDuyet = danhSachYeuCauSP.filter(x => x.trangThai === 'CHO_DUYET').length;
        badgeTabHoSoChoDuyet.textContent = soChoDuyet;
        soLuongTab1Badge.textContent = `Hiển thị: ${danhSachLoc.length} hồ sơ`;

        const tongSoTrang = Math.ceil(danhSachLoc.length / ITEMS_PER_PAGE) || 1;
        if (trangHienTaiTab1 > tongSoTrang) trangHienTaiTab1 = tongSoTrang;

        const batDau = (trangHienTaiTab1 - 1) * ITEMS_PER_PAGE;
        const ketThuc = batDau + ITEMS_PER_PAGE;
        const duLieuTrang = danhSachLoc.slice(batDau, ketThuc);

        thanBangTab1.innerHTML = '';

        if (duLieuTrang.length === 0) {
            thanBangTab1.innerHTML = `<tr><td colspan="10" class="text-center py-8 text-slate-400 italic">Không có hồ sơ nào phù hợp với bộ lọc.</td></tr>`;
            thongTinPhanTrangTab1.textContent = "Không có bản ghi nào";
            cumNutPhanTrangTab1.innerHTML = '';
            return;
        }

        thongTinPhanTrangTab1.textContent = `Đang hiển thị ${batDau + 1} đến ${Math.min(ketThuc, danhSachLoc.length)} trên tổng số ${danhSachLoc.length} hồ sơ`;

        duLieuTrang.forEach(item => {
            const soLanNop = item.lichSuNop ? item.lichSuNop.length : 1;
            const tr = document.createElement('tr');
            tr.className = 'dong-yeu-cau-sp transition-colors';
            tr.innerHTML = `
                <td class="py-3 px-3 font-semibold text-slate-900 leading-tight">
                    <span class="block font-bold text-slate-900">${item.tenThuoc}</span>
                    <span class="font-mono text-[10px] text-slate-400">ID: ${item.id}</span>
                </td>
                <td class="py-3 px-3 font-mono font-bold text-cyan-800 whitespace-nowrap">${item.soDangKy}</td>
                <td class="py-3 px-3 text-slate-800 font-semibold leading-tight">${item.tenNSX}</td>
                <td class="py-3 px-3 text-slate-700 leading-tight">${item.hoatChat}</td>
                <td class="py-3 px-2.5 text-slate-600 font-mono whitespace-nowrap">${item.hamLuong}</td>
                <td class="py-3 px-2.5 text-slate-600 whitespace-nowrap">${item.dangBaoChe}</td>
                <td class="py-3 px-2.5 text-center font-mono text-[11px] text-slate-600 whitespace-nowrap">${item.ngayNopHienTai}</td>
                <td class="py-3 px-2.5 text-center whitespace-nowrap">
                    <span class="font-mono font-bold ${soLanNop > 1 ? 'text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200' : 'text-slate-600'}">
                        Lần ${soLanNop}
                    </span>
                </td>
                <td class="py-3 px-2.5 text-center whitespace-nowrap">${taoBadgeTrangThai(item.trangThai)}</td>
                <td class="py-3 px-3 text-center whitespace-nowrap">
                    <button type="button" class="btn-xem-ho-so-sp px-3 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-800 font-bold text-xs transition-all border border-indigo-200 shadow-2xs flex items-center justify-center gap-1.5 mx-auto" data-id="${item.id}">
                        <i class="fa-solid fa-eye text-indigo-700 text-[11px]"></i> Xem
                    </button>
                </td>
            `;
            thanBangTab1.appendChild(tr);
        });

        renderPhanTrang(cumNutPhanTrangTab1, trangHienTaiTab1, tongSoTrang, (p) => {
            trangHienTaiTab1 = p;
            renderBangTab1();
        });

        ganSuKienNutTab1();
    }

    // ══ 5. RENDER TAB 2: SẢN PHẨM ĐÃ DUYỆT (CHỈ ĐỌC) ══
    const thanBangTab2 = document.getElementById('thanBangDaDuyet');
    const oTimKiemTab2 = document.getElementById('oTimKiemDaDuyet');
    const locNSXTab2 = document.getElementById('locNhaSanXuatDaDuyet');
    const soLuongTab2Badge = document.getElementById('soLuongDaDuyetBadge');
    const badgeTabSanPhamDaDuyet = document.getElementById('badgeTabSanPhamDaDuyet');
    const thongTinPhanTrangTab2 = document.getElementById('thongTinPhanTrangDaDuyet');
    const cumNutPhanTrangTab2 = document.getElementById('cumNutPhanTrangDaDuyet');

    function renderBangTab2() {
        const tuKhoa = oTimKiemTab2.value.trim().toLowerCase();
        const nsxChon = locNSXTab2.value;

        const danhSachLoc = danhSachSanPhamDaDuyet.filter(item => {
            const matchTuKhoa = item.tenThuoc.toLowerCase().includes(tuKhoa) || item.soDangKy.toLowerCase().includes(tuKhoa);
            const matchNSX = (nsxChon === 'TAT_CA') || (item.maNSX === nsxChon);
            return matchTuKhoa && matchNSX;
        });

        badgeTabSanPhamDaDuyet.textContent = danhSachSanPhamDaDuyet.length;
        soLuongTab2Badge.textContent = `Hiển thị: ${danhSachLoc.length} sản phẩm`;

        const tongSoTrang = Math.ceil(danhSachLoc.length / ITEMS_PER_PAGE) || 1;
        if (trangHienTaiTab2 > tongSoTrang) trangHienTaiTab2 = tongSoTrang;

        const batDau = (trangHienTaiTab2 - 1) * ITEMS_PER_PAGE;
        const ketThuc = batDau + ITEMS_PER_PAGE;
        const duLieuTrang = danhSachLoc.slice(batDau, ketThuc);

        thanBangTab2.innerHTML = '';

        if (duLieuTrang.length === 0) {
            thanBangTab2.innerHTML = `<tr><td colspan="9" class="text-center py-8 text-slate-400 italic">Không tìm thấy sản phẩm đã duyệt nào phù hợp.</td></tr>`;
            thongTinPhanTrangTab2.textContent = "Không có bản ghi nào";
            cumNutPhanTrangTab2.innerHTML = '';
            return;
        }

        thongTinPhanTrangTab2.textContent = `Đang hiển thị ${batDau + 1} đến ${Math.min(ketThuc, danhSachLoc.length)} trên tổng số ${danhSachLoc.length} sản phẩm`;

        duLieuTrang.forEach(item => {
            const hexRutGon = `${item.txHash.substring(0, 8)}...${item.txHash.substring(item.txHash.length - 6)}`;
            const explorerLink = `${EXPLORER_BASE_URL}${item.txHash}`;

            const tr = document.createElement('tr');
            tr.className = 'dong-da-duyet-sp transition-colors';
            tr.innerHTML = `
                <td class="py-3 px-3 font-semibold text-slate-900 leading-tight">
                    <span class="block font-bold text-slate-900">${item.tenThuoc}</span>
                    <span class="font-mono text-[10px] text-slate-400">ID: ${item.id}</span>
                </td>
                <td class="py-3 px-3 font-mono font-bold text-cyan-800 whitespace-nowrap">${item.soDangKy}</td>
                <td class="py-3 px-3 text-slate-700 leading-tight">${item.hoatChat}</td>
                <td class="py-3 px-2.5 text-slate-600 font-mono whitespace-nowrap">${item.hamLuong}</td>
                <td class="py-3 px-2.5 text-slate-600 whitespace-nowrap">${item.dangBaoChe}</td>
                <td class="py-3 px-3 text-slate-800 font-semibold leading-tight">${item.tenNSX}</td>
                <td class="py-3 px-2.5 text-center font-mono text-[11px] text-slate-600 whitespace-nowrap">${item.ngayDuyet}</td>
                <td class="py-3 px-3 whitespace-nowrap">
                    <div class="inline-flex items-center gap-1.5">
                        <a href="${explorerLink}" target="_blank" class="font-mono text-[11px] font-bold text-cyan-800 hover:underline flex items-center gap-1">
                            ${hexRutGon} <i class="fa-solid fa-arrow-up-right-from-square text-[9px] text-slate-400"></i>
                        </a>
                        <button type="button" class="btn-copy-tx px-1.5 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-600 text-[10px]" data-tx="${item.txHash}" title="Sao chép">
                            <i class="fa-regular fa-copy"></i>
                        </button>
                    </div>
                </td>
                <td class="py-3 px-3 text-center whitespace-nowrap">
                    <button type="button" class="btn-xem-chi-tiet-daduyet px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-all border border-slate-300 shadow-2xs flex items-center justify-center gap-1.5 mx-auto" data-id="${item.id}">
                        <i class="fa-solid fa-circle-info text-indigo-700 text-[11px]"></i> Chi Tiết
                    </button>
                </td>
            `;
            thanBangTab2.appendChild(tr);
        });

        renderPhanTrang(cumNutPhanTrangTab2, trangHienTaiTab2, tongSoTrang, (p) => {
            trangHienTaiTab2 = p;
            renderBangTab2();
        });

        ganSuKienNutTab2();
    }

    // Helper phân trang
    function renderPhanTrang(container, trangHienTai, tongSoTrang, onChange) {
        container.innerHTML = '';
        if (tongSoTrang <= 1) return;

        const btnPrev = document.createElement('button');
        btnPrev.className = `px-2.5 py-1 rounded-lg border text-xs font-bold transition-all ${trangHienTai === 1 ? 'border-slate-200 text-slate-300 cursor-not-allowed' : 'border-slate-300 text-slate-700 hover:bg-slate-100'}`;
        btnPrev.innerHTML = `<i class="fa-solid fa-angle-left"></i>`;
        btnPrev.disabled = (trangHienTai === 1);
        btnPrev.addEventListener('click', () => { if (trangHienTai > 1) onChange(trangHienTai - 1); });
        container.appendChild(btnPrev);

        for (let i = 1; i <= tongSoTrang; i++) {
            const btnPage = document.createElement('button');
            btnPage.className = `w-7 h-7 rounded-lg text-xs font-bold transition-all ${trangHienTai === i ? 'bg-indigo-700 text-white shadow-2xs' : 'border border-slate-200 text-slate-700 hover:bg-slate-100'}`;
            btnPage.textContent = i;
            btnPage.addEventListener('click', () => onChange(i));
            container.appendChild(btnPage);
        }

        const btnNext = document.createElement('button');
        btnNext.className = `px-2.5 py-1 rounded-lg border text-xs font-bold transition-all ${trangHienTai === tongSoTrang ? 'border-slate-200 text-slate-300 cursor-not-allowed' : 'border-slate-300 text-slate-700 hover:bg-slate-100'}`;
        btnNext.innerHTML = `<i class="fa-solid fa-angle-right"></i>`;
        btnNext.disabled = (trangHienTai === tongSoTrang);
        btnNext.addEventListener('click', () => { if (trangHienTai < tongSoTrang) onChange(trangHienTai + 1); });
        container.appendChild(btnNext);
    }

    // ══ 6. MODAL DÙNG CHUNG CHO CẢ HAI CHẾ ĐỘ (CHỜ DUYỆT & ĐÃ DUYỆT) ══
    const modalXemHoSoSP = document.getElementById('modalXemHoSoSP');
    const panelCumNutHanhDongSP = document.getElementById('panelCumNutHanhDongSP');
    const modalCanhBaoTrungSoDK = document.getElementById('modalCanhBaoTrungSoDK');
    const modalCanhBaoTrungSoDKText = document.getElementById('modalCanhBaoTrungSoDKText');
    const nutXemChiTietSPTrung = document.getElementById('nutXemChiTietSPTrung');
    const modalCanhBaoNSXBiThuHoi = document.getElementById('modalCanhBaoNSXBiThuHoi');
    const khungThongKeVanHanhDaDuyet = document.getElementById('khungThongKeVanHanhDaDuyet');

    let hoSoDangMoModal = null;
    let cheDoModal = 'CHO_DUYET'; // 'CHO_DUYET' hoặc 'DA_DUYET'
    let sanPhamBiTrungRef = null;

    function resetDiffStyle() {
        ['khungTenThuoc', 'khungSoDK', 'khungHoatChat', 'khungHamLuong', 'khungDangBaoChe'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.className = 'p-2 rounded-lg';
        });
        ['diffTenThuoc', 'diffSoDK', 'diffHoatChat', 'diffHamLuong', 'diffDangBaoChe'].forEach(id => {
            const el = document.getElementById(id);
            if (el) { el.classList.add('hidden'); el.textContent = ''; }
        });
        document.getElementById('panelGhiChuSoSanh').classList.add('hidden');
    }

    function moModalSanPham(item, mode = 'CHO_DUYET') {
        hoSoDangMoModal = item;
        cheDoModal = mode;
        resetDiffStyle();

        // 1. Header & Badge
        document.getElementById('panelTieuDeChinh').textContent = (mode === 'DA_DUYET') ? 'Chi Tiết Sản Phẩm Đã Duyệt:' : 'Thẩm Định Hồ Sơ Thuốc:';
        document.getElementById('panelTenThuocTitle').textContent = `${item.tenThuoc} (${item.soDangKy})`;
        document.getElementById('panelTrangThaiSPBadge').innerHTML = taoBadgeTrangThai(item.trangThai || 'DA_DUYET');

        // 2. Thông tin 5 trường sản phẩm chuẩn CSDL
        document.getElementById('panelTenThuoc').textContent = item.tenThuoc;
        document.getElementById('panelSoDK').textContent = item.soDangKy;
        document.getElementById('panelHoatChat').textContent = item.hoatChat;
        document.getElementById('panelHamLuong').textContent = item.hamLuong;
        document.getElementById('panelDangBaoChe').textContent = item.dangBaoChe;

        // 3. Thông tin Nhà sản xuất
        document.getElementById('panelTenNSX').textContent = item.tenNSX;
        document.getElementById('panelMstNSX').textContent = item.mstNSX;
        document.getElementById('panelGiayPhepNSX').textContent = item.giayPhepNSX;
        document.getElementById('panelDiaChiNSX').textContent = item.diaChiNSX;
        
        const badgeNSX = (item.trangThaiToChucNSX === 'DA_DUYET') 
            ? `<span class="badge-chuan px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">Đã duyệt</span>`
            : `<span class="badge-chuan px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-700 border border-red-300">Bị thu hồi quyền</span>`;
        document.getElementById('panelTrangThaiToChucNSX').innerHTML = badgeNSX;

        // CẢNH BÁO NẾU NSX ĐANG BỊ THU HỒI QUYỀN
        if (item.trangThaiToChucNSX === 'BI_THU_HOI') {
            modalCanhBaoNSXBiThuHoi.classList.remove('hidden');
        } else {
            modalCanhBaoNSXBiThuHoi.classList.add('hidden');
        }

        // 4. KIỂM TRA TRÙNG SỐ ĐĂNG KÝ VỚI DANH MỤC ĐÃ DUYỆT (TAB 2)
        sanPhamBiTrungRef = null;
        if (mode === 'CHO_DUYET') {
            const spTrung = danhSachSanPhamDaDuyet.find(x => x.soDangKy.toUpperCase() === item.soDangKy.toUpperCase());
            if (spTrung) {
                sanPhamBiTrungRef = spTrung;
                modalCanhBaoTrungSoDK.classList.remove('hidden');
                modalCanhBaoTrungSoDKText.innerHTML = `Số đăng ký lưu hành <strong>${item.soDangKy}</strong> đã tồn tại ở sản phẩm đã duyệt <strong>"${spTrung.tenThuoc}"</strong> thuộc <strong>${spTrung.tenNSX}</strong> trên blockchain! Cột số đăng ký là duy nhất (UNIQUE).`;
            } else {
                modalCanhBaoTrungSoDK.classList.add('hidden');
            }

            // So sánh Diff nếu là lần nộp >= 2
            if (item.lichSuNop && item.lichSuNop.length >= 2) {
                const snapCu = item.lichSuNop[0].snapshot;
                const snapMoi = item.lichSuNop[item.lichSuNop.length - 1].snapshot;
                let coThayDoi = false;

                if (snapCu && snapMoi) {
                    if (snapCu.soDangKy !== snapMoi.soDangKy) {
                        coThayDoi = true;
                        document.getElementById('khungSoDK').className = 'p-2 rounded-lg field-highlight-diff';
                        const diffEl = document.getElementById('diffSoDK');
                        diffEl.classList.remove('hidden');
                        diffEl.textContent = `Lần 1: "${snapCu.soDangKy}"`;
                    }
                    if (snapCu.hoatChat !== snapMoi.hoatChat) {
                        coThayDoi = true;
                        document.getElementById('khungHoatChat').className = 'p-2 rounded-lg field-highlight-diff';
                        const diffEl = document.getElementById('diffHoatChat');
                        diffEl.classList.remove('hidden');
                        diffEl.textContent = `Lần 1: "${snapCu.hoatChat}"`;
                    }
                    if (coThayDoi) document.getElementById('panelGhiChuSoSanh').classList.remove('hidden');
                }
            }
        } else {
            modalCanhBaoTrungSoDK.classList.add('hidden');
        }

        // 5. Khối thống kê vận hành & Blockchain (Chỉ hiện khi ĐÃ DUYỆT)
        if (mode === 'DA_DUYET') {
            khungThongKeVanHanhDaDuyet.classList.remove('hidden');
            
            const thuHoiText = item.soLoBiThuHoi > 0 ? `<span class="text-red-600 font-bold">(${item.soLoBiThuHoi} lô đã bị thu hồi)</span>` : '<span class="text-emerald-700 font-bold">(0 lô bị thu hồi)</span>';
            document.getElementById('thongKeSoLoSanPham').innerHTML = `${item.soLoDaTao} lô thuốc đã đăng ký ${thuHoiText}`;

            document.getElementById('textTxHashDaDuyet').textContent = item.txHash;
            document.getElementById('linkTxHashDaDuyet').href = `${EXPLORER_BASE_URL}${item.txHash}`;
        } else {
            khungThongKeVanHanhDaDuyet.classList.add('hidden');
        }

        // 6. Lịch sử các lần nộp hồ sơ
        const containerLichSu = document.getElementById('panelDanhSachLichSuNopSP');
        containerLichSu.innerHTML = '';

        if (!item.lichSuNop || item.lichSuNop.length <= 1) {
            containerLichSu.innerHTML = `
                <div class="p-2 rounded-lg border border-slate-200 bg-slate-50 text-slate-500 italic text-center text-xs">
                    <i class="fa-solid fa-circle-info mr-1"></i> Đây là lần nộp hồ sơ đăng ký đầu tiên của sản phẩm này.
                </div>
            `;
        } else {
            item.lichSuNop.forEach(ls => {
                const cardLS = document.createElement('div');
                cardLS.className = 'p-2 rounded-lg border border-slate-200 bg-slate-50 space-y-1 text-xs';

                let badge = '';
                if (ls.trangThai === 'TU_CHOI') {
                    badge = `<span class="badge-chuan px-2 py-0.2 rounded text-[10px] font-bold bg-red-100 text-red-700">Lần ${ls.lan}: Bị từ chối</span>`;
                } else if (ls.trangThai === 'DA_DUYET') {
                    badge = `<span class="badge-chuan px-2 py-0.2 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">Lần ${ls.lan}: Đã duyệt</span>`;
                } else {
                    badge = `<span class="badge-chuan px-2 py-0.2 rounded text-[10px] font-bold bg-amber-100 text-amber-800">Lần ${ls.lan}: Chờ duyệt</span>`;
                }

                cardLS.innerHTML = `
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            ${badge}
                            <span class="font-mono text-[11px] text-slate-600">${ls.ngayNop}</span>
                        </div>
                        <span class="text-[10px] text-slate-500">${ls.nguoiXuLy ? `Người xử lý: <strong class="font-mono text-slate-700">${ls.nguoiXuLy}</strong>` : ''}</span>
                    </div>
                    ${ls.lyDoTuChoi ? `<div class="p-1.5 bg-red-50 text-red-800 rounded border border-red-200 text-[11px]"><strong>Lý do từ chối:</strong> ${ls.lyDoTuChoi}</div>` : ''}
                `;
                containerLichSu.appendChild(cardLS);
            });
        }

        // 7. Cụm nút footer modal: Chờ duyệt có 2 nút Phê duyệt / Từ chối. Đã duyệt chỉ có nút Đóng.
        if (mode === 'CHO_DUYET' && item.trangThai === 'CHO_DUYET') {
            panelCumNutHanhDongSP.classList.remove('hidden');
        } else {
            panelCumNutHanhDongSP.classList.add('hidden');
        }

        modalXemHoSoSP.classList.remove('hidden');
    }

    // Nút liên kết xem sản phẩm bị trùng
    nutXemChiTietSPTrung.addEventListener('click', () => {
        if (sanPhamBiTrungRef) {
            moModalSanPham(sanPhamBiTrungRef, 'DA_DUYET');
        }
    });

    document.querySelectorAll('.dong-modal-hoso-sp').forEach(btn => {
        btn.addEventListener('click', () => modalXemHoSoSP.classList.add('hidden'));
    });

    document.getElementById('nutCopyTxDaDuyet').addEventListener('click', function () {
        if (!hoSoDangMoModal || !hoSoDangMoModal.txHash) return;
        navigator.clipboard.writeText(hoSoDangMoModal.txHash).then(() => {
            this.innerHTML = `<i class="fa-solid fa-check text-emerald-600"></i>`;
            setTimeout(() => { this.innerHTML = `<i class="fa-regular fa-copy"></i>`; }, 1500);
        });
    });

    // ══ 7. GẮN SỰ KIỆN NÚT TRÊN CẢ 2 BẢNG ══
    function ganSuKienNutTab1() {
        document.querySelectorAll('.btn-xem-ho-so-sp').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                const item = danhSachYeuCauSP.find(x => x.id === id);
                if (item) moModalSanPham(item, 'CHO_DUYET');
            });
        });
    }

    function ganSuKienNutTab2() {
        document.querySelectorAll('.btn-xem-chi-tiet-daduyet').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                const item = danhSachSanPhamDaDuyet.find(x => x.id === id);
                if (item) moModalSanPham(item, 'DA_DUYET');
            });
        });

        document.querySelectorAll('.btn-copy-tx').forEach(btn => {
            btn.addEventListener('click', function () {
                const tx = this.getAttribute('data-tx');
                navigator.clipboard.writeText(tx).then(() => {
                    this.innerHTML = `<i class="fa-solid fa-check text-emerald-600"></i>`;
                    setTimeout(() => { this.innerHTML = `<i class="fa-regular fa-copy"></i>`; }, 1500);
                });
            });
        });
    }

    // ══ 8. XỬ LÝ MODAL PHÊ DUYỆT (TỰ ĐỘNG CHUYỂN SANG TAB 2 SAU KHI DUYỆT) ══
    const modalDuyetSP = document.getElementById('modalXacNhanDuyetSP');
    const manHinhFormDuyetSP = document.getElementById('manHinhFormDuyetSP');
    const manHinhDangGhiBlockchainSP = document.getElementById('manHinhDangGhiBlockchainSP');
    const manHinhLoiBlockchainSP = document.getElementById('manHinhLoiBlockchainSP');
    const nutMoModalDuyetSP = document.getElementById('panelNutMoModalDuyetSP');
    const nutXacNhanDuyetSPGhiBlockchain = document.getElementById('nutXacNhanDuyetSPGhiBlockchain');
    const nutThuLaiDuyetSPBlockchain = document.getElementById('nutThuLaiDuyetSPBlockchain');

    nutMoModalDuyetSP.addEventListener('click', function () {
        if (!hoSoDangMoModal) return;

        document.getElementById('duyetModalTenThuoc').textContent = hoSoDangMoModal.tenThuoc;
        document.getElementById('duyetModalSoDK').textContent = hoSoDangMoModal.soDangKy;
        document.getElementById('duyetModalNSX').textContent = hoSoDangMoModal.tenNSX;

        manHinhFormDuyetSP.classList.remove('hidden');
        manHinhDangGhiBlockchainSP.classList.add('hidden');
        manHinhLoiBlockchainSP.classList.add('hidden');
        modalDuyetSP.classList.remove('hidden');
    });

    document.querySelectorAll('.dong-modal-duyet-sp').forEach(btn => {
        btn.addEventListener('click', () => modalDuyetSP.classList.add('hidden'));
    });

    function thucHienGhiBlockchainDuyetSP() {
        if (!hoSoDangMoModal) return;

        manHinhFormDuyetSP.classList.add('hidden');
        manHinhDangGhiBlockchainSP.classList.remove('hidden');
        manHinhLoiBlockchainSP.classList.add('hidden');

        setTimeout(() => {
            const thanhCongOnChain = true;

            if (thanhCongOnChain) {
                const now = new Date();
                const ngayDuyetStr = `${String(now.getDate()).padStart(2,'0')}/${String(now.getMonth()+1).padStart(2,'0')}/${now.getFullYear()}`;
                const txHashMoi = '0x' + Array.from({length: 64}, () => Math.floor(Math.random()*16).toString(16)).join('');
                
                // 1. Cập nhật trạng thái yêu cầu
                hoSoDangMoModal.trangThai = 'DA_DUYET';
                const lanCuoi = hoSoDangMoModal.lichSuNop[hoSoDangMoModal.lichSuNop.length - 1];
                lanCuoi.trangThai = 'DA_DUYET';
                lanCuoi.nguoiXuLy = TAI_KHOAN_CQQL_DANG_NHAP;
                lanCuoi.ngayXuLy = ngayDuyetStr;

                // 2. Thêm vào bảng sản phẩm đã duyệt (Tab 2)
                danhSachSanPhamDaDuyet.unshift({
                    id: 'SP-00' + (danhSachSanPhamDaDuyet.length + 1),
                    tenThuoc: hoSoDangMoModal.tenThuoc,
                    soDangKy: hoSoDangMoModal.soDangKy,
                    hoatChat: hoSoDangMoModal.hoatChat,
                    hamLuong: hoSoDangMoModal.hamLuong,
                    dangBaoChe: hoSoDangMoModal.dangBaoChe,
                    maNSX: hoSoDangMoModal.maNSX,
                    tenNSX: hoSoDangMoModal.tenNSX,
                    mstNSX: hoSoDangMoModal.mstNSX,
                    giayPhepNSX: hoSoDangMoModal.giayPhepNSX,
                    trangThaiToChucNSX: hoSoDangMoModal.trangThaiToChucNSX,
                    diaChiNSX: hoSoDangMoModal.diaChiNSX,
                    ngayDuyet: ngayDuyetStr,
                    nguoiDuyet: TAI_KHOAN_CQQL_DANG_NHAP,
                    txHash: txHashMoi,
                    soLoDaTao: 0,
                    soLoBiThuHoi: 0,
                    lichSuNop: JSON.parse(JSON.stringify(hoSoDangMoModal.lichSuNop))
                });

                modalDuyetSP.classList.add('hidden');
                modalXemHoSoSP.classList.add('hidden');

                alert(`Phê duyệt lưu hành thành công thuốc [${hoSoDangMoModal.tenThuoc}]!\n- Trạng thái: Đã duyệt\n- Mã băm giao dịch on-chain: ${txHashMoi.substring(0, 18)}...\n- Sản phẩm đã được đồng bộ vào Tab "Sản phẩm đã duyệt".`);

                renderBangTab1();
                renderBangTab2();
            } else {
                manHinhDangGhiBlockchainSP.classList.add('hidden');
                manHinhLoiBlockchainSP.classList.remove('hidden');
            }
        }, 1200);
    }

    nutXacNhanDuyetSPGhiBlockchain.addEventListener('click', thucHienGhiBlockchainDuyetSP);
    nutThuLaiDuyetSPBlockchain.addEventListener('click', thucHienGhiBlockchainDuyetSP);

    // ══ 9. XỬ LÝ MODAL TỪ CHỐI ══
    const modalTuChoiSP = document.getElementById('modalTuChoiSP');
    const nutMoModalTuChoiSP = document.getElementById('panelNutMoModalTuChoiSP');
    const nhapLyDoTuChoiSP = document.getElementById('nhapLyDoTuChoiSP');
    const demKyTuTuChoiSP = document.getElementById('demKyTuTuChoiSP');
    const nutXacNhanTuChoiSPOffChain = document.getElementById('nutXacNhanTuChoiSPOffChain');

    nutMoModalTuChoiSP.addEventListener('click', function () {
        if (!hoSoDangMoModal) return;

        document.getElementById('tuChoiModalTenThuoc').textContent = hoSoDangMoModal.tenThuoc;
        document.getElementById('tuChoiModalSoDK').textContent = hoSoDangMoModal.soDangKy;
        nhapLyDoTuChoiSP.value = '';
        demKyTuTuChoiSP.textContent = '0/500';
        capNhatTrangThaiNutTuChoi();

        modalTuChoiSP.classList.remove('hidden');
    });

    function capNhatTrangThaiNutTuChoi() {
        const len = nhapLyDoTuChoiSP.value.trim().length;
        demKyTuTuChoiSP.textContent = `${len}/500`;

        if (len > 0) {
            nutXacNhanTuChoiSPOffChain.disabled = false;
            nutXacNhanTuChoiSPOffChain.className = "px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl shadow-md transition-all flex items-center gap-1.5 cursor-pointer";
        } else {
            nutXacNhanTuChoiSPOffChain.disabled = true;
            nutXacNhanTuChoiSPOffChain.className = "px-6 py-2.5 bg-slate-200 text-slate-400 font-bold rounded-xl transition-all flex items-center gap-1.5 cursor-not-allowed shadow-none";
        }
    }

    nhapLyDoTuChoiSP.addEventListener('input', capNhatTrangThaiNutTuChoi);

    document.querySelectorAll('.dong-modal-tuchoi-sp').forEach(btn => {
        btn.addEventListener('click', () => modalTuChoiSP.classList.add('hidden'));
    });

    nutXacNhanTuChoiSPOffChain.addEventListener('click', function () {
        const lyDo = nhapLyDoTuChoiSP.value.trim();
        if (!lyDo || !hoSoDangMoModal) return;

        const now = new Date();
        const ngayTuChoiStr = `${String(now.getDate()).padStart(2,'0')}/${String(now.getMonth()+1).padStart(2,'0')}/${now.getFullYear()}`;

        hoSoDangMoModal.trangThai = 'TU_CHOI';
        const lanCuoi = hoSoDangMoModal.lichSuNop[hoSoDangMoModal.lichSuNop.length - 1];
        lanCuoi.trangThai = 'TU_CHOI';
        lanCuoi.nguoiXuLy = TAI_KHOAN_CQQL_DANG_NHAP;
        lanCuoi.ngayXuLy = ngayTuChoiStr;
        lanCuoi.lyDoTuChoi = lyDo;

        modalTuChoiSP.classList.add('hidden');
        modalXemHoSoSP.classList.add('hidden');

        alert(`Đã từ chối hồ sơ đăng ký thuốc [${hoSoDangMoModal.tenThuoc}].\nLý do từ chối đã được lưu vào CSDL và sẽ hiển thị cho nhà sản xuất tại trang Quản lý sản phẩm.`);
        renderBangTab1();
    });

    // ══ 10. LẮNG NGHE TÌM KIẾM & BỘ LỌC ══
    oTimKiemTab1.addEventListener('input', () => { trangHienTaiTab1 = 1; renderBangTab1(); });
    locNSXTab1.addEventListener('change', () => { trangHienTaiTab1 = 1; renderBangTab1(); });
    locTrangThaiTab1.addEventListener('change', () => { trangHienTaiTab1 = 1; renderBangTab1(); });

    oTimKiemTab2.addEventListener('input', () => { trangHienTaiTab2 = 1; renderBangTab2(); });
    locNSXTab2.addEventListener('change', () => { trangHienTaiTab2 = 1; renderBangTab2(); });

    // Khởi chạy
    renderBangTab1();
    renderBangTab2();
});
document.addEventListener('DOMContentLoaded', function () {
    const EXPLORER_BASE_URL = "https://amoy.polygonscan.com/address/";
    const ITEMS_PER_PAGE = 5;
    let trangHienTai = 1;

    // Helper: Tính số ngày còn lại đến khi hết hạn giấy phép (Mốc tham chiếu: 10/10/2026)
    function tinhSoNgayConLai(ngayHetHanStr) {
        if (!ngayHetHanStr) return 999;
        const [d, m, y] = ngayHetHanStr.split('/').map(Number);
        const hetHan = new Date(y, m - 1, d);
        const homNay = new Date(2026, 9, 10);
        const diffTime = hetHan.getTime() - homNay.getTime();
        return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    }

    // Helper: Rút gọn địa chỉ ví 0x1234...5678
    function rutGonDiaChiVi(vi) {
        if (!vi || vi.length < 12) return vi || '—';
        return `${vi.substring(0, 6)}...${vi.substring(vi.length - 4)}`;
    }

    // ══ 1. CSDL MÔ PHỎNG (toChuc, diaChiBlockchain, tonKhoLo, yeuCauDangKy) ══
    let danhSachToChuc = [
        {
            id: 1,
            tenToChuc: 'Công ty CP Dược Phẩm Medipharco',
            loaiHinh: 'NHA_SAN_XUAT',
            maSoThue: '3300101568',
            soGiayPhep: 'GMP-WHO-2024/045',
            coQuanCap: 'Bộ Y Tế - Cục Quản Lý Dược',
            ngayCap: '12/03/2024',
            ngayHetHanPhep: '12/03/2027', // Còn hạn (> 90 ngày)
            diaChi: 'Số 170 Đường Nguyễn Huệ, Thành Phố Huế, Thừa Thiên Huế',
            soDienThoai: '0234.382.3025',
            diaChiVi: '0x9a5b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0b',
            trangThaiPheDuyet: 'DA_DUYET',
            ngayDuyet: '15/03/2024',
            nguoiDuyet: 'dav_inspector_01',
            chuyenGiaoTreo: 0,
            thongKe: {
                soLo: 12, // NSX: Số lô đã đăng ký
                soLuongDangGiu: '150.000'
            },
            minhChung: [{ ten: 'Chung_Nhan_GMP_WHO_Medipharco.pdf' }, { ten: 'Dang_Ky_Doanh_Nghiep.pdf' }]
        },
        {
            id: 2,
            tenToChuc: 'Công ty Cổ phần Dược phẩm CPC1 Hà Nội',
            loaiHinh: 'NHA_PHAN_PHOI',
            maSoThue: '0103984512',
            soGiayPhep: 'GDP-HN-2025/112',
            coQuanCap: 'Bộ Y Tế - Cục Quản Lý Dược',
            ngayCap: '10/06/2025',
            ngayHetHanPhep: '10/11/2026', // Dưới 90 ngày (31 ngày) -> Sắp hết hạn
            diaChi: 'Cụm Công Nghiệp Hà Bình Phương, Huyện Thường Tín, Hà Nội',
            soDienThoai: '0243.856.7899',
            diaChiVi: '0x3a4b5c6d7e8f9a0b1c2d3e4f5a6b7c8d9e0f11c2',
            trangThaiPheDuyet: 'DA_DUYET',
            ngayDuyet: '12/06/2025',
            nguoiDuyet: 'dav_inspector_01',
            chuyenGiaoTreo: 2, // Đang có 2 chuyển giao treo chờ xác nhận
            thongKe: {
                soLo: 6, // NPP: Số lô đang giữ
                soLuongDangGiu: '45.000'
            },
            minhChung: [{ ten: 'Giay_Phep_GDP_CPC1.pdf' }]
        },
        {
            id: 3,
            tenToChuc: 'Nhà Thuốc PharmaCare Cần Thơ',
            loaiHinh: 'NHA_THUOC',
            maSoThue: '1801689988',
            soGiayPhep: 'GPP-CT-2026/089',
            coQuanCap: 'Sở Y Tế Thành Phố Cần Thơ',
            ngayCap: '15/01/2026',
            ngayHetHanPhep: '15/01/2029', // Còn hạn (> 90 ngày)
            diaChi: 'Số 256, Đường 30 Tháng 4, Phường Hưng Lợi, Quận Ninh Kiều, Cần Thơ',
            soDienThoai: '0292.3838.999',
            diaChiVi: '0x5a6b7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f91b4',
            trangThaiPheDuyet: 'DA_DUYET',
            ngayDuyet: '01/02/2026',
            nguoiDuyet: 'dav_inspector_02',
            chuyenGiaoTreo: 1, // Đang có 1 đơn chuyển giao chờ xác nhận nhận hàng
            thongKe: {
                soLo: 4, // Nhà thuốc: Số lô đang giữ
                soLuongDangGiu: '2.500'
            },
            minhChung: [{ ten: 'Chung_Chi_Hanh_Nghe_GPP.pdf' }]
        },
        {
            // TỔ CHỨC HẾT HẠN GIẤY PHÉP NHƯNG VẪN ĐÃ DUYỆT (CẦN NỔI BẬT VIỀN CAM)
            id: 4,
            tenToChuc: 'Công ty Dược phẩm BioPharm Miền Trung',
            loaiHinh: 'NHA_PHAN_PHOI',
            maSoThue: '3200889911',
            soGiayPhep: 'GDP-MT-2023/044',
            coQuanCap: 'Sở Y Tế Tỉnh Quảng Trị',
            ngayCap: '15/09/2023',
            ngayHetHanPhep: '15/09/2026', // ĐÃ HẾT HẠN (25 ngày trước)
            diaChi: 'Số 88 Lê Duẩn, TP. Đông Hà, Tỉnh Quảng Trị',
            soDienThoai: '0233.385.1234',
            diaChiVi: '0x1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c',
            trangThaiPheDuyet: 'DA_DUYET', // Chưa thu hồi -> Cần cảnh báo nổi bật!
            ngayDuyet: '20/09/2023',
            nguoiDuyet: 'dav_inspector_01',
            chuyenGiaoTreo: 0,
            thongKe: {
                soLo: 2,
                soLuongDangGiu: '12.000'
            },
            minhChung: [{ ten: 'Giay_Phep_GDP_Het_Han.pdf' }]
        },
        {
            // TỔ CHỨC ĐÃ BỊ THU HỒI
            id: 5,
            tenToChuc: 'Công ty CP Dược Phẩm OPV',
            loaiHinh: 'NHA_SAN_XUAT',
            maSoThue: '0301449988',
            soGiayPhep: 'GMP-WHO-2023/110',
            coQuanCap: 'Bộ Y Tế - Cục Quản Lý Dược',
            ngayCap: '05/04/2023',
            ngayHetHanPhep: '05/04/2026', // Quá hạn
            diaChi: 'Khu Công Nghiệp Biên Hòa 2, Đồng Nai',
            soDienThoai: '0251.383.6899',
            diaChiVi: '0x88f2a1b9c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8',
            trangThaiPheDuyet: 'BI_THU_HOI',
            ngayDuyet: '10/04/2023',
            nguoiDuyet: 'dav_inspector_02',
            ngayThuHoi: '20/04/2026 14:30',
            nguoiThuHoi: 'dav_inspector_01',
            lyDoThuHoi: 'Giấy chứng nhận GMP hết hạn hiệu lực không tiến hành tái thẩm định; đình chỉ hoạt động theo quyết định 102/QĐ-XPHC.',
            chuyenGiaoTreo: 0,
            thongKe: {
                soLo: 5,
                soLuongDangGiu: '0'
            },
            minhChung: [{ ten: 'Giay_Phep_GMP_Cu.pdf' }]
        }
    ];

    const mapLoaiHinh = {
        'NHA_SAN_XUAT': { ten: 'Nhà sản xuất', class: 'bg-emerald-50 text-emerald-800 border-emerald-300' },
        'NHA_PHAN_PHOI': { ten: 'Nhà phân phối', class: 'bg-blue-50 text-blue-800 border-blue-300' },
        'NHA_THUOC': { ten: 'Nhà thuốc', class: 'bg-cyan-50 text-cyan-800 border-cyan-300' }
    };

    function taoBadgeTrangThai(status) {
        if (status === 'DA_DUYET') {
            return `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300"><i class="fa-solid fa-circle-check"></i> Đã duyệt</span>`;
        } else {
            return `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-red-100 text-red-700 border border-red-300"><i class="fa-solid fa-ban"></i> Bị thu hồi</span>`;
        }
    }

    // ══ 2. RENDER BẢNG DANH SÁCH & PHÂN TRANG ══
    const thanBang = document.getElementById('thanBangQuyenHan');
    const oTimKiem = document.getElementById('oTimKiemQuyenHan');
    const locLoaiHinh = document.getElementById('locLoaiHinhToChuc');
    const locTrangThai = document.getElementById('locTrangThaiPheDuyet');
    const locHanPhep = document.getElementById('locHanGiayPhep');
    const soLuongBadge = document.getElementById('soLuongToChucBadge');
    const thongTinPhanTrang = document.getElementById('thongTinPhanTrang');
    const cumNutPhanTrang = document.getElementById('cumNutPhanTrang');

    function renderBangQuyenHan() {
        const tuKhoa = oTimKiem.value.trim().toLowerCase();
        const loaiChon = locLoaiHinh.value;
        const trangThaiChon = locTrangThai.value;
        const hanPhepChon = locHanPhep.value;

        const danhSachLoc = danhSachToChuc.filter(item => {
            const matchTuKhoa = item.tenToChuc.toLowerCase().includes(tuKhoa) || item.maSoThue.includes(tuKhoa);
            const matchLoai = (loaiChon === 'TAT_CA') || (item.loaiHinh === loaiChon);
            const matchTrangThai = (trangThaiChon === 'TAT_CA') || (item.trangThaiPheDuyet === trangThaiChon);

            let matchHan = true;
            const soNgay = tinhSoNgayConLai(item.ngayHetHanPhep);
            if (hanPhepChon === 'CON_HAN') matchHan = (soNgay > 90);
            else if (hanPhepChon === 'SAP_HET_HAN') matchHan = (soNgay > 0 && soNgay <= 90);
            else if (hanPhepChon === 'HET_HAN') matchHan = (soNgay <= 0);

            return matchTuKhoa && matchLoai && matchTrangThai && matchHan;
        });

        soLuongBadge.textContent = `Hiển thị: ${danhSachLoc.length} tổ chức`;

        const tongSoTrang = Math.ceil(danhSachLoc.length / ITEMS_PER_PAGE) || 1;
        if (trangHienTai > tongSoTrang) trangHienTai = tongSoTrang;

        const batDau = (trangHienTai - 1) * ITEMS_PER_PAGE;
        const ketThuc = batDau + ITEMS_PER_PAGE;
        const duLieuTrang = danhSachLoc.slice(batDau, ketThuc);

        thanBang.innerHTML = '';

        if (duLieuTrang.length === 0) {
            thanBang.innerHTML = `<tr><td colspan="7" class="text-center py-8 text-slate-400 italic">Không tìm thấy tổ chức nào phù hợp với bộ lọc tìm kiếm.</td></tr>`;
            thongTinPhanTrang.textContent = "Không có bản ghi nào";
            cumNutPhanTrang.innerHTML = '';
            return;
        }

        thongTinPhanTrang.textContent = `Đang hiển thị ${batDau + 1} đến ${Math.min(ketThuc, danhSachLoc.length)} trên tổng số ${danhSachLoc.length} tổ chức`;

        duLieuTrang.forEach(item => {
            const lh = mapLoaiHinh[item.loaiHinh] || { ten: item.loaiHinh, class: 'bg-slate-100 text-slate-700 border-slate-200' };
            const soNgayCon = tinhSoNgayConLai(item.ngayHetHanPhep);

            // Xử lý nhãn hạn giấy phép
            let hanPhepBadge = '';
            let hetHanNhungChuaThuHoi = false;

            if (soNgayCon <= 0) {
                hanPhepBadge = `<span class="badge-chuan ml-1 px-1.5 py-0.2 rounded text-[10px] font-bold bg-red-100 text-red-700 border border-red-300">Hết hạn</span>`;
                if (item.trangThaiPheDuyet === 'DA_DUYET') {
                    hetHanNhungChuaThuHoi = true; // Cần nổi bật viền cam!
                }
            } else if (soNgayCon <= 90) {
                hanPhepBadge = `<span class="badge-chuan ml-1 px-1.5 py-0.2 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300">Sắp hết hạn (${soNgayCon} ngày)</span>`;
            }

            // QUY TẮC NÚT: Chỉ tổ chức "Đã duyệt" mới có nút "Thu hồi quyền". Đã thu hồi chỉ có "Chi tiết".
            let nutThuHoiHtml = '';
            if (item.trangThaiPheDuyet === 'DA_DUYET') {
                nutThuHoiHtml = `
                    <button type="button" class="btn-mo-thuhoi-modal px-2.5 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 font-bold text-xs transition-all flex items-center gap-1 border border-red-200 whitespace-nowrap shadow-2xs" data-id="${item.id}">
                        <i class="fa-solid fa-ban text-[10px]"></i> Thu hồi quyền
                    </button>
                `;
            }

            const tr = document.createElement('tr');
            // Thêm class viền cam nếu hết hạn mà vẫn Đã duyệt
            tr.className = `dong-quyen-han transition-colors ${hetHanNhungChuaThuHoi ? 'dong-canh-bao-het-han' : ''}`;
            tr.innerHTML = `
                <td class="py-3 px-3 font-semibold text-slate-900 leading-tight">
                    <div class="flex items-center gap-1.5">
                        <span class="font-bold text-slate-900">${item.tenToChuc}</span>
                        ${hetHanNhungChuaThuHoi ? '<span class="text-[10px] text-amber-800 font-bold bg-amber-200 px-1 rounded" title="Giấy phép đã hết hạn nhưng tổ chức vẫn chưa bị thu hồi quyền!"><i class="fa-solid fa-triangle-exclamation"></i> Cần xử lý</span>' : ''}
                    </div>
                    <span class="font-mono text-[10px] text-slate-400">ID: TC-00${item.id}</span>
                </td>
                <td class="py-3 px-2.5 text-center whitespace-nowrap">
                    <span class="badge-chuan px-2 py-0.5 rounded-md text-[11px] font-bold border ${lh.class}">
                        ${lh.ten}
                    </span>
                </td>
                <td class="py-3 px-3 font-mono font-bold text-slate-800 whitespace-nowrap">${item.maSoThue}</td>
                <td class="py-3 px-3 font-mono font-semibold text-indigo-900 whitespace-nowrap">${item.soGiayPhep}</td>
                <td class="py-3 px-3 text-center whitespace-nowrap">
                    <span class="font-mono text-[11px] text-slate-700">${item.ngayHetHanPhep}</span>
                    ${hanPhepBadge}
                </td>
                <td class="py-3 px-2.5 text-center whitespace-nowrap">
                    ${taoBadgeTrangThai(item.trangThaiPheDuyet)}
                </td>
                <td class="py-3 px-3 text-center whitespace-nowrap">
                    <div class="inline-flex items-center justify-center gap-1.5">
                        <button type="button" class="btn-chi-tiet-tochuc px-2.5 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-800 font-bold text-xs transition-all flex items-center gap-1 border border-indigo-200 shadow-2xs whitespace-nowrap" data-id="${item.id}">
                            <i class="fa-solid fa-eye text-indigo-700 text-[10px]"></i> Chi tiết
                        </button>
                        ${nutThuHoiHtml}
                    </div>
                </td>
            `;
            thanBang.appendChild(tr);
        });

        renderPhanTrang(tongSoTrang);
        ganSuKienNutBang();
    }

    function renderPhanTrang(tongSoTrang) {
        cumNutPhanTrang.innerHTML = '';
        if (tongSoTrang <= 1) return;

        const btnPrev = document.createElement('button');
        btnPrev.className = `px-2.5 py-1 rounded-lg border text-xs font-bold transition-all ${trangHienTai === 1 ? 'border-slate-200 text-slate-300 cursor-not-allowed' : 'border-slate-300 text-slate-700 hover:bg-slate-100'}`;
        btnPrev.innerHTML = `<i class="fa-solid fa-angle-left"></i>`;
        btnPrev.disabled = (trangHienTai === 1);
        btnPrev.addEventListener('click', () => { if (trangHienTai > 1) { trangHienTai--; renderBangQuyenHan(); } });
        cumNutPhanTrang.appendChild(btnPrev);

        for (let i = 1; i <= tongSoTrang; i++) {
            const btnPage = document.createElement('button');
            btnPage.className = `w-7 h-7 rounded-lg text-xs font-bold transition-all ${trangHienTai === i ? 'bg-indigo-700 text-white shadow-2xs' : 'border border-slate-200 text-slate-700 hover:bg-slate-100'}`;
            btnPage.textContent = i;
            btnPage.addEventListener('click', () => { trangHienTai = i; renderBangQuyenHan(); });
            cumNutPhanTrang.appendChild(btnPage);
        }

        const btnNext = document.createElement('button');
        btnNext.className = `px-2.5 py-1 rounded-lg border text-xs font-bold transition-all ${trangHienTai === tongSoTrang ? 'border-slate-200 text-slate-300 cursor-not-allowed' : 'border-slate-300 text-slate-700 hover:bg-slate-100'}`;
        btnNext.innerHTML = `<i class="fa-solid fa-angle-right"></i>`;
        btnNext.disabled = (trangHienTai === tongSoTrang);
        btnNext.addEventListener('click', () => { if (trangHienTai < tongSoTrang) { trangHienTai++; renderBangQuyenHan(); } });
        cumNutPhanTrang.appendChild(btnNext);
    }

    // ══ 3. MODAL CHI TIẾT TỔ CHỨC ══
    const modalDetail = document.getElementById('modalChiTietToChuc');
    const detailKhungNutThuHoiNhanh = document.getElementById('detailKhungNutThuHoiNhanh');
    const detailBannerDaThuHoi = document.getElementById('detailBannerDaThuHoi');
    let toChucDangXemDetail = null;

    function moModalChiTietToChuc(id) {
        toChucDangXemDetail = danhSachToChuc.find(x => x.id === id);
        if (!toChucDangXemDetail) return;

        const lh = mapLoaiHinh[toChucDangXemDetail.loaiHinh] || { ten: toChucDangXemDetail.loaiHinh, class: 'bg-slate-100 text-slate-700 border-slate-200' };
        const soNgayCon = tinhSoNgayConLai(toChucDangXemDetail.ngayHetHanPhep);

        document.getElementById('detailTenToChucTitle').textContent = toChucDangXemDetail.tenToChuc;
        document.getElementById('detailTrangThaiBadge').innerHTML = taoBadgeTrangThai(toChucDangXemDetail.trangThaiPheDuyet);

        // 1. Phân biệt nhãn số lô theo loại hình: NSX là "Số lô đã đăng ký", NPP/NT là "Số lô đang giữ"
        const nhanLo = document.getElementById('detailNhanSoLo');
        if (toChucDangXemDetail.loaiHinh === 'NHA_SAN_XUAT') {
            nhanLo.textContent = "Số lô đã đăng ký:";
        } else {
            nhanLo.textContent = "Số lô đang giữ:";
        }
        document.getElementById('detailGiaTriSoLo').textContent = `${toChucDangXemDetail.thongKe.soLo} lô`;
        document.getElementById('detailSoDonViLuuThong').textContent = `${toChucDangXemDetail.thongKe.soLuongDangGiu} hộp`;

        // 2. Thông tin pháp lý
        document.getElementById('detailTenToChuc').textContent = toChucDangXemDetail.tenToChuc;
        document.getElementById('detailLoaiHinh').innerHTML = `<span class="badge-chuan px-2 py-0.5 rounded text-[11px] font-bold border ${lh.class}">${lh.ten}</span>`;
        document.getElementById('detailMaSoThue').textContent = toChucDangXemDetail.maSoThue;
        document.getElementById('detailSoGiayPhep').textContent = toChucDangXemDetail.soGiayPhep;
        document.getElementById('detailNgayHetHan').textContent = toChucDangXemDetail.ngayHetHanPhep;
        document.getElementById('detailCoQuanCap').textContent = toChucDangXemDetail.coQuanCap;
        document.getElementById('detailSoDienThoai').textContent = toChucDangXemDetail.soDienThoai;
        document.getElementById('detailDiaChi').textContent = toChucDangXemDetail.diaChi;

        // 3. Địa chỉ ví on-chain rút gọn kèm link Explorer & nút copy
        document.getElementById('detailDiaChiViOnChain').textContent = rutGonDiaChiVi(toChucDangXemDetail.diaChiVi);
        document.getElementById('detailLinkViExplorer').href = `${EXPLORER_BASE_URL}${toChucDangXemDetail.diaChiVi}`;

        // Cảnh báo hạn giấy phép trong modal chi tiết
        const badgePhep = document.getElementById('detailCanhBaoPhepBadge');
        if (soNgayCon <= 0) {
            badgePhep.className = "badge-chuan px-1.5 py-0.2 rounded text-[10px] font-bold bg-red-100 text-red-700 border border-red-300";
            badgePhep.innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i> Đã hết hạn`;
        } else if (soNgayCon <= 90) {
            badgePhep.className = "badge-chuan px-1.5 py-0.2 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300";
            badgePhep.innerHTML = `<i class="fa-solid fa-clock"></i> Sắp hết hạn (${soNgayCon} ngày)`;
        } else {
            badgePhep.className = "hidden";
        }

        // 4. Lịch sử quyền của tổ chức
        document.getElementById('detailNgayDuyetToChuc').textContent = toChucDangXemDetail.ngayDuyet || '—';
        document.getElementById('detailNguoiDuyetToChuc').textContent = toChucDangXemDetail.nguoiDuyet || '—';

        // 5. Nếu tổ chức BỊ THU HỒI: Hiện banner đỏ nổi bật kèm thông tin thu hồi và KHÔNG có nút thu hồi quyền
        if (toChucDangXemDetail.trangThaiPheDuyet === 'BI_THU_HOI') {
            detailBannerDaThuHoi.classList.remove('hidden');
            document.getElementById('detailLyDoThuHoiText').textContent = toChucDangXemDetail.lyDoThuHoi || 'Đình chỉ quyền do vi phạm điều kiện pháp lý.';
            document.getElementById('detailNguoiThuHoiText').textContent = toChucDangXemDetail.nguoiThuHoi || 'dav_inspector_01';
            document.getElementById('detailThoiGianThuHoiText').textContent = toChucDangXemDetail.ngayThuHoi || '—';
            detailKhungNutThuHoiNhanh.innerHTML = '';
        } else {
            detailBannerDaThuHoi.classList.add('hidden');
            detailKhungNutThuHoiNhanh.innerHTML = `
                <button type="button" id="nutMoThuHoiTuDetail" class="px-4 py-2 bg-red-50 hover:bg-red-100 text-red-600 font-bold text-xs rounded-xl border border-red-200 transition-all flex items-center gap-1.5 shadow-2xs">
                    <i class="fa-solid fa-ban text-[10px]"></i> Thu hồi quyền tổ chức này
                </button>
            `;
            document.getElementById('nutMoThuHoiTuDetail').addEventListener('click', () => {
                modalDetail.classList.add('hidden');
                moModalThuHoiQuyen(toChucDangXemDetail.id);
            });
        }

        // Danh sách minh chứng
        const containerMC = document.getElementById('detailDanhSachMinhChung');
        containerMC.innerHTML = '';
        toChucDangXemDetail.minhChung.forEach(mc => {
            const div = document.createElement('div');
            div.className = 'p-1.5 bg-white rounded-lg border border-indigo-200/80 flex items-center justify-between text-xs';
            div.innerHTML = `
                <div class="flex items-center gap-2 truncate">
                    <i class="fa-solid fa-file-pdf text-red-500"></i>
                    <span class="font-bold text-slate-800 truncate text-[11px]" title="${mc.ten}">${mc.ten}</span>
                </div>
                <button type="button" class="px-2 py-0.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-800 rounded font-bold text-[10px] shrink-0" onclick="alert('Đang mở xem tệp pháp lý bảo mật: ${mc.ten}')">
                    Xem
                </button>
            `;
            containerMC.appendChild(div);
        });

        modalDetail.classList.remove('hidden');
    }

    document.querySelectorAll('.dong-modal-detail').forEach(btn => {
        btn.addEventListener('click', () => modalDetail.classList.add('hidden'));
    });

    document.getElementById('nutCopyDiaChiViDetail').addEventListener('click', function () {
        if (!toChucDangXemDetail) return;
        navigator.clipboard.writeText(toChucDangXemDetail.diaChiVi).then(() => {
            this.innerHTML = `<i class="fa-solid fa-check text-emerald-600"></i>`;
            setTimeout(() => { this.innerHTML = `<i class="fa-regular fa-copy"></i>`; }, 1500);
        });
    });

    // ══ 4. MODAL THU HỒI QUYỀN HẠN (REVOKE ON-CHAIN) ══
    const modalThuHoi = document.getElementById('modalThuHoiQuyen');
    const manHinhFormThuHoi = document.getElementById('manHinhFormThuHoi');
    const manHinhDangThuHoiBlockchain = document.getElementById('manHinhDangThuHoiBlockchain');
    const manHinhLoiThuHoiBlockchain = document.getElementById('manHinhLoiThuHoiBlockchain');
    const thuHoiModalTenToChuc = document.getElementById('thuHoiModalTenToChuc');
    const thuHoiModalMST = document.getElementById('thuHoiModalMST');
    const thuHoiModalVaiTro = document.getElementById('thuHoiModalVaiTro');
    const thuHoiModalDiaChiVi = document.getElementById('thuHoiModalDiaChiVi');
    const thuHoiKhungChuyenGiaoTreo = document.getElementById('thuHoiKhungChuyenGiaoTreo');
    const thuHoiChuyenGiaoTreoText = document.getElementById('thuHoiChuyenGiaoTreoText');
    const nhapLyDoThuHoi = document.getElementById('nhapLyDoThuHoiQuyen');
    const demKyTuThuHoi = document.getElementById('demKyTuThuHoi');
    const nutXacNhanThuHoiOnChain = document.getElementById('nutXacNhanThuHoiOnChain');
    const nutThuLaiThuHoi = document.getElementById('nutThuLaiThuHoiBlockchain');
    let toChucDangThuHoi = null;

    function moModalThuHoiQuyen(id) {
        toChucDangThuHoi = danhSachToChuc.find(x => x.id === id);
        if (!toChucDangThuHoi) return;

        const lh = mapLoaiHinh[toChucDangThuHoi.loaiHinh]?.ten || toChucDangThuHoi.loaiHinh;

        thuHoiModalTenToChuc.textContent = toChucDangThuHoi.tenToChuc;
        thuHoiModalMST.textContent = toChucDangThuHoi.maSoThue;
        thuHoiModalVaiTro.textContent = lh;
        thuHoiModalDiaChiVi.textContent = rutGonDiaChiVi(toChucDangThuHoi.diaChiVi);

        // Hiển thị cảnh báo nếu đang có giao dịch chuyển giao treo chờ xác nhận
        if (toChucDangThuHoi.chuyenGiaoTreo > 0) {
            thuHoiKhungChuyenGiaoTreo.classList.remove('hidden');
            thuHoiChuyenGiaoTreoText.innerHTML = `Tổ chức hiện đang có <strong>${toChucDangThuHoi.chuyenGiaoTreo} đợt chuyển giao lô hàng chờ xác nhận</strong>. Sau khi thu hồi, tổ chức sẽ không thể xác nhận nhận hàng.`;
        } else {
            thuHoiKhungChuyenGiaoTreo.classList.add('hidden');
        }

        nhapLyDoThuHoi.value = '';
        demKyTuThuHoi.textContent = '0/500';
        capNhatTrangThaiNutThuHoi();

        manHinhFormThuHoi.classList.remove('hidden');
        manHinhDangThuHoiBlockchain.classList.add('hidden');
        manHinhLoiThuHoiBlockchain.classList.add('hidden');
        modalThuHoi.classList.remove('hidden');
    }

    function capNhatTrangThaiNutThuHoi() {
        const len = nhapLyDoThuHoi.value.trim().length;
        demKyTuThuHoi.textContent = `${len}/500`;

        if (len > 0) {
            nutXacNhanThuHoiOnChain.disabled = false;
            nutXacNhanThuHoiOnChain.className = "px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl shadow-md transition-all flex items-center gap-1.5 cursor-pointer";
        } else {
            nutXacNhanThuHoiOnChain.disabled = true;
            nutXacNhanThuHoiOnChain.className = "px-6 py-2.5 bg-slate-200 text-slate-400 font-bold rounded-xl transition-all flex items-center gap-1.5 cursor-not-allowed shadow-none";
        }
    }

    nhapLyDoThuHoi.addEventListener('input', capNhatTrangThaiNutThuHoi);

    document.querySelectorAll('.dong-modal-thuhoi').forEach(btn => {
        btn.addEventListener('click', () => modalThuHoi.classList.add('hidden'));
    });

    document.getElementById('nutCopyViModalThuHoi').addEventListener('click', function () {
        if (!toChucDangThuHoi) return;
        navigator.clipboard.writeText(toChucDangThuHoi.diaChiVi).then(() => {
            this.innerHTML = `<i class="fa-solid fa-check text-emerald-600"></i>`;
            setTimeout(() => { this.innerHTML = `<i class="fa-regular fa-copy"></i>`; }, 1500);
        });
    });

    function thucHienThuHoiOnChain() {
        const lyDo = nhapLyDoThuHoi.value.trim();
        if (!lyDo || !toChucDangThuHoi) return;

        manHinhFormThuHoi.classList.add('hidden');
        manHinhDangThuHoiBlockchain.classList.remove('hidden');
        manHinhLoiThuHoiBlockchain.classList.add('hidden');

        setTimeout(() => {
            // Giả lập tỉ lệ thành công (thanhCong = true)
            const thanhCongOnChain = true;

            if (thanhCongOnChain) {
                const now = new Date();
                const thoiGianStr = `${String(now.getDate()).padStart(2,'0')}/${String(now.getMonth()+1).padStart(2,'0')}/${now.getFullYear()} ${String(now.getHours()).padStart(2,'0')}:${String(now.getMinutes()).padStart(2,'0')}`;

                toChucDangThuHoi.trangThaiPheDuyet = 'BI_THU_HOI';
                toChucDangThuHoi.lyDoThuHoi = lyDo;
                toChucDangThuHoi.ngayThuHoi = thoiGianStr;
                toChucDangThuHoi.nguoiThuHoi = 'dav_inspector_01';

                modalThuHoi.classList.add('hidden');

                alert(`Thu hồi quyền hạn thành công tổ chức [${toChucDangThuHoi.tenToChuc}]!\n- Vai trò của tổ chức trên Smart Contract đã bị thu hồi.\n- Các tài khoản của tổ chức vẫn có thể đăng nhập để xem lý do nhưng không thể thực hiện thêm chức năng nghiệp vụ nào.`);
                renderBangQuyenHan();
            } else {
                // Thất bại: Giữ nguyên trạng thái tổ chức, cho phép thử lại
                manHinhDangThuHoiBlockchain.classList.add('hidden');
                manHinhLoiThuHoiBlockchain.classList.remove('hidden');
            }
        }, 1200);
    }

    nutXacNhanThuHoiOnChain.addEventListener('click', thucHienThuHoiOnChain);
    nutThuLaiThuHoi.addEventListener('click', thucHienThuHoiOnChain);

    // ══ 5. GẮN SỰ KIỆN CHO CÁC NÚT TRÊN BẢNG ══
    function ganSuKienNutBang() {
        document.querySelectorAll('.btn-chi-tiet-tochuc').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = parseInt(this.getAttribute('data-id'));
                moModalChiTietToChuc(id);
            });
        });

        document.querySelectorAll('.btn-mo-thuhoi-modal').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = parseInt(this.getAttribute('data-id'));
                moModalThuHoiQuyen(id);
            });
        });
    }

    // ══ 6. LẮNG NGHE TÌM KIẾM & BỘ LỌC ══
    oTimKiem.addEventListener('input', () => { trangHienTai = 1; renderBangQuyenHan(); });
    locLoaiHinh.addEventListener('change', () => { trangHienTai = 1; renderBangQuyenHan(); });
    locTrangThai.addEventListener('change', () => { trangHienTai = 1; renderBangQuyenHan(); });
    locHanPhep.addEventListener('change', () => { trangHienTai = 1; renderBangQuyenHan(); });

    renderBangQuyenHan();
});
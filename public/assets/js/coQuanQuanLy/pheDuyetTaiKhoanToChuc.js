document.addEventListener('DOMContentLoaded', function () {
    const ITEMS_PER_PAGE = 4;
    let trangHienTai = 1;

    // Tài khoản Cơ quan quản lý đang đăng nhập
    const TAI_KHOAN_CQQL_DANG_NHAP = 'dav_inspector_01';

    // Helper: Tính số ngày còn lại đến khi hết hạn giấy phép
    function tinhSoNgayConLaiPhep(ngayHetHanStr) {
        if (!ngayHetHanStr) return 999;
        const [d, m, y] = ngayHetHanStr.split('/').map(Number);
        const hetHan = new Date(y, m - 1, d);
        const homNay = new Date(2026, 9, 10); // Mốc ngày tham chiếu 10/10/2026
        const diffTime = hetHan.getTime() - homNay.getTime();
        return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    }

    // ══ 1. CSDL MÔ PHỎNG (toChuc, yeuCauDangKy, hoSoDinhKemDangKy) ══
    let danhSachYeuCau = [
        {
            id: 'YC-2026-001',
            tenToChuc: 'Nhà Thuốc PharmaCare Cần Thơ',
            loaiHinh: 'NHA_THUOC',
            maSoThue: '1801689988',
            soGiayPhep: 'GPP-CT-2026/089',
            coQuanCap: 'Sở Y Tế Thành Phố Cần Thơ',
            ngayCap: '15/01/2026',
            ngayHetHan: '15/01/2029',
            diaChi: 'Số 256, Đường 30 Tháng 4, Phường Hưng Lợi, Quận Ninh Kiều, Cần Thơ',
            soDienThoai: '0292.3838.999',
            tenDangNhap: 'pharmacare_ct_pharmacy',
            ngayNopHienTai: '01/02/2026 14:00',
            trangThai: 'CHO_XU_LY',
            lichSuNop: [
                {
                    lan: 1,
                    ngayNop: '20/01/2026 09:30',
                    trangThai: 'TU_CHOI',
                    nguoiXuLy: 'dav_inspector_02',
                    lyDoTuChoi: 'Giấy chứng nhận GPP đính kèm đã hết hạn hiệu lực, bản chụp bị mờ không rõ số quyết định cấp phép.'
                },
                {
                    lan: 2,
                    ngayNop: '01/02/2026 14:00',
                    trangThai: 'CHO_XU_LY',
                    nguoiXuLy: null,
                    lyDoTuChoi: null
                }
            ],
            hoSoDinhKem: [
                { id: 'TEP-01', ten: 'Giay_Chung_Nhan_Dang_Ky_Kinh_Doanh.pdf', loai: 'pdf' },
                { id: 'TEP-02', ten: 'Chung_Chi_Hanh_Nghe_Duoc_GPP_Moi.pdf', loai: 'pdf' },
                { id: 'TEP-03', ten: 'Anh_Chup_Bien_Hieu_Va_Quay_Thuoc.jpg', loai: 'image' }
            ]
        },
        {
            id: 'YC-2026-002',
            tenToChuc: 'Công ty Cổ phần Dược phẩm CPC1 Hà Nội',
            loaiHinh: 'NHA_PHAN_PHOI',
            maSoThue: '0103984512',
            soGiayPhep: 'GDP-HN-2025/112',
            coQuanCap: 'Bộ Y Tế - Cục Quản Lý Dược',
            ngayCap: '10/06/2025',
            ngayHetHan: '10/11/2026', // Dưới 90 ngày (sắp hết hạn)
            diaChi: 'Cụm Công Nghiệp Hà Bình Phương, Huyện Thường Tín, Hà Nội',
            soDienThoai: '0243.856.7899',
            tenDangNhap: 'cpc1_hanoi_dist',
            ngayNopHienTai: '03/02/2026 09:15',
            trangThai: 'CHO_XU_LY',
            lichSuNop: [
                {
                    lan: 1,
                    ngayNop: '03/02/2026 09:15',
                    trangThai: 'CHO_XU_LY',
                    nguoiXuLy: null,
                    lyDoTuChoi: null
                }
            ],
            hoSoDinhKem: [
                { id: 'TEP-04', ten: 'Giay_Phep_Phan_Phoi_Thuoc_GDP.pdf', loai: 'pdf' },
                { id: 'TEP-05', ten: 'Dang_Ky_Kinh_Doanh_Doanh_Nghiep.pdf', loai: 'pdf' }
            ]
        },
        {
            id: 'YC-2026-003',
            tenToChuc: 'Công ty CP Dược Phẩm Medipharco',
            loaiHinh: 'NHA_SAN_XUAT',
            maSoThue: '3300101568',
            soGiayPhep: 'GMP-WHO-2024/045',
            coQuanCap: 'Bộ Y Tế - Cục Quản Lý Dược',
            ngayCap: '12/03/2024',
            ngayHetHan: '12/03/2027',
            diaChi: 'Số 170 Đường Nguyễn Huệ, Thành Phố Huế, Thừa Thiên Huế',
            soDienThoai: '0234.382.3025',
            tenDangNhap: 'medipharco_nsx',
            ngayNopHienTai: '10/01/2026 08:30',
            trangThai: 'DA_DUYET',
            lichSuNop: [
                {
                    lan: 1,
                    ngayNop: '10/01/2026 08:30',
                    trangThai: 'DA_DUYET',
                    nguoiXuLy: 'dav_inspector_01',
                    lyDoTuChoi: null
                }
            ],
            hoSoDinhKem: [
                { id: 'TEP-06', ten: 'Giay_Chung_Nhan_GMP_WHO_Nha_May.pdf', loai: 'pdf' },
                { id: 'TEP-07', ten: 'Ho_So_Phap_Ly_To_Chuc_Medipharco.pdf', loai: 'pdf' }
            ]
        },
        {
            id: 'YC-2026-004',
            tenToChuc: 'Công ty TNHH Dược Thảo Á Châu',
            loaiHinh: 'NHA_PHAN_PHOI',
            maSoThue: '0314999888',
            soGiayPhep: 'GDP-HCM-2023/001',
            coQuanCap: 'Sở Y Tế TP. Hồ Chí Minh',
            ngayCap: '05/01/2023',
            ngayHetHan: '05/01/2026', // Đã hết hạn
            diaChi: 'Số 120 Đường Nguyễn Thị Minh Khai, Quận 3, TP. Hồ Chí Minh',
            soDienThoai: '0283.930.1122',
            tenDangNhap: 'achau_pharma_dist',
            ngayNopHienTai: '28/01/2026 16:30',
            trangThai: 'TU_CHOI',
            lichSuNop: [
                {
                    lan: 1,
                    ngayNop: '28/01/2026 16:30',
                    trangThai: 'TU_CHOI',
                    nguoiXuLy: 'dav_inspector_01',
                    lyDoTuChoi: 'Mã số thuế không tồn tại trên Cổng thông tin Đăng ký doanh nghiệp quốc gia. Giấy phép GDP đã quá hạn hiệu lực tính đến thời điểm thẩm định.'
                }
            ],
            hoSoDinhKem: [
                { id: 'TEP-08', ten: 'Ban_Chup_Giay_Phep_Het_Han.pdf', loai: 'pdf' }
            ]
        }
    ];

    const mapLoaiHinh = {
        'NHA_SAN_XUAT': { ten: 'Nhà sản xuất', class: 'bg-emerald-50 text-emerald-800 border-emerald-300' },
        'NHA_PHAN_PHOI': { ten: 'Nhà phân phối', class: 'bg-blue-50 text-blue-800 border-blue-300' },
        'NHA_THUOC': { ten: 'Nhà thuốc', class: 'bg-cyan-50 text-cyan-800 border-cyan-300' }
    };

    function taoBadgeTrangThai(status) {
        if (status === 'CHO_XU_LY') {
            return `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300"><i class="fa-solid fa-clock"></i> Chờ xử lý</span>`;
        } else if (status === 'DA_DUYET') {
            return `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300"><i class="fa-solid fa-circle-check"></i> Đã duyệt</span>`;
        } else {
            return `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-red-100 text-red-700 border border-red-300"><i class="fa-solid fa-circle-xmark"></i> Bị từ chối</span>`;
        }
    }

    // ══ 2. RENDER BẢNG DANH SÁCH & PHÂN TRANG (MẶC ĐỊNH CHỜ XỬ LÝ) ══
    const thanBang = document.getElementById('thanBangYeuCau');
    const oTimKiem = document.getElementById('oTimKiemToChuc');
    const locLoaiToChuc = document.getElementById('locLoaiToChuc');
    const locTrangThai = document.getElementById('locTrangThaiYeuCau');
    const soLuongBadge = document.getElementById('soLuongYeuCauBadge');
    const thongTinPhanTrang = document.getElementById('thongTinPhanTrang');
    const cumNutPhanTrang = document.getElementById('cumNutPhanTrang');

    function renderBangYeuCau() {
        const tuKhoa = oTimKiem.value.trim().toLowerCase();
        const loaiChon = locLoaiToChuc.value;
        const trangThaiChon = locTrangThai.value;

        const danhSachLoc = danhSachYeuCau.filter(item => {
            const matchTuKhoa = item.tenToChuc.toLowerCase().includes(tuKhoa) || item.maSoThue.includes(tuKhoa);
            const matchLoai = (loaiChon === 'TAT_CA') || (item.loaiHinh === loaiChon);
            const matchTrangThai = (trangThaiChon === 'TAT_CA') || (item.trangThai === trangThaiChon);
            return matchTuKhoa && matchLoai && matchTrangThai;
        });

        soLuongBadge.textContent = `Hiển thị: ${danhSachLoc.length} hồ sơ`;

        // Cập nhật số lượng đếm trên badge Sidebar
        const soLuongChoSidebar = danhSachYeuCau.filter(x => x.trangThai === 'CHO_XU_LY').length;
        const badgeSidebar = document.getElementById('badgeMenuChoDuyetToChuc');
        if (badgeSidebar) badgeSidebar.textContent = soLuongChoSidebar;

        const tongSoTrang = Math.ceil(danhSachLoc.length / ITEMS_PER_PAGE) || 1;
        if (trangHienTai > tongSoTrang) trangHienTai = tongSoTrang;

        const batDau = (trangHienTai - 1) * ITEMS_PER_PAGE;
        const ketThuc = batDau + ITEMS_PER_PAGE;
        const duLieuTrang = danhSachLoc.slice(batDau, ketThuc);

        thanBang.innerHTML = '';

        if (duLieuTrang.length === 0) {
            const msgRong = (trangThaiChon === 'CHO_XU_LY') 
                ? 'Không có hồ sơ nào đang chờ xử lý.' 
                : 'Không tìm thấy hồ sơ đăng ký nào phù hợp với bộ lọc.';
            thanBang.innerHTML = `<tr><td colspan="8" class="text-center py-8 text-slate-400 italic">${msgRong}</td></tr>`;
            thongTinPhanTrang.textContent = "Không có bản ghi nào";
            cumNutPhanTrang.innerHTML = '';
            return;
        }

        thongTinPhanTrang.textContent = `Đang hiển thị ${batDau + 1} đến ${Math.min(ketThuc, danhSachLoc.length)} trên tổng số ${danhSachLoc.length} hồ sơ`;

        duLieuTrang.forEach(item => {
            const lh = mapLoaiHinh[item.loaiHinh] || { ten: item.loaiHinh, class: 'bg-slate-100 text-slate-700 border-slate-200' };
            const soLanNop = item.lichSuNop ? item.lichSuNop.length : 1;

            const tr = document.createElement('tr');
            tr.className = 'dong-yeu-cau transition-colors';
            tr.innerHTML = `
                <td class="py-3 px-3 font-semibold text-slate-900 leading-tight">
                    <span class="block font-bold text-slate-900">${item.tenToChuc}</span>
                    <span class="font-mono text-[10px] text-slate-400">ID: ${item.id}</span>
                </td>
                <td class="py-3 px-2.5 text-center whitespace-nowrap">
                    <span class="badge-chuan px-2 py-0.5 rounded-md text-[11px] font-bold border ${lh.class}">
                        ${lh.ten}
                    </span>
                </td>
                <td class="py-3 px-3 font-mono font-bold text-slate-800 whitespace-nowrap">${item.maSoThue}</td>
                <td class="py-3 px-3 font-mono font-semibold text-indigo-900 whitespace-nowrap">${item.soGiayPhep}</td>
                <td class="py-3 px-2.5 text-center font-mono text-[11px] text-slate-600 whitespace-nowrap">${item.ngayNopHienTai}</td>
                <td class="py-3 px-2.5 text-center whitespace-nowrap">
                    <span class="font-mono font-bold ${soLanNop > 1 ? 'text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200' : 'text-slate-600'}">
                        Lần ${soLanNop}
                    </span>
                </td>
                <td class="py-3 px-2.5 text-center whitespace-nowrap">
                    ${taoBadgeTrangThai(item.trangThai)}
                </td>
                <td class="py-3 px-3 text-center whitespace-nowrap">
                    <button type="button" class="btn-xem-ho-so px-3 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-800 font-bold text-xs transition-all border border-indigo-200 shadow-2xs flex items-center justify-center gap-1.5 mx-auto" data-id="${item.id}">
                        <i class="fa-solid fa-folder-open text-indigo-700 text-[11px]"></i> Xem hồ sơ
                    </button>
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
        btnPrev.addEventListener('click', () => { if (trangHienTai > 1) { trangHienTai--; renderBangYeuCau(); } });
        cumNutPhanTrang.appendChild(btnPrev);

        for (let i = 1; i <= tongSoTrang; i++) {
            const btnPage = document.createElement('button');
            btnPage.className = `w-7 h-7 rounded-lg text-xs font-bold transition-all ${trangHienTai === i ? 'bg-indigo-700 text-white shadow-2xs' : 'border border-slate-200 text-slate-700 hover:bg-slate-100'}`;
            btnPage.textContent = i;
            btnPage.addEventListener('click', () => { trangHienTai = i; renderBangYeuCau(); });
            cumNutPhanTrang.appendChild(btnPage);
        }

        const btnNext = document.createElement('button');
        btnNext.className = `px-2.5 py-1 rounded-lg border text-xs font-bold transition-all ${trangHienTai === tongSoTrang ? 'border-slate-200 text-slate-300 cursor-not-allowed' : 'border-slate-300 text-slate-700 hover:bg-slate-100'}`;
        btnNext.innerHTML = `<i class="fa-solid fa-angle-right"></i>`;
        btnNext.disabled = (trangHienTai === tongSoTrang);
        btnNext.addEventListener('click', () => { if (trangHienTai < tongSoTrang) { trangHienTai++; renderBangYeuCau(); } });
        cumNutPhanTrang.appendChild(btnNext);
    }

    // ══ 3. PANEL XEM HỒ SƠ CHI TIẾT (ĐÃ XÓA CHỮ PHỤ VÀ SỐ THỨ TỰ) ══
    const modalXemHoSo = document.getElementById('modalXemHoSo');
    const panelCumNutHanhDong = document.getElementById('panelCumNutHanhDong');
    const panelTheKetQuaDaXuLy = document.getElementById('panelTheKetQuaDaXuLy');
    let hoSoDangXem = null;

    function moPanelXemHoSo(id) {
        hoSoDangXem = danhSachYeuCau.find(x => x.id === id);
        if (!hoSoDangXem) return;

        const lh = mapLoaiHinh[hoSoDangXem.loaiHinh] || { ten: hoSoDangXem.loaiHinh, class: 'bg-slate-100 text-slate-700 border-slate-200' };
        const soNgayCon = tinhSoNgayConLaiPhep(hoSoDangXem.ngayHetHan);

        // Header Panel
        document.getElementById('panelTenToChucTitle').textContent = hoSoDangXem.tenToChuc;
        document.getElementById('panelTrangThaiBadge').innerHTML = taoBadgeTrangThai(hoSoDangXem.trangThai);

        // Khối: Thông tin pháp lý kê khai
        document.getElementById('panelTenToChuc').textContent = hoSoDangXem.tenToChuc;
        document.getElementById('panelLoaiHinh').innerHTML = `<span class="badge-chuan px-2 py-0.5 rounded text-[11px] font-bold border ${lh.class}">${lh.ten}</span>`;
        document.getElementById('panelTenDangNhap').textContent = hoSoDangXem.tenDangNhap;
        document.getElementById('panelMaSoThue').textContent = hoSoDangXem.maSoThue;
        document.getElementById('panelSoDienThoai').textContent = hoSoDangXem.soDienThoai;
        document.getElementById('panelSoGiayPhep').textContent = hoSoDangXem.soGiayPhep;
        document.getElementById('panelCoQuanCap').textContent = hoSoDangXem.coQuanCap;
        document.getElementById('panelNgayCap').textContent = hoSoDangXem.ngayCap;
        document.getElementById('panelNgayHetHan').textContent = hoSoDangXem.ngayHetHan;
        document.getElementById('panelDiaChi').textContent = hoSoDangXem.diaChi;

        // Cảnh báo hạn phép
        const badgePhep = document.getElementById('panelCanhBaoPhepBadge');
        if (soNgayCon <= 0) {
            badgePhep.className = "badge-chuan px-1.5 py-0.2 rounded text-[10px] font-bold bg-red-100 text-red-700 border border-red-300";
            badgePhep.innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i> Đã hết hạn`;
        } else if (soNgayCon <= 90) {
            badgePhep.className = "badge-chuan px-1.5 py-0.2 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300";
            badgePhep.innerHTML = `<i class="fa-solid fa-clock"></i> Còn ${soNgayCon} ngày`;
        } else {
            badgePhep.className = "hidden";
        }

        // Khối: Hồ sơ minh chứng đính kèm (ĐÃ BỎ DÒNG CHỮ PHỤ VÀ DUNG LƯỢNG)
        const containerTep = document.getElementById('panelDanhSachTepMinhChung');
        containerTep.innerHTML = '';
        document.getElementById('panelSoLuongTepMinhChung').textContent = `${hoSoDangXem.hoSoDinhKem.length} tệp tài liệu`;

        hoSoDangXem.hoSoDinhKem.forEach(tep => {
            const cardTep = document.createElement('div');
            cardTep.className = 'p-2.5 bg-white rounded-xl border border-indigo-200/80 hover:border-indigo-400 transition-all flex items-center justify-between gap-2 shadow-2xs';
            
            const isPdf = tep.loai === 'pdf';
            const icon = isPdf ? 'fa-solid fa-file-pdf text-red-500' : 'fa-solid fa-file-image text-emerald-600';

            cardTep.innerHTML = `
                <div class="flex items-center gap-2.5 truncate">
                    <div class="w-8 h-8 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-center shrink-0 text-base">
                        <i class="${icon}"></i>
                    </div>
                    <span class="font-bold text-slate-800 truncate text-xs" title="${tep.ten}">${tep.ten}</span>
                </div>
                <button type="button" class="btn-xem-tep-bao-mat px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-800 rounded-lg font-bold text-[11px] shrink-0 transition-colors" data-tepid="${tep.id}" data-ten="${tep.ten}">
                    <i class="fa-solid fa-shield-halved text-[9px] mr-1"></i> Xem tệp
                </button>
            `;
            containerTep.appendChild(cardTep);
        });

        document.querySelectorAll('.btn-xem-tep-bao-mat').forEach(btn => {
            btn.addEventListener('click', function () {
                const tepId = this.getAttribute('data-tepid');
                const tenTep = this.getAttribute('data-ten');
                alert(`[XÁC THỰC BẢO MẬT]\nĐang mở tệp: "${tenTep}" qua cơ chế phân quyền Cục Quản lý Dược (Endpoint: xemMinhChung.php?tepId=${tepId}).`);
            });
        });

        // Khối: Lịch sử các lần nộp (ĐÃ BỎ DÒNG CHỮ "(Tổ chức đã nộp 2 lần)")
        const containerLichSu = document.getElementById('panelDanhSachLichSuNop');
        containerLichSu.innerHTML = '';

        if (!hoSoDangXem.lichSuNop || hoSoDangXem.lichSuNop.length <= 1) {
            containerLichSu.innerHTML = `
                <div class="p-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-500 italic text-center">
                    <i class="fa-solid fa-circle-info mr-1"></i> Đây là lần nộp đầu tiên của tổ chức.
                </div>
            `;
        } else {
            hoSoDangXem.lichSuNop.forEach(item => {
                const cardLS = document.createElement('div');
                cardLS.className = 'p-2 rounded-lg border border-slate-200 bg-slate-50 space-y-1 text-xs';

                let badge = '';
                if (item.trangThai === 'TU_CHOI') {
                    badge = `<span class="badge-chuan px-2 py-0.2 rounded text-[10px] font-bold bg-red-100 text-red-700">Lần ${item.lan}: Bị từ chối</span>`;
                } else if (item.trangThai === 'DA_DUYET') {
                    badge = `<span class="badge-chuan px-2 py-0.2 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">Lần ${item.lan}: Đã duyệt</span>`;
                } else {
                    badge = `<span class="badge-chuan px-2 py-0.2 rounded text-[10px] font-bold bg-amber-100 text-amber-800">Lần ${item.lan}: Chờ xử lý</span>`;
                }

                cardLS.innerHTML = `
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            ${badge}
                            <span class="font-mono text-[11px] text-slate-600">${item.ngayNop}</span>
                        </div>
                        <span class="text-[10px] text-slate-500">${item.nguoiXuLy ? `Người xử lý: <strong class="font-mono text-slate-700">${item.nguoiXuLy}</strong>` : ''}</span>
                    </div>
                    ${item.lyDoTuChoi ? `<div class="p-1.5 bg-red-50 text-red-800 rounded border border-red-200 text-[11px]"><strong>Lý do từ chối:</strong> ${item.lyDoTuChoi}</div>` : ''}
                `;
                containerLichSu.appendChild(cardLS);
            });
        }

        // Kiểm soát chế độ xem đã xử lý vs chờ xử lý
        if (hoSoDangXem.trangThai === 'CHO_XU_LY') {
            panelCumNutHanhDong.classList.remove('hidden');
            panelTheKetQuaDaXuLy.classList.add('hidden');
        } else {
            panelCumNutHanhDong.classList.add('hidden');
            panelTheKetQuaDaXuLy.classList.remove('hidden');

            const lanCuoi = hoSoDangXem.lichSuNop[hoSoDangXem.lichSuNop.length - 1];
            if (hoSoDangXem.trangThai === 'DA_DUYET') {
                panelTheKetQuaDaXuLy.className = "p-3 rounded-xl border border-emerald-300 bg-emerald-50 text-emerald-950 text-xs shrink-0 flex items-center justify-between";
                panelTheKetQuaDaXuLy.innerHTML = `
                    <div>
                        <span class="font-bold text-emerald-800 flex items-center gap-1.5"><i class="fa-solid fa-circle-check"></i> Hồ sơ đã được phê duyệt & Cấp vai trò on-chain thành công</span>
                        <div class="text-[11px] text-slate-600 mt-0.5">Người xử lý: <strong class="font-mono text-slate-800">${lanCuoi.nguoiXuLy || TAI_KHOAN_CQQL_DANG_NHAP}</strong> | Thời điểm: ${hoSoDangXem.ngayNopHienTai}</div>
                    </div>
                    <span class="badge-chuan px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-200 text-emerald-900 border border-emerald-300">Hoàn tất</span>
                `;
            } else {
                panelTheKetQuaDaXuLy.className = "p-3 rounded-xl border border-red-300 bg-red-50 text-red-950 text-xs shrink-0 space-y-1";
                panelTheKetQuaDaXuLy.innerHTML = `
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-red-700 flex items-center gap-1.5"><i class="fa-solid fa-circle-xmark"></i> Hồ sơ đã bị từ chối phê duyệt</span>
                        <span class="text-[10px] text-slate-500">Người xử lý: <strong class="font-mono text-slate-800">${lanCuoi.nguoiXuLy || TAI_KHOAN_CQQL_DANG_NHAP}</strong></span>
                    </div>
                    <div class="text-[11px] text-red-900"><strong>Lý do từ chối:</strong> ${lanCuoi.lyDoTuChoi || 'Không đạt tiêu chuẩn hồ sơ'}</div>
                `;
            }
        }

        modalXemHoSo.classList.remove('hidden');
    }

    function ganSuKienNutBang() {
        document.querySelectorAll('.btn-xem-ho-so').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                moPanelXemHoSo(id);
            });
        });
    }

    document.querySelectorAll('.dong-modal-hoso').forEach(btn => {
        btn.addEventListener('click', () => modalXemHoSo.classList.add('hidden'));
    });

    // ══ 4. XỬ LÝ MODAL PHÊ DUYỆT ══
    const modalDuyet = document.getElementById('modalXacNhanDuyet');
    const manHinhFormDuyet = document.getElementById('manHinhFormDuyet');
    const manHinhDangGhiBlockchain = document.getElementById('manHinhDangGhiBlockchain');
    const manHinhLoiBlockchain = document.getElementById('manHinhLoiBlockchain');
    const loiBlockchainChiTietText = document.getElementById('loiBlockchainChiTietText');
    const nutMoModalDuyet = document.getElementById('panelNutMoModalDuyet');
    const nutXacNhanDuyetGhiBlockchain = document.getElementById('nutXacNhanDuyetGhiBlockchain');
    const nutThuLaiGhiBlockchain = document.getElementById('nutThuLaiGhiBlockchain');
    const modalDuyetKhungCanhBaoPhep = document.getElementById('modalDuyetKhungCanhBaoPhep');

    nutMoModalDuyet.addEventListener('click', function () {
        if (!hoSoDangXem) return;

        // Xử lý đồng thời (Optimistic check)
        if (hoSoDangXem.trangThai !== 'CHO_XU_LY') {
            alert("Hồ sơ này đã được xử lý bởi chuyên viên khác. Danh sách sẽ được tải lại!");
            modalXemHoSo.classList.add('hidden');
            renderBangYeuCau();
            return;
        }

        const lh = mapLoaiHinh[hoSoDangXem.loaiHinh]?.ten || hoSoDangXem.loaiHinh;
        const soNgayCon = tinhSoNgayConLaiPhep(hoSoDangXem.ngayHetHan);

        document.getElementById('duyetModalTenToChuc').textContent = hoSoDangXem.tenToChuc;
        document.getElementById('duyetModalMST').textContent = hoSoDangXem.maSoThue;
        document.getElementById('duyetModalVaiTro').textContent = lh;
        document.getElementById('duyetModalTenDangNhap').textContent = hoSoDangXem.tenDangNhap;

        modalDuyetKhungCanhBaoPhep.className = "hidden";
        let noiDungCanhBaoHtml = '';

        if (soNgayCon <= 0) {
            noiDungCanhBaoHtml += `<div>• <strong>Giấy phép đã hết hạn hiệu lực:</strong> Ngày hết hạn là ${hoSoDangXem.ngayHetHan}. Cân nhắc yêu cầu doanh nghiệp bổ sung giấy phép mới trước khi duyệt!</div>`;
        } else if (soNgayCon <= 90) {
            noiDungCanhBaoHtml += `<div>• <strong>Giấy phép sắp hết hạn (dưới 90 ngày):</strong> Chỉ còn ${soNgayCon} ngày hiệu lực (${hoSoDangXem.ngayHetHan}).</div>`;
        }

        const trungMST = danhSachYeuCau.some(x => x.id !== hoSoDangXem.id && x.maSoThue === hoSoDangXem.maSoThue && x.trangThai === 'DA_DUYET');
        if (trungMST) {
            noiDungCanhBaoHtml += `<div>• <strong>Trùng lặp mã số thuế:</strong> MST ${hoSoDangXem.maSoThue} đã được sử dụng bởi một tổ chức đã duyệt khác trên hệ thống!</div>`;
        }

        if (noiDungCanhBaoHtml) {
            modalDuyetKhungCanhBaoPhep.className = "p-3 rounded-xl border border-amber-300 bg-amber-50 text-amber-950 text-[11px] leading-relaxed space-y-1 block";
            modalDuyetKhungCanhBaoPhep.innerHTML = `
                <div class="font-bold flex items-center gap-1.5 text-amber-800"><i class="fa-solid fa-triangle-exclamation"></i> CẢNH BÁO PHÁP LÝ TỰ ĐỘNG:</div>
                ${noiDungCanhBaoHtml}
            `;
        }

        manHinhFormDuyet.classList.remove('hidden');
        manHinhDangGhiBlockchain.classList.add('hidden');
        manHinhLoiBlockchain.classList.add('hidden');
        modalDuyet.classList.remove('hidden');
    });

    document.querySelectorAll('.dong-modal-duyet').forEach(btn => {
        btn.addEventListener('click', () => modalDuyet.classList.add('hidden'));
    });

    function thucHienGhiBlockchainVaPheDuyet() {
        manHinhFormDuyet.classList.add('hidden');
        manHinhDangGhiBlockchain.classList.remove('hidden');
        manHinhLoiBlockchain.classList.add('hidden');

        setTimeout(() => {
            const thanhCongOnChain = true;

            if (thanhCongOnChain) {
                hoSoDangXem.trangThai = 'DA_DUYET';
                const lanCuoi = hoSoDangXem.lichSuNop[hoSoDangXem.lichSuNop.length - 1];
                lanCuoi.trangThai = 'DA_DUYET';
                lanCuoi.nguoiXuLy = TAI_KHOAN_CQQL_DANG_NHAP;

                modalDuyet.classList.add('hidden');
                modalXemHoSo.classList.add('hidden');

                alert(`Phê duyệt thành công tổ chức [${hoSoDangXem.tenToChuc}]!\n- Đã khởi tạo ví blockchain thành công.\n- Đã gọi Smart Contract cấp vai trò on-chain thành công.\n- Tài khoản [${hoSoDangXem.tenDangNhap}] đã chuyển sang trạng thái Hoạt động.`);
                renderBangYeuCau();
            } else {
                manHinhDangGhiBlockchain.classList.add('hidden');
                manHinhLoiBlockchain.classList.remove('hidden');
                loiBlockchainChiTietText.textContent = "Không thể kết nối RPC Node Polygon Amoy hoặc giao dịch hết gas. Trạng thái hồ sơ được giữ nguyên.";
            }
        }, 1200);
    }

    nutXacNhanDuyetGhiBlockchain.addEventListener('click', thucHienGhiBlockchainVaPheDuyet);
    nutThuLaiGhiBlockchain.addEventListener('click', thucHienGhiBlockchainVaPheDuyet);

    // ══ 5. XỬ LÝ MODAL TỪ CHỐI ══
    const modalTuChoi = document.getElementById('modalTuChoiHoSo');
    const nutMoModalTuChoi = document.getElementById('panelNutMoModalTuChoi');
    const nhapLyDoTuChoi = document.getElementById('nhapLyDoTuChoiToChuc');
    const demKyTuTuChoi = document.getElementById('demKyTuTuChoi');
    const nutXacNhanTuChoiOffChain = document.getElementById('nutXacNhanTuChoiOffChain');

    nutMoModalTuChoi.addEventListener('click', function () {
        if (!hoSoDangXem) return;

        if (hoSoDangXem.trangThai !== 'CHO_XU_LY') {
            alert("Hồ sơ này đã được xử lý bởi chuyên viên khác. Danh sách sẽ được tải lại!");
            modalXemHoSo.classList.add('hidden');
            renderBangYeuCau();
            return;
        }

        document.getElementById('tuChoiModalTenToChuc').textContent = hoSoDangXem.tenToChuc;
        document.getElementById('tuChoiModalMST').textContent = hoSoDangXem.maSoThue;
        nhapLyDoTuChoi.value = '';
        demKyTuTuChoi.textContent = '0/500';
        capNhatTrangThaiNutTuChoi();

        modalTuChoi.classList.remove('hidden');
    });

    function capNhatTrangThaiNutTuChoi() {
        const len = nhapLyDoTuChoi.value.trim().length;
        demKyTuTuChoi.textContent = `${len}/500`;

        if (len > 0) {
            nutXacNhanTuChoiOffChain.disabled = false;
            nutXacNhanTuChoiOffChain.className = "px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl shadow-md transition-all flex items-center gap-1.5 cursor-pointer";
        } else {
            nutXacNhanTuChoiOffChain.disabled = true;
            nutXacNhanTuChoiOffChain.className = "px-6 py-2.5 bg-slate-200 text-slate-400 font-bold rounded-xl transition-all flex items-center gap-1.5 cursor-not-allowed shadow-none";
        }
    }

    nhapLyDoTuChoi.addEventListener('input', capNhatTrangThaiNutTuChoi);

    document.querySelectorAll('.dong-modal-tuchoi').forEach(btn => {
        btn.addEventListener('click', () => modalTuChoi.classList.add('hidden'));
    });

    nutXacNhanTuChoiOffChain.addEventListener('click', function () {
        const lyDo = nhapLyDoTuChoi.value.trim();
        if (!lyDo) return;

        hoSoDangXem.trangThai = 'TU_CHOI';
        
        const lanCuoi = hoSoDangXem.lichSuNop[hoSoDangXem.lichSuNop.length - 1];
        lanCuoi.trangThai = 'TU_CHOI';
        lanCuoi.nguoiXuLy = TAI_KHOAN_CQQL_DANG_NHAP;
        lanCuoi.lyDoTuChoi = lyDo;

        modalTuChoi.classList.add('hidden');
        modalXemHoSo.classList.add('hidden');

        alert(`Đã từ chối hồ sơ đăng ký của tổ chức [${hoSoDangXem.tenToChuc}].\nLý do từ chối đã được lưu vào CSDL và sẽ hiển thị cho tổ chức khi họ đăng nhập.`);
        renderBangYeuCau();
    });

    // ══ 6. LẮNG NGHE TÌM KIẾM & BỘ LỌC ══
    oTimKiem.addEventListener('input', () => { trangHienTai = 1; renderBangYeuCau(); });
    locLoaiToChuc.addEventListener('change', () => { trangHienTai = 1; renderBangYeuCau(); });
    locTrangThai.addEventListener('change', () => { trangHienTai = 1; renderBangYeuCau(); });

    renderBangYeuCau();
});
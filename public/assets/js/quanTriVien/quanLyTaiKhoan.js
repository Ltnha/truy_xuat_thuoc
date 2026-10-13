document.addEventListener('DOMContentLoaded', function () {
    const ITEMS_PER_PAGE = 4;
    let trangHienTai = 1;

    // TÀI KHOẢN ĐANG ĐĂNG NHẬP (Chính mình - không thể tự khóa)
    const TAI_KHOAN_DANG_NHAP_HIEN_TAI = 'sysadmin_master';

    // ══ 1. BẢNG ÁNH XẠ MÃ HÀNH ĐỘNG SANG TIẾNG VIỆT ══
    const mapHanhDongTiengViet = {
        'DANG_NHAP': 'Đăng nhập',
        'DANG_NHAP_THAT_BAI': 'Đăng nhập thất bại',
        'KHOA_TAI_KHOAN': 'Khóa tài khoản',
        'MO_KHOA_TAI_KHOAN': 'Mở khóa',
        'DOI_MAT_KHAU': 'Đổi mật khẩu',
        'GIAO_DICH_TU_CHOI': 'Giao dịch bị từ chối',
        'NGHIEP_VU_CHUOI': 'Giao dịch chuỗi cung ứng',
        'PHE_DUYET_SP': 'Phê duyệt sản phẩm',
        'LOI_HE_THONG': 'Lỗi hệ thống'
    };

    // ══ 2. DỮ LIỆU CSDL MÔ PHỎNG (taiKhoan, toChuc) ══
    // Đã đổi medipharco_admin -> medipharco_nsx, định dạng số dấu chấm, lưu vết khóa gần nhất
    let danhSachTaiKhoan = [
        {
            id: 1,
            tenDangNhap: 'medipharco_nsx',
            email: 'admin@medipharco.vn',
            vaiTro: 'NHA_SAN_XUAT',
            tenToChuc: 'Công ty CP Dược Phẩm Medipharco',
            loaiHinhToChuc: 'Doanh nghiệp sản xuất dược phẩm',
            trangThaiTK: 'ACTIVE',
            trangThaiToChuc: 'DA_DUYET',
            ngayTao: '10/01/2026 08:30',
            thongTinKhoaGanNhat: null
        },
        {
            id: 2,
            tenDangNhap: 'cpc1_hanoi_dist',
            email: 'contact@cpc1hn.com.vn',
            vaiTro: 'NHA_PHAN_PHOI',
            tenToChuc: 'Công ty Cổ phần Dược phẩm CPC1 Hà Nội',
            loaiHinhToChuc: 'Tổng đại lý phân phối dược phẩm',
            trangThaiTK: 'LOCKED',
            trangThaiToChuc: 'DA_DUYET',
            ngayTao: '15/01/2026 10:15',
            thongTinKhoaGanNhat: {
                lyDo: 'Phát hiện 5 lần nhập sai mật khẩu liên tiếp từ địa chỉ IP lạ (118.69.182.44).',
                nguoiKhoa: 'sysadmin_master',
                thoiGian: '08/02/2026 11:20'
            }
        },
        {
            id: 3,
            tenDangNhap: 'pharmacare_ct_pharmacy',
            email: 'pharmacare_ct@gmail.com',
            vaiTro: 'NHA_THUOC',
            tenToChuc: 'Nhà Thuốc PharmaCare Cần Thơ',
            loaiHinhToChuc: 'Cơ sở bán lẻ thuốc GPP',
            trangThaiTK: 'ACTIVE',
            trangThaiToChuc: 'DA_DUYET',
            ngayTao: '20/01/2026 14:00',
            thongTinKhoaGanNhat: null
        },
        {
            id: 4,
            tenDangNhap: 'dav_inspector_01',
            email: 'inspector01@dav.gov.vn',
            vaiTro: 'CO_QUAN_QUAN_LY',
            tenToChuc: null,
            loaiHinhToChuc: 'Cơ quan quản lý nhà nước về Dược',
            trangThaiTK: 'ACTIVE',
            trangThaiToChuc: 'KHONG_AP_DUNG',
            ngayTao: '05/01/2026 09:00',
            thongTinKhoaGanNhat: null
        },
        {
            id: 5,
            tenDangNhap: 'sysadmin_master',
            email: 'root@pharmacare.vn',
            vaiTro: 'QUAN_TRI_VIEN',
            tenToChuc: null,
            loaiHinhToChuc: 'Quản trị kỹ thuật hệ thống Off-chain',
            trangThaiTK: 'ACTIVE',
            trangThaiToChuc: 'KHONG_AP_DUNG',
            ngayTao: '01/01/2026 00:00',
            thongTinKhoaGanNhat: null
        },
        {
            id: 6,
            tenDangNhap: 'opv_pharma_revoked',
            email: 'qa@opvpharma.com',
            vaiTro: 'NHA_SAN_XUAT',
            tenToChuc: 'Công ty CP Dược Phẩm OPV',
            loaiHinhToChuc: 'Doanh nghiệp sản xuất dược phẩm',
            trangThaiTK: 'REVOKED',
            trangThaiToChuc: 'BI_THU_HOI',
            ngayTao: '18/01/2026 11:20',
            thongTinKhoaGanNhat: {
                lyDo: 'Doanh nghiệp bị đình chỉ giấy phép lưu hành theo quyết định xử phạt 102/QĐ-XPHC.',
                nguoiKhoa: 'sysadmin_master',
                thoiGian: '25/01/2026 09:00'
            }
        },
        {
            id: 7,
            tenDangNhap: 'vinapharm_pending_org',
            email: 'audit@vinapharm.com.vn',
            vaiTro: 'NHA_PHAN_PHOI',
            tenToChuc: 'Tổng Công ty Dược Việt Nam - Vinapharm',
            loaiHinhToChuc: 'Nhà phân phối bán buôn',
            trangThaiTK: 'PENDING',
            trangThaiToChuc: 'CHO_DUYET',
            ngayTao: '01/02/2026 16:40',
            thongTinKhoaGanNhat: null
        }
    ];

    // CSDL MÔ PHỎNG NHẬT KÝ HỆ THỐNG (Định dạng số dấu chấm: 10.000 hộp)
    let danhSachNhatKy = [
        { taiKhoan: 'cpc1_hanoi_dist', thoiGian: '10/02/2026 14:35', hanhDong: 'GIAO_DICH_TU_CHOI', chiTiet: 'Thất bại transferBatch(): Xuất 6.000 hộp vượt quá khả dụng (4.000 hộp)' },
        { taiKhoan: 'sysadmin_master', thoiGian: '10/02/2026 11:20', hanhDong: 'KHOA_TAI_KHOAN', chiTiet: 'Khóa tài khoản [cpc1_hanoi_dist]: Phát hiện 5 lần nhập sai mật khẩu liên tiếp từ IP lạ' },
        { taiKhoan: 'medipharco_nsx', thoiGian: '10/02/2026 09:12', hanhDong: 'NGHIEP_VU_CHUOI', chiTiet: 'Khởi tạo lô thuốc mới LOT-PARA-2026 (10.000 hộp) thành công trên blockchain' },
        { taiKhoan: 'pharmacare_ct_pharmacy', thoiGian: '10/02/2026 08:30', hanhDong: 'DANG_NHAP', chiTiet: 'Đăng nhập thành công từ địa chỉ IP 14.169.88.21' },
        { taiKhoan: 'dav_inspector_01', thoiGian: '08/02/2026 16:45', hanhDong: 'NGHIEP_VU_CHUOI', chiTiet: 'Kích hoạt lệnh thu hồi lô LOT-AMOX-2025-09 trên toàn quốc' },
        { taiKhoan: 'medipharco_nsx', thoiGian: '08/02/2026 14:15', hanhDong: 'NGHIEP_VU_CHUOI', chiTiet: 'Chuyển giao 2.000 hộp lô LOT-PARA-2026 đến Tổng kho Vinapharm' },
        { taiKhoan: 'cpc1_hanoi_dist', thoiGian: '05/02/2026 10:00', hanhDong: 'NGHIEP_VU_CHUOI', chiTiet: 'Chuyển giao 1.500 hộp đến Nhà thuốc PharmaCare Cần Thơ' },
        { taiKhoan: 'dav_inspector_01', thoiGian: '02/02/2026 14:00', hanhDong: 'PHE_DUYET_SP', chiTiet: 'Phê duyệt lưu hành sản phẩm Siro Ho Astex-S (VD-22991-17)' },
        { taiKhoan: 'sysadmin_master', thoiGian: '01/02/2026 09:00', hanhDong: 'DANG_NHAP', chiTiet: 'Quản trị viên đăng nhập hệ thống kiểm tra Nhật ký hệ thống' },
        { taiKhoan: 'opv_pharma_revoked', thoiGian: '25/01/2026 17:00', hanhDong: 'DANG_NHAP_THAT_BAI', chiTiet: 'Đăng nhập thất bại: Tài khoản đã bị thu hồi' }
    ];

    // Cấu hình vai trò
    const mapVaiTro = {
        'QUAN_TRI_VIEN': { ten: 'Quản trị viên', class: 'bg-slate-100 text-slate-800 border-slate-300' },
        'CO_QUAN_QUAN_LY': { ten: 'Cơ quan quản lý', class: 'bg-indigo-50 text-indigo-800 border-indigo-300' },
        'NHA_SAN_XUAT': { ten: 'Nhà sản xuất', class: 'bg-emerald-50 text-emerald-800 border-emerald-300' },
        'NHA_PHAN_PHOI': { ten: 'Nhà phân phối', class: 'bg-blue-50 text-blue-800 border-blue-300' },
        'NHA_THUOC': { ten: 'Nhà thuốc', class: 'bg-cyan-50 text-cyan-800 border-cyan-300' }
    };

    function taoBadgeTrangThaiTK(status) {
        if (status === 'ACTIVE') {
            return `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300"><i class="fa-solid fa-circle-check"></i> Hoạt động</span>`;
        } else if (status === 'LOCKED') {
            return `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-red-100 text-red-700 border border-red-300"><i class="fa-solid fa-lock"></i> Đã khóa</span>`;
        } else if (status === 'PENDING') {
            return `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300"><i class="fa-solid fa-clock"></i> Chờ xác minh</span>`;
        } else {
            return `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-slate-200 text-slate-800 border border-slate-300"><i class="fa-solid fa-ban"></i> Bị thu hồi</span>`;
        }
    }

    function taoBadgeTrangThaiToChuc(status) {
        if (status === 'DA_DUYET') {
            return `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Đã duyệt</span>`;
        } else if (status === 'CHO_DUYET') {
            return `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Chờ duyệt</span>`;
        } else if (status === 'BI_TU_CHOI') {
            return `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-red-50 text-red-700 border border-red-200">Bị từ chối</span>`;
        } else if (status === 'BI_THU_HOI') {
            return `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">Bị thu hồi</span>`;
        } else {
            return `<span class="text-slate-400 font-mono font-bold text-center block">—</span>`;
        }
    }

    // ══ 3. RENDER BẢNG TÀI KHOẢN (ĐÃ BỎ CỘT NGÀY TẠO, CẮT NGẮN EMAIL VÀ TỔ CHỨC) ══
    const thanBang = document.getElementById('thanBangTaiKhoan');
    const oTimKiem = document.getElementById('oTimKiemTaiKhoan');
    const locVaiTro = document.getElementById('locVaiTro');
    const locTrangThai = document.getElementById('locTrangThaiTK');
    const soLuongBadge = document.getElementById('soLuongTaiKhoanBadge');
    const thongTinPhanTrang = document.getElementById('thongTinPhanTrangTK');
    const cumNutPhanTrang = document.getElementById('cumNutPhanTrangTK');

    function renderBangTaiKhoan() {
        const tuKhoa = oTimKiem.value.trim().toLowerCase();
        const vaiTroChon = locVaiTro.value;
        const trangThaiChon = locTrangThai.value;

        const danhSachLoc = danhSachTaiKhoan.filter(item => {
            const tenTC = item.tenToChuc ? item.tenToChuc.toLowerCase() : '';
            const matchTuKhoa = item.tenDangNhap.toLowerCase().includes(tuKhoa) ||
                               item.email.toLowerCase().includes(tuKhoa) ||
                               tenTC.includes(tuKhoa);
            const matchVaiTro = (vaiTroChon === 'TAT_CA') || (item.vaiTro === vaiTroChon);
            const matchTrangThai = (trangThaiChon === 'TAT_CA') || (item.trangThaiTK === trangThaiChon);

            return matchTuKhoa && matchVaiTro && matchTrangThai;
        });

        soLuongBadge.textContent = `Hiển thị: ${danhSachLoc.length} tài khoản`;

        const tongSoTrang = Math.ceil(danhSachLoc.length / ITEMS_PER_PAGE) || 1;
        if (trangHienTai > tongSoTrang) trangHienTai = tongSoTrang;

        const batDau = (trangHienTai - 1) * ITEMS_PER_PAGE;
        const ketThuc = batDau + ITEMS_PER_PAGE;
        const duLieuTrang = danhSachLoc.slice(batDau, ketThuc);

        thanBang.innerHTML = '';

        if (duLieuTrang.length === 0) {
            thanBang.innerHTML = `<tr><td colspan="7" class="text-center py-8 text-slate-400 italic">Không tìm thấy tài khoản nào phù hợp với bộ lọc.</td></tr>`;
            thongTinPhanTrang.textContent = "Không có bản ghi nào";
            cumNutPhanTrang.innerHTML = '';
            return;
        }

        thongTinPhanTrang.textContent = `Đang hiển thị ${batDau + 1} đến ${Math.min(ketThuc, danhSachLoc.length)} trên tổng số ${danhSachLoc.length} tài khoản`;

        duLieuTrang.forEach(item => {
            const vt = mapVaiTro[item.vaiTro] || { ten: item.vaiTro, class: 'bg-slate-100 text-slate-700 border-slate-200' };
            const tenToChucHienThi = item.tenToChuc ? `<span class="truncate block max-w-[180px]" title="${item.tenToChuc}">${item.tenToChuc}</span>` : '<span class="text-slate-400 font-mono font-bold block text-center">—</span>';

            let nutThaoTacKhoaMoHtml = '';
            const laChinhMinh = (item.tenDangNhap === TAI_KHOAN_DANG_NHAP_HIEN_TAI);

            if (laChinhMinh) {
                nutThaoTacKhoaMoHtml = '';
            } else if (item.trangThaiTK === 'ACTIVE') {
                nutThaoTacKhoaMoHtml = `
                    <button type="button" class="btn-mo-khoa-modal px-2.5 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 font-bold text-xs transition-all flex items-center gap-1 border border-red-200 whitespace-nowrap shadow-2xs" data-id="${item.id}">
                        <i class="fa-solid fa-lock text-[10px]"></i> Khóa
                    </button>
                `;
            } else if (item.trangThaiTK === 'LOCKED') {
                nutThaoTacKhoaMoHtml = `
                    <button type="button" class="btn-mo-mokhoa-modal px-2.5 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold text-xs transition-all flex items-center gap-1 border border-emerald-300 whitespace-nowrap shadow-2xs" data-id="${item.id}">
                        <i class="fa-solid fa-lock-open text-[10px]"></i> Mở khóa
                    </button>
                `;
            } else if (item.trangThaiTK === 'PENDING') {
                nutThaoTacKhoaMoHtml = `
                    <span class="inline-block" title="Do Cơ quan quản lý xác minh">
                        <button type="button" disabled class="px-2 py-1.5 rounded-lg bg-slate-100 text-slate-400 font-medium text-[11px] cursor-not-allowed whitespace-nowrap">
                            Chờ duyệt
                        </button>
                    </span>
                `;
            } else if (item.trangThaiTK === 'REVOKED') {
                nutThaoTacKhoaMoHtml = `
                    <span class="inline-block" title="Do Cơ quan quản lý xử lý">
                        <button type="button" disabled class="px-2 py-1.5 rounded-lg bg-slate-100 text-slate-400 font-medium text-[11px] cursor-not-allowed whitespace-nowrap">
                            Bị thu hồi
                        </button>
                    </span>
                `;
            }

            const tr = document.createElement('tr');
            tr.className = 'dong-tai-khoan transition-colors';
            tr.innerHTML = `
                <td class="py-3 px-3 font-mono font-bold text-slate-900 whitespace-nowrap">
                    ${item.tenDangNhap} ${laChinhMinh ? '<span class="text-[10px] text-cyan-800 font-bold bg-cyan-50 border border-cyan-200 px-1 py-0.2 rounded ml-1">(Tôi)</span>' : ''}
                </td>
                
                <!-- Email cắt ngắn bằng truncate max-w-[130px], rê chuột hiện đầy đủ -->
                <td class="py-3 px-3 text-slate-600 whitespace-nowrap">
                    <span class="truncate block max-w-[130px] font-sans" title="${item.email}">${item.email}</span>
                </td>
                
                <td class="py-3 px-2.5 text-center whitespace-nowrap">
                    <span class="badge-chuan px-2 py-0.5 rounded-md text-[11px] font-bold border ${vt.class}">
                        ${vt.ten}
                    </span>
                </td>

                <!-- Tổ chức thu hẹp max-w-[180px], không vỡ 3-4 dòng -->
                <td class="py-3 px-3 font-semibold text-slate-800 whitespace-nowrap">${tenToChucHienThi}</td>
                
                <td class="py-3 px-2.5 text-center whitespace-nowrap">
                    ${taoBadgeTrangThaiTK(item.trangThaiTK)}
                </td>

                <td class="py-3 px-2.5 text-center whitespace-nowrap">
                    ${taoBadgeTrangThaiToChuc(item.trangThaiToChuc)}
                </td>

                <td class="py-3 px-3 text-center whitespace-nowrap">
                    <div class="inline-flex items-center justify-center gap-1.5">
                        ${nutThaoTacKhoaMoHtml}
                        <button type="button" class="btn-chi-tiet-tk px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-all flex items-center gap-1 border border-slate-300 shadow-2xs whitespace-nowrap" data-id="${item.id}">
                            <i class="fa-solid fa-eye text-slate-600 text-[10px]"></i> Chi tiết
                        </button>
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
        btnPrev.addEventListener('click', () => { if (trangHienTai > 1) { trangHienTai--; renderBangTaiKhoan(); } });
        cumNutPhanTrang.appendChild(btnPrev);

        for (let i = 1; i <= tongSoTrang; i++) {
            const btnPage = document.createElement('button');
            btnPage.className = `w-7 h-7 rounded-lg text-xs font-bold transition-all ${trangHienTai === i ? 'bg-slate-800 text-white shadow-2xs' : 'border border-slate-200 text-slate-700 hover:bg-slate-100'}`;
            btnPage.textContent = i;
            btnPage.addEventListener('click', () => { trangHienTai = i; renderBangTaiKhoan(); });
            cumNutPhanTrang.appendChild(btnPage);
        }

        const btnNext = document.createElement('button');
        btnNext.className = `px-2.5 py-1 rounded-lg border text-xs font-bold transition-all ${trangHienTai === tongSoTrang ? 'border-slate-200 text-slate-300 cursor-not-allowed' : 'border-slate-300 text-slate-700 hover:bg-slate-100'}`;
        btnNext.innerHTML = `<i class="fa-solid fa-angle-right"></i>`;
        btnNext.disabled = (trangHienTai === tongSoTrang);
        btnNext.addEventListener('click', () => { if (trangHienTai < tongSoTrang) { trangHienTai++; renderBangTaiKhoan(); } });
        cumNutPhanTrang.appendChild(btnNext);
    }

    // ══ 4. XỬ LÝ MODAL KHÓA TÀI KHOẢN (KIỂM TRA DỮ LIỆU & RÀNG BUỘC CQQL) ══
    const modalKhoa = document.getElementById('modalKhoaTaiKhoan');
    const khoaTenDangNhap = document.getElementById('khoaTenDangNhap');
    const khoaVaiTro = document.getElementById('khoaVaiTro');
    const khoaToChuc = document.getElementById('khoaToChuc');
    const canhBaoManhCQQL = document.getElementById('canhBaoManhCoQuanQuanLy');
    const canhBaoManhCQQLText = document.getElementById('canhBaoManhCoQuanQuanLyText');
    const nhapLyDoKhoa = document.getElementById('nhapLyDoKhoa');
    const demKyTuKhoa = document.getElementById('demKyTuKhoa');
    const nutXacNhanKhoa = document.getElementById('nutXacNhanKhoa');
    let taiKhoanDangKhoa = null;

    function moModalKhoa(id) {
        taiKhoanDangKhoa = danhSachTaiKhoan.find(x => x.id === id);
        if (!taiKhoanDangKhoa) return;

        khoaTenDangNhap.textContent = taiKhoanDangKhoa.tenDangNhap;
        khoaVaiTro.textContent = mapVaiTro[taiKhoanDangKhoa.vaiTro]?.ten || taiKhoanDangKhoa.vaiTro;
        khoaToChuc.textContent = taiKhoanDangKhoa.tenToChuc || 'Không thuộc tổ chức doanh nghiệp (Quản lý cấp cao)';
        nhapLyDoKhoa.value = '';
        demKyTuKhoa.textContent = '0/500';
        capNhatTrangThaiNutKhoa();

        // Kiểm tra ràng buộc tài khoản Cơ quan quản lý
        if (taiKhoanDangKhoa.vaiTro === 'CO_QUAN_QUAN_LY') {
            canhBaoManhCQQL.classList.remove('hidden');
            // Đếm số tài khoản CQQL đang ACTIVE
            const soLuongCQQLActive = danhSachTaiKhoan.filter(x => x.vaiTro === 'CO_QUAN_QUAN_LY' && x.trangThaiTK === 'ACTIVE').length;
            if (soLuongCQQLActive <= 1) {
                // Chặn hẳn việc khóa
                canhBaoManhCQQLText.innerHTML = `<strong>CHẶN KHÓA:</strong> Đây là tài khoản Cơ quan quản lý duy nhất đang hoạt động trên hệ thống! Bạn không thể khóa tài khoản này vì sẽ làm gián đoạn toàn bộ hoạt động kiểm soát thuốc của mạng lưới.`;
                nhapLyDoKhoa.disabled = true;
                nutXacNhanKhoa.disabled = true;
                nutXacNhanKhoa.className = "px-6 py-2.5 bg-slate-200 text-slate-400 font-bold rounded-xl transition-all flex items-center gap-1.5 cursor-not-allowed shadow-none";
            } else {
                canhBaoManhCQQLText.textContent = "Khóa tài khoản này sẽ chặn các thao tác duyệt, từ chối và thu hồi mà tài khoản đảm nhận!";
                nhapLyDoKhoa.disabled = false;
            }
        } else {
            canhBaoManhCQQL.classList.add('hidden');
            nhapLyDoKhoa.disabled = false;
        }

        modalKhoa.classList.remove('hidden');
    }

    function capNhatTrangThaiNutKhoa() {
        const len = nhapLyDoKhoa.value.trim().length;
        demKyTuKhoa.textContent = `${len}/500`;

        // Nếu là CQQL duy nhất bị chặn thì không bật nút
        if (taiKhoanDangKhoa && taiKhoanDangKhoa.vaiTro === 'CO_QUAN_QUAN_LY') {
            const soLuongCQQLActive = danhSachTaiKhoan.filter(x => x.vaiTro === 'CO_QUAN_QUAN_LY' && x.trangThaiTK === 'ACTIVE').length;
            if (soLuongCQQLActive <= 1) return;
        }

        if (len > 0) {
            nutXacNhanKhoa.disabled = false;
            nutXacNhanKhoa.className = "px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl shadow-md transition-all flex items-center gap-1.5 cursor-pointer";
        } else {
            nutXacNhanKhoa.disabled = true;
            nutXacNhanKhoa.className = "px-6 py-2.5 bg-slate-200 text-slate-400 font-bold rounded-xl transition-all flex items-center gap-1.5 cursor-not-allowed shadow-none";
        }
    }

    nhapLyDoKhoa.addEventListener('input', capNhatTrangThaiNutKhoa);

    nutXacNhanKhoa.addEventListener('click', function () {
        const lyDo = nhapLyDoKhoa.value.trim();
        if (!lyDo) {
            alert("Vui lòng nhập lý do khóa tài khoản!");
            return;
        }

        const now = new Date();
        const thoiGianStr = `${String(now.getDate()).padStart(2,'0')}/${String(now.getMonth()+1).padStart(2,'0')}/${now.getFullYear()} ${String(now.getHours()).padStart(2,'0')}:${String(now.getMinutes()).padStart(2,'0')}`;

        // Cập nhật trạng thái sang LOCKED và lưu thông tin lần khóa
        taiKhoanDangKhoa.trangThaiTK = 'LOCKED';
        taiKhoanDangKhoa.thongTinKhoaGanNhat = {
            lyDo: lyDo,
            nguoiKhoa: TAI_KHOAN_DANG_NHAP_HIEN_TAI,
            thoiGian: thoiGianStr
        };

        // Ghi nhận vào Nhật ký hệ thống
        danhSachNhatKy.unshift({
            taiKhoan: TAI_KHOAN_DANG_NHAP_HIEN_TAI,
            thoiGian: thoiGianStr,
            hanhDong: 'KHOA_TAI_KHOAN',
            chiTiet: `Khóa tài khoản [${taiKhoanDangKhoa.tenDangNhap}]: ${lyDo}`
        });

        alert(`Đã khóa tài khoản [${taiKhoanDangKhoa.tenDangNhap}] thành công. Bản ghi đã được lưu vào Nhật ký hệ thống.`);
        modalKhoa.classList.add('hidden');
        renderBangTaiKhoan();
    });

    document.querySelectorAll('.dong-modal-khoa').forEach(btn => {
        btn.addEventListener('click', () => modalKhoa.classList.add('hidden'));
    });

    // ══ 5. XỬ LÝ MODAL MỞ KHÓA TÀI KHOẢN (CĂN CỨ LẦN KHÓA & KIỂM TRA LÝ DO) ══
    const modalMoKhoa = document.getElementById('modalMoKhoaTaiKhoan');
    const moKhoaTenDangNhap = document.getElementById('moKhoaTenDangNhap');
    const moKhoaVaiTro = document.getElementById('moKhoaVaiTro');
    const moKhoaToChuc = document.getElementById('moKhoaToChuc');
    const moKhoaLyDoCuText = document.getElementById('moKhoaLyDoCuText');
    const moKhoaNguoiKhoaCuText = document.getElementById('moKhoaNguoiKhoaCuText');
    const moKhoaThoiGianCuText = document.getElementById('moKhoaThoiGianCuText');
    const moKhoaCanhBaoToChucThuHoi = document.getElementById('moKhoaCanhBaoToChucThuHoi');
    const nhapLyDoMoKhoa = document.getElementById('nhapLyDoMoKhoa');
    const demKyTuMoKhoa = document.getElementById('demKyTuMoKhoa');
    const nutXacNhanMoKhoa = document.getElementById('nutXacNhanMoKhoa');
    let taiKhoanDangMoKhoa = null;

    function moModalMoKhoa(id) {
        taiKhoanDangMoKhoa = danhSachTaiKhoan.find(x => x.id === id);
        if (!taiKhoanDangMoKhoa) return;

        moKhoaTenDangNhap.textContent = taiKhoanDangMoKhoa.tenDangNhap;
        moKhoaVaiTro.textContent = mapVaiTro[taiKhoanDangMoKhoa.vaiTro]?.ten || taiKhoanDangMoKhoa.vaiTro;
        moKhoaToChuc.textContent = taiKhoanDangMoKhoa.tenToChuc || 'Không thuộc tổ chức doanh nghiệp';

        // Đổ thông tin lần khóa trước làm căn cứ
        if (taiKhoanDangMoKhoa.thongTinKhoaGanNhat) {
            moKhoaLyDoCuText.textContent = taiKhoanDangMoKhoa.thongTinKhoaGanNhat.lyDo;
            moKhoaNguoiKhoaCuText.textContent = taiKhoanDangMoKhoa.thongTinKhoaGanNhat.nguoiKhoa;
            moKhoaThoiGianCuText.textContent = taiKhoanDangMoKhoa.thongTinKhoaGanNhat.thoiGian;
        } else {
            moKhoaLyDoCuText.textContent = "Khóa bảo mật hệ thống tự động do đăng nhập bất thường";
            moKhoaNguoiKhoaCuText.textContent = "Hệ thống tự động";
            moKhoaThoiGianCuText.textContent = "--";
        }

        // Nếu tổ chức đang ở trạng thái BI_THU_HOI -> Hiện cảnh báo vàng
        if (taiKhoanDangMoKhoa.trangThaiToChuc === 'BI_THU_HOI') {
            moKhoaCanhBaoToChucThuHoi.classList.remove('hidden');
        } else {
            moKhoaCanhBaoToChucThuHoi.classList.add('hidden');
        }

        nhapLyDoMoKhoa.value = '';
        demKyTuMoKhoa.textContent = '0/500';
        capNhatTrangThaiNutMoKhoa();

        modalMoKhoa.classList.remove('hidden');
    }

    function capNhatTrangThaiNutMoKhoa() {
        const len = nhapLyDoMoKhoa.value.trim().length;
        demKyTuMoKhoa.textContent = `${len}/500`;

        if (len > 0) {
            nutXacNhanMoKhoa.disabled = false;
            nutXacNhanMoKhoa.className = "px-6 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white font-bold rounded-xl shadow-md transition-all flex items-center gap-1.5 cursor-pointer";
        } else {
            nutXacNhanMoKhoa.disabled = true;
            nutXacNhanMoKhoa.className = "px-6 py-2.5 bg-slate-200 text-slate-400 font-bold rounded-xl transition-all flex items-center gap-1.5 cursor-not-allowed shadow-none";
        }
    }

    nhapLyDoMoKhoa.addEventListener('input', capNhatTrangThaiNutMoKhoa);

    nutXacNhanMoKhoa.addEventListener('click', function () {
        const lyDo = nhapLyDoMoKhoa.value.trim();
        if (!lyDo) {
            alert("Vui lòng nhập lý do mở khóa tài khoản!");
            return;
        }

        const now = new Date();
        const thoiGianStr = `${String(now.getDate()).padStart(2,'0')}/${String(now.getMonth()+1).padStart(2,'0')}/${now.getFullYear()} ${String(now.getHours()).padStart(2,'0')}:${String(now.getMinutes()).padStart(2,'0')}`;

        taiKhoanDangMoKhoa.trangThaiTK = 'ACTIVE';

        // Ghi nhận vào Nhật ký hệ thống
        danhSachNhatKy.unshift({
            taiKhoan: TAI_KHOAN_DANG_NHAP_HIEN_TAI,
            thoiGian: thoiGianStr,
            hanhDong: 'MO_KHOA_TAI_KHOAN',
            chiTiet: `Mở khóa tài khoản [${taiKhoanDangMoKhoa.tenDangNhap}]: ${lyDo}`
        });

        alert(`Đã mở khóa tài khoản [${taiKhoanDangMoKhoa.tenDangNhap}]. Quyền đăng nhập đã được khôi phục.`);
        modalMoKhoa.classList.add('hidden');
        renderBangTaiKhoan();
    });

    document.querySelectorAll('.dong-modal-mokhoa').forEach(btn => {
        btn.addEventListener('click', () => modalMoKhoa.classList.add('hidden'));
    });

    // ══ 6. MODAL CHI TIẾT TÀI KHOẢN (CÓ NÚT THAO TÁC NHANH Ở CHÂN MODAL) ══
    const modalDetail = document.getElementById('modalChiTietTaiKhoan');
    const thanBangHoatDong = document.getElementById('thanBangHoatDongGanDay');
    const linkSangNhatKy = document.getElementById('linkSangNhatKyHeThong');
    const detailKhungKhoaGanNhat = document.getElementById('detailKhungKhoaGanNhat');
    const detailLyDoKhoaText = document.getElementById('detailLyDoKhoaText');
    const detailNguoiKhoaText = document.getElementById('detailNguoiKhoaText');
    const detailThoiGianKhoaText = document.getElementById('detailThoiGianKhoaText');
    const khungNutThaoTacModalDetail = document.getElementById('khungNutThaoTacModalDetail');
    let taiKhoanDangXemDetail = null;

    function moModalChiTiet(id) {
        taiKhoanDangXemDetail = danhSachTaiKhoan.find(x => x.id === id);
        if (!taiKhoanDangXemDetail) return;

        const vt = mapVaiTro[taiKhoanDangXemDetail.vaiTro] || { ten: taiKhoanDangXemDetail.vaiTro, class: 'bg-slate-100 text-slate-700 border-slate-200' };

        document.getElementById('detailTenDangNhapTitle').textContent = taiKhoanDangXemDetail.tenDangNhap;
        document.getElementById('detailTenDangNhap').textContent = taiKhoanDangXemDetail.tenDangNhap;
        document.getElementById('detailEmail').textContent = taiKhoanDangXemDetail.email;
        document.getElementById('detailVaiTro').innerHTML = `<span class="badge-chuan px-2 py-0.5 rounded text-[10px] font-bold border ${vt.class}">${vt.ten}</span>`;
        document.getElementById('detailNgayTao').textContent = taiKhoanDangXemDetail.ngayTao;
        document.getElementById('detailTrangThaiTK').innerHTML = taoBadgeTrangThaiTK(taiKhoanDangXemDetail.trangThaiTK);
        document.getElementById('detailTrangThaiToChuc').innerHTML = taoBadgeTrangThaiToChuc(taiKhoanDangXemDetail.trangThaiToChuc);
        document.getElementById('detailTenToChuc').textContent = taiKhoanDangXemDetail.tenToChuc || '—';
        document.getElementById('detailLoaiHinhToChuc').textContent = taiKhoanDangXemDetail.loaiHinhToChuc || '—';

        // Nếu tài khoản đang khóa: Hiện khối lý do khóa, người khóa, thời điểm
        if (taiKhoanDangXemDetail.trangThaiTK === 'LOCKED' && taiKhoanDangXemDetail.thongTinKhoaGanNhat) {
            detailKhungKhoaGanNhat.classList.remove('hidden');
            detailLyDoKhoaText.textContent = taiKhoanDangXemDetail.thongTinKhoaGanNhat.lyDo;
            detailNguoiKhoaText.textContent = taiKhoanDangXemDetail.thongTinKhoaGanNhat.nguoiKhoa;
            detailThoiGianKhoaText.textContent = taiKhoanDangXemDetail.thongTinKhoaGanNhat.thoiGian;
        } else {
            detailKhungKhoaGanNhat.classList.add('hidden');
        }

        // Lọc 10 dòng nhật ký gần nhất và ánh xạ tên hành động tiếng Việt
        const nhatKyLienQuan = danhSachNhatKy
            .filter(log => log.taiKhoan === taiKhoanDangXemDetail.tenDangNhap)
            .slice(0, 10);

        thanBangHoatDong.innerHTML = '';
        if (nhatKyLienQuan.length === 0) {
            thanBangHoatDong.innerHTML = `<tr><td colspan="3" class="text-center py-4 text-slate-400 italic">Chưa có bản ghi hoạt động nào của tài khoản này trong hệ thống.</td></tr>`;
        } else {
            nhatKyLienQuan.forEach(log => {
                const tenHanhDongVn = mapHanhDongTiengViet[log.hanhDong] || log.hanhDong;
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="p-2.5 font-mono text-[11px] text-slate-500 whitespace-nowrap">${log.thoiGian}</td>
                    <td class="p-2.5 whitespace-nowrap">
                        <span class="badge-chuan px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">${tenHanhDongVn}</span>
                    </td>
                    <td class="p-2.5 text-slate-700 leading-tight">${log.chiTiet}</td>
                `;
                thanBangHoatDong.appendChild(tr);
            });
        }

        linkSangNhatKy.href = `nhatKyHeThong.php?taiKhoan=${encodeURIComponent(taiKhoanDangXemDetail.tenDangNhap)}`;

        // Thêm nút thao tác nhanh ở chân modal
        khungNutThaoTacModalDetail.innerHTML = '';
        const laChinhMinh = (taiKhoanDangXemDetail.tenDangNhap === TAI_KHOAN_DANG_NHAP_HIEN_TAI);

        if (!laChinhMinh) {
            if (taiKhoanDangXemDetail.trangThaiTK === 'ACTIVE') {
                khungNutThaoTacModalDetail.innerHTML = `
                    <button type="button" id="nutKhoaTuModalDetail" class="px-4 py-2 bg-red-50 hover:bg-red-100 text-red-600 font-bold text-xs rounded-xl border border-red-200 transition-all flex items-center gap-1.5 shadow-2xs">
                        <i class="fa-solid fa-lock text-[10px]"></i> Khóa tài khoản này
                    </button>
                `;
                document.getElementById('nutKhoaTuModalDetail').addEventListener('click', () => {
                    modalDetail.classList.add('hidden');
                    moModalKhoa(taiKhoanDangXemDetail.id);
                });
            } else if (taiKhoanDangXemDetail.trangThaiTK === 'LOCKED') {
                khungNutThaoTacModalDetail.innerHTML = `
                    <button type="button" id="nutMoKhoaTuModalDetail" class="px-4 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold text-xs rounded-xl border border-emerald-300 transition-all flex items-center gap-1.5 shadow-2xs">
                        <i class="fa-solid fa-lock-open text-[10px]"></i> Mở khóa tài khoản này
                    </button>
                `;
                document.getElementById('nutMoKhoaTuModalDetail').addEventListener('click', () => {
                    modalDetail.classList.add('hidden');
                    moModalMoKhoa(taiKhoanDangXemDetail.id);
                });
            }
        }

        modalDetail.classList.remove('hidden');
    }

    document.querySelectorAll('.dong-modal-detail').forEach(btn => {
        btn.addEventListener('click', () => modalDetail.classList.add('hidden'));
    });

    // ══ 7. GẮN SỰ KIỆN NÚT TRÊN BẢNG ══
    function ganSuKienNutBang() {
        document.querySelectorAll('.btn-mo-khoa-modal').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = parseInt(this.getAttribute('data-id'));
                moModalKhoa(id);
            });
        });

        document.querySelectorAll('.btn-mo-mokhoa-modal').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = parseInt(this.getAttribute('data-id'));
                moModalMoKhoa(id);
            });
        });

        document.querySelectorAll('.btn-chi-tiet-tk').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = parseInt(this.getAttribute('data-id'));
                moModalChiTiet(id);
            });
        });
    }

    // Lắng nghe tìm kiếm & lọc
    oTimKiem.addEventListener('input', () => { trangHienTai = 1; renderBangTaiKhoan(); });
    locVaiTro.addEventListener('change', () => { trangHienTai = 1; renderBangTaiKhoan(); });
    locTrangThai.addEventListener('change', () => { trangHienTai = 1; renderBangTaiKhoan(); });

    renderBangTaiKhoan();
});
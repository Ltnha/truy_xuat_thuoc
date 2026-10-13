document.addEventListener('DOMContentLoaded', function () {
    const ITEMS_PER_PAGE = 5; // Phân trang bắt buộc
    let trangHienTai = 1;

    // Helper: Chuyển chuỗi "dd/mm/yyyy hh:mm:ss" sang Date object để so sánh/sắp xếp
    function parseDateCustom(str) {
        if (!str) return new Date(0);
        const [datePart, timePart] = str.split(' ');
        const [day, month, year] = datePart.split('/').map(Number);
        const [hour, min, sec] = timePart.split(':').map(Number);
        return new Date(year, month - 1, day, hour, min, sec);
    }

    // ══ 1. CSDL MÔ PHỎNG BẢNG nhatKyHeThong ══
    let danhSachNhatKy = [
        {
            id: 1095,
            thoiGian: '10/02/2026 14:35:12',
            taiKhoan: 'cpc1_hanoi_dist',
            taiKhoanBiTacDong: null,
            hanhDong: 'GIAO_DICH_TU_CHOI',
            tenHanhDong: 'Giao dịch bị từ chối',
            ketQua: 'BI_TU_CHOI',
            tomTat: 'Số lượng chuyển vượt số khả dụng: yêu cầu 6.000, khả dụng 4.000',
            moTaTiengViet: 'Giao dịch chuyển giao lô thuốc bị Smart Contract từ chối do số lượng chuyển (6.000 hộp) vượt quá số dư khả dụng thực tế trong kho (4.000 hộp). Mã lô yêu cầu: LOT-PARA-2026.'
        },
        {
            id: 1094,
            thoiGian: '10/02/2026 11:20:05',
            taiKhoan: 'sysadmin_master',
            taiKhoanBiTacDong: 'cpc1_hanoi_dist',
            hanhDong: 'KHOA_TAI_KHOAN',
            tenHanhDong: 'Khóa tài khoản',
            ketQua: 'THANH_CONG',
            tomTat: 'Khóa tài khoản [cpc1_hanoi_dist]: Phát hiện 5 lần nhập sai mật khẩu',
            moTaTiengViet: 'Quản trị viên đã khóa tài khoản cpc1_hanoi_dist do hệ thống phát hiện 5 lần nhập sai mật khẩu liên tiếp từ địa chỉ IP bất thường (118.69.182.44).'
        },
        {
            id: 1093,
            thoiGian: '10/02/2026 10:15:40',
            taiKhoan: null,
            taiKhoanBiTacDong: null,
            tenDangNhapThu: 'root_admin_fake',
            hanhDong: 'DANG_NHAP_THAT_BAI',
            tenHanhDong: 'Đăng nhập thất bại',
            ketQua: 'BI_TU_CHOI',
            tomTat: 'Đăng nhập thất bại: Tên đăng nhập đã thử "root_admin_fake"',
            moTaTiengViet: 'Đăng nhập thất bại từ địa chỉ IP 45.118.144.12 do tên đăng nhập không tồn tại trong cơ sở dữ liệu xác thực.'
        },
        {
            id: 1092,
            thoiGian: '09/02/2026 16:40:20',
            taiKhoan: 'pharmacare_ct_pharmacy',
            taiKhoanBiTacDong: 'pharmacare_ct_pharmacy',
            hanhDong: 'DOI_MAT_KHAU',
            tenHanhDong: 'Đổi mật khẩu',
            ketQua: 'THANH_CONG',
            tomTat: 'Người dùng đổi mật khẩu tài khoản định kỳ thành công',
            moTaTiengViet: 'Tài khoản pharmacare_ct_pharmacy đã đổi mật khẩu thành công và xác thực phiên đăng nhập lại từ IP 14.169.88.21 theo chính sách an ninh.'
        },
        {
            id: 1091,
            thoiGian: '09/02/2026 14:05:10',
            taiKhoan: 'cpc1_hanoi_dist',
            taiKhoanBiTacDong: null,
            hanhDong: 'LOI_HE_THONG',
            tenHanhDong: 'Lỗi hệ thống / Blockchain',
            ketQua: 'LOI',
            tomTat: 'Lỗi timeout kết nối RPC Node Polygon Amoy (15.000ms)',
            moTaTiengViet: 'Hệ thống mất kết nối tạm thời tới nút RPC Polygon Amoy trong quá trình gửi yêu cầu truy vấn trạng thái khối của giao dịch CG-000091 (vượt quá thời gian chờ 15.000ms).'
        },
        {
            id: 1090,
            thoiGian: '08/02/2026 11:10:00',
            taiKhoan: 'sysadmin_master',
            taiKhoanBiTacDong: 'cpc1_hanoi_dist',
            hanhDong: 'MO_KHOA_TAI_KHOAN',
            tenHanhDong: 'Mở khóa',
            ketQua: 'THANH_CONG',
            tomTat: 'Mở khóa tài khoản [cpc1_hanoi_dist] sau xác minh đại diện',
            moTaTiengViet: 'Quản trị viên đã mở khóa tài khoản cpc1_hanoi_dist sau khi xác minh danh tính người đại diện và hỗ trợ đặt lại mật khẩu an toàn.'
        },
        {
            id: 1089,
            thoiGian: '08/02/2026 08:30:19',
            taiKhoan: 'pharmacare_ct_pharmacy',
            taiKhoanBiTacDong: null,
            hanhDong: 'DANG_NHAP',
            tenHanhDong: 'Đăng nhập',
            ketQua: 'THANH_CONG',
            tomTat: 'Đăng nhập thành công từ IP 14.169.88.21',
            moTaTiengViet: 'Tài khoản pharmacare_ct_pharmacy đăng nhập thành công vào hệ thống Cổng Nhà Thuốc từ địa chỉ IP 14.169.88.21.'
        },
        {
            id: 1088,
            thoiGian: '05/02/2026 17:00:30',
            taiKhoan: 'opv_pharma_revoked',
            taiKhoanBiTacDong: null,
            tenDangNhapThu: 'opv_pharma_revoked',
            hanhDong: 'DANG_NHAP_THAT_BAI',
            tenHanhDong: 'Đăng nhập thất bại',
            ketQua: 'BI_TU_CHOI',
            tomTat: 'Đăng nhập thất bại: Tài khoản đang ở trạng thái Bị thu hồi',
            moTaTiengViet: 'Từ chối đăng nhập từ IP 115.79.42.18 do tài khoản opv_pharma_revoked đã bị thu hồi quyền truy cập (REVOKED).'
        },
        {
            id: 1087,
            thoiGian: '03/02/2026 09:00:00',
            taiKhoan: 'sysadmin_master',
            taiKhoanBiTacDong: null,
            hanhDong: 'DANG_NHAP',
            tenHanhDong: 'Đăng nhập',
            ketQua: 'THANH_CONG',
            tomTat: 'Quản trị viên đăng nhập hệ thống kiểm tra Nhật ký hệ thống',
            moTaTiengViet: 'Quản trị viên sysadmin_master đăng nhập thành công vào Trung tâm Quản trị hệ thống.'
        },
        {
            id: 1086,
            thoiGian: '01/02/2026 10:20:15',
            taiKhoan: 'medipharco_nsx',
            taiKhoanBiTacDong: null,
            hanhDong: 'LOI_HE_THONG',
            tenHanhDong: 'Lỗi hệ thống / Blockchain',
            ketQua: 'LOI',
            tomTat: 'Lỗi ghi bộ đệm Redis Cache phiên đăng nhập',
            moTaTiengViet: 'Máy chủ tạm thời không kết nối được dịch vụ lưu cache bộ nhớ đệm, hệ thống tự động chuyển sang đọc cơ sở dữ liệu dự phòng để duy trì hoạt động.'
        }
    ];

    danhSachNhatKy.sort((a, b) => parseDateCustom(b.thoiGian) - parseDateCustom(a.thoiGian));

    // ══ 2. NHÃN KẾT QUẢ THUẦN VIỆT ══
    function taoBadgeKetQua(ketQua) {
        if (ketQua === 'THANH_CONG') {
            return `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300"><i class="fa-solid fa-circle-check"></i> Thành công</span>`;
        } else if (ketQua === 'BI_TU_CHOI') {
            return `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-red-100 text-red-700 border border-red-300"><i class="fa-solid fa-ban"></i> Bị từ chối</span>`;
        } else {
            return `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300"><i class="fa-solid fa-triangle-exclamation"></i> Lỗi</span>`;
        }
    }

    // ══ 3. RENDER BẢNG & PHÂN TRANG (MẶC ĐỊNH 7 NGÀY GẦN NHẤT) ══
    const thanBang = document.getElementById('thanBangNhatKy');
    const oTimKiem = document.getElementById('oTimKiemNhatKy');
    const locKhoangThoiGian = document.getElementById('locKhoangThoiGian');
    const locLoaiHanhDong = document.getElementById('locLoaiHanhDong');
    const locKetQua = document.getElementById('locKetQua');
    const soLuongBadge = document.getElementById('soLuongNhatKyBadge');
    const thongTinPhanTrang = document.getElementById('thongTinPhanTrangLog');
    const cumNutPhanTrang = document.getElementById('cumNutPhanTrangLog');

    const khungNgayTuyChon = document.getElementById('khungNgayTuyChon');
    const ngayBatDauInput = document.getElementById('ngayBatDauInput');
    const ngayKetThucInput = document.getElementById('ngayKetThucInput');
    const nutApDungKhoangNgay = document.getElementById('nutApDungKhoangNgay');

    const urlParams = new URLSearchParams(window.location.search);
    const taiKhoanParam = urlParams.get('taiKhoan');
    if (taiKhoanParam) {
        oTimKiem.value = taiKhoanParam;
        locKhoangThoiGian.value = 'TAT_CA';
    }

    locKhoangThoiGian.addEventListener('change', function () {
        if (this.value === 'TUY_CHON') {
            khungNgayTuyChon.classList.remove('hidden');
        } else {
            khungNgayTuyChon.classList.add('hidden');
            trangHienTai = 1;
            renderBangNhatKy();
        }
    });

    nutApDungKhoangNgay.addEventListener('click', function () {
        trangHienTai = 1;
        renderBangNhatKy();
    });

    function renderBangNhatKy() {
        const tuKhoa = oTimKiem.value.trim().toLowerCase();
        const hanhDongLoc = locLoaiHanhDong.value;
        const ketQuaLoc = locKetQua.value;
        const khoangThoiGian = locKhoangThoiGian.value;

        const now = new Date(2026, 1, 10, 23, 59, 59);

        const danhSachLoc = danhSachNhatKy.filter(item => {
            const tkText = item.taiKhoan ? item.taiKhoan.toLowerCase() : '';
            const matchTuKhoa = tkText.includes(tuKhoa) || 
                                item.tomTat.toLowerCase().includes(tuKhoa) || 
                                item.moTaTiengViet.toLowerCase().includes(tuKhoa);
            const matchHanhDong = (hanhDongLoc === 'TAT_CA') || (item.hanhDong === hanhDongLoc);
            const matchKetQua = (ketQuaLoc === 'TAT_CA') || (item.ketQua === ketQuaLoc);

            let matchThoiGian = true;
            const logDate = parseDateCustom(item.thoiGian);

            if (khoangThoiGian === 'HOM_NAY') {
                matchThoiGian = (logDate.getDate() === now.getDate() &&
                                 logDate.getMonth() === now.getMonth() &&
                                 logDate.getFullYear() === now.getFullYear());
            } else if (khoangThoiGian === '7_NGAY') {
                const bayNgayTruoc = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
                matchThoiGian = (logDate >= bayNgayTruoc);
            } else if (khoangThoiGian === '30_NGAY') {
                const baMuoiNgayTruoc = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000);
                matchThoiGian = (logDate >= baMuoiNgayTruoc);
            } else if (khoangThoiGian === 'TUY_CHON') {
                const tuNgayVal = ngayBatDauInput.value;
                const denNgayVal = ngayKetThucInput.value;
                if (tuNgayVal) {
                    const dStart = new Date(tuNgayVal + 'T00:00:00');
                    if (logDate < dStart) matchThoiGian = false;
                }
                if (denNgayVal) {
                    const dEnd = new Date(denNgayVal + 'T23:59:59');
                    if (logDate > dEnd) matchThoiGian = false;
                }
            }

            return matchTuKhoa && matchHanhDong && matchKetQua && matchThoiGian;
        });

        soLuongBadge.textContent = `Hiển thị: ${danhSachLoc.length} bản ghi`;

        const tongSoTrang = Math.ceil(danhSachLoc.length / ITEMS_PER_PAGE) || 1;
        if (trangHienTai > tongSoTrang) trangHienTai = tongSoTrang;

        const batDau = (trangHienTai - 1) * ITEMS_PER_PAGE;
        const ketThuc = batDau + ITEMS_PER_PAGE;
        const duLieuTrang = danhSachLoc.slice(batDau, ketThuc);

        thanBang.innerHTML = '';

        if (duLieuTrang.length === 0) {
            thanBang.innerHTML = `<tr><td colspan="6" class="text-center py-8 text-slate-400 italic">Không tìm thấy bản ghi nhật ký nào phù hợp với bộ lọc.</td></tr>`;
            thongTinPhanTrang.textContent = "Không có bản ghi nào";
            cumNutPhanTrang.innerHTML = '';
            return;
        }

        thongTinPhanTrang.textContent = `Đang hiển thị ${batDau + 1} đến ${Math.min(ketThuc, danhSachLoc.length)} trên tổng số ${danhSachLoc.length} bản ghi`;

        duLieuTrang.forEach(item => {
            const taiKhoanHienThi = item.taiKhoan ? item.taiKhoan : '<span class="text-slate-400 font-mono font-bold block text-center">—</span>';
            const badgeKetQua = taoBadgeKetQua(item.ketQua);

            const tr = document.createElement('tr');
            tr.className = 'dong-nhat-ky transition-colors';
            tr.innerHTML = `
                <td class="py-3 px-3 font-mono text-[11px] text-slate-600 whitespace-nowrap">${item.thoiGian}</td>
                <td class="py-3 px-3 font-mono font-bold text-slate-900 whitespace-nowrap">${taiKhoanHienThi}</td>
                <td class="py-3 px-3 font-semibold text-slate-800 whitespace-nowrap">${item.tenHanhDong}</td>
                <td class="py-3 px-2.5 text-center whitespace-nowrap">${badgeKetQua}</td>
                <td class="py-3 px-3 text-slate-600 truncate max-w-[280px]" title="${item.tomTat}">${item.tomTat}</td>
                <td class="py-3 px-3 text-center whitespace-nowrap">
                    <button type="button" class="btn-xem-chi-tiet-log px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-all flex items-center justify-center gap-1 mx-auto border border-slate-300 shadow-2xs">
                        <i class="fa-solid fa-eye text-slate-600 text-[10px]"></i> Chi tiết
                    </button>
                </td>
            `;

            tr.addEventListener('click', () => moModalChiTietLog(item));
            thanBang.appendChild(tr);
        });

        renderPhanTrang(tongSoTrang);
    }

    function renderPhanTrang(tongSoTrang) {
        cumNutPhanTrang.innerHTML = '';
        if (tongSoTrang <= 1) return;

        const btnPrev = document.createElement('button');
        btnPrev.className = `px-2.5 py-1 rounded-lg border text-xs font-bold transition-all ${trangHienTai === 1 ? 'border-slate-200 text-slate-300 cursor-not-allowed' : 'border-slate-300 text-slate-700 hover:bg-slate-100'}`;
        btnPrev.innerHTML = `<i class="fa-solid fa-angle-left"></i>`;
        btnPrev.disabled = (trangHienTai === 1);
        btnPrev.addEventListener('click', () => { if (trangHienTai > 1) { trangHienTai--; renderBangNhatKy(); } });
        cumNutPhanTrang.appendChild(btnPrev);

        for (let i = 1; i <= tongSoTrang; i++) {
            const btnPage = document.createElement('button');
            btnPage.className = `w-7 h-7 rounded-lg text-xs font-bold transition-all ${trangHienTai === i ? 'bg-slate-800 text-white shadow-2xs' : 'border border-slate-200 text-slate-700 hover:bg-slate-100'}`;
            btnPage.textContent = i;
            btnPage.addEventListener('click', () => { trangHienTai = i; renderBangNhatKy(); });
            cumNutPhanTrang.appendChild(btnPage);
        }

        const btnNext = document.createElement('button');
        btnNext.className = `px-2.5 py-1 rounded-lg border text-xs font-bold transition-all ${trangHienTai === tongSoTrang ? 'border-slate-200 text-slate-300 cursor-not-allowed' : 'border-slate-300 text-slate-700 hover:bg-slate-100'}`;
        btnNext.innerHTML = `<i class="fa-solid fa-angle-right"></i>`;
        btnNext.disabled = (trangHienTai === tongSoTrang);
        btnNext.addEventListener('click', () => { if (trangHienTai < tongSoTrang) { trangHienTai++; renderBangNhatKy(); } });
        cumNutPhanTrang.appendChild(btnNext);
    }

    // ══ 4. XỬ LÝ MODAL CHI TIẾT NHẬT KÝ (ĐÃ BỎ CHI TIẾT KỸ THUẬT) ══
    const modalLog = document.getElementById('modalChiTietLog');
    const modalLogId = document.getElementById('modalLogId');
    const modalLogThoiGian = document.getElementById('modalLogThoiGian');
    const modalLogTaiKhoan = document.getElementById('modalLogTaiKhoan');
    const modalLogHanhDong = document.getElementById('modalLogHanhDong');
    const modalLogKetQuaBadge = document.getElementById('modalLogKetQuaBadge');
    
    const khungTaiKhoanBiTacDong = document.getElementById('modalLogKhungTaiKhoanBiTacDong');
    const taiKhoanBiTacDongText = document.getElementById('modalLogTaiKhoanBiTacDongText');
    const linkSangTaiKhoan = document.getElementById('modalLogLinkSangTaiKhoan');

    const modalLogCanhBaoDangNhap = document.getElementById('modalLogCanhBaoDangNhap');
    const modalLogTenDangNhapThuText = document.getElementById('modalLogTenDangNhapThuText');
    const modalLogMoTaTiengViet = document.getElementById('modalLogMoTaTiengViet');

    function moModalChiTietLog(item) {
        modalLogId.textContent = `LOG-${item.id}`;
        modalLogThoiGian.textContent = item.thoiGian;
        modalLogTaiKhoan.textContent = item.taiKhoan ? item.taiKhoan : '— (Không xác định)';
        modalLogHanhDong.textContent = item.tenHanhDong;
        modalLogKetQuaBadge.innerHTML = taoBadgeKetQua(item.ketQua);

        // 1. Tài khoản bị tác động kèm liên kết chuyển hướng
        if (item.taiKhoanBiTacDong) {
            khungTaiKhoanBiTacDong.classList.remove('hidden');
            taiKhoanBiTacDongText.textContent = item.taiKhoanBiTacDong;
            linkSangTaiKhoan.href = `quanLyTaiKhoan.php?timKiem=${encodeURIComponent(item.taiKhoanBiTacDong)}`;
        } else {
            khungTaiKhoanBiTacDong.classList.add('hidden');
        }

        // 2. Trường hợp Đăng nhập thất bại (Cam kết không lưu mật khẩu)
        if (item.hanhDong === 'DANG_NHAP_THAT_BAI') {
            modalLogCanhBaoDangNhap.classList.remove('hidden');
            modalLogTenDangNhapThuText.textContent = item.tenDangNhapThu || item.taiKhoan || 'Không xác định';
        } else {
            modalLogCanhBaoDangNhap.classList.add('hidden');
        }

        // 3. Nội dung chi tiết sự kiện tiếng Việt
        modalLogMoTaTiengViet.textContent = item.moTaTiengViet;

        modalLog.classList.remove('hidden');
    }

    document.querySelectorAll('.dong-modal-log').forEach(btn => {
        btn.addEventListener('click', () => modalLog.classList.add('hidden'));
    });

    // Lắng nghe sự kiện lọc và tìm kiếm
    oTimKiem.addEventListener('input', () => { trangHienTai = 1; renderBangNhatKy(); });
    locLoaiHanhDong.addEventListener('change', () => { trangHienTai = 1; renderBangNhatKy(); });
    locKetQua.addEventListener('change', () => { trangHienTai = 1; renderBangNhatKy(); });

    renderBangNhatKy();
});
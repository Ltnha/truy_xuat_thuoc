document.addEventListener('DOMContentLoaded', function () {
    const ITEMS_PER_PAGE = 4;
    let trangHienTai = 1;

    // Dữ liệu mô phỏng CSDL bảng nhatKyHeThong (off-chain audit log)
    let danhSachNhatKy = [
        {
            id: 'LOG-1092',
            thoiGian: '2026-02-10 14:35:12',
            taiKhoan: 'cpc1_hanoi_dist (Nhà phân phối)',
            hanhDong: 'BLOCKCHAIN_REJECTED',
            tenHanhDong: 'Khởi tạo chuyển giao bị từ chối',
            chiTietTomTat: 'Thất bại hàm transferBatch(): Số lượng xuất (6,000) vượt quá số dư khả dụng (4,000)',
            chiTietDayDu: 'Lỗi từ Smart Contract: "Transfer amount exceeds available balance"\nActor: 0x3a4b...11C (CPC1 Hà Nội)\nBatch ID: 0x8f2d5e1b4a3c79a2f60e1d8b5c9a4e2f1a3b5c7d9e1f2a4b6c8d0e2f4a6b8c0\nKết quả: REJECTED (Giao dịch không được ghi vào chuỗi, đã lưu vết kiểm toán off-chain).'
        },
        {
            id: 'LOG-1091',
            thoiGian: '2026-02-10 11:20:05',
            taiKhoan: 'sysadmin_master (Quản trị viên)',
            hanhDong: 'QUAN_LY_TAI_KHOAN',
            tenHanhDong: 'Khóa tài khoản người dùng',
            chiTietTomTat: 'Đã khóa tài khoản [opv_pharma_suspended]. Lý do: Phát hiện dấu hiệu đăng nhập bất thường',
            chiTietDayDu: 'Hành động: Khóa tài khoản (LOCKED)\nTarget user: opv_pharma_suspended (ID: 6)\nLý do: Phát hiện 5 lần nhập sai mật khẩu liên tiếp từ địa chỉ IP lạ (118.69.182.44).\nTác nhân thực hiện: sysadmin_master.'
        },
        {
            id: 'LOG-1090',
            thoiGian: '2026-02-10 09:12:44',
            taiKhoan: 'medipharco_admin (Nhà sản xuất)',
            hanhDong: 'NGHIEP_VU_CHUOI',
            tenHanhDong: 'Đăng ký lô thuốc mới',
            chiTietTomTat: 'Thành công hàm createBatch(): Lô LOT-PARA-2026-03 (5,000 hộp)',
            chiTietDayDu: 'Giao dịch Smart Contract thành công.\nFunction: createBatch(productId, batchId, quantity, mfgDate, expDate)\nTransaction Hash: 0x9f1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f28c4\nTrạng thái lô: CREATED.'
        },
        {
            id: 'LOG-1089',
            thoiGian: '2026-02-10 08:30:19',
            taiKhoan: 'pharmacare_ct_pharmacy (Nhà thuốc)',
            hanhDong: 'DANG_NHAP',
            tenHanhDong: 'Đăng nhập hệ thống',
            chiTietTomTat: 'Đăng nhập thành công từ địa chỉ IP: 14.169.88.21 (Trình duyệt Chrome)',
            chiTietDayDu: 'Sự kiện: User Login Success\nTài khoản: pharmacare_ct_pharmacy\nVai trò: NHA_THUOC\nIP: 14.169.88.21\nUser-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/122.0.0.0'
        },
        {
            id: 'LOG-1088',
            thoiGian: '2026-02-09 17:05:30',
            taiKhoan: 'guest_attacker (Không xác thực)',
            hanhDong: 'DANG_NHAP',
            tenHanhDong: 'Đăng nhập thất bại',
            chiTietTomTat: 'Sai mật khẩu tài khoản root_system 3 lần liên tiếp',
            chiTietDayDu: 'Sự kiện: User Login Failed\nTên đăng nhập nhập vào: root_system\nIP nguồn: 45.118.144.12\nThời gian: 2026-02-09 17:05:30\nCảnh báo: Có dấu hiệu dò quét mật khẩu (brute-force).'
        },
        {
            id: 'LOG-1087',
            thoiGian: '2026-02-08 16:45:00',
            taiKhoan: 'dav_inspector_01 (Cơ quan quản lý)',
            hanhDong: 'NGHIEP_VU_CHUOI',
            tenHanhDong: 'Kích hoạt thu hồi lô thuốc',
            chiTietTomTat: 'Gọi hàm recallBatch(): Thu hồi lô LOT-AMOX-2025-09 trên toàn hệ thống',
            chiTietDayDu: 'Giao dịch Smart Contract thành công.\nFunction: recallBatch(batchId, reasonHash)\nTxHash: 0x71a49f2b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4e5\nLý do: Không đạt tiêu chuẩn độ hòa tan theo công văn 458/QLD.'
        }
    ];

    const thanBang = document.getElementById('thanBangNhatKy');
    const oTimKiem = document.getElementById('oTimKiemNhatKy');
    const locHanhDong = document.getElementById('locHanhDong');
    const locThoiGian = document.getElementById('locThoiGian');
    const soLuongBadge = document.getElementById('soLuongNhatKyBadge');
    const thongTinPhanTrang = document.getElementById('thongTinPhanTrangLog');
    const cumNutPhanTrang = document.getElementById('cumNutPhanTrangLog');

    function taoBadgeHanhDong(action) {
        if (action === 'BLOCKCHAIN_REJECTED') {
            return `<span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-red-100 text-red-700 border border-red-300"><i class="fa-solid fa-ban mr-1"></i> SMART CONTRACT REJECTED</span>`;
        } else if (action === 'QUAN_LY_TAI_KHOAN') {
            return `<span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300"><i class="fa-solid fa-user-gear mr-1"></i> TÀI KHOẢN</span>`;
        } else if (action === 'NGHIEP_VU_CHUOI') {
            return `<span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-cyan-100 text-cyan-800 border border-cyan-300"><i class="fa-solid fa-cube mr-1"></i> GIAO DỊCH CHUỖI</span>`;
        } else {
            return `<span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-slate-100 text-slate-700 border border-slate-300"><i class="fa-solid fa-right-to-bracket mr-1"></i> ĐĂNG NHẬP</span>`;
        }
    }

    // ══ 1. RENDER BẢNG NHẬT KÝ & PHÂN TRANG ══
    function renderBangNhatKy() {
        const tuKhoa = oTimKiem.value.trim().toLowerCase();
        const hanhDongLoc = locHanhDong.value;

        const danhSachLoc = danhSachNhatKy.filter(item => {
            const matchTuKhoa = item.id.toLowerCase().includes(tuKhoa) ||
                               item.taiKhoan.toLowerCase().includes(tuKhoa) ||
                               item.tenHanhDong.toLowerCase().includes(tuKhoa) ||
                               item.chiTietTomTat.toLowerCase().includes(tuKhoa);
            const matchHanhDong = (hanhDongLoc === 'TAT_CA') || (item.hanhDong === hanhDongLoc);

            return matchTuKhoa && matchHanhDong;
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
            const badge = taoBadgeHanhDong(item.hanhDong);

            const tr = document.createElement('tr');
            tr.className = 'dong-nhat-ky transition-colors';
            tr.innerHTML = `
                <td class="py-3.5 px-4 font-mono font-bold text-slate-900 whitespace-nowrap">${item.id}</td>
                <td class="py-3.5 px-4 font-mono text-[11px] text-slate-500 whitespace-nowrap">${item.thoiGian}</td>
                <td class="py-3.5 px-4 font-semibold text-slate-800">${item.taiKhoan}</td>
                <td class="py-3.5 px-4 whitespace-nowrap">${badge}</td>
                <td class="py-3.5 px-4 text-slate-600 truncate max-w-xs" title="${item.chiTietTomTat}">${item.chiTietTomTat}</td>
                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                    <button type="button" class="btn-xem-log px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-all flex items-center gap-1 mx-auto" data-id="${item.id}">
                        <i class="fa-solid fa-eye"></i> Chi Tiết
                    </button>
                </td>
            `;
            thanBang.appendChild(tr);
        });

        renderPhanTrang(tongSoTrang);
        ganSuKienNutBang();
    }

    // ══ 2. PHÂN TRANG ══
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

    // ══ 3. MODAL XEM CHI TIẾT NHẬT KÝ ══
    const modalLog = document.getElementById('modalChiTietLog');
    const modalLogId = document.getElementById('modalLogId');
    const modalLogThoiGian = document.getElementById('modalLogThoiGian');
    const modalLogTaiKhoan = document.getElementById('modalLogTaiKhoan');
    const modalLogBadgeHanhDong = document.getElementById('modalLogBadgeHanhDong');
    const modalLogChiTiet = document.getElementById('modalLogChiTiet');

    function ganSuKienNutBang() {
        document.querySelectorAll('.btn-xem-log').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                const log = danhSachNhatKy.find(x => x.id === id);
                if (!log) return;

                modalLogId.textContent = log.id;
                modalLogThoiGian.textContent = log.thoiGian;
                modalLogTaiKhoan.textContent = log.taiKhoan;
                modalLogBadgeHanhDong.innerHTML = taoBadgeHanhDong(log.hanhDong);
                modalLogChiTiet.textContent = log.chiTietDayDu;

                modalLog.classList.remove('hidden');
            });
        });
    }

    document.querySelectorAll('.dong-modal-log').forEach(btn => {
        btn.addEventListener('click', () => modalLog.classList.add('hidden'));
    });

    // Lắng nghe tìm kiếm & lọc
    oTimKiem.addEventListener('input', () => { trangHienTai = 1; renderBangNhatKy(); });
    locHanhDong.addEventListener('change', () => { trangHienTai = 1; renderBangNhatKy(); });
    locThoiGian.addEventListener('change', () => { trangHienTai = 1; renderBangNhatKy(); });

    renderBangNhatKy();
});
document.addEventListener('DOMContentLoaded', function () {
    const ITEMS_PER_PAGE = 4;
    let trangHienTai = 1;

    let danhSachTaiKhoan = [];

    async function taiKhoanApi() {
        try {
            const response = await fetch('/api/quan-tri/tai-khoan', {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error('Không thể tải dữ liệu tài khoản');
            }

            const json = await response.json();
            danhSachTaiKhoan = Array.isArray(json.data) ? json.data : [];
        } catch (error) {
            console.error(error);
            danhSachTaiKhoan = [];
        }

        renderBangTaiKhoan();
    }

    const thanBang = document.getElementById('thanBangTaiKhoan');
    const oTimKiem = document.getElementById('oTimKiemTaiKhoan');
    const locVaiTro = document.getElementById('locVaiTro');
    const locTrangThai = document.getElementById('locTrangThaiTK');
    const soLuongBadge = document.getElementById('soLuongTaiKhoanBadge');
    const thongTinPhanTrang = document.getElementById('thongTinPhanTrangTK');
    const cumNutPhanTrang = document.getElementById('cumNutPhanTrangTK');

    const mapVaiTro = {
        'NHA_SAN_XUAT': { ten: 'Nhà sản xuất', class: 'bg-emerald-50 text-emerald-800 border-emerald-300' },
        'NHA_PHAN_PHOI': { ten: 'Nhà phân phối', class: 'bg-blue-50 text-blue-800 border-blue-300' },
        'NHA_THUOC': { ten: 'Nhà thuốc', class: 'bg-cyan-50 text-cyan-800 border-cyan-300' },
        'CO_QUAN_QUAN_LY': { ten: 'Cơ quan quản lý', class: 'bg-indigo-50 text-indigo-800 border-indigo-300' },
        'QUAN_TRI_VIEN': { ten: 'Quản trị viên', class: 'bg-slate-100 text-slate-800 border-slate-300' }
    };

    function renderBangTaiKhoan() {
        const tuKhoa = oTimKiem.value.trim().toLowerCase();
        const vaiTroChon = locVaiTro.value;
        const trangThaiChon = locTrangThai.value;

        const danhSachLoc = danhSachTaiKhoan.filter(item => {
            const matchTuKhoa = item.tenDangNhap.toLowerCase().includes(tuKhoa) ||
                               item.email.toLowerCase().includes(tuKhoa) ||
                               item.tenToChuc.toLowerCase().includes(tuKhoa);
            const matchVaiTro = (vaiTroChon === 'TAT_CA') || (item.vaiTro === vaiTroChon);
            const matchTrangThai = (trangThaiChon === 'TAT_CA') || (item.trangThai === trangThaiChon);

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

            let badgeTrangThai = '';
            let nutKhoaMoHtml = '';

            if (item.trangThai === 'ACTIVE') {
                badgeTrangThai = `
                    <span class="badge-chuan px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300">
                        <i class="fa-solid fa-circle-check text-xs"></i> <span>ACTIVE</span>
                    </span>
                `;
                nutKhoaMoHtml = `
                    <button type="button" class="btn-khoa-mo px-2.5 py-1 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 font-bold text-xs transition-all flex items-center gap-1 border border-red-200 whitespace-nowrap shadow-2xs" data-id="${item.id}" data-action="LOCK">
                        <i class="fa-solid fa-lock"></i> Khóa
                    </button>
                `;
            } else if (item.trangThai === 'LOCKED') {
                badgeTrangThai = `
                    <span class="badge-chuan px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-red-100 text-red-700 border border-red-300">
                        <i class="fa-solid fa-lock text-xs"></i> <span>LOCKED</span>
                    </span>
                `;
                nutKhoaMoHtml = `
                    <button type="button" class="btn-khoa-mo px-2.5 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold text-xs transition-all flex items-center gap-1 border border-emerald-300 whitespace-nowrap shadow-2xs" data-id="${item.id}" data-action="UNLOCK">
                        <i class="fa-solid fa-lock-open"></i> Mở Khóa
                    </button>
                `;
            } else if (item.trangThai === 'PENDING') {
                badgeTrangThai = `
                    <span class="badge-chuan px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300">
                        <i class="fa-solid fa-clock text-xs"></i> <span>PENDING</span>
                    </span>
                `;
                nutKhoaMoHtml = `<button type="button" disabled class="px-2 py-1 rounded-lg bg-slate-100 text-slate-400 font-bold text-xs cursor-not-allowed whitespace-nowrap">Chờ duyệt</button>`;
            } else {
                badgeTrangThai = `
                    <span class="badge-chuan px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-slate-200 text-slate-800 border border-slate-300">
                        <i class="fa-solid fa-ban text-xs"></i> <span>REVOKED</span>
                    </span>
                `;
                nutKhoaMoHtml = `<button type="button" disabled class="px-2 py-1 rounded-lg bg-slate-100 text-slate-400 font-bold text-xs cursor-not-allowed whitespace-nowrap">Đã thu hồi</button>`;
            }

            const tr = document.createElement('tr');
            tr.className = 'dong-tai-khoan transition-colors';
            tr.innerHTML = `
                <td class="py-3 px-3 font-mono font-bold text-slate-900 whitespace-nowrap">${item.tenDangNhap}</td>
                <td class="py-3 px-3 text-slate-600 truncate max-w-[160px]" title="${item.email}">${item.email}</td>
                
                <td class="py-3 px-2.5 text-center whitespace-nowrap">
                    <span class="badge-chuan px-2 py-0.5 rounded-md text-[11px] font-bold border ${vt.class}">
                        ${vt.ten}
                    </span>
                </td>

                <td class="py-3 px-3 font-semibold text-slate-800 leading-tight">${item.tenToChuc}</td>
                <td class="py-3 px-2.5 text-center font-mono text-[10px] text-slate-500 whitespace-nowrap">${item.ngayTao}</td>
                
                <td class="py-3 px-2.5 text-center whitespace-nowrap">
                    ${badgeTrangThai}
                </td>

                <td class="py-3 px-3 text-center whitespace-nowrap">
                    <div class="inline-flex items-center justify-center gap-1.5">
                        ${nutKhoaMoHtml}
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

    const modalKhoaMo = document.getElementById('modalKhoaMoTaiKhoan');
    const tieuDeModal = document.getElementById('tieuDeModalKhoaMo');
    const modalTenDangNhap = document.getElementById('modalTenDangNhap');
    const modalVaiTro = document.getElementById('modalVaiTro');
    const modalToChuc = document.getElementById('modalToChuc');
    const lyDoTextarea = document.getElementById('lyDoKhoaMo');
    const canhBaoText = document.getElementById('canhBaoKhoaMoText');
    const nutXacNhanHanhDong = document.getElementById('nutXacNhanHanhDongKhoaMo');

    let taiKhoanDangChon = null;
    let hanhDongHienTai = null;

    function ganSuKienNutBang() {
        document.querySelectorAll('.btn-khoa-mo').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = parseInt(this.getAttribute('data-id'));
                hanhDongHienTai = this.getAttribute('data-action');
                taiKhoanDangChon = danhSachTaiKhoan.find(x => x.id === id);

                if (!taiKhoanDangChon) return;

                modalTenDangNhap.textContent = taiKhoanDangChon.tenDangNhap;
                modalVaiTro.textContent = mapVaiTro[taiKhoanDangChon.vaiTro]?.ten || taiKhoanDangChon.vaiTro;
                modalToChuc.textContent = taiKhoanDangChon.tenToChuc;
                lyDoTextarea.value = '';

                if (hanhDongHienTai === 'LOCK') {
                    tieuDeModal.innerHTML = `<i class="fa-solid fa-user-lock text-red-600"></i> Xác Nhận Khóa Tài Khoản`;
                    canhBaoText.textContent = "Lưu ý: Khi tài khoản bị khóa (LOCKED), người dùng sẽ không thể đăng nhập hoặc thực hiện bất kỳ thao tác nào trên hệ thống.";
                    nutXacNhanHanhDong.className = "px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl shadow-md transition-all flex items-center gap-1.5";
                    nutXacNhanHanhDong.innerHTML = `<i class="fa-solid fa-lock"></i> Khóa Ngay`;
                } else {
                    tieuDeModal.innerHTML = `<i class="fa-solid fa-user-check text-emerald-700"></i> Xác Nhận Mở Khóa Tài Khoản`;
                    canhBaoText.textContent = "Lưu ý: Tài khoản sẽ được chuyển lại trạng thái ACTIVE, khôi phục toàn bộ quyền đăng nhập bình thường.";
                    nutXacNhanHanhDong.className = "px-6 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white font-bold rounded-xl shadow-md transition-all flex items-center gap-1.5";
                    nutXacNhanHanhDong.innerHTML = `<i class="fa-solid fa-lock-open"></i> Mở Khóa Ngay`;
                }

                modalKhoaMo.classList.remove('hidden');
            });
        });
    }

    document.querySelectorAll('.dong-modal-khoa').forEach(btn => {
        btn.addEventListener('click', () => modalKhoaMo.classList.add('hidden'));
    });

    nutXacNhanHanhDong.addEventListener('click', function () {
        const lyDo = lyDoTextarea.value.trim();
        if (!lyDo) {
            alert("Vui lòng nhập lý do thực hiện để ghi vào nhật ký hệ thống!");
            return;
        }

        if (hanhDongHienTai === 'LOCK') {
            taiKhoanDangChon.trangThai = 'LOCKED';
            alert(`Đã khóa tài khoản [${taiKhoanDangChon.tenDangNhap}]. Lý do đã được ghi vào nhật ký kiểm toán.`);
        } else {
            taiKhoanDangChon.trangThai = 'ACTIVE';
            alert(`Đã mở khóa tài khoản [${taiKhoanDangChon.tenDangNhap}]. Quyền đăng nhập đã được khôi phục.`);
        }

        modalKhoaMo.classList.add('hidden');
        renderBangTaiKhoan();
    });

    oTimKiem.addEventListener('input', () => { trangHienTai = 1; renderBangTaiKhoan(); });
    locVaiTro.addEventListener('change', () => { trangHienTai = 1; renderBangTaiKhoan(); });
    locTrangThai.addEventListener('change', () => { trangHienTai = 1; renderBangTaiKhoan(); });

    taiKhoanApi();
});
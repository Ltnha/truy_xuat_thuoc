document.addEventListener('DOMContentLoaded', function () {
    const EXPLORER_BASE_URL = "https://amoy.polygonscan.com/tx/";
    const ITEMS_PER_PAGE = 4;
    let trangHienTaiSP = 1;

    let danhSachSanPham = [];

    async function sanPhamApi() {
        try {
            const response = await fetch('/api/quan-tri/san-pham', {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error('Không thể tải danh sách sản phẩm');
            }

            const json = await response.json();
            danhSachSanPham = Array.isArray(json.data) ? json.data : [];
        } catch (error) {
            console.error(error);
            danhSachSanPham = [];
        }

        renderBangSanPham();
    }

    const thanBangSP = document.getElementById('thanBangSanPham');
    const oTimKiem = document.getElementById('oTimKiemSP');
    const locTrangThai = document.getElementById('locTrangThaiSP');
    const soLuongBadge = document.getElementById('soLuongSPBadge');
    const thongTinPhanTrang = document.getElementById('thongTinPhanTrangSP');
    const cumNutPhanTrang = document.getElementById('cumNutPhanTrangSP');

    // ══ 1. RENDER BẢNG SẢN PHẨM & PHÂN TRANG ══
    function renderBangSanPham() {
        const tuKhoa = oTimKiem.value.trim().toLowerCase();
        const trangThaiLoc = locTrangThai.value;

        const danhSachLoc = danhSachSanPham.filter(item => {
            const matchTuKhoa = item.tenSanPham.toLowerCase().includes(tuKhoa) ||
                                item.soDangKy.toLowerCase().includes(tuKhoa) ||
                                item.thanhPhan.toLowerCase().includes(tuKhoa);
            
            const matchTrangThai = (trangThaiLoc === 'TAT_CA') || (item.trangThai === trangThaiLoc);

            return matchTuKhoa && matchTrangThai;
        });

        soLuongBadge.textContent = `Hiển thị: ${danhSachLoc.length} sản phẩm`;

        const tongSoTrang = Math.ceil(danhSachLoc.length / ITEMS_PER_PAGE) || 1;
        if (trangHienTaiSP > tongSoTrang) trangHienTaiSP = tongSoTrang;

        const batDau = (trangHienTaiSP - 1) * ITEMS_PER_PAGE;
        const ketThuc = batDau + ITEMS_PER_PAGE;
        const duLieuTrang = danhSachLoc.slice(batDau, ketThuc);

        thanBangSP.innerHTML = '';

        if (duLieuTrang.length === 0) {
            thanBangSP.innerHTML = `<tr><td colspan="7" class="text-center py-8 text-slate-400 italic">Không tìm thấy sản phẩm dược nào phù hợp với bộ lọc.</td></tr>`;
            thongTinPhanTrang.textContent = "Không có bản ghi nào";
            cumNutPhanTrang.innerHTML = '';
            return;
        }

        thongTinPhanTrang.textContent = `Đang hiển thị ${batDau + 1} đến ${Math.min(ketThuc, danhSachLoc.length)} trên tổng số ${danhSachLoc.length} sản phẩm`;

        duLieuTrang.forEach(item => {
            let badgeTrangThai = '';
            let nutHanhDongThem = '';

            if (item.trangThai === 'DA_DUYET') {
                badgeTrangThai = `
                    <span class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300">
                        <i class="fa-solid fa-circle-check text-xs"></i> <span>ĐÃ DUYỆT</span>
                    </span>
                `;
            } else if (item.trangThai === 'CHO_DUYET') {
                badgeTrangThai = `
                    <span class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300">
                        <i class="fa-solid fa-clock text-xs"></i> <span>CHỜ DUYỆT</span>
                    </span>
                `;
            } else {
                const lanGanNhat = item.lichSuNop[item.lichSuNop.length - 1];
                badgeTrangThai = `
                    <div class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap" title="${lanGanNhat.lyDoTuChoi}">
                        <span class="inline-flex items-center justify-center gap-1 whitespace-nowrap px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-red-100 text-red-700 border border-red-300">
                            <i class="fa-solid fa-circle-xmark text-xs"></i> <span>BỊ TỪ CHỐI</span>
                        </span>
                        <i class="fa-solid fa-triangle-exclamation text-red-600 text-xs cursor-help"></i>
                    </div>
                `;
                nutHanhDongThem = `
                    <button type="button" class="btn-nop-lai px-2.5 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs transition-all flex items-center gap-1 whitespace-nowrap shadow-2xs" data-id="${item.id}">
                        <i class="fa-solid fa-rotate-right"></i> Nộp Lại
                    </button>
                `;
            }

            const tr = document.createElement('tr');
            tr.className = 'dong-san-pham transition-colors';
            tr.innerHTML = `
                <td class="py-3 px-3 font-bold text-slate-900 leading-tight">${item.tenSanPham}</td>
                <td class="py-3 px-3 font-mono font-bold text-cyan-800 whitespace-nowrap">${item.soDangKy}</td>
                <td class="py-3 px-3 text-slate-700 leading-tight">${item.thanhPhan}</td>
                <td class="py-3 px-2.5 text-slate-600 whitespace-nowrap">${item.hamLuong}</td>
                <td class="py-3 px-2.5 text-slate-600 whitespace-nowrap">${item.dangBaoChe}</td>

                <td class="py-3 px-2.5 text-center whitespace-nowrap">
                    ${badgeTrangThai}
                </td>

                <td class="py-3 px-3 text-center whitespace-nowrap">
                    <div class="inline-flex items-center justify-center gap-1.5">
                        ${nutHanhDongThem}
                        <!-- Nút Chi tiết & Lịch sử theo chuẩn FR05 -->
                        <button type="button" class="btn-xem-chi-tiet-sp px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-all flex items-center gap-1 whitespace-nowrap border border-slate-300 shadow-2xs" data-id="${item.id}">
                            <i class="fa-solid fa-circle-info text-emerald-700"></i> Chi Tiết & Lịch Sử
                        </button>
                    </div>
                </td>
            `;

            thanBangSP.appendChild(tr);
        });

        renderPhanTrangSP(tongSoTrang);
        ganSuKienBangSP();
    }

    // ══ CỤM NÚT PHÂN TRANG ══
    function renderPhanTrangSP(tongSoTrang) {
        cumNutPhanTrang.innerHTML = '';
        if (tongSoTrang <= 1) return;

        const btnPrev = document.createElement('button');
        btnPrev.className = `px-2.5 py-1 rounded-lg border text-xs font-bold transition-all ${trangHienTaiSP === 1 ? 'border-slate-200 text-slate-300 cursor-not-allowed' : 'border-slate-300 text-slate-700 hover:bg-slate-100'}`;
        btnPrev.innerHTML = `<i class="fa-solid fa-angle-left"></i>`;
        btnPrev.disabled = (trangHienTaiSP === 1);
        btnPrev.addEventListener('click', () => { if (trangHienTaiSP > 1) { trangHienTaiSP--; renderBangSanPham(); } });
        cumNutPhanTrang.appendChild(btnPrev);

        for (let i = 1; i <= tongSoTrang; i++) {
            const btnPage = document.createElement('button');
            btnPage.className = `w-7 h-7 rounded-lg text-xs font-bold transition-all ${trangHienTaiSP === i ? 'bg-emerald-700 text-white shadow-2xs' : 'border border-slate-200 text-slate-700 hover:bg-slate-100'}`;
            btnPage.textContent = i;
            btnPage.addEventListener('click', () => { trangHienTaiSP = i; renderBangSanPham(); });
            cumNutPhanTrang.appendChild(btnPage);
        }

        const btnNext = document.createElement('button');
        btnNext.className = `px-2.5 py-1 rounded-lg border text-xs font-bold transition-all ${trangHienTaiSP === tongSoTrang ? 'border-slate-200 text-slate-300 cursor-not-allowed' : 'border-slate-300 text-slate-700 hover:bg-slate-100'}`;
        btnNext.innerHTML = `<i class="fa-solid fa-angle-right"></i>`;
        btnNext.disabled = (trangHienTaiSP === tongSoTrang);
        btnNext.addEventListener('click', () => { if (trangHienTaiSP < tongSoTrang) { trangHienTaiSP++; renderBangSanPham(); } });
        cumNutPhanTrang.appendChild(btnNext);
    }

    // ══ 2. MODAL ĐĂNG KÝ MỚI / NỘP LẠI ══
    const modalSP = document.getElementById('modalDangKySP');
    const formSP = document.getElementById('formSubmitSP');
    const tieuDeModalSP = document.getElementById('tieuDeModalSP');
    const khungCanhBaoTuChoi = document.getElementById('khungCanhBaoTuChoiCu');
    const noiDungLyDoTuChoi = document.getElementById('noiDungLyDoTuChoiCu');
    const spIdInput = document.getElementById('spIdDangChon');

    document.getElementById('nutMoModalDangKySP').addEventListener('click', () => {
        formSP.reset();
        spIdInput.value = '';
        khungCanhBaoTuChoi.classList.add('hidden');
        tieuDeModalSP.innerHTML = `<i class="fa-solid fa-pills text-emerald-700"></i> Nộp Đơn Đăng Ký Sản Phẩm Dược Mới`;
        modalSP.classList.remove('hidden');
    });

    document.querySelectorAll('.dong-modal-sp').forEach(btn => {
        btn.addEventListener('click', () => modalSP.classList.add('hidden'));
    });

    function ganSuKienBangSP() {
        // Nộp lại khi bị từ chối
        document.querySelectorAll('.btn-nop-lai').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                const sp = danhSachSanPham.find(x => x.id === id);
                if (!sp) return;

                spIdInput.value = sp.id;
                tieuDeModalSP.innerHTML = `<i class="fa-solid fa-rotate-right text-amber-600"></i> Nộp Lại Đăng Ký: ${sp.tenSanPham}`;

                document.getElementById('nhapTenThuoc').value = sp.tenSanPham;
                document.getElementById('nhapSoDK').value = sp.soDangKy;
                document.getElementById('nhapThanhPhan').value = sp.thanhPhan;
                document.getElementById('nhapHamLuong').value = sp.hamLuong;
                document.getElementById('nhapDangBaoChe').value = sp.dangBaoChe;

                const lanCu = sp.lichSuNop[sp.lichSuNop.length - 1];
                noiDungLyDoTuChoi.textContent = lanCu.lyDoTuChoi;
                khungCanhBaoTuChoi.classList.remove('hidden');

                modalSP.classList.remove('hidden');
            });
        });

        // Mở modal Chi tiết & Lịch sử
        document.querySelectorAll('.btn-xem-chi-tiet-sp').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                hienThiChiTietVaLichSu(id);
            });
        });
    }

    formSP.addEventListener('submit', function (e) {
        e.preventDefault();

        const idSua = spIdInput.value;
        const tenThuoc = document.getElementById('nhapTenThuoc').value.trim();
        const soDK = document.getElementById('nhapSoDK').value.trim().toUpperCase();
        const thanhPhan = document.getElementById('nhapThanhPhan').value.trim();
        const hamLuong = document.getElementById('nhapHamLuong').value.trim();
        const dangBaoChe = document.getElementById('nhapDangBaoChe').value.trim();
        const thoiGian = new Date().toLocaleString('vi-VN');

        if (idSua) {
            const sp = danhSachSanPham.find(x => x.id === idSua);
            if (sp) {
                sp.tenSanPham = tenThuoc;
                sp.soDangKy = soDK;
                sp.thanhPhan = thanhPhan;
                sp.hamLuong = hamLuong;
                sp.dangBaoChe = dangBaoChe;
                sp.trangThai = 'CHO_DUYET';
                sp.txHash = '';
                sp.lichSuNop.push({
                    lan: sp.lichSuNop.length + 1,
                    ngayNop: thoiGian,
                    thongTinKhai: {
                        ten: tenThuoc,
                        soDK: soDK,
                        thanhPhan: thanhPhan,
                        hamLuong: hamLuong,
                        dangBaoChe: dangBaoChe
                    },
                    ketQua: 'CHO_DUYET',
                    nguoiXuLy: 'Đang phân công chuyên viên thẩm định',
                    lyDoTuChoi: ''
                });
                alert(`Đã nộp lại đăng ký cho sản phẩm "${tenThuoc}"! Hồ sơ chuyển về trạng thái Chờ duyệt.`);
            }
        } else {
            const maMoi = 'SP' + Math.floor(100 + Math.random() * 900);
            danhSachSanPham.unshift({
                id: maMoi,
                tenSanPham: tenThuoc,
                soDangKy: soDK,
                thanhPhan: thanhPhan,
                hamLuong: hamLuong,
                dangBaoChe: dangBaoChe,
                trangThai: 'CHO_DUYET',
                txHash: '',
                lichSuNop: [
                    {
                        lan: 1,
                        ngayNop: thoiGian,
                        thongTinKhai: {
                            ten: tenThuoc,
                            soDK: soDK,
                            thanhPhan: thanhPhan,
                            hamLuong: hamLuong,
                            dangBaoChe: dangBaoChe
                        },
                        ketQua: 'CHO_DUYET',
                        nguoiXuLy: 'Đang phân công chuyên viên thẩm định',
                        lyDoTuChoi: ''
                    }
                ]
            });
            alert(`Đã gửi yêu cầu đăng ký sản phẩm mới "${tenThuoc}" đến Cơ quan quản lý!`);
        }

        modalSP.classList.add('hidden');
        renderBangSanPham();
    });

    // ══ 3. MODAL CHI TIẾT SẢN PHẨM & LỊCH SỬ THẨM DUYỆT (FR05) ══
    const modalChiTietSP = document.getElementById('modalChiTietSP');
    let spDangXemChiTiet = null;

    function hienThiChiTietVaLichSu(id) {
        spDangXemChiTiet = danhSachSanPham.find(x => x.id === id);
        if (!spDangXemChiTiet) return;

        // 1. Hiển thị thông tin tổng quan hiện tại
        document.getElementById('tenSPChiTietTitle').textContent = `${spDangXemChiTiet.tenSanPham} (${spDangXemChiTiet.soDangKy})`;
        document.getElementById('modalChiTietTenThuoc').textContent = spDangXemChiTiet.tenSanPham;
        document.getElementById('modalChiTietSoDK').textContent = spDangXemChiTiet.soDangKy;
        document.getElementById('modalChiTietThanhPhan').textContent = spDangXemChiTiet.thanhPhan;
        document.getElementById('modalChiTietHamLuong').textContent = spDangXemChiTiet.hamLuong;
        document.getElementById('modalChiTietDangBaoChe').textContent = spDangXemChiTiet.dangBaoChe;

        const badgeBox = document.getElementById('modalChiTietTrangThai');
        const khungBlockchain = document.getElementById('khungChungCuBlockchainSP');

        if (spDangXemChiTiet.trangThai === 'DA_DUYET') {
            badgeBox.innerHTML = `
                <span class="inline-flex items-center gap-1 font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded text-[10px] border border-emerald-300">
                    <i class="fa-solid fa-circle-check"></i> ĐÃ DUYỆT
                </span>
            `;
            // Hiển thị chứng cứ on-chain
            khungBlockchain.classList.remove('hidden');
            document.getElementById('modalChiTietTxHashSP').textContent = spDangXemChiTiet.txHash;
            document.getElementById('modalChiTietTxLinkSP').href = `${EXPLORER_BASE_URL}${spDangXemChiTiet.txHash}`;
        } else if (spDangXemChiTiet.trangThai === 'CHO_DUYET') {
            badgeBox.innerHTML = `
                <span class="inline-flex items-center gap-1 font-bold text-amber-800 bg-amber-100 px-2 py-0.5 rounded text-[10px] border border-amber-300">
                    <i class="fa-solid fa-clock"></i> CHỜ DUYỆT
                </span>
            `;
            khungBlockchain.classList.add('hidden');
        } else {
            badgeBox.innerHTML = `
                <span class="inline-flex items-center gap-1 font-bold text-red-700 bg-red-100 px-2 py-0.5 rounded text-[10px] border border-red-300">
                    <i class="fa-solid fa-circle-xmark"></i> BỊ TỪ CHỐI
                </span>
            `;
            khungBlockchain.classList.add('hidden');
        }

        // 2. Liệt kê các lần nộp từ cấu trúc yeuCauDangKySanPham
        const containerLichSu = document.getElementById('khungDanhSachLichSuNop');
        containerLichSu.innerHTML = '';

        spDangXemChiTiet.lichSuNop.forEach((itemLichSu) => {
            const card = document.createElement('div');
            card.className = 'p-3.5 rounded-xl border border-slate-200 bg-nenChinh space-y-2';

            let ketQuaHtml = '';
            if (itemLichSu.ketQua === 'DA_DUYET') {
                ketQuaHtml = `<span class="font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded text-[11px]"><i class="fa-solid fa-check"></i> Phê duyệt</span>`;
            } else if (itemLichSu.ketQua === 'TU_CHOI') {
                ketQuaHtml = `<span class="font-bold text-red-600 bg-red-100 px-2 py-0.5 rounded text-[11px]"><i class="fa-solid fa-xmark"></i> Từ chối</span>`;
            } else {
                ketQuaHtml = `<span class="font-bold text-amber-600 bg-amber-100 px-2 py-0.5 rounded text-[11px]"><i class="fa-solid fa-clock"></i> Đang chờ duyệt</span>`;
            }

            card.innerHTML = `
                <div class="flex items-center justify-between border-b border-slate-200 pb-1.5">
                    <span class="font-bold text-slate-800 text-xs">Lần nộp hồ sơ #${itemLichSu.lan}</span>
                    <span class="font-mono text-[11px] text-slate-500"><i class="fa-regular fa-calendar-check mr-1"></i> ${itemLichSu.ngayNop}</span>
                </div>

                <!-- Thông tin khai báo trong lần nộp -->
                <div class="p-2 bg-white rounded-lg border border-slate-200 text-[11px] space-y-1">
                    <div class="text-slate-500 font-semibold uppercase tracking-wider text-[10px]">Dữ liệu hồ sơ khai nộp:</div>
                    <div class="grid grid-cols-2 gap-2 text-slate-700">
                        <div>• Tên: <strong>${itemLichSu.thongTinKhai.ten}</strong> (SĐK: ${itemLichSu.thongTinKhai.soDK})</div>
                        <div>• Thành phần: <strong>${itemLichSu.thongTinKhai.thanhPhan}</strong></div>
                        <div>• Hàm lượng: <strong>${itemLichSu.thongTinKhai.hamLuong}</strong></div>
                        <div>• Dạng bào chế: <strong>${itemLichSu.thongTinKhai.dangBaoChe}</strong></div>
                    </div>
                </div>

                <!-- Kết quả & Người xử lý -->
                <div class="flex flex-wrap items-center justify-between gap-2 pt-1 text-[11px]">
                    <div>Kết quả: ${ketQuaHtml}</div>
                    <div class="text-slate-600">Người xử lý: <strong>${itemLichSu.nguoiXuLy}</strong></div>
                </div>

                <!-- Lý do từ chối (nếu có) -->
                ${itemLichSu.lyDoTuChoi ? `
                    <div class="p-2.5 bg-red-50 text-red-700 rounded-lg border border-red-200 text-[11px] leading-relaxed">
                        <i class="fa-solid fa-triangle-exclamation mr-1"></i> <strong>Lý do từ chối:</strong> ${itemLichSu.lyDoTuChoi}
                    </div>
                ` : ''}
            `;

            containerLichSu.appendChild(card);
        });

        modalChiTietSP.classList.remove('hidden');
    }

    // Nút sao chép txHash của sản phẩm đã duyệt
    document.getElementById('nutSaoChepTxSP').addEventListener('click', function () {
        if (!spDangXemChiTiet || !spDangXemChiTiet.txHash) return;
        navigator.clipboard.writeText(spDangXemChiTiet.txHash).then(() => {
            this.innerHTML = `<i class="fa-solid fa-check text-emerald-600"></i> Đã sao chép`;
            setTimeout(() => {
                this.innerHTML = `<i class="fa-regular fa-copy"></i> Sao chép`;
            }, 1800);
        });
    });

    document.querySelectorAll('.dong-modal-chitiet-sp').forEach(btn => {
        btn.addEventListener('click', () => modalChiTietSP.classList.add('hidden'));
    });

    // Lắng nghe tìm kiếm & lọc
    oTimKiem.addEventListener('input', () => { trangHienTaiSP = 1; renderBangSanPham(); });
    locTrangThai.addEventListener('change', () => { trangHienTaiSP = 1; renderBangSanPham(); });

    sanPhamApi();
});
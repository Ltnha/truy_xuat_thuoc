document.addEventListener('DOMContentLoaded', function () {
    const EXPLORER_BASE_URL = "https://amoy.polygonscan.com/tx/";
    const ITEMS_PER_PAGE = 4;
    let trangHienTai = 1;

    // Helper: Chuyển đổi YYYY-MM-DD sang dd/mm/yyyy
    function dinhDangNgay(ngayYMD) {
        if (!ngayYMD) return '--';
        const parts = ngayYMD.split('-');
        if (parts.length === 3) return `${parts[2]}/${parts[1]}/${parts[0]}`;
        return ngayYMD;
    }

    // Helper: Lấy ngày hiện tại dạng YYYY-MM-DD
    function layNgayHienTaiYMD() {
        return new Date().toISOString().split('T')[0];
    }

    // ══ 1. CSDL MÔ PHỎNG DANH SÁCH LÔ TOÀN HỆ THỐNG ══
    let danhSachLo = [
        {
            id: 1,
            maLo: 'LOT-AMOX-2025-09',
            tenSanPham: 'Amoxicillin 500mg',
            soDangKy: 'VD-19234-15',
            maNSX: 'PHARBACO',
            nhaSanXuat: 'Dược Phẩm Pharbaco',
            hanDung: '2028-09-10', // Còn hạn
            ngaySanXuat: '2025-09-10',
            tongSoLuong: 15000,
            trangThaiLo: 'RECALLED', // ĐÃ THU HỒI
            soBaoCaoNghiVan: 8,
            chuyenGiaoTreo: 0,
            thongTinThuHoi: {
                lyDo: 'Mẫu thuốc lưu không đạt tiêu chuẩn độ hòa tan theo Công văn số 458/QLD của Cục Quản lý Dược.',
                ngayThuHoi: '05/02/2026 16:45',
                nguoiQuyetDinh: 'inspector_01', // Thống nhất inspector_01
                hashBlockchain: '0x71a49f2b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4e5f'
            },
            phanBoKho: [
                { donVi: 'Dược Phẩm Pharbaco (Kho NSX)', vaiTro: 'Nhà sản xuất', giu: 5000, daBan: 0 },
                { donVi: 'Dược Phẩm Vimedimex TP.HCM', vaiTro: 'Nhà phân phối', giu: 9500, daBan: 0 },
                { donVi: 'Nhà Thuốc PharmaCare Cần Thơ', vaiTro: 'Nhà thuốc', giu: 380, daBan: 120 }
            ]
        },
        {
            id: 2,
            maLo: 'LOT-PARA-2026',
            tenSanPham: 'Paracetamol 500mg',
            soDangKy: 'VD-28491-18',
            maNSX: 'MEDIPHARCO',
            nhaSanXuat: 'Dược Phẩm Medipharco',
            hanDung: '2029-01-15', // Còn hạn
            ngaySanXuat: '2026-01-15',
            tongSoLuong: 10000,
            trangThaiLo: 'CREATED', // ĐANG LƯU THÔNG
            soBaoCaoNghiVan: 1,
            chuyenGiaoTreo: 0,
            thongTinThuHoi: null,
            phanBoKho: [
                { donVi: 'Dược Phẩm Medipharco (Kho NSX)', vaiTro: 'Nhà sản xuất', giu: 2000, daBan: 0 },
                { donVi: 'Tổng Công ty Vinapharm', vaiTro: 'Nhà phân phối', giu: 6500, daBan: 0 },
                { donVi: 'Nhà Thuốc PharmaCare Cần Thơ', vaiTro: 'Nhà thuốc', giu: 650, daBan: 850 }
            ]
        },
        {
            id: 3,
            maLo: 'LOT-DES-2026-01',
            tenSanPham: 'Deslotid OPV 5mg',
            soDangKy: 'VD-19234-15',
            maNSX: 'OPV',
            nhaSanXuat: 'Dược Phẩm OPV',
            hanDung: '2028-02-05', // Còn hạn
            ngaySanXuat: '2026-02-05',
            tongSoLuong: 5000,
            trangThaiLo: 'CREATED', // ĐANG LƯU THÔNG
            soBaoCaoNghiVan: 0,
            chuyenGiaoTreo: 0,
            thongTinThuHoi: null,
            phanBoKho: [
                { donVi: 'Dược Phẩm OPV (Kho NSX)', vaiTro: 'Nhà sản xuất', giu: 1000, daBan: 0 },
                { donVi: 'Dược Phẩm CPC1 Hà Nội', vaiTro: 'Nhà phân phối', giu: 3200, daBan: 0 },
                { donVi: 'Nhà Thuốc PharmaCare Cần Thơ', vaiTro: 'Nhà thuốc', giu: 680, daBan: 120 }
            ]
        },
        {
            id: 4,
            maLo: 'LOT-CEFA-2024-EX',
            tenSanPham: 'Cefalexin 500mg',
            soDangKy: 'VD-33012-20',
            maNSX: 'MEDIPHARCO',
            nhaSanXuat: 'Dược Phẩm Medipharco',
            hanDung: '2025-01-01', // ĐÃ HẾT HẠN (so với hiện tại)
            ngaySanXuat: '2022-01-01',
            tongSoLuong: 2000,
            trangThaiLo: 'CREATED', // Chưa thu hồi -> HẾT HẠN
            soBaoCaoNghiVan: 4,
            chuyenGiaoTreo: 1, // Đang có 1 chuyển giao treo (300 hộp)
            thongTinThuHoi: null,
            phanBoKho: [
                { donVi: 'Dược Phẩm Medipharco (Kho NSX)', vaiTro: 'Nhà sản xuất', giu: 0, daBan: 0 },
                { donVi: 'Tổng Công ty Vinapharm', vaiTro: 'Nhà phân phối', giu: 1500, daBan: 0 },
                { donVi: 'Nhà Thuốc PharmaCare Cần Thơ', vaiTro: 'Nhà thuốc', giu: 200, daBan: 300 }
            ]
        }
    ];

    // Helper: Tính số lượng đang thực tế lưu thông trong chuỗi (chưa bán lẻ ra người dân)
    function tinhSoLuongDangLuuThong(lo) {
        if (!lo.phanBoKho) return 0;
        return lo.phanBoKho.reduce((sum, item) => sum + item.giu, 0);
    }

    // ══ 2. RENDER BẢNG & PHÂN TRANG (ÁNH XẠ 4 TRẠNG THÁI GỌN GÀNG) ══
    const thanBang = document.getElementById('thanBangThuHoi');
    const oTimKiem = document.getElementById('oTimKiemThuHoi');
    const locNSX = document.getElementById('locNhaSanXuat');
    const locTrangThai = document.getElementById('locTrangThaiLo');
    const soLuongBadge = document.getElementById('soLuongLoBadge');
    const thongTinPhanTrang = document.getElementById('thongTinPhanTrang');
    const cumNutPhanTrang = document.getElementById('cumNutPhanTrang');

    function renderBangThuHoi() {
        const tuKhoa = oTimKiem.value.trim().toLowerCase();
        const nsxChon = locNSX.value;
        const trangThaiChon = locTrangThai.value;
        const homNay = layNgayHienTaiYMD();

        const danhSachLoc = danhSachLo.filter(item => {
            const matchTuKhoa = item.maLo.toLowerCase().includes(tuKhoa) || item.tenSanPham.toLowerCase().includes(tuKhoa);
            const matchNSX = (nsxChon === 'TAT_CA') || (item.maNSX === nsxChon);

            const biThuHoi = (item.trangThaiLo === 'RECALLED');
            const daHetHan = (item.hanDung < homNay);

            let matchTrangThai = true;
            if (trangThaiChon === 'DA_THU_HOI') matchTrangThai = biThuHoi;
            else if (trangThaiChon === 'HET_HAN') matchTrangThai = (!biThuHoi && daHetHan);
            else if (trangThaiChon === 'DANG_LUU_HANH') matchTrangThai = (!biThuHoi && !daHetHan);

            return matchTuKhoa && matchNSX && matchTrangThai;
        });

        soLuongBadge.textContent = `Hiển thị: ${danhSachLoc.length} lô`;

        const tongSoTrang = Math.ceil(danhSachLoc.length / ITEMS_PER_PAGE) || 1;
        if (trangHienTai > tongSoTrang) trangHienTai = tongSoTrang;

        const batDau = (trangHienTai - 1) * ITEMS_PER_PAGE;
        const ketThuc = batDau + ITEMS_PER_PAGE;
        const duLieuTrang = danhSachLoc.slice(batDau, ketThuc);

        thanBang.innerHTML = '';

        if (duLieuTrang.length === 0) {
            thanBang.innerHTML = `<tr><td colspan="8" class="text-center py-8 text-slate-400 italic">Không tìm thấy lô thuốc nào phù hợp với bộ lọc tìm kiếm.</td></tr>`;
            thongTinPhanTrang.textContent = "Không có bản ghi nào";
            cumNutPhanTrang.innerHTML = '';
            return;
        }

        thongTinPhanTrang.textContent = `Đang hiển thị ${batDau + 1} đến ${Math.min(ketThuc, danhSachLoc.length)} trên tổng số ${danhSachLoc.length} lô`;

        duLieuTrang.forEach(item => {
            const biThuHoi = (item.trangThaiLo === 'RECALLED');
            const daHetHan = (item.hanDung < homNay);
            const dangLuuThong = tinhSoLuongDangLuuThong(item);

            // Xử lý cột trạng thái
            let badgeTrangThai = '';
            if (biThuHoi) {
                badgeTrangThai = `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-red-100 text-red-700 border border-red-300"><i class="fa-solid fa-ban"></i> Đã thu hồi</span>`;
            } else if (daHetHan) {
                badgeTrangThai = `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300"><i class="fa-solid fa-triangle-exclamation"></i> Hết hạn</span>`;
            } else {
                badgeTrangThai = `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300"><i class="fa-solid fa-truck-fast"></i> Đang lưu thông</span>`;
            }

            // Xử lý cột hạn dùng
            let hanDungHtml = `<span class="font-mono text-[11px] text-slate-700">${dinhDangNgay(item.hanDung)}</span>`;
            if (daHetHan && !biThuHoi) {
                hanDungHtml += `<span class="badge-chuan ml-1 px-1.5 py-0.2 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300" title="Đã quá hạn sử dụng - Gợi ý thu hồi">Hết hạn</span>`;
            }

            // Nút thu hồi: Chỉ lô CHƯA THU HỒI mới có nút Thu hồi
            let nutThuHoiHtml = '';
            if (!biThuHoi) {
                nutThuHoiHtml = `
                    <button type="button" class="btn-mo-modal-thu-hoi px-2.5 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 font-bold text-xs transition-all flex items-center gap-1 border border-red-200 shadow-2xs whitespace-nowrap" data-malo="${item.maLo}">
                        <i class="fa-solid fa-ban text-[10px]"></i> Thu hồi
                    </button>
                `;
            }

            const tr = document.createElement('tr');
            tr.className = 'dong-lo-hang transition-colors';
            tr.innerHTML = `
                <td class="py-3 px-3 font-mono font-bold text-slate-900 whitespace-nowrap">${item.maLo}</td>
                <td class="py-3 px-3 font-bold text-slate-900 leading-tight">${item.tenSanPham}</td>
                <td class="py-3 px-3 text-slate-800 font-semibold leading-tight">${item.nhaSanXuat}</td>
                <td class="py-3 px-2.5 text-center whitespace-nowrap">${hanDungHtml}</td>
                <td class="py-3 px-3 text-right whitespace-nowrap">
                    <span class="font-mono font-bold text-indigo-900 block">${item.tongSoLuong.toLocaleString('vi-VN')} hộp</span>
                    <span class="text-[10px] text-slate-500 font-mono">(Lưu thông: ${dangLuuThong.toLocaleString('vi-VN')})</span>
                </td>
                <td class="py-3 px-2.5 text-center whitespace-nowrap">${badgeTrangThai}</td>
                <td class="py-3 px-2.5 text-center whitespace-nowrap">
                    <span class="font-mono font-bold ${item.soBaoCaoNghiVan > 0 ? 'text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200' : 'text-slate-400'}">
                        ${item.soBaoCaoNghiVan} báo cáo
                    </span>
                </td>
                <td class="py-3 px-3 text-center whitespace-nowrap">
                    <div class="inline-flex items-center justify-center gap-1.5">
                        <button type="button" class="btn-xem-chi-tiet-lo px-2.5 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-800 font-bold text-xs transition-all flex items-center gap-1 border border-indigo-200 shadow-2xs whitespace-nowrap" data-malo="${item.maLo}">
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
        btnPrev.addEventListener('click', () => { if (trangHienTai > 1) { trangHienTai--; renderBangThuHoi(); } });
        cumNutPhanTrang.appendChild(btnPrev);

        for (let i = 1; i <= tongSoTrang; i++) {
            const btnPage = document.createElement('button');
            btnPage.className = `w-7 h-7 rounded-lg text-xs font-bold transition-all ${trangHienTai === i ? 'bg-indigo-700 text-white shadow-2xs' : 'border border-slate-200 text-slate-700 hover:bg-slate-100'}`;
            btnPage.textContent = i;
            btnPage.addEventListener('click', () => { trangHienTai = i; renderBangThuHoi(); });
            cumNutPhanTrang.appendChild(btnPage);
        }

        const btnNext = document.createElement('button');
        btnNext.className = `px-2.5 py-1 rounded-lg border text-xs font-bold transition-all ${trangHienTai === tongSoTrang ? 'border-slate-200 text-slate-300 cursor-not-allowed' : 'border-slate-300 text-slate-700 hover:bg-slate-100'}`;
        btnNext.innerHTML = `<i class="fa-solid fa-angle-right"></i>`;
        btnNext.disabled = (trangHienTai === tongSoTrang);
        btnNext.addEventListener('click', () => { if (trangHienTai < tongSoTrang) { trangHienTai++; renderBangThuHoi(); } });
        cumNutPhanTrang.appendChild(btnNext);
    }

    // ══ 3. MODAL CHI TIẾT LÔ THUỐC ══
    const modalDetail = document.getElementById('modalChiTietLo');
    const detailBannerDaThuHoi = document.getElementById('detailBannerDaThuHoi');
    const detailBannerGoiYThuHoiHetHan = document.getElementById('detailBannerGoiYThuHoiHetHan');
    const detailKhungNutThuHoiNhanh = document.getElementById('detailKhungNutThuHoiNhanh');
    let loDangXemDetail = null;

    function moModalChiTietLo(maLo) {
        loDangXemDetail = danhSachLo.find(x => x.maLo === maLo);
        if (!loDangXemDetail) return;

        const homNay = layNgayHienTaiYMD();
        const biThuHoi = (loDangXemDetail.trangThaiLo === 'RECALLED');
        const daHetHan = (loDangXemDetail.hanDung < homNay);
        const dangLuuThong = tinhSoLuongDangLuuThong(loDangXemDetail);

        document.getElementById('detailMaLoTitle').textContent = loDangXemDetail.maLo;

        // Trạng thái badge
        if (biThuHoi) {
            document.getElementById('detailTrangThaiLoBadge').innerHTML = `<span class="badge-chuan px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-red-100 text-red-700 border border-red-300">Đã thu hồi</span>`;
        } else if (daHetHan) {
            document.getElementById('detailTrangThaiLoBadge').innerHTML = `<span class="badge-chuan px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300">Hết hạn</span>`;
        } else {
            document.getElementById('detailTrangThaiLoBadge').innerHTML = `<span class="badge-chuan px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300">Đang lưu thông</span>`;
        }

        document.getElementById('detailTenThuoc').textContent = loDangXemDetail.tenSanPham;
        document.getElementById('detailSoDK').textContent = loDangXemDetail.soDangKy;
        document.getElementById('detailNhaSanXuat').textContent = loDangXemDetail.nhaSanXuat;
        document.getElementById('detailHanDung').textContent = dinhDangNgay(loDangXemDetail.hanDung);
        document.getElementById('detailTongSoLuong').textContent = `${loDangXemDetail.tongSoLuong.toLocaleString('vi-VN')} hộp`;
        document.getElementById('detailDangLuuThong').textContent = `${dangLuuThong.toLocaleString('vi-VN')} hộp`;
        document.getElementById('detailNgaySanXuat').textContent = dinhDangNgay(loDangXemDetail.ngaySanXuat);

        // Báo cáo nghi vấn kèm liên kết chuẩn lọc theo mã lô
        document.getElementById('detailSoBaoCaoNghiVan').textContent = `${loDangXemDetail.soBaoCaoNghiVan} báo cáo`;
        document.getElementById('detailLinkSangNghiVan').href = `../coQuanQuanLy/xuLyNghiVan.php?maLo=${encodeURIComponent(loDangXemDetail.maLo)}`;

        // Banner nếu đã thu hồi
        if (biThuHoi && loDangXemDetail.thongTinThuHoi) {
            detailBannerDaThuHoi.classList.remove('hidden');
            detailBannerGoiYThuHoiHetHan.classList.add('hidden');
            document.getElementById('detailLyDoThuHoiText').textContent = loDangXemDetail.thongTinThuHoi.lyDo;
            document.getElementById('detailNgayThuHoiText').textContent = loDangXemDetail.thongTinThuHoi.ngayThuHoi;
            document.getElementById('detailNguoiQuyetDinhText').textContent = 'inspector_01'; // Thống nhất inspector_01
            document.getElementById('detailTxHashThuHoiText').textContent = loDangXemDetail.thongTinThuHoi.hashBlockchain;
            document.getElementById('detailTxHashThuHoiLink').href = `${EXPLORER_BASE_URL}${loDangXemDetail.thongTinThuHoi.hashBlockchain}`;
            detailKhungNutThuHoiNhanh.innerHTML = '';
            document.getElementById('thCotBanLe').textContent = "Đã bán cho người dân (trước khi thu hồi)";
        } else {
            detailBannerDaThuHoi.classList.add('hidden');
            document.getElementById('thCotBanLe').textContent = "Đã xuất bán lẻ ra người dân";

            if (daHetHan) {
                detailBannerGoiYThuHoiHetHan.classList.remove('hidden');
            } else {
                detailBannerGoiYThuHoiHetHan.classList.add('hidden');
            }

            // Nút thu hồi nhanh ở chân modal
            detailKhungNutThuHoiNhanh.innerHTML = `
                <button type="button" id="nutMoThuHoiTuDetail" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-xl transition-all flex items-center gap-1.5 shadow-md">
                    <i class="fa-solid fa-ban text-[10px]"></i> Ban hành quyết định thu hồi
                </button>
            `;
            document.getElementById('nutMoThuHoiTuDetail').addEventListener('click', () => {
                modalDetail.classList.add('hidden');
                moModalThuHoi(loDangXemDetail.maLo);
            });
        }

        // Bảng phân bổ kho rút gọn
        const thanBangPhanBo = document.getElementById('detailThanBangPhanBoKho');
        thanBangPhanBo.innerHTML = '';
        loDangXemDetail.phanBoKho.forEach(pb => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-slate-50';
            tr.innerHTML = `
                <td class="p-2 font-bold text-slate-800 font-sans text-xs">${pb.donVi}</td>
                <td class="p-2 text-center font-sans">
                    <span class="badge-chuan px-2 py-0.2 rounded text-[10px] font-bold bg-slate-100 text-slate-700">${pb.vaiTro}</span>
                </td>
                <td class="p-2 text-right font-bold text-indigo-900">${pb.giu.toLocaleString('vi-VN')} hộp</td>
                <td class="p-2 text-right ${pb.daBan > 0 ? (biThuHoi ? 'text-red-700 font-bold' : 'text-slate-600') : 'text-slate-400'}">
                    ${pb.daBan > 0 ? pb.daBan.toLocaleString('vi-VN') + ' hộp' : '—'}
                </td>
            `;
            thanBangPhanBo.appendChild(tr);
        });

        modalDetail.classList.remove('hidden');
    }

    document.querySelectorAll('.dong-modal-detail').forEach(btn => {
        btn.addEventListener('click', () => modalDetail.classList.add('hidden'));
    });

    document.getElementById('nutCopyDetailTxThuHoi').addEventListener('click', function () {
        if (!loDangXemDetail || !loDangXemDetail.thongTinThuHoi) return;
        navigator.clipboard.writeText(loDangXemDetail.thongTinThuHoi.hashBlockchain).then(() => {
            this.innerHTML = `<i class="fa-solid fa-check text-emerald-600"></i>`;
            setTimeout(() => { this.innerHTML = `<i class="fa-regular fa-copy"></i>`; }, 1500);
        });
    });

    // ══ 4. MODAL THU HỒI LÔ THUỐC ══
    const modalThuHoi = document.getElementById('modalThuHoiLoHang');
    const manHinhFormThuHoi = document.getElementById('manHinhFormThuHoi');
    const manHinhDangGhiBlockchain = document.getElementById('manHinhDangGhiBlockchain');
    const manHinhLoiBlockchain = document.getElementById('manHinhLoiBlockchain');
    const nhapLyDoThuHoi = document.getElementById('nhapLyDoThuHoiLo');
    const demKyTuThuHoi = document.getElementById('demKyTuThuHoi');
    const nutXacNhanThuHoi = document.getElementById('nutXacNhanThuHoiBlockchain');
    const nutThuLaiThuHoi = document.getElementById('nutThuLaiThuHoiBlockchain');
    const thuHoiKhungChuyenGiaoTreo = document.getElementById('thuHoiKhungChuyenGiaoTreo');
    const thuHoiChuyenGiaoTreoText = document.getElementById('thuHoiChuyenGiaoTreoText');
    let loDangThuHoi = null;

    function moModalThuHoi(maLo) {
        loDangThuHoi = danhSachLo.find(x => x.maLo === maLo);
        if (!loDangThuHoi) return;

        document.getElementById('thuHoiModalMaLo').textContent = loDangThuHoi.maLo;
        document.getElementById('thuHoiModalTenThuoc').textContent = loDangThuHoi.tenSanPham;
        document.getElementById('thuHoiModalNSX').textContent = loDangThuHoi.nhaSanXuat;

        // Phân bổ gọn gàng không scroll dài
        const containerBenGiu = document.getElementById('thuHoiModalDanhSachBenGiu');
        containerBenGiu.innerHTML = '';
        loDangThuHoi.phanBoKho.forEach(pb => {
            const div = document.createElement('div');
            div.className = 'flex justify-between items-center py-0.5';
            div.innerHTML = `
                <span class="text-slate-700 font-sans truncate mr-2">• ${pb.donVi}:</span>
                <span class="font-bold text-slate-900 shrink-0">${pb.giu.toLocaleString('vi-VN')} hộp</span>
            `;
            containerBenGiu.appendChild(div);
        });

        // Cảnh báo số chuyển giao đang chờ xác nhận
        if (loDangThuHoi.chuyenGiaoTreo > 0) {
            thuHoiKhungChuyenGiaoTreo.classList.remove('hidden');
            thuHoiChuyenGiaoTreoText.innerHTML = `Hiện tại đang có <strong>${loDangThuHoi.chuyenGiaoTreo} đợt chuyển giao lô hàng chờ xác nhận</strong>. Sau khi ban hành quyết định thu hồi, các giao dịch này sẽ bị vô hiệu hóa.`;
        } else {
            thuHoiKhungChuyenGiaoTreo.classList.add('hidden');
        }

        nhapLyDoThuHoi.value = '';
        demKyTuThuHoi.textContent = '0/500';
        capNhatTrangThaiNutThuHoi();

        manHinhFormThuHoi.classList.remove('hidden');
        manHinhDangGhiBlockchain.classList.add('hidden');
        manHinhLoiBlockchain.classList.add('hidden');
        modalThuHoi.classList.remove('hidden');
    }

    function capNhatTrangThaiNutThuHoi() {
        const len = nhapLyDoThuHoi.value.trim().length;
        demKyTuThuHoi.textContent = `${len}/500`;

        if (len > 0) {
            nutXacNhanThuHoi.disabled = false;
            nutXacNhanThuHoi.className = "px-6 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl shadow-md transition-all flex items-center gap-1.5 cursor-pointer";
        } else {
            nutXacNhanThuHoi.disabled = true;
            nutXacNhanThuHoi.className = "px-6 py-2 bg-slate-200 text-slate-400 font-bold rounded-xl transition-all flex items-center gap-1.5 cursor-not-allowed shadow-none";
        }
    }

    nhapLyDoThuHoi.addEventListener('input', capNhatTrangThaiNutThuHoi);

    document.querySelectorAll('.dong-modal-thuhoi').forEach(btn => {
        btn.addEventListener('click', () => modalThuHoi.classList.add('hidden'));
    });

    function thucHienGhiBlockchainThuHoi() {
        const lyDo = nhapLyDoThuHoi.value.trim();
        if (!lyDo || !loDangThuHoi) return;

        manHinhFormThuHoi.classList.add('hidden');
        manHinhDangGhiBlockchain.classList.remove('hidden');
        manHinhLoiBlockchain.classList.add('hidden');

        setTimeout(() => {
            // Giả lập tỉ lệ thành công (thanhCong = true)
            const thanhCongOnChain = true;

            if (thanhCongOnChain) {
                const now = new Date();
                const thoiGianStr = `${String(now.getDate()).padStart(2,'0')}/${String(now.getMonth()+1).padStart(2,'0')}/${now.getFullYear()} ${String(now.getHours()).padStart(2,'0')}:${String(now.getMinutes()).padStart(2,'0')}`;
                const randomTx = '0x' + Array.from({length: 64}, () => Math.floor(Math.random()*16).toString(16)).join('');

                loDangThuHoi.trangThaiLo = 'RECALLED';
                loDangThuHoi.thongTinThuHoi = {
                    lyDo: lyDo,
                    ngayThuHoi: thoiGianStr,
                    nguoiQuyetDinh: 'inspector_01',
                    hashBlockchain: randomTx
                };

                modalThuHoi.classList.add('hidden');

                alert(`Ban hành quyết định thu hồi lô [${loDangThuHoi.maLo}] thành công!\n- Quyết định đã được ghi lên blockchain Polygon Amoy.\n- Nhà thuốc không thể bán các đơn vị thuộc lô này nữa, người tiêu dùng tra cứu sẽ thấy "Đã thu hồi".\n- Mã băm on-chain: ${randomTx.substring(0, 18)}...`);
                renderBangThuHoi();
            } else {
                // Thất bại: Không đổi trạng thái lô, cho thử lại
                manHinhDangGhiBlockchain.classList.add('hidden');
                manHinhLoiBlockchain.classList.remove('hidden');
            }
        }, 1200);
    }

    nutXacNhanThuHoi.addEventListener('click', thucHienGhiBlockchainThuHoi);
    nutThuLaiThuHoi.addEventListener('click', thucHienGhiBlockchainThuHoi);

    // ══ 5. GẮN SỰ KIỆN NÚT TRÊN BẢNG ══
    function ganSuKienNutBang() {
        document.querySelectorAll('.btn-xem-chi-tiet-lo').forEach(btn => {
            btn.addEventListener('click', function () {
                const malo = this.getAttribute('data-malo');
                moModalChiTietLo(malo);
            });
        });

        document.querySelectorAll('.btn-mo-modal-thu-hoi').forEach(btn => {
            btn.addEventListener('click', function () {
                const malo = this.getAttribute('data-malo');
                moModalThuHoi(malo);
            });
        });
    }

    // ══ 6. LẮNG NGHE TÌM KIẾM & BỘ LỌC ══
    oTimKiem.addEventListener('input', () => { trangHienTai = 1; renderBangThuHoi(); });
    locNSX.addEventListener('change', () => { trangHienTai = 1; renderBangThuHoi(); });
    locTrangThai.addEventListener('change', () => { trangHienTai = 1; renderBangThuHoi(); });

    // Khởi chạy ban đầu
    renderBangThuHoi();
});
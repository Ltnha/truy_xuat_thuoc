document.addEventListener('DOMContentLoaded', function () {
    const EXPLORER_BASE_URL = "https://amoy.polygonscan.com/tx/";
    const ITEMS_PER_PAGE = 4;
    let trangHienTai = 1;

    const TAI_KHOAN_CQQL_DANG_NHAP = 'inspector_01';

    let danhSachBaoCao = [
        {
            maBaoCao: 'BC-2026-001',
            loThuocId: 'LOT-AMOX-2025-09',
            maLoKhaiBao: 'LOT-AMOX-2025-09',
            tenSanPham: 'Amoxicillin 500mg',
            nguonLoai: 'TO_CHUC',
            nguoiBaoCao: 'Nhà Thuốc PharmaCare Cần Thơ',
            vaiTroNguoiGui: 'Nhà thuốc',
            ngayGui: '02/02/2026',
            trangThai: 'DA_XU_LY',
            ketQuaLoai: 'THU_HOI',
            lyDo: 'Hạn sử dụng bị sửa đổi / tẩy xóa',
            moTa: 'Phát hiện tem niêm phong trên nắp hộp có dấu hiệu bị bóc dán đè. Hạn sử dụng trên vỉ thuốc in đè mực khác với nhãn ngoài vỏ hộp.',
            danhSachMinhChung: [
                { ten: 'anh_tem_dan_de.jpg', loai: 'image', url: 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=600&auto=format&fit=crop&q=60' },
                { ten: 'bien_ban_kiem_tra.pdf', loai: 'pdf', url: '#' }
            ],
            thongTinLoOnChain: {
                tonTaiOnChain: true,
                soDangKy: 'VD-19234-15',
                nhaSanXuat: 'Dược Phẩm Pharbaco',
                hanDung: '10/09/2028',
                trangThaiLo: 'RECALLED',
                soLuongDaBanLe: 120,
                phanBoKho: [
                    { donVi: 'Dược Phẩm Pharbaco (Kho NSX)', giu: 5000 },
                    { donVi: 'Dược Phẩm Vimedimex TP.HCM', giu: 9500 },
                    { donVi: 'Nhà Thuốc PharmaCare Cần Thơ', giu: 380 }
                ],
                timelineChuyenGiao: [
                    { chặng: 1, tu: 'Dược Phẩm Pharbaco (NSX)', den: 'Dược Phẩm Vimedimex TP.HCM (NPP)', soLuong: 15000, tgKhoiTao: '10/09/2025 08:00', tgXacNhan: '10/09/2025 09:30', trangThaiGiaoDich: 'Đã nhận', daXuatBanLe: 0, txHash: '0x1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0b1c2d3e4f5a6b7c8d9e0f1a2b' },
                    { chặng: 2, tu: 'Dược Phẩm Vimedimex TP.HCM', den: 'Nhà Thuốc PharmaCare Cần Thơ', soLuong: 500, tgKhoiTao: '15/01/2026 07:15', tgXacNhan: '15/01/2026 08:30', trangThaiGiaoDich: 'Đã nhận', daXuatBanLe: 120, txHash: '0x71a49f2b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4e5f' }
                ]
            },
            ketQuaXuLy: {
                quyetDinh: 'THU_HOI',
                nguoiXuLy: 'inspector_01',
                ngayXuLy: '05/02/2026 16:45',
                lyDo: 'Mẫu thuốc lưu không đạt tiêu chuẩn độ hòa tan theo Công văn số 458/QLD của Cục Quản lý Dược.',
                txHashThuHoi: '0x71a49f2b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4e5f'
            }
        },
        {
            maBaoCao: 'BC-2026-002',
            loThuocId: 'LOT-PARA-2026',
            maLoKhaiBao: 'LOT-PARA-2026',
            tenSanPham: 'Paracetamol 500mg',
            nguonLoai: 'TO_CHUC',
            nguoiBaoCao: 'Tổng Công ty Dược Việt Nam - Vinapharm',
            vaiTroNguoiGui: 'Nhà phân phối',
            ngayGui: '06/02/2026',
            trangThai: 'DA_XU_LY',
            ketQuaLoai: 'TU_CHOI',
            lyDo: 'Bao bì có dấu hiệu làm giả / in đè nhãn',
            moTa: 'Bao bì một kiện thuốc thùng số 04 bị móp méo và trầy xước mực in trong quá trình vận chuyển, nghi ngờ nhãn không chính hãng.',
            danhSachMinhChung: [
                { ten: 'anh_vo_hop_mop.png', loai: 'image', url: 'https://images.unsplash.com/photo-1471864190281-a93a3070b6de?w=600&auto=format&fit=crop&q=60' }
            ],
            thongTinLoOnChain: {
                tonTaiOnChain: true,
                soDangKy: 'VD-28491-18',
                nhaSanXuat: 'Dược Phẩm Medipharco',
                hanDung: '15/01/2029',
                trangThaiLo: 'CREATED',
                soLuongDaBanLe: 850,
                phanBoKho: [
                    { donVi: 'Dược Phẩm Medipharco (Kho NSX)', giu: 2000 },
                    { donVi: 'Tổng Công ty Vinapharm', giu: 6500 },
                    { donVi: 'Nhà Thuốc PharmaCare Cần Thơ', giu: 650 }
                ],
                timelineChuyenGiao: [
                    { chặng: 1, tu: 'Dược Phẩm Medipharco (NSX)', den: 'Tổng Công ty Vinapharm (NPP)', soLuong: 10000, tgKhoiTao: '15/01/2026 09:00', tgXacNhan: '15/01/2026 10:00', trangThaiGiaoDich: 'Đã nhận', daXuatBanLe: 850, txHash: '0x3a4b5c6d7e8f9a0b1c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b' }
                ]
            },
            ketQuaXuLy: {
                quyetDinh: 'TU_CHOI',
                nguoiXuLy: 'inspector_01',
                ngayXuLy: '08/02/2026 11:20',
                lyDo: 'Đối soát mẫu lưu và thẩm định đối chứng từ Nhà sản xuất xác nhận sản phẩm chính hãng. Vết trầy xước do va chạm cơ học trong vận chuyển, không ảnh hưởng chất lượng thuốc bên trong.',
                txHashThuHoi: null
            }
        },
        {
            maBaoCao: 'BC-2026-003',
            loThuocId: 'LOT-AMOX-2025-09',
            maLoKhaiBao: 'LOT-AMOX-2025-09',
            tenSanPham: 'Amoxicillin 500mg',
            nguonLoai: 'NGUOI_TIEU_DUNG',
            nguoiBaoCao: 'Người tiêu dùng (không tài khoản)',
            vaiTroNguoiGui: null,
            ngayGui: '07/02/2026',
            trangThai: 'DANG_XU_LY',
            ketQuaLoai: null,
            lyDo: 'Thuốc có dấu hiệu biến chất / đổi màu / ẩm mốc',
            moTa: 'Mua thuốc tại quầy về mở vỉ thấy viên nang bị chảy nước và bột thuốc đổi màu sẫm bất thường.',
            danhSachMinhChung: [
                { ten: 'anh_vien_thuoc_doi_mau.jpg', loai: 'image', url: 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=600&auto=format&fit=crop&q=60' }
            ],
            thongTinLoOnChain: {
                tonTaiOnChain: true,
                soDangKy: 'VD-19234-15',
                nhaSanXuat: 'Dược Phẩm Pharbaco',
                hanDung: '10/09/2028',
                trangThaiLo: 'RECALLED',
                soLuongDaBanLe: 120,
                phanBoKho: [
                    { donVi: 'Dược Phẩm Pharbaco (Kho NSX)', giu: 5000 },
                    { donVi: 'Dược Phẩm Vimedimex TP.HCM', giu: 9500 },
                    { donVi: 'Nhà Thuốc PharmaCare Cần Thơ', giu: 380 }
                ],
                timelineChuyenGiao: [
                    { chặng: 1, tu: 'Dược Phẩm Pharbaco (NSX)', den: 'Dược Phẩm Vimedimex TP.HCM', soLuong: 15000, tgKhoiTao: '10/09/2025 08:00', tgXacNhan: '10/09/2025 09:30', trangThaiGiaoDich: 'Đã nhận', daXuatBanLe: 0, txHash: '0x1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0b1c2d3e4f5a6b7c8d9e0f1a2b' },
                    { chặng: 2, tu: 'Dược Phẩm Vimedimex TP.HCM', den: 'Nhà Thuốc PharmaCare Cần Thơ', soLuong: 500, tgKhoiTao: '15/01/2026 07:15', tgXacNhan: '15/01/2026 08:30', trangThaiGiaoDich: 'Đã nhận', daXuatBanLe: 120, txHash: '0x71a49f2b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4e5f' }
                ]
            },
            ketQuaXuLy: null
        },
        {
            maBaoCao: 'BC-2026-004',
            loThuocId: null,
            maLoKhaiBao: 'LOT-FAKE-9999',
            tenSanPham: 'Không xác định',
            nguonLoai: 'NGUOI_TIEU_DUNG',
            nguoiBaoCao: 'Người tiêu dùng (không tài khoản)',
            vaiTroNguoiGui: null,
            ngayGui: '09/02/2026',
            trangThai: 'DA_GUI',
            ketQuaLoai: null,
            lyDo: 'Mã QR không quét được hoặc không hợp lệ',
            moTa: 'Quét mã QR tem thùng bằng ứng dụng báo mã không tồn tại trên hệ thống Cục Quản lý Dược.',
            danhSachMinhChung: [
                { ten: 'anh_quet_loi_qr.jpg', loai: 'image', url: 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=600&auto=format&fit=crop&q=60' }
            ],
            thongTinLoOnChain: {
                tonTaiOnChain: false
            },
            ketQuaXuLy: null
        }
    ];

    function taoBadgeTrangThai(item) {
        if (item.trangThai === 'DA_XU_LY') {
            if (item.ketQuaLoai === 'THU_HOI') {
                return `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-red-100 text-red-700 border border-red-300"><i class="fa-solid fa-ban"></i> Đã xử lý - Thu hồi</span>`;
            } else if (item.ketQuaLoai === 'GHI_NHAN') {
                return `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-100 text-indigo-800 border border-indigo-300"><i class="fa-solid fa-shield-cat"></i> Đã xử lý - Ghi nhận ngoại vi</span>`;
            } else {
                return `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-slate-100 text-slate-700 border border-slate-300"><i class="fa-solid fa-circle-xmark"></i> Đã xử lý - Từ chối</span>`;
            }
        } else if (item.trangThai === 'DANG_XU_LY') {
            return `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300"><i class="fa-solid fa-clock"></i> Đang xử lý</span>`;
        } else {
            return `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-cyan-100 text-cyan-800 border border-cyan-300"><i class="fa-regular fa-paper-plane"></i> Đã gửi</span>`;
        }
    }

    const thanBang = document.getElementById('thanBangNghiVan');
    const oTimKiem = document.getElementById('oTimKiemNghiVan');
    const locTrangThai = document.getElementById('locTrangThaiBaoCao');
    const locNguon = document.getElementById('locNguonBaoCao');
    const soLuongBadge = document.getElementById('soLuongBaoCaoBadge');
    const thongTinPhanTrang = document.getElementById('thongTinPhanTrang');
    const cumNutPhanTrang = document.getElementById('cumNutPhanTrang');

    const urlParams = new URLSearchParams(window.location.search);
    const maLoParam = urlParams.get('maLo');
    if (maLoParam) {
        oTimKiem.value = maLoParam;
    }

    function renderBangNghiVan() {
        const tuKhoa = oTimKiem.value.trim().toLowerCase();
        const trangThaiChon = locTrangThai.value;
        const nguonChon = locNguon.value;

        const danhSachLoc = danhSachBaoCao.filter(item => {
            const matchTuKhoa = item.maBaoCao.toLowerCase().includes(tuKhoa) || 
                                item.maLoKhaiBao.toLowerCase().includes(tuKhoa) || 
                                item.tenSanPham.toLowerCase().includes(tuKhoa);
            const matchTrangThai = (trangThaiChon === 'TAT_CA') || (item.trangThai === trangThaiChon);
            const matchNguon = (nguonChon === 'TAT_CA') || (item.nguonLoai === nguonChon);
            return matchTuKhoa && matchTrangThai && matchNguon;
        });

        soLuongBadge.textContent = `Hiển thị: ${danhSachLoc.length} báo cáo`;

        const tongSoTrang = Math.ceil(danhSachLoc.length / ITEMS_PER_PAGE) || 1;
        if (trangHienTai > tongSoTrang) trangHienTai = tongSoTrang;

        const batDau = (trangHienTai - 1) * ITEMS_PER_PAGE;
        const ketThuc = batDau + ITEMS_PER_PAGE;
        const duLieuTrang = danhSachLoc.slice(batDau, ketThuc);

        thanBang.innerHTML = '';

        if (duLieuTrang.length === 0) {
            thanBang.innerHTML = `<tr><td colspan="7" class="text-center py-8 text-slate-400 italic">Không tìm thấy báo cáo nghi vấn nào phù hợp với bộ lọc.</td></tr>`;
            thongTinPhanTrang.textContent = "Không có bản ghi nào";
            cumNutPhanTrang.innerHTML = '';
            return;
        }

        thongTinPhanTrang.textContent = `Đang hiển thị ${batDau + 1} đến ${Math.min(ketThuc, danhSachLoc.length)} trên tổng số ${danhSachLoc.length} báo cáo`;

        duLieuTrang.forEach(item => {
            const daXuLy = (item.trangThai === 'DA_XU_LY');

            let nguoiBaoCaoHtml = '';
            if (item.nguonLoai === 'NGUOI_TIEU_DUNG') {
                nguoiBaoCaoHtml = `
                    <span class="block font-bold text-slate-800">${item.nguoiBaoCao}</span>
                    <span class="badge-chuan px-1.5 py-0.2 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">Người tiêu dùng</span>
                `;
            } else {
                nguoiBaoCaoHtml = `
                    <span class="block font-bold text-slate-900">${item.nguoiBaoCao}</span>
                    <span class="badge-chuan px-1.5 py-0.2 rounded text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">${item.vaiTroNguoiGui || 'Tổ chức'}</span>
                `;
            }

            const tr = document.createElement('tr');
            tr.className = 'dong-nghi-van transition-colors';
            tr.innerHTML = `
                <td class="py-3 px-3 font-mono font-bold text-cyan-900 whitespace-nowrap">${item.maBaoCao}</td>
                <td class="py-3 px-3 font-mono font-bold text-slate-900 whitespace-nowrap">${item.maLoKhaiBao}</td>
                <td class="py-3 px-3 font-semibold text-slate-900 leading-tight">${item.tenSanPham}</td>
                <td class="py-3 px-3 leading-tight">${nguoiBaoCaoHtml}</td>
                <td class="py-3 px-2.5 text-center font-mono text-[11px] text-slate-600 whitespace-nowrap">${item.ngayGui}</td>
                <td class="py-3 px-2.5 text-center whitespace-nowrap">${taoBadgeTrangThai(item)}</td>
                <td class="py-3 px-3 text-center whitespace-nowrap">
                    <button type="button" class="btn-mo-panel-xuly px-3 py-1.5 rounded-lg text-xs font-bold transition-all shadow-2xs flex items-center justify-center gap-1.5 mx-auto ${daXuLy ? 'bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300' : 'bg-indigo-700 hover:bg-indigo-800 text-white'}" data-mabc="${item.maBaoCao}">
                        ${daXuLy ? '<i class="fa-solid fa-eye text-indigo-700"></i> Xem' : '<i class="fa-solid fa-gavel"></i> Xử lý'}
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
        btnPrev.addEventListener('click', () => { if (trangHienTai > 1) { trangHienTai--; renderBangNghiVan(); } });
        cumNutPhanTrang.appendChild(btnPrev);

        for (let i = 1; i <= tongSoTrang; i++) {
            const btnPage = document.createElement('button');
            btnPage.className = `w-7 h-7 rounded-lg text-xs font-bold transition-all ${trangHienTai === i ? 'bg-indigo-700 text-white shadow-2xs' : 'border border-slate-200 text-slate-700 hover:bg-slate-100'}`;
            btnPage.textContent = i;
            btnPage.addEventListener('click', () => { trangHienTai = i; renderBangNghiVan(); });
            cumNutPhanTrang.appendChild(btnPage);
        }

        const btnNext = document.createElement('button');
        btnNext.className = `px-2.5 py-1 rounded-lg border text-xs font-bold transition-all ${trangHienTai === tongSoTrang ? 'border-slate-200 text-slate-300 cursor-not-allowed' : 'border-slate-300 text-slate-700 hover:bg-slate-100'}`;
        btnNext.innerHTML = `<i class="fa-solid fa-angle-right"></i>`;
        btnNext.disabled = (trangHienTai === tongSoTrang);
        btnNext.addEventListener('click', () => { if (trangHienTai < tongSoTrang) { trangHienTai++; renderBangNghiVan(); } });
        cumNutPhanTrang.appendChild(btnNext);
    }

    const modalXuLy = document.getElementById('modalXuLyBaoCao');
    const panelTheKetQuaDaXuLy = document.getElementById('panelTheKetQuaDaXuLy');
    const panelCumNutQuyetDinh = document.getElementById('panelCumNutQuyetDinh');
    const nutMoModalThuHoiTuBC = document.getElementById('nutMoModalThuHoiTuBaoCao');
    const nutXacNhanGhiNhanLoDaThuHoi = document.getElementById('nutXacNhanGhiNhanLoDaThuHoi');
    const nutMoModalGhiNhanHangGia = document.getElementById('nutMoModalGhiNhanHangGia');
    const nutGopXuLyCungLo = document.getElementById('nutGopXuLyCungLo');
    let baoCaoDangXuLy = null;

    function moPanelXuLyBaoCao(maBC) {
        baoCaoDangXuLy = danhSachBaoCao.find(x => x.maBaoCao === maBC);
        if (!baoCaoDangXuLy) return;

        if (baoCaoDangXuLy.trangThai === 'DA_GUI') {
            baoCaoDangXuLy.trangThai = 'DANG_XU_LY';
            renderBangNghiVan();
        }

        const daXuLy = (baoCaoDangXuLy.trangThai === 'DA_XU_LY');

        document.getElementById('panelMaBaoCaoTitle').textContent = baoCaoDangXuLy.maBaoCao;
        document.getElementById('panelTrangThaiBadge').innerHTML = taoBadgeTrangThai(baoCaoDangXuLy);

        document.getElementById('panelNguoiGuiText').textContent = baoCaoDangXuLy.nguoiBaoCao;
        document.getElementById('panelThoiDiemGui').textContent = baoCaoDangXuLy.ngayGui;
        document.getElementById('panelMaLoText').textContent = baoCaoDangXuLy.maLoKhaiBao;
        document.getElementById('panelLyDoNghiVan').textContent = baoCaoDangXuLy.lyDo;
        document.getElementById('panelMoTaChiTiet').textContent = baoCaoDangXuLy.moTa;

        const nguonBadge = (baoCaoDangXuLy.nguonLoai === 'NGUOI_TIEU_DUNG')
            ? `<span class="badge-chuan px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800">Người tiêu dùng</span>`
            : `<span class="badge-chuan px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">Tổ chức (${baoCaoDangXuLy.vaiTroNguoiGui})</span>`;
        document.getElementById('panelNguonGuiBadge').innerHTML = nguonBadge;

        const containerMC = document.getElementById('panelDanhSachMinhChung');
        containerMC.innerHTML = '';
        if (baoCaoDangXuLy.danhSachMinhChung && baoCaoDangXuLy.danhSachMinhChung.length > 0) {
            baoCaoDangXuLy.danhSachMinhChung.forEach(mc => {
                const cardMC = document.createElement('div');
                cardMC.className = 'p-2 bg-white rounded-lg border border-slate-200 flex items-center justify-between text-xs';
                const icon = mc.loai === 'pdf' ? 'fa-solid fa-file-pdf text-red-500' : 'fa-solid fa-file-image text-emerald-600';
                cardMC.innerHTML = `
                    <div class="flex items-center gap-2 truncate pr-2">
                        <i class="${icon} text-sm"></i>
                        <span class="font-bold text-slate-800 truncate" title="${mc.ten}">${mc.ten}</span>
                    </div>
                    <button type="button" class="px-2 py-0.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-800 rounded font-bold text-[10px] shrink-0" onclick="alert('Mở kiểm tra quyền truy cập tệp minh chứng: ${mc.ten}')">
                        Xem
                    </button>
                `;
                containerMC.appendChild(cardMC);
            });
        } else {
            containerMC.innerHTML = `<span class="text-slate-400 italic">Không có tệp đính kèm.</span>`;
        }

        const onChain = baoCaoDangXuLy.thongTinLoOnChain;
        const canhBaoKhongTonTai = document.getElementById('panelCanhBaoLoKhongTonTai');
        const khungThongTinLo = document.getElementById('panelKhungThongTinLoOnChain');
        const khungTimeline = document.getElementById('panelKhungTimelineChuyenGiao');
        const timelineContainer = document.getElementById('panelDanhSachTimeline');

        if (!onChain || !onChain.tonTaiOnChain) {
            canhBaoKhongTonTai.classList.remove('hidden');
            khungThongTinLo.classList.add('hidden');
            khungTimeline.classList.add('hidden');
            document.getElementById('panelTrangThaiOnChainLoBadge').innerHTML = `<span class="badge-chuan px-2 py-0.5 rounded text-[10px] font-extrabold bg-red-100 text-red-700 border border-red-300">Không tồn tại on-chain</span>`;
        } else {
            canhBaoKhongTonTai.classList.add('hidden');
            khungThongTinLo.classList.remove('hidden');
            khungTimeline.classList.remove('hidden');

            const loStatusBadge = (onChain.trangThaiLo === 'RECALLED')
                ? `<span class="badge-chuan px-2 py-0.5 rounded text-[10px] font-extrabold bg-red-100 text-red-700 border border-red-300"><i class="fa-solid fa-ban"></i> Đã thu hồi</span>`
                : `<span class="badge-chuan px-2 py-0.5 rounded text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300"><i class="fa-solid fa-check"></i> Đang lưu thông</span>`;
            document.getElementById('panelTrangThaiOnChainLoBadge').innerHTML = loStatusBadge;

            document.getElementById('panelOnChainTenSP').textContent = baoCaoDangXuLy.tenSanPham;
            document.getElementById('panelOnChainSoDK').textContent = onChain.soDangKy;
            document.getElementById('panelOnChainNSX').textContent = onChain.nhaSanXuat;
            document.getElementById('panelOnChainHanDung').textContent = onChain.hanDung;
            document.getElementById('panelOnChainSoLuongDaBanLe').textContent = `${onChain.soLuongDaBanLe.toLocaleString('vi-VN')} hộp`;

            timelineContainer.innerHTML = '';
            onChain.timelineChuyenGiao.forEach(item => {
                const hexRutGon = `${item.txHash.substring(0, 10)}...${item.txHash.substring(item.txHash.length - 8)}`;
                const ttGiaoDichBadge = (item.trangThaiGiaoDich === 'Đã nhận')
                    ? `<span class="badge-chuan px-1.5 py-0.2 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">Đã nhận</span>`
                    : `<span class="badge-chuan px-1.5 py-0.2 rounded text-[10px] font-bold bg-amber-100 text-amber-800">Chờ xác nhận</span>`;

                const cardTL = document.createElement('div');
                cardTL.className = 'p-2.5 bg-white rounded-xl border border-indigo-100 space-y-1.5 text-xs';
                cardTL.innerHTML = `
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-indigo-950 flex items-center gap-1.5">
                            <i class="fa-solid fa-truck-moving text-indigo-700"></i> Chặng ${item.chặng}: ${item.tu} <i class="fa-solid fa-arrow-right text-[9px] text-slate-400"></i> ${item.den}
                        </span>
                        <div class="flex items-center gap-2">
                            ${ttGiaoDichBadge}
                            <span class="font-mono font-bold text-slate-900">${item.soLuong.toLocaleString('vi-VN')} hộp</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-[10px] text-slate-500 pt-1 border-t border-slate-100">
                        <div>Khởi tạo: <strong class="font-mono text-slate-700">${item.tgKhoiTao}</strong></div>
                        <div>Xác nhận: <strong class="font-mono text-slate-700">${item.tgXacNhan || 'Đang chờ...'}</strong></div>
                    </div>
                    <div class="flex items-center justify-between text-[11px] pt-1 border-t border-slate-100">
                        <span class="text-slate-500 text-[10px]">Đã bán lẻ (DISPENSED): <strong class="font-mono text-red-700">${item.daXuatBanLe} hộp</strong></span>
                        <div class="inline-flex items-center gap-1.5">
                            <a href="${EXPLORER_BASE_URL}${item.txHash}" target="_blank" class="font-mono text-cyan-800 hover:underline flex items-center gap-1 font-bold">
                                ${hexRutGon} <i class="fa-solid fa-arrow-up-right-from-square text-[9px] text-slate-400"></i>
                            </a>
                            <button type="button" class="btn-copy-tx-tl p-0.5 text-slate-400 hover:text-slate-700" data-tx="${item.txHash}" title="Sao chép">
                                <i class="fa-regular fa-copy"></i>
                            </button>
                        </div>
                    </div>
                `;
                timelineContainer.appendChild(cardTL);
            });
        }

        const danhSachCungLo = danhSachBaoCao.filter(x => x.maLoKhaiBao === baoCaoDangXuLy.maLoKhaiBao && x.maBaoCao !== baoCaoDangXuLy.maBaoCao);
        const containerCungLo = document.getElementById('panelDanhSachBaoCaoCungLo');
        const noteCungLo = document.getElementById('panelSoLuongCungLoNote');
        containerCungLo.innerHTML = '';

        const danhSachCungLoChoXuLy = danhSachCungLo.filter(x => x.trangThai !== 'DA_XU_LY');

        if (danhSachCungLo.length === 0) {
            noteCungLo.textContent = '(Chưa có báo cáo nào khác)';
            containerCungLo.innerHTML = `<div class="p-2 bg-slate-50 rounded-lg text-slate-400 italic text-center">Chỉ có duy nhất 1 báo cáo phản ánh về mã lô này.</div>`;
            nutGopXuLyCungLo.classList.add('hidden');
        } else {
            noteCungLo.textContent = `(Phát hiện ${danhSachCungLo.length} báo cáo khác)`;
            danhSachCungLo.forEach(bc => {
                const divCL = document.createElement('div');
                divCL.className = 'p-2 bg-amber-50/70 border border-amber-200 rounded-lg flex items-center justify-between text-xs';
                divCL.innerHTML = `
                    <div>
                        <span class="font-mono font-bold text-cyan-900">${bc.maBaoCao}</span>
                        <span class="text-slate-600 ml-2">${bc.nguoiBaoCao}</span>
                        <span class="text-[11px] text-amber-900 italic block mt-0.5">• Lý do: ${bc.lyDo}</span>
                    </div>
                    <span class="font-mono text-[11px] text-slate-500">${bc.ngayGui}</span>
                `;
                containerCungLo.appendChild(divCL);
            });

            if (danhSachCungLoChoXuLy.length > 0 && !daXuLy) {
                nutGopXuLyCungLo.classList.remove('hidden');
            } else {
                nutGopXuLyCungLo.classList.add('hidden');
            }
        }

        if (daXuLy && baoCaoDangXuLy.ketQuaXuLy) {
            panelTheKetQuaDaXuLy.classList.remove('hidden');
            panelCumNutQuyetDinh.classList.add('hidden');

            const kq = baoCaoDangXuLy.ketQuaXuLy;
            if (kq.quyetDinh === 'THU_HOI') {
                panelTheKetQuaDaXuLy.className = "p-3 bg-red-50 border border-red-300 rounded-xl text-xs space-y-1.5";
                const hashHtml = kq.txHashThuHoi ? `
                    <div class="flex items-center gap-2 pt-1 border-t border-red-200 text-[11px]">
                        <span class="text-slate-500 font-mono">Mã giao dịch thu hồi on-chain:</span>
                        <a href="${EXPLORER_BASE_URL}${kq.txHashThuHoi}" target="_blank" class="font-mono font-bold text-cyan-800 hover:underline truncate flex-1">${kq.txHashThuHoi}</a>
                        <button type="button" class="btn-copy-tx-tl p-0.5 text-slate-500" data-tx="${kq.txHashThuHoi}"><i class="fa-regular fa-copy"></i></button>
                    </div>
                ` : '';

                panelTheKetQuaDaXuLy.innerHTML = `
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-red-700 flex items-center gap-1.5"><i class="fa-solid fa-ban"></i> QUYẾT ĐỊNH: ĐÃ THU HỒI LÔ THUỐC</span>
                        <span class="text-[10px] text-slate-500">Người xử lý: <strong class="font-mono text-slate-800">${kq.nguoiXuLy}</strong></span>
                    </div>
                    <div class="text-[11px] text-red-900"><strong>Căn cứ thu hồi:</strong> ${kq.lyDo}</div>
                    <div class="text-[10px] text-slate-500 font-mono">Thời điểm quyết định: ${kq.ngayXuLy}</div>
                    ${hashHtml}
                `;
            } else if (kq.quyetDinh === 'GHI_NHAN') {
                panelTheKetQuaDaXuLy.className = "p-3 bg-indigo-50 border border-indigo-300 rounded-xl text-xs space-y-1";
                panelTheKetQuaDaXuLy.innerHTML = `
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-indigo-900 flex items-center gap-1.5"><i class="fa-solid fa-shield-cat"></i> QUYẾT ĐỊNH: ĐÃ GHI NHẬN HÀNG GIẢ (CHUYỂN XỬ LÝ NGOẠI VI)</span>
                        <span class="text-[10px] text-slate-500">Người xử lý: <strong class="font-mono text-slate-800">${kq.nguoiXuLy}</strong></span>
                    </div>
                    <div class="text-[11px] text-indigo-950"><strong>Ghi chú xử lý:</strong> ${kq.lyDo}</div>
                    <div class="text-[10px] text-slate-500 font-mono">Thời điểm quyết định: ${kq.ngayXuLy}</div>
                `;
            } else {
                panelTheKetQuaDaXuLy.className = "p-3 bg-slate-50 border border-slate-300 rounded-xl text-xs space-y-1";
                panelTheKetQuaDaXuLy.innerHTML = `
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-slate-800 flex items-center gap-1.5"><i class="fa-solid fa-circle-xmark"></i> QUYẾT ĐỊNH: ĐÃ TỪ CHỐI BÁO CÁO NGHI VẤN</span>
                        <span class="text-[10px] text-slate-500">Người xử lý: <strong class="font-mono text-slate-800">${kq.nguoiXuLy}</strong></span>
                    </div>
                    <div class="text-[11px] text-slate-800"><strong>Lý do từ chối:</strong> ${kq.lyDo}</div>
                    <div class="text-[10px] text-slate-500 font-mono">Thời điểm quyết định: ${kq.ngayXuLy}</div>
                `;
            }
        } else {
            panelTheKetQuaDaXuLy.classList.add('hidden');
            panelCumNutQuyetDinh.classList.remove('hidden');

            if (!onChain || !onChain.tonTaiOnChain) {
                nutMoModalThuHoiTuBC.classList.add('hidden');
                nutXacNhanGhiNhanLoDaThuHoi.classList.add('hidden');
                nutMoModalGhiNhanHangGia.classList.remove('hidden');
            } else if (onChain.trangThaiLo === 'RECALLED') {
                nutMoModalThuHoiTuBC.classList.add('hidden');
                nutMoModalGhiNhanHangGia.classList.add('hidden');
                nutXacNhanGhiNhanLoDaThuHoi.classList.remove('hidden');
            } else {
                nutMoModalThuHoiTuBC.classList.remove('hidden');
                nutMoModalGhiNhanHangGia.classList.add('hidden');
                nutXacNhanGhiNhanLoDaThuHoi.classList.add('hidden');
            }
        }

        modalXuLy.classList.remove('hidden');
    }

    function ganSuKienNutBang() {
        document.querySelectorAll('.btn-mo-panel-xuly').forEach(btn => {
            btn.addEventListener('click', function () {
                const maBC = this.getAttribute('data-mabc');
                moPanelXuLyBaoCao(maBC);
            });
        });
    }

    document.querySelectorAll('.dong-modal-xuly').forEach(btn => {
        btn.addEventListener('click', () => modalXuLy.classList.add('hidden'));
    });

    document.addEventListener('click', function (e) {
        if (e.target.closest('.btn-copy-tx-tl')) {
            const btn = e.target.closest('.btn-copy-tx-tl');
            const tx = btn.getAttribute('data-tx');
            navigator.clipboard.writeText(tx).then(() => {
                btn.innerHTML = `<i class="fa-solid fa-check text-emerald-600"></i>`;
                setTimeout(() => { btn.innerHTML = `<i class="fa-regular fa-copy"></i>`; }, 1500);
            });
        }
    });

    const modalThuHoiBC = document.getElementById('modalXacNhanThuHoiTuBaoCao');
    const manHinhFormThuHoiBC = document.getElementById('manHinhFormThuHoiBC');
    const manHinhDangGhiBlockchainThuHoi = document.getElementById('manHinhDangGhiBlockchainThuHoi');
    const manHinhLoiBlockchainThuHoi = document.getElementById('manHinhLoiBlockchainThuHoi');
    const nhapLyDoThuHoiBC = document.getElementById('nhapLyDoThuHoiTuBC');
    const nutXacNhanKyThuHoi = document.getElementById('nutXacNhanKýThuHoiOnChain');
    const nutThuLaiThuHoi = document.getElementById('nutThuLaiThuHoiBlockchain');

    nutMoModalThuHoiTuBC.addEventListener('click', function () {
        if (!baoCaoDangXuLy) return;

        document.getElementById('modalThuHoiMaLo').textContent = baoCaoDangXuLy.maLoKhaiBao;
        document.getElementById('modalThuHoiCanCuBC').textContent = baoCaoDangXuLy.maBaoCao;
        nhapLyDoThuHoiBC.value = `Thu hồi khẩn cấp căn cứ báo cáo ${baoCaoDangXuLy.maBaoCao}: ${baoCaoDangXuLy.lyDo}.`;

        const containerPB = document.getElementById('modalThuHoiPhanBoKho');
        containerPB.innerHTML = '';
        if (baoCaoDangXuLy.thongTinLoOnChain && baoCaoDangXuLy.thongTinLoOnChain.phanBoKho) {
            baoCaoDangXuLy.thongTinLoOnChain.phanBoKho.forEach(pb => {
                const div = document.createElement('div');
                div.className = 'flex justify-between items-center py-0.5';
                div.innerHTML = `<span class="text-slate-700 font-sans truncate mr-2">• ${pb.donVi}:</span> <strong class="text-slate-900">${pb.giu.toLocaleString('vi-VN')} hộp</strong>`;
                containerPB.appendChild(div);
            });
        }

        manHinhFormThuHoiBC.classList.remove('hidden');
        manHinhDangGhiBlockchainThuHoi.classList.add('hidden');
        manHinhLoiBlockchainThuHoi.classList.add('hidden');
        modalThuHoiBC.classList.remove('hidden');
    });

    document.querySelectorAll('.dong-modal-thuhoi-bc').forEach(btn => {
        btn.addEventListener('click', () => modalThuHoiBC.classList.add('hidden'));
    });

    function thucHienKyBlockchainThuHoiBC() {
        const lyDo = nhapLyDoThuHoiBC.value.trim();
        if (!lyDo || !baoCaoDangXuLy) return;

        manHinhFormThuHoiBC.classList.add('hidden');
        manHinhDangGhiBlockchainThuHoi.classList.remove('hidden');
        manHinhLoiBlockchainThuHoi.classList.add('hidden');

        setTimeout(() => {
            const thanhCongOnChain = true;

            if (thanhCongOnChain) {
                const now = new Date();
                const thoiGianStr = `${String(now.getDate()).padStart(2,'0')}/${String(now.getMonth()+1).padStart(2,'0')}/${now.getFullYear()} ${String(now.getHours()).padStart(2,'0')}:${String(now.getMinutes()).padStart(2,'0')}`;
                const randomTx = '0x' + Array.from({length: 64}, () => Math.floor(Math.random()*16).toString(16)).join('');

                baoCaoDangXuLy.trangThai = 'DA_XU_LY';
                baoCaoDangXuLy.ketQuaLoai = 'THU_HOI';
                baoCaoDangXuLy.ketQuaXuLy = {
                    quyetDinh: 'THU_HOI',
                    nguoiXuLy: TAI_KHOAN_CQQL_DANG_NHAP,
                    ngayXuLy: thoiGianStr,
                    lyDo: lyDo,
                    txHashThuHoi: randomTx
                };

                if (baoCaoDangXuLy.thongTinLoOnChain) {
                    baoCaoDangXuLy.thongTinLoOnChain.trangThaiLo = 'RECALLED';
                }

                modalThuHoiBC.classList.add('hidden');
                modalXuLy.classList.add('hidden');

                alert(`Ban hành quyết định thu hồi lô [${baoCaoDangXuLy.maLoKhaiBao}] thành công!`);
                renderBangNghiVan();
            } else {
                manHinhDangGhiBlockchainThuHoi.classList.add('hidden');
                manHinhLoiBlockchainThuHoi.classList.remove('hidden');
            }
        }, 1200);
    }

    nutXacNhanKyThuHoi.addEventListener('click', thucHienKyBlockchainThuHoiBC);
    nutThuLaiThuHoi.addEventListener('click', thucHienKyBlockchainThuHoiBC);

    nutXacNhanGhiNhanLoDaThuHoi.addEventListener('click', function () {
        if (!baoCaoDangXuLy) return;
        const now = new Date();
        const thoiGianStr = `${String(now.getDate()).padStart(2,'0')}/${String(now.getMonth()+1).padStart(2,'0')}/${now.getFullYear()} ${String(now.getHours()).padStart(2,'0')}:${String(now.getMinutes()).padStart(2,'0')}`;

        baoCaoDangXuLy.trangThai = 'DA_XU_LY';
        baoCaoDangXuLy.ketQuaLoai = 'THU_HOI';
        baoCaoDangXuLy.ketQuaXuLy = {
            quyetDinh: 'THU_HOI',
            nguoiXuLy: TAI_KHOAN_CQQL_DANG_NHAP,
            ngayXuLy: thoiGianStr,
            lyDo: `Ghi nhận bổ sung vào quyết định thu hồi lô [${baoCaoDangXuLy.maLoKhaiBao}] đã ban hành trước đó.`,
            txHashThuHoi: null
        };

        modalXuLy.classList.add('hidden');
        alert(`Đã ghi nhận báo cáo [${baoCaoDangXuLy.maBaoCao}] vào hồ sơ thu hồi lô [${baoCaoDangXuLy.maLoKhaiBao}].`);
        renderBangNghiVan();
    });

    nutGopXuLyCungLo.addEventListener('click', function () {
        if (!baoCaoDangXuLy) return;
        const cacBaoCaoCungLo = danhSachBaoCao.filter(x => x.maLoKhaiBao === baoCaoDangXuLy.maLoKhaiBao && x.trangThai !== 'DA_XU_LY');

        if (confirm(`Bạn có chắc chắn muốn xử lý gộp đồng thời ${cacBaoCaoCungLo.length} báo cáo đang chờ xử lý của mã lô [${baoCaoDangXuLy.maLoKhaiBao}] không?`)) {
            const now = new Date();
            const thoiGianStr = `${String(now.getDate()).padStart(2,'0')}/${String(now.getMonth()+1).padStart(2,'0')}/${now.getFullYear()} ${String(now.getHours()).padStart(2,'0')}:${String(now.getMinutes()).padStart(2,'0')}`;

            cacBaoCaoCungLo.forEach(bc => {
                bc.trangThai = 'DA_XU_LY';
                bc.ketQuaLoai = 'THU_HOI';
                bc.ketQuaXuLy = {
                    quyetDinh: 'THU_HOI',
                    nguoiXuLy: TAI_KHOAN_CQQL_DANG_NHAP,
                    ngayXuLy: thoiGianStr,
                    lyDo: `Đã xử lý gộp theo quyết định thu hồi lô [${bc.maLoKhaiBao}].`,
                    txHashThuHoi: null
                };
            });

            modalXuLy.classList.add('hidden');
            alert(`Đã xử lý gộp thành công ${cacBaoCaoCungLo.length} báo cáo thuộc lô [${baoCaoDangXuLy.maLoKhaiBao}].`);
            renderBangNghiVan();
        }
    });

    const modalTuChoiBC = document.getElementById('modalXacNhanTuChoiBaoCao');
    const nhapLyDoTuChoiBC = document.getElementById('nhapLyDoTuChoiBC');
    const demKyTuTuChoiBC = document.getElementById('demKyTuTuChoiBC');
    const nutMoModalTuChoiBC = document.getElementById('nutMoModalTuChoiBaoCao');
    const nutXacNhanTuChoiBC = document.getElementById('nutXacNhanTuChoiBCOffChain');

    nutMoModalTuChoiBC.addEventListener('click', function () {
        if (!baoCaoDangXuLy) return;
        document.getElementById('modalTuChoiMaBC').textContent = baoCaoDangXuLy.maBaoCao;
        document.getElementById('modalTuChoiMaLo').textContent = baoCaoDangXuLy.maLoKhaiBao;
        nhapLyDoTuChoiBC.value = '';
        demKyTuTuChoiBC.textContent = '0/500';
        capNhatTrangThaiNutTuChoi();
        modalTuChoiBC.classList.remove('hidden');
    });

    function capNhatTrangThaiNutTuChoi() {
        const len = nhapLyDoTuChoiBC.value.trim().length;
        demKyTuTuChoiBC.textContent = `${len}/500`;
        if (len > 0) {
            nutXacNhanTuChoiBC.disabled = false;
            nutXacNhanTuChoiBC.className = "px-6 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-xl shadow-md transition-all flex items-center gap-1.5 cursor-pointer";
        } else {
            nutXacNhanTuChoiBC.disabled = true;
            nutXacNhanTuChoiBC.className = "px-6 py-2 bg-slate-200 text-slate-400 font-bold rounded-xl transition-all flex items-center gap-1.5 cursor-not-allowed shadow-none";
        }
    }

    nhapLyDoTuChoiBC.addEventListener('input', capNhatTrangThaiNutTuChoi);

    document.querySelectorAll('.dong-modal-tuchoi-bc').forEach(btn => {
        btn.addEventListener('click', () => modalTuChoiBC.classList.add('hidden'));
    });

    nutXacNhanTuChoiBC.addEventListener('click', function () {
        const lyDo = nhapLyDoTuChoiBC.value.trim();
        if (!lyDo || !baoCaoDangXuLy) return;
        const now = new Date();
        const thoiGianStr = `${String(now.getDate()).padStart(2,'0')}/${String(now.getMonth()+1).padStart(2,'0')}/${now.getFullYear()} ${String(now.getHours()).padStart(2,'0')}:${String(now.getMinutes()).padStart(2,'0')}`;

        baoCaoDangXuLy.trangThai = 'DA_XU_LY';
        baoCaoDangXuLy.ketQuaLoai = 'TU_CHOI';
        baoCaoDangXuLy.ketQuaXuLy = {
            quyetDinh: 'TU_CHOI',
            nguoiXuLy: TAI_KHOAN_CQQL_DANG_NHAP,
            ngayXuLy: thoiGianStr,
            lyDo: lyDo,
            txHashThuHoi: null
        };

        modalTuChoiBC.classList.add('hidden');
        modalXuLy.classList.add('hidden');
        alert(`Đã từ chối báo cáo nghi vấn [${baoCaoDangXuLy.maBaoCao}].`);
        renderBangNghiVan();
    });

    const modalGhiNhanHangGia = document.getElementById('modalXacNhanGhiNhanHangGia');
    const nhapGhiChuHangGiaBC = document.getElementById('nhapGhiChuHangGiaBC');
    const nutXacNhanGhiNhanHangGiaSubmit = document.getElementById('nutXacNhanGhiNhanHangGiaSubmit');

    nutMoModalGhiNhanHangGia.addEventListener('click', function () {
        if (!baoCaoDangXuLy) return;
        nhapGhiChuHangGiaBC.value = `Mã lô [${baoCaoDangXuLy.maLoKhaiBao}] không tồn tại trên hệ thống. Đã ghi nhận thông tin và chuyển hồ sơ sang Đội Quản lý thị trường kiểm tra điểm bán lẻ.`;
        modalGhiNhanHangGia.classList.remove('hidden');
    });

    document.querySelectorAll('.dong-modal-hanggia-bc').forEach(btn => {
        btn.addEventListener('click', () => modalGhiNhanHangGia.classList.add('hidden'));
    });

    nutXacNhanGhiNhanHangGiaSubmit.addEventListener('click', function () {
        const ghiChu = nhapGhiChuHangGiaBC.value.trim();
        if (!ghiChu || !baoCaoDangXuLy) return;
        const now = new Date();
        const thoiGianStr = `${String(now.getDate()).padStart(2,'0')}/${String(now.getMonth()+1).padStart(2,'0')}/${now.getFullYear()} ${String(now.getHours()).padStart(2,'0')}:${String(now.getMinutes()).padStart(2,'0')}`;

        baoCaoDangXuLy.trangThai = 'DA_XU_LY';
        baoCaoDangXuLy.ketQuaLoai = 'GHI_NHAN';
        baoCaoDangXuLy.ketQuaXuLy = {
            quyetDinh: 'GHI_NHAN',
            nguoiXuLy: TAI_KHOAN_CQQL_DANG_NHAP,
            ngayXuLy: thoiGianStr,
            lyDo: ghiChu,
            txHashThuHoi: null
        };

        modalGhiNhanHangGia.classList.add('hidden');
        modalXuLy.classList.add('hidden');
        alert(`Đã ghi nhận xử lý báo cáo hàng giả [${baoCaoDangXuLy.maBaoCao}].`);
        renderBangNghiVan();
    });

    oTimKiem.addEventListener('input', () => { trangHienTai = 1; renderBangNghiVan(); });
    locTrangThai.addEventListener('change', () => { trangHienTai = 1; renderBangNghiVan(); });
    locNguon.addEventListener('change', () => { trangHienTai = 1; renderBangNghiVan(); });

    renderBangNghiVan();
});
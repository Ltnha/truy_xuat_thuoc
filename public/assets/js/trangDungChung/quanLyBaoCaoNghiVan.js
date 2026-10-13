document.addEventListener('DOMContentLoaded', function () {
    const EXPLORER_BASE_URL = "https://amoy.polygonscan.com/tx/";
    const ITEMS_PER_PAGE = 4;
    let trangHienTai = 1;

    // Helper định dạng ngày giờ dd/mm/yyyy hh:mm
    function dinhDangNgayGio(chuoiNgay) {
        if (!chuoiNgay) return '--';
        // Hỗ trợ dạng YYYY-MM-DD HH:mm
        const parts = chuoiNgay.split(' ');
        if (parts.length === 2) {
            const dateParts = parts[0].split('-');
            if (dateParts.length === 3) {
                return `${dateParts[2]}/${dateParts[1]}/${dateParts[0]} ${parts[1]}`;
            }
        }
        return chuoiNgay;
    }

    // ══ 1. DỮ LIỆU MÔ PHỎNG (baoCaoNghiVan, minhChungBaoCao, ketQuaXuLyBaoCao, thuHoiLoHang) ══
    // Đảm bảo có đủ 3 trạng thái: DA_GUI, DANG_XU_LY, DA_XU_LY (với biến thể THU_HOI và TU_CHOI)
    let danhSachBaoCao = [
        {
            maBaoCao: 'BC-2026-001',
            maLo: 'LOT-AMOX-2025-09',
            ngayGui: '02/02/2026 14:20',
            trangThai: 'DA_XU_LY',
            ketQuaLoai: 'THU_HOI',
            lyDo: 'Hạn sử dụng bị sửa đổi / tẩy xóa',
            moTa: 'Phát hiện tem niêm phong trên nắp hộp có dấu hiệu bị bóc dán đè. Hạn sử dụng trên vỉ in đè mực khác với nhãn ngoài vỏ hộp thuốc.',
            danhSachMinhChung: [
                { ten: 'anh_chup_tem_dan_de.jpg', loai: 'image', url: 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=600&auto=format&fit=crop&q=60' },
                { ten: 'bien_ban_kiem_tra_hien_truong.pdf', loai: 'pdf', url: '#' }
            ],
            ketQuaThucTe: {
                quyetDinh: 'THU_HOI',
                ngayXuLy: '05/02/2026',
                // Nguồn: thuHoiLoHang.lyDo
                lyDoThuHoi: 'Mẫu thuốc lưu không đạt tiêu chuẩn độ hòa tan và phát hiện dấu hiệu tẩy xóa hạn dùng theo Công văn số 458/QLD.',
                // Nguồn: thuHoiLoHang.hashBlockchain
                hashBlockchainThuHoi: '0x71a49f2b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4e5f'
            }
        },
        {
            maBaoCao: 'BC-2026-002',
            maLo: 'LOT-PARA-2026',
            ngayGui: '06/02/2026 09:15',
            trangThai: 'DA_XU_LY',
            ketQuaLoai: 'TU_CHOI',
            lyDo: 'Bao bì có dấu hiệu làm giả / in đè nhãn',
            moTa: 'Bao bì một kiện thuốc thùng số 04 bị móp méo và trầy xước mực in trong quá trình vận chuyển, nghi ngờ nhãn không chính hãng.',
            danhSachMinhChung: [
                { ten: 'anh_vo_hop_mop.png', loai: 'image', url: 'https://images.unsplash.com/photo-1471864190281-a93a3070b6de?w=600&auto=format&fit=crop&q=60' }
            ],
            ketQuaThucTe: {
                quyetDinh: 'TU_CHOI',
                ngayXuLy: '08/02/2026',
                // Nguồn: ketQuaXuLyBaoCao.lyDoTuChoi
                lyDoTuChoi: 'Đối soát mẫu lưu và thẩm định đối chứng từ Nhà sản xuất xác nhận sản phẩm chính hãng. Vết trầy xước do va chạm cơ học trong vận chuyển, không ảnh hưởng chất lượng thuốc bên trong.'
            }
        },
        {
            maBaoCao: 'BC-2026-003',
            maLo: 'LOT-CEFA-2024-EX',
            ngayGui: '07/02/2026 10:00',
            trangThai: 'DANG_XU_LY',
            ketQuaLoai: null,
            lyDo: 'Mã QR không quét được hoặc không hợp lệ',
            moTa: 'Quét mã QR trên tem thùng trả về kết quả lỗi mã hash không xác định trên trình duyệt blockchain.',
            danhSachMinhChung: [
                { ten: 'anh_loi_quet_qr.jpg', loai: 'image', url: 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=600&auto=format&fit=crop&q=60' }
            ],
            ketQuaThucTe: null
        },
        {
            // Dòng minh họa trạng thái ĐÃ GỬI (Đủ 3 bộ lọc)
            maBaoCao: 'BC-2026-004',
            maLo: 'LOT-FAKE-9999', // Mã lô nghi giả không có trong CSDL
            ngayGui: '09/02/2026 15:45',
            trangThai: 'DA_GUI',
            ketQuaLoai: null,
            lyDo: 'Thuốc có dấu hiệu biến chất / đổi màu / ẩm mốc',
            moTa: 'Viên nén trong vỉ xuất hiện đốm nâu lạ và vỏ vỉ nhôm bị phồng, không tìm thấy số lô này trên cổng tra cứu quốc gia.',
            danhSachMinhChung: [
                { ten: 'bien_ban_phat_hien.pdf', loai: 'pdf', url: '#' }
            ],
            ketQuaThucTe: null
        }
    ];

    const thanBang = document.getElementById('thanBangBaoCao');
    const oTimKiem = document.getElementById('oTimKiemBaoCao');
    const locTrangThai = document.getElementById('locTrangThaiBaoCao');
    const soLuongBadge = document.getElementById('soLuongBaoCaoBadge');
    const thongTinPhanTrang = document.getElementById('thongTinPhanTrang');
    const cumNutPhanTrang = document.getElementById('cumNutPhanTrang');

    // ══ 2. RENDER NHÃN KẾT QUẢ TRỰC TIẾP TRÊN BẢNG ══
    function taoBadgeTrangThai(item) {
        if (item.trangThai === 'DA_XU_LY') {
            if (item.ketQuaLoai === 'THU_HOI') {
                return `<span class="badge-chuan px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-red-100 text-red-700 border border-red-300"><i class="fa-solid fa-ban"></i> Đã xử lý - Thu hồi</span>`;
            } else {
                return `<span class="badge-chuan px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-slate-100 text-slate-700 border border-slate-300"><i class="fa-solid fa-circle-xmark"></i> Đã xử lý - Từ chối</span>`;
            }
        } else if (item.trangThai === 'DANG_XU_LY') {
            return `<span class="badge-chuan px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300"><i class="fa-solid fa-clock"></i> Đang xử lý</span>`;
        } else {
            return `<span class="badge-chuan px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-cyan-50 text-cyan-800 border border-cyan-300"><i class="fa-regular fa-paper-plane"></i> Đã gửi</span>`;
        }
    }

    // ══ 3. RENDER BẢNG VÀ PHÂN TRANG ══
    function renderBangBaoCao() {
        const tuKhoa = oTimKiem.value.trim().toLowerCase();
        const trangThaiLoc = locTrangThai.value;

        const danhSachLoc = danhSachBaoCao.filter(item => {
            const matchTuKhoa = item.maBaoCao.toLowerCase().includes(tuKhoa) || 
                               item.maLo.toLowerCase().includes(tuKhoa) ||
                               item.lyDo.toLowerCase().includes(tuKhoa);
            const matchTrangThai = (trangThaiLoc === 'TAT_CA') || (item.trangThai === trangThaiLoc);
            return matchTuKhoa && matchTrangThai;
        });

        soLuongBadge.textContent = `Hiển thị: ${danhSachLoc.length} báo cáo`;

        const tongSoTrang = Math.ceil(danhSachLoc.length / ITEMS_PER_PAGE) || 1;
        if (trangHienTai > tongSoTrang) trangHienTai = tongSoTrang;

        const batDau = (trangHienTai - 1) * ITEMS_PER_PAGE;
        const ketThuc = batDau + ITEMS_PER_PAGE;
        const duLieuTrang = danhSachLoc.slice(batDau, ketThuc);

        thanBang.innerHTML = '';

        if (duLieuTrang.length === 0) {
            thanBang.innerHTML = `<tr><td colspan="6" class="text-center py-8 text-slate-400 italic">Không tìm thấy báo cáo nghi vấn nào phù hợp với bộ lọc.</td></tr>`;
            thongTinPhanTrang.textContent = "Không có bản ghi nào";
            cumNutPhanTrang.innerHTML = '';
            return;
        }

        thongTinPhanTrang.textContent = `Đang hiển thị ${batDau + 1} đến ${Math.min(ketThuc, danhSachLoc.length)} trên tổng số ${danhSachLoc.length} báo cáo`;

        duLieuTrang.forEach(item => {
            const badge = taoBadgeTrangThai(item);

            const tr = document.createElement('tr');
            tr.className = 'dong-bao-cao-dn transition-colors';
            tr.innerHTML = `
                <td class="py-3 px-3 font-mono font-bold text-cyan-900 whitespace-nowrap">${item.maBaoCao}</td>
                <td class="py-3 px-3 font-mono font-bold text-slate-900 whitespace-nowrap">${item.maLo}</td>
                <td class="py-3 px-3 font-semibold text-slate-800 leading-tight">${item.lyDo}</td>
                <td class="py-3 px-2.5 text-center text-slate-600 font-mono text-[11px] whitespace-nowrap">${item.ngayGui}</td>
                <td class="py-3 px-3 text-center whitespace-nowrap">${badge}</td>
                <td class="py-3 px-3 text-center whitespace-nowrap">
                    <button type="button" class="btn-xem-bc px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-all flex items-center justify-center gap-1 mx-auto border border-slate-300 shadow-2xs">
                        <i class="fa-solid fa-eye text-cyan-800"></i> Chi Tiết
                    </button>
                </td>
            `;

            tr.addEventListener('click', () => moChiTietBaoCao(item));
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
        btnPrev.addEventListener('click', () => { if (trangHienTai > 1) { trangHienTai--; renderBangBaoCao(); } });
        cumNutPhanTrang.appendChild(btnPrev);

        for (let i = 1; i <= tongSoTrang; i++) {
            const btnPage = document.createElement('button');
            btnPage.className = `w-7 h-7 rounded-lg text-xs font-bold transition-all ${trangHienTai === i ? 'bg-emerald-700 text-white shadow-2xs' : 'border border-slate-200 text-slate-700 hover:bg-slate-100'}`;
            btnPage.textContent = i;
            btnPage.addEventListener('click', () => { trangHienTai = i; renderBangBaoCao(); });
            cumNutPhanTrang.appendChild(btnPage);
        }

        const btnNext = document.createElement('button');
        btnNext.className = `px-2.5 py-1 rounded-lg border text-xs font-bold transition-all ${trangHienTai === tongSoTrang ? 'border-slate-200 text-slate-300 cursor-not-allowed' : 'border-slate-300 text-slate-700 hover:bg-slate-100'}`;
        btnNext.innerHTML = `<i class="fa-solid fa-angle-right"></i>`;
        btnNext.disabled = (trangHienTai === tongSoTrang);
        btnNext.addEventListener('click', () => { if (trangHienTai < tongSoTrang) { trangHienTai++; renderBangBaoCao(); } });
        cumNutPhanTrang.appendChild(btnNext);
    }

    // ══ 4. QUẢN LÝ NHIỀU TỆP MINH CHỨNG (TỐI ĐA 5 TỆP) ══
    let tepDaChonArr = [];
    const khungChonTep = document.getElementById('khungChonTepMinhChung');
    const tepInput = document.getElementById('tepMinhChungInput');
    const danhSachTepContainer = document.getElementById('danhSachTepDaChon');
    const soLuongTepLabel = document.getElementById('soLuongTepDaChon');

    khungChonTep.addEventListener('click', () => tepInput.click());

    tepInput.addEventListener('change', function () {
        const files = Array.from(this.files);
        for (let f of files) {
            if (tepDaChonArr.length >= 5) {
                alert("Bạn chỉ được đính kèm tối đa 5 tệp bằng chứng!");
                break;
            }
            if (f.size > 10 * 1024 * 1024) {
                alert(`Tệp "${f.name}" vượt quá dung lượng cho phép (10MB)!`);
                continue;
            }
            const hopLe = ['image/jpeg', 'image/png', 'application/pdf'].includes(f.type);
            if (!hopLe) {
                alert(`Tệp "${f.name}" không đúng định dạng JPG, PNG hoặc PDF!`);
                continue;
            }
            tepDaChonArr.push(f);
        }
        tepInput.value = '';
        renderDanhSachTepChon();
    });

    function renderDanhSachTepChon() {
        danhSachTepContainer.innerHTML = '';
        soLuongTepLabel.textContent = `${tepDaChonArr.length}/5 tệp`;

        tepDaChonArr.forEach((file, index) => {
            const isPdf = file.type === 'application/pdf';
            const iconClass = isPdf ? 'fa-solid fa-file-pdf text-red-500' : 'fa-solid fa-file-image text-emerald-600';
            const dungLuong = (file.size / 1024 / 1024).toFixed(2);

            const div = document.createElement('div');
            div.className = 'flex items-center justify-between p-2 bg-slate-50 border border-slate-200 rounded-lg text-xs';
            div.innerHTML = `
                <div class="flex items-center gap-2 truncate pr-2">
                    <i class="${iconClass} text-sm"></i>
                    <span class="font-medium text-slate-800 truncate" title="${file.name}">${file.name}</span>
                    <span class="text-[10px] text-slate-400 font-mono">(${dungLuong} MB)</span>
                </div>
                <button type="button" class="text-slate-400 hover:text-red-600 text-xs p-1" title="Xóa tệp">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            `;

            div.querySelector('button').addEventListener('click', () => {
                tepDaChonArr.splice(index, 1);
                renderDanhSachTepChon();
            });

            danhSachTepContainer.appendChild(div);
        });
    }

    // ══ 5. GỢI Ý MÃ LÔ VÀ TỰ ĐIỀN NẾU CHUYỂN TỪ TRANG KHÁC ══
    const oNhapMaLo = document.getElementById('nhapMaLoLienQuan');
    const menuGoiY = document.getElementById('menuGoiYMaLo');
    const nutMoMenuLo = document.getElementById('nutMoMenuLo');
    const iconMuiTenLo = document.getElementById('iconMuiTenLo');
    const cacMucGoiY = document.querySelectorAll('.item-lo');

    // Tự điền nếu có tham số URL ?maLo=...
    const urlParams = new URLSearchParams(window.location.search);
    const maLoTruyenVao = urlParams.get('maLo');
    if (maLoTruyenVao) {
        oNhapMaLo.value = maLoTruyenVao;
        document.getElementById('modalGuiBaoCaoMoi').classList.remove('hidden');
    }

    function batMenuLo() {
        menuGoiY.classList.remove('hidden');
        if (iconMuiTenLo) iconMuiTenLo.className = "fa-solid fa-chevron-up";
    }

    function tatMenuLo() {
        menuGoiY.classList.add('hidden');
        if (iconMuiTenLo) iconMuiTenLo.className = "fa-solid fa-chevron-down";
    }

    oNhapMaLo.addEventListener('focus', batMenuLo);
    oNhapMaLo.addEventListener('click', batMenuLo);
    nutMoMenuLo.addEventListener('click', (e) => {
        e.stopPropagation();
        menuGoiY.classList.contains('hidden') ? batMenuLo() : tatMenuLo();
    });

    cacMucGoiY.forEach(item => {
        item.addEventListener('click', function () {
            oNhapMaLo.value = this.getAttribute('data-malo');
            tatMenuLo();
        });
    });

    document.addEventListener('click', (e) => {
        const khung = document.getElementById('khungChonMaLo');
        if (khung && !khung.contains(e.target)) tatMenuLo();
    });

    // ══ 6. GỬI BÁO CÁO NGHI VẤN MỚI ══
    const modalGui = document.getElementById('modalGuiBaoCaoMoi');
    const formGui = document.getElementById('formSubmitBaoCao');

    document.getElementById('nutMoModalGuiBaoCao').addEventListener('click', () => {
        formGui.reset();
        tepDaChonArr = [];
        renderDanhSachTepChon();
        tatMenuLo();
        modalGui.classList.remove('hidden');
    });

    document.querySelectorAll('.dong-modal-gui').forEach(btn => {
        btn.addEventListener('click', () => modalGui.classList.add('hidden'));
    });

    formGui.addEventListener('submit', function (e) {
        e.preventDefault();

        const maLo = oNhapMaLo.value.trim().toUpperCase();
        const lyDo = document.getElementById('chonLyDoNghiVan').value;
        const moTa = document.getElementById('nhapMoTaChiTiet').value.trim();

        if (!maLo || !lyDo || !moTa) {
            alert("Vui lòng điền đầy đủ các trường thông tin bắt buộc!");
            return;
        }

        if (tepDaChonArr.length === 0) {
            alert("Vui lòng đính kèm ít nhất 1 tệp bằng chứng (hình ảnh hoặc biên bản kiểm nghiệm)!");
            return;
        }

        const now = new Date();
        const thoiGianStr = `${String(now.getDate()).padStart(2,'0')}/${String(now.getMonth()+1).padStart(2,'0')}/${now.getFullYear()} ${String(now.getHours()).padStart(2,'0')}:${String(now.getMinutes()).padStart(2,'0')}`;
        const maMoi = 'BC-' + now.getFullYear() + '-' + String(danhSachBaoCao.length + 1).padStart(3, '0');

        const danhSachMinhChungFormatted = tepDaChonArr.map(f => ({
            ten: f.name,
            loai: f.type === 'application/pdf' ? 'pdf' : 'image',
            url: f.type === 'application/pdf' ? '#' : URL.createObjectURL(f)
        }));

        const banGhiMoi = {
            maBaoCao: maMoi,
            maLo: maLo,
            ngayGui: thoiGianStr,
            trangThai: 'DA_GUI',
            ketQuaLoai: null,
            lyDo: lyDo,
            moTa: moTa,
            danhSachMinhChung: danhSachMinhChungFormatted,
            ketQuaThucTe: null
        };

        // Thêm vào đầu bảng
        danhSachBaoCao.unshift(banGhiMoi);
        renderBangBaoCao();
        modalGui.classList.add('hidden');

        alert(`Gửi báo cáo nghi vấn thành công!\n- Mã báo cáo: ${maMoi}\n- Trạng thái: Đã gửi\nHồ sơ đã được chuyển tiếp đến Cơ quan quản lý để tra soát.`);
    });

    // ══ 7. XEM CHI TIẾT BÁO CÁO (3 BIẾN THỂ KẾT QUẢ) ══
    const modalChiTiet = document.getElementById('modalChiTietBaoCao');
    const chiTietMaBaoCao = document.getElementById('chiTietMaBaoCao');
    const chiTietMaLo = document.getElementById('chiTietMaLo');
    const chiTietNgayGui = document.getElementById('chiTietNgayGui');
    const chiTietTrangThaiBadge = document.getElementById('chiTietTrangThaiBadge');
    const chiTietLyDo = document.getElementById('chiTietLyDo');
    const chiTietMoTa = document.getElementById('chiTietMoTa');
    const chiTietDanhSachMinhChung = document.getElementById('chiTietDanhSachMinhChung');
    const noiDungKetQuaXuLy = document.getElementById('noiDungKetQuaXuLy');

    function moChiTietBaoCao(item) {
        chiTietMaBaoCao.textContent = item.maBaoCao;
        chiTietMaLo.textContent = item.maLo;
        chiTietNgayGui.textContent = item.ngayGui;
        chiTietLyDo.textContent = item.lyDo;
        chiTietMoTa.textContent = item.moTa;
        chiTietTrangThaiBadge.innerHTML = taoBadgeTrangThai(item);

        // Render danh sách bằng chứng (ảnh + pdf)
        chiTietDanhSachMinhChung.innerHTML = '';
        if (item.danhSachMinhChung && item.danhSachMinhChung.length > 0) {
            item.danhSachMinhChung.forEach(mc => {
                const card = document.createElement('div');
                card.className = 'p-2.5 bg-slate-50 border border-slate-200 rounded-xl flex items-center gap-3';
                if (mc.loai === 'image') {
                    card.innerHTML = `
                        <div class="w-12 h-12 rounded-lg bg-slate-200 overflow-hidden shrink-0 border border-slate-300">
                            <img src="${mc.url}" alt="${mc.ten}" class="w-full h-full object-cover">
                        </div>
                        <div class="truncate text-xs flex-1">
                            <span class="font-semibold text-slate-800 truncate block" title="${mc.ten}">${mc.ten}</span>
                            <a href="${mc.url}" target="_blank" class="text-cyan-800 hover:underline text-[11px] font-bold">Xem ảnh gốc</a>
                        </div>
                    `;
                } else {
                    card.innerHTML = `
                        <div class="w-12 h-12 rounded-lg bg-red-50 text-red-600 flex items-center justify-center shrink-0 border border-red-200 text-xl">
                            <i class="fa-solid fa-file-pdf"></i>
                        </div>
                        <div class="truncate text-xs flex-1">
                            <span class="font-semibold text-slate-800 truncate block" title="${mc.ten}">${mc.ten}</span>
                            <a href="javascript:void(0)" onclick="alert('Đang tải tài liệu minh chứng kiểm nghiệm: ${mc.ten}')" class="text-red-700 hover:underline text-[11px] font-bold">Tải tài liệu PDF</a>
                        </div>
                    `;
                }
                chiTietDanhSachMinhChung.appendChild(card);
            });
        } else {
            chiTietDanhSachMinhChung.innerHTML = `<span class="text-slate-400 italic text-xs col-span-2">Không có tệp bằng chứng đính kèm.</span>`;
        }

        // Render 3 biến thể kết quả từ Cơ quan quản lý
        if (item.trangThai === 'DA_XU_LY') {
            if (item.ketQuaLoai === 'THU_HOI' && item.ketQuaThucTe) {
                const txLink = `${EXPLORER_BASE_URL}${item.ketQuaThucTe.hashBlockchainThuHoi}`;
                noiDungKetQuaXuLy.className = "p-4 rounded-xl border border-red-200 bg-red-50 text-red-900 space-y-2.5 text-xs";
                noiDungKetQuaXuLy.innerHTML = `
                    <div class="font-bold flex items-center gap-1.5 text-red-700 text-xs">
                        <i class="fa-solid fa-ban text-sm"></i> QUYẾT ĐỊNH: THU HỒI LÔ THUỐC TRÊN TOÀN CHUỖI CUNG ỨNG
                    </div>
                    <div>
                        <span class="text-slate-600 block text-[11px]">Lý do thu hồi (thuHoiLoHang.lyDo):</span>
                        <p class="font-semibold text-red-800 leading-relaxed">${item.ketQuaThucTe.lyDoThuHoi}</p>
                    </div>
                    <div class="text-[11px] text-slate-600 flex justify-between pt-1 border-t border-red-200/60">
                        <span>Ngày ban hành: <strong>${item.ketQuaThucTe.ngayXuLy}</strong></span>
                        <span class="font-bold text-red-700">Lô đã được chuyển sang trạng thái Đã thu hồi trên blockchain.</span>
                    </div>
                    <!-- Mã hash đầy đủ kèm nút sao chép và link Amoy Polygonscan -->
                    <div class="space-y-1 pt-1">
                        <span class="text-slate-600 font-bold block text-[11px]">Mã giao dịch thu hồi on-chain (hashBlockchain):</span>
                        <div class="flex items-center gap-2 p-2 bg-white border border-red-200 rounded-lg">
                            <a href="${txLink}" target="_blank" class="flex-1 font-mono text-[11px] text-cyan-800 hover:underline break-all" title="Xem trên Polygonscan">
                                ${item.ketQuaThucTe.hashBlockchainThuHoi} <i class="fa-solid fa-arrow-up-right-from-square text-[9px] text-slate-400"></i>
                            </a>
                            <button type="button" id="nutCopyRecallTx" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-[11px] font-bold shrink-0">
                                <i class="fa-regular fa-copy"></i> Sao chép
                            </button>
                        </div>
                    </div>
                `;

                // Bắt sự kiện nút sao chép txHash thu hồi
                setTimeout(() => {
                    const btnCopy = document.getElementById('nutCopyRecallTx');
                    if (btnCopy) {
                        btnCopy.addEventListener('click', () => {
                            navigator.clipboard.writeText(item.ketQuaThucTe.hashBlockchainThuHoi).then(() => {
                                btnCopy.innerHTML = `<i class="fa-solid fa-check text-emerald-600"></i> Đã sao chép`;
                                setTimeout(() => { btnCopy.innerHTML = `<i class="fa-regular fa-copy"></i> Sao chép`; }, 1500);
                            });
                        });
                    }
                }, 100);

            } else if (item.ketQuaLoai === 'TU_CHOI' && item.ketQuaThucTe) {
                noiDungKetQuaXuLy.className = "p-4 rounded-xl border border-slate-300 bg-slate-50 text-slate-800 space-y-2 text-xs";
                noiDungKetQuaXuLy.innerHTML = `
                    <div class="font-bold flex items-center gap-1.5 text-slate-800 text-xs">
                        <i class="fa-solid fa-circle-xmark text-slate-500"></i> QUYẾT ĐỊNH: TỪ CHỐI BÁO CÁO NGHI VẤN
                    </div>
                    <div>
                        <span class="text-slate-500 block text-[11px]">Lý do từ chối thẩm định (ketQuaXuLyBaoCao.lyDoTuChoi):</span>
                        <p class="font-semibold text-slate-800 leading-relaxed pt-0.5">${item.ketQuaThucTe.lyDoTuChoi}</p>
                    </div>
                    <div class="text-[11px] text-slate-500 font-mono pt-1 border-t border-slate-200">
                        Thời điểm xem xét: ${item.ketQuaThucTe.ngayXuLy}
                    </div>
                `;
            }
        } else {
            // Biến thể Đã gửi hoặc Đang xử lý
            noiDungKetQuaXuLy.className = "p-3.5 rounded-xl border border-amber-200 bg-amber-50 text-amber-800 text-xs flex items-center gap-2";
            noiDungKetQuaXuLy.innerHTML = `
                <i class="fa-solid fa-hourglass-half text-amber-600"></i>
                <span>Chưa có kết quả, Cơ quan quản lý đang xem xét và tra soát dữ liệu lô trên blockchain.</span>
            `;
        }

        modalChiTiet.classList.remove('hidden');
    }

    function tatChiTiet() {
        modalChiTiet.classList.add('hidden');
    }

    document.getElementById('nutDongChiTiet').addEventListener('click', tatChiTiet);
    document.getElementById('nutDongChiTietBottom').addEventListener('click', tatChiTiet);

    // Lắng nghe tìm kiếm & lọc
    oTimKiem.addEventListener('input', () => { trangHienTai = 1; renderBangBaoCao(); });
    locTrangThai.addEventListener('change', () => { trangHienTai = 1; renderBangBaoCao(); });

    renderBangBaoCao();
});
document.addEventListener('DOMContentLoaded', function () {
    const ITEMS_PER_PAGE = 3;
    let trangHienTai = 1;

    let danhSachBaoCao = [
        {
            maBaoCao: 'BC-DN-2026-001',
            maLo: 'LOT-AMOX-2025-09',
            ngayGui: '2026-02-02 14:20',
            trangThai: 'DA_XU_LY',
            lyDo: 'Phát hiện tem niêm phong trên nắp hộp có dấu hiệu bị bóc dán đè. Hạn sử dụng trên vỉ in đè mực khác với nhãn ngoài vỏ hộp.',
            anhMinhChung: 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=600&auto=format&fit=crop&q=60',
            ketQua: {
                quyetDinh: 'THU_HOI',
                chiTiet: 'Cục Quản lý Dược đã kiểm tra mẫu lưu và ra quyết định THU HỒI khẩn cấp toàn quốc đối với lô LOT-AMOX-2025-09. Smart Contract đã kích hoạt lệnh thu hồi (recallBatch).',
                ngayXuLy: '2026-02-05 16:45',
                txHashRecall: '0x71a49f2b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4e5'
            }
        },
        {
            maBaoCao: 'BC-DN-2026-002',
            maLo: 'LOT-PARA-2026',
            ngayGui: '2026-02-06 09:15',
            trangThai: 'DA_XU_LY',
            lyDo: 'Một thùng hàng số 04 bị móp méo nghiêm trọng trong quá trình vận chuyển, nghi ngờ ẩm mốc ảnh hưởng chất lượng viên nén.',
            anhMinhChung: '',
            ketQua: {
                quyetDinh: 'TU_CHOI',
                chiTiet: 'Kết quả giám định viên nén bên trong vỉ nhôm vẫn đạt tiêu chuẩn độ kín và hàm lượng. Tổ chức tự thực hiện đổi trả vỏ hộp với Nhà sản xuất theo quy trình thương mại.',
                ngayXuLy: '2026-02-08 11:30',
                txHashRecall: ''
            }
        },
        {
            maBaoCao: 'BC-DN-2026-003',
            maLo: 'LOT-CEFA-2024-EX',
            ngayGui: '2026-02-07 10:00',
            trangThai: 'DANG_XU_LY',
            lyDo: 'Lô thuốc nhận từ đơn vị vận chuyển có ngày hết hạn 01/01/2025 nhưng thông tin quét tem QR trả về vẫn hiển thị trạng thái lưu thông.',
            anhMinhChung: 'https://images.unsplash.com/photo-1471864190281-a93a3070b6de?w=600&auto=format&fit=crop&q=60',
            ketQua: null
        },
        {
            maBaoCao: 'BC-DN-2026-004',
            maLo: 'LOT-DES-2026-01',
            ngayGui: '2026-02-08 15:45',
            trangThai: 'DA_GUI',
            lyDo: 'Nghi vấn số đăng ký in trên vỏ hộp lệch 1 ký tự so với danh mục công bố của Cục Quản lý Dược.',
            anhMinhChung: '',
            ketQua: null
        }
    ];

    const thanBang = document.getElementById('thanBangBaoCao');
    const oTimKiem = document.getElementById('oTimKiemBaoCao');
    const locTrangThai = document.getElementById('locTrangThaiBaoCao');
    const soLuongBadge = document.getElementById('soLuongBaoCaoBadge');
    const thongTinPhanTrang = document.getElementById('thongTinPhanTrang');
    const cumNutPhanTrang = document.getElementById('cumNutPhanTrang');

    // ══ 1. BẢNG DANH SÁCH & PHÂN TRANG ══
    function renderBangBaoCao() {
        const tuKhoa = oTimKiem.value.trim().toLowerCase();
        const trangThaiLoc = locTrangThai.value;

        const danhSachLoc = danhSachBaoCao.filter(item => {
            const matchTuKhoa = item.maBaoCao.toLowerCase().includes(tuKhoa) || item.maLo.toLowerCase().includes(tuKhoa);
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
            thanBang.innerHTML = `<tr><td colspan="5" class="text-center py-8 text-slate-400 italic">Không tìm thấy báo cáo nào phù hợp với bộ lọc.</td></tr>`;
            thongTinPhanTrang.textContent = "Không có bản ghi nào";
            cumNutPhanTrang.innerHTML = '';
            return;
        }

        thongTinPhanTrang.textContent = `Đang hiển thị ${batDau + 1} đến ${Math.min(ketThuc, danhSachLoc.length)} trên tổng số ${danhSachLoc.length} báo cáo`;

        duLieuTrang.forEach(item => {
            const badgeTrangThai = taoBadgeTrangThai(item.trangThai);

            const tr = document.createElement('tr');
            tr.className = 'dong-bao-cao-dn transition-colors';
            tr.innerHTML = `
                <td class="py-3.5 px-4 font-mono font-bold text-cyan-900 whitespace-nowrap">
                    ${item.maBaoCao}
                </td>
                <td class="py-3.5 px-4 font-mono font-bold text-slate-800">
                    ${item.maLo}
                </td>
                <td class="py-3.5 px-4 text-slate-600 font-mono text-[11px]">
                    ${item.ngayGui}
                </td>
                <td class="py-3.5 px-4 text-center">
                    ${badgeTrangThai}
                </td>
                <td class="py-3.5 px-4 text-center">
                    <button type="button" class="btn-xem-bc px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-all flex items-center justify-center gap-1.5 mx-auto shadow-2xs">
                        <i class="fa-solid fa-eye"></i> Xem Chi Tiết
                    </button>
                </td>
            `;

            tr.addEventListener('click', () => moChiTietBaoCao(item));
            thanBang.appendChild(tr);
        });

        renderPhanTrang(tongSoTrang);
    }

    // ══ ĐIỀU CHỈNH: BỎ HIỆU ỨNG ĐỘNG (FA-SPIN) CỦA NHÃN "ĐANG XỬ LÝ" ══
    function taoBadgeTrangThai(status) {
        if (status === 'DA_XU_LY') {
            return `<span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300"><i class="fa-solid fa-check-double mr-1"></i> ĐÃ XỬ LÝ</span>`;
        } else if (status === 'DANG_XU_LY') {
            // ĐÃ BỎ fa-spin, dùng icon fa-clock tĩnh gọn gàng, không nhấp nháy chuyển động
            return `<span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300"><i class="fa-solid fa-clock mr-1"></i> ĐANG XỬ LÝ</span>`;
        } else {
            return `<span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-cyan-50 text-cyan-800 border border-cyan-300"><i class="fa-regular fa-paper-plane mr-1"></i> ĐÃ GỬI</span>`;
        }
    }

    // ══ PHÂN TRANG ══
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

    // ══ 2. XỬ LÝ DROPDOWN CHỌN LÔ THUỐC DỄ NHÌN HƠN ══
    const oNhapMaLo = document.getElementById('nhapMaLoLienQuan');
    const menuGoiY = document.getElementById('menuGoiYMaLo');
    const nutMoMenuLo = document.getElementById('nutMoMenuLo');
    const iconMuiTenLo = document.getElementById('iconMuiTenLo');
    const cacMucGoiY = document.querySelectorAll('.item-lo');

    function batMenuLo() {
        menuGoiY.classList.remove('hidden');
        if (iconMuiTenLo) {
            iconMuiTenLo.className = "fa-solid fa-chevron-up";
        }
    }

    function tatMenuLo() {
        menuGoiY.classList.add('hidden');
        if (iconMuiTenLo) {
            iconMuiTenLo.className = "fa-solid fa-chevron-down";
        }
    }

    // Nhấp vào ô input hoặc nút mũi tên để mở menu
    oNhapMaLo.addEventListener('focus', batMenuLo);
    oNhapMaLo.addEventListener('click', batMenuLo);
    nutMoMenuLo.addEventListener('click', function (e) {
        e.stopPropagation();
        if (menuGoiY.classList.contains('hidden')) {
            batMenuLo();
            oNhapMaLo.focus();
        } else {
            tatMenuLo();
        }
    });

    // Lọc danh sách lô theo từ khóa người dùng gõ
    oNhapMaLo.addEventListener('input', function () {
        batMenuLo();
        const kw = this.value.trim().toLowerCase();
        cacMucGoiY.forEach(item => {
            const malo = item.getAttribute('data-malo').toLowerCase();
            const ten = item.getAttribute('data-ten').toLowerCase();
            if (malo.includes(kw) || ten.includes(kw)) {
                item.classList.remove('hidden');
            } else {
                item.classList.add('hidden');
            }
        });
    });

    // Khi chọn một mục trong danh sách
    cacMucGoiY.forEach(item => {
        item.addEventListener('click', function () {
            const maChon = this.getAttribute('data-malo');
            oNhapMaLo.value = maChon;
            tatMenuLo();
        });
    });

    // Nhấp ra ngoài để đóng menu
    document.addEventListener('click', function (e) {
        const khung = document.getElementById('khungChonMaLo');
        if (khung && !khung.contains(e.target)) {
            tatMenuLo();
        }
    });

    // ══ 3. GỬI BÁO CÁO MỚI ══
    const modalGui = document.getElementById('modalGuiBaoCaoMoi');
    const formGui = document.getElementById('formSubmitBaoCao');
    const khungChonTep = document.getElementById('khungChonTepMinhChung');
    const tepInput = document.getElementById('tepMinhChungDN');
    const tenTepHienThi = document.getElementById('tenTepMinhChungDN');

    document.getElementById('nutMoModalGuiBaoCao').addEventListener('click', () => {
        formGui.reset();
        tenTepHienThi.innerHTML = "Nhấp để tải ảnh chụp bao bì, tem nhãn hoặc biên bản kiểm nghiệm";
        tatMenuLo();
        modalGui.classList.remove('hidden');
    });

    document.querySelectorAll('.dong-modal-gui').forEach(btn => {
        btn.addEventListener('click', () => modalGui.classList.add('hidden'));
    });

    khungChonTep.addEventListener('click', () => tepInput.click());
    tepInput.addEventListener('change', function () {
        if (this.files && this.files.length > 0) {
            tenTepHienThi.innerHTML = `<i class="fa-solid fa-file-image text-emerald-700 mr-1"></i> ${this.files[0].name} (${(this.files[0].size / 1024 / 1024).toFixed(2)} MB)`;
        }
    });

    formGui.addEventListener('submit', function (e) {
        e.preventDefault();

        const maLo = oNhapMaLo.value.trim().toUpperCase();
        const lyDo = document.getElementById('nhapLyDoNghiVan').value.trim();

        const maMoi = 'BC-DN-2026-' + String(danhSachBaoCao.length + 1).padStart(3, '0');
        const thoiGian = new Date().toISOString().replace('T', ' ').substring(0, 16);

        const banGhiMoi = {
            maBaoCao: maMoi,
            maLo: maLo,
            ngayGui: thoiGian,
            trangThai: 'DA_GUI',
            lyDo: lyDo,
            anhMinhChung: tepInput.files[0] ? URL.createObjectURL(tepInput.files[0]) : '',
            ketQua: null
        };

        danhSachBaoCao.unshift(banGhiMoi);
        renderBangBaoCao();
        modalGui.classList.add('hidden');

        alert(`Gửi báo cáo nghi vấn thành công! Mã báo cáo: ${maMoi}. Hồ sơ đã chuyển đến Cục Quản lý Dược.`);
    });

    // ══ 4. XEM CHI TIẾT BÁO CÁO ══
    const modalChiTiet = document.getElementById('modalChiTietBaoCao');
    const chiTietMaBaoCao = document.getElementById('chiTietMaBaoCao');
    const chiTietMaLo = document.getElementById('chiTietMaLo');
    const chiTietNgayGui = document.getElementById('chiTietNgayGui');
    const chiTietTrangThaiBadge = document.getElementById('chiTietTrangThaiBadge');
    const chiTietLyDo = document.getElementById('chiTietLyDo');
    const chiTietAnhMinhChung = document.getElementById('chiTietAnhMinhChung');
    const khungKetQuaXuLy = document.getElementById('khungKetQuaXuLy');
    const noiDungKetQuaXuLy = document.getElementById('noiDungKetQuaXuLy');

    function moChiTietBaoCao(item) {
        chiTietMaBaoCao.textContent = item.maBaoCao;
        chiTietMaLo.textContent = item.maLo;
        chiTietNgayGui.textContent = item.ngayGui;
        chiTietLyDo.textContent = item.lyDo;
        chiTietTrangThaiBadge.innerHTML = taoBadgeTrangThai(item.trangThai);

        if (item.anhMinhChung) {
            chiTietAnhMinhChung.innerHTML = `<img src="${item.anhMinhChung}" alt="Bằng chứng" class="w-full h-full object-cover">`;
        } else {
            chiTietAnhMinhChung.innerHTML = `<span class="text-slate-400 italic text-xs">Không có ảnh/tài liệu đính kèm</span>`;
        }

        if (item.ketQua) {
            khungKetQuaXuLy.classList.remove('hidden');
            if (item.ketQua.quyetDinh === 'THU_HOI') {
                noiDungKetQuaXuLy.className = "p-3.5 rounded-xl border border-red-200 bg-red-50 text-red-700 space-y-1.5";
                noiDungKetQuaXuLy.innerHTML = `
                    <div class="font-bold flex items-center gap-1.5 text-xs">
                        <i class="fa-solid fa-ban"></i> QUYẾT ĐỊNH: THU HỒI LÔ THUỐC TRÊN TOÀN CHUỖI CUNG ỨNG
                    </div>
                    <p class="leading-relaxed text-[11px]">${item.ketQua.chiTiet}</p>
                    <div class="pt-1 border-t border-red-200 text-[10px] font-mono text-red-600 flex items-center justify-between">
                        <span>Ngày quyết định: ${item.ketQua.ngayXuLy}</span>
                        <span>txHash: ${item.ketQua.txHashRecall.substring(0, 10)}...</span>
                    </div>
                `;
            } else {
                noiDungKetQuaXuLy.className = "p-3.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-700 space-y-1";
                noiDungKetQuaXuLy.innerHTML = `
                    <div class="font-bold flex items-center gap-1.5 text-xs text-slate-800">
                        <i class="fa-solid fa-circle-xmark text-slate-500"></i> QUYẾT ĐỊNH: TỪ CHỐI BÁO CÁO
                    </div>
                    <p class="leading-relaxed text-[11px]">${item.ketQua.chiTiet}</p>
                    <div class="text-[10px] text-slate-400 font-mono pt-1">Thời điểm xem xét: ${item.ketQua.ngayXuLy}</div>
                `;
            }
        } else {
            khungKetQuaXuLy.classList.remove('hidden');
            noiDungKetQuaXuLy.className = "p-3 rounded-xl border border-amber-200 bg-amber-50 text-amber-800 text-xs";
            noiDungKetQuaXuLy.innerHTML = `<i class="fa-solid fa-clock mr-1 text-amber-600"></i> Báo cáo đang trong quá trình đối soát lịch sử giao dịch trên Blockchain bởi Cục Quản lý Dược.`;
        }

        modalChiTiet.classList.remove('hidden');
    }

    function tatChiTiet() {
        modalChiTiet.classList.add('hidden');
    }

    document.getElementById('nutDongChiTiet').addEventListener('click', tatChiTiet);
    document.getElementById('nutDongChiTietBottom').addEventListener('click', tatChiTiet);

    oTimKiem.addEventListener('input', () => { trangHienTai = 1; renderBangBaoCao(); });
    locTrangThai.addEventListener('change', () => { trangHienTai = 1; renderBangBaoCao(); });

    renderBangBaoCao();
});
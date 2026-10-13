document.addEventListener('DOMContentLoaded', function () {
    const STORAGE_KEY_BAO_CAO = 'pharma_lich_su_bao_cao';

    // Tab Buttons & Containers
    const tabGuiBaoCao = document.getElementById('tabGuiBaoCao');
    const tabLichSuBaoCao = document.getElementById('tabLichSuBaoCao');
    const khungTabGui = document.getElementById('khungTabGuiBaoCao');
    const khungTabLichSu = document.getElementById('khungTabLichSuBaoCao');

    // Tab 1 Elements
    const formGuiBaoCao = document.getElementById('formGuiBaoCao');
    const maLoBaoCao = document.getElementById('maLoBaoCao');
    const lyDoNghiVan = document.getElementById('lyDoNghiVan');
    const khungKeoTha = document.getElementById('khungKeoThaMinhChung');
    const tepMinhChung = document.getElementById('tepMinhChung');
    const tenTepMinhChung = document.getElementById('tenTepMinhChung');

    // Success Modal Elements
    const modalThanhCong = document.getElementById('modalThanhCongBaoCao');
    const maBaoCaoSinhRa = document.getElementById('maBaoCaoSinhRa');
    const nutSaoChepMa = document.getElementById('nutSaoChepMa');
    const nutChuyenSangLichSu = document.getElementById('nutChuyenSangLichSu');
    const nutDongModalThanhCong = document.getElementById('nutDongModalThanhCong');

    // Tab 2 Elements
    const oTraCuuThuCong = document.getElementById('oTraCuuThuCong');
    const nutTraCuuThuCong = document.getElementById('nutTraCuuThuCong');
    const khungBangBaoCao = document.getElementById('khungBangBaoCao');
    const thanBangBaoCao = document.getElementById('thanBangBaoCao');
    const khungBaoCaoRong = document.getElementById('khungBaoCaoRong');
    const soLuongBadge = document.getElementById('soLuongBaoCaoBadge');

    // Detail Modal Elements
    const modalChiTiet = document.getElementById('modalChiTietBaoCao');
    const chiTietMaBaoCao = document.getElementById('chiTietMaBaoCao');
    const chiTietMaLo = document.getElementById('chiTietMaLo');
    const chiTietNgayGui = document.getElementById('chiTietNgayGui');
    const chiTietTrangThaiBadge = document.getElementById('chiTietTrangThaiBadge');
    const chiTietLyDo = document.getElementById('chiTietLyDo');
    const chiTietAnhMinhChung = document.getElementById('chiTietAnhMinhChung');
    const khungKetQuaXuLy = document.getElementById('khungKetQuaXuLy');
    const noiDungKetQuaXuLy = document.getElementById('noiDungKetQuaXuLy');
    const nutDongChiTiet = document.getElementById('nutDongChiTiet');
    const nutDongChiTietBottom = document.getElementById('nutDongChiTietBottom');

    // Dữ liệu mô phỏng đồng bộ với CSDL hệ thống (ketQuaXuLyBaoCao)
    const csdlBaoCaoHeThong = {
        'BC-2026-8812': {
            maBaoCao: 'BC-2026-8812',
            maLo: 'LOT-AMOX-2025-09',
            lyDo: 'Tem niêm phong có dấu hiệu bị bóc ra dán lại. Thuốc có mùi lạ so với bình thường.',
            trangThai: 'DA_XU_LY',
            ngayGui: '02/02/2026 14:20',
            anhMinhChung: 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=600&auto=format&fit=crop&q=60',
            ketQua: {
                quyetDinh: 'THU_HOI',
                chiTiet: 'Cục Quản lý Dược đã kiểm tra mẫu lưu và ra quyết định THU HỒI toàn quốc đối với lô LOT-AMOX-2025-09. Smart Contract đã kích hoạt lệnh recallBatch.',
                ngayXuLy: '05/02/2026 16:45'
            }
        },
        'BC-2026-1049': {
            maBaoCao: 'BC-2026-1049',
            maLo: 'LOT-PARA-2026',
            lyDo: 'Vỏ hộp hơi móp méo khi mua tại quầy số 2.',
            trangThai: 'DA_XU_LY',
            ngayGui: '28/01/2026 09:15',
            anhMinhChung: '',
            ketQua: {
                quyetDinh: 'TU_CHOI',
                chiTiet: 'Lô thuốc chính hãng, quy cách đóng gói bên trong đạt chuẩn chất lượng. Vỏ hộp bị móp nhẹ do quá trình vận chuyển cơ học, không ảnh hưởng chất lượng thuốc.',
                ngayXuLy: '30/01/2026 11:00'
            }
        }
    };

    // ══ CHUYỂN TAB ══
    function batTab1() {
        tabGuiBaoCao.className = "flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all bg-emerald-700 text-white shadow-sm";
        tabLichSuBaoCao.className = "flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all text-slate-600 hover:text-emerald-700 hover:bg-emerald-50";
        khungTabGui.classList.remove('hidden');
        khungTabLichSu.classList.add('hidden');
    }

    function batTab2() {
        tabLichSuBaoCao.className = "flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all bg-emerald-700 text-white shadow-sm";
        tabGuiBaoCao.className = "flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all text-slate-600 hover:text-emerald-700 hover:bg-emerald-50";
        khungTabLichSu.classList.remove('hidden');
        khungTabGui.classList.add('hidden');
        renderLichSuBaoCao();
    }

    tabGuiBaoCao.addEventListener('click', batTab1);
    tabLichSuBaoCao.addEventListener('click', batTab2);

    // Xử lý upload file đính kèm
    khungKeoTha.addEventListener('click', () => tepMinhChung.click());
    tepMinhChung.addEventListener('change', function () {
        if (this.files && this.files.length > 0) {
            tenTepMinhChung.innerHTML = `<i class="fa-solid fa-image text-emerald-700 mr-1"></i> ${this.files[0].name} (${(this.files[0].size / 1024 / 1024).toFixed(2)} MB)`;
        }
    });

    // ══ GỬI FORM BÁO CÁO MỚI ══
    formGuiBaoCao.addEventListener('submit', function (e) {
        e.preventDefault();

        const maLo = maLoBaoCao.value.trim();
        const lyDo = lyDoNghiVan.value.trim();

        if (!maLo || !lyDo) {
            alert("Vui lòng nhập đầy đủ mã lô và lý do nghi vấn.");
            return;
        }

        // Sinh mã báo cáo ngẫu nhiên đúng chuẩn nghiệp vụ (VD: BC-2026-xxxx)
        const maNgauNhien = 'BC-2026-' + Math.floor(1000 + Math.random() * 9000);
        const thoiGianHienTai = new Date().toLocaleString('vi-VN');

        const banGhiMoi = {
            maBaoCao: maNgauNhien,
            maLo: maLo,
            lyDo: lyDo,
            trangThai: 'DA_GUI', // Khởi tạo: ĐÃ GỬI
            ngayGui: thoiGianHienTai,
            anhMinhChung: tepMinhChung.files[0] ? URL.createObjectURL(tepMinhChung.files[0]) : ''
        };

        // Lưu vào localStorage thiết bị
        let danhSach = JSON.parse(localStorage.getItem(STORAGE_KEY_BAO_CAO) || '[]');
        danhSach.unshift(banGhiMoi);
        localStorage.setItem(STORAGE_KEY_BAO_CAO, JSON.stringify(danhSach));

        // Lưu tạm vào CSDL mock bộ nhớ để tra cứu chi tiết
        csdlBaoCaoHeThong[maNgauNhien] = banGhiMoi;

        // Hiển thị mã nổi bật lên Modal
        maBaoCaoSinhRa.textContent = maNgauNhien;
        modalThanhCong.classList.remove('hidden');

        // Reset form
        formGuiBaoCao.reset();
        tenTepMinhChung.innerHTML = `Chụp ảnh / Kéo thả ảnh bao bì vào đây hoặc <span class="text-emerald-700 underline font-bold">Duyệt ảnh</span>`;
    });

    // Nút sao chép mã
    nutSaoChepMa.addEventListener('click', function () {
        navigator.clipboard.writeText(maBaoCaoSinhRa.textContent).then(() => {
            this.innerHTML = `<i class="fa-solid fa-check text-emerald-700"></i> Đã sao chép!`;
            setTimeout(() => {
                this.innerHTML = `<i class="fa-regular fa-copy"></i> Sao chép mã`;
            }, 2000);
        });
    });

    nutDongModalThanhCong.addEventListener('click', () => modalThanhCong.classList.add('hidden'));
    nutChuyenSangLichSu.addEventListener('click', () => {
        modalThanhCong.classList.add('hidden');
        batTab2();
    });

    // ══ TAB 2: RENDER DANH SÁCH LỊCH SỬ ══
    function renderLichSuBaoCao() {
        const danhSach = JSON.parse(localStorage.getItem(STORAGE_KEY_BAO_CAO) || '[]');
        soLuongBadge.textContent = danhSach.length;

        if (danhSach.length === 0) {
            khungBangBaoCao.classList.add('hidden');
            khungBaoCaoRong.classList.remove('hidden');
            return;
        }

        khungBaoCaoRong.classList.add('hidden');
        khungBangBaoCao.classList.remove('hidden');
        thanBangBaoCao.innerHTML = '';

        danhSach.forEach(item => {
            // Đối soát với CSDL mock để lấy trạng thái mới nhất
            const thongTin = csdlBaoCaoHeThong[item.maBaoCao] || item;
            const badgeTrangThai = taoBadgeTrangThai(thongTin.trangThai);

            const tr = document.createElement('tr');
            tr.className = 'dong-bao-cao';
            tr.innerHTML = `
                <td class="py-3.5 px-4 font-mono font-bold text-cyan-800">${item.maBaoCao}</td>
                <td class="py-3.5 px-4 font-mono text-slate-800 font-semibold">${item.maLo}</td>
                <td class="py-3.5 px-4">${badgeTrangThai}</td>
                <td class="py-3.5 px-4 text-slate-500 font-mono text-[11px]">${item.ngayGui}</td>
                <td class="py-3.5 px-4 text-center">
                    <button type="button" class="nut-chi-tiet-bc px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 font-bold text-[11px] transition-all">
                        Xem <i class="fa-solid fa-arrow-right ml-0.5"></i>
                    </button>
                </td>
            `;

            tr.addEventListener('click', () => moChiTietBaoCao(thongTin));
            thanBangBaoCao.appendChild(tr);
        });
    }

    // Tra cứu mã thủ công
    nutTraCuuThuCong.addEventListener('click', function () {
        const code = oTraCuuThuCong.value.trim().toUpperCase();
        if (!code) {
            alert("Vui lòng nhập mã báo cáo cần tra cứu!");
            return;
        }

        const thongTin = csdlBaoCaoHeThong[code];
        if (thongTin) {
            moChiTietBaoCao(thongTin);
        } else {
            alert(`Không tìm thấy báo cáo nào có mã "${code}" trên hệ thống.`);
        }
    });

    // ══ MODAL CHI TIẾT BÁO CÁO ══
    function moChiTietBaoCao(item) {
        chiTietMaBaoCao.textContent = item.maBaoCao;
        chiTietMaLo.textContent = item.maLo;
        chiTietNgayGui.textContent = item.ngayGui;
        chiTietLyDo.textContent = item.lyDo;
        chiTietTrangThaiBadge.innerHTML = taoBadgeTrangThai(item.trangThai);

        // Hiển thị ảnh minh chứng
        if (item.anhMinhChung) {
            chiTietAnhMinhChung.innerHTML = `<img src="${item.anhMinhChung}" alt="Bằng chứng" class="w-full h-full object-cover">`;
        } else {
            chiTietAnhMinhChung.innerHTML = `<span class="text-slate-400 italic text-xs">Không có ảnh đính kèm</span>`;
        }

        // Hiển thị kết quả xử lý nếu đã xử lý
        if (item.ketQua) {
            khungKetQuaXuLy.classList.remove('hidden');
            if (item.ketQua.quyetDinh === 'THU_HOI') {
                noiDungKetQuaXuLy.className = "p-3.5 rounded-xl border border-red-200 bg-red-50 text-red-700 space-y-1";
                noiDungKetQuaXuLy.innerHTML = `
                    <div class="font-bold flex items-center gap-1.5"><i class="fa-solid fa-ban"></i> QUYẾT ĐỊNH: THU HỒI LÔ THUỐC</div>
                    <p>${item.ketQua.chiTiet}</p>
                    <div class="text-[10px] text-red-500 font-mono pt-1">Thời điểm quyết định: ${item.ketQua.ngayXuLy}</div>
                `;
            } else {
                noiDungKetQuaXuLy.className = "p-3.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-700 space-y-1";
                noiDungKetQuaXuLy.innerHTML = `
                    <div class="font-bold flex items-center gap-1.5"><i class="fa-solid fa-circle-xmark"></i> QUYẾT ĐỊNH: TỪ CHỐI BÁO CÁO</div>
                    <p>${item.ketQua.chiTiet}</p>
                    <div class="text-[10px] text-slate-400 font-mono pt-1">Thời điểm xem xét: ${item.ketQua.ngayXuLy}</div>
                `;
            }
        } else {
            khungKetQuaXuLy.classList.remove('hidden');
            noiDungKetQuaXuLy.className = "p-3 rounded-xl border border-amber-200 bg-amber-50 text-amber-800 text-xs";
            noiDungKetQuaXuLy.innerHTML = `<i class="fa-solid fa-hourglass-half mr-1"></i> Báo cáo đang trong quá trình xác minh, đối soát hồ sơ bởi Cơ quan quản lý.`;
        }

        modalChiTiet.classList.remove('hidden');
    }

    function tatChiTiet() {
        modalChiTiet.classList.add('hidden');
    }

    nutDongChiTiet.addEventListener('click', tatChiTiet);
    nutDongChiTietBottom.addEventListener('click', tatChiTiet);

    function taoBadgeTrangThai(status) {
        if (status === 'DA_XU_LY') {
            return `<span class="px-2.5 py-1 rounded-full text-[11px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-200"><i class="fa-solid fa-check-double mr-1"></i> ĐÃ XỬ LÝ</span>`;
        } else if (status === 'DANG_XU_LY') {
            return `<span class="px-2.5 py-1 rounded-full text-[11px] font-extrabold bg-amber-100 text-amber-800 border border-amber-200"><i class="fa-solid fa-spinner fa-spin mr-1"></i> ĐANG XỬ LÝ</span>`;
        } else {
            return `<span class="px-2.5 py-1 rounded-full text-[11px] font-extrabold bg-cyan-50 text-cyan-800 border border-cyan-800/20"><i class="fa-regular fa-paper-plane mr-1"></i> ĐÃ GỬI</span>`;
        }
    }
});
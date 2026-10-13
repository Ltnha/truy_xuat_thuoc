document.addEventListener('DOMContentLoaded', function () {
    const EXPLORER_BASE_URL = "https://amoy.polygonscan.com/tx/";

    function layNgayHienTai() {
        return new Date().toISOString().split('T')[0];
    }

    function dinhDangNgay(ngayYMD) {
        if (!ngayYMD) return '--';
        const parts = ngayYMD.split('-');
        if (parts.length === 3) return `${parts[2]}/${parts[1]}/${parts[0]}`;
        return ngayYMD;
    }

    function layThoiGianHienTaiStr() {
        const now = new Date();
        const dd = String(now.getDate()).padStart(2, '0');
        const mm = String(now.getMonth() + 1).padStart(2, '0');
        const yyyy = now.getFullYear();
        const hh = String(now.getHours()).padStart(2, '0');
        const min = String(now.getMinutes()).padStart(2, '0');
        return `${dd}/${mm}/${yyyy} ${hh}:${min}`;
    }

    // ══ 1. DỮ LIỆU TỒN KHO THEO LÔ TẠI NHÀ THUỐC ══
    let danhSachTonKhoLo = [
        {
            maLo: 'LOT-PARA-2026',
            tenSanPham: 'Paracetamol 500mg',
            hanDung: '2029-01-15',
            tongNhan: 2500,
            daBan: 850,
            trangThaiLo: 'CREATED',
            lyDoThuHoi: ''
        },
        {
            maLo: 'LOT-DES-2026-01',
            tenSanPham: 'Deslotid OPV 5mg',
            hanDung: '2028-02-05',
            tongNhan: 800,
            daBan: 120,
            trangThaiLo: 'CREATED',
            lyDoThuHoi: ''
        },
        {
            maLo: 'LOT-AMOX-2025-09',
            tenSanPham: 'Amoxicillin 500mg',
            hanDung: '2028-09-10',
            tongNhan: 500,
            daBan: 120,
            trangThaiLo: 'RECALLED',
            lyDoThuHoi: 'Mẫu thuốc lưu không đạt độ hòa tan theo công văn 458/QLD'
        },
        {
            maLo: 'LOT-CEFA-2024-EX',
            tenSanPham: 'Cefalexin 500mg',
            hanDung: '2025-01-01',
            tongNhan: 500,
            daBan: 300,
            trangThaiLo: 'CREATED',
            lyDoThuHoi: ''
        }
    ];

    // CSDL MÔ PHỎNG ĐƠN VỊ THUỐC (donViSanPham)
    let danhSachDonViTong = [
        { serial: 'UNIT-LOT-PARA-2026-00001', maLo: 'LOT-PARA-2026', tenSanPham: 'Paracetamol 500mg', hanDung: '2029-01-15', trangThai: 'SOLD', ngayBan: '05/02/2026 14:20', txHash: '0x8f2d5e1b4a3c79a2f60e1d8b5c9a4e2f1a3b5c7d9e1f2a4b6c8d0e2f4a6b8c0' },
        { serial: 'UNIT-LOT-PARA-2026-00142', maLo: 'LOT-PARA-2026', tenSanPham: 'Paracetamol 500mg', hanDung: '2029-01-15', trangThai: 'AVAILABLE', ngayBan: null, txHash: null },
        { serial: 'UNIT-LOT-PARA-2026-00143', maLo: 'LOT-PARA-2026', tenSanPham: 'Paracetamol 500mg', hanDung: '2029-01-15', trangThai: 'AVAILABLE', ngayBan: null, txHash: null },
        { serial: 'UNIT-LOT-PARA-2026-00144', maLo: 'LOT-PARA-2026', tenSanPham: 'Paracetamol 500mg', hanDung: '2029-01-15', trangThai: 'AVAILABLE', ngayBan: null, txHash: null },
        { serial: 'UNIT-LOT-DES-2026-00012', maLo: 'LOT-DES-2026-01', tenSanPham: 'Deslotid OPV 5mg', hanDung: '2028-02-05', trangThai: 'AVAILABLE', ngayBan: null, txHash: null },
        { serial: 'UNIT-LOT-AMOX-2025-00088', maLo: 'LOT-AMOX-2025-09', tenSanPham: 'Amoxicillin 500mg', hanDung: '2028-09-10', trangThai: 'AVAILABLE', ngayBan: null, txHash: null },
        { serial: 'UNIT-LOT-CEFA-2024-00015', maLo: 'LOT-CEFA-2024-EX', tenSanPham: 'Cefalexin 500mg', hanDung: '2025-01-01', trangThai: 'AVAILABLE', ngayBan: null, txHash: null }
    ];

    let gioHang = [];

    let danhSachLichSuBan = [
        {
            serial: 'UNIT-LOT-PARA-2026-00001',
            maLo: 'LOT-PARA-2026',
            tenSanPham: 'Paracetamol 500mg',
            hanDung: '2029-01-15',
            ngayBan: '05/02/2026 14:20',
            txHash: '0x8f2d5e1b4a3c79a2f60e1d8b5c9a4e2f1a3b5c7d9e1f2a4b6c8d0e2f4a6b8c0'
        }
    ];

    // ══ 2. KIỂM TRA ĐƠN VỊ VÀ ĐƯA VÀO GIỎ BÁN ══
    const khungThongBao = document.getElementById('khungThongBaoKiemTra');

    function kiemTraVaThemDonVi(serialInput) {
        const serial = serialInput.trim().toUpperCase();
        if (!serial) return;

        if (gioHang.some(x => x.serial === serial)) {
            hienThongBao('canhBao', `Hộp thuốc có mã serial <strong>${serial}</strong> đã có sẵn trong giỏ bán hàng!`);
            return;
        }

        const donVi = danhSachDonViTong.find(x => x.serial === serial);
        if (!donVi) {
            hienThongBao('loiKhongThuocKho', `Không tìm thấy mã serial <strong>${serial}</strong> hoặc không thuộc quyền quản lý của nhà thuốc!`, serial);
            return;
        }

        const loTonKho = danhSachTonKhoLo.find(x => x.maLo === donVi.maLo);
        if (!loTonKho) {
            hienThongBao('loiKhongThuocKho', `Mã serial <strong>${serial}</strong> thuộc lô ${donVi.maLo} không tồn tại trong kho nhà thuốc!`, donVi.maLo);
            return;
        }

        if (donVi.trangThai === 'SOLD') {
            hienThongBao('loiDaBan', `Đơn vị này đã được bán trước đó vào lúc ${donVi.ngayBan}!`);
            return;
        }

        if (loTonKho.trangThaiLo === 'RECALLED') {
            hienThongBao('loiThuHoi', `CẢNH BÁO: Lô thuốc <strong>${donVi.maLo}</strong> đã có quyết định THU HỒI trên toàn hệ thống! Nghiêm cấm bán.`);
            return;
        }

        const homNay = layNgayHienTai();
        if (donVi.hanDung < homNay) {
            hienThongBao('loiHetHan', `CẢNH BÁO: Thuốc đã hết hạn sử dụng (${dinhDangNgay(donVi.hanDung)})! Không thể xuất bán.`);
            return;
        }

        const conBan = loTonKho.tongNhan - loTonKho.daBan;
        const soLuongDangChonTrongGio = gioHang.filter(x => x.maLo === donVi.maLo).length;
        if (conBan - soLuongDangChonTrongGio <= 0) {
            hienThongBao('canhBao', `Lô thuốc <strong>${donVi.maLo}</strong> đã hết số lượng còn bán được trong kho!`);
            return;
        }

        gioHang.push({
            serial: donVi.serial,
            maLo: donVi.maLo,
            tenSanPham: donVi.tenSanPham,
            hanDung: donVi.hanDung
        });

        hienThongBao('thanhCong', `Đã thêm vào giỏ: <strong>${donVi.tenSanPham}</strong> (Serial: ${donVi.serial})`);
        capNhatGioHang();
    }

    function hienThongBao(loai, noiDungHtml, maLoHoacSerial = '') {
        khungThongBao.classList.remove('hidden');

        if (loai === 'thanhCong') {
            khungThongBao.className = "p-3.5 rounded-xl border border-emerald-300 bg-emerald-50 text-emerald-900 text-xs flex items-center gap-2";
            khungThongBao.innerHTML = `<i class="fa-solid fa-circle-check text-emerald-700 text-base shrink-0"></i> <div>${noiDungHtml}</div>`;
        } else if (loai === 'loiDaBan') {
            khungThongBao.className = "p-3.5 rounded-xl border border-red-300 bg-red-50 text-red-900 text-xs flex items-center gap-2";
            khungThongBao.innerHTML = `<i class="fa-solid fa-circle-xmark text-red-600 text-base shrink-0"></i> <div>${noiDungHtml}</div>`;
        } else if (loai === 'loiThuHoi') {
            khungThongBao.className = "p-3.5 rounded-xl border border-red-400 bg-red-100 text-red-950 text-xs flex items-center gap-2";
            khungThongBao.innerHTML = `<i class="fa-solid fa-ban text-red-700 text-lg shrink-0"></i> <div>${noiDungHtml}</div>`;
        } else if (loai === 'loiHetHan') {
            khungThongBao.className = "p-3.5 rounded-xl border border-amber-300 bg-amber-50 text-amber-900 text-xs flex items-center gap-2";
            khungThongBao.innerHTML = `<i class="fa-solid fa-triangle-exclamation text-amber-600 text-base shrink-0"></i> <div>${noiDungHtml}</div>`;
        } else if (loai === 'loiKhongThuocKho') {
            const linkBc = `../trangDungChung/quanLyBaoCaoNghiVan.php?vaiTro=NHA_THUOC&maLo=${encodeURIComponent(maLoHoacSerial)}`;
            khungThongBao.className = "p-3.5 rounded-xl border border-red-300 bg-red-50 text-red-900 text-xs space-y-1.5";
            khungThongBao.innerHTML = `
                <div class="flex items-center gap-2 font-bold text-red-700">
                    <i class="fa-solid fa-triangle-exclamation"></i> CẢNH BÁO: HÀNG KHÔNG XÁC ĐỊNH TRÊN HỆ THỐNG
                </div>
                <div>${noiDungHtml}</div>
                <div class="pt-1">
                    <a href="${linkBc}" class="inline-flex items-center gap-1.5 text-amber-800 hover:text-amber-950 font-bold hover:underline">
                        <i class="fa-solid fa-flag"></i> Nghi ngờ hàng giả/lậu? Nhấp vào đây để Báo cáo nghi vấn ngay
                    </a>
                </div>
            `;
        } else {
            khungThongBao.className = "p-3.5 rounded-xl border border-slate-200 bg-slate-100 text-slate-800 text-xs flex items-center gap-2";
            khungThongBao.innerHTML = `<i class="fa-solid fa-circle-info text-slate-500 text-base shrink-0"></i> <div>${noiDungHtml}</div>`;
        }
    }

    // ══ 3. TẢI ẢNH MÃ QR LÊN (THAY THẾ CHUYỂN ĐỘNG QUÉT CAMERA) ══
    const khungTaiAnhQR = document.getElementById('khungTaiAnhQR');
    const tepAnhQRInput = document.getElementById('tepAnhQRInput');
    const khungChuaChonAnh = document.getElementById('khungChuaChonAnh');
    const khungDaChonAnh = document.getElementById('khungDaChonAnh');
    const anhQRPreview = document.getElementById('anhQRPreview');
    const tenAnhQRDangChon = document.getElementById('tenAnhQRDangChon');
    const nutXoaAnhQR = document.getElementById('nutXoaAnhQR');

    khungTaiAnhQR.addEventListener('click', function (e) {
        if (e.target.closest('#nutXoaAnhQR')) return;
        tepAnhQRInput.click();
    });

    tepAnhQRInput.addEventListener('change', function () {
        if (this.files && this.files[0]) {
            const file = this.files[0];
            const reader = new FileReader();

            reader.onload = function (e) {
                anhQRPreview.src = e.target.result;
                tenAnhQRDangChon.textContent = file.name;
                khungChuaChonAnh.classList.add('hidden');
                khungDaChonAnh.classList.remove('hidden');

                // Mô phỏng giải mã QR từ ảnh tải lên: Nhận diện mã serial hợp lệ
                const serialNhanDien = 'UNIT-LOT-PARA-2026-00143';
                kiemTraVaThemDonVi(serialNhanDien);
            };

            reader.readAsDataURL(file);
        }
    });

    nutXoaAnhQR.addEventListener('click', function (e) {
        e.stopPropagation();
        tepAnhQRInput.value = '';
        anhQRPreview.src = '';
        khungDaChonAnh.classList.add('hidden');
        khungChuaChonAnh.classList.remove('hidden');
    });

    // Nút chọn mẫu thử nghiệm nhanh
    document.querySelectorAll('.btn-demo-quet').forEach(btn => {
        btn.addEventListener('click', function () {
            const serial = this.getAttribute('data-serial');
            kiemTraVaThemDonVi(serial);
        });
    });

    // Form nhập serial thủ công
    document.getElementById('formNhapSerialThuCong').addEventListener('submit', function (e) {
        e.preventDefault();
        const input = document.getElementById('nhapSerialInput');
        kiemTraVaThemDonVi(input.value);
        input.value = '';
    });

    // ══ 4. RENDER GIỎ BÁN HÀNG ══
    const thanBangGio = document.getElementById('thanBangGioHang');
    const badgeTongGio = document.getElementById('badgeTongSoGioHang');
    const nutXacNhanBan = document.getElementById('nutXacNhanBanHang');

    function capNhatGioHang() {
        badgeTongGio.textContent = `${gioHang.length} hộp`;
        thanBangGio.innerHTML = '';

        if (gioHang.length === 0) {
            thanBangGio.innerHTML = `<tr><td colspan="6" class="text-center py-8 text-slate-400 italic">Chưa có hộp thuốc nào được chọn. Tải ảnh mã QR hoặc nhập số serial ở trên để thêm vào giỏ.</td></tr>`;
            nutXacNhanBan.disabled = true;
            nutXacNhanBan.className = "px-6 py-2.5 bg-slate-200 text-slate-400 font-bold rounded-xl text-xs transition-all flex items-center gap-2 cursor-not-allowed shadow-none";
            return;
        }

        nutXacNhanBan.disabled = false;
        nutXacNhanBan.className = "px-6 py-2.5 bg-cyan-700 hover:bg-cyan-800 text-white font-bold rounded-xl text-xs transition-all flex items-center gap-2 shadow-md";

        gioHang.forEach((item, index) => {
            const tr = document.createElement('tr');
            tr.className = 'dong-gio-hang transition-colors';
            tr.innerHTML = `
                <td class="py-3 px-3 font-mono font-bold text-slate-900 whitespace-nowrap">${item.serial}</td>
                <td class="py-3 px-3 font-semibold text-slate-900 leading-tight">${item.tenSanPham}</td>
                <td class="py-3 px-3 font-mono font-bold text-cyan-800 whitespace-nowrap">${item.maLo}</td>
                <td class="py-3 px-2.5 text-center font-mono text-slate-600 text-[11px] whitespace-nowrap">${dinhDangNgay(item.hanDung)}</td>
                <td class="py-3 px-2.5 text-center whitespace-nowrap">
                    <span class="badge-chuan px-2 py-0.5 rounded text-[10px] font-bold bg-cyan-100 text-cyan-800 border border-cyan-300">
                        Chờ bán
                    </span>
                </td>
                <td class="py-3 px-2.5 text-center whitespace-nowrap">
                    <button type="button" class="btn-xoa-khoi-gio p-1.5 text-slate-400 hover:text-red-600 rounded transition-colors" data-index="${index}" title="Xóa khỏi giỏ">
                        <i class="fa-solid fa-trash-can text-sm"></i>
                    </button>
                </td>
            `;
            thanBangGio.appendChild(tr);
        });

        document.querySelectorAll('.btn-xoa-khoi-gio').forEach(btn => {
            btn.addEventListener('click', function () {
                const idx = parseInt(this.getAttribute('data-index'));
                gioHang.splice(idx, 1);
                capNhatGioHang();
            });
        });
    }

    // ══ 5. XÁC NHẬN BÁN & KÝ BLOCKCHAIN (KHÔNG DÙNG CHUYỂN ĐỘNG SPIN) ══
    const modalKetQua = document.getElementById('modalKetQuaBanHang');
    const thanhProgress = document.getElementById('thanhProgressOnChain');
    const textProgress = document.getElementById('textTienDoOnChain');
    const danhSachKetQuaDonVi = document.getElementById('danhSachKetQuaDonVi');

    nutXacNhanBan.addEventListener('click', function () {
        if (gioHang.length === 0) return;

        let danhSachBiLoi = [];
        const homNay = layNgayHienTai();

        gioHang.forEach(item => {
            const lo = danhSachTonKhoLo.find(x => x.maLo === item.maLo);
            if (!lo || lo.trangThaiLo === 'RECALLED') {
                danhSachBiLoi.push({ item, lyDo: 'Lô thuốc vừa có quyết định thu hồi khẩn cấp!' });
            } else if (item.hanDung < homNay) {
                danhSachBiLoi.push({ item, lyDo: 'Thuốc đã quá hạn sử dụng!' });
            }
        });

        modalKetQua.classList.remove('hidden');
        danhSachKetQuaDonVi.innerHTML = '';
        thanhProgress.style.width = '100%';
        textProgress.textContent = `${gioHang.length}/${gioHang.length} đơn vị`;

        const ketQuaGiaoDich = [];
        const thoiGianBan = layThoiGianHienTaiStr();

        gioHang.forEach(item => {
            const biLoi = danhSachBiLoi.find(x => x.item.serial === item.serial);
            if (biLoi) {
                ketQuaGiaoDich.push({
                    item: item,
                    thanhCong: false,
                    lyDo: biLoi.lyDo
                });
            } else {
                const randomHash = '0x' + Array.from({length: 64}, () => Math.floor(Math.random()*16).toString(16)).join('');
                ketQuaGiaoDich.push({
                    item: item,
                    thanhCong: true,
                    txHash: randomHash,
                    thoiGian: thoiGianBan
                });
            }
        });

        renderKetQuaDonVi(ketQuaGiaoDich);
        hoanTatQuyTrinhBan(ketQuaGiaoDich);
    });

    function renderKetQuaDonVi(danhSach) {
        danhSachKetQuaDonVi.innerHTML = '';
        danhSach.forEach(kq => {
            const div = document.createElement('div');
            if (kq.thanhCong) {
                div.className = 'p-2.5 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center justify-between';
                div.innerHTML = `
                    <div class="truncate pr-2">
                        <span class="font-bold text-slate-800 block truncate">${kq.item.tenSanPham}</span>
                        <span class="font-mono text-[11px] text-slate-500">${kq.item.serial}</span>
                    </div>
                    <span class="badge-chuan px-2 py-0.5 rounded text-[10px] font-extrabold bg-emerald-200 text-emerald-800 shrink-0">
                        <i class="fa-solid fa-check"></i> Đã bán
                    </span>
                `;
            } else {
                div.className = 'p-2.5 bg-red-50 border border-red-200 rounded-xl flex items-center justify-between';
                div.innerHTML = `
                    <div class="truncate pr-2">
                        <span class="font-bold text-slate-800 block truncate">${kq.item.tenSanPham}</span>
                        <span class="font-mono text-[11px] text-red-600">${kq.lyDo}</span>
                    </div>
                    <span class="badge-chuan px-2 py-0.5 rounded text-[10px] font-extrabold bg-red-200 text-red-800 shrink-0">
                        <i class="fa-solid fa-xmark"></i> Thất bại
                    </span>
                `;
            }
            danhSachKetQuaDonVi.appendChild(div);
        });
    }

    function hoanTatQuyTrinhBan(ketQuaGiaoDich) {
        ketQuaGiaoDich.forEach(kq => {
            if (kq.thanhCong) {
                const dv = danhSachDonViTong.find(x => x.serial === kq.item.serial);
                if (dv) {
                    dv.trangThai = 'SOLD';
                    dv.ngayBan = kq.thoiGian;
                    dv.txHash = kq.txHash;
                }

                const lo = danhSachTonKhoLo.find(x => x.maLo === kq.item.maLo);
                if (lo) {
                    lo.daBan += 1;
                }

                danhSachLichSuBan.unshift({
                    serial: kq.item.serial,
                    maLo: kq.item.maLo,
                    tenSanPham: kq.item.tenSanPham,
                    hanDung: kq.item.hanDung,
                    ngayBan: kq.thoiGian,
                    txHash: kq.txHash
                });
            }
        });

        gioHang = gioHang.filter(item => {
            const kq = ketQuaGiaoDich.find(x => x.item.serial === item.serial);
            return kq && !kq.thanhCong;
        });

        capNhatGioHang();
        renderTabTonKhoLo();
        renderTabLichSuBan();
    }

    document.querySelectorAll('.dong-modal-ket-qua').forEach(btn => {
        btn.addEventListener('click', () => modalKetQua.classList.add('hidden'));
    });
    document.getElementById('nutHoanTatGiaoDichBan').addEventListener('click', () => modalKetQua.classList.add('hidden'));

    // ══ 6. RENDER TAB TỒN KHO THEO LÔ ══
    const thanBangTonKhoLo = document.getElementById('thanBangTonKhoLo');
    const badgeSoLuongTonKho = document.getElementById('badgeSoLuongTonKho');

    function renderTabTonKhoLo() {
        const tuKhoa = document.getElementById('oTimKiemTonKho').value.trim().toLowerCase();
        badgeSoLuongTonKho.textContent = danhSachTonKhoLo.length;
        thanBangTonKhoLo.innerHTML = '';

        const homNay = layNgayHienTai();

        const danhSachLoc = danhSachTonKhoLo.filter(item => {
            return item.maLo.toLowerCase().includes(tuKhoa) || item.tenSanPham.toLowerCase().includes(tuKhoa);
        });

        danhSachLoc.forEach(item => {
            const daHetHan = item.hanDung < homNay;
            const biThuHoi = item.trangThaiLo === 'RECALLED';
            const conBan = Math.max(0, item.tongNhan - item.daBan);

            let trangThaiHtml = '';
            if (biThuHoi) {
                trangThaiHtml = `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-red-100 text-red-700 border border-red-300"><i class="fa-solid fa-ban"></i> Đã thu hồi</span>`;
            } else if (daHetHan) {
                trangThaiHtml = `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300"><i class="fa-solid fa-triangle-exclamation"></i> Hết hạn</span>`;
            } else if (conBan === 0) {
                trangThaiHtml = `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-slate-100 text-slate-600 border border-slate-300">Hết hàng</span>`;
            } else {
                trangThaiHtml = `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300"><i class="fa-solid fa-check"></i> Còn bán được</span>`;
            }

            const tr = document.createElement('tr');
            tr.className = 'dong-ton-kho transition-colors';
            tr.innerHTML = `
                <td class="py-3 px-3 font-mono font-bold text-slate-900 whitespace-nowrap">${item.maLo}</td>
                <td class="py-3 px-3 font-semibold text-slate-900 leading-tight">${item.tenSanPham}</td>
                <td class="py-3 px-2.5 text-center font-mono text-slate-600 text-[11px] whitespace-nowrap">${dinhDangNgay(item.hanDung)}</td>
                <td class="py-3 px-3 text-right font-mono font-semibold whitespace-nowrap">${item.tongNhan.toLocaleString()} hộp</td>
                <td class="py-3 px-3 text-right font-mono font-semibold whitespace-nowrap">${item.daBan.toLocaleString()} hộp</td>
                <td class="py-3 px-3 text-right font-mono font-bold whitespace-nowrap ${conBan > 0 && !biThuHoi && !daHetHan ? 'text-emerald-700' : 'text-slate-400'}">
                    ${biThuHoi ? '0 hộp' : conBan.toLocaleString() + ' hộp'}
                </td>
                <td class="py-3 px-2.5 text-center whitespace-nowrap">${trangThaiHtml}</td>
                <td class="py-3 px-3 text-center whitespace-nowrap">
                    <button type="button" class="btn-chi-tiet-ton-kho px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-all border border-slate-300 shadow-2xs" data-malo="${item.maLo}">
                        <i class="fa-solid fa-eye text-cyan-800"></i> Chi Tiết
                    </button>
                </td>
            `;
            thanBangTonKhoLo.appendChild(tr);
        });

        document.querySelectorAll('.btn-chi-tiet-ton-kho').forEach(btn => {
            btn.addEventListener('click', function () {
                const malo = this.getAttribute('data-malo');
                moModalChiTietTonKhoLo(malo);
            });
        });
    }

    // ══ 7. RENDER TAB LỊCH SỬ ĐÃ BÁN ══
    const thanBangLichSu = document.getElementById('thanBangLichSuBan');
    const badgeSoLuongDaBan = document.getElementById('badgeSoLuongDaBan');

    function renderTabLichSuBan() {
        const tuKhoa = document.getElementById('oTimKiemLichSuBan').value.trim().toLowerCase();
        badgeSoLuongDaBan.textContent = danhSachLichSuBan.length;
        thanBangLichSu.innerHTML = '';

        const danhSachLoc = danhSachLichSuBan.filter(item => {
            return item.serial.toLowerCase().includes(tuKhoa) || item.tenSanPham.toLowerCase().includes(tuKhoa) || item.maLo.toLowerCase().includes(tuKhoa);
        });

        if (danhSachLoc.length === 0) {
            thanBangLichSu.innerHTML = `<tr><td colspan="6" class="text-center py-6 text-slate-400 italic">Chưa có giao dịch bán nào phù hợp.</td></tr>`;
            return;
        }

        danhSachLoc.forEach(item => {
            const hexRutGon = `${item.txHash.substring(0, 8)}...${item.txHash.substring(item.txHash.length - 6)}`;
            const explorerLink = `${EXPLORER_BASE_URL}${item.txHash}`;

            const tr = document.createElement('tr');
            tr.className = 'dong-lich-su transition-colors';
            tr.innerHTML = `
                <td class="py-3 px-3 font-mono font-bold text-cyan-900 whitespace-nowrap">${item.serial}</td>
                <td class="py-3 px-3 font-semibold text-slate-900 leading-tight">${item.tenSanPham}</td>
                <td class="py-3 px-3 font-mono font-bold text-slate-800 whitespace-nowrap">${item.maLo}</td>
                <td class="py-3 px-2.5 text-center font-mono text-[11px] text-slate-500 whitespace-nowrap">${item.ngayBan}</td>
                <td class="py-3 px-3 whitespace-nowrap">
                    <a href="${explorerLink}" target="_blank" class="font-mono text-[11px] font-bold text-cyan-800 hover:underline flex items-center gap-1">
                        ${hexRutGon} <i class="fa-solid fa-arrow-up-right-from-square text-[9px] text-slate-400"></i>
                    </a>
                </td>
                <td class="py-3 px-2.5 text-center whitespace-nowrap">
                    <button type="button" class="btn-chi-tiet-donvi px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-all border border-slate-300 shadow-2xs" data-serial="${item.serial}">
                        <i class="fa-solid fa-circle-info text-cyan-800"></i> Chi Tiết
                    </button>
                </td>
            `;
            thanBangLichSu.appendChild(tr);
        });

        document.querySelectorAll('.btn-chi-tiet-donvi').forEach(btn => {
            btn.addEventListener('click', function () {
                const serial = this.getAttribute('data-serial');
                moModalChiTietDonVi(serial);
            });
        });
    }

    // ══ 8. MODAL CHI TIẾT TỒN KHO LÔ ══
    const modalTonKhoLo = document.getElementById('modalChiTietTonKhoLo');
    const bannerCanhBaoLo = document.getElementById('modalTonKhoBannerCanhBao');
    let loDangXem = null;

    function moModalChiTietTonKhoLo(maLo) {
        loDangXem = danhSachTonKhoLo.find(x => x.maLo === maLo);
        if (!loDangXem) return;

        const homNay = layNgayHienTai();
        const daHetHan = loDangXem.hanDung < homNay;
        const biThuHoi = loDangXem.trangThaiLo === 'RECALLED';
        const conBan = Math.max(0, loDangXem.tongNhan - loDangXem.daBan);

        document.getElementById('modalTonKhoMaLoTitle').textContent = loDangXem.maLo;
        document.getElementById('modalTonKhoSanPham').textContent = loDangXem.tenSanPham;
        document.getElementById('modalTonKhoHanDung').textContent = dinhDangNgay(loDangXem.hanDung);
        document.getElementById('modalTonKhoTongNhan').textContent = `${loDangXem.tongNhan.toLocaleString()} hộp`;
        document.getElementById('modalTonKhoConBan').textContent = `${conBan.toLocaleString()} hộp`;
        document.getElementById('linkBaoCaoTuModalTonKho').href = `../trangDungChung/quanLyBaoCaoNghiVan.php?vaiTro=NHA_THUOC&maLo=${encodeURIComponent(loDangXem.maLo)}`;

        if (biThuHoi) {
            bannerCanhBaoLo.className = "p-3.5 bg-red-50 border border-red-200 rounded-xl text-xs text-red-900 block space-y-1";
            bannerCanhBaoLo.innerHTML = `
                <div class="font-bold flex items-center gap-1.5 text-red-700">
                    <i class="fa-solid fa-ban text-sm"></i> CẢNH BÁO: LÔ THUỐC ĐÃ BỊ THU HỒI TRÊN TOÀN QUỐC
                </div>
                <div>Lý do thu hồi: <strong>${loDangXem.lyDoThuHoi}</strong></div>
                <div class="font-bold text-red-800">Yêu cầu ngừng bán ngay lập tức và đưa toàn bộ số hộp còn lại ra khỏi quầy hàng!</div>
            `;
        } else if (daHetHan) {
            bannerCanhBaoLo.className = "p-3.5 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-900 block space-y-1";
            bannerCanhBaoLo.innerHTML = `
                <div class="font-bold flex items-center gap-1.5 text-amber-700">
                    <i class="fa-solid fa-triangle-exclamation text-sm"></i> CẢNH BÁO: LÔ THUỐC ĐÃ HẾT HẠN SỬ DỤNG
                </div>
                <div>Hạn dùng (${dinhDangNgay(loDangXem.hanDung)}) đã quá hạn. Sản phẩm không còn đủ điều kiện bán lẻ.</div>
            `;
        } else {
            bannerCanhBaoLo.className = "hidden";
        }

        renderDanhSachSerialTrongLo();
        modalTonKhoLo.classList.remove('hidden');
    }

    function renderDanhSachSerialTrongLo() {
        if (!loDangXem) return;
        const kw = document.getElementById('oTimKiemSerialLo').value.trim().toUpperCase();
        const locTT = document.getElementById('locTrangThaiSerialLo').value;
        const thanBang = document.getElementById('thanBangSerialLo');
        thanBang.innerHTML = '';

        const ds = danhSachDonViTong.filter(x => {
            const matchLo = (x.maLo === loDangXem.maLo);
            const matchKw = x.serial.includes(kw);
            const matchTT = (locTT === 'TAT_CA') || (x.trangThai === locTT);
            return matchLo && matchKw && matchTT;
        });

        if (ds.length === 0) {
            thanBang.innerHTML = `<tr><td colspan="3" class="text-center py-4 text-slate-400 font-sans italic">Không tìm thấy mã serial nào phù hợp.</td></tr>`;
            return;
        }

        ds.forEach(item => {
            const daBan = (item.trangThai === 'SOLD');
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-slate-50';
            tr.innerHTML = `
                <td class="p-2.5 font-bold text-slate-800">${item.serial}</td>
                <td class="p-2.5 text-center font-sans">
                    <span class="badge-chuan px-2 py-0.5 rounded text-[10px] font-bold ${daBan ? 'bg-slate-200 text-slate-700' : 'bg-emerald-100 text-emerald-800'}">
                        ${daBan ? 'Đã bán' : 'Chưa bán'}
                    </span>
                </td>
                <td class="p-2.5 text-center font-sans">
                    <button type="button" class="btn-xem-dv-tu-lo px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-[11px] font-bold" data-serial="${item.serial}">
                        Xem
                    </button>
                </td>
            `;
            thanBang.appendChild(tr);
        });

        document.querySelectorAll('.btn-xem-dv-tu-lo').forEach(btn => {
            btn.addEventListener('click', function () {
                const s = this.getAttribute('data-serial');
                moModalChiTietDonVi(s);
            });
        });
    }

    document.getElementById('oTimKiemSerialLo').addEventListener('input', renderDanhSachSerialTrongLo);
    document.getElementById('locTrangThaiSerialLo').addEventListener('change', renderDanhSachSerialTrongLo);
    document.querySelectorAll('.dong-modal-tonkho').forEach(btn => btn.addEventListener('click', () => modalTonKhoLo.classList.add('hidden')));

    // ══ 9. MODAL CHI TIẾT ĐƠN VỊ THUỐC ══
    const modalDonVi = document.getElementById('modalChiTietDonVi');

    function moModalChiTietDonVi(serial) {
        const dv = danhSachDonViTong.find(x => x.serial === serial);
        if (!dv) return;

        document.getElementById('modalDonViSerialTitle').textContent = dv.serial;
        document.getElementById('modalDonViMaLo').textContent = dv.maLo;
        document.getElementById('modalDonViTenThuoc').textContent = dv.tenSanPham;
        document.getElementById('modalDonViHanDung').textContent = dinhDangNgay(dv.hanDung);
        document.getElementById('modalDonViNgayBan').textContent = dv.ngayBan ? dv.ngayBan : 'Chưa bán';

        const ttBox = document.getElementById('modalDonViTrangThai');
        if (dv.trangThai === 'SOLD') {
            ttBox.innerHTML = `<span class="badge-chuan px-2 py-0.5 rounded text-[10px] font-extrabold bg-slate-200 text-slate-800">Đã bán</span>`;
        } else {
            ttBox.innerHTML = `<span class="badge-chuan px-2 py-0.5 rounded text-[10px] font-extrabold bg-emerald-100 text-emerald-800">Còn bán được</span>`;
        }

        const txHash = dv.txHash || 'Chưa phát sinh giao dịch bán on-chain';
        document.getElementById('modalDonViTxHash').textContent = txHash;
        document.getElementById('modalDonViTxLink').href = dv.txHash ? `${EXPLORER_BASE_URL}${dv.txHash}` : '#';
        document.getElementById('linkBaoCaoTuDonViModal').href = `../trangDungChung/quanLyBaoCaoNghiVan.php?vaiTro=NHA_THUOC&maLo=${encodeURIComponent(dv.maLo)}`;

        modalDonVi.classList.remove('hidden');
    }

    document.querySelectorAll('.dong-modal-donvi').forEach(btn => btn.addEventListener('click', () => modalDonVi.classList.add('hidden')));

    document.getElementById('nutCopyDonViTx').addEventListener('click', function () {
        const txt = document.getElementById('modalDonViTxHash').textContent;
        if (txt && txt.startsWith('0x')) {
            navigator.clipboard.writeText(txt).then(() => {
                this.innerHTML = `<i class="fa-solid fa-check text-emerald-600"></i>`;
                setTimeout(() => { this.innerHTML = `<i class="fa-regular fa-copy"></i>`; }, 1500);
            });
        }
    });

    // ══ 10. CHUYỂN TAB DƯỚI & CUỘN NHANH ══
    const tabTonKhoLo = document.getElementById('tabTonKhoLo');
    const tabLichSuBan = document.getElementById('tabLichSuBan');
    const noiDungTabTonKho = document.getElementById('noiDungTabTonKho');
    const noiDungTabLichSuBan = document.getElementById('noiDungTabLichSuBan');

    tabTonKhoLo.addEventListener('click', () => {
        tabTonKhoLo.className = "flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all bg-cyan-700 text-white shadow-sm";
        tabLichSuBan.className = "flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all text-slate-600 hover:text-cyan-700 hover:bg-cyan-50";
        noiDungTabTonKho.classList.remove('hidden');
        noiDungTabLichSuBan.classList.add('hidden');
        renderTabTonKhoLo();
    });

    tabLichSuBan.addEventListener('click', () => {
        tabLichSuBan.className = "flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all bg-cyan-700 text-white shadow-sm";
        tabTonKhoLo.className = "flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all text-slate-600 hover:text-cyan-700 hover:bg-cyan-50";
        noiDungTabLichSuBan.classList.remove('hidden');
        noiDungTabTonKho.classList.add('hidden');
        renderTabLichSuBan();
    });

    document.getElementById('nutCuonXuongTonKho').addEventListener('click', () => {
        tabTonKhoLo.click();
        document.getElementById('khungNganDuoi').scrollIntoView({ behavior: 'smooth' });
    });

    document.getElementById('oTimKiemTonKho').addEventListener('input', renderTabTonKhoLo);
    document.getElementById('oTimKiemLichSuBan').addEventListener('input', renderTabLichSuBan);

    // Nạp giao diện ban đầu
    capNhatGioHang();
    renderTabTonKhoLo();
    renderTabLichSuBan();
});
document.addEventListener('DOMContentLoaded', function () {
    const EXPLORER_BASE_URL = "https://amoy.polygonscan.com/tx/";
    const ITEMS_PER_PAGE = 3;
    let trangHienTai = 1;

    // Yêu cầu 5: Hàm định dạng dd/mm/yyyy đồng nhất
    function dinhDangNgay(ngayYMD) {
        if (!ngayYMD) return '';
        const parts = ngayYMD.split('-');
        if (parts.length === 3) {
            return `${parts[2]}/${parts[1]}/${parts[0]}`;
        }
        return ngayYMD;
    }

    let danhSachLo = [];

    async function loThuocApi() {
        try {
            const response = await fetch('/api/quan-tri/lo-thuoc', {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error('Không thể tải dữ liệu lô thuốc');
            }

            const json = await response.json();
            danhSachLo = Array.isArray(json.data) ? json.data : [];
        } catch (error) {
            console.error(error);
            danhSachLo = [];
        }

        renderBangLoThuoc();
    }

    const thanBangLo = document.getElementById('thanBangLoThuoc');
    const oTimKiem = document.getElementById('oTimKiemLo');
    const locTrangThai = document.getElementById('locTrangThaiLo');
    const locHanDung = document.getElementById('locHanDung');
    const tongSoBadge = document.getElementById('tongSoLoHienThi');
    const thongTinPhanTrang = document.getElementById('thongTinPhanTrangLo');
    const cumNutPhanTrang = document.getElementById('cumNutPhanTrangLo');

    function layNgayHienTai() {
        const d = new Date();
        return d.toISOString().split('T')[0];
    }

    function tinhSoLuongKhaDung(item) {
        const daGiaoHoacDangPending = item.chuyenGiao.reduce((sum, cg) => sum + cg.soLuong, 0);
        const khaDung = item.soLuongTong - daGiaoHoacDangPending;
        return Math.max(0, khaDung);
    }

    function layThongTinTrangThai(item) {
        const homNay = layNgayHienTai();
        const daHetHan = item.hanDung < homNay;

        if (item.trangThai === 'RECALLED') {
            return {
                ma: 'RECALLED',
                nhan: 'Đã thu hồi',
                icon: 'fa-solid fa-ban',
                badgeClass: 'bg-red-100 text-red-700 border-red-300',
                choPhepPhanPhoi: false
            };
        }

        if (daHetHan) {
            return {
                ma: 'HET_HAN',
                nhan: 'Đã hết hạn',
                icon: 'fa-solid fa-triangle-exclamation',
                badgeClass: 'bg-amber-100 text-amber-800 border-amber-300',
                choPhepPhanPhoi: false
            };
        }

        if (item.trangThai === 'IN_TRANSIT') {
            return {
                ma: 'IN_TRANSIT',
                nhan: 'Đang phân phối',
                icon: 'fa-solid fa-truck-fast',
                badgeClass: 'bg-cyan-100 text-cyan-800 border-cyan-300',
                choPhepPhanPhoi: tinhSoLuongKhaDung(item) > 0
            };
        }

        return {
            ma: 'CREATED',
            nhan: 'Đã tạo',
            icon: 'fa-solid fa-circle-check',
            badgeClass: 'bg-emerald-100 text-emerald-800 border-emerald-300',
            choPhepPhanPhoi: tinhSoLuongKhaDung(item) > 0
        };
    }

    // ══ RENDER BẢNG LÔ THUỐC & PHÂN TRANG ══
    function renderBangLoThuoc() {
        const tuKhoa = oTimKiem.value.trim().toLowerCase();
        const trangThaiLoc = locTrangThai.value;
        const hanDungLoc = locHanDung.value;
        const homNay = layNgayHienTai();

        const danhSachLoc = danhSachLo.filter(item => {
            const matchTuKhoa = item.maLo.toLowerCase().includes(tuKhoa) || item.tenSanPham.toLowerCase().includes(tuKhoa);
            const ttHienTai = layThongTinTrangThai(item);

            let matchTrangThai = true;
            if (trangThaiLoc === 'HET_HAN') {
                matchTrangThai = (ttHienTai.ma === 'HET_HAN');
            } else if (trangThaiLoc !== 'TAT_CA') {
                matchTrangThai = (item.trangThai === trangThaiLoc) && (ttHienTai.ma !== 'HET_HAN');
            }

            let matchHanDung = true;
            if (hanDungLoc === 'DA_HET_HAN') matchHanDung = item.hanDung < homNay;
            else if (hanDungLoc === 'CON_HAN') matchHanDung = item.hanDung >= homNay;

            return matchTuKhoa && matchTrangThai && matchHanDung;
        });

        tongSoBadge.textContent = `Hiển thị: ${danhSachLoc.length} lô`;

        const tongSoTrang = Math.ceil(danhSachLoc.length / ITEMS_PER_PAGE) || 1;
        if (trangHienTai > tongSoTrang) trangHienTai = tongSoTrang;

        const batDau = (trangHienTai - 1) * ITEMS_PER_PAGE;
        const ketThuc = batDau + ITEMS_PER_PAGE;
        const duLieuTrang = danhSachLoc.slice(batDau, ketThuc);

        thanBangLo.innerHTML = '';

        if (duLieuTrang.length === 0) {
            thanBangLo.innerHTML = `<tr><td colspan="9" class="text-center py-8 text-slate-400 italic">Không tìm thấy lô thuốc phù hợp với điều kiện lọc.</td></tr>`;
            thongTinPhanTrang.textContent = "Không có bản ghi nào";
            cumNutPhanTrang.innerHTML = '';
            return;
        }

        thongTinPhanTrang.textContent = `Đang hiển thị ${batDau + 1} đến ${Math.min(ketThuc, danhSachLoc.length)} trên tổng số ${danhSachLoc.length} lô`;

        duLieuTrang.forEach(item => {
            const khaDung = tinhSoLuongKhaDung(item);
            const tt = layThongTinTrangThai(item);
            const coThePhanPhoi = tt.choPhepPhanPhoi && khaDung > 0;

            const hexRutGon = item.txHash ? `${item.txHash.substring(0, 6)}...${item.txHash.substring(item.txHash.length - 4)}` : 'N/A';
            const explorerLink = item.txHash ? `${EXPLORER_BASE_URL}${item.txHash}` : '#';

            const tr = document.createElement('tr');
            tr.className = 'dong-lo-thuoc transition-colors';
            tr.innerHTML = `
                <!-- 1. Mã Lô -->
                <td class="py-3 px-2.5 font-mono font-bold text-slate-900 whitespace-nowrap">${item.maLo}</td>

                <!-- 2. Mã QR (Yêu cầu 7: Nút Đơn vị + tooltip) -->
                <td class="py-3 px-2 text-center whitespace-nowrap">
                    <div class="inline-flex items-center justify-center gap-1">
                        <button type="button" class="btn-tai-qr-lo px-2 py-1 bg-white hover:bg-emerald-50 text-emerald-800 rounded border border-slate-200 text-[11px] font-bold shadow-2xs whitespace-nowrap" data-malo="${item.maLo}" title="Tải QR lô">
                            <i class="fa-solid fa-qrcode"></i> Lô
                        </button>
                        <button type="button" class="btn-xuat-qr-donvi px-2 py-1 bg-cyan-800 hover:bg-cyan-900 text-white rounded text-[11px] font-bold shadow-2xs whitespace-nowrap" data-malo="${item.maLo}" title="Tải QR đơn vị (ZIP)">
                            <i class="fa-solid fa-file-zipper"></i> Đơn vị
                        </button>
                    </div>
                </td>

                <!-- 3. Mã txHash -->
                <td class="py-3 px-2 whitespace-nowrap">
                    <div class="inline-flex items-center gap-1 p-1 rounded-md bg-nenChinh border border-emerald-900/10">
                        <a href="${explorerLink}" target="_blank" class="font-mono text-[10px] font-bold text-cyan-800 hover:underline flex items-center gap-1" title="Xem trên Polygonscan: ${item.txHash}">
                            ${hexRutGon} <i class="fa-solid fa-arrow-up-right-from-square text-[8px] text-slate-400"></i>
                        </a>
                        <button type="button" class="btn-copy-hex p-0.5 text-slate-400 hover:text-cyan-800 rounded transition-colors" data-hex="${item.txHash}" title="Sao chép toàn bộ txHash">
                            <i class="fa-regular fa-copy text-[10px]"></i>
                        </button>
                    </div>
                </td>

                <!-- 4. Tên sản phẩm -->
                <td class="py-3 px-2.5 font-semibold text-slate-900 leading-tight">${item.tenSanPham}</td>

                <!-- 5. Hạn dùng (Yêu cầu 5: dd/mm/yyyy) -->
                <td class="py-3 px-2 text-center text-slate-600 font-mono text-[11px] whitespace-nowrap">${dinhDangNgay(item.hanDung)}</td>

                <!-- 6. Tổng số -->
                <td class="py-3 px-2 text-right font-mono font-semibold whitespace-nowrap">${item.soLuongTong.toLocaleString()}</td>

                <!-- 7. Khả dụng -->
                <td class="py-3 px-2 text-right font-mono font-bold whitespace-nowrap ${khaDung > 0 ? 'text-emerald-700' : 'text-slate-400'}">${khaDung.toLocaleString()}</td>

                <!-- 8. Trạng thái -->
                <td class="py-3 px-2 text-center whitespace-nowrap">
                    <span class="inline-flex items-center justify-center gap-1 whitespace-nowrap px-2.5 py-1 rounded-full text-[10px] font-extrabold border ${tt.badgeClass}">
                        <i class="${tt.icon} text-xs"></i> <span>${tt.nhan}</span>
                    </span>
                </td>

                <!-- 9. Hành động -->
                <td class="py-3 px-2.5 text-center whitespace-nowrap">
                    <div class="inline-flex items-center justify-center gap-1.5">
                        <button type="button" class="btn-mo-phan-phoi px-2.5 py-1 rounded-lg text-[11px] font-bold transition-all flex items-center gap-1 whitespace-nowrap ${coThePhanPhoi ? 'bg-cyan-800 hover:bg-cyan-900 text-white shadow-2xs' : 'bg-slate-100 text-slate-400 cursor-not-allowed'}" data-malo="${item.maLo}" ${!coThePhanPhoi ? 'disabled' : ''}>
                            <i class="fa-solid fa-truck-fast text-[10px]"></i> Phân Phối
                        </button>
                        <button type="button" class="btn-xem-chi-tiet px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] transition-all flex items-center gap-1 whitespace-nowrap border border-slate-300 shadow-2xs" data-malo="${item.maLo}">
                            <i class="fa-solid fa-eye text-[10px]"></i> Chi Tiết
                        </button>
                    </div>
                </td>
            `;

            thanBangLo.appendChild(tr);
        });

        renderPhanTrangLo(tongSoTrang);
        ganSuKienNutBang();
    }

    // ══ CỤM NÚT PHÂN TRANG ══
    function renderPhanTrangLo(tongSoTrang) {
        cumNutPhanTrang.innerHTML = '';
        if (tongSoTrang <= 1) return;

        const btnPrev = document.createElement('button');
        btnPrev.className = `px-2.5 py-1 rounded-lg border text-xs font-bold transition-all ${trangHienTai === 1 ? 'border-slate-200 text-slate-300 cursor-not-allowed' : 'border-slate-300 text-slate-700 hover:bg-slate-100'}`;
        btnPrev.innerHTML = `<i class="fa-solid fa-angle-left"></i>`;
        btnPrev.disabled = (trangHienTai === 1);
        btnPrev.addEventListener('click', () => { if (trangHienTai > 1) { trangHienTai--; renderBangLoThuoc(); } });
        cumNutPhanTrang.appendChild(btnPrev);

        for (let i = 1; i <= tongSoTrang; i++) {
            const btnPage = document.createElement('button');
            btnPage.className = `w-7 h-7 rounded-lg text-xs font-bold transition-all ${trangHienTai === i ? 'bg-emerald-700 text-white shadow-2xs' : 'border border-slate-200 text-slate-700 hover:bg-slate-100'}`;
            btnPage.textContent = i;
            btnPage.addEventListener('click', () => { trangHienTai = i; renderBangLoThuoc(); });
            cumNutPhanTrang.appendChild(btnPage);
        }

        const btnNext = document.createElement('button');
        btnNext.className = `px-2.5 py-1 rounded-lg border text-xs font-bold transition-all ${trangHienTai === tongSoTrang ? 'border-slate-200 text-slate-300 cursor-not-allowed' : 'border-slate-300 text-slate-700 hover:bg-slate-100'}`;
        btnNext.innerHTML = `<i class="fa-solid fa-angle-right"></i>`;
        btnNext.disabled = (trangHienTai === tongSoTrang);
        btnNext.addEventListener('click', () => { if (trangHienTai < tongSoTrang) { trangHienTai++; renderBangLoThuoc(); } });
        cumNutPhanTrang.appendChild(btnNext);
    }

    function taiTapTinVanBan(tenFile, noiDung) {
        const blob = new Blob([noiDung], { type: 'text/plain;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = tenFile;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    // ══ GẮN SỰ KIỆN NÚT BẢNG ══
    function ganSuKienNutBang() {
        document.querySelectorAll('.btn-copy-hex').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                const hex = this.getAttribute('data-hex');
                if (!hex) return;

                navigator.clipboard.writeText(hex).then(() => {
                    const icon = this.querySelector('i');
                    icon.className = 'fa-solid fa-check text-emerald-600 text-[10px]';
                    setTimeout(() => { icon.className = 'fa-regular fa-copy text-[10px]'; }, 1800);
                });
            });
        });

        document.querySelectorAll('.btn-tai-qr-lo').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                const maLo = this.getAttribute('data-malo');
                taiTapTinVanBan(`QR-THUNG-${maLo}.txt`, `MÃ QR CẤP LÔ THUỐC (THÙNG LỚN): ${maLo}\nLoại mã: Batch Serialization\nThời gian xuất: ${new Date().toLocaleString('vi-VN')}\nBlockchain: Polygon Testnet`);
            });
        });

        document.querySelectorAll('.btn-xuat-qr-donvi').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                const maLo = this.getAttribute('data-malo');
                const item = danhSachLo.find(x => x.maLo === maLo);
                const soLuong = item ? item.soLuongTong : 5000;
                
                let noiDungCsv = "STT,MaDonVi,MaLoThuoc,TrangThai,QRCodeLink\n";
                for (let i = 1; i <= Math.min(soLuong, 20); i++) {
                    noiDungCsv += `${i},UNIT-${maLo}-${String(i).padStart(5, '0')},${maLo},AVAILABLE,https://pharmacare.vn/tra-cuu?unit=UNIT-${maLo}-${String(i).padStart(5, '0')}\n`;
                }
                noiDungCsv += `... và ${soLuong - 20} đơn vị lẻ tiếp theo được lưu trong hợp đồng thông minh.`;

                taiTapTinVanBan(`DANH-SACH-QR-DON-VI-${maLo}.csv`, noiDungCsv);
            });
        });

        document.querySelectorAll('.btn-mo-phan-phoi').forEach(btn => {
            btn.addEventListener('click', function () {
                const maLo = this.getAttribute('data-malo');
                loDangChonPhanPhoi = danhSachLo.find(x => x.maLo === maLo);

                if (loDangChonPhanPhoi) {
                    const khaDung = tinhSoLuongKhaDung(loDangChonPhanPhoi);
                    document.getElementById('phanPhoiMaLoText').textContent = loDangChonPhanPhoi.maLo;
                    document.getElementById('phanPhoiSoLuongConLaiText').textContent = `${khaDung.toLocaleString()} hộp`;
                    
                    const inputSL = document.getElementById('soLuongPhanPhoi');
                    inputSL.max = khaDung;
                    inputSL.value = '';
                    document.getElementById('loiSoLuongPhanPhoi').classList.add('hidden');
                    modalPhanPhoi.classList.remove('hidden');
                }
            });
        });

        document.querySelectorAll('.btn-xem-chi-tiet').forEach(btn => {
            btn.addEventListener('click', function () {
                const maLo = this.getAttribute('data-malo');
                hienThiChiTietLo(maLo);
            });
        });
    }

    // ══ MODAL ĐĂNG KÝ LÔ MỚI ══
    const modalDangKy = document.getElementById('modalDangKyLo');
    const manHinhForm = document.getElementById('manHinhFormDangKyLo');
    const manHinhKetQua = document.getElementById('manHinhKetQuaSinhQR');
    const formDangKy = document.getElementById('formSubmitDangKyLo');
    let loVuaTaoMoi = null;

    document.getElementById('nutMoModalDangKyLo').addEventListener('click', () => {
        manHinhForm.classList.remove('hidden');
        manHinhKetQua.classList.add('hidden');
        formDangKy.reset();
        modalDangKy.classList.remove('hidden');
    });

    document.querySelectorAll('.dong-modal-lo').forEach(btn => {
        btn.addEventListener('click', () => modalDangKy.classList.add('hidden'));
    });

    formDangKy.addEventListener('submit', function (e) {
        e.preventDefault();

        const selectSP = document.getElementById('chonSanPhamDaDuyet');
        const tenSP = selectSP.options[selectSP.selectedIndex].getAttribute('data-ten');
        const maLoMoi = document.getElementById('nhapMaLo').value.trim().toUpperCase();
        const soLuong = parseInt(document.getElementById('nhapSoLuong').value);
        const ngaySX = document.getElementById('nhapNgaySX').value;
        const hanDung = document.getElementById('nhapHanDung').value;

        loVuaTaoMoi = {
            maLo: maLoMoi,
            tenSanPham: tenSP,
            ngaySX: ngaySX,
            hanDung: hanDung,
            soLuongTong: soLuong,
            trangThai: 'CREATED',
            txHash: '0x' + Array.from({length: 64}, () => Math.floor(Math.random()*16).toString(16)).join(''),
            chuyenGiao: []
        };

        danhSachLo.unshift(loVuaTaoMoi);
        renderBangLoThuoc();

        document.getElementById('hienThiTxHashTaoLo').textContent = `txHash: ${loVuaTaoMoi.txHash}`;
        document.getElementById('hienThiMaLoQR').textContent = maLoMoi;
        document.getElementById('hienThiSoLuongDonVi').textContent = soLuong.toLocaleString();

        manHinhForm.classList.add('hidden');
        manHinhKetQua.classList.remove('hidden');
    });

    document.getElementById('nutTaiQRLoModal').addEventListener('click', () => {
        if (!loVuaTaoMoi) return;
        taiTapTinVanBan(`QR-THUNG-${loVuaTaoMoi.maLo}.txt`, `MÃ QR CẤP LÔ THÙNG: ${loVuaTaoMoi.maLo}\nTxHash: ${loVuaTaoMoi.txHash}`);
    });

    document.getElementById('nutXuatQRDonViModal').addEventListener('click', () => {
        if (!loVuaTaoMoi) return;
        taiTapTinVanBan(`BO-TEM-DON-VI-${loVuaTaoMoi.maLo}.csv`, `STT,UnitID,BatchID\n1,UNIT-${loVuaTaoMoi.maLo}-00001,${loVuaTaoMoi.maLo}\n(Tổng cộng ${loVuaTaoMoi.soLuongTong.toLocaleString()} đơn vị serial)`);
    });

    // ══ MODAL PHÂN PHỐI LÔ ══
    const modalPhanPhoi = document.getElementById('modalPhanPhoiLo');
    const formPhanPhoi = document.getElementById('formSubmitPhanPhoi');
    let loDangChonPhanPhoi = null;

    document.getElementById('nutDongModalPhanPhoi').addEventListener('click', () => modalPhanPhoi.classList.add('hidden'));
    document.getElementById('nutHuyPhanPhoi').addEventListener('click', () => modalPhanPhoi.classList.add('hidden'));

    formPhanPhoi.addEventListener('submit', function (e) {
        e.preventDefault();
        const soLuongXuat = parseInt(document.getElementById('soLuongPhanPhoi').value);
        const nppSelect = document.getElementById('chonNhaPhanPhoi');
        const tenNPP = nppSelect.options[nppSelect.selectedIndex].text;
        const khaDung = tinhSoLuongKhaDung(loDangChonPhanPhoi);

        if (soLuongXuat > khaDung) {
            document.getElementById('loiSoLuongPhanPhoi').classList.remove('hidden');
            return;
        }

        loDangChonPhanPhoi.trangThai = 'IN_TRANSIT';

        const now = new Date();
        const dd = String(now.getDate()).padStart(2, '0');
        const mm = String(now.getMonth() + 1).padStart(2, '0');
        const yyyy = now.getFullYear();
        const hh = String(now.getHours()).padStart(2, '0');
        const min = String(now.getMinutes()).padStart(2, '0');
        const timeNow = `${dd}/${mm}/${yyyy} ${hh}:${min}`;

        // Yêu cầu 6: Khởi tạo với thoiGianXacNhan là null
        loDangChonPhanPhoi.chuyenGiao.push({
            benNhan: tenNPP,
            soLuong: soLuongXuat,
            thoiGianKhoiTao: timeNow,
            thoiGianXacNhan: null,
            trangThai: 'PENDING'
        });

        alert(`Đã gửi yêu cầu chuyển giao ${soLuongXuat.toLocaleString()} hộp đến ${tenNPP}! Trạng thái: PENDING.`);
        modalPhanPhoi.classList.add('hidden');
        renderBangLoThuoc();
    });

    // ══ MODAL CHI TIẾT LÔ ══
    const modalChiTiet = document.getElementById('modalChiTietLo');
    let loDangXemChiTiet = null;

    function hienThiChiTietLo(maLo) {
        loDangXemChiTiet = danhSachLo.find(x => x.maLo === maLo);
        if (!loDangXemChiTiet) return;

        const khaDung = tinhSoLuongKhaDung(loDangXemChiTiet);

        document.getElementById('modalMaLoTitle').textContent = loDangXemChiTiet.maLo;
        document.getElementById('modalChiTietSanPham').textContent = loDangXemChiTiet.tenSanPham;
        
        // Yêu cầu 5: Ngày SX & Hạn dùng đồng nhất dd/mm/yyyy
        document.getElementById('modalChiTietNgaySX').textContent = dinhDangNgay(loDangXemChiTiet.ngaySX);
        document.getElementById('modalChiTietHanDung').textContent = dinhDangNgay(loDangXemChiTiet.hanDung);
        
        document.getElementById('modalChiTietSoLuong').textContent = `${loDangXemChiTiet.soLuongTong.toLocaleString()} / ${khaDung.toLocaleString()}`;
        
        document.getElementById('modalChiTietTxHash').textContent = loDangXemChiTiet.txHash;
        document.getElementById('modalChiTietTxLink').href = `${EXPLORER_BASE_URL}${loDangXemChiTiet.txHash}`;

        // Yêu cầu 6: Timeline hiển thị cả thoiGianKhoiTao và thoiGianXacNhan
        const timelineBox = document.getElementById('danhSachTimelineChuyenGiao');
        timelineBox.innerHTML = '';

        if (loDangXemChiTiet.chuyenGiao.length === 0) {
            timelineBox.innerHTML = `<p class="text-slate-400 italic">Chưa phát sinh giao dịch chuyển giao nào cho lô này.</p>`;
        } else {
            loDangXemChiTiet.chuyenGiao.forEach(cg => {
                const div = document.createElement('div');
                div.className = 'relative space-y-1';
                const xacNhanText = cg.thoiGianXacNhan ? cg.thoiGianXacNhan : 'Chưa xác nhận';
                const badgeClass = cg.trangThai === 'RECEIVED' ? 'text-emerald-700' : 'text-amber-600';
                
                div.innerHTML = `
                    <span class="absolute -left-[31px] top-1 w-3.5 h-3.5 rounded-full ${cg.trangThai === 'RECEIVED' ? 'bg-emerald-700' : 'bg-amber-500'} ring-4 ring-white"></span>
                    <div class="font-bold text-slate-800">Chuyển giao cho: ${cg.benNhan}</div>
                    <div class="text-slate-600 font-mono text-[11px]">Số lượng: <strong>${cg.soLuong.toLocaleString()}</strong> hộp – Trạng thái: <span class="font-bold ${badgeClass}">${cg.trangThai}</span></div>
                    <div class="text-[10px] text-slate-500 font-mono flex flex-wrap gap-x-4 gap-y-0.5">
                        <span><i class="fa-regular fa-clock mr-1"></i> Khởi tạo: ${cg.thoiGianKhoiTao}</span>
                        <span><i class="fa-solid fa-check-double mr-1"></i> Tiếp nhận: ${xacNhanText}</span>
                    </div>
                `;
                timelineBox.appendChild(div);
            });
        }

        modalChiTiet.classList.remove('hidden');
    }

    document.getElementById('nutSaoChepModalTx').addEventListener('click', function () {
        if (!loDangXemChiTiet) return;
        navigator.clipboard.writeText(loDangXemChiTiet.txHash).then(() => {
            this.innerHTML = `<i class="fa-solid fa-check text-emerald-600"></i> Đã sao chép`;
            setTimeout(() => { this.innerHTML = `<i class="fa-regular fa-copy"></i> Sao chép`; }, 1800);
        });
    });

    document.getElementById('nutXuatQRThungModal').addEventListener('click', () => {
        if (!loDangXemChiTiet) return;
        taiTapTinVanBan(`QR-THUNG-${loDangXemChiTiet.maLo}.txt`, `MÃ QR LÔ THÙNG: ${loDangXemChiTiet.maLo}\ntxHash: ${loDangXemChiTiet.txHash}`);
    });

    document.getElementById('nutXuatQRDonViZipModal').addEventListener('click', () => {
        if (!loDangXemChiTiet) return;
        taiTapTinVanBan(`BO-MA-QR-DON-VI-${loDangXemChiTiet.maLo}.zip`, `[Tệp nén chứa ${loDangXemChiTiet.soLuongTong.toLocaleString()} ảnh QR đơn vị định dạng PNG]`);
    });

    document.getElementById('nutXuatQRDonViCsvModal').addEventListener('click', () => {
        if (!loDangXemChiTiet) return;
        taiTapTinVanBan(`SERIAL-DON-VI-${loDangXemChiTiet.maLo}.csv`, `STT,SerialID,BatchID,Status\n1,UNIT-${loDangXemChiTiet.maLo}-00001,${loDangXemChiTiet.maLo},AVAILABLE\n(Danh sách ${loDangXemChiTiet.soLuongTong.toLocaleString()} đơn vị)`);
    });

    document.getElementById('nutDongModalChiTietLo').addEventListener('click', () => modalChiTiet.classList.add('hidden'));
    document.getElementById('nutDongModalChiTietLoBottom').addEventListener('click', () => modalChiTiet.classList.add('hidden'));

    // Lắng nghe tìm kiếm & lọc
    oTimKiem.addEventListener('input', () => { trangHienTai = 1; renderBangLoThuoc(); });
    locTrangThai.addEventListener('change', () => { trangHienTai = 1; renderBangLoThuoc(); });
    locHanDung.addEventListener('change', () => { trangHienTai = 1; renderBangLoThuoc(); });

    loThuocApi();
});
document.addEventListener('DOMContentLoaded', function () {
    const STORAGE_KEY = 'pharma_lich_su_bao_cao';
    const MAX_FILES = 5;
    const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB

    const tabGui = document.getElementById('tabGuiBaoCao');
    const tabLichSu = document.getElementById('tabLichSuBaoCao');
    const khungGui = document.getElementById('khungTabGuiBaoCao');
    const khungLichSu = document.getElementById('khungTabLichSuBaoCao');

    const thongBaoBox = document.getElementById('thongBaoPublic');
    const iconThongBao = document.getElementById('iconThongBaoPublic');
    const noiDungThongBao = document.getElementById('noiDungThongBaoPublic');

    const formGui = document.getElementById('formGuiBaoCao');
    const oNhapMaLoPublic = document.getElementById('maLoBaoCao');
    const trangThaiNhanDienLo = document.getElementById('trangThaiNhanDienLo');
    const submitBtn = document.getElementById('nutGuiBaoCaoSubmit');
    const inputAnhQR = document.getElementById('inputAnhQR');
    const inputTep = document.getElementById('tepMinhChung');
    const khungKeoTha = document.getElementById('khungKeoThaMinhChung');
    const soLuongTepLabel = document.getElementById('soLuongTepDaChonPublic');
    const danhSachTepWrap = document.getElementById('danhSachTepDaChonPublic');

    const modalThanhCong = document.getElementById('modalThanhCongBaoCao');
    const maBaoCaoSinhRa = document.getElementById('maBaoCaoSinhRa');
    const btnCopy = document.getElementById('nutSaoChepMa');
    const btnTaiTxt = document.getElementById('nutTaiPhieuMa');

    const inputTraCuu = document.getElementById('oTraCuuThuCong');
    const btnTraCuu = document.getElementById('nutTraCuuThuCong');
    const inputUploadTxt = document.getElementById('inputUploadFileTxt');
    const inputNhapLichSuTxt = document.getElementById('inputNhapLichSuTxt');

    const thanBang = document.getElementById('thanBangBaoCao');
    const badgeCount = document.getElementById('soLuongBaoCaoBadge');
    const khungRong = document.getElementById('khungBaoCaoRong');
    const khungBang = document.getElementById('khungBangBaoCao');
    const modalChiTiet = document.getElementById('modalChiTietBaoCao');
    const nutXoaLichSu = document.getElementById('nutXoaLichSuPublic');
    const nutXuatLichSu = document.getElementById('nutXuatLichSuPublic');

    let duLieuLuuTru = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
    let danhSachTepTam = [];
    let baoCaoVuaGui = null;
    let timerKiemTraMaLo = null;
    let lyDoChanGuiHienTai = '';

    function escapeHtml(str) {
        if (str == null) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function capNhatTrangThaiNutGui(biChan, lyDoChan = '') {
        lyDoChanGuiHienTai = biChan ? lyDoChan : '';
        if (!submitBtn) return;

        if (biChan) {
            submitBtn.disabled = true;
            submitBtn.classList.remove('bg-emerald-700', 'hover:bg-emerald-800', 'cursor-pointer');
            submitBtn.classList.add('bg-slate-300', 'text-slate-500', 'cursor-not-allowed');
        } else {
            submitBtn.disabled = false;
            submitBtn.classList.remove('bg-slate-300', 'text-slate-500', 'cursor-not-allowed');
            submitBtn.classList.add('bg-emerald-700', 'hover:bg-emerald-800', 'text-white', 'cursor-pointer');
        }
    }

    function hienThongBao(noiDung, loai = 'error') {
        if (!thongBaoBox || !noiDungThongBao) return;
        thongBaoBox.classList.remove('hidden', 'bg-red-50', 'border-red-200', 'text-red-800', 'bg-emerald-50', 'border-emerald-200', 'text-emerald-800');

        if (loai === 'success') {
            thongBaoBox.classList.add('bg-emerald-50', 'border-emerald-200', 'text-emerald-800');
            if (iconThongBao) iconThongBao.className = 'fa-solid fa-circle-check';
        } else {
            thongBaoBox.classList.add('bg-red-50', 'border-red-200', 'text-red-800');
            if (iconThongBao) iconThongBao.className = 'fa-solid fa-triangle-exclamation';
        }

        noiDungThongBao.textContent = noiDung;
    }

    function anThongBao() {
        thongBaoBox?.classList.add('hidden');
    }

    document.getElementById('dongThongBaoPublic')?.addEventListener('click', anThongBao);

    function batTabGui() {
        anThongBao();
        tabGui.className = 'flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all bg-emerald-700 text-white shadow-xs cursor-pointer';
        tabLichSu.className = 'flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all text-slate-600 hover:text-emerald-700 hover:bg-emerald-50 cursor-pointer';
        khungGui.classList.remove('hidden');
        khungLichSu.classList.add('hidden');
    }

    function batTabLichSu() {
        anThongBao();
        tabLichSu.className = 'flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all bg-emerald-700 text-white shadow-xs cursor-pointer';
        tabGui.className = 'flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all text-slate-600 hover:text-emerald-700 hover:bg-emerald-50 cursor-pointer';
        khungLichSu.classList.remove('hidden');
        khungGui.classList.add('hidden');
        renderBangLichSu();
    }

    if (tabGui) tabGui.addEventListener('click', batTabGui);
    if (tabLichSu) tabLichSu.addEventListener('click', batTabLichSu);

    // ══ ĐỐI CHIẾU NHANH MÃ LÔ, KIỂM TRA THU HỒI & CHẶN GỬI TRÙNG ══
    function trichXuatDinhDanhTuQR(rawText) {
        const text = (rawText || '').trim();
        if (!text) return { value: '', isToken: false };

        if (text.startsWith('{') && text.endsWith('}')) {
            try {
                const obj = JSON.parse(text);
                if (obj.maDinhDanh) return { value: String(obj.maDinhDanh).trim(), isToken: true };
                const candidate = obj.maLoNghiepVu || obj.maLo || obj.batchCode || obj.code;
                if (candidate) return { value: String(candidate).trim().toUpperCase(), isToken: false };
            } catch (e) { /* Bỏ qua */ }
        }

        try {
            const urlObj = new URL(text);
            const maDinhDanh = urlObj.searchParams.get('maDinhDanh');
            if (maDinhDanh) {
                return { value: maDinhDanh.trim(), isToken: true };
            }

            const paramMa = urlObj.searchParams.get('maLoNghiepVu')
                || urlObj.searchParams.get('maLo')
                || urlObj.searchParams.get('ma')
                || urlObj.searchParams.get('code');
            if (paramMa) {
                return { value: paramMa.trim().toUpperCase(), isToken: false };
            }

            const segments = urlObj.pathname.split('/').filter(Boolean);
            const idxTraCuu = segments.findIndex(s => s === 'tra-cuu-nguon-goc' || s === 'tra-cuu');
            if (idxTraCuu !== -1 && segments[idxTraCuu + 1]) {
                return { value: decodeURIComponent(segments[idxTraCuu + 1]).trim(), isToken: true };
            }
        } catch (e) { /* Không phải URL đầy đủ */ }

        const tokenMatch = text.match(/maDinhDanh=([A-Za-z0-9\-_]+)/i);
        if (tokenMatch && tokenMatch[1]) {
            return { value: tokenMatch[1].trim(), isToken: true };
        }

        const maLoMatch = text.match(/(?:maLoNghiepVu|maLo|ma)=([A-Za-z0-9\-_]+)/i);
        if (maLoMatch && maLoMatch[1]) {
            return { value: maLoMatch[1].trim().toUpperCase(), isToken: false };
        }

        return { value: text, isToken: false };
    }

    function hienTrangThaiLo(html, loai = 'info') {
        if (!trangThaiNhanDienLo) return;
        trangThaiNhanDienLo.classList.remove(
            'hidden',
            'bg-emerald-50', 'border-emerald-200', 'text-emerald-800',
            'bg-amber-50', 'border-amber-200', 'text-amber-800',
            'bg-red-50', 'border-red-200', 'text-red-800',
            'bg-cyan-50', 'border-cyan-200', 'text-cyan-800'
        );

        if (loai === 'success') {
            trangThaiNhanDienLo.classList.add('bg-emerald-50', 'border-emerald-200', 'text-emerald-800');
        } else if (loai === 'warning') {
            trangThaiNhanDienLo.classList.add('bg-amber-50', 'border-amber-200', 'text-amber-800');
        } else if (loai === 'error') {
            trangThaiNhanDienLo.classList.add('bg-red-50', 'border-red-200', 'text-red-800');
        } else {
            trangThaiNhanDienLo.classList.add('bg-cyan-50', 'border-cyan-200', 'text-cyan-800');
        }
        trangThaiNhanDienLo.innerHTML = html;
    }

    /**
     * Đề xuất 2 (Lớp trình duyệt): Kiểm tra mã lô có phiếu đang chờ xử lý trên trình duyệt không,
     * đồng thời đối soát lại trạng thái mới nhất từ server để tránh khóa oan nếu phiếu cũ đã được thẩm định xong.
     */
    async function kiemTraBaoCaoTrungTrenTrinhDuyet(maLoCanKiemTra) {
        const code = (maLoCanKiemTra || '').trim().toUpperCase();
        if (!code) return null;

        const banGhiCu = duLieuLuuTru.find(item =>
            (item.maLo || '').trim().toUpperCase() === code
            && (item.trangThai === 'DA_GUI' || item.trangThai === 'DANG_XU_LY')
        );

        if (!banGhiCu) return null;

        try {
            const res = await fetch(`/api/bao-cao-nghi-van/tra-cuu/${encodeURIComponent(banGhiCu.maBaoCao)}`, {
                headers: { 'Accept': 'application/json' }
            });
            const json = await res.json();
            if (res.ok && json.success && json.data) {
                luuVaoLichSuKhongTrungLap({
                    maBaoCao: json.data.maBaoCao,
                    maLo: json.data.maLo,
                    ngayGui: json.data.ngayGui,
                    trangThai: json.data.trangThai
                });

                if (json.data.trangThai === 'DA_GUI' || json.data.trangThai === 'DANG_XU_LY') {
                    return json.data;
                }
                return null;
            }
        } catch (e) { /* Nếu mất kết nối tạm thời thì dùng trạng thái lưu cục bộ */ }

        return banGhiCu;
    }

    function hienCanhBaoTrungBaoCao(baoCaoTrung, maLoHienThi) {
        const lyDo = `Bạn đã gửi báo cáo [${baoCaoTrung.maBaoCao}] cho lô [${maLoHienThi}] và hồ sơ đang chờ Cơ quan quản lý thẩm định.`;
        capNhatTrangThaiNutGui(true, lyDo);
        hienTrangThaiLo(
            `<i class="fa-solid fa-circle-info mr-1"></i> Bạn đã gửi báo cáo (<strong>${escapeHtml(baoCaoTrung.maBaoCao)}</strong>) cho lô <strong>${escapeHtml(maLoHienThi)}</strong> và hồ sơ đang chờ thẩm định. <button type="button" data-ma-bc="${escapeHtml(baoCaoTrung.maBaoCao)}" class="btn-xem-phieu-trung underline font-bold cursor-pointer ml-1">Xem tiến trình &rarr;</button>`,
            'info'
        );

        trangThaiNhanDienLo?.querySelector('.btn-xem-phieu-trung')?.addEventListener('click', function () {
            const maBc = this.getAttribute('data-ma-bc');
            if (maBc) thucHienTraCuu(maBc);
        });
    }

    async function kiemTraNhanhMaLo(rawInput) {
        if (!trangThaiNhanDienLo) return;
        const parsed = trichXuatDinhDanhTuQR(rawInput);
        const queryCode = parsed.value;

        if (!queryCode) {
            trangThaiNhanDienLo.classList.add('hidden');
            capNhatTrangThaiNutGui(false);
            return;
        }

        if (oNhapMaLoPublic && oNhapMaLoPublic.value.trim() !== queryCode) {
            oNhapMaLoPublic.value = queryCode;
        }

        capNhatTrangThaiNutGui(false);

        try {
            const res = await fetch(`/api/bao-cao-nghi-van/kiem-tra-lo?code=${encodeURIComponent(queryCode)}`, {
                headers: { 'Accept': 'application/json' }
            });
            const json = await res.json();

            if (res.ok && json.success) {
                const maLoChuan = String(json.data?.maLoNghiepVu || '').toUpperCase();
                const tenThuoc = json.data?.tenSanPham || 'Dược phẩm đã đăng ký trên hệ thống';
                const daThuHoi = json.data?.trangThai === 'RECALLED' || json.data?.daThuHoi === true;

                if (maLoChuan && oNhapMaLoPublic) {
                    oNhapMaLoPublic.value = maLoChuan;
                }

                // ĐỀ XUẤT 1: Lô đã bị thu hồi -> Khóa gửi báo cáo ngay
                if (daThuHoi) {
                    const msgChan = `Lô thuốc [${maLoChuan}] đã bị Cơ quan quản lý ban hành lệnh THU HỒI. Không thể gửi thêm báo cáo cho lô này.`;
                    capNhatTrangThaiNutGui(true, msgChan);
                    hienTrangThaiLo(
                        `<i class="fa-solid fa-ban mr-1"></i> Lô thuốc <strong>${escapeHtml(maLoChuan)}</strong> (${escapeHtml(tenThuoc)}) <strong>ĐÃ BỊ THU HỒI</strong> bởi Cơ quan quản lý. Không thể gửi thêm báo cáo — <a href="/tra-cuu-nguon-goc?maDinhDanh=${encodeURIComponent(maLoChuan)}" class="underline font-bold">Xem chi tiết thu hồi</a>.`,
                        'error'
                    );
                    return;
                }

                // ĐỀ XUẤT 2: Kiểm tra trùng báo cáo đang chờ xử lý
                const baoCaoTrung = await kiemTraBaoCaoTrungTrenTrinhDuyet(maLoChuan);
                if (baoCaoTrung) {
                    hienCanhBaoTrungBaoCao(baoCaoTrung, maLoChuan);
                    return;
                }

                hienTrangThaiLo(
                    `<i class="fa-solid fa-circle-check mr-1"></i> Lô hợp lệ trong hệ thống${maLoChuan ? ` (<strong>${escapeHtml(maLoChuan)}</strong>)` : ''}: <strong>${escapeHtml(tenThuoc)}</strong>`,
                    'success'
                );
            } else {
                const maNgoaiHeThong = (!parsed.isToken && !/^https?:\/\//i.test(queryCode))
                    ? queryCode.toUpperCase()
                    : queryCode;

                if (!parsed.isToken && oNhapMaLoPublic && !/^https?:\/\//i.test(queryCode)) {
                    oNhapMaLoPublic.value = maNgoaiHeThong;
                }

                // ĐỀ XUẤT 2: Chặn trùng cả với mã lô ngoài hệ thống đang chờ xử lý
                const baoCaoTrung = await kiemTraBaoCaoTrungTrenTrinhDuyet(maNgoaiHeThong);
                if (baoCaoTrung) {
                    hienCanhBaoTrungBaoCao(baoCaoTrung, maNgoaiHeThong);
                    return;
                }

                hienTrangThaiLo(
                    `<i class="fa-solid fa-triangle-exclamation mr-1"></i> Mã lô chưa có trên hệ thống — sẽ được ghi nhận theo diện <strong>Lô ngoài hệ thống / Nghi giả</strong>.`,
                    'warning'
                );
            }
        } catch (err) {
            trangThaiNhanDienLo.classList.add('hidden');
        }
    }

    oNhapMaLoPublic?.addEventListener('input', function () {
        clearTimeout(timerKiemTraMaLo);
        timerKiemTraMaLo = setTimeout(() => kiemTraNhanhMaLo(this.value), 400);
    });

    // Tự động điền nếu chuyển từ trang tra cứu (?maLo=... hoặc ?maDinhDanh=...)
    const urlParams = new URLSearchParams(window.location.search);
    const paramKhoiTao = urlParams.get('maLo') || urlParams.get('maDinhDanh') || urlParams.get('ma');
    if (paramKhoiTao && oNhapMaLoPublic) {
        oNhapMaLoPublic.value = paramKhoiTao.trim();
        kiemTraNhanhMaLo(oNhapMaLoPublic.value);
    }

    // ══ ĐỌC MÃ QR TỪ TỆP ẢNH / SVG TẢI LÊN ══
    async function giaiMaFileSvg(file) {
        const svgText = await file.text();
        const targetMatch = svgText.match(/data-qr-target=["']([^"']+)["']/i)
            || svgText.match(/https?:\/\/[^\s"'<>]+maDinhDanh=[A-Za-z0-9\-_]+/i);
        if (targetMatch) {
            return targetMatch[1] || targetMatch[0];
        }

        return await new Promise((resolve, reject) => {
            const img = new Image();
            const svgBlob = new Blob([svgText], { type: 'image/svg+xml;charset=utf-8' });
            const url = URL.createObjectURL(svgBlob);
            img.onload = () => {
                try {
                    const canvas = document.createElement('canvas');
                    canvas.width = img.width || 400;
                    canvas.height = img.height || 400;
                    const ctx = canvas.getContext('2d');
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, canvas.width, canvas.height);
                    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                    URL.revokeObjectURL(url);

                    canvas.toBlob(async (pngBlob) => {
                        if (!pngBlob) return reject(new Error('Không chuyển được SVG'));
                        const pngFile = new File([pngBlob], 'qr.png', { type: 'image/png' });
                        const scanner = new Html5Qrcode('qrFileHiddenReader');
                        const text = await scanner.scanFile(pngFile, true);
                        await scanner.clear();
                        resolve(text);
                    }, 'image/png');
                } catch (e) {
                    reject(e);
                }
            };
            img.onerror = () => {
                URL.revokeObjectURL(url);
                reject(new Error('Lỗi đọc tệp SVG'));
            };
            img.src = url;
        });
    }

    inputAnhQR?.addEventListener('change', async function (e) {
        const file = e.target.files?.[0];
        if (!file) return;

        hienTrangThaiLo(`<i class="fa-solid fa-spinner fa-spin mr-1"></i> Đang giải mã QR từ tệp ảnh...`, 'info');

        try {
            if (typeof Html5Qrcode === 'undefined') {
                throw new Error('Chưa tải được thư viện giải mã QR.');
            }

            let decodedText = '';
            const isSvg = file.type === 'image/svg+xml' || file.name.toLowerCase().endsWith('.svg');

            if (isSvg) {
                decodedText = await giaiMaFileSvg(file);
            } else {
                const fileScanner = new Html5Qrcode('qrFileHiddenReader');
                decodedText = await fileScanner.scanFile(file, true);
                await fileScanner.clear();
            }

            const parsed = trichXuatDinhDanhTuQR(decodedText);
            if (!parsed.value) {
                throw new Error('Nội dung QR trống.');
            }

            if (oNhapMaLoPublic) {
                oNhapMaLoPublic.value = parsed.value;
                await kiemTraNhanhMaLo(parsed.value);
            }
        } catch (err) {
            capNhatTrangThaiNutGui(false);
            hienTrangThaiLo(
                `<i class="fa-solid fa-circle-xmark mr-1"></i> Không nhận diện được mã QR trong tệp này. Vui lòng chọn ảnh rõ nét hơn.`,
                'error'
            );
        } finally {
            inputAnhQR.value = '';
        }
    });

    // ══ QUẢN LÝ TỆP MINH CHỨNG (TỐI ĐA 5 TỆP) ══
    function dongBoInputFiles() {
        if (!inputTep) return;
        const dt = new DataTransfer();
        danhSachTepTam.forEach(file => dt.items.add(file));
        inputTep.files = dt.files;

        if (soLuongTepLabel) {
            soLuongTepLabel.textContent = `${danhSachTepTam.length}/${MAX_FILES} tệp`;
        }

        if (!danhSachTepWrap) return;
        if (danhSachTepTam.length === 0) {
            danhSachTepWrap.innerHTML = '';
            return;
        }

        danhSachTepWrap.innerHTML = danhSachTepTam.map((file, idx) => `
            <div class="flex items-center justify-between px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-[11px]">
                <div class="flex items-center gap-2 truncate">
                    <i class="fa-solid fa-file-circle-check text-emerald-700"></i>
                    <span class="font-medium text-slate-800 truncate">${escapeHtml(file.name)}</span>
                    <span class="text-slate-400 font-mono shrink-0">(${(file.size / 1024 / 1024).toFixed(2)} MB)</span>
                </div>
                <button type="button" data-index="${idx}" class="btn-xoa-tep-public text-red-500 hover:text-red-700 px-1.5 cursor-pointer" title="Gỡ tệp">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        `).join('');

        danhSachTepWrap.querySelectorAll('.btn-xoa-tep-public').forEach(btn => {
            btn.addEventListener('click', function () {
                const index = parseInt(this.getAttribute('data-index'), 10);
                danhSachTepTam.splice(index, 1);
                dongBoInputFiles();
            });
        });
    }

    if (khungKeoTha) khungKeoTha.addEventListener('click', () => inputTep?.click());
    if (inputTep) {
        inputTep.addEventListener('change', function () {
            anThongBao();
            const filesMoi = Array.from(this.files || []);
            const allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];

            for (const file of filesMoi) {
                if (danhSachTepTam.length >= MAX_FILES) {
                    hienThongBao(`Chỉ được đính kèm tối đa ${MAX_FILES} tệp minh chứng.`);
                    break;
                }
                if (!allowedTypes.includes(file.type)) {
                    hienThongBao(`Tệp "${file.name}" không đúng định dạng (chỉ nhận JPG, PNG, PDF).`);
                    continue;
                }
                if (file.size > MAX_FILE_SIZE) {
                    hienThongBao(`Tệp "${file.name}" vượt quá dung lượng tối đa 10MB.`);
                    continue;
                }
                const daTonTai = danhSachTepTam.some(f => f.name === file.name && f.size === file.size);
                if (!daTonTai) {
                    danhSachTepTam.push(file);
                }
            }

            dongBoInputFiles();
        });
    }

    // ══ GỬI BÁO CÁO CÔNG KHAI ══
    if (formGui) {
        formGui.addEventListener('submit', function (e) {
            e.preventDefault();
            anThongBao();

            if (lyDoChanGuiHienTai) {
                hienThongBao(lyDoChanGuiHienTai);
                return;
            }

            const formData = new FormData(formGui);
            submitBtn.disabled = true;
            submitBtn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Đang gửi báo cáo...`;

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            fetch('/api/bao-cao-nghi-van/gui-bao-cao', {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
            .then(async res => {
                const json = await res.json();
                if (!res.ok || !json.success) {
                    const err = new Error(
                        (json.errors ? Object.values(json.errors)[0]?.[0] : null)
                        || json.message
                        || 'Không thể gửi báo cáo!'
                    );
                    err.payload = json;
                    throw err;
                }
                return json;
            })
            .then(res => {
                submitBtn.innerHTML = `<i class="fa-solid fa-paper-plane"></i> Gửi Báo Cáo Nghi Vấn`;
                capNhatTrangThaiNutGui(false);

                const data = res.data;
                baoCaoVuaGui = data;
                maBaoCaoSinhRa.textContent = data.maBaoCao;

                luuVaoLichSuKhongTrungLap({
                    maBaoCao: data.maBaoCao,
                    maLo: data.maLo,
                    ngayGui: data.ngayGui,
                    trangThai: 'DA_GUI'
                });

                modalThanhCong.classList.remove('hidden');
                formGui.reset();
                trangThaiNhanDienLo?.classList.add('hidden');
                danhSachTepTam = [];
                dongBoInputFiles();
            })
            .catch(err => {
                submitBtn.innerHTML = `<i class="fa-solid fa-paper-plane"></i> Gửi Báo Cáo Nghi Vấn`;
                const payload = err.payload || {};

                if (payload.code === 'BATCH_RECALLED') {
                    capNhatTrangThaiNutGui(true, err.message);
                    hienTrangThaiLo(`<i class="fa-solid fa-ban mr-1"></i> ${escapeHtml(err.message)}`, 'error');
                } else if (payload.code === 'DUPLICATE_REPORT' && payload.existingMaBaoCao) {
                    luuVaoLichSuKhongTrungLap({
                        maBaoCao: payload.existingMaBaoCao,
                        maLo: payload.existingMaLo || (oNhapMaLoPublic?.value || '').trim().toUpperCase(),
                        ngayGui: payload.existingNgayGui || '--',
                        trangThai: payload.existingTrangThai || 'DA_GUI'
                    });
                    hienCanhBaoTrungBaoCao(
                        { maBaoCao: payload.existingMaBaoCao },
                        payload.existingMaLo || (oNhapMaLoPublic?.value || '').trim().toUpperCase()
                    );
                } else {
                    capNhatTrangThaiNutGui(false);
                }

                hienThongBao(err.message || 'Đã xảy ra lỗi kết nối máy chủ!');
            });
        });
    }

    if (btnCopy) {
        btnCopy.addEventListener('click', function () {
            navigator.clipboard.writeText(maBaoCaoSinhRa.textContent).then(() => {
                this.innerHTML = `<i class="fa-solid fa-check text-emerald-700"></i> Đã sao chép!`;
                setTimeout(() => { this.innerHTML = `<i class="fa-regular fa-copy"></i> Sao chép mã`; }, 2000);
            });
        });
    }

    if (btnTaiTxt) {
        btnTaiTxt.addEventListener('click', function () {
            const code = baoCaoVuaGui?.maBaoCao || maBaoCaoSinhRa.textContent;
            const maLo = baoCaoVuaGui?.maLo || 'N/A';
            const ngayGui = baoCaoVuaGui?.ngayGui || new Date().toLocaleString('vi-VN');

            const noiDung =
`--------------------------------------------------
     PHARMACHAIN - PHIẾU LƯU MÃ BÁO CÁO NGHI VẤN
--------------------------------------------------
- Mã Tra Cứu  : ${code}
- Mã Lô Thuốc : ${maLo}
- Ngày Gửi    : ${ngayGui}
--------------------------------------------------
HƯỚNG DẪN TRA CỨU TIẾN TRÌNH XỬ LÝ:
1. Truy cập: ${window.location.origin}/bao-cao-nghi-van
2. Chọn Tab "Lịch Sử & Tra Cứu"
3. Nhập mã "${code}" hoặc tải file .txt này lên.
--------------------------------------------------`;

            const blob = new Blob([noiDung], { type: 'text/plain;charset=utf-8' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = `PhieuLuuMaBaoCao_${code}.txt`;
            a.click();
            URL.revokeObjectURL(a.href);
        });
    }

    document.getElementById('nutDongModalThanhCong')?.addEventListener('click', () => modalThanhCong.classList.add('hidden'));
    document.getElementById('nutChuyenSangLichSu')?.addEventListener('click', () => {
        modalThanhCong.classList.add('hidden');
        batTabLichSu();
    });

    // ══ TRA CỨU TỪ FILE .TXT ĐƠN LẺ ══
    if (inputUploadTxt) {
        inputUploadTxt.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function (evt) {
                const text = evt.target.result;
                const matches = text.match(/BC-\d{4}-[A-Z0-9]+/gi) || text.match(/BC-[A-Z0-9]+/gi);
                if (matches && matches.length > 0) {
                    const code = matches[0].toUpperCase();
                    inputTraCuu.value = code;
                    thucHienTraCuu(code);
                } else {
                    hienThongBao('Không tìm thấy Mã Báo Cáo hợp lệ trong file .txt này!');
                }
                inputUploadTxt.value = '';
            };
            reader.readAsText(file);
        });
    }

    // ══ NHẬP TOÀN BỘ LỊCH SỬ TỪ FILE .TXT ══
    if (inputNhapLichSuTxt) {
        inputNhapLichSuTxt.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function (evt) {
                const text = evt.target.result;
                const matches = text.match(/BC-\d{4}-[A-Z0-9]+/gi) || text.match(/BC-[A-Z0-9]+/gi);

                if (matches && matches.length > 0) {
                    const uniqueCodes = [...new Set(matches.map(c => c.toUpperCase()))];
                    let addedCount = 0;

                    Promise.all(
                        uniqueCodes.map(code =>
                            fetch(`/api/bao-cao-nghi-van/tra-cuu/${encodeURIComponent(code)}`)
                                .then(res => res.json())
                                .then(res => res.success ? res.data : null)
                                .catch(() => null)
                        )
                    ).then(results => {
                        results.forEach(data => {
                            if (data) {
                                luuVaoLichSuKhongTrungLap({
                                    maBaoCao: data.maBaoCao,
                                    maLo: data.maLo,
                                    ngayGui: data.ngayGui,
                                    trangThai: data.trangThai
                                });
                                addedCount++;
                            }
                        });

                        if (addedCount > 0) {
                            hienThongBao(`Đã nhập thành công ${addedCount} báo cáo vào lịch sử!`, 'success');
                        } else {
                            hienThongBao('Không tìm thấy hồ sơ báo cáo hợp lệ từ các mã trong file!');
                        }
                    });
                } else {
                    hienThongBao('Không tìm thấy Mã Báo Cáo hợp lệ nào trong file này!');
                }
                inputNhapLichSuTxt.value = '';
            };
            reader.readAsText(file);
        });
    }

    if (btnTraCuu) {
        btnTraCuu.addEventListener('click', function () {
            const code = inputTraCuu.value.trim().toUpperCase();
            if (!code) {
                hienThongBao('Vui lòng nhập mã báo cáo cần kiểm tra!');
                return;
            }
            thucHienTraCuu(code);
        });
    }

    function thucHienTraCuu(maBaoCao) {
        anThongBao();
        fetch(`/api/bao-cao-nghi-van/tra-cuu/${encodeURIComponent(maBaoCao)}`, {
            headers: { 'Accept': 'application/json' }
        })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    const data = res.data;

                    luuVaoLichSuKhongTrungLap({
                        maBaoCao: data.maBaoCao,
                        maLo: data.maLo,
                        ngayGui: data.ngayGui,
                        trangThai: data.trangThai
                    });

                    moModalChiTiet(data);
                } else {
                    hienThongBao(res.message || 'Không tìm thấy hồ sơ báo cáo.');
                }
            })
            .catch(() => hienThongBao('Lỗi hệ thống khi tra cứu!'));
    }

    function luuVaoLichSuKhongTrungLap(mucMoi) {
        const indexDaTonTai = duLieuLuuTru.findIndex(item => item.maBaoCao === mucMoi.maBaoCao);
        if (indexDaTonTai !== -1) {
            duLieuLuuTru.splice(indexDaTonTai, 1);
        }
        duLieuLuuTru.unshift(mucMoi);
        localStorage.setItem(STORAGE_KEY, JSON.stringify(duLieuLuuTru));
        renderBangLichSu();
    }

    function renderBangLichSu() {
        if (!badgeCount) return;
        badgeCount.textContent = duLieuLuuTru.length;

        if (duLieuLuuTru.length === 0) {
            if (khungBang) khungBang.classList.add('hidden');
            if (khungRong) khungRong.classList.remove('hidden');
            return;
        }

        if (khungRong) khungRong.classList.add('hidden');
        if (khungBang) khungBang.classList.remove('hidden');
        thanBang.innerHTML = '';

        duLieuLuuTru.forEach(item => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-slate-50 border-b border-slate-100 transition-colors cursor-pointer';

            let badgeTrangThai = `<span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-cyan-50 text-cyan-800 border border-cyan-200">Đã gửi</span>`;
            if (item.trangThai === 'DANG_XU_LY') {
                badgeTrangThai = `<span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300">Đang xác minh</span>`;
            } else if (item.trangThai === 'DA_XU_LY') {
                badgeTrangThai = `<span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">Đã thẩm định</span>`;
            }

            tr.innerHTML = `
                <td class="py-3 px-4 font-mono font-bold text-cyan-800">${escapeHtml(item.maBaoCao)}</td>
                <td class="py-3 px-4 font-mono font-bold text-slate-800">${escapeHtml(item.maLo)}</td>
                <td class="py-3 px-4">${badgeTrangThai}</td>
                <td class="py-3 px-4 text-slate-500 font-mono text-[11px]">${escapeHtml(item.ngayGui)}</td>
                <td class="py-3 px-4 text-center">
                    <button type="button" class="px-3 py-1 bg-emerald-50 text-emerald-700 font-bold text-xs rounded-lg hover:bg-emerald-100 transition-all cursor-pointer">
                        Tra Cứu <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </td>
            `;

            tr.addEventListener('click', () => thucHienTraCuu(item.maBaoCao));
            thanBang.appendChild(tr);
        });
    }

    if (nutXoaLichSu) {
        nutXoaLichSu.addEventListener('click', function () {
            if (duLieuLuuTru.length === 0) return;
            if (confirm('Bạn có chắc chắn muốn xóa toàn bộ lịch sử báo cáo trên trình duyệt này không?')) {
                localStorage.removeItem(STORAGE_KEY);
                duLieuLuuTru = [];
                renderBangLichSu();
                if (oNhapMaLoPublic?.value.trim()) {
                    kiemTraNhanhMaLo(oNhapMaLoPublic.value);
                }
            }
        });
    }

    if (nutXuatLichSu) {
        nutXuatLichSu.addEventListener('click', function () {
            if (duLieuLuuTru.length === 0) {
                hienThongBao('Lịch sử báo cáo đang trống, không có dữ liệu để xuất!');
                return;
            }

            let noiDung = `--------------------------------------------------\n`;
            noiDung += `   DANH SÁCH MÃ BÁO CÁO NGHI VẤN - PHARMACHAIN\n`;
            noiDung += `--------------------------------------------------\n`;
            noiDung += `Thời gian xuất: ${new Date().toLocaleString('vi-VN')}\n`;
            noiDung += `Tổng số lượng: ${duLieuLuuTru.length} báo cáo\n\n`;

            duLieuLuuTru.forEach((item, index) => {
                noiDung += `[${index + 1}] Mã Báo Cáo : ${item.maBaoCao}\n`;
                noiDung += `    Mã Lô      : ${item.maLo}\n`;
                noiDung += `    Thời Gian  : ${item.ngayGui}\n`;
                noiDung += `--------------------------------------------------\n`;
            });

            const blob = new Blob([noiDung], { type: 'text/plain;charset=utf-8' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = `DanhSachLichSuBaoCao_${dateFormatted()}.txt`;
            a.click();
            URL.revokeObjectURL(a.href);
        });
    }

    function dateFormatted() {
        const d = new Date();
        return `${d.getFullYear()}${String(d.getMonth() + 1).padStart(2, '0')}${String(d.getDate()).padStart(2, '0')}`;
    }

    // ══ MODAL CHI TIẾT KẾT QUẢ THẨM ĐỊNH (PHƯƠNG ÁN A) ══
    function moModalChiTiet(data) {
        document.getElementById('chiTietMaBaoCao').textContent = data.maBaoCao;
        document.getElementById('chiTietMaLo').textContent = data.maLo;
        document.getElementById('chiTietTenThuoc').textContent = data.tenThuoc;
        document.getElementById('chiTietNgayGui').textContent = data.ngayGui;
        document.getElementById('chiTietLyDo').textContent = data.lyDo;

        const loaiBadge = document.getElementById('chiTietLoaiBaoCaoBadge');
        if (loaiBadge) {
            loaiBadge.innerHTML = data.loaiBaoCao === 'NGOAI_HE_THONG'
                ? `<span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">Ngoài hệ thống</span>`
                : `<span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">Trong vòng đời</span>`;
        }

        let badgeHtml = `<span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-cyan-50 text-cyan-800 border border-cyan-200">Đã gửi</span>`;
        if (data.trangThai === 'DANG_XU_LY') {
            badgeHtml = `<span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300">Đang xác minh</span>`;
        } else if (data.trangThai === 'DA_XU_LY') {
            badgeHtml = `<span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">Đã thẩm định</span>`;
        }
        document.getElementById('chiTietTrangThaiBadge').innerHTML = badgeHtml;

        const khungAnh = document.getElementById('chiTietAnhMinhChung');
        const soTep = Number(data.soLuongMinhChung || 0);
        if (soTep > 0) {
            khungAnh.innerHTML = `
                <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-slate-700 flex items-center gap-2 text-xs">
                    <i class="fa-solid fa-lock text-slate-500"></i>
                    <span>Đã đính kèm <strong>${soTep} tệp minh chứng</strong> (Được ẩn trên cổng tra cứu công khai để bảo mật thông tin người gửi).</span>
                </div>
            `;
        } else {
            khungAnh.innerHTML = `<span class="text-slate-400 italic text-xs">Không có tệp bằng chứng đính kèm.</span>`;
        }

        const khungKetQua = document.getElementById('noiDungKetQuaXuLy');
        if (data.ketQua) {
            if (data.ketQua.quyetDinh === 'THU_HOI') {
                const txHtml = data.ketQua.txHashThuHoi
                    ? `<div class="pt-1 border-t border-red-200/80 flex items-center justify-between gap-2 text-[11px]">
                           <span class="font-mono truncate">TX: ${escapeHtml(data.ketQua.txHashThuHoi)}</span>
                           <a href="https://sepolia.etherscan.io/tx/${encodeURIComponent(data.ketQua.txHashThuHoi)}" target="_blank" class="font-bold underline shrink-0">Sepolia Etherscan</a>
                       </div>`
                    : '';

                khungKetQua.className = 'p-3 rounded-xl border border-red-200 bg-red-50 text-red-900 space-y-1.5 text-xs';
                khungKetQua.innerHTML = `
                    <div class="font-bold text-red-700 flex items-center gap-1.5"><i class="fa-solid fa-ban"></i> QUYẾT ĐỊNH: THU HỒI TOÀN BỘ LÔ THUỐC</div>
                    <p class="leading-relaxed">${escapeHtml(data.ketQua.lyDoXuLy)}</p>
                    ${txHtml}
                    <div class="text-[10px] text-red-600 font-mono pt-0.5">Thời điểm: ${escapeHtml(data.ketQua.ngayXuLy)}</div>
                `;
            } else if (data.ketQua.quyetDinh === 'GHI_NHAN') {
                khungKetQua.className = 'p-3 rounded-xl border border-amber-200 bg-amber-50 text-amber-950 space-y-1.5 text-xs';
                khungKetQua.innerHTML = `
                    <div class="font-bold text-amber-800 flex items-center gap-1.5"><i class="fa-solid fa-triangle-exclamation"></i> QUYẾT ĐỊNH: GHI NHẬN CẢNH BÁO HÀNG GIẢ NGOÀI HỆ THỐNG</div>
                    <p class="leading-relaxed">${escapeHtml(data.ketQua.lyDoXuLy)}</p>
                    <div class="text-[10px] text-amber-700 font-mono pt-0.5">Thời điểm: ${escapeHtml(data.ketQua.ngayXuLy)}</div>
                `;
            } else {
                khungKetQua.className = 'p-3 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 space-y-1.5 text-xs';
                khungKetQua.innerHTML = `
                    <div class="font-bold text-slate-700 flex items-center gap-1.5"><i class="fa-solid fa-circle-xmark"></i> QUYẾT ĐỊNH: TỪ CHỐI BÁO CÁO</div>
                    <p class="leading-relaxed">${escapeHtml(data.ketQua.lyDoXuLy)}</p>
                    <div class="text-[10px] text-slate-500 font-mono pt-0.5">Thời điểm: ${escapeHtml(data.ketQua.ngayXuLy)}</div>
                `;
            }
        } else {
            khungKetQua.className = 'p-3 rounded-xl border border-amber-200 bg-amber-50 text-amber-800 text-xs';
            khungKetQua.innerHTML = `<i class="fa-solid fa-hourglass-half mr-1"></i> Báo cáo đang trong tiến trình đối soát hồ sơ bởi Cơ quan quản lý Dược.`;
        }

        if (modalChiTiet) modalChiTiet.classList.remove('hidden');
    }

    document.getElementById('nutDongChiTiet')?.addEventListener('click', () => modalChiTiet.classList.add('hidden'));
    document.getElementById('nutDongChiTietBottom')?.addEventListener('click', () => modalChiTiet.classList.add('hidden'));
});
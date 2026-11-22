document.addEventListener('DOMContentLoaded', function () {
    const EXPLORER_BASE_URL = 'https://sepolia.etherscan.io/tx/';
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    let trangHienTai = 1;
    let cachedDanhSachLo = [];
    let loDangXemDetail = null;
    let loDangThuHoiId = null;

    const thanBang = document.getElementById('thanBangThuHoi');
    const oTimKiem = document.getElementById('oTimKiemThuHoi');
    const locNSX = document.getElementById('locNhaSanXuat');
    const locTrangThai = document.getElementById('locTrangThaiLo');
    const locNghiVan = document.getElementById('locNghiVanLo');
    const soLuongBadge = document.getElementById('soLuongLoBadge');
    const thongTinPhanTrang = document.getElementById('thongTinPhanTrang');
    const cumNutPhanTrang = document.getElementById('cumNutPhanTrang');

    function escapeHtml(str) {
        if (str == null) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function dinhDangNgay(ngayYMD) {
        if (!ngayYMD) return '--';
        const parts = ngayYMD.split('T')[0].split('-');
        if (parts.length === 3) return `${parts[2]}/${parts[1]}/${parts[0]}`;
        return ngayYMD;
    }

    function tinhSoLuongDangLuuThong(lo) {
        if (!lo.phanBoKho) return 0;
        return lo.phanBoKho.reduce((sum, item) => sum + (item.giu || 0), 0);
    }

    function tinhSoLuongDaBanLe(lo) {
        if (!lo.phanBoKho) return 0;
        return lo.phanBoKho.reduce((sum, item) => sum + (item.daBan || 0), 0);
    }

    // ══ 1. TẢI DỮ LIỆU TỪ BACKEND API ══
    async function loadDanhSachLo() {
        thanBang.innerHTML = `<tr><td colspan="8" class="text-center py-8 text-slate-400 italic"><i class="fa-solid fa-spinner fa-spin mr-2"></i>Đang tải dữ liệu từ máy chủ...</td></tr>`;

        const params = new URLSearchParams({
            page: trangHienTai,
            tuKhoa: oTimKiem ? oTimKiem.value.trim() : '',
            nhaSanXuatId: locNSX ? locNSX.value : 'TAT_CA',
            trangThaiLo: locTrangThai ? locTrangThai.value : 'TAT_CA'
        });

        try {
            const response = await fetch(`/co-quan/quan-ly-thu-hoi/data?${params.toString()}`, {
                headers: { 'Accept': 'application/json' }
            });
            if (!response.ok) throw new Error('Không thể lấy danh sách lô thuốc từ API');

            const data = await response.json();
            cachedDanhSachLo = data.data || [];

            renderBang(data);
        } catch (error) {
            console.error(error);
            thanBang.innerHTML = `<tr><td colspan="8" class="text-center py-8 text-red-500 italic">Có lỗi xảy ra khi tải dữ liệu. Vui lòng thử lại sau!</td></tr>`;
        }
    }

    // ══ 2. RENDER BẢNG VÀ PHÂN TRANG ══
    function renderBang(apiResponse) {
        let duLieu = apiResponse.data || [];
        if (locNghiVan && locNghiVan.value === 'CO_BAO_CAO') {
            duLieu = duLieu.filter(item => (item.soBaoCaoNghiVan || 0) > 0);
        }

        const tongSo = (locNghiVan && locNghiVan.value === 'CO_BAO_CAO')
            ? duLieu.length
            : (apiResponse.total || duLieu.length);

        soLuongBadge.textContent = `Hiển thị: ${tongSo} lô`;
        thanBang.innerHTML = '';

        if (duLieu.length === 0) {
            thanBang.innerHTML = `<tr><td colspan="8" class="text-center py-8 text-slate-400 italic">Không tìm thấy lô thuốc nào phù hợp với bộ lọc.</td></tr>`;
            thongTinPhanTrang.textContent = 'Không có bản ghi nào';
            cumNutPhanTrang.innerHTML = '';
            return;
        }

        thongTinPhanTrang.textContent = `Đang hiển thị ${apiResponse.from || 1} đến ${apiResponse.to || duLieu.length} trên tổng số ${tongSo} lô`;

        const homNay = new Date().toISOString().split('T')[0];

        duLieu.forEach(item => {
            const biThuHoi = (item.trangThaiLo === 'RECALLED');
            const daHetHan = (item.hanDung < homNay);
            const dangLuuThong = tinhSoLuongDangLuuThong(item);
            const daBanLe = tinhSoLuongDaBanLe(item);

            let badgeTrangThai = '';
            if (biThuHoi) {
                badgeTrangThai = `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-red-100 text-red-700 border border-red-300"><i class="fa-solid fa-ban"></i> Đã thu hồi</span>`;
            } else if (daHetHan) {
                badgeTrangThai = `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300"><i class="fa-solid fa-triangle-exclamation"></i> Hết hạn</span>`;
            } else {
                badgeTrangThai = `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300"><i class="fa-solid fa-truck-fast"></i> Đang lưu thông</span>`;
            }

            let nutThuHoiHtml = '';
            if (!biThuHoi) {
                nutThuHoiHtml = `
                    <button type="button" class="btn-mo-modal-thu-hoi px-2.5 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 font-bold text-xs transition-all flex items-center gap-1 border border-red-200 shadow-2xs whitespace-nowrap cursor-pointer" data-id="${item.id}">
                        <i class="fa-solid fa-ban text-[10px]"></i> Thu hồi
                    </button>
                `;
            }

            const nhanTonKho = biThuHoi ? 'Phong tỏa' : 'Lưu thông';
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-slate-50 transition-colors';
            tr.innerHTML = `
                <td class="py-3 px-3 font-mono font-bold text-slate-900 whitespace-nowrap">${escapeHtml(item.maLo)}</td>
                <td class="py-3 px-3 font-bold text-slate-900 leading-tight">${escapeHtml(item.tenSanPham)}</td>
                <td class="py-3 px-3 text-slate-800 font-semibold leading-tight">${escapeHtml(item.nhaSanXuat)}</td>
                <td class="py-3 px-2.5 text-center whitespace-nowrap font-mono text-xs">${escapeHtml(dinhDangNgay(item.hanDung))}</td>
                <td class="py-3 px-3 text-right whitespace-nowrap">
                    <span class="font-mono font-bold text-indigo-900 block">${(item.tongSoLuong || 0).toLocaleString('vi-VN')} hộp</span>
                    <span class="text-[10px] text-slate-500 font-mono">(${nhanTonKho}: ${dangLuuThong.toLocaleString('vi-VN')} • Đã bán: ${daBanLe.toLocaleString('vi-VN')})</span>
                </td>
                <td class="py-3 px-2.5 text-center whitespace-nowrap">${badgeTrangThai}</td>
                <td class="py-3 px-2.5 text-center whitespace-nowrap">
                    <span class="font-mono font-bold ${item.soBaoCaoNghiVan > 0 ? 'text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200' : 'text-slate-400'}">
                        ${item.soBaoCaoNghiVan || 0} báo cáo
                    </span>
                </td>
                <td class="py-3 px-3 text-center whitespace-nowrap">
                    <div class="inline-flex items-center justify-center gap-1.5">
                        <button type="button" class="btn-xem-chi-tiet-lo px-2.5 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-800 font-bold text-xs transition-all flex items-center gap-1 border border-indigo-200 shadow-2xs whitespace-nowrap cursor-pointer" data-id="${item.id}">
                            <i class="fa-solid fa-eye text-indigo-700 text-[10px]"></i> Chi tiết
                        </button>
                        ${nutThuHoiHtml}
                    </div>
                </td>
            `;
            thanBang.appendChild(tr);
        });

        renderPhanTrang(apiResponse.last_page || 1);
        ganSuKienNutBang();
    }

    function renderPhanTrang(tongSoTrang) {
        cumNutPhanTrang.innerHTML = '';
        if (tongSoTrang <= 1) return;

        for (let i = 1; i <= tongSoTrang; i++) {
            const btnPage = document.createElement('button');
            btnPage.type = 'button';
            btnPage.className = `w-7 h-7 rounded-lg text-xs font-bold transition-all cursor-pointer ${trangHienTai === i ? 'bg-indigo-700 text-white shadow-2xs' : 'border border-slate-200 text-slate-700 hover:bg-slate-100'}`;
            btnPage.textContent = i;
            btnPage.addEventListener('click', () => {
                trangHienTai = i;
                loadDanhSachLo();
            });
            cumNutPhanTrang.appendChild(btnPage);
        }
    }

    // ══ 3. MODAL CHI TIẾT LÔ THUỐC ══
    const modalDetail = document.getElementById('modalChiTietLo');
    const detailBannerDaThuHoi = document.getElementById('detailBannerDaThuHoi');
    const detailBannerGoiYThuHoiHetHan = document.getElementById('detailBannerGoiYThuHoiHetHan');
    const detailKhungNutThuHoiNhanh = document.getElementById('detailKhungNutThuHoiNhanh');
    const nutCopyTxHash = document.getElementById('nutCopyDetailTxThuHoi');

    function moModalChiTietLo(id) {
        loDangXemDetail = cachedDanhSachLo.find(x => x.id == id);
        if (!loDangXemDetail) return;

        const homNay = new Date().toISOString().split('T')[0];
        const biThuHoi = (loDangXemDetail.trangThaiLo === 'RECALLED');
        const daHetHan = (loDangXemDetail.hanDung < homNay);
        const dangLuuThong = tinhSoLuongDangLuuThong(loDangXemDetail);
        const daBanLe = tinhSoLuongDaBanLe(loDangXemDetail);

        document.getElementById('detailMaLoTitle').textContent = loDangXemDetail.maLo;

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
        document.getElementById('detailTongSoLuong').textContent = `${(loDangXemDetail.tongSoLuong || 0).toLocaleString('vi-VN')} hộp`;

        const nhanTonKho = document.getElementById('detailNhanTonKho');
        if (nhanTonKho) nhanTonKho.textContent = biThuHoi ? 'Tồn kho bị phong tỏa:' : 'Đang lưu thông:';
        const elDangLuuThong = document.getElementById('detailDangLuuThong');
        if (elDangLuuThong) {
            elDangLuuThong.textContent = `${dangLuuThong.toLocaleString('vi-VN')} hộp`;
            elDangLuuThong.className = biThuHoi ? 'font-mono font-bold text-red-700' : 'font-mono font-bold text-emerald-700';
        }

        const elDaBan = document.getElementById('detailDaBanRaThiTruong');
        if (elDaBan) elDaBan.textContent = `${daBanLe.toLocaleString('vi-VN')} hộp`;

        document.getElementById('detailSoBaoCaoNghiVan').textContent = `${loDangXemDetail.soBaoCaoNghiVan || 0} báo cáo`;
        document.getElementById('detailLinkSangNghiVan').href = `/co-quan/xu-ly-nghi-van?tuKhoa=${encodeURIComponent(loDangXemDetail.maLo)}`;

        const thGiu = document.getElementById('thCotSoLuongGiu');
        if (thGiu) thGiu.textContent = biThuHoi ? 'Tồn Kho Bị Phong Tỏa' : 'Số Lượng Giữ';

        if (biThuHoi && loDangXemDetail.thongTinThuHoi) {
            detailBannerDaThuHoi.classList.remove('hidden');
            detailBannerGoiYThuHoiHetHan.classList.add('hidden');
            document.getElementById('detailLyDoThuHoiText').textContent = loDangXemDetail.thongTinThuHoi.lyDo;
            document.getElementById('detailNgayThuHoiText').textContent = loDangXemDetail.thongTinThuHoi.ngayThuHoi;
            document.getElementById('detailDongBoText').textContent = loDangXemDetail.thongTinThuHoi.trangThaiDongBo;

            const hash = loDangXemDetail.thongTinThuHoi.hashBlockchain || 'N/A';
            document.getElementById('detailTxHashThuHoiText').textContent = hash;

            if (hash.startsWith('0x')) {
                document.getElementById('detailTxHashThuHoiLink').href = `${EXPLORER_BASE_URL}${hash}`;
            } else {
                document.getElementById('detailTxHashThuHoiLink').href = '#';
            }
            detailKhungNutThuHoiNhanh.innerHTML = '';
        } else {
            detailBannerDaThuHoi.classList.add('hidden');
            if (daHetHan) {
                detailBannerGoiYThuHoiHetHan.classList.remove('hidden');
            } else {
                detailBannerGoiYThuHoiHetHan.classList.add('hidden');
            }

            detailKhungNutThuHoiNhanh.innerHTML = `
                <button type="button" id="nutMoThuHoiTuDetail" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-xl transition-all flex items-center gap-1.5 shadow-md cursor-pointer">
                    <i class="fa-solid fa-ban text-[10px]"></i> Ban hành quyết định thu hồi
                </button>
            `;
            document.getElementById('nutMoThuHoiTuDetail')?.addEventListener('click', () => {
                modalDetail.classList.add('hidden');
                moModalThuHoi(loDangXemDetail.id);
            });
        }

        const thanBangPhanBo = document.getElementById('detailThanBangPhanBoKho');
        thanBangPhanBo.innerHTML = '';
        (loDangXemDetail.phanBoKho || []).forEach(pb => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-slate-50';
            tr.innerHTML = `
                <td class="p-2 font-bold text-slate-800 text-xs">${escapeHtml(pb.donVi)}</td>
                <td class="p-2 text-center font-sans">
                    <span class="badge-chuan px-2 py-0.2 rounded text-[10px] font-bold bg-slate-100 text-slate-700">${escapeHtml(pb.vaiTro)}</span>
                </td>
                <td class="p-2 text-right font-bold ${biThuHoi ? 'text-red-700' : 'text-indigo-900'}">${(pb.giu || 0).toLocaleString('vi-VN')} hộp</td>
                <td class="p-2 text-right text-slate-600">${(pb.daBan || 0).toLocaleString('vi-VN')} hộp</td>
            `;
            thanBangPhanBo.appendChild(tr);
        });

        modalDetail.classList.remove('hidden');
    }

    nutCopyTxHash?.addEventListener('click', function () {
        const text = document.getElementById('detailTxHashThuHoiText')?.textContent;
        if (text && text !== 'N/A') {
            navigator.clipboard.writeText(text);
            this.innerHTML = `<i class="fa-solid fa-check text-emerald-600"></i>`;
            setTimeout(() => {
                this.innerHTML = `<i class="fa-regular fa-copy"></i>`;
            }, 2000);
        }
    });

    document.querySelectorAll('.dong-modal-detail').forEach(btn => {
        btn.addEventListener('click', () => modalDetail.classList.add('hidden'));
    });

    // ══ 4. MODAL THU HỒI LÔ THUỐC (CHỜ BLOCKCHAIN CONFIRMED MỚI CHUYỂN POPUP) ══
    const modalThuHoi = document.getElementById('modalThuHoiLoHang');
    const manHinhFormThuHoi = document.getElementById('manHinhFormThuHoi');
    const manHinhDangGhiBlockchain = document.getElementById('manHinhDangGhiBlockchain');
    const manHinhKetQuaThuHoi = document.getElementById('manHinhKetQuaThuHoi');

    const iconKetQua = document.getElementById('iconKetQuaThuHoi');
    const tieuDeKetQua = document.getElementById('tieuDeKetQuaThuHoi');
    const noiDungKetQua = document.getElementById('noiDungKetQuaThuHoi');

    const nhapLyDoThuHoi = document.getElementById('nhapLyDoThuHoiLo');
    const demKyTuThuHoi = document.getElementById('demKyTuThuHoi');
    const nutXacNhanThuHoi = document.getElementById('nutXacNhanThuHoiBlockchain');

    function moModalThuHoi(id) {
        const lo = cachedDanhSachLo.find(x => x.id == id);
        if (!lo) return;

        loDangThuHoiId = id;
        const daBanLe = tinhSoLuongDaBanLe(lo);

        document.getElementById('thuHoiModalMaLo').textContent = lo.maLo;
        document.getElementById('thuHoiModalTenThuoc').textContent = lo.tenSanPham;
        document.getElementById('thuHoiModalNSX').textContent = lo.nhaSanXuat;

        const elModalDaBan = document.getElementById('thuHoiModalDaBan');
        if (elModalDaBan) elModalDaBan.textContent = `${daBanLe.toLocaleString('vi-VN')} hộp`;

        const containerBenGiu = document.getElementById('thuHoiModalDanhSachBenGiu');
        containerBenGiu.innerHTML = '';
        (lo.phanBoKho || []).forEach(pb => {
            const div = document.createElement('div');
            div.className = 'flex justify-between items-center py-0.5';
            div.innerHTML = `
                <span class="text-slate-700 font-sans truncate mr-2">• ${escapeHtml(pb.donVi)}:</span>
                <span class="font-bold text-slate-900 shrink-0">${(pb.giu || 0).toLocaleString('vi-VN')} hộp</span>
            `;
            containerBenGiu.appendChild(div);
        });

        nhapLyDoThuHoi.value = '';
        demKyTuThuHoi.textContent = '0/500';
        capNhatTrangThaiNutThuHoi();

        manHinhFormThuHoi.classList.remove('hidden');
        manHinhDangGhiBlockchain.classList.add('hidden');
        manHinhKetQuaThuHoi.classList.add('hidden');
        modalThuHoi.classList.remove('hidden');
    }

    function capNhatTrangThaiNutThuHoi() {
        const len = nhapLyDoThuHoi.value.trim().length;
        demKyTuThuHoi.textContent = `${len}/500`;

        if (len >= 5) {
            nutXacNhanThuHoi.disabled = false;
            nutXacNhanThuHoi.className = 'px-6 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl shadow-md transition-all cursor-pointer flex items-center gap-1.5';
        } else {
            nutXacNhanThuHoi.disabled = true;
            nutXacNhanThuHoi.className = 'px-6 py-2 bg-slate-200 text-slate-400 font-bold rounded-xl transition-all cursor-not-allowed shadow-none flex items-center gap-1.5';
        }
    }

    nhapLyDoThuHoi?.addEventListener('input', capNhatTrangThaiNutThuHoi);

    document.querySelectorAll('.dong-modal-thuhoi').forEach(btn => {
        btn.addEventListener('click', () => modalThuHoi.classList.add('hidden'));
    });

    nutXacNhanThuHoi?.addEventListener('click', async function () {
        const lyDo = nhapLyDoThuHoi.value.trim();
        if (!lyDo || !loDangThuHoiId) return;

        manHinhFormThuHoi.classList.add('hidden');
        manHinhKetQuaThuHoi.classList.add('hidden');
        manHinhDangGhiBlockchain.classList.remove('hidden');

        try {
            const response = await fetch('/co-quan/quan-ly-thu-hoi/thu-hoi', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: JSON.stringify({
                    loThuocId: loDangThuHoiId,
                    lyDo: lyDo
                })
            });

            const text = await response.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (err) {
                throw new Error(`Phản hồi từ máy chủ không hợp lệ (Mã ${response.status}).`);
            }

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Tạo quyết định thu hồi thất bại.');
            }

            const thuHoiId = data.data.id;

            let numTries = 0;
            const maxTries = 30;

            const checkTimer = setInterval(async () => {
                numTries++;
                try {
                    const resCheck = await fetch(`/co-quan/quan-ly-thu-hoi/check-status/${thuHoiId}`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const statusData = await resCheck.json();

                    if (statusData.success && statusData.trangThai === 'CONFIRMED') {
                        clearInterval(checkTimer);
                        manHinhDangGhiBlockchain.classList.add('hidden');
                        manHinhKetQuaThuHoi.classList.remove('hidden');

                        iconKetQua.className = 'w-16 h-16 mx-auto rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-3xl';
                        iconKetQua.innerHTML = `<i class="fa-solid fa-circle-check"></i>`;

                        tieuDeKetQua.className = 'text-base font-bold text-emerald-800';
                        tieuDeKetQua.textContent = 'Thu Hồi & Neo Blockchain Thành Công!';

                        noiDungKetQua.innerHTML = `Lô thuốc đã thu hồi và phong tỏa tồn kho chính thức.<br><span class="font-mono text-[11px] text-cyan-800 font-bold break-all">TxHash: ${escapeHtml(statusData.txHash)}</span>`;

                        loadDanhSachLo();
                    } else if (statusData.success && statusData.trangThai === 'FAILED') {
                        clearInterval(checkTimer);
                        manHinhDangGhiBlockchain.classList.add('hidden');
                        manHinhKetQuaThuHoi.classList.remove('hidden');

                        iconKetQua.className = 'w-16 h-16 mx-auto rounded-full bg-red-100 text-red-600 flex items-center justify-center text-3xl';
                        iconKetQua.innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i>`;

                        tieuDeKetQua.className = 'text-base font-bold text-red-700';
                        tieuDeKetQua.textContent = 'Giao Dịch Blockchain Thất Bại!';

                        noiDungKetQua.textContent = 'Hồ sơ đã lưu nhưng giao dịch ký On-chain bị lỗi. Vui lòng kiểm tra lại Queue Worker / Signer Service.';

                        loadDanhSachLo();
                    } else if (numTries >= maxTries) {
                        clearInterval(checkTimer);
                        manHinhDangGhiBlockchain.classList.add('hidden');
                        manHinhKetQuaThuHoi.classList.remove('hidden');

                        iconKetQua.className = 'w-16 h-16 mx-auto rounded-full bg-amber-100 text-amber-600 flex items-center justify-center text-3xl';
                        iconKetQua.innerHTML = `<i class="fa-solid fa-clock"></i>`;

                        tieuDeKetQua.className = 'text-base font-bold text-amber-800';
                        tieuDeKetQua.textContent = 'Đã Ban Hành (Đang Chờ Đào Block)';

                        noiDungKetQua.textContent = 'Quyết định đã được đưa vào hàng chờ Queue. Tiến trình Blockchain vẫn đang được xử lý ngầm.';

                        loadDanhSachLo();
                    }
                } catch (err) {
                    console.error('Lỗi khi polling:', err);
                }
            }, 2000);
        } catch (error) {
            manHinhDangGhiBlockchain.classList.add('hidden');
            manHinhKetQuaThuHoi.classList.remove('hidden');

            iconKetQua.className = 'w-16 h-16 mx-auto rounded-full bg-red-100 text-red-600 flex items-center justify-center text-3xl';
            iconKetQua.innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i>`;

            tieuDeKetQua.className = 'text-base font-bold text-red-700';
            tieuDeKetQua.textContent = 'Ban Hành Thất Bại!';

            noiDungKetQua.textContent = error.message;
        }
    });

    function ganSuKienNutBang() {
        document.querySelectorAll('.btn-xem-chi-tiet-lo').forEach(btn => {
            btn.addEventListener('click', function () {
                moModalChiTietLo(this.getAttribute('data-id'));
            });
        });

        document.querySelectorAll('.btn-mo-modal-thu-hoi').forEach(btn => {
            btn.addEventListener('click', function () {
                moModalThuHoi(this.getAttribute('data-id'));
            });
        });
    }

    oTimKiem?.addEventListener('input', () => { trangHienTai = 1; loadDanhSachLo(); });
    locNSX?.addEventListener('change', () => { trangHienTai = 1; loadDanhSachLo(); });
    locTrangThai?.addEventListener('change', () => { trangHienTai = 1; loadDanhSachLo(); });
    locNghiVan?.addEventListener('change', () => { trangHienTai = 1; loadDanhSachLo(); });

    loadDanhSachLo();
});
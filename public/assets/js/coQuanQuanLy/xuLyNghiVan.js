document.addEventListener('DOMContentLoaded', function () {
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    let trangHienTai = 1;
    let cachedDanhSachNghiVan = [];
    let nhomBaoCaoCungLoHienTai = [];
    let lichSuDieuHuongModal = [];
    let baoCaoDangChonObj = null;
    let baoCaoDangChonId = null;
    let cheDoXuLyHienTai = 'THU_HOI'; // 'THU_HOI' | 'GHI_NHAN' | 'TU_CHOI' | 'THU_HOI_DOI_CHIEU'

    const thanBang = document.getElementById('thanBangNghiVan');
    const oTimKiem = document.getElementById('oTimKiemNghiVan');
    const locTrangThai = document.getElementById('locTrangThaiNghiVan');
    const locLoaiBaoCao = document.getElementById('locLoaiBaoCao');
    const soLuongBadge = document.getElementById('soLuongNghiVanBadge');
    const thongTinPhanTrang = document.getElementById('thongTinPhanTrangNghiVan');
    const cumNutPhanTrang = document.getElementById('cumNutPhanTrangNghiVan');

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('tuKhoa') && oTimKiem) {
        oTimKiem.value = urlParams.get('tuKhoa');
    }

    function escapeHtml(str) {
        if (str == null) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function dinhDangNgayGio(ngayISO) {
        if (!ngayISO || ngayISO === '--' || ngayISO === 'N/A') return '--';
        const date = new Date(ngayISO);
        if (isNaN(date.getTime())) return ngayISO;
        return date.toLocaleString('vi-VN', {
            hour: '2-digit', minute: '2-digit',
            day: '2-digit', month: '2-digit', year: 'numeric'
        });
    }

    function taoBadgePhanLoai(loai) {
        if (loai === 'NGOAI_HE_THONG') {
            return `<span class="badge-chuan px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200 whitespace-nowrap">Ngoài hệ thống</span>`;
        }
        return `<span class="badge-chuan px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 whitespace-nowrap">Trong vòng đời</span>`;
    }

    function taoBadgeTrangThai(item) {
        if (item.trangThai !== 'DA_XU_LY') {
            return `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300"><i class="fa-solid fa-clock"></i> Chờ xử lý</span>`;
        }
        if (item.quyetDinhXuLy === 'THU_HOI') {
            return `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-red-100 text-red-700 border border-red-300"><i class="fa-solid fa-ban"></i> Đã thu hồi lô</span>`;
        }
        if (item.quyetDinhXuLy === 'GHI_NHAN') {
            return `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-900 border border-amber-300"><i class="fa-solid fa-triangle-exclamation"></i> Đã ghi nhận giả</span>`;
        }
        if (item.quyetDinhXuLy === 'TU_CHOI') {
            return `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-slate-100 text-slate-700 border border-slate-300"><i class="fa-solid fa-circle-xmark"></i> Đã từ chối</span>`;
        }
        return `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300"><i class="fa-solid fa-check"></i> Đã xử lý</span>`;
    }

    function timBaoCaoTheoId(id) {
        return nhomBaoCaoCungLoHienTai.find(x => x.id == id)
            || cachedDanhSachNghiVan.find(x => x.id == id)
            || null;
    }

    // ══ 1. TẢI DỮ LIỆU TỪ BACKEND API ══
    async function loadDanhSachBaoCao() {
        if (!thanBang) return;
        thanBang.innerHTML = `<tr><td colspan="8" class="text-center py-8 text-slate-400 italic"><i class="fa-solid fa-spinner fa-spin mr-2"></i>Đang tải dữ liệu nghi vấn...</td></tr>`;

        const params = new URLSearchParams({
            page: trangHienTai,
            tuKhoa: oTimKiem ? oTimKiem.value.trim() : '',
            trangThai: locTrangThai ? locTrangThai.value : 'TAT_CA',
            loaiBaoCao: locLoaiBaoCao ? locLoaiBaoCao.value : 'TAT_CA'
        });

        try {
            const response = await fetch(`/co-quan/xu-ly-nghi-van/data?${params.toString()}`, {
                headers: { 'Accept': 'application/json' }
            });

            const text = await response.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (err) {
                throw new Error(`Dữ liệu máy chủ không hợp lệ (Mã ${response.status}): ${text.substring(0, 100)}...`);
            }

            if (!response.ok) {
                throw new Error(data.message || 'Không thể lấy danh sách báo cáo nghi vấn từ máy chủ');
            }

            cachedDanhSachNghiVan = data.data || [];
            renderBang(data);
        } catch (error) {
            console.error('Lỗi khi tải danh sách báo cáo nghi vấn:', error);
            thanBang.innerHTML = `<tr><td colspan="8" class="text-center py-8 text-red-500 italic">Có lỗi xảy ra: ${escapeHtml(error.message)}</td></tr>`;
        }
    }

    // ══ 2. RENDER BẢNG VÀ PHÂN TRANG ══
    function renderBang(apiResponse) {
        const duLieu = apiResponse.data || [];
        const tongSo = apiResponse.total || duLieu.length;

        if (soLuongBadge) soLuongBadge.textContent = `Hiển thị: ${tongSo} báo cáo`;
        thanBang.innerHTML = '';

        if (duLieu.length === 0) {
            thanBang.innerHTML = `<tr><td colspan="8" class="text-center py-8 text-slate-400 italic">Không tìm thấy báo cáo nghi vấn nào phù hợp.</td></tr>`;
            if (thongTinPhanTrang) thongTinPhanTrang.textContent = 'Không có bản ghi nào';
            if (cumNutPhanTrang) cumNutPhanTrang.innerHTML = '';
            return;
        }

        if (thongTinPhanTrang) {
            thongTinPhanTrang.textContent = `Đang hiển thị ${apiResponse.from || 1} đến ${apiResponse.to || duLieu.length} trên tổng số ${tongSo} bản ghi`;
        }

        duLieu.forEach(item => {
            const daXuLy = (item.trangThai === 'DA_XU_LY');
            const laTrongVongDoi = (item.loaiBaoCao === 'TRONG_VONG_DOI' && item.loThuocId);

            let nutXuLyHtml = '';
            if (!daXuLy) {
                const btnClass = laTrongVongDoi
                    ? 'bg-red-50 hover:bg-red-100 text-red-600 border-red-200'
                    : 'bg-amber-50 hover:bg-amber-100 text-amber-800 border-amber-200';
                nutXuLyHtml = `
                    <button type="button" class="btn-mo-modal-xu-ly px-2.5 py-1.5 rounded-lg ${btnClass} font-bold text-xs transition-all flex items-center gap-1 border shadow-2xs whitespace-nowrap cursor-pointer" data-id="${item.id}">
                        <i class="fa-solid fa-gavel text-[10px]"></i> Xử lý
                    </button>
                `;
            }

            const maBaoCaoText = escapeHtml(item.maBaoCao || ('BC-' + item.id));
            const maLoText = escapeHtml(item.maLo || 'N/A');
            const badgeCungLo = (item.tongBaoCaoCungLo || 1) > 1
                ? `<div class="mt-0.5"><span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200 text-[10px] font-sans font-bold"><i class="fa-solid fa-layer-group text-[9px]"></i> ${item.tongBaoCaoCungLo} phiếu cùng lô</span></div>`
                : '';

            const tenSanPhamText = escapeHtml(item.tenSanPham || 'N/A');
            const loGocSub = item.loBiLamGia
                ? `<div class="text-[10px] text-amber-800 font-mono mt-0.5">Đối chiếu lô gốc: ${escapeHtml(item.loBiLamGia.maLoNghiepVu)}${item.loBiLamGia.daThuHoi ? ' (Đã thu hồi)' : ''}</div>`
                : '';
            const donViText = escapeHtml(item.donViBaoCao || 'N/A');
            const thoiGianText = escapeHtml(dinhDangNgayGio(item.thoiGianGui));

            const tr = document.createElement('tr');
            tr.className = 'hover:bg-slate-50 transition-colors';
            tr.innerHTML = `
                <td class="py-3 px-3 font-mono font-bold text-amber-900 whitespace-nowrap">${maBaoCaoText}</td>
                <td class="py-3 px-3 whitespace-nowrap">${taoBadgePhanLoai(item.loaiBaoCao)}</td>
                <td class="py-3 px-3 font-mono font-bold text-indigo-900 whitespace-nowrap">
                    <div>${maLoText}</div>
                    ${badgeCungLo}
                </td>
                <td class="py-3 px-3 font-bold text-slate-900 leading-tight">
                    <div>${tenSanPhamText}</div>
                    ${loGocSub}
                </td>
                <td class="py-3 px-3 text-slate-800 font-semibold leading-tight">${donViText}</td>
                <td class="py-3 px-2.5 text-center whitespace-nowrap font-mono text-xs">${thoiGianText}</td>
                <td class="py-3 px-2.5 text-center whitespace-nowrap">${taoBadgeTrangThai(item)}</td>
                <td class="py-3 px-3 text-center whitespace-nowrap">
                    <div class="inline-flex items-center justify-center gap-1.5">
                        <button type="button" class="btn-xem-chi-tiet-nghi-van px-2.5 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-800 font-bold text-xs transition-all flex items-center gap-1 border border-indigo-200 shadow-2xs whitespace-nowrap cursor-pointer" data-id="${item.id}">
                            <i class="fa-solid fa-eye text-indigo-700 text-[10px]"></i> Chi tiết
                        </button>
                        ${nutXuLyHtml}
                    </div>
                </td>
            `;
            thanBang.appendChild(tr);
        });

        renderPhanTrang(apiResponse.last_page || 1);
        ganSuKienNutBang();
    }

    function renderPhanTrang(tongSoTrang) {
        if (!cumNutPhanTrang) return;
        cumNutPhanTrang.innerHTML = '';
        if (tongSoTrang <= 1) return;

        for (let i = 1; i <= tongSoTrang; i++) {
            const btnPage = document.createElement('button');
            btnPage.type = 'button';
            btnPage.className = `w-7 h-7 rounded-lg text-xs font-bold transition-all cursor-pointer ${trangHienTai === i ? 'bg-indigo-700 text-white shadow-2xs' : 'border border-slate-200 text-slate-700 hover:bg-slate-100'}`;
            btnPage.textContent = i;
            btnPage.addEventListener('click', () => {
                trangHienTai = i;
                loadDanhSachBaoCao();
            });
            cumNutPhanTrang.appendChild(btnPage);
        }
    }

    // ══ 3. MODAL CHI TIẾT BÁO CÁO NGHI VẤN (ĐỀ XUẤT 4: XEM CHÉO BÁO CÁO CÙNG LÔ) ══
    const modalDetail = document.getElementById('modalChiTietNghiVan');

    function moModalChiTiet(id, isChuyenNoiBo = false) {
        if (!isChuyenNoiBo) {
            lichSuDieuHuongModal = [];
            const goc = cachedDanhSachNghiVan.find(x => x.id == id);
            if (!goc || !modalDetail) return;
            nhomBaoCaoCungLoHienTai = [goc, ...(goc.baoCaoLienQuan || [])];
        }

        const item = timBaoCaoTheoId(id);
        if (!item || !modalDetail) return;

        const daXuLy = (item.trangThai === 'DA_XU_LY');
        const laTrongVongDoi = (item.loaiBaoCao === 'TRONG_VONG_DOI' && item.loThuocId);

        // Thanh quay lại phiếu trước khi điều hướng trong nhóm cùng lô
        const thanhQuayLai = document.getElementById('detailThanhQuayLaiPhieuGoc');
        const nutQuayLai = document.getElementById('nutQuayLaiPhieuTruoc');
        if (thanhQuayLai && nutQuayLai) {
            if (lichSuDieuHuongModal.length > 0) {
                const prevId = lichSuDieuHuongModal[lichSuDieuHuongModal.length - 1];
                const prevItem = timBaoCaoTheoId(prevId);
                nutQuayLai.innerHTML = `<i class="fa-solid fa-arrow-left text-[10px]"></i> Quay lại phiếu ${escapeHtml(prevItem?.maBaoCao || '')}`;
                thanhQuayLai.classList.remove('hidden');
            } else {
                thanhQuayLai.classList.add('hidden');
            }
        }

        document.getElementById('detailMaBaoCaoTitle').textContent = item.maBaoCao || ('BC-' + item.id);
        document.getElementById('detailLoaiBaoCaoBadge').innerHTML = taoBadgePhanLoai(item.loaiBaoCao);
        document.getElementById('detailTrangThaiBadge').innerHTML = taoBadgeTrangThai(item);

        document.getElementById('detailMaLoThuoc').textContent = item.maLo || 'N/A';
        document.getElementById('detailTenSanPham').textContent = item.tenSanPham || 'N/A';
        document.getElementById('detailDonViBaoCao').textContent = item.donViBaoCao || 'N/A';
        document.getElementById('detailThoiGianGui').textContent = dinhDangNgayGio(item.thoiGianGui);
        document.getElementById('detailLoaiBaoCao').textContent = item.loaiBaoCaoNhan || 'Báo cáo nghi vấn';
        document.getElementById('detailNoiDungBaoCao').textContent = item.noiDung || 'Không có mô tả chi tiết.';

        // Khung lô gốc đối chiếu
        const khungLoBiLamGia = document.getElementById('detailKhungLoBiLamGia');
        const thongTinLoBiLamGia = document.getElementById('detailThongTinLoBiLamGia');
        const badgeLoGoc = document.getElementById('detailBadgeTrangThaiLoGoc');
        const txLoGocWrap = document.getElementById('detailTxLoGocWrap');

        if (khungLoBiLamGia && thongTinLoBiLamGia) {
            if (!laTrongVongDoi && item.loBiLamGia) {
                const lg = item.loBiLamGia;
                thongTinLoBiLamGia.textContent = `Mã lô gốc: ${lg.maLoNghiepVu} — ${lg.tenSanPham} (SĐK: ${lg.soDangKy}, NSX: ${lg.nhaSanXuat}, HSD: ${lg.hanSuDung})`;

                if (badgeLoGoc) {
                    badgeLoGoc.innerHTML = lg.daThuHoi
                        ? `<span class="badge-chuan px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-red-100 text-red-700 border border-red-300"><i class="fa-solid fa-ban"></i> Lô đối chiếu đã thu hồi</span>`
                        : '';
                }

                if (txLoGocWrap && lg.daThuHoi && lg.txHashThuHoi) {
                    txLoGocWrap.classList.remove('hidden');
                    document.getElementById('detailTxLoGocText').textContent = `TX thu hồi lô gốc: ${lg.txHashThuHoi}`;
                    document.getElementById('detailTxLoGocLink').href = `https://sepolia.etherscan.io/tx/${encodeURIComponent(lg.txHashThuHoi)}`;
                } else if (txLoGocWrap) {
                    txLoGocWrap.classList.add('hidden');
                }

                khungLoBiLamGia.classList.remove('hidden');
            } else {
                khungLoBiLamGia.classList.add('hidden');
            }
        }

        // Danh sách minh chứng đính kèm
        const khungMinhChung = document.getElementById('detailKhungMinhChung');
        const dsMinhChung = document.getElementById('detailDanhSachMinhChung');
        if (khungMinhChung && dsMinhChung) {
            if (item.minhChungs && item.minhChungs.length > 0) {
                khungMinhChung.classList.remove('hidden');
                dsMinhChung.innerHTML = item.minhChungs.map(mc => `
                    <a href="${escapeHtml(mc.duongDan)}" target="_blank" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-white border border-slate-200 hover:border-indigo-400 rounded-lg text-[11px] font-medium text-slate-700 hover:text-indigo-700 transition-all shadow-2xs">
                        <i class="fa-solid fa-file-image text-indigo-500"></i> ${escapeHtml(mc.tenFile)}
                    </a>
                `).join('');
            } else {
                khungMinhChung.classList.add('hidden');
                dsMinhChung.innerHTML = '';
            }
        }

        const khungKetQua = document.getElementById('detailKhungKetQuaXuLy');
        const tieuDeKetQua = document.getElementById('detailTieuDeKetQua');
        const noiDungKetQua = document.getElementById('detailNoiDungKetQua');
        const txWrap = document.getElementById('detailTxThuHoiWrap');
        const khungNutNhanh = document.getElementById('detailKhungNutXuLyNhanh');

        if (daXuLy) {
            if (khungKetQua) {
                khungKetQua.classList.remove('hidden');
                if (item.quyetDinhXuLy === 'THU_HOI') {
                    khungKetQua.className = 'space-y-1.5 p-3 rounded-xl border bg-red-50 border-red-200 text-red-950';
                    tieuDeKetQua.innerHTML = `<i class="fa-solid fa-ban text-red-700 mr-1"></i> Quyết định: Thu hồi toàn bộ lô thuốc`;
                } else if (item.quyetDinhXuLy === 'GHI_NHAN') {
                    khungKetQua.className = 'space-y-1.5 p-3 rounded-xl border bg-amber-50 border-amber-200 text-amber-950';
                    tieuDeKetQua.innerHTML = `<i class="fa-solid fa-triangle-exclamation text-amber-700 mr-1"></i> Quyết định: Ghi nhận cảnh báo hàng giả ngoài hệ thống`;
                } else {
                    khungKetQua.className = 'space-y-1.5 p-3 rounded-xl border bg-slate-100 border-slate-300 text-slate-900';
                    tieuDeKetQua.innerHTML = `<i class="fa-solid fa-circle-xmark text-slate-600 mr-1"></i> Quyết định: Từ chối báo cáo`;
                }

                noiDungKetQua.textContent = item.ketQuaXuLy || 'Đã hoàn tất thẩm định báo cáo.';
                document.getElementById('detailThoiGianXuLy').textContent = `Thời gian xử lý: ${dinhDangNgayGio(item.thoiGianXuLy)}`;

                if (txWrap && item.quyetDinhXuLy === 'THU_HOI' && item.txHashThuHoi) {
                    txWrap.classList.remove('hidden');
                    document.getElementById('detailTxThuHoiText').textContent = `TX: ${item.txHashThuHoi}`;
                    document.getElementById('detailTxThuHoiLink').href = `https://sepolia.etherscan.io/tx/${encodeURIComponent(item.txHashThuHoi)}`;
                } else if (txWrap) {
                    txWrap.classList.add('hidden');
                }
            }

            if (khungNutNhanh) {
                if (item.quyetDinhXuLy === 'GHI_NHAN' && item.loBiLamGia && !item.loBiLamGia.daThuHoi) {
                    khungNutNhanh.innerHTML = `
                        <button type="button" id="nutThuHoiLoDoiChieu" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-xl transition-all flex items-center gap-1.5 shadow-md cursor-pointer">
                            <i class="fa-solid fa-ban text-[10px]"></i> Thu hồi lô ${escapeHtml(item.loBiLamGia.maLoNghiepVu)}
                        </button>
                    `;
                    document.getElementById('nutThuHoiLoDoiChieu')?.addEventListener('click', () => {
                        modalDetail.classList.add('hidden');
                        moModalXuLy(item.id, 'THU_HOI_DOI_CHIEU');
                    });
                } else {
                    khungNutNhanh.innerHTML = '';
                }
            }
        } else {
            if (khungKetQua) khungKetQua.classList.add('hidden');

            const nutChinhHtml = laTrongVongDoi
                ? `<button type="button" data-mode="THU_HOI" class="btn-chon-xu-ly-nhanh px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-xl transition-all flex items-center gap-1.5 shadow-md cursor-pointer">
                       <i class="fa-solid fa-ban text-[10px]"></i> Ban hành thu hồi lô
                   </button>`
                : `<button type="button" data-mode="GHI_NHAN" class="btn-chon-xu-ly-nhanh px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs rounded-xl transition-all flex items-center gap-1.5 shadow-md cursor-pointer">
                       <i class="fa-solid fa-triangle-exclamation text-[10px]"></i> Ghi nhận hàng giả
                   </button>`;

            if (khungNutNhanh) {
                khungNutNhanh.innerHTML = `
                    <button type="button" data-mode="TU_CHOI" class="btn-chon-xu-ly-nhanh px-3.5 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold text-xs rounded-xl transition-all flex items-center gap-1.5 cursor-pointer">
                        <i class="fa-solid fa-circle-xmark text-[10px]"></i> Từ chối báo cáo
                    </button>
                    ${nutChinhHtml}
                `;
            }

            document.querySelectorAll('.btn-chon-xu-ly-nhanh').forEach(btn => {
                btn.addEventListener('click', () => {
                    const mode = btn.getAttribute('data-mode');
                    modalDetail.classList.add('hidden');
                    moModalXuLy(item.id, mode);
                });
            });
        }

        // Render khối CÁC LƯỢT BÁO CÁO KHÁC CÙNG LÔ
        const khungLienQuan = document.getElementById('detailKhungBaoCaoLienQuan');
        const tieuDeLienQuan = document.getElementById('detailTieuDeBaoCaoLienQuan');
        const tomTatNguon = document.getElementById('detailTomTatNguonLienQuan');
        const dsLienQuanWrap = document.getElementById('detailDanhSachBaoCaoLienQuan');

        const cacPhieuKhac = nhomBaoCaoCungLoHienTai.filter(x => x.id != item.id);
        if (khungLienQuan && dsLienQuanWrap) {
            if (cacPhieuKhac.length > 0) {
                const soDn = cacPhieuKhac.filter(x => x.laDoanhNghiep).length;
                const soNtd = cacPhieuKhac.length - soDn;

                if (tieuDeLienQuan) {
                    tieuDeLienQuan.textContent = `Các Báo Cáo Khác Cùng Lô (${cacPhieuKhac.length} phiếu liên quan)`;
                }
                if (tomTatNguon) {
                    tomTatNguon.textContent = `DN: ${soDn} • NTD: ${soNtd}`;
                }

                dsLienQuanWrap.innerHTML = cacPhieuKhac.map(rel => {
                    const soTep = (rel.minhChungs || []).length;
                    const nguonBadge = rel.laDoanhNghiep
                        ? `<span class="px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-800 font-bold text-[10px]">DN</span>`
                        : `<span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-semibold text-[10px]">NTD</span>`;

                    return `
                        <div class="p-2.5 bg-white border border-indigo-100 rounded-xl flex items-center justify-between gap-2 hover:border-indigo-300 transition-all">
                            <div class="min-w-0 flex-1 space-y-0.5">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="font-mono font-bold text-amber-900">${escapeHtml(rel.maBaoCao)}</span>
                                    ${nguonBadge}
                                    <span class="font-semibold text-slate-800 truncate">${escapeHtml(rel.donViBaoCao)}</span>
                                </div>
                                <div class="text-[11px] text-slate-600 truncate">${escapeHtml(rel.lyDo)}</div>
                                <div class="text-[10px] text-slate-400 font-mono flex items-center gap-2">
                                    <span>${escapeHtml(dinhDangNgayGio(rel.thoiGianGui))}</span>
                                    <span>• ${soTep} tệp minh chứng</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                ${taoBadgeTrangThai(rel)}
                                <button type="button" data-rel-id="${rel.id}" class="btn-xem-phieu-lien-quan px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-800 border border-indigo-200 font-bold rounded-lg text-[11px] cursor-pointer">
                                    Xem
                                </button>
                            </div>
                        </div>
                    `;
                }).join('');

                dsLienQuanWrap.querySelectorAll('.btn-xem-phieu-lien-quan').forEach(btn => {
                    btn.addEventListener('click', function () {
                        const nextId = this.getAttribute('data-rel-id');
                        if (nextId) {
                            lichSuDieuHuongModal.push(item.id);
                            moModalChiTiet(nextId, true);
                        }
                    });
                });

                khungLienQuan.classList.remove('hidden');
            } else {
                khungLienQuan.classList.add('hidden');
                dsLienQuanWrap.innerHTML = '';
            }
        }

        modalDetail.classList.remove('hidden');
    }

    document.getElementById('nutQuayLaiPhieuTruoc')?.addEventListener('click', function () {
        if (lichSuDieuHuongModal.length === 0) return;
        const prevId = lichSuDieuHuongModal.pop();
        moModalChiTiet(prevId, true);
    });

    document.querySelectorAll('.dong-modal-detail-nghivan').forEach(btn => {
        btn.addEventListener('click', () => modalDetail?.classList.add('hidden'));
    });

    // ══ 4. MODAL XỬ LÝ BÁO CÁO & THU HỒI LÔ ĐỐI CHIẾU ══
    const modalXuLy = document.getElementById('modalXuLyThuHoiNghiVan');
    const manHinhFormNghiVan = document.getElementById('manHinhFormNghiVan');
    const manHinhDangGhiNghiVan = document.getElementById('manHinhDangGhiNghiVan');
    const manHinhKetQuaNghiVan = document.getElementById('manHinhKetQuaNghiVan');

    const iconKetQua = document.getElementById('iconKetQuaNghiVan');
    const tieuDeKetQua = document.getElementById('tieuDeKetQuaNghiVan');
    const noiDungKetQua = document.getElementById('noiDungKetQuaNghiVan');

    const thongBaoGopPhieu = document.getElementById('thongBaoGopPhieuCungLo');
    const khungHinhThucQuyetDinh = document.getElementById('khungHinhThucQuyetDinh');
    const khungChonCheDo = document.getElementById('khungChonCheDoXuLy');
    const nhapLyDoXuLy = document.getElementById('nhapLyDoXuLyNghiVan');
    const demKyTuNghiVan = document.getElementById('demKyTuNghiVan');
    const nutXacNhanXuLy = document.getElementById('nutXacNhanThuHoiNghiVan');

    function thietLapCheDoXuLy(mode, item) {
        cheDoXuLyHienTai = mode;
        const laTrongVongDoi = (item.loaiBaoCao === 'TRONG_VONG_DOI' && item.loThuocId);

        // Hiển thị cảnh báo đóng gộp các phiếu chờ khác cùng lô nếu ban hành THU_HOI
        if (thongBaoGopPhieu) {
            const soPhieuChoKhac = nhomBaoCaoCungLoHienTai.filter(
                x => x.id != item.id && x.trangThai !== 'DA_XU_LY' && x.loaiBaoCao === 'TRONG_VONG_DOI'
            ).length;

            if (mode === 'THU_HOI' && soPhieuChoKhac > 0) {
                thongBaoGopPhieu.innerHTML = `<i class="fa-solid fa-layer-group mr-1 text-red-700"></i> Lô này hiện còn <strong>${soPhieuChoKhac} báo cáo nghi vấn khác</strong> đang chờ xử lý. Khi ban hành thu hồi lô, hệ thống sẽ tự động cập nhật kết quả thu hồi cho toàn bộ các phiếu cùng lô này.`;
                thongBaoGopPhieu.classList.remove('hidden');
            } else {
                thongBaoGopPhieu.classList.add('hidden');
            }
        }

        if (khungHinhThucQuyetDinh) {
            if (mode === 'THU_HOI_DOI_CHIEU') {
                khungHinhThucQuyetDinh.classList.add('hidden');
            } else {
                khungHinhThucQuyetDinh.classList.remove('hidden');
            }
        }

        if (khungChonCheDo && mode !== 'THU_HOI_DOI_CHIEU') {
            const optionChinh = laTrongVongDoi
                ? { mode: 'THU_HOI', label: 'Thu hồi toàn bộ lô', icon: 'fa-ban', activeClass: 'border-red-600 bg-red-50 text-red-800' }
                : { mode: 'GHI_NHAN', label: 'Ghi nhận hàng giả', icon: 'fa-triangle-exclamation', activeClass: 'border-amber-600 bg-amber-50 text-amber-900' };

            const optionTuChoi = { mode: 'TU_CHOI', label: 'Từ chối báo cáo', icon: 'fa-circle-xmark', activeClass: 'border-slate-700 bg-slate-100 text-slate-900' };

            khungChonCheDo.innerHTML = [optionChinh, optionTuChoi].map(opt => {
                const isSelected = (opt.mode === mode);
                const cls = isSelected
                    ? `border-2 ${opt.activeClass} font-extrabold shadow-2xs`
                    : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 font-semibold';
                return `
                    <button type="button" data-mode="${opt.mode}" class="btn-switch-mode-xuly p-2.5 rounded-xl text-xs transition-all cursor-pointer flex items-center justify-center gap-1.5 ${cls}">
                        <i class="fa-solid ${opt.icon}"></i>
                        <span>${opt.label}</span>
                    </button>
                `;
            }).join('');

            khungChonCheDo.querySelectorAll('.btn-switch-mode-xuly').forEach(btn => {
                btn.addEventListener('click', () => thietLapCheDoXuLy(btn.getAttribute('data-mode'), item));
            });
        }

        capNhatTrangThaiNutXuLy();
    }

    function moModalXuLy(id, preferredMode = null) {
        const item = timBaoCaoTheoId(id);
        if (!item || !modalXuLy) return;

        if (nhomBaoCaoCungLoHienTai.length === 0 || !nhomBaoCaoCungLoHienTai.some(x => x.id == id)) {
            nhomBaoCaoCungLoHienTai = [item, ...(item.baoCaoLienQuan || [])];
        }

        baoCaoDangChonObj = item;
        baoCaoDangChonId = id;
        const laTrongVongDoi = (item.loaiBaoCao === 'TRONG_VONG_DOI' && item.loThuocId);
        const defaultMode = preferredMode || (laTrongVongDoi ? 'THU_HOI' : 'GHI_NHAN');

        const tieuDeModal = document.getElementById('tieuDeModalXuLyNghiVan');
        const nhanMaLo = document.getElementById('modalNghiVanNhanMaLo');

        document.getElementById('modalNghiVanMaBaoCao').textContent = item.maBaoCao || ('BC-' + item.id);

        if (defaultMode === 'THU_HOI_DOI_CHIEU' && item.loBiLamGia) {
            if (tieuDeModal) {
                tieuDeModal.innerHTML = `<i class="fa-solid fa-ban text-red-600"></i> Thu Hồi Lô Thuốc Đối Chiếu`;
            }
            if (nhanMaLo) nhanMaLo.textContent = 'Lô đối chiếu thu hồi:';
            document.getElementById('modalNghiVanMaLo').innerHTML = `<span class="text-red-700">${escapeHtml(item.loBiLamGia.maLoNghiepVu)} — ${escapeHtml(item.loBiLamGia.tenSanPham)}</span>`;
        } else {
            if (tieuDeModal) {
                tieuDeModal.innerHTML = `<i class="fa-solid fa-gavel text-indigo-700"></i> Quyết Định Xử Lý Báo Cáo Nghi Vấn`;
            }
            if (nhanMaLo) nhanMaLo.textContent = 'Mã lô khai báo:';
            document.getElementById('modalNghiVanMaLo').innerHTML = laTrongVongDoi
                ? escapeHtml(item.maLo || 'N/A')
                : `<span class="text-amber-800">${escapeHtml(item.maLo || 'N/A')}</span>`;
        }

        if (nhapLyDoXuLy) nhapLyDoXuLy.value = '';
        if (demKyTuNghiVan) demKyTuNghiVan.textContent = '0/500';

        thietLapCheDoXuLy(defaultMode, item);

        if (manHinhFormNghiVan) manHinhFormNghiVan.classList.remove('hidden');
        if (manHinhDangGhiNghiVan) manHinhDangGhiNghiVan.classList.add('hidden');
        if (manHinhKetQuaNghiVan) manHinhKetQuaNghiVan.classList.add('hidden');
        modalXuLy.classList.remove('hidden');
    }

    function capNhatTrangThaiNutXuLy() {
        if (!nhapLyDoXuLy || !nutXacNhanXuLy) return;
        const len = nhapLyDoXuLy.value.trim().length;
        if (demKyTuNghiVan) demKyTuNghiVan.textContent = `${len}/500`;

        let labelHtml = `<i class="fa-solid fa-ban"></i> Ban Hành Thu Hồi Lô`;
        let activeColor = 'bg-red-600 hover:bg-red-700';

        if (cheDoXuLyHienTai === 'GHI_NHAN') {
            labelHtml = `<i class="fa-solid fa-triangle-exclamation"></i> Ghi Nhận Hàng Giả`;
            activeColor = 'bg-amber-600 hover:bg-amber-700';
        } else if (cheDoXuLyHienTai === 'TU_CHOI') {
            labelHtml = `<i class="fa-solid fa-circle-xmark"></i> Xác Nhận Từ Chối`;
            activeColor = 'bg-slate-700 hover:bg-slate-800';
        }

        nutXacNhanXuLy.innerHTML = labelHtml;

        if (len >= 5) {
            nutXacNhanXuLy.disabled = false;
            nutXacNhanXuLy.className = `px-6 py-2 ${activeColor} text-white font-bold rounded-xl shadow-md transition-all cursor-pointer flex items-center gap-1.5`;
        } else {
            nutXacNhanXuLy.disabled = true;
            nutXacNhanXuLy.className = 'px-6 py-2 bg-slate-200 text-slate-400 font-bold rounded-xl transition-all cursor-not-allowed shadow-none flex items-center gap-1.5';
        }
    }

    nhapLyDoXuLy?.addEventListener('input', capNhatTrangThaiNutXuLy);

    document.querySelectorAll('.dong-modal-nghivan').forEach(btn => {
        btn.addEventListener('click', () => modalXuLy?.classList.add('hidden'));
    });

    nutXacNhanXuLy?.addEventListener('click', async function () {
        const lyDo = nhapLyDoXuLy ? nhapLyDoXuLy.value.trim() : '';
        if (!lyDo || !baoCaoDangChonId) return;

        const itemDangChon = baoCaoDangChonObj || timBaoCaoTheoId(baoCaoDangChonId);
        const mode = cheDoXuLyHienTai;
        const moTaLoading = document.getElementById('moTaDangXuLyNghiVan');

        if (manHinhFormNghiVan) manHinhFormNghiVan.classList.add('hidden');
        if (manHinhKetQuaNghiVan) manHinhKetQuaNghiVan.classList.add('hidden');
        if (manHinhDangGhiNghiVan) manHinhDangGhiNghiVan.classList.remove('hidden');

        const laThuHoi = (mode === 'THU_HOI' || mode === 'THU_HOI_DOI_CHIEU');
        if (moTaLoading) {
            moTaLoading.textContent = laThuHoi
                ? 'Đang phong tỏa tồn kho và đồng bộ quyết định Thu hồi lên Blockchain...'
                : 'Đang lưu kết quả thẩm định hồ sơ nghi vấn...';
        }

        try {
            let apiUrl = `/co-quan/xu-ly-nghi-van/thu-hoi/${baoCaoDangChonId}`;
            let payloadBody = { lyDo: lyDo, gopCungLo: true };

            if (mode === 'THU_HOI_DOI_CHIEU') {
                if (!itemDangChon?.loBiLamGia?.id) {
                    throw new Error('Không tìm thấy thông tin lô thuốc đối chiếu để thu hồi.');
                }
                apiUrl = '/co-quan/quan-ly-thu-hoi/thu-hoi';
                payloadBody = {
                    loThuocId: itemDangChon.loBiLamGia.id,
                    lyDo: lyDo
                };
            } else if (mode === 'GHI_NHAN') {
                apiUrl = `/co-quan/xu-ly-nghi-van/${baoCaoDangChonId}/ghi-nhan-hang-gia`;
            } else if (mode === 'TU_CHOI') {
                apiUrl = `/co-quan/xu-ly-nghi-van/${baoCaoDangChonId}/tu-choi`;
            }

            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: JSON.stringify(payloadBody)
            });

            const text = await response.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (err) {
                throw new Error(`Phản hồi từ máy chủ không hợp lệ (Mã ${response.status}).`);
            }

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Xử lý thất bại.');
            }

            if (!laThuHoi) {
                if (manHinhDangGhiNghiVan) manHinhDangGhiNghiVan.classList.add('hidden');
                if (manHinhKetQuaNghiVan) manHinhKetQuaNghiVan.classList.remove('hidden');

                if (mode === 'GHI_NHAN') {
                    iconKetQua.className = 'w-16 h-16 mx-auto rounded-full bg-amber-100 text-amber-700 flex items-center justify-center text-3xl';
                    iconKetQua.innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i>`;
                    tieuDeKetQua.className = 'text-base font-bold text-amber-900';
                    tieuDeKetQua.textContent = 'Đã Ghi Nhận Cảnh Báo Hàng Giả!';
                    noiDungKetQua.textContent = data.message || 'Đã ghi nhận cảnh báo hàng giả.';
                } else {
                    iconKetQua.className = 'w-16 h-16 mx-auto rounded-full bg-slate-100 text-slate-700 flex items-center justify-center text-3xl';
                    iconKetQua.innerHTML = `<i class="fa-solid fa-circle-xmark"></i>`;
                    tieuDeKetQua.className = 'text-base font-bold text-slate-800';
                    tieuDeKetQua.textContent = 'Đã Từ Chối Báo Cáo!';
                    noiDungKetQua.textContent = data.message || 'Hồ sơ báo cáo đã được đóng.';
                }

                loadDanhSachBaoCao();
                return;
            }

            const thuHoiId = data.data?.id;
            if (!thuHoiId) {
                if (manHinhDangGhiNghiVan) manHinhDangGhiNghiVan.classList.add('hidden');
                if (manHinhKetQuaNghiVan) manHinhKetQuaNghiVan.classList.remove('hidden');
                loadDanhSachBaoCao();
                return;
            }

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
                        if (manHinhDangGhiNghiVan) manHinhDangGhiNghiVan.classList.add('hidden');
                        if (manHinhKetQuaNghiVan) manHinhKetQuaNghiVan.classList.remove('hidden');

                        iconKetQua.className = 'w-16 h-16 mx-auto rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-3xl';
                        iconKetQua.innerHTML = `<i class="fa-solid fa-circle-check"></i>`;
                        tieuDeKetQua.className = 'text-base font-bold text-emerald-800';
                        tieuDeKetQua.textContent = 'Thu Hồi & Neo Blockchain Thành Công!';
                        noiDungKetQua.innerHTML = `Lô thuốc đã được thu hồi chính thức.<br><span class="font-mono text-[11px] text-cyan-800 font-bold break-all">TxHash: ${escapeHtml(statusData.txHash)}</span>`;

                        loadDanhSachBaoCao();
                    } else if (statusData.success && statusData.trangThai === 'FAILED') {
                        clearInterval(checkTimer);
                        if (manHinhDangGhiNghiVan) manHinhDangGhiNghiVan.classList.add('hidden');
                        if (manHinhKetQuaNghiVan) manHinhKetQuaNghiVan.classList.remove('hidden');

                        iconKetQua.className = 'w-16 h-16 mx-auto rounded-full bg-red-100 text-red-600 flex items-center justify-center text-3xl';
                        iconKetQua.innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i>`;
                        tieuDeKetQua.className = 'text-base font-bold text-red-700';
                        tieuDeKetQua.textContent = 'Giao Dịch Blockchain Thất Bại!';
                        noiDungKetQua.textContent = 'Hồ sơ đã lưu nhưng giao dịch ký On-chain bị lỗi. Vui lòng kiểm tra lại Queue Worker / Signer Service.';

                        loadDanhSachBaoCao();
                    } else if (numTries >= maxTries) {
                        clearInterval(checkTimer);
                        if (manHinhDangGhiNghiVan) manHinhDangGhiNghiVan.classList.add('hidden');
                        if (manHinhKetQuaNghiVan) manHinhKetQuaNghiVan.classList.remove('hidden');

                        iconKetQua.className = 'w-16 h-16 mx-auto rounded-full bg-amber-100 text-amber-600 flex items-center justify-center text-3xl';
                        iconKetQua.innerHTML = `<i class="fa-solid fa-clock"></i>`;
                        tieuDeKetQua.className = 'text-base font-bold text-amber-800';
                        tieuDeKetQua.textContent = 'Đã Ban Hành (Đang Chờ Đào Block)';
                        noiDungKetQua.textContent = 'Quyết định đã được đưa vào hàng chờ Queue. Tiến trình Blockchain vẫn đang được xử lý ngầm.';

                        loadDanhSachBaoCao();
                    }
                } catch (err) {
                    console.error('Lỗi khi polling trạng thái thu hồi:', err);
                }
            }, 2000);
        } catch (error) {
            if (manHinhDangGhiNghiVan) manHinhDangGhiNghiVan.classList.add('hidden');
            if (manHinhKetQuaNghiVan) manHinhKetQuaNghiVan.classList.remove('hidden');

            iconKetQua.className = 'w-16 h-16 mx-auto rounded-full bg-red-100 text-red-600 flex items-center justify-center text-3xl';
            iconKetQua.innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i>`;
            tieuDeKetQua.className = 'text-base font-bold text-red-700';
            tieuDeKetQua.textContent = 'Xử Lý Thất Bại!';
            noiDungKetQua.textContent = error.message;
        }
    });

    function ganSuKienNutBang() {
        document.querySelectorAll('.btn-xem-chi-tiet-nghi-van').forEach(btn => {
            btn.addEventListener('click', function () {
                moModalChiTiet(this.getAttribute('data-id'));
            });
        });

        document.querySelectorAll('.btn-mo-modal-xu-ly').forEach(btn => {
            btn.addEventListener('click', function () {
                moModalXuLy(this.getAttribute('data-id'));
            });
        });
    }

    oTimKiem?.addEventListener('input', () => { trangHienTai = 1; loadDanhSachBaoCao(); });
    locTrangThai?.addEventListener('change', () => { trangHienTai = 1; loadDanhSachBaoCao(); });
    locLoaiBaoCao?.addEventListener('change', () => { trangHienTai = 1; loadDanhSachBaoCao(); });

    loadDanhSachBaoCao();
});
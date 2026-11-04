document.addEventListener('DOMContentLoaded', function () {
    // Thay đổi từ PolygonScan sang Etherscan Sepolia
    const EXPLORER_BASE_URL = "https://sepolia.etherscan.io/tx/";
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    let trangHienTai = 1;
    let cachedDanhSachLo = [];
    let loDangXemDetail = null;
    let loDangThuHoiId = null;

    // Element DOM
    const thanBang = document.getElementById('thanBangThuHoi');
    const oTimKiem = document.getElementById('oTimKiemThuHoi');
    const locNSX = document.getElementById('locNhaSanXuat');
    const locTrangThai = document.getElementById('locTrangThaiLo');
    const soLuongBadge = document.getElementById('soLuongLoBadge');
    const thongTinPhanTrang = document.getElementById('thongTinPhanTrang');
    const cumNutPhanTrang = document.getElementById('cumNutPhanTrang');

    // Helper: Định dạng ngày
    function dinhDangNgay(ngayYMD) {
        if (!ngayYMD) return '--';
        const parts = ngayYMD.split('T')[0].split('-');
        if (parts.length === 3) return `${parts[2]}/${parts[1]}/${parts[0]}`;
        return ngayYMD;
    }

    // Helper: Tính số lượng đang lưu thông
    function tinhSoLuongDangLuuThong(lo) {
        if (!lo.phanBoKho) return 0;
        return lo.phanBoKho.reduce((sum, item) => sum + (item.giu || 0), 0);
    }

    // ══ 1. TẢI DỮ LIỆU TỪ BACKEND API ══
    async function loadDanhSachLo() {
        thanBang.innerHTML = `<tr><td colspan="8" class="text-center py-8 text-slate-400 italic"><i class="fa-solid fa-spinner fa-spin mr-2"></i>Đang tải dữ liệu từ máy chủ...</td></tr>`;

        const params = new URLSearchParams({
            page: trangHienTai,
            tuKhoa: oTimKiem.value.trim(),
            nhaSanXuatId: locNSX.value,
            trangThaiLo: locTrangThai.value
        });

        try {
            const response = await fetch(`/co-quan/quan-ly-thu-hoi/data?${params.toString()}`);
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
        const duLieu = apiResponse.data || [];
        const tongSo = apiResponse.total || duLieu.length;
        
        soLuongBadge.textContent = `Hiển thị: ${tongSo} lô`;
        thanBang.innerHTML = '';

        if (duLieu.length === 0) {
            thanBang.innerHTML = `<tr><td colspan="8" class="text-center py-8 text-slate-400 italic">Không tìm thấy lô thuốc nào phù hợp với bộ lọc.</td></tr>`;
            thongTinPhanTrang.textContent = "Không có bản ghi nào";
            cumNutPhanTrang.innerHTML = '';
            return;
        }

        thongTinPhanTrang.textContent = `Đang hiển thị ${apiResponse.from || 1} đến ${apiResponse.to || duLieu.length} trên tổng số ${tongSo} lô`;

        const homNay = new Date().toISOString().split('T')[0];

        duLieu.forEach(item => {
            const biThuHoi = (item.trangThaiLo === 'RECALLED');
            const daHetHan = (item.hanDung < homNay);
            const dangLuuThong = tinhSoLuongDangLuuThong(item);

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
                    <button type="button" class="btn-mo-modal-thu-hoi px-2.5 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 font-bold text-xs transition-all flex items-center gap-1 border border-red-200 shadow-2xs whitespace-nowrap" data-id="${item.id}">
                        <i class="fa-solid fa-ban text-[10px]"></i> Thu hồi
                    </button>
                `;
            }

            const tr = document.createElement('tr');
            tr.className = 'hover:bg-slate-50 transition-colors';
            tr.innerHTML = `
                <td class="py-3 px-3 font-mono font-bold text-slate-900 whitespace-nowrap">${item.maLo}</td>
                <td class="py-3 px-3 font-bold text-slate-900 leading-tight">${item.tenSanPham}</td>
                <td class="py-3 px-3 text-slate-800 font-semibold leading-tight">${item.nhaSanXuat}</td>
                <td class="py-3 px-2.5 text-center whitespace-nowrap font-mono text-xs">${dinhDangNgay(item.hanDung)}</td>
                <td class="py-3 px-3 text-right whitespace-nowrap">
                    <span class="font-mono font-bold text-indigo-900 block">${(item.tongSoLuong || 0).toLocaleString('vi-VN')} hộp</span>
                    <span class="text-[10px] text-slate-500 font-mono">(Lưu thông: ${dangLuuThong.toLocaleString('vi-VN')})</span>
                </td>
                <td class="py-3 px-2.5 text-center whitespace-nowrap">${badgeTrangThai}</td>
                <td class="py-3 px-2.5 text-center whitespace-nowrap">
                    <span class="font-mono font-bold ${item.soBaoCaoNghiVan > 0 ? 'text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200' : 'text-slate-400'}">
                        ${item.soBaoCaoNghiVan || 0} báo cáo
                    </span>
                </td>
                <td class="py-3 px-3 text-center whitespace-nowrap">
                    <div class="inline-flex items-center justify-center gap-1.5">
                        <button type="button" class="btn-xem-chi-tiet-lo px-2.5 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-800 font-bold text-xs transition-all flex items-center gap-1 border border-indigo-200 shadow-2xs whitespace-nowrap" data-id="${item.id}">
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
            btnPage.className = `w-7 h-7 rounded-lg text-xs font-bold transition-all ${trangHienTai === i ? 'bg-indigo-700 text-white shadow-2xs' : 'border border-slate-200 text-slate-700 hover:bg-slate-100'}`;
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

    function moModalChiTietLo(id) {
        loDangXemDetail = cachedDanhSachLo.find(x => x.id == id);
        if (!loDangXemDetail) return;

        const homNay = new Date().toISOString().split('T')[0];
        const biThuHoi = (loDangXemDetail.trangThaiLo === 'RECALLED');
        const daHetHan = (loDangXemDetail.hanDung < homNay);
        const dangLuuThong = tinhSoLuongDangLuuThong(loDangXemDetail);

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
        document.getElementById('detailDangLuuThong').textContent = `${dangLuuThong.toLocaleString('vi-VN')} hộp`;
        document.getElementById('detailNgaySanXuat').textContent = dinhDangNgay(loDangXemDetail.ngaySanXuat);

        document.getElementById('detailSoBaoCaoNghiVan').textContent = `${loDangXemDetail.soBaoCaoNghiVan || 0} báo cáo`;
        document.getElementById('detailLinkSangNghiVan').href = `/co-quan/xu-ly-nghi-van?tuKhoa=${encodeURIComponent(loDangXemDetail.maLo)}`;

        if (biThuHoi && loDangXemDetail.thongTinThuHoi) {
            detailBannerDaThuHoi.classList.remove('hidden');
            detailBannerGoiYThuHoiHetHan.classList.add('hidden');
            document.getElementById('detailLyDoThuHoiText').textContent = loDangXemDetail.thongTinThuHoi.lyDo;
            document.getElementById('detailNgayThuHoiText').textContent = loDangXemDetail.thongTinThuHoi.ngayThuHoi;
            document.getElementById('detailDongBoText').textContent = loDangXemDetail.thongTinThuHoi.trangThaiDongBo;
            
            const hash = loDangXemDetail.thongTinThuHoi.hashBlockchain;
            if (hash) {
                document.getElementById('detailTxHashThuHoiText').textContent = hash;
                document.getElementById('detailTxHashThuHoiLink').href = `${EXPLORER_BASE_URL}${hash}`;
            } else {
                document.getElementById('detailTxHashThuHoiText').textContent = 'Đang đồng bộ...';
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
                <button type="button" id="nutMoThuHoiTuDetail" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-xl transition-all flex items-center gap-1.5 shadow-md">
                    <i class="fa-solid fa-ban text-[10px]"></i> Ban hành quyết định thu hồi
                </button>
            `;
            document.getElementById('nutMoThuHoiTuDetail').addEventListener('click', () => {
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
                <td class="p-2 font-bold text-slate-800 text-xs">${pb.donVi}</td>
                <td class="p-2 text-center font-sans">
                    <span class="badge-chuan px-2 py-0.2 rounded text-[10px] font-bold bg-slate-100 text-slate-700">${pb.vaiTro}</span>
                </td>
                <td class="p-2 text-right font-bold text-indigo-900">${(pb.giu || 0).toLocaleString('vi-VN')} hộp</td>
                <td class="p-2 text-right text-slate-600">${(pb.daBan || 0).toLocaleString('vi-VN')} hộp</td>
            `;
            thanBangPhanBo.appendChild(tr);
        });

        modalDetail.classList.remove('hidden');
    }

    document.querySelectorAll('.dong-modal-detail').forEach(btn => {
        btn.addEventListener('click', () => modalDetail.classList.add('hidden'));
    });

    // ══ 4. MODAL BAN HÀNH THU HỒI LÔ THUỐC (API CALL) ══
    const modalThuHoi = document.getElementById('modalThuHoiLoHang');
    const manHinhFormThuHoi = document.getElementById('manHinhFormThuHoi');
    const manHinhDangGhiBlockchain = document.getElementById('manHinhDangGhiBlockchain');
    const manHinhLoiBlockchain = document.getElementById('manHinhLoiBlockchain');
    const nhapLyDoThuHoi = document.getElementById('nhapLyDoThuHoiLo');
    const demKyTuThuHoi = document.getElementById('demKyTuThuHoi');
    const nutXacNhanThuHoi = document.getElementById('nutXacNhanThuHoiBlockchain');

    function moModalThuHoi(id) {
        const lo = cachedDanhSachLo.find(x => x.id == id);
        if (!lo) return;

        loDangThuHoiId = id;
        document.getElementById('thuHoiModalMaLo').textContent = lo.maLo;
        document.getElementById('thuHoiModalTenThuoc').textContent = lo.tenSanPham;
        document.getElementById('thuHoiModalNSX').textContent = lo.nhaSanXuat;

        const containerBenGiu = document.getElementById('thuHoiModalDanhSachBenGiu');
        containerBenGiu.innerHTML = '';
        (lo.phanBoKho || []).forEach(pb => {
            const div = document.createElement('div');
            div.className = 'flex justify-between items-center py-0.5';
            div.innerHTML = `
                <span class="text-slate-700 font-sans truncate mr-2">• ${pb.donVi}:</span>
                <span class="font-bold text-slate-900 shrink-0">${(pb.giu || 0).toLocaleString('vi-VN')} hộp</span>
            `;
            containerBenGiu.appendChild(div);
        });

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

        if (len >= 5) {
            nutXacNhanThuHoi.disabled = false;
            nutXacNhanThuHoi.className = "px-6 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl shadow-md transition-all cursor-pointer";
        } else {
            nutXacNhanThuHoi.disabled = true;
            nutXacNhanThuHoi.className = "px-6 py-2 bg-slate-200 text-slate-400 font-bold rounded-xl transition-all cursor-not-allowed shadow-none";
        }
    }

    nhapLyDoThuHoi.addEventListener('input', capNhatTrangThaiNutThuHoi);

    document.querySelectorAll('.dong-modal-thuhoi').forEach(btn => {
        btn.addEventListener('click', () => modalThuHoi.classList.add('hidden'));
    });

    nutXacNhanThuHoi.addEventListener('click', async function () {
        const lyDo = nhapLyDoThuHoi.value.trim();
        if (!lyDo || !loDangThuHoiId) return;

        manHinhFormThuHoi.classList.add('hidden');
        manHinhDangGhiBlockchain.classList.remove('hidden');

        try {
            const response = await fetch('/co-quan/quan-ly-thu-hoi/thu-hoi', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: JSON.stringify({
                    loThuocId: loDangThuHoiId,
                    lyDo: lyDo
                })
            });

            const data = await response.json();

            if (data.success) {
                modalThuHoi.classList.add('hidden');
                alert(data.message || 'Đã ban hành quyết định thu hồi thành công.');
                loadDanhSachLo();
            } else {
                throw new Error(data.message || 'Xử lý thất bại');
            }
        } catch (error) {
            manHinhDangGhiBlockchain.classList.add('hidden');
            document.getElementById('noiDungLoiThuHoiText').textContent = error.message;
            manHinhLoiBlockchain.classList.remove('hidden');
        }
    });

    // ══ 5. GẮN SỰ KIỆN VÀ LẮNG NGHE LỌC TÌM KIẾM ══
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

    oTimKiem.addEventListener('input', () => { trangHienTai = 1; loadDanhSachLo(); });
    locNSX.addEventListener('change', () => { trangHienTai = 1; loadDanhSachLo(); });
    locTrangThai.addEventListener('change', () => { trangHienTai = 1; loadDanhSachLo(); });

    loadDanhSachLo();
});
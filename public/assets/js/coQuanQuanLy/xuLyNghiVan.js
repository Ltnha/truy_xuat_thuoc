document.addEventListener('DOMContentLoaded', function () {
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    let trangHienTai = 1;
    let cachedDanhSachBaoCao = [];
    let baoCaoDangXuLy = null;

    // Element DOM
    const thanBang = document.getElementById('thanBangNghiVan');
    const oTimKiem = document.getElementById('oTimKiemNghiVan');
    const locTrangThai = document.getElementById('locTrangThaiBaoCao');
    const locNguon = document.getElementById('locNguonBaoCao');
    const soLuongBadge = document.getElementById('soLuongBaoCaoBadge');
    const thongTinPhanTrang = document.getElementById('thongTinPhanTrang');
    const cumNutPhanTrang = document.getElementById('cumNutPhanTrang');

    const urlParams = new URLSearchParams(window.location.search);
    const tuKhoaParam = urlParams.get('tuKhoa') || urlParams.get('maLo');
    if (tuKhoaParam) {
        oTimKiem.value = tuKhoaParam;
    }

    function taoBadgeTrangThai(item) {
        if (item.trangThai === 'DA_XU_LY') {
            const kq = item.ket_qua_xu_ly || item.ketQuaXuLy;
            const quyetDinh = kq ? kq.quyetDinh : null;
            if (quyetDinh === 'THU_HOI') {
                return `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-red-100 text-red-700 border border-red-300"><i class="fa-solid fa-ban"></i> Đã thu hồi</span>`;
            } else if (quyetDinh === 'GHI_NHAN') {
                return `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-100 text-indigo-800 border border-indigo-300"><i class="fa-solid fa-shield-cat"></i> Đã ghi nhận</span>`;
            } else {
                return `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-slate-100 text-slate-700 border border-slate-300"><i class="fa-solid fa-circle-xmark"></i> Đã từ chối</span>`;
            }
        } else if (item.trangThai === 'DANG_XU_LY') {
            return `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300"><i class="fa-solid fa-clock"></i> Đang xử lý</span>`;
        } else {
            return `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-cyan-100 text-cyan-800 border border-cyan-300"><i class="fa-regular fa-paper-plane"></i> Đã gửi</span>`;
        }
    }

    // ══ 1. TẢI DANH SÁCH BÁO CÁO TỪ BACKEND API ══
    async function loadDanhSachNghiVan() {
        thanBang.innerHTML = `<tr><td colspan="6" class="text-center py-8 text-slate-400 italic"><i class="fa-solid fa-spinner fa-spin mr-2"></i>Đang tải dữ liệu từ máy chủ...</td></tr>`;

        const params = new URLSearchParams({
            page: trangHienTai,
            tuKhoa: oTimKiem.value.trim(),
            trangThai: locTrangThai.value,
            nguonLoai: locNguon.value
        });

        try {
            const response = await fetch(`/co-quan/xu-ly-nghi-van/data?${params.toString()}`);
            if (!response.ok) throw new Error('Không thể kết nối đến máy chủ');

            const data = await response.json();
            cachedDanhSachBaoCao = data.data || [];

            renderBang(data);
        } catch (error) {
            console.error(error);
            thanBang.innerHTML = `<tr><td colspan="6" class="text-center py-8 text-red-500 italic">Có lỗi xảy ra khi tải dữ liệu báo cáo. Vui lòng thử lại!</td></tr>`;
        }
    }

    // ══ 2. RENDER BẢNG BÁO CÁO ══
    function renderBang(apiResponse) {
        const duLieu = apiResponse.data || [];
        const tongSo = apiResponse.total || duLieu.length;

        soLuongBadge.textContent = `Hiển thị: ${tongSo} báo cáo`;
        thanBang.innerHTML = '';

        if (duLieu.length === 0) {
            thanBang.innerHTML = `<tr><td colspan="6" class="text-center py-8 text-slate-400 italic">Không tìm thấy báo cáo nghi vấn nào phù hợp.</td></tr>`;
            thongTinPhanTrang.textContent = "Không có bản ghi nào";
            cumNutPhanTrang.innerHTML = '';
            return;
        }

        thongTinPhanTrang.textContent = `Đang hiển thị ${apiResponse.from || 1} đến ${apiResponse.to || duLieu.length} trên tổng số ${tongSo} báo cáo`;

        duLieu.forEach(item => {
            const daXuLy = (item.trangThai === 'DA_XU_LY');
            const tenNguoiGui = item.tenNguoiGui || (item.nguoi_gui ? item.nguoi_gui.hoTen : 'Người tiêu dùng');
            const ngayGuiStr = item.ngayGui ? item.ngayGui.split(' ')[0] : (item.created_at ? item.created_at.split('T')[0] : '--');

            const tr = document.createElement('tr');
            tr.className = 'hover:bg-slate-50 transition-colors';
            tr.innerHTML = `
                <td class="py-3 px-3 font-mono font-bold text-cyan-900 whitespace-nowrap">${item.maBaoCao}</td>
                <td class="py-3 px-3 font-mono font-bold text-slate-900 whitespace-nowrap">${item.maLoKhaiBao}</td>
                <td class="py-3 px-3 font-bold text-slate-800">${tenNguoiGui}</td>
                <td class="py-3 px-2.5 text-center font-mono text-[11px] text-slate-600 whitespace-nowrap">${ngayGuiStr}</td>
                <td class="py-3 px-2.5 text-center whitespace-nowrap">${taoBadgeTrangThai(item)}</td>
                <td class="py-3 px-3 text-center whitespace-nowrap">
                    <button type="button" class="btn-mo-panel-xuly px-3 py-1.5 rounded-lg text-xs font-bold transition-all ${daXuLy ? 'bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300' : 'bg-indigo-700 hover:bg-indigo-800 text-white'}" data-id="${item.id}">
                        ${daXuLy ? '<i class="fa-solid fa-eye text-indigo-700"></i> Xem' : '<i class="fa-solid fa-gavel"></i> Xử lý'}
                    </button>
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
                loadDanhSachNghiVan(); 
            });
            cumNutPhanTrang.appendChild(btnPage);
        }
    }

    // ══ 3. PANEL CHI TIẾT & XỬ LÝ BÁO CÁO ══
    const modalXuLy = document.getElementById('modalXuLyBaoCao');
    const panelTheKetQuaDaXuLy = document.getElementById('panelTheKetQuaDaXuLy');
    const panelCumNutQuyetDinh = document.getElementById('panelCumNutQuyetDinh');
    const nutMoModalThuHoiTuBaoCao = document.getElementById('nutMoModalThuHoiTuBaoCao');
    const nutMoModalGhiNhanHangGia = document.getElementById('nutMoModalGhiNhanHangGia');

    function moPanelXuLyBaoCao(id) {
        baoCaoDangXuLy = cachedDanhSachBaoCao.find(x => x.id == id);
        if (!baoCaoDangXuLy) return;

        document.getElementById('panelMaBaoCaoTitle').textContent = baoCaoDangXuLy.maBaoCao;
        document.getElementById('panelTrangThaiBadge').innerHTML = taoBadgeTrangThai(baoCaoDangXuLy);
        
        const tenNguoiGui = baoCaoDangXuLy.tenNguoiGui || (baoCaoDangXuLy.nguoi_gui ? baoCaoDangXuLy.nguoi_gui.hoTen : 'Người tiêu dùng');
        document.getElementById('panelNguoiGuiText').textContent = tenNguoiGui;
        document.getElementById('panelThoiDiemGui').textContent = baoCaoDangXuLy.ngayGui || baoCaoDangXuLy.created_at || '--';
        document.getElementById('panelMaLoText').textContent = baoCaoDangXuLy.maLoKhaiBao;
        document.getElementById('panelLyDoNghiVan').textContent = baoCaoDangXuLy.lyDo || 'N/A';
        document.getElementById('panelMoTaChiTiet').textContent = baoCaoDangXuLy.moTa || 'N/A';

        // Hiển thị tài liệu / minh chứng
        const mcContainer = document.getElementById('panelMinhChungContainer');
        const mcList = document.getElementById('panelDanhSachMinhChung');
        if (mcContainer && mcList) {
            mcList.innerHTML = '';
            const minhChungs = baoCaoDangXuLy.minhChungs || [];
            if (minhChungs.length > 0) {
                mcContainer.classList.remove('hidden');
                minhChungs.forEach(mc => {
                    const a = document.createElement('a');
                    a.href = mc.url;
                    a.target = '_blank';
                    a.className = 'flex items-center gap-1.5 p-2 bg-white rounded-lg border border-slate-200 hover:border-indigo-500 text-cyan-800 text-[11px] font-medium truncate';
                    a.innerHTML = `<i class="fa-solid ${mc.loai === 'pdf' ? 'fa-file-pdf text-red-500' : 'fa-image text-emerald-600'}"></i> <span class="truncate">${mc.ten}</span>`;
                    mcList.appendChild(a);
                });
            } else {
                mcContainer.classList.add('hidden');
            }
        }

        const daXuLy = (baoCaoDangXuLy.trangThai === 'DA_XU_LY');
        const kq = baoCaoDangXuLy.ket_qua_xu_ly || baoCaoDangXuLy.ketQuaXuLy;

        if (daXuLy && kq) {
            panelTheKetQuaDaXuLy.classList.remove('hidden');
            panelCumNutQuyetDinh.classList.add('hidden');

            let innerHtml = `
                <div class="flex items-center justify-between">
                    <span class="font-bold text-slate-800">QUYẾT ĐỊNH XỬ LÝ: ${kq.quyetDinh}</span>
                    <span class="text-[11px] text-slate-500">${kq.ngayXuLy || ''}</span>
                </div>
                <div class="text-[11px] text-slate-700 mt-1"><strong>Nội dung / Lý do:</strong> ${kq.lyDo}</div>
            `;
            panelTheKetQuaDaXuLy.innerHTML = innerHtml;
        } else {
            panelTheKetQuaDaXuLy.classList.add('hidden');
            panelCumNutQuyetDinh.classList.remove('hidden');

            // Nếu lô tồn tại trên hệ thống thì cho phép thu hồi, ngược lại cho phép ghi nhận hàng giả ngoại vi
            if (baoCaoDangXuLy.loThuocId) {
                nutMoModalThuHoiTuBaoCao?.classList.remove('hidden');
                nutMoModalGhiNhanHangGia?.classList.add('hidden');
            } else {
                nutMoModalThuHoiTuBaoCao?.classList.add('hidden');
                nutMoModalGhiNhanHangGia?.classList.remove('hidden');
            }
        }

        modalXuLy.classList.remove('hidden');
    }

    document.querySelectorAll('.dong-modal-xuly').forEach(btn => {
        btn.addEventListener('click', () => modalXuLy.classList.add('hidden'));
    });

    // ══ 4. XỬ LÝ THU HỒI TỪ BÁO CÁO (API POST) ══
    const modalThuHoiBC = document.getElementById('modalXacNhanThuHoiTuBaoCao');
    const nhapLyDoThuHoiBC = document.getElementById('nhapLyDoThuHoiTuBC');
    const nutXacNhanKyThuHoi = document.getElementById('nutXacNhanKyThuHoiOnChain');

    nutMoModalThuHoiTuBaoCao?.addEventListener('click', function () {
        if (!baoCaoDangXuLy) return;
        nhapLyDoThuHoiBC.value = `Thu hồi lô [${baoCaoDangXuLy.maLoKhaiBao}] căn cứ báo cáo nghi vấn ${baoCaoDangXuLy.maBaoCao}`;
        modalThuHoiBC.classList.remove('hidden');
    });

    document.querySelectorAll('.dong-modal-thuhoi-bc').forEach(btn => {
        btn.addEventListener('click', () => modalThuHoiBC.classList.add('hidden'));
    });

    nutXacNhanKyThuHoi?.addEventListener('click', async function () {
        const lyDo = nhapLyDoThuHoiBC.value.trim();
        if (!lyDo || !baoCaoDangXuLy) return;

        try {
            const response = await fetch(`/co-quan/xu-ly-nghi-van/${baoCaoDangXuLy.id}/thu-hoi-tu-bao-cao`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: JSON.stringify({ lyDo: lyDo, gopCungLo: true })
            });

            const data = await response.json();
            if (data.success) {
                modalThuHoiBC.classList.add('hidden');
                modalXuLy.classList.add('hidden');
                alert(data.message || 'Đã ban hành quyết định thu hồi lô thành công.');
                loadDanhSachNghiVan();
            } else {
                alert(data.message || 'Xử lý thất bại');
            }
        } catch (error) {
            alert('Có lỗi xảy ra khi kết nối máy chủ.');
        }
    });

    // ══ 5. XỬ LÝ TỪ CHỐI BÁO CÁO (API POST) ══
    const modalTuChoiBC = document.getElementById('modalXacNhanTuChoiBaoCao');
    const nhapLyDoTuChoiBC = document.getElementById('nhapLyDoTuChoiBC');
    const nutXacNhanTuChoiBC = document.getElementById('nutXacNhanTuChoiBCOffChain');

    document.getElementById('nutMoModalTuChoiBaoCao')?.addEventListener('click', function () {
        if (!baoCaoDangXuLy) return;
        nhapLyDoTuChoiBC.value = '';
        modalTuChoiBC.classList.remove('hidden');
    });

    document.querySelectorAll('.dong-modal-tuchoi-bc').forEach(btn => {
        btn.addEventListener('click', () => modalTuChoiBC.classList.add('hidden'));
    });

    nutXacNhanTuChoiBC?.addEventListener('click', async function () {
        const lyDo = nhapLyDoTuChoiBC.value.trim();
        if (!lyDo || !baoCaoDangXuLy) return;

        try {
            const response = await fetch(`/co-quan/xu-ly-nghi-van/${baoCaoDangXuLy.id}/tu-choi`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: JSON.stringify({ lyDo: lyDo })
            });

            const data = await response.json();
            if (data.success) {
                modalTuChoiBC.classList.add('hidden');
                modalXuLy.classList.add('hidden');
                alert(data.message || 'Đã từ chối báo cáo thành công.');
                loadDanhSachNghiVan();
            } else {
                alert(data.message || 'Xử lý thất bại');
            }
        } catch (error) {
            alert('Có lỗi xảy ra khi gửi yêu cầu.');
        }
    });

    // ══ 6. XỬ LÝ GHI NHẬN HÀNG GIẢ NGOẠI VI (API POST) ══
    const modalGhiNhanBC = document.getElementById('modalXacNhanGhiNhanHangGia');
    const nhapGhiChuHangGia = document.getElementById('nhapGhiChuHangGia');
    const nutXacNhanGhiNhanHangGia = document.getElementById('nutXacNhanGhiNhanHangGia');

    nutMoModalGhiNhanHangGia?.addEventListener('click', function () {
        if (!baoCaoDangXuLy) return;
        nhapGhiChuHangGia.value = `Ghi nhận hồ sơ hàng giả ngoại vi cho mã lô khai báo [${baoCaoDangXuLy.maLoKhaiBao}]`;
        modalGhiNhanBC.classList.remove('hidden');
    });

    document.querySelectorAll('.dong-modal-ghinhan-bc').forEach(btn => {
        btn.addEventListener('click', () => modalGhiNhanBC.classList.add('hidden'));
    });

    nutXacNhanGhiNhanHangGia?.addEventListener('click', async function () {
        const ghiChu = nhapGhiChuHangGia.value.trim();
        if (!ghiChu || !baoCaoDangXuLy) return;

        try {
            const response = await fetch(`/co-quan/xu-ly-nghi-van/${baoCaoDangXuLy.id}/ghi-nhan-hang-gia`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: JSON.stringify({ ghiChu: ghiChu })
            });

            const data = await response.json();
            if (data.success) {
                modalGhiNhanBC.classList.add('hidden');
                modalXuLy.classList.add('hidden');
                alert(data.message || 'Đã ghi nhận hồ sơ xử lý hàng giả ngoại vi thành công.');
                loadDanhSachNghiVan();
            } else {
                alert(data.message || 'Xử lý thất bại');
            }
        } catch (error) {
            alert('Có lỗi xảy ra khi gửi yêu cầu.');
        }
    });

    // ══ 7. GẮN SỰ KIỆN NÚT TRÊN BẢNG VÀ FILTER ══
    function ganSuKienNutBang() {
        document.querySelectorAll('.btn-mo-panel-xuly').forEach(btn => {
            btn.addEventListener('click', function () {
                moPanelXuLyBaoCao(this.getAttribute('data-id'));
            });
        });
    }

    oTimKiem.addEventListener('input', () => { trangHienTai = 1; loadDanhSachNghiVan(); });
    locTrangThai.addEventListener('change', () => { trangHienTai = 1; loadDanhSachNghiVan(); });
    locNguon.addEventListener('change', () => { trangHienTai = 1; loadDanhSachNghiVan(); });

    loadDanhSachNghiVan();
});

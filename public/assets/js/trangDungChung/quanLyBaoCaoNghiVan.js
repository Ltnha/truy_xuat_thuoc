document.addEventListener('DOMContentLoaded', function () {
    const ITEMS_PER_PAGE = 8;
    let trangHienTai = 1;

    const danhSachBaoCao = window.danhSachBaoCaoGoc || [];
    const danhSachLoThuoc = window.danhSachLoThuocGoc || [];

    const thanBang = document.getElementById('thanBangBaoCao');
    const oTimKiem = document.getElementById('oTimKiemBaoCao');
    const locLoai = document.getElementById('locLoaiBaoCao');
    const locTrangThai = document.getElementById('locTrangThaiBaoCao');
    const soLuongBadge = document.getElementById('soLuongBaoCaoBadge');
    const thongTinPhanTrang = document.getElementById('thongTinPhanTrang');
    const cumNutPhanTrang = document.getElementById('cumNutPhanTrang');

    const LY_DO_THEO_TAB = {
        TRONG_VONG_DOI: [
            'Cảnh báo chất lượng hậu kiểm / mẫu lưu biến đổi chất lượng',
            'Lô thuốc hư hỏng bao bì, sai điều kiện bảo quản trong vận chuyển',
            'Sai lệch số lượng thực tế hoặc chứng từ chuyển giao lô',
            'Tem nhãn hoặc mã QR trên lô bị mờ, rách, lỗi kỹ thuật',
            'Phản hồi bất thường về chất lượng sử dụng từ lô thuốc',
            'Sự cố bất thường khác trên lô thuộc vòng đời đơn vị',
        ],
        NGOAI_HE_THONG: [
            'Phát hiện lô thuốc giả mạo thương hiệu / in nhái bao bì',
            'Mã lô lạ không tồn tại trong hệ thống chuỗi cung ứng',
            'Sao chép trái phép mã QR hoặc số lô của đơn vị',
            'Bao bì có dấu hiệu in đè nhãn mác, tẩy xóa hạn sử dụng',
            'Cảnh báo hàng giả mạo khác lưu hành trên thị trường',
        ],
    };

    function escapeHtml(str) {
        if (str == null) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function showToast(message, type = 'error', title = 'Thông báo') {
        if (window.PharmaPopup?.show) {
            window.PharmaPopup.show({ type, title, message });
        } else {
            alert(message);
        }
    }

    function taoBadgeLoaiBaoCao(loai) {
        if (loai === 'NGOAI_HE_THONG') {
            return `<span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200 whitespace-nowrap">Ngoài hệ thống</span>`;
        }
        return `<span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 whitespace-nowrap">Trong vòng đời</span>`;
    }

    function taoBadgeTrangThai(item) {
        if (item.trangThai === 'DA_XU_LY') {
            if (item.ketQuaLoai === 'THU_HOI') {
                return `<span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-red-100 text-red-700 border border-red-300"><i class="fa-solid fa-ban mr-1"></i>Đã xử lý - Thu hồi lô</span>`;
            } else if (item.ketQuaLoai === 'GHI_NHAN') {
                return `<span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300"><i class="fa-solid fa-triangle-exclamation mr-1"></i>Đã ghi nhận hàng giả</span>`;
            } else {
                return `<span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-slate-100 text-slate-700 border border-slate-300"><i class="fa-solid fa-circle-xmark mr-1"></i>Đã xử lý - Từ chối</span>`;
            }
        } else if (item.trangThai === 'DANG_XU_LY') {
            return `<span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300"><i class="fa-solid fa-clock mr-1"></i>Đang xử lý</span>`;
        } else {
            return `<span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-cyan-50 text-cyan-800 border border-cyan-200"><i class="fa-regular fa-paper-plane mr-1"></i>Đã gửi</span>`;
        }
    }

    function renderBangBaoCao() {
        const tuKhoa = oTimKiem ? oTimKiem.value.trim().toLowerCase() : '';
        const loaiLoc = locLoai ? locLoai.value : 'TAT_CA';
        const trangThaiLoc = locTrangThai ? locTrangThai.value : 'TAT_CA';

        const danhSachLoc = danhSachBaoCao.filter(item => {
            const matchTuKhoa = (item.maBaoCao || '').toLowerCase().includes(tuKhoa) ||
                (item.maLo || '').toLowerCase().includes(tuKhoa) ||
                (item.tenSanPham || '').toLowerCase().includes(tuKhoa) ||
                (item.lyDo || '').toLowerCase().includes(tuKhoa);
            const matchLoai = (loaiLoc === 'TAT_CA') || (item.loaiBaoCao === loaiLoc);
            const matchTrangThai = (trangThaiLoc === 'TAT_CA') || (item.trangThai === trangThaiLoc);
            return matchTuKhoa && matchLoai && matchTrangThai;
        });

        if (soLuongBadge) soLuongBadge.textContent = `Hiển thị: ${danhSachLoc.length} báo cáo`;

        const tongSoTrang = Math.ceil(danhSachLoc.length / ITEMS_PER_PAGE) || 1;
        if (trangHienTai > tongSoTrang) trangHienTai = tongSoTrang;

        const batDau = (trangHienTai - 1) * ITEMS_PER_PAGE;
        const ketThuc = batDau + ITEMS_PER_PAGE;
        const duLieuTrang = danhSachLoc.slice(batDau, ketThuc);

        if (!thanBang) return;
        thanBang.innerHTML = '';

        if (duLieuTrang.length === 0) {
            thanBang.innerHTML = `<tr><td colspan="7" class="text-center py-8 text-slate-400 italic">Không tìm thấy báo cáo nghi vấn nào.</td></tr>`;
            if (thongTinPhanTrang) thongTinPhanTrang.textContent = 'Không có bản ghi nào';
            if (cumNutPhanTrang) cumNutPhanTrang.innerHTML = '';
            return;
        }

        if (thongTinPhanTrang) {
            thongTinPhanTrang.textContent = `Đang hiển thị ${batDau + 1} đến ${Math.min(ketThuc, danhSachLoc.length)} trên tổng số ${danhSachLoc.length} báo cáo`;
        }

        duLieuTrang.forEach(item => {
            const moTaPhu = item.loaiBaoCao === 'NGOAI_HE_THONG'
                ? (item.loBiLamGia ? `Đối chiếu lô gốc: ${item.loBiLamGia.maLoNghiepVu}` : 'Lô phát hiện ngoài hệ thống')
                : `${item.tenSanPham || ''}${item.quyMoLo ? ' • ' + item.quyMoLo : ''}`;

            const tr = document.createElement('tr');
            tr.className = 'hover:bg-slate-50 border-b border-slate-100 transition-colors cursor-pointer';
            tr.innerHTML = `
                <td class="py-3 px-4 font-mono font-bold text-cyan-900 whitespace-nowrap">${escapeHtml(item.maBaoCao)}</td>
                <td class="py-3 px-4 whitespace-nowrap">${taoBadgeLoaiBaoCao(item.loaiBaoCao)}</td>
                <td class="py-3 px-4">
                    <div class="font-mono font-bold text-slate-900 whitespace-nowrap">${escapeHtml(item.maLo)}</div>
                    <div class="text-[11px] text-slate-500">${escapeHtml(moTaPhu)}</div>
                </td>
                <td class="py-3 px-4 font-semibold text-slate-800 leading-tight">${escapeHtml(item.lyDo)}</td>
                <td class="py-3 px-4 text-center text-slate-600 font-mono text-[11px] whitespace-nowrap">${escapeHtml(item.ngayGui)}</td>
                <td class="py-3 px-4 text-center whitespace-nowrap">${taoBadgeTrangThai(item)}</td>
                <td class="py-3 px-4 text-center whitespace-nowrap">
                    <button type="button" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-lg transition-all border border-slate-300 cursor-pointer">
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
        if (!cumNutPhanTrang) return;
        cumNutPhanTrang.innerHTML = '';
        if (tongSoTrang <= 1) return;

        for (let i = 1; i <= tongSoTrang; i++) {
            const btnPage = document.createElement('button');
            btnPage.type = 'button';
            btnPage.className = `w-7 h-7 rounded-lg text-xs font-bold transition-all ${trangHienTai === i ? 'bg-emerald-700 text-white shadow-xs' : 'border border-slate-200 text-slate-700 hover:bg-slate-100'}`;
            btnPage.textContent = i;
            btnPage.addEventListener('click', () => { trangHienTai = i; renderBangBaoCao(); });
            cumNutPhanTrang.appendChild(btnPage);
        }
    }

    // ================== CHUYỂN TAB & ĐỐI SOÁT THÔNG TIN LÔ ==================
    const tabBtnTrongVongDoi = document.getElementById('tabBtnTrongVongDoi');
    const tabBtnNgoaiHeThong = document.getElementById('tabBtnNgoaiHeThong');
    const panelTrongVongDoi = document.getElementById('panelTrongVongDoi');
    const panelNgoaiHeThong = document.getElementById('panelNgoaiHeThong');
    const inputLoaiBaoCao = document.getElementById('inputLoaiBaoCao');
    const selectLoTrongVongDoi = document.getElementById('selectLoTrongVongDoi');
    const inputMaLoNgoaiHeThong = document.getElementById('inputMaLoNgoaiHeThong');
    const selectLoBiLamGia = document.getElementById('selectLoBiLamGia');
    const selectLyDo = document.getElementById('nhapLyDo');
    const khungThongTinLoTab1 = document.getElementById('khungThongTinLoTab1');
    const khungThongTinLoTab2 = document.getElementById('khungThongTinLoTab2');

    function renderTheThongTinLo(container, loId, laLoDoiChieu = false) {
        if (!container) return;
        if (!loId) {
            container.classList.add('hidden');
            container.innerHTML = '';
            return;
        }

        const loKhop = danhSachLoThuoc.find(l => Number(l.id) === Number(loId));
        if (!loKhop) {
            container.classList.add('hidden');
            return;
        }

        const tieuDe = laLoDoiChieu
            ? `Lô gốc đối chiếu: ${escapeHtml(loKhop.tenSanPham)} (SĐK: ${escapeHtml(loKhop.soDangKy)})`
            : `${escapeHtml(loKhop.tenSanPham)} (SĐK: ${escapeHtml(loKhop.soDangKy)})`;

        container.innerHTML = `
            <div class="font-bold ${laLoDoiChieu ? 'text-amber-900' : 'text-emerald-900'}">${tieuDe}</div>
            <div class="grid grid-cols-2 gap-x-3 gap-y-1 text-[11px] text-slate-700">
                <div><span class="text-slate-500">Nhà sản xuất:</span> <strong>${escapeHtml(loKhop.nhaSanXuat)}</strong></div>
                <div><span class="text-slate-500">Quy mô lô:</span> <strong class="font-mono">${Number(loKhop.soLuong || 0).toLocaleString('vi-VN')} ${escapeHtml(loKhop.donViTinh || 'Hộp')}</strong></div>
                <div><span class="text-slate-500">Ngày SX:</span> <span class="font-mono">${escapeHtml(loKhop.ngaySanXuat)}</span></div>
                <div><span class="text-slate-500">Hạn dùng:</span> <span class="font-mono font-semibold">${escapeHtml(loKhop.hanSuDung)}</span></div>
            </div>
        `;
        container.classList.remove('hidden');
    }

    function capNhatDanhSachLyDo(loai) {
        if (!selectLyDo) return;
        const list = LY_DO_THEO_TAB[loai] || [];
        selectLyDo.innerHTML = `<option value="">-- Chọn nhóm lý do nghi vấn --</option>` +
            list.map(lyDo => `<option value="${escapeHtml(lyDo)}">${escapeHtml(lyDo)}</option>`).join('');
    }

    function chonTab(loai) {
        if (inputLoaiBaoCao) inputLoaiBaoCao.value = loai;
        capNhatDanhSachLyDo(loai);

        if (loai === 'TRONG_VONG_DOI') {
            tabBtnTrongVongDoi.className = 'py-2 px-3 rounded-lg bg-white text-emerald-800 shadow-xs transition-all cursor-pointer flex items-center justify-center gap-1.5';
            tabBtnNgoaiHeThong.className = 'py-2 px-3 rounded-lg text-slate-600 hover:text-slate-900 transition-all cursor-pointer flex items-center justify-center gap-1.5';
            panelTrongVongDoi?.classList.remove('hidden');
            panelNgoaiHeThong?.classList.add('hidden');

            if (selectLoTrongVongDoi) selectLoTrongVongDoi.required = true;
            if (inputMaLoNgoaiHeThong) inputMaLoNgoaiHeThong.required = false;
        } else {
            tabBtnNgoaiHeThong.className = 'py-2 px-3 rounded-lg bg-white text-amber-800 shadow-xs transition-all cursor-pointer flex items-center justify-center gap-1.5';
            tabBtnTrongVongDoi.className = 'py-2 px-3 rounded-lg text-slate-600 hover:text-slate-900 transition-all cursor-pointer flex items-center justify-center gap-1.5';
            panelNgoaiHeThong?.classList.remove('hidden');
            panelTrongVongDoi?.classList.add('hidden');

            if (selectLoTrongVongDoi) selectLoTrongVongDoi.required = false;
            if (inputMaLoNgoaiHeThong) inputMaLoNgoaiHeThong.required = true;
        }
    }

    tabBtnTrongVongDoi?.addEventListener('click', () => chonTab('TRONG_VONG_DOI'));
    tabBtnNgoaiHeThong?.addEventListener('click', () => chonTab('NGOAI_HE_THONG'));
    selectLoTrongVongDoi?.addEventListener('change', function () {
        renderTheThongTinLo(khungThongTinLoTab1, this.value, false);
    });
    selectLoBiLamGia?.addEventListener('change', function () {
        renderTheThongTinLo(khungThongTinLoTab2, this.value, true);
    });

    // ================== ĐÍNH KÈM MINH CHỨNG ==================
    let tepDaChonArr = [];
    const khungChonTep = document.getElementById('khungChonTepMinhChung');
    const tepInput = document.getElementById('tepMinhChungInput');
    const danhSachTepContainer = document.getElementById('danhSachTepDaChon');
    const soLuongTepLabel = document.getElementById('soLuongTepDaChon');

    if (khungChonTep && tepInput) {
        khungChonTep.addEventListener('click', () => tepInput.click());
        tepInput.addEventListener('change', function () {
            const files = Array.from(this.files);
            for (const f of files) {
                if (tepDaChonArr.length >= 5) {
                    showToast('Chỉ được đính kèm tối đa 5 tệp bằng chứng!', 'warning', 'Giới hạn tệp');
                    break;
                }
                if (f.size > 10 * 1024 * 1024) {
                    showToast(`Tệp "${f.name}" vượt quá dung lượng tối đa 10MB!`, 'warning', 'Tệp quá lớn');
                    continue;
                }
                tepDaChonArr.push(f);
            }
            tepInput.value = '';
            renderDanhSachTepChon();
        });
    }

    function renderDanhSachTepChon() {
        if (!danhSachTepContainer) return;
        danhSachTepContainer.innerHTML = '';
        if (soLuongTepLabel) soLuongTepLabel.textContent = `${tepDaChonArr.length}/5 tệp`;

        tepDaChonArr.forEach((file, index) => {
            const div = document.createElement('div');
            div.className = 'flex items-center justify-between p-2 bg-slate-50 border border-slate-200 rounded-lg text-xs';
            div.innerHTML = `
                <span class="font-medium text-slate-800 truncate">${escapeHtml(file.name)}</span>
                <button type="button" class="text-slate-400 hover:text-red-600 text-xs p-1 cursor-pointer">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            `;
            div.querySelector('button').addEventListener('click', (e) => {
                e.stopPropagation();
                tepDaChonArr.splice(index, 1);
                renderDanhSachTepChon();
            });
            danhSachTepContainer.appendChild(div);
        });
    }

    // ================== MODAL GỬI BÁO CÁO MỚI ==================
    const modalGui = document.getElementById('modalGuiBaoCaoMoi');
    const formGui = document.getElementById('formSubmitBaoCao');
    const btnSubmitForm = document.getElementById('btnSubmitFormAuth');

    document.getElementById('nutMoModalGuiBaoCao')?.addEventListener('click', () => {
        if (formGui) formGui.reset();
        tepDaChonArr = [];
        renderDanhSachTepChon();
        renderTheThongTinLo(khungThongTinLoTab1, '');
        renderTheThongTinLo(khungThongTinLoTab2, '');
        chonTab('TRONG_VONG_DOI');
        if (modalGui) modalGui.classList.remove('hidden');
    });

    document.querySelectorAll('.dong-modal-gui').forEach(btn => {
        btn.addEventListener('click', () => modalGui?.classList.add('hidden'));
    });

    if (formGui) {
        formGui.addEventListener('submit', function (e) {
            e.preventDefault();

            if (tepDaChonArr.length === 0) {
                showToast('Vui lòng đính kèm ít nhất 1 tệp bằng chứng (JPG, PNG, PDF).', 'warning', 'Thiếu bằng chứng');
                return;
            }

            if (btnSubmitForm) {
                btnSubmitForm.disabled = true;
                btnSubmitForm.innerHTML = `<i class="fa-solid fa-spinner fa-spin mr-1"></i> Đang gửi...`;
            }

            const formData = new FormData(formGui);
            tepDaChonArr.forEach(f => formData.append('minhChung[]', f));

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            fetch('/api/quan-ly-bao-cao-nghi-van/gui-bao-cao', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
            .then(async res => {
                const data = await res.json();
                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Gửi báo cáo thất bại!');
                }
                modalGui?.classList.add('hidden');
                location.reload();
            })
            .catch(err => {
                if (btnSubmitForm) {
                    btnSubmitForm.disabled = false;
                    btnSubmitForm.innerHTML = `<i class="fa-solid fa-paper-plane mr-1"></i> Gửi Báo Cáo`;
                }
                showToast(err.message || 'Đã xảy ra lỗi kết nối hệ thống!', 'error', 'Không thể gửi báo cáo');
            });
        });
    }

    // ================== MODAL CHI TIẾT BÁO CÁO ==================
    const modalChiTiet = document.getElementById('modalChiTietBaoCao');

    function moChiTietBaoCao(item) {
        document.getElementById('chiTietMaBaoCao').textContent = item.maBaoCao || '';
        document.getElementById('chiTietMaLo').textContent = item.maLo || '';
        document.getElementById('chiTietNgayGui').textContent = item.ngayGui || '';
        document.getElementById('chiTietLyDo').textContent = item.lyDo || '';
        document.getElementById('chiTietMoTa').textContent = item.moTa || 'Không có mô tả chi tiết.';
        document.getElementById('chiTietLoaiBadge').innerHTML = taoBadgeLoaiBaoCao(item.loaiBaoCao);
        document.getElementById('chiTietTrangThaiBadge').innerHTML = taoBadgeTrangThai(item);

        const elSpLo = document.getElementById('chiTietSanPhamLo');
        if (elSpLo) {
            if (item.loaiBaoCao === 'NGOAI_HE_THONG') {
                elSpLo.textContent = item.loBiLamGia
                    ? `Đối chiếu lô gốc của đơn vị: ${item.loBiLamGia.maLoNghiepVu} — ${item.loBiLamGia.tenSanPham} (HSD: ${item.loBiLamGia.hanSuDung})`
                    : 'Mã lô phát hiện ngoài hệ thống (không gắn lô gốc đối chiếu)';
            } else {
                elSpLo.textContent = [
                    item.tenSanPham || 'Chưa xác định',
                    item.quyMoLo ? `Quy mô: ${item.quyMoLo}` : null,
                    item.hanSuDung ? `HSD: ${item.hanSuDung}` : null
                ].filter(Boolean).join(' • ');
            }
        }

        const khungAnh = document.getElementById('chiTietDanhSachMinhChung');
        khungAnh.innerHTML = '';
        if (item.danhSachMinhChung && item.danhSachMinhChung.length > 0) {
            item.danhSachMinhChung.forEach(mc => {
                const card = document.createElement('div');
                card.className = 'p-2 bg-slate-50 border border-slate-200 rounded-lg flex items-center justify-between text-xs';
                card.innerHTML = `
                    <span class="font-semibold text-slate-800 truncate" title="${escapeHtml(mc.ten)}">${escapeHtml(mc.ten)}</span>
                    <a href="${escapeHtml(mc.url)}" target="_blank" class="text-cyan-800 font-bold text-[11px] hover:underline shrink-0 ml-2">Xem tệp</a>
                `;
                khungAnh.appendChild(card);
            });
        } else {
            khungAnh.innerHTML = `<span class="text-slate-400 italic text-xs col-span-2">Không có tệp bằng chứng.</span>`;
        }

        const noiDungKetQuaXuLy = document.getElementById('noiDungKetQuaXuLy');
        if (item.trangThai === 'DA_XU_LY' && item.ketQuaThucTe) {
            if (item.ketQuaLoai === 'THU_HOI') {
                const txHtml = item.txHashThuHoi
                    ? `<div class="pt-1.5 border-t border-red-200/70 flex items-center justify-between gap-2">
                           <span class="font-mono text-[11px] truncate text-red-800">TX: ${escapeHtml(item.txHashThuHoi)}</span>
                           <a href="https://sepolia.etherscan.io/tx/${encodeURIComponent(item.txHashThuHoi)}" target="_blank" class="font-bold text-red-700 underline shrink-0">Sepolia Etherscan</a>
                       </div>`
                    : '';
                noiDungKetQuaXuLy.className = 'p-3 rounded-xl border border-red-200 bg-red-50 text-red-900 space-y-1.5 text-xs';
                noiDungKetQuaXuLy.innerHTML = `
                    <div class="font-bold text-red-700"><i class="fa-solid fa-ban mr-1"></i>QUYẾT ĐỊNH: THU HỒI TOÀN BỘ LÔ THUỐC</div>
                    <p>${escapeHtml(item.ketQuaThucTe.lyDoXuLy || '')}</p>
                    ${txHtml}
                `;
            } else if (item.ketQuaLoai === 'GHI_NHAN') {
                noiDungKetQuaXuLy.className = 'p-3 rounded-xl border border-amber-200 bg-amber-50 text-amber-900 space-y-1 text-xs';
                noiDungKetQuaXuLy.innerHTML = `
                    <div class="font-bold text-amber-800"><i class="fa-solid fa-triangle-exclamation mr-1"></i>QUYẾT ĐỊNH: GHI NHẬN CẢNH BÁO LÔ GIẢ MẠO</div>
                    <p>${escapeHtml(item.ketQuaThucTe.lyDoXuLy || '')}</p>
                `;
            } else {
                noiDungKetQuaXuLy.className = 'p-3 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 space-y-1 text-xs';
                noiDungKetQuaXuLy.innerHTML = `
                    <div class="font-bold text-slate-700"><i class="fa-solid fa-circle-xmark mr-1"></i>QUYẾT ĐỊNH: TỪ CHỐI BÁO CÁO</div>
                    <p>${escapeHtml(item.ketQuaThucTe.lyDoXuLy || '')}</p>
                `;
            }
        } else {
            noiDungKetQuaXuLy.className = 'p-3 rounded-xl border border-amber-200 bg-amber-50 text-amber-800 text-xs';
            noiDungKetQuaXuLy.innerHTML = `<i class="fa-solid fa-hourglass-half mr-1"></i> Báo cáo đang được Cơ quan quản lý tra soát dữ liệu trên hệ thống và blockchain.`;
        }

        if (modalChiTiet) modalChiTiet.classList.remove('hidden');
    }

    document.getElementById('nutDongChiTiet')?.addEventListener('click', () => modalChiTiet?.classList.add('hidden'));
    document.getElementById('nutDongChiTietBottom')?.addEventListener('click', () => modalChiTiet?.classList.add('hidden'));

    if (oTimKiem) oTimKiem.addEventListener('input', () => { trangHienTai = 1; renderBangBaoCao(); });
    if (locLoai) locLoai.addEventListener('change', () => { trangHienTai = 1; renderBangBaoCao(); });
    if (locTrangThai) locTrangThai.addEventListener('change', () => { trangHienTai = 1; renderBangBaoCao(); });

    chonTab('TRONG_VONG_DOI');
    renderBangBaoCao();
});
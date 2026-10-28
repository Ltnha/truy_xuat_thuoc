document.addEventListener('DOMContentLoaded', function () {
    const ITEMS_PER_PAGE = 5;
    let trangHienTai = 1;

    let danhSachBaoCao = window.danhSachBaoCaoGoc || [];

    const thanBang = document.getElementById('thanBangBaoCao');
    const oTimKiem = document.getElementById('oTimKiemBaoCao');
    const locTrangThai = document.getElementById('locTrangThaiBaoCao');
    const soLuongBadge = document.getElementById('soLuongBaoCaoBadge');
    const thongTinPhanTrang = document.getElementById('thongTinPhanTrang');
    const cumNutPhanTrang = document.getElementById('cumNutPhanTrang');

    function taoBadgeTrangThai(item) {
        if (item.trangThai === 'DA_XU_LY') {
            if (item.ketQuaLoai === 'THU_HOI') {
                return `<span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-red-100 text-red-700 border border-red-300"><i class="fa-solid fa-ban"></i> Đã xử lý - Thu hồi</span>`;
            } else {
                return `<span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-slate-100 text-slate-700 border border-slate-300"><i class="fa-solid fa-circle-xmark"></i> Đã xử lý - Từ chối</span>`;
            }
        } else if (item.trangThai === 'DANG_XU_LY') {
            return `<span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300"><i class="fa-solid fa-clock"></i> Đang xử lý</span>`;
        } else {
            return `<span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-cyan-50 text-cyan-800 border border-cyan-200"><i class="fa-regular fa-paper-plane mr-1"></i>Đã gửi</span>`;
        }
    }

    function renderBangBaoCao() {
        const tuKhoa = oTimKiem ? oTimKiem.value.trim().toLowerCase() : '';
        const trangThaiLoc = locTrangThai ? locTrangThai.value : 'TAT_CA';

        const danhSachLoc = danhSachBaoCao.filter(item => {
            const matchTuKhoa = item.maBaoCao.toLowerCase().includes(tuKhoa) || 
                               item.maLo.toLowerCase().includes(tuKhoa) ||
                               item.lyDo.toLowerCase().includes(tuKhoa);
            const matchTrangThai = (trangThaiLoc === 'TAT_CA') || (item.trangThai === trangThaiLoc);
            return matchTuKhoa && matchTrangThai;
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
            thanBang.innerHTML = `<tr><td colspan="6" class="text-center py-8 text-slate-400 italic">Không tìm thấy báo cáo nghi vấn nào.</td></tr>`;
            if (thongTinPhanTrang) thongTinPhanTrang.textContent = "Không có bản ghi nào";
            if (cumNutPhanTrang) cumNutPhanTrang.innerHTML = '';
            return;
        }

        if (thongTinPhanTrang) {
            thongTinPhanTrang.textContent = `Đang hiển thị ${batDau + 1} đến ${Math.min(ketThuc, danhSachLoc.length)} trên tổng số ${danhSachLoc.length} báo cáo`;
        }

        duLieuTrang.forEach(item => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-slate-50 border-b border-slate-100 transition-colors cursor-pointer';
            tr.innerHTML = `
                <td class="py-3 px-4 font-mono font-bold text-cyan-900 whitespace-nowrap">${item.maBaoCao}</td>
                <td class="py-3 px-4 font-mono font-bold text-slate-900 whitespace-nowrap">${item.maLo}</td>
                <td class="py-3 px-4 font-semibold text-slate-800 leading-tight">${item.lyDo}</td>
                <td class="py-3 px-4 text-center text-slate-600 font-mono text-[11px] whitespace-nowrap">${item.ngayGui}</td>
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
            btnPage.className = `w-7 h-7 rounded-lg text-xs font-bold transition-all ${trangHienTai === i ? 'bg-emerald-700 text-white shadow-xs' : 'border border-slate-200 text-slate-700 hover:bg-slate-100'}`;
            btnPage.textContent = i;
            btnPage.addEventListener('click', () => { trangHienTai = i; renderBangBaoCao(); });
            cumNutPhanTrang.appendChild(btnPage);
        }
    }

    let tepDaChonArr = [];
    const khungChonTep = document.getElementById('khungChonTepMinhChung');
    const tepInput = document.getElementById('tepMinhChungInput');
    const danhSachTepContainer = document.getElementById('danhSachTepDaChon');
    const soLuongTepLabel = document.getElementById('soLuongTepDaChon');

    if (khungChonTep && tepInput) {
        khungChonTep.addEventListener('click', () => tepInput.click());
        tepInput.addEventListener('change', function () {
            const files = Array.from(this.files);
            for (let f of files) {
                if (tepDaChonArr.length >= 5) {
                    alert("Chỉ được đính kèm tối đa 5 tệp bằng chứng!");
                    break;
                }
                if (f.size > 10 * 1024 * 1024) {
                    alert(`Tệp "${f.name}" vượt quá 10MB!`);
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
                <span class="font-medium text-slate-800 truncate">${file.name}</span>
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

    const oNhapMaLo = document.getElementById('nhapMaLoLienQuan');
    const menuGoiY = document.getElementById('menuGoiYMaLo');
    const nutMoMenuLo = document.getElementById('nutMoMenuLo');
    const iconMuiTenLo = document.getElementById('iconMuiTenLo');
    const cacMucGoiY = document.querySelectorAll('.item-lo');

    function batMenuLo() {
        if (menuGoiY) menuGoiY.classList.remove('hidden');
        if (iconMuiTenLo) iconMuiTenLo.className = "fa-solid fa-chevron-up";
    }

    function tatMenuLo() {
        if (menuGoiY) menuGoiY.classList.add('hidden');
        if (iconMuiTenLo) iconMuiTenLo.className = "fa-solid fa-chevron-down";
    }

    if (oNhapMaLo) {
        oNhapMaLo.addEventListener('focus', batMenuLo);
        oNhapMaLo.addEventListener('click', batMenuLo);
    }
    if (nutMoMenuLo) {
        nutMoMenuLo.addEventListener('click', (e) => {
            e.stopPropagation();
            menuGoiY.classList.contains('hidden') ? batMenuLo() : tatMenuLo();
        });
    }

    cacMucGoiY.forEach(item => {
        item.addEventListener('click', function () {
            if (oNhapMaLo) oNhapMaLo.value = this.getAttribute('data-malo');
            tatMenuLo();
        });
    });

    document.addEventListener('click', (e) => {
        const khung = document.getElementById('khungChonMaLo');
        if (khung && !khung.contains(e.target)) tatMenuLo();
    });

    const modalGui = document.getElementById('modalGuiBaoCaoMoi');
    const formGui = document.getElementById('formSubmitBaoCao');
    const btnSubmitForm = document.getElementById('btnSubmitFormAuth');

    document.getElementById('nutMoModalGuiBaoCao')?.addEventListener('click', () => {
        if (formGui) formGui.reset();
        tepDaChonArr = [];
        renderDanhSachTepChon();
        tatMenuLo();
        if (modalGui) modalGui.classList.remove('hidden');
    });

    document.querySelectorAll('.dong-modal-gui').forEach(btn => {
        btn.addEventListener('click', () => modalGui?.classList.add('hidden'));
    });

    // SUBMIT FORM BÁO CÁO MỚI (TỰ ĐỘNG TẢI LẠI TRANG KHI GỬI THÀNH CÔNG, KHÔNG HIỆN POPUP)
    if (formGui) {
        formGui.addEventListener('submit', function (e) {
            e.preventDefault();

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
                    'X-CSRF-TOKEN': csrfToken
                }
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    modalGui?.classList.add('hidden');
                    location.reload();
                } else {
                    if (btnSubmitForm) {
                        btnSubmitForm.disabled = false;
                        btnSubmitForm.innerHTML = `<i class="fa-solid fa-paper-plane mr-1"></i> Gửi Báo Cáo`;
                    }
                    alert(res.message || "Gửi báo cáo thất bại!");
                }
            })
            .catch(() => {
                if (btnSubmitForm) {
                    btnSubmitForm.disabled = false;
                    btnSubmitForm.innerHTML = `<i class="fa-solid fa-paper-plane mr-1"></i> Gửi Báo Cáo`;
                }
                alert("Đã xảy ra lỗi kết nối hệ thống!");
            });
        });
    }

    const modalChiTiet = document.getElementById('modalChiTietBaoCao');

    function moChiTietBaoCao(item) {
        document.getElementById('chiTietMaBaoCao').textContent = item.maBaoCao;
        document.getElementById('chiTietMaLo').textContent = item.maLo;
        document.getElementById('chiTietNgayGui').textContent = item.ngayGui;
        document.getElementById('chiTietLyDo').textContent = item.lyDo;
        document.getElementById('chiTietMoTa').textContent = item.moTa || 'Không có mô tả chi tiết.';
        document.getElementById('chiTietTrangThaiBadge').innerHTML = taoBadgeTrangThai(item);

        const khungAnh = document.getElementById('chiTietDanhSachMinhChung');
        khungAnh.innerHTML = '';
        if (item.danhSachMinhChung && item.danhSachMinhChung.length > 0) {
            item.danhSachMinhChung.forEach(mc => {
                const card = document.createElement('div');
                card.className = 'p-2 bg-slate-50 border border-slate-200 rounded-lg flex items-center justify-between text-xs';
                card.innerHTML = `
                    <span class="font-semibold text-slate-800 truncate" title="${mc.ten}">${mc.ten}</span>
                    <a href="${mc.url}" target="_blank" class="text-cyan-800 font-bold text-[11px] hover:underline">Xem tệp</a>
                `;
                khungAnh.appendChild(card);
            });
        } else {
            khungAnh.innerHTML = `<span class="text-slate-400 italic text-xs col-span-2">Không có tệp bằng chứng.</span>`;
        }

        const noiDungKetQuaXuLy = document.getElementById('noiDungKetQuaXuLy');
        if (item.trangThai === 'DA_XU_LY' && item.ketQuaThucTe) {
            if (item.ketQuaLoai === 'THU_HOI') {
                noiDungKetQuaXuLy.className = "p-3 rounded-xl border border-red-200 bg-red-50 text-red-900 space-y-1 text-xs";
                noiDungKetQuaXuLy.innerHTML = `
                    <div class="font-bold text-red-700"><i class="fa-solid fa-ban"></i> QUYẾT ĐỊNH: THU HỒI LÔ THUỐC</div>
                    <p>${item.ketQuaThucTe.lyDoXuLy}</p>
                `;
            } else {
                noiDungKetQuaXuLy.className = "p-3 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 space-y-1 text-xs";
                noiDungKetQuaXuLy.innerHTML = `
                    <div class="font-bold text-slate-700"><i class="fa-solid fa-circle-xmark"></i> QUYẾT ĐỊNH: TỪ CHỐI BÁO CÁO</div>
                    <p>${item.ketQuaThucTe.lyDoXuLy}</p>
                `;
            }
        } else {
            noiDungKetQuaXuLy.className = "p-3 rounded-xl border border-amber-200 bg-amber-50 text-amber-800 text-xs";
            noiDungKetQuaXuLy.innerHTML = `<i class="fa-solid fa-hourglass-half mr-1"></i> Báo cáo đang được Cơ quan quản lý tra soát dữ liệu lô trên blockchain.`;
        }

        if (modalChiTiet) modalChiTiet.classList.remove('hidden');
    }

    document.getElementById('nutDongChiTiet')?.addEventListener('click', () => modalChiTiet?.classList.add('hidden'));
    document.getElementById('nutDongChiTietBottom')?.addEventListener('click', () => modalChiTiet?.classList.add('hidden'));

    if (oTimKiem) oTimKiem.addEventListener('input', () => { trangHienTai = 1; renderBangBaoCao(); });
    if (locTrangThai) locTrangThai.addEventListener('change', () => { trangHienTai = 1; renderBangBaoCao(); });

    renderBangBaoCao();
});
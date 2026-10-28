document.addEventListener('DOMContentLoaded', function () {
    const STORAGE_KEY = 'pharma_lich_su_bao_cao';

    const tabGui = document.getElementById('tabGuiBaoCao');
    const tabLichSu = document.getElementById('tabLichSuBaoCao');
    const khungGui = document.getElementById('khungTabGuiBaoCao');
    const khungLichSu = document.getElementById('khungTabLichSuBaoCao');

    const formGui = document.getElementById('formGuiBaoCao');
    const inputTep = document.getElementById('tepMinhChung');
    const txtTepLabel = document.getElementById('tenTepMinhChung');
    const khungKeoTha = document.getElementById('khungKeoThaMinhChung');

    const oNhapMaLoPublic = document.getElementById('maLoBaoCao');
    const menuGoiYPublic = document.getElementById('menuGoiYMaLoPublic');
    const nutMoMenuPublic = document.getElementById('nutMoMenuLoPublic');
    const iconMuiTenPublic = document.getElementById('iconMuiTenLoPublic');
    const itemLoPublicElements = document.querySelectorAll('.item-lo-public');

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

    function batTabGui() {
        tabGui.className = "flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all bg-emerald-700 text-white shadow-xs cursor-pointer";
        tabLichSu.className = "flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all text-slate-600 hover:text-emerald-700 hover:bg-emerald-50 cursor-pointer";
        khungGui.classList.remove('hidden');
        khungLichSu.classList.add('hidden');
    }

    function batTabLichSu() {
        tabLichSu.className = "flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all bg-emerald-700 text-white shadow-xs cursor-pointer";
        tabGui.className = "flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all text-slate-600 hover:text-emerald-700 hover:bg-emerald-50 cursor-pointer";
        khungLichSu.classList.remove('hidden');
        khungGui.classList.add('hidden');
        renderBangLichSu();
    }

    if (tabGui) tabGui.addEventListener('click', batTabGui);
    if (tabLichSu) tabLichSu.addEventListener('click', batTabLichSu);

    function batMenuLoPublic() {
        if (menuGoiYPublic) menuGoiYPublic.classList.remove('hidden');
        if (iconMuiTenPublic) iconMuiTenPublic.className = "fa-solid fa-chevron-up";
    }

    function tatMenuLoPublic() {
        if (menuGoiYPublic) menuGoiYPublic.classList.add('hidden');
        if (iconMuiTenPublic) iconMuiTenPublic.className = "fa-solid fa-chevron-down";
    }

    if (oNhapMaLoPublic) {
        oNhapMaLoPublic.addEventListener('focus', batMenuLoPublic);
        oNhapMaLoPublic.addEventListener('click', batMenuLoPublic);
        oNhapMaLoPublic.addEventListener('input', function () {
            const keyword = this.value.trim().toLowerCase();
            batMenuLoPublic();
            itemLoPublicElements.forEach(item => {
                const maLoText = item.getAttribute('data-malo').toLowerCase();
                item.style.display = maLoText.includes(keyword) ? 'flex' : 'none';
            });
        });
    }

    itemLoPublicElements.forEach(item => {
        item.addEventListener('click', function () {
            if (oNhapMaLoPublic) oNhapMaLoPublic.value = this.getAttribute('data-malo');
            tatMenuLoPublic();
        });
    });

    if (nutMoMenuPublic) {
        nutMoMenuPublic.addEventListener('click', (e) => {
            e.stopPropagation();
            menuGoiYPublic.classList.contains('hidden') ? batMenuLoPublic() : tatMenuLoPublic();
        });
    }

    document.addEventListener('click', (e) => {
        const khung = document.getElementById('khungChonMaLoPublic');
        if (khung && !khung.contains(e.target)) tatMenuLoPublic();
    });

    if (khungKeoTha) khungKeoTha.addEventListener('click', () => inputTep.click());
    if (inputTep) {
        inputTep.addEventListener('change', function () {
            if (this.files && this.files.length > 0) {
                txtTepLabel.innerHTML = `<i class="fa-solid fa-file-circle-check text-emerald-700"></i> ${this.files[0].name} (${(this.files[0].size / 1024 / 1024).toFixed(2)} MB)`;
            }
        });
    }

    // GỬI BÁO CÁO CÔNG KHAI
    if (formGui) {
        formGui.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(formGui);
            const submitBtn = document.getElementById('nutGuiBaoCaoSubmit');
            submitBtn.disabled = true;
            submitBtn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Đang gửi báo cáo...`;

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            fetch('/api/bao-cao-nghi-van/gui-bao-cao', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
            .then(res => res.json())
            .then(res => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = `<i class="fa-solid fa-paper-plane"></i> Gửi Báo Cáo Nghi Vấn`;

                if (res.success) {
                    const data = res.data;
                    maBaoCaoSinhRa.textContent = data.maBaoCao;
                    
                    luuVaoLichSuKhongTrungLap({
                        maBaoCao: data.maBaoCao,
                        maLo: data.maLo,
                        ngayGui: data.ngayGui,
                        trangThai: 'DA_GUI'
                    });

                    modalThanhCong.classList.remove('hidden');
                    formGui.reset();
                    txtTepLabel.innerHTML = `<i class="fa-solid fa-cloud-arrow-up text-base text-emerald-700"></i> <span>Nhấp để chọn tệp bằng chứng (JPG, PNG, PDF)</span>`;
                } else {
                    alert(res.message || "Không thể gửi báo cáo!");
                }
            })
            .catch(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = `<i class="fa-solid fa-paper-plane"></i> Gửi Báo Cáo Nghi Vấn`;
                alert("Đã xảy ra lỗi kết nối máy chủ!");
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

    // ĐỊNH DẠNG XUẤT FILE PHIẾU LƯU MÃ CHUẨN ĐÃ THAY =============== BẰNG ---------------
    if (btnTaiTxt) {
        btnTaiTxt.addEventListener('click', function () {
            const code = maBaoCaoSinhRa.textContent;
            const maLo = oNhapMaLoPublic.value.trim() || 'Khai báo công khai';
            const noiDung =
`--------------------------------------------------
     PHARMACHAIN - PHIẾU LƯU MÃ BÁO CÁO NGHI VẤN
--------------------------------------------------
- Mã Tra Cứu  : 
${code}
- Mã Lô Thuốc : 
${maLo}
- Ngày Gửi    : 
${new Date().toLocaleString('vi-VN')}
--------------------------------------------------
HƯỚNG DẪN TRA CỨU TIẾN TRÌNH XỬ LÝ:
1. Truy cập: 
${window.location.origin}/bao-cao-nghi-van
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

    // TRA CỨU 1 MÃ TỪ FILE .TXT ĐƠN LẺ (NHẬP TỪ FILE .TXT Ở KHUNG TÌM KIẾM)
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
                    alert("Không tìm thấy Mã Báo Cáo hợp lệ trong file .txt này!");
                }
                inputUploadTxt.value = '';
            };
            reader.readAsText(file);
        });
    }

    // NHẬP TOÀN BỘ LỊCH SỬ TỪ FILE SAO LƯU .TXT (NÚT NHẬP LỊCH SỬ MỚI BỔ SUNG)
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
                            fetch(`/api/bao-cao-nghi-van/tra-cuu/${code}`)
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
                            alert(`Đã nhập thành công ${addedCount} báo cáo vào lịch sử!`);
                        } else {
                            alert("Không tìm thấy hồ sơ báo cáo hợp lệ từ các mã trong file!");
                        }
                    });

                } else {
                    alert("Không tìm thấy Mã Báo Cáo hợp lệ nào trong file này!");
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
                alert("Vui lòng nhập mã báo cáo cần kiểm tra!");
                return;
            }
            thucHienTraCuu(code);
        });
    }

    function thucHienTraCuu(maBaoCao) {
        fetch(`/api/bao-cao-nghi-van/tra-cuu/${maBaoCao}`)
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
                    alert(res.message);
                }
            })
            .catch(() => alert("Lỗi hệ thống khi tra cứu!"));
    }

    // TỰ ĐỘNG CHỐNG TRÙNG LẶP & ĐẨY MÃ MỚI NHẤT LÊN ĐẦU
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
                <td class="py-3 px-4 font-mono font-bold text-cyan-800">${item.maBaoCao}</td>
                <td class="py-3 px-4 font-mono font-bold text-slate-800">${item.maLo}</td>
                <td class="py-3 px-4">${badgeTrangThai}</td>
                <td class="py-3 px-4 text-slate-500 font-mono text-[11px]">${item.ngayGui}</td>
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
            if (confirm('Bạn có chắc chắn muốn xóa toàn bộ lịch sử báo cáo không?')) {
                localStorage.removeItem(STORAGE_KEY);
                duLieuLuuTru = [];
                renderBangLichSu();
            }
        });
    }

    // XUẤT TOÀN BỘ LỊCH SỬ DÙNG ĐẦU PHÂN CÁCH DẠNG ------------------
    if (nutXuatLichSu) {
        nutXuatLichSu.addEventListener('click', function () {
            if (duLieuLuuTru.length === 0) {
                alert("Lịch sử báo cáo đang trống, không có dữ liệu để xuất!");
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

    function moModalChiTiet(data) {
        document.getElementById('chiTietMaBaoCao').textContent = data.maBaoCao;
        document.getElementById('chiTietMaLo').textContent = data.maLo;
        document.getElementById('chiTietTenThuoc').textContent = data.tenThuoc;
        document.getElementById('chiTietNgayGui').textContent = data.ngayGui;
        document.getElementById('chiTietLyDo').textContent = data.lyDo + (data.moTa ? `\n\nMô tả chi tiết: ${data.moTa}` : '');

        let badgeHtml = `<span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-cyan-50 text-cyan-800 border border-cyan-200">Đã gửi</span>`;
        if (data.trangThai === 'DANG_XU_LY') {
            badgeHtml = `<span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300">Đang xác minh</span>`;
        } else if (data.trangThai === 'DA_XU_LY') {
            badgeHtml = `<span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">Đã thẩm định</span>`;
        }
        document.getElementById('chiTietTrangThaiBadge').innerHTML = badgeHtml;

        const khungAnh = document.getElementById('chiTietAnhMinhChung');
        khungAnh.innerHTML = '';
        if (data.danhSachMinhChung && data.danhSachMinhChung.length > 0) {
            data.danhSachMinhChung.forEach(mc => {
                const item = document.createElement('div');
                item.className = 'p-2 bg-slate-50 border border-slate-200 rounded-lg flex items-center justify-between text-xs';
                item.innerHTML = `
                    <span class="font-medium text-slate-800 truncate">${mc.ten}</span>
                    <a href="${mc.url}" target="_blank" class="text-cyan-800 hover:underline font-bold text-[11px]">Mở tệp</a>
                `;
                khungAnh.appendChild(item);
            });
        } else {
            khungAnh.innerHTML = `<span class="text-slate-400 italic text-xs">Không có tệp bằng chứng đính kèm.</span>`;
        }

        const khungKetQua = document.getElementById('noiDungKetQuaXuLy');
        if (data.ketQua) {
            if (data.ketQua.quyetDinh === 'THU_HOI') {
                khungKetQua.className = "p-3 rounded-xl border border-red-200 bg-red-50 text-red-900 space-y-1 text-xs";
                khungKetQua.innerHTML = `
                    <div class="font-bold text-red-700 flex items-center gap-1.5"><i class="fa-solid fa-ban"></i> QUYẾT ĐỊNH: THU HỒI LÔ THUỐC</div>
                    <p>${data.ketQua.lyDoXuLy}</p>
                    <div class="text-[10px] text-red-500 font-mono pt-0.5">Thời điểm: ${data.ketQua.ngayXuLy}</div>
                `;
            } else {
                khungKetQua.className = "p-3 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 space-y-1 text-xs";
                khungKetQua.innerHTML = `
                    <div class="font-bold text-slate-700 flex items-center gap-1.5"><i class="fa-solid fa-circle-xmark"></i> QUYẾT ĐỊNH: TỪ CHỐI BÁO CÁO</div>
                    <p>${data.ketQua.lyDoXuLy}</p>
                    <div class="text-[10px] text-slate-400 font-mono pt-0.5">Thời điểm: ${data.ketQua.ngayXuLy}</div>
                `;
            }
        } else {
            khungKetQua.className = "p-3 rounded-xl border border-amber-200 bg-amber-50 text-amber-800 text-xs";
            khungKetQua.innerHTML = `<i class="fa-solid fa-hourglass-half mr-1"></i> Báo cáo đang trong tiến trình đối soát hồ sơ bởi Cơ quan quản lý Dược.`;
        }

        if (modalChiTiet) modalChiTiet.classList.remove('hidden');
    }

    document.getElementById('nutDongChiTiet')?.addEventListener('click', () => modalChiTiet.classList.add('hidden'));
    document.getElementById('nutDongChiTietBottom')?.addEventListener('click', () => modalChiTiet.classList.add('hidden'));
});
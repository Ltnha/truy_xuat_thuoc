document.addEventListener('DOMContentLoaded', function () {
    const modalXemHoSo = document.getElementById('modalXemHoSo');
    const modalDuyet = document.getElementById('modalXacNhanDuyet');
    const modalTuChoi = document.getElementById('modalTuChoiHoSo');

    const formActionDuyet = document.getElementById('formActionDuyetToChuc');
    const formActionTuChoi = document.getElementById('formActionTuChoiToChuc');

    const txtLyDoTuChoi = document.getElementById('nhapLyDoTuChoiToChuc');
    const nutXacNhanTuChoi = document.getElementById('nutXacNhanTuChoi');

    function renderDanhSachFile(fileList) {
        const containerFiles = document.getElementById('panelDanhSachTepMinhChung');
        if (!containerFiles) return;

        if (!fileList || fileList.length === 0) {
            containerFiles.innerHTML = `<div class="text-slate-400 text-center py-4">Không có tệp đính kèm nào</div>`;
        } else {
            containerFiles.innerHTML = fileList.map(file => `
                <div class="p-2.5 bg-white rounded-xl border border-indigo-100 flex items-center justify-between text-xs">
                    <span class="font-medium text-slate-800 truncate max-w-[200px]">${file.ten}</span>
                    <a href="/ho-so/tai-lieu/${file.id}" target="_blank" class="px-2.5 py-1 bg-indigo-50 text-indigo-700 font-bold rounded-lg text-[11px] hover:bg-indigo-100">
                        <i class="fa-solid fa-download mr-1"></i> Tải về
                    </a>
                </div>
            `).join('');
        }
    }

    function renderThongTinPhapLy(data) {
        if (!data) return;
        document.getElementById('panelTenToChucTitle').textContent = data.tenToChuc || '';
        document.getElementById('panelTenToChuc').textContent = data.tenToChuc || '';
        document.getElementById('panelMaSoThue').textContent = data.maSoThue || '';
        document.getElementById('panelSoDienThoai').textContent = data.soDienThoai || 'N/A';
        document.getElementById('panelSoGiayPhep').textContent = data.soGiayPhepDuoc || '';
        document.getElementById('panelCoQuanCap').textContent = data.coQuanCap || '';
        document.getElementById('panelNgayCap').textContent = data.ngayCapGiayPhep || '';
        document.getElementById('panelDiaChi').textContent = data.diaChi || '';
    }

    document.querySelectorAll('.btn-xem-ho-so').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            const tendangnhap = this.getAttribute('data-tendangnhap');
            const trangthai = this.getAttribute('data-trangthai');
            const lichsuList = JSON.parse(this.getAttribute('data-lichsu') || '[]');

            document.getElementById('panelTenDangNhap').textContent = tendangnhap || 'Chưa tạo';

            const lanMoiNhat = lichsuList[0] || {};
            renderThongTinPhapLy(lanMoiNhat);
            renderDanhSachFile(lanMoiNhat.taiLieus || []);

            const viewingBadge = document.getElementById('panelViewingVersionBadge');
            const headerVersionLabel = document.getElementById('panelHeaderVersionLabel');
            if (viewingBadge) {
                viewingBadge.textContent = lanMoiNhat.phienBan ? `Lần nộp ${lanMoiNhat.phienBan} (Mới nhất)` : 'Lần nộp mới nhất';
            }
            if (headerVersionLabel) {
                headerVersionLabel.textContent = lanMoiNhat.phienBan ? `Lần nộp ${lanMoiNhat.phienBan}` : 'Lần mới nhất';
            }

            const containerLichSu = document.getElementById('panelDanhSachLichSuNop');
            if (containerLichSu) {
                if (lichsuList.length === 0) {
                    containerLichSu.innerHTML = `<div class="text-slate-400 text-center py-2">Chưa có lịch sử nộp</div>`;
                } else {
                    containerLichSu.innerHTML = lichsuList.map((item, idx) => `
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs space-y-1.5">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-800">Lần nộp thứ ${item.phienBan} (${item.ngayGui})</span>
                                <div class="flex items-center gap-2">
                                    ${item.trangThai === 'DA_DUYET' 
                                        ? '<span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold text-[10px]">Đã duyệt</span>'
                                        : (item.trangThai === 'TU_CHOI' 
                                            ? '<span class="px-2 py-0.5 rounded-full bg-red-100 text-red-800 font-bold text-[10px]">Từ chối</span>'
                                            : '<span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 font-bold text-[10px]">Chờ duyệt</span>')
                                    }
                                    <button type="button" class="btn-xem-lan-nop px-2.5 py-1 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg text-[10px] transition-all cursor-pointer" data-idx="${idx}">
                                        <i class="fa-solid fa-eye mr-1"></i> Xem thông tin lần này
                                    </button>
                                </div>
                            </div>
                            ${item.lyDoTuChoi ? `<div class="text-red-600 font-medium pt-0.5"><strong class="text-slate-700">Lý do từ chối (${item.ngayXuLy}):</strong>${item.lyDoTuChoi}</div>` : ''}
                        </div>
                    `).join('');

                    document.querySelectorAll('.btn-xem-lan-nop').forEach(b => {
                        b.addEventListener('click', function () {
                            const index = parseInt(this.getAttribute('data-idx'), 10);
                            const lanSelected = lichsuList[index];
                            if (lanSelected) {
                                renderThongTinPhapLy(lanSelected);
                                renderDanhSachFile(lanSelected.taiLieus || []);
                                if (viewingBadge) {
                                    viewingBadge.textContent = `Lần nộp ${lanSelected.phienBan} (${lanSelected.ngayGui})`;
                                }
                                if (headerVersionLabel) {
                                    headerVersionLabel.textContent = `Lần nộp ${lanSelected.phienBan}`;
                                }
                            }
                        });
                    });
                }
            }

            if (formActionDuyet) formActionDuyet.action = `/co-quan/phe-duyet-to-chuc/${id}`;
            if (formActionTuChoi) formActionTuChoi.action = `/co-quan/phe-duyet-to-chuc/${id}`;

            document.getElementById('duyetModalTenToChuc').textContent = lanMoiNhat.tenToChuc || '';
            document.getElementById('duyetModalMST').textContent = lanMoiNhat.maSoThue || '';

            const cumNut = document.getElementById('panelCumNutHanhDong');
            if (trangthai !== 'CHO_DUYET') {
                if (cumNut) cumNut.classList.add('hidden');
            } else {
                if (cumNut) cumNut.classList.remove('hidden');
            }

            if (modalXemHoSo) modalXemHoSo.classList.remove('hidden');
        });
    });

    document.querySelectorAll('.dong-modal-hoso').forEach(b => b.addEventListener('click', () => modalXemHoSo?.classList.add('hidden')));
    document.querySelectorAll('.dong-modal-duyet').forEach(b => b.addEventListener('click', () => modalDuyet?.classList.add('hidden')));
    document.querySelectorAll('.dong-modal-tuchoi').forEach(b => b.addEventListener('click', () => modalTuChoi?.classList.add('hidden')));

    document.getElementById('panelNutMoModalDuyet')?.addEventListener('click', () => {
        modalXemHoSo?.classList.add('hidden');
        modalDuyet?.classList.remove('hidden');
    });

    document.getElementById('panelNutMoModalTuChoi')?.addEventListener('click', () => {
        modalXemHoSo?.classList.add('hidden');
        modalTuChoi?.classList.remove('hidden');
    });

    if (txtLyDoTuChoi && nutXacNhanTuChoi) {
        txtLyDoTuChoi.addEventListener('input', function () {
            if (this.value.trim().length >= 5) {
                nutXacNhanTuChoi.disabled = false;
                nutXacNhanTuChoi.classList.remove('bg-slate-200', 'text-slate-400', 'cursor-not-allowed');
                nutXacNhanTuChoi.classList.add('bg-red-600', 'hover:bg-red-700', 'text-white', 'cursor-pointer');
            } else {
                nutXacNhanTuChoi.disabled = true;
                nutXacNhanTuChoi.classList.add('bg-slate-200', 'text-slate-400', 'cursor-not-allowed');
                nutXacNhanTuChoi.classList.remove('bg-red-600', 'hover:bg-red-700', 'text-white', 'cursor-pointer');
            }
        });
    }
});
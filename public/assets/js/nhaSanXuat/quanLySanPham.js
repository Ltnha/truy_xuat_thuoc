document.addEventListener('DOMContentLoaded', function () {
    // 1. Khai báo dữ liệu gốc & Biến quản lý trạng thái
    const meSanPhamGoc = window.danhSachSanPham || [];
    let danhSachDaLoc = [...meSanPhamGoc];
    let trangHienTai = 1;
    const soDongMoiTrang = 10;

    // Các phần tử DOM bảng & phân trang
    const thanBang = document.getElementById('thanBangSanPham');
    const badgeSoLuong = document.getElementById('soLuongSPBadge');
    const txtThongTinPhanTrang = document.getElementById('thongTinPhanTrangSP');
    const cumNutPhanTrang = document.getElementById('cumNutPhanTrangSP');

    // Bộ lọc & Tìm kiếm
    const oTimKiem = document.getElementById('oTimKiemSP');
    const locTrangThai = document.getElementById('locTrangThaiSP');

    // Modal Đăng ký / Nộp lại
    const modalSP = document.getElementById('modalDangKySP');
    const formSP = document.getElementById('formSubmitSP');
    const tieuDeModalSP = document.getElementById('tieuDeModalSP');
    const khungCanhBaoTuChoi = document.getElementById('khungCanhBaoTuChoiCu');
    const noiDungLyDoTuChoi = document.getElementById('noiDungLyDoTuChoiCu');
    const spIdInput = document.getElementById('spIdDangChon');

    // Modal Chi tiết & Lịch sử
    const modalChiTiet = document.getElementById('modalChiTietSP');

    // ════════════════════════════════════════════════════════════════
    // 2. HÀM RENDER BẢNG SẢN PHẨM & PHÂN TRANG
    // ════════════════════════════════════════════════════════════════
    function renderBangSanPham() {
        if (!thanBang) return;
        thanBang.innerHTML = '';

        const tongSo = danhSachDaLoc.length;
        if (badgeSoLuong) badgeSoLuong.textContent = `Hiển thị: ${tongSo} sản phẩm`;

        if (tongSo === 0) {
            thanBang.innerHTML = `
                <tr>
                    <td colspan="7" class="py-8 text-center text-slate-400 italic">
                        Không tìm thấy sản phẩm dược phẩm nào phù hợp.
                    </td>
                </tr>
            `;
            if (txtThongTinPhanTrang) txtThongTinPhanTrang.textContent = 'Trang 0 / 0';
            if (cumNutPhanTrang) cumNutPhanTrang.innerHTML = '';
            return;
        }

        const tongSoTrang = Math.ceil(tongSo / soDongMoiTrang);
        if (trangHienTai > tongSoTrang) trangHienTai = tongSoTrang;
        if (trangHienTai < 1) trangHienTai = 1;

        const batDau = (trangHienTai - 1) * soDongMoiTrang;
        const ketThuc = Math.min(batDau + soDongMoiTrang, tongSo);
        const danhSachTrang = danhSachDaLoc.slice(batDau, ketThuc);

        danhSachTrang.forEach(sp => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-slate-50/80 transition-colors border-b border-slate-100';

            let badgeHtml = '';
            if (sp.trangThaiDuyet === 'DA_DUYET') {
                badgeHtml = `<span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 rounded-full font-bold text-[11px] inline-flex items-center gap-1"><i class="fa-solid fa-circle-check"></i> Đã duyệt (Lưu hành)</span>`;
            } else if (sp.trangThaiDuyet === 'CHO_DUYET') {
                badgeHtml = `<span class="px-2.5 py-1 bg-amber-100 text-amber-800 rounded-full font-bold text-[11px] inline-flex items-center gap-1"><i class="fa-solid fa-clock"></i> Chờ duyệt</span>`;
            } else {
                badgeHtml = `<span class="px-2.5 py-1 bg-red-100 text-red-800 rounded-full font-bold text-[11px] inline-flex items-center gap-1"><i class="fa-solid fa-circle-xmark"></i> Bị từ chối</span>`;
            }

            let nutNopLaiHtml = '';
            if (sp.trangThaiDuyet === 'TU_CHOI') {
                nutNopLaiHtml = `
                    <button type="button" class="btn-nop-lai px-2.5 py-1 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-lg text-[11px] transition-all shadow-2xs flex items-center gap-1" data-id="${sp.id}">
                        <i class="fa-solid fa-rotate-right"></i> Nộp lại
                    </button>
                `;
            }

            tr.innerHTML = `
                <td class="py-3 px-3 font-bold text-slate-900">${sp.tenSanPham}</td>
                <td class="py-3 px-3 font-mono font-bold text-cyan-800 whitespace-nowrap">${sp.soDangKy}</td>
                <td class="py-3 px-3 text-slate-700">${sp.thanhPhan || '---'}</td>
                <td class="py-3 px-2.5 text-slate-700 whitespace-nowrap">${sp.hamLuong || '---'}</td>
                <td class="py-3 px-2.5 text-slate-700 whitespace-nowrap">${sp.dangBaoChe || '---'}</td>
                <td class="py-3 px-2.5 text-center whitespace-nowrap">${badgeHtml}</td>
                <td class="py-3 px-3 text-center whitespace-nowrap">
                    <div class="flex items-center justify-center gap-1.5">
                        <button type="button" class="btn-xem-chi-tiet px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold rounded-lg text-[11px] transition-all flex items-center gap-1" data-id="${sp.id}">
                            <i class="fa-solid fa-eye"></i> Xem
                        </button>
                        ${nutNopLaiHtml}
                    </div>
                </td>
            `;

            thanBang.appendChild(tr);
        });

        if (txtThongTinPhanTrang) {
            txtThongTinPhanTrang.textContent = `Hiển thị từ ${batDau + 1} đến ${ketThuc} trên tổng số ${tongSo} sản phẩm (Trang ${trangHienTai}/${tongSoTrang})`;
        }

        renderNutPhanTrang(tongSoTrang);
        ganSuKienDongBang();
    }

    function renderNutPhanTrang(tongSoTrang) {
        if (!cumNutPhanTrang) return;
        cumNutPhanTrang.innerHTML = '';
        if (tongSoTrang <= 1) return;

        const btnTruoc = document.createElement('button');
        btnTruoc.className = `px-3 py-1.5 rounded-lg border text-xs font-bold transition-all ${trangHienTai === 1 ? 'border-slate-200 text-slate-300 cursor-not-allowed' : 'border-slate-300 text-slate-700 hover:bg-slate-100'}`;
        btnTruoc.innerHTML = `<i class="fa-solid fa-chevron-left"></i>`;
        btnTruoc.disabled = trangHienTai === 1;
        btnTruoc.addEventListener('click', () => {
            if (trangHienTai > 1) {
                trangHienTai--;
                renderBangSanPham();
            }
        });
        cumNutPhanTrang.appendChild(btnTruoc);

        for (let i = 1; i <= tongSoTrang; i++) {
            const btnSo = document.createElement('button');
            btnSo.className = `px-3 py-1.5 rounded-lg text-xs font-bold transition-all ${i === trangHienTai ? 'bg-emerald-700 text-white shadow-xs' : 'border border-slate-300 text-slate-700 hover:bg-slate-100'}`;
            btnSo.textContent = i;
            btnSo.addEventListener('click', () => {
                trangHienTai = i;
                renderBangSanPham();
            });
            cumNutPhanTrang.appendChild(btnSo);
        }

        const btnSau = document.createElement('button');
        btnSau.className = `px-3 py-1.5 rounded-lg border text-xs font-bold transition-all ${trangHienTai === tongSoTrang ? 'border-slate-200 text-slate-300 cursor-not-allowed' : 'border-slate-300 text-slate-700 hover:bg-slate-100'}`;
        btnSau.innerHTML = `<i class="fa-solid fa-chevron-right"></i>`;
        btnSau.disabled = trangHienTai === tongSoTrang;
        btnSau.addEventListener('click', () => {
            if (trangHienTai < tongSoTrang) {
                trangHienTai++;
                renderBangSanPham();
            }
        });
        cumNutPhanTrang.appendChild(btnSau);
    }

    // ════════════════════════════════════════════════════════════════
    // 3. XỬ LÝ LỌC VÀ TÌM KIẾM SẢN PHẨM
    // ════════════════════════════════════════════════════════════════
    function locDanhSach() {
        const tuKhoa = oTimKiem ? oTimKiem.value.toLowerCase().trim() : '';
        const trangThai = locTrangThai ? locTrangThai.value : 'TAT_CA';

        danhSachDaLoc = meSanPhamGoc.filter(sp => {
            const matchTuKhoa = !tuKhoa ||
                (sp.tenSanPham && sp.tenSanPham.toLowerCase().includes(tuKhoa)) ||
                (sp.soDangKy && sp.soDangKy.toLowerCase().includes(tuKhoa)) ||
                (sp.thanhPhan && sp.thanhPhan.toLowerCase().includes(tuKhoa));

            const matchTrangThai = trangThai === 'TAT_CA' || sp.trangThaiDuyet === trangThai;

            return matchTuKhoa && matchTrangThai;
        });

        trangHienTai = 1;
        renderBangSanPham();
    }

    if (oTimKiem) oTimKiem.addEventListener('input', locDanhSach);
    if (locTrangThai) locTrangThai.addEventListener('change', locDanhSach);

    // ════════════════════════════════════════════════════════════════
    // 4. BẮT SỰ KIỆN NÚT XEM CHI TIẾT & NỘP LẠI
    // ════════════════════════════════════════════════════════════════
    function ganSuKienDongBang() {
        document.querySelectorAll('.btn-xem-chi-tiet').forEach(btn => {
            btn.addEventListener('click', function () {
                const spId = parseInt(this.getAttribute('data-id'));
                const sp = meSanPhamGoc.find(x => x.id === spId);
                if (!sp) return;

                document.getElementById('tenSPChiTietTitle').textContent = sp.tenSanPham;
                document.getElementById('modalChiTietTenThuoc').textContent = sp.tenSanPham;
                document.getElementById('modalChiTietSoDK').textContent = sp.soDangKy;
                document.getElementById('modalChiTietThanhPhan').textContent = sp.thanhPhan || '---';
                document.getElementById('modalChiTietHamLuong').textContent = sp.hamLuong || '---';
                document.getElementById('modalChiTietDangBaoChe').textContent = sp.dangBaoChe || '---';

                const elTrangThai = document.getElementById('modalChiTietTrangThai');
                if (sp.trangThaiDuyet === 'DA_DUYET') {
                    elTrangThai.innerHTML = `<span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-full font-bold text-[10px]">Đã duyệt</span>`;
                } else if (sp.trangThaiDuyet === 'CHO_DUYET') {
                    elTrangThai.innerHTML = `<span class="px-2 py-0.5 bg-amber-100 text-amber-800 rounded-full font-bold text-[10px]">Chờ duyệt</span>`;
                } else {
                    elTrangThai.innerHTML = `<span class="px-2 py-0.5 bg-red-100 text-red-800 rounded-full font-bold text-[10px]">Bị từ chối</span>`;
                }

                const khungBC = document.getElementById('khungChungCuBlockchainSP');
                const bctx = sp.blockchain_transactions && sp.blockchain_transactions.length > 0 ? sp.blockchain_transactions[0] : null;

                if (bctx && bctx.txHash) {
                    khungBC.classList.remove('hidden');
                    document.getElementById('modalChiTietTxHashSP').textContent = bctx.txHash;
                    document.getElementById('modalChiTietTxLinkSP').href = `https://sepolia.etherscan.io/tx/${bctx.txHash}`;
                } else {
                    khungBC.classList.add('hidden');
                }

                const khungLichSu = document.getElementById('khungDanhSachLichSuNop');
                khungLichSu.innerHTML = '';

                const yeuCaus = sp.yeu_cau_dang_ky_san_phams || [];
                if (yeuCaus.length === 0) {
                    khungLichSu.innerHTML = `<div class="text-slate-400 italic">Chưa có dữ liệu lịch sử nộp.</div>`;
                } else {
                    yeuCaus.forEach(yc => {
                        let ycBadge = '';
                        if (yc.trangThai === 'DA_DUYET') ycBadge = `<span class="text-emerald-700 font-bold">Đã duyệt</span>`;
                        else if (yc.trangThai === 'CHO_DUYET') ycBadge = `<span class="text-amber-700 font-bold">Chờ xử lý</span>`;
                        else ycBadge = `<span class="text-red-600 font-bold">Bị từ chối</span>`;

                        const lyDoHtml = yc.lyDoTuChoi ? `<div class="text-red-600 mt-1 bg-red-50 p-2 rounded-lg"><strong>Lý do từ chối:</strong> ${yc.lyDoTuChoi}</div>` : '';

                        const div = document.createElement('div');
                        div.className = 'p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-1';
                        div.innerHTML = `
                            <div class="flex items-center justify-between font-bold">
                                <span>Phiên bản hồ sơ: v${yc.versionHoSo}</span>
                                <span>${ycBadge}</span>
                            </div>
                            <div class="text-[11px] text-slate-500">Ngày gửi: ${new Date(yc.ngayGui).toLocaleString('vi-VN')}</div>
                            ${lyDoHtml}
                        `;
                        khungLichSu.appendChild(div);
                    });
                }

                modalChiTiet.classList.remove('hidden');
            });
        });

        document.querySelectorAll('.btn-nop-lai').forEach(btn => {
            btn.addEventListener('click', function () {
                const spId = parseInt(this.getAttribute('data-id'));
                const sp = meSanPhamGoc.find(x => x.id === spId);
                if (!sp) return;

                spIdInput.value = sp.id;
                // Chuẩn hóa Form Action cho đường dẫn Nộp lại
                formSP.action = `/nha-san-xuat/quan-ly-san-pham/${sp.id}/nop-lai`;

                if (!formSP.querySelector('input[name="_method"]')) {
                    const methodInput = document.createElement('input');
                    methodInput.type = 'hidden';
                    methodInput.name = '_method';
                    methodInput.value = 'PUT';
                    formSP.appendChild(methodInput);
                }

                tieuDeModalSP.innerHTML = `<i class="fa-solid fa-rotate-right text-amber-600"></i> Nộp Lại Đăng Ký: ${sp.tenSanPham}`;

                document.getElementById('nhapTenThuoc').value = sp.tenSanPham;
                document.getElementById('nhapSoDK').value = sp.soDangKy;
                document.getElementById('nhapThanhPhan').value = sp.thanhPhan || '';
                document.getElementById('nhapHamLuong').value = sp.hamLuong || '';
                document.getElementById('nhapDangBaoChe').value = sp.dangBaoChe || '';

                if (sp.yeu_cau_dang_ky_san_phams && sp.yeu_cau_dang_ky_san_phams.length > 0) {
                    const lanCu = sp.yeu_cau_dang_ky_san_phams[sp.yeu_cau_dang_ky_san_phams.length - 1];
                    if (lanCu.lyDoTuChoi) {
                        noiDungLyDoTuChoi.textContent = lanCu.lyDoTuChoi;
                        khungCanhBaoTuChoi.classList.remove('hidden');
                    }
                }

                modalSP.classList.remove('hidden');
            });
        });
    }

    // ════════════════════════════════════════════════════════════════
    // 5. XỬ LÝ ẨN HỆN MODAL
    // ════════════════════════════════════════════════════════════════
    const btnOpen = document.getElementById('nutMoModalDangKySP');
    if (btnOpen) {
        btnOpen.addEventListener('click', () => {
            formSP.reset();
            // Chuẩn hóa Form Action cho đường dẫn Tạo mới
            formSP.action = "/nha-san-xuat/quan-ly-san-pham";

            const oldMethod = formSP.querySelector('input[name="_method"]');
            if (oldMethod) oldMethod.remove();

            spIdInput.value = '';
            khungCanhBaoTuChoi.classList.add('hidden');
            tieuDeModalSP.innerHTML = `<i class="fa-solid fa-pills text-emerald-700"></i> Nộp Đơn Đăng Ký Sản Phẩm Dược Mới`;
            modalSP.classList.remove('hidden');
        });
    }

    document.querySelectorAll('.dong-modal-sp').forEach(btn => {
        btn.addEventListener('click', () => modalSP.classList.add('hidden'));
    });

    document.querySelectorAll('.dong-modal-chitiet-sp').forEach(btn => {
        btn.addEventListener('click', () => modalChiTiet.classList.add('hidden'));
    });

    const nutSaoChep = document.getElementById('nutSaoChepTxSP');
    if (nutSaoChep) {
        nutSaoChep.addEventListener('click', function () {
            const txHash = document.getElementById('modalChiTietTxHashSP').textContent;
            navigator.clipboard.writeText(txHash).then(() => {
                nutSaoChep.innerHTML = `<i class="fa-solid fa-check"></i> Đã chép`;
                setTimeout(() => {
                    nutSaoChep.innerHTML = `<i class="fa-regular fa-copy"></i> Sao chép`;
                }, 2000);
            });
        });
    }

    // 6. Chạy render bảng ban đầu
    renderBangSanPham();
});
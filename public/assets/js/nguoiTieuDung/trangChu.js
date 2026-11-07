document.addEventListener('DOMContentLoaded', () => {

    /**
     * Hàm phân trang dùng chung cho các bảng dữ liệu HTML
     */
    function khoiTaoPhanTrang({ thanBangId, dongClass, nutContainerId, thongTinId, size = 5 }) {
        const thanBang = document.getElementById(thanBangId);
        const nutContainer = document.getElementById(nutContainerId);
        const thongTin = document.getElementById(thongTinId);

        if (!thanBang || !nutContainer) return null;

        let trangHienTai = 1;
        const hangPerTrang = size;

        function update() {
            // Lấy danh sách các dòng đang không bị ẩn bởi bộ lọc search/select
            const allDongs = Array.from(thanBang.querySelectorAll(`.${dongClass}`));
            const activeDongs = allDongs.filter(d => d.getAttribute('data-filtered-out') !== 'true');

            const tongSo = activeDongs.length;
            const tongTrang = Math.ceil(tongSo / hangPerTrang) || 1;

            if (trangHienTai > tongTrang) trangHienTai = tongTrang;
            if (trangHienTai < 1) trangHienTai = 1;

            // Ẩn/hiện theo trang
            allDongs.forEach(d => d.style.display = 'none');

            const startIdx = (trangHienTai - 1) * hangPerTrang;
            const endIdx = startIdx + hangPerTrang;
            const dongsTrangHienTai = activeDongs.slice(startIdx, endIdx);

            dongsTrangHienTai.forEach(d => d.style.display = '');

            // Cập nhật nhãn thông tin
            if (thongTin) {
                if (tongSo === 0) {
                    thongTin.textContent = 'Không có dữ liệu phù hợp';
                } else {
                    const from = startIdx + 1;
                    const to = Math.min(endIdx, tongSo);
                    thongTin.textContent = `Hiển thị ${from} - ${to} trong tổng số ${tongSo}`;
                }
            }

            // Vẽ nút phân trang
            nutContainer.innerHTML = '';
            if (tongTrang <= 1) return;

            // Nút Prev
            const btnPrev = document.createElement('button');
            btnPrev.className = `px-2.5 py-1 rounded-lg border text-xs font-bold transition-all ${trangHienTai === 1 ? 'border-slate-200 text-slate-300 cursor-not-allowed' : 'border-slate-300 text-slate-700 hover:bg-slate-100 cursor-pointer'}`;
            btnPrev.innerHTML = `<i class="fa-solid fa-chevron-left text-[10px]"></i>`;
            btnPrev.disabled = trangHienTai === 1;
            btnPrev.onclick = () => { trangHienTai--; update(); };
            nutContainer.appendChild(btnPrev);

            // Các nút số trang
            for (let i = 1; i <= tongTrang; i++) {
                const btnPage = document.createElement('button');
                const isCurrent = i === trangHienTai;
                btnPage.className = `px-3 py-1 rounded-lg text-xs font-bold transition-all cursor-pointer ${isCurrent ? 'bg-indigo-700 text-white shadow-2xs' : 'border border-slate-200 text-slate-600 hover:bg-slate-100'}`;
                btnPage.textContent = i;
                btnPage.onclick = () => { trangHienTai = i; update(); };
                nutContainer.appendChild(btnPage);
            }

            // Nút Next
            const btnNext = document.createElement('button');
            btnNext.className = `px-2.5 py-1 rounded-lg border text-xs font-bold transition-all ${trangHienTai === tongTrang ? 'border-slate-200 text-slate-300 cursor-not-allowed' : 'border-slate-300 text-slate-700 hover:bg-slate-100 cursor-pointer'}`;
            btnNext.innerHTML = `<i class="fa-solid fa-chevron-right text-[10px]"></i>`;
            btnNext.disabled = trangHienTai === tongTrang;
            btnNext.onclick = () => { trangHienTai++; update(); };
            nutContainer.appendChild(btnNext);
        }

        update();

        return {
            refresh: () => { trangHienTai = 1; update(); }
        };
    }

    // ══ 1. PHÂN TRANG BẢNG THU HỒI DƯỢC PHẨM ══
    const pagerThuHoi = khoiTaoPhanTrang({
        thanBangId: 'thanBangThuHoiPublic',
        dongClass: 'dong-thu-hoi-item',
        nutContainerId: 'nutPhanTrangThuHoi',
        thongTinId: 'thongTinTrangThuHoi',
        size: 5
    });

    // ══ 2. LỌC & PHÂN TRANG BẢNG DANH MỤC THUỐC ══
    const pagerThuoc = khoiTaoPhanTrang({
        thanBangId: 'thanBangThuocPublic',
        dongClass: 'dong-thuoc-item',
        nutContainerId: 'nutPhanTrangThuoc',
        thongTinId: 'thongTinTrangThuoc',
        size: 5
    });

    const oTimKiemThuoc = document.getElementById('timKiemThuocPublic');
    const thanBangThuoc = document.getElementById('thanBangThuocPublic');

    if (oTimKiemThuoc && thanBangThuoc) {
        oTimKiemThuoc.addEventListener('input', function () {
            const tuKhoa = this.value.toLowerCase().trim();
            const dongs = thanBangThuoc.querySelectorAll('.dong-thuoc-item');

            dongs.forEach(dong => {
                const ten = dong.querySelector('.ten-thuoc')?.textContent.toLowerCase() || '';
                const soDk = dong.querySelector('.so-dk')?.textContent.toLowerCase() || '';
                const thanhPhan = dong.querySelector('.thanh-phan')?.textContent.toLowerCase() || '';

                if (!tuKhoa || ten.includes(tuKhoa) || soDk.includes(tuKhoa) || thanhPhan.includes(tuKhoa)) {
                    dong.removeAttribute('data-filtered-out');
                } else {
                    dong.setAttribute('data-filtered-out', 'true');
                }
            });

            if (pagerThuoc) pagerThuoc.refresh();
        });
    }

    // ══ 3. LỌC & PHÂN TRANG BẢNG CƠ SỞ DƯỢC PHẨM ══
    const pagerCoSo = khoiTaoPhanTrang({
        thanBangId: 'thanBangCoSoPublic',
        dongClass: 'dong-co-so-item',
        nutContainerId: 'nutPhanTrangCoSo',
        thongTinId: 'thongTinTrangCoSo',
        size: 5
    });

    const selectLoaiCoSo = document.getElementById('locLoaiCoSoPublic');
    const oTimKiemCoSo = document.getElementById('timKiemCoSoPublic');
    const thanBangCoSo = document.getElementById('thanBangCoSoPublic');

    function locBaoBangCoSo() {
        if (!thanBangCoSo) return;

        const loaiSelected = selectLoaiCoSo ? selectLoaiCoSo.value : 'ALL';
        const tuKhoa = oTimKiemCoSo ? oTimKiemCoSo.value.toLowerCase().trim() : '';
        const dongs = thanBangCoSo.querySelectorAll('.dong-co-so-item');

        dongs.forEach(dong => {
            const loaiItem = dong.getAttribute('data-loai') || '';
            const ten = dong.querySelector('.ten-co-so')?.textContent.toLowerCase() || '';
            const diaChi = dong.querySelector('.dia-chi-co-so')?.textContent.toLowerCase() || '';

            const meatchLoai = (loaiSelected === 'ALL' || loaiItem === loaiSelected);
            const matchTuKhoa = (!tuKhoa || ten.includes(tuKhoa) || diaChi.includes(tuKhoa));

            if (meatchLoai && matchTuKhoa) {
                dong.removeAttribute('data-filtered-out');
            } else {
                dong.setAttribute('data-filtered-out', 'true');
            }
        });

        if (pagerCoSo) pagerCoSo.refresh();
    }

    if (selectLoaiCoSo) selectLoaiCoSo.addEventListener('change', locBaoBangCoSo);
    if (oTimKiemCoSo) oTimKiemCoSo.addEventListener('input', locBaoBangCoSo);
});
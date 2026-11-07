document.addEventListener('DOMContentLoaded', function () {
    let rangeHienTai = '30_NGAY';
    let chartDuongInstance = null;
    let chartCotInstance = null;
    let cachedTopLo = [];
    let cachedTopToChuc = [];

    // Elements DOM
    const nutLocThoiGian = document.querySelectorAll('.btn-loc-thoigian');
    const khungChonThang = document.getElementById('khungChonThang');
    const khungChonNgay = document.getElementById('khungChonNgayTuyChon');
    const selectThang = document.getElementById('selectThang');
    const selectNam = document.getElementById('selectNam');
    const inputNgayBatDau = document.getElementById('inputNgayBatDau');
    const inputNgayKetThuc = document.getElementById('inputNgayKetThuc');

    const thanBangLo = document.getElementById('thanBangLoThuocNghiVan');
    const thanBangToChuc = document.getElementById('thanBangToChucNghiVan');

    function dinhDangLoaiToChuc(loai) {
        switch (loai) {
            case 'NHA_SAN_XUAT': return 'Nhà sản xuất';
            case 'NHA_PHAN_PHOI': return 'Nhà phân phối';
            case 'NHA_THUOC': return 'Nhà thuốc';
            case 'CO_QUAN_QUAN_LY': return 'Cơ quan quản lý';
            default: return loai || 'N/A';
        }
    }

    function renderRuiRoBadge(mucDo) {
        if (mucDo === 'NGUY_CAP') {
            return `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-red-100 text-red-700 border border-red-300"><i class="fa-solid fa-triangle-exclamation"></i> Nguy cấp</span>`;
        } else if (mucDo === 'CAO') {
            return `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300"><i class="fa-solid fa-circle-exclamation"></i> Rủi ro cao</span>`;
        }
        return `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-300">Trung bình</span>`;
    }

    // ══ 1. TẢI DỮ LIỆU TỪ BACKEND API ══
    async function taiDuLieuThongKe() {
        ['kpiTongLoDangKy', 'kpiTongToChuc', 'kpiLoDangLuuHanh', 'kpiLoDaThuHoi', 'kpiNghiVanChoXuLy', 'kpiHoSoChoDuyet'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = '...';
        });

        if (thanBangLo) thanBangLo.innerHTML = `<tr><td colspan="8" class="text-center py-6 text-slate-400 italic"><i class="fa-solid fa-spinner fa-spin mr-2"></i>Đang tải thống kê lô thuốc...</td></tr>`;
        if (thanBangToChuc) thanBangToChuc.innerHTML = `<tr><td colspan="8" class="text-center py-6 text-slate-400 italic"><i class="fa-solid fa-spinner fa-spin mr-2"></i>Đang tải thống kê tổ chức...</td></tr>`;

        const params = new URLSearchParams({ range: rangeHienTai });

        if (rangeHienTai === 'THANG') {
            params.append('thang', selectThang.value);
            params.append('nam', selectNam.value);
        } else if (rangeHienTai === 'TUY_CHON') {
            params.append('tuNgay', inputNgayBatDau.value);
            params.append('denNgay', inputNgayKetThuc.value);
        }

        try {
            const res = await fetch(`/co-quan/thong-ke-bao-cao/data?${params.toString()}`, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();

            if (!res.ok || !data.success) {
                throw new Error(data.message || 'Không thể lấy dữ liệu thống kê từ máy chủ.');
            }

            // Cập nhật KPI
            const kpi = data.kpi || {};
            document.getElementById('kpiTongLoDangKy').textContent = (kpi.tongLoDangKy || 0).toLocaleString('vi-VN');
            document.getElementById('kpiTongToChuc').textContent = (kpi.tongToChuc || 0).toLocaleString('vi-VN');
            document.getElementById('kpiLoDangLuuHanh').textContent = (kpi.luuHanh || 0).toLocaleString('vi-VN');
            document.getElementById('kpiLoDaThuHoi').textContent = (kpi.thuHoi || 0).toLocaleString('vi-VN');
            document.getElementById('kpiNghiVanChoXuLy').textContent = (kpi.nghiVan || 0).toLocaleString('vi-VN');
            document.getElementById('kpiHoSoChoDuyet').textContent = (kpi.choDuyet || 0).toLocaleString('vi-VN');

            // Vẽ Biểu đồ
            khoiTaoBieuDo(data.chart || { labels: [], loData: [], thuHoiData: [] });

            // Render 2 Bảng độc lập
            cachedTopLo = data.topLoThuoc || [];
            renderBangTopLo(cachedTopLo);

            cachedTopToChuc = data.topToChuc || [];
            renderBangTopToChuc(cachedTopToChuc);

        } catch (err) {
            console.error("Lỗi tải thống kê:", err);
            if (thanBangLo) thanBangLo.innerHTML = `<tr><td colspan="8" class="text-center py-6 text-red-500 italic">Có lỗi xảy ra: ${err.message}</td></tr>`;
            if (thanBangToChuc) thanBangToChuc.innerHTML = `<tr><td colspan="8" class="text-center py-6 text-red-500 italic">Có lỗi xảy ra: ${err.message}</td></tr>`;
        }
    }

    // ══ 2. KHỞI TẠO BIỂU ĐỒ CHART.JS ══
    function khoiTaoBieuDo(chartData) {
        const labels = chartData.labels || [];
        const loData = chartData.loData || [];
        const thuHoiData = chartData.thuHoiData || [];

        let nhanTex = '30 ngày qua';
        if (rangeHienTai === '7_NGAY') nhanTex = '7 ngày qua';
        else if (rangeHienTai === 'THANG') nhanTex = `Tháng ${selectThang.value}/${selectNam.value}`;
        else if (rangeHienTai === 'TUY_CHON') nhanTex = `${inputNgayBatDau.value} đến ${inputNgayKetThuc.value}`;

        const nhanBDDuong = document.getElementById('nhanBieuDoDuong');
        const nhanBDCot = document.getElementById('nhanBieuDoCot');
        if (nhanBDDuong) nhanBDDuong.textContent = nhanTex;
        if (nhanBDCot) nhanBDCot.textContent = nhanTex;

        // Biểu đồ đường
        const ctxDuong = document.getElementById('bieuDoDuongDangKy')?.getContext('2d');
        if (ctxDuong) {
            if (chartDuongInstance) chartDuongInstance.destroy();
            chartDuongInstance = new Chart(ctxDuong, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Số lô thuốc đăng ký',
                        data: loData,
                        borderColor: '#4338ca',
                        backgroundColor: 'rgba(67, 56, 202, 0.08)',
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.3,
                        pointBackgroundColor: '#4338ca',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { family: 'Inter', size: 10 } } },
                        y: { grid: { color: '#f1f5f9' }, ticks: { font: { family: 'Inter', size: 10 }, precision: 0 } }
                    }
                }
            });
        }

        // Biểu đồ cột
        const ctxCot = document.getElementById('bieuDoCotThuHoi')?.getContext('2d');
        if (ctxCot) {
            if (chartCotInstance) chartCotInstance.destroy();
            chartCotInstance = new Chart(ctxCot, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Số vụ thu hồi lô thuốc',
                        data: thuHoiData,
                        backgroundColor: '#dc2626',
                        borderRadius: 6,
                        barThickness: 20
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { family: 'Inter', size: 10 } } },
                        y: { grid: { color: '#f1f5f9' }, ticks: { font: { family: 'Inter', size: 10 }, precision: 0 } }
                    }
                }
            });
        }
    }

    // ══ 3. RENDER BẢNG 1: TOP LÔ THUỐC ══
    function renderBangTopLo(danhSach) {
        if (!thanBangLo) return;
        thanBangLo.innerHTML = '';

        if (!danhSach || danhSach.length === 0) {
            thanBangLo.innerHTML = `<tr><td colspan="8" class="text-center py-6 text-slate-400 italic">Không có dữ liệu lô thuốc bị báo cáo nào trong khoảng thời gian này.</td></tr>`;
            return;
        }

        danhSach.forEach(item => {
            let hangColor = 'text-slate-700 font-bold';
            if (item.hang === 1) hangColor = 'text-red-600 font-black';
            else if (item.hang === 2) hangColor = 'text-amber-600 font-bold';
            else if (item.hang === 3) hangColor = 'text-indigo-700 font-bold';

            const tr = document.createElement('tr');
            tr.className = 'hover:bg-slate-50 transition-colors border-b border-slate-100';
            tr.innerHTML = `
                <td class="py-3 px-3 text-center font-mono ${hangColor}">${item.hang}</td>
                <td class="py-3 px-3 font-mono font-bold text-indigo-900 whitespace-nowrap">${item.maLo}</td>
                <td class="py-3 px-3 font-bold text-slate-900 leading-tight">
                    ${item.tenSanPham}<br>
                    <span class="font-mono text-[11px] font-semibold text-cyan-800">SĐK: ${item.soDangKy}</span>
                </td>
                <td class="py-3 px-3 text-slate-800 font-semibold leading-tight">${item.toChucPhuTrach}</td>
                <td class="py-3 px-2.5 text-center font-mono font-bold text-red-700 whitespace-nowrap">${item.soLuotBaoCao} lượt</td>
                <td class="py-3 px-3 text-slate-600 leading-tight max-w-xs truncate" title="${item.hanhViChuYeu}">${item.hanhViChuYeu}</td>
                <td class="py-3 px-2.5 text-center whitespace-nowrap">${renderRuiRoBadge(item.mucDoRuiRo)}</td>
                <td class="py-3 px-3 text-center whitespace-nowrap">
                    <a href="/co-quan/xu-ly-nghi-van?tuKhoa=${encodeURIComponent(item.maLo)}" class="px-2.5 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-800 font-bold text-xs transition-all inline-flex items-center gap-1 border border-indigo-200 shadow-2xs">
                        Xử lý <i class="fa-solid fa-arrow-right text-[9px]"></i>
                    </a>
                </td>
            `;
            thanBangLo.appendChild(tr);
        });
    }

    // ══ 4. RENDER BẢNG 2: TOP TỔ CHỨC ══
    function renderBangTopToChuc(danhSach) {
        if (!thanBangToChuc) return;
        thanBangToChuc.innerHTML = '';

        if (!danhSach || danhSach.length === 0) {
            thanBangToChuc.innerHTML = `<tr><td colspan="8" class="text-center py-6 text-slate-400 italic">Không có dữ liệu tổ chức bị báo cáo nào trong khoảng thời gian này.</td></tr>`;
            return;
        }

        danhSach.forEach(item => {
            let hangColor = 'text-slate-700 font-bold';
            if (item.hang === 1) hangColor = 'text-red-600 font-black';
            else if (item.hang === 2) hangColor = 'text-amber-600 font-bold';
            else if (item.hang === 3) hangColor = 'text-indigo-700 font-bold';

            const tr = document.createElement('tr');
            tr.className = 'hover:bg-slate-50 transition-colors border-b border-slate-100';
            tr.innerHTML = `
                <td class="py-3 px-3 text-center font-mono ${hangColor}">${item.hang}</td>
                <td class="py-3 px-3 font-bold text-slate-900 leading-tight">${item.tenToChuc}</td>
                <td class="py-3 px-2.5 text-center whitespace-nowrap">
                    <span class="px-2.5 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                        ${dinhDangLoaiToChuc(item.loaiToChuc)}
                    </span>
                </td>
                <td class="py-3 px-3 font-mono text-slate-700">${item.maSoThue}</td>
                <td class="py-3 px-2.5 text-center font-mono font-bold text-red-700 whitespace-nowrap">${item.soLuotBaoCao} lượt</td>
                <td class="py-3 px-3 text-slate-600 leading-tight max-w-xs truncate" title="${item.hanhViChuYeu}">${item.hanhViChuYeu}</td>
                <td class="py-3 px-2.5 text-center whitespace-nowrap">${renderRuiRoBadge(item.mucDoRuiRo)}</td>
                <td class="py-3 px-3 text-center whitespace-nowrap">
                    <a href="/co-quan/quan-ly-quyen-han?tuKhoa=${encodeURIComponent(item.tenToChuc)}" class="px-2.5 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-800 font-bold text-xs transition-all inline-flex items-center gap-1 border border-indigo-200 shadow-2xs">
                        Giám sát <i class="fa-solid fa-arrow-right text-[9px]"></i>
                    </a>
                </td>
            `;
            thanBangToChuc.appendChild(tr);
        });
    }

    // ══ 5. SỰ KIỆN LỌC THỜI GIAN ══
    nutLocThoiGian.forEach(btn => {
        btn.addEventListener('click', function () {
            nutLocThoiGian.forEach(b => {
                b.className = "btn-loc-thoigian px-3 py-1.5 rounded-lg transition-all text-slate-600 hover:text-indigo-900";
            });
            this.className = "btn-loc-thoigian px-3 py-1.5 rounded-lg transition-all bg-indigo-700 text-white shadow-2xs font-bold";

            const range = this.getAttribute('data-range');
            rangeHienTai = range;

            if (khungChonThang) khungChonThang.classList.add('hidden');
            if (khungChonNgay) khungChonNgay.classList.add('hidden');

            if (range === 'THANG') {
                if (khungChonThang) khungChonThang.classList.remove('hidden');
            } else if (range === 'TUY_CHON') {
                if (khungChonNgay) khungChonNgay.classList.remove('hidden');
            } else {
                taiDuLieuThongKe();
            }
        });
    });

    document.getElementById('nutApDungThang')?.addEventListener('click', function () {
        taiDuLieuThongKe();
    });

    document.getElementById('nutApDungKhoangNgay')?.addEventListener('click', function () {
        if (!inputNgayBatDau.value || !inputNgayKetThuc.value) {
            alert("Vui lòng chọn đầy đủ từ ngày và đến ngày!");
            return;
        }
        taiDuLieuThongKe();
    });

    // ══ 6. XUẤT CSV BÁO CÁO GIÁM SÁT ══
    document.getElementById('nutXuatBaoCao')?.addEventListener('click', function () {
        const tieuDe = `BAO_CAO_GIAM_SAT_PHARMACHAIN_${rangeHienTai}_${Date.now()}.csv`;
        let csvContent = "\uFEFF"; // BOM UTF-8
        csvContent += "CUC QUAN LY DUOC - HE THONG GIAM SAT PHARMACHAIN\n";
        csvContent += `Ngay xuat bao cao: ${new Date().toLocaleDateString('vi-VN')}\n\n`;

        csvContent += "--- TOP LO THUOC BI BAO CAO NGHI VAN NHIEU NHAT ---\n";
        csvContent += "Hang,Ma lo,Ten san pham,So dang ky,Don vi san xuat,So luot bao cao,Noi dung nghi van,Muc do rui ro\n";
        cachedTopLo.forEach(item => {
            csvContent += `${item.hang},"${item.maLo}","${item.tenSanPham}","${item.soDangKy}","${item.toChucPhuTrach}",${item.soLuotBaoCao},"${item.hanhViChuYeu}","${item.mucDoRuiRo}"\n`;
        });

        csvContent += "\n--- TOP TO CHUC BI BAO CAO NGHI VAN NHIEU NHAT ---\n";
        csvContent += "Hang,Ten to chuc,Loai to chuc,Ma so thue / So GP,So luot bao cao,Noi dung nghi van,Muc do rui ro\n";
        cachedTopToChuc.forEach(item => {
            csvContent += `${item.hang},"${item.tenToChuc}","${dinhDangLoaiToChuc(item.loaiToChuc)}","${item.maSoThue}",${item.soLuotBaoCao},"${item.hanhViChuYeu}","${item.mucDoRuiRo}"\n`;
        });

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = tieuDe;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    });

    // Khởi chạy mặc định
    taiDuLieuThongKe();
});
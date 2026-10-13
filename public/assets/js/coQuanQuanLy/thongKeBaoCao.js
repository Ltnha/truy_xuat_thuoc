document.addEventListener('DOMContentLoaded', function () {
    // ══ 1. CƠ SỞ DỮ LIỆU MÔ PHỎNG THEO KHOẢNG THỜI GIAN ══
    const duLieuTheoKy = {
        '7_NGAY': {
            tongLo: 1250,
            toChuc: 84,
            luuHanh: 1185,
            thuHoi: 18,
            nghiVan: 3,
            choDuyet: 2,
            lineLabels: ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'],
            lineData: [15, 22, 18, 30, 25, 12, 8],
            barLabels: ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'],
            barData: [0, 1, 0, 0, 2, 0, 0]
        },
        '30_NGAY': {
            tongLo: 1250,
            toChuc: 84,
            luuHanh: 1185,
            thuHoi: 18,
            nghiVan: 5,
            choDuyet: 3,
            lineLabels: ['Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10'],
            lineData: [140, 165, 190, 210, 245, 280],
            barLabels: ['Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10'],
            barData: [2, 1, 4, 3, 5, 3]
        },
        'TUY_CHON': {
            tongLo: 450,
            toChuc: 84,
            luuHanh: 430,
            thuHoi: 6,
            nghiVan: 2,
            choDuyet: 1,
            lineLabels: ['Tuần 1', 'Tuần 2', 'Tuần 3', 'Tuần 4'],
            lineData: [90, 110, 125, 125],
            barLabels: ['Tuần 1', 'Tuần 2', 'Tuần 3', 'Tuần 4'],
            barData: [1, 2, 1, 2]
        }
    };

    // Danh sách bảng xếp hạng điểm nóng giám sát
    const danhSachDiemNong = [
        {
            hang: 1,
            tenDoiTuong: 'LOT-AMOX-2025-09 (Amoxicillin 500mg)',
            phanLoai: 'LO_THUOC',
            donViPhuTrach: 'Dược Phẩm Pharbaco (NSX)',
            soLuotBaoCao: 8,
            hanhViChuYeu: 'Hạn dùng tẩy xóa, độ hòa tan không đạt TCVN',
            mucDoRuiRo: 'NGUY_CAP',
            linkLienKet: '../coQuanQuanLy/quanLyThuHoi.php'
        },
        {
            hang: 2,
            tenDoiTuong: 'Công ty TNHH Dược Thảo Á Châu',
            phanLoai: 'TO_CHUC',
            donViPhuTrach: 'Mã số thuế: 0314999888 (NPP)',
            soLuotBaoCao: 6,
            hanhViChuYeu: 'Giấy phép GDP hết hạn, kho bảo quản ẩm mốc',
            mucDoRuiRo: 'CAO',
            linkLienKet: '../coQuanQuanLy/quanLyQuyenHan.php'
        },
        {
            hang: 3,
            tenDoiTuong: 'LOT-FAKE-9999 (Paracetamol giả)',
            phanLoai: 'LO_THUOC',
            donViPhuTrach: 'Không xác định nguồn gốc trên hệ thống',
            soLuotBaoCao: 5,
            hanhViChuYeu: 'Mã QR không quét được, bao bì in nhòe nhãn mác',
            mucDoRuiRo: 'NGUY_CAP',
            linkLienKet: '../coQuanQuanLy/xuLyNghiVan.php'
        },
        {
            hang: 4,
            tenDoiTuong: 'LOT-CEFA-2024-EX (Cefalexin 500mg)',
            phanLoai: 'LO_THUOC',
            donViPhuTrach: 'Dược Phẩm Medipharco (NSX)',
            soLuotBaoCao: 4,
            hanhViChuYeu: 'Lô thuốc hết hạn nhưng xuất hiện tại đại lý bán buôn',
            mucDoRuiRo: 'TRUNG_BINH',
            linkLienKet: '../coQuanQuanLy/giamSatLuuThong.php'
        },
        {
            hang: 5,
            tenDoiTuong: 'Nhà Thuốc Đại Phúc An (Bán lẻ)',
            phanLoai: 'TO_CHUC',
            donViPhuTrach: 'Mã số thuế: 1801992211 (Nhà thuốc)',
            soLuotBaoCao: 3,
            hanhViChuYeu: 'Người dân quét QR báo cáo bán thuốc không quét mã tại quầy',
            mucDoRuiRo: 'TRUNG_BINH',
            linkLienKet: '../coQuanQuanLy/xuLyNghiVan.php'
        }
    ];

    // ══ 2. KHỞI TẠO BIỂU ĐỒ CHART.JS ══
    let chartDuongInstance = null;
    let chartCotInstance = null;

    function khoiTaoBieuDo(duLieuHienTai) {
        // Biểu đồ đường: Số lô đăng ký mới
        const ctxDuong = document.getElementById('bieuDoDuongDangKy').getContext('2d');
        if (chartDuongInstance) chartDuongInstance.destroy();

        chartDuongInstance = new Chart(ctxDuong, {
            type: 'line',
            data: {
                labels: duLieuHienTai.lineLabels,
                datasets: [{
                    label: 'Số lô thuốc đăng ký mới',
                    data: duLieuHienTai.lineData,
                    borderColor: '#4338ca', // Indigo-700
                    backgroundColor: 'rgba(67, 56, 202, 0.08)',
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#4338ca',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 4.5,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleFont: { family: 'Inter', size: 11 },
                        bodyFont: { family: 'Inter', size: 12 },
                        padding: 10,
                        cornerRadius: 8
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'Inter', size: 11 }, color: '#64748b' }
                    },
                    y: {
                        grid: { color: '#f1f5f9' },
                        ticks: { font: { family: 'Inter', size: 11 }, color: '#64748b', precision: 0 }
                    }
                }
            }
        });

        // Biểu đồ cột: Số vụ thu hồi
        const ctxCot = document.getElementById('bieuDoCotThuHoi').getContext('2d');
        if (chartCotInstance) chartCotInstance.destroy();

        chartCotInstance = new Chart(ctxCot, {
            type: 'bar',
            data: {
                labels: duLieuHienTai.barLabels,
                datasets: [{
                    label: 'Số vụ thu hồi lô thuốc',
                    data: duLieuHienTai.barData,
                    backgroundColor: '#dc2626', // Red-600
                    borderRadius: 6,
                    borderSkipped: false,
                    barThickness: 24
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleFont: { family: 'Inter', size: 11 },
                        bodyFont: { family: 'Inter', size: 12 },
                        padding: 10,
                        cornerRadius: 8
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'Inter', size: 11 }, color: '#64748b' }
                    },
                    y: {
                        grid: { color: '#f1f5f9' },
                        ticks: { font: { family: 'Inter', size: 11 }, color: '#64748b', precision: 0 }
                    }
                }
            }
        });
    }

    // ══ 3. CẬP NHẬT CHỈ SỐ KPI CHÍNH ══
    function capNhatDashboard(khoangThoiGianKey) {
        const d = duLieuTheoKy[khoangThoiGianKey] || duLieuTheoKy['30_NGAY'];

        document.getElementById('kpiTongLoDangKy').textContent = d.tongLo.toLocaleString('vi-VN');
        document.getElementById('kpiTongToChuc').textContent = d.toChuc;
        document.getElementById('kpiLoDangLuuHanh').textContent = d.luuHanh.toLocaleString('vi-VN');
        document.getElementById('kpiLoDaThuHoi').textContent = d.thuHoi;
        document.getElementById('kpiNghiVanChoXuLy').textContent = d.nghiVan;
        document.getElementById('kpiHoSoChoDuyet').textContent = d.choDuyet;

        khoiTaoBieuDo(d);
    }

    // ══ 4. RENDER BẢNG ĐIỂM NÓNG GIÁM SÁT ══
    const thanBangDiemNong = document.getElementById('thanBangDiemNong');

    function renderBangDiemNong() {
        thanBangDiemNong.innerHTML = '';

        danhSachDiemNong.forEach(item => {
            let phanLoaiBadge = '';
            if (item.phanLoai === 'LO_THUOC') {
                phanLoaiBadge = `<span class="badge-chuan px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200"><i class="fa-solid fa-capsules"></i> Lô thuốc</span>`;
            } else {
                phanLoaiBadge = `<span class="badge-chuan px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200"><i class="fa-solid fa-building"></i> Tổ chức</span>`;
            }

            let ruiRoBadge = '';
            if (item.mucDoRuiRo === 'NGUY_CAP') {
                ruiRoBadge = `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-red-100 text-red-700 border border-red-300">Nguy cấp</span>`;
            } else if (item.mucDoRuiRo === 'CAO') {
                ruiRoBadge = `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300">Rủi ro cao</span>`;
            } else {
                ruiRoBadge = `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-300">Trung bình</span>`;
            }

            let hangColor = 'text-slate-700 font-bold';
            if (item.hang === 1) hangColor = 'text-red-600 font-black';
            else if (item.hang === 2) hangColor = 'text-amber-600 font-bold';

            const tr = document.createElement('tr');
            tr.className = 'dong-diem-nong transition-colors';
            tr.innerHTML = `
                <td class="py-3 px-3 text-center font-mono ${hangColor}">${item.hang}</td>
                <td class="py-3 px-3 font-bold text-slate-900 leading-tight">${item.tenDoiTuong}</td>
                <td class="py-3 px-2.5 text-center whitespace-nowrap">${phanLoaiBadge}</td>
                <td class="py-3 px-3 text-slate-700 leading-tight">${item.donViPhuTrach}</td>
                <td class="py-3 px-2.5 text-center font-mono font-bold text-red-700 whitespace-nowrap">${item.soLuotBaoCao} lượt</td>
                <td class="py-3 px-3 text-slate-600 leading-tight">${item.hanhViChuYeu}</td>
                <td class="py-3 px-2.5 text-center whitespace-nowrap">${ruiRoBadge}</td>
                <td class="py-3 px-3 text-center whitespace-nowrap">
                    <a href="${item.linkLienKet}" class="px-2.5 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-800 font-bold text-xs transition-all inline-flex items-center gap-1 border border-indigo-200 shadow-2xs">
                        Kiểm tra <i class="fa-solid fa-arrow-right text-[9px]"></i>
                    </a>
                </td>
            `;
            thanBangDiemNong.appendChild(tr);
        });
    }

    // ══ 5. XỬ LÝ BỘ LỌC THỜI GIAN TOÀN TRANG ══
    const nutLocThoiGian = document.querySelectorAll('.btn-loc-thoigian');
    const khungChonNgay = document.getElementById('khungChonNgayTuyChon');

    nutLocThoiGian.forEach(btn => {
        btn.addEventListener('click', function () {
            nutLocThoiGian.forEach(b => {
                b.className = "btn-loc-thoigian px-3.5 py-1.5 rounded-lg transition-all text-slate-600 hover:text-indigo-900";
            });
            this.className = "btn-loc-thoigian px-3.5 py-1.5 rounded-lg transition-all bg-indigo-700 text-white shadow-2xs font-bold";

            const range = this.getAttribute('data-range');
            if (range === 'TUY_CHON') {
                khungChonNgay.classList.remove('hidden');
            } else {
                khungChonNgay.classList.add('hidden');
                capNhatDashboard(range);
            }
        });
    });

    document.getElementById('nutApDungKhoangNgay').addEventListener('click', function () {
        const start = document.getElementById('inputNgayBatDau').value;
        const end = document.getElementById('inputNgayKetThuc').value;
        if (!start || !end) {
            alert("Vui lòng chọn cả ngày bắt đầu và ngày kết thúc!");
            return;
        }
        capNhatDashboard('TUY_CHON');
    });

    // ══ 6. XUẤT FILE BÁO CÁO GIÁM SÁT (CSV) ══
    document.getElementById('nutXuatBaoCao').addEventListener('click', function () {
        const tieuDe = "BAO_CAO_THONG_KE_GIAM_SAT_DUOC_PHAM_2026.csv";
        let csvContent = "\uFEFF"; // BOM UTF-8
        csvContent += "BAO CAO THONG KE GIAM SAT - CUC QUAN LY DUOC\n";
        csvContent += `Ngay xuat: ${new Date().toLocaleDateString('vi-VN')}\n\n`;
        csvContent += "CHI SO TONG QUAN\n";
        csvContent += "Tong lo da dang ky,1250\n";
        csvContent += "So to chuc dang hoat dong,84\n";
        csvContent += "So lo dang luu hanh,1185\n";
        csvContent += "So lo da thu hoi,18\n";
        csvContent += "Bao cao nghi van cho xu ly,5\n\n";
        csvContent += "DIEM NONG GIAM SAT CO TAN SUAT NGHI VAN CAO\n";
        csvContent += "Hang,Doi tuong giam sat,Phan loai,Don vi phu trach,So luot bao cao,Hanh vi chu yeu,Muc do rui ro\n";

        danhSachDiemNong.forEach(item => {
            csvContent += `${item.hang},"${item.tenDoiTuong}","${item.phanLoai}","${item.donViPhuTrach}",${item.soLuotBaoCao},"${item.hanhViChuYeu}","${item.mucDoRuiRo}"\n`;
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

    // Khởi chạy mặc định 30 ngày qua
    capNhatDashboard('30_NGAY');
    renderBangDiemNong();
});
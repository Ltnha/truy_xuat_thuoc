document.addEventListener('DOMContentLoaded', function () {
    const tabNhapMa = document.getElementById('tabNhapMa');
    const tabQuetQr = document.getElementById('tabQuetQr');
    const khungNhapMa = document.getElementById('khungNhapMa');
    const khungQuetCamera = document.getElementById('khungQuetCamera');
    
    const oNhapMa = document.getElementById('oNhapMa');
    const nutXacNhanTraCuu = document.getElementById('nutXacNhanTraCuu');
    const khuVucKetQua = document.getElementById('khuVucKetQua');

    // Chuyển đổi giữa 2 tab Nhập mã & Quét camera
    tabNhapMa.addEventListener('click', () => {
        tabNhapMa.className = "pb-3 px-6 text-sm font-bold border-b-2 border-emerald-700 text-emerald-700 flex items-center gap-2";
        tabQuetQr.className = "pb-3 px-6 text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-emerald-700 flex items-center gap-2";
        khungNhapMa.classList.remove('hidden');
        khungQuetCamera.classList.add('hidden');
    });

    tabQuetQr.addEventListener('click', () => {
        tabQuetQr.className = "pb-3 px-6 text-sm font-bold border-b-2 border-emerald-700 text-emerald-700 flex items-center gap-2";
        tabNhapMa.className = "pb-3 px-6 text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-emerald-700 flex items-center gap-2";
        khungQuetCamera.classList.remove('hidden');
        khungNhapMa.classList.add('hidden');
    });

    // Bấm nút mẫu thử nhanh
    document.querySelectorAll('.btn-mau').forEach(btn => {
        btn.addEventListener('click', function () {
            oNhapMa.value = this.getAttribute('data-ma');
            thucHienTraCuu(oNhapMa.value.trim());
        });
    });

    nutXacNhanTraCuu.addEventListener('click', () => {
        thucHienTraCuu(oNhapMa.value.trim());
    });

    // Tự động kích hoạt nếu có tham số truyền từ URL
    if (oNhapMa.value.trim() !== '') {
        thucHienTraCuu(oNhapMa.value.trim());
    }

    // Dữ liệu mô phỏng đối soát On-Chain & Off-Chain
    const duLieuMoPhong = {
        'LOT-PARA-2026': {
            trangThai: 'VALID',
            loai: 'MÃ LÔ THUỐC (BATCH)',
            tenThuoc: 'Paracetamol 500mg',
            hamLuongDangBaoChe: 'Viên nén bao phim, 500mg',
            hoatChat: 'Paracetamol',
            soDangKy: 'VD-28491-18',
            nhaSanXuat: 'Công ty Cổ phần Dược phẩm Medipharco',
            ngaySanXuat: '15/01/2026',
            hanSuDung: '15/01/2029',
            batchIdHash: '0x8f2d5e1b4a3c79a2f60e1d8b5c9a4e2f1a3b5c7d9e1f2a4b6c8d0e2f4a6b8c0',
            txHash: '0x3a4b5c6d7e8f9a0b1c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b',
            isDispensed: 'N/A (Cấp Lô)',
            hanhTrinh: [
                { tieuDe: 'Khởi tạo lô thuốc và cấp tem mã on-chain', donVi: 'Medipharco (Nhà máy số 2)', thoiGian: '15/01/2026 08:30', hoanTat: true },
                { tieuDe: 'Xuất kho và chuyển giao đến Nhà Phân Phối', donVi: 'Dược Phẩm Trung Ương 3 (Hà Nội)', thoiGian: '20/01/2026 14:15', hoanTat: true },
                { tieuDe: 'Tiếp nhận kho lẻ và phân phối đến Nhà Thuốc', donVi: 'Nhà thuốc PharmaCare Cần Thơ', thoiGian: '25/01/2026 10:00', hoanTat: true }
            ]
        },
        'LOT-AMOX-2025-09': {
            trangThai: 'RECALLED',
            loai: 'MÃ LÔ THUỐC (BATCH)',
            tenThuoc: 'Amoxicillin Trihydrate 500mg',
            hamLuongDangBaoChe: 'Viên nang cứng, 500mg',
            hoatChat: 'Amoxicillin',
            soDangKy: 'VD-19234-15',
            nhaSanXuat: 'Dược Phẩm Pharbaco',
            ngaySanXuat: '10/09/2025',
            hanSuDung: '10/09/2028',
            batchIdHash: '0x1c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0b1c2',
            txHash: '0x71a49f2b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4e5',
            isDispensed: 'N/A (Đã khóa thu hồi)',
            hanhTrinh: [
                { tieuDe: 'Đăng ký sản xuất lô', donVi: 'Dược Phẩm Pharbaco', thoiGian: '10/09/2025 09:00', hoanTat: true },
                { tieuDe: 'Cơ quan quản lý kích hoạt lệnh THU HỒI trên Smart Contract', donVi: 'Cục Quản Lý Dược (Bộ Y Tế)', thoiGian: '05/02/2026 16:45', hoanTat: true, canhBao: true }
            ]
        },
        'LOT-EXPIRED-2024': {
            trangThai: 'EXPIRED',
            loai: 'MÃ LÔ THUỐC (BATCH)',
            tenThuoc: 'Vitamin C 500mg Kháng Khuẩn',
            hamLuongDangBaoChe: 'Viên sủi, 500mg',
            hoatChat: 'Acid Ascorbic',
            soDangKy: 'VD-11029-14',
            nhaSanXuat: 'Dược Hậu Giang (DHG)',
            ngaySanXuat: '01/01/2023',
            hanSuDung: '01/01/2025',
            batchIdHash: '0x5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0b1c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6',
            txHash: '0x99a8b7c6d5e4f3a2b1c0d9e8f7a6b5c4d3e2f1a0b9c8d7e6f5a4b3c2d1e0f9a',
            isDispensed: 'N/A (Đã hết hạn)',
            hanhTrinh: [
                { tieuDe: 'Đăng ký sản xuất', donVi: 'DHG Pharma', thoiGian: '01/01/2023 08:00', hoanTat: true }
            ]
        },
        'UNIT-PARA-00891': {
            trangThai: 'VALID',
            loai: 'MÃ ĐƠN VỊ HỘP LẺ (UNIT SERIAL)',
            tenThuoc: 'Paracetamol 500mg (Hộp bán lẻ #00891)',
            hamLuongDangBaoChe: 'Hộp 10 vỉ x 10 viên',
            hoatChat: 'Paracetamol',
            soDangKy: 'VD-28491-18',
            nhaSanXuat: 'Công ty Cổ phần Dược phẩm Medipharco',
            ngaySanXuat: '15/01/2026',
            hanSuDung: '15/01/2029',
            batchIdHash: '0x8f2d5e1b4a3c79a2f60e1d8b5c9a4e2f1a3b5c7d9e1f2a4b6c8d0e2f4a6b8c0',
            txHash: '0xfa12345bcde678901234567890abcdef1234567890abcdef1234567890abcdef',
            isDispensed: 'ĐÃ BÁN (DISPENSED)',
            hanhTrinh: [
                { tieuDe: 'Sản xuất và sinh mã serial', donVi: 'Nhà máy Medipharco', thoiGian: '15/01/2026 08:30', hoanTat: true },
                { tieuDe: 'Giao nhận tại Nhà thuốc PharmaCare Cần Thơ', donVi: 'Nhà thuốc PharmaCare Cần Thơ', thoiGian: '25/01/2026 10:00', hoanTat: true },
                { tieuDe: 'Dược sĩ xác nhận bán cho Người tiêu dùng', donVi: 'Quầy bán lẻ số 1 (Dược sĩ Trần Mai)', thoiGian: '02/02/2026 19:20', hoanTat: true }
            ]
        }
    };

    function thucHienTraCuu(ma) {
        if (!ma) {
            alert("Vui lòng nhập mã lô hoặc mã đơn vị thuốc để kiểm tra!");
            return;
        }

        khuVucKetQua.classList.remove('hidden');

        const ketQua = duLieuMoPhong[ma] || {
            trangThai: 'NOT_FOUND',
            loai: 'KHÔNG TÌM THẤY',
            tenThuoc: 'Không có thông tin trong CSDL Blockchain',
            hamLuongDangBaoChe: 'Không xác định',
            hoatChat: 'Không xác định',
            soDangKy: 'Chưa cấp phép',
            nhaSanXuat: 'Không rõ',
            ngaySanXuat: '--',
            hanSuDung: '--',
            batchIdHash: '0x0000000000000000000000000000000000000000',
            txHash: '0x0000000000000000000000000000000000000000',
            isDispensed: 'KHÔNG TỒN TẠI',
            hanhTrinh: []
        };

        capNhatGiaoDien(ma, ketQua);
        luuVaoLocalStorage(ma, ketQua);
    }

    function capNhatGiaoDien(ma, kq) {
        const banner = document.getElementById('bannerTrangThai');
        const bieuTuong = document.getElementById('bieuTuongTrangThai');
        const theTrangThaiText = document.getElementById('theTrangThaiText');
        const loaiMa = document.getElementById('loaiMaKiemTra');
        const tieuDe = document.getElementById('tieuDeTrangThai');
        const moTa = document.getElementById('moTaTrangThai');
        const khoiChiTiet = document.getElementById('khoiChiTietThuoc');

        loaiMa.textContent = kq.loai;

        // Xử lý 5 trạng thái quy chuẩn
        if (kq.trangThai === 'VALID') {
            banner.className = "p-6 rounded-2xl border border-emerald-200 bg-emerald-50/60 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bong-xanh";
            bieuTuong.className = "w-14 h-14 rounded-xl flex items-center justify-center text-3xl shrink-0 bg-emerald-100 text-emerald-700";
            bieuTuong.innerHTML = '<i class="fa-solid fa-circle-check"></i>';
            theTrangThaiText.className = "px-3 py-1 rounded-full text-xs font-extrabold uppercase tracking-wide bg-emerald-700 text-white";
            theTrangThaiText.textContent = "HỢP LỆ (VALID)";
            tieuDe.className = "text-lg sm:text-xl font-bold mt-1 text-emerald-800";
            tieuDe.textContent = "Sản phẩm chính hãng, đầy đủ kiểm chứng Blockchain";
            moTa.textContent = "Lô thuốc đang trong trạng thái lưu thông an toàn, nguồn gốc từ nhà sản xuất hợp lệ.";
            khoiChiTiet.classList.remove('opacity-50', 'pointer-events-none');
        } else if (kq.trangThai === 'RECALLED') {
            banner.className = "p-6 rounded-2xl border border-red-200 bg-red-50/70 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bong-xanh";
            bieuTuong.className = "w-14 h-14 rounded-xl flex items-center justify-center text-3xl shrink-0 bg-red-100 text-red-600";
            bieuTuong.innerHTML = '<i class="fa-solid fa-ban"></i>';
            theTrangThaiText.className = "px-3 py-1 rounded-full text-xs font-extrabold uppercase tracking-wide bg-red-600 text-white";
            theTrangThaiText.textContent = "ĐÃ THU HỒI (RECALLED)";
            tieuDe.className = "text-lg sm:text-xl font-bold mt-1 text-red-700";
            tieuDe.textContent = "CẢNH BÁO: Thuốc đã có quyết định thu hồi khẩn cấp!";
            moTa.textContent = "Tuyệt đối không sử dụng sản phẩm thuộc lô này. Vui lòng liên hệ cơ sở y tế gần nhất.";
            khoiChiTiet.classList.remove('opacity-50', 'pointer-events-none');
        } else if (kq.trangThai === 'EXPIRED') {
            banner.className = "p-6 rounded-2xl border border-amber-200 bg-amber-50/70 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bong-xanh";
            bieuTuong.className = "w-14 h-14 rounded-xl flex items-center justify-center text-3xl shrink-0 bg-amber-100 text-amber-600";
            bieuTuong.innerHTML = '<i class="fa-solid fa-hourglass-end"></i>';
            theTrangThaiText.className = "px-3 py-1 rounded-full text-xs font-extrabold uppercase tracking-wide bg-amber-600 text-white";
            theTrangThaiText.textContent = "QUÁ HẠN DÙNG (EXPIRED)";
            tieuDe.className = "text-lg sm:text-xl font-bold mt-1 text-amber-700";
            tieuDe.textContent = "Lô thuốc đã quá hạn sử dụng";
            moTa.textContent = "Hạn dùng sản phẩm đã kết thúc. Nhà thuốc bị cấm phân phối đơn vị thuốc này.";
            khoiChiTiet.classList.remove('opacity-50', 'pointer-events-none');
        } else {
            // NOT_FOUND hoặc INVALID
            banner.className = "p-6 rounded-2xl border border-slate-300 bg-slate-50 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bong-xanh";
            bieuTuong.className = "w-14 h-14 rounded-xl flex items-center justify-center text-3xl shrink-0 bg-slate-200 text-slate-500";
            bieuTuong.innerHTML = '<i class="fa-solid fa-circle-question"></i>';
            theTrangThaiText.className = "px-3 py-1 rounded-full text-xs font-extrabold uppercase tracking-wide bg-slate-600 text-white";
            theTrangThaiText.textContent = "KHÔNG TỒN TẠI (NOT FOUND)";
            tieuDe.className = "text-lg sm:text-xl font-bold mt-1 text-slate-900";
            tieuDe.textContent = "Cảnh báo nghi vấn: Không tìm thấy mã trên Blockchain!";
            moTa.textContent = "Mã QR hoặc chuỗi định danh này chưa từng được đăng ký bởi bất kỳ đơn vị hợp pháp nào.";
            khoiChiTiet.classList.add('opacity-50', 'pointer-events-none');
        }

        // Đổ dữ liệu chi tiết
        document.getElementById('tenThuoc').textContent = kq.tenThuoc;
        document.getElementById('soDkLuuHanh').textContent = kq.soDangKy;
        document.getElementById('hamLuongDangBaoChe').textContent = kq.hamLuongDangBaoChe;
        document.getElementById('hoatChat').textContent = kq.hoatChat;
        document.getElementById('tenNhaSanXuat').textContent = kq.nhaSanXuat;
        document.getElementById('ngaySanXuat').textContent = kq.ngaySanXuat;
        document.getElementById('hanSuDung').textContent = kq.hanSuDung;

        document.getElementById('batchIdHash').textContent = kq.batchIdHash;
        document.getElementById('txHashKhoiTao').textContent = kq.txHash;
        
        const coBanLe = document.getElementById('coBanLe');
        coBanLe.textContent = kq.isDispensed;
        coBanLe.className = kq.isDispensed.includes('ĐÃ BÁN') 
            ? 'font-bold px-2 py-0.5 rounded text-[11px] inline-block bg-emerald-100 text-emerald-800' 
            : 'font-bold px-2 py-0.5 rounded text-[11px] inline-block bg-slate-100 text-slate-600';

        // Render timeline hành trình
        const dongThoiGian = document.getElementById('dongThoiGianHanhTrinh');
        dongThoiGian.innerHTML = '';
        if (kq.hanhTrinh.length === 0) {
            dongThoiGian.innerHTML = '<p class="text-xs text-slate-500 italic">Không có dữ liệu hành trình chuyển giao.</p>';
        } else {
            kq.hanhTrinh.forEach(b => {
                const step = document.createElement('div');
                step.className = "relative space-y-1";
                step.innerHTML = `
                    <span class="absolute -left-[31px] top-1 w-3.5 h-3.5 rounded-full ${b.canhBao ? 'bg-red-600' : 'bg-emerald-700'} ring-4 ring-white"></span>
                    <div class="text-xs font-bold ${b.canhBao ? 'text-red-600' : 'text-slate-900'}">${b.tieuDe}</div>
                    <div class="text-[11px] text-slate-600"><i class="fa-solid fa-location-dot text-cyan-800 mr-1"></i> ${b.donVi}</div>
                    <div class="text-[10px] text-slate-400 font-mono"><i class="fa-regular fa-clock mr-1"></i> ${b.thoiGian}</div>
                `;
                dongThoiGian.appendChild(step);
            });
        }
    }

    // Tự động lưu cục bộ cho trang lichSuTraCuu.php (FR12)
    function luuVaoLocalStorage(ma, kq) {
        let lichSu = JSON.parse(localStorage.getItem('pharma_lich_su_tra_cuu') || '[]');
        lichSu = lichSu.filter(item => item.ma !== ma);
        lichSu.unshift({
            ma: ma,
            tenThuoc: kq.tenThuoc,
            trangThai: kq.trangThai,
            thoiGian: new Date().toLocaleString('vi-VN')
        });
        if (lichSu.length > 20) lichSu.pop();
        localStorage.setItem('pharma_lich_su_tra_cuu', JSON.stringify(lichSu));
    }
});
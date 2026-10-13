document.addEventListener('DOMContentLoaded', function () {
    const KEY_STORAGE = 'pharma_lich_su_tra_cuu';

    const khungDangTai = document.getElementById('khungDangTai');
    const khungBangLichSu = document.getElementById('khungBangLichSu');
    const thanBangLichSu = document.getElementById('thanBangLichSu');
    const khungLichSuRong = document.getElementById('khungLichSuRong');
    
    const nutXoaLichSu = document.getElementById('nutXoaLichSu');
    const nutXoaLichSuMobile = document.getElementById('nutXoaLichSuMobile');
    
    const modalXacNhanXoa = document.getElementById('modalXacNhanXoa');
    const nutHuyXoa = document.getElementById('nutHuyXoa');
    const nutDongYXoa = document.getElementById('nutDongYXoa');

    // Dữ liệu Smart Contract On-Chain mới nhất (dùng để đối soát lại khi tải trang)
    const duLieuOnChainMoiNhat = {
        'LOT-PARA-2026': {
            trangThai: 'VALID',
            tenThuoc: 'Paracetamol 500mg'
        },
        'LOT-AMOX-2025-09': {
            trangThai: 'RECALLED', // Ví dụ: lô này bị cơ quan quản lý kích hoạt thu hồi
            tenThuoc: 'Amoxicillin Trihydrate 500mg'
        },
        'LOT-EXPIRED-2024': {
            trangThai: 'EXPIRED',
            tenThuoc: 'Vitamin C 500mg Kháng Khuẩn'
        },
        'UNIT-PARA-00891': {
            trangThai: 'VALID',
            tenThuoc: 'Paracetamol 500mg (Hộp bán lẻ #00891)'
        }
    };

    // Hàm lấy danh sách từ localStorage
    function layDanhSachLichSu() {
        try {
            return JSON.parse(localStorage.getItem(KEY_STORAGE)) || [];
        } catch (e) {
            return [];
        }
    }

    // Hàm truy vấn và làm mới trạng thái từ blockchain cho từng phần tử
    async function lamMoiVaHienThiLichSu() {
        const danhSach = layDanhSachLichSu();

        khungDangTai.classList.remove('hidden');
        khungBangLichSu.classList.add('hidden');
        khungLichSuRong.classList.add('hidden');

        // Giả lập độ trễ truy vấn mạng Blockchain (300ms)
        await new Promise(resolve => setTimeout(resolve, 300));
        khungDangTai.classList.add('hidden');

        if (!danhSach || danhSach.length === 0) {
            khungLichSuRong.classList.remove('hidden');
            nutXoaLichSu.classList.add('hidden');
            nutXoaLichSuMobile.classList.add('hidden');
            return;
        }

        // Hiển thị nút xóa lịch sử
        nutXoaLichSu.classList.remove('hidden');
        nutXoaLichSuMobile.classList.remove('hidden');
        khungBangLichSu.classList.remove('hidden');

        thanBangLichSu.innerHTML = '';

        danhSach.forEach(item => {
            // TỰ ĐỘNG GỌI LẠI ĐỐI SOÁT TRẠNG THÁI HIỆN TẠI (Không dùng trạng thái cũ đã lưu)
            const thongTinMoi = duLieuOnChainMoiNhat[item.ma] || {
                trangThai: 'NOT_FOUND',
                tenThuoc: item.tenThuoc || 'Không có trong CSDL Blockchain'
            };

            const trangThaiHienTai = thongTinMoi.trangThai;
            const tenThuocHienTai = thongTinMoi.tenThuoc;

            // Xác định giao diện nhãn trạng thái (Badge)
            let nhanTrangThaiHtml = '';
            if (trangThaiHienTai === 'VALID') {
                nhanTrangThaiHtml = `<span class="px-2.5 py-1 rounded-full text-[11px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-200"><i class="fa-solid fa-circle-check mr-1"></i> HỢP LỆ</span>`;
            } else if (trangThaiHienTai === 'RECALLED') {
                nhanTrangThaiHtml = `<span class="px-2.5 py-1 rounded-full text-[11px] font-extrabold bg-red-100 text-red-700 border border-red-200"><i class="fa-solid fa-ban mr-1"></i> ĐÃ THU HỒI</span>`;
            } else if (trangThaiHienTai === 'EXPIRED') {
                nhanTrangThaiHtml = `<span class="px-2.5 py-1 rounded-full text-[11px] font-extrabold bg-amber-100 text-amber-700 border border-amber-200"><i class="fa-solid fa-hourglass-end mr-1"></i> QUÁ HẠN</span>`;
            } else {
                nhanTrangThaiHtml = `<span class="px-2.5 py-1 rounded-full text-[11px] font-extrabold bg-slate-100 text-slate-600 border border-slate-200"><i class="fa-solid fa-circle-question mr-1"></i> KHÔNG TỒN TẠI</span>`;
            }

            // Tạo dòng bảng
            const tr = document.createElement('tr');
            tr.className = 'dong-lich-su';
            tr.innerHTML = `
                <td class="py-3.5 px-4 font-semibold text-slate-900">${tenThuocHienTai}</td>
                <td class="py-3.5 px-4 font-mono font-bold text-cyan-800">${item.ma}</td>
                <td class="py-3.5 px-4">${nhanTrangThaiHtml}</td>
                <td class="py-3.5 px-4 text-slate-500 font-mono text-[11px]"><i class="fa-regular fa-clock mr-1 text-slate-400"></i> ${item.thoiGian || '--'}</td>
                <td class="py-3.5 px-4 text-center">
                    <button type="button" class="nut-xem-chi-tiet px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 font-bold text-[11px] transition-all">
                        Xem Kết Quả <i class="fa-solid fa-arrow-right ml-1"></i>
                    </button>
                </td>
            `;

            // CLICK VÀO 1 DÒNG → MỞ LẠI ĐÚNG GIAO DIỆN KẾT QUẢ CỦA TRANG TRƯỚC
            tr.addEventListener('click', function () {
                window.location.href = 'traCuuNguonGoc.php?maDinhDanh=' + encodeURIComponent(item.ma);
            });

            thanBangLichSu.appendChild(tr);
        });
    }

    // Xử lý bật modal xác nhận xóa
    function batModalXoa() {
        modalXacNhanXoa.classList.remove('hidden');
    }

    function tatModalXoa() {
        modalXacNhanXoa.classList.add('hidden');
    }

    nutXoaLichSu.addEventListener('click', batModalXoa);
    nutXoaLichSuMobile.addEventListener('click', batModalXoa);
    nutHuyXoa.addEventListener('click', tatModalXoa);

    // Xác nhận xóa lịch sử
    nutDongYXoa.addEventListener('click', function () {
        localStorage.removeItem(KEY_STORAGE);
        tatModalXoa();
        lamMoiVaHienThiLichSu();
    });

    // Khởi chạy khi nạp trang
    lamMoiVaHienThiLichSu();
});
document.addEventListener('DOMContentLoaded', function () {
    // 1. LẤY CÁC THẦN PHẦN GIAO DIỆN THEO ĐÚNG ID TRONG BLADE
    const thanBang = document.getElementById('thanBangLoThuoc');
    const tongSoLoText = document.getElementById('tongSoLoHienThi');
    const thongTinPhanTrangText = document.getElementById('thongTinPhanTrangLo');
    const cumNutPhanTrang = document.getElementById('cumNutPhanTrangLo');

    const oTimKiem = document.getElementById('oTimKiemLo');
    const locTrangThai = document.getElementById('locTrangThaiLo');
    const locHanDung = document.getElementById('locHanDung');

    // Biến toàn cục quản lý dữ liệu & phân trang
    let tatCaLoThuoc = [];
    let danhSachDaLoc = [];
    let trangHienTai = 1;
    const soDongTrenTrang = 5;

    // 2. TẢI DỮ LIỆU TỪ CSDL MYSQL QUA API
    async function loadDataFromDB() {
        try {
            thanBang.innerHTML = `
                <tr>
                    <td colspan="9" class="py-8 text-center text-slate-500">
                        <i class="fa-solid fa-spinner fa-spin text-emerald-700 text-xl mb-2 block"></i>
                        Đang truy vấn danh sách lô thuốc từ CSDL MySQL...
                    </td>
                </tr>`;

            const response = await fetch('/nha-san-xuat/api/danh-sach-lo-thuoc');
            const result = await response.json();

            if (result.success) {
                tatCaLoThuoc = result.data || [];
                apDungBoLocVaphanTrang();
            } else {
                thanBang.innerHTML = `<tr><td colspan="9" class="py-6 text-center text-red-500 font-bold">Không thể lấy dữ liệu lô thuốc!</td></tr>`;
            }
        } catch (error) {
            console.error('Lỗi khi tải dữ liệu CSDL:', error);
            thanBang.innerHTML = `<tr><td colspan="9" class="py-6 text-center text-red-500 font-bold">Lỗi kết nối máy chủ CSDL!</td></tr>`;
        }
    }

    // 3. XỬ LÝ LỌC DỮ LIỆU & PHÂN TRANG
    function apDungBoLocVaphanTrang() {
        const tuKao = oTimKiem ? oTimKiem.value.toLowerCase().trim() : '';
        const tt = locTrangThai ? locTrangThai.value : 'TAT_CA';
        const hd = locHanDung ? locHanDung.value : 'TAT_CA';

        const today = new Date();
        const sixMonthsLater = new Date();
        sixMonthsLater.setMonth(today.getMonth() + 6);

        danhSachDaLoc = tatCaLoThuoc.filter(item => {
            // Lọc theo từ khóa
            const maLo = (item.maLoNghiepVu || '').toLowerCase();
            const tenThuoc = (item.san_pham?.tenSanPham || '').toLowerCase();
            const khớpTuKhoa = maLo.includes(tuKao) || tenThuoc.includes(tuKao);

            // Lọc theo trạng thái
            const khớpTrangThai = (tt === 'TAT_CA') || (item.trangThai === tt);

            // Lọc theo hạn dùng
            let khớpHanDung = true;
            if (item.hanSuDung) {
                const ngayHetHan = new Date(item.hanSuDung);
                if (hd === 'CON_HAN') khớpHanDung = ngayHetHan > today;
                else if (hd === 'CAN_HAN') khớpHanDung = ngayHetHan > today && ngayHetHan <= sixMonthsLater;
                else if (hd === 'DA_HET_HAN') khớpHanDung = ngayHetHan <= today;
            }

            return khớpTuKhoa && khớpTrangThai && khớpHanDung;
        });

        trangHienTai = 1;
        renderBangAndPhanTrang();
    }

    // 4. HIỂN THỊ CÁC DÒNG VÀO BẢNG & THANH PHÂN TRANG
    function renderBangAndPhanTrang() {
        const tongSoRecord = danhSachDaLoc.length;
        if (tongSoLoText) tongSoLoText.innerText = `Hiển thị: ${tongSoRecord} lô`;

        if (tongSoRecord === 0) {
            thanBang.innerHTML = `<tr><td colspan="9" class="py-8 text-center text-slate-400">Không tìm thấy lô thuốc nào phù hợp.</td></tr>`;
            if (thongTinPhanTrangText) thongTinPhanTrangText.innerText = 'Đang hiển thị 0 lô';
            if (cumNutPhanTrang) cumNutPhanTrang.innerHTML = '';
            return;
        }

        const tongSoTrang = Math.ceil(tongSoRecord / soDongTrenTrang);
        if (trangHienTai > tongSoTrang) trangHienTai = tongSoTrang;

        const batDau = (trangHienTai - 1) * soDongTrenTrang;
        const ketThuc = Math.min(batDau + soDongTrenTrang, tongSoRecord);
        const danhSachTrang = danhSachDaLoc.slice(batDau, ketThuc);

        // Render từng dòng HTML
        thanBang.innerHTML = danhSachTrang.map(item => {
            const txHash = (item.blockchain_transactions && item.blockchain_transactions.length > 0)
                ? item.blockchain_transactions[0].txHash
                : null;

            return `
                <tr class="hover:bg-slate-50/80 transition-colors">
                    <td class="py-3 px-2.5 font-mono font-bold text-slate-900 whitespace-nowrap">${item.maLoNghiepVu}</td>
                    <td class="py-3 px-2 text-center whitespace-nowrap">
                        <button type="button" class="text-emerald-700 hover:text-emerald-800 font-bold text-sm" title="Xem mã QR">
                            <i class="fa-solid fa-qrcode"></i>
                        </button>
                    </td>
                    <td class="py-3 px-2 whitespace-nowrap font-mono text-[11px]">
                        ${txHash 
                            ? `<span class="text-cyan-800 bg-cyan-50 px-2 py-0.5 rounded border border-cyan-200" title="${txHash}">${txHash.substring(0, 8)}...${txHash.substring(txHash.length - 6)}</span>` 
                            : `<span class="text-slate-400 italic">Chưa đồng bộ</span>`}
                    </td>
                    <td class="py-3 px-2.5">
                        <div class="font-bold text-slate-800">${item.san_pham?.tenSanPham || 'N/A'}</div>
                        <div class="text-[10px] text-slate-400 font-mono">SĐK: ${item.san_pham?.soDangKy || 'N/A'}</div>
                    </td>
                    <td class="py-3 px-2 text-center font-mono text-slate-700 whitespace-nowrap">${formatDate(item.hanSuDung)}</td>
                    <td class="py-3 px-2 text-right font-mono font-bold text-slate-800 whitespace-nowrap">${new Number(item.soLuong).toLocaleString()}</td>
                    <td class="py-3 px-2 text-right font-mono font-bold text-emerald-700 whitespace-nowrap">${new Number(item.soLuong).toLocaleString()}</td>
                    <td class="py-3 px-2 text-center whitespace-nowrap">${renderBadgeTrangThai(item.trangThai)}</td>
                    <td class="py-3 px-2.5 text-center whitespace-nowrap">
                        <div class="flex items-center justify-center gap-1.5">
                            <a href="/nha-san-xuat/lo-thuoc/${item.id}/dong-bo-sepolia" class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-lg text-[11px] font-bold transition-all" title="Đồng bộ Sepolia">
                                <i class="fa-solid fa-cloud-arrow-up me-1"></i> Đồng bộ
                            </a>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        // Cập nhật text thông tin phân trang
        if (thongTinPhanTrangText) {
            thongTinPhanTrangText.innerText = `Đang hiển thị ${batDau + 1} đến ${ketThuc} trong tổng số ${tongSoRecord} lô`;
        }

        // Cập nhật các nút bấm phân trang
        renderNutPhanTrang(tongSoTrang);
    }

    function renderNutPhanTrang(tongSoTrang) {
        if (!cumNutPhanTrang) return;
        let htmlButtons = '';

        // Nút Trước
        htmlButtons += `
            <button type="button" class="px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50 font-bold disabled:opacity-40 disabled:cursor-not-allowed" ${trangHienTai === 1 ? 'disabled' : ''} onclick="chuyenTrangLo(${trangHienTai - 1})">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
        `;

        // Số trang
        for (let i = 1; i <= tongSoTrang; i++) {
            if (i === trangHienTai) {
                htmlButtons += `<button type="button" class="px-3 py-1.5 bg-emerald-700 text-white rounded-lg font-bold">${i}</button>`;
            } else {
                htmlButtons += `<button type="button" class="px-3 py-1.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-lg font-bold" onclick="chuyenTrangLo(${i})">${i}</button>`;
            }
        }

        // Nút Sau
        htmlButtons += `
            <button type="button" class="px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50 font-bold disabled:opacity-40 disabled:cursor-not-allowed" ${trangHienTai === tongSoTrang ? 'disabled' : ''} onclick="chuyenTrangLo(${trangHienTai + 1})">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        `;

        cumNutPhanTrang.innerHTML = htmlButtons;
    }

    // Hàm chuyển trang cho các nút onclick
    window.chuyenTrangLo = function (trangMoi) {
        trangHienTai = trangMoi;
        renderBangAndPhanTrang();
    };

    function renderBadgeTrangThai(trangThai) {
        switch (trangThai) {
            case 'CREATED':
                return `<span class="px-2 py-0.5 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-full text-[10px] font-bold">Đã tạo (Mới)</span>`;
            case 'IN_TRANSIT':
                return `<span class="px-2 py-0.5 bg-cyan-50 text-cyan-800 border border-cyan-200 rounded-full text-[10px] font-bold">Đang phân phối</span>`;
            case 'RECALLED':
                return `<span class="px-2 py-0.5 bg-red-50 text-red-700 border border-red-200 rounded-full text-[10px] font-bold">Đã thu hồi</span>`;
            default:
                return `<span class="px-2 py-0.5 bg-slate-100 text-slate-700 border border-slate-200 rounded-full text-[10px] font-bold">${trangThai || 'Khởi tạo'}</span>`;
        }
    }

    function formatDate(dateStr) {
        if (!dateStr) return 'N/A';
        const d = new Date(dateStr);
        return d.toLocaleDateString('vi-VN');
    }

    // 5. GẮN SỰ KIỆN LỌC DỮ LIỆU
    if (oTimKiem) oTimKiem.addEventListener('input', apDungBoLocVaphanTrang);
    if (locTrangThai) locTrangThai.addEventListener('change', apDungBoLocVaphanTrang);
    if (locHanDung) locHanDung.addEventListener('change', apDungBoLocVaphanTrang);

    // 6. SỰ KIỆN MỞ / ĐÓNG MODAL ĐĂNG KÝ LÔ MỚI
    const nutMoModal = document.getElementById('nutMoModalDangKyLo');
    const modalDangKy = document.getElementById('modalDangKyLo');
    const cacNutDongModal = document.querySelectorAll('.dong-modal-lo');

    if (nutMoModal && modalDangKy) {
        nutMoModal.addEventListener('click', () => modalDangKy.classList.remove('hidden'));
    }

    cacNutDongModal.forEach(btn => {
        btn.addEventListener('click', () => {
            if (modalDangKy) modalDangKy.classList.add('hidden');
        });
    });

    // Tải dữ liệu từ MySQL khi vào trang
    loadDataFromDB();
    // XỬ LÝ SỰ KIỆN SUBMIT FORM ĐĂNG KÝ LÔ MỚI
    const formDangKyLo = document.getElementById('formSubmitDangKyLo');
    if (formDangKyLo) {
        formDangKyLo.addEventListener('submit', async function (e) {
            e.preventDefault();

            // Lấy URL động được sinh từ route() của Laravel
            const targetUrl = this.getAttribute('data-url') || '/nha-san-xuat/api/tao-lo-thuoc';

            // Lấy CSRF token an toàn
            const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';

            if (!csrfToken) {
                alert('Lỗi: Chưa bổ sung meta[name="csrf-token"] vào thẻ <head>!');
                return;
            }

            const payload = {
                sanPhamId: document.getElementById('chonSanPhamDaDuyet').value,
                maLoNghiepVu: document.getElementById('nhapMaLo').value,
                soLuong: document.getElementById('nhapSoLuong').value,
                ngaySanXuat: document.getElementById('nhapNgaySX').value,
                hanSuDung: document.getElementById('nhapHanDung').value
            };

            try {
                const response = await fetch('/nha-san-xuat/api/tao-lo-thuoc', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json', // Ép máy chủ trả về JSON
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    // Đóng Modal đăng ký lô
                    const modalDangKy = document.getElementById('modalDangKyLo');
                    if (modalDangKy) modalDangKy.classList.add('hidden');

                    alert(result.message || 'Đăng ký lô thuốc thành công!');

                    // Tải lại bảng dữ liệu MySQL
                    if (typeof loadDataFromDB === 'function') {
                        loadDataFromDB();
                    }
                } else {
                    // Hiển thị thông báo lỗi cụ thể từ Laravel (ví dụ: trùng mã lô, thiếu dữ liệu)
                    alert('Đăng ký thất bại: ' + (result.message || 'Thông tin nhập vào không hợp lệ hoặc mã lô đã tồn tại!'));
                }
            } catch (error) {
                console.error('Chi tiết lỗi:', error);
                alert('Đã xảy ra lỗi kết nối hoặc phản hồi máy chủ không hợp lệ! Mở F12 -> Console để xem chi tiết.');
            }
        });
    }
});
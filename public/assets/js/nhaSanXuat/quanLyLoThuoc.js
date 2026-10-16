document.addEventListener('DOMContentLoaded', function () {
    // 1. LẤY CÁC THÀNH PHẦN GIAO DIỆN THEO ID TRONG BLADE
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
            if (thanBang) {
                thanBang.innerHTML = `
                    <tr>
                        <td colspan="9" class="py-8 text-center text-slate-500">
                            <i class="fa-solid fa-spinner fa-spin text-emerald-700 text-xl mb-2 block"></i>
                            Đang truy vấn danh sách lô thuốc từ CSDL...
                        </td>
                    </tr>`;
            }

            const response = await fetch('/nha-san-xuat/api/danh-sach-lo-thuoc');
            const result = await response.json();

            if (result.success) {
                tatCaLoThuoc = result.data || [];
                apDungBoLocVaphanTrang();
            } else {
                if (thanBang) {
                    thanBang.innerHTML = `<tr><td colspan="9" class="py-6 text-center text-red-500 font-bold">Không thể lấy dữ liệu lô thuốc!</td></tr>`;
                }
            }
        } catch (error) {
            console.error('Lỗi khi tải dữ liệu CSDL:', error);
            if (thanBang) {
                thanBang.innerHTML = `<tr><td colspan="9" class="py-6 text-center text-red-500 font-bold">Lỗi kết nối máy chủ CSDL!</td></tr>`;
            }
        }
    }

    // 3. XỬ LÝ LỌC DỮ LIỆU & PHÂN TRANG
    function apDungBoLocVaphanTrang() {
        const tuKhoa = oTimKiem ? oTimKiem.value.toLowerCase().trim() : '';
        const tt = locTrangThai ? locTrangThai.value : 'TAT_CA';
        const hd = locHanDung ? locHanDung.value : 'TAT_CA';

        const today = new Date();
        const sixMonthsLater = new Date();
        sixMonthsLater.setMonth(today.getMonth() + 6);

        danhSachDaLoc = tatCaLoThuoc.filter(item => {
            // Lọc theo từ khóa
            const maLo = (item.maLoNghiepVu || '').toLowerCase();
            const tenThuoc = (item.san_pham?.tenSanPham || '').toLowerCase();
            const khopTuKhoa = maLo.includes(tuKhoa) || tenThuoc.includes(tuKhoa);

            // Lọc theo trạng thái
            const khopTrangThai = (tt === 'TAT_CA') || (item.trangThai === tt);

            // Lọc theo hạn dùng
            let khopHanDung = true;
            if (item.hanSuDung) {
                const ngayHetHan = new Date(item.hanSuDung);
                if (hd === 'CON_HAN') khopHanDung = ngayHetHan > today;
                else if (hd === 'CAN_HAN') khopHanDung = ngayHetHan > today && ngayHetHan <= sixMonthsLater;
                else if (hd === 'DA_HET_HAN') khopHanDung = ngayHetHan <= today;
            }

            return khopTuKhoa && khopTrangThai && khopHanDung;
        });

        trangHienTai = 1;
        renderBangAndPhanTrang();
    }

    // 4. HIỂN THỊ DỮ LIỆU VÀO BẢNG & THANH PHÂN TRANG
    function renderBangAndPhanTrang() {
        const tongSoRecord = danhSachDaLoc.length;
        if (tongSoLoText) tongSoLoText.innerText = `Hiển thị: ${tongSoRecord} lô`;

        if (!thanBang) return;

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
                            ? `<a href="https://sepolia.etherscan.io/tx/${txHash}" target="_blank" class="text-cyan-800 bg-cyan-50 px-2 py-0.5 rounded border border-cyan-200 hover:underline" title="${txHash}">${txHash.substring(0, 8)}...${txHash.substring(txHash.length - 6)}</a>` 
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
                            <a href="/nha-san-xuat/lo-thuoc/${item.id}/dong-bo-sepolia" class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-lg text-[11px] font-bold transition-all" title="Xem chi tiết đồng bộ">
                                <i class="fa-solid fa-link me-1"></i> Chi tiết
                            </a>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        if (thongTinPhanTrangText) {
            thongTinPhanTrangText.innerText = `Đang hiển thị ${batDau + 1} đến ${ketThuc} trong tổng số ${tongSoRecord} lô`;
        }

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

    // 5. GẮN SỰ KIỆN LỌC
    if (oTimKiem) oTimKiem.addEventListener('input', apDungBoLocVaphanTrang);
    if (locTrangThai) locTrangThai.addEventListener('change', apDungBoLocVaphanTrang);
    if (locHanDung) locHanDung.addEventListener('change', apDungBoLocVaphanTrang);

    // 6. QUẢN LÝ MODAL ĐĂNG KÝ LÔ MỚI
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

    // 7. HÀM CHUẨN HÓA DỮ LIỆU BYTES32 (ĐẢM BẢO CHÍNH XÁC 64 HẠT HEX = 32 BYTES)
    function formatBytes32(hashStr) {
        if (!hashStr) return '0'.repeat(64);
        let clean = hashStr.toString().replace(/^0x/i, '').trim();
        clean = clean.padStart(64, '0');
        return clean.slice(-64);
    }

    // 8. XỬ LÝ SUBMIT FORM & TỰ ĐỘNG KÍCH HOẠT METAMASK POP-UP
    const formDangKyLo = document.getElementById('formSubmitDangKyLo');
    if (formDangKyLo) {
        formDangKyLo.addEventListener('submit', async function (e) {
            e.preventDefault();

            // Kiểm tra MetaMask
            if (typeof window.ethereum === 'undefined') {
                alert('Vui lòng cài đặt tiện ích ví MetaMask trên trình duyệt để sử dụng tính năng đồng bộ Blockchain!');
                return;
            }

            const btnSubmit = this.querySelector('button[type="submit"]');
            const originalBtnContent = btnSubmit ? btnSubmit.innerHTML : 'Xác nhận đăng ký';

            const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';
            const contractMeta = document.querySelector('meta[name="contract-address"]');
            const contractAddress = contractMeta ? contractMeta.getAttribute('content') : '';

            if (!csrfToken) {
                alert('Lỗi: Chưa bổ sung meta[name="csrf-token"] trong thẻ <head>!');
                return;
            }

            if (!contractAddress) {
                alert('Lỗi: Chưa bổ sung meta[name="contract-address"] trong thẻ <head>!');
                return;
            }

            const payload = {
                sanPhamId: document.getElementById('chonSanPhamDaDuyet')?.value,
                maLoNghiepVu: document.getElementById('nhapMaLo')?.value,
                soLuong: document.getElementById('nhapSoLuong')?.value,
                ngaySanXuat: document.getElementById('nhapNgaySX')?.value,
                hanSuDung: document.getElementById('nhapHanDung')?.value
            };

            try {
                // Khóa nút bấm & đổi giao diện trạng thái
                if (btnSubmit) {
                    btnSubmit.disabled = true;
                    btnSubmit.classList.add('opacity-75', 'cursor-not-allowed');
                    btnSubmit.innerHTML = `<i class="fa-solid fa-spinner fa-spin me-2"></i> Đang kết nối ví MetaMask...`;
                }

                // 1. Lấy ví người dùng từ MetaMask
                const accounts = await window.ethereum.request({ method: 'eth_requestAccounts' });
                const userAccount = accounts[0];

                // 2. Tạo lô trước trong CSDL MySQL & lấy 4 mã bytes32 pre-computed
                const targetUrl = this.getAttribute('data-url') || '/nha-san-xuat/api/tao-lo-thuoc';
                const response = await fetch(targetUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Không thể khởi tạo thông tin lô trong CSDL MySQL!');
                }

                const loThuocId = result.data.id;
                const { batchId, businessId, eventType, payloadHash } = result.hashes;

                if (btnSubmit) {
                    btnSubmit.innerHTML = `<i class="fa-solid fa-wallet me-2"></i> Vui lòng xác nhận trên cửa sổ MetaMask...`;
                }

                // 3. Chuẩn hóa 4 tham số bytes32 (mỗi tham số đúng 64 ký tự hex)
                const p1 = formatBytes32(batchId);
                const p2 = formatBytes32(businessId);
                const p3 = formatBytes32(eventType);
                const p4 = formatBytes32(payloadHash);

                // 4. Ghép chuỗi Calldata (Selector recordBatchEvent: 0x6b2b73ad + 4 x 64 hex = 266 ký tự)
                const encodedData = '0x6b2b73ad' + p1 + p2 + p3 + p4;

                console.log('--- CHI TIẾT CALLDATA METAMASK ---');
                console.log('Contract Address:', contractAddress);
                console.log('Batch ID:', p1);
                console.log('Business ID:', p2);
                console.log('Event Type:', p3);
                console.log('Payload Hash:', p4);
                console.log('Encoded Data Length (Bắt buộc = 266):', encodedData.length);

                // 5. Kích hoạt MetaMask Pop-up yêu cầu xác nhận giao dịch
                const txHash = await window.ethereum.request({
                    method: 'eth_sendTransaction',
                    params: [{
                        from: userAccount,
                        to: contractAddress,
                        data: encodedData,
                        gas: '0x7A120' // Dự phòng 500,000 gas limit để tránh Out of Gas
                    }]
                });

                // 6. Lưu mã txHash thành công vào MySQL
                await fetch(`/nha-san-xuat/api/lo-thuoc/${loThuocId}/luu-txhash`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ txHash: txHash })
                });

                // Đóng Modal & thông báo thành công
                if (modalDangKy) modalDangKy.classList.add('hidden');
                alert('Tạo lô thuốc và phát giao dịch Blockchain thành công!\n\nTxHash: ' + txHash);

                // Nạp lại bảng dữ liệu MySQL
                loadDataFromDB();

            } catch (error) {
                console.error('Chi tiết lỗi hệ thống:', error);
                if (error.code === 4001 || error.code === 4012) {
                    alert('Bạn đã hủy hoặc từ chối xác nhận giao dịch trên MetaMask!');
                } else {
                    alert('Thao tác thất bại: ' + (error.message || 'Đã xảy ra lỗi kết nối hoặc bị Revert từ Smart Contract!'));
                }
            } finally {
                // Phục hồi lại nút bấm ban đầu
                if (btnSubmit) {
                    btnSubmit.disabled = false;
                    btnSubmit.classList.remove('opacity-75', 'cursor-not-allowed');
                    btnSubmit.innerHTML = originalBtnContent;
                }
            }
        });
    }

    // Tải dữ liệu mặc định khi trang được nạp
    loadDataFromDB();
});
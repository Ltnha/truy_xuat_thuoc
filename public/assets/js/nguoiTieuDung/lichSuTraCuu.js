/**
 * lichSuTraCuu.js - Quản lý lịch sử tra cứu nguồn gốc dược phẩm
 * Hỗ trợ: Nhập/Xuất file .txt, đối soát trạng thái thời gian thực từ Blockchain API,
 * xem lại kết quả tra cứu mới nhất trên trang tra cứu.
 */
document.addEventListener('DOMContentLoaded', function () {
    const KEY_STORAGE = 'pharma_lich_su_tra_cuu';

    // Các phần tử giao diện chính
    const khungDangTai = document.getElementById('khungDangTai');
    const khungBangLichSu = document.getElementById('khungBangLichSu');
    const thanBangLichSu = document.getElementById('thanBangLichSu');
    const khungLichSuRong = document.getElementById('khungLichSuRong');
    const soLuongTraCuuBadge = document.getElementById('soLuongTraCuuBadge');

    // Cụm tra cứu nhanh đầu trang
    const inputTraCuu = document.getElementById('oTraCuuThuCong');
    const btnTraCuu = document.getElementById('nutTraCuuThuCong');
    const inputUploadFileTxt = document.getElementById('inputUploadFileTxt');

    // Cụm nhập / xuất / xóa lịch sử
    const inputNhapLichSuTxt = document.getElementById('inputNhapLichSuTxt');
    const nutXuatLichSu = document.getElementById('nutXuatLichSuPublic');
    const nutXoaLichSu = document.getElementById('nutXoaLichSuPublic');

    // Modal xóa lịch sử
    const modalXacNhanXoa = document.getElementById('modalXacNhanXoa');
    const nutHuyXoa = document.getElementById('nutHuyXoa');
    const nutDongYXoa = document.getElementById('nutDongYXoa');

    // Thông báo inline
    const thongBaoBox = document.getElementById('thongBaoPublic');
    const iconThongBao = document.getElementById('iconThongBaoPublic');
    const noiDungThongBao = document.getElementById('noiDungThongBaoPublic');
    const nutDongThongBao = document.getElementById('dongThongBaoPublic');

    let timeoutThongBao = null;

    function hienThongBao(noiDung, loai = 'error') {
        if (!thongBaoBox || !noiDungThongBao) return;
        if (timeoutThongBao) clearTimeout(timeoutThongBao);

        thongBaoBox.classList.remove('hidden', 'bg-red-50', 'border-red-200', 'text-red-800', 'bg-emerald-50', 'border-emerald-200', 'text-emerald-800');

        if (loai === 'success') {
            thongBaoBox.classList.add('bg-emerald-50', 'border-emerald-200', 'text-emerald-800');
            if (iconThongBao) iconThongBao.className = 'fa-solid fa-circle-check text-emerald-700';
        } else {
            thongBaoBox.classList.add('bg-red-50', 'border-red-200', 'text-red-800');
            if (iconThongBao) iconThongBao.className = 'fa-solid fa-triangle-exclamation text-red-600';
        }

        noiDungThongBao.textContent = noiDung;

        timeoutThongBao = setTimeout(() => {
            thongBaoBox.classList.add('hidden');
        }, 5000);
    }

    if (nutDongThongBao) {
        nutDongThongBao.addEventListener('click', () => {
            thongBaoBox?.classList.add('hidden');
        });
    }

    function escapeHtml(str) {
        if (str == null) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function dateFormatted() {
        const d = new Date();
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}${m}${day}`;
    }

    // Hàm lấy danh sách từ localStorage
    function layDanhSachLichSu() {
        try {
            return JSON.parse(localStorage.getItem(KEY_STORAGE)) || [];
        } catch (e) {
            return [];
        }
    }

    // Hàm lưu danh sách vào localStorage không trùng lặp (đưa lên đầu)
    function luuVaoLichSu(item) {
        let list = layDanhSachLichSu();
        const maChuan = String(item.ma || '').trim();
        if (!maChuan) return;

        list = list.filter(x => String(x.ma).trim().toUpperCase() !== maChuan.toUpperCase());
        list.unshift({
            ma: maChuan,
            tenThuoc: item.tenThuoc || maChuan,
            trangThai: item.trangThai || 'VALID',
            thoiGian: item.thoiGian || new Date().toLocaleString('vi-VN')
        });

        if (list.length > 50) list.pop();
        localStorage.setItem(KEY_STORAGE, JSON.stringify(list));
    }

    // Dịch trạng thái sang chuỗi tiếng Việt rõ ràng cho xuất file
    function dichTrangThaiText(trangThai) {
        switch (trangThai) {
            case 'VALID':
                return 'HỢP LỆ (Đã đối soát Blockchain)';
            case 'RECALLED':
                return 'ĐÃ THU HỒI';
            case 'EXPIRED':
                return 'QUÁ HẠN SỬ DỤNG';
            case 'INVALID':
                return 'SAI LỆCH HỒ SƠ / NGHI VẤN';
            case 'NOT_REGISTERED_ON_CHAIN':
                return 'CHƯA LÊN ON-CHAIN';
            case 'UNAVAILABLE':
                return 'CHỜ ĐỐI SOÁT RPC';
            case 'NOT_FOUND':
            default:
                return 'KHÔNG TỒN TẠI';
        }
    }

    // Tạo Badge trạng thái theo chuẩn giao diện PharmaChain
    function taoBadgeTrangThai(trangThai) {
        switch (trangThai) {
            case 'VALID':
                return `<span class="px-2.5 py-1 rounded-full text-[11px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-200 inline-flex items-center gap-1"><i class="fa-solid fa-circle-check"></i> HỢP LỆ</span>`;
            case 'RECALLED':
                return `<span class="px-2.5 py-1 rounded-full text-[11px] font-extrabold bg-red-100 text-red-700 border border-red-200 inline-flex items-center gap-1"><i class="fa-solid fa-ban"></i> ĐÃ THU HỒI</span>`;
            case 'EXPIRED':
                return `<span class="px-2.5 py-1 rounded-full text-[11px] font-extrabold bg-amber-100 text-amber-700 border border-amber-200 inline-flex items-center gap-1"><i class="fa-solid fa-hourglass-end"></i> QUÁ HẠN</span>`;
            case 'INVALID':
                return `<span class="px-2.5 py-1 rounded-full text-[11px] font-extrabold bg-rose-100 text-rose-700 border border-rose-200 inline-flex items-center gap-1"><i class="fa-solid fa-triangle-exclamation"></i> SAI LỆCH HỒ SƠ</span>`;
            case 'NOT_REGISTERED_ON_CHAIN':
                return `<span class="px-2.5 py-1 rounded-full text-[11px] font-extrabold bg-indigo-100 text-indigo-700 border border-indigo-200 inline-flex items-center gap-1"><i class="fa-solid fa-file-circle-question"></i> CHƯA LÊN ON-CHAIN</span>`;
            case 'UNAVAILABLE':
                return `<span class="px-2.5 py-1 rounded-full text-[11px] font-extrabold bg-sky-100 text-sky-800 border border-sky-200 inline-flex items-center gap-1"><i class="fa-solid fa-wifi"></i> CHỜ ĐỐI SOÁT RPC</span>`;
            case 'NOT_FOUND':
            default:
                return `<span class="px-2.5 py-1 rounded-full text-[11px] font-extrabold bg-slate-100 text-slate-600 border border-slate-200 inline-flex items-center gap-1"><i class="fa-solid fa-circle-question"></i> KHÔNG TỒN TẠI</span>`;
        }
    }

    // ========== ĐỐI SOÁT TRẠNG THÁI TỪ API THẬT & HIỂN THỊ ==========
    async function lamMoiVaHienThiLichSu() {
        const danhSach = layDanhSachLichSu();

        if (soLuongTraCuuBadge) {
            soLuongTraCuuBadge.textContent = danhSach ? danhSach.length : 0;
        }

        if (!danhSach || danhSach.length === 0) {
            khungDangTai?.classList.add('hidden');
            khungBangLichSu?.classList.add('hidden');
            khungLichSuRong?.classList.remove('hidden');
            return;
        }

        khungDangTai?.classList.remove('hidden');
        khungBangLichSu?.classList.add('hidden');
        khungLichSuRong?.classList.add('hidden');

        // Gọi API thật /api/tra-cuu-nguon-goc/{ma} để đối soát lại trạng thái mới nhất cho từng mã
        const updatedList = await Promise.all(
            danhSach.map(async (item) => {
                try {
                    const res = await fetch(`/api/tra-cuu-nguon-goc/${encodeURIComponent(item.ma)}`);
                    if (res.ok) {
                        const kq = await res.json();
                        if (kq) {
                            return {
                                ...item,
                                tenThuoc: (kq.trangThai !== 'NOT_FOUND' && kq.tenThuoc) ? kq.tenThuoc : (item.tenThuoc || 'Không có trong CSDL'),
                                trangThai: kq.trangThai || 'NOT_FOUND'
                            };
                        }
                    }
                } catch (err) {
                    console.warn('[LichSuTraCuu] Không thể gọi API đối soát mã:', item.ma, err);
                }
                return item;
            })
        );

        // Lưu lại dữ liệu đã đối soát trạng thái mới nhất vào localStorage
        try {
            localStorage.setItem(KEY_STORAGE, JSON.stringify(updatedList));
        } catch (e) {}

        khungDangTai?.classList.add('hidden');
        khungBangLichSu?.classList.remove('hidden');

        thanBangLichSu.innerHTML = updatedList.map(item => `
            <tr class="dong-lich-su hover:bg-slate-50/80 transition-all cursor-pointer">
                <td class="py-3.5 px-4 font-semibold text-slate-900">${escapeHtml(item.tenThuoc || '--')}</td>
                <td class="py-3.5 px-4 font-mono font-bold text-cyan-800">${escapeHtml(item.ma || '--')}</td>
                <td class="py-3.5 px-4">${taoBadgeTrangThai(item.trangThai)}</td>
                <td class="py-3.5 px-4 text-slate-500 font-mono text-[11px] whitespace-nowrap">
                    <i class="fa-regular fa-clock mr-1 text-slate-400"></i> ${escapeHtml(item.thoiGian || '--')}
                </td>
                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                    <a href="/tra-cuu-nguon-goc?maDinhDanh=${encodeURIComponent(item.ma)}" class="nut-xem-chi-tiet px-3 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-700 hover:text-white text-emerald-700 border border-emerald-200 font-bold text-[11px] transition-all inline-flex items-center gap-1.5 shadow-2xs" title="Xem kết quả tra cứu mới nhất của lô này">
                        <i class="fa-solid fa-eye text-xs"></i> Xem Kết Quả
                    </a>
                </td>
            </tr>
        `).join('');

        // Cho phép click cả dòng để chuyển sang trang tra cứu và in kết quả mới nhất
        thanBangLichSu.querySelectorAll('tr').forEach((tr, index) => {
            const item = updatedList[index];
            if (!item) return;

            tr.addEventListener('click', function (e) {
                // Không can thiệp nếu người dùng click thẳng vào thẻ <a>
                if (e.target.closest('a')) return;
                window.location.href = `/tra-cuu-nguon-goc?maDinhDanh=${encodeURIComponent(item.ma)}`;
            });
        });
    }

    // ========== XUẤT LỊCH SỬ TRA CỨU RA FILE .TXT ==========
    if (nutXuatLichSu) {
        nutXuatLichSu.addEventListener('click', function () {
            const danhSach = layDanhSachLichSu();
            if (!danhSach || danhSach.length === 0) {
                hienThongBao('Lịch sử tra cứu đang trống, không có dữ liệu để xuất!', 'error');
                return;
            }

            let noiDung = `--------------------------------------------------\n`;
            noiDung += `   DANH SÁCH LỊCH SỬ TRA CỨU DƯỢC PHẨM - PHARMACHAIN\n`;
            noiDung += `--------------------------------------------------\n`;
            noiDung += `Thời gian xuất: ${new Date().toLocaleString('vi-VN')}\n`;
            noiDung += `Tổng số lượng: ${danhSach.length} lượt tra cứu\n\n`;

            danhSach.forEach((item, index) => {
                noiDung += `[${index + 1}] Tên Thuốc  : ${item.tenThuoc || 'N/A'}\n`;
                noiDung += `    Mã Đã Tra  : ${item.ma}\n`;
                noiDung += `    Trạng Thái : ${dichTrangThaiText(item.trangThai)}\n`;
                noiDung += `    Thời Gian  : ${item.thoiGian || '--'}\n`;
                noiDung += `--------------------------------------------------\n`;
            });

            const blob = new Blob([noiDung], { type: 'text/plain;charset=utf-8' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = `DanhSachLichSuTraCuu_${dateFormatted()}.txt`;
            a.click();
            URL.revokeObjectURL(a.href);

            hienThongBao(`Đã xuất thành công ${danhSach.length} bản ghi lịch sử ra file .txt!`, 'success');
        });
    }

    // ========== NHẬP LỊCH SỬ TỪ FILE .TXT ==========
    function xuLyDocFileTxt(file, chuyenHuongNeuCo1Ma = false) {
        if (!file) return;

        const reader = new FileReader();
        reader.onload = async function (evt) {
            const text = evt.target.result || '';

            // Thu thập các mã tìm thấy trong file
            const maList = [];

            // 1. Regex dạng key: value từ file xuất ra: "Mã Đã Tra : <ma>" hoặc "Mã Lô : <ma>"
            const regexKieu1 = /(?:Mã Đã Tra|Mã Lô|Mã Tra Cứu)\s*:\s*([^\r\n]+)/gi;
            let match;
            while ((match = regexKieu1.exec(text)) !== null) {
                const val = match[1]?.trim();
                if (val && !val.includes('---')) maList.push(val);
            }

            // 2. Regex tìm các mã lô / mã định danh phổ biến: LOT-..., MED-..., BATCH-..., QR-...
            const regexKieu2 = text.match(/(?:LOT|MED|BATCH|QR|UNIT)[A-Z0-9_\-\.]+/gi);
            if (regexKieu2) {
                regexKieu2.forEach(m => maList.push(m.trim()));
            }

            // 3. Nếu là file văn bản chứa danh sách mã mỗi dòng một mã
            if (maList.length === 0) {
                const lines = text.split(/\r?\n/)
                    .map(l => l.trim())
                    .filter(l => l && !l.startsWith('-') && !l.startsWith('[') && !l.includes('Thời gian') && !l.includes('Tổng số'));
                lines.forEach(l => {
                    if (l.length >= 3 && l.length <= 64) maList.push(l);
                });
            }

            const uniqueCodes = [...new Set(maList.map(c => c.trim()))].filter(Boolean);

            if (uniqueCodes.length === 0) {
                hienThongBao('Không tìm thấy mã thuốc hoặc mã định danh hợp lệ nào trong file!', 'error');
                return;
            }

            // Nếu người dùng tải từ ô tra cứu nhanh đầu trang và chỉ có 1 mã -> Điền & chuyển ngay
            if (chuyenHuongNeuCo1Ma && uniqueCodes.length === 1) {
                const ma = uniqueCodes[0];
                if (inputTraCuu) inputTraCuu.value = ma;
                hienThongBao(`Đã nhận diện mã: ${ma}. Đang chuyển sang kết quả tra cứu...`, 'success');
                setTimeout(() => {
                    window.location.href = `/tra-cuu-nguon-goc?maDinhDanh=${encodeURIComponent(ma)}`;
                }, 600);
                return;
            }

            hienThongBao(`Đang đối soát thông tin của ${uniqueCodes.length} mã từ file...`, 'success');

            let addedCount = 0;
            for (const code of uniqueCodes) {
                try {
                    const res = await fetch(`/api/tra-cuu-nguon-goc/${encodeURIComponent(code)}`);
                    if (res.ok) {
                        const kq = await res.json();
                        luuVaoLichSu({
                            ma: code,
                            tenThuoc: (kq.trangThai !== 'NOT_FOUND' && kq.tenThuoc) ? kq.tenThuoc : code,
                            trangThai: kq.trangThai || 'NOT_FOUND',
                            thoiGian: new Date().toLocaleString('vi-VN')
                        });
                        addedCount++;
                    } else {
                        luuVaoLichSu({
                            ma: code,
                            tenThuoc: code,
                            trangThai: 'NOT_FOUND',
                            thoiGian: new Date().toLocaleString('vi-VN')
                        });
                        addedCount++;
                    }
                } catch (e) {
                    luuVaoLichSu({
                        ma: code,
                        tenThuoc: code,
                        trangThai: 'UNAVAILABLE',
                        thoiGian: new Date().toLocaleString('vi-VN')
                    });
                    addedCount++;
                }
            }

            hienThongBao(`Đã nhập thành công ${addedCount} mã tra cứu vào lịch sử!`, 'success');
            lamMoiVaHienThiLichSu();
        };

        reader.readAsText(file);
    }

    if (inputNhapLichSuTxt) {
        inputNhapLichSuTxt.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                xuLyDocFileTxt(file, false);
                inputNhapLichSuTxt.value = '';
            }
        });
    }

    if (inputUploadFileTxt) {
        inputUploadFileTxt.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                xuLyDocFileTxt(file, true);
                inputUploadFileTxt.value = '';
            }
        });
    }

    // ========== TRA CỨU NHANH TỪ Ô ĐẦU TRANG ==========
    if (btnTraCuu) {
        btnTraCuu.addEventListener('click', function () {
            const code = inputTraCuu?.value?.trim();
            if (!code) {
                hienThongBao('Vui lòng nhập mã lô thuốc hoặc mã định danh cần tra cứu!', 'error');
                return;
            }
            window.location.href = `/tra-cuu-nguon-goc?maDinhDanh=${encodeURIComponent(code)}`;
        });
    }

    if (inputTraCuu) {
        inputTraCuu.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                btnTraCuu?.click();
            }
        });
    }

    // ========== XÓA TOÀN BỘ LỊCH SỬ ==========
    function batModalXoa() {
        const danhSach = layDanhSachLichSu();
        if (!danhSach || danhSach.length === 0) {
            hienThongBao('Lịch sử tra cứu hiện đang trống!', 'error');
            return;
        }
        modalXacNhanXoa?.classList.remove('hidden');
    }

    function tatModalXoa() {
        modalXacNhanXoa?.classList.add('hidden');
    }

    if (nutXoaLichSu) nutXoaLichSu.addEventListener('click', batModalXoa);
    if (nutHuyXoa) nutHuyXoa.addEventListener('click', tatModalXoa);

    if (nutDongYXoa) {
        nutDongYXoa.addEventListener('click', function () {
            localStorage.removeItem(KEY_STORAGE);
            tatModalXoa();
            hienThongBao('Đã xóa toàn bộ lịch sử tra cứu trên thiết bị này!', 'success');
            lamMoiVaHienThiLichSu();
        });
    }

    // Đóng modal khi click ra ngoài nền mờ
    if (modalXacNhanXoa) {
        modalXacNhanXoa.addEventListener('click', function (e) {
            if (e.target === this) tatModalXoa();
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') tatModalXoa();
    });

    // Khởi chạy khi nạp trang
    lamMoiVaHienThiLichSu();
});

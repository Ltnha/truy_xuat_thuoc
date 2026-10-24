document.addEventListener('DOMContentLoaded', function () {
    const modalChiTiet = document.getElementById('modalChiTietToChuc');
    if (!modalChiTiet) return;

    // ════════════════════════════════════════════════════════════════
    // 1. SỰ KIỆN MỞ MODAL & BÓC TÁCH DỮ LIỆU TỔ CHỨC / TÀI KHOẢN
    // ════════════════════════════════════════════════════════════════
    document.querySelectorAll('.btn-xem-chi-tiet-to-chuc').forEach(button => {
        button.addEventListener('click', function () {
            const dataStr = this.getAttribute('data-tochuc');
            if (!dataStr) return;

            try {
                const data = JSON.parse(dataStr);

                // --- A. TIÊU ĐỀ & TRẠNG THÁI TỔ CHỨC ---
                const txtTitle = document.getElementById('detailTenToChucTitle');
                if (txtTitle) txtTitle.textContent = data.tenToChuc || '--';

                const badgeTrangThai = document.getElementById('detailTrangThaiBadge');
                if (badgeTrangThai) {
                    if (data.trangThaiDuyet === 'DA_DUYET') {
                        badgeTrangThai.className = 'badge-chuan px-2 py-0.5 rounded text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300';
                        badgeTrangThai.innerHTML = '<i class="fa-solid fa-circle-check"></i> Đang hoạt động';
                    } else if (data.trangThaiDuyet === 'BI_THU_HOI') {
                        badgeTrangThai.className = 'badge-chuan px-2 py-0.5 rounded text-[10px] font-extrabold bg-red-100 text-red-800 border border-red-300';
                        badgeTrangThai.innerHTML = '<i class="fa-solid fa-ban"></i> Bị thu hồi';
                    } else {
                        badgeTrangThai.className = 'badge-chuan px-2 py-0.5 rounded text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300';
                        badgeTrangThai.innerHTML = '<i class="fa-solid fa-clock"></i> Chờ duyệt';
                    }
                }

                // --- B. BANNER CẢNH BÁO THU HỒI ---
                const bannerDaThuHoi = document.getElementById('detailBannerDaThuHoi');
                if (bannerDaThuHoi) {
                    if (data.trangThaiDuyet === 'BI_THU_HOI') {
                        bannerDaThuHoi.classList.remove('hidden');
                    } else {
                        bannerDaThuHoi.classList.add('hidden');
                    }
                }

                // --- C. THÔNG TIN TÀI KHOẢN ĐĂNG NHẬP ĐẠI DIỆN ---
                const txtTenDangNhap = document.getElementById('detailTenDangNhap');
                if (txtTenDangNhap) txtTenDangNhap.textContent = data.tenDangNhap || '--';

                const txtEmailTK = document.getElementById('detailEmailTaiKhoan');
                if (txtEmailTK) txtEmailTK.textContent = data.emailTaiKhoan || '--';

                const txtHoTenDD = document.getElementById('detailHoTenDaiDien');
                if (txtHoTenDD) txtHoTenDD.textContent = data.hoTenDaiDien || '--';

                const badgeTaiKhoan = document.getElementById('detailBadgeTaiKhoan');
                if (badgeTaiKhoan) {
                    if (data.trangThaiTaiKhoan === 'ACTIVE') {
                        badgeTaiKhoan.className = 'px-2 py-0.5 rounded text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300';
                        badgeTaiKhoan.textContent = 'Tài khoản: Đang hoạt động';
                    } else if (data.trangThaiTaiKhoan === 'LOCKED') {
                        badgeTaiKhoan.className = 'px-2 py-0.5 rounded text-[10px] font-extrabold bg-red-100 text-red-800 border border-red-300';
                        badgeTaiKhoan.textContent = 'Tài khoản: Đã khóa';
                    } else {
                        badgeTaiKhoan.className = 'px-2 py-0.5 rounded text-[10px] font-extrabold bg-slate-100 text-slate-700 border border-slate-300';
                        badgeTaiKhoan.textContent = 'Tài khoản: Chưa kích hoạt';
                    }
                }

                // --- D. QUY MÔ HOẠT ĐỘNG ---
                const txtSoLo = document.getElementById('detailGiaTriSoLo');
                if (txtSoLo) txtSoLo.textContent = (data.soLo || 0) + ' lô';

                const txtSoLuuThong = document.getElementById('detailSoDonViLuuThong');
                if (txtSoLuuThong) txtSoLuuThong.textContent = (data.soLuuThong || 0).toLocaleString('vi-VN') + ' hộp';

                // --- E. THÔNG TIN PHÁP LÝ DOANH NGHIỆP ---
                const txtTenToChuc = document.getElementById('detailTenToChuc');
                if (txtTenToChuc) txtTenToChuc.textContent = data.tenToChuc || '--';

                const txtLoaiHinh = document.getElementById('detailLoaiHinh');
                if (txtLoaiHinh) txtLoaiHinh.textContent = data.loaiToChucLabel || '--';

                const txtMST = document.getElementById('detailMaSoThue');
                if (txtMST) txtMST.textContent = data.maSoThue || '--';

                const txtSoGiayPhep = document.getElementById('detailSoGiayPhep');
                if (txtSoGiayPhep) txtSoGiayPhep.textContent = data.soGiayPhepDuoc || '--';

                const txtNgayHetHan = document.getElementById('detailNgayHetHan');
                if (txtNgayHetHan) txtNgayHetHan.textContent = data.ngayHetHanGiayPhep || '--';

                const txtCoQuanCap = document.getElementById('detailCoQuanCap');
                if (txtCoQuanCap) txtCoQuanCap.textContent = data.coQuanCapGiayPhep || '--';

                const txtSDT = document.getElementById('detailSoDienThoai');
                if (txtSDT) txtSDT.textContent = data.soDienThoai || '--';

                const txtDiaChi = document.getElementById('detailDiaChi');
                if (txtDiaChi) txtDiaChi.textContent = data.diaChi || '--';

                // --- F. VÍ ON-CHAIN ACTIVE & ETHERSCAN LINK ---
                const diaChiVi = data.diaChiVi || '';
                const txtVi = document.getElementById('detailDiaChiViOnChain');
                const linkVi = document.getElementById('detailLinkViExplorer');

                if (txtVi && linkVi) {
                    if (diaChiVi) {
                        txtVi.textContent = diaChiVi;
                        linkVi.href = 'https://sepolia.etherscan.io/address/' + diaChiVi;
                    } else {
                        txtVi.textContent = 'Chưa khởi tạo ví';
                        linkVi.href = '#';
                    }
                }

                // --- G. LỊCH SỬ DỰIET ---
                const txtNgayDuyet = document.getElementById('detailNgayDuyetToChuc');
                if (txtNgayDuyet) txtNgayDuyet.textContent = data.ngayDuyet || '--';

                const txtNguoiDuyet = document.getElementById('detailNguoiDuyetToChuc');
                if (txtNguoiDuyet) txtNguoiDuyet.textContent = data.nguoiDuyet || '--';

                // --- H. HỒ SƠ PHÁP LÝ ĐÍNH KÈM ---
                const dsMinhChung = document.getElementById('detailDanhSachMinhChung');
                if (dsMinhChung) {
                    dsMinhChung.innerHTML = '';
                    if (data.taiLieus && data.taiLieus.length > 0) {
                        data.taiLieus.forEach(tl => {
                            const div = document.createElement('div');
                            div.className = 'flex items-center justify-between p-2 bg-white rounded-lg border border-slate-200 text-xs';
                            div.innerHTML = `
                                <span class="font-medium text-slate-700 truncate mr-2"><i class="fa-regular fa-file-pdf text-red-500 mr-1.5"></i> ${tl.tenTaiLieu}</span>
                                <a href="${tl.duongDan}" target="_blank" class="px-2 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold rounded text-[10px] shrink-0">Tải xuống</a>
                            `;
                            dsMinhChung.appendChild(div);
                        });
                    } else {
                        dsMinhChung.innerHTML = '<span class="text-slate-400 italic text-[11px]">Không có tài liệu đính kèm</span>';
                    }
                }

                // MỞ MODAL
                modalChiTiet.classList.remove('hidden');

            } catch (err) {
                console.error('Lỗi hiển thị chi tiết tài khoản tổ chức:', err);
            }
        });
    });

    // ════════════════════════════════════════════════════════════════
    // 2. BẮT SỰ KIỆN ĐÓNG MODAL
    // ════════════════════════════════════════════════════════════════
    document.querySelectorAll('.dong-modal-detail').forEach(btn => {
        btn.addEventListener('click', () => {
            modalChiTiet.classList.add('hidden');
        });
    });

    // Đóng khi nhấp bên ngoài khung Modal
    modalChiTiet.addEventListener('click', function (e) {
        if (e.target === this) {
            this.classList.add('hidden');
        }
    });

    // ════════════════════════════════════════════════════════════════
    // 3. SAO CHÉP ĐỊA CHỈ VÍ ON-CHAIN VÀO CLIPBOARD
    // ════════════════════════════════════════════════════════════════
    const nutCopy = document.getElementById('nutCopyDiaChiViDetail');
    if (nutCopy) {
        nutCopy.addEventListener('click', function () {
            const txtViElem = document.getElementById('detailDiaChiViOnChain');
            if (!txtViElem) return;

            const txtVi = txtViElem.textContent.trim();
            if (txtVi && txtVi !== 'Chưa khởi tạo ví') {
                navigator.clipboard.writeText(txtVi).then(() => {
                    this.innerHTML = '<i class="fa-solid fa-check text-emerald-600"></i>';
                    setTimeout(() => {
                        this.innerHTML = '<i class="fa-regular fa-copy"></i>';
                    }, 1500);
                }).catch(err => {
                    console.error('Không thể sao chép địa chỉ ví:', err);
                });
            }
        });
    }
});
document.addEventListener('DOMContentLoaded', function () {
    const modalChiTiet = document.getElementById('modalChiTietToChuc');

    // ════════════════════════════════════════════════════════════════
    // 1. SỰ KIỆN MỞ MODAL CHI TIẾT TỔ CHỨC
    // ════════════════════════════════════════════════════════════════
    document.querySelectorAll('.btn-xem-chi-tiet-to-chuc').forEach(button => {
        button.addEventListener('click', function () {
            const dataStr = this.getAttribute('data-tochuc');
            if (!dataStr) return;

            try {
                const data = JSON.parse(dataStr);

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

                const bannerDaThuHoi = document.getElementById('detailBannerDaThuHoi');
                if (bannerDaThuHoi) {
                    if (data.trangThaiDuyet === 'BI_THU_HOI') {
                        bannerDaThuHoi.classList.remove('hidden');
                    } else {
                        bannerDaThuHoi.classList.add('hidden');
                    }
                }

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

                const txtSoLo = document.getElementById('detailGiaTriSoLo');
                if (txtSoLo) txtSoLo.textContent = (data.soLo || 0) + ' lô';

                const txtSoLuuThong = document.getElementById('detailSoDonViLuuThong');
                if (txtSoLuuThong) txtSoLuuThong.textContent = (data.soLuuThong || 0).toLocaleString('vi-VN') + ' hộp';

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

                const txtNgayDuyet = document.getElementById('detailNgayDuyetToChuc');
                if (txtNgayDuyet) txtNgayDuyet.textContent = data.ngayDuyet || '--';

                const txtNguoiDuyet = document.getElementById('detailNguoiDuyetToChuc');
                if (txtNguoiDuyet) txtNguoiDuyet.textContent = data.nguoiDuyet || '--';

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

                modalChiTiet?.classList.remove('hidden');

            } catch (err) {
                console.error('Lỗi hiển thị chi tiết tài khoản tổ chức:', err);
            }
        });
    });

    document.querySelectorAll('.dong-modal-detail').forEach(btn => {
        btn.addEventListener('click', () => modalChiTiet?.classList.add('hidden'));
    });

    // ════════════════════════════════════════════════════════════════
    // 2. SAO CHÉP ĐỊA CHỈ VÍ ON-CHAIN
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
                });
            }
        });
    }

    // ════════════════════════════════════════════════════════════════
    // 3. XỬ LÝ POPUP XÁC NHẬN VÀ AJAX THAO TÁC BLOCKCHAIN
    // ════════════════════════════════════════════════════════════════
    const modalConfirm = document.getElementById('modalXacNhanHanhDong');
    const confirmIcon = document.getElementById('confirmModalIcon');
    const confirmTitle = document.getElementById('confirmModalTitle');
    const confirmDesc = document.getElementById('confirmModalDesc');
    const btnThucHien = document.getElementById('btnThucHienHanhDong');
    const btnHuyConfirm = document.getElementById('btnHuyXacNhan');

    const modalProcess = document.getElementById('modalProcessBlockchain');
    const stateProcessing = document.getElementById('popupStateProcessing');
    const stateSuccess = document.getElementById('popupStateSuccess');
    const popupWallet = document.getElementById('popupSuccessWallet');
    const popupTxLink = document.getElementById('popupSuccessTxLink');
    const popupTxHash = document.getElementById('popupSuccessTxHash');
    const btnHoanTatReload = document.getElementById('btnHoanTatReload');

    let pendingForm = null;
    let pollingInterval = null;

    // Bắt sự kiện submit form để mở Popup xác nhận thay vì confirm()
    document.querySelectorAll('.form-action-blockchain').forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            pendingForm = this;

            const actionType = this.getAttribute('data-action-type'); // THU_HOI, DOI_VI, KHOI_PHUC
            const confirmMsg = this.getAttribute('data-confirm-msg') || 'Bạn có chắc chắn muốn thực hiện thao tác này?';

            // Cấu hình giao diện Popup xác nhận theo từng loại hành động
            if (actionType === 'THU_HOI') {
                if (confirmIcon) confirmIcon.className = "w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto text-2xl shadow-sm";
                if (confirmIcon) confirmIcon.innerHTML = '<i class="fa-solid fa-user-slash"></i>';
                if (confirmTitle) confirmTitle.textContent = "Xác Nhận Thu Hồi Quyền";
                if (btnThucHien) btnThucHien.className = "px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl text-xs shadow-md cursor-pointer";
            } else if (actionType === 'DOI_VI') {
                if (confirmIcon) confirmIcon.className = "w-16 h-16 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center mx-auto text-2xl shadow-sm";
                if (confirmIcon) confirmIcon.innerHTML = '<i class="fa-solid fa-arrows-rotate"></i>';
                if (confirmTitle) confirmTitle.textContent = "Xác Nhận Đổi Ví Mới";
                if (btnThucHien) btnThucHien.className = "px-6 py-2.5 bg-indigo-700 hover:bg-indigo-800 text-white font-bold rounded-xl text-xs shadow-md cursor-pointer";
            } else if (actionType === 'KHOI_PHUC') {
                if (confirmIcon) confirmIcon.className = "w-16 h-16 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center mx-auto text-2xl shadow-sm";
                if (confirmIcon) confirmIcon.innerHTML = '<i class="fa-solid fa-user-check"></i>';
                if (confirmTitle) confirmTitle.textContent = "Xác Nhận Khôi Phục Quyền";
                if (btnThucHien) btnThucHien.className = "px-6 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white font-bold rounded-xl text-xs shadow-md cursor-pointer";
            }

            if (confirmDesc) confirmDesc.textContent = confirmMsg;
            modalConfirm?.classList.remove('hidden');
        });
    });

    // Nút Hủy trên Popup Xác nhận
    btnHuyConfirm?.addEventListener('click', () => {
        modalConfirm?.classList.add('hidden');
        pendingForm = null;
    });

    // Nút Xác Nhận trên Popup -> Tiến hành gửi AJAX và theo dõi On-chain
    btnThucHien?.addEventListener('click', () => {
        if (!pendingForm) return;

        modalConfirm?.classList.add('hidden');

        const form = pendingForm;
        pendingForm = null;

        const toChucId = form.getAttribute('data-tochuc-id');
        const actionType = form.getAttribute('data-action-type');
        const actionUrl = form.action;
        const formData = new FormData(form);
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const txtProcessing = document.getElementById('popupProcessingText');
        if (actionType === 'THU_HOI') {
            if (txtProcessing) txtProcessing.textContent = 'Hệ thống đang tước quyền On-chain và khóa tài khoản tổ chức trên Sepolia. Vui lòng chờ trong giây lát...';
        } else if (actionType === 'DOI_VI') {
            if (txtProcessing) txtProcessing.textContent = 'Hệ thống đang thu hồi ví cũ, khởi tạo ví mới và cấp lại quyền On-chain trên Sepolia. Vui lòng chờ trong giây lát...';
        } else if (actionType === 'KHOI_PHUC') {
            if (txtProcessing) txtProcessing.textContent = 'Hệ thống đang cấp lại quyền On-chain và kích hoạt lại tài khoản tổ chức trên Sepolia. Vui lòng chờ trong giây lát...';
        }

        stateProcessing?.classList.remove('hidden');
        stateSuccess?.classList.add('hidden');
        modalProcess?.classList.remove('hidden');

        fetch(actionUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                batDauPollingTrangThai(toChucId, actionType);
            } else {
                alert(data.message || 'Có lỗi xảy ra!');
                modalProcess?.classList.add('hidden');
            }
        })
        .catch(() => {
            alert('Lỗi kết nối máy chủ!');
            modalProcess?.classList.add('hidden');
        });
    });

    function batDauPollingTrangThai(toChucId, actionType) {
        pollingInterval = setInterval(() => {
            fetch(`/co-quan/to-chuc/${toChucId}/trang-thai-duyet`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                let isFinished = false;

                if (actionType === 'THU_HOI') {
                    if (data.trangThaiVi === 'REVOKED' || data.trangThaiDuyet === 'BI_THU_HOI') {
                        isFinished = true;
                    }
                } else if (actionType === 'KHOI_PHUC') {
                    if (data.trangThaiDuyet === 'DA_DUYET' && data.trangThaiVi === 'ACTIVE') {
                        isFinished = true;
                    }
                } else if (actionType === 'DOI_VI') {
                    if (data.trangThaiVi === 'ACTIVE') {
                        isFinished = true;
                    }
                }

                if (isFinished) {
                    clearInterval(pollingInterval);

                    const popupTitle = document.getElementById('popupSuccessTitle');
                    const popupDesc = document.getElementById('popupSuccessDesc');

                    if (actionType === 'THU_HOI') {
                        if (popupTitle) popupTitle.textContent = 'Thu Hồi Quyền On-Chain Thành Công!';
                        if (popupDesc) popupDesc.textContent = 'Tổ chức đã bị tước quyền doanh nghiệp và khóa tài khoản thành công.';
                    } else if (actionType === 'KHOI_PHUC') {
                        if (popupTitle) popupTitle.textContent = 'Khôi Phục Quyền On-Chain Thành Công!';
                        if (popupDesc) popupDesc.textContent = 'Tổ chức đã được cấp lại quyền doanh nghiệp và kích hoạt lại tài khoản.';
                    } else if (actionType === 'DOI_VI') {
                        if (popupTitle) popupTitle.textContent = 'Đổi Ví On-Chain Thành Công!';
                        if (popupDesc) popupDesc.textContent = 'Địa chỉ ví mới đã được khởi tạo và cấp quyền thành công.';
                    }

                    stateProcessing?.classList.add('hidden');
                    stateSuccess?.classList.remove('hidden');

                    if (popupWallet) popupWallet.textContent = data.diaChiVi || 'N/A';

                    if (data.txHash) {
                        document.getElementById('popupSuccessTxContainer')?.classList.remove('hidden');
                        if (popupTxHash) popupTxHash.textContent = data.txHash;
                        if (popupTxLink) popupTxLink.href = `https://sepolia.etherscan.io/tx/${data.txHash}`;
                    } else {
                        document.getElementById('popupSuccessTxContainer')?.classList.add('hidden');
                    }
                }
            })
            .catch(err => console.error('Polling error:', err));
        }, 2000);
    }

    btnHoanTatReload?.addEventListener('click', () => {
        location.reload();
    });
});
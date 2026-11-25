document.addEventListener('DOMContentLoaded', function () {
    const tabNhapMa = document.getElementById('tabNhapMa');
    const tabQuetQr = document.getElementById('tabQuetQr');
    const khungNhapMa = document.getElementById('khungNhapMa');
    const khungQuetCamera = document.getElementById('khungQuetCamera');

    const oNhapMa = document.getElementById('oNhapMa');
    const nutXacNhanTraCuu = document.getElementById('nutXacNhanTraCuu');
    const khuVucKetQua = document.getElementById('khuVucKetQua');

    // Phần tử của cụm tải ảnh mã QR
    const khungTaiAnhQR = document.getElementById('khungTaiAnhQR');
    const tepAnhQRInput = document.getElementById('tepAnhQRInput');
    const khungChuaChonAnh = document.getElementById('khungChuaChonAnh');
    const khungDangXuLyAnh = document.getElementById('khungDangXuLyAnh');
    const khungDaChonAnh = document.getElementById('khungDaChonAnh');
    const anhQRPreview = document.getElementById('anhQRPreview');
    const tenAnhQRDangChon = document.getElementById('tenAnhQRDangChon');
    const dungLuongAnhQR = document.getElementById('dungLuongAnhQR');
    const maNhanDienDuoc = document.getElementById('maNhanDienDuoc');
    const nutXoaAnhQR = document.getElementById('nutXoaAnhQR');
    const nutChonAnhKhac = document.getElementById('nutChonAnhKhac');
    const nutTraCuuTuAnh = document.getElementById('nutTraCuuTuAnh');

    // Chuyển đổi giữa 2 tab: Nhập mã & Tải ảnh mã QR
    tabNhapMa.addEventListener('click', () => {
        tabNhapMa.className = "pb-3 px-6 text-sm font-bold border-b-2 border-emerald-700 text-emerald-700 flex items-center gap-2 transition-all";
        tabQuetQr.className = "pb-3 px-6 text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-emerald-700 flex items-center gap-2 transition-all";
        khungNhapMa.classList.remove('hidden');
        khungQuetCamera.classList.add('hidden');
    });

    tabQuetQr.addEventListener('click', () => {
        tabQuetQr.className = "pb-3 px-6 text-sm font-bold border-b-2 border-emerald-700 text-emerald-700 flex items-center gap-2 transition-all";
        tabNhapMa.className = "pb-3 px-6 text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-emerald-700 flex items-center gap-2 transition-all";
        khungQuetCamera.classList.remove('hidden');
        khungNhapMa.classList.add('hidden');
    });

    // Xử lý tệp ảnh QR (Tải lên / Kéo thả / Dán từ Clipboard)
    function xuLyTepAnhQR(file) {
        if (!file || !file.type.startsWith('image/')) {
            alert('Vui lòng chọn hoặc kéo thả tệp hình ảnh hợp lệ (PNG, JPG, WEBP)!');
            return;
        }

        khungChuaChonAnh.classList.add('hidden');
        khungDaChonAnh.classList.add('hidden');
        khungDangXuLyAnh.classList.remove('hidden');

        const reader = new FileReader();
        reader.onload = function (e) {
            const dataUrl = e.target.result;
            const img = new Image();
            img.onload = function () {
                let maKetQua = null;

                if (typeof jsQR !== 'undefined') {
                    try {
                        const canvas = document.createElement('canvas');
                        canvas.width = img.width;
                        canvas.height = img.height;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0);
                        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                        const code = jsQR(imageData.data, canvas.width, canvas.height);
                        if (code && code.data) {
                            maKetQua = code.data.trim();
                            if (maKetQua.includes('maDinhDanh=')) {
                                try {
                                    const parsedUrl = new URL(maKetQua, window.location.origin);
                                    maKetQua = parsedUrl.searchParams.get('maDinhDanh') || maKetQua;
                                } catch (err) { }
                            }
                        }
                    } catch (err) {
                        console.warn('Lỗi khi phân tích QR qua jsQR:', err);
                    }
                }

                if (!maKetQua) {
                    khungDangXuLyAnh.classList.add('hidden');
                    khungChuaChonAnh.classList.remove('hidden');
                    if (tepAnhQRInput) tepAnhQRInput.value = '';
                    alert('Không tìm thấy mã QR hợp lệ trong ảnh. Vui lòng tải ảnh QR rõ nét hơn.');
                    return;
                }

                setTimeout(() => {
                    khungDangXuLyAnh.classList.add('hidden');
                    khungDaChonAnh.classList.remove('hidden');

                    anhQRPreview.src = dataUrl;
                    tenAnhQRDangChon.textContent = file.name;
                    dungLuongAnhQR.textContent = `(${(file.size / 1024).toFixed(1)} KB)`;
                    maNhanDienDuoc.textContent = maKetQua;

                    oNhapMa.value = maKetQua;
                    thucHienTraCuu(maKetQua);
                }, 300);
            };
            img.onerror = function () {
                khungDangXuLyAnh.classList.add('hidden');
                khungChuaChonAnh.classList.remove('hidden');
                if (tepAnhQRInput) tepAnhQRInput.value = '';
                alert('Không thể đọc tệp ảnh. Vui lòng chọn ảnh PNG, JPG hoặc WEBP hợp lệ.');
            };
            img.src = dataUrl;
        };
        reader.readAsDataURL(file);
    }

    if (khungTaiAnhQR && tepAnhQRInput) {
        khungTaiAnhQR.addEventListener('click', function (e) {
            if (e.target.closest('#nutXoaAnhQR') || e.target.closest('#nutChonAnhKhac') || e.target.closest('#nutTraCuuTuAnh')) {
                return;
            }
            tepAnhQRInput.click();
        });

        tepAnhQRInput.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                xuLyTepAnhQR(this.files[0]);
            }
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            khungTaiAnhQR.addEventListener(eventName, function (e) {
                e.preventDefault();
                e.stopPropagation();
                khungTaiAnhQR.classList.add('keo-qua');
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            khungTaiAnhQR.addEventListener(eventName, function (e) {
                e.preventDefault();
                e.stopPropagation();
                khungTaiAnhQR.classList.remove('keo-qua');
            });
        });

        khungTaiAnhQR.addEventListener('drop', function (e) {
            const dt = e.dataTransfer;
            if (dt && dt.files && dt.files.length > 0) {
                xuLyTepAnhQR(dt.files[0]);
            }
        });
    }

    if (nutXoaAnhQR) {
        nutXoaAnhQR.addEventListener('click', function (e) {
            e.stopPropagation();
            if (tepAnhQRInput) tepAnhQRInput.value = '';
            anhQRPreview.src = '';
            khungDaChonAnh.classList.add('hidden');
            khungDangXuLyAnh.classList.add('hidden');
            khungChuaChonAnh.classList.remove('hidden');
        });
    }

    if (nutChonAnhKhac) {
        nutChonAnhKhac.addEventListener('click', function (e) {
            e.stopPropagation();
            if (tepAnhQRInput) {
                tepAnhQRInput.value = '';
                tepAnhQRInput.click();
            }
        });
    }

    if (nutTraCuuTuAnh) {
        nutTraCuuTuAnh.addEventListener('click', function (e) {
            e.stopPropagation();
            const ma = maNhanDienDuoc.textContent.trim();
            if (ma) {
                oNhapMa.value = ma;
                thucHienTraCuu(ma);
            }
        });
    }

    window.addEventListener('paste', function (e) {
        if (!khungQuetCamera.classList.contains('hidden') && e.clipboardData && e.clipboardData.items) {
            for (let item of e.clipboardData.items) {
                if (item.type.indexOf('image') !== -1) {
                    const blob = item.getAsFile();
                    xuLyTepAnhQR(blob);
                    break;
                }
            }
        }
    });

    oNhapMa.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            thucHienTraCuu(oNhapMa.value.trim());
        }
    });

    nutXacNhanTraCuu.addEventListener('click', () => {
        thucHienTraCuu(oNhapMa.value.trim());
    });

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('cheDo') === 'quet' || urlParams.get('cheDo') === 'taiAnh') {
        tabQuetQr.click();
    }

    const maTuUrl = urlParams.get('maDinhDanh') || urlParams.get('maLo') || urlParams.get('ma');
    if (maTuUrl && (!oNhapMa.value || oNhapMa.value.trim() === '')) {
        oNhapMa.value = maTuUrl.trim();
    }

    if (oNhapMa.value.trim() !== '') {
        thucHienTraCuu(oNhapMa.value.trim());
    }

    async function thucHienTraCuu(ma) {
        if (!ma) {
            alert("Vui lòng nhập mã lô hoặc mã đơn vị thuốc để kiểm tra!");
            return;
        }

        const khungDangXacThuc = document.getElementById('khungDangXacThuc');
        const noiDungKetQua = document.getElementById('noiDungKetQuaXacThuc');

        if (nutXacNhanTraCuu) {
            nutXacNhanTraCuu.disabled = true;
            nutXacNhanTraCuu.classList.add('opacity-75', 'cursor-not-allowed');
            nutXacNhanTraCuu.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1.5"></i> Đang Xác Thực...';
        }
        if (nutTraCuuTuAnh) {
            nutTraCuuTuAnh.disabled = true;
            nutTraCuuTuAnh.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1.5"></i> Đang Xác Thực...';
        }

        khuVucKetQua.classList.remove('hidden');
        khuVucKetQua.setAttribute('aria-busy', 'true');
        if (khungDangXacThuc) khungDangXacThuc.classList.remove('hidden');
        if (noiDungKetQua) noiDungKetQua.classList.add('hidden');

        khuVucKetQua.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        try {
            const response = await fetch('/api/tra-cuu-nguon-goc/' + encodeURIComponent(ma), {
                headers: { 'Accept': 'application/json' },
                cache: 'no-store'
            });
            if (!response.ok) {
                throw new Error('Không thể tra cứu dữ liệu (' + response.status + ').');
            }
            const ketQua = await response.json();
            capNhatGiaoDien(ma, ketQua);
            luuVaoLocalStorage(ma, ketQua);
        } catch (error) {
            const ketQua = {
                trangThai: 'UNAVAILABLE',
                loai: 'KHÔNG THỂ KẾT NỐI',
                tenThuoc: 'Không thể lấy dữ liệu xác thực từ máy chủ.',
                hamLuongDangBaoChe: '—',
                hoatChat: '—',
                soDangKy: '—',
                nhaSanXuat: '—',
                ngaySanXuat: '—',
                hanSuDung: '—',
                batchIdHash: '—',
                txHash: '—',
                isDispensed: 'Không xác định',
                productIntegrity: 'UNVERIFIED',
                batchIntegrity: 'UNVERIFIED',
                chainReadStatus: 'UNAVAILABLE',
                chainOwner: null,
                chainRecalled: false,
                chainHistory: [],
                hanhTrinh: []
            };
            capNhatGiaoDien(ma, ketQua);
            console.error(error);
        } finally {
            khuVucKetQua.removeAttribute('aria-busy');
            if (khungDangXacThuc) khungDangXacThuc.classList.add('hidden');
            if (noiDungKetQua) noiDungKetQua.classList.remove('hidden');

            if (nutXacNhanTraCuu) {
                nutXacNhanTraCuu.disabled = false;
                nutXacNhanTraCuu.classList.remove('opacity-75', 'cursor-not-allowed');
                nutXacNhanTraCuu.innerHTML = '<i class="fa-solid fa-shield-halved mr-1.5"></i> Xác Thực Ngay';
            }
            if (nutTraCuuTuAnh) {
                nutTraCuuTuAnh.disabled = false;
                nutTraCuuTuAnh.innerHTML = '<i class="fa-solid fa-shield-halved mr-1.5"></i> Xác thực lại nguồn gốc';
            }
        }
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
        } else if (kq.trangThai === 'UNAVAILABLE') {
            banner.className = "p-6 rounded-2xl border border-amber-200 bg-amber-50/70 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bong-xanh";
            bieuTuong.className = "w-14 h-14 rounded-xl flex items-center justify-center text-3xl shrink-0 bg-amber-100 text-amber-700";
            bieuTuong.innerHTML = '<i class="fa-solid fa-cloud-arrow-down"></i>';
            theTrangThaiText.className = "px-3 py-1 rounded-full text-xs font-extrabold uppercase tracking-wide bg-amber-600 text-white";
            theTrangThaiText.textContent = "CHƯA THỂ XÁC MINH";
            tieuDe.className = "text-lg sm:text-xl font-bold mt-1 text-amber-800";
            tieuDe.textContent = "Không kết nối được dịch vụ xác minh";
            moTa.textContent = "Không được kết luận thuốc hợp lệ hoặc không hợp lệ khi chưa đọc được dữ liệu xác thực.";
            khoiChiTiet.classList.remove('opacity-50', 'pointer-events-none');
        } else if (kq.trangThai === 'NOT_REGISTERED_ON_CHAIN') {
            banner.className = "p-6 rounded-2xl border border-amber-200 bg-amber-50/70 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bong-xanh";
            bieuTuong.className = "w-14 h-14 rounded-xl flex items-center justify-center text-3xl shrink-0 bg-amber-100 text-amber-700";
            bieuTuong.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i>';
            theTrangThaiText.className = "px-3 py-1 rounded-full text-xs font-extrabold uppercase tracking-wide bg-amber-600 text-white";
            theTrangThaiText.textContent = "CHƯA ĐĂNG KÝ ON-CHAIN";
            tieuDe.className = "text-lg sm:text-xl font-bold mt-1 text-amber-800";
            tieuDe.textContent = "Lô chưa được xác nhận đăng ký trên blockchain";
            moTa.textContent = "Dữ liệu trong hệ thống chưa có bằng chứng BatchRegistered tương ứng trên Sepolia.";
            khoiChiTiet.classList.remove('opacity-50', 'pointer-events-none');
        } else if (kq.trangThai === 'INVALID') {
            banner.className = "p-6 rounded-2xl border border-red-200 bg-red-50/70 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bong-xanh";
            bieuTuong.className = "w-14 h-14 rounded-xl flex items-center justify-center text-3xl shrink-0 bg-red-100 text-red-700";
            bieuTuong.innerHTML = '<i class="fa-solid fa-shield-halved"></i>';
            theTrangThaiText.className = "px-3 py-1 rounded-full text-xs font-extrabold uppercase tracking-wide bg-red-600 text-white";
            theTrangThaiText.textContent = "KHÔNG KHỚP DỮ LIỆU";
            tieuDe.className = "text-lg sm:text-xl font-bold mt-1 text-red-800";
            tieuDe.textContent = "Không thể xác thực tính toàn vẹn hồ sơ";
            moTa.textContent = "Hash hồ sơ sản phẩm, thông tin lô thuốc hoặc chủ sở hữu on-chain không khớp dữ liệu hiện tại.";
            khoiChiTiet.classList.remove('opacity-50', 'pointer-events-none');
        } else {
            banner.className = "p-6 rounded-2xl border border-slate-300 bg-slate-50 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bong-xanh";
            bieuTuong.className = "w-14 h-14 rounded-xl flex items-center justify-center text-3xl shrink-0 bg-slate-200 text-slate-500";
            bieuTuong.innerHTML = '<i class="fa-solid fa-circle-question"></i>';
            theTrangThaiText.className = "px-3 py-1 rounded-full text-xs font-extrabold uppercase tracking-wide bg-slate-600 text-white";
            theTrangThaiText.textContent = "KHÔNG TÌM THẤY";
            tieuDe.className = "text-lg sm:text-xl font-bold mt-1 text-slate-900";
            tieuDe.textContent = "Không tìm thấy mã định danh trong hệ thống";
            moTa.textContent = "Mã QR hoặc chuỗi định danh này chưa được đăng ký trong cơ sở dữ liệu.";
            khoiChiTiet.classList.add('opacity-50', 'pointer-events-none');
        }

        const nutBaoCao = document.getElementById('nutBaoCaoNghiVan');
        if (nutBaoCao) {
            const baseUrl = nutBaoCao.getAttribute('href').split('?')[0];
            const maTruyen = kq.maLo && kq.trangThai !== 'NOT_FOUND' ? kq.maLo : ma;
            nutBaoCao.setAttribute('href', `${baseUrl}?maDinhDanh=${encodeURIComponent(maTruyen)}`);
        }

        // Đổ dữ liệu chi tiết
        document.getElementById('tenThuoc').textContent = kq.tenThuoc;
        document.getElementById('soDkLuuHanh').textContent = kq.soDangKy;
        document.getElementById('hamLuongDangBaoChe').textContent = kq.hamLuongDangBaoChe;
        document.getElementById('hoatChat').textContent = kq.hoatChat;
        document.getElementById('tenNhaSanXuat').textContent = kq.nhaSanXuat;
        document.getElementById('ngaySanXuat').textContent = kq.ngaySanXuat;
        document.getElementById('hanSuDung').textContent = kq.hanSuDung;

        document.getElementById('batchIdHash').textContent = kq.batchIdHash || '—';
        document.getElementById('txHashKhoiTao').textContent = kq.txHash || 'Chưa có giao dịch blockchain';

        const ownerNode = document.getElementById('chainOwner');
        if (ownerNode) ownerNode.textContent = kq.chainOwner || 'Chưa có dữ liệu on-chain';

        const ownerIntegrityNode = document.getElementById('ownerIntegrity');
        if (ownerIntegrityNode) {
            let ownerLabel = 'CHƯA ĐỐI SOÁT ĐƯỢC';
            if (kq.ownerIntegrity === 'MATCH') ownerLabel = 'KHỚP CHỦ SỞ HỮU';
            else if (kq.ownerIntegrity === 'MISMATCH') ownerLabel = 'KHÔNG KHỚP CHỦ SỞ HỮU';
            ownerIntegrityNode.textContent = ownerLabel;
            ownerIntegrityNode.className = 'font-bold px-2.5 py-1 rounded text-[11px] inline-block ' +
                (kq.ownerIntegrity === 'MATCH' ? 'bg-emerald-100 text-emerald-800' :
                    (kq.ownerIntegrity === 'MISMATCH' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-800'));
        }

        const integrityNode = document.getElementById('productIntegrity');
        if (integrityNode) {
            let label = 'CHƯA ĐỐI SOÁT ĐƯỢC';
            if (kq.productIntegrity === 'MATCH') label = 'TRÙNG KHỚP (HỢP LỆ)';
            else if (kq.productIntegrity === 'MISMATCH') label = 'SAI LỆCH HỒ SƠ';
            else if (kq.productIntegrity === 'NOT_APPROVED_ON_CHAIN') label = 'CHƯA PHÊ DUYỆT ON-CHAIN';
            integrityNode.textContent = label;
            integrityNode.className = 'font-bold px-2.5 py-1 rounded text-[11px] inline-block ' +
                (kq.productIntegrity === 'MATCH' ? 'bg-emerald-100 text-emerald-800' :
                    (kq.productIntegrity === 'MISMATCH' || kq.productIntegrity === 'NOT_APPROVED_ON_CHAIN' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-800'));
        }

        const batchIntegrityNode = document.getElementById('batchIntegrity');
        if (batchIntegrityNode) {
            let bLabel = 'CHƯA ĐỐI SOÁT ĐƯỢC';
            if (kq.batchIntegrity === 'MATCH') bLabel = 'TRÙNG KHỚP (HỢP LỆ)';
            else if (kq.batchIntegrity === 'MISMATCH') bLabel = 'SAI LỆCH THÔNG TIN LÔ';
            batchIntegrityNode.textContent = bLabel;
            batchIntegrityNode.className = 'font-bold px-2.5 py-1 rounded text-[11px] inline-block ' +
                (kq.batchIntegrity === 'MATCH' ? 'bg-emerald-100 text-emerald-800' :
                    (kq.batchIntegrity === 'MISMATCH' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-800'));
        }

        const batchStatusNode = document.getElementById('chainBatchStatus');
        if (batchStatusNode) {
            batchStatusNode.textContent = kq.chainReadStatus !== 'AVAILABLE'
                ? 'Không thể kết nối RPC để xác minh'
                : (kq.chainRecalled ? 'Đã thu hồi on-chain' : (kq.batchExistsOnChain ? 'Đã xác thực trên blockchain (Sepolia)' : 'Chưa đăng ký on-chain'));
            batchStatusNode.className = 'font-semibold ' +
                (kq.chainReadStatus !== 'AVAILABLE' ? 'text-amber-600' :
                    (kq.chainRecalled ? 'text-red-600' : (kq.batchExistsOnChain ? 'text-emerald-700' : 'text-slate-600')));
        }

        const coBanLe = document.getElementById('coBanLe');
        coBanLe.textContent = kq.isDispensed;
        const daCoBanLe = typeof kq.isDispensed === 'string' && !kq.isDispensed.startsWith('Đã bán 0 ');
        coBanLe.className = daCoBanLe
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
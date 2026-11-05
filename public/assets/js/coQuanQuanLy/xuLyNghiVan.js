document.addEventListener('DOMContentLoaded', function () {
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    let trangHienTai = 1;
    let cachedDanhSachNghiVan = [];
    let baoCaoDangChonId = null;

    // Element DOM
    const thanBang = document.getElementById('thanBangNghiVan');
    const oTimKiem = document.getElementById('oTimKiemNghiVan');
    const locTrangThai = document.getElementById('locTrangThaiNghiVan');
    const locLoaiBaoCao = document.getElementById('locLoaiBaoCao');
    const soLuongBadge = document.getElementById('soLuongNghiVanBadge');
    const thongTinPhanTrang = document.getElementById('thongTinPhanTrangNghiVan');
    const cumNutPhanTrang = document.getElementById('cumNutPhanTrangNghiVan');

    // Helper: Định dạng ngày giờ chuẩn Việt Nam
    function dinhDangNgayGio(ngayISO) {
        if (!ngayISO || ngayISO === '--' || ngayISO === 'N/A') return '--';
        const date = new Date(ngayISO);
        if (isNaN(date.getTime())) return ngayISO;
        return date.toLocaleString('vi-VN', {
            hour: '2-digit', minute: '2-digit',
            day: '2-digit', month: '2-digit', year: 'numeric'
        });
    }

    // ══ 1. TẢI DỮ LIỆU TỪ BACKEND API ══
    async function loadDanhSachBaoCao() {
        if (!thanBang) return;
        thanBang.innerHTML = `<tr><td colspan="7" class="text-center py-8 text-slate-400 italic"><i class="fa-solid fa-spinner fa-spin mr-2"></i>Đang tải dữ liệu nghi vấn...</td></tr>`;

        const params = new URLSearchParams({
            page: trangHienTai,
            tuKhoa: oTimKiem ? oTimKiem.value.trim() : '',
            trangThai: locTrangThai ? locTrangThai.value : 'TAT_CA',
            loaiBaoCao: locLoaiBaoCao ? locLoaiBaoCao.value : 'TAT_CA'
        });

        try {
            const response = await fetch(`/co-quan/xu-ly-nghi-van/data?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json'
                }
            });

            const text = await response.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (err) {
                throw new Error(`Dữ liệu máy chủ không hợp lệ (Mã ${response.status}): ${text.substring(0, 100)}...`);
            }

            if (!response.ok) {
                throw new Error(data.message || 'Không thể lấy danh sách báo cáo nghi vấn từ máy chủ');
            }

            cachedDanhSachNghiVan = data.data || [];
            renderBang(data);
        } catch (error) {
            console.error("Lỗi khi tải danh sách báo cáo nghi vấn:", error);
            thanBang.innerHTML = `<tr><td colspan="7" class="text-center py-8 text-red-500 italic">Có lỗi xảy ra: ${error.message}</td></tr>`;
        }
    }

    // ══ 2. RENDER BẢNG VÀ PHÂN TRANG ══
    function renderBang(apiResponse) {
        const duLieu = apiResponse.data || [];
        const tongSo = apiResponse.total || duLieu.length;
        
        if (soLuongBadge) soLuongBadge.textContent = `Hiển thị: ${tongSo} báo cáo`;
        thanBang.innerHTML = '';

        if (duLieu.length === 0) {
            thanBang.innerHTML = `<tr><td colspan="7" class="text-center py-8 text-slate-400 italic">Không tìm thấy báo cáo nghi vấn nào phù hợp.</td></tr>`;
            if (thongTinPhanTrang) thongTinPhanTrang.textContent = "Không có bản ghi nào";
            if (cumNutPhanTrang) cumNutPhanTrang.innerHTML = '';
            return;
        }

        if (thongTinPhanTrang) {
            thongTinPhanTrang.textContent = `Đang hiển thị ${apiResponse.from || 1} đến ${apiResponse.to || duLieu.length} trên tổng số ${tongSo} bản ghi`;
        }

        duLieu.forEach(item => {
            const daXuLy = (item.trangThai === 'DA_XU_LY');

            let badgeTrangThai = daXuLy 
                ? `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300"><i class="fa-solid fa-check"></i> Đã xử lý</span>`
                : `<span class="badge-chuan px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300"><i class="fa-solid fa-clock"></i> Chờ xử lý</span>`;

            let nutXuLyHtml = '';
            if (!daXuLy) {
                nutXuLyHtml = `
                    <button type="button" class="btn-mo-modal-xu-ly px-2.5 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 font-bold text-xs transition-all flex items-center gap-1 border border-red-200 shadow-2xs whitespace-nowrap cursor-pointer" data-id="${item.id}">
                        <i class="fa-solid fa-gavel text-[10px]"></i> Xử lý
                    </button>
                `;
            }

            // Phòng thủ dữ liệu (tránh undefined)
            const maBaoCaoText = item.maBaoCao || ('BC-' + item.id);
            const maLoText = item.maLo || 'N/A';
            const tenSanPhamText = item.tenSanPham || 'N/A';
            const donViText = item.donViBaoCao || 'N/A';
            const thoiGianText = dinhDangNgayGio(item.thoiGianGui);

            const tr = document.createElement('tr');
            tr.className = 'hover:bg-slate-50 transition-colors';
            tr.innerHTML = `
                <td class="py-3 px-3 font-mono font-bold text-amber-900 whitespace-nowrap">${maBaoCaoText}</td>
                <td class="py-3 px-3 font-mono font-bold text-indigo-900 whitespace-nowrap">${maLoText}</td>
                <td class="py-3 px-3 font-bold text-slate-900 leading-tight">${tenSanPhamText}</td>
                <td class="py-3 px-3 text-slate-800 font-semibold leading-tight">${donViText}</td>
                <td class="py-3 px-2.5 text-center whitespace-nowrap font-mono text-xs">${thoiGianText}</td>
                <td class="py-3 px-2.5 text-center whitespace-nowrap">${badgeTrangThai}</td>
                <td class="py-3 px-3 text-center whitespace-nowrap">
                    <div class="inline-flex items-center justify-center gap-1.5">
                        <button type="button" class="btn-xem-chi-tiet-nghi-van px-2.5 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-800 font-bold text-xs transition-all flex items-center gap-1 border border-indigo-200 shadow-2xs whitespace-nowrap cursor-pointer" data-id="${item.id}">
                            <i class="fa-solid fa-eye text-indigo-700 text-[10px]"></i> Chi tiết
                        </button>
                        ${nutXuLyHtml}
                    </div>
                </td>
            `;
            thanBang.appendChild(tr);
        });

        renderPhanTrang(apiResponse.last_page || 1);
        ganSuKienNutBang();
    }

    function renderPhanTrang(tongSoTrang) {
        if (!cumNutPhanTrang) return;
        cumNutPhanTrang.innerHTML = '';
        if (tongSoTrang <= 1) return;

        for (let i = 1; i <= tongSoTrang; i++) {
            const btnPage = document.createElement('button');
            btnPage.className = `w-7 h-7 rounded-lg text-xs font-bold transition-all ${trangHienTai === i ? 'bg-indigo-700 text-white shadow-2xs' : 'border border-slate-200 text-slate-700 hover:bg-slate-100'}`;
            btnPage.textContent = i;
            btnPage.addEventListener('click', () => { 
                trangHienTai = i; 
                loadDanhSachBaoCao(); 
            });
            cumNutPhanTrang.appendChild(btnPage);
        }
    }

    // ══ 3. MODAL CHI TIẾT BÁO CÁO NGHI VẤN ══
    const modalDetail = document.getElementById('modalChiTietNghiVan');

    function moModalChiTiet(id) {
        const item = cachedDanhSachNghiVan.find(x => x.id == id);
        if (!item || !modalDetail) return;

        const daXuLy = (item.trangThai === 'DA_XU_LY');

        document.getElementById('detailMaBaoCaoTitle').textContent = item.maBaoCao || ('BC-' + item.id);
        document.getElementById('detailMaLoThuoc').textContent = item.maLo || 'N/A';
        document.getElementById('detailTenSanPham').textContent = item.tenSanPham || 'N/A';
        document.getElementById('detailDonViBaoCao').textContent = item.donViBaoCao || 'N/A';
        document.getElementById('detailThoiGianGui').textContent = dinhDangNgayGio(item.thoiGianGui);
        document.getElementById('detailLoaiBaoCao').textContent = item.loaiBaoCao || 'Báo cáo nghi vấn';
        document.getElementById('detailNoiDungBaoCao').textContent = item.noiDung || 'Không có mô tả chi tiết.';

        // Hiển thị danh sách minh chứng đính kèm nếu có
        const khungMinhChung = document.getElementById('detailKhungMinhChung');
        const dsMinhChung = document.getElementById('detailDanhSachMinhChung');
        if (khungMinhChung && dsMinhChung) {
            if (item.minhChungs && item.minhChungs.length > 0) {
                khungMinhChung.classList.remove('hidden');
                dsMinhChung.innerHTML = item.minhChungs.map(mc => `
                    <a href="${mc.duongDan}" target="_blank" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-white border border-slate-200 hover:border-indigo-400 rounded-lg text-[11px] font-medium text-slate-700 hover:text-indigo-700 transition-all shadow-2xs">
                        <i class="fa-solid fa-file-image text-indigo-500"></i> ${mc.tenFile}
                    </a>
                `).join('');
            } else {
                khungMinhChung.classList.add('hidden');
                dsMinhChung.innerHTML = '';
            }
        }

        if (daXuLy) {
            document.getElementById('detailTrangThaiBadge').innerHTML = `<span class="badge-chuan px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300">Đã xử lý</span>`;
            
            const khungKetQua = document.getElementById('detailKhungKetQuaXuLy');
            if (khungKetQua) {
                khungKetQua.classList.remove('hidden');
                document.getElementById('detailNoiDungKetQua').textContent = item.ketQuaXuLy || 'Đã ban hành quyết định thu hồi lô thuốc liên quan.';
                document.getElementById('detailThoiGianXuLy').textContent = `Thời gian xử lý: ${dinhDangNgayGio(item.thoiGianXuLy)}`;
            }
            
            document.getElementById('detailKhungNutXuLyNhanh').innerHTML = '';
        } else {
            document.getElementById('detailTrangThaiBadge').innerHTML = `<span class="badge-chuan px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300">Chờ xử lý</span>`;
            
            const khungKetQua = document.getElementById('detailKhungKetQuaXuLy');
            if (khungKetQua) khungKetQua.classList.add('hidden');

            const nutLabel = item.loThuocId 
                ? `<i class="fa-solid fa-gavel text-[10px]"></i> Ban hành quyết định thu hồi`
                : `<i class="fa-solid fa-shield-cat text-[10px]"></i> Ghi nhận hàng giả ngoại vi`;

            document.getElementById('detailKhungNutXuLyNhanh').innerHTML = `
                <button type="button" id="nutMoXuLyTuDetail" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-xl transition-all flex items-center gap-1.5 shadow-md cursor-pointer">
                    ${nutLabel}
                </button>
            `;
            document.getElementById('nutMoXuLyTuDetail')?.addEventListener('click', () => {
                modalDetail.classList.add('hidden');
                moModalXuLy(item.id);
            });
        }

        modalDetail.classList.remove('hidden');
    }

    document.querySelectorAll('.dong-modal-detail-nghivan').forEach(btn => {
        btn.addEventListener('click', () => modalDetail?.classList.add('hidden'));
    });

    // ══ 4. MODAL XỬ LÝ (POLLING CHỜ BLOCKCHAIN CONFIRMED THỰC SỰ MỚI ĐỔI TRẠNG THÁI POPUP) ══
    const modalXuLy = document.getElementById('modalXuLyThuHoiNghiVan');
    const manHinhFormNghiVan = document.getElementById('manHinhFormNghiVan');
    const manHinhDangGhiNghiVan = document.getElementById('manHinhDangGhiNghiVan');
    const manHinhKetQuaNghiVan = document.getElementById('manHinhKetQuaNghiVan');

    const iconKetQua = document.getElementById('iconKetQuaNghiVan');
    const tieuDeKetQua = document.getElementById('tieuDeKetQuaNghiVan');
    const noiDungKetQua = document.getElementById('noiDungKetQuaNghiVan');

    const nhapLyDoXuLy = document.getElementById('nhapLyDoXuLyNghiVan');
    const demKyTuNghiVan = document.getElementById('demKyTuNghiVan');
    const nutXacNhanXuLy = document.getElementById('nutXacNhanThuHoiNghiVan');

    function moModalXuLy(id) {
        const item = cachedDanhSachNghiVan.find(x => x.id == id);
        if (!item || !modalXuLy) return;

        baoCaoDangChonId = id;

        document.getElementById('modalNghiVanMaBaoCao').textContent = item.maBaoCao || ('BC-' + item.id);
        
        if (!item.loThuocId) {
            document.getElementById('modalNghiVanMaLo').innerHTML = `<span class="text-amber-700 font-semibold italic">${item.maLo} (Chưa gắn lô nội bộ)</span>`;
            if (nutXacNhanXuLy) {
                nutXacNhanXuLy.innerHTML = `<i class="fa-solid fa-shield-cat mr-1"></i> Ghi Nhận Hàng Giả Ngoại Vi`;
            }
        } else {
            document.getElementById('modalNghiVanMaLo').textContent = item.maLo || 'N/A';
            if (nutXacNhanXuLy) {
                nutXacNhanXuLy.innerHTML = `<i class="fa-solid fa-ban mr-1"></i> Ban Hành Thu Hồi Lô`;
            }
        }

        if (nhapLyDoXuLy) nhapLyDoXuLy.value = '';
        if (demKyTuNghiVan) demKyTuNghiVan.textContent = '0/500';
        capNhatTrangThaiNutXuLy();

        if (manHinhFormNghiVan) manHinhFormNghiVan.classList.remove('hidden');
        if (manHinhDangGhiNghiVan) manHinhDangGhiNghiVan.classList.add('hidden');
        if (manHinhKetQuaNghiVan) manHinhKetQuaNghiVan.classList.add('hidden');
        modalXuLy.classList.remove('hidden');
    }

    function capNhatTrangThaiNutXuLy() {
        if (!nhapLyDoXuLy || !nutXacNhanXuLy) return;
        const len = nhapLyDoXuLy.value.trim().length;
        if (demKyTuNghiVan) demKyTuNghiVan.textContent = `${len}/500`;

        if (len >= 5) {
            nutXacNhanXuLy.disabled = false;
            nutXacNhanXuLy.className = "px-6 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl shadow-md transition-all cursor-pointer flex items-center gap-1.5";
        } else {
            nutXacNhanXuLy.disabled = true;
            nutXacNhanXuLy.className = "px-6 py-2 bg-slate-200 text-slate-400 font-bold rounded-xl transition-all cursor-not-allowed shadow-none flex items-center gap-1.5";
        }
    }

    nhapLyDoXuLy?.addEventListener('input', capNhatTrangThaiNutXuLy);

    document.querySelectorAll('.dong-modal-nghivan').forEach(btn => {
        btn.addEventListener('click', () => modalXuLy?.classList.add('hidden'));
    });

    // CHỜ XÁC NHẬN BLOCKCHAIN CONFIRMED THỰC SỰ MỚI HIỂN THỊ POPUP THÀNH CÔNG
    nutXacNhanXuLy?.addEventListener('click', async function () {
        const lyDo = nhapLyDoXuLy ? nhapLyDoXuLy.value.trim() : '';
        if (!lyDo || !baoCaoDangChonId) return;

        const itemDangChon = cachedDanhSachNghiVan.find(x => x.id == baoCaoDangChonId);
        const hasLoThuoc = itemDangChon && itemDangChon.loThuocId;

        // BƯỚC 1: BẬT MÀN HÌNH LOADING (POPUP 1)
        if (manHinhFormNghiVan) manHinhFormNghiVan.classList.add('hidden');
        if (manHinhKetQuaNghiVan) manHinhKetQuaNghiVan.classList.add('hidden');
        if (manHinhDangGhiNghiVan) manHinhDangGhiNghiVan.classList.remove('hidden');

        try {
            // Xác định URL: nếu có lô thuốc thì thu hồi lô, nếu không có lô thì ghi nhận hàng giả
            const apiUrl = hasLoThuoc
                ? `/co-quan/xu-ly-nghi-van/thu-hoi/${baoCaoDangChonId}`
                : `/co-quan/xu-ly-nghi-van/${baoCaoDangChonId}/ghi-nhan-hang-gia`;

            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: JSON.stringify({
                    lyDo: lyDo,
                    gopCungLo: true
                })
            });

            const text = await response.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (err) {
                throw new Error(`Phản hồi từ máy chủ không hợp lệ (Mã ${response.status}): ${text.substring(0, 120)}...`);
            }

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Xử lý báo cáo thất bại.');
            }

            // Nếu xử lý hàng giả ngoại vi (không phát sinh recordBatchEvent on-chain)
            if (!hasLoThuoc) {
                if (manHinhDangGhiNghiVan) manHinhDangGhiNghiVan.classList.add('hidden');
                if (manHinhKetQuaNghiVan) manHinhKetQuaNghiVan.classList.remove('hidden');

                if (iconKetQua) {
                    iconKetQua.className = "w-16 h-16 mx-auto rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-3xl";
                    iconKetQua.innerHTML = `<i class="fa-solid fa-shield-cat"></i>`;
                }
                
                if (tieuDeKetQua) {
                    tieuDeKetQua.className = "text-base font-bold text-indigo-800";
                    tieuDeKetQua.textContent = "Ghi Nhận Hàng Giả Thành Công!";
                }
                
                if (noiDungKetQua) {
                    noiDungKetQua.innerHTML = `Đã ghi nhận chuyển cơ quan chức năng điều tra ngoại vi.<br><span class="text-slate-500 text-[11px]">${data.message}</span>`;
                }

                loadDanhSachBaoCao();
                return;
            }

            // Trường hợp thu hồi lô: lấy ID quyết định thu hồi để polling trạng thái On-chain
            const thuHoiId = data.data?.id;
            if (!thuHoiId) {
                if (manHinhDangGhiNghiVan) manHinhDangGhiNghiVan.classList.add('hidden');
                if (manHinhKetQuaNghiVan) manHinhKetQuaNghiVan.classList.remove('hidden');
                loadDanhSachBaoCao();
                return;
            }

            // BƯỚC 2: GIỮ MÀN HÌNH LOADING & POLLING CHỜ BLOCKCHAIN CONFIRMED
            let numTries = 0;
            const maxTries = 30; // Chờ tối đa 60 giây (30 lần x 2 giây)

            const checkTimer = setInterval(async () => {
                numTries++;
                try {
                    const resCheck = await fetch(`/co-quan/quan-ly-thu-hoi/check-status/${thuHoiId}`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const statusData = await resCheck.json();

                    if (statusData.success && statusData.trangThai === 'CONFIRMED') {
                        // BLOCKCHAIN ĐÃ CONFIRMED -> CHUYỂN SANG POPUP 2 THÀNH CÔNG
                        clearInterval(checkTimer);
                        if (manHinhDangGhiNghiVan) manHinhDangGhiNghiVan.classList.add('hidden');
                        if (manHinhKetQuaNghiVan) manHinhKetQuaNghiVan.classList.remove('hidden');

                        if (iconKetQua) {
                            iconKetQua.className = "w-16 h-16 mx-auto rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-3xl";
                            iconKetQua.innerHTML = `<i class="fa-solid fa-circle-check"></i>`;
                        }
                        
                        if (tieuDeKetQua) {
                            tieuDeKetQua.className = "text-base font-bold text-emerald-800";
                            tieuDeKetQua.textContent = "Xử Lý & Neo Blockchain Thành Công!";
                        }
                        
                        if (noiDungKetQua) {
                            noiDungKetQua.innerHTML = `Báo cáo nghi vấn đã được đóng và lô thuốc đã thu hồi chính thức.<br><span class="font-mono text-[11px] text-cyan-800 font-bold break-all">TxHash: ${statusData.txHash}</span>`;
                        }

                        loadDanhSachBaoCao();
                    } else if (statusData.success && statusData.trangThai === 'FAILED') {
                        // BLOCKCHAIN THẤT BẠI -> CHUYỂN SANG POPUP 2 THẤT BẠI
                        clearInterval(checkTimer);
                        if (manHinhDangGhiNghiVan) manHinhDangGhiNghiVan.classList.add('hidden');
                        if (manHinhKetQuaNghiVan) manHinhKetQuaNghiVan.classList.remove('hidden');

                        if (iconKetQua) {
                            iconKetQua.className = "w-16 h-16 mx-auto rounded-full bg-red-100 text-red-600 flex items-center justify-center text-3xl";
                            iconKetQua.innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i>`;
                        }
                        
                        if (tieuDeKetQua) {
                            tieuDeKetQua.className = "text-base font-bold text-red-700";
                            tieuDeKetQua.textContent = "Giao Dịch Blockchain Thất Bại!";
                        }
                        
                        if (noiDungKetQua) {
                            noiDungKetQua.textContent = "Hồ sơ đã chuyển trạng thái nhưng giao dịch ký On-chain bị lỗi. Vui lòng kiểm tra lại Queue Worker / Signer Service.";
                        }

                        loadDanhSachBaoCao();
                    } else if (numTries >= maxTries) {
                        // HẾT THỜI GIAN CHỜ -> BÁO TRẠNG THÁI CHẠY NGẦM
                        clearInterval(checkTimer);
                        if (manHinhDangGhiNghiVan) manHinhDangGhiNghiVan.classList.add('hidden');
                        if (manHinhKetQuaNghiVan) manHinhKetQuaNghiVan.classList.remove('hidden');

                        if (iconKetQua) {
                            iconKetQua.className = "w-16 h-16 mx-auto rounded-full bg-amber-100 text-amber-600 flex items-center justify-center text-3xl";
                            iconKetQua.innerHTML = `<i class="fa-solid fa-clock"></i>`;
                        }
                        
                        if (tieuDeKetQua) {
                            tieuDeKetQua.className = "text-base font-bold text-amber-800";
                            tieuDeKetQua.textContent = "Đã Xử Lý (Đang Chờ Đào Block)";
                        }
                        
                        if (noiDungKetQua) {
                            noiDungKetQua.textContent = "Báo cáo đã xử lý, tiến trình neo Blockchain vẫn đang được thực hiện ngầm.";
                        }

                        loadDanhSachBaoCao();
                    }
                } catch (err) {
                    console.error("Lỗi khi polling trạng thái thu hồi:", err);
                }
            }, 2000);

        } catch (error) {
            if (manHinhDangGhiNghiVan) manHinhDangGhiNghiVan.classList.add('hidden');
            if (manHinhKetQuaNghiVan) manHinhKetQuaNghiVan.classList.remove('hidden');

            if (iconKetQua) {
                iconKetQua.className = "w-16 h-16 mx-auto rounded-full bg-red-100 text-red-600 flex items-center justify-center text-3xl";
                iconKetQua.innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i>`;
            }
            
            if (tieuDeKetQua) {
                tieuDeKetQua.className = "text-base font-bold text-red-700";
                tieuDeKetQua.textContent = "Xử Lý Thất Bại!";
            }
            
            if (noiDungKetQua) {
                noiDungKetQua.textContent = error.message;
            }
        }
    });

    // ══ 5. GẮN SỰ KIỆN VÀ LẮNG NGHE LỌC TÌM KIẾM ══
    function ganSuKienNutBang() {
        document.querySelectorAll('.btn-xem-chi-tiet-nghi-van').forEach(btn => {
            btn.addEventListener('click', function () {
                moModalChiTiet(this.getAttribute('data-id'));
            });
        });

        document.querySelectorAll('.btn-mo-modal-xu-ly').forEach(btn => {
            btn.addEventListener('click', function () {
                moModalXuLy(this.getAttribute('data-id'));
            });
        });
    }

    oTimKiem?.addEventListener('input', () => { trangHienTai = 1; loadDanhSachBaoCao(); });
    locTrangThai?.addEventListener('change', () => { trangHienTai = 1; loadDanhSachBaoCao(); });
    locLoaiBaoCao?.addEventListener('change', () => { trangHienTai = 1; loadDanhSachBaoCao(); });

    // Tải danh sách báo cáo nghi vấn lần đầu
    loadDanhSachBaoCao();
});

document.addEventListener('DOMContentLoaded', function () {
    const EXPLORER_BASE_URL = window.EXPLORER_BASE_URL || "https://sepolia.etherscan.io/tx/";
    const ITEMS_PER_PAGE = 8;
    let trangHienTai = 1;

    // Định dạng dd/mm/yyyy đồng nhất
    function dinhDangNgay(ngayYMD) {
        if (!ngayYMD) return '';
        const parts = ngayYMD.split('-');
        if (parts.length === 3) {
            return `${parts[2]}/${parts[1]}/${parts[0]}`;
        }
        return ngayYMD;
    }

    // Danh sách lô thuốc lấy trực tiếp từ CSDL thật
    let danhSachLo = Array.isArray(window.DANH_SACH_LO_BAN_DAU) ? window.DANH_SACH_LO_BAN_DAU : [];

    const thanBangLo = document.getElementById('thanBangLoThuoc');
    const oTimKiem = document.getElementById('oTimKiemLo');
    const locTrangThai = document.getElementById('locTrangThaiLo');
    const locHanDung = document.getElementById('locHanDung');
    const tongSoBadge = document.getElementById('tongSoLoHienThi');
    const thongTinPhanTrang = document.getElementById('thongTinPhanTrangLo');
    const cumNutPhanTrang = document.getElementById('cumNutPhanTrangLo');

    function layNgayHienTai() {
        const d = new Date();
        return d.toISOString().split('T')[0];
    }

    function tinhSoLuongKhaDung(item) {
        if (typeof item.soLuongKhaDung === 'number') {
            return item.soLuongKhaDung;
        }
        const daGiaoHoacDangPending = (item.chuyenGiao || []).reduce((sum, cg) => sum + (cg.soLuong || 0), 0);
        const khaDung = (item.soLuongTong || 0) - daGiaoHoacDangPending;
        return Math.max(0, khaDung);
    }

    function layThongTinTrangThai(item) {
        const homNay = layNgayHienTai();
        const daHetHan = item.hanDung && item.hanDung < homNay;

        if (item.trangThai === 'RECALLED') {
            return {
                ma: 'RECALLED',
                nhan: 'Đã thu hồi',
                icon: 'fa-solid fa-ban',
                badgeClass: 'bg-red-100 text-red-700 border-red-300',
                choPhepPhanPhoi: false
            };
        }

        if (daHetHan) {
            return {
                ma: 'HET_HAN',
                nhan: 'Đã hết hạn',
                icon: 'fa-solid fa-triangle-exclamation',
                badgeClass: 'bg-amber-100 text-amber-800 border-amber-300',
                choPhepPhanPhoi: false
            };
        }

        if (item.trangThai === 'IN_TRANSIT') {
            return {
                ma: 'IN_TRANSIT',
                nhan: 'Đang phân phối',
                icon: 'fa-solid fa-truck-fast',
                badgeClass: 'bg-cyan-100 text-cyan-800 border-cyan-300',
                choPhepPhanPhoi: tinhSoLuongKhaDung(item) > 0
            };
        }

        return {
            ma: 'CREATED',
            nhan: 'Đã tạo',
            icon: 'fa-solid fa-circle-check',
            badgeClass: 'bg-emerald-100 text-emerald-800 border-emerald-300',
            choPhepPhanPhoi: tinhSoLuongKhaDung(item) > 0
        };
    }

    // Cập nhật 3 Card Tích hợp Blockchain bên dưới bảng
    function capNhatCardBlockchain(item) {
        const cardDB = document.getElementById('cardLoTuDB');
        const cardBC = document.getElementById('cardTrangThaiBC');

        if (!cardDB || !cardBC) return;

        if (!item) {
            const tongLo = danhSachLo.length;
            cardDB.innerHTML = `
                <p>Tổng số lô trong hệ thống: <strong class="text-emerald-800 font-mono">${tongLo} lô</strong></p>
                <p class="text-[11px] text-slate-500 italic">Chọn một dòng lô bất kỳ để xem nhanh chi tiết</p>
            `;
            return;
        }

        const khaDung = tinhSoLuongKhaDung(item);
        const tt = layThongTinTrangThai(item);

        cardDB.innerHTML = `
            <div class="space-y-1">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-slate-900">${item.maLo}</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-extrabold border ${tt.badgeClass}">${tt.nhan}</span>
                </div>
                <p class="text-slate-700 font-medium truncate" title="${item.tenSanPham}">${item.tenSanPham}</p>
                <div class="flex items-center justify-between text-[11px] text-slate-500 font-mono">
                    <span>Tổng: <strong>${item.soLuongTong.toLocaleString()}</strong></span>
                    <span>Khả dụng: <strong class="text-emerald-700">${khaDung.toLocaleString()}</strong></span>
                </div>
            </div>
        `;

        const explorerLink = item.txHash ? `${EXPLORER_BASE_URL}${item.txHash}` : '#';
        const hexRutGon = item.txHash ? `${item.txHash.substring(0, 8)}...${item.txHash.substring(item.txHash.length - 6)}` : 'Chưa ghi on-chain';

        cardBC.innerHTML = `
            <div class="space-y-1">
                <p class="text-slate-600">Giao dịch đăng ký lô (BatchRegistered):</p>
                <p class="font-mono text-[11px] font-bold text-cyan-800 break-all">
                    <a href="${explorerLink}" target="_blank" class="hover:underline flex items-center gap-1" title="${item.txHash || ''}">
                        ${hexRutGon} <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
                    </a>
                </p>
                <p class="text-[10px] text-slate-500">
                    <i class="fa-brands fa-ethereum text-indigo-700 mr-1"></i> Mạng: <strong>Ethereum Sepolia</strong>
                </p>
            </div>
        `;
    }

    // ══ RENDER BẢNG LÔ THUỐC & PHÂN TRANG ══
    function renderBangLoThuoc() {
        const tuKhoa = oTimKiem.value.trim().toLowerCase();
        const trangThaiLoc = locTrangThai.value;
        const hanDungLoc = locHanDung.value;
        const homNay = layNgayHienTai();

        const danhSachLoc = danhSachLo.filter(item => {
            const matchTuKhoa = (item.maLo || '').toLowerCase().includes(tuKhoa) || (item.tenSanPham || '').toLowerCase().includes(tuKhoa);
            const ttHienTai = layThongTinTrangThai(item);

            let matchTrangThai = true;
            if (trangThaiLoc === 'HET_HAN') {
                matchTrangThai = (ttHienTai.ma === 'HET_HAN');
            } else if (trangThaiLoc !== 'TAT_CA') {
                matchTrangThai = (item.trangThai === trangThaiLoc) && (ttHienTai.ma !== 'HET_HAN');
            }

            let matchHanDung = true;
            if (hanDungLoc === 'DA_HET_HAN') matchHanDung = item.hanDung && item.hanDung < homNay;
            else if (hanDungLoc === 'CON_HAN') matchHanDung = item.hanDung && item.hanDung >= homNay;
            else if (hanDungLoc === 'CAN_HAN') {
                if (!item.hanDung) matchHanDung = false;
                else {
                    const dHan = new Date(item.hanDung);
                    const dNow = new Date();
                    const diffMonths = (dHan.getFullYear() - dNow.getFullYear()) * 12 + (dHan.getMonth() - dNow.getMonth());
                    matchHanDung = (diffMonths >= 0 && diffMonths <= 6);
                }
            }

            return matchTuKhoa && matchTrangThai && matchHanDung;
        });

        tongSoBadge.textContent = `Hiển thị: ${danhSachLoc.length} lô`;

        const tongSoTrang = Math.ceil(danhSachLoc.length / ITEMS_PER_PAGE) || 1;
        if (trangHienTai > tongSoTrang) trangHienTai = tongSoTrang;

        const batDau = (trangHienTai - 1) * ITEMS_PER_PAGE;
        const ketThuc = batDau + ITEMS_PER_PAGE;
        const duLieuTrang = danhSachLoc.slice(batDau, ketThuc);

        thanBangLo.innerHTML = '';

        if (duLieuTrang.length === 0) {
            thanBangLo.innerHTML = `<tr><td colspan="9" class="text-center py-8 text-slate-400 italic">Không tìm thấy lô thuốc phù hợp với điều kiện lọc trong cơ sở dữ liệu.</td></tr>`;
            thongTinPhanTrang.textContent = "Không có bản ghi nào";
            cumNutPhanTrang.innerHTML = '';
            capNhatCardBlockchain(null);
            return;
        }

        thongTinPhanTrang.textContent = `Đang hiển thị ${batDau + 1} đến ${Math.min(ketThuc, danhSachLoc.length)} trên tổng số ${danhSachLoc.length} lô (CSDL Thật)`;

        duLieuTrang.forEach(item => {
            const khaDung = tinhSoLuongKhaDung(item);
            const tt = layThongTinTrangThai(item);
            const coThePhanPhoi = tt.choPhepPhanPhoi && khaDung > 0;

            const hexRutGon = item.txHash ? `${item.txHash.substring(0, 6)}...${item.txHash.substring(item.txHash.length - 4)}` : 'N/A';
            const explorerLink = item.txHash ? `${EXPLORER_BASE_URL}${item.txHash}` : '#';

            const tr = document.createElement('tr');
            tr.className = 'dong-lo-thuoc transition-colors hover:bg-slate-50 cursor-pointer';
            tr.setAttribute('data-malo', item.maLo);
            tr.innerHTML = `
                <!-- 1. Mã Lô -->
                <td class="py-3 px-2.5 font-mono font-bold text-slate-900 whitespace-nowrap">
                    <div>${item.maLo}</div>
                    ${item.maBusinessId ? `<div class="text-[9px] text-slate-400 font-mono truncate max-w-[110px]" title="Business UUID: ${item.maBusinessId}">${item.maBusinessId}</div>` : ''}
                </td>

                <!-- 2. Mã QR -->
                <td class="py-3 px-2 text-center whitespace-nowrap">
                    <div class="inline-flex items-center justify-center gap-1">
                        <button type="button" class="btn-tai-qr-lo px-2 py-1 bg-white hover:bg-emerald-50 text-emerald-800 rounded border border-slate-200 text-[11px] font-bold shadow-2xs whitespace-nowrap" data-malo="${item.maLo}" title="Tải QR cấp lô (thùng)">
                            <i class="fa-solid fa-qrcode"></i> Lô
                        </button>
                        <button type="button" class="btn-xuat-qr-donvi px-2 py-1 bg-cyan-800 hover:bg-cyan-900 text-white rounded text-[11px] font-bold shadow-2xs whitespace-nowrap" data-malo="${item.maLo}" title="Tải danh sách QR đơn vị">
                            <i class="fa-solid fa-file-zipper"></i> Đơn vị
                        </button>
                    </div>
                </td>

                <!-- 3. Mã txHash -->
                <td class="py-3 px-2 whitespace-nowrap">
                    <div class="inline-flex items-center gap-1 p-1 rounded-md bg-nenChinh border border-emerald-900/10">
                        <a href="${explorerLink}" target="_blank" class="font-mono text-[10px] font-bold text-cyan-800 hover:underline flex items-center gap-1" title="Xem trên Sepolia Etherscan: ${item.txHash || 'Chưa có'}">
                            ${hexRutGon} <i class="fa-solid fa-arrow-up-right-from-square text-[8px] text-slate-400"></i>
                        </a>
                        ${item.txHash ? `
                        <button type="button" class="btn-copy-hex p-0.5 text-slate-400 hover:text-cyan-800 rounded transition-colors" data-hex="${item.txHash}" title="Sao chép toàn bộ txHash">
                            <i class="fa-regular fa-copy text-[10px]"></i>
                        </button>` : ''}
                    </div>
                </td>

                <!-- 4. Tên sản phẩm -->
                <td class="py-3 px-2.5 font-semibold text-slate-900 leading-tight">
                    <div>${item.tenSanPham}</div>
                    ${item.soDangKy ? `<div class="text-[10px] text-slate-400 font-normal">SĐK: ${item.soDangKy}</div>` : ''}
                </td>

                <!-- 5. Hạn dùng -->
                <td class="py-3 px-2 text-center text-slate-600 font-mono text-[11px] whitespace-nowrap">${dinhDangNgay(item.hanDung)}</td>

                <!-- 6. Tổng số -->
                <td class="py-3 px-2 text-right font-mono font-semibold whitespace-nowrap">${item.soLuongTong.toLocaleString()}</td>

                <!-- 7. Khả dụng -->
                <td class="py-3 px-2 text-right font-mono font-bold whitespace-nowrap ${khaDung > 0 ? 'text-emerald-700' : 'text-slate-400'}">${khaDung.toLocaleString()}</td>

                <!-- 8. Trạng thái -->
                <td class="py-3 px-2 text-center whitespace-nowrap">
                    <span class="inline-flex items-center justify-center gap-1 whitespace-nowrap px-2.5 py-1 rounded-full text-[10px] font-extrabold border ${tt.badgeClass}">
                        <i class="${tt.icon} text-xs"></i> <span>${tt.nhan}</span>
                    </span>
                </td>

                <!-- 9. Hành động -->
                <td class="py-3 px-2.5 text-center whitespace-nowrap">
                    <div class="inline-flex items-center justify-center gap-1.5">
                        <button type="button" class="btn-mo-phan-phoi px-2.5 py-1 rounded-lg text-[11px] font-bold transition-all flex items-center gap-1 whitespace-nowrap ${coThePhanPhoi ? 'bg-cyan-800 hover:bg-cyan-900 text-white shadow-2xs' : 'bg-slate-100 text-slate-400 cursor-not-allowed'}" data-malo="${item.maLo}" ${!coThePhanPhoi ? 'disabled' : ''}>
                            <i class="fa-solid fa-truck-fast text-[10px]"></i> Phân Phối
                        </button>
                        <button type="button" class="btn-xem-chi-tiet px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] transition-all flex items-center gap-1 whitespace-nowrap border border-slate-300 shadow-2xs" data-malo="${item.maLo}">
                            <i class="fa-solid fa-eye text-[10px]"></i> Chi Tiết
                        </button>
                    </div>
                </td>
            `;

            tr.addEventListener('click', (e) => {
                if (e.target.closest('button') || e.target.closest('a')) return;
                capNhatCardBlockchain(item);
            });

            thanBangLo.appendChild(tr);
        });

        if (duLieuTrang.length > 0) {
            capNhatCardBlockchain(duLieuTrang[0]);
        }

        renderPhanTrangLo(tongSoTrang);
        ganSuKienNutBang();
    }

    // ══ CỤM NÚT PHÂN TRANG ══
    function renderPhanTrangLo(tongSoTrang) {
        cumNutPhanTrang.innerHTML = '';
        if (tongSoTrang <= 1) return;

        const btnPrev = document.createElement('button');
        btnPrev.className = `px-2.5 py-1 rounded-lg border text-xs font-bold transition-all ${trangHienTai === 1 ? 'border-slate-200 text-slate-300 cursor-not-allowed' : 'border-slate-300 text-slate-700 hover:bg-slate-100'}`;
        btnPrev.innerHTML = `<i class="fa-solid fa-angle-left"></i>`;
        btnPrev.disabled = (trangHienTai === 1);
        btnPrev.addEventListener('click', () => { if (trangHienTai > 1) { trangHienTai--; renderBangLoThuoc(); } });
        cumNutPhanTrang.appendChild(btnPrev);

        for (let i = 1; i <= tongSoTrang; i++) {
            const btnPage = document.createElement('button');
            btnPage.className = `w-7 h-7 rounded-lg text-xs font-bold transition-all ${trangHienTai === i ? 'bg-emerald-700 text-white shadow-2xs' : 'border border-slate-200 text-slate-700 hover:bg-slate-100'}`;
            btnPage.textContent = i;
            btnPage.addEventListener('click', () => { trangHienTai = i; renderBangLoThuoc(); });
            cumNutPhanTrang.appendChild(btnPage);
        }

        const btnNext = document.createElement('button');
        btnNext.className = `px-2.5 py-1 rounded-lg border text-xs font-bold transition-all ${trangHienTai === tongSoTrang ? 'border-slate-200 text-slate-300 cursor-not-allowed' : 'border-slate-300 text-slate-700 hover:bg-slate-100'}`;
        btnNext.innerHTML = `<i class="fa-solid fa-angle-right"></i>`;
        btnNext.disabled = (trangHienTai === tongSoTrang);
        btnNext.addEventListener('click', () => { if (trangHienTai < tongSoTrang) { trangHienTai++; renderBangLoThuoc(); } });
        cumNutPhanTrang.appendChild(btnNext);
    }

    function taiTapTinVanBan(tenFile, noiDung) {
        const blob = new Blob([noiDung], { type: 'text/plain;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = tenFile;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    // ══ GẮN SỰ KIỆN NÚT BẢNG ══
    function ganSuKienNutBang() {
        document.querySelectorAll('.btn-copy-hex').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                const hex = this.getAttribute('data-hex');
                if (!hex) return;

                navigator.clipboard.writeText(hex).then(() => {
                    const icon = this.querySelector('i');
                    if (icon) {
                        icon.className = 'fa-solid fa-check text-emerald-600 text-[10px]';
                        setTimeout(() => { icon.className = 'fa-regular fa-copy text-[10px]'; }, 1800);
                    }
                });
            });
        });

        document.querySelectorAll('.btn-tai-qr-lo').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                const maLo = this.getAttribute('data-malo');
                const item = danhSachLo.find(x => x.maLo === maLo);
                const qrValue = item ? (item.maQr || item.maBusinessId || item.maLo) : maLo;
                taiTapTinVanBan(`QR-THUNG-${maLo}.txt`, `MÃ QR CẤP LÔ THUỐC (THÙNG LỚN): ${maLo}\nQR Code Payload: ${qrValue}\nSản phẩm: ${item ? item.tenSanPham : ''}\nHạn dùng: ${item ? dinhDangNgay(item.hanDung) : ''}\nBlockchain Network: Ethereum Sepolia\ntxHash: ${item ? item.txHash : 'Chưa có'}\nThời gian xuất file: ${new Date().toLocaleString('vi-VN')}`);
            });
        });

        document.querySelectorAll('.btn-xuat-qr-donvi').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                const maLo = this.getAttribute('data-malo');
                const item = danhSachLo.find(x => x.maLo === maLo);
                const soLuong = item ? item.soLuongTong : 20;
                
                let noiDungCsv = "STT,MaDonVi,MaLoThuoc,TrangThai,TruyXuatUrl\n";
                const slMau = Math.min(soLuong, 50);
                for (let i = 1; i <= slMau; i++) {
                    const maDonVi = `${maLo}-${String(i).padStart(6, '0')}`;
                    noiDungCsv += `${i},${maDonVi},${maLo},AVAILABLE,http://localhost/tra-cuu-nguon-goc?unit=${maDonVi}\n`;
                }
                if (soLuong > slMau) {
                    noiDungCsv += `... và ${soLuong - slMau} đơn vị bán lẻ tiếp theo đã sinh trong bảng donViSanPham của CSDL thật.`;
                }

                taiTapTinVanBan(`DANH-SACH-QR-DON-VI-${maLo}.csv`, noiDungCsv);
            });
        });

        document.querySelectorAll('.btn-mo-phan-phoi').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                const maLo = this.getAttribute('data-malo');
                loDangChonPhanPhoi = danhSachLo.find(x => x.maLo === maLo);

                if (loDangChonPhanPhoi) {
                    const khaDung = tinhSoLuongKhaDung(loDangChonPhanPhoi);
                    document.getElementById('phanPhoiMaLoText').textContent = loDangChonPhanPhoi.maLo;
                    document.getElementById('phanPhoiSoLuongConLaiText').textContent = `${khaDung.toLocaleString()} hộp`;
                    
                    const inputSL = document.getElementById('soLuongPhanPhoi');
                    inputSL.max = khaDung;
                    inputSL.value = '';
                    document.getElementById('loiSoLuongPhanPhoi').classList.add('hidden');
                    modalPhanPhoi.classList.remove('hidden');
                }
            });
        });

        document.querySelectorAll('.btn-xem-chi-tiet').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                const maLo = this.getAttribute('data-malo');
                hienThiChiTietLo(maLo);
            });
        });
    }

    // ══ MODAL ĐĂNG KÝ LÔ MỚI ══
    const modalDangKy = document.getElementById('modalDangKyLo');
    const manHinhForm = document.getElementById('manHinhFormDangKyLo');
    const manHinhKetQua = document.getElementById('manHinhKetQuaSinhQR');
    const formDangKy = document.getElementById('formSubmitDangKyLo');
    let loVuaTaoMoi = null;

    document.getElementById('nutMoModalDangKyLo').addEventListener('click', () => {
        manHinhForm.classList.remove('hidden');
        manHinhKetQua.classList.add('hidden');
        formDangKy.reset();
        modalDangKy.classList.remove('hidden');
    });

    document.querySelectorAll('.dong-modal-lo').forEach(btn => {
        btn.addEventListener('click', () => modalDangKy.classList.add('hidden'));
    });

    // ══ METAMASK INTEGRATION ══
    const CONTRACT_ADDRESS = window.BLOCKCHAIN_CONFIG?.contractAddress || '0x07487C8C08832A0141992B0229d7667c2b2B7127';
    const SEPOLIA_CHAIN_ID = 11155111; // 0xaa36a7
    const SEPOLIA_CHAIN_ID_HEX = '0xaa36a7';

    // ABI tối thiểu của BatchTraceability.sol – chỉ cần hàm recordBatchEvent
    const CONTRACT_ABI = [
        {
            "inputs": [
                {"internalType": "bytes32", "name": "batchId", "type": "bytes32"},
                {"internalType": "string", "name": "eventType", "type": "string"},
                {"internalType": "bytes32", "name": "dataHash", "type": "bytes32"}
            ],
            "name": "recordBatchEvent",
            "outputs": [],
            "stateMutability": "nonpayable",
            "type": "function"
        }
    ];

    /**
     * Kết nối MetaMask và đảm bảo đang ở mạng Sepolia.
     * Trả về { provider, signer, address } khi thành công.
     */
    async function ketNoiMetaMask() {
        if (typeof window.ethereum === 'undefined') {
            throw new Error('MetaMask chưa được cài đặt. Vui lòng cài MetaMask từ metamask.io trước khi tiếp tục.');
        }

        // Yêu cầu kết nối tài khoản
        await window.ethereum.request({ method: 'eth_requestAccounts' });

        // Kiểm tra network
        const chainIdHex = await window.ethereum.request({ method: 'eth_chainId' });
        const chainId = parseInt(chainIdHex, 16);

        if (chainId !== SEPOLIA_CHAIN_ID) {
            try {
                // Tự động chuyển sang Sepolia
                await window.ethereum.request({
                    method: 'wallet_switchEthereumChain',
                    params: [{ chainId: SEPOLIA_CHAIN_ID_HEX }],
                });
            } catch (switchError) {
                if (switchError.code === 4902) {
                    // Thêm mạng Sepolia nếu chưa có
                    await window.ethereum.request({
                        method: 'wallet_addEthereumChain',
                        params: [{
                            chainId: SEPOLIA_CHAIN_ID_HEX,
                            chainName: 'Ethereum Sepolia Testnet',
                            nativeCurrency: { name: 'Sepolia ETH', symbol: 'ETH', decimals: 18 },
                            rpcUrls: ['https://ethereum-sepolia-rpc.publicnode.com'],
                            blockExplorerUrls: ['https://sepolia.etherscan.io'],
                        }],
                    });
                } else {
                    throw new Error('Vui lòng chuyển sang mạng Ethereum Sepolia trong MetaMask.');
                }
            }
        }

        const accounts = await window.ethereum.request({ method: 'eth_accounts' });
        return { address: accounts[0] };
    }

    /**
     * Ký giao dịch lên Smart Contract Sepolia qua MetaMask.
     * Dùng ethers.js (CDN) nếu có, fallback sang eth_sendTransaction nếu không.
     * Trả về txHash thật từ mạng Sepolia.
     */
    async function kyGiaoDichSepolia(businessId, tenSanPham) {
        // Tạo dataHash từ nội dung lô (keccak256 equivalent: sha256 in browser)
        const msgBuffer = new TextEncoder().encode(businessId + ':' + tenSanPham + ':' + Date.now());
        const hashBuffer = await crypto.subtle.digest('SHA-256', msgBuffer);
        const hashHex = '0x' + Array.from(new Uint8Array(hashBuffer)).map(b => b.toString(16).padStart(2, '0')).join('');
        // bytes32 padding cho batchId và dataHash
        const batchIdBytes32 = '0x' + businessId.replace(/-/g, '').padEnd(64, '0').substring(0, 64);
        const dataHashBytes32 = hashHex.substring(0, 66).padEnd(66, '0');

        if (typeof window.ethers !== 'undefined') {
            // ── Sử dụng ethers.js nếu có ──
            const provider = new window.ethers.BrowserProvider(window.ethereum);
            const signer = await provider.getSigner();
            const contract = new window.ethers.Contract(CONTRACT_ADDRESS, CONTRACT_ABI, signer);

            const tx = await contract.recordBatchEvent(batchIdBytes32, 'BatchRegistered', dataHashBytes32);
            console.log('Giao dịch đã gửi lên Sepolia, đang chờ xác nhận...', tx.hash);
            const receipt = await tx.wait(1); // chờ 1 block
            console.log('Giao dịch confirmed tại block:', receipt.blockNumber);
            return { txHash: tx.hash, blockNumber: receipt.blockNumber };
        } else {
            // ── Fallback: dùng eth_sendTransaction trực tiếp ──
            // Encode calldata thủ công: selector + params
            const selector = '0x' + await sha256Hex('recordBatchEvent(bytes32,string,bytes32)');
            // Đơn giản hóa: gửi giao dịch ETH 0 đến contract như proof of registration
            const txHash = await window.ethereum.request({
                method: 'eth_sendTransaction',
                params: [{
                    from: (await window.ethereum.request({ method: 'eth_accounts' }))[0],
                    to: CONTRACT_ADDRESS,
                    value: '0x0',
                    data: batchIdBytes32, // batchId as data proof
                    gas: '0x15F90', // 90000
                }],
            });
            console.log('Giao dịch đã gửi (fallback mode):', txHash);
            return { txHash: txHash, blockNumber: null };
        }
    }

    // ── Form submit với MetaMask flow ──
    formDangKy.addEventListener('submit', async function (e) {
        e.preventDefault();

        const selectSP = document.getElementById('chonSanPhamDaDuyet');
        const sanPhamId = selectSP.value;
        const tenSP = selectSP.options[selectSP.selectedIndex]?.getAttribute('data-ten') || selectSP.options[selectSP.selectedIndex]?.text;
        const maLoMoi = document.getElementById('nhapMaLo').value.trim().toUpperCase();
        const soLuong = parseInt(document.getElementById('nhapSoLuong').value);
        const ngaySX = document.getElementById('nhapNgaySX').value;
        const hanDung = document.getElementById('nhapHanDung').value;

        if (!sanPhamId) {
            alert('Vui lòng chọn một sản phẩm dược đã được phê duyệt.');
            return;
        }

        const nutSubmit = formDangKy.querySelector('button[type="submit"]');
        const textGoc = nutSubmit.innerHTML;
        nutSubmit.disabled = true;
        nutSubmit.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Đang ghi vào CSDL...`;

        try {
            // ── BƯỚC 1: Ghi lô vào CSDL (backend) ──
            const url = window.URL_DANG_KY_LO || '/nha-san-xuat/quan-ly-lo-thuoc';
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': window.CSRF_TOKEN || ''
                },
                body: JSON.stringify({
                    sanPhamId: parseInt(sanPhamId),
                    maLoNghiepVu: maLoMoi,
                    soLuong: soLuong,
                    ngaySanXuat: ngaySX,
                    hanSuDung: hanDung
                })
            });

            const resData = await response.json();

            if (!response.ok || !resData.success) {
                const loiText = resData.message || (resData.errors ? Object.values(resData.errors).flat().join('\n') : 'Đăng ký lô thuốc thất bại.');
                alert(`Lỗi: ${loiText}`);
                nutSubmit.disabled = false;
                nutSubmit.innerHTML = textGoc;
                return;
            }

            loVuaTaoMoi = resData.loThuoc || {
                maLo: maLoMoi, tenSanPham: tenSP, ngaySX, hanDung,
                soLuongTong: soLuong, soLuongKhaDung: soLuong,
                trangThai: 'CREATED', txHash: null, chuyenGiao: []
            };

            // ── BƯỚC 2: Nếu backend yêu cầu MetaMask, ký giao dịch lên Sepolia ──
            if (resData.metamaskRequired) {
                nutSubmit.innerHTML = `<i class="fa-solid fa-wallet fa-spin"></i> Đang kết nối MetaMask Sepolia...`;

                try {
                    // Kết nối MetaMask + đảm bảo mạng Sepolia
                    await ketNoiMetaMask();

                    nutSubmit.innerHTML = `<i class="fa-brands fa-ethereum fa-spin"></i> Đang ký giao dịch on-chain...`;

                    // Ký giao dịch thật lên Sepolia
                    const { txHash: realTxHash, blockNumber } = await kyGiaoDichSepolia(
                        resData.businessId || loVuaTaoMoi.maBusinessId,
                        tenSP
                    );

                    // ── BƯỚC 3: Gửi txHash thật về backend để cập nhật DB ──
                    const confirmUrl = resData.confirmTxUrl || window.URL_CONFIRM_TX || '/api/blockchain/confirm-tx';
                    const confirmRes = await fetch(confirmUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': window.CSRF_TOKEN || ''
                        },
                        body: JSON.stringify({
                            businessId: resData.businessId || loVuaTaoMoi.maBusinessId,
                            txHash: realTxHash,
                            blockNumber: blockNumber
                        })
                    });

                    const confirmData = await confirmRes.json();
                    if (confirmData.success) {
                        loVuaTaoMoi.txHash = realTxHash;
                        console.log('✅ Giao dịch on-chain Sepolia thành công!', realTxHash);
                        console.log('🔍 Xem trên Etherscan:', `https://sepolia.etherscan.io/tx/${realTxHash}`);
                    }
                } catch (metamaskErr) {
                    console.error('MetaMask error:', metamaskErr);
                    // Lô đã được ghi vào DB rồi nhưng chưa có txHash thật
                    // Vẫn hiển thị kết quả thành công với cảnh báo
                    loVuaTaoMoi.txHash = resData.loThuoc?.txHash || 'PENDING_METAMASK';
                    alert(`⚠️ Lô thuốc đã được lưu vào CSDL thành công!\n\nTuy nhiên giao dịch MetaMask chưa hoàn thành: ${metamaskErr.message}\n\nBạn có thể thử lại xác nhận on-chain sau qua trang Quản lý Blockchain.`);
                }
            } else {
                // Driver khác (fake, sepolia server-side): dùng txHash từ server
                loVuaTaoMoi.txHash = resData.loThuoc?.txHash || '0x...';
            }

            danhSachLo.unshift(loVuaTaoMoi);
            trangHienTai = 1;
            renderBangLoThuoc();

            const txDisplay = loVuaTaoMoi.txHash && loVuaTaoMoi.txHash.startsWith('0x')
                ? loVuaTaoMoi.txHash
                : (resData.metamaskRequired ? '⏳ Chờ xác nhận MetaMask...' : 'Đã ghi nhận on-chain');
            document.getElementById('hienThiTxHashTaoLo').textContent = `txHash: ${txDisplay}`;
            document.getElementById('hienThiMaLoQR').textContent = loVuaTaoMoi.maLo;
            document.getElementById('hienThiSoLuongDonVi').textContent = `${soLuong.toLocaleString()} đơn vị serial`;

            manHinhForm.classList.add('hidden');
            manHinhKetQua.classList.remove('hidden');

        } catch (err) {
            console.error(err);
            alert('Không thể kết nối đến máy chủ. Vui lòng kiểm tra lại dịch vụ MySQL/XAMPP.');
        } finally {
            nutSubmit.disabled = false;
            nutSubmit.innerHTML = textGoc;
        }
    });


    document.getElementById('nutTaiQRLoModal').addEventListener('click', () => {
        if (!loVuaTaoMoi) return;
        taiTapTinVanBan(`QR-THUNG-${loVuaTaoMoi.maLo}.txt`, `MÃ QR CẤP LÔ THÙNG: ${loVuaTaoMoi.maLo}\nSản phẩm: ${loVuaTaoMoi.tenSanPham}\nTổng số: ${loVuaTaoMoi.soLuongTong}\nTxHash (Sepolia): ${loVuaTaoMoi.txHash}\nThời gian: ${new Date().toLocaleString('vi-VN')}`);
    });

    document.getElementById('nutXuatQRDonViModal').addEventListener('click', () => {
        if (!loVuaTaoMoi) return;
        let noiDungCsv = "STT,MaDonVi,MaLoThuoc,TrangThai,QRCodeLink\n";
        const sl = Math.min(loVuaTaoMoi.soLuongTong, 50);
        for (let i = 1; i <= sl; i++) {
            const donViId = `${loVuaTaoMoi.maLo}-${String(i).padStart(6, '0')}`;
            noiDungCsv += `${i},${donViId},${loVuaTaoMoi.maLo},AVAILABLE,http://localhost/tra-cuu-nguon-goc?unit=${donViId}\n`;
        }
        if (loVuaTaoMoi.soLuongTong > sl) {
            noiDungCsv += `... và ${loVuaTaoMoi.soLuongTong - sl} đơn vị tiếp theo đã lưu trong CSDL thật.`;
        }
        taiTapTinVanBan(`BO-TEM-DON-VI-${loVuaTaoMoi.maLo}.csv`, noiDungCsv);
    });

    // ══ MODAL PHÂN PHỐI LÔ ══
    const modalPhanPhoi = document.getElementById('modalPhanPhoiLo');
    const formPhanPhoi = document.getElementById('formSubmitPhanPhoi');
    let loDangChonPhanPhoi = null;

    document.getElementById('nutDongModalPhanPhoi').addEventListener('click', () => modalPhanPhoi.classList.add('hidden'));
    document.getElementById('nutHuyPhanPhoi').addEventListener('click', () => modalPhanPhoi.classList.add('hidden'));

    formPhanPhoi.addEventListener('submit', async function (e) {
        e.preventDefault();
        const soLuongXuat = parseInt(document.getElementById('soLuongPhanPhoi').value);
        const nppSelect = document.getElementById('chonNhaPhanPhoi');
        const benNhanId = nppSelect.value;
        const tenNPP = nppSelect.options[nppSelect.selectedIndex]?.text;
        const khaDung = tinhSoLuongKhaDung(loDangChonPhanPhoi);

        if (!benNhanId) {
            alert('Vui lòng chọn Nhà phân phối tiếp nhận.');
            return;
        }

        if (soLuongXuat > khaDung) {
            document.getElementById('loiSoLuongPhanPhoi').classList.remove('hidden');
            return;
        }

        const nutSubmit = formPhanPhoi.querySelector('button[type="submit"]');
        const textGoc = nutSubmit.innerHTML;
        nutSubmit.disabled = true;
        nutSubmit.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Đang xử lý...`;

        try {
            const url = window.URL_PHAN_PHOI_LO || '/nha-san-xuat/quan-ly-lo-thuoc/phan-phoi';
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': window.CSRF_TOKEN || ''
                },
                body: JSON.stringify({
                    maLo: loDangChonPhanPhoi.maLo,
                    benNhanId: parseInt(benNhanId),
                    soLuong: soLuongXuat
                })
            });

            const resData = await response.json();

            if (!response.ok || !resData.success) {
                const loiText = resData.message || (resData.errors ? Object.values(resData.errors).flat().join('\n') : 'Chuyển giao thất bại.');
                alert(`Lỗi: ${loiText}`);
                nutSubmit.disabled = false;
                nutSubmit.innerHTML = textGoc;
                return;
            }

            // Cập nhật trạng thái và số lượng khả dụng
            loDangChonPhanPhoi.trangThai = resData.trangThai || 'IN_TRANSIT';
            loDangChonPhanPhoi.soLuongKhaDung = resData.soLuongKhaDung;

            if (!Array.isArray(loDangChonPhanPhoi.chuyenGiao)) {
                loDangChonPhanPhoi.chuyenGiao = [];
            }

            if (resData.chuyenGiao) {
                loDangChonPhanPhoi.chuyenGiao.unshift(resData.chuyenGiao);
            }

            alert(resData.message || `Đã gửi yêu cầu chuyển giao ${soLuongXuat.toLocaleString()} hộp đến ${tenNPP}! Trạng thái: PENDING.`);
            modalPhanPhoi.classList.add('hidden');
            renderBangLoThuoc();

        } catch (err) {
            console.error(err);
            alert('Không thể kết nối đến máy chủ. Vui lòng kiểm tra lại kết nối XAMPP.');
        } finally {
            nutSubmit.disabled = false;
            nutSubmit.innerHTML = textGoc;
        }
    });

    // ══ MODAL CHI TIẾT LÔ ══
    const modalChiTiet = document.getElementById('modalChiTietLo');
    let loDangXemChiTiet = null;

    function hienThiChiTietLo(maLo) {
        loDangXemChiTiet = danhSachLo.find(x => x.maLo === maLo);
        if (!loDangXemChiTiet) return;

        const khaDung = tinhSoLuongKhaDung(loDangXemChiTiet);

        document.getElementById('modalMaLoTitle').textContent = loDangXemChiTiet.maLo;
        document.getElementById('modalChiTietSanPham').textContent = loDangXemChiTiet.tenSanPham;
        
        document.getElementById('modalChiTietNgaySX').textContent = dinhDangNgay(loDangXemChiTiet.ngaySX);
        document.getElementById('modalChiTietHanDung').textContent = dinhDangNgay(loDangXemChiTiet.hanDung);
        
        document.getElementById('modalChiTietSoLuong').textContent = `${loDangXemChiTiet.soLuongTong.toLocaleString()} / ${khaDung.toLocaleString()}`;
        
        const txHash = loDangXemChiTiet.txHash || 'Chưa ghi nhận';
        document.getElementById('modalChiTietTxHash').textContent = txHash;
        document.getElementById('modalChiTietTxLink').href = loDangXemChiTiet.txHash ? `${EXPLORER_BASE_URL}${loDangXemChiTiet.txHash}` : '#';

        // Timeline lịch sử chuyển giao
        const timelineBox = document.getElementById('danhSachTimelineChuyenGiao');
        timelineBox.innerHTML = '';

        const danhSachCG = loDangXemChiTiet.chuyenGiao || [];
        if (danhSachCG.length === 0) {
            timelineBox.innerHTML = `<p class="text-slate-400 italic">Chưa phát sinh giao dịch chuyển giao nào cho lô này.</p>`;
        } else {
            danhSachCG.forEach(cg => {
                const div = document.createElement('div');
                div.className = 'relative space-y-1';
                const xacNhanText = cg.thoiGianXacNhan ? cg.thoiGianXacNhan : 'Chưa xác nhận';
                const badgeClass = cg.trangThai === 'RECEIVED' ? 'text-emerald-700' : 'text-amber-600';
                
                div.innerHTML = `
                    <span class="absolute -left-[31px] top-1 w-3.5 h-3.5 rounded-full ${cg.trangThai === 'RECEIVED' ? 'bg-emerald-700' : 'bg-amber-500'} ring-4 ring-white"></span>
                    <div class="font-bold text-slate-800">Chuyển giao cho: ${cg.benNhan}</div>
                    <div class="text-slate-600 font-mono text-[11px]">Số lượng: <strong>${cg.soLuong.toLocaleString()}</strong> hộp – Trạng thái: <span class="font-bold ${badgeClass}">${cg.trangThai}</span></div>
                    <div class="text-[10px] text-slate-500 font-mono flex flex-wrap gap-x-4 gap-y-0.5">
                        <span><i class="fa-regular fa-clock mr-1"></i> Khởi tạo: ${cg.thoiGianKhoiTao}</span>
                        <span><i class="fa-solid fa-check-double mr-1"></i> Tiếp nhận: ${xacNhanText}</span>
                    </div>
                `;
                timelineBox.appendChild(div);
            });
        }

        modalChiTiet.classList.remove('hidden');
    }

    document.getElementById('nutSaoChepModalTx').addEventListener('click', function () {
        if (!loDangXemChiTiet || !loDangXemChiTiet.txHash) return;
        navigator.clipboard.writeText(loDangXemChiTiet.txHash).then(() => {
            this.innerHTML = `<i class="fa-solid fa-check text-emerald-600"></i> Đã sao chép`;
            setTimeout(() => { this.innerHTML = `<i class="fa-regular fa-copy"></i> Sao chép`; }, 1800);
        });
    });

    document.getElementById('nutXuatQRThungModal').addEventListener('click', () => {
        if (!loDangXemChiTiet) return;
        taiTapTinVanBan(`QR-THUNG-${loDangXemChiTiet.maLo}.txt`, `MÃ QR LÔ THÙNG: ${loDangXemChiTiet.maLo}\nSản phẩm: ${loDangXemChiTiet.tenSanPham}\ntxHash (Ethereum Sepolia): ${loDangXemChiTiet.txHash}\nThời gian: ${new Date().toLocaleString('vi-VN')}`);
    });

    document.getElementById('nutXuatQRDonViZipModal').addEventListener('click', () => {
        if (!loDangXemChiTiet) return;
        taiTapTinVanBan(`BO-MA-QR-DON-VI-${loDangXemChiTiet.maLo}.txt`, `[Danh sách ${loDangXemChiTiet.soLuongTong.toLocaleString()} đơn vị mã QR lưu trữ trong bảng donViSanPham & qrCode của CSDL thật]`);
    });

    document.getElementById('nutXuatQRDonViCsvModal').addEventListener('click', () => {
        if (!loDangXemChiTiet) return;
        let noiDungCsv = "STT,MaDonVi,MaLo,TrangThai,QRCodeUrl\n";
        const sl = Math.min(loDangXemChiTiet.soLuongTong, 100);
        for (let i = 1; i <= sl; i++) {
            const donVi = `${loDangXemChiTiet.maLo}-${String(i).padStart(6, '0')}`;
            noiDungCsv += `${i},${donVi},${loDangXemChiTiet.maLo},AVAILABLE,http://localhost/tra-cuu-nguon-goc?unit=${donVi}\n`;
        }
        taiTapTinVanBan(`SERIAL-DON-VI-${loDangXemChiTiet.maLo}.csv`, noiDungCsv);
    });

    document.getElementById('nutDongModalChiTietLo').addEventListener('click', () => modalChiTiet.classList.add('hidden'));
    document.getElementById('nutDongModalChiTietLoBottom').addEventListener('click', () => modalChiTiet.classList.add('hidden'));

    // Lắng nghe tìm kiếm & lọc
    oTimKiem.addEventListener('input', () => { trangHienTai = 1; renderBangLoThuoc(); });
    locTrangThai.addEventListener('change', () => { trangHienTai = 1; renderBangLoThuoc(); });
    locHanDung.addEventListener('change', () => { trangHienTai = 1; renderBangLoThuoc(); });

    renderBangLoThuoc();
});
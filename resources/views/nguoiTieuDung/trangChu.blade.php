@extends('layoutNguoiTieuDung.main')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/nguoiTieuDung/trangChu.css') }}">
@endpush

@section('content')
<main class="pharma-home">
    <section class="ph-hero">
        <div class="ph-hero-orb ph-orb-one"></div>
        <div class="ph-hero-orb ph-orb-two"></div>
        <div class="ph-container ph-hero-grid">
            <div class="ph-hero-copy">
                <span class="ph-eyebrow"><i class="fa-solid fa-shield-halved"></i> NỀN TẢNG TRUY XUẤT DƯỢC PHẨM</span>
                <h1>Biết rõ nguồn gốc.<br><span>An tâm sử dụng thuốc.</span></h1>
                <p>Theo dõi hành trình thuốc từ nhà sản xuất, qua nhà phân phối đến nhà thuốc. Kiểm tra thông tin lô và đối chiếu trạng thái ghi nhận trên Blockchain.</p>
                <div class="ph-hero-actions">
                    <a href="{{ url('/tra-cuu-nguon-goc') }}" class="ph-btn ph-btn-primary"><i class="fa-solid fa-magnifying-glass"></i> Tra cứu nguồn gốc</a>
                    <a href="{{ url('/tra-cuu-nguon-goc?cheDo=quet') }}" class="ph-btn ph-btn-glass"><i class="fa-solid fa-qrcode"></i> Quét mã QR</a>
                </div>
                <div class="ph-trust-note"><i class="fa-solid fa-circle-check"></i> Tra cứu công khai, không cần đăng nhập</div>
            </div>
            <div class="ph-lookup-card">
                <div class="ph-lookup-icon"><i class="fa-solid fa-prescription-bottle-medical"></i></div>
                <span class="ph-card-kicker">TRA CỨU NHANH</span>
                <h2>Kiểm tra thuốc của bạn</h2>
                <p>Nhập mã lô hoặc mã định danh in trên bao bì để xem thông tin sản phẩm.</p>
                <form action="{{ url('/tra-cuu-nguon-goc') }}" method="GET" class="ph-lookup-form">
                    <label for="maDinhDanhHome">Mã lô / mã định danh</label>
                    <div class="ph-input-wrap">
                        <i class="fa-solid fa-barcode"></i>
                        <input id="maDinhDanhHome" type="text" name="maDinhDanh" placeholder="Ví dụ: LOT-2026-001" autocomplete="off">
                    </div>
                    <button type="submit" class="ph-btn ph-btn-submit">Kiểm tra ngay <i class="fa-solid fa-arrow-right"></i></button>
                </form>
                <div class="ph-lookup-foot"><i class="fa-solid fa-lock"></i> Kết quả đối chiếu hiển thị theo dữ liệu hệ thống và trạng thái Blockchain đọc được.</div>
            </div>
        </div>
        <div class="ph-hero-bottom">
            <div class="ph-container ph-hero-bottom-inner">
                <span><i class="fa-solid fa-link"></i> Theo dõi chuỗi cung ứng</span>
                <span><i class="fa-solid fa-cubes-stacked"></i> Dữ liệu ghi nhận trên Blockchain</span>
                <span><i class="fa-solid fa-bell"></i> Cảnh báo lô thuốc thu hồi</span>
            </div>
        </div>
    </section>

    <section class="ph-container ph-stat-section" aria-label="Thống kê hệ thống">
        <div class="ph-section-heading">
            <div><span class="ph-kicker">PHARМACHAIN TRONG SỐ LIỆU</span><h2>Mạng lưới dược phẩm đang được quản lý</h2></div>
            <p>Số liệu tổng hợp từ dữ liệu hiện có của hệ thống.</p>
        </div>
        <div class="ph-stats-grid">
            <article class="ph-stat-card"><span class="ph-stat-icon green"><i class="fa-solid fa-boxes-stacked"></i></span><strong>{{ number_format($tongLoDangKy) }}</strong><span>Lô thuốc đã đăng ký</span></article>
            <article class="ph-stat-card"><span class="ph-stat-icon blue"><i class="fa-solid fa-capsules"></i></span><strong>{{ number_format($tongSanPhamDuyet) }}</strong><span>Sản phẩm đã được duyệt</span></article>
            <article class="ph-stat-card"><span class="ph-stat-icon cyan"><i class="fa-solid fa-building-circle-check"></i></span><strong>{{ number_format($tongToChucDuyet) }}</strong><span>Tổ chức đã được duyệt</span></article>
            <article class="ph-stat-card"><span class="ph-stat-icon violet"><i class="fa-solid fa-cart-shopping"></i></span><strong>{{ number_format($tongDonViDaBan) }}</strong><span>Đơn vị đã bán lẻ</span></article>
            <article class="ph-stat-card ph-stat-alert"><span class="ph-stat-icon red"><i class="fa-solid fa-triangle-exclamation"></i></span><strong>{{ number_format($tongLoThuHoi) }}</strong><span>Lô thuốc có thông tin thu hồi</span></article>
        </div>
    </section>

    <section class="ph-process-section">
        <div class="ph-container">
            <div class="ph-section-heading ph-centered">
                <span class="ph-kicker">HÀNH TRÌNH CỦA MỘT LÔ THUỐC</span>
                <h2>Mỗi mắt xích, một dấu vết rõ ràng</h2>
                <p>Thông tin được cập nhật xuyên suốt quá trình lưu thông. Người tiêu dùng có thể tra cứu công khai ở bước cuối.</p>
            </div>
            <div class="ph-process-grid">
                <article class="ph-process-card">
                    <span class="ph-step">01</span><div class="ph-process-icon green"><i class="fa-solid fa-industry"></i></div>
                    <h3>Nhà sản xuất</h3><p>Đăng ký sản phẩm, tạo lô thuốc và cung cấp mã QR định danh cho từng lô.</p>
                </article>
                <article class="ph-process-card">
                    <span class="ph-step">02</span><div class="ph-process-icon blue"><i class="fa-solid fa-truck-fast"></i></div>
                    <h3>Nhà phân phối</h3><p>Tiếp nhận lô hàng, quản lý tồn kho và chuyển giao số lượng đến nhà thuốc.</p>
                </article>
                <article class="ph-process-card">
                    <span class="ph-step">03</span><div class="ph-process-icon cyan"><i class="fa-solid fa-house-medical"></i></div>
                    <h3>Nhà thuốc</h3><p>Quét mã QR để kiểm tra thông tin lô trước khi tiếp nhận và bán lẻ thuốc.</p>
                </article>
                <article class="ph-process-card">
                    <span class="ph-step">04</span><div class="ph-process-icon violet"><i class="fa-solid fa-user-shield"></i></div>
                    <h3>Người tiêu dùng</h3><p>Chủ động tra cứu nguồn gốc, thông tin sản phẩm và cảnh báo thu hồi.</p>
                </article>
            </div>
        </div>
    </section>

    <section id="canhBaoThuoc" class="ph-container ph-data-section">
        <div class="ph-data-card ph-warning-card">
            <div class="ph-data-heading">
                <div class="ph-heading-icon warning"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div class="ph-heading-copy"><span class="ph-kicker">CẬP NHẬT AN TOÀN</span><h2>Thông tin lô thuốc thu hồi</h2><p>Danh sách các trường hợp thu hồi được ghi nhận trong hệ thống. Mở mã giao dịch để xem bằng chứng trên Sepolia khi có.</p></div>
                <a href="{{ url('/bao-cao-nghi-van') }}" class="ph-btn ph-btn-outline-warning"><i class="fa-solid fa-flag"></i> Báo cáo nghi vấn</a>
            </div>
            <div class="ph-table-wrap">
                <table class="ph-table">
                    <thead><tr><th>Mã lô</th><th>Sản phẩm</th><th>Nhà sản xuất</th><th>Lý do thu hồi</th><th>Ngày thu hồi</th><th>Giao dịch Blockchain</th><th></th></tr></thead>
                    <tbody id="thanBangThuHoiPublic">
                        @forelse($danhSachThuHoi as $th)
                        <tr class="dong-thu-hoi-item">
                            <td class="ph-code">{{ $th['maLo'] }}</td>
                            <td class="ph-strong">{{ $th['tenThuoc'] }}</td>
                            <td>{{ $th['nhaSanXuat'] }}</td>
                            <td class="ph-reason">{{ $th['lyDo'] }}</td>
                            <td class="ph-nowrap">{{ \Carbon\Carbon::parse($th['ngayThuHoi'])->format('d/m/Y') }}</td>
                            <td class="ph-nowrap">
                                @if($th['txHash'])
                                <a href="https://sepolia.etherscan.io/tx/{{ $th['txHash'] }}" target="_blank" rel="noopener noreferrer" class="ph-chain-link">{{ Str::limit($th['txHash'], 12) }} <i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                                @else
                                <span class="ph-muted">Chưa có mã giao dịch xác nhận</span>
                                @endif
                            </td>
                            <td><a class="ph-row-link" href="{{ url('/tra-cuu-nguon-goc?maDinhDanh=' . $th['maLo']) }}">Tra cứu <i class="fa-solid fa-arrow-right"></i></a></td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="ph-empty">Chưa ghi nhận lô thuốc bị thu hồi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div id="thanhPhanTrangThuHoi" class="ph-pagination"><span id="thongTinTrangThuHoi">Hiển thị 0 / 0 lô thu hồi</span><div id="nutPhanTrangThuHoi"></div></div>
        </div>
    </section>

    <section class="ph-container ph-data-section">
        <div class="ph-data-card">
            <div class="ph-data-heading">
                <div class="ph-heading-icon product"><i class="fa-solid fa-capsules"></i></div>
                <div class="ph-heading-copy"><span class="ph-kicker">DANH MỤC SẢN PHẨM</span><h2>Sản phẩm dược phẩm đã được duyệt</h2><p>Tra cứu thông tin đăng ký và đơn vị sản xuất. Muốn xác thực một lô cụ thể, hãy dùng chức năng tra cứu nguồn gốc phía trên.</p></div>
                <div class="ph-search-box"><i class="fa-solid fa-magnifying-glass"></i><input type="search" id="timKiemThuocPublic" placeholder="Tìm tên thuốc, số đăng ký..."></div>
            </div>
            <div class="ph-table-wrap">
                <table class="ph-table">
                    <thead><tr><th>Tên thuốc</th><th>Số đăng ký</th><th>Thành phần</th><th>Hàm lượng</th><th>Dạng bào chế</th><th>Nhà sản xuất</th></tr></thead>
                    <tbody id="thanBangThuocPublic">
                        @forelse($danhSachSanPham as $sp)
                        <tr class="dong-thuoc-item">
                            <td class="ph-strong ten-thuoc">{{ $sp['tenSanPham'] }}</td><td class="ph-code so-dk">{{ $sp['soDangKy'] }}</td><td class="thanh-phan">{{ $sp['thanhPhan'] }}</td><td>{{ $sp['hamLuong'] }}</td><td>{{ $sp['dangBaoChe'] }}</td><td>{{ $sp['nhaSanXuat'] }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="ph-empty">Chưa có sản phẩm nào được duyệt.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div id="thanhPhanTrangThuoc" class="ph-pagination"><span id="thongTinTrangThuoc">Hiển thị 0 / 0 sản phẩm</span><div id="nutPhanTrangThuoc"></div></div>
        </div>
    </section>

    <section class="ph-container ph-data-section ph-last-section">
        <div class="ph-data-card">
            <div class="ph-data-heading">
                <div class="ph-heading-icon organization"><i class="fa-solid fa-building-circle-check"></i></div>
                <div class="ph-heading-copy"><span class="ph-kicker">MẠNG LƯỚI ĐỐI TÁC</span><h2>Cơ sở dược phẩm đã được duyệt</h2><p>Danh sách các tổ chức tham gia chuỗi cung ứng đã được hệ thống phê duyệt.</p></div>
                <div class="ph-filter-tools">
                    <select id="locLoaiCoSoPublic"><option value="ALL">Tất cả loại hình</option><option value="NHA_SAN_XUAT">Nhà sản xuất</option><option value="NHA_PHAN_PHOI">Nhà phân phối</option><option value="NHA_THUOC">Nhà thuốc</option></select>
                    <div class="ph-search-box"><i class="fa-solid fa-magnifying-glass"></i><input type="search" id="timKiemCoSoPublic" placeholder="Tìm tên cơ sở, địa chỉ..."></div>
                </div>
            </div>
            <div class="ph-table-wrap">
                <table class="ph-table">
                    <thead><tr><th>Tên cơ sở</th><th>Loại hình</th><th>Địa chỉ</th><th>Trạng thái</th></tr></thead>
                    <tbody id="thanBangCoSoPublic">
                        @forelse($danhSachToChuc as $tc)
                        <tr class="dong-co-so-item" data-loai="{{ $tc->loaiToChuc }}">
                            <td class="ph-strong ten-co-so">{{ $tc->tenToChuc }}</td>
                            <td>
                                @if($tc->loaiToChuc === 'NHA_SAN_XUAT')<span class="ph-type-tag green">Nhà sản xuất</span>
                                @elseif($tc->loaiToChuc === 'NHA_PHAN_PHOI')<span class="ph-type-tag blue">Nhà phân phối</span>
                                @elseif($tc->loaiToChuc === 'NHA_THUOC')<span class="ph-type-tag violet">Nhà thuốc</span>
                                @else<span class="ph-type-tag">Cơ quan quản lý</span>@endif
                            </td>
                            <td class="dia-chi-co-so">{{ $tc->diaChi ?? 'Chưa cập nhật' }}</td>
                            <td><span class="ph-status-tag"><i class="fa-solid fa-circle-check"></i> Đã duyệt</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="ph-empty">Chưa có cơ sở dược phẩm nào được duyệt.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div id="thanhPhanTrangCoSo" class="ph-pagination"><span id="thongTinTrangCoSo">Hiển thị 0 / 0 cơ sở</span><div id="nutPhanTrangCoSo"></div></div>
        </div>
    </section>
</main>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/nguoiTieuDung/trangChu.js') }}"></script>
@endpush
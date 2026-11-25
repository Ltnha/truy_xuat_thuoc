@extends('layoutNguoiTieuDung.main')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/nguoiTieuDung/trangChu.css') }}">
@endpush

@section('content')
<main class="phx-home">
    <section class="phx-hero">
        <div class="phx-hero-grid">
            <div class="phx-hero-copy">
                <span class="phx-overline"><i class="fa-solid fa-shield-halved"></i> PHARMACHAIN · TRUY XUẤT DƯỢC PHẨM</span>
                <h1>Mỗi viên thuốc<br>đều có <em>nguồn gốc.</em></h1>
                <p>Kiểm tra hành trình lưu thông của thuốc từ nhà sản xuất đến nhà thuốc. Tra cứu thông tin lô, đơn vị tham gia và trạng thái xác thực trên Blockchain.</p>
                <div class="phx-actions">
                    <a class="phx-button phx-button-light" href="{{ url('/tra-cuu-nguon-goc') }}"><i class="fa-solid fa-magnifying-glass"></i> Tra cứu thuốc</a>
                    <a class="phx-button phx-button-outline" href="{{ url('/tra-cuu-nguon-goc?cheDo=quet') }}"><i class="fa-solid fa-qrcode"></i> Kiểm tra bằng QR</a>
                </div>
                <div class="phx-assurance"><i class="fa-solid fa-circle-check"></i><span>Tra cứu công khai · Không cần tài khoản</span></div>
            </div>

            <div class="phx-search-panel">
                <div class="phx-search-top">
                    <span class="phx-search-symbol"><i class="fa-solid fa-prescription-bottle-medical"></i></span>
                    <span class="phx-search-label">CỔNG TRA CỨU NHANH</span>
                </div>
                <h2>Thuốc bạn đang sử dụng có rõ nguồn gốc?</h2>
                <p>Nhập mã lô hoặc mã định danh được in trên bao bì thuốc.</p>
                <form action="{{ url('/tra-cuu-nguon-goc') }}" method="GET" class="phx-search-form">
                    <label for="phxBatchCode">Mã lô / mã định danh</label>
                    <div class="phx-input">
                        <i class="fa-solid fa-barcode"></i>
                        <input id="phxBatchCode" name="maDinhDanh" type="text" placeholder="Nhập mã được in trên bao bì" autocomplete="off" required>
                    </div>
                    <button type="submit">Bắt đầu tra cứu <i class="fa-solid fa-arrow-right"></i></button>
                </form>
                <a class="phx-qr-link" href="{{ url('/tra-cuu-nguon-goc?cheDo=quet') }}"><i class="fa-solid fa-camera"></i> Hoặc tải ảnh mã QR để kiểm tra</a>
                <div class="phx-search-note"><i class="fa-solid fa-circle-info"></i> Kết quả sẽ phân biệt dữ liệu hệ thống và dữ liệu xác thực đọc được từ Blockchain.</div>
            </div>
        </div>
        <div class="phx-hero-foot">
            <div><i class="fa-solid fa-link"></i> Theo dõi chuỗi cung ứng</div>
            <div><i class="fa-solid fa-cubes-stacked"></i> Đối chiếu dữ liệu Blockchain</div>
            <div><i class="fa-solid fa-bell"></i> Nhận biết thông tin thu hồi</div>
        </div>
    </section>

    <section class="phx-section phx-intro">
        <div class="phx-section-heading">
            <span class="phx-eyebrow">MINH BẠCH TRONG TỪNG CHẶNG</span>
            <h2>Tra cứu những thông tin quan trọng</h2>
            <p>Một điểm tra cứu, các thông tin cần thiết để người dùng chủ động kiểm tra thuốc.</p>
        </div>
        <div class="phx-benefit-grid">
            <article class="phx-benefit">
                <span class="phx-benefit-icon mint"><i class="fa-solid fa-capsules"></i></span>
                <h3>Thông tin sản phẩm</h3>
                <p>Tên thuốc, số đăng ký, thành phần, hàm lượng và đơn vị sản xuất.</p>
            </article>
            <article class="phx-benefit">
                <span class="phx-benefit-icon blue"><i class="fa-solid fa-route"></i></span>
                <h3>Hành trình lô thuốc</h3>
                <p>Theo dõi các mốc chuyển giao và đơn vị đang quản lý lô thuốc.</p>
            </article>
            <article class="phx-benefit">
                <span class="phx-benefit-icon violet"><i class="fa-solid fa-link"></i></span>
                <h3>Đối chiếu Blockchain</h3>
                <p>Kiểm tra trạng thái ghi nhận on-chain, mã giao dịch và tính toàn vẹn dữ liệu.</p>
            </article>
            <article class="phx-benefit">
                <span class="phx-benefit-icon amber"><i class="fa-solid fa-triangle-exclamation"></i></span>
                <h3>Cảnh báo thu hồi</h3>
                <p>Chú ý các lô thuốc có thông tin thu hồi được công bố trong hệ thống.</p>
            </article>
        </div>
    </section>

    <section class="phx-flow-section">
        <div class="phx-section phx-flow-inner">
            <div class="phx-section-heading">
                <span class="phx-eyebrow">TỪ NHÀ SẢN XUẤT ĐẾN NGƯỜI DÙNG</span>
                <h2>Hành trình của một lô thuốc</h2>
                <p>Mỗi bước tiếp nhận và chuyển giao đều là một phần của lịch sử lưu thông.</p>
            </div>
            <div class="phx-flow">
                <article class="phx-flow-step">
                    <span class="phx-flow-number">01</span>
                    <span class="phx-flow-icon"><i class="fa-solid fa-industry"></i></span>
                    <h3>Nhà sản xuất</h3>
                    <p>Đăng ký sản phẩm, tạo lô và mã QR định danh.</p>
                </article>
                <span class="phx-flow-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                <article class="phx-flow-step">
                    <span class="phx-flow-number">02</span>
                    <span class="phx-flow-icon"><i class="fa-solid fa-truck-fast"></i></span>
                    <h3>Nhà phân phối</h3>
                    <p>Tiếp nhận, quản lý tồn kho và chuyển giao.</p>
                </article>
                <span class="phx-flow-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                <article class="phx-flow-step">
                    <span class="phx-flow-number">03</span>
                    <span class="phx-flow-icon"><i class="fa-solid fa-house-medical"></i></span>
                    <h3>Nhà thuốc</h3>
                    <p>Kiểm tra QR, tiếp nhận và bán lẻ thuốc.</p>
                </article>
                <span class="phx-flow-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                <article class="phx-flow-step">
                    <span class="phx-flow-number">04</span>
                    <span class="phx-flow-icon"><i class="fa-solid fa-user-check"></i></span>
                    <h3>Người tiêu dùng</h3>
                    <p>Chủ động kiểm tra thông tin trước khi sử dụng.</p>
                </article>
            </div>
        </div>
    </section>

    @if($danhSachThuHoi->isNotEmpty())
    <section class="phx-section phx-content-section">
        <div class="phx-section-title-row">
            <div>
                <span class="phx-eyebrow phx-eyebrow-danger">CẬP NHẬT AN TOÀN</span>
                <h2>Lô thuốc có thông tin thu hồi</h2>
                <p>Vui lòng kiểm tra kỹ mã lô nếu sản phẩm của bạn xuất hiện trong danh sách.</p>
            </div>
            <a class="phx-text-link" href="{{ url('/bao-cao-nghi-van') }}">Báo cáo nghi vấn <i class="fa-solid fa-arrow-up-right-from-square"></i></a>
        </div>
        <div class="phx-recall-list">
            @foreach($danhSachThuHoi->take(4) as $th)
            <article class="phx-recall-item">
                <span class="phx-recall-icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
                <div class="phx-recall-main">
                    <div class="phx-recall-meta"><span class="phx-code">{{ $th['maLo'] }}</span><span>{{ \Carbon\Carbon::parse($th['ngayThuHoi'])->format('d/m/Y') }}</span></div>
                    <h3>{{ $th['tenThuoc'] }}</h3>
                    <p>{{ $th['lyDo'] ?: 'Chưa có mô tả lý do thu hồi.' }}</p>
                    <small>Nhà sản xuất: {{ $th['nhaSanXuat'] }}</small>
                </div>
                <div class="phx-recall-actions">
                    @if($th['txHash'])
                    <a href="https://sepolia.etherscan.io/tx/{{ $th['txHash'] }}" target="_blank" rel="noopener noreferrer">Giao dịch <i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                    @else
                    <span>Chưa có TX xác nhận</span>
                    @endif
                    <a href="{{ url('/tra-cuu-nguon-goc?maDinhDanh=' . urlencode($th['maLo'])) }}">Tra cứu lô <i class="fa-solid fa-arrow-right"></i></a>
                </div>
            </article>
            @endforeach
        </div>
        @if($danhSachThuHoi->count() > 4)
        <p class="phx-list-note">Đang hiển thị 4 trên {{ $danhSachThuHoi->count() }} lô có thông tin thu hồi gần đây.</p>
        @endif
    </section>
    @endif

    <section class="phx-bottom-cta">
        <div class="phx-bottom-cta-inner">
            <div>
                <span class="phx-eyebrow">CHỦ ĐỘNG BẢO VỆ BẠN VÀ GIA ĐÌNH</span>
                <h2>Đừng chỉ nhìn bao bì. Hãy kiểm tra nguồn gốc.</h2>
                <p>Dùng mã lô hoặc QR trên sản phẩm để bắt đầu tra cứu.</p>
            </div>
            <a class="phx-button phx-button-light" href="{{ url('/tra-cuu-nguon-goc') }}">Tra cứu ngay <i class="fa-solid fa-arrow-right"></i></a>
        </div>
    </section>
</main>
@endsection

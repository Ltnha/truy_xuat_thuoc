@extends('layoutNguoiTieuDung.main')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/nguoiTieuDung/trangChu.css') }}">
@endpush

@section('content')
<main class="phx-home">
    <section class="phx-section phx-content-section" style="padding-top: 48px;">
        <div class="phx-section-heading">
            <span class="phx-eyebrow">MẠNG LƯỚI PHARMACHAIN</span>
            <h2>Đơn vị &amp; sản phẩm dược phẩm</h2>
            <p>Danh sách tổ chức đã được phê duyệt trong chuỗi cung ứng và các sản phẩm dược phẩm đang lưu hành trên hệ thống.</p>
        </div>
    </section>

    @if($danhSachToChuc->isNotEmpty())
    <section class="phx-section phx-partner-section" style="padding-top: 12px;">
        <div class="phx-section-title-row">
            <div>
                <span class="phx-eyebrow">MẠNG LƯỚI THAM GIA</span>
                <h2>Các đơn vị trong chuỗi cung ứng</h2>
                <p>Tổ chức có trạng thái phê duyệt trong hệ thống PharmaChain.</p>
            </div>
            <span class="phx-count">{{ number_format($tongToChucDuyet) }} tổ chức</span>
        </div>
        <div class="phx-partner-grid">
            @foreach($danhSachToChuc as $tc)
                <article class="phx-partner-card">
                    <span class="phx-partner-icon">
                        @if($tc->loaiToChuc === 'NHA_SAN_XUAT')
                            <i class="fa-solid fa-industry"></i>
                        @elseif($tc->loaiToChuc === 'NHA_PHAN_PHOI')
                            <i class="fa-solid fa-truck-fast"></i>
                        @else
                            <i class="fa-solid fa-house-medical"></i>
                        @endif
                    </span>
                    <div>
                        <h3>{{ $tc->tenToChuc }}</h3>
                        <p>
                            @if($tc->loaiToChuc === 'NHA_SAN_XUAT')
                                Nhà sản xuất
                            @elseif($tc->loaiToChuc === 'NHA_PHAN_PHOI')
                                Nhà phân phối
                            @elseif($tc->loaiToChuc === 'NHA_THUOC')
                                Nhà thuốc
                            @else
                                Đơn vị tham gia hệ thống
                            @endif
                        </p>
                        <small><i class="fa-solid fa-location-dot"></i> {{ $tc->diaChi ?: 'Chưa cập nhật địa chỉ' }}</small>
                    </div>
                    <span class="phx-approved" title="Đã được duyệt"><i class="fa-solid fa-check"></i></span>
                </article>
                @endforeach
        </div>
    </section>
    @else
    <section class="phx-section" style="padding: 24px 0;">
        <p class="phx-list-note">Chưa có tổ chức nào được phê duyệt.</p>
    </section>
    @endif

    @if($danhSachSanPham->isNotEmpty())
    <section class="phx-section phx-content-section">
        <div class="phx-section-title-row">
            <div>
                <span class="phx-eyebrow">DANH MỤC ĐƯỢC PHÊ DUYỆT</span>
                <h2>Sản phẩm dược phẩm</h2>
                <p>Các sản phẩm đã được cơ quan quản lý phê duyệt trong hệ thống.</p>
            </div>
            <span class="phx-count">{{ number_format($tongSanPhamDuyet) }} sản phẩm</span>
        </div>
        <div class="phx-product-grid">
            @foreach($danhSachSanPham as $sp)
            <article class="phx-product-card">
                <span class="phx-product-icon"><i class="fa-solid fa-prescription-bottle-medical"></i></span>
                <span class="phx-product-status"><i class="fa-solid fa-circle-check"></i> Đã duyệt</span>
                <h3>{{ $sp['tenSanPham'] }}</h3>
                <dl>
                    <div><dt>Số đăng ký</dt><dd>{{ $sp['soDangKy'] ?: 'Chưa cập nhật' }}</dd></div>
                    <div><dt>Thành phần</dt><dd>{{ $sp['thanhPhan'] ?: 'Chưa cập nhật' }}</dd></div>
                    <div><dt>Hàm lượng</dt><dd>{{ $sp['hamLuong'] ?: 'Chưa cập nhật' }}</dd></div>
                    <div><dt>Nhà sản xuất</dt><dd>{{ $sp['nhaSanXuat'] }}</dd></div>
                </dl>
            </article>
            @endforeach
        </div>
    </section>
    @else
    <section class="phx-section" style="padding: 24px 0 48px;">
        <p class="phx-list-note">Chưa có sản phẩm dược phẩm nào được phê duyệt.</p>
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

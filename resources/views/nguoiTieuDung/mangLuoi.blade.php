@extends('layoutNguoiTieuDung.main')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/nguoiTieuDung/trangChu.css') }}">
<style>
    .phx-pagination {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-top: 20px;
        padding-top: 16px;
        border-top: 1px solid #e3ece7;
    }
    .phx-pagination-info {
        font-size: 12px;
        color: #71827a;
    }
    .phx-pagination-links {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px;
    }
    .phx-pagination-links a,
    .phx-pagination-links span {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 34px;
        height: 34px;
        padding: 0 10px;
        border-radius: 8px;
        border: 1px solid #e3ece7;
        background: #fff;
        color: #17372f;
        font-size: 12px;
        font-weight: 700;
        text-decoration: none;
        transition: background .15s, border-color .15s, color .15s;
    }
    .phx-pagination-links a:hover {
        background: #edf7f1;
        border-color: #b9e3cf;
        color: #08765b;
    }
    .phx-pagination-links span.phx-page-current {
        background: #08765b;
        border-color: #08765b;
        color: #fff;
    }
    .phx-pagination-links span.phx-page-disabled {
        opacity: .45;
        cursor: not-allowed;
    }
</style>
@endpush

@section('content')
<main class="phx-home">
    <section class="phx-section phx-content-section" style="padding-top: 48px;">
        <div class="phx-section-heading">
            <span class="phx-eyebrow">MẠNG LƯỚI PHARMACHAIN</span>
            <h2>Đơn vị & sản phẩm dược phẩm</h2>
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

        @if($danhSachToChuc->hasPages())
        <div class="phx-pagination">
            <div class="phx-pagination-info">
                Hiển thị {{ $danhSachToChuc->firstItem() }}–{{ $danhSachToChuc->lastItem() }}
                / {{ number_format($danhSachToChuc->total()) }} tổ chức
            </div>
            <div class="phx-pagination-links">
                @if($danhSachToChuc->onFirstPage())
                    <span class="phx-page-disabled"><i class="fa-solid fa-chevron-left"></i></span>
                @else
                    <a href="{{ $danhSachToChuc->previousPageUrl() }}" rel="prev"><i class="fa-solid fa-chevron-left"></i></a>
                @endif

                @foreach($danhSachToChuc->getUrlRange(max(1, $danhSachToChuc->currentPage() - 2), min($danhSachToChuc->lastPage(), $danhSachToChuc->currentPage() + 2)) as $page => $url)
                    @if($page == $danhSachToChuc->currentPage())
                        <span class="phx-page-current">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach

                @if($danhSachToChuc->hasMorePages())
                    <a href="{{ $danhSachToChuc->nextPageUrl() }}" rel="next"><i class="fa-solid fa-chevron-right"></i></a>
                @else
                    <span class="phx-page-disabled"><i class="fa-solid fa-chevron-right"></i></span>
                @endif
            </div>
        </div>
        @endif
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
                <h3>{{ $sp->tenSanPham }}</h3>
                <dl>
                    <div><dt>Số đăng ký</dt><dd>{{ $sp->soDangKy ?: 'Chưa cập nhật' }}</dd></div>
                    <div><dt>Thành phần</dt><dd>{{ $sp->thanhPhan ?: 'Chưa cập nhật' }}</dd></div>
                    <div><dt>Hàm lượng</dt><dd>{{ $sp->hamLuong ?: 'Chưa cập nhật' }}</dd></div>
                    <div><dt>Nhà sản xuất</dt><dd>{{ $sp->nhaSanXuat?->tenToChuc ?? 'Chưa xác định' }}</dd></div>
                </dl>
            </article>
            @endforeach
        </div>

        @if($danhSachSanPham->hasPages())
        <div class="phx-pagination">
            <div class="phx-pagination-info">
                Hiển thị {{ $danhSachSanPham->firstItem() }}–{{ $danhSachSanPham->lastItem() }}
                / {{ number_format($danhSachSanPham->total()) }} sản phẩm
            </div>
            <div class="phx-pagination-links">
                @if($danhSachSanPham->onFirstPage())
                    <span class="phx-page-disabled"><i class="fa-solid fa-chevron-left"></i></span>
                @else
                    <a href="{{ $danhSachSanPham->previousPageUrl() }}" rel="prev"><i class="fa-solid fa-chevron-left"></i></a>
                @endif

                @foreach($danhSachSanPham->getUrlRange(max(1, $danhSachSanPham->currentPage() - 2), min($danhSachSanPham->lastPage(), $danhSachSanPham->currentPage() + 2)) as $page => $url)
                    @if($page == $danhSachSanPham->currentPage())
                        <span class="phx-page-current">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach

                @if($danhSachSanPham->hasMorePages())
                    <a href="{{ $danhSachSanPham->nextPageUrl() }}" rel="next"><i class="fa-solid fa-chevron-right"></i></a>
                @else
                    <span class="phx-page-disabled"><i class="fa-solid fa-chevron-right"></i></span>
                @endif
            </div>
        </div>
        @endif
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

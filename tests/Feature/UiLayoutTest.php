<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UiLayoutTest extends TestCase
{
    public function test_public_pages_render_with_the_new_layout_and_assets(): void
    {
        $this->createPublicLookupSchema();

        $this->get('/trangChu')
            ->assertOk()
            ->assertSee('assets/css/cong/trangChu.css');

        $this->get('/truyXuat/xem/LOT-TEST-001')
            ->assertOk()
            ->assertSee('assets/css/cong/traCuuNguonGoc.css');

        $this->get('/truyXuat/lichSu')
            ->assertOk()
            ->assertSee('assets/css/cong/lichSuTraCuu.css');

        $this->get('/baoCao/gui')
            ->assertOk()
            ->assertSee('assets/css/baoCao/baoCaoNghiVan.css');
    }

    private function createPublicLookupSchema(): void
    {
        Schema::create('loThuoc', function (Blueprint $table): void {
            $table->id();
            $table->string('trangThai');
            $table->unsignedBigInteger('sanPhamId')->nullable();
            $table->unsignedBigInteger('toChucId')->nullable();
            $table->string('maLoNghiepVu')->nullable();
            $table->string('maLo')->nullable();
        });
        Schema::create('donViSanPham', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('loThuocId')->nullable();
            $table->string('maDonVi')->nullable();
            $table->string('serialNumber')->nullable();
            $table->string('trangThai')->nullable();
            $table->dateTime('ngayBan')->nullable();
            $table->string('txHash')->nullable();
        });
        Schema::create('qrCode', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('donViSanPhamId')->nullable();
            $table->unsignedBigInteger('loThuocId')->nullable();
            $table->string('giaTriQR')->nullable();
            $table->string('loaiQR')->nullable();
        });
        Schema::create('sanPham', function (Blueprint $table): void {
            $table->id();
            $table->string('tenSanPham')->nullable();
            $table->string('soDangKy')->nullable();
        });
        Schema::create('toChuc', function (Blueprint $table): void {
            $table->id();
            $table->string('tenToChuc')->nullable();
        });
        Schema::create('thuHoiLoHang', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('loThuocId')->nullable();
            $table->text('lyDo')->nullable();
            $table->string('txHash')->nullable();
        });
        Schema::create('blockchainTransaction', function (Blueprint $table): void {
            $table->id();
        });
    }

    public function test_authentication_screens_render_with_the_new_guest_layout(): void
    {
        $this->get('/dangNhap')
            ->assertOk()
            ->assertSee('Đăng Nhập Hệ Thống')
            ->assertSee('assets/css/xacThuc/dangNhap.css');

        $this->get('/dangKyToChuc')
            ->assertOk()
            ->assertSee('Đăng Ký Tài Khoản Tổ Chức')
            ->assertSee('assets/css/xacThuc/dangKyToChuc.css');

        $this->get('/dangKyToChuc/trangThai')
            ->assertOk()
            ->assertSee('Kiểm tra trạng thái hồ sơ');
    }
}

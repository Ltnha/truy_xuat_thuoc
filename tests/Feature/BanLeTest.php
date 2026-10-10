<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\OrganizationApprovalStatus;
use App\Enums\OrganizationType;
use App\Enums\Role;
use App\Models\DonViSanPham;
use App\Models\LoThuoc;
use App\Models\TaiKhoan;
use App\Models\ToChuc;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BanLeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('taiKhoan', function (Blueprint $table): void {
            $table->id();
            $table->string('tenDangNhap')->unique();
            $table->string('matKhau');
            $table->string('vaiTro');
            $table->string('trangThai');
            $table->unsignedBigInteger('toChucId')->nullable();
        });
        Schema::create('toChuc', function (Blueprint $table): void {
            $table->id();
            $table->string('tenToChuc');
            $table->string('loaiToChuc');
            $table->string('trangThaiDuyet');
        });
        Schema::create('loThuoc', function (Blueprint $table): void {
            $table->id();
            $table->string('maLo')->unique();
            $table->string('maLoNghiepVu')->unique();
            $table->unsignedBigInteger('sanPhamId');
            $table->unsignedBigInteger('toChucId');
            $table->unsignedInteger('soLuong');
            $table->date('ngaySanXuat');
            $table->date('hanSuDung');
            $table->string('trangThai');
        });
        Schema::create('donViSanPham', function (Blueprint $table): void {
            $table->id();
            $table->string('maDonVi')->unique();
            $table->string('serialNumber')->unique();
            $table->unsignedBigInteger('loThuocId');
            $table->unsignedBigInteger('banLeId')->nullable();
            $table->string('trangThai');
            $table->dateTime('ngayTao')->nullable();
            $table->dateTime('ngayBan')->nullable();
            $table->string('txHash')->nullable();
        });
        Schema::create('tonKhoLo', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('loThuocId');
            $table->unsignedBigInteger('toChucId');
            $table->unsignedInteger('soLuong');
            $table->dateTime('ngayCapNhat')->nullable();
        });
        Schema::create('banLe', function (Blueprint $table): void {
            $table->id();
            $table->string('maBanLe')->unique();
            $table->unsignedBigInteger('toChucId');
            $table->dateTime('ngayBan');
            $table->unsignedInteger('soLuong');
            $table->string('trangThai');
            $table->string('txHash')->nullable();
        });
        Schema::create('blockchainTransaction', function (Blueprint $table): void {
            $table->id();
            $table->string('txHash')->unique();
            $table->unsignedBigInteger('blockNumber')->nullable();
            $table->string('eventName');
            $table->string('businessId');
            $table->dateTime('thoiGian');
            $table->string('network');
            $table->string('trangThai');
            $table->unsignedBigInteger('toChucId');
            $table->unsignedBigInteger('loThuocId');
            $table->unsignedBigInteger('chuyenGiaoId')->nullable();
            $table->unsignedBigInteger('donViSanPhamId')->nullable();
            $table->unsignedBigInteger('banLeId')->nullable();
        });
        Schema::create('lichSuLoThuoc', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('loThuocId');
            $table->string('suKien');
            $table->text('moTa')->nullable();
            $table->string('txHash')->nullable();
        });
        Schema::create('nhatKyHeThong', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('taiKhoanId');
            $table->string('hanhDong');
            $table->dateTime('thoiGian');
            $table->text('noiDung')->nullable();
            $table->string('diaChiIP')->nullable();
        });
    }

    public function test_pharmacy_sale_decrements_stock_and_records_unit_and_transaction(): void
    {
        $pharmacy = ToChuc::query()->create([
            'tenToChuc' => 'Nhà thuốc Demo',
            'loaiToChuc' => OrganizationType::nhaThuoc,
            'trangThaiDuyet' => OrganizationApprovalStatus::daDuyet,
        ]);
        $account = TaiKhoan::query()->create([
            'tenDangNhap' => 'pharmacy.demo',
            'matKhau' => Hash::make('DemoPassword123'),
            'vaiTro' => Role::nhaThuoc,
            'trangThai' => AccountStatus::active,
            'toChucId' => $pharmacy->id,
        ]);
        $lot = LoThuoc::query()->create([
            'maLo' => 'lot-unit-sale',
            'maLoNghiepVu' => 'LOT-SALE-001',
            'sanPhamId' => 1,
            'toChucId' => $pharmacy->id,
            'soLuong' => 5,
            'ngaySanXuat' => today()->subMonth(),
            'hanSuDung' => today()->addYear(),
            'trangThai' => 'CREATED',
        ]);
        DonViSanPham::query()->create([
            'maDonVi' => 'LOT-SALE-001-000001',
            'serialNumber' => 'SERIALSALE000001',
            'loThuocId' => $lot->id,
            'trangThai' => 'AVAILABLE',
        ]);
        \Illuminate\Support\Facades\DB::table('tonKhoLo')->insert([
            'loThuocId' => $lot->id,
            'toChucId' => $pharmacy->id,
            'soLuong' => 5,
        ]);

        $this->actingAs($account)
            ->post(route('nt.banLe.store'), ['maDinhDanh' => 'LOT-SALE-001-000001'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tonKhoLo', ['loThuocId' => $lot->id, 'toChucId' => $pharmacy->id, 'soLuong' => 4]);
        $this->assertDatabaseHas('donViSanPham', ['maDonVi' => 'LOT-SALE-001-000001', 'trangThai' => 'DISPENSED']);
        $this->assertDatabaseHas('banLe', ['toChucId' => $pharmacy->id, 'soLuong' => 1, 'trangThai' => 'CONFIRMED']);
        $this->assertDatabaseHas('blockchainTransaction', ['eventName' => 'UnitDispensed']);
        $this->assertDatabaseHas('lichSuLoThuoc', ['loThuocId' => $lot->id, 'suKien' => 'BAN_LE']);
    }
}

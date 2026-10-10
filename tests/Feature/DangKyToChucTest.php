<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DangKyToChucTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('toChuc', function (Blueprint $table): void {
            $table->id();
            $table->string('tenToChuc');
            $table->string('loaiToChuc');
            $table->string('diaChi')->nullable();
            $table->string('soDienThoai')->nullable();
            $table->string('maSoThue')->unique();
            $table->string('soGiayPhepDuoc');
            $table->date('ngayCapGiayPhep');
            $table->date('ngayHetHanGiayPhep')->nullable();
            $table->string('coQuanCap');
            $table->string('trangThaiDuyet');
        });
        Schema::create('taiKhoan', function (Blueprint $table): void {
            $table->id();
            $table->string('tenDangNhap')->unique();
            $table->string('matKhau');
            $table->string('email')->unique();
            $table->string('soDienThoai')->nullable();
            $table->string('vaiTro');
            $table->string('trangThai');
            $table->unsignedBigInteger('toChucId')->nullable();
            $table->dateTime('ngayTao')->nullable();
        });
        Schema::create('yeuCauDangKy', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('toChucId');
            $table->date('ngayGui');
            $table->unsignedInteger('versionHoSo');
            $table->string('trangThai');
            $table->unsignedBigInteger('nguoiDuyetId')->nullable();
            $table->date('ngayXuLy')->nullable();
            $table->string('lyDoTuChoi')->nullable();
        });
        Schema::create('taiLieuDangKy', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('yeuCauDangKyId');
            $table->string('tenTaiLieu');
            $table->string('loaiTaiLieu')->nullable();
            $table->string('duongDan');
            $table->date('ngayTaiLen');
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

    public function test_registration_creates_pending_organization_account_and_private_documents(): void
    {
        Storage::fake('local');

        $response = $this->post(route('dangKyToChuc.store'), [
            'loaiToChuc' => 'NHA_SAN_XUAT',
            'tenToChuc' => 'Công ty Dược Demo',
            'maSoThue' => '0101234567',
            'soDienThoai' => '0900000000',
            'diaChi' => 'Hà Nội',
            'soGiayPhepDuoc' => 'GP-001',
            'coQuanCap' => 'Cục Quản lý Dược',
            'ngayCapGiayPhep' => '2024-01-01',
            'ngayHetHanGiayPhep' => '2030-01-01',
            'taiLieu' => [UploadedFile::fake()->createWithContent('giay-phep.pdf', '%PDF-1.4 sample')],
            'tenDangNhap' => 'demo_dangky',
            'email' => 'demo@example.test',
            'matKhau' => 'DemoPassword123',
            'matKhau_confirmation' => 'DemoPassword123',
        ]);

        $response->assertRedirect(route('dangKyToChuc.trangThai'))
            ->assertSessionHas('thanhCong');
        $this->assertDatabaseHas('toChuc', [
            'maSoThue' => '0101234567',
            'trangThaiDuyet' => 'CHO_DUYET',
        ]);
        $this->assertDatabaseHas('taiKhoan', [
            'tenDangNhap' => 'demo_dangky',
            'vaiTro' => 'NHA_SAN_XUAT',
            'trangThai' => 'PENDING',
        ]);
        $this->assertDatabaseHas('yeuCauDangKy', ['trangThai' => 'CHO_DUYET']);
        $document = DB::table('taiLieuDangKy')->first();
        $this->assertNotNull($document);
        Storage::disk('local')->assertExists($document->duongDan);
        $this->assertFalse(Storage::disk('public')->exists($document->duongDan));

        $this->post(route('dangKyToChuc.kiemTraTrangThai'), [
            'tenDangNhap' => 'demo_dangky',
            'matKhau' => 'DemoPassword123',
        ])
            ->assertOk()
            ->assertSeeText('Trạng thái tổ chức: CHO_DUYET')
            ->assertSeeText('Trạng thái tài khoản: PENDING');

        $organizationId = DB::table('toChuc')->value('id');
        $reviewer = new \App\Models\TaiKhoan;
        $reviewer->tenDangNhap = 'co_quan_demo';
        $reviewer->matKhau = Hash::make('ReviewerPassword123');
        $reviewer->email = 'reviewer@example.test';
        $reviewer->soDienThoai = '0900111222';
        $reviewer->vaiTro = 'CO_QUAN_QUAN_LY';
        $reviewer->trangThai = 'ACTIVE';
        $reviewer->save();
        $this->actingAs($reviewer)
            ->patch(route('cq.toChuc.approve', $organizationId), ['trangThaiDuyet' => 'DA_DUYET'])
            ->assertSessionHas('thanhCong', 'Đã cập nhật kết quả xét duyệt tổ chức.');

        $this->assertDatabaseHas('toChuc', ['id' => $organizationId, 'trangThaiDuyet' => 'DA_DUYET']);
        $this->assertDatabaseHas('taiKhoan', ['tenDangNhap' => 'demo_dangky', 'trangThai' => 'ACTIVE']);
    }
}

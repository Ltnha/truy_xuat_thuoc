<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\OrganizationApprovalStatus;
use App\Enums\OrganizationType;
use App\Enums\Role;
use App\Models\BaoCaoNghiVan;
use App\Models\LoThuoc;
use App\Models\TaiKhoan;
use App\Models\ToChuc;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BaoCaoXuLyTest extends TestCase
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
        Schema::create('baoCaoNghiVan', function (Blueprint $table): void {
            $table->id();
            $table->string('maBaoCao')->unique();
            $table->unsignedBigInteger('loThuocId');
            $table->unsignedBigInteger('taiKhoanId')->nullable();
            $table->text('lyDo');
            $table->text('moTa')->nullable();
            $table->date('ngayGui');
            $table->string('trangThai');
        });
        Schema::create('ketQuaXuLyBaoCao', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('baoCaoId')->unique();
            $table->unsignedBigInteger('nguoiXuLyId');
            $table->string('quyetDinh');
            $table->date('ngayXuLy');
            $table->string('lyDoXuLy')->nullable();
            $table->string('ghiChu')->nullable();
        });
        Schema::create('thuHoiLoHang', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('loThuocId');
            $table->unsignedBigInteger('ketQuaXuLyBaoCaoId')->nullable()->unique();
            $table->text('lyDo');
            $table->date('ngayThuHoi');
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
            $table->dateTime('thoiGian');
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

    public function test_regulator_can_recall_lot_from_a_report_and_record_the_result(): void
    {
        $organization = ToChuc::query()->create([
            'tenToChuc' => 'Nhà sản xuất Demo',
            'loaiToChuc' => OrganizationType::nhaSanXuat,
            'trangThaiDuyet' => OrganizationApprovalStatus::daDuyet,
        ]);
        $account = TaiKhoan::query()->create([
            'tenDangNhap' => 'regulator.demo',
            'matKhau' => Hash::make('DemoPassword123'),
            'vaiTro' => Role::coQuanQuanLy,
            'trangThai' => AccountStatus::active,
        ]);
        $lot = LoThuoc::query()->create([
            'maLo' => 'lot-hash-demo',
            'maLoNghiepVu' => 'LOT-RECALL-001',
            'sanPhamId' => 1,
            'toChucId' => $organization->id,
            'soLuong' => 10,
            'ngaySanXuat' => today()->subMonth(),
            'hanSuDung' => today()->addYear(),
            'trangThai' => 'CREATED',
        ]);
        $report = BaoCaoNghiVan::query()->create([
            'maBaoCao' => 'BC-TEST-001',
            'loThuocId' => $lot->id,
            'lyDo' => 'Nghi vấn nguồn gốc',
            'ngayGui' => today(),
            'trangThai' => 'PENDING',
        ]);

        $this->actingAs($account)
            ->patch(route('cq.baoCao.xuLy', $report), [
                'quyetDinh' => 'THU_HOI',
                'lyDoXuLy' => 'Có bằng chứng sai lệch nguồn gốc.',
            ])
            ->assertSessionHas('thanhCong', 'Đã cập nhật kết quả xử lý báo cáo.')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('loThuoc', ['id' => $lot->id, 'trangThai' => 'RECALLED']);
        $this->assertDatabaseHas('baoCaoNghiVan', ['id' => $report->id, 'trangThai' => 'APPROVED']);
        $this->assertDatabaseHas('ketQuaXuLyBaoCao', ['baoCaoId' => $report->id, 'quyetDinh' => 'THU_HOI']);
        $this->assertDatabaseHas('thuHoiLoHang', ['loThuocId' => $lot->id, 'trangThai' => 'CONFIRMED']);
        $this->assertDatabaseHas('blockchainTransaction', ['eventName' => 'BatchRecalled']);
        $this->assertDatabaseHas('lichSuLoThuoc', ['loThuocId' => $lot->id, 'suKien' => 'THU_HOI']);
    }
}

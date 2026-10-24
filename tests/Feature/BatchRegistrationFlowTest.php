<?php

namespace Tests\Feature;

use App\Jobs\ProcessBatchRegistrationJob;
use App\Models\BlockchainIdentity;
use App\Models\BlockchainTransaction;
use App\Models\LoThuoc;
use App\Models\SanPham;
use App\Models\TaiKhoan;
use App\Models\ToChuc;
use App\Services\Blockchain\SignerClient;
use App\Services\ChuoiCungUng\TonKhoService;
use App\Services\LoThuoc\DonViSanPhamService;
use App\Services\LoThuoc\QrService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class BatchRegistrationFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('toChuc', function (Blueprint $table): void {
            $table->id();
            $table->string('tenToChuc');
            $table->string('loaiToChuc');
            $table->string('maSoThue')->nullable();
            $table->string('trangThaiDuyet')->default('CHO_DUYET');
        });

        Schema::create('taiKhoan', function (Blueprint $table): void {
            $table->id();
            $table->string('tenDangNhap')->unique();
            $table->string('matKhau');
            $table->string('vaiTro');
            $table->string('trangThai')->default('ACTIVE');
            $table->unsignedBigInteger('toChucId')->nullable();
        });

        Schema::create('sanPham', function (Blueprint $table): void {
            $table->id();
            $table->string('maSanPham')->unique();
            $table->string('tenSanPham');
            $table->string('soDangKy')->unique();
            $table->unsignedBigInteger('nhaSanXuatId');
            $table->string('trangThaiDuyet')->default('CHO_DUYET');
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
            $table->string('trangThai')->default('CREATED');
        });

        Schema::create('blockchainIdentity', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('toChucId');
            $table->string('signerKeyId')->nullable();
            $table->string('diaChiVi')->unique();
            $table->string('network')->default('Ethereum Sepolia Testnet');
            $table->string('trangThai')->default('KHOI_TAO');
            $table->dateTime('ngayTao')->nullable();
        });

        Schema::create('blockchainTransaction', function (Blueprint $table): void {
            $table->id();
            $table->string('txHash')->unique();
            $table->unsignedBigInteger('blockNumber')->nullable();
            $table->string('eventName');
            $table->unsignedBigInteger('doiTuongToChucId')->nullable();
            $table->string('businessId');
            $table->dateTime('thoiGian')->nullable();
            $table->string('network')->default('Ethereum Sepolia Testnet');
            $table->string('trangThai')->default('PENDING');
            $table->unsignedBigInteger('toChucId');
            $table->unsignedBigInteger('sanPhamId')->nullable();
            $table->unsignedBigInteger('loThuocId')->nullable();
            $table->unsignedBigInteger('chuyenGiaoId')->nullable();
            $table->unsignedBigInteger('donViSanPhamId')->nullable();
            $table->unsignedBigInteger('banLeId')->nullable();
        });

        Schema::create('donViSanPham', function (Blueprint $table): void {
            $table->id();
            $table->string('maDonVi')->unique();
            $table->unsignedBigInteger('loThuocId');
            $table->unsignedBigInteger('banLeId')->nullable();
            $table->string('trangThai')->default('AVAILABLE');
            $table->dateTime('ngayTao')->nullable();
            $table->dateTime('ngayBan')->nullable();
        });

        Schema::create('qrCode', function (Blueprint $table): void {
            $table->id();
            $table->string('giaTriQR')->unique();
            $table->string('loaiQR');
            $table->unsignedBigInteger('loThuocId')->nullable();
            $table->unsignedBigInteger('donViSanPhamId')->nullable();
            $table->dateTime('ngayTao')->nullable();
            $table->string('trangThai')->default('ACTIVE');
        });

        Schema::create('tonKhoLo', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('loThuocId');
            $table->unsignedBigInteger('toChucId');
            $table->unsignedInteger('soLuong');
            $table->dateTime('ngayCapNhat')->nullable();
        });

        Schema::create('nhatKyHeThong', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('taiKhoanId')->nullable();
            $table->unsignedBigInteger('doiTuongId')->nullable();
            $table->string('hanhDong');
            $table->string('ketQua')->default('THANH_CONG');
            $table->dateTime('thoiGian')->nullable();
            $table->text('chiTiet')->nullable();
        });
    }

    public function test_batch_creation_validations_and_job_dispatch(): void
    {
        Queue::fake();

        // 1. Tạo tổ chức NSX và tài khoản
        $toChuc = ToChuc::create([
            'tenToChuc' => 'Công ty Dược Test ' . rand(100, 999),
            'loaiToChuc' => 'NHA_SAN_XUAT',
            'maSoThue' => 'MST_' . rand(100000, 999999),
            'trangThaiDuyet' => 'DA_DUYET',
        ]);

        $taiKhoan = TaiKhoan::create([
            'tenDangNhap' => 'user_nsx_' . rand(100, 999),
            'matKhau' => bcrypt('password123'),
            'vaiTro' => 'NHA_SAN_XUAT',
            'trangThai' => 'ACTIVE',
            'toChucId' => $toChuc->id,
        ]);

        // Tạo sản phẩm mẫu
        $sanPhamDaDuyet = SanPham::create([
            'maSanPham' => (string) \Illuminate\Support\Str::uuid(),
            'tenSanPham' => 'Thuốc Paracetamol Test',
            'soDangKy' => 'SDK_' . rand(1000, 9999),
            'nhaSanXuatId' => $toChuc->id,
            'trangThaiDuyet' => 'DA_DUYET',
        ]);

        // Chưa có ví active -> phải trả về 403
        $response = $this->actingAs($taiKhoan)->postJson('/nha-san-xuat/api/tao-lo-thuoc', [
            'sanPhamId' => $sanPhamDaDuyet->id,
            'maLoNghiepVu' => 'LOT-TEST-001',
            'soLuong' => 100,
            'ngaySanXuat' => date('Y-m-d'),
            'hanSuDung' => date('Y-m-d', strtotime('+1 year')),
        ]);
        $response->assertStatus(403);

        // Tạo ví ACTIVE
        BlockchainIdentity::create([
            'toChucId' => $toChuc->id,
            'diaChiVi' => '0x' . bin2hex(random_bytes(20)),
            'signerKeyId' => 'KEY_TEST_' . $toChuc->id,
            'network' => 'Ethereum Sepolia Testnet',
            'trangThai' => 'ACTIVE',
        ]);

        // Sản phẩm chưa duyệt -> phải trả về 422
        $sanPhamChuaDuyet = SanPham::create([
            'maSanPham' => (string) \Illuminate\Support\Str::uuid(),
            'tenSanPham' => 'Thuốc Test Chưa Duyệt',
            'soDangKy' => 'SDK_' . rand(1000, 9999),
            'nhaSanXuatId' => $toChuc->id,
            'trangThaiDuyet' => 'CHO_DUYET',
        ]);

        $response = $this->actingAs($taiKhoan)->postJson('/nha-san-xuat/api/tao-lo-thuoc', [
            'sanPhamId' => $sanPhamChuaDuyet->id,
            'maLoNghiepVu' => 'LOT-TEST-002',
            'soLuong' => 100,
            'ngaySanXuat' => date('Y-m-d'),
            'hanSuDung' => date('Y-m-d', strtotime('+1 year')),
        ]);
        $response->assertStatus(422);

        // Tạo thành công với sản phẩm đã duyệt

        $maLoNghiepVu = 'LOT-TEST-SUCCESS-' . rand(1000, 9999);
        $response = $this->actingAs($taiKhoan)->postJson('/nha-san-xuat/api/tao-lo-thuoc', [
            'sanPhamId' => $sanPhamDaDuyet->id,
            'maLoNghiepVu' => $maLoNghiepVu,
            'soLuong' => 50,
            'ngaySanXuat' => date('Y-m-d'),
            'hanSuDung' => date('Y-m-d', strtotime('+2 years')),
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        // Kiểm tra lô thuốc và transaction đã được lưu ở trạng thái PENDING
        $loThuoc = LoThuoc::where('maLoNghiepVu', $maLoNghiepVu)->first();
        $this->assertNotNull($loThuoc);
        $this->assertSame('CREATED', $loThuoc->trangThai);

        $bctx = BlockchainTransaction::where('loThuocId', $loThuoc->id)->first();
        $this->assertNotNull($bctx);
        $this->assertSame('PENDING', $bctx->trangThai);

        // Kiểm tra Job đã được dispatch
        Queue::assertPushed(ProcessBatchRegistrationJob::class);
    }

    public function test_job_execution_with_idempotency_and_stock_init(): void
    {
        // 1. Dựng dữ liệu giả lập
        $toChuc = ToChuc::create([
            'tenToChuc' => 'Công ty Dược Job Test ' . rand(100, 999),
            'loaiToChuc' => 'NHA_SAN_XUAT',
            'maSoThue' => 'MST_' . rand(100000, 999999),
            'trangThaiDuyet' => 'DA_DUYET',
        ]);

        $taiKhoan = TaiKhoan::create([
            'tenDangNhap' => 'user_nsx_job_' . rand(100, 999),
            'matKhau' => bcrypt('password123'),
            'vaiTro' => 'NHA_SAN_XUAT',
            'trangThai' => 'ACTIVE',
            'toChucId' => $toChuc->id,
        ]);

        $sanPham = SanPham::create([
            'maSanPham' => (string) \Illuminate\Support\Str::uuid(),
            'tenSanPham' => 'Thuốc Amoxicillin Test',
            'soDangKy' => 'SDK_' . rand(1000, 9999),
            'nhaSanXuatId' => $toChuc->id,
            'trangThaiDuyet' => 'DA_DUYET',
        ]);

        $maLo = (string) \Illuminate\Support\Str::uuid();
        $maLoNghiepVu = 'LOT-JOB-' . rand(1000, 9999);
        $soLuong = 5;

        $loThuoc = LoThuoc::create([
            'maLo' => $maLo,
            'maLoNghiepVu' => $maLoNghiepVu,
            'sanPhamId' => $sanPham->id,
            'toChucId' => $toChuc->id,
            'soLuong' => $soLuong,
            'ngaySanXuat' => date('Y-m-d'),
            'hanSuDung' => date('Y-m-d', strtotime('+1 year')),
            'trangThai' => 'CREATED',
        ]);

        BlockchainTransaction::create([
            'txHash' => 'PENDING_' . str_replace('-', '', $maLo),
            'eventName' => 'BatchEventRecorded',
            'businessId' => $maLo,
            'network' => 'Ethereum Sepolia Testnet',
            'trangThai' => 'PENDING',
            'toChucId' => $toChuc->id,
            'sanPhamId' => $sanPham->id,
            'loThuocId' => $loThuoc->id,
        ]);

        // Mock SignerClient
        $mockSigner = Mockery::mock(SignerClient::class);
        $mockSigner->shouldReceive('isBatchEventRecorded')->once()->andReturn(false);
        $mockSigner->shouldReceive('ensureGas')->once()->andReturnNull();
        $mockSigner->shouldReceive('tx')->once()->andReturn([
            'ok' => true,
            'txHash' => '0x' . bin2hex(random_bytes(32)),
            'blockNumber' => 1234567,
        ]);

        $job = new ProcessBatchRegistrationJob($loThuoc->id, $taiKhoan->id);
        $job->handle(
            $mockSigner,
            app(DonViSanPhamService::class),
            app(QrService::class),
            app(TonKhoService::class)
        );

        // Kiểm tra sau khi Job chạy:
        // 1. Transaction được CONFIRMED với hash thật
        $bctx = BlockchainTransaction::where('loThuocId', $loThuoc->id)->first();
        $this->assertSame('CONFIRMED', $bctx->trangThai);
        $this->assertStringStartsWith('0x', $bctx->txHash);

        // 2. Các đơn vị thuốc đã được sinh
        $donViCount = DB::table('donViSanPham')->where('loThuocId', $loThuoc->id)->count();
        $this->assertSame($soLuong, $donViCount);

        // 3. Tồn kho được khởi tạo
        $tonKho = DB::table('tonKhoLo')->where('loThuocId', $loThuoc->id)->where('toChucId', $toChuc->id)->first();
        $this->assertNotNull($tonKho);
        $this->assertSame($soLuong, $tonKho->soLuong);

        // --- KIỂM TRA TÍNH IDEMPOTENT KHI RETRY ---
        // Giả sử Job chạy lại lần 2
        $mockSignerRetry = Mockery::mock(SignerClient::class);
        // On-chain đã ghi nhận
        $mockSignerRetry->shouldReceive('isBatchEventRecorded')->once()->andReturn(true);
        // Không được gọi tx nữa
        $mockSignerRetry->shouldNotReceive('tx');

        $jobRetry = new ProcessBatchRegistrationJob($loThuoc->id, $taiKhoan->id);
        $jobRetry->handle(
            $mockSignerRetry,
            app(DonViSanPhamService::class),
            app(QrService::class),
            app(TonKhoService::class)
        );

        // Tồn kho và đơn vị sản phẩm KHÔNG bị nhân đôi
        $donViCountSauRetry = DB::table('donViSanPham')->where('loThuocId', $loThuoc->id)->count();
        $this->assertSame($soLuong, $donViCountSauRetry);

        $tonKhoSauRetry = DB::table('tonKhoLo')->where('loThuocId', $loThuoc->id)->where('toChucId', $toChuc->id)->first();
        $this->assertSame($soLuong, $tonKhoSauRetry->soLuong);
    }
}

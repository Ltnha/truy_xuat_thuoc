<?php

namespace Tests\Feature;

use App\Enums\OrganizationType;
use App\Exceptions\NghiepVuException;
use App\Jobs\ProcessBatchSaleEventJob;
use App\Models\BanLe;
use App\Models\LoThuoc;
use App\Models\SanPham;
use App\Models\TaiKhoan;
use App\Models\ToChuc;
use App\Services\ChuoiCungUng\BanLeService;
use App\Services\ChuoiCungUng\TonKhoService;
use App\Services\Blockchain\SignerClient;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class BanLeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('toChuc', function (Blueprint $t): void {
            $t->id(); $t->string('tenToChuc'); $t->string('loaiToChuc'); $t->string('trangThaiDuyet');
        });
        Schema::create('taiKhoan', function (Blueprint $t): void {
            $t->id(); $t->string('tenDangNhap'); $t->string('matKhau'); $t->string('vaiTro'); $t->string('trangThai'); $t->unsignedBigInteger('toChucId')->nullable();
        });
        Schema::create('sanPham', function (Blueprint $t): void {
            $t->id(); $t->string('maSanPham'); $t->string('tenSanPham'); $t->string('soDangKy')->nullable();
            $t->text('thanhPhan')->nullable(); $t->string('hamLuong')->nullable(); $t->string('dangBaoChe')->nullable();
            $t->string('quyCachDongGoi')->nullable(); $t->unsignedBigInteger('nhaSanXuatId'); $t->string('trangThaiDuyet');
        });
        Schema::create('loThuoc', function (Blueprint $t): void {
            $t->id(); $t->string('maLo'); $t->string('maLoNghiepVu'); $t->unsignedBigInteger('sanPhamId');
            $t->unsignedBigInteger('toChucId'); $t->unsignedInteger('soLuong'); $t->date('ngaySanXuat'); $t->date('hanSuDung'); $t->string('trangThai');
        });
        Schema::create('blockchainIdentity', function (Blueprint $t): void {
            $t->id(); $t->unsignedBigInteger('toChucId'); $t->string('signerKeyId')->nullable(); $t->string('diaChiVi'); $t->string('trangThai');
        });
        Schema::create('tonKhoLo', function (Blueprint $t): void {
            $t->id(); $t->unsignedBigInteger('loThuocId')->unique(); $t->unsignedBigInteger('toChucId'); $t->unsignedInteger('soLuong'); $t->dateTime('ngayCapNhat')->nullable();
        });
        Schema::create('thuHoiLoHang', function (Blueprint $t): void { $t->id(); $t->unsignedBigInteger('loThuocId'); });
        Schema::create('chuyenGiao', function (Blueprint $t): void { $t->id(); $t->unsignedBigInteger('loThuocId'); $t->string('trangThai'); });
        Schema::create('banLe', function (Blueprint $t): void {
            $t->id(); $t->string('maHoaDon'); $t->unsignedBigInteger('toChucId'); $t->unsignedBigInteger('taiKhoanId');
            $t->decimal('tongTien', 18, 2); $t->string('trangThai'); $t->dateTime('ngayBan'); $t->string('ghiChu')->nullable();
        });
        Schema::create('chiTietBanLe', function (Blueprint $t): void {
            $t->id(); $t->unsignedBigInteger('banLeId'); $t->unsignedBigInteger('loThuocId');
            $t->unsignedInteger('soLuong'); $t->decimal('donGia', 18, 2); $t->decimal('thanhTien', 18, 2);
        });
        Schema::create('blockchainTransaction', function (Blueprint $t): void {
            $t->id(); $t->string('txHash')->nullable(); $t->unsignedBigInteger('blockNumber')->nullable();
            $t->string('eventName'); $t->unsignedBigInteger('doiTuongToChucId')->nullable(); $t->string('businessId');
            $t->string('payloadHash')->nullable(); $t->dateTime('thoiGian')->nullable(); $t->string('network'); $t->string('trangThai');
            $t->unsignedInteger('soLanThu')->default(0); $t->dateTime('lanThuCuoi')->nullable(); $t->text('loiCuoi')->nullable();
            $t->unsignedBigInteger('toChucId'); $t->unsignedBigInteger('sanPhamId'); $t->unsignedBigInteger('loThuocId');
            $t->unsignedBigInteger('chuyenGiaoId')->nullable(); $t->unsignedBigInteger('banLeId')->nullable(); $t->unsignedBigInteger('chiTietBanLeId')->nullable();
        });
        Schema::create('nhatKyHeThong', function (Blueprint $t): void {
            $t->id(); $t->unsignedBigInteger('taiKhoanId')->nullable(); $t->unsignedBigInteger('doiTuongId')->nullable();
            $t->string('hanhDong'); $t->string('ketQua'); $t->dateTime('thoiGian'); $t->text('chiTiet')->nullable();
        });
    }

    public function test_pharmacy_sale_creates_invoice_decrements_batch_stock_and_queues_sold_event(): void
    {
        Queue::fake();

        $manufacturer = ToChuc::create(['tenToChuc' => 'NSX test', 'loaiToChuc' => 'NHA_SAN_XUAT', 'trangThaiDuyet' => 'DA_DUYET']);
        $pharmacy = ToChuc::create(['tenToChuc' => 'Nhà thuốc test', 'loaiToChuc' => OrganizationType::nhaThuoc, 'trangThaiDuyet' => 'DA_DUYET']);
        $account = TaiKhoan::create(['tenDangNhap' => 'pharmacy.test', 'matKhau' => bcrypt('secret'), 'vaiTro' => 'NHA_THUOC', 'trangThai' => 'ACTIVE', 'toChucId' => $pharmacy->id]);
        $product = SanPham::create([
            'maSanPham' => 'MED-TEST-01', 'tenSanPham' => 'Paracetamol test', 'soDangKy' => 'SDK-TEST',
            'thanhPhan' => 'Paracetamol', 'hamLuong' => '500mg', 'dangBaoChe' => 'Vien nen',
            'quyCachDongGoi' => 'Hộp 10 vỉ', 'nhaSanXuatId' => $manufacturer->id, 'trangThaiDuyet' => 'DA_DUYET',
        ]);
        $lot = LoThuoc::create([
            'maLo' => '11111111-1111-4111-8111-111111111111', 'maLoNghiepVu' => 'LOT-TEST-01',
            'sanPhamId' => $product->id, 'toChucId' => $manufacturer->id, 'soLuong' => 10,
            'ngaySanXuat' => today()->subMonth(), 'hanSuDung' => today()->addYear(), 'trangThai' => 'IN_TRANSIT',
        ]);
        $wallet = '0x1111111111111111111111111111111111111111';
        DB::table('blockchainIdentity')->insert(['toChucId' => $pharmacy->id, 'signerKeyId' => 'KEY_PHARMACY', 'diaChiVi' => $wallet, 'trangThai' => 'ACTIVE']);
        DB::table('tonKhoLo')->insert(['loThuocId' => $lot->id, 'toChucId' => $pharmacy->id, 'soLuong' => 5]);

        $payload = [
            'maSanPham' => 'MED-TEST-01', 'tenSanPham' => 'Paracetamol test', 'soDangKy' => 'SDK-TEST',
            'thanhPhan' => 'Paracetamol', 'hamLuong' => '500mg', 'dangBaoChe' => 'Vien nen',
            'quyCachDongGoi' => 'Hộp 10 vỉ', 'nhaSanXuatId' => (int) $manufacturer->id,
        ];
        $payloadHash = '0x' . hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION));
        $signer = Mockery::mock(SignerClient::class);
        $signer->shouldReceive('getBatchState')->once()->andReturn(['batch' => ['exists' => true, 'recalled' => false, 'currentOwner' => $wallet]]);
        $signer->shouldReceive('isProductApproved')->once()->andReturn(['isApproved' => true, 'payloadHash' => $payloadHash]);
        $this->app->instance(SignerClient::class, $signer);

        $invoice = app(BanLeService::class)->banLe([
            ['loThuocId' => $lot->id, 'soLuong' => 2, 'donGia' => 12000],
        ], $pharmacy, $account);

        $this->assertInstanceOf(BanLe::class, $invoice);
        $this->assertSame('DA_HOAN_TAT', $invoice->trangThai);
        $this->assertDatabaseHas('tonKhoLo', ['loThuocId' => $lot->id, 'toChucId' => $pharmacy->id, 'soLuong' => 3]);
        $this->assertDatabaseHas('chiTietBanLe', ['banLeId' => $invoice->id, 'loThuocId' => $lot->id, 'soLuong' => 2]);
        $this->assertDatabaseHas('blockchainTransaction', ['banLeId' => $invoice->id, 'eventName' => 'SOLD', 'trangThai' => 'PENDING', 'txHash' => null]);
        Queue::assertPushed(ProcessBatchSaleEventJob::class);
    }
}

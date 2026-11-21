<?php

namespace Tests\Feature;

use App\Services\ChuoiCungUng\TonKhoService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TonKhoChuyenGiaoReservationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('chuyenGiao');
        Schema::dropIfExists('tonKhoLo');

        Schema::create('tonKhoLo', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('loThuocId')->unique();
            $table->unsignedBigInteger('toChucId');
            $table->unsignedInteger('soLuong');
            $table->dateTime('ngayCapNhat')->nullable();
        });

        Schema::create('chuyenGiao', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('loThuocId');
            $table->unsignedBigInteger('benGuiId');
            $table->unsignedBigInteger('benNhanId');
            $table->unsignedInteger('soLuong');
            $table->string('trangThai');
        });
    }

    public function test_pending_transfer_reserves_available_quantity_without_removing_lot_from_sender_warehouse(): void
    {
        DB::table('tonKhoLo')->insert([
            'loThuocId' => 100,
            'toChucId' => 20,
            'soLuong' => 15,
        ]);
        DB::table('chuyenGiao')->insert([
            'loThuocId' => 100,
            'benGuiId' => 20,
            'benNhanId' => 30,
            'soLuong' => 15,
            'trangThai' => 'PENDING',
        ]);

        $service = app(TonKhoService::class);

        $this->assertSame(15, $service->soLuongHienCo(100, 20));
        $this->assertSame(15, $service->soLuongDangChuyenGiao(100, 20));
        $this->assertSame(0, $service->soLuongKhaDung(100, 20));
        $this->assertDatabaseHas('tonKhoLo', [
            'loThuocId' => 100,
            'toChucId' => 20,
            'soLuong' => 15,
        ]);
    }

    public function test_rejected_transfer_restores_available_quantity_and_received_transfer_moves_ownership(): void
    {
        DB::table('tonKhoLo')->insert([
            'loThuocId' => 101,
            'toChucId' => 20,
            'soLuong' => 12,
        ]);
        $transferId = DB::table('chuyenGiao')->insertGetId([
            'loThuocId' => 101,
            'benGuiId' => 20,
            'benNhanId' => 30,
            'soLuong' => 12,
            'trangThai' => 'PENDING',
        ]);

        $service = app(TonKhoService::class);
        $this->assertSame(0, $service->soLuongKhaDung(101, 20));

        DB::table('chuyenGiao')->where('id', $transferId)->update(['trangThai' => 'REJECTED']);
        $this->assertSame(12, $service->soLuongKhaDung(101, 20));

        DB::table('chuyenGiao')->where('id', $transferId)->update(['trangThai' => 'PENDING']);
        DB::table('tonKhoLo')->where('loThuocId', 101)->update(['toChucId' => 30]);
        DB::table('chuyenGiao')->where('id', $transferId)->update(['trangThai' => 'RECEIVED']);

        $this->assertSame(0, $service->soLuongKhaDung(101, 20));
        $this->assertSame(12, $service->soLuongKhaDung(101, 30));
    }
}

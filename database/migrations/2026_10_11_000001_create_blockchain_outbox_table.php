<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blockchainTransaction', function (Blueprint $table): void {
            $table->unsignedBigInteger('toChucId')->nullable()->change();
            $table->unsignedBigInteger('loThuocId')->nullable()->change();
        });

        Schema::create('blockchainOutbox', function (Blueprint $table): void {
            $table->id();
            $table->string('idempotencyKey', 100)->unique();
            $table->string('eventName', 100);
            $table->string('businessId', 100);
            $table->string('functionName', 100);
            $table->json('payload');
            $table->string('txHash', 66)->nullable()->unique();
            $table->string('network', 50);
            $table->string('trangThai', 30)->default('QUEUED');
            $table->unsignedInteger('soLanThu')->default(0);
            $table->unsignedInteger('soConfirmations')->default(0);
            $table->unsignedBigInteger('blockNumber')->nullable();
            $table->unsignedBigInteger('toChucId')->nullable();
            $table->unsignedBigInteger('loThuocId')->nullable();
            $table->unsignedBigInteger('chuyenGiaoId')->nullable();
            $table->unsignedBigInteger('donViSanPhamId')->nullable();
            $table->unsignedBigInteger('banLeId')->nullable();
            $table->text('loiGanNhat')->nullable();
            $table->timestamp('thoiGianThuLai')->nullable();
            $table->timestamp('thoiGianGuiGanNhat')->nullable();
            $table->timestamp('thoiGianXacNhan')->nullable();
            $table->timestamps();
            $table->index(['trangThai', 'thoiGianThuLai']);
            $table->index(['loThuocId', 'eventName']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blockchainOutbox');

        Schema::table('blockchainTransaction', function (Blueprint $table): void {
            $table->unsignedBigInteger('toChucId')->nullable(false)->change();
            $table->unsignedBigInteger('loThuocId')->nullable(false)->change();
        });
    }
};

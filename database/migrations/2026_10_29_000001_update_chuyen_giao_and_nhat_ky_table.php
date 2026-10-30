<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('nhatKyHeThong')) {
            try {
                DB::statement('ALTER TABLE nhatKyHeThong DROP FOREIGN KEY nhatkyhethong_ibfk_2');
            } catch (\Throwable $e) {
                // Đã drop hoặc không tồn tại constraint
            }
        }

        if (Schema::hasTable('chuyenGiao')) {
            try {
                DB::statement("ALTER TABLE chuyenGiao MODIFY COLUMN trangThai ENUM('PENDING','RECEIVED','CANCELLED','REJECTED') NOT NULL DEFAULT 'PENDING'");
            } catch (\Throwable $e) {
                // Đã cập nhật enum
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('chuyenGiao')) {
            try {
                DB::statement("ALTER TABLE chuyenGiao MODIFY COLUMN trangThai ENUM('PENDING','RECEIVED') NOT NULL DEFAULT 'PENDING'");
            } catch (\Throwable $e) {
            }
        }
    }
};

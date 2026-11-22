<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('baoCaoNghiVan', function (Blueprint $table) {
            if (! Schema::hasColumn('baoCaoNghiVan', 'loaiBaoCao')) {
                $table->enum('loaiBaoCao', ['TRONG_VONG_DOI', 'NGOAI_HE_THONG'])
                    ->default('TRONG_VONG_DOI')
                    ->after('maBaoCao');
            }

            if (! Schema::hasColumn('baoCaoNghiVan', 'loBiLamGiaId')) {
                $table->unsignedBigInteger('loBiLamGiaId')->nullable()->after('loThuocId');
                $table->foreign('loBiLamGiaId', 'fk_baocao_lobilamgia')
                    ->references('id')
                    ->on('loThuoc')
                    ->nullOnDelete();
                $table->index('loBiLamGiaId', 'idx_baocao_lobilamgia');
            }

            if (! Schema::hasColumn('baoCaoNghiVan', 'toChucId')) {
                $table->unsignedBigInteger('toChucId')->nullable()->after('taiKhoanId');
                $table->foreign('toChucId', 'fk_baocao_tochuc')
                    ->references('id')
                    ->on('toChuc')
                    ->nullOnDelete();
                $table->index('toChucId', 'idx_baocao_tochuc');
            }
        });

        DB::table('baoCaoNghiVan')
            ->whereNull('loThuocId')
            ->update(['loaiBaoCao' => 'NGOAI_HE_THONG']);
    }

    public function down(): void
    {
        Schema::table('baoCaoNghiVan', function (Blueprint $table) {
            if (Schema::hasColumn('baoCaoNghiVan', 'loBiLamGiaId')) {
                $table->dropForeign('fk_baocao_lobilamgia');
                $table->dropIndex('idx_baocao_lobilamgia');
                $table->dropColumn('loBiLamGiaId');
            }

            if (Schema::hasColumn('baoCaoNghiVan', 'toChucId')) {
                $table->dropForeign('fk_baocao_tochuc');
                $table->dropIndex('idx_baocao_tochuc');
                $table->dropColumn('toChucId');
            }

            if (Schema::hasColumn('baoCaoNghiVan', 'loaiBaoCao')) {
                $table->dropColumn('loaiBaoCao');
            }
        });
    }
};

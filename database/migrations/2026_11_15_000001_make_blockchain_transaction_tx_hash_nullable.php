<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blockchainTransaction', function (Blueprint $table): void {
            $table->string('txHash', 66)->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::table('blockchainTransaction')->whereNull('txHash')->exists()) {
            throw new RuntimeException(
                'Không thể rollback: blockchainTransaction đang có bản ghi chưa nhận txHash.'
            );
        }

        Schema::table('blockchainTransaction', function (Blueprint $table): void {
            $table->string('txHash', 66)->nullable(false)->change();
        });
    }
};

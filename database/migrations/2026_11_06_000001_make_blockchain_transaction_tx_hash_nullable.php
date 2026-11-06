<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        // Rollback chỉ thực hiện được sau khi mọi transaction PENDING/FAILED
        // đã được xử lý và txHash không còn NULL.
        Schema::table('blockchainTransaction', function (Blueprint $table): void {
            $table->string('txHash', 66)->nullable(false)->change();
        });
    }
};

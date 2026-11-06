<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blockchainTransaction', function (Blueprint $table): void {
            $table->string('txHash', 66)->nullable()->change();
        });

        // Dọn các marker giả đã từng được ghi nhầm vào cột txHash.
        // Các giá trị 0x... không thể tự động phân biệt thật/giả chỉ bằng regex.
        DB::table('blockchainTransaction')
            ->where(function ($query): void {
                $query->where('txHash', 'like', 'PENDING_%')
                    ->orWhere('txHash', 'like', 'ONCHAIN_CONFIRMED_%')
                    ->orWhere('txHash', 'like', 'ONCHAIN_TRANSFER_%');
            })
            ->update(['txHash' => null]);
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

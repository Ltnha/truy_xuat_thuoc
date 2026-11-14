<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('chuyenGiao', 'lyDoTuChoi')) {
            Schema::table('chuyenGiao', function (Blueprint $table): void {
                $table->string('lyDoTuChoi', 500)->nullable()->after('trangThai');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('chuyenGiao', 'lyDoTuChoi')) {
            Schema::table('chuyenGiao', function (Blueprint $table): void {
                $table->dropColumn('lyDoTuChoi');
            });
        }
    }
};

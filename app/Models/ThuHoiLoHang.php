<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ThuHoiLoHang extends Model
{
    protected $table = 'thuHoiLoHang';
    public $timestamps = false;
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'ngayThuHoi' => 'datetime',
        ];
    }

    public function loThuoc(): BelongsTo
    {
        return $this->belongsTo(LoThuoc::class, 'loThuocId');
    }

    public function coQuanQuyetDinh(): BelongsTo
    {
        return $this->belongsTo(TaiKhoan::class, 'coQuanQuyetDinhId');
    }

    public function ketQuaXuLyBaoCao(): BelongsTo
    {
        return $this->belongsTo(KetQuaXuLyBaoCao::class, 'ketQuaXuLyBaoCaoId');
    }

    public function blockchainTx(): HasOne
    {
        return $this->hasOne(BlockchainTransaction::class, 'loThuocId', 'loThuocId')
            ->whereIn('eventName', ['BatchRecalled', 'RECALL_BATCH'])
            ->latestOfMany('id');
    }

    public function getTrangThaiDongBoAttribute(): string
    {
        return $this->blockchainTx?->trangThai ?? 'CHO_DONG_BO';
    }
}

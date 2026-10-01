<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;

abstract class Policy
{
    use HandlesAuthorization;

    protected function boTuChoi(string $thongBao = 'Bạn không có quyền thực hiện hành động này.'): bool
    {
        return $this->deny($thongBao);
    }
}

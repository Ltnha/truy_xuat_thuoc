<?php

namespace App\Services\XacThuc;

use App\Enums\AccountStatus;
use App\Services\Service;
use Illuminate\Support\Facades\Auth;

class XacThucService extends Service
{
    public function dangNhap(array $duLieu): bool
    {
        if (! Auth::attempt([
            'tenDangNhap' => $duLieu['tenDangNhap'],
            'password' => $duLieu['matKhau'],
        ])) {
            return false;
        }

        if (Auth::user()?->trangThai !== AccountStatus::active) {
            Auth::logout();

            return false;
        }

        return true;
    }
}

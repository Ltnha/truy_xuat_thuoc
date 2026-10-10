<?php

namespace App\Http\Requests\XacThuc;

use App\Http\Requests\BaseRequest;

class DangNhapRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'tenDangNhap' => ['required', 'string'],
            'matKhau' => ['required', 'string', 'min:8'],
        ];
    }
}

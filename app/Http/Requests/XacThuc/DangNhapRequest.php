<?php

namespace App\Http\Requests\XacThuc;

use App\Http\Requests\BaseRequest;

class DangNhapRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'matKhau' => ['required', 'string', 'min:8'],
        ];
    }
}

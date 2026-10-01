<?php

namespace App\Http\Requests\XacThuc;

use App\Http\Requests\BaseRequest;

class DangKyToChucRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'tenToChuc' => ['required', 'string', 'max:255'],
            'loaiToChuc' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
        ];
    }
}

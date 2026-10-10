<?php

namespace App\Http\Requests\LoThuoc;

use App\Http\Requests\BaseRequest;

class LoThuocRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'sanPhamId' => ['required', 'integer', 'exists:sanPham,id'],
            'maLoNghiepVu' => ['required', 'string', 'max:50', 'unique:loThuoc,maLoNghiepVu'],
            'soLuong' => ['required', 'integer', 'min:1'],
            'ngaySanXuat' => ['required', 'date'],
            'hanSuDung' => ['required', 'date', 'after:ngaySanXuat'],
        ];
    }
}

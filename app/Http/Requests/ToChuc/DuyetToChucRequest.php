<?php

namespace App\Http\Requests\ToChuc;

use App\Http\Requests\BaseRequest;

class DuyetToChucRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'toChucId' => ['required', 'integer', 'exists:toChuc,id'],
            'trangThaiDuyet' => ['required', 'string'],
        ];
    }
}

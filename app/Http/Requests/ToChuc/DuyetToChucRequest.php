<?php

namespace App\Http\Requests\ToChuc;

use Illuminate\Foundation\Http\FormRequest;

class DuyetToChucRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'trangThaiDuyet' => ['required', 'string', 'in:DA_DUYET,TU_CHOI'],
            'lyDo' => ['nullable', 'required_if:trangThaiDuyet,TU_CHOI', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'lyDo.required_if' => 'Vui lòng nhập lý do từ chối phê duyệt hồ sơ.',
        ];
    }
}

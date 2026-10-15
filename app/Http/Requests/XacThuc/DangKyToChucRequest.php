<?php

namespace App\Http\Requests\XacThuc;

use Illuminate\Foundation\Http\FormRequest;

class DangKyToChucRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'loaiToChuc' => ['required', 'string', 'in:NHA_SAN_XUAT,NHA_PHAN_PHOI,NHA_THUOC'],
            'tenToChuc' => ['required', 'string', 'max:255'],
            'maSoThue' => ['required', 'string', 'max:20', 'unique:toChuc,maSoThue'],
            'soDienThoai' => ['required', 'string', 'max:20'],
            'diaChi' => ['required', 'string', 'max:255'],
            'soGiayPhepDuoc' => ['required', 'string', 'max:50'],
            'coQuanCap' => ['required', 'string', 'max:255'],
            'ngayCapGiayPhep' => ['required', 'date', 'before_or_equal:today'],
            'ngayHetHanGiayPhep' => ['nullable', 'date', 'after:ngayCapGiayPhep'],
            'taiLieu' => ['required', 'array', 'min:1', 'max:5'],
            'taiLieu.*' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'tenDangNhap' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:taiKhoan,tenDangNhap'],
            'email' => ['required', 'email', 'max:100', 'unique:taiKhoan,email'],
            'matKhau' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'maSoThue.unique' => 'Mã số thuế này đã được đăng ký trên hệ thống.',
            'tenDangNhap.unique' => 'Tên đăng nhập này đã tồn tại, vui lòng chọn tên khác.',
            'email.unique' => 'Email này đã được sử dụng bởi một tài khoản khác.',
            'matKhau.confirmed' => 'Mật khẩu xác nhận không trùng khớp.',
            'taiLieu.required' => 'Vui lòng đính kèm ít nhất 1 tệp tài liệu minh chứng.',
        ];
    }
}

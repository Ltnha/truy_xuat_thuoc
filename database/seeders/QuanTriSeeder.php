<?php
namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\OrganizationApprovalStatus;
use App\Enums\OrganizationType;
use App\Enums\Role;
use App\Models\TaiKhoan;
use App\Models\ToChuc;
use Illuminate\Database\Seeder;

class QuanTriSeeder extends Seeder
{
    public function run(): void
    {
        $toChuc = ToChuc::firstOrCreate([
            'tenToChuc' => 'Ban Quản Lý Dược Phẩm',
            'maSoThue' => 'MST-0001',
            'loaiToChuc' => OrganizationType::coQuanQuanLy->value,
            'trangThaiDuyet' => OrganizationApprovalStatus::daDuyet->value,
        ]);

        TaiKhoan::firstOrCreate(
            ['tenDangNhap' => 'admin'],
            [
                'matKhau' => bcrypt('admin123'),
                'email' => 'admin@example.com',
                'soDienThoai' => '0900000000',
                'vaiTro' => Role::quanTriVien->value,
                'trangThai' => AccountStatus::active->value,
                'toChucId' => $toChuc->id,
            ]
        );
    }
}
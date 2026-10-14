<?php

namespace App\Services\XacThuc;

use App\Enums\AccountStatus;
use App\Enums\OrganizationApprovalStatus;
use App\Models\TaiKhoan;
use App\Services\Service;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class XacThucService extends Service
{
    public function dangNhap(array $duLieu): array
    {
        $tenDangNhap = $duLieu['tenDangNhap'];
        $matKhau = $duLieu['matKhau'];

        // Truy vấn tài khoản kèm tổ chức và yêu cầu đăng ký mới nhất
        $taiKhoan = TaiKhoan::query()
            ->with(['toChuc.yeuCauDangKys' => fn($q) => $q->latest('id')])
            ->where('tenDangNhap', $tenDangNhap)
            ->first();

        // 1. Kiểm tra sự tồn tại & mật khẩu
        if (! $taiKhoan || ! Hash::check($matKhau, $taiKhoan->matKhau)) {
            return [
                'thanhCong' => false,
                'thongBao' => 'Tên đăng nhập hoặc mật khẩu không chính xác.',
            ];
        }

        // 2. Kiểm tra trạng thái xét duyệt của Tổ chức
        if ($taiKhoan->toChuc) {
            $trangThaiDuyet = $taiKhoan->toChuc->trangThaiDuyet;

            if ($trangThaiDuyet === OrganizationApprovalStatus::choDuyet) {
                return [
                    'thanhCong' => false,
                    'thongBao' => 'Tài khoản tổ chức của bạn đang chờ Cơ quan quản lý xét duyệt. Vui lòng quay lại sau.',
                ];
            }

            if ($trangThaiDuyet === OrganizationApprovalStatus::tuChoi) {
                $yeuCau = $taiKhoan->toChuc->yeuCauDangKys->first();
                $lyDo = $yeuCau?->lyDoTuChoi ?? 'Hồ sơ chưa đáp ứng đủ điều kiện pháp lý.';

                return [
                    'thanhCong' => false,
                    'thongBao' => "Hồ sơ đăng ký tài khoản tổ chức của bạn đã bị TỪ CHỐI xét duyệt.\nLý do từ chối: {$lyDo}",
                ];
            }

            if ($trangThaiDuyet === OrganizationApprovalStatus::biThuHoi) {
                return [
                    'thanhCong' => false,
                    'thongBao' => 'Quyền hoạt động của tổ chức đã bị Cơ quan quản lý thu hồi.',
                ];
            }
        }

        // 3. Kiểm tra trạng thái kích hoạt của Tài khoản
        if ($taiKhoan->trangThai !== AccountStatus::active) {
            return [
                'thanhCong' => false,
                'thongBao' => 'Tài khoản của bạn hiện đang bị khóa hoặc chưa được kích hoạt.',
            ];
        }

        // Đăng nhập hợp lệ -> Tạo Session
        Auth::login($taiKhoan);

        return [
            'thanhCong' => true,
            'taiKhoan' => $taiKhoan,
        ];
    }
}

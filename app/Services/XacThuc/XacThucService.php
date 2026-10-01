<?php

namespace App\Services\XacThuc;

use App\Services\Service;

class XacThucService extends Service
{
    public function dangNhap(array $duLieu): array
    {
        $this->ghiNhatKy('DANG_NHAP', $duLieu);

        return [
            'status' => 'pending',
            'message' => 'Chức năng đăng nhập đang chờ triển khai nghiệp vụ.',
        ];
    }
}

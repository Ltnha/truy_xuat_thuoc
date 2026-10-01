<?php

namespace App\Services\LoThuoc;

use App\Services\Service;

class LoThuocService extends Service
{
    public function tao(array $duLieu): array
    {
        $this->ghiNhatKy('TAO_LO_THUOC', $duLieu);

        return [
            'status' => 'pending',
            'message' => 'Chức năng tạo lô thuốc đang chờ triển khai nghiệp vụ.',
        ];
    }
}

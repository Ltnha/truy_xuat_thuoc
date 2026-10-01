<?php

namespace App\Services\ToChuc;

use App\Services\Service;

class ToChucService extends Service
{
    public function duyet(array $duLieu): array
    {
        $this->ghiNhatKy('DUYET_TO_CHUC', $duLieu);

        return [
            'status' => 'pending',
            'message' => 'Chức năng duyệt tổ chức đang chờ triển khai nghiệp vụ.',
        ];
    }
}

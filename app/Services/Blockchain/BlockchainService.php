<?php

namespace App\Services\Blockchain;

use LogicException;

/**
 * @deprecated Use SignerClient / SignerBlockchainGateway.
 * Private keys must never be loaded or signed inside the Laravel process.
 */
class BlockchainService
{
    public static function recordBatchAuto($loThuoc)
    {
        throw new LogicException(
            'BlockchainService::recordBatchAuto đã ngừng sử dụng. Hãy dùng ProcessBatchRegistrationJob hoặc SignerBlockchainGateway.'
        );
    }
}

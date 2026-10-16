<?php

namespace App\Services\Blockchain;

interface BlockchainGateway
{
    /**
     * Ghi nhận giao dịch nghiệp vụ lô thuốc lên Blockchain.
     */
    public function ghiNhan(string $tenNghiepVu, array $duLieu): array;

    /**
     * Cấp vai trò On-chain cho địa chỉ ví của tổ chức.
     */
    public function grantRole(string $accountAddress, string $roleHash): string;

    /**
     * Sinh cặp khóa ví Custodial Wallet cho doanh nghiệp.
     */
    public function taoViCustodial(): array;
}

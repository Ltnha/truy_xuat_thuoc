<?php

return [
    'blockchain' => [
        'driver' => env('BLOCKCHAIN_DRIVER', 'sepolia'),
        'mang' => env('BLOCKCHAIN_NETWORK', 'Ethereum Sepolia'),
        'chainId' => (int) env('BLOCKCHAIN_CHAIN_ID', 11155111),
        'confirmations' => (int) env('BLOCKCHAIN_CONFIRMATIONS', 3),
        'rpcUrl' => env('BLOCKCHAIN_RPC_URL'),
        'diaChiHopDong' => env('BLOCKCHAIN_CONTRACT_ADDRESS'),
        'signerUrl' => env('BLOCKCHAIN_SIGNER_URL'),
        'signerApiToken' => env('SIGNER_API_TOKEN'),
        'adminKeyId' => env('ADMIN_MASTER_KEY_ID', 'ADMIN_MASTER_KEY'),
        'privateKey' => env('BLOCKCHAIN_PRIVATE_KEY'), // <-- Thêm dòng này vào đây
    ],
    'choPhepNsxDenNhaThuoc' => false,
    'soDonViBanToiDaMoiLan' => 50,
    'kichThuocChunk' => 1000,
    'diskTaiLieu' => 'private',
];

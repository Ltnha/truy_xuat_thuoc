<?php
return [
    'blockchain' => [
        'driver' => env('BLOCKCHAIN_DRIVER', 'fake'),
        'mang' => env('BLOCKCHAIN_NETWORK', 'Polygon Testnet'),
        'rpcUrl' => env('BLOCKCHAIN_RPC_URL'),
        'diaChiHopDong' => env('BLOCKCHAIN_CONTRACT_ADDRESS'),
        'signerUrl' => env('BLOCKCHAIN_SIGNER_URL'),
    ],
    'choPhepNsxDenNhaThuoc' => false,
    'soDonViBanToiDaMoiLan' => 50,
    'kichThuocChunk' => 1000,
    'diskTaiLieu' => 'private',
];
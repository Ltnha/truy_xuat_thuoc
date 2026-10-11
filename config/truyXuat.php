<?php
return [
    'blockchain' => [
        'driver' => env('BLOCKCHAIN_DRIVER', 'fake'),
        'mang' => env('BLOCKCHAIN_NETWORK', 'Ethereum Sepolia'),
        'chainId' => (int) env('BLOCKCHAIN_CHAIN_ID', 11155111),
        'confirmations' => (int) env('BLOCKCHAIN_CONFIRMATIONS', 3),
        'rpcUrl' => env('BLOCKCHAIN_RPC_URL'),
        'diaChiHopDong' => env('BLOCKCHAIN_CONTRACT_ADDRESS'),
        'signerUrl' => env('BLOCKCHAIN_SIGNER_URL'),
    ],
    'choPhepNsxDenNhaThuoc' => false,
    'soDonViBanToiDaMoiLan' => 50,
    'kichThuocChunk' => 1000,
    'diskTaiLieu' => 'private',
];
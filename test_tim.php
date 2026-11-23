<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Mock signer client that always fails to test Tier 2 direct RPC
$failingSigner = new class extends \App\Services\Blockchain\SignerClient {
    public function __construct() {}
    public function getBatchState(string $batchId): array { throw new Exception("Signer down test"); }
    public function isProductApproved(string $productId): array { throw new Exception("Signer down test"); }
};

$controller = new \App\Http\Controllers\TraCuu\TraCuuController($failingSigner);
$reflector = new ReflectionClass($controller);
$method = $reflector->getMethod('timTheoMa');
$method->setAccessible(true);

$res = $method->invoke($controller, 'lo2111520');
echo "=== TIER 2 FALLBACK (SIGNER DOWN) ===" . PHP_EOL;
echo "TrangThai: " . ($res['trangThai'] ?? '') . PHP_EOL;
echo "ChainReadStatus: " . ($res['chainReadStatus'] ?? '') . PHP_EOL;
echo "ProductIntegrity: " . ($res['productIntegrity'] ?? '') . PHP_EOL;
echo "BatchExistsOnChain: " . (($res['batchExistsOnChain'] ?? false) ? 'true' : 'false') . PHP_EOL;
echo "ChainOwner: " . ($res['chainOwner'] ?? '') . PHP_EOL;

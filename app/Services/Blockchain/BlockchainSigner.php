<?php

namespace App\Services\Blockchain;

use App\Exceptions\NghiepVuException;
use Illuminate\Support\Facades\Http;
use Throwable;

class BlockchainSigner
{
    public function submit(string $data, string $idempotencyKey): string
    {
        $url = config('truyXuat.blockchain.signerUrl');
        $token = config('truyXuat.blockchain.signerToken');

        if (! is_string($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new NghiepVuException('Chưa cấu hình dịch vụ ký blockchain an toàn.');
        }

        try {
            $response = Http::acceptJson()
                ->withToken(is_string($token) ? $token : '')
                ->timeout(15)
                ->withHeaders(['Idempotency-Key' => $idempotencyKey])
                ->post($url, [
                    'chainId' => (int) config('truyXuat.blockchain.chainId'),
                    'contractAddress' => config('truyXuat.blockchain.diaChiHopDong'),
                    'data' => $data,
                    'idempotencyKey' => $idempotencyKey,
                ]);
        } catch (Throwable $exception) {
            throw new NghiepVuException('Không kết nối được dịch vụ ký blockchain: '.$exception->getMessage(), previous: $exception);
        }

        if (! $response->successful()) {
            throw new NghiepVuException("Dịch vụ ký blockchain trả HTTP {$response->status()}.");
        }

        $txHash = $response->json('txHash');
        if (! is_string($txHash) || ! preg_match('/^0x[a-fA-F0-9]{64}$/', $txHash)) {
            throw new NghiepVuException('Dịch vụ ký blockchain không trả transaction hash hợp lệ.');
        }

        return strtolower($txHash);
    }
}

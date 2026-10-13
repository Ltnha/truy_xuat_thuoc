<?php

namespace App\Console\Commands;

use App\Models\BlockchainOutbox;
use App\Services\Blockchain\BlockchainOutboxProcessor;
use Illuminate\Console\Command;

class ProcessBlockchainOutbox extends Command
{
    protected $signature = 'blockchain:process-outbox {--limit=100 : Maximum outbox records to process}';

    protected $description = 'Submit and confirm queued Ethereum Sepolia business events';

    public function handle(BlockchainOutboxProcessor $processor): int
    {
        if (config('truyXuat.blockchain.driver') !== 'sepolia') {
            $this->error('Set BLOCKCHAIN_DRIVER=sepolia before processing blockchain outbox records.');

            return self::FAILURE;
        }

        $limit = max(1, min(500, (int) $this->option('limit')));
        $records = BlockchainOutbox::query()
            ->where(function ($query): void {
                $query->whereIn('trangThai', ['QUEUED', 'SUBMITTED'])
                    ->orWhere(function ($retry): void {
                        $retry->where('trangThai', 'RETRY')
                            ->where(function ($due): void {
                                $due->whereNull('thoiGianThuLai')->orWhere('thoiGianThuLai', '<=', now());
                            });
                    });
            })
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($records as $record) {
            $processor->process($record);
        }

        $this->info("Processed {$records->count()} blockchain outbox record(s).");

        return self::SUCCESS;
    }
}

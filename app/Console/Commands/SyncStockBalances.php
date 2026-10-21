<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\StockReconciliationService;

class SyncStockBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:sync {--product_id= : Specific product ID to sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize warehouse stocks and product batch quantities with true transaction ledgers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $productId = $this->option('product_id') ? (int) $this->option('product_id') : null;

        $this->info("Starting stock reconciliation" . ($productId ? " for product ID {$productId}..." : "..."));

        $result = StockReconciliationService::reconcile($productId);

        $this->info($result['message']);
        $this->table(
            ['Metric', 'Count'],
            [
                ['Products Scanned', $result['products_scanned']],
                ['Warehouse Stock Records Updated', $result['stocks_updated']],
                ['Batch Records Updated', $result['batches_updated']],
                ['Stock Movements Synchronized', $result['movements_updated'] ?? 0],
            ]
        );

        return 0;
    }
}

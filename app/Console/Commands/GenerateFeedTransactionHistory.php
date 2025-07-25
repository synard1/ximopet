<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Feed\FeedTransactionHistoryService;
use Illuminate\Support\Facades\Log;

/**
 * Command untuk generate/migrasi data histori transaksi feed ke tabel feed_transaction_histories.
 *
 * Contoh penggunaan:
 * php artisan feed:generate-history --livestock=... --feed=... --start=2025-01-01 --end=2025-12-31 --company=...
 *
 * Opsi:
 * --livestock=...   (filter livestock_id)
 * --feed=...        (filter feed_id)
 * --start=...       (start date)
 * --end=...         (end date)
 * --company=...     (filter company_id)
 * --dry-run         (hanya simulasi, tidak insert data)
 */
class GenerateFeedTransactionHistory extends Command
{
    protected $signature = 'feed:generate-history
        {--livestock= : Filter by livestock_id}
        {--feed= : Filter by feed_id}
        {--start= : Start date (YYYY-MM-DD)}
        {--end= : End date (YYYY-MM-DD)}
        {--company= : Filter by company_id}
        {--dry-run : Simulate only, do not insert data}';

    protected $description = 'Generate/migrate feed transaction history data to feed_transaction_histories table.';

    public function handle(FeedTransactionHistoryService $service)
    {
        $livestockId = $this->option('livestock');
        $feedId = $this->option('feed');
        $startDate = $this->option('start');
        $endDate = $this->option('end');
        $companyId = $this->option('company');
        $dryRun = $this->option('dry-run');

        $extraFilter = [];
        if ($companyId) $extraFilter['company_id'] = $companyId;

        $this->info('Generating feed transaction history...');
        $this->info('Filter: ' . json_encode([
            'livestock_id' => $livestockId,
            'feed_id' => $feedId,
            'start' => $startDate,
            'end' => $endDate,
            'company_id' => $companyId,
            'dry_run' => $dryRun,
        ]));
        if (!$livestockId && !$feedId) {
            $this->warn('WARNING: Running without livestock_id or feed_id filter. This may process a very large amount of data!');
        }

        // Generate from legacy/operasional data
        $histories = $service->generateFromLegacy($livestockId, $feedId, $startDate, $endDate, $extraFilter, $dryRun);
        $total = count($histories);
        $this->info("Found $total transaction(s) to process.");
        if ($total === 0) {
            $this->info('No data to process.');
            return 0;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();
        $created = 0;
        foreach ($histories as $trx) {
            $created++;
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
        $this->info($dryRun
            ? "[DRY RUN] $total transaction(s) would be generated."
            : "$created transaction(s) generated to feed_transaction_histories.");
        Log::info('[FeedTransactionHistory] Generate command executed', [
            'livestock_id' => $livestockId,
            'feed_id' => $feedId,
            'start' => $startDate,
            'end' => $endDate,
            'company_id' => $companyId,
            'dry_run' => $dryRun,
            'total' => $total,
            'created' => $created,
        ]);
        return 0;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Livestock\LivestockCostRecalculationService;
use App\Models\Livestock;
use Illuminate\Support\Facades\Log;

class RecalculateLivestockCost extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Usage:
     *  php artisan livestock:recalculate-cost {livestock_id} {--from=} {--to=}
     */
    protected $signature = 'livestock:recalculate-cost {livestock_id} {--from=} {--to=} {--dry-run}';

    /**
     * The console command description.
     */
    protected $description = 'Recalculate all livestock cost history for a given livestock. Optionally specify --from and --to dates.';

    /**
     * Execute the console command.
     */
    public function handle(LivestockCostRecalculationService $recalcService)
    {
        $livestockId = $this->argument('livestock_id');
        $from = $this->option('from');
        $to = $this->option('to');
        $dryRun = $this->option('dry-run');

        $livestock = Livestock::find($livestockId);
        if (!$livestock) {
            $this->error('Livestock not found: ' . $livestockId);
            return 1;
        }

        // --- Analisa tanggal dan sumber data ---
        $startDate = $from ? $from : $livestock->start_date;
        $endDate = $to ? $to : now()->toDateString();
        $start = \Carbon\Carbon::parse($startDate);
        $end = \Carbon\Carbon::parse($endDate);
        $dateList = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dateStr = $date->format('Y-m-d');
            $hasRecording = \App\Models\Recording::where('livestock_id', $livestockId)->whereDate('tanggal', $dateStr)->exists();
            $hasFeedUsage = \App\Models\FeedUsage::where('livestock_id', $livestockId)->whereDate('usage_date', $dateStr)->exists();
            $hasSupplyUsage = \App\Models\SupplyUsage::where('livestock_id', $livestockId)->whereDate('usage_date', $dateStr)->exists();
            $hasDepletion = \App\Models\LivestockDepletion::where('livestock_id', $livestockId)->whereDate('tanggal', $dateStr)->exists();
            $dateList[] = [
                'date' => $dateStr,
                'recording' => $hasRecording,
                'feed_usage' => $hasFeedUsage,
                'supply_usage' => $hasSupplyUsage,
                'depletion' => $hasDepletion,
            ];
        }
        // Output tabel informatif
        $this->info("\nTanggal yang akan dikalkulasi dan sumber data:");
        $this->line(str_pad('Tanggal', 12) . str_pad('Recording', 12) . str_pad('FeedUsage', 12) . str_pad('SupplyUsage', 14) . str_pad('Depletion', 12));
        foreach ($dateList as $row) {
            $this->line(str_pad($row['date'], 12)
                . str_pad($row['recording'] ? 'Y' : '-', 12)
                . str_pad($row['feed_usage'] ? 'Y' : '-', 12)
                . str_pad($row['supply_usage'] ? 'Y' : '-', 14)
                . str_pad($row['depletion'] ? 'Y' : '-', 12));
        }
        Log::info('📋 Livestock cost recalculation data sources', [
            'livestock_id' => $livestockId,
            'date_sources' => $dateList
        ]);

        $this->info('Starting recalculation for Livestock: ' . $livestock->name . ' (' . $livestock->id . ')');
        if ($from && $to) {
            $this->info("Recalculating from $from to $to");
            $recalcService->recalculateAll($livestockId, $from, $to, $dryRun);
        } elseif ($from) {
            $this->info("Recalculating from $from to latest");
            $recalcService->recalculateFrom($livestockId, $from, $dryRun);
        } else {
            $this->info('Recalculating ALL history');
            $recalcService->recalculateAll($livestockId, null, null, $dryRun);
        }
        $this->info('Done.');
        return 0;
    }
}

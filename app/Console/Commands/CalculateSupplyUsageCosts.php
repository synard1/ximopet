<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SupplyUsage;
use App\Models\Livestock;
use App\Models\Recording;
use App\Services\Supply\SupplyUsageCostService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CalculateSupplyUsageCosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'supply:calculate-costs 
                            {--livestock-id= : Specific livestock ID to process}
                            {--date= : Specific date to process (Y-m-d format)}
                            {--start-date= : Start date for range processing (Y-m-d format)}
                            {--end-date= : End date for range processing (Y-m-d format)}
                            {--force : Force recalculation even if cost record exists}
                            {--dry-run : Show what would be calculated without saving}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate supply usage costs for livestock without recordings';

    protected SupplyUsageCostService $costService;

    public function __construct(SupplyUsageCostService $costService)
    {
        parent::__construct();
        $this->costService = $costService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Starting Supply Usage Cost Calculation...');

        $livestockId = $this->option('livestock-id');
        $date = $this->option('date');
        $startDate = $this->option('start-date');
        $endDate = $this->option('end-date');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be saved');
        }

        try {
            if ($livestockId) {
                // Process specific livestock
                $this->processSpecificLivestock($livestockId, $date, $force, $dryRun);
            } elseif ($startDate && $endDate) {
                // Process date range
                $this->processDateRange($startDate, $endDate, $force, $dryRun);
            } elseif ($date) {
                // Process specific date
                $this->processSpecificDate($date, $force, $dryRun);
            } else {
                // Process all livestock with supply usage but no recordings
                $this->processAllLivestock($force, $dryRun);
            }

            $this->info('✅ Supply usage cost calculation completed successfully!');
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Error during calculation: ' . $e->getMessage());
            Log::error('Supply usage cost calculation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Process specific livestock
     */
    private function processSpecificLivestock($livestockId, $date, $force, $dryRun)
    {
        $this->info("Processing livestock ID: {$livestockId}");

        $livestock = Livestock::find($livestockId);
        if (!$livestock) {
            $this->error("Livestock with ID {$livestockId} not found");
            return;
        }

        if ($date) {
            $this->processLivestockForDate($livestock, $date, $force, $dryRun);
        } else {
            // Process all dates for this livestock
            $this->processLivestockAllDates($livestock, $force, $dryRun);
        }
    }

    /**
     * Process specific date for all livestock
     */
    private function processSpecificDate($date, $force, $dryRun)
    {
        $this->info("Processing date: {$date}");

        // Find livestock with supply usage on this date but no recording
        $supplyUsages = SupplyUsage::whereDate('usage_date', $date)
            ->whereIn('status', ['completed', 'in_process'])
            ->with('livestock')
            ->get()
            ->groupBy('livestock_id');

        $processedCount = 0;

        foreach ($supplyUsages as $livestockId => $usages) {
            $livestock = $usages->first()->livestock;

            // Check if recording exists for this date
            $hasRecording = Recording::where('livestock_id', $livestockId)
                ->whereDate('tanggal', $date)
                ->exists();

            if (!$hasRecording) {
                $this->processLivestockForDate($livestock, $date, $force, $dryRun);
                $processedCount++;
            }
        }

        $this->info("Processed {$processedCount} livestock for date {$date}");
    }

    /**
     * Process date range
     */
    private function processDateRange($startDate, $endDate, $force, $dryRun)
    {
        $this->info("Processing date range: {$startDate} to {$endDate}");

        $currentDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);
        $totalProcessed = 0;

        while ($currentDate->lte($endDate)) {
            $dateStr = $currentDate->format('Y-m-d');
            $this->processSpecificDate($dateStr, $force, $dryRun);
            $currentDate->addDay();
            $totalProcessed++;
        }

        $this->info("Processed {$totalProcessed} dates in range");
    }

    /**
     * Process all livestock with supply usage but no recordings
     */
    private function processAllLivestock($force, $dryRun)
    {
        $this->info('Processing all livestock with supply usage but no recordings...');

        // Get all livestock with supply usage
        $livestockWithSupplyUsage = SupplyUsage::whereIn('status', ['completed', 'in_process'])
            ->with('livestock')
            ->get()
            ->groupBy('livestock_id');

        $processedCount = 0;

        foreach ($livestockWithSupplyUsage as $livestockId => $usages) {
            $livestock = $usages->first()->livestock;
            $this->processLivestockAllDates($livestock, $force, $dryRun);
            $processedCount++;
        }

        $this->info("Processed {$processedCount} livestock");
    }

    /**
     * Process livestock for specific date
     */
    private function processLivestockForDate($livestock, $date, $force, $dryRun)
    {
        $this->line("  Processing {$livestock->name} for {$date}");

        // Check if supply usage exists
        $hasSupplyUsage = SupplyUsage::where('livestock_id', $livestock->id)
            ->whereDate('usage_date', $date)
            ->whereIn('status', ['completed', 'in_process'])
            ->exists();

        if (!$hasSupplyUsage) {
            $this->line("  ⏭️  No supply usage found, skipping");
            return;
        }

        // Check if cost record already exists
        $existingCost = \App\Models\LivestockCost::where('livestock_id', $livestock->id)
            ->whereDate('tanggal', $date)
            ->first();

        if ($existingCost && !$force) {
            $this->line("  ⏭️  Cost record already exists, use --force to recalculate");
            return;
        }

        try {
            if ($dryRun) {
                $costData = $this->costService->calculateForDate($livestock->id, $date);
                $this->line("  🔍 Would calculate cost: {$costData['total_cost']} for {$costData['detail_count']} details");
            } else {
                // Use LivestockCostService which will handle minimal recording creation
                $livestockCostService = app(\App\Services\Livestock\LivestockCostService::class);
                $livestockCost = $livestockCostService->calculateForDate($livestock->id, $date);
                $this->line("  ✅ Cost calculated: {$livestockCost->total_cost} (ID: {$livestockCost->id})");

                // Check if minimal recording was created
                if ($livestockCost->isCalculatedWithMinimalRecording()) {
                    $this->line("  📝 Minimal recording created for supply usage");
                }
            }
        } catch (\Exception $e) {
            $this->error("  ❌ Error processing {$livestock->name} for {$date}: " . $e->getMessage());
        }
    }

    /**
     * Process livestock for all dates
     */
    private function processLivestockAllDates($livestock, $force, $dryRun)
    {
        $this->line("Processing {$livestock->name} (ID: {$livestock->id})");

        // Get all supply usage dates for this livestock
        $supplyUsageDates = SupplyUsage::where('livestock_id', $livestock->id)
            ->whereIn('status', ['completed', 'in_process'])
            ->pluck('usage_date')
            ->map(function ($date) {
                return $date->format('Y-m-d');
            })
            ->unique()
            ->sort()
            ->values();

        $processedDates = 0;

        foreach ($supplyUsageDates as $date) {
            $this->processLivestockForDate($livestock, $date, $force, $dryRun);
            $processedDates++;
        }

        $this->line("  Processed {$processedDates} dates for {$livestock->name}");
    }
}

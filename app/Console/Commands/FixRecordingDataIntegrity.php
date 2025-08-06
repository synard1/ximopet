<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Recording;
use App\Models\Livestock;
use App\Models\LivestockDepletion;
use App\Models\CurrentLivestock;
use Carbon\Carbon;

class FixRecordingDataIntegrity extends Command
{
    protected $signature = 'recording:fix-data-integrity 
                            {--livestock-id= : Specific livestock ID to fix}
                            {--date= : Specific date to fix (Y-m-d format)}
                            {--start-date= : Start date for range (Y-m-d format)}
                            {--end-date= : End date for range (Y-m-d format)}
                            {--dry-run : Show what would be fixed without making changes}
                            {--force : Force fix without confirmation}
                            {--fix-negative-stock : Fix negative stock_akhir values}
                            {--fix-zero-stock-awal : Fix zero stock_awal values}
                            {--fix-null-depletion : Fix null total_deplesi values}';

    protected $description = 'Fix recording data integrity issues (negative stock, zero stock_awal, null depletion)';

    public function handle()
    {
        $this->info('🔧 Starting Recording Data Integrity Fix (Historical Repair)...');
        $this->info('==========================================================');

        $livestockId = $this->option('livestock-id');
        $date = $this->option('date');
        $startDate = $this->option('start-date');
        $endDate = $this->option('end-date');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $fixNegativeStock = $this->option('fix-negative-stock');
        $fixZeroStockAwal = $this->option('fix-zero-stock-awal');
        $fixNullDepletion = $this->option('fix-null-depletion');

        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No changes will be made');
        }

        // Build query - get ALL recordings for historical repair
        $query = Recording::query();

        if ($livestockId) {
            $query->where('livestock_id', $livestockId);
        }

        if ($date) {
            $query->whereDate('tanggal', $date);
        } elseif ($startDate && $endDate) {
            $query->whereBetween('tanggal', [$startDate, $endDate]);
        }

        // IMPORTANT: Sort by livestock_id and tanggal for historical repair
        $recordings = $query->with(['livestock'])
            ->orderBy('livestock_id')
            ->orderBy('tanggal')
            ->get();

        if ($recordings->isEmpty()) {
            $this->info('✅ No recordings found!');
            return 0;
        }

        $this->info("📊 Found {$recordings->count()} recordings for historical repair");

        // Group recordings by livestock_id for historical repair
        $groupedRecordings = $recordings->groupBy('livestock_id');

        $this->info("📋 Grouped by livestock: " . $groupedRecordings->count() . " livestock groups");

        // Show preview of problematic data
        $this->showHistoricalPreview($groupedRecordings);

        // Skip confirmation if force is used or if running in non-interactive mode
        if (!$force && !$dryRun && !$this->isNonInteractive()) {
            if (!$this->confirm('Do you want to proceed with historical repair of these recordings?')) {
                $this->info('❌ Operation cancelled.');
                return 0;
            }
        }

        // Process recordings historically (by livestock and date)
        $results = $this->processRecordingsHistorically($groupedRecordings, $dryRun);

        // Show results
        $this->showResults($results);

        return 0;
    }

    private function isNonInteractive()
    {
        // Check if running in web context or non-interactive mode
        return app()->runningInConsole() === false ||
            app()->environment('testing') ||
            $this->option('force') ||
            $this->option('dry-run');
    }

    private function showHistoricalPreview($groupedRecordings)
    {
        $this->info('📋 Historical Preview by Livestock:');

        foreach ($groupedRecordings as $livestockId => $recordings) {
            $livestock = $recordings->first()->livestock;
            $livestockName = $livestock ? $livestock->name : 'N/A';
            $this->line("  📊 Livestock: {$livestockName} ({$livestockId})");
            $this->line("  📅 Records: {$recordings->count()} recordings");

            // Show first few records
            $sampleRecords = $recordings->take(3);
            foreach ($sampleRecords as $record) {
                $issues = [];
                if ($record->stock_akhir < 0) $issues[] = 'Negative stock_akhir';
                if ($record->stock_awal == 0) $issues[] = 'Zero stock_awal';
                if (is_null($record->total_deplesi)) $issues[] = 'Null depletion';
                if ($record->stock_awal < 0) $issues[] = 'Negative stock_awal';

                $issueText = !empty($issues) ? ' (' . implode(', ', $issues) . ')' : '';

                $this->line("    - {$record->tanggal->format('Y-m-d')}: stock_awal={$record->stock_awal}, stock_akhir={$record->stock_akhir}, deplesi={$record->total_deplesi}{$issueText}");
            }

            if ($recordings->count() > 3) {
                $this->line("    ... and " . ($recordings->count() - 3) . " more records");
            }
            $this->line("");
        }
    }

    private function processRecordingsHistorically($groupedRecordings, $dryRun)
    {
        $results = [
            'processed' => 0,
            'fixed' => 0,
            'errors' => 0,
            'details' => []
        ];

        $totalGroups = $groupedRecordings->count();
        $this->info("🔄 Processing {$totalGroups} livestock groups historically...");

        foreach ($groupedRecordings as $livestockId => $recordings) {
            $this->info("📊 Processing livestock: {$livestockId}");

            try {
                $groupResult = $this->processLivestockHistorically($livestockId, $recordings, $dryRun);
                $results['processed'] += $groupResult['processed'];
                $results['fixed'] += $groupResult['fixed'];
                $results['errors'] += $groupResult['errors'];
                $results['details'] = array_merge($results['details'], $groupResult['details']);
            } catch (\Exception $e) {
                $results['errors']++;
                $this->error("❌ Error processing livestock {$livestockId}: {$e->getMessage()}");
            }
        }

        return $results;
    }

    private function processLivestockHistorically($livestockId, $recordings, $dryRun)
    {
        $results = [
            'processed' => 0,
            'fixed' => 0,
            'errors' => 0,
            'details' => []
        ];

        $livestock = $recordings->first()->livestock;
        $this->line("  📅 Processing {$recordings->count()} recordings chronologically");

        // Get initial stock from livestock
        $initialStock = $this->getInitialStock($livestock);
        $currentStock = $initialStock;

        $this->line("  📊 Initial stock: {$initialStock}");

        foreach ($recordings as $recording) {
            try {
                $result = $this->fixRecordingHistorically($recording, $currentStock, $dryRun);
                $results['processed']++;

                if ($result['success']) {
                    $results['fixed']++;
                    $results['details'][] = $result['details'];

                    // Update current stock for next iteration
                    $currentStock = $result['new_stock_akhir'];
                } else {
                    $results['errors']++;
                    $this->error("  ❌ Error fixing recording {$recording->id}: {$result['error']}");
                }
            } catch (\Exception $e) {
                $results['errors']++;
                $this->error("  ❌ Exception fixing recording {$recording->id}: {$e->getMessage()}");
            }
        }

        return $results;
    }

    private function fixRecordingHistorically($recording, $previousStockAkhir, $dryRun)
    {
        $livestock = $recording->livestock;
        $originalData = [
            'stock_awal' => $recording->stock_awal,
            'stock_akhir' => $recording->stock_akhir,
            'total_deplesi' => $recording->total_deplesi
        ];

        $fixes = [];

        // Fix stock_awal - use previous stock_akhir
        $newStockAwal = $previousStockAkhir;
        if ($recording->stock_awal != $newStockAwal) {
            $fixes['stock_awal'] = [
                'old' => $recording->stock_awal,
                'new' => $newStockAwal
            ];
            if (!$dryRun) {
                $recording->stock_awal = $newStockAwal;
            }
        }

        // Fix total_deplesi if null
        if (is_null($recording->total_deplesi)) {
            $newTotalDepletion = $this->calculateTotalDepletion($recording);
            $fixes['total_deplesi'] = [
                'old' => null,
                'new' => $newTotalDepletion
            ];
            if (!$dryRun) {
                $recording->total_deplesi = $newTotalDepletion;
            }
        }

        // Calculate new stock_akhir
        $newStockAkhir = $newStockAwal - ($recording->total_deplesi ?? 0);
        $newStockAkhir = max(0, $newStockAkhir); // Ensure not negative

        if ($recording->stock_akhir != $newStockAkhir) {
            $fixes['stock_akhir'] = [
                'old' => $recording->stock_akhir,
                'new' => $newStockAkhir
            ];
            if (!$dryRun) {
                $recording->stock_akhir = $newStockAkhir;
            }
        }

        if (empty($fixes)) {
            return [
                'success' => true,
                'details' => 'No fixes needed',
                'new_stock_akhir' => $newStockAkhir
            ];
        }

        if (!$dryRun) {
            $recording->save();
        }

        return [
            'success' => true,
            'details' => [
                'recording_id' => $recording->id,
                'date' => $recording->tanggal->format('Y-m-d'),
                'livestock_name' => $livestock->name ?? 'N/A',
                'fixes' => $fixes,
                'calculation' => "{$newStockAwal} - {$recording->total_deplesi} = {$newStockAkhir}"
            ],
            'new_stock_akhir' => $newStockAkhir
        ];
    }

    private function getInitialStock($livestock)
    {
        // Try to get from livestock initial_quantity
        if ($livestock && $livestock->initial_quantity > 0) {
            return $livestock->initial_quantity;
        }

        // Use CurrentLivestock quantity as fallback
        $currentLivestock = CurrentLivestock::where('livestock_id', $livestock->id)->first();
        if ($currentLivestock && $currentLivestock->quantity > 0) {
            return $currentLivestock->quantity;
        }

        // Default fallback
        return 12000; // Default initial stock
    }

    private function calculateTotalDepletion($recording)
    {
        // Get depletion from LivestockDepletion table
        $depletions = LivestockDepletion::where('livestock_id', $recording->livestock_id)
            ->whereDate('tanggal', $recording->tanggal)
            ->get();

        return $depletions->sum('jumlah');
    }

    private function showResults($results)
    {
        $this->info('📊 Fix Results:');
        $this->line("   - Processed: {$results['processed']}");
        $this->line("   - Fixed: {$results['fixed']}");
        $this->line("   - Errors: {$results['errors']}");

        if (!empty($results['details'])) {
            $this->info('📝 Fix Details:');
            foreach ($results['details'] as $detail) {
                if (is_array($detail) && isset($detail['fixes'])) {
                    $this->line("   Recording {$detail['recording_id']} ({$detail['date']}):");
                    foreach ($detail['fixes'] as $field => $fix) {
                        $this->line("     - {$field}: {$fix['old']} → {$fix['new']}");
                    }
                }
            }
        }

        if ($results['fixed'] > 0) {
            $this->info('✅ Recording data integrity fix completed successfully!');
        } else {
            $this->warn('⚠️  No fixes were applied.');
        }
    }
}

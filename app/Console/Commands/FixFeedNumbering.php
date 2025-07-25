<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Models\FeedPurchase;
use App\Models\FeedUsage;
use App\Models\FeedMutation;
use App\Services\Feed\FeedNumberingService;
use Carbon\Carbon;

class FixFeedNumbering extends Command
{
    protected $signature = 'feed:fix-numbering 
        {--type=purchase : Jenis data yang akan difix (purchase|usage|mutation|all)}
        {--dry-run : Show what would be fixed without making changes}
        {--batch-size=100 : Number of records to process per batch}
        {--force : Force update even if numbering exists}
        {--livestock-id= : Filter by specific livestock ID}
        {--status= : Filter by specific status}
        {--date-from= : Filter by date from (Y-m-d)}
        {--date-to= : Filter by date to (Y-m-d)}
        {--export= : Export results to JSON file}
        {--reset-cache : Reset cache before processing}';

    protected $description = 'Fix missing number and number_full fields for FeedPurchase, FeedUsage, and FeedMutation records.';

    protected $typeMap = [
        'purchase' => [
            'model' => FeedPurchase::class,
            'table' => 'feed_purchases',
            'label' => 'FeedPurchase',
            'filters' => ['livestock_id', 'status', 'date'],
            'date_field' => 'date',
            'extra_fields' => ['invoice_number'],
        ],
        'usage' => [
            'model' => FeedUsage::class,
            'table' => 'feed_usages',
            'label' => 'FeedUsage',
            'filters' => ['livestock_id', 'usage_date'],
            'date_field' => 'usage_date',
            'extra_fields' => [],
        ],
        'mutation' => [
            'model' => FeedMutation::class,
            'table' => 'feed_mutations',
            'label' => 'FeedMutation',
            'filters' => ['from_livestock_id', 'to_livestock_id', 'date'],
            'date_field' => 'date',
            'extra_fields' => [],
        ],
    ];

    public function handle()
    {
        try {
            $type = $this->option('type') ?? 'purchase';
            $types = $type === 'all' ? ['purchase', 'usage', 'mutation'] : [$type];
            $allResults = [];
            $startTime = microtime(true);

            $this->info('🔧 Starting Feed Numbering Fix');
            $this->table(['Setting', 'Value'], [
                ['Type', $type],
                ['Dry Run', $this->option('dry-run') ? 'Yes' : 'No'],
                ['Batch Size', $this->option('batch-size')],
                ['Force Update', $this->option('force') ? 'Yes' : 'No'],
                ['Livestock ID Filter', $this->option('livestock-id') ?: 'All'],
                ['Status Filter', $this->option('status') ?: 'All'],
                ['Date From', $this->option('date-from') ?: 'All'],
                ['Date To', $this->option('date-to') ?: 'All'],
                ['Export Results', $this->option('export') ?: 'No'],
                ['Reset Cache', $this->option('reset-cache') ? 'Yes' : 'No'],
            ]);

            // Reset cache if requested
            if ($this->option('reset-cache')) {
                $this->info('🔄 Resetting cache before processing...');
                $numberingService = new FeedNumberingService();
                $numberingService->resetAllCache();
                $this->info('✅ Cache reset completed');
            }

            foreach ($types as $t) {
                $this->info("\n=== Processing {$t} ===");
                $results = $this->processType($t);
                $allResults[$t] = $results;
            }

            $duration = round(microtime(true) - $startTime, 2);
            $this->info("\n✅ Feed Numbering Fix completed in {$duration} seconds");

            if ($this->option('export')) {
                $this->exportResults($allResults);
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Error during numbering fix: ' . $e->getMessage());
            Log::error('Feed Numbering Fix failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    private function processType($type)
    {
        $model = $this->getModelForType($type);
        $table = $this->getTableForType($type);
        $label = $this->getLabelForType($type);
        $dateField = $this->getDateFieldForType($type);
        $extraFields = $this->getExtraFieldsForType($type);

        // Log config aktif
        $config = \App\Config\FeedNumberingConfig::getFor($table);
        $this->info("\n🔧 Active numbering config for {$table}: " . json_encode($config));

        $this->preflightChecks($model, $table);

        $query = $model::query();

        // Filters
        if ($this->option('livestock-id')) {
            if ($type === 'purchase') {
                $query->whereHas('feedPurchaseItems', function ($q) {
                    $q->where('livestock_id', $this->option('livestock-id'));
                });
            } elseif ($type === 'usage') {
                $query->where('livestock_id', $this->option('livestock-id'));
            } elseif ($type === 'mutation') {
                $query->where(function ($q) {
                    $q->where('from_livestock_id', $this->option('livestock-id'))
                        ->orWhere('to_livestock_id', $this->option('livestock-id'));
                });
            }
        }

        if ($this->option('status') && $type === 'purchase') {
            $query->where('status', $this->option('status'));
        }

        if ($this->option('date-from')) {
            $query->whereDate($dateField, '>=', $this->option('date-from'));
        }

        if ($this->option('date-to')) {
            $query->whereDate($dateField, '<=', $this->option('date-to'));
        }

        if (!$this->option('force')) {
            $query->where(function ($q) {
                $q->whereNull('number')
                    ->orWhereNull('number_full')
                    ->orWhere('number', '')
                    ->orWhere('number_full', '');
            });
        }

        $query->orderBy($dateField)->orderBy('created_at');

        $totalRecords = $query->count();

        if ($totalRecords === 0) {
            $this->warn("❌ No $label records found matching the criteria");
            return [];
        }

        $this->info("📋 Found {$totalRecords} $label records to process");

        $batchSize = (int) $this->option('batch-size');
        $processed = 0;
        $fixed = 0;
        $skipped = 0;
        $errors = 0;
        $results = [
            'total_processed' => 0,
            'total_fixed' => 0,
            'total_skipped' => 0,
            'total_errors' => 0,
            'details' => []
        ];

        $progressBar = $this->output->createProgressBar($totalRecords);
        $progressBar->start();

        $allRecords = $query->get();

        // Get config for reset rule
        $config = \App\Config\FeedNumberingConfig::getFor($table);
        $resetRule = $config['reset_rule'] ?? 'yearly';

        // Group by period based on reset rule, not individual dates
        $grouped = $allRecords->groupBy(function ($r) use ($dateField, $resetRule) {
            if (!$r->$dateField) {
                return 'unknown';
            }

            $carbon = Carbon::parse($r->$dateField);

            return match ($resetRule) {
                'daily' => $carbon->format('Y-m-d'),
                'monthly' => $carbon->format('Y-m'),
                'yearly' => $carbon->format('Y'),
                default => $carbon->format('Y'),
            };
        });

        $numberingService = new FeedNumberingService();

        foreach ($grouped as $period => $records) {
            $records = $records->sortBy('created_at');

            // Set ignore existing flag for this table to start from 1
            $numberingService->setIgnoreExisting($table, true);

            // Initialize counter for this period
            $periodCounter = 1;

            foreach ($records as $rec) {
                $processed++;
                $result = [
                    'id' => $rec->id,
                    'date' => $rec->$dateField?->format('Y-m-d') ?? 'unknown',
                    'old_number' => $rec->number,
                    'old_number_full' => $rec->number_full,
                    'action' => 'skipped',
                    'error' => null
                ];

                // Add extra fields to result for detailed reporting
                foreach ($extraFields as $field) {
                    if (isset($rec->$field)) {
                        $result[$field] = $rec->$field;
                    }
                }

                // Default changed = false, will be set true if value changes
                $result['changed'] = false;

                try {
                    // Generate new numbering using the improved method
                    $numberingData = $numberingService->generateNumberAndFull($table, $rec->$dateField?->format('Y-m-d'));

                    // Override with sequential numbering for this period
                    $number = $periodCounter;
                    $numberFull = $this->generateNumberFull($table, $number, $rec->$dateField?->format('Y-m-d'));

                    if ($this->option('dry-run')) {
                        $result['new_number'] = $number;
                        $result['new_number_full'] = $numberFull;
                        $result['action'] = 'would_fix';
                        $fixed++;
                        // Set changed true if value would change
                        if ($rec->number != $number || $rec->number_full != $numberFull) {
                            $result['changed'] = true;
                        }

                        // Show detailed before/after in dry-run mode
                        $this->line("\n📋 Record {$rec->id}:");
                        $this->line("   Date: {$rec->$dateField?->format('Y-m-d')}");
                        $this->line("   Before: number='{$rec->number}', number_full='{$rec->number_full}'");
                        $this->line("   After:  number='{$number}', number_full='{$numberFull}'");
                        $this->line("   Status: Would be fixed");
                    } else {
                        $result['new_number'] = $number;
                        $result['new_number_full'] = $numberFull;
                        $result['action'] = 'fixed';
                        $fixed++;
                        // Set changed true if value changes
                        if ($rec->number != $number || $rec->number_full != $numberFull) {
                            $result['changed'] = true;
                        }
                        $rec->number = $number;
                        $rec->number_full = $numberFull;
                        $rec->save();

                        Log::info("Fixed numbering for {$label}", [
                            'record_id' => $rec->id,
                            'old_number' => $rec->getOriginal('number'),
                            'new_number' => $number,
                            'old_number_full' => $rec->getOriginal('number_full'),
                            'new_number_full' => $numberFull,
                            'date' => $rec->$dateField?->format('Y-m-d'),
                            'changed' => $result['changed']
                        ]);
                    }

                    // Increment counter for next record in this period
                    $periodCounter++;
                } catch (\Exception $e) {
                    $result['action'] = 'error';
                    $result['error'] = $e->getMessage();
                    $errors++;

                    Log::error("Error fixing numbering for {$label}", [
                        'record_id' => $rec->id,
                        'error' => $e->getMessage(),
                        'date' => $rec->$dateField?->format('Y-m-d')
                    ]);
                }

                $results['details'][] = $result;
                $progressBar->advance();

                if ($processed % $batchSize === 0) {
                    $this->newLine();
                    $this->info("Processed {$processed}/{$totalRecords} records...");
                }
            }
        }

        $progressBar->finish();
        $this->newLine();

        $results['total_processed'] = $processed;
        $results['total_fixed'] = $fixed;
        $results['total_skipped'] = $skipped;
        $results['total_errors'] = $errors;

        $this->generateReport($results, $label, $extraFields);

        return $results;
    }

    private function preflightChecks($model, $table)
    {
        // Check if table exists
        if (!Schema::hasTable($table)) {
            throw new \Exception("Table {$table} does not exist");
        }

        // Check if required columns exist
        $requiredColumns = ['number', 'number_full'];
        foreach ($requiredColumns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                throw new \Exception("Column {$column} does not exist in table {$table}");
            }
        }

        $this->info("✅ Preflight checks passed for {$table}");
    }

    private function generateReport($results, $label, $extraFields)
    {
        $this->info("\n📊 {$label} Processing Report:");
        $this->table(['Metric', 'Count'], [
            ['Total Processed', $results['total_processed']],
            ['Fixed', $results['total_fixed']],
            ['Skipped', $results['total_skipped']],
            ['Errors', $results['total_errors']],
        ]);

        // Show detailed changes for fixed records
        if ($results['total_fixed'] > 0) {
            $this->info("\n📋 Detailed Changes:");

            $fixedRecords = array_filter($results['details'], fn($d) => $d['action'] === 'fixed' || $d['action'] === 'would_fix');

            foreach ($fixedRecords as $record) {
                $action = $record['action'] === 'would_fix' ? 'Would Fix' : 'Fixed';
                $oldNumber = $record['old_number'] ?: '(empty)';
                $oldNumberFull = $record['old_number_full'] ?: '(empty)';
                $changed = $record['old_number'] !== $record['new_number'] || $record['old_number_full'] !== $record['new_number_full'];

                $this->line("🔄 Record {$record['id']} ({$action}):");
                $this->line("   📅 Date: {$record['date']}");
                $this->line("   📊 Number: {$oldNumber} → {$record['new_number']}");
                $this->line("   🏷️  Number Full: {$oldNumberFull} → {$record['new_number_full']}");
                $this->line("   🔄 Changed: " . ($changed ? 'Yes' : 'No'));

                // Show extra fields if available
                if (!empty($extraFields)) {
                    foreach ($extraFields as $field) {
                        if (isset($record[$field])) {
                            $this->line("   📝 {$field}: {$record[$field]}");
                        }
                    }
                }
                $this->line("");
            }
        }

        if ($results['total_errors'] > 0) {
            $this->warn("\n⚠️  Errors encountered:");
            $errorDetails = array_filter($results['details'], fn($d) => $d['action'] === 'error');
            foreach (array_slice($errorDetails, 0, 5) as $error) {
                $this->error("Record {$error['id']}: {$error['error']}");
            }
            if (count($errorDetails) > 5) {
                $this->error("... and " . (count($errorDetails) - 5) . " more errors");
            }
        }

        if ($this->option('dry-run') && $results['total_fixed'] > 0) {
            $this->warn("\n💡 DRY RUN: {$results['total_fixed']} records would be fixed");
        }

        // Show summary statistics
        if ($results['total_fixed'] > 0) {
            $this->info("\n📈 Summary Statistics:");

            // Group by date to show numbering distribution
            $dateGroups = [];
            foreach ($fixedRecords as $record) {
                $date = $record['date'];
                if (!isset($dateGroups[$date])) {
                    $dateGroups[$date] = [];
                }
                $dateGroups[$date][] = $record['new_number'];
            }

            foreach ($dateGroups as $date => $numbers) {
                $minNumber = min($numbers);
                $maxNumber = max($numbers);
                $count = count($numbers);
                $this->line("   📅 {$date}: {$count} records (numbers {$minNumber}-{$maxNumber})");
            }
        }
    }

    private function exportResults($allResults)
    {
        $exportPath = $this->option('export');
        if (!$exportPath) {
            $exportPath = 'feed_numbering_fix_' . now()->format('Y-m-d_H-i-s') . '.json';
        }

        $exportData = [
            'timestamp' => now()->toISOString(),
            'command' => 'feed:fix-numbering',
            'options' => [
                'type' => $this->option('type'),
                'dry_run' => $this->option('dry-run'),
                'force' => $this->option('force'),
                'livestock_id' => $this->option('livestock-id'),
                'status' => $this->option('status'),
                'date_from' => $this->option('date-from'),
                'date_to' => $this->option('date-to'),
            ],
            'results' => $allResults
        ];

        file_put_contents($exportPath, json_encode($exportData, JSON_PRETTY_PRINT));
        $this->info("\n📁 Results exported to: {$exportPath}");
    }

    private function getModelForType($type)
    {
        if (!isset($this->typeMap[$type])) {
            throw new \Exception("Unknown type: $type");
        }
        return $this->typeMap[$type]['model'];
    }

    private function getTableForType($type)
    {
        if (!isset($this->typeMap[$type])) {
            throw new \Exception("Unknown type: $type");
        }
        return $this->typeMap[$type]['table'];
    }

    private function getLabelForType($type)
    {
        if (!isset($this->typeMap[$type])) {
            throw new \Exception("Unknown type: $type");
        }
        return $this->typeMap[$type]['label'];
    }

    private function getDateFieldForType($type)
    {
        if (!isset($this->typeMap[$type])) {
            throw new \Exception("Unknown type: $type");
        }
        return $this->typeMap[$type]['date_field'];
    }

    private function getExtraFieldsForType($type)
    {
        if (!isset($this->typeMap[$type])) {
            throw new \Exception("Unknown type: $type");
        }
        return $this->typeMap[$type]['extra_fields'];
    }

    /**
     * Generate number_full string based on config and number
     */
    private function generateNumberFull(string $table, int $number, string $date): string
    {
        $config = \App\Config\FeedNumberingConfig::getFor($table);
        if (empty($config) || empty($config['enabled'])) {
            throw new \Exception("Numbering not enabled for table: $table");
        }

        $format = $config['format'] ?? '{prefix}-{urut:3}/{TGL}/{BLN}/{THN}';
        $prefix = $config['prefix'] ?? '';
        $padding = $config['number_padding'] ?? 3;
        $carbon = Carbon::parse($date);

        // Build number string with padding
        $urut = str_pad($number, $padding, '0', STR_PAD_LEFT);

        // Generate full number - handle {urut:3} format properly
        $numberFull = $format;

        // Replace {urut:3} pattern with actual number
        $numberFull = preg_replace('/\{urut:(\d+)\}/', $urut, $numberFull);

        // Replace other placeholders
        $numberFull = strtr($numberFull, [
            '{prefix}' => $prefix,
            '{urut}' => $urut,
            '{TGL}' => $carbon->format('d'),
            '{BLN}' => $carbon->format('m'),
            '{THN}' => $carbon->format('Y'),
        ]);

        return $numberFull;
    }
}

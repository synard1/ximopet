<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Models\SupplyPurchaseBatch;
use App\Models\SupplyMutation;
use App\Models\SupplyUsage;
use App\Services\Supply\SupplyNumberGeneratorService;
use Carbon\Carbon;

class FixSupplyNumbering extends Command
{
    protected $signature = 'supply:fix-numbering 
        {--type=purchase : Jenis data yang akan difix (purchase|mutation|usage|all)}
        {--dry-run : Show what would be fixed without making changes}
        {--batch-size=100 : Number of records to process per batch}
        {--force : Force update even if numbering exists}
        {--farm-id= : Filter by specific farm ID}
        {--status= : Filter by specific status}
        {--date-from= : Filter by date from (Y-m-d)}
        {--date-to= : Filter by date to (Y-m-d)}
        {--export= : Export results to JSON file}
        {--fix-available : Recalculate quantity_available for all SupplyStock records (with optional filters)}';

    protected $description = 'Fix missing number and number_full fields for SupplyPurchaseBatch, SupplyMutation, and SupplyUsage records. Optionally recalculate quantity_available for SupplyStock.';

    protected $typeMap = [
        'purchase' => [
            'model' => \App\Models\SupplyPurchaseBatch::class,
            'table' => 'supply_purchase_batches',
            'label' => 'SupplyPurchaseBatch',
            'filters' => ['farm_id', 'status', 'date'],
            'date_field' => 'date',
            'extra_fields' => ['invoice_number'],
        ],
        'mutation' => [
            'model' => \App\Models\SupplyMutation::class,
            'table' => 'supply_mutations',
            'label' => 'SupplyMutation',
            'filters' => ['from_farm_id', 'to_farm_id', 'status', 'date'],
            'date_field' => 'date',
            'extra_fields' => ['mutation_id'],
        ],
        'usage' => [
            'model' => \App\Models\SupplyUsage::class,
            'table' => 'supply_usages',
            'label' => 'SupplyUsage',
            'filters' => ['farm_id', 'status', 'usage_date'],
            'date_field' => 'usage_date',
            'extra_fields' => [],
        ],
    ];

    public function handle()
    {
        try {
            if ($this->option('fix-available')) {
                $this->fixSupplyStockAvailable();
                return 0;
            }

            $type = $this->option('type') ?? 'purchase';
            $types = $type === 'all' ? ['purchase', 'mutation', 'usage'] : [$type];
            $allResults = [];
            $startTime = microtime(true);

            $this->info('🔧 Starting Supply Numbering Fix');
            $this->table(['Setting', 'Value'], [
                ['Type', $type],
                ['Dry Run', $this->option('dry-run') ? 'Yes' : 'No'],
                ['Batch Size', $this->option('batch-size')],
                ['Force Update', $this->option('force') ? 'Yes' : 'No'],
                ['Farm ID Filter', $this->option('farm-id') ?: 'All'],
                ['Status Filter', $this->option('status') ?: 'All'],
                ['Date From', $this->option('date-from') ?: 'All'],
                ['Date To', $this->option('date-to') ?: 'All'],
                ['Export Results', $this->option('export') ?: 'No'],
            ]);

            foreach ($types as $t) {
                $this->info("\n=== Processing {$t} ===");
                $results = $this->processType($t);
                $allResults[$t] = $results;
            }

            $duration = round(microtime(true) - $startTime, 2);
            $this->info("\n✅ Supply Numbering Fix completed in {$duration} seconds");

            if ($this->option('export')) {
                $this->exportResults($allResults);
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Error during numbering fix: ' . $e->getMessage());
            Log::error('Supply Numbering Fix failed', [
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
        if (!isset($this->typeMap[$type])) {
            $this->error("Unknown type: $type");
            return [];
        }

        $meta = $this->typeMap[$type];
        $model = $meta['model'];
        $table = $meta['table'];
        $label = $meta['label'];
        $dateField = $meta['date_field'];
        $extraFields = $meta['extra_fields'];

        $this->preflightChecks($model, $table);

        $query = $model::query();

        // Filters
        if ($this->option('farm-id')) {
            if ($type === 'purchase') {
                $query->where('farm_id', $this->option('farm-id'));
            } else {
                $query->where(function ($q) {
                    $q->where('from_farm_id', $this->option('farm-id'))
                        ->orWhere('to_farm_id', $this->option('farm-id'));
                });
            }
        }

        if ($this->option('status')) {
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
        $grouped = $allRecords->groupBy(fn($r) => $r->$dateField ? Carbon::parse($r->$dateField)->format('Y-m-d') : ($r->created_at ? Carbon::parse($r->created_at)->format('Y-m-d') : 'unknown'));

        foreach ($grouped as $date => $records) {
            $maxNumber = $model::whereDate($dateField, $date)
                ->whereNotNull('number')
                ->max('number') ?? 0;
            $urut = $maxNumber;
            $records = $records->sortBy('created_at');

            foreach ($records as $rec) {
                $processed++;
                $result = [
                    'id' => $rec->id,
                    'date' => $rec->$dateField?->format('Y-m-d'),
                    'status' => $rec->status,
                    'old_number' => $rec->number,
                    'old_number_full' => $rec->number_full,
                    'new_number' => null,
                    'new_number_full' => null,
                    'status_label' => $label,
                    'status_process' => 'skipped',
                    'message' => 'No changes needed',
                ];

                foreach ($extraFields as $f) {
                    $result[$f] = $rec->$f ?? null;
                }

                try {
                    $needsNumbering = $this->option('force') || empty($rec->number) || empty($rec->number_full);
                    if (!$needsNumbering) {
                        $skipped++;
                        $results['details'][] = $result;
                        $progressBar->advance();
                        continue;
                    }

                    $urut++;
                    $numbering = SupplyNumberGeneratorService::generateNumber(
                        $table,
                        $rec->$dateField ?? $rec->created_at,
                        []
                    );

                    $config = \App\Config\SupplyNumberingConfig::getFor($table);
                    $format = $config['format'] ?? '{prefix}-{urut:5}/{TGL}/{BLN}/{THN}';
                    $dateObj = $rec->$dateField ?? $rec->created_at;
                    $dateObj = $dateObj ? Carbon::parse($dateObj) : now();

                    $placeholders = [
                        '{TGL}' => $dateObj->format('d'),
                        '{BLN}' => $dateObj->format('m'),
                        '{THN}' => $dateObj->format('Y'),
                    ];

                    if (!empty($config['prefix'])) {
                        $placeholders['{prefix}'] = $config['prefix'];
                    }

                    $fullNumber = $format;
                    $fullNumber = str_replace(array_keys($placeholders), array_values($placeholders), $fullNumber);
                    $padding = $config['number_padding'] ?? 5;
                    $fullNumber = preg_replace_callback('/\{urut:(\d+)\}/', function ($m) use ($urut) {
                        return str_pad($urut, (int)$m[1], '0', STR_PAD_LEFT);
                    }, $fullNumber);
                    $fullNumber = str_replace(['{urut}'], [$urut], $fullNumber);

                    $numbering['number'] = $urut;
                    $numbering['full_number'] = $fullNumber;

                    $result['new_number'] = $urut;
                    $result['new_number_full'] = $fullNumber;

                    if (!$this->option('dry-run')) {
                        $rec->number = $urut;
                        $rec->number_full = $fullNumber;
                        $rec->save();

                        Log::info("Fixed $label numbering", [
                            'id' => $rec->id,
                            'date' => $date,
                            'new_number' => $urut,
                            'new_number_full' => $fullNumber
                        ]);
                    }

                    $fixed++;
                    $result['status_process'] = 'fixed';
                    $result['message'] = 'Numbering generated successfully (unique per date)';
                } catch (\Exception $e) {
                    $errors++;
                    $result['status_process'] = 'error';
                    $result['message'] = $e->getMessage();

                    Log::error("Failed to fix $label numbering", [
                        'id' => $rec->id,
                        'date' => $date,
                        'error' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine()
                    ]);
                }

                $results['details'][] = $result;
                $progressBar->advance();
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
        $this->info("🔍 Pre-flight checks for $table...");

        if (!class_exists($model)) {
            throw new \Exception("Model not found: $model");
        }

        if (!Schema::hasTable($table)) {
            throw new \Exception("Table not found: $table");
        }

        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            throw new \Exception('Database connection failed: ' . $e->getMessage());
        }

        $this->info('✅ Pre-flight checks passed');
    }

    private function generateReport($results, $label, $extraFields)
    {
        $this->newLine();
        $this->info("📊 Processing Report for $label");

        $this->table(['Metric', 'Count'], [
            ['Total Processed', $results['total_processed']],
            ['Fixed', $results['total_fixed']],
            ['Skipped', $results['total_skipped']],
            ['Errors', $results['total_errors']],
        ]);

        $fixedRecords = array_filter($results['details'], fn($r) => $r['status_process'] === 'fixed');
        if (!empty($fixedRecords)) {
            $this->newLine();
            $this->info('✅ Sample of Fixed Records');
            $sampleData = array_slice($fixedRecords, 0, 5);

            $headers = array_merge(['ID', 'Date', 'Old Number', 'New Number', 'New Full Number'], array_map('ucfirst', $extraFields));

            $this->table(
                $headers,
                array_map(function ($record) use ($extraFields) {
                    $row = [
                        substr($record['id'], 0, 8) . '...',
                        $record['date'],
                        $record['old_number'] ?: 'NULL',
                        $record['new_number'],
                        $record['new_number_full']
                    ];

                    foreach ($extraFields as $f) {
                        $row[] = $record[$f] ?? '';
                    }

                    return $row;
                }, $sampleData)
            );
        }

        $errorRecords = array_filter($results['details'], fn($r) => $r['status_process'] === 'error');
        if (!empty($errorRecords)) {
            $this->newLine();
            $this->warn('⚠️ Sample of Errors');
            $sampleErrors = array_slice($errorRecords, 0, 3);

            $this->table(
                ['ID', 'Date', 'Error'],
                array_map(function ($record) {
                    return [
                        substr($record['id'], 0, 8) . '...',
                        $record['date'],
                        substr($record['message'], 0, 50) . '...'
                    ];
                }, $sampleErrors)
            );
        }
    }

    private function exportResults($allResults)
    {
        $exportPath = $this->option('export');

        try {
            $exportData = [
                'command' => 'supply:fix-numbering',
                'executed_at' => now()->toISOString(),
                'options' => [
                    'dry_run' => $this->option('dry-run'),
                    'batch_size' => $this->option('batch-size'),
                    'force' => $this->option('force'),
                    'type' => $this->option('type'),
                ],
                'results' => $allResults,
            ];

            \Illuminate\Support\Facades\File::put($exportPath, json_encode($exportData, JSON_PRETTY_PRINT));
            $this->info("\n📤 Results exported to $exportPath");
        } catch (\Exception $e) {
            $this->error("Failed to export results: " . $e->getMessage());
        }
    }

    /**
     * Recalculate quantity_available for SupplyStock
     */
    private function fixSupplyStockAvailable()
    {
        $this->info('🔧 Starting SupplyStock quantity_available recalculation');
        $query = \App\Models\SupplyStock::query();
        if ($this->option('farm-id')) {
            $query->where('farm_id', $this->option('farm-id'));
        }
        if ($this->option('supply-id')) {
            $query->where('supply_id', $this->option('supply-id'));
        }
        $total = $query->count();
        if ($total === 0) {
            $this->warn('No SupplyStock records found matching the criteria');
            return;
        }
        $this->info("Found $total SupplyStock records to process");
        $bar = $this->output->createProgressBar($total);
        $bar->start();
        $fixed = 0;
        $skipped = 0;
        $errors = 0;
        $details = [];
        $query->chunk(100, function ($stocks) use (&$fixed, &$skipped, &$errors, &$details, $bar) {
            foreach ($stocks as $stock) {
                try {
                    $old = $stock->quantity_available;
                    $new = $stock->quantity_in - $stock->quantity_used - $stock->quantity_mutated - $stock->quantity_reserved;
                    if ($old !== $new) {
                        $details[] = [
                            'id' => $stock->id,
                            'old' => $old,
                            'new' => $new,
                        ];
                        if (!$this->option('dry-run')) {
                            $stock->quantity_available = $new;
                            $stock->save();
                        }
                        $fixed++;
                    } else {
                        $skipped++;
                    }
                } catch (\Exception $e) {
                    $errors++;
                    $this->error("Error on stock {$stock->id}: {$e->getMessage()}");
                }
                $bar->advance();
            }
        });
        $bar->finish();
        $this->newLine();
        $this->info("\nRecalculation complete. Fixed: $fixed, Skipped: $skipped, Errors: $errors");
        if (!empty($details)) {
            $this->table(['ID', 'Old Available', 'New Available'], $details);
        }
    }
}

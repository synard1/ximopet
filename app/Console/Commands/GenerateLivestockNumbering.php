<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LivestockPurchase;
use App\Models\Livestock;
use App\Models\LivestockBatch;
use App\Models\LivestockMutation;
use App\Models\LivestockDepletion;
use App\Services\Livestock\LivestockNumberGeneratorService;
use Illuminate\Support\Facades\Log;

class GenerateLivestockNumbering extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'livestock:generate-numbering 
                            {--table= : Specific table to process (livestock_purchases, livestocks, livestock_batches, livestock_mutations, livestock_depletions)}
                            {--date= : Specific date to process (YYYY-MM-DD)}
                            {--dry-run : Show what would be done without making changes}
                            {--force : Force regeneration of existing numbers}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate automatic numbering for existing livestock records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting Livestock Numbering Generation...');

        $table = $this->option('table');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        }

        $tables = $table ? [$table] : [
            'livestock_purchases',
            'livestocks',
            'livestock_batches',
            'livestock_mutations',
            'livestock_depletions'
        ];

        $totalProcessed = 0;
        $totalGenerated = 0;
        $totalErrors = 0;

        foreach ($tables as $tableName) {
            $this->info("\n📋 Processing table: {$tableName}");

            try {
                $result = $this->processTable($tableName, $dryRun, $force);
                $totalProcessed += $result['processed'];
                $totalGenerated += $result['generated'];
                $totalErrors += $result['errors'];

                $this->info("✅ {$tableName}: {$result['processed']} processed, {$result['generated']} generated, {$result['errors']} errors");
            } catch (\Exception $e) {
                $this->error("❌ Error processing {$tableName}: " . $e->getMessage());
                $totalErrors++;
            }
        }

        $this->info("\n🎯 SUMMARY:");
        $this->info("Total processed: {$totalProcessed}");
        $this->info("Total generated: {$totalGenerated}");
        $this->info("Total errors: {$totalErrors}");

        if ($dryRun) {
            $this->warn("\n💡 Run without --dry-run to apply changes");
        } else {
            $this->info("\n✅ Numbering generation completed!");
        }

        return 0;
    }

    /**
     * Process a specific table
     */
    private function processTable(string $tableName, bool $dryRun, bool $force): array
    {
        $model = $this->getModelForTable($tableName);
        if (!$model) {
            throw new \Exception("Unknown table: {$tableName}");
        }

        $query = $model::query();

        if (!$force) {
            $query->whereNull('number')->orWhereNull('number_full');
        }

        $records = $query->get();
        $processed = 0;
        $generated = 0;
        $errors = 0;

        $this->info("Found {$records->count()} records to process");

        $progressBar = $this->output->createProgressBar($records->count());
        $progressBar->start();

        foreach ($records as $record) {
            try {
                $processed++;

                // Get date for numbering context
                $date = $this->getDateFromRecord($record, $tableName);

                // Generate numbering
                $numbering = LivestockNumberGeneratorService::generateNumber($tableName, $date);

                if ($numbering && !empty($numbering['number']) && !empty($numbering['full_number'])) {
                    if (!$dryRun) {
                        $record->number = $numbering['number'];
                        $record->number_full = $numbering['full_number'];
                        $record->save();

                        Log::info("Generated numbering for {$tableName}", [
                            'record_id' => $record->id,
                            'number' => $record->number,
                            'number_full' => $record->number_full,
                            'date' => $date
                        ]);
                    }

                    $generated++;
                } else {
                    $this->warn("\n⚠️  Failed to generate numbering for record {$record->id}");
                    $errors++;
                }
            } catch (\Exception $e) {
                $this->error("\n❌ Error processing record {$record->id}: " . $e->getMessage());
                $errors++;
                Log::error("Error generating numbering for {$tableName}", [
                    'record_id' => $record->id,
                    'error' => $e->getMessage()
                ]);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        return [
            'processed' => $processed,
            'generated' => $generated,
            'errors' => $errors
        ];
    }

    /**
     * Get model class for table name
     */
    private function getModelForTable(string $tableName): ?string
    {
        $modelMap = [
            'livestock_purchases' => LivestockPurchase::class,
            'livestocks' => Livestock::class,
            'livestock_batches' => LivestockBatch::class,
            'livestock_mutations' => LivestockMutation::class,
            'livestock_depletions' => LivestockDepletion::class,
        ];

        return $modelMap[$tableName] ?? null;
    }

    /**
     * Get date from record for numbering context
     */
    private function getDateFromRecord($record, string $tableName): string
    {
        // Try different date fields based on table
        $dateFields = [
            'tanggal',
            'date',
            'created_at',
            'start_date',
            'purchase_date',
            'mutation_date',
            'sale_date',
            'depletion_date'
        ];

        foreach ($dateFields as $field) {
            if (isset($record->$field) && !empty($record->$field)) {
                return $record->$field;
            }
        }

        // Fallback to created_at
        return $record->created_at ?? now();
    }
}

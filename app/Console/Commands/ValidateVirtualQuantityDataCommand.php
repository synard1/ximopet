<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

use App\Models\LivestockBatch;
use App\Models\RecordingSale;
use App\Models\RecordingSaleItem;

use function App\Helpers\logInfoIfDebug;
use function App\Helpers\logDebugIfDebug;

class ValidateVirtualQuantityDataCommand extends Command
{
    protected $signature = 'validate:virtual-quantity 
                            {--livestock-id= : Specific livestock ID to validate}
                            {--date= : Specific date to validate (Y-m-d format)}
                            {--fix : Fix inconsistencies automatically}
                            {--detailed : Show detailed validation results}';

    protected $description = 'Validate virtual quantity data consistency';

    public function handle()
    {
        $livestockId = $this->option('livestock-id');
        $date = $this->option('date');
        $fix = $this->option('fix');
        $detailed = $this->option('detailed');

        $this->info("🔍 Validating Virtual Quantity Data");
        $this->info("================================");
        $this->info("Livestock ID: " . ($livestockId ?: 'ALL'));
        $this->info("Date: " . ($date ?: 'ALL'));
        $this->info("Fix Mode: " . ($fix ? 'YES' : 'NO'));
        $this->info("Detailed: " . ($detailed ? 'YES' : 'NO'));
        $this->info("");

        // Validate configuration
        $this->validateConfiguration();

        // Get data to validate
        $validationData = $this->getValidationData($livestockId, $date);

        if ($validationData->isEmpty()) {
            $this->warn("⚠️ No data found to validate");
            return 0;
        }

        $this->info("📊 Found {$validationData->count()} records to validate");
        $this->info("");

        // Perform validation
        $validationResult = $this->performValidation($validationData, $detailed);

        // Show validation summary
        $this->showValidationSummary($validationResult);

        // Fix inconsistencies if requested
        if ($fix && $validationResult['issues_count'] > 0) {
            $this->fixInconsistencies($validationResult['issues']);
        }

        return 0;
    }

    /**
     * Validate that virtual calculation is enabled
     */
    private function validateConfiguration(): void
    {
        $salesConfig = \App\Config\CompanyConfig::getSalesConfig();
        $quantityCalculation = $salesConfig['quantity_calculation'] ?? [];
        $isVirtualMode = ($quantityCalculation['mode'] ?? 'real') === 'virtual';

        if (!$isVirtualMode) {
            $this->error("❌ Virtual quantity calculation is not enabled in configuration");
            $this->error("Please enable virtual mode in CompanyConfig::getDefaultSalesConfig()");
            exit(1);
        }

        $this->info("✅ Virtual quantity calculation is enabled");
    }

    /**
     * Get data for validation
     */
    private function getValidationData(?string $livestockId, ?string $date)
    {
        $query = RecordingSale::with(['items.batch'])
            ->where('is_header', true)
            ->where('status', 'draft');

        if ($livestockId) {
            $query->where('livestock_id', $livestockId);
        }

        if ($date) {
            $query->where('date', $date);
        }

        return $query->get();
    }

    /**
     * Perform validation
     */
    private function performValidation($records, bool $detailed): array
    {
        $totalRecords = $records->count();
        $validRecords = 0;
        $invalidRecords = 0;
        $issues = [];

        $this->info("🔍 Starting validation process...");
        $this->info("");

        $progressBar = $this->output->createProgressBar($totalRecords);
        $progressBar->start();

        foreach ($records as $record) {
            $recordValidation = $this->validateRecord($record, $detailed);

            if ($recordValidation['valid']) {
                $validRecords++;
            } else {
                $invalidRecords++;
                $issues[] = $recordValidation['issues'];
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->info("");
        $this->info("");

        return [
            'total_records' => $totalRecords,
            'valid_records' => $validRecords,
            'invalid_records' => $invalidRecords,
            'issues_count' => count($issues),
            'issues' => $issues
        ];
    }

    /**
     * Validate a single record
     */
    private function validateRecord(RecordingSale $record, bool $detailed): array
    {
        $issues = [];
        $isValid = true;

        // Get sales items
        $items = $record->items;
        if ($items->isEmpty()) {
            $issues[] = [
                'type' => 'no_items',
                'message' => 'No sales items found',
                'severity' => 'error'
            ];
            $isValid = false;
        }

        // Validate virtual data consistency
        $virtualDataValidation = $this->validateVirtualDataConsistency($record, $items);
        if (!$virtualDataValidation['valid']) {
            $issues = array_merge($issues, $virtualDataValidation['issues']);
            $isValid = false;
        }

        // Validate batch data integrity
        $batchValidation = $this->validateBatchDataIntegrity($record, $items);
        if (!$batchValidation['valid']) {
            $issues = array_merge($issues, $batchValidation['issues']);
            $isValid = false;
        }

        // Validate metadata consistency
        $metadataValidation = $this->validateMetadataConsistency($record);
        if (!$metadataValidation['valid']) {
            $issues = array_merge($issues, $metadataValidation['issues']);
            $isValid = false;
        }

        if ($detailed && !empty($issues)) {
            $this->showDetailedIssues($record, $issues);
        }

        return [
            'valid' => $isValid,
            'issues' => [
                'record_id' => $record->id,
                'livestock_id' => $record->livestock_id,
                'date' => $record->date,
                'issues' => $issues
            ]
        ];
    }

    /**
     * Validate virtual data consistency
     */
    private function validateVirtualDataConsistency(RecordingSale $record, $items): array
    {
        $issues = [];
        $isValid = true;

        $expectedQuantity = $record->total_quantity;
        $expectedWeight = $record->total_weight;
        $actualVirtualQuantity = 0;
        $actualVirtualWeight = 0;

        foreach ($items as $item) {
            $batch = $item->batch;
            if (!$batch) {
                $issues[] = [
                    'type' => 'missing_batch',
                    'message' => "Batch not found for item {$item->id}",
                    'severity' => 'error',
                    'item_id' => $item->id
                ];
                $isValid = false;
                continue;
            }

            $batchData = $batch->data ?? [];
            $dateKey = $record->date instanceof \Carbon\Carbon ? $record->date->format('Y-m-d') : (string) $record->date;
            $virtualSales = $batchData['virtual_sales'][$dateKey] ?? null;

            if (!$virtualSales) {
                $issues[] = [
                    'type' => 'missing_virtual_data',
                    'message' => "Virtual sales data missing for batch {$batch->id} on {$record->date}",
                    'severity' => 'error',
                    'batch_id' => $batch->id,
                    'date' => $record->date
                ];
                $isValid = false;
            } else {
                $actualVirtualQuantity += (int) ($virtualSales['quantity'] ?? 0);
                $actualVirtualWeight += (float) ($virtualSales['weight'] ?? 0);

                // Validate item vs virtual data consistency
                if ($virtualSales['quantity'] != $item->quantity) {
                    $issues[] = [
                        'type' => 'quantity_mismatch',
                        'message' => "Quantity mismatch: item shows {$item->quantity}, virtual shows {$virtualSales['quantity']}",
                        'severity' => 'warning',
                        'batch_id' => $batch->id,
                        'item_quantity' => $item->quantity,
                        'virtual_quantity' => $virtualSales['quantity']
                    ];
                    $isValid = false;
                }

                if (abs($virtualSales['weight'] - $item->weight) > 0.01) {
                    $issues[] = [
                        'type' => 'weight_mismatch',
                        'message' => "Weight mismatch: item shows {$item->weight}, virtual shows {$virtualSales['weight']}",
                        'severity' => 'warning',
                        'batch_id' => $batch->id,
                        'item_weight' => $item->weight,
                        'virtual_weight' => $virtualSales['weight']
                    ];
                    $isValid = false;
                }
            }
        }

        // Validate totals
        if ($actualVirtualQuantity != $expectedQuantity) {
            $issues[] = [
                'type' => 'total_quantity_mismatch',
                'message' => "Total quantity mismatch: record shows {$expectedQuantity}, virtual total {$actualVirtualQuantity}",
                'severity' => 'error',
                'expected_quantity' => $expectedQuantity,
                'actual_quantity' => $actualVirtualQuantity
            ];
            $isValid = false;
        }

        if (abs($actualVirtualWeight - $expectedWeight) > 0.01) {
            $issues[] = [
                'type' => 'total_weight_mismatch',
                'message' => "Total weight mismatch: record shows {$expectedWeight}, virtual total {$actualVirtualWeight}",
                'severity' => 'error',
                'expected_weight' => $expectedWeight,
                'actual_weight' => $actualVirtualWeight
            ];
            $isValid = false;
        }

        return ['valid' => $isValid, 'issues' => $issues];
    }

    /**
     * Validate batch data integrity
     */
    private function validateBatchDataIntegrity(RecordingSale $record, $items): array
    {
        $issues = [];
        $isValid = true;

        foreach ($items as $item) {
            $batch = $item->batch;
            if (!$batch) {
                continue;
            }

            // Check if batch has real stock impact (should not in virtual mode)
            if ($batch->quantity_sales > 0) {
                $issues[] = [
                    'type' => 'real_stock_impact',
                    'message' => "Batch {$batch->id} has real stock impact (quantity_sales: {$batch->quantity_sales})",
                    'severity' => 'warning',
                    'batch_id' => $batch->id,
                    'quantity_sales' => $batch->quantity_sales
                ];
                $isValid = false;
            }

            // Check if batch data structure is valid
            $batchData = $batch->data ?? [];
            if (!isset($batchData['virtual_sales'])) {
                $issues[] = [
                    'type' => 'missing_virtual_structure',
                    'message' => "Batch {$batch->id} missing virtual_sales structure in data column",
                    'severity' => 'error',
                    'batch_id' => $batch->id
                ];
                $isValid = false;
            }
        }

        return ['valid' => $isValid, 'issues' => $issues];
    }

    /**
     * Validate metadata consistency
     */
    private function validateMetadataConsistency(RecordingSale $record): array
    {
        $issues = [];
        $isValid = true;

        $metadata = $record->metadata ?? [];

        // Check if virtual mode is properly set
        if (!isset($metadata['virtual_mode']) || !$metadata['virtual_mode']) {
            $issues[] = [
                'type' => 'missing_virtual_mode',
                'message' => 'Virtual mode not set in metadata',
                'severity' => 'warning'
            ];
            $isValid = false;
        }

        // Check if calculation mode is set
        if (!isset($metadata['calculation_mode']) || $metadata['calculation_mode'] !== 'virtual') {
            $calculationMode = $metadata['calculation_mode'] ?? 'not_set';
            $issues[] = [
                'type' => 'incorrect_calculation_mode',
                'message' => "Calculation mode should be 'virtual', got '{$calculationMode}'",
                'severity' => 'warning'
            ];
            $isValid = false;
        }

        return ['valid' => $isValid, 'issues' => $issues];
    }

    /**
     * Show detailed issues for a record
     */
    private function showDetailedIssues(RecordingSale $record, array $issues): void
    {
        $this->line("📋 Issues for record {$record->id} ({$record->livestock_id} - {$record->date}):");

        foreach ($issues as $issue) {
            $severityIcon = $issue['severity'] === 'error' ? '❌' : '⚠️';
            $this->line("   {$severityIcon} {$issue['message']}");
        }
        $this->line("");
    }

    /**
     * Show validation summary
     */
    private function showValidationSummary(array $result): void
    {
        $this->info("📊 Validation Summary");
        $this->info("===================");
        $this->info("Total Records: {$result['total_records']}");
        $this->info("Valid Records: {$result['valid_records']}");
        $this->info("Invalid Records: {$result['invalid_records']}");
        $this->info("Total Issues: {$result['issues_count']}");
        $this->info("");

        if ($result['valid_records'] > 0) {
            $this->info("✅ {$result['valid_records']} records are valid");
        }

        if ($result['invalid_records'] > 0) {
            $this->error("❌ {$result['invalid_records']} records have issues");
        }

        if ($result['issues_count'] > 0) {
            $this->warn("⚠️ {$result['issues_count']} issues found");
        }
    }

    /**
     * Fix inconsistencies
     */
    private function fixInconsistencies(array $issues): void
    {
        $this->info("🔧 Fixing inconsistencies...");
        $this->info("");

        $fixedCount = 0;
        $errorCount = 0;

        foreach ($issues as $issueGroup) {
            $recordId = $issueGroup['record_id'];
            $recordIssues = $issueGroup['issues'];

            try {
                $fixResult = $this->fixRecordIssues($recordId, $recordIssues);
                if ($fixResult['success']) {
                    $fixedCount++;
                    $this->info("✅ Fixed issues for record {$recordId}");
                } else {
                    $errorCount++;
                    $this->error("❌ Failed to fix record {$recordId}: {$fixResult['error']}");
                }
            } catch (Exception $e) {
                $errorCount++;
                $this->error("❌ Error fixing record {$recordId}: {$e->getMessage()}");
            }
        }

        $this->info("");
        $this->info("🔧 Fix Summary:");
        $this->info("Fixed: {$fixedCount}");
        $this->info("Errors: {$errorCount}");
    }

    /**
     * Fix issues for a specific record
     */
    private function fixRecordIssues(string $recordId, array $issues): array
    {
        try {
            $record = RecordingSale::find($recordId);
            if (!$record) {
                return ['success' => false, 'error' => 'Record not found'];
            }

            return DB::transaction(function () use ($record, $issues) {
                foreach ($issues as $issue) {
                    switch ($issue['type']) {
                        case 'missing_virtual_data':
                            $this->fixMissingVirtualData($record, $issue);
                            break;
                        case 'quantity_mismatch':
                        case 'weight_mismatch':
                            $this->fixDataMismatch($record, $issue);
                            break;
                        case 'missing_virtual_mode':
                        case 'incorrect_calculation_mode':
                            $this->fixMetadata($record, $issue);
                            break;
                    }
                }

                return ['success' => true, 'error' => null];
            });
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Fix missing virtual data
     */
    private function fixMissingVirtualData(RecordingSale $record, array $issue): void
    {
        $batchId = $issue['batch_id'] ?? null;
        if (!$batchId) {
            return;
        }

        $batch = LivestockBatch::find($batchId);
        if (!$batch) {
            return;
        }

        // Find corresponding item
        $item = $record->items->where('livestock_batch_id', $batchId)->first();
        if (!$item) {
            return;
        }

        $existingData = $batch->data ?? [];
        if (!isset($existingData['virtual_sales'])) {
            $existingData['virtual_sales'] = [];
        }

        // Create virtual data from item
        $dateKey = $record->date instanceof \Carbon\Carbon ? $record->date->format('Y-m-d') : (string) $record->date;
        $existingData['virtual_sales'][$dateKey] = [
            'quantity' => $item->quantity,
            'weight' => $item->weight,
            'date' => $record->date,
            'status' => 'draft',
            'metadata' => [
                'calculation_method' => 'fix_missing_virtual_data',
                'calculation_timestamp' => now()->toIso8601String(),
                'original_item_id' => $item->id,
                'price_per_unit' => $item->price_per_unit,
                'amount' => $item->amount,
            ]
        ];

        $batch->update([
            'data' => $existingData,
            'updated_at' => now()
        ]);
    }

    /**
     * Fix data mismatch
     */
    private function fixDataMismatch(RecordingSale $record, array $issue): void
    {
        $batchId = $issue['batch_id'] ?? null;
        if (!$batchId) {
            return;
        }

        $batch = LivestockBatch::find($batchId);
        if (!$batch) {
            return;
        }

        $item = $record->items->where('livestock_batch_id', $batchId)->first();
        if (!$item) {
            return;
        }

        $existingData = $batch->data ?? [];
        $dateKey = $record->date instanceof \Carbon\Carbon ? $record->date->format('Y-m-d') : (string) $record->date;
        if (isset($existingData['virtual_sales'][$dateKey])) {
            // Update virtual data to match item data
            $existingData['virtual_sales'][$dateKey]['quantity'] = $item->quantity;
            $existingData['virtual_sales'][$dateKey]['weight'] = $item->weight;
            $existingData['virtual_sales'][$dateKey]['metadata']['fixed_at'] = now()->toIso8601String();
            $existingData['virtual_sales'][$dateKey]['metadata']['fix_reason'] = $issue['type'];

            $batch->update([
                'data' => $existingData,
                'updated_at' => now()
            ]);
        }
    }

    /**
     * Fix metadata
     */
    private function fixMetadata(RecordingSale $record, array $issue): void
    {
        $metadata = $record->metadata ?? [];

        if ($issue['type'] === 'missing_virtual_mode') {
            $metadata['virtual_mode'] = true;
        }

        if ($issue['type'] === 'incorrect_calculation_mode') {
            $metadata['calculation_mode'] = 'virtual';
        }

        $metadata['fixed_at'] = now()->toIso8601String();
        $metadata['fix_reason'] = $issue['type'];

        $record->update([
            'metadata' => $metadata,
            'updated_at' => now()
        ]);
    }
}

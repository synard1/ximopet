<?php

namespace App\Console\Commands;

use App\Helpers\DataChangeDetector;
use Illuminate\Console\Command;

class TestLivestockPurchaseBypass extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'livestock:test-bypass {--scenario= : Test scenario to run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the bypass logic specifically for LivestockPurchase component';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $scenario = $this->option('scenario') ?? 'all';

        $this->info('🧪 Testing LivestockPurchase Bypass Logic...\n');

        switch ($scenario) {
            case 'no-changes':
                $this->testNoChanges();
                break;
            case 'with-changes':
                $this->testWithChanges();
                break;
            case 'items-changes':
                $this->testItemsChanges();
                break;
            case 'all':
            default:
                $this->testNoChanges();
                $this->testWithChanges();
                $this->testItemsChanges();
                break;
        }

        $this->info('\n🎉 LivestockPurchase Bypass Test Completed!');
        return 0;
    }

    /**
     * Test scenario: No changes detected
     */
    private function testNoChanges(): void
    {
        $this->info('1. Testing No Changes Scenario...');

        $originalData = [
            'invoice_number' => 'INV-001',
            'date' => '2025-08-12',
            'supplier_id' => 'supplier-123',
            'farm_id' => 'farm-456',
            'coop_id' => 'coop-789',
            'expedition_id' => 'expedition-123',
            'expedition_fee' => '1000000',
            'batch_name' => 'BATCH-001',
            'status' => 'draft',
            'items' => [
                ['strain_id' => 'strain-1', 'quantity' => 100, 'price' => 1000, 'weight_value' => 50, 'weight_type' => 'per_unit'],
                ['strain_id' => 'strain-2', 'quantity' => 200, 'price' => 2000, 'weight_value' => 100, 'weight_type' => 'per_unit']
            ]
        ];

        $currentData = [
            'invoice_number' => 'INV-001',
            'date' => '2025-08-12',
            'supplier_id' => 'supplier-123',
            'farm_id' => 'farm-456',
            'coop_id' => 'coop-789',
            'expedition_id' => 'expedition-123',
            'expedition_fee' => '1000000',
            'batch_name' => 'BATCH-001',
            'status' => 'draft',
            'items' => [
                ['strain_id' => 'strain-1', 'quantity' => 100, 'price' => 1000, 'weight_value' => 50, 'weight_type' => 'per_unit'],
                ['strain_id' => 'strain-2', 'quantity' => 200, 'price' => 2000, 'weight_value' => 100, 'weight_type' => 'per_unit']
            ]
        ];

        $excludedFields = [
            'updated_at',
            'created_at',
            'id',
            'pembelianId',
            'edit_mode',
            'showForm',
            'availableKandangs',
            'errorItems',
            'validationErrors'
        ];

        $hasChanges = DataChangeDetector::formHasChanges($currentData, $originalData, $excludedFields);
        $changes = DataChangeDetector::getChangedFields($currentData, $originalData, $excludedFields);

        if (!$hasChanges) {
            $this->info('✅ No changes detected correctly');
            $this->info('   Changes count: ' . count($changes));
        } else {
            $this->error('❌ Changes detected when there should be none');
            $this->info('   Changed fields: ' . implode(', ', array_keys($changes)));
        }

        $this->line('');
    }

    /**
     * Test scenario: Changes detected
     */
    private function testWithChanges(): void
    {
        $this->info('2. Testing With Changes Scenario...');

        $originalData = [
            'invoice_number' => 'INV-001',
            'date' => '2025-08-12',
            'supplier_id' => 'supplier-123',
            'farm_id' => 'farm-456',
            'coop_id' => 'coop-789',
            'expedition_id' => 'expedition-123',
            'expedition_fee' => '1000000',
            'batch_name' => 'BATCH-001',
            'status' => 'draft',
            'items' => [
                ['strain_id' => 'strain-1', 'quantity' => 100, 'price' => 1000, 'weight_value' => 50, 'weight_type' => 'per_unit']
            ]
        ];

        $currentData = [
            'invoice_number' => 'INV-002', // Changed
            'date' => '2025-08-13',        // Changed
            'supplier_id' => 'supplier-456', // Changed
            'farm_id' => 'farm-456',
            'coop_id' => 'coop-789',
            'expedition_id' => 'expedition-123',
            'expedition_fee' => '1500000', // Changed
            'batch_name' => 'BATCH-002',   // Changed
            'status' => 'draft',
            'items' => [
                ['strain_id' => 'strain-1', 'quantity' => 100, 'price' => 1000, 'weight_value' => 50, 'weight_type' => 'per_unit']
            ]
        ];

        $excludedFields = [
            'updated_at',
            'created_at',
            'id',
            'pembelianId',
            'edit_mode',
            'showForm',
            'availableKandangs',
            'errorItems',
            'validationErrors'
        ];

        $hasChanges = DataChangeDetector::formHasChanges($currentData, $originalData, $excludedFields);
        $changes = DataChangeDetector::getChangedFields($currentData, $originalData, $excludedFields);

        if ($hasChanges) {
            $this->info('✅ Changes detected correctly');
            $this->info('   Changed fields: ' . implode(', ', array_keys($changes)));
            $this->table(
                ['Field', 'From', 'To'],
                collect($changes)->map(function ($change, $field) {
                    return [$field, $change['from'], $change['to']];
                })->toArray()
            );
        } else {
            $this->error('❌ No changes detected when there should be');
        }

        $this->line('');
    }

    /**
     * Test scenario: Items changes detected
     */
    private function testItemsChanges(): void
    {
        $this->info('3. Testing Items Changes Scenario...');

        $originalData = [
            'invoice_number' => 'INV-001',
            'date' => '2025-08-12',
            'supplier_id' => 'supplier-123',
            'farm_id' => 'farm-456',
            'coop_id' => 'coop-789',
            'expedition_id' => 'expedition-123',
            'expedition_fee' => '1000000',
            'batch_name' => 'BATCH-001',
            'status' => 'draft',
            'items' => [
                ['strain_id' => 'strain-1', 'quantity' => 100, 'price' => 1000, 'weight_value' => 50, 'weight_type' => 'per_unit'],
                ['strain_id' => 'strain-2', 'quantity' => 200, 'price' => 2000, 'weight_value' => 100, 'weight_type' => 'per_unit']
            ]
        ];

        $currentData = [
            'invoice_number' => 'INV-001',
            'date' => '2025-08-12',
            'supplier_id' => 'supplier-123',
            'farm_id' => 'farm-456',
            'coop_id' => 'coop-789',
            'expedition_id' => 'expedition-123',
            'expedition_fee' => '1000000',
            'batch_name' => 'BATCH-001',
            'status' => 'draft',
            'items' => [
                ['strain_id' => 'strain-1', 'quantity' => 150, 'price' => 1000, 'weight_value' => 50, 'weight_type' => 'per_unit'], // Quantity changed
                ['strain_id' => 'strain-2', 'quantity' => 200, 'price' => 2500, 'weight_value' => 100, 'weight_type' => 'per_unit']  // Price changed
            ]
        ];

        $excludedFields = [
            'updated_at',
            'created_at',
            'id',
            'pembelianId',
            'edit_mode',
            'showForm',
            'availableKandangs',
            'errorItems',
            'validationErrors'
        ];

        $hasChanges = DataChangeDetector::formHasChanges($currentData, $originalData, $excludedFields);
        $changes = DataChangeDetector::getChangedFields($currentData, $originalData, $excludedFields);

        if ($hasChanges) {
            $this->info('✅ Items changes detected correctly');
            $this->info('   Changed fields: ' . implode(', ', array_keys($changes)));

            // Show detailed changes for items
            if (isset($changes['items'])) {
                $this->info('   Items changes detected in nested array');
            }
        } else {
            $this->error('❌ Items changes not detected');
        }

        $this->line('');
    }
}

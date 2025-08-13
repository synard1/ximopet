<?php

namespace App\Console\Commands;

use App\Helpers\DataChangeDetector;
use Illuminate\Console\Command;

class TestBypassLogic extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bypass:test {--scenario= : Test scenario to run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the bypass logic for data changes detection';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $scenario = $this->option('scenario') ?? 'all';

        $this->info('🧪 Testing Bypass Logic & Data Change Detection...\n');

        switch ($scenario) {
            case 'no-changes':
                $this->testNoChanges();
                break;
            case 'with-changes':
                $this->testWithChanges();
                break;
            case 'partial-changes':
                $this->testPartialChanges();
                break;
            case 'all':
            default:
                $this->testNoChanges();
                $this->testWithChanges();
                $this->testPartialChanges();
                break;
        }

        $this->info('\n🎉 Bypass Logic Test Completed!');
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
            'status' => 'draft'
        ];

        $currentData = [
            'invoice_number' => 'INV-001',
            'date' => '2025-08-12',
            'supplier_id' => 'supplier-123',
            'farm_id' => 'farm-456',
            'coop_id' => 'coop-789',
            'status' => 'draft'
        ];

        $hasChanges = DataChangeDetector::hasChanges($currentData, $originalData);
        $changes = DataChangeDetector::getChangedFields($currentData, $originalData);

        if (!$hasChanges) {
            $this->info('✅ No changes detected correctly');
            $this->info('   Changes count: ' . count($changes));
        } else {
            $this->error('❌ Changes detected when there should be none');
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
            'status' => 'draft'
        ];

        $currentData = [
            'invoice_number' => 'INV-002', // Changed
            'date' => '2025-08-13',        // Changed
            'supplier_id' => 'supplier-123',
            'farm_id' => 'farm-456',
            'coop_id' => 'coop-789',
            'status' => 'draft'
        ];

        $hasChanges = DataChangeDetector::hasChanges($currentData, $originalData);
        $changes = DataChangeDetector::getChangedFields($currentData, $originalData);

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
     * Test scenario: Partial changes with excluded fields
     */
    private function testPartialChanges(): void
    {
        $this->info('3. Testing Partial Changes with Excluded Fields...');

        $originalData = [
            'invoice_number' => 'INV-001',
            'date' => '2025-08-12',
            'supplier_id' => 'supplier-123',
            'farm_id' => 'farm-456',
            'coop_id' => 'coop-789',
            'status' => 'draft',
            'updated_at' => '2025-08-12 10:00:00', // Excluded field
            'created_at' => '2025-08-12 09:00:00'  // Excluded field
        ];

        $currentData = [
            'invoice_number' => 'INV-001',
            'date' => '2025-08-12',
            'supplier_id' => 'supplier-123',
            'farm_id' => 'farm-456',
            'coop_id' => 'coop-789',
            'status' => 'draft',
            'updated_at' => '2025-08-12 11:00:00', // Changed but excluded
            'created_at' => '2025-08-12 09:00:00'
        ];

        $excludedFields = ['updated_at', 'created_at'];

        $hasChanges = DataChangeDetector::hasChanges($currentData, $originalData, $excludedFields);
        $changes = DataChangeDetector::getChangedFields($currentData, $originalData, $excludedFields);

        if (!$hasChanges) {
            $this->info('✅ No significant changes detected (excluded fields ignored)');
            $this->info('   Changes count: ' . count($changes));
        } else {
            $this->warn('⚠️ Changes detected in non-excluded fields');
            $this->info('   Changed fields: ' . implode(', ', array_keys($changes)));
        }

        $this->line('');
    }

    /**
     * Test form change detection
     */
    private function testFormChanges(): void
    {
        $this->info('4. Testing Form Change Detection...');

        $originalData = [
            'invoice_number' => 'INV-001',
            'date' => '2025-08-12',
            'supplier_id' => 'supplier-123',
            'items' => [
                ['strain_id' => 'strain-1', 'quantity' => 100, 'price' => 1000],
                ['strain_id' => 'strain-2', 'quantity' => 200, 'price' => 2000]
            ]
        ];

        $currentData = [
            'invoice_number' => 'INV-001',
            'date' => '2025-08-12',
            'supplier_id' => 'supplier-123',
            'items' => [
                ['strain_id' => 'strain-1', 'quantity' => 150, 'price' => 1000], // Quantity changed
                ['strain_id' => 'strain-2', 'quantity' => 200, 'price' => 2000]
            ]
        ];

        $hasChanges = DataChangeDetector::formHasChanges($currentData, $originalData);
        $changes = DataChangeDetector::getChangedFields($currentData, $originalData);

        if ($hasChanges) {
            $this->info('✅ Form changes detected correctly');
            $this->info('   Changed fields: ' . implode(', ', array_keys($changes)));
        } else {
            $this->error('❌ Form changes not detected');
        }

        $this->line('');
    }
}

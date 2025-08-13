<?php

namespace App\Console\Commands;

use App\Helpers\DataChangeDetector;
use Illuminate\Console\Command;

class TestAdvancedBypassLogic extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bypass:test-advanced {--scenario= : Test scenario to run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the advanced bypass logic with realistic data scenarios';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $scenario = $this->option('scenario') ?? 'all';

        $this->info('🧪 Testing Advanced Bypass Logic...\n');

        switch ($scenario) {
            case 'null-vs-empty':
                $this->testNullVsEmpty();
                break;
            case 'array-structure':
                $this->testArrayStructure();
                break;
            case 'calculated-fields':
                $this->testCalculatedFields();
                break;
            case 'realistic-data':
                $this->testRealisticData();
                break;
            case 'all':
            default:
                $this->testNullVsEmpty();
                $this->testArrayStructure();
                $this->testCalculatedFields();
                $this->testRealisticData();
                break;
        }

        $this->info('\n🎉 Advanced Bypass Logic Test Completed!');
        return 0;
    }

    /**
     * Test null vs empty string handling
     */
    private function testNullVsEmpty(): void
    {
        $this->info('1. Testing Null vs Empty String Handling...');

        $originalData = [
            'batch_name' => null,
            'notes' => '',
            'status' => 'draft'
        ];

        $currentData = [
            'batch_name' => '',
            'notes' => null,
            'status' => 'draft'
        ];

        $excludedFields = ['updated_at', 'created_at'];

        $hasChanges = DataChangeDetector::formHasChanges($currentData, $originalData, $excludedFields);
        $changes = DataChangeDetector::getChangedFields($currentData, $originalData, $excludedFields);

        if (!$hasChanges) {
            $this->info('✅ Null vs empty string handled correctly - no meaningful changes');
            $this->info('   Changes count: ' . count($changes));
        } else {
            $this->warn('⚠️ Changes detected in null vs empty comparison');
            $this->info('   Changed fields: ' . implode(', ', array_keys($changes)));
        }

        $this->line('');
    }

    /**
     * Test array structure differences
     */
    private function testArrayStructure(): void
    {
        $this->info('2. Testing Array Structure Differences...');

        $originalData = [
            'items' => [
                [
                    'strain_id' => 'strain-1',
                    'quantity' => 100,
                    'price' => 1000,
                    'weight_value' => 50,
                    'weight_type' => 'per_unit'
                ]
            ]
        ];

        $currentData = [
            'items' => [
                [
                    'livestock_strain_id' => 'strain-1', // Different key name
                    'quantity' => 100,
                    'price_value' => 1000, // Different key name
                    'weight_value' => 50,
                    'weight_type' => 'per_unit',
                    'sub_total' => 100000 // Additional calculated field
                ]
            ]
        ];

        $excludedFields = ['updated_at', 'created_at', 'sub_total'];

        $hasChanges = DataChangeDetector::formHasChanges($currentData, $originalData, $excludedFields);
        $changes = DataChangeDetector::getChangedFields($currentData, $originalData, $excludedFields);

        if (!$hasChanges) {
            $this->info('✅ Array structure differences handled correctly - no meaningful changes');
            $this->info('   Changes count: ' . count($changes));
        } else {
            $this->warn('⚠️ Changes detected in array structure comparison');
            $this->info('   Changed fields: ' . implode(', ', array_keys($changes)));
        }

        $this->line('');
    }

    /**
     * Test calculated fields exclusion
     */
    private function testCalculatedFields(): void
    {
        $this->info('3. Testing Calculated Fields Exclusion...');

        $originalData = [
            'invoice_number' => 'INV-001',
            'date' => '2025-08-12',
            'total_quantity' => 100,
            'total_weight' => 5000,
            'sub_total' => 100000
        ];

        $currentData = [
            'invoice_number' => 'INV-001',
            'date' => '2025-08-12',
            'total_quantity' => 100,
            'total_weight' => 5000,
            'sub_total' => 100000
        ];

        $excludedFields = ['updated_at', 'created_at', 'total_quantity', 'total_weight', 'sub_total'];

        $hasChanges = DataChangeDetector::formHasChanges($currentData, $originalData, $excludedFields);
        $changes = DataChangeDetector::getChangedFields($currentData, $originalData, $excludedFields);

        if (!$hasChanges) {
            $this->info('✅ Calculated fields excluded correctly - no meaningful changes');
            $this->info('   Changes count: ' . count($changes));
        } else {
            $this->warn('⚠️ Changes detected despite calculated fields exclusion');
            $this->info('   Changed fields: ' . implode(', ', array_keys($changes)));
        }

        $this->line('');
    }

    /**
     * Test realistic data scenario
     */
    private function testRealisticData(): void
    {
        $this->info('4. Testing Realistic Data Scenario...');

        $originalData = [
            'invoice_number' => '00001',
            'date' => '2025-07-01 00:00:00',
            'supplier_id' => '9f46f024-591e-40f2-b6ce-04f2b149f799',
            'farm_id' => '9f46ed24-ed84-4746-ada7-ad0747328e92',
            'coop_id' => '9f4d29d2-9142-4191-8930-456fcfa75d32',
            'expedition_id' => '9f46f05f-1df2-4aa7-9263-d7aae9d2d3e5',
            'expedition_fee' => '250000.00',
            'batch_name' => null,
            'status' => 'draft',
            'items' => [
                [
                    'strain_id' => '9f4744c1-d62d-4ece-a5b4-e76621ed0398',
                    'quantity' => 5000,
                    'price' => '7800.00',
                    'weight_value' => '40.00',
                    'weight_type' => 'per_unit'
                ]
            ]
        ];

        $currentData = [
            'invoice_number' => '00001',
            'date' => '2025-07-01 00:00:00',
            'supplier_id' => '9f46f024-591e-40f2-b6ce-04f2b149f799',
            'farm_id' => '9f46ed24-ed84-4746-ada7-ad0747328e92',
            'coop_id' => '9f4d29d2-9142-4191-8930-456fcfa75d32',
            'expedition_id' => '9f46f05f-1df2-4aa7-9263-d7aae9d2d3e5',
            'expedition_fee' => '250000.00',
            'batch_name' => 'PR-Farm01-K3F1-01072025',
            'status' => 'draft',
            'items' => [
                [
                    'livestock_strain_id' => '9f4744c1-d62d-4ece-a5b4-e76621ed0398',
                    'quantity' => 5000,
                    'price_value' => '7800.00',
                    'weight_value' => '40.00',
                    'weight_type' => 'per_unit',
                    'sub_total' => 39000000
                ]
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
            'validationErrors',
            'sub_total',
            'total_quantity',
            'total_weight',
            'start_date',
            'livestock_id',
            'livestock_strain_standard_id',
            'farm_id',
            'coop_id'
        ];

        $hasChanges = DataChangeDetector::formHasChanges($currentData, $originalData, $excludedFields);
        $changes = DataChangeDetector::getChangedFields($currentData, $originalData, $excludedFields);

        if (!$hasChanges) {
            $this->info('✅ Realistic data handled correctly - no meaningful changes');
            $this->info('   Changes count: ' . count($changes));
        } else {
            $this->warn('⚠️ Changes detected in realistic data comparison');
            $this->info('   Changed fields: ' . implode(', ', array_keys($changes)));

            if (!empty($changes)) {
                $this->info('   Changed fields details:');
                foreach ($changes as $field => $change) {
                    $from = is_array($change['from']) ? 'array' : (string)$change['from'];
                    $to = is_array($change['to']) ? 'array' : (string)$change['to'];
                    $this->line("     - {$field}: {$from} → {$to}");
                }
            }
        }

        $this->line('');
    }
}

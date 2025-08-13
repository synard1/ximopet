<?php

namespace App\Console\Commands;

use App\Helpers\DataChangeDetector;
use Illuminate\Console\Command;

class DebugBypassLogic extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bypass:debug';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug bypass logic with production-like data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Debugging Bypass Logic with Production Data...\n');

        // Test case 1: batch_name issue
        $this->testBatchNameIssue();

        // Test case 2: items array issue
        $this->testItemsArrayIssue();

        // Test case 3: combined data
        $this->testCombinedData();

        $this->info('\n🎯 Debug Complete!');
        return 0;
    }

    /**
     * Test batch_name empty vs non-empty issue
     */
    private function testBatchNameIssue(): void
    {
        $this->info('1. Testing batch_name issue...');

        $originalData = [
            'batch_name' => '',
            'status' => 'draft'
        ];

        $currentData = [
            'batch_name' => 'PR-Farm01-K3F1-01072025',
            'status' => 'draft'
        ];

        $excludedFields = ['updated_at', 'created_at'];

        $this->info('   Original data: ' . json_encode($originalData));
        $this->info('   Current data: ' . json_encode($currentData));
        $this->info('   Excluded fields: ' . json_encode($excludedFields));

        $hasChanges = DataChangeDetector::formHasChanges($currentData, $originalData, $excludedFields);
        $changes = DataChangeDetector::getChangedFields($currentData, $originalData, $excludedFields);

        $this->info('   Result: ' . ($hasChanges ? 'CHANGES DETECTED' : 'NO CHANGES'));
        $this->info('   Changes count: ' . count($changes));

        if (!empty($changes)) {
            foreach ($changes as $field => $change) {
                $from = is_array($change['from']) ? 'array' : (string)$change['from'];
                $to = is_array($change['to']) ? 'array' : (string)$change['to'];
                $this->info("     - {$field}: '{$from}' → '{$to}'");
            }
        }

        $this->line('');
    }

    /**
     * Test items array structure issue
     */
    private function testItemsArrayIssue(): void
    {
        $this->info('2. Testing items array issue...');

        $originalData = [
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

        $excludedFields = ['updated_at', 'created_at', 'sub_total'];

        $this->info('   Original items: ' . json_encode($originalData['items']));
        $this->info('   Current items: ' . json_encode($currentData['items']));
        $this->info('   Excluded fields: ' . json_encode($excludedFields));

        $hasChanges = DataChangeDetector::formHasChanges($currentData, $originalData, $excludedFields);
        $changes = DataChangeDetector::getChangedFields($currentData, $originalData, $excludedFields);

        $this->info('   Result: ' . ($hasChanges ? 'CHANGES DETECTED' : 'NO CHANGES'));
        $this->info('   Changes count: ' . count($changes));

        if (!empty($changes)) {
            foreach ($changes as $field => $change) {
                $from = is_array($change['from']) ? 'array' : (string)$change['from'];
                $to = is_array($change['to']) ? 'array' : (string)$change['to'];
                $this->info("     - {$field}: '{$from}' → '{$to}'");
            }
        }

        $this->line('');
    }

    /**
     * Test combined data like in production
     */
    private function testCombinedData(): void
    {
        $this->info('3. Testing combined production data...');

        $originalData = [
            'invoice_number' => '00001',
            'date' => '2025-07-01 00:00:00',
            'supplier_id' => '9f46f024-591e-40f2-b6ce-04f2b149f799',
            'farm_id' => '9f46ed24-ed84-4746-ada7-ad0747328e92',
            'coop_id' => '9f4d29d2-9142-4191-8930-456fcfa75d32',
            'expedition_id' => '9f46f05f-1df2-4aa7-9263-d7aae9d2d3e5',
            'expedition_fee' => '250000.00',
            'batch_name' => '',
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

        $this->info('   Testing with production-like data...');
        $this->info('   Excluded fields count: ' . count($excludedFields));

        $hasChanges = DataChangeDetector::formHasChanges($currentData, $originalData, $excludedFields);
        $changes = DataChangeDetector::getChangedFields($currentData, $originalData, $excludedFields);

        $this->info('   Final Result: ' . ($hasChanges ? 'CHANGES DETECTED' : 'NO CHANGES'));
        $this->info('   Changes count: ' . count($changes));

        if (!empty($changes)) {
            $this->info('   Changed fields:');
            foreach ($changes as $field => $change) {
                $from = is_array($change['from']) ? 'array' : (string)$change['from'];
                $to = is_array($change['to']) ? 'array' : (string)$change['to'];
                $this->info("     - {$field}: '{$from}' → '{$to}'");
            }
        } else {
            $this->info('   ✅ SUCCESS: No changes detected - bypass should work!');
        }

        $this->line('');
    }
}

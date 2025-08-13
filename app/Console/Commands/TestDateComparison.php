<?php

namespace App\Console\Commands;

use App\Helpers\DataChangeDetector;
use Illuminate\Console\Command;

class TestDateComparison extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bypass:test-date';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test date comparison logic in bypass system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Testing Date Comparison Logic...\n');

        // Test case 1: Same date strings
        $this->testSameDateStrings();

        // Test case 2: Different date formats
        $this->testDifferentDateFormats();

        // Test case 3: Production-like data
        $this->testProductionData();

        $this->info('\n🎯 Date Comparison Test Complete!');
        return 0;
    }

    /**
     * Test same date strings
     */
    private function testSameDateStrings(): void
    {
        $this->info('1. Testing Same Date Strings...');

        $originalData = [
            'date' => '2025-07-01 00:00:00',
            'status' => 'draft'
        ];

        $currentData = [
            'date' => '2025-07-01 00:00:00',
            'status' => 'draft'
        ];

        $excludedFields = ['updated_at', 'created_at'];

        $this->info('   Original date: ' . $originalData['date']);
        $this->info('   Current date: ' . $currentData['date']);

        $hasChanges = DataChangeDetector::formHasChanges($currentData, $originalData, $excludedFields);
        $changes = DataChangeDetector::getChangedFields($currentData, $originalData, $excludedFields);

        $this->info('   Result: ' . ($hasChanges ? 'CHANGES DETECTED' : 'NO CHANGES'));
        $this->info('   Changes count: ' . count($changes));

        if (!empty($changes)) {
            foreach ($changes as $field => $change) {
                $this->info("     - {$field}: '{$change['from']}' → '{$change['to']}'");
            }
        }

        $this->line('');
    }

    /**
     * Test different date formats
     */
    private function testDifferentDateFormats(): void
    {
        $this->info('2. Testing Different Date Formats...');

        $originalData = [
            'date' => '2025-07-01',
            'status' => 'draft'
        ];

        $currentData = [
            'date' => '2025-07-01 00:00:00',
            'status' => 'draft'
        ];

        $excludedFields = ['updated_at', 'created_at'];

        $this->info('   Original date: ' . $originalData['date']);
        $this->info('   Current date: ' . $currentData['date']);

        $hasChanges = DataChangeDetector::formHasChanges($currentData, $originalData, $excludedFields);
        $changes = DataChangeDetector::getChangedFields($currentData, $originalData, $excludedFields);

        $this->info('   Result: ' . ($hasChanges ? 'CHANGES DETECTED' : 'NO CHANGES'));
        $this->info('   Changes count: ' . count($changes));

        if (!empty($changes)) {
            foreach ($changes as $field => $change) {
                $this->info("     - {$field}: '{$change['from']}' → '{$change['to']}'");
            }
        }

        $this->line('');
    }

    /**
     * Test production-like data
     */
    private function testProductionData(): void
    {
        $this->info('3. Testing Production-like Data...');

        $originalData = [
            'invoice_number' => '00001',
            'date' => '2025-07-01 00:00:00',
            'supplier_id' => '9f46f024-591e-40f2-b6ce-04f2b149f799',
            'batch_name' => '',
            'status' => 'draft'
        ];

        $currentData = [
            'invoice_number' => '00001',
            'date' => '2025-07-01 00:00:00',
            'supplier_id' => '9f46f024-591e-40f2-b6ce-04f2b149f799',
            'batch_name' => 'PR-Farm01-K3F1-01072025',
            'status' => 'draft'
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

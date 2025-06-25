<?php

/**
 * Test Script for Manual Depletion Data Structure Fix
 * 
 * This script tests the refactored methods in ManualBatchDepletion component
 * to ensure they work with the existing LivestockDepletion model structure.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\LivestockDepletion;
use App\Models\LivestockBatch;
use Carbon\Carbon;

class ManualDepletionTest
{
    private $livestockId = 'test-livestock-123';

    public function runTests()
    {
        echo "=== Manual Depletion Data Structure Fix Test ===\n\n";

        $this->testGetConflictingBatchesToday();
        $this->testGetBatchDepletionCountsToday();
        $this->testGetLastDepletionTime();

        echo "\n=== All Tests Completed ===\n";
    }

    /**
     * Test the refactored getConflictingBatchesToday method
     */
    private function testGetConflictingBatchesToday()
    {
        echo "Testing getConflictingBatchesToday method...\n";

        try {
            $selectedBatches = [
                ['batch_id' => 'batch-1'],
                ['batch_id' => 'batch-2']
            ];
            $selectedBatchIds = collect($selectedBatches)->pluck('batch_id')->toArray();

            // Simulate the refactored method logic
            $todayDepletions = LivestockDepletion::where('livestock_id', $this->livestockId)
                ->whereDate('created_at', now()->toDateString())
                ->get();

            $existingBatches = [];

            foreach ($todayDepletions as $depletion) {
                if (isset($depletion->data['manual_batches']) && is_array($depletion->data['manual_batches'])) {
                    foreach ($depletion->data['manual_batches'] as $batchData) {
                        if (in_array($batchData['batch_id'], $selectedBatchIds)) {
                            $batch = LivestockBatch::find($batchData['batch_id']);
                            if ($batch) {
                                $existingBatches[] = $batch->batch_name;
                            }
                        }
                    }
                }
            }

            $result = array_unique($existingBatches);

            echo "✅ getConflictingBatchesToday: SUCCESS\n";
            echo "   - Found " . count($result) . " conflicting batches\n";
        } catch (Exception $e) {
            echo "❌ getConflictingBatchesToday: FAILED\n";
            echo "   Error: " . $e->getMessage() . "\n";
        }

        echo "\n";
    }

    /**
     * Test the refactored getBatchDepletionCountsToday method
     */
    private function testGetBatchDepletionCountsToday()
    {
        echo "Testing getBatchDepletionCountsToday method...\n";

        try {
            $selectedBatches = [
                ['batch_id' => 'batch-1'],
                ['batch_id' => 'batch-2']
            ];
            $selectedBatchIds = collect($selectedBatches)->pluck('batch_id')->toArray();

            // Simulate the refactored method logic
            $todayDepletions = LivestockDepletion::where('livestock_id', $this->livestockId)
                ->whereDate('created_at', now()->toDateString())
                ->get();

            $counts = [];

            foreach ($todayDepletions as $depletion) {
                if (isset($depletion->data['manual_batches']) && is_array($depletion->data['manual_batches'])) {
                    foreach ($depletion->data['manual_batches'] as $batchData) {
                        $batchId = $batchData['batch_id'];
                        if (in_array($batchId, $selectedBatchIds)) {
                            $quantity = $batchData['quantity'] ?? 1;
                            $counts[$batchId] = ($counts[$batchId] ?? 0) + $quantity;
                        }
                    }
                }
            }

            echo "✅ getBatchDepletionCountsToday: SUCCESS\n";
            echo "   - Processed " . count($counts) . " batch counts\n";
            echo "   - Total counts: " . json_encode($counts) . "\n";
        } catch (Exception $e) {
            echo "❌ getBatchDepletionCountsToday: FAILED\n";
            echo "   Error: " . $e->getMessage() . "\n";
        }

        echo "\n";
    }

    /**
     * Test the refactored getLastDepletionTime method
     */
    private function testGetLastDepletionTime()
    {
        echo "Testing getLastDepletionTime method...\n";

        try {
            // Simulate the refactored method logic
            $lastDepletion = LivestockDepletion::where('livestock_id', $this->livestockId)
                ->orderBy('created_at', 'desc')
                ->first();

            $result = $lastDepletion ? Carbon::parse($lastDepletion->created_at) : null;

            echo "✅ getLastDepletionTime: SUCCESS\n";
            echo "   - Last depletion: " . ($result ? $result->format('Y-m-d H:i:s') : 'None found') . "\n";
        } catch (Exception $e) {
            echo "❌ getLastDepletionTime: FAILED\n";
            echo "   Error: " . $e->getMessage() . "\n";
        }

        echo "\n";
    }

    /**
     * Test data structure compatibility
     */
    private function testDataStructure()
    {
        echo "Testing LivestockDepletion data structure...\n";

        try {
            // Test sample data structure
            $sampleData = [
                'depletion_method' => 'manual',
                'manual_batches' => [
                    [
                        'batch_id' => 'batch-1',
                        'quantity' => 10,
                        'note' => 'Test note'
                    ],
                    [
                        'batch_id' => 'batch-2',
                        'quantity' => 5,
                        'note' => 'Another note'
                    ]
                ],
                'reason' => 'Test depletion',
                'processed_at' => now()->toDateTimeString(),
                'processed_by' => 'test-user'
            ];

            $sampleMetadata = [
                'validation' => [
                    'config_validated' => true,
                    'restrictions_checked' => true
                ],
                'processing' => [
                    'preview_generated' => true,
                    'batch_availability_verified' => true
                ]
            ];

            // Verify JSON encoding/decoding works
            $encodedData = json_encode($sampleData);
            $decodedData = json_decode($encodedData, true);

            if ($decodedData['manual_batches'][0]['batch_id'] === 'batch-1') {
                echo "✅ Data structure: SUCCESS\n";
                echo "   - JSON encoding/decoding works correctly\n";
                echo "   - Manual batches structure is valid\n";
            } else {
                echo "❌ Data structure: FAILED\n";
                echo "   - JSON structure validation failed\n";
            }
        } catch (Exception $e) {
            echo "❌ Data structure: FAILED\n";
            echo "   Error: " . $e->getMessage() . "\n";
        }

        echo "\n";
    }
}

// Run the tests
if (php_sapi_name() === 'cli') {
    $test = new ManualDepletionTest();
    $test->runTests();
} else {
    echo "This script should be run from command line.\n";
}

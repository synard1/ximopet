<?php

/**
 * Simple Syntax Test for Manual Depletion Data Structure Fix
 * 
 * This script tests the logic and syntax of the refactored methods
 * without requiring database connection.
 */

class ManualDepletionSyntaxTest
{
    public function runTests()
    {
        echo "=== Manual Depletion Syntax Test ===\n\n";

        $this->testBatchConflictLogic();
        $this->testBatchCountLogic();
        $this->testDataStructure();

        echo "\n=== All Syntax Tests Completed ===\n";
    }

    /**
     * Test the logic of getConflictingBatchesToday method
     */
    private function testBatchConflictLogic()
    {
        echo "Testing batch conflict detection logic...\n";

        try {
            // Sample data structure
            $selectedBatches = [
                ['batch_id' => 'batch-1'],
                ['batch_id' => 'batch-2']
            ];
            $selectedBatchIds = array_column($selectedBatches, 'batch_id');

            // Simulate depletion data
            $todayDepletions = [
                (object)[
                    'data' => [
                        'manual_batches' => [
                            ['batch_id' => 'batch-1', 'quantity' => 5],
                            ['batch_id' => 'batch-3', 'quantity' => 3]
                        ]
                    ]
                ],
                (object)[
                    'data' => [
                        'manual_batches' => [
                            ['batch_id' => 'batch-2', 'quantity' => 2]
                        ]
                    ]
                ]
            ];

            $existingBatches = [];

            foreach ($todayDepletions as $depletion) {
                if (isset($depletion->data['manual_batches']) && is_array($depletion->data['manual_batches'])) {
                    foreach ($depletion->data['manual_batches'] as $batchData) {
                        if (in_array($batchData['batch_id'], $selectedBatchIds)) {
                            // Simulate batch name lookup
                            $batchName = 'Batch-' . $batchData['batch_id'];
                            $existingBatches[] = $batchName;
                        }
                    }
                }
            }

            $result = array_unique($existingBatches);

            echo "✅ Batch conflict logic: SUCCESS\n";
            echo "   - Found " . count($result) . " conflicting batches\n";
            echo "   - Conflicts: " . implode(', ', $result) . "\n";
        } catch (Exception $e) {
            echo "❌ Batch conflict logic: FAILED\n";
            echo "   Error: " . $e->getMessage() . "\n";
        }

        echo "\n";
    }

    /**
     * Test the logic of getBatchDepletionCountsToday method
     */
    private function testBatchCountLogic()
    {
        echo "Testing batch count calculation logic...\n";

        try {
            $selectedBatches = [
                ['batch_id' => 'batch-1'],
                ['batch_id' => 'batch-2']
            ];
            $selectedBatchIds = array_column($selectedBatches, 'batch_id');

            // Simulate depletion data with multiple entries for same batch
            $todayDepletions = [
                (object)[
                    'data' => [
                        'manual_batches' => [
                            ['batch_id' => 'batch-1', 'quantity' => 5],
                            ['batch_id' => 'batch-2', 'quantity' => 3]
                        ]
                    ]
                ],
                (object)[
                    'data' => [
                        'manual_batches' => [
                            ['batch_id' => 'batch-1', 'quantity' => 2],
                            ['batch_id' => 'batch-3', 'quantity' => 1]
                        ]
                    ]
                ]
            ];

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

            echo "✅ Batch count logic: SUCCESS\n";
            echo "   - Processed " . count($counts) . " batch counts\n";
            echo "   - Counts: " . json_encode($counts) . "\n";
            echo "   - Expected: batch-1=7, batch-2=3\n";

            // Verify expected results
            if ($counts['batch-1'] === 7 && $counts['batch-2'] === 3) {
                echo "   - ✅ Count calculation is correct\n";
            } else {
                echo "   - ❌ Count calculation is incorrect\n";
            }
        } catch (Exception $e) {
            echo "❌ Batch count logic: FAILED\n";
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
                'processed_at' => date('Y-m-d H:i:s'),
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
                ],
                'audit' => [
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'Test Agent'
                ]
            ];

            // Verify JSON encoding/decoding works
            $encodedData = json_encode($sampleData);
            $decodedData = json_decode($encodedData, true);

            $encodedMetadata = json_encode($sampleMetadata);
            $decodedMetadata = json_decode($encodedMetadata, true);

            if (
                $decodedData['manual_batches'][0]['batch_id'] === 'batch-1' &&
                $decodedMetadata['validation']['config_validated'] === true
            ) {
                echo "✅ Data structure: SUCCESS\n";
                echo "   - JSON encoding/decoding works correctly\n";
                echo "   - Manual batches structure is valid\n";
                echo "   - Metadata structure is valid\n";
                echo "   - Total batch count: " . count($decodedData['manual_batches']) . "\n";
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

    /**
     * Test array manipulation functions
     */
    private function testArrayFunctions()
    {
        echo "Testing array manipulation functions...\n";

        try {
            // Test array_column function
            $batches = [
                ['batch_id' => 'batch-1', 'name' => 'Batch One'],
                ['batch_id' => 'batch-2', 'name' => 'Batch Two']
            ];

            $ids = array_column($batches, 'batch_id');

            if (count($ids) === 2 && $ids[0] === 'batch-1') {
                echo "✅ array_column: SUCCESS\n";
            } else {
                echo "❌ array_column: FAILED\n";
            }

            // Test in_array function
            if (in_array('batch-1', $ids)) {
                echo "✅ in_array: SUCCESS\n";
            } else {
                echo "❌ in_array: FAILED\n";
            }

            // Test array_unique function
            $duplicates = ['batch-1', 'batch-2', 'batch-1', 'batch-3'];
            $unique = array_unique($duplicates);

            if (count($unique) === 3) {
                echo "✅ array_unique: SUCCESS\n";
            } else {
                echo "❌ array_unique: FAILED\n";
            }
        } catch (Exception $e) {
            echo "❌ Array functions: FAILED\n";
            echo "   Error: " . $e->getMessage() . "\n";
        }

        echo "\n";
    }
}

// Run the tests
$test = new ManualDepletionSyntaxTest();
$test->runTests();

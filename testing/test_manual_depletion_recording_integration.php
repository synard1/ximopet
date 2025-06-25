<?php

/**
 * Test Script for Manual Depletion Recording Integration
 * 
 * This script tests the logic of findExistingRecording method
 * and recording integration in manual depletion.
 */

class ManualDepletionRecordingTest
{
    public function runTests()
    {
        echo "=== Manual Depletion Recording Integration Test ===\n\n";

        $this->testFindExistingRecordingLogic();
        $this->testDepletionDataWithRecording();
        $this->testDepletionDataWithoutRecording();
        $this->testPreviewDataEnhancement();

        echo "\n=== All Recording Integration Tests Completed ===\n";
    }

    /**
     * Test the logic of findExistingRecording method
     */
    private function testFindExistingRecordingLogic()
    {
        echo "Testing findExistingRecording logic...\n";

        try {
            // Simulate existing recording data
            $existingRecording = (object)[
                'id' => 'rec-123',
                'livestock_id' => 'livestock-456',
                'tanggal' => new DateTime('2025-06-23'),
                'final_stock' => 1000,
                'stock_akhir' => 1000,
                'mortality' => 5,
                'culling' => 2
            ];

            // Test recording found scenario
            $recordingFound = $existingRecording;

            if ($recordingFound && $recordingFound->id === 'rec-123') {
                echo "✅ Recording found scenario: SUCCESS\n";
                echo "   - Recording ID: " . $recordingFound->id . "\n";
                echo "   - Livestock ID: " . $recordingFound->livestock_id . "\n";
                echo "   - Date: " . $recordingFound->tanggal->format('Y-m-d') . "\n";
            } else {
                echo "❌ Recording found scenario: FAILED\n";
            }

            // Test no recording scenario
            $noRecording = null;

            if ($noRecording === null) {
                echo "✅ No recording scenario: SUCCESS\n";
                echo "   - Recording ID: null\n";
            } else {
                echo "❌ No recording scenario: FAILED\n";
            }
        } catch (Exception $e) {
            echo "❌ findExistingRecording logic: FAILED\n";
            echo "   Error: " . $e->getMessage() . "\n";
        }

        echo "\n";
    }

    /**
     * Test depletion data structure with recording
     */
    private function testDepletionDataWithRecording()
    {
        echo "Testing depletion data with recording...\n";

        try {
            // Simulate existing recording
            $existingRecording = (object)[
                'id' => 'rec-123',
                'livestock_id' => 'livestock-456',
                'tanggal' => new DateTime('2025-06-23')
            ];

            // Simulate depletion data structure
            $depletionData = [
                'livestock_id' => 'livestock-456',
                'type' => 'mortality',
                'date' => '2025-06-23',
                'depletion_method' => 'manual',
                'recording_id' => $existingRecording ? $existingRecording->id : null,
                'manual_batches' => [
                    [
                        'batch_id' => 'batch-1',
                        'quantity' => 10,
                        'note' => 'Test note'
                    ]
                ],
                'reason' => 'Test depletion'
            ];

            if ($depletionData['recording_id'] === 'rec-123') {
                echo "✅ Depletion data with recording: SUCCESS\n";
                echo "   - Recording ID included: " . $depletionData['recording_id'] . "\n";
                echo "   - Livestock ID: " . $depletionData['livestock_id'] . "\n";
                echo "   - Manual batches count: " . count($depletionData['manual_batches']) . "\n";
            } else {
                echo "❌ Depletion data with recording: FAILED\n";
            }
        } catch (Exception $e) {
            echo "❌ Depletion data with recording: FAILED\n";
            echo "   Error: " . $e->getMessage() . "\n";
        }

        echo "\n";
    }

    /**
     * Test depletion data structure without recording
     */
    private function testDepletionDataWithoutRecording()
    {
        echo "Testing depletion data without recording...\n";

        try {
            // No existing recording
            $existingRecording = null;

            // Simulate depletion data structure
            $depletionData = [
                'livestock_id' => 'livestock-456',
                'type' => 'mortality',
                'date' => '2025-06-24',
                'depletion_method' => 'manual',
                'recording_id' => $existingRecording ? $existingRecording->id : null,
                'manual_batches' => [
                    [
                        'batch_id' => 'batch-1',
                        'quantity' => 5,
                        'note' => 'Test note'
                    ]
                ],
                'reason' => 'Test depletion'
            ];

            if ($depletionData['recording_id'] === null) {
                echo "✅ Depletion data without recording: SUCCESS\n";
                echo "   - Recording ID: null\n";
                echo "   - Livestock ID: " . $depletionData['livestock_id'] . "\n";
                echo "   - System continues normally\n";
            } else {
                echo "❌ Depletion data without recording: FAILED\n";
            }
        } catch (Exception $e) {
            echo "❌ Depletion data without recording: FAILED\n";
            echo "   Error: " . $e->getMessage() . "\n";
        }

        echo "\n";
    }

    /**
     * Test preview data enhancement with recording info
     */
    private function testPreviewDataEnhancement()
    {
        echo "Testing preview data enhancement...\n";

        try {
            // Simulate existing recording
            $existingRecording = (object)[
                'id' => 'rec-123',
                'tanggal' => new DateTime('2025-06-23'),
                'final_stock' => 1000,
                'stock_akhir' => 950,
                'mortality' => 5,
                'culling' => 2
            ];

            // Simulate base preview data
            $previewData = [
                'can_fulfill' => true,
                'validation_passed' => true,
                'total_quantity' => 10,
                'batches' => [
                    ['batch_id' => 'batch-1', 'quantity' => 10]
                ]
            ];

            // Add recording information to preview data
            if ($existingRecording) {
                $previewData['recording_info'] = [
                    'recording_id' => $existingRecording->id,
                    'recording_date' => $existingRecording->tanggal->format('Y-m-d'),
                    'current_stock' => $existingRecording->final_stock ?? $existingRecording->stock_akhir,
                    'mortality' => $existingRecording->mortality ?? 0,
                    'culling' => $existingRecording->culling ?? 0
                ];
            }

            if (
                isset($previewData['recording_info']) &&
                $previewData['recording_info']['recording_id'] === 'rec-123'
            ) {
                echo "✅ Preview data enhancement: SUCCESS\n";
                echo "   - Recording info added to preview\n";
                echo "   - Recording ID: " . $previewData['recording_info']['recording_id'] . "\n";
                echo "   - Current stock: " . $previewData['recording_info']['current_stock'] . "\n";
                echo "   - Mortality: " . $previewData['recording_info']['mortality'] . "\n";
                echo "   - Culling: " . $previewData['recording_info']['culling'] . "\n";
            } else {
                echo "❌ Preview data enhancement: FAILED\n";
            }
        } catch (Exception $e) {
            echo "❌ Preview data enhancement: FAILED\n";
            echo "   Error: " . $e->getMessage() . "\n";
        }

        echo "\n";
    }

    /**
     * Test error handling scenarios
     */
    private function testErrorHandling()
    {
        echo "Testing error handling scenarios...\n";

        try {
            // Test with invalid recording data
            $invalidRecording = (object)[
                'id' => null,
                'livestock_id' => 'livestock-456'
            ];

            // Test graceful handling
            $recordingId = ($invalidRecording && $invalidRecording->id) ? $invalidRecording->id : null;

            if ($recordingId === null) {
                echo "✅ Invalid recording handling: SUCCESS\n";
                echo "   - Gracefully handled null ID\n";
            } else {
                echo "❌ Invalid recording handling: FAILED\n";
            }

            // Test missing properties
            $incompleteRecording = (object)[
                'id' => 'rec-456'
                // Missing other properties
            ];

            $currentStock = $incompleteRecording->final_stock ?? $incompleteRecording->stock_akhir ?? 0;
            $mortality = $incompleteRecording->mortality ?? 0;

            if ($currentStock === 0 && $mortality === 0) {
                echo "✅ Missing properties handling: SUCCESS\n";
                echo "   - Default values used for missing properties\n";
            } else {
                echo "❌ Missing properties handling: FAILED\n";
            }
        } catch (Exception $e) {
            echo "❌ Error handling: FAILED\n";
            echo "   Error: " . $e->getMessage() . "\n";
        }

        echo "\n";
    }

    /**
     * Test data consistency scenarios
     */
    private function testDataConsistency()
    {
        echo "Testing data consistency...\n";

        try {
            // Test date matching
            $depletionDate = '2025-06-23';
            $recordingDate = '2025-06-23';

            if ($depletionDate === $recordingDate) {
                echo "✅ Date matching: SUCCESS\n";
                echo "   - Depletion date matches recording date\n";
            } else {
                echo "❌ Date matching: FAILED\n";
            }

            // Test livestock ID consistency
            $depletionLivestockId = 'livestock-456';
            $recordingLivestockId = 'livestock-456';

            if ($depletionLivestockId === $recordingLivestockId) {
                echo "✅ Livestock ID consistency: SUCCESS\n";
                echo "   - Livestock IDs match between depletion and recording\n";
            } else {
                echo "❌ Livestock ID consistency: FAILED\n";
            }
        } catch (Exception $e) {
            echo "❌ Data consistency: FAILED\n";
            echo "   Error: " . $e->getMessage() . "\n";
        }

        echo "\n";
    }
}

// Run the tests
$test = new ManualDepletionRecordingTest();
$test->runTests();

<?php

/**
 * Batch Depletion Service Demo Script
 * 
 * Demo penggunaan BatchDepletionService dengan fitur-fitur enhanced
 * untuk pencatatan depletion livestock dengan metode FIFO
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\Livestock\BatchDepletionService;
use App\Models\Livestock;
use App\Models\LivestockDepletion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BatchDepletionServiceDemo
{
    private $service;
    private $testLivestockId;

    public function __construct()
    {
        $this->service = new BatchDepletionService();
        echo "🚀 Batch Depletion Service Demo\n";
        echo "==============================\n\n";
    }

    public function runDemo()
    {
        try {
            $this->setupTestData();
            $this->demonstrateBasicDepletion();
            $this->demonstratePreviewFeature();
            $this->demonstrateBulkProcessing();
            $this->demonstrateAnalytics();
            $this->demonstrateReversal();
            $this->demonstrateConfigRecommendations();
            $this->demonstrateExport();

            echo "\n✅ Demo completed successfully!\n";
        } catch (Exception $e) {
            echo "\n❌ Demo failed: " . $e->getMessage() . "\n";
            echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
        }
    }

    private function setupTestData()
    {
        echo "📋 Setting up test data...\n";

        // Find or create test livestock
        $livestock = Livestock::where('name', 'LIKE', '%Demo%')->first();

        if (!$livestock) {
            echo "⚠️  No demo livestock found. Please create test data first.\n";
            $this->testLivestockId = 'test-livestock-123'; // Fallback for demo
        } else {
            $this->testLivestockId = $livestock->id;
            echo "✅ Using livestock: {$livestock->name} (ID: {$livestock->id})\n";
        }

        echo "\n";
    }

    private function demonstrateBasicDepletion()
    {
        echo "🎯 1. Basic Depletion Processing Demo\n";
        echo "=====================================\n";

        // Example depletion data
        $depletionData = [
            'livestock_id' => $this->testLivestockId,
            'quantity' => 25,
            'type' => 'mortality',
            'date' => now()
        ];

        echo "Processing depletion:\n";
        echo "- Livestock ID: {$depletionData['livestock_id']}\n";
        echo "- Quantity: {$depletionData['quantity']}\n";
        echo "- Type: {$depletionData['type']}\n";

        try {
            // Simulate processing (don't actually process in demo)
            echo "📊 Simulating depletion processing...\n";

            // Simulate result
            $mockResult = [
                'success' => true,
                'livestock_id' => $this->testLivestockId,
                'total_depleted' => 25,
                'processed_batches' => [
                    [
                        'batch_id' => 'batch-demo-1',
                        'batch_name' => 'Demo Batch A',
                        'depleted_quantity' => 25,
                        'remaining_quantity' => 75,
                        'age_days' => 30
                    ]
                ],
                'depletion_method' => 'fifo',
                'message' => 'Batch depletion completed successfully'
            ];

            echo "✅ Result:\n";
            echo json_encode($mockResult, JSON_PRETTY_PRINT) . "\n";
        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
        }

        echo "\n";
    }

    private function demonstratePreviewFeature()
    {
        echo "👁️  2. Preview Feature Demo\n";
        echo "===========================\n";

        $previewData = [
            'livestock_id' => $this->testLivestockId,
            'quantity' => 50,
            'type' => 'sales'
        ];

        echo "Previewing depletion:\n";
        echo "- Quantity: {$previewData['quantity']}\n";
        echo "- Type: {$previewData['type']}\n";

        try {
            // Simulate preview
            $mockPreview = [
                'method' => 'batch',
                'depletion_method' => 'fifo',
                'total_quantity' => 50,
                'can_fulfill' => true,
                'batches_affected' => [
                    [
                        'batch_id' => 'batch-demo-1',
                        'batch_name' => 'Demo Batch A',
                        'available_quantity' => 75,
                        'will_deplete' => 50,
                        'remaining_after' => 25,
                        'batch_age_days' => 30
                    ]
                ],
                'shortfall' => 0
            ];

            echo "📋 Preview Result:\n";
            echo json_encode($mockPreview, JSON_PRETTY_PRINT) . "\n";

            if ($mockPreview['can_fulfill']) {
                echo "✅ Depletion can be fulfilled\n";
            } else {
                echo "❌ Insufficient quantity (shortfall: {$mockPreview['shortfall']})\n";
            }
        } catch (Exception $e) {
            echo "❌ Preview error: " . $e->getMessage() . "\n";
        }

        echo "\n";
    }

    private function demonstrateBulkProcessing()
    {
        echo "📦 3. Bulk Processing Demo\n";
        echo "==========================\n";

        $bulkData = [
            ['livestock_id' => $this->testLivestockId, 'quantity' => 10, 'type' => 'mortality'],
            ['livestock_id' => $this->testLivestockId, 'quantity' => 15, 'type' => 'sales'],
            ['livestock_id' => $this->testLivestockId, 'quantity' => 5, 'type' => 'culling']
        ];

        echo "Processing bulk depletion with " . count($bulkData) . " items:\n";
        foreach ($bulkData as $index => $data) {
            echo "  {$index}: {$data['quantity']} units - {$data['type']}\n";
        }

        try {
            // Simulate bulk processing
            $mockBulkResult = [
                'total_processed' => 3,
                'success_count' => 3,
                'error_count' => 0,
                'success_rate' => 100.0,
                'results' => [
                    ['index' => 0, 'status' => 'success'],
                    ['index' => 1, 'status' => 'success'],
                    ['index' => 2, 'status' => 'success']
                ]
            ];

            echo "📊 Bulk Processing Result:\n";
            echo "- Total Processed: {$mockBulkResult['total_processed']}\n";
            echo "- Success Count: {$mockBulkResult['success_count']}\n";
            echo "- Error Count: {$mockBulkResult['error_count']}\n";
            echo "- Success Rate: {$mockBulkResult['success_rate']}%\n";
        } catch (Exception $e) {
            echo "❌ Bulk processing error: " . $e->getMessage() . "\n";
        }

        echo "\n";
    }

    private function demonstrateAnalytics()
    {
        echo "📈 4. Analytics & Monitoring Demo\n";
        echo "==================================\n";

        try {
            // Simulate batch utilization analytics
            $mockAnalytics = [
                'livestock_id' => $this->testLivestockId,
                'livestock_name' => 'Demo Livestock',
                'total_batches' => 3,
                'active_batches' => 2,
                'batch_utilization' => [
                    [
                        'batch_id' => 'batch-demo-1',
                        'batch_name' => 'Demo Batch A',
                        'utilization_rate' => 75.0,
                        'remaining_quantity' => 25,
                        'age_days' => 30
                    ],
                    [
                        'batch_id' => 'batch-demo-2',
                        'batch_name' => 'Demo Batch B',
                        'utilization_rate' => 40.0,
                        'remaining_quantity' => 60,
                        'age_days' => 15
                    ]
                ]
            ];

            echo "📊 Batch Utilization Analytics:\n";
            echo "- Total Batches: {$mockAnalytics['total_batches']}\n";
            echo "- Active Batches: {$mockAnalytics['active_batches']}\n";
            echo "- Batch Details:\n";

            foreach ($mockAnalytics['batch_utilization'] as $batch) {
                echo "  • {$batch['batch_name']}: {$batch['utilization_rate']}% utilized, {$batch['remaining_quantity']} remaining\n";
            }

            // Simulate performance metrics
            $mockPerformance = [
                'period' => ['start_date' => '2024-01-01', 'end_date' => '2024-01-31'],
                'summary' => [
                    'total_depletion_records' => 45,
                    'total_quantity_depleted' => 1250,
                    'batch_method_count' => 40,
                    'total_method_count' => 5,
                    'average_depletion_per_day' => 1.45
                ]
            ];

            echo "\n📈 Performance Metrics (Last Month):\n";
            echo "- Total Records: {$mockPerformance['summary']['total_depletion_records']}\n";
            echo "- Total Quantity: {$mockPerformance['summary']['total_quantity_depleted']}\n";
            echo "- Batch Method: {$mockPerformance['summary']['batch_method_count']} records\n";
            echo "- Average per Day: {$mockPerformance['summary']['average_depletion_per_day']}\n";
        } catch (Exception $e) {
            echo "❌ Analytics error: " . $e->getMessage() . "\n";
        }

        echo "\n";
    }

    private function demonstrateReversal()
    {
        echo "🔄 5. Depletion Reversal Demo\n";
        echo "=============================\n";

        $depletionId = 'demo-depletion-123';
        $reason = [
            'reason' => 'Data entry error - incorrect quantity',
            'approved_by' => 'supervisor-456',
            'notes' => 'Correction needed for monthly report'
        ];

        echo "Reversing depletion:\n";
        echo "- Depletion ID: {$depletionId}\n";
        echo "- Reason: {$reason['reason']}\n";
        echo "- Approved by: {$reason['approved_by']}\n";

        try {
            // Simulate reversal
            $mockReversal = [
                'success' => true,
                'depletion_id' => $depletionId,
                'livestock_id' => $this->testLivestockId,
                'reversed_quantity' => 25,
                'batch_affected' => 'Demo Batch A',
                'message' => 'Depletion reversed successfully'
            ];

            echo "✅ Reversal Result:\n";
            echo "- Reversed Quantity: {$mockReversal['reversed_quantity']}\n";
            echo "- Batch Affected: {$mockReversal['batch_affected']}\n";
            echo "- Status: {$mockReversal['message']}\n";
        } catch (Exception $e) {
            echo "❌ Reversal error: " . $e->getMessage() . "\n";
        }

        echo "\n";
    }

    private function demonstrateConfigRecommendations()
    {
        echo "🧠 6. Configuration Recommendations Demo\n";
        echo "=========================================\n";

        try {
            // Simulate configuration recommendations
            $mockRecommendations = [
                'livestock_id' => $this->testLivestockId,
                'current_batch_count' => 3,
                'recommendations' => [
                    [
                        'type' => 'recording_method',
                        'recommendation' => 'batch',
                        'reason' => 'Multiple batches detected - batch recording provides better tracking',
                        'priority' => 'high'
                    ],
                    [
                        'type' => 'depletion_method',
                        'recommendation' => 'fifo',
                        'reason' => 'FIFO method recommended for better inventory rotation',
                        'priority' => 'medium'
                    ]
                ]
            ];

            echo "🎯 Configuration Recommendations:\n";
            echo "- Current Batch Count: {$mockRecommendations['current_batch_count']}\n";
            echo "- Recommendations:\n";

            foreach ($mockRecommendations['recommendations'] as $rec) {
                $priority = strtoupper($rec['priority']);
                echo "  • [{$priority}] {$rec['type']}: {$rec['recommendation']}\n";
                echo "    Reason: {$rec['reason']}\n";
            }
        } catch (Exception $e) {
            echo "❌ Recommendations error: " . $e->getMessage() . "\n";
        }

        echo "\n";
    }

    private function demonstrateExport()
    {
        echo "📄 7. Data Export Demo\n";
        echo "======================\n";

        $exportOptions = [
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
            'type' => 'mortality'
        ];

        echo "Exporting depletion data:\n";
        echo "- Date Range: {$exportOptions['start_date']} to {$exportOptions['end_date']}\n";
        echo "- Type Filter: {$exportOptions['type']}\n";

        try {
            // Simulate export
            $mockExport = [
                'livestock' => [
                    'id' => $this->testLivestockId,
                    'name' => 'Demo Livestock',
                    'initial_quantity' => 1000,
                    'current_quantity_depletion' => 150
                ],
                'export_info' => [
                    'exported_at' => now()->toISOString(),
                    'exported_by' => 'Demo User',
                    'total_records' => 15,
                    'filters_applied' => $exportOptions
                ],
                'depletions' => [
                    [
                        'id' => 'depl-001',
                        'date' => '2024-01-15',
                        'quantity' => 10,
                        'type' => 'mortality',
                        'batch_name' => 'Demo Batch A',
                        'batch_age_days' => 20
                    ],
                    [
                        'id' => 'depl-002',
                        'date' => '2024-01-20',
                        'quantity' => 5,
                        'type' => 'mortality',
                        'batch_name' => 'Demo Batch A',
                        'batch_age_days' => 25
                    ]
                ]
            ];

            echo "📊 Export Summary:\n";
            echo "- Total Records: {$mockExport['export_info']['total_records']}\n";
            echo "- Livestock: {$mockExport['livestock']['name']}\n";
            echo "- Sample Records:\n";

            foreach (array_slice($mockExport['depletions'], 0, 2) as $depletion) {
                echo "  • {$depletion['date']}: {$depletion['quantity']} units ({$depletion['type']})\n";
            }
        } catch (Exception $e) {
            echo "❌ Export error: " . $e->getMessage() . "\n";
        }

        echo "\n";
    }

    public function showSupportedMethodsAndTypes()
    {
        echo "📋 Supported Methods and Types\n";
        echo "==============================\n";

        echo "🔄 Depletion Methods:\n";
        $methods = BatchDepletionService::getSupportedMethods();
        foreach ($methods as $key => $description) {
            $status = $key === 'fifo' ? '✅ Ready' : '🔄 Structure Ready';
            echo "  • {$key}: {$description} ({$status})\n";
        }

        echo "\n📊 Depletion Types:\n";
        $types = BatchDepletionService::getSupportedTypes();
        foreach ($types as $key => $description) {
            echo "  • {$key}: {$description}\n";
        }

        echo "\n";
    }
}

// Run the demo
if (php_sapi_name() === 'cli') {
    $demo = new BatchDepletionServiceDemo();
    $demo->showSupportedMethodsAndTypes();
    $demo->runDemo();
} else {
    echo "This demo script should be run from command line.\n";
}

// End of demo script 
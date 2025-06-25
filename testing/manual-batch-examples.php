<?php

/**
 * Manual Batch Selection Examples
 * Demonstrasi berbagai penggunaan metode manual dalam BatchDepletionService
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\Livestock\BatchDepletionService;

echo "=== MANUAL BATCH SELECTION EXAMPLES ===\n\n";

$service = new BatchDepletionService();

echo "📋 Available Depletion Methods:\n";
foreach (BatchDepletionService::getSupportedMethods() as $key => $name) {
    echo "  - {$key}: {$name}\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "CONTOH 1: GET AVAILABLE BATCHES FOR MANUAL SELECTION\n";
echo str_repeat("=", 80) . "\n";

echo "Step 1: Mendapatkan daftar batch tersedia\n";
try {
    $availableBatches = $service->getAvailableBatchesForManualSelection('livestock-123');
    echo "✅ Available Batches Response:\n";
    print_r($availableBatches);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "CONTOH 2: SINGLE BATCH MANUAL SELECTION\n";
echo str_repeat("=", 80) . "\n";

$singleBatchData = [
    'livestock_id' => 'livestock-123',
    'type' => 'mortality',
    'date' => '2024-01-15',
    'depletion_method' => 'manual',
    'manual_batches' => [
        [
            'batch_id' => 'batch-002',
            'quantity' => 25,
            'note' => 'Health issues detected in this batch'
        ]
    ],
    'reason' => 'Targeted mortality removal from problematic batch'
];

echo "Request Data:\n";
print_r($singleBatchData);

echo "\nStep 1: Preview Manual Depletion\n";
try {
    $preview = $service->previewManualBatchDepletion($singleBatchData);
    echo "✅ Preview Response:\n";
    print_r($preview);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\nStep 2: Process Manual Depletion\n";
try {
    $result = $service->processDepletion($singleBatchData);
    echo "✅ Processing Response:\n";
    print_r($result);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "CONTOH 3: MULTIPLE BATCH MANUAL SELECTION\n";
echo str_repeat("=", 80) . "\n";

$multipleBatchData = [
    'livestock_id' => 'livestock-456',
    'type' => 'culling',
    'date' => '2024-01-15',
    'depletion_method' => 'manual',
    'manual_batches' => [
        [
            'batch_id' => 'batch-001',
            'quantity' => 15,
            'note' => 'Low performance batch - first priority'
        ],
        [
            'batch_id' => 'batch-004',
            'quantity' => 20,
            'note' => 'Health concerns identified'
        ],
        [
            'batch_id' => 'batch-007',
            'quantity' => 10,
            'note' => 'Age-related culling'
        ]
    ],
    'reason' => 'Selective culling based on performance and health metrics'
];

echo "Request Data (Multiple Batches):\n";
print_r($multipleBatchData);

echo "\nPreview Multiple Batch Depletion:\n";
try {
    $preview = $service->previewManualBatchDepletion($multipleBatchData);
    echo "✅ Preview Response:\n";
    print_r($preview);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "CONTOH 4: SALES WITH MANUAL BATCH SELECTION\n";
echo str_repeat("=", 80) . "\n";

$salesData = [
    'livestock_id' => 'livestock-789',
    'type' => 'sales',
    'date' => '2024-01-15',
    'depletion_method' => 'manual',
    'manual_batches' => [
        [
            'batch_id' => 'batch-003',
            'quantity' => 50,
            'note' => 'Premium batch - highest market price'
        ],
        [
            'batch_id' => 'batch-005',
            'quantity' => 30,
            'note' => 'Good quality - ready for market'
        ]
    ],
    'reason' => 'Strategic sales from best performing batches'
];

echo "Sales Request Data:\n";
print_r($salesData);

echo "\nProcess Sales with Manual Selection:\n";
try {
    $result = $service->processDepletion($salesData);
    echo "✅ Sales Processing Response:\n";
    print_r($result);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "CONTOH 5: MUTATION WITH MANUAL BATCH SELECTION\n";
echo str_repeat("=", 80) . "\n";

$mutationData = [
    'livestock_id' => 'livestock-101',
    'type' => 'mutation',
    'date' => '2024-01-15',
    'depletion_method' => 'manual',
    'manual_batches' => [
        [
            'batch_id' => 'batch-008',
            'quantity' => 40,
            'note' => 'Transfer to new facility - youngest batch'
        ]
    ],
    'reason' => 'Facility expansion - moving specific batch'
];

echo "Mutation Request Data:\n";
print_r($mutationData);

echo "\nProcess Mutation with Manual Selection:\n";
try {
    $result = $service->processDepletion($mutationData);
    echo "✅ Mutation Processing Response:\n";
    print_r($result);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "CONTOH 6: ERROR HANDLING - INSUFFICIENT QUANTITY\n";
echo str_repeat("=", 80) . "\n";

$errorData = [
    'livestock_id' => 'livestock-123',
    'type' => 'sales',
    'depletion_method' => 'manual',
    'manual_batches' => [
        [
            'batch_id' => 'batch-001',
            'quantity' => 999999, // Quantity terlalu besar
            'note' => 'This will cause an error'
        ]
    ]
];

echo "Error Test Data (Insufficient Quantity):\n";
print_r($errorData);

echo "\nTesting Error Handling:\n";
try {
    $preview = $service->previewManualBatchDepletion($errorData);
    echo "Preview Response:\n";
    print_r($preview);
} catch (Exception $e) {
    echo "❌ Expected Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "CONTOH 7: ERROR HANDLING - INVALID BATCH ID\n";
echo str_repeat("=", 80) . "\n";

$invalidBatchData = [
    'livestock_id' => 'livestock-123',
    'type' => 'mortality',
    'depletion_method' => 'manual',
    'manual_batches' => [
        [
            'batch_id' => 'nonexistent-batch-999',
            'quantity' => 10,
            'note' => 'This batch does not exist'
        ]
    ]
];

echo "Invalid Batch Test Data:\n";
print_r($invalidBatchData);

echo "\nTesting Invalid Batch Error:\n";
try {
    $preview = $service->previewManualBatchDepletion($invalidBatchData);
    echo "Preview Response:\n";
    print_r($preview);
} catch (Exception $e) {
    echo "❌ Expected Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "CONTOH 8: COMPARISON - FIFO vs MANUAL\n";
echo str_repeat("=", 80) . "\n";

// FIFO Method
$fifoData = [
    'livestock_id' => 'livestock-123',
    'quantity' => 100,
    'type' => 'sales',
    'depletion_method' => 'fifo'
];

echo "FIFO Method (Automatic):\n";
print_r($fifoData);

echo "\nFIFO Preview:\n";
try {
    $fifoPreview = $service->previewDepletion($fifoData);
    echo "✅ FIFO Preview:\n";
    print_r($fifoPreview);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// Manual Method for same scenario
$manualEquivalent = [
    'livestock_id' => 'livestock-123',
    'type' => 'sales',
    'depletion_method' => 'manual',
    'manual_batches' => [
        ['batch_id' => 'batch-001', 'quantity' => 50, 'note' => 'Manual selection - oldest batch'],
        ['batch_id' => 'batch-002', 'quantity' => 30, 'note' => 'Manual selection - second oldest'],
        ['batch_id' => 'batch-003', 'quantity' => 20, 'note' => 'Manual selection - remaining quantity']
    ]
];

echo "\nManual Method (User Control):\n";
print_r($manualEquivalent);

echo "\nManual Preview:\n";
try {
    $manualPreview = $service->previewManualBatchDepletion($manualEquivalent);
    echo "✅ Manual Preview:\n";
    print_r($manualPreview);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "CONTOH 9: BULK MANUAL DEPLETION\n";
echo str_repeat("=", 80) . "\n";

$bulkManualData = [
    [
        'livestock_id' => 'livestock-001',
        'type' => 'culling',
        'depletion_method' => 'manual',
        'manual_batches' => [
            ['batch_id' => 'batch-A1', 'quantity' => 15, 'note' => 'Health issues']
        ]
    ],
    [
        'livestock_id' => 'livestock-002',
        'type' => 'sales',
        'depletion_method' => 'manual',
        'manual_batches' => [
            ['batch_id' => 'batch-B1', 'quantity' => 25, 'note' => 'Premium batch'],
            ['batch_id' => 'batch-B2', 'quantity' => 10, 'note' => 'Good quality']
        ]
    ],
    [
        'livestock_id' => 'livestock-003',
        'type' => 'mortality',
        'depletion_method' => 'manual',
        'manual_batches' => [
            ['batch_id' => 'batch-C1', 'quantity' => 5, 'note' => 'Disease outbreak']
        ]
    ]
];

echo "Bulk Manual Depletion Data:\n";
foreach ($bulkManualData as $index => $data) {
    echo "  Request " . ($index + 1) . ":\n";
    print_r($data);
    echo "\n";
}

echo "Processing Bulk Manual Depletion:\n";
try {
    $bulkResult = $service->processBulkDepletion($bulkManualData);
    echo "✅ Bulk Processing Result:\n";
    print_r($bulkResult);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "SUMMARY & BEST PRACTICES\n";
echo str_repeat("=", 80) . "\n";

echo "📝 Key Points for Manual Batch Selection:\n\n";

echo "1. Data Structure Requirements:\n";
echo "   ✅ depletion_method: 'manual'\n";
echo "   ✅ manual_batches: array with batch_id, quantity, note\n";
echo "   ✅ Each batch must exist and be active\n";
echo "   ✅ Sufficient quantity must be available\n\n";

echo "2. Workflow Best Practices:\n";
echo "   ✅ Use getAvailableBatchesForManualSelection() to get batch options\n";
echo "   ✅ Always preview with previewManualBatchDepletion() first\n";
echo "   ✅ Check preview.can_fulfill before processing\n";
echo "   ✅ Handle errors gracefully with try-catch\n\n";

echo "3. Use Cases for Manual Selection:\n";
echo "   ✅ Selective culling based on health assessment\n";
echo "   ✅ Strategic sales from high-performing batches\n";
echo "   ✅ Targeted mortality removal from problematic batches\n";
echo "   ✅ Specific batch mutations for facility transfers\n\n";

echo "4. Validation & Error Handling:\n";
echo "   ✅ Batch ownership validation (must belong to livestock)\n";
echo "   ✅ Quantity availability checks\n";
echo "   ✅ Batch status validation (must be active)\n";
echo "   ✅ Descriptive error messages for troubleshooting\n\n";

echo "5. Audit Trail Benefits:\n";
echo "   ✅ Complete record of user selections\n";
echo "   ✅ Notes for each batch selection stored\n";
echo "   ✅ Metadata includes selection method and user info\n";
echo "   ✅ Full traceability for compliance\n\n";

echo "🎯 Manual selection memberikan kontrol penuh kepada user\n";
echo "   untuk memilih batch yang tepat berdasarkan kondisi spesifik,\n";
echo "   sambil tetap mempertahankan integritas data dan audit trail.\n\n";

echo "DEMO COMPLETED ✅\n";
echo str_repeat("=", 80) . "\n";

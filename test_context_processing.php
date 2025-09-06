<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Test shouldProcessContext
$planningService = app(\App\Services\AiPlanningService::class);

// Gunakan reflection untuk mengakses method private
$reflection = new ReflectionClass($planningService);
$shouldProcessMethod = $reflection->getMethod('shouldProcessContext');
$shouldProcessMethod->setAccessible(true);

// Test dengan strategy dan complexity yang sesuai
$strategy = [
    'optimizations' => ['fast_response'] // tidak ada 'skip_context_analysis'
];
$complexity = 'high';

$shouldProcess = $shouldProcessMethod->invoke($planningService, $strategy, $complexity);

echo "Strategy: " . json_encode($strategy) . "\n";
echo "Complexity: $complexity\n";
echo "Should Process Context: " . ($shouldProcess ? 'YES' : 'NO') . "\n";

// Test dengan complexity minimal
$complexity2 = 'minimal';
$shouldProcess2 = $shouldProcessMethod->invoke($planningService, $strategy, $complexity2);
echo "\nComplexity: $complexity2\n";
echo "Should Process Context: " . ($shouldProcess2 ? 'YES' : 'NO') . "\n";

// Test dengan skip_context_analysis
$strategy3 = [
    'optimizations' => ['fast_response', 'skip_context_analysis']
];
$shouldProcess3 = $shouldProcessMethod->invoke($planningService, $strategy3, 'high');
echo "\nStrategy with skip_context_analysis: " . json_encode($strategy3) . "\n";
echo "Should Process Context: " . ($shouldProcess3 ? 'YES' : 'NO') . "\n";

// Test full PRR execution untuk melihat reasoning result
echo "\n=== FULL PRR TEST ===\n";
$userMessage = 'tampilkan daftar perusahaan';
$context = [
    'companies' => [
        'total_companies' => 1,
        'companies' => [
            [
                'id' => '4a5621d0-4814-4762-a7af-89d85f198470',
                'name' => 'System Template',
                'status' => 'active'
            ]
        ]
    ]
];

$prrResult = $planningService->executePRR($userMessage, $context, []);

if ($prrResult['success']) {
    echo "PRR Success: YES\n";
    echo "Planning Query Type: " . $prrResult['planning']['query_type'] . "\n";
    echo "Planning Complexity: " . $prrResult['planning']['complexity'] . "\n";
    echo "Data Needs Database Access: " . ($prrResult['planning']['data_needs']['database_access'] ? 'YES' : 'NO') . "\n";
    echo "Data Needs Context Processing: " . ($prrResult['planning']['data_needs']['context_processing'] ? 'YES' : 'NO') . "\n";
    echo "Reasoning Context Needed: " . ($prrResult['reasoning']['context_needed'] ? 'YES' : 'NO') . "\n";
    echo "Reasoning Response Approach: " . $prrResult['reasoning']['response_approach'] . "\n";
    echo "Relevant Data: " . json_encode($prrResult['reasoning']['relevant_data']) . "\n";
} else {
    echo "PRR Success: NO\n";
    echo "Error: " . ($prrResult['error'] ?? 'unknown') . "\n";
}
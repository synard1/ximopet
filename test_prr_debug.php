<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== DEBUGGING PRR RESPONSE APPROACH ISSUE ===\n\n";

// Test dengan data yang sama seperti di log
$planningService = app(\App\Services\AiPlanningService::class);
$reflection = new ReflectionClass($planningService);

$query = 'tampilkan daftar perusahaan';
echo "Query: $query\n\n";

// 1. Test categorizeQuery
$categorizeMethod = $reflection->getMethod('categorizeQuery');
$categorizeMethod->setAccessible(true);
$queryType = $categorizeMethod->invoke($planningService, $query);
echo "1. QUERY CATEGORIZATION:\n";
echo "Query Type: $queryType\n\n";

// 2. Test dengan context yang sama seperti di log
$context = [
    'companies' => [
        'total_companies' => 1,
        'companies' => [
            [
                'id' => '4a5621d0-4814-4762-a7af-89d85f198470',
                'code' => 'SYSTEM',
                'name' => 'System Template',
                'address' => null,
                'phone' => null,
                'email' => null,
                'status' => 'active',
                'type' => 'system',
                'created_at' => '2025-09-04 17:10:41'
            ]
        ]
    ],
    'farms' => [
        'total_farms' => 0,
        'total_coops' => 0,
        'farm_list' => [],
        'active_farms' => 0,
        'farm_details' => []
    ]
];

echo "2. CONTEXT DATA:\n";
echo "Context keys: " . implode(', ', array_keys($context)) . "\n";
echo "Companies available: " . (isset($context['companies']) ? 'YES' : 'NO') . "\n";
echo "Number of companies: " . (isset($context['companies']['total_companies']) ? $context['companies']['total_companies'] : 0) . "\n\n";

// 3. Test extractRelevantData
$extractMethod = $reflection->getMethod('extractRelevantData');
$extractMethod->setAccessible(true);
$relevantData = $extractMethod->invoke($planningService, $context, $queryType, $query);

echo "3. EXTRACT RELEVANT DATA:\n";
echo "Relevant data extracted: " . (!empty($relevantData) ? 'YES' : 'NO') . "\n";
if (!empty($relevantData)) {
    echo "Relevant data keys: " . implode(', ', array_keys($relevantData)) . "\n";
    if (isset($relevantData['companies'])) {
        echo "Companies in relevant data: " . count($relevantData['companies']['companies']) . "\n";
    }
} else {
    echo "❌ NO RELEVANT DATA EXTRACTED!\n";
    echo "This is the problem - extractRelevantData is not working correctly\n";
}
echo "\n";

// 4. Test applyLogicalReasoning
$reasoningMethod = $reflection->getMethod('applyLogicalReasoning');
$reasoningMethod->setAccessible(true);
$logicalAnalysis = $reasoningMethod->invoke($planningService, $relevantData, $query, $queryType);

echo "4. LOGICAL ANALYSIS:\n";
echo "Data available: " . ($logicalAnalysis['data_available'] ? 'YES' : 'NO') . "\n";
echo "Data quality: " . $logicalAnalysis['data_quality'] . "\n";
echo "Response confidence: " . $logicalAnalysis['response_confidence'] . "\n\n";

// 5. Test determineResponseApproach
$approachMethod = $reflection->getMethod('determineResponseApproach');
$approachMethod->setAccessible(true);
$planningResult = ['complexity' => 'high'];
$responseApproach = $approachMethod->invoke($planningService, $planningResult, $logicalAnalysis);

echo "5. RESPONSE APPROACH DETERMINATION:\n";
echo "Response approach: $responseApproach\n\n";

// 6. Analisis masalah
echo "6. PROBLEM ANALYSIS:\n";
if ($queryType !== 'data_company') {
    echo "❌ PROBLEM: Query not categorized as 'data_company'\n";
    echo "Expected: data_company, Got: $queryType\n";
} else {
    echo "✅ Query categorization is correct\n";
}

if (empty($relevantData)) {
    echo "❌ PROBLEM: No relevant data extracted despite companies being available\n";
    echo "This means extractRelevantData is not working for queryType: $queryType\n";
} else {
    echo "✅ Relevant data extraction is working\n";
}

if (!$logicalAnalysis['data_available']) {
    echo "❌ PROBLEM: Logical analysis says no data available\n";
    echo "This leads to 'no_data_response' approach\n";
} else {
    echo "✅ Logical analysis correctly detects data availability\n";
}

if ($responseApproach === 'no_data_response') {
    echo "❌ PROBLEM: Response approach is 'no_data_response' despite data being available\n";
    echo "This causes AI to refuse showing data\n";
} else {
    echo "✅ Response approach is correct: $responseApproach\n";
}

echo "\n7. SOLUTION NEEDED:\n";
if ($queryType !== 'data_company') {
    echo "- Fix categorizeQuery to properly detect company queries\n";
}
if (empty($relevantData)) {
    echo "- Fix extractRelevantData to properly extract company data\n";
}
if ($responseApproach === 'no_data_response') {
    echo "- Fix determineResponseApproach logic\n";
}

echo "\n=== END DEBUG ===\n";
<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Test categorizeQuery untuk 'tampilkan daftar perusahaan'
$planningService = app(\App\Services\AiPlanningService::class);

// Gunakan reflection untuk mengakses method private
$reflection = new ReflectionClass($planningService);
$categorizeMethod = $reflection->getMethod('categorizeQuery');
$categorizeMethod->setAccessible(true);

$query = 'tampilkan daftar perusahaan';
$queryType = $categorizeMethod->invoke($planningService, $query);

echo "Query: $query\n";
echo "Query Type: $queryType\n";

// Test extractRelevantData
$extractMethod = $reflection->getMethod('extractRelevantData');
$extractMethod->setAccessible(true);

// Simulasi context yang berisi data perusahaan
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

$relevantData = $extractMethod->invoke($planningService, $context, $queryType, $query);

echo "\nContext keys: " . implode(', ', array_keys($context)) . "\n";
echo "Relevant Data: " . json_encode($relevantData, JSON_PRETTY_PRINT) . "\n";

// Test applyLogicalReasoning
$reasoningMethod = $reflection->getMethod('applyLogicalReasoning');
$reasoningMethod->setAccessible(true);

$logicalAnalysis = $reasoningMethod->invoke($planningService, $relevantData, $query, $queryType);
echo "\nLogical Analysis: " . json_encode($logicalAnalysis, JSON_PRETTY_PRINT) . "\n";

// Test determineResponseApproach
$approachMethod = $reflection->getMethod('determineResponseApproach');
$approachMethod->setAccessible(true);

$planningResult = ['complexity' => 'high'];
$responseApproach = $approachMethod->invoke($planningService, $planningResult, $logicalAnalysis);
echo "\nResponse Approach: $responseApproach\n";
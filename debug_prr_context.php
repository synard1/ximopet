<?php

require_once __DIR__ . '/vendor/autoload.php';

// Simulate the context structure from the log
$contextFromLog = [
    "companies" => [
        "companies" => [
            [
                "id" => "4a5621d0-4814-4762-a7af-89d85f198470",
                "name" => "System Template",
                "registered_date" => "2025-09-04",
                "type" => "All Companies Access"
            ]
        ],
        "total" => 1,
        "access_level" => "full"
    ]
];

// Test extractRelevantData logic
function extractRelevantData(array $context, string $queryType, string $userMessage): array
{
    if (empty($context)) return [];

    // Simple relevance extraction based on query type
    $relevantData = [];

    switch ($queryType) {
        case 'data_company':
            if (isset($context['companies'])) $relevantData['companies'] = $context['companies'];
            break;
        case 'data_livestock':
            if (isset($context['livestock'])) $relevantData['livestock'] = $context['livestock'];
            if (isset($context['livestock_details'])) $relevantData['livestock_details'] = $context['livestock_details'];
            break;
        case 'data_financial':
            if (isset($context['financial'])) $relevantData['financial'] = $context['financial'];
            break;
    }

    return $relevantData;
}

// Test applyLogicalReasoning logic
function applyLogicalReasoning(array $data, string $userMessage, string $queryType): array
{
    return [
        'data_available' => !empty($data),
        'data_quality' => assessDataQuality($data),
        'user_intent' => 'comprehensive_list',
        'response_confidence' => calculateResponseConfidence($data, $queryType)
    ];
}

function assessDataQuality(array $data): string
{
    if (empty($data)) return 'no_data';
    if (count($data) === 1 && count($data[array_key_first($data)]) < 3) return 'limited';
    if (count($data) >= 2) return 'good';

    return 'adequate';
}

function calculateResponseConfidence(array $data, string $queryType): float
{
    if (empty($data)) return 0.2;

    $baseConfidence = 0.7;
    if (strpos($queryType, 'data_') === 0 && !empty($data)) $baseConfidence = 0.9;

    return $baseConfidence;
}

function determineResponseApproach(array $planningResult, array $logicalAnalysis): string
{
    if (!$logicalAnalysis['data_available']) return 'no_data_response';
    if ($logicalAnalysis['response_confidence'] < 0.5) return 'cautious_response';
    if ($planningResult['complexity'] === 'minimal') return 'minimal_response';

    return 'confident_detailed_response';
}

// Test the flow
echo "=== DEBUG PRR CONTEXT EXTRACTION ===\n\n";

echo "Original context structure:\n";
print_r($contextFromLog);

echo "\n=== Testing extractRelevantData ===\n";
$relevantData = extractRelevantData($contextFromLog, 'data_company', 'tampilkan semua daftar perusahaan');
echo "Relevant data extracted:\n";
print_r($relevantData);
echo "Is relevant data empty? " . (empty($relevantData) ? 'YES' : 'NO') . "\n";

echo "\n=== Testing applyLogicalReasoning ===\n";
$logicalAnalysis = applyLogicalReasoning($relevantData, 'tampilkan semua daftar perusahaan', 'data_company');
echo "Logical analysis:\n";
print_r($logicalAnalysis);

echo "\n=== Testing determineResponseApproach ===\n";
$planningResult = ['complexity' => 'high'];
$responseApproach = determineResponseApproach($planningResult, $logicalAnalysis);
echo "Response approach: " . $responseApproach . "\n";

echo "\n=== CONCLUSION ===\n";
if ($responseApproach === 'no_data_response') {
    echo "❌ PROBLEM FOUND: Response approach is 'no_data_response' even though data exists!\n";
    echo "This explains why AI gives 'no farm data available' message.\n";
} else {
    echo "✅ Response approach is correct: " . $responseApproach . "\n";
}
<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== DEBUGGING DATA_AVAILABLE ISSUE ===\n\n";

// Simulasi data yang seharusnya tersedia
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

$userMessage = 'tampilkan daftar perusahaan';
$queryType = 'data_company';

echo "1. CONTEXT DATA:\n";
echo "Context keys: " . implode(', ', array_keys($context)) . "\n";
echo "Companies available: " . (isset($context['companies']) ? 'YES' : 'NO') . "\n";
echo "Company count: " . (isset($context['companies']['companies']) ? count($context['companies']['companies']) : 0) . "\n\n";

// Test extractRelevantData
echo "2. EXTRACT RELEVANT DATA:\n";
$relevantData = [];
if (!empty($context)) {
    switch ($queryType) {
        case 'data_company':
            if (isset($context['companies'])) {
                $relevantData['companies'] = $context['companies'];
                echo "✅ Companies data extracted\n";
            } else {
                echo "❌ No companies data found\n";
            }
            break;
    }
}

echo "Relevant data keys: " . implode(', ', array_keys($relevantData)) . "\n";
echo "Relevant data empty: " . (empty($relevantData) ? 'YES' : 'NO') . "\n\n";

// Test data_available logic
echo "3. DATA_AVAILABLE LOGIC:\n";
$data_available = !empty($relevantData);
echo "!empty(\$relevantData): " . ($data_available ? 'TRUE' : 'FALSE') . "\n";

// Test assessDataQuality
echo "\n4. ASSESS DATA QUALITY:\n";
$dataQuality = 0.0;
if (!empty($relevantData)) {
    $totalItems = 0;
    $qualityScore = 0;
    
    foreach ($relevantData as $dataType => $data) {
        if (is_array($data)) {
            if (isset($data['companies']) && is_array($data['companies'])) {
                $totalItems += count($data['companies']);
                foreach ($data['companies'] as $item) {
                    if (is_array($item) && !empty($item['name'])) {
                        $qualityScore += 1;
                    }
                }
            } elseif (is_array($data)) {
                $totalItems += count($data);
                $qualityScore += count($data);
            }
        }
    }
    
    if ($totalItems > 0) {
        $dataQuality = min(1.0, $qualityScore / $totalItems);
    }
}

echo "Total items: $totalItems\n";
echo "Quality score: $qualityScore\n";
echo "Data quality: $dataQuality\n";

// Test analyzeUserIntent
echo "\n5. ANALYZE USER INTENT:\n";
$intentClarity = 0.8; // Default for data queries
echo "Intent clarity: $intentClarity\n";

// Test calculateResponseConfidence
echo "\n6. CALCULATE RESPONSE CONFIDENCE:\n";
$baseConfidence = 0.7;
$dataBonus = $data_available ? 0.2 : 0;
$qualityBonus = $dataQuality * 0.1;
$intentBonus = $intentClarity * 0.1;

$responseConfidence = min(1.0, $baseConfidence + $dataBonus + $qualityBonus + $intentBonus);

echo "Base confidence: $baseConfidence\n";
echo "Data bonus: $dataBonus\n";
echo "Quality bonus: $qualityBonus\n";
echo "Intent bonus: $intentBonus\n";
echo "Response confidence: $responseConfidence\n";

// Final logical analysis result
echo "\n7. LOGICAL ANALYSIS RESULT:\n";
$logicalAnalysis = [
    'data_available' => $data_available,
    'data_quality' => $dataQuality,
    'intent_clarity' => $intentClarity,
    'response_confidence' => $responseConfidence
];

foreach ($logicalAnalysis as $key => $value) {
    echo "$key: " . (is_bool($value) ? ($value ? 'TRUE' : 'FALSE') : $value) . "\n";
}

// Test determineResponseApproach
echo "\n8. DETERMINE RESPONSE APPROACH:\n";
if (!$logicalAnalysis['data_available']) {
    $responseApproach = 'no_data_response';
    echo "❌ Result: no_data_response (because data_available is FALSE)\n";
} elseif ($logicalAnalysis['response_confidence'] < 0.5) {
    $responseApproach = 'cautious_response';
    echo "⚠️ Result: cautious_response (low confidence)\n";
} else {
    $responseApproach = 'confident_detailed_response';
    echo "✅ Result: confident_detailed_response\n";
}

echo "\n=== CONCLUSION ===\n";
if ($data_available && $responseApproach === 'no_data_response') {
    echo "❌ BUG FOUND: data_available should be TRUE but response_approach is no_data_response\n";
} elseif (!$data_available) {
    echo "❌ ISSUE: data_available is FALSE - need to check why relevant data extraction failed\n";
} else {
    echo "✅ Logic working correctly\n";
}

echo "\n=== END DEBUG ===\n";
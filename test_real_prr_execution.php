<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\AiPlanningService;

echo "=== TESTING REAL PRR EXECUTION ===\n\n";

// Simulasi data yang sama dengan log
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
$metadata = [
    'session_id' => 'test-session',
    'provider' => 'openwebui'
];

try {
    $planningService = app(AiPlanningService::class);
    
    echo "1. EXECUTING REAL PRR...\n";
    $prrResult = $planningService->executePRR($userMessage, $context, $metadata);
    
    echo "\n2. PRR RESULT ANALYSIS:\n";
    if ($prrResult['success']) {
        echo "✅ PRR execution successful\n";
        echo "Response approach: " . $prrResult['reasoning']['response_approach'] . "\n";
        echo "Optimized prompt length: " . strlen($prrResult['response']['optimized_prompt']) . "\n";
    } else {
        echo "❌ PRR execution failed: " . $prrResult['error'] . "\n";
        return;
    }
    
    // Check if prompt contains conflicting instructions
    echo "\n3. PROMPT ANALYSIS:\n";
    $prompt = $prrResult['response']['optimized_prompt'];
    
    if (strpos($prompt, 'no relevant data is available') !== false) {
        echo "❌ FOUND CONFLICTING INSTRUCTION: 'no relevant data is available'\n";
    }
    
    if (strpos($prompt, 'DO NOT show any data') !== false) {
        echo "❌ FOUND CONFLICTING INSTRUCTION: 'DO NOT show any data'\n";
    }
    
    if (strpos($prompt, 'CURRENT SYSTEM DATA') !== false) {
        echo "✅ FOUND DATA INCLUSION: 'CURRENT SYSTEM DATA'\n";
    }
    
    if (strpos($prompt, 'System Template') !== false) {
        echo "✅ FOUND ACTUAL DATA: 'System Template'\n";
    }
    
    echo "\n4. FULL PROMPT (first 500 chars):\n";
    echo substr($prompt, 0, 500) . "...\n";
    
    echo "\n5. PROMPT SECTIONS ANALYSIS:\n";
    $sections = explode('\n\n', $prompt);
    foreach ($sections as $index => $section) {
        if (strlen($section) > 50) {
            echo "Section " . ($index + 1) . ": " . substr($section, 0, 80) . "...\n";
        }
    }
    
    // Check reasoning result details
    if (isset($prrResult['reasoning'])) {
        echo "\n6. REASONING RESULT DETAILS:\n";
        $reasoning = $prrResult['reasoning'];
        foreach ($reasoning as $key => $value) {
            if (is_bool($value)) {
                echo "$key: " . ($value ? 'TRUE' : 'FALSE') . "\n";
            } elseif (is_array($value)) {
                echo "$key: [array with " . count($value) . " items]\n";
            } else {
                echo "$key: $value\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== END TEST ===\n";
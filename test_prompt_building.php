<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Test buildOptimizedPrompt
$planningService = app(\App\Services\AiPlanningService::class);

// Gunakan reflection untuk mengakses method private
$reflection = new ReflectionClass($planningService);
$buildPromptMethod = $reflection->getMethod('buildOptimizedPrompt');
$buildPromptMethod->setAccessible(true);

$userMessage = 'tampilkan daftar perusahaan';

// Simulasi planning result
$planningResult = [
    'language' => 'indonesian',
    'query_type' => 'data_company',
    'complexity' => 'high',
    'strategy' => [
        'tone' => 'professional',
        'length' => 'detailed'
    ]
];

// Simulasi reasoning result
$reasoningResult = [
    'response_approach' => 'confident_detailed_response',
    'relevant_data' => [
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
    ]
];

$optimizedPrompt = $buildPromptMethod->invoke($planningService, $userMessage, $planningResult, $reasoningResult);

echo "=== OPTIMIZED PROMPT ===\n";
echo $optimizedPrompt . "\n";
echo "\n=== PROMPT LENGTH ===\n";
echo "Length: " . strlen($optimizedPrompt) . " characters\n";

// Check if prompt contains data
echo "\n=== DATA CHECK ===\n";
echo "Contains 'System Template': " . (strpos($optimizedPrompt, 'System Template') !== false ? 'YES' : 'NO') . "\n";
echo "Contains 'companies': " . (strpos($optimizedPrompt, 'companies') !== false ? 'YES' : 'NO') . "\n";
echo "Contains data section: " . (strpos($optimizedPrompt, 'Current System Data') !== false ? 'YES' : 'NO') . "\n";
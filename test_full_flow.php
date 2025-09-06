<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    echo "=== TESTING BUILDOPTIMIZEDPROMPT AFTER FIX ===\n";
    
    $message = 'tampilkan daftar perusahaan';
    
    // Get planning service
    $planningService = app('App\\Services\\AiPlanningService');
    
    // Create mock planning result
    $planningResult = [
        'language' => 'id',
        'strategy' => [
            'tone' => 'professional',
            'length' => 'medium'
        ]
    ];
    
    // Create mock reasoning result with relevant data
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
    
    echo "\n=== TESTING buildOptimizedPrompt ===\n";
    
    // Use reflection to access private buildOptimizedPrompt method
    $reflection = new ReflectionClass($planningService);
    $buildPromptMethod = $reflection->getMethod('buildOptimizedPrompt');
    $buildPromptMethod->setAccessible(true);
    
    // Build the optimized prompt
    $optimizedPrompt = $buildPromptMethod->invoke(
        $planningService,
        $message,
        $planningResult,
        $reasoningResult
    );
    
    echo "Prompt built successfully: YES\n";
    echo "Prompt length: " . strlen($optimizedPrompt) . " characters\n";
    
    echo "\n=== DATA INCLUSION CHECK ===\n";
    
    $containsSystemTemplate = strpos($optimizedPrompt, 'System Template') !== false;
    $containsCompanies = strpos($optimizedPrompt, 'companies') !== false;
    $containsCurrentData = strpos($optimizedPrompt, 'CURRENT SYSTEM DATA') !== false;
    $containsJsonData = strpos($optimizedPrompt, '{') !== false && strpos($optimizedPrompt, '}') !== false;
    
    echo "Contains 'System Template': " . ($containsSystemTemplate ? 'YES' : 'NO') . "\n";
    echo "Contains 'companies': " . ($containsCompanies ? 'YES' : 'NO') . "\n";
    echo "Contains 'CURRENT SYSTEM DATA': " . ($containsCurrentData ? 'YES' : 'NO') . "\n";
    echo "Contains JSON data: " . ($containsJsonData ? 'YES' : 'NO') . "\n";
    
    echo "\n=== PROMPT SAMPLE ===\n";
    
    // Find and show the data section
    $dataStart = strpos($optimizedPrompt, 'CURRENT SYSTEM DATA');
    if ($dataStart !== false) {
        $dataSample = substr($optimizedPrompt, $dataStart, 400);
        echo "Data section sample:\n";
        echo "---\n";
        echo $dataSample;
        echo "\n---\n";
    } else {
        echo "No data section found in prompt.\n";
    }
    
    echo "\n=== CONCLUSION ===\n";
    
    if ($containsSystemTemplate && $containsCurrentData && $containsJsonData) {
        echo "✅ SUCCESS: Data is properly included in the prompt!\n";
        echo "The buildOptimizedPrompt fix is working correctly.\n";
        echo "AI should now be able to see and use the company data.\n";
    } else {
        echo "❌ ISSUE: Data is not properly included in the prompt.\n";
        echo "The fix may not be working as expected.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
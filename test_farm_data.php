<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Test the AiDatabaseServiceRefactored directly
echo "Testing AiDatabaseServiceRefactored for farm data...\n";

try {
    // Authenticate as a user first
    $user = \App\Models\User::first();
    if (!$user) {
        echo "No users found in database\n";
        return;
    }
    
    \Illuminate\Support\Facades\Auth::login($user);
    echo "Authenticated as: " . $user->email . "\n";
    
    $aiDbService = app('App\\Services\\AiDatabaseServiceRefactored');
    
    // Test farm query detection
    $query = 'tampilkan semua data dari perusahaan Demo Company';
    
    echo "\n=== TESTING FARM QUERY DETECTION ===\n";
    echo "Query: $query\n";
    
    $data = $aiDbService->searchData($query, []);
    
    echo "\n=== SEARCH RESULTS ===\n";
    echo "Data keys: " . implode(', ', array_keys($data)) . "\n";
    
    if (isset($data['farms'])) {
        echo "\n=== FARM DATA FOUND ===\n";
        echo "Farm data structure:\n";
        print_r($data['farms']);
    } else {
        echo "\n=== NO FARM DATA ===\n";
        echo "Available data: " . json_encode(array_keys($data), JSON_PRETTY_PRINT) . "\n";
    }
    
    // Test reasoning phase dengan executePRR
    echo "\n\n=== TESTING AI PLANNING SERVICE ===\n";
    $aiPlanningService = app('App\\Services\\AiPlanningService');
    
    $context = $data;
    $prrResult = $aiPlanningService->executePRR($query, $context, []);
    
    if ($prrResult['success']) {
        echo "\n=== PRR EXECUTION SUCCESS ===\n";
        echo "Planning Query Type: " . $prrResult['planning']['query_type'] . "\n";
        echo "Planning Complexity: " . $prrResult['planning']['complexity'] . "\n";
        echo "Data Needs Database Access: " . ($prrResult['planning']['data_needs']['database_access'] ? 'YES' : 'NO') . "\n";
        echo "Data Needs Context Processing: " . ($prrResult['planning']['data_needs']['context_processing'] ? 'YES' : 'NO') . "\n";
        echo "Reasoning Context Needed: " . ($prrResult['reasoning']['context_needed'] ? 'YES' : 'NO') . "\n";
        echo "Reasoning Response Approach: " . $prrResult['reasoning']['response_approach'] . "\n";
        echo "Relevant Data Keys: " . implode(', ', array_keys($prrResult['reasoning']['relevant_data'])) . "\n";
        
        if (isset($prrResult['reasoning']['relevant_data']['farms'])) {
            echo "\n=== FARM DATA IN REASONING ===\n";
            echo "Farm data passed to reasoning: YES\n";
            echo "Farm count in reasoning: " . count($prrResult['reasoning']['relevant_data']['farms']) . "\n";
        } else {
            echo "\n=== NO FARM DATA IN REASONING ===\n";
            echo "Available in reasoning: " . json_encode(array_keys($prrResult['reasoning']['relevant_data']), JSON_PRETTY_PRINT) . "\n";
        }
        
        echo "\n=== OPTIMIZED PROMPT PREVIEW ===\n";
        $promptPreview = substr($prrResult['response']['optimized_prompt'], 0, 500);
        echo $promptPreview . "...\n";
        
        // Check if farm data is in the prompt
        if (strpos($prrResult['response']['optimized_prompt'], 'CURRENT SYSTEM DATA') !== false) {
            echo "\n=== FARM DATA IN PROMPT ===\n";
            echo "Farm data included in prompt: YES\n";
        } else {
            echo "\n=== NO FARM DATA IN PROMPT ===\n";
            echo "Farm data included in prompt: NO\n";
        }
    } else {
        echo "\n=== PRR EXECUTION FAILED ===\n";
        echo "Error: " . ($prrResult['error'] ?? 'unknown') . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
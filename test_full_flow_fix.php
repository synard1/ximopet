<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\AiChatService;
use App\Services\AiPlanningService;
use App\Models\ChatSession;
use Illuminate\Support\Facades\Auth;

echo "=== TESTING FULL FLOW WITH FIX ===\n\n";

// Simulasi user login
Auth::loginUsingId('9fcbe4b3-b708-4579-a5e7-1f186bf6fb24');

// Simulasi context yang sama dengan log
$originalContext = "SYSTEM CONTEXT: You are an AI assistant for XiMoPet, a livestock management system specifically designed for poultry farming operations. [Respond in Indonesian/Bahasa Indonesia] SCOPE LIMITATION: You can ONLY provide guidance about livestock management, poultry farming, feed management, farm operations, and related agricultural topics. If users ask about topics outside of livestock/farm management (like Linux servers, IT systems, general technology), you MUST redirect them to ask about farm-related topics instead. ULTRA CRITICAL - NEVER show thinking process, meta-commentary, or use phrases like 'Let me think', 'I should', 'Okay, the user', etc. CRITICAL: State clearly that no relevant data is available. DO NOT show any data or information. Use 'perusahaan' (not 'kompanyi') when referring to companies in Indonesian. Use a professional tone. \n\nUser query: tampilkan daftar perusahaan \n\nContext: You are an AI assistant for XiMoPet livestock management system. You have access to real farm management data and can provide accurate, data-driven responses. \n\nCurrent User Context: \n- Name: Mhd Iqbal Syahputra \n\nGuidelines: \n- Provide accurate responses based on real system data \n- For navigation questions, provide step-by-step UI guidance \n- Be helpful and professional in all interactions \n- Format numbers and data clearly for easy reading \n\n\n=== CURRENT SYSTEM DATA === \n{ \n    \"companies\": { \n        \"companies\": [ \n            { \n                \"id\": \"4a5621d0-4814-4762-a7af-89d85f198470\", \n                \"name\": \"System Template\", \n                \"registered_date\": \"2025-09-04\", \n                \"type\": \"All Companies Access\" \n            } \n        ], \n        \"total\": 1, \n        \"access_level\": \"full\" \n    } \n} \n\nCurrent System Data: \n{ \n    \"companies\": { \n        \"total_companies\": 1, \n        \"companies\": [ \n            { \n                \"id\": \"4a5621d0-4814-4762-a7af-89d85f198470\", \n                \"code\": \"SYSTEM\", \n                \"name\": \"System Template\", \n                \"address\": null, \n                \"phone\": null, \n                \"email\": null, \n                \"status\": \"active\", \n                \"type\": \"system\", \n                \"created_at\": \"2025-09-04 17:10:41\" \n            } \n        ] \n    }, \n    \"farms\": { \n        \"total_farms\": 0, \n        \"total_coops\": 0, \n        \"farm_list\": [], \n        \"active_farms\": 0, \n        \"farm_details\": [] \n    } \n}";

$userMessage = 'tampilkan daftar perusahaan';

try {
    // Simulasi ChatSession
    $session = new ChatSession();
    $session->id = '9fcedbc0-d940-4847-a418-a792d77824a1';
    $session->ai_provider = 'openwebui';
    $session->model_name = 'llama3.2:3b';
    
    echo "1. TESTING PRR EXECUTION...\n";
    $planningService = app(AiPlanningService::class);
    
    // Parse context seperti di AiChatService
    $parsedContext = [];
    if (!empty($originalContext)) {
        if (is_string($originalContext)) {
            if (preg_match('/Current System Data:\s*({.*})/s', $originalContext, $matches)) {
                $jsonData = json_decode($matches[1], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $parsedContext = $jsonData;
                }
            }
        }
    }
    
    echo "Parsed context keys: " . implode(', ', array_keys($parsedContext)) . "\n";
    
    // Execute PRR
    $prrResult = $planningService->executePRR($userMessage, $parsedContext, [
        'session_id' => $session->id,
        'provider' => $session->ai_provider
    ]);
    
    if (!$prrResult['success']) {
        echo "❌ PRR failed: " . $prrResult['error'] . "\n";
        return;
    }
    
    $planning = $prrResult['planning'];
    $reasoning = $prrResult['reasoning'];
    $response = $prrResult['response'];
    
    echo "✅ PRR successful\n";
    echo "Response approach: " . $reasoning['response_approach'] . "\n";
    
    echo "\n2. TESTING PROMPT CONSTRUCTION...\n";
    
    // Simulasi database data (kosong karena tidak ada farms)
    $databaseData = [];
    $context = $originalContext;
    
    if ($planning['data_needs']['database_access'] && $reasoning['context_needed']) {
        echo "Database access needed but no data found\n";
        // Tidak ada data yang ditambahkan ke context
    }
    
    // Use optimized prompt from PRR
    $optimizedPrompt = $response['optimized_prompt'];
    echo "Initial optimized prompt length: " . strlen($optimizedPrompt) . "\n";
    
    // CRITICAL TEST: Check if context is added again
    echo "\n3. TESTING CONTEXT ADDITION LOGIC...\n";
    echo "Context needed: " . ($reasoning['context_needed'] ? 'YES' : 'NO') . "\n";
    echo "Context not empty: " . (!empty($context) ? 'YES' : 'NO') . "\n";
    echo "Response approach: " . $reasoning['response_approach'] . "\n";
    
    $shouldAddContext = $reasoning['context_needed'] && !empty($context) && $reasoning['response_approach'] !== 'confident_detailed_response';
    echo "Should add context: " . ($shouldAddContext ? 'YES' : 'NO') . "\n";
    
    if ($shouldAddContext) {
        $optimizedPrompt .= "\n\nContext: " . $context;
        echo "❌ Context added again - this could cause conflicts\n";
    } else {
        echo "✅ Context NOT added - using PRR optimized prompt only\n";
    }
    
    echo "\nFinal optimized prompt length: " . strlen($optimizedPrompt) . "\n";
    
    echo "\n4. ANALYZING FINAL PROMPT...\n";
    
    // Check for conflicting instructions
    $hasNoDataInstruction = strpos($optimizedPrompt, 'no relevant data is available') !== false;
    $hasDoNotShowData = strpos($optimizedPrompt, 'DO NOT show any data') !== false;
    $hasCurrentSystemData = strpos($optimizedPrompt, 'CURRENT SYSTEM DATA') !== false;
    $hasSystemTemplate = strpos($optimizedPrompt, 'System Template') !== false;
    
    echo "Contains 'no relevant data is available': " . ($hasNoDataInstruction ? 'YES ❌' : 'NO ✅') . "\n";
    echo "Contains 'DO NOT show any data': " . ($hasDoNotShowData ? 'YES ❌' : 'NO ✅') . "\n";
    echo "Contains 'CURRENT SYSTEM DATA': " . ($hasCurrentSystemData ? 'YES ✅' : 'NO ❌') . "\n";
    echo "Contains 'System Template': " . ($hasSystemTemplate ? 'YES ✅' : 'NO ❌') . "\n";
    
    echo "\n5. FINAL ASSESSMENT...\n";
    if (!$hasNoDataInstruction && !$hasDoNotShowData && $hasCurrentSystemData && $hasSystemTemplate) {
        echo "✅ PROMPT IS CORRECT: No conflicting instructions, data is included\n";
        echo "✅ FIX SUCCESSFUL: AI should now provide accurate response with company data\n";
    } else {
        echo "❌ PROMPT STILL HAS ISSUES\n";
        if ($hasNoDataInstruction || $hasDoNotShowData) {
            echo "  - Still contains conflicting 'no data' instructions\n";
        }
        if (!$hasCurrentSystemData || !$hasSystemTemplate) {
            echo "  - Missing actual company data\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== END TEST ===\n";
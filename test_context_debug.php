<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\ChatSession;
use App\Services\AiPlanningService;
use App\Services\ChatContextService;
use App\Services\AiDatabaseServiceRefactored;
use Illuminate\Support\Facades\Auth;

try {
    echo "=== DEBUGGING CONTEXT STRUCTURE ===\n\n";
    
    // Login user
    $user = User::where('email', 'synard1@gmail.com')->first();
    if (!$user) {
        throw new Exception('User not found');
    }
    Auth::login($user);
    echo "✅ User logged in: {$user->name}\n";
    echo "User company_id: {$user->company_id}\n\n";
    
    $userMessage = 'tampilkan daftar perusahaan';
    echo "1. TESTING CONTEXT STRUCTURE...\n";
    echo "User message: '{$userMessage}'\n\n";
    
    // Test AiDatabaseService directly
    echo "2. TESTING AI DATABASE SERVICE...\n";
    $databaseService = app(AiDatabaseServiceRefactored::class);
    
    // Get company data directly
    $companyData = $databaseService->getCompanyData($user);
    echo "Company data from AiDatabaseServiceRefactored:\n";
    echo json_encode($companyData, JSON_PRETTY_PRINT) . "\n\n";
    
    // Test ChatContextService
    echo "3. TESTING CHAT CONTEXT SERVICE...\n";
    $contextService = app(ChatContextService::class);
    
    $nlpResult = $contextService->processNaturalLanguageQuery($userMessage);
    echo "NLP Result:\n";
    echo "- Context type: {$nlpResult['context_type']}\n";
    echo "- Criteria: " . json_encode($nlpResult['criteria']) . "\n\n";
    
    if ($nlpResult['context_type'] !== 'general') {
        $contextData = $contextService->getContextData($nlpResult['context_type'], $nlpResult['criteria']);
        echo "Context data from ChatContextService:\n";
        echo json_encode($contextData, JSON_PRETTY_PRINT) . "\n\n";
    }
    
    // Test what context is passed to PRR
    echo "4. TESTING PRR CONTEXT...\n";
    $planningService = app(AiPlanningService::class);
    
    // Create context array like in AiChatService
    $context = [
        'user_id' => $user->id,
        'company_id' => $user->company_id,
        'session_id' => 'test-session'
    ];
    
    // Add company data directly for PRR analysis (same as AiChatService fix)
    try {
        if (!empty($companyData['companies'])) {
            $context['companies'] = $companyData['companies'];
        }
    } catch (Exception $e) {
        echo "Error adding company data to context: " . $e->getMessage() . "\n";
    }
    
    echo "Context passed to PRR:\n";
    echo json_encode($context, JSON_PRETTY_PRINT) . "\n\n";
    
    // Execute PRR with debug
    $prrResult = $planningService->executePRR($userMessage, $context);
    
    if ($prrResult['success']) {
        echo "5. PRR RESULTS ANALYSIS...\n";
        echo "Response approach: {$prrResult['reasoning']['response_approach']}\n";
        echo "Data available: " . ($prrResult['reasoning']['logical_analysis']['data_available'] ? 'TRUE' : 'FALSE') . "\n";
        echo "Relevant data count: " . count($prrResult['reasoning']['relevant_data']) . "\n";
        echo "Context needed: " . ($prrResult['reasoning']['context_needed'] ? 'TRUE' : 'FALSE') . "\n\n";
        
        echo "Relevant data structure:\n";
        echo json_encode($prrResult['reasoning']['relevant_data'], JSON_PRETTY_PRINT) . "\n\n";
        
        echo "Logical analysis:\n";
        echo json_encode($prrResult['reasoning']['logical_analysis'], JSON_PRETTY_PRINT) . "\n\n";
    } else {
        echo "❌ PRR execution failed: " . ($prrResult['error'] ?? 'Unknown error') . "\n";
    }
    
    echo "=== CONCLUSION ===\n";
    echo "The issue is likely in how context data is structured or extracted.\n";
    
    echo "\n=== END DEBUG ===\n";
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    echo "\n=== END DEBUG ===\n";
}
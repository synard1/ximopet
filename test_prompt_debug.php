<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\ChatSession;
use App\Services\AiChatService;
use App\Services\AiPlanningService;
use App\Services\ChatContextService;
use Illuminate\Support\Facades\Auth;

try {
    echo "=== DEBUGGING PROMPT CONSTRUCTION ===\n\n";
    
    // Login user
    $user = User::where('email', 'synard1@gmail.com')->first();
    if (!$user) {
        throw new Exception('User not found');
    }
    Auth::login($user);
    echo "✅ User logged in: {$user->name}\n";
    
    // Get existing session
    $session = ChatSession::where('user_id', $user->id)->first();
    if (!$session) {
        throw new Exception('No chat session found');
    }
    echo "✅ Chat session found: {$session->id}\n\n";
    
    $userMessage = 'tampilkan daftar perusahaan';
    echo "1. TESTING PROMPT CONSTRUCTION...\n";
    echo "User message: '{$userMessage}'\n\n";
    
    // Initialize services using Laravel container
    $aiChatService = app(AiChatService::class);
    $aiPlanningService = app(AiPlanningService::class);
    $contextService = app(ChatContextService::class);
    
    echo "2. EXECUTING PRR...\n";
    
    // Execute PRR to get optimized prompt
    $context = [
        'user_id' => $user->id,
        'company_id' => $user->company_id,
        'session_id' => $session->id
    ];
    $prrResult = $aiPlanningService->executePRR($userMessage, $context);
    
    if (!$prrResult['success']) {
        throw new Exception('PRR execution failed: ' . ($prrResult['error'] ?? 'Unknown error'));
    }
    
    echo "✅ PRR executed successfully\n";
    echo "Response approach: " . $prrResult['reasoning']['response_approach'] . "\n";
    echo "Data available: " . ($prrResult['reasoning']['logical_analysis']['data_available'] ? 'TRUE' : 'FALSE') . "\n";
    echo "Relevant data count: " . count($prrResult['reasoning']['relevant_data']) . "\n\n";
    
    echo "3. ANALYZING OPTIMIZED PROMPT...\n";
    $optimizedPrompt = $prrResult['response']['optimized_prompt'];
    echo "Optimized prompt length: " . strlen($optimizedPrompt) . " characters\n";
    
    // Check if prompt contains data
    $containsSystemTemplate = strpos($optimizedPrompt, 'System Template') !== false;
    $containsCompanyData = strpos($optimizedPrompt, 'companies') !== false;
    $containsDataInclusion = strpos($optimizedPrompt, 'CURRENT SYSTEM DATA') !== false;
    
    echo "Contains 'System Template': " . ($containsSystemTemplate ? 'YES' : 'NO') . "\n";
    echo "Contains 'companies': " . ($containsCompanyData ? 'YES' : 'NO') . "\n";
    echo "Contains 'CURRENT SYSTEM DATA': " . ($containsDataInclusion ? 'YES' : 'NO') . "\n\n";
    
    echo "4. FULL OPTIMIZED PROMPT:\n";
    echo "=== PROMPT START ===\n";
    echo $optimizedPrompt . "\n";
    echo "=== PROMPT END ===\n\n";
    
    echo "5. CONTEXT SERVICE TEST...\n";
    
    // Test context service directly
    $contextResult = $contextService->processNaturalLanguageQuery($userMessage);
    echo "Context type: " . $contextResult['context_type'] . "\n";
    echo "Context criteria: " . json_encode($contextResult['criteria']) . "\n";
    
    if ($contextResult['context_type'] !== 'general') {
        $contextData = $contextService->getContextData($contextResult['context_type'], $contextResult['criteria']);
        echo "Context data available: " . (empty($contextData) ? 'NO' : 'YES') . "\n";
        if (!empty($contextData)) {
            echo "Context data preview: " . substr(json_encode($contextData), 0, 200) . "...\n";
        }
    }
    
    echo "\n=== CONCLUSION ===\n";
    if ($containsSystemTemplate && $containsDataInclusion) {
        echo "✅ SUCCESS: Prompt contains company data\n";
    } else if ($containsDataInclusion && !$containsSystemTemplate) {
        echo "⚠️  PARTIAL: Prompt has data section but missing specific company data\n";
    } else {
        echo "❌ ISSUE: Prompt missing company data\n";
    }
    
    echo "\n=== END DEBUG ===\n";
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    echo "\n=== END DEBUG ===\n";
}
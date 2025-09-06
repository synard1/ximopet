<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\AiChatService;
use App\Models\ChatSession;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

echo "=== TESTING AI RESPONSE WITH FIX ===\n\n";

try {
    // Simulasi user login
    $user = User::find('9fcbe4b3-b708-4579-a5e7-1f186bf6fb24');
    if (!$user) {
        echo "❌ User not found\n";
        return;
    }
    Auth::login($user);
    echo "✅ User logged in: " . $user->name . "\n";
    
    // Buat atau ambil chat session
    $session = ChatSession::where('user_id', $user->id)
        ->where('ai_provider', 'openwebui')
        ->first();
        
    if (!$session) {
        echo "❌ No chat session found\n";
        return;
    }
    
    echo "✅ Chat session found: " . $session->id . "\n";
    
    $userMessage = 'tampilkan daftar perusahaan';
    
    echo "\n1. TESTING AI CHAT SERVICE...\n";
    $aiChatService = app(AiChatService::class);
    
    // Test dengan message yang sama seperti di log
    echo "Sending message: '$userMessage'\n";
    
    // Simulasi context yang akan diberikan oleh ContextService
    $context = "SYSTEM CONTEXT: You are an AI assistant for XiMoPet, a livestock management system specifically designed for poultry farming operations. [Respond in Indonesian/Bahasa Indonesia] SCOPE LIMITATION: You can ONLY provide guidance about livestock management, poultry farming, feed management, farm operations, and related agricultural topics. If users ask about topics outside of livestock/farm management (like Linux servers, IT systems, general technology), you MUST redirect them to ask about farm-related topics instead. ULTRA CRITICAL - NEVER show thinking process, meta-commentary, or use phrases like 'Let me think', 'I should', 'Okay, the user', etc. CRITICAL: State clearly that no relevant data is available. DO NOT show any data or information. Use 'perusahaan' (not 'kompanyi') when referring to companies in Indonesian. Use a professional tone. \n\nUser query: tampilkan daftar perusahaan \n\nContext: You are an AI assistant for XiMoPet livestock management system. You have access to real farm management data and can provide accurate, data-driven responses. \n\nCurrent User Context: \n- Name: Mhd Iqbal Syahputra \n\nGuidelines: \n- Provide accurate responses based on real system data \n- For navigation questions, provide step-by-step UI guidance \n- Be helpful and professional in all interactions \n- Format numbers and data clearly for easy reading \n\n\n=== CURRENT SYSTEM DATA === \n{ \n    \"companies\": { \n        \"companies\": [ \n            { \n                \"id\": \"4a5621d0-4814-4762-a7af-89d85f198470\", \n                \"name\": \"System Template\", \n                \"registered_date\": \"2025-09-04\", \n                \"type\": \"All Companies Access\" \n            } \n        ], \n        \"total\": 1, \n        \"access_level\": \"full\" \n    } \n} \n\nCurrent System Data: \n{ \n    \"companies\": { \n        \"total_companies\": 1, \n        \"companies\": [ \n            { \n                \"id\": \"4a5621d0-4814-4762-a7af-89d85f198470\", \n                \"code\": \"SYSTEM\", \n                \"name\": \"System Template\", \n                \"address\": null, \n                \"phone\": null, \n                \"email\": null, \n                \"status\": \"active\", \n                \"type\": \"system\", \n                \"created_at\": \"2025-09-04 17:10:41\" \n            } \n        ] \n    }, \n    \"farms\": { \n        \"total_farms\": 0, \n        \"total_coops\": 0, \n        \"farm_list\": [], \n        \"active_farms\": 0, \n        \"farm_details\": [] \n    } \n}";
    
    echo "\n2. CALLING AI CHAT SERVICE...\n";
    
    // Call the actual service method
    $result = $aiChatService->sendMessage(
        $userMessage,
        $session->id,
        'openwebui',
        'llama3.2:3b',
        [],
        'general'
    );
    
    echo "\n3. ANALYZING RESULT...\n";
    
    if (isset($result['success']) && $result['success'] && isset($result['assistant_message'])) {
        $response = $result['assistant_message']->content;
        echo "✅ Response received\n";
        echo "Response length: " . strlen($response) . " characters\n";
        echo "Processing time: " . ($result['processing_time'] ?? 'N/A') . " seconds\n";
        
        // Check if response contains company data
        $containsSystemTemplate = strpos($response, 'System Template') !== false;
        $containsNoAccess = strpos($response, 'tidak memiliki akses') !== false;
        $containsNoData = strpos($response, 'tidak tersedia') !== false || strpos($response, 'no data') !== false;
        
        echo "\n4. RESPONSE ANALYSIS:\n";
        echo "Contains 'System Template': " . ($containsSystemTemplate ? 'YES ✅' : 'NO ❌') . "\n";
        echo "Contains 'tidak memiliki akses': " . ($containsNoAccess ? 'YES ❌' : 'NO ✅') . "\n";
        echo "Contains 'no data' messages: " . ($containsNoData ? 'YES ❌' : 'NO ✅') . "\n";
        
        echo "\n5. FULL RESPONSE:\n";
        echo "=== AI RESPONSE START ===\n";
        echo $response . "\n";
        echo "=== AI RESPONSE END ===\n";
        
        echo "\n6. FINAL ASSESSMENT:\n";
        if ($containsSystemTemplate && !$containsNoAccess && !$containsNoData) {
            echo "✅ SUCCESS: AI response now includes company data correctly!\n";
            echo "✅ FIX VERIFIED: The issue has been resolved\n";
        } else {
            echo "❌ ISSUE PERSISTS: AI response still not showing company data\n";
            if (!$containsSystemTemplate) {
                echo "  - Missing company name 'System Template'\n";
            }
            if ($containsNoAccess) {
                echo "  - Still saying 'tidak memiliki akses'\n";
            }
            if ($containsNoData) {
                echo "  - Still saying 'no data available'\n";
            }
        }
        
    echo "\n=== RESPONSE CONTENT ===\n";
        echo $response . "\n";
        echo "========================\n";
        
        echo "\n=== ANALYSIS ===\n";
        echo "Contains 'System Template': " . ($containsSystemTemplate ? 'YES' : 'NO') . "\n";
        echo "Contains 'tidak memiliki akses': " . ($containsNoAccess ? 'YES' : 'NO') . "\n";
        echo "Contains 'tidak tersedia/no data': " . ($containsNoData ? 'YES' : 'NO') . "\n";
        
        if ($containsSystemTemplate) {
            echo "\n✅ SUCCESS: AI response now includes company data!\n";
            echo "✅ FIX VERIFIED: The context duplication issue has been resolved.\n";
        } else if ($containsNoData) {
            echo "\n❌ ISSUE: AI still claims no data available despite data being present.\n";
        } else {
            echo "\n❌ UNCLEAR: Response doesn't clearly indicate data presence or absence.\n";
        }
        
    } else {
        echo "❌ No response received or request failed\n";
        echo "Result keys: " . implode(', ', array_keys($result)) . "\n";
        if (isset($result['success']) && !$result['success']) {
            echo "Error: " . ($result['error'] ?? 'Unknown error') . "\n";
        }
    }
    
    echo "\n=== END TEST ===\n";
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    echo "\n=== END TEST ===\n";
}
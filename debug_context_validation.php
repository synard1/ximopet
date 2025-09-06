<?php

require_once 'vendor/autoload.php';

// Simulate the context data from the log
$contextFromLog = 'SYSTEM CONTEXT: You are an AI assistant for XiMoPet, a livestock management system specifically designed for poultry farming operations. [Respond in Indonesian/Bahasa Indonesia] SCOPE LIMITATION: You can ONLY provide guidance about livestock management, poultry farming, feed management, farm operations, and related agricultural topics. If users ask about topics outside of livestock/farm management (like Linux servers, IT systems, general technology), you MUST redirect them to ask about farm-related topics instead. ULTRA CRITICAL - NEVER show thinking process, meta-commentary, or use phrases like \'Let me think\', \'I should\', \'Okay, the user\', etc. Politely explain that no farm data is available. Use a professional tone. 

User query: tampilkan semua daftar perusahaan 

Context: You are an AI assistant for XiMoPet livestock management system. You have access to real farm management data and can provide accurate, data-driven responses. 

Current User Context: 
- Name: Mhd Iqbal Syahputra 

Guidelines: 
- Provide accurate responses based on real system data 
- For navigation questions, provide step-by-step UI guidance 
- Be helpful and professional in all interactions 
- Format numbers and data clearly for easy reading 


=== CURRENT SYSTEM DATA === 
{ 
    "companies": { 
        "companies": [ 
            { 
                "id": "4a5621d0-4814-4762-a7af-89d85f198470", 
                "name": "System Template", 
                "registered_date": "2025-09-04", 
                "type": "All Companies Access" 
            } 
        ], 
        "total": 1, 
        "access_level": "full" 
    } 
} 

Current System Data: 
{ 
    "companies": { 
        "total_companies": 1, 
        "companies": [ 
            { 
                "id": "4a5621d0-4814-4762-a7af-89d85f198470", 
                "code": "SYSTEM", 
                "name": "System Template", 
                "address": null, 
                "phone": null, 
                "email": null, 
                "status": "active", 
                "type": "system", 
                "created_at": "2025-09-04 17:10:41" 
            } 
        ] 
    }, 
    "farms": { 
        "total_farms": 0, 
        "total_coops": 0, 
        "farm_list": [], 
        "active_farms": 0, 
        "farm_details": [] 
    } 
}';

$userMessage = 'tampilkan semua daftar perusahaan';

echo "=== DEBUG CONTEXT VALIDATION ===\n";
echo "Context length: " . strlen($contextFromLog) . "\n";
echo "User message: " . $userMessage . "\n\n";

// Test the validation logic manually
function validateContextHasData(string $context, string $message): bool
{
    // If context is empty or too short, no data available
    if (empty($context) || strlen(trim($context)) < 20) {
        echo "❌ Context too short or empty\n";
        return false;
    }

    // Clean and normalize context for analysis
    $cleanContext = strtolower(trim($context));
    $cleanMessage = strtolower(trim($message));
    
    echo "Clean message: " . $cleanMessage . "\n";
    echo "Context preview: " . substr($cleanContext, 0, 200) . "...\n\n";

    // Check for company-related queries
    if (preg_match('/\b(perusahaan|company|daftar.*perusahaan|list.*company)\b/', $cleanMessage)) {
        echo "✅ Detected company-related query\n";
        
        // Look for actual company data indicators
        $companyIndicators = [
            'company_name', 'nama_perusahaan', 'company_id',
            'status', 'active', 'inactive', 'created_at',
            'system template', 'template', 'farm_name',
            'total companies:', 'companies:', 'company details:',
            'registered:', 'created:', 'company information',
            '"companies"', '"total_companies"', 'system template'
        ];

        foreach ($companyIndicators as $indicator) {
            if (strpos($cleanContext, strtolower($indicator)) !== false) {
                echo "✅ Found indicator: " . $indicator . "\n";
                return true;
            }
        }
        
        echo "❌ No company indicators found\n";
        
        // Check for JSON-like structure indicating actual data
        if (preg_match('/\{.*\}|\[.*\]/', $context) &&
            (strpos($cleanContext, 'id') !== false || strpos($cleanContext, 'name') !== false)) {
            echo "✅ Found JSON structure with id/name\n";
            return true;
        }
        
        echo "❌ No JSON structure found\n";
    } else {
        echo "❌ Not a company-related query\n";
    }

    return false;
}

$result = validateContextHasData($contextFromLog, $userMessage);
echo "\n=== FINAL RESULT ===\n";
echo "Has data: " . ($result ? 'YES' : 'NO') . "\n";

if (!$result) {
    echo "\n=== DEBUGGING SPECIFIC CHECKS ===\n";
    $cleanContext = strtolower($contextFromLog);
    
    echo "Contains 'companies': " . (strpos($cleanContext, 'companies') !== false ? 'YES' : 'NO') . "\n";
    echo "Contains 'system template': " . (strpos($cleanContext, 'system template') !== false ? 'YES' : 'NO') . "\n";
    echo "Contains JSON braces: " . (preg_match('/\{.*\}/', $contextFromLog) ? 'YES' : 'NO') . "\n";
    echo "Contains 'id': " . (strpos($cleanContext, 'id') !== false ? 'YES' : 'NO') . "\n";
    echo "Contains 'name': " . (strpos($cleanContext, 'name') !== false ? 'YES' : 'NO') . "\n";
}
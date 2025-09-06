<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TESTING CONTEXT PARSING ISSUE ===\n\n";

// Simulasi context yang diterima dari contextService
$originalContext = "SYSTEM CONTEXT: You are an AI assistant for XiMoPet, a livestock management system specifically designed for poultry farming operations. [Respond in Indonesian/Bahasa Indonesia] SCOPE LIMITATION: You can ONLY provide guidance about livestock management, poultry farming, feed management, farm operations, and related agricultural topics. If users ask about topics outside of livestock/farm management (like Linux servers, IT systems, general technology), you MUST redirect them to ask about farm-related topics instead. ULTRA CRITICAL - NEVER show thinking process, meta-commentary, or use phrases like 'Let me think', 'I should', 'Okay, the user', etc. CRITICAL: State clearly that no relevant data is available. DO NOT show any data or information. Use 'perusahaan' (not 'kompanyi') when referring to companies in Indonesian. Use a professional tone. \n\nUser query: tampilkan daftar perusahaan \n\nContext: You are an AI assistant for XiMoPet livestock management system. You have access to real farm management data and can provide accurate, data-driven responses. \n\nCurrent User Context: \n- Name: Mhd Iqbal Syahputra \n\nGuidelines: \n- Provide accurate responses based on real system data \n- For navigation questions, provide step-by-step UI guidance \n- Be helpful and professional in all interactions \n- Format numbers and data clearly for easy reading \n\n\n=== CURRENT SYSTEM DATA === \n{ \n    \"companies\": { \n        \"companies\": [ \n            { \n                \"id\": \"4a5621d0-4814-4762-a7af-89d85f198470\", \n                \"name\": \"System Template\", \n                \"registered_date\": \"2025-09-04\", \n                \"type\": \"All Companies Access\" \n            } \n        ], \n        \"total\": 1, \n        \"access_level\": \"full\" \n    } \n} \n\nCurrent System Data: \n{ \n    \"companies\": { \n        \"total_companies\": 1, \n        \"companies\": [ \n            { \n                \"id\": \"4a5621d0-4814-4762-a7af-89d85f198470\", \n                \"code\": \"SYSTEM\", \n                \"name\": \"System Template\", \n                \"address\": null, \n                \"phone\": null, \n                \"email\": null, \n                \"status\": \"active\", \n                \"type\": \"system\", \n                \"created_at\": \"2025-09-04 17:10:41\" \n            } \n        ] \n    }, \n    \"farms\": { \n        \"total_farms\": 0, \n        \"total_coops\": 0, \n        \"farm_list\": [], \n        \"active_farms\": 0, \n        \"farm_details\": [] \n    } \n}";

echo "1. ORIGINAL CONTEXT LENGTH: " . strlen($originalContext) . "\n\n";

// Test parsing logic yang ada di AiChatService
echo "2. TESTING CURRENT PARSING LOGIC:\n";
$parsedContext = [];
if (!empty($originalContext)) {
    if (is_string($originalContext)) {
        // Extract JSON data from context string
        if (preg_match('/Current System Data:\s*({.*})/s', $originalContext, $matches)) {
            echo "✅ Found 'Current System Data:' pattern\n";
            echo "JSON match length: " . strlen($matches[1]) . "\n";
            $jsonData = json_decode($matches[1], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                echo "✅ JSON parsing successful\n";
                $parsedContext = $jsonData;
            } else {
                echo "❌ JSON parsing failed: " . json_last_error_msg() . "\n";
            }
        } else {
            echo "❌ 'Current System Data:' pattern NOT found\n";
            echo "Trying alternative pattern...\n";
            
            // Try alternative pattern
            if (preg_match('/=== CURRENT SYSTEM DATA ===\s*\n\s*({.*?})\s*\n/s', $originalContext, $matches)) {
                echo "✅ Found alternative pattern\n";
                echo "JSON match: " . substr($matches[1], 0, 100) . "...\n";
                $jsonData = json_decode($matches[1], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    echo "✅ Alternative JSON parsing successful\n";
                    $parsedContext = $jsonData;
                } else {
                    echo "❌ Alternative JSON parsing failed: " . json_last_error_msg() . "\n";
                }
            } else {
                echo "❌ Alternative pattern also NOT found\n";
            }
        }
    }
}

echo "\n3. PARSED CONTEXT RESULT:\n";
if (!empty($parsedContext)) {
    echo "✅ Parsed context is not empty\n";
    echo "Keys: " . implode(', ', array_keys($parsedContext)) . "\n";
    if (isset($parsedContext['companies'])) {
        echo "Companies found: " . count($parsedContext['companies']['companies']) . "\n";
    }
} else {
    echo "❌ Parsed context is EMPTY - This is the problem!\n";
}

echo "\n4. IMPROVED PARSING LOGIC:\n";
// Try a more robust parsing approach
$improvedParsedContext = [];

// Look for any JSON-like structure in the context
if (preg_match_all('/{[^{}]*(?:{[^{}]*}[^{}]*)*}/s', $originalContext, $allMatches)) {
    echo "Found " . count($allMatches[0]) . " JSON-like structures\n";
    
    foreach ($allMatches[0] as $index => $jsonCandidate) {
        $decoded = json_decode($jsonCandidate, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($decoded['companies'])) {
            echo "✅ Found valid companies data in structure #" . ($index + 1) . "\n";
            $improvedParsedContext = $decoded;
            break;
        }
    }
}

if (!empty($improvedParsedContext)) {
    echo "✅ Improved parsing successful!\n";
    echo "Companies found: " . count($improvedParsedContext['companies']['companies']) . "\n";
    echo "Company name: " . $improvedParsedContext['companies']['companies'][0]['name'] . "\n";
} else {
    echo "❌ Even improved parsing failed\n";
}

echo "\n5. SOLUTION NEEDED:\n";
echo "The current parsing logic in AiChatService.php is too restrictive.\n";
echo "It only looks for 'Current System Data:' but the actual pattern is different.\n";
echo "Need to update the regex pattern or use a more robust JSON extraction method.\n";

echo "\n=== END TEST ===\n";
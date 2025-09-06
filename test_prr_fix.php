<?php

require_once 'vendor/autoload.php';

// Simulate the context parsing logic
function parseContextForPRR($context) {
    $parsedContext = [];
    if (!empty($context)) {
        // Try to parse JSON context if it's a string
        if (is_string($context)) {
            // Extract JSON data from context string
            if (preg_match('/Current System Data:\s*({.*})/s', $context, $matches)) {
                $jsonData = json_decode($matches[1], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $parsedContext = $jsonData;
                }
            }
        } else {
            $parsedContext = $context;
        }
    }
    return $parsedContext;
}

// Test with the actual context from the log
$contextFromLog = 'Current System Data: 
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

echo "=== Testing Context Parsing Fix ===\n";
echo "Original context type: " . gettype($contextFromLog) . "\n";
echo "Original context length: " . strlen($contextFromLog) . "\n\n";

$parsedContext = parseContextForPRR($contextFromLog);

echo "Parsed context type: " . gettype($parsedContext) . "\n";
echo "Parsed context structure:\n";
print_r($parsedContext);

echo "\n=== Testing extractRelevantData Logic ===\n";

// Simulate extractRelevantData for 'data_company'
function extractRelevantData($context, $queryType) {
    if (empty($context)) {
        return [];
    }
    
    switch ($queryType) {
        case 'data_company':
            return isset($context['companies']) ? $context['companies'] : [];
        default:
            return $context;
    }
}

$extractedData = extractRelevantData($parsedContext, 'data_company');
echo "Extracted company data:\n";
print_r($extractedData);

echo "\n=== Testing applyLogicalReasoning ===\n";
$dataAvailable = !empty($extractedData);
echo "Data available: " . ($dataAvailable ? 'YES' : 'NO') . "\n";

if ($dataAvailable && isset($extractedData['companies'])) {
    echo "Number of companies found: " . count($extractedData['companies']) . "\n";
    echo "Company names: ";
    foreach ($extractedData['companies'] as $company) {
        echo $company['name'] . ", ";
    }
    echo "\n";
}

echo "\n=== Expected Result ===\n";
echo "With this fix, PRR should now receive proper context data\n";
echo "and determineResponseApproach should return 'confident_detailed_response'\n";
echo "instead of 'no_data_response'\n";
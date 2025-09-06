<?php

require_once 'vendor/autoload.php';

use App\Services\OpenWebUIService;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

class ModelChecker
{
    private $openWebUIService;
    
    public function __construct()
    {
        $this->openWebUIService = app(OpenWebUIService::class);
    }
    
    public function checkAvailableModels()
    {
        echo "\n============================================================\n";
        echo "CHECKING AVAILABLE MODELS ON OPENWEBUI SERVER\n";
        echo "============================================================\n\n";
        
        try {
            // Try to get models list from OpenWebUI API
            $baseUrl = config('ai-chat-v2.providers.openwebui.base_url');
            $apiKey = config('ai-chat-v2.providers.openwebui.api_key');
            
            echo "Base URL: {$baseUrl}\n";
            echo "API Key: " . (empty($apiKey) ? 'NOT SET' : 'SET') . "\n\n";
            
            // Make direct API call to get models
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $baseUrl . '/api/models');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                echo "❌ CURL Error: {$error}\n";
                return;
            }
            
            echo "HTTP Status Code: {$httpCode}\n";
            
            if ($httpCode !== 200) {
                echo "❌ API Error: HTTP {$httpCode}\n";
                echo "Response: {$response}\n";
                return;
            }
            
            $data = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                echo "❌ JSON Parse Error: " . json_last_error_msg() . "\n";
                echo "Raw Response: {$response}\n";
                return;
            }
            
            echo "✅ Successfully connected to OpenWebUI\n\n";
            echo "AVAILABLE MODELS:\n";
            echo "------------------------------\n";
            
            if (isset($data['data']) && is_array($data['data'])) {
                foreach ($data['data'] as $model) {
                    $modelId = $model['id'] ?? 'Unknown';
                    $modelName = $model['name'] ?? $modelId;
                    echo "• {$modelId}\n";
                }
            } else {
                echo "No models found or unexpected response format\n";
                echo "Response structure: " . print_r($data, true) . "\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Exception: " . $e->getMessage() . "\n";
        }
    }
    
    public function testModelConnection($modelId)
    {
        echo "\n\nTesting connection to model: {$modelId}\n";
        echo "--------------------------------------------------\n";
        
        try {
            $startTime = microtime(true);
            
            $response = $this->openWebUIService->sendChatRequest(
                'Hello, please respond with just "OK" to test connection.',
                $modelId,
                ['temperature' => 0.1, 'max_tokens' => 10]
            );
            
            $endTime = microtime(true);
            $processingTime = round(($endTime - $startTime) * 1000, 2);
            
            if (isset($response['response']) && !empty($response['response'])) {
                echo "✅ SUCCESS\n";
                echo "Processing Time: {$processingTime} ms\n";
                echo "Response: " . substr($response['response'], 0, 100) . "\n";
            } else {
                echo "❌ FAILED - Empty response\n";
                echo "Processing Time: {$processingTime} ms\n";
            }
            
        } catch (Exception $e) {
            echo "❌ FAILED - " . $e->getMessage() . "\n";
        }
    }
}

// Run the checker
$checker = new ModelChecker();
$checker->checkAvailableModels();

// Test a few specific models
$testModels = ['llama3.2:3b', 'llama3.1:8b'];
foreach ($testModels as $model) {
    $checker->testModelConnection($model);
}

echo "\n\n============================================================\n";
echo "MODEL CHECK COMPLETED\n";
echo "============================================================\n";
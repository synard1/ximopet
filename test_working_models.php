<?php

require_once 'vendor/autoload.php';

use App\Services\OpenWebUIService;
use App\Services\AiPlanningService;
use App\Services\AiDatabaseServiceRefactored;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

class WorkingModelTester
{
    private $openWebUIService;
    private $planningService;
    private $databaseService;
    private $testQuery = 'Tampilkan semua data perusahaan dan farm yang tersedia';
    private $userId = 1;
    
    // Models to test based on server availability
    private $modelsToTest = [
        'llama3.2:3b',
        'llama3.1:8b',
        'qwen2.5-coder:latest',
        'gemma3:4b',
        'qwen3:1.7b',
        '9-.mistral:7b'
    ];
    
    public function __construct()
    {
        $this->openWebUIService = app(OpenWebUIService::class);
        $this->planningService = app(AiPlanningService::class);
        $this->databaseService = app(AiDatabaseServiceRefactored::class);
    }
    
    public function testModels()
    {
        echo "\n============================================================\n";
        echo "TESTING WORKING MODELS WITH SIMPLE PROMPT\n";
        echo "============================================================\n\n";
        
        $results = [];
        
        foreach ($this->modelsToTest as $model) {
            echo "Testing model: {$model}\n";
            echo "--------------------------------------------------\n";
            
            $startTime = microtime(true);
            
            try {
                // Simple direct test first
                $simpleResponse = $this->testSimplePrompt($model);
                
                if (!empty($simpleResponse)) {
                    echo "✅ Simple test SUCCESS\n";
                    echo "Response length: " . strlen($simpleResponse) . " characters\n";
                    echo "Preview: " . substr($simpleResponse, 0, 100) . "...\n\n";
                    
                    // Now test with full PRR flow
                    $fullResponse = $this->testFullFlow($model);
                    
                    $endTime = microtime(true);
                    $processingTime = round(($endTime - $startTime) * 1000, 2);
                    
                    $results[$model] = [
                        'success' => true,
                        'processing_time' => $processingTime,
                        'simple_response_length' => strlen($simpleResponse),
                        'full_response_length' => strlen($fullResponse),
                        'contains_company_data' => $this->containsCompanyData($fullResponse),
                        'contains_farm_data' => $this->containsFarmData($fullResponse),
                        'full_response_preview' => substr($fullResponse, 0, 200)
                    ];
                    
                    echo "Full flow response length: " . strlen($fullResponse) . " characters\n";
                    echo "Contains company data: " . ($this->containsCompanyData($fullResponse) ? 'YES' : 'NO') . "\n";
                    echo "Contains farm data: " . ($this->containsFarmData($fullResponse) ? 'YES' : 'NO') . "\n";
                    
                } else {
                    echo "❌ Simple test FAILED - Empty response\n";
                    $results[$model] = ['success' => false, 'error' => 'Empty response'];
                }
                
            } catch (Exception $e) {
                echo "❌ FAILED - " . $e->getMessage() . "\n";
                $results[$model] = ['success' => false, 'error' => $e->getMessage()];
            }
            
            echo "\n";
        }
        
        $this->printSummary($results);
    }
    
    private function testSimplePrompt($model)
    {
        $response = $this->openWebUIService->sendChatRequest(
                'Hello, please respond with "Test successful" to confirm you are working.',
                $model,
                ['temperature' => 0.1, 'max_tokens' => 50]
            );
            
            return $response['content'] ?? '';
    }
    
    private function testFullFlow($model)
    {
        // Get context data
        $context = $this->databaseService->searchData($this->testQuery, []);
        
        // Execute PRR
        $prrResult = $this->planningService->executePRR(
            $this->testQuery,
            $context,
            ['user_id' => $this->userId, 'provider' => 'openwebui', 'model' => $model]
        );
        
        if (!$prrResult['success']) {
            throw new Exception('PRR execution failed: ' . ($prrResult['error'] ?? 'Unknown error'));
        }
        
        // Get optimized prompt and parameters
        $optimizedPrompt = $prrResult['response']['optimized_prompt'];
        $modelParams = $prrResult['response']['model_parameters'];
        $modelParams['model'] = $model;
        
        // Send to AI
            $aiResponse = $this->openWebUIService->sendChatRequest(
                $optimizedPrompt,
                $model,
                $modelParams
            );
            
            return $aiResponse['content'] ?? '';
    }
    
    private function containsCompanyData($response)
    {
        $companyKeywords = ['Demo Company', 'DEMO001', 'demo@company.com', 'Jl. Demo'];
        foreach ($companyKeywords as $keyword) {
            if (stripos($response, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }
    
    private function containsFarmData($response)
    {
        $farmKeywords = ['Farm Demo', 'FARM001', 'Kandang', 'farm'];
        foreach ($farmKeywords as $keyword) {
            if (stripos($response, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }
    
    private function printSummary($results)
    {
        echo "\n\n============================================================\n";
        echo "PERFORMANCE SUMMARY\n";
        echo "============================================================\n\n";
        
        $successful = array_filter($results, function($result) {
            return $result['success'] ?? false;
        });
        
        if (!empty($successful)) {
            echo "SUCCESSFUL MODELS:\n";
            echo "------------------------------\n";
            
            foreach ($successful as $model => $result) {
                $companyData = $result['contains_company_data'] ? '✅' : '❌';
                $farmData = $result['contains_farm_data'] ? '✅' : '❌';
                
                printf("%-20s | %8.2f ms | %4d chars | Company: %s | Farm: %s\n",
                    $model,
                    $result['processing_time'],
                    $result['full_response_length'],
                    $companyData,
                    $farmData
                );
            }
            
            // Find best model
            $bestModel = null;
            $bestScore = -1;
            
            foreach ($successful as $model => $result) {
                $score = 0;
                if ($result['contains_company_data']) $score += 2;
                if ($result['contains_farm_data']) $score += 2;
                if ($result['full_response_length'] > 100) $score += 1;
                if ($result['processing_time'] < 10000) $score += 1; // Under 10 seconds
                
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestModel = $model;
                }
            }
            
            if ($bestModel) {
                echo "\nRECOMMENDATIONS:\n";
                echo "------------------------------\n";
                echo "🏆 BEST OVERALL MODEL: {$bestModel}\n";
                echo "   - Processing Time: {$successful[$bestModel]['processing_time']} ms\n";
                echo "   - Response Length: {$successful[$bestModel]['full_response_length']} characters\n";
                echo "   - Company Data: " . ($successful[$bestModel]['contains_company_data'] ? '✅' : '❌') . "\n";
                echo "   - Farm Data: " . ($successful[$bestModel]['contains_farm_data'] ? '✅' : '❌') . "\n";
                echo "\n   Response Preview:\n";
                echo "   " . $successful[$bestModel]['full_response_preview'] . "...\n";
            }
        } else {
            echo "❌ NO SUCCESSFUL MODELS FOUND\n";
        }
        
        $failed = array_filter($results, function($result) {
            return !($result['success'] ?? false);
        });
        
        if (!empty($failed)) {
            echo "\nFAILED MODELS:\n";
            echo "------------------------------\n";
            foreach ($failed as $model => $result) {
                echo "❌ {$model}: " . ($result['error'] ?? 'Unknown error') . "\n";
            }
        }
    }
}

// Run the test
$tester = new WorkingModelTester();
$tester->testModels();

echo "\n\n============================================================\n";
echo "MODEL TESTING COMPLETED\n";
echo "============================================================\n";
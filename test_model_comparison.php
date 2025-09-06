<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\AiPlanningService;
use App\Services\OpenWebUIService;
use App\Services\AiDatabaseServiceRefactored;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ModelTester
{
    private $planningService;
    private $openWebUIService;
    private $databaseService;
    private $testQuery = "tampilkan semua data dari perusahaan Demo Company";
    private $userId = '9fcbe4b3-7d32-4b66-9286-1e1cae1be21d';
    private $sessionId;
    private $logChannel = 'model_testing';

    // Available models to test
    private $modelsToTest = [
        // // Huge
        // '9-.deepseek-r1:32b-qwen-distill-q4_K_M',
        // '9-.codestral:22b',
        // 'qwen2.5-coder:latest',
        // 'qwen3-coder:30b',
        // 'gpt-oss:20b',
        // '9-.qwen3-coder:30b',

        // // Large
        // 'llama3.1:8b',

        // '9-.mistral:7b',

        // Small
        'gemma3:1b',
        'gemma3:270m',
        'qwen3:0.6b',
        'llama3.2:3b',
        'gemma3:4b',
        'qwen3:1.7b',

    ];

    public function __construct()
    {
        $this->planningService = app(AiPlanningService::class);
        $this->openWebUIService = app(OpenWebUIService::class);
        $this->databaseService = app(AiDatabaseServiceRefactored::class);
        $this->sessionId = uniqid('test_session_', true);
        
        // Log session start
        Log::channel($this->logChannel)->info('Model testing session started', [
            'session_id' => $this->sessionId,
            'test_query' => $this->testQuery,
            'user_id' => $this->userId,
            'models_to_test' => $this->modelsToTest,
            'timestamp' => Carbon::now()->toISOString()
        ]);
    }

    public function runTests()
    {
        echo "=== AI Model Performance Comparison ===\n";
        echo "Query: {$this->testQuery}\n";
        echo "User ID: {$this->userId}\n";
        echo "Session ID: {$this->sessionId}\n\n";

        Log::channel($this->logChannel)->info('Starting model tests execution', [
            'session_id' => $this->sessionId,
            'total_models' => count($this->modelsToTest)
        ]);

        $results = [];

        foreach ($this->modelsToTest as $model) {
            echo "Testing model: {$model}\n";
            echo str_repeat('-', 50) . "\n";

            Log::channel($this->logChannel)->info('Starting test for model', [
                'session_id' => $this->sessionId,
                'model' => $model,
                'timestamp' => Carbon::now()->toISOString()
            ]);

            $result = $this->testModel($model);
            $results[$model] = $result;

            // Log individual test result
            Log::channel($this->logChannel)->info('Model test completed', [
                'session_id' => $this->sessionId,
                'model' => $model,
                'success' => $result['success'],
                'processing_time_ms' => $result['processing_time_ms'],
                'response_length' => $result['response_length'],
                'contains_company_data' => $result['contains_company_data'],
                'contains_farm_data' => $result['contains_farm_data'],
                'error' => $result['error'],
                'timestamp' => Carbon::now()->toISOString()
            ]);

            $this->displayResult($model, $result);
            echo "\n";

            // Wait between tests to avoid rate limiting
            sleep(2);
        }

        $this->displaySummary($results);
        $this->logFinalSummary($results);
    }

    private function testModel($model)
    {
        $startTime = microtime(true);

        try {
            Log::channel($this->logChannel)->debug('Getting context data', [
                'session_id' => $this->sessionId,
                'model' => $model,
                'query' => $this->testQuery
            ]);

            // Get context data first
            $context = $this->databaseService->searchData($this->testQuery, []);

            Log::channel($this->logChannel)->debug('Context data retrieved', [
                'session_id' => $this->sessionId,
                'model' => $model,
                'context_count' => is_array($context) ? count($context) : 0
            ]);

            // Test the PRR planning service flow
            $prrResult = $this->planningService->executePRR(
                $this->testQuery,
                $context,
                ['user_id' => $this->userId, 'provider' => 'openwebui', 'model' => $model]
            );

            if (!$prrResult['success']) {
                Log::channel($this->logChannel)->error('PRR execution failed', [
                    'session_id' => $this->sessionId,
                    'model' => $model,
                    'error' => $prrResult['error'] ?? 'Unknown error'
                ]);
                throw new Exception('PRR execution failed: ' . ($prrResult['error'] ?? 'Unknown error'));
            }

            Log::channel($this->logChannel)->debug('PRR execution successful', [
                'session_id' => $this->sessionId,
                'model' => $model
            ]);

            // Get the optimized prompt from PRR result
            $optimizedPrompt = $prrResult['response']['optimized_prompt'];
            $modelParams = $prrResult['response']['model_parameters'];

            // Override model in parameters
            $modelParams['model'] = $model;

            Log::channel($this->logChannel)->debug('Sending request to OpenWebUI', [
                'session_id' => $this->sessionId,
                'model' => $model,
                'prompt_length' => strlen($optimizedPrompt),
                'model_params' => $modelParams
            ]);

            // Send request to OpenWebUI
            $aiResponse = $this->openWebUIService->sendChatRequest(
                $optimizedPrompt,
                $model,
                $modelParams
            );

            $response = [
                'response' => $aiResponse['content'] ?? '',
                'processing_time' => $aiResponse['processing_time'] ?? 0
            ];

            $endTime = microtime(true);
            $processingTime = round(($endTime - $startTime) * 1000, 2); // Convert to milliseconds

            $result = [
                'success' => true,
                'response' => $response['response'] ?? '',
                'processing_time_ms' => $processingTime,
                'response_length' => strlen($response['response'] ?? ''),
                'contains_farm_data' => $this->checkForFarmData($response['response'] ?? ''),
                'contains_company_data' => $this->checkForCompanyData($response['response'] ?? ''),
                'error' => null
            ];

            Log::channel($this->logChannel)->debug('Model test successful', [
                'session_id' => $this->sessionId,
                'model' => $model,
                'response_preview' => substr($response['response'], 0, 100),
                'ai_processing_time' => $response['processing_time'],
                'total_processing_time_ms' => $processingTime
            ]);

            return $result;
        } catch (Exception $e) {
            $endTime = microtime(true);
            $processingTime = round(($endTime - $startTime) * 1000, 2);

            Log::channel($this->logChannel)->error('Model test failed', [
                'session_id' => $this->sessionId,
                'model' => $model,
                'error' => $e->getMessage(),
                'processing_time_ms' => $processingTime,
                'stack_trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'response' => '',
                'processing_time_ms' => $processingTime,
                'response_length' => 0,
                'contains_farm_data' => false,
                'contains_company_data' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function checkForFarmData($response)
    {
        $farmKeywords = ['farm', 'kandang', 'Demo Farm', 'Central', 'North', 'East', 'West', 'South'];

        foreach ($farmKeywords as $keyword) {
            if (stripos($response, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    private function checkForCompanyData($response)
    {
        $companyKeywords = ['Demo Company', 'DEMO', 'perusahaan', 'company'];

        foreach ($companyKeywords as $keyword) {
            if (stripos($response, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    private function displayResult($model, $result)
    {
        if ($result['success']) {
            echo "✅ SUCCESS\n";
            echo "Processing Time: {$result['processing_time_ms']} ms\n";
            echo "Response Length: {$result['response_length']} characters\n";
            echo "Contains Company Data: " . ($result['contains_company_data'] ? 'YES' : 'NO') . "\n";
            echo "Contains Farm Data: " . ($result['contains_farm_data'] ? 'YES' : 'NO') . "\n";

            // Show first 200 characters of response
            $preview = substr($result['response'], 0, 200);
            echo "Response Preview: {$preview}" . (strlen($result['response']) > 200 ? '...' : '') . "\n";
        } else {
            echo "❌ FAILED\n";
            echo "Processing Time: {$result['processing_time_ms']} ms\n";
            echo "Error: {$result['error']}\n";
        }
    }

    private function displaySummary($results)
    {
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "PERFORMANCE SUMMARY\n";
        echo str_repeat('=', 60) . "\n";

        // Sort by success rate and processing time
        $successful = array_filter($results, function ($r) {
            return $r['success'];
        });
        $failed = array_filter($results, function ($r) {
            return !$r['success'];
        });

        echo "\nSUCCESSFUL MODELS:\n";
        echo str_repeat('-', 30) . "\n";

        // Sort successful models by processing time
        uasort($successful, function ($a, $b) {
            return $a['processing_time_ms'] <=> $b['processing_time_ms'];
        });

        foreach ($successful as $model => $result) {
            $farmData = $result['contains_farm_data'] ? '✅' : '❌';
            $companyData = $result['contains_company_data'] ? '✅' : '❌';

            echo sprintf(
                "%-15s | %6s ms | %4d chars | Company: %s | Farm: %s\n",
                $model,
                $result['processing_time_ms'],
                $result['response_length'],
                $companyData,
                $farmData
            );
        }

        if (!empty($failed)) {
            echo "\nFAILED MODELS:\n";
            echo str_repeat('-', 30) . "\n";

            foreach ($failed as $model => $result) {
                echo sprintf(
                    "%-15s | %6s ms | Error: %s\n",
                    $model,
                    $result['processing_time_ms'],
                    substr($result['error'], 0, 50)
                );
            }
        }

        // Recommendations
        echo "\nRECOMMENDATIONS:\n";
        echo str_repeat('-', 30) . "\n";

        if (!empty($successful)) {
            // Find best overall model (considers both speed and data completeness)
            $bestModel = null;
            $bestScore = -1;

            foreach ($successful as $model => $result) {
                // Score: speed (lower is better) + data completeness bonus
                $speedScore = 10000 / max($result['processing_time_ms'], 1); // Higher score for faster
                $dataScore = ($result['contains_company_data'] ? 50 : 0) + ($result['contains_farm_data'] ? 100 : 0);
                $lengthScore = min($result['response_length'] / 10, 50); // Bonus for longer responses, capped

                $totalScore = $speedScore + $dataScore + $lengthScore;

                if ($totalScore > $bestScore) {
                    $bestScore = $totalScore;
                    $bestModel = $model;
                }
            }

            echo "🏆 BEST OVERALL MODEL: {$bestModel}\n";
            echo "   - Processing Time: {$successful[$bestModel]['processing_time_ms']} ms\n";
            echo "   - Response Length: {$successful[$bestModel]['response_length']} characters\n";
            echo "   - Data Completeness: " .
                ($successful[$bestModel]['contains_company_data'] ? 'Company ✅ ' : 'Company ❌ ') .
                ($successful[$bestModel]['contains_farm_data'] ? 'Farm ✅' : 'Farm ❌') . "\n";
        } else {
            echo "❌ No models were successful. Check OpenWebUI connection.\n";
        }
    }

    private function logFinalSummary($results)
    {
        // Separate successful and failed models
        $successful = array_filter($results, function ($r) {
            return $r['success'];
        });
        $failed = array_filter($results, function ($r) {
            return !$r['success'];
        });

        // Find best model
        $bestModel = null;
        $bestScore = -1;
        
        if (!empty($successful)) {
            foreach ($successful as $model => $result) {
                $speedScore = 10000 / max($result['processing_time_ms'], 1);
                $dataScore = ($result['contains_company_data'] ? 50 : 0) + ($result['contains_farm_data'] ? 100 : 0);
                $lengthScore = min($result['response_length'] / 10, 50);
                $totalScore = $speedScore + $dataScore + $lengthScore;

                if ($totalScore > $bestScore) {
                    $bestScore = $totalScore;
                    $bestModel = $model;
                }
            }
        }

        // Log comprehensive summary
        Log::channel($this->logChannel)->info('Model testing session completed', [
            'session_id' => $this->sessionId,
            'total_models_tested' => count($this->modelsToTest),
            'successful_models' => count($successful),
            'failed_models' => count($failed),
            'best_model' => $bestModel,
            'best_model_score' => $bestScore,
            'successful_models_details' => array_map(function($model, $result) {
                return [
                    'model' => $model,
                    'processing_time_ms' => $result['processing_time_ms'],
                    'response_length' => $result['response_length'],
                    'contains_company_data' => $result['contains_company_data'],
                    'contains_farm_data' => $result['contains_farm_data']
                ];
            }, array_keys($successful), $successful),
            'failed_models_details' => array_map(function($model, $result) {
                return [
                    'model' => $model,
                    'processing_time_ms' => $result['processing_time_ms'],
                    'error' => $result['error']
                ];
            }, array_keys($failed), $failed),
            'test_query' => $this->testQuery,
            'user_id' => $this->userId,
            'completion_timestamp' => Carbon::now()->toISOString()
        ]);

        // Log performance statistics
        if (!empty($successful)) {
            $processingTimes = array_column($successful, 'processing_time_ms');
            $responseLengths = array_column($successful, 'response_length');
            
            Log::channel($this->logChannel)->info('Performance statistics', [
                'session_id' => $this->sessionId,
                'avg_processing_time_ms' => round(array_sum($processingTimes) / count($processingTimes), 2),
                'min_processing_time_ms' => min($processingTimes),
                'max_processing_time_ms' => max($processingTimes),
                'avg_response_length' => round(array_sum($responseLengths) / count($responseLengths), 2),
                'min_response_length' => min($responseLengths),
                'max_response_length' => max($responseLengths),
                'models_with_company_data' => count(array_filter($successful, function($r) { return $r['contains_company_data']; })),
                'models_with_farm_data' => count(array_filter($successful, function($r) { return $r['contains_farm_data']; }))
            ]);
        }
    }
}

// Run the tests
try {
    $tester = new ModelTester();
    $tester->runTests();
    
    // Log script completion
    Log::channel('model_testing')->info('Model testing script completed successfully', [
        'timestamp' => Carbon::now()->toISOString()
    ]);
} catch (Exception $e) {
    echo "Error running tests: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    
    // Log script error
    Log::channel('model_testing')->error('Model testing script failed', [
        'error' => $e->getMessage(),
        'stack_trace' => $e->getTraceAsString(),
        'timestamp' => Carbon::now()->toISOString()
    ]);
}

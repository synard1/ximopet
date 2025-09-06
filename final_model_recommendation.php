<?php

require_once 'vendor/autoload.php';

use App\Services\OpenWebUIService;
use App\Services\AiPlanningService;
use App\Services\AiDatabaseServiceRefactored;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

class FinalModelRecommendation
{
    private $openWebUIService;
    private $planningService;
    private $databaseService;
    private $testQuery = 'tampilkan semua data dari perusahaan Demo Company';
    private $userId = 1;
    
    // Top performing models based on previous tests
    private $topModels = [
        'qwen3:1.7b',        // Fastest (27.9s)
        'gemma3:4b',         // Good balance (46.4s)
        'llama3.1:8b',       // Most comprehensive (48.9s)
        'llama3.2:3b'        // Current default (51.2s)
    ];
    
    public function __construct()
    {
        $this->openWebUIService = app(OpenWebUIService::class);
        $this->planningService = app(AiPlanningService::class);
        $this->databaseService = app(AiDatabaseServiceRefactored::class);
    }
    
    public function runFinalComparison()
    {
        echo "\n============================================================\n";
        echo "FINAL MODEL PERFORMANCE COMPARISON\n";
        echo "============================================================\n\n";
        
        $results = [];
        
        foreach ($this->topModels as $model) {
            echo "Testing model: {$model}\n";
            echo "--------------------------------------------------\n";
            
            $results[$model] = $this->runComprehensiveTest($model);
            
            if ($results[$model]['success']) {
                echo "✅ SUCCESS\n";
                echo "Processing Time: {$results[$model]['processing_time']} ms\n";
                echo "Response Length: {$results[$model]['response_length']} characters\n";
                echo "Company Data: " . ($results[$model]['contains_company_data'] ? '✅' : '❌') . "\n";
                echo "Farm Data: " . ($results[$model]['contains_farm_data'] ? '✅' : '❌') . "\n";
                echo "Quality Score: {$results[$model]['quality_score']}/10\n";
            } else {
                echo "❌ FAILED: {$results[$model]['error']}\n";
            }
            
            echo "\n";
        }
        
        $this->generateRecommendations($results);
    }
    
    private function runComprehensiveTest($model)
    {
        $startTime = microtime(true);
        
        try {
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
            
            $response = $aiResponse['content'] ?? '';
            $endTime = microtime(true);
            $processingTime = round(($endTime - $startTime) * 1000, 2);
            
            // Analyze response quality
            $analysis = $this->analyzeResponseQuality($response);
            
            return [
                'success' => true,
                'processing_time' => $processingTime,
                'response_length' => strlen($response),
                'contains_company_data' => $analysis['company_data'],
                'contains_farm_data' => $analysis['farm_data'],
                'quality_score' => $analysis['quality_score'],
                'response_preview' => substr($response, 0, 200),
                'full_response' => $response
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function analyzeResponseQuality($response)
    {
        $score = 0;
        
        // Check for company data
        $companyKeywords = ['Demo Company', 'DEMO', 'demo@company.com', 'Kode Perusahaan'];
        $hasCompanyData = false;
        foreach ($companyKeywords as $keyword) {
            if (stripos($response, $keyword) !== false) {
                $hasCompanyData = true;
                $score += 2;
                break;
            }
        }
        
        // Check for farm data
        $farmKeywords = ['Farm', 'FARM-', 'peternakan', 'kandang'];
        $hasFarmData = false;
        foreach ($farmKeywords as $keyword) {
            if (stripos($response, $keyword) !== false) {
                $hasFarmData = true;
                $score += 2;
                break;
            }
        }
        
        // Check response completeness
        if (strlen($response) > 300) $score += 1;
        if (strlen($response) > 500) $score += 1;
        
        // Check structure quality
        if (strpos($response, '•') !== false || strpos($response, '*') !== false) $score += 1;
        if (preg_match('/\d+/', $response)) $score += 1; // Contains numbers/codes
        
        // Check language appropriateness (Indonesian)
        $indonesianWords = ['perusahaan', 'data', 'informasi', 'berikut', 'nama'];
        foreach ($indonesianWords as $word) {
            if (stripos($response, $word) !== false) {
                $score += 0.5;
                break;
            }
        }
        
        return [
            'company_data' => $hasCompanyData,
            'farm_data' => $hasFarmData,
            'quality_score' => min(10, round($score, 1))
        ];
    }
    
    private function generateRecommendations($results)
    {
        echo "\n\n============================================================\n";
        echo "FINAL RECOMMENDATIONS\n";
        echo "============================================================\n\n";
        
        $successful = array_filter($results, function($result) {
            return $result['success'] ?? false;
        });
        
        if (empty($successful)) {
            echo "❌ NO SUCCESSFUL MODELS FOUND\n";
            return;
        }
        
        // Sort by quality score, then by processing time
        uasort($successful, function($a, $b) {
            if ($a['quality_score'] == $b['quality_score']) {
                return $a['processing_time'] <=> $b['processing_time'];
            }
            return $b['quality_score'] <=> $a['quality_score'];
        });
        
        echo "PERFORMANCE RANKING:\n";
        echo "------------------------------\n";
        
        $rank = 1;
        foreach ($successful as $model => $result) {
            $speedRating = $this->getSpeedRating($result['processing_time']);
            $qualityRating = $this->getQualityRating($result['quality_score']);
            
            echo "{$rank}. {$model}\n";
            echo "   Quality Score: {$result['quality_score']}/10 ({$qualityRating})\n";
            echo "   Processing Time: {$result['processing_time']} ms ({$speedRating})\n";
            echo "   Data Completeness: Company " . ($result['contains_company_data'] ? '✅' : '❌') . " | Farm " . ($result['contains_farm_data'] ? '✅' : '❌') . "\n";
            echo "   Response Length: {$result['response_length']} chars\n\n";
            $rank++;
        }
        
        // Get the best model
        $bestModel = array_key_first($successful);
        $bestResult = $successful[$bestModel];
        
        echo "\n🏆 RECOMMENDED MODEL: {$bestModel}\n";
        echo "=====================================\n";
        echo "Reasons for recommendation:\n";
        echo "• Quality Score: {$bestResult['quality_score']}/10\n";
        echo "• Processing Speed: " . $this->getSpeedRating($bestResult['processing_time']) . "\n";
        echo "• Data Completeness: " . ($bestResult['contains_company_data'] && $bestResult['contains_farm_data'] ? 'Excellent' : 'Good') . "\n";
        echo "• Response Length: {$bestResult['response_length']} characters\n\n";
        
        echo "CONFIGURATION UPDATE:\n";
        echo "------------------------------\n";
        echo "Update your .env file:\n";
        echo "OPENWEBUI_DEFAULT_MODEL={$bestModel}\n\n";
        
        echo "SAMPLE RESPONSE:\n";
        echo "------------------------------\n";
        echo $bestResult['response_preview'] . "...\n\n";
        
        // Alternative recommendations
        $alternatives = array_slice($successful, 1, 2, true);
        if (!empty($alternatives)) {
            echo "ALTERNATIVE OPTIONS:\n";
            echo "------------------------------\n";
            foreach ($alternatives as $model => $result) {
                echo "• {$model}: Quality {$result['quality_score']}/10, Speed " . $this->getSpeedRating($result['processing_time']) . "\n";
            }
        }
    }
    
    private function getSpeedRating($processingTime)
    {
        if ($processingTime < 30000) return 'Very Fast';
        if ($processingTime < 50000) return 'Fast';
        if ($processingTime < 70000) return 'Moderate';
        if ($processingTime < 100000) return 'Slow';
        return 'Very Slow';
    }
    
    private function getQualityRating($score)
    {
        if ($score >= 9) return 'Excellent';
        if ($score >= 7) return 'Good';
        if ($score >= 5) return 'Fair';
        if ($score >= 3) return 'Poor';
        return 'Very Poor';
    }
}

// Run the final comparison
$recommender = new FinalModelRecommendation();
$recommender->runFinalComparison();

echo "\n\n============================================================\n";
echo "MODEL RECOMMENDATION COMPLETED\n";
echo "============================================================\n";
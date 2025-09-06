<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Services\LanguageDetectionService;
use Exception;

/**
 * AI Planning Service implementing Planning-Reasoning-Response (PRR) methodology
 *
 * This service optimizes AI chat by implementing a structured 3-phase approach:
 * 1. PLANNING: Analyze user query and determine optimal response strategy
 * 2. REASONING: Process available data and context logically
 * 3. RESPONSE: Generate structured, contextual responses
 */
class AiPlanningService
{
    protected $languageDetectionService;
    public function __construct(LanguageDetectionService $languageDetectionService)
    {
        $this->languageDetectionService = $languageDetectionService;
    }

    /**
     * Execute the complete Planning-Reasoning-Response cycle
     */
    public function executePRR(string $userMessage, array $context = [], array $metadata = []): array
    {
        $startTime = microtime(true);

        try {
            // PHASE 1: PLANNING
            $planningResult = $this->planningPhase($userMessage, $metadata);

            // PHASE 2: REASONING
            $reasoningResult = $this->reasoningPhase($userMessage, $context, $planningResult);

            // PHASE 3: RESPONSE
            $responseResult = $this->responsePhase($userMessage, $planningResult, $reasoningResult);

            $processingTime = microtime(true) - $startTime;

            return [
                'success' => true,
                'planning' => $planningResult,
                'reasoning' => $reasoningResult,
                'response' => $responseResult,
                'processing_time' => $processingTime,
                'optimized' => true
            ];

        } catch (Exception $e) {
            Log::error('AiPlanningService: PRR execution failed', [
                'error' => $e->getMessage(),
                'user_message' => substr($userMessage, 0, 100),
                'user_id' => Auth::id()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'fallback_to_simple' => true
            ];
        }
    }

    /**
     * PHASE 1: PLANNING - Analyze user query and determine optimal response strategy
     */
    private function planningPhase(string $userMessage, array $metadata = []): array
    {
        $planningStart = microtime(true);

        // Detect language using dedicated service
         $language = $this->languageDetectionService->getLanguage($userMessage);

        // Categorize the query type
        $queryType = $this->categorizeQuery($userMessage);

        // Determine processing complexity needed
        $complexity = $this->determineComplexity($userMessage, $queryType);

        // Determine data needs
        $dataNeeds = $this->determineDataNeeds($userMessage, $queryType);

        // Create response strategy
        $strategy = $this->createResponseStrategy($queryType, $complexity, $language, $dataNeeds);

        $planningTime = microtime(true) - $planningStart;

        return [
            'language' => $language,
            'query_type' => $queryType,
            'complexity' => $complexity,
            'data_needs' => $dataNeeds,
            'strategy' => $strategy,
            'planning_time' => $planningTime,
            'optimizations_applied' => $this->getOptimizationsApplied($complexity, $queryType)
        ];
    }

    /**
     * PHASE 2: REASONING - Process available data and context logically
     */
    private function reasoningPhase(string $userMessage, array $context, array $planningResult): array
    {
        $reasoningStart = microtime(true);

        // Apply reasoning based on planned strategy
        $strategy = $planningResult['strategy'];
        $queryType = $planningResult['query_type'];
        $complexity = $planningResult['complexity'];

        // Determine if we need to process context
        $contextProcessing = $this->shouldProcessContext($strategy, $complexity);

        // Extract relevant data based on reasoning
        $relevantData = $contextProcessing ? $this->extractRelevantData($context, $queryType, $userMessage) : [];

        // Apply logical reasoning to data
        $logicalAnalysis = $this->applyLogicalReasoning($relevantData, $userMessage, $queryType);

        // Determine response approach
        $responseApproach = $this->determineResponseApproach($planningResult, $logicalAnalysis);

        $reasoningTime = microtime(true) - $reasoningStart;

        return [
            'context_needed' => $contextProcessing,
            'relevant_data' => $relevantData,
            'logical_analysis' => $logicalAnalysis,
            'response_approach' => $responseApproach,
            'reasoning_time' => $reasoningTime,
            'data_quality_score' => $this->calculateDataQualityScore($relevantData)
        ];
    }

    /**
     * PHASE 3: RESPONSE - Generate structured, contextual responses
     */
    private function responsePhase(string $userMessage, array $planningResult, array $reasoningResult): array
    {
        $responseStart = microtime(true);

        $language = $planningResult['language'];
        $strategy = $planningResult['strategy'];
        $approach = $reasoningResult['response_approach'];

        // Build optimized prompt based on PRR analysis
        $optimizedPrompt = $this->buildOptimizedPrompt(
            $userMessage,
            $planningResult,
            $reasoningResult
        );

        // Determine AI model parameters based on complexity
        $modelParameters = $this->optimizeModelParameters($planningResult['complexity'], $language);

        // Create response instructions
        $responseInstructions = $this->createResponseInstructions($language, $approach, $strategy);

        $responseTime = microtime(true) - $responseStart;

        return [
            'optimized_prompt' => $optimizedPrompt,
            'model_parameters' => $modelParameters,
            'response_instructions' => $responseInstructions,
            'response_time' => $responseTime,
            'language_instruction' => $this->getLanguageInstruction($language),
            'should_use_simple_model' => $planningResult['complexity'] === 'low'
        ];
    }

    /**
     * Detect language using dedicated service
     */
    private function detectLanguage(string $message): string
    {
        $detectedLanguage = $this->languageDetectionService->getLanguage($message);
        
        // Map language codes to expected format
        return $detectedLanguage === 'id' ? 'indonesian' : 'english';
    }

    /**
     * Check if message is in Indonesian
     */
    private function isIndonesian(string $message): bool
    {
        return $this->languageDetectionService->isIndonesian($message);
    }

    /**
     * Get language detection confidence
     */
    private function getLanguageConfidence(string $message): float
    {
        return $this->languageDetectionService->getConfidence($message);
    }

    /**
     * Categorize query type for optimal processing
     */
    private function categorizeQuery(string $message): string
    {
        $message = strtolower($message);

        // Quick pattern matching for query categorization
        if (preg_match('/\b(hello|hi|hey|halo|thanks|terima|salam)\b/', $message)) {
            return 'greeting_casual';
        }

        if (preg_match('/\b(perusahaan|company|semua.*perusahaan|all.*compan|daftar|list)\b/', $message)) {
            return 'data_company';
        }

        // Enhanced farm query detection with status awareness
        if (preg_match('/\b(peternakan|kandang|farm|coop|fasilitas|facility|lokasi|location|operasi|operation|usaha|business)\b/', $message)) {
            // Detect specific farm status queries
            if (preg_match('/\b(semua farm|all farm|total farm|seluruh farm)\b/', $message)) {
                return 'data_farm_all';
            } elseif (preg_match('/\b(farm tidak aktif|farm nonaktif|farm inactive|farm non-aktif)\b/', $message)) {
                return 'data_farm_inactive';
            } elseif (preg_match('/\b(farm aktif|active farm)\b/', $message)) {
                return 'data_farm_active';
            }
            return 'data_farm';
        }

        if (preg_match('/\b(ternak|livestock|ayam|chicken|bebek|duck)\b/', $message)) {
            return 'data_livestock';
        }

        if (preg_match('/\b(keuangan|financial|untung|profit|rugi|biaya)\b/', $message)) {
            return 'data_financial';
        }

        // Enhanced counting queries with farm status awareness
        if (preg_match('/\b(berapa|how many|jumlah|total|count)\b/', $message)) {
            // Detect specific farm count queries
            if (preg_match('/\b(berapa.*semua farm|berapa.*total farm|jumlah.*semua farm|jumlah.*total farm)\b/', $message)) {
                return 'data_counting_farm_all';
            } elseif (preg_match('/\b(berapa.*farm.*tidak aktif|berapa.*farm.*nonaktif|jumlah.*farm.*tidak aktif|jumlah.*farm.*nonaktif)\b/', $message)) {
                return 'data_counting_farm_inactive';
            } elseif (preg_match('/\b(berapa.*farm.*aktif|jumlah.*farm.*aktif)\b/', $message)) {
                return 'data_counting_farm_active';
            }
            return 'data_counting';
        }

        if (preg_match('/\b(help|bantuan|cara|how to|panduan|optimasi|optimization|server|sistem|aplikasi|application)\b/', $message)) {
            return 'help_guidance';
        }

        return 'general_query';
    }

    /**
     * Determine processing complexity needed
     */
    private function determineComplexity(string $message, string $queryType): string
    {
        // Ultra-fast complexity determination
        switch ($queryType) {
            case 'greeting_casual':
                return 'minimal';
            case 'help_guidance':
            case 'general_query':
                return 'low';
            case 'data_counting':
                return 'medium';
            case 'data_company':
            case 'data_farm':
            case 'data_livestock':
            case 'data_financial':
                return 'high';
            default:
                return 'medium';
        }
    }

    /**
     * Determine what data is needed for this query
     */
    private function determineDataNeeds(string $message, string $queryType): array
    {
        $dataNeeds = [
            'database_access' => false,
            'context_processing' => false,
            'real_time_data' => false,
            'user_permissions' => false
        ];

        switch ($queryType) {
            case 'data_company':
            case 'data_farm':
            case 'data_farm_all':
            case 'data_farm_active':
            case 'data_farm_inactive':
            case 'data_livestock':
            case 'data_financial':
            case 'data_counting':
            case 'data_counting_farm_all':
            case 'data_counting_farm_active':
            case 'data_counting_farm_inactive':
                $dataNeeds['database_access'] = true;
                $dataNeeds['context_processing'] = true;
                $dataNeeds['user_permissions'] = true;
                break;
            case 'help_guidance':
                $dataNeeds['context_processing'] = true;
                break;
        }

        return $dataNeeds;
    }

    /**
     * Create optimal response strategy
     */
    private function createResponseStrategy(string $queryType, string $complexity, string $language, array $dataNeeds): array
    {
        return [
            'approach' => $this->getOptimalApproach($queryType, $complexity),
            'tone' => $this->getOptimalTone($queryType, $language),
            'length' => $this->getOptimalLength($complexity, $queryType),
            'format' => $this->getOptimalFormat($queryType),
            'optimizations' => $this->getStrategyOptimizations($complexity, $dataNeeds)
        ];
    }

    /**
     * Get optimal response approach
     */
    private function getOptimalApproach(string $queryType, string $complexity): string
    {
        if ($complexity === 'minimal') return 'direct_simple';
        if ($queryType === 'greeting_casual') return 'friendly_brief';
        if (strpos($queryType, 'data_') === 0) return 'structured_informative';
        if ($queryType === 'help_guidance') return 'helpful_detailed';

        return 'balanced_contextual';
    }

    /**
     * Get optimal tone
     */
    private function getOptimalTone(string $queryType, string $language): string
    {
        if ($queryType === 'greeting_casual') return 'friendly';
        if (strpos($queryType, 'data_') === 0) return 'professional';
        if ($queryType === 'help_guidance') return 'helpful';

        return 'conversational';
    }

    /**
     * Get optimal response length
     */
    private function getOptimalLength(string $complexity, string $queryType): string
    {
        if ($complexity === 'minimal') return 'very_short';
        if ($queryType === 'greeting_casual') return 'short';
        if (strpos($queryType, 'data_') === 0) return 'detailed';
        if ($queryType === 'help_guidance') return 'comprehensive';

        return 'medium';
    }

    /**
     * Get optimal format
     */
    private function getOptimalFormat(string $queryType): string
    {
        if (strpos($queryType, 'data_') === 0) return 'structured_list';
        if ($queryType === 'help_guidance') return 'step_by_step';

        return 'conversational';
    }

    /**
     * Get strategy optimizations
     */
    private function getStrategyOptimizations(string $complexity, array $dataNeeds): array
    {
        $optimizations = ['fast_response'];

        if ($complexity === 'minimal') {
            $optimizations[] = 'minimal_processing';
        }

        if (!$dataNeeds['database_access']) {
            $optimizations[] = 'no_database_queries';
        }

        if (!$dataNeeds['context_processing']) {
            $optimizations[] = 'skip_context_analysis';
        }

        return $optimizations;
    }

    /**
     * Determine if context processing is needed
     */
    private function shouldProcessContext(array $strategy, string $complexity): bool
    {
        if ($complexity === 'minimal') return false;
        if (in_array('skip_context_analysis', $strategy['optimizations'])) return false;

        return true;
    }

    /**
     * Extract relevant data from context
     */
    private function extractRelevantData(array $context, string $queryType, string $userMessage): array
    {
        if (empty($context)) return [];

        // Simple relevance extraction based on query type
        $relevantData = [];

        switch ($queryType) {
            case 'data_company':
                if (isset($context['companies'])) $relevantData['companies'] = $context['companies'];
                // CRITICAL FIX: Include farm/coop data when asking about company details
                if (isset($context['farms'])) $relevantData['farms'] = $context['farms'];
                break;
            case 'data_livestock':
                if (isset($context['livestock'])) $relevantData['livestock'] = $context['livestock'];
                if (isset($context['livestock_details'])) $relevantData['livestock_details'] = $context['livestock_details'];
                // Include farm data for livestock queries
                if (isset($context['farms'])) $relevantData['farms'] = $context['farms'];
                break;
            case 'data_financial':
                if (isset($context['financial'])) $relevantData['financial'] = $context['financial'];
                break;
            case 'data_farm':
                // New case for farm-specific queries
                if (isset($context['farms'])) $relevantData['farms'] = $context['farms'];
                if (isset($context['companies'])) $relevantData['companies'] = $context['companies'];
                break;
        }

        // CRITICAL FIX: Always include available data for comprehensive queries
        $userMessageLower = strtolower($userMessage);
        if (strpos($userMessageLower, 'semua') !== false || strpos($userMessageLower, 'all') !== false || 
            strpos($userMessageLower, 'tampilkan') !== false || strpos($userMessageLower, 'show') !== false) {
            // For comprehensive queries, include all available data
            if (isset($context['companies'])) $relevantData['companies'] = $context['companies'];
            if (isset($context['farms'])) $relevantData['farms'] = $context['farms'];
            if (isset($context['livestock'])) $relevantData['livestock'] = $context['livestock'];
            if (isset($context['financial'])) $relevantData['financial'] = $context['financial'];
        }

        return $relevantData;
    }

    /**
     * Apply logical reasoning to data
     */
    private function applyLogicalReasoning(array $data, string $userMessage, string $queryType): array
    {
        return [
            'data_available' => !empty($data),
            'data_quality' => $this->assessDataQuality($data),
            'user_intent' => $this->analyzeUserIntent($userMessage, $queryType),
            'response_confidence' => $this->calculateResponseConfidence($data, $queryType)
        ];
    }

    /**
     * Assess data quality
     */
    private function assessDataQuality(array $data): string
    {
        if (empty($data)) return 'no_data';
        if (count($data) === 1 && count($data[array_key_first($data)]) < 3) return 'limited';
        if (count($data) >= 2) return 'good';

        return 'adequate';
    }

    /**
     * Analyze user intent
     */
    private function analyzeUserIntent(string $message, string $queryType): string
    {
        $message = strtolower($message);

        if (strpos($message, 'semua') !== false || strpos($message, 'all') !== false) return 'comprehensive_list';
        if (strpos($message, 'berapa') !== false || strpos($message, 'how many') !== false) return 'count_summary';
        if (strpos($message, 'detail') !== false) return 'detailed_info';

        return 'general_info';
    }

    /**
     * Calculate response confidence
     */
    private function calculateResponseConfidence(array $data, string $queryType): float
    {
        if (empty($data)) return 0.2;

        $baseConfidence = 0.7;
        if (strpos($queryType, 'data_') === 0 && !empty($data)) $baseConfidence = 0.9;

        return $baseConfidence;
    }

    /**
     * Determine response approach
     */
    private function determineResponseApproach(array $planningResult, array $logicalAnalysis): string
    {
        if (!$logicalAnalysis['data_available']) return 'no_data_response';
        if ($logicalAnalysis['response_confidence'] < 0.5) return 'cautious_response';
        if ($planningResult['complexity'] === 'minimal') return 'minimal_response';

        return 'confident_detailed_response';
    }

    /**
     * Build optimized prompt based on PRR analysis
     */
    private function buildOptimizedPrompt(string $userMessage, array $planningResult, array $reasoningResult): string
    {
        $language = $planningResult['language'];
        $approach = $reasoningResult['response_approach'];
        $strategy = $planningResult['strategy'];
        $relevantData = $reasoningResult['relevant_data'] ?? [];

        // Build livestock management system context enforcement
        $prompt = "SYSTEM CONTEXT: You are an AI assistant for XiMoPet, a livestock management system specifically designed for poultry farming operations. ";
        
        // Add language instruction
        $prompt .= $this->getLanguageInstruction($language);
        
        // CRITICAL: Enforce livestock management scope
        $prompt .= "SCOPE LIMITATION: You can ONLY provide guidance about livestock management, poultry farming, feed management, farm operations, and related agricultural topics. ";
        $prompt .= "If users ask about topics outside of livestock/farm management (like Linux servers, IT systems, general technology), ";
        $prompt .= "you MUST redirect them to ask about farm-related topics instead. ";
        
        // Add banned thinking patterns
        $prompt .= "ULTRA CRITICAL - NEVER show thinking process, meta-commentary, or use phrases like 'Let me think', 'I should', 'Okay, the user', etc. ";
        
        // Add optimization instructions based on analysis
        if ($approach === 'minimal_response') {
            $prompt .= "Give a brief, direct response about farm management. ";
        } elseif ($approach === 'no_data_response') {
            $prompt .= "CRITICAL: State clearly that no relevant data is available. DO NOT show any data or information. ";
            $prompt .= "Use 'perusahaan' (not 'kompanyi') when referring to companies in Indonesian. ";
        } elseif ($approach === 'confident_detailed_response') {
            $prompt .= "Provide a comprehensive response based on livestock management principles and the available data. ";
            $prompt .= "Use 'perusahaan' (not 'kompanyi') when referring to companies in Indonesian. ";
            
            // CRITICAL FIX: Include relevant data in prompt for confident detailed responses
            if (!empty($relevantData)) {
                $prompt .= "\n\nCURRENT SYSTEM DATA:\n";
                $prompt .= json_encode($relevantData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                $prompt .= "\n\nIMPORTANT: Use the above data to provide accurate, specific information. If the user asks about companies, livestock, or financial data, reference the actual data provided above. ";
                
                // Add specific instructions for different farm status queries
                if (preg_match('/berapa.*farm.*aktif|jumlah.*farm.*aktif/i', $userMessage)) {
                    if (isset($relevantData['farms']['active_farms'])) {
                        $farmCount = $relevantData['farms']['active_farms'];
                        $prompt .= "\n\nSPECIFIC INSTRUCTION FOR ACTIVE FARM COUNT: You MUST respond with the exact format: 'Saat ini ada {$farmCount} farm aktif' when user asks about number of active farms. ";
                        $prompt .= "Do NOT mention company information unless specifically asked. Focus only on the active farm count. ";
                    }
                } elseif (preg_match('/berapa.*farm.*tidak aktif|berapa.*farm.*nonaktif|jumlah.*farm.*tidak aktif|jumlah.*farm.*nonaktif/i', $userMessage)) {
                    if (isset($relevantData['farms']['inactive_farms'])) {
                        $farmCount = $relevantData['farms']['inactive_farms'];
                        $prompt .= "\n\nSPECIFIC INSTRUCTION FOR INACTIVE FARM COUNT: You MUST respond with the exact format: 'Saat ini ada {$farmCount} farm tidak aktif' when user asks about number of inactive farms. ";
                        $prompt .= "Do NOT mention company information unless specifically asked. Focus only on the inactive farm count. ";
                    }
                } elseif (preg_match('/berapa.*semua farm|berapa.*total farm|jumlah.*semua farm|jumlah.*total farm/i', $userMessage)) {
                    if (isset($relevantData['farms']['all_farms_count'])) {
                        $farmCount = $relevantData['farms']['all_farms_count'];
                        $prompt .= "\n\nSPECIFIC INSTRUCTION FOR TOTAL FARM COUNT: You MUST respond with the exact format: 'Saat ini ada {$farmCount} farm secara total' when user asks about total number of farms. ";
                        $prompt .= "You may also mention the breakdown: '{$relevantData['farms']['active_farms']} farm aktif dan {$relevantData['farms']['inactive_farms']} farm tidak aktif'. ";
                        $prompt .= "Do NOT mention company information unless specifically asked. Focus only on the total farm count and breakdown. ";
                    }
                }
                
                // Add explicit instruction for comprehensive data display
                if (strpos(strtolower($userMessage), 'semua') !== false || strpos(strtolower($userMessage), 'tampilkan') !== false) {
                    $prompt .= "\n\nIMPORTANT INSTRUCTION: You MUST follow this exact format:\n";
                    $prompt .= "1. Show company information first\n";
                    
                    if (isset($relevantData['farms']) && !empty($relevantData['farms'])) {
                        $farmCount = count($relevantData['farms']);
                        $prompt .= "2. Then show ALL {$farmCount} farms with format: Farm Name (Code) - Status - Company\n";
                        $prompt .= "DO NOT skip farms section. List every single farm.\n";
                    }
                    
                    $prompt .= "Use simple bullet points. Be complete, not brief.\n";
                }
                
                // Add status-specific display instructions
                if (isset($relevantData['farms']['status_filter_applied'])) {
                    $statusFilter = $relevantData['farms']['status_filter_applied'];
                    if ($statusFilter === 'all') {
                        $prompt .= "\n\nSTATUS DISPLAY INSTRUCTION: When showing farm data, include status information for each farm and provide status breakdown summary. ";
                        $prompt .= "Show both active and inactive farms clearly marked with their status. ";
                    } elseif ($statusFilter === 'inactive') {
                        $prompt .= "\n\nSTATUS DISPLAY INSTRUCTION: Focus only on inactive farms. Clearly mention that you are showing only inactive farms. ";
                    } elseif ($statusFilter === 'active') {
                        $prompt .= "\n\nSTATUS DISPLAY INSTRUCTION: Focus only on active farms. This is the default behavior. ";
                    }
                }
            }
        }

        // Add tone guidance
        $prompt .= "Use a {$strategy['tone']} tone. ";

        // Add length guidance
        if ($strategy['length'] === 'very_short') {
            $prompt .= "Keep response under 50 words. ";
        } elseif ($strategy['length'] === 'short') {
            $prompt .= "Keep response concise (under 100 words). ";
        }
        
        // Check if user is asking about non-farm topics
        if ($this->isNonFarmQuery($userMessage)) {
            $prompt .= "\n\nThe user is asking about non-farm topics. Politely redirect them to ask about livestock management, feed management, farm operations, or poultry farming instead.";
        }

        $prompt .= "\n\nUser query: " . $userMessage;

        return $prompt;
    }

    /**
     * Optimize model parameters based on complexity
     */
    private function optimizeModelParameters(string $complexity, string $language): array
    {
        $params = [
            'temperature' => 0.7,
            'max_tokens' => 500,
            'timeout' => 60  // Increased from 30 to 60 seconds as base
        ];

        switch ($complexity) {
            case 'minimal':
                $params['temperature'] = 0.1;
                $params['max_tokens'] = 50;
                $params['timeout'] = 30;  // Increased from 10 to 30 seconds
                break;
            case 'low':
                $params['temperature'] = 0.3;
                $params['max_tokens'] = 150;
                $params['timeout'] = 45;  // Increased from 15 to 45 seconds
                break;
            case 'medium':
                $params['temperature'] = 0.5;
                $params['max_tokens'] = 300;
                $params['timeout'] = 60;  // Increased from 20 to 60 seconds
                break;
            case 'high':
                $params['temperature'] = 0.1;  // Reduced from 0.7 to 0.1 for more consistent responses
                $params['max_tokens'] = 2000;  // Increased from 800 to 2000 for comprehensive data responses
                $params['timeout'] = 120;  // Increased from 90 to 120 seconds
                break;
        }

        return $params;
    }

    /**
     * Create response instructions
     */
    private function createResponseInstructions(string $language, string $approach, array $strategy): array
    {
        return [
            'language_preference' => $language,
            'response_approach' => $approach,
            'tone' => $strategy['tone'],
            'format' => $strategy['format'],
            'length' => $strategy['length'],
            'optimizations' => $strategy['optimizations']
        ];
    }

    /**
     * Get language instruction
     */
    private function getLanguageInstruction(string $language): string
    {
        return $language === 'indonesian'
            ? "[Respond in Indonesian/Bahasa Indonesia] "
            : "";
    }
    
    /**
     * Check if user query is about non-farm topics
     */
    private function isNonFarmQuery(string $message): bool
    {
        $message = strtolower($message);
        
        // Non-farm technology keywords that should be redirected
        $nonFarmKeywords = [
            'linux', 'server', 'windows', 'computer', 'laptop', 'software',
            'programming', 'coding', 'database', 'network', 'internet',
            'website', 'browser', 'email', 'password', 'security',
            'backup', 'optimization', 'performance', 'system', 'hardware',
            'cpu', 'memory', 'disk', 'cloud', 'aws', 'docker'
        ];
        
        // Check for farm-related keywords first
        $farmKeywords = [
            'ternak', 'livestock', 'ayam', 'chicken', 'pakan', 'feed',
            'kandang', 'farm', 'keuangan', 'financial', 'produksi',
            'production', 'kesehatan', 'health', 'monitoring'
        ];
        
        $hasFarmContext = false;
        foreach ($farmKeywords as $keyword) {
            if (strpos($message, $keyword) !== false) {
                $hasFarmContext = true;
                break;
            }
        }
        
        // If it has farm context, it's fine
        if ($hasFarmContext) {
            return false;
        }
        
        // Check for non-farm keywords
        foreach ($nonFarmKeywords as $keyword) {
            if (strpos($message, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get optimizations applied
     */
    private function getOptimizationsApplied(string $complexity, string $queryType): array
    {
        $optimizations = ['prr_methodology'];

        if ($complexity === 'minimal') {
            $optimizations[] = 'ultra_fast_processing';
        }

        if ($queryType === 'greeting_casual') {
            $optimizations[] = 'minimal_context_loading';
        }

        $optimizations[] = 'lightweight_language_detection';

        return $optimizations;
    }

    /**
     * Calculate data quality score
     */
    private function calculateDataQualityScore(array $data): float
    {
        if (empty($data)) return 0.0;

        $score = 0.5; // Base score

        // Add points for data completeness
        if (count($data) > 1) $score += 0.2;
        if (count($data) > 3) $score += 0.2;

        // Check for rich data content
        foreach ($data as $category => $info) {
            if (is_array($info) && count($info) > 3) {
                $score += 0.1;
            }
        }

        return min(1.0, $score);
    }
}

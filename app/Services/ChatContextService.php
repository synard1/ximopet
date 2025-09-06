<?php

namespace App\Services;

use App\Helpers\OllamaChatContextHelper;
use App\Helpers\OllamaChatQueryHelper;
use App\Services\UIResourceService;
use App\Services\AiDatabaseServiceRefactored;
use App\Services\DataAccessService;
use App\Services\PermissionChecker;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class ChatContextService
{
    protected int $defaultContextLimit;
    protected array $contextTypes;
    protected UIResourceService $uiResourceService;
    protected AiDatabaseServiceRefactored $databaseService;
    protected DataAccessService $dataAccessService;
    protected PermissionChecker $permissionChecker;

    public function __construct(
        UIResourceService $uiResourceService,
        AiDatabaseServiceRefactored $databaseService,
        DataAccessService $dataAccessService,
        PermissionChecker $permissionChecker
    ) {
        $this->defaultContextLimit = config('chat.context.max_context_size', 4000);
        $this->contextTypes = config('chat.context.context_types', []);
        $this->uiResourceService = $uiResourceService;
        $this->databaseService = $databaseService;
        $this->dataAccessService = $dataAccessService;
        $this->permissionChecker = $permissionChecker;
    }

    /**
     * Build secure context with real database access based on user permissions
     */
    public function buildSecureContext(string $query, User $user, string $contextType = 'general', array $filters = []): string
    {
        try {
            // 1. Parse query intent to understand what data is needed
            $intent = $this->parseQueryIntent($query);

            // 2. Check user permissions for requested data types
            $allowedDataTypes = $this->permissionChecker->getAllowedDataTypes($user, $intent['data_types']);

            // 3. Build base context
            $context = $this->buildBaseContext($user);

            // 4. Add secure real data if user has access
            if (!empty($allowedDataTypes) && $this->queryNeedsData($query, $intent)) {
                $secureData = $this->gatherSecureData($user, $allowedDataTypes, $query, $filters);
                if (!empty($secureData)) {
                    // NOTE: This service still uses DataAccessService which has formatDataForAI method
                    // If DataAccessService is also refactored, consider using direct JSON encoding:
                    $formattedData = json_encode($secureData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                    // $formattedData = $this->dataAccessService->formatDataForAI($secureData);
                    $context .= "\n\n=== CURRENT SYSTEM DATA ===\n" . $formattedData;
                }
            }

            // 5. Add UI context if needed for navigation queries
            if ($this->needsUIContext($contextType, $intent)) {
                $context .= "\n\n=== UI NAVIGATION HELP ===\n" . $this->uiResourceService->getUIContext();
            }

            return $context;

        } catch (Exception $e) {
            Log::error('ChatContextService: Error building secure context', [
                'error' => $e->getMessage(),
                'query' => $query,
                'user_id' => $user->id,
                'context_type' => $contextType
            ]);

            // Return safe fallback context
            return $this->getMinimalSystemPrompt($user);
        }
    }

    /**
     * Build context data based on type and filters - Intelligent Context Selection
     */
    public function buildContext(string $contextType = 'general', array $filters = [], string $companyId = null): string
    {
        try {
            // For general context, return minimal context to allow natural conversation
            if ($contextType === 'general') {
                return $this->getMinimalSystemPrompt();
            }

            $contextLimit = $this->getContextLimit($filters);
            $cacheKey = $this->generateCacheKey($contextType, $filters, $contextLimit, $companyId);

            // Try to get from cache first
            if (config('chat.performance.cache_context_data', true)) {
                $cachedContext = Cache::get($cacheKey);
                if ($cachedContext) {
                    return $cachedContext;
                }
            }

            // Build specific context only when needed
            $context = $this->buildContextByType($contextType, $filters, $contextLimit);

            // Add UI resources only for navigation-related queries
            if ($this->needsUIContext($contextType, $filters)) {
                $context .= "\n\n" . $this->uiResourceService->getUIContext();
            }

            // Cache the result
            if (config('chat.performance.cache_context_data', true) && !empty($context)) {
                Cache::put($cacheKey, $context, config('chat.performance.cache_ttl', 3600));
            }

            return $context;

        } catch (Exception $e) {
            Log::error('ChatContextService: Error building context', [
                'error' => $e->getMessage(),
                'context_type' => $contextType,
                'filters' => $filters
            ]);

            // Return minimal system prompt on error
            return $this->getMinimalSystemPrompt();
        }
    }

    /**
     * Parse query intent to determine what data types are needed
     */
    private function parseQueryIntent(string $query): array
    {
        $query = strtolower(trim($query));
        $dataTypes = [];
        $criteria = [];

        // More specific patterns to avoid cross-contamination
        $patterns = [
            'company_list' => '/(?:buatkan|tampilkan|show|list|daftar).*(?:perusahaan|company|companies)(?!.*(?:farm|kandang|ternak|livestock))/i',
            'livestock_data' => '/(?:berapa|how many|jumlah|total).*(?:ternak|livestock|ayam|chicken|hewan)(?!.*(?:perusahaan|company))/i',
            'financial_data' => '/(?:keuangan|financial|profit|untung|rugi|pendapatan|income|biaya|cost)(?!.*(?:perusahaan|company|farm|kandang))/i',
            'farm_data' => '/(?:farm|kandang|cage|pen|facility|fasilitas|lokasi)(?!.*(?:perusahaan|company))/i',
            'supply_data' => '/(?:stok|stock|pakan|feed|supply|supplies|inventory|gudang)(?!.*(?:perusahaan|company|farm|kandang))/i',
            'analytics_data' => '/(?:analisis|analysis|report|laporan|statistik|statistic)(?!.*(?:perusahaan|company))/i'
        ];

        foreach ($patterns as $type => $pattern) {
            if (preg_match($pattern, $query)) {
                $dataTypes[] = $type;
            }
        }

        // Extract additional criteria
        if (preg_match('/(?:bulan|month)\s+(\w+)/i', $query, $matches)) {
            $criteria['month'] = $matches[1];
        }

        if (preg_match('/(?:tahun|year)\s+(\d{4})/i', $query, $matches)) {
            $criteria['year'] = $matches[1];
        }

        return [
            'data_types' => $dataTypes,
            'criteria' => $criteria,
            'intent' => $this->classifyIntent($query),
            'needs_data' => !empty($dataTypes)
        ];
    }

    /**
     * Classify the overall intent of the query
     */
    private function classifyIntent(string $query): string
    {
        $query = strtolower($query);

        if (preg_match('/\b(hi|hello|hai|halo|selamat)\b/', $query)) {
            return 'greeting';
        }

        if (preg_match('/\b(cara|how to|bagaimana|help|bantuan)\b/', $query)) {
            return 'help_request';
        }

        if (preg_match('/\b(berapa|jumlah|total|count|sum)\b/', $query)) {
            return 'data_request';
        }

        if (preg_match('/\b(buatkan|create|tambah|add|buat)\b/', $query)) {
            return 'creation_request';
        }

        return 'general';
    }

    /**
     * Check if query needs actual data or just general conversation
     */
    private function queryNeedsData(string $query, array $intent): bool
    {
        // Casual greetings don't need data
        if ($intent['intent'] === 'greeting') {
            return false;
        }

        // General help questions might not need specific data
        if ($intent['intent'] === 'help_request' && empty($intent['data_types'])) {
            return false;
        }

        // Data requests definitely need data
        if ($intent['intent'] === 'data_request' || !empty($intent['data_types'])) {
            return true;
        }

        // Check for specific data keywords
        $dataKeywords = ['berapa', 'jumlah', 'total', 'list', 'daftar', 'show', 'tampilkan'];
        $queryLower = strtolower($query);

        foreach ($dataKeywords as $keyword) {
            if (strpos($queryLower, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gather secure data based on user permissions and query intent
     */
    private function gatherSecureData(User $user, array $allowedDataTypes, string $query, array $filters): array
    {
        $secureData = [];

        foreach ($allowedDataTypes as $dataType) {
            try {
                switch ($dataType) {
                    case 'company_list':
                        $secureData['companies'] = $this->dataAccessService->getCompanyList($user);
                        break;

                    case 'livestock_data':
                        $secureData['livestock'] = $this->dataAccessService->getLivestockSummary($user, $filters);
                        break;

                    case 'farm_data':
                        $secureData['farms'] = $this->dataAccessService->getFarmData($user, $filters);
                        break;

                    case 'financial_data':
                        $secureData['financial'] = $this->dataAccessService->getFinancialSummary($user, $filters);
                        break;

                    case 'supply_data':
                        $secureData['inventory'] = $this->dataAccessService->getSupplyInventory($user, $filters);
                        break;

                    default:
                        // Use fallback search for other data types
                        $searchResults = $this->dataAccessService->searchData($user, $query, $filters);
                        if (!empty($searchResults)) {
                            $secureData = array_merge($secureData, $searchResults);
                        }
                        break;
                }
            } catch (Exception $e) {
                Log::warning('ChatContextService: Error gathering data for type', [
                    'data_type' => $dataType,
                    'error' => $e->getMessage(),
                    'user_id' => $user->id
                ]);

                // Add error information to context
                $secureData[$dataType] = [
                    'error' => 'Failed to retrieve data',
                    'suggestion' => 'Please try again or contact support.'
                ];
            }
        }

        return $secureData;
    }

    /**
     * Build base context with user information and system prompt
     */
    private function buildBaseContext(User $user): string
    {
        $context = "You are an AI assistant for XiMoPet livestock management system. ";
        $context .= "You have access to real farm management data and can provide accurate, data-driven responses.\n\n";

        $context .= "Current User Context:\n";
        $context .= "- Name: {$user->name}\n";

        if ($user->company_id && $user->company) {
            $context .= "- Company: {$user->company->name}\n";
        }

        $context .= "\nGuidelines:\n";
        $context .= "- Provide accurate responses based on real system data\n";
        $context .= "- For navigation questions, provide step-by-step UI guidance\n";
        $context .= "- Be helpful and professional in all interactions\n";
        $context .= "- Format numbers and data clearly for easy reading\n";

        return $context;
    }

    /**
     * Get minimal system prompt for natural conversations
     */
    private function getMinimalSystemPrompt(User $user = null): string
    {
        $prompt = "You are a helpful AI assistant for XiMoPet, a livestock management application. ".
               "Provide natural, helpful responses. When users ask about specific data or need navigation help, ".
               "you can access their farm management information to provide relevant insights.";

        if ($user) {
            $prompt .= "\n\nUser: {$user->name}";
            if ($user->company) {
                $prompt .= " from {$user->company->name}";
            }
        }

        return $prompt;
    }

    /**
     * Check if UI context is needed for the query
     */
    private function needsUIContext(string $contextType, array $filters): bool
    {
        $uiRelatedTypes = ['navigation', 'ui_guidance', 'help'];
        return in_array($contextType, $uiRelatedTypes) ||
               isset($filters['needs_navigation']) ||
               isset($filters['ui_guidance']);
    }

    /**
     * Build farm-specific context for livestock queries
     */
    public function buildLivestockContext(array $criteria = [], string $companyId = null): string
    {
        try {
            $contextLimit = $this->getContextLimit($criteria);

            // If specific ID or identifier is provided
            if (isset($criteria['id']) || isset($criteria['identifier'])) {
                $identifier = $criteria['id'] ?? $criteria['identifier'];
                return OllamaChatContextHelper::getSpecificContextData($identifier, $contextLimit);
            }

            // If specific status is requested
            if (isset($criteria['status'])) {
                $query = "livestock status={$criteria['status']}";
                return OllamaChatQueryHelper::executeLivestockQuery($criteria['status'], $contextLimit);
            }

            // Default to livestock summary
            return OllamaChatContextHelper::getLivestockSummary($contextLimit);

        } catch (Exception $e) {
            Log::error('ChatContextService: Error building livestock context', [
                'error' => $e->getMessage(),
                'criteria' => $criteria
            ]);
            return '';
        }
    }

    /**
     * Build context for feed management queries
     */
    public function buildFeedContext(array $criteria = [], string $companyId = null): string
    {
        try {
            $contextLimit = $this->getContextLimit($criteria);

            // If specific purchase ID is provided
            if (isset($criteria['purchase_id'])) {
                return OllamaChatContextHelper::getSpecificContextData($criteria['purchase_id'], $contextLimit);
            }

            // If date range is specified
            if (isset($criteria['date_range']) || isset($criteria['month']) || isset($criteria['year'])) {
                $dateQuery = $this->buildDateQuery($criteria, 'feed');
                return OllamaChatQueryHelper::executeFeedPurchaseQuery($dateQuery, $contextLimit);
            }

            // Default to feed purchase summary
            return OllamaChatContextHelper::getFeedPurchaseSummary($contextLimit);

        } catch (Exception $e) {
            Log::error('ChatContextService: Error building feed context', [
                'error' => $e->getMessage(),
                'criteria' => $criteria
            ]);
            return '';
        }
    }

    /**
     * Build context for supply management queries
     */
    public function buildSupplyContext(array $criteria = [], string $companyId = null): string
    {
        try {
            $contextLimit = $this->getContextLimit($criteria);

            // If specific supply ID is provided
            if (isset($criteria['supply_id'])) {
                return OllamaChatContextHelper::getSpecificContextData($criteria['supply_id'], $contextLimit);
            }

            // If date range is specified
            if (isset($criteria['date_range']) || isset($criteria['month']) || isset($criteria['year'])) {
                $dateQuery = $this->buildDateQuery($criteria, 'supply');
                return OllamaChatQueryHelper::executeSupplyPurchaseQuery($dateQuery, $contextLimit);
            }

            // Default to supply purchase summary
            return OllamaChatContextHelper::getSupplyPurchaseSummary($contextLimit);

        } catch (Exception $e) {
            Log::error('ChatContextService: Error building supply context', [
                'error' => $e->getMessage(),
                'criteria' => $criteria
            ]);
            return '';
        }
    }

    /**
     * Build analytics context
     */
    public function buildAnalyticsContext(array $criteria = [], string $companyId = null): string
    {
        try {
            $contextLimit = $this->getContextLimit($criteria);

            // Determine analytics type
            $analyticsType = $criteria['analytics_type'] ?? 'overview';

            switch ($analyticsType) {
                case 'livestock':
                    return OllamaChatQueryHelper::getLivestockPerformanceData($contextLimit);

                case 'feed':
                    return OllamaChatQueryHelper::getFeedAnalyticsData($contextLimit);

                case 'supply':
                    return OllamaChatQueryHelper::getSupplyInventoryData($contextLimit);

                case 'financial':
                    return OllamaChatQueryHelper::getFinancialSummaryData($contextLimit);

                case 'operational':
                    return OllamaChatQueryHelper::getOperationalMetricsData($contextLimit);

                default:
                    return OllamaChatContextHelper::getRecentDataSummary($contextLimit);
            }

        } catch (Exception $e) {
            Log::error('ChatContextService: Error building analytics context', [
                'error' => $e->getMessage(),
                'criteria' => $criteria
            ]);
            return '';
        }
    }

    /**
     * Build financial context
     */
    public function buildFinancialContext(array $criteria = [], string $companyId = null): string
    {
        try {
            $contextLimit = $this->getContextLimit($criteria);

            // If specific time period is requested
            if (isset($criteria['period'])) {
                $period = $criteria['period'];
                return OllamaChatQueryHelper::executeNaturalLanguagePurchaseQuery($period, $contextLimit);
            }

            // Default to financial summary
            return OllamaChatQueryHelper::getFinancialSummaryData($contextLimit);

        } catch (Exception $e) {
            Log::error('ChatContextService: Error building financial context', [
                'error' => $e->getMessage(),
                'criteria' => $criteria
            ]);
            return '';
        }
    }

    /**
     * Process natural language query and extract context - Enhanced Intelligence
     */
    public function processNaturalLanguageQuery(string $query): array
    {
        try {
            $queryLower = strtolower($query);

            // First check if this is a casual/general conversation
            if ($this->isCasualConversation($query)) {
                return [
                    'context_type' => 'general',
                    'criteria' => []
                ];
            }

            // Check if this is a UI/navigation query
            $uiGuidance = $this->processUIQuery($query);
            if (!empty($uiGuidance)) {
                return [
                    'context_type' => 'ui_guidance',
                    'criteria' => ['ui_guidance' => $uiGuidance],
                    'specific_guidance' => $uiGuidance
                ];
            }

            // Detect specific data context type
            $contextType = $this->detectContextType($query);

            // Extract criteria from query
            $criteria = $this->extractCriteria($query);

            // Add intelligent context selection
            if ($this->needsDatabaseAccess($query)) {
                $criteria['needs_database'] = true;
            }

            return [
                'context_type' => $contextType,
                'criteria' => $criteria
            ];

        } catch (Exception $e) {
            Log::error('ChatContextService: Error processing natural language query', [
                'error' => $e->getMessage(),
                'query' => $query
            ]);

            return [
                'context_type' => 'general',
                'criteria' => []
            ];
        }
    }

    /**
     * Check if the query is casual conversation that doesn't need context
     */
    private function isCasualConversation(string $query): bool
    {
        $casualPatterns = [
            '/^(hi|hello|halo|hai|selamat)/i',
            '/^(thank|terima|thanks)/i',
            '/^(bye|goodbye|sampai jumpa)/i',
            '/^(how are you|apa kabar|bagaimana)/i',
            '/^(good|baik|bagus)/i',
            '/(joke|lelucon|funny|lucu)/i',
            '/(weather|cuaca)/i',
        ];

        foreach ($casualPatterns as $pattern) {
            if (preg_match($pattern, $query)) {
                return true;
            }
        }

        // Check for simple questions without data context
        $simpleQuestions = ['what', 'apa', 'how', 'bagaimana', 'why', 'kenapa', 'when', 'kapan'];
        $hasDataKeyword = $this->containsDataKeywords($query);

        return !$hasDataKeyword && str_word_count($query) < 10;
    }

    /**
     * Check if query needs database access
     */
    private function needsDatabaseAccess(string $query): bool
    {
        $databaseKeywords = [
            'berapa', 'how many', 'total', 'jumlah', 'show me', 'tampilkan',
            'list', 'daftar', 'summary', 'ringkasan', 'report', 'laporan',
            'data', 'informasi', 'info', 'statistik', 'analytics', 'analisis'
        ];

        $queryLower = strtolower($query);

        foreach ($databaseKeywords as $keyword) {
            if (strpos($queryLower, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if query contains data-related keywords
     */
    private function containsDataKeywords(string $query): bool
    {
        $dataKeywords = [
            'ternak', 'livestock', 'ayam', 'chicken', 'sapi', 'cow',
            'pakan', 'feed', 'supply', 'suplai', 'pembelian', 'purchase',
            'penjualan', 'sales', 'keuangan', 'financial', 'biaya', 'cost'
        ];

        $queryLower = strtolower($query);

        foreach ($dataKeywords as $keyword) {
            if (strpos($queryLower, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build context by type
     */
    protected function buildContextByType(string $contextType, array $filters, int $contextLimit): string
    {
        switch ($contextType) {
            case 'livestock':
                return $this->buildLivestockContext($filters);

            case 'feed':
                return $this->buildFeedContext($filters);

            case 'supply':
                return $this->buildSupplyContext($filters);

            case 'analytics':
                return $this->buildAnalyticsContext($filters);

            case 'financial':
                return $this->buildFinancialContext($filters);

            default:
                return OllamaChatContextHelper::getRecentDataSummary($contextLimit);
        }
    }

    /**
     * Detect context type from natural language query
     */
    protected function detectContextType(string $query): string
    {
        $queryLower = strtolower($query);

        // Livestock patterns
        if (preg_match('/(ternak|livestock|ayam|chicken|sapi|cow|batch)/i', $queryLower)) {
            return 'livestock';
        }

        // Feed patterns
        if (preg_match('/(pakan|feed|makanan)/i', $queryLower)) {
            return 'feed';
        }

        // Supply patterns
        if (preg_match('/(supply|suplai|perlengkapan|equipment|ovk)/i', $queryLower)) {
            return 'supply';
        }

        // Analytics patterns
        if (preg_match('/(analisis|analytics|laporan|report|statistik|statistics|performa|performance)/i', $queryLower)) {
            return 'analytics';
        }

        // Financial patterns
        if (preg_match('/(keuangan|financial|biaya|cost|harga|price|pembelian|purchase|penjualan|sales)/i', $queryLower)) {
            return 'financial';
        }

        return 'general';
    }

    /**
     * Extract criteria from natural language query
     */
    protected function extractCriteria(string $query): array
    {
        $criteria = [];
        $queryLower = strtolower($query);

        // Extract status
        if (preg_match('/(aktif|active|completed|selesai|pending|draft)/i', $queryLower, $matches)) {
            $criteria['status'] = strtolower($matches[1]);
        }

        // Extract date patterns
        if (preg_match('/(?:bulan|month)\s+(\w+)\s+(\d{4})/i', $queryLower, $matches)) {
            $criteria['month'] = $matches[1];
            $criteria['year'] = $matches[2];
        }

        // Extract year pattern
        if (preg_match('/(?:tahun|year)\s+(\d{4})/i', $queryLower, $matches)) {
            $criteria['year'] = $matches[1];
        }

        // Extract specific identifiers (UUIDs or IDs)
        if (preg_match('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i', $query, $matches)) {
            $criteria['id'] = $matches[0];
        }

        return $criteria;
    }

    /**
     * Build date query from criteria
     */
    protected function buildDateQuery(array $criteria, string $type): string
    {
        if (isset($criteria['month']) && isset($criteria['year'])) {
            return "bulan {$criteria['month']} {$criteria['year']}";
        }

        if (isset($criteria['year'])) {
            return "tahun {$criteria['year']}";
        }

        return 'terbaru';
    }

    /**
     * Get context limit from criteria or use default
     */
    protected function getContextLimit(array $criteria): int
    {
        return $criteria['context_limit'] ?? $this->defaultContextLimit;
    }

    /**
     * Generate cache key for context data
     */
    protected function generateCacheKey(string $contextType, array $filters, int $contextLimit, ?string $companyId): string
    {
        $keyData = [
            'type' => $contextType,
            'filters' => $filters,
            'limit' => $contextLimit,
            'company' => $companyId
        ];

        return 'chat_context_' . md5(json_encode($keyData));
    }

    /**
     * Get available context types
     */
    public function getContextTypes(): array
    {
        return $this->contextTypes;
    }

    /**
     * Validate context access for company
     */
    public function validateContextAccess(string $contextType, string $companyId): bool
    {
        // Basic validation - can be enhanced with more specific rules
        return !empty($companyId) && in_array($contextType, array_keys($this->contextTypes));
    }

    /**
     * Clear context cache
     */
    public function clearContextCache(string $companyId = null): bool
    {
        try {
            if ($companyId) {
                // Clear cache for specific company
                $pattern = "chat_context_*{$companyId}*";
            } else {
                // Clear all context cache
                $pattern = 'chat_context_*';
            }

            // Note: This is a simplified cache clearing method
            // In production, you might want to implement a more sophisticated cache tagging system
            Cache::flush();

            return true;

        } catch (Exception $e) {
            Log::error('ChatContextService: Error clearing context cache', [
                'error' => $e->getMessage(),
                'company_id' => $companyId
            ]);

            return false;
        }
    }

    /**
     * Get project-specific system prompt
     */
    protected function getProjectSystemPrompt(): string
    {
        $projectContext = config('chat.context.project_context', []);

        if (empty($projectContext)) {
            return '';
        }

        $prompt = $projectContext['system_prompt'] ?? '';

        // Add application context
        if (isset($projectContext['application_name'])) {
            $prompt .= "\n\nApplication: " . $projectContext['application_name'];
        }

        if (isset($projectContext['description'])) {
            $prompt .= "\nDescription: " . $projectContext['description'];
        }

        // Add main features
        if (isset($projectContext['main_features']) && is_array($projectContext['main_features'])) {
            $prompt .= "\n\nMain Features:\n";
            foreach ($projectContext['main_features'] as $feature) {
                $prompt .= "- " . $feature . "\n";
            }
        }

        // Add scope limitations
        if (isset($projectContext['scope_limitations']) && is_array($projectContext['scope_limitations'])) {
            $prompt .= "\nImportant Guidelines:\n";
            foreach ($projectContext['scope_limitations'] as $limitation) {
                $prompt .= "- " . $limitation . "\n";
            }
        }

        return $prompt;
    }

    /**
     * Process UI-related queries and provide specific navigation guidance
     */
    public function processUIQuery(string $query): string
    {
        $queryLower = strtolower($query);

        // Detect specific UI-related queries with more comprehensive patterns
        if (preg_match('/(cara|how|bagaimana).*(tambah|add|buat|create|input|masuk|entry)/i', $queryLower)) {
            // Extract the feature they want to add
            $feature = '';
            if (preg_match('/(farm|peternakan)/i', $queryLower)) {
                $feature = 'farm';
            } elseif (preg_match('/(kandang|cage|pen)/i', $queryLower)) {
                $feature = 'kandang';
            } elseif (preg_match('/(livestock|ternak|ayam|chicken|sapi|cow)/i', $queryLower)) {
                $feature = 'livestock';
            } elseif (preg_match('/(feed|pakan|makanan)/i', $queryLower)) {
                $feature = 'feed';
            } elseif (preg_match('/(recording|record|catat|pencatatan|produksi|production)/i', $queryLower)) {
                $feature = 'recording';
            }

            if ($feature) {
                return $this->uiResourceService->getNavigationGuidance($feature);
            }
        }

        // Handle specific "where is" or "dimana" queries
        if (preg_match('/(dimana|where|lokasi|location).*(menu|fitur|feature)/i', $queryLower)) {
            if (preg_match('/(farm|peternakan)/i', $queryLower)) {
                return $this->uiResourceService->getNavigationGuidance('farm');
            } elseif (preg_match('/(kandang)/i', $queryLower)) {
                return $this->uiResourceService->getNavigationGuidance('kandang');
            } elseif (preg_match('/(livestock|ternak)/i', $queryLower)) {
                return $this->uiResourceService->getNavigationGuidance('livestock');
            } elseif (preg_match('/(feed|pakan)/i', $queryLower)) {
                return $this->uiResourceService->getNavigationGuidance('feed');
            }
        }

        // Handle navigation structure queries
        if (preg_match('/(menu|navigation|navigasi|struktur|structure)/i', $queryLower)) {
            return $this->uiResourceService->getUIContext();
        }

        // Handle access/permission queries
        if (preg_match('/(akses|access|permission|izin|login|masuk)/i', $queryLower)) {
            return "=== LOGIN & ACCESS GUIDE ===\n\n" .
                   "1. LOGIN PROCESS:\n" .
                   "   - Navigate to /login\n" .
                   "   - Enter your username and password\n" .
                   "   - Ensure you have the correct user role\n\n" .
                   "2. USER ROLES & PERMISSIONS:\n" .
                   "   - Admin: Full access to all features\n" .
                   "   - Farm Manager: Farm-specific operations\n" .
                   "   - Operator: Daily recording and data entry\n" .
                   "   - Viewer: Read-only access to reports\n\n" .
                   "3. COMMON ACCESS ISSUES:\n" .
                   "   - Check if you're logged in with correct company scope\n" .
                   "   - Verify your user role has permission for the feature\n" .
                   "   - Contact admin if features appear disabled\n\n" .
                   $this->uiResourceService->getUIContext();
        }

        return '';
    }
}

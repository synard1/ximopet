<?php

namespace App\Services\Recording;

use App\Models\Livestock;
use App\Models\Recording;
use App\Models\LivestockDepletion;
use App\Models\FeedUsage;
use App\Models\SupplyUsage;
use App\Config\LivestockDepletionConfig;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

/**
 * Service untuk validasi perubahan metode pencatatan (manual ↔ FIFO)
 * Berdasarkan analisis records-configuration-change-analysis.md
 */
class RecordingMethodValidationService
{
    /**
     * Validasi perubahan konfigurasi metode pencatatan
     *
     * @param Livestock $livestock
     * @param array $newConfig
     * @param array $currentConfig
     * @return array
     */
    public function validateConfigurationChange(Livestock $livestock, array $newConfig, array $currentConfig = []): array
    {
        try {
            Log::info('🔍 RecordingMethodValidation: Starting validation', [
                'livestock_id' => $livestock->id,
                'new_config' => $newConfig,
                'current_config' => $currentConfig
            ]);

            $validationResults = [];

            // 1. Basic validation
            $basicValidation = $this->validateBasicRequirements($livestock, $newConfig);
            $validationResults['basic'] = $basicValidation;

            if (!$basicValidation['valid']) {
                return [
                    'valid' => false,
                    'message' => $basicValidation['message'],
                    'details' => $validationResults
                ];
            }

            // 2. Depletion method validation
            if (isset($newConfig['depletion_method'])) {
                $depletionValidation = $this->validateDepletionMethodChange(
                    $livestock,
                    $newConfig['depletion_method'],
                    $currentConfig['depletion_method'] ?? 'manual'
                );
                $validationResults['depletion_method'] = $depletionValidation;

                if (!$depletionValidation['valid']) {
                    return [
                        'valid' => false,
                        'message' => $depletionValidation['message'],
                        'details' => $validationResults
                    ];
                }
            }

            // 3. Feed usage method validation
            if (isset($newConfig['feed_usage_method'])) {
                $feedValidation = $this->validateFeedUsageMethodChange(
                    $livestock,
                    $newConfig['feed_usage_method'],
                    $currentConfig['feed_usage_method'] ?? 'manual'
                );
                $validationResults['feed_usage_method'] = $feedValidation;

                if (!$feedValidation['valid']) {
                    return [
                        'valid' => false,
                        'message' => $feedValidation['message'],
                        'details' => $validationResults
                    ];
                }
            }

            // 4. Backward compatibility
            $compatibilityValidation = $this->validateBackwardCompatibility($livestock, $newConfig);
            $validationResults['backward_compatibility'] = $compatibilityValidation;

            // 5. Historical data impact
            $historyValidation = $this->validateHistoricalDataImpact($livestock, $newConfig);
            $validationResults['historical_impact'] = $historyValidation;

            // 6. Service availability
            $serviceValidation = $this->validateServiceAvailability($newConfig);
            $validationResults['service_availability'] = $serviceValidation;

            Log::info('✅ RecordingMethodValidation: Validation completed', [
                'livestock_id' => $livestock->id,
                'validation_results' => $validationResults
            ]);

            return [
                'valid' => true,
                'message' => 'Configuration change is valid and safe',
                'details' => $validationResults,
                'recommendations' => $this->generateRecommendations($livestock, $newConfig, $validationResults)
            ];
        } catch (Exception $e) {
            Log::error('❌ RecordingMethodValidation: Validation failed', [
                'livestock_id' => $livestock->id,
                'error' => $e->getMessage()
            ]);

            return [
                'valid' => false,
                'message' => 'Validation failed: ' . $e->getMessage(),
                'details' => ['error' => $e->getMessage()]
            ];
        }
    }

    /**
     * Validasi requirements dasar untuk perubahan konfigurasi
     */
    private function validateBasicRequirements(Livestock $livestock, array $newConfig): array
    {
        // Check if livestock exists and is active
        if (!$livestock || !$livestock->exists) {
            return [
                'valid' => false,
                'message' => 'Livestock record not found or inactive'
            ];
        }

        // Check if livestock has valid farm and coop
        if (!$livestock->farm_id || !$livestock->coop_id) {
            return [
                'valid' => false,
                'message' => 'Livestock must have valid farm and coop assignment'
            ];
        }

        // Check if configuration structure is valid
        $requiredFields = ['recording_method'];
        foreach ($requiredFields as $field) {
            if (!isset($newConfig[$field])) {
                return [
                    'valid' => false,
                    'message' => "Required configuration field '{$field}' is missing"
                ];
            }
        }

        // Check if user has permission to change configuration
        if (!auth()->user()->can('update livestock configuration')) {
            return [
                'valid' => false,
                'message' => 'User does not have permission to change livestock configuration'
            ];
        }

        return [
            'valid' => true,
            'message' => 'Basic requirements met'
        ];
    }

    /**
     * Validasi perubahan metode deplesi
     */
    public function validateDepletionMethodChange(Livestock $livestock, string $newMethod, string $currentMethod): array
    {
        $validMethods = ['manual', 'fifo', 'traditional'];

        if (!in_array($newMethod, $validMethods)) {
            return [
                'valid' => false,
                'message' => "Invalid depletion method: {$newMethod}. Valid methods: " . implode(', ', $validMethods)
            ];
        }

        // Jika tidak ada perubahan
        if ($newMethod === $currentMethod) {
            return [
                'valid' => true,
                'message' => 'No change in depletion method',
                'change_required' => false
            ];
        }

        // Validasi khusus untuk FIFO
        if ($newMethod === 'fifo') {
            $fifoValidation = $this->validateFifoRequirements($livestock, 'depletion');
            if (!$fifoValidation['valid']) {
                return $fifoValidation;
            }
        }

        return [
            'valid' => true,
            'message' => 'Depletion method change is valid',
            'change_required' => true,
            'impact' => $this->assessDepletionMethodChangeImpact($livestock, $newMethod, $currentMethod)
        ];
    }

    /**
     * Validasi perubahan metode feed usage
     */
    public function validateFeedUsageMethodChange(Livestock $livestock, string $newMethod, string $currentMethod): array
    {
        $validMethods = ['manual', 'fifo', 'traditional'];

        if (!in_array($newMethod, $validMethods)) {
            return [
                'valid' => false,
                'message' => "Invalid feed usage method: {$newMethod}. Valid methods: " . implode(', ', $validMethods)
            ];
        }

        // Jika tidak ada perubahan
        if ($newMethod === $currentMethod) {
            return [
                'valid' => true,
                'message' => 'No change in feed usage method',
                'change_required' => false
            ];
        }

        // Validasi khusus untuk FIFO
        if ($newMethod === 'fifo') {
            $fifoValidation = $this->validateFifoRequirements($livestock, 'feed_usage');
            if (!$fifoValidation['valid']) {
                return $fifoValidation;
            }
        }

        return [
            'valid' => true,
            'message' => 'Feed usage method change is valid',
            'change_required' => true,
            'impact' => $this->assessFeedUsageMethodChangeImpact($livestock, $newMethod, $currentMethod)
        ];
    }

    /**
     * Validasi requirements untuk FIFO
     */
    public function validateFifoRequirements(Livestock $livestock, string $type): array
    {
        // Check if FIFO services are available
        if ($type === 'depletion' && !app()->bound('App\Services\Livestock\FIFODepletionService')) {
            return [
                'valid' => false,
                'message' => 'FIFODepletionService is not available in the system'
            ];
        }

        if ($type === 'feed_usage' && !app()->bound('App\Services\FeedUsageService')) {
            return [
                'valid' => false,
                'message' => 'FeedUsageService is not available in the system'
            ];
        }

        // Check if livestock has multiple batches (FIFO is most beneficial for multi-batch)
        if (method_exists($livestock, 'getActiveBatchesCount')) {
            $batchCount = $livestock->getActiveBatchesCount();
            if ($batchCount <= 1) {
                Log::warning('FIFO method selected for single batch livestock', [
                    'livestock_id' => $livestock->id,
                    'batch_count' => $batchCount,
                    'type' => $type
                ]);
                // Not an error, but worth noting
            }
        }

        return [
            'valid' => true,
            'message' => 'FIFO requirements met'
        ];
    }

    /**
     * Validasi backward compatibility
     */
    private function validateBackwardCompatibility(Livestock $livestock, array $newConfig): array
    {
        $issues = [];
        $warnings = [];

        // Check if existing data can be read with new configuration
        $existingRecordings = Recording::where('livestock_id', $livestock->id)->count();
        $existingDepletions = LivestockDepletion::where('livestock_id', $livestock->id)->count();
        $existingFeedUsages = FeedUsage::where('livestock_id', $livestock->id)->count();

        if ($existingRecordings > 0 || $existingDepletions > 0 || $existingFeedUsages > 0) {
            $warnings[] = "Livestock has existing data that will be processed with mixed methods";
        }

        // Check depletion type compatibility
        if ($existingDepletions > 0) {
            $depletionTypes = LivestockDepletion::where('livestock_id', $livestock->id)
                ->distinct()
                ->pluck('jenis')
                ->toArray();

            foreach ($depletionTypes as $type) {
                try {
                    $normalized = LivestockDepletionConfig::normalize($type);
                    $legacy = LivestockDepletionConfig::toLegacy($normalized);
                } catch (Exception $e) {
                    $issues[] = "Depletion type '{$type}' may not be compatible with new configuration";
                }
            }
        }

        return [
            'valid' => empty($issues),
            'message' => empty($issues) ? 'Backward compatibility validated' : 'Compatibility issues found',
            'issues' => $issues,
            'warnings' => $warnings,
            'existing_data' => [
                'recordings' => $existingRecordings,
                'depletions' => $existingDepletions,
                'feed_usages' => $existingFeedUsages
            ]
        ];
    }

    /**
     * Validasi impact pada data historis
     */
    private function validateHistoricalDataImpact(Livestock $livestock, array $newConfig): array
    {
        $impact = [
            'data_loss_risk' => 'none',
            'performance_impact' => 'minimal',
            'reporting_impact' => 'minimal'
        ];

        // Assess data loss risk (should be none for proper implementation)
        $impact['data_loss_risk'] = 'none'; // Our implementation is non-destructive

        // Assess performance impact
        $recordCount = Recording::where('livestock_id', $livestock->id)->count();
        if ($recordCount > 1000) {
            $impact['performance_impact'] = 'moderate';
        } elseif ($recordCount > 5000) {
            $impact['performance_impact'] = 'high';
        }

        // Assess reporting impact
        $hasHistoricalData = $recordCount > 0;
        if ($hasHistoricalData) {
            $impact['reporting_impact'] = 'moderate'; // Mixed method data in reports
        }

        return [
            'valid' => true,
            'message' => 'Historical data impact assessed',
            'impact' => $impact,
            'mitigation_strategies' => $this->generateMitigationStrategies($impact)
        ];
    }

    /**
     * Validasi ketersediaan service
     */
    private function validateServiceAvailability(array $newConfig): array
    {
        $requiredServices = [];
        $availableServices = [];
        $missingServices = [];

        // Check for depletion method services
        if (isset($newConfig['depletion_method']) && $newConfig['depletion_method'] === 'fifo') {
            $requiredServices[] = 'App\Services\Livestock\FIFODepletionService';
        }

        // Check for feed usage method services
        if (isset($newConfig['feed_usage_method']) && $newConfig['feed_usage_method'] === 'fifo') {
            $requiredServices[] = 'App\Services\FeedUsageService';
            $requiredServices[] = 'App\Services\FIFOService';
        }

        // Check service availability
        foreach ($requiredServices as $service) {
            if (app()->bound($service)) {
                $availableServices[] = $service;
            } else {
                $missingServices[] = $service;
            }
        }

        return [
            'valid' => empty($missingServices),
            'message' => empty($missingServices) ? 'All required services available' : 'Some required services are missing',
            'required_services' => $requiredServices,
            'available_services' => $availableServices,
            'missing_services' => $missingServices
        ];
    }

    /**
     * Generate recommendations based on validation results
     */
    private function generateRecommendations(Livestock $livestock, array $newConfig, array $validationResults): array
    {
        $recommendations = [];

        // Performance recommendations
        if (
            isset($validationResults['historical_impact']['impact']['performance_impact']) &&
            $validationResults['historical_impact']['impact']['performance_impact'] !== 'minimal'
        ) {
            $recommendations[] = [
                'type' => 'performance',
                'message' => 'Consider implementing background job processing for FIFO operations',
                'priority' => 'medium'
            ];
        }

        // Backup recommendations
        if (
            isset($validationResults['backward_compatibility']['existing_data']) &&
            array_sum($validationResults['backward_compatibility']['existing_data']) > 0
        ) {
            $recommendations[] = [
                'type' => 'backup',
                'message' => 'Create backup of livestock configuration before applying changes',
                'priority' => 'high'
            ];
        }

        // Monitoring recommendations
        $recommendations[] = [
            'type' => 'monitoring',
            'message' => 'Monitor system performance and data consistency for 1 week after change',
            'priority' => 'medium'
        ];

        // User training recommendations
        if (isset($newConfig['depletion_method']) && $newConfig['depletion_method'] === 'fifo') {
            $recommendations[] = [
                'type' => 'training',
                'message' => 'Train users on FIFO depletion method behavior and reporting differences',
                'priority' => 'medium'
            ];
        }

        return $recommendations;
    }

    /**
     * Assess impact of depletion method change
     */
    private function assessDepletionMethodChangeImpact(Livestock $livestock, string $newMethod, string $currentMethod): array
    {
        return [
            'method_change' => "{$currentMethod} → {$newMethod}",
            'processing_change' => $newMethod === 'fifo' ? 'batch-based' : 'traditional',
            'data_structure_change' => 'none', // Structure remains the same
            'performance_impact' => $newMethod === 'fifo' ? 'increased processing time' : 'faster processing',
            'accuracy_impact' => $newMethod === 'fifo' ? 'improved batch tracking' : 'simplified tracking'
        ];
    }

    /**
     * Assess impact of feed usage method change
     */
    private function assessFeedUsageMethodChangeImpact(Livestock $livestock, string $newMethod, string $currentMethod): array
    {
        return [
            'method_change' => "{$currentMethod} → {$newMethod}",
            'stock_selection' => $newMethod === 'fifo' ? 'oldest stock first' : 'any available stock',
            'data_structure_change' => 'none', // Structure remains the same
            'performance_impact' => $newMethod === 'fifo' ? 'increased processing time' : 'faster processing',
            'accuracy_impact' => $newMethod === 'fifo' ? 'improved cost tracking' : 'simplified tracking'
        ];
    }

    /**
     * Generate mitigation strategies for identified impacts
     */
    private function generateMitigationStrategies(array $impact): array
    {
        $strategies = [];

        if ($impact['performance_impact'] !== 'minimal') {
            $strategies[] = [
                'issue' => 'performance_impact',
                'strategy' => 'Implement background job processing for heavy calculations',
                'implementation' => 'Use Laravel queues for FIFO processing'
            ];
        }

        if ($impact['reporting_impact'] !== 'minimal') {
            $strategies[] = [
                'issue' => 'reporting_impact',
                'strategy' => 'Add method indicators in reports',
                'implementation' => 'Display method used for each record in reports'
            ];
        }

        return $strategies;
    }

    /**
     * Preview configuration change impact
     */
    public function previewConfigurationChange(Livestock $livestock, array $newConfig): array
    {
        $currentConfig = $livestock->getConfiguration();

        return [
            'livestock' => [
                'id' => $livestock->id,
                'name' => $livestock->name,
                'current_method' => $currentConfig['depletion_method'] ?? 'manual'
            ],
            'changes' => [
                'depletion_method' => [
                    'from' => $currentConfig['depletion_method'] ?? 'manual',
                    'to' => $newConfig['depletion_method'] ?? $currentConfig['depletion_method'] ?? 'manual'
                ],
                'feed_usage_method' => [
                    'from' => $currentConfig['feed_usage_method'] ?? 'manual',
                    'to' => $newConfig['feed_usage_method'] ?? $currentConfig['feed_usage_method'] ?? 'manual'
                ]
            ],
            'impact_preview' => $this->validateConfigurationChange($livestock, $newConfig, $currentConfig)
        ];
    }
}

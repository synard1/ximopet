<?php

declare(strict_types=1);

namespace App\Services\Recording;

use App\Models\{CurrentLivestock, Livestock};
use App\Services\Recording\DTOs\ProcessingResult;
use App\Services\Recording\Exceptions\RecordingException;
use Illuminate\Support\Facades\{Log, Auth};
use Carbon\Carbon;

/**
 * PayloadBuilderService
 * 
 * Handles building structured payloads for recording data
 * with comprehensive metadata, performance metrics, and historical data.
 */
class PayloadBuilderService
{
    private UnitConversionService $unitConversionService;
    private StockAnalysisService $stockAnalysisService;

    public function __construct(
        UnitConversionService $unitConversionService,
        StockAnalysisService $stockAnalysisService
    ) {
        $this->unitConversionService = $unitConversionService;
        $this->stockAnalysisService = $stockAnalysisService;
    }

    /**
     * Build structured payload with organized sections for future-proof data storage
     */
    public function buildStructuredPayload(
        CurrentLivestock $ternak,
        int $age,
        int $stockAwal,
        int $stockAkhir,
        float $weightToday,
        float $weightYesterday,
        float $weightGain,
        array $performanceMetrics,
        array $weightHistory,
        array $feedHistory,
        array $populationHistory,
        array $outflowHistory,
        array $usages = [],
        array $supplyUsages = [],
        array $livestockConfig = [],
        ?string $recordingMethod = null,
        bool $isManualDepletionEnabled = false,
        bool $isManualFeedUsageEnabled = false,
        int $mortality = 0,
        int $culling = 0,
        int $salesQuantity = 0,
        float $salesWeight = 0,
        float $salesPrice = 0,
        float $totalSales = 0,
        string $date = null
    ): ProcessingResult {
        try {
            // Calculate feed-related data
            $feedData = $this->calculateFeedData($usages);

            // Calculate supply-related data
            $supplyData = $this->calculateSupplyData($supplyUsages);

            // Build comprehensive payload
            $payload = [
                // === METADATA SECTION ===
                'schema' => [
                    'version' => '3.0',
                    'schema_date' => '2025-01-23',
                    'compatibility' => ['2.0', '3.0'],
                    'structure' => 'hierarchical_organized'
                ],

                'recording' => [
                    'timestamp' => now()->toIso8601String(),
                    'date' => $date,
                    'age_days' => $age,
                    'user' => $this->buildUserInfo(),
                    'source' => [
                        'application' => 'payloadbuilder_service',
                        'component' => 'PayloadBuilderService',
                        'method' => 'buildStructuredPayload',
                        'version' => '3.0'
                    ]
                ],

                // === BUSINESS DATA SECTION ===
                'livestock' => $this->buildLivestockInfo($ternak, $age),
                'production' => $this->buildProductionInfo(
                    $weightYesterday,
                    $weightToday,
                    $weightGain,
                    $mortality,
                    $culling,
                    $salesQuantity,
                    $salesWeight,
                    $salesPrice,
                    $totalSales
                ),
                'consumption' => $this->buildConsumptionInfo($feedData, $supplyData),

                // === PERFORMANCE SECTION ===
                'performance' => array_merge($performanceMetrics, [
                    'calculated_at' => now()->toIso8601String(),
                    'calculation_method' => 'standard_poultry_metrics'
                ]),

                // === HISTORICAL DATA SECTION ===
                'history' => [
                    'weight' => $weightHistory,
                    'feed' => $feedHistory,
                    'population' => $populationHistory,
                    'outflow' => $outflowHistory
                ],

                // === ENVIRONMENT SECTION (Extensible) ===
                'environment' => $this->buildEnvironmentInfo(),

                // === CONFIGURATION SECTION ===
                'config' => [
                    'manual_depletion_enabled' => $isManualDepletionEnabled,
                    'manual_feed_usage_enabled' => $isManualFeedUsageEnabled,
                    'recording_method' => $recordingMethod ?? 'total',
                    'livestock_config' => $livestockConfig
                ],

                // === VALIDATION SECTION ===
                'validation' => $this->buildValidationInfo(
                    $weightToday,
                    $weightGain,
                    $stockAwal,
                    $stockAkhir,
                    $feedData['total_quantity'],
                    $mortality,
                    $culling,
                    $supplyData['total_quantity']
                )
            ];

            return ProcessingResult::success($payload, 'Structured payload built successfully');
        } catch (\Exception $e) {
            Log::error('Error building structured payload', [
                'livestock_id' => $ternak->livestock->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new RecordingException("Failed to build structured payload: " . $e->getMessage());
        }
    }

    /**
     * Calculate feed-related data
     */
    private function calculateFeedData(array $usages): array
    {
        $totalQuantity = array_sum(array_column($usages, 'quantity'));
        $totalCost = array_sum(array_map(function ($usage) {
            $qty = $usage['quantity'] ?? 0;
            $price = $usage['stock_prices']['average_price'] ?? 0;
            return $qty * $price;
        }, $usages));

        return [
            'total_quantity' => $totalQuantity,
            'total_cost' => $totalCost,
            'items' => $usages,
            'types_count' => count($usages),
            'cost_per_kg' => $totalQuantity > 0 ? $totalCost / $totalQuantity : 0,
            'enhanced_details' => $this->enhanceFeedDetails($usages)
        ];
    }

    /**
     * Calculate supply-related data
     */
    private function calculateSupplyData(array $supplyUsages): array
    {
        $totalQuantity = array_sum(array_column($supplyUsages, 'quantity'));
        $totalCost = array_sum(array_map(function ($usage) {
            $qty = $usage['quantity'] ?? 0;
            $price = $usage['stock_prices']['average_price'] ?? 0;
            return $qty * $price;
        }, $supplyUsages));

        return [
            'total_quantity' => $totalQuantity,
            'total_cost' => $totalCost,
            'items' => $supplyUsages,
            'types_count' => count($supplyUsages),
            'cost_per_unit' => $totalQuantity > 0 ? $totalCost / $totalQuantity : 0,
            'enhanced_details' => $this->enhanceSupplyDetails($supplyUsages)
        ];
    }

    /**
     * Enhance feed details with unit conversion and stock analysis
     */
    private function enhanceFeedDetails(array $usages): array
    {
        $enhanced = [];

        foreach ($usages as $usage) {
            $feedId = $usage['feed_id'] ?? null;
            if (!$feedId) continue;

            $enhanced[] = [
                'feed_id' => $feedId,
                'quantity' => $usage['quantity'] ?? 0,
                'feed_name' => $usage['feed_name'] ?? 'Unknown',
                'category' => $usage['category'] ?? 'Unknown',
                'unit_conversion' => $this->unitConversionService->getDetailedFeedUnitInfo(
                    \App\Models\Feed::find($feedId),
                    $usage['quantity'] ?? 0
                ),
                'cost_analysis' => [
                    'unit_cost' => $usage['stock_prices']['average_price'] ?? 0,
                    'total_cost' => ($usage['quantity'] ?? 0) * ($usage['stock_prices']['average_price'] ?? 0),
                    'price_range' => [
                        'min' => $usage['stock_prices']['min_price'] ?? 0,
                        'max' => $usage['stock_prices']['max_price'] ?? 0,
                    ]
                ]
            ];
        }

        return $enhanced;
    }

    /**
     * Enhance supply details with unit conversion and stock analysis
     */
    private function enhanceSupplyDetails(array $supplyUsages): array
    {
        $enhanced = [];

        foreach ($supplyUsages as $usage) {
            $supplyId = $usage['supply_id'] ?? null;
            if (!$supplyId) continue;

            $enhanced[] = [
                'supply_id' => $supplyId,
                'quantity' => $usage['quantity'] ?? 0,
                'supply_name' => $usage['supply_name'] ?? 'Unknown',
                'category' => $usage['category'] ?? 'Unknown',
                'unit_conversion' => $this->unitConversionService->getDetailedSupplyUnitInfo(
                    \App\Models\Supply::find($supplyId),
                    $usage['quantity'] ?? 0
                ),
                'cost_analysis' => [
                    'unit_cost' => $usage['stock_prices']['average_price'] ?? 0,
                    'total_cost' => ($usage['quantity'] ?? 0) * ($usage['stock_prices']['average_price'] ?? 0),
                    'price_range' => [
                        'min' => $usage['stock_prices']['min_price'] ?? 0,
                        'max' => $usage['stock_prices']['max_price'] ?? 0,
                    ]
                ]
            ];
        }

        return $enhanced;
    }

    /**
     * Build user information
     */
    private function buildUserInfo(): array
    {
        $user = Auth::user();

        return [
            'id' => $user ? $user->id : null,
            'name' => $user ? $user->name : 'Unknown User',
            'role' => $user && $user->roles->first() ? $user->roles->first()->name : 'Unknown Role',
            'company_id' => $user ? $user->company_id : null,
        ];
    }

    /**
     * Build livestock information
     */
    private function buildLivestockInfo(CurrentLivestock $ternak, int $age): array
    {
        return [
            'basic_info' => [
                'id' => $ternak->livestock->id,
                'name' => $ternak->livestock->name,
                'strain' => $ternak->livestock->strain ?? 'Unknown Strain',
                'start_date' => $ternak->livestock->start_date,
                'age_days' => $age
            ],
            'location' => [
                'farm_id' => $ternak->livestock->farm_id,
                'farm_name' => $ternak->livestock->farm->name ?? 'Unknown Farm',
                'coop_id' => $ternak->livestock->coop_id,
                'coop_name' => $ternak->livestock->coop->name ?? 'Unknown Coop'
            ],
            'population' => [
                'initial' => $ternak->livestock->initial_quantity,
                'current' => $ternak->quantity ?? 0,
                'depleted' => $ternak->livestock->initial_quantity - ($ternak->quantity ?? 0)
            ]
        ];
    }

    /**
     * Build production information
     */
    private function buildProductionInfo(
        float $weightYesterday,
        float $weightToday,
        float $weightGain,
        int $mortality,
        int $culling,
        int $salesQuantity,
        float $salesWeight,
        float $salesPrice,
        float $totalSales
    ): array {
        return [
            'weight' => [
                'yesterday' => $weightYesterday,
                'today' => $weightToday,
                'gain' => $weightGain,
                'unit' => 'grams'
            ],
            'depletion' => [
                'mortality' => $mortality,
                'culling' => $culling,
                'total' => $mortality + $culling
            ],
            'sales' => [
                'quantity' => $salesQuantity,
                'weight' => $salesWeight,
                'price_per_unit' => $salesPrice,
                'total_value' => $totalSales,
                'average_weight' => $salesQuantity > 0 ? $salesWeight / $salesQuantity : 0
            ]
        ];
    }

    /**
     * Build consumption information
     */
    private function buildConsumptionInfo(array $feedData, array $supplyData): array
    {
        return [
            'feed' => $feedData,
            'supply' => $supplyData,
            'combined_cost' => $feedData['total_cost'] + $supplyData['total_cost'],
            'cost_breakdown' => [
                'feed_percentage' => ($feedData['total_cost'] + $supplyData['total_cost']) > 0
                    ? round(($feedData['total_cost'] / ($feedData['total_cost'] + $supplyData['total_cost'])) * 100, 2)
                    : 0,
                'supply_percentage' => ($feedData['total_cost'] + $supplyData['total_cost']) > 0
                    ? round(($supplyData['total_cost'] / ($feedData['total_cost'] + $supplyData['total_cost'])) * 100, 2)
                    : 0,
            ]
        ];
    }

    /**
     * Build environment information (extensible section)
     */
    private function buildEnvironmentInfo(): array
    {
        return [
            'climate' => [
                'temperature' => null,
                'humidity' => null,
                'pressure' => null
            ],
            'housing' => [
                'lighting' => null,
                'ventilation' => null,
                'density' => null
            ],
            'water' => [
                'consumption' => null,
                'quality' => null,
                'temperature' => null
            ],
            'notes' => 'Environment data can be extended based on sensor integrations'
        ];
    }

    /**
     * Build validation information
     */
    private function buildValidationInfo(
        float $weightToday,
        float $weightGain,
        int $stockAwal,
        int $stockAkhir,
        float $totalFeedUsage,
        int $mortality,
        int $culling,
        float $totalSupplyUsage
    ): array {
        return [
            'data_quality' => [
                'weight_logical' => $weightToday >= 0 && $weightGain >= -100,
                'population_logical' => $stockAkhir >= 0 && $stockAwal >= $stockAkhir,
                'feed_consumption_logical' => $totalFeedUsage >= 0,
                'depletion_logical' => $mortality >= 0 && $culling >= 0,
                'supply_usage_logical' => $totalSupplyUsage >= 0
            ],
            'completeness' => [
                'has_weight_data' => $weightToday > 0,
                'has_feed_data' => $totalFeedUsage > 0,
                'has_depletion_data' => $mortality > 0 || $culling > 0,
                'has_supply_data' => $totalSupplyUsage > 0
            ],
            'quality_score' => $this->calculateQualityScore([
                'weight_logical' => $weightToday >= 0 && $weightGain >= -100,
                'population_logical' => $stockAkhir >= 0 && $stockAwal >= $stockAkhir,
                'feed_consumption_logical' => $totalFeedUsage >= 0,
                'depletion_logical' => $mortality >= 0 && $culling >= 0,
                'has_weight_data' => $weightToday > 0,
                'has_feed_data' => $totalFeedUsage > 0,
            ])
        ];
    }

    /**
     * Calculate data quality score
     */
    private function calculateQualityScore(array $checks): float
    {
        $totalChecks = count($checks);
        $passedChecks = array_sum(array_map(fn($check) => $check ? 1 : 0, $checks));

        return $totalChecks > 0 ? round(($passedChecks / $totalChecks) * 100, 2) : 0;
    }

    /**
     * Build lightweight payload for quick operations
     */
    public function buildLightweightPayload(
        int $livestockId,
        string $date,
        array $basicData
    ): ProcessingResult {
        try {
            $payload = [
                'schema' => [
                    'version' => '3.0-lite',
                    'type' => 'lightweight'
                ],
                'livestock_id' => $livestockId,
                'date' => $date,
                'timestamp' => now()->toIso8601String(),
                'data' => $basicData,
                'user' => $this->buildUserInfo()
            ];

            return ProcessingResult::success($payload, 'Lightweight payload built successfully');
        } catch (\Exception $e) {
            Log::error('Error building lightweight payload', [
                'livestock_id' => $livestockId,
                'date' => $date,
                'error' => $e->getMessage()
            ]);

            throw new RecordingException("Failed to build lightweight payload: " . $e->getMessage());
        }
    }

    /**
     * Validate payload structure
     */
    public function validatePayloadStructure(array $payload): ProcessingResult
    {
        try {
            $errors = [];

            // Check required sections
            $requiredSections = ['schema', 'recording', 'livestock', 'production'];
            foreach ($requiredSections as $section) {
                if (!isset($payload[$section])) {
                    $errors[] = "Missing required section: {$section}";
                }
            }

            // Check schema version
            if (isset($payload['schema']['version'])) {
                $version = $payload['schema']['version'];
                if (!in_array($version, ['2.0', '3.0', '3.0-lite'])) {
                    $errors[] = "Unsupported schema version: {$version}";
                }
            }

            // Check data types
            if (
                isset($payload['livestock']['population']['initial']) &&
                !is_numeric($payload['livestock']['population']['initial'])
            ) {
                $errors[] = "Invalid data type for livestock.population.initial";
            }

            if (empty($errors)) {
                return ProcessingResult::success([], 'Payload structure is valid');
            }

            return ProcessingResult::failure($errors, 'Payload validation failed');
        } catch (\Exception $e) {
            Log::error('Error validating payload structure', [
                'error' => $e->getMessage()
            ]);

            return ProcessingResult::failure(['Validation failed'], 'Payload validation failed');
        }
    }

    /**
     * Convert payload between versions
     */
    public function convertPayloadVersion(array $payload, string $targetVersion): ProcessingResult
    {
        try {
            $currentVersion = $payload['schema']['version'] ?? '2.0';

            if ($currentVersion === $targetVersion) {
                return ProcessingResult::success($payload, 'No conversion needed');
            }

            // Implement version conversion logic here
            // For now, just update the schema version
            $convertedPayload = $payload;
            $convertedPayload['schema']['version'] = $targetVersion;
            $convertedPayload['schema']['converted_from'] = $currentVersion;
            $convertedPayload['schema']['converted_at'] = now()->toIso8601String();

            return ProcessingResult::success($convertedPayload, 'Payload converted successfully');
        } catch (\Exception $e) {
            Log::error('Error converting payload version', [
                'current_version' => $payload['schema']['version'] ?? 'unknown',
                'target_version' => $targetVersion,
                'error' => $e->getMessage()
            ]);

            return ProcessingResult::failure(['Conversion failed'], 'Payload conversion failed');
        }
    }
}

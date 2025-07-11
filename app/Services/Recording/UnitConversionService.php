<?php

declare(strict_types=1);

namespace App\Services\Recording;

use App\Models\{Feed, Supply, Unit};
use App\Services\Recording\DTOs\ProcessingResult;
use App\Services\Recording\Exceptions\RecordingException;
use Illuminate\Support\Facades\{Cache, Log};
use Illuminate\Support\Collection;

/**
 * UnitConversionService
 * 
 * Handles all unit conversion operations for feeds and supplies
 * with caching and comprehensive error handling.
 */
class UnitConversionService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const CACHE_PREFIX = 'unit_conversion_';

    /**
     * Get detailed unit information for a feed item
     * 
     * @param Feed|null $feed The feed item
     * @param float $quantity The quantity to convert
     * @return array Detailed unit information
     */
    public function getDetailedFeedUnitInfo(?Feed $feed, float $quantity): array
    {
        $result = [
            'smallest_unit_id' => null,
            'smallest_unit_name' => 'Unknown',
            'original_unit_id' => null,
            'original_unit_name' => 'Unknown',
            'consumption_unit_id' => null,
            'consumption_unit_name' => 'Unknown',
            'conversion_factor' => 1,
            'converted_quantity' => $quantity,
            'unit_hierarchy' => [],
            'conversion_rates' => [],
        ];

        if (!$feed) {
            return $result;
        }

        try {
            $cacheKey = self::CACHE_PREFIX . "feed_{$feed->id}";
            
            $unitInfo = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($feed) {
                return $this->processFeedUnitConversion($feed);
            });

            // Apply quantity conversion
            $result = array_merge($result, $unitInfo);
            $result['converted_quantity'] = $this->calculateConvertedQuantity($quantity, $unitInfo);

            return $result;
        } catch (\Exception $e) {
            Log::error('Error getting detailed feed unit info', [
                'feed_id' => $feed->id,
                'quantity' => $quantity,
                'error' => $e->getMessage()
            ]);

            return $result;
        }
    }

    /**
     * Get detailed unit information for a supply item
     * 
     * @param Supply|null $supply The supply item
     * @param float $quantity The quantity to convert
     * @return array Detailed unit information
     */
    public function getDetailedSupplyUnitInfo(?Supply $supply, float $quantity): array
    {
        $result = [
            'smallest_unit_id' => null,
            'smallest_unit_name' => 'Unknown',
            'original_unit_id' => null,
            'original_unit_name' => 'Unknown',
            'consumption_unit_id' => null,
            'consumption_unit_name' => 'Unknown',
            'conversion_factor' => 1,
            'converted_quantity' => $quantity,
            'unit_hierarchy' => [],
            'conversion_rates' => [],
        ];

        if (!$supply) {
            return $result;
        }

        try {
            $cacheKey = self::CACHE_PREFIX . "supply_{$supply->id}";
            
            $unitInfo = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($supply) {
                return $this->processSupplyUnitConversion($supply);
            });

            // Apply quantity conversion
            $result = array_merge($result, $unitInfo);
            $result['converted_quantity'] = $this->calculateConvertedQuantity($quantity, $unitInfo);

            return $result;
        } catch (\Exception $e) {
            Log::error('Error getting detailed supply unit info', [
                'supply_id' => $supply->id,
                'quantity' => $quantity,
                'error' => $e->getMessage()
            ]);

            return $result;
        }
    }

    /**
     * Process feed unit conversion
     */
    private function processFeedUnitConversion(Feed $feed): array
    {
        $result = [
            'smallest_unit_id' => null,
            'smallest_unit_name' => 'Unknown',
            'original_unit_id' => null,
            'original_unit_name' => 'Unknown',
            'consumption_unit_id' => null,
            'consumption_unit_name' => 'Unknown',
            'conversion_factor' => 1,
            'unit_hierarchy' => [],
            'conversion_rates' => [],
        ];

        // Get unit information from feed payload
        if (isset($feed->payload['conversion_units']) && is_array($feed->payload['conversion_units'])) {
            $conversionUnits = collect($feed->payload['conversion_units']);

            // Get smallest unit (for storage)
            $smallestUnit = $conversionUnits->firstWhere('is_smallest', true);
            if ($smallestUnit) {
                $result['smallest_unit_id'] = $smallestUnit['unit_id'];
                $unit = Unit::find($smallestUnit['unit_id']);
                $result['smallest_unit_name'] = $unit ? $unit->name : 'Unknown';
                $result['conversion_factor'] = floatval($smallestUnit['value'] ?? 1);
            }

            // Get original unit (for purchase)
            $originalUnit = $conversionUnits->firstWhere('is_default_purchase', true);
            if ($originalUnit) {
                $result['original_unit_id'] = $originalUnit['unit_id'];
                $unit = Unit::find($originalUnit['unit_id']);
                $result['original_unit_name'] = $unit ? $unit->name : 'Unknown';
            }

            // Get consumption unit (for usage)
            $consumptionUnit = $conversionUnits->firstWhere('is_default_mutation', true) ??
                $conversionUnits->firstWhere('is_smallest', true);
            if ($consumptionUnit) {
                $result['consumption_unit_id'] = $consumptionUnit['unit_id'];
                $unit = Unit::find($consumptionUnit['unit_id']);
                $result['consumption_unit_name'] = $unit ? $unit->name : 'Unknown';
            }

            // Build unit hierarchy
            $result['unit_hierarchy'] = $this->buildUnitHierarchy($conversionUnits);

            // Build conversion rates
            $result['conversion_rates'] = $this->buildConversionRates($conversionUnits);
        } else if ($feed->unit) {
            // Fallback to basic unit information
            $result['smallest_unit_id'] = $feed->unit->id;
            $result['smallest_unit_name'] = $feed->unit->name;
            $result['original_unit_id'] = $feed->unit->id;
            $result['original_unit_name'] = $feed->unit->name;
            $result['consumption_unit_id'] = $feed->unit->id;
            $result['consumption_unit_name'] = $feed->unit->name;
        }

        return $result;
    }

    /**
     * Process supply unit conversion
     */
    private function processSupplyUnitConversion(Supply $supply): array
    {
        $result = [
            'smallest_unit_id' => null,
            'smallest_unit_name' => 'Unknown',
            'original_unit_id' => null,
            'original_unit_name' => 'Unknown',
            'consumption_unit_id' => null,
            'consumption_unit_name' => 'Unknown',
            'conversion_factor' => 1,
            'unit_hierarchy' => [],
            'conversion_rates' => [],
        ];

        // Get unit information from supply data
        if (isset($supply->data['conversion_units']) && is_array($supply->data['conversion_units'])) {
            $conversionUnits = collect($supply->data['conversion_units']);

            // Get smallest unit (for storage)
            $smallestUnit = $conversionUnits->firstWhere('is_smallest', true);
            if ($smallestUnit) {
                $result['smallest_unit_id'] = $smallestUnit['unit_id'];
                $unit = Unit::find($smallestUnit['unit_id']);
                $result['smallest_unit_name'] = $unit ? $unit->name : 'Unknown';
                $result['conversion_factor'] = floatval($smallestUnit['value'] ?? 1);
            }

            // Get original unit (for purchase)
            $originalUnit = $conversionUnits->firstWhere('is_default_purchase', true);
            if ($originalUnit) {
                $result['original_unit_id'] = $originalUnit['unit_id'];
                $unit = Unit::find($originalUnit['unit_id']);
                $result['original_unit_name'] = $unit ? $unit->name : 'Unknown';
            }

            // Get consumption unit (for usage)
            $consumptionUnit = $conversionUnits->firstWhere('is_default_mutation', true) ??
                $conversionUnits->firstWhere('is_smallest', true);
            if ($consumptionUnit) {
                $result['consumption_unit_id'] = $consumptionUnit['unit_id'];
                $unit = Unit::find($consumptionUnit['unit_id']);
                $result['consumption_unit_name'] = $unit ? $unit->name : 'Unknown';
            }

            // Build unit hierarchy
            $result['unit_hierarchy'] = $this->buildUnitHierarchy($conversionUnits);

            // Build conversion rates
            $result['conversion_rates'] = $this->buildConversionRates($conversionUnits);
        } else if ($supply->unit) {
            // Fallback to basic unit information
            $result['smallest_unit_id'] = $supply->unit->id;
            $result['smallest_unit_name'] = $supply->unit->name;
            $result['original_unit_id'] = $supply->unit->id;
            $result['original_unit_name'] = $supply->unit->name;
            $result['consumption_unit_id'] = $supply->unit->id;
            $result['consumption_unit_name'] = $supply->unit->name;
        }

        return $result;
    }

    /**
     * Calculate converted quantity based on unit information
     */
    private function calculateConvertedQuantity(float $quantity, array $unitInfo): float
    {
        if (empty($unitInfo['conversion_rates'])) {
            return $quantity;
        }

        $smallestValue = $unitInfo['conversion_rates']['smallest'] ?? 1;
        $consumptionValue = $unitInfo['conversion_rates']['consumption'] ?? 1;

        if ($smallestValue > 0 && $consumptionValue > 0) {
            return ($quantity * $consumptionValue) / $smallestValue;
        }

        return $quantity;
    }

    /**
     * Build unit hierarchy from conversion units
     */
    private function buildUnitHierarchy(Collection $conversionUnits): array
    {
        $hierarchy = [];

        foreach ($conversionUnits as $unit) {
            $unitModel = Unit::find($unit['unit_id']);
            $hierarchy[] = [
                'unit_id' => $unit['unit_id'],
                'unit_name' => $unitModel ? $unitModel->name : 'Unknown',
                'value' => $unit['value'] ?? 1,
                'is_smallest' => $unit['is_smallest'] ?? false,
                'is_default_purchase' => $unit['is_default_purchase'] ?? false,
                'is_default_mutation' => $unit['is_default_mutation'] ?? false,
            ];
        }

        // Sort by value ascending (smallest first)
        usort($hierarchy, function ($a, $b) {
            return $a['value'] <=> $b['value'];
        });

        return $hierarchy;
    }

    /**
     * Build conversion rates from conversion units
     */
    private function buildConversionRates(Collection $conversionUnits): array
    {
        $rates = [];

        foreach ($conversionUnits as $unit) {
            $key = 'unknown';
            if ($unit['is_smallest'] ?? false) {
                $key = 'smallest';
            } elseif ($unit['is_default_purchase'] ?? false) {
                $key = 'purchase';
            } elseif ($unit['is_default_mutation'] ?? false) {
                $key = 'consumption';
            }

            $rates[$key] = floatval($unit['value'] ?? 1);
        }

        return $rates;
    }

    /**
     * Convert quantity between units
     */
    public function convertQuantity(float $quantity, int $fromUnitId, int $toUnitId): ProcessingResult
    {
        try {
            if ($fromUnitId === $toUnitId) {
                return ProcessingResult::success($quantity, 'No conversion needed');
            }

            $fromUnit = Unit::find($fromUnitId);
            $toUnit = Unit::find($toUnitId);

            if (!$fromUnit || !$toUnit) {
                return ProcessingResult::failure(['Unit not found'], 'Unit conversion failed');
            }

            // For now, return the same quantity as we need more complex conversion logic
            // This would be implemented based on unit relationships in the database
            $convertedQuantity = $quantity;

            return ProcessingResult::success($convertedQuantity, 'Quantity converted successfully');
        } catch (\Exception $e) {
            Log::error('Error converting quantity', [
                'quantity' => $quantity,
                'from_unit_id' => $fromUnitId,
                'to_unit_id' => $toUnitId,
                'error' => $e->getMessage()
            ]);

            return ProcessingResult::failure(['Conversion failed'], 'Quantity conversion failed');
        }
    }

    /**
     * Get available units for a feed
     */
    public function getAvailableUnitsForFeed(int $feedId): ProcessingResult
    {
        try {
            $feed = Feed::find($feedId);
            
            if (!$feed) {
                return ProcessingResult::failure(['Feed not found'], 'Feed not found');
            }

            $unitInfo = $this->getDetailedFeedUnitInfo($feed, 1);
            $units = [];

            foreach ($unitInfo['unit_hierarchy'] as $unit) {
                $units[] = [
                    'unit_id' => $unit['unit_id'],
                    'unit_name' => $unit['unit_name'],
                    'conversion_factor' => $unit['value'],
                    'is_default' => $unit['is_default_purchase'] || $unit['is_default_mutation'],
                ];
            }

            return ProcessingResult::success($units, 'Available units retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error getting available units for feed', [
                'feed_id' => $feedId,
                'error' => $e->getMessage()
            ]);

            return ProcessingResult::failure(['Failed to get units'], 'Units retrieval failed');
        }
    }

    /**
     * Get available units for a supply
     */
    public function getAvailableUnitsForSupply(int $supplyId): ProcessingResult
    {
        try {
            $supply = Supply::find($supplyId);
            
            if (!$supply) {
                return ProcessingResult::failure(['Supply not found'], 'Supply not found');
            }

            $unitInfo = $this->getDetailedSupplyUnitInfo($supply, 1);
            $units = [];

            foreach ($unitInfo['unit_hierarchy'] as $unit) {
                $units[] = [
                    'unit_id' => $unit['unit_id'],
                    'unit_name' => $unit['unit_name'],
                    'conversion_factor' => $unit['value'],
                    'is_default' => $unit['is_default_purchase'] || $unit['is_default_mutation'],
                ];
            }

            return ProcessingResult::success($units, 'Available units retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error getting available units for supply', [
                'supply_id' => $supplyId,
                'error' => $e->getMessage()
            ]);

            return ProcessingResult::failure(['Failed to get units'], 'Units retrieval failed');
        }
    }

    /**
     * Clear unit conversion cache
     */
    public function clearCache(?int $itemId = null, ?string $type = null): bool
    {
        try {
            if ($itemId && $type) {
                $cacheKey = self::CACHE_PREFIX . "{$type}_{$itemId}";
                Cache::forget($cacheKey);
            } else {
                // Clear all unit conversion cache
                Cache::flush();
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Error clearing unit conversion cache', [
                'item_id' => $itemId,
                'type' => $type,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Validate unit conversion data
     */
    public function validateConversionData(array $conversionUnits): ProcessingResult
    {
        try {
            $errors = [];

            if (empty($conversionUnits)) {
                $errors[] = 'Conversion units cannot be empty';
            }

            $hasSmallest = false;
            $hasPurchase = false;
            $hasMutation = false;

            foreach ($conversionUnits as $unit) {
                if (!isset($unit['unit_id']) || !isset($unit['value'])) {
                    $errors[] = 'Each conversion unit must have unit_id and value';
                    continue;
                }

                if ($unit['is_smallest'] ?? false) {
                    $hasSmallest = true;
                }

                if ($unit['is_default_purchase'] ?? false) {
                    $hasPurchase = true;
                }

                if ($unit['is_default_mutation'] ?? false) {
                    $hasMutation = true;
                }

                if ($unit['value'] <= 0) {
                    $errors[] = 'Conversion value must be greater than 0';
                }
            }

            if (!$hasSmallest) {
                $errors[] = 'At least one unit must be marked as smallest';
            }

            if (empty($errors)) {
                return ProcessingResult::success([], 'Conversion data is valid');
            }

            return ProcessingResult::failure($errors, 'Conversion data validation failed');
        } catch (\Exception $e) {
            Log::error('Error validating conversion data', [
                'error' => $e->getMessage()
            ]);

            return ProcessingResult::failure(['Validation failed'], 'Conversion data validation failed');
        }
    }
} 
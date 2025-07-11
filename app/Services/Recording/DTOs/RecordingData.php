<?php

declare(strict_types=1);

namespace App\Services\Recording\DTOs;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * RecordingData DTO
 * 
 * Handles recording data with comprehensive livestock information, calculations, and metadata.
 * Used across all recording services for consistent data handling.
 */
class RecordingData
{
    /**
     * Create a new recording data instance.
     *
     * @param string $livestockId
     * @param Carbon $date
     * @param int $age
     * @param float $bodyWeight
     * @param int $mortality
     * @param int $culling
     * @param int $sale
     * @param int $transfer
     * @param array $feedUsages
     * @param array $supplyUsages
     * @param array $depletionData
     * @param array $performanceMetrics
     * @param array $metadata
     */
    public function __construct(
        public readonly string $livestockId,
        public readonly Carbon $date,
        public readonly int $age,
        public readonly float $bodyWeight,
        public readonly int $mortality,
        public readonly int $culling,
        public readonly int $sale,
        public readonly int $transfer,
        public readonly array $feedUsages = [],
        public readonly array $supplyUsages = [],
        public readonly array $depletionData = [],
        public readonly array $performanceMetrics = [],
        public readonly array $metadata = []
    ) {
        // Log recording data creation for debugging (only in debug mode)
        if (config('app.debug')) {
            Log::debug('RecordingData created', [
                'livestock_id' => $this->livestockId,
                'date' => $this->date->format('Y-m-d'),
                'age' => $this->age,
                'weight_gain' => $this->getWeightGain(),
                'total_depletion' => $this->getTotalDepletion(),
                'feed_usage_count' => count($this->feedUsages),
                'supply_usage_count' => count($this->supplyUsages),
            ]);
        }
    }

    /**
     * Calculate weight gain from yesterday to today.
     *
     * @return float
     */
    public function getWeightGain(): float
    {
        return $this->bodyWeight - $this->getMetadata('body_weight_yesterday', 0.0);
    }

    /**
     * Calculate total depletion (mortality + culling).
     *
     * @return int
     */
    public function getTotalDepletion(): int
    {
        return $this->mortality + $this->culling + $this->sale + $this->transfer;
    }

    /**
     * Calculate total feed usage quantity.
     *
     * @return float
     */
    public function getTotalFeedConsumption(): float
    {
        $total = 0.0;

        foreach ($this->feedUsages as $feedId => $data) {
            // Handle both legacy format (feedId => quantity) and new format (array of feed usage objects)
            if (is_array($data)) {
                // New format: array with feed_id, quantity, etc.
                $quantity = $data['quantity'] ?? 0.0;
                $total += $quantity;
            } else {
                // Legacy format: feedId => quantity
                $total += $data;
            }
        }

        return $total;
    }

    /**
     * Calculate total supply usage quantity.
     *
     * @return float
     */
    public function getTotalSupplyConsumption(): float
    {
        $total = 0.0;

        foreach ($this->supplyUsages as $supplyId => $data) {
            // Handle both legacy format (supplyId => quantity) and new format (array of supply usage objects)
            if (is_array($data)) {
                // New format: array with supply_id, quantity, etc.
                $quantity = $data['quantity'] ?? 0.0;
                $total += $quantity;
            } else {
                // Legacy format: supplyId => quantity
                $total += $data;
            }
        }

        return $total;
    }

    /**
     * Calculate total feed cost.
     *
     * @return float
     */
    public function getFeedCost(): float
    {
        $total = 0.0;

        foreach ($this->feedUsages as $feedId => $data) {
            // Handle both legacy format (feedId => quantity) and new format (array of feed usage objects)
            if (is_array($data)) {
                // New format: array with feed_id, quantity, etc.
                $feedIdKey = $data['feed_id'] ?? null;
                $quantity = $data['quantity'] ?? 0.0;

                if ($feedIdKey !== null) {
                    $unitCost = $this->getMetadata("feed_costs.{$feedIdKey}", 0.0);
                    $total += $quantity * $unitCost;
                }
            } else {
                // Legacy format: feedId => quantity
                $quantity = $data;
                $unitCost = $this->getMetadata("feed_costs.{$feedId}", 0.0);
                $total += $quantity * $unitCost;
            }
        }

        return $total;
    }

    /**
     * Calculate total supply cost.
     *
     * @return float
     */
    public function getSupplyCost(): float
    {
        $total = 0.0;

        foreach ($this->supplyUsages as $supplyId => $data) {
            // Handle both legacy format (supplyId => quantity) and new format (array of supply usage objects)
            if (is_array($data)) {
                // New format: array with supply_id, quantity, etc.
                $supplyIdKey = $data['supply_id'] ?? null;
                $quantity = $data['quantity'] ?? 0.0;

                if ($supplyIdKey !== null) {
                    $unitCost = $this->getMetadata("supply_costs.{$supplyIdKey}", 0.0);
                    $total += $quantity * $unitCost;
                }
            } else {
                // Legacy format: supplyId => quantity
                $quantity = $data;
                $unitCost = $this->getMetadata("supply_costs.{$supplyId}", 0.0);
                $total += $quantity * $unitCost;
            }
        }

        return $total;
    }

    /**
     * Calculate total operational cost.
     *
     * @return float
     */
    public function getTotalCost(): float
    {
        return $this->getFeedCost() + $this->getSupplyCost();
    }

    /**
     * Calculate sales revenue.
     *
     * @return float
     */
    public function getRevenueEstimate(): float
    {
        $salePrice = $this->getMetadata('sale_price_per_kg', 0.0);
        $currentWeight = $this->bodyWeight;
        $saleQuantity = $this->sale;

        return $salePrice * $currentWeight * $saleQuantity;
    }

    /**
     * Get feed usage by feed ID.
     *
     * @param int $feedId
     * @return float
     */
    public function getFeedUsage(int $feedId): float
    {
        if (!isset($this->feedUsages[$feedId])) {
            return 0.0;
        }

        $data = $this->feedUsages[$feedId];

        // Handle both legacy format (feedId => quantity) and new format (array of feed usage objects)
        if (is_array($data)) {
            // New format: array with feed_id, quantity, etc.
            return $data['quantity'] ?? 0.0;
        } else {
            // Legacy format: feedId => quantity
            return $data;
        }
    }

    /**
     * Get supply usage by supply ID.
     *
     * @param int $supplyId
     * @return float
     */
    public function getSupplyUsage(int $supplyId): float
    {
        if (!isset($this->supplyUsages[$supplyId])) {
            return 0.0;
        }

        $data = $this->supplyUsages[$supplyId];

        // Handle both legacy format (supplyId => quantity) and new format (array of supply usage objects)
        if (is_array($data)) {
            // New format: array with supply_id, quantity, etc.
            return $data['quantity'] ?? 0.0;
        } else {
            // Legacy format: supplyId => quantity
            return $data;
        }
    }

    /**
     * Get specific metadata value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return data_get($this->metadata, $key, $default);
    }

    /**
     * Check if metadata exists.
     *
     * @param string $key
     * @return bool
     */
    public function hasMetadata(string $key): bool
    {
        return isset($this->metadata[$key]);
    }

    /**
     * Check if has any feed usage.
     *
     * @return bool
     */
    public function hasFeedUsage(): bool
    {
        return !empty($this->feedUsages);
    }

    /**
     * Check if has any supply usage.
     *
     * @return bool
     */
    public function hasSupplyUsage(): bool
    {
        return !empty($this->supplyUsages);
    }

    /**
     * Check if has any depletion.
     *
     * @return bool
     */
    public function hasDepletion(): bool
    {
        return $this->getTotalDepletion() > 0;
    }

    /**
     * Check if has any sales.
     *
     * @return bool
     */
    public function hasSales(): bool
    {
        return $this->sale > 0;
    }

    /**
     * Check if weight gain is positive.
     *
     * @return bool
     */
    public function hasWeightGain(): bool
    {
        return $this->getWeightGain() > 0;
    }

    /**
     * Check if has performance metrics.
     *
     * @return bool
     */
    public function hasPerformanceMetrics(): bool
    {
        return !empty($this->performanceMetrics);
    }

    /**
     * Get feed conversion ratio.
     *
     * @return float
     */
    public function getFeedConversionRatio(): float
    {
        $weightGain = $this->getWeightGain();
        $feedConsumption = $this->getTotalFeedConsumption();

        if ($weightGain <= 0) {
            return 0.0;
        }

        return $feedConsumption / $weightGain;
    }

    /**
     * Get cost per kg of weight gain.
     *
     * @return float
     */
    public function getCostPerKgGain(): float
    {
        $weightGain = $this->getWeightGain();

        if ($weightGain <= 0) {
            return 0.0;
        }

        return $this->getTotalCost() / $weightGain;
    }

    /**
     * Get performance metric.
     *
     * @param string $key
     * @return mixed
     */
    public function getPerformanceMetric(string $key): mixed
    {
        return $this->performanceMetrics[$key] ?? null;
    }

    /**
     * Get feed usage summary.
     *
     * @return array
     */
    public function getFeedUsageSummary(): array
    {
        $summary = [];

        foreach ($this->feedUsages as $feedId => $quantity) {
            $feedName = $this->getMetadata("feed_names.{$feedId}", "Feed {$feedId}");
            $cost = $quantity * $this->getMetadata("feed_costs.{$feedId}", 0.0);

            $summary[] = [
                'feed_id' => $feedId,
                'feed_name' => $feedName,
                'quantity' => $quantity,
                'cost' => $cost
            ];
        }

        return $summary;
    }

    /**
     * Get supply usage summary.
     *
     * @return array
     */
    public function getSupplyUsageSummary(): array
    {
        $summary = [];

        foreach ($this->supplyUsages as $supplyId => $quantity) {
            $supplyName = $this->getMetadata("supply_names.{$supplyId}", "Supply {$supplyId}");
            $cost = $quantity * $this->getMetadata("supply_costs.{$supplyId}", 0.0);

            $summary[] = [
                'supply_id' => $supplyId,
                'supply_name' => $supplyName,
                'quantity' => $quantity,
                'cost' => $cost
            ];
        }

        return $summary;
    }

    /**
     * Get depletion summary.
     *
     * @return array
     */
    public function getDepletionSummary(): array
    {
        return [
            'mortality' => $this->mortality,
            'culling' => $this->culling,
            'sale' => $this->sale,
            'transfer' => $this->transfer,
            'total' => $this->getTotalDepletion()
        ];
    }

    /**
     * Get business summary.
     *
     * @return array
     */
    public function getBusinessSummary(): array
    {
        return [
            'date' => $this->date->toDateString(),
            'age' => $this->age,
            'body_weight' => $this->bodyWeight,
            'weight_gain' => $this->getWeightGain(),
            'total_feed_consumption' => $this->getTotalFeedConsumption(),
            'total_supply_consumption' => $this->getTotalSupplyConsumption(),
            'total_depletion' => $this->getTotalDepletion(),
            'feed_conversion_ratio' => $this->getFeedConversionRatio(),
            'feed_cost' => $this->getFeedCost(),
            'supply_cost' => $this->getSupplyCost(),
            'total_cost' => $this->getTotalCost(),
            'cost_per_kg_gain' => $this->getCostPerKgGain()
        ];
    }

    /**
     * Validate recording data.
     *
     * @return ValidationResult
     */
    public function validate(): ValidationResult
    {
        $errors = [];
        $warnings = [];

        // Basic validation
        if ($this->livestockId <= 0) {
            $errors[] = 'Livestock ID must be greater than 0';
        }

        if ($this->age < 0) {
            $errors[] = 'Age cannot be negative';
        }

        if ($this->bodyWeight < 0) {
            $errors[] = 'Body weight cannot be negative';
        }

        if ($this->mortality < 0) {
            $errors[] = 'Mortality cannot be negative';
        }

        if ($this->culling < 0) {
            $errors[] = 'Culling cannot be negative';
        }

        if ($this->sale < 0) {
            $errors[] = 'Sale cannot be negative';
        }

        if ($this->transfer < 0) {
            $errors[] = 'Transfer cannot be negative';
        }

        // Business logic validation
        if ($this->getTotalDepletion() > 1000) {
            $warnings[] = 'Total depletion seems unusually high';
        }

        if ($this->getTotalFeedConsumption() > 1000) {
            $warnings[] = 'Feed consumption seems unusually high';
        }

        if ($this->getFeedConversionRatio() > 3.0) {
            $warnings[] = 'Feed conversion ratio seems poor';
        }

        // Feed usage validation
        foreach ($this->feedUsages as $feedId => $quantity) {
            if ($quantity < 0) {
                $errors[] = "Feed usage for feed {$feedId} cannot be negative";
            }
        }

        // Supply usage validation
        foreach ($this->supplyUsages as $supplyId => $quantity) {
            if ($quantity < 0) {
                $errors[] = "Supply usage for supply {$supplyId} cannot be negative";
            }
        }

        return empty($errors)
            ? ValidationResult::success('Recording data is valid', ['warnings' => $warnings])
            : ValidationResult::failure($errors, 'Recording data validation failed', $warnings);
    }

    /**
     * Convert to array format.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'livestock_id' => $this->livestockId,
            'date' => $this->date->format('Y-m-d'),
            'age' => $this->age,
            'body_weight' => $this->bodyWeight,
            'mortality' => $this->mortality,
            'culling' => $this->culling,
            'sale' => $this->sale,
            'transfer' => $this->transfer,
            'feed_usages' => $this->feedUsages,
            'supply_usages' => $this->supplyUsages,
            'depletion_data' => $this->depletionData,
            'performance_metrics' => $this->performanceMetrics,
            'metadata' => $this->metadata,
            'business_summary' => $this->getBusinessSummary()
        ];
    }

    /**
     * Convert to JSON string.
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }

    /**
     * Create recording data from array.
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['livestock_id'],
            Carbon::parse($data['date']),
            $data['age'] ?? 0,
            (float) $data['body_weight'],
            $data['mortality'] ?? 0,
            $data['culling'] ?? 0,
            $data['sale'] ?? 0,
            $data['transfer'] ?? 0,
            $data['feed_usages'] ?? [],
            $data['supply_usages'] ?? [],
            $data['depletion_data'] ?? [],
            $data['performance_metrics'] ?? [],
            $data['metadata'] ?? []
        );
    }

    /**
     * Create recording data from Records component data.
     *
     * @param object $component
     * @return self
     */
    public static function fromRecordsComponent(object $component): self
    {
        return new self(
            $component->livestockId,
            Carbon::parse($component->date),
            $component->age,
            (float) $component->bodyWeight,
            $component->mortality ?? 0,
            $component->culling ?? 0,
            $component->sale ?? 0,
            $component->transfer ?? 0,
            $component->itemQuantities ?? [],
            $component->supplyQuantities ?? [],
            [
                'mortality' => $component->mortality ?? 0,
                'culling' => $component->culling ?? 0,
                'sale' => $component->sale ?? 0,
                'transfer' => $component->transfer ?? 0,
            ],
            $component->performanceMetrics ?? [],
            [
                'source' => 'records_component',
                'created_at' => now()->toISOString(),
                'user_id' => null
            ]
        );
    }

    /**
     * Create empty recording data for specific livestock.
     *
     * @param string $livestockId
     * @param Carbon|null $date
     * @return self
     */
    public static function empty(string $livestockId, ?Carbon $date = null): self
    {
        return new self(
            $livestockId,
            $date ?? Carbon::now(),
            0,
            0.0,
            0,
            0,
            0,
            0
        );
    }

    /**
     * Get profit estimate.
     *
     * @return float
     */
    public function getProfitEstimate(): float
    {
        return $this->getRevenueEstimate() - $this->getTotalCost();
    }

    /**
     * Get efficiency metrics.
     *
     * @return array
     */
    public function getEfficiencyMetrics(): array
    {
        return [
            'feed_conversion_ratio' => $this->getFeedConversionRatio(),
            'cost_per_kg_gain' => $this->getCostPerKgGain(),
            'feed_efficiency' => $this->getWeightGain() / $this->getTotalFeedConsumption(),
            'cost_efficiency' => $this->getWeightGain() / $this->getTotalCost()
        ];
    }

    /**
     * Create with additional metadata.
     *
     * @param array $metadata
     * @return self
     */
    public function withMetadata(array $metadata): self
    {
        return new self(
            livestockId: $this->livestockId,
            date: $this->date,
            age: $this->age,
            bodyWeight: $this->bodyWeight,
            mortality: $this->mortality,
            culling: $this->culling,
            sale: $this->sale,
            transfer: $this->transfer,
            feedUsages: $this->feedUsages,
            supplyUsages: $this->supplyUsages,
            depletionData: $this->depletionData,
            performanceMetrics: $this->performanceMetrics,
            metadata: array_merge($this->metadata, $metadata)
        );
    }

    /**
     * Create with updated feed usages.
     *
     * @param array $feedUsages
     * @return self
     */
    public function withFeedUsages(array $feedUsages): self
    {
        return new self(
            livestockId: $this->livestockId,
            date: $this->date,
            age: $this->age,
            bodyWeight: $this->bodyWeight,
            mortality: $this->mortality,
            culling: $this->culling,
            sale: $this->sale,
            transfer: $this->transfer,
            feedUsages: $feedUsages,
            supplyUsages: $this->supplyUsages,
            depletionData: $this->depletionData,
            performanceMetrics: $this->performanceMetrics,
            metadata: $this->metadata
        );
    }

    /**
     * Create with updated supply usages.
     *
     * @param array $supplyUsages
     * @return self
     */
    public function withSupplyUsages(array $supplyUsages): self
    {
        return new self(
            livestockId: $this->livestockId,
            date: $this->date,
            age: $this->age,
            bodyWeight: $this->bodyWeight,
            mortality: $this->mortality,
            culling: $this->culling,
            sale: $this->sale,
            transfer: $this->transfer,
            feedUsages: $this->feedUsages,
            supplyUsages: $supplyUsages,
            depletionData: $this->depletionData,
            performanceMetrics: $this->performanceMetrics,
            metadata: $this->metadata
        );
    }

    /**
     * Debug information.
     *
     * @return array
     */
    public function debug(): array
    {
        return [
            'basic_info' => [
                'livestock_id' => $this->livestockId,
                'date' => $this->date->toDateString(),
                'age' => $this->age,
                'body_weight' => $this->bodyWeight
            ],
            'depletion_info' => $this->getDepletionSummary(),
            'usage_info' => [
                'feed_usage_count' => count($this->feedUsages),
                'supply_usage_count' => count($this->supplyUsages),
                'total_feed_consumption' => $this->getTotalFeedConsumption(),
                'total_supply_consumption' => $this->getTotalSupplyConsumption()
            ],
            'business_info' => [
                'feed_cost' => $this->getFeedCost(),
                'supply_cost' => $this->getSupplyCost(),
                'total_cost' => $this->getTotalCost(),
                'revenue_estimate' => $this->getRevenueEstimate(),
                'profit_estimate' => $this->getProfitEstimate()
            ],
            'efficiency_info' => $this->getEfficiencyMetrics()
        ];
    }
}

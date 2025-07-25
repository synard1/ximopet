<?php

declare(strict_types=1);

namespace App\Services\Recording\Contracts;

use App\Services\Recording\DTOs\ServiceResult;

/**
 * RecordingDataAggregatorInterface
 * 
 * Interface for recording data aggregation services that can handle data from multiple sources:
 * - Feed Usage (manual and automated)
 * - Supply Usage (with status filtering)
 * - Depletion (mortality, culling, sales)
 * - Recording (weight, population, performance metrics)
 * 
 * @version 1.0
 * @since 2025-01-25
 */
interface RecordingDataAggregatorInterface
{
    /**
     * Aggregate all recording data for a specific livestock and date
     * 
     * @param string $livestockId
     * @param string $date
     * @param array $options
     * @return ServiceResult
     */
    public function aggregateData(string $livestockId, string $date, array $options = []): ServiceResult;

    /**
     * Get service configuration
     * 
     * @return array
     */
    public function getConfig(): array;

    /**
     * Update service configuration
     * 
     * @param array $newConfig
     * @return void
     */
    public function updateConfig(array $newConfig): void;

    /**
     * Reset service configuration to defaults
     * 
     * @return void
     */
    public function resetConfig(): void;
}

<?php

declare(strict_types=1);

namespace App\Services\Recording\Contracts;

use App\Services\Recording\DTOs\ProcessingResult;
use Carbon\Carbon;

/**
 * FeedSupplyProcessingServiceInterface
 * 
 * Interface for feed and supply processing operations
 */
interface FeedSupplyProcessingServiceInterface
{
    /**
     * Save feed usage with enhanced tracking
     */
    public function saveFeedUsageWithTracking(
        array $data,
        string $recordingId,
        string $livestockId,
        string $date,
        array $usages,
        ?string $feedUsageId = null
    ): ProcessingResult;

    /**
     * Save supply usage with enhanced tracking
     */
    public function saveSupplyUsageWithTracking(
        array $data,
        string $recordingId,
        string $livestockId,
        string $date,
        array $supplyUsages,
        ?string $supplyUsageId = null
    ): ProcessingResult;

    /**
     * Get feed usage statistics
     */
    public function getFeedUsageStatistics(string $livestockId, ?Carbon $startDate = null, ?Carbon $endDate = null): ProcessingResult;

    /**
     * Get supply usage statistics
     */
    public function getSupplyUsageStatistics(string $livestockId, ?Carbon $startDate = null, ?Carbon $endDate = null): ProcessingResult;

    /**
     * Process feed usage data
     */
    public function processFeedUsage(array $data): ProcessingResult;

    /**
     * Process supply usage data  
     */
    public function processSupplyUsage(array $data): ProcessingResult;
}

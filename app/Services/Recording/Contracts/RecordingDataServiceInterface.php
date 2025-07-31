<?php

declare(strict_types=1);

namespace App\Services\Recording\Contracts;

use App\Services\Recording\DTOs\ServiceResult;

/**
 * Interface for services that handle loading and retrieving recording-related data.
 *
 * This contract ensures that any data loading service will have a consistent API
 * for the Records component to interact with, making it easier to swap implementations
 * in the future (e.g., from a basic DB implementation to a cached one).
 *
 * @version 1.0
 * @since 2025-07-09
 */
interface RecordingDataServiceInterface
{
    /**
     * Load all necessary data for a specific recording date.
     * This includes current weight, depletion, feed usage, etc.
     *
     * @param string $livestockId The ID of the livestock.
     * @param string $date The selected date.
     * @param bool $bypassCache Whether to bypass the cache.
     * @return ServiceResult A result object containing the data or an error.
     */
    public function loadCurrentDateData(string $livestockId, string $date, bool $bypassCache = false): ServiceResult;

    /**
     * Load a summary of the previous day's data.
     *
     * @param string $livestockId The ID of the livestock.
     * @param string $yesterdayDate The date of the previous day.
     * @return ServiceResult A result object containing the data or an error.
     */
    public function loadYesterdayData(string $livestockId, string $yesterdayDate): ServiceResult;

    /**
     * Load the historical recording data for display in a table.
     *
     * @param string $livestockId The ID of the livestock.
     * @return ServiceResult A result object containing the table data or an error.
     */
    public function loadRecordingDataForTable(string $livestockId): ServiceResult;

    /**
     * Get the livestock summary.
     *
     * @param string $livestockId The ID of the livestock.
     * @return array The livestock summary.
     */
    public function getLivestockSummary(string $livestockId): array;
}

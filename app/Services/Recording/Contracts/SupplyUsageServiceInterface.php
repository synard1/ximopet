<?php

declare(strict_types=1);

namespace App\Services\Recording\Contracts;

use App\Services\Recording\DTOs\ProcessingResult;
use Carbon\Carbon;

interface SupplyUsageServiceInterface
{
    /**
     * Record supply usage for livestock
     */
    public function recordSupplyUsage(int $livestockId, int $supplyId, float $quantity, Carbon $date, int $recordingId): ProcessingResult;

    /**
     * Rollback supply usage
     */
    public function rollbackSupplyUsage(int $supplyUsageId): ProcessingResult;

    /**
     * Get supply usage statistics
     */
    public function getSupplyUsageStats(int $livestockId, Carbon $startDate, Carbon $endDate): array;
}

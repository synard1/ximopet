<?php

declare(strict_types=1);

namespace App\Services\Recording\Contracts;

use App\Services\Recording\DTOs\ProcessingResult;
use Carbon\Carbon;

interface FeedUsageServiceInterface
{
    /**
     * Record feed usage for livestock
     */
    public function recordFeedUsage(int $livestockId, int $feedId, float $quantity, Carbon $date, int $recordingId): ProcessingResult;

    /**
     * Rollback feed usage
     */
    public function rollbackFeedUsage(int $feedUsageId): ProcessingResult;

    /**
     * Get feed usage statistics
     */
    public function getFeedUsageStats(int $livestockId, Carbon $startDate, Carbon $endDate): array;
}

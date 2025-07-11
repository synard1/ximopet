<?php

declare(strict_types=1);

namespace App\Services\Recording\Contracts;

use App\Services\Recording\DTOs\ProcessingResult;
use Carbon\Carbon;

interface LivestockSynchronizationServiceInterface
{
    /**
     * Synchronize livestock data
     */
    public function synchronizeLivestock(int $livestockId, Carbon $date): ProcessingResult;

    /**
     * Synchronize all livestock for a farm
     */
    public function synchronizeFarmLivestock(int $farmId): ProcessingResult;

    /**
     * Get synchronization status
     */
    public function getSynchronizationStatus(int $livestockId): array;
}

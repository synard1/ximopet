<?php

namespace App\Traits;

use App\Services\History\JsonHistoryService;
use Illuminate\Support\Arr;

trait HasJsonHistory
{
    /**
     * Append history under a flexible JSON path on a given array column.
     *
     * @param string $column Array/JSON attribute name on the model (e.g., 'data', 'metadata', 'cost_details')
     * @param string $historyPath Dot path under the column to store history (e.g., 'history', 'changes.history')
     * @param array $entry The history entry payload
     * @param int $maxEntries Cap number of entries (default 5)
     */
    public function appendJsonHistory(string $column, string $historyPath, array $entry, int $maxEntries = 5): void
    {
        $service = app(JsonHistoryService::class);

        $container = (array) ($this->getAttribute($column) ?? []);
        $fullPath = $historyPath; // path inside column only
        $container = $service->appendEntry($container, $fullPath, $entry, $maxEntries);
        $this->setAttribute($column, $container);
    }

    /**
     * Get history array from flexible JSON path.
     */
    public function getJsonHistory(string $column, string $historyPath): array
    {
        $container = (array) ($this->getAttribute($column) ?? []);
        $history = Arr::get($container, $historyPath, []);
        return is_array($history) ? $history : [];
    }
}

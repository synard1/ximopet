<?php

namespace App\Services\History;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class JsonHistoryService
{
    /**
     * Append a history entry into an array container at a given nested path, capped by max entries.
     * If the target path doesn't exist, it will be created.
     *
     * Example path: 'data.history' or 'metadata.history' or 'cost_details.history'
     */
    public function appendEntry(array $container, string $path, array $entry, int $maxEntries = 5): array
    {
        try {
            $history = Arr::get($container, $path, []);
            if (!is_array($history)) {
                $history = [];
            }

            $entry = $this->normalizeEntry($entry);
            array_unshift($history, $entry);

            if ($maxEntries > 0 && count($history) > $maxEntries) {
                $history = array_slice($history, 0, $maxEntries);
            }

            Arr::set($container, $path, $history);
            return $container;
        } catch (\Throwable $e) {
            Log::error('JsonHistoryService appendEntry error', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
            return $container; // fail-safe: return original container
        }
    }

    /**
     * Ensure entry has id and timestamp.
     */
    private function normalizeEntry(array $entry): array
    {
        if (!isset($entry['id'])) {
            $entry['id'] = (string) Str::uuid();
        }
        if (!isset($entry['at'])) {
            $entry['at'] = now()->toISOString();
        }
        return $entry;
    }
}

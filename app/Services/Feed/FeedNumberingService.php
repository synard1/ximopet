<?php

namespace App\Services\Feed;

use App\Config\FeedNumberingConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FeedNumberingService
{
    /**
     * Generate feed number for a given table/model with flexible format and reset rules
     * Static method for easy integration (similar to Supply & Livestock)
     * 
     * @param string $table
     * @param string|null $date (Y-m-d H:i:s or Y-m-d)
     * @param array $context (optional, for custom placeholder)
     * @return array ['full_number' => ..., 'number' => ...]
     */
    public static function generateNumber(string $table, $date = null, array $context = []): array
    {
        $config = FeedNumberingConfig::getFor($table);
        if (empty($config) || empty($config['enabled'])) {
            return [
                'full_number' => null,
                'number' => null,
            ];
        }

        $dateObj = $date ? Carbon::parse($date) : Carbon::now();
        $resetRule = $config['reset_rule'] ?? 'yearly';
        $prefix = $config['prefix'] ?? '';
        $padding = $config['number_padding'] ?? 3;
        $format = $config['format'] ?? '{prefix}-{urut}/{TGL}/{BLN}/{THN}';

        // Get appropriate date field for the table
        $dateField = self::getDateFieldForTable($table);

        // Override date field if provided in context
        if (isset($context['date_field'])) {
            $dateField = $context['date_field'];
        }

        // Log for debugging
        Log::info("FeedNumberingService: Generating number for table {$table}", [
            'date_field' => $dateField,
            'reset_rule' => $resetRule,
            'target_date' => $dateObj->toDateString(),
            'context' => $context
        ]);

        // Get next number based on existing records
        $query = DB::table($table);

        if ($resetRule === 'daily') {
            $query->whereDate($dateField, $dateObj->toDateString());
        } elseif ($resetRule === 'monthly') {
            $query->whereMonth($dateField, $dateObj->month)->whereYear($dateField, $dateObj->year);
        } elseif ($resetRule === 'yearly') {
            $query->whereYear($dateField, $dateObj->year);
        }

        $lastNumber = $query->max('number') ?? 0;
        $number = $lastNumber + 1;
        $numberStr = str_pad($number, $padding, '0', STR_PAD_LEFT);

        // Build placeholder map
        $placeholders = [
            '{urut:' . $padding . '}' => $numberStr,
            '{urut}' => $number,
            '{TGL}' => $dateObj->format('d'),
            '{BLN}' => $dateObj->format('m'),
            '{THN}' => $dateObj->format('Y'),
        ];

        // Add prefix from config if available
        if (!empty($prefix)) {
            $placeholders['{prefix}'] = $prefix;
        }

        // Support custom context placeholder
        foreach ($context as $key => $val) {
            if (!in_array($key, ['date_field', 'override_date'])) { // Skip internal context keys
                $placeholders['{' . strtoupper($key) . '}'] = $val;
            }
        }

        // Replace all placeholders in format
        $fullNumber = $format;

        // Replace {urut:x} pattern with actual number
        $fullNumber = preg_replace_callback('/\{urut:(\d+)\}/', function ($m) use ($number) {
            return str_pad($number, (int)$m[1], '0', STR_PAD_LEFT);
        }, $fullNumber);

        // Replace static placeholders
        $fullNumber = str_replace(array_keys($placeholders), array_values($placeholders), $fullNumber);

        $result = [
            'full_number' => $fullNumber,
            'number' => $number,
        ];

        Log::info("FeedNumberingService: Generated number", [
            'table' => $table,
            'full_number' => $fullNumber,
            'number' => $number,
            'date_field_used' => $dateField
        ]);

        return $result;
    }

    /**
     * Get the appropriate date field for a given table
     * @param string $table
     * @return string
     */
    private static function getDateFieldForTable(string $table): string
    {
        $dateFieldMap = [
            'feed_purchases' => 'date',
            'feed_usages' => 'usage_date',
            'feed_mutations' => 'date',
            'feed_stocks' => 'date',
            'feed_purchase_batches' => 'date',
            'feed_usage_details' => 'usage_date',
            'feed_mutation_items' => 'date',
        ];

        return $dateFieldMap[$table] ?? 'created_at';
    }

    /**
     * Generate number with specific date field override
     * @param string $table
     * @param string|null $date
     * @param string $dateField
     * @param array $context
     * @return array
     */
    public static function generateNumberWithDateField(string $table, $date = null, string $dateField, array $context = []): array
    {
        $context['date_field'] = $dateField;
        return self::generateNumber($table, $date, $context);
    }

    /**
     * Validate if a table has the required date field for numbering
     * @param string $table
     * @return bool
     */
    public static function validateTableDateField(string $table): bool
    {
        $dateField = self::getDateFieldForTable($table);

        if ($dateField === 'created_at') {
            return true; // created_at always exists
        }

        // Check if the field exists in the table
        try {
            $hasField = DB::getSchemaBuilder()->hasColumn($table, $dateField);
            Log::info("FeedNumberingService: Validating date field", [
                'table' => $table,
                'date_field' => $dateField,
                'exists' => $hasField
            ]);
            return $hasField;
        } catch (\Exception $e) {
            Log::error("FeedNumberingService: Error validating date field", [
                'table' => $table,
                'date_field' => $dateField,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get numbering statistics for a table
     * @param string $table
     * @param string|null $date
     * @return array
     */
    public static function getNumberingStats(string $table, $date = null): array
    {
        $config = FeedNumberingConfig::getFor($table);
        if (empty($config) || empty($config['enabled'])) {
            return [
                'enabled' => false,
                'total_records' => 0,
                'last_number' => 0,
                'next_number' => 0,
            ];
        }

        $dateObj = $date ? Carbon::parse($date) : now();
        $resetRule = $config['reset_rule'] ?? 'yearly';
        $dateField = self::getDateFieldForTable($table);

        $query = DB::table($table);

        if ($resetRule === 'daily') {
            $query->whereDate($dateField, $dateObj->toDateString());
        } elseif ($resetRule === 'monthly') {
            $query->whereMonth($dateField, $dateObj->month)->whereYear($dateField, $dateObj->year);
        } elseif ($resetRule === 'yearly') {
            $query->whereYear($dateField, $dateObj->year);
        }

        $totalRecords = $query->count();
        $lastNumber = $query->max('number') ?? 0;
        $nextNumber = $lastNumber + 1;

        return [
            'enabled' => true,
            'reset_rule' => $resetRule,
            'date_field' => $dateField,
            'target_date' => $dateObj->toDateString(),
            'total_records' => $totalRecords,
            'last_number' => $lastNumber,
            'next_number' => $nextNumber,
            'config' => $config,
        ];
    }

    /**
     * Generate the next number for a given feed-related table/model
     *
     * @param string $table
     * @param string|null $date (Y-m-d) for reset rule
     * @return string
     */
    public function generateNextNumber(string $table, ?string $date = null): string
    {
        $config = FeedNumberingConfig::getFor($table);
        if (empty($config) || empty($config['enabled'])) {
            throw new \Exception("Numbering not enabled for table: $table");
        }

        $date = $date ?: now()->toDateString();
        $resetRule = $config['reset_rule'] ?? 'yearly';
        $prefix = $config['prefix'] ?? '';
        $padding = $config['number_padding'] ?? 3;
        $separator = $config['separator'] ?? '/';
        $format = $config['format'] ?? '{prefix}-{urut}/{TGL}/{BLN}/{THN}';

        // Determine period for reset
        $period = $this->getPeriod($resetRule, $date);
        $cacheKey = "feed_numbering:{$table}:{$period}";

        // Use cache to avoid race conditions (future: use DB for concurrency)
        $lastNumber = Cache::lock($cacheKey . ':lock', 5)->block(3, function () use ($cacheKey) {
            $current = Cache::get($cacheKey, 0);
            $next = $current + 1;
            Cache::put($cacheKey, $next, now()->addDay());
            return $next;
        });

        // Build number string
        $urut = str_pad($lastNumber, $padding, '0', STR_PAD_LEFT);
        $carbon = Carbon::parse($date);
        $number = strtr($format, [
            '{prefix}' => $prefix,
            '{urut}' => $urut,
            '{TGL}' => $carbon->format('d'),
            '{BLN}' => $carbon->format('m'),
            '{THN}' => $carbon->format('Y'),
        ]);
        return $number;
    }

    /**
     * Generate both number and number_full for a given table
     * This method validates against existing records in database
     *
     * @param string $table
     * @param string|null $date (Y-m-d) for reset rule
     * @return array
     */
    public function generateNumberAndFull(string $table, ?string $date = null): array
    {
        $config = FeedNumberingConfig::getFor($table);
        if (empty($config) || empty($config['enabled'])) {
            throw new \Exception("Numbering not enabled for table: $table");
        }

        $date = $date ?: now()->toDateString();
        $resetRule = $config['reset_rule'] ?? 'yearly';
        $prefix = $config['prefix'] ?? '';
        $padding = $config['number_padding'] ?? 3;
        $format = $config['format'] ?? '{prefix}-{urut}/{TGL}/{BLN}/{THN}';

        // Determine period for reset
        $period = $this->getPeriod($resetRule, $date);
        $cacheKey = "feed_numbering:{$table}:{$period}";

        // Get the next number based on existing records in database
        $lastNumber = $this->getNextNumberFromDatabase($table, $date, $resetRule, $cacheKey);

        // Build number string
        $urut = str_pad($lastNumber, $padding, '0', STR_PAD_LEFT);
        $carbon = Carbon::parse($date);

        // Generate full number - handle {urut:3} format properly
        $numberFull = $format;

        // Replace {urut:3} pattern with actual number
        $numberFull = preg_replace('/\{urut:(\d+)\}/', $urut, $numberFull);

        // Replace other placeholders
        $numberFull = strtr($numberFull, [
            '{prefix}' => $prefix,
            '{urut}' => $urut,
            '{TGL}' => $carbon->format('d'),
            '{BLN}' => $carbon->format('m'),
            '{THN}' => $carbon->format('Y'),
        ]);

        // For 'number' field, use the numeric sequence (integer)
        // For 'number_full' field, use the full formatted string
        $number = $lastNumber; // Integer value for database

        return [
            'number' => $number,
            'number_full' => $numberFull,
            'sequence' => $lastNumber,
            'date' => $date,
            'period' => $period
        ];
    }

    /**
     * Get next number based on existing records in database
     * This ensures numbering is consistent with actual data
     */
    private function getNextNumberFromDatabase(string $table, string $date, string $resetRule, string $cacheKey): int
    {
        $carbon = Carbon::parse($date);

        // Build date range based on reset rule
        $dateRange = $this->getDateRangeForResetRule($carbon, $resetRule);

        // Check if we should ignore existing numbers (for fix commands)
        $ignoreExisting = Cache::get("feed_numbering:{$table}:ignore_existing", false);

        if ($ignoreExisting) {
            // Start from 1 for this period
            $nextNumber = 1;
            Cache::forget("feed_numbering:{$table}:ignore_existing");
        } else {
            // Query existing records in the same period
            $existingMaxNumber = DB::table($table)
                ->whereNotNull('number')
                ->where('number', '>', 0)
                ->whereBetween('date', [$dateRange['start'], $dateRange['end']])
                ->max('number');

            $nextNumber = ($existingMaxNumber ?? 0) + 1;
        }

        // Update cache to reflect the actual next number
        Cache::put($cacheKey, $nextNumber, now()->addDay());

        return $nextNumber;
    }

    /**
     * Get date range for reset rule
     */
    private function getDateRangeForResetRule(Carbon $carbon, string $resetRule): array
    {
        return match ($resetRule) {
            'daily' => [
                'start' => $carbon->copy()->startOfDay(),
                'end' => $carbon->copy()->endOfDay()
            ],
            'monthly' => [
                'start' => $carbon->copy()->startOfMonth(),
                'end' => $carbon->copy()->endOfMonth()
            ],
            'yearly' => [
                'start' => $carbon->copy()->startOfYear(),
                'end' => $carbon->copy()->endOfYear()
            ],
            default => [
                'start' => $carbon->copy()->startOfYear(),
                'end' => $carbon->copy()->endOfYear()
            ],
        };
    }

    /**
     * Reset cache for a specific table and period
     * Useful for testing or manual cache clearing
     */
    public function resetCache(string $table, ?string $date = null): bool
    {
        $config = FeedNumberingConfig::getFor($table);
        if (empty($config)) {
            return false;
        }

        $date = $date ?: now()->toDateString();
        $resetRule = $config['reset_rule'] ?? 'yearly';
        $period = $this->getPeriod($resetRule, $date);
        $cacheKey = "feed_numbering:{$table}:{$period}";

        return Cache::forget($cacheKey);
    }

    /**
     * Set flag to ignore existing numbers and start from 1
     * Useful for fix commands that want to renumber from scratch
     */
    public function setIgnoreExisting(string $table, bool $ignore = true): bool
    {
        $cacheKey = "feed_numbering:{$table}:ignore_existing";
        if ($ignore) {
            Cache::put($cacheKey, true, now()->addMinutes(5));
        } else {
            Cache::forget($cacheKey);
        }
        return true;
    }

    /**
     * Reset all cache for feed numbering
     */
    public function resetAllCache(): bool
    {
        $config = FeedNumberingConfig::getConfig();
        $cleared = 0;

        foreach ($config as $table => $tableConfig) {
            if (!empty($tableConfig['enabled'])) {
                $cacheKey = "feed_numbering:{$table}:*";
                // Note: This is a simplified approach. In production, you might want to use Redis SCAN
                Cache::forget($cacheKey);
                $cleared++;
            }
        }

        return $cleared > 0;
    }

    /**
     * Get period string for reset rule
     */
    private function getPeriod(string $resetRule, string $date): string
    {
        $carbon = Carbon::parse($date);
        return match ($resetRule) {
            'daily' => $carbon->format('Ymd'),
            'monthly' => $carbon->format('Ym'),
            'yearly' => $carbon->format('Y'),
            default => $carbon->format('Y'),
        };
    }
}

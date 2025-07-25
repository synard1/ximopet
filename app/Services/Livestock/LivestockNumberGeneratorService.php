<?php

namespace App\Services\Livestock;

use App\Config\LivestockNumberingConfig;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LivestockNumberGeneratorService
{
    /**
     * Generate livestock number for a given table/model with flexible format and daily reset
     * @param string $table
     * @param string|null $date (Y-m-d H:i:s or Y-m-d)
     * @param array $context (optional, for custom placeholder and override behavior)
     * @return array ['full_number' => ..., 'number' => ...]
     */
    public static function generateNumber(string $table, $date = null, array $context = []): array
    {
        $config = LivestockNumberingConfig::getConfig()[$table] ?? null;
        if (!$config || empty($config['enabled'])) {
            return [
                'full_number' => null,
                'number' => null,
            ];
        }

        $dateObj = $date ? Carbon::parse($date) : now();
        $resetRule = $config['reset_rule'] ?? 'daily';
        $numberPadding = $config['number_padding'] ?? 5;
        $format = $config['format'] ?? '{urut:5}/{TGL}/{BLN}/{THN}';

        // 1. Tentukan nomor urut terakhir sesuai reset_rule
        $query = DB::table($table);

        // Gunakan field tanggal yang sesuai dengan tabel, bukan created_at
        $dateField = self::getDateFieldForTable($table);

        // Override date field jika ada di context
        if (isset($context['date_field'])) {
            $dateField = $context['date_field'];
        }

        // Log untuk debugging
        Log::info("LivestockNumberGenerator: Generating number for table {$table}", [
            'date_field' => $dateField,
            'reset_rule' => $resetRule,
            'target_date' => $dateObj->toDateString(),
            'context' => $context
        ]);

        if ($resetRule === 'daily') {
            $query->whereDate($dateField, $dateObj->toDateString());
        } elseif ($resetRule === 'monthly') {
            $query->whereMonth($dateField, $dateObj->month)->whereYear($dateField, $dateObj->year);
        } elseif ($resetRule === 'yearly') {
            $query->whereYear($dateField, $dateObj->year);
        }

        $lastNumber = $query->max('number') ?? 0;
        $number = $lastNumber + 1;
        $numberStr = str_pad($number, $numberPadding, '0', STR_PAD_LEFT);

        // 2. Build placeholder map
        $placeholders = [
            '{urut:' . $numberPadding . '}' => $numberStr,
            '{urut}' => $number,
            '{TGL}' => $dateObj->format('d'),
            '{BLN}' => $dateObj->format('m'),
            '{THN}' => $dateObj->format('Y'),
        ];
        // Tambahkan {prefix} dari config jika ada
        if (!empty($config['prefix'])) {
            $placeholders['{prefix}'] = $config['prefix'];
        }
        // Support custom context placeholder
        foreach ($context as $key => $val) {
            if (!in_array($key, ['date_field', 'override_date'])) { // Skip internal context keys
                $placeholders['{' . strtoupper($key) . '}'] = $val;
            }
        }

        // 3. Replace all placeholders in format
        $fullNumber = $format;
        foreach ($placeholders as $ph => $val) {
            $fullNumber = str_replace($ph, $val, $fullNumber);
        }

        $result = [
            'full_number' => $fullNumber,
            'number' => $number,
        ];

        Log::info("LivestockNumberGenerator: Generated number", [
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
            'livestock_purchases' => 'tanggal',
            'livestock_mutations' => 'tanggal',
            'livestock_sales' => 'tanggal',
            'livestock_depletions' => 'tanggal',
            'livestocks' => 'start_date', // Assuming this field exists
            'livestock_batches' => 'start_date', // Assuming this field exists
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
            Log::info("LivestockNumberGenerator: Validating date field", [
                'table' => $table,
                'date_field' => $dateField,
                'exists' => $hasField
            ]);
            return $hasField;
        } catch (\Exception $e) {
            Log::error("LivestockNumberGenerator: Error validating date field", [
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
        $config = LivestockNumberingConfig::getConfig()[$table] ?? null;
        if (!$config || empty($config['enabled'])) {
            return [
                'enabled' => false,
                'total_records' => 0,
                'last_number' => 0,
                'next_number' => 0,
            ];
        }

        $dateObj = $date ? Carbon::parse($date) : now();
        $resetRule = $config['reset_rule'] ?? 'daily';
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
}

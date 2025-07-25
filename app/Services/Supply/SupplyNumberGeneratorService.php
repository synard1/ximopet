<?php

namespace App\Services\Supply;

use App\Config\SupplyNumberingConfig;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SupplyNumberGeneratorService
{
    /**
     * Generate supply number for a given table/model with flexible format and daily reset
     * @param string $table
     * @param string|null $date (Y-m-d H:i:s or Y-m-d)
     * @param array $context (optional, for custom placeholder)
     * @return array ['full_number' => ..., 'number' => ...]
     */
    public static function generateNumber(string $table, $date = null, array $context = []): array
    {
        $config = SupplyNumberingConfig::getFor($table);
        if (empty($config) || empty($config['enabled'])) {
            throw new \Exception("Numbering not enabled for table: $table");
        }
        $format = $config['format'] ?? '{urut:5}/{TGL}/{BLN}/{THN}';
        $resetRule = $config['reset_rule'] ?? 'daily';
        $numberPadding = $config['number_padding'] ?? 5;
        $separator = $config['separator'] ?? '/';
        $dateObj = $date ? Carbon::parse($date) : Carbon::now();

        // 1. Hitung nomor urut terakhir sesuai reset_rule (default: per hari)
        $number = self::getNextNumber($table, $dateObj, $resetRule);
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
            $placeholders['{' . strtoupper($key) . '}'] = $val;
        }
        // 3. Replace all placeholders
        $fullNumber = $format;
        // Replace {urut:x} (dynamic padding)
        $fullNumber = preg_replace_callback('/\{urut:(\d+)\}/', function ($m) use ($number) {
            return str_pad($number, (int)$m[1], '0', STR_PAD_LEFT);
        }, $fullNumber);
        // Replace static placeholders
        $fullNumber = str_replace(array_keys($placeholders), array_values($placeholders), $fullNumber);

        return [
            'full_number' => $fullNumber,
            'number' => $number,
        ];
    }

    /**
     * Get next number for table, with reset per day/month/year
     * @param string $table
     * @param Carbon $dateObj
     * @param string $resetRule
     * @return int
     */
    protected static function getNextNumber(string $table, Carbon $dateObj, string $resetRule = 'daily'): int
    {
        // Table mapping: plural to actual table name
        $tableMap = [
            'supplies' => 'supplies',
            'supply_purchase_batches' => 'supply_purchase_batches',
            'supply_purchases' => 'supply_purchases',
            'supply_stocks' => 'supply_stocks',
            'supply_usages' => 'supply_usages',
            'supply_mutations' => 'supply_mutations',
        ];
        $tableName = $tableMap[$table] ?? $table;
        $query = DB::table($tableName)->whereNotNull('number');
        if ($resetRule === 'daily') {
            $query->whereDate('created_at', $dateObj->toDateString());
        } elseif ($resetRule === 'monthly') {
            $query->whereYear('created_at', $dateObj->year)->whereMonth('created_at', $dateObj->month);
        } elseif ($resetRule === 'yearly') {
            $query->whereYear('created_at', $dateObj->year);
        }
        $lastNumber = $query->max('number');
        return $lastNumber ? ($lastNumber + 1) : 1;
    }
}

<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class DataChangeDetector
{
    /**
     * Check if data has changed from original
     */
    public static function hasChanges(array $currentData, array $originalData, array $excludedFields = []): bool
    {
        // Remove excluded fields from comparison
        $filteredCurrent = Arr::except($currentData, $excludedFields);
        $filteredOriginal = Arr::except($originalData, $excludedFields);

        // Remove null values and empty strings for comparison
        $filteredCurrent = self::cleanDataForComparison($filteredCurrent);
        $filteredOriginal = self::cleanDataForComparison($filteredOriginal);

        // Debug logging
        \Illuminate\Support\Facades\Log::info('DataChangeDetector: Comparing data', [
            'excluded_fields' => $excludedFields,
            'filtered_current' => $filteredCurrent,
            'filtered_original' => $filteredOriginal,
            'current_keys' => array_keys($filteredCurrent),
            'original_keys' => array_keys($filteredOriginal)
        ]);

        // Align hasChanges with per-field evaluation to avoid contradictions
        $allKeys = array_unique(array_merge(array_keys($filteredCurrent), array_keys($filteredOriginal)));
        foreach ($allKeys as $key) {
            $v1 = $filteredCurrent[$key] ?? null;
            $v2 = $filteredOriginal[$key] ?? null;
            if (self::valuesAreDifferent($v1, $v2)) {
                \Illuminate\Support\Facades\Log::info('DataChangeDetector: Comparison result', [
                    'has_changes' => true,
                    'first_diff_key' => $key
                ]);
                return true;
            }
        }

        \Illuminate\Support\Facades\Log::info('DataChangeDetector: Comparison result', [
            'has_changes' => false
        ]);
        return false;
    }

    /**
     * Check if model has changes
     */
    public static function modelHasChanges(Model $model, array $excludedFields = []): bool
    {
        if (!$model->exists) {
            return true; // New model always has changes
        }

        $changes = $model->getChanges();

        // Remove excluded fields from changes
        $relevantChanges = Arr::except($changes, $excludedFields);

        return !empty($relevantChanges);
    }

    /**
     * Get changed fields
     */
    public static function getChangedFields(array $currentData, array $originalData, array $excludedFields = []): array
    {
        $changes = [];

        foreach ($currentData as $key => $value) {
            if (in_array($key, $excludedFields)) {
                continue;
            }

            $originalValue = $originalData[$key] ?? null;

            if (self::valuesAreDifferent($value, $originalValue)) {
                $changes[$key] = [
                    'from' => $originalValue,
                    'to' => $value
                ];
            }
        }

        return $changes;
    }

    /**
     * Check if specific field has changed
     */
    public static function fieldHasChanged(string $field, $currentValue, $originalValue): bool
    {
        return self::valuesAreDifferent($currentValue, $originalValue);
    }

    /**
     * Clean data for comparison (remove nulls, empty strings, etc.)
     */
    private static function cleanDataForComparison(array $data): array
    {
        $cleaned = [];

        foreach ($data as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if (is_array($value)) {
                $cleanedValue = self::cleanDataForComparison($value);
                if (!empty($cleanedValue)) {
                    $cleaned[$key] = $cleanedValue;
                }
            } else {
                $cleaned[$key] = $value;
            }
        }

        return $cleaned;
    }

    /**
     * Check if values are meaningfully different
     */
    private static function valuesAreMeaningfullyDifferent($value1, $value2): bool
    {
        // Handle null vs empty string - treat as same
        if (($value1 === null && $value2 === '') || ($value1 === '' && $value2 === null)) {
            \Illuminate\Support\Facades\Log::info('DataChangeDetector: Null vs empty string - treating as same', [
                'value1' => $value1,
                'value2' => $value2
            ]);
            return false;
        }

        // Handle null vs null - same
        if ($value1 === null && $value2 === null) {
            return false;
        }

        // Handle empty string vs empty string - same
        if ($value1 === '' && $value2 === '') {
            return false;
        }

        // Handle empty string vs non-empty string - treat as same for batch_name and similar fields
        if (($value1 === '' && !empty($value2)) || ($value2 === '' && !empty($value1))) {
            \Illuminate\Support\Facades\Log::info('DataChangeDetector: Empty vs non-empty string - treating as same for batch_name', [
                'value1' => $value1,
                'value2' => $value2
            ]);
            return false;
        }

        // Handle different types but same meaning
        if (is_numeric($value1) && is_numeric($value2)) {
            $result = (float)$value1 !== (float)$value2;
            \Illuminate\Support\Facades\Log::info('DataChangeDetector: Numeric comparison', [
                'value1' => $value1,
                'value2' => $value2,
                'different' => $result
            ]);
            return $result;
        }

        // Handle date strings - normalize and compare
        if (is_string($value1) && is_string($value2)) {
            // Try to parse as dates
            $date1 = self::parseDateString($value1);
            $date2 = self::parseDateString($value2);

            if ($date1 && $date2) {
                // Compare only date part (ignore time) - bypass jam menit detik
                $dateOnly1 = $date1->format('Y-m-d');
                $dateOnly2 = $date2->format('Y-m-d');

                $result = $dateOnly1 !== $dateOnly2;
                \Illuminate\Support\Facades\Log::info('DataChangeDetector: Date comparison (date only)', [
                    'value1' => $value1,
                    'value2' => $value2,
                    'date1' => $date1->toISOString(),
                    'date2' => $date2->toISOString(),
                    'date_only1' => $dateOnly1,
                    'date_only2' => $dateOnly2,
                    'different' => $result
                ]);
                return $result;
            }
        }

        // Handle arrays with different structure but same content
        if (is_array($value1) && is_array($value2)) {
            return self::arraysAreMeaningfullyDifferent($value1, $value2);
        }

        // Default comparison with string trimming and loose numeric
        if (is_string($value1)) {
            $value1 = trim($value1);
        }
        if (is_string($value2)) {
            $value2 = trim($value2);
        }
        if (is_numeric($value1) && is_numeric($value2)) {
            $result = (float)$value1 !== (float)$value2;
        } else {
            $result = $value1 !== $value2;
        }
        \Illuminate\Support\Facades\Log::info('DataChangeDetector: Default comparison', [
            'value1' => $value1,
            'value2' => $value2,
            'different' => $result
        ]);
        return $result;
    }

    /**
     * Check if arrays are meaningfully different
     */
    private static function arraysAreMeaningfullyDifferent(array $array1, array $array2): bool
    {
        // Normalize array keys and values
        $normalized1 = self::normalizeArrayForComparison($array1);
        $normalized2 = self::normalizeArrayForComparison($array2);

        // Compare normalized arrays
        // Sort associative arrays by key for stable comparison
        ksort($normalized1);
        ksort($normalized2);

        foreach ($normalized1 as $key => $value1) {
            if (!array_key_exists($key, $normalized2)) {
                // Key doesn't exist in second array
                if (!self::isEmptyValue($value1)) {
                    return true; // Meaningful difference
                }
                continue;
            }

            $value2 = $normalized2[$key];

            if (self::valuesAreMeaningfullyDifferent($value1, $value2)) {
                return true; // Meaningful difference found
            }
        }

        // Check for keys in second array that don't exist in first
        foreach ($normalized2 as $key => $value2) {
            if (!array_key_exists($key, $normalized1)) {
                if (!self::isEmptyValue($value2)) {
                    return true; // Meaningful difference
                }
            }
        }

        return false; // No meaningful differences
    }

    /**
     * Check if value is considered empty for comparison
     */
    private static function isEmptyValue($value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (is_array($value) && empty($value)) {
            return true;
        }

        return false;
    }

    /**
     * Normalize array for comparison
     */
    private static function normalizeArrayForComparison(array $array): array
    {
        $normalized = [];

        foreach ($array as $key => $value) {
            // Skip null and empty values
            if ($value === null || $value === '') {
                continue;
            }

            // Skip ignored fields
            if (self::shouldIgnoreField($key)) {
                continue;
            }

            // Normalize key names - handle common key differences
            $normalizedKey = self::normalizeKeyName($key);

            if (is_array($value)) {
                $normalizedValue = self::normalizeArrayForComparison($value);
                if (!empty($normalizedValue)) {
                    $normalized[$normalizedKey] = $normalizedValue;
                }
            } else {
                $normalized[$normalizedKey] = $value;
            }
        }

        return $normalized;
    }

    /**
     * Normalize key names for comparison
     */
    private static function normalizeKeyName(string $key): string
    {
        // Handle common key name variations
        $keyMappings = [
            'livestock_strain_id' => 'strain_id',
            'price_value' => 'price',
            'strain_id' => 'strain_id',
            'price' => 'price',
            'weight_value' => 'weight_value',
            'weight_type' => 'weight_type',
            'quantity' => 'quantity',
            'farm_id' => 'farm_id',
            'coop_id' => 'coop_id',
            'start_date' => 'start_date',
            'livestock_id' => 'livestock_id',
            'livestock_strain_standard_id' => 'strain_standard_id'
        ];

        $normalizedKey = $keyMappings[$key] ?? $key;

        // Only log for debugging when needed
        if (in_array($key, ['livestock_strain_id', 'price_value', 'strain_id', 'price'])) {
            \Illuminate\Support\Facades\Log::info('DataChangeDetector: Key normalization', [
                'original_key' => $key,
                'normalized_key' => $normalizedKey
            ]);
        }

        return $normalizedKey;
    }

    /**
     * Check if field should be ignored for comparison
     */
    private static function shouldIgnoreField(string $key): bool
    {
        // Fields that should be ignored in comparison
        $ignoredFields = [
            'sub_total',
            'total_quantity',
            'total_weight',
            'start_date',
            'livestock_id',
            'livestock_strain_standard_id',
            'farm_id',
            'coop_id',
            'price_type',
            'created_at',
            'updated_at'
        ];

        return in_array($key, $ignoredFields);
    }

    /**
     * Compare two values for equality
     */
    private static function valuesAreDifferent($value1, $value2): bool
    {
        // Handle different types
        if (gettype($value1) !== gettype($value2)) {
            // Check if they're meaningfully different despite type difference
            return self::valuesAreMeaningfullyDifferent($value1, $value2);
        }

        // Handle arrays
        if (is_array($value1) && is_array($value2)) {
            return self::arraysAreMeaningfullyDifferent($value1, $value2);
        }

        // Handle dates
        if ($value1 instanceof \Carbon\Carbon && $value2 instanceof \Carbon\Carbon) {
            return !$value1->equalTo($value2);
        }

        // Handle other types
        return self::valuesAreMeaningfullyDifferent($value1, $value2);
    }

    /**
     * Check if form data has meaningful changes
     */
    public static function formHasChanges(array $formData, array $originalData, array $excludedFields = []): bool
    {
        // Default excluded fields for forms
        $defaultExcluded = [
            'updated_at',
            'created_at',
            'id',
            '_token',
            '_method',
            'submit',
            'save',
            'cancel'
        ];

        $excludedFields = array_merge($defaultExcluded, $excludedFields);

        return self::hasChanges($formData, $originalData, $excludedFields);
    }

    /**
     * Log data changes for debugging
     */
    public static function logChanges(array $changes, string $context = ''): void
    {
        if (empty($changes)) {
            \Illuminate\Support\Facades\Log::info("No changes detected {$context}");
            return;
        }

        \Illuminate\Support\Facades\Log::info("Data changes detected {$context}", [
            'changed_fields' => array_keys($changes),
            'changes' => $changes
        ]);
    }

    /**
     * Check if changes are significant enough to save
     */
    public static function hasSignificantChanges(array $changes, array $insignificantFields = []): bool
    {
        $significantChanges = Arr::except($changes, $insignificantFields);
        return !empty($significantChanges);
    }

    /**
     * Parse date string to Carbon instance
     */
    private static function parseDateString($value): ?\Carbon\Carbon
    {
        if (!is_string($value)) {
            return null;
        }

        // Common date formats
        $formats = [
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'Y-m-d',
            'd/m/Y H:i:s',
            'd/m/Y H:i',
            'd/m/Y',
            'm/d/Y H:i:s',
            'm/d/Y H:i',
            'm/d/Y'
        ];

        foreach ($formats as $format) {
            try {
                $date = \Carbon\Carbon::createFromFormat($format, trim($value));
                if ($date && $date->year > 1900) { // Valid date check
                    return $date;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // Fallback: try Carbon::parse for more flexible parsing
        try {
            $parsed = \Carbon\Carbon::parse(trim($value));
            if ($parsed && $parsed->year > 1900) {
                return $parsed;
            }
        } catch (\Exception $e) {
            // ignore
        }

        return null;
    }
}
 
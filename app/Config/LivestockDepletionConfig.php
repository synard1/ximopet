<?php

namespace App\Config;

/**
 * Centralized configuration for Livestock Depletion Types
 * 
 * This class manages the mapping between different depletion type representations
 * to ensure consistency across the application and backward compatibility.
 * 
 * @version 1.0
 * @since 2025-01-23
 */
class LivestockDepletionConfig
{
    /**
     * Standard depletion types (English - Primary)
     */
    const TYPE_MORTALITY = 'mortality';
    const TYPE_CULLING = 'culling';
    const TYPE_SALES = 'sales';
    const TYPE_MUTATION = 'mutation';
    const TYPE_TRANSFER = 'transfer';
    const TYPE_OTHER = 'other';

    /**
     * Legacy depletion types (Indonesian - Backward Compatibility)
     */
    const LEGACY_TYPE_MATI = 'Mati';
    const LEGACY_TYPE_AFKIR = 'Afkir';
    const LEGACY_TYPE_JUAL = 'Jual';
    const LEGACY_TYPE_MUTASI = 'Mutasi';
    const LEGACY_TYPE_TRANSFER = 'Transfer';
    const LEGACY_TYPE_LAINNYA = 'Lainnya';

    /**
     * Mapping from legacy (Indonesian) to standard (English)
     * 
     * @var array
     */
    private static $legacyToStandardMapping = [
        self::LEGACY_TYPE_MATI => self::TYPE_MORTALITY,
        self::LEGACY_TYPE_AFKIR => self::TYPE_CULLING,
        self::LEGACY_TYPE_JUAL => self::TYPE_SALES,
        self::LEGACY_TYPE_MUTASI => self::TYPE_MUTATION,
        self::LEGACY_TYPE_TRANSFER => self::TYPE_TRANSFER,
        self::LEGACY_TYPE_LAINNYA => self::TYPE_OTHER,
    ];

    /**
     * Mapping from standard (English) to legacy (Indonesian)
     * 
     * @var array
     */
    private static $standardToLegacyMapping = [
        self::TYPE_MORTALITY => self::LEGACY_TYPE_MATI,
        self::TYPE_CULLING => self::LEGACY_TYPE_AFKIR,
        self::TYPE_SALES => self::LEGACY_TYPE_JUAL,
        self::TYPE_MUTATION => self::LEGACY_TYPE_MUTASI,
        self::TYPE_TRANSFER => self::LEGACY_TYPE_TRANSFER,
        self::TYPE_OTHER => self::LEGACY_TYPE_LAINNYA,
    ];

    /**
     * Display names for each depletion type (Indonesian for UI)
     * 
     * @var array
     */
    private static $displayNames = [
        self::TYPE_MORTALITY => 'Kematian',
        self::TYPE_CULLING => 'Afkir',
        self::TYPE_SALES => 'Penjualan',
        self::TYPE_MUTATION => 'Mutasi',
        self::TYPE_TRANSFER => 'Transfer',
        self::TYPE_OTHER => 'Lainnya',
    ];

    /**
     * Short display names for reports and compact views
     * 
     * @var array
     */
    private static $shortDisplayNames = [
        self::TYPE_MORTALITY => 'Mati',
        self::TYPE_CULLING => 'Afkir',
        self::TYPE_SALES => 'Jual',
        self::TYPE_MUTATION => 'Mutasi',
        self::TYPE_TRANSFER => 'Transfer',
        self::TYPE_OTHER => 'Lainnya',
    ];

    /**
     * Validation rules for each depletion type
     * 
     * @var array
     */
    private static $validationRules = [
        self::TYPE_MORTALITY => [
            'requires_reason' => false,
            'requires_weight' => false,
            'requires_price' => false,
            'affects_stock' => true,
            'category' => 'loss'
        ],
        self::TYPE_CULLING => [
            'requires_reason' => true,
            'requires_weight' => false,
            'requires_price' => false,
            'affects_stock' => true,
            'category' => 'loss'
        ],
        self::TYPE_SALES => [
            'requires_reason' => false,
            'requires_weight' => true,
            'requires_price' => true,
            'affects_stock' => true,
            'category' => 'revenue'
        ],
        self::TYPE_MUTATION => [
            'requires_reason' => true,
            'requires_weight' => false,
            'requires_price' => false,
            'affects_stock' => true,
            'category' => 'transfer'
        ],
        self::TYPE_TRANSFER => [
            'requires_reason' => true,
            'requires_weight' => false,
            'requires_price' => false,
            'affects_stock' => true,
            'category' => 'transfer'
        ],
        self::TYPE_OTHER => [
            'requires_reason' => true,
            'requires_weight' => false,
            'requires_price' => false,
            'affects_stock' => true,
            'category' => 'other'
        ],
    ];

    /**
     * Convert legacy type to standard type
     * 
     * @param string $legacyType
     * @return string
     */
    public static function toStandard(string $legacyType): string
    {
        return self::$legacyToStandardMapping[$legacyType] ?? $legacyType;
    }

    /**
     * Convert standard type to legacy type
     * 
     * @param string $standardType
     * @return string
     */
    public static function toLegacy(string $standardType): string
    {
        return self::$standardToLegacyMapping[$standardType] ?? $standardType;
    }

    /**
     * Get display name for a depletion type
     * 
     * @param string $type
     * @param bool $short
     * @return string
     */
    public static function getDisplayName(string $type, bool $short = false): string
    {
        $standardType = self::toStandard($type);

        if ($short) {
            return self::$shortDisplayNames[$standardType] ?? $type;
        }

        return self::$displayNames[$standardType] ?? $type;
    }

    /**
     * Get all standard depletion types
     * 
     * @return array
     */
    public static function getStandardTypes(): array
    {
        return [
            self::TYPE_MORTALITY,
            self::TYPE_CULLING,
            self::TYPE_SALES,
            self::TYPE_MUTATION,
            self::TYPE_TRANSFER,
            self::TYPE_OTHER,
        ];
    }

    /**
     * Get all legacy depletion types
     * 
     * @return array
     */
    public static function getLegacyTypes(): array
    {
        return [
            self::LEGACY_TYPE_MATI,
            self::LEGACY_TYPE_AFKIR,
            self::LEGACY_TYPE_JUAL,
            self::LEGACY_TYPE_MUTASI,
            self::LEGACY_TYPE_TRANSFER,
            self::LEGACY_TYPE_LAINNYA,
        ];
    }

    /**
     * Get validation rules for a depletion type
     * 
     * @param string $type
     * @return array
     */
    public static function getValidationRules(string $type): array
    {
        $standardType = self::toStandard($type);
        return self::$validationRules[$standardType] ?? [];
    }

    /**
     * Check if a depletion type is valid
     * 
     * @param string $type
     * @return bool
     */
    public static function isValidType(string $type): bool
    {
        return in_array($type, self::getStandardTypes()) ||
            in_array($type, self::getLegacyTypes());
    }

    /**
     * Get all types with their display names for form options
     * 
     * @param bool $includeLegacy
     * @param bool $short
     * @return array
     */
    public static function getTypesForForm(bool $includeLegacy = false, bool $short = false): array
    {
        $types = [];

        foreach (self::getStandardTypes() as $type) {
            $types[$type] = self::getDisplayName($type, $short);
        }

        if ($includeLegacy) {
            foreach (self::getLegacyTypes() as $legacyType) {
                if (!isset($types[self::toStandard($legacyType)])) {
                    $types[$legacyType] = $legacyType;
                }
            }
        }

        return $types;
    }

    /**
     * Normalize depletion type (always return standard format)
     * 
     * @param string $type
     * @return string
     */
    public static function normalize(string $type): string
    {
        return self::toStandard($type);
    }

    /**
     * Get type category (loss, revenue, transfer, other)
     * 
     * @param string $type
     * @return string
     */
    public static function getCategory(string $type): string
    {
        $rules = self::getValidationRules($type);
        return $rules['category'] ?? 'other';
    }

    /**
     * Check if type requires specific fields
     * 
     * @param string $type
     * @param string $field (reason, weight, price)
     * @return bool
     */
    public static function requiresField(string $type, string $field): bool
    {
        $rules = self::getValidationRules($type);
        return $rules["requires_{$field}"] ?? false;
    }

    /**
     * Get mapping for FIFO service compatibility
     * 
     * @return array
     */
    public static function getFifoMapping(): array
    {
        return [
            self::LEGACY_TYPE_MATI => 'mortality',
            self::LEGACY_TYPE_AFKIR => 'culling',
            self::LEGACY_TYPE_JUAL => 'sales',
            self::LEGACY_TYPE_MUTASI => 'mutation',
            self::TYPE_MORTALITY => 'mortality',
            self::TYPE_CULLING => 'culling',
            self::TYPE_SALES => 'sales',
            self::TYPE_MUTATION => 'mutation',
            self::TYPE_TRANSFER => 'mutation',
            self::TYPE_OTHER => 'other'
        ];
    }

    /**
     * Convert depletion data with backward compatibility
     * 
     * @param array $data
     * @return array
     */
    public static function convertDepletionData(array $data): array
    {
        $convertedData = $data;

        // Convert jenis field if present
        if (isset($data['jenis'])) {
            $convertedData['type'] = self::toStandard($data['jenis']);
            $convertedData['legacy_type'] = $data['jenis'];
        }

        // Convert type field to standard
        if (isset($data['type'])) {
            $convertedData['type'] = self::toStandard($data['type']);
        }

        // Add metadata
        $convertedData['conversion_metadata'] = [
            'original_type' => $data['jenis'] ?? $data['type'] ?? null,
            'standard_type' => $convertedData['type'] ?? null,
            'converted_at' => now()->toIso8601String(),
            'config_version' => '1.0'
        ];

        return $convertedData;
    }

    /**
     * Get configuration summary for debugging
     * 
     * @return array
     */
    public static function getConfigSummary(): array
    {
        return [
            'version' => '1.0',
            'standard_types_count' => count(self::getStandardTypes()),
            'legacy_types_count' => count(self::getLegacyTypes()),
            'mapping_pairs' => count(self::$legacyToStandardMapping),
            'validation_rules_count' => count(self::$validationRules),
            'display_names_count' => count(self::$displayNames),
            'creation_date' => '2025-01-23',
            'purpose' => 'Centralized depletion type management with backward compatibility'
        ];
    }
}

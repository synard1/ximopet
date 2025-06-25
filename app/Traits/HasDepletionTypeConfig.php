<?php

namespace App\Traits;

use App\Config\LivestockDepletionConfig;

/**
 * Trait for models that handle depletion types with config-based normalization
 * 
 * This trait provides methods to handle backward compatibility between
 * legacy Indonesian terms and standardized English terms.
 * 
 * @version 1.0
 * @since 2025-01-23
 */
trait HasDepletionTypeConfig
{
    /**
     * Get normalized depletion type
     * 
     * @return string
     */
    public function getNormalizedTypeAttribute(): string
    {
        return LivestockDepletionConfig::normalize($this->jenis ?? '');
    }

    /**
     * Get display name for depletion type
     * 
     * @param bool $short
     * @return string
     */
    public function getDisplayName(bool $short = false): string
    {
        return LivestockDepletionConfig::getDisplayName($this->jenis ?? '', $short);
    }

    /**
     * Get depletion category
     * 
     * @return string
     */
    public function getCategoryAttribute(): string
    {
        return LivestockDepletionConfig::getCategory($this->jenis ?? '');
    }

    /**
     * Get validation rules for this depletion type
     * 
     * @return array
     */
    public function getValidationRulesAttribute(): array
    {
        return LivestockDepletionConfig::getValidationRules($this->jenis ?? '');
    }

    /**
     * Check if this depletion type requires a specific field
     * 
     * @param string $field
     * @return bool
     */
    public function requiresField(string $field): bool
    {
        return LivestockDepletionConfig::requiresField($this->jenis ?? '', $field);
    }

    /**
     * Get legacy type (Indonesian)
     * 
     * @return string
     */
    public function getLegacyTypeAttribute(): string
    {
        return LivestockDepletionConfig::toLegacy($this->normalized_type);
    }

    /**
     * Check if this record uses legacy format
     * 
     * @return bool
     */
    public function isLegacyFormatAttribute(): bool
    {
        return in_array($this->jenis, LivestockDepletionConfig::getLegacyTypes());
    }

    /**
     * Convert to API format with backward compatibility
     * 
     * @param string $format ('legacy', 'standard', 'both')
     * @return array
     */
    public function toApiFormat(string $format = 'both'): array
    {
        $baseData = [
            'id' => $this->id,
            'livestock_id' => $this->livestock_id,
            'tanggal' => $this->tanggal?->format('Y-m-d'),
            'jumlah' => $this->jumlah,
            'recording_id' => $this->recording_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        switch ($format) {
            case 'legacy':
                return array_merge($baseData, [
                    'jenis' => $this->jenis,
                    'jenis_display' => $this->getDisplayName(true)
                ]);

            case 'standard':
                return array_merge($baseData, [
                    'type' => $this->normalized_type,
                    'type_display' => $this->getDisplayName(),
                    'category' => $this->category
                ]);

            case 'both':
            default:
                return array_merge($baseData, [
                    // Legacy format
                    'jenis' => $this->jenis,
                    'jenis_display' => $this->getDisplayName(true),

                    // Standard format
                    'type' => $this->normalized_type,
                    'type_display' => $this->getDisplayName(),
                    'category' => $this->category,

                    // Metadata
                    'is_legacy_format' => $this->is_legacy_format,
                    'validation_rules' => $this->validation_rules,
                    'conversion_info' => [
                        'config_version' => '1.0',
                        'normalized' => true
                    ]
                ]);
        }
    }

    /**
     * Scope to filter by normalized type
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        $normalizedType = LivestockDepletionConfig::normalize($type);

        // Get all possible representations of this type
        $possibleTypes = [
            $normalizedType,
            LivestockDepletionConfig::toLegacy($normalizedType),
            $type // original input
        ];

        return $query->whereIn('jenis', array_unique($possibleTypes));
    }

    /**
     * Scope to filter by category
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $category
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfCategory($query, string $category)
    {
        $typesInCategory = collect(LivestockDepletionConfig::getStandardTypes())
            ->filter(fn($type) => LivestockDepletionConfig::getCategory($type) === $category)
            ->flatMap(fn($type) => [
                $type,
                LivestockDepletionConfig::toLegacy($type)
            ])
            ->unique()
            ->values()
            ->toArray();

        return $query->whereIn('jenis', $typesInCategory);
    }

    /**
     * Create depletion with config normalization
     * 
     * @param array $attributes
     * @return static
     */
    public static function createWithConfig(array $attributes)
    {
        // Normalize the type before saving
        if (isset($attributes['type'])) {
            $normalizedType = LivestockDepletionConfig::normalize($attributes['type']);
            $attributes['jenis'] = LivestockDepletionConfig::toLegacy($normalizedType);
            unset($attributes['type']);
        }

        // Add config metadata
        $metadata = $attributes['metadata'] ?? [];
        $metadata['depletion_config'] = [
            'original_type' => $attributes['jenis'] ?? null,
            'normalized_type' => LivestockDepletionConfig::normalize($attributes['jenis'] ?? ''),
            'config_version' => '1.0',
            'created_with_config' => true,
            'created_at' => now()->toIso8601String()
        ];
        $attributes['metadata'] = $metadata;

        return static::create($attributes);
    }

    /**
     * Update with config normalization
     * 
     * @param array $attributes
     * @return bool
     */
    public function updateWithConfig(array $attributes): bool
    {
        // Normalize the type before updating
        if (isset($attributes['type'])) {
            $normalizedType = LivestockDepletionConfig::normalize($attributes['type']);
            $attributes['jenis'] = LivestockDepletionConfig::toLegacy($normalizedType);
            unset($attributes['type']);
        }

        // Update config metadata
        $metadata = $this->metadata ?? [];
        $metadata['depletion_config'] = array_merge(
            $metadata['depletion_config'] ?? [],
            [
                'last_updated_type' => $attributes['jenis'] ?? $this->jenis,
                'normalized_type' => LivestockDepletionConfig::normalize($attributes['jenis'] ?? $this->jenis),
                'config_version' => '1.0',
                'updated_with_config' => true,
                'updated_at' => now()->toIso8601String()
            ]
        );
        $attributes['metadata'] = $metadata;

        return $this->update($attributes);
    }
}

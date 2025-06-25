<?php

namespace App\Services;

use App\Config\LivestockDepletionConfig;
use App\Models\LivestockDepletion;
use App\Models\Recording;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Service to handle livestock depletion data with backward compatibility
 * 
 * This service ensures that legacy data (using Indonesian terms) and new data
 * (using English terms) can coexist and be properly converted when needed.
 * 
 * @version 1.0
 * @since 2025-01-23
 */
class LivestockDepletionService
{
    /**
     * Convert legacy depletion records to standardized format
     * 
     * @param Collection $records
     * @return Collection
     */
    public function normalizeDeplectionRecords(Collection $records): Collection
    {
        return $records->map(function ($record) {
            return $this->normalizeDepletionRecord($record);
        });
    }

    /**
     * Normalize a single depletion record
     * 
     * @param LivestockDepletion $record
     * @return LivestockDepletion
     */
    public function normalizeDepletionRecord(LivestockDepletion $record): LivestockDepletion
    {
        // Add normalized fields without modifying original data
        $record->normalized_type = LivestockDepletionConfig::normalize($record->jenis);
        $record->display_name = LivestockDepletionConfig::getDisplayName($record->jenis);
        $record->display_name_short = LivestockDepletionConfig::getDisplayName($record->jenis, true);
        $record->category = LivestockDepletionConfig::getCategory($record->jenis);
        $record->validation_rules = LivestockDepletionConfig::getValidationRules($record->jenis);

        // Add conversion metadata
        $record->conversion_metadata = [
            'original_type' => $record->jenis,
            'normalized_type' => $record->normalized_type,
            'converted_at' => now()->toIso8601String(),
            'config_version' => '1.0'
        ];

        return $record;
    }

    /**
     * Get depletion data for a livestock with backward compatibility
     * 
     * @param string $livestockId
     * @param string|null $date
     * @return array
     */
    public function getDepletionDataForLivestock(string $livestockId, ?string $date = null): array
    {
        $query = LivestockDepletion::where('livestock_id', $livestockId);

        if ($date) {
            $query->whereDate('tanggal', $date);
        }

        $records = $query->get();
        $normalizedRecords = $this->normalizeDeplectionRecords($records);

        // Group by normalized types for consistency
        $groupedData = $normalizedRecords->groupBy('normalized_type')->map(function ($group, $type) {
            return [
                'type' => $type,
                'display_name' => LivestockDepletionConfig::getDisplayName($type),
                'total_quantity' => $group->sum('jumlah'),
                'records_count' => $group->count(),
                'records' => $group->values(),
                'category' => LivestockDepletionConfig::getCategory($type)
            ];
        });

        return [
            'livestock_id' => $livestockId,
            'date_filter' => $date,
            'total_records' => $records->count(),
            'grouped_by_type' => $groupedData,
            'summary' => $this->generateDepletionSummary($normalizedRecords),
            'conversion_metadata' => [
                'service_version' => '1.0',
                'processed_at' => now()->toIso8601String(),
                'config_version' => '1.0'
            ]
        ];
    }

    /**
     * Generate summary statistics for depletion data
     * 
     * @param Collection $records
     * @return array
     */
    private function generateDepletionSummary(Collection $records): array
    {
        $summary = [];

        foreach (LivestockDepletionConfig::getStandardTypes() as $type) {
            $typeRecords = $records->where('normalized_type', $type);
            $summary[$type] = [
                'type' => $type,
                'display_name' => LivestockDepletionConfig::getDisplayName($type),
                'quantity' => $typeRecords->sum('jumlah'),
                'count' => $typeRecords->count(),
                'category' => LivestockDepletionConfig::getCategory($type)
            ];
        }

        // Calculate totals
        $summary['totals'] = [
            'total_quantity' => $records->sum('jumlah'),
            'total_records' => $records->count(),
            'types_with_data' => collect($summary)->filter(fn($item) => ($item['count'] ?? 0) > 0)->count()
        ];

        return $summary;
    }

    /**
     * Convert depletion data for API responses with backward compatibility
     * 
     * @param LivestockDepletion $record
     * @param string $format ('legacy', 'standard', 'both')
     * @return array
     */
    public function convertForApiResponse(LivestockDepletion $record, string $format = 'both'): array
    {
        $normalized = $this->normalizeDepletionRecord($record);

        $baseData = [
            'id' => $record->id,
            'livestock_id' => $record->livestock_id,
            'tanggal' => $record->tanggal->format('Y-m-d'),
            'jumlah' => $record->jumlah,
            'recording_id' => $record->recording_id,
            'created_at' => $record->created_at,
            'updated_at' => $record->updated_at,
        ];

        switch ($format) {
            case 'legacy':
                return array_merge($baseData, [
                    'jenis' => $record->jenis,
                    'jenis_display' => LivestockDepletionConfig::getDisplayName($record->jenis, true)
                ]);

            case 'standard':
                return array_merge($baseData, [
                    'type' => $normalized->normalized_type,
                    'type_display' => $normalized->display_name,
                    'category' => $normalized->category
                ]);

            case 'both':
            default:
                return array_merge($baseData, [
                    // Legacy format
                    'jenis' => $record->jenis,
                    'jenis_display' => LivestockDepletionConfig::getDisplayName($record->jenis, true),

                    // Standard format
                    'type' => $normalized->normalized_type,
                    'type_display' => $normalized->display_name,
                    'category' => $normalized->category,

                    // Metadata
                    'conversion_info' => [
                        'original_format' => 'detected_from_database',
                        'normalized' => true,
                        'config_version' => '1.0'
                    ]
                ]);
        }
    }

    /**
     * Validate depletion data using config rules
     * 
     * @param array $data
     * @return array
     */
    public function validateDepletionData(array $data): array
    {
        $errors = [];
        $type = $data['type'] ?? $data['jenis'] ?? null;

        if (!$type) {
            $errors[] = 'Depletion type is required';
            return ['valid' => false, 'errors' => $errors];
        }

        $normalizedType = LivestockDepletionConfig::normalize($type);

        if (!LivestockDepletionConfig::isValidType($type)) {
            $errors[] = "Invalid depletion type: {$type}";
        }

        // Check required fields based on config
        if (LivestockDepletionConfig::requiresField($normalizedType, 'reason')) {
            if (empty($data['reason'])) {
                $errors[] = "Reason is required for {$normalizedType} depletion";
            }
        }

        if (LivestockDepletionConfig::requiresField($normalizedType, 'weight')) {
            if (empty($data['weight']) || !is_numeric($data['weight'])) {
                $errors[] = "Weight is required for {$normalizedType} depletion";
            }
        }

        if (LivestockDepletionConfig::requiresField($normalizedType, 'price')) {
            if (empty($data['price']) || !is_numeric($data['price'])) {
                $errors[] = "Price is required for {$normalizedType} depletion";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'normalized_type' => $normalizedType,
            'validation_rules' => LivestockDepletionConfig::getValidationRules($normalizedType)
        ];
    }

    /**
     * Migrate legacy depletion data to include normalized metadata
     * 
     * @param int $batchSize
     * @return array
     */
    public function migrateLegacyData(int $batchSize = 100): array
    {
        $totalProcessed = 0;
        $totalUpdated = 0;
        $errors = [];

        try {
            LivestockDepletion::whereNull('metadata->depletion_config')
                ->orWhereJsonLength('metadata->depletion_config', 0)
                ->chunk($batchSize, function ($records) use (&$totalProcessed, &$totalUpdated, &$errors) {
                    foreach ($records as $record) {
                        try {
                            $normalizedType = LivestockDepletionConfig::normalize($record->jenis);

                            // Update metadata with config information
                            $currentMetadata = $record->metadata ?? [];
                            $currentMetadata['depletion_config'] = [
                                'original_type' => $record->jenis,
                                'normalized_type' => $normalizedType,
                                'legacy_type' => LivestockDepletionConfig::toLegacy($normalizedType),
                                'display_name' => LivestockDepletionConfig::getDisplayName($normalizedType),
                                'category' => LivestockDepletionConfig::getCategory($normalizedType),
                                'config_version' => '1.0',
                                'migrated_at' => now()->toIso8601String()
                            ];

                            $record->update(['metadata' => $currentMetadata]);
                            $totalUpdated++;
                        } catch (\Exception $e) {
                            $errors[] = "Error processing record {$record->id}: " . $e->getMessage();
                        }

                        $totalProcessed++;
                    }
                });

            Log::info('Legacy depletion data migration completed', [
                'total_processed' => $totalProcessed,
                'total_updated' => $totalUpdated,
                'errors_count' => count($errors)
            ]);
        } catch (\Exception $e) {
            $errors[] = "Migration error: " . $e->getMessage();
            Log::error('Legacy depletion data migration failed', ['error' => $e->getMessage()]);
        }

        return [
            'success' => empty($errors) || count($errors) < $totalProcessed * 0.1, // Allow 10% error rate
            'total_processed' => $totalProcessed,
            'total_updated' => $totalUpdated,
            'errors' => $errors,
            'migration_completed_at' => now()->toIso8601String()
        ];
    }

    /**
     * Get migration status
     * 
     * @return array
     */
    public function getMigrationStatus(): array
    {
        $totalRecords = LivestockDepletion::count();
        $migratedRecords = LivestockDepletion::whereNotNull('metadata->depletion_config')->count();
        $unmigrated = $totalRecords - $migratedRecords;

        return [
            'total_records' => $totalRecords,
            'migrated_records' => $migratedRecords,
            'unmigrated_records' => $unmigrated,
            'migration_percentage' => $totalRecords > 0 ? round(($migratedRecords / $totalRecords) * 100, 2) : 0,
            'migration_complete' => $unmigrated === 0,
            'checked_at' => now()->toIso8601String()
        ];
    }
}

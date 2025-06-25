<?php

namespace App\Services\Livestock;

use App\Models\Livestock;
use App\Models\LivestockDepletion;
use App\Config\LivestockDepletionConfig;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

/**
 * Depletion Data Standardization Service
 * 
 * Service untuk standardisasi data depletion antara metode manual/traditional dan FIFO
 * agar memiliki struktur metadata dan data yang konsisten untuk reporting dan debugging.
 * 
 * @author System
 * @version 1.0
 */
class DepletionDataStandardizationService
{
    /**
     * Standardize FIFO depletion records to match traditional format
     * This ensures consistent metadata and data structure across all depletion methods
     *
     * @param array $fifoRecords Array of FIFO depletion records
     * @param Livestock $livestock The livestock instance
     * @param string $jenis The depletion type
     * @param string $recordingId The recording ID
     * @param int $age The livestock age in days
     * @return array Standardized records
     */
    public function standardizeFifoDepletionRecords(array $fifoRecords, Livestock $livestock, string $jenis, string $recordingId, int $age): array
    {
        try {
            Log::info('🔄 Standardizing FIFO depletion records', [
                'livestock_id' => $livestock->id,
                'depletion_type' => $jenis,
                'records_count' => count($fifoRecords),
                'recording_id' => $recordingId
            ]);

            $standardizedRecords = [];

            foreach ($fifoRecords as $record) {
                $standardizedRecord = $this->standardizeSingleRecord($record, $livestock, $jenis, $recordingId, $age);
                if ($standardizedRecord) {
                    $standardizedRecords[] = $standardizedRecord;
                }
            }

            Log::info('✅ FIFO depletion records standardized successfully', [
                'livestock_id' => $livestock->id,
                'original_count' => count($fifoRecords),
                'standardized_count' => count($standardizedRecords)
            ]);

            return $standardizedRecords;
        } catch (Exception $e) {
            Log::error('❌ Error standardizing FIFO depletion records', [
                'livestock_id' => $livestock->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Standardize a single depletion record
     *
     * @param mixed $record The depletion record (could be model or array)
     * @param Livestock $livestock The livestock instance
     * @param string $jenis The depletion type
     * @param string $recordingId The recording ID
     * @param int $age The livestock age in days
     * @return array|null Standardized record or null if failed
     */
    private function standardizeSingleRecord($record, Livestock $livestock, string $jenis, string $recordingId, int $age): ?array
    {
        try {
            // Convert model to array if needed
            $recordData = is_array($record) ? $record : $record->toArray();

            // Normalize depletion type using config
            $normalizedType = LivestockDepletionConfig::normalize($jenis);
            $legacyType = LivestockDepletionConfig::getLegacyType($normalizedType);

            // Build standardized metadata
            $standardizedMetadata = $this->buildStandardizedMetadata($livestock, $normalizedType, $recordingId, $age);

            // Build standardized data
            $standardizedData = $this->buildStandardizedData($recordData, $livestock, $normalizedType);

            // Update the record with standardized structure
            if (isset($recordData['id'])) {
                $depletionRecord = LivestockDepletion::find($recordData['id']);
                if ($depletionRecord) {
                    $depletionRecord->update([
                        'jenis' => $normalizedType, // Use normalized type consistently
                        'metadata' => $standardizedMetadata,
                        'data' => $standardizedData
                    ]);

                    Log::info('📝 Depletion record standardized', [
                        'record_id' => $recordData['id'],
                        'livestock_id' => $livestock->id,
                        'original_type' => $jenis,
                        'normalized_type' => $normalizedType,
                        'legacy_type' => $legacyType
                    ]);

                    return $depletionRecord->toArray();
                }
            }

            return null;
        } catch (Exception $e) {
            Log::error('❌ Error standardizing single depletion record', [
                'livestock_id' => $livestock->id,
                'record_id' => $recordData['id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Build standardized metadata structure
     *
     * @param Livestock $livestock The livestock instance
     * @param string $normalizedType The normalized depletion type
     * @param string $recordingId The recording ID
     * @param int $age The livestock age in days
     * @return array Standardized metadata
     */
    private function buildStandardizedMetadata(Livestock $livestock, string $normalizedType, string $recordingId, int $age): array
    {
        return [
            // Basic livestock information
            'livestock_name' => $livestock->name ?? 'Unknown',
            'farm_id' => $livestock->farm_id ?? null,
            'farm_name' => $livestock->farm->name ?? 'Unknown',
            'coop_id' => $livestock->coop_id ?? null,
            'kandang_name' => $livestock->kandang->name ?? 'Unknown',

            // Age and timing information
            'age_days' => $age,
            'updated_at' => now()->format('Y-m-d\TH:i:sP'),
            'updated_by' => auth()->id() ?? null,
            'updated_by_name' => auth()->user()->name ?? 'System',

            // Depletion configuration
            'depletion_config' => [
                'original_type' => request()->input('original_type', $normalizedType),
                'normalized_type' => $normalizedType,
                'legacy_type' => LivestockDepletionConfig::getLegacyType($normalizedType),
                'display_name' => LivestockDepletionConfig::getDisplayName($normalizedType),
                'category' => LivestockDepletionConfig::getCategory($normalizedType),
                'config_version' => '1.0'
            ],

            // Processing information
            'processing_method' => 'standardization_service',
            'standardized_at' => now()->toISOString(),
            'standardized_by' => auth()->id() ?? null,
            'recording_id' => $recordingId,

            // Method tracking
            'depletion_method' => 'fifo', // Mark as FIFO method
            'standardization_version' => '1.0'
        ];
    }

    /**
     * Build standardized data structure
     *
     * @param array $recordData The original record data
     * @param Livestock $livestock The livestock instance
     * @param string $normalizedType The normalized depletion type
     * @return array Standardized data
     */
    private function buildStandardizedData(array $recordData, Livestock $livestock, string $normalizedType): array
    {
        // Get batch information if available
        $batchInfo = $this->extractBatchInformation($recordData, $livestock);

        return [
            // Batch information (similar to traditional method)
            'batch_id' => $batchInfo['batch_id'] ?? null,
            'batch_name' => $batchInfo['batch_name'] ?? 'FIFO-Generated',
            'batch_start_date' => $batchInfo['batch_start_date'] ?? null,

            // Method information
            'depletion_method' => 'fifo',
            'processing_type' => 'fifo_service',

            // Quantity information
            'original_request' => $recordData['jumlah'] ?? 0,
            'processed_quantity' => $recordData['jumlah'] ?? 0,

            // FIFO specific data
            'fifo_distribution' => $recordData['fifo_distribution'] ?? null,
            'fifo_sequence' => $recordData['fifo_sequence'] ?? null,
            'affected_batches' => $recordData['affected_batches'] ?? [],

            // Consistency markers
            'data_structure_version' => '3.0',
            'standardized' => true,
            'standardized_at' => now()->toISOString(),

            // Backward compatibility
            'legacy_data' => $recordData['original_data'] ?? null
        ];
    }

    /**
     * Extract batch information from record data
     *
     * @param array $recordData The record data
     * @param Livestock $livestock The livestock instance
     * @return array Batch information
     */
    private function extractBatchInformation(array $recordData, Livestock $livestock): array
    {
        // Try to get batch info from FIFO distribution
        if (isset($recordData['fifo_distribution']) && is_array($recordData['fifo_distribution'])) {
            $firstBatch = reset($recordData['fifo_distribution']);
            if ($firstBatch) {
                return [
                    'batch_id' => $firstBatch['batch_id'] ?? null,
                    'batch_name' => $firstBatch['batch_name'] ?? null,
                    'batch_start_date' => $firstBatch['start_date'] ?? null
                ];
            }
        }

        // Try to get from affected batches
        if (isset($recordData['affected_batches']) && is_array($recordData['affected_batches'])) {
            $firstBatch = reset($recordData['affected_batches']);
            if ($firstBatch) {
                return [
                    'batch_id' => $firstBatch['id'] ?? null,
                    'batch_name' => $firstBatch['name'] ?? null,
                    'batch_start_date' => $firstBatch['start_date'] ?? null
                ];
            }
        }

        // Fallback to livestock's latest batch
        $latestBatch = $livestock->batches()->latest('start_date')->first();
        if ($latestBatch) {
            return [
                'batch_id' => $latestBatch->id,
                'batch_name' => $latestBatch->name ?? 'Latest Batch',
                'batch_start_date' => $latestBatch->start_date
            ];
        }

        return [
            'batch_id' => null,
            'batch_name' => 'Unknown Batch',
            'batch_start_date' => null
        ];
    }

    /**
     * Validate record consistency after standardization
     *
     * @param array $standardizedRecords The standardized records
     * @param Livestock $livestock The livestock instance
     * @return array Validation results
     */
    public function validateRecordConsistency(array $standardizedRecords, Livestock $livestock): array
    {
        $validationResults = [
            'valid' => true,
            'total_records' => count($standardizedRecords),
            'valid_records' => 0,
            'invalid_records' => 0,
            'issues' => []
        ];

        foreach ($standardizedRecords as $record) {
            $recordValid = true;
            $recordIssues = [];

            // Check required fields
            $requiredFields = ['jenis', 'metadata', 'data'];
            foreach ($requiredFields as $field) {
                if (!isset($record[$field])) {
                    $recordValid = false;
                    $recordIssues[] = "Missing required field: {$field}";
                }
            }

            // Check metadata structure
            if (isset($record['metadata'])) {
                $requiredMetadataFields = ['livestock_name', 'depletion_config', 'processing_method'];
                foreach ($requiredMetadataFields as $field) {
                    if (!isset($record['metadata'][$field])) {
                        $recordValid = false;
                        $recordIssues[] = "Missing metadata field: {$field}";
                    }
                }
            }

            // Check data structure
            if (isset($record['data'])) {
                $requiredDataFields = ['depletion_method', 'data_structure_version'];
                foreach ($requiredDataFields as $field) {
                    if (!isset($record['data'][$field])) {
                        $recordValid = false;
                        $recordIssues[] = "Missing data field: {$field}";
                    }
                }
            }

            if ($recordValid) {
                $validationResults['valid_records']++;
            } else {
                $validationResults['invalid_records']++;
                $validationResults['issues'][] = [
                    'record_id' => $record['id'] ?? 'unknown',
                    'issues' => $recordIssues
                ];
            }
        }

        if ($validationResults['invalid_records'] > 0) {
            $validationResults['valid'] = false;
        }

        Log::info('📊 Record consistency validation completed', [
            'livestock_id' => $livestock->id,
            'validation_results' => $validationResults
        ]);

        return $validationResults;
    }

    /**
     * Get standardization statistics
     *
     * @param Livestock $livestock The livestock instance
     * @param string $period The period to analyze (e.g., '30_days', '7_days')
     * @return array Standardization statistics
     */
    public function getStandardizationStats(Livestock $livestock, string $period = '30_days'): array
    {
        $days = match ($period) {
            '7_days' => 7,
            '30_days' => 30,
            '90_days' => 90,
            default => 30
        };

        $startDate = Carbon::now()->subDays($days);

        $depletions = LivestockDepletion::where('livestock_id', $livestock->id)
            ->where('created_at', '>=', $startDate)
            ->get();

        $stats = [
            'total_records' => $depletions->count(),
            'standardized_records' => 0,
            'fifo_records' => 0,
            'manual_records' => 0,
            'consistent_structure' => 0,
            'inconsistent_structure' => 0,
            'methods_breakdown' => [],
            'data_quality_score' => 0
        ];

        foreach ($depletions as $depletion) {
            // Check if standardized
            if (isset($depletion->data['standardized']) && $depletion->data['standardized']) {
                $stats['standardized_records']++;
            }

            // Check method
            $method = $depletion->data['depletion_method'] ?? 'unknown';
            if ($method === 'fifo') {
                $stats['fifo_records']++;
            } elseif (in_array($method, ['manual', 'traditional'])) {
                $stats['manual_records']++;
            }

            // Count methods
            if (!isset($stats['methods_breakdown'][$method])) {
                $stats['methods_breakdown'][$method] = 0;
            }
            $stats['methods_breakdown'][$method]++;

            // Check structure consistency
            $hasRequiredFields = isset($depletion->metadata['depletion_config']) &&
                isset($depletion->data['depletion_method']);

            if ($hasRequiredFields) {
                $stats['consistent_structure']++;
            } else {
                $stats['inconsistent_structure']++;
            }
        }

        // Calculate data quality score
        if ($stats['total_records'] > 0) {
            $qualityScore = ($stats['consistent_structure'] / $stats['total_records']) * 100;
            $stats['data_quality_score'] = round($qualityScore, 2);
        }

        return $stats;
    }
}

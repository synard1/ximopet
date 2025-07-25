<?php

declare(strict_types=1);

namespace App\Services\Recording;

use App\Models\Livestock;
use App\Models\SupplyUsage;
use App\Services\Recording\DTOs\ServiceResult;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Simple Recording Data Aggregator Service
 * 
 * Simplified version untuk testing dan debugging
 */
class SimpleRecordingDataAggregatorService
{
    /**
     * Valid supply usage statuses for processing
     */
    private const VALID_SUPPLY_STATUSES = [
        'pending',
        'in_process',
        'completed',
        'partially_used'
    ];

    /**
     * Aggregate data from supply usage only
     */
    public function aggregateData(string $livestockId, string $date): ServiceResult
    {
        try {
            Log::info('🔄 SimpleRecordingDataAggregatorService::aggregateData started', [
                'livestock_id' => $livestockId,
                'date' => $date
            ]);

            // Validate inputs
            $this->validateInputs($livestockId, $date);

            // Get livestock information
            $livestock = Livestock::findOrFail($livestockId);
            Log::info('✅ Livestock found', ['livestock_name' => $livestock->name]);

            // Get supply usage data
            $supplyUsageData = $this->getSupplyUsageData($livestockId, $date);
            Log::info('✅ Supply usage data retrieved', ['has_data' => !empty($supplyUsageData)]);

            // Build simple payload
            $payload = $this->buildSimplePayload($livestock, $date, $supplyUsageData);

            Log::info('✅ SimpleRecordingDataAggregatorService::aggregateData completed');

            return ServiceResult::success('Data aggregated successfully', $payload);
        } catch (Exception $e) {
            Log::error('❌ SimpleRecordingDataAggregatorService::aggregateData failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ServiceResult::error('Failed to aggregate data', $e);
        }
    }

    /**
     * Get supply usage data
     */
    private function getSupplyUsageData(string $livestockId, string $date): array
    {
        try {
            $supplyUsage = SupplyUsage::where('livestock_id', $livestockId)
                ->whereDate('usage_date', $date)
                ->whereIn('status', self::VALID_SUPPLY_STATUSES)
                ->first();

            if (!$supplyUsage) {
                Log::debug('No supply usage found', [
                    'livestock_id' => $livestockId,
                    'date' => $date
                ]);
                return [];
            }

            Log::debug('Supply usage found', [
                'supply_usage_id' => $supplyUsage->id,
                'status' => $supplyUsage->status
            ]);

            return [
                'usage_id' => $supplyUsage->id,
                'status' => $supplyUsage->status,
                'total_quantity' => 0,
                'total_cost' => 0,
                'items' => []
            ];
        } catch (Exception $e) {
            Log::error('Error in getSupplyUsageData', [
                'livestock_id' => $livestockId,
                'date' => $date,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Build simple payload
     */
    private function buildSimplePayload(Livestock $livestock, string $date, array $supplyUsageData): array
    {
        return [
            'schema' => [
                'version' => '3.0',
                'structure' => 'simple',
                'schema_date' => '2025-01-25'
            ],
            'livestock' => [
                'id' => $livestock->id,
                'name' => $livestock->name
            ],
            'date' => $date,
            'supply_usage' => $supplyUsageData,
            'metadata' => [
                'aggregated_at' => now()->toIso8601String(),
                'data_sources' => !empty($supplyUsageData) ? ['supply_usage'] : [],
                'service_version' => '1.0-simple'
            ]
        ];
    }

    /**
     * Validate input parameters
     */
    private function validateInputs(string $livestockId, string $date): void
    {
        if (empty($livestockId)) {
            throw new Exception('Livestock ID is required');
        }

        if (empty($date)) {
            throw new Exception('Date is required');
        }

        // Try to parse the date to validate format
        try {
            Carbon::parse($date);
        } catch (Exception $e) {
            throw new Exception('Invalid date format: ' . $date);
        }
    }
}

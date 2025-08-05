<?php

namespace App\Services\Livestock;

use App\Models\Livestock;
use App\Models\LivestockBatch;
use App\Models\LivestockPurchase;
use App\Models\LivestockMutation;
use App\Models\LivestockDepletion;
use App\Models\LivestockSalesItem;
use App\Models\Recording;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Livestock Tracking Service
 * 
 * Comprehensive service for tracking livestock origin and batch history
 * with detailed chain of custody, performance metrics, and audit trail.
 * 
 * Features:
 * - Complete origin chain tracking
 * - Batch history and performance analysis
 * - Mutation and movement tracking
 * - Cost and performance metrics
 * - Audit trail and compliance reporting
 * - Real-time status monitoring
 * 
 * @author System
 * @version 1.0
 */
class LivestockTrackingService
{
    /**
     * Get complete tracking information for a livestock
     * 
     * @param string $livestockId
     * @return array
     */
    public function getCompleteTrackingInfo(string $livestockId): array
    {
        try {
            $livestock = Livestock::findOrFail($livestockId);

            return [
                'livestock_info' => $this->getLivestockBasicInfo($livestock),
                'origin_chain' => $this->getOriginChain($livestock),
                'batch_history' => $this->getBatchHistory($livestock),
                'mutation_history' => $this->getMutationHistory($livestock),
                'performance_metrics' => $this->getPerformanceMetrics($livestock),
                'cost_analysis' => $this->getCostAnalysis($livestock),
                'current_status' => $this->getCurrentStatus($livestock),
                'compliance_info' => $this->getComplianceInfo($livestock),
                'audit_trail' => $this->getAuditTrail($livestock)
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'Failed to retrieve tracking information',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get basic livestock information
     * 
     * @param Livestock $livestock
     * @return array
     */
    private function getLivestockBasicInfo(Livestock $livestock): array
    {
        return [
            'id' => $livestock->id,
            'name' => $livestock->name,
            'farm' => $livestock->farm->name ?? 'Unknown',
            'coop' => $livestock->coop->name ?? 'Unknown',
            'strain' => $livestock->livestockStrain->name ?? 'Unknown',
            'status' => $livestock->status,
            'status_label' => $livestock->getStatusLabel(),
            'created_at' => $livestock->created_at,
            'updated_at' => $livestock->updated_at,
            'total_initial_quantity' => $livestock->getTotalInitialQuantity(),
            'total_available_quantity' => $livestock->getTotalAvailableQuantity(),
            'total_weight' => $livestock->getTotalWeight(),
            'availability_percentage' => $livestock->getOverallAvailabilityPercentage()
        ];
    }

    /**
     * Get complete origin chain from purchase to current state
     * 
     * @param Livestock $livestock
     * @return array
     */
    private function getOriginChain(Livestock $livestock): array
    {
        try {
            $chain = [];

            // Get purchase information directly from batches
            $purchaseBatches = $livestock->batches()
                ->where('source_type', 'purchase')
                ->get();

            foreach ($purchaseBatches as $batch) {
                // Check if sourcePurchase exists
                if ($batch->sourcePurchase) {
                    $chain[] = [
                        'type' => 'purchase',
                        'date' => $batch->start_date ?? $batch->created_at,
                        'invoice_number' => $batch->sourcePurchase->invoice_number ?? 'N/A',
                        'supplier' => $batch->sourcePurchase->supplier ? $batch->sourcePurchase->supplier->name : 'Unknown',
                        'quantity' => $batch->initial_quantity,
                        'price_per_unit' => $batch->price_per_unit,
                        'weight_per_unit' => $batch->weight_per_unit,
                        'total_cost' => $batch->initial_quantity * $batch->price_per_unit,
                        'data' => [
                            'batch_id' => $batch->id,
                            'batch_name' => $batch->name
                        ]
                    ];
                }
            }

            // Get mutation history directly from batches
            $mutationBatches = $livestock->batches()
                ->where('source_type', 'mutation')
                ->get();

            foreach ($mutationBatches as $batch) {
                if ($batch->sourceMutation) {
                    $chain[] = [
                        'type' => 'mutation',
                        'date' => $batch->start_date ?? $batch->created_at,
                        'quantity' => $batch->initial_quantity,
                        'direction' => $batch->sourceMutation->direction ?? 'unknown',
                        'mutation_type' => $batch->sourceMutation->jenis ?? 'unknown',
                        'source_livestock' => $batch->sourceMutation->sourceLivestock ? $batch->sourceMutation->sourceLivestock->name : 'Unknown',
                        'destination_livestock' => $batch->sourceMutation->destinationLivestock ? $batch->sourceMutation->destinationLivestock->name : 'Unknown',
                        'data' => [
                            'batch_id' => $batch->id,
                            'batch_name' => $batch->name,
                            'mutation_id' => $batch->sourceMutation->id
                        ]
                    ];
                }
            }

            return $chain;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get detailed batch history
     * 
     * @param Livestock $livestock
     * @return array
     */
    private function getBatchHistory(Livestock $livestock): array
    {
        try {
            $batches = $livestock->batches()
                ->orderBy('created_at', 'asc')
                ->get();

            $batchHistory = [];

            foreach ($batches as $batch) {
                $batchHistory[] = [
                    'id' => $batch->id,
                    'name' => $batch->name,
                    'number' => $batch->number,
                    'source_type' => $batch->source_type,
                    'source_id' => $batch->source_id,
                    'initial_quantity' => $batch->initial_quantity,
                    'quantity_available' => $batch->quantity_available,
                    'quantity_depletion' => $batch->quantity_depletion,
                    'quantity_sales' => $batch->quantity_sales,
                    'quantity_mutated' => $batch->quantity_mutated,
                    'initial_weight' => $batch->initial_weight,
                    'weight' => $batch->weight,
                    'weight_per_unit' => $batch->weight_per_unit,
                    'price_per_unit' => $batch->price_per_unit,
                    'price_total' => $batch->price_total,
                    'start_date' => $batch->start_date,
                    'end_date' => $batch->end_date,
                    'status' => $batch->status,
                    'notes' => $batch->notes,
                    'source_purchase' => $batch->sourcePurchase,
                    'source_mutation' => $batch->sourceMutation,
                    'purchase_item' => $batch->purchaseItem,
                    'created_at' => $batch->created_at,
                    'updated_at' => $batch->updated_at
                ];
            }

            return $batchHistory;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get detailed mutation history
     * 
     * @param Livestock $livestock
     * @return array
     */
    private function getMutationHistory(Livestock $livestock): array
    {
        try {
            $mutations = $livestock->allMutations();

            $mutationHistory = [];

            foreach ($mutations as $mutation) {
                $mutationHistory[] = [
                    'id' => $mutation->id,
                    'date' => $mutation->tanggal,
                    'type' => $mutation->jenis,
                    'direction' => $mutation->direction,
                    'quantity' => $mutation->jumlah,
                    'source_livestock' => $mutation->sourceLivestock ? $mutation->sourceLivestock->name : 'Unknown',
                    'destination_livestock' => $mutation->destinationLivestock ? $mutation->destinationLivestock->name : 'Unknown',
                    'notes' => $mutation->notes,
                    'status' => $mutation->status ?? 'completed',
                    'created_at' => $mutation->created_at,
                    'updated_at' => $mutation->updated_at
                ];
            }

            // Sort by date
            usort($mutationHistory, function ($a, $b) {
                return $a['date']->timestamp - $b['date']->timestamp;
            });

            return $mutationHistory;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get performance metrics
     * 
     * @param Livestock $livestock
     * @return array
     */
    private function getPerformanceMetrics(Livestock $livestock): array
    {
        $totalInitial = $livestock->getTotalInitialQuantity();
        $totalAvailable = $livestock->getTotalAvailableQuantity();
        $totalDepletion = $livestock->getTotalDepletionQuantity();
        $totalSales = $livestock->getTotalSalesQuantity();
        $totalMutated = $livestock->getTotalMutatedQuantity();

        $survivalRate = $totalInitial > 0 ? (($totalAvailable + $totalSales) / $totalInitial) * 100 : 0;
        $mortalityRate = $totalInitial > 0 ? ($totalDepletion / $totalInitial) * 100 : 0;
        $salesRate = $totalInitial > 0 ? ($totalSales / $totalInitial) * 100 : 0;

        return [
            'total_initial_quantity' => $totalInitial,
            'total_available_quantity' => $totalAvailable,
            'total_depletion_quantity' => $totalDepletion,
            'total_sales_quantity' => $totalSales,
            'total_mutated_quantity' => $totalMutated,
            'survival_rate_percentage' => round($survivalRate, 2),
            'mortality_rate_percentage' => round($mortalityRate, 2),
            'sales_rate_percentage' => round($salesRate, 2),
            'availability_percentage' => $livestock->getOverallAvailabilityPercentage(),
            'availability_status' => $livestock->getOverallAvailabilityStatus()
        ];
    }

    /**
     * Get cost analysis
     * 
     * @param Livestock $livestock
     * @return array
     */
    private function getCostAnalysis(Livestock $livestock): array
    {
        $purchaseInfo = $livestock->getPurchaseInfo();
        $feedStats = $livestock->getFeedStats();

        $initialCost = 0;
        $feedCost = $feedStats['total_cost'] ?? 0;
        $totalCost = $initialCost + $feedCost;

        if ($purchaseInfo) {
            $initialCost = $purchaseInfo['quantity'] * $purchaseInfo['price_per_unit'];
            $totalCost = $initialCost + $feedCost;
        }

        $availableQuantity = $livestock->getTotalAvailableQuantity();
        $costPerUnit = $availableQuantity > 0 ? $totalCost / $availableQuantity : 0;

        return [
            'initial_cost' => $initialCost,
            'feed_cost' => $feedCost,
            'total_cost' => $totalCost,
            'cost_per_unit' => $costPerUnit,
            'feed_consumption' => $feedStats['total_consumed'] ?? 0,
            'average_feed_cost_per_unit' => $livestock->getAverageFeedCostPerUnit(),
            'purchase_info' => $purchaseInfo
        ];
    }

    /**
     * Get current status information
     * 
     * @param Livestock $livestock
     * @return array
     */
    private function getCurrentStatus(Livestock $livestock): array
    {
        return [
            'status' => $livestock->status,
            'status_label' => $livestock->getStatusLabel(),
            'is_active' => $livestock->isActive(),
            'can_be_edited' => $livestock->canBeEdited(),
            'can_be_cancelled' => $livestock->canBeCancelled(),
            'is_locked' => $livestock->isLocked(),
            'last_updated' => $livestock->updated_at,
            'days_since_start' => $livestock->start_date ? Carbon::parse($livestock->start_date)->diffInDays(now()) : 0
        ];
    }

    /**
     * Get compliance information
     * 
     * @param Livestock $livestock
     * @return array
     */
    private function getComplianceInfo(Livestock $livestock): array
    {
        $batches = $livestock->batches()->count();
        $hasMultipleBatches = $livestock->hasMultipleBatches();
        $isRecordingConfigured = $livestock->isRecordingMethodConfigured();

        return [
            'has_proper_tracking' => $batches > 0,
            'has_multiple_batches' => $hasMultipleBatches,
            'is_recording_configured' => $isRecordingConfigured,
            'batch_count' => $batches,
            'active_batch_count' => $livestock->getActiveBatchesCount(),
            'recording_method' => $livestock->getRecordingMethod(),
            'depletion_method' => $livestock->getDepletionMethod(),
            'mutation_method' => $livestock->getConfiguredMutationMethod(),
            'feed_usage_method' => $livestock->getConfiguredFeedUsageMethod()
        ];
    }

    /**
     * Get audit trail
     * 
     * @param Livestock $livestock
     * @return array
     */
    private function getAuditTrail(Livestock $livestock): array
    {
        try {
            $auditTrail = [];

            // Add livestock creation
            $auditTrail[] = [
                'date' => $livestock->created_at,
                'action' => 'livestock_created',
                'description' => 'Livestock created',
                'user' => $livestock->created_by,
                'data' => [
                    'name' => $livestock->name,
                    'farm' => $livestock->farm->name ?? 'Unknown',
                    'coop' => $livestock->coop->name ?? 'Unknown'
                ]
            ];

            // Add batch creations
            $batches = $livestock->batches()->orderBy('created_at')->get();
            foreach ($batches as $batch) {
                $auditTrail[] = [
                    'date' => $batch->created_at,
                    'action' => 'batch_created',
                    'description' => "Batch '{$batch->name}' created",
                    'user' => $batch->created_by,
                    'data' => [
                        'batch_name' => $batch->name,
                        'quantity' => $batch->initial_quantity,
                        'source_type' => $batch->source_type
                    ]
                ];
            }

            // Add mutations
            $mutations = $livestock->allMutations();
            foreach ($mutations as $mutation) {
                $auditTrail[] = [
                    'date' => $mutation->created_at,
                    'action' => 'mutation_created',
                    'description' => "Mutation {$mutation->jenis} ({$mutation->direction})",
                    'user' => $mutation->created_by,
                    'data' => [
                        'type' => $mutation->jenis,
                        'direction' => $mutation->direction,
                        'quantity' => $mutation->jumlah
                    ]
                ];
            }

            // Add depletions
            $depletions = $livestock->livestockDepletion()->orderBy('created_at')->get();
            foreach ($depletions as $depletion) {
                $auditTrail[] = [
                    'date' => $depletion->created_at,
                    'action' => 'depletion_created',
                    'description' => "Depletion {$depletion->jenis}",
                    'user' => $depletion->created_by,
                    'data' => [
                        'type' => $depletion->jenis,
                        'quantity' => $depletion->jumlah
                    ]
                ];
            }

            // Sort by date
            usort($auditTrail, function ($a, $b) {
                return $a['date']->timestamp - $b['date']->timestamp;
            });

            return $auditTrail;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get tracking summary for multiple livestock
     * 
     * @param array $livestockIds
     * @return array
     */
    public function getTrackingSummaryForMultiple(array $livestockIds): array
    {
        $summaries = [];

        foreach ($livestockIds as $livestockId) {
            $livestock = Livestock::find($livestockId);
            if ($livestock) {
                $summaries[] = [
                    'livestock_id' => $livestockId,
                    'name' => $livestock->name,
                    'origin_chain' => $this->getOriginChain($livestock),
                    'performance_metrics' => $this->getPerformanceMetrics($livestock),
                    'current_status' => $this->getCurrentStatus($livestock)
                ];
            }
        }

        return $summaries;
    }

    /**
     * Export tracking data for reporting
     * 
     * @param string $livestockId
     * @param string $format
     * @return array
     */
    public function exportTrackingData(string $livestockId, string $format = 'json'): array
    {
        $trackingData = $this->getCompleteTrackingInfo($livestockId);

        if ($format === 'csv') {
            return $this->formatForCsv($trackingData);
        }

        return $trackingData;
    }

    /**
     * Format tracking data for CSV export
     * 
     * @param array $trackingData
     * @return array
     */
    private function formatForCsv(array $trackingData): array
    {
        // Implementation for CSV formatting
        return $trackingData;
    }

    /**
     * Get tracking statistics for dashboard
     * 
     * @param string $companyId
     * @return array
     */
    public function getTrackingStatistics(string $companyId): array
    {
        $livestock = Livestock::where('company_id', $companyId)->get();

        $totalBatches = 0;
        $activeBatches = 0;

        foreach ($livestock as $l) {
            $totalBatches += $l->batches()->count();
            $activeBatches += $l->getActiveBatchesCount();
        }

        $stats = [
            'total_livestock' => $livestock->count(),
            'from_purchase' => $livestock->filter(fn($l) => $l->isFromPurchase())->count(),
            'from_mutation' => $livestock->filter(fn($l) => $l->isFromMutation())->count(),
            'mixed_source' => $livestock->filter(fn($l) => $l->getSourceType() === 'mixed')->count(),
            'average_survival_rate' => $livestock->avg(fn($l) => $l->getOverallAvailabilityPercentage()),
            'total_batches' => $totalBatches,
            'active_batches' => $activeBatches
        ];

        return $stats;
    }
}

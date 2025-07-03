<?php

namespace App\Services\Report;

use App\Models\Livestock;
use App\Models\LivestockDepletion;
use App\Config\LivestockDepletionConfig;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Service for handling livestock depletion report logic
 * Integrates with LivestockDepletionConfig for consistent type handling
 * 
 * @version 1.0
 * @since 2025-01-25
 */
class LivestockDepletionReportService
{
    /**
     * Process livestock data per depletion record for detail mode
     * 
     * @param Livestock $livestock
     * @param Carbon $tanggal
     * @param array $distinctFeedNames
     * @param array &$totals
     * @param mixed $allFeedUsageDetails
     * @param LivestockDepletion|null $depletionRecord
     * @return array
     */
    public function processLivestockDepletionDetails(
        Livestock $livestock,
        Carbon $tanggal,
        array $distinctFeedNames,
        array &$totals,
        $allFeedUsageDetails = null,
        ?LivestockDepletion $depletionRecord = null
    ): array {
        // Get recording data
        $recordingData = $livestock->recordings()
            ->whereDate('tanggal', $tanggal)
            ->first();

        // Calculate basic livestock info
        $age = Carbon::parse($livestock->start_date)->diffInDays($tanggal);
        $stockAwal = (int) $livestock->initial_quantity;

        // Process depletion data using config
        $depletionData = $this->processDepletionRecord($depletionRecord);

        // Calculate cumulative depletion
        $totalDepletionCumulative = $this->getTotalDepletionCumulative($livestock, $tanggal);

        // Get sales data
        $salesData = $this->getSalesData($livestock, $tanggal);

        // Process feed usage
        $feedData = $this->processFeedUsage($livestock, $tanggal, $distinctFeedNames, $allFeedUsageDetails);

        // Calculate stock
        $stockAkhir = $stockAwal - $totalDepletionCumulative - $salesData['cumulative_sales'];

        // Update totals
        $this->updateTotals($totals, $stockAwal, $depletionData, $salesData, $feedData, $stockAkhir, $recordingData);

        // Create batch name with depletion info
        $batchName = $this->createBatchName($livestock, $depletionRecord);

        Log::debug('Processed livestock depletion details', [
            'livestock_id' => $livestock->id,
            'livestock_name' => $livestock->name,
            'depletion_record_id' => $depletionRecord?->id,
            'depletion_type' => $depletionData['type'],
            'depletion_amount' => $depletionData['amount'],
            'batch_name' => $batchName
        ]);

        return [
            'livestock_id' => $livestock->id,
            'livestock_name' => $batchName,
            'umur' => $age,
            'stock_awal' => $stockAwal,
            'mati' => $depletionData['mortality'],
            'afkir' => $depletionData['culling'],
            'total_deplesi' => $depletionData['amount'],
            'deplesi_percentage' => $stockAwal > 0 ? round(($depletionData['amount'] / $stockAwal) * 100, 2) : 0,
            'jual_ekor' => $salesData['daily_sales_count'],
            'jual_kg' => $salesData['daily_sales_weight'],
            'stock_akhir' => $stockAkhir,
            'berat_semalam' => $recordingData->berat_semalam ?? 0,
            'berat_hari_ini' => $recordingData->berat_hari_ini ?? 0,
            'kenaikan_berat' => $recordingData->kenaikan_berat ?? 0,
            'pakan_harian' => $feedData['feed_by_type'],
            'pakan_total' => $feedData['cumulative_feed'],
            'pakan_jenis' => $distinctFeedNames,
            'depletion_record_id' => $depletionRecord?->id,
            'depletion_type' => $depletionData['type'],
            'depletion_amount' => $depletionData['amount'],
            'depletion_category' => $depletionData['category']
        ];
    }

    /**
     * Get all depletion records for livestock on specific date
     * 
     * @param Livestock $livestock
     * @param Carbon $tanggal
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDepletionRecords(Livestock $livestock, Carbon $tanggal)
    {
        return LivestockDepletion::where('livestock_id', $livestock->id)
            ->where('tanggal', $tanggal->format('Y-m-d'))
            ->get();
    }

    /**
     * Process depletion record using LivestockDepletionConfig
     * 
     * @param LivestockDepletion|null $depletionRecord
     * @return array
     */
    private function processDepletionRecord(?LivestockDepletion $depletionRecord): array
    {
        if (!$depletionRecord) {
            return [
                'mortality' => 0,
                'culling' => 0,
                'amount' => 0,
                'type' => '',
                'category' => 'other'
            ];
        }

        $amount = (int) $depletionRecord->jumlah;
        $rawType = $depletionRecord->jenis;

        // Normalize type using config
        $standardType = LivestockDepletionConfig::normalize($rawType);
        $category = LivestockDepletionConfig::getCategory($standardType);

        // Determine mortality and culling based on standard type
        $mortality = 0;
        $culling = 0;

        switch ($standardType) {
            case LivestockDepletionConfig::TYPE_MORTALITY:
                $mortality = $amount;
                break;
            case LivestockDepletionConfig::TYPE_CULLING:
                $culling = $amount;
                break;
            default:
                // For other types (sales, mutation, transfer), treat as general depletion
                break;
        }

        return [
            'mortality' => $mortality,
            'culling' => $culling,
            'amount' => $amount,
            'type' => $rawType, // Keep original for display
            'standard_type' => $standardType,
            'category' => $category
        ];
    }

    /**
     * Get total cumulative depletion up to date
     * 
     * @param Livestock $livestock
     * @param Carbon $tanggal
     * @return int
     */
    private function getTotalDepletionCumulative(Livestock $livestock, Carbon $tanggal): int
    {
        return (int) LivestockDepletion::where('livestock_id', $livestock->id)
            ->where('tanggal', '<=', $tanggal->format('Y-m-d'))
            ->sum('jumlah');
    }

    /**
     * Get sales data for livestock on specific date
     * 
     * @param Livestock $livestock
     * @param Carbon $tanggal
     * @return array
     */
    private function getSalesData(Livestock $livestock, Carbon $tanggal): array
    {
        $sales = $livestock->salesItems()
            ->whereHas('livestockSale', function ($query) use ($tanggal) {
                $query->whereDate('tanggal', $tanggal);
            })
            ->first();

        $cumulativeSales = (int) $livestock->salesItems()
            ->whereHas('livestockSale', function ($query) use ($tanggal) {
                $query->whereDate('tanggal', '<=', $tanggal);
            })
            ->sum('quantity');

        return [
            'daily_sales_count' => (int) ($sales->quantity ?? 0),
            'daily_sales_weight' => (float) ($sales->total_berat ?? 0),
            'cumulative_sales' => $cumulativeSales
        ];
    }

    /**
     * Process feed usage data
     * 
     * @param Livestock $livestock
     * @param Carbon $tanggal
     * @param array $distinctFeedNames
     * @param mixed $allFeedUsageDetails
     * @return array
     */
    private function processFeedUsage(Livestock $livestock, Carbon $tanggal, array $distinctFeedNames, $allFeedUsageDetails): array
    {
        // Get feed usage details for this livestock
        $feedUsageDetails = $allFeedUsageDetails
            ? $allFeedUsageDetails->filter(function ($detail) use ($livestock) {
                return $detail->feedUsage && $detail->feedUsage->livestock_id === $livestock->id;
            })
            : collect();

        // Fallback: if no data for livestock, use all feed usage for farm on this date
        if ($feedUsageDetails->isEmpty() && $allFeedUsageDetails) {
            $feedUsageDetails = $allFeedUsageDetails;
        }

        $feedByType = [];
        foreach ($distinctFeedNames as $feedName) {
            $feedByType[$feedName] = $feedUsageDetails->where('feed.name', $feedName)->sum('quantity_taken');
        }

        // Get cumulative feed usage
        $cumulativeFeed = (float) $livestock->feedUsageDetails()
            ->whereHas('feedUsage', function ($query) use ($tanggal) {
                $query->whereDate('usage_date', '<=', $tanggal);
            })
            ->sum('quantity_taken');

        return [
            'feed_by_type' => $feedByType,
            'cumulative_feed' => $cumulativeFeed
        ];
    }

    /**
     * Update totals array
     * 
     * @param array &$totals
     * @param int $stockAwal
     * @param array $depletionData
     * @param array $salesData
     * @param array $feedData
     * @param int $stockAkhir
     * @param mixed $recordingData
     */
    private function updateTotals(array &$totals, int $stockAwal, array $depletionData, array $salesData, array $feedData, int $stockAkhir, $recordingData): void
    {
        $totals['stock_awal'] += $stockAwal;
        $totals['mati'] += $depletionData['mortality'];
        $totals['afkir'] += $depletionData['culling'];
        $totals['total_deplesi'] += $depletionData['amount'];
        $totals['jual_ekor'] += $salesData['daily_sales_count'];
        $totals['jual_kg'] += $salesData['daily_sales_weight'];
        $totals['stock_akhir'] += $stockAkhir;
        $totals['berat_semalam'] += (float) ($recordingData->berat_semalam ?? 0);
        $totals['berat_hari_ini'] += (float) ($recordingData->berat_hari_ini ?? 0);
        $totals['kenaikan_berat'] += (float) ($recordingData->kenaikan_berat ?? 0);
        $totals['pakan_total'] += $feedData['cumulative_feed'];
        $totals['tangkap_ekor'] += $salesData['daily_sales_count'];
        $totals['tangkap_kg'] += $salesData['daily_sales_weight'];

        foreach ($feedData['feed_by_type'] as $jenis => $jumlah) {
            $totals['pakan_harian'][$jenis] = ($totals['pakan_harian'][$jenis] ?? 0) + (float) $jumlah;
        }
    }

    /**
     * Create informative batch name with depletion info
     * 
     * @param Livestock $livestock
     * @param LivestockDepletion|null $depletionRecord
     * @return string
     */
    private function createBatchName(Livestock $livestock, ?LivestockDepletion $depletionRecord): string
    {
        $batchName = $livestock->name;

        if ($depletionRecord) {
            $displayName = LivestockDepletionConfig::getDisplayName($depletionRecord->jenis, true);
            $batchName .= ' [' . $displayName . ': ' . $depletionRecord->jumlah . ' ekor]';
        }

        return $batchName;
    }

    /**
     * Get service configuration summary for debugging
     * 
     * @return array
     */
    public function getServiceInfo(): array
    {
        return [
            'service' => 'LivestockDepletionReportService',
            'version' => '1.0',
            'config_integration' => 'LivestockDepletionConfig',
            'features' => [
                'depletion_type_normalization',
                'config_based_categorization',
                'modular_processing',
                'comprehensive_logging'
            ],
            'created_at' => '2025-01-25'
        ];
    }
}

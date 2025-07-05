<?php

namespace App\Services\Report;

use App\Models\Livestock;
use App\Models\Recording;
use App\Models\FeedUsageDetail;
use App\Services\Report\ReportCalculationService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Service untuk menangani agregasi data dalam laporan
 * Memisahkan logic aggregation dari controller untuk reusability
 */
class ReportAggregationService
{
    protected $calculationService;

    public function __construct(ReportCalculationService $calculationService)
    {
        $this->calculationService = $calculationService;
    }

    /**
     * Aggregate livestock data by coop
     * 
     * @param Collection $livestocks Collection of livestock
     * @param Carbon $tanggal Report date
     * @param array $distinctFeedNames Available feed names
     * @param array $totals Reference to totals array
     * @param Collection|null $allFeedUsageDetails Pre-loaded feed usage details
     * @return array Aggregated data by coop
     */
    public function aggregateByCoops(Collection $livestocks, Carbon $tanggal, array $distinctFeedNames, array &$totals, Collection $allFeedUsageDetails = null): array
    {
        $livestocksByCoopNama = $livestocks->groupBy(function ($livestock) {
            return $livestock->coop->name;
        });

        $aggregatedResults = [];

        foreach ($livestocksByCoopNama as $coopNama => $coopLivestocks) {
            $aggregatedData = $this->processCoopAggregation(
                $coopLivestocks,
                $tanggal,
                $distinctFeedNames,
                $totals,
                $allFeedUsageDetails
            );

            if ($aggregatedData !== null) {
                $aggregatedResults[$coopNama] = $aggregatedData;
            }
        }

        Log::info('Livestock aggregated by coops', [
            'coop_count' => count($aggregatedResults),
            'total_livestock' => $livestocks->count(),
            'date' => $tanggal->format('Y-m-d')
        ]);

        return $aggregatedResults;
    }

    /**
     * Process aggregation for a single coop
     * 
     * @param Collection $coopLivestocks Livestock in the coop
     * @param Carbon $tanggal Report date
     * @param array $distinctFeedNames Available feed names
     * @param array $totals Reference to totals array
     * @param Collection|null $allFeedUsageDetails Pre-loaded feed usage details
     * @return array|null Aggregated data or null if no valid data
     */
    public function processCoopAggregation(Collection $coopLivestocks, Carbon $tanggal, array $distinctFeedNames, array &$totals, Collection $allFeedUsageDetails = null): ?array
    {
        $aggregatedData = [
            'umur' => 0,
            'stock_awal' => 0,
            'mati' => 0,
            'afkir' => 0,
            'total_deplesi' => 0,
            'jual_ekor' => 0,
            'jual_kg' => 0,
            'stock_akhir' => 0,
            'berat_semalam' => 0,
            'berat_hari_ini' => 0,
            'kenaikan_berat' => 0,
            'pakan_harian' => [],
            'pakan_total' => 0,
            'livestock_count' => 0
        ];

        $processedCount = 0;
        $batchDataCollection = [];

        foreach ($coopLivestocks as $livestock) {
            // Check if livestock has recording data for the date
            if (!$this->hasRecordingData($livestock, $tanggal)) {
                continue;
            }

            $batchData = $this->processIndividualLivestock($livestock, $tanggal, $distinctFeedNames, $totals, $allFeedUsageDetails);

            if ($batchData === null) {
                continue;
            }

            $processedCount++;
            $batchDataCollection[] = $batchData;
        }

        if (empty($batchDataCollection)) {
            Log::debug('No valid livestock data found for coop aggregation', [
                'coop_livestock_count' => $coopLivestocks->count(),
                'date' => $tanggal->format('Y-m-d')
            ]);
            return null;
        }

        $aggregatedData['livestock_count'] = $processedCount;

        // Aggregate all batch data
        foreach ($batchDataCollection as $batchData) {
            $aggregatedData['umur'] = $batchData['umur']; // Same for all in coop
            $aggregatedData['stock_awal'] += $batchData['stock_awal'];
            $aggregatedData['mati'] += $batchData['mati'];
            $aggregatedData['afkir'] += $batchData['afkir'];
            $aggregatedData['total_deplesi'] += $batchData['total_deplesi'];
            $aggregatedData['jual_ekor'] += $batchData['jual_ekor'];
            $aggregatedData['jual_kg'] += $batchData['jual_kg'];
            $aggregatedData['stock_akhir'] += $batchData['stock_akhir'];
            $aggregatedData['berat_semalam'] += $batchData['berat_semalam'];
            $aggregatedData['berat_hari_ini'] += $batchData['berat_hari_ini'];
            $aggregatedData['kenaikan_berat'] += $batchData['kenaikan_berat'];
            $aggregatedData['pakan_total'] += $batchData['pakan_total'];

            // Aggregate feed usage by type
            foreach ($batchData['pakan_harian'] as $jenis => $jumlah) {
                $aggregatedData['pakan_harian'][$jenis] = ($aggregatedData['pakan_harian'][$jenis] ?? 0) + (float) $jumlah;
            }
        }

        // Ensure all feed types are represented
        foreach ($distinctFeedNames as $feedName) {
            if (!isset($aggregatedData['pakan_harian'][$feedName])) {
                $aggregatedData['pakan_harian'][$feedName] = 0;
            }
        }

        // Calculate averages for weight-related metrics
        if ($aggregatedData['livestock_count'] > 0) {
            $aggregatedData['berat_semalam'] = $aggregatedData['berat_semalam'] / $aggregatedData['livestock_count'];
            $aggregatedData['berat_hari_ini'] = $aggregatedData['berat_hari_ini'] / $aggregatedData['livestock_count'];
            $aggregatedData['kenaikan_berat'] = $aggregatedData['kenaikan_berat'] / $aggregatedData['livestock_count'];
        }

        // Calculate depletion percentage
        $aggregatedData['deplesi_percentage'] = $this->calculationService->calculateDepletionPercentage(
            $aggregatedData['total_deplesi'],
            $aggregatedData['stock_awal']
        );

        Log::debug('Coop aggregation completed', [
            'livestock_count' => $aggregatedData['livestock_count'],
            'stock_awal' => $aggregatedData['stock_awal'],
            'stock_akhir' => $aggregatedData['stock_akhir'],
            'total_deplesi' => $aggregatedData['total_deplesi'],
            'feed_types' => array_keys($aggregatedData['pakan_harian'])
        ]);

        return $aggregatedData;
    }

    /**
     * Process individual livestock data
     * 
     * @param Livestock $livestock The livestock model
     * @param Carbon $tanggal Report date
     * @param array $distinctFeedNames Available feed names
     * @param array $totals Reference to totals array
     * @param Collection|null $allFeedUsageDetails Pre-loaded feed usage details
     * @return array|null Processed livestock data or null if invalid
     */
    public function processIndividualLivestock(Livestock $livestock, Carbon $tanggal, array $distinctFeedNames, array &$totals, Collection $allFeedUsageDetails = null): ?array
    {
        // Get recording data for validation
        $recordingData = Recording::where('livestock_id', $livestock->id)
            ->whereDate('tanggal', $tanggal)
            ->first();

        if (!$recordingData) {
            Log::debug('Skipping livestock - no Recording data', [
                'livestock_id' => $livestock->id,
                'date' => $tanggal->format('Y-m-d')
            ]);
            return null;
        }

        // Calculate basic metrics
        $age = $this->calculationService->calculateAgeInDays($livestock->start_date, $tanggal);
        $stockAwal = (int) $livestock->initial_quantity;

        // Get depletion data
        $depletionData = $this->getDepletionData($livestock, $tanggal);

        // Get sales data
        $salesData = $this->getSalesData($livestock, $tanggal);

        // Get feed usage data
        $feedUsageData = $this->getFeedUsageData($livestock, $tanggal, $distinctFeedNames, $allFeedUsageDetails);

        // Calculate stock after depletion and sales
        $stockAkhir = $stockAwal - $depletionData['total_depletion'] - $salesData['total_sales_cumulative'];

        // Get weight data
        $beratSemalam = (float) ($recordingData->berat_semalam ?? 0);
        $beratHariIni = (float) ($recordingData->berat_hari_ini ?? 0);
        $kenaikanberat = (float) ($recordingData->kenaikan_berat ?? 0);

        // Update totals
        $this->updateTotals($totals, [
            'stock_awal' => $stockAwal,
            'mati' => $depletionData['mortality'],
            'afkir' => $depletionData['culling'],
            'total_deplesi' => $depletionData['total_depletion'],
            'jual_ekor' => $salesData['daily_sales_count'],
            'jual_kg' => $salesData['daily_sales_weight'],
            'stock_akhir' => $stockAkhir,
            'berat_semalam' => $beratSemalam,
            'berat_hari_ini' => $beratHariIni,
            'kenaikan_berat' => $kenaikanberat,
            'pakan_total' => $feedUsageData['total_pakan_usage'],
            'pakan_harian' => $feedUsageData['pakan_harian_per_jenis']
        ]);

        return [
            'livestock_id' => $livestock->id,
            'livestock_name' => $livestock->name,
            'umur' => $age,
            'stock_awal' => $stockAwal,
            'mati' => $depletionData['mortality'],
            'afkir' => $depletionData['culling'],
            'total_deplesi' => $depletionData['total_depletion'],
            'deplesi_percentage' => $this->calculationService->calculateDepletionPercentage($depletionData['total_depletion'], $stockAwal),
            'jual_ekor' => $salesData['daily_sales_count'],
            'jual_kg' => $salesData['daily_sales_weight'],
            'stock_akhir' => $stockAkhir,
            'berat_semalam' => $beratSemalam,
            'berat_hari_ini' => $beratHariIni,
            'kenaikan_berat' => $kenaikanberat,
            'pakan_harian' => $feedUsageData['pakan_harian_per_jenis'],
            'pakan_total' => $feedUsageData['total_pakan_usage'],
            'pakan_jenis' => $distinctFeedNames
        ];
    }

    /**
     * Check if livestock has recording data for the date
     * 
     * @param Livestock $livestock The livestock model
     * @param Carbon $tanggal Report date
     * @return bool
     */
    private function hasRecordingData(Livestock $livestock, Carbon $tanggal): bool
    {
        return Recording::where('livestock_id', $livestock->id)
            ->whereDate('tanggal', $tanggal)
            ->exists();
    }

    /**
     * Get depletion data for livestock on specific date
     * 
     * @param Livestock $livestock The livestock model
     * @param Carbon $tanggal Report date
     * @return array Depletion data
     */
    private function getDepletionData(Livestock $livestock, Carbon $tanggal): array
    {
        // This would use the existing depletion service
        // For now, keeping the basic logic
        $mortality = 0; // Calculate using LivestockDepletionConfig
        $culling = 0;   // Calculate using LivestockDepletionConfig
        $totalDepletion = $mortality + $culling;

        return [
            'mortality' => $mortality,
            'culling' => $culling,
            'total_depletion' => $totalDepletion
        ];
    }

    /**
     * Get sales data for livestock on specific date
     * 
     * @param Livestock $livestock The livestock model
     * @param Carbon $tanggal Report date
     * @return array Sales data
     */
    private function getSalesData(Livestock $livestock, Carbon $tanggal): array
    {
        // Basic sales data calculation
        $dailySalesCount = 0;
        $dailySalesWeight = 0;
        $totalSalesCumulative = 0;

        return [
            'daily_sales_count' => $dailySalesCount,
            'daily_sales_weight' => $dailySalesWeight,
            'total_sales_cumulative' => $totalSalesCumulative
        ];
    }

    /**
     * Get feed usage data for livestock on specific date
     * 
     * @param Livestock $livestock The livestock model
     * @param Carbon $tanggal Report date
     * @param array $distinctFeedNames Available feed names
     * @param Collection|null $allFeedUsageDetails Pre-loaded feed usage details
     * @return array Feed usage data
     */
    private function getFeedUsageData(Livestock $livestock, Carbon $tanggal, array $distinctFeedNames, Collection $allFeedUsageDetails = null): array
    {
        $pakanHarianPerJenis = [];
        $totalPakanHarian = 0;
        $totalPakanUsage = 0;

        // Initialize all feed types with zero
        foreach ($distinctFeedNames as $feedName) {
            $pakanHarianPerJenis[$feedName] = 0;
        }

        // Calculate feed usage (basic implementation)
        // This would be enhanced with actual feed usage calculations

        return [
            'pakan_harian_per_jenis' => $pakanHarianPerJenis,
            'total_pakan_harian' => $totalPakanHarian,
            'total_pakan_usage' => $totalPakanUsage
        ];
    }

    /**
     * Update totals array with new data
     * 
     * @param array $totals Reference to totals array
     * @param array $data Data to add to totals
     */
    private function updateTotals(array &$totals, array $data): void
    {
        $totals['stock_awal'] += $data['stock_awal'];
        $totals['mati'] += $data['mati'];
        $totals['afkir'] += $data['afkir'];
        $totals['total_deplesi'] += $data['total_deplesi'];
        $totals['jual_ekor'] += $data['jual_ekor'];
        $totals['jual_kg'] += $data['jual_kg'];
        $totals['stock_akhir'] += $data['stock_akhir'];
        $totals['berat_semalam'] += $data['berat_semalam'];
        $totals['berat_hari_ini'] += $data['berat_hari_ini'];
        $totals['kenaikan_berat'] += $data['kenaikan_berat'];
        $totals['pakan_total'] += $data['pakan_total'];

        // Update legacy fields
        $totals['tangkap_ekor'] += $data['jual_ekor'];
        $totals['tangkap_kg'] += $data['jual_kg'];

        // Update feed usage by type
        foreach ($data['pakan_harian'] as $jenis => $jumlah) {
            $totals['pakan_harian'][$jenis] = ($totals['pakan_harian'][$jenis] ?? 0) + (float) $jumlah;
        }
    }

    /**
     * Finalize totals calculations
     * 
     * @param array $totals Reference to totals array
     * @param array $distinctFeedNames Available feed names
     */
    public function finalizeTotals(array &$totals, array $distinctFeedNames): void
    {
        // Ensure all feed names are represented
        foreach ($distinctFeedNames as $feedName) {
            if (!isset($totals['pakan_harian'][$feedName])) {
                $totals['pakan_harian'][$feedName] = 0;
            }
        }

        // Calculate final percentages
        $totals['deplesi_percentage'] = $this->calculationService->calculateDepletionPercentage(
            $totals['total_deplesi'],
            $totals['stock_awal']
        );

        $totals['survival_rate'] = $this->calculationService->calculateSurvivalRate(
            $totals['stock_akhir'],
            $totals['stock_awal']
        );

        Log::info('Totals finalized', [
            'stock_awal' => $totals['stock_awal'],
            'stock_akhir' => $totals['stock_akhir'],
            'total_deplesi' => $totals['total_deplesi'],
            'deplesi_percentage' => $totals['deplesi_percentage'],
            'survival_rate' => $totals['survival_rate'],
            'feed_types_count' => count($distinctFeedNames)
        ]);
    }
}

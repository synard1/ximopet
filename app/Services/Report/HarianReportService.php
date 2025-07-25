<?php

namespace App\Services\Report;

use App\Models\CurrentLivestock;
use App\Models\Farm;
use App\Models\Livestock;
use App\Models\Recording;
use App\Models\FeedUsageDetail;
use App\Models\LivestockSalesItem;
use App\Models\LivestockDepletion;
use App\Config\LivestockDepletionConfig;
use App\Models\SupplyUsageDetail;
use App\Models\SupplyUsage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class HarianReportService
{
    protected $depletionReportService;
    protected $unitConversionService;

    public function __construct(LivestockDepletionReportService $depletionReportService, $unitConversionService = null)
    {
        $this->depletionReportService = $depletionReportService;
        $this->unitConversionService = $unitConversionService;
    }

    /**
     * Get comprehensive daily report data for a farm and date
     * Extracted from ReportsController::getHarianReportData()
     * 
     * @param Farm $farm
     * @param Carbon $tanggal
     * @param string $reportType ('detail' or 'simple')
     * @return array
     */
    public function getHarianReportData(Farm $farm, Carbon $tanggal, string $reportType, $livestockId = null): array
    {
        Log::info('Generating Harian Report Data', [
            'farm_id' => $farm->id,
            'farm_name' => $farm->name,
            'tanggal' => $tanggal->format('Y-m-d'),
            'report_type' => $reportType,
            'user_id' => Auth::id(),
            'filter_livestock_id' => $livestockId
        ]);

        // Get all active livestock for the date
        $livestocks = $this->getActiveLivestock($farm, $tanggal);
        if ($livestockId) {
            $livestocks = $livestocks->where('id', $livestockId);
        }

        // Optimize feed names query
        $distinctFeedNames = $this->getDistinctFeedNames($farm, $tanggal);

        // Get all feed usage details once for efficiency
        $allFeedUsageDetails = $this->getAllFeedUsageDetails($farm, $tanggal);

        // Get all supply usage details once for efficiency
        $allSupplyUsageDetails = $this->getAllSupplyUsageDetails($farm, $tanggal);

        // dd($allSupplyUsageDetails);

        // Check if there are any recordings for this date
        $hasRecordings = $this->hasRecordingsForDate($livestocks, $tanggal);

        if (!$hasRecordings) {
            Log::warning('No Recording data found for export', [
                'farm_id' => $farm->id,
                'farm_name' => $farm->name,
                'tanggal' => $tanggal->format('Y-m-d'),
                'report_type' => $reportType
            ]);
            return [
                'farm' => $farm,
                'tanggal' => $tanggal,
                'recordings' => [],
                'totals' => $this->getEmptyTotals($distinctFeedNames),
                'distinctFeedNames' => $distinctFeedNames,
                'reportType' => $reportType,
                'supplyUsage' => [
                    'by_type' => [],
                    'total_cost' => 0,
                    'details' => []
                ]
            ];
        }

        // Initialize totals
        $totals = $this->initializeTotals();
        $supplyUsageTotals = [
            'by_type' => [],
            'total_cost' => 0,
            'details' => []
        ];

        // Process data based on report type
        $recordings = $this->processRecordings($livestocks, $tanggal, $reportType, $distinctFeedNames, $totals, $allFeedUsageDetails, $allSupplyUsageDetails, $supplyUsageTotals);

        // Finalize totals and calculations
        $this->finalizeTotals($totals, $distinctFeedNames);
        $this->finalizeSupplyUsageTotals($supplyUsageTotals);

        // --- AGREGASI SUPPLY USAGE MASSAL (tanpa livestock_id) ---
        $massalSupplyDetails = $allSupplyUsageDetails
            ? $allSupplyUsageDetails->filter(function ($detail) {
                return empty($detail->supplyUsage->livestock_id);
            })
            : collect();
        foreach ($massalSupplyDetails as $detail) {
            $supplyName = $detail->supply->name ?? 'Unknown';
            $unitName = $detail->unit->name ?? 'pcs';
            $quantity = (float) $detail->quantity_taken;
            $unitCost = $detail->price_per_unit ?? ($detail->supply->price ?? 0);
            $cost = $quantity * $unitCost;
            if (!isset($supplyUsageTotals['by_type'][$supplyName])) {
                $supplyUsageTotals['by_type'][$supplyName] = [
                    'quantity' => 0,
                    'cost' => 0,
                    'unit' => $unitName,
                    'unit_cost' => $unitCost
                ];
            }
            $supplyUsageTotals['by_type'][$supplyName]['quantity'] += $quantity;
            $supplyUsageTotals['by_type'][$supplyName]['cost'] += $cost;
        }
        $this->finalizeSupplyUsageTotals($supplyUsageTotals);

        Log::info('Harian Report Data generated successfully', [
            'farm_id' => $farm->id,
            'recordings_count' => count($recordings),
            'total_stock_awal' => $totals['stock_awal'],
            'total_stock_akhir' => $totals['stock_akhir'],
            'total_deplesi' => $totals['total_deplesi'],
            'deplesi_percentage' => $totals['deplesi_percentage'],
            'supply_total_cost' => $supplyUsageTotals['total_cost']
        ]);

        return [
            'farm' => $farm,
            'tanggal' => $tanggal,
            'recordings' => $recordings,
            'totals' => $totals,
            'distinctFeedNames' => $distinctFeedNames,
            'reportType' => $reportType,
            'supplyUsage' => $supplyUsageTotals
        ];
    }

    /**
     * Get active livestock for farm and date
     */
    private function getActiveLivestock(Farm $farm, Carbon $tanggal)
    {
        return Livestock::where('farm_id', $farm->id)
            ->whereDate('start_date', '<=', $tanggal)
            ->with(['coop'])
            ->get();
    }

    /**
     * Get distinct feed names for optimization
     */
    private function getDistinctFeedNames(Farm $farm, Carbon $tanggal): array
    {
        $distinctFeedNames = FeedUsageDetail::whereHas('feedUsage', function ($query) use ($farm, $tanggal) {
            $query->whereHas('livestock', function ($q) use ($farm) {
                $q->where('farm_id', $farm->id);
            })->whereDate('usage_date', $tanggal);
        })
            ->with('feed')
            ->get()
            ->pluck('feed.name')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        // Fallback: get all feed names for the farm if none found for specific date
        if (empty($distinctFeedNames)) {
            $distinctFeedNames = FeedUsageDetail::whereHas('feedUsage', function ($query) use ($farm) {
                $query->whereHas('livestock', function ($q) use ($farm) {
                    $q->where('farm_id', $farm->id);
                });
            })
                ->with('feed')
                ->get()
                ->pluck('feed.name')
                ->filter()
                ->unique()
                ->values()
                ->toArray();
        }

        Log::debug('Distinct feed names retrieved', [
            'farm_id' => $farm->id,
            'tanggal' => $tanggal->format('Y-m-d'),
            'feed_names' => $distinctFeedNames,
            'count' => count($distinctFeedNames)
        ]);

        return $distinctFeedNames;
    }

    /**
     * Get all feed usage details for efficiency
     */
    private function getAllFeedUsageDetails(Farm $farm, Carbon $tanggal)
    {
        return FeedUsageDetail::whereHas('feedUsage', function ($query) use ($farm, $tanggal) {
            $query->whereHas('livestock', function ($q) use ($farm) {
                $q->where('farm_id', $farm->id);
            })->whereDate('usage_date', $tanggal);
        })->with(['feed', 'feedUsage.livestock'])->get();
    }

    /**
     * Get all supply usage details for efficiency
     */
    private function getAllSupplyUsageDetails(Farm $farm, Carbon $tanggal)
    {
        // 1. SupplyUsageDetail with livestock_id (batch-specific usage)
        $detailsWithLivestock = SupplyUsageDetail::whereHas('supplyUsage', function ($query) use ($farm, $tanggal) {
            $query->whereHas('livestock', function ($q) use ($farm) {
                $q->where('farm_id', $farm->id);
            })
                ->whereDate('usage_date', $tanggal)
                ->whereIn('status', ['pending', 'in_process', 'completed']);
        })
            ->with(['supply', 'unit', 'supplyUsage.livestock', 'supplyStock.supplyPurchase'])
            ->get();

        // 2. SupplyUsageDetail with SupplyUsage that has farm_id (umum/OVK massal, no livestock_id)
        $detailsWithoutLivestock = SupplyUsageDetail::whereHas('supplyUsage', function ($query) use ($farm, $tanggal) {
            $query->whereNull('livestock_id')
                ->where('farm_id', $farm->id)
                ->whereDate('usage_date', $tanggal)
                ->whereIn('status', ['pending', 'in_process', 'completed']);
        })
            ->with(['supply', 'unit', 'supplyUsage', 'supplyStock.supplyPurchase'])
            ->get();

        $all = $detailsWithLivestock->merge($detailsWithoutLivestock);

        Log::info('getAllSupplyUsageDetails result', [
            'with_livestock_count' => $detailsWithLivestock->count(),
            'without_livestock_count' => $detailsWithoutLivestock->count(),
            'total' => $all->count(),
            'farm_id' => $farm->id,
            'tanggal' => $tanggal->format('Y-m-d')
        ]);

        return $all;
    }

    /**
     * Check if there are recordings for the date
     */
    private function hasRecordingsForDate($livestocks, Carbon $tanggal): bool
    {
        return Recording::whereIn('livestock_id', $livestocks->pluck('id')->toArray())
            ->whereDate('tanggal', $tanggal)
            ->exists();
    }

    /**
     * Initialize totals array
     */
    private function initializeTotals(): array
    {
        return [
            'stock_awal' => 0,
            'mati' => 0,
            'afkir' => 0,
            'total_deplesi' => 0,
            'deplesi_percentage' => 0,
            'jual_ekor' => 0,
            'jual_kg' => 0,
            'stock_akhir' => 0,
            'berat_semalam' => 0,
            'berat_hari_ini' => 0,
            'kenaikan_berat' => 0,
            'pakan_harian' => [],
            'pakan_total' => 0,
            'tangkap_ekor' => 0,  // legacy field for compatibility
            'tangkap_kg' => 0,    // legacy field for compatibility
            'survival_rate' => 0  // survival rate percentage
        ];
    }

    /**
     * Get empty totals with feed names initialized
     */
    private function getEmptyTotals(array $distinctFeedNames): array
    {
        $totals = $this->initializeTotals();
        foreach ($distinctFeedNames as $feedName) {
            $totals['pakan_harian'][$feedName] = 0;
        }
        return $totals;
    }

    /**
     * Process recordings based on report type
     */
    private function processRecordings($livestocks, Carbon $tanggal, string $reportType, array $distinctFeedNames, array &$totals, $allFeedUsageDetails, $allSupplyUsageDetails, array &$supplyUsageTotals): array
    {
        $recordings = [];

        if ($reportType === 'detail') {
            $recordings = $this->processDetailMode($livestocks, $tanggal, $distinctFeedNames, $totals, $allFeedUsageDetails, $allSupplyUsageDetails, $supplyUsageTotals);
        } else {
            $recordings = $this->processSimpleMode($livestocks, $tanggal, $distinctFeedNames, $totals, $allFeedUsageDetails, $allSupplyUsageDetails, $supplyUsageTotals);
        }

        return $recordings;
    }

    /**
     * Process detail mode - show individual depletion records
     */
    private function processDetailMode($livestocks, Carbon $tanggal, array $distinctFeedNames, array &$totals, $allFeedUsageDetails, $allSupplyUsageDetails, array &$supplyUsageTotals): array
    {
        $recordings = [];
        $livestocksByCoopNama = $livestocks->groupBy(function ($livestock) {
            return optional($livestock->coop)->name ?? 'Tanpa Kandang';
        });

        Log::debug('Detail mode processing', [
            'coop_groups' => $livestocksByCoopNama->map(function ($group, $coopName) {
                return [
                    'coop_name' => $coopName,
                    'livestock_count' => $group->count(),
                    'livestock_ids' => $group->pluck('id')->toArray()
                ];
            })->toArray()
        ]);

        foreach ($livestocksByCoopNama as $coopNama => $coopLivestocks) {
            $coopData = [];

            foreach ($coopLivestocks as $livestock) {
                // Skip if no Recording records exist
                if (!Recording::where('livestock_id', $livestock->id)
                    ->whereDate('tanggal', $tanggal)
                    ->exists()) {
                    continue;
                }

                // Get depletion records for this livestock
                $depletionRecords = $this->depletionReportService->getDepletionRecords($livestock, $tanggal);

                if ($depletionRecords->count() > 0) {
                    // Create one batch per depletion record
                    foreach ($depletionRecords as $depletionRecord) {
                        $batchData = $this->depletionReportService->processLivestockDepletionDetails(
                            $livestock,
                            $tanggal,
                            $distinctFeedNames,
                            $totals,
                            $allFeedUsageDetails,
                            $depletionRecord
                        );
                        $coopData[] = $batchData;
                    }
                } else {
                    // No depletion records, create one batch with zero depletion
                    $batchData = $this->depletionReportService->processLivestockDepletionDetails(
                        $livestock,
                        $tanggal,
                        $distinctFeedNames,
                        $totals,
                        $allFeedUsageDetails,
                        null
                    );
                    $coopData[] = $batchData;
                }
            }

            if (!empty($coopData)) {
                $recordings[$coopNama] = $coopData;
            }
        }

        return $recordings;
    }

    /**
     * Process simple mode - aggregate data per coop
     */
    private function processSimpleMode($livestocks, Carbon $tanggal, array $distinctFeedNames, array &$totals, $allFeedUsageDetails, $allSupplyUsageDetails, array &$supplyUsageTotals): array
    {
        $recordings = [];
        $livestocksByCoopNama = $livestocks->groupBy(function ($livestock) {
            return optional($livestock->coop)->name ?? 'Tanpa Kandang';
        });

        foreach ($livestocksByCoopNama as $coopNama => $coopLivestocks) {
            $aggregatedData = $this->processCoopAggregation($coopLivestocks, $tanggal, $distinctFeedNames, $totals, $allFeedUsageDetails, $allSupplyUsageDetails, $supplyUsageTotals);
            if ($aggregatedData !== null) {
                $recordings[$coopNama] = $aggregatedData;
            }
        }

        return $recordings;
    }

    /**
     * Process coop aggregation for simple mode
     * Extracted from ReportsController::processCoopAggregation()
     */
    private function processCoopAggregation($coopLivestocks, Carbon $tanggal, array $distinctFeedNames, array &$totals, $allFeedUsageDetails = null, $allSupplyUsageDetails = null, array &$supplyUsageTotals = null, &$stockCarryOver = [])
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
            // --- REF: Stock Carry Over Logic ---
            $umur = Carbon::parse($livestock->start_date)->diffInDays($tanggal);
            if ($umur === 0) {
                $stockAwal = (int) $livestock->initial_quantity;
            } else {
                if (isset($stockCarryOver[$livestock->id])) {
                    $stockAwal = $stockCarryOver[$livestock->id];
                } else {
                    // Cari recording sebelumnya
                    $recordingPrev = Recording::where('livestock_id', $livestock->id)
                        ->whereDate('tanggal', '<', $tanggal)
                        ->orderBy('tanggal', 'desc')
                        ->first();
                    if ($recordingPrev && isset($recordingPrev->stock_akhir)) {
                        $stockAwal = (int) $recordingPrev->stock_akhir;
                    } else {
                        $stockAwal = (int) $livestock->initial_quantity;
                    }
                }
            }
            // Inject stock_awal ke batchData
            $batchData = $this->processLivestockDataWithStockAwal($livestock, $tanggal, $distinctFeedNames, $totals, $allFeedUsageDetails, $allSupplyUsageDetails, $supplyUsageTotals, $stockAwal);
            Log::info('Debug batchData', [
                'livestock_id' => $livestock->id,
                'batchData' => $batchData
            ]);
            if ($batchData === null) {
                Log::warning('Livestock skipped, no recording', ['livestock_id' => $livestock->id]);
                continue; // Skip livestock without Recording
            }
            $processedCount++;
            $batchDataCollection[] = $batchData;
            // Simpan stock_akhir untuk carry-over hari berikutnya
            $stockCarryOver[$livestock->id] = $batchData['stock_akhir'];
            Log::debug('Stock Carry Over', [
                'livestock_id' => $livestock->id,
                'umur' => $umur,
                'stock_awal' => $stockAwal,
                'stock_akhir' => $batchData['stock_akhir'],
                'tanggal' => $tanggal->format('Y-m-d')
            ]);
        }
        // dd($batchDataCollection); // Debug semua hasil valid

        if (empty($batchDataCollection)) {
            return null; // No data to aggregate for this coop
        }

        $aggregatedData['livestock_count'] = $processedCount;

        // Aggregate all batch data
        foreach ($batchDataCollection as $batchData) {
            $aggregatedData['umur'] = $batchData['umur']; // Use last livestock age
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

        // Calculate averages for weight data
        if ($aggregatedData['livestock_count'] > 0) {
            $aggregatedData['berat_semalam'] = $aggregatedData['berat_semalam'] / $aggregatedData['livestock_count'];
            $aggregatedData['berat_hari_ini'] = $aggregatedData['berat_hari_ini'] / $aggregatedData['livestock_count'];
            $aggregatedData['kenaikan_berat'] = $aggregatedData['kenaikan_berat'] / $aggregatedData['livestock_count'];
        }

        // Calculate depletion percentage
        $aggregatedData['deplesi_percentage'] = $aggregatedData['stock_awal'] > 0
            ? round(($aggregatedData['total_deplesi'] / $aggregatedData['stock_awal']) * 100, 2)
            : 0;

        Log::debug('Processed coop aggregation', [
            'coop_livestock_count' => $aggregatedData['livestock_count'],
            'total_stock_awal' => $aggregatedData['stock_awal'],
            'total_deplesi' => $aggregatedData['total_deplesi'],
            'feed_usage_per_jenis' => $aggregatedData['pakan_harian']
        ]);

        return $aggregatedData;
    }

    /**
     * Process individual livestock data
     * Extracted from ReportsController::processLivestockData()
     */
    private function processLivestockData($livestock, Carbon $tanggal, array $distinctFeedNames, array &$totals, $allFeedUsageDetails = null, $allSupplyUsageDetails = null, array &$supplyUsageTotals = null)
    {
        $recordingData = Recording::where('livestock_id', $livestock->id)
            ->whereDate('tanggal', $tanggal)
            ->first();

        // Skip if no Recording data
        if (!$recordingData) {
            Log::debug('Skipping livestock - no Recording data', [
                'livestock_id' => $livestock->id,
                'tanggal' => $tanggal->format('Y-m-d'),
            ]);
            return null;
        }

        $age = Carbon::parse($livestock->start_date)->diffInDays($tanggal);
        $stockAwal = (int) $livestock->initial_quantity;

        // Get depletion data with type normalization
        $mortalityQuery = LivestockDepletion::where('livestock_id', $livestock->id)
            ->whereIn('jenis', [
                LivestockDepletionConfig::TYPE_MORTALITY,
                LivestockDepletionConfig::LEGACY_TYPE_MATI
            ])
            ->whereDate('tanggal', $tanggal->format('Y-m-d'));
        $mortality = (int) $mortalityQuery->sum('jumlah');

        $cullingQuery = LivestockDepletion::where('livestock_id', $livestock->id)
            ->whereIn('jenis', [
                LivestockDepletionConfig::TYPE_CULLING,
                LivestockDepletionConfig::LEGACY_TYPE_AFKIR
            ])
            ->whereDate('tanggal', $tanggal->format('Y-m-d'));
        $culling = (int) $cullingQuery->sum('jumlah');

        $totalDepletion = $mortality + $culling;

        // Get sales data
        $sales = LivestockSalesItem::where('livestock_id', $livestock->id)
            ->whereHas('livestockSale', function ($query) use ($tanggal) {
                $query->whereDate('tanggal', $tanggal);
            })
            ->first();

        $totalSalesCumulative = (int) LivestockSalesItem::where('livestock_id', $livestock->id)
            ->whereHas('livestockSale', function ($query) use ($tanggal) {
                $query->whereDate('tanggal', '<=', $tanggal);
            })
            ->sum('quantity');

        // Get feed usage data
        $feedUsageDetails = $allFeedUsageDetails
            ? $allFeedUsageDetails->filter(function ($detail) use ($livestock) {
                return $detail->feedUsage && $detail->feedUsage->livestock_id === $livestock->id;
            })
            : collect();

        // Fallback: if no data for livestock, use all feed usage for farm
        if ($feedUsageDetails->isEmpty() && $allFeedUsageDetails) {
            $feedUsageDetails = $allFeedUsageDetails;
        }

        $pakanHarianPerJenis = [];
        $totalPakanHarian = 0;

        // Process feed usage by type
        foreach ($distinctFeedNames as $feedName) {
            $jumlah = $feedUsageDetails->where('feed.name', $feedName)->sum('quantity_taken');
            $pakanHarianPerJenis[$feedName] = $jumlah;
            $totalPakanHarian += $jumlah;
        }

        // Get cumulative feed usage
        $totalPakanUsage = (float) FeedUsageDetail::whereHas('feedUsage', function ($query) use ($livestock, $tanggal) {
            $query->where('livestock_id', $livestock->id)
                ->whereDate('usage_date', '<=', $tanggal);
        })->sum('quantity_taken');

        // Get weight data
        $berat_semalam = (float) ($recordingData->berat_semalam ?? 0);
        $berat_hari_ini = (float) ($recordingData->berat_hari_ini ?? 0);
        $kenaikan_berat = (float) ($recordingData->kenaikan_berat ?? 0);

        $stockAkhir = $stockAwal - $totalDepletion - $totalSalesCumulative;

        // Update totals
        $totals['stock_awal'] += $stockAwal;
        $totals['mati'] += $mortality;
        $totals['afkir'] += $culling;
        $totals['total_deplesi'] += $totalDepletion;
        $totals['jual_ekor'] += (int) ($sales->quantity ?? 0);
        $totals['jual_kg'] += (float) ($sales->total_berat ?? 0);
        $totals['stock_akhir'] += $stockAkhir;
        $totals['berat_semalam'] += $berat_semalam;
        $totals['berat_hari_ini'] += $berat_hari_ini;
        $totals['kenaikan_berat'] += $kenaikan_berat;
        $totals['pakan_total'] += $totalPakanUsage;
        $totals['tangkap_ekor'] += (int) ($sales->quantity ?? 0);
        $totals['tangkap_kg'] += (float) ($sales->total_berat ?? 0);

        foreach ($pakanHarianPerJenis as $jenis => $jumlah) {
            $totals['pakan_harian'][$jenis] = ($totals['pakan_harian'][$jenis] ?? 0) + (float) $jumlah;
        }

        // --- SUPPLY USAGE AGGREGATION ---
        $supplyUsageDetails = $allSupplyUsageDetails
            ? $allSupplyUsageDetails->filter(function ($detail) use ($livestock) {
                return $detail->supplyUsage && $detail->supplyUsage->livestock_id === $livestock->id;
            })
            : collect();

        $supplyByType = [];
        $supplyDetails = [];
        $totalSupplyCost = 0;

        foreach ($supplyUsageDetails as $detail) {
            $supplyName = $detail->supply->name ?? 'Unknown';
            $unitName = $detail->unit->name ?? 'pcs';
            $quantity = (float) $detail->quantity_taken;
            $unitCost = $detail->price_per_unit ?? ($detail->supply->price ?? 0);
            $cost = $quantity * $unitCost;

            if (!isset($supplyByType[$supplyName])) {
                $supplyByType[$supplyName] = [
                    'quantity' => 0,
                    'cost' => 0,
                    'unit' => $unitName,
                    'unit_cost' => $unitCost
                ];
            }
            $supplyByType[$supplyName]['quantity'] += $quantity;
            $supplyByType[$supplyName]['cost'] += $cost;
            $totalSupplyCost += $cost;

            $supplyDetails[] = [
                'livestock_id' => $livestock->id,
                'livestock_name' => $livestock->name,
                'supply_name' => $supplyName,
                'quantity' => $quantity,
                'unit' => $unitName,
                'unit_cost' => $unitCost,
                'cost' => $cost,
                'usage_date' => $detail->supplyUsage->usage_date ?? null,
                'status' => $detail->supplyUsage->status ?? null
            ];
        }

        // Update global supply usage totals (by_type)
        if ($supplyUsageTotals !== null) {
            foreach ($supplyByType as $type => $data) {
                if (!isset($supplyUsageTotals['by_type'][$type])) {
                    $supplyUsageTotals['by_type'][$type] = [
                        'quantity' => 0,
                        'cost' => 0,
                        'unit' => $data['unit'],
                        'unit_cost' => $data['unit_cost']
                    ];
                }
                $supplyUsageTotals['by_type'][$type]['quantity'] += $data['quantity'];
                $supplyUsageTotals['by_type'][$type]['cost'] += $data['cost'];
            }
            // Merge details for audit trail
            $supplyUsageTotals['details'] = array_merge($supplyUsageTotals['details'], $supplyDetails);
        }

        Log::debug('Processed livestock supply usage', [
            'livestock_id' => $livestock->id,
            'supply_by_type' => $supplyByType,
            'total_supply_cost' => $totalSupplyCost
        ]);

        Log::debug('Processed livestock data', [
            'livestock_id' => $livestock->id,
            'livestock_name' => $livestock->name,
            'stock_awal' => $stockAwal,
            'mortality' => $mortality,
            'culling' => $culling,
            'total_depletion' => $totalDepletion,
            'feed_usage_per_jenis' => $pakanHarianPerJenis
        ]);

        return [
            'livestock_id' => $livestock->id,
            'livestock_name' => $livestock->name,
            'umur' => $age,
            'stock_awal' => $stockAwal,
            'mati' => $mortality,
            'afkir' => $culling,
            'total_deplesi' => $totalDepletion,
            'deplesi_percentage' => $stockAwal > 0 ? round(($totalDepletion / $stockAwal) * 100, 2) : 0,
            'jual_ekor' => (int) ($sales->quantity ?? 0),
            'jual_kg' => (float) ($sales->total_berat ?? 0),
            'stock_akhir' => $stockAkhir,
            'berat_semalam' => $berat_semalam,
            'berat_hari_ini' => $berat_hari_ini,
            'kenaikan_berat' => $kenaikan_berat,
            'pakan_harian' => $pakanHarianPerJenis,
            'pakan_total' => $totalPakanUsage,
            'pakan_jenis' => $distinctFeedNames,
            'supply_usage_by_type' => $supplyByType,
            'supply_total_cost' => $totalSupplyCost
        ];
    }

    // Tambahkan versi baru processLivestockData yang menerima stock_awal
    private function processLivestockDataWithStockAwal($livestock, Carbon $tanggal, array $distinctFeedNames, array &$totals, $allFeedUsageDetails = null, $allSupplyUsageDetails = null, array &$supplyUsageTotals = null, $stockAwalOverride = null)
    {
        $recordingData = Recording::where('livestock_id', $livestock->id)
            ->whereDate('tanggal', $tanggal)
            ->first();

        if (!$recordingData) {
            Log::debug('Skipping livestock - no Recording data', [
                'livestock_id' => $livestock->id,
                'tanggal' => $tanggal->format('Y-m-d'),
            ]);
            return null;
        }

        $age = Carbon::parse($livestock->start_date)->diffInDays($tanggal);
        // FIX: Prioritaskan override!
        if ($stockAwalOverride !== null) {
            $stockAwal = $stockAwalOverride;
            $stockAwalSource = 'carry_over';
        } else if ($age === 0) {
            $stockAwal = (int) $livestock->initial_quantity;
            $stockAwalSource = 'initial_quantity';
        } else {
            // Cari recording terdekat sebelum tanggal ini
            $recordingPrev = Recording::where('livestock_id', $livestock->id)
                ->whereDate('tanggal', '<', $tanggal)
                ->orderBy('tanggal', 'desc')
                ->first();
            if ($recordingPrev && isset($recordingPrev->stock_akhir)) {
                $stockAwal = (int) $recordingPrev->stock_akhir;
                $stockAwalSource = 'prev_recording';
                Log::debug('StockAwal carry-over from previous recording', [
                    'livestock_id' => $livestock->id,
                    'tanggal' => $tanggal->format('Y-m-d'),
                    'stock_awal' => $stockAwal,
                    'from_tanggal' => $recordingPrev->tanggal,
                    'stock_akhir_prev' => $recordingPrev->stock_akhir
                ]);
            } else {
                $stockAwal = (int) $livestock->initial_quantity;
                $stockAwalSource = 'initial_quantity_fallback';
                Log::debug('StockAwal fallback to initial_quantity', [
                    'livestock_id' => $livestock->id,
                    'tanggal' => $tanggal->format('Y-m-d'),
                    'stock_awal' => $stockAwal
                ]);
            }
        }

        // Get depletion data with type normalization
        $mortalityQuery = LivestockDepletion::where('livestock_id', $livestock->id)
            ->whereIn('jenis', [
                LivestockDepletionConfig::TYPE_MORTALITY,
                LivestockDepletionConfig::LEGACY_TYPE_MATI
            ])
            ->whereDate('tanggal', $tanggal->format('Y-m-d'));
        $mortality = (int) $mortalityQuery->sum('jumlah');

        $cullingQuery = LivestockDepletion::where('livestock_id', $livestock->id)
            ->whereIn('jenis', [
                LivestockDepletionConfig::TYPE_CULLING,
                LivestockDepletionConfig::LEGACY_TYPE_AFKIR
            ])
            ->whereDate('tanggal', $tanggal->format('Y-m-d'));
        $culling = (int) $cullingQuery->sum('jumlah');

        $totalDepletion = $mortality + $culling;

        // Get sales data
        $sales = LivestockSalesItem::where('livestock_id', $livestock->id)
            ->whereHas('livestockSale', function ($query) use ($tanggal) {
                $query->whereDate('tanggal', $tanggal);
            })
            ->first();

        $totalSalesCumulative = (int) LivestockSalesItem::where('livestock_id', $livestock->id)
            ->whereHas('livestockSale', function ($query) use ($tanggal) {
                $query->whereDate('tanggal', '<=', $tanggal);
            })
            ->sum('quantity');

        // Get feed usage data
        $feedUsageDetails = $allFeedUsageDetails
            ? $allFeedUsageDetails->filter(function ($detail) use ($livestock) {
                return $detail->feedUsage && $detail->feedUsage->livestock_id === $livestock->id;
            })
            : collect();

        // Fallback: if no data for livestock, use all feed usage for farm
        if ($feedUsageDetails->isEmpty() && $allFeedUsageDetails) {
            $feedUsageDetails = $allFeedUsageDetails;
        }

        $pakanHarianPerJenis = [];
        $totalPakanHarian = 0;

        // Process feed usage by type
        foreach ($distinctFeedNames as $feedName) {
            $jumlah = $feedUsageDetails->where('feed.name', $feedName)->sum('quantity_taken');
            $pakanHarianPerJenis[$feedName] = $jumlah;
            $totalPakanHarian += $jumlah;
        }

        // Get cumulative feed usage
        $totalPakanUsage = (float) FeedUsageDetail::whereHas('feedUsage', function ($query) use ($livestock, $tanggal) {
            $query->where('livestock_id', $livestock->id)
                ->whereDate('usage_date', '<=', $tanggal);
        })->sum('quantity_taken');

        // Get weight data
        $berat_semalam = (float) ($recordingData->berat_semalam ?? 0);
        $berat_hari_ini = (float) ($recordingData->berat_hari_ini ?? 0);
        $kenaikan_berat = (float) ($recordingData->kenaikan_berat ?? 0);

        // Perhitungan stock akhir HARUS konsisten: stock_awal - deplesi - penjualan cumulative
        $stockAkhir = $stockAwal - $totalDepletion - $totalSalesCumulative;

        // Logging detail untuk debugging
        Log::info('Livestock Stock Calculation', [
            'livestock_id' => $livestock->id,
            'livestock_name' => $livestock->name,
            'tanggal' => $tanggal->format('Y-m-d'),
            'umur' => $age,
            'stock_awal' => $stockAwal,
            'stock_awal_source' => $stockAwalSource ?? 'unknown',
            'deplesi' => $totalDepletion,
            'penjualan_cumulative' => $totalSalesCumulative,
            'stock_akhir' => $stockAkhir
        ]);

        // Update totals
        $totals['stock_awal'] += $stockAwal;
        $totals['mati'] += $mortality;
        $totals['afkir'] += $culling;
        $totals['total_deplesi'] += $totalDepletion;
        $totals['jual_ekor'] += (int) ($sales->quantity ?? 0);
        $totals['jual_kg'] += (float) ($sales->total_berat ?? 0);
        $totals['stock_akhir'] += $stockAkhir;
        $totals['berat_semalam'] += $berat_semalam;
        $totals['berat_hari_ini'] += $berat_hari_ini;
        $totals['kenaikan_berat'] += $kenaikan_berat;
        $totals['pakan_total'] += $totalPakanUsage;
        $totals['tangkap_ekor'] += (int) ($sales->quantity ?? 0);
        $totals['tangkap_kg'] += (float) ($sales->total_berat ?? 0);

        foreach ($pakanHarianPerJenis as $jenis => $jumlah) {
            $totals['pakan_harian'][$jenis] = ($totals['pakan_harian'][$jenis] ?? 0) + (float) $jumlah;
        }

        // --- SUPPLY USAGE AGGREGATION ---
        $supplyUsageDetails = $allSupplyUsageDetails
            ? $allSupplyUsageDetails->filter(function ($detail) use ($livestock) {
                return $detail->supplyUsage && $detail->supplyUsage->livestock_id === $livestock->id;
            })
            : collect();

        $supplyByType = [];
        $supplyDetails = [];
        $totalSupplyCost = 0;

        foreach ($supplyUsageDetails as $detail) {
            $supplyName = $detail->supply->name ?? 'Unknown';
            $unitName = $detail->unit->name ?? 'pcs';
            $quantity = (float) $detail->quantity_taken;

            // Fix: Get price from SupplyPurchase properly
            $unitCost = 0;
            if ($detail->price_per_unit) {
                $unitCost = $detail->price_per_unit;
            } elseif ($detail->supply->price) {
                $unitCost = $detail->supply->price;
            } elseif ($detail->supplyStock && $detail->supplyStock->supplyPurchase) {
                // Use price_per_converted_unit if available, otherwise price_per_unit
                $unitCost = $detail->supplyStock->supplyPurchase->price_per_converted_unit ??
                    $detail->supplyStock->supplyPurchase->price_per_unit ?? 0;
            }

            $cost = $quantity * $unitCost;

            if (!isset($supplyByType[$supplyName])) {
                $supplyByType[$supplyName] = [
                    'quantity' => 0,
                    'cost' => 0,
                    'unit' => $unitName,
                    'unit_cost' => $unitCost
                ];
            }
            $supplyByType[$supplyName]['quantity'] += $quantity;
            $supplyByType[$supplyName]['cost'] += $cost;
            $totalSupplyCost += $cost;

            $supplyDetails[] = [
                'livestock_id' => $livestock->id,
                'livestock_name' => $livestock->name,
                'supply_name' => $supplyName,
                'quantity' => $quantity,
                'unit' => $unitName,
                'unit_cost' => $unitCost,
                'cost' => $cost,
                'usage_date' => $detail->supplyUsage->usage_date ?? null,
                'status' => $detail->supplyUsage->status ?? null
            ];
        }

        // Update global supply usage totals (by_type)
        if ($supplyUsageTotals !== null) {
            foreach ($supplyByType as $type => $data) {
                if (!isset($supplyUsageTotals['by_type'][$type])) {
                    $supplyUsageTotals['by_type'][$type] = [
                        'quantity' => 0,
                        'cost' => 0,
                        'unit' => $data['unit'],
                        'unit_cost' => $data['unit_cost']
                    ];
                }
                $supplyUsageTotals['by_type'][$type]['quantity'] += $data['quantity'];
                $supplyUsageTotals['by_type'][$type]['cost'] += $data['cost'];
            }
            // Merge details for audit trail
            $supplyUsageTotals['details'] = array_merge($supplyUsageTotals['details'], $supplyDetails);
        }

        Log::debug('Processed livestock supply usage', [
            'livestock_id' => $livestock->id,
            'supply_by_type' => $supplyByType,
            'total_supply_cost' => $totalSupplyCost
        ]);

        Log::debug('Processed livestock data', [
            'livestock_id' => $livestock->id,
            'livestock_name' => $livestock->name,
            'stock_awal' => $stockAwal,
            'mortality' => $mortality,
            'culling' => $culling,
            'total_depletion' => $totalDepletion,
            'feed_usage_per_jenis' => $pakanHarianPerJenis
        ]);

        return [
            'livestock_id' => $livestock->id,
            'livestock_name' => $livestock->name,
            'umur' => $age,
            'stock_awal' => $stockAwal,
            'mati' => $mortality,
            'afkir' => $culling,
            'total_deplesi' => $totalDepletion,
            'deplesi_percentage' => $stockAwal > 0 ? round(($totalDepletion / $stockAwal) * 100, 2) : 0,
            'jual_ekor' => (int) ($sales->quantity ?? 0),
            'jual_kg' => (float) ($sales->total_berat ?? 0),
            'stock_akhir' => $stockAkhir,
            'berat_semalam' => $berat_semalam,
            'berat_hari_ini' => $berat_hari_ini,
            'kenaikan_berat' => $kenaikan_berat,
            'pakan_harian' => $pakanHarianPerJenis,
            'pakan_total' => $totalPakanUsage,
            'pakan_jenis' => $distinctFeedNames,
            'supply_usage_by_type' => $supplyByType,
            'supply_total_cost' => $totalSupplyCost,
            'date' => $tanggal->format('Y-m-d'),
            'stock_awal_source' => $stockAwalSource ?? 'unknown',
        ];
    }

    /**
     * Finalize totals and calculate final percentages
     */
    private function finalizeTotals(array &$totals, array $distinctFeedNames): void
    {
        // Ensure all feed names are represented
        foreach ($distinctFeedNames as $feedName) {
            if (!isset($totals['pakan_harian'][$feedName])) {
                $totals['pakan_harian'][$feedName] = 0;
            }
        }

        // Calculate final percentages
        $totals['deplesi_percentage'] = $totals['stock_awal'] > 0
            ? round(($totals['total_deplesi'] / $totals['stock_awal']) * 100, 2)
            : 0;

        // Calculate survival rate
        $totals['survival_rate'] = $totals['stock_awal'] > 0
            ? round(($totals['stock_akhir'] / $totals['stock_awal']) * 100, 2)
            : 0;

        // Sync legacy fields
        $totals['tangkap_ekor'] = $totals['jual_ekor'];
        $totals['tangkap_kg'] = $totals['jual_kg'];

        Log::debug('Final totals calculated', [
            'stock_awal' => $totals['stock_awal'],
            'stock_akhir' => $totals['stock_akhir'],
            'total_deplesi' => $totals['total_deplesi'],
            'deplesi_percentage' => $totals['deplesi_percentage'],
            'survival_rate' => $totals['survival_rate'],
            'pakan_total' => $totals['pakan_total'],
            'feed_types_count' => count($distinctFeedNames)
        ]);
    }

    /**
     * Finalize supply usage totals
     */
    private function finalizeSupplyUsageTotals(array &$supplyUsageTotals): void
    {
        // Ensure all types are present and calculate grand total
        $supplyUsageTotals['total_cost'] = 0;
        foreach ($supplyUsageTotals['by_type'] as $type => &$data) {
            $data['cost'] = round($data['cost'], 2);
            $supplyUsageTotals['total_cost'] += $data['cost'];
        }
        $supplyUsageTotals['total_cost'] = round($supplyUsageTotals['total_cost'], 2);
    }

    /**
     * Export harian report in requested format
     * 
     * @param \Illuminate\Http\Request $request
     * @param string $format
     * @return \Illuminate\Http\Response
     */
    public function exportHarianReport($request, $format = 'html', $livestockId = null)
    {
        try {
            // Validate input
            $request->validate([
                'farm' => 'required',
                'tanggal' => 'required|date',
                'report_type' => 'required|in:simple,detail',
                'export_format' => 'nullable|in:html,excel,pdf,csv'
            ]);

            $farm = Farm::findOrFail($request->farm);
            $tanggal = Carbon::parse($request->tanggal);
            $reportType = $request->report_type ?? 'simple';
            $exportFormat = $request->export_format ?? 'html';

            Log::info('Export Harian Report', [
                'farm_id' => $farm->id,
                'farm_name' => $farm->name,
                'tanggal' => $tanggal->format('Y-m-d'),
                'report_type' => $reportType,
                'export_format' => $exportFormat,
                'user_id' => Auth::id(),
                'filter_livestock_id' => $livestockId
            ]);

            // Get report data
            $exportData = $this->getHarianReportData($farm, $tanggal, $reportType, $livestockId);

            // dd($exportData);

            // Validate if there are recordings
            if (empty($exportData['recordings'])) {
                Log::warning('No Recording data found for export', [
                    'farm_id' => $farm->id,
                    'farm_name' => $farm->name,
                    'tanggal' => $tanggal->format('Y-m-d'),
                    'report_type' => $reportType
                ]);

                return response()->json([
                    'error' => 'Tidak ada data Recording untuk tanggal ' . $tanggal->format('d-M-Y') . ' di farm ' . $farm->name . '.'
                ], 404);
            }

            // Route to appropriate export format
            switch ($exportFormat) {
                case 'excel':
                    return $this->exportToExcel($exportData, $farm, $tanggal, $reportType);
                case 'pdf':
                    return $this->exportToPdf($exportData, $farm, $tanggal, $reportType);
                case 'csv':
                    return $this->exportToCsv($exportData, $farm, $tanggal, $reportType);
                default:
                    return $this->exportToHtml($exportData, $farm, $tanggal, $reportType);
            }
        } catch (\Exception $e) {
            Log::error('Error exporting harian report: ' . $e->getMessage());
            Log::debug('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Export to HTML format
     */
    private function exportToHtml($data, $farm, $tanggal, $reportType)
    {
        Log::info('Exporting Harian Report to HTML', [
            'data' => $data,
            'farm' => $farm->name,
            'tanggal' => $tanggal->format('d-M-y'),
            'report_type' => $reportType
        ]);
        return view('pages.reports.harian', [
            'farm' => $farm->name,
            'tanggal' => $tanggal->format('d-M-y'),
            'recordings' => $data['recordings'],
            'totals' => $data['totals'],
            'distinctFeedNames' => $data['distinctFeedNames'],
            'reportType' => $reportType,
            'diketahui' => '',
            'dibuat' => '',
            'supplyUsage' => $data['supplyUsage'] ?? []
        ]);
    }

    /**
     * Export to Excel format
     */
    private function exportToExcel($data, $farm, $tanggal, $reportType)
    {
        // Use existing DaillyReportExcelExportService
        $excelService = app(\App\Services\Report\DaillyReportExcelExportService::class);
        return $excelService->exportToExcel($data, $farm, $tanggal, $reportType);
    }

    /**
     * Export to PDF format
     */
    private function exportToPdf($data, $farm, $tanggal, $reportType)
    {
        try {
            $filename = 'laporan_harian_' . $farm->name . '_' . $tanggal->format('Y-m-d') . '_' . $reportType . '.pdf';

            $pdf = app('dompdf.wrapper');
            $html = view('pages.reports.harian-pdf', [
                'farm' => $farm->name,
                'tanggal' => $tanggal->format('d-M-y'),
                'recordings' => $data['recordings'],
                'totals' => $data['totals'],
                'distinctFeedNames' => $data['distinctFeedNames'],
                'reportType' => $reportType,
                'diketahui' => 'RIA NARSO',
                'dibuat' => 'HENDRA'
            ])->render();

            $pdf->loadHTML($html);
            $pdf->setPaper('A4', 'landscape');

            Log::info('PDF export completed', [
                'filename' => $filename,
                'report_type' => $reportType
            ]);

            return $pdf->download($filename);
        } catch (\Exception $e) {
            Log::error('PDF export failed', [
                'error' => $e->getMessage()
            ]);

            // Fallback to HTML view
            return $this->exportToHtml($data, $farm, $tanggal, $reportType);
        }
    }

    /**
     * Export to CSV format
     */
    private function exportToCsv($data, $farm, $tanggal, $reportType)
    {
        try {
            $filename = 'laporan_harian_' . $farm->name . '_' . $tanggal->format('Y-m-d') . '_' . $reportType . '.csv';

            // Use existing DaillyReportExcelExportService for structured data
            $excelService = app(\App\Services\Report\DaillyReportExcelExportService::class);
            $csvData = $excelService->prepareStructuredData($data, $farm, $tanggal, $reportType);

            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0'
            ];

            Log::info('CSV export completed', [
                'filename' => $filename,
                'rows_count' => count($csvData),
                'report_type' => $reportType
            ]);

            $callback = function () use ($csvData) {
                $file = fopen('php://output', 'w');
                fwrite($file, "\xEF\xBB\xBF"); // BOM for UTF-8
                foreach ($csvData as $row) {
                    fputcsv($file, $row, ',', '"');
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            Log::error('CSV export failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Export CSV gagal: ' . $e->getMessage()
            ], 500);
        }
    }
}

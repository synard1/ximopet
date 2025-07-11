<?php

declare(strict_types=1);

namespace App\Services\Recording;

use App\Services\Recording\Contracts\RecordingDataServiceInterface;
use App\Services\Recording\DTOs\ServiceResult;
use Illuminate\Support\Facades\Log;
use Exception;

// Models - Pindahkan semua model yang dibutuhkan dari Legacy Service
use App\Models\Recording;
use App\Models\CurrentLivestock;
use App\Models\FeedUsage;
use App\Models\SupplyUsage;
use App\Models\LivestockDepletion;
use App\Config\LivestockDepletionConfig;
use Carbon\Carbon;

use function App\Helpers\logInfoIfDebug;
use function App\Helpers\logDebugIfDebug;
use function App\Helpers\logErrorIfDebug;

/**
 * Concrete implementation for loading recording-related data.
 * In this initial phase, it contains placeholder logic. The actual
 * business logic will be implemented in subsequent phases.
 * 
 * @version 1.0
 * @since 2025-07-09
 */
class RecordingDataService implements RecordingDataServiceInterface
{
    public function loadCurrentDateData(string $livestockId, string $date): ServiceResult
    {
        $performanceService = app(\App\Services\Recording\RecordingPerformanceService::class);
        $startTime = microtime(true);
        $carbonDate = Carbon::parse($date);

        logInfoIfDebug('🔄 RecordingDataService::loadCurrentDateData started', [
            'livestock_id' => $livestockId,
            'date' => $date,
            'cache_enabled' => true
        ]);

        // 1. Cek cache sebelum query berat
        $cached = $performanceService->getCachedRecordingData($livestockId, $carbonDate);
        if ($cached) {
            $performanceService->logPerformanceMetrics('loadCurrentDateData_cache_hit', microtime(true) - $startTime, [
                'livestock_id' => $livestockId,
                'date' => $date,
                'cache' => true
            ]);
            logInfoIfDebug('✅ MODULAR_PATH: Cache hit for loadCurrentDateData', [
                'livestock_id' => $livestockId,
                'date' => $date,
                'cached_data_keys' => array_keys($cached)
            ]);
            return ServiceResult::success('Data loaded from cache.', $cached);
        }

        logInfoIfDebug('🔄 MODULAR_PATH: RecordingDataService::loadCurrentDateData called.', compact('livestockId', 'date'));
        try {
            $recording = Recording::where('livestock_id', $livestockId)
                ->where('tanggal', $date)
                ->first();

            logDebugIfDebug('Recording query result', [
                'recording_found' => $recording ? true : false,
                'recording_id' => $recording?->id,
                'recording_payload' => $recording ? (is_array($recording->payload) ? 'array' : 'json') : null
            ]);

            // Initialize all expected keys to prevent "Undefined array key" errors in the component.
            $data = [
                'weight_today' => null,
                'mortality' => 0,
                'culling' => 0,
                'sales_quantity' => null,
                'sales_price' => null,
                'total_sales' => null,
                'itemQuantities' => [],
                'supplyQuantities' => [],
                'feedUsageId' => null,
                'supplyUsageId' => null,
                'isManualDepletionEnabled' => false,
                'isManualFeedUsageEnabled' => false,
                'recording_exists' => false,
            ];

            // ALWAYS fetch depletion data, regardless of recording existence
            $data['mortality'] = LivestockDepletion::where('livestock_id', $livestockId)
                ->where('tanggal', $date)
                ->where('jenis', LivestockDepletionConfig::TYPE_MORTALITY)
                ->sum('jumlah');

            $data['culling'] = LivestockDepletion::where('livestock_id', $livestockId)
                ->where('tanggal', $date)
                ->where('jenis', LivestockDepletionConfig::TYPE_CULLING)
                ->sum('jumlah');

            logDebugIfDebug('Depletion data loaded', [
                'mortality' => $data['mortality'],
                'culling' => $data['culling']
            ]);

            if ($recording) {
                $payload = is_array($recording->payload)
                    ? $recording->payload
                    : json_decode($recording->payload, true);
                $data['weight_today'] = $recording->berat_hari_ini;
                $data['recording_exists'] = true;

                logDebugIfDebug('Recording data loaded', [
                    'weight_today' => $data['weight_today'],
                    'payload_keys' => $payload ? array_keys($payload) : null
                ]);

                // The manual depletion flag check can remain as it controls UI behavior
                if (isset($payload['config']['manual_depletion_enabled']) && $payload['config']['manual_depletion_enabled']) {
                    $data['isManualDepletionEnabled'] = true;
                } else {
                    $data['isManualDepletionEnabled'] = false;
                }
            }

            // ALWAYS fetch feed and supply usage, regardless of recording existence.
            // This ensures feedUsageId and supplyUsageId are always present.
            $feedUsage = FeedUsage::where('livestock_id', $livestockId)->where('usage_date', $date)->with('details')->first();
            if ($feedUsage) {
                logInfoIfDebug('✅ MODULAR_PATH: FeedUsage found for today.', [
                    'feedUsageId' => $feedUsage->id,
                    'details_count' => $feedUsage->details->count(),
                    'created_by_manual_input' => $feedUsage->created_by_manual_input
                ]);
                $data['feedUsageId'] = $feedUsage->id;
                if ($feedUsage->created_by_manual_input) {
                    $data['isManualFeedUsageEnabled'] = true;
                    // itemQuantities remains empty as it's handled by another component
                    logInfoIfDebug('MODULAR_PATH: Manual feed usage detected. Skipping itemQuantities population.');
                } else {
                    $data['isManualFeedUsageEnabled'] = false;
                    $itemQuantities = [];
                    foreach ($feedUsage->details as $detail) {
                        $itemQuantities[$detail->feed_id] = $detail->quantity_taken;
                    }
                    $data['itemQuantities'] = $itemQuantities;
                    logInfoIfDebug('MODULAR_PATH: Populated itemQuantities from FeedUsage details.', ['itemQuantities' => $itemQuantities]);
                }
            } else {
                logInfoIfDebug('MODULAR_PATH: No FeedUsage found for today.');
            }

            $supplyUsage = SupplyUsage::where('livestock_id', $livestockId)
                ->where('usage_date', $date)
                ->first();
            if ($supplyUsage) {
                $supplyQuantities = [];
                foreach ($supplyUsage->details as $detail) {
                    $supplyQuantities[$detail->supply_id] = $detail->quantity_taken;
                }
                $data['supplyQuantities'] = $supplyQuantities;
                $data['supplyUsageId'] = $supplyUsage->id;

                logDebugIfDebug('Supply usage loaded', [
                    'supplyUsageId' => $data['supplyUsageId'],
                    'supplyQuantities_count' => count($supplyQuantities)
                ]);
            }

            logInfoIfDebug('✅ MODULAR_PATH: Final data being returned from loadCurrentDateData.', [
                'data_keys' => array_keys($data),
                'essential_keys_present' => [
                    'itemQuantities' => isset($data['itemQuantities']),
                    'supplyQuantities' => isset($data['supplyQuantities']),
                    'mortality' => isset($data['mortality']),
                    'culling' => isset($data['culling'])
                ]
            ]);

            // 2. Simpan hasil ke cache
            $performanceService->cacheRecordingData($livestockId, $carbonDate, $data);
            // 3. Log waktu proses dengan metadata komprehensif
            $user = \Illuminate\Support\Facades\Auth::user();
            $executionTime = microtime(true) - $startTime;
            $threshold = 2.0; // seconds, match PERFORMANCE_THRESHOLD
            $isBottleneck = $executionTime > $threshold;
            $userName = $user?->name ?? null;
            $companyName = $user?->company?->name ?? null;
            $envMeta = [
                'server_hostname' => gethostname(),
                'php_version' => PHP_VERSION,
                'os' => PHP_OS,
                'memory_usage' => memory_get_usage(true),
                'peak_memory_usage' => memory_get_peak_usage(true),
                'laravel_version' => app()->version(),
                'app_env' => config('app.env'),
                'app_debug' => config('app.debug'),
            ];
            $metadata = array_merge([
                'date' => $date,
                'cache' => false,
                'livestock_id' => $livestockId,
                'query_params' => [
                    'livestock_id' => $livestockId,
                    'date' => $date
                ],
                'recording_exists' => $data['recording_exists'] ?? null,
                'feed_usage_count' => isset($data['itemQuantities']) ? count($data['itemQuantities']) : 0,
                'supply_usage_count' => isset($data['supplyQuantities']) ? count($data['supplyQuantities']) : 0,
                'depletion_count' => ($data['mortality'] > 0 ? 1 : 0) + ($data['culling'] > 0 ? 1 : 0),
                'is_manual_depletion' => $data['isManualDepletionEnabled'] ?? null,
                'is_manual_feed_usage' => $data['isManualFeedUsageEnabled'] ?? null,
                'user_id' => $user?->id,
                'user_name' => $userName,
                'company_id' => $user?->company_id,
                'company_name' => $companyName,
                'user_agent' => request()->header('User-Agent'),
                'service_version' => 'performance_v1.0',
                'environment' => config('app.env'),
                'app_debug' => config('app.debug'),
                'is_bottleneck' => $isBottleneck,
                'execution_time' => $executionTime,
                'performance_threshold' => $threshold,
            ], $envMeta);
            $performanceService->logPerformanceMetrics('loadCurrentDateData', $executionTime, $metadata);
            return ServiceResult::success('Data for the current date loaded successfully.', $data);
        } catch (Exception $e) {
            logErrorIfDebug('❌ MODULAR_PATH: Error in RecordingDataService::loadCurrentDateData', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ServiceResult::error('Failed to load data for the selected date.', $e);
        }
    }

    public function loadYesterdayData(string $livestockId, string $yesterdayDate): ServiceResult
    {
        logInfoIfDebug('MODULAR_PATH: RecordingDataService::loadYesterdayData called.', compact('livestockId', 'yesterdayDate'));
        try {
            // --- Fetch all data in parallel ---
            $yesterdayRecording = Recording::where('livestock_id', $livestockId)
                ->where('tanggal', $yesterdayDate)
                ->first();

            $yesterdayDeplesi = LivestockDepletion::where('livestock_id', $livestockId)
                ->where('tanggal', $yesterdayDate)
                ->get();

            $yesterdayFeedUsage = FeedUsage::where('livestock_id', $livestockId)
                ->where('usage_date', $yesterdayDate)
                ->with('details.feed.unit') // Eager load relations
                ->first();

            $yesterdaySupplyUsage = SupplyUsage::where('livestock_id', $livestockId)
                ->where('usage_date', $yesterdayDate)
                ->with('details.supply.unit') // Eager load relations
                ->first();


            // --- Process Data ---
            $yesterday_weight = $yesterdayRecording->berat_hari_ini ?? null;
            $yesterday_stock_end = $yesterdayRecording->stock_akhir ?? null;

            $mortalityTypes = [LivestockDepletionConfig::LEGACY_TYPE_MATI, LivestockDepletionConfig::TYPE_MORTALITY];
            $cullingTypes = [LivestockDepletionConfig::LEGACY_TYPE_AFKIR, LivestockDepletionConfig::TYPE_CULLING];

            $yesterday_mortality = $yesterdayDeplesi->whereIn('jenis', $mortalityTypes)->sum('jumlah');
            $yesterday_culling = $yesterdayDeplesi->whereIn('jenis', $cullingTypes)->sum('jumlah');

            $feedUsageDetails = [];
            if ($yesterdayFeedUsage) {
                $feedUsageDetails = [
                    'total_quantity' => $yesterdayFeedUsage->details->sum('quantity_taken'),
                    'by_type' => $yesterdayFeedUsage->details->groupBy('feed.name')->map(fn($group) => [
                        'name' => $group->first()->feed->name ?? 'Unknown',
                        'total_quantity' => $group->sum('quantity_taken'),
                        'unit' => $group->first()->feed->unit->name ?? 'Kg'
                    ])->values()->toArray(),
                    'types_count' => $yesterdayFeedUsage->details->pluck('feed.name')->unique()->count(),
                ];
            } else {
                $feedUsageDetails = ['total_quantity' => 0, 'by_type' => [], 'types_count' => 0];
            }

            $supplyUsageDetails = [];
            if ($yesterdaySupplyUsage) {
                $supplyUsageDetails = [
                    'total_quantity' => $yesterdaySupplyUsage->details->sum('quantity'),
                    'by_type' => $yesterdaySupplyUsage->details->groupBy('supply.name')->map(fn($group) => [
                        'name' => $group->first()->supply->name ?? 'Unknown',
                        'total_quantity' => $group->sum('quantity'),
                        'unit' => $group->first()->supply->unit->name ?? 'Unit'
                    ])->values()->toArray(),
                    'types_count' => $yesterdaySupplyUsage->details->pluck('supply.name')->unique()->count(),
                ];
            } else {
                $supplyUsageDetails = ['total_quantity' => 0, 'by_type' => [], 'types_count' => 0];
            }

            $summary = $this->generateYesterdaySummary(
                $yesterday_weight,
                $yesterday_mortality,
                $yesterday_culling,
                $feedUsageDetails,
                $supplyUsageDetails
            );

            $isManualDepletion = $yesterdayDeplesi->contains(function ($item) {
                $metadata = is_array($item->metadata) ? $item->metadata : json_decode($item->metadata ?? '{}', true);
                return isset($metadata['depletion_method']) && $metadata['depletion_method'] === 'manual';
            });

            // --- Build final data structure ---
            $data = [
                'date' => $yesterdayDate,
                'formatted_date' => Carbon::parse($yesterdayDate)->format('d/m/Y'),
                'day_name' => Carbon::parse($yesterdayDate)->locale('id')->dayName,
                'weight' => $yesterday_weight,
                'stock_end' => $yesterday_stock_end,
                'mortality' => $yesterday_mortality,
                'culling' => $yesterday_culling,
                'total_depletion' => $yesterday_mortality + $yesterday_culling,
                'feed_usage' => $feedUsageDetails,
                'supply_usage' => $supplyUsageDetails,
                'has_data' => $yesterdayRecording || $yesterdayDeplesi->isNotEmpty() || $yesterdayFeedUsage || $yesterdaySupplyUsage,
                'summary' => $summary,
                'is_manual_depletion' => $isManualDepletion,
                'depletion_method' => $isManualDepletion ? 'manual' : 'recording',
                'weight_yesterday' => $yesterday_weight // For direct access in component
            ];


            return ServiceResult::success('Yesterday data loaded successfully.', $data);
        } catch (Exception $e) {
            logErrorIfDebug('❌ MODULAR_PATH: Error in RecordingDataService::loadYesterdayData', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ServiceResult::error('Failed to load yesterday\'s data.', $e);
        }
    }

    public function loadRecordingDataForTable(string $livestockId): ServiceResult
    {
        logInfoIfDebug('MODULAR_PATH: RecordingDataService::loadRecordingDataForTable called.', compact('livestockId'));

        try {
            $recordings = Recording::where('livestock_id', $livestockId)
                ->orderBy('tanggal', 'desc')
                ->get()
                ->map(function ($recording) {
                    $payload = is_array($recording->payload)
                        ? $recording->payload
                        : json_decode($recording->payload, true);

                    // Robust depletion calculation by querying the source table directly
                    $kematian = LivestockDepletion::where('recording_id', $recording->id)->sum('jumlah');

                    return [
                        'id' => $recording->id,
                        'tanggal' => Carbon::parse($recording->tanggal)->format('d M Y'),
                        'age' => $recording->age,
                        'stock_akhir' => $recording->stock_akhir,
                        'berat_hari_ini' => number_format((float) $recording->berat_hari_ini, 2),
                        'kenaikan_berat' => number_format((float) $recording->kenaikan_berat, 2),
                        'pakan_harian' => isset($payload['data']['consumption']['feed']['total_quantity'])
                            ? number_format((float) $payload['data']['consumption']['feed']['total_quantity'], 2)
                            : number_format((float) $recording->pakan_harian, 2),
                        'kematian' => $kematian,
                        'payload_version' => $payload['schema']['version'] ?? '1.0'
                    ];
                });

            return ServiceResult::success('Table data loaded successfully.', ['recordings' => $recordings->toArray()]);
        } catch (Exception $e) {
            logErrorIfDebug('❌ MODULAR_PATH: Error in RecordingDataService::loadRecordingDataForTable', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ServiceResult::error('Failed to load recording history.', $e);
        }
    }

    /**
     * Generates a summary string for yesterday's data.
     * This is a helper method moved from the legacy service.
     */
    private function generateYesterdaySummary($yesterday_weight, $yesterday_mortality, $yesterday_culling, $yesterday_feed_usage, $yesterday_supply_usage): string
    {
        $summary = [];
        if ($yesterday_weight > 0) $summary[] = "Berat: " . number_format((float) $yesterday_weight, 0) . "gr";
        if ($yesterday_mortality > 0) $summary[] = "Mati: " . $yesterday_mortality . " ekor";
        if ($yesterday_culling > 0) $summary[] = "Afkir: " . $yesterday_culling . " ekor";
        if ($yesterday_feed_usage['total_quantity'] > 0) $summary[] = "Pakan: " . number_format($yesterday_feed_usage['total_quantity'], 1) . "kg";
        if ($yesterday_supply_usage['total_quantity'] > 0) $summary[] = "OVK: " . $yesterday_supply_usage['types_count'] . " jenis";
        return empty($summary) ? "Tidak ada data" : implode(", ", $summary);
    }
}

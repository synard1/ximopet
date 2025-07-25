<?php

namespace App\Services\Livestock;

use App\Models\Livestock;
use App\Models\LivestockCost;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LivestockCostRecalculationService
{
    protected $costService;

    public function __construct(LivestockCostService $costService)
    {
        $this->costService = $costService;
    }

    /**
     * Recalculate all cost history for a livestock (from start_date to last available date)
     * Optionally specify startDate and endDate
     */
    public function recalculateAll($livestockId, $startDate = null, $endDate = null, $dryRun = false)
    {
        $livestock = Livestock::findOrFail($livestockId);
        $start = $startDate ? Carbon::parse($startDate) : Carbon::parse($livestock->start_date);
        $lastCost = LivestockCost::where('livestock_id', $livestockId)->orderBy('tanggal', 'desc')->first();
        $end = $endDate ? Carbon::parse($endDate) : ($lastCost ? Carbon::parse($lastCost->tanggal) : Carbon::now());

        $superAdmin = \App\Models\User::role('SuperAdmin')->first();
        $userId = $superAdmin ? $superAdmin->id : 1;

        $startTime = microtime(true);
        $startTimeStr = now()->toDateTimeString();

        $existingCosts = LivestockCost::where('livestock_id', $livestockId)
            ->whereBetween('tanggal', [$start, $end])
            ->orderBy('tanggal')
            ->get()
            ->keyBy(function ($item) {
                return Carbon::parse($item->tanggal)->format('Y-m-d');
            });
        $existingCount = $existingCosts->count();
        $existingDates = $existingCosts->keys()->toArray();

        if ($dryRun) {
            Log::info('🔎 DRY RUN: Would recalculate ALL livestock costs', [
                'livestock_id' => $livestockId,
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
                'existing_count' => $existingCount,
                'existing_dates' => $existingDates
            ]);
            echo "[DRY RUN] Livestock: {$livestock->name} ({$livestock->id})\n";
            echo "[DRY RUN] Existing LivestockCost count in range: $existingCount\n";
            echo "[DRY RUN] Dates to be updated: [" . implode(', ', $existingDates) . "]\n";
            $datesToProcess = [];
            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $datesToProcess[] = $date->format('Y-m-d');
            }
            echo "[DRY RUN] Dates to be recalculated: [" . implode(', ', $datesToProcess) . "]\n";
            echo "[DRY RUN] Total days to process: " . count($datesToProcess) . "\n";
            return;
        }

        echo "[INFO] Livestock: {$livestock->name} ({$livestock->id})\n";
        echo "[INFO] Existing LivestockCost count in range: $existingCount\n";
        echo "[INFO] Will update existing and insert missing LivestockCost for dates: [" . implode(', ', $existingDates) . "]\n";

        Log::info('🔄 Recalculating ALL livestock costs (update/insert mode)', [
            'livestock_id' => $livestockId,
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'existing_count' => $existingCount,
            'existing_dates' => $existingDates
        ]);

        // --- Locking: Cek dan set flag cost_recalc_in_progress ---
        $data = $livestock->data ?? [];
        if (is_string($data)) {
            $data = json_decode($data, true) ?: [];
        }
        if (!empty($data['cost_recalc_in_progress'])) {
            echo "[ERROR] Recalculation already in progress for this livestock. Aborting.\n";
            Log::warning('❌ Recalculation aborted: already in progress', [
                'livestock_id' => $livestockId
            ]);
            return;
        }
        $data['cost_recalc_in_progress'] = true;
        $livestock->data = $data;
        $livestock->save();
        try {
            $datesProcessed = [];
            $updateCount = 0;
            $insertCount = 0;
            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $dateStr = $date->format('Y-m-d');
                try {
                    // The calculateForDate service now handles all logic, including saving the record.
                    $this->costService->calculateForDate($livestockId, $dateStr);

                    // We check if a cost record existed before this run to correctly report updates vs. inserts.
                    if ($existingCosts->has($dateStr)) {
                        $updateCount++;
                        echo "[UPDATE] $dateStr\n";
                    } else {
                        $insertCount++;
                        echo "[INSERT] $dateStr\n";
                    }
                    $datesProcessed[] = $dateStr;
                } catch (\Exception $e) {
                    // Cek ulang supply usage
                    $supplyUsageCost = \App\Models\SupplyUsageDetail::getTotalSupplyUsageCost($livestockId, $dateStr);
                    if ($supplyUsageCost > 0) {
                        $recordingId = null;
                        $prevRecording = \App\Models\Recording::where('livestock_id', $livestockId)
                            ->whereDate('tanggal', '<', $dateStr)
                            ->orderBy('tanggal', 'desc')
                            ->first();
                        if ($prevRecording && !empty($prevRecording->id)) {
                            $recordingId = $prevRecording->id;
                        } else {
                            $recordingId = (string) Str::uuid();
                        }
                        echo "[DEBUG] Insert minimal: recording_id = $recordingId\n";
                        $breakdown = [
                            'supply_usage_cost' => $supplyUsageCost,
                        ];
                        $newCost = new LivestockCost();
                        $newCost->livestock_id = $livestockId;
                        $newCost->tanggal = $dateStr;
                        $newCost->recording_id = $recordingId;
                        $newCost->total_cost = $supplyUsageCost;
                        $newCost->cost_per_ayam = 0;
                        $newCost->cost_breakdown = $breakdown;
                        $newCost->created_by = $userId;
                        $newCost->updated_by = $userId;
                        $newCost->save();
                        $insertCount++;
                        $datesProcessed[] = $dateStr;
                        echo "[INSERT-MIN] $dateStr (supply usage only)\n";
                    } else {
                        Log::error('❌ Failed cost calculation', [
                            'livestock_id' => $livestockId,
                            'date' => $dateStr,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        echo "[ERROR] Failed: $dateStr ({$e->getMessage()})\n";
                    }
                }
            }
            $newCount = LivestockCost::where('livestock_id', $livestockId)
                ->whereBetween('tanggal', [$start, $end])
                ->count();
            echo "[INFO] Recalculated and updated LivestockCost for dates: [" . implode(', ', $datesProcessed) . "]\n";
            echo "[INFO] Updated: $updateCount, Inserted: $insertCount\n";
            echo "[INFO] New LivestockCost count in range: $newCount\n";
            Log::info('✅ Completed recalculating ALL livestock costs', [
                'livestock_id' => $livestockId,
                'created_count' => $newCount,
                'created_dates' => $datesProcessed,
                'updated_count' => $updateCount,
                'inserted_count' => $insertCount
            ]);
        } finally {
            $finishTime = microtime(true);
            $finishTimeStr = now()->toDateTimeString();
            $duration = $finishTime - $startTime;
            $durationStr = $duration < 60 ? round($duration, 2) . ' detik' : ($duration < 3600 ? round($duration / 60, 2) . ' menit' : round($duration / 3600, 2) . ' jam');
            echo "[INFO] Start time: $startTimeStr\n";
            echo "[INFO] Finish time: $finishTimeStr\n";
            echo "[INFO] Duration: $durationStr\n";
            Log::info('⏱️ Recalculation timing', [
                'livestock_id' => $livestockId,
                'start_time' => $startTimeStr,
                'finish_time' => $finishTimeStr,
                'duration_seconds' => round($duration, 2),
                'duration_human' => $durationStr
            ]);
            // --- Unlock: set flag false ---
            $livestock = Livestock::findOrFail($livestockId); // reload
            $data = $livestock->data ?? [];
            if (is_string($data)) {
                $data = json_decode($data, true) ?: [];
            }
            $data['cost_recalc_in_progress'] = false;
            $data['cost_recalc_last_start_time'] = $startTimeStr;
            $data['cost_recalc_last_finish_time'] = $finishTimeStr;
            $data['cost_recalc_last_duration'] = $durationStr;
            $livestock->data = $data;
            $livestock->save();
            Log::info('🔓 Recalculation lock released', [
                'livestock_id' => $livestockId
            ]);
        }
    }

    /**
     * Recalculate cost from a specific date until the last available date
     */
    public function recalculateFrom($livestockId, $fromDate, $dryRun = false)
    {
        $livestock = Livestock::findOrFail($livestockId);
        $start = Carbon::parse($fromDate);
        $lastCost = LivestockCost::where('livestock_id', $livestockId)->orderBy('tanggal', 'desc')->first();
        $end = $lastCost ? Carbon::parse($lastCost->tanggal) : Carbon::now();
        $this->recalculateAll($livestockId, $start, $end, $dryRun);
    }
}

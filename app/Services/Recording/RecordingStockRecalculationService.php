<?php

declare(strict_types=1);

namespace App\Services\Recording;

use App\Models\Recording;
use App\Models\Livestock;
use App\Models\Farm;
use App\Models\Coop;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RecordingStockRecalculationService
{
    /**
     * Recalculate for a single livestock, optionally within a date range.
     */
    public function recalculateForLivestock(string $livestockId, ?string $startDate = null, ?string $endDate = null, array $options = []): array
    {
        $query = Recording::where('livestock_id', $livestockId)
            ->orderBy('tanggal');
        if ($startDate) $query->where('tanggal', '>=', $startDate);
        if ($endDate) $query->where('tanggal', '<=', $endDate);
        $recordings = $query->get();
        if ($recordings->isEmpty()) return ['updated' => 0, 'log' => [], 'error' => null];

        $livestock = Livestock::find($livestockId);
        if (!$livestock) return ['updated' => 0, 'log' => [], 'error' => 'Livestock not found'];

        $initialStock = $this->getInitialStock($livestock, $startDate, $recordings);
        $log = [];
        $updated = 0;
        $prevStockAkhir = $initialStock;
        DB::beginTransaction();
        try {
            foreach ($recordings as $rec) {
                $oldStockAwal = $rec->stock_awal;
                $oldStockAkhir = $rec->stock_akhir;
                $stockAwal = $prevStockAkhir;
                $totalDeplesi = (int)($rec->total_deplesi ?? 0);
                $totalPenjualan = (int)($rec->total_penjualan ?? 0);
                $stockAkhir = $stockAwal - $totalDeplesi - $totalPenjualan;
                if ($options['dry_run'] ?? false) {
                    $log[] = [
                        'id' => $rec->id,
                        'tanggal' => $rec->tanggal,
                        'old_stock_awal' => $oldStockAwal,
                        'new_stock_awal' => $stockAwal,
                        'old_stock_akhir' => $oldStockAkhir,
                        'new_stock_akhir' => $stockAkhir,
                    ];
                } else {
                    $rec->stock_awal = $stockAwal;
                    $rec->stock_akhir = $stockAkhir;
                    $rec->save();
                    $log[] = [
                        'id' => $rec->id,
                        'tanggal' => $rec->tanggal,
                        'old_stock_awal' => $oldStockAwal,
                        'new_stock_awal' => $stockAwal,
                        'old_stock_akhir' => $oldStockAkhir,
                        'new_stock_akhir' => $stockAkhir,
                    ];
                    $updated++;
                }
                $prevStockAkhir = $stockAkhir;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Stock recalc error', ['error' => $e->getMessage()]);
            return ['updated' => 0, 'log' => $log, 'error' => $e->getMessage()];
        }
        return ['updated' => $updated, 'log' => $log, 'error' => null];
    }

    /**
     * Recalculate for all livestock in a farm.
     */
    public function recalculateForFarm(string $farmId, ?string $startDate = null, ?string $endDate = null, array $options = []): array
    {
        $farm = Farm::find($farmId);
        if (!$farm) return ['updated' => 0, 'log' => [], 'error' => 'Farm not found'];
        $livestocks = $farm->livestocks ?? [];
        $summary = ['updated' => 0, 'log' => [], 'error' => null];
        foreach ($livestocks as $livestock) {
            $result = $this->recalculateForLivestock($livestock->id, $startDate, $endDate, $options);
            $summary['updated'] += $result['updated'];
            $summary['log'] = array_merge($summary['log'], $result['log']);
            if ($result['error']) $summary['error'] = $result['error'];
        }
        return $summary;
    }

    /**
     * Recalculate for all livestock in a coop.
     */
    public function recalculateForCoop(string $coopId, ?string $startDate = null, ?string $endDate = null, array $options = []): array
    {
        $coop = Coop::find($coopId);
        if (!$coop) return ['updated' => 0, 'log' => [], 'error' => 'Coop not found'];
        $livestocks = $coop->livestocks ?? [];
        $summary = ['updated' => 0, 'log' => [], 'error' => null];
        foreach ($livestocks as $livestock) {
            $result = $this->recalculateForLivestock($livestock->id, $startDate, $endDate, $options);
            $summary['updated'] += $result['updated'];
            $summary['log'] = array_merge($summary['log'], $result['log']);
            if ($result['error']) $summary['error'] = $result['error'];
        }
        return $summary;
    }

    /**
     * Recalculate for all livestock (global or with filters).
     */
    public function recalculateAll(array $filters = [], array $options = []): array
    {
        $query = Livestock::query();
        if (isset($filters['company_id'])) $query->where('company_id', $filters['company_id']);
        if (isset($filters['farm_id'])) $query->where('farm_id', $filters['farm_id']);
        if (isset($filters['coop_id'])) $query->where('coop_id', $filters['coop_id']);
        $livestocks = $query->get();
        $summary = ['updated' => 0, 'log' => [], 'error' => null];
        foreach ($livestocks as $livestock) {
            $result = $this->recalculateForLivestock($livestock->id, $filters['start_date'] ?? null, $filters['end_date'] ?? null, $options);
            $summary['updated'] += $result['updated'];
            $summary['log'] = array_merge($summary['log'], $result['log']);
            if ($result['error']) $summary['error'] = $result['error'];
        }
        return $summary;
    }

    /**
     * Get initial stock for recalculation.
     */
    private function getInitialStock($livestock, $startDate, $recordings)
    {
        if ($startDate) {
            $prev = Recording::where('livestock_id', $livestock->id)
                ->where('tanggal', '<', $startDate)
                ->orderByDesc('tanggal')->first();
            if ($prev) return $prev->stock_akhir;
        }
        return $livestock->initial_quantity ?? 0;
    }
}

<?php

namespace App\Services;

use App\Models\ExpeditionTariff;
use App\Models\ExpeditionTransaction;
use App\Models\Expedition;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ExpeditionTrackingService
{
    /**
     * Get estimated cost for reference only (not for actual calculation)
     * 
     * @param string $expeditionId
     * @param string $destinationZone
     * @param float $weight
     * @return array
     */
    public function getEstimatedCost(
        string $expeditionId,
        string $destinationZone,
        float $weight
    ): array {
        Log::info('Getting estimated expedition cost', [
            'expedition_id' => $expeditionId,
            'destination_zone' => $destinationZone,
            'weight' => $weight
        ]);

        // Find reference tariff for estimation
        $tariff = ExpeditionTariff::where('expedition_id', $expeditionId)
            ->where('zone_name', $destinationZone)
            ->active()
            ->first();

        $expedition = Expedition::find($expeditionId);

        if (!$tariff) {
            // Fallback to average rate from historical data
            $avgCostPerKg = ExpeditionTransaction::where('expedition_id', $expeditionId)
                ->where('destination_zone', $destinationZone)
                ->avg('expedition_cost') /
                ExpeditionTransaction::where('expedition_id', $expeditionId)
                ->where('destination_zone', $destinationZone)
                ->avg('total_weight') ?: 3000; // Default 3000 per kg

            return [
                'success' => false,
                'message' => 'Tarif tidak ditemukan, menggunakan estimasi berdasarkan data historis',
                'expedition_name' => $expedition->name ?? 'Unknown',
                'estimated_cost' => $weight * $avgCostPerKg,
                'rate_per_kg' => $avgCostPerKg,
                'note' => 'Estimasi berdasarkan data historis'
            ];
        }

        $estimatedCost = $tariff->getEstimatedCost($weight);

        return [
            'success' => true,
            'expedition_name' => $expedition->name ?? 'Unknown',
            'zone' => $destinationZone,
            'estimated_cost' => $estimatedCost,
            'rate_per_kg' => $tariff->estimated_rate_per_kg,
            'note' => 'Estimasi berdasarkan tarif referensi'
        ];
    }

    /**
     * Record actual expedition cost (main function)
     * 
     * @param mixed $transaction - Source transaction (sales, purchase, etc)
     * @param string $transactionType
     * @param array $expeditionData
     * @return ExpeditionTransaction
     */
    public function recordExpeditionCost(
        $transaction,
        string $transactionType,
        array $expeditionData
    ): ExpeditionTransaction {
        Log::info('Recording expedition cost', [
            'transaction_type' => $transactionType,
            'transaction_id' => $transaction->id,
            'expedition_id' => $expeditionData['expedition_id'],
            'expedition_cost' => $expeditionData['expedition_cost']
        ]);

        // Validate required data
        $this->validateExpeditionData($expeditionData);

        $expeditionTransaction = ExpeditionTransaction::createFromTransaction(
            $transaction,
            $transactionType,
            $expeditionData
        );

        Log::info('Expedition cost recorded', [
            'expedition_transaction_id' => $expeditionTransaction->id,
            'expedition_cost' => $expeditionTransaction->expedition_cost
        ]);

        return $expeditionTransaction;
    }

    /**
     * Get expedition cost summary for reporting
     * 
     * @param array $filters
     * @return array
     */
    public function getExpeditionCostSummary(array $filters = []): array
    {
        $query = ExpeditionTransaction::query();

        // Apply filters
        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->dateRange(
                Carbon::parse($filters['start_date']),
                Carbon::parse($filters['end_date'])
            );
        }

        if (isset($filters['expedition_id'])) {
            $query->forExpedition($filters['expedition_id']);
        }

        if (isset($filters['transaction_type'])) {
            $query->ofType($filters['transaction_type']);
        }

        if (isset($filters['zone'])) {
            $query->inZone($filters['zone']);
        }

        if (isset($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        $transactions = $query->with(['expedition'])->get();

        return [
            'total_transactions' => $transactions->count(),
            'total_cost' => $transactions->sum('expedition_cost'),
            'total_weight' => $transactions->sum('total_weight'),
            'average_cost_per_kg' => $transactions->avg('cost_per_kg'),
            'breakdown_by_expedition' => $this->groupByExpedition($transactions),
            'breakdown_by_zone' => $this->groupByZone($transactions),
            'breakdown_by_transaction_type' => $this->groupByTransactionType($transactions),
            'monthly_trend' => $this->getMonthlyTrend($transactions),
        ];
    }

    /**
     * Get available zones for an expedition
     * 
     * @param string $expeditionId
     * @return array
     */
    public function getAvailableZones(string $expeditionId): array
    {
        return ExpeditionTariff::getZonesForExpedition($expeditionId);
    }

    /**
     * Validate expedition data
     */
    private function validateExpeditionData(array $data): void
    {
        $required = ['expedition_id', 'total_weight', 'destination_address', 'expedition_cost'];

        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \InvalidArgumentException("Field '{$field}' is required for expedition transaction");
            }
        }

        if ($data['total_weight'] <= 0) {
            throw new \InvalidArgumentException("Total weight must be greater than 0");
        }

        if ($data['expedition_cost'] <= 0) {
            throw new \InvalidArgumentException("Expedition cost must be greater than 0");
        }
    }

    /**
     * Group transactions by expedition
     */
    private function groupByExpedition($transactions): array
    {
        return $transactions->groupBy('expedition.name')
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total_cost' => $group->sum('expedition_cost'),
                    'total_weight' => $group->sum('total_weight'),
                    'avg_cost_per_kg' => $group->avg('cost_per_kg'),
                ];
            })
            ->toArray();
    }

    /**
     * Group transactions by zone
     */
    private function groupByZone($transactions): array
    {
        return $transactions->groupBy('destination_zone')
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total_cost' => $group->sum('expedition_cost'),
                    'total_weight' => $group->sum('total_weight'),
                    'avg_cost_per_kg' => $group->avg('cost_per_kg'),
                ];
            })
            ->toArray();
    }

    /**
     * Group transactions by transaction type
     */
    private function groupByTransactionType($transactions): array
    {
        return $transactions->groupBy('transaction_type')
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total_cost' => $group->sum('expedition_cost'),
                    'total_weight' => $group->sum('total_weight'),
                    'avg_cost_per_kg' => $group->avg('cost_per_kg'),
                ];
            })
            ->toArray();
    }

    /**
     * Get monthly trend
     */
    private function getMonthlyTrend($transactions): array
    {
        return $transactions->groupBy(function ($transaction) {
            return $transaction->shipping_date->format('Y-m');
        })
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total_cost' => $group->sum('expedition_cost'),
                    'total_weight' => $group->sum('total_weight'),
                ];
            })
            ->toArray();
    }
}

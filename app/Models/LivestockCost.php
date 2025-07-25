<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\LivestockLockCheck;
use Carbon\Carbon;

class LivestockCost extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;
    use LivestockLockCheck;

    protected $fillable = [
        'id',
        'livestock_id',
        'tanggal',
        'recording_id', // Required - maintains data integrity
        'total_cost',
        'cost_per_ayam',
        'cost_breakdown',
        'data',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'cost_breakdown' => 'array',
        'data' => 'array', // <-- Tambahkan cast data
    ];

    public function recording()
    {
        return $this->belongsTo(Recording::class);
    }

    public function livestock()
    {
        return $this->belongsTo(Livestock::class);
    }

    public function setLastCostCalculatedAt(Carbon $timestamp)
    {
        $data = $this->data ?? [];
        $data['last_cost_calculated_at'] = $timestamp->toDateTimeString();
        $this->data = $data;
        $this->save();
    }

    /**
     * Check if this cost record was calculated with minimal recording
     */
    public function isCalculatedWithMinimalRecording(): bool
    {
        $breakdown = $this->cost_breakdown ?? [];
        return isset($breakdown['summary']['calculation_method']) &&
            str_contains($breakdown['summary']['calculation_method'], 'minimal_recording');
    }

    /**
     * Get supply usage costs for this livestock and date
     */
    public static function getSupplyUsageCostsForDate($livestockId, $tanggal): array
    {
        $supplyUsages = \App\Models\SupplyUsage::where('livestock_id', $livestockId)
            ->whereDate('usage_date', $tanggal)
            ->whereIn('status', ['completed', 'in_process'])
            ->with(['details.supply', 'details.supplyStock.supplyPurchase'])
            ->get();

        $totalCost = 0;
        $details = [];

        foreach ($supplyUsages as $usage) {
            foreach ($usage->details as $detail) {
                $supply = $detail->supply;
                $supplyStock = $detail->supplyStock;
                $supplyPurchase = $supplyStock?->supplyPurchase;

                if (!$supply || !$supplyStock || !$supplyPurchase) {
                    continue;
                }

                // Calculate cost using price per converted unit
                $pricePerUnit = $supplyPurchase->price_per_converted_unit ??
                    $supplyPurchase->price_per_unit ?? 0;
                $quantity = floatval($detail->quantity_taken);
                $subtotal = $quantity * $pricePerUnit;

                $totalCost += $subtotal;

                $details[] = [
                    'supply_name' => $supply->name,
                    'quantity' => $quantity,
                    'price_per_unit' => $pricePerUnit,
                    'subtotal' => $subtotal,
                    'usage_id' => $usage->id,
                    'detail_id' => $detail->id
                ];
            }
        }

        return [
            'total_cost' => $totalCost,
            'details' => $details,
            'usage_count' => $supplyUsages->count()
        ];
    }

    /**
     * Calculate total supply usage cost for a livestock across date range
     */
    public static function getSupplyUsageCostsForRange($livestockId, $startDate, $endDate): array
    {
        $supplyUsages = \App\Models\SupplyUsage::where('livestock_id', $livestockId)
            ->whereBetween('usage_date', [$startDate, $endDate])
            ->whereIn('status', ['completed', 'in_process'])
            ->with(['details.supply', 'details.supplyStock.supplyPurchase'])
            ->get();

        $totalCost = 0;
        $dailyBreakdown = [];

        foreach ($supplyUsages as $usage) {
            $date = $usage->usage_date->format('Y-m-d');
            $dailyCost = 0;

            foreach ($usage->details as $detail) {
                $supply = $detail->supply;
                $supplyStock = $detail->supplyStock;
                $supplyPurchase = $supplyStock?->supplyPurchase;

                if (!$supply || !$supplyStock || !$supplyPurchase) {
                    continue;
                }

                $pricePerUnit = $supplyPurchase->price_per_converted_unit ??
                    $supplyPurchase->price_per_unit ?? 0;
                $quantity = floatval($detail->quantity_taken);
                $subtotal = $quantity * $pricePerUnit;

                $totalCost += $subtotal;
                $dailyCost += $subtotal;
            }

            if (!isset($dailyBreakdown[$date])) {
                $dailyBreakdown[$date] = 0;
            }
            $dailyBreakdown[$date] += $dailyCost;
        }

        return [
            'total_cost' => $totalCost,
            'daily_breakdown' => $dailyBreakdown,
            'usage_count' => $supplyUsages->count()
        ];
    }

    /**
     * Tandai cost sebagai invalid dan catat history perubahan (hanya field yang berubah)
     */
    public function markInvalidCalculation($note = null, $oldData = null)
    {
        $data = $this->data ?? [];
        $data['invalid_calculation'] = true;
        if ($note) {
            $data['notes'] = $note;
        }
        $this->data = $data;
        $this->save();
    }

    /**
     * Helper: compute minimal diff between two arrays (flat, only top-level fields)
     */
    protected function computeMinimalDiff(array $old, array $new): array
    {
        $diff = [];
        foreach ($old as $key => $oldValue) {
            if (array_key_exists($key, $new) && $new[$key] !== $oldValue) {
                $diff[$key] = [
                    'old' => $oldValue,
                    'new' => $new[$key],
                ];
            }
        }
        // Optionally, include new fields added in $new
        // foreach ($new as $key => $newValue) {
        //     if (!array_key_exists($key, $old)) {
        //         $diff[$key] = [
        //             'old' => null,
        //             'new' => $newValue,
        //         ];
        //     }
        // }
        return $diff;
    }
}

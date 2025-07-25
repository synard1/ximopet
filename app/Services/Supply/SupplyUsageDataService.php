<?php

namespace App\Services\Supply;

use App\Models\SupplyUsage;
use App\Models\SupplyUsageDetail;
use App\Models\Farm;
use App\Models\Coop;
use App\Models\Livestock;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SupplyUsageDataService
{
    /**
     * Ambil data SupplyUsage dengan filter dinamis dan relasi lengkap.
     *
     * @param array $filters
     * @param bool $withDetails
     * @param bool $paginate
     * @param int $perPage
     * @return Collection|LengthAwarePaginator
     */
    public function getSupplyUsages(array $filters = [], bool $withDetails = true, bool $paginate = false, int $perPage = 50)
    {
        $query = SupplyUsage::query();

        // Eager load relasi
        $with = [
            'farm',
            'coop',
            'livestock',
        ];
        if ($withDetails) {
            $with[] = 'details.supply';
            $with[] = 'details.unit';
            $with[] = 'details.supplyStock';
        }
        $query->with($with);

        // Filter by company (multi-tenant)
        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        // Filter by farm
        if (!empty($filters['farm_id'])) {
            $query->forFarm($filters['farm_id']);
        }

        // Filter by coop
        if (!empty($filters['coop_id'])) {
            $query->forCoop($filters['coop_id']);
        }

        // Filter by livestock
        if (!empty($filters['livestock_id'])) {
            $query->forLivestock($filters['livestock_id']);
        }

        // Filter by date range
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->inDateRange($filters['start_date'], $filters['end_date']);
        } elseif (!empty($filters['start_date'])) {
            $query->where('usage_date', '>=', Carbon::parse($filters['start_date'])->startOfDay());
        } elseif (!empty($filters['end_date'])) {
            $query->where('usage_date', '<=', Carbon::parse($filters['end_date'])->endOfDay());
        }

        // Filter by status
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->withStatus($filters['status']);
            }
        } else {
            // Default: only active (not cancelled/rejected)
            $query->active();
        }

        // Filter by supply_id (on details)
        if (!empty($filters['supply_id'])) {
            $query->whereHas('details', function ($q) use ($filters) {
                $q->where('supply_id', $filters['supply_id']);
            });
        }

        // Search/keyword (optional, simple implementation)
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', "%$search%")
                    ->orWhereHas('farm', fn($q2) => $q2->where('name', 'like', "%$search%"))
                    ->orWhereHas('coop', fn($q2) => $q2->where('name', 'like', "%$search%"))
                    ->orWhereHas('livestock', fn($q2) => $q2->where('name', 'like', "%$search%"));
            });
        }

        // Sorting
        $sort = $filters['sort'] ?? 'usage_date';
        $order = $filters['order'] ?? 'desc';
        $query->orderBy($sort, $order);

        // Role-based access (SuperAdmin, Operator, dsb)
        $user = Auth::user();
        if ($user && !$user->hasRole('SuperAdmin')) {
            // Batasi ke company/farm yang diakses user
            if (method_exists($user, 'company_id') && empty($filters['company_id'])) {
                $query->where('company_id', $user->company_id);
            }
            // Bisa tambahkan filter farm/operator di sini jika perlu
        }

        // Pagination
        if ($paginate) {
            return $query->paginate($perPage);
        }
        return $query->get();
    }

    /**
     * Ambil breakdown/agregasi supply usage (total, per supply, per unit, dsb)
     *
     * @param array $filters
     * @return array
     */
    public function getSupplyUsageSummary(array $filters = []): array
    {
        $usages = $this->getSupplyUsages($filters, true, false);
        $summary = [
            'total_quantity' => 0,
            'total_cost' => 0,
            'by_supply' => [],
            'by_unit' => [],
        ];
        foreach ($usages as $usage) {
            foreach ($usage->details as $detail) {
                $supplyName = $detail->supply->name ?? 'Unknown';
                $unitName = $detail->unit->name ?? 'pcs';
                $qty = (float) $detail->quantity_taken;
                $cost = (float) ($detail->total_price ?? ($detail->price_per_unit * $qty));
                $summary['total_quantity'] += $qty;
                $summary['total_cost'] += $cost;
                // By supply
                if (!isset($summary['by_supply'][$supplyName])) {
                    $summary['by_supply'][$supplyName] = [
                        'quantity' => 0,
                        'cost' => 0,
                        'unit' => $unitName
                    ];
                }
                $summary['by_supply'][$supplyName]['quantity'] += $qty;
                $summary['by_supply'][$supplyName]['cost'] += $cost;
                // By unit
                if (!isset($summary['by_unit'][$unitName])) {
                    $summary['by_unit'][$unitName] = 0;
                }
                $summary['by_unit'][$unitName] += $qty;
            }
        }
        return $summary;
    }

    /**
     * Ambil detail SupplyUsageDetail langsung (misal untuk breakdown harian atau supply tertentu)
     *
     * @param array $filters
     * @return Collection
     */
    public function getSupplyUsageDetails(array $filters = []): Collection
    {
        $query = SupplyUsageDetail::query();
        $query->with(['supply', 'unit', 'supplyStock', 'supplyUsage']);
        // Filter by supply_usage_id
        if (!empty($filters['supply_usage_id'])) {
            $query->where('supply_usage_id', $filters['supply_usage_id']);
        }
        // Filter by supply_id
        if (!empty($filters['supply_id'])) {
            $query->where('supply_id', $filters['supply_id']);
        }
        // Filter by unit_id
        if (!empty($filters['unit_id'])) {
            $query->where('unit_id', $filters['unit_id']);
        }
        // Filter by date (via supplyUsage)
        if (!empty($filters['start_date']) || !empty($filters['end_date'])) {
            $query->whereHas('supplyUsage', function ($q) use ($filters) {
                if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                    $q->whereBetween('usage_date', [
                        Carbon::parse($filters['start_date'])->startOfDay(),
                        Carbon::parse($filters['end_date'])->endOfDay()
                    ]);
                } elseif (!empty($filters['start_date'])) {
                    $q->where('usage_date', '>=', Carbon::parse($filters['start_date'])->startOfDay());
                } elseif (!empty($filters['end_date'])) {
                    $q->where('usage_date', '<=', Carbon::parse($filters['end_date'])->endOfDay());
                }
            });
        }
        // Filter by farm/coop/livestock (via supplyUsage)
        if (!empty($filters['farm_id'])) {
            $query->whereHas('supplyUsage', fn($q) => $q->where('farm_id', $filters['farm_id']));
        }
        if (!empty($filters['coop_id'])) {
            $query->whereHas('supplyUsage', fn($q) => $q->where('coop_id', $filters['coop_id']));
        }
        if (!empty($filters['livestock_id'])) {
            $query->whereHas('supplyUsage', fn($q) => $q->where('livestock_id', $filters['livestock_id']));
        }
        // Status (via supplyUsage)
        if (!empty($filters['status'])) {
            $query->whereHas('supplyUsage', function ($q) use ($filters) {
                if (is_array($filters['status'])) {
                    $q->whereIn('status', $filters['status']);
                } else {
                    $q->where('status', $filters['status']);
                }
            });
        } else {
            $query->whereHas('supplyUsage', fn($q) => $q->active());
        }
        // Sorting
        $sort = $filters['sort'] ?? 'created_at';
        $order = $filters['order'] ?? 'desc';
        $query->orderBy($sort, $order);
        return $query->get();
    }
}

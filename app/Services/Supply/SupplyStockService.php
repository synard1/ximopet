<?php

namespace App\Services\Supply;

use App\Models\CurrentSupply;
use App\Models\SupplyStock;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class SupplyStockService
{
    /**
     * Get available supply stocks for a given farm and user (role-based, modular, reusable)
     *
     * @param int|string $farmId
     * @param \App\Models\User $user
     * @param string|null $usageDate (optional, default now)
     * @return \Illuminate\Support\Collection
     */
    public function getAvailableSupplyStocks($farmId, $user, $usageDate = null)
    {
        $companyId   = $user->company_id;
        $isSuperAdmin = $user->hasRole('SuperAdmin');
        $usageDate   = $usageDate ? Carbon::parse($usageDate) : now();

        // SupplyStock only (more reliable for stock picking)
        $query = SupplyStock::where('farm_id', $farmId)
            ->where('date', '<=', $usageDate)
            ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
            ->with('supply');

        if (!$isSuperAdmin) {
            $query->whereHas('supply', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            });
        }

        $result = $query->get();

        Log::debug('SupplyStockService@SupplyStock result', [
            'farm_id' => $farmId,
            'count'   => $result->count(),
            'ids'     => $result->pluck('id'),
        ]);

        return $result;
    }
}
 
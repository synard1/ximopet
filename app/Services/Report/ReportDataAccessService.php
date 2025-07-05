<?php

namespace App\Services\Report;

use App\Models\Livestock;
use App\Models\Farm;
use App\Models\Coop;
use App\Models\Ternak;
use App\Models\Kandang;
use App\Models\Partner;
use App\Models\Expedition;
use App\Models\Feed;
use App\Models\Supply;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Service untuk menangani akses data report berdasarkan role user
 * SuperAdmin dapat melihat semua data, user lain dibatasi berdasarkan company_id
 */
class ReportDataAccessService
{
    /**
     * Check if current user is SuperAdmin
     *
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        $user = Auth::user();
        return $user && $user->hasRole('SuperAdmin');
    }

    /**
     * Get company filter closure based on user role
     *
     * @param string $columnName
     * @return \Closure|null
     */
    public function getCompanyFilter(string $columnName = 'company_id'): ?\Closure
    {
        $user = Auth::user();

        // SuperAdmin can see all data
        if ($this->isSuperAdmin()) {
            Log::info('SuperAdmin access granted - no company filter applied', [
                'user_id' => $user->id,
                'column' => $columnName
            ]);
            return null; // No filter needed
        }

        // Other users are restricted to their company
        if ($user && $user->company_id) {
            Log::debug('Company filter applied', [
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'column' => $columnName
            ]);

            return function ($query) use ($user, $columnName) {
                $query->where($columnName, $user->company_id);
            };
        }

        // Fallback: no access if no company_id
        Log::warning('No company access - returning empty results', [
            'user_id' => $user ? $user->id : null,
            'column' => $columnName
        ]);

        return function ($query) {
            $query->where('id', 0); // No results
        };
    }

    /**
     * Apply company filter to query builder
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $columnName
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function applyCompanyFilter($query, string $columnName = 'company_id')
    {
        $filter = $this->getCompanyFilter($columnName);

        if ($filter) {
            $query->where($filter);
        }

        return $query;
    }

    /**
     * Get livestock data with company filtering
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getLivestock()
    {
        $query = Livestock::query();
        return $this->applyCompanyFilter($query)->get();
    }

    /**
     * Get farms based on livestock IDs with company filtering
     *
     * @param array $livestockIds
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFarmsByLivestock(array $livestockIds)
    {
        $query = Farm::whereIn('id', $livestockIds);
        return $this->applyCompanyFilter($query)->get();
    }

    /**
     * Get coops based on livestock IDs with company filtering
     *
     * @param array $livestockIds
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCoopsByLivestock(array $livestockIds)
    {
        $query = Coop::whereIn('id', $livestockIds);
        return $this->applyCompanyFilter($query)->get();
    }

    /**
     * Get legacy ternak data with company filtering
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTernak()
    {
        $query = Ternak::query();
        return $this->applyCompanyFilter($query)->get();
    }

    /**
     * Get kandang based on ternak IDs with company filtering
     *
     * @param array $ternakIds
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getKandangByTernak(array $ternakIds)
    {
        $query = Kandang::whereIn('id', $ternakIds);
        return $this->applyCompanyFilter($query)->get();
    }

    /**
     * Get farms with company filtering
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFarms()
    {
        $query = Farm::query();
        return $this->applyCompanyFilter($query)->get();
    }

    /**
     * Get suppliers with company filtering
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSuppliers()
    {
        $query = Partner::where('type', 'Supplier');
        return $this->applyCompanyFilter($query)->get();
    }

    /**
     * Get expeditions with company filtering
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getExpeditions()
    {
        $query = Expedition::query();
        return $this->applyCompanyFilter($query)->get();
    }

    /**
     * Get feeds with company filtering
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFeeds()
    {
        $query = Feed::query();
        return $this->applyCompanyFilter($query)->get();
    }

    /**
     * Get supplies with company filtering
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSupplies()
    {
        $query = Supply::query();
        return $this->applyCompanyFilter($query)->get();
    }

    /**
     * Transform livestock data for view
     *
     * @param \Illuminate\Database\Eloquent\Collection $livestock
     * @return array
     */
    public function transformLivestockForView($livestock): array
    {
        return $livestock->map(function ($item) {
            return [
                'id' => $item->id,
                'farm_id' => $item->farm_id,
                'farm_name' => $item->farm->name,
                'coop_id' => $item->coop_id,
                'coop_name' => $item->coop->name,
                'name' => $item->name,
                'start_date' => $item->start_date,
                'year' => $item->start_date->format('Y'),
            ];
        })->toArray();
    }

    /**
     * Transform ternak data for view
     *
     * @param \Illuminate\Database\Eloquent\Collection $ternak
     * @return array
     */
    public function transformTernakForView($ternak): array
    {
        return $ternak->map(function ($item) {
            $allData = isset($item->data[0]['administrasi']) ? $item->data[0]['administrasi'] : [];

            return [
                'id' => $item->id,
                'farm_id' => $item->farm_id,
                'farm_name' => $item->farm->nama,
                'kandang_id' => $item->kandang_id,
                'kandang_name' => $item->kandang->nama,
                'name' => $item->name,
                'start_date' => $item->start_date,
                'year' => $item->start_date->format('Y'),
                'tanggal_surat' => $allData['tanggal_laporan'] ?? null,
            ];
        })->toArray();
    }

    /**
     * Log data access for audit purposes
     *
     * @param string $reportType
     * @param array $counts
     * @return void
     */
    public function logDataAccess(string $reportType, array $counts): void
    {
        Log::info("Report data access: {$reportType}", array_merge([
            'user_id' => Auth::id(),
            'is_super_admin' => $this->isSuperAdmin(),
            'company_id' => Auth::user()->company_id ?? null,
        ], $counts));
    }
}

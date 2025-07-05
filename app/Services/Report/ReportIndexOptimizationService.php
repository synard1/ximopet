<?php

namespace App\Services\Report;

use App\Models\Livestock;
use App\Models\Ternak;
use App\Models\Farm;
use App\Models\Coop;
use App\Models\Kandang;
use App\Models\Partner;
use App\Models\Expedition;
use App\Models\Feed;
use App\Models\Supply;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ReportIndexOptimizationService
{
    protected $dataAccessService;

    public function __construct(ReportDataAccessService $dataAccessService)
    {
        $this->dataAccessService = $dataAccessService;
    }

    /**
     * Prepare common index data for livestock-based reports
     * 
     * @param string $modelType 'livestock' or 'ternak'
     * @return array
     */
    public function prepareCommonIndexData($modelType = 'livestock')
    {
        try {
            if ($modelType === 'livestock') {
                return $this->prepareLivestockIndexData();
            } else {
                return $this->prepareTernakIndexData();
            }
        } catch (\Exception $e) {
            Log::error('Error preparing livestock index data: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Prepare index data for Livestock model
     * 
     * @return array
     */
    protected function prepareLivestockIndexData()
    {
        $livestock = Livestock::query();
        $this->dataAccessService->applyCompanyFilter($livestock);
        $livestock = $livestock->get();

        $farms = Farm::whereIn('id', $livestock->pluck('farm_id'));
        $this->dataAccessService->applyCompanyFilter($farms);
        $farms = $farms->get();

        $coops = Coop::whereIn('id', $livestock->pluck('coop_id'));
        $this->dataAccessService->applyCompanyFilter($coops);
        $coops = $coops->get();

        $ternak = $livestock->map(function ($item) {
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

        return [
            'farms' => $farms,
            'coops' => $coops,
            'ternak' => $ternak
        ];
    }

    /**
     * Prepare index data for Ternak model
     * 
     * @return array
     */
    protected function prepareTernakIndexData()
    {
        $kelompokTernak = Ternak::query();
        $this->dataAccessService->applyCompanyFilter($kelompokTernak);
        $kelompokTernak = $kelompokTernak->get();

        $farms = Farm::whereIn('id', $kelompokTernak->pluck('farm_id'));
        $this->dataAccessService->applyCompanyFilter($farms);
        $farms = $farms->get();

        $kandangs = Kandang::whereIn('id', $kelompokTernak->pluck('kandang_id'));
        $this->dataAccessService->applyCompanyFilter($kandangs);
        $kandangs = $kandangs->get();

        $ternak = $kelompokTernak->map(function ($item) {
            return [
                'id' => $item->id,
                'farm_id' => $item->farm_id,
                'farm_name' => $item->farm->nama,
                'kandang_id' => $item->kandang_id,
                'kandang_name' => $item->kandang->nama,
                'name' => $item->name,
                'start_date' => $item->start_date,
                'year' => $item->start_date->format('Y'),
            ];
        })->toArray();

        return [
            'farms' => $farms,
            'kandangs' => $kandangs,
            'ternak' => $ternak
        ];
    }

    /**
     * Prepare index data for Ternak model with additional data
     * 
     * @return array
     */
    public function prepareTernakIndexDataWithAdditional()
    {
        try {
            $kelompokTernak = Ternak::query();
            $this->dataAccessService->applyCompanyFilter($kelompokTernak);
            $kelompokTernak = $kelompokTernak->get();

            $farms = Farm::whereIn('id', $kelompokTernak->pluck('farm_id'));
            $this->dataAccessService->applyCompanyFilter($farms);
            $farms = $farms->get();

            $kandangs = Kandang::whereIn('id', $kelompokTernak->pluck('kandang_id'));
            $this->dataAccessService->applyCompanyFilter($kandangs);
            $kandangs = $kandangs->get();

            $ternak = $kelompokTernak->map(function ($item) {
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

            return [
                'farms' => $farms,
                'kandangs' => $kandangs,
                'ternak' => $ternak
            ];
        } catch (\Exception $e) {
            Log::error('Error preparing ternak index data with additional: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Prepare index data for Livestock model with additional data
     * 
     * @return array
     */
    public function prepareLivestockIndexDataWithAdditional()
    {
        try {
            $livestock = Livestock::query();
            $this->dataAccessService->applyCompanyFilter($livestock);
            $livestock = $livestock->get();

            $farms = Farm::whereIn('id', $livestock->pluck('farm_id'));
            $this->dataAccessService->applyCompanyFilter($farms);
            $farms = $farms->get();

            $coops = Coop::whereIn('id', $livestock->pluck('coop_id'));
            $this->dataAccessService->applyCompanyFilter($coops);
            $coops = $coops->get();

            $ternak = $livestock->map(function ($item) {
                $allData = isset($item->data[0]['administrasi']) ? $item->data[0]['administrasi'] : [];

                return [
                    'id' => $item->id,
                    'farm_id' => $item->farm_id,
                    'farm_name' => $item->farm->name,
                    'coop_id' => $item->coop_id,
                    'coop_name' => $item->coop->name,
                    'name' => $item->name,
                    'start_date' => $item->start_date,
                    'year' => $item->start_date->format('Y'),
                    'tanggal_surat' => $allData['tanggal_laporan'] ?? null,
                ];
            })->toArray();

            return [
                'farms' => $farms,
                'coops' => $coops,
                'ternak' => $ternak
            ];
        } catch (\Exception $e) {
            Log::error('Error preparing livestock index data with additional: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Prepare index data for inventory report
     * 
     * @return array
     */
    public function prepareInventoryIndexData()
    {
        try {
            $kelompokTernak = Ternak::query();
            $this->dataAccessService->applyCompanyFilter($kelompokTernak);
            $kelompokTernak = $kelompokTernak->get();

            $farms = Farm::whereIn('id', $kelompokTernak->pluck('farm_id'));
            $this->dataAccessService->applyCompanyFilter($farms);
            $farms = $farms->get();

            $ternak = $kelompokTernak->map(function ($item) {
                $allData = $item->data ? json_decode($item->data, true) : [];

                return [
                    'id' => $item->id,
                    'farm_id' => $item->farm_id,
                    'farm_name' => $item->farm->nama,
                    'kandang_id' => $item->kandang_id,
                    'kandang_name' => $item->kandang->nama,
                    'name' => $item->name,
                    'start_date' => $item->start_date,
                    'year' => $item->start_date->format('Y'),
                    'tanggal_surat' => $allData['administrasi']['tanggal_laporan'] ?? null,
                ];
            })->toArray();

            return [
                'farms' => $farms,
                'ternak' => $ternak
            ];
        } catch (\Exception $e) {
            Log::error('Error preparing inventory index data: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Prepare index data for purchase reports
     * 
     * @param string $type 'livestock', 'feed', or 'supply'
     * @return array
     */
    public function preparePurchaseIndexData($type = 'livestock')
    {
        try {
            $farms = Farm::query();
            $this->dataAccessService->applyCompanyFilter($farms);
            $farms = $farms->get();

            $partners = Partner::where('type', 'Supplier');
            $this->dataAccessService->applyCompanyFilter($partners);
            $partners = $partners->get();

            $expeditions = Expedition::query();
            $this->dataAccessService->applyCompanyFilter($expeditions);
            $expeditions = $expeditions->get();

            $data = [
                'farms' => $farms,
                'partners' => $partners,
                'expeditions' => $expeditions
            ];

            // Add type-specific data
            if ($type === 'feed') {
                $feeds = Feed::query();
                $this->dataAccessService->applyCompanyFilter($feeds);
                $data['feeds'] = $feeds->get();
            } elseif ($type === 'supply') {
                $supplies = Supply::query();
                $this->dataAccessService->applyCompanyFilter($supplies);
                $data['supplies'] = $supplies->get();
            }

            Log::info("Purchase Report Index accessed for type: {$type}", [
                'user_id' => Auth::id(),
                'farms_count' => $farms->count(),
                'partners_count' => $partners->count(),
                'expeditions_count' => $expeditions->count()
            ]);

            return $data;
        } catch (\Exception $e) {
            Log::error("Error preparing purchase index data for type {$type}: " . $e->getMessage());
            throw $e;
        }
    }
}

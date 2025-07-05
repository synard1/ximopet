<?php

namespace App\Services\Report;

use App\Services\Report\ReportDataAccessService;

/**
 * Service untuk menangani business logic report index pages
 * Memisahkan logic dari controller untuk maintainability yang lebih baik
 */
class ReportIndexService
{
    protected $dataAccessService;

    public function __construct(ReportDataAccessService $dataAccessService)
    {
        $this->dataAccessService = $dataAccessService;
    }

    /**
     * Prepare data for Harian report index
     *
     * @return array
     */
    public function prepareHarianReportData(): array
    {
        $livestock = $this->dataAccessService->getLivestock();
        $farms = $this->dataAccessService->getFarmsByLivestock($livestock->pluck('farm_id')->toArray());
        $coops = $this->dataAccessService->getCoopsByLivestock($livestock->pluck('coop_id')->toArray());

        $livestockForView = $this->dataAccessService->transformLivestockForView($livestock);

        $this->dataAccessService->logDataAccess('harian_index', [
            'livestock_count' => $livestock->count(),
            'farms_count' => $farms->count(),
            'coops_count' => $coops->count()
        ]);

        return compact('farms', 'coops', 'livestockForView');
    }

    /**
     * Prepare data for Batch Worker report index
     *
     * @return array
     */
    public function prepareBatchWorkerReportData(): array
    {
        $livestock = $this->dataAccessService->getLivestock();
        $farms = $this->dataAccessService->getFarmsByLivestock($livestock->pluck('farm_id')->toArray());
        $coops = $this->dataAccessService->getCoopsByLivestock($livestock->pluck('coop_id')->toArray());

        $livestockForView = $this->dataAccessService->transformLivestockForView($livestock);

        $this->dataAccessService->logDataAccess('batch_worker_index', [
            'livestock_count' => $livestock->count(),
            'farms_count' => $farms->count(),
            'coops_count' => $coops->count()
        ]);

        return compact('farms', 'coops', 'livestockForView');
    }

    /**
     * Prepare data for Daily Cost report index
     *
     * @return array
     */
    public function prepareDailyCostReportData(): array
    {
        $livestock = $this->dataAccessService->getLivestock();
        $farms = $this->dataAccessService->getFarmsByLivestock($livestock->pluck('farm_id')->toArray());
        $coops = $this->dataAccessService->getCoopsByLivestock($livestock->pluck('coop_id')->toArray());

        $ternakForView = $this->dataAccessService->transformLivestockForView($livestock);

        $this->dataAccessService->logDataAccess('daily_cost_index', [
            'livestock_count' => $livestock->count(),
            'farms_count' => $farms->count(),
            'coops_count' => $coops->count()
        ]);

        return compact('farms', 'coops', 'ternakForView');
    }

    /**
     * Prepare data for Penjualan report index
     *
     * @return array
     */
    public function preparePenjualanReportData(): array
    {
        $kelompokTernak = $this->dataAccessService->getTernak();
        $farms = $this->dataAccessService->getFarmsByLivestock($kelompokTernak->pluck('farm_id')->toArray());
        $kandangs = $this->dataAccessService->getKandangByTernak($kelompokTernak->pluck('kandang_id')->toArray());

        $ternakForView = $this->dataAccessService->transformTernakForView($kelompokTernak);

        $this->dataAccessService->logDataAccess('penjualan_index', [
            'ternak_count' => $kelompokTernak->count(),
            'farms_count' => $farms->count(),
            'kandangs_count' => $kandangs->count()
        ]);

        return compact('farms', 'kandangs', 'ternakForView');
    }

    /**
     * Prepare data for Performance Mitra report index
     *
     * @return array
     */
    public function preparePerformaMitraReportData(): array
    {
        $kelompokTernak = $this->dataAccessService->getTernak();
        $farms = $this->dataAccessService->getFarmsByLivestock($kelompokTernak->pluck('farm_id')->toArray());
        $kandangs = $this->dataAccessService->getKandangByTernak($kelompokTernak->pluck('kandang_id')->toArray());

        $ternakForView = $this->dataAccessService->transformTernakForView($kelompokTernak);

        $this->dataAccessService->logDataAccess('performa_mitra_index', [
            'ternak_count' => $kelompokTernak->count(),
            'farms_count' => $farms->count(),
            'kandangs_count' => $kandangs->count()
        ]);

        return compact('farms', 'kandangs', 'ternakForView');
    }

    /**
     * Prepare data for Performance report index
     *
     * @return array
     */
    public function preparePerformaReportData(): array
    {
        $livestock = $this->dataAccessService->getLivestock();
        $farms = $this->dataAccessService->getFarmsByLivestock($livestock->pluck('farm_id')->toArray());
        $coops = $this->dataAccessService->getCoopsByLivestock($livestock->pluck('coop_id')->toArray());

        $ternakForView = $this->dataAccessService->transformLivestockForView($livestock);

        $this->dataAccessService->logDataAccess('performa_index', [
            'livestock_count' => $livestock->count(),
            'farms_count' => $farms->count(),
            'coops_count' => $coops->count()
        ]);

        return compact('farms', 'coops', 'ternakForView');
    }

    /**
     * Prepare data for Inventory report index
     *
     * @return array
     */
    public function prepareInventoryReportData(): array
    {
        $kelompokTernak = $this->dataAccessService->getTernak();
        $farms = $this->dataAccessService->getFarmsByLivestock($kelompokTernak->pluck('farm_id')->toArray());

        $ternakForView = $kelompokTernak->map(function ($item) {
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

        $this->dataAccessService->logDataAccess('inventory_index', [
            'ternak_count' => $kelompokTernak->count(),
            'farms_count' => $farms->count()
        ]);

        return compact('farms', 'ternakForView');
    }

    /**
     * Prepare data for Livestock Purchase report index
     *
     * @return array
     */
    public function preparePembelianLivestockData(): array
    {
        $farms = $this->dataAccessService->getFarms();
        $partners = $this->dataAccessService->getSuppliers();
        $expeditions = $this->dataAccessService->getExpeditions();

        $this->dataAccessService->logDataAccess('pembelian_livestock_index', [
            'farms_count' => $farms->count(),
            'partners_count' => $partners->count(),
            'expeditions_count' => $expeditions->count()
        ]);

        return compact('farms', 'partners', 'expeditions');
    }

    /**
     * Prepare data for Feed Purchase report index
     *
     * @return array
     */
    public function preparePembelianPakanData(): array
    {
        $farms = $this->dataAccessService->getFarms();
        $partners = $this->dataAccessService->getSuppliers();
        $expeditions = $this->dataAccessService->getExpeditions();
        $feeds = $this->dataAccessService->getFeeds();

        $this->dataAccessService->logDataAccess('pembelian_pakan_index', [
            'farms_count' => $farms->count(),
            'partners_count' => $partners->count(),
            'expeditions_count' => $expeditions->count(),
            'feeds_count' => $feeds->count()
        ]);

        return compact('farms', 'partners', 'expeditions', 'feeds');
    }

    /**
     * Prepare data for Supply Purchase report index
     *
     * @return array
     */
    public function preparePembelianSupplyData(): array
    {
        $farms = $this->dataAccessService->getFarms();
        $partners = $this->dataAccessService->getSuppliers();
        $expeditions = $this->dataAccessService->getExpeditions();
        $supplies = $this->dataAccessService->getSupplies();

        $this->dataAccessService->logDataAccess('pembelian_supply_index', [
            'farms_count' => $farms->count(),
            'partners_count' => $partners->count(),
            'expeditions_count' => $expeditions->count(),
            'supplies_count' => $supplies->count()
        ]);

        return compact('farms', 'partners', 'expeditions', 'supplies');
    }
}

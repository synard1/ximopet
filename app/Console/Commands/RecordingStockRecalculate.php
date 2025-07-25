<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Recording\RecordingStockRecalculationService;

class RecordingStockRecalculate extends Command
{
    protected $signature = 'recording:stock-recalculate
        {--livestock= : Livestock ID}
        {--farm= : Farm ID}
        {--coop= : Coop ID}
        {--company= : Company ID}
        {--start= : Start date (Y-m-d)}
        {--end= : End date (Y-m-d)}
        {--dry-run : Preview only, do not update DB}
        ';

    protected $description = 'Recalculate stock_awal and stock_akhir for recordings with various filters (livestock, farm, coop, company, date range).';

    public function handle()
    {
        $service = app(RecordingStockRecalculationService::class);
        $filters = [];
        $options = [];
        if ($this->option('livestock')) $filters['livestock_id'] = $this->option('livestock');
        if ($this->option('farm')) $filters['farm_id'] = $this->option('farm');
        if ($this->option('coop')) $filters['coop_id'] = $this->option('coop');
        if ($this->option('company')) $filters['company_id'] = $this->option('company');
        if ($this->option('start')) $filters['start_date'] = $this->option('start');
        if ($this->option('end')) $filters['end_date'] = $this->option('end');
        if ($this->option('dry-run')) $options['dry_run'] = true;

        $this->info('Starting stock recalculation...');
        $result = null;
        try {
            if (isset($filters['livestock_id'])) {
                $result = $service->recalculateForLivestock($filters['livestock_id'], $filters['start_date'] ?? null, $filters['end_date'] ?? null, $options);
            } elseif (isset($filters['farm_id'])) {
                $result = $service->recalculateForFarm($filters['farm_id'], $filters['start_date'] ?? null, $filters['end_date'] ?? null, $options);
            } elseif (isset($filters['coop_id'])) {
                $result = $service->recalculateForCoop($filters['coop_id'], $filters['start_date'] ?? null, $filters['end_date'] ?? null, $options);
            } else {
                $result = $service->recalculateAll($filters, $options);
            }
        } catch (\Throwable $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
        if ($result['error']) {
            $this->error('Error: ' . $result['error']);
            return 1;
        }
        $this->info("Records updated: {$result['updated']}");
        if (!empty($result['log'])) {
            $this->line('--- Change Log ---');
            foreach ($result['log'] as $log) {
                $this->line(
                    "[{$log['tanggal']}] ID:{$log['id']} stock_awal: {$log['old_stock_awal']}→{$log['new_stock_awal']} stock_akhir: {$log['old_stock_akhir']}→{$log['new_stock_akhir']}"
                );
            }
        }
        $this->info('Done.');
        return 0;
    }
}

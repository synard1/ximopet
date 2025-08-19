<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Services\Recording\Contracts\RecordingDataServiceInterface;

class DebugRecordingYesterday extends Command
{
    protected $signature = 'recording:debug:yesterday {livestock_id} {date} {--bypass-cache}';
    protected $description = 'Debug yesterday data loading and summary extraction for Records module';

    public function handle()
    {
        $livestockId = $this->argument('livestock_id');
        $date = $this->argument('date');
        $bypass = (bool) $this->option('bypass-cache');

        $yesterday = Carbon::parse($date)->subDay()->format('Y-m-d');
        $this->info("Testing Yesterday Data Logic");
        $this->line("- Livestock: {$livestockId}");
        $this->line("- Selected Date: {$date}");
        $this->line("- Yesterday: {$yesterday}");
        $this->line("- Bypass Cache: " . ($bypass ? 'yes' : 'no'));

        /** @var RecordingDataServiceInterface $svc */
        $svc = app(RecordingDataServiceInterface::class);

        $this->line("\n1) loadYesterdayData(yesterday)");
        $r1 = $svc->loadYesterdayData($livestockId, $yesterday);
        if ($r1 && method_exists($r1, 'isSuccess') && $r1->isSuccess()) {
            $data = $r1->getData();
            $this->table(['Key', 'Value'], [
                ['weight', $data['weight'] ?? null],
                ['mortality', $data['mortality'] ?? null],
                ['culling', $data['culling'] ?? null],
                ['feed.total_quantity', $data['feed_usage']['total_quantity'] ?? null],
                ['supply.total_quantity', $data['supply_usage']['total_quantity'] ?? null],
                ['sales.quantity', $data['sales']['quantity'] ?? null],
                ['sales.weight', $data['sales']['weight'] ?? null],
            ]);
        } else {
            $this->error('loadYesterdayData failed: ' . ($r1 ? $r1->getMessage() : 'no result'));
        }

        $this->line("\n2) loadCurrentDateData(yesterday)");
        $r2 = $svc->loadCurrentDateData($livestockId, $yesterday, $bypass);
        if ($r2 && method_exists($r2, 'isSuccess') && $r2->isSuccess()) {
            $d2 = $r2->getData();
            $payload = $d2['payload'] ?? [];
            $itemQuantities = $d2['itemQuantities'] ?? [];
            $totalItems = 0;
            if (is_array($itemQuantities)) {
                foreach ($itemQuantities as $qty) {
                    $totalItems += (float) $qty;
                }
            }
            $this->table(['Path', 'Value'], [
                ['payload.production.weight.today', $payload['production']['weight']['today'] ?? null],
                ['payload.production.depletion.mortality', $payload['production']['depletion']['mortality'] ?? null],
                ['payload.production.depletion.culling', $payload['production']['depletion']['culling'] ?? null],
                ['payload.consumption.feed.total_quantity', $payload['consumption']['feed']['total_quantity'] ?? null],
                ['payload.consumption.feed.cumulative_feed_consumption', $payload['consumption']['feed']['cumulative_feed_consumption'] ?? null],
                ['payload.production.sales.quantity', $payload['production']['sales']['quantity'] ?? null],
                ['payload.production.sales.weight', $payload['production']['sales']['weight'] ?? null],
                ['payload.weight_today (root)', $payload['weight_today'] ?? null],
                ['payload.mortality (root)', $payload['mortality'] ?? null],
                ['payload.culling (root)', $payload['culling'] ?? null],
                ['itemQuantities.total (sum)', $totalItems],
            ]);
        } else {
            $this->error('loadCurrentDateData (yesterday) failed: ' . ($r2 ? $r2->getMessage() : 'no result'));
        }

        $this->info("\nDone.");
        return 0;
    }
}

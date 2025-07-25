<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Supply\SupplyUsageDataService;
use App\Models\Farm;
use App\Models\Livestock;
use Carbon\Carbon;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class GenerateSupplyUsageReport extends Command
{
    protected $signature = 'report:supply-usage
        {--farm= : Farm ID}
        {--livestock= : Livestock ID}
        {--start= : Start date (Y-m-d)}
        {--end= : End date (Y-m-d)}
        {--supply= : Supply ID (optional)}
        {--company= : Company ID (optional)}
        {--test : Test mode (dummy header) }';

    protected $description = 'Generate Supply Usage Report Analysis (CLI, colored, summary, breakdown, sample daily records)';

    public function handle(SupplyUsageDataService $service)
    {
        $output = $this->output;
        $output->getFormatter()->setStyle('title', new OutputFormatterStyle('yellow', null, ['bold']));
        $output->getFormatter()->setStyle('section', new OutputFormatterStyle('cyan', null, ['bold']));
        $output->getFormatter()->setStyle('label', new OutputFormatterStyle('green', null, ['bold']));
        $output->getFormatter()->setStyle('value', new OutputFormatterStyle('white', null, ['bold']));
        $output->getFormatter()->setStyle('dim', new OutputFormatterStyle('gray'));
        $output->getFormatter()->setStyle('error', new OutputFormatterStyle('red', null, ['bold']));

        $farmId = $this->option('farm');
        $livestockId = $this->option('livestock');
        $start = $this->option('start') ?? now()->subMonth()->format('Y-m-d');
        $end = $this->option('end') ?? now()->format('Y-m-d');
        $supplyId = $this->option('supply');
        $companyId = $this->option('company');
        $testMode = $this->option('test');

        $filters = [
            'farm_id' => $farmId,
            'livestock_id' => $livestockId,
            'start_date' => $start,
            'end_date' => $end,
            'supply_id' => $supplyId,
            'company_id' => $companyId,
        ];

        // Header
        $output->writeln('');
        $output->writeln('<title>🧪 TEST MODE - Supply Usage Report Analysis</title>');
        $output->writeln('');
        $output->writeln('<section>📄 Report Overview</section>');

        // Farm & Livestock Info
        $farm = $farmId ? Farm::find($farmId) : null;
        $livestock = $livestockId ? Livestock::find($livestockId) : null;
        $output->writeln('Farm: <value>' . ($farm ? $farm->name : '-') . '</value>');
        $output->writeln('Livestock: <value>' . ($livestock ? $livestock->name : '-') . '</value>');
        $output->writeln('Start Date: <value>' . $start . '</value>');
        $output->writeln('End Date: <value>' . $end . '</value>');
        $output->writeln('Supply Filter: <value>' . ($supplyId ?: '-') . '</value>');
        $output->writeln('');

        // Summary
        $summary = $service->getSupplyUsageSummary($filters);
        $output->writeln('<section>📊 Overall Totals</section>');
        $output->writeln('Total Supply Quantity: <value>' . number_format($summary['total_quantity']) . '</value>');
        $output->writeln('Total Supply Cost: <value>Rp ' . number_format($summary['total_cost']) . '</value>');
        $output->writeln('');

        // Breakdown by supply
        $output->writeln('<section>🧾 Supply Breakdown</section>');
        foreach ($summary['by_supply'] as $supply => $data) {
            $output->writeln('  <label>' . $supply . '</label>: <value>' . number_format($data['quantity']) . ' ' . $data['unit'] . '</value> | Cost: <value>Rp ' . number_format($data['cost']) . '</value>');
        }
        $output->writeln('');

        // Breakdown by unit
        $output->writeln('<section>📦 Unit Breakdown</section>');
        foreach ($summary['by_unit'] as $unit => $qty) {
            $output->writeln('  <label>' . $unit . '</label>: <value>' . number_format($qty) . '</value>');
        }
        $output->writeln('');

        // Sample Daily Records (First 5)
        $output->writeln('<section>📅 Sample Daily Records (First 5)</section>');
        $usages = $service->getSupplyUsages($filters, true, false);
        $daily = [];
        foreach ($usages as $usage) {
            $date = $usage->usage_date->format('Y-m-d');
            if (!isset($daily[$date])) {
                $daily[$date] = [];
            }
            foreach ($usage->details as $detail) {
                $daily[$date][] = [
                    'supply' => $detail->supply->name ?? '-',
                    'qty' => $detail->quantity_taken,
                    'unit' => $detail->unit->name ?? '-',
                    'cost' => $detail->total_price ?? ($detail->price_per_unit * $detail->quantity_taken),
                ];
            }
        }
        $dates = array_slice(array_keys($daily), 0, 5);
        if (empty($dates)) {
            $output->writeln('<dim>No daily records found.</dim>');
        } else {
            foreach ($dates as $i => $date) {
                $output->writeln('');
                $output->writeln('<label>Day ' . ($i + 1) . ': ' . $date . '</label>');
                if (empty($daily[$date])) {
                    $output->writeln('  <dim>Supply Usage: No data</dim>');
                } else {
                    foreach ($daily[$date] as $row) {
                        $output->writeln('  <label>' . $row['supply'] . '</label>: <value>' . number_format($row['qty']) . ' ' . $row['unit'] . '</value> | Cost: <value>Rp ' . number_format($row['cost']) . '</value>');
                    }
                }
            }
        }
        $output->writeln('');
        $output->writeln('<dim>Report generated at ' . now()->format('Y-m-d H:i:s') . '</dim>');
        $output->writeln('');
        return 0;
    }
}

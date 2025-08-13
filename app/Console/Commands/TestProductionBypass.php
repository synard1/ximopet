<?php

namespace App\Console\Commands;

use App\Helpers\DataChangeDetector;
use Illuminate\Console\Command;

class TestProductionBypass extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bypass:test-production';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test production bypass with date bypass logic';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Testing Production Bypass with Date Bypass Logic...');

        // Simulate Livewire showEditForm snapshot
        $snapshot = [
            'invoice_number' => '00001',
            'date' => '2025-07-01 00:00:00',
            'supplier_id' => '9f46f024-591e-40f2-b6ce-04f2b149f799',
            'farm_id' => '9f46ed24-ed84-4746-ada7-ad0747328e92',
            'coop_id' => '9f4d29d2-9142-4191-8930-456fcfa75d32',
            'expedition_id' => '9f46f05f-1df2-4aa7-9263-d7aae9d2d3e5',
            'expedition_fee' => '250000.00',
            'batch_name' => 'PR-Farm01-K3F1-01072025',
            'status' => 'draft',
            'items' => [[
                'livestock_id' => null,
                'livestock_strain_id' => '9f4744c1-d62d-4ece-a5b4-e76621ed0398',
                'quantity' => 5000,
                'price_value' => '7800.00',
                'price_type' => 'per_unit',
                'farm_id' => '9f46ed24-ed84-4746-ada7-ad0747328e92',
                'coop_id' => '9f4d29d2-9142-4191-8930-456fcfa75d32',
                'livestock_strain_standard_id' => null,
                'start_date' => '2025-07-01 00:00:00',
                'weight_type' => 'per_unit',
                'weight_value' => '40.00',
            ]],
        ];

        // Case A: Current form is identical to snapshot
        $current = $snapshot;
        $excluded = [
            'updated_at','created_at','id','pembelianId','edit_mode','showForm','availableKandangs','errorItems','validationErrors',
            'sub_total','total_quantity','total_weight','start_date','livestock_id','livestock_strain_standard_id','farm_id','coop_id'
        ];

        $this->line('');
        $this->info('A) Identical snapshot vs current → expected NO CHANGES');
        $this->line('   Keys: '.implode(',', array_keys($snapshot)));
        $has = DataChangeDetector::formHasChanges($current, $snapshot, $excluded);
        $changes = DataChangeDetector::getChangedFields($current, $snapshot, $excluded);
        $this->info('   Result: '.($has ? 'CHANGES DETECTED' : 'NO CHANGES'));
        $this->info('   Changes count: '.count($changes));

        // Case B: Minor non-meaningful differences (date time-part, numeric string vs float)
        $currentB = $snapshot;
        $currentB['date'] = '2025-07-01'; // remove time
        $currentB['expedition_fee'] = 250000.0; // numeric type change
        $this->line('');
        $this->info('B) Date-only and numeric type difference → expected NO CHANGES');
        $hasB = DataChangeDetector::formHasChanges($currentB, $snapshot, $excluded);
        $changesB = DataChangeDetector::getChangedFields($currentB, $snapshot, $excluded);
        $this->info('   Result: '.($hasB ? 'CHANGES DETECTED' : 'NO CHANGES'));
        $this->info('   Changes count: '.count($changesB));

        // Case C: Items keys normalized (price_value vs price)
        $snapshotC = $snapshot;
        $snapshotC['items'] = [[
            'strain_id' => '9f4744c1-d62d-4ece-a5b4-e76621ed0398',
            'quantity' => 5000,
            'price' => '7800.00',
            'weight_value' => '40.00',
            'weight_type' => 'per_unit',
        ]];
        $this->line('');
        $this->info('C) Items key differences normalized → expected NO CHANGES');
        $hasC = DataChangeDetector::formHasChanges($snapshot['items'] ? $snapshot : [], $snapshotC['items'] ? $snapshotC : [], $excluded);
        $changesC = DataChangeDetector::getChangedFields($snapshot['items'] ? $snapshot : [], $snapshotC['items'] ? $snapshotC : [], $excluded);
        $this->info('   Result: '.($hasC ? 'CHANGES DETECTED' : 'NO CHANGES'));
        $this->info('   Changes count: '.count($changesC));

        // Case D: Meaningful difference (quantity change)
        $currentD = $snapshot;
        $currentD['items'][0]['quantity'] = 6000;
        $this->line('');
        $this->info('D) Quantity changed → expected CHANGES DETECTED');
        $hasD = DataChangeDetector::formHasChanges($currentD, $snapshot, $excluded);
        $changesD = DataChangeDetector::getChangedFields($currentD, $snapshot, $excluded);
        $this->info('   Result: '.($hasD ? 'CHANGES DETECTED' : 'NO CHANGES'));
        $this->info('   Changes count: '.count($changesD));

        $this->line('');
        $this->info('🎯 Production Bypass Test Complete!');
        return 0;
    }
}

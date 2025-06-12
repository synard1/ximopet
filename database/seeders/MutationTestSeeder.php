<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Livestock;
use App\Models\Feed;
use App\Models\Unit;
use App\Models\FeedPurchase;
use App\Models\FeedPurchaseBatch;
use App\Models\FeedStock;
use App\Services\MutationService;
use Illuminate\Support\Str;

class MutationTestSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Unit
        $unit = Unit::create([
            'id' => Str::uuid(),
            'name' => 'Kilogram',
            'symbol' => 'kg',
            'created_by' => 1
        ]);

        // 2. Buat Feed
        $feed = Feed::create([
            'id' => Str::uuid(),
            'name' => 'Pakan Test',
            'type' => 'feed',
            'data' => [
                'unit_id' => $unit->id,
                'conversion_units' => [
                    [
                        'unit_id' => $unit->id,
                        'value' => 1,
                        'is_smallest' => true
                    ]
                ]
            ],
            'created_by' => 1
        ]);

        // 3. Buat Livestock
        $livestock1 = Livestock::create([
            'id' => Str::uuid(),
            'name' => 'Livestock 1',
            'farm_id' => 1,
            'coop_id' => 1,
            'created_by' => 1
        ]);

        $livestock2 = Livestock::create([
            'id' => Str::uuid(),
            'name' => 'Livestock 2',
            'farm_id' => 1,
            'coop_id' => 1,
            'created_by' => 1
        ]);

        // 4. Buat Feed Purchase Batch
        $batch = FeedPurchaseBatch::create([
            'id' => Str::uuid(),
            'date' => now(),
            'supplier_id' => 1,
            'created_by' => 1
        ]);

        // 5. Buat Feed Purchase
        $purchase = FeedPurchase::create([
            'id' => Str::uuid(),
            'livestock_id' => $livestock1->id,
            'feed_purchase_batch_id' => $batch->id,
            'feed_id' => $feed->id,
            'unit_id' => $unit->id,
            'quantity' => 100,
            'converted_unit' => $unit->id,
            'converted_quantity' => 100,
            'price_per_unit' => 10000,
            'price_per_converted_unit' => 10000,
            'created_by' => 1
        ]);

        // 6. Buat Feed Stock
        FeedStock::create([
            'id' => Str::uuid(),
            'livestock_id' => $livestock1->id,
            'feed_id' => $feed->id,
            'feed_purchase_id' => $purchase->id,
            'date' => now(),
            'source_type' => 'purchase',
            'source_id' => $purchase->id,
            'quantity_in' => 100,
            'quantity_used' => 0,
            'quantity_mutated' => 0,
            'available' => 100,
            'amount' => 1000000,
            'created_by' => 1
        ]);

        // 7. Test Mutasi
        $mutationData = [
            'date' => now(),
            'source_livestock_id' => $livestock1->id,
            'destination_livestock_id' => $livestock2->id,
            'notes' => 'Test mutation'
        ];

        $mutationItems = [
            [
                'type' => 'feed',
                'item_id' => $feed->id,
                'unit_id' => $unit->id,
                'quantity' => 50
            ]
        ];

        // Lakukan mutasi
        $mutation = MutationService::feedMutation($mutationData, $mutationItems);

        // Verifikasi hasil
        $this->verifyMutation($mutation, $livestock1, $livestock2, $feed);
    }

    private function verifyMutation($mutation, $livestock1, $livestock2, $feed): void
    {
        // Verifikasi Feed Stock
        $sourceStock = FeedStock::where('livestock_id', $livestock1->id)
            ->where('feed_id', $feed->id)
            ->first();

        $targetStock = FeedStock::where('livestock_id', $livestock2->id)
            ->where('feed_id', $feed->id)
            ->first();

        // Verifikasi Current Supply
        $sourceCurrentSupply = \App\Models\CurrentSupply::where('livestock_id', $livestock1->id)
            ->where('item_id', $feed->id)
            ->where('type', 'feed')
            ->first();

        $targetCurrentSupply = \App\Models\CurrentSupply::where('livestock_id', $livestock2->id)
            ->where('item_id', $feed->id)
            ->where('type', 'feed')
            ->first();

        // Log hasil verifikasi
        \Log::info('Mutation Test Results:', [
            'mutation_id' => $mutation->id,
            'source_stock' => [
                'quantity_in' => $sourceStock->quantity_in,
                'quantity_used' => $sourceStock->quantity_used,
                'quantity_mutated' => $sourceStock->quantity_mutated,
                'available' => $sourceStock->available
            ],
            'target_stock' => [
                'quantity_in' => $targetStock->quantity_in,
                'quantity_used' => $targetStock->quantity_used,
                'quantity_mutated' => $targetStock->quantity_mutated,
                'available' => $targetStock->available
            ],
            'source_current_supply' => [
                'quantity' => $sourceCurrentSupply->quantity
            ],
            'target_current_supply' => [
                'quantity' => $targetCurrentSupply->quantity
            ]
        ]);
    }
}

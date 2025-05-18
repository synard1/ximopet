<?php

namespace Tests;

use App\Models\Feed;
use App\Models\FeedPurchase;
use App\Models\FeedPurchaseBatch;
use App\Models\FeedStock;
use App\Models\CurrentSupply;
use App\Models\Livestock;
use App\Models\Partner;
use App\Models\Supplier;
use App\Models\Unit;
use App\Services\MutationService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MutationTest extends TestCase
{
    use RefreshDatabase;

    protected $unit;
    protected $feed;
    protected $supplier;
    protected $livestock1;
    protected $livestock2;
    protected $invoice_number;

    protected function setUp(): void
    {
        parent::setUp();

        // Jalankan migration dan seeder
        $this->artisan('migrate:fresh', ['--seed' => true]);

        // Ambil data dari seeder
        $this->unit = Unit::where('symbol', 'kg')->first();
        $this->feed = Feed::where('status', 'active')->first();
        $this->supplier = Partner::where('type', 'Supplier')->inRandomOrder()->first();
        $this->livestock1 = Livestock::first();
        $this->livestock2 = Livestock::skip(1)->first();

        $this->invoice_number = generateInvoiceNumber();
    }

    public function test_feed_purchase_and_mutation_flow()
    {
        // 1. Proses Pembelian Pakan
        $batch = FeedPurchaseBatch::create([
            'id' => Str::uuid(),
            'date' => now(),
            'invoice_number' => $this->invoice_number,
            'supplier_id' => $this->supplier->id,
            'created_by' => 1
        ]);

        // Buat Feed Purchase dengan quantity yang sesuai
        $purchase = FeedPurchase::create([
            'id' => Str::uuid(),
            'livestock_id' => $this->livestock1->id,
            'feed_purchase_batch_id' => $batch->id,
            'feed_id' => $this->feed->id,
            'unit_id' => $this->unit->id,
            'quantity' => 100.00,
            'converted_unit' => $this->unit->id,
            'converted_quantity' => 100.00,
            'price_per_unit' => 10000,
            'price_per_converted_unit' => 10000,
            'created_by' => 1
        ]);

        // Buat Feed Stock untuk pembelian
        FeedStock::create([
            'id' => Str::uuid(),
            'livestock_id' => $this->livestock1->id,
            'feed_id' => $this->feed->id,
            'feed_purchase_id' => $purchase->id,
            'date' => now(),
            'source_type' => 'purchase',
            'source_id' => $purchase->id,
            'quantity_in' => 100.00,
            'quantity_used' => 0.00,
            'quantity_mutated' => 0.00,
            'created_by' => 1
        ]);

        // Verifikasi Current Supply setelah pembelian
        $initialCurrentSupply = CurrentSupply::where('livestock_id', $this->livestock1->id)
            ->where('item_id', $this->feed->id)
            ->where('type', 'feed')
            ->first();

        $this->assertNotNull($initialCurrentSupply, 'Current supply should be created after purchase');
        $this->assertEquals(100.00, $initialCurrentSupply->quantity);

        // 2. Proses Mutasi
        $mutationData = [
            'date' => now(),
            'source_livestock_id' => $this->livestock1->id,
            'destination_livestock_id' => $this->livestock2->id,
            'notes' => 'Test mutation'
        ];

        $mutationItems = [
            [
                'type' => 'feed',
                'item_id' => $this->feed->id,
                'unit_id' => $this->unit->id,
                'quantity' => 50.00
            ]
        ];

        $mutation = MutationService::feedMutation($mutationData, $mutationItems);

        // Verifikasi setelah mutasi
        // Source Current Supply
        $sourceCurrentSupply = CurrentSupply::where('livestock_id', $this->livestock1->id)
            ->where('item_id', $this->feed->id)
            ->where('type', 'feed')
            ->first();

        $this->assertNotNull($sourceCurrentSupply, 'Source current supply should exist after mutation');
        $this->assertEquals(50.00, $sourceCurrentSupply->quantity);

        // Target Current Supply
        $targetCurrentSupply = CurrentSupply::where('livestock_id', $this->livestock2->id)
            ->where('item_id', $this->feed->id)
            ->where('type', 'feed')
            ->first();

        $this->assertNotNull($targetCurrentSupply, 'Target current supply should exist after mutation');
        $this->assertEquals(50.00, $targetCurrentSupply->quantity);

        // 3. Proses Hapus Mutasi
        MutationService::delete_mutation($mutation->id);

        // Verifikasi setelah hapus mutasi
        // Source Current Supply
        $sourceCurrentSupplyAfterDelete = CurrentSupply::where('livestock_id', $this->livestock1->id)
            ->where('item_id', $this->feed->id)
            ->where('type', 'feed')
            ->first();

        $this->assertNotNull($sourceCurrentSupplyAfterDelete, 'Source current supply should exist after mutation deletion');
        $this->assertEquals(100.00, $sourceCurrentSupplyAfterDelete->quantity);

        // Target Current Supply seharusnya sudah dihapus
        $targetCurrentSupplyAfterDelete = CurrentSupply::where('livestock_id', $this->livestock2->id)
            ->where('item_id', $this->feed->id)
            ->where('type', 'feed')
            ->first();

        $this->assertNull($targetCurrentSupplyAfterDelete, 'Target current supply should be deleted after mutation deletion');
    }

    public function test_feed_mutation_validates_stock_availability()
    {
        // Buat stock dengan quantity kecil
        FeedStock::create([
            'id' => Str::uuid(),
            'livestock_id' => $this->livestock1->id,
            'feed_id' => $this->feed->id,
            'date' => now(),
            'source_type' => 'purchase',
            'source_id' => Str::uuid(),
            'quantity_in' => 20.00,
            'quantity_used' => 0.00,
            'quantity_mutated' => 0.00,
            'available' => 20.00,
            'amount' => 200000,
            'created_by' => 1
        ]);

        // Buat Current Supply dengan quantity kecil
        CurrentSupply::create([
            'id' => Str::uuid(),
            'livestock_id' => $this->livestock1->id,
            'item_id' => $this->feed->id,
            'type' => 'feed',
            'quantity' => 20.00,
            'unit_id' => $this->unit->id,
            'created_by' => 1
        ]);

        $mutationData = [
            'date' => now(),
            'source_livestock_id' => $this->livestock1->id,
            'destination_livestock_id' => $this->livestock2->id,
            'notes' => 'Test mutation'
        ];

        $mutationItems = [
            [
                'type' => 'feed',
                'item_id' => $this->feed->id,
                'unit_id' => $this->unit->id,
                'quantity' => 50.00
            ]
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Stok tidak cukup');
        MutationService::feedMutation($mutationData, $mutationItems);
    }

    public function test_feed_mutation_validates_livestock_existence()
    {
        $mutationData = [
            'date' => now(),
            'source_livestock_id' => Str::uuid(), // ID tidak valid
            'destination_livestock_id' => $this->livestock2->id,
            'notes' => 'Test mutation'
        ];

        $mutationItems = [
            [
                'type' => 'feed',
                'item_id' => $this->feed->id,
                'unit_id' => $this->unit->id,
                'quantity' => 50.00
            ]
        ];

        $this->expectException(\Exception::class);
        MutationService::feedMutation($mutationData, $mutationItems);
    }
}

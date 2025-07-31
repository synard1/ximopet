<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\RecordingSale;
use App\Models\RecordingSaleItem;
use App\Models\Livestock;
use App\Models\LivestockBatch;
use App\Models\Recording;
use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class RecordingSaleOptimizedSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting RecordingSale Optimized seeding...');

        // Get sample data
        $company = Company::first();
        $user = User::first();
        $livestock = Livestock::with('batches')->first();

        if (!$company || !$user || !$livestock) {
            $this->command->warn('Required data not found. Please run other seeders first.');
            return;
        }

        $batches = $livestock->batches()->where('status', 'active')->take(3)->get();

        if ($batches->count() < 2) {
            $this->command->warn('Not enough batches found for livestock. Creating sample batches...');
            $this->createSampleBatches($livestock, $user, $company);
            $batches = $livestock->batches()->where('status', 'active')->take(3)->get();
        }

        // Create sample recordings
        $recording = $this->createSampleRecording($livestock, $user, $company);

        // Create sample sales with multiple batches (header-item pattern)
        $this->createMultipleBatchSale($livestock, $recording, $batches, $user, $company);

        // Create sample sales with single batch (legacy pattern)
        $this->createSingleBatchSale($livestock, $recording, $batches->first(), $user, $company);

        $this->command->info('RecordingSale Optimized seeding completed!');
    }

    /**
     * Create sample batches if not enough exist
     */
    private function createSampleBatches(Livestock $livestock, User $user, Company $company): void
    {
        for ($i = 1; $i <= 3; $i++) {
            LivestockBatch::create([
                'livestock_id' => $livestock->id,
                'company_id' => $company->id,
                'farm_id' => $livestock->farm_id,
                'coop_id' => $livestock->kandang_id,
                'name' => "Sample Batch {$i}",
                'start_date' => now()->subDays(30 - ($i * 5)),
                'initial_quantity' => 1000,
                'quantity_available' => 800 - ($i * 100),
                'quantity_depletion' => 50 + ($i * 10),
                'quantity_sales' => 150 - ($i * 20),
                'weight_per_unit' => 1.8 + ($i * 0.2),
                'price_per_unit' => 25000 + ($i * 1000),
                'status' => 'active',
                'number' => "BATCH-{$i}-" . now()->format('Ymd'),
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        }
    }

    /**
     * Create sample recording
     */
    private function createSampleRecording(Livestock $livestock, User $user, Company $company): Recording
    {
        return Recording::create([
            'livestock_id' => $livestock->id,
            'company_id' => $company->id,
            'tanggal' => now()->subDays(1),
            'umur' => 35,
            'stock_awal' => 2000,
            'mati' => 5,
            'afkir' => 3,
            'stock_akhir' => 1992,
            'berat_kemarin' => 1.5,
            'berat_hari_ini' => 1.8,
            'kenaikan_berat' => 0.3,
            'penjualan_ekor' => 150,
            'penjualan_berat' => 270.0,
            'penjualan_harga' => 25000,
            'penjualan_total' => 3750000,
            'status' => 'draft',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    /**
     * Create sale with multiple batches (header-item pattern)
     */
    private function createMultipleBatchSale(Livestock $livestock, Recording $recording, $batches, User $user, Company $company): void
    {
        // Create header (RecordingSale with is_header = true)
        $header = RecordingSale::create([
            'company_id' => $company->id,
            'livestock_id' => $livestock->id,
            'recording_id' => $recording->id,
            'livestock_batch_id' => null, // Header has no specific batch
            'date' => now()->subDays(1),
            'quantity' => 0, // Will be calculated
            'weight' => 0, // Will be calculated
            'price' => 25000, // Average price
            'total_quantity' => 150,
            'total_weight' => 270.0,
            'total_amount' => 3750000,
            'is_header' => true, // Mark as header
            'batch_count' => 3,
            'status' => 'completed',
            'metadata' => [
                'allocation_method' => 'fifo',
                'created_by_seeder' => true,
                'note' => 'Sample multi-batch sale created by optimized seeder',
            ],
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        // Create items for multiple batches (FIFO allocation)
        $remainingQuantity = 150;
        $remainingWeight = 270.0;
        $pricePerUnit = 25000;

        foreach ($batches as $index => $batch) {
            if ($remainingQuantity <= 0) break;

            $allocateQty = min($batch->quantity_available, $remainingQuantity);
            $allocateWeight = ($allocateQty / 150) * 270.0; // Proportional weight

            RecordingSaleItem::create([
                'recording_sale_id' => $header->id,
                'livestock_batch_id' => $batch->id,
                'quantity' => $allocateQty,
                'weight' => round($allocateWeight, 2),
                'price_per_unit' => $pricePerUnit,
                'amount' => $allocateQty * $pricePerUnit,
                'metadata' => [
                    'allocation_order' => $index + 1,
                    'batch_name' => $batch->name,
                    'fifo_sequence' => 'batch_' . ($index + 1),
                    'weight_per_unit' => round($allocateWeight / $allocateQty, 3),
                ],
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            // Update batch quantities
            $batch->quantity_available -= $allocateQty;
            $batch->quantity_sales += $allocateQty;
            $batch->save();

            $remainingQuantity -= $allocateQty;
            $remainingWeight -= $allocateWeight;
        }

        $this->command->info("✅ Created multi-batch sale (Header ID: {$header->id}) with " . $header->items()->count() . " items");
    }

    /**
     * Create sale with single batch (legacy pattern)
     */
    private function createSingleBatchSale(Livestock $livestock, Recording $recording, LivestockBatch $batch, User $user, Company $company): void
    {
        // Create legacy record (RecordingSale with is_header = false)
        $sale = RecordingSale::create([
            'company_id' => $company->id,
            'livestock_id' => $livestock->id,
            'recording_id' => $recording->id,
            'livestock_batch_id' => $batch->id, // Single batch assigned
            'date' => now(),
            'quantity' => 50,
            'weight' => 90.0,
            'price' => 25000,
            'total_quantity' => 50, // Same as quantity for legacy
            'total_weight' => 90.0, // Same as weight for legacy
            'total_amount' => 1250000, // Same as quantity * price
            'is_header' => false, // Mark as legacy single record
            'batch_count' => 1,
            'status' => 'draft',
            'metadata' => [
                'allocation_method' => 'single_batch',
                'created_by_seeder' => true,
                'note' => 'Sample single-batch sale created by optimized seeder',
            ],
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->command->info("✅ Created single-batch sale (ID: {$sale->id}) - Legacy pattern");
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Livestock;
use App\Models\LivestockBatch;
use App\Models\Farm;
use App\Models\Kandang;
use App\Models\LivestockStrain;
use App\Models\LivestockStrainStandard;
use App\Models\LivestockPurchase;
use App\Models\LivestockPurchaseItem;
use App\Models\CurrentLivestock;
use App\Models\Partner;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\Livestock\LivestockStrainStandardService;

class LivestockBatchSeeder extends Seeder
{
    public function run(): void
    {
        try {
            DB::beginTransaction();

            // Initialize service
            $livestockStrainStandardService = new LivestockStrainStandardService();

            // Get or create a test farm
            $farm = Farm::firstOrCreate(
                ['code' => 'FARM-BATCH-TEST'],
                [
                    'name' => 'Farm Batch Test',
                    'contact_person' => 'Test Contact',
                    'phone_number' => '08123456789',
                    'address' => 'Test Address',
                    'status' => 'active',
                    'created_by' => 1,
                    'updated_by' => 1,
                ]
            );

            // Get or create a test kandang
            $kandang = Kandang::firstOrCreate(
                [
                    'farm_id' => $farm->id,
                    'kode' => 'K-BATCH-TEST'
                ],
                [
                    'nama' => 'Kandang Batch Test',
                    'jumlah' => 0,
                    'berat' => 0,
                    'kapasitas' => 5000, // Total capacity for both batches
                    'status' => 'Digunakan',
                    'created_by' => 1,
                    'updated_by' => 1,
                ]
            );

            // Get or create a strain
            $strain = LivestockStrain::firstOrCreate(
                ['name' => 'Broiler Test', 'code' => 'BRO-TEST'],
                [
                    'description' => 'Test Breed',
                    'created_by' => 1,
                    'updated_by' => 1,
                ]
            );

            // Get or create breed standard
            $strainStandard = LivestockStrainStandard::firstOrCreate(
                ['livestock_strain_id' => $strain->id],
                [
                    'livestock_strain_name' => 'Broiler Test',
                    'description' => 'Test Standard',
                    'standar_data' => [
                        [
                            'umur' => 0,
                            'bobot' => ['min' => 40, 'max' => 45, 'target' => 42],
                            'feed_intake' => ['min' => 0, 'max' => 0, 'target' => 0],
                            'fcr' => ['min' => 0, 'max' => 0, 'target' => 0]
                        ]
                    ],
                    'status' => 'active',
                    'created_by' => 1,
                    'updated_by' => 1,
                ]
            );

            // Get or create a vendor
            $vendor = Partner::firstOrCreate(
                ['type' => 'Supplier'],
                [
                    'name' => 'Test Supplier',
                    'contact_person' => 'Test Supplier Contact',
                    'phone_number' => '08123456789',
                    'address' => 'Test Supplier Address',
                    'status' => 'active',
                    'created_by' => 1,
                    'updated_by' => 1,
                ]
            );

            // Create one Livestock record for all batches
            $livestock = Livestock::create([
                'farm_id' => $farm->id,
                'kandang_id' => $kandang->id,
                'name' => 'Test Livestock',
                'livestock_strain_id' => $strain->id,
                'livestock_strain_standard_id' => $strainStandard->id,
                'livestock_strain_name' => $strain->name,
                'start_date' => Carbon::now(),
                'populasi_awal' => 0, // Will be updated with total
                'quantity_depletion' => 0,
                'quantity_sales' => 0,
                'quantity_mutated' => 0,
                'berat_awal' => 0, // Will be updated with average
                'harga' => 0, // Will be updated with average
                'status' => 'active',
                'created_by' => 1,
                'updated_by' => 1,
            ]);

            $hargaPerEkor = 15000;
            $batches = [
                [
                    'name' => 'Batch 1',
                    'tanggal' => Carbon::now(),
                    'jumlah' => 2000,
                    'berat_awal' => 40,
                    'harga' => $hargaPerEkor,
                ],
                [
                    'name' => 'Batch 2',
                    'tanggal' => Carbon::now()->addDays(7),
                    'jumlah' => 2500,
                    'berat_awal' => 45,
                    'harga' => 16000,
                ]
            ];

            $totalPopulasi = 0;
            $totalBerat = 0;
            $totalHarga = 0;

            foreach ($batches as $index => $batchData) {
                // 1. Create Livestock Purchase
                $purchase = LivestockPurchase::create([
                    'invoice_number' => 'INV-BATCH-TEST-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                    'tanggal' => $batchData['tanggal'],
                    'vendor_id' => $vendor->id,
                    'created_by' => 1,
                    'updated_by' => 1,
                ]);

                // 2. Create Purchase Item
                $purchaseItem = LivestockPurchaseItem::create([
                    'livestock_purchase_id' => $purchase->id,
                    'livestock_id' => $livestock->id,
                    'jumlah' => $batchData['jumlah'],
                    'harga_per_ekor' => $batchData['harga'],
                    'created_by' => 1,
                    'updated_by' => 1,
                ]);

                // 3. Create LivestockBatch
                $batch = LivestockBatch::create([
                    'livestock_id' => $livestock->id,
                    'farm_id' => $farm->id,
                    'kandang_id' => $kandang->id,
                    'livestock_strain_id' => $strain->id,
                    'livestock_strain_standard_id' => $strainStandard->id,
                    'name' => $batchData['name'],
                    'livestock_strain_name' => 'Broiler Test',
                    'start_date' => $batchData['tanggal'],
                    'populasi_awal' => $batchData['jumlah'],
                    'quantity_depletion' => 0,
                    'quantity_sales' => 0,
                    'quantity_mutated' => 0,
                    'berat_awal' => $batchData['berat_awal'],
                    'harga' => $batchData['harga'],
                    'status' => 'active',
                    'livestock_purchase_item_id' => $purchaseItem->id,
                    'created_by' => 1,
                    'updated_by' => 1,
                ]);

                // 4. Run service for batch
                try {
                    $livestockStrainStandardService->updateLivestockStrainStandard([
                        'livestock_id' => $livestock->id,
                        'livestock_batch_id' => $batch->id,
                        'livestock_strain_standard_id' => $strainStandard->id,
                    ]);
                    $this->command->info("LivestockStrainStandardService executed for {$batchData['name']} ID: {$batch->id}");
                } catch (\Exception $e) {
                    $this->command->error("Failed to run LivestockStrainStandardService for {$batchData['name']} ID: {$batch->id}. Error: " . $e->getMessage());
                    throw $e;
                }

                // Update totals for Livestock record
                $totalPopulasi += $batchData['jumlah'];
                $totalBerat += ($batchData['jumlah'] * $batchData['berat_awal']);
                $totalHarga += ($batchData['jumlah'] * $batchData['harga']);
            }

            // Update Livestock record with totals
            $livestock->update([
                'populasi_awal' => $totalPopulasi,
                'berat_awal' => $totalBerat / $totalPopulasi, // Average weight
                'harga' => $totalHarga / $totalPopulasi, // Average price
            ]);

            // Create Current Livestock
            CurrentLivestock::create([
                'livestock_id' => $livestock->id,
                'farm_id' => $farm->id,
                'kandang_id' => $kandang->id,
                'quantity' => $totalPopulasi,
                'berat_total' => $totalBerat,
                'avg_berat' => $totalBerat / $totalPopulasi,
                'age' => 0,
                'status' => 'active',
                'created_by' => 1,
                'updated_by' => 1,
            ]);

            // Update Kandang
            $kandang->update([
                'livestock_id' => $livestock->id,
                'jumlah' => $totalPopulasi,
                'berat' => $totalBerat,
                'status' => 'Digunakan',
                'updated_by' => 1,
            ]);

            // Assign farm operator
            $this->assignFarmOperator($farm);

            DB::commit();
            $this->command->info('LivestockBatchSeeder completed successfully');
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->command->error("Seeder failed: " . $th->getMessage());
            throw $th;
        }
    }

    private function assignFarmOperator(Farm $farm)
    {
        $supervisor = User::where('email', 'supervisor@demo.com')->first();
        $operator = User::where('email', 'operator@demo.com')->first();

        if ($supervisor && $operator) {
            // Check if operator is already assigned to this farm
            $isOperatorAssigned = DB::table('farm_operators')
                ->where('farm_id', $farm->id)
                ->where('user_id', $operator->id)
                ->exists();

            if (!$isOperatorAssigned) {
                // Only attach if not already assigned
                $farm->operators()->attach($operator);
                $this->command->info("Operator assigned to farm: {$farm->name}");
            } else {
                $this->command->info("Operator already assigned to farm: {$farm->name}");
            }
        }
    }
}

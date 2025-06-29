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
use App\Models\Coop;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\Livestock\LivestockStrainStandardService;
use Faker\Factory as Faker;

class LivestockBatchSeeder extends Seeder
{
    public function run(): void
    {
        try {
            DB::beginTransaction();

            // Get admin user as primary user for created_by
            $adminUser = User::where('email', 'admin@peternakan.digital')->first();
            if (!$adminUser) {
                $adminUser = User::first(); // Fallback to any user
            }

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
                    'created_by' => $adminUser ? $adminUser->id : null,
                    'updated_by' => $adminUser ? $adminUser->id : null,
                ]
            );

            // Get or create a test kandang
            $kandang = Coop::firstOrCreate(
                [
                    'farm_id' => $farm->id,
                    'code' => 'K-BATCH-TEST'
                ],
                [
                    'name' => 'Kandang Batch Test',
                    'capacity' => 5000, // Total capacity for both batches
                    'status' => 'active',
                    'created_by' => $adminUser ? $adminUser->id : null,
                    'updated_by' => $adminUser ? $adminUser->id : null,
                ]
            );

            // Get or create a strain
            $strain = LivestockStrain::firstOrCreate(
                ['name' => 'Broiler Test', 'code' => 'BRO-TEST'],
                [
                    'description' => 'Test Breed',
                    'created_by' => $adminUser ? $adminUser->id : null,
                    'updated_by' => $adminUser ? $adminUser->id : null,
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
                    'created_by' => $adminUser ? $adminUser->id : null,
                    'updated_by' => $adminUser ? $adminUser->id : null,
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
                    'created_by' => $adminUser ? $adminUser->id : null,
                    'updated_by' => $adminUser ? $adminUser->id : null,
                ]
            );

            // Create one Livestock record for all batches
            $livestock = Livestock::create([
                'farm_id' => $farm->id,
                'coop_id' => $kandang->id,
                'name' => 'Test Livestock',
                'livestock_strain_id' => $strain->id,
                'livestock_strain_standard_id' => $strainStandard->id,
                'livestock_strain_name' => $strain->name,
                'start_date' => Carbon::now(),
                'initial_quantity' => 0, // Will be updated with total
                'quantity_depletion' => 0,
                'quantity_sales' => 0,
                'quantity_mutated' => 0,
                'initial_weight' => 0, // Will be updated with average
                'price' => 0, // Will be updated with average
                'status' => 'active',
                'created_by' => $adminUser ? $adminUser->id : null,
                'updated_by' => $adminUser ? $adminUser->id : null,
            ]);

            $hargaPerEkor = 15000;
            $batches = [
                [
                    'name' => 'Batch 1',
                    'tanggal' => Carbon::now(),
                    'quantity' => 2000,
                    'weight_value' => 40, // berat per ekor dalam gram
                    'weight_type' => 'per_unit',
                    'weight_per_unit' => 40, // berat per ekor dalam gram
                    'weight_total' => 80000, // total berat dalam gram
                    'price_value' => 15000,
                    'price_type' => 'per_unit',
                    'price_per_unit' => 15000, // harga per ekor dalam rupiah
                    'price_total' => 30000000, // total harga dalam rupiah
                ],
                [
                    'name' => 'Batch 2',
                    'tanggal' => Carbon::now()->addDays(7),
                    'quantity' => 2500,
                    'weight_value' => 50, // total berat dalam gram
                    'weight_type' => 'per_unit',
                    'weight_per_unit' => 50, // berat per ekor dalam gram
                    'weight_total' => 125000, // total berat dalam gram
                    'price_value' => 15000,
                    'price_type' => 'per_unit',
                    'price_per_unit' => 15000, // harga per ekor dalam rupiah
                    'price_total' => 37500000, // total harga dalam rupiah
                ],
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
                    'created_by' => $adminUser ? $adminUser->id : null,
                    'updated_by' => $adminUser ? $adminUser->id : null,
                ]);

                // 2. Create Purchase Item
                $purchaseItem = LivestockPurchaseItem::create([
                    'livestock_purchase_id' => $purchase->id,
                    'livestock_id' => $livestock->id,
                    'quantity' => $batchData['quantity'],
                    'price_value' => $batchData['price_value'],
                    'price_type' => $batchData['price_type'],
                    'price_per_unit' => $batchData['price_per_unit'],
                    'price_total' => $batchData['price_total'],
                    'weight_value' => $batchData['weight_value'],
                    'weight_type' => $batchData['weight_type'],
                    'weight_per_unit' => $batchData['weight_per_unit'],
                    'weight_total' => $batchData['weight_total'],
                    'created_by' => $adminUser ? $adminUser->id : null,
                    'updated_by' => $adminUser ? $adminUser->id : null,
                ]);

                // 3. Create LivestockBatch
                $batch = LivestockBatch::create([
                    'livestock_id' => $livestock->id,
                    'source_type' => 'purchase',
                    'source_id' => $purchase->id,
                    'farm_id' => $farm->id,
                    'coop_id' => $kandang->id,
                    'livestock_strain_id' => $strain->id,
                    'livestock_strain_standard_id' => $strainStandard->id,
                    'name' => $batchData['name'],
                    'livestock_strain_name' => 'Broiler Test',
                    'start_date' => $batchData['tanggal'],
                    'initial_quantity' => $batchData['quantity'],
                    'quantity_depletion' => 0,
                    'quantity_sales' => 0,
                    'quantity_mutated' => 0,
                    'initial_weight' => $batchData['weight_per_unit'],
                    'weight' => $batchData['weight_per_unit'],
                    'weight_type' => $batchData['weight_type'],
                    'weight_per_unit' => $batchData['weight_per_unit'],
                    'weight_total' => $batchData['weight_total'],
                    'status' => 'active',
                    'livestock_purchase_item_id' => $purchaseItem->id,
                    'created_by' => $adminUser ? $adminUser->id : null,
                    'updated_by' => $adminUser ? $adminUser->id : null,
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
                $totalPopulasi += $batchData['quantity'];
                $totalBerat += ($batchData['quantity'] * $batchData['weight_per_unit']);
                $totalHarga += ($batchData['quantity'] * $batchData['price_per_unit']);
            }

            // Update Livestock record with totals
            $livestock->update([
                'initial_quantity' => $totalPopulasi,
                'initial_weight' => $totalBerat / $totalPopulasi, // Average weight
                'price' => $totalHarga / $totalPopulasi, // Average price
            ]);

            // Create Current Livestock
            CurrentLivestock::create([
                'livestock_id' => $livestock->id,
                'farm_id' => $farm->id,
                'coop_id' => $kandang->id,
                'quantity' => $totalPopulasi,
                'berat_total' => $totalBerat,
                'avg_berat' => $totalBerat / $totalPopulasi,
                'age' => 0,
                'status' => 'active',
                'created_by' => $adminUser ? $adminUser->id : null,
                'updated_by' => $adminUser ? $adminUser->id : null,
            ]);

            // Update Kandang
            $kandang->update([
                'livestock_id' => $livestock->id,
                'quantity' => $totalPopulasi,
                'weight' => $totalBerat,
                'status' => 'in_use',
                'updated_by' => $adminUser ? $adminUser->id : null,
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

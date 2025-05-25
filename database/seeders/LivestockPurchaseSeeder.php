<?php

namespace Database\Seeders;

use App\Models\CurrentLivestock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Farm;
use App\Models\Kandang;
use App\Models\Rekanan;
use App\Models\Partner;
use App\Models\Livestock;
use App\Models\LivestockBatch;
use App\Models\LivestockStrain;
use App\Models\LivestockStrainStandard;
use App\Models\LivestockPurchase;
use App\Models\LivestockPurchaseItem;
use App\Models\User;

use App\Services\Livestock\LivestockStrainStandardService;

class LivestockPurchaseSeeder extends Seeder
{
    public function run(): void
    {
        $emails = [
            'supervisor@demo.com' => 'DF01',
            'supervisor@demo2.com' => 'DF02',
        ];

        $vendor = Partner::where('type', 'Supplier')->inRandomOrder()->first();
        $vendorId = optional($vendor)->id;

        $breed = LivestockStrain::inRandomOrder()->first();
        $breedId = optional($breed)->id;

        if (!$breed) {
            $this->command->warn("No livestock breed found. Using null for livestock_strain_id.");
        }

        $hargaPerEkor = 5500;

        // Initialize service
        $livestockStrainStandardService = new LivestockStrainStandardService();

        foreach ($emails as $email => $farmCode) {
            $user = User::where('email', $email)->first();
            $userId = optional($user)->id;

            $farm = Farm::where('code', $farmCode)->where('status', 'active')->first();
            $farmId = optional($farm)->id;

            $kandang1 = Kandang::where('farm_id', $farmId)->where('kode', "K01-{$farmCode}")->first();
            $kandang2 = Kandang::where('farm_id', $farmId)->where('kode', "K02-{$farmCode}")->first();

            $kandang1Id = optional($kandang1)->id;
            $kandang2Id = optional($kandang2)->id;

            $data = [
                [
                    'kandang_id' => $kandang1Id,
                    'tanggal' => '2025-03-01',
                    'jumlah' => 10000,
                    'name' => "Periode Maret - " . optional($kandang1)->nama,
                ],
                [
                    'kandang_id' => $kandang2Id,
                    'tanggal' => '2025-03-03',
                    'jumlah' => 8000,
                    'name' => "Periode Maret - " . optional($kandang2)->nama,
                ],
            ];

            try {
                $livestockStrainStandard = LivestockStrainStandard::where('livestock_strain_id', $breedId)->first();
                $livestockStrainStandardId = optional($livestockStrainStandard)->id;

                DB::beginTransaction();

                foreach ($data as $index => $item) {
                    // 1. Create Livestock (parent record)
                    $livestock = Livestock::create([
                        'farm_id' => $farmId,
                        'kandang_id' => $item['kandang_id'],
                        'name' => $item['name'],
                        'livestock_strain_id' => $breedId,
                        'breed' => $breed->name,
                        'livestock_strain_standard_id' => $livestockStrainStandardId,
                        'start_date' => Carbon::parse($item['tanggal']),
                        'populasi_awal' => $item['jumlah'],
                        'quantity_depletion' => 0,
                        'quantity_sales' => 0,
                        'quantity_mutated' => 0,
                        'berat_awal' => 40,
                        'harga' => $hargaPerEkor,
                        'status' => 'active',
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);

                    if ($livestock) {
                        // 2. Create Livestock Purchase
                        $purchase = LivestockPurchase::create([
                            'invoice_number' => 'INV-' . strtoupper(optional($farm)->kode) . '-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                            'tanggal' => $item['tanggal'],
                            'vendor_id' => $vendorId,
                            'created_by' => $userId,
                            'updated_by' => $userId,
                        ]);

                        // 3. Create Purchase Item
                        $purchaseItem = LivestockPurchaseItem::create([
                            'livestock_purchase_id' => optional($purchase)->id,
                            'livestock_id' => optional($livestock)->id,
                            'jumlah' => $item['jumlah'],
                            'harga_per_ekor' => $hargaPerEkor,
                            'created_by' => $userId,
                            'updated_by' => $userId,
                        ]);

                        // 4. Create LivestockBatch
                        $batch = LivestockBatch::create([
                            'livestock_id' => $livestock->id,
                            'farm_id' => $farmId,
                            'kandang_id' => $item['kandang_id'],
                            'livestock_strain_id' => $breedId,
                            'livestock_strain_standard_id' => $livestockStrainStandardId,
                            'name' => $item['name'],
                            'breed' => $breed->name,
                            'start_date' => Carbon::parse($item['tanggal']),
                            'populasi_awal' => $item['jumlah'],
                            'quantity_depletion' => 0,
                            'quantity_sales' => 0,
                            'quantity_mutated' => 0,
                            'berat_awal' => 40,
                            'harga' => $hargaPerEkor,
                            'status' => 'active',
                            'livestock_purchase_item_id' => optional($purchaseItem)->id,
                            'created_by' => $userId,
                            'updated_by' => $userId,
                        ]);

                        // Run service for batch
                        try {
                            $livestockStrainStandardService->updateLivestockStrainStandard([
                                'livestock_id' => $livestock->id,
                                'livestock_batch_id' => $batch->id,
                                'livestock_strain_standard_id' => $livestockStrainStandardId,
                            ]);
                            $this->command->info("LivestockStrainStandardService executed for Batch ID: {$batch->id}");
                        } catch (\Exception $e) {
                            $this->command->error("Failed to run LivestockStrainStandardService for Batch ID: {$batch->id}. Error: " . $e->getMessage());
                            throw $e;
                        }

                        // 5. Create Current Livestock
                        CurrentLivestock::create([
                            'livestock_id' => optional($livestock)->id,
                            'farm_id' => $farmId,
                            'kandang_id' => $item['kandang_id'],
                            'quantity' => $item['jumlah'],
                            'berat_total' => $item['jumlah'] * 40,
                            'avg_berat' => 40,
                            'age' => 0,
                            'status' => 'active',
                            'created_by' => $userId,
                            'updated_by' => $userId,
                        ]);

                        // 6. Update Kandang
                        if ($item['kandang_id']) {
                            $kandang = Kandang::find($item['kandang_id']);
                            if ($kandang) {
                                $kandang->update([
                                    'livestock_id' => optional($livestock)->id,
                                    'jumlah' => $item['jumlah'],
                                    'berat' => $item['jumlah'] * 40,
                                    'status' => 'Digunakan',
                                    'updated_by' => $userId,
                                ]);
                            }
                        }
                    } else {
                        $this->command->warn("Livestock record not created for item: " . $item['name']);
                    }
                }

                // Assign farm operator
                $this->assignFarmOperator($farm);

                DB::commit();
            } catch (\Throwable $th) {
                DB::rollBack();
                $this->command->info("Seeder failed for $email: " . $th->getMessage());
            }
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

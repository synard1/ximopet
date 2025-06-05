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
use App\Models\Coop;

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

        $strain = LivestockStrain::inRandomOrder()->first();
        $strainId = optional($strain)->id;

        if (!$strain) {
            $this->command->warn("No livestock strain found. Using null for livestock_strain_id.");
        }

        $hargaPerEkor = 5500;

        // Initialize service
        $livestockStrainStandardService = new LivestockStrainStandardService();

        foreach ($emails as $email => $farmCode) {
            $user = User::where('email', $email)->first();
            $userId = optional($user)->id;

            $farm = Farm::where('code', $farmCode)->where('status', 'active')->first();
            $farmId = optional($farm)->id;

            $kandang1 = Coop::where('farm_id', $farmId)->where('code', "K01-{$farmCode}")->first();
            $kandang2 = Coop::where('farm_id', $farmId)->where('code', "K02-{$farmCode}")->first();

            $kandang1Id = optional($kandang1)->id;
            $kandang2Id = optional($kandang2)->id;

            $data = [
                [
                    'coop_id' => $kandang1Id,
                    'tanggal' => '2025-03-01',
                    'quantity' => 10000,
                    'name' => "Periode Maret - " . optional($kandang1)->name,
                ],
                [
                    'coop_id' => $kandang2Id,
                    'tanggal' => '2025-03-03',
                    'quantity' => 8000,
                    'name' => "Periode Maret - " . optional($kandang2)->name,
                ],
            ];

            try {
                $livestockStrainStandard = LivestockStrainStandard::where('livestock_strain_id', $strainId)->first();
                $livestockStrainStandardId = optional($livestockStrainStandard)->id;

                DB::beginTransaction();

                foreach ($data as $index => $item) {
                    // 1. Create Livestock (parent record)
                    $livestock = Livestock::create([
                        'farm_id' => $farmId,
                        'coop_id' => $item['coop_id'],
                        'name' => $item['name'],
                        'livestock_strain_id' => $strainId,
                        'livestock_strain_name' => $strain->name,
                        'livestock_strain_standard_id' => $livestockStrainStandardId,
                        'start_date' => Carbon::parse($item['tanggal']),
                        'initial_quantity' => $item['quantity'],
                        'quantity_depletion' => 0,
                        'quantity_sales' => 0,
                        'quantity_mutated' => 0,
                        'initial_weight' => 40,
                        'price' => $hargaPerEkor,
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
                            'quantity' => $item['quantity'],
                            'price_value' => $hargaPerEkor,
                            'price_type' => 'per_unit',
                            'price_per_unit' => $hargaPerEkor,
                            'price_total' => $item['quantity'] * $hargaPerEkor,
                            'tax_amount' => 0,
                            'tax_percentage' => 0,
                            'weight_value' => 40,
                            'weight_type' => 'per_unit',
                            'weight_per_unit' => 40,
                            'weight_total' => $item['quantity'] * 40,
                            'notes' => '',
                            'created_by' => $userId,
                            'updated_by' => $userId,
                        ]);

                        // 4. Create LivestockBatch
                        $batch = LivestockBatch::create([
                            'livestock_id' => $livestock->id,
                            'source_type' => 'purchase',
                            'source_id' => optional($purchase)->id,
                            'farm_id' => $farmId,
                            'coop_id' => $item['coop_id'],
                            'livestock_strain_id' => $strainId,
                            'livestock_strain_standard_id' => $livestockStrainStandardId,
                            'name' => $item['name'],
                            'livestock_strain_name' => $strain->name,
                            'start_date' => Carbon::parse($item['tanggal']),
                            'initial_quantity' => $item['quantity'],
                            'quantity_depletion' => 0,
                            'quantity_sales' => 0,
                            'quantity_mutated' => 0,
                            'initial_weight' => 40,
                            'weight' => 40,
                            'weight_type' => 'per_unit',
                            'weight_per_unit' => 40,
                            'weight_total' => $item['quantity'] * 40,
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
                            'coop_id' => $item['coop_id'],
                            'quantity' => $item['quantity'],
                            'berat_total' => $item['quantity'] * 40,
                            'avg_berat' => 40,
                            'age' => 0,
                            'status' => 'active',
                            'created_by' => $userId,
                            'updated_by' => $userId,
                        ]);

                        // 6. Update Kandang
                        if ($item['coop_id']) {
                            $kandang = Coop::find($item['coop_id']);
                            if ($kandang) {
                                $kandang->update([
                                    'livestock_id' => optional($livestock)->id,
                                    'quantity' => $item['quantity'],
                                    'weight' => $item['quantity'] * 40,
                                    'status' => 'in_use',
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

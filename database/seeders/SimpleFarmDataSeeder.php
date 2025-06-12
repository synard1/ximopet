<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\{
    User,
    Farm,
    Coop,
    Partner,
    Feed,
    Supply,
    Unit,
    Livestock,
    LivestockPurchase,
    LivestockPurchaseItem,
    Recording,
    LivestockDepletion,
    FeedUsage,
    FeedUsageDetail
};

class SimpleFarmDataSeeder extends Seeder
{
    public function run()
    {
        Log::info('🚀 Starting Simple Farm Data Seeder');

        DB::transaction(function () {
            $this->createData();
        });

        Log::info('✅ Simple Farm Data Seeder completed successfully');
    }

    private function createData()
    {
        // Get admin user
        $adminUser = User::first();
        if (!$adminUser) {
            throw new \Exception('No admin user found');
        }

        Log::info('📦 Creating farms and basic data...');

        // Create farms
        $farms = [];
        for ($i = 1; $i <= 3; $i++) {
            $farms[] = Farm::create([
                'code' => 'FARM' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'name' => 'Farm Demo ' . $i,
                'contact_person' => 'Manager Farm ' . $i,
                'phone_number' => '08123456780' . $i,
                'address' => 'Jl. Peternakan No. ' . $i,
                'description' => 'Farm demo untuk testing smart analytics',
                'quantity' => 0,
                'capacity' => 50000,
                'status' => 'active',
                'created_by' => $adminUser->id
            ]);
        }

        // Create coops
        $coops = [];
        foreach ($farms as $farm) {
            for ($i = 1; $i <= 2; $i++) {
                $coops[] = Coop::create([
                    'farm_id' => $farm->id,
                    'code' => 'COOP' . $farm->id . '-' . $i,
                    'name' => 'Kandang ' . chr(64 + $i) . ' - ' . $farm->name,
                    'capacity' => rand(5000, 10000),
                    'status' => 'active',
                    'created_by' => $adminUser->id
                ]);
            }
        }

        // Create suppliers
        $supplier = Partner::firstOrCreate([
            'type' => 'supplier',
            'name' => 'Supplier DOC Demo'
        ], [
            'code' => 'SUP001',
            'email' => 'supplier@demo.com',
            'phone_number' => '08123456789',
            'address' => 'Jl. Supplier DOC',
            'status' => 'active',
            'created_by' => $adminUser->id
        ]);

        // Create feeds
        $feeds = [];
        $feedNames = ['Starter BR-1', 'Grower BR-2', 'Finisher BR-3'];
        foreach ($feedNames as $index => $name) {
            $feeds[] = Feed::firstOrCreate(['name' => $name], [
                'code' => 'FEED' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                'status' => 'active',
                'data' => [
                    'unit_id' => Unit::where('name', 'Kg')->first()?->id,
                    'description' => 'Pakan ' . $name . ' untuk ayam broiler'
                ],
                'created_by' => $adminUser->id
            ]);
        }

        Log::info('✅ Basic data created', [
            'farms' => count($farms),
            'coops' => count($coops),
            'feeds' => count($feeds)
        ]);

        // Create livestock purchases and recordings
        $purchaseDate = Carbon::now()->subDays(30);

        foreach ($coops as $coop) {
            Log::info('🐔 Creating livestock for coop: ' . $coop->name);

            // Create livestock purchase
            $purchase = LivestockPurchase::create([
                'farm_id' => $coop->farm_id,
                'purchase_date' => $purchaseDate,
                'vendor_id' => $supplier->id,
                'expedition_id' => $supplier->id,
                'invoice_number' => 'INV-DOC-' . $coop->id,
                'status' => 'complete',
                'notes' => 'Pembelian DOC untuk ' . $coop->name,
                'created_by' => $adminUser->id
            ]);

            // Create livestock purchase item
            $quantity = rand(4000, 6000);
            $purchaseItem = LivestockPurchaseItem::create([
                'livestock_purchase_id' => $purchase->id,
                'livestock_strain_id' => null, // Skip strain for now
                'quantity' => $quantity,
                'weight_per_unit' => 0.045,
                'price_per_unit' => 4500,
                'total_weight' => $quantity * 0.045,
                'total_amount' => $quantity * 4500,
                'created_by' => $adminUser->id
            ]);

            // Create livestock master
            $livestock = Livestock::create([
                'farm_id' => $coop->farm_id,
                'coop_id' => $coop->id,
                'name' => 'Batch-' . $coop->name . '-' . $purchaseDate->format('Y-m'),
                'start_date' => $purchaseDate,
                'initial_quantity' => $quantity,
                'initial_weight' => 0.045,
                'price' => 4500,
                'status' => 'active',
                'data' => [
                    'purchase_id' => $purchase->id,
                    'strain' => 'Broiler'
                ],
                'created_by' => $adminUser->id
            ]);

            // Create daily recordings for 30 days
            $currentDate = $purchaseDate->copy();
            $currentPopulation = $quantity;
            $currentWeight = 0.045;

            for ($day = 0; $day < 30; $day++) {
                $age = $day + 1;

                // Calculate realistic mortality (0.1% daily)
                $mortality = max(0, round($currentPopulation * 0.001));

                // Calculate weight gain (realistic curve)
                $weightGain = $this->calculateWeightGain($age);
                $previousWeight = $currentWeight;
                $currentWeight += $weightGain;

                // Calculate feed consumption
                $feedConsumption = $currentPopulation * $currentWeight * 0.08; // 8% of body weight

                // Update population
                $stockAwal = $currentPopulation;
                $currentPopulation -= $mortality;

                // Create recording
                $recording = Recording::create([
                    'livestock_id' => $livestock->id,
                    'tanggal' => $currentDate->format('Y-m-d'),
                    'age' => $age,
                    'stock_awal' => $stockAwal,
                    'stock_akhir' => $currentPopulation,
                    'berat_semalam' => $previousWeight,
                    'berat_hari_ini' => $currentWeight,
                    'kenaikan_berat' => $weightGain,
                    'pakan_jenis' => $feeds[0]->name,
                    'pakan_harian' => $feedConsumption,
                    'payload' => [
                        'mortality' => $mortality,
                        'age_days' => $age,
                        'population' => $currentPopulation,
                        'version' => '2.0',
                        'recorded_by' => [
                            'id' => $adminUser->id,
                            'name' => $adminUser->name,
                            'role' => 'System Seeder'
                        ]
                    ],
                    'created_by' => $adminUser->id
                ]);

                // Create depletion record if mortality
                if ($mortality > 0) {
                    LivestockDepletion::create([
                        'livestock_id' => $livestock->id,
                        'recording_id' => $recording->id,
                        'tanggal' => $currentDate->format('Y-m-d'),
                        'jumlah' => $mortality,
                        'jenis' => 'Mati',
                        'metadata' => [
                            'age_days' => $age,
                            'created_by_seeder' => true
                        ],
                        'created_by' => $adminUser->id
                    ]);
                }

                // Create feed usage
                if ($feedConsumption > 0) {
                    $feedUsage = FeedUsage::create([
                        'livestock_id' => $livestock->id,
                        'recording_id' => $recording->id,
                        'usage_date' => $currentDate->format('Y-m-d'),
                        'total_quantity' => $feedConsumption,
                        'created_by' => $adminUser->id
                    ]);
                }

                $currentDate->addDay();

                // Log progress every 7 days
                if ($age % 7 === 0) {
                    Log::info('📅 Recording progress', [
                        'livestock' => $livestock->name,
                        'age' => $age,
                        'population' => $currentPopulation,
                        'weight' => round($currentWeight, 3),
                        'mortality_total' => $quantity - $currentPopulation
                    ]);
                }
            }
        }

        Log::info('✅ All livestock and recordings created');
    }

    private function calculateWeightGain($age)
    {
        // Realistic broiler weight gain curve (kg per day)
        if ($age <= 7) return 0.015; // 15g/day week 1
        if ($age <= 14) return 0.035; // 35g/day week 2
        if ($age <= 21) return 0.055; // 55g/day week 3
        if ($age <= 28) return 0.065; // 65g/day week 4
        return 0.050; // 50g/day week 5+
    }
}

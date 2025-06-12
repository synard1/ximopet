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
    LivestockStrain,
    LivestockStrainStandard,
    Livestock,
    LivestockPurchase,
    LivestockPurchaseItem,
    LivestockBatch,
    Feed,
    FeedPurchaseBatch,
    FeedPurchase,
    FeedStock,
    Supply,
    SupplyCategory,
    SupplyPurchaseBatch,
    SupplyPurchase,
    SupplyStock,
    CurrentLivestock,
    CurrentFeed,
    CurrentSupply,
    Recording,
    LivestockDepletion,
    FeedUsage,
    FeedUsageDetail,
    Unit
};

class ComprehensiveFarmDataSeeder extends Seeder
{
    private $adminUser;
    private $farms = [];
    private $coops = [];
    private $suppliers = [];
    private $feeds = [];
    private $supplies = [];
    private $livestocks = [];

    public function run()
    {
        Log::info('🚀 Starting Comprehensive Farm Data Seeder');

        DB::transaction(function () {
            $this->createBasicData();
            $this->createLivestockPurchases();
            $this->createFeedPurchases();
            $this->createSupplyPurchases();
            $this->createDailyRecordings();
        });

        Log::info('✅ Comprehensive Farm Data Seeder completed successfully');
    }

    private function createBasicData()
    {
        Log::info('📦 Creating basic data (users, farms, coops, suppliers...)');

        // Get admin user
        $this->adminUser = User::where('email', 'admin@demo.com')->first() ?? User::first();

        // Create farms if not exist
        $this->farms = Farm::take(3)->get();
        if ($this->farms->count() < 3) {
            for ($i = $this->farms->count(); $i < 3; $i++) {
                $this->farms->push(Farm::create([
                    'code' => 'FARM' . str_pad(($i + 1), 3, '0', STR_PAD_LEFT),
                    'name' => 'Farm Demo ' . ($i + 1),
                    'contact_person' => 'Manager Farm ' . ($i + 1),
                    'phone_number' => '0812345678' . $i,
                    'address' => 'Jl. Peternakan No. ' . ($i + 1),
                    'description' => 'Farm demo untuk testing smart analytics',
                    'quantity' => 0,
                    'capacity' => 50000,
                    'status' => 'active',
                    'created_by' => $this->adminUser->id
                ]));
            }
        }

        // Create coops
        foreach ($this->farms as $farm) {
            $coopsCount = Coop::where('farm_id', $farm->id)->count();
            if ($coopsCount < 2) {
                for ($i = $coopsCount; $i < 2; $i++) {
                    $this->coops[] = Coop::create([
                        'farm_id' => $farm->id,
                        'code' => 'COOP' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                        'name' => 'Kandang ' . chr(65 + $i) . '-' . $farm->name,
                        'capacity' => rand(5000, 10000),
                        'status' => 'active',
                        'created_by' => $this->adminUser->id
                    ]);
                }
            } else {
                $existingCoops = Coop::where('farm_id', $farm->id)->get();
                foreach ($existingCoops as $coop) {
                    $this->coops[] = $coop;
                }
            }
        }

        // Create suppliers
        $supplierTypes = ['Supplier DOC', 'Supplier Pakan', 'Supplier OVK'];
        foreach ($supplierTypes as $type) {
            $this->suppliers[] = Partner::firstOrCreate([
                'type' => 'supplier',
                'name' => $type . ' Demo'
            ], [
                'code' => 'SUP' . str_pad(count($this->suppliers) + 1, 3, '0', STR_PAD_LEFT),
                'email' => strtolower(str_replace(' ', '', $type)) . '@demo.com',
                'phone_number' => '08123456789',
                'address' => 'Jl. Supplier ' . $type,
                'status' => 'active',
                'created_by' => $this->adminUser->id
            ]);
        }

        // Create feeds if not exist
        $feedNames = ['Starter BR-1', 'Grower BR-2', 'Finisher BR-3'];
        foreach ($feedNames as $name) {
            $this->feeds[] = Feed::firstOrCreate(['name' => $name], [
                'code' => 'FEED' . str_pad(count($this->feeds) + 1, 3, '0', STR_PAD_LEFT),
                // 'category_id' => 1,
                'description' => 'Pakan ' . $name . ' untuk ayam broiler',
                'status' => 'active',
                'data' => [
                    'unit_id' => Unit::where('name', 'Kg')->first()?->id,
                    'conversion_units' => [
                        ['unit_id' => Unit::where('name', 'Gram')->first()?->id, 'value' => 1, 'is_smallest' => true],
                        ['unit_id' => Unit::where('name', 'Kg')->first()?->id, 'value' => 1000, 'is_default_purchase' => true],
                        ['unit_id' => Unit::where('name', 'Karung')->first()?->id, 'value' => 50000, 'is_default_mutation' => false]
                    ]
                ],
                'created_by' => $this->adminUser->id
            ]);
        }

        Log::info('✅ Basic data created', [
            'farms' => count($this->farms),
            'coops' => count($this->coops),
            'suppliers' => count($this->suppliers),
            'feeds' => count($this->feeds)
        ]);
    }

    private function createLivestockPurchases()
    {
        Log::info('🐔 Creating livestock purchases...');

        $purchaseDate = Carbon::now()->subDays(45);

        foreach ($this->farms as $farmIndex => $farm) {
            $coopsForFarm = array_filter($this->coops, fn($coop) => $coop instanceof Coop ? $coop->farm_id === $farm->id : $coop['farm_id'] === $farm->id);

            foreach ($coopsForFarm as $coopIndex => $coop) {
                $coopId = $coop instanceof Coop ? $coop->id : $coop['id'];

                // Create livestock purchase
                $purchase = LivestockPurchase::create([
                    // 'farm_id' => $farm->id,
                    'tanggal' => $purchaseDate,
                    'vendor_id' => $this->suppliers[0]->id,
                    'expedition_id' => $this->suppliers[0]->id,
                    'invoice_number' => 'INV-DOC-' . str_pad($farmIndex * 10 + $coopIndex, 4, '0', STR_PAD_LEFT),
                    'data' => [
                        'farm_id' => $farm->id,
                        'coop_id' => $coopId,
                        'batch_name' => 'Batch-' . $farm->name . '-' . ($coop instanceof Coop ? $coop->name : $coop['name']) . '-' . $purchaseDate->format('Y-m'),
                        'notes' => 'Pembelian DOC untuk kandang ' . ($coop instanceof Coop ? $coop->name : $coop['name']),
                        'total_weight' => 0,
                        // 'total_quantity' => array_sum(array_column($this->items, 'quantity')),
                        // 'total_weight' => array_sum(array_map(function ($item) {
                        //     return $item['weight_type'] === 'per_unit' ?
                        //         ($item['weight_value'] * $item['quantity']) :
                        //         $item['weight_value'];
                        // }, $this->items)),
                    ],
                    'status' => 'completed',
                    // 'notes' => 'Pembelian DOC untuk kandang ' . ($coop instanceof Coop ? $coop->name : $coop['name']),
                    'created_by' => $this->adminUser->id
                ]);

                // Create livestock purchase item
                $quantity = rand(4000, 6000);
                $purchaseItem = LivestockPurchaseItem::create([
                    'tanggal' => $purchaseDate,
                    'livestock_purchase_id' => $purchase->id,
                    'livestock_strain_id' => LivestockStrain::first()?->id,
                    'quantity' => $quantity,
                    'weight_value' => 40, // 40 gram
                    'weight_type' => 'per_unit',
                    'weight_per_unit' => 40,
                    'weight_total' => $quantity * 40,
                    'price_value' => 4500,
                    'price_type' => 'per_unit',
                    'price_per_unit' => 4500,
                    'price_total' => $quantity * 4500,
                    'tax_amount' => 0,
                    // 'total_weight' => $quantity * 0.045,
                    // 'total_amount' => $quantity * 4500,
                    'created_by' => $this->adminUser->id
                ]);

                // Create livestock master
                $livestock = Livestock::create([
                    'farm_id' => $farm->id,
                    'coop_id' => $coopId,
                    'name' => 'Batch-' . $farm->name . '-' . ($coop instanceof Coop ? $coop->name : $coop['name']) . '-' . $purchaseDate->format('Y-m'),
                    'start_date' => $purchaseDate,
                    'initial_quantity' => $quantity,
                    'initial_weight' => 40,
                    'price' => 4500,
                    'status' => 'active',
                    'data' => [
                        'purchase_id' => $purchase->id,
                        'strain' => 'Broiler',
                        'target_age' => 35
                    ],
                    'created_by' => $this->adminUser->id
                ]);

                // Create livestock batch
                LivestockBatch::create([
                    'livestock_id' => $livestock->id,
                    'livestock_purchase_item_id' => $purchaseItem->id,
                    'livestock_strain_id' => $purchaseItem->livestock_strain_id,
                    'source_type' => 'purchase',
                    'source_id' => $purchase->id,
                    'farm_id' => $farm->id,
                    'coop_id' => $coopId,
                    'name' => $livestock->name,
                    'livestock_strain_name' => 'Broiler',
                    'start_date' => $purchaseDate,
                    'initial_quantity' => $quantity,
                    'initial_weight' => 40,
                    'weight' => 40,
                    'weight_type' => 'per_unit',
                    'weight_per_unit' => 40,
                    'weight_total' => $quantity * 40,
                    'status' => 'active',
                    'created_by' => $this->adminUser->id
                ]);

                // Create current livestock
                CurrentLivestock::create([
                    'livestock_id' => $livestock->id,
                    'farm_id' => $farm->id,
                    'coop_id' => $coopId,
                    'quantity' => $quantity,
                    'berat_total' => $quantity * 40,
                    'avg_berat' => 40,
                    'age' => 0,
                    'status' => 'active',
                    'created_by' => $this->adminUser->id
                ]);

                $this->livestocks[] = $livestock;

                $purchaseItem->update([
                    'livestock_id' => $livestock->id,
                ]);

                Log::info('✅ Livestock purchase created', [
                    'farm' => $farm->name,
                    'coop' => $coop instanceof Coop ? $coop->name : $coop['name'],
                    'livestock_id' => $livestock->id,
                    'quantity' => $quantity
                ]);
            }
        }
    }

    private function createFeedPurchases()
    {
        Log::info('🌾 Creating feed purchases...');

        $purchaseDate = Carbon::now()->subDays(40);

        foreach ($this->livestocks as $livestock) {
            // Create feed purchase batch
            $batch = FeedPurchaseBatch::create([
                'date' => $purchaseDate,
                'supplier_id' => $this->suppliers[1]->id,
                'invoice_number' => 'INV-FEED-' . $livestock->id,
                'notes' => 'Pembelian pakan untuk ' . $livestock->name,
                'created_by' => $this->adminUser->id
            ]);

            foreach ($this->feeds as $feed) {
                $quantity = rand(10, 30); // karung
                $convertedQuantity = $quantity * 50; // kg

                // Create feed purchase
                $purchase = FeedPurchase::create([
                    'livestock_id' => $livestock->id,
                    'feed_purchase_batch_id' => $batch->id,
                    'feed_id' => $feed->id,
                    'unit_id' => Unit::where('name', 'Karung')->first()?->id,
                    'quantity' => $quantity,
                    'converted_unit' => Unit::where('name', 'Kg')->first()?->id,
                    'converted_quantity' => $convertedQuantity,
                    'price_per_unit' => 350000, // per karung
                    'price_per_converted_unit' => 7000, // per kg
                    'created_by' => $this->adminUser->id
                ]);

                // Create feed stock
                FeedStock::create([
                    'livestock_id' => $livestock->id,
                    'feed_id' => $feed->id,
                    'feed_purchase_id' => $purchase->id,
                    'date' => $purchaseDate,
                    'source_type' => 'purchase',
                    'source_id' => $purchase->id,
                    'quantity_in' => $convertedQuantity * 1000, // convert to grams
                    'quantity_used' => 0,
                    'quantity_mutated' => 0,
                    'created_by' => $this->adminUser->id
                ]);

                // Create current feed
                CurrentFeed::updateOrCreate([
                    'livestock_id' => $livestock->id,
                    'feed_id' => $feed->id
                ], [
                    'farm_id' => $livestock->farm_id,
                    'coop_id' => $livestock->coop_id,
                    'unit_id' => Unit::where('name', 'Gram')->first()?->id,
                    'quantity' => $convertedQuantity * 1000,
                    'status' => 'active',
                    'created_by' => $this->adminUser->id
                ]);
            }

            Log::info('✅ Feed purchases created', [
                'livestock' => $livestock->name,
                'feeds_count' => count($this->feeds),
                'total_quantity_kg' => array_sum(array_map(fn($feed) => rand(10, 30) * 50, $this->feeds))
            ]);
        }
    }

    private function createSupplyPurchases()
    {
        Log::info('💊 Creating supply purchases...');

        $purchaseDate = Carbon::now()->subDays(35);

        // Create supplies if not exist
        $supplyNames = ['Obat', 'Vitamin', 'Antibiotik', 'Vaksin', 'OVK'];
        foreach ($supplyNames as $name) {
            $this->supplies[] = Supply::firstOrCreate(['name' => $name], [
                'code' => 'SUP' . str_pad(count($this->supplies) + 1, 3, '0', STR_PAD_LEFT),
                'supply_category_id' => SupplyCategory::where('name', 'like', '%' . $name . '%')->first()?->id,
                'description' => $name . ' untuk ayam broiler',
                'status' => 'active',
                'data' => [
                    'unit_id' => Unit::where('name', 'Botol')->first()?->id ?? Unit::first()->id
                ],
                'created_by' => $this->adminUser->id
            ]);
        }

        foreach ($this->livestocks as $livestock) {
            // Create supply purchase batch
            $batch = SupplyPurchaseBatch::create([
                'date' => $purchaseDate,
                'supplier_id' => $this->suppliers[2]->id,
                'invoice_number' => 'INV-OVK-' . $livestock->id,
                'notes' => 'Pembelian OVK untuk ' . $livestock->name,
                'created_by' => $this->adminUser->id
            ]);

            foreach ($this->supplies as $supply) {
                $quantity = rand(5, 15);

                // Refactored to align with the flow of @Create.php
                $selectedUnit = Unit::where('name', 'Botol')->first();
                $smallestUnit = Unit::where('name', 'Gram')->first();
                $convertedQuantity = $smallestUnit->value > 0 ? ($quantity * $selectedUnit->value) / $smallestUnit->value : 0;

                $purchase = SupplyPurchase::updateOrCreate(
                    [
                        'supply_purchase_batch_id' => $batch->id,
                        'supply_id' => $supply->id,
                        'unit_id' => $selectedUnit->id,
                    ],
                    [
                        'farm_id' => $livestock->farm_id,
                        'quantity' => $quantity,
                        'converted_quantity' => $convertedQuantity,
                        'converted_unit' => $smallestUnit->id,
                        'price_per_unit' => 25000,
                        'price_per_converted_unit' => $selectedUnit->id !== $smallestUnit->id
                            ? round(25000 * ($smallestUnit->value / ($selectedUnit->value ?: 1)), 2)
                            : 25000,
                        'created_by' => $this->adminUser->id,
                        'updated_by' => $this->adminUser->id,
                    ]
                );

                // Create supply stock
                SupplyStock::create([
                    'farm_id' => $livestock->farm_id,
                    'supply_id' => $supply->id,
                    'supply_purchase_id' => $purchase->id,
                    'date' => $purchaseDate,
                    'source_type' => 'purchase',
                    'source_id' => $purchase->id,
                    'quantity_in' => $quantity,
                    'quantity_used' => 0,
                    'quantity_mutated' => 0,
                    'created_by' => $this->adminUser->id
                ]);

                // Create current supply
                CurrentSupply::updateOrCreate([
                    'farm_id' => $livestock->farm_id,
                    'item_id' => $supply->id,
                    'type' => 'supply'
                ], [
                    'farm_id' => $livestock->farm_id,
                    'coop_id' => $livestock->coop_id,
                    'unit_id' => Unit::where('name', 'Botol')->first()?->id ?? Unit::first()->id,
                    'quantity' => $quantity,
                    'status' => 'active',
                    'created_by' => $this->adminUser->id
                ]);
            }

            Log::info('✅ Supply purchases created', [
                'livestock' => $livestock->name,
                'supplies_count' => count($this->supplies)
            ]);
        }
    }

    private function createDailyRecordings()
    {
        Log::info('📊 Creating daily recordings...');

        foreach ($this->livestocks as $livestock) {
            $startDate = Carbon::parse($livestock->start_date);
            $currentDate = $startDate->copy();
            $endDate = Carbon::now()->subDays(1);

            $currentPopulation = $livestock->initial_quantity;
            $currentWeight = $livestock->initial_weight;
            $totalFeedUsed = 0;
            $totalDepletion = 0;

            Log::info('📈 Creating recordings for livestock', [
                'livestock' => $livestock->name,
                'period' => $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d'),
                'days' => $startDate->diffInDays($endDate)
            ]);

            while ($currentDate <= $endDate) {
                $age = $startDate->diffInDays($currentDate);

                // Calculate mortality/culling (realistic rates)
                $mortalityRate = $this->calculateMortalityRate($age);
                $mortality = max(0, round($currentPopulation * $mortalityRate / 100));
                $culling = $age > 20 && rand(1, 100) <= 5 ? rand(1, 3) : 0;

                // Calculate weight gain (realistic growth curve)
                $weightGain = $this->calculateWeightGain($age);
                $previousWeight = $currentWeight;
                $currentWeight += $weightGain;

                // Calculate feed consumption
                $feedPerBird = $this->calculateFeedConsumption($age, $currentWeight);
                $totalDailyFeed = $currentPopulation * $feedPerBird;

                // Update populations
                $dailyDepletion = $mortality + $culling;
                $stockAwal = $currentPopulation;
                $currentPopulation -= $dailyDepletion;
                $totalDepletion += $dailyDepletion;
                $totalFeedUsed += $totalDailyFeed;

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
                    'pakan_jenis' => $this->feeds[0]->name, // Primary feed
                    'pakan_harian' => $totalDailyFeed,
                    'payload' => [
                        'mortality' => $mortality,
                        'culling' => $culling,
                        'sales_quantity' => 0,
                        'feed_consumption' => $totalDailyFeed,
                        'weight_gain' => $weightGain,
                        'fcr' => $totalFeedUsed > 0 ? round($totalFeedUsed / ($currentWeight * $currentPopulation), 3) : 0,
                        'age_days' => $age,
                        'population' => $currentPopulation,
                        'version' => '2.0',
                        'recorded_at' => now()->toIso8601String(),
                        'recorded_by' => [
                            'id' => $this->adminUser->id,
                            'name' => $this->adminUser->name,
                            'role' => 'System Seeder'
                        ]
                    ],
                    'created_by' => $this->adminUser->id,
                    'updated_by' => $this->adminUser->id
                ]);

                // Create depletion records
                if ($mortality > 0) {
                    LivestockDepletion::create([
                        'livestock_id' => $livestock->id,
                        'recording_id' => $recording->id,
                        'tanggal' => $currentDate->format('Y-m-d'),
                        'jumlah' => $mortality,
                        'jenis' => 'Mati',
                        'data' => [
                            'age_days' => $age,
                            'percentage' => round(($mortality / $stockAwal) * 100, 2),
                            'created_by_seeder' => true
                        ],
                        'created_by' => $this->adminUser->id
                    ]);
                }

                if ($culling > 0) {
                    LivestockDepletion::create([
                        'livestock_id' => $livestock->id,
                        'recording_id' => $recording->id,
                        'tanggal' => $currentDate->format('Y-m-d'),
                        'jumlah' => $culling,
                        'jenis' => 'Afkir',
                        'data' => [
                            'age_days' => $age,
                            'reason' => 'Underweight',
                            'created_by_seeder' => true
                        ],
                        'created_by' => $this->adminUser->id
                    ]);
                }

                // Create feed usage
                if ($totalDailyFeed > 0) {
                    $feedUsage = FeedUsage::create([
                        'livestock_id' => $livestock->id,
                        'recording_id' => $recording->id,
                        'usage_date' => $currentDate->format('Y-m-d'),
                        'total_quantity' => $totalDailyFeed,
                        'created_by' => $this->adminUser->id
                    ]);

                    // Create feed usage details (distribute across available feeds)
                    $feedPerType = $totalDailyFeed / count($this->feeds);
                    foreach ($this->feeds as $feed) {
                        $feedStock = FeedStock::where('livestock_id', $livestock->id)
                            ->where('feed_id', $feed->id)
                            ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
                            ->first();

                        if ($feedStock && $feedPerType > 0) {
                            FeedUsageDetail::create([
                                'feed_usage_id' => $feedUsage->id,
                                'feed_stock_id' => $feedStock->id,
                                'feed_id' => $feed->id,
                                'quantity_taken' => $feedPerType,
                                'created_by' => $this->adminUser->id
                            ]);

                            // Update feed stock
                            $feedStock->update([
                                'quantity_used' => $feedStock->quantity_used + $feedPerType
                            ]);
                        }
                    }
                }

                $currentDate->addDay();

                // Log progress every 7 days
                if ($age % 7 === 0) {
                    Log::info('📅 Recording progress', [
                        'livestock' => $livestock->name,
                        'age' => $age,
                        'population' => $currentPopulation,
                        'weight' => round($currentWeight, 3),
                        'total_feed' => round($totalFeedUsed, 0),
                        'mortality_total' => $totalDepletion
                    ]);
                }
            }

            // Update Livestock quantity_depletion with total depletion
            $livestock->update([
                'quantity_depletion' => $totalDepletion,
                'updated_by' => $this->adminUser->id
            ]);

            // Update current livestock with real-time calculation
            $currentLivestock = CurrentLivestock::where('livestock_id', $livestock->id)->first();
            if ($currentLivestock) {
                // Calculate real-time quantity: initial_quantity - quantity_depletion - quantity_sales - quantity_mutated
                $realTimeQuantity = $livestock->initial_quantity
                    - $totalDepletion
                    - ($livestock->quantity_sales ?? 0)
                    - ($livestock->quantity_mutated ?? 0);

                $realTimeQuantity = max(0, $realTimeQuantity);

                $currentLivestock->update([
                    'quantity' => $realTimeQuantity,
                    'metadata' => [
                        'final_age' => $age,
                        'final_weight' => $currentWeight,
                        'total_feed_consumed' => $totalFeedUsed,
                        'total_depletion' => $totalDepletion,
                        'mortality_rate' => round(($totalDepletion / $livestock->initial_quantity) * 100, 2),
                        'calculation_source' => 'seeder_realtime_calculation',
                        'formula_breakdown' => [
                            'initial_quantity' => $livestock->initial_quantity,
                            'quantity_depletion' => $totalDepletion,
                            'quantity_sales' => $livestock->quantity_sales ?? 0,
                            'quantity_mutated' => $livestock->quantity_mutated ?? 0,
                            'calculated_quantity' => $realTimeQuantity
                        ],
                        'updated_by_seeder' => true,
                        'last_updated' => now()->toIso8601String()
                    ]
                ]);

                Log::info('✅ Updated livestock quantities', [
                    'livestock_name' => $livestock->name,
                    'initial_quantity' => $livestock->initial_quantity,
                    'total_depletion' => $totalDepletion,
                    'final_current_quantity' => $realTimeQuantity,
                    'formula' => sprintf(
                        '%d - %d - %d - %d = %d',
                        $livestock->initial_quantity,
                        $totalDepletion,
                        $livestock->quantity_sales ?? 0,
                        $livestock->quantity_mutated ?? 0,
                        $realTimeQuantity
                    )
                ]);
            }
        }

        Log::info('✅ Daily recordings completed', [
            'total_livestock' => count($this->livestocks),
            'total_recordings' => Recording::count()
        ]);
    }

    private function calculateMortalityRate($age)
    {
        // Realistic broiler mortality rate curve
        if ($age <= 7) return 0.3; // Week 1: 0.3%
        if ($age <= 14) return 0.15; // Week 2: 0.15%
        if ($age <= 21) return 0.1; // Week 3: 0.1%
        if ($age <= 28) return 0.05; // Week 4: 0.05%
        return 0.02; // Week 5+: 0.02%
    }

    private function calculateWeightGain($age)
    {
        // Realistic broiler weight gain curve (kg per day)
        if ($age <= 7) return 0.015; // 15g/day week 1
        if ($age <= 14) return 0.035; // 35g/day week 2
        if ($age <= 21) return 0.055; // 55g/day week 3
        if ($age <= 28) return 0.065; // 65g/day week 4
        if ($age <= 35) return 0.070; // 70g/day week 5
        return 0.050; // 50g/day week 6+
    }

    private function calculateFeedConsumption($age, $weight)
    {
        // Feed consumption based on age and weight (grams per bird per day)
        $baseConsumption = $weight * 0.08; // 8% of body weight
        $ageMultiplier = 1 + ($age * 0.015); // Increase with age
        return max(10, $baseConsumption * $ageMultiplier);
    }
}

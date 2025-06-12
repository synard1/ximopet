<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\{User, Farm, Coop, Livestock, Recording, LivestockDepletion};

class ExtendedRecordingSeeder extends Seeder
{
    public function run()
    {
        Log::info('🚀 Starting Extended Recording Seeder');

        DB::transaction(function () {
            $this->createAdditionalData();
        });

        Log::info('✅ Extended Recording Seeder completed successfully');
    }

    private function createAdditionalData()
    {
        $adminUser = User::first();
        if (!$adminUser) {
            throw new \Exception('No admin user found');
        }

        // Get existing farms or create more
        $farms = Farm::all();
        if ($farms->count() < 3) {
            for ($i = $farms->count(); $i < 3; $i++) {
                $farms->push(Farm::create([
                    'code' => 'FARM' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                    'name' => 'Farm Demo ' . ($i + 1),
                    'contact_person' => 'Manager Demo ' . ($i + 1),
                    'phone_number' => '08123456789' . $i,
                    'address' => 'Jl. Demo No. ' . ($i + 1),
                    'description' => 'Farm demo untuk testing smart analytics',
                    'quantity' => 0,
                    'capacity' => 50000,
                    'status' => 'active',
                    'created_by' => $adminUser->id
                ]));
            }
        }

        Log::info('📊 Creating extended livestock data...');

        foreach ($farms as $farmIndex => $farm) {
            // Create 2 coops per farm if not exist
            $coops = Coop::where('farm_id', $farm->id)->get();
            while ($coops->count() < 2) {
                $coopIndex = $coops->count() + 1;
                $coop = Coop::create([
                    'farm_id' => $farm->id,
                    'code' => 'COOP' . $farm->id . '-' . $coopIndex,
                    'name' => 'Kandang ' . chr(64 + $coopIndex) . ' - ' . $farm->name,
                    'capacity' => rand(4000, 8000),
                    'status' => 'active',
                    'created_by' => $adminUser->id
                ]);
                $coops->push($coop);
            }

            foreach ($coops as $coopIndex => $coop) {
                // Check if livestock already exists for this coop
                $existingLivestock = Livestock::where('farm_id', $farm->id)
                    ->where('coop_id', $coop->id)
                    ->first();

                if ($existingLivestock) {
                    Log::info('⏭️ Skipping existing livestock', [
                        'farm' => $farm->name,
                        'coop' => $coop->name,
                        'livestock' => $existingLivestock->name
                    ]);
                    continue;
                }

                // Create different scenarios for each farm
                $scenarios = [
                    ['start_days_ago' => 45, 'performance' => 'good', 'mortality_factor' => 0.8],
                    ['start_days_ago' => 35, 'performance' => 'average', 'mortality_factor' => 1.0],
                    ['start_days_ago' => 25, 'performance' => 'poor', 'mortality_factor' => 1.5]
                ];

                $scenario = $scenarios[$farmIndex % 3];
                $startDate = Carbon::now()->subDays($scenario['start_days_ago']);
                $initialQuantity = rand(3500, 5500);

                // Create livestock
                $livestock = Livestock::create([
                    'farm_id' => $farm->id,
                    'coop_id' => $coop->id,
                    'name' => 'Batch-' . $farm->name . '-' . $coop->name . '-' . $startDate->format('Y-m'),
                    'start_date' => $startDate,
                    'initial_quantity' => $initialQuantity,
                    'initial_weight' => 0.045,
                    'price' => 4500,
                    'status' => 'active',
                    'data' => [
                        'scenario' => $scenario['performance'],
                        'demo' => true
                    ],
                    'created_by' => $adminUser->id
                ]);

                Log::info('🐔 Creating livestock with scenario', [
                    'livestock' => $livestock->name,
                    'scenario' => $scenario['performance'],
                    'initial_quantity' => $initialQuantity,
                    'start_date' => $startDate->format('Y-m-d')
                ]);

                // Create recordings
                $this->createRecordings($livestock, $scenario, $adminUser);
            }
        }
    }

    private function createRecordings($livestock, $scenario, $adminUser)
    {
        $startDate = Carbon::parse($livestock->start_date);
        $currentDate = $startDate->copy();
        $endDate = Carbon::now()->subDays(1);

        $currentPopulation = $livestock->initial_quantity;
        $currentWeight = $livestock->initial_weight;
        $totalFeedUsed = 0;

        while ($currentDate <= $endDate) {
            $age = $startDate->diffInDays($currentDate) + 1;

            // Calculate mortality based on scenario
            $baseMortalityRate = $this->getBaseMortalityRate($age);
            $adjustedMortalityRate = $baseMortalityRate * $scenario['mortality_factor'];
            $mortality = max(0, round($currentPopulation * ($adjustedMortalityRate / 100)));

            // Calculate weight gain based on scenario
            $baseWeightGain = $this->calculateWeightGain($age);
            $performanceMultiplier = match ($scenario['performance']) {
                'good' => 1.1,
                'average' => 1.0,
                'poor' => 0.85,
                default => 1.0
            };
            $weightGain = $baseWeightGain * $performanceMultiplier;

            $previousWeight = $currentWeight;
            $currentWeight += $weightGain;

            // Calculate feed consumption
            $feedConsumption = $currentPopulation * $currentWeight * 0.08;
            $totalFeedUsed += $feedConsumption;

            // Update population
            $stockAwal = $currentPopulation;
            $currentPopulation -= $mortality;

            // Add some random culling occasionally
            $culling = ($age > 21 && rand(1, 100) <= 3) ? rand(1, 5) : 0;
            $currentPopulation -= $culling;

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
                'pakan_jenis' => $this->getFeedType($age),
                'pakan_harian' => $feedConsumption,
                'payload' => [
                    'mortality' => $mortality,
                    'culling' => $culling,
                    'age_days' => $age,
                    'population' => $currentPopulation,
                    'scenario' => $scenario['performance'],
                    'fcr' => $this->calculateFCR($totalFeedUsed, $currentWeight, $currentPopulation, $livestock->initial_quantity),
                    'mortality_rate_cumulative' => round((($livestock->initial_quantity - $currentPopulation) / $livestock->initial_quantity) * 100, 2),
                    'version' => '2.0',
                    'recorded_by' => [
                        'id' => $adminUser->id,
                        'name' => $adminUser->name,
                        'role' => 'System Seeder'
                    ]
                ],
                'created_by' => $adminUser->id
            ]);

            // Create depletion records
            if ($mortality > 0) {
                LivestockDepletion::create([
                    'livestock_id' => $livestock->id,
                    'recording_id' => $recording->id,
                    'tanggal' => $currentDate->format('Y-m-d'),
                    'jumlah' => $mortality,
                    'jenis' => 'Mati',
                    'created_by' => $adminUser->id
                ]);
            }

            if ($culling > 0) {
                LivestockDepletion::create([
                    'livestock_id' => $livestock->id,
                    'recording_id' => $recording->id,
                    'tanggal' => $currentDate->format('Y-m-d'),
                    'jumlah' => $culling,
                    'jenis' => 'Afkir',
                    'created_by' => $adminUser->id
                ]);
            }

            $currentDate->addDay();
        }

        $totalRecordings = Recording::where('livestock_id', $livestock->id)->count();
        $totalMortality = LivestockDepletion::where('livestock_id', $livestock->id)->sum('jumlah');
        $survivalRate = round((($livestock->initial_quantity - $totalMortality) / $livestock->initial_quantity) * 100, 2);
        $finalFCR = $this->calculateFCR($totalFeedUsed, $currentWeight, $currentPopulation, $livestock->initial_quantity);

        Log::info('✅ Recordings completed for livestock', [
            'livestock' => $livestock->name,
            'scenario' => $scenario['performance'],
            'recordings' => $totalRecordings,
            'survival_rate' => $survivalRate . '%',
            'final_weight' => round($currentWeight, 3) . ' kg',
            'final_population' => $currentPopulation,
            'fcr' => $finalFCR
        ]);
    }

    private function getBaseMortalityRate($age)
    {
        // Realistic broiler mortality rate curve (daily %)
        if ($age <= 7) return 0.3;     // Week 1: 0.3%
        if ($age <= 14) return 0.15;   // Week 2: 0.15%
        if ($age <= 21) return 0.1;    // Week 3: 0.1%
        if ($age <= 28) return 0.05;   // Week 4: 0.05%
        return 0.02;                   // Week 5+: 0.02%
    }

    private function calculateWeightGain($age)
    {
        // Realistic broiler weight gain curve (kg per day)
        if ($age <= 7) return 0.015;   // 15g/day week 1
        if ($age <= 14) return 0.035;  // 35g/day week 2
        if ($age <= 21) return 0.055;  // 55g/day week 3
        if ($age <= 28) return 0.065;  // 65g/day week 4
        if ($age <= 35) return 0.070;  // 70g/day week 5
        return 0.050;                  // 50g/day week 6+
    }

    private function getFeedType($age)
    {
        if ($age <= 14) return 'Starter BR-1';
        if ($age <= 28) return 'Grower BR-2';
        return 'Finisher BR-3';
    }

    private function calculateFCR($totalFeedUsed, $currentWeight, $currentPopulation, $initialQuantity)
    {
        if ($currentWeight <= 0 || $currentPopulation <= 0) return 0;

        // Total weight gain = (current weight * current population) - (initial weight * initial population)
        $totalCurrentWeight = $currentWeight * $currentPopulation;
        $totalInitialWeight = 0.045 * $initialQuantity; // Initial weight was 45g
        $totalWeightGain = $totalCurrentWeight - $totalInitialWeight;

        return $totalWeightGain > 0 ? round($totalFeedUsed / $totalWeightGain, 3) : 0;
    }
}

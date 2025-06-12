<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\{User, Farm, Coop, Livestock, Recording, LivestockDepletion};

class BasicRecordingSeeder extends Seeder
{
    public function run()
    {
        Log::info('🚀 Starting Basic Recording Seeder');

        DB::transaction(function () {
            $this->createData();
        });

        Log::info('✅ Basic Recording Seeder completed successfully');
    }

    private function createData()
    {
        // Get admin user
        $adminUser = User::first();
        if (!$adminUser) {
            throw new \Exception('No admin user found');
        }

        // Get or create existing livestock
        $livestock = Livestock::first();
        if (!$livestock) {
            // Create basic farm and coop first
            $farm = Farm::create([
                'code' => 'FARM001',
                'name' => 'Farm Demo 1',
                'contact_person' => 'Manager Demo',
                'phone_number' => '08123456789',
                'address' => 'Jl. Demo No. 1',
                'description' => 'Farm demo untuk testing',
                'quantity' => 0,
                'capacity' => 50000,
                'status' => 'active',
                'created_by' => $adminUser->id
            ]);

            $coop = Coop::create([
                'farm_id' => $farm->id,
                'code' => 'COOP001',
                'name' => 'Kandang Demo A',
                'capacity' => 5000,
                'status' => 'active',
                'created_by' => $adminUser->id
            ]);

            $livestock = Livestock::create([
                'farm_id' => $farm->id,
                'coop_id' => $coop->id,
                'name' => 'Batch Demo ' . now()->format('Y-m'),
                'start_date' => Carbon::now()->subDays(30),
                'initial_quantity' => 5000,
                'initial_weight' => 0.045,
                'price' => 4500,
                'status' => 'active',
                'data' => ['demo' => true],
                'created_by' => $adminUser->id
            ]);

            Log::info('✅ Created basic farm infrastructure', [
                'farm_id' => $farm->id,
                'coop_id' => $coop->id,
                'livestock_id' => $livestock->id
            ]);
        }

        // Create daily recordings for 30 days
        $startDate = Carbon::now()->subDays(30);
        $currentDate = $startDate->copy();
        $currentPopulation = $livestock->initial_quantity;
        $currentWeight = $livestock->initial_weight;

        Log::info('📊 Creating daily recordings', [
            'livestock_id' => $livestock->id,
            'start_date' => $startDate->format('Y-m-d'),
            'initial_population' => $currentPopulation
        ]);

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
                'pakan_jenis' => 'Starter BR-1',
                'pakan_harian' => $feedConsumption,
                'payload' => [
                    'mortality' => $mortality,
                    'age_days' => $age,
                    'population' => $currentPopulation,
                    'fcr' => $this->calculateFCR($age, $feedConsumption, $currentWeight, $currentPopulation),
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
                    'mortality_total' => $livestock->initial_quantity - $currentPopulation,
                    'fcr' => $this->calculateFCR($age, $feedConsumption, $currentWeight, $currentPopulation)
                ]);
            }
        }

        // Generate summary
        $totalRecordings = Recording::where('livestock_id', $livestock->id)->count();
        $totalMortality = LivestockDepletion::where('livestock_id', $livestock->id)->sum('jumlah');
        $survivalRate = round((($livestock->initial_quantity - $totalMortality) / $livestock->initial_quantity) * 100, 2);

        Log::info('✅ Recording generation completed', [
            'livestock_id' => $livestock->id,
            'total_recordings' => $totalRecordings,
            'total_mortality' => $totalMortality,
            'survival_rate' => $survivalRate . '%',
            'final_weight' => round($currentWeight, 3) . ' kg',
            'final_population' => $currentPopulation
        ]);
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

    private function calculateFCR($age, $dailyFeed, $currentWeight, $population)
    {
        if ($currentWeight <= 0 || $population <= 0) return 0;

        // Simple FCR calculation
        $totalFeedConsumed = $dailyFeed * $age; // Approximate total feed
        $totalWeightGain = $currentWeight * $population;

        return $totalWeightGain > 0 ? round($totalFeedConsumed / $totalWeightGain, 3) : 0;
    }
}

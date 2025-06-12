<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PerformanceBenchmark;
use App\Models\LivestockStrain;
use Illuminate\Support\Str;

class PerformanceBenchmarkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing strains or create default ones
        $strains = LivestockStrain::all();

        if ($strains->isEmpty()) {
            // Create default strains if none exist
            $defaultStrains = [
                ['name' => 'Broiler Ross 308', 'code' => 'ROSS308'],
                ['name' => 'Broiler Cobb 500', 'code' => 'COBB500'],
                ['name' => 'Broiler Arbor Acres', 'code' => 'AA'],
            ];

            foreach ($defaultStrains as $strainData) {
                $strains[] = LivestockStrain::create([
                    'id' => Str::uuid(),
                    'name' => $strainData['name'],
                    'code' => $strainData['code'],
                    'description' => 'Default strain for analytics benchmarks',
                    'is_active' => true,
                    'created_by' => 1,
                ]);
            }
        }

        // Benchmark data for broiler chickens (weekly targets)
        $benchmarkData = [
            // Week 1
            1 => [
                'target_weight' => 180,
                'target_fcr' => 0.85,
                'target_mortality_rate' => 0.5,
                'target_daily_gain' => 20,
                'weight_min' => 160,
                'weight_max' => 200,
                'fcr_min' => 0.75,
                'fcr_max' => 0.95,
                'mortality_max' => 1.0,
            ],
            // Week 2
            2 => [
                'target_weight' => 450,
                'target_fcr' => 1.15,
                'target_mortality_rate' => 0.8,
                'target_daily_gain' => 38,
                'weight_min' => 400,
                'weight_max' => 500,
                'fcr_min' => 1.05,
                'fcr_max' => 1.25,
                'mortality_max' => 1.5,
            ],
            // Week 3
            3 => [
                'target_weight' => 850,
                'target_fcr' => 1.35,
                'target_mortality_rate' => 1.0,
                'target_daily_gain' => 57,
                'weight_min' => 750,
                'weight_max' => 950,
                'fcr_min' => 1.25,
                'fcr_max' => 1.45,
                'mortality_max' => 2.0,
            ],
            // Week 4
            4 => [
                'target_weight' => 1350,
                'target_fcr' => 1.50,
                'target_mortality_rate' => 1.2,
                'target_daily_gain' => 71,
                'weight_min' => 1200,
                'weight_max' => 1500,
                'fcr_min' => 1.40,
                'fcr_max' => 1.60,
                'mortality_max' => 2.5,
            ],
            // Week 5
            5 => [
                'target_weight' => 1900,
                'target_fcr' => 1.65,
                'target_mortality_rate' => 1.5,
                'target_daily_gain' => 79,
                'weight_min' => 1700,
                'weight_max' => 2100,
                'fcr_min' => 1.55,
                'fcr_max' => 1.75,
                'mortality_max' => 3.0,
            ],
            // Week 6
            6 => [
                'target_weight' => 2500,
                'target_fcr' => 1.80,
                'target_mortality_rate' => 2.0,
                'target_daily_gain' => 86,
                'weight_min' => 2200,
                'weight_max' => 2800,
                'fcr_min' => 1.70,
                'fcr_max' => 1.90,
                'mortality_max' => 3.5,
            ],
            // Week 7
            7 => [
                'target_weight' => 3100,
                'target_fcr' => 1.95,
                'target_mortality_rate' => 2.5,
                'target_daily_gain' => 86,
                'weight_min' => 2800,
                'weight_max' => 3400,
                'fcr_min' => 1.85,
                'fcr_max' => 2.05,
                'mortality_max' => 4.0,
            ],
            // Week 8
            8 => [
                'target_weight' => 3700,
                'target_fcr' => 2.10,
                'target_mortality_rate' => 3.0,
                'target_daily_gain' => 86,
                'weight_min' => 3400,
                'weight_max' => 4000,
                'fcr_min' => 2.00,
                'fcr_max' => 2.20,
                'mortality_max' => 4.5,
            ],
        ];

        // Create benchmarks for each strain and week
        foreach ($strains as $strain) {
            foreach ($benchmarkData as $week => $data) {
                PerformanceBenchmark::create([
                    'id' => Str::uuid(),
                    'strain_id' => $strain->id,
                    'age_week' => $week,
                    'target_weight' => $data['target_weight'],
                    'target_fcr' => $data['target_fcr'],
                    'target_mortality_rate' => $data['target_mortality_rate'],
                    'target_daily_gain' => $data['target_daily_gain'],
                    'weight_min' => $data['weight_min'],
                    'weight_max' => $data['weight_max'],
                    'fcr_min' => $data['fcr_min'],
                    'fcr_max' => $data['fcr_max'],
                    'mortality_max' => $data['mortality_max'],
                    'is_active' => true,
                    'created_by' => 1,
                ]);
            }
        }

        $this->command->info('Performance benchmarks seeded successfully!');
        $this->command->info('Created benchmarks for ' . $strains->count() . ' strains across 8 weeks');
    }
}

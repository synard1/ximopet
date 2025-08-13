<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ExpeditionTariff;
use App\Models\Expedition;
use App\Models\User;

class ExpeditionTariffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get an existing user for created_by field
        $user = User::first();
        if (!$user) {
            $this->command->error('No users found in database. Please create a user first.');
            return;
        }

        // Get existing expeditions or create sample ones
        $expeditions = Expedition::all();

        if ($expeditions->isEmpty()) {
            // Create sample expeditions if none exist
            $expeditions = collect([
                Expedition::create([
                    'code' => 'JNE01',
                    'name' => 'JNE Express',
                    'contact_person' => 'Customer Service JNE',
                    'phone_number' => '1500-125',
                    'address' => 'Jakarta Pusat',
                    'status' => 'active',
                    'created_by' => $user->id,
                ]),
                Expedition::create([
                    'code' => 'TIKI01',
                    'name' => 'TIKI Express',
                    'contact_person' => 'Customer Service TIKI',
                    'phone_number' => '1500-125',
                    'address' => 'Jakarta Selatan',
                    'status' => 'active',
                    'created_by' => $user->id,
                ]),
                Expedition::create([
                    'code' => 'SICEPAT01',
                    'name' => 'SiCepat Express',
                    'contact_person' => 'Customer Service SiCepat',
                    'phone_number' => '021-2927-2927',
                    'address' => 'Tangerang',
                    'status' => 'active',
                    'created_by' => $user->id,
                ]),
            ]);
        }

        // Define simple zones with estimated rates
        $zones = [
            'Jakarta' => [
                'description' => 'DKI Jakarta dan sekitarnya',
                'estimated_rates' => [
                    'JNE01' => 2500,      // JNE rate per kg
                    'TIKI01' => 2300,     // TIKI rate per kg  
                    'SICEPAT01' => 2100,  // SiCepat rate per kg
                ]
            ],
            'Jawa Barat' => [
                'description' => 'Provinsi Jawa Barat',
                'estimated_rates' => [
                    'JNE01' => 3000,
                    'TIKI01' => 2800,
                    'SICEPAT01' => 2600,
                ]
            ],
            'Jawa Tengah' => [
                'description' => 'Provinsi Jawa Tengah',
                'estimated_rates' => [
                    'JNE01' => 3500,
                    'TIKI01' => 3300,
                    'SICEPAT01' => 3100,
                ]
            ],
            'Jawa Timur' => [
                'description' => 'Provinsi Jawa Timur',
                'estimated_rates' => [
                    'JNE01' => 3800,
                    'TIKI01' => 3600,
                    'SICEPAT01' => 3400,
                ]
            ],
            'Sumatera' => [
                'description' => 'Pulau Sumatera',
                'estimated_rates' => [
                    'JNE01' => 5500,
                    'TIKI01' => 5300,
                    'SICEPAT01' => 5000,
                ]
            ],
            'Kalimantan' => [
                'description' => 'Pulau Kalimantan',
                'estimated_rates' => [
                    'JNE01' => 6500,
                    'TIKI01' => 6200,
                    'SICEPAT01' => 5800,
                ]
            ],
        ];

        foreach ($expeditions as $expedition) {
            foreach ($zones as $zoneName => $zoneData) {
                $estimatedRate = $zoneData['estimated_rates'][$expedition->code] ?? 3000;

                ExpeditionTariff::create([
                    'expedition_id' => $expedition->id,
                    'zone_name' => $zoneName,
                    'zone_description' => $zoneData['description'],
                    'estimated_rate_per_kg' => $estimatedRate,
                    'notes' => 'Tarif estimasi untuk zona ' . $zoneName,
                    'is_active' => true,
                ]);
            }
        }

        $this->command->info('Simple expedition tariffs seeded successfully!');
        $this->command->info('Total expeditions: ' . $expeditions->count());
        $this->command->info('Total zones: ' . count($zones));
        $this->command->info('Total reference tariffs created: ' . ExpeditionTariff::count());
    }
}
 
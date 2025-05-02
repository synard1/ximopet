<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Unit;
use App\Models\User; // Asumsi ada model User
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Ambil ID user pertama (atau sesuaikan dengan kebutuhan Anda)
        $user = User::first();
        $userId = $user ? $user->id : 1; // Jika tidak ada user, pakai ID 1

        $units = [
            [
                'type' => 'Obat',
                'code' => 'SAT01',
                'name' => 'AMPUL',
                'created_by' => $userId,
            ],
            [
                'type' => 'Obat',
                'code' => 'SAT02',
                'name' => 'BOTOL',
                'created_by' => $userId,
            ],
            [
                'type' => 'Umum',
                'code' => 'BOX',
                'name' => 'BOX',
                'created_by' => $userId,
            ],
            [
                'type' => 'Umum',
                'code' => 'SAT03',
                'name' => 'BUNGKUS',
                'created_by' => $userId,
            ],
            [
                'type' => 'Volume',
                'code' => 'SAT04',
                'name' => 'CC',
                'created_by' => $userId,
            ],
            [
                'type' => 'Panjang',
                'code' => 'SAT05',
                'name' => 'CM',
                'created_by' => $userId,
            ],
            [
                'type' => 'Obat',
                'code' => 'SAT06',
                'name' => 'FLS',
                'created_by' => $userId,
            ],
            [
                'type' => 'Volume',
                'code' => 'SAT07',
                'name' => 'GALON',
                'created_by' => $userId,
            ],
            [
                'type' => 'Berat',
                'code' => 'SAT',
                'name' => 'GRAM',
                'created_by' => $userId,
            ],
            [
                'type' => 'Obat',
                'code' => 'SAT08',
                'name' => 'INHALER',
                'created_by' => $userId,
            ],
            [
                'type' => 'Obat',
                'code' => 'KAP',
                'name' => 'KAPSUL',
                'created_by' => $userId,
            ],
            [
                'type' => 'Berat',
                'code' => 'SAT09',
                'name' => 'KG/GR',
                'created_by' => $userId,
            ],
            [
                'type' => 'Umum',
                'code' => 'SAT10',
                'name' => 'KOTAK',
                'created_by' => $userId,
            ],
            [
                'type' => 'Umum',
                'code' => 'SAT11',
                'name' => 'LEMBAR',
                'created_by' => $userId,
            ],
            [
                'type' => 'Volume',
                'code' => 'SAT12',
                'name' => 'LITER',
                'created_by' => $userId,
            ],
            [
                'type' => 'Panjang',
                'code' => 'SAT13',
                'name' => 'METER',
                'created_by' => $userId,
            ],
            [
                'type' => 'Obat',
                'code' => 'SAT14',
                'name' => 'NEBULE',
                'created_by' => $userId,
            ],
            [
                'type' => 'Berat',
                'code' => 'SAT15',
                'name' => 'ONS/GR',
                'created_by' => $userId,
            ],
            [
                'type' => 'Umum',
                'code' => 'SAT16',
                'name' => 'PAKET',
                'created_by' => $userId,
            ],
            [
                'type' => 'Umum',
                'code' => 'SAT17',
                'name' => 'PCS',
                'created_by' => $userId,
            ],
            [
                'type' => 'Umum', // Atau Obat, sesuaikan
                'code' => 'SAT18',
                'name' => 'PSG',
                'created_by' => $userId,
            ],
            [
                'type' => 'Obat',
                'code' => 'PUYER',
                'name' => 'PUYER',
                'created_by' => $userId,
            ],
            [
                'type' => 'Umum', // Atau Obat, sesuaikan
                'code' => 'SAT19',
                'name' => 'ROL',
                'created_by' => $userId,
            ],
            [
                'type' => 'Obat',
                'code' => 'SAT20',
                'name' => 'SACHET',
                'created_by' => $userId,
            ],
            [
                'type' => 'Umum',
                'code' => 'SAT21',
                'name' => 'SET',
                'created_by' => $userId,
            ],
            [
                'type' => 'Obat',
                'code' => 'SAT22',
                'name' => 'STRIP',
                'created_by' => $userId,
            ],
            [
                'type' => 'Obat',
                'code' => 'SAT23',
                'name' => 'SUPP',
                'created_by' => $userId,
            ],
            [
                'type' => 'Alat', // Atau Obat, sesuaikan
                'code' => 'SAT24',
                'name' => 'SYIRINGE',
                'created_by' => $userId,
            ],
            [
                'type' => 'Obat',
                'code' => 'SAT25',
                'name' => 'TAB',
                'created_by' => $userId,
            ],
            [
                'type' => 'Obat',
                'code' => 'SAT26',
                'name' => 'TABLET',
                'created_by' => $userId,
            ],
            [
                'type' => 'Obat',
                'code' => 'SAT27',
                'name' => 'TUBE',
                'created_by' => $userId,
            ],
            [
                'type' => 'Obat',
                'code' => 'SAT28',
                'name' => 'VIAL',
                'created_by' => $userId,
            ],
            [
                'type' => 'Berat',
                'code' => 'SAT29',
                'name' => 'KG',
                'created_by' => $userId,
            ],
            [
                'type' => 'Berat',
                'code' => 'SAT30',
                'name' => 'SAK',
                'created_by' => $userId,
            ],
        ];

        foreach ($units as $unitData) {
            Unit::create($unitData);
        }

        $this->command->info('Units seeded!');
    }
}
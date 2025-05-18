<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UnitSeeder extends Seeder
{
    public function run()
    {
        $user = User::first();
        $userId = $user ? $user->id : 1;

        $units = [
            [
                'type' => 'Obat',
                'code' => 'SAT01',
                'name' => 'AMPUL',
                'symbol' => 'amp',
                'created_by' => $userId,
            ],
            [
                'type' => 'Obat',
                'code' => 'SAT02',
                'name' => 'BOTOL',
                'symbol' => 'btl',
                'created_by' => $userId,
            ],
            [
                'type' => 'Umum',
                'code' => 'BOX',
                'name' => 'BOX',
                'symbol' => 'box',
                'created_by' => $userId,
            ],
            [
                'type' => 'Umum',
                'code' => 'SAT03',
                'name' => 'BUNGKUS',
                'symbol' => 'bks',
                'created_by' => $userId,
            ],
            [
                'type' => 'Volume',
                'code' => 'SAT04',
                'name' => 'CC',
                'symbol' => 'cc',
                'created_by' => $userId,
            ],
            [
                'type' => 'Panjang',
                'code' => 'SAT05',
                'name' => 'CM',
                'symbol' => 'cm',
                'created_by' => $userId,
            ],
            [
                'type' => 'Obat',
                'code' => 'SAT06',
                'name' => 'FLS',
                'symbol' => 'fls',
                'created_by' => $userId,
            ],
            [
                'type' => 'Volume',
                'code' => 'SAT07',
                'name' => 'GALON',
                'symbol' => 'gal',
                'created_by' => $userId,
            ],
            [
                'type' => 'Berat',
                'code' => 'SAT',
                'name' => 'GRAM',
                'symbol' => 'gr',
                'created_by' => $userId,
            ],
            [
                'type' => 'Obat',
                'code' => 'SAT08',
                'name' => 'INHALER',
                'symbol' => 'inh',
                'created_by' => $userId,
            ],
            [
                'type' => 'Obat',
                'code' => 'KAP',
                'name' => 'KAPSUL',
                'symbol' => 'kap',
                'created_by' => $userId,
            ],
            [
                'type' => 'Berat',
                'code' => 'SAT09',
                'name' => 'KG/GR',
                'symbol' => 'kg',
                'created_by' => $userId,
            ],
            [
                'type' => 'Umum',
                'code' => 'SAT10',
                'name' => 'KOTAK',
                'symbol' => 'ktk',
                'created_by' => $userId,
            ],
            [
                'type' => 'Umum',
                'code' => 'SAT11',
                'name' => 'LEMBAR',
                'symbol' => 'lbr',
                'created_by' => $userId,
            ],
            [
                'type' => 'Volume',
                'code' => 'SAT12',
                'name' => 'LITER',
                'symbol' => 'lt',
                'created_by' => $userId,
            ],
            [
                'type' => 'Panjang',
                'code' => 'SAT13',
                'name' => 'METER',
                'symbol' => 'm',
                'created_by' => $userId,
            ],
            [
                'type' => 'Obat',
                'code' => 'SAT14',
                'name' => 'NEBULE',
                'symbol' => 'neb',
                'created_by' => $userId,
            ],
            [
                'type' => 'Berat',
                'code' => 'SAT15',
                'name' => 'ONS/GR',
                'symbol' => 'ons',
                'created_by' => $userId,
            ],
            [
                'type' => 'Umum',
                'code' => 'SAT16',
                'name' => 'PAKET',
                'symbol' => 'pkt',
                'created_by' => $userId,
            ],
            [
                'type' => 'Umum',
                'code' => 'SAT17',
                'name' => 'PCS',
                'symbol' => 'pcs',
                'created_by' => $userId,
            ],
            [
                'type' => 'Umum',
                'code' => 'SAT18',
                'name' => 'PSG',
                'symbol' => 'psg',
                'created_by' => $userId,
            ],
            [
                'type' => 'Obat',
                'code' => 'PUYER',
                'name' => 'PUYER',
                'symbol' => 'pyr',
                'created_by' => $userId,
            ],
            [
                'type' => 'Umum',
                'code' => 'SAT19',
                'name' => 'ROL',
                'symbol' => 'rol',
                'created_by' => $userId,
            ],
            [
                'type' => 'Obat',
                'code' => 'SAT20',
                'name' => 'SACHET',
                'symbol' => 'sch',
                'created_by' => $userId,
            ],
            [
                'type' => 'Umum',
                'code' => 'SAT21',
                'name' => 'SET',
                'symbol' => 'set',
                'created_by' => $userId,
            ],
            [
                'type' => 'Obat',
                'code' => 'SAT22',
                'name' => 'STRIP',
                'symbol' => 'str',
                'created_by' => $userId,
            ],
            [
                'type' => 'Obat',
                'code' => 'SAT23',
                'name' => 'SUPP',
                'symbol' => 'sup',
                'created_by' => $userId,
            ],
            [
                'type' => 'Alat',
                'code' => 'SAT24',
                'name' => 'SYIRINGE',
                'symbol' => 'syr',
                'created_by' => $userId,
            ],
            [
                'type' => 'Obat',
                'code' => 'SAT25',
                'name' => 'TAB',
                'symbol' => 'tab',
                'created_by' => $userId,
            ],
            [
                'type' => 'Obat',
                'code' => 'SAT26',
                'name' => 'TABLET',
                'symbol' => 'tbl',
                'created_by' => $userId,
            ],
            [
                'type' => 'Obat',
                'code' => 'SAT27',
                'name' => 'TUBE',
                'symbol' => 'tbe',
                'created_by' => $userId,
            ],
            [
                'type' => 'Obat',
                'code' => 'SAT28',
                'name' => 'VIAL',
                'symbol' => 'vial',
                'created_by' => $userId,
            ],
            [
                'type' => 'Berat',
                'code' => 'SAT29',
                'name' => 'KG',
                'symbol' => 'kg',
                'created_by' => $userId,
            ],
            [
                'type' => 'Berat',
                'code' => 'SAT30',
                'name' => 'SAK',
                'symbol' => 'sak',
                'created_by' => $userId,
            ],
            [
                'type' => 'Volume',
                'code' => 'SAT31',
                'name' => 'Jerigen 5 Liter',
                'symbol' => 'jrg5',
                'created_by' => $userId,
            ],
            [
                'type' => 'Volume',
                'code' => 'SAT32',
                'name' => 'Jerigen 20 Liter',
                'symbol' => 'jrg20',
                'created_by' => $userId,
            ],
            [
                'type' => 'Volume',
                'code' => 'SAT33',
                'name' => 'Jerigen 25 Liter',
                'symbol' => 'jrg25',
                'created_by' => $userId,
            ],
        ];

        foreach ($units as $unitData) {
            Unit::create($unitData);
        }

        $this->command->info('Units seeded!');
    }
}

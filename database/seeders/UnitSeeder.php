<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class UnitSeeder extends Seeder
{
    public function run()
    {
        // Make sure FK checks don't interfere when users table is empty
        Schema::disableForeignKeyConstraints();

        $user = User::first();
        $userId = $user?->id ?? Str::uuid()->toString();

        // Get company_id from the command
        $companyId = config('seeder.current_company_id');
        if (!$companyId) {
            // Fallback: seeding global/default units (no company_id)
            $this->command->warn('UnitSeeder: `company_id` not found in config, seeding as global/default units.');
        }

        $units = [
            ['type' => 'Obat', 'code' => 'SAT01', 'name' => 'AMPUL', 'symbol' => 'amp'],
            ['type' => 'Obat', 'code' => 'SAT02', 'name' => 'BOTOL', 'symbol' => 'btl'],
            ['type' => 'Umum', 'code' => 'BOX', 'name' => 'BOX', 'symbol' => 'box'],
            ['type' => 'Umum', 'code' => 'SAT03', 'name' => 'BUNGKUS', 'symbol' => 'bks'],
            ['type' => 'Volume', 'code' => 'SAT04', 'name' => 'CC', 'symbol' => 'cc'],
            ['type' => 'Panjang', 'code' => 'SAT05', 'name' => 'CM', 'symbol' => 'cm'],
            ['type' => 'Obat', 'code' => 'SAT06', 'name' => 'FLS', 'symbol' => 'fls'],
            ['type' => 'Volume', 'code' => 'SAT07', 'name' => 'GALON', 'symbol' => 'gal'],
            ['type' => 'Berat', 'code' => 'SAT', 'name' => 'GRAM', 'symbol' => 'gr'],
            ['type' => 'Obat', 'code' => 'SAT08', 'name' => 'INHALER', 'symbol' => 'inh'],
            ['type' => 'Obat', 'code' => 'KAP', 'name' => 'KAPSUL', 'symbol' => 'kap'],
            ['type' => 'Berat', 'code' => 'SAT09', 'name' => 'KG/GR', 'symbol' => 'kg'],
            ['type' => 'Umum', 'code' => 'SAT10', 'name' => 'KOTAK', 'symbol' => 'ktk'],
            ['type' => 'Umum', 'code' => 'SAT11', 'name' => 'LEMBAR', 'symbol' => 'lbr'],
            ['type' => 'Volume', 'code' => 'SAT12', 'name' => 'LITER', 'symbol' => 'lt'],
            ['type' => 'Panjang', 'code' => 'SAT13', 'name' => 'METER', 'symbol' => 'm'],
            ['type' => 'Obat', 'code' => 'SAT14', 'name' => 'NEBULE', 'symbol' => 'neb'],
            ['type' => 'Berat', 'code' => 'SAT15', 'name' => 'ONS/GR', 'symbol' => 'ons'],
            ['type' => 'Umum', 'code' => 'SAT16', 'name' => 'PAKET', 'symbol' => 'pkt'],
            ['type' => 'Umum', 'code' => 'SAT17', 'name' => 'PCS', 'symbol' => 'pcs'],
            ['type' => 'Umum', 'code' => 'SAT18', 'name' => 'PSG', 'symbol' => 'psg'],
            ['type' => 'Obat', 'code' => 'PUYER', 'name' => 'PUYER', 'symbol' => 'pyr'],
            ['type' => 'Umum', 'code' => 'SAT19', 'name' => 'ROL', 'symbol' => 'rol'],
            ['type' => 'Obat', 'code' => 'SAT20', 'name' => 'SACHET', 'symbol' => 'sch'],
            ['type' => 'Umum', 'code' => 'SAT21', 'name' => 'SET', 'symbol' => 'set'],
            ['type' => 'Obat', 'code' => 'SAT22', 'name' => 'STRIP', 'symbol' => 'str'],
            ['type' => 'Obat', 'code' => 'SAT23', 'name' => 'SUPP', 'symbol' => 'sup'],
            ['type' => 'Alat', 'code' => 'SAT24', 'name' => 'SYIRINGE', 'symbol' => 'syr'],
            ['type' => 'Obat', 'code' => 'SAT25', 'name' => 'TAB', 'symbol' => 'tab'],
            ['type' => 'Obat', 'code' => 'SAT26', 'name' => 'TABLET', 'symbol' => 'tbl'],
            ['type' => 'Obat', 'code' => 'SAT27', 'name' => 'TUBE', 'symbol' => 'tbe'],
            ['type' => 'Obat', 'code' => 'SAT28', 'name' => 'VIAL', 'symbol' => 'vial'],
            ['type' => 'Berat', 'code' => 'SAT29', 'name' => 'KG', 'symbol' => 'kg'],
            ['type' => 'Berat', 'code' => 'SAT30', 'name' => 'SAK', 'symbol' => 'sak'],
            ['type' => 'Volume', 'code' => 'SAT31', 'name' => 'Jerigen 5 Liter', 'symbol' => 'jrg5'],
            ['type' => 'Volume', 'code' => 'SAT32', 'name' => 'Jerigen 20 Liter', 'symbol' => 'jrg20'],
            ['type' => 'Volume', 'code' => 'SAT33', 'name' => 'Jerigen 25 Liter', 'symbol' => 'jrg25'],
        ];

        $created = 0;
        foreach ($units as $unitData) {
            $searchCriteria = [
                'code' => $unitData['code'],
            ];
            if ($companyId) {
                $searchCriteria['company_id'] = $companyId;
            }

            $createData = array_merge($unitData, [
                'created_by' => $userId,
                'company_id' => $companyId ?? null
            ]);

            $unit = Unit::firstOrCreate($searchCriteria, $createData);

            if ($unit->wasRecentlyCreated) {
                $created++;
            }
        }

        Schema::enableForeignKeyConstraints();

        $this->command->info("Units seeder completed for company {$companyId}. New units: {$created} (duplicates skipped)");
    }
}

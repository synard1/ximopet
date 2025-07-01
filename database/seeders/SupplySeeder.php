<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supply;
use App\Models\SupplyCategory;
use App\Models\User;
use Illuminate\Support\Str;

class SupplySeeder extends Seeder
{
    public function run()
    {
        $user = User::first();
        $userId = $user?->id ?? Str::uuid()->toString();

        $companyId = config('seeder.current_company_id');
        if (!$companyId) {
            $this->command->warn('SupplySeeder: `company_id` not found, skipping.');
            return;
        }

        /*****************************
         * Refactor: Seed OVK supplies
         * -----------------------------------
         * We follow the richer dataset style used in OVKSeeder but keep it
         * lightweight.  Supplies will be created for the "OVK" category with
         * default unit mappings.  Idempotent via firstOrCreate.
         *****************************/

        // Ensure OVK category exists for this company
        $category = SupplyCategory::firstOrCreate(
            ['name' => 'OVK', 'company_id' => $companyId],
            ['created_by' => $userId, 'company_id' => $companyId]
        );

        // Helper to generate unique code prefix
        $generateCode = fn(string $prefix) => $prefix . strtoupper(Str::random(6));

        // Complete OVK item dataset (name => unit)
        $ovkItems = [
            ['name' => 'Biocid', 'unit' => 'LITER'],
            ['name' => 'Biodes', 'unit' => 'LITER'],
            ['name' => 'Cevac New L 1000', 'unit' => 'VIAL'],
            ['name' => 'Cevamune', 'unit' => 'TABLET'],
            ['name' => 'Chickofit', 'unit' => 'LITER'],
            ['name' => 'CID 2000', 'unit' => 'KG'],
            ['name' => 'Coxymas', 'unit' => 'LITER'],
            ['name' => 'Cupri Sulfate', 'unit' => 'KG'],
            ['name' => 'Elektrovit', 'unit' => 'KG'],
            ['name' => 'Enroforte', 'unit' => 'LITER'],
            ['name' => 'Formalin', 'unit' => 'LITER'],
            ['name' => 'Hiptovit', 'unit' => 'KG'],
            ['name' => 'Kaporit Tepung', 'unit' => 'KG'],
            ['name' => 'Kumavit @250 Gram', 'unit' => 'BUNGKUS'],
            ['name' => 'Nopstress', 'unit' => 'KG'],
            ['name' => 'Rhodivit', 'unit' => 'KG'],
            ['name' => 'Selco', 'unit' => 'LITER'],
            ['name' => 'Starbio', 'unit' => 'LITER'],
            ['name' => 'TH4', 'unit' => 'LITER'],
            ['name' => 'Toltracox', 'unit' => 'LITER'],
            ['name' => 'Vigosine', 'unit' => 'LITER'],
            ['name' => 'Vitamin C', 'unit' => 'KG'],
            ['name' => 'Virukill', 'unit' => 'LITER'],
            ['name' => 'Zuramox', 'unit' => 'KG'],
            ['name' => 'Cyprotylogrin', 'unit' => 'KG'],
            ['name' => 'Acid Pack 4 Way', 'unit' => 'KG'],
        ];

        foreach ($ovkItems as $item) {
            $baseUnit = \App\Models\Unit::where('name', $item['unit'])->where('company_id', $companyId)->first();
            if (!$baseUnit) {
                // Skip if unit missing to keep seeder safe
                continue;
            }

            $searchCriteria = [
                'name' => $item['name'],
                'company_id' => $companyId
            ];

            $supplyData = [
                'supply_category_id' => $category->id,
                'code' => $generateCode('OVK-'),
                'data' => [
                    'unit_id' => $baseUnit->id,
                    'unit_details' => ['id' => $baseUnit->id, 'name' => $baseUnit->name],
                ],
                'created_by' => $userId,
                'company_id' => $companyId
            ];

            Supply::firstOrCreate($searchCriteria, $supplyData);
        }
    }
}

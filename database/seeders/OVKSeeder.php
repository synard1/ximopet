<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SupplyCategory; // Assuming this is in App\Models
use App\Models\Supply;        // Assuming this is in App\Models
use App\Models\User;         // Assuming this is in App\Models
use App\Models\Unit;
use App\Models\UnitConversion;
use Illuminate\Support\Str;

class OVKSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Get or Create the OVK Supply Category
        $ovkCategory = SupplyCategory::firstOrCreate(
            ['name' => 'OVK'],
            ['created_by' => User::where('email', 'supervisor@demo.com')->firstOrFail()->id]
        );

        $supervisorId = User::where('email', 'supervisor@demo.com')->firstOrFail()->id;

        // 2. OVK Data (Simplified)
        $ovkData = collect([
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
        ])->map(function ($item) {
            $codePrefix = 'OVK-';
            $randomCode = strtoupper(Str::random(8));
            $item['code'] = $codePrefix . $randomCode;
            $item['unit_conversion'] = $item['unit'];
            $item['conversion'] = 1;
            return $item;
        })->toArray();

        // 3. Create Supplies
        foreach ($ovkData as $data) {
            $baseUnit = Unit::where('name', $data['unit'])->first();
            if (!$baseUnit) {
                $this->command->error("Unit {$data['unit']} not found. Skipping {$data['name']}");
                continue;
            }

            // Prepare the conversion units array - start with the base unit
            $conversionUnits = [
                [
                    'unit_id' => $baseUnit->id,
                    'unit_name' => $baseUnit->name,
                    'value' => 1,
                    'is_default_purchase' => true,
                    'is_default_mutation' => true,
                    'is_default_sale' => true,
                    'is_smallest' => true,
                ]
            ];

            // Create the supply with all conversion units
            $supply = Supply::create([
                'supply_category_id' => $ovkCategory->id,
                'code' => $data['code'],
                'name' => $data['name'],
                'data' => [
                    'unit_id' => $baseUnit->id,
                    'unit_details' => [
                        'id' => $baseUnit->id,
                        'name' => $baseUnit->name,
                        'description' => $baseUnit->description,
                    ],
                    'conversion_units' => $conversionUnits,
                ],
                'created_by' => $supervisorId,
            ]);

            // Create the base unit conversion
            UnitConversion::updateOrCreate(
                [
                    'type' => 'Supply',
                    'item_id' => $supply->id,
                    'unit_id' => $baseUnit->id,
                    'conversion_unit_id' => $baseUnit->id,
                ],
                [
                    'conversion_value' => 1,
                    'default_purchase' => true,
                    'default_mutation' => true,
                    'default_sale' => true,
                    'smallest' => true,
                    'created_by' => $supervisorId,
                ]
            );

            $this->command->info('Supply ' . $supply->name . ' created with ' . count($conversionUnits) . ' units.');
        }

        $this->command->info('OVK seeding completed successfully.');
    }
}

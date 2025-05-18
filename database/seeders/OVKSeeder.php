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

        // Define additional container units for LITER items
        $containerUnits = [
            [
                'type' => 'Volume',
                'code' => 'SAT31',
                'name' => 'Jerigen 5 Liter',
                'conversion' => 5,
                'created_by' => $supervisorId,
            ],
            [
                'type' => 'Volume',
                'code' => 'SAT32',
                'name' => 'Jerigen 20 Liter',
                'conversion' => 20,
                'created_by' => $supervisorId,
            ],
            [
                'type' => 'Volume',
                'code' => 'SAT33',
                'name' => 'Jerigen 25 Liter',
                'conversion' => 25,
                'created_by' => $supervisorId,
            ],
        ];

        // Ensure container units exist in the Units table
        foreach ($containerUnits as $containerUnit) {
            Unit::firstOrCreate(
                ['code' => $containerUnit['code']],
                [
                    'type' => $containerUnit['type'],
                    'name' => $containerUnit['name'],
                    'created_by' => $containerUnit['created_by'],
                ]
            );
        }

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

            // If this is a LITER unit, add the container units
            if ($data['unit'] === 'LITER') {
                foreach ($containerUnits as $containerUnit) {
                    $unit = Unit::where('code', $containerUnit['code'])->first();
                    if ($unit) {
                        $conversionUnits[] = [
                            'unit_id' => $unit->id,
                            'unit_name' => $unit->name,
                            'value' => $containerUnit['conversion'],
                            'is_default_purchase' => false,
                            'is_default_mutation' => false,
                            'is_default_sale' => false,
                            'is_smallest' => false,
                        ];
                    }
                }
            }

            // Create the supply with all conversion units
            $supply = Supply::create([
                'supply_category_id' => $ovkCategory->id,
                'code' => $data['code'],
                'name' => $data['name'],
                'payload' => [
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

            // If this is a LITER unit, add container unit conversions
            if ($data['unit'] === 'LITER') {
                foreach ($containerUnits as $containerUnit) {
                    $unit = Unit::where('code', $containerUnit['code'])->first();
                    if ($unit) {
                        UnitConversion::updateOrCreate(
                            [
                                'type' => 'Supply',
                                'item_id' => $supply->id,
                                'unit_id' => $baseUnit->id,
                                'conversion_unit_id' => $unit->id,
                            ],
                            [
                                'conversion_value' => $containerUnit['conversion'],
                                'default_purchase' => false,
                                'default_mutation' => false,
                                'default_sale' => false,
                                'smallest' => false,
                                'created_by' => $supervisorId,
                            ]
                        );

                        $this->command->info("Added {$containerUnit['name']} conversion for {$supply->name}");
                    }
                }
            }

            $this->command->info('Supply ' . $supply->name . ' created with ' . count($conversionUnits) . ' units.');
        }

        $this->command->info('OVK seeding completed successfully.');
    }

    // public function run(): void
    // {
    //     // 1. Get or Create the OVK Supply Category
    //     $ovkCategory = SupplyCategory::firstOrCreate(
    //         ['name' => 'OVK'],
    //         ['created_by' => User::where('email', 'supervisor@demo.com')->firstOrFail()->id]
    //     );

    //     // 2. OVK Data (Simplified)
    //     $ovkData = collect([
    //         ['name' => 'Biocid', 'unit' => 'LITER'],
    //         ['name' => 'Biodes', 'unit' => 'LITER'],
    //         ['name' => 'Cevac New L 1000', 'unit' => 'VIAL'],
    //         ['name' => 'Cevamune', 'unit' => 'TABLET'],
    //         ['name' => 'Chickofit', 'unit' => 'LITER'],
    //         ['name' => 'CID 2000', 'unit' => 'KG'],
    //         ['name' => 'Coxymas', 'unit' => 'LITER'],
    //         ['name' => 'Cupri Sulfate', 'unit' => 'KG'],
    //         ['name' => 'Elektrovit', 'unit' => 'KG'],
    //         ['name' => 'Enroforte', 'unit' => 'LITER'],
    //         ['name' => 'Formalin', 'unit' => 'LITER'],
    //         ['name' => 'Hiptovit', 'unit' => 'KG'],
    //         ['name' => 'Kaporit Tepung', 'unit' => 'KG'],
    //         ['name' => 'Kumavit @250 Gram', 'unit' => 'BUNGKUS'],
    //         ['name' => 'Nopstress', 'unit' => 'KG'],
    //         ['name' => 'Rhodivit', 'unit' => 'KG'],
    //         ['name' => 'Selco', 'unit' => 'LITER'],
    //         ['name' => 'Starbio', 'unit' => 'LITER'],
    //         ['name' => 'TH4', 'unit' => 'LITER'],
    //         ['name' => 'Toltracox', 'unit' => 'LITER'],
    //         ['name' => 'Vigosine', 'unit' => 'LITER'],
    //         ['name' => 'Vitamin C', 'unit' => 'KG'],
    //         ['name' => 'Virukill', 'unit' => 'LITER'],
    //         ['name' => 'Zuramox', 'unit' => 'KG'],
    //         ['name' => 'Cyprotylogrin', 'unit' => 'KG'],
    //         ['name' => 'Acid Pack 4 Way', 'unit' => 'KG'],
    //     ])->map(function ($item) {
    //         $codePrefix = 'OVK-';
    //         $randomCode = strtoupper(Str::random(8));
    //         $item['code'] = $codePrefix . $randomCode;
    //         $item['unit_conversion'] = $item['unit'];
    //         $item['conversion'] = 1;
    //         return $item;
    //     })->toArray();

    //     // 3. Create Supplies
    //     $supervisorId = User::where('email', 'supervisor@demo.com')->firstOrFail()->id;

    //     foreach ($ovkData as $data) {
    //         $unit = Unit::where('name',$data['unit'])->first();
    //         // dd($data['unit']);
    //         $supply = Supply::create([
    //             'supply_category_id' => $ovkCategory->id,
    //             'code' => $data['code'],
    //             'name' => $data['name'],
    //             'payload' => [
    //                 'unit_id' => $unit->id,
    //                 'unit_details' => [
    //                         'id' => $unit->id, // Sesuaikan jika ada ID lain yang relevan
    //                         'name' => $unit->name, // Atau $unitKG->name jika yakin ada
    //                         'description' => $unit->description, // Deskripsi sesuai kebutuhan
    //                 ],
    //                 'conversion_units' => [
    //                     [
    //                         'unit_id' => $unit->id,
    //                         'unit_name' => $unit->name,
    //                         'value' => 1,
    //                         'is_default_purchase' => true,
    //                         'is_default_mutation' => true,
    //                         'is_default_sale' => true,
    //                         'is_smallest' => true,
    //                     ],   
    //                 ],
    //             ],
    //             'created_by' => $supervisorId,
    //         ]);

    //         UnitConversion::updateOrCreate(
    //             [
    //                 'type' => 'Supply',
    //                 'item_id' => $supply->id,
    //                 'unit_id' => $unit->id,
    //                 'conversion_unit_id' => $unit->id,
    //             ],
    //             [
    //                 'conversion_value' => 1,
    //                 'default_purchase' => true,
    //                 'default_mutation' => true,
    //                 'default_sale' => true,
    //                 'smallest' => true,
    //                 'created_by' => $supervisorId,
    //             ]
    //         );
    //         $this->command->info('Supply '.$supply->name.' Created');

    //     }
    // }
}

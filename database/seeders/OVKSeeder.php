<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SupplyCategory; // Assuming this is in App\Models
use App\Models\Supply;        // Assuming this is in App\Models
use App\Models\User;         // Assuming this is in App\Models
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

        // 2. OVK Data (Simplified)
        $ovkData = collect([
            ['name' => 'Biocid', 'unit' => 'Liter'],
            ['name' => 'Biodes', 'unit' => 'Liter'],
            ['name' => 'Cevac New L 1000', 'unit' => 'Vial'],
            ['name' => 'Cevamune', 'unit' => 'Tablet'],
            ['name' => 'Chickofit', 'unit' => 'Liter'],
            ['name' => 'CID 2000', 'unit' => 'Kg'],
            ['name' => 'Coxymas', 'unit' => 'Liter'],
            ['name' => 'Cupri Sulfate', 'unit' => 'Kg'],
            ['name' => 'Elektrovit', 'unit' => 'Kg'],
            ['name' => 'Enroforte', 'unit' => 'Liter'],
            ['name' => 'Formalin', 'unit' => 'Liter'],
            ['name' => 'Hiptovit', 'unit' => 'Kg'],
            ['name' => 'Kaporit Tepung', 'unit' => 'Kg'],
            ['name' => 'Kumavit @250 Gram', 'unit' => 'Bungkus'],
            ['name' => 'Nopstress', 'unit' => 'Kg'],
            ['name' => 'Rhodivit', 'unit' => 'Kg'],
            ['name' => 'Selco', 'unit' => 'Liter'],
            ['name' => 'Starbio', 'unit' => 'Liter'],
            ['name' => 'TH4', 'unit' => 'Liter'],
            ['name' => 'Toltracox', 'unit' => 'Liter'],
            ['name' => 'Vigosine', 'unit' => 'Liter'],
            ['name' => 'Vitamin C', 'unit' => 'Kg'],
            ['name' => 'Virukill', 'unit' => 'Liter'],
            ['name' => 'Zuramox', 'unit' => 'Kg'],
            ['name' => 'Cyprotylogrin', 'unit' => 'Kg'],
            ['name' => 'Acid Pack 4 Way', 'unit' => 'Kg'],
        ])->map(function ($item) {
            $codePrefix = 'OVK-';
            $randomCode = strtoupper(Str::random(8));
            $item['code'] = $codePrefix . $randomCode;
            $item['unit_conversion'] = $item['unit'];
            $item['conversion'] = 1;
            return $item;
        })->toArray();

        // 3. Create Supplies
        $supervisorId = User::where('email', 'supervisor@demo.com')->firstOrFail()->id;

        foreach ($ovkData as $data) {
            Supply::create([
                'supply_category_id' => $ovkCategory->id,
                'code' => $data['code'],
                'name' => $data['name'],
                'unit' => $data['unit'],
                'unit_conversion' => $data['unit_conversion'],
                'conversion' => $data['conversion'],
                'created_by' => $supervisorId,
            ]);
        }
    }
}

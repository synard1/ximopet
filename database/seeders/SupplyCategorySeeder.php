<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SupplyCategory;

class SupplyCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Obat',
            'Vitamin',
            'Kimia',
            'Disinfektan',
            'Nutrisi Tambahan',
            'OVK',  // Obat, Vitamin, dan Kimia
            'Lain - Lain',
        ];

        foreach ($categories as $name) {
            SupplyCategory::firstOrCreate(['name' => $name]);
        }
    }
}

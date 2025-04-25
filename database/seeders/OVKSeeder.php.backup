<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Item;
use App\Models\ItemCategory;

class OVKSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ovkCategory = ItemCategory::where('name', 'OVK')->first();

        $ovkData = [
            ['category_id' => $ovkCategory->id , 'name' => 'Biocid', 'satuan_besar' => 'Liter', 'satuan_kecil' => 'Liter', 'konversi' => 1],
            ['category_id' => $ovkCategory->id , 'name' => 'Biodes',  'satuan_besar' => 'Liter', 'satuan_kecil' => 'Liter', 'konversi' => 1],
            ['category_id' => $ovkCategory->id , 'name' => 'Cevac New L 1000',  'satuan_besar' => 'Vial', 'satuan_kecil' => 'Vial', 'konversi' => 1],
            ['category_id' => $ovkCategory->id , 'name' => 'Cevamune',  'satuan_besar' => 'Tablet', 'satuan_kecil' => 'Tablet', 'konversi' => 1],
            ['category_id' => $ovkCategory->id , 'name' => 'Chickofit',  'satuan_besar' => 'Liter', 'satuan_kecil' => 'Liter', 'konversi' => 1],
            ['category_id' => $ovkCategory->id , 'name' => 'CID 2000',  'satuan_besar' => 'Kg', 'satuan_kecil' => 'Kg', 'konversi' => 1],
            ['category_id' => $ovkCategory->id , 'name' => 'Coxymas',  'satuan_besar' => 'Liter', 'satuan_kecil' => 'Liter', 'konversi' => 1],
            ['category_id' => $ovkCategory->id , 'name' => 'Cupri Sulfate',  'satuan_besar' => 'Kg', 'satuan_kecil' => 'Kg', 'konversi' => 1],
            ['category_id' => $ovkCategory->id , 'name' => 'Elektrovit',  'satuan_besar' => 'Kg', 'satuan_kecil' => 'Kg', 'konversi' => 1],
            ['category_id' => $ovkCategory->id , 'name' => 'Enroforte',  'satuan_besar' => 'Liter', 'satuan_kecil' => 'Liter', 'konversi' => 1],
            ['category_id' => $ovkCategory->id , 'name' => 'Formalin',  'satuan_besar' => 'Liter', 'satuan_kecil' => 'Liter', 'konversi' => 1],
            ['category_id' => $ovkCategory->id , 'name' => 'Hiptovit',  'satuan_besar' => 'Kg', 'satuan_kecil' => 'Kg', 'konversi' => 1],
            ['category_id' => $ovkCategory->id , 'name' => 'Kaporit Tepung',  'satuan_besar' => 'Kg', 'satuan_kecil' => 'Kg', 'konversi' => 1],
            ['category_id' => $ovkCategory->id , 'name' => 'Kumavit @250 Gram',  'satuan_besar' => 'Bungkus', 'satuan_kecil' => 'Bungkus', 'konversi' => 1],
            ['category_id' => $ovkCategory->id , 'name' => 'Nopstress',  'satuan_besar' => 'Kg', 'satuan_kecil' => 'Kg', 'konversi' => 1],
            ['category_id' => $ovkCategory->id , 'name' => 'Rhodivit',  'satuan_besar' => 'Kg', 'satuan_kecil' => 'Kg', 'konversi' => 1],
            ['category_id' => $ovkCategory->id , 'name' => 'Selco',  'satuan_besar' => 'Liter', 'satuan_kecil' => 'Liter', 'konversi' => 1],
            ['category_id' => $ovkCategory->id , 'name' => 'Starbio',  'satuan_besar' => 'Liter', 'satuan_kecil' => 'Liter', 'konversi' => 1],
            ['category_id' => $ovkCategory->id , 'name' => 'TH4',  'satuan_besar' => 'Liter', 'satuan_kecil' => 'Liter', 'konversi' => 1],
            ['category_id' => $ovkCategory->id , 'name' => 'Toltracox',  'satuan_besar' => 'Liter', 'satuan_kecil' => 'Liter', 'konversi' => 1],
            ['category_id' => $ovkCategory->id , 'name' => 'Vigosine',  'satuan_besar' => 'Liter', 'satuan_kecil' => 'Liter', 'konversi' => 1],
            ['category_id' => $ovkCategory->id , 'name' => 'Vitamin C',  'satuan_besar' => 'Kg', 'satuan_kecil' => 'Kg', 'konversi' => 1],
            ['category_id' => $ovkCategory->id , 'name' => 'Virukill',  'satuan_besar' => 'Liter', 'satuan_kecil' => 'Liter', 'konversi' => 1],
            ['category_id' => $ovkCategory->id , 'name' => 'Zuramox',  'satuan_besar' => 'Kg', 'satuan_kecil' => 'Kg', 'konversi' => 1],
            ['category_id' => $ovkCategory->id , 'name' => 'Cyprotylogrin',  'satuan_besar' => 'Kg', 'satuan_kecil' => 'Kg', 'konversi' => 1],
            ['category_id' => $ovkCategory->id , 'name' => 'Acid Pack 4 Way',  'satuan_besar' => 'Kg', 'satuan_kecil' => 'Kg', 'konversi' => 1],
        ];

        foreach ($ovkData as $data) {
            $supervisor = User::where('email', 'supervisor@demo.com')->first();
            // $category = ItemCategory::create(array_merge($data, ['status' => 'Aktif', 'created_by' => $supervisor->id]));

            // dd($ovkCategory->id);

            Item::create([
                'category_id' => $data['category_id'],
                'kode' => 'OVK-' . str_pad(Item::count() + 1, 3, '0', STR_PAD_LEFT),
                'name' => $data['name'],
                'satuan_besar' => $data['satuan_besar'],
                'satuan_kecil' => $data['satuan_kecil'],
                'konversi' => $data['konversi'],
                'status' => 'Aktif',
                'is_feed' => false,
                'created_by' => $supervisor->id,
            ]);
        }
    }
}

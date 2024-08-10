<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Generator;
use App\Models\User;
use App\Models\Rekanan;
use App\Models\Farm;
use App\Models\Kandang;
use App\Models\Stok;

class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(Generator $faker)
    {
        $demoRekanan = Rekanan::create([
            'jenis'         => 'Supplier',
            'kode'          => 'S001',
            'nama'          => $faker->company,
            'alamat'        => $faker->address,
            'telp'          => $faker->phoneNumber,
            'pic'           => $faker->name,
            'telp_pic'      => $faker->phoneNumber,
            'email'         => $faker->email,
            'status'        => 'Aktif',
        ]);

        $demoFarm = Farm::create([
            'kode'          => 'F001',
            'nama'          => $faker->company.'-Farm',
            'alamat'        => $faker->address,
            'telp'          => $faker->phoneNumber,
            'pic'           => $faker->name,
            'telp_pic'      => $faker->phoneNumber,
            'jumlah'        => 0,
            'kapasitas'     => '100000',
            'status'        => 'Aktif',
        ]);

        $demoKandang = Farm::create([
            'kode'          => 'K001',
            'farm_id'       => $demoFarm->id,
            'nama'          => 'Kandang-FK001',
            'jumlah'        => 0,
            'kapasitas'     => '100000',
            'status'        => 'Aktif',
        ]);
    }
}

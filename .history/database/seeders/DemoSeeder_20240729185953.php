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

        $jenisStok = ['DOC', 'Pakan', 'Obat', 'Vaksin', 'Lainnya'];
        $kodeStok = ['DOC', 'PK', 'OB', 'VK', 'LL'];

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
            'kapasitas'     => '1000000',
            'status'        => 'Aktif',
        ]);

        $demoKandang = Kandang::create([
            'kode'          => 'K001',
            'farm_id'       => $demoFarm->id,
            'nama'          => 'Kandang-FK001',
            'jumlah'        => 0,
            'kapasitas'     => '100000',
            'status'        => 'Aktif',
        ]);

        for ($i=0; $i < count($jenisStok); $i++) { 
            $demoStok = Stok::create([
                'kode'          => $kodeStok[$i].'001',
                'jenis'        => $jenisStok[$i],
                'farm_id'       => $demoFarm->id,
                'nama'          => 'Kandang-FK001',
                'jumlah'        => 0,
                'kapasitas'     => '100000',
                'status'        => 'Aktif',
            ]);
        }

        foreach($jenisStok as $jenis){

            $demoStok = Stok::create([
                'kode'          => 'K001',
                'farm_id'       => $demoFarm->id,
                'nama'          => 'Kandang-FK001',
                'jumlah'        => 0,
                'kapasitas'     => '100000',
                'status'        => 'Aktif',
            ]);

        }
    }
}

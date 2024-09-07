<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Generator;
use App\Models\User;
use App\Models\Rekanan;
use App\Models\Farm;
use App\Models\FarmOperator;
use App\Models\Kandang;
use App\Models\Stok;
use App\Models\Transaksi;
use App\Models\TransaksiDetail;
use Spatie\Permission\Models\Role;


class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(Generator $faker)
    {

        $jenisStok = ['DOC', 'Pakan', 'Obat', 'Vaksin', 'Lainnya'];
        $kodeStok = ['DOC', 'PK', 'OB', 'VK', 'LL'];
        $satuanBesarStok = ['Ekor', 'Kg', 'Butir', 'Impul', 'LL'];
        $satuanKecilStok = ['Ekor', 'Gram', 'Butir', 'Impul', 'LL'];
        $operator = User::where('email','operator@demo.com')->first();
        $supervisor = User::where('email','supervisor@demo.com')->first();

        for ($i=0; $i < count($jenisStok); $i++) { 
            $demoStok = Stok::create([
                'kode'          => $kodeStok[$i].'001',
                'jenis'         => $jenisStok[$i],
                'nama'          => 'Nama Stok'.$jenisStok[$i],
                'satuan_besar'  => $satuanBesarStok[$i],
                'satuan_kecil'  => $satuanKecilStok[$i],
                'konversi'      => '1',
                'status'        => 'Aktif',
            ]);
        }

        for ($i=0; $i < 5; $i++) { 
            $demoRekanan = Rekanan::create([
                'jenis'         => 'Supplier',
                'kode'          => 'S00'.$i+1,
                'nama'          => $faker->company,
                'alamat'        => $faker->address,
                'telp'          => $faker->phoneNumber,
                'pic'           => $faker->name,
                'telp_pic'      => $faker->phoneNumber,
                'email'         => $faker->email,
                'status'        => 'Aktif',
            ]);

            $demoRekanan2 = Rekanan::create([
                'jenis'         => 'Customer',
                'kode'          => 'C00'.$i+1,
                'nama'          => $faker->company,
                'alamat'        => $faker->address,
                'telp'          => $faker->phoneNumber,
                'pic'           => $faker->name,
                'telp_pic'      => $faker->phoneNumber,
                'email'         => $faker->email,
                'status'        => 'Aktif',
            ]);

            $demoFarm = Farm::create([
                'kode'          => 'F00'.$i+1,
                'nama'          => $faker->company.'-Farm',
                'alamat'        => $faker->address,
                'telp'          => $faker->phoneNumber,
                'pic'           => $faker->name,
                'telp_pic'      => $faker->phoneNumber,
                'jumlah'        => 0,
                'kapasitas'     => '1000000',
                'status'        => 'Aktif',
            ]);

            $demoFarmOperator = FarmOperator::create([
                'farm_id'           => $demoFarm->id,
                'nama_farm'         => $demoFarm->nama,
                'nama_operator'     => $operator->name,
                'user_id'           => $operator->id,
                'status'            => 'Aktif',
            ]);
    
            $demoKandang = Kandang::create([
                'kode'          => 'K00'.$i+1,
                'farm_id'       => $demoFarm->id,
                'nama'          => 'Kandang-FK00'.$i+1,
                'jumlah'        => 0,
                'kapasitas'     => '100000',
                'status'        => 'Aktif',
                'user_id'       => 1,
            ]);

             // Data dummy untuk Transaksi Pembelian DOC
            $supplier = Rekanan::where('jenis', 'Supplier')->inRandomOrder()->first();
            $farm = Farm::inRandomOrder()->first();
            $kandang = Kandang::where('farm_id', $farm->id)->inRandomOrder()->first();
            $stokDoc = Stok::where('jenis', 'DOC')->inRandomOrder()->first();
            $stok = Stok::where('jenis', 'Pakan')->inRandomOrder()->first();
            // $stok = Stok::whereIn('jenis', ['Pakan', 'Obat', 'Vaksin', 'Lainnya'])->inRandomOrder()->first();
            $qty = $faker->numberBetween(10000, 20000);
            $harga = $faker->numberBetween(10000, 20000);

            $itemsToStore[] = [
                'qty' => $qty,
                'terpakai' => 0,
                'harga' => $harga,
                'total' => $qty * $harga,
                'jenis' => $stokDoc->jenis,
                'item_id' => $stokDoc->id,
                'item_nama' => $stokDoc->nama,
            ];

            $stokToStore[] = [
                'qty' => $qty,
                'terpakai' => 0,
                'harga' => $harga,
                'total' => $qty * $harga,
                'jenis' => $stok->jenis,
                'item_id' => $stok->id,
                'item_nama' => $stok->nama,
            ];

            // Pembelian DOC
            $transaksiPembelian = Transaksi::create([
                'jenis' => 'Pembelian',
                'jenis_barang' => 'DOC',
                'faktur' => 'DOC-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'tanggal' => $faker->dateTimeBetween('-1 month', 'now'),
                'rekanan_id' => $supplier->id,
                'farm_id' => $farm->id,
                'kandang_id' => $kandang->id,
                'rekanan_nama' => $supplier->nama,
                'harga' => $faker->numberBetween(10000, 20000),
                'qty' => $faker->numberBetween(1000, 5000),
                'sub_total' => 0, // Akan diupdate setelah detail ditambahkan
                'status' => 'Aktif',
                'payload' => ['items' => $itemsToStore],
                'user_id' => $supervisor->id, // Supervisor yang menyetujui
            ]);

            // Pembelian Pakan dan Lainnya
            $transaksiPembelianStok = Transaksi::create([
                'jenis' => 'Pembelian',
                'jenis_barang' => 'Pakan',
                'faktur' => 'PB-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'tanggal' => $faker->dateTimeBetween('-1 month', 'now'),
                'rekanan_id' => $supplier->id,
                'farm_id' => $farm->id,
                'kandang_id' => $kandang->id,
                'rekanan_nama' => $supplier->nama,
                'harga' => $faker->numberBetween(10000, 20000),
                'qty' => $faker->numberBetween(1000, 5000),
                'sub_total' => 0, // Akan diupdate setelah detail ditambahkan
                'status' => 'Aktif',
                'payload' => ['items' => $stokToStore],
                'user_id' => $operator->id, // Supervisor yang menyetujui
            ]);

            // Detail Transaksi Pembelian DOC
            $subTotal = 0;
            $qty = $transaksiPembelian->qty;
            $harga = $transaksiPembelian->harga;
            $subTotal += $qty * $harga;

            TransaksiDetail::create([
                'transaksi_id' => $transaksiPembelian->id,
                'jenis' => 'Pembelian',
                'jenis_barang' => 'DOC',
                'tanggal' => $transaksiPembelian->tanggal,
                'rekanan_id' => $supplier->id,
                'farm_id' => $farm->id,
                'item_id' => $stokDoc->id,
                'item_nama' => $stokDoc->nama,
                'harga' => $harga,
                'qty' => $qty,
                'terpakai' => 0,
                'sisa' => $qty,
                'sub_total' => $subTotal,
                'status' => 'Aktif',
                'user_id' => $supervisor->id,
            ]);

            // Update sub total transaksi pembelian
            $transaksiPembelian->sub_total = $subTotal;
            $transaksiPembelian->save();

            // Detail Transaksi Pembelian DOC
            $subTotal = 0;
            $qty = $transaksiPembelianStok->qty;
            $harga = $transaksiPembelianStok->harga;
            $subTotal += $qty * $harga;

            TransaksiDetail::create([
                'transaksi_id' => $transaksiPembelianStok->id,
                'jenis' => 'Pembelian',
                'jenis_barang' => 'Pakan',
                'tanggal' => $transaksiPembelianStok->tanggal,
                'rekanan_id' => $supplier->id,
                'farm_id' => $farm->id,
                'item_id' => $stok->id,
                'item_nama' => $stok->nama,
                'harga' => $harga,
                'qty' => $qty,
                'terpakai' => 0,
                'sisa' => $qty,
                'sub_total' => $subTotal,
                'status' => 'Aktif',
                'user_id' => $operator->id,
            ]);

            // Update sub total transaksi pembelian
            $transaksiPembelianStok->sub_total = $subTotal;
            $transaksiPembelianStok->save();


            // Update Status Kandang
            $kandang->jumlah = $transaksiPembelian->qty;
            $kandang->status = 'Digunakan';
            $kandang->save();
        }

        // Create the default role if it doesn't exist
        $role = Role::firstOrCreate(['name' => 'Operator']); // Replace 'user' with your desired default role name

        // Create users and assign the default role
        User::factory(10) // Adjust the number of users as needed
            ->create()
            ->each(function ($user) use ($role) {
                $user->assignRole($role);

                // Create 1-5 FarmOperator entries for this user
                $numberOfFarms = rand(1, 5); // Generate a random number between 1 and 5

                for ($i = 0; $i < $numberOfFarms; $i++) {
                    // You'll need to fetch or create a 'demoFarm' here
                    $demoFarm = Farm::inRandomOrder()->first(); // Get a random farm

                    FarmOperator::create([
                        'farm_id'        => $demoFarm->id,
                        'nama_farm'       => $demoFarm->nama,
                        'nama_operator'   => $user->name, // Use the newly created user's name
                        'user_id'        => $user->id,
                        'status'         => 'Aktif',
                    ]);
                }
            });
        

        

    }
}

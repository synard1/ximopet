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
        // Define stock data in an array for easier management
        $stockData = [
            ['kode' => 'DOC001', 'jenis' => 'DOC', 'name' => 'Nama Stok DOC', 'satuan_besar' => 'Ekor', 'satuan_kecil' => 'Ekor', 'konversi' => 1],
            ['kode' => 'PK001', 'jenis' => 'Pakan', 'name' => 'Nama Stok Pakan', 'satuan_besar' => 'Kg', 'satuan_kecil' => 'Gram', 'konversi' => 1000],
            ['kode' => 'OB001', 'jenis' => 'Obat', 'name' => 'Nama Stok Obat', 'satuan_besar' => 'Butir', 'satuan_kecil' => 'Butir', 'konversi' => 1],
            ['kode' => 'VK001', 'jenis' => 'Vaksin', 'name' => 'Nama Stok Vaksin', 'satuan_besar' => 'Impul', 'satuan_kecil' => 'Impul', 'konversi' => 1],
            ['kode' => 'LL001', 'jenis' => 'Lainnya', 'name' => 'Nama Stok Lainnya', 'satuan_besar' => 'LL', 'satuan_kecil' => 'LL', 'konversi' => 1],
        ];

        // Create Stok records using the array
        foreach ($stockData as $data) {
            Stok::create(array_merge($data, ['status' => 'Aktif']));
        }

        // Create Rekanan records (Suppliers and Customers)
        for ($i = 1; $i <= 5; $i++) {
            Rekanan::factory()->create([
                'jenis' => 'Supplier',
                'kode' => 'S00' . $i,
            ]);

            Rekanan::factory()->create([
                'jenis' => 'Customer',
                'kode' => 'C00' . $i,
            ]);
        }

        // $jenisStok = ['DOC', 'Pakan', 'Obat', 'Vaksin', 'Lainnya'];
        // $kodeStok = ['DOC', 'PK', 'OB', 'VK', 'LL'];
        // $satuanBesarStok = ['Ekor', 'Kg', 'Butir', 'Impul', 'LL'];
        // $satuanKecilStok = ['Ekor', 'Gram', 'Butir', 'Impul', 'LL'];
        // $konversiStok = [1, 1000, 1, 1, 1];
        $operator = User::where('email','operator@demo.com')->first();
        $supervisor = User::where('email','supervisor@demo.com')->first();
        $counter = 0;

        // Create Farm records
        Farm::factory(5)->create()->each(function ($demoFarm) use ($supervisor, $operator, $faker, &$counter) {
            // Initialize counter if it doesn't exist
            if (!isset($counter)) {
                $counter = 1; 
            }
            // Create FarmOperator record
            $demoFarm->operators()->attach($operator); 

            // Create Kandang records for each farm
            Kandang::factory(1)->create([
                'farm_id' => $demoFarm->id,
                'kode' => 'K00' . $demoFarm->kode, // Assuming you want a unique kandang code per farm
                'nama' => 'Kandang-F' . $demoFarm->kode,
            ]);

            // Data dummy untuk Transaksi Pembelian DOC
            $supplier = Rekanan::where('jenis', 'Supplier')->inRandomOrder()->first();
            $kandang = $demoFarm->kandangs->first(); 
            $stokDoc = Stok::where('jenis', 'DOC')->inRandomOrder()->first();
            $stok = Stok::whereIn('jenis', ['Pakan', 'Obat', 'Vaksin', 'Lainnya'])->inRandomOrder()->first();

            $qty = $faker->numberBetween(10, 20) * 100;
            $harga = $faker->numberBetween(1, 10) * 500;

            // Check if a 'DOC' transaction already exists for this kandang
            $existingDocTransaction = Transaksi::with('transaksiDetail')
                                                ->where('jenis','Pembelian')
                                                ->whereHas('transaksiDetail', function ($query) {
                                                    $query->where('jenis_barang', 'DOC');
                                                })
                                                ->where('kandang_id', $kandang->id)
                                                ->exists();

            // dd($kandang ? $kandang->id : null); 
            // dd($kandang->id);

            // Pembelian DOC
            $transaksiPembelian = Transaksi::create([
                'jenis' => 'Pembelian',
                // 'jenis_barang' => 'DOC',
                'faktur' => 'DOC-' . str_pad($counter + 1, 3, '0', STR_PAD_LEFT),
                'tanggal' => $faker->dateTimeBetween('-1 month', 'now'),
                'rekanan_id' => $supplier->id,
                'farm_id' => $demoFarm->id,
                'kandang_id' => $kandang->id,
                'terpakai' => 0,
                'sisa' => $qty,
                'total_qty' => $qty,
                'harga' => $harga,
                'periode' => 'PR-'.$demoFarm->kode . '-' . $kandang->kode . str_pad($counter + 1, 3, '0', STR_PAD_LEFT),
                // 'sub_total' => 0, // Akan diupdate setelah detail ditambahkan
                'status' => 'Aktif',
                // 'payload' => ['items' => $itemsToStore],
                'user_id' => $supervisor->id, // Supervisor yang menyetujui
            ]);

            // Detail Transaksi Pembelian DOC
            $subTotal = 0;
            // $qty = $transaksiPembelian->qty;
            // $harga = $transaksiPembelian->harga;
            $subTotal += $qty * $harga;

            TransaksiDetail::create([
                'transaksi_id' => $transaksiPembelian->id,
                'jenis' => 'Pembelian',
                'jenis_barang' => 'DOC',
                'tanggal' => $transaksiPembelian->tanggal,
                'rekanan_id' => $supplier->id,
                'farm_id' => $demoFarm->id,
                'kandang_id' => $kandang->id,
                'item_id' => $stokDoc->id,
                'item_nama' => $stokDoc->nama,
                'harga' => $harga,
                'qty' => $qty,
                'terpakai' => 0,
                'sisa' => $qty,
                'sub_total' => $subTotal,
                'konversi' => 1,
                'periode' => $transaksiPembelian->periode,
                'status' => 'Aktif',
                'user_id' => $supervisor->id,
            ]);

            // Update sub total transaksi pembelian
            $transaksiPembelian->sub_total = $subTotal;
            $transaksiPembelian->save();

            // Detail Transaksi Pembelian Stok
            $subTotal = 0;
            $qty = $faker->numberBetween(10, 20) * 100;
            $harga = $faker->numberBetween(10, 20) * 1000;
            $subTotal += $qty * $harga;

            // Pembelian Pakan dan Lainnya
            $transaksiPembelianStok = Transaksi::create([
                'jenis' => 'Pembelian',
                // 'jenis_barang' => 'Pakan',
                'faktur' => 'PB-' . str_pad($counter + 1, 3, '0', STR_PAD_LEFT),
                'tanggal' => $faker->dateTimeBetween('-1 month', 'now'),
                'rekanan_id' => $supplier->id,
                'farm_id' => $demoFarm->id,
                'kandang_id' => $kandang->id,
                // 'rekanan_nama' => $supplier->nama,
                'harga' => $harga,
                'total_qty' => $qty,
                'terpakai' => 0,
                'sisa' => $qty,
                'sub_total' => $harga * $qty, // Akan diupdate setelah detail ditambahkan
                'status' => 'Aktif',
                // 'payload' => ['items' => $stokToStore],
                'user_id' => $operator->id, // Supervisor yang menyetujui
            ]);

            
            TransaksiDetail::create([
                'transaksi_id' => $transaksiPembelianStok->id,
                'jenis' => 'Pembelian',
                'jenis_barang' => 'Pakan',
                'tanggal' => $transaksiPembelianStok->tanggal,
                'rekanan_id' => $supplier->id,
                'farm_id' => $demoFarm->id,
                'item_id' => $stok->id,
                'item_nama' => $stok->nama,
                'harga' => $harga,
                'qty' => $qty * $stok->konversi,
                'terpakai' => 0,
                'sisa' => $qty * $stok->konversi,
                'sub_total' => ($qty * $harga),
                'konversi' => $stok->konversi,
                'status' => 'Aktif',
                'user_id' => $operator->id,
            ]);

            // Update sub total transaksi pembelian
            $transaksiPembelianStok->sub_total = $subTotal;
            $transaksiPembelianStok->save();


            // Update Status Kandang
            $kandang->jumlah = $transaksiPembelian->transaksiDetail->first()?->qty;
            $kandang->status = 'Digunakan';
            $kandang->save();

            $counter++; // Increment the counter for the next iteration
        });

        // for ($i=0; $i < count($jenisStok); $i++) { 
        //     $demoStok = Stok::create([
        //         'kode'          => $kodeStok[$i].'001',
        //         'jenis'         => $jenisStok[$i],
        //         'nama'          => 'Nama Stok'.$jenisStok[$i],
        //         'satuan_besar'  => $satuanBesarStok[$i],
        //         'satuan_kecil'  => $satuanKecilStok[$i],
        //         'konversi'      => $konversiStok[$i],
        //         'status'        => 'Aktif',
        //     ]);
        // }

        for ($i=0; $i < 5; $i++) { 
            // $demoRekanan = Rekanan::create([
            //     'jenis'         => 'Supplier',
            //     'kode'          => 'S00'.$i+1,
            //     'nama'          => $faker->company,
            //     'alamat'        => $faker->address,
            //     'telp'          => $faker->phoneNumber,
            //     'pic'           => $faker->name,
            //     'telp_pic'      => $faker->phoneNumber,
            //     'email'         => $faker->email,
            //     'status'        => 'Aktif',
            // ]);

            // $demoRekanan2 = Rekanan::create([
            //     'jenis'         => 'Customer',
            //     'kode'          => 'C00'.$i+1,
            //     'nama'          => $faker->company,
            //     'alamat'        => $faker->address,
            //     'telp'          => $faker->phoneNumber,
            //     'pic'           => $faker->name,
            //     'telp_pic'      => $faker->phoneNumber,
            //     'email'         => $faker->email,
            //     'status'        => 'Aktif',
            // ]);

            // $demoFarm = Farm::create([
            //     'kode'          => 'F00'.$i+1,
            //     'nama'          => $faker->company.'-Farm',
            //     'alamat'        => $faker->address,
            //     'telp'          => $faker->phoneNumber,
            //     'pic'           => $faker->name,
            //     'telp_pic'      => $faker->phoneNumber,
            //     'jumlah'        => 0,
            //     'kapasitas'     => '1000000',
            //     'status'        => 'Aktif',
            // ]);

            // $demoFarmOperator = FarmOperator::create([
            //     'farm_id'        => $demoFarm->id,
            //     'user_id'        => $operator->id,
            // ]);
    
            // $demoKandang = Kandang::create([
            //     'kode'          => 'K00'.$i+1,
            //     'farm_id'       => $demoFarm->id,
            //     'nama'          => 'Kandang-FK00'.$i+1,
            //     'jumlah'        => 0,
            //     'kapasitas'     => '100000',
            //     'status'        => 'Aktif',
            //     'user_id'       => 1,
            // ]);

            // Data dummy untuk Transaksi Pembelian DOC
            // $supplier = Rekanan::where('jenis', 'Supplier')->inRandomOrder()->first();
            // $farm = Farm::inRandomOrder()->first();
            // $kandang = $farm->kandangs->first(); // Get the first kandang associated with the farm
            // $stokDoc = Stok::where('jenis', 'DOC')->inRandomOrder()->first();

            // $stok = Stok::whereIn('jenis', ['Pakan', 'Obat', 'Vaksin', 'Lainnya'])->inRandomOrder()->first();
            // $qty = $faker->numberBetween(10, 20) * 100;
            // $harga = $faker->numberBetween(1, 10) * 500;

            // // Pembelian DOC
            // $transaksiPembelian = Transaksi::create([
            //     'jenis' => 'Pembelian',
            //     // 'jenis_barang' => 'DOC',
            //     'faktur' => 'DOC-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
            //     'tanggal' => $faker->dateTimeBetween('-1 month', 'now'),
            //     'rekanan_id' => $supplier->id,
            //     'farm_id' => $farm->id,
            //     'kandang_id' => $kandang->id,
            //     'terpakai' => 0,
            //     'sisa' => $qty,
            //     'total_qty' => $qty,
            //     'harga' => $harga,
            //     'periode' => 'PR-'.$farm->kode . '-' . $kandang->kode . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
            //     // 'sub_total' => 0, // Akan diupdate setelah detail ditambahkan
            //     'status' => 'Aktif',
            //     // 'payload' => ['items' => $itemsToStore],
            //     'user_id' => $supervisor->id, // Supervisor yang menyetujui
            // ]);

            // // Detail Transaksi Pembelian DOC
            // $subTotal = 0;
            // // $qty = $transaksiPembelian->qty;
            // // $harga = $transaksiPembelian->harga;
            // $subTotal += $qty * $harga;

            // TransaksiDetail::create([
            //     'transaksi_id' => $transaksiPembelian->id,
            //     'jenis' => 'Pembelian',
            //     'jenis_barang' => 'DOC',
            //     'tanggal' => $transaksiPembelian->tanggal,
            //     'rekanan_id' => $supplier->id,
            //     'farm_id' => $farm->id,
            //     'item_id' => $stokDoc->id,
            //     'item_nama' => $stokDoc->nama,
            //     'harga' => $harga,
            //     'qty' => $qty,
            //     'terpakai' => 0,
            //     'sisa' => $qty,
            //     'sub_total' => $subTotal,
            //     'konversi' => 1,
            //     'periode' => $transaksiPembelian->periode,
            //     'status' => 'Aktif',
            //     'user_id' => $supervisor->id,
            // ]);

            // // Update sub total transaksi pembelian
            // $transaksiPembelian->sub_total = $subTotal;
            // $transaksiPembelian->save();


            // $stokToStore[] = [
            //     // 'qty' => $qty,
            //     'terpakai' => 0,
            //     // 'harga' => $harga,
            //     'total' => $qty * $harga,
            //     'jenis' => $stok->jenis,
            //     'item_id' => $stok->id,
            //     'item_nama' => $stok->nama,
            //     'satuan_besar' => $stok->satuan_besar,
            //     'satuan_kecil' => $stok->satuan_kecil,
            //     'konversi' => $stok->konversi,
            // ];

            // // Pembelian Pakan dan Lainnya
            // $transaksiPembelianStok = Transaksi::create([
            //     'jenis' => 'Pembelian',
            //     // 'jenis_barang' => 'Pakan',
            //     'faktur' => 'PB-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
            //     'tanggal' => $faker->dateTimeBetween('-1 month', 'now'),
            //     'rekanan_id' => $supplier->id,
            //     'farm_id' => $farm->id,
            //     'kandang_id' => $kandang->id,
            //     // 'rekanan_nama' => $supplier->nama,
            //     // 'harga' => $faker->numberBetween(10000, 20000),
            //     'total_qty' => 0,
            //     // 'sub_total' => 0, // Akan diupdate setelah detail ditambahkan
            //     'status' => 'Aktif',
            //     // 'payload' => ['items' => $stokToStore],
            //     'user_id' => $operator->id, // Supervisor yang menyetujui
            // ]);

            // // Detail Transaksi Pembelian Stok
            // $subTotal = 0;
            // $qty = $faker->numberBetween(10, 20);
            // $harga = $faker->numberBetween(10, 20) * 1000;
            // $subTotal += $qty * $harga;

            // TransaksiDetail::create([
            //     'transaksi_id' => $transaksiPembelianStok->id,
            //     'jenis' => 'Pembelian',
            //     'jenis_barang' => 'Pakan',
            //     'tanggal' => $transaksiPembelianStok->tanggal,
            //     'rekanan_id' => $supplier->id,
            //     'farm_id' => $farm->id,
            //     'item_id' => $stok->id,
            //     'item_nama' => $stok->nama,
            //     'harga' => $harga,
            //     'qty' => $qty * $stok->konversi,
            //     'terpakai' => 0,
            //     'sisa' => $qty * $stok->konversi,
            //     'sub_total' => $qty * $harga,
            //     'konversi' => $stok->konversi,
            //     'status' => 'Aktif',
            //     'user_id' => $operator->id,
            // ]);

            // // Update sub total transaksi pembelian
            // // $transaksiPembelianStok->sub_total = $subTotal;
            // // $transaksiPembelianStok->save();


            // // Update Status Kandang
            // $kandang->jumlah = 0;
            // $kandang->status = 'Digunakan';
            // $kandang->save();
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

                    // FarmOperator::create([
                    //     'farm_id'        => $demoFarm->id,
                    //     'user_id'        => $user->id,
                    // ]);
                    $existingFarmOperator = FarmOperator::where('farm_id', $demoFarm->id)
                                  ->where('user_id', $user->id)
                                  ->exists();

                    if (!$existingFarmOperator) {
                        FarmOperator::create([
                            'farm_id' => $demoFarm->id,
                            'user_id' => $user->id,
                        ]);
                    }
                }
            });
        

        

    }
}

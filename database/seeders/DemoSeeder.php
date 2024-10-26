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
use App\Models\Item;
use App\Models\Transaksi;
use App\Models\TransaksiDetail;
use Spatie\Permission\Models\Role;
use App\Models\StokMutasi;

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
            ['kode' => 'VT001', 'jenis' => 'Vitamin', 'name' => 'Nama Stok Vitamin', 'satuan_besar' => 'Tablet', 'satuan_kecil' => 'Tablet', 'konversi' => 1],
            ['kode' => 'LL001', 'jenis' => 'Lainnya', 'name' => 'Nama Stok Lainnya', 'satuan_besar' => 'LL', 'satuan_kecil' => 'LL', 'konversi' => 1],
        ];

        $beratBeli = 100;
        $beratJual = 0;

        // Create Stok records using the array
        foreach ($stockData as $data) {
            Item::create(array_merge($data, ['status' => 'Aktif']));
        }

        // Create Rekanan records (Suppliers and Customers)
        for ($i = 1; $i <= 7; $i++) {
            Rekanan::factory()->create([
                'jenis' => 'Supplier',
                'kode' => 'S00' . $i,
            ]);

            Rekanan::factory()->create([
                'jenis' => 'Customer',
                'kode' => 'C00' . $i,
            ]);
        }

        $operator1 = User::where('email','operator@demo.com')->first();
        $operator2 = User::where('email','operator2@demo.com')->first();
        $supervisor = User::where('email','supervisor@demo.com')->first();
        $counter = 0;

        // Create Farm records
        Farm::factory(7)->create()->each(function ($demoFarm) use ($supervisor, $operator1, $operator2, $faker, &$counter, $beratBeli, $beratJual) {
            // Initialize counter if it doesn't exist
            if (!isset($counter)) {
                $counter = 1; 
            }
            // Create FarmOperator record
            $operator = ($counter % 2 == 0) ? $operator1 : $operator2;
            $demoFarm->operators()->attach($operator); 

            // Create Kandang records for each farm
            Kandang::factory(1)->create([
                'farm_id' => $demoFarm->id,
                'kode' => 'K00' . $demoFarm->kode,
                'nama' => 'Kandang-F' . $demoFarm->kode,
            ]);

            // Data dummy untuk Transaksi Pembelian DOC
            $supplier = Rekanan::where('jenis', 'Supplier')->inRandomOrder()->first();
            $kandang = $demoFarm->kandangs->first(); 
            $stokDoc = Item::where('jenis', 'DOC')->inRandomOrder()->first();
            $stok = Item::whereIn('jenis', ['Pakan', 'Obat', 'Vaksin', 'Lainnya'])->inRandomOrder()->first();

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

            // Pembelian DOC
            $transaksiPembelian = Transaksi::create([
                'jenis' => 'Pembelian',
                'faktur' => 'DOC-' . str_pad($counter + 1, 3, '0', STR_PAD_LEFT),
                'tanggal' => $faker->dateTimeBetween('-1 month', 'now'),
                'rekanan_id' => $supplier->id,
                'farm_id' => $demoFarm->id,
                'kandang_id' => $kandang->id,
                'terpakai' => 0,
                'sisa' => $qty,
                'total_qty' => $qty,
                'total_berat' => $qty * 100,
                'harga' => $harga,
                'sub_total' => $qty * $harga,
                'status' => 'Aktif',
                'user_id' => $supervisor->id,
            ]);

            if($transaksiPembelian->kelompokTernak()->exists()){
                $kelompokTernak = $transaksiPembelian->kelompokTernak;
            }else{
                $kelompokTernak = $transaksiPembelian->kelompokTernak()->create([
                    'transaksi_id' => $transaksiPembelian->id,
                    'name' => 'PR-'.$demoFarm->kode . '-' . $kandang->kode . str_pad($counter + 1, 3, '0', STR_PAD_LEFT),
                    'breed' => 'DOC',
                    'start_date' => $transaksiPembelian->tanggal,
                    'estimated_end_date' => $transaksiPembelian->tanggal->addMonths(6),
                    'stok_awal' => 0,
                    'stok_masuk' => $qty,
                    'jumlah_mati' => 0,
                    'jumlah_dipotong' => 0,
                    'jumlah_dijual' => 0,
                    'stok_akhir' => $qty,
                    'berat_beli' => $beratBeli * $qty,
                    'berat_jual' => $beratJual * $qty,
                    'status' => 'Aktif',
                    'farm_id' => $transaksiPembelian->farm_id,
                    'kandang_id' => $transaksiPembelian->kandang_id,
                    'created_by' => $supervisor->id,
                ]);

                $historyTernak = $kelompokTernak->historyTernaks()->create([
                    'transaksi_id' => $transaksiPembelian->id,
                    'kelompok_ternak_id' => $kelompokTernak->id,
                    'parent_id' => null,
                    'farm_id' => $transaksiPembelian->farm_id,
                    'kandang_id' => $transaksiPembelian->kandang_id,
                    'tanggal' => $transaksiPembelian->tanggal,
                    'jenis' => 'Masuk',
                    'perusahaan_nama' => $transaksiPembelian->rekanans->nama,
                    'hpp' => $transaksiPembelian->sub_total,
                    'stok_awal' => 0,
                    'stok_akhir' => $qty,
                    'stok_masuk' => $qty,
                    'stok_keluar' => 0,
                    'total_berat' => $kelompokTernak->berat_beli,
                    'status' => 'hidup',
                    'keterangan' => null,
                    'created_by' => $supervisor->id,
                ]);

                // Update Transaksi Pembelian Ternak
                $transaksiPembelian->kelompok_ternak_id = $kelompokTernak->id;
                $transaksiPembelian->save();

                // Detail Transaksi Pembelian DOC
                $subTotal = 0;
                $subTotal += $qty * $harga;

                $transaksiDetail = $transaksiPembelian->transaksiDetail()->create([
                    'transaksi_id' => $transaksiPembelian->id,
                    'jenis' => 'Pembelian',
                    'jenis_barang' => 'DOC',
                    'tanggal' => $transaksiPembelian->tanggal,
                    'item_id' => $stokDoc->id,
                    'item_name' => $stokDoc->name,
                    'harga' => $harga,
                    'qty' => $qty,
                    'berat' => $kelompokTernak->berat_beli,
                    'terpakai' => 0,
                    'sisa' => $qty,
                    'sub_total' => $subTotal,
                    'satuan_besar' => $stokDoc->satuan_besar,
                    'satuan_kecil' => $stokDoc->satuan_kecil,
                    'konversi' => 1,
                    'status' => 'Aktif',
                    'user_id' => $supervisor->id,
                ]);

                // Update Status Kandang
                $kandang->jumlah = $transaksiPembelian->transaksiDetail->first()?->qty;
                $kandang->berat = $transaksiPembelian->transaksiDetail->first()?->berat;
                $kandang->kelompok_ternak_id = $kelompokTernak->id;
                $kandang->status = 'Digunakan';
                $kandang->save();
            }

            // Detail Transaksi Pembelian Stok
            $subTotal = 0;
            $qty = $faker->numberBetween(10, 20) * 100;
            $harga = $faker->numberBetween(10, 20) * 1000;
            $subTotal += $qty * $harga;

            // Pembelian Pakan dan Lainnya
            $transaksiPembelianStok = Transaksi::create([
                'jenis' => 'Pembelian',
                'faktur' => 'PB-' . str_pad($counter + 1, 3, '0', STR_PAD_LEFT),
                'tanggal' => $faker->dateTimeBetween('-1 month', 'now'),
                'rekanan_id' => $supplier->id,
                'farm_id' => $demoFarm->id,
                'kandang_id' => $kandang->id,
                'harga' => $harga,
                'total_qty' => $qty,
                'terpakai' => 0,
                'sisa' => $qty,
                'sub_total' => $harga * $qty,
                'status' => 'Aktif',
                'user_id' => $operator->id,
            ]);

            if($transaksiPembelianStok->exists()){
                $transaksiDetail = $transaksiPembelianStok->transaksiDetail()->create([
                    'transaksi_id' => $transaksiPembelianStok->id,
                    'jenis' => 'Pembelian',
                    'jenis_barang' => $stok->jenis,
                    'tanggal' => $transaksiPembelianStok->tanggal,
                    'item_id' => $stok->id,
                    'item_name' => $stok->name,
                    'harga' => $harga,
                    'qty' => $qty * $stok->konversi,
                    'berat' => 0,
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

                $stokHistory = $transaksiPembelianStok->stokHistory()->create([
                    'transaksi_id' => $transaksiPembelianStok->id,
                    'parent_id' => null,
                    'farm_id' => $demoFarm->id,
                    'kandang_id' => $kandang->id,
                    'tanggal' => $transaksiPembelianStok->tanggal,
                    'jenis' => 'Masuk',
                    'item_id' => $stok->id,
                    'item_name' => $stok->name,
                    'satuan' => $stok->satuan_besar,
                    'jenis_barang' => $stok->jenis,
                    'kadaluarsa' => $transaksiPembelianStok->tanggal->addMonths(18),
                    'perusahaan_nama' => $transaksiPembelianStok->rekanans->nama,
                    'hpp' => $transaksiPembelianStok->harga,
                    'stok_awal' => 0,
                    'stok_masuk' => $qty * $stok->konversi,
                    'stok_keluar' => 0,
                    'stok_akhir' => $qty * $stok->konversi,
                    'status' => 'Aktif',
                    'user_id' => $operator->id,
                ]);
            }

            $counter++;
        });

        // Create the default role if it doesn't exist
        $role = Role::firstOrCreate(['name' => 'Operator']);

        // Create users and assign the default role
        User::factory(5)
            ->create()
            ->each(function ($user) use ($role) {
                $user->assignRole($role);

                $numberOfFarms = rand(1, 5);

                for ($i = 0; $i < $numberOfFarms; $i++) {
                    $demoFarm = Farm::inRandomOrder()->first();

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
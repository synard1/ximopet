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
use App\Models\ItemCategory;
use App\Models\ItemLocation;
use App\Models\InventoryLocation;
use App\Models\CurrentStock;
use App\Models\StockMovement;
use App\Models\StockHistory;
use App\Models\TransaksiBeli;
use App\Models\TransaksiBeliDetail;
use App\Models\KelompokTernak;
use App\Models\CurrentTernak;
use App\Models\TransaksiTernak;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;

class DemoSeeder extends Seeder
{
    public function run(Generator $faker)
    {
        // Create item categories and items (unchanged)
        $this->createItemCategoriesAndItems($faker);

        // Run OVK Seeder
        $this->call([
            OVKSeeder::class,
        ]);

        // Add this line to create pakan items
        $this->createPakan();

        // Create Rekanan records
        $this->createRekanan($faker);

        // Create farms and related data
        $this->createFarms($faker);

        // Add this line to create item location mappings before purchases
        $this->createItemLocationMappings();

        // Now create purchases for each farm
        // $this->createPurchases($faker);

        // Create additional users with roles (unchanged)
        // $this->createAdditionalUsers($faker);
    }

    private function createItemCategoriesAndItems(Generator $faker)
    {
        $categoryData = [
            ['name' => 'DOC', 'code' => 'DOC', 'description' => 'Day Old Chick Category'],
            ['name' => 'Pakan', 'code' => 'PKN', 'description' => 'Feed Category'],
            ['name' => 'OVK', 'code' => 'OVK', 'description' => 'OVK Category'],
            // ['name' => 'Obat', 'code' => 'OBT', 'description' => 'Medicine Category'],
            // ['name' => 'Vaksin', 'code' => 'VKS', 'description' => 'Vaccine Category'],
            // ['name' => 'Vitamin', 'code' => 'VTM', 'description' => 'Vitamin Category'],
            ['name' => 'Lainnya', 'code' => 'LNY', 'description' => 'Other Category'],
        ];

        foreach ($categoryData as $data) {
            $supervisor = User::where('email', 'supervisor@demo.com')->first();
            $category = ItemCategory::create(array_merge($data, ['status' => 'Aktif', 'created_by' => $supervisor->id]));

            // Item::create([
            //     'category_id' => $category->id,
            //     'kode' => $category->code . '001',
            //     'name' => 'Nama Stok ' . $category->name,
            //     'satuan_besar' => $this->getSatuanBesar($category->name),
            //     'satuan_kecil' => $this->getSatuanKecil($category->name),
            //     'konversi' => $category->name === 'Pakan' ? 1000 : 1,
            //     'status' => 'Aktif',
            //     'is_feed' => $category->name === 'Pakan',
            //     'created_by' => $supervisor->id,
            // ]);
        }
    }

    private function createPakan()
    {
        $pakanTypes = [
            ['name' => 'S 10', 'code' => 'S10'],
            ['name' => 'TS 11', 'code' => 'TS11'],
            ['name' => 'OS 11', 'code' => 'OS11'],
            ['name' => 'KSS 12', 'code' => 'KSS12'],
            ['name' => 'OSP 12', 'code' => 'OSP12'],
            ['name' => 'GONI', 'code' => 'GONI'],
        ];

        $supervisor = User::where('email', 'supervisor@demo.com')->first();
        $pakanCategory = ItemCategory::where('name', 'Pakan')->first();

        if (!$pakanCategory) {
            $pakanCategory = ItemCategory::create([
                'name' => 'Pakan',
                'code' => 'PKN',
                'description' => 'Feed Category',
                'status' => 'Aktif',
                'created_by' => $supervisor->id
            ]);
        }

        foreach ($pakanTypes as $type) {
            Item::create([
                'category_id' => $pakanCategory->id,
                'kode' => $pakanCategory->code . $type['code'],
                'name' => $type['name'],
                'satuan_besar' => 'Kg',
                'satuan_kecil' => 'Kg',
                'konversi' => 1,
                'status' => 'Aktif',
                'is_feed' => true,
                'created_by' => $supervisor->id,
            ]);
        }
    }

    private function createFarms(Generator $faker)
    {
        Farm::factory(7)->create()->each(function ($farm) use ($faker) {
            $supervisor = User::where('email', 'supervisor@demo.com')->first();

            // Create warehouse location for farm
            InventoryLocation::create([
                'farm_id' => $farm->id,
                'name' => 'Gudang ' . $farm->nama,
                'code' => 'WH-' . $farm->kode,
                'type' => 'warehouse',
                'status' => 'Aktif',
                'created_by' => $supervisor->id,
            ]);

            // Create kandang
            Kandang::factory()->create([
                'farm_id' => $farm->id,
                'kode' => 'K00' . $farm->kode,
                'nama' => 'Kandang-F' . $farm->kode,
            ]);
        });
    }

    private function createPurchases(Generator $faker)
    {
        Farm::all()->each(function ($farm) use ($faker) {
            $supervisor = User::where('email', 'supervisor@demo.com')->first();
            $operator1 = User::where('email', 'operator@demo.com')->first();
            $operator2 = User::where('email', 'operator2@demo.com')->first();

            // Get a random warehouse
            $warehouseLocation = InventoryLocation::where('farm_id', $farm->id)
                ->where('type', 'warehouse')
                ->first();

            // Assign operator
            $operator = rand(0, 1) ? $operator1 : $operator2;
            $farm->operators()->attach($operator);

            // Get the kandang for this farm
            $kandang = Kandang::where('farm_id', $farm->id)->first();

            // Create DOC purchase
            $this->createDocPurchase($farm, $kandang, $warehouseLocation, $supervisor, $faker);

            // Create inventory purchase
            $this->createInventoryPurchase($farm, $kandang, $warehouseLocation, $operator, $faker);
        });
    }

    private function getSatuanBesar($categoryName)
    {
        return match($categoryName) {
            'DOC' => 'Ekor',
            'Pakan' => 'Kg',
            'Obat' => 'Butir',
            'Vaksin' => 'Impul',
            'Vitamin' => 'Tablet',
            default => 'LL'
        };
    }

    private function getSatuanKecil($categoryName)
    {
        return match($categoryName) {
            'DOC' => 'Ekor',
            'Pakan' => 'Kg',
            'Obat' => 'Butir',
            'Vaksin' => 'Impul',
            'Vitamin' => 'Tablet',
            default => 'LL'
        };
    }

    private function createItemLocationMappings()
    {
        $farms = Farm::all();
        $items = Item::all();

        foreach ($farms as $farm) {
            // Get the warehouse location for this farm
            $warehouseLocation = InventoryLocation::where('farm_id', $farm->id)
                ->where('type', 'warehouse')
                ->first();

            if (!$warehouseLocation) {
                continue; // Skip if no warehouse found for this farm
            }

            foreach ($items as $item) {
                // Create or update the item location mapping
                ItemLocation::updateOrCreate(
                    [
                        'item_id' => $item->id,
                        'farm_id' => $farm->id,
                    ],
                    [
                        'location_id' => $warehouseLocation->id,
                        'created_by' => 3, // Assuming 3 is a valid user ID (e.g., supervisor)
                        'updated_by' => 3,
                    ]
                );
            }
        }
    }

    private function createAdditionalUsers(Generator $faker)
    {
        $roles = ['manager', 'accountant', 'staff'];

        foreach ($roles as $role) {
            $user = User::create([
                'name' => ucfirst($role),
                'email' => $role . '@demo.com',
                'password' => bcrypt('demo'),
            ]);

            $role = Role::firstOrCreate(['name' => $role]);
            $user->assignRole($role);
        }
    }

    private function createRekanan(Generator $faker)
    {
        $rekananTypes = ['Supplier', 'Customer', 'Both'];
        foreach ($rekananTypes as $type) {
            for ($i = 0; $i < 5; $i++) {
                Rekanan::create([
                    'jenis' => $type,
                    'kode' => 'REK-' . $type[0] . '-' . str_pad(Rekanan::count() + 1, 3, '0', STR_PAD_LEFT),
                    'nama' => $faker->company,
                    'alamat' => $faker->address,
                    'telp' => $faker->phoneNumber,
                    'pic' => $faker->name,
                    'telp_pic' => $faker->phoneNumber,
                    'email' => $faker->companyEmail,
                    'status' => 'Aktif',
                    'created_by' => 3,
                ]);
            }
        }
    }

    private function createDocPurchase($farm, $kandang, $location, $supervisor, $faker)
    {
        if ($supervisor) {
            $supplier = Rekanan::where('jenis', 'Supplier')->inRandomOrder()->first();
            $docItem = Item::whereHas('category', function($q) {
                $q->where('name', 'DOC');
            })->first();

            $qty = $faker->numberBetween(10, 20) * 100;
            $harga = $faker->numberBetween(1, 10) * 500;
            $tanggal = $faker->dateTimeBetween('-1 month', 'now');
            $batchNumber = $this->generateUniqueBatchNumber($tanggal);

            

            // Create purchase transaction
            $purchase = TransaksiBeli::create([
                'jenis' => 'DOC',
                'faktur' => 'DOC-' . str_pad($farm->id, 3, '0', STR_PAD_LEFT),
                'tanggal' => $faker->dateTimeBetween('-1 month', 'now'),
                'rekanan_id' => $supplier->id,
                'batch_number' => $batchNumber,
                'farm_id' => $farm->id,
                'kandang_id' => $kandang->id,
                'total_qty' => $qty,
                'total_berat' => $qty * 0.1, // Assuming 100g per DOC
                'harga' => $harga,
                'sub_total' => $qty * $harga,
                'terpakai' => 0,
                'sisa' => $qty,
                'notes' => 'Initial DOC Purchase',
                'status' => 'Aktif',
                'created_by' => 3,
            ]);

            // Create purchase detail
            TransaksiBeliDetail::create([
                'transaksi_id' => $purchase->id,
                'jenis' => 'Pembelian',
                'jenis_barang' => 'DOC',
                'tanggal' => $purchase->tanggal,
                'item_id' => $docItem->id,
                'item_name' => $docItem->name,
                'qty' => $qty,
                'berat' => $qty * 0.1,
                'harga' => $harga,
                'sub_total' => $qty * $harga,
                'terpakai' => 0,
                'sisa' => $qty,
                'satuan_besar' => $docItem->satuan_besar,
                'satuan_kecil' => $docItem->satuan_kecil,
                'konversi' => $docItem->konversi,
                'status' => 'Aktif',
                'created_by' => 3,
            ]);

            // Create kelompok ternak
            $kelompokTernak = KelompokTernak::create([
                'transaksi_id' => $purchase->id,
                'name' => 'PR-' . $farm->kode . '-' . $kandang->kode . '-' . Carbon::parse($purchase->tanggal)->format('dmY'),
                'breed' => 'DOC',
                'start_date' => $purchase->tanggal,
                'populasi_awal' => $qty,
                'berat_awal' => $qty * 0.1,
                'hpp' => $harga,
                'status' => 'Aktif',
                'keterangan' => 'Initial DOC Purchase',
                'created_by' => 3,
            ]);

        // Add new code: Create transaksi_ternak record
        TransaksiTernak::create([
            'kelompok_ternak_id' => $kelompokTernak->id,
            'jenis_transaksi' => 'Pembelian',
            'tanggal' => $purchase->tanggal,
            'farm_id' => $farm->id,
            'kandang_id' => $kandang->id,
            'quantity' => $qty,
            'berat_total' => $qty * 0.1,
            'berat_rata' => 0.1, // DOC average weight
            'harga_satuan' => $harga,
            'total_harga' => $qty * $harga,
            'status' => 'Aktif',
            'keterangan' => 'Pembelian DOC Batch ' . $kelompokTernak->name,
            'created_by' => 3,
        ]);

        // Update purchase with kelompok_ternak_id
        $purchase->update([
            'kelompok_ternak_id' => $kelompokTernak->id
        ]);

            // Create current ternak record
            CurrentTernak::create([
                'kelompok_ternak_id' => $kelompokTernak->id,
                'farm_id' => $farm->id,
                'kandang_id' => $kandang->id,
                'quantity' => $qty,
                'berat_total' => $qty * 0.1,
                'avg_berat' => 0.1,
                'status' => 'Aktif',
                'created_by' => 3,
            ]);

        // Update kandang
        $kandang->update([
            'jumlah' => $qty,
            'berat' => $qty * 0.1,
            'kelompok_ternak_id' => $kelompokTernak->id,
            'status' => 'Digunakan',
            'updated_by' => $supervisor->id,
        ]);
        }
    }

    private function createInventoryPurchase($farm, $kandang, $warehouse, $operator, $faker)
    {
        // Get a random supplier
        $supplier = Rekanan::where('jenis', 'Supplier')->inRandomOrder()->first();

        // Get random item category (excluding DOC)
        $category = ItemCategory::where('name', '!=', 'DOC')->inRandomOrder()->first();

        // Get random item from the selected category
        $item = Item::where('category_id', $category->id)->inRandomOrder()->first();


        $qty = $faker->numberBetween(100, 1000);
        $harga = $faker->numberBetween(1000, 10000);
        $tanggal = $faker->dateTimeBetween('-1 month', 'now');
        $batchNumber = $this->generateUniqueBatchNumber($tanggal);


        // Create purchase transaction
        $purchase = TransaksiBeli::create([
            'jenis' => $category->name,
            'faktur' => $category->code . '-' . str_pad($farm->id, 3, '0', STR_PAD_LEFT),
            'tanggal' => $tanggal,
            'rekanan_id' => $supplier->id,
            'batch_number' => $batchNumber,
            'farm_id' => $farm->id,
            'kandang_id' => $kandang->id,
            'total_qty' => $qty,
            'total_berat' => $qty * ($item->is_feed ? 1 : 0.1), // Assume 1kg for feed, 100g for others
            'harga' => $harga,
            'sub_total' => $qty * $harga,
            'terpakai' => 0,
            'sisa' => $qty,
            'notes' => 'Purchase of ' . $item->name,
            'status' => 'Aktif',
            'created_by' => $operator->id,
        ]);

        // Create purchase detail
        TransaksiBeliDetail::create([
            'transaksi_id' => $purchase->id,
            'jenis' => 'Pembelian',
            'jenis_barang' => $category->name,
            'tanggal' => $purchase->tanggal,
            'item_id' => $item->id,
            'item_name' => $item->name,
            'qty' => $qty,
            'berat' => $qty * ($item->is_feed ? 1 : 0.1),
            'harga' => $harga,
            'sub_total' => $qty * $harga,
            'terpakai' => 0,
            'sisa' => $qty,
            'satuan_besar' => $item->satuan_besar,
            'satuan_kecil' => $item->satuan_kecil,
            'konversi' => $item->konversi,
            'status' => 'Aktif',
            'created_by' => $operator->id,
        ]);



        // Create inventory stock
        $currentStock = CurrentStock::create([
            'item_id' => $item->id,
            'location_id' => $location->id,
            'quantity' => $qty,
            'reserved_quantity' => 0,
            'available_quantity' => $qty,
            'hpp' => $harga,
            'status' => 'Aktif',
            'created_by' => $operator->id,
        ]);
        
        // Create stock movement record
        StockMovement::create([
            'transaksi_id' => $purchase->id,
            'item_id' => $item->id,
            'destination_location_id' => $location->id,
            'movement_type' => 'purchase',
            'tanggal' => $tanggal,
            'batch_number' => $batchNumber,
            'quantity' => $qty,
            'satuan' => $item->satuan_besar,
            'hpp' => $harga,
            'status' => 'Completed',
            'keterangan' => 'Initial purchase of ' . $item->name,
            'created_by' => $operator->id,
        ]);

        // Log the StockHistory
        StockHistory::create([
            'transaksi_id' => $purchase->id,
            'jenis' => 'Pembelian',
            'parent_id' => null,
            'stock_id' => $currentStock ? $currentStock->id : null,
            'item_id' => $item->id,
            'location_id' => $currentStock->location_id,
            'batch_number' => $batchNumber,
            'expiry_date' => null,
            'quantity' => $qty,
            'reserved_quantity' => 0,
            'available_quantity' => $qty,
            'hpp' => $harga,
            'status' => 'In',
            'created_by' => $operator->id,
            // Other fields for StockHistory
        ]);
    }

    private function getTransaksiBeliCountByDate($date)
    {
        // Validate the date format (optional)
        $validatedDate = Carbon::parse($date)->format('Y-m-d');

        // Count the number of TransaksiBeli for the specified date, including soft-deleted records
        return TransaksiBeli::withTrashed()
            ->whereDate('tanggal', $validatedDate)
            ->count();
    }

    private function generateUniqueBatchNumber($date)
    {
        $batchDate = Carbon::parse($date)->format('Ymd');
        $baseNumber = 'Pembelian-' . $batchDate . '-';

        $latestBatch = TransaksiBeli::withTrashed()
            ->whereDate('tanggal', $date)
            ->where('batch_number', 'like', $baseNumber . '%')
            ->orderByRaw('CAST(SUBSTRING(batch_number, -2) AS UNSIGNED) DESC')
            ->value('batch_number');

        if ($latestBatch) {
            $latestNumber = intval(substr($latestBatch, -2));
            $newNumber = $latestNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $baseNumber . str_pad($newNumber, 2, '0', STR_PAD_LEFT);
    }
    // ... rest of the helper methods
}
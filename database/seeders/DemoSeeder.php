<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Generator;
use App\Models\User;
use App\Models\Rekanan;
use App\Models\Farm;
use App\Models\Coop;
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
use App\Models\Feed;
use App\Models\Ternak;
use App\Models\TransaksiTernak;
use App\Models\StandarBobot;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use App\Models\Partner;
use App\Models\Unit;
use App\Models\UnitConversion;

use Faker\Factory as Faker;

class DemoSeeder extends Seeder
{
    public function run(Generator $faker)
    {
        // Create item categories and items (unchanged)
        $this->createItemCategoriesAndItems($faker);

        // Run OVK Seeder
        $this->call([
            SupplyCategorySeeder::class,
            UnitSeeder::class,
            OVKSeeder::class,
            StrainSeeder::class,
            WorkerSeeder::class,
        ]);

        // Add this line to create doc items
        $this->createDoc();

        // Add this line to create pakan items
        $this->createPakan();

        // Create Rekanan records
        $this->createRekanan($faker);

        // Create the specific Demo Farm
        $this->createDemoFarms($faker);

        // Add this line to create item location mappings before purchases
        $this->createItemLocationMappings();

        // Assign Farm Operator
        $this->createFarmOperator();

        // Now create purchases for each farm (Adjust if needed for only Demo Farm)
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
            $category = ItemCategory::create(array_merge($data, ['status' => 'active', 'created_by' => $supervisor->id]));

            // Item::create([
            //     'category_id' => $category->id,
            //     'kode' => $category->code . '001',
            //     'name' => 'Nama Stok ' . $category->name,
            //     'satuan_besar' => $this->getSatuanBesar($category->name),
            //     'satuan_kecil' => $this->getSatuanKecil($category->name),
            //     'konversi' => $category->name === 'Pakan' ? 1000 : 1,
            //     'status' => 'active',
            //     'is_feed' => $category->name === 'Pakan',
            //     'created_by' => $supervisor->id,
            // ]);
        }
    }

    private function createDoc()
    {
        $pakanTypes = [
            ['name' => 'Grade 1', 'code' => 'grade1'],
            ['name' => 'Grade 2', 'code' => 'grade2'],
            ['name' => 'Grade 3', 'code' => 'grade3'],
            ['name' => 'Cobb', 'code' => 'cobb'],
            ['name' => 'Ross', 'code' => 'ross'],

        ];

        $supervisor = User::where('email', 'supervisor@demo.com')->first();
        $pakanCategory = ItemCategory::where('name', 'DOC')->first();

        if (!$pakanCategory) {
            $pakanCategory = ItemCategory::create([
                'name' => 'Pakan',
                'code' => 'PKN',
                'description' => 'Feed Category',
                'status' => 'active',
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
                'status' => 'active',
                'is_feed' => false,
                'created_by' => $supervisor->id,
            ]);
        }
    }

    private function createPakan()
    {
        $pakanTypes = [
            ['name' => 'SP 10', 'code' => 'SP10'],
            ['name' => 'S 11', 'code' => 'S11'],
            ['name' => 'S 12', 'code' => 'S12'],
        ];

        $supervisor = User::where('email', 'supervisor@demo.com')->first();
        $unitKg = Unit::where('name', 'KG')->first();
        $unitSak = Unit::where('name', 'SAK')->first();

        foreach ($pakanTypes as $type) {
            $feed = Feed::create([
                'code' => $type['code'],
                'name' => $type['name'],
                'data' => [
                    'unit_id' => $unitKg->id,
                    'unit_details' => [
                        'id' => $unitKg->id,
                        'name' => $unitKg->name,
                        'description' => $unitKg->description,
                    ],
                    'conversion_units' => [
                        [
                            'unit_id' => $unitKg->id,
                            'unit_name' => $unitKg->name,
                            'value' => 1,
                            'is_default_purchase' => true,
                            'is_default_mutation' => true,
                            'is_default_sale' => true,
                            'is_smallest' => true,
                        ],
                        [
                            'unit_id' => $unitSak->id,
                            'unit_name' => $unitSak->name,
                            'value' => 50, // 1 SAK = 50 KG
                            'is_default_purchase' => false,
                            'is_default_mutation' => false,
                            'is_default_sale' => false,
                            'is_smallest' => false,
                        ]
                    ],
                ],
                'created_by' => $supervisor->id,
            ]);

            // KG -> KG
            UnitConversion::updateOrCreate(
                [
                    'type' => 'Feed',
                    'item_id' => $feed->id,
                    'unit_id' => $unitKg->id,
                    'conversion_unit_id' => $unitKg->id,
                ],
                [
                    'conversion_value' => 1,
                    'default_purchase' => true,
                    'default_mutation' => true,
                    'default_sale' => true,
                    'smallest' => true,
                    'created_by' => $supervisor->id,
                ]
            );

            // SAK -> KG
            UnitConversion::updateOrCreate(
                [
                    'type' => 'Feed',
                    'item_id' => $feed->id,
                    'unit_id' => $unitKg->id,
                    'conversion_unit_id' => $unitSak->id,
                ],
                [
                    'conversion_value' => 50, // 1 SAK = 50 KG
                    'default_purchase' => false,
                    'default_mutation' => false,
                    'default_sale' => false,
                    'smallest' => false,
                    'created_by' => $supervisor->id,
                ]
            );
        }
    }

    // private function createPakan()
    // {
    //     $pakanTypes = [
    //         ['name' => 'SP 10', 'code' => 'SP10'],
    //         ['name' => 'S 11', 'code' => 'S11'],
    //         ['name' => 'S 12', 'code' => 'S12'],
    //     ];

    //     $supervisor = User::where('email', 'supervisor@demo.com')->first();
    //     $unit = Unit::where('name','KG')->first();
    //     $unitSak = Unit::where('name','SAK')->first();

    //     foreach ($pakanTypes as $type){
    //         $feed=Feed::create([
    //                 'code' => $type['code'],
    //                 'name' => $type['name'],
    //                 'payload' => [
    //                         'unit_id' => $unit->id,
    //                         'unit_details' => [
    //                             'id' => $unit->id, // Sesuaikan jika ada ID lain yang relevan
    //                             'name' => $unit->name, // Atau $unitKg->name jika yakin ada
    //                             'description' => $unit->description, // Deskripsi sesuai kebutuhan
    //                         ],
    //                         'conversion_units' => [
    //                             [
    //                                 'unit_id' => $unit->id,
    //                                 'unit_name' => $unit->name,
    //                                 'value' => 1,
    //                                 'is_default_purchase' => true,
    //                                 'is_default_mutation' => true,
    //                                 'is_default_sale' => true,
    //                                 'is_smallest' => true,
    //                             ],   
    //                         ],
    //                 ],
    //                 'created_by' => $supervisor->id,
    //             ]);

    //             UnitConversion::updateOrCreate(
    //                 [
    //                     'type' => 'Feed',
    //                     'item_id' => $feed->id,
    //                     'unit_id' => $unit->id,
    //                     'conversion_unit_id' => $unit->id,
    //                 ],
    //                 [
    //                     'conversion_value' => 1,
    //                     'default_purchase' => true,
    //                     'default_mutation' => true,
    //                     'default_sale' => true,
    //                     'smallest' => true,
    //                     'created_by' => $supervisor->id,
    //                 ]
    //             );
    //     }
    // }

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
                'status' => 'active',
                'created_by' => $supervisor->id,
            ]);

            // Create kandang
            Coop::factory()->create([
                'farm_id' => $farm->id,
                'code' => 'K00' . $farm->kode,
                'name' => 'Kandang-F' . $farm->kode,
            ]);
        });
    }

    private function createDemoFarms(Generator $faker)
    {
        $demoSupervisors = [
            [
                'email'    => 'supervisor@demo.com',
                'kode'     => 'DF01',
                'farmName' => 'Demo Farm',
            ],
            [
                'email'    => 'supervisor@demo2.com',
                'kode'     => 'DF02',
                'farmName' => 'Demo Farm 2',
            ],
        ];

        foreach ($demoSupervisors as $data) {
            $supervisor = User::where('email', $data['email'])->first();

            if (!$supervisor) {
                continue; // Skip if supervisor not found
            }

            // Create the Demo Farm
            $farm = Farm::create([
                'code'       => $data['kode'],
                'name'       => $data['farmName'],
                'address'     => $faker->address,
                'status'     => 'active',
                'created_by' => $supervisor->id,
                'updated_by' => $supervisor->id,
            ]);

            // Create warehouse location for the farm
            InventoryLocation::create([
                'farm_id'    => $farm->id,
                'name'       => 'Gudang ' . $data['farmName'],
                'code'       => 'WH-' . $data['kode'],
                'type'       => 'warehouse',
                'status'     => 'active',
                'created_by' => $supervisor->id,
            ]);

            // Create two Kandang for each farm
            for ($i = 1; $i <= 2; $i++) {
                Coop::create([
                    'farm_id'    => $farm->id,
                    'code'       => "K0{$i}-" . $data['kode'],
                    'name'       => "Kandang {$i} - " . $data['farmName'],
                    'capacity'  => 20000,
                    'status'     => 'active',
                    'created_by' => $supervisor->id,
                ]);
            }
        }
    }


    private function createFarmOperator()
    {
        Farm::where('status', 'active')->each(function ($farm) {
            $supervisor = User::where('email', 'supervisor@demo.com')->first();
            $operator = User::where('email', 'operator@demo.com')->first();

            // Assign operator
            $farm->operators()->attach($operator);
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
            $kandang = Coop::where('farm_id', $farm->id)->first();

            // Create DOC purchase
            $this->createDocPurchase($farm, $kandang, $warehouseLocation, $supervisor, $faker);

            // Create inventory purchase
            $this->createInventoryPurchase($farm, $kandang, $warehouseLocation, $operator, $faker);
        });
    }

    private function getSatuanBesar($categoryName)
    {
        return match ($categoryName) {
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
        return match ($categoryName) {
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
        // Get admin user as primary user for created_by
        $adminUser = User::where('email', 'admin@peternakan.digital')->first();
        if (!$adminUser) {
            $adminUser = User::first(); // Fallback to any user
        }

        $farms = Farm::all();
        $warehouseLocation = InventoryLocation::where('type', 'warehouse')->first();

        foreach ($farms as $farm) {
            $items = Item::all();

            foreach ($items as $item) {
                // Create or update the item location mapping
                ItemLocation::updateOrCreate(
                    [
                        'item_id' => $item->id,
                        'farm_id' => $farm->id,
                    ],
                    [
                        'location_id' => $warehouseLocation->id,
                        'created_by' => $adminUser ? $adminUser->id : null,
                        'updated_by' => $adminUser ? $adminUser->id : null,
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
        $faker = Faker::create('id_ID'); // Sesuaikan dengan locale yang Anda inginkan

        // Get admin user as primary user for created_by
        $adminUser = User::where('email', 'admin@peternakan.digital')->first();
        if (!$adminUser) {
            $adminUser = User::first(); // Fallback to any user
        }

        foreach ($rekananTypes as $type) {
            for ($i = 0; $i < 5; $i++) {
                Partner::create([
                    'type' => $type,
                    'code' => 'REK-' . $type[0] . '-' . str_pad(Partner::count() + 1, 3, '0', STR_PAD_LEFT),
                    'name' => $faker->company,
                    'address' => $faker->address,
                    'phone_number' => $faker->phoneNumber,
                    'contact_person' => $faker->name,
                    'phone_number' => $faker->phoneNumber,
                    'email' => $faker->companyEmail,
                    'status' => 'active',
                    'created_by' => $adminUser ? $adminUser->id : null,
                ]);
            }
        }
    }

    private function createDocPurchase($farm, $kandang, $location, $supervisor, $faker)
    {
        if ($supervisor) {
            // Get admin user as primary user for created_by
            $adminUser = User::where('email', 'admin@peternakan.digital')->first();
            if (!$adminUser) {
                $adminUser = User::first(); // Fallback to any user
            }

            $supplier = Partner::where('type', 'Supplier')->inRandomOrder()->first();
            $docItem = Item::whereHas('category', function ($q) {
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
                'coop_id' => $kandang->id,
                'total_qty' => $qty,
                'total_berat' => $qty * 0.1, // Assuming 100g per DOC
                'harga' => $harga,
                'sub_total' => $qty * $harga,
                'terpakai' => 0,
                'sisa' => $qty,
                'notes' => 'Initial DOC Purchase',
                'status' => 'active',
                'created_by' => $adminUser ? $adminUser->id : null,
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
                'status' => 'active',
                'created_by' => $adminUser ? $adminUser->id : null,
            ]);

            // Create kelompok ternak
            $ternak = Ternak::create([
                'transaksi_id' => $purchase->id,
                'name' => 'PR-' . $farm->kode . '-' . $kandang->kode . '-' . Carbon::parse($purchase->tanggal)->format('dmY'),
                'breed' => 'DOC',
                'start_date' => $purchase->tanggal,
                'populasi_awal' => $qty,
                'berat_awal' => $qty * 0.1,
                'hpp' => $harga,
                'status' => 'active',
                'keterangan' => 'Initial DOC Purchase',
                'created_by' => $adminUser ? $adminUser->id : null,
            ]);

            // Add new code: Create transaksi_ternak record
            TransaksiTernak::create([
                'kelompok_ternak_id' => $ternak->id,
                'jenis_transaksi' => 'Pembelian',
                'tanggal' => $purchase->tanggal,
                'farm_id' => $farm->id,
                'coop_id' => $kandang->id,
                'quantity' => $qty,
                'berat_total' => $qty * 0.1,
                'berat_rata' => 0.1, // DOC average weight
                'harga_satuan' => $harga,
                'total_harga' => $qty * $harga,
                'status' => 'active',
                'keterangan' => 'Pembelian DOC Batch ' . $ternak->name,
                'created_by' => $adminUser ? $adminUser->id : null,
            ]);

            // Update purchase with kelompok_ternak_id
            $purchase->update([
                'kelompok_ternak_id' => $ternak->id
            ]);

            // Create current ternak record
            CurrentTernak::create([
                'kelompok_ternak_id' => $ternak->id,
                'farm_id' => $farm->id,
                'coop_id' => $kandang->id,
                'quantity' => $qty,
                'berat_total' => $qty * 0.1,
                'avg_berat' => 0.1,
                'status' => 'active',
                'created_by' => $adminUser ? $adminUser->id : null,
            ]);

            // Update kandang
            $kandang->update([
                'jumlah' => $qty,
                'berat' => $qty * 0.1,
                'kelompok_ternak_id' => $ternak->id,
                'status' => 'Digunakan',
                'updated_by' => $adminUser ? $adminUser->id : null,
            ]);
        }
    }

    private function createInventoryPurchase($farm, $kandang, $warehouse, $operator, $faker)
    {
        // Get a random supplier
        $supplier = Partner::where('type', 'Supplier')->inRandomOrder()->first();

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
            'coop_id' => $kandang->id,
            'total_qty' => $qty,
            'total_berat' => $qty * ($item->is_feed ? 1 : 0.1), // Assume 1kg for feed, 100g for others
            'harga' => $harga,
            'sub_total' => $qty * $harga,
            'terpakai' => 0,
            'sisa' => $qty,
            'notes' => 'Purchase of ' . $item->name,
            'status' => 'active',
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
            'status' => 'active',
            'created_by' => $operator->id,
        ]);



        // Create inventory stock
        $currentStock = CurrentStock::create([
            'item_id' => $item->id,
            'location_id' => $warehouse->id,
            'quantity' => $qty,
            'reserved_quantity' => 0,
            'available_quantity' => $qty,
            'hpp' => $harga,
            'status' => 'active',
            'created_by' => $operator->id,
        ]);

        // Create stock movement record
        StockMovement::create([
            'transaksi_id' => $purchase->id,
            'item_id' => $item->id,
            'destination_location_id' => $warehouse->id,
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

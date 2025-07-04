<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Menu;
// use Spatie\Permission\Models\Role;
// use Spatie\Permission\Models\Permission;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MenuSeeder extends Seeder
{
    public function run()
    {
        // Get admin user as primary user for created_by
        $adminUser = User::where('email', 'admin@peternakan.digital')->first();
        if (!$adminUser) {
            $adminUser = User::first(); // Fallback to any user
        }

        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Clear existing menus and related data
        DB::table('menu_permission')->truncate();
        DB::table('menu_role')->truncate();
        DB::table('menus')->truncate();

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Create main menu items
        $dashboard = Menu::create([
            'name' => 'dashboard',
            'label' => 'Dashboard',
            'route' => '/',
            'icon' => 'fa-solid fa-house',
            'location' => 'sidebar',
            'order_number' => 1,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        // Master Data Menu
        $masterData = Menu::create([
            'name' => 'master-data',
            'label' => 'Master Data',
            'route' => '#',
            'icon' => 'fa-solid fa-database',
            'location' => 'sidebar',
            'order_number' => 2,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        // Master Data Submenu Items
        $farm = Menu::create([
            'parent_id' => $masterData->id,
            'name' => 'farm',
            'label' => 'Farm',
            'route' => '/master/farms',
            'icon' => 'fa-solid fa-farm',
            'location' => 'sidebar',
            'order_number' => 1,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        $kandang = Menu::create([
            'parent_id' => $masterData->id,
            'name' => 'kandang',
            'label' => 'Kandang',
            'route' => '/master/kandangs',
            'icon' => 'fa-solid fa-truck',
            'location' => 'sidebar',
            'order_number' => 2,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        $supplier = Menu::create([
            'parent_id' => $masterData->id,
            'name' => 'supplier',
            'label' => 'Supplier',
            'route' => '/master/suppliers',
            'icon' => 'fa-solid fa-user-plus',
            'location' => 'sidebar',
            'order_number' => 3,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        $customer = Menu::create([
            'parent_id' => $masterData->id,
            'name' => 'customer',
            'label' => 'Pembeli',
            'route' => '/master/customers',
            'icon' => 'fa-solid fa-user-plus',
            'location' => 'sidebar',
            'order_number' => 4,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        $expedition = Menu::create([
            'parent_id' => $masterData->id,
            'name' => 'expedition',
            'label' => 'Ekspedisi',
            'route' => '/master/expeditions',
            'icon' => 'fa-solid fa-truck',
            'location' => 'sidebar',
            'order_number' => 5,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        $unit = Menu::create([
            'parent_id' => $masterData->id,
            'name' => 'unit',
            'label' => 'Unit Satuan',
            'route' => '/master/units',
            'icon' => 'fa-solid fa-ruler',
            'location' => 'sidebar',
            'order_number' => 6,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        $feed = Menu::create([
            'parent_id' => $masterData->id,
            'name' => 'feed',
            'label' => 'Pakan',
            'route' => '/master/feeds',
            'icon' => 'fa-solid fa-wheat-awn',
            'location' => 'sidebar',
            'order_number' => 7,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        $supply = Menu::create([
            'parent_id' => $masterData->id,
            'name' => 'supply',
            'label' => 'Supply',
            'route' => '/master/supplies',
            'icon' => 'fa-solid fa-box',
            'location' => 'sidebar',
            'order_number' => 8,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        $worker = Menu::create([
            'parent_id' => $masterData->id,
            'name' => 'worker',
            'label' => 'Pekerja',
            'route' => '/master/workers',
            'icon' => 'fa-solid fa-users',
            'location' => 'sidebar',
            'order_number' => 9,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        // Inventory Menu
        $inventory = Menu::create([
            'name' => 'inventory',
            'label' => 'Inventory',
            'route' => '#',
            'icon' => 'fa-solid fa-box',
            'location' => 'sidebar',
            'order_number' => 3,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        // Inventory Submenu Items
        $doc = Menu::create([
            'parent_id' => $inventory->id,
            'name' => 'doc',
            'label' => 'DOC',
            'route' => '/inventory/docs',
            'icon' => 'fa-solid fa-folder',
            'location' => 'sidebar',
            'order_number' => 1,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        $feedInventory = Menu::create([
            'parent_id' => $inventory->id,
            'name' => 'feed-inventory',
            'label' => 'Pakan',
            'route' => '/stocks/feed',
            'icon' => 'fa-solid fa-wheat-awn',
            'location' => 'sidebar',
            'order_number' => 2,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        $supplyInventory = Menu::create([
            'parent_id' => $inventory->id,
            'name' => 'supply-inventory',
            'label' => 'Supply',
            'route' => '/stocks/supply',
            'icon' => 'fa-solid fa-box',
            'location' => 'sidebar',
            'order_number' => 3,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        // User Management Menu
        $userManagement = Menu::create([
            'name' => 'user-management',
            'label' => 'User Management',
            'route' => '#',
            'icon' => 'fa-solid fa-users',
            'location' => 'sidebar',
            'order_number' => 4,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        // User Management Submenu Items
        $userList = Menu::create([
            'parent_id' => $userManagement->id,
            'name' => 'user-list',
            'label' => 'User List',
            'route' => '/users',
            'icon' => 'fa-solid fa-users',
            'location' => 'sidebar',
            'order_number' => 1,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        $userRole = Menu::create([
            'parent_id' => $userManagement->id,
            'name' => 'user-role',
            'label' => 'User Role',
            'route' => '/user/roles',
            'icon' => 'fa-solid fa-shield',
            'location' => 'sidebar',
            'order_number' => 2,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        $userPermission = Menu::create([
            'parent_id' => $userManagement->id,
            'name' => 'user-permission',
            'label' => 'User Permission',
            'route' => '/user/permissions',
            'icon' => 'fa-solid fa-lock',
            'location' => 'sidebar',
            'order_number' => 3,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        // Peternakan Menu
        $peternakan = Menu::create([
            'name' => 'peternakan',
            'label' => 'Peternakan',
            'route' => '#',
            'icon' => 'fa-solid fa-warehouse',
            'location' => 'sidebar',
            'order_number' => 5,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        // Peternakan Submenu Items
        $dataFarm = Menu::create([
            'parent_id' => $peternakan->id,
            'name' => 'data-farm',
            'label' => 'Data Farm',
            'route' => '/data/farms',
            'icon' => 'fa-solid fa-warehouse',
            'location' => 'sidebar',
            'order_number' => 1,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        $dataKandang = Menu::create([
            'parent_id' => $peternakan->id,
            'name' => 'data-kandang',
            'label' => 'Data Kandang',
            'route' => '/data/kandangs',
            'icon' => 'fa-solid fa-house',
            'location' => 'sidebar',
            'order_number' => 2,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        $dataLivestock = Menu::create([
            'parent_id' => $peternakan->id,
            'name' => 'data-livestock',
            'label' => 'Data Ternak',
            'route' => '/data/livestocks',
            'icon' => '/assets/media/icons/custom/chicken.png',
            'location' => 'sidebar',
            'order_number' => 3,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        $dataStandarBobot = Menu::create([
            'parent_id' => $peternakan->id,
            'name' => 'data-standar-bobot',
            'label' => 'Data Standar Bobot',
            'route' => '/data/standar-bobot',
            'icon' => 'fa-solid fa-weight-hanging',
            'location' => 'sidebar',
            'order_number' => 4,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        $dataAfkir = Menu::create([
            'parent_id' => $peternakan->id,
            'name' => 'data-afkir',
            'label' => 'Data Ternak Afkir',
            'route' => '/livestock/afkir',
            'icon' => 'fa-solid fa-ban',
            'location' => 'sidebar',
            'order_number' => 5,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        $dataJual = Menu::create([
            'parent_id' => $peternakan->id,
            'name' => 'data-jual',
            'label' => 'Data Ternak Jual',
            'route' => '/livestock/jual',
            'icon' => 'fa-solid fa-tags',
            'location' => 'sidebar',
            'order_number' => 6,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        $dataMati = Menu::create([
            'parent_id' => $peternakan->id,
            'name' => 'data-mati',
            'label' => 'Data Ternak Mati',
            'route' => '/livestock/mati',
            'icon' => 'fa-solid fa-skull',
            'location' => 'sidebar',
            'order_number' => 7,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        // Transaksi Menu
        $transaksi = Menu::create([
            'name' => 'transaksi',
            'label' => 'Transaksi',
            'route' => '#',
            'icon' => 'fa-solid fa-money-bill',
            'location' => 'sidebar',
            'order_number' => 6,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        // Transaksi Submenu Items
        $pembelianDoc = Menu::create([
            'parent_id' => $transaksi->id,
            'name' => 'pembelian-doc',
            'label' => 'Pembelian DOC',
            'route' => '/pembelian/doc',
            'icon' => 'fa-solid fa-cart-shopping',
            'location' => 'sidebar',
            'order_number' => 1,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        $pembelianPakan = Menu::create([
            'parent_id' => $transaksi->id,
            'name' => 'pembelian-pakan',
            'label' => 'Pembelian Pakan',
            'route' => '/transaction/feed',
            'icon' => 'fa-solid fa-cart-shopping',
            'location' => 'sidebar',
            'order_number' => 2,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        $pembelianOvk = Menu::create([
            'parent_id' => $transaksi->id,
            'name' => 'pembelian-ovk',
            'label' => 'Pembelian OVK',
            'route' => '/pembelian/ovk',
            'icon' => 'fa-solid fa-cart-shopping',
            'location' => 'sidebar',
            'order_number' => 3,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        $pembelianStock = Menu::create([
            'parent_id' => $transaksi->id,
            'name' => 'pembelian-stock',
            'label' => 'Pembelian Stock',
            'route' => '/transaction/supply',
            'icon' => 'fa-solid fa-cart-shopping',
            'location' => 'sidebar',
            'order_number' => 4,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        $penjualanTernak = Menu::create([
            'parent_id' => $transaksi->id,
            'name' => 'penjualan-ternak',
            'label' => 'Penjualan Ternak',
            'route' => '/transaction/sales',
            'icon' => 'fa-solid fa-tags',
            'location' => 'sidebar',
            'order_number' => 5,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        $pemakaianSupply = Menu::create([
            'parent_id' => $transaksi->id,
            'name' => 'pemakaian-supply',
            'label' => 'Pemakaian Supply',
            'route' => '/livestock/supply-recording',
            'icon' => 'fa-solid fa-tags',
            'location' => 'sidebar',
            'order_number' => 6,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        $mutasiFeed = Menu::create([
            'parent_id' => $transaksi->id,
            'name' => 'mutasi-feed',
            'label' => 'Mutasi Feed',
            'route' => '/feeds/mutation',
            'icon' => 'fa-solid fa-arrows-rotate',
            'location' => 'sidebar',
            'order_number' => 7,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        $mutasiSupply = Menu::create([
            'parent_id' => $transaksi->id,
            'name' => 'mutasi-supply',
            'label' => 'Mutasi Supply',
            'route' => '/supplies/mutation',
            'icon' => 'fa-solid fa-arrows-rotate',
            'location' => 'sidebar',
            'order_number' => 8,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        $mutasiAyam = Menu::create([
            'parent_id' => $transaksi->id,
            'name' => 'mutasi-ayam',
            'label' => 'Mutasi Ayam',
            'route' => '/livestock/mutasi',
            'icon' => 'fa-solid fa-arrows-rotate',
            'location' => 'sidebar',
            'order_number' => 9,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        // Reports Menu
        $reports = Menu::create([
            'name' => 'reports',
            'label' => 'Reports',
            'route' => '#',
            'icon' => 'fa-solid fa-chart-line',
            'location' => 'sidebar',
            'order_number' => 7,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        // Reports Submenu Items
        $reportHarian = Menu::create([
            'parent_id' => $reports->id,
            'name' => 'report-harian',
            'label' => 'Harian',
            'route' => '/reports/harian',
            'icon' => 'fa-solid fa-chart-line',
            'location' => 'sidebar',
            'order_number' => 1,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        $reportDailyCost = Menu::create([
            'parent_id' => $reports->id,
            'name' => 'report-daily-cost',
            'label' => 'Harian Biaya',
            'route' => '/reports/daily-cost',
            'icon' => 'fa-solid fa-chart-line',
            'location' => 'sidebar',
            'order_number' => 2,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        $reportPerforma = Menu::create([
            'parent_id' => $reports->id,
            'name' => 'report-performa',
            'label' => 'Performa',
            'route' => '/reports/performa',
            'icon' => 'fa-solid fa-chart-line',
            'location' => 'sidebar',
            'order_number' => 3,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        $reportPenjualan = Menu::create([
            'parent_id' => $reports->id,
            'name' => 'report-penjualan',
            'label' => 'Penjualan',
            'route' => '/reports/penjualan',
            'icon' => 'fa-solid fa-chart-line',
            'location' => 'sidebar',
            'order_number' => 4,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        $reportFeedPurchase = Menu::create([
            'parent_id' => $reports->id,
            'name' => 'report-feed-purchase',
            'label' => 'Pembelian Pakan',
            'route' => '/reports/feed/purchase',
            'icon' => 'fa-solid fa-chart-line',
            'location' => 'sidebar',
            'order_number' => 5,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        $reportPerformaMitra = Menu::create([
            'parent_id' => $reports->id,
            'name' => 'report-performa-mitra',
            'label' => 'Performa Kemitraan',
            'route' => '/reports/performa-mitra',
            'icon' => 'fa-solid fa-chart-line',
            'location' => 'sidebar',
            'order_number' => 6,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        // Administrator Menu
        $administrator = Menu::create([
            'name' => 'administrator',
            'label' => 'Administrator',
            'route' => '#',
            'icon' => 'fa-solid fa-gear',
            'location' => 'sidebar',
            'order_number' => 8,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        // Administrator Submenu Items
        $qa = Menu::create([
            'parent_id' => $administrator->id,
            'name' => 'qa',
            'label' => 'QA',
            'route' => '/administrator/qa',
            'icon' => 'fa-solid fa-chart-line',
            'location' => 'sidebar',
            'order_number' => 1,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        $routes = Menu::create([
            'parent_id' => $administrator->id,
            'name' => 'routes',
            'label' => 'Routes',
            'route' => '/administrator/routes',
            'icon' => 'fa-solid fa-chart-line',
            'location' => 'sidebar',
            'order_number' => 2,
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null
        ]);

        // Attach roles and permissions
        $superAdminRole = Role::where('name', 'SuperAdmin')->first();
        $adminRole = Role::where('name', 'Administrator')->first();
        $operatorRole = Role::where('name', 'Operator')->first();
        $managerRole = Role::where('name', 'Manager')->first();
        $supervisorRole = Role::where('name', 'Supervisor')->first();
        $qaTesterRole = Role::where('name', 'QA Tester')->first();

        // Attach roles to menus based on configuration
        $this->attachRolesToMenu($dashboard, [$superAdminRole, $adminRole, $operatorRole, $managerRole, $supervisorRole, $qaTesterRole]);
        $this->attachRolesToMenu($masterData, [$superAdminRole, $adminRole, $qaTesterRole]);
        $this->attachRolesToMenu($inventory, [$adminRole, $operatorRole]);
        $this->attachRolesToMenu($userManagement, [$superAdminRole, $adminRole]);
        $this->attachRolesToMenu($peternakan, [$managerRole, $supervisorRole, $operatorRole]);
        $this->attachRolesToMenu($administrator, [$superAdminRole, $qaTesterRole]);

        // Attach specific permissions to menus
        $this->attachPermissionsToMenu($farm, ['access farm master data', 'read farm master data']);
        $this->attachPermissionsToMenu($kandang, ['access kandang management', 'read kandang management']);
        $this->attachPermissionsToMenu($supplier, ['read supplier management']);
        $this->attachPermissionsToMenu($customer, ['read customer management']);
        $this->attachPermissionsToMenu($userList, ['read user management']);
        $this->attachPermissionsToMenu($userRole, ['read user management']);
        $this->attachPermissionsToMenu($userPermission, ['SuperAdmin']);
    }

    private function attachRolesToMenu($menu, $roles)
    {
        foreach ($roles as $role) {
            if ($role) {
                $menu->roles()->attach($role->id);
            }
        }
    }

    private function attachPermissionsToMenu($menu, $permissions)
    {
        foreach ($permissions as $permission) {
            $perm = Permission::where('name', $permission)->first();
            if ($perm) {
                $menu->permissions()->attach($perm->id);
            }
        }
    }
}

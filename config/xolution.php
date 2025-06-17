<?php

return [

    'ALLOW_NEGATIF_SELLING' => true,
    'ALLOW_ROUNDUP_PRICE' => true,

    'TERNAK' => [
        'UMUR_JUAL_MIN' => 30,
    ],

    'APPS' => [
        'NAME' => 'Xistem Monitoring Peternakan',
        'Tag' => 'Xistem Monitoring Peternakan',
        'Version' => 'V1.2.1',
    ],

    // Dipakai untuk menentukan tipe unit yang akan ditampilkan di dropdown type pada model Unit Satuan
    'unit_type' => [
        'Alat' => 'Alat',
        'Berat' => 'Berat',
        'Lainnya' => 'Lainnya',
        'Obat' => 'Obat',
        'Panjang' => 'Panjang',
        'Umum' => 'Umum',
        'Volume' => 'Volume',
    ],

    // Dipakai untuk menentukan role yang akan ditampilkan di dropdown role pada model CompanyRole
    'company_roles' => [
        'Administrator' => 'Administrator',
        'Manager' => 'Manager',
        'Supervisor' => 'Supervisor',
        'Operator' => 'Operator',
    ],

    'company_role_descriptions' => [
        'Administrator' => 'Administrator bertugas untuk mengelola master data, user, dan role',
        'Manager' => 'Manager hanya bisa melihat semua jenis transaksi dan data yang ada',
        'Supervisor' => 'Supervisor bertugas untuk melakukan verifikasi data pembelian, pemakaian supply, dan pembelian pakan, dan juga pencatatan harian ayam',
        'Operator' => 'Operator bertugas untuk melakukan pencatatan harian ayam, pembelian pakan, dan pemakaian supply, dan pembelian ayam',
    ],

    'menu' => [
        'General' => [
            'order' => 1,
            'show' => true,
            'items' => [
                [
                    'route' => '/',
                    'label' => 'Dashboard',
                    'icon' => 'fa-solid fa-house',
                    'active' => 'callback', // Using string marker instead of function
                    'order' => 1,
                    'show' => true,
                    'show_without_category' => true, // Tampilkan tanpa kategori
                ],
            ],
        ],
        'Master Data' => [
            'order' => 2,
            'show' => true,
            // 'roles' => ['SuperAdmin', 'Administrator', 'QA Tester'],
            'can' => ['access master data'],
            'items' => [
                [
                    'route' => '/master/farms',
                    'label' => 'Farm',
                    'icon' => 'fa-solid fa-farm',
                    'active' => 'master/farms', // Using route string instead of function
                    // 'roles' => ['SuperAdmin', 'Administrator', 'QA Tester'],
                    'order' => 1,
                    'show' => true,
                    'can' => ['access farm management'],
                ],
                [
                    'route' => '/master/kandangs',
                    'label' => 'Kandang',
                    'icon' => 'fa-solid fa-truck',
                    'active' => 'master/kandangs', // Using route string instead of function
                    'roles' => ['SuperAdmin', 'Administrator', 'QA Tester'],
                    'order' => 1,
                    'show' => true,
                ],
                [
                    'route' => '/master/suppliers',
                    'label' => 'Supplier',
                    'icon' => 'fa-solid fa-user-plus',
                    'can' => 'read supplier management',
                    'order' => 1,
                    'show' => true,
                ],
                [
                    'route' => '/master/customers',
                    'label' => 'Pembeli',
                    'icon' => 'fa-solid fa-user-plus',
                    'can' => 'read customer management',
                    'order' => 2,
                    'show' => true,
                ],
                [
                    'route' => '/master/expeditions',
                    'label' => 'Ekspedisi',
                    'icon' => 'fa-solid fa-truck',
                    'active' => 'master/expeditions', // Using route string instead of function
                    'roles' => ['SuperAdmin', 'Administrator', 'QA Tester'],
                    'order' => 1,
                    'show' => true,
                ],
                [
                    'route' => '/master/units',
                    'label' => 'Unit Satuan',
                    'icon' => 'fa-solid fa-ruler',
                    'active' => 'master/units', // Using route string instead of function
                    'roles' => ['SuperAdmin', 'Administrator', 'QA Tester'],
                    'order' => 2,
                    'show' => true,
                ],
                [
                    'route' => '/master/feeds',
                    'label' => 'Pakan',
                    'icon' => 'fa-solid fa-wheat-awn',
                    'active' => 'master/feeds', // Using route string instead of function
                    'roles' => ['Administrator', 'Operator'],
                    'order' => 3,
                    'show' => true,
                ],
                [
                    'route' => '/master/supplies',
                    'label' => 'Supply',
                    'icon' => 'fa-solid fa-box',
                    'active' => 'master/supplies', // Using route string instead of function
                    'roles' => ['SuperAdmin', 'Administrator', 'QA Tester'],
                    'order' => 4,
                    'show' => true,
                ],
                [
                    'route' => '/master/workers',
                    'label' => 'Pekerja',
                    'icon' => 'fa-solid fa-users',
                    'active' => 'master/workers', // Using route string instead of function
                    'roles' => ['SuperAdmin', 'Administrator', 'QA Tester'],
                    'order' => 5,
                    'show' => true,
                ],
            ],
        ],
        'Rekanan' => [
            'order' => 3,
            'show' => false,
            'items' => [
                [
                    'route' => '/rekanan/suppliers',
                    'label' => 'Supplier',
                    'icon' => 'fa-solid fa-user-plus',
                    'can' => 'read supplier management',
                    'order' => 1,
                    'show' => true,
                ],
                [
                    'route' => '/rekanan/customers',
                    'label' => 'Pembeli',
                    'icon' => 'fa-solid fa-user-plus',
                    'can' => 'read customer management',
                    'order' => 2,
                    'show' => true,
                ],
                [
                    'route' => '/rekanan/ekspedisis',
                    'label' => 'Ekspedisi',
                    'icon' => 'fa-solid fa-truck',
                    'can' => 'read ekspedisi',
                    'order' => 3,
                    'show' => true,
                ],
            ],
        ],
        'Inventory' => [
            'order' => 4,
            'show' => true,
            'roles' => ['Administrator', 'Operator'],
            'items' => [
                [
                    'route' => '/inventory/docs',
                    'label' => 'DOC',
                    'icon' => 'fa-solid fa-folder',
                    'roles' => ['SuperAdmin', 'Administrator', 'QA Tester'],
                    'order' => 1,
                    'show' => true,
                ],
                [
                    'route' => '/stocks/feed',
                    'label' => 'Pakan',
                    'icon' => 'fa-solid fa-wheat-awn',
                    'roles' => ['Operator'],
                    'order' => 2,
                    'show' => true,
                ],
                [
                    'route' => '/stocks/supply',
                    'label' => 'Supply',
                    'icon' => 'fa-solid fa-box',
                    'roles' => ['Operator'],
                    'order' => 3,
                    'show' => true,
                ],
            ],
        ],
        'User Management' => [
            'order' => 5,
            'show' => true,
            'items' => [
                [
                    'route' => '/users',
                    'label' => 'User List',
                    'icon' => 'fa-solid fa-users',
                    'can' => 'read user management',
                    'order' => 1,
                    'show' => true,
                ],
                [
                    'route' => '/user/roles',
                    'label' => 'User Role',
                    'icon' => 'fa-solid fa-shield',
                    'can' => 'read user management',
                    'order' => 2,
                    'show' => true,
                ],
                [
                    'route' => '/user/permissions',
                    'label' => 'User Permission',
                    'icon' => 'fa-solid fa-lock',
                    'can' => 'SuperAdmin',
                    'order' => 3,
                    'show' => true,
                ],
            ],
        ],
        'Peternakan' => [
            'order' => 6,
            'show' => true,
            'roles' => ['Manager', 'Supervisor', 'Operator'],
            'items' => [
                [
                    'route' => '/data/farms',
                    'label' => 'Data Farm',
                    'icon' => 'fa-solid fa-warehouse',
                    'active' => ['data/farms', 'master/farms'], // Using array of routes
                    'roles' => ['SuperAdmin', 'Administrator', 'QA Tester'],
                    'order' => 1,
                    'show' => true,
                ],
                [
                    'route' => '/data/kandangs',
                    'label' => 'Data Kandang',
                    'icon' => 'fa-solid fa-house',
                    'active' => ['data/kandangs', 'master/kandangs'], // Using array of routes
                    'order' => 2,
                    'show' => true,
                    'roles' => ['SuperAdmin', 'Administrator', 'QA Tester'],
                ],
                [
                    'route' => '/data/livestocks',
                    'label' => 'Data Ternak', // Removed the function call
                    'icon' => '/assets/media/icons/custom/chicken.png',
                    'active' => ['data/livestocks', 'master/livestocks'], // Using array of routes
                    'order' => 3,
                    'show' => true,
                ],
                [
                    'route' => '/data/standar-bobot',
                    'label' => 'Data Standar Bobot',
                    'icon' => 'fa-solid fa-weight-hanging',
                    'active' => 'data/standar-bobot', // Using route string instead of function
                    'order' => 4,
                    'show' => true,
                    'roles' => ['Admin'],
                ],
                [
                    'route' => '/livestock/afkir',
                    'label' => 'Data Ternak Afkir', // Removed the function call
                    'icon' => 'fa-solid fa-ban',
                    'order' => 5,
                    'show' => true,
                ],
                [
                    'route' => '/livestock/jual',
                    'label' => 'Data Ternak Jual', // Removed the function call
                    'icon' => 'fa-solid fa-tags',
                    'order' => 6,
                    'show' => true,
                ],
                [
                    'route' => '/livestock/mati',
                    'label' => 'Data Ternak Mati', // Removed the function call
                    'icon' => 'fa-solid fa-skull',
                    'order' => 7,
                    'show' => true,
                ],
            ],
        ],
        'Transaksi' => [
            'order' => 7,
            'show' => true,
            'items' => [
                [
                    'route' => '/pembelian/doc',
                    'label' => 'Pembelian DOC',
                    'icon' => 'fa-solid fa-cart-shopping',
                    'active' => 'pembelian/doc', // Using route string instead of function
                    'roles' => ['Supervisor', 'Manager'],
                    'order' => 1,
                    'show' => true,
                ],
                [
                    'route' => '/transaction/feed',
                    'label' => 'Pembelian Pakan',
                    'icon' => 'fa-solid fa-cart-shopping',
                    'active' => 'transaction/feed', // Using route string instead of function
                    'roles' => ['Operator'],
                    'order' => 2,
                    'show' => true,
                ],
                [
                    'route' => '/pembelian/ovk',
                    'label' => 'Pembelian OVK',
                    'icon' => 'fa-solid fa-cart-shopping',
                    'active' => 'pembelian/ovk', // Using route string instead of function
                    'roles' => ['Admin'],
                    'order' => 3,
                    'show' => true,
                ],
                [
                    'route' => '/transaction/supply',
                    'label' => 'Pembelian Stock',
                    'icon' => 'fa-solid fa-cart-shopping',
                    'active' => ['transaction/stock', 'transaction/stoks'], // Using array of routes
                    'roles' => ['Operator'],
                    'order' => 4,
                    'show' => true,
                ],
                [
                    'route' => '/transaction/sales',
                    'label' => 'Penjualan Ternak', // Removed the function call
                    'icon' => 'fa-solid fa-tags',
                    'active' => 'transaction/sales', // Using route string instead of function
                    'order' => 5,
                    'show' => true,
                ],
                [
                    'route' => '/livestock/supply-recording',
                    'label' => 'Pemakaian Supply', // Removed the function call
                    'icon' => 'fa-solid fa-tags',
                    'active' => '/livestock/supply-recording', // Using route string instead of function
                    'order' => 5,
                    'show' => true,
                ],
                [
                    'route' => '/feeds/mutation',
                    'label' => 'Mutasi Feed',
                    'icon' => 'fa-solid fa-arrows-rotate',
                    'order' => 6,
                    'show' => true,
                ],
                [
                    'route' => '/supplies/mutation',
                    'label' => 'Mutasi Supply',
                    'icon' => 'fa-solid fa-arrows-rotate',
                    'order' => 7,
                    'show' => true,
                ],
                [
                    'route' => '/livestock/mutasi',
                    'label' => 'Mutasi Ayam',
                    'icon' => 'fa-solid fa-arrows-rotate',
                    'order' => 8,
                    'show' => true,
                ],
            ],
        ],
        'Reports' => [
            'order' => 8,
            'show' => false,
            'items' => [
                [
                    'route' => '/reports/harian',
                    'label' => 'Harian',
                    'icon' => 'fa-solid fa-chart-line',
                    'active' => 'reports/harian', // Using route string instead of function
                    'order' => 1,
                    'show' => true,
                ],
                [
                    'route' => '/reports/daily-cost',
                    'label' => 'Harian Biaya',
                    'icon' => 'fa-solid fa-chart-line',
                    'active' => 'reports/daily-cost', // Using route string instead of function
                    'order' => 2,
                    'show' => true,
                ],
                [
                    'route' => '/reports/performa',
                    'label' => 'Performa',
                    'icon' => 'fa-solid fa-chart-line',
                    'active' => 'reports/performa', // Using route string instead of function
                    'order' => 3,
                    'show' => true,
                ],
                [
                    'route' => '/reports/penjualan',
                    'label' => 'Penjualan',
                    'icon' => 'fa-solid fa-chart-line',
                    'active' => 'reports/penjualan', // Using route string instead of function
                    'order' => 4,
                    'show' => true,
                ],
                [
                    'route' => '/reports/feed/purchase',
                    'label' => 'Pembelian Pakan',
                    'icon' => 'fa-solid fa-chart-line',
                    'active' => 'reports/feed/purchase', // Using route string instead of function
                    'order' => 5,
                    'show' => true,
                ],
                [
                    'route' => '/reports/performa-mitra',
                    'label' => 'Performa Kemitraan',
                    'icon' => 'fa-solid fa-chart-line',
                    'active' => 'reports/performa-mitra', // Using route string instead of function
                    'order' => 6,
                    'show' => true,
                ],
            ],
        ],
        'Administrator' => [
            'order' => 9,
            'show' => true,
            'roles' => ['SuperAdmin', 'QA Tester'],
            'items' => [
                [
                    'route' => '/administrator/qa',
                    'label' => 'QA',
                    'icon' => 'fa-solid fa-chart-line',
                    'active' => '/administrator/qa', // Using route string instead of function
                    'order' => 1,
                    'roles' => ['SuperAdmin', 'QA Tester'],
                    'show' => true,
                ],
                [
                    'route' => '/administrator/routes',
                    'label' => 'Routes',
                    'icon' => 'fa-solid fa-chart-line',
                    'active' => '/administrator/routes', // Using route string instead of function
                    'order' => 2,
                    'roles' => ['SuperAdmin', 'QA Tester'],
                    'show' => true,
                ],
            ],
        ],
    ],

];

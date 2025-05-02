@php
$currentUrl = request()->url();
$isDashboard = $currentUrl === url('/') || $currentUrl === url('/dashboard');

$menuItems = [
    'General' => [
        ['route' => '/', 'label' => 'Dashboard', 'icon' => 'ki-home', 'active' => $isDashboard],
        ['route' => '#', 'label' => 'User Profile', 'icon' => 'ki-user', 'items' => [
            ['route' => '#', 'label' => 'Overview'],
            ['route' => '#', 'label' => 'Activity'],
        ]],
    ],
    'Master Data' => [
        ['route' => '/master-data/expeditions', 'label' => 'Ekspedisi', 'icon' => 'ki-purchase', 'active' => request()->is('master-data/expeditions'), 'roles' => ['Administrator']],
        ['route' => '/master-data/units', 'label' => 'Unit Satuan', 'icon' => 'ki-purchase', 'active' => request()->is('master-data/units'), 'roles' => ['Administrator']],
        ['route' => '/master-data/feeds', 'label' => 'Pakan', 'icon' => 'ki-purchase', 'active' => request()->is('master-data/feeds'), 'roles' => ['Administrator']],
        ['route' => '/master-data/supplies', 'label' => 'Supply', 'icon' => 'ki-purchase', 'active' => request()->is('master-data/supplies'), 'roles' => ['Administrator']],
    ],
    'Rekanan' => [
        ['route' => '/rekanan/suppliers', 'label' => 'Supplier', 'icon' => 'ki-add-user', 'can' => 'read supplier management'],
        ['route' => '/rekanan/customers', 'label' => 'Pembeli', 'icon' => 'ki-add-user', 'can' => 'read customer management'],
        ['route' => '/rekanan/ekspedisis', 'label' => 'Ekspedisi', 'icon' => 'ki-truck', 'can' => 'read ekspedisi'],
    ],
    'Inventory' => [
        ['route' => '/inventory/docs', 'label' => 'DOC', 'icon' => 'ki-folder'],
        ['route' => '/inventory/stocks', 'label' => 'Stock', 'icon' => 'ki-package'],
        // ['route' => '#', 'label' => 'Storage', 'icon' => 'ki-box'],
    ],
    'User Management' => [
        ['route' => '/users', 'label' => 'User List', 'icon' => 'ki-user-circle', 'can' => 'read user management'],
        ['route' => '/user/roles', 'label' => 'User Role', 'icon' => 'ki-shield', 'can' => 'read user management'],
        ['route' => '/user/permissions', 'label' => 'User Permission', 'icon' => 'ki-lock-2', 'can' => 'SuperAdmin'],
    ],
    'Peternakan' => [
        ['route' => '/data/farms', 'label' => 'Data Farm', 'icon' => 'ki-farm', 'active' => request()->is('data/farms') || request()->is('master-data/farms')],
        ['route' => '/data/kandangs', 'label' => 'Data Kandang', 'icon' => 'ki-barn', 'active' => request()->is('data/kandangs') || request()->is('master-data/kandangs')],
        ['route' => '/data/livestocks', 'label' => 'Data ' . trans('content.livestocks',[],'id'), 'icon' => 'ki-cow', 'active' => request()->is('data/livestocks') || request()->is('master-data/livestocks')],
        // ['route' => '/data/stoks', 'label' => 'Data Stoks', 'icon' => 'ki-chart-line-up', 'active' => request()->is('data/stoks') || request()->is('master-data/stoks')],
        ['route' => '/data/standar-bobot', 'label' => 'Data Standar Bobot', 'icon' => 'ki-weight', 'active' => request()->is('data/standar-bobot')],
        ['route' => '/livestock/afkir', 'label' => trans('content.ternak',[],'id') . ' Afkir', 'icon' => 'ki-remove'],
        ['route' => '/livestock/jual', 'label' => trans('content.ternak',[],'id') . ' Jual', 'icon' => 'ki-sale'],
        ['route' => '/livestock/mati', 'label' => trans('content.ternak',[],'id') . ' Mati', 'icon' => 'ki-skull'],
        
    ],
    'Transaksi' => [
        ['route' => '/pembelian/doc', 'label' => 'Pembelian DOC', 'icon' => 'ki-purchase', 'active' => request()->is('pembelian/doc'), 'roles' => ['Supervisor','Manager']],
        ['route' => '/pembelian/pakan', 'label' => 'Pembelian Pakan', 'icon' => 'ki-purchase', 'active' => request()->is('pembelian/pakan'), 'roles' => ['Admin']],
        ['route' => '/pembelian/ovk', 'label' => 'Pembelian OVK', 'icon' => 'ki-purchase', 'active' => request()->is('pembelian/ovk'), 'roles' => ['Admin']],
        ['route' => '/pembelian/stock', 'label' => 'Pembelian Stock', 'icon' => 'ki-purchase', 'active' => request()->is('pembelian/stock') || request()->is('transaksi/stoks'), 'roles' => ['Operator']],
        ['route' => '/transaksi/penjualan', 'label' => 'Penjualan ' . trans('content.ternak',[],'id') . ' ', 'icon' => 'ki-sale', 'active' => request()->is('transaksi/penjualan')],
		['route' => '/stocks/mutasi', 'label' => 'Mutasi Pakan', 'icon' => 'ki-transfer'],
        ['route' => '/livestock/mutasi', 'label' => 'Mutasi Ayam', 'icon' => 'ki-transfer', 'alt_label' => ''],
        //['route' => '/transaksi/harian', 'label' => 'Harian', 'icon' => 'ki-calendar', 'active' => request()->is('transaksi/harian')],
    ],
    'Reports' => [
        ['route' => '/reports/harian', 'label' => 'Harian', 'icon' => 'ki-chart-line-up', 'active' => request()->is('reports/harian')],
        ['route' => '/reports/daily-cost', 'label' => 'Harian Biaya', 'icon' => 'ki-chart-line-up', 'active' => request()->is('reports/daily-cost')],
        ['route' => '/reports/performa', 'label' => 'Performa', 'icon' => 'ki-chart-line-up', 'active' => request()->is('reports/performa')],
        ['route' => '/reports/penjualan', 'label' => 'Penjualan', 'icon' => 'ki-chart-line-up', 'active' => request()->is('reports/penjualan')],
        ['route' => '/reports/feed/purchase', 'label' => 'Pembelian Pakan', 'icon' => 'ki-chart-line-up', 'active' => request()->is('reports/feed/purchase')],
        ['route' => '/reports/performa-mitra', 'label' => 'Performa Kemitraan', 'icon' => 'ki-chart-line-up', 'active' => request()->is('reports/performa-mitra')],
    ],
];

// Filter out categories the user can't access
$menuItems = array_filter($menuItems, function ($items) {
    foreach ($items as $item) {
        $canAccess = !isset($item['can']) || auth()->user()->can($item['can']);
        $hasRole = !isset($item['roles']) || (isset($item['roles']) && count(array_intersect(auth()->user()->getRoleNames()->toArray(), $item['roles'])) > 0);
        if ($canAccess && $hasRole) {
            return true; // User has access to at least one item in this category
        }
    }
    return false; // User has no access to any item in this category
});
@endphp

<div class="app-header-menu app-header-mobile-drawer align-items-stretch" data-kt-drawer="true"
     data-kt-drawer-name="app-header-menu" data-kt-drawer-activate="{default: true, lg: false}"
     data-kt-drawer-overlay="true" data-kt-drawer-width="{default:'200px', '300px': '250px'}"
     data-kt-drawer-direction="start" data-kt-drawer-toggle="#kt_app_header_menu_toggle" data-kt-swapper="true"
     data-kt-swapper-mode="{default: 'append', lg: 'prepend'}"
     data-kt-swapper-parent="{default: '#kt_app_body', lg: '#kt_app_header_menu_wrapper'}">
    <div class="menu menu-rounded menu-column menu-lg-row menu-active-bg menu-title-gray-700 menu-state-gray-900 menu-icon-gray-500 menu-arrow-gray-500 menu-state-icon-primary menu-state-bullet-primary fw-semibold fs-6 align-items-stretch my-5 my-lg-0 px-2 px-lg-0"
         id="#kt_app_header_menu" data-kt-menu="true">

        @foreach ($menuItems as $category => $items)
            @php
                $categoryActive = false;
                foreach ($items as $item) {
                    if (isset($item['active']) && $item['active']) {
                        $categoryActive = true;
                        break;
                    }
                }
            @endphp

            <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="bottom-start"
                 class="menu-item menu-lg-down-accordion me-0 me-lg-2 {{ $categoryActive ? 'here show menu-here-bg' : '' }}">
                <span class="menu-link">
                    <span class="menu-title">{{ $category }}</span>
                    <span class="menu-arrow d-lg-none"></span>
                </span>
                <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown px-lg-2 py-lg-4 w-lg-200px">
                    @foreach ($items as $item)
                        @php
                            $canAccess = !isset($item['can']) || auth()->user()->can($item['can']);
                            $hasRole = !isset($item['roles']) || (isset($item['roles']) && count(array_intersect(auth()->user()->getRoleNames()->toArray(), $item['roles'])) > 0);
                        @endphp

                        @if ($canAccess && $hasRole)
                            <div class="menu-item p-0 m-0">
                                <a href="{{ $item['route'] }}" class="menu-link {{ isset($item['active']) && $item['active'] ? 'active' : '' }}">
                                    <span class="menu-icon"><i class="{{ $item['icon'] }} fs-2"></i></span>
                                    <span class="menu-title">{{ $item['label'] }}</span>
                                    @if (isset($item['alt_label']))
                                        <span class="menu-desc text-muted fs-7">{{ $item['alt_label'] }}</span>
                                    @endif
                                </a>
                                @if (isset($item['items']))
                                    <div class="menu-sub menu-sub-dropdown">
                                        @foreach ($item['items'] as $subItem)
                                            <div class="menu-item p-0 m-0">
                                                <a href="{{ $subItem['route'] }}" class="menu-link">
                                                    <span class="menu-title">{{ $subItem['label'] }}</span>
                                                </a>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
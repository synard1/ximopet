@php
$currentUrl = request()->url();
$isDashboard = $currentUrl === url('/') || $currentUrl === url('/dashboard');


$menuConfig = [
'show_categories' => true, // Set to false to hide category headers
'sort_by' => 'order', // Options: 'order', 'label', 'custom'
'show_without_category' => true, // Set to true to show items without category
'icon_type' => 'fa', // Options: 'fa' (Font Awesome), 'ki' (Keenthemes Icons)
];

$menuItems = config('xolution.menu');

// Sort categories based on order
uasort($menuItems, function ($a, $b) {
return $a['order'] <=> $b['order'];
    });

    $user = auth()->user();
    $filteredMenuItems = [];

    foreach ($menuItems as $category => $categoryData) {
    $hasCategoryAccess =
    hasRequiredPermission($user, $categoryData['can'] ?? null) &&
    hasRequiredRoles($user, $categoryData['roles'] ?? null);

    if (!$hasCategoryAccess || !$categoryData['show']) {
    continue;
    }

    $filteredItems = [];
    foreach ($categoryData['items'] as $item) {
    $hasItemAccess =
    hasRequiredPermission($user, $item['can'] ?? null) && hasRequiredRoles($user, $item['roles'] ?? null);

    if ($hasItemAccess && $item['show']) {
    $filteredItems[] = $item;
    }
    }

    if (!empty($filteredItems)) {
    usort($filteredItems, fn($a, $b) => $a['order'] <=> $b['order']);
        $categoryData['items'] = $filteredItems;
        $filteredMenuItems[$category] = $categoryData;
        }
        }

        $menuItems = $filteredMenuItems;

        // Filter out empty categories
        $menuItems = array_filter($menuItems, function ($categoryData) {
        return (bool)($categoryData['show'] && !empty($categoryData['items']));
        });

        @endphp

        <div class="menu menu-column menu-rounded menu-sub-indention fw-semibold fs-6" id="#kt_app_sidebar_menu"
            data-kt-menu="true" data-kt-menu-expand="false">
            @foreach ($menuItems as $category => $categoryData)
            @foreach ($categoryData['items'] as $item)
            @if (isset($item['show_without_category']) && $item['show_without_category'] &&
            $menuConfig['show_without_category'])
            {{-- Tampilkan item tanpa kategori --}}
            <div class="menu-item">
                <a class="menu-link {{ isset($item['active']) && $item['active'] ? 'active' : '' }}"
                    href="{{ $item['route'] }}">
                    <span class="menu-icon">
                        <i class="{{ $item['icon'] }} fs-2"></i>
                    </span>
                    <span class="menu-title">{{ $item['label'] }}</span>
                </a>
            </div>
            @else
            {{-- Tampilkan item dengan kategori --}}
            @if ($loop->first && $menuConfig['show_categories'])
            <div class="menu-item pt-5">
                <div class="menu-content">
                    <span class="menu-heading fw-bold text-uppercase fs-7">{{ $category }}</span>
                </div>
            </div>
            @endif
            <div class="menu-item {{ isset($item['submenu']) ? 'menu-accordion' : '' }}">
                @if (isset($item['submenu']))
                <span class="menu-link menu-toggle">
                    <span class="menu-icon">
                        <i class="{{ $item['icon'] }} fs-2"></i>
                    </span>
                    <span class="menu-title">{{ $item['label'] }}</span>
                    <span class="menu-arrow"></span>
                </span>
                <div class="menu-sub menu-sub-accordion">
                    @foreach ($item['submenu'] as $subItem)
                    <div class="menu-item">
                        <a class="menu-link {{ isset($subItem['active']) && $subItem['active'] ? 'active' : '' }}"
                            href="{{ $subItem['route'] }}">
                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                            @php
                            $subLabel = is_callable($subItem['label']) ? $subItem['label']() : $subItem['label'];
                            @endphp
                            <span class="menu-title">{{ $subLabel }}</span>

                        </a>
                    </div>
                    @endforeach
                </div>
                @else
                <a class="menu-link {{ isset($item['active']) && $item['active'] ? 'active' : '' }}"
                    href="{{ $item['route'] }}">
                    @php
                    $icon = is_callable($item['icon']) ? $item['icon']() : $item['icon'];
                    @endphp
                    <span class="menu-icon">
                        @if (Str::startsWith($icon, ['http://', 'https://','/']))
                        <img src="{{ $icon }}" class="menu-icon-img w-20px h-20px" alt="icon" />
                        @else
                        <i class="{{ $icon }} fs-2"></i>
                        @endif
                    </span>

                    @php
                    $label = is_callable($item['label']) ? $item['label']() : $item['label'];
                    @endphp
                    <span class="menu-title">{{ $label }}</span>

                </a>
                @endif
            </div>
            @endif
            @endforeach
            @endforeach
        </div>
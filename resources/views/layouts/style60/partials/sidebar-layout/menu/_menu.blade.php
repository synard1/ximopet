@php
$currentUrl = request()->url();
$isDashboard = $currentUrl === url('/') || $currentUrl === url('/dashboard');

$menuConfig = [
'show_categories' => true,
'sort_by' => 'order',
'show_without_category' => true,
'icon_type' => 'fa',
];

// Get menus from database
$menus = App\Models\Menu::getMenuByLocation('sidebar', auth()->user());

// Convert database menus to the expected format
$menuItems = [];
foreach ($menus as $menu) {
if (!$menu->parent_id) { // Only process parent menus
$categoryData = [
'order' => $menu->order_number,
'show' => true,
'items' => []
];

// Add child menus
foreach ($menu->children as $child) {
$categoryData['items'][] = [
'route' => $child->route,
'label' => $child->label,
'icon' => $child->icon,
'order' => $child->order_number,
'show' => true
];
}

$menuItems[$menu->label] = $categoryData;
}
}

// Sort categories based on order
uasort($menuItems, function ($a, $b) {
return $a['order'] <=> $b['order'];
    });

    $user = auth()->user();
    $isSuperAdmin = $user->hasRole('SuperAdmin');
    $filteredMenuItems = [];

    foreach ($menuItems as $category => $categoryData) {
    // Skip if category is not shown
    if (!$categoryData['show']) {
    continue;
    }

    // For SuperAdmin, bypass all role and permission checks
    $hasCategoryAccess = $isSuperAdmin ? true : (
    hasRequiredPermission($user, $categoryData['can'] ?? null) &&
    hasRequiredRoles($user, $categoryData['roles'] ?? null)
    );

    if (!$hasCategoryAccess) {
    continue;
    }

    $filteredItems = [];
    foreach ($categoryData['items'] as $item) {
    // For SuperAdmin, bypass all role and permission checks for items
    $hasItemAccess = $isSuperAdmin ? true : (
    hasRequiredPermission($user, $item['can'] ?? null) &&
    hasRequiredRoles($user, $item['roles'] ?? null)
    );

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

        <x-menu location="sidebar" />
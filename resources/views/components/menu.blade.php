@props(['location' => 'sidebar'])

@php
$menus = App\Models\Menu::getMenuByLocation($location, auth()->user());
$currentUrl = request()->url();
$currentPath = request()->path(); // Use path for more reliable matching
$isDashboard = $currentPath === '/' || $currentPath === 'dashboard';

// Function to check if a menu or its children are active
function isMenuActive($menu, $currentPath) {
// Check if the current menu item's route matches the current path precisely
// or if the current path starts exactly with the menu route path followed by a slash
if ($menu->route && $menu->route !== '#') {
$menuPath = ltrim(parse_url(url($menu->route), PHP_URL_PATH), '/');
if ($currentPath === $menuPath || str_starts_with($currentPath, $menuPath . '/')) {
return true;
}
}

// If it's a parent menu, check if any of its children are active
if ($menu->children->isNotEmpty()) {
foreach ($menu->children as $child) {
if ($child->route && $child->route !== '#') {
$childPath = ltrim(parse_url(url($child->route), PHP_URL_PATH), '/');
if ($currentPath === $childPath || str_starts_with($currentPath, $childPath . '/')) {
return true;
}
}
}
}

return false;
}

// Function to check if a direct menu item is active (not a parent)
function isMenuItemActive($menu, $currentPath, $isDashboard) {
// Check if it's the dashboard link and the current path is the dashboard or root
if ($isDashboard && $menu->route === '/') {
return true;
}
// Check if the menu item's route matches the current path precisely
// or if the current path starts exactly with the menu route path followed by a slash
if ($menu->route && $menu->route !== '#') {
$menuPath = ltrim(parse_url(url($menu->route), PHP_URL_PATH), '/');
if ($currentPath === $menuPath || str_starts_with($currentPath, $menuPath . '/')) {
return true;
}
}
return false;
}
@endphp

<div class="menu menu-column menu-rounded menu-sub-indention fw-semibold fs-6" id="kt_app_sidebar_menu"
    data-kt-menu="true" data-kt-menu-expand="false">

    @foreach($menus as $menu)
    @if($menu->children->isNotEmpty())
    <div class="menu-item menu-accordion">
        {{-- 'active show' classes for initial state if a child is active --}}
        <span class="menu-link menu-toggle {{ isMenuActive($menu, $currentPath) ? 'active show' : '' }}"
            data-kt-menu-trigger="click" {{-- Add href for accessibility, use # if no route --}}
            href="{{ $menu->route && $menu->route !== '#' ? url($menu->route) : '#' }}">
            <span class="menu-icon">
                <i class="{{ $menu->icon }} fs-2"></i>
            </span>
            <span class="menu-title">{{ $menu->label }}</span>
            <span class="menu-arrow"></span>
        </span>
        {{-- 'show' class for initial state of submenu if a child is active --}}
        <div class="menu-sub menu-sub-accordion {{ isMenuActive($menu, $currentPath) ? 'show' : '' }}">
            @foreach($menu->children as $child)
            <div class="menu-item">
                <a class="menu-link {{ isMenuItemActive($child, $currentPath, false) ? 'active' : '' }}" {{-- Check
                    child active state --}}
                    href="{{ $child->route && $child->route !== '#' ? url($child->route) : '#' }}">
                    <span class="menu-bullet">
                        <span class="bullet bullet-dot"></span>
                    </span>
                    <span class="menu-title">{{ $child->label }}</span>
                </a>
            </div>
            @endforeach
        </div>
    </div>
    @else
    <div class="menu-item">
        <a class="menu-link {{ isMenuItemActive($menu, $currentPath, $isDashboard) ? 'active' : '' }}" {{-- Check direct
            menu item active state --}} href="{{ $menu->route && $menu->route !== '#' ? url($menu->route) : '#' }}">
            <span class="menu-icon">
                <i class="{{ $menu->icon }} fs-2"></i>
            </span>
            <span class="menu-title">{{ $menu->label }}</span>
        </a>
    </div>
    @endif
    @endforeach
</div>

@push('styles')
<style>
    /* Example CSS to highlight active menu items */
    .menu .menu-link.active {
        color: var(--bs-menu-link-color-active, #ffffff) !important;
        /* Example: White text color */
        background-color: var(--bs-menu-link-bg-color-active, #009EF7) !important;
        /* Example: A blue background */
        /* Add any other desired styles, like font-weight, border, etc. */
    }

    /* If you also want to style the active parent toggle */
    .menu .menu-link.menu-toggle.active {
        /* Styles for the active parent toggle, e.g., */
        color: var(--bs-menu-link-color-active, #ffffff) !important;
        background-color: var(--bs-menu-link-bg-color-active, #009EF7) !important;
    }

    /* Optionally style the active child item text within an open parent */
    .menu .menu-sub-accordion .menu-item .menu-link.active {
        color: var(--bs-link-color, #009EF7) !important;
        /* Example: Use a theme color for active child link text */
        background-color: transparent !important;
        /* Usually child items don't have a background */
    }

    /* CSS for active submenu items (increased specificity) */

    /* Target the active link directly within the submenu div */
    .menu.menu-column .menu-item.menu-accordion .menu-sub.menu-sub-accordion .menu-item .menu-link.active {
        /* Example: Change text color */
        color: var(--bs-primary, #007bff) !important;
        /* Using Bootstrap primary color as an example */
        /* Example: Add a subtle background color */
        /* background-color: var(--bs-light, #f8f9fa) !important; */
        /* Example: Bold font weight */
        font-weight: 600 !important;
        /* Add any other desired styles */
    }

    /* Target the bullet point specifically if it's not changing */
    .menu.menu-column .menu-item.menu-accordion .menu-sub.menu-sub-accordion .menu-item .menu-link.active .menu-bullet .bullet.bullet-dot {
        background-color: var(--bs-primary, #007bff) !important;
        /* Example: Match bullet color to active link color */
    }
</style>

@endpush
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Select all menu toggles that have the click trigger attribute
        const menuToggles = document.querySelectorAll('.menu-toggle[data-kt-menu-trigger="click"]');

        menuToggles.forEach(toggle => {
            // Add click event listener
            toggle.addEventListener('click', function(e) {
                const parentItem = this.closest('.menu-item.menu-accordion');
                if (!parentItem) return; // Exit if not inside an accordion menu item

                const submenu = this.nextElementSibling;
                if (!submenu || !submenu.classList.contains('menu-sub-accordion')) return; // Exit if no submenu or not an accordion submenu

                // Check if the menu toggle has the 'active' class (set by Blade if active by route)
                const isCurrentlyActiveByRoute = this.classList.contains('active');

                // If the menu is currently active by route, prevent default and do nothing else (it stays open).
                if (isCurrentlyActiveByRoute) {
                    e.preventDefault();
                    return; // Stop processing this click
                }

                // If it's not active by route, prevent default for '#' links and toggle its state.
                 if (this.getAttribute('href') === '#' || this.getAttribute('href') === null) {
                     e.preventDefault();
                 }

                 // Close all other open accordion menus that are NOT active by route
                 document.querySelectorAll('.menu-item.menu-accordion .menu-sub-accordion.show').forEach(openSubmenu => {
                     const openToggle = openSubmenu.previousElementSibling;
                      // Check if the toggle exists, is a menu-toggle, and is NOT active by route
                     if(openToggle && openToggle.classList.contains('menu-toggle') && !openToggle.classList.contains('active')) {
                             openSubmenu.classList.remove('show');
                             openToggle.classList.remove('active', 'show');
                     }
                 });

                // Toggle the current menu's show/active state
                this.classList.toggle('active');
                this.classList.toggle('show');
                submenu.classList.toggle('show');
            });
        });

        // Initial logic to handle state on page load based on classes set by Blade
        // Find all menu links that are marked as active by Blade
        document.querySelectorAll('.menu-link.active').forEach(activeLink => {
            let currentElement = activeLink.closest('.menu-sub-accordion');
            while (currentElement) {
                currentElement.classList.add('show');
                const parentToggle = currentElement.previousElementSibling;
                if (parentToggle && parentToggle.classList.contains('menu-toggle')) {
                     parentToggle.classList.add('active', 'show'); // Mark parent toggle as active/show
                     currentElement = parentToggle.parentElement.closest('.menu-sub-accordion'); // Move up to grandparent submenu
                } else {
                    break; // Stop if no parent toggle found
                }
            }
        });

    });
</script>
@endpush
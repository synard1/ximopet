<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\MenuBackupService;
use App\Services\LegacyMenuImportService;
use Illuminate\Support\Facades\File;

class MenuController extends Controller
{
    protected $menuBackupService;
    protected $legacyMenuImportService;

    public function __construct(MenuBackupService $menuBackupService, LegacyMenuImportService $legacyMenuImportService)
    {
        $this->menuBackupService = $menuBackupService;
        $this->legacyMenuImportService = $legacyMenuImportService;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $menus = Menu::with(['roles', 'permissions', 'parent'])
                ->orderBy('location')
                ->orderBy('parent_id', 'asc')
                ->orderBy('order_number');

            return DataTables::eloquent($menus)
                ->addColumn('name', function (Menu $menu) {
                    $prefix = $menu->parent_id ? '-- ' : '';
                    return $prefix . $menu->name;
                })
                ->addColumn('roles', function (Menu $menu) {
                    return $menu->roles->map(function ($role) {
                        return '<span class="badge badge-primary me-1">' . $role->name . '</span>';
                    })->implode('');
                })
                ->addColumn('permissions', function (Menu $menu) {
                    return $menu->permissions->map(function ($permission) {
                        return '<span class="badge badge-info me-1">' . $permission->name . '</span>';
                    })->implode('');
                })
                ->addColumn('actions', function (Menu $menu) {
                    $editUrl = route('administrator.menu.edit', $menu);
                    $deleteUrl = route('administrator.menu.destroy', $menu);
                    $csrf = csrf_field();
                    $method = method_field('DELETE');

                    return "
                        <a href=\"{$editUrl}\" class=\"btn btn-sm btn-info\"><i class=\"fa fa-edit\"></i></a>
                        <form action=\"{$deleteUrl}\" method=\"POST\" class=\"d-inline\" onsubmit=\"return confirm('Are you sure?')\">
                            {$csrf}
                            {$method}
                            <button type=\"submit\" class=\"btn btn-sm btn-danger\"><i class=\"fa fa-trash\"></i></button>
                        </form>
                    ";
                })
                ->rawColumns(['roles', 'permissions', 'actions'])
                ->toJson();
        }

        return view('pages.menu.index');
    }

    public function create()
    {
        $roles = Role::all();
        $permissions = Permission::all();
        $parentMenus = Menu::whereNull('parent_id')->get();

        return view('pages.menu.create', compact('roles', 'permissions', 'parentMenus'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'label' => 'required|string|max:255',
            'route' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'location' => 'required|string|in:sidebar,header',
            'order_number' => 'required|integer|min:0',
            'parent_id' => 'nullable|exists:menus,id',
            'is_active' => 'boolean',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        try {
            DB::beginTransaction();

            $menu = Menu::create($validated);

            // Always sync roles and permissions, even if empty arrays
            $menu->roles()->sync($request->input('roles', []));
            $menu->permissions()->sync($request->input('permissions', []));

            DB::commit();

            // Create backup after successful creation, outside transaction
            try {
                $this->menuBackupService->createBackup('created');
            } catch (\Exception $e) {
                Log::error('Backup creation failed after menu creation: ' . $e->getMessage());
                // Don't throw the error, just log it
            }

            return redirect()->route('administrator.menu.index')
                ->with('success', 'Menu created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create menu: ' . $e->getMessage());
            return back()->with('error', 'Failed to create menu. Please try again.');
        }
    }

    public function edit(Menu $menu)
    {
        $roles = Role::all();
        $permissions = Permission::all();
        $parentMenus = Menu::whereNull('parent_id')
            ->where('id', '!=', $menu->id)
            ->get();

        return view('pages.menu.edit', compact('menu', 'roles', 'permissions', 'parentMenus'));
    }

    public function update(Request $request, Menu $menu)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'label' => 'required|string|max:255',
            'route' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'location' => 'required|string|in:sidebar,header',
            'order_number' => 'required|integer|min:0',
            'parent_id' => 'nullable|exists:menus,id',
            'is_active' => 'boolean',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        try {
            DB::beginTransaction();

            $menu->update($validated);

            // Always sync roles and permissions, even if empty arrays
            $menu->roles()->sync($request->input('roles', []));
            $menu->permissions()->sync($request->input('permissions', []));

            DB::commit();

            // Create backup after successful update, outside transaction
            try {
                $this->menuBackupService->createBackup('updated');
            } catch (\Exception $e) {
                Log::error('Backup creation failed after menu update: ' . $e->getMessage());
                // Don't throw the error, just log it
            }

            return redirect()->route('administrator.menu.index')
                ->with('success', 'Menu updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update menu: ' . $e->getMessage());
            return back()->with('error', 'Failed to update menu. Please try again.');
        }
    }

    public function destroy(Menu $menu)
    {
        try {
            DB::beginTransaction();

            $menu->delete();

            DB::commit();

            // Create backup after successful deletion, outside transaction
            try {
                $this->menuBackupService->createBackup('deleted');
            } catch (\Exception $e) {
                Log::error('Backup creation failed after menu deletion: ' . $e->getMessage());
                // Don't throw the error, just log it
            }

            return redirect()->route('administrator.menu.index')
                ->with('success', 'Menu deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete menu: ' . $e->getMessage());
            return back()->with('error', 'Failed to delete menu. Please try again.');
        }
    }

    public function updateOrder(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:menus,id',
            'items.*.order_number' => 'required|integer',
        ]);

        foreach ($validated['items'] as $item) {
            Menu::where('id', $item['id'])->update(['order_number' => $item['order_number']]);
        }

        Cache::flush();

        return response()->json(['message' => 'Menu order updated successfully']);
    }

    public function export()
    {
        try {
            $menus = Menu::with(['roles', 'permissions', 'children.roles', 'children.permissions'])
                ->whereNull('parent_id')
                ->orderBy('order_number')
                ->get();

            $data = $menus->map(function ($menu) {
                return [
                    'id' => $menu->id,
                    'name' => $menu->name,
                    'label' => $menu->label,
                    'route' => $menu->route,
                    'icon' => $menu->icon,
                    'location' => $menu->location,
                    'order_number' => $menu->order_number,
                    'is_active' => $menu->is_active,
                    'roles' => $menu->roles->map(function ($role) {
                        return [
                            'id' => $role->id,
                            'name' => $role->name
                        ];
                    })->toArray(),
                    'permissions' => $menu->permissions->map(function ($permission) {
                        return [
                            'id' => $permission->id,
                            'name' => $permission->name
                        ];
                    })->toArray(),
                    'children' => $menu->children->map(function ($child) {
                        return [
                            'id' => $child->id,
                            'name' => $child->name,
                            'label' => $child->label,
                            'route' => $child->route,
                            'icon' => $child->icon,
                            'location' => $child->location,
                            'order_number' => $child->order_number,
                            'is_active' => $child->is_active,
                            'roles' => $child->roles->map(function ($role) {
                                return [
                                    'id' => $role->id,
                                    'name' => $role->name
                                ];
                            })->toArray(),
                            'permissions' => $child->permissions->map(function ($permission) {
                                return [
                                    'id' => $permission->id,
                                    'name' => $permission->name
                                ];
                            })->toArray(),
                        ];
                    })->toArray()
                ];
            })->toArray();

            $filename = 'menu_configuration_' . date('Y-m-d_H-i-s') . '.json';
            $jsonData = json_encode($data, JSON_PRETTY_PRINT);

            return response($jsonData)
                ->header('Content-Type', 'application/json')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        } catch (\Exception $e) {
            Log::error('Failed to export menu configuration', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Failed to export menu configuration: ' . $e->getMessage());
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'menu_file' => 'required|file|mimes:json',
        ]);

        $file = $request->file('menu_file');
        $jsonContent = file_get_contents($file->getRealPath());
        $menuConfig = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return redirect()->route('administrator.menu.index')
                ->with('error', 'Invalid JSON format in the uploaded file.');
        }

        try {
            // Validate menu configuration
            $validation = $this->legacyMenuImportService->validateMenuConfiguration($menuConfig);

            if (!empty($validation['errors'])) {
                return redirect()->route('administrator.menu.index')
                    ->with('error', 'Menu validation failed: ' . implode(', ', $validation['errors']));
            }

            // Import menu configuration using the legacy service
            $result = $this->legacyMenuImportService->importMenuConfiguration($menuConfig, 'sidebar');

            if ($result['success']) {
                // Clear menu cache
                Cache::flush();

                $message = sprintf(
                    'Menu configuration imported successfully! Format: %s, Imported: %d menus, Roles: %d, Permissions: %d',
                    $result['format'],
                    $result['imported_count'],
                    $result['roles_attached'],
                    $result['permissions_attached']
                );

                return redirect()->route('administrator.menu.index')
                    ->with('success', $message);
            } else {
                return redirect()->route('administrator.menu.index')
                    ->with('error', 'Error importing menu configuration: ' . $result['error']);
            }
        } catch (\Exception $e) {
            Log::error('Menu import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('administrator.menu.index')
                ->with('error', 'Error importing menu configuration: ' . $e->getMessage());
        }
    }

    /**
     * Preview menu import before actual import
     */
    public function importPreview(Request $request)
    {
        $request->validate([
            'menu_file' => 'required|file|mimes:json',
        ]);

        $file = $request->file('menu_file');
        $jsonContent = file_get_contents($file->getRealPath());
        $menuConfig = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid JSON format in the uploaded file.'
            ], 400);
        }

        try {
            // Get preview information
            $preview = $this->legacyMenuImportService->getImportPreview($menuConfig);

            // Validate menu configuration
            $validation = $this->legacyMenuImportService->validateMenuConfiguration($menuConfig);

            return response()->json([
                'success' => true,
                'preview' => $preview,
                'validation' => $validation
            ]);
        } catch (\Exception $e) {
            Log::error('Menu import preview failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error processing menu configuration: ' . $e->getMessage()
            ], 500);
        }
    }

    protected function importMenus(array $menuItems, $parentId = null, $location)
    {
        foreach ($menuItems as $menuItem) {
            // Create the menu item
            $menu = Menu::create([
                'parent_id' => $parentId,
                'name' => $menuItem['name'] ?? null,
                'label' => $menuItem['label'] ?? null,
                'route' => $menuItem['route'] ?? null,
                'icon' => $menuItem['icon'] ?? null,
                'location' => $location, // Ensure correct location is set
                'order_number' => $menuItem['order_number'] ?? 0,
                'is_active' => $menuItem['is_active'] ?? true, // Assuming boolean
            ]);

            // Attach roles
            if (isset($menuItem['roles']) && is_array($menuItem['roles'])) {
                $roleIds = Role::whereIn('name', array_column($menuItem['roles'], 'name'))->pluck('id');
                $menu->roles()->attach($roleIds);
            }

            // Attach permissions
            if (isset($menuItem['permissions']) && is_array($menuItem['permissions'])) {
                $permissionIds = Permission::whereIn('name', array_column($menuItem['permissions'], 'name'))->pluck('id');
                $menu->permissions()->attach($permissionIds);
            }

            // Recursively import children
            if (isset($menuItem['children']) && is_array($menuItem['children'])) {
                $this->importMenus($menuItem['children'], $menu->id, $location);
            }
        }
    }

    /**
     * Duplicate a menu item and its children
     *
     * @param Menu $menu
     * @return \Illuminate\Http\RedirectResponse
     */
    public function duplicate(Menu $menu)
    {
        try {
            DB::beginTransaction();

            // Create a new menu instance with the same attributes
            $newMenu = $menu->replicate();
            $newMenu->label = $menu->label . ' (Copy)';
            $newMenu->order_number = Menu::max('order_number') + 1;
            $newMenu->save();

            // Duplicate roles and permissions
            $newMenu->roles()->sync($menu->roles);
            $newMenu->permissions()->sync($menu->permissions);

            // If the menu has children, duplicate them as well
            if ($menu->children->isNotEmpty()) {
                $this->duplicateChildren($menu, $newMenu);
            }

            DB::commit();

            // Create backup after successful creation, outside transaction
            try {
                $this->menuBackupService->createBackup('duplicated');
            } catch (\Exception $e) {
                Log::error('Backup creation failed after menu duplication: ' . $e->getMessage());
                // Don't throw the error, just log it
            }

            return redirect()->route('administrator.menu.index')
                ->with('success', 'Menu item duplicated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Menu duplication failed: ' . $e->getMessage());
            return redirect()->route('administrator.menu.index')
                ->with('error', 'Failed to duplicate menu item: ' . $e->getMessage());
        }
    }

    /**
     * Recursively duplicate child menu items
     *
     * @param Menu $originalParent
     * @param Menu $newParent
     * @return void
     */
    private function duplicateChildren(Menu $originalParent, Menu $newParent)
    {
        foreach ($originalParent->children as $child) {
            // Duplicate the child menu
            $newChild = $child->replicate();
            $newChild->parent_id = $newParent->id;
            $newChild->save();

            // Duplicate roles and permissions for the child
            $newChild->roles()->sync($child->roles);
            $newChild->permissions()->sync($child->permissions);

            // If the child has its own children, duplicate them recursively
            if ($child->children->isNotEmpty()) {
                $this->duplicateChildren($child, $newChild);
            }
        }
    }

    public function createBackup()
    {
        try {
            // Use 'manual' if no event is passed, otherwise createBackup logic handles 'auto'
            $success = $this->menuBackupService->createBackup(func_num_args() > 0 ? func_get_arg(0) : 'manual');

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Menu backup created successfully'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to create menu backup'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Failed to create manual backup: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create menu backup'
            ], 500);
        }
    }

    public function getMenu(Request $request)
    {
        $location = $request->query('location'); // Get location from query parameters
        $user = $request->user(); // Get the authenticated user
        $token = $request->header('Authorization');
        // dd($token);

        // Fetch the menu based on location and user permissions
        $menus = Menu::getMenuByLocationApi($location, $token);

        return response()->json($menus);
    }
}

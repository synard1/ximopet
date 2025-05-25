<?php

namespace App\Services;

use App\Models\Menu;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MenuBackupService
{
    /**
     * Create a backup of the current menu state
     */
    public function createBackup($event = 'manual')
    {
        try {
            Log::info('Creating menu backup', ['event' => $event]);

            // Get all menus with their relationships
            $menus = Menu::with(['roles', 'permissions', 'children.roles', 'children.permissions'])
                ->whereNull('parent_id')
                ->orderBy('order_number')
                ->get();

            // Format the data to include roles and permissions
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
                            })->toArray()
                        ];
                    })->toArray()
                ];
            })->toArray();

            $timestamp = now()->format('Y-m-d_His');
            $filenameEvent = ($event === 'manual') ? 'manual' : 'auto';
            $filename = "menu_backup_{$filenameEvent}_{$timestamp}.json";
            $path = storage_path('app/backups/menus/' . $filename);

            if (!File::exists(storage_path('app/backups/menus'))) {
                File::makeDirectory(storage_path('app/backups/menus'), 0755, true);
            }

            File::put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            Log::info('Menu backup created successfully', [
                'filename' => $filename,
                'event' => $event
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to create menu backup', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'event' => $event
            ]);
            return false;
        }
    }
}

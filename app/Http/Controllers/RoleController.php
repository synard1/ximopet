<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\Permission;
use App\Services\RoleBackupService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RoleController extends Controller
{
    protected $roleBackupService;

    public function __construct(RoleBackupService $roleBackupService)
    {
        $this->roleBackupService = $roleBackupService;
    }

    public function index()
    {
        $roles = Role::with('permissions')->get();
        return view('pages.apps.user-management.roles.list', compact('roles'));
    }

    public function create()
    {
        $permissions = Permission::all();
        return view('pages.apps.user-management.roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        try {
            DB::beginTransaction();

            $role = Role::create(['name' => $validated['name']]);

            if (isset($validated['permissions'])) {
                $role->syncPermissions($validated['permissions']);
            }

            DB::commit();

            return redirect()->route('roles.index')
                ->with('success', 'Role created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create role: ' . $e->getMessage());
            return back()->with('error', 'Failed to create role. Please try again.');
        }
    }

    public function edit(Role $role)
    {
        $permissions = Permission::all();
        return view('pages.apps.user-management.roles.edit', compact('role', 'permissions'));
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        try {
            DB::beginTransaction();

            $role->update(['name' => $validated['name']]);

            if (isset($validated['permissions'])) {
                $role->syncPermissions($validated['permissions']);
            } else {
                $role->syncPermissions([]);
            }

            DB::commit();

            return redirect()->route('roles.index')
                ->with('success', 'Role updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update role: ' . $e->getMessage());
            return back()->with('error', 'Failed to update role. Please try again.');
        }
    }

    public function destroy(Role $role)
    {
        try {
            DB::beginTransaction();

            $role->delete();

            DB::commit();

            return redirect()->route('roles.index')
                ->with('success', 'Role deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete role: ' . $e->getMessage());
            return back()->with('error', 'Failed to delete role. Please try again.');
        }
    }

    public function createBackup()
    {
        try {
            $success = $this->roleBackupService->createBackup('manual');

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Role and permission backup created successfully'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to create role and permission backup'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Failed to create manual backup: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create role and permission backup'
            ], 500);
        }
    }
}

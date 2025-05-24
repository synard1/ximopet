<?php

namespace App\Http\Controllers\UserManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;

class RoleController extends Controller
{
    public function data()
    {
        Log::info('Fetching role data for DataTable');
        $query = Role::with('permissions');

        // If user is not SuperAdmin, exclude SuperAdmin role
        if (!auth()->user()->hasRole('SuperAdmin')) {
            $query->where('name', '!=', 'SuperAdmin');
        }

        $roles = $query->get();
        Log::info('Roles retrieved: ' . $roles->count());
        // Log role names and permission counts for a few roles to check data structure
        $roles->take(5)->each(function ($role) {
            Log::info('Role: ' . $role->name . ', Permissions count: ' . $role->permissions->count());
        });

        return DataTables::of($roles)
            ->addIndexColumn()
            ->addColumn('action', function ($role) {
                return view('livewire.permission.role-actions', ['role' => $role])->render();
            })
            ->addColumn('permissions', function ($role) {
                return $role->permissions->pluck('name')->implode(', ');
            })
            ->rawColumns(['action'])
            ->make(true);
    }
}

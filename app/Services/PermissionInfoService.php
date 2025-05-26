<?php

namespace App\Services;

use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Log;

class PermissionInfoService
{
    protected $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function getPermissions()
    {
        return Permission::all();
    }

    public function getRelevantRolesAndPermissions($url)
    {
        $jsonFilePath = __DIR__ . '/../Livewire/AdminMonitoring/permissions.json';
        $json = file_get_contents($jsonFilePath);
        $data = json_decode($json, true);

        // Ensure the URL has a leading slash
        if ($url[0] !== '/') {
            $url = '/' . $url;
        }

        // Debugging: Check the URL and JSON data
        Log::info('Requested URL: ' . $url);
        Log::info('JSON Data: ', $data);

        // Check if the URL exists in the JSON data
        if (!isset($data['permissions'][$url])) {
            return [
                'roles' => [],
                'permissions' => [],
            ];
        }

        $urlData = $data['permissions'][$url];

        $relevantRoles = [];
        foreach ($urlData['roles'] as $role => $value) {
            $relevantRoles[$role] = in_array($role, $this->user->getRoleNames()->toArray());
        }

        $relevantPermissions = [];
        foreach ($urlData['permissions'] as $permission => $value) {
            $relevantPermissions[$permission] = $this->user->can($permission);
        }

        return [
            'roles' => $relevantRoles,
            'permissions' => $relevantPermissions,
        ];
    }
}

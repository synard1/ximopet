<?php

namespace App\Traits;

use Carbon\Carbon;

trait HasTempAuthorization
{
    public $tempAuthEnabled = false;

    protected $tempAuthListeners = [
        'tempAuthGranted' => 'onTempAuthGranted',
        'tempAuthRevoked' => 'onTempAuthRevoked',
    ];

    /**
     * Initialize temp authorization
     */
    public function initializeTempAuth()
    {
        $this->checkTempAuth();

        // Merge listeners if they exist
        if (property_exists($this, 'listeners')) {
            $this->listeners = array_merge($this->listeners, $this->tempAuthListeners);
        } else {
            $this->listeners = $this->tempAuthListeners;
        }
    }

    /**
     * Check current temp authorization status
     */
    public function checkTempAuth()
    {
        $sessionAuth = session('temp_auth_authorized', false);
        $sessionExpiry = session('temp_auth_expiry');

        if ($sessionAuth && $sessionExpiry) {
            $expiry = Carbon::parse($sessionExpiry);
            if (Carbon::now()->lessThan($expiry)) {
                $this->tempAuthEnabled = true;
                return true;
            } else {
                // Authorization expired, clear it
                $this->clearTempAuth();
            }
        }

        $this->tempAuthEnabled = false;
        return false;
    }

    /**
     * Handle temp auth granted event
     */
    public function onTempAuthGranted($data = null)
    {
        $this->tempAuthEnabled = true;
        $this->dispatch('success', 'Autorisasi temporer diberikan. Data sekarang dapat diedit.');
    }

    /**
     * Handle temp auth revoked event
     */
    public function onTempAuthRevoked()
    {
        $this->tempAuthEnabled = false;
        $this->dispatch('info', 'Autorisasi temporer dicabut. Data kembali ke mode readonly.');
    }

    /**
     * Request temporary authorization
     */
    public function requestTempAuth($targetComponent = '')
    {
        // Use full class name if no specific component provided
        $componentName = $targetComponent ?: get_class($this);

        // Debug log
        \Illuminate\Support\Facades\Log::info('requestTempAuth called', [
            'targetComponent' => $componentName,
            'provided_component' => $targetComponent,
            'current_class' => get_class($this),
            'current_class_basename' => class_basename($this),
            'current_url' => request()->fullUrl(),
            'method' => request()->method()
        ]);

        $this->dispatch('requestTempAuth', $componentName);
    }

    /**
     * Check if current user can request temp auth
     */
    public function canRequestTempAuth()
    {
        if (!auth()->check()) {
            \Illuminate\Support\Facades\Log::info('canRequestTempAuth: User not authenticated');
            return false;
        }

        $user = auth()->user();

        // Debug log
        \Illuminate\Support\Facades\Log::info('canRequestTempAuth called', [
            'user_id' => $user->id,
            'user_roles' => $user->roles->pluck('name')->toArray(),
        ]);

        // Check if user has bypass permissions
        $bypassPermissions = config('temp_auth.bypass_permissions', []);
        \Illuminate\Support\Facades\Log::info('Checking bypass permissions', ['permissions' => $bypassPermissions]);

        foreach ($bypassPermissions as $permission) {
            if ($user->can($permission)) {
                \Illuminate\Support\Facades\Log::info('User has bypass permission', ['permission' => $permission]);
                return true;
            }
        }

        // Check if user has allowed roles
        $allowedRoles = config('temp_auth.allowed_roles', []);
        \Illuminate\Support\Facades\Log::info('Checking allowed roles', ['allowed_roles' => $allowedRoles]);

        foreach ($allowedRoles as $role) {
            if ($user->hasRole($role)) {
                \Illuminate\Support\Facades\Log::info('User has allowed role', ['role' => $role]);
                return true;
            }
        }

        \Illuminate\Support\Facades\Log::info('canRequestTempAuth: No access granted');
        return false;
    }

    /**
     * Check if data should be readonly (considering temp auth)
     * Helper method for components that want to use trait logic
     */
    public function checkIsReadonly($additionalConditions = [])
    {
        // If temp auth is enabled, not readonly
        if ($this->tempAuthEnabled) {
            return false;
        }

        // Check additional conditions
        foreach ($additionalConditions as $condition) {
            if ($condition) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if controls should be disabled (considering temp auth)
     * Helper method for components that want to use trait logic
     */
    public function checkIsDisabled($additionalConditions = [])
    {
        // If temp auth is enabled, not disabled
        if ($this->tempAuthEnabled) {
            return false;
        }

        // Check additional conditions
        foreach ($additionalConditions as $condition) {
            if ($condition) {
                return true;
            }
        }

        return false;
    }

    /**
     * Clear temp authorization from session
     */
    private function clearTempAuth()
    {
        session()->forget([
            'temp_auth_authorized',
            'temp_auth_expiry',
            'temp_auth_reason',
            'temp_auth_user',
            'temp_auth_time'
        ]);
    }

    /**
     * Get remaining time for temp authorization
     */
    public function getTempAuthTimeRemaining()
    {
        if (!$this->tempAuthEnabled) {
            return null;
        }

        $expiry = session('temp_auth_expiry');
        if (!$expiry) {
            return null;
        }

        $expiryTime = Carbon::parse($expiry);
        $now = Carbon::now();

        if ($now->greaterThanOrEqualTo($expiryTime)) {
            $this->clearTempAuth();
            $this->tempAuthEnabled = false;
            return null;
        }

        return $expiryTime->diffForHumans($now, true);
    }

    /**
     * Get temp auth session info
     */
    public function getTempAuthInfo()
    {
        if (!$this->tempAuthEnabled) {
            return null;
        }

        return [
            'reason' => session('temp_auth_reason'),
            'user' => session('temp_auth_user'),
            'time' => session('temp_auth_time'),
            'expiry' => session('temp_auth_expiry'),
            'remaining' => $this->getTempAuthTimeRemaining()
        ];
    }
}

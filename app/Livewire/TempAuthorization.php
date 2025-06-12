<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\User;
use App\Models\TempAuthLog;

class TempAuthorization extends Component
{
    public $showModal = false;
    public $password = '';
    public $reason = '';
    public $authorized = false;
    public $authExpiry = null;
    public $targetComponent = '';
    public $errorMessage = '';

    // Authorization mode and configuration
    public $authMode = 'mixed'; // password, user, mixed
    public $authDurationMinutes;
    public $authPassword;

    // User-based authorization
    public $authorizerEmail = '';
    public $authorizerPassword = '';
    public $selectedAuthMethod = 'password'; // password, user
    public $isProcessing = false;

    protected $listeners = [
        'requestTempAuth' => 'showAuthModal',
        'checkTempAuth' => 'checkAuthorization'
    ];

    public function mount()
    {
        // Load config values
        $this->authMode = config('temp_auth.mode', 'mixed');
        $this->authDurationMinutes = config('temp_auth.default_duration', 30);
        $this->authPassword = config('temp_auth.password.default_password', 'admin123');

        // Set default auth method based on mode
        if ($this->authMode === 'password') {
            $this->selectedAuthMethod = 'password';
        } elseif ($this->authMode === 'user') {
            $this->selectedAuthMethod = 'user';
        }

        // User mode initialization (no longer loads authorizer list for security)

        // Check if there's existing authorization in session
        $this->checkExistingAuth();
    }

    public function showAuthModal($targetComponent = '')
    {
        \Illuminate\Support\Facades\Log::info('showAuthModal called', [
            'targetComponent' => $targetComponent,
            'showModal_before' => $this->showModal
        ]);

        $this->targetComponent = $targetComponent;
        $this->showModal = true;
        $this->reset(['password', 'reason', 'errorMessage']);

        \Illuminate\Support\Facades\Log::info('showAuthModal finished', [
            'showModal_after' => $this->showModal
        ]);
    }



    public function grantAuthorization()
    {
        $this->isProcessing = true;
        $this->errorMessage = '';

        try {
            // Validate common fields
            if (empty($this->reason)) {
                $this->errorMessage = 'Alasan autorisasi harus diisi';
                $this->isProcessing = false;
                return;
            }

            $authorizerUser = null;
            $authMethod = $this->selectedAuthMethod;

            // Handle authorization based on selected method
            if ($this->selectedAuthMethod === 'password') {
                if (!$this->validatePasswordAuth()) {
                    $this->isProcessing = false;
                    return;
                }
            } elseif ($this->selectedAuthMethod === 'user') {
                $authorizerUser = $this->validateUserAuth();
                if (!$authorizerUser) {
                    $this->isProcessing = false;
                    return;
                }
            } else {
                $this->errorMessage = 'Method autorisasi tidak valid';
                $this->isProcessing = false;
                return;
            }

            // Grant authorization
            $this->grantAuthorizationInternal($authorizerUser, $authMethod);
        } catch (\Exception $e) {
            $this->errorMessage = 'Terjadi kesalahan: ' . $e->getMessage();
            $this->isProcessing = false;
        }
    }

    protected function validatePasswordAuth(): bool
    {
        if (empty($this->password)) {
            $this->errorMessage = 'Password harus diisi';
            return false;
        }

        \Illuminate\Support\Facades\Log::info('Password validation', [
            'input_password' => $this->password,
            'auth_password' => $this->authPassword,
            'match' => $this->password === $this->authPassword
        ]);

        if ($this->password !== $this->authPassword) {
            $this->errorMessage = 'Password tidak valid';
            return false;
        }

        return true;
    }

    protected function validateUserAuth(): ?User
    {
        if (empty($this->authorizerEmail)) {
            $this->errorMessage = 'Email/Username authorizer harus diisi';
            return null;
        }

        // Cari user berdasarkan email atau username (jika ada field username)
        $authorizer = User::where('email', $this->authorizerEmail)
            ->orWhere('name', $this->authorizerEmail) // fallback ke name field
            ->first();

        if (!$authorizer) {
            $this->errorMessage = 'User authorizer tidak ditemukan';
            return null;
        }

        // Check if user can grant authorization
        if (!$authorizer->canGrantTempAuthorization()) {
            $this->errorMessage = 'User tidak memiliki hak untuk memberikan autorisasi';
            return null;
        }

        // Check if authorizer can authorize this component
        if ($this->targetComponent && !$authorizer->canAuthorizeTempAccessFor($this->targetComponent)) {
            $this->errorMessage = 'User tidak memiliki hak untuk mengautorisasi komponen ini';
            return null;
        }

        // Validate authorizer password if required
        if (config('temp_auth.user.require_password', true)) {
            if (empty($this->authorizerPassword)) {
                $this->errorMessage = 'Password authorizer harus diisi';
                return null;
            }

            if (!Hash::check($this->authorizerPassword, $authorizer->password)) {
                $this->errorMessage = 'Password authorizer tidak valid';
                return null;
            }
        }

        return $authorizer;
    }

    protected function grantAuthorizationInternal(?User $authorizerUser, string $authMethod)
    {
        // Set authorization
        $this->authorized = true;
        $this->authExpiry = Carbon::now()->addMinutes($this->authDurationMinutes);

        // Store in session
        session([
            'temp_auth_authorized' => true,
            'temp_auth_expiry' => $this->authExpiry,
            'temp_auth_reason' => $this->reason,
            'temp_auth_user' => auth()->user()->name,
            'temp_auth_authorizer' => $authorizerUser ? $authorizerUser->name : 'System',
            'temp_auth_method' => $authMethod,
            'temp_auth_time' => Carbon::now()
        ]);

        // Log to database if enabled
        if (config('temp_auth.audit.store_in_database', true)) {
            TempAuthLog::create([
                'user_id' => auth()->id(),
                'authorizer_user_id' => $authorizerUser?->id,
                'action' => 'granted',
                'component' => $this->targetComponent,
                'request_url' => request()->fullUrl(),
                'component_namespace' => $this->getComponentNamespace(),
                'request_method' => request()->method(),
                'referrer_url' => request()->header('referer'),
                'reason' => $this->reason,
                'duration_minutes' => $this->authDurationMinutes,
                'auth_method' => $authMethod,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'granted_at' => Carbon::now(),
                'expires_at' => $this->authExpiry,
                'metadata' => [
                    'target_component' => $this->targetComponent,
                    'requested_by' => auth()->user()->name,
                    'authorizer_name' => $authorizerUser?->name,
                    'request_headers' => $this->getFilteredHeaders(),
                    'session_id' => session()->getId(),
                ]
            ]);
        }

        // Emit success notification first
        $authorizerName = $authorizerUser ? $authorizerUser->name : 'System';
        $this->dispatch('success', "Autorisasi temporer berhasil diberikan oleh {$authorizerName} untuk {$this->authDurationMinutes} menit");

        // Emit event to target component
        $this->dispatch('tempAuthGranted', [
            'authorized' => true,
            'expiry' => $this->authExpiry,
            'reason' => $this->reason,
            'authorizer' => $authorizerUser ? $authorizerUser->name : 'System',
            'method' => $authMethod
        ]);

        // Mark processing as complete and close modal after short delay
        $this->isProcessing = false;

        // Dispatch JavaScript to close modal after showing success message
        $this->dispatch('authorizationSuccess', [
            'message' => "Autorisasi berhasil diberikan oleh {$authorizerName}",
            'closeDelay' => 1500 // 1.5 seconds
        ]);
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->isProcessing = false;
        $this->reset(['password', 'reason', 'errorMessage', 'authorizerEmail', 'authorizerPassword']);
    }

    public function forceCloseModal()
    {
        $this->showModal = false;
        $this->reset(['password', 'reason', 'errorMessage', 'authorizerEmail', 'authorizerPassword']);
    }

    public function switchAuthMethod($method)
    {
        $this->selectedAuthMethod = $method;
        $this->reset(['password', 'authorizerEmail', 'authorizerPassword', 'errorMessage']);
    }

    public function checkAuthorization()
    {
        $this->checkExistingAuth();
        return $this->authorized;
    }

    private function checkExistingAuth()
    {
        $sessionAuth = session('temp_auth_authorized', false);
        $sessionExpiry = session('temp_auth_expiry');

        if ($sessionAuth && $sessionExpiry) {
            $expiry = Carbon::parse($sessionExpiry);
            if (Carbon::now()->lessThan($expiry)) {
                $this->authorized = true;
                $this->authExpiry = $expiry;
                return;
            } else {
                // Authorization expired
                $this->revokeAuthorization();
            }
        }

        $this->authorized = false;
        $this->authExpiry = null;
    }

    public function revokeAuthorization()
    {
        $this->authorized = false;
        $this->authExpiry = null;

        // Log revoke to database if enabled
        if (config('temp_auth.audit.store_in_database', true)) {
            // Find the most recent granted authorization for this user
            $recentAuth = TempAuthLog::where('user_id', auth()->id())
                ->where('action', 'granted')
                ->whereNull('revoked_at')
                ->latest('granted_at')
                ->first();

            if ($recentAuth) {
                // Only update existing record with revoke information (no duplication)
                $recentAuth->update([
                    'revoked_at' => Carbon::now(),
                    'metadata' => array_merge($recentAuth->metadata ?? [], [
                        'revoked_by' => auth()->user()->name,
                        'revoked_reason' => 'Manual revoke by user',
                        'revoked_url' => request()->fullUrl(),
                        'revoked_ip' => request()->ip(),
                        'revoked_user_agent' => request()->userAgent(),
                        'revoked_session_id' => session()->getId(),
                    ])
                ]);

                \Illuminate\Support\Facades\Log::info('TempAuth: Authorization revoked', [
                    'log_id' => $recentAuth->id,
                    'user_id' => auth()->id(),
                    'component' => $recentAuth->component,
                    'revoked_by' => auth()->user()->name,
                    'revoked_at' => Carbon::now()->toISOString(),
                ]);
            }
        }

        // Clear session
        session()->forget([
            'temp_auth_authorized',
            'temp_auth_expiry',
            'temp_auth_reason',
            'temp_auth_user',
            'temp_auth_authorizer',
            'temp_auth_method',
            'temp_auth_time'
        ]);

        $this->dispatch('tempAuthRevoked');
        $this->dispatch('info', 'Autorisasi temporer telah dicabut');
    }

    public function getTimeRemaining()
    {
        if (!$this->authorized || !$this->authExpiry) {
            return null;
        }

        $now = Carbon::now();
        $expiry = Carbon::parse($this->authExpiry);

        if ($now->greaterThanOrEqualTo($expiry)) {
            $this->revokeAuthorization();
            return null;
        }

        return $expiry->diffForHumans($now, true);
    }

    /**
     * Get the namespace of the component that triggered the authorization
     */
    private function getComponentNamespace(): ?string
    {
        if (!$this->targetComponent) {
            return null;
        }

        // If targetComponent is already a full namespace, return it as is
        if (class_exists($this->targetComponent)) {
            return $this->targetComponent;
        }

        // Try to get component registry from Livewire
        try {
            $livewireManager = app('livewire');

            // Check if component is registered with Livewire
            if (method_exists($livewireManager, 'getComponent')) {
                try {
                    $component = $livewireManager->getComponent($this->targetComponent);
                    if ($component) {
                        return get_class($component);
                    }
                } catch (\Exception $e) {
                    // Component not found, continue to manual detection
                }
            }
        } catch (\Exception $e) {
            // Livewire manager not available, continue to manual detection
        }

        // Try common namespace patterns for this application
        $possibleNamespaces = [
            $this->targetComponent, // First try as-is
            "App\\Livewire\\{$this->targetComponent}",
            "App\\Livewire\\" . str_replace(['-', '_'], ['\\', '\\'], ucwords($this->targetComponent, '-_')),
            "App\\Http\\Livewire\\{$this->targetComponent}",
            "App\\Http\\Livewire\\" . str_replace(['-', '_'], ['\\', '\\'], ucwords($this->targetComponent, '-_')),
        ];

        foreach ($possibleNamespaces as $namespace) {
            if (class_exists($namespace)) {
                return $namespace;
            }
        }

        // If no class found, return the component name as is
        return $this->targetComponent;
    }

    /**
     * Get filtered request headers (exclude sensitive data)
     */
    private function getFilteredHeaders(): array
    {
        $headers = request()->headers->all();

        // Remove sensitive headers
        $sensitiveHeaders = [
            'authorization',
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
            'php-auth-user',
            'php-auth-pw'
        ];

        return array_filter($headers, function ($value, $key) use ($sensitiveHeaders) {
            return !in_array(strtolower($key), $sensitiveHeaders);
        }, ARRAY_FILTER_USE_BOTH);
    }

    public function render()
    {
        return view('livewire.temp-authorization');
    }
}
 
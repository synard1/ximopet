<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\TempAuthAuthorizer;
use App\Models\TempAuthLog;

class ManageTempAuthCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'temp-auth:manage 
                            {action : Action to perform (list, grant, revoke, cleanup, active, logs)}
                            {--user= : User email for grant/revoke actions}
                            {--authorizer= : Authorizer email for grant action}
                            {--duration= : Max duration in minutes for grant action}
                            {--components= : Allowed components (comma separated)}
                            {--notes= : Notes for grant action}
                            {--limit=10 : Number of records to show}
                            {--component= : Filter by component name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage temporary authorization authorizers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'list':
                $this->listAuthorizers();
                break;
            case 'grant':
                $this->grantAuthorization();
                break;
            case 'revoke':
                $this->revokeAuthorization();
                break;
            case 'cleanup':
                $this->cleanupExpired();
                break;
            case 'active':
                $this->showActiveAuthorizations();
                break;
            case 'logs':
                $this->showAuthorizationLogs();
                break;
            default:
                $this->error("Unknown action: {$action}");
                $this->info("Available actions: list, grant, revoke, cleanup, active, logs");
                return 1;
        }

        return 0;
    }

    protected function listAuthorizers()
    {
        $authorizers = TempAuthAuthorizer::with(['user', 'authorizedBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        $this->info("Found {$authorizers->count()} temp auth authorizers");

        if ($authorizers->isEmpty()) {
            $this->info('No temp auth authorizers found.');
            return;
        }

        $headers = ['ID', 'User', 'Email', 'Authorized By', 'Active', 'Max Duration', 'Components', 'Expires'];
        $rows = [];

        foreach ($authorizers as $auth) {
            $rows[] = [
                $auth->id,
                $auth->user ? $auth->user->name : 'N/A',
                $auth->user ? $auth->user->email : 'N/A',
                $auth->authorizedBy ? $auth->authorizedBy->name : 'N/A',
                $auth->is_active ? '✅' : '❌',
                $auth->max_authorization_duration ? $auth->max_authorization_duration . 'm' : 'No limit',
                $auth->allowed_components ? implode(', ', $auth->allowed_components) : 'All',
                $auth->expires_at ? $auth->expires_at->format('Y-m-d H:i') : 'Never',
            ];
        }

        $this->table($headers, $rows);
    }

    protected function grantAuthorization()
    {
        $userEmail = $this->option('user');
        $authorizerEmail = $this->option('authorizer');

        if (!$userEmail) {
            $userEmail = $this->ask('Enter user email to grant authorization to');
        }

        if (!$authorizerEmail) {
            $authorizerEmail = $this->ask('Enter authorizer email (who grants the authorization)');
        }

        $user = User::where('email', $userEmail)->first();
        if (!$user) {
            $this->error("User not found: {$userEmail}");
            return;
        }

        $authorizer = User::where('email', $authorizerEmail)->first();
        if (!$authorizer) {
            $this->error("Authorizer not found: {$authorizerEmail}");
            return;
        }

        // Check if already exists
        $existing = TempAuthAuthorizer::where('user_id', $user->id)->active()->first();
        if ($existing) {
            if (!$this->confirm("User already has active authorization. Replace it?")) {
                return;
            }
            $existing->update(['is_active' => false]);
        }

        $duration = $this->option('duration');
        if (!$duration) {
            $duration = $this->ask('Max authorization duration in minutes (leave empty for no limit)');
        }

        $components = $this->option('components');
        if (!$components) {
            $components = $this->ask('Allowed components (comma separated, leave empty for all)');
        }

        $notes = $this->option('notes');
        if (!$notes) {
            $notes = $this->ask('Notes (optional)');
        }

        $data = [
            'user_id' => $user->id,
            'authorized_by' => $authorizer->id,
            'is_active' => true,
            'can_authorize_self' => false,
            'max_authorization_duration' => $duration ?: null,
            'allowed_components' => $components ? explode(',', str_replace(' ', '', $components)) : null,
            'notes' => $notes,
            'authorized_at' => now(),
            'expires_at' => null, // Can be set later if needed
        ];

        TempAuthAuthorizer::create($data);

        $this->info("✅ Authorization granted to {$user->name} ({$user->email})");
        $this->info("   Authorized by: {$authorizer->name}");
        $this->info("   Max duration: " . ($duration ? $duration . ' minutes' : 'No limit'));
        $this->info("   Components: " . ($components ? $components : 'All'));
    }

    protected function revokeAuthorization()
    {
        $userEmail = $this->option('user');

        if (!$userEmail) {
            $userEmail = $this->ask('Enter user email to revoke authorization from');
        }

        $user = User::where('email', $userEmail)->first();
        if (!$user) {
            $this->error("User not found: {$userEmail}");
            return;
        }

        $authorizer = TempAuthAuthorizer::where('user_id', $user->id)->active()->first();
        if (!$authorizer) {
            $this->error("No active authorization found for {$user->email}");
            return;
        }

        $authorizer->update(['is_active' => false]);

        $this->info("✅ Authorization revoked from {$user->name} ({$user->email})");
    }

    protected function cleanupExpired()
    {
        $expiredCount = TempAuthAuthorizer::where('is_active', true)
            ->where('expires_at', '<', now())
            ->update(['is_active' => false]);

        $this->info("✅ Cleaned up {$expiredCount} expired authorizations");

        // Also cleanup old logs
        $oldLogsCount = TempAuthLog::where('created_at', '<', now()->subMonths(6))->count();

        if ($oldLogsCount > 0) {
            if ($this->confirm("Found {$oldLogsCount} logs older than 6 months. Delete them?")) {
                TempAuthLog::where('created_at', '<', now()->subMonths(6))->delete();
                $this->info("✅ Deleted {$oldLogsCount} old logs");
            }
        }
    }

    protected function showActiveAuthorizations()
    {
        $userEmail = $this->option('user');
        $component = $this->option('component');
        $limit = $this->option('limit');

        $query = TempAuthLog::with(['user', 'authorizerUser'])
            ->where('action', 'granted')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->orderBy('granted_at', 'desc');

        if ($userEmail) {
            $user = User::where('email', $userEmail)->first();
            if (!$user) {
                $this->error("User not found: {$userEmail}");
                return;
            }
            $query->where('user_id', $user->id);
        }

        if ($component) {
            $query->where(function ($q) use ($component) {
                $q->where('component', 'like', "%{$component}%")
                    ->orWhere('component_namespace', 'like', "%{$component}%");
            });
        }

        $activeAuths = $query->limit($limit)->get();

        $this->info("Found {$activeAuths->count()} active authorizations" . ($limit ? " (showing last {$limit})" : ""));

        if ($activeAuths->isEmpty()) {
            $this->info('No active authorizations found.');
            return;
        }

        $headers = ['ID', 'User', 'Component', 'Authorizer', 'Method', 'Granted At', 'Expires At', 'Time Left'];
        $rows = [];

        foreach ($activeAuths as $auth) {
            $timeLeft = $auth->expires_at ? $auth->expires_at->diffForHumans(null, true) : 'Never';

            $rows[] = [
                $auth->id,
                $auth->user ? $auth->user->name : 'N/A',
                $this->truncateString($auth->component_namespace ?: $auth->component, 30),
                $auth->authorizerUser ? $auth->authorizerUser->name : 'System',
                $auth->auth_method,
                $auth->granted_at ? $auth->granted_at->format('m-d H:i') : 'N/A',
                $auth->expires_at ? $auth->expires_at->format('m-d H:i') : 'Never',
                $timeLeft,
            ];
        }

        $this->table($headers, $rows);

        // Show additional info
        $this->newLine();
        $this->info("🔍 Filter options:");
        $this->info("  --user=email@domain.com    : Filter by specific user");
        $this->info("  --component=ComponentName  : Filter by component name");
        $this->info("  --limit=20                 : Change result limit");
    }

    protected function showAuthorizationLogs()
    {
        $userEmail = $this->option('user');
        $component = $this->option('component');
        $limit = $this->option('limit');

        $query = TempAuthLog::with(['user', 'authorizerUser'])
            ->orderBy('created_at', 'desc');

        if ($userEmail) {
            $user = User::where('email', $userEmail)->first();
            if (!$user) {
                $this->error("User not found: {$userEmail}");
                return;
            }
            $query->where('user_id', $user->id);
        }

        if ($component) {
            $query->where(function ($q) use ($component) {
                $q->where('component', 'like', "%{$component}%")
                    ->orWhere('component_namespace', 'like', "%{$component}%");
            });
        }

        $logs = $query->limit($limit)->get();

        $this->info("Found {$logs->count()} authorization logs" . ($limit ? " (showing last {$limit})" : ""));

        if ($logs->isEmpty()) {
            $this->info('No authorization logs found.');
            return;
        }

        $headers = ['ID', 'User', 'Action', 'Component', 'Method', 'URL', 'IP', 'Created At'];
        $rows = [];

        foreach ($logs as $log) {
            // Determine status based on revoked_at
            $status = $log->action;
            if ($log->action === 'granted' && $log->revoked_at) {
                $status = 'revoked';
            }

            $rows[] = [
                $log->id,
                $log->user ? $log->user->name : 'N/A',
                $this->getActionIcon($status) . ' ' . $status,
                $this->truncateString($log->component_namespace ?: $log->component, 25),
                $log->auth_method,
                $this->truncateString($log->request_url, 30),
                $log->ip_address,
                $log->created_at ? $log->created_at->format('m-d H:i:s') : 'N/A',
            ];
        }

        $this->table($headers, $rows);

        // Show statistics
        $this->newLine();

        // Calculate proper statistics
        $totalGranted = TempAuthLog::where('action', 'granted')->count();
        $totalRevoked = TempAuthLog::where('action', 'granted')->whereNotNull('revoked_at')->count();
        $totalExpired = TempAuthLog::where('action', 'granted')
            ->whereNull('revoked_at')
            ->where('expires_at', '<', now())
            ->count();
        $totalActive = TempAuthLog::where('action', 'granted')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->count();

        $this->info("📊 Statistics:");
        $this->info("  ✅ granted: {$totalGranted}");
        $this->info("  ❌ revoked: {$totalRevoked}");
        $this->info("  ⏰ expired: {$totalExpired}");
        $this->info("  🟢 active: {$totalActive}");

        $this->newLine();
        $this->info("🔍 Filter options:");
        $this->info("  --user=email@domain.com    : Filter by specific user");
        $this->info("  --component=ComponentName  : Filter by component name");
        $this->info("  --limit=20                 : Change result limit");
    }

    private function truncateString($string, $length)
    {
        if (strlen($string) <= $length) {
            return $string;
        }
        return substr($string, 0, $length - 3) . '...';
    }

    private function getActionIcon($action)
    {
        return match ($action) {
            'granted' => '✅',
            'revoked' => '❌',
            'expired' => '⏰',
            default => '📝'
        };
    }
}

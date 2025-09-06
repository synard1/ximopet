<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PermissionChecker;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class TestManualAccessValidation extends Command
{
    protected $signature = 'test:manual-access {user_id?}';
    protected $description = 'Manual test of access validation';

    public function handle()
    {
        $userId = $this->argument('user_id');

        if ($userId) {
            $user = User::find($userId);
        } else {
            $user = User::first();
        }

        if (!$user) {
            $this->error('No user found');
            return 1;
        }

        Auth::login($user);

        $this->info("Manual Access Validation Test");
        $this->info("User: {$user->name}");
        $this->info("Company ID: " . ($user->company_id ?: 'None'));
        $this->info("Roles: " . $user->getRoleNames()->join(', '));

        // Test permission checker directly
        $permissionChecker = app(PermissionChecker::class);

        $this->info("\n=== Testing Permission Checker ===");

        $dataTypes = ['company_list', 'livestock_data', 'financial_data', 'farm_data', 'supply_data'];

        foreach ($dataTypes as $dataType) {
            $hasAccess = $permissionChecker->canAccessData($user, $dataType);
            $status = $hasAccess ? '✅ ALLOWED' : '❌ DENIED';
            $this->line("{$dataType}: {$status}");
        }

        $this->info("\n=== Testing Access Validation Method ===");

        $testMessages = [
            'tampilkan semua data perusahaan yang ada',
            'berapa total ternak di perusahaan',
            'hi'
        ];

        foreach ($testMessages as $message) {
            $this->line("\nMessage: '{$message}'");

            // Use reflection to test private method
            $chatService = app(\App\Services\AiChatService::class);
            $reflection = new \ReflectionClass($chatService);
            $method = $reflection->getMethod('validateDatabaseAccess');
            $method->setAccessible(true);

            $result = $method->invoke($chatService, $user, $message);

            if ($result['has_access']) {
                $this->info("✅ Access granted");
                if (isset($result['allowed_types'])) {
                    $this->line("Allowed types: " . implode(', ', $result['allowed_types']));
                }
            } else {
                $this->warn("❌ Access denied");
                $this->line("Reason: " . $result['reason']);
                if (isset($result['suggestions'])) {
                    $this->line("Suggestions:");
                    foreach ($result['suggestions'] as $suggestion) {
                        $this->line("  • {$suggestion}");
                    }
                }
            }
        }

        $this->info("\n=== Test Complete ===");

        return 0;
    }
}

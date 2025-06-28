<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\CompanyUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncUserCompanyId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:sync-company-id {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync company_id from CompanyUser table to User table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
        }

        $this->info('Starting company_id sync from CompanyUser to User table...');

        // Get all active company user mappings
        $companyUsers = CompanyUser::where('status', 'active')
            ->with(['user', 'company'])
            ->get();

        $this->info("Found {$companyUsers->count()} active company user mappings");

        $updatedCount = 0;
        $skippedCount = 0;
        $errors = [];

        foreach ($companyUsers as $companyUser) {
            try {
                $user = $companyUser->user;
                $company = $companyUser->company;

                if (!$user) {
                    $this->warn("User not found for CompanyUser ID: {$companyUser->id}");
                    $skippedCount++;
                    continue;
                }

                if (!$company) {
                    $this->warn("Company not found for CompanyUser ID: {$companyUser->id}");
                    $skippedCount++;
                    continue;
                }

                // Check if user already has company_id set
                if ($user->company_id && $user->company_id !== $company->id) {
                    $this->warn("User {$user->name} (ID: {$user->id}) already has company_id: {$user->company_id}, but mapping shows: {$company->id}");
                    $skippedCount++;
                    continue;
                }

                if ($user->company_id === $company->id) {
                    $this->line("User {$user->name} already has correct company_id: {$company->id}");
                    $skippedCount++;
                    continue;
                }

                if (!$isDryRun) {
                    $user->update(['company_id' => $company->id]);
                    Log::info("Updated user company_id", [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'company_id' => $company->id,
                        'company_name' => $company->name
                    ]);
                }

                $this->info("✓ User {$user->name} -> Company {$company->name}");
                $updatedCount++;
            } catch (\Exception $e) {
                $error = "Error processing CompanyUser ID {$companyUser->id}: " . $e->getMessage();
                $this->error($error);
                $errors[] = $error;
                Log::error($error, ['exception' => $e]);
            }
        }

        $this->newLine();
        $this->info("Sync completed!");
        $this->info("Updated: {$updatedCount}");
        $this->info("Skipped: {$skippedCount}");

        if (!empty($errors)) {
            $this->error("Errors: " . count($errors));
            foreach ($errors as $error) {
                $this->error("  - {$error}");
            }
        }

        if ($isDryRun) {
            $this->warn("This was a dry run. Run without --dry-run to apply changes.");
        }

        return 0;
    }
}

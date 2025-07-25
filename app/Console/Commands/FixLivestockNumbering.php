<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Livestock;
use App\Services\Livestock\LivestockNumberGeneratorService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class FixLivestockNumbering extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'livestock:fix-numbering 
                            {--dry-run : Show what would be fixed without making changes}
                            {--limit=100 : Maximum number of records to process}
                            {--company-id= : Process only specific company ID}
                            {--farm-id= : Process only specific farm ID}
                            {--coop-id= : Process only specific coop ID}
                            {--date= : Use specific date for numbering (format: Y-m-d)}
                            {--force : Force update even if numbering exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix missing number and number_full fields for Livestock records';

    /**
     * Statistics for the command execution
     */
    private array $stats = [
        'total_processed' => 0,
        'total_fixed' => 0,
        'total_skipped' => 0,
        'total_errors' => 0,
        'companies_processed' => [],
        'farms_processed' => [],
        'errors' => []
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting Livestock Numbering Fix Process...');
        $this->info('');

        // Validate options
        $this->validateOptions();

        // Show configuration
        $this->showConfiguration();

        // Get and log SuperAdmin user for updated_by
        $superAdminUser = $this->getSuperAdminUser();
        $this->info("👤 Using SuperAdmin user for updates: {$superAdminUser->name} (ID: {$superAdminUser->id})");

        // Confirm execution
        if (!$this->confirmExecution()) {
            $this->info('❌ Process cancelled by user.');
            return 1;
        }

        // Process livestock records
        $this->processLivestockRecords();

        // Show results
        $this->showResults();

        // Log final statistics
        $this->logFinalStatistics();

        return 0;
    }

    /**
     * Validate command options
     */
    private function validateOptions(): void
    {
        $date = $this->option('date');
        if ($date && !Carbon::canParse($date)) {
            $this->error("❌ Invalid date format: {$date}. Use Y-m-d format (e.g., 2024-01-15)");
            exit(1);
        }

        $limit = (int) $this->option('limit');
        if ($limit <= 0) {
            $this->error("❌ Limit must be greater than 0");
            exit(1);
        }

        $this->info('✅ Options validation passed');
    }

    /**
     * Show current configuration
     */
    private function showConfiguration(): void
    {
        $this->info('📋 Configuration:');
        $this->line("   • Dry Run: " . ($this->option('dry-run') ? 'Yes' : 'No'));
        $this->line("   • Limit: " . $this->option('limit') . " records");
        $this->line("   • Company ID: " . ($this->option('company-id') ?: 'All'));
        $this->line("   • Farm ID: " . ($this->option('farm-id') ?: 'All'));
        $this->line("   • Coop ID: " . ($this->option('coop-id') ?: 'All'));
        $this->line("   • Date: " . ($this->option('date') ?: 'Current date'));
        $this->line("   • Force Update: " . ($this->option('force') ? 'Yes' : 'No'));
        $this->line("   • Verbose: " . ($this->output->isVerbose() ? 'Yes' : 'No'));
        $this->info('');
    }

    /**
     * Confirm execution with user
     */
    private function confirmExecution(): bool
    {
        if ($this->option('dry-run')) {
            $this->warn('⚠️  DRY RUN MODE: No changes will be made to the database');
            return true;
        }

        $this->warn('⚠️  This will update Livestock records in the database');
        return $this->confirm('Do you want to continue?', false);
    }

    /**
     * Process livestock records
     */
    private function processLivestockRecords(): void
    {
        $this->info('🔍 Querying Livestock records...');

        // Build query
        $query = Livestock::query()
            ->with(['farm', 'coop'])
            ->orderBy('created_at', 'asc');

        // Apply filters
        $this->applyFilters($query);

        // Apply numbering condition
        if (!$this->option('force')) {
            $query->where(function ($q) {
                $q->whereNull('number')
                    ->orWhere('number', '')
                    ->orWhereNull('number_full')
                    ->orWhere('number_full', '');
            });
        }

        // Get total count
        $totalCount = $query->count();
        $this->info("📊 Found {$totalCount} Livestock records to process");

        if ($totalCount === 0) {
            $this->info('✅ No records need fixing');
            return;
        }

        // Apply limit
        $limit = (int) $this->option('limit');
        $query->limit($limit);

        // Process records
        $this->processRecords($query->get());
    }

    /**
     * Apply filters to query
     */
    private function applyFilters($query): void
    {
        if ($companyId = $this->option('company-id')) {
            $query->where('company_id', $companyId);
            $this->stats['companies_processed'][] = $companyId;
        }

        if ($farmId = $this->option('farm-id')) {
            $query->where('farm_id', $farmId);
            $this->stats['farms_processed'][] = $farmId;
        }

        if ($coopId = $this->option('coop-id')) {
            $query->where('coop_id', $coopId);
        }
    }

    /**
     * Process individual records
     */
    private function processRecords($livestocks): void
    {
        $progressBar = $this->output->createProgressBar($livestocks->count());
        $progressBar->start();

        foreach ($livestocks as $livestock) {
            $this->stats['total_processed']++;

            try {
                $this->processSingleLivestock($livestock);
                $progressBar->advance();
            } catch (\Exception $e) {
                $this->stats['total_errors']++;
                $this->stats['errors'][] = [
                    'livestock_id' => $livestock->id,
                    'error' => $e->getMessage()
                ];

                if ($this->output->isVerbose()) {
                    $this->error("❌ Error processing Livestock ID {$livestock->id}: {$e->getMessage()}");
                }

                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->info('');
    }

    /**
     * Process single livestock record
     */
    private function processSingleLivestock(Livestock $livestock): void
    {
        // Check if numbering is needed
        if (!$this->needsNumbering($livestock)) {
            $this->stats['total_skipped']++;
            return;
        }

        // Generate numbering
        $numbering = $this->generateNumbering($livestock);

        if ($this->option('dry-run')) {
            $this->stats['total_fixed']++;
            $this->logDryRunResult($livestock, $numbering);
            return;
        }

        // Update livestock
        $this->updateLivestock($livestock, $numbering);
    }

    /**
     * Check if livestock needs numbering
     */
    private function needsNumbering(Livestock $livestock): bool
    {
        if ($this->option('force')) {
            return true;
        }

        return empty($livestock->number) ||
            empty($livestock->number_full) ||
            $livestock->number === '' ||
            $livestock->number_full === '';
    }

    /**
     * Generate numbering for livestock
     */
    private function generateNumbering(Livestock $livestock): array
    {
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : $livestock->start_date ?? now();

        try {
            $numbering = LivestockNumberGeneratorService::generateNumber('livestocks', $date, [
                'livestock_id' => $livestock->id,
                'farm_id' => $livestock->farm_id,
                'coop_id' => $livestock->coop_id,
                'company_id' => $livestock->company_id
            ]);

            return $numbering;
        } catch (\Exception $e) {
            throw new \Exception("Failed to generate numbering: {$e->getMessage()}");
        }
    }

    /**
     * Update livestock with new numbering
     */
    private function updateLivestock(Livestock $livestock, array $numbering): void
    {
        DB::beginTransaction();
        try {
            $oldNumber = $livestock->number;
            $oldNumberFull = $livestock->number_full;

            // Get SuperAdmin user for updated_by
            $superAdminUser = $this->getSuperAdminUser();

            $livestock->update([
                'number' => $numbering['number'],
                'number_full' => $numbering['full_number'],
                'updated_by' => $superAdminUser->id,
                'updated_at' => now()
            ]);

            $this->stats['total_fixed']++;

            // Log the update
            $this->logUpdate($livestock, $oldNumber, $oldNumberFull, $numbering, $superAdminUser);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception("Failed to update livestock: {$e->getMessage()}");
        }
    }

    /**
     * Log dry run result
     */
    private function logDryRunResult(Livestock $livestock, array $numbering): void
    {
        if ($this->output->isVerbose()) {
            $this->line("🔍 DRY RUN - Would fix Livestock ID {$livestock->id}:");
            $this->line("   • Current: number='{$livestock->number}', number_full='{$livestock->number_full}'");
            $this->line("   • Would set: number='{$numbering['number']}', number_full='{$numbering['full_number']}'");
        }
    }

    /**
     * Log update result
     */
    private function logUpdate(Livestock $livestock, $oldNumber, $oldNumberFull, array $numbering, User $updatedByUser): void
    {
        $logData = [
            'livestock_id' => $livestock->id,
            'livestock_name' => $livestock->name,
            'old_number' => $oldNumber,
            'old_number_full' => $oldNumberFull,
            'new_number' => $numbering['number'],
            'new_number_full' => $numbering['full_number'],
            'farm_id' => $livestock->farm_id,
            'coop_id' => $livestock->coop_id,
            'company_id' => $livestock->company_id,
            'updated_by' => $updatedByUser->id,
            'updated_by_name' => $updatedByUser->name,
            'updated_at' => now()->toDateTimeString()
        ];

        Log::info('🔧 Fixed Livestock numbering', $logData);

        if ($this->output->isVerbose()) {
            $this->line("✅ Fixed Livestock ID {$livestock->id}:");
            $this->line("   • Old: number='{$oldNumber}', number_full='{$oldNumberFull}'");
            $this->line("   • New: number='{$numbering['number']}', number_full='{$numbering['full_number']}'");
            $this->line("   • Updated by: {$updatedByUser->name} (ID: {$updatedByUser->id})");
        }
    }

    /**
     * Show final results
     */
    private function showResults(): void
    {
        $this->info('');
        $this->info('📊 Results Summary:');
        $this->info('');

        $this->line("   • Total Processed: {$this->stats['total_processed']}");
        $this->line("   • Total Fixed: {$this->stats['total_fixed']}");
        $this->line("   • Total Skipped: {$this->stats['total_skipped']}");
        $this->line("   • Total Errors: {$this->stats['total_errors']}");

        if (!empty($this->stats['companies_processed'])) {
            $this->line("   • Companies Processed: " . implode(', ', $this->stats['companies_processed']));
        }

        if (!empty($this->stats['farms_processed'])) {
            $this->line("   • Farms Processed: " . implode(', ', $this->stats['farms_processed']));
        }

        if ($this->stats['total_errors'] > 0) {
            $this->warn('');
            $this->warn("⚠️  {$this->stats['total_errors']} errors occurred. Check logs for details.");
        }

        if ($this->option('dry-run')) {
            $this->warn('');
            $this->warn('⚠️  DRY RUN MODE: No actual changes were made to the database');
        }

        $this->info('');
        $this->info('✅ Process completed successfully!');
    }

    /**
     * Get SuperAdmin user for updated_by field
     */
    private function getSuperAdminUser(): User
    {
        try {
            // Try to get SuperAdmin user
            $superAdmin = User::whereHas('roles', function ($query) {
                $query->where('name', 'SuperAdmin');
            })->first();

            if ($superAdmin) {
                return $superAdmin;
            }

            // Fallback: get first user if no SuperAdmin found
            $fallbackUser = User::first();
            if ($fallbackUser) {
                Log::warning('No SuperAdmin user found, using fallback user for updated_by', [
                    'fallback_user_id' => $fallbackUser->id,
                    'fallback_user_name' => $fallbackUser->name
                ]);
                return $fallbackUser;
            }

            // Last resort: return system user (ID 1)
            Log::error('No users found in system, using system user ID 1 for updated_by');
            return new User(['id' => 1, 'name' => 'System']);
        } catch (\Exception $e) {
            Log::error('Error getting SuperAdmin user for updated_by', [
                'error' => $e->getMessage()
            ]);
            // Return system user as fallback
            return new User(['id' => 1, 'name' => 'System']);
        }
    }

    /**
     * Log final statistics
     */
    private function logFinalStatistics(): void
    {
        $logData = [
            'command' => 'livestock:fix-numbering',
            'execution_time' => now()->toDateTimeString(),
            'options' => [
                'dry_run' => $this->option('dry-run'),
                'limit' => $this->option('limit'),
                'company_id' => $this->option('company-id'),
                'farm_id' => $this->option('farm-id'),
                'coop_id' => $this->option('coop-id'),
                'date' => $this->option('date'),
                'force' => $this->option('force'),
                'verbose' => $this->output->isVerbose()
            ],
            'statistics' => $this->stats
        ];

        Log::info('📈 Livestock numbering fix command completed', $logData);
    }
}

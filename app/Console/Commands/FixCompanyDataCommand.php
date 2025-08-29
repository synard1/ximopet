<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\User;
use App\Jobs\SyncCompanyDefaultMasterData;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixCompanyDataCommand extends Command
{
    protected $signature = 'company:fix-data
                            {company_id? : ID perusahaan yang akan difix (kosongkan untuk semua)}
                            {--force : Force update semua data}
                            {--sync-template : Sync ulang template data}
                            {--dry-run : Tampilkan perubahan tanpa mengeksekusi}';

    protected $description = 'Fix dan lengkapi data perusahaan yang kurang atau tidak valid';

    public function handle()
    {
        try {
            $companyId = $this->argument('company_id');
            $force = $this->option('force');
            $syncTemplate = $this->option('sync-template');
            $dryRun = $this->option('dry-run');

            // Get companies to process
            $query = Company::query();
            if ($companyId) {
                $query->where('id', $companyId);
            }

            $companies = $query->get();

            if ($companies->isEmpty()) {
                $this->error('No companies found to process.');
                return Command::FAILURE;
            }

            $this->info(sprintf(
                "Found %d %s to process%s",
                $companies->count(),
                Str::plural('company', $companies->count()),
                $dryRun ? ' (DRY RUN)' : ''
            ));

            // Process each company
            foreach ($companies as $company) {
                $this->processCompany($company, $force, $syncTemplate, $dryRun);
            }

            $this->newLine();
            $this->info('Processing completed successfully! 🎉');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            Log::error('Failed to fix company data', [
                'error' => $e->getMessage(),
                'company_id' => $companyId ?? 'all'
            ]);

            $this->error('Failed to fix company data: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    protected function processCompany(Company $company, bool $force, bool $syncTemplate, bool $dryRun): void
    {
        $this->newLine();
        $this->line("Processing company: {$company->name} ({$company->code})");

        $changes = [];
        $warnings = [];

        // Check and fix company code
        if (empty($company->code) || ($force && !$this->isValidCompanyCode($company->code))) {
            $newCode = $this->generateValidCompanyCode($company->name);
            $changes['code'] = [
                'from' => $company->code,
                'to' => $newCode
            ];
        }

        // Check and fix required fields
        $requiredFields = ['name', 'domain', 'database', 'type', 'package', 'status'];
        foreach ($requiredFields as $field) {
            if (empty($company->$field) || $force) {
                $newValue = $this->getDefaultValueForField($field, $company);
                if ($company->$field !== $newValue) {
                    $changes[$field] = [
                        'from' => $company->$field,
                        'to' => $newValue
                    ];
                }
            }
        }

        // Check and fix metadata
        if (empty($company->metadata) || $force) {
            $systemCompany = Company::where('code', 'SYSTEM')->first();
            if ($systemCompany) {
                $baseMetadata = $systemCompany->metadata ?? [];
                $newMetadata = $this->generateMetadata($company, $baseMetadata);

                if ($company->metadata != $newMetadata) {
                    $changes['metadata'] = [
                        'from' => json_encode($company->metadata),
                        'to' => json_encode($newMetadata)
                    ];
                }
            } else {
                $warnings[] = 'System company not found, skipping metadata fix';
            }
        }

        // Check admin user association
        $hasAdmin = $company->users()
            ->whereHas('roles', function($q) {
                $q->where('name', 'Administrator');
            })
            ->exists();

        if (!$hasAdmin) {
            $warnings[] = 'Company has no administrator user';
        }

        // Show changes
        if (!empty($changes)) {
            $this->info('Changes to be made:');
            foreach ($changes as $field => $change) {
                $this->line("- {$field}:");
                $this->line("  From: " . ($change['from'] ?: '(empty)'));
                $this->line("  To  : " . $change['to']);
            }
        } else {
            $this->info('No changes needed.');
        }

        // Show warnings
        if (!empty($warnings)) {
            $this->warn('Warnings:');
            foreach ($warnings as $warning) {
                $this->line("- {$warning}");
            }
        }

        // Apply changes if not dry run
        if (!empty($changes) && !$dryRun) {
            DB::beginTransaction();
            try {
                // Update company data
                $updateData = [];
                foreach ($changes as $field => $change) {
                    if ($field === 'metadata') {
                        $updateData[$field] = json_decode($change['to'], true);
                    } else {
                        $updateData[$field] = $change['to'];
                    }
                }

                if (!empty($updateData)) {
                    $updateData['updated_by'] = User::whereHas('companies', function($q) {
                        $q->where('companies.code', 'SYSTEM');
                    })->first()?->id;

                    $company->update($updateData);
                    $this->info('Changes applied successfully.');
                }

                // Re-sync template data if requested
                if ($syncTemplate) {
                    $this->info('Queuing template data sync...');
                    SyncCompanyDefaultMasterData::dispatch($company);
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        }
    }

    protected function isValidCompanyCode(string $code): bool
    {
        return strlen($code) <= 10 && preg_match('/^[A-Z0-9]+$/', $code);
    }

    protected function generateValidCompanyCode(string $name): string
    {
        $base = strtoupper(substr(str_replace([' ', '.'], '', $name), 0, 10));
        $code = $base;
        $counter = 1;

        while (Company::where('code', $code)->exists()) {
            $suffix = str_pad($counter++, 2, '0', STR_PAD_LEFT);
            $code = substr($base, 0, 8) . $suffix;
        }

        return $code;
    }

    protected function getDefaultValueForField(string $field, Company $company): string
    {
        return match($field) {
            'domain' => $company->domain ?: Str::slug($company->name) . '.example.com',
            'database' => $company->database ?: Str::slug($company->name),
            'type' => $company->type ?: 'client',
            'package' => $company->package ?: 'basic',
            'status' => $company->status ?: 'active',
            default => $company->$field
        };
    }

    protected function generateMetadata(Company $company, array $baseMetadata): array
    {
        return array_merge($baseMetadata, [
            'features' => [
                'basic_features' => true,
                'advanced_features' => $company->package !== 'basic',
                'premium_features' => $company->package === 'enterprise'
            ],
            'last_fixed_at' => now()->toISOString(),
            'fixed_by' => 'FixCompanyDataCommand',
            'config_source' => $company->metadata['config_source'] ?? 'FixCompanyDataCommand'
        ]);
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CheckCompanySetupProgress extends Command
{
    protected $signature = 'company:setup-progress {company_id : ID of the company}';
    protected $description = 'Check the progress of company template data setup';

    public function handle()
    {
        $companyId = $this->argument('company_id');
        $company = Company::find($companyId);

        if (!$company) {
            $this->error("Company with ID {$companyId} not found.");
            return Command::FAILURE;
        }

        $cacheKey = "company_setup_progress_{$companyId}";
        $progress = Cache::get($cacheKey);

        if (!$progress) {
            $this->warn("No progress data found for company {$company->name}");
            return Command::FAILURE;
        }

        $this->info("Setup Progress for {$company->name}:");
        $this->newLine();

        // Show progress bar
        $this->output->progressStart(100);
        $this->output->progressAdvance($progress['progress']);

        $this->newLine(2);
        $this->table(
            ['Stage', 'Message', 'Progress', 'Error'],
            [[
                $progress['stage'],
                $progress['message'],
                $progress['progress'] . '%',
                $progress['error'] ?? 'None'
            ]]
        );

        if ($progress['progress'] >= 100) {
            $this->info('✅ Template data generation completed!');
        } elseif ($progress['error']) {
            $this->error('❌ Template data generation failed!');
        } else {
            $this->info('⏳ Template data generation in progress...');
        }

        return Command::SUCCESS;
    }
}

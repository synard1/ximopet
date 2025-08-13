<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config as LaravelConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Company;
use App\Models\AppConfig;
use App\Config\company\CompanyConfigService;

class SystemConfigSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure SYSTEM company exists (reuse code from SystemCompanySeeder expectation)
        $code = config('app.template_company_code', env('TEMPLATE_COMPANY_CODE', 'SYSTEM'));

        $company = Company::where('code', $code)->first();
        if (!$company) {
            $company = Company::create([
                'id' => (string) Str::uuid(),
                'code' => $code,
                'name' => 'System Template',
                'type' => 'system',
                'status' => 'active',
                'is_locked' => true,
                'metadata' => [
                    'description' => 'Default template company for app-level configuration',
                    'created_by' => 'SystemConfigSeeder',
                ],
            ]);
        }

        // Seed AppConfig row with default template config to serve as system-level config
        $defaultTemplate = CompanyConfigService::getDefaultTemplateConfig();

        $appConfig = AppConfig::firstOrNew(['company_id' => $company->id]);
        $appConfig->config = $defaultTemplate;
        $appConfig->save();

        // Optional: store current company context for other seeders
        LaravelConfig::set('seeder.current_company_id', $company->id);

        $this->command->info('SystemConfigSeeder: seeded default template configuration for company code ' . $code);
    }
}

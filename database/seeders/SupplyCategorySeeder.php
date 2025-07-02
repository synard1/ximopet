<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SupplyCategory;
use App\Models\Company;
use Illuminate\Support\Str;

class SupplyCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Obat',
            'Vitamin',
            'Kimia',
            'Disinfektan',
            'Vaksin',
            'Antibiotik',
            'Nutrisi Tambahan',
            'OVK',  // Obat, Vitamin, dan Kimia
            'Lain - Lain',
        ];

        // Get company_id from config (for job-triggered seeding)
        $companyId = config('seeder.current_company_id');

        if ($companyId) {
            // Seed for specific company
            $this->seedForCompany($companyId, $categories);
        } else {
            // Seed for all companies (for manual seeding)
            $companies = Company::all();
            foreach ($companies as $company) {
                $this->seedForCompany($company->id, $categories);
            }

            // Update existing records with NULL company_id
            $this->updateExistingRecords($categories);
        }
    }

    private function seedForCompany(string $companyId, array $categories): void
    {
        foreach ($categories as $name) {
            SupplyCategory::firstOrCreate([
                'name' => $name,
                'company_id' => $companyId
            ], [
                'id' => Str::uuid(),
                'name' => $name,
                'company_id' => $companyId,
                'created_by' => $this->getDefaultUserId($companyId),
                'updated_by' => $this->getDefaultUserId($companyId),
            ]);
        }
    }

    private function updateExistingRecords(array $categories): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            foreach ($categories as $name) {
                SupplyCategory::where('name', $name)
                    ->whereNull('company_id')
                    ->update([
                        'company_id' => $company->id,
                        'created_by' => $this->getDefaultUserId($company->id),
                        'updated_by' => $this->getDefaultUserId($company->id),
                    ]);
            }
        }
    }

    private function getDefaultUserId(string $companyId): string
    {
        // Try to get first user for the company
        $user = \App\Models\User::where('company_id', $companyId)->first();

        if ($user) {
            return $user->id;
        }

        // Fallback: get any user from the system if no company-specific user found
        $anyUser = \App\Models\User::first();
        if ($anyUser) {
            return $anyUser->id;
        }

        // Last resort: return null if no users exist (will cause constraint error but shows the issue)
        throw new \Exception("No users found in database. Please create at least one user before running seeders.");
    }
}

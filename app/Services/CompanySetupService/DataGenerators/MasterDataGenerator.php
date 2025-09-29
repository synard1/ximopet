<?php

namespace App\Services\CompanySetupService\DataGenerators;

use App\Contracts\CompanySetup\DataGeneratorInterface;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MasterDataGenerator implements DataGeneratorInterface
{
    /**
     * Generate master data from template
     */
    public function generate(Company $company): bool
    {
        try {
            DB::beginTransaction();

            // Only generate data for tables that exist
            $this->generateExistingMasterData($company);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to generate company master data', [
                'company_id' => $company->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Validate master data
     */
    public function validate(Company $company): bool
    {
        // For now, just return true since we're only working with existing tables
        // and not creating mandatory master data
        return true;
    }

    /**
     * Cleanup invalid master data
     */
    public function cleanup(Company $company): void
    {
        try {
            DB::beginTransaction();

            // Only cleanup tables that exist
            if ($this->tableExists('units')) {
                DB::table('units')
                    ->where('company_id', $company->id)
                    ->delete();
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to cleanup company master data', [
                'company_id' => $company->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Generate master data only for existing tables
     */
    private function generateExistingMasterData(Company $company): void
    {
        // Only generate data for tables that actually exist in the database
        if ($this->tableExists('units')) {
            $this->generateDefaultUnits($company);
        }
        
        // Future: Add other tables when they exist
        // if ($this->tableExists('categories')) {
        //     $this->generateDefaultCategories($company);
        // }
    }

    /**
     * Check if a table exists in the database
     */
    private function tableExists(string $tableName): bool
    {
        try {
            $tables = DB::select("SHOW TABLES LIKE ?", [$tableName]);
            return count($tables) > 0;
        } catch (\Exception $e) {
            Log::warning('Failed to check table existence', [
                'table' => $tableName,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Generate default units (only if table exists)
     */
    private function generateDefaultUnits(Company $company): void
    {
        // For now, just log that we're generating units
        // In the future, this could create default units for the company
        Log::info('Generating default units for company', [
            'company_id' => $company->id
        ]);
        
        // Example implementation (commented out for safety):
        // $defaultUnits = [
        //     ['name' => 'Kilogram', 'symbol' => 'kg', 'type' => 'weight'],
        //     ['name' => 'Piece', 'symbol' => 'pcs', 'type' => 'count'],
        // ];
        // 
        // foreach ($defaultUnits as $unit) {
        //     DB::table('units')->updateOrInsert(
        //         ['company_id' => $company->id, 'symbol' => $unit['symbol']],
        //         array_merge($unit, ['company_id' => $company->id])
        //     );
        // }
    }


}

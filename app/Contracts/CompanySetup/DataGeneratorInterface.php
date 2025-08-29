<?php

namespace App\Contracts\CompanySetup;

use App\Models\Company;

interface DataGeneratorInterface
{
    /**
     * Generate template data for new company
     *
     * @param Company $company
     * @return bool
     */
    public function generate(Company $company): bool;

    /**
     * Validate generated data
     *
     * @param Company $company
     * @return bool
     */
    public function validate(Company $company): bool;

    /**
     * Clean up if generation fails
     *
     * @param Company $company
     * @return void
     */
    public function cleanup(Company $company): void;
}

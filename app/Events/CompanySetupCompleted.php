<?php

namespace App\Events;

use App\Models\Company;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CompanySetupCompleted
{
    use Dispatchable, SerializesModels;

    public Company $company;
    public array $generatedData;

    public function __construct(Company $company, array $generatedData)
    {
        $this->company = $company;
        $this->generatedData = $generatedData;
    }
}

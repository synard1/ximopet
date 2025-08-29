<?php

namespace App\Events;

use App\Models\Company;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CompanySetupFailed
{
    use Dispatchable, SerializesModels;

    public Company $company;
    public \Exception $exception;

    public function __construct(Company $company, \Exception $exception)
    {
        $this->company = $company;
        $this->exception = $exception;
    }
}

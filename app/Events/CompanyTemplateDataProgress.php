<?php

namespace App\Events;

use App\Models\Company;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CompanyTemplateDataProgress
{
    use Dispatchable, SerializesModels;

    public Company $company;
    public string $stage;
    public string $message;
    public int $progress;
    public ?string $error;

    public function __construct(Company $company, string $stage, string $message, int $progress, ?string $error = null)
    {
        $this->company = $company;
        $this->stage = $stage;
        $this->message = $message;
        $this->progress = $progress;
        $this->error = $error;
    }
}

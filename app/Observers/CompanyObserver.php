<?php

namespace App\Observers;

use App\Models\Company;
use App\Jobs\SyncCompanyDefaultMasterData;

class CompanyObserver
{
    /**
     * Handle the Company "created" event.
     */
    public function created(Company $company): void
    {
        // Dispatch a job so that heavy seeding is done asynchronously
        SyncCompanyDefaultMasterData::dispatch($company);
    }
}

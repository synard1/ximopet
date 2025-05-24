<?php

namespace App\Traits;

use App\Services\RoleBackupService;

trait DisableBackupDuringSeeding
{
    protected function disableBackups()
    {
        RoleBackupService::setSeeding(true);
    }

    protected function enableBackups()
    {
        RoleBackupService::setSeeding(false);
    }
}

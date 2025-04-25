<?php

namespace App\Traits;

use App\Models\Ternak;

trait TernakLockCheck
{
    public function isTernakLocked($ternakId)
    {
        $kelompokTernak = Ternak::find($ternakId);

        if (!$kelompokTernak) {
            return false;
        }

        return $kelompokTernak->status === 'Locked';
    }

    public function getTernakLockMessage($ternakId)
    {
        if ($this->isTernakLocked($ternakId)) {
            return "Data DOC di Lock";
        }

        return null;
    }
}
<?php

namespace App\Traits;

use App\Models\KelompokTernak;

trait KelompokTernakLockCheck
{
    public function isKelompokTernakLocked($kelompokTernakId)
    {
        $kelompokTernak = KelompokTernak::find($kelompokTernakId);

        if (!$kelompokTernak) {
            return false;
        }

        return $kelompokTernak->status === 'Locked';
    }

    public function getKelompokTernakLockMessage($kelompokTernakId)
    {
        if ($this->isKelompokTernakLocked($kelompokTernakId)) {
            return "Data DOC di Lock";
        }

        return null;
    }
}
<?php

namespace App\Traits;

use App\Models\Livestock;

trait LivestockLockCheck
{
    public function isLivestockLocked($livestockId)
    {
        $livestock = Livestock::find($livestockId);

        if (!$livestock) {
            return false;
        }

        return $livestock->status === 'Locked';
    }

    public function getLivestockLockMessage($livestockId)
    {
        if ($this->isLivestockLocked($livestockId)) {
            return "Data DOC di Lock";
        }

        return null;
    }
}
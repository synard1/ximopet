<?php

namespace App\Helpers;

use App\Config\SupplyUsageBypassConfig;

class SupplyUsageStatusHelper
{
    /**
     * Get allowed status transitions for a user/role and current status.
     *
     * @param string $role
     * @param string $currentStatus
     * @return array
     */
    public static function getAllowedStatusOptions(string $role, string $currentStatus): array
    {
        // Always include current status as selected
        $allowed = SupplyUsageBypassConfig::getAllowedTransitions($role, $currentStatus);
        if (!in_array($currentStatus, $allowed)) {
            array_unshift($allowed, $currentStatus);
        }
        return array_unique($allowed);
    }
}

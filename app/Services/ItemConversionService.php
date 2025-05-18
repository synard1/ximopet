<?php

namespace App\Services;

use App\Models\Feed;
use App\Models\Supply;

class ItemConversionService
{
    /**
     * Convert input quantity to the smallest unit
     *
     * @param string $type 'feed' or 'supply'
     * @param int $itemId
     * @param int $unitId
     * @param float $quantity
     * @return float
     */
    public static function toSmallest(string $type, $itemId, $unitId, float $quantity): float
    {
        $rate = 1;

        if ($type === 'feed') {
            $feed = Feed::findOrFail($itemId);
            $rate = $feed->conversionUnits()
                ->where('conversion_unit_id', $unitId)
                ->value('conversion_value') ?? 1;
        } elseif ($type === 'supply') {
            $supply = Supply::findOrFail($itemId);
            $rate = $supply->conversionUnits()
                ->where('conversion_unit_id', $unitId)
                ->value('conversion_value') ?? 1;
        }

        return $quantity * $rate;
    }
}

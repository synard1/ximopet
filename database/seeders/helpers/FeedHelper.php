<?php

namespace Database\Seeders\Helpers;

use App\Models\Feed;
use App\Models\Unit;
use App\Models\UnitConversion;

class FeedHelper
{
    /**
     * Membuat Feed beserta konversi satuan (data dan UnitConversion)
     *
     * @param string $code
     * @param string $name
     * @param Unit $unitKg
     * @param Unit $unitSak
     * @param int|string $createdBy
     * @param int|null $companyId
     * @return Feed
     */
    public static function createFeedWithConversions($code, $name, $unitKg, $unitSak, $createdBy, $companyId = null)
    {
        $data = [
            'unit_id' => $unitKg->id,
            'unit_details' => [
                'id' => $unitKg->id,
                'name' => $unitKg->name,
                'description' => $unitKg->description,
            ],
            'conversion_units' => [
                [
                    'unit_id' => $unitKg->id,
                    'unit_name' => $unitKg->name,
                    'value' => 1,
                    'is_default_purchase' => true,
                    'is_default_mutation' => true,
                    'is_default_sale' => true,
                    'is_smallest' => true,
                ],
                [
                    'unit_id' => $unitSak->id,
                    'unit_name' => $unitSak->name,
                    'value' => 50, // 1 SAK = 50 KG
                    'is_default_purchase' => false,
                    'is_default_mutation' => false,
                    'is_default_sale' => false,
                    'is_smallest' => false,
                ]
            ],
        ];

        $feed = Feed::create([
            'code' => $code,
            'name' => $name,
            'data' => $data,
            'created_by' => $createdBy,
            'company_id' => $companyId,
        ]);

        // KG -> KG
        UnitConversion::updateOrCreate(
            [
                'type' => 'Feed',
                'item_id' => $feed->id,
                'unit_id' => $unitKg->id,
                'conversion_unit_id' => $unitKg->id,
            ],
            [
                'conversion_value' => 1,
                'default_purchase' => true,
                'default_mutation' => true,
                'default_sale' => true,
                'smallest' => true,
                'created_by' => $createdBy,
            ]
        );

        // SAK -> KG
        UnitConversion::updateOrCreate(
            [
                'type' => 'Feed',
                'item_id' => $feed->id,
                'unit_id' => $unitKg->id,
                'conversion_unit_id' => $unitSak->id,
            ],
            [
                'conversion_value' => 50,
                'default_purchase' => false,
                'default_mutation' => false,
                'default_sale' => false,
                'smallest' => false,
                'created_by' => $createdBy,
            ]
        );

        return $feed;
    }
}

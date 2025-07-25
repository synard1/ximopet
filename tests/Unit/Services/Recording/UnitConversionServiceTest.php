<?php

namespace Tests\Unit\Services\Recording;

use Tests\TestCase;
use App\Services\Recording\UnitConversionService;
use App\Models\Feed;
use App\Models\Supply;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

class UnitConversionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Unit $kg;
    protected Unit $gram;
    protected Unit $ton;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test units
        $this->kg = Unit::factory()->create(['name' => 'Kilogram', 'id' => 1]);
        $this->gram = Unit::factory()->create(['name' => 'Gram', 'id' => 2]);
        $this->ton = Unit::factory()->create(['name' => 'Ton', 'id' => 3]);
    }

    /** @test */
    public function it_converts_feed_quantity_to_smallest_unit_correctly()
    {
        // Create a feed with conversion units
        $feed = Feed::factory()->create([
            'name' => 'Test Feed',
            'data' => [
                'conversion_units' => [
                    [
                        'unit_id' => $this->ton->id,
                        'value' => 1000,
                        'is_smallest' => false,
                        'is_default_purchase' => true,
                    ],
                    [
                        'unit_id' => $this->kg->id,
                        'value' => 1,
                        'is_smallest' => true,
                        'is_default_mutation' => true,
                    ],
                    [
                        'unit_id' => $this->gram->id,
                        'value' => 0.001,
                        'is_smallest' => false,
                    ],
                ]
            ]
        ]);

        // Test conversion from ton to kg (smallest unit)
        $result = UnitConversionService::getConvertedQuantityAndUnitId('feed', $feed->id, $this->ton->id, 2.5);

        $this->assertEquals(2500, $result['converted_quantity']); // 2.5 * 1000 = 2500 kg
        $this->assertEquals($this->kg->id, $result['converted_unit_id']);
    }

    /** @test */
    public function it_converts_supply_quantity_to_smallest_unit_correctly()
    {
        // Create a supply with conversion units
        $supply = Supply::factory()->create([
            'name' => 'Test Supply',
            'data' => [
                'conversion_units' => [
                    [
                        'unit_id' => $this->ton->id,
                        'value' => 1000,
                        'is_smallest' => false,
                        'is_default_purchase' => true,
                    ],
                    [
                        'unit_id' => $this->kg->id,
                        'value' => 1,
                        'is_smallest' => true,
                        'is_default_mutation' => true,
                    ],
                ]
            ]
        ]);

        // Test conversion from ton to kg (smallest unit)
        $result = UnitConversionService::getConvertedQuantityAndUnitId('supply', $supply->id, $this->ton->id, 1.5);

        $this->assertEquals(1500, $result['converted_quantity']); // 1.5 * 1000 = 1500 kg
        $this->assertEquals($this->kg->id, $result['converted_unit_id']);
    }

    /** @test */
    public function it_returns_same_quantity_when_unit_is_already_smallest()
    {
        $feed = Feed::factory()->create([
            'name' => 'Test Feed',
            'data' => [
                'conversion_units' => [
                    [
                        'unit_id' => $this->kg->id,
                        'value' => 1,
                        'is_smallest' => true,
                        'is_default_purchase' => true,
                    ],
                ]
            ]
        ]);

        $result = UnitConversionService::getConvertedQuantityAndUnitId('feed', $feed->id, $this->kg->id, 100);

        $this->assertEquals(100, $result['converted_quantity']);
        $this->assertEquals($this->kg->id, $result['converted_unit_id']);
    }

    /** @test */
    public function it_handles_feed_not_found_gracefully()
    {
        $result = UnitConversionService::getConvertedQuantityAndUnitId('feed', 99999, $this->kg->id, 100);

        $this->assertEquals(100, $result['converted_quantity']);
        $this->assertEquals($this->kg->id, $result['converted_unit_id']);
    }

    /** @test */
    public function it_handles_supply_not_found_gracefully()
    {
        $result = UnitConversionService::getConvertedQuantityAndUnitId('supply', 99999, $this->kg->id, 100);

        $this->assertEquals(100, $result['converted_quantity']);
        $this->assertEquals($this->kg->id, $result['converted_unit_id']);
    }

    /** @test */
    public function it_handles_missing_conversion_units_gracefully()
    {
        $feed = Feed::factory()->create([
            'name' => 'Test Feed',
            'data' => [] // No conversion units
        ]);

        $result = UnitConversionService::getConvertedQuantityAndUnitId('feed', $feed->id, $this->kg->id, 100);

        $this->assertEquals(100, $result['converted_quantity']);
        $this->assertEquals($this->kg->id, $result['converted_unit_id']);
    }

    /** @test */
    public function it_handles_invalid_unit_values_gracefully()
    {
        $feed = Feed::factory()->create([
            'name' => 'Test Feed',
            'data' => [
                'conversion_units' => [
                    [
                        'unit_id' => $this->ton->id,
                        'value' => 0, // Invalid value
                        'is_smallest' => false,
                    ],
                    [
                        'unit_id' => $this->kg->id,
                        'value' => 1,
                        'is_smallest' => true,
                    ],
                ]
            ]
        ]);

        $result = UnitConversionService::getConvertedQuantityAndUnitId('feed', $feed->id, $this->ton->id, 100);

        $this->assertEquals(100, $result['converted_quantity']);
        $this->assertEquals($this->ton->id, $result['converted_unit_id']);
    }

    /** @test */
    public function it_handles_unknown_type_gracefully()
    {
        $result = UnitConversionService::getConvertedQuantityAndUnitId('unknown', 1, $this->kg->id, 100);

        $this->assertEquals(100, $result['converted_quantity']);
        $this->assertEquals($this->kg->id, $result['converted_unit_id']);
    }

    /** @test */
    public function it_uses_fallback_smallest_unit_when_not_marked()
    {
        $feed = Feed::factory()->create([
            'name' => 'Test Feed',
            'data' => [
                'conversion_units' => [
                    [
                        'unit_id' => $this->ton->id,
                        'value' => 1000,
                        'is_smallest' => false,
                    ],
                    [
                        'unit_id' => $this->kg->id,
                        'value' => 1,
                        'is_smallest' => false, // Not marked as smallest
                    ],
                    [
                        'unit_id' => $this->gram->id,
                        'value' => 0.001,
                        'is_smallest' => false,
                    ],
                ]
            ]
        ]);

        $result = UnitConversionService::getConvertedQuantityAndUnitId('feed', $feed->id, $this->ton->id, 2);

        // Should convert to gram (lowest value) since no unit is marked as smallest
        $this->assertEquals(2000000, $result['converted_quantity']); // 2 * 1000 / 0.001 = 2,000,000
        $this->assertEquals($this->gram->id, $result['converted_unit_id']);
    }

    /** @test */
    public function it_matches_feed_purchases_conversion_logic()
    {
        // This test verifies the conversion logic matches the pattern used in FeedPurchases
        $feed = Feed::factory()->create([
            'name' => 'Test Feed',
            'data' => [
                'conversion_units' => [
                    [
                        'unit_id' => $this->ton->id,
                        'value' => 1000,
                        'is_smallest' => false,
                        'is_default_purchase' => true,
                    ],
                    [
                        'unit_id' => $this->kg->id,
                        'value' => 1,
                        'is_smallest' => true,
                        'is_default_mutation' => true,
                    ],
                ]
            ]
        ]);

        $quantity = 2.5;
        $unitId = $this->ton->id;

        // Manual calculation matching FeedPurchases logic
        $units = collect($feed->data['conversion_units']);
        $selectedUnit = $units->firstWhere('unit_id', $unitId);
        $smallestUnit = $units->firstWhere('is_smallest', true);
        $convertedQuantity = ($quantity * $selectedUnit['value']) / $smallestUnit['value'];

        // Service calculation
        $result = UnitConversionService::getConvertedQuantityAndUnitId('feed', $feed->id, $unitId, $quantity);

        $this->assertEquals($convertedQuantity, $result['converted_quantity']);
        $this->assertEquals($smallestUnit['unit_id'], $result['converted_unit_id']);
    }

    /** @test */
    public function it_matches_supply_purchases_conversion_logic()
    {
        // This test verifies the conversion logic matches the pattern used in SupplyPurchases
        $supply = Supply::factory()->create([
            'name' => 'Test Supply',
            'data' => [
                'conversion_units' => [
                    [
                        'unit_id' => $this->ton->id,
                        'value' => 1000,
                        'is_smallest' => false,
                        'is_default_purchase' => true,
                    ],
                    [
                        'unit_id' => $this->kg->id,
                        'value' => 1,
                        'is_smallest' => true,
                        'is_default_mutation' => true,
                    ],
                ]
            ]
        ]);

        $quantity = 1.5;
        $unitId = $this->ton->id;

        // Manual calculation matching SupplyPurchases logic
        $units = collect($supply->data['conversion_units']);
        $selectedUnit = $units->firstWhere('unit_id', $unitId);
        $smallestUnit = $units->firstWhere('is_smallest', true);
        $convertedQuantity = ($quantity * $selectedUnit['value']) / $smallestUnit['value'];

        // Service calculation
        $result = UnitConversionService::getConvertedQuantityAndUnitId('supply', $supply->id, $unitId, $quantity);

        $this->assertEquals($convertedQuantity, $result['converted_quantity']);
        $this->assertEquals($smallestUnit['unit_id'], $result['converted_unit_id']);
    }
}

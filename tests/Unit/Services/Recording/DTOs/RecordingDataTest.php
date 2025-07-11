<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Recording\DTOs;

use App\Services\Recording\DTOs\RecordingData;
use App\Services\Recording\DTOs\ValidationResult;
use Carbon\Carbon;
use Tests\TestCase;

class RecordingDataTest extends TestCase
{
    public function test_creates_recording_data_with_required_parameters()
    {
        $date = Carbon::parse('2024-01-15');
        $recordingData = new RecordingData(
            livestockId: 1,
            date: $date,
            age: 30,
            bodyWeight: 2.5,
            mortality: 5,
            culling: 2,
            sale: 0,
            transfer: 0
        );

        $this->assertEquals(1, $recordingData->livestockId);
        $this->assertEquals($date, $recordingData->date);
        $this->assertEquals(30, $recordingData->age);
        $this->assertEquals(2.5, $recordingData->bodyWeight);
        $this->assertEquals(5, $recordingData->mortality);
        $this->assertEquals(2, $recordingData->culling);
        $this->assertEquals(0, $recordingData->sale);
        $this->assertEquals(0, $recordingData->transfer);
    }

    public function test_creates_from_array()
    {
        $data = [
            'livestock_id' => 1,
            'date' => '2024-01-15',
            'age' => 30,
            'body_weight' => 2.5,
            'mortality' => 5,
            'culling' => 2,
            'sale' => 0,
            'transfer' => 0,
            'feed_usages' => [1 => 10.5, 2 => 5.0],
            'supply_usages' => [1 => 2.0],
            'metadata' => ['source' => 'test']
        ];

        $recordingData = RecordingData::fromArray($data);

        $this->assertEquals(1, $recordingData->livestockId);
        $this->assertEquals(Carbon::parse('2024-01-15'), $recordingData->date);
        $this->assertEquals(30, $recordingData->age);
        $this->assertEquals(2.5, $recordingData->bodyWeight);
        $this->assertEquals([1 => 10.5, 2 => 5.0], $recordingData->feedUsages);
        $this->assertEquals([1 => 2.0], $recordingData->supplyUsages);
        $this->assertEquals(['source' => 'test'], $recordingData->metadata);
    }

    public function test_creates_from_records_component()
    {
        $component = (object) [
            'livestockId' => 1,
            'date' => '2024-01-15',
            'age' => 30,
            'bodyWeight' => 2.5,
            'mortality' => 5,
            'culling' => 2,
            'sale' => 0,
            'transfer' => 0,
            'itemQuantities' => [1 => 10.5],
            'supplyQuantities' => [1 => 2.0]
        ];

        $recordingData = RecordingData::fromRecordsComponent($component);

        $this->assertEquals(1, $recordingData->livestockId);
        $this->assertEquals(30, $recordingData->age);
        $this->assertEquals([1 => 10.5], $recordingData->feedUsages);
        $this->assertEquals([1 => 2.0], $recordingData->supplyUsages);
        $this->assertEquals('records_component', $recordingData->metadata['source']);
    }

    public function test_creates_empty_recording_data()
    {
        $date = Carbon::parse('2024-01-15');
        $recordingData = RecordingData::empty(1, $date);

        $this->assertEquals(1, $recordingData->livestockId);
        $this->assertEquals($date, $recordingData->date);
        $this->assertEquals(0, $recordingData->age);
        $this->assertEquals(0.0, $recordingData->bodyWeight);
        $this->assertEquals(0, $recordingData->mortality);
        $this->assertEquals(0, $recordingData->culling);
        $this->assertEquals(0, $recordingData->sale);
        $this->assertEquals(0, $recordingData->transfer);
    }

    public function test_calculates_total_feed_consumption()
    {
        $recordingData = new RecordingData(
            livestockId: 1,
            date: Carbon::now(),
            age: 30,
            bodyWeight: 2.5,
            mortality: 0,
            culling: 0,
            sale: 0,
            transfer: 0,
            feedUsages: [1 => 10.5, 2 => 5.0, 3 => 2.5]
        );

        $this->assertEqualsWithDelta(18.0, $recordingData->getTotalFeedConsumption(), 0.01);
    }

    public function test_calculates_total_supply_consumption()
    {
        $recordingData = new RecordingData(
            livestockId: 1,
            date: Carbon::now(),
            age: 30,
            bodyWeight: 2.5,
            mortality: 0,
            culling: 0,
            sale: 0,
            transfer: 0,
            supplyUsages: [1 => 2.0, 2 => 1.5]
        );

        $this->assertEqualsWithDelta(3.5, $recordingData->getTotalSupplyConsumption(), 0.01);
    }

    public function test_calculates_total_depletion()
    {
        $recordingData = new RecordingData(
            livestockId: 1,
            date: Carbon::now(),
            age: 30,
            bodyWeight: 2.5,
            mortality: 5,
            culling: 2,
            sale: 3,
            transfer: 1
        );

        $this->assertEquals(11, $recordingData->getTotalDepletion());
    }

    public function test_calculates_weight_gain()
    {
        $recordingData = new RecordingData(
            livestockId: 1,
            date: Carbon::now(),
            age: 30,
            bodyWeight: 2.5,
            mortality: 0,
            culling: 0,
            sale: 0,
            transfer: 0,
            metadata: ['weight_gain' => 0.15]
        );

        // getWeightGain() returns bodyWeight if no specific weight_gain in metadata
        $this->assertEqualsWithDelta(2.5, $recordingData->getWeightGain(), 0.01);
    }

    public function test_calculates_feed_conversion_ratio()
    {
        $recordingData = new RecordingData(
            livestockId: 1,
            date: Carbon::now(),
            age: 30,
            bodyWeight: 2.5,
            mortality: 0,
            culling: 0,
            sale: 0,
            transfer: 0,
            feedUsages: [1 => 10.0],
            metadata: ['weight_gain' => 2.5]
        );

        // FCR = total_feed / weight_gain = 10.0 / 2.5 = 4.0
        $this->assertEqualsWithDelta(4.0, $recordingData->getFeedConversionRatio(), 0.01);
    }

    public function test_calculates_feed_costs()
    {
        $recordingData = new RecordingData(
            livestockId: 1,
            date: Carbon::now(),
            age: 30,
            bodyWeight: 2.5,
            mortality: 0,
            culling: 0,
            sale: 0,
            transfer: 0,
            feedUsages: [1 => 10.0, 2 => 5.0],
            metadata: [
                'feed_costs' => [
                    1 => 1000.0,  // Rp 1000 per kg
                    2 => 1500.0   // Rp 1500 per kg
                ]
            ]
        );

        $expectedCost = (10.0 * 1000.0) + (5.0 * 1500.0); // 10000 + 7500 = 17500
        $this->assertEqualsWithDelta(17500.0, $recordingData->getFeedCost(), 0.01);
    }

    public function test_calculates_supply_costs()
    {
        $recordingData = new RecordingData(
            livestockId: 1,
            date: Carbon::now(),
            age: 30,
            bodyWeight: 2.5,
            mortality: 0,
            culling: 0,
            sale: 0,
            transfer: 0,
            supplyUsages: [1 => 2.0, 2 => 1.0],
            metadata: [
                'supply_costs' => [
                    1 => 5000.0,  // Rp 5000 per unit
                    2 => 3000.0   // Rp 3000 per unit
                ]
            ]
        );

        $expectedCost = (2.0 * 5000.0) + (1.0 * 3000.0); // 10000 + 3000 = 13000
        $this->assertEqualsWithDelta(13000.0, $recordingData->getSupplyCost(), 0.01);
    }

    public function test_calculates_total_cost()
    {
        $recordingData = new RecordingData(
            livestockId: 1,
            date: Carbon::now(),
            age: 30,
            bodyWeight: 2.5,
            mortality: 0,
            culling: 0,
            sale: 0,
            transfer: 0,
            feedUsages: [1 => 10.0],
            supplyUsages: [1 => 2.0],
            metadata: [
                'feed_costs' => [1 => 1000.0],
                'supply_costs' => [1 => 5000.0]
            ]
        );

        $expectedCost = (10.0 * 1000.0) + (2.0 * 5000.0); // 10000 + 10000 = 20000
        $this->assertEqualsWithDelta(20000.0, $recordingData->getTotalCost(), 0.01);
    }

    public function test_gets_feed_and_supply_usage()
    {
        $recordingData = new RecordingData(
            livestockId: 1,
            date: Carbon::now(),
            age: 30,
            bodyWeight: 2.5,
            mortality: 0,
            culling: 0,
            sale: 0,
            transfer: 0,
            feedUsages: [1 => 10.0, 2 => 5.0],
            supplyUsages: [1 => 2.0]
        );

        $this->assertEqualsWithDelta(10.0, $recordingData->getFeedUsage(1), 0.01);
        $this->assertEqualsWithDelta(5.0, $recordingData->getFeedUsage(2), 0.01);
        $this->assertEqualsWithDelta(0.0, $recordingData->getFeedUsage(3), 0.01); // Non-existent

        $this->assertEqualsWithDelta(2.0, $recordingData->getSupplyUsage(1), 0.01);
        $this->assertEqualsWithDelta(0.0, $recordingData->getSupplyUsage(2), 0.01); // Non-existent
    }

    public function test_checks_has_methods()
    {
        $recordingDataWithUsages = new RecordingData(
            livestockId: 1,
            date: Carbon::now(),
            age: 30,
            bodyWeight: 2.5,
            mortality: 5,
            culling: 0,
            sale: 0,
            transfer: 0,
            feedUsages: [1 => 10.0],
            supplyUsages: [1 => 2.0],
            performanceMetrics: ['fcr' => 2.0]
        );

        $this->assertTrue($recordingDataWithUsages->hasFeedUsage());
        $this->assertTrue($recordingDataWithUsages->hasSupplyUsage());
        $this->assertTrue($recordingDataWithUsages->hasDepletion());
        $this->assertTrue($recordingDataWithUsages->hasPerformanceMetrics());

        $emptyRecordingData = RecordingData::empty(1, Carbon::now());
        $this->assertFalse($emptyRecordingData->hasFeedUsage());
        $this->assertFalse($emptyRecordingData->hasSupplyUsage());
        $this->assertFalse($emptyRecordingData->hasDepletion());
        $this->assertFalse($emptyRecordingData->hasPerformanceMetrics());
    }

    public function test_validates_recording_data()
    {
        $validRecordingData = new RecordingData(
            livestockId: 1,
            date: Carbon::now(),
            age: 30,
            bodyWeight: 2.5,
            mortality: 5,
            culling: 2,
            sale: 0,
            transfer: 0,
            feedUsages: [1 => 10.0],
            supplyUsages: [1 => 2.0]
        );

        $result = $validRecordingData->validate();
        $this->assertTrue($result->isValid);

        $invalidRecordingData = new RecordingData(
            livestockId: -1, // Invalid
            date: Carbon::now(),
            age: -5, // Invalid
            bodyWeight: -2.5, // Invalid
            mortality: -1, // Invalid
            culling: 0,
            sale: 0,
            transfer: 0,
            feedUsages: [1 => -10.0], // Invalid
            supplyUsages: [1 => 2.0]
        );

        $result = $invalidRecordingData->validate();
        $this->assertFalse($result->isValid);
        $this->assertGreaterThan(0, count($result->errors));
    }

    public function test_converts_to_array()
    {
        $date = Carbon::parse('2024-01-15');
        $recordingData = new RecordingData(
            livestockId: 1,
            date: $date,
            age: 30,
            bodyWeight: 2.5,
            mortality: 5,
            culling: 2,
            sale: 0,
            transfer: 0,
            feedUsages: [1 => 10.0],
            supplyUsages: [1 => 2.0],
            metadata: ['source' => 'test']
        );

        $array = $recordingData->toArray();

        $this->assertEquals(1, $array['livestock_id']);
        $this->assertEquals('2024-01-15', $array['date']);
        $this->assertEquals(30, $array['age']);
        $this->assertEqualsWithDelta(2.5, $array['body_weight'], 0.01);
        $this->assertEquals([1 => 10.0], $array['feed_usages']);
        $this->assertEquals([1 => 2.0], $array['supply_usages']);
        $this->assertEquals(['source' => 'test'], $array['metadata']);
        $this->assertArrayHasKey('business_summary', $array);
    }

    public function test_converts_to_json()
    {
        $recordingData = RecordingData::empty(1, Carbon::parse('2024-01-15'));
        $json = $recordingData->toJson();

        $this->assertJson($json);
        $decoded = json_decode($json, true);
        $this->assertEquals(1, $decoded['livestock_id']);
        $this->assertEquals('2024-01-15', $decoded['date']);
    }

    public function test_metadata_operations()
    {
        $recordingData = new RecordingData(
            livestockId: 1,
            date: Carbon::now(),
            age: 30,
            bodyWeight: 2.5,
            mortality: 0,
            culling: 0,
            sale: 0,
            transfer: 0,
            metadata: ['key' => 'value', 'number' => 42]
        );

        $this->assertEquals('value', $recordingData->getMetadata('key'));
        $this->assertEquals(42, $recordingData->getMetadata('number'));
        $this->assertEquals('default', $recordingData->getMetadata('missing', 'default'));
        $this->assertTrue($recordingData->hasMetadata('key'));
        $this->assertFalse($recordingData->hasMetadata('missing'));
    }

    public function test_business_calculations()
    {
        $recordingData = new RecordingData(
            livestockId: 1,
            date: Carbon::now(),
            age: 30,
            bodyWeight: 2.5,
            mortality: 0,
            culling: 0,
            sale: 10,
            transfer: 0,
            feedUsages: [1 => 10.0],
            metadata: [
                'sale_price_per_kg' => 25000.0,
                'feed_costs' => [1 => 1000.0]
            ]
        );

        $this->assertEqualsWithDelta(625000.0, $recordingData->getRevenueEstimate(), 0.01);
        $this->assertEqualsWithDelta(615000.0, $recordingData->getProfitEstimate(), 0.01);
    }

    public function test_edge_cases()
    {
        // Test with zero values
        $recordingData = RecordingData::empty(1, Carbon::now());
        $this->assertEquals(0.0, $recordingData->getTotalFeedConsumption());
        $this->assertEquals(0.0, $recordingData->getTotalSupplyConsumption());
        $this->assertEquals(0, $recordingData->getTotalDepletion());
        $this->assertEquals(0.0, $recordingData->getFeedCost());
        $this->assertEquals(0.0, $recordingData->getSupplyCost());
        $this->assertEquals(0.0, $recordingData->getTotalCost());

        // Test with empty arrays
        $recordingData = new RecordingData(
            livestockId: 1,
            date: Carbon::now(),
            age: 30,
            bodyWeight: 2.5,
            mortality: 0,
            culling: 0,
            sale: 0,
            transfer: 0,
            feedUsages: [],
            supplyUsages: []
        );

        $this->assertFalse($recordingData->hasFeedUsage());
        $this->assertFalse($recordingData->hasSupplyUsage());
        $this->assertFalse($recordingData->hasDepletion());
    }

    public function test_debug_functionality()
    {
        $recordingData = new RecordingData(
            livestockId: 1,
            date: Carbon::parse('2024-01-15'),
            age: 30,
            bodyWeight: 2.5,
            mortality: 5,
            culling: 2,
            sale: 3,
            transfer: 1,
            feedUsages: [1 => 10.0],
            supplyUsages: [1 => 2.0],
            metadata: [
                'feed_costs' => [1 => 1000.0],
                'supply_costs' => [1 => 5000.0],
                'weight_gain' => 1.5
            ]
        );

        $debug = $recordingData->debug();

        $this->assertIsArray($debug);
        $this->assertArrayHasKey('basic_info', $debug);
        $this->assertArrayHasKey('depletion_info', $debug);
        $this->assertArrayHasKey('usage_info', $debug);
        $this->assertArrayHasKey('business_info', $debug);

        $this->assertEquals(1, $debug['basic_info']['livestock_id']);
        $this->assertEquals('2024-01-15', $debug['basic_info']['date']);
        $this->assertEquals(11, $debug['depletion_info']['total']);
        $this->assertEquals(1, $debug['usage_info']['feed_usage_count']);
        $this->assertEquals(1, $debug['usage_info']['supply_usage_count']);
    }
}

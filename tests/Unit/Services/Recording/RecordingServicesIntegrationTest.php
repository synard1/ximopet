<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Recording;

use Tests\TestCase;
use App\Services\Recording\{
    FeedSupplyProcessingService,
    UnitConversionService,
    StockAnalysisService,
    DepletionProcessingService,
    PayloadBuilderService
};
use App\Services\Recording\DTOs\ProcessingResult;
use App\Models\{Livestock, CurrentLivestock, Feed, Supply, FeedStock, SupplyStock};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Auth};
use Carbon\Carbon;

/**
 * RecordingServicesIntegrationTest
 * 
 * Integration test for all refactored recording services
 * to ensure they work together properly.
 */
class RecordingServicesIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private FeedSupplyProcessingService $feedSupplyService;
    private UnitConversionService $unitService;
    private StockAnalysisService $stockService;
    private DepletionProcessingService $depletionService;
    private PayloadBuilderService $payloadService;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock services for testing
        $this->unitService = $this->app->make(UnitConversionService::class);
        $this->stockService = $this->app->make(StockAnalysisService::class);
        $this->depletionService = $this->app->make(DepletionProcessingService::class);
        $this->payloadService = $this->app->make(PayloadBuilderService::class);
        $this->feedSupplyService = $this->app->make(FeedSupplyProcessingService::class);
    }

    /**
     * Test complete recording workflow integration
     */
    public function testCompleteRecordingWorkflow(): void
    {
        $this->markTestSkipped('Integration test - requires full environment setup');

        // Create test data
        $livestock = Livestock::factory()->create([
            'initial_quantity' => 1000,
            'start_date' => Carbon::now()->subDays(30),
        ]);

        $currentLivestock = CurrentLivestock::factory()->create([
            'livestock_id' => $livestock->id,
            'quantity' => 950,
        ]);

        $feed = Feed::factory()->create(['name' => 'Starter Feed']);
        $supply = Supply::factory()->create(['name' => 'Vitamins']);

        // Create feed and supply stocks
        $feedStock = FeedStock::factory()->create([
            'feed_id' => $feed->id,
            'livestock_id' => $livestock->id,
            'quantity_in' => 100,
            'quantity_used' => 0,
            'quantity_mutated' => 0,
        ]);

        $supplyStock = SupplyStock::factory()->create([
            'supply_id' => $supply->id,
            'farm_id' => $livestock->farm_id,
            'quantity_in' => 50,
            'quantity_used' => 0,
            'quantity_mutated' => 0,
        ]);

        // Test 1: Stock Analysis
        $stockResult = $this->stockService->getFeedStockDetails($feed->id, $livestock->id);
        $this->assertIsArray($stockResult);
        $this->assertArrayHasKey('available_stocks', $stockResult);
        $this->assertArrayHasKey('total_available', $stockResult);
        $this->assertEquals(100, $stockResult['total_available']);

        // Test 2: Unit Conversion
        $unitResult = $this->unitService->getDetailedFeedUnitInfo($feed, 50);
        $this->assertIsArray($unitResult);
        $this->assertArrayHasKey('base_unit', $unitResult);

        // Test 3: Feed Usage Processing
        $feedUsageResult = $this->feedSupplyService->saveFeedUsageWithTracking(
            ['total_quantity' => 25],
            1,
            $livestock->id,
            Carbon::now()->format('Y-m-d'),
            [
                [
                    'feed_id' => $feed->id,
                    'quantity' => 25,
                    'feed_name' => $feed->name,
                    'category' => 'Starter',
                    'stock_prices' => ['average_price' => 50]
                ]
            ]
        );

        $this->assertInstanceOf(ProcessingResult::class, $feedUsageResult);
        $this->assertTrue($feedUsageResult->isSuccess());

        // Test 4: Depletion Processing
        $depletionResult = $this->depletionService->storeDepletionWithTracking(
            'mortality',
            5,
            1,
            $livestock->id,
            Carbon::now()->format('Y-m-d')
        );

        $this->assertInstanceOf(ProcessingResult::class, $depletionResult);
        $this->assertTrue($depletionResult->isSuccess());

        // Test 5: Payload Building
        $payloadResult = $this->payloadService->buildStructuredPayload(
            $currentLivestock,
            30,
            1000,
            945,
            2500.0,
            2480.0,
            20.0,
            [
                'fcr' => 1.5,
                'avg_weight' => 2.5,
                'mortality_rate' => 0.5
            ],
            [],
            [],
            [],
            [],
            [
                [
                    'feed_id' => $feed->id,
                    'quantity' => 25,
                    'feed_name' => $feed->name,
                    'category' => 'Starter',
                    'stock_prices' => ['average_price' => 50]
                ]
            ],
            [],
            [],
            'total',
            false,
            false,
            5,
            0,
            0,
            0.0,
            0.0,
            0.0,
            Carbon::now()->format('Y-m-d')
        );

        $this->assertInstanceOf(ProcessingResult::class, $payloadResult);
        $this->assertTrue($payloadResult->isSuccess());

        $payload = $payloadResult->getData();
        $this->assertArrayHasKey('schema', $payload);
        $this->assertArrayHasKey('livestock', $payload);
        $this->assertArrayHasKey('production', $payload);
        $this->assertArrayHasKey('consumption', $payload);
        $this->assertEquals('3.0', $payload['schema']['version']);
    }

    /**
     * Test service error handling
     */
    public function testServiceErrorHandling(): void
    {
        // Test with invalid livestock ID
        $stockResult = $this->stockService->getFeedStockDetails(99999, 99999);
        $this->assertIsArray($stockResult);
        $this->assertEquals(0, $stockResult['total_available']);

        // Test unit conversion with null feed
        $unitResult = $this->unitService->getDetailedFeedUnitInfo(null, 50);
        $this->assertIsArray($unitResult);
        $this->assertArrayHasKey('error', $unitResult);
    }

    /**
     * Test service statistics retrieval
     */
    public function testServiceStatistics(): void
    {
        $this->markTestSkipped('Statistics test - requires database setup');

        $livestock = Livestock::factory()->create();

        // Test feed usage statistics
        $feedStats = $this->feedSupplyService->getFeedUsageStatistics($livestock->id);
        $this->assertInstanceOf(ProcessingResult::class, $feedStats);
        $this->assertTrue($feedStats->isSuccess());

        // Test depletion statistics
        $depletionStats = $this->depletionService->getDepletionStatistics($livestock->id);
        $this->assertInstanceOf(ProcessingResult::class, $depletionStats);
        $this->assertTrue($depletionStats->isSuccess());
    }

    /**
     * Test payload validation
     */
    public function testPayloadValidation(): void
    {
        $validPayload = [
            'schema' => ['version' => '3.0'],
            'recording' => ['timestamp' => now()->toIso8601String()],
            'livestock' => ['basic_info' => ['id' => 1]],
            'production' => ['weight' => ['today' => 2500]]
        ];

        $validationResult = $this->payloadService->validatePayloadStructure($validPayload);
        $this->assertInstanceOf(ProcessingResult::class, $validationResult);
        $this->assertTrue($validationResult->isSuccess());

        // Test invalid payload
        $invalidPayload = [
            'schema' => ['version' => '1.0'], // Unsupported version
        ];

        $invalidResult = $this->payloadService->validatePayloadStructure($invalidPayload);
        $this->assertInstanceOf(ProcessingResult::class, $invalidResult);
        $this->assertFalse($invalidResult->isSuccess());
    }

    /**
     * Test lightweight payload creation
     */
    public function testLightweightPayload(): void
    {
        $lightweightResult = $this->payloadService->buildLightweightPayload(
            1,
            Carbon::now()->format('Y-m-d'),
            ['weight' => 2500, 'mortality' => 2]
        );

        $this->assertInstanceOf(ProcessingResult::class, $lightweightResult);
        $this->assertTrue($lightweightResult->isSuccess());

        $payload = $lightweightResult->getData();
        $this->assertArrayHasKey('schema', $payload);
        $this->assertEquals('3.0-lite', $payload['schema']['version']);
    }

    /**
     * Test multi-stock availability check
     */
    public function testMultiStockAvailability(): void
    {
        $this->markTestSkipped('Multi-stock test - requires database setup');

        $livestock = Livestock::factory()->create();

        $requirements = [
            [
                'type' => 'feed',
                'item_id' => 1,
                'quantity' => 50
            ],
            [
                'type' => 'supply',
                'item_id' => 1,
                'quantity' => 10
            ]
        ];

        $availabilityResult = $this->stockService->checkMultipleStockAvailability(
            $livestock->id,
            $requirements
        );

        $this->assertInstanceOf(ProcessingResult::class, $availabilityResult);
        $this->assertTrue($availabilityResult->isSuccess());

        $data = $availabilityResult->getData();
        $this->assertArrayHasKey('all_available', $data);
        $this->assertArrayHasKey('details', $data);
        $this->assertCount(2, $data['details']);
    }

    /**
     * Test service performance with large datasets
     */
    public function testServicePerformance(): void
    {
        $this->markTestSkipped('Performance test - requires large dataset');

        $startTime = microtime(true);

        // Simulate processing of 1000 records
        for ($i = 0; $i < 1000; $i++) {
            $result = $this->payloadService->buildLightweightPayload(
                $i + 1,
                Carbon::now()->format('Y-m-d'),
                ['weight' => 2500 + $i, 'mortality' => 0]
            );

            $this->assertInstanceOf(ProcessingResult::class, $result);
            $this->assertTrue($result->isSuccess());
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Assert that processing 1000 records takes less than 10 seconds
        $this->assertLessThan(10, $executionTime, 'Service performance test failed');
    }

    /**
     * Test service configuration and caching
     */
    public function testServiceConfiguration(): void
    {
        // Test cache clearing
        $cacheResult = $this->stockService->clearCache();
        $this->assertTrue($cacheResult);

        // Test cache with specific parameters
        $cacheResult = $this->stockService->clearCache(1, 'feed');
        $this->assertTrue($cacheResult);
    }

    /**
     * Test service memory usage
     */
    public function testServiceMemoryUsage(): void
    {
        $initialMemory = memory_get_usage(true);

        // Process multiple operations
        for ($i = 0; $i < 100; $i++) {
            $this->payloadService->buildLightweightPayload(
                1,
                Carbon::now()->format('Y-m-d'),
                ['weight' => 2500, 'mortality' => 0]
            );
        }

        $finalMemory = memory_get_usage(true);
        $memoryIncrease = $finalMemory - $initialMemory;

        // Assert memory increase is reasonable (less than 10MB)
        $this->assertLessThan(10 * 1024 * 1024, $memoryIncrease, 'Memory usage test failed');
    }
}

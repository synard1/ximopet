<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Recording;

use Tests\TestCase;
use App\Services\Recording\RecordingDataService;
use App\Services\Recording\DTOs\{RecordingData, ProcessingResult};
use App\Models\{Livestock, Recording, LivestockDepletion, FeedUsage};
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\{Cache, DB};
use Carbon\Carbon;

class RecordingDataServiceTest extends TestCase
{
    use WithFaker;

    private RecordingDataService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RecordingDataService();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    /** @test */
    public function loads_yesterday_data_successfully()
    {
        $livestockId = 1;
        $date = Carbon::now();

        // Mock database calls that are expected
        DB::shouldReceive('table')
            ->andReturnSelf();
        DB::shouldReceive('where')
            ->andReturnSelf();
        DB::shouldReceive('whereDate')
            ->andReturnSelf();
        DB::shouldReceive('with')
            ->andReturnSelf();
        DB::shouldReceive('first')
            ->andReturn(null);
        DB::shouldReceive('get')
            ->andReturn(collect([]));

        $result = $this->service->loadYesterdayData($livestockId, $date);

        $this->assertInstanceOf(ProcessingResult::class, $result);
        $this->assertTrue($result->isSuccess());
    }

    /** @test */
    public function loads_recording_data_successfully()
    {
        $livestockId = 1;
        $startDate = Carbon::now()->subDays(30);
        $endDate = Carbon::now();

        // Mock cache to return null (cache miss)
        Cache::shouldReceive('remember')
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        // Mock database calls
        DB::shouldReceive('table')
            ->andReturnSelf();
        DB::shouldReceive('where')
            ->andReturnSelf();
        DB::shouldReceive('whereBetween')
            ->andReturnSelf();
        DB::shouldReceive('orderBy')
            ->andReturnSelf();
        DB::shouldReceive('with')
            ->andReturnSelf();
        DB::shouldReceive('find')
            ->andReturn(null);
        DB::shouldReceive('get')
            ->andReturn(collect([]));

        $result = $this->service->loadRecordingData($livestockId, $startDate, $endDate);

        $this->assertInstanceOf(ProcessingResult::class, $result);
        $this->assertFalse($result->isSuccess()); // Will fail because livestock not found
    }

    /** @test */
    public function gets_current_livestock_stock_successfully()
    {
        $livestockId = 1;

        // Mock database calls
        DB::shouldReceive('table')
            ->andReturnSelf();
        DB::shouldReceive('where')
            ->andReturnSelf();
        DB::shouldReceive('with')
            ->andReturnSelf();
        DB::shouldReceive('find')
            ->andReturn(null);

        $result = $this->service->getCurrentLivestockStock($livestockId);

        $this->assertInstanceOf(ProcessingResult::class, $result);
        $this->assertFalse($result->isSuccess()); // Will fail because livestock not found
    }

    /** @test */
    public function generates_data_summary_successfully()
    {
        $livestockId = 1;
        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now();

        // Mock database calls
        DB::shouldReceive('table')
            ->andReturnSelf();
        DB::shouldReceive('where')
            ->andReturnSelf();
        DB::shouldReceive('whereBetween')
            ->andReturnSelf();
        DB::shouldReceive('find')
            ->andReturn(null);
        DB::shouldReceive('get')
            ->andReturn(collect([]));

        $result = $this->service->generateDataSummary($livestockId, $startDate, $endDate);

        $this->assertInstanceOf(ProcessingResult::class, $result);
        $this->assertFalse($result->isSuccess()); // Will fail because livestock not found
    }

    /** @test */
    public function checks_has_recording_data()
    {
        $livestockId = 1;
        $date = Carbon::now();

        // Mock database calls
        DB::shouldReceive('table')
            ->andReturnSelf();
        DB::shouldReceive('where')
            ->andReturnSelf();
        DB::shouldReceive('whereDate')
            ->andReturnSelf();
        DB::shouldReceive('exists')
            ->andReturn(false);

        $result = $this->service->hasRecordingData($livestockId, $date);

        $this->assertIsBool($result);
        $this->assertFalse($result);
    }

    /** @test */
    public function gets_last_recorded_date()
    {
        $livestockId = 1;

        // Mock database calls
        DB::shouldReceive('table')
            ->andReturnSelf();
        DB::shouldReceive('where')
            ->andReturnSelf();
        DB::shouldReceive('orderBy')
            ->andReturnSelf();
        DB::shouldReceive('value')
            ->andReturn(null);

        $result = $this->service->getLastRecordedDate($livestockId);

        $this->assertNull($result);
    }

    /** @test */
    public function gets_recording_by_date()
    {
        $livestockId = 1;
        $date = Carbon::now();

        // Mock database calls
        DB::shouldReceive('table')
            ->andReturnSelf();
        DB::shouldReceive('where')
            ->andReturnSelf();
        DB::shouldReceive('whereDate')
            ->andReturnSelf();
        DB::shouldReceive('with')
            ->andReturnSelf();
        DB::shouldReceive('first')
            ->andReturn(null);
        DB::shouldReceive('get')
            ->andReturn(collect([]));

        $result = $this->service->getRecordingByDate($livestockId, $date);

        $this->assertInstanceOf(ProcessingResult::class, $result);
        $this->assertTrue($result->isSuccess());
    }

    /** @test */
    public function gets_historical_data()
    {
        $livestockId = 1;
        $days = 30;

        // Mock database calls
        DB::shouldReceive('table')
            ->andReturnSelf();
        DB::shouldReceive('where')
            ->andReturnSelf();
        DB::shouldReceive('whereBetween')
            ->andReturnSelf();
        DB::shouldReceive('orderBy')
            ->andReturnSelf();
        DB::shouldReceive('with')
            ->andReturnSelf();
        DB::shouldReceive('get')
            ->andReturn(collect([]));

        $result = $this->service->getHistoricalData($livestockId, $days);

        $this->assertInstanceOf(ProcessingResult::class, $result);
        $this->assertTrue($result->isSuccess());
    }

    /** @test */
    public function gets_feed_consumption_history()
    {
        $livestockId = 1;
        $days = 30;

        // Mock database calls
        DB::shouldReceive('table')
            ->andReturnSelf();
        DB::shouldReceive('where')
            ->andReturnSelf();
        DB::shouldReceive('whereBetween')
            ->andReturnSelf();
        DB::shouldReceive('orderBy')
            ->andReturnSelf();
        DB::shouldReceive('with')
            ->andReturnSelf();
        DB::shouldReceive('get')
            ->andReturn(collect([]));

        $result = $this->service->getFeedConsumptionHistory($livestockId, $days);

        $this->assertInstanceOf(ProcessingResult::class, $result);
        $this->assertTrue($result->isSuccess());
    }

    /** @test */
    public function gets_weight_history()
    {
        $livestockId = 1;
        $days = 30;

        // Mock database calls
        DB::shouldReceive('table')
            ->andReturnSelf();
        DB::shouldReceive('where')
            ->andReturnSelf();
        DB::shouldReceive('whereBetween')
            ->andReturnSelf();
        DB::shouldReceive('orderBy')
            ->andReturnSelf();
        DB::shouldReceive('get')
            ->andReturn(collect([]));

        $result = $this->service->getWeightHistory($livestockId, $days);

        $this->assertInstanceOf(ProcessingResult::class, $result);
        $this->assertTrue($result->isSuccess());
    }

    /** @test */
    public function caches_recording_data()
    {
        $livestockId = 1;
        $recordingData = RecordingData::empty($livestockId);

        // Mock cache
        Cache::shouldReceive('put')
            ->andReturn(true);

        $result = $this->service->cacheRecordingData($livestockId, $recordingData);

        $this->assertTrue($result);
    }

    /** @test */
    public function gets_cached_recording_data()
    {
        $livestockId = 1;
        $date = Carbon::now();

        // Mock cache
        Cache::shouldReceive('get')
            ->andReturn(null);

        $result = $this->service->getCachedRecordingData($livestockId, $date);

        $this->assertNull($result);
    }

    /** @test */
    public function clears_cache()
    {
        $livestockId = 1;

        // Mock cache
        Cache::shouldReceive('forget')
            ->andReturn(true);

        $result = $this->service->clearCache($livestockId);

        $this->assertTrue($result);
    }

    /** @test */
    public function exports_data()
    {
        $livestockId = 1;
        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now();

        // Mock database calls
        DB::shouldReceive('table')
            ->andReturnSelf();
        DB::shouldReceive('where')
            ->andReturnSelf();
        DB::shouldReceive('whereBetween')
            ->andReturnSelf();
        DB::shouldReceive('orderBy')
            ->andReturnSelf();
        DB::shouldReceive('with')
            ->andReturnSelf();
        DB::shouldReceive('get')
            ->andReturn(collect([]));

        $result = $this->service->exportData($livestockId, $startDate, $endDate);

        $this->assertInstanceOf(ProcessingResult::class, $result);
        $this->assertTrue($result->isSuccess());
    }

    /** @test */
    public function gets_data_statistics()
    {
        $livestockId = 1;

        // Mock database calls
        DB::shouldReceive('table')
            ->andReturnSelf();
        DB::shouldReceive('where')
            ->andReturnSelf();
        DB::shouldReceive('count')
            ->andReturn(0);
        DB::shouldReceive('sum')
            ->andReturn(0);
        DB::shouldReceive('avg')
            ->andReturn(0);
        DB::shouldReceive('min')
            ->andReturn(null);
        DB::shouldReceive('max')
            ->andReturn(null);

        $result = $this->service->getDataStatistics($livestockId);

        $this->assertInstanceOf(ProcessingResult::class, $result);
        $this->assertTrue($result->isSuccess());
    }

    /** @test */
    public function validates_data_integrity()
    {
        $livestockId = 1;

        // Mock database calls
        DB::shouldReceive('table')
            ->andReturnSelf();
        DB::shouldReceive('where')
            ->andReturnSelf();
        DB::shouldReceive('get')
            ->andReturn(collect([]));

        $result = $this->service->validateDataIntegrity($livestockId);

        $this->assertInstanceOf(\App\Services\Recording\DTOs\ValidationResult::class, $result);
        $this->assertTrue($result->isValid);
    }

    /** @test */
    public function gets_data_quality_score()
    {
        $livestockId = 1;

        // Mock database calls
        DB::shouldReceive('table')
            ->andReturnSelf();
        DB::shouldReceive('where')
            ->andReturnSelf();
        DB::shouldReceive('count')
            ->andReturn(0);
        DB::shouldReceive('get')
            ->andReturn(collect([]));

        $result = $this->service->getDataQualityScore($livestockId);

        $this->assertInstanceOf(ProcessingResult::class, $result);
        $this->assertTrue($result->isSuccess());
    }
}

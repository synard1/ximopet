<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Recording;

use Tests\TestCase;
use App\Services\Recording\RecordingCalculationService;
use App\Services\Recording\DTOs\RecordingData;
use App\Models\{Livestock, Recording, FeedUsage, LivestockDepletion};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * RecordingCalculationServiceTest
 * 
 * Comprehensive unit tests for RecordingCalculationService.
 * Tests all calculation scenarios for production readiness.
 */
class RecordingCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    private RecordingCalculationService $service;
    private Livestock $livestock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new RecordingCalculationService();
        
        // Create test livestock
        $this->livestock = Livestock::factory()->create([
            'initial_quantity' => 1000,
            'start_date' => Carbon::now()->subDays(30)
        ]);
    }

    /** @test */
    public function it_calculates_performance_metrics_successfully()
    {
        $recordingData = RecordingData::create([
            'livestockId' => $this->livestock->id,
            'date' => Carbon::now()->format('Y-m-d'),
            'age' => 25,
            'bodyWeight' => 1.5,
            'population' => 995,
            'userId' => 1,
            'companyId' => 1,
            'feedUsage' => [
                ['feed_id' => 1, 'quantity' => 3.0]
            ]
        ]);

        $result = $this->service->calculatePerformanceMetrics($recordingData->toArray());

        $this->assertTrue($result->isSuccess());
        $this->assertArrayHasKey('fcr', $result->getData());
        $this->assertArrayHasKey('adg', $result->getData());
        $this->assertArrayHasKey('mortality_rate', $result->getData());
        $this->assertArrayHasKey('performance_index', $result->getData());
    }

    /** @test */
    public function it_calculates_feed_conversion_ratio_correctly()
    {
        // Create historical recordings
        Recording::factory()->create([
            'livestock_id' => $this->livestock->id,
            'tanggal' => Carbon::now()->subDay(),
            'berat_hari_ini' => 1.0,
            'age' => 24
        ]);

        $recordingData = RecordingData::create([
            'livestockId' => $this->livestock->id,
            'date' => Carbon::now()->format('Y-m-d'),
            'age' => 25,
            'bodyWeight' => 1.5,
            'population' => 995,
            'userId' => 1,
            'companyId' => 1
        ]);

        // Create feed usage
        FeedUsage::factory()->create([
            'livestock_id' => $this->livestock->id,
            'usage_date' => Carbon::now()->subDay(),
            'total_quantity' => 2.0
        ]);

        $result = $this->service->calculateFeedConversionRatio($this->livestock->id, Carbon::now());

        $this->assertTrue($result->isSuccess());
        $this->assertArrayHasKey('fcr', $result->getData());
        $this->assertGreaterThan(0, $result->getData()['fcr']);
    }

    /** @test */
    public function it_calculates_average_daily_gain_correctly()
    {
        // Create historical recordings
        Recording::factory()->create([
            'livestock_id' => $this->livestock->id,
            'tanggal' => Carbon::now()->subDays(10),
            'berat_hari_ini' => 1.0,
            'age' => 15
        ]);

        Recording::factory()->create([
            'livestock_id' => $this->livestock->id,
            'tanggal' => Carbon::now()->subDays(5),
            'berat_hari_ini' => 1.25,
            'age' => 20
        ]);

        Recording::factory()->create([
            'livestock_id' => $this->livestock->id,
            'tanggal' => Carbon::now(),
            'berat_hari_ini' => 1.5,
            'age' => 25
        ]);

        $result = $this->service->calculateAverageDailyGain(
            $this->livestock->id,
            Carbon::now()->subDays(10),
            Carbon::now()
        );

        $this->assertTrue($result->isSuccess());
        $this->assertArrayHasKey('adg', $result->getData());
        $this->assertArrayHasKey('total_gain', $result->getData());
        $this->assertArrayHasKey('days', $result->getData());
        $this->assertGreaterThan(0, $result->getData()['adg']);
    }

    /** @test */
    public function it_calculates_mortality_rate_correctly()
    {
        // Create depletion records
        LivestockDepletion::factory()->create([
            'livestock_id' => $this->livestock->id,
            'tanggal' => Carbon::now()->subDays(5),
            'jenis' => 'mortality',
            'jumlah' => 10
        ]);

        LivestockDepletion::factory()->create([
            'livestock_id' => $this->livestock->id,
            'tanggal' => Carbon::now()->subDays(3),
            'jenis' => 'mortality',
            'jumlah' => 5
        ]);

        $result = $this->service->calculateMortalityRate(
            $this->livestock->id,
            Carbon::now()->subDays(7),
            Carbon::now()
        );

        $this->assertTrue($result->isSuccess());
        $this->assertArrayHasKey('mortality_rate', $result->getData());
        $this->assertArrayHasKey('total_mortality', $result->getData());
        $this->assertArrayHasKey('initial_population', $result->getData());
        $this->assertEquals(1.5, $result->getData()['mortality_rate']); // 15/1000 * 100
    }

    /** @test */
    public function it_calculates_feed_efficiency_correctly()
    {
        // Create recordings and feed usage
        Recording::factory()->create([
            'livestock_id' => $this->livestock->id,
            'tanggal' => Carbon::now()->subDays(7),
            'berat_hari_ini' => 1.0,
            'age' => 18
        ]);

        Recording::factory()->create([
            'livestock_id' => $this->livestock->id,
            'tanggal' => Carbon::now(),
            'berat_hari_ini' => 1.5,
            'age' => 25
        ]);

        FeedUsage::factory()->create([
            'livestock_id' => $this->livestock->id,
            'usage_date' => Carbon::now()->subDays(5),
            'total_quantity' => 15.0
        ]);

        $result = $this->service->calculateFeedEfficiency(
            $this->livestock->id,
            Carbon::now()->subDays(7),
            Carbon::now()
        );

        $this->assertTrue($result->isSuccess());
        $this->assertArrayHasKey('feed_efficiency', $result->getData());
        $this->assertArrayHasKey('weight_gain', $result->getData());
        $this->assertArrayHasKey('feed_consumed', $result->getData());
    }

    /** @test */
    public function it_calculates_cost_analysis_correctly()
    {
        $recordingData = RecordingData::create([
            'livestockId' => $this->livestock->id,
            'date' => Carbon::now()->format('Y-m-d'),
            'age' => 25,
            'bodyWeight' => 1.5,
            'population' => 995,
            'userId' => 1,
            'companyId' => 1,
            'feedUsage' => [
                ['feed_id' => 1, 'quantity' => 3.0, 'cost' => 15.0]
            ],
            'supplyUsage' => [
                ['supply_id' => 1, 'quantity' => 2.0, 'cost' => 10.0]
            ]
        ]);

        $result = $this->service->calculateCostAnalysis($recordingData->toArray());

        $this->assertTrue($result->isSuccess());
        $this->assertArrayHasKey('total_cost', $result->getData());
        $this->assertArrayHasKey('feed_cost', $result->getData());
        $this->assertArrayHasKey('supply_cost', $result->getData());
        $this->assertArrayHasKey('cost_per_unit', $result->getData());
        $this->assertEquals(25.0, $result->getData()['total_cost']);
    }

    /** @test */
    public function it_handles_zero_division_errors_gracefully()
    {
        // Test FCR calculation with zero weight gain
        $result = $this->service->calculateFeedConversionRatio($this->livestock->id, Carbon::now());

        $this->assertTrue($result->isSuccess());
        $this->assertEquals(0, $result->getData()['fcr']);
    }

    /** @test */
    public function it_calculates_performance_index_correctly()
    {
        // Create comprehensive test data
        Recording::factory()->create([
            'livestock_id' => $this->livestock->id,
            'tanggal' => Carbon::now()->subDays(25),
            'berat_hari_ini' => 0.5,
            'age' => 1
        ]);

        Recording::factory()->create([
            'livestock_id' => $this->livestock->id,
            'tanggal' => Carbon::now(),
            'berat_hari_ini' => 2.0,
            'age' => 25
        ]);

        FeedUsage::factory()->create([
            'livestock_id' => $this->livestock->id,
            'usage_date' => Carbon::now()->subDays(10),
            'total_quantity' => 30.0
        ]);

        LivestockDepletion::factory()->create([
            'livestock_id' => $this->livestock->id,
            'tanggal' => Carbon::now()->subDays(5),
            'jenis' => 'mortality',
            'jumlah' => 50
        ]);

        $recordingData = RecordingData::create([
            'livestockId' => $this->livestock->id,
            'date' => Carbon::now()->format('Y-m-d'),
            'age' => 25,
            'bodyWeight' => 2.0,
            'population' => 950,
            'userId' => 1,
            'companyId' => 1
        ]);

        $result = $this->service->calculatePerformanceIndex($recordingData->toArray());

        $this->assertTrue($result->isSuccess());
        $this->assertArrayHasKey('performance_index', $result->getData());
        $this->assertArrayHasKey('adg', $result->getData());
        $this->assertArrayHasKey('fcr', $result->getData());
        $this->assertArrayHasKey('survival_rate', $result->getData());
    }

    /** @test */
    public function it_calculates_growth_rate_correctly()
    {
        // Create weight progression
        Recording::factory()->create([
            'livestock_id' => $this->livestock->id,
            'tanggal' => Carbon::now()->subDays(20),
            'berat_hari_ini' => 1.0,
            'age' => 5
        ]);

        Recording::factory()->create([
            'livestock_id' => $this->livestock->id,
            'tanggal' => Carbon::now()->subDays(10),
            'berat_hari_ini' => 1.5,
            'age' => 15
        ]);

        Recording::factory()->create([
            'livestock_id' => $this->livestock->id,
            'tanggal' => Carbon::now(),
            'berat_hari_ini' => 2.0,
            'age' => 25
        ]);

        $result = $this->service->calculateGrowthRate(
            $this->livestock->id,
            Carbon::now()->subDays(20),
            Carbon::now()
        );

        $this->assertTrue($result->isSuccess());
        $this->assertArrayHasKey('growth_rate', $result->getData());
        $this->assertArrayHasKey('initial_weight', $result->getData());
        $this->assertArrayHasKey('final_weight', $result->getData());
        $this->assertArrayHasKey('growth_percentage', $result->getData());
    }

    /** @test */
    public function it_calculates_production_efficiency_correctly()
    {
        $recordingData = RecordingData::create([
            'livestockId' => $this->livestock->id,
            'date' => Carbon::now()->format('Y-m-d'),
            'age' => 25,
            'bodyWeight' => 1.5,
            'population' => 995,
            'userId' => 1,
            'companyId' => 1,
            'feedUsage' => [
                ['feed_id' => 1, 'quantity' => 3.0, 'cost' => 15.0]
            ]
        ]);

        $result = $this->service->calculateProductionEfficiency($recordingData->toArray());

        $this->assertTrue($result->isSuccess());
        $this->assertArrayHasKey('efficiency_score', $result->getData());
        $this->assertArrayHasKey('weight_per_feed', $result->getData());
        $this->assertArrayHasKey('cost_efficiency', $result->getData());
    }

    /** @test */
    public function it_handles_missing_data_gracefully()
    {
        // Test with minimal data
        $result = $this->service->calculatePerformanceMetrics([
            'livestockId' => $this->livestock->id,
            'date' => Carbon::now()->format('Y-m-d')
        ]);

        $this->assertFalse($result->isSuccess());
        $this->assertNotEmpty($result->getErrors());
    }

    /** @test */
    public function it_validates_calculation_parameters()
    {
        // Test with invalid livestock ID
        $result = $this->service->calculateFeedConversionRatio(999999, Carbon::now());

        $this->assertFalse($result->isSuccess());
        $this->assertStringContainsString('Livestock not found', $result->getMessage());
    }
} 
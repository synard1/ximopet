<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\AnalyticsService;
use App\Models\DailyAnalytics;
use App\Models\Farm;
use App\Models\Coop;
use App\Models\Livestock;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MortalityChartsTest extends TestCase
{
    use RefreshDatabase;

    protected AnalyticsService $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyticsService = new AnalyticsService();
    }

    /** @test */
    public function it_returns_all_farms_chart_when_no_filters_provided()
    {
        $chart = $this->analyticsService->getMortalityChartData([]);

        $this->assertIsArray($chart);
        $this->assertEquals('bar', $chart['type']);
        $this->assertEquals('Inter-Farm Mortality Comparison', $chart['title']);
        $this->assertArrayHasKey('labels', $chart);
        $this->assertArrayHasKey('datasets', $chart);
        $this->assertArrayHasKey('options', $chart);
    }

    /** @test */
    public function it_returns_single_farm_chart_when_farm_filter_provided()
    {
        $farm = Farm::factory()->create();

        $chart = $this->analyticsService->getMortalityChartData([
            'farm_id' => $farm->id
        ]);

        $this->assertIsArray($chart);
        $this->assertEquals('bar', $chart['type']);
        $this->assertEquals('Farm Mortality Comparison (by Coop)', $chart['title']);
        $this->assertArrayHasKey('labels', $chart);
        $this->assertArrayHasKey('datasets', $chart);
    }

    /** @test */
    public function it_returns_single_coop_chart_when_coop_filter_provided()
    {
        $coop = Coop::factory()->create();

        $chart = $this->analyticsService->getMortalityChartData([
            'coop_id' => $coop->id
        ]);

        $this->assertIsArray($chart);
        $this->assertEquals('line', $chart['type']);
        $this->assertEquals('Mortality Comparison - Single Coop', $chart['title']);
        $this->assertArrayHasKey('labels', $chart);
        $this->assertArrayHasKey('datasets', $chart);
    }

    /** @test */
    public function it_returns_single_livestock_chart_when_livestock_filter_provided()
    {
        $livestock = Livestock::factory()->create();

        $chart = $this->analyticsService->getMortalityChartData([
            'livestock_id' => $livestock->id
        ]);

        $this->assertIsArray($chart);
        $this->assertEquals('line', $chart['type']);
        $this->assertEquals('Daily Mortality Trend - Single Livestock', $chart['title']);
        $this->assertArrayHasKey('labels', $chart);
        $this->assertArrayHasKey('datasets', $chart);
    }

    /** @test */
    public function it_respects_date_range_filters()
    {
        $dateFrom = Carbon::now()->subDays(7);
        $dateTo = Carbon::now();

        $chart = $this->analyticsService->getMortalityChartData([
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ]);

        $this->assertIsArray($chart);
        $this->assertArrayHasKey('labels', $chart);
        $this->assertArrayHasKey('datasets', $chart);
        // Date range filter should be applied in the underlying query
    }

    /** @test */
    public function it_returns_empty_chart_data_on_exception()
    {
        // Force an exception by providing invalid livestock_id
        $chart = $this->analyticsService->getMortalityChartData([
            'livestock_id' => 99999 // Non-existent ID
        ]);

        $this->assertIsArray($chart);
        $this->assertEquals('line', $chart['type']);
        $this->assertEquals('Daily Mortality Trend - Single Livestock', $chart['title']);
        $this->assertEmpty($chart['labels']);
        $this->assertEmpty($chart['datasets']);
    }

    /** @test */
    public function chart_datasets_have_required_properties()
    {
        $chart = $this->analyticsService->getMortalityChartData([]);

        $this->assertArrayHasKey('datasets', $chart);

        foreach ($chart['datasets'] as $dataset) {
            $this->assertArrayHasKey('label', $dataset);
            $this->assertArrayHasKey('data', $dataset);
            $this->assertTrue(
                array_key_exists('backgroundColor', $dataset) ||
                    array_key_exists('borderColor', $dataset)
            );
        }
    }

    /** @test */
    public function chart_options_are_properly_configured()
    {
        $chart = $this->analyticsService->getMortalityChartData([]);

        $this->assertArrayHasKey('options', $chart);
        $this->assertArrayHasKey('responsive', $chart['options']);
        $this->assertTrue($chart['options']['responsive']);
    }

    /** @test */
    public function it_handles_multiple_filter_combinations()
    {
        $farm = Farm::factory()->create();
        $coop = Coop::factory()->for($farm)->create();
        $livestock = Livestock::factory()->for($coop)->for($farm)->create();

        $testCases = [
            'farm_only' => ['farm_id' => $farm->id],
            'coop_only' => ['coop_id' => $coop->id],
            'livestock_only' => ['livestock_id' => $livestock->id],
            'farm_and_date' => [
                'farm_id' => $farm->id,
                'date_from' => Carbon::now()->subDays(7),
                'date_to' => Carbon::now()
            ],
        ];

        foreach ($testCases as $testName => $filters) {
            $chart = $this->analyticsService->getMortalityChartData($filters);

            $this->assertIsArray($chart, "Failed for test case: $testName");
            $this->assertArrayHasKey('type', $chart, "Missing type for test case: $testName");
            $this->assertArrayHasKey('title', $chart, "Missing title for test case: $testName");
            $this->assertArrayHasKey('labels', $chart, "Missing labels for test case: $testName");
            $this->assertArrayHasKey('datasets', $chart, "Missing datasets for test case: $testName");
        }
    }

    /** @test */
    public function chart_data_types_are_correct_based_on_filters()
    {
        $farm = Farm::factory()->create();
        $coop = Coop::factory()->for($farm)->create();
        $livestock = Livestock::factory()->for($coop)->for($farm)->create();

        // Test different filter scenarios and expected chart types
        $scenarios = [
            ['filters' => [], 'expected_type' => 'bar'],
            ['filters' => ['farm_id' => $farm->id], 'expected_type' => 'bar'],
            ['filters' => ['coop_id' => $coop->id], 'expected_type' => 'line'],
            ['filters' => ['livestock_id' => $livestock->id], 'expected_type' => 'line'],
        ];

        foreach ($scenarios as $scenario) {
            $chart = $this->analyticsService->getMortalityChartData($scenario['filters']);
            $this->assertEquals(
                $scenario['expected_type'],
                $chart['type'],
                'Chart type mismatch for filters: ' . json_encode($scenario['filters'])
            );
        }
    }
}

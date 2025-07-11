<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Recording;

use Tests\TestCase;
use App\Services\Recording\RecordingValidationService;
use App\Services\Recording\DTOs\RecordingData;
use App\Models\{Livestock, Recording, FeedStock, SupplyStock, User};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

/**
 * RecordingValidationServiceTest
 * 
 * Comprehensive unit tests for RecordingValidationService.
 * Tests all validation scenarios for production readiness.
 */
class RecordingValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    private RecordingValidationService $service;
    private Livestock $livestock;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new RecordingValidationService();

        // Create test user
        $this->user = User::factory()->create([
            'company_id' => 1
        ]);

        // Create test livestock
        $this->livestock = Livestock::factory()->create([
            'status' => 'active',
            'initial_quantity' => 1000,
            'start_date' => Carbon::now()->subDays(30)->format('Y-m-d'),
            'farm_id' => 1
        ]);

        // Set authenticated user
        Auth::login($this->user);
    }

    /** @test */
    public function it_validates_basic_recording_data_successfully()
    {
        $recordingData = new RecordingData(
            livestockId: $this->livestock->id,
            date: Carbon::now(),
            age: 25,
            bodyWeight: 1.5,
            mortality: 0,
            culling: 0,
            sale: 0,
            transfer: 0,
            feedUsages: [],
            supplyUsages: [],
            depletionData: [],
            performanceMetrics: [],
            metadata: []
        );

        $result = $this->service->validateRecordingData($recordingData);

        $this->assertTrue($result->isValid);
        $this->assertEmpty($result->errors);
    }

    /** @test */
    public function it_fails_validation_for_missing_required_fields()
    {
        $recordingData = new RecordingData(
            livestockId: 0, // Invalid livestock ID
            date: Carbon::now(),
            age: 25,
            bodyWeight: 1.5,
            mortality: 0,
            culling: 0,
            sale: 0,
            transfer: 0
        );

        $result = $this->service->validateRecordingData($recordingData);

        $this->assertFalse($result->isValid);
        $this->assertNotEmpty($result->errors);
    }

    /** @test */
    public function it_validates_livestock_constraints_successfully()
    {
        $data = [
            'mortality' => 5,
            'culling' => 10,
            'sale' => 15,
            'transfer' => 0
        ];

        $result = $this->service->validateLivestockConstraints($this->livestock->id, $data);

        $this->assertTrue($result->isValid);
        $this->assertEmpty($result->errors);
    }

    /** @test */
    public function it_fails_validation_for_excessive_depletion()
    {
        $data = [
            'mortality' => 500,
            'culling' => 600,
            'sale' => 0,
            'transfer' => 0
        ];

        $result = $this->service->validateLivestockConstraints($this->livestock->id, $data);

        $this->assertFalse($result->isValid);
        $this->assertNotEmpty($result->errors);
    }

    /** @test */
    public function it_validates_business_rules_successfully()
    {
        $recordingData = new RecordingData(
            livestockId: $this->livestock->id,
            date: Carbon::now(),
            age: 25,
            bodyWeight: 1.5,
            mortality: 1,
            culling: 2,
            sale: 0,
            transfer: 0,
            feedUsages: [1 => 3.0],
            supplyUsages: [],
            depletionData: [],
            performanceMetrics: [],
            metadata: ['body_weight_yesterday' => 1.4]
        );

        $result = $this->service->validateBusinessRules($recordingData);

        $this->assertTrue($result->isValid);
    }

    /** @test */
    public function it_validates_basic_info_successfully()
    {
        $result = $this->service->validateBasicInfo(
            $this->livestock->id,
            Carbon::now(),
            25,
            1.5
        );

        $this->assertTrue($result->isValid);
        $this->assertEmpty($result->errors);
    }

    /** @test */
    public function it_fails_validation_for_negative_weight()
    {
        $result = $this->service->validateBasicInfo(
            $this->livestock->id,
            Carbon::now(),
            25,
            -1.5 // Negative weight
        );

        $this->assertFalse($result->isValid);
        $this->assertContains('Body weight must be positive', $result->errors);
    }

    /** @test */
    public function it_validates_feed_usage_successfully()
    {
        $feedUsages = [
            ['feed_id' => 1, 'quantity' => 3.0],
            ['feed_id' => 2, 'quantity' => 2.5]
        ];

        $result = $this->service->validateFeedUsage(
            $this->livestock->id,
            $feedUsages,
            Carbon::now()
        );

        $this->assertTrue($result->isValid);
        $this->assertEmpty($result->errors);
    }

    /** @test */
    public function it_fails_validation_for_invalid_feed_usage()
    {
        $feedUsages = [
            ['feed_id' => 1, 'quantity' => -3.0], // Negative quantity
            ['supply_id' => 2, 'quantity' => 2.5] // Wrong ID field
        ];

        $result = $this->service->validateFeedUsage(
            $this->livestock->id,
            $feedUsages,
            Carbon::now()
        );

        $this->assertFalse($result->isValid);
        $this->assertNotEmpty($result->errors);
    }

    /** @test */
    public function it_validates_supply_usage_successfully()
    {
        $supplyUsages = [
            ['supply_id' => 1, 'quantity' => 1.0],
            ['supply_id' => 2, 'quantity' => 0.5]
        ];

        $result = $this->service->validateSupplyUsage(
            $this->livestock->id,
            $supplyUsages,
            Carbon::now()
        );

        $this->assertTrue($result->isValid);
        $this->assertEmpty($result->errors);
    }

    /** @test */
    public function it_validates_depletion_data_successfully()
    {
        $result = $this->service->validateDepletionData(
            $this->livestock->id,
            5, // mortality
            10, // culling
            15, // sale
            0 // transfer
        );

        $this->assertTrue($result->isValid);
        $this->assertEmpty($result->errors);
    }

    /** @test */
    public function it_fails_validation_for_negative_depletion()
    {
        $result = $this->service->validateDepletionData(
            $this->livestock->id,
            -5, // negative mortality
            10,
            15,
            0
        );

        $this->assertFalse($result->isValid);
        $this->assertContains('Mortality count cannot be negative', $result->errors);
    }

    /** @test */
    public function it_validates_user_permissions_successfully()
    {
        $result = $this->service->validateUserPermissions(
            $this->livestock->id,
            'create'
        );

        $this->assertTrue($result->isValid);
        $this->assertEmpty($result->errors);
    }

    /** @test */
    public function it_validates_date_constraints_successfully()
    {
        $result = $this->service->validateDateConstraints(
            $this->livestock->id,
            Carbon::now()
        );

        $this->assertTrue($result->isValid);
        $this->assertEmpty($result->errors);
    }

    /** @test */
    public function it_fails_validation_for_future_date()
    {
        $result = $this->service->validateDateConstraints(
            $this->livestock->id,
            Carbon::now()->addDays(10) // Future date
        );

        $this->assertFalse($result->isValid);
        $this->assertNotEmpty($result->errors);
    }

    /** @test */
    public function it_validates_data_consistency_successfully()
    {
        $recordingData = new RecordingData(
            livestockId: $this->livestock->id,
            date: Carbon::now(),
            age: 25,
            bodyWeight: 1.5,
            mortality: 0,
            culling: 0,
            sale: 0,
            transfer: 0
        );

        $result = $this->service->validateDataConsistency(
            $this->livestock->id,
            Carbon::now(),
            $recordingData
        );

        $this->assertTrue($result->isValid);
    }

    /** @test */
    public function it_validates_stock_availability_successfully()
    {
        $feedUsages = [
            ['feed_id' => 1, 'quantity' => 3.0]
        ];
        $supplyUsages = [
            ['supply_id' => 1, 'quantity' => 1.0]
        ];

        $result = $this->service->validateStockAvailability(
            $this->livestock->id,
            $feedUsages,
            $supplyUsages
        );

        $this->assertTrue($result->isValid);
    }

    /** @test */
    public function it_validates_weight_progression_successfully()
    {
        $result = $this->service->validateWeightProgression(
            $this->livestock->id,
            1.5,
            Carbon::now()
        );

        $this->assertTrue($result->isValid);
    }

    /** @test */
    public function it_validates_mortality_rates_successfully()
    {
        $result = $this->service->validateMortalityRates(
            $this->livestock->id,
            5,
            Carbon::now()
        );

        $this->assertTrue($result->isValid);
    }

    /** @test */
    public function it_validates_feed_conversion_ratio_successfully()
    {
        $recordingData = new RecordingData(
            livestockId: $this->livestock->id,
            date: Carbon::now(),
            age: 25,
            bodyWeight: 1.5,
            mortality: 0,
            culling: 0,
            sale: 0,
            transfer: 0,
            feedUsages: [1 => 3.0],
            supplyUsages: [],
            depletionData: [],
            performanceMetrics: [],
            metadata: ['body_weight_yesterday' => 1.4]
        );

        $result = $this->service->validateFeedConversionRatio($recordingData);

        $this->assertTrue($result->isValid);
    }

    /** @test */
    public function it_validates_data_integrity_successfully()
    {
        $recordingData = new RecordingData(
            livestockId: $this->livestock->id,
            date: Carbon::now(),
            age: 25,
            bodyWeight: 1.5,
            mortality: 0,
            culling: 0,
            sale: 0,
            transfer: 0
        );

        $result = $this->service->validateDataIntegrity(
            $this->livestock->id,
            $recordingData
        );

        $this->assertTrue($result->isValid);
    }

    /** @test */
    public function it_gets_validation_rules_for_context()
    {
        $rules = $this->service->getValidationRules('basic');

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('livestock_id', $rules);
        $this->assertArrayHasKey('date', $rules);
        $this->assertArrayHasKey('body_weight', $rules);
    }

    /** @test */
    public function it_handles_validation_errors_gracefully()
    {
        // Test with non-existent livestock
        $result = $this->service->validateLivestockConstraints(99999, []);

        $this->assertFalse($result->isValid);
        $this->assertNotEmpty($result->errors);
    }
}

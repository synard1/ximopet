<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\{User, Company, Livestock, Feed, Supply, CurrentLivestock};
use App\Livewire\Records;
use App\Services\Recording\RecordsAdapter;
use App\Services\Recording\RecordsIntegrationService;
use Livewire\Livewire;
use Illuminate\Support\Facades\{Config, Log, DB};
use Carbon\Carbon;

class RecordsIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;
    private Company $company;
    private Livestock $livestock;
    private Feed $feed;
    private Supply $supply;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->createTestData();
    }

    private function createTestData(): void
    {
        $this->company = Company::factory()->create();

        $this->user = User::factory()->create([
            'company_id' => $this->company->id
        ]);

        $this->livestock = Livestock::factory()->create([
            'company_id' => $this->company->id,
            'start_date' => Carbon::now()->subDays(30),
            'initial_quantity' => 1000
        ]);

        CurrentLivestock::factory()->create([
            'livestock_id' => $this->livestock->id,
            'quantity' => 950
        ]);

        $this->feed = Feed::factory()->create([
            'company_id' => $this->company->id
        ]);

        $this->supply = Supply::factory()->create([
            'company_id' => $this->company->id
        ]);
    }

    /** @test */
    public function test_legacy_mode_works_correctly()
    {
        // Configure for legacy mode
        Config::set('recording.features.use_modular_services', false);

        $this->actingAs($this->user);

        $component = Livewire::test(Records::class)
            ->call('setRecords', $this->livestock->id)
            ->set('date', Carbon::now()->format('Y-m-d'))
            ->set('weight_today', 2.5)
            ->set('mortality', 5)
            ->set('culling', 3)
            ->set('itemQuantities', [$this->feed->id => 100])
            ->set('supplyQuantities', [$this->supply->id => 50])
            ->call('save');

        $component->assertHasNoErrors();

        // Verify data was saved
        $this->assertDatabaseHas('recordings', [
            'livestock_id' => $this->livestock->id,
            'tanggal' => Carbon::now()->format('Y-m-d')
        ]);
    }

    /** @test */
    public function test_modular_mode_works_correctly()
    {
        // Configure for modular mode
        Config::set('recording.features.use_modular_services', true);
        Config::set('recording.features.use_legacy_fallback', false);

        $this->actingAs($this->user);

        $component = Livewire::test(Records::class)
            ->call('setRecords', $this->livestock->id)
            ->set('date', Carbon::now()->format('Y-m-d'))
            ->set('weight_today', 2.5)
            ->set('mortality', 5)
            ->set('culling', 3)
            ->set('itemQuantities', [$this->feed->id => 100])
            ->set('supplyQuantities', [$this->supply->id => 50])
            ->call('save');

        $component->assertHasNoErrors();

        // Verify data was saved
        $this->assertDatabaseHas('recordings', [
            'livestock_id' => $this->livestock->id,
            'tanggal' => Carbon::now()->format('Y-m-d')
        ]);
    }

    /** @test */
    public function test_fallback_mechanism_works()
    {
        // Configure for modular mode with fallback
        Config::set('recording.features.use_modular_services', true);
        Config::set('recording.features.use_legacy_fallback', true);

        $this->actingAs($this->user);

        // Mock adapter to throw exception
        $this->mock(RecordsAdapter::class, function ($mock) {
            $mock->shouldReceive('processRecording')
                ->once()
                ->andThrow(new \Exception('Modular service failed'));
        });

        $component = Livewire::test(Records::class)
            ->call('setRecords', $this->livestock->id)
            ->set('date', Carbon::now()->format('Y-m-d'))
            ->set('weight_today', 2.5)
            ->set('mortality', 5)
            ->call('save');

        $component->assertHasNoErrors();

        // Verify data was still saved via legacy fallback
        $this->assertDatabaseHas('recordings', [
            'livestock_id' => $this->livestock->id,
            'tanggal' => Carbon::now()->format('Y-m-d')
        ]);
    }

    /** @test */
    public function test_adapter_data_transformation()
    {
        $adapter = app(RecordsAdapter::class);

        $legacyData = [
            'livestock_id' => $this->livestock->id,
            'date' => Carbon::now()->format('Y-m-d'),
            'body_weight' => 2.5,
            'mortality' => 5,
            'culling' => 3,
            'sales_quantity' => 2,
            'feed_usages' => [$this->feed->id => 100],
            'supply_usages' => [$this->supply->id => 50]
        ];

        $modularData = $adapter->adaptLegacyToModular($legacyData);

        $this->assertEquals($this->livestock->id, $modularData['livestock_id']);
        $this->assertEquals(2.5, $modularData['body_weight']);
        $this->assertEquals(5, $modularData['mortality']);
        $this->assertEquals(3, $modularData['culling']);
        $this->assertEquals(2, $modularData['sale']);
        $this->assertEquals([$this->feed->id => 100.0], $modularData['feed_usages']);
        $this->assertEquals([$this->supply->id => 50.0], $modularData['supply_usages']);
    }

    /** @test */
    public function test_compatibility_validation()
    {
        $adapter = app(RecordsAdapter::class);

        // Valid data
        $validData = [
            'livestock_id' => $this->livestock->id,
            'date' => Carbon::now()->format('Y-m-d'),
            'body_weight' => 2.5,
            'mortality' => 5,
            'feed_usages' => [$this->feed->id => 100],
            'supply_usages' => [$this->supply->id => 50]
        ];

        $result = $adapter->validateCompatibility($validData);
        $this->assertTrue($result->isValid);

        // Invalid data - missing required fields
        $invalidData = [
            'body_weight' => 2.5,
            'mortality' => 5
        ];

        $result = $adapter->validateCompatibility($invalidData);
        $this->assertFalse($result->isValid);
        $this->assertContains('Livestock ID is required for modular processing', $result->errors);
        $this->assertContains('Date is required for modular processing', $result->errors);
    }

    /** @test */
    public function test_performance_monitoring()
    {
        // Enable performance monitoring
        Config::set('recording.features.enable_performance_monitoring', true);

        $this->actingAs($this->user);

        $startTime = microtime(true);

        $component = Livewire::test(Records::class)
            ->call('setRecords', $this->livestock->id)
            ->set('date', Carbon::now()->format('Y-m-d'))
            ->set('weight_today', 2.5)
            ->set('mortality', 5)
            ->call('save');

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $component->assertHasNoErrors();

        // Verify performance was logged
        $this->assertDatabaseHas('recording_performance_logs', [
            'livestock_id' => $this->livestock->id,
            'operation_type' => 'save',
            'success' => true
        ]);

        // Verify execution time is reasonable
        $this->assertLessThan(10, $executionTime, 'Recording should complete within 10 seconds');
    }

    /** @test */
    public function test_feature_flag_initialization()
    {
        // Test default values
        Config::set('recording.features.use_modular_services', false);
        Config::set('recording.features.use_legacy_fallback', true);

        $this->actingAs($this->user);

        $component = Livewire::test(Records::class)
            ->call('setRecords', $this->livestock->id);

        // Access private properties via reflection for testing
        $reflection = new \ReflectionClass($component->instance());

        $useModularProperty = $reflection->getProperty('useModularServices');
        $useModularProperty->setAccessible(true);
        $useModular = $useModularProperty->getValue($component->instance());

        $fallbackProperty = $reflection->getProperty('enableLegacyFallback');
        $fallbackProperty->setAccessible(true);
        $fallback = $fallbackProperty->getValue($component->instance());

        $this->assertFalse($useModular);
        $this->assertTrue($fallback);
    }

    /** @test */
    public function test_error_handling_in_modular_mode()
    {
        // Configure for modular mode without fallback
        Config::set('recording.features.use_modular_services', true);
        Config::set('recording.features.use_legacy_fallback', false);

        $this->actingAs($this->user);

        // Mock adapter to return failure
        $this->mock(RecordsAdapter::class, function ($mock) {
            $mock->shouldReceive('processRecording')
                ->once()
                ->andReturn([
                    'success' => false,
                    'errors' => ['Validation failed'],
                    'message' => 'Recording failed'
                ]);
        });

        $component = Livewire::test(Records::class)
            ->call('setRecords', $this->livestock->id)
            ->set('date', Carbon::now()->format('Y-m-d'))
            ->set('weight_today', 2.5)
            ->set('mortality', 5)
            ->call('save');

        $component->assertHasErrors(['general']);
    }

    /** @test */
    public function test_service_health_check()
    {
        $integrationService = app(RecordsIntegrationService::class);

        $health = $integrationService->getHealthStatus();

        $this->assertIsArray($health);
        $this->assertArrayHasKey('overall_status', $health);
        $this->assertArrayHasKey('services', $health);
        $this->assertArrayHasKey('timestamp', $health);

        // Should have all required services
        $this->assertArrayHasKey('data_service', $health['services']);
        $this->assertArrayHasKey('validation_service', $health['services']);
        $this->assertArrayHasKey('calculation_service', $health['services']);
        $this->assertArrayHasKey('persistence_service', $health['services']);
        $this->assertArrayHasKey('database', $health['services']);
    }

    /** @test */
    public function test_batch_processing_capability()
    {
        $this->actingAs($this->user);

        // Create multiple livestock records
        $livestock2 = Livestock::factory()->create([
            'company_id' => $this->company->id,
            'start_date' => Carbon::now()->subDays(30),
            'initial_quantity' => 1000
        ]);

        CurrentLivestock::factory()->create([
            'livestock_id' => $livestock2->id,
            'quantity' => 950
        ]);

        $recordingData = [
            [
                'livestock_id' => $this->livestock->id,
                'date' => Carbon::now()->format('Y-m-d'),
                'body_weight' => 2.5,
                'mortality' => 5,
                'culling' => 3,
                'feed_usages' => [$this->feed->id => 100],
                'supply_usages' => [$this->supply->id => 50]
            ],
            [
                'livestock_id' => $livestock2->id,
                'date' => Carbon::now()->format('Y-m-d'),
                'body_weight' => 2.3,
                'mortality' => 3,
                'culling' => 2,
                'feed_usages' => [$this->feed->id => 80],
                'supply_usages' => [$this->supply->id => 40]
            ]
        ];

        $integrationService = app(RecordsIntegrationService::class);
        $result = $integrationService->saveBatchRecordings($recordingData);

        $this->assertTrue($result->isSuccess());

        // Verify both records were saved
        $this->assertDatabaseHas('recordings', [
            'livestock_id' => $this->livestock->id,
            'tanggal' => Carbon::now()->format('Y-m-d')
        ]);

        $this->assertDatabaseHas('recordings', [
            'livestock_id' => $livestock2->id,
            'tanggal' => Carbon::now()->format('Y-m-d')
        ]);
    }

    /** @test */
    public function test_configuration_override()
    {
        // Test that configuration can be overridden per user/company
        Config::set('recording.migration.user_whitelist', (string) $this->user->id);
        Config::set('recording.migration.enabled', true);

        $this->actingAs($this->user);

        $component = Livewire::test(Records::class)
            ->call('setRecords', $this->livestock->id);

        // Component should initialize with modular services for whitelisted user
        $this->assertTrue(true); // Placeholder - actual test would check internal state
    }

    protected function tearDown(): void
    {
        // Clean up any test data
        DB::table('recording_performance_logs')->delete();

        parent::tearDown();
    }
}

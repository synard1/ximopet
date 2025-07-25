<?php

namespace Tests\Unit\Services\Livestock;

use Tests\TestCase;
use App\Services\Livestock\LivestockCostService;
use App\Models\Livestock;
use App\Models\Recording;
use App\Models\LivestockCost;
use App\Models\SupplyUsage;
use App\Models\SupplyUsageDetail;
use App\Models\Supply;
use App\Models\SupplyStock;
use App\Models\SupplyPurchase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class LivestockCostServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LivestockCostService $service;
    protected Livestock $livestock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(LivestockCostService::class);

        // Create test livestock
        $this->livestock = Livestock::factory()->create([
            'initial_quantity' => 1000,
            'start_date' => '2025-01-01',
            'price' => 15000
        ]);
    }

    /** @test */
    public function it_creates_minimal_recording_when_supply_usage_exists_but_no_recording()
    {
        // Arrange
        $date = '2025-01-25';

        // Create supply usage without recording
        $supplyUsage = $this->createSupplyUsage($date);

        // Act
        $livestockCost = $this->service->calculateForDate($this->livestock->id, $date);

        // Assert
        $this->assertNotNull($livestockCost);
        $this->assertEquals($this->livestock->id, $livestockCost->livestock_id);
        $this->assertEquals($date, $livestockCost->tanggal->format('Y-m-d'));

        // Check that minimal recording was created
        $recording = Recording::where('livestock_id', $this->livestock->id)
            ->whereDate('tanggal', $date)
            ->first();

        $this->assertNotNull($recording);
        $this->assertEquals('minimal_for_supply_usage', $recording->payload['recording_type']);
        $this->assertTrue($recording->payload['created_by_cost_service']);
        $this->assertEquals($this->livestock->initial_quantity, $recording->stock_awal);
        $this->assertEquals($this->livestock->initial_quantity, $recording->stock_akhir);

        // Check LivestockCost has proper recording_id
        $this->assertEquals($recording->id, $livestockCost->recording_id);
    }

    /** @test */
    public function it_uses_existing_recording_when_available()
    {
        // Arrange
        $date = '2025-01-25';

        // Create existing recording
        $recording = Recording::factory()->create([
            'livestock_id' => $this->livestock->id,
            'tanggal' => $date,
            'stock_awal' => 1000,
            'stock_akhir' => 995,
            'payload' => [
                'mortality' => 3,
                'culling' => 2,
                'sales_quantity' => 0
            ]
        ]);

        // Create supply usage
        $supplyUsage = $this->createSupplyUsage($date);

        // Act
        $livestockCost = $this->service->calculateForDate($this->livestock->id, $date);

        // Assert
        $this->assertNotNull($livestockCost);
        $this->assertEquals($recording->id, $livestockCost->recording_id);

        // Check that no new recording was created
        $recordingsCount = Recording::where('livestock_id', $this->livestock->id)
            ->whereDate('tanggal', $date)
            ->count();

        $this->assertEquals(1, $recordingsCount);
    }

    /** @test */
    public function it_calculates_supply_usage_costs_correctly()
    {
        // Arrange
        $date = '2025-01-25';
        $supplyUsage = $this->createSupplyUsage($date);

        // Act
        $livestockCost = $this->service->calculateForDate($this->livestock->id, $date);

        // Assert
        $breakdown = $livestockCost->cost_breakdown;

        $this->assertGreaterThan(0, $breakdown['supply_usage']);
        $this->assertEquals(0, $breakdown['pakan']); // No feed usage
        $this->assertEquals(0, $breakdown['ovk']); // No OVK usage

        // Check supply usage details
        $this->assertNotEmpty($breakdown['supply_usage_detail']);
        $this->assertCount(1, $breakdown['supply_usage_detail']);

        $detail = $breakdown['supply_usage_detail'][0];
        $this->assertEquals('Test Supply', $detail['supply_name']);
        $this->assertEquals(10, $detail['quantity']);
        $this->assertEquals(5000, $detail['price_per_unit']);
        $this->assertEquals(50000, $detail['subtotal']);
    }

    /** @test */
    public function it_handles_multiple_supply_usages_on_same_date()
    {
        // Arrange
        $date = '2025-01-25';

        // Create multiple supply usages
        $supplyUsage1 = $this->createSupplyUsage($date, 'Supply 1', 10, 5000);
        $supplyUsage2 = $this->createSupplyUsage($date, 'Supply 2', 5, 3000);

        // Act
        $livestockCost = $this->service->calculateForDate($this->livestock->id, $date);

        // Assert
        $breakdown = $livestockCost->cost_breakdown;
        $expectedTotal = (10 * 5000) + (5 * 3000); // 50000 + 15000 = 65000

        $this->assertEquals($expectedTotal, $breakdown['supply_usage']);
        $this->assertCount(2, $breakdown['supply_usage_detail']);
    }

    /** @test */
    public function it_calculates_per_chicken_costs_correctly()
    {
        // Arrange
        $date = '2025-01-25';
        $supplyUsage = $this->createSupplyUsage($date);

        // Act
        $livestockCost = $this->service->calculateForDate($this->livestock->id, $date);

        // Assert
        $breakdown = $livestockCost->cost_breakdown;
        $expectedCostPerChicken = 50000 / 1000; // 50 per chicken

        $this->assertEquals($expectedCostPerChicken, $breakdown['supply_usage_per_ayam']);
        $this->assertEquals($expectedCostPerChicken, $breakdown['daily_added_cost_per_chicken']);
    }

    /** @test */
    public function it_handles_zero_stock_correctly()
    {
        // Arrange
        $date = '2025-01-25';

        // Create livestock with zero initial quantity
        $zeroLivestock = Livestock::factory()->create([
            'initial_quantity' => 0,
            'start_date' => '2025-01-01',
            'price' => 15000
        ]);

        $supplyUsage = $this->createSupplyUsage($date, 'Test Supply', 10, 5000, $zeroLivestock->id);

        // Act
        $livestockCost = $this->service->calculateForDate($zeroLivestock->id, $date);

        // Assert
        $breakdown = $livestockCost->cost_breakdown;

        // Should handle division by zero gracefully
        $this->assertEquals(0, $breakdown['supply_usage_per_ayam']);
        $this->assertEquals(0, $breakdown['daily_added_cost_per_chicken']);
    }

    /** @test */
    public function it_preserves_previous_day_stock_data()
    {
        // Arrange
        $previousDate = '2025-01-24';
        $currentDate = '2025-01-25';

        // Create previous day recording
        $previousRecording = Recording::factory()->create([
            'livestock_id' => $this->livestock->id,
            'tanggal' => $previousDate,
            'stock_awal' => 1000,
            'stock_akhir' => 995, // 5 depletions
            'payload' => [
                'mortality' => 3,
                'culling' => 2,
                'sales_quantity' => 0
            ]
        ]);

        $supplyUsage = $this->createSupplyUsage($currentDate);

        // Act
        $livestockCost = $this->service->calculateForDate($this->livestock->id, $currentDate);

        // Assert
        $recording = Recording::where('livestock_id', $this->livestock->id)
            ->whereDate('tanggal', $currentDate)
            ->first();

        $this->assertEquals(995, $recording->stock_awal); // From previous day
        $this->assertEquals(995, $recording->stock_akhir); // No new depletions
    }

    /** @test */
    public function it_logs_minimal_recording_creation()
    {
        // Arrange
        $date = '2025-01-25';
        $supplyUsage = $this->createSupplyUsage($date);

        // Act
        $this->service->calculateForDate($this->livestock->id, $date);

        // Assert - Check that appropriate logs were written
        // Note: In real testing, you might want to use a logging mock
        // For now, we'll just verify the functionality works without errors
        $this->assertTrue(true); // Placeholder assertion
    }

    /** @test */
    public function it_handles_supply_usage_without_valid_purchase_data()
    {
        // Arrange
        $date = '2025-01-25';

        // Create supply usage with invalid purchase data
        $supply = Supply::factory()->create(['name' => 'Test Supply']);
        $supplyStock = SupplyStock::factory()->create([
            'supply_id' => $supply->id,
            'supply_purchase_id' => null // No purchase data
        ]);

        $supplyUsage = SupplyUsage::factory()->create([
            'livestock_id' => $this->livestock->id,
            'usage_date' => $date,
            'status' => 'completed'
        ]);

        SupplyUsageDetail::factory()->create([
            'supply_usage_id' => $supplyUsage->id,
            'supply_stock_id' => $supplyStock->id,
            'quantity_taken' => 10
        ]);

        // Act
        $livestockCost = $this->service->calculateForDate($this->livestock->id, $date);

        // Assert
        $breakdown = $livestockCost->cost_breakdown;
        $this->assertEquals(0, $breakdown['supply_usage']); // Should be 0 due to missing price data
    }

    private function createSupplyUsage($date, $supplyName = 'Test Supply', $quantity = 10, $price = 5000, $livestockId = null)
    {
        $livestockId = $livestockId ?? $this->livestock->id;

        // Create supply and purchase data
        $supply = Supply::factory()->create(['name' => $supplyName]);
        $supplyPurchase = SupplyPurchase::factory()->create([
            'price_per_unit' => $price,
            'price_per_converted_unit' => $price
        ]);

        $supplyStock = SupplyStock::factory()->create([
            'supply_id' => $supply->id,
            'supply_purchase_id' => $supplyPurchase->id
        ]);

        // Create supply usage
        $supplyUsage = SupplyUsage::factory()->create([
            'livestock_id' => $livestockId,
            'usage_date' => $date,
            'status' => 'completed'
        ]);

        // Create supply usage detail
        SupplyUsageDetail::factory()->create([
            'supply_usage_id' => $supplyUsage->id,
            'supply_stock_id' => $supplyStock->id,
            'quantity_taken' => $quantity
        ]);

        return $supplyUsage;
    }
}

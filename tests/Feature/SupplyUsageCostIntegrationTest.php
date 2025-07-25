<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Livestock;
use App\Models\Recording;
use App\Models\LivestockCost;
use App\Models\SupplyUsage;
use App\Models\SupplyUsageDetail;
use App\Models\Supply;
use App\Models\SupplyStock;
use App\Models\SupplyPurchase;
use App\Models\LivestockPurchaseItem;
use App\Services\Livestock\LivestockCostService;
use App\Services\Supply\SupplyUsageCostService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class SupplyUsageCostIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected LivestockCostService $livestockCostService;
    protected SupplyUsageCostService $supplyUsageCostService;
    protected Livestock $livestock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->livestockCostService = app(LivestockCostService::class);
        $this->supplyUsageCostService = app(SupplyUsageCostService::class);

        // Create test livestock with purchase data
        $this->livestock = Livestock::factory()->create([
            'initial_quantity' => 1000,
            'start_date' => '2025-01-01',
            'price' => 15000
        ]);

        // Create livestock purchase item
        LivestockPurchaseItem::factory()->create([
            'livestock_id' => $this->livestock->id,
            'price_per_unit' => 15000,
            'quantity' => 1000,
            'price_total' => 15000000
        ]);
    }

    /** @test */
    public function it_integrates_supply_usage_costs_with_livestock_cost_calculation()
    {
        // Arrange
        $date = '2025-01-25';
        $supplyUsage = $this->createSupplyUsage($date);

        // Act
        $livestockCost = $this->livestockCostService->calculateForDate($this->livestock->id, $date);

        // Assert
        $this->assertNotNull($livestockCost);
        $this->assertEquals($this->livestock->id, $livestockCost->livestock_id);
        $this->assertEquals($date, $livestockCost->tanggal->format('Y-m-d'));

        // Check cost breakdown includes supply usage
        $breakdown = $livestockCost->cost_breakdown;
        $this->assertGreaterThan(0, $breakdown['supply_usage']);
        $this->assertEquals(50000, $breakdown['supply_usage']); // 10 * 5000

        // Check per-chicken costs
        $this->assertEquals(50, $breakdown['supply_usage_per_ayam']); // 50000 / 1000
        $this->assertEquals(50, $breakdown['daily_added_cost_per_chicken']);

        // Check cumulative costs include initial price
        $this->assertGreaterThan(15000, $livestockCost->cost_per_ayam); // Initial + supply usage
    }

    /** @test */
    public function it_handles_supply_usage_with_existing_recording()
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

        $supplyUsage = $this->createSupplyUsage($date);

        // Act
        $livestockCost = $this->livestockCostService->calculateForDate($this->livestock->id, $date);

        // Assert
        $this->assertEquals($recording->id, $livestockCost->recording_id);

        $breakdown = $livestockCost->cost_breakdown;
        $this->assertEquals(50000, $breakdown['supply_usage']);

        // Per-chicken cost should be based on stock_akhir (995)
        $expectedCostPerChicken = 50000 / 995; // ~50.25
        $this->assertEquals(round($expectedCostPerChicken, 2), $breakdown['supply_usage_per_ayam']);
    }

    /** @test */
    public function it_calculates_cumulative_costs_correctly()
    {
        // Arrange
        $date1 = '2025-01-25';
        $date2 = '2025-01-26';

        // Create supply usages for two consecutive days
        $supplyUsage1 = $this->createSupplyUsage($date1);
        $supplyUsage2 = $this->createSupplyUsage($date2);

        // Act
        $livestockCost1 = $this->livestockCostService->calculateForDate($this->livestock->id, $date1);
        $livestockCost2 = $this->livestockCostService->calculateForDate($this->livestock->id, $date2);

        // Assert
        $breakdown1 = $livestockCost1->cost_breakdown;
        $breakdown2 = $livestockCost2->cost_breakdown;

        // Day 1: Initial price + supply usage
        $day1CostPerChicken = 15000 + 50; // 15050

        // Day 2: Previous cumulative + new supply usage
        $day2CostPerChicken = $day1CostPerChicken + 50; // 15100

        $this->assertEquals($day1CostPerChicken, $livestockCost1->cost_per_ayam);
        $this->assertEquals($day2CostPerChicken, $livestockCost2->cost_per_ayam);
    }

    /** @test */
    public function it_handles_multiple_supply_types_correctly()
    {
        // Arrange
        $date = '2025-01-25';

        // Create multiple supply usages with different types
        $supplyUsage1 = $this->createSupplyUsage($date, 'Vitamin A', 10, 5000);
        $supplyUsage2 = $this->createSupplyUsage($date, 'Antibiotic', 5, 8000);
        $supplyUsage3 = $this->createSupplyUsage($date, 'Vaccine', 2, 15000);

        // Act
        $livestockCost = $this->livestockCostService->calculateForDate($this->livestock->id, $date);

        // Assert
        $breakdown = $livestockCost->cost_breakdown;
        $expectedTotal = (10 * 5000) + (5 * 8000) + (2 * 15000); // 50000 + 40000 + 30000 = 120000

        $this->assertEquals($expectedTotal, $breakdown['supply_usage']);
        $this->assertCount(3, $breakdown['supply_usage_detail']);

        // Check individual details
        $details = collect($breakdown['supply_usage_detail']);

        $vitaminA = $details->firstWhere('supply_name', 'Vitamin A');
        $this->assertEquals(50000, $vitaminA['subtotal']);

        $antibiotic = $details->firstWhere('supply_name', 'Antibiotic');
        $this->assertEquals(40000, $antibiotic['subtotal']);

        $vaccine = $details->firstWhere('supply_name', 'Vaccine');
        $this->assertEquals(30000, $vaccine['subtotal']);
    }

    /** @test */
    public function it_handles_supply_usage_with_depletion()
    {
        // Arrange
        $date = '2025-01-25';

        // Create recording with depletion
        $recording = Recording::factory()->create([
            'livestock_id' => $this->livestock->id,
            'tanggal' => $date,
            'stock_awal' => 1000,
            'stock_akhir' => 990, // 10 depletions
            'payload' => [
                'mortality' => 7,
                'culling' => 3,
                'sales_quantity' => 0
            ]
        ]);

        $supplyUsage = $this->createSupplyUsage($date);

        // Act
        $livestockCost = $this->livestockCostService->calculateForDate($this->livestock->id, $date);

        // Assert
        $breakdown = $livestockCost->cost_breakdown;

        // Supply usage cost should be calculated based on stock_akhir (990)
        $expectedCostPerChicken = 50000 / 990; // ~50.51
        $this->assertEquals(round($expectedCostPerChicken, 2), $breakdown['supply_usage_per_ayam']);

        // Depletion cost should be calculated
        $this->assertGreaterThan(0, $breakdown['deplesi']);
    }

    /** @test */
    public function it_handles_supply_usage_with_feed_and_ovk_costs()
    {
        // Arrange
        $date = '2025-01-25';

        // Create recording with feed and OVK data
        $recording = Recording::factory()->create([
            'livestock_id' => $this->livestock->id,
            'tanggal' => $date,
            'stock_awal' => 1000,
            'stock_akhir' => 1000,
            'pakan_harian' => 100, // 100kg feed
            'pakan_total' => 100,
            'payload' => [
                'mortality' => 0,
                'culling' => 0,
                'sales_quantity' => 0
            ]
        ]);

        $supplyUsage = $this->createSupplyUsage($date);

        // Act
        $livestockCost = $this->livestockCostService->calculateForDate($this->livestock->id, $date);

        // Assert
        $breakdown = $livestockCost->cost_breakdown;

        // Should have feed, OVK, and supply usage costs
        $this->assertGreaterThan(0, $breakdown['pakan']);
        $this->assertGreaterThan(0, $breakdown['ovk']);
        $this->assertEquals(50000, $breakdown['supply_usage']);

        // Total daily cost should include all components
        $totalDailyCost = $breakdown['pakan'] + $breakdown['ovk'] + $breakdown['supply_usage'];
        $this->assertEquals($totalDailyCost, $breakdown['daily_total']);
    }

    /** @test */
    public function it_handles_supply_usage_with_unit_conversion()
    {
        // Arrange
        $date = '2025-01-25';

        // Create supply with unit conversion
        $supply = Supply::factory()->create([
            'name' => 'Test Supply',
            'data' => [
                'conversion_units' => [
                    [
                        'unit_id' => 'purchase_unit',
                        'unit_name' => 'Liter',
                        'conversion_value' => 1
                    ],
                    [
                        'unit_id' => 'smallest_unit',
                        'unit_name' => 'ml',
                        'conversion_value' => 1000
                    ]
                ]
            ]
        ]);

        $supplyPurchase = SupplyPurchase::factory()->create([
            'price_per_unit' => 50000, // Price per liter
            'price_per_converted_unit' => 50 // Price per ml (50000 / 1000)
        ]);

        $supplyStock = SupplyStock::factory()->create([
            'supply_id' => $supply->id,
            'supply_purchase_id' => $supplyPurchase->id
        ]);

        $supplyUsage = SupplyUsage::factory()->create([
            'livestock_id' => $this->livestock->id,
            'usage_date' => $date,
            'status' => 'completed'
        ]);

        SupplyUsageDetail::factory()->create([
            'supply_usage_id' => $supplyUsage->id,
            'supply_stock_id' => $supplyStock->id,
            'quantity_taken' => 1000 // 1000ml = 1 liter
        ]);

        // Act
        $livestockCost = $this->livestockCostService->calculateForDate($this->livestock->id, $date);

        // Assert
        $breakdown = $livestockCost->cost_breakdown;
        $expectedCost = 1000 * 50; // 1000ml * 50 per ml = 50000

        $this->assertEquals($expectedCost, $breakdown['supply_usage']);
    }

    /** @test */
    public function it_handles_supply_usage_with_fifo_calculation()
    {
        // Arrange
        $date = '2025-01-25';

        // Create multiple supply purchases with different prices (FIFO)
        $supply = Supply::factory()->create(['name' => 'Test Supply']);

        $purchase1 = SupplyPurchase::factory()->create([
            'price_per_unit' => 4000, // Older, cheaper
            'created_at' => '2025-01-01'
        ]);

        $purchase2 = SupplyPurchase::factory()->create([
            'price_per_unit' => 6000, // Newer, more expensive
            'created_at' => '2025-01-15'
        ]);

        $stock1 = SupplyStock::factory()->create([
            'supply_id' => $supply->id,
            'supply_purchase_id' => $purchase1->id,
            'quantity' => 50
        ]);

        $stock2 = SupplyStock::factory()->create([
            'supply_id' => $supply->id,
            'supply_purchase_id' => $purchase2->id,
            'quantity' => 30
        ]);

        $supplyUsage = SupplyUsage::factory()->create([
            'livestock_id' => $this->livestock->id,
            'usage_date' => $date,
            'status' => 'completed'
        ]);

        // Use 60 units (50 from first purchase + 10 from second)
        SupplyUsageDetail::factory()->create([
            'supply_usage_id' => $supplyUsage->id,
            'supply_stock_id' => $stock1->id,
            'quantity_taken' => 50
        ]);

        SupplyUsageDetail::factory()->create([
            'supply_usage_id' => $supplyUsage->id,
            'supply_stock_id' => $stock2->id,
            'quantity_taken' => 10
        ]);

        // Act
        $livestockCost = $this->livestockCostService->calculateForDate($this->livestock->id, $date);

        // Assert
        $breakdown = $livestockCost->cost_breakdown;
        $expectedCost = (50 * 4000) + (10 * 6000); // 200000 + 60000 = 260000

        $this->assertEquals($expectedCost, $breakdown['supply_usage']);
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

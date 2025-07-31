<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Services\Recording\VirtualQuantityCalculationService;
use App\Models\Livestock;
use App\Models\LivestockBatch;

use function App\Helpers\logInfoIfDebug;
use function App\Helpers\logDebugIfDebug;

class TestVirtualQuantityCalculationCommand extends Command
{
    protected $signature = 'test:virtual-quantity 
                            {livestock_id : Livestock ID to test}
                            {date : Date to test (Y-m-d format)}
                            {--quantity=100 : Sales quantity to test}
                            {--weight=2500 : Sales weight to test}
                            {--method=projection : Calculation method (projection, estimate, forecast)}
                            {--clear : Clear virtual data after test}';

    protected $description = 'Test virtual quantity calculation functionality';

    public function handle()
    {
        $livestockId = $this->argument('livestock_id');
        $date = $this->argument('date');
        $quantity = (int) $this->option('quantity');
        $weight = (float) $this->option('weight');
        $method = $this->option('method');
        $clear = $this->option('clear');

        $this->info("🧪 Testing Virtual Quantity Calculation");
        $this->info("=====================================");
        $this->info("Livestock ID: {$livestockId}");
        $this->info("Date: {$date}");
        $this->info("Quantity: {$quantity}");
        $this->info("Weight: {$weight}");
        $this->info("Method: {$method}");
        $this->info("");

        // Validate livestock exists
        $livestock = Livestock::find($livestockId);
        if (!$livestock) {
            $this->error("❌ Livestock not found: {$livestockId}");
            return 1;
        }

        // Show real stock before test
        $this->showRealStock($livestockId, 'BEFORE');

        // Test virtual quantity calculation
        $this->testVirtualCalculation($livestockId, $date, $quantity, $weight, $method);

        // Show real stock after test
        $this->showRealStock($livestockId, 'AFTER');

        // Show virtual data
        $this->showVirtualData($livestockId, $date);

        // Clear virtual data if requested
        if ($clear) {
            $this->clearVirtualData($livestockId, $date);
        }

        $this->info("✅ Test completed successfully!");
        return 0;
    }

    private function showRealStock(string $livestockId, string $stage): void
    {
        $this->info("📊 Real Stock {$stage} Virtual Calculation:");

        $batches = LivestockBatch::where('livestock_id', $livestockId)
            ->where('status', 'active')
            ->get();

        if ($batches->isEmpty()) {
            $this->warn("⚠️ No active batches found");
            return;
        }

        $table = [];
        foreach ($batches as $batch) {
            $table[] = [
                'Batch ID' => $batch->id,
                'Name' => $batch->name,
                'Initial Qty' => $batch->initial_quantity,
                'Depletion' => $batch->quantity_depletion,
                'Sales' => $batch->quantity_sales,
                'Mutated' => $batch->quantity_mutated,
                'Available' => $batch->quantity_available,
            ];
        }

        $this->table([
            'Batch ID',
            'Name',
            'Initial Qty',
            'Depletion',
            'Sales',
            'Mutated',
            'Available'
        ], $table);

        // Show totals
        $totalInitial = $batches->sum('initial_quantity');
        $totalDepletion = $batches->sum('quantity_depletion');
        $totalSales = $batches->sum('quantity_sales');
        $totalMutated = $batches->sum('quantity_mutated');
        $totalAvailable = $batches->sum('quantity_available');

        $this->info("📈 Totals:");
        $this->info("   Initial Quantity: {$totalInitial}");
        $this->info("   Total Depletion: {$totalDepletion}");
        $this->info("   Total Sales: {$totalSales}");
        $this->info("   Total Mutated: {$totalMutated}");
        $this->info("   Total Available: {$totalAvailable}");
        $this->info("");
    }

    private function testVirtualCalculation(string $livestockId, string $date, int $quantity, float $weight, string $method): void
    {
        $this->info("🔄 Testing Virtual Calculation...");

        try {
            $virtualService = app(VirtualQuantityCalculationService::class);

            $salesData = [
                'quantity' => $quantity,
                'weight' => $weight,
                'method' => $method
            ];

            $result = $virtualService->calculateVirtualSalesQuantity($livestockId, $date, $salesData);

            if ($result->isSuccess()) {
                $this->info("✅ Virtual calculation successful!");
                $data = $result->getData();

                $this->info("📊 Virtual Calculation Results:");
                $this->info("   Quantity: {$data['quantity']}");
                $this->info("   Weight: {$data['weight']}");
                $this->info("   Method: {$data['calculation_method']}");

                if (isset($data['projection_data'])) {
                    $this->info("   Historical Days: {$data['projection_data']['historical_days']}");
                    $this->info("   Average Daily Sales: {$data['projection_data']['average_daily_sales']}");
                    $this->info("   Growth Rate: {$data['projection_data']['growth_rate']}%");
                    $this->info("   Projected Sales: {$data['projection_data']['projected_sales']}");
                }

                $this->info("");
            } else {
                $this->error("❌ Virtual calculation failed: " . $result->getMessage());
            }
        } catch (\Exception $e) {
            $this->error("❌ Exception in virtual calculation: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
        }
    }

    private function showVirtualData(string $livestockId, string $date): void
    {
        $this->info("📋 Virtual Data Stored:");

        try {
            $virtualService = app(VirtualQuantityCalculationService::class);
            $result = $virtualService->getVirtualQuantityData($livestockId, $date);

            if ($result->isSuccess()) {
                $virtualData = $result->getData();

                if (empty($virtualData)) {
                    $this->warn("⚠️ No virtual data found for date: {$date}");
                    return;
                }

                $table = [];
                foreach ($virtualData as $data) {
                    $virtualSales = $data['virtual_sales'];
                    $table[] = [
                        'Batch ID' => $data['batch_id'],
                        'Batch Name' => $data['batch_name'],
                        'Quantity' => $virtualSales['quantity'],
                        'Weight' => $virtualSales['weight'],
                        'Status' => $virtualSales['status'],
                        'Method' => $virtualSales['metadata']['calculation_method'] ?? 'N/A',
                    ];
                }

                $this->table([
                    'Batch ID',
                    'Batch Name',
                    'Quantity',
                    'Weight',
                    'Status',
                    'Method'
                ], $table);

                // Show metadata
                if (!empty($virtualData)) {
                    $firstData = $virtualData[0];
                    $metadata = $firstData['virtual_sales']['metadata'] ?? [];

                    $this->info("📝 Metadata:");
                    $this->info("   Calculation Method: " . ($metadata['calculation_method'] ?? 'N/A'));
                    $this->info("   Calculation Timestamp: " . ($metadata['calculation_timestamp'] ?? 'N/A'));

                    if (isset($metadata['real_stock_reference'])) {
                        $realStock = $metadata['real_stock_reference'];
                        $this->info("   Real Stock Reference:");
                        $this->info("     Total Available: " . ($realStock['total_available'] ?? 'N/A'));
                        $this->info("     Total Initial: " . ($realStock['total_initial_quantity'] ?? 'N/A'));
                        $this->info("     Total Depletion: " . ($realStock['total_depletion'] ?? 'N/A'));
                        $this->info("     Total Sales: " . ($realStock['total_sales'] ?? 'N/A'));
                        $this->info("     Batches Count: " . ($realStock['batches_count'] ?? 'N/A'));
                    }
                }
            } else {
                $this->error("❌ Failed to get virtual data: " . $result->getMessage());
            }
        } catch (\Exception $e) {
            $this->error("❌ Exception in get virtual data: " . $e->getMessage());
        }

        $this->info("");
    }

    private function clearVirtualData(string $livestockId, string $date): void
    {
        $this->info("🧹 Clearing virtual data...");

        try {
            $virtualService = app(VirtualQuantityCalculationService::class);
            $result = $virtualService->clearVirtualData($livestockId, $date);

            if ($result->isSuccess()) {
                $this->info("✅ Virtual data cleared successfully!");
            } else {
                $this->error("❌ Failed to clear virtual data: " . $result->getMessage());
            }
        } catch (\Exception $e) {
            $this->error("❌ Exception in clear virtual data: " . $e->getMessage());
        }

        $this->info("");
    }
}

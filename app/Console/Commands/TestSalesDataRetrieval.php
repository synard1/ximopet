<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Livestock;
use App\Models\LivestockSales;
use App\Models\RecordingSale;
use App\Models\Farm;
use App\Models\Coop;
use Illuminate\Support\Facades\Log;

class TestSalesDataRetrieval extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:sales-data 
                            {--livestock_id= : Specific livestock ID to test}
                            {--farm_id= : Farm ID to test}
                            {--coop_id= : Coop ID to test}
                            {--tahun= : Year to test}
                            {--include_draft= : Include draft sales (true/false)}
                            {--debug= : Enable debug mode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test sales data retrieval with detailed logging';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🔍 Starting Sales Data Retrieval Test...');

        $livestockId = $this->option('livestock_id');
        $farmId = $this->option('farm_id');
        $coopId = $this->option('coop_id');
        $tahun = $this->option('tahun');
        $includeDraft = $this->option('include_draft') === 'true';
        $debug = $this->option('debug') === 'true';

        $this->info("📊 Test Parameters:");
        $this->info("   - Livestock ID: " . ($livestockId ?: 'Not specified'));
        $this->info("   - Farm ID: " . ($farmId ?: 'Not specified'));
        $this->info("   - Coop ID: " . ($coopId ?: 'Not specified'));
        $this->info("   - Tahun: " . ($tahun ?: 'Not specified'));
        $this->info("   - Include Draft: " . ($includeDraft ? 'Yes' : 'No'));
        $this->info("   - Debug Mode: " . ($debug ? 'Yes' : 'No'));

        // Test 1: Check Livestock Data
        if ($livestockId) {
            $this->testLivestockData($livestockId, $debug);
        }

        // Test 2: Check Farm Data
        if ($farmId) {
            $this->testFarmData($farmId, $debug);
        }

        // Test 3: Check Coop Data
        if ($coopId) {
            $this->testCoopData($coopId, $debug);
        }

        // Test 4: Check LivestockSales Data
        $this->testLivestockSalesData($livestockId, $farmId, $coopId, $tahun, $includeDraft, $debug);

        // Test 5: Check RecordingSale Data
        $this->testRecordingSaleData($livestockId, $farmId, $coopId, $tahun, $includeDraft, $debug);

        // Test 6: Check Relationships
        $this->testRelationships($livestockId, $farmId, $coopId, $debug);

        $this->info('✅ Sales Data Retrieval Test Completed!');
        return 0;
    }

    /**
     * Test livestock data
     */
    private function testLivestockData($livestockId, $debug = false)
    {
        $this->info("\n🐄 Testing Livestock Data...");

        $livestock = Livestock::with(['farm', 'coop'])->find($livestockId);

        if (!$livestock) {
            $this->error("❌ Livestock not found with ID: {$livestockId}");
            return;
        }

        $this->info("✅ Livestock found:");
        $this->info("   - ID: {$livestock->id}");
        $this->info("   - Name: {$livestock->name}");
        $this->info("   - Farm: " . ($livestock->farm ? $livestock->farm->name : 'N/A'));
        $this->info("   - Coop: " . ($livestock->coop ? $livestock->coop->name : 'N/A'));
        $this->info("   - Start Date: {$livestock->start_date}");
        $this->info("   - End Date: {$livestock->end_date}");

        if ($debug) {
            $this->info("   - Raw Data: " . json_encode($livestock->toArray(), JSON_PRETTY_PRINT));
        }
    }

    /**
     * Test farm data
     */
    private function testFarmData($farmId, $debug = false)
    {
        $this->info("\n🏭 Testing Farm Data...");

        $farm = Farm::find($farmId);

        if (!$farm) {
            $this->error("❌ Farm not found with ID: {$farmId}");
            return;
        }

        $this->info("✅ Farm found:");
        $this->info("   - ID: {$farm->id}");
        $this->info("   - Name: {$farm->name}");

        // Count related livestock
        $livestockCount = Livestock::where('farm_id', $farmId)->count();
        $this->info("   - Related Livestock Count: {$livestockCount}");

        if ($debug) {
            $livestockList = Livestock::where('farm_id', $farmId)->get(['id', 'name']);
            $this->info("   - Related Livestock: " . json_encode($livestockList->toArray(), JSON_PRETTY_PRINT));
        }
    }

    /**
     * Test coop data
     */
    private function testCoopData($coopId, $debug = false)
    {
        $this->info("\n🏠 Testing Coop Data...");

        $coop = Coop::find($coopId);

        if (!$coop) {
            $this->error("❌ Coop not found with ID: {$coopId}");
            return;
        }

        $this->info("✅ Coop found:");
        $this->info("   - ID: {$coop->id}");
        $this->info("   - Name: {$coop->name}");

        // Count related livestock
        $livestockCount = Livestock::where('coop_id', $coopId)->count();
        $this->info("   - Related Livestock Count: {$livestockCount}");

        if ($debug) {
            $livestockList = Livestock::where('coop_id', $coopId)->get(['id', 'name']);
            $this->info("   - Related Livestock: " . json_encode($livestockList->toArray(), JSON_PRETTY_PRINT));
        }
    }

    /**
     * Test LivestockSales data
     */
    private function testLivestockSalesData($livestockId, $farmId, $coopId, $tahun, $includeDraft, $debug = false)
    {
        $this->info("\n💰 Testing LivestockSales Data...");

        // Test by livestock ID
        if ($livestockId) {
            $this->info("🔍 Testing by Livestock ID: {$livestockId}");

            $query = LivestockSales::where('livestock_id', $livestockId);

            if (!$includeDraft) {
                $query->whereNotIn('status', ['draft', 'pending']);
            }

            $sales = $query->get();

            $this->info("   - Total Sales: {$sales->count()}");

            if ($sales->count() > 0) {
                $this->info("   - Sample Sales:");
                foreach ($sales->take(3) as $sale) {
                    $this->info("     * ID: {$sale->id}, Status: {$sale->status}, Amount: {$sale->total_amount}");
                }
            }

            if ($debug) {
                $this->info("   - All Sales: " . json_encode($sales->toArray(), JSON_PRETTY_PRINT));
            }
        }

        // Test by farm/coop filters
        if ($farmId || $coopId || $tahun) {
            $this->info("🔍 Testing by Filters:");
            $this->info("   - Farm ID: " . ($farmId ?: 'Not specified'));
            $this->info("   - Coop ID: " . ($coopId ?: 'Not specified'));
            $this->info("   - Tahun: " . ($tahun ?: 'Not specified'));

            $query = LivestockSales::query();

            if ($farmId) {
                $query->where('farm_id', $farmId);
            }

            if ($coopId) {
                $query->where('coop_id', $coopId);
            }

            if ($tahun) {
                $query->whereYear('date', $tahun);
            }

            if (!$includeDraft) {
                $query->whereNotIn('status', ['draft', 'pending']);
            }

            $sales = $query->get();

            $this->info("   - Total Sales: {$sales->count()}");

            if ($sales->count() > 0) {
                $this->info("   - Sample Sales:");
                foreach ($sales->take(3) as $sale) {
                    $this->info("     * ID: {$sale->id}, Livestock: {$sale->livestock_id}, Status: {$sale->status}, Amount: {$sale->total_amount}");
                }
            }

            if ($debug) {
                $this->info("   - All Sales: " . json_encode($sales->toArray(), JSON_PRETTY_PRINT));
            }
        }
    }

    /**
     * Test RecordingSale data
     */
    private function testRecordingSaleData($livestockId, $farmId, $coopId, $tahun, $includeDraft, $debug = false)
    {
        $this->info("\n📝 Testing RecordingSale Data...");

        // Test by livestock ID
        if ($livestockId) {
            $this->info("🔍 Testing by Livestock ID: {$livestockId}");

            $query = RecordingSale::where('livestock_id', $livestockId);

            if (!$includeDraft) {
                $query->whereNotIn('status', ['draft', 'pending']);
            }

            $sales = $query->get();

            $this->info("   - Total Recording Sales: {$sales->count()}");

            if ($sales->count() > 0) {
                $this->info("   - Sample Recording Sales:");
                foreach ($sales->take(3) as $sale) {
                    $this->info("     * ID: {$sale->id}, Status: {$sale->status}, Quantity: {$sale->total_quantity}, Weight: {$sale->total_weight}");
                }
            }

            if ($debug) {
                $this->info("   - All Recording Sales: " . json_encode($sales->toArray(), JSON_PRETTY_PRINT));
            }
        }

        // Test by farm/coop filters
        if ($farmId || $coopId || $tahun) {
            $this->info("🔍 Testing by Filters:");
            $this->info("   - Farm ID: " . ($farmId ?: 'Not specified'));
            $this->info("   - Coop ID: " . ($coopId ?: 'Not specified'));
            $this->info("   - Tahun: " . ($tahun ?: 'Not specified'));

            $query = RecordingSale::with(['livestock.farm', 'livestock.coop']);

            if ($farmId) {
                $query->whereHas('livestock', function ($q) use ($farmId) {
                    $q->where('farm_id', $farmId);
                });
            }

            if ($coopId) {
                $query->whereHas('livestock', function ($q) use ($coopId) {
                    $q->where('coop_id', $coopId);
                });
            }

            if ($tahun) {
                $query->whereYear('date', $tahun);
            }

            if (!$includeDraft) {
                $query->whereNotIn('status', ['draft', 'pending']);
            }

            $sales = $query->get();

            $this->info("   - Total Recording Sales: {$sales->count()}");

            if ($sales->count() > 0) {
                $this->info("   - Sample Recording Sales:");
                foreach ($sales->take(3) as $sale) {
                    $this->info("     * ID: {$sale->id}, Livestock: {$sale->livestock_id}, Status: {$sale->status}, Quantity: {$sale->total_quantity}");
                }
            }

            if ($debug) {
                $this->info("   - All Recording Sales: " . json_encode($sales->toArray(), JSON_PRETTY_PRINT));
            }
        }
    }

    /**
     * Test relationships
     */
    private function testRelationships($livestockId, $farmId, $coopId, $debug = false)
    {
        $this->info("\n🔗 Testing Relationships...");

        if ($livestockId) {
            $livestock = Livestock::with(['farm', 'coop'])->find($livestockId);

            if ($livestock) {
                $this->info("🔍 Livestock Relationships:");
                $this->info("   - Farm Relationship: " . ($livestock->farm ? "✅ Connected to {$livestock->farm->name}" : "❌ No farm relationship"));
                $this->info("   - Coop Relationship: " . ($livestock->coop ? "✅ Connected to {$livestock->coop->name}" : "❌ No coop relationship"));

                if ($debug) {
                    $this->info("   - Farm Data: " . json_encode($livestock->farm ? $livestock->farm->toArray() : null, JSON_PRETTY_PRINT));
                    $this->info("   - Coop Data: " . json_encode($livestock->coop ? $livestock->coop->toArray() : null, JSON_PRETTY_PRINT));
                }
            }
        }

        // Test cross-relationships
        if ($farmId && $coopId) {
            $this->info("🔍 Testing Farm-Coop Cross Relationship:");

            $livestockCount = Livestock::where('farm_id', $farmId)
                ->where('coop_id', $coopId)
                ->count();

            $this->info("   - Livestock with Farm {$farmId} and Coop {$coopId}: {$livestockCount}");

            if ($debug) {
                $livestockList = Livestock::where('farm_id', $farmId)
                    ->where('coop_id', $coopId)
                    ->get(['id', 'name', 'farm_id', 'coop_id']);

                $this->info("   - Livestock List: " . json_encode($livestockList->toArray(), JSON_PRETTY_PRINT));
            }
        }
    }
}

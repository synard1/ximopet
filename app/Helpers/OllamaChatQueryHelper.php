<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use App\Models\Livestock;
use App\Models\FeedPurchase;
use App\Models\FeedPurchaseItem;
use App\Models\SupplyPurchase;
use App\Models\SupplyPurchaseBatch;
use App\Models\LivestockPurchase;
use App\Models\LivestockPurchaseItem;
use Illuminate\Support\Carbon;

class OllamaChatQueryHelper
{
    /**
     * Execute complex query with enhanced purchase support
     */
    public static function executeComplexQuery(string $query, int $contextLimit): string
    {
        try {
            // Enhanced natural language parsing for purchases
            if (preg_match('/pembelian\s+(livestock|ternak|ternak)\s+(.+)$/i', $query, $matches)) {
                return self::executeLivestockPurchaseQuery($matches[2], $contextLimit);
            }

            if (preg_match('/pembelian\s+(feed|pakan)\s+(.+)$/i', $query, $matches)) {
                return self::executeFeedPurchaseQuery($matches[2], $contextLimit);
            }

            if (preg_match('/pembelian\s+(supply|suplai)\s+(.+)$/i', $query, $matches)) {
                return self::executeSupplyPurchaseQuery($matches[2], $contextLimit);
            }

            // Legacy pattern matching
            if (preg_match('/^livestock\s+(.+)$/i', $query, $matches)) {
                return self::executeLivestockQuery($matches[1], $contextLimit);
            }

            if (preg_match('/^feed\s+(.+)$/i', $query, $matches)) {
                return self::executeFeedQuery($matches[1], $contextLimit);
            }

            if (preg_match('/^supply\s+(.+)$/i', $query, $matches)) {
                return self::executeSupplyQuery($matches[1], $contextLimit);
            }

            if (preg_match('/^analytics\s+(.+)$/i', $query, $matches)) {
                return self::executeAnalyticsQuery($matches[1], $contextLimit);
            }

            // Natural language purchase queries
            if (preg_match('/(?:berapa|berapa banyak|total)\s+(?:pembelian|purchase)\s+(?:selama|dalam|pada)\s+(.+)$/i', $query, $matches)) {
                return self::executeNaturalLanguagePurchaseQuery($matches[1], $contextLimit);
            }

            // Default: try to execute as raw SQL (with safety checks)
            return self::executeRawQuery($query, $contextLimit);
        } catch (\Exception $e) {
            return "Error executing complex query: " . $e->getMessage();
        }
    }

    /**
     * Execute query by type
     */
    public static function executeQueryByType(string $queryType, int $contextLimit): string
    {
        switch (strtolower($queryType)) {
            case 'livestock-performance':
                return self::getLivestockPerformanceData($contextLimit);

            case 'feed-analytics':
                return self::getFeedAnalyticsData($contextLimit);

            case 'supply-inventory':
                return self::getSupplyInventoryData($contextLimit);

            case 'financial-summary':
                return self::getFinancialSummaryData($contextLimit);

            case 'operational-metrics':
                return self::getOperationalMetricsData($contextLimit);

            default:
                return "Unknown query type: {$queryType}";
        }
    }

    /**
     * Execute livestock-specific query
     */
    public static function executeLivestockQuery(string $criteria, int $contextLimit): string
    {
        try {
            $query = Livestock::query();

            // Parse criteria
            if (preg_match('/status\s*=\s*(\w+)/i', $criteria, $matches)) {
                $query->where('status', $matches[1]);
            }

            if (preg_match('/quantity\s*>\s*(\d+)/i', $criteria, $matches)) {
                $query->where('initial_quantity', '>', $matches[1]);
            }

            if (preg_match('/quantity\s*<\s*(\d+)/i', $criteria, $matches)) {
                $query->where('initial_quantity', '<', $matches[1]);
            }

            if (preg_match('/date\s*>\s*(\d{4}-\d{2}-\d{2})/i', $criteria, $matches)) {
                $query->where('start_date', '>', $matches[1]);
            }

            if (preg_match('/date\s*<\s*(\d{4}-\d{2}-\d{2})/i', $criteria, $matches)) {
                $query->where('start_date', '<', $matches[1]);
            }

            $livestocks = $query->latest()->take(10)->get();

            if ($livestocks->isEmpty()) {
                return "No livestock found matching criteria: {$criteria}";
            }

            $result = "=== LIVESTOCK QUERY RESULTS ===\n";
            $result .= "Criteria: {$criteria}\n";
            $result .= "Found: {$livestocks->count()} records\n\n";

            foreach ($livestocks as $livestock) {
                $result .= "• {$livestock->name} ({$livestock->number}): {$livestock->getStatusLabel()}, {$livestock->initial_quantity} units\n";
            }

            return substr($result, 0, $contextLimit);
        } catch (\Exception $e) {
            return "Error executing livestock query: " . $e->getMessage();
        }
    }

    /**
     * Execute feed-specific query
     */
    public static function executeFeedQuery(string $criteria, int $contextLimit): string
    {
        try {
            $query = FeedPurchase::query();

            // Parse criteria
            if (preg_match('/status\s*=\s*(\w+)/i', $criteria, $matches)) {
                $query->where('status', $matches[1]);
            }

            if (preg_match('/date\s*>\s*(\d{4}-\d{2}-\d{2})/i', $criteria, $matches)) {
                $query->where('date', '>', $matches[1]);
            }

            if (preg_match('/date\s*<\s*(\d{4}-\d{2}-\d{2})/i', $criteria, $matches)) {
                $query->where('date', '<', $matches[1]);
            }

            $purchases = $query->latest()->take(10)->get();

            if ($purchases->isEmpty()) {
                return "No feed purchases found matching criteria: {$criteria}";
            }

            $result = "=== FEED PURCHASE QUERY RESULTS ===\n";
            $result .= "Criteria: {$criteria}\n";
            $result .= "Found: {$purchases->count()} records\n\n";

            foreach ($purchases as $purchase) {
                $result .= "• {$purchase->invoice_number}: {$purchase->getStatusLabel()}, {$purchase->date->format('Y-m-d')}\n";
            }

            return substr($result, 0, $contextLimit);
        } catch (\Exception $e) {
            return "Error executing feed query: " . $e->getMessage();
        }
    }

    /**
     * Execute supply-specific query
     */
    public static function executeSupplyQuery(string $criteria, int $contextLimit): string
    {
        try {
            $query = SupplyPurchase::query();

            // Parse criteria
            if (preg_match('/status\s*=\s*(\w+)/i', $criteria, $matches)) {
                $query->where('status', $matches[1]);
            }

            $purchases = $query->latest()->take(10)->get();

            if ($purchases->isEmpty()) {
                return "No supply purchases found matching criteria: {$criteria}";
            }

            $result = "=== SUPPLY PURCHASE QUERY RESULTS ===\n";
            $result .= "Criteria: {$criteria}\n";
            $result .= "Found: {$purchases->count()} records\n\n";

            foreach ($purchases as $purchase) {
                $result .= "• ID {$purchase->id}: {$purchase->status}, {$purchase->date->format('Y-m-d')}\n";
            }

            return substr($result, 0, $contextLimit);
        } catch (\Exception $e) {
            return "Error executing supply query: " . $e->getMessage();
        }
    }

    /**
     * Execute analytics query
     */
    public static function executeAnalyticsQuery(string $criteria, int $contextLimit): string
    {
        try {
            $result = "=== ANALYTICS QUERY RESULTS ===\n";
            $result .= "Criteria: {$criteria}\n\n";

            // Livestock analytics
            $totalLivestock = Livestock::count();
            $activeLivestock = Livestock::where('status', 'active')->count();

            $result .= "Livestock Analytics:\n";
            $result .= "• Total livestock: {$totalLivestock}\n";
            $result .= "• Active livestock: {$activeLivestock}\n";
            $result .= "• Active percentage: " . round(($activeLivestock / max($totalLivestock, 1)) * 100, 2) . "%\n\n";

            // Feed analytics
            $totalFeedPurchases = FeedPurchase::count();
            $completedFeedPurchases = FeedPurchase::where('status', 'completed')->count();

            $result .= "Feed Purchase Analytics:\n";
            $result .= "• Total purchases: {$totalFeedPurchases}\n";
            $result .= "• Completed purchases: {$completedFeedPurchases}\n";
            $result .= "• Completion rate: " . round(($completedFeedPurchases / max($totalFeedPurchases, 1)) * 100, 2) . "%\n";

            return substr($result, 0, $contextLimit);
        } catch (\Exception $e) {
            return "Error executing analytics query: " . $e->getMessage();
        }
    }

    /**
     * Execute raw query (with safety checks)
     */
    public static function executeRawQuery(string $query, int $contextLimit): string
    {
        // Safety check - only allow SELECT queries
        if (!preg_match('/^select\s+/i', trim($query))) {
            return "Only SELECT queries are allowed for security reasons";
        }

        // Additional safety checks
        $dangerousKeywords = ['delete', 'drop', 'insert', 'update', 'alter', 'create', 'truncate'];
        foreach ($dangerousKeywords as $keyword) {
            if (stripos($query, $keyword) !== false) {
                return "Query contains dangerous keyword: {$keyword}";
            }
        }

        try {
            $results = DB::select($query);

            if (empty($results)) {
                return "No results found for query: {$query}";
            }

            $result = "=== RAW QUERY RESULTS ===\n";
            $result .= "Query: {$query}\n";
            $result .= "Found: " . count($results) . " records\n\n";

            // Convert results to readable format
            foreach (array_slice($results, 0, 5) as $row) {
                $result .= "• " . json_encode($row) . "\n";
            }

            if (count($results) > 5) {
                $result .= "... and " . (count($results) - 5) . " more records\n";
            }

            return substr($result, 0, $contextLimit);
        } catch (\Exception $e) {
            return "Error executing raw query: " . $e->getMessage();
        }
    }

    /**
     * Get livestock performance data
     */
    public static function getLivestockPerformanceData(int $contextLimit): string
    {
        try {
            $result = "=== LIVESTOCK PERFORMANCE DATA ===\n\n";

            // Performance metrics
            $totalLivestock = Livestock::count();
            $activeLivestock = Livestock::where('status', 'active')->count();
            $completedLivestock = Livestock::where('status', 'completed')->count();

            $result .= "Performance Metrics:\n";
            $result .= "• Total livestock: {$totalLivestock}\n";
            $result .= "• Active livestock: {$activeLivestock}\n";
            $result .= "• Completed livestock: {$completedLivestock}\n";
            $result .= "• Success rate: " . round(($completedLivestock / max($totalLivestock, 1)) * 100, 2) . "%\n\n";

            // Recent performance
            $recentLivestock = Livestock::latest()->take(5)->get();
            $result .= "Recent Livestock:\n";
            foreach ($recentLivestock as $livestock) {
                $result .= "• {$livestock->name}: {$livestock->getStatusLabel()}\n";
            }

            return substr($result, 0, $contextLimit);
        } catch (\Exception $e) {
            return "Error getting livestock performance data: " . $e->getMessage();
        }
    }

    /**
     * Get feed analytics data
     */
    public static function getFeedAnalyticsData(int $contextLimit): string
    {
        try {
            $result = "=== FEED ANALYTICS DATA ===\n\n";

            // Feed purchase analytics
            $totalPurchases = FeedPurchase::count();
            $completedPurchases = FeedPurchase::where('status', 'completed')->count();
            $pendingPurchases = FeedPurchase::where('status', 'pending')->count();

            $result .= "Feed Purchase Analytics:\n";
            $result .= "• Total purchases: {$totalPurchases}\n";
            $result .= "• Completed: {$completedPurchases}\n";
            $result .= "• Pending: {$pendingPurchases}\n";
            $result .= "• Completion rate: " . round(($completedPurchases / max($totalPurchases, 1)) * 100, 2) . "%\n\n";

            // Recent purchases
            $recentPurchases = FeedPurchase::latest()->take(5)->get();
            $result .= "Recent Purchases:\n";
            foreach ($recentPurchases as $purchase) {
                $result .= "• {$purchase->invoice_number}: {$purchase->getStatusLabel()}\n";
            }

            return substr($result, 0, $contextLimit);
        } catch (\Exception $e) {
            return "Error getting feed analytics data: " . $e->getMessage();
        }
    }

    /**
     * Get supply inventory data
     */
    public static function getSupplyInventoryData(int $contextLimit): string
    {
        try {
            $result = "=== SUPPLY INVENTORY DATA ===\n\n";

            // Supply purchase analytics
            $totalPurchases = SupplyPurchase::count();
            $completedPurchases = SupplyPurchase::where('status', 'completed')->count();

            $result .= "Supply Purchase Analytics:\n";
            $result .= "• Total purchases: {$totalPurchases}\n";
            $result .= "• Completed: {$completedPurchases}\n";
            $result .= "• Completion rate: " . round(($completedPurchases / max($totalPurchases, 1)) * 100, 2) . "%\n\n";

            // Recent purchases
            $recentPurchases = SupplyPurchase::latest()->take(5)->get();
            $result .= "Recent Purchases:\n";
            foreach ($recentPurchases as $purchase) {
                $result .= "• ID {$purchase->id}: {$purchase->status}\n";
            }

            return substr($result, 0, $contextLimit);
        } catch (\Exception $e) {
            return "Error getting supply inventory data: " . $e->getMessage();
        }
    }

    /**
     * Get financial summary data
     */
    public static function getFinancialSummaryData(int $contextLimit): string
    {
        try {
            $result = "=== FINANCIAL SUMMARY DATA ===\n\n";

            // Basic financial metrics
            $totalFeedPurchases = FeedPurchase::count();
            $totalSupplyPurchases = SupplyPurchase::count();

            $result .= "Financial Overview:\n";
            $result .= "• Total feed purchases: {$totalFeedPurchases}\n";
            $result .= "• Total supply purchases: {$totalSupplyPurchases}\n";
            $result .= "• Total transactions: " . ($totalFeedPurchases + $totalSupplyPurchases) . "\n\n";

            // Recent financial activity
            $recentFeedPurchases = FeedPurchase::latest()->take(3)->get();
            $result .= "Recent Feed Purchases:\n";
            foreach ($recentFeedPurchases as $purchase) {
                $result .= "• {$purchase->invoice_number}: {$purchase->getStatusLabel()}\n";
            }

            return substr($result, 0, $contextLimit);
        } catch (\Exception $e) {
            return "Error getting financial summary data: " . $e->getMessage();
        }
    }

    /**
     * Get operational metrics data
     */
    public static function getOperationalMetricsData(int $contextLimit): string
    {
        try {
            $result = "=== OPERATIONAL METRICS DATA ===\n\n";

            // Operational metrics
            $totalLivestock = Livestock::count();
            $activeLivestock = Livestock::where('status', 'active')->count();
            $totalFeedPurchases = FeedPurchase::count();
            $completedFeedPurchases = FeedPurchase::where('status', 'completed')->count();

            $result .= "Operational Metrics:\n";
            $result .= "• Livestock efficiency: " . round(($activeLivestock / max($totalLivestock, 1)) * 100, 2) . "%\n";
            $result .= "• Feed purchase efficiency: " . round(($completedFeedPurchases / max($totalFeedPurchases, 1)) * 100, 2) . "%\n";
            $result .= "• Overall operational health: " . round((($activeLivestock / max($totalLivestock, 1)) + ($completedFeedPurchases / max($totalFeedPurchases, 1))) * 50, 2) . "%\n";

            return substr($result, 0, $contextLimit);
        } catch (\Exception $e) {
            return "Error getting operational metrics data: " . $e->getMessage();
        }
    }

    /**
     * Execute livestock purchase query with natural language support
     */
    public static function executeLivestockPurchaseQuery(string $criteria, int $contextLimit): string
    {
        try {
            $result = "=== LIVESTOCK PURCHASE DATA ===\n\n";

            // Parse natural language criteria
            $dateRange = self::parseDateRange($criteria);
            $statusFilter = self::parseStatusFilter($criteria);

            $query = LivestockPurchase::with(['supplier', 'farm', 'coop']);

            // Apply date filter
            if ($dateRange) {
                $query->whereBetween('tanggal', [$dateRange['start'], $dateRange['end']]);
            }

            // Apply status filter
            if ($statusFilter) {
                $query->where('status', $statusFilter);
            }

            $purchases = $query->latest()->take(10)->get();

            if ($purchases->isEmpty()) {
                return "No livestock purchases found matching criteria: {$criteria}";
            }

            $result .= "Livestock Purchases:\n";
            foreach ($purchases as $purchase) {
                $supplierName = $purchase->supplier ? $purchase->supplier->name : 'N/A';
                $farmName = $purchase->farm ? $purchase->farm->name : 'N/A';
                $date = $purchase->tanggal ? $purchase->tanggal->format('Y-m-d') : 'N/A';

                $result .= "• Invoice: {$purchase->invoice_number}\n";
                $result .= "  - Date: {$date}\n";
                $result .= "  - Supplier: {$supplierName}\n";
                $result .= "  - Farm: {$farmName}\n";
                $result .= "  - Status: {$purchase->getStatusLabel()}\n";
                $result .= "  - Items: " . $purchase->details->count() . " items\n\n";
            }

            return substr($result, 0, $contextLimit);
        } catch (\Exception $e) {
            return "Error executing livestock purchase query: " . $e->getMessage();
        }
    }

    /**
     * Execute feed purchase query with natural language support
     */
    public static function executeFeedPurchaseQuery(string $criteria, int $contextLimit): string
    {
        try {
            $result = "=== FEED PURCHASE DATA ===\n\n";

            // Parse natural language criteria
            $dateRange = self::parseDateRange($criteria);
            $statusFilter = self::parseStatusFilter($criteria);

            $query = FeedPurchase::with(['supplier', 'farm', 'coop']);

            // Apply date filter
            if ($dateRange) {
                $query->whereBetween('date', [$dateRange['start'], $dateRange['end']]);
            }

            // Apply status filter
            if ($statusFilter) {
                $query->where('status', $statusFilter);
            }

            $purchases = $query->latest()->take(10)->get();

            if ($purchases->isEmpty()) {
                return "No feed purchases found matching criteria: {$criteria}";
            }

            $result .= "Feed Purchases:\n";
            foreach ($purchases as $purchase) {
                $supplierName = $purchase->supplier ? $purchase->supplier->name : 'N/A';
                $farmName = $purchase->farm ? $purchase->farm->name : 'N/A';
                $date = $purchase->date ? $purchase->date->format('Y-m-d') : 'N/A';

                $result .= "• Invoice: {$purchase->invoice_number}\n";
                $result .= "  - Date: {$date}\n";
                $result .= "  - Supplier: {$supplierName}\n";
                $result .= "  - Farm: {$farmName}\n";
                $result .= "  - Status: {$purchase->getStatusLabel()}\n";
                $result .= "  - Items: " . $purchase->feedPurchaseItems->count() . " items\n\n";
            }

            return substr($result, 0, $contextLimit);
        } catch (\Exception $e) {
            return "Error executing feed purchase query: " . $e->getMessage();
        }
    }

    /**
     * Execute supply purchase query with natural language support
     */
    public static function executeSupplyPurchaseQuery(string $criteria, int $contextLimit): string
    {
        try {
            $result = "=== SUPPLY PURCHASE DATA ===\n\n";

            // Parse natural language criteria
            $dateRange = self::parseDateRange($criteria);
            $statusFilter = self::parseStatusFilter($criteria);

            $query = SupplyPurchaseBatch::with(['supplier', 'farm', 'supplyPurchases.supply']);

            // Apply date filter
            if ($dateRange) {
                $query->whereBetween('date', [$dateRange['start'], $dateRange['end']]);
            }

            // Apply status filter
            if ($statusFilter) {
                $query->where('status', $statusFilter);
            }

            $batches = $query->latest()->take(10)->get();

            if ($batches->isEmpty()) {
                return "No supply purchases found matching criteria: {$criteria}";
            }

            $result .= "Supply Purchases:\n";
            foreach ($batches as $batch) {
                $supplierName = $batch->supplier ? $batch->supplier->name : 'N/A';
                $farmName = $batch->farm ? $batch->farm->name : 'N/A';
                $date = $batch->date ? $batch->date->format('Y-m-d') : 'N/A';

                $result .= "• Invoice: {$batch->invoice_number}\n";
                $result .= "  - Date: {$date}\n";
                $result .= "  - Supplier: {$supplierName}\n";
                $result .= "  - Farm: {$farmName}\n";
                $result .= "  - Status: {$batch->getStatusLabel()}\n";
                $result .= "  - Items: " . $batch->supplyPurchases->count() . " items\n\n";
            }

            return substr($result, 0, $contextLimit);
        } catch (\Exception $e) {
            return "Error executing supply purchase query: " . $e->getMessage();
        }
    }

    /**
     * Execute natural language purchase query
     */
    public static function executeNaturalLanguagePurchaseQuery(string $timeCriteria, int $contextLimit): string
    {
        try {
            $result = "=== PURCHASE SUMMARY DATA ===\n\n";

            // Parse time criteria
            $dateRange = self::parseDateRange($timeCriteria);

            if (!$dateRange) {
                return "Unable to parse time criteria: {$timeCriteria}. Please specify a valid date range.";
            }

            // Get all purchase types for the date range
            $livestockPurchases = LivestockPurchase::whereBetween('tanggal', [$dateRange['start'], $dateRange['end']])->count();
            $feedPurchases = FeedPurchase::whereBetween('date', [$dateRange['start'], $dateRange['end']])->count();
            $supplyPurchases = SupplyPurchaseBatch::whereBetween('date', [$dateRange['start'], $dateRange['end']])->count();

            $result .= "Purchase Summary for {$timeCriteria}:\n";
            $result .= "• Livestock Purchases: {$livestockPurchases}\n";
            $result .= "• Feed Purchases: {$feedPurchases}\n";
            $result .= "• Supply Purchases: {$supplyPurchases}\n";
            $result .= "• Total Purchases: " . ($livestockPurchases + $feedPurchases + $supplyPurchases) . "\n\n";

            // Get recent purchases for each type
            if ($livestockPurchases > 0) {
                $recentLivestock = LivestockPurchase::whereBetween('tanggal', [$dateRange['start'], $dateRange['end']])
                    ->latest()->take(3)->get();
                $result .= "Recent Livestock Purchases:\n";
                foreach ($recentLivestock as $purchase) {
                    $result .= "• {$purchase->invoice_number} ({$purchase->getStatusLabel()})\n";
                }
                $result .= "\n";
            }

            if ($feedPurchases > 0) {
                $recentFeed = FeedPurchase::whereBetween('date', [$dateRange['start'], $dateRange['end']])
                    ->latest()->take(3)->get();
                $result .= "Recent Feed Purchases:\n";
                foreach ($recentFeed as $purchase) {
                    $result .= "• {$purchase->invoice_number} ({$purchase->getStatusLabel()})\n";
                }
                $result .= "\n";
            }

            if ($supplyPurchases > 0) {
                $recentSupply = SupplyPurchaseBatch::whereBetween('date', [$dateRange['start'], $dateRange['end']])
                    ->latest()->take(3)->get();
                $result .= "Recent Supply Purchases:\n";
                foreach ($recentSupply as $purchase) {
                    $result .= "• {$purchase->invoice_number} ({$purchase->getStatusLabel()})\n";
                }
                $result .= "\n";
            }

            return substr($result, 0, $contextLimit);
        } catch (\Exception $e) {
            return "Error executing natural language purchase query: " . $e->getMessage();
        }
    }

    /**
     * Parse date range from natural language
     */
    private static function parseDateRange(string $criteria): ?array
    {
        // Parse month and year patterns
        if (preg_match('/(?:bulan|month)\s+(\w+)\s+(\d{4})/i', $criteria, $matches)) {
            $monthName = strtolower($matches[1]);
            $year = $matches[2];
            $monthNumber = self::convertMonthToNumber($monthName);

            if ($monthNumber) {
                $startDate = Carbon::createFromDate($year, $monthNumber, 1);
                $endDate = $startDate->copy()->endOfMonth();
                return ['start' => $startDate, 'end' => $endDate];
            }
        }

        // Parse specific date patterns
        if (preg_match('/(\d{4}-\d{2}-\d{2})\s+(?:sampai|to)\s+(\d{4}-\d{2}-\d{2})/i', $criteria, $matches)) {
            return [
                'start' => Carbon::parse($matches[1]),
                'end' => Carbon::parse($matches[2])
            ];
        }

        // Parse single date
        if (preg_match('/(\d{4}-\d{2}-\d{2})/i', $criteria, $matches)) {
            $date = Carbon::parse($matches[1]);
            return ['start' => $date, 'end' => $date];
        }

        return null;
    }

    /**
     * Parse status filter from natural language
     */
    private static function parseStatusFilter(string $criteria): ?string
    {
        $statusMap = [
            'draft' => 'draft',
            'pending' => 'pending',
            'confirmed' => 'confirmed',
            'in_transit' => 'in_transit',
            'arrived' => 'arrived',
            'completed' => 'completed',
            'cancelled' => 'cancelled',
            'selesai' => 'completed',
            'dibatalkan' => 'cancelled',
            'dalam perjalanan' => 'in_transit',
            'tiba' => 'arrived',
            'dikonfirmasi' => 'confirmed',
        ];

        foreach ($statusMap as $keyword => $status) {
            if (stripos($criteria, $keyword) !== false) {
                return $status;
            }
        }

        return null;
    }

    /**
     * Convert month name to number
     */
    private static function convertMonthToNumber(string $monthName): ?int
    {
        $months = [
            'januari' => 1,
            'january' => 1,
            'februari' => 2,
            'february' => 2,
            'maret' => 3,
            'march' => 3,
            'april' => 4,
            'mei' => 5,
            'may' => 5,
            'juni' => 6,
            'june' => 6,
            'juli' => 7,
            'july' => 7,
            'agustus' => 8,
            'august' => 8,
            'september' => 9,
            'oktober' => 10,
            'october' => 10,
            'november' => 11,
            'desember' => 12,
            'december' => 12,
        ];

        return $months[strtolower($monthName)] ?? null;
    }
}

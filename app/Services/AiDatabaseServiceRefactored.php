<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

/**
 * Refactored AiDatabaseService that focuses purely on data retrieval without text manipulation.
 * This service provides raw data in structured formats that can be consumed by AI models
 * for natural language processing and response generation.
 */
class AiDatabaseServiceRefactored
{
    /**
     * Check if user has SuperAdmin role - Centralized helper method
     *
     * @param User $user The user to check
     * @return bool True if user is SuperAdmin, false otherwise
     */
    private function isSuperAdmin(User $user): bool
    {
        $userRoles = $user->getRoleNames()->toArray();
        return !empty(array_intersect(
            array_map('strtolower', $userRoles),
            ['superadmin', 'super-admin', 'system', 'admin']
        ));
    }

    /**
     * Get livestock summary for the current user/company
     *
     * @return array Livestock summary data in structured format
     */
    public function getLivestockSummary(): array
    {
        try {
            $user = Auth::user();
            $companyId = $user->company_id;

            // Example queries - adjust table names based on your schema
            $summary = [
                'total_livestock' => $this->getTotalLivestock($companyId),
                'active_batches' => $this->getActiveBatches($companyId),
                'recent_mortality' => $this->getRecentMortality($companyId),
                'feed_consumption' => $this->getFeedConsumption($companyId),
            ];

            return $summary;
        } catch (Exception $e) {
            Log::error('AiDatabaseServiceRefactored: Error getting livestock summary', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            return [];
        }
    }

    /**
     * Get financial summary
     *
     * @return array Financial summary data in structured format
     */
    public function getFinancialSummary(): array
    {
        try {
            $user = Auth::user();
            $companyId = $user->company_id;
            $currentMonth = Carbon::now()->format('Y-m');

            $summary = [
                'monthly_expenses' => $this->getMonthlyExpenses($companyId, $currentMonth),
                'monthly_revenue' => $this->getMonthlyRevenue($companyId, $currentMonth),
                'feed_costs' => $this->getFeedCosts($companyId, $currentMonth),
                'recent_purchases' => $this->getRecentPurchases($companyId),
            ];

            return $summary;
        } catch (Exception $e) {
            Log::error('AiDatabaseServiceRefactored: Error getting financial summary', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            return [];
        }
    }

    /**
     * Search data based on user query - Enhanced with optimized keyword detection
     *
     * @param string $query The user's search query
     * @param array $filters Optional filters to apply to the search
     * @return array Search results in structured format
     */
    public function searchData(string $query, array $filters = []): array
    {
        try {
            $user = Auth::user();
            $companyId = $user->company_id;
            $results = [];

            // DEBUG: Log user and company information
            Log::info('AiDatabaseServiceRefactored: Starting data search', [
                'query' => $query,
                'user_id' => $user->id,
                'user_company_id' => $companyId,
                'user_email' => $user->email ?? 'N/A'
            ]);

            // Analyze query to determine what data to fetch
            $queryLower = strtolower($query);

            // Check for company/organization related queries first
            if (
                strpos($queryLower, 'perusahaan') !== false || strpos($queryLower, 'company') !== false ||
                strpos($queryLower, 'organisasi') !== false || strpos($queryLower, 'terdaftar') !== false ||
                strpos($queryLower, 'list perusahaan') !== false || strpos($queryLower, 'daftar perusahaan') !== false ||
                strpos($queryLower, 'show all companies') !== false || strpos($queryLower, 'list companies') !== false ||
                strpos($queryLower, 'companies') !== false || strpos($queryLower, 'all companies') !== false
            ) {
                $results['companies'] = $this->getCompanyData($user);
            }

            // Check for farm/farming operations (peternakan) - Priority check to avoid confusion with livestock
            if (
                strpos($queryLower, 'peternakan') !== false || strpos($queryLower, 'kandang') !== false ||
                strpos($queryLower, 'farm') !== false || strpos($queryLower, 'coop') !== false ||
                strpos($queryLower, 'fasilitas') !== false || strpos($queryLower, 'facility') !== false ||
                strpos($queryLower, 'lokasi') !== false || strpos($queryLower, 'location') !== false ||
                strpos($queryLower, 'operasi') !== false || strpos($queryLower, 'operation') !== false ||
                strpos($queryLower, 'usaha') !== false || strpos($queryLower, 'business') !== false
            ) {

                // Detect farm status filter from query
                $farmStatusFilter = 'active'; // default
                
                // Check for inactive farms queries FIRST (highest priority for specific status)
                if (strpos($queryLower, 'farm tidak aktif') !== false || strpos($queryLower, 'farm nonaktif') !== false ||
                    strpos($queryLower, 'farm inactive') !== false || strpos($queryLower, 'farm non-aktif') !== false ||
                    strpos($queryLower, 'tidak aktif') !== false || strpos($queryLower, 'nonaktif') !== false ||
                    strpos($queryLower, 'inactive farm') !== false || strpos($queryLower, 'inactive only') !== false ||
                    strpos($queryLower, 'show me inactive') !== false || strpos($queryLower, 'show inactive') !== false ||
                    strpos($queryLower, 'list inactive') !== false || strpos($queryLower, 'tampilkan tidak aktif') !== false ||
                    strpos($queryLower, 'ada berapa farm tidak aktif') !== false || strpos($queryLower, 'berapa farm tidak aktif') !== false) {
                    $farmStatusFilter = 'inactive';
                } 
                // Check for active farms queries
                elseif (strpos($queryLower, 'farm aktif') !== false || strpos($queryLower, 'active farm') !== false ||
                         strpos($queryLower, 'berapa farm aktif') !== false || strpos($queryLower, 'ada berapa farm aktif') !== false) {
                    $farmStatusFilter = 'active';
                }
                // Check for 'all farms' queries (lower priority to avoid conflicts)
                elseif (strpos($queryLower, 'semua farm') !== false || strpos($queryLower, 'all farm') !== false ||
                         strpos($queryLower, 'total farm') !== false || strpos($queryLower, 'seluruh farm') !== false ||
                         strpos($queryLower, 'jumlah farm') !== false || strpos($queryLower, 'berapa farm') !== false ||
                         strpos($queryLower, 'ada berapa farm') !== false || strpos($queryLower, 'how many farm') !== false ||
                         strpos($queryLower, 'show me all farm') !== false || strpos($queryLower, 'show all farm') !== false ||
                         strpos($queryLower, 'tampilkan semua farm') !== false || strpos($queryLower, 'list all farm') !== false ||
                         strpos($queryLower, 'count farm') !== false || strpos($queryLower, 'number of farm') !== false ||
                         strpos($queryLower, 'berapa jumlah farm') !== false || strpos($queryLower, 'ada berapa jumlah farm') !== false) {
                    $farmStatusFilter = 'all';
                }

                Log::info('AiDatabaseServiceRefactored: Farm query detected', [
                    'query' => $query,
                    'user_id' => Auth::id(),
                    'company_id' => $companyId,
                    'company_id_is_null' => is_null($companyId),
                    'farm_status_filter' => $farmStatusFilter,
                    'detected_keywords' => array_filter([
                        'peternakan' => strpos($queryLower, 'peternakan') !== false,
                        'kandang' => strpos($queryLower, 'kandang') !== false,
                        'farm' => strpos($queryLower, 'farm') !== false
                    ])
                ]);

                $results['farms'] = $this->getFarmDetails($companyId, $farmStatusFilter);

                Log::info('AiDatabaseServiceRefactored: Farm data retrieved', [
                    'company_id' => $companyId,
                    'farm_results' => $results['farms'],
                    'farm_count' => isset($results['farms']['total_farms']) ? $results['farms']['total_farms'] : 0
                ]);

                // DEBUG: If no farms found, check if it's due to company_id mismatch
                if (empty($results['farms']) || (isset($results['farms']['total_farms']) && $results['farms']['total_farms'] === 0)) {
                    Log::warning('AiDatabaseServiceRefactored: No farms found for user', [
                        'user_company_id' => $companyId,
                        'user_id' => Auth::id(),
                        'query' => $query
                    ]);

                    // Check if there are farms for other companies
                    try {
                        $totalFarmsInDb = DB::table('farms')
                            ->where('status', 'active')
                            ->whereNull('deleted_at')
                            ->count();

                        Log::info('AiDatabaseServiceRefactored: Total active farms in database', [
                            'total_farms' => $totalFarmsInDb,
                            'user_company_id' => $companyId
                        ]);

                        if ($totalFarmsInDb > 0) {
                            Log::warning('AiDatabaseServiceRefactored: Farms exist but not for user\'s company', [
                                'total_farms_in_db' => $totalFarmsInDb,
                                'user_company_id' => $companyId
                            ]);
                        }
                    } catch (Exception $debugError) {
                        Log::error('AiDatabaseServiceRefactored: Debug query failed', ['error' => $debugError->getMessage()]);
                    }
                }
            }

            // Check for livestock animals (ternak) - Exclude if already detected as farm operation
            // Also exclude if query contains 'peternakan' to avoid confusion
            if (
                !isset($results['farms']) &&
                strpos($queryLower, 'peternakan') === false && // Critical: Don't match livestock if 'peternakan' is mentioned
                (strpos($queryLower, 'ternak') !== false || strpos($queryLower, 'livestock') !== false ||
                    strpos($queryLower, 'ayam') !== false || strpos($queryLower, 'chicken') !== false ||
                    strpos($queryLower, 'bebek') !== false || strpos($queryLower, 'duck') !== false ||
                    strpos($queryLower, 'hewan') !== false || strpos($queryLower, 'animal') !== false)
            ) {
                $results['livestock'] = $this->getLivestockSummary();
                $results['livestock_details'] = $this->getLivestockDetails($companyId);
            }

            // If user specifically asks for both farm AND livestock, provide both
            if ((strpos($queryLower, 'ternak') !== false || strpos($queryLower, 'livestock') !== false) &&
                (strpos($queryLower, 'kandang') !== false || strpos($queryLower, 'farm') !== false) &&
                (strpos($queryLower, 'dan') !== false || strpos($queryLower, 'and') !== false ||
                    strpos($queryLower, 'semua') !== false || strpos($queryLower, 'all') !== false ||
                    strpos($queryLower, 'kedua') !== false || strpos($queryLower, 'both') !== false)
            ) {
                $results['livestock'] = $this->getLivestockSummary();
                $results['livestock_details'] = $this->getLivestockDetails($companyId);
                $results['farms'] = $this->getFarmDetails($companyId, 'active');
            }

            if (
                strpos($queryLower, 'keuangan') !== false || strpos($queryLower, 'financial') !== false ||
                strpos($queryLower, 'untung') !== false || strpos($queryLower, 'rugi') !== false ||
                strpos($queryLower, 'profit') !== false || strpos($queryLower, 'loss') !== false ||
                strpos($queryLower, 'pendapatan') !== false || strpos($queryLower, 'income') !== false ||
                strpos($queryLower, 'biaya') !== false || strpos($queryLower, 'cost') !== false
            ) {
                $results['financial'] = $this->getFinancialSummary();
            }

            if (
                strpos($queryLower, 'pakan') !== false || strpos($queryLower, 'feed') !== false ||
                strpos($queryLower, 'makanan') !== false || strpos($queryLower, 'food') !== false
            ) {
                $results['feed'] = $this->getFeedData($companyId);
                $results['feed_usage'] = $this->getFeedUsageDetails($companyId);
            }

            if (
                strpos($queryLower, 'berapa') !== false || strpos($queryLower, 'how many') !== false ||
                strpos($queryLower, 'jumlah') !== false || strpos($queryLower, 'total') !== false ||
                strpos($queryLower, 'count') !== false || strpos($queryLower, 'sum') !== false
            ) {
                $results['counts'] = $this->getCounts($companyId);
            }

            if (
                strpos($queryLower, 'batch') !== false || strpos($queryLower, 'periode') !== false ||
                strpos($queryLower, 'period') !== false || strpos($queryLower, 'cycle') !== false ||
                strpos($queryLower, 'siklus') !== false
            ) {
                $results['batches'] = $this->getBatchDetails($companyId);
            }

            return $results;
        } catch (Exception $e) {
            Log::error('AiDatabaseServiceRefactored: Error searching data', [
                'error' => $e->getMessage(),
                'query' => $query,
                'user_id' => Auth::id()
            ]);
            return [];
        }
    }

    /**
     * Get total livestock count
     *
     * @param int|null $companyId The company ID to filter by, or null for all
     * @return int Total livestock count
     */
    private function getTotalLivestock($companyId): int
    {
        try {
            $user = Auth::user();
            $isSuperAdmin = $this->isSuperAdmin($user);

            $query = DB::table('livestocks')
                ->whereNull('deleted_at')
                ->where('status', '!=', 'inactive');

            // SuperAdmin bypass: see all livestock, regular users see only their company's livestock
            if (!$isSuperAdmin && $companyId) {
                $query->where('company_id', $companyId);
            } else if (!$isSuperAdmin && !$companyId) {
                $query->whereNull('company_id');
            }

            return $query->count();
        } catch (Exception $e) {
            Log::warning('AiDatabaseServiceRefactored: Could not get livestock count', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Get active batches count
     *
     * @param int|null $companyId The company ID to filter by, or null for all
     * @return int Active batches count
     */
    private function getActiveBatches($companyId): int
    {
        try {
            $user = Auth::user();
            $isSuperAdmin = $this->isSuperAdmin($user);

            $query = DB::table('livestock_batches')
                ->where('status', 'active')
                ->whereNull('deleted_at');

            // SuperAdmin bypass: see all batches, regular users see only their company's batches
            if (!$isSuperAdmin && $companyId) {
                $query->where('company_id', $companyId);
            }

            return $query->count();
        } catch (Exception $e) {
            Log::warning('AiDatabaseServiceRefactored: Could not get batch count', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Get recent mortality data
     *
     * @param int|null $companyId The company ID to filter by, or null for all
     * @return array Recent mortality data in structured format
     */
    private function getRecentMortality($companyId): array
    {
        try {
            $user = Auth::user();
            $isSuperAdmin = $this->isSuperAdmin($user);

            // Use livestock_depletions table for mortality data
            $query = DB::table('livestock_depletions')
                ->where('depletion_type', 'mortality')
                ->where('created_at', '>=', Carbon::now()->subDays(30));

            // SuperAdmin bypass: see all mortality data, regular users see only their company's data
            if (!$isSuperAdmin && $companyId) {
                $query->where('company_id', $companyId);
            }

            $mortalityCount = $query->sum('quantity') ?? 0;

            return [
                'count' => $mortalityCount,
                'period' => '30 days'
            ];
        } catch (Exception $e) {
            Log::warning('AiDatabaseServiceRefactored: Could not get mortality data', ['error' => $e->getMessage()]);
            return ['count' => 0, 'period' => '30 days'];
        }
    }

    /**
     * Get feed consumption data
     *
     * @param int|null $companyId The company ID to filter by, or null for all
     * @return array Feed consumption data in structured format
     */
    private function getFeedConsumption($companyId): array
    {
        try {
            $user = Auth::user();
            $isSuperAdmin = $this->isSuperAdmin($user);

            // Use feed_usages table for consumption data
            $query = DB::table('feed_usages')
                ->where('created_at', '>=', Carbon::now()->subDays(7));

            // SuperAdmin bypass: see all feed consumption, regular users see only their company's data
            if (!$isSuperAdmin && $companyId) {
                $query->where('company_id', $companyId);
            }

            $totalConsumption = $query->sum('quantity') ?? 0;

            return [
                'total_kg' => $totalConsumption,
                'period' => '7 days'
            ];
        } catch (Exception $e) {
            Log::warning('AiDatabaseServiceRefactored: Could not get feed consumption', ['error' => $e->getMessage()]);
            return ['total_kg' => 0, 'period' => '7 days'];
        }
    }

    /**
     * Get monthly expenses
     *
     * @param int|null $companyId The company ID to filter by, or null for all
     * @param string $month The month in Y-m format (e.g., '2023-05')
     * @return float Monthly expenses amount
     */
    private function getMonthlyExpenses($companyId, $month): float
    {
        try {
            $user = Auth::user();
            $isSuperAdmin = $this->isSuperAdmin($user);

            // Get expenses from livestock purchase items
            $livestockQuery = DB::table('livestock_purchase_items')
                ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$month]);

            // SuperAdmin bypass: see all expenses, regular users see only their company's expenses
            if (!$isSuperAdmin && $companyId) {
                $livestockQuery->where('company_id', $companyId);
            }

            $livestockExpenses = $livestockQuery->sum('price_total') ?? 0;

            // Get expenses from feed purchase items (if table exists)
            $feedExpenses = 0;
            try {
                $feedQuery = DB::table('feed_purchase_items')
                    ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$month]);

                // SuperAdmin bypass: see all feed expenses, regular users see only their company's expenses
                if (!$isSuperAdmin && $companyId) {
                    $feedQuery->where('company_id', $companyId);
                }

                $feedExpenses = $feedQuery->sum('price_total') ?? 0;
            } catch (Exception $e) {
                // Feed purchase items table might not exist, ignore
            }

            // Get expenses from supply purchase items (if table exists)
            $supplyExpenses = 0;
            try {
                $supplyQuery = DB::table('supply_purchase_items')
                    ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$month]);

                // SuperAdmin bypass: see all supply expenses, regular users see only their company's expenses
                if (!$isSuperAdmin && $companyId) {
                    $supplyQuery->where('company_id', $companyId);
                }

                $supplyExpenses = $supplyQuery->sum('price_total') ?? 0;
            } catch (Exception $e) {
                // Supply purchase items table might not exist, ignore
            }

            return $livestockExpenses + $feedExpenses + $supplyExpenses;
        } catch (Exception $e) {
            Log::warning('AiDatabaseServiceRefactored: Could not get monthly expenses', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Get monthly revenue
     *
     * @param int|null $companyId The company ID to filter by, or null for all
     * @param string $month The month in Y-m format (e.g., '2023-05')
     * @return float Monthly revenue amount
     */
    private function getMonthlyRevenue($companyId, $month): float
    {
        try {
            $user = Auth::user();
            $isSuperAdmin = $this->isSuperAdmin($user);

            // Get revenue from livestock sales items
            $livestockQuery = DB::table('livestock_sales_items')
                ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$month]);

            // SuperAdmin bypass: see all revenue, regular users see only their company's revenue
            if (!$isSuperAdmin && $companyId) {
                $livestockQuery->where('company_id', $companyId);
            }

            $livestockRevenue = $livestockQuery->sum('price_total') ?? 0;

            // Get revenue from recording sales
            $recordingQuery = DB::table('recording_sale_items')
                ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$month]);

            // SuperAdmin bypass: see all recording revenue, regular users see only their company's revenue
            if (!$isSuperAdmin && $companyId) {
                $recordingQuery->where('company_id', $companyId);
            }

            $recordingRevenue = $recordingQuery->sum('total_price') ?? 0;

            return $livestockRevenue + $recordingRevenue;
        } catch (Exception $e) {
            Log::warning('AiDatabaseServiceRefactored: Could not get monthly revenue', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Get feed costs
     *
     * @param int|null $companyId The company ID to filter by, or null for all
     * @param string $month The month in Y-m format (e.g., '2023-05')
     * @return float Feed costs amount
     */
    private function getFeedCosts($companyId, $month): float
    {
        try {
            $user = Auth::user();
            $isSuperAdmin = $this->isSuperAdmin($user);

            // Get feed costs from feed purchase items
            $query = DB::table('feed_purchase_items')
                ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$month]);

            // SuperAdmin bypass: see all feed costs, regular users see only their company's costs
            if (!$isSuperAdmin && $companyId) {
                $query->where('company_id', $companyId);
            }

            return $query->sum('price_total') ?? 0;
        } catch (Exception $e) {
            Log::warning('AiDatabaseServiceRefactored: Could not get feed costs', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Get recent purchases
     *
     * @param int|null $companyId The company ID to filter by, or null for all
     * @return array Recent purchases data in structured format
     */
    private function getRecentPurchases($companyId): array
    {
        try {
            $user = Auth::user();
            $isSuperAdmin = $this->isSuperAdmin($user);

            // Get recent livestock purchases with total amounts
            $livestockQuery = DB::table('livestock_purchase_items')
                ->select('created_at', 'price_total as amount', DB::raw("'livestock' as type"))
                ->where('created_at', '>=', Carbon::now()->subDays(7))
                ->orderBy('created_at', 'desc')
                ->limit(10);

            // SuperAdmin bypass: see all purchases, regular users see only their company's purchases
            if (!$isSuperAdmin && $companyId) {
                $livestockQuery->where('company_id', $companyId);
            }

            $livestockPurchases = $livestockQuery->get();

            // Get recent feed purchases if table exists
            $feedPurchases = collect();
            try {
                $feedQuery = DB::table('feed_purchase_items')
                    ->select('created_at', 'price_total as amount', DB::raw("'feed' as type"))
                    ->where('created_at', '>=', Carbon::now()->subDays(7))
                    ->orderBy('created_at', 'desc')
                    ->limit(10);

                // SuperAdmin bypass: see all feed purchases, regular users see only their company's purchases
                if (!$isSuperAdmin && $companyId) {
                    $feedQuery->where('company_id', $companyId);
                }

                $feedPurchases = $feedQuery->get();
            } catch (Exception $e) {
                // Feed purchase items table might not exist
            }

            $allPurchases = $livestockPurchases->concat($feedPurchases)
                ->sortByDesc('created_at')
                ->take(5)
                ->values();

            return $allPurchases->toArray();
        } catch (Exception $e) {
            Log::warning('AiDatabaseServiceRefactored: Could not get recent purchases', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get feed data
     *
     * @param int|null $companyId The company ID to filter by, or null for all
     * @return array Feed data in structured format
     */
    private function getFeedData($companyId): array
    {
        try {
            $user = Auth::user();
            $isSuperAdmin = $this->isSuperAdmin($user);

            // Use feed_stocks table for inventory data
            $query = DB::table('feed_stocks');

            // SuperAdmin bypass: see all feed data, regular users see only their company's feed data
            if (!$isSuperAdmin && $companyId) {
                $query->where('company_id', $companyId);
            }

            $totalStock = $query->sum('quantity') ?? 0;
            $lowStockItems = $query->where('quantity', '<', 100)->count();
            $feedTypes = $query->distinct()->count('feed_id');

            return [
                'total_stock_kg' => $totalStock,
                'low_stock_items' => $lowStockItems,
                'feed_types' => $feedTypes
            ];
        } catch (Exception $e) {
            Log::warning('AiDatabaseServiceRefactored: Could not get feed data', ['error' => $e->getMessage()]);
            return ['total_stock_kg' => 0, 'low_stock_items' => 0, 'feed_types' => 0];
        }
    }

    /**
     * Get various counts
     *
     * @param int|null $companyId The company ID to filter by, or null for all
     * @return array Various counts data in structured format
     */
    private function getCounts($companyId): array
    {
        $user = Auth::user();
        $isSuperAdmin = $this->isSuperAdmin($user);

        Log::info('AiDatabaseServiceRefactored: Getting counts', [
            'user_id' => $user->id,
            'company_id' => $companyId,
            'is_super_admin' => $isSuperAdmin
        ]);

        $counts = [
            'livestock' => $this->getTotalLivestock($companyId),
            'batches' => $this->getActiveBatches($companyId),
            'users' => $this->getUserCount($companyId),
        ];

        // Add farm counts with SuperAdmin bypass
        try {
            $farmQuery = DB::table('farms')
                ->where('status', 'active')
                ->whereNull('deleted_at');

            // SuperAdmin bypass: count all farms, regular users count only their company's farms
            if (!$isSuperAdmin && $companyId) {
                $farmQuery->where('company_id', $companyId);
            }

            $farmCounts = $farmQuery->count();

            $counts['farms'] = $farmCounts;
            $counts['active_farms'] = $farmCounts; // Same as farms since we filter by active
            $counts['access_level'] = $isSuperAdmin ? 'SuperAdmin (All Data)' : 'Company Scoped';

            Log::info('AiDatabaseServiceRefactored: Farm counts included', [
                'company_id' => $companyId,
                'is_super_admin' => $isSuperAdmin,
                'farm_count' => $farmCounts
            ]);
        } catch (Exception $e) {
            Log::warning('AiDatabaseServiceRefactored: Could not get farm counts', [
                'error' => $e->getMessage(),
                'company_id' => $companyId
            ]);
            $counts['farms'] = 0;
            $counts['active_farms'] = 0;
        }

        return $counts;
    }

    /**
     * Get user count
     *
     * @param int|null $companyId The company ID to filter by, or null for all
     * @return int User count
     */
    private function getUserCount($companyId): int
    {
        try {
            $user = Auth::user();
            $isSuperAdmin = $this->isSuperAdmin($user);

            $query = DB::table('users');

            // SuperAdmin bypass: see all users, regular users see only their company's users
            if (!$isSuperAdmin && $companyId) {
                $query->where('company_id', $companyId);
            }

            return $query->count();
        } catch (Exception $e) {
            Log::warning('AiDatabaseServiceRefactored: Could not get user count', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Get detailed livestock information
     *
     * @param int|null $companyId The company ID to filter by, or null for all
     * @return array Livestock details data in structured format
     */
    private function getLivestockDetails($companyId): array
    {
        try {
            $user = Auth::user();
            $isSuperAdmin = $this->isSuperAdmin($user);

            $livestocks = DB::table('livestocks')
                ->select('name', 'initial_quantity', 'quantity_depletion', 'quantity_sales', 'status', 'start_date')
                ->whereNull('deleted_at')
                ->orderBy('start_date', 'desc')
                ->limit(5);

            // SuperAdmin bypass: see all livestock, regular users see only their company's livestock
            if (!$isSuperAdmin && $companyId) {
                $livestocks->where('company_id', $companyId);
            }

            $livestocks = $livestocks->get();

            return [
                'recent_livestock' => $livestocks->toArray(),
                'active_count' => $livestocks->where('status', 'active')->count(),
                'total_initial_quantity' => $livestocks->sum('initial_quantity')
            ];
        } catch (Exception $e) {
            Log::warning('AiDatabaseServiceRefactored: Could not get livestock details', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get feed usage details
     *
     * @param int|null $companyId The company ID to filter by, or null for all
     * @return array Feed usage details data in structured format
     */
    private function getFeedUsageDetails($companyId): array
    {
        try {
            $user = Auth::user();
            $isSuperAdmin = $this->isSuperAdmin($user);

            $recentUsageQuery = DB::table('feed_usages')
                ->where('created_at', '>=', Carbon::now()->subDays(7));

            $monthlyUsageQuery = DB::table('feed_usages')
                ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [Carbon::now()->format('Y-m')]);

            // SuperAdmin bypass: see all feed usage, regular users see only their company's feed usage
            if (!$isSuperAdmin && $companyId) {
                $recentUsageQuery->where('company_id', $companyId);
                $monthlyUsageQuery->where('company_id', $companyId);
            }

            $recentUsage = $recentUsageQuery->sum('quantity') ?? 0;
            $thisMonthUsage = $monthlyUsageQuery->sum('quantity') ?? 0;

            return [
                'last_7_days_kg' => $recentUsage,
                'this_month_kg' => $thisMonthUsage,
                'daily_average' => $thisMonthUsage > 0 ? round($thisMonthUsage / Carbon::now()->day, 2) : 0
            ];
        } catch (Exception $e) {
            Log::warning('AiDatabaseServiceRefactored: Could not get feed usage details', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get farm details
     *
     * @param int|null $companyId The company ID to filter by, or null for all
     * @param string $statusFilter Filter farms by status: 'active', 'inactive', or 'all'
     * @return array Farm details data in structured format
     */
    private function getFarmDetails($companyId, $statusFilter = 'active'): array
    {
        try {
            $user = Auth::user();
            $isSuperAdmin = $this->isSuperAdmin($user);

            Log::info('AiDatabaseServiceRefactored: Getting farm details', [
                'user_id' => $user->id,
                'company_id' => $companyId,
                'is_super_admin' => $isSuperAdmin,
                'status_filter' => $statusFilter
            ]);

            $farmQuery = DB::table('farms')
                ->select('id', 'name', 'code', 'status', 'created_at', 'company_id')
                ->whereNull('deleted_at');  // Handle soft deletes

            // Apply status filter
            Log::info('AiDatabaseServiceRefactored: Applying status filter', [
                'status_filter' => $statusFilter,
                'filter_type' => gettype($statusFilter)
            ]);
            
            if ($statusFilter === 'active') {
                $farmQuery->where('status', 'active');
                Log::info('AiDatabaseServiceRefactored: Applied active filter');
            } elseif ($statusFilter === 'inactive') {
                $farmQuery->where('status', 'inactive');
                Log::info('AiDatabaseServiceRefactored: Applied inactive filter');
            } else {
                Log::info('AiDatabaseServiceRefactored: No status filter applied (showing all)');
            }
            // For 'all' status, no additional filter is applied

            // SuperAdmin bypass: see all farms, regular users see only their company's farms
            if (!$isSuperAdmin && $companyId) {
                $farmQuery->where('company_id', $companyId);
            }

            $farms = $farmQuery->get();

            // Handle coops table query with error handling
            $coops = 0;
            try {
                $coopQuery = DB::table('coops')
                    ->whereNull('deleted_at');   // Handle soft deletes for coops too

                // SuperAdmin bypass: see all coops, regular users see only their company's coops
                if (!$isSuperAdmin && $companyId) {
                    $coopQuery->where('company_id', $companyId);
                }

                $coops = $coopQuery->count();
            } catch (Exception $coopError) {
                Log::warning('AiDatabaseServiceRefactored: Could not query coops table', [
                    'error' => $coopError->getMessage(),
                    'company_id' => $companyId
                ]);
                // Continue without coops data
            }

            Log::info('AiDatabaseServiceRefactored: Farm details query results', [
                'company_id' => $companyId,
                'is_super_admin' => $isSuperAdmin,
                'farms_found' => $farms->count(),
                'coops_found' => $coops,
                'farm_names' => $farms->pluck('name')->toArray()
            ]);

            return [
                'total_farms' => $farms->count(),
                'total_coops' => $coops,
                'farm_list' => $farms->pluck('name')->toArray(),
                'active_farms' => $farms->where('status', 'active')->count(),
                // Removed access_level - not needed for user responses
                'farm_details' => $farms->map(function ($farm) {
                    return [
                        'id' => $farm->id,
                        'name' => $farm->name,
                        'code' => $farm->code ?? 'N/A',
                        'status' => $farm->status,
                        'company_id' => $farm->company_id,
                        'created_date' => Carbon::parse($farm->created_at)->format('Y-m-d')
                    ];
                })->toArray()
            ];
        } catch (Exception $e) {
            Log::error('AiDatabaseServiceRefactored: Could not get farm details', [
                'error' => $e->getMessage(),
                'company_id' => $companyId,
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * Get batch details
     *
     * @param int|null $companyId The company ID to filter by, or null for all
     * @return array Batch details data in structured format
     */
    private function getBatchDetails($companyId): array
    {
        try {
            $user = Auth::user();
            $isSuperAdmin = $this->isSuperAdmin($user);

            $activeBatchQuery = DB::table('livestock_batches')
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->orderBy('created_at', 'desc')
                ->limit(5);

            // SuperAdmin bypass: see all batches, regular users see only their company's batches
            if (!$isSuperAdmin && $companyId) {
                $activeBatchQuery->where('company_id', $companyId);
            }

            $activeBatches = $activeBatchQuery->get();

            $completedBatchQuery = DB::table('livestock_batches')
                ->where('status', 'completed')
                ->whereNull('deleted_at');

            // SuperAdmin bypass: see all completed batches, regular users see only their company's batches
            if (!$isSuperAdmin && $companyId) {
                $completedBatchQuery->where('company_id', $companyId);
            }

            $completedBatches = $completedBatchQuery->count();

            return [
                'active_batches' => $activeBatches->count(),
                'completed_batches' => $completedBatches,
                'batch_names' => $activeBatches->pluck('name')->take(5)->toArray(),
                'batch_details' => $activeBatches->map(function ($batch) {
                    return [
                        'id' => $batch->id,
                        'name' => $batch->name,
                        'status' => $batch->status,
                        'created_date' => Carbon::parse($batch->created_at)->format('Y-m-d')
                    ];
                })->toArray()
            ];
        } catch (Exception $e) {
            Log::warning('AiDatabaseServiceRefactored: Could not get batch details', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get company data based on user permissions
     *
     * @param User $user The authenticated user
     * @return array Company data in structured format
     */
    public function getCompanyData(User $user): array
    {
        try {
            $userRoles = $user->getRoleNames()->toArray();
            $isSuperAdmin = $this->isSuperAdmin($user);

            Log::info('AiDatabaseServiceRefactored: Getting company data', [
                'user_id' => $user->id,
                'user_roles' => $userRoles,
                'is_super_admin' => $isSuperAdmin,
                'user_company_id' => $user->company_id
            ]);

            $companies = collect();

            if ($isSuperAdmin) {
                // SuperAdmin can see all companies
                $companies = DB::table('companies')
                    ->select('id', 'code', 'name', 'address', 'phone', 'email', 'status', 'type', 'created_at')
                    ->whereNull('deleted_at')
                    ->orderBy('created_at', 'desc')
                    ->get();

                Log::info('AiDatabaseServiceRefactored: SuperAdmin accessing all companies', [
                    'total_companies' => $companies->count()
                ]);
            } else {
                // Regular users can only see their own company
                if ($user->company_id) {
                    $companies = DB::table('companies')
                        ->select('id', 'code', 'name', 'address', 'phone', 'email', 'status', 'type', 'created_at')
                        ->where('id', $user->company_id)
                        ->whereNull('deleted_at')
                        ->get();

                    Log::info('AiDatabaseServiceRefactored: Regular user accessing own company', [
                        'company_id' => $user->company_id,
                        'found_company' => $companies->count() > 0
                    ]);
                } else {
                    Log::warning('AiDatabaseServiceRefactored: User has no company association', [
                        'user_id' => $user->id
                    ]);
                }
            }

            if ($companies->isEmpty()) {
                return [
                    'total_companies' => 0,
                    'companies' => []
                ];
            }

            // Return raw company data without formatting
            $companyData = $companies->map(function ($company) {
                return [
                    'id' => $company->id,
                    'code' => $company->code,
                    'name' => $company->name,
                    'address' => $company->address,
                    'phone' => $company->phone,
                    'email' => $company->email,
                    'status' => $company->status,
                    'type' => $company->type,
                    'created_at' => $company->created_at
                ];
            });

            $result = [
                'total_companies' => $companies->count(),
                'companies' => $companyData->toArray()
            ];

            Log::info('AiDatabaseServiceRefactored: Company data retrieval successful', [
                'total_companies' => $companies->count()
            ]);

            return $result;
        } catch (Exception $e) {
            Log::error('AiDatabaseServiceRefactored: Error getting company data', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'total_companies' => 0,
                'companies' => []
            ];
        }
    }
}

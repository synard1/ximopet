<?php

namespace App\Services;

use App\Models\User;
use App\Models\Company;
use App\Models\Livestock;
use App\Models\Farm;
use App\Models\Kandang;
use App\Models\FeedPurchase;
use App\Models\SupplyPurchase;
use App\Models\LivestockSales;
use App\Models\RecordingSale;
use App\Services\PermissionChecker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Exception;

class DataAccessService
{
    protected PermissionChecker $permissionChecker;

    public function __construct(PermissionChecker $permissionChecker)
    {
        $this->permissionChecker = $permissionChecker;
    }

    /**
     * Get company list based on user permissions
     */
    public function getCompanyList(User $user): array
    {
        try {
            if (!$this->permissionChecker->canAccessData($user, 'company_list')) {
                return [
                    'error' => 'Insufficient permissions to access company data',
                    'suggestion' => 'You can view your company information in the Company Settings section.'
                ];
            }

            // For regular users, only show their company
            $userRoles = $user->getRoleNames()->toArray();
            $isSuperAdmin = !empty(array_intersect(
                array_map('strtolower', $userRoles),
                ['superadmin', 'super-admin', 'system', 'admin']
            ));

            if (!$isSuperAdmin && !$user->hasRole('company_admin')) {
                if (!$user->company_id) {
                    return [
                        'error' => 'No company association found',
                        'suggestion' => 'Please contact your administrator to assign you to a company.'
                    ];
                }

                return [
                    'companies' => [
                        [
                            'id' => $user->company_id,
                            'name' => $user->company->name ?? 'Unknown Company',
                            'type' => 'Your Company',
                            'user_role' => $user->getRoleNames()->first()
                        ]
                    ],
                    'total' => 1,
                    'access_level' => 'limited'
                ];
            }

            // Superadmin can see all companies
            $companies = Company::select('id', 'name', 'created_at')
                ->orderBy('name')
                ->get()
                ->map(function ($company) {
                    return [
                        'id' => $company->id,
                        'name' => $company->name,
                        'registered_date' => $company->created_at->format('Y-m-d'),
                        'type' => 'All Companies Access'
                    ];
                });

            return [
                'companies' => $companies->toArray(),
                'total' => $companies->count(),
                'access_level' => 'full'
            ];

        } catch (Exception $e) {
            Log::error('DataAccessService: Error getting company list', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);

            return [
                'error' => 'Failed to retrieve company data',
                'suggestion' => 'Please try again or contact support if the issue persists.'
            ];
        }
    }

    /**
     * Get livestock summary based on user permissions
     */
    public function getLivestockSummary(User $user, array $filters = []): array
    {
        try {
            if (!$this->permissionChecker->canAccessData($user, 'livestock_data')) {
                return [
                    'error' => 'Insufficient permissions to access livestock data',
                    'suggestion' => 'You can view livestock assigned to your farms in the Livestock section.'
                ];
            }

            if (!$user->company_id) {
                return [
                    'error' => 'No company association found for livestock data',
                    'suggestion' => 'Please contact your administrator.'
                ];
            }

            $query = Livestock::where('company_id', $user->company_id);

            // Apply role-based filtering
            if ($user->hasRole('farm_worker')) {
                // For farm workers, we'll use a more basic approach
                // Since assignedFarms relationship doesn't exist, we'll filter based on company only
                // This is a safer approach that maintains data isolation
                Log::info('Farm worker access - company-scoped livestock data only', [
                    'user_id' => $user->id,
                    'company_id' => $user->company_id
                ]);
            }

            // Apply additional filters if provided
            if (isset($filters['status']) && !empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (isset($filters['type']) && !empty($filters['type'])) {
                $query->where('livestock_type', $filters['type']);
            }

            $totalLivestock = $query->count();
            $activeLivestock = (clone $query)->where('status', 'active')->count();

            $byType = $query->groupBy('livestock_type')
                ->selectRaw('livestock_type, count(*) as count')
                ->pluck('count', 'livestock_type')
                ->toArray();

            $byStatus = $query->groupBy('status')
                ->selectRaw('status, count(*) as count')
                ->pluck('count', 'status')
                ->toArray();

            return [
                'total_livestock' => $totalLivestock,
                'active_livestock' => $activeLivestock,
                'by_type' => $byType,
                'by_status' => $byStatus,
                'user_access_level' => $user->getRoleNames()->first(),
                'company_name' => $user->company->name ?? 'Unknown Company'
            ];

        } catch (Exception $e) {
            Log::error('DataAccessService: Error getting livestock summary', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'filters' => $filters
            ]);

            return [
                'error' => 'Failed to retrieve livestock data',
                'suggestion' => 'Please try again or contact support if the issue persists.'
            ];
        }
    }

    /**
     * Get farm management data based on user permissions
     */
    public function getFarmData(User $user, array $filters = []): array
    {
        try {
            if (!$this->permissionChecker->canAccessData($user, 'farm_data')) {
                return [
                    'error' => 'Insufficient permissions to access farm data',
                    'suggestion' => 'Please contact your manager for farm management access.'
                ];
            }

            if (!$user->company_id) {
                return [
                    'error' => 'No company association found',
                    'suggestion' => 'Please contact your administrator.'
                ];
            }

            $query = Farm::where('company_id', $user->company_id);

            // Apply status filtering based on request
            $statusFilter = $filters['status'] ?? 'active';
            
            if ($statusFilter === 'all') {
                // No status filter - get all farms
                Log::info('Getting all farms regardless of status', [
                    'user_id' => $user->id,
                    'company_id' => $user->company_id
                ]);
            } elseif ($statusFilter === 'inactive') {
                $query->where('status', 'inactive');
                Log::info('Getting inactive farms only', [
                    'user_id' => $user->id,
                    'company_id' => $user->company_id
                ]);
            } else {
                // Default to active farms
                $query->where('status', 'active');
                Log::info('Getting active farms only', [
                    'user_id' => $user->id,
                    'company_id' => $user->company_id
                ]);
            }

            // Apply role-based filtering
            if ($user->hasRole('farm_worker')) {
                // For now, farm workers get company-scoped access
                // TODO: Implement proper farm assignment system
                Log::info('Farm worker accessing farm data - company scoped', [
                    'user_id' => $user->id,
                    'company_id' => $user->company_id
                ]);
            }

            $farms = $query->with(['kandangs'])->get();

            $farmSummary = $farms->map(function ($farm) {
                return [
                    'name' => $farm->name,
                    'location' => $farm->location ?? 'Not specified',
                    'kandang_count' => $farm->kandangs->count(),
                    'total_capacity' => $farm->kandangs->sum('capacity'),
                    'status' => $farm->status ?? 'active'
                ];
            });

            // Get status breakdown for comprehensive reporting
            $statusBreakdown = [
                'active' => $farms->where('status', 'active')->count(),
                'inactive' => $farms->where('status', 'inactive')->count(),
                'total' => $farms->count()
            ];

            return [
                'farms' => $farmSummary->toArray(),
                'total_farms' => $farms->count(),
                'active_farms' => $statusBreakdown['active'],
                'inactive_farms' => $statusBreakdown['inactive'],
                'status_filter_applied' => $statusFilter,
                'status_breakdown' => $statusBreakdown,
                'total_kandangs' => $farms->sum(fn($farm) => $farm->kandangs->count()),
                'total_capacity' => $farms->sum(fn($farm) => $farm->kandangs->sum('capacity'))
            ];

        } catch (Exception $e) {
            Log::error('DataAccessService: Error getting farm data', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'filters' => $filters
            ]);

            return [
                'error' => 'Failed to retrieve farm data',
                'suggestion' => 'Please try again or contact support if the issue persists.'
            ];
        }
    }

    /**
     * Get financial summary based on user permissions
     */
    public function getFinancialSummary(User $user, array $filters = []): array
    {
        try {
            if (!$this->permissionChecker->canAccessData($user, 'financial_data')) {
                return [
                    'error' => 'Insufficient permissions to access financial data',
                    'suggestion' => 'Please contact your manager for financial reports access.'
                ];
            }

            if (!$user->company_id) {
                return [
                    'error' => 'No company association found',
                    'suggestion' => 'Please contact your administrator.'
                ];
            }

            // Get recent purchase data
            $feedPurchases = FeedPurchase::where('company_id', $user->company_id)
                ->where('created_at', '>=', now()->subMonths(3))
                ->sum('total_amount') ?? 0;

            $supplyPurchases = SupplyPurchase::where('company_id', $user->company_id)
                ->where('created_at', '>=', now()->subMonths(3))
                ->sum('total_amount') ?? 0;

            // Get sales data - using LivestockSales and RecordingSale models
            $livestockSales = LivestockSales::where('company_id', $user->company_id)
                ->where('created_at', '>=', now()->subMonths(3))
                ->sum('total_amount') ?? 0;

            $recordingSales = RecordingSale::where('company_id', $user->company_id)
                ->where('created_at', '>=', now()->subMonths(3))
                ->sum('total_amount') ?? 0;

            $recentSales = $livestockSales + $recordingSales;

            $totalExpenses = $feedPurchases + $supplyPurchases;
            $grossProfit = $recentSales - $totalExpenses;

            return [
                'period' => 'Last 3 months',
                'total_sales' => $recentSales,
                'feed_purchases' => $feedPurchases,
                'supply_purchases' => $supplyPurchases,
                'total_expenses' => $totalExpenses,
                'gross_profit' => $grossProfit,
                'profit_margin' => $recentSales > 0 ? ($grossProfit / $recentSales) * 100 : 0
            ];

        } catch (Exception $e) {
            Log::error('DataAccessService: Error getting financial summary', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'filters' => $filters
            ]);

            return [
                'error' => 'Failed to retrieve financial data',
                'suggestion' => 'Please try again or contact support if the issue persists.'
            ];
        }
    }

    /**
     * Get supply/feed inventory data
     */
    public function getSupplyInventory(User $user, array $filters = []): array
    {
        try {
            if (!$this->permissionChecker->canAccessData($user, 'supply_data')) {
                return [
                    'error' => 'Insufficient permissions to access supply data',
                    'suggestion' => 'Please contact your manager for inventory access.'
                ];
            }

            if (!$user->company_id) {
                return [
                    'error' => 'No company association found',
                    'suggestion' => 'Please contact your administrator.'
                ];
            }

            // Get recent supply purchases
            $supplyQuery = SupplyPurchase::where('company_id', $user->company_id)
                ->where('status', 'completed')
                ->orderBy('purchase_date', 'desc')
                ->limit(10);

            $feedQuery = FeedPurchase::where('company_id', $user->company_id)
                ->where('status', 'completed')
                ->orderBy('purchase_date', 'desc')
                ->limit(10);

            $recentSupplies = $supplyQuery->get(['id', 'supplier_name', 'total_amount', 'purchase_date']);
            $recentFeeds = $feedQuery->get(['id', 'supplier_name', 'total_amount', 'purchase_date']);

            return [
                'recent_supplies' => $recentSupplies->toArray(),
                'recent_feeds' => $recentFeeds->toArray(),
                'total_supply_value' => $supplyQuery->sum('total_amount'),
                'total_feed_value' => $feedQuery->sum('total_amount')
            ];

        } catch (Exception $e) {
            Log::error('DataAccessService: Error getting supply inventory', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'filters' => $filters
            ]);

            return [
                'error' => 'Failed to retrieve supply data',
                'suggestion' => 'Please try again or contact support if the issue persists.'
            ];
        }
    }

    /**
     * Search for specific data based on query
     */
    public function searchData(User $user, string $query, array $filters = []): array
    {
        try {
            $query = strtolower(trim($query));
            $results = [];

            // Detect query type and route to appropriate method
            if (strpos($query, 'company') !== false || strpos($query, 'perusahaan') !== false) {
                $results['companies'] = $this->getCompanyList($user);
            }

            if (strpos($query, 'livestock') !== false || strpos($query, 'ternak') !== false || strpos($query, 'ayam') !== false) {
                $results['livestock'] = $this->getLivestockSummary($user, $filters);
            }

            if (strpos($query, 'farm') !== false || strpos($query, 'kandang') !== false) {
                $results['farms'] = $this->getFarmData($user, $filters);
            }

            if (strpos($query, 'financial') !== false || strpos($query, 'keuangan') !== false || strpos($query, 'profit') !== false) {
                $results['financial'] = $this->getFinancialSummary($user, $filters);
            }

            if (strpos($query, 'supply') !== false || strpos($query, 'feed') !== false || strpos($query, 'pakan') !== false) {
                $results['inventory'] = $this->getSupplyInventory($user, $filters);
            }

            return $results;

        } catch (Exception $e) {
            Log::error('DataAccessService: Error in searchData', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'query' => $query
            ]);

            return [
                'error' => 'Failed to search data',
                'suggestion' => 'Please try again with a more specific query.'
            ];
        }
    }

    /**
     * Format data for AI consumption
     */
    public function formatDataForAI(array $data): string
    {
        try {
            $formattedParts = [];

            foreach ($data as $dataType => $content) {
                if (isset($content['error'])) {
                    $formattedParts[] = "🚫 {$dataType}: {$content['error']}";
                    if (isset($content['suggestion'])) {
                        $formattedParts[] = "💡 Suggestion: {$content['suggestion']}";
                    }
                    continue;
                }

                switch ($dataType) {
                    case 'companies':
                        $formattedParts[] = $this->formatCompanyData($content);
                        break;
                    case 'livestock':
                        $formattedParts[] = $this->formatLivestockData($content);
                        break;
                    case 'farms':
                        $formattedParts[] = $this->formatFarmData($content);
                        break;
                    case 'financial':
                        $formattedParts[] = $this->formatFinancialData($content);
                        break;
                    case 'inventory':
                        $formattedParts[] = $this->formatInventoryData($content);
                        break;
                }
            }

            return implode("\n\n", $formattedParts);

        } catch (Exception $e) {
            Log::error('DataAccessService: Error formatting data for AI', [
                'error' => $e->getMessage(),
                'data_keys' => array_keys($data)
            ]);

            return "Maaf, terjadi kendala saat memproses data. Silakan coba lagi.";
        }
    }

    /**
     * Format company data for AI
     */
    private function formatCompanyData(array $data): string
    {
        $formatted = "";

        if (!empty($data['companies'])) {
            if (count($data['companies']) == 1) {
                $company = $data['companies'][0];
                $formatted .= "Perusahaan: {$company['name']}";
                if (isset($company['type']) && $company['type']) {
                    $formatted .= " ({$company['type']})";
                }
                if (isset($company['registered_date'])) {
                    $formatted .= " - Aktif sejak " . date('d M Y', strtotime($company['registered_date']));
                }
                $formatted .= "\n";
            } else {
                $formatted .= "Perusahaan yang tersedia:\n";
                foreach ($data['companies'] as $company) {
                    $formatted .= "• {$company['name']}";
                    if (isset($company['type']) && $company['type']) {
                        $formatted .= " ({$company['type']})";
                    }
                    if (isset($company['registered_date'])) {
                        $formatted .= " - Aktif sejak " . date('d M Y', strtotime($company['registered_date']));
                    }
                    $formatted .= "\n";
                }
            }
        } else {
            $formatted = "Belum ada data perusahaan yang tersedia.\n";
        }

        return $formatted;
    }

    /**
     * Format livestock data for AI
     */
    private function formatLivestockData(array $data): string
    {
        $formatted = "Ringkasan ternak di {$data['company_name']}:\n";
        $formatted .= "Total ternak: {$data['total_livestock']} ekor\n";
        $formatted .= "Ternak aktif: {$data['active_livestock']} ekor\n\n";

        if (!empty($data['by_type'])) {
            $formatted .= "By Type:\n";
            foreach ($data['by_type'] as $type => $count) {
                $formatted .= "• {$type}: {$count}\n";
            }
        }

        if (!empty($data['by_status'])) {
            $formatted .= "\nBy Status:\n";
            foreach ($data['by_status'] as $status => $count) {
                $formatted .= "• {$status}: {$count}\n";
            }
        }

        return $formatted;
    }

    /**
     * Format farm data for AI
     */
    private function formatFarmData(array $data): string
    {
        if ($data['total_farms'] == 0) {
            $formatted = "Belum ada farm yang terdaftar.\n";
        } else {
            $formatted = "";
            if (!empty($data['farms'])) {
                if (count($data['farms']) == 1) {
                    $farm = $data['farms'][0];
                    $formatted .= "Farm: {$farm['name']}";
                    if ($farm['kandang_count'] > 0) {
                        $formatted .= " - {$farm['kandang_count']} kandang";
                        if ($farm['total_capacity'] > 0) {
                            $formatted .= " (kapasitas {$farm['total_capacity']} ekor)";
                        }
                    }
                    $formatted .= "\n";
                } else {
                    $formatted .= "Farms yang tersedia:\n";
                    foreach ($data['farms'] as $farm) {
                        $formatted .= "• {$farm['name']}";
                        if ($farm['kandang_count'] > 0) {
                            $formatted .= " - {$farm['kandang_count']} kandang";
                            if ($farm['total_capacity'] > 0) {
                                $formatted .= " (kapasitas {$farm['total_capacity']} ekor)";
                            }
                        }
                        $formatted .= "\n";
                    }
                }
            }
        }

        return $formatted;
    }

    /**
     * Format financial data for AI
     */
    private function formatFinancialData(array $data): string
    {
        $formatted = "Ringkasan keuangan ({$data['period']}): \n";
        $formatted .= "Penjualan: Rp " . number_format($data['total_sales']) . "\n";
        $formatted .= "Pembelian pakan: Rp " . number_format($data['feed_purchases']) . "\n";
        $formatted .= "Pembelian supplies: Rp " . number_format($data['supply_purchases']) . "\n";
        $formatted .= "Total pengeluaran: Rp " . number_format($data['total_expenses']) . "\n";
        $formatted .= "Keuntungan kotor: Rp " . number_format($data['gross_profit']) . "\n";
        $formatted .= "Margin keuntungan: " . number_format($data['profit_margin'], 2) . "%\n";

        return $formatted;
    }

    /**
     * Format inventory data for AI
     */
    private function formatInventoryData(array $data): string
    {
        $formatted = "Ringkasan inventori:\n";
        $formatted .= "Nilai total supplies: Rp " . number_format($data['total_supply_value']) . "\n";
        $formatted .= "Nilai total pakan: Rp " . number_format($data['total_feed_value']) . "\n\n";

        if (!empty($data['recent_supplies'])) {
            $formatted .= "Recent Supplies:\n";
            foreach (array_slice($data['recent_supplies'], 0, 5) as $supply) {
                $formatted .= "• {$supply['supplier_name']} - Rp " . number_format($supply['total_amount']) . " ({$supply['purchase_date']})\n";
            }
        }

        return $formatted;
    }
}

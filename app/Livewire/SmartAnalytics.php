<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\AnalyticsService;
use App\Models\Farm;
use App\Models\Coop;
use App\Models\Livestock;
use App\Models\AnalyticsAlert;
use Carbon\Carbon;
use Livewire\Attributes\On;

class SmartAnalytics extends Component
{
    public $activeTab = 'overview';
    public $farmId = null;
    public $coopId = null;
    public $livestockId = null;
    public $dateFrom;
    public $dateTo;

    // New properties for chart customization
    public $chartType = 'auto';
    public $viewType = 'livestock'; // 'livestock' or 'daily' for single coop

    public $insights = [];
    public $farms = [];
    public $coops = [];
    public $livestocks = [];
    public $isLoading = false;

    protected AnalyticsService $analyticsService;

    public function boot(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public function mount()
    {
        logger()->info('[Analytics Debug] Starting component mount');

        try {
            logger()->info('[Analytics Debug] Loading farms, coops and livestock data');
            $this->farms = Farm::active()->get();
            $this->coops = collect();
            $this->livestocks = collect();

            // Set default dates to match actual data availability (May-June 2025)
            $this->dateFrom = '2025-05-10';  // Updated to match actual data range
            $this->dateTo = '2025-06-09';    // Updated to match actual data range

            logger()->info('[Analytics Debug] Default date range set to actual data', [
                'date_from' => $this->dateFrom,
                'date_to' => $this->dateTo,
                'note' => 'Using actual data range instead of relative dates'
            ]);

            $this->loadData();

            logger()->info('[Analytics Debug] Initializing insights with safe defaults');
            // Initialize insights with safe defaults first
            $this->insights = [
                'overview' => [
                    'total_livestock' => 0,
                    'avg_mortality_rate' => 0,
                    'avg_efficiency_score' => 0,
                    'avg_fcr' => 0,
                    'total_revenue' => 0,
                    'problematic_coops' => 0,
                    'high_performers' => 0,
                ],
                'mortality_analysis' => collect(),
                'sales_analysis' => collect(),
                'production_analysis' => collect(),
                'coop_rankings' => collect(),
                'alerts' => collect(),
                'trends' => [
                    'mortality_trend' => [],
                    'efficiency_trend' => [],
                    'fcr_trend' => [],
                    'revenue_trend' => [],
                ]
            ];

            logger()->info('[Analytics Debug] Calling refreshAnalytics to load real data');
            // Then try to load real data
            $this->refreshAnalytics();

            logger()->info('[Analytics Debug] SmartAnalytics component mounted successfully');
        } catch (\Exception $e) {
            logger()->error('[Analytics Debug] Failed to mount SmartAnalytics', [
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString()
            ]);

            // Ensure we have safe defaults even on error
            $this->insights = [
                'overview' => [
                    'total_livestock' => 0,
                    'avg_mortality_rate' => 0,
                    'avg_efficiency_score' => 0,
                    'avg_fcr' => 0,
                    'total_revenue' => 0,
                    'problematic_coops' => 0,
                    'high_performers' => 0,
                ],
                'mortality_analysis' => collect(),
                'sales_analysis' => collect(),
                'production_analysis' => collect(),
                'coop_rankings' => collect(),
                'alerts' => collect(),
                'trends' => [
                    'mortality_trend' => [],
                    'efficiency_trend' => [],
                    'fcr_trend' => [],
                    'revenue_trend' => [],
                ]
            ];

            $this->isLoading = false;

            logger()->info('[Analytics Debug] Set safe defaults due to mount error');
        }
    }

    public function loadData()
    {
        $this->farms = Farm::active()->get();
        $this->coops = $this->farmId
            ? Coop::where('farm_id', $this->farmId)->get()
            : Coop::all();

        // Load livestock based on selected farm and coop
        $livestockQuery = Livestock::with(['farm', 'coop']);

        if ($this->farmId) {
            $livestockQuery->where('farm_id', $this->farmId);
        }

        if ($this->coopId) {
            $livestockQuery->where('coop_id', $this->coopId);
        }

        $this->livestocks = $livestockQuery->orderBy('name')->get();
    }

    #[On('farm-changed')]
    public function updatedFarmId()
    {
        logger()->info('[Analytics Debug] Farm filter changed', [
            'farm_id' => $this->farmId ?? 'none'
        ]);

        try {
            logger()->info('[Analytics Debug] Resetting coop and livestock filters and loading new data');
            $this->coopId = null;
            $this->livestockId = null;

            $this->coops = $this->farmId
                ? Coop::where('farm_id', $this->farmId)->get()
                : Coop::all();

            // Load livestock for the selected farm
            $this->livestocks = $this->farmId
                ? Livestock::with(['farm', 'coop'])->where('farm_id', $this->farmId)->orderBy('name')->get()
                : Livestock::with(['farm', 'coop'])->orderBy('name')->get();

            logger()->info('[Analytics Debug] Loaded coops and livestock for farm', [
                'farm_id' => $this->farmId,
                'coops_count' => $this->coops->count(),
                'livestock_count' => $this->livestocks->count()
            ]);

            logger()->info('[Analytics Debug] Calling refreshAnalytics due to FILTER CHANGE');
            $this->refreshAnalytics();

            // Refresh chart data if on mortality tab
            if ($this->activeTab === 'mortality') {
                logger()->info('[Analytics Debug] Refreshing mortality chart data for farm filter change');
                $this->refreshMortalityChartData();
            }
        } catch (\Exception $e) {
            logger()->error('[Analytics Debug] Failed to update farm filter', [
                'farm_id' => $this->farmId,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString()
            ]);

            // Reset to safe state
            $this->coops = collect();
            $this->livestocks = collect();
            $this->isLoading = false;

            logger()->info('[Analytics Debug] Dispatching analytics-error from updatedFarmId');
            $this->dispatch('analytics-error', [
                'message' => 'Failed to load farm data',
                'type' => 'error'
            ]);
        }
    }

    // Handle coop filter change
    public function updatedCoopId()
    {
        logger()->info('[Analytics Debug] Coop filter changed', [
            'coop_id' => $this->coopId ?? 'none'
        ]);

        try {
            logger()->info('[Analytics Debug] Resetting livestock filter and loading livestock for coop');
            $this->livestockId = null;

            // Load livestock for the selected coop
            $livestockQuery = Livestock::with(['farm', 'coop']);

            if ($this->farmId) {
                $livestockQuery->where('farm_id', $this->farmId);
            }

            if ($this->coopId) {
                $livestockQuery->where('coop_id', $this->coopId);
            }

            $this->livestocks = $livestockQuery->orderBy('name')->get();

            logger()->info('[Analytics Debug] Loaded livestock for coop', [
                'coop_id' => $this->coopId,
                'livestock_count' => $this->livestocks->count()
            ]);

            logger()->info('[Analytics Debug] Calling refreshAnalytics due to COOP FILTER CHANGE');
            $this->refreshAnalytics();

            // Refresh chart data if on mortality tab
            if ($this->activeTab === 'mortality') {
                logger()->info('[Analytics Debug] Refreshing mortality chart data for coop filter change');
                $this->refreshMortalityChartData();
            }
        } catch (\Exception $e) {
            logger()->error('[Analytics Debug] Failed to update coop filter', [
                'coop_id' => $this->coopId,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString()
            ]);

            // Reset to safe state
            $this->livestocks = collect();
            $this->isLoading = false;

            logger()->info('[Analytics Debug] Dispatching analytics-error from updatedCoopId');
            $this->dispatch('analytics-error', [
                'message' => 'Failed to load coop data',
                'type' => 'error'
            ]);
        }
    }

    // Handle livestock filter change
    public function updatedLivestockId()
    {
        logger()->info('[Analytics Debug] Livestock filter changed - COMPREHENSIVE HANDLING', [
            'livestock_id' => $this->livestockId ?? 'none',
            'farm_id' => $this->farmId ?? 'none',
            'coop_id' => $this->coopId ?? 'none',
            'timestamp' => now()->toDateTimeString()
        ]);

        try {
            // Validate livestock exists if provided
            if ($this->livestockId) {
                $livestock = Livestock::find($this->livestockId);
                if (!$livestock) {
                    logger()->warning('[Analytics Debug] Selected livestock not found, resetting', [
                        'livestock_id' => $this->livestockId
                    ]);
                    $this->livestockId = null;
                    return;
                }

                logger()->info('[Analytics Debug] Livestock validated successfully', [
                    'livestock_id' => $this->livestockId,
                    'livestock_name' => $livestock->name,
                    'farm_id' => $livestock->farm_id,
                    'coop_id' => $livestock->coop_id
                ]);

                // Auto-set farm and coop if not already set
                if (!$this->farmId && $livestock->farm_id) {
                    $this->farmId = $livestock->farm_id;
                    logger()->info('[Analytics Debug] Auto-set farm from livestock', [
                        'farm_id' => $this->farmId
                    ]);
                }

                if (!$this->coopId && $livestock->coop_id) {
                    $this->coopId = $livestock->coop_id;
                    logger()->info('[Analytics Debug] Auto-set coop from livestock', [
                        'coop_id' => $this->coopId
                    ]);
                }

                // Reload related data
                $this->loadData();
            }

            logger()->info('[Analytics Debug] Calling refreshAnalytics due to LIVESTOCK FILTER CHANGE');
            $this->refreshAnalytics();

            // CRITICAL: Ensure chart gets updated for mortality tab
            if ($this->activeTab === 'mortality') {
                logger()->info('[Analytics Debug] Dispatching mortality-chart-updated for livestock change');
                $this->dispatch('mortality-chart-updated');

                // Also refresh chart data to get fresh data with new filters
                $this->refreshMortalityChartData();
            }

            // Also dispatch general data refresh event
            $this->dispatch('data-refreshed', [
                'trigger' => 'livestock_filter_change',
                'livestock_id' => $this->livestockId,
                'active_tab' => $this->activeTab
            ]);

            logger()->info('[Analytics Debug] Livestock filter change handled successfully', [
                'livestock_id' => $this->livestockId,
                'final_farm_id' => $this->farmId,
                'final_coop_id' => $this->coopId,
                'active_tab' => $this->activeTab
            ]);
        } catch (\Exception $e) {
            logger()->error('[Analytics Debug] Failed to handle livestock filter change', [
                'livestock_id' => $this->livestockId,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString()
            ]);

            // Reset to safe state
            $this->livestockId = null;
            $this->isLoading = false;

            $this->dispatch('analytics-error', [
                'message' => 'Failed to load livestock data: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    // Handle date range changes
    public function updatedDateFrom()
    {
        logger()->info('[Analytics Debug] Date From filter changed', [
            'date_from' => $this->dateFrom
        ]);

        logger()->info('[Analytics Debug] Calling refreshAnalytics due to DATE FROM CHANGE');
        $this->refreshAnalytics();
    }

    public function updatedDateTo()
    {
        logger()->info('[Analytics Debug] Date To filter changed', [
            'date_to' => $this->dateTo
        ]);

        logger()->info('[Analytics Debug] Calling refreshAnalytics due to DATE TO CHANGE');
        $this->refreshAnalytics();
    }

    // Handle chart type changes
    public function updatedChartType()
    {
        logger()->info('[Analytics Debug] Chart type changed - ENHANCED HANDLING', [
            'chart_type' => $this->chartType,
            'active_tab' => $this->activeTab,
            'has_livestock_filter' => !empty($this->livestockId),
            'livestock_id' => $this->livestockId ?? 'none'
        ]);

        // For chart type changes, we need to refresh chart data to ensure proper display
        if ($this->activeTab === 'mortality') {
            logger()->info('[Analytics Debug] Refreshing mortality chart data for type change');
            $this->dispatch('mortality-chart-updated', [
                'trigger' => 'chart_type_change',
                'chart_type' => $this->chartType,
                'force_refresh' => true
            ]);
        }
    }

    // Handle view type changes
    public function updatedViewType()
    {
        logger()->info('[Analytics Debug] View type changed - ENHANCED HANDLING', [
            'view_type' => $this->viewType,
            'active_tab' => $this->activeTab,
            'has_livestock_filter' => !empty($this->livestockId),
            'livestock_id' => $this->livestockId ?? 'none'
        ]);

        // For view type changes, we need to completely refresh the data
        if ($this->activeTab === 'mortality') {
            logger()->info('[Analytics Debug] Refreshing analytics data for view type change');

            // View type changes might need different data structure, so refresh analytics
            $this->refreshAnalytics();

            // Then update the chart
            $this->dispatch('mortality-chart-updated', [
                'trigger' => 'view_type_change',
                'view_type' => $this->viewType,
                'force_refresh' => true
            ]);
        }
    }

    public function refreshAnalytics()
    {
        $startTime = microtime(true);
        logger()->info('[Analytics Debug] Starting refreshAnalytics', [
            'farm_id' => $this->farmId,
            'coop_id' => $this->coopId,
            'livestock_id' => $this->livestockId,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'chart_type' => $this->chartType,
            'view_type' => $this->viewType,
            'timestamp' => now()->toDateTimeString()
        ]);

        $this->isLoading = true;

        try {
            logger()->info('[Analytics Debug] Building filters for analytics service');

            $filters = [
                'farm_id' => $this->farmId,
                'coop_id' => $this->coopId,
                'livestock_id' => $this->livestockId,
                'date_from' => $this->dateFrom,
                'date_to' => $this->dateTo,
            ];

            logger()->info('[Analytics Debug] Calling AnalyticsService->getSmartInsights', [
                'filters' => $filters
            ]);

            $insights = $this->analyticsService->getSmartInsights($filters);

            logger()->info('[Analytics Debug] Received insights from service', [
                'insights_type' => gettype($insights),
                'insights_keys' => is_array($insights) ? array_keys($insights) : 'not-array',
                'data_size' => is_array($insights) ? count($insights) : 'unknown'
            ]);

            // Ensure insights is always an array with proper structure
            $this->insights = is_array($insights) ? $insights : [];

            // Ensure all required keys exist with proper defaults
            $this->insights = array_merge([
                'overview' => [
                    'total_livestock' => 0,
                    'avg_mortality_rate' => 0,
                    'avg_efficiency_score' => 0,
                    'avg_fcr' => 0,
                    'total_revenue' => 0,
                    'problematic_coops' => 0,
                    'high_performers' => 0,
                ],
                'mortality_analysis' => collect(),
                'sales_analysis' => collect(),
                'production_analysis' => collect(),
                'coop_rankings' => collect(),
                'alerts' => collect(),
                'trends' => [
                    'mortality_trend' => [],
                    'efficiency_trend' => [],
                    'fcr_trend' => [],
                    'revenue_trend' => [],
                ]
            ], $this->insights);

            logger()->info('[Analytics Debug] Dispatching analytics-updated event');
            $this->dispatch('analytics-updated', $this->insights);

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            logger()->info('[Analytics Debug] Analytics refreshed successfully', [
                'execution_time_ms' => $executionTime,
                'insights_keys' => is_array($this->insights) ? array_keys($this->insights) : 'not-array',
                'overview_data' => $this->insights['overview'] ?? 'N/A',
                'mortality_count' => isset($this->insights['mortality_analysis'])
                    ? (is_countable($this->insights['mortality_analysis']) ? count($this->insights['mortality_analysis']) : 0)
                    : 0,
                'filters_used' => $filters
            ]);
        } catch (\Exception $e) {
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            logger()->error('[Analytics Debug] Failed to refresh analytics', [
                'execution_time_ms' => $executionTime,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'filters' => $filters ?? []
            ]);

            // Set safe defaults on error
            $this->insights = [
                'overview' => [
                    'total_livestock' => 0,
                    'avg_mortality_rate' => 0,
                    'avg_efficiency_score' => 0,
                    'avg_fcr' => 0,
                    'total_revenue' => 0,
                    'problematic_coops' => 0,
                    'high_performers' => 0,
                ],
                'mortality_analysis' => collect(),
                'sales_analysis' => collect(),
                'production_analysis' => collect(),
                'coop_rankings' => collect(),
                'alerts' => collect(),
                'trends' => [
                    'mortality_trend' => [],
                    'efficiency_trend' => [],
                    'fcr_trend' => [],
                    'revenue_trend' => [],
                ]
            ];

            logger()->info('[Analytics Debug] Dispatching analytics-error event');
            $this->dispatch('analytics-error', [
                'message' => 'Failed to load analytics data: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        } finally {
            logger()->info('[Analytics Debug] Setting isLoading to false');
            $this->isLoading = false;
        }
    }

    public function setActiveTab($tab)
    {
        logger()->info('[Analytics Debug] Tab change requested - ENSURING DATA AVAILABILITY', [
            'from_tab' => $this->activeTab,
            'to_tab' => $tab,
            'timestamp' => now()->toDateTimeString()
        ]);

        // ZERO LOADING POLICY for tab changes - force clear immediately
        $this->isLoading = false;

        // Change tab immediately without any loading state
        $this->activeTab = $tab;

        // Ensure all data is available for the new tab
        $this->ensureDataForTab($tab);

        // Dispatch immediate clear event to frontend
        $this->dispatch('tab-changed', [
            'activeTab' => $tab,
            'clearLoading' => true,
            'forceClear' => true,
            'preventLoading' => true,
            'dataReady' => true
        ]);

        // Additional dispatch to ensure overlay is cleared
        $this->dispatch('force-clear-loading');

        logger()->info('[Analytics Debug] Tab changed - DATA ENSURED', [
            'active_tab' => $this->activeTab,
            'loading_state' => $this->isLoading,
            'policy' => 'ZERO_LOADING_WITH_DATA_READY'
        ]);
    }

    /**
     * Ensure data is available for specific tab
     */
    private function ensureDataForTab($tab)
    {
        logger()->info('[Analytics Debug] Ensuring data availability for tab: ' . $tab);

        // Check if insights data is properly loaded
        if (empty($this->insights) || !is_array($this->insights)) {
            logger()->info('[Analytics Debug] Insights empty, calling refreshAnalytics');
            $this->refreshAnalytics();
        } else {
            logger()->info('[Analytics Debug] Insights available, checking specific tab data');

            // Check specific tab data availability
            switch ($tab) {
                case 'mortality':
                    if (
                        !isset($this->insights['mortality_analysis']) ||
                        (is_countable($this->insights['mortality_analysis']) && count($this->insights['mortality_analysis']) === 0)
                    ) {
                        logger()->info('[Analytics Debug] Mortality data missing, refreshing');
                        $this->refreshAnalytics();
                    }
                    break;
                case 'sales':
                    if (!isset($this->insights['sales_analysis'])) {
                        logger()->info('[Analytics Debug] Sales data missing, refreshing');
                        $this->refreshAnalytics();
                    }
                    break;
                case 'production':
                    if (
                        !isset($this->insights['production_analysis']) ||
                        (is_countable($this->insights['production_analysis']) && count($this->insights['production_analysis']) === 0)
                    ) {
                        logger()->info('[Analytics Debug] Production data missing, refreshing');
                        $this->refreshAnalytics();
                    }
                    break;
                case 'rankings':
                    // if (!isset($this->insights['coop_rankings'])) {
                    if (
                        !isset($this->insights['coop_rankings']) ||
                        (is_countable($this->insights['coop_rankings']) && count($this->insights['coop_rankings']) === 0)
                    ) {
                        logger()->info('[Analytics Debug] Rankings data missing, refreshing');
                        $this->refreshAnalytics();
                    }
                    break;
                case 'alerts':
                    if (!isset($this->insights['alerts'])) {
                        logger()->info('[Analytics Debug] Alerts data missing, refreshing');
                        $this->refreshAnalytics();
                    }
                    break;
            }
        }

        logger()->info('[Analytics Debug] Data ensured for tab: ' . $tab);
    }

    public function resolveAlert($alertId)
    {
        try {
            $alert = AnalyticsAlert::findOrFail($alertId);
            $alert->resolve();

            $this->refreshAnalytics();

            $this->dispatch('alert-resolved', [
                'message' => 'Alert resolved successfully',
                'type' => 'success'
            ]);

            logger()->info("Alert resolved: {$alertId}");
        } catch (\Exception $e) {
            $this->dispatch('alert-resolved', [
                'message' => 'Failed to resolve alert: ' . $e->getMessage(),
                'type' => 'error'
            ]);

            logger()->error("Failed to resolve alert {$alertId}: " . $e->getMessage());
        }
    }

    public function calculateDailyAnalytics()
    {
        try {
            $this->isLoading = true;
            $this->analyticsService->calculateDailyAnalytics();
            $this->refreshAnalytics();

            $this->dispatch('calculation-complete', [
                'message' => 'Daily analytics calculated successfully',
                'type' => 'success'
            ]);

            logger()->info('Daily analytics calculation completed manually');
        } catch (\Exception $e) {
            $this->dispatch('calculation-complete', [
                'message' => 'Failed to calculate analytics: ' . $e->getMessage(),
                'type' => 'error'
            ]);

            logger()->error('Failed to calculate daily analytics: ' . $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    public function getOverviewData()
    {
        return $this->insights['overview'] ?? [
            'total_livestock' => 0,
            'avg_mortality_rate' => 0,
            'avg_efficiency_score' => 0,
            'avg_fcr' => 0,
            'total_revenue' => 0,
            'problematic_coops' => 0,
            'high_performers' => 0,
        ];
    }

    public function getMortalityAnalysis()
    {
        return $this->insights['mortality_analysis'] ?? collect();
    }

    public function getSalesAnalysis()
    {
        return $this->insights['sales_analysis'] ?? collect();
    }

    public function getProductionAnalysis()
    {
        return $this->insights['production_analysis'] ?? collect();
    }

    public function getCoopRankings()
    {
        return $this->insights['coop_rankings'] ?? collect();
    }

    public function getActiveAlerts()
    {
        return $this->insights['alerts'] ?? collect();
    }

    public function getTrendData()
    {
        return $this->insights['trends'] ?? [
            'mortality_trend' => [],
            'efficiency_trend' => [],
            'fcr_trend' => [],
            'revenue_trend' => [],
        ];
    }

    public function getMortalityChartData(): array
    {
        try {
            logger()->info('[Mortality Chart] Starting getMortalityChartData - ENHANCED FOR SINGLE LIVESTOCK', [
                'farm_id' => $this->farmId,
                'coop_id' => $this->coopId,
                'livestock_id' => $this->livestockId,
                'date_from' => $this->dateFrom,
                'date_to' => $this->dateTo,
                'chart_type' => $this->chartType,
                'view_type' => $this->viewType,
                'is_single_livestock' => !empty($this->livestockId),
                'timestamp' => now()->toDateTimeString()
            ]);

            // Validate livestock if provided
            if ($this->livestockId) {
                $livestock = Livestock::find($this->livestockId);
                if (!$livestock) {
                    logger()->warning('[Mortality Chart] Livestock not found, returning empty chart', [
                        'livestock_id' => $this->livestockId
                    ]);

                    return [
                        'type' => 'line',
                        'title' => 'Livestock Not Found',
                        'labels' => [],
                        'datasets' => [],
                        'options' => [
                            'responsive' => true,
                            'plugins' => [
                                'title' => [
                                    'display' => true,
                                    'text' => 'Selected livestock batch not found'
                                ]
                            ]
                        ]
                    ];
                }

                logger()->info('[Mortality Chart] Livestock validated for chart', [
                    'livestock_id' => $this->livestockId,
                    'livestock_name' => $livestock->name,
                    'farm_name' => $livestock->farm->name ?? 'Unknown',
                    'coop_name' => $livestock->coop->name ?? 'Unknown'
                ]);
            }

            $filters = [
                'farm_id' => $this->farmId,
                'coop_id' => $this->coopId,
                'livestock_id' => $this->livestockId,
                'date_from' => $this->dateFrom,
                'date_to' => $this->dateTo,
                'chart_type' => $this->chartType,
                'view_type' => $this->viewType,
            ];

            logger()->info('[Mortality Chart] Calling analyticsService with enhanced filters', [
                'filters' => $filters,
                'service_method' => 'getMortalityChartData'
            ]);

            $chartData = $this->analyticsService->getMortalityChartData($filters);

            logger()->info('[Mortality Chart] Chart data received from service - DETAILED ANALYSIS', [
                'type' => $chartData['type'] ?? 'unknown',
                'title' => $chartData['title'] ?? 'unknown',
                'labels_count' => count($chartData['labels'] ?? []),
                'datasets_count' => count($chartData['datasets'] ?? []),
                'has_options' => isset($chartData['options']),
                'first_5_labels' => array_slice($chartData['labels'] ?? [], 0, 5),
                'total_data_points' => array_sum(array_map(function ($dataset) {
                    return count($dataset['data'] ?? []);
                }, $chartData['datasets'] ?? []))
            ]);

            // Enhanced dataset logging for single livestock
            if (isset($chartData['datasets']) && $this->livestockId) {
                foreach ($chartData['datasets'] as $index => $dataset) {
                    $dataSum = array_sum($dataset['data'] ?? []);
                    $nonZeroCount = count(array_filter($dataset['data'] ?? [], function ($value) {
                        return $value > 0;
                    }));

                    logger()->info("[Mortality Chart] Single Livestock Dataset $index Analysis", [
                        'livestock_id' => $this->livestockId,
                        'dataset_label' => $dataset['label'] ?? 'unknown',
                        'data_count' => count($dataset['data'] ?? []),
                        'total_deaths' => $dataSum,
                        'days_with_deaths' => $nonZeroCount,
                        'avg_daily_deaths' => $nonZeroCount > 0 ? round($dataSum / $nonZeroCount, 2) : 0,
                        'max_daily_deaths' => !empty($dataset['data']) ? max($dataset['data']) : 0,
                        'has_background_color' => isset($dataset['backgroundColor']),
                        'has_border_color' => isset($dataset['borderColor'])
                    ]);
                }
            }

            // Validate chart data structure
            if (empty($chartData['labels']) || empty($chartData['datasets'])) {
                logger()->warning('[Mortality Chart] Empty chart data received', [
                    'livestock_id' => $this->livestockId,
                    'has_labels' => !empty($chartData['labels']),
                    'has_datasets' => !empty($chartData['datasets']),
                    'filters_used' => $filters
                ]);

                // Return informative empty chart
                return [
                    'type' => $this->chartType === 'auto' ? 'line' : $this->chartType,
                    'title' => $this->livestockId
                        ? 'No Mortality Data for Selected Livestock'
                        : 'No Mortality Data Available',
                    'labels' => [],
                    'datasets' => [],
                    'options' => [
                        'responsive' => true,
                        'plugins' => [
                            'title' => [
                                'display' => true,
                                'text' => 'No mortality data found for the selected filters'
                            ]
                        ]
                    ]
                ];
            }

            logger()->info('[Mortality Chart] Chart data validated and ready for frontend', [
                'livestock_id' => $this->livestockId,
                'chart_ready' => true,
                'data_quality' => 'good'
            ]);

            return $chartData;
        } catch (\Exception $e) {
            logger()->error('[Mortality Chart] Failed to get mortality chart data in component - ENHANCED ERROR', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'livestock_id' => $this->livestockId,
                'filters' => [
                    'farm_id' => $this->farmId,
                    'coop_id' => $this->coopId,
                    'livestock_id' => $this->livestockId,
                    'date_from' => $this->dateFrom,
                    'date_to' => $this->dateTo,
                    'chart_type' => $this->chartType,
                    'view_type' => $this->viewType
                ]
            ]);

            return [
                'type' => 'line',
                'title' => 'Chart Error',
                'labels' => [],
                'datasets' => [],
                'options' => [
                    'responsive' => true,
                    'plugins' => [
                        'title' => [
                            'display' => true,
                            'text' => 'Unable to load mortality chart data: ' . $e->getMessage()
                        ]
                    ]
                ]
            ];
        }
    }

    public function getChartData($type)
    {
        $trends = $this->getTrendData();

        // Ensure trends is an array
        if (!is_array($trends)) {
            $trends = [
                'mortality_trend' => [],
                'efficiency_trend' => [],
                'fcr_trend' => [],
                'revenue_trend' => [],
            ];
        }

        switch ($type) {
            case 'mortality':
                // Use new mortality chart data method
                return $this->getMortalityChartData();

            case 'efficiency':
                $efficiencyTrend = $trends['efficiency_trend'] ?? [];
                // Convert Collection to array if needed
                if (is_object($efficiencyTrend) && method_exists($efficiencyTrend, 'toArray')) {
                    $efficiencyTrend = $efficiencyTrend->toArray();
                }
                $efficiencyTrend = is_array($efficiencyTrend) ? $efficiencyTrend : [];

                return [
                    'labels' => array_keys($efficiencyTrend),
                    'data' => array_values($efficiencyTrend),
                    'label' => 'Efficiency Score',
                    'color' => 'rgb(34, 197, 94)',
                ];

            case 'fcr':
                $fcrTrend = $trends['fcr_trend'] ?? [];
                // Convert Collection to array if needed
                if (is_object($fcrTrend) && method_exists($fcrTrend, 'toArray')) {
                    $fcrTrend = $fcrTrend->toArray();
                }
                $fcrTrend = is_array($fcrTrend) ? $fcrTrend : [];

                return [
                    'labels' => array_keys($fcrTrend),
                    'data' => array_values($fcrTrend),
                    'label' => 'Feed Conversion Ratio',
                    'color' => 'rgb(59, 130, 246)',
                ];

            case 'revenue':
                $revenueTrend = $trends['revenue_trend'] ?? [];
                // Convert Collection to array if needed
                if (is_object($revenueTrend) && method_exists($revenueTrend, 'toArray')) {
                    $revenueTrend = $revenueTrend->toArray();
                }
                $revenueTrend = is_array($revenueTrend) ? $revenueTrend : [];

                return [
                    'labels' => array_keys($revenueTrend),
                    'data' => array_values($revenueTrend),
                    'label' => 'Daily Revenue',
                    'color' => 'rgb(168, 85, 247)',
                ];

            default:
                return [
                    'labels' => [],
                    'data' => [],
                    'label' => '',
                    'color' => 'rgb(107, 114, 128)',
                ];
        }
    }

    public function getPerformanceColor($score)
    {
        if ($score >= 80) return 'text-green-600';
        if ($score >= 60) return 'text-yellow-600';
        return 'text-red-600';
    }

    public function getPerformanceBadge($score)
    {
        if ($score >= 80) return 'bg-green-100 text-green-800';
        if ($score >= 60) return 'bg-yellow-100 text-yellow-800';
        return 'bg-red-100 text-red-800';
    }

    public function render()
    {
        // Log render untuk monitoring
        logger()->debug('SmartAnalytics rendering', [
            'active_tab' => $this->activeTab,
            'farm_id' => $this->farmId,
            'date_range' => $this->dateFrom . ' to ' . $this->dateTo
        ]);

        return view('livewire.smart-analytics');
    }

    /**
     * Get fresh mortality chart data via AJAX call
     * This method is called from frontend to get updated data when filters change
     */
    public function refreshMortalityChartData()
    {
        logger()->info('[Analytics Debug] AJAX request for fresh mortality chart data', [
            'farm_id' => $this->farmId,
            'coop_id' => $this->coopId,
            'livestock_id' => $this->livestockId,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'chart_type' => $this->chartType,
            'view_type' => $this->viewType,
            'timestamp' => now()->toDateTimeString()
        ]);

        // dd($this->all());

        try {
            // Get fresh chart data with current filters
            $chartData = $this->getMortalityChartData();

            logger()->info('[Analytics Debug] Fresh chart data obtained via AJAX', [
                'labels_count' => count($chartData['labels'] ?? []),
                'datasets_count' => count($chartData['datasets'] ?? []),
                'chart_type' => $chartData['type'] ?? 'unknown',
                'title' => $chartData['title'] ?? 'unknown'
            ]);

            // dd($chartData);

            // Dispatch fresh data to frontend
            $this->dispatch('mortality-chart-data-refreshed', $chartData);

            return $chartData;
        } catch (\Exception $e) {
            logger()->error('[Analytics Debug] Failed to refresh mortality chart data via AJAX', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $errorChart = [
                'type' => 'line',
                'title' => 'Chart Data Error',
                'labels' => [],
                'datasets' => [],
                'options' => [
                    'responsive' => true,
                    'plugins' => [
                        'title' => [
                            'display' => true,
                            'text' => 'Unable to refresh chart data: ' . $e->getMessage()
                        ]
                    ]
                ]
            ];

            $this->dispatch('mortality-chart-data-refreshed', $errorChart);
            return $errorChart;
        }
    }
}

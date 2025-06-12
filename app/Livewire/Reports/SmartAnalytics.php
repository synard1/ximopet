<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use Livewire\WithPagination;
use App\Services\AnalyticsService;
use App\Models\Farm;
use App\Models\Coop;
use App\Models\DailyAnalytics;
use App\Models\PeriodAnalytics;
use App\Models\AnalyticsAlert;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SmartAnalytics extends Component
{
    use WithPagination;

    public $selectedFarm = '';
    public $selectedCoop = '';
    public $dateFrom;
    public $dateTo;
    public $activeTab = 'overview';
    public $alertsFilter = 'all';

    // Chart data properties
    public $mortalityTrendData = [];
    public $efficiencyTrendData = [];
    public $fcrTrendData = [];
    public $topPerformersData = [];
    public $bottomPerformersData = [];

    protected $analyticsService;

    public function mount()
    {
        $this->analyticsService = new AnalyticsService();
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');

        $this->loadChartData();
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['selectedFarm', 'selectedCoop', 'dateFrom', 'dateTo'])) {
            $this->loadChartData();
            $this->resetPage();
        }
    }

    public function loadChartData()
    {
        $filters = $this->getFilters();
        $report = $this->analyticsService->getAnalyticsReport($filters);

        $this->mortalityTrendData = $this->prepareMortalityTrendData($report['trends']);
        $this->efficiencyTrendData = $this->prepareEfficiencyTrendData($report['trends']);
        $this->fcrTrendData = $this->prepareFcrTrendData($report['trends']);
        $this->topPerformersData = $report['top_performers'];
        $this->bottomPerformersData = $report['bottom_performers'];
    }

    public function getFilters()
    {
        $filters = [
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ];

        if ($this->selectedFarm) {
            $filters['farm_id'] = $this->selectedFarm;
        }

        if ($this->selectedCoop) {
            $filters['coop_id'] = $this->selectedCoop;
        }

        return $filters;
    }

    public function prepareMortalityTrendData($trends)
    {
        $labels = [];
        $data = [];

        foreach ($trends as $date => $metrics) {
            $labels[] = Carbon::parse($date)->format('d/m');
            $data[] = $metrics['mortality_rate'];
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Mortality Rate (%)',
                    'data' => $data,
                    'borderColor' => 'rgb(239, 68, 68)',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'tension' => 0.4,
                ]
            ]
        ];
    }

    public function prepareEfficiencyTrendData($trends)
    {
        $labels = [];
        $data = [];

        foreach ($trends as $date => $metrics) {
            $labels[] = Carbon::parse($date)->format('d/m');
            $data[] = $metrics['efficiency_score'];
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Efficiency Score',
                    'data' => $data,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'tension' => 0.4,
                ]
            ]
        ];
    }

    public function prepareFcrTrendData($trends)
    {
        $labels = [];
        $data = [];

        foreach ($trends as $date => $metrics) {
            $labels[] = Carbon::parse($date)->format('d/m');
            $data[] = $metrics['fcr'];
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'FCR',
                    'data' => $data,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'tension' => 0.4,
                ]
            ]
        ];
    }

    public function getOverviewMetrics()
    {
        $filters = $this->getFilters();
        $report = $this->analyticsService->getAnalyticsReport($filters);

        return $report['summary'];
    }

    public function getMortalityAnalysis()
    {
        $query = DailyAnalytics::with(['coop', 'farm', 'livestock'])
            ->select('coop_id', 'farm_id')
            ->selectRaw('
                AVG(mortality_rate) as avg_mortality_rate,
                SUM(mortality_count) as total_mortality,
                COUNT(*) as total_days,
                MAX(mortality_count) as max_daily_mortality
            ')
            ->groupBy('coop_id', 'farm_id');

        if ($this->selectedFarm) {
            $query->where('farm_id', $this->selectedFarm);
        }

        if ($this->dateFrom && $this->dateTo) {
            $query->whereBetween('date', [$this->dateFrom, $this->dateTo]);
        }

        return $query->orderByDesc('total_mortality')->get();
    }

    public function getSalesAnalysis()
    {
        $query = DailyAnalytics::with(['coop', 'farm', 'livestock'])
            ->select('coop_id', 'farm_id')
            ->selectRaw('
                SUM(sales_count) as total_sales,
                SUM(sales_weight) as total_weight,
                SUM(sales_revenue) as total_revenue,
                AVG(sales_revenue/NULLIF(sales_count,0)) as avg_price_per_bird
            ')
            ->groupBy('coop_id', 'farm_id');

        if ($this->selectedFarm) {
            $query->where('farm_id', $this->selectedFarm);
        }

        if ($this->dateFrom && $this->dateTo) {
            $query->whereBetween('date', [$this->dateFrom, $this->dateTo]);
        }

        return $query->orderByDesc('total_revenue')->get();
    }

    public function getProductionAnalysis()
    {
        $query = DailyAnalytics::with(['coop', 'farm', 'livestock'])
            ->select('coop_id', 'farm_id')
            ->selectRaw('
                AVG(average_weight) as avg_weight,
                AVG(daily_weight_gain) as avg_daily_gain,
                AVG(fcr) as avg_fcr,
                AVG(efficiency_score) as avg_efficiency,
                MAX(average_weight) as max_weight
            ')
            ->groupBy('coop_id', 'farm_id');

        if ($this->selectedFarm) {
            $query->where('farm_id', $this->selectedFarm);
        }

        if ($this->dateFrom && $this->dateTo) {
            $query->whereBetween('date', [$this->dateFrom, $this->dateTo]);
        }

        return $query->orderByDesc('avg_efficiency')->get();
    }

    public function getAlerts()
    {
        $query = AnalyticsAlert::with(['livestock', 'farm', 'coop'])
            ->when($this->alertsFilter !== 'all', function ($q) {
                if ($this->alertsFilter === 'unresolved') {
                    $q->where('is_resolved', false);
                } else {
                    $q->where('severity', $this->alertsFilter);
                }
            })
            ->when($this->selectedFarm, function ($q) {
                $q->where('farm_id', $this->selectedFarm);
            })
            ->orderBy('severity')
            ->orderBy('created_at', 'desc');

        return $query->paginate(10);
    }

    public function resolveAlert($alertId)
    {
        $alert = AnalyticsAlert::findOrFail($alertId);
        $alert->resolve();

        session()->flash('message', 'Alert berhasil diresolve');
    }

    public function generateAnalytics()
    {
        try {
            $this->analyticsService->generateDailyAnalytics();
            session()->flash('message', 'Analytics berhasil digenerate');
            $this->loadChartData();
        } catch (\Exception $e) {
            session()->flash('error', 'Error generating analytics: ' . $e->getMessage());
        }
    }

    public function getCoopRanking()
    {
        $query = DailyAnalytics::with(['coop', 'farm'])
            ->select('coop_id', 'farm_id')
            ->selectRaw('
                AVG(efficiency_score) as avg_efficiency,
                AVG(mortality_rate) as avg_mortality,
                AVG(fcr) as avg_fcr,
                AVG(daily_weight_gain) as avg_daily_gain,
                COUNT(*) as total_days
            ')
            ->groupBy('coop_id', 'farm_id')
            ->having('total_days', '>=', 7); // At least 7 days of data

        if ($this->selectedFarm) {
            $query->where('farm_id', $this->selectedFarm);
        }

        if ($this->dateFrom && $this->dateTo) {
            $query->whereBetween('date', [$this->dateFrom, $this->dateTo]);
        }

        return $query->orderByDesc('avg_efficiency')->get();
    }

    public function getPerformanceInsights()
    {
        $mortalityAnalysis = $this->getMortalityAnalysis();
        $salesAnalysis = $this->getSalesAnalysis();
        $productionAnalysis = $this->getProductionAnalysis();

        $insights = [];

        // Mortality insights
        $highMortalityCoops = $mortalityAnalysis->where('avg_mortality_rate', '>', 5);
        if ($highMortalityCoops->count() > 0) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Kandang dengan Tingkat Kematian Tinggi',
                'description' => $highMortalityCoops->count() . ' kandang memiliki tingkat kematian di atas 5%',
                'action' => 'Lakukan evaluasi manajemen kesehatan'
            ];
        }

        // FCR insights
        $highFcrCoops = $productionAnalysis->where('avg_fcr', '>', 2.0);
        if ($highFcrCoops->count() > 0) {
            $insights[] = [
                'type' => 'info',
                'title' => 'Kandang dengan FCR Tinggi',
                'description' => $highFcrCoops->count() . ' kandang memiliki FCR di atas 2.0',
                'action' => 'Optimalisasi program feeding'
            ];
        }

        // Top performer insight
        $topPerformer = $productionAnalysis->first();
        if ($topPerformer) {
            $insights[] = [
                'type' => 'success',
                'title' => 'Top Performer',
                'description' => "Kandang {$topPerformer->coop->name} mencapai efficiency score tertinggi: " . round($topPerformer->avg_efficiency, 1) . "%",
                'action' => 'Pelajari best practices dari kandang ini'
            ];
        }

        return $insights;
    }

    public function render()
    {
        $farms = Farm::where('status', 'active')->get();
        $coops = $this->selectedFarm ?
            Coop::where('farm_id', $this->selectedFarm)->where('status', 'active')->get() :
            collect();

        $data = [
            'farms' => $farms,
            'coops' => $coops,
            'overviewMetrics' => $this->getOverviewMetrics(),
            'mortalityAnalysis' => $this->getMortalityAnalysis(),
            'salesAnalysis' => $this->getSalesAnalysis(),
            'productionAnalysis' => $this->getProductionAnalysis(),
            'alerts' => $this->getAlerts(),
            'coopRanking' => $this->getCoopRanking(),
            'insights' => $this->getPerformanceInsights(),
        ];

        return view('livewire.reports.smart-analytics', $data);
    }
}

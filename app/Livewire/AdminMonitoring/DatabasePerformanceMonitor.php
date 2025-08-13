<?php

namespace App\Livewire\AdminMonitoring;

use App\Models\DatabasePerformanceLog;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DatabasePerformanceMonitor extends Component
{
    use WithPagination;

    public $selectedModel = '';
    public $selectedOperation = '';
    public $selectedStatus = '';
    public $dateRange = '7';
    public $showBottleneckAnalysis = false;
    public $showTrends = false;
    public $refreshInterval = 30; // seconds

    protected $paginationTheme = 'bootstrap';
    protected $queryString = [
        'selectedModel' => ['except' => ''],
        'selectedOperation' => ['except' => ''],
        'selectedStatus' => ['except' => ''],
        'dateRange' => ['except' => '7'],
    ];

    public function mount()
    {
        // Auto-refresh every 30 seconds
        $this->dispatch('start-auto-refresh', ['interval' => $this->refreshInterval * 1000]);
    }

    public function render()
    {
        $query = DatabasePerformanceLog::query();

        // Apply filters
        if ($this->selectedModel) {
            $query->where('model_class', $this->selectedModel);
        }

        if ($this->selectedOperation) {
            $query->where('operation_type', $this->selectedOperation);
        }

        if ($this->selectedStatus) {
            $query->where('status', $this->selectedStatus);
        }

        // Apply date range
        $startDate = Carbon::now()->subDays($this->dateRange);
        $query->where('created_at', '>=', $startDate);

        $logs = $query->orderBy('created_at', 'desc')
            ->paginate(25);

        // Get summary statistics
        $summaryStats = $this->getSummaryStats();

        // Get model list for filter
        $models = $this->getModelList();

        // Get operation types for filter
        $operationTypes = $this->getOperationTypes();

        // Get statuses for filter
        $statuses = $this->getStatuses();

        return view('livewire.admin-monitoring.database-performance-monitor', [
            'logs' => $logs,
            'summaryStats' => $summaryStats,
            'models' => $models,
            'operationTypes' => $operationTypes,
            'statuses' => $statuses,
        ]);
    }

    public function updatedDateRange()
    {
        $this->resetPage();
    }

    public function updatedSelectedModel()
    {
        $this->resetPage();
    }

    public function updatedSelectedOperation()
    {
        $this->resetPage();
    }

    public function updatedSelectedStatus()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->selectedModel = '';
        $this->selectedOperation = '';
        $this->selectedStatus = '';
        $this->dateRange = '7';
        $this->resetPage();
    }

    public function getBottleneckAnalysis()
    {
        $this->showBottleneckAnalysis = true;
        $this->showTrends = false;
    }

    public function getPerformanceTrends()
    {
        $this->showTrends = true;
        $this->showBottleneckAnalysis = false;
    }

    public function hideAnalysis()
    {
        $this->showBottleneckAnalysis = false;
        $this->showTrends = false;
    }

    public function getSummaryStats()
    {
        $startDate = Carbon::now()->subDays($this->dateRange);

        return DatabasePerformanceLog::where('created_at', '>=', $startDate)
            ->selectRaw('
                COUNT(*) as total_operations,
                COUNT(DISTINCT model_class) as unique_models,
                COUNT(DISTINCT user_id) as unique_users,
                AVG(execution_time_ms) as overall_avg_time,
                MAX(execution_time_ms) as slowest_operation,
                SUM(records_count) as total_records_processed,
                COUNT(CASE WHEN status = "slow" THEN 1 END) as total_slow_operations,
                COUNT(CASE WHEN status = "error" THEN 1 END) as total_errors
            ')
            ->first();
    }

    public function getModelList()
    {
        return DatabasePerformanceLog::select('model_class')
            ->distinct()
            ->orderBy('model_class')
            ->pluck('model_class')
            ->map(function ($modelClass) {
                return [
                    'value' => $modelClass,
                    'label' => class_basename($modelClass)
                ];
            });
    }

    public function getOperationTypes()
    {
        return DatabasePerformanceLog::select('operation_type')
            ->distinct()
            ->orderBy('operation_type')
            ->pluck('operation_type');
    }

    public function getStatuses()
    {
        return DatabasePerformanceLog::select('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');
    }

    public function getBottleneckAnalysisData()
    {
        $startDate = Carbon::now()->subDays($this->dateRange);

        return DatabasePerformanceLog::where('created_at', '>=', $startDate)
            ->selectRaw('
                model_class,
                operation_type,
                COUNT(*) as operation_count,
                AVG(execution_time_ms) as avg_execution_time,
                MAX(execution_time_ms) as max_execution_time,
                AVG(query_count) as avg_query_count,
                MAX(query_count) as max_query_count,
                SUM(records_count) as total_records_processed,
                COUNT(CASE WHEN status = "slow" THEN 1 END) as slow_count,
                COUNT(CASE WHEN status = "error" THEN 1 END) as error_count
            ')
            ->groupBy('model_class', 'operation_type')
            ->having('avg_execution_time', '>', 500) // Operations taking more than 500ms on average
            ->orderBy('avg_execution_time', 'desc')
            ->get();
    }

    public function getPerformanceTrendsData()
    {
        $startDate = Carbon::now()->subDays($this->dateRange);

        return DatabasePerformanceLog::where('created_at', '>=', $startDate)
            ->selectRaw('
                DATE(created_at) as date,
                model_class,
                COUNT(*) as operations,
                AVG(execution_time_ms) as avg_execution_time,
                AVG(memory_usage_mb) as avg_memory_usage,
                AVG(query_count) as avg_query_count,
                SUM(records_count) as total_records,
                COUNT(CASE WHEN status = "success" THEN 1 END) as success_count,
                COUNT(CASE WHEN status = "error" THEN 1 END) as error_count,
                COUNT(CASE WHEN status = "slow" THEN 1 END) as slow_count
            ')
            ->groupBy('date', 'model_class')
            ->orderBy('date')
            ->get();
    }

    public function refreshData()
    {
        $this->dispatch('$refresh');
    }

    public function exportData()
    {
        // Implementation for data export
        $this->dispatch('show-export-modal');
    }
}

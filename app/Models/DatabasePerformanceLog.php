<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Carbon\Carbon;

class DatabasePerformanceLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'operation_type', // create, update, delete, bulk_create, bulk_update, bulk_delete
        'model_class',
        'model_id',
        'table_name',
        'records_count',
        'execution_time_ms',
        'memory_usage_mb',
        'query_count',
        'slow_query_count',
        'status', // success, error, slow
        'error_message',
        'user_id',
        'company_id',
        'request_id',
        'session_id',
        'ip_address',
        'user_agent',
        'additional_data',
        'created_at'
    ];

    protected $casts = [
        'additional_data' => 'array',
        'execution_time_ms' => 'decimal:3',
        'memory_usage_mb' => 'decimal:2',
        'records_count' => 'integer',
        'query_count' => 'integer',
        'slow_query_count' => 'integer',
        'created_at' => 'datetime'
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scopes
     */
    public function scopeSlow($query, $threshold = 1000)
    {
        return $query->where('execution_time_ms', '>', $threshold);
    }

    public function scopeByModel($query, $modelClass)
    {
        return $query->where('model_class', $modelClass);
    }

    public function scopeByOperation($query, $operationType)
    {
        return $query->where('operation_type', $operationType);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Get performance statistics for a model
     */
    public static function getModelStats($modelClass, $startDate = null, $endDate = null)
    {
        $query = self::where('model_class', $modelClass);

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $stats = $query->selectRaw('
            COUNT(*) as total_operations,
            AVG(execution_time_ms) as avg_execution_time,
            MAX(execution_time_ms) as max_execution_time,
            MIN(execution_time_ms) as min_execution_time,
            AVG(memory_usage_mb) as avg_memory_usage,
            MAX(memory_usage_mb) as max_memory_usage,
            AVG(query_count) as avg_query_count,
            MAX(query_count) as max_query_count,
            SUM(records_count) as total_records_processed,
            COUNT(CASE WHEN status = "success" THEN 1 END) as success_count,
            COUNT(CASE WHEN status = "error" THEN 1 END) as error_count,
            COUNT(CASE WHEN status = "slow" THEN 1 END) as slow_count
        ')->first();

        return $stats;
    }

    /**
     * Get performance trends over time
     */
    public static function getPerformanceTrends($modelClass = null, $days = 30)
    {
        $startDate = Carbon::now()->subDays($days);
        $query = self::where('created_at', '>=', $startDate);

        if ($modelClass) {
            $query->where('model_class', $modelClass);
        }

        return $query->selectRaw('
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

    /**
     * Get slowest operations
     */
    public static function getSlowestOperations($modelClass = null, $limit = 20)
    {
        $query = self::orderBy('execution_time_ms', 'desc');

        if ($modelClass) {
            $query->where('model_class', $modelClass);
        }

        return $query->limit($limit)->get();
    }

    /**
     * Get error logs
     */
    public static function getErrorLogs($modelClass = null, $limit = 50)
    {
        $query = self::where('status', 'error')->orderBy('created_at', 'desc');

        if ($modelClass) {
            $query->where('model_class', $modelClass);
        }

        return $query->limit($limit)->get();
    }

    /**
     * Get bottleneck analysis
     */
    public static function getBottleneckAnalysis($days = 7)
    {
        $startDate = Carbon::now()->subDays($days);

        return self::where('created_at', '>=', $startDate)
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

    /**
     * Clean old performance logs
     */
    public static function cleanOldLogs($daysToKeep = 90)
    {
        $cutoffDate = Carbon::now()->subDays($daysToKeep);
        return self::where('created_at', '<', $cutoffDate)->delete();
    }

    /**
     * Get summary statistics
     */
    public static function getSummaryStats($days = 30)
    {
        $startDate = Carbon::now()->subDays($days);

        return self::where('created_at', '>=', $startDate)
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
}

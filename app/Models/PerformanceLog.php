<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Carbon\Carbon;

class PerformanceLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'service_name',
        'method_name',
        'livestock_id',
        'tanggal',
        'execution_time_ms',
        'memory_usage_mb',
        'data_sources',
        'status',
        'error_message',
        'created_at'
    ];

    protected $casts = [
        'tanggal' => 'date',
        'data_sources' => 'array',
        'execution_time_ms' => 'decimal:2',
        'memory_usage_mb' => 'decimal:2',
        'created_at' => 'datetime'
    ];

    public function livestock()
    {
        return $this->belongsTo(Livestock::class);
    }

    /**
     * Get performance statistics for a service
     */
    public static function getServiceStats($serviceName, $startDate = null, $endDate = null)
    {
        $query = self::where('service_name', $serviceName);

        if ($startDate) {
            $query->where('tanggal', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('tanggal', '<=', $endDate);
        }

        $stats = $query->selectRaw('
            COUNT(*) as total_executions,
            AVG(execution_time_ms) as avg_execution_time,
            MAX(execution_time_ms) as max_execution_time,
            MIN(execution_time_ms) as min_execution_time,
            AVG(memory_usage_mb) as avg_memory_usage,
            MAX(memory_usage_mb) as max_memory_usage,
            COUNT(CASE WHEN status = "success" THEN 1 END) as success_count,
            COUNT(CASE WHEN status = "error" THEN 1 END) as error_count
        ')->first();

        return $stats;
    }

    /**
     * Get performance trends over time
     */
    public static function getPerformanceTrends($serviceName, $days = 30)
    {
        $startDate = Carbon::now()->subDays($days);

        return self::where('service_name', $serviceName)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('
                DATE(created_at) as date,
                COUNT(*) as executions,
                AVG(execution_time_ms) as avg_execution_time,
                AVG(memory_usage_mb) as avg_memory_usage,
                COUNT(CASE WHEN status = "success" THEN 1 END) as success_count,
                COUNT(CASE WHEN status = "error" THEN 1 END) as error_count
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    /**
     * Get slowest executions
     */
    public static function getSlowestExecutions($serviceName, $limit = 10)
    {
        return self::where('service_name', $serviceName)
            ->orderBy('execution_time_ms', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get error logs
     */
    public static function getErrorLogs($serviceName, $limit = 50)
    {
        return self::where('service_name', $serviceName)
            ->where('status', 'error')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
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
}

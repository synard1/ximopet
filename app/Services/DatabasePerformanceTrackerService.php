<?php

namespace App\Services;

use App\Models\DatabasePerformanceLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DatabasePerformanceTrackerService
{
    protected $startTime;
    protected $startMemory;
    protected $queryCount = 0;
    protected $slowQueryCount = 0;
    protected $slowQueryThreshold = 100; // ms
    protected $isEnabled = true;

    public function __construct()
    {
        $this->isEnabled = config('database.performance_tracking.enabled', true);
        $this->slowQueryThreshold = config('database.performance_tracking.slow_query_threshold', 100);
    }

    /**
     * Start tracking a database operation
     */
    public function startTracking(): void
    {
        if (!$this->isEnabled) {
            return;
        }

        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);
        $this->queryCount = 0;
        $this->slowQueryCount = 0;

        // Listen to database queries
        DB::listen(function ($query) {
            $this->queryCount++;

            $executionTime = $query->time;
            if ($executionTime > $this->slowQueryThreshold) {
                $this->slowQueryCount++;
            }
        });
    }

    /**
     * Stop tracking and log performance data
     */
    public function stopTracking(
        string $operationType,
        string $modelClass,
        ?string $modelId = null,
        int $recordsCount = 1,
        ?string $errorMessage = null
    ): ?DatabasePerformanceLog {
        if (!$this->isEnabled || !$this->startTime) {
            return null;
        }

        $executionTime = (microtime(true) - $this->startTime) * 1000; // Convert to milliseconds
        $memoryUsage = (memory_get_usage(true) - $this->startMemory) / 1024 / 1024; // Convert to MB

        // Determine status
        $status = 'success';
        if ($errorMessage) {
            $status = 'error';
        } elseif ($executionTime > 1000) { // Operations taking more than 1 second are considered slow
            $status = 'slow';
        }

        // Get table name from model class
        $tableName = $this->getTableNameFromModel($modelClass);

        try {
            $log = DatabasePerformanceLog::create([
                'operation_type' => $operationType,
                'model_class' => $modelClass,
                'model_id' => $modelId,
                'table_name' => $tableName,
                'records_count' => $recordsCount,
                'execution_time_ms' => round($executionTime, 3),
                'memory_usage_mb' => round($memoryUsage, 2),
                'query_count' => $this->queryCount,
                'slow_query_count' => $this->slowQueryCount,
                'status' => $status,
                'error_message' => $errorMessage,
                'user_id' => Auth::id(),
                'company_id' => Auth::user()->company_id ?? null,
                'request_id' => $this->getRequestId(),
                'session_id' => session()->getId(),
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'additional_data' => [
                    'slow_query_threshold' => $this->slowQueryThreshold,
                    'peak_memory' => memory_get_peak_usage(true) / 1024 / 1024,
                    'php_version' => PHP_VERSION,
                    'laravel_version' => app()->version(),
                ]
            ]);

            // Log to performance-specific channels
            if ($status === 'slow' || $status === 'error') {
                // Log to performance channel
                \Illuminate\Support\Facades\Log::channel('performance')->warning("Database Performance Issue: {$operationType} on {$modelClass}", [
                    'execution_time_ms' => $executionTime,
                    'memory_usage_mb' => $memoryUsage,
                    'query_count' => $this->queryCount,
                    'slow_query_count' => $this->slowQueryCount,
                    'status' => $status,
                    'error_message' => $errorMessage,
                    'user_id' => Auth::id(),
                    'company_id' => Auth::user()->company_id ?? null,
                ]);
            }

            // Always log to database performance channel for monitoring
            \Illuminate\Support\Facades\Log::channel('database_performance')->info("Database Operation: {$operationType} on {$modelClass}", [
                'execution_time_ms' => $executionTime,
                'memory_usage_mb' => $memoryUsage,
                'query_count' => $this->queryCount,
                'slow_query_count' => $this->slowQueryCount,
                'status' => $status,
                'error_message' => $errorMessage,
                'user_id' => Auth::id(),
                'company_id' => Auth::user()->company_id ?? null,
                'model_id' => $modelId,
                'table_name' => $tableName,
                'records_count' => $recordsCount,
                'operation_type' => $operationType,
            ]);

            return $log;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to create database performance log', [
                'error' => $e->getMessage(),
                'operation_type' => $operationType,
                'model_class' => $modelClass,
                'execution_time_ms' => $executionTime
            ]);
            return null;
        }
    }

    /**
     * Track model creation
     */
    public function trackCreate(Model $model, ?string $errorMessage = null): ?DatabasePerformanceLog
    {
        $this->startTracking();

        try {
            $model->save();
            return $this->stopTracking(
                'create',
                get_class($model),
                $model->id,
                1,
                $errorMessage
            );
        } catch (\Exception $e) {
            return $this->stopTracking(
                'create',
                get_class($model),
                null,
                1,
                $e->getMessage()
            );
        }
    }

    /**
     * Track model update
     */
    public function trackUpdate(Model $model, ?string $errorMessage = null): ?DatabasePerformanceLog
    {
        $this->startTracking();

        try {
            $model->save();
            return $this->stopTracking(
                'update',
                get_class($model),
                $model->id,
                1,
                $errorMessage
            );
        } catch (\Exception $e) {
            return $this->stopTracking(
                'update',
                get_class($model),
                $model->id,
                1,
                $e->getMessage()
            );
        }
    }

    /**
     * Track model deletion
     */
    public function trackDelete(Model $model, ?string $errorMessage = null): ?DatabasePerformanceLog
    {
        $this->startTracking();

        try {
            $model->delete();
            return $this->stopTracking(
                'delete',
                get_class($model),
                $model->id,
                1,
                $errorMessage
            );
        } catch (\Exception $e) {
            return $this->stopTracking(
                'delete',
                get_class($model),
                $model->id,
                1,
                $e->getMessage()
            );
        }
    }

    /**
     * Track bulk operations
     */
    public function trackBulkOperation(
        string $operationType,
        string $modelClass,
        array $data,
        callable $operation,
        ?string $errorMessage = null
    ): ?DatabasePerformanceLog {
        $this->startTracking();

        try {
            $result = $operation();
            return $this->stopTracking(
                $operationType,
                $modelClass,
                null,
                count($data),
                $errorMessage
            );
        } catch (\Exception $e) {
            return $this->stopTracking(
                $operationType,
                $modelClass,
                null,
                count($data),
                $e->getMessage()
            );
        }
    }

    /**
     * Track database transaction
     */
    public function trackTransaction(
        string $modelClass,
        callable $transaction,
        ?string $errorMessage = null
    ): ?DatabasePerformanceLog {
        $this->startTracking();

        try {
            $result = DB::transaction($transaction);
            return $this->stopTracking(
                'transaction',
                $modelClass,
                null,
                1,
                $errorMessage
            );
        } catch (\Exception $e) {
            return $this->stopTracking(
                'transaction',
                $modelClass,
                null,
                1,
                $e->getMessage()
            );
        }
    }

    /**
     * Get table name from model class
     */
    protected function getTableNameFromModel(string $modelClass): string
    {
        try {
            if (class_exists($modelClass)) {
                $model = new $modelClass();
                return $model->getTable();
            }
        } catch (\Exception $e) {
            // If we can't instantiate the model, try to extract table name from class name
            $className = class_basename($modelClass);
            return Str::snake(Str::pluralStudly($className));
        }

        return 'unknown';
    }

    /**
     * Get unique request ID
     */
    protected function getRequestId(): string
    {
        if (!session()->has('request_id')) {
            session(['request_id' => Str::uuid()]);
        }
        return session('request_id');
    }

    /**
     * Enable/disable tracking
     */
    public function setEnabled(bool $enabled): void
    {
        $this->isEnabled = $enabled;
    }

    /**
     * Set slow query threshold
     */
    public function setSlowQueryThreshold(int $threshold): void
    {
        $this->slowQueryThreshold = $threshold;
    }

    /**
     * Get current tracking status
     */
    public function isTracking(): bool
    {
        return $this->isEnabled && $this->startTime !== null;
    }

    /**
     * Reset tracking state
     */
    public function reset(): void
    {
        $this->startTime = null;
        $this->startMemory = null;
        $this->queryCount = 0;
        $this->slowQueryCount = 0;
    }
}

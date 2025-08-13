<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PerformanceLogHelper
{
    /**
     * Log performance information to performance channel
     */
    public static function logPerformance(string $operation, string $model, array $data = [], string $level = 'info'): void
    {
        $logData = array_merge([
            'operation' => $operation,
            'model' => $model,
            'user_id' => Auth::id(),
            'company_id' => Auth::user()->company_id ?? null,
            'timestamp' => Carbon::now()->toISOString(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ], $data);

        Log::channel('performance')->$level("Performance: {$operation} on {$model}", $logData);
    }

    /**
     * Log database operation performance
     */
    public static function logDatabaseOperation(string $operation, string $model, float $executionTime, array $data = []): void
    {
        $logData = array_merge([
            'operation' => $operation,
            'model' => $model,
            'execution_time_ms' => $executionTime,
            'user_id' => Auth::id(),
            'company_id' => Auth::user()->company_id ?? null,
            'timestamp' => Carbon::now()->toISOString(),
        ], $data);

        Log::channel('database_performance')->info("DB Operation: {$operation} on {$model}", $logData);
    }

    /**
     * Log slow operation warning
     */
    public static function logSlowOperation(string $operation, string $model, float $executionTime, array $data = []): void
    {
        $logData = array_merge([
            'operation' => $operation,
            'model' => $model,
            'execution_time_ms' => $executionTime,
            'user_id' => Auth::id(),
            'company_id' => Auth::user()->company_id ?? null,
            'timestamp' => Carbon::now()->toISOString(),
            'warning_type' => 'slow_operation',
        ], $data);

        Log::channel('performance')->warning("Slow Operation: {$operation} on {$model}", $logData);
        Log::channel('monitoring')->warning("Slow Operation Detected: {$operation} on {$model}", $logData);
    }

    /**
     * Log error operation
     */
    public static function logErrorOperation(string $operation, string $model, string $error, array $data = []): void
    {
        $logData = array_merge([
            'operation' => $operation,
            'model' => $model,
            'error' => $error,
            'user_id' => Auth::id(),
            'company_id' => Auth::user()->company_id ?? null,
            'timestamp' => Carbon::now()->toISOString(),
            'error_type' => 'operation_error',
        ], $data);

        Log::channel('performance')->error("Error Operation: {$operation} on {$model}", $logData);
        Log::channel('monitoring')->error("Error Operation Detected: {$operation} on {$model}", $logData);
    }

    /**
     * Log monitoring information
     */
    public static function logMonitoring(string $message, array $data = [], string $level = 'info'): void
    {
        $logData = array_merge([
            'message' => $message,
            'user_id' => Auth::id(),
            'company_id' => Auth::user()->company_id ?? null,
            'timestamp' => Carbon::now()->toISOString(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
        ], $data);

        Log::channel('monitoring')->$level($message, $logData);
    }

    /**
     * Log transaction performance
     */
    public static function logTransaction(string $transactionType, string $model, float $executionTime, array $data = []): void
    {
        $logData = array_merge([
            'transaction_type' => $transactionType,
            'model' => $model,
            'execution_time_ms' => $executionTime,
            'user_id' => Auth::id(),
            'company_id' => Auth::user()->company_id ?? null,
            'timestamp' => Carbon::now()->toISOString(),
        ], $data);

        Log::channel('database_performance')->info("Transaction: {$transactionType} on {$model}", $logData);
    }

    /**
     * Log bulk operation performance
     */
    public static function logBulkOperation(string $operation, string $model, int $recordCount, float $executionTime, array $data = []): void
    {
        $logData = array_merge([
            'operation' => $operation,
            'model' => $model,
            'record_count' => $recordCount,
            'execution_time_ms' => $executionTime,
            'user_id' => Auth::id(),
            'company_id' => Auth::user()->company_id ?? null,
            'timestamp' => Carbon::now()->toISOString(),
        ], $data);

        Log::channel('database_performance')->info("Bulk Operation: {$operation} on {$model}", $logData);
    }

    /**
     * Log memory usage
     */
    public static function logMemoryUsage(string $operation, string $model, float $memoryUsage, array $data = []): void
    {
        $logData = array_merge([
            'operation' => $operation,
            'model' => $model,
            'memory_usage_mb' => $memoryUsage,
            'user_id' => Auth::id(),
            'company_id' => Auth::user()->company_id ?? null,
            'timestamp' => Carbon::now()->toISOString(),
        ], $data);

        Log::channel('performance')->info("Memory Usage: {$operation} on {$model}", $logData);
    }

    /**
     * Log query performance
     */
    public static function logQueryPerformance(string $operation, string $model, int $queryCount, int $slowQueryCount, array $data = []): void
    {
        $logData = array_merge([
            'operation' => $operation,
            'model' => $model,
            'query_count' => $queryCount,
            'slow_query_count' => $slowQueryCount,
            'user_id' => Auth::id(),
            'company_id' => Auth::user()->company_id ?? null,
            'timestamp' => Carbon::now()->toISOString(),
        ], $data);

        Log::channel('database_performance')->info("Query Performance: {$operation} on {$model}", $logData);
    }
}

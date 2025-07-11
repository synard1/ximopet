<?php

namespace App\Services\Recording;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Recording Health Service
 * 
 * Provides health monitoring and status checking for recording services
 * Implements comprehensive health checks, performance monitoring, and alerting
 */
class RecordingHealthService
{
    private const HEALTH_CHECK_CACHE_TTL = 300; // 5 minutes
    private const PERFORMANCE_THRESHOLD = 2.0; // seconds
    private const ERROR_RATE_THRESHOLD = 0.05; // 5%

    public function __construct(
        private UuidHelperService $uuidHelper
    ) {}

    /**
     * Get comprehensive health status
     */
    public function getHealthStatus(): array
    {
        $checks = [
            'database' => $this->checkDatabaseHealth(),
            'cache' => $this->checkCacheHealth(),
            'performance' => $this->checkPerformanceHealth(),
            'services' => $this->checkServicesHealth(),
            'errors' => $this->checkErrorHealth(),
            'system' => $this->checkSystemHealth(),
        ];

        $overallStatus = $this->determineOverallStatus($checks);
        
        return [
            'status' => $overallStatus,
            'timestamp' => now()->toISOString(),
            'checks' => $checks,
            'summary' => $this->generateHealthSummary($checks, $overallStatus)
        ];
    }

    /**
     * Check database health
     */
    private function checkDatabaseHealth(): array
    {
        try {
            $startTime = microtime(true);
            
            // Test basic connectivity
            DB::connection()->getPdo();
            
            // Test simple query
            $result = DB::select('SELECT 1 as test');
            
            $responseTime = microtime(true) - $startTime;
            
            // Check recording tables
            $tableChecks = $this->checkRecordingTables();
            
            $status = $responseTime < 1.0 && $tableChecks['healthy'] ? 'healthy' : 'degraded';
            
            return [
                'status' => $status,
                'response_time' => round($responseTime, 4),
                'connection' => 'connected',
                'tables' => $tableChecks,
                'details' => [
                    'database_name' => config('database.connections.mysql.database'),
                    'connection_driver' => config('database.default')
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Database health check failed', ['error' => $e->getMessage()]);
            
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'connection' => 'disconnected',
                'response_time' => null
            ];
        }
    }

    /**
     * Check recording tables health
     */
    private function checkRecordingTables(): array
    {
        $tables = [
            'recordings',
            'ovk_records',
            'feed_usages',
            'supply_usages',
            'livestock_mutations',
            'recording_performance_logs'
        ];

        $results = [];
        $healthyCount = 0;

        foreach ($tables as $table) {
            try {
                $count = DB::table($table)->count();
                $results[$table] = [
                    'exists' => true,
                    'record_count' => $count,
                    'status' => 'healthy'
                ];
                $healthyCount++;
            } catch (\Exception $e) {
                $results[$table] = [
                    'exists' => false,
                    'error' => $e->getMessage(),
                    'status' => 'unhealthy'
                ];
            }
        }

        return [
            'healthy' => $healthyCount === count($tables),
            'healthy_count' => $healthyCount,
            'total_count' => count($tables),
            'tables' => $results
        ];
    }

    /**
     * Check cache health
     */
    private function checkCacheHealth(): array
    {
        try {
            $startTime = microtime(true);
            
            $testKey = 'health_check_' . time();
            $testValue = 'test_value';
            
            // Test write
            Cache::put($testKey, $testValue, 60);
            
            // Test read
            $retrievedValue = Cache::get($testKey);
            
            // Clean up
            Cache::forget($testKey);
            
            $responseTime = microtime(true) - $startTime;
            
            $status = ($retrievedValue === $testValue && $responseTime < 0.1) ? 'healthy' : 'degraded';
            
            return [
                'status' => $status,
                'response_time' => round($responseTime, 4),
                'read_write' => $retrievedValue === $testValue,
                'driver' => config('cache.default'),
                'details' => [
                    'connection' => 'connected',
                    'test_passed' => $retrievedValue === $testValue
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Cache health check failed', ['error' => $e->getMessage()]);
            
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'driver' => config('cache.default'),
                'connection' => 'disconnected'
            ];
        }
    }

    /**
     * Check performance health
     */
    private function checkPerformanceHealth(): array
    {
        try {
            $lastHour = now()->subHour();
            
            $performanceStats = DB::table('recording_performance_logs')
                ->select([
                    DB::raw('AVG(execution_time) as avg_time'),
                    DB::raw('MAX(execution_time) as max_time'),
                    DB::raw('COUNT(*) as total_operations'),
                    DB::raw('SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as successful_operations')
                ])
                ->where('created_at', '>=', $lastHour)
                ->first();

            $avgTime = $performanceStats->avg_time ?? 0;
            $maxTime = $performanceStats->max_time ?? 0;
            $totalOps = $performanceStats->total_operations ?? 0;
            $successfulOps = $performanceStats->successful_operations ?? 0;
            
            $successRate = $totalOps > 0 ? ($successfulOps / $totalOps) : 1.0;
            $errorRate = 1 - $successRate;

            $status = 'healthy';
            if ($avgTime > self::PERFORMANCE_THRESHOLD || $errorRate > self::ERROR_RATE_THRESHOLD) {
                $status = 'degraded';
            }
            if ($errorRate > 0.1) { // 10% error rate
                $status = 'unhealthy';
            }

            return [
                'status' => $status,
                'metrics' => [
                    'avg_execution_time' => round($avgTime, 4),
                    'max_execution_time' => round($maxTime, 4),
                    'total_operations' => $totalOps,
                    'successful_operations' => $successfulOps,
                    'success_rate' => round($successRate * 100, 2),
                    'error_rate' => round($errorRate * 100, 2)
                ],
                'thresholds' => [
                    'performance_threshold' => self::PERFORMANCE_THRESHOLD,
                    'error_rate_threshold' => self::ERROR_RATE_THRESHOLD * 100
                ],
                'period' => 'last_hour'
            ];
        } catch (\Exception $e) {
            Log::error('Performance health check failed', ['error' => $e->getMessage()]);
            
            return [
                'status' => 'unknown',
                'error' => $e->getMessage(),
                'metrics' => null
            ];
        }
    }

    /**
     * Check services health
     */
    private function checkServicesHealth(): array
    {
        $services = [
            'uuid_helper' => $this->checkUuidHelperService(),
            'performance' => $this->checkPerformanceService(),
            'error_handling' => $this->checkErrorHandlingService(),
        ];

        $healthyCount = count(array_filter($services, fn($s) => $s['status'] === 'healthy'));
        $totalCount = count($services);

        return [
            'status' => $healthyCount === $totalCount ? 'healthy' : 'degraded',
            'healthy_count' => $healthyCount,
            'total_count' => $totalCount,
            'services' => $services
        ];
    }

    /**
     * Check UUID helper service
     */
    private function checkUuidHelperService(): array
    {
        try {
            $testUuid = $this->uuidHelper->generateUuid();
            $isValid = $this->uuidHelper->isValidUuid($testUuid);
            
            return [
                'status' => $isValid ? 'healthy' : 'unhealthy',
                'test_uuid' => $testUuid,
                'validation_passed' => $isValid
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check performance service
     */
    private function checkPerformanceService(): array
    {
        try {
            // Test cache operations
            $testKey = 'health_check_performance_' . time();
            Cache::put($testKey, 'test', 60);
            $result = Cache::get($testKey);
            Cache::forget($testKey);
            
            return [
                'status' => $result === 'test' ? 'healthy' : 'unhealthy',
                'cache_test_passed' => $result === 'test'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check error handling service
     */
    private function checkErrorHandlingService(): array
    {
        try {
            // Test error categorization
            $testError = new \Exception('Test error');
            $errorService = app(\App\Services\Recording\RecordingErrorHandlingService::class);
            
            $result = $errorService->createErrorResponse($testError, ['test' => true]);
            
            return [
                'status' => isset($result['error']['type']) ? 'healthy' : 'unhealthy',
                'error_handling_passed' => isset($result['error']['type'])
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check error health
     */
    private function checkErrorHealth(): array
    {
        try {
            $lastHour = now()->subHour();
            
            $errorStats = DB::table('recording_performance_logs')
                ->select([
                    DB::raw('COUNT(*) as total_errors'),
                    DB::raw('COUNT(DISTINCT operation_type) as error_types'),
                    DB::raw('MAX(created_at) as last_error_time')
                ])
                ->where('success', false)
                ->where('created_at', '>=', $lastHour)
                ->first();

            $totalErrors = $errorStats->total_errors ?? 0;
            $errorTypes = $errorStats->error_types ?? 0;
            $lastErrorTime = $errorStats->last_error_time;

            $status = 'healthy';
            if ($totalErrors > 10) {
                $status = 'degraded';
            }
            if ($totalErrors > 50) {
                $status = 'unhealthy';
            }

            return [
                'status' => $status,
                'metrics' => [
                    'total_errors_last_hour' => $totalErrors,
                    'error_types_count' => $errorTypes,
                    'last_error_time' => $lastErrorTime,
                    'error_free_period' => $lastErrorTime ? now()->diffInMinutes($lastErrorTime) : null
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unknown',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check system health
     */
    private function checkSystemHealth(): array
    {
        return [
            'status' => 'healthy',
            'metrics' => [
                'memory_usage' => memory_get_usage(true),
                'peak_memory_usage' => memory_get_peak_usage(true),
                'memory_limit' => ini_get('memory_limit'),
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'timezone' => config('app.timezone'),
                'environment' => config('app.env')
            ]
        ];
    }

    /**
     * Determine overall health status
     */
    private function determineOverallStatus(array $checks): string
    {
        $statuses = array_column($checks, 'status');
        
        if (in_array('unhealthy', $statuses)) {
            return 'unhealthy';
        }
        
        if (in_array('degraded', $statuses)) {
            return 'degraded';
        }
        
        return 'healthy';
    }

    /**
     * Generate health summary
     */
    private function generateHealthSummary(array $checks, string $overallStatus): array
    {
        $healthyChecks = count(array_filter($checks, fn($c) => $c['status'] === 'healthy'));
        $totalChecks = count($checks);
        
        $summary = [
            'overall_status' => $overallStatus,
            'healthy_checks' => $healthyChecks,
            'total_checks' => $totalChecks,
            'health_percentage' => round(($healthyChecks / $totalChecks) * 100, 2)
        ];

        // Add recommendations based on status
        if ($overallStatus === 'unhealthy') {
            $summary['recommendations'] = [
                'Immediate attention required',
                'Check error logs for details',
                'Verify database connectivity',
                'Review system resources'
            ];
        } elseif ($overallStatus === 'degraded') {
            $summary['recommendations'] = [
                'Monitor system performance',
                'Check for recent errors',
                'Review performance metrics'
            ];
        } else {
            $summary['recommendations'] = [
                'System is operating normally',
                'Continue monitoring'
            ];
        }

        return $summary;
    }

    /**
     * Get detailed health report
     */
    public function getDetailedHealthReport(): array
    {
        $healthStatus = $this->getHealthStatus();
        
        // Add additional details
        $healthStatus['detailed_metrics'] = [
            'database_connections' => $this->getDatabaseConnectionInfo(),
            'cache_statistics' => $this->getCacheStatistics(),
            'performance_trends' => $this->getPerformanceTrends(),
            'error_analysis' => $this->getErrorAnalysis()
        ];

        return $healthStatus;
    }

    /**
     * Get database connection information
     */
    private function getDatabaseConnectionInfo(): array
    {
        try {
            $pdo = DB::connection()->getPdo();
            
            return [
                'driver' => config('database.default'),
                'database' => config('database.connections.mysql.database'),
                'host' => config('database.connections.mysql.host'),
                'port' => config('database.connections.mysql.port'),
                'connection_status' => 'connected',
                'server_version' => $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION)
            ];
        } catch (\Exception $e) {
            return [
                'connection_status' => 'disconnected',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get cache statistics
     */
    private function getCacheStatistics(): array
    {
        return [
            'driver' => config('cache.default'),
            'prefix' => config('cache.prefix'),
            'ttl_default' => config('cache.ttl', 3600)
        ];
    }

    /**
     * Get performance trends
     */
    private function getPerformanceTrends(): array
    {
        try {
            $last24Hours = now()->subDay();
            
            $trends = DB::table('recording_performance_logs')
                ->select([
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('AVG(execution_time) as avg_time'),
                    DB::raw('COUNT(*) as operations'),
                    DB::raw('SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as successful')
                ])
                ->where('created_at', '>=', $last24Hours)
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            return [
                'period' => 'last_24_hours',
                'data_points' => $trends->count(),
                'trends' => $trends->toArray()
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get error analysis
     */
    private function getErrorAnalysis(): array
    {
        try {
            $last24Hours = now()->subDay();
            
            $errors = DB::table('recording_performance_logs')
                ->select([
                    'operation_type',
                    DB::raw('COUNT(*) as error_count'),
                    DB::raw('MAX(created_at) as last_occurrence')
                ])
                ->where('success', false)
                ->where('created_at', '>=', $last24Hours)
                ->groupBy('operation_type')
                ->orderBy('error_count', 'desc')
                ->get();

            return [
                'period' => 'last_24_hours',
                'total_errors' => $errors->sum('error_count'),
                'error_types' => $errors->toArray()
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }
} 
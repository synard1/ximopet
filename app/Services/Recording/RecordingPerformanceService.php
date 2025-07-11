<?php

namespace App\Services\Recording;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use function App\Helpers\logInfoIfDebug;
use function App\Helpers\logDebugIfDebug;
use function App\Helpers\logWarningIfDebug;
use function App\Helpers\logErrorIfDebug;

/**
 * Recording Performance Service
 * 
 * Provides performance optimization for recording operations
 * Implements caching, query optimization, and performance monitoring
 */
class RecordingPerformanceService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const BATCH_SIZE = 1000;
    private const PERFORMANCE_THRESHOLD = 2.0; // seconds

    public function __construct(
        private UuidHelperService $uuidHelper
    ) {}

    /**
     * Cache recording data with intelligent key generation
     */
    public function cacheRecordingData(string $livestockId, Carbon $date, array $data): bool
    {
        try {
            $cacheKey = $this->generateCacheKey('recording_data', $livestockId, $date);

            // Check if cache store supports tagging
            if ($this->cacheStoreSupportsTags()) {
                Cache::tags(['recording', 'livestock', $livestockId])
                    ->put($cacheKey, $data, self::CACHE_TTL);
            } else {
                // Fallback for cache stores that don't support tagging
                Cache::put($cacheKey, $data, self::CACHE_TTL);
            }

            return true;
        } catch (\Exception $e) {
            logErrorIfDebug('Failed to cache recording data', [
                'livestock_id' => $livestockId,
                'date' => $date->format('Y-m-d'),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get cached recording data
     */
    public function getCachedRecordingData(string $livestockId, Carbon $date): ?array
    {
        try {
            $cacheKey = $this->generateCacheKey('recording_data', $livestockId, $date);

            // Check if cache store supports tagging
            if ($this->cacheStoreSupportsTags()) {
                return Cache::tags(['recording', 'livestock', $livestockId])
                    ->get($cacheKey);
            } else {
                // Fallback for cache stores that don't support tagging
                return Cache::get($cacheKey);
            }
        } catch (\Exception $e) {
            logErrorIfDebug('Failed to get cached recording data', [
                'livestock_id' => $livestockId,
                'date' => $date->format('Y-m-d'),
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Clear recording cache for specific livestock
     */
    public function clearRecordingCache(string $livestockId): bool
    {
        try {
            // Check if cache store supports tagging
            if ($this->cacheStoreSupportsTags()) {
                Cache::tags(['recording', 'livestock', $livestockId])->flush();
            } else {
                // Fallback: clear all cache (less precise but functional)
                logWarningIfDebug('Cache store does not support tagging, clearing all cache', [
                    'livestock_id' => $livestockId,
                    'cache_driver' => config('cache.default')
                ]);
                Cache::flush();
            }
            return true;
        } catch (\Exception $e) {
            logErrorIfDebug('Failed to clear recording cache', [
                'livestock_id' => $livestockId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Optimize database queries with eager loading
     */
    public function getOptimizedRecordingData(string $livestockId, Carbon $date): ?array
    {
        try {
            $startTime = microtime(true);

            $data = DB::table('recordings')
                ->select([
                    'recordings.*',
                    'livestocks.name as livestock_name',
                    'farms.name as farm_name',
                    'coops.name as coop_name'
                ])
                ->leftJoin('livestocks', 'recordings.livestock_id', '=', 'livestocks.id')
                ->leftJoin('farms', 'livestocks.farm_id', '=', 'farms.id')
                ->leftJoin('coops', 'livestocks.coop_id', '=', 'coops.id')
                ->where('recordings.livestock_id', $livestockId)
                ->whereDate('recordings.date', $date)
                ->first();

            $executionTime = microtime(true) - $startTime;

            // Log performance if exceeds threshold
            if ($executionTime > self::PERFORMANCE_THRESHOLD) {
                $this->logPerformanceIssue('getOptimizedRecordingData', $executionTime, [
                    'livestock_id' => $livestockId,
                    'date' => $date->format('Y-m-d')
                ]);
            }

            return $data ? (array) $data : null;
        } catch (\Exception $e) {
            logErrorIfDebug('Failed to get optimized recording data', [
                'livestock_id' => $livestockId,
                'date' => $date->format('Y-m-d'),
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Batch process recording operations
     */
    public function batchProcessRecordings(array $recordings): array
    {
        $results = [];
        $batches = array_chunk($recordings, self::BATCH_SIZE);

        foreach ($batches as $batchIndex => $batch) {
            try {
                $startTime = microtime(true);

                DB::beginTransaction();

                foreach ($batch as $recording) {
                    $result = $this->processSingleRecording($recording);
                    $results[] = $result;
                }

                DB::commit();

                $executionTime = microtime(true) - $startTime;

                logInfoIfDebug('Batch processing completed', [
                    'batch_index' => $batchIndex,
                    'batch_size' => count($batch),
                    'execution_time' => $executionTime,
                    'success_count' => count(array_filter($results, fn($r) => $r['success']))
                ]);
            } catch (\Exception $e) {
                DB::rollBack();

                logErrorIfDebug('Batch processing failed', [
                    'batch_index' => $batchIndex,
                    'error' => $e->getMessage()
                ]);

                // Mark all records in batch as failed
                foreach ($batch as $recording) {
                    $results[] = [
                        'success' => false,
                        'error' => $e->getMessage(),
                        'recording_id' => $recording['id'] ?? null
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * Process single recording with performance monitoring
     */
    private function processSingleRecording(array $recording): array
    {
        try {
            $startTime = microtime(true);

            // Validate UUID fields
            $validatedData = $this->uuidHelper->validateRecordingDataUuids($recording);

            // Process recording logic here
            $result = [
                'success' => true,
                'recording_id' => $validatedData['id'] ?? null,
                'processed_at' => now()->toISOString()
            ];

            $executionTime = microtime(true) - $startTime;

            // Log performance metrics
            $this->logPerformanceMetrics('processSingleRecording', $executionTime, [
                'recording_id' => $validatedData['id'] ?? null,
                'livestock_id' => $validatedData['livestock_id'] ?? null
            ]);

            return $result;
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'recording_id' => $recording['id'] ?? null
            ];
        }
    }

    /**
     * Generate intelligent cache keys
     */
    private function generateCacheKey(string $type, string $livestockId, Carbon $date): string
    {
        return "recording:{$type}:{$livestockId}:{$date->format('Y-m-d')}";
    }

    /**
     * Log performance issues
     */
    private function logPerformanceIssue(string $operation, float $executionTime, array $context = []): void
    {
        logWarningIfDebug('Performance issue detected', [
            'operation' => $operation,
            'execution_time' => $executionTime,
            'threshold' => self::PERFORMANCE_THRESHOLD,
            'context' => $context,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Log performance metrics
     */
    public function logPerformanceMetrics(string $operation, float $executionTime, array $context = []): void
    {
        // Store in performance logs table
        try {
            // User/Company fallback
            $userId = $context['user_id'] ?? null;
            $companyId = $context['company_id'] ?? null;
            if (\Illuminate\Support\Facades\Auth::check()) {
                $userId = \Illuminate\Support\Facades\Auth::id();
                $companyId = \Illuminate\Support\Facades\Auth::user()->company_id ?? $companyId;
            } else if (isset(\Illuminate\Support\Facades\Auth::user()->id)) {
                $userId = \Illuminate\Support\Facades\Auth::user()->id;
                $companyId = \Illuminate\Support\Facades\Auth::user()->company_id ?? $companyId;
            }
            DB::table('recording_performance_logs')->insert([
                'operation_type' => $operation,
                'livestock_id' => $context['livestock_id'] ?? null,
                'execution_time' => $executionTime,
                'success' => true,
                'service_version' => 'performance_v1.0',
                'metadata' => json_encode($context),
                'user_id' => $userId,
                'company_id' => $companyId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            logErrorIfDebug('Failed to log performance metrics', [
                'error' => $e->getMessage(),
                'operation' => $operation,
                'execution_time' => $executionTime
            ]);
        }
    }

    /**
     * Get performance statistics
     */
    public function getPerformanceStats(Carbon $startDate, Carbon $endDate): array
    {
        try {
            $stats = DB::table('recording_performance_logs')
                ->select([
                    DB::raw('AVG(execution_time) as avg_execution_time'),
                    DB::raw('MAX(execution_time) as max_execution_time'),
                    DB::raw('MIN(execution_time) as min_execution_time'),
                    DB::raw('COUNT(*) as total_operations'),
                    DB::raw('SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as successful_operations'),
                    DB::raw('SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as failed_operations')
                ])
                ->whereBetween('created_at', [$startDate, $endDate])
                ->first();

            return [
                'period' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d')
                ],
                'performance' => [
                    'avg_execution_time' => round($stats->avg_execution_time, 4),
                    'max_execution_time' => round($stats->max_execution_time, 4),
                    'min_execution_time' => round($stats->min_execution_time, 4)
                ],
                'operations' => [
                    'total' => $stats->total_operations,
                    'successful' => $stats->successful_operations,
                    'failed' => $stats->failed_operations,
                    'success_rate' => $stats->total_operations > 0
                        ? round(($stats->successful_operations / $stats->total_operations) * 100, 2)
                        : 0
                ]
            ];
        } catch (\Exception $e) {
            logErrorIfDebug('Failed to get performance stats', [
                'error' => $e->getMessage(),
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d')
            ]);

            return [
                'error' => $e->getMessage(),
                'period' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d')
                ]
            ];
        }
    }

    /**
     * Optimize database indexes for recording operations
     */
    public function optimizeDatabaseIndexes(): bool
    {
        try {
            // Add composite indexes for common queries
            $indexes = [
                'recordings_livestock_date_idx' => ['livestock_id', 'date'],
                'recordings_company_date_idx' => ['company_id', 'date'],
                'recordings_user_date_idx' => ['user_id', 'date'],
                'recordings_created_at_idx' => ['created_at'],
                'recordings_updated_at_idx' => ['updated_at'],
            ];

            foreach ($indexes as $indexName => $columns) {
                try {
                    DB::statement("CREATE INDEX IF NOT EXISTS {$indexName} ON recordings (" . implode(', ', $columns) . ")");
                } catch (\Exception $e) {
                    logWarningIfDebug("Failed to create index {$indexName}", ['error' => $e->getMessage()]);
                }
            }

            return true;
        } catch (\Exception $e) {
            logErrorIfDebug('Failed to optimize database indexes', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get memory usage statistics
     */
    public function getMemoryUsage(): array
    {
        return [
            'memory_usage' => memory_get_usage(true),
            'peak_memory_usage' => memory_get_peak_usage(true),
            'memory_limit' => ini_get('memory_limit'),
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * Clean up old performance logs
     */
    public function cleanupOldPerformanceLogs(int $daysToKeep = 30): int
    {
        try {
            $cutoffDate = now()->subDays($daysToKeep);

            $deletedCount = DB::table('recording_performance_logs')
                ->where('created_at', '<', $cutoffDate)
                ->delete();

            logInfoIfDebug('Cleaned up old performance logs', [
                'deleted_count' => $deletedCount,
                'cutoff_date' => $cutoffDate->format('Y-m-d')
            ]);

            return $deletedCount;
        } catch (\Exception $e) {
            logErrorIfDebug('Failed to cleanup old performance logs', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Check if the current cache store supports tagging
     */
    private function cacheStoreSupportsTags(): bool
    {
        $cacheDriver = config('cache.default');

        // Cache drivers that support tagging
        $taggingSupported = ['redis', 'memcached', 'dynamodb'];

        $supportsTags = in_array($cacheDriver, $taggingSupported);

        // Log cache driver info for debugging
        if (!app()->environment('production') || config('app.debug')) {
            logDebugIfDebug('Cache driver check', [
                'driver' => $cacheDriver,
                'supports_tags' => $supportsTags,
                'supported_drivers' => $taggingSupported
            ]);
        }

        return $supportsTags;
    }
}

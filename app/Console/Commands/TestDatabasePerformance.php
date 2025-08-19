<?php

namespace App\Console\Commands;

use App\Models\DatabasePerformanceLog;
use App\Services\DatabasePerformanceTrackerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;

class TestDatabasePerformance extends Command
{
    protected $signature = 'performance:test-database {--env= : Test with specific environment (enabled/disabled)} {--connection= : Test specific database connection (mysql/pgsql/both)} {--records=1000 : Number of test records} {--batch-size=100 : Batch size for operations} {--cleanup : Clean test data after completion}';
    protected $description = 'Test database performance on database_performance_logs table with different environments and database connections';

    public function handle()
    {
        $this->info('🚀 Testing Database Performance on database_performance_logs table...');
        $this->line('');

        $env = $this->option('env');
        $connection = $this->option('connection');
        $records = (int) $this->option('records');
        $batchSize = (int) $this->option('batch-size');
        $cleanup = $this->option('cleanup');

        // Store original configuration
        $originalEnabled = config('database.performance_tracking.enabled');
        $originalThreshold = config('database.performance_tracking.slow_query_threshold');

        // Test database connections availability
        $connections = $this->getTestConnections($connection);

        if (empty($connections)) {
            $this->error('❌ No valid database connections found!');
            return 1;
        }

        try {
            foreach ($connections as $connectionName) {
                $this->info("🔗 Testing Connection: {$connectionName}");
                $this->line('');

                if ($env === 'enabled') {
                    $this->testWithEnabledEnvironment($records, $batchSize, $connectionName);
                } elseif ($env === 'disabled') {
                    $this->testWithDisabledEnvironment($records, $batchSize, $connectionName);
                } else {
                    // Test both environments
                    $this->testWithEnabledEnvironment($records, $batchSize, $connectionName);
                    $this->line('');
                    $this->testWithDisabledEnvironment($records, $batchSize, $connectionName);
                }

                if (count($connections) > 1) {
                    $this->line('');
                    $this->line('═══════════════════════════════════════════════════════════════');
                    $this->line('');
                }
            }

            if ($cleanup) {
                foreach ($connections as $connectionName) {
                    $this->cleanupTestData($connectionName);
                }
            }
        } finally {
            // Restore original configuration
            Config::set('database.performance_tracking.enabled', $originalEnabled);
            Config::set('database.performance_tracking.slow_query_threshold', $originalThreshold);
        }

        $this->line('');
        $this->info('✅ Database Performance Test Complete!');
        return 0;
    }

    /**
     * Get available database connections for testing
     */
    private function getTestConnections(?string $connection): array
    {
        $availableConnections = [];

        // Test MySQL connection
        if ($connection === 'mysql' || $connection === 'both' || $connection === null) {
            if ($this->testConnectionAvailability('mysql')) {
                $availableConnections[] = 'mysql';
            } else {
                $this->warn('⚠️ MySQL connection not available or not configured');
            }
        }

        // Test PostgreSQL connection  
        if ($connection === 'pgsql' || $connection === 'both' || $connection === null) {
            if ($this->testConnectionAvailability('pgsql')) {
                $availableConnections[] = 'pgsql';
            } else {
                $this->warn('⚠️ PostgreSQL connection not available or not configured');
            }
        }

        // If specific connection requested but not available (except 'both')
        if ($connection && $connection !== 'both' && !in_array($connection, $availableConnections)) {
            $this->error("❌ Requested connection '{$connection}' is not available!");
            return [];
        }

        return $availableConnections;
    }

    /**
     * Test if a database connection is available
     */
    private function testConnectionAvailability(string $connectionName): bool
    {
        try {
            $connection = DB::connection($connectionName);
            $connection->getPdo();

            // Check if the database_performance_logs table exists
            $tableName = 'database_performance_logs';
            $exists = $connection->getSchemaBuilder()->hasTable($tableName);

            if (!$exists) {
                $this->warn("⚠️ Table '{$tableName}' does not exist on {$connectionName} connection");
                return false;
            }

            return true;
        } catch (\Exception $e) {
            $this->warn("⚠️ Connection '{$connectionName}' failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get database engine name for display
     */
    private function getDatabaseEngine(string $connectionName): string
    {
        $engines = [
            'mysql' => 'MySQL',
            'pgsql' => 'PostgreSQL',
            'sqlite' => 'SQLite',
            'sqlsrv' => 'SQL Server'
        ];

        return $engines[$connectionName] ?? strtoupper($connectionName);
    }

    private function testWithEnabledEnvironment(int $records, int $batchSize, string $connectionName = 'mysql'): void
    {
        $engine = $this->getDatabaseEngine($connectionName);
        $this->info("📊 Testing with PERFORMANCE TRACKING ENABLED ({$engine})");
        $this->line('   Environment: DB_PERFORMANCE_TRACKING=true');
        $this->line('   Database: ' . $engine);
        $this->line('   Connection: ' . $connectionName);
        $this->line('   Slow Query Threshold: 100ms');

        // Configure environment
        Config::set('database.performance_tracking.enabled', true);
        Config::set('database.performance_tracking.slow_query_threshold', 100);

        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        // Test 1: Bulk Insert Performance
        $this->line('');
        $this->info('   Test 1: Bulk Insert Performance');
        $insertStartTime = microtime(true);
        $this->performBulkInsert($records, $batchSize, $connectionName);
        $insertTime = (microtime(true) - $insertStartTime) * 1000;
        $this->info('   ✓ Inserted ' . $records . ' records in ' . number_format($insertTime, 2) . 'ms');

        // Test 2: Query Performance
        $this->line('');
        $this->info('   Test 2: Query Performance');
        $this->performQueryTests($connectionName);

        // Test 3: Aggregation Performance
        $this->line('');
        $this->info('   Test 3: Aggregation Performance');
        $this->performAggregationTests($connectionName);

        // Test 4: Update Performance
        $this->line('');
        $this->info('   Test 4: Update Performance');
        $this->performUpdateTests($batchSize, $connectionName);

        $totalTime = (microtime(true) - $startTime) * 1000;
        $memoryUsed = (memory_get_usage(true) - $startMemory) / 1024 / 1024;

        $this->line('');
        $this->info('   📈 ENABLED Environment Results:');
        $this->info('   Total execution time: ' . number_format($totalTime, 2) . 'ms');
        $this->info('   Memory used: ' . number_format($memoryUsed, 2) . 'MB');
        $this->info('   Records in table: ' . DatabasePerformanceLog::on($connectionName)->count());
    }

    private function testWithDisabledEnvironment(int $records, int $batchSize, string $connectionName = 'mysql'): void
    {
        $engine = $this->getDatabaseEngine($connectionName);
        $this->info("📊 Testing with PERFORMANCE TRACKING DISABLED ({$engine})");
        $this->line('   Environment: DB_PERFORMANCE_TRACKING=false');
        $this->line('   Database: ' . $engine);
        $this->line('   Connection: ' . $connectionName);

        // Configure environment
        Config::set('database.performance_tracking.enabled', false);

        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        // Test 1: Bulk Insert Performance (simulated without tracking)
        $this->line('');
        $this->info('   Test 1: Bulk Insert Performance (No Tracking)');
        $insertStartTime = microtime(true);
        $this->performBulkInsertWithoutTracking($records, $batchSize);
        $insertTime = (microtime(true) - $insertStartTime) * 1000;
        $this->info('   ✓ Simulated ' . $records . ' operations in ' . number_format($insertTime, 2) . 'ms');

        // Test 2: Direct Database Operations
        $this->line('');
        $this->info('   Test 2: Direct Database Operations');
        $this->performDirectDatabaseTests($connectionName);

        $totalTime = (microtime(true) - $startTime) * 1000;
        $memoryUsed = (memory_get_usage(true) - $startMemory) / 1024 / 1024;

        $this->line('');
        $this->info('   📈 DISABLED Environment Results:');
        $this->info('   Total execution time: ' . number_format($totalTime, 2) . 'ms');
        $this->info('   Memory used: ' . number_format($memoryUsed, 2) . 'MB');
        $this->info('   Performance tracking overhead avoided');
    }

    private function performBulkInsert(int $records, int $batchSize, string $connectionName = 'mysql'): void
    {
        $batches = ceil($records / $batchSize);
        $data = [];

        // Get existing company ID or use null
        $companyId = DB::connection($connectionName)->table('companies')->first()?->id;

        for ($i = 0; $i < $records; $i++) {
            $data[] = [
                'id' => \Illuminate\Support\Str::uuid(),
                'operation_type' => 'test_create',
                'model_class' => 'App\\Models\\TestModel',
                'model_id' => \Illuminate\Support\Str::uuid(),
                'table_name' => 'test_models',
                'records_count' => 1,
                'execution_time_ms' => rand(50, 500),
                'memory_usage_mb' => rand(1, 10),
                'query_count' => rand(1, 5),
                'slow_query_count' => rand(0, 2),
                'status' => rand(0, 1) ? 'success' : 'slow',
                'error_message' => null,
                'user_id' => null,
                'company_id' => $companyId,
                'request_id' => \Illuminate\Support\Str::uuid(),
                'session_id' => 'artisan-test-' . time(),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'PHP Artisan Test',
                'additional_data' => json_encode(['test' => true, 'batch' => floor($i / $batchSize)]),
                'created_at' => Carbon::now()->subMinutes(rand(0, 1440)),
                'updated_at' => Carbon::now(),
            ];

            if (count($data) >= $batchSize) {
                DB::connection($connectionName)->table('database_performance_logs')->insert($data);
                $data = [];
                $batchNum = floor($i / $batchSize) + 1;
                $this->line('   Inserted batch ' . $batchNum . '/' . $batches);
            }
        }

        if (!empty($data)) {
            DB::connection($connectionName)->table('database_performance_logs')->insert($data);
            $this->line('   Inserted final batch');
        }
    }

    private function performBulkInsertWithoutTracking(int $records, int $batchSize): void
    {
        // Simulate operations without actually writing to performance logs
        for ($i = 0; $i < $records; $i += $batchSize) {
            // Simulate some work
            usleep(1000); // 1ms delay to simulate processing

            if (($i + $batchSize) % 500 === 0) {
                $progress = min($i + $batchSize, $records);
                $this->line('   Processed ' . $progress . '/' . $records . ' operations');
            }
        }
    }

    private function performQueryTests(string $connectionName = 'mysql'): void
    {
        $tests = [
            'Recent logs (last hour)' => function () use ($connectionName) {
                return DatabasePerformanceLog::on($connectionName)->where('created_at', '>=', Carbon::now()->subHour())->count();
            },
            'Slow operations' => function () use ($connectionName) {
                return DatabasePerformanceLog::on($connectionName)->where('status', 'slow')->count();
            },
            'By model class' => function () use ($connectionName) {
                return DatabasePerformanceLog::on($connectionName)->where('model_class', 'App\\Models\\TestModel')->count();
            },
            'Complex aggregation' => function () use ($connectionName) {
                return DatabasePerformanceLog::on($connectionName)->select('model_class')
                    ->selectRaw('COUNT(*) as count, AVG(execution_time_ms) as avg_time')
                    ->groupBy('model_class')
                    ->get();
            }
        ];

        foreach ($tests as $testName => $testFunction) {
            $startTime = microtime(true);
            $result = $testFunction();
            $queryTime = (microtime(true) - $startTime) * 1000;

            $resultCount = is_countable($result) ? count($result) : (is_numeric($result) ? $result : 'N/A');
            $this->line('   ✓ ' . $testName . ': ' . $resultCount . ' results in ' . number_format($queryTime, 2) . 'ms');
        }
    }

    private function performAggregationTests(string $connectionName = 'mysql'): void
    {
        $tests = [
            'Model statistics' => function () {
                return DatabasePerformanceLog::getModelStats('App\\Models\\TestModel');
            },
            'Performance trends' => function () {
                return DatabasePerformanceLog::getPerformanceTrends(7);
            },
            'Slowest operations' => function () {
                return DatabasePerformanceLog::getSlowestOperations(10);
            },
            'Summary stats' => function () {
                return DatabasePerformanceLog::getSummaryStats();
            }
        ];

        foreach ($tests as $testName => $testFunction) {
            $startTime = microtime(true);
            $result = $testFunction();
            $queryTime = (microtime(true) - $startTime) * 1000;

            $resultCount = is_countable($result) ? count($result) : 'complex';
            $this->line('   ✓ ' . $testName . ': ' . $resultCount . ' in ' . number_format($queryTime, 2) . 'ms');
        }
    }

    private function performUpdateTests(int $batchSize, string $connectionName = 'mysql'): void
    {
        $startTime = microtime(true);

        // Update test records in batches
        $testRecords = DatabasePerformanceLog::on($connectionName)->where('model_class', 'App\\Models\\TestModel')
            ->limit($batchSize)
            ->get();

        foreach ($testRecords as $record) {
            $record->update([
                'status' => $record->status === 'success' ? 'slow' : 'success',
                'execution_time_ms' => $record->execution_time_ms + rand(1, 50)
            ]);
        }

        $updateTime = (microtime(true) - $startTime) * 1000;
        $this->line('   ✓ Updated ' . $testRecords->count() . ' records in ' . number_format($updateTime, 2) . 'ms');
    }

    private function performDirectDatabaseTests(string $connectionName = 'mysql'): void
    {
        $tests = [
            'Raw count query' => function () use ($connectionName) {
                return DB::connection($connectionName)->select('SELECT COUNT(*) as count FROM database_performance_logs')[0]->count;
            },
            'Raw aggregation' => function () use ($connectionName) {
                return DB::connection($connectionName)->select('SELECT AVG(execution_time_ms) as avg_time FROM database_performance_logs')[0]->avg_time;
            },
            'Index usage test' => function () use ($connectionName) {
                return DB::connection($connectionName)->select('SELECT model_class, COUNT(*) as count FROM database_performance_logs GROUP BY model_class LIMIT 5');
            }
        ];

        foreach ($tests as $testName => $testFunction) {
            $startTime = microtime(true);
            $result = $testFunction();
            $queryTime = (microtime(true) - $startTime) * 1000;

            $resultInfo = is_array($result) ? count($result) . ' rows' : $result;
            $this->line('   ✓ ' . $testName . ': ' . $resultInfo . ' in ' . number_format($queryTime, 2) . 'ms');
        }
    }

    private function cleanupTestData(string $connectionName = 'mysql'): void
    {
        $this->line('');
        $engine = $this->getDatabaseEngine($connectionName);
        $this->info("🧹 Cleaning up test data from {$engine}...");

        $deletedCount = DatabasePerformanceLog::on($connectionName)->where('model_class', 'App\\Models\\TestModel')->delete();
        $this->info('   ✓ Deleted ' . $deletedCount . ' test records from ' . $connectionName);
    }
}

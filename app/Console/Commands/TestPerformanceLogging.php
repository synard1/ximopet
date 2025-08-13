<?php

namespace App\Console\Commands;

use App\Models\DatabasePerformanceLog;
use App\Services\DatabasePerformanceTrackerService;
use App\Models\LivestockPurchase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestPerformanceLogging extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'performance:test {--model= : Specific model to test} {--operation= : Specific operation to test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the performance logging system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Testing Performance Logging System...\n');

        // Test 1: Check if service exists
        $this->info('1. Testing DatabasePerformanceTrackerService...');
        try {
            $tracker = app(DatabasePerformanceTrackerService::class);
            $this->info('✅ Service loaded successfully');
        } catch (\Exception $e) {
            $this->error('❌ Service failed to load: ' . $e->getMessage());
            return 1;
        }

        // Test 2: Check if model exists
        $this->info('2. Testing DatabasePerformanceLog model...');
        try {
            $count = DatabasePerformanceLog::count();
            $this->info("✅ Model loaded successfully, current records: {$count}");
        } catch (\Exception $e) {
            $this->error('❌ Model failed to load: ' . $e->getMessage());
            return 1;
        }

        // Test 3: Test manual tracking
        $this->info('3. Testing manual performance tracking...');
        try {
            $startTime = microtime(true);

            // Simulate some work
            usleep(100000); // 100ms

            $executionTime = (microtime(true) - $startTime) * 1000;

            $tracker->startTracking();
            usleep(50000); // 50ms
            $log = $tracker->stopTracking(
                'test_operation',
                'TestModel',
                'test-id',
                1
            );

            if ($log) {
                $this->info("✅ Manual tracking successful, log ID: {$log->id}");
            } else {
                $this->warn('⚠️ Manual tracking returned null');
            }
        } catch (\Exception $e) {
            $this->error('❌ Manual tracking failed: ' . $e->getMessage());
        }

        // Test 4: Test model creation tracking
        $this->info('4. Testing model creation tracking...');
        try {
            // Create a test model to trigger tracking
            $testModel = new LivestockPurchase();
            $testModel->fill([
                'invoice_number' => 'TEST-' . time(),
                'tanggal' => now(),
                'status' => 'draft',
                'company_id' => 'test-company-id',
                'created_by' => 'test-user-id',
                'updated_by' => 'test-user-id',
            ]);

            // This should trigger the tracking
            $testModel->save();

            $this->info('✅ Model creation tracking test completed');

            // Clean up test data
            $testModel->delete();
        } catch (\Exception $e) {
            $this->warn('⚠️ Model creation tracking test failed: ' . $e->getMessage());
        }

        // Test 5: Check log files
        $this->info('5. Testing log channels...');
        try {
            $logFiles = [
                'performance' => storage_path('logs/performance.log'),
                'database_performance' => storage_path('logs/database-performance.log'),
                'monitoring' => storage_path('logs/monitoring.log'),
            ];

            foreach ($logFiles as $channel => $path) {
                if (file_exists($path)) {
                    $size = filesize($path);
                    $this->info("✅ {$channel} log exists, size: " . number_format($size) . " bytes");
                } else {
                    $this->warn("⚠️ {$channel} log not found");
                }
            }
        } catch (\Exception $e) {
            $this->error('❌ Log channel test failed: ' . $e->getMessage());
        }

        // Test 6: Check configuration
        $this->info('6. Testing configuration...');
        try {
            $config = config('database.performance_tracking');
            if ($config) {
                $this->info('✅ Configuration loaded');
                $this->table(
                    ['Setting', 'Value'],
                    [
                        ['Enabled', $config['enabled'] ? 'Yes' : 'No'],
                        ['Slow Query Threshold', $config['slow_query_threshold'] . 'ms'],
                        ['Track in Testing', $config['track_in_testing'] ? 'Yes' : 'No'],
                        ['Cleanup Enabled', $config['cleanup']['enabled'] ? 'Yes' : 'No'],
                        ['Days to Keep', $config['cleanup']['days_to_keep'] . ' days'],
                    ]
                );
            } else {
                $this->warn('⚠️ Configuration not found');
            }
        } catch (\Exception $e) {
            $this->error('❌ Configuration test failed: ' . $e->getMessage());
        }

        // Test 7: Check recent performance logs
        $this->info('7. Checking recent performance logs...');
        try {
            $recentLogs = DatabasePerformanceLog::orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            if ($recentLogs->count() > 0) {
                $this->info('✅ Found ' . $recentLogs->count() . ' recent logs');
                $this->table(
                    ['Time', 'Operation', 'Model', 'Status', 'Execution Time'],
                    $recentLogs->map(function ($log) {
                        return [
                            $log->created_at->format('H:i:s'),
                            $log->operation_type,
                            class_basename($log->model_class),
                            $log->status,
                            $log->execution_time_ms . 'ms'
                        ];
                    })
                );
            } else {
                $this->warn('⚠️ No recent performance logs found');
            }
        } catch (\Exception $e) {
            $this->error('❌ Recent logs check failed: ' . $e->getMessage());
        }

        $this->info('\n🎉 Performance Logging System Test Completed!');

        if (config('database.performance_tracking.enabled')) {
            $this->info('✅ System is enabled and ready');
        } else {
            $this->warn('⚠️ System is disabled - check configuration');
        }

        return 0;
    }
}

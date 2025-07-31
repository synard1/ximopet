<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

use App\Jobs\CalculateLivestockBatchWeightJob;
use App\Models\Livestock;
use App\Models\LivestockBatch;

class CalculateLivestockBatchWeightCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'livestock:calculate-batch-weight 
                            {--livestock-id= : Specific livestock ID to calculate}
                            {--batch-id= : Specific batch ID to calculate}
                            {--start-date= : Start date for calculation (Y-m-d)}
                            {--end-date= : End date for calculation (Y-m-d)}
                            {--user-id= : User ID who triggered the calculation}
                            {--queue=default : Queue name to dispatch job}
                            {--sync : Run synchronously instead of dispatching job}
                            {--estimate : Show time estimate without running}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate and update livestock batch weights based on historical recording data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startTime = microtime(true);

        $this->info('🚀 Starting Livestock Batch Weight Calculation');
        $this->info('==============================================');

        try {
            // Parse options
            $livestockId = $this->option('livestock-id');
            $batchId = $this->option('batch-id');
            $startDate = $this->option('start-date');
            $endDate = $this->option('end-date');
            $userId = $this->option('user-id');
            $queue = $this->option('queue');
            $sync = $this->option('sync');
            $estimate = $this->option('estimate');

            // Validate date format if provided
            if ($startDate && !$this->isValidDate($startDate)) {
                $this->error('❌ Invalid start date format. Use Y-m-d format (e.g., 2025-01-01)');
                return 1;
            }

            if ($endDate && !$this->isValidDate($endDate)) {
                $this->error('❌ Invalid end date format. Use Y-m-d format (e.g., 2025-12-31)');
                return 1;
            }

            // Determine scope and show information
            $scope = $this->determineScope($livestockId, $batchId, $startDate, $endDate);

            $this->info('📊 Calculation Scope:');
            $this->info("   Type: {$scope['type']}");
            $this->info("   Total Livestock: {$scope['total_livestock_count']}");
            $this->info("   Total Batches: {$scope['total_batch_count']}");
            $this->info("   Date Range: {$scope['date_range']['start']} to {$scope['date_range']['end']}");

            if ($estimate) {
                $this->showTimeEstimate($scope);
                return 0;
            }

            // Show confirmation for global calculation
            if ($scope['type'] === 'global' && !$sync) {
                if (!$this->confirm('⚠️  This will calculate weights for ALL livestock batches. Continue?')) {
                    $this->info('❌ Operation cancelled by user');
                    return 0;
                }
            }

            // Create job
            $job = new CalculateLivestockBatchWeightJob(
                $livestockId,
                $batchId,
                $startDate,
                $endDate,
                $userId
            );

            if ($sync) {
                $this->info('🔄 Running calculation synchronously...');
                $job->handle(app(\App\Services\Livestock\BatchWeightCalculationService::class));
                $this->info('✅ Calculation completed synchronously');
            } else {
                $this->info("🔄 Dispatching job to queue: {$queue}");
                $job->onQueue($queue);
                dispatch($job);

                $this->info('✅ Job dispatched successfully');
                $this->info('📋 Job Details:');
                $this->info("   Queue: {$queue}");
                $this->info("   Scope: {$scope['type']}");
                $this->info("   Batches: {$scope['total_batch_count']}");
                $this->info('');
                $this->info('💡 Monitor progress with:');
                $this->info("   tail -f storage/logs/bgjob.log");
                $this->info("   php artisan queue:work --queue={$queue}");
            }

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;

            $this->info('');
            $this->info("⏱️  Command execution time: " . round($executionTime, 2) . " seconds");

            return 0;
        } catch (\Exception $e) {
            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;

            $this->error('❌ Command failed: ' . $e->getMessage());
            $this->error('Execution time: ' . round($executionTime, 2) . ' seconds');

            Log::channel('bgjob')->error('CalculateLivestockBatchWeightCommand failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'execution_time' => $executionTime
            ]);

            return 1;
        }
    }

    /**
     * Determine calculation scope based on provided parameters
     */
    private function determineScope(?string $livestockId, ?string $batchId, ?string $startDate, ?string $endDate): array
    {
        if ($batchId) {
            $batch = LivestockBatch::find($batchId);
            if (!$batch) {
                throw new \Exception("Batch not found: {$batchId}");
            }

            return [
                'type' => 'single_batch',
                'total_livestock_count' => 1,
                'total_batch_count' => 1,
                'date_range' => [
                    'start' => $startDate ?? $batch->created_at->format('Y-m-d'),
                    'end' => $endDate ?? Carbon::now()->format('Y-m-d')
                ]
            ];
        }

        if ($livestockId) {
            $livestock = Livestock::find($livestockId);
            if (!$livestock) {
                throw new \Exception("Livestock not found: {$livestockId}");
            }

            $batches = $livestock->batches;
            return [
                'type' => 'single_livestock',
                'total_livestock_count' => 1,
                'total_batch_count' => $batches->count(),
                'date_range' => [
                    'start' => $startDate ?? $livestock->start_date,
                    'end' => $endDate ?? Carbon::now()->format('Y-m-d')
                ]
            ];
        }

        // Global calculation
        $livestockQuery = Livestock::query();
        if ($startDate) {
            $livestockQuery->where('start_date', '>=', $startDate);
        }
        if ($endDate) {
            $livestockQuery->where('start_date', '<=', $endDate);
        }

        $livestock = $livestockQuery->get();
        $totalBatches = $livestock->sum(function ($l) {
            return $l->batches->count();
        });

        return [
            'type' => 'global',
            'total_livestock_count' => $livestock->count(),
            'total_batch_count' => $totalBatches,
            'date_range' => [
                'start' => $startDate ?? '2020-01-01',
                'end' => $endDate ?? Carbon::now()->format('Y-m-d')
            ]
        ];
    }

    /**
     * Show time estimate for the calculation
     */
    private function showTimeEstimate(array $scope): void
    {
        $this->info('⏱️  Time Estimate:');

        // Base time per batch (in seconds)
        $baseTimePerBatch = 2; // 2 seconds per batch for basic calculation
        $estimatedSeconds = $scope['total_batch_count'] * $baseTimePerBatch;

        if ($scope['type'] === 'global') {
            // Add overhead for global calculations
            $estimatedSeconds += 30; // 30 seconds overhead
        }

        $estimatedMinutes = ceil($estimatedSeconds / 60);
        $estimatedHours = floor($estimatedMinutes / 60);
        $remainingMinutes = $estimatedMinutes % 60;

        if ($estimatedHours > 0) {
            $this->info("   Estimated time: {$estimatedHours}h {$remainingMinutes}m");
        } else {
            $this->info("   Estimated time: {$estimatedMinutes}m");
        }

        $this->info("   Batches to process: {$scope['total_batch_count']}");
        $this->info("   Average time per batch: {$baseTimePerBatch}s");

        if ($scope['type'] === 'global') {
            $this->warn('   ⚠️  Global calculation may take significant time');
            $this->warn('   💡 Consider using --sync for small datasets');
        }
    }

    /**
     * Validate date format
     */
    private function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}

<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\SupplyUsage;
use App\Services\SupplyUsageStockService;

class UpdateSupplyUsageStockJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;
    public $backoff = [10, 30, 60]; // Retry delays in seconds

    protected $usageId;
    protected $previousStatus;
    protected $newStatus;
    protected $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $usageId, string $previousStatus, string $newStatus, string $userId = null)
    {
        $this->usageId = $usageId;
        $this->previousStatus = $previousStatus;
        $this->newStatus = $newStatus;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(SupplyUsageStockService $stockService): void
    {
        try {
            Log::info('UpdateSupplyUsageStockJob: Starting job', [
                'usage_id' => $this->usageId,
                'previous_status' => $this->previousStatus,
                'new_status' => $this->newStatus,
                'user_id' => $this->userId
            ]);

            // Find the usage record
            $usage = SupplyUsage::with('details')->find($this->usageId);

            if (!$usage) {
                Log::error('UpdateSupplyUsageStockJob: Usage not found', [
                    'usage_id' => $this->usageId
                ]);
                return;
            }

            // Update stock using service
            $result = $stockService->updateStockForStatusChange($usage, $this->previousStatus, $this->newStatus);

            if ($result['success']) {
                Log::info('UpdateSupplyUsageStockJob: Job completed successfully', [
                    'usage_id' => $this->usageId,
                    'stock_actions_count' => count($result['stock_actions']),
                    'current_supply_actions_count' => count($result['current_supply_actions'])
                ]);
            } else {
                Log::error('UpdateSupplyUsageStockJob: Job failed', [
                    'usage_id' => $this->usageId,
                    'error' => $result['error'] ?? 'Unknown error'
                ]);

                // Re-throw exception to trigger retry
                throw new \Exception($result['error'] ?? 'Stock update failed');
            }
        } catch (\Exception $e) {
            Log::error('UpdateSupplyUsageStockJob: Exception occurred', [
                'usage_id' => $this->usageId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('UpdateSupplyUsageStockJob: Job failed permanently', [
            'usage_id' => $this->usageId,
            'previous_status' => $this->previousStatus,
            'new_status' => $this->newStatus,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Here you could send notifications, create alerts, etc.
        // For example, notify administrators about failed stock updates
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'supply-usage',
            'stock-update',
            'usage-id:' . $this->usageId,
            'status:' . $this->newStatus
        ];
    }
}

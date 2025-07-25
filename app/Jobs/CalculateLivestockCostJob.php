<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Services\Livestock\LivestockCostService;

class CalculateLivestockCostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;
    public $backoff = [10, 30, 60];

    protected $livestockId;
    protected $batchId;
    protected $date;

    /**
     * Create a new job instance.
     * @param string $livestockId
     * @param int|null $batchId
     * @param string|null $date (Y-m-d)
     */
    public function __construct(string $livestockId, ?int $batchId = null, ?string $date = null)
    {
        $this->livestockId = $livestockId;
        $this->batchId = $batchId;
        $this->date = $date;
    }

    /**
     * Execute the job.
     */
    public function handle(LivestockCostService $costService): void
    {
        Log::info('CalculateLivestockCostJob: Start', [
            'livestock_id' => $this->livestockId,
            'batch_id' => $this->batchId,
            'date' => $this->date
        ]);

        try {
            if ($this->date) {
                // Kalkulasi biaya untuk tanggal tertentu
                $result = $costService->calculateForDate(
                    $this->livestockId,
                    $this->date,
                    false // async = false agar tidak dispatch job lagi
                );
                Log::info('CalculateLivestockCostJob: Success (calculateForDate)', [
                    'livestock_id' => $this->livestockId,
                    'date' => $this->date,
                    'result_id' => $result->id ?? null
                ]);
            } elseif ($this->batchId) {
                // Kalkulasi ulang untuk seluruh range batch (jika batchId diberikan)
                $costService->recalculateRange($this->livestockId, null, null);
                Log::info('CalculateLivestockCostJob: Success (recalculateRange)', [
                    'livestock_id' => $this->livestockId,
                    'batch_id' => $this->batchId
                ]);
            } else {
                // Default: kalkulasi hari ini
                $result = $costService->calculateForDate(
                    $this->livestockId,
                    now()->toDateString(),
                    false // async = false agar tidak dispatch job lagi
                );
                Log::info('CalculateLivestockCostJob: Success (default today)', [
                    'livestock_id' => $this->livestockId,
                    'date' => now()->toDateString(),
                    'result_id' => $result->id ?? null
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('CalculateLivestockCostJob: Error', [
                'livestock_id' => $this->livestockId,
                'batch_id' => $this->batchId,
                'date' => $this->date,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Recording;

use App\Models\RecordingSale;
use App\Models\LivestockBatch;
use App\Services\Recording\Contracts\RecordingSaleServiceInterface;
use App\Services\Recording\DTOs\ServiceResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Exception;
use Illuminate\Support\Carbon;

class RecordingSaleService implements RecordingSaleServiceInterface
{
    /**
     * Helper: Allocate sale quantity to batches (FIFO)
     * Returns: [breakdown, batch_id]
     */
    private function allocateSaleToBatches(string $livestockId, float $quantity, float $weight = null, float $price = null): array
    {
        $batches = LivestockBatch::where('livestock_id', $livestockId)
            ->where('quantity_available', '>', 0)
            ->orderBy('start_date')
            ->lockForUpdate()
            ->get();
        $remaining = $quantity;
        $breakdown = [];
        $mainBatchId = null;
        foreach ($batches as $batch) {
            if ($remaining <= 0) break;
            $qty = min($batch->quantity_available, $remaining);
            if ($mainBatchId === null) $mainBatchId = $batch->id;
            $avgWeight = $batch->weight_per_unit ?? null;
            $breakdown[] = [
                'batch_id' => $batch->id,
                'quantity' => $qty,
                'weight' => $avgWeight ? round($qty * $avgWeight, 2) : null,
                'price_per_unit' => $price,
            ];
            $batch->quantity_available -= $qty;
            $batch->save();
            $remaining -= $qty;
        }
        if ($remaining > 0) {
            throw new \Exception('Stok batch tidak cukup untuk penjualan.');
        }
        return [$breakdown, $mainBatchId];
    }

    public function create(array $data): ServiceResult
    {
        DB::beginTransaction();
        try {
            // Validasi field utama
            $required = ['livestock_id', 'date', 'quantity', 'price'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return ServiceResult::error("Field '$field' is required.");
                }
            }
            // Alokasi batch FIFO
            [$breakdown, $mainBatchId] = $this->allocateSaleToBatches($data['livestock_id'], $data['quantity'], $data['weight'] ?? null, $data['price']);
            // Simpan ke recording_sales
            $sale = new RecordingSale();
            $sale->livestock_id = $data['livestock_id'];
            $sale->livestock_batch_id = $mainBatchId;
            $sale->date = $data['date'];
            $sale->quantity = $data['quantity'];
            $sale->weight = $data['weight'] ?? null;
            $sale->price = $data['price'];
            $sale->status = $data['status'] ?? 'draft';
            $sale->data = [
                'batch_breakdown' => $breakdown,
                'customer_id' => $data['customer_id'] ?? null,
                'notes' => $data['notes'] ?? null,
            ];
            $sale->save();
            DB::commit();
            return ServiceResult::success('Recording sale created.', $sale->toArray());
        } catch (\Exception $e) {
            DB::rollBack();
            return ServiceResult::error('Failed to create recording sale: ' . $e->getMessage());
        }
    }

    public function update(string $id, array $data): ServiceResult
    {
        DB::beginTransaction();
        try {
            $sale = RecordingSale::findOrFail($id);
            // Rollback previous batch allocation if needed (not implemented here)
            // Alokasi batch FIFO baru
            [$breakdown, $mainBatchId] = $this->allocateSaleToBatches($data['livestock_id'], $data['quantity'], $data['weight'] ?? null, $data['price']);
            $sale->livestock_id = $data['livestock_id'];
            $sale->livestock_batch_id = $mainBatchId;
            $sale->date = $data['date'];
            $sale->quantity = $data['quantity'];
            $sale->weight = $data['weight'] ?? null;
            $sale->price = $data['price'];
            $sale->status = $data['status'] ?? $sale->status;
            $sale->data = array_merge($sale->data ?? [], [
                'batch_breakdown' => $breakdown,
                'customer_id' => $data['customer_id'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
            $sale->save();
            DB::commit();
            return ServiceResult::success('Recording sale updated.', $sale->toArray());
        } catch (\Exception $e) {
            DB::rollBack();
            return ServiceResult::error('Failed to update recording sale: ' . $e->getMessage());
        }
    }

    public function delete(string $id): ServiceResult
    {
        try {
            $sale = RecordingSale::findOrFail($id);
            $sale->delete();
            Log::info('[RecordingSaleService] Deleted recording sale', ['id' => $id]);
            return ServiceResult::success('Recording sale deleted.');
        } catch (Exception $e) {
            Log::error('[RecordingSaleService] Failed to delete recording sale', ['id' => $id, 'error' => $e->getMessage()]);
            return ServiceResult::error('Failed to delete recording sale: ' . $e->getMessage());
        }
    }

    public function getById(string $id): ServiceResult
    {
        try {
            $sale = RecordingSale::findOrFail($id);
            return ServiceResult::success('Recording sale found.', $sale->toArray());
        } catch (Exception $e) {
            return ServiceResult::error('Recording sale not found: ' . $e->getMessage());
        }
    }

    public function listByLivestockAndDate(string $livestockId, string $date, array $filters = []): ServiceResult
    {
        try {
            $query = RecordingSale::where('livestock_id', $livestockId)
                ->whereDate('date', $date);
            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            $sales = $query->get();
            return ServiceResult::success('Recording sales listed.', $sales->all());
        } catch (Exception $e) {
            return ServiceResult::error('Failed to list recording sales: ' . $e->getMessage());
        }
    }

    public function listByCompanyAndPeriod(string $companyId, string $startDate, string $endDate, array $filters = []): ServiceResult
    {
        try {
            $query = RecordingSale::where('company_id', $companyId)
                ->whereBetween('date', [$startDate, $endDate]);
            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            if (isset($filters['livestock_id'])) {
                $query->where('livestock_id', $filters['livestock_id']);
            }
            $sales = $query->get();
            return ServiceResult::success('Recording sales listed.', $sales->all());
        } catch (Exception $e) {
            return ServiceResult::error('Failed to list recording sales: ' . $e->getMessage());
        }
    }

    public function validate(array $data): ServiceResult
    {
        $required = ['livestock_id', 'date', 'quantity', 'price'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ServiceResult::error("Field '$field' is required.");
            }
        }
        if (!is_numeric($data['quantity']) || $data['quantity'] < 0) {
            return ServiceResult::error('Quantity must be a non-negative number.');
        }
        // Validasi batch breakdown jika ada
        if (isset($data['batch_breakdown']) && is_array($data['batch_breakdown'])) {
            $total = 0;
            foreach ($data['batch_breakdown'] as $row) {
                if (empty($row['batch_id']) || !is_numeric($row['quantity']) || $row['quantity'] <= 0) {
                    return ServiceResult::error('Invalid batch breakdown data.');
                }
                $total += $row['quantity'];
            }
            if ($total != $data['quantity']) {
                return ServiceResult::error('Batch breakdown total does not match sale quantity.');
            }
        }
        return ServiceResult::success('Validation passed.');
    }

    public function finalize(string $id): ServiceResult
    {
        try {
            $sale = RecordingSale::findOrFail($id);
            if ($sale->status === 'finalized') {
                return ServiceResult::success('Recording sale already finalized.', $sale->toArray());
            }
            // Example: mark as finalized, or move to valid sales table
            $sale->status = 'finalized';
            $sale->save();
            Log::info('[RecordingSaleService] Finalized recording sale', ['id' => $id]);
            // Optionally, trigger event/job to move to LivestockSales
            return ServiceResult::success('Recording sale finalized.', $sale->toArray());
        } catch (Exception $e) {
            Log::error('[RecordingSaleService] Failed to finalize recording sale', ['id' => $id, 'error' => $e->getMessage()]);
            return ServiceResult::error('Failed to finalize recording sale: ' . $e->getMessage());
        }
    }
}

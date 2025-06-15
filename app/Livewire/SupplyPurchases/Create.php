<?php

namespace App\Livewire\SupplyPurchases;

use App\Services\AuditTrailService;
use App\Events\SupplyPurchaseStatusChanged;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

use App\Models\CurrentSupply;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Ekspedisi;
use  App\Models\Expedition;
use App\Models\Farm;
use App\Models\Supply;
use App\Models\SupplyPurchaseBatch;
use App\Models\SupplyPurchase;
use App\Models\SupplyStock;
use App\Models\Rekanan;
use App\Models\Partner;
use App\Models\Item;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class Create extends Component
{
    use WithFileUploads;

    public $livestockId;
    public $invoice_number;
    public $date;
    public $supplier_id;
    public $expedition_id;
    public $expedition_fee = 0;
    public $items = [];
    public $livestock_id;
    public $farm_id;
    public $pembelianId; // To hold the ID when editing
    public $showForm = false;
    public $edit_mode = false;
    public $errorItems = [];

    public bool $withHistory = false; // ← Tambahkan ini di atas class Livewire

    // protected $listeners = [
    //     'deleteSupplyPurchaseBatch' => 'deleteSupplyPurchaseBatch',
    //     'updateDoNumber' => 'updateDoNumber',
    //     'showEditForm' => 'showEditForm',
    //     'showCreateForm' => 'showCreateForm',
    //     'cancel' => 'cancel',
    //     'updateStatusSupplyPurchase' => 'updateStatusSupplyPurchase',
    //     'echo:supply-purchases,status-changed' => 'handleStatusChanged',
    // ];

    /**
     * Dynamic listeners untuk handle user-specific notifications
     */
    protected function getListeners()
    {
        $baseListeners = [
            'deleteSupplyPurchaseBatch' => 'deleteSupplyPurchaseBatch',
            'updateDoNumber' => 'updateDoNumber',
            'showEditForm' => 'showEditForm',
            'showCreateForm' => 'showCreateForm',
            'cancel' => 'cancel',
            'updateStatusSupplyPurchase' => 'updateStatusSupplyPurchase',
            'echo:supply-purchases,status-changed' => 'handleStatusChanged',
        ];

        // Add user-specific notification listener dynamically
        if (auth()->check()) {
            $baseListeners['echo-notification:App.Models.User.' . auth()->id()] = 'handleUserNotification';
        }

        return $baseListeners;
    }

    public function mount()
    {
        // $this->items = [
        //     [
        //         'supply_id' => null,
        //         'quantity' => null,
        //         'unit' => null, // ← new: satuan yang dipilih user
        //         'price_per_unit' => null,
        //         'available_units' => [], // ← new: daftar satuan berdasarkan supply
        //     ]
        // ];
    }

    public function addItem()
    {
        $this->validate([
            'invoice_number' => 'required|string',
            'date' => 'required|date',
            'supplier_id' => 'required|exists:partners,id',
            'expedition_fee' => 'numeric|min:0',
            'farm_id' => 'required|exists:farms,id',
        ]);

        $this->items[] = [
            'supply_id' => null,
            'quantity' => null,
            'unit' => null, // ← new: satuan yang dipilih user
            'price_per_unit' => null,
            'available_units' => [], // ← new: daftar satuan berdasarkan supply
        ];
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    /**
     * Save purchase transaction data (SupplyPurchaseBatch + SupplyPurchase only)
     * Stock processing happens later when status becomes 'arrived'
     */
    public function save()
    {
        $this->errorItems = [];

        Log::info('Starting save process for Supply Purchases (Purchase Transaction Only)');

        // Validasi kombinasi supply_id dan unit_id tidak boleh duplikat
        $uniqueKeys = [];
        foreach ($this->items as $idx => $item) {
            $key = $item['supply_id'] . '-' . $item['unit_id'];
            if (in_array($key, $uniqueKeys)) {
                $this->errorItems[$idx] = 'Jenis supply dan satuan tidak boleh sama dengan baris lain.';
            }
            $uniqueKeys[] = $key;
        }

        if (!empty($this->errorItems)) {
            Log::warning('Duplicate items found during validation: ' . json_encode($this->errorItems));
            $this->dispatch('validation-errors', ['errors' => array_values($this->errorItems)]);
            return;
        }

        $this->validate([
            'invoice_number' => 'required|string',
            'date' => 'required|date',
            'supplier_id' => 'required|exists:partners,id',
            'expedition_fee' => 'numeric|min:0',
            'farm_id' => 'required|exists:farms,id',
            'items' => 'required|array|min:1',
            'items.*.supply_id' => 'required|exists:supplies,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.price_per_unit' => 'required|numeric|min:0',
            'items.*.unit_id' => 'required|exists:units,id',
        ]);

        Log::info('Validation passed for Supply Purchases data');

        DB::beginTransaction();

        try {
            Log::info('Beginning database transaction for Supply Purchase Batch');

            $batchData = [
                'invoice_number' => $this->invoice_number,
                'date' => $this->date,
                'supplier_id' => $this->supplier_id,
                'expedition_id' => $this->expedition_id ?? null,
                'expedition_fee' => $this->expedition_fee ?? 0,
                'status' => $this->pembelianId ? null : SupplyPurchaseBatch::STATUS_DRAFT, // Set initial status for new batches
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ];

            $batch = SupplyPurchaseBatch::updateOrCreate(
                ['id' => $this->pembelianId, 'farm_id' => $this->farm_id],
                array_filter($batchData) // Remove null values to avoid overwriting existing status on edit
            );

            Log::info('Supply Purchase Batch saved or updated with ID: ' . $batch->id);

            // Buat key yang digunakan untuk cek data yang tidak lagi dipakai
            $newItemKeys = collect($this->items)->map(fn($item) => $item['supply_id'] . '-' . $item['unit_id'])->toArray();

            // Clean up obsolete purchases and their related stocks (if any)
            foreach ($batch->supplyPurchases as $purchase) {
                $key = $purchase->supply_id . '-' . $purchase->unit_id;

                if (!in_array($key, $newItemKeys)) {
                    // Remove stocks first if they exist
                    SupplyStock::where('supply_purchase_id', $purchase->id)->delete();

                    // Recalculate CurrentSupply after removing stock
                    $this->recalculateCurrentSupplyAfterStockRemoval($purchase);

                    if ($this->withHistory) {
                        $purchase->delete(); // soft delete
                    } else {
                        $purchase->forceDelete(); // hard delete
                    }
                }
            }

            Log::info('Cleaned up obsolete Supply Purchases for batch ID: ' . $batch->id);

            // Process each purchase item (SupplyPurchase only, no stock processing yet)
            foreach ($this->items as $item) {
                $supply = Supply::findOrFail($item['supply_id']);

                $units = collect($supply->data['conversion_units']);
                $selectedUnit = $units->firstWhere('unit_id', $item['unit_id']);
                $smallestUnit = $units->firstWhere('is_smallest', true);

                if (!$selectedUnit || !$smallestUnit) {
                    throw new \Exception("Invalid unit conversion for supply: {$supply->name}");
                }

                $convertedQuantity = ($item['quantity'] * $selectedUnit['value']) / $smallestUnit['value'];

                $purchase = SupplyPurchase::updateOrCreate(
                    [
                        'supply_purchase_batch_id' => $batch->id,
                        'supply_id' => $supply->id,
                        'unit_id' => $item['unit_id'],
                    ],
                    [
                        'farm_id' => $this->farm_id,
                        'quantity' => $item['quantity'],
                        'converted_quantity' => $convertedQuantity,
                        'converted_unit' => $smallestUnit['unit_id'],
                        'price_per_unit' => $item['price_per_unit'],
                        'price_per_converted_unit' => $item['unit_id'] !== $smallestUnit['unit_id']
                            ? round($item['price_per_unit'] * ($smallestUnit['value'] / $selectedUnit['value']), 2)
                            : $item['price_per_unit'],
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]
                );

                Log::info('Processed purchase item for Supply Purchase ID: ' . $purchase->id . ' in batch ID: ' . $batch->id);
            }

            // Save batch payload for future reference
            $batch->data = [
                'items' => collect($this->items)->map(fn($item) => [
                    'supply_id' => $item['supply_id'],
                    'quantity' => $item['quantity'],
                    'unit_id' => $item['unit_id'],
                    'price_per_unit' => $item['price_per_unit'],
                    'available_units' => $item['available_units'] ?? [],
                ])->toArray(),
                'farm_id' => $this->farm_id,
                'supplier_id' => $this->supplier_id,
                'expedition_id' => $this->expedition_id,
                'expedition_fee' => $this->expedition_fee,
                'date' => $this->date,
            ];
            $batch->save();

            Log::info('Batch payload saved for Supply Purchase Batch ID: ' . $batch->id);

            DB::commit();

            Log::info('Transaction committed successfully for Supply Purchase Batch ID: ' . $batch->id);

            $message = $this->pembelianId
                ? 'Pembelian supply berhasil diperbarui'
                : 'Pembelian supply berhasil disimpan dengan status DRAFT. Stock akan diproses ketika status menjadi ARRIVED.';

            $this->dispatch('success', $message);
            $this->close();
        } catch (ValidationException $e) {
            DB::rollBack();
            Log::error('Validation exception in save process: ' . $e->getMessage());
            $this->dispatch('validation-errors', ['errors' => $e->validator->errors()->all()]);
            $this->setErrorBag($e->validator->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('General exception in save process: ' . $e->getMessage());
            $this->dispatch('error', 'Terjadi kesalahan saat ' . ($this->pembelianId ? 'memperbarui' : 'menyimpan') . ' data. ' . $e->getMessage());
        }
    }

    /**
     * Process stock arrival - create SupplyStock and update CurrentSupply
     * This method is called when status changes to 'arrived'
     */
    public function processStockArrival(SupplyPurchaseBatch $batch)
    {
        Log::info('Starting stock arrival processing for batch ID: ' . $batch->id);

        DB::beginTransaction();

        try {
            foreach ($batch->supplyPurchases as $purchase) {
                $supply = Supply::findOrFail($purchase->supply_id);
                $farm = Farm::findOrFail($purchase->farm_id);

                // Create or update SupplyStock
                $supplyStock = SupplyStock::updateOrCreate(
                    [
                        'livestock_id' => $this->livestock_id,
                        'farm_id' => $purchase->farm_id,
                        'supply_id' => $purchase->supply_id,
                        'supply_purchase_id' => $purchase->id,
                    ],
                    [
                        'date' => $batch->date,
                        'source_type' => 'purchase',
                        'source_id' => $purchase->id,
                        'quantity_in' => $purchase->converted_quantity,
                        'quantity_mutated' => 0,
                        'quantity_used' => 0,
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]
                );

                Log::info('Created/Updated SupplyStock for Purchase ID: ' . $purchase->id);

                // Recalculate and update CurrentSupply
                $this->recalculateCurrentSupply($farm, $supply);
            }

            DB::commit();

            Log::info('Stock arrival processing completed successfully for batch ID: ' . $batch->id);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in stock arrival processing: ' . $e->getMessage(), [
                'batch_id' => $batch->id,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw $e;
        }

        // dd('supplyStock', $supplyStock);
    }

    /**
     * Recalculate CurrentSupply after stock changes
     */
    private function recalculateCurrentSupply(Farm $farm, Supply $supply)
    {
        // Calculate total quantity from all arrived supplies
        $totalQuantity = SupplyStock::join('supply_purchases', 'supply_stocks.supply_purchase_id', '=', 'supply_purchases.id')
            ->join('supply_purchase_batches', 'supply_purchases.supply_purchase_batch_id', '=', 'supply_purchase_batches.id')
            ->where('supply_stocks.farm_id', $farm->id)
            ->where('supply_stocks.supply_id', $supply->id)
            ->where('supply_purchase_batches.status', SupplyPurchaseBatch::STATUS_ARRIVED)
            ->when(!$this->withHistory, function ($q) {
                return $q->whereNull('supply_stocks.deleted_at');
            })
            ->sum('supply_stocks.quantity_in');

        CurrentSupply::updateOrCreate(
            [
                'farm_id' => $farm->id,
                'coop_id' => $farm->coop_id,
                'item_id' => $supply->id,
                'unit_id' => $supply->data['unit_id'],
                'type' => 'supply',
            ],
            [
                'quantity' => $totalQuantity,
                'status' => 'active',
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]
        );

        Log::info("Recalculated CurrentSupply for Farm {$farm->id}, Supply {$supply->id}: {$totalQuantity}");
    }

    /**
     * Recalculate CurrentSupply after removing stock
     */
    private function recalculateCurrentSupplyAfterStockRemoval(SupplyPurchase $purchase)
    {
        $supply = Supply::find($purchase->supply_id);
        $farm = Farm::find($purchase->farm_id);

        if ($supply && $farm) {
            $this->recalculateCurrentSupply($farm, $supply);
        }
    }

    public function resetForm()
    {
        $this->reset();
        $this->items = [
            [
                'supply_id' => null,
                'quantity' => null,
                'unit' => null, // ← new: satuan yang dipilih user
                'price_per_unit' => null,
                'available_units' => [], // ← new: daftar satuan berdasarkan supply
            ],
        ];
    }

    public function updatedItems($value, $key)
    {
        [$index, $field] = explode('.', $key);

        if ($field === 'supply_id') {
            $supply = Supply::find($value);

            if ($supply && isset($supply->data['conversion_units'])) {
                $units = collect($supply->data['conversion_units']);

                $this->items[$index]['available_units'] = $units->map(function ($unit) {
                    $unitModel = Unit::find($unit['unit_id']);
                    return [
                        'unit_id' => $unit['unit_id'],
                        'label' => $unitModel?->name ?? 'Unknown',
                        'value' => $unit['value'],
                        'is_smallest' => $unit['is_smallest'] ?? false,
                    ];
                })->toArray();

                // Set default unit based on is_default_purchase or first available unit
                $defaultUnit = $units->firstWhere('is_default_purchase', true) ?? $units->first();
                if ($defaultUnit) {
                    $this->items[$index]['unit_id'] = $defaultUnit['unit_id'];
                }
            } else {
                $this->items[$index]['available_units'] = [];
                $this->items[$index]['unit_id'] = null;
            }
        }
    }

    public function updateUnitConversion($index)
    {
        $unitId = $this->items[$index]['unit_id'] ?? null;
        $quantity = $this->items[$index]['quantity'] ?? null;
        $supplyId = $this->items[$index]['supply_id'] ?? null;

        if (!$unitId || !$quantity || !$supplyId) return;

        $supply = Supply::find($supplyId);
        if (!$supply || empty($supply->data['conversion_units'])) return;

        $units = collect($supply->data['conversion_units']);
        $selectedUnit = $units->firstWhere('unit_id', $unitId);
        $smallestUnit = $units->firstWhere('is_smallest', true);

        if ($selectedUnit && $smallestUnit) {
            // Convert to smallest unit
            $this->items[$index]['converted_quantity'] = ($quantity * $selectedUnit['value']) / $smallestUnit['value'];
        }
    }

    protected function convertToSmallestUnit($supply, $quantity, $unitId)
    {
        $units = collect($supply->data['conversion_units'] ?? []);

        $from = $units->firstWhere('unit_id', $unitId);
        $smallest = $units->firstWhere('is_smallest', true);

        if (!$from || !$smallest || $from['value'] == 0 || $smallest['value'] == 0) {
            return $quantity; // fallback tanpa konversi
        }

        // dd([
        //     'quantity' => $quantity,
        //     'value' => $from['value'],
        //     'smallest' => $smallest['value'],
        // ]);
    }

    public function render()
    {
        $supply = Supply::where('status', 'active')->orderBy('name')->get();
        $farmIds = auth()->user()->farmOperators()->pluck('farm_id')->toArray();
        $farms = Farm::whereIn('id', $farmIds)->get(['id', 'name']);

        // dd($supply);
        return view('livewire.supply-purchases.create', [
            'vendors' => Partner::where('type', 'Supplier')->get(),
            'expeditions' => Expedition::all(),
            'supplyItems' => $supply,
            'farms' => $farms,
        ]);
    }

    public function showCreateForm()
    {
        $this->resetForm();
        $this->showForm = true;
        $this->dispatch('hide-datatable');
    }

    public function cancel()
    {
        $this->resetForm();
        $this->showForm = false;
        $this->dispatch('show-datatable');
    }

    public function close()
    {
        $this->resetForm();
        $this->showForm = false;
        $this->dispatch('show-datatable');
    }

    public function deleteSupplyPurchaseBatch($batchId)
    {
        try {
            DB::beginTransaction();

            $batch = SupplyPurchaseBatch::with(['supplyPurchases.supply'])->findOrFail($batchId);

            // Check if batch has ARRIVED status - need to handle stocks
            $hasStocks = $batch->status === SupplyPurchaseBatch::STATUS_ARRIVED;

            // Track all related records for the audit trail
            $relatedRecords = [
                'supplyPurchases' => [],
                'supplyStocks' => [],
                'currentSupplies' => []
            ];

            // If batch has arrived status, check for stock usage before deletion
            if ($hasStocks) {
                foreach ($batch->supplyPurchases as $purchase) {
                    $supplyStocks = SupplyStock::where('supply_purchase_id', $purchase->id)->get();

                    foreach ($supplyStocks as $supplyStock) {
                        // Validasi: tidak bisa hapus jika sudah dipakai atau dimutasi
                        if (($supplyStock->used ?? 0) > 0 || ($supplyStock->quantity_mutated ?? 0) > 0) {
                            $this->dispatch('error', 'Tidak dapat menghapus batch. Beberapa stok sudah digunakan atau dimutasi.');
                            DB::rollBack();
                            return;
                        }
                    }
                }
            }

            // Process deletions
            foreach ($batch->supplyPurchases as $purchase) {
                // Add purchase to related records
                $relatedRecords['supplyPurchases'][] = [
                    'id' => $purchase->id,
                    'supply_id' => $purchase->supply_id,
                    'farm_id' => $purchase->farm_id,
                    'quantity' => $purchase->quantity,
                    'price_per_unit' => $purchase->price_per_unit
                ];

                // Handle stocks if batch was ARRIVED
                if ($hasStocks) {
                    $supplyStocks = SupplyStock::where('supply_purchase_id', $purchase->id)->get();

                    foreach ($supplyStocks as $supplyStock) {
                        // Add stock to related records
                        $relatedRecords['supplyStocks'][] = [
                            'id' => $supplyStock->id,
                            'farm_id' => $supplyStock->farm_id,
                            'supply_id' => $supplyStock->supply_id,
                            'quantity_in' => $supplyStock->quantity_in,
                        ];

                        // Delete the stock
                        $supplyStock->delete();
                    }

                    // Recalculate CurrentSupply after stock removal
                    $supply = Supply::find($purchase->supply_id);
                    $farm = Farm::find($purchase->farm_id);

                    if ($supply && $farm) {
                        $this->recalculateCurrentSupply($farm, $supply);

                        $relatedRecords['currentSupplies'][] = [
                            'farm_id' => $farm->id,
                            'supply_id' => $supply->id,
                            'action' => 'recalculated'
                        ];
                    }
                }

                // Delete the purchase (soft delete if withHistory is true)
                if ($this->withHistory) {
                    $purchase->delete(); // soft delete
                } else {
                    $purchase->forceDelete(); // hard delete
                }
            }

            // Log to audit trail before final deletion
            AuditTrailService::logCascadingDeletion(
                $batch,
                $relatedRecords,
                "User initiated deletion of supply purchase batch (Refactored version)"
            );

            // Delete batch with status history
            if ($this->withHistory) {
                $batch->delete(); // soft delete
            } else {
                $batch->forceDelete(); // hard delete
            }

            DB::commit();

            $message = $hasStocks
                ? 'Data berhasil dihapus dan stok telah diperbarui'
                : 'Data pembelian berhasil dihapus';

            $this->dispatch('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error deleting supply purchase batch: " . $e->getMessage(), [
                'batch_id' => $batchId,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->dispatch('error', 'Terjadi kesalahan saat menghapus data: ' . $e->getMessage());
        }
    }

    public function updateDoNumber($transaksiId, $newNoSj)
    {
        $transaksiDetail = SupplyPurchaseBatch::findOrFail($transaksiId);

        if ($transaksiDetail->exists()) {
            $transaksiDetail->do_number = $newNoSj;
            $transaksiDetail->save();
            $this->dispatch('noSjUpdated');
            $this->dispatch('success', 'Nomor Surat Jalan / Deliveri Order berhasil diperbarui.');
        } else {
            $this->dispatch('error', 'Tidak ada detail transaksi yang ditemukan.');
        }
    }

    public function showEditForm($id)
    {
        $this->pembelianId = $id;
        $pembelian = SupplyPurchaseBatch::with('supplyPurchases')->find($id);

        $this->items = [];
        if ($pembelian && !empty($pembelian->payload['items'])) {
            // Prefer load from payload
            foreach ($pembelian->payload['items'] as $item) {
                $this->items[] = [
                    'supply_id' => $item['supply_id'],
                    'quantity' => $item['quantity'],
                    'unit_id' => $item['unit_id'],
                    'price_per_unit' => $item['price_per_unit'],
                    'available_units' => $item['available_units'] ?? [],
                ];
            }
            $this->farm_id = $pembelian->payload['farm_id'] ?? null;
            $this->supplier_id = $pembelian->payload['supplier_id'] ?? null;
            $this->expedition_id = $pembelian->payload['expedition_id'] ?? null;
            $this->expedition_fee = $pembelian->payload['expedition_fee'] ?? 0;
            $this->date = $pembelian->payload['date'] ?? $pembelian->date;
            $this->invoice_number = $pembelian->invoice_number ?? null;
        } elseif ($pembelian && $pembelian->supplyPurchases->isNotEmpty()) {
            $this->date = $pembelian->date;
            $this->farm_id = $pembelian->supplyPurchases->first()->farm_id;
            $this->invoice_number = $pembelian->invoice_number;
            $this->supplier_id = $pembelian->supplier_id;
            $this->expedition_id = $pembelian->expedition_id;
            $this->expedition_fee = $pembelian->expedition_fee;

            foreach ($pembelian->supplyPurchases as $item) {
                $supply = \App\Models\Supply::find($item->supply_id);
                $available_units = [];
                $unit_id = $item->unit_id !== null ? (string)$item->unit_id : null;
                if ($supply && isset($supply->data['conversion_units'])) {
                    $available_units = collect($supply->data['conversion_units'])->map(function ($unit) {
                        $unitModel = \App\Models\Unit::find($unit['unit_id']);
                        return [
                            'unit_id' => (string)$unit['unit_id'],
                            'label' => $unitModel?->name ?? 'Unknown',
                            'value' => $unit['value'],
                            'is_smallest' => $unit['is_smallest'] ?? false,
                        ];
                    })->toArray();
                }
                $this->items[] = [
                    'farm_id' => $item->farm_id,
                    'supply_id' => $item->supply_id,
                    'quantity' => $item->quantity,
                    'price_per_unit' => $item->price_per_unit,
                    'unit_id' => $unit_id,
                    'available_units' => $available_units,
                ];
            }
        }
        $this->showForm = true;
        $this->edit_mode = true;
        $this->dispatch('hide-datatable');
    }

    public function updateStatusSupplyPurchase($purchaseId, $status, $notes)
    {
        if (empty($purchaseId) || empty($status)) {
            return;
        }

        $batch = \App\Models\SupplyPurchaseBatch::findOrFail($purchaseId);
        $notes = $notes ?? null;
        $oldStatus = $batch->status;

        Log::info('Starting status update for Supply Purchase Batch', [
            'batch_id' => $batch->id,
            'old_status' => $oldStatus,
            'new_status' => $status,
            'updated_by' => auth()->id(),
            'notes' => $notes
        ]);



        // If status is arrived, process stock arrival
        if ($status === \App\Models\SupplyPurchaseBatch::STATUS_ARRIVED) {
            // dd($status, $oldStatus);

            // try {
            // Process stock arrival - create SupplyStock and update CurrentSupply
            $this->processStockArrival($batch);

            Log::info("Stock arrival processed successfully for batch ID: {$batch->id}");
            // } catch (\Exception $e) {
            //     Log::error("Failed to process stock arrival for batch ID: {$batch->id}", [
            //         'error' => $e->getMessage(),
            //         'file' => $e->getFile(),
            //         'line' => $e->getLine()
            //     ]);

            //     $this->dispatch('error', 'Gagal memproses kedatangan stock: ' . $e->getMessage());
            //     return;
            // }
        }

        // If status is being changed from ARRIVED to something else, handle stock rollback
        if (
            $oldStatus === \App\Models\SupplyPurchaseBatch::STATUS_ARRIVED &&
            $status !== \App\Models\SupplyPurchaseBatch::STATUS_ARRIVED
        ) {
            try {
                $this->rollbackStockArrival($batch);

                Log::info("Stock rollback processed for batch ID: {$batch->id}");
            } catch (\Exception $e) {
                Log::error("Failed to rollback stock for batch ID: {$batch->id}", [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);

                $this->dispatch('error', 'Gagal rollback stock: ' . $e->getMessage());
                return;
            }
        }

        // Update status using the new SupplyStatusHistory system
        $batch->updateStatus($status, $notes, [
            'previous_status' => $oldStatus,
            'status_change_trigger' => 'manual_update',
            'updated_via' => 'livewire_component'
        ]);

        // ✅ IMMEDIATE REAL-TIME NOTIFICATION TO ALL LIVEWIRE COMPONENTS
        if ($oldStatus !== $status) {
            $notificationData = [
                'type' => $this->getNotificationTypeForStatus($status),
                'title' => 'Supply Purchase Status Updated',
                'message' => $this->getStatusChangeMessage($batch, $oldStatus, $status),
                'batch_id' => $batch->id,
                'old_status' => $oldStatus,
                'new_status' => $status,
                'updated_by' => auth()->id(),
                'updated_by_name' => auth()->user()->name,
                'invoice_number' => $batch->invoice_number,
                'requires_refresh' => $this->requiresRefresh($oldStatus, $status),
                'priority' => $this->getPriority($oldStatus, $status),
                'show_refresh_button' => true,
                'timestamp' => now()->toISOString()
            ];

            // 🎯 BROADCAST TO ALL SUPPLY PURCHASE LIVEWIRE COMPONENTS IMMEDIATELY
            $this->dispatch('notify-status-change', $notificationData)->to('supply-purchases.create');

            Log::info('IMMEDIATE notification dispatched to Livewire components', [
                'batch_id' => $batch->id,
                'notification_data' => $notificationData
            ]);

            // ✅ SEND TO SSE NOTIFICATION BRIDGE FOR REAL-TIME UPDATES (NO MORE POLLING!)
            $this->sendToSSENotificationBridge($notificationData, $batch);

            // Fire event for external systems and broadcasting (secondary)
            try {
                event(new SupplyPurchaseStatusChanged(
                    $batch,
                    $oldStatus,
                    $status,
                    auth()->id(),
                    $notes,
                    [
                        'source' => 'livewire_component',
                        'trigger' => 'manual_update',
                        'timestamp' => now()->toISOString(),
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                    ]
                ));

                Log::info('SupplyPurchaseStatusChanged event fired', [
                    'batch_id' => $batch->id,
                    'old_status' => $oldStatus,
                    'new_status' => $status,
                    'updated_by' => auth()->id()
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to fire SupplyPurchaseStatusChanged event', [
                    'batch_id' => $batch->id,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
            }
        }

        $this->dispatch('statusUpdated');

        $statusMessage = $status === \App\Models\SupplyPurchaseBatch::STATUS_ARRIVED
            ? 'Status berhasil diperbarui dan stock telah diproses.'
            : 'Status pembelian berhasil diperbarui.';

        $this->dispatch('success', $statusMessage);
    }

    /**
     * Handle real-time status change notifications
     */
    public function handleStatusChanged($event)
    {
        Log::info('Received real-time status change notification', [
            'batch_id' => $event['batch_id'] ?? 'unknown',
            'old_status' => $event['old_status'] ?? 'unknown',
            'new_status' => $event['new_status'] ?? 'unknown',
            'updated_by' => $event['updated_by'] ?? 'unknown',
            'current_user' => auth()->id()
        ]);

        try {
            // Check if this change affects current user's view
            if ($this->shouldRefreshData($event)) {
                $this->dispatch('notify-status-change', [
                    'type' => 'info',
                    'title' => 'Data Update Available',
                    'message' => $event['message'] ?? 'A supply purchase status has been updated.',
                    'requires_refresh' => $event['metadata']['requires_refresh'] ?? false,
                    'priority' => $event['metadata']['priority'] ?? 'normal',
                    'batch_id' => $event['batch_id'] ?? null,
                    'show_refresh_button' => true
                ]);

                Log::info('Status change notification dispatched to user', [
                    'batch_id' => $event['batch_id'] ?? 'unknown',
                    'user_id' => auth()->id()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error handling status change notification', [
                'error' => $e->getMessage(),
                'event' => $event,
                'user_id' => auth()->id()
            ]);
        }
    }

    /**
     * Handle user-specific notifications
     */
    public function handleUserNotification($notification)
    {
        Log::info('Received user-specific notification', [
            'notification_type' => $notification['type'] ?? 'unknown',
            'user_id' => auth()->id()
        ]);

        try {
            if (isset($notification['type']) && $notification['type'] === 'supply_purchase_status_changed') {
                $this->dispatch('notify-status-change', [
                    'type' => $this->getNotificationType($notification['priority'] ?? 'normal'),
                    'title' => $notification['title'] ?? 'Supply Purchase Update',
                    'message' => $notification['message'] ?? 'A supply purchase has been updated.',
                    'requires_refresh' => in_array('refresh_data', $notification['action_required'] ?? []),
                    'priority' => $notification['priority'] ?? 'normal',
                    'batch_id' => $notification['batch_id'] ?? null,
                    'show_refresh_button' => true
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error handling user notification', [
                'error' => $e->getMessage(),
                'notification' => $notification,
                'user_id' => auth()->id()
            ]);
        }
    }

    /**
     * Determine if data should be refreshed based on event
     */
    private function shouldRefreshData($event): bool
    {
        // Always show notifications for high priority changes
        if (($event['metadata']['priority'] ?? 'normal') === 'high') {
            return true;
        }

        // Show if refresh is explicitly required
        if ($event['metadata']['requires_refresh'] ?? false) {
            return true;
        }

        // Show for current user's related batches (if we're in edit mode)
        if ($this->pembelianId && isset($event['batch_id']) && $event['batch_id'] == $this->pembelianId) {
            return true;
        }

        return false;
    }

    /**
     * Get notification type based on priority
     */
    private function getNotificationType(string $priority): string
    {
        return match ($priority) {
            'high' => 'warning',
            'medium' => 'info',
            'low' => 'success',
            default => 'info'
        };
    }

    /**
     * Get notification type based on status
     */
    private function getNotificationTypeForStatus(string $status): string
    {
        switch ($status) {
            case 'arrived':
                return 'success';
            case 'cancelled':
                return 'warning';
            case 'completed':
                return 'info';
            default:
                return 'info';
        }
    }

    /**
     * Rollback stock when status changes from ARRIVED to other status
     */
    private function rollbackStockArrival(SupplyPurchaseBatch $batch)
    {
        Log::info('Starting stock rollback for batch ID: ' . $batch->id);

        DB::beginTransaction();

        try {
            foreach ($batch->supplyPurchases as $purchase) {
                // Remove SupplyStock records
                $stocksRemoved = SupplyStock::where('supply_purchase_id', $purchase->id)->delete();

                Log::info("Removed {$stocksRemoved} stock records for Purchase ID: {$purchase->id}");

                // Recalculate CurrentSupply
                $supply = Supply::find($purchase->supply_id);
                $farm = Farm::find($purchase->farm_id);

                if ($supply && $farm) {
                    $this->recalculateCurrentSupply($farm, $supply);
                }
            }

            DB::commit();

            Log::info('Stock rollback completed successfully for batch ID: ' . $batch->id);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in stock rollback: ' . $e->getMessage(), [
                'batch_id' => $batch->id,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw $e;
        }
    }

    /**
     * Get human-readable status change message
     */
    private function getStatusChangeMessage($batch, string $oldStatus, string $newStatus): string
    {
        $statusLabels = \App\Models\SupplyPurchaseBatch::STATUS_LABELS ?? [
            'draft' => 'Draft',
            'confirmed' => 'Confirmed',
            'shipped' => 'Shipped',
            'arrived' => 'Arrived',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled'
        ];

        $oldLabel = $statusLabels[$oldStatus] ?? $oldStatus;
        $newLabel = $statusLabels[$newStatus] ?? $newStatus;
        $supplier = Partner::find($batch->supplier_id);

        return "Purchase {$batch->invoice_number} status changed from {$oldLabel} to {$newLabel} by " . auth()->user()->name . " from {$supplier->name}";
    }

    /**
     * Check if status change requires page refresh
     */
    private function requiresRefresh(string $oldStatus, string $newStatus): bool
    {
        $criticalChanges = [
            'draft' => ['arrived', 'confirmed'],
            'confirmed' => ['arrived', 'cancelled'],
            'arrived' => ['completed', 'cancelled'],
            'pending' => ['arrived', 'cancelled'],
        ];

        return isset($criticalChanges[$oldStatus]) &&
            in_array($newStatus, $criticalChanges[$oldStatus]);
    }

    /**
     * Get notification priority
     */
    private function getPriority(string $oldStatus, string $newStatus): string
    {
        if ($newStatus === 'arrived') return 'high';
        if ($newStatus === 'cancelled') return 'medium';
        if ($newStatus === 'completed') return 'low';
        return 'normal';
    }

    /**
     * Send notification to SSE bridge with debounce mechanism
     */
    private function sendToSSENotificationBridge($notificationData, $batch)
    {
        try {
            // Debounce mechanism: prevent duplicate notifications for same batch within 2 seconds
            $cacheKey = "sse_notification_debounce_{$batch->id}_{$notificationData['new_status']}";

            if (Cache::has($cacheKey)) {
                Log::info('SSE notification debounced (too frequent)', [
                    'batch_id' => $batch->id,
                    'status' => $notificationData['new_status'],
                    'cache_key' => $cacheKey
                ]);
                return;
            }

            // Set debounce cache for 2 seconds
            Cache::put($cacheKey, true, 2);

            // Prepare notification data for SSE storage
            $sseNotification = [
                'type' => 'supply_purchase_status_changed',
                'title' => $notificationData['title'],
                'message' => $notificationData['message'],
                'source' => 'livewire_sse',
                'priority' => $notificationData['priority'] ?? 'normal',
                'data' => [
                    'batch_id' => $batch->id,
                    'invoice_number' => $batch->invoice_number,
                    'updated_by' => auth()->id(),
                    'updated_by_name' => auth()->user()->name,
                    'old_status' => $notificationData['old_status'],
                    'new_status' => $notificationData['new_status'],
                    'timestamp' => $notificationData['timestamp'],
                    'requires_refresh' => $notificationData['requires_refresh']
                ],
                'requires_refresh' => $notificationData['requires_refresh'],
                'timestamp' => time(),
                'debounce_key' => $cacheKey
            ];

            // Store notification for SSE clients (with retry mechanism)
            $result = $this->storeSSENotification($sseNotification);

            if ($result) {
                Log::info('Successfully stored notification for SSE bridge', [
                    'batch_id' => $batch->id,
                    'notification_id' => $result['id'],
                    'updated_by' => auth()->id(),
                    'sse_system' => 'active'
                ]);
            } else {
                Log::warning('Failed to store SSE notification after retries', [
                    'batch_id' => $batch->id,
                    'updated_by' => auth()->id()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error storing notification for SSE bridge', [
                'batch_id' => $batch->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }

    /**
     * Store notification for SSE clients with file locking and retry mechanism
     */
    private function storeSSENotification($notification)
    {
        $filePath = base_path('testing/sse-notifications.json');
        $maxRetries = 3;
        $retryDelay = 100000; // 100ms in microseconds

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                // Create lock file to prevent race conditions
                $lockFile = $filePath . '.lock';
                $lockHandle = fopen($lockFile, 'w');

                if (!$lockHandle || !flock($lockHandle, LOCK_EX | LOCK_NB)) {
                    if ($lockHandle) fclose($lockHandle);

                    // If this is not the last attempt, wait and retry
                    if ($attempt < $maxRetries) {
                        usleep($retryDelay * $attempt); // Exponential backoff
                        continue;
                    }

                    Log::warning('Could not acquire file lock for SSE notification', [
                        'attempt' => $attempt,
                        'file' => $filePath
                    ]);
                    return null;
                }

                // Initialize file if not exists
                if (!file_exists($filePath)) {
                    file_put_contents($filePath, json_encode([
                        'notifications' => [],
                        'last_update' => time(),
                        'stats' => [
                            'total_sent' => 0,
                            'clients_connected' => 0
                        ]
                    ]));
                }

                // Read existing data
                $content = file_get_contents($filePath);
                $data = $content ? json_decode($content, true) : [
                    'notifications' => [],
                    'last_update' => time(),
                    'stats' => ['total_sent' => 0, 'clients_connected' => 0]
                ];

                // Prepare notification with unique ID and timestamp
                $notification['id'] = uniqid() . '_' . microtime(true);
                $notification['timestamp'] = time();
                $notification['datetime'] = date('Y-m-d H:i:s');
                $notification['microseconds'] = microtime(true);

                // Add to beginning of array (newest first)
                array_unshift($data['notifications'], $notification);

                // Keep only last 50 notifications (reduced from 100 for performance)
                $data['notifications'] = array_slice($data['notifications'], 0, 50);

                $data['last_update'] = time();
                $data['stats']['total_sent']++;

                // Write atomically using temporary file
                $tempFile = $filePath . '.tmp';
                if (file_put_contents($tempFile, json_encode($data, JSON_PRETTY_PRINT)) !== false) {
                    rename($tempFile, $filePath);
                }

                // Release lock
                flock($lockHandle, LOCK_UN);
                fclose($lockHandle);
                unlink($lockFile);

                Log::info('SSE notification stored successfully', [
                    'notification_id' => $notification['id'],
                    'attempt' => $attempt,
                    'total_notifications' => count($data['notifications'])
                ]);

                return $notification;
            } catch (\Exception $e) {
                // Clean up lock if it exists
                if (isset($lockHandle) && $lockHandle) {
                    flock($lockHandle, LOCK_UN);
                    fclose($lockHandle);
                }
                if (isset($lockFile) && file_exists($lockFile)) {
                    unlink($lockFile);
                }

                Log::error('Error storing SSE notification', [
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);

                // If this is not the last attempt, wait and retry
                if ($attempt < $maxRetries) {
                    usleep($retryDelay * $attempt);
                    continue;
                }

                // Last attempt failed
                return null;
            }
        }

        return null;
    }

    /**
     * Get notification bridge URL based on environment (DEPRECATED: Using SSE now)
     */
    private function getBridgeUrl()
    {
        // Check if we're in a web context
        if (request()->server('HTTP_HOST')) {
            $baseUrl = request()->getSchemeAndHttpHost();
            $bridgeUrl = $baseUrl . '/testing/notification_bridge.php';

            // Test if bridge is available
            try {
                $testResponse = Http::timeout(2)->get($bridgeUrl . '?action=status');
                if ($testResponse->successful()) {
                    $data = $testResponse->json();
                    if ($data['success'] ?? false) {
                        return $bridgeUrl;
                    }
                }
            } catch (\Exception $e) {
                Log::debug('Bridge test failed', ['error' => $e->getMessage()]);
            }
        }

        return null; // Bridge not available
    }

    /**
     * Enhanced save method with production notification integration
     */
    public function saveWithNotification()
    {
        // Call the original save method
        $this->save();

        // Send immediate notification for new supply purchase creation
        if (!$this->pembelianId) {
            $notificationData = [
                'type' => 'success',
                'title' => 'New Supply Purchase Created',
                'message' => 'A new supply purchase has been created successfully.',
                'source' => 'livewire_production',
                'priority' => 'normal',
                'data' => [
                    'action' => 'created',
                    'timestamp' => now()->toISOString(),
                    'created_by' => auth()->user()->name
                ]
            ];

            // Send to bridge
            $this->sendToProductionNotificationBridge($notificationData, null);
        }
    }

    /**
     * Send test notification to production bridge
     */
    public function sendTestNotification()
    {
        $testNotification = [
            'type' => 'info',
            'title' => 'Test Notification from Livewire',
            'message' => 'This is a test notification sent from the Livewire component at ' . now()->format('H:i:s'),
            'source' => 'livewire_test',
            'priority' => 'normal',
            'data' => [
                'test' => true,
                'component' => 'supply-purchases.create',
                'user' => auth()->user()->name,
                'timestamp' => now()->toISOString()
            ]
        ];

        $this->sendToProductionNotificationBridge($testNotification, null);

        // Also dispatch to frontend
        $this->dispatch('notify-status-change', [
            'type' => 'info',
            'title' => 'Test Notification Sent',
            'message' => 'Test notification has been sent to the production bridge system.',
            'timestamp' => now()->toISOString()
        ]);

        Log::info('Test notification sent from Livewire component', [
            'user_id' => auth()->id(),
            'timestamp' => now()->toISOString()
        ]);
    }
}

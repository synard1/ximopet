<?php

namespace App\Livewire\SupplyPurchases;

use App\Services\AuditTrailService;

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

    protected $listeners = [
        'deleteSupplyPurchaseBatch' => 'deleteSupplyPurchaseBatch',
        'updateDoNumber' => 'updateDoNumber',
        'showEditForm' => 'showEditForm',
        'showCreateForm' => 'showCreateForm',
        'cancel' => 'cancel',

    ];

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

    public function save()
    {
        $this->errorItems = [];

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

        DB::beginTransaction();

        try {

            $batchData = [
                'invoice_number' => $this->invoice_number,
                'date' => $this->date,
                'supplier_id' => $this->supplier_id,
                'expedition_id' => $this->expedition_id ?? null,
                'expedition_fee' => $this->expedition_fee ?? 0,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ];

            $batch = SupplyPurchaseBatch::updateOrCreate(
                ['id' => $this->pembelianId],
                $batchData
            );

            // Buat key yang digunakan untuk cek data yang tidak lagi dipakai
            $newItemKeys = collect($this->items)->map(fn($item) => $item['supply_id'] . '-' . $item['unit_id'])->toArray();

            foreach ($batch->supplyPurchases as $purchase) {
                $key = $purchase->supply_id . '-' . $purchase->original_unit;

                if (!in_array($key, $newItemKeys)) {
                    SupplyStock::where('supply_purchase_id', $purchase->id)->delete();

                    if ($this->withHistory) {
                        $purchase->delete(); // soft delete
                    } else {
                        $purchase->forceDelete(); // hard delete
                    }
                }
            }

            foreach ($this->items as $item) {
                $supply = Supply::findOrFail($item['supply_id']);
                $farm = Farm::findOrFail($this->farm_id);

                $units = collect($supply->payload['conversion_units']);
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
                        'supply_purchase_batch_id' => $batch->id,
                        'supply_id' => $supply->id,
                        'unit_id' => $item['unit_id'],
                    ]
                );

                SupplyStock::updateOrCreate(
                    [
                        'livestock_id' => $this->livestock_id,
                        'farm_id' => $this->farm_id,
                        'supply_id' => $supply->id,
                        'supply_purchase_id' => $purchase->id,
                    ],
                    [
                        'date' => $this->date,
                        'source_type' => 'purchase',
                        'source_id' => $purchase->id,
                        'amount' => $convertedQuantity,
                        'available' => DB::raw('amount - used - quantity_mutated'),
                        'quantity_in' => $convertedQuantity,
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]
                );

                // Hitung ulang CurrentSupply TANPA softdelete jika withHistory == false
                $currentQuantity = SupplyPurchase::when(!$this->withHistory, function ($q) {
                    return $q->whereNull('deleted_at');
                })
                    ->where('farm_id', $farm->id)
                    ->where('supply_id', $supply->id)
                    ->sum('converted_quantity');

                CurrentSupply::updateOrCreate(
                    [
                        'farm_id' => $farm->id,
                        'coop_id' => $farm->coop_id,
                        'item_id' => $supply->id,
                        'unit_id' => $supply->payload['unit_id'],
                        'type'  => 'supply',
                    ],
                    [
                        'quantity' => $currentQuantity,
                        'status' => 'active',
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]
                );
            }

            $batch->payload = [
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

            DB::commit();

            $this->dispatch('success', 'Pembelian supply berhasil ' . ($this->pembelianId ? 'diperbarui' : 'disimpan'));
            $this->close();
        } catch (ValidationException $e) {
            DB::rollBack();
            $this->dispatch('validation-errors', ['errors' => $e->validator->errors()->all()]);
            $this->setErrorBag($e->validator->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('error', 'Terjadi kesalahan saat ' . ($this->pembelianId ? 'memperbarui' : 'menyimpan') . ' data. ' . $e->getMessage());
        }
    }

    // public function save()
    // {
    //     $this->errorItems = [];
    //     // Cek duplikasi kombinasi supply_id + unit_id di $this->items
    //     $uniqueKeys = [];
    //     foreach ($this->items as $idx => $item) {
    //         $key = $item['supply_id'] . '-' . $item['unit_id'];
    //         if (in_array($key, $uniqueKeys)) {
    //             $this->errorItems[$idx] = 'Jenis supply dan satuan tidak boleh sama dengan baris lain.';
    //         }
    //         $uniqueKeys[] = $key;
    //     }
    //     if (!empty($this->errorItems)) {
    //         $this->dispatch('validation-errors', ['errors' => array_values($this->errorItems)]);
    //         return;
    //     }

    //     $this->validate([
    //         'invoice_number' => 'required|string',
    //         'date' => 'required|date',
    //         'supplier_id' => 'required|exists:partners,id',
    //         'expedition_fee' => 'numeric|min:0',
    //         'farm_id' => 'required|exists:farms,id',
    //         'items' => 'required|array|min:1',
    //         'items.*.supply_id' => 'required|exists:supplies,id',
    //         'items.*.quantity' => 'required|numeric|min:0.01', // Perubahan di sini
    //         'items.*.price_per_unit' => 'required|numeric|min:0',
    //         'items.*.unit_id' => 'required|exists:units,id',
    //     ]);

    //     DB::beginTransaction();

    //     try {
    //         $batchData = [
    //             'invoice_number' => $this->invoice_number,
    //             'date' => $this->date,
    //             'supplier_id' => $this->supplier_id,
    //             'expedition_id' => $this->expedition_id ?? null,
    //             'expedition_fee' => $this->expedition_fee ?? 0,
    //             'created_by' => auth()->id(),
    //             'updated_by' => auth()->id(),
    //         ];

    //         $batch = SupplyPurchaseBatch::updateOrCreate(
    //             ['id' => $this->pembelianId],
    //             $batchData
    //         );

    //         // Build unique keys for existing and new items (supply_id + unit_id)
    //         $existingItemKeys = $batch->supplyPurchases->map(function($item) {
    //             return $item->supply_id . '-' . $item->original_unit;
    //         })->toArray();
    //         $newItemKeys = collect($this->items)->map(function($item) {
    //             return $item['supply_id'] . '-' . $item['unit_id'];
    //         })->toArray();

    //         $itemsToDelete = $batch->supplyPurchases->filter(function($purchase) use ($newItemKeys) {
    //             $key = $purchase->supply_id . '-' . $purchase->original_unit;
    //             return !in_array($key, $newItemKeys);
    //         });
    //         foreach ($itemsToDelete as $purchase) {
    //             SupplyStock::where('supply_purchase_id', $purchase->id)->delete();
    //             $purchase->delete();
    //         }

    //         foreach ($this->items as $item) {
    //             $supply = Supply::findOrFail($item['supply_id']);
    //             $units = collect($supply->payload['conversion_units']);
    //             $selectedUnit = $units->firstWhere('unit_id', $item['unit_id']);
    //             $smallestUnit = $units->firstWhere('is_smallest', true);

    //             if (!$selectedUnit || !$smallestUnit) {
    //                 throw new \Exception("Invalid unit conversion for supply: {$supply->name}");
    //             }

    //             // Always convert to the smallest unit
    //             $convertedQuantity = ($item['quantity'] * $selectedUnit['value']) / $smallestUnit['value'];

    //             $purchase = SupplyPurchase::updateOrCreate(
    //                 [
    //                     'supply_purchase_batch_id' => $batch->id,
    //                     'supply_id' => $supply->id,
    //                     'original_unit' => $item['unit_id'], // make unique per supply+unit
    //                 ],
    //                 [
    //                     'supply_purchase_batch_id' => $batch->id,
    //                     'farm_id' => $this->farm_id,
    //                     'supply_id' => $supply->id,
    //                     'quantity' => $item['quantity'], // original
    //                     'original_quantity' => $item['quantity'],
    //                     'original_unit' => $item['unit_id'],
    //                     'price_per_unit' => $item['price_per_unit'],
    //                     'created_by' => auth()->id(),
    //                     'updated_by' => auth()->id(),
    //                 ]
    //             );

    //             SupplyStock::updateOrCreate(
    //                 [
    //                     'farm_id' => $this->farm_id,
    //                     'supply_id' => $supply->id,
    //                     'supply_purchase_id' => $purchase->id,
    //                 ],
    //                 [
    //                     'date' => $this->date,
    //                     'source_id' => $purchase->id,
    //                     'amount' => $convertedQuantity, // always smallest unit
    //                     'available' => DB::raw('amount - used - quantity_mutated'),
    //                     'quantity_in' => $convertedQuantity, // always smallest unit
    //                     'created_by' => auth()->id(),
    //                     'updated_by' => auth()->id(),
    //                 ]
    //             );

    //             // Update CurrentSupply
    //             $currentStock = CurrentSupply::where('item_id', $supply->id)
    //                                         ->where('farm_id',$this->farm_id)
    //                                         ->first();

    //             if ($currentStock) {
    //                 // Update existing stock
    //                 $currentStock->quantity += $item['qty'];
    //                 // $currentStock->available_quantity += $itemData['qty'];
    //                 $currentStock->save();
    //             } else {
    //                 // Create new stock entry
    //                 $currentStock = CurrentSupply::create([
    //                     'item_id' => $supply->id,
    //                     'farm_id' => $this->farm_id,
    //                     'unit_id' => $supply->payload['unit_id'],
    //                     'quantity' => $item['quantity'],
    //                     'created_by' => auth()->user()->id,
    //                     // Other fields for CurrentStock
    //                 ]);
    //             }
    //         }

    //         // After saving all items, store details in batch payload
    //         $batchPayload = [
    //             'items' => collect($this->items)->map(function ($item) {
    //                 return [
    //                     'supply_id' => $item['supply_id'],
    //                     'quantity' => $item['quantity'],
    //                     'unit_id' => $item['unit_id'],
    //                     'price_per_unit' => $item['price_per_unit'],
    //                     'available_units' => $item['available_units'] ?? [],
    //                 ];
    //             })->toArray(),
    //             'farm_id' => $this->farm_id,
    //             'supplier_id' => $this->supplier_id,
    //             'expedition_id' => $this->expedition_id,
    //             'expedition_fee' => $this->expedition_fee,
    //             'date' => $this->date,
    //         ];
    //         $batch->payload = $batchPayload;
    //         $batch->save();

    //         DB::commit();

    //         $this->dispatch('success', 'Pembelian supply berhasil ' . ($this->pembelianId ? 'diperbarui' : 'disimpan'));
    //         $this->close();
    //     } catch (ValidationException $e) {
    //         $this->dispatch('validation-errors', ['errors' => $e->validator->errors()->all()]);
    //         $this->setErrorBag($e->validator->errors());
    //     } catch (\Exception $e) {
    //         DB::rollBack();

    //         logger()->error('Error saat menyimpan data pembelian supply', [
    //             'message' => $e->getMessage(),
    //             'file' => $e->getFile(),
    //             'line' => $e->getLine(),
    //             'trace' => $e->getTraceAsString(),
    //         ]);

    //         // $this->dispatch('error', 'Terjadi kesalahan saat ' . ($this->pembelianId ? 'memperbarui' : 'menyimpan') . ' data. ' . $e->getMessage());
    //         $this->dispatch('error', 'Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

    //     }

    // }

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

            if ($supply && isset($supply->payload['conversion_units'])) {
                $units = collect($supply->payload['conversion_units']);

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
        if (!$supply || empty($supply->payload['conversion_units'])) return;

        $units = collect($supply->payload['conversion_units']);
        $selectedUnit = $units->firstWhere('unit_id', $unitId);
        $smallestUnit = $units->firstWhere('is_smallest', true);

        if ($selectedUnit && $smallestUnit) {
            // Convert to smallest unit
            $this->items[$index]['converted_quantity'] = ($quantity * $selectedUnit['value']) / $smallestUnit['value'];
        }
    }

    protected function convertToSmallestUnit($supply, $quantity, $unitId)
    {
        $units = collect($supply->payload['conversion_units'] ?? []);

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

            $batch = SupplyPurchaseBatch::with(['supplyPurchases.supplyStocks', 'supplyPurchases.supply'])->findOrFail($batchId);

            // Track quantities to adjust in CurrentSupply by farm and supply
            $adjustments = [];

            // Track all related records for the audit trail
            $relatedRecords = [
                'supplyPurchases' => [],
                'supplyStocks' => [],
                'currentSupplies' => []
            ];

            // Loop semua SupplyPurchase di dalam batch
            foreach ($batch->supplyPurchases as $purchase) {
                // Add purchase to related records
                $relatedRecords['supplyPurchases'][] = [
                    'id' => $purchase->id,
                    'supply_id' => $purchase->supply_id,
                    'farm_id' => $purchase->farm_id,
                    'quantity' => $purchase->quantity,
                    'price_per_unit' => $purchase->price_per_unit
                ];

                // Get all related stocks for this purchase
                $supplyStocks = $purchase->supplyStocks;

                if ($supplyStocks->isEmpty()) {
                    // If no stocks found, try to find directly
                    $supplyStocks = SupplyStock::where('supply_purchase_id', $purchase->id)->get();
                }

                foreach ($supplyStocks as $supplyStock) {
                    // Add stock to related records
                    $relatedRecords['supplyStocks'][] = [
                        'id' => $supplyStock->id,
                        'farm_id' => $supplyStock->farm_id,
                        'supply_id' => $supplyStock->supply_id,
                        'quantity_in' => $supplyStock->quantity_in,
                        'quantity_used' => $supplyStock->quantity_used,
                        'quantity_mutated' => $supplyStock->quantity_mutated
                    ];

                    // Validasi: tidak bisa hapus jika sudah dipakai atau dimutasi
                    if (($supplyStock->quantity_used ?? 0) > 0 || ($supplyStock->quantity_mutated ?? 0) > 0) {
                        $this->dispatch('error', 'Tidak dapat menghapus batch. Beberapa stok sudah digunakan atau dimutasi.');
                        DB::rollBack();
                        return;
                    }

                    // Get the farm and supply IDs for this stock
                    $farmId = $supplyStock->farm_id;
                    $supplyId = $supplyStock->supply_id;

                    // Get the supply with its conversion data
                    $supply = $purchase->supply;
                    if (!$supply) {
                        $supply = Supply::find($supplyId);
                    }

                    if (!$supply) {
                        Log::error("Supply not found for ID: {$supplyId} when deleting purchase batch");
                        continue;
                    }

                    // Get unit conversion information
                    $conversionUnits = collect($supply->payload['conversion_units'] ?? []);
                    $smallestUnitId = $conversionUnits->firstWhere('is_smallest', true)['unit_id'] ?? null;

                    // Create a unique key for this farm+supply combination
                    $key = "{$farmId}_{$supplyId}";

                    // Calculate the quantity to deduct in the smallest unit
                    $quantityToDeduct = $supplyStock->quantity_in;

                    // Store the adjustment information
                    if (!isset($adjustments[$key])) {
                        $adjustments[$key] = [
                            'farm_id' => $farmId,
                            'supply_id' => $supplyId,
                            'smallest_unit_id' => $smallestUnitId,
                            'quantity_to_deduct' => 0,
                            'supply' => $supply
                        ];
                    }

                    // Add this stock's quantity to the total to deduct
                    $adjustments[$key]['quantity_to_deduct'] += $quantityToDeduct;

                    // Delete the stock
                    $supplyStock->delete();
                }

                // Delete the purchase
                $purchase->delete();
            }

            // Update CurrentSupply records for each affected farm+supply combination
            foreach ($adjustments as $key => $adjustment) {
                $farmId = $adjustment['farm_id'];
                $supplyId = $adjustment['supply_id'];
                $quantityToDeduct = $adjustment['quantity_to_deduct'];
                $supply = $adjustment['supply'];

                // Get the current supply record
                $currentSupply = CurrentSupply::where('farm_id', $farmId)
                    ->where('item_id', $supplyId)
                    ->first();

                if ($currentSupply) {
                    // Add to related records before updating
                    $relatedRecords['currentSupplies'][] = [
                        'id' => $currentSupply->id,
                        'farm_id' => $currentSupply->farm_id,
                        'item_id' => $currentSupply->item_id,
                        'before_quantity' => $currentSupply->quantity
                    ];

                    // Get the unit conversion data
                    $conversionUnits = collect($supply->payload['conversion_units'] ?? []);
                    $currentUnitId = $currentSupply->unit_id;
                    $smallestUnitId = $adjustment['smallest_unit_id'];

                    // Convert the quantity to deduct from smallest unit to the current supply's unit
                    $currentUnitData = $conversionUnits->firstWhere('unit_id', $currentUnitId);
                    $smallestUnitData = $conversionUnits->firstWhere('unit_id', $smallestUnitId);

                    if ($currentUnitData && $smallestUnitData) {
                        // Calculate the conversion ratio
                        $currentToSmallestRatio = $smallestUnitData['value'] / $currentUnitData['value'];

                        // Convert quantity from smallest to current unit
                        $quantityToDeductInCurrentUnit = $quantityToDeduct / $currentToSmallestRatio;

                        // Update the current supply
                        $newQuantity = max(0, $currentSupply->quantity - $quantityToDeductInCurrentUnit);

                        $currentSupply->update([
                            'quantity' => $newQuantity,
                            'updated_by' => auth()->id()
                        ]);

                        // Add the "after" quantity to the related record
                        $relatedRecords['currentSupplies'][count($relatedRecords['currentSupplies']) - 1]['after_quantity'] = $newQuantity;
                        $relatedRecords['currentSupplies'][count($relatedRecords['currentSupplies']) - 1]['deducted'] = $quantityToDeductInCurrentUnit;

                        Log::info("Updated CurrentSupply for farm {$farmId}, supply {$supplyId}: deducted {$quantityToDeductInCurrentUnit} units, new quantity: {$newQuantity}");
                    } else {
                        // Fallback: direct update without conversion if unit data not found
                        $oldQuantity = $currentSupply->quantity;
                        $currentSupply->decrement('quantity', $quantityToDeduct);

                        // Add the "after" quantity to the related record
                        $relatedRecords['currentSupplies'][count($relatedRecords['currentSupplies']) - 1]['after_quantity'] = $currentSupply->fresh()->quantity;
                        $relatedRecords['currentSupplies'][count($relatedRecords['currentSupplies']) - 1]['deducted'] = $quantityToDeduct;

                        Log::warning("Unit conversion data not found for supply {$supplyId}, used direct deduction");
                    }
                }
            }

            // Log to audit trail before final deletion
            AuditTrailService::logCascadingDeletion(
                $batch,
                $relatedRecords,
                "User initiated deletion of supply purchase batch"
            );

            // Hapus batch setelah semua data terkait dihapus dan tercatat
            $batch->delete();

            DB::commit();
            $this->dispatch('success', 'Data berhasil dihapus dan stok telah diperbarui');
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

    // Tanpa AuditTrail
    // public function deleteSupplyPurchaseBatch($batchId)
    // {
    //     try {
    //         DB::beginTransaction();

    //         $batch = SupplyPurchaseBatch::with(['supplyPurchases.supplyStocks', 'supplyPurchases.supply'])->findOrFail($batchId);

    //         // Track quantities to adjust in CurrentSupply by farm and supply
    //         $adjustments = [];

    //         // Loop semua SupplyPurchase di dalam batch
    //         foreach ($batch->supplyPurchases as $purchase) {
    //             // Get all related stocks for this purchase
    //             $supplyStocks = $purchase->supplyStocks;

    //             if ($supplyStocks->isEmpty()) {
    //                 // If no stocks found, try to find directly
    //                 $supplyStocks = SupplyStock::where('supply_purchase_id', $purchase->id)->get();
    //             }

    //             foreach ($supplyStocks as $supplyStock) {
    //                 // Validasi: tidak bisa hapus jika sudah dipakai atau dimutasi
    //                 if (($supplyStock->quantity_used ?? 0) > 0 || ($supplyStock->quantity_mutated ?? 0) > 0) {
    //                     $this->dispatch('error', 'Tidak dapat menghapus batch. Beberapa stok sudah digunakan atau dimutasi.');
    //                     DB::rollBack();
    //                     return;
    //                 }

    //                 // Get the farm and supply IDs for this stock
    //                 $farmId = $supplyStock->farm_id;
    //                 $supplyId = $supplyStock->supply_id;

    //                 // Get the supply with its conversion data
    //                 $supply = $purchase->supply;
    //                 if (!$supply) {
    //                     $supply = Supply::find($supplyId);
    //                 }

    //                 if (!$supply) {
    //                     Log::error("Supply not found for ID: {$supplyId} when deleting purchase batch");
    //                     continue;
    //                 }

    //                 // Get unit conversion information
    //                 $conversionUnits = collect($supply->payload['conversion_units'] ?? []);
    //                 $smallestUnitId = $conversionUnits->firstWhere('is_smallest', true)['unit_id'] ?? null;

    //                 // Create a unique key for this farm+supply combination
    //                 $key = "{$farmId}_{$supplyId}";

    //                 // Calculate the quantity to deduct in the smallest unit
    //                 $quantityToDeduct = $supplyStock->quantity_in;

    //                 // Store the adjustment information
    //                 if (!isset($adjustments[$key])) {
    //                     $adjustments[$key] = [
    //                         'farm_id' => $farmId,
    //                         'supply_id' => $supplyId,
    //                         'smallest_unit_id' => $smallestUnitId,
    //                         'quantity_to_deduct' => 0,
    //                         'supply' => $supply
    //                     ];
    //                 }

    //                 // Add this stock's quantity to the total to deduct
    //                 $adjustments[$key]['quantity_to_deduct'] += $quantityToDeduct;

    //                 // Delete the stock
    //                 $supplyStock->delete();
    //             }

    //             // Delete the purchase
    //             $purchase->delete();
    //         }

    //         // Update CurrentSupply records for each affected farm+supply combination
    //         foreach ($adjustments as $key => $adjustment) {
    //             $farmId = $adjustment['farm_id'];
    //             $supplyId = $adjustment['supply_id'];
    //             $quantityToDeduct = $adjustment['quantity_to_deduct'];
    //             $supply = $adjustment['supply'];

    //             // Get the current supply record
    //             $currentSupply = CurrentSupply::where('farm_id', $farmId)
    //                 ->where('item_id', $supplyId)
    //                 ->first();

    //             if ($currentSupply) {
    //                 // Get the unit conversion data
    //                 $conversionUnits = collect($supply->payload['conversion_units'] ?? []);
    //                 $currentUnitId = $currentSupply->unit_id;
    //                 $smallestUnitId = $adjustment['smallest_unit_id'];

    //                 // Convert the quantity to deduct from smallest unit to the current supply's unit
    //                 $currentUnitData = $conversionUnits->firstWhere('unit_id', $currentUnitId);
    //                 $smallestUnitData = $conversionUnits->firstWhere('unit_id', $smallestUnitId);

    //                 if ($currentUnitData && $smallestUnitData) {
    //                     // Calculate the conversion ratio
    //                     $currentToSmallestRatio = $smallestUnitData['value'] / $currentUnitData['value'];

    //                     // Convert quantity from smallest to current unit
    //                     $quantityToDeductInCurrentUnit = $quantityToDeduct / $currentToSmallestRatio;

    //                     // Update the current supply
    //                     $newQuantity = max(0, $currentSupply->quantity - $quantityToDeductInCurrentUnit);

    //                     $currentSupply->update([
    //                         'quantity' => $newQuantity,
    //                         'updated_by' => auth()->id()
    //                     ]);

    //                     Log::info("Updated CurrentSupply for farm {$farmId}, supply {$supplyId}: deducted {$quantityToDeductInCurrentUnit} units, new quantity: {$newQuantity}");
    //                 } else {
    //                     // Fallback: direct update without conversion if unit data not found
    //                     $currentSupply->decrement('quantity', $quantityToDeduct);
    //                     Log::warning("Unit conversion data not found for supply {$supplyId}, used direct deduction");
    //                 }
    //             }
    //         }

    //         // Hapus batch setelah semua data terkait dihapus
    //         $batch->delete();

    //         DB::commit();
    //         $this->dispatch('success', 'Data berhasil dihapus dan stok telah diperbarui');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error("Error deleting supply purchase batch: " . $e->getMessage(), [
    //             'batch_id' => $batchId,
    //             'file' => $e->getFile(),
    //             'line' => $e->getLine(),
    //             'trace' => $e->getTraceAsString()
    //         ]);
    //         $this->dispatch('error', 'Terjadi kesalahan saat menghapus data: ' . $e->getMessage());
    //     }
    // }

    // public function deleteSupplyPurchaseBatch($batchId)
    // {
    //     try {
    //         DB::beginTransaction();

    //         $batch = SupplyPurchaseBatch::with('supplyPurchases')->findOrFail($batchId);

    //         // Loop semua SupplyPurchase di dalam batch
    //         foreach ($batch->supplyPurchases as $purchase) {
    //             $supplyStock = SupplyStock::where('supply_purchase_id', $purchase->id)->first();

    //             // Validasi: tidak bisa hapus jika sudah dipakai atau dimutasi
    //             if (($supplyStock->quantity_used ?? 0) > 0 || ($supplyStock->quantity_mutated ?? 0) > 0) {
    //                 $this->dispatch('error', 'Tidak dapat menghapus batch. Beberapa stok sudah digunakan atau dimutasi.');
    //                 return;
    //             }

    //             // Hapus SupplyStock & SupplyPurchase
    //             $supplyStock?->delete();
    //             $purchase->delete();
    //         }

    //         // Hapus batch setelah semua anaknya aman
    //         $batch->delete();

    //         DB::commit();
    //         $this->dispatch('success', 'Data berhasil dihapus');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //     }
    // }

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
                $unit_id = $item->original_unit !== null ? (string)$item->original_unit : null;
                if ($supply && isset($supply->payload['conversion_units'])) {
                    $available_units = collect($supply->payload['conversion_units'])->map(function ($unit) {
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
}

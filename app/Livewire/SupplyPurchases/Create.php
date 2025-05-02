<?php

namespace App\Livewire\SupplyPurchases;

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
use App\Models\Livestock;
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
    public $farm_id;
    public $pembelianId; // To hold the ID when editing
    public $showForm = false;
    public $edit_mode = false;
    public $errorItems = [];



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
        // Cek duplikasi kombinasi supply_id + unit_id di $this->items
        $uniqueKeys = [];
        foreach ($this->items as $idx => $item) {
            $key = $item['supply_id'] . '-' . $item['unit_id'];
            if (in_array($key, $uniqueKeys)) {
                $this->errorItems[$idx] = 'Jenis pakan dan satuan tidak boleh sama dengan baris lain.';
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
            'items.*.quantity' => 'required|numeric|min:0.01', // Perubahan di sini
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

            // Build unique keys for existing and new items (supply_id + unit_id)
            $existingItemKeys = $batch->supplyPurchases->map(function($item) {
                return $item->supply_id . '-' . $item->original_unit;
            })->toArray();
            $newItemKeys = collect($this->items)->map(function($item) {
                return $item['supply_id'] . '-' . $item['unit_id'];
            })->toArray();

            $itemsToDelete = $batch->supplyPurchases->filter(function($purchase) use ($newItemKeys) {
                $key = $purchase->supply_id . '-' . $purchase->original_unit;
                return !in_array($key, $newItemKeys);
            });
            foreach ($itemsToDelete as $purchase) {
                SupplyStock::where('supply_purchase_id', $purchase->id)->delete();
                $purchase->delete();
            }

            foreach ($this->items as $item) {
                $supply = Supply::findOrFail($item['supply_id']);
                $units = collect($supply->payload['conversion_units']);
                $selectedUnit = $units->firstWhere('unit_id', $item['unit_id']);
                $smallestUnit = $units->firstWhere('is_smallest', true);
            
                if (!$selectedUnit || !$smallestUnit) {
                    throw new \Exception("Invalid unit conversion for supply: {$supply->name}");
                }
            
                // Always convert to the smallest unit
                $convertedQuantity = ($item['quantity'] * $selectedUnit['value']) / $smallestUnit['value'];
            
                $purchase = SupplyPurchase::updateOrCreate(
                    [
                        'supply_purchase_batch_id' => $batch->id,
                        'supply_id' => $supply->id,
                        'original_unit' => $item['unit_id'], // make unique per supply+unit
                    ],
                    [
                        'supply_purchase_batch_id' => $batch->id,
                        'farm_id' => $this->farm_id,
                        'supply_id' => $supply->id,
                        'quantity' => $item['quantity'], // original
                        'original_quantity' => $item['quantity'],
                        'original_unit' => $item['unit_id'],
                        'price_per_unit' => $item['price_per_unit'],
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]
                );
            
                SupplyStock::updateOrCreate(
                    [
                        'farm_id' => $this->farm_id,
                        'supply_id' => $supply->id,
                        'supply_purchase_id' => $purchase->id,
                    ],
                    [
                        'date' => $this->date,
                        'source_id' => $purchase->id,
                        'amount' => $convertedQuantity, // always smallest unit
                        'available' => DB::raw('amount - used - quantity_mutated'),
                        'quantity_in' => $convertedQuantity, // always smallest unit
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]
                );
            }

            // After saving all items, store details in batch payload
            $batchPayload = [
                'items' => collect($this->items)->map(function ($item) {
                    return [
                        'supply_id' => $item['supply_id'],
                        'quantity' => $item['quantity'],
                        'unit_id' => $item['unit_id'],
                        'price_per_unit' => $item['price_per_unit'],
                        'available_units' => $item['available_units'] ?? [],
                    ];
                })->toArray(),
                'farm_id' => $this->farm_id,
                'supplier_id' => $this->supplier_id,
                'expedition_id' => $this->expedition_id,
                'expedition_fee' => $this->expedition_fee,
                'date' => $this->date,
            ];
            $batch->payload = $batchPayload;
            $batch->save();

            DB::commit();

            $this->dispatch('success', 'Pembelian pakan berhasil ' . ($this->pembelianId ? 'diperbarui' : 'disimpan'));
            $this->close();
        } catch (ValidationException $e) {
            $this->dispatch('validation-errors', ['errors' => $e->validator->errors()->all()]);
            $this->setErrorBag($e->validator->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('error', 'Terjadi kesalahan saat ' . ($this->pembelianId ? 'memperbarui' : 'menyimpan') . ' data. ' . $e->getMessage());
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
        $supply = Supply::where('status','active')->orderBy('name')->get();
        $farmIds = auth()->user()->farmOperators()->pluck('farm_id')->toArray();
        $farms = Farm::whereIn('id', $farmIds)->get(['id', 'name']);

        // dd($supply);
        return view('livewire.supply-purchases.create', [
            'vendors' => Partner::where('type','Supplier')->get(),
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

            $batch = SupplyPurchaseBatch::with('supplyPurchases')->findOrFail($batchId);

            // Loop semua SupplyPurchase di dalam batch
            foreach ($batch->supplyPurchases as $purchase) {
                $supplyStock = SupplyStock::where('supply_purchase_id', $purchase->id)->first();

                // Validasi: tidak bisa hapus jika sudah dipakai atau dimutasi
                if (($supplyStock->quantity_used ?? 0) > 0 || ($supplyStock->quantity_mutated ?? 0) > 0) {
                    $this->dispatch('error', 'Tidak dapat menghapus batch. Beberapa stok sudah digunakan atau dimutasi.');
                    return;
                }

                // Hapus SupplyStock & SupplyPurchase
                $supplyStock?->delete();
                $purchase->delete();
            }

            // Hapus batch setelah semua anaknya aman
            $batch->delete();

            DB::commit();
            $this->dispatch('success', 'Data berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
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

<?php

namespace App\Livewire\FeedPurchases;

use App\Services\AuditTrailService;

use App\Models\CurrentSupply;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Ekspedisi;
use  App\Models\Expedition;
use App\Models\Feed;
use App\Models\FeedPurchaseBatch;
use App\Models\FeedPurchase;
use App\Models\FeedStock;
use App\Models\Rekanan;
use App\Models\Partner;
use App\Models\Supply;
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
    public $livestock_id;
    public $pembelianId; // To hold the ID when editing
    public $showForm = false;
    public $edit_mode = false;
    public $errorItems = [];

    public bool $withHistory = false; // ← Tambahkan ini di atas class Livewire

    protected $listeners = [
        'deleteFeedPurchaseBatch' => 'deleteFeedPurchaseBatch',
        'updateDoNumber' => 'updateDoNumber',
        'showEditForm' => 'showEditForm',
        'showCreateForm' => 'showCreateForm',
        'cancel' => 'cancel',

    ];

    public function mount()
    {
        // $this->date = now()->toDateString();
        $this->items = [
            [
                'feed_id' => null,
                'quantity' => null,
                'unit' => null, // ← new: satuan yang dipilih user
                'price_per_unit' => null,
                'available_units' => [], // ← new: daftar satuan berdasarkan feed
            ]
        ];
        // $this->items = [
        //     ['feed_id' => '', 'quantity' => '', 'price_per_unit' => '']
        // ];
    }

    public function addItem()
    {
        // $this->items[] = ['feed_id' => '', 'quantity' => '', 'price_per_unit' => ''];
        $this->items[] = [
            'feed_id' => null,
            'quantity' => null,
            'unit' => null, // ← new: satuan yang dipilih user
            'price_per_unit' => null,
            'available_units' => [], // ← new: daftar satuan berdasarkan feed
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

        // Validasi kombinasi feed_id dan unit_id tidak boleh duplikat
        $uniqueKeys = [];
        foreach ($this->items as $idx => $item) {
            $key = $item['feed_id'] . '-' . $item['unit_id'];
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
            'livestock_id' => 'required|exists:livestocks,id',
            'items' => 'required|array|min:1',
            'items.*.feed_id' => 'required|exists:feeds,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.price_per_unit' => 'required|numeric|min:0',
            'items.*.unit_id' => 'required|exists:units,id',
        ]);

        DB::beginTransaction();

        try {
            $batch = FeedPurchaseBatch::updateOrCreate(
                ['id' => $this->pembelianId],
                [
                    'invoice_number' => $this->invoice_number,
                    'date' => $this->date,
                    'supplier_id' => $this->supplier_id,
                    'expedition_id' => $this->expedition_id ?? null,
                    'expedition_fee' => $this->expedition_fee ?? 0,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]
            );

            // Buat key yang digunakan untuk cek data yang tidak lagi dipakai
            $newItemKeys = collect($this->items)->map(fn($item) => $item['feed_id'] . '-' . $item['unit_id'])->toArray();

            foreach ($batch->feedPurchases as $purchase) {
                $key = $purchase->feed_id . '-' . $purchase->original_unit;

                if (!in_array($key, $newItemKeys)) {
                    FeedStock::where('feed_purchase_id', $purchase->id)->delete();

                    if ($this->withHistory) {
                        $purchase->delete(); // soft delete
                    } else {
                        $purchase->forceDelete(); // hard delete
                    }
                }
            }

            foreach ($this->items as $item) {
                $feed = Feed::findOrFail($item['feed_id']);
                $livestock = Livestock::findOrFail($this->livestock_id);

                $units = collect($feed->payload['conversion_units']);
                $selectedUnit = $units->firstWhere('unit_id', $item['unit_id']);
                $smallestUnit = $units->firstWhere('is_smallest', true);

                if (!$selectedUnit || !$smallestUnit) {
                    throw new \Exception("Invalid unit conversion for feed: {$feed->name}");
                }

                $convertedQuantity = ($item['quantity'] * $selectedUnit['value']) / $smallestUnit['value'];

                $purchase = FeedPurchase::updateOrCreate(
                    [
                        'feed_purchase_batch_id' => $batch->id,
                        'feed_id' => $feed->id,
                        'unit_id' => $item['unit_id'],
                    ],
                    [
                        'livestock_id' => $this->livestock_id,
                        'quantity' => $item['quantity'],
                        'converted_quantity' => $convertedQuantity,
                        'converted_unit' => $smallestUnit['unit_id'],
                        'price_per_unit' => $item['price_per_unit'],
                        'price_per_converted_unit' => $item['unit_id'] !== $smallestUnit['unit_id']
                            ? round($item['price_per_unit'] * ($smallestUnit['value'] / $selectedUnit['value']), 2)
                            : $item['price_per_unit'],
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                        'feed_purchase_batch_id' => $batch->id,
                        'feed_id' => $feed->id,
                        'unit_id' => $item['unit_id'],
                    ]
                );

                FeedStock::updateOrCreate(
                    [
                        'livestock_id' => $this->livestock_id,
                        'feed_id' => $feed->id,
                        'feed_purchase_id' => $purchase->id,
                    ],
                    [
                        'date' => $this->date,
                        'source_type' => 'purchase',
                        'source_id' => $purchase->id,
                        // 'amount' => $convertedQuantity,
                        // 'available' => DB::raw('amount - used - quantity_mutated'),
                        'quantity_in' => $convertedQuantity,
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]
                );

                // Hitung ulang CurrentSupply TANPA softdelete jika withHistory == false
                $currentQuantity = FeedPurchase::when(!$this->withHistory, function ($q) {
                    return $q->whereNull('deleted_at');
                })
                    ->where('livestock_id', $livestock->id)
                    ->where('feed_id', $feed->id)
                    ->sum('converted_quantity');

                CurrentSupply::updateOrCreate(
                    [
                        'livestock_id' => $livestock->id,
                        'farm_id' => $livestock->farm_id,
                        'coop_id' => $livestock->coop_id,
                        'item_id' => $feed->id,
                        'unit_id' => $feed->payload['unit_id'],
                        'type' => 'feed',
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
                    'feed_id' => $item['feed_id'],
                    'quantity' => $item['quantity'],
                    'unit_id' => $item['unit_id'],
                    'price_per_unit' => $item['price_per_unit'],
                    'available_units' => $item['available_units'] ?? [],
                ])->toArray(),
                'livestock_id' => $this->livestock_id,
                'supplier_id' => $this->supplier_id,
                'expedition_id' => $this->expedition_id,
                'expedition_fee' => $this->expedition_fee,
                'date' => $this->date,
            ];
            $batch->save();

            DB::commit();

            $this->dispatch('success', 'Pembelian pakan berhasil ' . ($this->pembelianId ? 'diperbarui' : 'disimpan'));
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
    //     // Cek duplikasi kombinasi feed_id + unit_id di $this->items
    //     $uniqueKeys = [];
    //     foreach ($this->items as $idx => $item) {
    //         $key = $item['feed_id'] . '-' . $item['unit_id'];
    //         if (in_array($key, $uniqueKeys)) {
    //             $this->errorItems[$idx] = 'Jenis pakan dan satuan tidak boleh sama dengan baris lain.';
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
    //         'livestock_id' => 'required|exists:livestocks,id',
    //         'items' => 'required|array|min:1',
    //         'items.*.feed_id' => 'required|exists:feeds,id',
    //         'items.*.quantity' => 'required|numeric|min:1',
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

    //         $batch = FeedPurchaseBatch::updateOrCreate(
    //             ['id' => $this->pembelianId],
    //             $batchData
    //         );

    //         // Build unique keys for existing and new items (feed_id + unit_id)
    //         $existingItemKeys = $batch->feedPurchases->map(function($item) {
    //             return $item->feed_id . '-' . $item->original_unit;
    //         })->toArray();
    //         $newItemKeys = collect($this->items)->map(function($item) {
    //             return $item['feed_id'] . '-' . $item['unit_id'];
    //         })->toArray();

    //         $itemsToDelete = $batch->feedPurchases->filter(function($purchase) use ($newItemKeys) {
    //             $key = $purchase->feed_id . '-' . $purchase->original_unit;
    //             return !in_array($key, $newItemKeys);
    //         });
    //         foreach ($itemsToDelete as $purchase) {
    //             FeedStock::where('feed_purchase_id', $purchase->id)->delete();
    //             $purchase->delete();
    //         }

    //         foreach ($this->items as $item) {
    //             $feed = Feed::findOrFail($item['feed_id']);
    //             $livestock = Livestock::findOrFail($this->livestock_id);
    //             $units = collect($feed->payload['conversion_units']);
    //             $selectedUnit = $units->firstWhere('unit_id', $item['unit_id']);
    //             $smallestUnit = $units->firstWhere('is_smallest', true);

    //             if (!$selectedUnit || !$smallestUnit) {
    //                 throw new \Exception("Invalid unit conversion for feed: {$feed->name}");
    //             }

    //             // Always convert to the smallest unit
    //             $convertedQuantity = ($item['quantity'] * $selectedUnit['value']) / $smallestUnit['value'];

    //             $purchase = FeedPurchase::updateOrCreate(
    //                 [
    //                     'feed_purchase_batch_id' => $batch->id,
    //                     'feed_id' => $feed->id,
    //                     'unit_id' => $item['unit_id'], // tetap untuk identifikasi unik
    //                 ],
    //                 [
    //                     'feed_purchase_batch_id' => $batch->id,
    //                     'livestock_id' => $this->livestock_id,
    //                     'feed_id' => $feed->id,
    //                     'unit_id' => $item['unit_id'],                 // asli
    //                     'quantity' => $item['quantity'],               // asli
    //                     'converted_quantity' => $convertedQuantity,    // hasil konversi
    //                     'converted_unit' => $smallestUnit['unit_id'],  // unit terkecil
    //                     'price_per_unit' => $item['price_per_unit'],
    //                     'created_by' => auth()->id(),
    //                     'updated_by' => auth()->id(),
    //                 ]
    //             );
    //             FeedStock::updateOrCreate(
    //                 [
    //                     'livestock_id' => $this->livestock_id,
    //                     'feed_id' => $feed->id,
    //                     'feed_purchase_id' => $purchase->id,
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

    //             $currentQuantity = FeedPurchase::where('livestock_id', $livestock->id)
    //                 ->where('feed_id', $feed->id)
    //                 ->whereNull('deleted_at')
    //                 ->sum('converted_quantity');

    //             CurrentSupply::updateOrCreate(
    //                 [
    //                     'livestock_id' => $livestock->id,
    //                     'farm_id' => $livestock->farm_id,
    //                     'coop_id' => $livestock->coop_id,
    //                     'item_id' => $feed->id,
    //                     'unit_id' => $feed->payload['unit_id'],
    //                 ],
    //                 [
    //                     'quantity' => $currentQuantity,
    //                     'status' => 'active',
    //                     'created_by' => auth()->id(),
    //                     'updated_by' => auth()->id(),
    //                 ]
    //             );
    //         }

    //         // After saving all items, store details in batch payload
    //         $batchPayload = [
    //             'items' => collect($this->items)->map(function ($item) {
    //                 return [
    //                     'feed_id' => $item['feed_id'],
    //                     'quantity' => $item['quantity'],
    //                     'unit_id' => $item['unit_id'],
    //                     'price_per_unit' => $item['price_per_unit'],
    //                     'available_units' => $item['available_units'] ?? [],
    //                 ];
    //             })->toArray(),
    //             'livestock_id' => $this->livestock_id,
    //             'supplier_id' => $this->supplier_id,
    //             'expedition_id' => $this->expedition_id,
    //             'expedition_fee' => $this->expedition_fee,
    //             'date' => $this->date,
    //         ];
    //         $batch->payload = $batchPayload;
    //         $batch->save();

    //         DB::commit();

    //         $this->dispatch('success', 'Pembelian pakan berhasil ' . ($this->pembelianId ? 'diperbarui' : 'disimpan'));
    //         $this->close();
    //     } catch (ValidationException $e) {
    //         $this->dispatch('validation-errors', ['errors' => $e->validator->errors()->all()]);
    //         $this->setErrorBag($e->validator->errors());
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         $this->dispatch('error', 'Terjadi kesalahan saat ' . ($this->pembelianId ? 'memperbarui' : 'menyimpan') . ' data. ' . $e->getMessage());
    //     }
    // }


    // public function save()
    // {
    //     $this->validate([
    //         'invoice_number' => 'required|string',
    //         'date' => 'required|date',
    //         'supplier_id' => 'required|exists:partners,id',
    //         'expedition_fee' => 'numeric|min:0',
    //         'livestock_id' => 'required|exists:livestocks,id',
    //         'items' => 'required|array|min:1',
    //         'items.*.feed_id' => 'required|exists:feeds,id',
    //         'items.*.quantity' => 'required|numeric|min:1',
    //         'items.*.price_per_unit' => 'required|numeric|min:0',
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

    //         $batch = FeedPurchaseBatch::updateOrCreate(
    //             ['id' => $this->pembelianId],
    //             $batchData
    //         );

    //         // Sync FeedPurchase items
    //         $existingItemIds = $batch->feedPurchases->pluck('feed_id')->toArray();
    //         $newItemIds = collect($this->items)->pluck('feed_id')->toArray();

    //         // Delete items that are no longer present
    //         $itemsToDelete = $batch->feedPurchases->whereNotIn('feed_id', $newItemIds);
    //         foreach ($itemsToDelete as $purchase) {
    //             // Optionally handle related FeedStock entries
    //             FeedStock::where('feed_purchase_id', $purchase->id)->delete();
    //             $purchase->delete();
    //         }

    //         foreach ($this->items as $item) {
    //             $feedItem = Feed::findOrFail($item['feed_id']);
    //             $purchaseData = [
    //                 'feed_purchase_batch_id' => $batch->id,
    //                 'livestock_id' => $this->livestock_id,
    //                 'feed_id' => $feedItem->id,
    //                 'quantity' => $item['quantity'],
    //                 'price_per_unit' => $item['price_per_unit'],
    //                 'created_by' => auth()->id(),
    //                 'updated_by' => auth()->id(),
    //             ];

    //             $purchase = FeedPurchase::updateOrCreate(
    //                 [
    //                     'feed_purchase_batch_id' => $batch->id,
    //                     'feed_id' => $feedItem->id,
    //                 ],
    //                 $purchaseData
    //             );

    //             // Update or Create FeedStock
    //             FeedStock::updateOrCreate(
    //                 [
    //                     'livestock_id' => $this->livestock_id,
    //                     'feed_id' => $feedItem->id,
    //                     'feed_purchase_id' => $purchase->id,
    //                 ],
    //                 [
    //                     'date' => $this->date,
    //                     'source_id' => $purchase->id,
    //                     'amount' => $item['quantity'] * $feedItem->conversion,
    //                     'available' => DB::raw('amount - used - quantity_mutated'),
    //                     'quantity_in' => $item['quantity'] * $feedItem->conversion,
    //                     'created_by' => auth()->id(),
    //                     'updated_by' => auth()->id(),
    //                 ]
    //             );
    //         }

    //         DB::commit();

    //         $this->dispatch('success', 'Pembelian pakan berhasil ' . ($this->pembelianId ? 'diperbarui' : 'disimpan'));
    //         $this->close();

    //     } catch (ValidationException $e) {
    //         $this->dispatch('validation-errors', ['errors' => $e->validator->errors()->all()]);
    //         $this->setErrorBag($e->validator->errors());
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         $this->dispatch('error', 'Terjadi kesalahan saat ' . ($this->pembelianId ? 'memperbarui' : 'menyimpan') . ' data. ' . $e->getMessage());
    //     } finally {
    //         // $this->reset();
    //     }
    // }

    // public function save()
    // {
    //     $this->validate([
    //         'invoice_number' => 'required|string',
    //         'date' => 'required|date',
    //         'supplier_id' => 'required|exists:partners,id',
    //         'expedition_fee' => 'numeric|min:0',
    //         'livestock_id' => 'required|exists:livestocks,id',
    //         'items' => 'required|array|min:1',
    //         'items.*.feed_id' => 'required|exists:feeds,id',
    //         'items.*.quantity' => 'required|numeric|min:1',
    //         'items.*.price_per_unit' => 'required|numeric|min:0',
    //     ]);

    //     // dd($this->all());

    //     DB::beginTransaction();

    //     try {
    //         $batch = FeedPurchaseBatch::create([
    //             'id' => Str::uuid(),
    //             'invoice_number' => $this->invoice_number,
    //             'date' => $this->date,
    //             'supplier_id' => $this->supplier_id,
    //             'expedition_id' => $this->expedition_id ?? null,
    //             'expedition_fee' => $this->expedition_fee ?? 0,
    //             'created_by' => auth()->id(),
    //         ]);

    //         foreach ($this->items as $item) {
    //             $feedItem = Feed::where('id',$item['feed_id'])->firstOrFail();
    //             $purchase = FeedPurchase::create([
    //                 // 'id' => Str::uuid(),
    //                 'feed_purchase_batch_id' => $batch->id,
    //                 'livestock_id' => $this->livestock_id,
    //                 'feed_id' => $feedItem->id,
    //                 'quantity' => $item['quantity'],
    //                 // 'quantity' => $item['quantity'] * $feedItem->conversion,
    //                 'price_per_unit' => $item['price_per_unit'],
    //                 'created_by' => auth()->id(),
    //             ]);

    //             // Simpan ke feed_stocks
    //             FeedStock::create([
    //                 // 'id' => Str::uuid(),
    //                 'livestock_id' => $this->livestock_id,
    //                 'feed_id' => $feedItem->id,
    //                 'feed_purchase_id' => $purchase->id,
    //                 'date' => $this->date,
    //                 'source_id' => $purchase->id, // purchase sebagai sumber
    //                 'amount' => $item['quantity'] * $feedItem->conversion,
    //                 'used' => 0,
    //                 'available' => $item['quantity'] * $feedItem->conversion,
    //                 'quantity_in' => $item['quantity'] * $feedItem->conversion,
    //                 'quantity_used' => 0,
    //                 'quantity_mutated' => 0,
    //                 'created_by' => auth()->id(),
    //             ]);
    //         }

    //         DB::commit();

    //         $this->dispatch('success', 'Pembelian pakan berhasil disimpan');
    //         $this->close();

    //         // return redirect()->route('feed-purchases.index');

    //     } catch (ValidationException $e) {
    //         $this->dispatch('validation-errors', ['errors' => $e->validator->errors()->all()]);
    //         $this->setErrorBag($e->validator->errors());
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         $this->dispatch('error', 'Terjadi kesalahan saat memperbarui data. ' . $e->getMessage());
    //     } finally {
    //         // $this->reset();
    //     }
    // }

    public function resetForm()
    {
        $this->reset();
        $this->items = [
            [
                'feed_id' => null,
                'quantity' => null,
                'unit' => null, // ← new: satuan yang dipilih user
                'price_per_unit' => null,
                'available_units' => [], // ← new: daftar satuan berdasarkan feed
            ],
            // ['feed_id' => '', 'quantity' => 1, 'price_per_unit' => 0],
        ];
    }

    public function updatedItems($value, $key)
    {
        [$index, $field] = explode('.', $key);

        if ($field === 'feed_id') {
            $feed = Feed::find($value);

            if ($feed && isset($feed->payload['conversion_units'])) {
                $units = collect($feed->payload['conversion_units']);

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
        $feedId = $this->items[$index]['feed_id'] ?? null;

        if (!$unitId || !$quantity || !$feedId) return;

        $feed = Feed::find($feedId);
        if (!$feed || empty($feed->payload['conversion_units'])) return;

        $units = collect($feed->payload['conversion_units']);
        $selectedUnit = $units->firstWhere('unit_id', $unitId);
        $smallestUnit = $units->firstWhere('is_smallest', true);

        if ($selectedUnit && $smallestUnit) {
            // Convert to smallest unit
            $this->items[$index]['converted_quantity'] = ($quantity * $selectedUnit['value']) / $smallestUnit['value'];
        }
    }

    // public function updateUnitConversion($feedId, $index)
    // {
    //     $feed = Feed::find($feedId);

    //     if (!$feed || empty($feed->payload['conversion_units'])) {
    //         $this->items[$index]['available_units'] = [];
    //         return;
    //     }

    //     $this->items[$index]['available_units'] = collect($feed->payload['conversion_units'])
    //         ->map(function ($unit) {
    //             $unitModel = Unit::find($unit['unit_id']);
    //             return [
    //                 'unit_id' => $unit['unit_id'],
    //                 'label' => $unitModel?->name ?? 'Unknown',
    //                 'value' => $unit['value'],
    //                 'is_smallest' => $unit['is_smallest'] ?? false,
    //             ];
    //         })
    //         ->values()
    //         ->toArray();

    //     // Default pilih unit yang is_smallest
    //     $defaultUnit = collect($this->items[$index]['available_units'])->firstWhere('is_smallest', true);
    //     if ($defaultUnit) {
    //         $this->items[$index]['unit_id'] = $defaultUnit['unit_id'];
    //     }
    // }


    protected function convertToSmallestUnit($feed, $quantity, $unitId)
    {
        $units = collect($feed->payload['conversion_units'] ?? []);

        $from = $units->firstWhere('unit_id', $unitId);
        $smallest = $units->firstWhere('is_smallest', true);

        if (!$from || !$smallest || $from['value'] == 0 || $smallest['value'] == 0) {
            return $quantity; // fallback tanpa konversi
        }

        // return ($quantity * $from['value']) / $smallest['value'];
        dd([
            'quantity' => $quantity,
            'value' => $from['value'],
            'smallest' => $smallest['value'],
        ]);
        // dd(($quantity * $from['value']) / $smallest['value']);
    }




    public function render()
    {
        return view('livewire.feed-purchases.create', [
            'vendors' => Partner::where('type', 'Supplier')->get(),
            'expeditions' => Expedition::all(),
            'feedItems' => Feed::all(),
            'livestocks' => Livestock::whereHas('farm.farmOperators', function ($query) {
                $query->where('user_id', auth()->id());
            })->get(),
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
        // $this->dispatch('closeForm');
        $this->resetForm();
        $this->showForm = false;
        $this->dispatch('show-datatable');
    }

    public function deleteFeedPurchaseBatch($batchId)
    {
        try {
            DB::beginTransaction();

            $batch = FeedPurchaseBatch::with(['feedPurchases.feedStocks', 'feedPurchases.feed'])->findOrFail($batchId);

            // Track quantities to adjust in CurrentFeed by farm and feed
            $adjustments = [];

            // Track all related records for the audit trail
            $relatedRecords = [
                'feedPurchases' => [],
                'feedStocks' => [],
                'currentSupplies' => []
            ];

            // Loop semua FeedPurchase di dalam batch
            foreach ($batch->feedPurchases as $purchase) {
                // Add purchase to related records
                $relatedRecords['feedPurchases'][] = [
                    'id' => $purchase->id,
                    'feed_id' => $purchase->feed_id,
                    'farm_id' => $purchase->farm_id,
                    'quantity' => $purchase->quantity,
                    'price_per_unit' => $purchase->price_per_unit
                ];

                // Get all related stocks for this purchase
                $feedStocks = $purchase->feedStocks;

                if ($feedStocks->isEmpty()) {
                    // If no stocks found, try to find directly
                    $feedStocks = FeedStock::where('feed_purchase_id', $purchase->id)->get();
                }

                foreach ($feedStocks as $feedStock) {
                    // Add stock to related records
                    $relatedRecords['feedStocks'][] = [
                        'id' => $feedStock->id,
                        'farm_id' => $feedStock->farm_id,
                        'feed_id' => $feedStock->feed_id,
                        'quantity_in' => $feedStock->quantity_in,
                        'quantity_used' => $feedStock->quantity_used,
                        'quantity_mutated' => $feedStock->quantity_mutated
                    ];

                    // Validasi: tidak bisa hapus jika sudah dipakai atau dimutasi
                    if (($feedStock->quantity_used ?? 0) > 0 || ($feedStock->quantity_mutated ?? 0) > 0) {
                        $this->dispatch('error', 'Tidak dapat menghapus batch. Beberapa stok sudah digunakan atau dimutasi.');
                        DB::rollBack();
                        return;
                    }

                    // dd($feedStock);

                    // Get the farm and feed IDs for this stock
                    $farmId = $feedStock->livestock->farm_id;
                    $feedId = $feedStock->feed_id;

                    // Get the feed with its conversion data
                    $feed = $purchase->feed;
                    if (!$feed) {
                        $feed = Feed::find($feedId);
                    }

                    if (!$feed) {
                        Log::error("Feed not found for ID: {$feedId} when deleting purchase batch");
                        continue;
                    }

                    // Get unit conversion information
                    $conversionUnits = collect($feed->payload['conversion_units'] ?? []);
                    $smallestUnitId = $conversionUnits->firstWhere('is_smallest', true)['unit_id'] ?? null;

                    // Create a unique key for this farm+feed combination
                    $key = "{$farmId}_{$feedId}";

                    // Calculate the quantity to deduct in the smallest unit
                    $quantityToDeduct = $feedStock->quantity_in;

                    // Store the adjustment information
                    if (!isset($adjustments[$key])) {
                        $adjustments[$key] = [
                            'farm_id' => $farmId,
                            'feed_id' => $feedId,
                            'smallest_unit_id' => $smallestUnitId,
                            'quantity_to_deduct' => 0,
                            'feed' => $feed
                        ];
                    }

                    // Add this stock's quantity to the total to deduct
                    $adjustments[$key]['quantity_to_deduct'] += $quantityToDeduct;

                    // Delete the stock
                    $feedStock->delete();
                }

                // Delete the purchase
                $purchase->delete();
            }

            // Update CurrentFeed records for each affected farm+feed combination
            foreach ($adjustments as $key => $adjustment) {
                $farmId = $adjustment['farm_id'];
                $feedId = $adjustment['feed_id'];
                $quantityToDeduct = $adjustment['quantity_to_deduct'];
                $feed = $adjustment['feed'];

                // Get the current feed record
                $currentFeed = CurrentSupply::where('farm_id', $farmId)
                    ->where('item_id', $feedId)
                    ->first();

                // dd($currentFeed);

                if ($currentFeed) {
                    // Add to related records before updating
                    $relatedRecords['currentSupplies'][] = [
                        'id' => $currentFeed->id,
                        'farm_id' => $currentFeed->farm_id,
                        'item_id' => $currentFeed->item_id,
                        'before_quantity' => $currentFeed->quantity
                    ];

                    // Get the unit conversion data
                    $conversionUnits = collect($feed->payload['conversion_units'] ?? []);
                    $currentUnitId = $currentFeed->unit_id;
                    $smallestUnitId = $adjustment['smallest_unit_id'];

                    // Convert the quantity to deduct from smallest unit to the current feed's unit
                    $currentUnitData = $conversionUnits->firstWhere('unit_id', $currentUnitId);
                    $smallestUnitData = $conversionUnits->firstWhere('unit_id', $smallestUnitId);

                    if ($currentUnitData && $smallestUnitData) {
                        // Calculate the conversion ratio
                        $currentToSmallestRatio = $smallestUnitData['value'] / $currentUnitData['value'];

                        // Convert quantity from smallest to current unit
                        $quantityToDeductInCurrentUnit = $quantityToDeduct / $currentToSmallestRatio;

                        // Update the current feed
                        $newQuantity = max(0, $currentFeed->quantity - $quantityToDeductInCurrentUnit);

                        $currentFeed->update([
                            'quantity' => $newQuantity,
                            'updated_by' => auth()->id()
                        ]);

                        // Add the "after" quantity to the related record
                        $relatedRecords['currentSupplies'][count($relatedRecords['currentSupplies']) - 1]['after_quantity'] = $newQuantity;
                        $relatedRecords['currentSupplies'][count($relatedRecords['currentSupplies']) - 1]['deducted'] = $quantityToDeductInCurrentUnit;

                        Log::info("Updated CurrentFeed for farm {$farmId}, feed {$feedId}: deducted {$quantityToDeductInCurrentUnit} units, new quantity: {$newQuantity}");
                    } else {
                        // Fallback: direct update without conversion if unit data not found
                        $oldQuantity = $currentFeed->quantity;
                        $currentFeed->decrement('quantity', $quantityToDeduct);

                        // Add the "after" quantity to the related record
                        $relatedRecords['currentSupplies'][count($relatedRecords['currentSupplies']) - 1]['after_quantity'] = $currentFeed->fresh()->quantity;
                        $relatedRecords['currentSupplies'][count($relatedRecords['currentSupplies']) - 1]['deducted'] = $quantityToDeduct;

                        Log::warning("Unit conversion data not found for feed {$feedId}, used direct deduction");
                    }
                }
            }

            // Log to audit trail before final deletion
            AuditTrailService::logCascadingDeletion(
                $batch,
                $relatedRecords,
                "User initiated deletion of feed purchase batch"
            );

            // Hapus batch setelah semua data terkait dihapus dan tercatat
            $batch->delete();

            DB::commit();
            $this->dispatch('success', 'Data berhasil dihapus dan stok telah diperbarui');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error deleting feed purchase batch: " . $e->getMessage(), [
                'batch_id' => $batchId,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->dispatch('error', 'Terjadi kesalahan saat menghapus data: ' . $e->getMessage());
        }
    }

    // public function deleteFeedPurchaseBatch($batchId)
    // {
    //     try {
    //         DB::beginTransaction();

    //         $batch = FeedPurchaseBatch::with('feedPurchases')->findOrFail($batchId);

    //         // Loop semua FeedPurchase di dalam batch
    //         foreach ($batch->feedPurchases as $purchase) {
    //             $feedStock = FeedStock::where('feed_purchase_id', $purchase->id)->first();

    //             // Validasi: tidak bisa hapus jika sudah dipakai atau dimutasi
    //             if (($feedStock->quantity_used ?? 0) > 0 || ($feedStock->quantity_mutated ?? 0) > 0) {
    //                 $this->dispatch('error', 'Tidak dapat menghapus batch. Beberapa stok sudah digunakan atau dimutasi.');
    //                 return;
    //             }

    //             // Hapus FeedStock & FeedPurchase
    //             $feedStock?->delete();
    //             $purchase->delete();
    //         }

    //         // Hapus batch setelah semua anaknya aman
    //         $batch->delete();

    //         DB::commit();
    //         $this->dispatch('success', 'Data berhasil dihapus');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         $this->dispatch('error', 'Terjadi kesalahan saat menghapus data. ' . $e->getMessage());
    //     }
    // }

    public function updateDoNumber($transaksiId, $newNoSj)
    {
        $transaksiDetail = FeedPurchaseBatch::findOrFail($transaksiId);

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
        $pembelian = FeedPurchaseBatch::with(['feedPurchases.feed'])->find($id);

        if (!$pembelian) {
            $this->dispatch('error', 'Data pembelian tidak ditemukan');
            return;
        }

        // Initialize basic data
        $this->date = $pembelian->date;
        $this->invoice_number = $pembelian->invoice_number;
        $this->supplier_id = $pembelian->supplier_id;
        $this->expedition_id = $pembelian->expedition_id;
        $this->expedition_fee = $pembelian->expedition_fee;

        // Get data from feedPurchases
        $feedPurchaseData = $pembelian->feedPurchases->map(function ($purchase) {
            $feed = $purchase->feed;
            $available_units = [];
            $unit_id = $purchase->unit_id;

            if ($feed && isset($feed->payload['conversion_units'])) {
                $available_units = collect($feed->payload['conversion_units'])->map(function ($unit) {
                    $unitModel = \App\Models\Unit::find($unit['unit_id']);
                    return [
                        'unit_id' => (string)$unit['unit_id'],
                        'label' => $unitModel?->name ?? 'Unknown',
                        'value' => $unit['value'],
                        'is_smallest' => $unit['is_smallest'] ?? false,
                    ];
                })->toArray();
            }

            return [
                'livestock_id' => $purchase->livestock_id,
                'feed_id' => $purchase->feed_id,
                'quantity' => $purchase->quantity,
                'price_per_unit' => $purchase->price_per_unit,
                'unit_id' => $unit_id,
                'available_units' => $available_units,
            ];
        })->toArray();

        // Get data from payload
        $payloadData = $pembelian->payload['items'] ?? [];

        // Compare and use the most recent data
        $this->items = [];
        $this->livestock_id = null;

        if (!empty($payloadData)) {
            // Check if payload data is newer than feedPurchases
            $payloadItems = collect($payloadData)->map(function ($item) {
                return [
                    'feed_id' => $item['feed_id'],
                    'quantity' => $item['quantity'],
                    'unit_id' => $item['unit_id'],
                    'price_per_unit' => $item['price_per_unit'],
                    'available_units' => $item['available_units'] ?? [],
                ];
            })->toArray();

            $feedPurchaseItems = collect($feedPurchaseData)->map(function ($item) {
                return [
                    'feed_id' => $item['feed_id'],
                    'quantity' => $item['quantity'],
                    'unit_id' => $item['unit_id'],
                    'price_per_unit' => $item['price_per_unit'],
                ];
            })->toArray();

            // If payload data is different, update it
            if ($this->isDataDifferent($payloadItems, $feedPurchaseItems)) {
                $pembelian->payload = array_merge($pembelian->payload ?? [], [
                    'items' => $feedPurchaseData,
                    'livestock_id' => $feedPurchaseData[0]['livestock_id'] ?? null,
                    'supplier_id' => $this->supplier_id,
                    'expedition_id' => $this->expedition_id,
                    'expedition_fee' => $this->expedition_fee,
                    'date' => $this->date,
                ]);
                $pembelian->save();

                $this->items = $feedPurchaseData;
                $this->livestock_id = $feedPurchaseData[0]['livestock_id'] ?? null;
            } else {
                $this->items = $payloadItems;
                $this->livestock_id = $pembelian->payload['livestock_id'] ?? null;
            }
        } else {
            // If no payload data, use feedPurchases data
            $this->items = $feedPurchaseData;
            $this->livestock_id = $feedPurchaseData[0]['livestock_id'] ?? null;

            // Update payload with feedPurchases data
            $pembelian->payload = [
                'items' => $feedPurchaseData,
                'livestock_id' => $this->livestock_id,
                'supplier_id' => $this->supplier_id,
                'expedition_id' => $this->expedition_id,
                'expedition_fee' => $this->expedition_fee,
                'date' => $this->date,
            ];
            $pembelian->save();
        }

        $this->showForm = true;
        $this->edit_mode = true;
        $this->dispatch('hide-datatable');
    }

    private function isDataDifferent($payloadItems, $feedPurchaseItems)
    {
        if (count($payloadItems) !== count($feedPurchaseItems)) {
            return true;
        }

        foreach ($payloadItems as $index => $payloadItem) {
            $feedPurchaseItem = $feedPurchaseItems[$index] ?? null;

            if (!$feedPurchaseItem) {
                return true;
            }

            if (
                $payloadItem['feed_id'] !== $feedPurchaseItem['feed_id'] ||
                $payloadItem['quantity'] != $feedPurchaseItem['quantity'] ||
                $payloadItem['unit_id'] !== $feedPurchaseItem['unit_id'] ||
                $payloadItem['price_per_unit'] != $feedPurchaseItem['price_per_unit']
            ) {
                return true;
            }
        }

        return false;
    }
}

<?php

namespace App\Livewire\FeedPurchases;

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
        // Cek duplikasi kombinasi feed_id + unit_id di $this->items
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
            $batchData = [
                'invoice_number' => $this->invoice_number,
                'date' => $this->date,
                'supplier_id' => $this->supplier_id,
                'expedition_id' => $this->expedition_id ?? null,
                'expedition_fee' => $this->expedition_fee ?? 0,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ];

            $batch = FeedPurchaseBatch::updateOrCreate(
                ['id' => $this->pembelianId],
                $batchData
            );

            // Build unique keys for existing and new items (feed_id + unit_id)
            $existingItemKeys = $batch->feedPurchases->map(function($item) {
                return $item->feed_id . '-' . $item->original_unit;
            })->toArray();
            $newItemKeys = collect($this->items)->map(function($item) {
                return $item['feed_id'] . '-' . $item['unit_id'];
            })->toArray();

            $itemsToDelete = $batch->feedPurchases->filter(function($purchase) use ($newItemKeys) {
                $key = $purchase->feed_id . '-' . $purchase->original_unit;
                return !in_array($key, $newItemKeys);
            });
            foreach ($itemsToDelete as $purchase) {
                FeedStock::where('feed_purchase_id', $purchase->id)->delete();
                $purchase->delete();
            }

            foreach ($this->items as $item) {
                $feed = Feed::findOrFail($item['feed_id']);
                $units = collect($feed->payload['conversion_units']);
                $selectedUnit = $units->firstWhere('unit_id', $item['unit_id']);
                $smallestUnit = $units->firstWhere('is_smallest', true);
            
                if (!$selectedUnit || !$smallestUnit) {
                    throw new \Exception("Invalid unit conversion for feed: {$feed->name}");
                }
            
                // Always convert to the smallest unit
                $convertedQuantity = ($item['quantity'] * $selectedUnit['value']) / $smallestUnit['value'];
            
                $purchase = FeedPurchase::updateOrCreate(
                    [
                        'feed_purchase_batch_id' => $batch->id,
                        'feed_id' => $feed->id,
                        'original_unit' => $item['unit_id'], // make unique per feed+unit
                    ],
                    [
                        'feed_purchase_batch_id' => $batch->id,
                        'livestock_id' => $this->livestock_id,
                        'feed_id' => $feed->id,
                        'quantity' => $item['quantity'], // original
                        'original_quantity' => $item['quantity'],
                        'original_unit' => $item['unit_id'],
                        'price_per_unit' => $item['price_per_unit'],
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
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
                        'feed_id' => $item['feed_id'],
                        'quantity' => $item['quantity'],
                        'unit_id' => $item['unit_id'],
                        'price_per_unit' => $item['price_per_unit'],
                        'available_units' => $item['available_units'] ?? [],
                    ];
                })->toArray(),
                'livestock_id' => $this->livestock_id,
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
            'vendors' => Partner::where('type','Supplier')->get(),
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

            $batch = FeedPurchaseBatch::with('feedPurchases')->findOrFail($batchId);

            // Loop semua FeedPurchase di dalam batch
            foreach ($batch->feedPurchases as $purchase) {
                $feedStock = FeedStock::where('feed_purchase_id', $purchase->id)->first();

                // Validasi: tidak bisa hapus jika sudah dipakai atau dimutasi
                if (($feedStock->quantity_used ?? 0) > 0 || ($feedStock->quantity_mutated ?? 0) > 0) {
                    $this->dispatch('error', 'Tidak dapat menghapus batch. Beberapa stok sudah digunakan atau dimutasi.');
                    return;
                }

                // Hapus FeedStock & FeedPurchase
                $feedStock?->delete();
                $purchase->delete();
            }

            // Hapus batch setelah semua anaknya aman
            $batch->delete();

            DB::commit();
            $this->dispatch('success', 'Data berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
            // $this->dispatch('error', 'Terjadi kesalahan saat menghapus data. ' . $e->getMessage());
        }
    }

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
        $pembelian = FeedPurchaseBatch::with('feedPurchases')->find($id);

        $this->items = [];
        if ($pembelian && !empty($pembelian->payload['items'])) {
            // Prefer load from payload
            foreach ($pembelian->payload['items'] as $item) {
                $this->items[] = [
                    'feed_id' => $item['feed_id'],
                    'quantity' => $item['quantity'],
                    'unit_id' => $item['unit_id'],
                    'price_per_unit' => $item['price_per_unit'],
                    'available_units' => $item['available_units'] ?? [],
                ];
            }
            $this->livestock_id = $pembelian->payload['livestock_id'] ?? null;
            $this->supplier_id = $pembelian->payload['supplier_id'] ?? null;
            $this->expedition_id = $pembelian->payload['expedition_id'] ?? null;
            $this->expedition_fee = $pembelian->payload['expedition_fee'] ?? 0;
            $this->date = $pembelian->payload['date'] ?? $pembelian->date;
            $this->invoice_number = $pembelian->invoice_number ?? null;
        } elseif ($pembelian && $pembelian->feedPurchases->isNotEmpty()) {
            $this->date = $pembelian->date;
            $this->livestock_id = $pembelian->feedPurchases->first()->livestock_id;
            $this->invoice_number = $pembelian->invoice_number;
            $this->supplier_id = $pembelian->supplier_id;
            $this->expedition_id = $pembelian->expedition_id;
            $this->expedition_fee = $pembelian->expedition_fee;

            foreach ($pembelian->feedPurchases as $item) {
                $feed = \App\Models\Feed::find($item->feed_id);
                $available_units = [];
                $unit_id = $item->original_unit !== null ? (string)$item->original_unit : null;
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
                $this->items[] = [
                    'livestock_id' => $item->livestock_id,
                    'feed_id' => $item->feed_id,
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

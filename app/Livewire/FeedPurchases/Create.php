<?php

namespace App\Livewire\FeedPurchases;

use App\Models\CurrentFeed;
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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Traits\HasTempAuthorization;

class Create extends Component
{
    use WithFileUploads, HasTempAuthorization;

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
    public $status = null;


    public bool $withHistory = false; // ← Tambahkan ini di atas class Livewire

    protected $listeners = [
        'deleteFeedPurchaseBatch' => 'deleteFeedPurchaseBatch',
        'updateDoNumber' => 'updateDoNumber',
        'showEditForm' => 'showEditForm',
        'showCreateForm' => 'showCreateForm',
        'cancel' => 'cancel',
        'updateStatusFeedPurchase' => 'updateStatusFeedPurchase',
        'echo:feed-purchases,status-changed' => 'handleStatusChanged',
    ];

    public function getListeners()
    {
        return array_merge($this->listeners, [
            'echo-notification:App.Models.User.' . auth()->id() => 'handleUserNotification',
        ]);
    }

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
                    'expedition_id' => (!empty($this->expedition_id) && $this->expedition_id !== '') ? $this->expedition_id : null,
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

                $units = collect($feed->data['conversion_units']);
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

                // // Process FeedStock creation/update
                // $this->processFeedStock($purchase, $item, $feed, $livestock, $convertedQuantity);

                // // Update CurrentSupply
                // $this->updateCurrentSupply($livestock, $feed);
            }

            $batch->data = [
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

            if ($feed && isset($feed->data['conversion_units'])) {
                $units = collect($feed->data['conversion_units']);

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
        if (!$feed || empty($feed->data['conversion_units'])) return;

        $units = collect($feed->data['conversion_units']);
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
        $units = collect($feed->data['conversion_units'] ?? []);

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
        $user = auth()->user();
        $companyId = $user->company_id;

        $isSuperAdmin = $user->hasRole('SuperAdmin');

        return view('livewire.feed-purchases.create', [
            'vendors' => $isSuperAdmin
                ? Partner::where('type', 'Supplier')->get()
                : Partner::where('type', 'Supplier')->where('company_id', $companyId)->get(),
            'expeditions' => $isSuperAdmin
                ? Expedition::all()
                : Expedition::where('company_id', $companyId)->get(),
            'feedItems' => $isSuperAdmin
                ? Feed::all()
                : Feed::where('company_id', $companyId)->get(),
            'livestocks' => $isSuperAdmin
                ? Livestock::all()
                : Livestock::where('company_id', $companyId)
                ->whereHas('farm.farmOperators', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
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
                    $conversionUnits = collect($feed->data['conversion_units'] ?? []);
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
                    $conversionUnits = collect($feed->data['conversion_units'] ?? []);
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

    public function isReadonly()
    {
        if ($this->tempAuthEnabled) {
            return false;
        }

        return in_array($this->status, ['arrived', 'completed']);
    }

    public function isDisabled()
    {
        // If temp auth is enabled, not disabled
        if ($this->tempAuthEnabled) {
            return false;
        }

        // Check local conditions
        return in_array($this->status, ['arrived', 'completed']);
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
        $this->status = $pembelian->status;

        // Get data from feedPurchases
        $feedPurchaseData = $pembelian->feedPurchases->map(function ($purchase) {
            $feed = $purchase->feed;
            $available_units = [];
            $unit_id = $purchase->unit_id;

            if ($feed && isset($feed->data['conversion_units'])) {
                $available_units = collect($feed->data['conversion_units'])->map(function ($unit) {
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
        $payloadData = $pembelian->data['items'] ?? [];

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
                $pembelian->data = array_merge($pembelian->data ?? [], [
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
                $this->livestock_id = $pembelian->data['livestock_id'] ?? null;
            }
        } else {
            // If no payload data, use feedPurchases data
            $this->items = $feedPurchaseData;
            $this->livestock_id = $feedPurchaseData[0]['livestock_id'] ?? null;

            // Update payload with feedPurchases data
            $pembelian->data = [
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

    public function updateStatusFeedPurchase($purchaseId, $status, $notes)
    {
        if (empty($purchaseId) || empty($status)) {
            return;
        }

        $purchase = \App\Models\FeedPurchaseBatch::findOrFail($purchaseId)->load('feedPurchases');
        $notes = $notes ?? null;
        $oldStatus = $purchase->status;

        // If status is in_coop, try generating livestock and batch first
        if ($status === \App\Models\FeedPurchaseBatch::STATUS_ARRIVED) {
            try {
                // Get actual FeedPurchase records from the batch
                $feedPurchases = $purchase->feedPurchases;

                if ($feedPurchases->isEmpty()) {
                    throw new \Exception('Tidak ada data FeedPurchase yang ditemukan untuk batch ini.');
                }

                foreach ($feedPurchases as $feedPurchase) {
                    $feed = Feed::findOrFail($feedPurchase->feed_id);
                    $livestock = Livestock::findOrFail($feedPurchase->livestock_id);
                    $convertedQuantity = $feedPurchase->converted_quantity;

                    // Process FeedStock creation/update using actual FeedPurchase ID
                    $this->processFeedStock($feedPurchase, $feed, $livestock, $convertedQuantity);

                    // Update CurrentFeed
                    $this->updateCurrentFeed($livestock, $feed);
                }
            } catch (\Exception $e) {
                $this->dispatch('error', 'Gagal generate feed: ' . $e->getMessage());
                return;
            }
        }

        // Only update status if livestock generation was successful or not needed
        $purchase->updateFeedStatus($status, $notes, ['ip' => request()->ip(), 'user_agent' => request()->userAgent()]);

        // 🎯 DISPATCH REAL-TIME NOTIFICATION
        if ($oldStatus !== $status) {
            $notificationData = [
                'type' => $this->getNotificationTypeForStatus($status),
                'title' => 'Feed Purchase Status Updated',
                'message' => $this->getStatusChangeMessage($purchase, $oldStatus, $status),
                'batch_id' => $purchase->id,
                'old_status' => $oldStatus,
                'new_status' => $status,
                'updated_by' => auth()->id(),
                'updated_by_name' => auth()->user()->name,
                'invoice_number' => $purchase->invoice_number,
                'requires_refresh' => $this->requiresRefresh($oldStatus, $status),
                'priority' => $this->getPriority($oldStatus, $status),
                'show_refresh_button' => true,
                'timestamp' => now()->toISOString()
            ];

            // 🎯 BROADCAST TO ALL FEED PURCHASE LIVEWIRE COMPONENTS IMMEDIATELY
            $this->dispatch('notify-status-change', $notificationData)->to('feed-purchases.create');

            Log::info('IMMEDIATE feed purchase notification dispatched to Livewire components', [
                'batch_id' => $purchase->id,
                'notification_data' => $notificationData
            ]);

            // ✅ SEND TO SSE NOTIFICATION BRIDGE FOR REAL-TIME UPDATES (NO MORE POLLING!)
            // TODO: Uncomment this when the notification bridge is ready
            // $this->sendToSSENotificationBridge($notificationData, $purchase);

            // Fire event for external systems and broadcasting (secondary)
            try {
                \App\Events\FeedPurchaseStatusChanged::dispatch(
                    $purchase,
                    $oldStatus,
                    $status,
                    auth()->id(),
                    $notes,
                    $notificationData
                );

                Log::info('FeedPurchaseStatusChanged event fired', [
                    'batch_id' => $purchase->id,
                    'old_status' => $oldStatus,
                    'new_status' => $status,
                    'updated_by' => auth()->id()
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to fire FeedPurchaseStatusChanged event', [
                    'batch_id' => $purchase->id,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
            }
        }

        $this->dispatch('statusUpdated');
        $this->dispatch('success', 'Status pembelian berhasil diperbarui.');
    }

    /**
     * Process FeedStock creation/update for a feed purchase
     */
    private function processFeedStock($feedPurchase, $feed, $livestock, $convertedQuantity)
    {
        // dd($feedPurchase, $feed, $livestock, $convertedQuantity);
        Log::info("Processing FeedStock", [
            'feed_purchase_id' => $feedPurchase->id,
            'feed_id' => $feed->id,
            'livestock_id' => $livestock->id,
            'converted_quantity' => $convertedQuantity
        ]);

        // Validate that the FeedPurchase exists and has the required ID
        if (!$feedPurchase || !$feedPurchase->exists) {
            throw new \Exception("FeedPurchase dengan ID {$feedPurchase->id} tidak ditemukan.");
        }

        // Validate foreign key constraint - ensure FeedPurchase record exists in database
        $existingFeedPurchase = FeedPurchase::find($feedPurchase->id);
        if (!$existingFeedPurchase) {
            Log::error("FeedPurchase tidak ditemukan di database", [
                'feed_purchase_id' => $feedPurchase->id,
                'feed_id' => $feed->id,
                'livestock_id' => $livestock->id
            ]);
            throw new \Exception("FeedPurchase dengan ID {$feedPurchase->id} tidak ditemukan di database.");
        }

        FeedStock::updateOrCreate(
            [
                'livestock_id' => $livestock->id,
                'feed_id' => $feed->id,
                'feed_purchase_id' => $feedPurchase->id,
            ],
            [
                'date' => $feedPurchase->batch->date,
                'source_type' => 'purchase',
                'source_id' => $feedPurchase->id,
                'quantity_in' => $convertedQuantity,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]
        );

        Log::info("FeedStock processed successfully", [
            'feed_purchase_id' => $feedPurchase->id,
            'feed_stock_created' => true
        ]);
    }

    /**
     * Update CurrentSupply for livestock and feed
     */
    private function updateCurrentFeed($livestock, $feed)
    {
        Log::info("Updating CurrentSupply", [
            'livestock_id' => $livestock->id,
            'feed_id' => $feed->id,
            'farm_id' => $livestock->farm_id,
            'coop_id' => $livestock->coop_id
        ]);

        // Hitung ulang CurrentSupply TANPA softdelete jika withHistory == false
        $currentQuantity = FeedPurchase::when(!$this->withHistory, function ($q) {
            return $q->whereNull('deleted_at');
        })
            ->where('livestock_id', $livestock->id)
            ->where('feed_id', $feed->id)
            ->sum('converted_quantity');

        $currentSupply = CurrentFeed::updateOrCreate(
            [
                'livestock_id' => $livestock->id,
                'farm_id' => $livestock->farm_id,
                'coop_id' => $livestock->coop_id,
                'feed_id' => $feed->id,
                'unit_id' => $feed->data['unit_id'],
            ],
            [
                'quantity' => $currentQuantity,
                'status' => 'active',
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]
        );

        Log::info("CurrentSupply updated successfully", [
            'current_supply_id' => $currentSupply->id,
            'quantity' => $currentQuantity,
            'feed_id' => $feed->id,
            'livestock_id' => $livestock->id
        ]);

        return $currentSupply;
    }

    /**
     * Handle real-time status change notifications from broadcasting
     */
    public function handleStatusChanged($event)
    {
        Log::info('Received real-time feed purchase status change notification', [
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
                    'message' => $event['message'] ?? 'A feed purchase status has been updated.',
                    'requires_refresh' => $event['metadata']['requires_refresh'] ?? false,
                    'priority' => $event['metadata']['priority'] ?? 'normal',
                    'batch_id' => $event['batch_id'] ?? null,
                    'show_refresh_button' => true
                ]);

                Log::info('Feed purchase status change notification dispatched to user', [
                    'batch_id' => $event['batch_id'] ?? 'unknown',
                    'user_id' => auth()->id()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error handling feed purchase status change notification', [
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
        Log::info('Received user-specific feed purchase notification', [
            'notification_type' => $notification['type'] ?? 'unknown',
            'user_id' => auth()->id()
        ]);

        try {
            if (isset($notification['type']) && $notification['type'] === 'feed_purchase_status_changed') {
                $this->dispatch('notify-status-change', [
                    'type' => $this->getNotificationType($notification['priority'] ?? 'normal'),
                    'title' => $notification['title'] ?? 'Feed Purchase Update',
                    'message' => $notification['message'] ?? 'A feed purchase has been updated.',
                    'requires_refresh' => in_array('refresh_data', $notification['action_required'] ?? []),
                    'priority' => $notification['priority'] ?? 'normal',
                    'batch_id' => $notification['batch_id'] ?? null,
                    'show_refresh_button' => true
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error handling feed purchase user notification', [
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
     * Get status change message
     */
    private function getStatusChangeMessage($purchase, $oldStatus, $newStatus): string
    {
        $statusLabels = \App\Models\FeedPurchaseBatch::STATUS_LABELS;
        $oldLabel = $statusLabels[$oldStatus] ?? $oldStatus;
        $newLabel = $statusLabels[$newStatus] ?? $newStatus;

        return sprintf(
            'Feed Purchase #%s status changed from %s to %s by %s',
            $purchase->invoice_number,
            $oldLabel,
            $newLabel,
            auth()->user()->name
        );
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
    private function sendToSSENotificationBridge($notificationData, $purchase)
    {
        try {
            // Debounce mechanism: prevent duplicate notifications for same batch within 2 seconds
            $cacheKey = "sse_notification_debounce_feed_{$purchase->id}_{$notificationData['new_status']}";

            if (Cache::has($cacheKey)) {
                Log::info('SSE feed purchase notification debounced (too frequent)', [
                    'batch_id' => $purchase->id,
                    'status' => $notificationData['new_status'],
                    'cache_key' => $cacheKey
                ]);
                return;
            }

            // Set debounce cache for 2 seconds
            Cache::put($cacheKey, true, 2);

            // Prepare notification data for SSE storage
            $sseNotification = [
                'type' => 'feed_purchase_status_changed',
                'title' => $notificationData['title'],
                'message' => $notificationData['message'],
                'source' => 'livewire_sse',
                'priority' => $notificationData['priority'] ?? 'normal',
                'data' => [
                    'batch_id' => $purchase->id,
                    'invoice_number' => $purchase->invoice_number,
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
                Log::info('Successfully stored feed purchase notification for SSE bridge', [
                    'batch_id' => $purchase->id,
                    'notification_id' => $result['id'],
                    'updated_by' => auth()->id(),
                    'sse_system' => 'active'
                ]);
            } else {
                Log::warning('Failed to store SSE feed purchase notification after retries', [
                    'batch_id' => $purchase->id,
                    'updated_by' => auth()->id()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error storing feed purchase notification for SSE bridge', [
                'batch_id' => $purchase->id,
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

                    Log::warning('Could not acquire file lock for SSE feed purchase notification', [
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

                Log::info('SSE feed purchase notification stored successfully', [
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

                Log::error('Error storing SSE feed purchase notification', [
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
     * Get notification bridge URL based on environment
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
}

<?php
namespace App\Livewire\FeedMutations;

use App\Models\CurrentSupply;
// use App\Models\Item as Feed;
use App\Models\Feed;
use App\Models\Ternak;
use App\Models\Livestock;
use App\Models\FeedStock;
use App\Models\FeedMutation;
use App\Models\FeedMutationItem;
use App\Models\Supply;
use App\Models\SupplyMutation;
use App\Models\SupplyMutationItem;
use App\Models\SupplyStock;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Create extends Component
{
    public $date;
    public $from_ternak_id;
    public $to_ternak_id;
    public $items = [];
    public $availableItems = [];

    public bool $dryRun = true;


    protected $listeners = [
        'updatedItem' => 'updatedItem',
        
    ];

    public function mount()
    {
        $this->date = now()->toDateString();
        $this->items[] = ['feed_id' => '', 'quantity' => ''];
    }

    public function addItem()
    {
        $this->items[] = [
            'item_id' => '',
            'unit_id' => '',
            'quantity' => 0,
            'type' => '',
            'units' => [],
            'available_stock' => 0,
        ];
    }

    // public function addItem()
    // {
    //     $this->items[] = ['feed_id' => '', 'quantity' => ''];
    // }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function updatedItems($value, $key)
    {

        // dd($key);
        // Contoh $key: '2.item_id'
        if (str_ends_with($key, 'item_id')) {
            $index = explode('.', $key)[0];
            $this->handleItemSelected($index);
        }
    }


    public function handleItemSelected($index)
    {
        $itemId = $this->items[$index]['item_id'] ?? null;
        $selected = collect($this->availableItems)->firstWhere('id', $itemId);

        if (!$selected) return;

        $this->items[$index]['type'] = $selected['type'];

        if ($selected['type'] === 'feed') {
            $this->items[$index]['units'] = Feed::find($itemId)?->conversionUnits?->map(fn($u) => [
                // 'id' => $u->unit_id,
                'id' => $u->conversion_unit_id,
                'name' => optional($u->conversionUnit)->name,
                ])->toArray() ?? [];

            $this->items[$index]['available_stock'] = FeedStock::where('livestock_id', $this->from_ternak_id)
                ->where('feed_id', $itemId)
                ->sum('available');
        } else {
            $this->items[$index]['units'] = Supply::find($itemId)?->conversionUnits?->map(fn($u) => [
                'id' => $u->conversion_unit_id,
                'name' => optional($u->conversionUnit)->name,
                ])->toArray() ?? [];

            $this->items[$index]['available_stock'] = SupplyStock::where('livestock_id', $this->from_ternak_id)
                ->where('item_id', $itemId)
                ->sum('quantity');
        }

        // dd($this->items);
    }

    public function save()
    {
        if ($this->type === 'feed') {
            $this->saveFeedMutation();
        } elseif ($this->type === 'supply') {
            $this->saveSupplyMutation();
        } else {
            $this->dispatch('error', 'Tipe mutasi tidak dikenali');
        }
    }


    public function saveFeedMutation()
    {
        DB::beginTransaction();

        try {
            foreach ($this->items as $item) {
                $feedId = $item['feed_id'];
                $feed = Feed::findOrFail($feedId);
                $requiredQty = $item['quantity'];

                $stocks = FeedStock::where('livestock_id', $this->from_ternak_id)
                    ->where('feed_id', $feedId)
                    ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
                    ->orderBy('date')
                    ->orderBy('created_at')
                    ->lockForUpdate()
                    ->get();

                $totalAvailable = $stocks->sum(fn($s) => $s->quantity_in - $s->quantity_used - $s->quantity_mutated);

                if ($totalAvailable < $requiredQty) {
                    throw new \Exception("Stok tidak cukup untuk feed: $feed->name. Dibutuhkan: $requiredQty, Tersedia: $totalAvailable");
                }
            }

            // Simpan jika bukan dry run
            if (!$this->dryRun) {
                $mutation = FeedMutation::create([
                    'id' => Str::uuid(),
                    'date' => $this->date,
                    'from_livestock_id' => $this->from_ternak_id,
                    'to_livestock_id' => $this->to_ternak_id,
                    'created_by' => auth()->id(),
                ]);
            } else {
                $mutation = (object)['id' => Str::uuid()]; // dummy object
            }

            foreach ($this->items as $item) {
                $feedId = $item['feed_id'];
                $requiredQty = $item['quantity'];

                $stocks = FeedStock::where('livestock_id', $this->from_ternak_id)
                    ->where('feed_id', $feedId)
                    ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
                    ->orderBy('date')
                    ->orderBy('created_at')
                    ->lockForUpdate()
                    ->get();

                foreach ($stocks as $stock) {
                    if ($requiredQty <= 0) break;

                    $available = $stock->quantity_in - $stock->quantity_used - $stock->quantity_mutated;
                    $takeQty = min($available, $requiredQty);

                    if (!$this->dryRun) {
                        $stock->quantity_mutated += $takeQty;
                        $stock->save();

                        FeedStock::create([
                            'id' => Str::uuid(),
                            'livestock_id' => $this->to_ternak_id,
                            'feed_id' => $feedId,
                            'feed_purchase_id' => $stock->feed_purchase_id,
                            'date' => $this->date,
                            'source_id' => $mutation->id,
                            'amount' => $takeQty * ($stock->amount / $stock->quantity_in),
                            'available' => $takeQty,
                            'quantity_in' => $takeQty,
                            'quantity_used' => 0,
                            'quantity_mutated' => 0,
                            'created_by' => auth()->id(),
                        ]);

                        FeedMutationItem::create([
                            'feed_mutation_id' => $mutation->id,
                            'feed_stock_id' => $stock->id,
                            'feed_id' => $feedId,
                            'quantity' => $takeQty,
                            'created_by' => auth()->id(),
                        ]);
                    }

                    $requiredQty -= $takeQty;
                }
            }

            if ($this->dryRun) {
                DB::rollBack(); // cancel semua perubahan
                $this->dispatch('success', 'Simulasi mutasi pakan berhasil dijalankan');
            } else {
                DB::commit();
                $this->resetForm();
                $this->dispatch('success', 'Mutasi pakan berhasil disimpan');
                $this->dispatch('closeForm');
            }

        } catch (ValidationException $e) {
            $this->dispatch('validation-errors', ['errors' => $e->validator->errors()->all()]);
            $this->setErrorBag($e->validator->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('error', 'Terjadi kesalahan saat memproses data. ' . $e->getMessage());
        }
    }

    public function saveSupplyMutation()
    {
        DB::beginTransaction();

        try {
            foreach ($this->items as $item) {
                $supplyId = $item['supply_id'];
                $supply = Supply::findOrFail($supplyId); // diasumsikan `Item` adalah model general supply
                $requiredQty = $item['quantity'];

                $stocks = CurrentSupply::where('livestock_id', $this->from_ternak_id)
                    ->where('item_id', $supplyId)
                    ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
                    ->orderBy('date')
                    ->orderBy('created_at')
                    ->lockForUpdate()
                    ->get();

                $totalAvailable = $stocks->sum(fn($s) => $s->quantity_in - $s->quantity_used - $s->quantity_mutated);

                if ($totalAvailable < $requiredQty) {
                    throw new \Exception("Stok tidak cukup untuk item: $supply->name. Dibutuhkan: $requiredQty, Tersedia: $totalAvailable");
                }
            }

            if (!$this->dryRun) {
                $mutation = SupplyMutation::create([
                    'id' => Str::uuid(),
                    'date' => $this->date,
                    'from_livestock_id' => $this->from_ternak_id,
                    'to_livestock_id' => $this->to_ternak_id,
                    'created_by' => auth()->id(),
                ]);
            } else {
                $mutation = (object)['id' => Str::uuid()];
            }

            foreach ($this->items as $item) {
                $supplyId = $item['supply_id'];
                $requiredQty = $item['quantity'];

                $stocks = CurrentSupply::where('livestock_id', $this->from_ternak_id)
                    ->where('item_id', $supplyId)
                    ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
                    ->orderBy('date')
                    ->orderBy('created_at')
                    ->lockForUpdate()
                    ->get();

                foreach ($stocks as $stock) {
                    if ($requiredQty <= 0) break;

                    $available = $stock->quantity_in - $stock->quantity_used - $stock->quantity_mutated;
                    $takeQty = min($available, $requiredQty);

                    if (!$this->dryRun) {
                        $stock->quantity_mutated += $takeQty;
                        $stock->save();

                        CurrentSupply::create([
                            'id' => Str::uuid(),
                            'livestock_id' => $this->to_ternak_id,
                            'item_id' => $supplyId,
                            'item_purchase_id' => $stock->item_purchase_id,
                            'date' => $this->date,
                            'source_id' => $mutation->id,
                            'amount' => $takeQty * ($stock->amount / $stock->quantity_in),
                            'available' => $takeQty,
                            'quantity_in' => $takeQty,
                            'quantity_used' => 0,
                            'quantity_mutated' => 0,
                            'created_by' => auth()->id(),
                        ]);

                        SupplyMutationItem::create([
                            'supply_mutation_id' => $mutation->id,
                            'supply_stock_id' => $stock->id,
                            'item_id' => $supplyId,
                            'quantity' => $takeQty,
                            'created_by' => auth()->id(),
                        ]);
                    }

                    $requiredQty -= $takeQty;
                }
            }

            if ($this->dryRun) {
                DB::rollBack();
                $this->dispatch('success', 'Simulasi mutasi supply berhasil dijalankan');
            } else {
                DB::commit();
                $this->resetForm();
                $this->dispatch('success', 'Mutasi supply berhasil disimpan');
                $this->dispatch('closeForm');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('error', 'Terjadi kesalahan saat memproses data supply. ' . $e->getMessage());
        }
    }

    // public function save()
    // {
    //     // dd($this->all());
    //     // $this->validate([
    //     //     'date' => 'required|date',
    //     //     'from_ternak_id' => 'required|exists:ternaks,id|different:to_ternak_id',
    //     //     'to_ternak_id' => 'required|exists:ternaks,id',
    //     //     'items' => 'required|array|min:1',
    //     //     'items.*.feed_id' => 'required|exists:items,id',
    //     //     'items.*.quantity' => 'required|numeric|min:0.01',
    //     // ]);

    //     DB::beginTransaction();

    //     try {
    //         // VALIDASI stok pakan untuk setiap feed
    //         foreach ($this->items as $item) {
    //             $feedId = $item['feed_id'];
    //             $feed = Feed::findOrFail($feedId);
    //             $requiredQty = $item['quantity'];

    //             $stocks = FeedStock::where('livestock_id', $this->from_ternak_id)
    //                 ->where('feed_id', $feedId)
    //                 ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
    //                 ->orderBy('date')
    //                 ->orderBy('created_at')
    //                 ->lockForUpdate()
    //                 ->get();

    //             $totalAvailable = $stocks->sum(fn($s) => $s->quantity_in - $s->quantity_used - $s->quantity_mutated);

    //             if ($totalAvailable < $requiredQty) {
    //                 throw new \Exception("Stok tidak cukup untuk feed: $feed->name. Dibutuhkan: $requiredQty, Tersedia: $totalAvailable");
    //             }
    //         }

    //         // BUAT MUTASI UTAMA
    //         $mutation = FeedMutation::create([
    //             'id' => Str::uuid(),
    //             'date' => $this->date,
    //             'from_livestock_id' => $this->from_ternak_id,
    //             'to_livestock_id' => $this->to_ternak_id,
    //             'created_by' => auth()->id(),
    //         ]);

    //         // PROSES PENGURANGAN DAN PENAMBAHAN STOK SECARA FIFO
    //         foreach ($this->items as $item) {
    //             $feedId = $item['feed_id'];
    //             $requiredQty = $item['quantity'];

    //             $stocks = FeedStock::where('livestock_id', $this->from_ternak_id)
    //                 ->where('feed_id', $feedId)
    //                 ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
    //                 ->orderBy('date')
    //                 ->orderBy('created_at')
    //                 ->lockForUpdate()
    //                 ->get();

    //             foreach ($stocks as $stock) {
    //                 if ($requiredQty <= 0) break;

    //                 $available = $stock->quantity_in - $stock->quantity_used - $stock->quantity_mutated;
    //                 $takeQty = min($available, $requiredQty);

    //                 // Kurangi stok dari ternak asal
    //                 $stock->quantity_mutated += $takeQty;
    //                 $stock->save();

    //                 // Tambahkan ke stok ternak tujuan
    //                 FeedStock::create([
    //                     'id' => Str::uuid(),
    //                     'livestock_id' => $this->to_ternak_id,
    //                     'feed_id' => $feedId,
    //                     'feed_purchase_id' => $stock->feed_purchase_id,
    //                     'date' => $this->date,
    //                     'source_id' => $mutation->id,
    //                     'amount' => $takeQty * ($stock->amount / $stock->quantity_in),
    //                     'available' => $takeQty,
    //                     'quantity_in' => $takeQty,
    //                     'quantity_used' => 0,
    //                     'quantity_mutated' => 0,
    //                     'created_by' => auth()->id(),
    //                 ]);

    //                 // Simpan detail mutasi
    //                 FeedMutationItem::create([
    //                     'feed_mutation_id' => $mutation->id,
    //                     'feed_stock_id' => $stock->id,
    //                     'feed_id' => $feedId,
    //                     'quantity' => $takeQty,
    //                     'created_by' => auth()->id(),
    //                 ]);

    //                 $requiredQty -= $takeQty;
    //             }
    //         }

    //         DB::commit();

    //         $this->resetForm();

    //         $this->dispatch('success', 'Mutasi pakan berhasil disimpan');
    //         $this->dispatch('closeForm');

    //         // session()->flash('success', 'Mutasi pakan berhasil disimpan.');
    //         // return redirect()->route('feed-mutations.index');

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
        // $this->reset();
        // Reset hanya form input, bukan flash/session
        $this->reset(['date', 'from_ternak_id', 'to_ternak_id', 'items']);

        // Set ulang defaults jika perlu
        $this->date = now()->toDateString();
        $this->items = [['feed_id' => '', 'quantity' => '']];
    }

    // public function save()
    // {
    //     $this->validate([
    //         'date' => 'required|date',
    //         'from_ternak_id' => 'required|exists:ternaks,id|different:to_ternak_id',
    //         'to_ternak_id' => 'required|exists:ternaks,id|different:from_ternak_id',
    //         'items.*.feed_id' => 'required|exists:items,id',
    //         'items.*.quantity' => 'required|numeric|min:0.01',
    //     ]);

    //     DB::beginTransaction();
    //     try {
    //         // Validasi ketersediaan stok dari ternak asal
    //         foreach ($this->items as $item) {
    //             $feedId = $item['feed_id'];
    //             $requiredQty = $item['quantity'];

    //             $stocks = FeedStock::where('ternak_id', $this->from_ternak_id)
    //                 ->where('feed_id', $feedId)
    //                 ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
    //                 ->orderBy('date')
    //                 ->orderBy('created_at')
    //                 ->lockForUpdate()
    //                 ->get();

    //             $totalAvailable = $stocks->sum(function ($s) {
    //                 return $s->quantity_in - $s->quantity_used - $s->quantity_mutated;
    //             });

    //             if ($totalAvailable < $requiredQty) {
    //                 throw new \Exception("Stok tidak cukup untuk feed ID: $feedId. Dibutuhkan: $requiredQty, Tersedia: $totalAvailable");
    //             }
    //         }

    //         dd($totalAvailable);

    //         $mutation = FeedMutation::create([
    //             'id' => Str::uuid(),
    //             'date' => $this->date,
    //             'from_ternak_id' => $this->from_ternak_id,
    //             'to_ternak_id' => $this->to_ternak_id,
    //             'created_by' => auth()->id(),
    //         ]);

    //         foreach ($this->items as $item) {
    //             $feedId = $item['feed_id'];
    //             $requiredQty = $item['quantity'];
    //             $totalMutated = 0;

    //             // Ambil stok dari ternak asal (FIFO)
    //             $stocks = FeedStock::where('ternak_id', $this->from_ternak_id)
    //                 ->where('feed_id', $feedId)
    //                 ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
    //                 ->orderBy('date')
    //                 ->orderBy('created_at')
    //                 ->lockForUpdate()
    //                 ->get();

    //             FeedMutationItem::create([
    //                 'feed_mutation_id' => $mutation->id,
    //                 'feed_stock_id' => $feedStock->id,
    //                 'feed_id' => $item['feed_id'],
    //                 'quantity' => $item['quantity'],
    //                 'created_by' => auth()->id(),
    //             ]);
    //         }

    //         DB::commit();
    //         // session()->flash('success', 'Mutasi pakan berhasil disimpan.');
    //         $this->dispatch('success', 'Mutasi pakan berhasil disimpan');

    //         // return redirect()->route('feed-mutations.index');
    //     } catch (\Throwable $e) {
    //         DB::rollBack();
    //         $this->addError('save_error', $e->getMessage());
    //     }
    // }

    public function close()
    {
        $this->dispatch('closeForm');
    }

    public function render()
    {
        return view('livewire.feed-mutations.create', [
            'livestocks' => Livestock::whereHas('farm.farmOperators', function ($query) {
                                $query->where('user_id', auth()->id());
                            })->get(),
            'feeds' => Feed::all(),
        ]);
    }

    public function updatedFromTernakId()
    {
        $this->loadAvailableItems();
    }

    public function loadAvailableItems()
    {
        $source = Livestock::findOrFail($this->from_ternak_id);

        $feedItems = FeedStock::where('livestock_id', $this->from_ternak_id)
            ->with('feed:id,name')
            ->get()
            ->groupBy('feed_id')
            ->map(fn($group) => [
                'id' => $group->first()->feed_id,
                'type' => 'feed',
                'name' => optional($group->first()->feed)->name,
            ]);

        $supplyItems = SupplyStock::where('farm_id', $source->farm_id)
            ->with('supply:id,name')
            ->get()
            ->groupBy('item_id')
            ->map(fn($group) => [
                'id' => $group->first()->item_id,
                'type' => 'supply',
                'name' => optional($group->first()->item)->name,
            ]);

        $this->availableItems = array_merge(
            $feedItems->values()->toArray(),
            $supplyItems->values()->toArray()
        );
    }

    // public function loadAvailableItems()
    // {
    //     $this->availableItems = [];

    //     if (!$this->from_ternak_id) return;

    //     $source = Livestock::findOrFail($this->from_ternak_id);

    //     // Feed stock
    //     $feeds = FeedStock::where('livestock_id', $this->from_ternak_id)
    //     ->join('feeds', 'feed_stocks.feed_id', '=', 'feeds.id')
    //     ->select('feed_stocks.feed_id as id', 'feeds.name')
    //     ->distinct()
    //     ->get()
    //     ->map(fn($row) => [
    //         'id' => $row->id,
    //         'type' => 'feed',
    //         'name' => $row->name,
    //     ]);
    


    //     // Supply stock
    //     $supplies = SupplyStock::where('farm_id', $source->farm_id)
    //         ->select('supply_id as id')
    //         ->with('supply:id,name')
    //         ->groupBy('supply_id')
    //         ->get()
    //         ->map(fn($stock) => [
    //             'id' => $stock->id,
    //             'type' => 'supply',
    //             'name' => optional($stock->supply)->name
    //         ]);

    //     $this->availableItems = $feeds->merge($supplies)->toArray();

    //     // dd($feeds);
    // }

}

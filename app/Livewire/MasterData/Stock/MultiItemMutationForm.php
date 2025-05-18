<?php

namespace App\Livewire\MasterData\Stock;

use App\Models\Feed;
use App\Models\FeedMutation;
use App\Models\FeedMutationItem;
use App\Models\FeedStock;
use App\Models\Livestock;
use App\Models\Supply;
use App\Models\SupplyStock;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use App\Services\ItemMutationService;
use App\Services\MutationService;

class MultiItemMutationForm extends Component
{
    public $item, $mutation_type, $quantity, $mutation_date, $notes, $destination_livestock_id, $available_stock, $source_name, $availableStock, $availableUnit, $tanggal, $date, $from_livestock_id, $to_livestock_id, $fromLivestockId, $toLivestockId, $mutationId;
    public $items =[], $availableItems =[];
    public $showForm = false;

    public bool $dryRun = true;

    protected $listeners = [
        'showMutationForm' => 'showMutationForm',
        
    ];

    public function mount($item = null)
    {
        $this->item = $item;
        $this->available_stock = $item ? $item->available_stock : 0;
    }


    public function render()
    {
        return view('livewire.master-data.stock.multi-item-mutation-form', [
            'livestocks' => Livestock::whereHas('farm.farmOperators', function ($query) {
                                $query->where('user_id', auth()->id());
                            })->get(),
        ]);
    }

    public function addItem()
    {
        $this->validate([
            'date' => 'required|date',
            'from_livestock_id' => 'required|exists:livestocks,id',
            'to_livestock_id' => 'required|exists:livestocks,id',
        ]);

        $this->items[] = [
            'item_id' => '',
            'unit_id' => '',
            'quantity' => 0,
            'type' => '',
            'units' => [],
            'available_stock' => 0,
        ];
    }

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
            $feed = Feed::find($itemId);
            $this->items[$index]['units'] = $feed?->conversionUnits?->map(fn($u) => [
                'id' => $u->conversion_unit_id,
                'name' => optional($u->conversionUnit)->name,
            ])->toArray() ?? [];

            $stocks = FeedStock::where('livestock_id', $this->from_livestock_id)
                ->where('feed_id', $itemId)
                ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
                ->orderBy('date')
                ->orderBy('created_at')
                ->get();

            $totalAvailable = $stocks->sum(fn($s) => $s->quantity_in - $s->quantity_used - $s->quantity_mutated);
            $this->items[$index]['available_stock'] = $totalAvailable;

        } else {
            $supply = Supply::find($itemId);
            $livestock = Livestock::find($this->from_livestock_id);
            $this->items[$index]['units'] = $supply?->conversionUnits?->map(fn($u) => [
                'id' => $u->conversion_unit_id,
                'name' => optional($u->conversionUnit)->name,
            ])->toArray() ?? [];

            $stocks = SupplyStock::where('farm_id', $livestock->farm_id)
                ->where('supply_id', $itemId)
                ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
                ->orderBy('date')
                ->orderBy('created_at')
                ->get();

            $totalAvailable = $stocks->sum(fn($s) => $s->quantity_in - $s->quantity_used - $s->quantity_mutated);
            $this->items[$index]['available_stock'] = $totalAvailable;
        }
    }


    public function updatedFromLivestockId()
    {
        $this->loadAvailableItems();
    }

    public function loadAvailableItems()
    {
        $source = Livestock::findOrFail($this->from_livestock_id);

        $feedItems = FeedStock::where('livestock_id', $this->from_livestock_id)
            ->with('feed:id,name')
            ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
            ->get()
            ->groupBy('feed_id')
            ->map(fn($group) => [
                'id' => $group->first()->feed_id,
                'type' => 'feed',
                'name' => optional($group->first()->feed)->name,
            ]);

        $supplyItems = SupplyStock::where('farm_id', $source->farm_id)
            ->with('supply:id,name')
            ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
            ->get()
            ->groupBy('supply_id')
            ->map(fn($group) => [
                'id' => $group->first()->supply_id,
                'type' => 'supply',
                'name' => optional($group->first()->supply)->name,
            ]);

        $this->availableItems = array_merge(
            $feedItems->values()->toArray(),
            $supplyItems->values()->toArray()
        );

        // dd($this->availableItems);
    }

    public function save()
    {
        // dd($this->all());
        try {

            // VALIDASI: Hanya boleh satu jenis type (feed atau supply)
            $types = collect($this->items)->pluck('type')->unique();

            if ($types->count() > 1) {
                throw new \Exception('Tidak bisa melakukan mutasi campuran antara feed dan supply dalam satu proses. Pisahkan prosesnya.');
            }

            foreach ($this->items as $item) {
                if (($item['type'] ?? 'feed') !== 'feed') continue;
            
                $feedId = $item['item_id'];
                $feed = Feed::findOrFail($feedId);
            
                // Ambil satuan yang digunakan user
                $unitId = $item['unit_id'];
                $inputQty = $item['quantity'];
            
                // Konversi ke satuan terkecil
                $conversionRate = $feed->conversionUnits()
                    ->where('conversion_unit_id', $unitId)
                    ->value('conversion_value');
            
                // Jika tidak ada rate, asumsikan 1 (berarti satuan terkecil)
                $rate = $conversionRate ?: 1;
            
                $requiredQty = $inputQty * $rate;
            
                // Ambil stok feed berdasarkan FIFO
                $stocks = FeedStock::where('livestock_id', $this->from_livestock_id)
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

            $mutation = MutationService::saveOrUpdateMutation([
                'date' => $this->date,
                'from_livestock_id' => $this->from_livestock_id,
                'to_livestock_id' => $this->to_livestock_id,
                'notes' => $this->notes,
            ], $this->items, $this->mutationId); // jika mutationId null, akan dianggap create baru
            
            DB::commit();
            $this->resetForm();
            $this->dispatch('success', 'Mutasi berhasil disimpan');
            $this->dispatch('closeForm');

        } catch (ValidationException $e) {
            $this->dispatch('validation-errors', ['errors' => $e->validator->errors()->all()]);
            $this->setErrorBag($e->validator->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('error', 'Terjadi kesalahan saat memproses data. ' . $e->getMessage());
        }
        
        // Logic untuk menyimpan mutasi
    }

    public function resetForm()
    {
        $this->reset();
    }

    public function close()
    {
        // $this->dispatch('closeForm');
        $this->resetForm();
        $this->showForm = false;
        $this->dispatch('show-datatable');
    }

    public function cancel()
    {
        $this->resetForm();
        $this->showForm = false;
        $this->dispatch('show-datatable');
    }

    public function showMutationForm()
    {
        $this->resetForm();
        $this->showForm = true;
        $this->dispatch('hide-datatable');
    }

    // public function showMutationForm($livestockId, $feedId)
    // {
    //     $livestock = Livestock::where('status', 'active'); // Definisikan sekali
    //     $feeds = Feed::where('status', 'active')->get(['id', 'name','payload']); // Definisikan sekali

    //     $excludedIds = [$livestockId];
    //     $dest = clone $livestock; // Clone query builder

    //     $this->livestocks = $dest->whereNotIn('id', $excludedIds)->get();

    //     $source = clone $livestock; // Clone query builder
    //     $source = $source->where('id', $livestockId)->first(['id', 'name']);

    //     // dd($livestock->get());

    //     $this->source_name = $source ? $source->name : '-';
    //     $this->feeds = $feeds;
    //     $this->from_livestock_id = $livestockId;
    //     // $this->name = $feed->name;
    //     // $this->type = "Feed";
    //     // $this->unit_id = $feed->payload['unit_id'];
    //     // $this->description = $feed->description;
    //     // $this->conversion_units = $feed->payload['conversion_units'] ?? [];

    //     $this->showForm = true;
    //     $this->edit_mode = true;
    //     $this->dispatch('hide-datatable');
    // }
}

<?php

namespace App\Livewire\MasterData\Feed;

use App\Models\CurrentFeed;
use App\Models\Farm;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

use App\Models\Feed;
use App\Models\FeedMutation;
use App\Models\FeedMutationItem;
use App\Models\FeedStock;
use App\Models\Livestock;
use App\Models\Unit;
use App\Models\Mutation as MutationModel;
use App\Models\UnitConversion;
use App\Services\MutationService;
use Exception;
use Illuminate\Support\Facades\Log;

class Mutation extends Component
{
    public $feedId, $feed_id, $source_livestock_id, $destination_livestock_id, $availableItems = [];
    public $code, $name, $category, $type, $description, $volume;
    public $farms, $source_farm_id, $destination_farm_id, $dstLivestocks;
    public $mutationId;
    public $date, $tanggal, $notes;
    public $quantity;

    public $items = [];
    public $livestock_id;
    public $showForm = false;
    public $edit_mode = false;
    public $conversions = []; // untuk satuan konversi
    // public $units = []; // ambil dari DB untuk dropdown
    public $unit_id;
    public $conversion_units = [];
    public $default_unit_id = null;
    public $source_id, $source_name, $livestocks = [], $feeds = [];
    public $availableStock, $availableUnit;

    protected $listeners = [
        'showMutationForm' => 'showMutationForm',
        'showEditForm' => 'showEditForm',
        'showCreateForm' => 'showCreateForm',
        'delete_mutation' => 'delete_mutation',
        'cancel' => 'cancel',
        'edit' => 'edit',
        'addConversion' => 'addConversion',

    ];

    // Definisikan aturan validasi
    protected $rules = [
        'destination_farm_id' => 'required|uuid|different:source_farm_id',
        'feed_id' => 'required|uuid',
        'quantity' => 'required|numeric|min:0.01|lte:availableStock',
        'tanggal' => 'required|date',
        'notes' => 'nullable|string|max:255',
    ];

    // Definisikan pesan error kustom (opsional)
    protected $messages = [
        'destination_farm_id.required' => 'Tujuan harus dipilih.',
        'destination_farm_id.uuid' => 'Tujuan tidak valid.',
        'destination_farm_id.different' => 'Tujuan tidak boleh sama dengan asal.',
        'feed_id.required' => 'Item harus dipilih.',
        'feed_id.uuid' => 'Item tidak valid.',
        'quantity.required' => 'Jumlah harus diisi.',
        'quantity.numeric' => 'Jumlah harus berupa angka.',
        'quantity.min' => 'Jumlah minimal adalah 0.01.',
        'quantity.lte' => 'Jumlah tidak boleh melebihi stok yang tersedia.',
        'tanggal.required' => 'Tanggal mutasi harus diisi.',
        'tanggal.date' => 'Format tanggal tidak valid.',
        'notes.max' => 'Catatan maksimal 255 karakter.',
    ];

    public function mount()
    {
        $this->dstLivestocks = Livestock::all();
        $this->livestocks = Livestock::whereIn('id', function ($query) {
            $query->select('livestock_id')
                ->from('current_supplies')
                ->where('status', 'active')
                ->where('quantity', '>', 0)
                ->groupBy('livestock_id');
        })
            ->with('farm') // Jika ingin akses farm->name di blade
            ->get();

        // $this->livestocks = FeedStock::distinct('livestock_id')
        //     ->with('livestock:id,name') // Eager load the 'farm' relationship to access the name
        //     ->get()
        //     ->pluck('livestock');
        // dd($this->livestocks->toArray());
    }

    public function addConversion()
    {
        $this->validate([
            'code' => 'required',
            'name' => 'required',
            'type' => 'required', // Contoh dengan 'in'
            'unit_id' => 'required', // Contoh dengan 'in'
        ]);

        $this->conversion_units[] = [
            'unit_id' => '',
            'value' => 1,
            'is_default_purchase' => false,
            'is_default_mutation' => false,
            'is_default_sale' => false,
            'is_smallest' => false,
        ];
    }


    public function removeConversion($index)
    {
        $unit = $this->conversion_units[$index] ?? null;

        if (!$unit) return;

        // Cegah penghapusan jika unit ini adalah default
        if (
            $unit['is_default_purchase'] ||
            $unit['is_default_mutation'] ||
            $unit['is_default_sale'] ||
            $unit['is_smallest']
        ) {
            $this->addError('conversion_units', 'Tidak bisa menghapus unit default.');
            return;
        }

        unset($this->conversion_units[$index]);
        $this->conversion_units = array_values($this->conversion_units); // Reset index
    }


    public function removeConversionUnit($index)
    {
        unset($this->conversion_units[$index]);
        $this->conversion_units = array_values($this->conversion_units);
    }

    public function updatedUnitId($value)
    {
        $this->validate([
            'code' => 'required',
            'name' => 'required',
            'type' => 'required', // Contoh dengan 'in'
            // validasi tambahan lain
        ]);

        if (!$value) return;

        $unit = Unit::find($value);
        if (!$unit) return;

        $this->resetErrorBag();

        // Hapus satuan default sebelumnya dari conversion_units
        if ($this->default_unit_id) {
            $this->conversion_units = collect($this->conversion_units)
                ->reject(fn($item) => (string) $item['unit_id'] === (string) $this->default_unit_id)
                ->values()
                ->toArray();
        }

        // Tambah satuan default baru
        $this->conversion_units[] = [
            'unit_id' => $unit->id,
            'unit_name' => $unit->name,
            'value' => 1,
            'is_default_purchase' => true,
            'is_default_mutation' => true,
            'is_default_sale' => true,
            'is_smallest' => true,
        ];

        // Simpan unit default baru
        $this->default_unit_id = $unit->id;
    }

    protected function validateConversionDefaults()
    {
        foreach (['is_default_purchase', 'is_default_mutation', 'is_default_sale', 'is_smallest'] as $field) {
            $count = collect($this->conversion_units)->filter(fn($unit) => $unit[$field] ?? false)->count();

            if ($count > 1) {
                throw ValidationException::withMessages([
                    'conversion_units' => "Hanya boleh satu $field yang dipilih sebagai default.",
                ]);
            }
        }
    }

    public function toggleDefault($field, $index)
    {
        foreach ($this->conversion_units as $i => $unit) {
            $this->conversion_units[$i][$field] = ($i === $index);
        }
    }

    public function addItem()
    {
        $this->validate([
            'tanggal' => 'required|date',
            'source_livestock_id' => 'required|exists:livestocks,id',
            'destination_livestock_id' => 'required|uuid|different:source_livestock_id',
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

            $stocks = FeedStock::where('livestock_id', $this->source_livestock_id)
                ->where('feed_id', $itemId)
                ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
                ->orderBy('date')
                ->orderBy('created_at')
                ->get();

            $totalAvailable = $stocks->sum(fn($s) => $s->quantity_in - $s->quantity_used - $s->quantity_mutated);
            $this->items[$index]['available_stock'] = $totalAvailable;
        }
    }

    public function render()
    {
        return view('livewire.master-data.feed.mutation', [
            'units' => Unit::all(),
        ]);
    }

    public function showCreateForm()
    {
        $this->resetForm();
        $this->showForm = true;
        $this->dispatch('hide-datatable');
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
        $this->showForm = true;
        $this->dispatch('hide-datatable');
    }

    public function showEditForm($id)
    {
        // $mutation = MutationModel::with('items')->findOrFail($id);
        $mutation = MutationModel::with(['items.item', 'fromLivestock', 'toLivestock'])->findOrFail($id);

        $this->mutationId = $mutation->id;
        $this->tanggal = $mutation->date->format('Y-m-d');
        $this->source_livestock_id = $mutation->from_livestock_id;
        $this->destination_livestock_id = $mutation->to_livestock_id;
        $this->notes = $mutation->notes;

        $this->items = $mutation->items->map(function ($item) {
            $feed = $item->item;
            $unitId = $feed->payload['unit_id'] ?? null;
            $conversionUnits = $feed->payload['conversion_units'] ?? [];

            // Cari unit_id yang paling sesuai (default ke satuan terkecil)
            $defaultUnitId = collect($conversionUnits)->where('is_smallest', true)->first()['unit_id'] ?? $unitId;

            // Hitung ulang quantity dalam satuan yang sesuai
            $conversionRate = collect($conversionUnits)
                ->where('unit_id', $defaultUnitId)
                ->first()['conversion_value'] ?? 1;

            return [
                'item_id' => $feed->id,
                'type' => 'feed',
                'name' => $feed->name,
                'unit_id' => $defaultUnitId,
                'quantity' => $item->quantity / $conversionRate, // Kembalikan ke satuan input
            ];
        })->toArray();

        $this->edit_mode = true;
        $this->showForm = true;
        $this->dispatch('hide-datatable');
    }


    public function updated($propertyName)
    {
        // Dipanggil setiap kali properti Livewire berubah
        if ($propertyName === 'feed_id' || $propertyName === 'source_livestock_id') {
            $this->checkAvailableStock();
        }
    }

    public function checkAvailableStock()
    {
        if ($this->source_livestock_id && $this->feed_id) {
            $stock = FeedStock::where('livestock_id', $this->source_livestock_id)
                ->where('feed_id', $this->feed_id)
                ->first();

            // dd($stock);

            if ($stock) {
                $this->availableStock = $stock->quantity_in - $stock->quantity_used - $stock->quantity_mutated;
                $this->availableUnit = $stock->feed->payload['unit_details']['name'] ?? '';
            } else {
                $this->availableStock = 0;
                $this->availableUnit = '';
            }
        } else {
            $this->availableStock = 0;
            $this->availableUnit = '';
        }
    }

    // public function save()
    // {
    //     $this->transferStock($this->all());

    //     $this->dispatch('success', 'Data Mutasi Pakan berhasil ' . ($this->edit_mode ? 'diperbarui' : 'disimpan'));
    //     $this->close();
    // }

    public function save()
    {
        try {
            // Validate the form data
            $this->validate([
                'tanggal' => 'required|date',
                'source_livestock_id' => 'required|exists:livestocks,id',
                'destination_livestock_id' => 'required|uuid|different:source_livestock_id',
                'items' => 'required|array|min:1',
                'items.*.item_id' => 'required|uuid|exists:feeds,id',
                'items.*.unit_id' => 'required|uuid|exists:units,id',
                'items.*.quantity' => 'required|numeric|gt:0',
            ]);

            // Process each item to enrich with unit information
            foreach ($this->items as $index => $item) {
                $feedId = $item['item_id'];
                $unitId = $item['unit_id'];

                $feed = Feed::with('unit')->findOrFail($feedId);
                $unit = Unit::findOrFail($unitId);

                // Get and validate unit conversion information
                $conversionUnits = collect($feed->payload['conversion_units'] ?? []);
                $selectedUnit = $conversionUnits->firstWhere('unit_id', $unitId);
                $smallestUnit = $conversionUnits->firstWhere('is_smallest', true);

                if (!$selectedUnit || !$smallestUnit) {
                    $this->addError("items.$index.unit_id", "Informasi konversi untuk unit {$unit->name} tidak ditemukan pada pakan {$feed->name}");
                    return;
                }

                // Enrich the item with unit names and type information
                $this->items[$index]['unit_name'] = $unit->name;
                $this->items[$index]['type'] = 'feed'; // Since this is feed mutation

                // Check available stock in smallest units
                $inputQty = floatval($item['quantity']);
                $inputUnitValue = floatval($selectedUnit['value']);
                $smallestUnitValue = floatval($smallestUnit['value']);
                $requiredQtySmallest = ($inputQty * $inputUnitValue) / $smallestUnitValue;

                // Verify stock availability
                $stocks = FeedStock::where('livestock_id', $this->source_livestock_id)
                    ->where('feed_id', $feedId)
                    ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
                    ->get();

                $totalAvailable = $stocks->sum(fn($s) => $s->quantity_in - $s->quantity_used - $s->quantity_mutated);

                if ($totalAvailable < $requiredQtySmallest) {
                    $availableInInputUnits = ($totalAvailable * $smallestUnitValue) / $inputUnitValue;
                    $this->addError(
                        "items.$index.quantity",
                        "Stok {$feed->name} tidak mencukupi. Tersedia: " . number_format($availableInInputUnits, 2) . " {$unit->name}"
                    );
                    return;
                }
            }

            // Create/update the mutation
            $mutation = MutationService::feedMutation([
                'date' => $this->tanggal,
                'source_livestock_id' => $this->source_livestock_id,
                'destination_livestock_id' => $this->destination_livestock_id,
                'notes' => $this->notes,
            ], $this->items, $this->mutationId);

            $this->dispatch('success', 'Data Mutasi Pakan berhasil ' . ($this->edit_mode ? 'diperbarui' : 'disimpan'));
            $this->close();
        } catch (\Exception $e) {
            // Log the error
            $class = __CLASS__;
            $method = __FUNCTION__;
            $line = $e->getLine();
            $file = $e->getFile();
            $message = $e->getMessage();

            // Human-readable error message
            $this->dispatch('error', $message);

            // Log detailed error for debugging
            Log::error("[$class::$method] Error: $message | Line: $line | File: $file");
            Log::debug("[$class::$method] Stack trace: " . $e->getTraceAsString());
        }
    }

    // public function save()
    // {
    //     try {
    //         $tanggal = $this->tanggal;
    //         $quantity = $this->quantity;
    //         $sourceId = $this->source_livestock_id;
    //         $destinationId = $this->destination_livestock_id;

    //         foreach ($this->items as $item) {
    //             if (($item['type'] ?? 'feed') !== 'feed') continue;

    //             $feedId = $item['item_id'];
    //             $feed = Feed::findOrFail($feedId);

    //             // Ambil satuan yang digunakan user
    //             $unitId = $item['unit_id'];
    //             $inputQty = $item['quantity'];

    //             // Konversi ke satuan terkecil
    //             $conversionRate = $feed->conversionUnits()
    //                 ->where('conversion_unit_id', $unitId)
    //                 ->value('conversion_value');

    //             // Jika tidak ada rate, asumsikan 1 (berarti satuan terkecil)
    //             $rate = $conversionRate ?: 1;

    //             $requiredQty = $inputQty * $rate;

    //             // Ambil stok feed berdasarkan FIFO
    //             $stocks = FeedStock::where('livestock_id', $this->source_livestock_id)
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

    //         $mutation = MutationService::feedMutation([
    //             'date' => $this->tanggal,
    //             'source_livestock_id' => $this->source_livestock_id,
    //             'destination_livestock_id' => $this->destination_livestock_id,
    //             'notes' => $this->notes,
    //         ], $this->items, $this->mutationId); // jika mutationId null, akan dianggap create baru

    //         // // Buat entri mutasi utama
    //         // $mutation = FeedMutation::create([
    //         //     'id' => Str::uuid(),
    //         //     'date' => $this->tanggal,
    //         //     'from_livestock_id' => $this->source_livestock_id,
    //         //     'to_livestock_id' => $this->destination_livestock_id,
    //         //     'created_by' => auth()->id(),
    //         // ]);

    //         // // Proses FIFO pengambilan
    //         // $requiredQty = $inputQty;
    //         // foreach ($stocks as $stock) {
    //         //     if ($requiredQty <= 0) break;

    //         //     $available = $stock->quantity_in - $stock->quantity_used - $stock->quantity_mutated;
    //         //     $takeQty = min($available, $requiredQty);

    //         //     // Kurangi stok sumber
    //         //     $stock->quantity_mutated += $takeQty;
    //         //     $stock->save();

    //         //     // Hitung nilai amount secara proporsional
    //         //     $unitCost = $stock->amount / $stock->quantity_in;
    //         //     $totalAmount = $takeQty * $unitCost;

    //         //     // Tambahkan ke stok tujuan
    //         //     FeedStock::create([
    //         //         'id' => Str::uuid(),
    //         //         'livestock_id' => $destinationId,
    //         //         'feed_id' => $feedId,
    //         //         'feed_purchase_id' => $stock->feed_purchase_id,
    //         //         'date' => $tanggal,
    //         //         'source_id' => $mutation->id,
    //         //         'amount' => $totalAmount,
    //         //         'available' => $takeQty,
    //         //         'quantity_in' => $takeQty,
    //         //         'quantity_used' => 0,
    //         //         'quantity_mutated' => 0,
    //         //         'created_by' => auth()->id(),
    //         //     ]);

    //         //     // Detail mutasi
    //         //     FeedMutationItem::create([
    //         //         'feed_mutation_id' => $mutation->id,
    //         //         'feed_stock_id' => $stock->id,
    //         //         'feed_id' => $feedId,
    //         //         'quantity' => $takeQty,
    //         //         'created_by' => auth()->id(),
    //         //     ]);

    //         //     $requiredQty -= $takeQty;
    //         // }

    //         // $this->transferStock($this->all());

    //         $this->dispatch('success', 'Data Mutasi Pakan berhasil ' . ($this->edit_mode ? 'diperbarui' : 'disimpan'));
    //         $this->close();
    //     } catch (\Exception $e) {
    //         DB::rollBack(); // Rollback on any other exception

    //         $class = __CLASS__;
    //         $method = __FUNCTION__;
    //         $line = $e->getLine();
    //         $file = $e->getFile();
    //         $message = $e->getMessage();

    //         // Human-readable error message
    //         $errorMessage = 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.';

    //         // Dispatch user-friendly error
    //         $this->dispatch('error', $errorMessage);

    //         // Log detailed error for debugging
    //         Log::error("[$class::$method] Error: $message | Line: $line | File: $file");

    //         // Optionally: log stack trace
    //         Log::debug("[$class::$method] Stack trace: " . $e->getTraceAsString());
    //     }
    // }

    private function transferStock($data)
    {
        dd($data);
        try {
            DB::beginTransaction();

            $feedId = $data['feed_id'];
            $tanggal = $data['tanggal'];
            $quantity = $data['quantity'];
            $sourceId = $data['source_livestock_id'];
            $destinationId = $data['destination_livestock_id'];

            $feed = Feed::findOrFail($feedId);
            $source = Farm::findOrFail($sourceId);
            $destination = Farm::findOrFail($destinationId);

            // Cek stok FIFO di sumber
            $stocks = FeedStock::where('livestock_id', $sourceId)
                ->where('feed_id', $feedId)
                ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
                ->orderBy('date')
                ->orderBy('created_at')
                ->lockForUpdate()
                ->get();

            $totalAvailable = $stocks->sum(fn($s) => $s->quantity_in - $s->quantity_used - $s->quantity_mutated);

            // ✅ VALIDASI: Tanggal mutasi tidak boleh lebih kecil dari tanggal stok awal
            $earliestDate = $stocks->min('date');
            if (Carbon::parse($tanggal)->lt(Carbon::parse($earliestDate))) {
                throw new \Exception("Tanggal mutasi ($tanggal) tidak boleh lebih kecil dari tanggal pembelian pertama ($earliestDate).");
            }

            if ($totalAvailable < $quantity) {
                throw new \Exception("Stok tidak cukup untuk feed: $feed->name. Dibutuhkan: $quantity, Tersedia: $totalAvailable");
            }

            // Cek stok summary
            $sourceStockSummary = CurrentFeed::where('item_id', $feedId)
                ->where('livestock_id', $sourceId)
                ->lockForUpdate()
                ->first();

            if (!$sourceStockSummary || $sourceStockSummary->quantity < $quantity) {
                throw new Exception("Stok tidak mencukupi di sumber.");
            }

            // Buat entri mutasi utama
            $mutation = FeedMutation::create([
                'id' => Str::uuid(),
                'date' => $tanggal,
                'from_livestock_id' => $sourceId,
                'to_livestock_id' => $destinationId,
                'created_by' => auth()->id(),
            ]);

            // Proses FIFO pengambilan
            $requiredQty = $quantity;
            foreach ($stocks as $stock) {
                if ($requiredQty <= 0) break;

                $available = $stock->quantity_in - $stock->quantity_used - $stock->quantity_mutated;
                $takeQty = min($available, $requiredQty);

                // Kurangi stok sumber
                $stock->quantity_mutated += $takeQty;
                $stock->save();

                // Hitung nilai amount secara proporsional
                $unitCost = $stock->amount / $stock->quantity_in;
                $totalAmount = $takeQty * $unitCost;

                // Tambahkan ke stok tujuan
                FeedStock::create([
                    'id' => Str::uuid(),
                    'livestock_id' => $destinationId,
                    'feed_id' => $feedId,
                    'feed_purchase_id' => $stock->feed_purchase_id,
                    'date' => $tanggal,
                    'source_id' => $mutation->id,
                    'amount' => $totalAmount,
                    'available' => $takeQty,
                    'quantity_in' => $takeQty,
                    'quantity_used' => 0,
                    'quantity_mutated' => 0,
                    'created_by' => auth()->id(),
                ]);

                // Detail mutasi
                FeedMutationItem::create([
                    'feed_mutation_id' => $mutation->id,
                    'feed_stock_id' => $stock->id,
                    'feed_id' => $feedId,
                    'quantity' => $takeQty,
                    'created_by' => auth()->id(),
                ]);

                $requiredQty -= $takeQty;
            }

            // Recalculate CurrentFeed untuk source & destination
            // $this->recalculateCurrentFeed($feedId, $sourceId);
            // $this->recalculateCurrentFeed($feedId, $destinationId);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Stock transferred successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Stock transfer error: ' . $e->getMessage());
            throw $e;
        }
    }

    private function recalculateCurrentFeed($itemId, $livestockId)
    {
        $total = FeedStock::where('feed_id', $itemId)
            ->where('livestock_id', $livestockId)
            ->selectRaw('COALESCE(SUM(quantity_in - quantity_used - quantity_mutated), 0) as total')
            ->value('total');

        $livestock = Livestock::findOrFail($livestockId);
        $feed = Feed::findOrFail($itemId); // pastikan Feed punya field unit_id

        CurrentFeed::updateOrCreate(
            ['item_id' => $itemId, 'livestock_id' => $livestockId],
            [
                'quantity' => $total,
                'farm_id' => $livestock->farm_id,
                'kandang_id' => $livestock->kandang_id ?? null, // kalau ada
                'unit_id' => $feed->payload['unit_id'],
                'created_by' => auth()->id(),
            ]
        );
    }

    public function updatedSourceLivestockId()
    {
        $this->loadAvailableItems();
    }

    public function loadAvailableItems()
    {
        // $source = Farm::findOrFail($this->source_farm_id);

        $feedItems = FeedStock::where('livestock_id', $this->source_livestock_id)
            ->with('feed:id,name')
            ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
            ->get()
            ->groupBy('feed_id')
            ->map(fn($group) => [
                'id' => $group->first()->feed_id,
                'type' => 'feed',
                'name' => optional($group->first()->feed)->name,
            ]);

        // dd($feedItems);

        $this->availableItems = array_merge(
            $feedItems->values()->toArray()
        );

        // dd($this->availableItems);
    }

    public function delete_mutation($id)
    {
        try {
            DB::beginTransaction();

            // Load mutation with related data
            $mutation = MutationModel::with([
                'mutationItems', // tanpa ->with('item')
                'fromLivestock',
                'toLivestock'
            ])->findOrFail($id);
            

            // Validate if mutation can be deleted
            foreach ($mutation->mutationItems as $item) {
                if (!$item->item) {
                    continue; // Skip if item is null
                }

                // Check from livestock stock
                $fromStock = FeedStock::where('livestock_id', $mutation->from_livestock_id)
                    ->where('feed_id', $item->item_id)
                    ->where('source_type', 'mutation')
                    ->where('source_id', $mutation->id)
                    ->first();

                if ($fromStock) {
                    // Check if stock has been used
                    if (($fromStock->quantity_used ?? 0) > 0) {
                        throw new \Exception("Tidak dapat menghapus mutasi. Stok dari kandang {$mutation->fromLivestock->name} sudah digunakan.");
                    }

                    // Check if stock has been mutated again
                    if (($fromStock->quantity_mutated ?? 0) > 0) {
                        throw new \Exception("Tidak dapat menghapus mutasi. Stok dari kandang {$mutation->fromLivestock->name} sudah dimutasi lagi.");
                    }
                }

                // Check to livestock stock
                $toStock = FeedStock::where('livestock_id', $mutation->to_livestock_id)
                    ->where('feed_id', $item->item_id)
                    ->where('source_type', 'mutation')
                    ->where('source_id', $mutation->id)
                    ->first();

                if ($toStock) {
                    // Check if stock has been used
                    if (($toStock->quantity_used ?? 0) > 0) {
                        throw new \Exception("Tidak dapat menghapus mutasi. Stok ke kandang {$mutation->toLivestock->name} sudah digunakan.");
                    }

                    // Check if stock has been mutated again
                    if (($toStock->quantity_mutated ?? 0) > 0) {
                        throw new \Exception("Tidak dapat menghapus mutasi. Stok ke kandang {$mutation->toLivestock->name} sudah dimutasi lagi.");
                    }
                }
            }

            // Delete related FeedStock records
            FeedStock::where('source_type', 'mutation')
                ->where('source_id', $mutation->id)
                ->delete();

            // Delete mutation items
            $mutation->mutationItems()->delete();

            // Delete the mutation
            $mutation->delete();

            DB::commit();
            $this->dispatch('success', 'Data mutasi berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('error', $e->getMessage());
        }
    }
}

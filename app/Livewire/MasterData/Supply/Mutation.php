<?php

namespace App\Livewire\MasterData\Supply;

use App\Models\CurrentSupply;
use App\Models\Farm;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

use App\Models\Mutation as MutationModel;
use App\Models\Supply;
use App\Models\SupplyMutation;
use App\Models\SupplyMutationItem;
use App\Models\SupplyStock;
use App\Models\Livestock;
use App\Models\Unit;
use App\Models\UnitConversion;
use App\Services\MutationService;
use Exception;
use Illuminate\Support\Facades\Log;

class Mutation extends Component
{
    public $supplyId, $supply_id, $source_livestock_id, $destination_livestock_id, $availableItems = [];
    public $code, $name, $category, $type, $description, $volume;
    public $farms, $source_farm_id, $destination_farm_id, $dstFarms;
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
    public $source_id, $source_name, $livestocks = [], $supplys = [];
    public $availableStock, $availableUnit;
    public bool $withHistory = false;

    protected $listeners = [
        'showMutationForm' => 'showMutationForm',
        'showCreateForm' => 'showCreateForm',
        'cancel' => 'cancel',
        'editSupplyMutation' => 'editSupplyMutation',
        'addConversion' => 'addConversion',
        'deleteSupplyMutation' => 'deleteSupplyMutation',

    ];

    // Definisikan aturan validasi
    protected $rules = [
        'destination_farm_id' => 'required|uuid|different:source_farm_id',
        'supply_id' => 'required|uuid',
        'quantity' => 'required|numeric|min:0.01|lte:availableStock',
        'tanggal' => 'required|date',
        'notes' => 'nullable|string|max:255',
    ];

    // Definisikan pesan error kustom (opsional)
    protected $messages = [
        'destination_farm_id.required' => 'Tujuan harus dipilih.',
        'destination_farm_id.uuid' => 'Tujuan tidak valid.',
        'destination_farm_id.different' => 'Tujuan tidak boleh sama dengan asal.',
        'supply_id.required' => 'Item harus dipilih.',
        'supply_id.uuid' => 'Item tidak valid.',
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
        $this->dstFarms = Farm::all();
        $this->farms = SupplyStock::distinct('farm_id')
            ->with('farm:id,name') // Eager load the 'farm' relationship to access the name
            ->get()
            ->pluck('farm');
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
            'source_farm_id' => 'required|exists:farms,id',
            'destination_farm_id' => 'required|uuid|different:source_farm_id',
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

        if ($selected['type'] === 'supply') {
            $supply = Supply::find($itemId);
            $this->items[$index]['units'] = $supply?->conversionUnits?->map(fn($u) => [
                'id' => $u->conversion_unit_id,
                'name' => optional($u->conversionUnit)->name,
            ])->toArray() ?? [];

            $stocks = SupplyStock::where('farm_id', $this->source_farm_id)
                ->where('supply_id', $itemId)
                ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
                ->orderBy('date')
                ->orderBy('created_at')
                ->get();

            $totalAvailable = $stocks->sum(fn($s) => $s->quantity_in - $s->quantity_used - $s->quantity_mutated);
            $this->items[$index]['available_stock'] = $totalAvailable;
            $this->items[$index]['smallest_unit_name'] = $supply->payload['unit_details']['name'] ?? '';
        }
    }

    public function editSupplyMutation($id)
    {
        $this->loadFarms();
        $mutation = MutationModel::with(['mutationItems.supply', 'fromFarm', 'toFarm'])->lockForUpdate()->findOrFail($id);

        $this->mutationId = $mutation->id;
        $this->tanggal = $mutation->date->format('Y-m-d');
        $this->source_farm_id = $mutation->from_farm_id;
        $this->destination_farm_id = $mutation->to_farm_id;
        $this->notes = $mutation->notes;

        $this->items = $mutation->mutationItems->map(function ($item) {
            $supply = $item->supply;
            $unitId = $item->unit_metadata['input_unit_id'] ?? null;
            $conversionUnits = $supply->payload['conversion_units'] ?? [];
            $defaultUnitId = $unitId;
            $conversionRate = collect($conversionUnits)
                ->where('unit_id', $defaultUnitId)
                ->first()['value'] ?? 1;

            return [
                'item_id' => $supply->id,
                'type' => 'supply',
                'unit_id' => $defaultUnitId,
                'quantity' => $item->quantity / $conversionRate,
                // 'units' => [], // will be filled by handleItemSelected
                // 'available_stock' => 0, // will be filled by handleItemSelected
            ];
        })->toArray();

        $this->loadAvailableItems();

        // Hydrate units and available_stock for each item
        foreach ($this->items as $index => $item) {
            $this->handleItemSelected($index);
        }

        // dd($this->items);

        $this->edit_mode = true;
        $this->showForm = true;
        $this->dispatch('hide-datatable');
    }

    public function render()
    {
        return view('livewire.master-data.supply.mutation', [
            'units' => Unit::all(),
        ]);
    }

    public function showCreateForm()
    {
        $this->loadFarms();
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
        $this->loadFarms();
        $this->showForm = true;
        $this->dispatch('hide-datatable');
    }

    public function updated($propertyName)
    {
        // Dipanggil setiap kali properti Livewire berubah
        if ($propertyName === 'supply_id' || $propertyName === 'source_livestock_id') {
            $this->checkAvailableStock();
        }
    }

    public function checkAvailableStock()
    {
        if ($this->source_livestock_id && $this->supply_id) {
            $stock = SupplyStock::where('livestock_id', $this->source_livestock_id)
                ->where('supply_id', $this->supply_id)
                ->first();

            // dd($stock);

            if ($stock) {
                $this->availableStock = $stock->quantity_in - $stock->quantity_used - $stock->quantity_mutated;
                $this->availableUnit = $stock->supply->payload['unit_details']['name'] ?? '';
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
            foreach ($this->items as $item) {
                if (($item['type'] ?? 'supply') !== 'supply') continue;

                $supplyId = $item['item_id'];
                $feed = Supply::findOrFail($supplyId);

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
                $stocks = SupplyStock::where('farm_id', $this->source_farm_id)
                    ->where('supply_id', $supplyId)
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

            $mutation = MutationService::supplyMutationWithHistoryControl([
                'date' => $this->tanggal,
                'source_farm_id' => $this->source_farm_id,
                'destination_farm_id' => $this->destination_farm_id,
                'notes' => $this->notes,
            ], $this->items, $this->mutationId, $this->withHistory); // jika mutationId null, akan dianggap create baru

            // $this->transferStock($this->all());

            $this->dispatch('success', 'Data Mutasi Pakan berhasil ' . ($this->edit_mode ? 'diperbarui' : 'disimpan'));
            $this->close();
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback on any other exception

            $class = __CLASS__;
            $method = __FUNCTION__;
            $line = $e->getLine();
            $file = $e->getFile();
            $message = $e->getMessage();

            // Human-readable error message
            $errorMessage = 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.';

            // Dispatch user-friendly error
            $this->dispatch('error', $errorMessage);

            // Log detailed error for debugging
            Log::error("[$class::$method] Error: $message | Line: $line | File: $file");

            // Optionally: log stack trace
            Log::debug("[$class::$method] Stack trace: " . $e->getTraceAsString());
        }
    }

    private function transferStock($data)
    {
        dd($data);
        try {
            DB::beginTransaction();

            $supplyId = $data['supply_id'];
            $tanggal = $data['tanggal'];
            $quantity = $data['quantity'];
            $sourceId = $data['source_farm_id'];
            $destinationId = $data['destination_farm_id'];

            $supply = Supply::findOrFail($supplyId);
            $source = Farm::findOrFail($sourceId);
            $destination = Farm::findOrFail($destinationId);

            // Cek stok FIFO di sumber
            $stocks = SupplyStock::where('farm_id', $sourceId)
                ->where('supply_id', $supplyId)
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
                throw new \Exception("Stok tidak cukup untuk supply: $supply->name. Dibutuhkan: $quantity, Tersedia: $totalAvailable");
            }

            // Cek stok summary
            $sourceStockSummary = CurrentSupply::where('item_id', $supplyId)
                ->where('livestock_id', $sourceId)
                ->lockForUpdate()
                ->first();

            if (!$sourceStockSummary || $sourceStockSummary->quantity < $quantity) {
                throw new Exception("Stok tidak mencukupi di sumber.");
            }

            // Buat entri mutasi utama
            $mutation = SupplyMutation::create([
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
                SupplyStock::create([
                    'id' => Str::uuid(),
                    'livestock_id' => $destinationId,
                    'supply_id' => $supplyId,
                    'supply_purchase_id' => $stock->supply_purchase_id,
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
                SupplyMutationItem::create([
                    'supply_mutation_id' => $mutation->id,
                    'supply_stock_id' => $stock->id,
                    'supply_id' => $supplyId,
                    'quantity' => $takeQty,
                    'created_by' => auth()->id(),
                ]);

                $requiredQty -= $takeQty;
            }

            // Recalculate CurrentSupply untuk source & destination
            // $this->recalculateCurrentSupply($supplyId, $sourceId);
            // $this->recalculateCurrentSupply($supplyId, $destinationId);

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

    private function recalculateCurrentSupply($itemId, $livestockId)
    {
        $total = SupplyStock::where('supply_id', $itemId)
            ->where('livestock_id', $livestockId)
            ->selectRaw('COALESCE(SUM(quantity_in - quantity_used - quantity_mutated), 0) as total')
            ->value('total');

        $livestock = Livestock::findOrFail($livestockId);
        $supply = Supply::findOrFail($itemId); // pastikan Supply punya field unit_id

        CurrentSupply::updateOrCreate(
            ['item_id' => $itemId, 'livestock_id' => $livestockId],
            [
                'quantity' => $total,
                'farm_id' => $livestock->farm_id,
                'coop_id' => $livestock->coop_id ?? null, // kalau ada
                'unit_id' => $supply->payload['unit_id'],
                'created_by' => auth()->id(),
            ]
        );
    }

    public function updatedSourceFarmId()
    {
        $this->loadAvailableItems();
    }

    public function loadAvailableItems()
    {
        // $source = Farm::findOrFail($this->source_farm_id);

        $supplyItems = SupplyStock::where('farm_id', $this->source_farm_id)
            ->with('supply:id,name')
            ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
            ->get()
            ->groupBy('supply_id')
            ->map(fn($group) => [
                'id' => $group->first()->supply_id,
                'type' => 'supply',
                'name' => optional($group->first()->supply)->name,
            ]);

        // dd($supplyItems);

        $this->availableItems = array_merge(
            $supplyItems->values()->toArray()
        );

        // dd($this->availableItems);
    }

    public function deleteSupplyMutation($id)
    {
        Log::info('Method delete mutasi di Livewire terpanggil untuk ID:', ['id' => $id]);

        try {
            $mutation = MutationModel::with('mutationItems')->findOrFail($id);

            // Check if mutation has related items
            if ($mutation->mutationItems->isNotEmpty()) {
                foreach ($mutation->mutationItems as $item) {
                    // Check if the feed stock is being used
                    $supplyStock = SupplyStock::where('id', $item->stock_id)
                        ->where('quantity_used', '>', 0)
                        ->first();

                    if ($supplyStock) {
                        $this->dispatch('error', 'Tidak dapat menghapus mutasi karena stok pakan sudah digunakan.');
                        return;
                    }
                }
            }

            // If all validations pass, proceed with deletion
            $mutation->mutationItems()->delete(); // Delete related items first
            $mutation->delete(); // Then delete the mutation

            // Additionally, delete related SupplyStock records
            SupplyStock::where('source_id', $id)->where('source_type', 'mutation')->delete();

            $this->dispatch('success', 'Data Mutasi berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error('Error deleting mutation:', ['error' => $e->getMessage()]);
            $this->dispatch('error', 'Terjadi kesalahan saat menghapus mutasi.');
        }
    }

    private function loadFarms()
    {
        $this->dstFarms = Farm::all();
        $this->farms = SupplyStock::distinct('farm_id')
            ->with('farm:id,name')
            ->get()
            ->pluck('farm');
    }
}

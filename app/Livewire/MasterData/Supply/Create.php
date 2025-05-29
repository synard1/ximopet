<?php

namespace App\Livewire\MasterData\Supply;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

use App\Models\Supply;
use App\Models\Unit;
use App\Models\UnitConversion;
use App\Models\SupplyCategory;

class Create extends Component
{
    public $supplyId;
    public $code, $name, $category, $type, $description, $volume;
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
    public $conversions = []; // untuk satuan konversi
    public $unit_id;
    public $conversion_units = [];
    public $default_unit_id = null;

    protected $listeners = [
        'showEditForm' => 'showEditForm',
        'showCreateForm' => 'showCreateForm',
        'cancel' => 'cancel',
        'edit' => 'edit',
        'addConversion' => 'addConversion',
        'deleteSupply' => 'deleteSupply',

    ];

    public function mount()
    {
        // $this->units = Unit::all();
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

    public function render()
    {
        return view('livewire.master-data.supply.create', [
            'units' => Unit::all(),
            'types' => SupplyCategory::all(),
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
        $this->resetForm();
        $this->showForm = false;
        $this->dispatch('show-datatable');
    }

    public function showEditForm($id)
    {
        $supply = Supply::with('conversionUnits.unit')->findOrFail($id);

        $this->supplyId = $supply->id;
        $this->code = $supply->code;
        $this->name = $supply->name;
        $this->type = $supply->supplyCategory->name;
        $this->unit_id = $supply->payload['unit_id'];
        $this->description = $supply->description;
        $this->conversion_units = $supply->payload['conversion_units'] ?? [];

        $this->showForm = true;
        $this->edit_mode = true;
        $this->dispatch('hide-datatable');
    }

    public function save()
    {
        $this->validate([
            'code' => 'required',
            'name' => 'required',
            'type' => 'required', // Contoh dengan aturan tambahan
        ]);

        $this->validateConversionDefaults();

        DB::transaction(function () {
            // Buat payload yang akan disimpan ke field `payload`
            $payload = [
                'unit_id' => $this->unit_id,
                'unit_details' => Unit::find($this->unit_id)?->only('id', 'name', 'description'),
                'conversion_units' => collect($this->conversion_units)->map(function ($conv) {
                    $unit = Unit::find($conv['unit_id'])?->only('id', 'name', 'description');

                    // dd($unit);
                    return [
                        'unit_id' => $conv['unit_id'],
                        'unit_name' => $unit['name'],
                        'value' => $conv['value'],
                        'is_default_purchase' => $conv['is_default_purchase'] ?? false,
                        'is_default_mutation' => $conv['is_default_mutation'] ?? false,
                        'is_default_sale' => $conv['is_default_sale'] ?? false,
                        'is_smallest' => $conv['is_smallest'] ?? false,
                    ];
                })->toArray(),
            ];

            // Simpan atau update Supply
            $supply = $this->edit_mode && $this->supplyId
                ? Supply::findOrFail($this->supplyId)
                : new Supply(['created_by' => auth()->id()]);

            $supplyCategory = SupplyCategory::where('name', $this->type)->first();

            // dd($supplyCategory);

            $supply->fill([
                'code' => $this->code,
                'name' => $this->name,
                'payload' => $payload,
                'supply_category_id' => $supplyCategory->id,
            ])->save();

            // Bersihkan semua konversi lama untuk supply ini
            // UnitConversion::where('type', 'Supply')->where('item_id', $supply->id)->delete();

            // Simpan ulang konversi, hindari duplikasi
            $uniqueConversions = collect($this->conversion_units)
                ->unique(fn($conv) => $this->unit_id . '-' . $conv['unit_id']);

            foreach ($uniqueConversions as $conversion) {
                UnitConversion::updateOrCreate(
                    [
                        'type' => $this->type,
                        'item_id' => $supply->id,
                        'unit_id' => $this->unit_id,
                        'conversion_unit_id' => $conversion['unit_id'],
                    ],
                    [
                        'conversion_value' => $conversion['value'],
                        'default_purchase' => $conversion['is_default_purchase'] ?? false,
                        'default_mutation' => $conversion['is_default_mutation'] ?? false,
                        'default_sale' => $conversion['is_default_sale'] ?? false,
                        'smallest' => $conversion['is_smallest'] ?? false,
                        'created_by' => auth()->id(),
                    ]
                );
            }
        });

        $this->dispatch('success', 'Data Pakan berhasil ' . ($this->edit_mode ? 'diperbarui' : 'disimpan'));
        $this->close();
    }

    public function deleteSupply($id)
    {
        $supply = Supply::findOrFail($id);

        if ($supply->supplyPurchase->count() > 0) {
            $this->dispatch('error', 'Data Supply tidak bisa dihapus karena sudah memiliki data pembelian');
            return;
        }

        $supply->delete();
        $this->dispatch('success', 'Data Supply berhasil dihapus');
    }
}

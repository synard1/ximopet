<?php

namespace App\Livewire\MasterData\Feed;

use App\Traits\HasTempAuthorization;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

use App\Models\Feed;
use App\Models\Unit;
use App\Models\UnitConversion;

class Create extends Component
{
    use HasTempAuthorization;

    public $feedId;
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
    // public $units = []; // ambil dari DB untuk dropdown
    public $unit_id;
    public $conversion_units = [];
    public $default_unit_id = null;
    public $tempAuthEnabled = false;

    protected $listeners = [
        'showEditForm' => 'showEditForm',
        'showCreateForm' => 'showCreateForm',
        'cancel' => 'cancel',
        'edit' => 'edit',
        'addConversion' => 'addConversion',
        'delete_feed' => 'deleteFeed',
        'confirmDeleteFeed' => 'confirmDeleteFeed',
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
            // 'type' => 'required', // Contoh dengan 'in'
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
            // 'type' => 'required', // Contoh dengan 'in'
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




    public function render()
    {
        return view('livewire.master-data.feed.create', [
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
        // $this->items = [
        //     ['feed_id' => '', 'quantity' => 1, 'price_per_unit' => 0],
        // ];
    }

    public function close()
    {
        // $this->dispatch('closeForm');
        $this->resetForm();
        $this->showForm = false;
        $this->dispatch('show-datatable');
    }

    public function showEditForm($id)
    {
        $feed = Feed::with('conversionUnits.unit')->findOrFail($id);

        $this->feedId = $feed->id;
        $this->code = $feed->code;
        $this->name = $feed->name;
        $this->type = "Feed";
        $this->unit_id = $feed->data['unit_id'];
        $this->description = $feed->description;
        $this->conversion_units = $feed->data['conversion_units'] ?? [];


        // $this->conversion_units = $feed->conversionUnits->map(function ($item) {
        //     return [
        //         'unit_id' => $item->unit_id,
        //         'unit_name' => optional($item->unit)->name,
        //         'value' => $item->value,
        //         'is_default_purchase' => $item->is_default_purchase,
        //         'is_default_mutation' => $item->is_default_mutation,
        //         'is_default_sale' => $item->is_default_sale,
        //         'is_smallest' => $item->is_smallest,
        //     ];
        // })->toArray();

        $this->showForm = true;
        $this->edit_mode = true;
        $this->dispatch('hide-datatable');
    }

    public function save()
    {
        $this->validate([
            'code' => 'required',
            'name' => 'required',
            // 'type' => 'required|in:Feed,Supplement,Medicine,Others', // Contoh dengan aturan tambahan
        ]);

        $this->validateConversionDefaults();

        if (!auth()->user()->can('create feed master data') && !$this->edit_mode) {
            $this->addError('create', 'You do not have permission to create feed master data.');
            return;
        }

        if (!auth()->user()->can('update feed master data') && $this->edit_mode) {
            $this->addError('update', 'You do not have permission to update feed master data.');
            return;
        }

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

            // Simpan atau update Feed
            $feed = $this->edit_mode && $this->feedId
                ? Feed::findOrFail($this->feedId)
                : new Feed(['created_by' => auth()->id()]);

            $feed->fill([
                'code' => $this->code,
                'name' => $this->name,
                'payload' => $payload,
            ])->save();

            // Bersihkan semua konversi lama untuk feed ini
            // UnitConversion::where('type', 'Feed')->where('item_id', $feed->id)->delete();

            // Simpan ulang konversi, hindari duplikasi
            $uniqueConversions = collect($this->conversion_units)
                ->unique(fn($conv) => $this->unit_id . '-' . $conv['unit_id']);

            foreach ($uniqueConversions as $conversion) {
                UnitConversion::updateOrCreate(
                    [
                        'type' => 'Feed',
                        'item_id' => $feed->id,
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

    public function deleteFeed($feedId)
    {
        if (!auth()->user()->can('delete feed master data')) {
            $this->addError('delete', 'You do not have permission to delete feed master data.');
            return;
        }

        try {
            // Check if feed has unit conversions
            $hasUnitConversions = DB::table('unit_conversions')
                ->where('type', 'Feed')
                ->where('item_id', $feedId)
                ->whereNull('deleted_at')
                ->exists();

            if ($hasUnitConversions) {
                // Dispatch event to show warning popup
                $this->dispatch('show-delete-warning', [
                    'feedId' => $feedId,
                    'message' => 'Feed ini memiliki konversi satuan. Apakah Anda yakin ingin menghapusnya?'
                ]);
                return;
            }

            // If no unit conversions, proceed with deletion
            $this->confirmDeleteFeed($feedId);
        } catch (\Exception $e) {
            $this->dispatch('error', $e->getMessage());
        }
    }

    public function confirmDeleteFeed($feedId)
    {
        try {
            DB::beginTransaction();

            // Check if feed is used in FeedPurchases
            $usedInPurchases = DB::table('feed_purchases')
                ->where('feed_id', $feedId)
                ->whereNull('deleted_at')
                ->exists();

            // Check if feed is used in FeedMutations
            $usedInMutations = DB::table('feed_mutation_items')
                ->where('feed_id', $feedId)
                ->whereNull('deleted_at')
                ->exists();

            if ($usedInPurchases || $usedInMutations) {
                throw new \Exception('Feed tidak dapat dihapus karena masih digunakan dalam transaksi pembelian atau mutasi.');
            }

            // Soft delete related unit conversions
            DB::table('unit_conversions')
                ->where('type', 'Feed')
                ->where('item_id', $feedId)
                ->update(['deleted_at' => now()]);

            // Soft delete the feed
            Feed::findOrFail($feedId)->delete();

            DB::commit();
            $this->dispatch('success', 'Feed berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('error', $e->getMessage());
        }
    }
}

<?php

namespace App\Livewire\MasterData\Livestock;

use App\Models\Livestock;
use App\Models\Farm;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

use App\Models\Mutation as MutationModel;
use App\Models\MutationItem;
use App\Models\LivestockBatch;
use App\Models\CurrentLivestock;
use App\Services\MutationService;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Models\Coop;

class Mutation extends Component
{
    public $livestockId, $source_livestock_id, $destination_livestock_id;
    public $farms, $source_farm_id, $destination_farm_id, $dstLivestocks, $srcLivestocks;
    public $mutationId;
    public $date, $tanggal, $notes;
    public $quantity, $weight;

    public $items = [];
    public $livestock_id;
    public $showForm = false;
    public $edit_mode = false;
    public $errorItems = [];
    public $total_weight_estimation = 0;
    public bool $withHistory = false;

    protected $listeners = [
        'showMutationForm' => 'showMutationForm',
        'showEditForm' => 'showEditForm',
        'showCreateForm' => 'showCreateForm',
        'delete_mutation' => 'delete_mutation',
        'cancel' => 'cancel',
        'edit' => 'edit',
    ];

    protected $rules = [
        'destination_livestock_id' => 'required|uuid|different:source_livestock_id',
        'tanggal' => 'required|date',
        'notes' => 'nullable|string|max:255',
        'items' => 'required|array|min:1',
        'items.*.quantity' => 'required|numeric|gt:0',
        'items.*.weight' => 'required|numeric|gt:0',
    ];

    protected $messages = [
        'destination_livestock_id.required' => 'Tujuan harus dipilih.',
        'destination_livestock_id.uuid' => 'Tujuan tidak valid.',
        'destination_livestock_id.different' => 'Tujuan tidak boleh sama dengan asal.',
        'tanggal.required' => 'Tanggal mutasi harus diisi.',
        'tanggal.date' => 'Format tanggal tidak valid.',
        'notes.max' => 'Catatan maksimal 255 karakter.',
        'items.required' => 'Minimal satu item harus ditambahkan.',
        'items.*.quantity.required' => 'Jumlah harus diisi.',
        'items.*.quantity.numeric' => 'Jumlah harus berupa angka.',
        'items.*.quantity.gt' => 'Jumlah harus lebih dari 0.',
        'items.*.weight.required' => 'Berat harus diisi.',
        'items.*.weight.numeric' => 'Berat harus berupa angka.',
        'items.*.weight.gt' => 'Berat harus lebih dari 0.',
    ];

    public function mount()
    {
        // Get all active livestocks for destination
        $this->dstLivestocks = Livestock::where('status', '!=', Livestock::STATUS_CANCELLED)
            ->where('status', '!=', Livestock::STATUS_COMPLETED)
            ->when(auth()->user()->hasRole('Operator'), function ($query) {
                $query->whereHas('farm.farmOperators', function ($q) {
                    $q->where('user_id', auth()->id());
                });
            })
            ->get();

        // If no livestocks found, get active/in_use coops
        if ($this->dstLivestocks->isEmpty()) {
            $this->dstLivestocks = Coop::whereIn('status', ['active', 'in_use'])
                ->when(auth()->user()->hasRole('Operator'), function ($query) {
                    $query->whereHas('farm.farmOperators', function ($q) {
                        $q->where('user_id', auth()->id());
                    });
                })
                ->get();
        }

        // Get source livestocks that have available stock
        $this->srcLivestocks = Livestock::whereIn('id', function ($query) {
            $query->select('livestock_id')
                ->from('current_livestocks')
                ->where('status', 'active')
                ->where('quantity', '>', 0)
                ->groupBy('livestock_id');
        })
            ->when(auth()->user()->hasRole('Operator'), function ($query) {
                $query->whereHas('farm.farmOperators', function ($q) {
                    $q->where('user_id', auth()->id());
                });
            })
            ->with('farm')
            ->get();
    }

    public function updatedSourceLivestockId($value)
    {
        // Filter destination livestocks to exclude the selected source
        if ($value) {
            $this->dstLivestocks = Livestock::where('status', '!=', Livestock::STATUS_CANCELLED)
                ->where('status', '!=', Livestock::STATUS_COMPLETED)
                ->where('id', '!=', $value)
                ->get();

            // If no livestocks found, get active/in_use coops
            if ($this->dstLivestocks->isEmpty()) {
                $this->dstLivestocks = Coop::whereIn('status', ['active', 'in_use'])
                    ->whereNotIn('id', function ($query) use ($value) {
                        $query->select('coop_id')
                            ->from('livestocks')
                            ->where('id', $value);
                    })
                    ->get();
            }
        } else {
            $this->dstLivestocks = Livestock::where('status', '!=', Livestock::STATUS_CANCELLED)
                ->where('status', '!=', Livestock::STATUS_COMPLETED)
                ->get();
        }

        // Reset destination selection if it matches the new source
        if ($this->destination_livestock_id === $value) {
            $this->destination_livestock_id = null;
        }

        $this->loadAvailableItems();
    }

    /**
     * Hitung berat ayam sebenarnya secara realtime
     * @param string $livestockId
     * @return array ['berat_rata2' => float, 'populasi' => int, 'berat_total' => float]
     */
    private function getActualWeight($livestockId)
    {
        $livestock = Livestock::find($livestockId);
        if (!$livestock) return [
            'berat_rata2' => 0,
            'populasi' => 0,
            'berat_total' => 0
        ];

        // 1. Hitung berat rata-rata per ekor
        $initialWeight = $livestock->initial_weight ?? 0;
        $totalKenaikan = \App\Models\Recording::where('livestock_id', $livestockId)
            ->sum('kenaikan_berat');
        $beratRata2 = $initialWeight + $totalKenaikan;

        // 2. Hitung populasi saat ini
        $initialQty = $livestock->initial_quantity ?? 0;
        $totalDeplesi = \App\Models\LivestockDepletion::where('livestock_id', $livestockId)
            ->sum('jumlah');
        $populasiSaatIni = $initialQty - $totalDeplesi;

        // 3. Hitung berat total
        $beratTotal = $beratRata2 * $populasiSaatIni;

        return [
            'berat_rata2' => $beratRata2,
            'populasi' => $populasiSaatIni,
            'berat_total' => $beratTotal
        ];
    }

    public function loadAvailableItems()
    {
        if (!$this->source_livestock_id) return;

        $currentLivestock = CurrentLivestock::where('livestock_id', $this->source_livestock_id)
            ->where('status', 'active')
            ->first();

        $weightData = $this->getActualWeight($this->source_livestock_id);

        if ($currentLivestock) {
            $this->items = [[
                'livestock_id' => $this->source_livestock_id,
                'quantity' => $weightData['populasi'],
                'weight' => $weightData['berat_total'],
                'available_quantity' => $weightData['populasi'],
                'available_weight' => $weightData['berat_total'],
            ]];
        }
    }

    public function addItem()
    {
        $this->validate([
            'tanggal' => 'required|date',
            'source_livestock_id' => 'required|exists:livestocks,id',
            'destination_livestock_id' => 'required|uuid|different:source_livestock_id',
        ]);

        $currentLivestock = CurrentLivestock::where('livestock_id', $this->source_livestock_id)
            ->where('status', 'active')
            ->first();

        $weightData = $this->getActualWeight($this->source_livestock_id);

        if ($currentLivestock) {
            $this->items[] = [
                'livestock_id' => $this->source_livestock_id,
                'quantity' => $weightData['populasi'],
                'weight' => $weightData['berat_total'],
                'available_quantity' => $weightData['populasi'],
                'available_weight' => $weightData['berat_total'],
            ];
        }
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function updatedItems($value, $key)
    {
        // Jika field yang diupdate adalah quantity, hitung ulang berat total
        if (preg_match('/^(\d+)\.quantity$/', $key, $matches)) {
            $index = (int)$matches[1];
            $item = $this->items[$index] ?? null;
            if ($item && !empty($item['livestock_id'])) {
                $weightData = $this->getActualWeight($item['livestock_id']);
                $qty = (int)($this->items[$index]['quantity'] ?? 0);
                $beratRata2 = $weightData['berat_rata2'] ?? 0;
                $this->items[$index]['weight'] = $qty * $beratRata2;
                $this->total_weight_estimation = $this->items[$index]['weight'];
            }
        }
        // Tetap jalankan handler lama jika livestock_id berubah
        if (str_ends_with($key, 'livestock_id')) {
            $index = explode('.', $key)[0];
            $this->handleItemSelected($index);
        }
    }

    public function handleItemSelected($index)
    {
        $livestockId = $this->items[$index]['livestock_id'] ?? null;
        if (!$livestockId) return;

        $livestock = Livestock::find($livestockId);
        if (!$livestock) return;

        $currentLivestock = CurrentLivestock::where('livestock_id', $livestockId)
            ->where('status', 'active')
            ->first();

        if ($currentLivestock) {
            $this->items[$index]['available_quantity'] = $currentLivestock->quantity;
            $this->items[$index]['available_weight'] = $currentLivestock->weight_total;
        }
    }

    public function render()
    {
        return view('livewire.master-data.livestock.mutation');
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
        $mutation = MutationModel::with(['mutationItems.livestock', 'fromLivestock', 'toLivestock'])
            ->lockForUpdate()
            ->findOrFail($id);

        $this->mutationId = $mutation->id;
        $this->tanggal = $mutation->date->format('Y-m-d');
        $this->source_livestock_id = $mutation->from_livestock_id;
        $this->destination_livestock_id = $mutation->to_livestock_id;
        $this->notes = $mutation->notes;

        $this->items = $mutation->mutationItems->map(function ($item) {
            return [
                'livestock_id' => $item->item_id,
                'quantity' => $item->quantity,
                'weight' => $item->weight ?? 0,
            ];
        })->toArray();

        $this->edit_mode = true;
        $this->showForm = true;
        $this->dispatch('hide-datatable');
    }

    public function save()
    {
        $this->errorItems = [];

        try {
            $this->validate();

            // Validate each item's quantity and weight against available stock
            foreach ($this->items as $index => $item) {
                $livestock = Livestock::find($item['livestock_id']);
                $currentLivestock = CurrentLivestock::where('livestock_id', $item['livestock_id'])
                    ->where('status', 'active')
                    ->first();

                if (!$currentLivestock) {
                    $this->errorItems[$index] = "Stok ternak tidak ditemukan.";
                    continue;
                }

                if ($currentLivestock->quantity < $item['quantity']) {
                    $this->errorItems[$index] = "Jumlah mutasi melebihi stok yang tersedia. Tersedia: {$currentLivestock->quantity}";
                }

                if ($currentLivestock->weight_total < $item['weight']) {
                    $this->errorItems[$index] = "Berat mutasi melebihi stok yang tersedia. Tersedia: {$currentLivestock->weight_total}";
                }
            }

            if (!empty($this->errorItems)) {
                $this->dispatch('validation-errors', ['errors' => array_values($this->errorItems)]);
                return;
            }

            // Create/update the mutation
            $mutation = MutationService::livestockMutation([
                'date' => $this->tanggal,
                'source_livestock_id' => $this->source_livestock_id,
                'destination_livestock_id' => $this->destination_livestock_id,
                'notes' => $this->notes,
            ], $this->items, $this->mutationId, $this->withHistory);

            $this->dispatch('success', 'Data Mutasi Ternak berhasil ' . ($this->edit_mode ? 'diperbarui' : 'disimpan'));
            $this->close();
        } catch (\Exception $e) {
            Log::error("Error in livestock mutation: " . $e->getMessage());
            $this->dispatch('error', $e->getMessage());
        }
    }

    public function delete_mutation($id)
    {
        try {
            DB::beginTransaction();

            $mutation = MutationModel::with([
                'mutationItems',
                'fromLivestock',
                'toLivestock'
            ])->findOrFail($id);

            // Validate if mutation can be deleted
            foreach ($mutation->mutationItems as $item) {
                if (!$item->item) continue;

                // Check if the livestock has been used in other operations
                $currentLivestock = CurrentLivestock::where('livestock_id', $item->item_id)
                    ->where('status', 'active')
                    ->first();

                if ($currentLivestock && $currentLivestock->quantity > 0) {
                    throw new \Exception("Tidak dapat menghapus mutasi. Ternak masih aktif di kandang tujuan.");
                }
            }

            // Delete related records
            $mutation->mutationItems()->delete();
            $mutation->delete();

            DB::commit();
            $this->dispatch('success', 'Data mutasi berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('error', $e->getMessage());
        }
    }
}

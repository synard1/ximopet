<?php

namespace App\Livewire\MasterData;

use Livewire\Component;
use App\Models\Farm;
use App\Models\Coop;
use App\Models\Livestock;
use App\Models\LivestockPurchase;
use App\Models\CurrentLivestock;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KandangForm extends Component
{
    public $farm_id;
    public $coop_id;
    public $code;
    public $name;
    public $capacity;
    public $status = 'active';
    public $farms;
    public $isOpen = false;
    public $isEdit = false;

    protected $listeners = [
        'openModal' => 'openModal',
        'createCoop' => 'createKandang',
        'editKandang' => 'editKandang',
        'delete_kandang' => 'deleteKandang',
        'closeModalFarm' => 'closeModalFarm',
    ];

    protected $rules = [
        'farm_id' => 'required',
        'code' => 'required|unique:coops,code',
        'name' => 'required|string|max:255',
        'capacity' => 'required|numeric|min:1',
        'status' => 'required|in:active,inactive'
];

    public function mount()
    {
        if (!Auth::user()->can('create coop master data')) {
            $this->dispatch('error', 'You do not have permission to create coop.');
            return;
        }

        if (auth()->user()->hasRole('SuperAdmin')) {
            $this->farms = Farm::where('status', 'active')->get();
        } else {
            $this->farms = Farm::where('status', 'active')->where('company_id', auth()->user()->company_id)->get();
        }
    }

    public function render()
    {
        return view('livewire.master-data.kandang-form');
    }

    public function createKandang()
    {
        $this->isEdit = false;
        $this->openModal();
        // $this->dispatch('success', 'Kandang berhasil ditambahkan');
    }

    public function openModal()
    {
        $this->isOpen = true;
    }

    public function closeModalFarm()
    {
        $this->isOpen = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->reset(['coop_id', 'code', 'name', 'capacity', 'status']);
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function store()
    {
        if (!Auth::user()->can('create coop master data')) {
            $this->dispatch('error', 'You do not have permission to create coop.');
            return;
        }

        // Define rules dynamically based on whether it's an edit operation
        $rules = [
            'farm_id' => 'required',
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'status' => 'required|in:active,inactive'
        ];

        try {
            DB::beginTransaction();

            if ($this->isEdit) {
                // Get total current livestock in kandang
                $totalLivestock = Livestock::where('coop_id', $this->coop_id)
                    ->where('status', 'active')
                    ->sum('populasi_awal');

                $totalCurrentLivestock = CurrentLivestock::where('coop_id', $this->coop_id)
                    ->where('status', 'active')
                    ->sum('quantity');

                // $totalPopulation = $totalLivestock + $totalCurrentLivestock;

                if ($this->capacity < $totalLivestock) {
                    throw new \Exception('Kapasitas kandang tidak boleh lebih kecil dari jumlah ayam yang ada (' . $totalLivestock . ')');
                }
            }

            $data = [
                'farm_id' => $this->farm_id,
                'code' => $this->code,
                'name' => $this->name,
                'capacity' => $this->capacity,
                'status' => $this->status,
            ];

            if ($this->isEdit) {
                $coop = Coop::findOrFail($this->coop_id);
                $coop->update($data);
                $message = 'Kandang berhasil diperbarui';
            } else {
                $data['created_by'] = Auth::id();
                $coop = Coop::create($data);
                $message = 'Kandang berhasil ditambahkan';
            }

            DB::commit();

            $this->dispatch('success', $message);
            $this->closeModal();
            $this->dispatch('refreshDatatable');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving kandang: ' . $e->getMessage());
            $this->dispatch('error', 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage());
        }
    }

    public function closeModal()
    {
        $this->isOpen = false;
        $this->reset(['farm_id', 'code', 'name', 'capacity', 'status']);
        $this->resetValidation();
        $this->dispatch('hideModal');
    }

    public function editKandang($id)
    {
        $this->isEdit = true;
        $coop = Coop::findOrFail($id);
        $this->coop_id = $id;
        $this->farm_id = $coop->farm_id;
        $this->code = $coop->code;
        $this->name = $coop->name;
        $this->capacity = intval($coop->capacity);
        $this->status = $coop->status;

        $this->openModal();
    }

    public function deleteKandang($id)
    {
        try {
            // Check if kandang has any livestock purchase data
            $hasLivestockPurchase = Livestock::where('coop_id', $id)->exists();

            if ($hasLivestockPurchase) {
                $this->dispatch('error', 'Coop tidak dapat dihapus karena memiliki data pembelian ayam');
                return;
            }

            DB::beginTransaction();

            // Delete kandang
            $coop = Coop::findOrFail($id);
            $coop->delete();

            DB::commit();

            $this->dispatch('success', 'Coop berhasil dihapus');
            $this->dispatch('refreshDatatable');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting coop: ' . $e->getMessage());
            $this->dispatch('error', 'Terjadi kesalahan saat menghapus coop');
        }
    }
}

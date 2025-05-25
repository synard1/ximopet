<?php

namespace App\Livewire\MasterData;

use Livewire\Component;
use App\Models\Farm;
use App\Models\Kandang;
use App\Models\Livestock;
use App\Models\LivestockPurchase;
use App\Models\CurrentLivestock;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KandangForm extends Component
{
    public $farm_id;
    public $kandang_id;
    public $kode;
    public $code;
    public $name;
    public $nama;
    public $kapasitas;
    public $status = 'active';
    public $farms;
    public $isOpen = false;
    public $isEdit = false;

    protected $listeners = [
        'openModal' => 'openModal',
        'createKandang' => 'createKandang',
        'editKandang' => 'editKandang',
        'delete_kandang' => 'deleteKandang',
        'closeModalFarm' => 'closeModalFarm',
    ];

    protected $rules = [
        'farm_id' => 'required',
        'kode' => 'required|unique:master_kandangs,kode',
        'nama' => 'required|string|max:255',
        'kapasitas' => 'required|numeric|min:1',
        'status' => 'required|in:active,Nonaktif'
    ];

    public function mount()
    {
        if (!Auth::user()->can('create kandang management')) {
            $this->dispatch('error', 'You do not have permission to create kandang.');
            return;
        }

        $this->farms = Farm::where('status', 'active')->get();
    }

    public function render()
    {
        return view('livewire.master-data.kandang-form');
    }

    public function createKandang()
    {
        $this->isEdit = false;
        $this->openModal();
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
        $this->reset(['kandang_id', 'code', 'name', 'kapasitas', 'status']);
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function store()
    {
        if (!Auth::user()->can('create kandang management')) {
            $this->dispatch('error', 'You do not have permission to create kandang.');
            return;
        }

        // Define rules dynamically based on whether it's an edit operation
        $rules = [
            'farm_id' => 'required',
            'nama' => 'required|string|max:255',
            'kapasitas' => 'required|integer|min:1',
            'status' => 'required|in:active,Nonaktif'
        ];

        try {
            DB::beginTransaction();

            if ($this->isEdit) {
                // Get total current livestock in kandang
                $totalLivestock = Livestock::where('kandang_id', $this->kandang_id)
                    ->where('status', 'active')
                    ->sum('populasi_awal');

                $totalCurrentLivestock = CurrentLivestock::where('kandang_id', $this->kandang_id)
                    ->where('status', 'active')
                    ->sum('quantity');

                // $totalPopulation = $totalLivestock + $totalCurrentLivestock;

                if ($this->kapasitas < $totalLivestock) {
                    throw new \Exception('Kapasitas kandang tidak boleh lebih kecil dari jumlah ayam yang ada (' . $totalLivestock . ')');
                }
            }

            $data = [
                'farm_id' => $this->farm_id,
                'kode' => $this->kode,
                'nama' => $this->nama,
                'kapasitas' => $this->kapasitas,
                'status' => $this->status,
            ];

            if ($this->isEdit) {
                $kandang = Kandang::findOrFail($this->kandang_id);
                $kandang->update($data);
                $message = 'Kandang berhasil diperbarui';
            } else {
                $data['created_by'] = Auth::id();
                $kandang = Kandang::create($data);
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
        $this->reset(['farm_id', 'kode', 'nama', 'kapasitas', 'status']);
        $this->resetValidation();
        $this->dispatch('hideModal');
    }

    public function editKandang($id)
    {
        $this->isEdit = true;
        $kandang = Kandang::findOrFail($id);
        $this->kandang_id = $id;
        $this->farm_id = $kandang->farm_id;
        $this->kode = $kandang->kode;
        $this->nama = $kandang->nama;
        $this->kapasitas = intval($kandang->kapasitas);
        $this->status = $kandang->status;

        $this->openModal();
    }

    public function deleteKandang($id)
    {
        try {
            // Check if kandang has any livestock purchase data
            $hasLivestockPurchase = Livestock::where('kandang_id', $id)->exists();

            if ($hasLivestockPurchase) {
                $this->dispatch('error', 'Kandang tidak dapat dihapus karena memiliki data pembelian ayam');
                return;
            }

            DB::beginTransaction();

            // Delete kandang
            $kandang = Kandang::findOrFail($id);
            $kandang->delete();

            DB::commit();

            $this->dispatch('success', 'Kandang berhasil dihapus');
            $this->dispatch('refreshDatatable');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting kandang: ' . $e->getMessage());
            $this->dispatch('error', 'Terjadi kesalahan saat menghapus kandang');
        }
    }
}

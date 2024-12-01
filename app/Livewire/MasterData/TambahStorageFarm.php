<?php

namespace App\Livewire\MasterData;

use Livewire\Component;
use App\Models\Farm;
use App\Models\InventoryLocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TambahStorageFarm extends Component
{
    public $isOpen = 0;
    public $farms;
    public $selectedFarm;
    public $storageName;
    public $storageCode;
    public $storageType;

    protected $rules = [
        'selectedFarm' => 'required',
        'storageName' => 'required|string|max:255',
        'storageCode' => 'required|string|max:255|unique:inventory_locations,code',
        'storageType' => 'required|string',
    ];

    public function mount()
    {
        // Get all available farms
        $this->farms = Farm::where('status', 'Aktif')->get();
    }

    public function render()
    {
        return view('livewire.master-data.tambah-storage-farm');
    }

    public function storeStorageLocation()
    {
        $this->validate();

        try {
            DB::beginTransaction();

            $data = [
                'farm_id' => $this->selectedFarm,
                'name' => $this->storageName,
                'code' => $this->storageCode,
                'type' => $this->storageType,
                'status' => 'Aktif',
                'user_id' => auth()->user()->id,
            ];

            InventoryLocation::create($data);

            DB::commit();

            $this->dispatch('success', __('Storage location successfully added'));
        } catch (ValidationException $e) {
            DB::rollBack();
            $this->dispatch('validation-errors', ['errors' => $e->validator->errors()->all()]);
            $this->setErrorBag($e->validator->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('error', 'An error occurred while saving data. ' . $e->getMessage());
        }
    }
}

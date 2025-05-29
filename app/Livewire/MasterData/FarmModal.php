<?php

namespace App\Livewire\MasterData;

use Livewire\Component;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Models\Farm;
use App\Models\Kandang;

class FarmModal extends Component
{
    public $farms, $farm_id, $code, $name, $address, $phone_number, $contact_person, $status = 'active';
    public $isOpen = false;
    public $isEdit = false;

    protected $listeners = [
        'delete_farm' => 'deleteFarmList',
        'editFarm' => 'editFarm',
        'create' => 'create',
        'closeModalFarm' => 'closeModalFarm',
        'openModalForm' => 'openModalForm',
    ];

    protected function rules()
    {
        return [
            'code' => 'required|' . ($this->isEdit ? 'unique:farms,code,' . $this->farm_id : 'unique:farms,code'),
            'name' => 'required|string',
            'address' => 'nullable|string',
            'phone_number' => 'nullable|numeric',
            'contact_person' => 'nullable|string|max:255',
        ];
    }

    public function render()
    {
        return view('livewire.master-data.farm-modal');
    }

    public function create()
    {
        $this->isEdit = false;
        $this->resetForm();
        $this->openModal();
    }

    public function editFarm($id)
    {
        $this->isEdit = true;
        $farm = Farm::findOrFail($id);
        $this->farm_id = $id;
        $this->code = $farm->code;
        $this->name = $farm->name;
        $this->address = $farm->address;
        $this->phone_number = $farm->phone_number;
        $this->contact_person = $farm->contact_person;
        $this->status = $farm->status;

        $this->openModal();
    }

    public function deleteFarmList($id)
    {
        try {
            // Check if farm has any kandang data
            $hasKandang = Kandang::where('farm_id', $id)->exists();

            // dd($hasKandang);

            if ($hasKandang) {
                $this->dispatch('error', 'Farm tidak dapat dihapus karena memiliki data kandang');
                return;
            }

            DB::beginTransaction();

            // Delete farm
            $farm = Farm::findOrFail($id);
            $farm->delete();

            DB::commit();

            $this->dispatch('success', 'Farm berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting farm: ' . $e->getMessage());
            $this->dispatch('error', 'Terjadi kesalahan saat menghapus farm');
        }
    }

    public function storeFarm()
    {
        try {
            $this->validate();

            DB::beginTransaction();

            $data = [
                'code' => $this->code,
                'name' => $this->name,
                'address' => $this->address,
                'phone_number' => $this->phone_number,
                'contact_person' => $this->contact_person,
                'status' => $this->status,
            ];

            if ($this->isEdit) {
                $farm = Farm::findOrFail($this->farm_id);
                $farm->update($data);
                $message = 'Farm ' . $farm->name . ' berhasil diperbarui';
            } else {
                $farm = Farm::create($data);
                $message = 'Farm ' . $farm->name . ' berhasil ditambahkan';
            }

            DB::commit();

            $this->dispatch('success', $message);
            $this->closeModalFarm();
            $this->dispatch('refreshDatatable');
        } catch (ValidationException $e) {
            $this->dispatch('validation-errors', ['errors' => $e->validator->errors()->all()]);
            $this->setErrorBag($e->validator->errors());
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("[" . __CLASS__ . "::" . __FUNCTION__ . "] Error: " . $e->getMessage() .
                " | Line: " . $e->getLine() .
                " | File: " . $e->getFile());

            $this->dispatch('error', 'Terjadi kesalahan saat menyimpan data farm');
        }
    }

    private function resetForm()
    {
        $this->reset(['farm_id', 'code', 'name', 'address', 'phone_number', 'contact_person', 'status']);
        $this->resetErrorBag();
        $this->resetValidation();
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
}

<?php

namespace App\Livewire\MasterData;

use Livewire\Component;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Models\Farm;

class FarmModal extends Component
{
    public $farms,$farm_id, $kode_farm, $nama, $alamat, $telp, $pic, $telp_pic, $email, $status = 'Aktif';
    public $isOpen = 0;


    protected $rules = [
        'kode_farm' => 'required|unique:master_farms,kode',
        'nama' => 'required|string',
        'alamat' => 'string',
        'telp' => 'numeric',
        'pic' => 'string|max:255',
        'telp_pic' => 'numeric',
        'email' => 'required|email|unique:master_farms,email', // Add table and column for email uniqueness
    ];

    public function render()
    {
        return view('livewire.master-data.farm-modal');
    }

    public function storeFarm()
    {
        try {
            // Validate the form input data
            $this->validate(); 
        
            // Wrap database operation in a transaction (if applicable)
            DB::beginTransaction();
        
            // Prepare the data for creating/updating the farm
            $data = [
                'kode' => $this->kode_farm,
                'jenis' => "Farm",
                'nama' => $this->nama,
                'alamat' => $this->alamat,
                'telp' => $this->telp,
                'pic' => $this->pic,
                'telp_pic' => $this->telp_pic,
                'email' => $this->email,
                'status' => 'Aktif',
            ];
        
            $farm = Farm::where('id', $this->farm_id)->first() ?? Farm::create($data);
        
            DB::commit();
    
            // Emit success event if no errors occurred
            $this->dispatch('success', 'Farm '. $farm->nama .' berhasil ditambahkan');
        } catch (ValidationException $e) {
            $this->dispatch('validation-errors', ['errors' => $e->validator->errors()->all()]);
            $this->setErrorBag($e->validator->errors());
        } catch (\Exception $e) {
            DB::rollBack();
    
            // Handle validation and general errors
            $this->dispatch('error', 'Terjadi kesalahan saat menyimpan data. ');
            // Optionally log the error: Log::error($e->getMessage());
        } finally {
            // Reset the form in all cases to prepare for new data
            $this->reset();
        }
    }

    public function openModal()
    {
        $this->isOpen = true;
    }

    public function closeModalFarm()
    {
        $this->isOpen = false;
    }

    private function resetInputFields(){
        $this->nama = ''; // Generate UUID for new contacts
    }
}

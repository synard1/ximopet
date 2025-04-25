<?php

namespace App\Livewire\MasterData;

use Livewire\Component;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Models\Rekanan;

class EkspedisiModal extends Component
{
    public $ekspedisis,$ekspedisi_id, $kode_ekspedisi, $name, $alamat, $telp, $pic, $telp_pic, $email, $status = 'Aktif';
    public $isOpen = 0;


    protected $rules = [
        'kode_ekspedisi' => 'required|unique:master_rekanan,kode',
        'name' => 'required|string',
        'alamat' => 'string',
        'telp' => 'numeric',
        'pic' => 'string|max:255',
        'telp_pic' => 'numeric',
        'email' => 'required|email|unique:master_rekanan,email', // Add table and column for email uniqueness
    ];

    public function render()
    {
        return view('livewire.master-data.ekspedisi-modal');
    }

    public function storeEkspedisi()
    {
        try {
            // Validate the form input data
            $this->validate(); 
        
            // Wrap database operation in a transaction (if applicable)
            DB::beginTransaction();
        
            // Prepare the data for creating/updating the ekspedisi
            $data = [
                'kode' => $this->kode_ekspedisi,
                'jenis' => "Ekspedisi",
                'nama' => $this->name,
                'alamat' => $this->alamat,
                'telp' => $this->telp,
                'pic' => $this->pic,
                'telp_pic' => $this->telp_pic,
                'email' => $this->email,
                'status' => 'Aktif',
            ];
        
            $rekanan = Rekanan::where('id', $this->ekspedisi_id)->first() ?? Rekanan::create($data);
        
            DB::commit();
    
            // Emit success event if no errors occurred
            $this->dispatch('success', 'Ekspedisi '. $rekanan->nama .' berhasil ditambahkan');
            // Reset the form
            $this->resetForm();
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
    
    private function resetForm()
    {
        $this->reset([
            'kode_ekspedisi',
            'name',
            'alamat',
            'telp',
            'pic',
            'telp_pic',
            'email',
            'status',
        ]);
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function openModal()
    {
        $this->isOpen = true;
    }

    public function closeModalEkspedisi()
    {
        $this->isOpen = false;
    }

    private function resetInputFields(){
        $this->nama = ''; // Generate UUID for new contacts
    }
}

<?php

namespace App\Livewire\MasterData;

use Livewire\Component;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Models\Rekanan;

class SupplierModal extends Component
{
    public $suppliers,$supplier_id, $kode_supplier, $name, $alamat, $telp, $pic, $telp_pic, $email, $status = 'Aktif';
    public $isOpen = 0;


    protected $rules = [
        'kode_supplier' => 'required|unique:master_rekanan,kode',
        'name' => 'required|string',
        'alamat' => 'string',
        'telp' => 'numeric',
        'pic' => 'string|max:255',
        'telp_pic' => 'numeric',
        'email' => 'required|email|unique:master_rekanan,email', // Add table and column for email uniqueness
    ];

    public function render()
    {
        return view('livewire.master-data.supplier-modal');
    }

    public function storeSupplier()
    {
        // $this->validate();

        // // session()->flash('message', $this->nama ? 'Contact updated successfully.' : 'Contact created successfully.');
        
        // // Emit a success event with a message
        // $this->dispatch('success', __('Data Supplier Berhasil Dibuat'));

        // $this->closeModalSupplier();
        // $this->resetInputFields();
    }

    public function openModal()
    {
        $this->isOpen = true;
    }

    public function closeModalSupplier()
    {
        $this->isOpen = false;
    }

    private function resetInputFields(){
        $this->nama = ''; // Generate UUID for new contacts
    }
}

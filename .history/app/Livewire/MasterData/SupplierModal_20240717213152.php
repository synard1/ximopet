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
    // protected $layout = 'layouts.style60.master'; 
    public $contacts, $kode, $nama, $alamat, $email;
    public $isOpen = 0;


    protected $rules = [
        'nama' => 'required',
    ];

    public function render()
    {
        return view('livewire.master-data.supplier-modal');
    }

    public function layout()
    {
        return 'layouts.style60.master'; // Assuming your layout is at resources/views/layouts/app.blade.php
    }

    public function storeSupplier()
    {
        $this->validate();

        // session()->flash('message', $this->nama ? 'Contact updated successfully.' : 'Contact created successfully.');
        
        // Emit a success event with a message
        $this->dispatch('success', __('Data Supplier Berhasil Dibuat'));

        $this->closeModalSupplier();
        $this->resetInputFields();
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

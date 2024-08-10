<?php

namespace App\Livewire\MasterData;

use Livewire\Component;

class SupplierModal extends Component
{
    // protected $layout = 'layouts.style60.master'; 

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

    public function store()
    {
        $this->validate();

        session()->flash('message', $this->nama ? 'Contact updated successfully.' : 'Contact created successfully.');
        $this->closeModal();
        $this->resetInputFields();
    }

    private function resetInputFields(){
        $this->kode = ''; // Generate UUID for new contacts
        $this->nama = '';
        $this->alamat = '';
        $this->email = '';
        $this->contact_id = '';
    }
}

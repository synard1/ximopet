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
        Contact::updateOrCreate(['id' => $this->contact_id], [
            'kode' => $this->kode,
            'nama' => $this->nama,
            'alamat' => $this->alamat,
            'email' => $this->email,
        ]);

        session()->flash('message', 
        $this->contact_id ? 'Contact updated successfully.' : 'Contact created successfully.');
        $this->closeModal();
        $this->resetInputFields();
    }
}

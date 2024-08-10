<?php

namespace App\Livewire\Transaksi;

use Livewire\Component;
use App\Models\Rekanan;

class PembelianList extends Component
{
    public $faktur, $tanggal;
    public $isOpen = 0;

    protected $rules = [
        'faktur' => 'required',
        'tanggal' => 'required',
    ];

    public function render()
    {

        $this->supplier = Rekanan::where('jenis', 'Supplier')->get();
        return view('livewire.transaksi.pembelian-list', ['farms' => $this->farms,
        'noFarmMessage' => $this->noFarmMessage, // Pass the message to the view
        ]);
    }

    public function createPembelian()
    {
        $this->openModal();
    }

    public function openModal()
    {
        $this->isOpen = true;
    }

    public function close()
    {
        $this->isOpen = false;
        $this->dispatch('closeForm');

    }

    public function store()
    {
        $this->validate(); 
        // Emit success event if no errors occurred
        $this->dispatch('success', 'Data Pembelian Stok berhasil ditambahkan');
    }
}

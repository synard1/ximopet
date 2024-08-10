<?php

namespace App\Livewire\Transaksi;

use Livewire\Component;

class PembelianList extends Component
{
    public $faktur, $tanggal
    public $isOpen = 0;

    protected $rules = [
        'faktur' => 'required',
        'tanggal' => 'required',
    ];

    public function render()
    {
        return view('livewire.transaksi.pembelian-list');
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

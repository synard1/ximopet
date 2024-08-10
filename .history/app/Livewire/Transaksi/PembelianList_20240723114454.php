<?php

namespace App\Livewire\Transaksi;

use Livewire\Component;

class PembelianList extends Component
{
    public $isOpen = 0;

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
    }

    public function store()
    {
        // Emit success event if no errors occurred
        $this->dispatch('success', 'Data Pembelian Stok berhasil ditambahkan');
    }
}

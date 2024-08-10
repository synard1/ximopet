<?php

namespace App\Livewire\Transaksi;

use Livewire\Component;
use App\Models\Rekanan;

class PembelianList extends Component
{
    public $faktur, $tanggal, $suppliers, $supplier;
    public $isOpen = 0;

    protected $rules = [
        'faktur' => 'required',
        'tanggal' => 'required',
    ];

    public function render()
    {

        $this->suppliers = Rekanan::where('jenis', 'Supplier')->get();
        return view('livewire.transaksi.pembelian-list', ['suppliers' => $this->suppliers,
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
        // $this->validate(); 

        // Prepare the data for creating/updating
        $data = [
            'jenis' => 'Pembelian',
            'faktur' => $this->faktur,
            'tanggal' => $this->tanggal,
            'supplier' => $this->supplier,
        ];

        dd($data)

        // Emit success event if no errors occurred
        $this->dispatch('success', 'Data Pembelian Stok berhasil ditambahkan');
    }
}

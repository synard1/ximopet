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
            'jenis_barang' => 'DOC',
            'faktur' => $this->faktur,
            'tanggal' => $this->tanggal,
            'rekanan_id' => $this->supplierSelect,
            'farm_id' => $kandang->farm_id,
            'kandang_id' => $this->selectedKandang,
            'rekanan_nama' => $supplier->nama ?? '',
            'harga' => $this->harga,
            'jumlah' => $this->qty,
            'sub_total' => $this->qty * $this->harga,
            'periode' => $this->periode,
            'user_id' => auth()->user()->id,
            'payload'=> [
                'doc' => $doc,
            ],
            'status' => 'Aktif',
        ];
        // Emit success event if no errors occurred
        $this->dispatch('success', 'Data Pembelian Stok berhasil ditambahkan');
    }
}

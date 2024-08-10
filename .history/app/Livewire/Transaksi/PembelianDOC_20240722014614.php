<?php

namespace App\Livewire\Transaksi;

use Livewire\Component;
use App\Models\Stok;
use App\Models\Rekanan;

class PembelianDOC extends Component
{
    public $check_doc, $kode_doc, $suppliers;

    public function render()
    {
        $this->docs = Stok::where('jenis','DOC')->get();
        $this->suppliers = Rekanan::where('jenis','Supplier')->get();
        return view('livewire.transaksi.pembelian-d-o-c',[
            'check_doc' => $this->check_doc,
            'suppliers' => $this->suppliers,
        ]);
    }
}

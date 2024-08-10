<?php

namespace App\Livewire\Transaksi;

use Livewire\Component;
use App\Models\Stok;

class PembelianDOC extends Component
{
    public $check_doc, $kode_doc;

    public function render()
    {
        $this->check_doc = Stok::where('jenis','DOC')->get();
        return view('livewire.transaksi.pembelian-d-o-c',['check_doc' => $this->check_doc]);
    }
}

<?php

namespace App\Livewire\Transaksi;

use Livewire\Component;
use App\Models\Stok;

class PembelianDOC extends Component
{
    public $check_doc;

    public function render()
    {
        $check_doc = Stok::where('jenis')
        return view('livewire.pembelian-d-o-c');
    }
}

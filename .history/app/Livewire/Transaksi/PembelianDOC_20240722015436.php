<?php

namespace App\Livewire\Transaksi;

use Livewire\Component;
use App\Models\Stok;
use App\Models\Rekanan;
use App\Models\Kandang;

class PembelianDOC extends Component
{
    public $docs, $kode_doc, $suppliers, $kandangs, $periode;

    protected $rules = [
        'faktur' => 'required|unique:transaksis,faktur',
        'tanggal_pembelian' => 'required',
        'supplierSelect' => 'required|string',
        'satuan_kecil' => 'required|string',
        'satuan_besar' => 'required|string',
        'konversi' => 'required|integer',
    ];

    public function render()
    {
        $this->docs = Stok::where('jenis','DOC')->get();
        $this->suppliers = Rekanan::where('jenis','Supplier')->get();
        $this->kandangs = Kandang::where('status','Aktif')->get();
        return view('livewire.transaksi.pembelian-d-o-c',[
            'docs' => $this->docs,
            'suppliers' => $this->suppliers,
            'kandangs' => $this->kandangs,
        ]);
    }
}

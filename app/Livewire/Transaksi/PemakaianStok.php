<?php

namespace App\Livewire\Transaksi;

use Livewire\Component;
use App\Models\Rekanan;
use App\Models\Stok;
use App\Models\FarmOperator;

class PemakaianStok extends Component
{
    public $isOpenPemakaian = 0;
    public $faktur, $tanggal, $suppliers, $supplier, $name =[], $quantity=[], $allItems, $farms, $selectedFarm, $selectedSupplier;
    public $items = [['name' => '', 'qty' => 1, 'harga' => 0]]; // Initial empty item


    protected $listeners = [
        // 'createPemakaianStok' => 'createPemakaianStok',
    ];

    public function render()
    {
        $this->suppliers = Rekanan::where('jenis','Supplier')->get();
        $this->allItems = Stok::where('status', 'Aktif')->where('jenis','!=','DOC')->get();
        $this->farms = FarmOperator::where('user_id', auth()->user()->id)->where('status','Aktif')->get();


        return view('livewire.transaksi.pemakaian-stok', [
            'suppliers'=> $this->suppliers,
            'allItems' => $this->allItems,
            'farms' => $this->farms,
        ]);
    }

    public function createPemakaianStok()
    {
        $this->isOpenPemakaian = true;
    }

    public function close()
    {
        $this->isOpenPemakaian = false;
        $this->dispatch('closeFormPemakaian');

    }

    public function store()
    {
        $this->dispatch('success', 'Data Pemakaian Stok berhasil ditambahkan');
        $this->dispatch('closeFormPemakaian');

    }

    public function addItem()
    {
        $this->items[] = ['name' => '', 'qty' => 1, 'harga' => 0];
        // $this->dispatch('reinitialize-select2-pemakaianStok'); // Trigger Select2 initialization
    }
}

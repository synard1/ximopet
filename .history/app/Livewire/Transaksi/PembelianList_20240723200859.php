<?php

namespace App\Livewire\Transaksi;

use Livewire\Component;
use App\Models\Rekanan;
use App\Models\Stok;

class PembelianList extends Component
{
    public $faktur, $tanggal, $suppliers, $supplier, $name =[], $quantity=[];
    public $selectedSupplier = null;
    public $items = [];
    public $isOpen = 0;

    protected $rules = [
        'faktur' => 'required',
        'tanggal' => 'required',
    ];

    public function mount()
    {
        // Fetch items from the database and add the initial empty item
        // $this->items = Item::all()->toArray();
        $this->addItem();
    }

    public function render()
    {

        $this->suppliers = Rekanan::where('jenis', 'Supplier')->get();
        $this->allItems = Stok::where('jenis', '!=','Supplier')->get();
        return view('livewire.transaksi.pembelian-list', ['suppliers' => $this->suppliers,
        ]);
    }

    public function addItem()
    {
        $this->items[] = ['id' => null, 'name' => '', 'description' => '', 'qty' => 1, 'price' => 0];
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
            'supplier' => $this->selectedSupplier,
            'quantity' => $this->selectedSupplier,
            'supplier' => $this->selectedSupplier,
        ];

        dd($data);

        // Emit success event if no errors occurred
        $this->dispatch('success', 'Data Pembelian Stok berhasil ditambahkan');
    }
}

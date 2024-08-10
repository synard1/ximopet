<?php

namespace App\Livewire\Transaksi;

use Livewire\Component;
use App\Models\Rekanan;
use App\Models\Stok;

class PembelianList extends Component
{
    public $faktur, $tanggal, $suppliers, $supplier, $name =[], $quantity=[], $allItems;
    public $selectedSupplier = null;
    public $items = [['name' => '', 'qty' => 1, 'price' => 0]]; // Initial empty item

    public $isOpen = 0;

    protected $rules = [
        'faktur' => 'required',
        'tanggal' => 'required',
    ];

    public function mount()
    {
        // Fetch items from the database and add the initial empty item
        // $this->items = Item::all()->toArray();
        // $this->addItem();
        $this->faktur = "000000";
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items); // Reindex the array
    }

    public function render()
    {

        $this->suppliers = Rekanan::where('jenis', 'Supplier')->get();
        $this->allItems = Stok::where('status', 'Aktif')->get();
        // $this->suppliers = Rekanan::where('jenis', 'Supplier')->get();
        return view('livewire.transaksi.pembelian-list', [
            'suppliers' => $this->suppliers,
            'allItems' => $this->allItems,
        ]);
    }

    public function addItem()
    {
        $this->items[] = ['name' => '', 'qty' => 1, 'price' => 0];
        $this->dispatch('reinitialize-select2'); // Trigger Select2 initialization
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

        foreach ($this->items as $itemData) {
            // $item = Stok::where('nama', $itemData['name'])->first();
            $itemsToStore[] = [
                // 'item_id' => $item->id ?? '',
                'qty' => $itemData['qty'],
                'harga' => $itemData['price'],
                // 'total' => $itemData['qty'] * $itemData['price'],
                'name' => $itemData['name'],
                // 'description' => $item->description, // Assuming you have a description in your Item model
            ];
        }
    
        dd($itemsToStore); 

        // Prepare the data for creating/updating
        $data = [
            'jenis' => 'Pembelian',
            'faktur' => $this->faktur,
            'tanggal' => $this->tanggal,
            'supplier' => $this->selectedSupplier,
            'quantity' => $this->selectedSupplier,
            'supplier' => $this->selectedSupplier,
            'name' => $this->name,
        ];

        dd($data);

        // Emit success event if no errors occurred
        $this->dispatch('success', 'Data Pembelian Stok berhasil ditambahkan');
    }
}

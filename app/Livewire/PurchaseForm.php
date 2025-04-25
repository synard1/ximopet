<?php

namespace App\Livewire;

use App\Models\Item;
use Livewire\Component;

class PurchaseForm extends Component
{
    public $items = [];
    public $selectedItemId;
    public $qty;
    public $harga;

    public function mount()
    {
        $this->addItem();
    }

    public function addItem()
    {
        $this->items[] = ['item_id' => '', 'qty' => 1, 'harga' => 0];
        $this->dispatch('select2-initial');

    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function updatedSelectedItemId($value)
    {
        $item = Item::find($value);
        if ($item) {
            $this->harga = $item->harga;
        }
    }

    public function render()
    {
        $allItems = Item::whereHas('itemCategory', function ($query) {
            $query->where('name', '!=', 'DOC');
        })->where('status', 'Aktif')->get();
        return view('livewire.purchase-form', compact('allItems'));
    }
}
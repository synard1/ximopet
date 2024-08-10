<?php

namespace App\Livewire\MasterData;

use Livewire\Component;

class TambahOperatorFarm extends Component
{
    public $isOpen = 0;
    
    public function render()
    {
        return view('livewire.master-data.tambah-operator-farm');
    }
}

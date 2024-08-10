<?php

namespace App\Livewire;

use Livewire\Component;

class Menu extends Component
{
    public $isOpen = 0;
    public $rand = 0;
    public $noFarmMessage = ''; // Add a property to store the message


    protected $listeners = ['openModal'];

    public function render()
    {
        return view('livewire.menu');
    }

    public function mount()
    {
        $this->rand = rand(1, 10000);
        $this->noFarmMessage = 'Belum Ada Data Farm';

    }

    public function openModal()
    {
        $this->isOpen = true;
        // Emit a success event with a message
        // $this->dispatch('success', 'Modal Open');
    }
}

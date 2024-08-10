<?php

namespace App\Livewire;

use Livewire\Component;

class Menu extends Component
{
    public $isOpen = 0;

    public function render()
    {
        return view('livewire.menu');
    }

    public function openModal()
    {
        $this->isOpen = true;

        // Emit a success event with a message
        $this->dispatch('success', 'Modal Open');
    }
}

<?php

namespace App\Livewire;

use Livewire\Component;

class Menu extends Component
{
    public function render()
    {
        return view('livewire.menu');
    }

    public function openModal()
    {
        // Emit a success event with a message
        $this->dispatch('success', 'Modal Open');
    }
}

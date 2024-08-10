<?php

namespace App\Livewire\MasterData;

use Livewire\Component;

class SupplierModal extends Component
{
    public function render()
    {
        return view('livewire.master-data.supplier-modal');
    }

    public function layout()
{
    return 'layouts.app'; // Assuming your layout is at resources/views/layouts/app.blade.php
}
}

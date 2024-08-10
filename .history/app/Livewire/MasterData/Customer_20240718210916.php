<?php

namespace App\Livewire\MasterData;

use Livewire\Component;

class Customer extends Component
{
    public $customers, $kode, $nama, $alamat, $telp, $pic, $telp_pic, $email, $status = 'Aktif';

    public $supplier_id = null; // Initialize with null instead of empty string

    public function render()
    {
        return view('livewire.customer');
    }
}

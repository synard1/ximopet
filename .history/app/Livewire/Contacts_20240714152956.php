<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Str;

class Contacts extends Component
{
    public $contacts, $kode, $nama, $alamat, $email, $contact_id;
    public $isOpen = 0;

    public function render()
    {
        return view('livewire.contacts');
    }
}

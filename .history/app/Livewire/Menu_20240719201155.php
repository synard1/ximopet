<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Farm;

class Menu extends Component
{
    public $isOpen = 0;
    public $rand = 0;
    public $farms, $noFarmMessage = ''; // Add a property to store the message
    public $kandangs, $selectedFarm, $kandang_id, $kode_kandang, $nama, $alamat, $telp, $pic, $telp_pic, $status = 'Aktif';



    protected $listeners = ['openModal'];

    public function render()
    {
        return view('livewire.menu');
    }

    public function mount()
    {
        $this->rand = now;
        $this->farms = Farm::where('status', 'Aktif')->get();
        // $this->rand = rand(1, 10000);

        // Check if there are any active farms
        if ($this->farms->isEmpty()) {
            $this->noFarmMessage = 'Belum Ada Data Farm';
        }
    }

    public function openModal()
    {
        $this->isOpen = true;
        // Emit a success event with a message
        // $this->dispatch('success', 'Modal Open');
    }
}

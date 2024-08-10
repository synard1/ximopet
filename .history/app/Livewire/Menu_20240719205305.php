<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Farm;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Request; // Import the Request facade

class Menu extends Component
{
    public $isOpen = 0;
    public $rand = 0;
    public $farms, $noFarmMessage = ''; // Add a property to store the message
    public $kandangs, $selectedFarm, $kandang_id, $kode_kandang, $nama, $alamat, $telp, $pic, $telp_pic, $status = 'Aktif';
    public $dynamicNumber, $currentUrl, $referer;



    protected $listeners = ['openModal'];

    public function render()
    {
        return view('livewire.menu');
    }

    public function mount()
    {
        $this->rand = now();
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
        $this->dynamicNumber = rand(100, 999); // Generate random number
        // $this->currentUrl = URL::current();
        // $this->currentUrl = Request::header('Referer');
        $refererUrl = Request::header('Referer');
        // $this->currentUrl = $refererUrl ? URL::parse($refererUrl)->getPath() : null;
        if ($refererUrl) {
            $parsedUrl = parse_url($refererUrl);
            $this->currentUrl = isset($parsedUrl['path']) ? $parsedUrl['path'] : null;
        } else {
            $this->currentUrl = null;
        }

        $this->dispatch('show-modal');


        // Emit a success event with a message
        // $this->dispatch('success', 'Modal Open');
    }

    public function closeModal()
    {
        $this->isOpen = false;
    }
}

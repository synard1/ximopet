<?php

namespace App\Livewire\MasterData;

use Livewire\Component;
use Illuminate\Support\Str;
use App\Models\Farm;
use App\Models\Stok;
use Ramsey\Uuid\Uuid; // Import the UUID library


class StokList extends Component
{
    public $stoks, $farms, $selectedFarm, $kode, $nama, $kapasitas, $status;

    public $stok_id = null; // Initialize with null instead of empty string
    public $isOpen = 0;
    public $noFarmMessage = ''; // Add a property to store the message

    // protected $listeners = ['edit','create']; // Add the listener

    protected $listeners = [
        'delete_stok' => 'deleteStokList',
        'editStok' => 'editStok',
        'create' => 'create',
    ];

    public function mount()
    {
        $this->farms = Farm::where('status', 'Aktif')->get();
        // Check if there are any active farms
        if ($this->farms->isEmpty()) {
            $this->noFarmMessage = 'Belum Ada Data Farm';
        }
    }

    public function render()
    {
        $this->stoks = Stok::all();
        // $this->farms = Farm::where('status', 'Aktif')->get();
        return view('livewire.master-data.stok-list', ['stoks' => $this->stoks,            
        'farms' => $this->farms, // Pass $farms to the view
    ]);
    }

    public function create()
    {
        $this->resetInputFields();
        // $this->kode = (string) Uuid::uuid4(); // Generate and cast to string
        $this->openModal();
    }

    public function store()
    {
        $this->validate();
        Stok::updateOrCreate(['id' => $this->stok_id], [
            'kode' => $this->kode,
            'nama' => $this->nama,
            'kapasitas' => $this->kapasitas,
            'status' => $this->status,
        ]);

        if($this->stok_id){
            $this->dispatch('success', __('Data Stok Berhasil Diubah'));

        }else{
            $this->dispatch('success', __('Data Stok Berhasil Dibuat'));
        }

        // session()->flash('message', 
        // $this->stok_id ? 'Stok updated successfully.' : 'Stok created successfully.');
        $this->closeModal();
        $this->resetInputFields();
    }

    public function editStok($id)
    {
        $stok = Stok::where('id',$id)->first();
        $this->stok_id = $id;
        $this->kode = $stok->kode;
        $this->nama = $stok->nama;
        $this->kapasitas = $stok->kapasitas;
        $this->status = $stok->status;



        $this->openModal();
    }

    public function deleteStokList($id)
    {

        // Delete the user record with the specified ID
        Stok::destroy($id);

        // Emit a success event with a message
        $this->dispatch('success', 'Data berhasil dihapus');
    }

    public function openModal()
    {
        $this->isOpen = true;
        // $this->farms = Farm::where('status', 'Aktif')->get();

    }

    public function closeModal()
    {
        $this->isOpen = false;
    }

    private function resetInputFields(){
        // $this->kode = Str::uuid(); // Generate UUID for new stoks
        $this->kode = ''; // Generate UUID for new stoks
        $this->nama = '';
        $this->stok_id = '';
    }

    protected $rules = [
        'kode' => 'required', // Unique except on update
        'nama' => 'required',
    ];

    public function updatingKode()
    {
        if ($this->stok_id) {
            $this->resetErrorBag('kode'); // Clear error if it's an edit
        }
    }
}

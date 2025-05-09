<?php

namespace App\Livewire\MasterData;

use Livewire\Component;
use Illuminate\Support\Str;
use App\Models\Farm;
use App\Models\Item;
use Ramsey\Uuid\Uuid; // Import the UUID library


class StokList extends Component
{
    public $stoks, $farms, $selectedFarm, $kode, $nama, $satuan_besar, $satuan_kecil, $konversi, $status;
    public $jenis = ''; // Define the property for the selected value
    public $availableJenis = ['DOC', 'Pakan', 'Obat', 'Vaksin', 'Lainnya']; // List of available options

    public $stok_id = null; // Initialize with null instead of empty string
    public $isOpen = 0;

    // protected $listeners = ['edit','create']; // Add the listener

    protected $listeners = [
        'delete_stok' => 'deleteStokList',
        'editStok' => 'editStok',
        'create' => 'create',
    ];

    public function mount()
    {
    }

    public function render()
    {
        $this->stoks = Item::all();
        // $this->farms = Farm::where('status', 'Aktif')->get();
        return view('livewire.master-data.stok-list', ['stoks' => $this->stoks,            
        'farms' => $this->farms, // Pass $farms to the view
        'availableJenis' => $this->availableJenis
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
        Item::updateOrCreate(['id' => $this->stok_id], [
            'jenis' => $this->jenis,
            'kode' => $this->kode,
            'nama' => $this->nama,
            'satuan_besar' => $this->satuan_besar,
            'satuan_kecil' => $this->satuan_kecil,
            'konversi' => $this->konversi,
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
        $stok = Item::where('id',$id)->first();
        $this->stok_id = $id;
        $this->jenis = $stok->jenis;
        $this->kode = $stok->kode;
        $this->nama = $stok->nama;
        $this->satuan_besar = $stok->satuan_besar;
        $this->satuan_kecil = $stok->satuan_kecil;
        $this->konversi = $stok->konversi;
        $this->status = $stok->status;

        $this->openModal();
    }

    public function deleteStokList($id)
    {

        // Delete the user record with the specified ID
        Item::destroy($id);

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

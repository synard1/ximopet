<?php

namespace App\Livewire\MasterData;

use Livewire\Component;
use Illuminate\Support\Str;
use App\Models\Farm;
use App\Models\Kandang;
use Ramsey\Uuid\Uuid; // Import the UUID library


class KandangList extends Component
{
    public $kandangs, $farms, $selectedFarm, $kode, $nama, $k$status = 'Aktif';

    public $kandang_id = null; // Initialize with null instead of empty string
    public $isOpen = 0;
    public $noFarmMessage = ''; // Add a property to store the message

    // protected $listeners = ['edit','create']; // Add the listener

    protected $listeners = [
        'delete_kandang' => 'deleteKandangList',
        'editKandang' => 'editKandang',
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
        $this->kandangs = Kandang::all();
        // $this->farms = Farm::where('status', 'Aktif')->get();
        return view('livewire.master-data.kandang-list', ['kandangs' => $this->kandangs,            
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
        Kandang::updateOrCreate(['id' => $this->kandang_id], [
            'jenis' => "KandangList",
            'kode' => $this->kode,
            'nama' => $this->nama,
            'status' => 'Aktif',
        ]);

        if($this->kandang_id){
            $this->dispatch('success', __('Data KandangList Berhasil Diubah'));

        }else{
            $this->dispatch('success', __('Data KandangList Berhasil Dibuat'));
        }

        // session()->flash('message', 
        // $this->kandang_id ? 'Kandang updated successfully.' : 'Kandang created successfully.');
        $this->closeModal();
        $this->resetInputFields();
    }

    public function editKandang($id)
    {
        $kandang = Kandang::where('id',$id)->first();
        $this->kandang_id = $id;
        $this->kode = $kandang->kode;
        $this->nama = $kandang->nama;
        $this->kapasitas = $kandang->kapasitas;



        $this->openModal();
    }

    public function deleteKandangList($id)
    {
        // Delete the user record with the specified ID
        Kandang::destroy($id);

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
        // $this->kode = Str::uuid(); // Generate UUID for new kandangs
        $this->kode = ''; // Generate UUID for new kandangs
        $this->nama = '';
        $this->kandang_id = '';
    }

    protected $rules = [
        'kode' => 'required', // Unique except on update
        'nama' => 'required',
    ];

    public function updatingKode()
    {
        if ($this->kandang_id) {
            $this->resetErrorBag('kode'); // Clear error if it's an edit
        }
    }
}

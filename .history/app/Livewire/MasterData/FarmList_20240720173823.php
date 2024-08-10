<?php

namespace App\Livewire\MasterData;

use Livewire\Component;
use Illuminate\Support\Str;
use App\Models\Farm;
use App\Models\Kandang;
use Ramsey\Uuid\Uuid; // Import the UUID library


class FarmList extends Component
{
    public $farms, $kode, $nama, $alamat, $telp, $pic, $telp_pic, $status = 'Aktif';

    public $farm_id = null; // Initialize with null instead of empty string
    public $isOpen = 0;

    // protected $listeners = ['edit','create']; // Add the listener

    protected $listeners = [
        'delete_farm' => 'deleteFarmList',
        'editFarm' => 'editFarm',
        'create' => 'create',
    ];


    public function render()
    {
        $this->farms = Farm::all();
        return view('livewire.master-data.farm-list', ['farms' => $this->farms]);
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
        Farm::updateOrCreate(['id' => $this->farm_id], [
            'jenis' => "FarmList",
            'kode' => $this->kode,
            'nama' => $this->nama,
            'alamat' => $this->alamat,
            'telp' => $this->telp,
            'pic' => $this->pic,
            'telp_pic' => $this->telp_pic,
            'status' => 'Aktif',
        ]);

        if($this->farm_id){
            $this->dispatch('success', __('Data FarmList Berhasil Diubah'));

        }else{
            $this->dispatch('success', __('Data FarmList Berhasil Dibuat'));
        }

        // session()->flash('message', 
        // $this->farm_id ? 'Farm updated successfully.' : 'Farm created successfully.');
        $this->closeModal();
        $this->resetInputFields();
    }

    public function editFarm($id)
    {
        $farm = Farm::where('id',$id)->first();
        $this->farm_id = $id;
        $this->kode = $farm->kode;
        $this->nama = $farm->nama;
        $this->alamat = $farm->alamat;
        $this->telp = $farm->telp;
        $this->pic = $farm->pic;
        $this->telp_pic = $farm->telp_pic;

        // session()->flash('message', 'test edit');


        $this->openModal();
    }

    // public function delete($id)
    // {
    //     // Farm::find($id)->delete();
    //     Farm::where('id',$id)->delete();
    //     session()->flash('message', 'Farm deleted successfully.');
    // }

    public function deleteFarmList($id)
    {
        $kandang = Kandang::where('farm_id',$id)->first();

        // Prevent deletion of current user
        if ($kandang) {
            $this->dispatch('error', 'User cannot be deleted');
            return;
        }

        // Delete the user record with the specified ID
        Farm::destroy($id);

        // Emit a success event with a message
        $this->dispatch('success', 'Data berhasil dihapus');
    }

    public function openModal()
    {
        $this->isOpen = true;
    }

    public function closeModal()
    {
        $this->isOpen = false;
    }

    private function resetInputFields(){
        // $this->kode = Str::uuid(); // Generate UUID for new farms
        $this->kode = ''; // Generate UUID for new farms
        $this->nama = '';
        $this->alamat = '';
        $this->farm_id = '';
    }

    protected $rules = [
        'kode' => 'required', // Unique except on update
        'nama' => 'required',
        'alamat' => 'required',
        'telp' => 'required',
        'pic' => 'required',
        'telp_pic' => 'required',
    ];

    public function updatingKode()
    {
        if ($this->farm_id) {
            $this->resetErrorBag('kode'); // Clear error if it's an edit
        }
    }
}

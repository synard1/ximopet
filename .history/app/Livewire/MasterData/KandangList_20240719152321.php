<?php

namespace App\Livewire\MasterData;

use Livewire\Component;
use Illuminate\Support\Str;
use App\Models\Kandang;
use Ramsey\Uuid\Uuid; // Import the UUID library


class KandangList extends Component
{
    public $kandangs, $kode, $nama, $status = 'Aktif';

    public $kandang_id = null; // Initialize with null instead of empty string
    public $isOpen = 0;

    // protected $listeners = ['edit','create']; // Add the listener

    protected $listeners = [
        'delete_kandang' => 'deleteKandangList',
        'editKandang' => 'editKandang',
        'create' => 'create',
    ];


    public function render()
    {
        $this->kandangs = Kandang::all();
        return view('livewire.master-data.kandang-list', ['kandangs' => $this->kandangs]);
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
        $this->alamat = $kandang->alamat;
        $this->telp = $kandang->telp;
        $this->pic = $kandang->pic;
        $this->telp_pic = $kandang->telp_pic;

        // session()->flash('message', 'test edit');


        $this->openModal();
    }

    // public function delete($id)
    // {
    //     // Kandang::find($id)->delete();
    //     Kandang::where('id',$id)->delete();
    //     session()->flash('message', 'Kandang deleted successfully.');
    // }

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
    }

    public function closeModal()
    {
        $this->isOpen = false;
    }

    private function resetInputFields(){
        // $this->kode = Str::uuid(); // Generate UUID for new kandangs
        $this->kode = ''; // Generate UUID for new kandangs
        $this->nama = '';
        $this->alamat = '';
        $this->kandang_id = '';
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
        if ($this->kandang_id) {
            $this->resetErrorBag('kode'); // Clear error if it's an edit
        }
    }
}

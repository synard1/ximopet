<?php

namespace App\Livewire\MasterData;

use Livewire\Component;
use Illuminate\Support\Str;
use App\Models\Rekanan;
use Ramsey\Uuid\Uuid; // Import the UUID library


class Ekspedisi extends Component
{
    public $ekspedisis, $kode, $nama, $alamat, $telp, $pic, $telp_pic, $email, $status = 'Aktif';

    public $ekspedisi_id = null; // Initialize with null instead of empty string
    public $isOpen = 0;

    // protected $listeners = ['edit','create']; // Add the listener

    protected $listeners = [
        'delete_ekspedisi' => 'deleteEkspedisi',
        'editEkspedisi' => 'editEkspedisi',
        'create' => 'create',
    ];


    public function render()
    {
        $this->ekspedisis = Rekanan::all();
        return view('livewire.master-data.ekspedisi', ['ekspedisis' => $this->ekspedisis]);
    }

    public function create()
    {
        $this->resetInputFields();
        // $this->kode = (string) Uuid::uuid4(); // Generate and cast to string
        $this->openModal();
    }

    public function store()
    {
        $data =[
            'jenis' => "Ekspedisi",
            'kode' => $this->kode,
            'nama' => $this->nama,
            'alamat' => $this->alamat,
            'email' => $this->email,
            'status' => 'Aktif',
        ];

        // dd($data);
        $this->validate();
        Rekanan::updateOrCreate(['id' => $this->ekspedisi_id], [
            'jenis' => "Ekspedisi",
            'kode' => $this->kode,
            'nama' => $this->nama,
            'alamat' => $this->alamat,
            'email' => $this->email,
            'status' => 'Aktif',
        ]);

        if($this->ekspedisi_id){
            $this->dispatch('success', __('Data Ekspedisi Berhasil Diubah'));

        }else{
            $this->dispatch('success', __('Data Ekspedisi Berhasil Dibuat'));
        }

        // session()->flash('message', 
        // $this->ekspedisi_id ? 'Rekanan updated successfully.' : 'Rekanan created successfully.');
        $this->closeModal();
        $this->resetInputFields();
    }

    public function editEkspedisi($id)
    {
        $ekspedisi = Rekanan::where('id',$id)->first();
        $this->ekspedisi_id = $id;
        $this->kode = $ekspedisi->kode;
        $this->nama = $ekspedisi->nama;
        $this->alamat = $ekspedisi->alamat;
        $this->telp = $ekspedisi->telp;
        $this->pic = $ekspedisi->pic;
        $this->telp_pic = $ekspedisi->telp_pic;
        $this->email = $ekspedisi->email;

        // session()->flash('message', 'test edit');


        $this->openModal();
    }

    public function deleteEkspedisi($id)
    {
        $ekspedisi = Rekanan::findOrFail($id);

        if ($ekspedisi->transaksiBelis()->exists() || $ekspedisi->transaksiHarians()->exists()) {
            $this->dispatch('error', 'Ekspedisi tidak dapat dihapus karena memiliki transaksi terkait.');
            return;
        }

        $ekspedisi->delete();

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
        // $this->kode = Str::uuid(); // Generate UUID for new ekspedisis
        $this->kode = ''; // Generate UUID for new ekspedisis
        $this->nama = '';
        $this->alamat = '';
        $this->email = '';
        $this->ekspedisi_id = '';
    }

    protected $rules = [
        'kode' => 'required', // Unique except on update
        'nama' => 'required',
        'alamat' => 'required',
        'email' => 'required|email',
    ];

    public function updatingKode()
    {
        if ($this->ekspedisi_id) {
            $this->resetErrorBag('kode'); // Clear error if it's an edit
        }
    }
}

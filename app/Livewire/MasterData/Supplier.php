<?php

namespace App\Livewire\MasterData;

use Livewire\Component;
use Illuminate\Support\Str;
use App\Models\Rekanan;
use Ramsey\Uuid\Uuid; // Import the UUID library


class Supplier extends Component
{
    public $suppliers, $kode, $nama, $alamat, $telp, $pic, $telp_pic, $email, $status = 'Aktif';

    public $supplier_id = null; // Initialize with null instead of empty string
    public $isOpen = 0;

    // protected $listeners = ['edit','create']; // Add the listener

    protected $listeners = [
        'delete_supplier' => 'deleteSupplier',
        'editSupplier' => 'editSupplier',
        'create' => 'create',
    ];


    public function render()
    {
        $this->suppliers = Rekanan::all();
        return view('livewire.master-data.supplier', ['suppliers' => $this->suppliers]);
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
            'jenis' => "Supplier",
            'kode' => $this->kode,
            'nama' => $this->nama,
            'alamat' => $this->alamat,
            'email' => $this->email,
            'status' => 'Aktif',
        ];

        // dd($data);
        $this->validate();
        Rekanan::updateOrCreate(['id' => $this->supplier_id], [
            'jenis' => "Supplier",
            'kode' => $this->kode,
            'nama' => $this->nama,
            'alamat' => $this->alamat,
            'email' => $this->email,
            'status' => 'Aktif',
        ]);

        if($this->supplier_id){
            $this->dispatch('success', __('Data Supplier Berhasil Diubah'));

        }else{
            $this->dispatch('success', __('Data Supplier Berhasil Dibuat'));
        }

        // session()->flash('message', 
        // $this->supplier_id ? 'Rekanan updated successfully.' : 'Rekanan created successfully.');
        $this->closeModal();
        $this->resetInputFields();
    }

    public function editSupplier($id)
    {
        $supplier = Rekanan::where('id',$id)->first();
        $this->supplier_id = $id;
        $this->kode = $supplier->kode;
        $this->nama = $supplier->nama;
        $this->alamat = $supplier->alamat;
        $this->telp = $supplier->telp;
        $this->pic = $supplier->pic;
        $this->telp_pic = $supplier->telp_pic;
        $this->email = $supplier->email;

        // session()->flash('message', 'test edit');


        $this->openModal();
    }

    public function deleteSupplier($id)
    {
        $supplier = Rekanan::findOrFail($id);

        if ($supplier->transaksi()->exists()) {
            $this->dispatch('error', 'Supplier tidak dapat dihapus karena memiliki transaksi terkait.');
            return;
        }

        $supplier->delete();

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
        // $this->kode = Str::uuid(); // Generate UUID for new suppliers
        $this->kode = ''; // Generate UUID for new suppliers
        $this->nama = '';
        $this->alamat = '';
        $this->email = '';
        $this->supplier_id = '';
    }

    protected $rules = [
        'kode' => 'required', // Unique except on update
        'nama' => 'required',
        'alamat' => 'required',
        'email' => 'required|email',
    ];

    public function updatingKode()
    {
        if ($this->supplier_id) {
            $this->resetErrorBag('kode'); // Clear error if it's an edit
        }
    }
}

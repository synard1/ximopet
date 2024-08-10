<?php

namespace App\Livewire\MasterData;

use Livewire\Component;
use Illuminate\Support\Str;
use App\Models\Farm;
use Ramsey\Uuid\Uuid; // Import the UUID library


class FarmList extends Component
{
    public $customers, $kode, $nama, $alamat, $telp, $pic, $telp_pic, $email, $status = 'Aktif';

    public $customer_id = null; // Initialize with null instead of empty string
    public $isOpen = 0;

    // protected $listeners = ['edit','create']; // Add the listener

    protected $listeners = [
        'delete_customer' => 'deleteFarmList',
        'editFarmList' => 'editFarmList',
        'create' => 'create',
    ];


    public function render()
    {
        $this->customers = Farm::all();
        return view('livewire.master-data.customer', ['customers' => $this->customers]);
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
        Farm::updateOrCreate(['id' => $this->customer_id], [
            'jenis' => "FarmList",
            'kode' => $this->kode,
            'nama' => $this->nama,
            'alamat' => $this->alamat,
            'email' => $this->email,
            'status' => 'Aktif',
        ]);

        if($this->customer_id){
            $this->dispatch('success', __('Data FarmList Berhasil Diubah'));

        }else{
            $this->dispatch('success', __('Data FarmList Berhasil Dibuat'));
        }

        // session()->flash('message', 
        // $this->customer_id ? 'Farm updated successfully.' : 'Farm created successfully.');
        $this->closeModal();
        $this->resetInputFields();
    }

    public function editFarmList($id)
    {
        $customer = Farm::where('id',$id)->first();
        $this->customer_id = $id;
        $this->kode = $customer->kode;
        $this->nama = $customer->nama;
        $this->alamat = $customer->alamat;
        $this->telp = $customer->telp;
        $this->pic = $customer->pic;
        $this->telp_pic = $customer->telp_pic;
        $this->email = $customer->email;

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
        // $this->kode = Str::uuid(); // Generate UUID for new customers
        $this->kode = ''; // Generate UUID for new customers
        $this->nama = '';
        $this->alamat = '';
        $this->email = '';
        $this->customer_id = '';
    }

    protected $rules = [
        'kode' => 'required', // Unique except on update
        'nama' => 'required',
        'alamat' => 'required',
        'email' => 'required|email',
    ];

    public function updatingKode()
    {
        if ($this->customer_id) {
            $this->resetErrorBag('kode'); // Clear error if it's an edit
        }
    }
}

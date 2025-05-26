<?php

namespace App\Livewire\MasterData;

use Livewire\Component;
use Illuminate\Support\Str;
use App\Models\Partner;
use Ramsey\Uuid\Uuid; // Import the UUID library


class Customer extends Component
{
    public $customers, $code, $name, $address, $phone_number, $contact_person, $email, $status = 'active';
    public $showForm = false;

    public $customer_id = null; // Initialize with null instead of empty string
    public $isOpen = 0;

    // protected $listeners = ['edit','create']; // Add the listener

    protected $listeners = [
        'delete_customer' => 'deleteCustomer',
        'editCustomer' => 'editCustomer',
        'create' => 'create',
        'createShowModal' => 'createShowModal',
    ];


    public function render()
    {
        $this->customers = Partner::where('type', 'Customer')->get();
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
        Partner::updateOrCreate(['id' => $this->customer_id], [
            'type' => "Customer",
            'code' => $this->kode,
            'name' => $this->nama,
            'address' => $this->alamat,
            'email' => $this->email,
            'status' => 'active',
        ]);

        if ($this->customer_id) {
            $this->dispatch('success', __('Data Customer Berhasil Diubah'));
        } else {
            $this->dispatch('success', __('Data Customer Berhasil Dibuat'));
        }

        // session()->flash('message', 
        // $this->customer_id ? 'Partner updated successfully.' : 'Partner created successfully.');
        $this->closeModal();
        $this->resetInputFields();
    }

    public function editCustomer($id)
    {
        $customer = Partner::where('id', $id)->first();
        $this->customer_id = $id;
        $this->code = $customer->code;
        $this->name = $customer->name;
        $this->address = $customer->address;
        $this->phone_number = $customer->phone_number;
        $this->contact_person = $customer->contact_person;
        $this->email = $customer->email;

        // session()->flash('message', 'test edit');


        $this->openModal();
    }

    // public function delete($id)
    // {
    //     // Partner::find($id)->delete();
    //     Partner::where('id',$id)->delete();
    //     session()->flash('message', 'Partner deleted successfully.');
    // }

    public function deleteCustomer($id)
    {
        // Delete the user record with the specified ID
        Partner::destroy($id);

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

    private function resetInputFields()
    {
        // $this->kode = Str::uuid(); // Generate UUID for new customers
        $this->code = ''; // Generate UUID for new customers
        $this->name = '';
        $this->address = '';
        $this->email = '';
        $this->customer_id = '';
    }

    protected $rules = [
        'code' => 'required', // Unique except on update
        'name' => 'required',
        'address' => 'required',
        'email' => 'nullable',
        'phone_number' => 'nullable',
        'contact_person' => 'nullable',
    ];

    public function updatingKode()
    {
        if ($this->customer_id) {
            $this->resetErrorBag('code'); // Clear error if it's an edit
        }
    }

    public function createShowModal()
    {
        $this->resetInputFields();
        $this->showForm = true;
        $this->dispatch('hide-datatable');
    }
}

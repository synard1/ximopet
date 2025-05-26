<?php

namespace App\Livewire\MasterData\Customer;

use Livewire\Component;
use Illuminate\Support\Str;
use App\Models\Partner;
use Ramsey\Uuid\Uuid; // Import the UUID library
use Illuminate\Validation\Rule;

class Create extends Component
{
    public $customers, $code, $name, $address, $phone_number, $contact_person, $email, $status = 'active';
    public $modalFormVisible = false;
    public $modalId = null;
    public $showForm = false;
    public $customer_id = null; // Initialize with null instead of empty string
    public $isOpen = 0;

    // protected $listeners = ['edit','create']; // Add the listener

    protected $listeners = [
        'delete_customer' => 'deleteCustomer',
        'editCustomer' => 'editCustomer',
        'create' => 'create',
        'createShowModal' => 'createShowModal',
        'showEditModal' => 'editShowModal',
    ];


    public function render()
    {
        $this->customers = Partner::all();
        return view('livewire.master-data.customer.create', [
            'customers' => $this->customers,
            'modalId' => $this->modalId,
            'modalFormVisible' => $this->modalFormVisible,
            'data' => Partner::orderBy('name')->paginate(10),
        ]);
    }

    public function create()
    {
        $this->resetInputFields();
        // $this->kode = (string) Uuid::uuid4(); // Generate and cast to string
        $this->openModal();
    }

    public function save()
    {
        $data = [
            'type' => "Customer",
            'code' => $this->code,
            'name' => $this->name,
            'address' => $this->address,
            'phone_number' => $this->phone_number,
            'contact_person' => $this->contact_person,
            'email' => $this->email,
            'status' => 'active',
        ];

        // dd($data);
        $this->validate();
        Partner::updateOrCreate(['id' => $this->customer_id], [
            'type' => "Customer",
            'code' => $this->code,
            'name' => $this->name,
            'address' => $this->address,
            'phone_number' => $this->phone_number,
            'contact_person' => $this->contact_person,
            'email' => $this->email,
            'status' => 'active',
        ]);

        if ($this->customer_id) {
            $this->dispatch('success', __('Data Customer Berhasil Diubah'));
            $this->closeModalCustomer();
        } else {
            $this->dispatch('success', __('Data Customer Berhasil Dibuat'));
            $this->closeModalCustomer();
        }

        // session()->flash('message', 
        // $this->ekspedisi_id ? 'Rekanan updated successfully.' : 'Rekanan created successfully.');
        $this->closeModal();
        $this->resetInputFields();
    }

    public function editCustomer($id)
    {
        $customer = Partner::where('id', $id)->where('type', 'Customer')->first();
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

    public function deleteCustomer($id)
    {
        $customer = Partner::findOrFail($id);

        if ($customer->livestockPurchasesAsVendor()->exists() || $customer->livestockPurchasesAsExpedition()->exists()) {
            $this->dispatch('error', 'Customer tidak dapat dihapus karena memiliki transaksi terkait.');
            return;
        }

        // dd($expedition);

        $customer->delete();

        $this->dispatch('success', 'Data berhasil dihapus');
    }

    public function openModal()
    {
        $this->isOpen = true;
        $this->showForm = true;
        $this->dispatch('hide-datatable');
    }

    public function closeModal()
    {
        $this->isOpen = false;
        $this->modalFormVisible = false;
        $this->dispatch('show-datatable');
    }

    private function resetInputFields()
    {
        $this->code = '';
        $this->name = '';
        $this->address = '';
        $this->phone_number = '';
        $this->contact_person = '';
        $this->email = '';
        $this->customer_id = '';
    }

    public function rules()
    {
        return [
            'code' => [
                'required',
                Rule::unique('partners')->where(function ($query) {
                    return $query->where('type', 'Customer');
                })->ignore($this->customer_id)
            ],
            'name' => 'required',
            'address' => 'required',
            'email' => 'nullable',
            'phone_number' => 'nullable',
            'contact_person' => 'nullable',
        ];
    }

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

    public function closeModalCustomer()
    {
        $this->showForm = false;
        $this->resetInputFields();
        $this->dispatch('show-datatable');
    }
}

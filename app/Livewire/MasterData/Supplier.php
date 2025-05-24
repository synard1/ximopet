<?php

namespace App\Livewire\MasterData;

use Livewire\Component;
use Illuminate\Support\Str;
use App\Models\Partner;
use Ramsey\Uuid\Uuid; // Import the UUID library
use Illuminate\Support\Facades\Log;


class Supplier extends Component
{
    public $suppliers, $code, $name, $address, $phone_number, $contact_person, $email, $status = 'active';

    public $supplier_id = null; // Initialize with null instead of empty string
    public $isOpen = 0;
    public $isEdit = false;
    public $formKey; // Add a property to track the form key

    // protected $listeners = ['edit','create']; // Add the listener

    protected $listeners = [
        'delete_supplier' => 'deleteSupplier',
        'editSupplier' => 'editSupplier',
        'showCreateForm' => 'create',
    ];


    public function render()
    {
        $this->suppliers = Partner::where('type', 'Supplier')->get();
        return view('livewire.master-data.supplier', ['suppliers' => $this->suppliers]);
    }

    public function create()
    {
        $this->resetInputFields();
        $this->isEdit = false;
        $this->formKey = 'create-' . now()->timestamp; // Update key for create
        $this->openModal();
    }

    public function store()
    {
        $this->validate($this->rules());

        $data = [
            'type' => "Supplier",
            'code' => $this->code,
            'name' => $this->name,
            'address' => $this->address,
            'phone_number' => $this->phone_number,
            'contact_person' => $this->contact_person,
            'email' => $this->email,
            'status' => 'active',
        ];

        try {
            Partner::updateOrCreate(['id' => $this->supplier_id], $data);

            $message = $this->supplier_id ? 'Data Supplier Berhasil Diubah' : 'Data Supplier Berhasil Dibuat';
            $this->dispatch('success', $message);

            Log::info('Supplier data saved successfully', [
                'supplier_id' => $this->supplier_id,
                'is_edit' => $this->isEdit,
                'data' => $data
            ]);

            $this->closeModal();
            $this->resetInputFields();
        } catch (\Exception $e) {
            Log::error('Error saving supplier data', [
                'supplier_id' => $this->supplier_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->dispatch('error', 'Terjadi kesalahan saat menyimpan data supplier: ' . $e->getMessage());
        }
    }

    public function editSupplier($id)
    {
        $supplier = Partner::where('id', $id)->firstOrFail();
        $this->supplier_id = $id;
        $this->code = $supplier->code;
        $this->name = $supplier->name;
        $this->address = $supplier->address;
        $this->phone_number = $supplier->phone_number;
        $this->contact_person = $supplier->contact_person;
        $this->email = $supplier->email;
        $this->isEdit = true;
        $this->formKey = 'edit-' . $id . '-' . now()->timestamp; // Update key for edit

        $this->openModal();
    }

    public function deleteSupplier($id)
    {
        try {
            $supplier = Partner::findOrFail($id);

            // Check if supplier has any related transactions in both supply and feed purchases
            $hasSupplyTransactions = $supplier->supplyPurchaseBatch()->exists();
            $hasFeedTransactions = $supplier->feedPurchasesBatch()->exists();
            $hasLivestockTransactions = $supplier->livestockPurchases()->exists();

            if ($hasSupplyTransactions || $hasFeedTransactions || $hasLivestockTransactions) {
                // Log detailed information about the transactions
                Log::warning('Supplier deletion blocked due to existing transactions', [
                    'supplier_id' => $id,
                    'supplier_name' => $supplier->name,
                    'has_supply_transactions' => $hasSupplyTransactions,
                    'has_feed_transactions' => $hasFeedTransactions,
                    'has_livestock_transactions' => $hasLivestockTransactions,
                    'supply_transactions_count' => $supplier->supplyPurchaseBatch()->count(),
                    'feed_transactions_count' => $supplier->feedPurchasesBatch()->count(),
                    'livestock_transactions_count' => $supplier->livestockPurchases()->count(),
                ]);

                $this->dispatch('error', 'Supplier tidak dapat dihapus karena memiliki transaksi pembelian ( supply, pakan, ayam ) terkait.');
                return;
            }

            // If no transactions, proceed with deletion
            $supplier->delete();
            Log::info('Supplier successfully deleted', [
                'supplier_id' => $id,
                'supplier_name' => $supplier->name
            ]);

            $this->dispatch('success', 'Data supplier berhasil dihapus');
        } catch (\Exception $e) {
            Log::error('Error deleting supplier', [
                'supplier_id' => $id,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString()
            ]);

            $this->dispatch('error', 'Terjadi kesalahan saat menghapus data supplier: ' . $e->getMessage());
        }
    }

    public function openModal()
    {
        $this->isOpen = true;
    }

    public function closeModal()
    {
        $this->isOpen = false;
        $this->resetInputFields();
    }

    private function resetInputFields()
    {
        $this->code = '';
        $this->name = '';
        $this->address = '';
        $this->phone_number = '';
        $this->contact_person = '';
        $this->email = '';
        $this->supplier_id = null;
        $this->isEdit = false;
        $this->formKey = 'reset-' . now()->timestamp;
    }

    protected function rules()
    {
        // Check for unique code, ignoring soft-deleted records and the current record if editing
        $rules = [
            'code' => 'required|unique:partners,code,NULL,id,deleted_at,NULL', // Modified rule for unique code with soft deletes
            'name' => 'required',
            'address' => 'required',
            'phone_number' => 'required',
            'contact_person' => 'required',
            'email' => 'required|email',
        ];

        // If editing, the unique rule should ignore the current supplier's ID
        if ($this->supplier_id) {
            $rules['code'] = 'required|unique:partners,code,' . $this->supplier_id . ',id,deleted_at,NULL';
        }

        return $rules;
    }

    public function updatingCode()
    {
        if ($this->supplier_id) {
            $this->resetErrorBag('code');
        }
    }
}

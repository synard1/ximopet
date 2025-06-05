<?php

namespace App\Livewire\MasterData\Expedition;

use Livewire\Component;
use Illuminate\Support\Str;
use App\Models\Partner;
use Ramsey\Uuid\Uuid; // Import the UUID library
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class Create extends Component
{
    public $expeditions, $code, $name, $address, $phone_number, $contact_person, $email, $status = 'active';
    public $modalFormVisible = false;
    public $modalId = null;
    public $showForm = false;
    public $expedition_id = null; // Initialize with null instead of empty string
    public $isOpen = 0;

    protected $listeners = [
        'delete_expedition' => 'deleteExpedition',
        'editExpedition' => 'editExpedition',
        'create' => 'create',
        'createShowModal' => 'createShowModal',
        'showEditModal' => 'editShowModal',
    ];

    public function render()
    {
        if (!Auth::user()->can('read expedition master data')) {
            abort(403);
        }

        $this->expeditions = Partner::all();
        return view('livewire.master-data.expedition.create', [
            'expeditions' => $this->expeditions,
            'modalId' => $this->modalId,
            'modalFormVisible' => $this->modalFormVisible,
            'data' => Partner::orderBy('name')->paginate(10),
        ]);
    }

    public function create()
    {
        if (!Auth::user()->can('create expedition master data')) {
            abort(403);
        }

        $this->resetInputFields();
        $this->openModal();
    }

    public function save()
    {
        if (!Auth::user()->can('create expedition master data') && !$this->expedition_id) {
            abort(403);
        }

        if (!Auth::user()->can('update expedition master data') && $this->expedition_id) {
            abort(403);
        }

        $data = [
            'type' => "Expedition",
            'code' => $this->code,
            'name' => $this->name,
            'address' => $this->address,
            'phone_number' => $this->phone_number,
            'contact_person' => $this->contact_person,
            'email' => $this->email,
            'status' => 'active',
        ];

        $this->validate();
        Partner::updateOrCreate(['id' => $this->expedition_id], [
            'type' => "Expedition",
            'code' => $this->code,
            'name' => $this->name,
            'address' => $this->address,
            'phone_number' => $this->phone_number,
            'contact_person' => $this->contact_person,
            'email' => $this->email,
            'status' => 'active',
        ]);

        if ($this->expedition_id) {
            $this->dispatch('success', __('Data Expedisi Berhasil Diubah'));
            $this->closeModalExpedition();
        } else {
            $this->dispatch('success', __('Data Expedisi Berhasil Dibuat'));
            $this->closeModalExpedition();
        }

        $this->closeModal();
        $this->resetInputFields();
    }

    public function editExpedition($id)
    {
        if (!Auth::user()->can('update expedition master data')) {
            abort(403);
        }

        $expedition = Partner::where('id', $id)->where('type', 'Expedition')->first();
        $this->expedition_id = $id;
        $this->code = $expedition->code;
        $this->name = $expedition->name;
        $this->address = $expedition->address;
        $this->phone_number = $expedition->phone_number;
        $this->contact_person = $expedition->contact_person;
        $this->email = $expedition->email;

        $this->openModal();
    }

    public function deleteExpedition($id)
    {
        if (!Auth::user()->can('delete expedition master data')) {
            abort(403);
        }

        $expedition = Partner::findOrFail($id);

        if ($expedition->livestockPurchasesAsVendor()->exists() || $expedition->livestockPurchasesAsExpedition()->exists()) {
            $this->dispatch('error', 'Expedisi tidak dapat dihapus karena memiliki transaksi terkait.');
            return;
        }

        $expedition->delete();

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
        $this->expedition_id = '';
    }

    public function rules()
    {
        return [
            'code' => [
                'required',
                Rule::unique('partners')->where(function ($query) {
                    return $query->where('type', 'Expedition');
                })->ignore($this->expedition_id)
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
        if ($this->expedition_id) {
            $this->resetErrorBag('code'); // Clear error if it's an edit
        }
    }

    public function createShowModal()
    {
        if (!Auth::user()->can('create expedition master data')) {
            abort(403);
        }

        $this->resetInputFields();
        $this->showForm = true;
        $this->dispatch('hide-datatable');
    }

    public function closeModalExpedition()
    {
        $this->showForm = false;
        $this->resetInputFields();
        $this->dispatch('show-datatable');
    }
}

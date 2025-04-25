<?php

namespace App\Livewire\Expeditions;

use App\Models\Expedition;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Log;

class Create extends Component
{
    use WithPagination;

    public $modalFormVisible = false;
    public $modalId;

    public $kode;
    public $name;
    public $contact_person;
    public $phone_number;
    public $address;
    public $description;
    public $status = 'active'; // Default status
    public $created_by; // Akan diisi otomatis oleh user yang login
    public $updated_by; // Akan diisi saat update

    protected $rules = [
        'kode' => 'required|string|max:64|unique:expeditions,kode',
        'name' => 'required|string|max:255',
        'contact_person' => 'nullable|string|max:255',
        'phone_number' => 'nullable|string|max:20',
        'address' => 'nullable|string',
        'description' => 'nullable|string',
        'status' => 'required|in:active,inactive',
    ];

    protected $listeners = [
        'showCreateModal' => 'createShowModal',
        'showEditModal' => 'editShowModal',
        'delete' => 'delete',
    ];

    public function mount()
    {
        $this->resetCreateForm();
        $this->created_by = auth()->id(); // Set created_by saat mount
    }

    public function render()
    {
        return view('livewire.expeditions.create', [
            'data' => Expedition::orderBy('name')->paginate(10),
        ]);
    }

    public function resetCreateForm()
    {
        $this->kode = '';
        $this->name = '';
        $this->contact_person = '';
        $this->phone_number = '';
        $this->address = '';
        $this->description = '';
        $this->status = 'active';
        $this->modalId = null;
        $this->resetValidation();
    }

    public function createShowModal()
    {
        $this->resetCreateForm();
        $this->modalFormVisible = true;
    }

    public function editShowModal($id)
    {
        $this->resetCreateForm();
        $this->modalId = $id;
        $model = Expedition::findOrFail($id);
        $this->kode = $model->kode;
        $this->name = $model->name;
        $this->contact_person = $model->contact_person;
        $this->phone_number = $model->phone_number;
        $this->address = $model->address;
        $this->description = $model->description;
        $this->status = $model->status;
        $this->modalFormVisible = true;
    }

    public function create()
    {
        $this->validate();

        Expedition::create([
            'kode' => $this->kode,
            'name' => $this->name,
            'contact_person' => $this->contact_person,
            'phone_number' => $this->phone_number,
            'address' => $this->address,
            'description' => $this->description,
            'status' => $this->status,
            'created_by' => auth()->id(),
        ]);

        $this->modalFormVisible = false;
        $this->resetCreateForm();
        $this->dispatch('show-alert', ['type' => 'success', 'message' => 'Ekspedisi berhasil ditambahkan.']);
    }

    public function update()
    {
        $this->validate([
            'kode' => 'required|string|max:64|unique:expeditions,kode,' . $this->modalId,
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $model = Expedition::findOrFail($this->modalId);
        $model->update([
            'kode' => $this->kode,
            'name' => $this->name,
            'contact_person' => $this->contact_person,
            'phone_number' => $this->phone_number,
            'address' => $this->address,
            'description' => $this->description,
            'status' => $this->status,
            'updated_by' => auth()->id(),
        ]);

        $this->modalFormVisible = false;
        $this->resetCreateForm();
        $this->dispatch('show-alert', ['type' => 'success', 'message' => 'Ekspedisi berhasil diperbarui.']);
    }

    public function delete($id)
    {
        Log::info('Method delete di Livewire terpanggil untuk ID:', ['id' => $id]);
        Expedition::findOrFail($id)->delete();
        $this->dispatch('show-alert', ['type' => 'success', 'message' => 'Ekspedisi berhasil dihapus.']);
    }

    // public function delete($id)
    // {
    //     Expedition::findOrFail($id)->delete();
    //     $this->dispatch('show-alert', ['type' => 'success', 'message' => 'Ekspedisi berhasil dihapus.']);
    // }

    public function closeModal()
    {
        $this->modalFormVisible = false;
        $this->resetCreateForm();
    }
}

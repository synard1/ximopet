<?php

namespace App\Livewire\MasterData\LivestockStrain;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use App\Models\LivestockStrain;
use App\Models\Unit;
use App\Models\UnitConversion;
use App\Models\LivestockStrainStandard;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class Create extends Component
{
    public $livestockStrainId;
    public $code, $name, $description, $status;
    public $showForm = false;
    public $edit_mode = false;

    protected $listeners = [
        'editStrain' => 'showEditForm',
        'showCreateForm' => 'showCreateForm',
        'cancel' => 'cancel',
        'delete_strain' => 'deleteStrain',
    ];

    public function mount()
    {
        // $this->units = Unit::all();
    }


    public function render()
    {
        return view('livewire.master-data.livestock-strain.create');
    }

    public function showCreateForm()
    {
        $this->resetForm();
        $this->showForm = true;
        $this->dispatch('hide-datatable');
    }

    public function resetForm()
    {
        $this->reset();
    }

    public function resetInputStandard()
    {
        $this->reset();
    }

    public function close()
    {
        $this->resetForm();
        $this->showForm = false;
        $this->dispatch('show-datatable');
    }

    public function showEditForm($id)
    {
        $livestockStrain = LivestockStrain::findOrFail($id);

        $this->livestockStrainId = $livestockStrain->id;
        $this->code = $livestockStrain->code;
        $this->name = $livestockStrain->name;
        $this->description = $livestockStrain->description;
        $this->status = $livestockStrain->status;

        $this->showForm = true;
        $this->edit_mode = true;
        $this->dispatch('hide-datatable');
    }

    public function save()
    {
        $this->validate([
            'code' => 'required',
            'name' => 'required',
        ]);

        // Check if the user has the permission to create or update livestock strains
        if (!auth()->user()->can('create livestock strain') && !$this->edit_mode) {
            $this->dispatch('error', 'You do not have permission to create livestock strains.');
            return;
        }

        if (!auth()->user()->can('update livestock strain') && $this->edit_mode) {
            $this->dispatch('error', 'You do not have permission to update livestock strain.');
            return;
        }

        try {
            DB::beginTransaction();

            // Simpan atau update Livestock Strain
            $livestockStrain = $this->edit_mode && $this->livestockStrainId
                ? LivestockStrain::findOrFail($this->livestockStrainId)
                : new LivestockStrain(['created_by' => auth()->id()]);

            $livestockStrain->fill([
                'code' => $this->code,
                'name' => $this->name,
                'description' => $this->description,
                'status' => $this->status,
            ])->save();

            DB::commit();

            $this->dispatch('success', 'Data Strain berhasil ' . ($this->edit_mode ? 'diperbarui' : 'disimpan'));
            $this->close();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to save livestock strain data: ' . $e->getMessage());
            $this->dispatch('error', 'Gagal menyimpan data strain. Silakan coba lagi. Error: ' . $e->getMessage());
        }
    }

    public function deleteStrain($strainId)
    {
        $strain = LivestockStrain::findOrFail($strainId);

        // Check if strain has any associated standards
        $hasStandards = LivestockStrainStandard::where('livestock_strain_id', $strainId)->exists();

        if ($hasStandards) {
            $this->dispatch('error', 'Cannot delete strain because it has associated standards. Please delete the standards first.');
            return;
        }

        // Check if the user has the permission to delete livestock strains
        if (!auth()->user()->can('delete livestock strain')) {
            $this->dispatch('error', 'You do not have permission to delete livestock strain.');
            return;
        }

        $strain->delete();
        $this->dispatch('success', 'Strain deleted successfully');
    }
}

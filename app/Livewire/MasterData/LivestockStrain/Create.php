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
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\Auth;


class Create extends Component
{
    public $livestockStrainId;
    public $code, $name, $description, $status;
    public $showForm = false;
    public $edit_mode = false;
    public $conversion_units = [];
    public $unit_id;
    public $default_unit_id = null;

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

        $user = Auth::user();
        $companyId = $user->company_id;

        $units = Unit::active()->where('company_id', $companyId)->orderBy('name')->get();

        return view('livewire.master-data.livestock-strain.create', [
            'units' => $units,
        ]);
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
        $this->conversion_units = $livestockStrain->data['conversion_units'] ?? [];
        $this->unit_id = $livestockStrain->data['unit_id'] ?? null;
        $this->showForm = true;
        $this->edit_mode = true;
        $this->dispatch('hide-datatable');
    }

    public function addConversion()
    {
        $this->validate([
            'code' => 'required',
            'name' => 'required',
            'unit_id' => 'required',
        ]);

        $this->conversion_units[] = [
            'unit_id' => '',
            'value' => 1,
            'is_default_purchase' => false,
            'is_default_mutation' => false,
            'is_default_sale' => false,
            'is_smallest' => false,
        ];
    }

    public function removeConversion($index)
    {
        $unit = $this->conversion_units[$index] ?? null;
        if (!$unit) return;
        if (
            $unit['is_default_purchase'] ||
            $unit['is_default_mutation'] ||
            $unit['is_default_sale'] ||
            $unit['is_smallest']
        ) {
            $this->addError('conversion_units', 'Tidak bisa menghapus unit default.');
            return;
        }
        unset($this->conversion_units[$index]);
        $this->conversion_units = array_values($this->conversion_units);
    }

    public function removeConversionUnit($index)
    {
        unset($this->conversion_units[$index]);
        $this->conversion_units = array_values($this->conversion_units);
    }

    public function updatedUnitId($value)
    {
        $this->validate([
            'code' => 'required',
            'name' => 'required',
        ]);
        if (!$value) return;
        $unit = Unit::find($value);
        if (!$unit) return;
        $this->resetErrorBag();
        if ($this->default_unit_id) {
            $this->conversion_units = collect($this->conversion_units)
                ->reject(fn($item) => (string) $item['unit_id'] === (string) $this->default_unit_id)
                ->values()
                ->toArray();
        }
        $this->conversion_units[] = [
            'unit_id' => $unit->id,
            'unit_name' => $unit->name,
            'value' => 1,
            'is_default_purchase' => true,
            'is_default_mutation' => true,
            'is_default_sale' => true,
            'is_smallest' => true,
        ];
        $this->default_unit_id = $unit->id;
    }

    protected function validateConversionDefaults()
    {
        foreach (['is_default_purchase', 'is_default_mutation', 'is_default_sale', 'is_smallest'] as $field) {
            $count = collect($this->conversion_units)->filter(fn($unit) => $unit[$field] ?? false)->count();
            if ($count > 1) {
                throw ValidationException::withMessages([
                    'conversion_units' => "Hanya boleh satu $field yang dipilih sebagai default.",
                ]);
            }
        }
    }

    public function toggleDefault($field, $index)
    {
        foreach ($this->conversion_units as $i => $unit) {
            $this->conversion_units[$i][$field] = ($i === $index);
        }
    }

    public function save()
    {
        $this->validate([
            'code' => 'required',
            'name' => 'required',
        ]);
        $this->validateConversionDefaults();

        // Check if the user has the permission to create or update livestock strains
        if (!Auth::user()->can('create livestock strain master data') && !$this->edit_mode) {
            $this->dispatch('error', 'You do not have permission to create livestock strains.');
            return;
        }

        if (!Auth::user()->can('update livestock strain master data') && $this->edit_mode) {
            $this->dispatch('error', 'You do not have permission to update livestock strain.');
            return;
        }

        try {
            DB::beginTransaction();

            // Simpan atau update Livestock Strain
            $livestockStrain = $this->edit_mode && $this->livestockStrainId
                ? LivestockStrain::findOrFail($this->livestockStrainId)
                : new LivestockStrain(['created_by' => Auth::id()]);

            $payload = [
                'unit_id' => $this->unit_id,
                'unit_details' => Unit::find($this->unit_id)?->only('id', 'name', 'description'),
                'conversion_units' => collect($this->conversion_units)->map(function ($conv) {
                    $unit = Unit::find($conv['unit_id'])?->only('id', 'name', 'description');
                    return [
                        'unit_id' => $conv['unit_id'],
                        'unit_name' => $unit['name'],
                        'value' => $conv['value'],
                        'is_default_purchase' => $conv['is_default_purchase'] ?? false,
                        'is_default_mutation' => $conv['is_default_mutation'] ?? false,
                        'is_default_sale' => $conv['is_default_sale'] ?? false,
                        'is_smallest' => $conv['is_smallest'] ?? false,
                    ];
                })->toArray(),
            ];
            $livestockStrain->fill([
                'code' => $this->code,
                'name' => $this->name,
                'description' => $this->description,
                'status' => $this->status,
                'data' => $payload,
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
        if (!Auth::user()->can('delete livestock strain master data')) {
            $this->dispatch('error', 'You do not have permission to delete livestock strain.');
            return;
        }

        $strain->delete();
        $this->dispatch('success', 'Strain deleted successfully');
    }
}

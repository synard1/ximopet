<?php

namespace App\Livewire\MasterData\Unit;

use App\Models\Unit;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class Create extends Component
{
    public $showForm = false;
    public $edit_mode = false;
    public $unitId;
    public $type;
    public $code;
    public $symbol;
    public $name;
    public $status = 'active'; // Set default status

    protected $listeners = [
        'showEditForm' => 'showEditForm',
        'showCreateForm' => 'showCreateForm',
        'cancel' => 'cancel',
        'edit' => 'edit',
        'delete_unit' => 'delete',
    ];

    protected $rules = [
        'type' => 'required',
        'code' => 'nullable|unique:units,code,except,id',
        'symbol' => 'nullable',
        'name' => 'nullable',
        'status' => 'required',
        // 'status' => 'required|in:active,inactive,on_leave,suspended,terminated,probation,part_time,full_time,contract,temporary,resigned,retired,deceased,blacklist',
    ];

    public function showCreateForm()
    {
        if (!Auth::user()->can('create unit master data')) {
            abort(403, 'Unauthorized action.');
        }

        $this->resetForm();
        $this->showForm = true;
        $this->dispatch('hide-datatable');
    }

    public function showEditForm($id)
    {
        if (!Auth::user()->can('update unit master data')) {
            abort(403, 'Unauthorized action.');
        }

        $unit = Unit::findOrFail($id);

        $this->unitId = $unit->id;
        $this->type = $unit->type;
        $this->code = $unit->code;
        $this->symbol = $unit->symbol;
        $this->name = $unit->name;
        $this->status = $unit->status;

        $this->showForm = true;
        $this->edit_mode = true;
        $this->dispatch('hide-datatable');
    }

    public function resetForm()
    {
        $this->reset(['unitId', 'type', 'code', 'symbol', 'name', 'status']);
        $this->resetErrorBag();
    }

    public function close()
    {
        $this->resetForm();
        $this->showForm = false;
        $this->dispatch('show-datatable');
    }

    public function save()
    {
        if (!Auth::user()->can('create unit master data') && !$this->edit_mode) {
            abort(403, 'Unauthorized action.');
        }

        if (!Auth::user()->can('update unit master data') && $this->edit_mode) {
            abort(403, 'Unauthorized action.');
        }

        $this->validate([
            'type' => 'required',
            'code' => 'nullable|unique:units,code,' . ($this->edit_mode ? $this->unitId : 'NULL') . ',id',
            'symbol' => 'nullable',
            'name' => 'nullable',
            'status' => 'required',
        ]);

        DB::beginTransaction();
        try {
            if ($this->edit_mode) {
                $unit = Unit::findOrFail($this->unitId);
                $unit->update([
                    'type' => $this->type,
                    'code' => $this->code,
                    'symbol' => $this->symbol,
                    'name' => $this->name,
                    'status' => $this->status,
                    'updated_by' => auth()->id(), // Assuming you have authentication
                ]);
                $this->dispatch('success', 'Data unit berhasil diperbarui.');
                $this->dispatch('unitUpdated'); // Emit event for data table refresh
            } else {
                Unit::create([
                    'type' => $this->type,
                    'code' => $this->code,
                    'symbol' => $this->symbol,
                    'name' => $this->name,
                    'status' => $this->status,
                    'created_by' => auth()->id(), // Assuming you have authentication
                ]);
                $this->dispatch('success', 'Data unit berhasil disimpan.');
                $this->dispatch('unitCreated'); // Emit event for data table refresh
            }

            DB::commit();
            $this->close();
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback on any other exception

            $class = __CLASS__;
            $method = __FUNCTION__;
            $line = $e->getLine();
            $file = $e->getFile();
            $message = $e->getMessage();

            // Human-readable error message
            $errorMessage = 'Terjadi kesalahan saat menyimpan data unit. Silakan coba lagi.';

            // Dispatch user-friendly error
            $this->dispatch('error', $errorMessage);

            // Log detailed error for debugging
            Log::error("[$class::$method] Error: $message | Line: $line | File: $file");

            // Optionally: log stack trace
            Log::debug("[$class::$method] Stack trace: " . $e->getTraceAsString());
        }
    }

    public function delete($id)
    {
        if (!Auth::user()->can('delete unit master data')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();

            // Cek apakah unit ada di pakai di supplypurchase
            $usedInSupplyPurchase = DB::table('supply_purchases')
                ->where('unit_id', $id)
                ->exists();

            // Cek apakah unit ada di pakai di feedpurchase
            $usedInFeedPurchase = DB::table('feed_purchases')
                ->where('unit_id', $id)
                ->exists();

            if ($usedInSupplyPurchase || $usedInFeedPurchase) {
                throw new \Exception('Unit tidak dapat dihapus karena masih digunakan dalam supply purchase atau feed purchase.');
            }

            // Cek apakah unit ada di pakai di unitconversion
            $usedInUnitConversion = DB::table('unit_conversions')
                ->where('unit_id', $id)
                ->exists();

            // Cek apakah unit ada di pakai di unitconversion
            $usedInUnitConversionTwo = DB::table('unit_conversions')
                ->where('conversion_unit_id', $id)
                ->exists();

            if ($usedInUnitConversion || $usedInUnitConversionTwo) {
                throw new \Exception('Unit tidak dapat dihapus karena masih digunakan dalam unit conversion.');
            }

            // Jika tidak ada batch worker aktif, hapus data unit
            Unit::find($id)->delete();

            DB::commit();
            $this->dispatch('success', 'Data unit berhasil dihapus.');
            $this->dispatch('unitDeleted'); // Emit event for data table refresh

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('error', $e->getMessage());
            Log::error("[" . __CLASS__ . "::" . __FUNCTION__ . "] Error: " . $e->getMessage());
        }
    }

    public function render()
    {
        $unitTypes = config('xolution.unit_type');
        return view('livewire.master-data.unit.create', [
            'unitTypes' => $unitTypes,
        ]);
    }
}

<?php

namespace App\Livewire\OVK;

use App\DataTables\OVKRecordDataTable;
use App\Models\Farm;
use App\Models\Kandang;
use App\Models\OVKRecord;
use App\Models\Supply;
use App\Models\Unit;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $supply_id;
    public $farm_id;
    public $kandang_id;
    public $quantity;
    public $unit_id;
    public $usage_date;
    public $notes;
    public $showForm = false;

    protected $listeners = [
        'ovk-record-created' => '$refresh',
        'ovk-record-updated' => '$refresh',
        'ovk-record-deleted' => '$refresh',
        'showCreateForm' => 'showCreateForm',
        'showEditForm' => 'showEditForm',
        'deleteOVKRecord' => 'delete',
    ];

    public function render()
    {
        $records = OVKRecord::with(['supply', 'farm', 'kandang', 'unit'])
            ->latest()
            ->paginate(10);
        $supplies = Supply::whereHas('supplyCategory', function ($query) {
            $query->where('status', 'active');
        })->get();
        $farms = Farm::all();
        $kandangs = Kandang::all();
        $units = Unit::all();

        return view('livewire.ovk.index', [
            'records' => $records,
            'supplies' => $supplies,
            'farms' => $farms,
            'kandangs' => $kandangs,
            'units' => $units,
            'dataTable' => app(OVKRecordDataTable::class)
        ]);
    }

    public function showCreateForm()
    {
        $this->resetForm();
        $this->showForm = true;
        $this->dispatch('hide-datatable');
    }

    public function showEditForm($id)
    {
        dd($id);
        // $this->dispatch('showEditForm', ['id' => $id]);
    }

    public function delete($id)
    {
        try {
            $record = OVKRecord::findOrFail($id);
            $record->delete();
            $this->dispatch('success', 'Record deleted successfully');
        } catch (\Exception $e) {
            $this->dispatch('error', 'Failed to delete record: ' . $e->getMessage());
        }
    }

    public function resetForm()
    {
        $this->reset();
        // $this->items = [
        //     [
        //         'supply_id' => null,
        //         'quantity' => null,
        //         'unit' => null, // ← new: satuan yang dipilih user
        //         'price_per_unit' => null,
        //         'available_units' => [], // ← new: daftar satuan berdasarkan supply
        //     ],
        // ];
    }
}

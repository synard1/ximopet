<?php

namespace App\Livewire\OVK;

use App\Models\OVKRecord;
use App\Models\Supply;
use App\Models\Farm;
use App\Models\Kandang;
use App\Models\Unit;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Edit extends Component
{
    public OVKRecord $ovkRecord;
    public $supply_id;
    public $farm_id;
    public $coop_id;
    public $quantity;
    public $unit_id;
    public $usage_date;
    public $notes;

    protected $rules = [
        'supply_id' => 'required|exists:supplies,id',
        'farm_id' => 'required|exists:farms,id',
        'coop_id' => 'required|exists:kandangs,id',
        'quantity' => 'required|numeric|min:0',
        'unit_id' => 'required|exists:units,id',
        'usage_date' => 'required|date',
        'notes' => 'nullable|string',
    ];

    public function mount(OVKRecord $ovkRecord)
    {
        $this->ovkRecord = $ovkRecord;
        $this->supply_id = $ovkRecord->supply_id;
        $this->farm_id = $ovkRecord->farm_id;
        $this->coop_id = $ovkRecord->coop_id;
        $this->quantity = $ovkRecord->quantity;
        $this->unit_id = $ovkRecord->unit_id;
        $this->usage_date = $ovkRecord->usage_date->format('Y-m-d');
        $this->notes = $ovkRecord->notes;
    }

    public function render()
    {
        $supplies = Supply::whereHas('category', function ($query) {
            $query->where('name', 'OVK');
        })->get();

        $farms = Farm::all();
        $kandangs = Kandang::all();
        $units = Unit::all();

        return view('livewire.ovk.edit', [
            'supplies' => $supplies,
            'farms' => $farms,
            'kandangs' => $kandangs,
            'units' => $units,
        ]);
    }

    public function update()
    {
        $this->validate();

        try {
            $this->ovkRecord->update([
                'supply_id' => $this->supply_id,
                'farm_id' => $this->farm_id,
                'coop_id' => $this->coop_id,
                'quantity' => $this->quantity,
                'unit_id' => $this->unit_id,
                'usage_date' => $this->usage_date,
                'notes' => $this->notes,
                'updated_by' => Auth::id(),
            ]);

            session()->flash('success', 'OVK record updated successfully');
            return redirect()->route('ovk-records.index');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update OVK record: ' . $e->getMessage());
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\OVKRecord;
use App\Models\Supply;
use App\Models\Farm;
use App\Models\Kandang;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OVKRecordController extends Controller
{
    public function index()
    {
        $records = OVKRecord::with(['supply', 'farm', 'kandang', 'unit'])
            ->latest()
            ->paginate(10);

        return view('pages.ovk-records.index', compact('records'));
    }

    public function create()
    {
        $supplies = Supply::whereHas('category', function ($query) {
            $query->where('name', 'OVK');
        })->get();

        $farms = Farm::all();
        $kandangs = Kandang::all();
        $units = Unit::all();

        return view('ovk-records.create', compact('supplies', 'farms', 'kandangs', 'units'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supply_id' => 'required|exists:supplies,id',
            'farm_id' => 'required|exists:farms,id',
            'kandang_id' => 'required|exists:kandangs,id',
            'quantity' => 'required|numeric|min:0',
            'unit_id' => 'required|exists:units,id',
            'usage_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $record = OVKRecord::create([
                ...$validated,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            return redirect()
                ->route('ovk-records.index')
                ->with('success', 'OVK record created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Failed to create OVK record: ' . $e->getMessage());
        }
    }

    public function edit(OVKRecord $ovkRecord)
    {
        $supplies = Supply::whereHas('category', function ($query) {
            $query->where('name', 'OVK');
        })->get();

        $farms = Farm::all();
        $kandangs = Kandang::all();
        $units = Unit::all();

        return view('ovk-records.edit', compact('ovkRecord', 'supplies', 'farms', 'kandangs', 'units'));
    }

    public function update(Request $request, OVKRecord $ovkRecord)
    {
        $validated = $request->validate([
            'supply_id' => 'required|exists:supplies,id',
            'farm_id' => 'required|exists:farms,id',
            'kandang_id' => 'required|exists:kandangs,id',
            'quantity' => 'required|numeric|min:0',
            'unit_id' => 'required|exists:units,id',
            'usage_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $ovkRecord->update([
                ...$validated,
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            return redirect()
                ->route('ovk-records.index')
                ->with('success', 'OVK record updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Failed to update OVK record: ' . $e->getMessage());
        }
    }

    public function destroy(OVKRecord $ovkRecord)
    {
        try {
            DB::beginTransaction();

            $ovkRecord->delete();

            DB::commit();

            return redirect()
                ->route('ovk-records.index')
                ->with('success', 'OVK record deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->with('error', 'Failed to delete OVK record: ' . $e->getMessage());
        }
    }
}

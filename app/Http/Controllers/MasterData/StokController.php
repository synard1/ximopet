<?php

namespace App\Http\Controllers\MasterData;

use App\Models\Item;
use App\Models\Farm;
use App\Models\CurrentStock;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\DataTables\StoksDataTable;
use App\DataTables\StoksPakanDataTable;
use App\DataTables\StoksOvkDataTable;

class StokController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(StoksDataTable $dataTable)
    {
        addVendors(['datatables']);

        addJavascriptFile('assets/js/custom/fetch-data.js');

        // $farms = CurrentStock::where('status', 'Aktif')->get(['id', 'nama']);
        $farms = Farm::whereHas('inventoryLocations.currentStocks', function ($query) {
            $query->where('current_stocks.status', 'Aktif');
        })
        ->select('id', 'nama')
        ->distinct()
        ->get();

        return $dataTable->render('pages/masterdata.stok.index',compact('farms'));
    }

    public function stockPakan(StoksPakanDataTable $dataTable)
    {
        addJavascriptFile('assets/js/custom/fetch-data.js');
        return $dataTable->render('pages/masterdata.stok.index_pakan');
    }

    public function stockOvk(StoksOvkDataTable $dataTable)
    {
        addJavascriptFile('assets/js/custom/fetch-data.js');
        return $dataTable->render('pages/masterdata.stok.index_ovk');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Stok $stok)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Stok $stok)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Stok $stok)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Stok $stok)
    {
        //
    }
}

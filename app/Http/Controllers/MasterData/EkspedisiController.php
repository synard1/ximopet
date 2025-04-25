<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;

use App\Models\Ekspedisi;
use Illuminate\Http\Request;
use App\DataTables\EkspedisiDataTable;

class EkspedisiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(EkspedisiDataTable $dataTable)
    {
        // return view('pages/masterdata.supplier.list');

        addVendors(['datatables']);

        return $dataTable->render('pages/masterdata.ekspedisi.list');
        // return $dataTable->render('test2');
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
    public function show(Ekspedisi $ekspedisi)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Ekspedisi $ekspedisi)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Ekspedisi $ekspedisi)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ekspedisi $ekspedisi)
    {
        //
    }
}

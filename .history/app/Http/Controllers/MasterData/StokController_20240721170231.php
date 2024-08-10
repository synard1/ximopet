<?php

namespace App\Http\Controllers\MasterData;

use App\Models\Stok;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\DataTables\StoksDataTable;

class StokController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(SuppliersDataTable $dataTable)
    {
        // return view('pages/masterdata.supplier.list');
        return $dataTable->render('pages/masterdata.supplier.list');
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

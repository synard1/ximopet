<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Rekanan;
use Illuminate\Http\Request;
use App\DataTables\SuppliersDataTable;

class RekananController extends Controller
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
    
    public function customerIndex(SuppliersDataTable $dataTable)
    {
        // return view('pages/masterdata.supplier.list');
        return $dataTable->render('pages/masterdata.customer.list');
        // return $dataTable->render('test2');
    }

    // public function index(SuppliersDataTable $dataTable)
    // {
    //     return $dataTable->render('pages/masterdata.supplier.list');
    // }

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
    public function show(Rekanan $rekanan)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Rekanan $rekanan)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Rekanan $rekanan)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Rekanan $rekanan)
    {
        //
    }
}

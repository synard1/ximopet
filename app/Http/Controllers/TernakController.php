<?php

namespace App\Http\Controllers;

use App\Models\Ternak;
use Illuminate\Http\Request;
use App\DataTables\TernakDataTable;

class TernakController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(TernakDataTable $dataTable)
    {
        // $data = Ternak::all();
        // $dataTable->data = $data;
        // $dataTable->setup();
        return $dataTable->render('pages.masterdata.ternak.list');
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
    public function show(Ternak $ternak)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Ternak $ternak)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Ternak $ternak)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ternak $ternak)
    {
        //
    }

    public function kematianTernakIndex(TernakDataTable $dataTable)
    {
        $data = Ternak::all();
        $dataTable->data = $data;
        $dataTable->setup();
        return $dataTable->render('pages.transaksi.kematian-ternak.index');
    }
}

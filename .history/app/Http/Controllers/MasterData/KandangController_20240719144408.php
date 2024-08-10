<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;

use App\Models\Kandang;
use Illuminate\Http\Request;
use App\DataTables\KandangsDataTable;

class KandangController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(KandangsDataTable $dataTable)
    {
        return $dataTable->render('pages/masterdata.kandang.list');
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
    public function show(Kandang $kandang)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Kandang $kandang)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Kandang $kandang)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Kandang $kandang)
    {
        //
    }
}

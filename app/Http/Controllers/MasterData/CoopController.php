<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Coop;
use Illuminate\Http\Request;
use App\DataTables\CoopsDataTable;

class CoopController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(CoopsDataTable $dataTable)
    {
        // $coops = Coop::all();
        // return view('pages.masterdata.coop.index', compact('coops'));
        addVendors(['datatables']);

        return $dataTable->render('pages.masterdata.coop.index');
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
    public function show(Coop $coop)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Coop $coop)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Coop $coop)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Coop $coop)
    {
        //
    }
}

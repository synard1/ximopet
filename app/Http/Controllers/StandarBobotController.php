<?php

namespace App\Http\Controllers;

use App\Models\StandarBobot;
use Illuminate\Http\Request;
use App\DataTables\StandarBobotDataTable;

class StandarBobotController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(StandarBobotDataTable $dataTable)
    {
        // return view('pages/masterdata.standar-bobot.list');

        addVendors(['datatables']);

        return $dataTable->render('pages.masterdata.standar-bobot.list');
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
    public function show(StandarBobot $standarBobot)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(StandarBobot $standarBobot)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, StandarBobot $standarBobot)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StandarBobot $standarBobot)
    {
        //
    }
}

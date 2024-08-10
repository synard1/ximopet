<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use Illuminate\Http\Request;
use App\DataTables\FarmsDataTable;
use App\DataTables\UsersDataTable;
use App\Models\Kandang;

class FarmController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // public function index(FarmsDataTable $dataTable, UsersDataTable $kandangDataTable)
    // {
    //     $availableFarms = Farm::where('status', 'Aktif')->get();
    //     return $dataTable->render('pages/masterdata.farm.list', ['availableFarms' => $availableFarms]);
    // }

    public function index(FarmsDataTable $dataTable, UsersDataTable $kandangDataTable)
{
    // Ensure both DataTables are rendered and processed.
    $dataTable->render();
    $kandangDataTable->render();

    // Prepare data for the view.
    $viewData = [
        'availableFarms'  => Farm::where('status', 'Aktif')->get(),
        'farmsData'       => $dataTable->getData(), 
        'kandangData'     => $kandangDataTable->getData(), 
    ];

    // Return the view with the necessary data.
    return view('pages/masterdata.farm.list', $viewData);
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
    public function show(Farm $farm)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Farm $farm)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Farm $farm)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Farm $farm)
    {
        //
    }
}

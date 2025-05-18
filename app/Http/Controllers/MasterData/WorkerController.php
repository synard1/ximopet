<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Worker;
use Illuminate\Http\Request;
use App\DataTables\WorkersDataTable;

class WorkerController extends Controller
{
    public function index(WorkersDataTable $dataTable)
    {
        addVendors(['datatables']);

        return $dataTable->render('pages.masterdata.worker.index');
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
    public function show(Worker $worker)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Worker $worker)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Worker $worker)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Worker $worker)
    {
        //
    }
}

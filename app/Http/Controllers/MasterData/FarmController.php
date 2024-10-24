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
    public function index(FarmsDataTable $dataTable, UsersDataTable $kandangDataTable)
    {
        $availableFarms = Farm::where('status', 'Aktif')->get();
        return $dataTable->render('pages/masterdata.farm.list', ['availableFarms' => $availableFarms]);
    }

//     public function index(FarmsDataTable $dataTable, UsersDataTable $kandangDataTable)
// {
//     $availableFarms = Farm::where('status', 'Aktif')->get();

//     // Render both tables in the same view
//     return view('pages.masterdata.farm.list', [
//         'availableFarms' => $availableFarms,
//         'dataTable' => $dataTable->html(),
//         'kandangDataTable' => $kandangDataTable->html()
//     ]);
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

    public function getDataAjax(Request $request)
    {
        $roles = $request->get('roles');
        $type = $request->get('type');

        if($type == 'list'){
            if($roles == 'Operator'){
                // Fetch existing farm IDs associated with the selected roles and type
                $farmIds = auth()->user()->farmOperators()->pluck('farm_id')->toArray();

                // Fetch operators not associated with the selected farm
                $farms = Farm::whereIn('id', $farmIds)->get(['id', 'nama']);

                $result = ['farms' => $farms];

                return response()->json($result);
            }
        }

        

        
        // return response()->json(['operators' => $operators]);
    }
}

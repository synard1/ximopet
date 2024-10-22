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

    public function getKandangs(Request $request)
    {
        $type = $request->type;
        $status = $request->status;

        $result = Kandang::where('status', $status)->get(['id','kode','nama','kapasitas','jumlah']);

        // dd($request->all());


        return response()->json($result);
    }

    public function getDataAjax(Request $request)
    {
        $roles = $request->get('roles');
        $type = $request->get('type');
        $farm_id = $request->get('farm_id');

        if($type == 'list'){
            if($roles == 'Operator'){
                // Fetch existing farm IDs associated with the selected roles and type
                $farmIds = auth()->user()->farmOperators()->pluck('farm_id')->toArray();

                // Fetch kandangs associated with the selected farm and status 'Digunakan'
                $kandangs = Kandang::where('master_kandangs.farm_id', $farm_id)
                            ->where('master_kandangs.status', 'Digunakan')
                            ->leftJoin('kelompok_ternak', 'master_kandangs.id', '=', 'kelompok_ternak.kandang_id')
                            ->select('master_kandangs.id', 'master_kandangs.nama', 'kelompok_ternak.name as kelompok_ternak_name', 'kelompok_ternak.start_date')
                            ->get();

                $result = [
                    'kandangs' => $kandangs,
                    'oldestDate' => $kandangs->min('start_date')
                ];

                return response()->json($result);
            }
        }
    }
}

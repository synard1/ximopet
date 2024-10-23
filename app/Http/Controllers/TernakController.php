<?php

namespace App\Http\Controllers;

use App\Models\Ternak;
use Illuminate\Http\Request;
use App\DataTables\TernakDataTable;
use App\DataTables\KematianTernakDataTable;

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

    public function kematianTernakIndex(KematianTernakDataTable $dataTable)
    {
        // $data = Ternak::all();
        // $dataTable->data = $data;
        // $dataTable->setup();
        return $dataTable->render('pages.transaksi.kematian-ternak.list');
    }

    public function getDataAjax(Request $request)
    {
        $roles = $request->get('roles');
        $task = $request->get('task');
        $type = $request->get('type');
        $jenis = $request->get('jenis');
        $id = $request->get('id');


        if($type == 'detail' || $type == 'Detail'){
            if($roles == 'Operator'){
                // Fetch ternak details based on the provided $id
                $result = Ternak::where('id', $id)
                    ->with(['jenisTernak','kandang', 'kematianTernak' => function($query) {
                        $query->orderBy('tanggal', 'asc');
                    }])
                    ->first();

                if (!$result) {
                    return response()->json(['error' => 'Ternak not found'], 404);
                }

                // dd($result);

                // Calculate total jumlah_mati and berat_mati from kematianTernak relation
                // $totalJumlahMati = $result->kematianTernak->sum('jumlah');
                // $totalBeratMati = $result->kematianTernak->sum('berat');

                // Map kematian ternak data chronologically
                $kematianData = $result->kematianTernak->map(function($item) use($result) {

                    // dd($item);
                    return [
                        'id' => $result->id,
                        'tanggal' => $item->tanggal,
                        // 'item_name' => $item->name,
                        // 'tanggal' => $item->tanggal,
                        'stok_awal' => $item->stok_awal,
                        'jumlah_mati' => $item->quantity,
                        'stok_akhir' => $item->stok_akhir,
                        'berat_mati' => $item->total_berat,
                        'penyebab' => $item->penyebab,
                        // 'keterangan' => $item->keterangan
                    ];
                });

                // // Format the result as needed
                // $formattedResult = [
                //     'id' => $result->id,
                //     'jenis_barang' => $result->jenisTernak->name,
                //     'item_name' => $result->name,
                //     'stok_awal' => $result->jumlah,
                //     'jumlah_mati' => $totalJumlahMati,
                //     'berat_mati' => $totalBeratMati,
                //     'stok_akhir' => $result->jumlah - $totalJumlahMati,
                //     'kandang' => $result->kandang->nama,
                //     'kematian_data' => $kematianData
                // ];

                // $result = [$formattedResult];

                return response()->json($kematianData);
            }elseif($roles == 'Supervisor'){

                // $result = Kandang::where('status', $status)->get(['id','kode','nama','kapasitas','jumlah']);

                // return response()->json($result);
            }
        }
    }
}

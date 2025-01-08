<?php

namespace App\Http\Controllers;

use App\Models\Ternak;
use Illuminate\Http\Request;
use App\DataTables\TernakDataTable;
use App\DataTables\ternakAfkirDataTable;
use App\DataTables\ternakJualDataTable;
use App\DataTables\KematianTernakDataTable;
use App\Models\KematianTernak;
use App\Models\TernakAfkir;
use App\Models\TernakJual;
use App\Models\TransaksiHarian;
use App\Models\TransaksiHarianDetail;
use App\Models\CurrentTernak;
use App\Models\TransaksiJual;
use App\Models\KelompokTernak;
use Carbon\Carbon;

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
        addVendors(['datatables']);

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

    public function ternakAfkirIndex(ternakAfkirDataTable $dataTable)
    {
        // $data = Ternak::all();
        // $dataTable->data = $data;
        // $dataTable->setup();
        return $dataTable->render('pages.ternak.afkir.list');
    }

    public function ternakMatiIndex(KematianTernakDataTable $dataTable)
    {
        // $data = Ternak::all();
        // $dataTable->data = $data;
        // $dataTable->setup();
        return $dataTable->render('pages.transaksi.kematian-ternak.list');
    }

    public function ternakJualIndex(ternakJualDataTable $dataTable)
    {
        // $data = Ternak::all();
        // $dataTable->data = $data;
        // $dataTable->setup();
        return $dataTable->render('pages.ternak.jual.list');
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

    private function getCategoriesFromItemCategory()
    {
        return \App\Models\ItemCategory::where('status', 'Aktif')
            ->whereIn('name', ['Pakan', 'Obat', 'Vitamin'])
            ->pluck('name')
            ->toArray();
    }

    public function showTernakDetails($id)
    {
        $kelompokTernak = KelompokTernak::findOrFail($id);
        $startDate = $kelompokTernak->start_date; // Get data for the last 30 days
        
        // Get the last transaction date
        $lastTransactionDate = TransaksiHarian::where('kelompok_ternak_id', $kelompokTernak->id)
        ->latest('tanggal')
        ->value('tanggal');

        if($kelompokTernak->status == 'Aktif' || $kelompokTernak->status == 'Locked'){
            // If there's no transaction, use the current date
            // Otherwise, use the last transaction date plus 1 days
            $endDate = $lastTransactionDate 
            ? Carbon::parse($lastTransactionDate)->addDays(1)->format('Y-m-d')
            : Carbon::now()->format('Y-m-d');

            // Ensure that the end date is not before the start date
            $endDate = max(Carbon::parse($endDate), Carbon::parse($startDate)->addDays(1));
        }else{
            $endDate = $kelompokTernak->end_date;

        }


        $dailyData = $this->getDailyData($kelompokTernak, $startDate, $endDate);

        $result = ['result' => $dailyData,
                'nama' => $kelompokTernak->name];

        // return view('pages.masterdata.ternak._detail_modal', compact('kelompokTernak', 'dailyData'));
        return response()->json($result);

        // return view('pages.masterdata.ternak._detail_modal', compact('dailyData'));

        // dd($dailyData);

    }

    private function getDailyData(KelompokTernak $kelompokTernak, Carbon $startDate, Carbon $endDate)
    {
        $dateRange = $this->getDateRange($startDate, $endDate);

        $kematianData = $this->getKematianData($kelompokTernak, $startDate, $endDate);
        $afkirData = $this->getAfkirData($kelompokTernak, $startDate, $endDate);
        $penjualanData = $this->getPenjualanData($kelompokTernak, $startDate, $endDate);
        $pakanData = $this->getPakanData($kelompokTernak, $startDate, $endDate);
        $ovkData = $this->getOvkData($kelompokTernak, $startDate, $endDate);
        // $obatData = $this->getObatData($kelompokTernak, $startDate, $endDate);
        // $vitaminData = $this->getVitaminData($kelompokTernak, $startDate, $endDate);

        // dd($pakanData);
        // dd($pakanData['2024-10-29'][0]['nama']);

        $dailyData = [];
        foreach ($dateRange as $date) {
            $formattedDate = Carbon::parse($date)->format('Y-m-d');
            $dailyData[$formattedDate] = [
                'tanggal' => $date,
                'stok_awal' => $kematianData[$date]['stok_awal'] ?? $afkirData[$date]['stok_awal'] ?? $penjualanData[$date]['stok_awal'] ?? 0,
                'ternak_mati' => $kematianData[$date]['quantity'] ?? 0,
                'ternak_afkir' => $afkirData[$date]['jumlah'] ?? 0,
                'ternak_terjual' => $penjualanData[$date]['quantity'] ?? 0,
                'stok_akhir' => $kematianData[$date]['stok_akhir'] ?? $afkirData[$date]['stok_akhir'] ?? $penjualanData[$date]['stok_akhir'] ?? 0,
                'pakan_nama' => $pakanData[$date][0]['nama'] ?? '',
                'pakan_quantity' => $pakanData[$date][0]['quantity'] ?? 0,
                'ovk_harian' => $ovkData[$date] ?? 0,
            ];
        }


        // dd($dailyData['2024-12-03']);

        return collect($dailyData)->sortByDesc('tanggal')->values();
    }

    private function getDateRange(Carbon $startDate, Carbon $endDate)
    {
        return $startDate->range($endDate)->map(function ($date) {
            return $date->format('Y-m-d');
        });
    }

    private function getKematianData(KelompokTernak $kelompokTernak, Carbon $startDate, Carbon $endDate)
    {
        return $kelompokTernak->kematianTernak()
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->get()
            ->keyBy(function ($item) {
                return Carbon::parse($item->tanggal)->format('Y-m-d');
            })
            ->toArray();
    }

    private function getAfkirData(KelompokTernak $kelompokTernak, Carbon $startDate, Carbon $endDate)
    {
        return $kelompokTernak->ternakAfkir()
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->get()
            ->keyBy(function ($item) {
                return Carbon::parse($item->tanggal)->format('Y-m-d');
            })
            ->toArray();
    }

    private function getPenjualanData(KelompokTernak $kelompokTernak, Carbon $startDate, Carbon $endDate)
    {
        return $kelompokTernak->penjualanTernaks()
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->get()
            ->keyBy(function ($item) {
                return Carbon::parse($item->tanggal)->format('Y-m-d');
            })
            ->toArray();
    }

    // private function getPakanData(KelompokTernak $kelompokTernak, Carbon $startDate, Carbon $endDate)
    // {
    //     return $kelompokTernak->transaksiHarian()
    //         ->whereBetween('tanggal', [$startDate, $endDate])
    //         ->whereHas('details.item.category', function ($query) {
    //             $query->where('name', 'Pakan');
    //         })
    //         ->get()
    //         ->map(function ($transaksi) {
    //             return [
    //                 'tanggal' => $transaksi->tanggal->format('Y-m-d'),
    //                 'nama' => $transaksi->details->item,
    //                 'quantity' => $transaksi->details->where('item.category.name', 'Pakan')->sum('quantity')
    //             ];
    //         })
    //         ->keyBy('tanggal')
    //         // ->map(function ($item) {
    //         //     return $item['quantity'];
    //         // })
    //         ->toArray();
    // }
    private function getPakanData(KelompokTernak $kelompokTernak, Carbon $startDate, Carbon $endDate)
    {
        return $kelompokTernak->transaksiHarian()
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->whereHas('details.item.category', function ($query) {
                $query->where('name', 'Pakan');
            })
            ->with(['details.item' => function ($query) {
                $query->where('category_id', function ($subQuery) {
                    $subQuery->select('id')
                        ->from('item_categories')
                        ->where('name', 'Pakan');
                });
            }])
            ->get()
            ->flatMap(function ($transaksi) {
                return $transaksi->details->map(function ($detail) use ($transaksi) {
                    return [
                        'tanggal' => $transaksi->tanggal->format('Y-m-d'),
                        'nama_pakan' => $detail->item->name,
                        'quantity' => $detail->quantity
                    ];
                });
            })
            ->groupBy('tanggal')
            ->map(function ($group) {
                return $group->groupBy('nama_pakan')->map(function ($items) {
                    return [
                        'nama' => $items->first()['nama_pakan'],
                        'quantity' => $items->sum('quantity')
                    ];
                })->values();
            })
            ->toArray();
    }

    private function getOvkData(KelompokTernak $kelompokTernak, Carbon $startDate, Carbon $endDate)
    {
        return $kelompokTernak->transaksiHarian()
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->whereHas('details.item.category', function ($query) {
                $query->where('name', 'OVK');
            })
            ->get()
            ->map(function ($transaksi) {
                return [
                    'tanggal' => $transaksi->tanggal->format('Y-m-d'),
                    'quantity' => $transaksi->details->where('item.category.name', 'OVK')->sum('quantity')
                ];
            })
            ->keyBy('tanggal')
            ->map(function ($item) {
                return $item['quantity'];
            })
            ->toArray();
    }


    public function showDetail($id)
    {
        $ternak = Ternak::findOrFail($id);
        $mati = KematianTernak::where('kelompok_ternak_id', $id)->sum('quantity');
        $afkir = TernakAfkir::where('kelompok_ternak_id', $id)->sum('jumlah');
        $terjual = TransaksiJual::where('kelompok_ternak_id', $id)->sum('jumlah');
        $sisa = CurrentTernak::where('kelompok_ternak_id', $id)->first()->quantity ?? 0;
        
        // You'll need to implement these methods based on your database structure
        $pakan = $this->getPakanForTernak($id);
        $obat = $this->getObatForTernak($id);
        $vitamin = $this->getVitaminForTernak($id);

        return view('pages.masterdata.ternak._detail_content', compact('ternak', 'mati', 'afkir', 'terjual', 'sisa', 'pakan', 'obat', 'vitamin'));

    }

    public function storeBonusData($kelompokTernakId, $bonusData)
    {
        $kelompokTernak = KelompokTernak::findOrFail($kelompokTernakId);

        // Get existing data or initialize an empty array
        $existingData = json_decode($kelompokTernak->data, true) ?? [];

        // Merge the new bonus data with existing data
        $existingData['bonus'] = $bonusData;

        // Update the data column
        $kelompokTernak->update([
            'data' => json_encode($existingData)
        ]);

        return response()->json(['message' => 'Bonus data stored successfully']);
    }

    public function addBonus(Request $request)
    {
        $bonusData = $request->validate([
            'jumlah' => 'required|numeric',
            'keterangan' => '',
            'tanggal' => 'required',
            // Add any other validation rules for your bonus data
        ]);

        $kelompokTernakId = $request->input('ternak_id');

        return $this->storeBonusData($kelompokTernakId, $bonusData);
    }

    public function getBonusData($ternakId)
    {
        try {
            $ternak = KelompokTernak::findOrFail($ternakId);
            
            // Retrieve the entire data column
            $allData = $ternak->data ? json_decode($ternak->data, true) : [];
            
            // Extract the bonus data from the 'bonus' key
            $bonusData = $allData['bonus'] ?? null;

            return response()->json([
                'success' => true,
                'bonus' => $bonusData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving bonus data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getDetailReportData($ternakId)
    {
        try {
            $ternak = KelompokTernak::findOrFail($ternakId);
            
            // Retrieve the entire data column
            $allData = $ternak->data ? json_decode($ternak->data, true) : [];
            
            // Extract the bonus data from the 'bonus' key
            $bonusData = $allData['bonus'] ?? null;
            $administrasiData = $allData['administrasi'] ?? null;

            return response()->json([
                'success' => true,
                'bonus' => $bonusData,
                'administrasi' => $administrasiData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving detail report data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function storeTanggalSurat(Request $request, $ternakId)
    {
        try {
            $request->validate([
                'tanggal_surat' => 'required|date',
            ]);

            $kelompokTernak = KelompokTernak::findOrFail($ternakId);

            // Get existing data or initialize an empty array
            $existingData = json_decode($kelompokTernak->data, true) ?? [];

            // Add or update the tanggal_surat
            $existingData['tanggal_surat'] = $request->tanggal_surat;

            // Update the data column
            $kelompokTernak->update([
                'data' => json_encode($existingData)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tanggal surat berhasil disimpan',
                'tanggal_surat' => $request->tanggal_surat
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error menyimpan tanggal surat: ' . $e->getMessage()
            ], 500);
        }
    }

    public function addAdministrasi(Request $request)
    {
        $administrasiData = $request->validate([
            'persetujuan_nama' => 'required',
            'persetujuan_jabatan' => 'required',
            'verifikator_nama' => 'required',
            'verifikator_jabatan' => 'required',
            'tanggal_laporan' => '',
            // Add any other validation rules for your bonus data
        ]);

        if($request->input('tanggal_laporan') == null){
            $administrasiData['tanggal_laporan'] = Carbon::now()->format('Y-m-d');
        }

        $kelompokTernakId = $request->input('ternak_id');

        return $this->storeAdministrasiData($kelompokTernakId, $administrasiData);
    }

    public function storeAdministrasiData($kelompokTernakId, $administrasiData)
    {
        $kelompokTernak = KelompokTernak::findOrFail($kelompokTernakId);

        // Get existing data or initialize an empty array
        $existingData = json_decode($kelompokTernak->data, true) ?? [];

        // Merge the new bonus data with existing data
        $existingData['administrasi'] = $administrasiData;

        // Update the data column
        $kelompokTernak->update([
            'data' => json_encode($existingData)
        ]);

        return response()->json(['message' => 'Administrasi data stored successfully']);
    }

}

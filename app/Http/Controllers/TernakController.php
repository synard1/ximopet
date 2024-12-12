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

        // $startDate = Carbon::now()->subDays(30); // Get data for the last 30 days
        $startDate = $kelompokTernak->start_date; // Get data for the last 30 days
        $endDate = Carbon::now();

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
        $obatData = $this->getObatData($kelompokTernak, $startDate, $endDate);
        $vitaminData = $this->getVitaminData($kelompokTernak, $startDate, $endDate);

        // dd($pakanData);

        $dailyData = [];
        foreach ($dateRange as $date) {
            $formattedDate = Carbon::parse($date)->format('Y-m-d');
            $dailyData[$formattedDate] = [
                'tanggal' => $date,
                'stok_awal' => $kematianData[$date]['stok_awal'] ?? $afkirData[$date]['stok_awal'] ?? $penjualanData[$date]['stok_awal'] ?? 0,
                'ternak_mati' => $kematianData[$date]['quantity'] ?? 0,
                'ternak_afkir' => $afkirData[$date]['jumlah'] ?? 0,
                'ternak_terjual' => $penjualanData[$date]['quantity'] ?? 0,
                // 'stok_akhir' => $kematianData[$date]['stok_akhir'] ?? $afkirData[$date]['stok_akhir'] ?? $penjualanData[$date]['stok_akhir'] ?? 0,
                'stok_akhir' => $kematianData[$date]['stok_akhir'] ?? $afkirData[$date]['stok_akhir'] ?? $penjualanData[$date]['stok_akhir'] ?? 0,
                'pakan_harian' => $pakanData[$date] ?? 0,
                'obat_harian' => $obatData[$date] ?? 0,
                'vitamin_harian' => $vitaminData[$date] ?? 0,
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

    private function getPakanData(KelompokTernak $kelompokTernak, Carbon $startDate, Carbon $endDate)
    {
        return $kelompokTernak->transaksiHarian()
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->whereHas('details.item.category', function ($query) {
                $query->where('name', 'Pakan');
            })
            ->get()
            ->map(function ($transaksi) {
                return [
                    'tanggal' => $transaksi->tanggal->format('Y-m-d'),
                    'quantity' => $transaksi->details->where('item.category.name', 'Pakan')->sum('quantity')
                ];
            })
            ->keyBy('tanggal')
            ->map(function ($item) {
                return $item['quantity'];
            })
            ->toArray();
    }

    private function getObatData(KelompokTernak $kelompokTernak, Carbon $startDate, Carbon $endDate)
    {
        return $kelompokTernak->transaksiHarian()
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->whereHas('details.item.category', function ($query) {
                $query->where('name', 'Obat');
            })
            ->get()
            ->map(function ($transaksi) {
                return [
                    'tanggal' => $transaksi->tanggal->format('Y-m-d'),
                    'quantity' => $transaksi->details->where('item.category.name', 'Obat')->sum('quantity')
                ];
            })
            ->keyBy('tanggal')
            ->map(function ($item) {
                return $item['quantity'];
            })
            ->toArray();
    }

    private function getVitaminData(KelompokTernak $kelompokTernak, Carbon $startDate, Carbon $endDate)
    {
        return $kelompokTernak->transaksiHarian()
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->whereHas('details.item.category', function ($query) {
                $query->where('name', 'Vitamin');
            })
            ->get()
            ->map(function ($transaksi) {
                return [
                    'tanggal' => $transaksi->tanggal->format('Y-m-d'),
                    'quantity' => $transaksi->details->where('item.category.name', 'Vitamin')->sum('quantity')
                ];
            })
            ->keyBy('tanggal')
            ->map(function ($item) {
                return $item['quantity'];
            })
            ->toArray();
    }

    // private function getPakanForTernak($id)
    // {
    //     $categories = $this->getCategoriesFromItemCategory();
    //     return $this->getItemQuantityForTernak($id, 'Pakan', $categories);
    // }

    // private function getObatForTernak($id)
    // {
    //     $categories = $this->getCategoriesFromItemCategory();
    //     return $this->getItemQuantityForTernak($id, 'Obat', $categories);
    // }

    // private function getVitaminForTernak($id)
    // {
    //     $categories = $this->getCategoriesFromItemCategory();
    //     return $this->getItemQuantityForTernak($id, 'Vitamin', $categories);
    // }

    // private function getItemQuantityForTernak($id, $itemType, $categories)
    // {
    //     if (!in_array($itemType, $categories)) {
    //         return 0;
    //     }

    //     return TransaksiHarianDetail::whereHas('transaksiHarian', function ($query) use ($id) {
    //         $query->where('kelompok_ternak_id', $id);
    //     })
    //     ->whereHas('item.category', function ($query) use ($itemType) {
    //         $query->where('name', $itemType);
    //     })
    //     ->sum('quantity');
    // }

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
}

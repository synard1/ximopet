<?php

namespace App\Http\Controllers;

use App\Models\CurrentStock;
use App\Models\CurrentTernak;
use App\Models\User;
use App\Models\Farm;
use App\Models\Rekanan;
use App\Models\Kandang;
use App\Models\TransaksiJual;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        addVendors(['amcharts', 'amcharts-maps', 'amcharts-stock']);
        addJavascriptFile('assets/js/widgets.bundle.js');

        $user = User::whereDoesntHave('roles', function($query) {
            $query->where('name', 'SuperAdmin');
        })->get();
        $farm = Farm::whereIn('status',['Aktif','Digunakan']);
        $kandang = Kandang::whereIn('status',['Aktif','Digunakan']);
        $ternak = CurrentTernak::where('status','Aktif');
        // get current stocks with category Pakan from relation with item_id category Pakan
        $currentStocks = CurrentStock::whereHas('item', function ($query) {
            $query->where('category_id', '9db8c901-b60a-4611-b5c1-01f264e1187a');
        })
        ->where('quantity','>',0)
        ->with('item')
        ->get();

        // dd($currentStocks);

        if (auth()->user()->hasRole('Manager')) {
            $totalSalesCount = TransaksiJual::select('farms.name as farm_nama', DB::raw('SUM(transaksi_jual_details.qty) as total_sales'))
                ->join('transaksi_jual_details', 'transaksi_jual.id', '=', 'transaksi_jual_details.transaksi_jual_id')
                ->join('farms', 'transaksi_jual_details.farm_id', '=', 'farms.id')
                ->where('transaksi_jual.status', 'OK')
                ->groupBy('transaksi_jual_details.farm_id', 'farms.name')
                ->get();
    
            // Transform the data to match the desired format
            $formattedChartData = $totalSalesCount->map(function ($item) {
                return [
                    'farm_nama' => $item->farm_nama,
                    'total_sales' => (float) $item->total_sales
                ];
            })->toArray();
        } else {
            $formattedChartData = [];
        }


        

        // dd($totalSalesCount);


        // if (auth()->user()->hasRole('Operator')) {
        //     $farmOperator = auth()->user()->farmOperators;
        //     if ($farmOperator) {
        //         $farmIds = $farmOperator->pluck('farm_id')->toArray();
        //         $farm = Farm::whereIn('id', $farmIds)->get();
        //     } else {
        //         $farm = collect(); // Empty collection if no farms are assigned
        //     }
        // } else {
        //     $farm = Farm::all();
        // }
        

        // if (auth()->user()->hasRole('Operator')) {
        //     $farmOperator = auth()->user()->farmOperators;
        //     if ($farmOperator) {
        //         $farmIds = $farmOperator->pluck('farm_id')->toArray();
        //         $kandang = Kandang::whereIn('farm_id', $farmIds)->get();
        //     } else {
        //         $kandang = collect(); // Empty collection if no farms are assigned
        //     }
        // } else {
        //     $kandang = Kandang::all();
        // }
        // $rekanan = Rekanan::all();
        $stock = \App\Models\TransaksiBeliDetail::where('jenis', 'Pembelian')
            ->where('jenis_barang', '!=', 'DOC')
            ->select('jenis_barang', 'sisa', 'konversi');

        // if (auth()->user()->hasRole('Operator')) {
        //     $farmOperator = auth()->user()->farmOperators;
        //     if ($farmOperator) {
        //         $farmIds = $farmOperator->pluck('farm_id')->toArray();
        //         $stock = $stock->whereHas('transaksi', function ($query) use ($farmIds) {
        //             $query->whereIn('farm_id', $farmIds);
        //         });
        //     }
        // }

        $stock = $stock->get()->sum(function ($item) {
            return $item->sisa / $item->konversi;
        });

        // dd($stock);

        $stockByType = \App\Models\TransaksiBeli::where('transaksi_beli.jenis', 'Stock')
            ->join('transaksi_beli_details', 'transaksi_beli.id', '=', 'transaksi_beli_details.transaksi_id')
            ->where('transaksi_beli_details.jenis', 'Pembelian')
            ->select('transaksi_beli_details.jenis_barang', 
                DB::raw('SUM(transaksi_beli_details.sisa / transaksi_beli_details.konversi) as total_sisa'));

        if (auth()->user()->hasRole('Operator')) {
            $farmOperator = auth()->user()->farmOperators;
            if ($farmOperator) {
                $farmIds = $farmOperator->pluck('farm_id')->toArray();
                $farm = $farm->whereIn('id', $farmIds)->get();
                $kandang = $kandang->whereIn('farm_id', $farmIds)->get();
                $ternak = $ternak->whereIn('farm_id', $farmIds)->get();

                // dd($ternak);
                $stockByType = $stockByType->whereIn('transaksi_beli.farm_id', $farmIds)
                    ->where('transaksi_beli_details.jenis', 'Pembelian');
            }
        }

        $stockByType = $stockByType->groupBy('jenis_barang')
            ->get();

        // $lastTransactions = \App\Models\TransaksiDetail::join('transaksis', 'transaksi_details.transaksi_id', '=', 'transaksis.id')
        //     ->join('farms', 'transaksis.farm_id', '=', 'farms.id')
        //     ->where('transaksis.jenis', 'Pembelian')
        //     ->whereNull('transaksis.deleted_at')
        //     ->select('transaksi_details.*', 'farms.name as farm_name', 'transaksis.created_at');

        // if (auth()->user()->hasRole('Operator')) {
        //     $farmOperator = auth()->user()->farmOperators;
        //     if ($farmOperator) {
        //         $farmIds = $farmOperator->pluck('farm_id')->toArray();
        //         $lastTransactions = $lastTransactions->whereIn('transaksis.farm_id', $farmIds);
        //     }
        // }

        // $lastTransactions = $lastTransactions
        //     ->orderBy('transaksis.created_at', 'desc')
        //     ->limit(5)
        //     ->get();

        // dd($stockByType);


        $user = $user ?? [];
        $farm = $farm ?? [];
        $kandang = $kandang ?? [];
        $ternak = $ternak ?? [];
        $currentStocks = $currentStocks ?? [];
        $rekanan = [];
        $stock = $stok ?? [];
        $stockByType = $stockByType ?? [];
        $lastTransactions = [];
        $lastTransactions = [];
        // $totalSalesCount = $totalSalesCount ?? [];
        // $chartData = json_encode($totalSalesCount ?? []);

        $chartData = $formattedChartData;




        return view('pages/dashboards.index', compact('user', 'farm', 'kandang', 'ternak','rekanan', 'currentStocks','stock', 'stockByType', 'lastTransactions','chartData'));
    }
}

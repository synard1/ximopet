<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Farm;
use App\Models\Rekanan;
use App\Models\Kandang;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // addVendors(['amcharts', 'amcharts-maps', 'amcharts-stock']);
        // $user = User::whereDoesntHave('roles', function($query) {
        //     $query->where('name', 'SuperAdmin');
        // })->get();

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
        // $stock = \App\Models\TransaksiDetail::where('jenis', 'Pembelian')
        //     ->where('jenis_barang', '!=', 'DOC')
        //     ->select('jenis_barang', 'sisa', 'konversi');

        // if (auth()->user()->hasRole('Operator')) {
        //     $farmOperator = auth()->user()->farmOperators;
        //     if ($farmOperator) {
        //         $farmIds = $farmOperator->pluck('farm_id')->toArray();
        //         $stock = $stock->whereHas('transaksi', function ($query) use ($farmIds) {
        //             $query->whereIn('farm_id', $farmIds);
        //         });
        //     }
        // }

        // $stock = $stock->get()->sum(function ($item) {
        //     return $item->sisa / $item->konversi;
        // });

        // $stockByType = \App\Models\Transaksi::where('transaksis.jenis', 'Pembelian')
        //     ->join('transaksi_details', 'transaksis.id', '=', 'transaksi_details.transaksi_id')
        //     ->where('transaksi_details.jenis', 'Pembelian')
        //     ->select('transaksi_details.jenis_barang', 
        //         DB::raw('SUM(transaksi_details.sisa / transaksi_details.konversi) as total_sisa'));

        // if (auth()->user()->hasRole('Operator')) {
        //     $farmOperator = auth()->user()->farmOperators;
        //     if ($farmOperator) {
        //         $farmIds = $farmOperator->pluck('farm_id')->toArray();
        //         $stockByType = $stockByType->whereIn('transaksis.farm_id', $farmIds)
        //             ->where('transaksi_details.jenis', 'Pembelian');
        //     }
        // }

        // $stockByType = $stockByType->groupBy('transaksi_details.jenis_barang')
        //     ->get();

        // $lastTransactions = \App\Models\TransaksiDetail::join('transaksis', 'transaksi_details.transaksi_id', '=', 'transaksis.id')
        //     ->join('master_farms', 'transaksis.farm_id', '=', 'master_farms.id')
        //     ->where('transaksis.jenis', 'Pembelian')
        //     ->whereNull('transaksis.deleted_at')
        //     ->select('transaksi_details.*', 'master_farms.nama as farm_name', 'transaksis.created_at');

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

            // dd($lastTransactions);


        $user =[];
        $farm =[];
        $kandang =[];
        $rekanan = [];
        $stock = [];
        $stockByType = [];
        $lastTransactions = [];


        return view('pages/dashboards.index', compact('user', 'farm', 'kandang', 'rekanan', 'stock', 'stockByType', 'lastTransactions'));
    }
}

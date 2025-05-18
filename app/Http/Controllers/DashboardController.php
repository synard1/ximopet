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
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index()
    {
        addVendors(['amcharts', 'amcharts-maps', 'amcharts-stock']);
        addJavascriptFile('assets/js/widgets.bundle.js');

        $user = auth()->user();
        $isOperator = $user->hasRole('Operator');
        $farmIds = $isOperator ? $user->farmOperators->pluck('farm_id')->toArray() : null;

        $userList = Cache::remember('dashboard:user_list', now()->addHours(2), function () {
            return User::whereDoesntHave('roles', fn($q) => $q->where('name', 'SuperAdmin'))->get();
        });

        $farm = Cache::remember('dashboard:farm_list_' . ($isOperator ? implode('-', $farmIds) : 'all'), now()->addMinutes(30), function () use ($farmIds) {
            return Farm::whereIn('status', ['Aktif', 'Digunakan'])
                ->when($farmIds, fn($q) => $q->whereIn('id', $farmIds))
                ->get();
        });

        $kandang = Cache::remember('dashboard:kandang_list_' . ($isOperator ? implode('-', $farmIds) : 'all'), now()->addMinutes(30), function () use ($farmIds) {
            return Kandang::whereIn('status', ['Aktif', 'Digunakan'])
                ->when($farmIds, fn($q) => $q->whereIn('farm_id', $farmIds))
                ->get();
        });

        $ternak = Cache::remember('dashboard:ternak_list_' . ($isOperator ? implode('-', $farmIds) : 'all'), now()->addMinutes(15), function () use ($farmIds) {
            return CurrentTernak::where('status', 'Aktif')
                ->when($farmIds, fn($q) => $q->whereIn('farm_id', $farmIds))
                ->get();
        });

        $currentStocks = Cache::remember('dashboard:current_stocks', now()->addMinutes(10), function () {
            return CurrentStock::whereHas('item', function ($query) {
                $query->where('category_id', '9db8c901-b60a-4611-b5c1-01f264e1187a');
            })
                ->where('quantity', '>', 0)
                ->with('item')
                ->get();
        });

        $stock = Cache::remember('dashboard:stock_sum', now()->addMinutes(5), function () {
            return \App\Models\TransaksiBeliDetail::where('jenis', 'Pembelian')
                ->where('jenis_barang', '!=', 'DOC')
                ->get()
                ->sum(fn($item) => $item->sisa / $item->konversi);
        });

        $stockByType = Cache::remember('dashboard:stock_by_type_' . ($isOperator ? implode('-', $farmIds) : 'all'), now()->addMinutes(10), function () use ($farmIds) {
            return \App\Models\TransaksiBeli::where('transaksi_beli.jenis', 'Stock')
                ->join('transaksi_beli_details', 'transaksi_beli.id', '=', 'transaksi_beli_details.transaksi_id')
                ->where('transaksi_beli_details.jenis', 'Pembelian')
                ->when($farmIds, fn($q) => $q->whereIn('transaksi_beli.farm_id', $farmIds))
                ->select(
                    'transaksi_beli_details.jenis_barang',
                    DB::raw('SUM(transaksi_beli_details.sisa / transaksi_beli_details.konversi) as total_sisa')
                )
                ->groupBy('jenis_barang')
                ->get();
        });

        $chartData = [];
        if ($user->hasRole('Manager')) {
            $chartData = Cache::remember('dashboard:chart_data', now()->addMinutes(15), function () {
                return TransaksiJual::select('farms.name as farm_nama', DB::raw('SUM(transaksi_jual_details.qty) as total_sales'))
                    ->join('transaksi_jual_details', 'transaksi_jual.id', '=', 'transaksi_jual_details.transaksi_jual_id')
                    ->join('farms', 'transaksi_jual_details.farm_id', '=', 'farms.id')
                    ->where('transaksi_jual.status', 'OK')
                    ->groupBy('transaksi_jual_details.farm_id', 'farms.name')
                    ->get()
                    ->map(fn($item) => [
                        'farm_nama' => $item->farm_nama,
                        'total_sales' => (float) $item->total_sales,
                    ])
                    ->toArray();
            });
        }

        return view('pages/dashboards.index', compact(
            'userList',
            'farm',
            'kandang',
            'ternak',
            'currentStocks',
            'stock',
            'stockByType',
            'chartData'
        ));
    }
}

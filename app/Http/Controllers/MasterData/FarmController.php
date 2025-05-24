<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\InventoryLocation;
use Illuminate\Http\Request;
use App\DataTables\FarmsDataTable;
use App\DataTables\UsersDataTable;
use App\Models\Kandang;
use App\Models\FarmOperator;
use App\Models\Transaksi;
use App\Models\TransaksiDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FarmController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(FarmsDataTable $dataTable, UsersDataTable $kandangDataTable)
    {
        addVendors(['datatables']);

        $availableFarms = Farm::where('status', 'Aktif')->get();
        return $dataTable->render('pages/masterdata.farm.index', ['availableFarms' => $availableFarms]);
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

    public function getDataAjax(Request $request)
    {
        $roles = $request->get('roles');
        $type = $request->get('type');
        $data = $request->get('data');

        if ($type == 'list') {
            if ($roles == 'Operator') {
                if ($data == 'stocks') {
                    return $this->farmStocks($request->get('farm_id'));
                } else {
                    $farmIds = auth()->user()->farmOperators()->pluck('farm_id')->toArray();
                    $farms = Farm::whereIn('id', $farmIds)->get(['id', 'nama']);
                    return response()->json(['farms' => $farms]);
                }
            } elseif ($roles == 'Supervisor') {
            }
        }
    }

    public function farmStocks($farmId)
    {
        $data = Transaksi::with(['transaksiDetail' => function ($query) {
            $query->whereNotIn('jenis_barang', ['DOC'])
                ->where('sisa', '>', 0);
        }])
            ->where('farm_id', $farmId)
            ->where('jenis', 'Pembelian')
            ->get()
            ->flatMap(function ($transaksi) {
                return $transaksi->transaksiDetail;
            })
            ->groupBy('item_id')
            ->map(function ($group) {
                return [
                    'item_id' => $group->first()->item_id,
                    'item_name' => $group->first()->item_name,
                    'total' => $group->sum('sisa')
                ];
            })
            ->values();

        $oldestDate = Transaksi::with(['transaksiDetail' => function ($query) {
            $query->where('sisa', '>', 0)
                ->whereNotIn('jenis_barang', ['DOC']);
        }])
            ->where('farm_id', $farmId)
            ->whereHas('transaksiDetail', function ($query) {
                $query->where('sisa', '>', 0)
                    ->whereNotIn('jenis_barang', ['DOC']);
            })
            ->min('tanggal');

        $kandangs = Kandang::where('farm_id', $farmId)->where('status', 'Digunakan')->get(['id', 'nama']);

        if ($data->isEmpty()) {
            return response()->json([
                'error' => 'No data found for the specified farm ID.'
            ], 404);
        } else {
            $result = [
                'stock' => $data,
                'parameter' => ['oldestDate' => $oldestDate],
                'kandangs' => $kandangs
            ];
            return response()->json($result);
        }
    }

    public function getOperator($farmId)
    {
        $operators = FarmOperator::with('user:id,name')
            ->where('farm_id', $farmId)
            ->get(['user_id', 'farm_id']);

        $data = $operators->map(function ($operator) {
            return [
                'user_id' => $operator->user_id,
                'farm_id' => $operator->farm_id,
                'nama_operator' => $operator->user ? $operator->user->name : 'N/A',
            ];
        });

        return response()->json(['data' => $data]);
    }

    public function addInventoryLocationAjax(Request $request)
    {
        $request->validate([
            'farm_id' => 'required|exists:farms,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:inventory_locations,code',
            'type' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            $data = [
                'farm_id' => $request->farm_id,
                'name' => $request->name,
                'code' => $request->code,
                'type' => $request->type,
                'status' => 'Aktif',
                'user_id' => auth()->user()->id,
            ];

            InventoryLocation::create($data);

            DB::commit();

            return response()->json(['success' => 'Inventory location successfully added']);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['errors' => $e->validator->errors()->all()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'An error occurred while saving data. ' . $e->getMessage()], 500);
        }
    }

    public function getKandangs(Request $request)
    {
        $farm = Farm::find($request->farm_id);
        // dd($request->farm_id);
        $kandangs = $farm->kandangs()->with(['livestock' => function ($query) {
            // $query->where('status', 'active')->orWhere('status', 'Locked');
        }])->get()->map(function ($kandang) {
            $livestock = $kandang->livestock;
            return [
                'id' => $kandang->id,
                'nama' => $kandang->nama,
                'kode' => $kandang->kode,
                'kapasitas' => $kandang->kapasitas,
                'status' => $kandang->status,
                'livestock' => $livestock ? [
                    'id' => $livestock->id,
                    'name' => $livestock->name,
                    'breed' => $livestock->breed,
                    'populasi_awal' => $livestock->populasi_awal,
                    'berat_awal' => $livestock->berat_awal,
                    'start_date' => $livestock->start_date,
                ] : null,
            ];
        });

        return response()->json($kandangs);
    }
}

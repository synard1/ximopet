<?php

namespace App\Actions;

use App\Models\User;
use App\Models\Farm;
use App\Models\FarmOperator;
use App\Models\Kandang;
use App\Models\StokMutasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\TransaksiDetail;
use App\Models\Transaksi;
use App\Models\TransaksiBeli;
use App\Models\TransaksiBeliDetail;
use App\Models\TransaksiJual;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class AppApi
{
    public function datatableListFarm(Request $request)
    {
        $draw = $request->input('draw', 0);
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $columns = $request->input('columns');
        $searchValue = $request->input('search.value');

        $orderColumn = $request->input('order.0.column', 0); // Get the order column index
        $orderDir = $request->input('order.0.dir', 'asc'); // Get the order direction (ASC or DESC)

        $query = Farm::query();

        if ($searchValue) {
            $searchColumns = ['kode', 'nama', 'alamat'];
            $query->where(function ($query) use ($searchValue, $searchColumns) {
                foreach ($searchColumns as $column) {
                    $query->orWhere(DB::raw("LOWER($column)"), 'LIKE', '%' . strtolower($searchValue) . '%');
                }
            });
        }

        // Get the column name for ordering based on the orderColumn index
        $orderColumnName = $columns[$orderColumn]['data'] ?? 'id';

        // exclude core user for demo purpose
        $query->whereNotIn('id', [1]);

        // Apply ordering to the query
        $query->orderBy($orderColumnName, $orderDir);

        $totalRecords = $query->count();

        $records = $query->offset($start)->limit($length)->get();

        $data = [
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => $records,
            'orderColumnName' => $orderColumnName,
        ];

        return $data;
    }

    public function getFarm(Request $request)
    {
        $filter = $request->input('filter');
        $options = Farm::when($filter, function ($query) use ($filter) {
            $query->where('name', 'like', "%$filter%");
        })->get();

        return response()->json($options);
    }

    public function getTransaksi(Request $request)
    {
        // dd($request->all());
        $id = $request->input('id');

        $data = StokMutasi::where('stok_transaksi_id', $id)->get();

        dd($id);

        return response()->json($data);
    }

    public function getTransaksiBeliDetail($id)
    {
        dd($id);
        $options = TransaksiBeliDetail::with(['items' => function ($query) {
            $query->select('id', 'satuan_besar', 'satuan_kecil', 'konversi');
        }])
            ->where('transaksi_id', $id)
            ->get(['id', 'jenis', 'jenis_barang', 'item_id', 'item_name', 'qty', 'terpakai', 'sisa', 'harga', 'sub_total']);

        $formattedOptions = $options->map(function ($item) {
            if ($item->items) {
                $konversi = floatval($item->items->konversi) ?: 1; // Use 1 as default to avoid division by zero

                $item->qty = floatval($item->qty) / $konversi;
                $item->sisa = floatval($item->sisa) / $konversi;
                $item->terpakai = floatval($item->terpakai) / $konversi;
                $item->jumlah = $item->terpakai;
                $item->satuan_besar = $item->items->satuan_besar;
                $item->satuan_kecil = $item->items->satuan_kecil;
                $item->konversi = $konversi;
            } else {
                $item->sisa = floatval($item->qty) - floatval($item->terpakai);
                $item->terpakai = floatval($item->terpakai);
                $item->jumlah = $item->terpakai;
                $item->satuan_besar = null;
                $item->satuan_kecil = null;
                $item->konversi = null;
            }

            // Ensure all numeric values are floats
            $item->qty = floatval($item->qty);
            $item->sisa = floatval($item->sisa);
            $item->terpakai = floatval($item->terpakai);
            $item->jumlah = floatval($item->jumlah);
            $item->harga = floatval($item->harga);
            // $item->sub_total = floatval($item->sub_total);

            if (config('xolution.ALLOW_ROUNDUP_PRICE') == true) {
                $item->sub_total = floatval($item->sub_total);
            } else {
                $item->sub_total = intval($item->sub_total);
            }

            return $item;
        });

        return response()->json(['data' => $formattedOptions]);
    }

    public function getTransaksiDetail($id)
    {
        // $options = TransaksiDetail::where('transaksi_id', $id)->get(['id','jenis','jenis_barang','item_nama','qty','terpakai', 'sisa', 'harga','sub_total']);
        // Gunakan eager loading untuk mengambil data terkait dari model items
        $options = TransaksiDetail::with(['items' => function ($query) {
            $query->select('id', 'satuan_besar', 'satuan_kecil', 'konversi');
        }])
            ->where('transaksi_id', $id)
            ->get(['id', 'jenis', 'jenis_barang', 'item_id', 'item_name', 'qty', 'terpakai', 'sisa', 'harga', 'sub_total']);

        // Map over the collection to calculate the 'sisa' field and include satuan data
        $formattedOptions = $options->map(function ($item) {

            // Pastikan relasi 'items' ada sebelum mengakses propertinya
            if ($item->items) {
                // $item->qty = number_format(($item->qty / $item->items->konversi), 3);
                $item->qty = number_format(($item->qty / $item->items->konversi), 0);
                // $item->sisa = ((floatval($item->qty) * floatval($item->items->konversi)) -  (floatval($item->terpakai)) / floatval($item->items->konversi));
                $item->sisa = number_format(($item->sisa / $item->items->konversi), 0);
                $item->terpakai = number_format(($item->terpakai / $item->items->konversi), 0);
                $item->jumlah = number_format(($item->terpakai / $item->items->konversi), 0);
                $item->satuan_besar = $item->items->satuan_besar;
                $item->satuan_kecil = $item->items->satuan_kecil;
                $item->konversi = $item->items->konversi;
            } else {
                $item->sisa = $item->qty - ($item->terpakai / $item->items->konversi);
                // Tangani kasus ketika 'items' null (misalnya, berikan nilai default)
                $item->satuan_besar = null;
                $item->satuan_kecil = null;
                $item->konversi = null;
            }

            return $item;
        });

        return response()->json(['data' => $formattedOptions]);
    }

    public function getFarmOperator()
    {
        // $data = FarmOperator::orderBy('nama_farm', 'asc')->get(['id','farm_id','nama_farm','nama_operator','status']);
        // $data = FarmOperator::with('user')->get(['farm_id','user.name']);

        // Map over the collection to calculate the 'sisa' field for each record
        // $formattedOptions = $options->map(function ($item) {
        //     $item->sisa = $item->qty - $item->terpakai;
        //     return $item;
        // });

        $data = FarmOperator::with('user:id,name')
            ->get(['farm_id', 'user_id']);

        // Extract the 'nama' from the 'farm' relationship
        $data = $data->map(function ($farmOperator) {
            return [
                'farm_id' => $farmOperator->farm_id,
                'user_id' => $farmOperator->user_id,
                'nama_farm' => $farmOperator->farm ? $farmOperator->farm->nama : 'N/A',
                'nama_operator' => $farmOperator->user ? $farmOperator->user->name : 'N/A',
            ];
        });

        // Wrap the formatted data in an array
        $result = ['data' => $data];

        // Return the formatted data as JSON
        return response()->json($result);
    }

    public function getPenjualan($transaksiId)
    {
        // Fetch operators not associated with the selected farm
        $data = TransaksiJual::findOrFail($transaksiId);

        // $operators = User::where('role', 'Operator')
        //     ->whereNotIn('id', $existingOperatorIds)
        //     ->get(['id', 'name']);
        $result = ['data' => $data];


        return response()->json($result);
        // return response()->json(['operators' => $operators]);
    }

    public function getFarms()
    {
        // Fetch operators not associated with the selected farm
        $farms = Farm::where('status', 'active')->get(['id', 'name']);

        // $operators = User::where('role', 'Operator')
        //     ->whereNotIn('id', $existingOperatorIds)
        //     ->get(['id', 'name']);
        $result = ['farms' => $farms];


        return response()->json($result);
        // return response()->json(['operators' => $operators]);
    }

    public function getKandangs($farmId, $status)
    {
        // Fetch operators not associated with the selected farm
        if ($status == 'used') {
            $kandangs = Kandang::where('farm_id', $farmId)->where('status', 'Digunakan')->get(['id', 'nama']);
            // }else{
            //     $kandangs = Kandang::where('status', 'Tidak Aktif')->get(['id', 'nama']);
        }

        $result = ['kandangs' => $kandangs];


        return response()->json($result);
    }

    public function createMutasiStok(Request $request)
    {
        dd($request->all());
        // Get the operator IDs already associated with the selected farm
        $existingOperatorIds = FarmOperator::where('farm_id', $farmId)->pluck('nama_operator');

        // Fetch operators not associated with the selected farm
        $operators = User::role('Operator') // Get users with 'Operator' role
            ->whereDoesntHave('farmOperators', function ($query) use ($farmId) {
                $query->where('farm_id', $farmId);
            })
            // ->pluck('name', 'id');
            ->get(['id', 'name']);

        // $operators = User::where('role', 'Operator')
        //     ->whereNotIn('id', $existingOperatorIds)
        //     ->get(['id', 'name']);
        $result = ['operators' => $operators];


        return response()->json($result);
        // return response()->json(['operators' => $operators]);
    }

    public function getOperators($farmId)
    {
        // Get the operator IDs already associated with the selected farm
        // $existingOperatorIds = FarmOperator::where('farm_id', $farmId)->pluck('nama_operator');

        // Fetch operators not associated with the selected farm
        $operators = User::role('Operator') // Get users with 'Operator' role
            ->whereDoesntHave('farmOperators', function ($query) use ($farmId) {
                $query->where('farm_id', $farmId);
            })
            // ->pluck('name', 'id');
            ->get(['id', 'name']);

        // $operators = User::where('role', 'Operator')
        //     ->whereNotIn('id', $existingOperatorIds)
        //     ->get(['id', 'name']);
        $result = ['operators' => $operators];


        return response()->json($result);
        // return response()->json(['operators' => $operators]);
    }

    public function getFarmStoks($farmId)
    {
        // Get the operator IDs already associated with the selected farm
        // $data = TransaksiDetail::where('farm_id', $farmId)
        //         ->groupBy('farm_id')
        //         ->distinct('nama')
        //         ->get();
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

        // Separate query to get the oldest date
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

        // return $data->count();

        if ($data->isEmpty()) {
            // No results found
            return response()->json([
                'error' => 'No data found for the specified farm ID.'
            ], 404); // 404 Not Found is a suitable status code for this scenario    

        } else {
            //refractor $data
            $a['oldestDate'] = $oldestDate;

            // Results found, you can work with $data here
            $result = [
                'stock' => $data,
                'parameter' => $a
            ];
            return response()->json($result);
        }
    }

    public function deleteFarmOperator(Request $request)
    {
        $type = $request->input('type');
        $userId = $request->input('user_id');
        $farmId = $request->input('farm_id');

        if ($type == 'DELETE') {
            // Assuming you have $userId and $farmId
            $user = User::find($userId);

            // dd($type);
            $user->farms()->detach($farmId);
            return response()->json(['success' => true, 'message' => 'Berhasil Hapus Data',]);
        }



        // return FarmOperator::destroy($id);
    }

    public function farmOperator(Request $request)
    {
        $type = $request->input('type');
        $userId = $request->input('user_id');
        $farmId = $request->input('farm_id');

        if ($type == 'DELETE') {
            // Assuming you have $userId and $farmId
            // Using Eloquent model (if you have a model for the pivot table)
            FarmOperator::where('user_id', $userId)
                ->where('farm_id', $farmId)
                ->delete();
            // $user = User::find($userId);

            // dd($type);
            // $user->farms()->detach($farmId); 
            return response()->json(['success' => true, 'message' => 'Berhasil Hapus Data',]);
        }

        // return FarmOperator::destroy($id);
    }

    public function create(Request $request)
    {
        $user = $request->all();

        $rules = [
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
        ];

        $validator = Validator::make($user, $rules);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $updated = User::create($user);

        return response()->json(['success' => $updated]);
    }

    public function postStockEdit(Request $request)
    {
        $id = $request->input('id');
        $value = $request->input('value');
        $column = $request->input('column');

        try {
            // Wrap database operation in a transaction (if applicable)
            DB::beginTransaction();

            // Update Detail Items
            $transaksiDetail = TransaksiDetail::findOrFail($id);
            if ($column == 'qty') {
                $transaksiDetail->update(
                    [
                        $column => $value * $transaksiDetail->items->konversi,
                        'sisa' => $value * $transaksiDetail->items->konversi,
                    ]
                );
            } else {
                $transaksiDetail->update(
                    [
                        $column => $value,
                    ]
                );
            }


            $test = ($transaksiDetail->qty / $transaksiDetail->items->konversi) * $transaksiDetail->harga;
            // dd($transaksiDetail->qty . '-'. $transaksiDetail->items->konversi . '-'. $transaksiDetail->harga . '-'. $test);

            $transaksiDetail->update(
                [
                    'sub_total' => ($transaksiDetail->qty / $transaksiDetail->items->konversi) * $transaksiDetail->harga,
                    // 'total_qty' => ($transaksiDetail->qty / $transaksiDetail->items->konversi),
                ]
            );


            //Update Parent Transaksi
            // $transaksi = Transaksi::where('id', $transaksiDetail->transaksi_id)->first();

            $transaksi = Transaksi::findOrFail($transaksiDetail->transaksi_id);
            // $sumQty = TransaksiDetail::where('transaksi_id',$transaksiDetail->transaksi_id)->sum('qty');
            $sumQty = TransaksiDetail::where('transaksi_id', $transaksiDetail->transaksi_id)
                ->with('items') // Eager load relasi 'items'
                ->get() // Ambil semua data yang sesuai
                ->sum(function ($item) {
                    return $item->qty / $item->items->konversi; // Hitung qty / konversi untuk setiap item
                });
            $sumHarga = TransaksiDetail::where('transaksi_id', $transaksiDetail->transaksi_id)->sum('harga');
            $transaksi->update(
                [
                    'total_qty' => $sumQty,
                    'sisa' => $sumQty,
                    'harga' => $sumHarga,
                    'sub_total' => $sumHarga * $sumQty
                ]
            );

            // Commit the transaction
            DB::commit();

            // return response()->json(['success' => true,'message'=>'Berhasil Update Data']);
            return response()->json(['message' => 'Berhasil Update Data', 'status' => 'success']);
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
        }



        return response()->json(['success' => $updated]);
    }

    public function get($id)
    {
        return Farm::findOrFail($id);
    }

    public function resetDemo()
    {
        try {
            //code...
            Artisan::call('migrate:fresh --seed');
            // Artisan::call('db:seed');
            return response()->json(['message' => 'Data Berhasil Direset', 'status' => 'success']);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json(['errors' => 'Reset Error'], 400);
        }
    }

    public function showVersion()
    {
        $gitCommitHash = trim(exec('git rev-parse --short HEAD'));
        $gitBranch = trim(exec('git rev-parse --abbrev-ref HEAD'));
        $gitTag = trim(exec('git describe --tags --abbrev=0 2>/dev/null')); // May be empty if no tag exists

        // You can customize this logic to determine if the latest release is running
        $isLatestRelease = true; // Replace with your actual logic

        return view('version', [
            'commitHash' => $gitCommitHash,
            'branch' => $gitBranch,
            'tag' => $gitTag,
            'isLatestRelease' => $isLatestRelease,
        ]);
    }

    // public function delete($id)
    // {
    //     return User::destroy($id);
    // }
}

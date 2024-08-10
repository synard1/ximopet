<?php

namespace App\Actions;

use App\Models\User;
use App\Models\Farm;
use App\Models\Kandang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\TransaksiDetail;

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

    public function getTransaksiDetail($id)
    {
        $options = TransaksiDetail::where('transaksi_id', $id)->get(['jenis','jenis_barang','nama','qty','terpakai', 'sisa', 'harga','sub_total']);

        // Map over the collection to calculate the 'sisa' field for each record
        $formattedOptions = $options->map(function ($item) {
            $item->sisa = $item->jumlah - $item->terpakai;
            return $item;
        });

        // Wrap the formatted data in an array
        $result = ['data' => $formattedOptions];

        // Return the formatted data as JSON
        return response()->json($result);
    }

    // public function datatableList(Request $request)
    // {
    //     $draw = $request->input('draw', 0);
    //     $start = $request->input('start', 0);
    //     $length = $request->input('length', 10);
    //     $columns = $request->input('columns');
    //     $searchValue = $request->input('search.value');

    //     $orderColumn = $request->input('order.0.column', 0); // Get the order column index
    //     $orderDir = $request->input('order.0.dir', 'asc'); // Get the order direction (ASC or DESC)

    //     $query = User::query()->with('roles');

    //     if ($searchValue) {
    //         $searchColumns = ['name', 'email'];
    //         $query->where(function ($query) use ($searchValue, $searchColumns) {
    //             foreach ($searchColumns as $column) {
    //                 $query->orWhere(DB::raw("LOWER($column)"), 'LIKE', '%' . strtolower($searchValue) . '%');
    //             }
    //         });
    //     }

    //     // Get the column name for ordering based on the orderColumn index
    //     $orderColumnName = $columns[$orderColumn]['data'] ?? 'id';

    //     // exclude core user for demo purpose
    //     $query->whereNotIn('id', [1]);

    //     // Apply ordering to the query
    //     $query->orderBy($orderColumnName, $orderDir);

    //     $totalRecords = $query->count();

    //     $records = $query->offset($start)->limit($length)->get();

    //     $data = [
    //         'draw' => $draw,
    //         'recordsTotal' => $totalRecords,
    //         'recordsFiltered' => $totalRecords,
    //         'data' => $records,
    //         'orderColumnName' => $orderColumnName,
    //     ];

    //     return $data;
    // }

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

    public function get($id)
    {
        return Farm::findOrFail($id);
    }

    // public function update($id, Request $request)
    // {
    //     $data = $request->validate([
    //         'name' => 'required|string',
    //         'email' => 'required|email|unique:users,email,' . $id,
    //         'role' => 'required|string',
    //     ]);

    //     $user = User::findOrFail($id);
    //     $user->update($data);

    //     $user->assignRole($request->role);

    //     return response()->json(['success' => true]);
    // }

    // public function delete($id)
    // {
    //     return User::destroy($id);
    // }
}

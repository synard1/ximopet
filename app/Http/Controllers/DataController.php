<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\CurrentStock;
use App\Models\Farm;
use App\Models\Kandang;
use App\Models\FarmOperator;
use App\Models\FarmSilo;
use App\Models\InventoryLocation;
use App\Models\User;
use App\Models\ItemLocation;
use App\Models\Item; // Assuming there is an Item model

use App\Models\StockHistory;
use App\Models\TransaksiHarian;
use App\Models\TransaksiHarianDetail;

use Illuminate\Support\Facades\DB;

class DataController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, $type, $submodul = null)
    {
        $user = Auth::user();
        $farmId = $request->input('farm_id');
        $userRoleName = $user->roles->first()->name; // Assuming 'roles' is a relationship and user has only one role
        $mode = $request->input('mode', 'LIST'); // Default to 'list' if mode is not provided
        $task = $request->input('task', 'GET'); // Default to 'list' if mode is not provided

        // dd($request->all());

        // Check permissions based on user role
        if (!$task || !in_array($task, ['GET', 'LIST', 'ADD', 'DELETE', 'UPDATE'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($userRoleName === 'admin') {
            $data = $this->getAdminData($type);
        } elseif ($userRoleName === 'Supervisor') {
            if ($submodul && $submodul === 'operators') {
                if ($task === 'GET') {
                    $data = $this->getFarmOperator();
                } elseif ($task === 'DELETE') {
                    return $this->deleteFarmOperator($request);
                } else {
                    return response()->json(['error' => 'Unauthorized'], 403);
                }
            } elseif ($submodul && $submodul === 'storage') {
                if ($task === 'GET') {
                    $data = $this->getFarmStorage();
                } elseif ($task === 'DELETE') {
                    $storageId = $request->input('storage_id');
                    return $this->deleteStorageIfActive($storageId);
                } else {
                    return response()->json(['error' => 'Unauthorized'], 403);
                }
            } elseif($type === 'items'){
                if ($submodul && $submodul === 'location') {
                    if ($task === 'GET') {
                        $data = $this->getItemLocationMapping();
                    }
                }else{
                    if ($task === 'GET') {
                        $data = $this->getActiveItems();
                    }
                }
            } elseif($type === 'farms'){
                if ($submodul && $submodul === 'items_mapping') {
                    if ($task === 'GET') {
                        $farmId = $request->input('farm_id');
                        $data = $this->getItemsNotInLocation($farmId);
                    }elseif ($task === 'ADD') {
                        // dd($request->all());
                        return $this->storeItemLocationMapping($request);

                    } elseif ($task === 'DELETE') {
                        $id = $request->input('id');
                        return $this->deleteStorageMapping($id);
                    }
                }elseif ($submodul && $submodul === 'location') {
                    $farmId = $request->input('farm_id');
                    $data = $this->getFarmStorage($farmId);
                }else{
                    $data = $this->getActiveFarms();
                }
                // dd($request->all());
                // if ($task === 'DELETE') {
                //     $farmId = $request->input('farm_id');
                //     return $this->deleteFarmWithRelatedData($farmId);
                // } elseif ($task === 'GET') {
                //     if ($submodul && $submodul === 'items_mapping') {
                //         $farmId = $request->input('farm_id');
                //         $data = $this->getItemsNotInLocation($farmId);
                //     }elseif ($submodul && $submodul === 'location') {
                //         $farmId = $request->input('farm_id');
                //         $data = $this->getFarmStorage($farmId);
                //     }else{
                //         $data = $this->getActiveFarms();

                //     }
                // }else {
                //     return response()->json(['error' => 'Unauthorized'], 403);
                // }
            } else {
                $data = $this->getFarmData($type);
            }
        } elseif ($userRoleName === 'Operator') {
            if ($submodul && $submodul === 'stocks') {
                if ($task === 'GET') {
                    $farmId = $request->input('farm_id');
                    $data = $this->getFarmStocks($farmId);
                } elseif ($task === 'DELETE') {
                    $stockId = $request->input('stock_id');
                    return $this->deleteStock($stockId);
                } else {
                    return response()->json(['error' => 'Unauthorized'], 403);
                }
            }elseif ($submodul && $submodul === 'details') {
                if ($task === 'GET') {
                    $farmId = $request->input('farm_id');
                    $data = $this->getFarmDetails($farmId);
                }
            } elseif($type === 'items'){
                if ($submodul && $submodul === 'location') {
                    if ($task === 'GET') {
                        $data = $this->getItemLocationMapping();
                    }
                }else{
                    if ($task === 'GET') {
                        $data = $this->getActiveItems();
                    }
                }
            }elseif($type === 'farms'){
                if ($submodul && $submodul === 'location') {
                    $farmId = $request->input('farm_id');
                    $data = $this->getFarmStorage($farmId);
                }else{
                    $data = $this->getActiveFarms();
                }
            }
        } else {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($this->formatData($data, $mode));
    }

    public function getItemLocationMapping()
    {
        $data = ItemLocation::with(['item:id,name', 'farm:id,nama', 'location:id,name'])
            ->get(['id', 'item_id','farm_id', 'location_id']);

        $formattedData = $data->map(function ($mapping) {
            return [
                'id' => $mapping->id,
                'item_name' => $mapping->item ? $mapping->item->name : 'N/A',
                'farm_name' => $mapping->farm ? $mapping->farm->nama : 'N/A',
                'location_name' => $mapping->location ? $mapping->location->name : 'N/A',
            ];
        });

        return $formattedData;
    }

    

    public function getFarmStocks($farmId)
    {
        // Retrieve the current stock using the location relation to get the farm ID
        $currentStocks = CurrentStock::whereHas('inventoryLocation', function ($query) use ($farmId) {
            $query->where('farm_id', $farmId);
        })->get();

        // Format the data to include necessary details
        $formattedStocks = $currentStocks->map(function ($stock) {
            return [
                'item_id' => $stock->item ? $stock->item->id : 'N/A',
                'item_name' => $stock->item ? $stock->item->name : 'N/A',
                'total' => $stock->quantity,
            ];
        });

        $oldestDate = $currentStocks->min('created_at');

        $kandangs = Kandang::where('farm_id', $farmId)->where('status', 'Digunakan')->get(['id', 'nama']);

        if ($formattedStocks->isEmpty()) {
            return response()->json([
                'error' => 'No data found for the specified farm ID.'
            ], 404);
        } else {
            $result = [
                'stock' => $formattedStocks,
                'parameter' => ['oldestDate' => $oldestDate],
                'kandangs' => $kandangs
            ];
            return $result;
            // return response()->json($result);
        }
    }

    public function getFarmDetails($farmId)
    {
        // Retrieve the current stock using the location relation to get the farm ID
        $currentStocks = CurrentStock::whereHas('inventoryLocation', function ($query) use ($farmId) {
            $query->where('farm_id', $farmId);
        })->get();

        // $currentTernak = CurrentTernak::where('farm_id', $farmId)->get();

        // Format the data to include necessary details
        $formattedStocks = $currentStocks->map(function ($stock) {
            return [
                'item_id' => $stock->item ? $stock->item->id : 'N/A',
                'item_name' => $stock->item ? $stock->item->name : 'N/A',
                'total' => $stock->quantity,
            ];
        });

        $oldestDate = $currentStocks->min('created_at');

        $kandangs = Kandang::whereHas('kelompokTernak', function ($query) {
            $query->where('status', 'Aktif');
        })
        ->where('farm_id', $farmId)
        ->where('status', 'Digunakan')
        ->with(['kelompokTernak' => function ($query) {
            $query->select('id', 'start_date')->where('status', 'Aktif');
        }])
        ->get(['id', 'nama','kelompok_ternak_id'])
        ->map(function ($kandang) {
            return [
                'id' => $kandang->id,
                'nama' => $kandang->nama,
                'start_date' => $kandang->kelompokTernak && $kandang->kelompokTernak->first()
                    ? $kandang->kelompokTernak->first()->start_date 
                    : null,
            ];
        });

        // $kandangs = Kandang::where('farm_id', $farmId)->where('status', 'Digunakan')->get(['id', 'nama']);

        if ($formattedStocks->isEmpty()) {
            return response()->json([
                'error' => 'No data found for the specified farm ID.'
            ], 404);
        } else {
            $result = [
                'stock' => $formattedStocks,
                'parameter' => ['oldestDate' => $oldestDate],
                'kandangs' => $kandangs
            ];
            return $result;
            // return response()->json($result);
        }
    }

    private function getFarmData($type)
    {
        if ($type === 'compact') {
            return Farm::select('id', 'name')->where('is_active', true)->get();
        }

        return Farm::where('is_active', true)->get(); // Detailed data
    }

    public function getFarmOperator()
    {
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

        return $data;
    }

    public function getFarmStorage($farmId = null)
    {
        // $farmId = $request->input('farm_id');

        $query = InventoryLocation::with('farm:id,nama');

        if ($farmId) {
            $query->where('farm_id', $farmId);
        }

        $data = $query->get(['id', 'farm_id', 'name', 'type']);

        // Extract the 'nama' from the 'farm' relationship
        $data = $data->map(function ($inventoryLocation) {
            return [
                'storage_id' => $inventoryLocation->id,
                'farm_id' => $inventoryLocation->farm_id,
                'nama_farm' => $inventoryLocation->farm ? $inventoryLocation->farm->nama : 'N/A',
                'nama' => $inventoryLocation->name,
                'type' => $inventoryLocation->type,
            ];
        });

        return $data;
    }

    public function getActiveItems()
    {
        $activeItems = Item::where('status', 'Aktif')->get(['id', 'name']);

        return $activeItems->map(function ($item) {
            return [
                'item_id' => $item->id,
                'item_name' => $item->name,
            ];
        });
    }

    public function getActiveFarms()
    {
        $activeItems = Farm::where('status', 'Aktif')->get(['id', 'nama']);

        return $activeItems->map(function ($item) {
            return [
                'farm_id' => $item->id,
                'farm_name' => $item->nama,
            ];
        });
    }

    private function deleteStorageIfActive($storageId)
    {
        $storage = InventoryLocation::find($storageId);

        if ($storage && $storage->status === 'Aktif') {
            if ($storage->type === 'silo') {
                $farmSilo = FarmSilo::where('id', $storage->silo_id)->first();
                if ($farmSilo) {
                    $farmSilo->delete();
                }
            }
            $storage->delete();
            return response()->json(['message' => 'Storage deleted successfully.']);
        }

        return response()->json(['error' => 'Storage is not active or does not exist.'], 400);
    }

    public function deleteFarmOperator(Request $request)
    {
        $userId = $request->input('user_id');
        $farmId = $request->input('farm_id');

        FarmOperator::where('user_id', $userId)
            ->where('farm_id', $farmId)
            ->delete();

        return response()->json(['success' => true, 'message' => 'Berhasil Hapus Data']);
    }

    private function deleteFarmWithRelatedData($farmId)
    {
        $farm = Farm::with('kandangs', 'storages', 'operators')->find($farmId);

        if (!$farm) {
            return response()->json(['error' => 'Farm not found'], 404);
        }

        $relatedData = [];

        if ($farm->kandangs->isNotEmpty()) {
            foreach ($farm->kandangs as $kandang) {
                $relatedData[] = ['type' => 'Kandang', 'name' => $kandang->nama];
            }
        }

        if ($farm->storages->isNotEmpty()) {
            foreach ($farm->storages as $storage) {
                $relatedData[] = ['type' => 'Storage', 'name' => $storage->name];
            }
        }

        if ($farm->operators->isNotEmpty()) {
            foreach ($farm->operators as $operator) {
                $relatedData[] = ['type' => 'Operator', 'name' => $operator->name];
            }
        }

        if (!empty($relatedData)) {
            return response()->json(['relatedData' => $relatedData]);
        }

        // If no related data, proceed with delete
        $farm->delete();
        return response()->json(['success' => 'Farm deleted successfully']);
    }

    public function getFarmDetailWithRelatedData($farmId)
    {
        $farm = Farm::with('kandangs', 'storages', 'operators')->find($farmId);

        if (!$farm) {
            return response()->json(['error' => 'Farm not found'], 404);
        }

        $farmDetails = [
            'farm' => [
                'id' => $farm->id,
                'name' => $farm->name,
                'location' => $farm->location,
            ],
            'kandangs' => $farm->kandangs->map(function ($kandang) {
                return [
                    'id' => $kandang->id,
                    'name' => $kandang->nama,
                ];
            }),
            'storages' => $farm->storages->map(function ($storage) {
                return [
                    'id' => $storage->id,
                    'name' => $storage->name,
                ];
            }),
            'operators' => $farm->operators->map(function ($operator) {
                return [
                    'id' => $operator->id,
                    'name' => $operator->name,
                ];
            }),
        ];

        return response()->json(['farmDetails' => $farmDetails]);
    }

    public function getItemsNotInLocation($farmId)
    {
        // $farmId = $request->input('farm_id');

        if (!$farmId) {
            return response()->json(['error' => 'Farm ID is required'], 400);
        }

        $itemsInLocation = ItemLocation::where('farm_id', $farmId)->pluck('item_id')->toArray();

        $items = Item::whereNotIn('id', $itemsInLocation)->get(['id', 'name']);

        return $items->map(function ($item) {
            return [
                'item_id' => $item->id,
                'item_name' => $item->name,
            ];
        });
    }

    private function formatData($data, $mode)
    {
        if ($mode === 'TABLE') {
            return [
                'draw' => 1,
                'recordsTotal' => $data->count(),
                'recordsFiltered' => $data->count(),
                'data' => $data
            ];
        }

        return $data;

        // return ['data' => $data];
    }

    public function storeItemLocationMapping(Request $request)
    {
        // $task = $request->input('task');

        // if ($task !== 'SAVE') {
        //     return response()->json(['error' => 'Invalid task'], 400);
        // }

        $request->validate([
            'item_select' => 'required|exists:items,id',
            'farm_select' => 'required|exists:master_farms,id',
            'location_select' => 'required|exists:inventory_locations,id',
        ]);

        // dd($request->all());

        try {
            $itemLocation = ItemLocation::updateOrCreate(
                [
                    'item_id' => $request->item_select,
                    'farm_id' => $request->farm_select,
                ],
                ['location_id' => $request->location_select]
            );

            return response()->json([
                'message' => 'Item location mapping saved successfully',
                'data' => $itemLocation
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to save item location mapping', 'details' => $e->getMessage()], 500);
        }
    }

    public function deleteStorageMapping($id)
    {
        try {
            $itemLocation = ItemLocation::findOrFail($id);
            
            // Check if the item location mapping exists
            if (!$itemLocation) {
                return response()->json(['error' => 'Item location mapping not found'], 404);
            }

            // Check if there's related data in CurrentStock
            $hasCurrentStock = CurrentStock::where('item_id', $itemLocation->item_id)
            ->where('location_id', $itemLocation->location_id)
            ->exists();

            if ($hasCurrentStock) {
                return response()->json(['error' => 'Cannot delete mapping. There is current stock associated with this item and location.'], 400);
            }

            // Delete the item location mapping
            $itemLocation->delete();

            // Return a success response
            return response()->json(['message' => 'Item location mapping deleted successfully'], 200);
        } catch (\Exception $e) {
            // Log the error
            // \Log::error('Error deleting item location mapping: ' . $e->getMessage());

            // Return an error response
            return response()->json(['error' => 'An error occurred while deleting the item location mapping' . $e->getMessage()], 500);
        }
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
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function transaksi(Request $request, $type =null)
    {
        $bentuk = $request->bentuk;
        // $status = $request->status;
        $jenis = $request->jenis;
        $task = $request->task;
        $id = $request->id;
        $value = $request->input('value');
        $column = $request->input('column');

        if($task == 'UPDATE'){
            // dd($request->all());
            // Update Detail Items
            $transaksiDetail = TransaksiHarianDetail::findOrFail($id);
            if($column == 'qty'){
                $transaksiDetail->update(
                    [
                        $column => $value * $transaksiDetail->items->konversi,
                    ]
                );
            }else{
                $transaksiDetail->update(
                    [
                        $column => $value,
                    ]
                );
            }

            return response()->json(['message' => 'Berhasil Update Data', 'status' => 'success' ]);
        }elseif($task == 'READ'){
            // Read Detail Items
            $transactions = TransaksiHarian::with(['details' => function($query) {
                $query->select('id', 'transaksi_id', 'item_id', 'quantity', 'harga')
                    ->with(['item' => function($itemQuery) {
                        $itemQuery->select('id', 'name', 'category_id')
                            ->with(['category' => function($categoryQuery) {
                                $categoryQuery->select('id', 'name');
                            }]);
                    }]);
            }])
            ->where('id', $id)
            ->select('id', 'tanggal')
            ->orderBy('tanggal', 'DESC')
            ->get()
            ->map(function ($transaction) {
                return $transaction->details->map(function ($detail) use ($transaction) {
                    
                    return [
                        'nama' => $detail->item->name ?? 'N/A',
                        'kategori' => $detail->item->category->name ?? 'N/A',
                        'stok_awal' => $detail->qty + $detail->sisa,
                        'terpakai' => $detail->quantity,
                        'sisa' => $detail->sisa,
                        'harga' => $detail->harga,
                        'sub_total' => $detail->sub_total,
                        'konversi' => $detail->konversi,
                        'tanggal' => $transaction->tanggal
                    ];
                });
            })
            ->flatten(1);

            return response()->json(['data' => $transactions]);
        }

    }
}

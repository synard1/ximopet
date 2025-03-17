<?php

namespace App\Http\Controllers;

use App\Models\TransaksiHarian;
use App\Models\TransaksiHarianDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ItemCategory;
use Carbon\Carbon;

class TransaksiHarianController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
        DB::beginTransaction();

        try {
            // Create the main transaction
            $transaksiHarian = TransaksiHarian::create([
                'tanggal' => $request->input('tanggal'),
                'farm_id' => $request->input('farm_id'),
                'kandang_id' => $request->input('kandang_id'),
                'keterangan' => $request->input('keterangan'),
                'created_by' => auth()->id(),
            ]);

            // Handle details based on type
            $details = $request->input('details', []);
            foreach ($details as $detail) {
                $type = $detail['type'];
                $commonData = [
                    'transaksi_harian_id' => $transaksiHarian->id,
                    'type' => $type,
                    'quantity' => $detail['quantity'],
                    'keterangan' => $detail['keterangan'],
                    'created_by' => auth()->id(),
                    'payload' => [], // Initialize payload as an empty array
                ];

                $typeSpecificData = [];
                switch ($type) {
                    case 'feed':
                        $typeSpecificData = [
                            'item_id' => $detail['item_id'],
                            'berat' => $detail['berat'],
                            'payload' => [
                                'item_id' => $detail['item_id'],
                                'berat' => $detail['berat'],
                            ],
                        ];
                        break;
                    case 'medication':
                    case 'vitamin':
                        $typeSpecificData = [
                            'item_id' => $detail['item_id'],
                            'dosis' => $detail['dosis'],
                            'payload' => [
                                'item_id' => $detail['item_id'],
                                'dosis' => $detail['dosis'],
                            ],
                        ];
                        break;
                    case 'sale':
                        $typeSpecificData = [
                            'pembeli_id' => $detail['pembeli_id'],
                            'harga_jual' => $detail['harga_jual'],
                            'payload' => [
                                'pembeli_id' => $detail['pembeli_id'],
                                'harga_jual' => $detail['harga_jual'],
                            ],
                        ];
                        break;
                    case 'death':
                    case 'culling':
                        $typeSpecificData = [
                            'reason' => $detail['reason'],
                            'payload' => [
                                'reason' => $detail['reason'],
                            ],
                        ];
                        break;
                }

                $data = array_merge($commonData, $typeSpecificData);
                TransaksiHarianDetail::create($data);
            }

            DB::commit();
            return response()->json(['message' => 'Transaksi Harian created successfully.'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create Transaksi Harian.'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(TransaksiHarian $transaksiHarian)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TransaksiHarian $transaksiHarian)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TransaksiHarian $transaksiHarian)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TransaksiHarian $transaksiHarian)
    {
        //
    }

    public function filter(Request $request)
    {
        $dateRange = explode(' - ', $request->date_range);
        $startDate = Carbon::createFromFormat('m/d/Y', $dateRange[0])->startOfDay();
        $endDate = Carbon::createFromFormat('m/d/Y', $dateRange[1])->endOfDay();
        $selectedJenis = $request->jenis;

        $query = TransaksiHarianDetail::with(['transaksiHarian', 'item.category'])
            ->whereHas('transaksiHarian', function($q) use ($startDate, $endDate) {
                $q->whereBetween('tanggal', [$startDate, $endDate]);
            });

        if (!empty($selectedJenis)) {
            $query->whereHas('item.category', function ($q) use ($selectedJenis) {
                $q->whereIn('name', $selectedJenis);
            });
        }

        $filteredData = $query->get();

        if ($filteredData->isEmpty()) {
            return response()->json([
                'error' => 'No data found for the selected filter.'
            ], 404);
        }

        // Ensure each transaksiHarian is loaded with kandang_id
        $filteredData->load('transaksiHarian:kandang_id,id,tanggal');

        // Group the data by date
        $groupedData = $filteredData->groupBy(function ($item) {
            return $item->transaksiHarian->tanggal->format('Y-m-d');
        });

        return view('pages.transaksi.harian._filtered_results', compact('groupedData'));
    }
}

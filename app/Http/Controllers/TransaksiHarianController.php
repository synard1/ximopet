<?php

namespace App\Http\Controllers;

use App\Models\TransaksiHarian;
use App\Models\TransaksiHarianDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
}

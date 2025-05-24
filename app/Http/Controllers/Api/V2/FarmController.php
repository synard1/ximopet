<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Kandang;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class FarmController extends Controller
{
    public function getKandangs(Request $request)
    {
        try {
            $request->validate([
                'farm_id' => 'required|exists:farms,id',
                'task' => 'required|in:GET',
                'mode' => 'required|in:DETAIL'
            ]);

            $kandangs = Kandang::with(['kelompokTernak' => function ($query) {
                $query->select('id', 'kandang_id', 'start_date', 'populasi_awal');
            }])
                ->where('farm_id', $request->farm_id)
                ->select('id', 'kode', 'nama', 'kapasitas', 'status', 'farm_id')
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Data kandang berhasil diambil',
                'data' => $kandangs
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error("[" . __CLASS__ . "::" . __FUNCTION__ . "] Error: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data kandang'
            ], 500);
        }
    }
}

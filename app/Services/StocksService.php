<?php

namespace App\Services;

use App\Models\CurrentTernak;
use App\Models\CurrentStock;
use App\Models\Kandang;
use App\Models\KelompokTernak;
use App\Models\KematianTernak;
use App\Models\Transaksi;
use App\Models\TransaksiDetail;
use App\Models\TransaksiJual;
use App\Models\TransaksiJualDetail;
use App\Models\StokMutasi;
use App\Models\StokHistory;
use App\Models\TransaksiBeli;
use App\Models\TernakAfkir;
use App\Models\TernakJual;
use App\Models\TernakHistory;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class StocksService
{

    /**
     * Check stock for a given ternak_id from CurrentStock.
     *
     * @param int $ternakId
     * @return array
     */
    public function checkStockByTernakId($ternakId)
    {
        $currentStock = CurrentStock::where('ternak_id', $ternakId)->first();

        if (!$currentStock) {
            return [
                'ternak_id' => $ternakId,
                'stock' => 0,
                'message' => 'No stock found for this ternak_id.'
            ];
        }

        return [
            'ternak_id' => $ternakId,
            'stock' => $currentStock->quantity,
            'message' => 'Stock retrieved successfully.'
        ];
    }
}

<?php

use App\Models\Recording;
use App\Models\FeedUsage;
use Illuminate\Support\Carbon;

class RecordingFeedInsightBuilder
{
    public static function buildForLivestockAndDate($livestockId, $tanggal): array
    {
        // Ambil semua feed usage di tanggal tersebut
        $feedUsage = FeedUsage::with(['details.feedStock.feedPurchase'])
            ->where('livestock_id', $livestockId)
            ->whereDate('tanggal', $tanggal)
            ->first();

        if (!$feedUsage) {
            return [
                'stock_purchase_dates' => [],
                'stock_prices' => [
                    'max_price' => 0,
                    'min_price' => 0,
                    'average_price' => 0,
                ],
            ];
        }

        $prices = [];
        $dates = [];

        foreach ($feedUsage->details as $detail) {
            $purchase = $detail->feedStock->feedPurchase ?? null;

            if ($purchase) {
                $prices[] = $purchase->price;
                $dates[] = Carbon::parse($purchase->purchase_date)->toDateString();
            }
        }

        return [
            'stock_purchase_dates' => array_values(array_unique($dates)),
            'stock_prices' => [
                'max_price' => $prices ? max($prices) : 0,
                'min_price' => $prices ? min($prices) : 0,
                'average_price' => $prices ? round(array_sum($prices) / count($prices), 2) : 0,
            ],
        ];
    }
}

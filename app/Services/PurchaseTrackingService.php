<?php

namespace App\Services;

use App\Models\FeedStock;
use App\Models\FeedPurchase;
use App\Models\FeedPurchaseItem;

class PurchaseTrackingService
{
    /**
     * Get purchase info for a FeedStock (tracking to FeedPurchaseItem and FeedPurchase)
     * @param FeedStock $feedStock
     * @return array
     */
    public static function getFeedPurchaseInfo(FeedStock $feedStock): array
    {
        $purchaseItem = null;
        $purchase = null;
        // If source_type is 'purchase', source_id is FeedPurchaseItem
        if ($feedStock->source_type === 'purchase' && $feedStock->source_id) {
            $purchaseItem = FeedPurchaseItem::find($feedStock->source_id);
            $purchase = $purchaseItem ? $purchaseItem->feedPurchase : null;
        } elseif ($feedStock->feed_purchase_id) {
            // fallback: direct relation
            $purchase = $feedStock->feedPurchase;
            // Try to find item by feed_purchase_id and feed_id
            $purchaseItem = $purchase ? $purchase->feedPurchaseItems()->where('feed_id', $feedStock->feed_id)->first() : null;
        }
        return [
            'purchase_id' => $purchase ? $purchase->id : null,
            'invoice_number' => $purchase ? $purchase->invoice_number : null,
            'do_number' => $purchase ? $purchase->do_number : null,
            'supplier' => $purchase && $purchase->supplier ? $purchase->supplier->name : 'Unknown',
            'expedition' => $purchase && $purchase->expedition ? $purchase->expedition->name : null,
            'purchase_date' => $purchase ? $purchase->date : null,
            'item_id' => $purchaseItem ? $purchaseItem->id : null,
            'item_quantity' => $purchaseItem ? $purchaseItem->quantity : null,
            'item_unit' => $purchaseItem && $purchaseItem->unit ? $purchaseItem->unit->name : null,
            'item_price_per_unit' => $purchaseItem ? $purchaseItem->price_per_unit : null,
        ];
    }
}

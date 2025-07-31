<?php

namespace App\Helpers;

use App\Models\Livestock;
use App\Models\FeedPurchase;
use App\Models\SupplyPurchase;
use App\Models\LivestockPurchase;
use App\Models\SupplyPurchaseBatch;

class OllamaChatContextHelper
{
    /**
     * Get specific context data by ID or identifier
     */
    public static function getSpecificContextData(string $context, int $contextLimit): string
    {
        // Try to parse as UUID or ID
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $context)) {
            // UUID format
            return self::getContextDataByUUID($context, $contextLimit);
        }

        if (is_numeric($context)) {
            // Numeric ID
            return self::getContextDataByID($context, $contextLimit);
        }

        // Try to find by name or number
        return self::getContextDataByName($context, $contextLimit);
    }

    /**
     * Get context data by UUID
     */
    public static function getContextDataByUUID(string $uuid, int $contextLimit): string
    {
        // Try Livestock
        $livestock = Livestock::where('id', $uuid)->first();
        if ($livestock) {
            return self::formatLivestockContext($livestock, $contextLimit);
        }

        // Try FeedPurchase
        $feedPurchase = FeedPurchase::where('id', $uuid)->first();
        if ($feedPurchase) {
            return self::formatFeedPurchaseContext($feedPurchase, $contextLimit);
        }

        // Try SupplyPurchase
        $supplyPurchase = SupplyPurchase::where('id', $uuid)->first();
        if ($supplyPurchase) {
            return self::formatSupplyPurchaseContext($supplyPurchase, $contextLimit);
        }

        return "No data found for UUID: {$uuid}";
    }

    /**
     * Get context data by ID
     */
    public static function getContextDataByID(string $id, int $contextLimit): string
    {
        // Try Livestock
        $livestock = Livestock::find($id);
        if ($livestock) {
            return self::formatLivestockContext($livestock, $contextLimit);
        }

        // Try FeedPurchase
        $feedPurchase = FeedPurchase::find($id);
        if ($feedPurchase) {
            return self::formatFeedPurchaseContext($feedPurchase, $contextLimit);
        }

        return "No data found for ID: {$id}";
    }

    /**
     * Get context data by name or number
     */
    public static function getContextDataByName(string $name, int $contextLimit): string
    {
        // Try Livestock by name or number
        $livestock = Livestock::where('name', 'like', "%{$name}%")
            ->orWhere('number', 'like', "%{$name}%")
            ->orWhere('number_full', 'like', "%{$name}%")
            ->first();

        if ($livestock) {
            return self::formatLivestockContext($livestock, $contextLimit);
        }

        // Try FeedPurchase by invoice number
        $feedPurchase = FeedPurchase::where('invoice_number', 'like', "%{$name}%")
            ->orWhere('do_number', 'like', "%{$name}%")
            ->orWhere('number', 'like', "%{$name}%")
            ->first();

        if ($feedPurchase) {
            return self::formatFeedPurchaseContext($feedPurchase, $contextLimit);
        }

        return "No data found for name/number: {$name}";
    }

    /**
     * Get context data by type
     */
    public static function getContextDataByType(string $contextType, int $contextLimit): string
    {
        switch (strtolower($contextType)) {
            case 'livestock':
            case 'livestocks':
                return self::getLivestockSummary($contextLimit);

            case 'feed':
            case 'feed-purchase':
            case 'feedpurchase':
                return self::getFeedPurchaseSummary($contextLimit);

            case 'supply':
            case 'supply-purchase':
            case 'supplypurchase':
                return self::getSupplyPurchaseSummary($contextLimit);

            case 'livestock-purchase':
            case 'livestockpurchase':
                return self::getLivestockPurchaseSummary($contextLimit);

            case 'purchases':
            case 'pembelian':
                return self::getAllPurchasesSummary($contextLimit);

            case 'recent':
            case 'latest':
                return self::getRecentDataSummary($contextLimit);

            default:
                return "Unknown context type: {$contextType}";
        }
    }

    /**
     * Format Livestock context data
     */
    public static function formatLivestockContext($livestock, int $contextLimit): string
    {
        $context = "=== LIVESTOCK DATA ===\n";
        $context .= "ID: {$livestock->id}\n";
        $context .= "Name: {$livestock->name}\n";
        $context .= "Number: {$livestock->number}\n";
        $context .= "Status: {$livestock->getStatusLabel()}\n";
        $context .= "Initial Quantity: {$livestock->initial_quantity}\n";
        $context .= "Start Date: {$livestock->start_date?->format('Y-m-d')}\n";
        $context .= "End Date: {$livestock->end_date?->format('Y-m-d')}\n";

        if ($livestock->notes) {
            $context .= "Notes: {$livestock->notes}\n";
        }

        // Add basic batch information if available (with error handling)
        try {
            $batchCount = $livestock->batches()->count();
            if ($batchCount > 0) {
                $context .= "\n=== BATCHES ===\n";
                $context .= "Total batches: {$batchCount}\n";
                // Only get basic batch info to avoid complex queries
                $batches = $livestock->batches()->select('batch_number', 'quantity', 'weight')->take(3)->get();
                foreach ($batches as $batch) {
                    $context .= "- Batch {$batch->batch_number}: {$batch->quantity} units, {$batch->weight} kg\n";
                }
            }
        } catch (\Exception $e) {
            $context .= "\n=== BATCHES ===\n";
            $context .= "- Error loading batch data: " . $e->getMessage() . "\n";
        }

        return substr($context, 0, $contextLimit);
    }

    /**
     * Format FeedPurchase context data
     */
    public static function formatFeedPurchaseContext($feedPurchase, int $contextLimit): string
    {
        $context = "=== FEED PURCHASE DATA ===\n";
        $context .= "ID: {$feedPurchase->id}\n";
        $context .= "Invoice Number: {$feedPurchase->invoice_number}\n";
        $context .= "DO Number: {$feedPurchase->do_number}\n";
        $context .= "Status: {$feedPurchase->getStatusLabel()}\n";
        $context .= "Date: {$feedPurchase->date->format('Y-m-d')}\n";
        $context .= "Expedition Fee: Rp " . number_format($feedPurchase->expedition_fee, 0, ',', '.') . "\n";

        if ($feedPurchase->notes) {
            $context .= "Notes: {$feedPurchase->notes}\n";
        }

        // Add items information
        if ($feedPurchase->feedPurchaseItems()->count() > 0) {
            $context .= "\n=== PURCHASE ITEMS ===\n";
            foreach ($feedPurchase->feedPurchaseItems as $item) {
                $context .= "- {$item->feed->name}: {$item->quantity} kg @ Rp " . number_format($item->price, 0, ',', '.') . "\n";
            }
        }

        return substr($context, 0, $contextLimit);
    }

    /**
     * Format SupplyPurchase context data
     */
    public static function formatSupplyPurchaseContext($supplyPurchase, int $contextLimit): string
    {
        try {
            $context = "=== SUPPLY PURCHASE DATA ===\n";
            $context .= "ID: {$supplyPurchase->id}\n";
            $context .= "Status: {$supplyPurchase->status}\n";

            // Get date from batch relationship
            $date = 'N/A';
            if ($supplyPurchase->batch && $supplyPurchase->batch->date) {
                $date = $supplyPurchase->batch->date->format('Y-m-d');
            }
            $context .= "Date: {$date}\n";

            if ($supplyPurchase->notes) {
                $context .= "Notes: {$supplyPurchase->notes}\n";
            }

            return substr($context, 0, $contextLimit);
        } catch (\Exception $e) {
            return "Error formatting supply purchase context: " . $e->getMessage();
        }
    }

    /**
     * Get Livestock summary
     */
    public static function getLivestockSummary(int $contextLimit): string
    {
        try {
            $livestocks = Livestock::latest()->take(5)->get();

            if ($livestocks->isEmpty()) {
                return "No livestock data found.";
            }

            $context = "=== RECENT LIVESTOCK DATA ===\n";
            foreach ($livestocks as $livestock) {
                $context .= "• {$livestock->name} ({$livestock->number}): {$livestock->getStatusLabel()}, Initial: {$livestock->initial_quantity} units\n";
            }

            return substr($context, 0, $contextLimit);
        } catch (\Exception $e) {
            return "Error loading livestock data: " . $e->getMessage();
        }
    }

    /**
     * Get FeedPurchase summary
     */
    public static function getFeedPurchaseSummary(int $contextLimit): string
    {
        $feedPurchases = FeedPurchase::latest()->take(5)->get();

        if ($feedPurchases->isEmpty()) {
            return "No feed purchase data found.";
        }

        $context = "=== RECENT FEED PURCHASES ===\n";
        foreach ($feedPurchases as $purchase) {
            $context .= "• {$purchase->invoice_number}: {$purchase->getStatusLabel()}, {$purchase->date->format('Y-m-d')}\n";
        }

        return substr($context, 0, $contextLimit);
    }

    /**
     * Get SupplyPurchase summary
     */
    public static function getSupplyPurchaseSummary(int $contextLimit): string
    {
        try {
            $supplyPurchases = SupplyPurchase::with('batch')->latest()->take(5)->get();

            if ($supplyPurchases->isEmpty()) {
                return "No supply purchase data found.";
            }

            $context = "=== RECENT SUPPLY PURCHASES ===\n";
            foreach ($supplyPurchases as $purchase) {
                $date = $purchase->batch && $purchase->batch->date ? $purchase->batch->date->format('Y-m-d') : 'N/A';
                $context .= "• ID {$purchase->id}: {$purchase->status}, {$date}\n";
            }

            return substr($context, 0, $contextLimit);
        } catch (\Exception $e) {
            return "Error loading supply purchase data: " . $e->getMessage();
        }
    }

    /**
     * Get recent data summary
     */
    public static function getRecentDataSummary(int $contextLimit): string
    {
        $context = "=== RECENT DATA SUMMARY ===\n";

        // Recent Livestock
        $recentLivestock = Livestock::latest()->take(3)->get();
        if ($recentLivestock->isNotEmpty()) {
            $context .= "Recent Livestock:\n";
            foreach ($recentLivestock as $livestock) {
                $context .= "- {$livestock->name}: {$livestock->getStatusLabel()}\n";
            }
        }

        // Recent Feed Purchases
        $recentFeedPurchases = FeedPurchase::latest()->take(3)->get();
        if ($recentFeedPurchases->isNotEmpty()) {
            $context .= "\nRecent Feed Purchases:\n";
            foreach ($recentFeedPurchases as $purchase) {
                $context .= "- {$purchase->invoice_number}: {$purchase->getStatusLabel()}\n";
            }
        }

        return substr($context, 0, $contextLimit);
    }

    /**
     * Get LivestockPurchase summary
     */
    public static function getLivestockPurchaseSummary(int $contextLimit): string
    {
        try {
            $livestockPurchases = LivestockPurchase::with(['supplier', 'farm'])->latest()->take(5)->get();

            if ($livestockPurchases->isEmpty()) {
                return "No livestock purchase data found.";
            }

            $context = "=== RECENT LIVESTOCK PURCHASES ===\n";
            foreach ($livestockPurchases as $purchase) {
                $supplierName = $purchase->supplier ? $purchase->supplier->name : 'N/A';
                $farmName = $purchase->farm ? $purchase->farm->name : 'N/A';
                $date = $purchase->tanggal ? $purchase->tanggal->format('Y-m-d') : 'N/A';

                $context .= "• {$purchase->invoice_number}: {$purchase->getStatusLabel()}, {$date}\n";
                $context .= "  - Supplier: {$supplierName}, Farm: {$farmName}\n";
                $context .= "  - Items: " . $purchase->details->count() . " items\n\n";
            }

            return substr($context, 0, $contextLimit);
        } catch (\Exception $e) {
            return "Error loading livestock purchase data: " . $e->getMessage();
        }
    }

    /**
     * Get all purchases summary
     */
    public static function getAllPurchasesSummary(int $contextLimit): string
    {
        try {
            $context = "=== ALL PURCHASES SUMMARY ===\n\n";

            // Livestock Purchases
            $livestockPurchases = LivestockPurchase::latest()->take(3)->get();
            if ($livestockPurchases->isNotEmpty()) {
                $context .= "Recent Livestock Purchases:\n";
                foreach ($livestockPurchases as $purchase) {
                    $date = $purchase->tanggal ? $purchase->tanggal->format('Y-m-d') : 'N/A';
                    $context .= "• {$purchase->invoice_number}: {$purchase->getStatusLabel()}, {$date}\n";
                }
                $context .= "\n";
            }

            // Feed Purchases
            $feedPurchases = FeedPurchase::latest()->take(3)->get();
            if ($feedPurchases->isNotEmpty()) {
                $context .= "Recent Feed Purchases:\n";
                foreach ($feedPurchases as $purchase) {
                    $date = $purchase->date ? $purchase->date->format('Y-m-d') : 'N/A';
                    $context .= "• {$purchase->invoice_number}: {$purchase->getStatusLabel()}, {$date}\n";
                }
                $context .= "\n";
            }

            // Supply Purchases
            $supplyBatches = SupplyPurchaseBatch::latest()->take(3)->get();
            if ($supplyBatches->isNotEmpty()) {
                $context .= "Recent Supply Purchases:\n";
                foreach ($supplyBatches as $batch) {
                    $date = $batch->date ? $batch->date->format('Y-m-d') : 'N/A';
                    $context .= "• {$batch->invoice_number}: {$batch->getStatusLabel()}, {$date}\n";
                }
                $context .= "\n";
            }

            return substr($context, 0, $contextLimit);
        } catch (\Exception $e) {
            return "Error loading all purchases data: " . $e->getMessage();
        }
    }

    /**
     * Format context prompt
     */
    public static function formatContextPrompt(string $prompt, string $contextData): string
    {
        $enhancedPrompt = "Based on the following database context, please answer the question:\n\n";
        $enhancedPrompt .= "=== DATABASE CONTEXT ===\n";
        $enhancedPrompt .= $contextData;
        $enhancedPrompt .= "\n\n=== QUESTION ===\n";
        $enhancedPrompt .= $prompt;
        $enhancedPrompt .= "\n\nPlease provide a detailed answer based on the context data above.";

        return $enhancedPrompt;
    }
}

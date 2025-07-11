<?php

declare(strict_types=1);

namespace App\Services\Recording;

use App\Models\{FeedStock, SupplyStock, Feed, Supply, Livestock};
use App\Services\Recording\DTOs\ProcessingResult;
use App\Services\Recording\Exceptions\RecordingException;
use Illuminate\Support\Facades\{Cache, Log};
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * StockAnalysisService
 * 
 * Handles all stock analysis operations including availability checks,
 * stock details, price analysis, and stock history tracking.
 */
class StockAnalysisService
{
    private const CACHE_TTL = 1800; // 30 minutes
    private const CACHE_PREFIX = 'stock_analysis_';

    /**
     * Get detailed stock information for a feed item
     * 
     * @param string $feedId The feed ID
     * @param string $livestockId The livestock ID
     * @return array Detailed stock information
     */
    public function getFeedStockDetails(string $feedId, string $livestockId): array
    {
        $result = [
            'available_stocks' => [],
            'stock_origins' => [],
            'stock_purchase_dates' => [],
            'stock_prices' => [
                'min_price' => 0,
                'max_price' => 0,
                'average_price' => 0,
            ],
            'total_available' => 0,
            'stock_age_analysis' => [],
            'expiry_warnings' => [],
        ];

        try {
            $cacheKey = self::CACHE_PREFIX . "feed_{$feedId}_{$livestockId}";
            
            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($feedId, $livestockId, $result) {
                return $this->processFeedStockDetails($feedId, $livestockId, $result);
            });
        } catch (\Exception $e) {
            Log::error('Error getting feed stock details', [
                'feed_id' => $feedId,
                'livestock_id' => $livestockId,
                'error' => $e->getMessage()
            ]);

            return $result;
        }
    }

    /**
     * Get detailed stock information for a supply item
     * 
     * @param string $supplyId The supply ID
     * @param string $livestockId The livestock ID
     * @return array Detailed stock information
     */
    public function getSupplyStockDetails(string $supplyId, string $livestockId): array
    {
        $result = [
            'available_stocks' => [],
            'stock_origins' => [],
            'stock_purchase_dates' => [],
            'stock_prices' => [
                'min_price' => 0,
                'max_price' => 0,
                'average_price' => 0,
            ],
            'total_available' => 0,
            'stock_age_analysis' => [],
            'expiry_warnings' => [],
        ];

        try {
            $livestock = Livestock::find($livestockId);
            if (!$livestock) {
                return $result;
            }

            $cacheKey = self::CACHE_PREFIX . "supply_{$supplyId}_{$livestock->farm_id}";
            
            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($supplyId, $livestock, $result) {
                return $this->processSupplyStockDetails($supplyId, $livestock->farm_id, $result);
            });
        } catch (\Exception $e) {
            Log::error('Error getting supply stock details', [
                'supply_id' => $supplyId,
                'livestock_id' => $livestockId,
                'error' => $e->getMessage()
            ]);

            return $result;
        }
    }

    /**
     * Process feed stock details
     */
    private function processFeedStockDetails(string $feedId, string $livestockId, array $result): array
    {
        // Get available stocks for the feed and livestock
        $stocks = FeedStock::with(['feedPurchase.batch.supplier', 'feed'])
            ->where('feed_id', $feedId)
            ->where('livestock_id', $livestockId)
            ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
            ->orderBy('date')
            ->get();

        if ($stocks->isEmpty()) {
            return $result;
        }

        $stockDetails = [];
        $prices = [];
        $origins = [];
        $purchaseDates = [];
        $totalAvailable = 0;
        $ageAnalysis = [];

        foreach ($stocks as $stock) {
            $available = $stock->quantity_in - $stock->quantity_used - $stock->quantity_mutated;

            if ($available <= 0) {
                continue;
            }

            $totalAvailable += $available;

            // Get price information
            $price = 0;
            if ($stock->feedPurchase) {
                $price = $stock->feedPurchase->price_per_converted_unit ??
                    ($stock->feedPurchase->price_per_unit ?? 0);
                $prices[] = $price;
            }

            // Get origin information
            $origin = 'Unknown';
            if ($stock->feedPurchase && $stock->feedPurchase->batch && $stock->feedPurchase->batch->supplier) {
                $origin = $stock->feedPurchase->batch->supplier->name ?? 'Unknown';
                $origins[$origin] = ($origins[$origin] ?? 0) + $available;
            }

            // Get purchase date
            $purchaseDate = $stock->date ?? ($stock->feedPurchase->batch->date ?? null);
            if ($purchaseDate) {
                $formattedDate = Carbon::parse($purchaseDate)->format('Y-m-d');
                $purchaseDates[$formattedDate] = ($purchaseDates[$formattedDate] ?? 0) + $available;

                // Age analysis
                $ageInDays = Carbon::parse($purchaseDate)->diffInDays(Carbon::now());
                $ageAnalysis[] = [
                    'stock_id' => $stock->id,
                    'age_days' => $ageInDays,
                    'quantity' => $available,
                    'purchase_date' => $formattedDate,
                ];
            }

            // Add stock detail
            $stockDetails[] = [
                'stock_id' => $stock->id,
                'available' => $available,
                'price' => $price,
                'origin' => $origin,
                'purchase_date' => $purchaseDate ? Carbon::parse($purchaseDate)->format('Y-m-d') : null,
                'batch_id' => $stock->feedPurchase->batch->id ?? null,
                'batch_number' => $stock->feedPurchase->batch->invoice_number ?? null,
                'age_days' => $purchaseDate ? Carbon::parse($purchaseDate)->diffInDays(Carbon::now()) : null,
            ];
        }

        // Calculate price statistics
        if (!empty($prices)) {
            $result['stock_prices'] = [
                'min_price' => min($prices),
                'max_price' => max($prices),
                'average_price' => array_sum($prices) / count($prices),
            ];
        }

        // Format stock origins
        foreach ($origins as $origin => $quantity) {
            $result['stock_origins'][] = [
                'origin' => $origin,
                'quantity' => $quantity,
                'percentage' => $totalAvailable > 0 ? round(($quantity / $totalAvailable) * 100, 2) : 0,
            ];
        }

        // Format stock purchase dates
        foreach ($purchaseDates as $date => $quantity) {
            $result['stock_purchase_dates'][] = [
                'date' => $date,
                'quantity' => $quantity,
                'percentage' => $totalAvailable > 0 ? round(($quantity / $totalAvailable) * 100, 2) : 0,
            ];
        }

        $result['available_stocks'] = $stockDetails;
        $result['total_available'] = $totalAvailable;
        $result['stock_age_analysis'] = $this->analyzeStockAge($ageAnalysis);
        $result['expiry_warnings'] = $this->generateExpiryWarnings($stockDetails);

        return $result;
    }

    /**
     * Process supply stock details
     */
    private function processSupplyStockDetails(string $supplyId, string $farmId, array $result): array
    {
        // Get available stocks for the supply and farm
        $stocks = SupplyStock::with(['supplyPurchase.batch.supplier', 'supply'])
            ->where('supply_id', $supplyId)
            ->where('farm_id', $farmId)
            ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
            ->orderBy('date')
            ->get();

        if ($stocks->isEmpty()) {
            return $result;
        }

        $stockDetails = [];
        $prices = [];
        $origins = [];
        $purchaseDates = [];
        $totalAvailable = 0;
        $ageAnalysis = [];

        foreach ($stocks as $stock) {
            $available = $stock->quantity_in - $stock->quantity_used - $stock->quantity_mutated;

            if ($available <= 0) {
                continue;
            }

            $totalAvailable += $available;

            // Get price information
            $price = 0;
            if ($stock->supplyPurchase) {
                $price = $stock->supplyPurchase->price_per_converted_unit ??
                    ($stock->supplyPurchase->price_per_unit ?? 0);
                $prices[] = $price;
            }

            // Get origin information
            $origin = 'Unknown';
            if ($stock->supplyPurchase && $stock->supplyPurchase->batch && $stock->supplyPurchase->batch->supplier) {
                $origin = $stock->supplyPurchase->batch->supplier->name ?? 'Unknown';
                $origins[$origin] = ($origins[$origin] ?? 0) + $available;
            }

            // Get purchase date
            $purchaseDate = $stock->date ?? ($stock->supplyPurchase->batch->date ?? null);
            if ($purchaseDate) {
                $formattedDate = Carbon::parse($purchaseDate)->format('Y-m-d');
                $purchaseDates[$formattedDate] = ($purchaseDates[$formattedDate] ?? 0) + $available;

                // Age analysis
                $ageInDays = Carbon::parse($purchaseDate)->diffInDays(Carbon::now());
                $ageAnalysis[] = [
                    'stock_id' => $stock->id,
                    'age_days' => $ageInDays,
                    'quantity' => $available,
                    'purchase_date' => $formattedDate,
                ];
            }

            // Add stock detail
            $stockDetails[] = [
                'stock_id' => $stock->id,
                'available' => $available,
                'price' => $price,
                'origin' => $origin,
                'purchase_date' => $purchaseDate ? Carbon::parse($purchaseDate)->format('Y-m-d') : null,
                'batch_id' => $stock->supplyPurchase->batch->id ?? null,
                'batch_number' => $stock->supplyPurchase->batch->invoice_number ?? null,
                'age_days' => $purchaseDate ? Carbon::parse($purchaseDate)->diffInDays(Carbon::now()) : null,
            ];
        }

        // Calculate price statistics
        if (!empty($prices)) {
            $result['stock_prices'] = [
                'min_price' => min($prices),
                'max_price' => max($prices),
                'average_price' => array_sum($prices) / count($prices),
            ];
        }

        // Format stock origins
        foreach ($origins as $origin => $quantity) {
            $result['stock_origins'][] = [
                'origin' => $origin,
                'quantity' => $quantity,
                'percentage' => $totalAvailable > 0 ? round(($quantity / $totalAvailable) * 100, 2) : 0,
            ];
        }

        // Format stock purchase dates
        foreach ($purchaseDates as $date => $quantity) {
            $result['stock_purchase_dates'][] = [
                'date' => $date,
                'quantity' => $quantity,
                'percentage' => $totalAvailable > 0 ? round(($quantity / $totalAvailable) * 100, 2) : 0,
            ];
        }

        $result['available_stocks'] = $stockDetails;
        $result['total_available'] = $totalAvailable;
        $result['stock_age_analysis'] = $this->analyzeStockAge($ageAnalysis);
        $result['expiry_warnings'] = $this->generateExpiryWarnings($stockDetails);

        return $result;
    }

    /**
     * Analyze stock age distribution
     */
    private function analyzeStockAge(array $ageAnalysis): array
    {
        if (empty($ageAnalysis)) {
            return [];
        }

        $ageGroups = [
            'fresh' => ['min' => 0, 'max' => 7, 'quantity' => 0, 'count' => 0],
            'good' => ['min' => 8, 'max' => 30, 'quantity' => 0, 'count' => 0],
            'aging' => ['min' => 31, 'max' => 90, 'quantity' => 0, 'count' => 0],
            'old' => ['min' => 91, 'max' => 999, 'quantity' => 0, 'count' => 0],
        ];

        $totalQuantity = 0;

        foreach ($ageAnalysis as $stock) {
            $age = $stock['age_days'];
            $quantity = $stock['quantity'];
            $totalQuantity += $quantity;

            foreach ($ageGroups as $group => &$data) {
                if ($age >= $data['min'] && $age <= $data['max']) {
                    $data['quantity'] += $quantity;
                    $data['count']++;
                    break;
                }
            }
        }

        // Calculate percentages
        foreach ($ageGroups as $group => &$data) {
            $data['percentage'] = $totalQuantity > 0 ? round(($data['quantity'] / $totalQuantity) * 100, 2) : 0;
        }

        return [
            'age_groups' => $ageGroups,
            'average_age' => $this->calculateAverageAge($ageAnalysis),
            'oldest_stock' => max(array_column($ageAnalysis, 'age_days')),
            'newest_stock' => min(array_column($ageAnalysis, 'age_days')),
            'total_stocks' => count($ageAnalysis),
        ];
    }

    /**
     * Calculate average age of stocks
     */
    private function calculateAverageAge(array $ageAnalysis): float
    {
        if (empty($ageAnalysis)) {
            return 0;
        }

        $totalWeightedAge = 0;
        $totalQuantity = 0;

        foreach ($ageAnalysis as $stock) {
            $weightedAge = $stock['age_days'] * $stock['quantity'];
            $totalWeightedAge += $weightedAge;
            $totalQuantity += $stock['quantity'];
        }

        return $totalQuantity > 0 ? round($totalWeightedAge / $totalQuantity, 2) : 0;
    }

    /**
     * Generate expiry warnings for stocks
     */
    private function generateExpiryWarnings(array $stockDetails): array
    {
        $warnings = [];

        foreach ($stockDetails as $stock) {
            $age = $stock['age_days'] ?? 0;

            if ($age > 90) {
                $warnings[] = [
                    'stock_id' => $stock['stock_id'],
                    'severity' => 'high',
                    'message' => "Stock is {$age} days old and may be expired",
                    'action' => 'Check quality and consider disposal',
                ];
            } elseif ($age > 60) {
                $warnings[] = [
                    'stock_id' => $stock['stock_id'],
                    'severity' => 'medium',
                    'message' => "Stock is {$age} days old and aging",
                    'action' => 'Use this stock first (FIFO)',
                ];
            } elseif ($age > 30) {
                $warnings[] = [
                    'stock_id' => $stock['stock_id'],
                    'severity' => 'low',
                    'message' => "Stock is {$age} days old",
                    'action' => 'Monitor for quality changes',
                ];
            }
        }

        return $warnings;
    }

    /**
     * Check stock availability for multiple items
     */
    public function checkMultipleStockAvailability(string $livestockId, array $requirements): ProcessingResult
    {
        try {
            $results = [];
            $allAvailable = true;
            $shortages = [];

            foreach ($requirements as $requirement) {
                $type = $requirement['type']; // 'feed' or 'supply'
                $itemId = $requirement['item_id'];
                $requiredQuantity = $requirement['quantity'];

                if ($type === 'feed') {
                    $stockDetails = $this->getFeedStockDetails($itemId, $livestockId);
                } else {
                    $stockDetails = $this->getSupplyStockDetails($itemId, $livestockId);
                }

                $available = $stockDetails['total_available'];
                $isAvailable = $available >= $requiredQuantity;

                $results[] = [
                    'type' => $type,
                    'item_id' => $itemId,
                    'required' => $requiredQuantity,
                    'available' => $available,
                    'is_available' => $isAvailable,
                    'shortage' => $isAvailable ? 0 : $requiredQuantity - $available,
                ];

                if (!$isAvailable) {
                    $allAvailable = false;
                    $shortages[] = [
                        'type' => $type,
                        'item_id' => $itemId,
                        'shortage' => $requiredQuantity - $available,
                    ];
                }
            }

            $summary = [
                'all_available' => $allAvailable,
                'total_items' => count($requirements),
                'available_items' => count(array_filter($results, fn($r) => $r['is_available'])),
                'shortage_items' => count($shortages),
                'shortages' => $shortages,
                'details' => $results,
            ];

            return ProcessingResult::success($summary, 'Stock availability checked successfully');
        } catch (\Exception $e) {
            Log::error('Error checking multiple stock availability', [
                'livestock_id' => $livestockId,
                'requirements_count' => count($requirements),
                'error' => $e->getMessage()
            ]);

            return ProcessingResult::failure(['Stock availability check failed'], 'Stock check failed');
        }
    }

    /**
     * Get stock movement history
     */
    public function getStockMovementHistory(string $itemId, string $type, int $days = 30): ProcessingResult
    {
        try {
            $startDate = Carbon::now()->subDays($days);
            
            if ($type === 'feed') {
                $movements = FeedStock::with(['feedPurchase', 'feed'])
                    ->where('feed_id', $itemId)
                    ->where('date', '>=', $startDate)
                    ->orderBy('date', 'desc')
                    ->get();
            } else {
                $movements = SupplyStock::with(['supplyPurchase', 'supply'])
                    ->where('supply_id', $itemId)
                    ->where('date', '>=', $startDate)
                    ->orderBy('date', 'desc')
                    ->get();
            }

            $history = $movements->map(function ($stock) {
                return [
                    'date' => $stock->date,
                    'type' => 'incoming',
                    'quantity_in' => $stock->quantity_in,
                    'quantity_used' => $stock->quantity_used,
                    'quantity_mutated' => $stock->quantity_mutated,
                    'balance' => $stock->quantity_in - $stock->quantity_used - $stock->quantity_mutated,
                    'batch_number' => $stock->feedPurchase->batch->invoice_number ?? 
                                    $stock->supplyPurchase->batch->invoice_number ?? null,
                ];
            });

            return ProcessingResult::success($history, 'Stock movement history retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error getting stock movement history', [
                'item_id' => $itemId,
                'type' => $type,
                'days' => $days,
                'error' => $e->getMessage()
            ]);

            return ProcessingResult::failure(['Failed to get movement history'], 'Movement history retrieval failed');
        }
    }

    /**
     * Clear stock analysis cache
     */
    public function clearCache(?int $itemId = null, ?string $type = null): bool
    {
        try {
            if ($itemId && $type) {
                $pattern = self::CACHE_PREFIX . "{$type}_{$itemId}_*";
                // Note: This is a simplified cache clear. In production, you might want to use Redis patterns
                Cache::flush();
            } else {
                Cache::flush();
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Error clearing stock analysis cache', [
                'item_id' => $itemId,
                'type' => $type,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }
} 
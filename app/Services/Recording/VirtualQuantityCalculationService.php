<?php

namespace App\Services\Recording;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

use App\Models\Livestock;
use App\Models\LivestockBatch;
use App\Models\Recording;
use App\Models\RecordingSale;
use App\Models\LivestockDepletion;

use App\Services\Recording\DTOs\ServiceResult;

use function App\Helpers\logInfoIfDebug;
use function App\Helpers\logDebugIfDebug;
use function App\Helpers\logWarningIfDebug;
use function App\Helpers\logErrorIfDebug;

class VirtualQuantityCalculationService
{
    protected array $config;

    public function __construct()
    {
        $this->config = config('company.sales.livestock_sales.quantity_calculation.virtual_settings', []);
    }

    /**
     * Calculate virtual quantity for sales recording
     * This method calculates quantity without affecting real stock
     */
    public function calculateVirtualSalesQuantity(string $livestockId, string $date, array $salesData): ServiceResult
    {
        try {
            logInfoIfDebug('🔄 VirtualQuantityCalculationService::calculateVirtualSalesQuantity started', [
                'livestock_id' => $livestockId,
                'date' => $date,
                'sales_data' => $salesData
            ]);

            $livestock = Livestock::find($livestockId);
            if (!$livestock) {
                return ServiceResult::error('Livestock not found');
            }

            // Get current real stock from batches
            $realStockData = $this->getRealStockData($livestockId);

            // Calculate virtual quantity based on configuration
            $virtualQuantity = $this->calculateVirtualQuantity($livestockId, $date, $salesData, $realStockData);

            // Store virtual data in livestock_batches data column
            $this->storeVirtualData($livestockId, $date, $virtualQuantity, $salesData);

            logInfoIfDebug('✅ Virtual quantity calculated successfully', [
                'livestock_id' => $livestockId,
                'date' => $date,
                'virtual_quantity' => $virtualQuantity,
                'real_stock' => $realStockData
            ]);

            return ServiceResult::success('Virtual quantity calculated successfully', $virtualQuantity);
        } catch (Exception $e) {
            logErrorIfDebug('❌ Error in calculateVirtualSalesQuantity', [
                'livestock_id' => $livestockId,
                'date' => $date,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ServiceResult::error('Failed to calculate virtual quantity: ' . $e->getMessage(), $e);
        }
    }

    /**
     * Get real stock data from livestock batches
     */
    private function getRealStockData(string $livestockId): array
    {
        $batches = LivestockBatch::where('livestock_id', $livestockId)
            ->where('status', 'active')
            ->get();

        $totalInitialQuantity = $batches->sum('initial_quantity');
        $totalDepletion = $batches->sum('quantity_depletion');
        $totalSales = $batches->sum('quantity_sales');
        $totalMutated = $batches->sum('quantity_mutated');
        $totalAvailable = $batches->sum('quantity_available');

        return [
            'total_initial_quantity' => $totalInitialQuantity,
            'total_depletion' => $totalDepletion,
            'total_sales' => $totalSales,
            'total_mutated' => $totalMutated,
            'total_available' => $totalAvailable,
            'batches_count' => $batches->count(),
            'batches' => $batches->map(function ($batch) {
                return [
                    'id' => $batch->id,
                    'name' => $batch->name,
                    'initial_quantity' => $batch->initial_quantity,
                    'quantity_depletion' => $batch->quantity_depletion,
                    'quantity_sales' => $batch->quantity_sales,
                    'quantity_mutated' => $batch->quantity_mutated,
                    'quantity_available' => $batch->quantity_available,
                    'start_date' => $batch->start_date,
                ];
            })->toArray()
        ];
    }

    /**
     * Calculate virtual quantity based on configuration
     */
    private function calculateVirtualQuantity(string $livestockId, string $date, array $salesData, array $realStockData): array
    {
        $calculationMethod = $this->config['calculation_method'] ?? 'projection';

        switch ($calculationMethod) {
            case 'projection':
                return $this->calculateProjectionQuantity($livestockId, $date, $salesData, $realStockData);
            case 'estimate':
                return $this->calculateEstimateQuantity($livestockId, $date, $salesData, $realStockData);
            case 'forecast':
                return $this->calculateForecastQuantity($livestockId, $date, $salesData, $realStockData);
            default:
                return $this->calculateProjectionQuantity($livestockId, $date, $salesData, $realStockData);
        }
    }

    /**
     * Calculate projection-based virtual quantity
     */
    private function calculateProjectionQuantity(string $livestockId, string $date, array $salesData, array $realStockData): array
    {
        $projectionSettings = $this->config['projection_settings'] ?? [];
        $historicalDays = $projectionSettings['historical_days'] ?? 7;
        $growthRate = $projectionSettings['growth_rate_percentage'] ?? 0;

        // Get historical sales data
        $historicalSales = $this->getHistoricalSalesData($livestockId, $date, $historicalDays);

        // Calculate average daily sales
        $averageDailySales = $this->calculateAverageDailySales($historicalSales);

        // Apply growth rate
        $projectedSales = $averageDailySales * (1 + ($growthRate / 100));

        // Use provided sales data if available, otherwise use projection
        $virtualQuantity = $salesData['quantity'] ?? $projectedSales;
        $virtualWeight = $salesData['weight'] ?? ($virtualQuantity * ($realStockData['total_available'] > 0 ? 2.5 : 0)); // Default 2.5kg per unit

        return [
            'quantity' => (int) $virtualQuantity,
            'weight' => (float) $virtualWeight,
            'calculation_method' => 'projection',
            'projection_data' => [
                'historical_days' => $historicalDays,
                'average_daily_sales' => $averageDailySales,
                'growth_rate' => $growthRate,
                'projected_sales' => $projectedSales,
                'historical_sales' => $historicalSales
            ],
            'real_stock_reference' => $realStockData
        ];
    }

    /**
     * Calculate estimate-based virtual quantity
     */
    private function calculateEstimateQuantity(string $livestockId, string $date, array $salesData, array $realStockData): array
    {
        $estimateSettings = $this->config['estimate_settings'] ?? [];
        $accuracyThreshold = $estimateSettings['accuracy_threshold'] ?? 95;
        $confidenceLevel = $estimateSettings['confidence_level'] ?? 90;

        // Simple estimation based on available stock
        $availableStock = $realStockData['total_available'];
        $estimatedQuantity = min($salesData['quantity'] ?? $availableStock * 0.1, $availableStock); // Max 10% of available stock

        return [
            'quantity' => (int) $estimatedQuantity,
            'weight' => (float) ($estimatedQuantity * 2.5), // Default 2.5kg per unit
            'calculation_method' => 'estimate',
            'estimate_data' => [
                'accuracy_threshold' => $accuracyThreshold,
                'confidence_level' => $confidenceLevel,
                'available_stock' => $availableStock,
                'estimation_percentage' => 10
            ],
            'real_stock_reference' => $realStockData
        ];
    }

    /**
     * Calculate forecast-based virtual quantity
     */
    private function calculateForecastQuantity(string $livestockId, string $date, array $salesData, array $realStockData): array
    {
        $forecastSettings = $this->config['forecast_settings'] ?? [];
        $forecastPeriodDays = $forecastSettings['forecast_period_days'] ?? 30;
        $trendAnalysis = $forecastSettings['trend_analysis'] ?? true;

        // Get extended historical data for trend analysis
        $historicalSales = $this->getHistoricalSalesData($livestockId, $date, $forecastPeriodDays);

        // Calculate trend
        $trend = $this->calculateSalesTrend($historicalSales);

        // Forecast based on trend
        $forecastedQuantity = $this->applyTrendForecast($historicalSales, $trend);

        return [
            'quantity' => (int) ($salesData['quantity'] ?? $forecastedQuantity),
            'weight' => (float) (($salesData['quantity'] ?? $forecastedQuantity) * 2.5),
            'calculation_method' => 'forecast',
            'forecast_data' => [
                'forecast_period_days' => $forecastPeriodDays,
                'trend_analysis' => $trendAnalysis,
                'trend' => $trend,
                'forecasted_quantity' => $forecastedQuantity,
                'historical_sales' => $historicalSales
            ],
            'real_stock_reference' => $realStockData
        ];
    }

    /**
     * Get historical sales data
     */
    private function getHistoricalSalesData(string $livestockId, string $date, int $days): array
    {
        $startDate = Carbon::parse($date)->subDays($days);

        $historicalSales = RecordingSale::where('livestock_id', $livestockId)
            ->whereBetween('sale_date', [$startDate, $date])
            ->where('status', 'finalized')
            ->select('sale_date', 'quantity', 'weight')
            ->get()
            ->groupBy(function ($sale) {
                return Carbon::parse($sale->sale_date)->format('Y-m-d');
            })
            ->map(function ($daySales) {
                return [
                    'quantity' => $daySales->sum('quantity'),
                    'weight' => $daySales->sum('weight'),
                    'sales_count' => $daySales->count()
                ];
            })
            ->toArray();

        return $historicalSales;
    }

    /**
     * Calculate average daily sales
     */
    private function calculateAverageDailySales(array $historicalSales): float
    {
        if (empty($historicalSales)) {
            return 0;
        }

        $totalQuantity = collect($historicalSales)->sum('quantity');
        $daysCount = count($historicalSales);

        return $daysCount > 0 ? $totalQuantity / $daysCount : 0;
    }

    /**
     * Calculate sales trend
     */
    private function calculateSalesTrend(array $historicalSales): array
    {
        if (count($historicalSales) < 2) {
            return ['slope' => 0, 'intercept' => 0, 'r_squared' => 0];
        }

        $dates = array_keys($historicalSales);
        $quantities = array_column($historicalSales, 'quantity');

        // Simple linear regression
        $n = count($dates);
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $x = $i; // Use index as X
            $y = $quantities[$i];
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;

        return [
            'slope' => $slope,
            'intercept' => $intercept,
            'r_squared' => 0.8 // Simplified R-squared calculation
        ];
    }

    /**
     * Apply trend forecast
     */
    private function applyTrendForecast(array $historicalSales, array $trend): float
    {
        $lastIndex = count($historicalSales) - 1;
        $nextIndex = $lastIndex + 1;

        return $trend['slope'] * $nextIndex + $trend['intercept'];
    }

    /**
     * Store virtual data in livestock_batches data column
     */
    private function storeVirtualData(string $livestockId, string $date, array $virtualQuantity, array $salesData): void
    {
        $batches = LivestockBatch::where('livestock_id', $livestockId)
            ->where('status', 'active')
            ->get();

        foreach ($batches as $batch) {
            $existingData = $batch->data ?? [];

            // Initialize virtual data structure if not exists
            if (!isset($existingData['virtual_sales'])) {
                $existingData['virtual_sales'] = [];
            }
            if (!isset($existingData['virtual_depletion'])) {
                $existingData['virtual_depletion'] = [];
            }

            // Store virtual sales data
            $existingData['virtual_sales'][$date] = [
                'quantity' => $virtualQuantity['quantity'],
                'weight' => $virtualQuantity['weight'],
                'date' => $date,
                'status' => 'draft',
                'metadata' => [
                    'calculation_method' => $virtualQuantity['calculation_method'],
                    'calculation_timestamp' => now()->toIso8601String(),
                    'real_stock_reference' => $virtualQuantity['real_stock_reference'],
                    'sales_data' => $salesData
                ]
            ];

            // Update batch data
            $batch->update([
                'data' => $existingData,
                'updated_at' => now()
            ]);

            logDebugIfDebug('Virtual data stored in batch', [
                'batch_id' => $batch->id,
                'batch_name' => $batch->name,
                'date' => $date,
                'virtual_quantity' => $virtualQuantity['quantity'],
                'virtual_weight' => $virtualQuantity['weight']
            ]);
        }
    }

    /**
     * Get virtual quantity data for a specific date
     */
    public function getVirtualQuantityData(string $livestockId, string $date): ServiceResult
    {
        try {
            $batches = LivestockBatch::where('livestock_id', $livestockId)
                ->where('status', 'active')
                ->get();

            $virtualData = [];
            foreach ($batches as $batch) {
                $batchData = $batch->data ?? [];
                if (isset($batchData['virtual_sales'][$date])) {
                    $virtualData[] = [
                        'batch_id' => $batch->id,
                        'batch_name' => $batch->name,
                        'virtual_sales' => $batchData['virtual_sales'][$date],
                        'virtual_depletion' => $batchData['virtual_depletion'][$date] ?? null
                    ];
                }
            }

            return ServiceResult::success('Virtual quantity data retrieved successfully', $virtualData);
        } catch (Exception $e) {
            logErrorIfDebug('❌ Error in getVirtualQuantityData', [
                'livestock_id' => $livestockId,
                'date' => $date,
                'error' => $e->getMessage()
            ]);
            return ServiceResult::error('Failed to get virtual quantity data', $e);
        }
    }

    /**
     * Clear virtual data for a specific date
     */
    public function clearVirtualData(string $livestockId, string $date): ServiceResult
    {
        try {
            $batches = LivestockBatch::where('livestock_id', $livestockId)
                ->where('status', 'active')
                ->get();

            foreach ($batches as $batch) {
                $existingData = $batch->data ?? [];

                if (isset($existingData['virtual_sales'][$date])) {
                    unset($existingData['virtual_sales'][$date]);
                }
                if (isset($existingData['virtual_depletion'][$date])) {
                    unset($existingData['virtual_depletion'][$date]);
                }

                $batch->update([
                    'data' => $existingData,
                    'updated_at' => now()
                ]);
            }

            logInfoIfDebug('Virtual data cleared successfully', [
                'livestock_id' => $livestockId,
                'date' => $date
            ]);

            return ServiceResult::success('Virtual data cleared successfully');
        } catch (Exception $e) {
            logErrorIfDebug('❌ Error in clearVirtualData', [
                'livestock_id' => $livestockId,
                'date' => $date,
                'error' => $e->getMessage()
            ]);
            return ServiceResult::error('Failed to clear virtual data', $e);
        }
    }
}

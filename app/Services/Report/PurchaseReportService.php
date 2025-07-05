<?php

namespace App\Services\Report;

use App\Models\Farm;
use App\Models\LivestockPurchase;
use App\Models\FeedPurchase;
use App\Models\SupplyPurchase;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class PurchaseReportService
{
    /**
     * Generate livestock purchase report
     * Extracted from ReportsController::exportPembelianLivestock()
     * 
     * @param array $params
     * @return array
     */
    public function generateLivestockPurchaseReport(array $params): array
    {
        $farm = Farm::findOrFail($params['farm_id']);
        $startDate = Carbon::parse($params['start_date']);
        $endDate = Carbon::parse($params['end_date']);

        Log::info('Generating Livestock Purchase Report', [
            'farm_id' => $farm->id,
            'farm_name' => $farm->name,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'user_id' => Auth::id()
        ]);

        // Get livestock purchases data
        $purchases = LivestockPurchase::with(['farm', 'livestockPurchaseItems.livestock.coop'])
            ->where('farm_id', $farm->id)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->orderBy('tanggal', 'desc')
            ->get();

        // Process purchase data
        $processedData = $this->processLivestockPurchaseData($purchases);

        Log::info('Livestock Purchase Report generated successfully', [
            'farm_id' => $farm->id,
            'purchases_count' => $purchases->count(),
            'total_value' => $processedData['totals']['total_value'],
            'total_quantity' => $processedData['totals']['total_quantity']
        ]);

        return [
            'farm' => $farm,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'purchases' => $purchases,
            'processedData' => $processedData,
            'totals' => $processedData['totals']
        ];
    }

    /**
     * Generate feed purchase report
     * Extracted from ReportsController::exportPembelianPakan()
     * 
     * @param array $params
     * @return array
     */
    public function generateFeedPurchaseReport(array $params): array
    {
        $farm = Farm::findOrFail($params['farm_id']);
        $startDate = Carbon::parse($params['start_date']);
        $endDate = Carbon::parse($params['end_date']);

        Log::info('Generating Feed Purchase Report', [
            'farm_id' => $farm->id,
            'farm_name' => $farm->name,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'user_id' => Auth::id()
        ]);

        // Get feed purchases data
        $purchases = FeedPurchase::with(['farm', 'feedPurchaseItems.feed'])
            ->where('farm_id', $farm->id)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->orderBy('tanggal', 'desc')
            ->get();

        // Process purchase data
        $processedData = $this->processFeedPurchaseData($purchases);

        Log::info('Feed Purchase Report generated successfully', [
            'farm_id' => $farm->id,
            'purchases_count' => $purchases->count(),
            'total_value' => $processedData['totals']['total_value'],
            'total_quantity' => $processedData['totals']['total_quantity']
        ]);

        return [
            'farm' => $farm,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'purchases' => $purchases,
            'processedData' => $processedData,
            'totals' => $processedData['totals']
        ];
    }

    /**
     * Generate supply purchase report
     * Extracted from ReportsController::exportPembelianSupply()
     * 
     * @param array $params
     * @return array
     */
    public function generateSupplyPurchaseReport(array $params): array
    {
        $farm = Farm::findOrFail($params['farm_id']);
        $startDate = Carbon::parse($params['start_date']);
        $endDate = Carbon::parse($params['end_date']);

        Log::info('Generating Supply Purchase Report', [
            'farm_id' => $farm->id,
            'farm_name' => $farm->name,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'user_id' => Auth::id()
        ]);

        // Get supply purchases data
        $purchases = SupplyPurchase::with(['farm', 'supplyPurchaseItems.supply'])
            ->where('farm_id', $farm->id)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->orderBy('tanggal', 'desc')
            ->get();

        // Process purchase data
        $processedData = $this->processSupplyPurchaseData($purchases);

        Log::info('Supply Purchase Report generated successfully', [
            'farm_id' => $farm->id,
            'purchases_count' => $purchases->count(),
            'total_value' => $processedData['totals']['total_value'],
            'total_quantity' => $processedData['totals']['total_quantity']
        ]);

        return [
            'farm' => $farm,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'purchases' => $purchases,
            'processedData' => $processedData,
            'totals' => $processedData['totals']
        ];
    }

    /**
     * Process livestock purchase data
     * 
     * @param \Illuminate\Database\Eloquent\Collection $purchases
     * @return array
     */
    private function processLivestockPurchaseData($purchases): array
    {
        $processedData = [];
        $totals = [
            'total_quantity' => 0,
            'total_value' => 0,
            'total_purchases' => 0,
        ];

        foreach ($purchases as $purchase) {
            $purchaseData = [
                'id' => $purchase->id,
                'tanggal' => $purchase->tanggal,
                'supplier' => $purchase->supplier ?? '-',
                'items' => [],
                'subtotal_quantity' => 0,
                'subtotal_value' => 0,
            ];

            foreach ($purchase->livestockPurchaseItems as $item) {
                $itemData = [
                    'livestock_name' => $item->livestock->name ?? '-',
                    'coop_name' => $item->livestock->coop->name ?? '-',
                    'quantity' => $item->quantity,
                    'price_per_unit' => $item->price_per_unit,
                    'total_price' => $item->quantity * $item->price_per_unit,
                    'notes' => $item->notes ?? '-'
                ];

                $purchaseData['items'][] = $itemData;
                $purchaseData['subtotal_quantity'] += $item->quantity;
                $purchaseData['subtotal_value'] += $itemData['total_price'];
            }

            $processedData[] = $purchaseData;
            $totals['total_quantity'] += $purchaseData['subtotal_quantity'];
            $totals['total_value'] += $purchaseData['subtotal_value'];
            $totals['total_purchases']++;
        }

        Log::debug('Livestock purchase data processed', [
            'processed_count' => count($processedData),
            'total_quantity' => $totals['total_quantity'],
            'total_value' => $totals['total_value']
        ]);

        return [
            'data' => $processedData,
            'totals' => $totals
        ];
    }

    /**
     * Process feed purchase data
     * 
     * @param \Illuminate\Database\Eloquent\Collection $purchases
     * @return array
     */
    private function processFeedPurchaseData($purchases): array
    {
        $processedData = [];
        $totals = [
            'total_quantity' => 0,
            'total_value' => 0,
            'total_purchases' => 0,
        ];

        foreach ($purchases as $purchase) {
            $purchaseData = [
                'id' => $purchase->id,
                'tanggal' => $purchase->tanggal,
                'supplier' => $purchase->supplier ?? '-',
                'items' => [],
                'subtotal_quantity' => 0,
                'subtotal_value' => 0,
            ];

            foreach ($purchase->feedPurchaseItems as $item) {
                $itemData = [
                    'feed_name' => $item->feed->name ?? '-',
                    'quantity' => $item->quantity,
                    'unit' => $item->feed->unit ?? '-',
                    'price_per_unit' => $item->price_per_unit,
                    'total_price' => $item->quantity * $item->price_per_unit,
                    'notes' => $item->notes ?? '-'
                ];

                $purchaseData['items'][] = $itemData;
                $purchaseData['subtotal_quantity'] += $item->quantity;
                $purchaseData['subtotal_value'] += $itemData['total_price'];
            }

            $processedData[] = $purchaseData;
            $totals['total_quantity'] += $purchaseData['subtotal_quantity'];
            $totals['total_value'] += $purchaseData['subtotal_value'];
            $totals['total_purchases']++;
        }

        Log::debug('Feed purchase data processed', [
            'processed_count' => count($processedData),
            'total_quantity' => $totals['total_quantity'],
            'total_value' => $totals['total_value']
        ]);

        return [
            'data' => $processedData,
            'totals' => $totals
        ];
    }

    /**
     * Process supply purchase data
     * 
     * @param \Illuminate\Database\Eloquent\Collection $purchases
     * @return array
     */
    private function processSupplyPurchaseData($purchases): array
    {
        $processedData = [];
        $totals = [
            'total_quantity' => 0,
            'total_value' => 0,
            'total_purchases' => 0,
        ];

        foreach ($purchases as $purchase) {
            $purchaseData = [
                'id' => $purchase->id,
                'tanggal' => $purchase->tanggal,
                'supplier' => $purchase->supplier ?? '-',
                'items' => [],
                'subtotal_quantity' => 0,
                'subtotal_value' => 0,
            ];

            foreach ($purchase->supplyPurchaseItems as $item) {
                $itemData = [
                    'supply_name' => $item->supply->name ?? '-',
                    'quantity' => $item->quantity,
                    'unit' => $item->supply->unit ?? '-',
                    'price_per_unit' => $item->price_per_unit,
                    'total_price' => $item->quantity * $item->price_per_unit,
                    'notes' => $item->notes ?? '-'
                ];

                $purchaseData['items'][] = $itemData;
                $purchaseData['subtotal_quantity'] += $item->quantity;
                $purchaseData['subtotal_value'] += $itemData['total_price'];
            }

            $processedData[] = $purchaseData;
            $totals['total_quantity'] += $purchaseData['subtotal_quantity'];
            $totals['total_value'] += $purchaseData['subtotal_value'];
            $totals['total_purchases']++;
        }

        Log::debug('Supply purchase data processed', [
            'processed_count' => count($processedData),
            'total_quantity' => $totals['total_quantity'],
            'total_value' => $totals['total_value']
        ]);

        return [
            'data' => $processedData,
            'totals' => $totals
        ];
    }

    /**
     * Export livestock purchase report
     * 
     * @param array $data
     * @param string $format
     * @return mixed
     */
    public function exportLivestockPurchase(array $data, string $format)
    {
        switch ($format) {
            case 'excel':
                return $this->exportLivestockPurchaseToExcel($data);
            case 'pdf':
                return $this->exportLivestockPurchaseToPdf($data);
            case 'csv':
                return $this->exportLivestockPurchaseToCsv($data);
            case 'html':
            default:
                return view('pages.reports.pembelian-livestock', $data);
        }
    }

    /**
     * Export feed purchase report
     * 
     * @param array $data
     * @param string $format
     * @return mixed
     */
    public function exportFeedPurchase(array $data, string $format)
    {
        switch ($format) {
            case 'excel':
                return $this->exportFeedPurchaseToExcel($data);
            case 'pdf':
                return $this->exportFeedPurchaseToPdf($data);
            case 'csv':
                return $this->exportFeedPurchaseToCsv($data);
            case 'html':
            default:
                return view('pages.reports.pembelian-pakan', $data);
        }
    }

    /**
     * Export supply purchase report
     * 
     * @param array $data
     * @param string $format
     * @return mixed
     */
    public function exportSupplyPurchase(array $data, string $format)
    {
        switch ($format) {
            case 'excel':
                return $this->exportSupplyPurchaseToExcel($data);
            case 'pdf':
                return $this->exportSupplyPurchaseToPdf($data);
            case 'csv':
                return $this->exportSupplyPurchaseToCsv($data);
            case 'html':
            default:
                return view('pages.reports.pembelian-supply', $data);
        }
    }

    /**
     * Validate purchase report parameters
     * 
     * @param array $params
     * @return array
     */
    public function validateParams(array $params): array
    {
        $rules = [
            'farm_id' => 'required|uuid|exists:farms,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ];

        $validator = validator($params, $rules);

        if ($validator->fails()) {
            Log::warning('Purchase report validation failed', [
                'errors' => $validator->errors()->toArray(),
                'params' => $params
            ]);
            throw new \InvalidArgumentException('Invalid parameters: ' . implode(', ', $validator->errors()->all()));
        }

        return $params;
    }

    /**
     * Get purchase statistics
     * 
     * @param array $data
     * @return array
     */
    public function getPurchaseStatistics(array $data): array
    {
        $purchases = $data['purchases'];

        $stats = [
            'total_purchases' => $purchases->count(),
            'total_value' => $data['totals']['total_value'],
            'total_quantity' => $data['totals']['total_quantity'],
            'average_value_per_purchase' => $purchases->count() > 0 ? $data['totals']['total_value'] / $purchases->count() : 0,
            'unique_suppliers' => $purchases->pluck('supplier')->filter()->unique()->count(),
        ];

        Log::debug('Purchase statistics calculated', $stats);

        return $stats;
    }

    // Export methods (placeholders for now - would be implemented with actual export logic)

    private function exportLivestockPurchaseToExcel(array $data)
    {
        Log::info('Exporting livestock purchase to Excel');
        return response()->json(['message' => 'Excel export not implemented yet']);
    }

    private function exportLivestockPurchaseToPdf(array $data)
    {
        Log::info('Exporting livestock purchase to PDF');
        return response()->json(['message' => 'PDF export not implemented yet']);
    }

    private function exportLivestockPurchaseToCsv(array $data)
    {
        Log::info('Exporting livestock purchase to CSV');
        return response()->json(['message' => 'CSV export not implemented yet']);
    }

    private function exportFeedPurchaseToExcel(array $data)
    {
        Log::info('Exporting feed purchase to Excel');
        return response()->json(['message' => 'Excel export not implemented yet']);
    }

    private function exportFeedPurchaseToPdf(array $data)
    {
        Log::info('Exporting feed purchase to PDF');
        return response()->json(['message' => 'PDF export not implemented yet']);
    }

    private function exportFeedPurchaseToCsv(array $data)
    {
        Log::info('Exporting feed purchase to CSV');
        return response()->json(['message' => 'CSV export not implemented yet']);
    }

    private function exportSupplyPurchaseToExcel(array $data)
    {
        Log::info('Exporting supply purchase to Excel');
        return response()->json(['message' => 'Excel export not implemented yet']);
    }

    private function exportSupplyPurchaseToPdf(array $data)
    {
        Log::info('Exporting supply purchase to PDF');
        return response()->json(['message' => 'PDF export not implemented yet']);
    }

    private function exportSupplyPurchaseToCsv(array $data)
    {
        Log::info('Exporting supply purchase to CSV');
        return response()->json(['message' => 'CSV export not implemented yet']);
    }

    /**
     * Export livestock purchase report (alias for exportLivestockPurchase)
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function exportLivestockPurchaseReport($request)
    {
        try {
            // Validate and generate report
            $params = $this->validateParams($request->all());
            $reportData = $this->generateLivestockPurchaseReport($params);

            // Export in requested format
            $format = $request->export_format ?? 'html';
            return $this->exportLivestockPurchase($reportData, $format);
        } catch (\Exception $e) {
            Log::error('Error exporting livestock purchase report: ' . $e->getMessage());
            Log::debug('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Export feed purchase report (alias for exportFeedPurchase)
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function exportFeedPurchaseReport($request)
    {
        try {
            // Validate and generate report
            $params = $this->validateParams($request->all());
            $reportData = $this->generateFeedPurchaseReport($params);

            // Export in requested format
            $format = $request->export_format ?? 'html';
            return $this->exportFeedPurchase($reportData, $format);
        } catch (\Exception $e) {
            Log::error('Error exporting feed purchase report: ' . $e->getMessage());
            Log::debug('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Export supply purchase report (alias for exportSupplyPurchase)
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function exportSupplyPurchaseReport($request)
    {
        try {
            // Validate and generate report
            $params = $this->validateParams($request->all());
            $reportData = $this->generateSupplyPurchaseReport($params);

            // Export in requested format
            $format = $request->export_format ?? 'html';
            return $this->exportSupplyPurchase($reportData, $format);
        } catch (\Exception $e) {
            Log::error('Error exporting supply purchase report: ' . $e->getMessage());
            Log::debug('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }
}

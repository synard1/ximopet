<?php

namespace App\Http\Controllers;

use App\Models\Farm;
use App\Models\LivestockPurchase;
use App\Models\FeedPurchase;
use App\Models\FeedPurchaseItem;
use App\Models\SupplyPurchaseBatch;
use App\Models\Partner;
use App\Models\Expedition;
use App\Models\Feed;
use App\Models\Supply;
use App\Models\Livestock;
use App\Models\Coop;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\Auth;

class PurchaseReportsController extends Controller
{
    /**
     * Display Livestock Purchase Report Index
     */
    public function indexPembelianLivestock()
    {
        $user = Auth::user();
        $farms = Farm::where('company_id', $user->company_id)->get();
        $partners = Partner::where('type', 'Supplier')->where('company_id', $user->company_id)->get();
        $expeditions = Expedition::where('company_id', $user->company_id)->get();
        $livestocks = Livestock::with(['farm', 'coop'])->where('company_id', $user->company_id)->get()->map(function ($l) {
            return [
                'id' => $l->id,
                'farm_id' => $l->farm_id,
                'coop_id' => $l->coop_id,
                'coop_name' => $l->coop ? $l->coop->name : '-',
                'name' => $l->name,
                'start_date' => $l->start_date,
            ];
        })->values()->all();

        Log::info('Livestock Purchase Report Index accessed', [
            'user_id' => $user->id,
            'farms_count' => $farms->count(),
            'partners_count' => $partners->count()
        ]);

        return view('pages.reports.index_report_pembelian_livestock', compact(['farms', 'partners', 'expeditions', 'livestocks']));
    }

    /**
     * Display Feed Purchase Report Index
     */
    public function indexPembelianPakan()
    {
        $user = Auth::user();
        $farms = Farm::where('company_id', $user->company_id)->get();
        $partners = Partner::where('type', 'Supplier')->where('company_id', $user->company_id)->get();
        $expeditions = Expedition::where('company_id', $user->company_id)->get();
        $feeds = Feed::where('company_id', $user->company_id)->get();
        $livestocks = Livestock::with(['farm', 'coop'])->where('company_id', $user->company_id)->get()->map(function ($l) {
            return [
                'id' => $l->id,
                'farm_id' => $l->farm_id,
                'coop_id' => $l->coop_id,
                'coop_name' => $l->coop ? $l->coop->name : '-',
                'name' => $l->name,
                'start_date' => $l->start_date,
            ];
        })->values()->all();

        Log::info('Feed Purchase Report Index accessed', [
            'user_id' => $user->id,
            'farms_count' => $farms->count(),
            'feeds_count' => $feeds->count()
        ]);

        return view('pages.reports.index_report_pembelian_pakan', compact(['farms', 'partners', 'expeditions', 'feeds', 'livestocks']));
    }

    /**
     * Display Supply Purchase Report Index  
     */
    public function indexPembelianSupply()
    {
        $user = Auth::user();
        $farms = Farm::where('company_id', $user->company_id)->get();
        $partners = Partner::where('type', 'Supplier')->where('company_id', $user->company_id)->get();
        $expeditions = Expedition::where('company_id', $user->company_id)->get();
        $supplies = Supply::where('company_id', $user->company_id)->get();
        $livestocks = Livestock::with(['farm', 'coop'])->where('company_id', $user->company_id)->get()->map(function ($l) {
            return [
                'id' => $l->id,
                'farm_id' => $l->farm_id,
                'coop_id' => $l->coop_id,
                'coop_name' => $l->coop ? $l->coop->name : '-',
                'name' => $l->name,
                'start_date' => $l->start_date,
            ];
        })->values()->all();

        Log::info('Supply Purchase Report Index accessed', [
            'user_id' => $user->id,
            'farms_count' => $farms->count(),
            'supplies_count' => $supplies->count()
        ]);

        return view('pages.reports.index_report_pembelian_supply', compact(['farms', 'partners', 'expeditions', 'supplies', 'livestocks']));
    }

    /**
     * Export Livestock Purchase Report
     */
    public function exportPembelianLivestock(Request $request)
    {
        $user = Auth::user();
        // Validasi input
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'farm_id' => 'nullable|exists:farms,id',
            'supplier_id' => 'nullable|exists:partners,id',
            'expedition_id' => 'nullable|exists:expeditions,id',
            'status' => 'nullable|in:draft,confirmed,arrived,completed',
            'export_format' => 'nullable|in:html,excel,pdf,csv'
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $exportFormat = $request->export_format ?? 'html';

        Log::info('Export Livestock Purchase Report', [
            'user_id' => $user->id,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'export_format' => $exportFormat,
            'filters' => $request->only(['farm_id', 'supplier_id', 'expedition_id', 'status'])
        ]);

        // Ambil data pembelian livestock
        $purchasesQuery = LivestockPurchase::with([
            // 'farm',
            'supplier',
            'expedition',
            'details.livestockStrain',
            // 'details.unit'
        ])
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->when($request->farm_id, function ($query) use ($request) {
                return $query->where('farm_id', $request->farm_id);
            })
            ->when($request->supplier_id, function ($query) use ($request) {
                return $query->where('supplier_id', $request->supplier_id);
            })
            ->when($request->expedition_id, function ($query) use ($request) {
                return $query->where('expedition_id', $request->expedition_id);
            })
            ->when($request->status, function ($query) use ($request) {
                return $query->where('status', $request->status);
            })
            ->orderBy('tanggal', 'asc')
            ->orderBy('invoice_number', 'asc');

        $purchases = $purchasesQuery->get();

        if ($purchases->isEmpty()) {
            Log::warning('No Livestock Purchase data found for export', [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'filters' => $request->only(['farm_id', 'supplier_id', 'expedition_id', 'status'])
            ]);

            return response()->json([
                'error' => 'Tidak ada data pembelian livestock untuk periode ' .
                    $startDate->format('d-M-Y') . ' s.d. ' . $endDate->format('d-M-Y')
            ], 404);
        }

        // Hitung summary data
        $summary = [
            'period' => $startDate->format('d-M-Y') . ' s.d. ' . $endDate->format('d-M-Y'),
            'total_purchases' => $purchases->count(),
            'total_suppliers' => $purchases->unique('supplier_id')->count(),
            'total_farms' => $purchases->unique('farm_id')->count(),
            'total_value' => $purchases->sum(function ($purchase) {
                return $purchase->details->sum(function ($item) {
                    return $item->quantity * $item->price_per_unit;
                });
            }),
            'total_quantity' => $purchases->sum(function ($purchase) {
                return $purchase->details->sum('quantity');
            }),
            'by_status' => $purchases->groupBy('status')->map->count(),
            'by_farm' => $purchases->groupBy('farm.name')->map->count(),
            'by_supplier' => $purchases->groupBy('supplier.name')->map->count()
        ];

        $exportData = [
            'purchases' => $purchases,
            'summary' => $summary,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'farm' => $request->farm_id ? Farm::find($request->farm_id) : null,
                'supplier' => $request->supplier_id ? Partner::find($request->supplier_id) : null,
                'expedition' => $request->expedition_id ? Expedition::find($request->expedition_id) : null,
                'status' => $request->status
            ]
        ];

        // dd($exportData);

        // Route ke format export yang sesuai
        switch ($exportFormat) {
            case 'excel':
                return $this->exportLivestockPurchaseToExcel($exportData);
            case 'pdf':
                return $this->exportLivestockPurchaseToPdf($exportData);
            case 'csv':
                return $this->exportLivestockPurchaseToCsv($exportData);
            default:
                return $this->exportLivestockPurchaseToHtml($exportData);
        }
    }

    /**
     * Export Feed Purchase Report
     */
    public function exportPembelianPakan(Request $request)
    {
        $user = Auth::user();
        // Validasi input
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'farm_id' => 'nullable|exists:farms,id',
            'livestock_id' => 'nullable|exists:livestocks,id',
            'supplier_id' => 'nullable|exists:partners,id',
            'feed_id' => 'nullable|exists:feeds,id',
            'status' => 'nullable|in:draft,pending,confirmed,in_transit,arrived,cancelled,completed',
            'export_format' => 'nullable|in:html,excel,pdf,csv'
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $exportFormat = $request->export_format ?? 'html';

        Log::info('Export Feed Purchase Report', [
            'user_id' => $user->id,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'export_format' => $exportFormat,
            'filters' => $request->only(['farm_id', 'livestock_id', 'supplier_id', 'feed_id', 'status'])
        ]);

        // Ambil data pembelian pakan menggunakan struktur baru
        $purchasesQuery = FeedPurchase::with([
            'supplier',
            'expedition',
            'livestock.farm',
            'livestock.coop',
            'feedPurchaseItems.feed',
            'feedPurchaseItems.unit'
        ])
            ->whereBetween('date', [$startDate, $endDate])
            ->when($request->supplier_id, function ($query) use ($request) {
                return $query->where('supplier_id', $request->supplier_id);
            })
            ->when($request->status, function ($query) use ($request) {
                return $query->where('status', $request->status);
            })
            ->when($request->farm_id, function ($query) use ($request) {
                return $query->where('farm_id', $request->farm_id);
            })
            ->when($request->livestock_id, function ($query) use ($request) {
                return $query->where('livestock_id', $request->livestock_id);
            })
            ->when($request->feed_id, function ($query) use ($request) {
                return $query->whereHas('feedPurchaseItems', function ($q) use ($request) {
                    $q->where('feed_id', $request->feed_id);
                });
            })
            ->orderBy('date', 'asc')
            ->orderBy('invoice_number', 'asc');

        $purchases = $purchasesQuery->get();

        if ($purchases->isEmpty()) {
            Log::warning('No Feed Purchase data found for export', [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'filters' => $request->only(['farm_id', 'livestock_id', 'supplier_id', 'feed_id', 'status'])
            ]);

            return response()->json([
                'error' => 'Tidak ada data pembelian pakan untuk periode ' .
                    $startDate->format('d-M-Y') . ' s.d. ' . $endDate->format('d-M-Y')
            ], 404);
        }

        // Hitung summary data berdasarkan struktur baru
        $summary = [
            'period' => $startDate->format('d-M-Y') . ' s.d. ' . $endDate->format('d-M-Y'),
            'total_purchases' => $purchases->count(),
            'total_items' => $purchases->sum(function ($purchase) {
                return $purchase->feedPurchaseItems->count();
            }),
            'total_suppliers' => $purchases->unique('supplier_id')->count(),
            'total_farms' => $purchases->unique('farm_id')->count(),
            'total_value' => $purchases->sum(function ($purchase) {
                return $purchase->feedPurchaseItems->sum(function ($item) {
                    return $item->quantity * $item->price_per_unit;
                }) + $purchase->expedition_fee;
            }),
            'total_quantity' => $purchases->sum(function ($purchase) {
                return $purchase->feedPurchaseItems->sum('converted_quantity');
            }),
            'by_status' => $purchases->groupBy('status')->map->count(),
            'by_supplier' => $purchases->groupBy('supplier.name')->map->count(),
            'by_feed' => $purchases->flatMap->feedPurchaseItems->groupBy('feed.name')->map->count()
        ];

        $exportData = [
            'purchases' => $purchases,
            'summary' => $summary,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'farm' => $request->farm_id ? Farm::find($request->farm_id) : null,
                'livestock' => $request->livestock_id ? Livestock::find($request->livestock_id) : null,
                'supplier' => $request->supplier_id ? Partner::find($request->supplier_id) : null,
                'feed' => $request->feed_id ? Feed::find($request->feed_id) : null,
                'status' => $request->status
            ]
        ];

        // Route ke format export yang sesuai
        switch ($exportFormat) {
            case 'excel':
                return $this->exportFeedPurchaseToExcel($exportData);
            case 'pdf':
                return $this->exportFeedPurchaseToPdf($exportData);
            case 'csv':
                return $this->exportFeedPurchaseToCsv($exportData);
            default:
                return $this->exportFeedPurchaseToHtml($exportData);
        }
    }

    /**
     * Export Supply Purchase Report
     */
    public function exportPembelianSupply(Request $request)
    {
        $user = Auth::user();
        // Validasi input
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'farm' => 'nullable|exists:farms,id',
            'coop' => 'nullable|exists:coops,id',
            'tahun' => 'nullable|integer',
            'supplier_id' => 'nullable|exists:partners,id',
            'supplier' => 'nullable|exists:partners,id',
            'supply_id' => 'nullable|exists:supplies,id',
            'supply' => 'nullable|exists:supplies,id',
            'status' => 'nullable|in:draft,confirmed,arrived,completed',
            'export_format' => 'nullable|in:html,excel,pdf,csv'
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $exportFormat = $request->export_format ?? 'html';

        $supplierId = $request->supplier_id ?? $request->supplier;
        $supplyId = $request->supply_id ?? $request->supply;
        $farmId = $request->farm;
        $coopId = $request->coop;
        $tahun = $request->tahun;

        Log::info('Export Supply Purchase Report', [
            'user_id' => $user->id,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'export_format' => $exportFormat,
            'filters' => $request->all()
        ]);

        // Ambil data pembelian supply
        $batchesQuery = SupplyPurchaseBatch::with([
            'supplier',
            'expedition',
            'supplyPurchases.supply',
            'supplyPurchases.unit',
            'supplyPurchases.farm',
        ])
            ->whereBetween('date', [$startDate, $endDate])
            ->when($supplierId, function ($query) use ($supplierId) {
                return $query->where('supplier_id', $supplierId);
            })
            ->when($request->status, function ($query) use ($request) {
                return $query->where('status', $request->status);
            })
            ->when($farmId, function ($query) use ($farmId) {
                return $query->whereHas('supplyPurchases', function ($q) use ($farmId) {
                    $q->where('farm_id', $farmId);
                });
            })
            ->when($tahun, function ($query) use ($tahun) {
                return $query->whereHas('supplyPurchases', function ($q) use ($tahun) {
                    $q->whereYear('date', $tahun);
                });
            })
            ->when($supplyId, function ($query) use ($supplyId) {
                return $query->whereHas('supplyPurchases', function ($q) use ($supplyId) {
                    $q->where('supply_id', $supplyId);
                });
            });

        $batches = $batchesQuery->get();

        if ($batches->isEmpty()) {
            Log::warning('No Supply Purchase data found for export', [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'filters' => $request->all()
            ]);

            return response()->json([
                'error' => 'Tidak ada data pembelian supply untuk periode ' .
                    $startDate->format('d-M-Y') . ' s.d. ' . $endDate->format('d-M-Y')
            ], 404);
        }

        // Hitung summary data
        $summary = [
            'period' => $startDate->format('d-M-Y') . ' s.d. ' . $endDate->format('d-M-Y'),
            'total_batches' => $batches->count(),
            'total_purchases' => $batches->sum(function ($batch) {
                return $batch->supplyPurchases->count();
            }),
            'total_suppliers' => $batches->unique('supplier_id')->count(),
            'total_farms' => $batches->flatMap(function ($batch) {
                return $batch->supplyPurchases->pluck('farm_id');
            })->unique()->count(),
            'total_value' => $batches->sum(function ($batch) {
                return $batch->supplyPurchases->sum(function ($purchase) {
                    return $purchase->quantity * $purchase->price_per_unit;
                }) + $batch->expedition_fee;
            }),
            'total_quantity' => $batches->sum(function ($batch) {
                return $batch->supplyPurchases->sum('converted_quantity');
            }),
            'by_status' => $batches->groupBy('status')->map->count(),
            'by_supplier' => $batches->groupBy('supplier.name')->map->count(),
            'by_supply' => $batches->flatMap->supplyPurchases->groupBy('supply.name')->map->count()
        ];

        $exportData = [
            'batches' => $batches,
            'summary' => $summary,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'farm' => $farmId ? Farm::find($farmId) : null,
                'coop' => $coopId ? Coop::find($coopId) : null,
                'supplier' => $supplierId ? Partner::find($supplierId) : null,
                'supply' => $supplyId ? Supply::find($supplyId) : null,
                'status' => $request->status
            ]
        ];

        // Route ke format export yang sesuai
        switch ($exportFormat) {
            case 'excel':
                return $this->exportSupplyPurchaseToExcel($exportData);
            case 'pdf':
                return $this->exportSupplyPurchaseToPdf($exportData);
            case 'csv':
                return $this->exportSupplyPurchaseToCsv($exportData);
            default:
                return $this->exportSupplyPurchaseToHtml($exportData);
        }
    }

    /**
     * Export Supply Purchase Report to HTML
     */
    public function exportPembelianSupplyHtml(Request $request)
    {
        $data = $this->getSupplyPurchaseData($request);
        return view('pages.reports.pembelian-supply-html', $data);
    }

    /**
     * Get Supply Purchase Data
     */
    private function getSupplyPurchaseData(Request $request)
    {
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $supplierId = $request->supplier_id ?? $request->supplier;
        $supplyId = $request->supply_id ?? $request->supply;
        $farmId = $request->farm;
        $coopId = $request->coop;
        $tahun = $request->tahun;

        // Ambil data pembelian supply
        $batchesQuery = SupplyPurchaseBatch::with([
            'supplier',
            'expedition',
            'supplyPurchases.supply',
            'supplyPurchases.unit',
            'supplyPurchases.farm',
        ])
            ->whereBetween('date', [$startDate, $endDate])
            ->when($supplierId, fn($q) => $q->where('supplier_id', $supplierId))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($farmId, function ($q) use ($farmId) {
                $q->whereHas('supplyPurchases', fn($sq) => $sq->where('farm_id', $farmId));
            })
            ->when($tahun, function ($q) use ($tahun) {
                $q->whereHas('supplyPurchases', fn($sq) => $sq->whereYear('date', $tahun));
            })
            ->when($supplyId, function ($q) use ($supplyId) {
                $q->whereHas('supplyPurchases', fn($sq) => $sq->where('supply_id', $supplyId));
            })
            ->when($request->invoice_number, fn($q) => $q->where('invoice_number', $request->invoice_number));

        $batches = $batchesQuery->get();

        if ($batches->isEmpty()) {
            return response()->json([
                'error' => 'Tidak ada data pembelian supply untuk periode ' .
                    $startDate->format('d-M-Y') . ' s.d. ' . $endDate->format('d-M-Y')
            ], 404);
        }

        // Hitung summary data
        $summary = [
            'period' => $startDate->format('d-M-Y') . ' s.d. ' . $endDate->format('d-M-Y'),
            'total_batches' => $batches->count(),
            'total_purchases' => $batches->sum(fn($batch) => $batch->supplyPurchases->count()),
            'total_suppliers' => $batches->unique('supplier_id')->count(),
            'total_farms' => $batches->flatMap(fn($batch) => $batch->supplyPurchases->pluck('farm_id'))->unique()->count(),
            'total_value' => $batches->sum(
                fn($batch) =>
                $batch->supplyPurchases->sum(fn($purchase) => $purchase->quantity * $purchase->price_per_unit) + $batch->expedition_fee
            ),
            'total_quantity' => $batches->sum(fn($batch) => $batch->supplyPurchases->sum('converted_quantity')),
            'by_status' => $batches->groupBy('status')->map->count(),
            'by_supplier' => $batches->groupBy('supplier.name')->map->count(),
            'by_supply' => $batches->flatMap->supplyPurchases->groupBy('supply.name')->map->count()
        ];

        $invoiceDetail = null;
        if ($request->invoice_number) {
            $invoiceDetail = SupplyPurchaseBatch::with(['supplier', 'supplyPurchases.supply', 'supplyPurchases.unit'])
                ->where('invoice_number', $request->invoice_number)
                ->first();
        }

        return [
            'batches' => $batches,
            'summary' => $summary,
            'invoiceDetail' => $invoiceDetail,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'farm' => $farmId ? Farm::find($farmId) : null,
                'coop' => $coopId ? Coop::find($coopId) : null,
                'supplier' => $supplierId ? Partner::find($supplierId) : null,
                'supply' => $supplyId ? Supply::find($supplyId) : null,
                'status' => $request->status
            ]
        ];
    }

    // Helper methods for Livestock Purchase Export
    private function exportLivestockPurchaseToHtml($data)
    {
        return view('pages.reports.pembelian-livestock', $data);
    }

    private function exportLivestockPurchaseToExcel($data)
    {
        // Implement Excel export logic
        return response()->json(['message' => 'Excel export akan segera tersedia, fitur ini dalam tahap pengembangan'], 200);
    }

    private function exportLivestockPurchaseToPdf($data)
    {
        // Implement PDF export logic  
        return response()->json(['message' => 'PDF export akan segera tersedia, fitur ini dalam tahap pengembangan'], 200);
    }

    private function exportLivestockPurchaseToCsv($data)
    {
        // Implement CSV export logic
        return response()->json(['message' => 'CSV export akan segera tersedia, fitur ini dalam tahap pengembangan'], 200);
    }

    // Helper methods for Feed Purchase Export
    private function exportFeedPurchaseToHtml($data)
    {
        // Transform data structure to match view expectations
        $transformedData = [
            'batches' => $data['purchases'], // Keep 'batches' key for view compatibility
            'summary' => [
                'period' => $data['summary']['period'], // Add period key
                'total_batches' => $data['summary']['total_purchases'], // Map total_purchases to total_batches
                'total_purchases' => $data['summary']['total_items'], // Map total_items to total_purchases
                'total_quantity' => $data['summary']['total_quantity'],
                'total_value' => $data['summary']['total_value'],
                'by_status' => $data['summary']['by_status'],
                'by_supplier' => $data['summary']['by_supplier'],
                'by_feed' => $data['summary']['by_feed']
            ],
            'filters' => $data['filters']
        ];

        return view('pages.reports.pembelian-pakan', $transformedData);
    }

    private function exportFeedPurchaseToExcel($data)
    {
        // Implement Excel export logic
        return response()->view('pages.reports.export_info', [
            'message' => 'Excel export akan segera tersedia, fitur ini dalam tahap pengembangan'
        ]);
    }

    private function exportFeedPurchaseToPdf($data)
    {
        // Implement PDF export logic
        return response()->view('pages.reports.export_info', [
            'message' => 'PDF export akan segera tersedia, fitur ini dalam tahap pengembangan'
        ]);
    }

    private function exportFeedPurchaseToCsv($data)
    {
        // Implement CSV export logic
        return response()->view('pages.reports.export_info', [
            'message' => 'CSV export akan segera tersedia, fitur ini dalam tahap pengembangan'
        ]);
    }

    // Helper methods for Supply Purchase Export
    private function exportSupplyPurchaseToHtml($data)
    {
        return view('pages.reports.pembelian-supply', $data);
    }

    private function exportSupplyPurchaseToExcel($data)
    {
        // Implement Excel export logic
        return response()->json(['message' => 'Excel export akan segera tersedia, fitur ini dalam tahap pengembangan'], 200);
    }

    private function exportSupplyPurchaseToPdf($data)
    {
        // Implement PDF export logic
        return response()->json(['message' => 'PDF export akan segera tersedia, fitur ini dalam tahap pengembangan'], 200);
    }

    private function exportSupplyPurchaseToCsv($data)
    {
        // Implement CSV export logic
        return response()->json(['message' => 'CSV export akan segera tersedia, fitur ini dalam tahap pengembangan'], 200);
    }
}

<?php

namespace App\Services\Report;

use App\Models\TernakJual;
use App\Models\Ternak;
use App\Models\TransaksiJual;
use App\Models\LivestockCost;
use App\Models\Farm;
use App\Models\Livestock;
use App\Models\LivestockSales;
use App\Models\LivestockSalesItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

/**
 * Service untuk generate laporan penjualan ternak
 * 
 * @property ReportDataAccessService $dataAccessService
 */
class SalesReportService
{
    protected $dataAccessService;

    public function __construct(ReportDataAccessService $dataAccessService)
    {
        $this->dataAccessService = $dataAccessService;
    }

    /**
     * Generate sales report data menggunakan model LivestockSales yang baru
     * 
     * @param Request $request
     * @return array
     */
    public function generateSalesReport(Request $request)
    {
        try {
            Log::info('Starting sales report generation', [
                'request_data' => $request->all()
            ]);

            // Handle optional periode parameter
            $livestockId = $request->periode;
            $includeTempSales = $request->boolean('include_temp_sales', false);
            $includeDraftSales = $request->boolean('include_draft_sales', false);

            // If periode is provided, validate it exists
            if ($livestockId) {
                $request->validate([
                    'periode' => 'exists:livestocks,id'
                ]);
            } else {
                Log::info('No specific livestock selected, will search for all available data', [
                    'farm' => $request->farm,
                    'coop' => $request->coop,
                    'tahun' => $request->tahun
                ]);
            }

            // Load livestock dengan relationship yang diperlukan
            $livestock = null;
            if ($livestockId) {
                $livestock = Livestock::with([
                    'coop',
                    'farm',
                    'livestockDepletion',
                    'livestockSales' => function ($query) use ($includeDraftSales) {
                        $query->with(['customer', 'expedition', 'livestockSalesItems'])
                            ->where('status', '!=', LivestockSales::STATUS_CANCELLED);

                        // Include draft sales if requested
                        if (!$includeDraftSales) {
                            $query->whereNotIn('status', [
                                LivestockSales::STATUS_DRAFT,
                                LivestockSales::STATUS_PENDING
                            ]);
                        }

                        $query->orderBy('date', 'ASC');
                    }
                ])->findOrFail($livestockId);

                Log::info('Livestock loaded successfully', [
                    'livestock_id' => $livestockId,
                    'livestock_name' => $livestock->name ?? 'N/A',
                    'include_temp_sales' => $includeTempSales,
                    'include_draft_sales' => $includeDraftSales
                ]);
            } else {
                Log::info('No specific livestock selected, will search for all available data', [
                    'farm' => $request->farm,
                    'coop' => $request->coop,
                    'tahun' => $request->tahun,
                    'include_temp_sales' => $includeTempSales,
                    'include_draft_sales' => $includeDraftSales
                ]);
            }

            // Get sales data menggunakan field baru
            $salesData = collect();
            if ($livestockId) {
                $salesData = LivestockSales::with([
                    'customer',
                    'expedition',
                    'farm',
                    'coop',
                    'livestockSalesItems' => function ($query) {
                        $query->orderBy('date', 'ASC');
                    }
                ])
                    ->where('livestock_id', $livestockId)
                    ->where('status', '!=', LivestockSales::STATUS_CANCELLED);

                // Apply status filter based on includeDraftSales
                if (!$includeDraftSales) {
                    $salesData->whereNotIn('status', [
                        LivestockSales::STATUS_DRAFT,
                        LivestockSales::STATUS_PENDING
                    ]);
                }

                $salesData = $salesData->orderBy('date', 'ASC')->get();

                Log::info('Sales data retrieved', [
                    'total_sales' => $salesData->count(),
                    'livestock_id' => $livestockId,
                    'include_draft_sales' => $includeDraftSales,
                    'status_filter' => $includeDraftSales ? 'all' : 'excluding draft/pending'
                ]);
            } else {
                // Search for all sales data based on farm/coop filters
                $salesData = $this->getAllSalesData($request, $includeDraftSales);
                Log::info('All sales data retrieved', [
                    'total_sales' => $salesData->count(),
                    'farm' => $request->farm,
                    'coop' => $request->coop,
                    'tahun' => $request->tahun,
                    'include_draft_sales' => $includeDraftSales
                ]);
            }

            // Get temporary sales data if requested
            $tempSalesData = collect();
            if ($includeTempSales) {
                if ($livestockId) {
                    $tempSalesData = $this->getTemporarySalesData($livestockId, $includeDraftSales);
                    Log::info('Temporary sales data retrieved', [
                        'total_temp_sales' => $tempSalesData->count(),
                        'livestock_id' => $livestockId,
                        'include_draft_sales' => $includeDraftSales
                    ]);
                } else {
                    $tempSalesData = $this->getAllTemporarySalesData($request, $includeDraftSales);
                    Log::info('All temporary sales data retrieved', [
                        'total_temp_sales' => $tempSalesData->count(),
                        'farm' => $request->farm,
                        'coop' => $request->coop,
                        'tahun' => $request->tahun,
                        'include_draft_sales' => $includeDraftSales
                    ]);
                }
            }

            // Enhanced logging for debugging
            Log::info('Data retrieval summary', [
                'livestock_id' => $livestockId,
                'sales_data_count' => $salesData->count(),
                'temp_sales_data_count' => $tempSalesData->count(),
                'include_temp_sales' => $includeTempSales,
                'include_draft_sales' => $includeDraftSales,
                'has_data' => $salesData->isNotEmpty() || $tempSalesData->isNotEmpty()
            ]);

            // Jika data baru kosong dan ada livestock spesifik, coba fallback ke data lama
            if ($salesData->isEmpty() && $tempSalesData->isEmpty() && $livestockId) {
                Log::info('No data in new LivestockSales model, trying fallback to old data', [
                    'livestock_id' => $livestockId,
                    'include_draft_sales' => $includeDraftSales
                ]);

                // Coba ambil data dari model lama (TransaksiJual)
                $oldSalesData = $this->getOldSalesData($livestockId);

                if ($oldSalesData->isEmpty()) {
                    Log::warning('No sales data found in both new and old models', [
                        'livestock_id' => $livestockId,
                        'livestock_name' => $livestock ? $livestock->name : 'N/A',
                        'include_draft_sales' => $includeDraftSales
                    ]);

                    // Check if there's draft data available
                    $draftDataAvailable = false;
                    $draftMessage = '';

                    if ($includeTempSales) {
                        $draftRecordingSales = \App\Models\RecordingSale::where('livestock_id', $livestockId)
                            ->whereIn('status', ['draft', 'pending'])
                            ->count();

                        if ($draftDataAvailable = ($draftRecordingSales > 0)) {
                            $draftMessage = "Terdapat {$draftRecordingSales} data draft yang dapat ditampilkan jika opsi 'Include Draft Sales' diaktifkan.";
                        }
                    }

                    // Return data kosong dengan pesan yang informatif
                    return [
                        'data' => collect(),
                        'livestock' => $livestock,
                        'coop' => $livestock->coop->name ?? 'N/A',
                        'farm' => $livestock->farm->name ?? 'N/A',
                        'periode' => $this->formatPeriod($livestock),
                        'penjualanData' => collect(),
                        'tempSalesData' => collect(),
                        'summary' => $this->getEmptySummary(),
                        'warning_message' => 'Belum ada data penjualan untuk periode ini. ' . $draftMessage,
                        'has_data' => false,
                        'include_temp_sales' => $includeTempSales,
                        'include_draft_sales' => $includeDraftSales,
                        'draft_data_available' => $draftDataAvailable
                    ];
                }

                // Gunakan data lama
                $salesData = $oldSalesData;
                Log::info('Using fallback data from old model', [
                    'livestock_id' => $livestockId,
                    'total_sales' => $salesData->count()
                ]);
            }

            // Calculate summary metrics
            $summary = $this->calculateSalesSummary($salesData, $tempSalesData);

            // Format period menggunakan field baru
            $periode = $livestock ? $this->formatPeriod($livestock) : 'Semua Periode';

            $reportData = [
                'data' => $salesData,
                'livestock' => $livestock,
                'coop' => $livestock ? ($livestock->coop->name ?? 'N/A') : 'Semua Kandang',
                'farm' => $livestock ? ($livestock->farm->name ?? 'N/A') : 'Semua Farm',
                'periode' => $periode,
                'penjualanData' => $salesData,
                'tempSalesData' => $tempSalesData,
                'summary' => $summary,
                'has_data' => $salesData->isNotEmpty() || $tempSalesData->isNotEmpty(),
                'include_temp_sales' => $includeTempSales,
                'include_draft_sales' => $includeDraftSales
            ];

            Log::info('Sales report generated successfully', [
                'livestock_id' => $livestockId,
                'total_sales' => $salesData->count(),
                'total_temp_sales' => $tempSalesData->count(),
                'total_amount' => $summary['total_amount'] ?? 0
            ]);

            return $reportData;
        } catch (\Exception $e) {
            Log::error('Error generating sales report', [
                'livestock_id' => $request->periode ?? 'N/A',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Return error data dengan pesan yang informatif
            return [
                'data' => collect(),
                'livestock' => null,
                'coop' => 'N/A',
                'farm' => 'N/A',
                'periode' => 'N/A',
                'penjualanData' => collect(),
                'tempSalesData' => collect(),
                'summary' => $this->getEmptySummary(),
                'error_message' => 'Terjadi kesalahan saat memuat laporan. Silakan coba lagi atau hubungi administrator.',
                'has_data' => false
            ];
        }
    }

    /**
     * Get temporary sales data from RecordingSale model
     * 
     * @param string $livestockId
     * @param bool $includeDraftSales
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getTemporarySalesData($livestockId, $includeDraftSales = false)
    {
        try {
            // First, let's check if the livestock exists and get its details
            $livestock = \App\Models\Livestock::find($livestockId);

            Log::info('Checking livestock for temporary sales', [
                'requested_livestock_id' => $livestockId,
                'livestock_found' => $livestock ? true : false,
                'livestock_name' => $livestock ? $livestock->name : 'N/A',
                'include_draft_sales' => $includeDraftSales
            ]);

            // Build query with enhanced logging
            $query = \App\Models\RecordingSale::with([
                'items' => function ($query) {
                    $query->orderBy('created_at', 'ASC');
                },
                'livestock',
                'livestockBatch'
            ])
                ->where('livestock_id', $livestockId)
                ->where('status', '!=', \App\Models\RecordingSale::STATUS_CANCELLED);

            // Apply status filter based on includeDraftSales
            if (!$includeDraftSales) {
                $query->whereNotIn('status', [
                    \App\Models\RecordingSale::STATUS_DRAFT,
                    \App\Models\RecordingSale::STATUS_PENDING
                ]);
            }

            // Get all recording sales for debugging
            $allRecordingSales = \App\Models\RecordingSale::where('livestock_id', $livestockId)->get();

            Log::info('All recording sales found for livestock', [
                'livestock_id' => $livestockId,
                'total_recording_sales' => $allRecordingSales->count(),
                'status_distribution' => $allRecordingSales->groupBy('status')->map->count(),
                'include_draft_sales' => $includeDraftSales,
                'sample_records' => $allRecordingSales->take(3)->map(function ($sale) {
                    return [
                        'id' => $sale->id,
                        'status' => $sale->status,
                        'date' => $sale->date,
                        'total_quantity' => $sale->total_quantity,
                        'total_weight' => $sale->total_weight,
                        'is_header' => $sale->is_header
                    ];
                })->toArray()
            ]);

            // Log what will be filtered out
            if (!$includeDraftSales) {
                $draftSales = $allRecordingSales->whereIn('status', ['draft', 'pending']);
                Log::info('Recording sales that will be filtered out (draft/pending)', [
                    'livestock_id' => $livestockId,
                    'filtered_out_count' => $draftSales->count(),
                    'filtered_records' => $draftSales->map(function ($sale) {
                        return [
                            'id' => $sale->id,
                            'status' => $sale->status,
                            'date' => $sale->date,
                            'total_quantity' => $sale->total_quantity,
                            'total_weight' => $sale->total_weight
                        ];
                    })->toArray()
                ]);
            }

            $tempSales = $query->orderBy('date', 'ASC')->get();

            Log::info('Temporary sales data retrieved', [
                'livestock_id' => $livestockId,
                'total_temp_sales' => $tempSales->count(),
                'include_draft_sales' => $includeDraftSales,
                'filtered_statuses' => $includeDraftSales ? 'all' : 'excluding draft/pending',
                'sample_data' => $tempSales->first() ? [
                    'id' => $tempSales->first()->id,
                    'is_header' => $tempSales->first()->is_header,
                    'total_quantity' => $tempSales->first()->total_quantity,
                    'total_weight' => $tempSales->first()->total_weight,
                    'total_amount' => $tempSales->first()->total_amount,
                    'items_count' => $tempSales->first()->items ? $tempSales->first()->items->count() : 0,
                    'status' => $tempSales->first()->status
                ] : null
            ]);

            // If no data found with exact livestock_id, try to find related recording sales
            if ($tempSales->isEmpty() && $livestock) {
                Log::info('No recording sales found with exact livestock_id, searching for related data', [
                    'livestock_id' => $livestockId,
                    'livestock_name' => $livestock->name
                ]);

                // Try to find recording sales by livestock name or other criteria
                $relatedRecordingSales = \App\Models\RecordingSale::with([
                    'items' => function ($query) {
                        $query->orderBy('created_at', 'ASC');
                    },
                    'livestock',
                    'livestockBatch'
                ])
                    ->whereHas('livestock', function ($query) use ($livestock) {
                        // Try to match by name pattern or other criteria
                        $query->where('name', 'like', '%' . $livestock->name . '%')
                            ->orWhere('name', 'like', '%' . str_replace(' ', '%', $livestock->name) . '%');
                    })
                    ->where('status', '!=', \App\Models\RecordingSale::STATUS_CANCELLED);

                // Apply status filter
                if (!$includeDraftSales) {
                    $relatedRecordingSales->whereNotIn('status', [
                        \App\Models\RecordingSale::STATUS_DRAFT,
                        \App\Models\RecordingSale::STATUS_PENDING
                    ]);
                }

                $relatedSales = $relatedRecordingSales->orderBy('date', 'ASC')->get();

                Log::info('Related recording sales found', [
                    'livestock_id' => $livestockId,
                    'livestock_name' => $livestock->name,
                    'related_sales_count' => $relatedSales->count(),
                    'related_livestock_ids' => $relatedSales->pluck('livestock_id')->unique()->toArray()
                ]);

                if ($relatedSales->isNotEmpty()) {
                    return $relatedSales;
                }
            }

            return $tempSales;
        } catch (\Exception $e) {
            Log::error('Error retrieving temporary sales data', [
                'livestock_id' => $livestockId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return collect();
        }
    }

    /**
     * Get all sales data based on farm/coop filters
     * 
     * @param Request $request
     * @param bool $includeDraftSales
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getAllSalesData($request, $includeDraftSales = false)
    {
        try {
            $query = LivestockSales::with([
                'customer',
                'expedition',
                'farm',
                'coop',
                'livestock',
                'livestockSalesItems' => function ($query) {
                    $query->orderBy('date', 'ASC');
                }
            ])
                ->where('status', '!=', LivestockSales::STATUS_CANCELLED);

            // Apply farm filter if provided
            if ($request->farm) {
                $query->where('farm_id', $request->farm);
            }

            // Apply coop filter if provided
            if ($request->coop) {
                $query->where('coop_id', $request->coop);
            }

            // Apply year filter if provided
            if ($request->tahun) {
                $query->whereYear('date', $request->tahun);
            }

            // Apply status filter based on includeDraftSales
            if (!$includeDraftSales) {
                $query->whereNotIn('status', [
                    LivestockSales::STATUS_DRAFT,
                    LivestockSales::STATUS_PENDING
                ]);
            }

            $salesData = $query->orderBy('date', 'ASC')->get();

            Log::info('All sales data retrieved', [
                'total_sales' => $salesData->count(),
                'farm' => $request->farm,
                'coop' => $request->coop,
                'tahun' => $request->tahun,
                'include_draft_sales' => $includeDraftSales
            ]);

            return $salesData;
        } catch (\Exception $e) {
            Log::error('Error retrieving all sales data', [
                'farm' => $request->farm,
                'coop' => $request->coop,
                'tahun' => $request->tahun,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return collect();
        }
    }

    /**
     * Get all temporary sales data based on farm/coop filters
     * 
     * @param Request $request
     * @param bool $includeDraftSales
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getAllTemporarySalesData($request, $includeDraftSales = false)
    {
        try {
            $query = \App\Models\RecordingSale::with([
                'items' => function ($query) {
                    $query->orderBy('created_at', 'ASC');
                },
                'livestock',
                'livestockBatch',
                'livestock.farm',
                'livestock.coop'
            ])
                ->where('status', '!=', \App\Models\RecordingSale::STATUS_CANCELLED);

            // Apply farm filter if provided
            if ($request->farm) {
                $query->whereHas('livestock', function ($q) use ($request) {
                    $q->where('farm_id', $request->farm);
                });
            }

            // Apply coop filter if provided
            if ($request->coop) {
                $query->whereHas('livestock', function ($q) use ($request) {
                    $q->where('coop_id', $request->coop);
                });
            }

            // Apply year filter if provided
            if ($request->tahun) {
                $query->whereYear('date', $request->tahun);
            }

            // Apply status filter based on includeDraftSales
            if (!$includeDraftSales) {
                $query->whereNotIn('status', [
                    \App\Models\RecordingSale::STATUS_DRAFT,
                    \App\Models\RecordingSale::STATUS_PENDING
                ]);
            }

            $tempSalesData = $query->orderBy('date', 'ASC')->get();

            Log::info('All temporary sales data retrieved', [
                'total_temp_sales' => $tempSalesData->count(),
                'farm' => $request->farm,
                'coop' => $request->coop,
                'tahun' => $request->tahun,
                'include_draft_sales' => $includeDraftSales
            ]);

            return $tempSalesData;
        } catch (\Exception $e) {
            Log::error('Error retrieving all temporary sales data', [
                'farm' => $request->farm,
                'coop' => $request->coop,
                'tahun' => $request->tahun,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return collect();
        }
    }

    /**
     * Get old sales data from TransaksiJual model
     * 
     * @param string $livestockId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getOldSalesData($livestockId)
    {
        try {
            // Coba ambil data dari model lama
            $oldData = \App\Models\TransaksiJual::with(['detail.rekanan', 'kelompokTernak'])
                ->where('kelompok_ternak_id', $livestockId)
                ->where('status', 'OK')
                ->get();

            Log::info('Old sales data retrieved', [
                'livestock_id' => $livestockId,
                'total_old_sales' => $oldData->count()
            ]);

            return $oldData;
        } catch (\Exception $e) {
            Log::error('Error retrieving old sales data', [
                'livestock_id' => $livestockId,
                'error' => $e->getMessage()
            ]);
            return collect();
        }
    }

    /**
     * Format period string
     * 
     * @param Livestock $livestock
     * @return string
     */
    protected function formatPeriod($livestock)
    {
        try {
            if (!$livestock) {
                return 'Semua Periode';
            }

            $startDate = Carbon::parse($livestock->start_date);
            $endDate = Carbon::parse($livestock->end_date);
            return $startDate->translatedFormat('F') . ' s.d. ' . $endDate->translatedFormat('F Y');
        } catch (\Exception $e) {
            Log::error('Error formatting period', [
                'livestock_id' => $livestock ? $livestock->id : 'N/A',
                'error' => $e->getMessage()
            ]);
            return 'Periode tidak tersedia';
        }
    }

    /**
     * Get empty summary structure
     * 
     * @return array
     */
    protected function getEmptySummary()
    {
        return [
            'total_sales' => 0,
            'total_temp_sales' => 0,
            'total_quantity' => 0,
            'total_weight' => 0,
            'total_amount' => 0,
            'total_expedition_fee' => 0,
            'avg_amount_per_sale' => 0,
            'avg_quantity_per_sale' => 0,
            'avg_weight_per_sale' => 0,
            'payment_summary' => [
                'unpaid' => 0,
                'partial' => 0,
                'paid' => 0,
                'overdue' => 0
            ],
            'status_summary' => [
                'draft' => 0,
                'pending' => 0,
                'confirmed' => 0,
                'delivered' => 0,
                'completed' => 0,
                'cancelled' => 0
            ],
            'temp_sales_summary' => [
                'draft' => 0,
                'pending' => 0,
                'confirmed' => 0,
                'completed' => 0,
                'cancelled' => 0
            ]
        ];
    }

    /**
     * Calculate sales summary metrics including temporary sales
     * 
     * @param \Illuminate\Database\Eloquent\Collection $salesData
     * @param \Illuminate\Database\Eloquent\Collection $tempSalesData
     * @return array
     */
    protected function calculateSalesSummary($salesData, $tempSalesData = null)
    {
        $summary = [
            'total_sales' => $salesData->count(),
            'total_temp_sales' => $tempSalesData ? $tempSalesData->count() : 0,
            'total_quantity' => 0,
            'total_weight' => 0,
            'total_amount' => 0,
            'total_expedition_fee' => 0,
            'avg_amount_per_sale' => 0,
            'avg_quantity_per_sale' => 0,
            'avg_weight_per_sale' => 0,
            'payment_summary' => [
                'unpaid' => 0,
                'partial' => 0,
                'paid' => 0,
                'overdue' => 0
            ],
            'status_summary' => [
                'draft' => 0,
                'pending' => 0,
                'confirmed' => 0,
                'delivered' => 0,
                'completed' => 0,
                'cancelled' => 0
            ],
            'temp_sales_summary' => [
                'draft' => 0,
                'pending' => 0,
                'confirmed' => 0,
                'completed' => 0,
                'cancelled' => 0
            ]
        ];

        // Process main sales data
        foreach ($salesData as $sale) {
            // Aggregate from header fields
            $summary['total_quantity'] += $sale->total_quantity ?? 0;
            $summary['total_weight'] += $sale->total_weight ?? 0;
            $summary['total_amount'] += $sale->total_amount ?? 0;
            $summary['total_expedition_fee'] += $sale->expedition_fee ?? 0;

            // Count payment status
            $paymentStatus = $sale->payment_status ?? LivestockSales::PAYMENT_STATUS_UNPAID;
            if (isset($summary['payment_summary'][$paymentStatus])) {
                $summary['payment_summary'][$paymentStatus]++;
            }

            // Count transaction status
            $status = $sale->status ?? LivestockSales::STATUS_DRAFT;
            if (isset($summary['status_summary'][$status])) {
                $summary['status_summary'][$status]++;
            }

            // Aggregate from items if needed (for old data structure)
            if ($sale->livestockSalesItems && $sale->livestockSalesItems->isNotEmpty()) {
                foreach ($sale->livestockSalesItems as $item) {
                    $summary['total_quantity'] += $item->quantity ?? 0;
                    $summary['total_weight'] += $item->total_weight ?? 0;
                    $summary['total_amount'] += $item->total_price ?? 0;
                }
            }

            // Handle old data structure (TransaksiJual)
            if (isset($sale->detail)) {
                $summary['total_quantity'] += $sale->jumlah ?? 0;
                $summary['total_weight'] += $sale->detail->berat ?? 0;
                $summary['total_amount'] += ($sale->detail->harga_jual ?? 0) * ($sale->detail->berat ?? 0);
            }
        }

        // Process temporary sales data (RecordingSale)
        if ($tempSalesData && $tempSalesData->isNotEmpty()) {
            foreach ($tempSalesData as $tempSale) {
                // Aggregate from temporary sales header fields
                $summary['total_quantity'] += $tempSale->total_quantity ?? 0;
                $summary['total_weight'] += $tempSale->total_weight ?? 0;
                $summary['total_amount'] += $tempSale->total_amount ?? 0;

                // Count temporary sales status
                $tempStatus = $tempSale->status ?? \App\Models\RecordingSale::STATUS_DRAFT;
                if (isset($summary['temp_sales_summary'][$tempStatus])) {
                    $summary['temp_sales_summary'][$tempStatus]++;
                }

                // Aggregate from items if header record
                if ($tempSale->is_header && $tempSale->items && $tempSale->items->isNotEmpty()) {
                    foreach ($tempSale->items as $item) {
                        $summary['total_quantity'] += $item->quantity ?? 0;
                        $summary['total_weight'] += $item->weight ?? 0;
                        $summary['total_amount'] += $item->amount ?? 0;
                    }
                } else {
                    // For legacy single records, use direct fields
                    $summary['total_quantity'] += $tempSale->quantity ?? 0;
                    $summary['total_weight'] += $tempSale->weight ?? 0;
                    $summary['total_amount'] += ($tempSale->quantity ?? 0) * ($tempSale->price ?? 0);
                }
            }
        }

        // Calculate averages
        $totalSales = $summary['total_sales'] + $summary['total_temp_sales'];
        if ($totalSales > 0) {
            $summary['avg_amount_per_sale'] = $summary['total_amount'] / $totalSales;
            $summary['avg_quantity_per_sale'] = $summary['total_quantity'] / $totalSales;
            $summary['avg_weight_per_sale'] = $summary['total_weight'] / $totalSales;
        }

        return $summary;
    }

    /**
     * Generate performance partner report dengan struktur baru
     * 
     * @param Request $request
     * @return array
     */
    public function generatePerformancePartnerReport(Request $request)
    {
        try {
            Log::info('Starting performance partner report generation', [
                'request_data' => $request->all()
            ]);

            $request->validate([
                'periode' => 'required|exists:ternaks,id',
                'tanggal_surat' => 'nullable|date',
                'integrasi' => 'nullable|array'
            ]);

            $ternakId = $request->periode;
            $ternak = Ternak::with([
                'kandang',
                'kematianTernak',
                'penjualanTernaks',
                'transaksiHarians.transaksiHarianDetails.item.itemCategory',
                'transaksiJuals.transaksiJualDetails'
            ])->findOrFail($ternakId);

            // Check if ternak is still active
            if ($ternak->status === 'Aktif') {
                throw new \Exception('Status Batch ' . trans('content.ternak', [], 'id') . ' Masih Aktif.');
            }

            $existingData = $ternak->data ?: [];
            $isTernakMati = in_array('ternak_mati', $request->input('integrasi', []));

            // Calculate metrics
            $metrics = $this->calculatePerformanceMetrics($ternak, $isTernakMati);

            // Process tanggal surat
            $tanggalSurat = $this->processTanggalSurat($request, $ternak, $existingData);

            // Calculate costs
            $costs = $this->calculateCosts($ternak);

            // Build final data
            $data = array_merge($metrics, $costs, [
                'tanggal_surat' => $tanggalSurat,
                'bonus' => $existingData['bonus'] ?? null,
                'administrasi' => $existingData['administrasi'] ?? []
            ]);

            Log::info('Performance partner report generated successfully', [
                'ternak_id' => $ternakId,
                'periode' => $metrics['periode'] ?? 'N/A'
            ]);

            return [
                'data' => $data,
                'ternak' => $ternak,
                'kandang' => $ternak->kandang->nama,
                'periode' => $metrics['periode'],
                'penjualanData' => $metrics['penjualanData']
            ];
        } catch (\Exception $e) {
            Log::error('Error generating performance partner report', [
                'ternak_id' => $request->periode ?? 'N/A',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Calculate performance metrics dengan optimasi query
     * 
     * @param Ternak $ternak
     * @param bool $isTernakMati
     * @return array
     */
    protected function calculatePerformanceMetrics($ternak, $isTernakMati)
    {
        Log::info('Calculating performance metrics', [
            'ternak_id' => $ternak->id,
            'is_ternak_mati' => $isTernakMati
        ]);

        // Set locale
        setlocale(LC_TIME, 'id_ID');
        Carbon::setLocale('id');

        // Format period
        $startDate = Carbon::parse($ternak->start_date);
        $endDate = Carbon::parse($ternak->end_date);
        $periode = $startDate->translatedFormat('F') . ' s.d. ' . $endDate->translatedFormat('F Y');

        // Get sales data menggunakan LivestockSales yang baru
        $penjualanData = LivestockSales::with(['customer', 'livestockSalesItems'])
            ->where('livestock_id', $ternak->livestock_id ?? $ternak->id)
            ->whereIn('status', [
                LivestockSales::STATUS_CONFIRMED,
                LivestockSales::STATUS_DELIVERED,
                LivestockSales::STATUS_COMPLETED
            ])
            ->get();

        Log::info('Sales data retrieved for performance metrics', [
            'ternak_id' => $ternak->id,
            'total_sales' => $penjualanData->count()
        ]);

        // Calculate basic metrics
        $kematian = $ternak->kematianTernak()->whereNull('deleted_at')->sum("quantity");
        $penjualan = $ternak->penjualanTernaks()->whereNull('deleted_at')->sum("quantity");

        // Calculate consumption and weight
        $konsumsiPakan = $this->calculateFeedConsumption($ternak);
        $totalBerat = $this->calculateTotalWeight($ternak);

        if ($totalBerat <= 0) {
            throw new \Exception('Data Penjualan Ternak Masih Belum Lengkap.');
        }

        // Calculate performance metrics
        $umurPanen = $penjualanData->sum(fn($data) => $data->livestockSalesItems->sum('quantity') * ($data->date ? Carbon::parse($data->date)->diffInDays($startDate) : 0)) / $penjualanData->sum(fn($data) => $data->livestockSalesItems->sum('quantity'));
        $penjualanKilo = $penjualanData->sum('total_weight') / $penjualanData->sum('total_quantity');
        $fcr = $konsumsiPakan / $totalBerat;

        // Calculate mortality rate
        if ($isTernakMati) {
            $mortalityRate = ($kematian / $ternak->populasi_awal) * 100;
            $persentaseKematian = ($kematian / $ternak->populasi_awal) * 100;
        } else {
            $mortalityRate = ($ternak->populasi_awal - $penjualan) / $ternak->populasi_awal * 100;
            $kematian = $ternak->populasi_awal - $penjualan;
            $persentaseKematian = ($kematian / $ternak->populasi_awal) * 100;
        }

        // Calculate IP
        $averageWeight = $totalBerat / $penjualan;
        $ageInDays = $umurPanen;
        $ip = (100 - $mortalityRate) * ($averageWeight / ($fcr * $ageInDays)) * 100;
        $ip = round($ip, 2);

        $metrics = [
            'beratJual' => $totalBerat,
            'penjualan' => $penjualan,
            'kematian' => $kematian,
            'penjualanData' => $penjualanData,
            'periode' => $periode,
            'kematian' => $kematian,
            'persentaseKematian' => $persentaseKematian,
            'penjualanKilo' => $penjualanKilo,
            'konsumsiPakan' => $konsumsiPakan,
            'umurPanen' => $umurPanen,
            'fcr' => $fcr,
            'ip' => $ip
        ];

        Log::info('Performance metrics calculated', [
            'ternak_id' => $ternak->id,
            'total_berat' => $totalBerat,
            'penjualan' => $penjualan,
            'ip' => $ip
        ]);

        return $metrics;
    }

    /**
     * Calculate feed consumption dengan optimasi query
     * 
     * @param Ternak $ternak
     * @return float
     */
    protected function calculateFeedConsumption($ternak)
    {
        return $ternak->transaksiHarians()
            ->join('transaksi_harian_details', 'transaksi_harians.id', '=', 'transaksi_harian_details.transaksi_id')
            ->join('items', 'transaksi_harian_details.item_id', '=', 'items.id')
            ->join('item_categories', 'items.category_id', '=', 'item_categories.id')
            ->where('item_categories.name', 'Pakan')
            ->whereNull('transaksi_harians.deleted_at')
            ->whereNull('transaksi_harian_details.deleted_at')
            ->sum('transaksi_harian_details.quantity');
    }

    /**
     * Calculate total weight menggunakan LivestockSales yang baru
     * 
     * @param Ternak $ternak
     * @return float
     */
    protected function calculateTotalWeight($ternak)
    {
        // Try using new LivestockSales model first
        $newWeight = LivestockSales::where('livestock_id', $ternak->livestock_id ?? $ternak->id)
            ->whereIn('status', [
                LivestockSales::STATUS_CONFIRMED,
                LivestockSales::STATUS_DELIVERED,
                LivestockSales::STATUS_COMPLETED
            ])
            ->sum('total_weight');

        if ($newWeight > 0) {
            return $newWeight;
        }

        // Fallback to old TransaksiJual if no data in new model
        return $ternak->transaksiJuals()
            ->join('transaksi_jual_details', 'transaksi_jual.id', '=', 'transaksi_jual_details.transaksi_jual_id')
            ->where('transaksi_jual.status', 'OK')
            ->whereNull('transaksi_jual.deleted_at')
            ->whereNull('transaksi_jual_details.deleted_at')
            ->sum('transaksi_jual_details.berat');
    }

    /**
     * Calculate costs dengan optimasi
     * 
     * @param Ternak $ternak
     * @return array
     */
    protected function calculateCosts($ternak)
    {
        Log::info('Calculating costs for ternak', ['ternak_id' => $ternak->id]);

        // Calculate feed costs
        $totalBiayaPakan = $this->calculateFeedCosts($ternak);
        $biayaPakanDetails = $this->calculateFeedCostDetails($ternak);

        // Calculate OVK costs
        $totalBiayaOvk = $this->calculateOvkCosts($ternak);

        // Calculate total HPP
        $biayaDoc = $ternak->populasi_awal * $ternak->harga_beli;
        $totalHpp = $biayaDoc + $totalBiayaPakan + $totalBiayaOvk;

        // Add bonus if exists
        $existingData = $ternak->data ?: [];
        if (isset($existingData['bonus'])) {
            $bonusTotal = is_array($existingData['bonus'])
                ? array_sum(array_column($existingData['bonus'], 'jumlah'))
                : $existingData['bonus']['jumlah'];
            $totalHpp += $bonusTotal;
        }

        // Calculate per-unit costs
        $penjualan = $ternak->penjualanTernaks()->whereNull('deleted_at')->sum("quantity");
        $totalBerat = $this->calculateTotalWeight($ternak);

        $costs = [
            'totalBiayaPakan' => round($totalBiayaPakan, 2),
            'biayaPakanDetails' => $biayaPakanDetails,
            'totalBiayaOvk' => round($totalBiayaOvk, 2),
            'total_hpp' => $totalHpp,
            'hpp_per_ekor' => $penjualan > 0 ? $totalHpp / $penjualan : 0,
            'hpp_per_kg' => $totalBerat > 0 ? $totalHpp / $totalBerat : 0,
            'total_penghasilan' => ($totalBerat * $ternak->transaksiJuals()->where('status', 'OK')->avg('detail.harga_jual')) - $totalHpp,
            'penghasilan_per_ekor' => $penjualan > 0 ? (($totalBerat * $ternak->transaksiJuals()->where('status', 'OK')->avg('detail.harga_jual')) - $totalHpp) / $penjualan : 0
        ];

        Log::info('Costs calculated successfully', [
            'ternak_id' => $ternak->id,
            'total_hpp' => $totalHpp,
            'total_biaya_pakan' => $totalBiayaPakan
        ]);

        return $costs;
    }

    /**
     * Calculate feed costs dengan optimasi query
     * 
     * @param Ternak $ternak
     * @return float
     */
    protected function calculateFeedCosts($ternak)
    {
        return $ternak->transaksiHarians()
            ->join('transaksi_harian_details', 'transaksi_harians.id', '=', 'transaksi_harian_details.transaksi_id')
            ->join('items', 'transaksi_harian_details.item_id', '=', 'items.id')
            ->join('item_categories', 'items.category_id', '=', 'item_categories.id')
            ->where('item_categories.name', 'Pakan')
            ->whereNull('transaksi_harians.deleted_at')
            ->whereNull('transaksi_harian_details.deleted_at')
            ->sum(DB::raw('transaksi_harian_details.quantity * transaksi_harian_details.harga'));
    }

    /**
     * Calculate feed cost details dengan optimasi
     * 
     * @param Ternak $ternak
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function calculateFeedCostDetails($ternak)
    {
        return $ternak->transaksiHarians()
            ->join('transaksi_harian_details', 'transaksi_harians.id', '=', 'transaksi_harian_details.transaksi_id')
            ->join('items', 'transaksi_harian_details.item_id', '=', 'items.id')
            ->join('item_categories', 'items.category_id', '=', 'item_categories.id')
            ->where('item_categories.name', 'Pakan')
            ->whereNull('transaksi_harians.deleted_at')
            ->whereNull('transaksi_harian_details.deleted_at')
            ->select(
                'items.id as item_id',
                'items.name as item_name',
                DB::raw('SUM(transaksi_harian_details.quantity) as total_quantity'),
                DB::raw('AVG(transaksi_harian_details.harga) as avg_price'),
                DB::raw('SUM(transaksi_harian_details.quantity * transaksi_harian_details.harga) as total_cost')
            )
            ->groupBy('items.id', 'items.name')
            ->orderBy('items.name')
            ->get();
    }

    /**
     * Calculate OVK costs dengan optimasi
     * 
     * @param Ternak $ternak
     * @return float
     */
    protected function calculateOvkCosts($ternak)
    {
        return $ternak->transaksiHarians()
            ->join('transaksi_harian_details', 'transaksi_harians.id', '=', 'transaksi_harian_details.transaksi_id')
            ->join('items', 'transaksi_harian_details.item_id', '=', 'items.id')
            ->join('item_categories', 'items.category_id', '=', 'item_categories.id')
            ->where('item_categories.name', 'OVK')
            ->whereNull('transaksi_harians.deleted_at')
            ->whereNull('transaksi_harian_details.deleted_at')
            ->sum(DB::raw('transaksi_harian_details.quantity * transaksi_harian_details.harga'));
    }

    /**
     * Process tanggal surat dengan logging
     * 
     * @param Request $request
     * @param Ternak $ternak
     * @param array $existingData
     * @return string
     */
    protected function processTanggalSurat($request, $ternak, $existingData)
    {
        if ($request->tanggal_surat) {
            $tanggalSurat = Carbon::parse($request->tanggal_surat)->translatedFormat('d F Y');

            // Update data if tanggal_surat is different
            if (
                !isset($existingData['administrasi']['tanggal_laporan']) ||
                $existingData['administrasi']['tanggal_laporan'] !== $request->tanggal_surat
            ) {
                $existingData['administrasi']['tanggal_laporan'] = $request->tanggal_surat;
                $ternak->update(['data' => json_encode($existingData)]);

                Log::info('Tanggal surat updated', [
                    'ternak_id' => $ternak->id,
                    'tanggal_surat' => $request->tanggal_surat
                ]);
            }
        } else {
            $tanggalSurat = Carbon::now()->translatedFormat('d F Y');
            $existingData['administrasi']['tanggal_laporan'] = $tanggalSurat;
            $ternak->update(['data' => json_encode($existingData)]);
        }

        return $tanggalSurat;
    }

    /**
     * Export sales report dengan format yang berbeda
     * 
     * @param Request $request
     * @param string $format
     * @return \Illuminate\Http\Response
     */
    public function exportSalesReport(Request $request, $format = 'html')
    {
        try {
            Log::info('Exporting sales report', [
                'format' => $format,
                'request_data' => $request->all()
            ]);

            $reportData = $this->generateSalesReport($request);

            if ($format === 'html') {
                return view('pages.reports.penjualan_details', $reportData);
            } else {
                // Handle other formats (excel, pdf, csv)
                return $this->exportToFormat($reportData, $format);
            }
        } catch (\Exception $e) {
            Log::error('Error exporting sales report', [
                'format' => $format,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Export performance partner report
     * 
     * @param Request $request
     * @param string $format
     * @return \Illuminate\Http\Response
     */
    public function exportPerformancePartnerReport(Request $request, $format = 'html')
    {
        try {
            Log::info('Exporting performance partner report', [
                'format' => $format,
                'request_data' => $request->all()
            ]);

            $reportData = $this->generatePerformancePartnerReport($request);

            if ($format === 'html') {
                return view('pages.reports.performance_kemitraan', $reportData);
            } else {
                // Handle other formats (excel, pdf, csv)
                return $this->exportToFormat($reportData, $format);
            }
        } catch (\Exception $e) {
            Log::error('Error exporting performance partner report', [
                'format' => $format,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Export to specific format
     * 
     * @param array $reportData
     * @param string $format
     * @return \Illuminate\Http\Response
     */
    protected function exportToFormat($reportData, $format)
    {
        Log::info('Exporting to format', ['format' => $format]);

        // Implementation for different export formats
        switch ($format) {
            case 'excel':
                return $this->exportToExcel($reportData);
            case 'pdf':
                return $this->exportToPdf($reportData);
            case 'csv':
                return $this->exportToCsv($reportData);
            default:
                throw new \Exception('Unsupported export format: ' . $format);
        }
    }

    /**
     * Export to Excel
     * 
     * @param array $reportData
     * @return \Illuminate\Http\Response
     */
    protected function exportToExcel($reportData)
    {
        // Excel export implementation
        // This would use PhpSpreadsheet or similar library
        throw new \Exception('Excel export not implemented yet');
    }

    /**
     * Export to PDF
     * 
     * @param array $reportData
     * @return \Illuminate\Http\Response
     */
    protected function exportToPdf($reportData)
    {
        // PDF export implementation
        // This would use DomPDF or similar library
        throw new \Exception('PDF export not implemented yet');
    }

    /**
     * Export to CSV
     * 
     * @param array $reportData
     * @return \Illuminate\Http\Response
     */
    protected function exportToCsv($reportData)
    {
        // CSV export implementation
        throw new \Exception('CSV export not implemented yet');
    }

    /**
     * Get sales statistics untuk dashboard
     * 
     * @param Request $request
     * @return array
     */
    public function getSalesStatistics(Request $request)
    {
        try {
            Log::info('Getting sales statistics', [
                'request_data' => $request->all()
            ]);

            $query = LivestockSales::with(['customer', 'livestock']);

            // Apply filters
            if ($request->filled('date_from')) {
                $query->where('date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->where('date', '<=', $request->date_to);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('payment_status')) {
                $query->where('payment_status', $request->payment_status);
            }

            $sales = $query->get();

            $statistics = [
                'total_sales' => $sales->count(),
                'total_amount' => $sales->sum('total_amount'),
                'total_quantity' => $sales->sum('total_quantity'),
                'total_weight' => $sales->sum('total_weight'),
                'avg_amount_per_sale' => $sales->count() > 0 ? $sales->sum('total_amount') / $sales->count() : 0,
                'status_distribution' => $sales->groupBy('status')->map->count(),
                'payment_status_distribution' => $sales->groupBy('payment_status')->map->count(),
                'top_customers' => $sales->groupBy('customer_id')
                    ->map(function ($group) {
                        return [
                            'customer_name' => $group->first()->customer->name ?? 'Unknown',
                            'total_amount' => $group->sum('total_amount'),
                            'total_sales' => $group->count()
                        ];
                    })
                    ->sortByDesc('total_amount')
                    ->take(5)
            ];

            Log::info('Sales statistics calculated', [
                'total_sales' => $statistics['total_sales'],
                'total_amount' => $statistics['total_amount']
            ]);

            return $statistics;
        } catch (\Exception $e) {
            Log::error('Error getting sales statistics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}

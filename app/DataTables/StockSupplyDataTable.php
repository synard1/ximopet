<?php

namespace App\DataTables;

use App\Models\Item;
use App\Models\SupplyStock;
use App\Models\StockHistory;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class StockSupplyDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn() // Add this line to include row numbers

            // ->editColumn('kode', function (SupplyStock $item) {
            //     return '<a href="#" class="text-gray-800 text-hover-primary mb-1" data-kt-action="view_detail_stok" data-item-id="' . $item->id . '"><span class="menu-icon">
            // 				<i class="ki-outline ki-package fs-4 text-success"></i>
            // 			</span>' . $item->kode . '</a>';
            // })
            // ->editColumn('jumlah', function (SupplyStock $stok) {
            //     if (auth()->user()->farmOperators()->exists()) {
            //         $farmIds = auth()->user()->farmOperators()->pluck('farm_id')->toArray();
            //         $stokBeli = $stok->stockHistory->where('jenis', 'Pembelian')->sum('quantity');
            //         $stokPakai = $stok->stockHistory->where('jenis', 'Pemakaian')->sum('quantity');
            //         $stokAkhir = $stokBeli - $stokPakai;
            //     } else {
            //         $stokAkhir = $stok->currentStock->sum('available_quantity');
            //     }
            //     // return number_format($stokAkhir / $stok->konversi, 2);
            //     // dd($stokAkhir);
            //     return floatval($stokAkhir);
            // })
            // ->editColumn('jumlah', function (SupplyStock $stok) {
            //     if (auth()->user()->farmOperators()->exists()) {
            //         $farmIds = auth()->user()->farmOperators()->pluck('farm_id')->toArray();
            //         $stokAkhir = $stok->currentStock()
            //             ->whereHas('inventoryLocation', function ($query) use ($farmIds) {
            //                 $query->whereIn('farm_id', $farmIds);
            //             })
            //             ->sum('available_quantity');
            //     } else {
            //         $stokAkhir = $stok->currentStock->sum('available_quantity');
            //     }
            //     // return number_format($stokAkhir / $stok->konversi, 2);
            //     return number_format($stokAkhir);
            // })
            ->editColumn('farm_id', function (SupplyStock $stok) {
                return $stok->farm->name;
            })
            ->editColumn('supply_id', function (SupplyStock $stok) {
                return $stok->supply->name;
            })
            ->editColumn('unit', function (SupplyStock $stok) {
                return $stok->supply->unit;
            })
            ->editColumn('quantity', function (SupplyStock $stok) {
                $quantity = $stok->quantity_in - $stok->quantity_mutated - $stok->quantity_used;
                return $quantity;
            })
            ->editColumn('created_at', function (SupplyStock $stok) {
                return $stok->created_at->format('d M Y, h:i a');
            })
            ->addColumn('action', function (SupplyStock $stok) {
                if (auth()->user()->hasRole('Operator')) {

                    return view('pages.masterdata.stock._actions', compact('stok'));
                };
            })
            ->filterColumn('farm_id', function ($query, $keyword) {
                dd($keyword);
                $query->whereHas('farms', function ($q) use ($keyword) {
                    $q->where('id', 'like', "%{$keyword}%");
                });
            })
            // ->filterColumn('farm', function($query, $keyword) {

            //     $query->whereHas('farms', function($query) use ($keyword) { // Assuming you have a 'farm' relationship on your model
            //         $query->where('nama', 'like', "%{$keyword}%");
            //     });
            // })
            ->setRowId('id')
            ->rawColumns(['kode']);
        // ->make(true);
    }


    /**
     * Get the query source of dataTable.
     */
    public function query(SupplyStock $model): QueryBuilder
    {
        $query = $model->newQuery();

        // if (request()->has('farm_id')) {
        //     $query->where('farm_id', request('farm_id'));
        // }
        // if (request()->has('supply_id')) {
        //     $query->where('supply_id', request('supply_id'));
        // }

        // return $model->newQuery();
        // if (auth()->user()->hasRole('Operator')) {
        //     // Get farm IDs for current user from farmOperators
        //     $farmIds = auth()->user()->farmOperators()->pluck('farm_id')->toArray();

        //     // dd($farmIds);

        //     // Add a condition to filter items based on farm IDs
        //     $query->whereHas('stockHistory.inventoryLocation.farm', function ($q) use ($farmIds) {
        //         $q->whereIn('id', $farmIds);
        //     })->with(['itemCategory'])
        //     ->whereHas('itemCategory', function ($q) {
        //         $q->where('name', 'OVK');
        //     });
        //     // $query = $model::with(['stokHistory', 'transaksiDetail'])
        //     //     ->where('jenis', '!=', 'DOC')
        //     //     // ->whereHas('farmOperator', function ($q) {
        //     //     //     $q->where('user_id', auth()->id());
        //     //     // })
        //     //     ->orderBy('name', 'DESC')
        //     //     ->newQuery();
        // } else {
        //     // $query = $model::with(['stockHistory','itemCategory'])
        //     $query = $model::with(['itemCategory'])
        //         ->whereHas('itemCategory', function ($q) {
        //             $q->where('name', 'OVK');
        //         })
        //         ->orderBy('name', 'DESC')
        //         ->newQuery();
        // }

        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('stoks-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            // ->dom('Bfrtip')
            // ->dom('rt' . "<'row'<'col-sm-12 col-md-5'l><'col-sm-12 col-md-7'p>>",)
            // ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer text-gray-600 fw-semibold')
            ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer')
            ->setTableHeadClass('text-start text-muted fw-bold fs-7 text-uppercase gs-0')
            ->orderBy(1)
            ->parameters([
                'scrollX'      =>  true,
                'searching'       =>  true,
                // 'responsive'       =>  true,
                'lengthMenu' => [
                    [10, 25, 50, -1],
                    ['10 rows', '25 rows', '50 rows', 'Show all']
                ],
                'buttons'      => ['export', 'print', 'reload', 'colvis'],
            ])
            ->drawCallback("function() {" . file_get_contents(resource_path('views/pages/masterdata/stock/_draw-scripts.js')) . "}");
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex', 'No.')
                ->title('No.')
                ->addClass('text-center')
                ->width(50),
            // Column::make('kode')->searchable(false),
            Column::computed('farm_id')->searchable(true),
            Column::computed('supply_id')->title('Supply')->searchable(true),
            Column::computed('unit')->title('Unit')->searchable(true),
            Column::computed('quantity')->title('Quantity')->searchable(true),
            // Column::make('name'),
            // Column::make('status')
            //     ->visible(false),
            // Column::make('satuan_kecil')
            //     ->visible(false),
            // Column::make('konversi')
            //     ->visible(false),
            // Column::computed('jumlah')
            //     ->visible(true),
            // Column::make('satuan_kecil')->title('Satuan')
            //     ->visible(true),
            Column::make('created_at')->title('Created Date')->addClass('text-nowrap')
                ->searchable(false)
                ->visible(false),
            Column::computed('action')
                // ->addClass('text-end text-nowrap')
                ->exportable(false)
                ->printable(false)
            // ->width(60)
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Stoks_' . date('YmdHis');
    }
}

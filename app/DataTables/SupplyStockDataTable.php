<?php

namespace App\DataTables;

use App\Models\SupplyStock;
use App\Models\Item;
use App\Models\StockHistory;
use App\Models\Unit;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class SupplyStockDataTable extends DataTable
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

            ->editColumn('supply_id', function (SupplyStock $supply) {
                return $supply->supply->name;
            })

            ->editColumn('farm_id', function (SupplyStock $supply) {
                return '<a href="#" class="text-gray-800 text-hover-primary mb-1" data-kt-action="view_detail_supplystocks" data-supply-id="' . $supply->supply_id . '" data-farm-id="' . $supply->farm_id . '"><span class="menu-icon">
            				<i class="ki-outline ki-package fs-4 text-success"></i>
            			</span>' . $supply->farm->name . '</a>';
            })

            ->editColumn('unit', function (SupplyStock $supply) {
                $unit = Unit::findOrFail($supply->supply->data['unit_id']);
                return $unit->name;
            })

            ->editColumn('quantity', function (SupplyStock $stock) {
                $quantity = $stock->total_in - $stock->total_used - $stock->total_mutated;
                return $quantity;
            })
            ->addColumn('action', function (SupplyStock $transaction) {
                // dd($transaction);
                return view('pages.masterdata.stock._feedstock_actions', compact('transaction'));
            })
            ->filterColumn('farm_id', function ($query, $keyword) {
                $query->whereHas('farm', function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%");
                });
            })

            // ->editColumn('created_at', function (SupplyStock $supply) {
            //     return $supply->created_at->format('d M Y, h:i a');
            // })

            ->setRowId('id')
            ->rawColumns(['farm_id']);
    }


    /**
     * Get the query source of dataTable.
     */
    public function query(SupplyStock $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->selectRaw('
                farm_id,
                supply_id,
                SUM(quantity_in) as total_in,
                SUM(quantity_used) as total_used,
                SUM(quantity_mutated) as total_mutated
            ')
            ->with(['supply', 'farm'])
            ->groupBy('farm_id', 'supply_id');

        if (auth()->user()->hasRole('Operator')) {
            $query->whereHas('farm.farmOperators', function ($q) {
                $q->where('user_id', auth()->id());
            });
        }

        if (auth()->user()->hasRole(['Administrator', 'Manager', 'Supervisor'])) {
            $query->where('company_id', auth()->user()->company_id);
        }

        return $query;
    }
    // public function query(SupplyStock $model): QueryBuilder
    // {
    //     $query = $model->newQuery()->with(['farm', 'supply']);

    //     if (request()->filled('farm_id')) {
    //         $query->where('farm_id', request('farm_id'));
    //     }

    //     if (request()->filled('supply_id')) {
    //         $query->where('supply_id', request('supply_id'));
    //     }

    //     return $query;
    // }



    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('stocks-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('Bfrtip')
            // ->dom('rt' . "<'row'<'col-sm-12 col-md-5'l><'col-sm-12 col-md-7'p>>",)
            // ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer text-gray-600 fw-semibold')
            ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer')
            ->setTableHeadClass('text-start text-muted fw-bold fs-7 text-uppercase gs-0')
            // ->orderBy(1)
            ->parameters([
                'scrollX'      =>  true,
                'searching'       =>  true,
                // 'responsive'       =>  true,
                'lengthMenu' => [
                    [10, 25, 50, -1],
                    ['10 rows', '25 rows', '50 rows', 'Show all']
                ],
                'buttons' => [
                    'copy',
                    'csv',
                    'excel',
                    'pdf',
                    'print',
                    'colvis',
                    [
                        'text' => 'Reload',
                        'action' => 'function ( e, dt, node, config ) { dt.ajax.reload(); }',
                        'className' => 'btn btn-secondary'
                    ]
                ],
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
            Column::make('farm_id')->title('Farm Ayam')->searchable(true),
            // Column::computed('farm')->searchable(true),
            Column::make('supply_id')->title('Nama Pakan'),
            Column::computed('quantity')->title('Jumlah')
                ->visible(true),
            Column::computed('unit')
                ->visible(true),
            // Column::make('konversi')
            //     ->visible(false),
            // Column::computed('jumlah')
            //     ->visible(true),
            // Column::make('satuan_kecil')->title('Satuan')
            //     ->visible(true),
            // Column::make('created_at')->title('Created Date')->addClass('text-nowrap')
            //     ->searchable(false)
            //     ->visible(false),
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
        return 'SupplyStocks_' . date('YmdHis');
    }
}

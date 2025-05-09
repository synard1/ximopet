<?php

namespace App\DataTables;

use App\Models\SupplyStock;
use App\Models\Item;
use App\Models\StockHistory;
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
						</span>' . $supply->supply->name . '</a>';
            })

            ->editColumn('unit', function (SupplyStock $stock) {
                return $stock->supply->unit_conversion;
            })

            ->editColumn('quantity', function (SupplyStock $stock) {
                $quantity = $stock->total_in - $stock->total_used - $stock->total_mutated;
                return $quantity;
            })
            ->addColumn('action', function (SupplyStock $transaction) {
                // dd($transaction);
                return view('pages.masterdata.stock._feedstock_actions', compact('transaction'));
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
        return $model->newQuery()
            ->selectRaw('
                farm_id,
                supply_id,
                SUM(quantity_in) as total_in,
                SUM(quantity_used) as total_used,
                SUM(quantity_mutated) as total_mutated
            ')
            ->with('supply')
            ->groupBy('farm_id','supply_id');
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
            // ->orderBy(1)
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
            Column::make('farm_id')->title('Farm Ayam')->searchable(false),
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

<?php

namespace App\DataTables;

use App\Models\FeedStock;
use App\Models\Item;
use App\Models\StockHistory;
use App\Models\Unit;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class FeedStockDataTable extends DataTable
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

            ->editColumn('feed_id', function (FeedStock $stok) {
                return $stok->feed->name;
            })

            ->editColumn('livestock_id', function (FeedStock $stock) {
                return '<a href="#" class="text-gray-800 text-hover-primary mb-1" data-kt-action="view_detail_feedstoks" data-feed-id="' . $stock->feed_id . '" data-livestock-id="' . $stock->livestock_id . '"><span class="menu-icon">
							<i class="ki-outline ki-package fs-4 text-success"></i>
						</span>' . $stock->livestock->name . '</a>';
            })

            ->editColumn('unit', function (FeedStock $stock) {
                $unit = Unit::findOrFail($stock->feed->data['unit_id']);
                return $unit->name;
            })

            ->editColumn('quantity', function (FeedStock $stock) {
                $quantity = $stock->total_in - $stock->total_used - $stock->total_mutated;
                return number_format($quantity, 2);
            })
            ->addColumn('action', function (FeedStock $transaction) {
                return view('pages.masterdata.stock._feedstock_actions', compact('transaction'));
            })
            ->filterColumn('livestock_id', function ($query, $keyword) {
                $query->whereHas('livestock', function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('feed_id', function ($query, $keyword) {
                $query->whereHas('feed', function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%");
                });
            })
            // ->editColumn('created_at', function (FeedStock $stok) {
            //     return $stok->created_at->format('d M Y, h:i a');
            // })
            ->setRowId('id')
            ->rawColumns(['livestock_id']);
    }


    /**
     * Get the query source of dataTable.
     */
    public function query(FeedStock $model): QueryBuilder
    {
        $query = $model->newQuery();
        
        if (auth()->user()->hasRole('Operator')) {
            $query->whereHas('livestock.farm.farmOperators', function ($q) {
                $q->where('user_id', auth()->id());
            });
        }

        return $query->selectRaw('
                livestock_id,
                feed_id,
                SUM(quantity_in) as total_in,
                SUM(quantity_used) as total_used,
                SUM(quantity_mutated) as total_mutated
            ')
            ->with(['feed', 'livestock.farm'])
            ->groupBy('livestock_id', 'feed_id');
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
            ->dom('Bfrtip')
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
            Column::make('livestock_id')->title('Batch Ayam')->searchable(true),
            // Column::computed('farm')->searchable(true),
            Column::make('feed_id')->title('Nama Pakan')->searchable(true),
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
        return 'FeedStocks_' . date('YmdHis');
    }
}

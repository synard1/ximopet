<?php

namespace App\DataTables;

use App\Models\FeedStock;
use App\Models\FeedMutation;
use App\Models\FeedMutationItem;
use App\Models\Mutation;
use App\Models\Item;
use App\Models\StockHistory;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class FeedMutationDataTable extends DataTable
{
    private function formatNumber($amount)
    {
        // Convert the number to a string with two decimal places
        $formattedAmount = number_format($amount, 0, ',', '.');

        // Add the currency symbol and return the formatted number
        return $formattedAmount;
    }

    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->editColumn('from_livestock_id', function (Mutation $stok) {
                return $stok->fromLivestock->name;
            })
            ->editColumn('to_livestock_id', function (Mutation $stok) {
                return $stok->toLivestock->name;
            })
            ->editColumn('quantity', function (Mutation $transaction) {
                $total = $transaction->mutationItems->sum(function ($mutation) {
                    return $mutation->quantity;
                });

                return $this->formatNumber($total);
            })
            // ->editColumn('quantity', function (Mutation $stok) {
            //     return $stok->feedMutationDetails->sum('quantity');
            // })

            ->editColumn('date', function (Mutation $stok) {
                return $stok->date->format('d M Y, h:i a');
            })
            ->editColumn('created_at', function (Mutation $stok) {
                return $stok->created_at->format('d M Y, h:i a');
            })
            ->addColumn('action', function (Mutation $transaction) {
                return view('pages.masterdata.feed._mutation_actions', compact('transaction'));
            })
            ->setRowId('id')
            ->rawColumns(['']);
    }


    /**
     * Get the query source of dataTable.
     */
    public function query(Mutation $model): QueryBuilder
    {
        $query = $model->where('type', 'feed')->newQuery();
        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('feedMutation-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('Bfrtip')
            ->parameters([
                'scrollX'      =>  true,
                'searching'    =>  true,
                'responsive'   =>  false,
                'lengthMenu' => [
                    [10, 25, 50, -1],
                    ['10 rows', '25 rows', '50 rows', 'Show all']
                ],
                'language' => [
                    'search' => 'Search:',
                    'searchPlaceholder' => 'Enter search term...'
                ],
            ])
            ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer')
            ->setTableHeadClass('text-start text-muted fw-bold fs-7 text-uppercase gs-0')
            ->drawCallback("function() {" . file_get_contents(resource_path('views/pages/masterdata/feed/_mutation_draw-scripts.js')) . "}");
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::make('date')->title('Tanggal')->searchable(false),
            Column::make('from_livestock_id')->title('Asal'),
            Column::make('to_livestock_id')->title('Tujuan'),
            Column::computed('quantity')->title('Jumlah'),
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

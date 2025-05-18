<?php

namespace App\DataTables;

use App\Models\SupplyStock;
use App\Models\Mutation;
use App\Models\MutationItem;
use App\Models\Item;
use App\Models\StockHistory;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class SupplyMutationDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->editColumn('from_farm_id', function (Mutation $stok) {
                return $stok->fromFarm->name;
            })
            ->editColumn('to_farm_id', function (Mutation $stok) {
                return $stok->toFarm->name;
            })
            ->editColumn('quantity', function (Mutation $transaction) {
                $total = $transaction->mutationItems->sum(function ($mutation) {
                    return $mutation->quantity;
                });

                return formatNumber($total, 0);
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
                return view('pages.masterdata.supply._mutation_actions', compact('transaction'));
            })
            ->setRowId('id')
            ->rawColumns(['']);
    }


    /**
     * Get the query source of dataTable.
     */
    public function query(Mutation $model): QueryBuilder
    {
        $query = $model->where('type', 'supply')->newQuery();
        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('supplyMutation-table')
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
                // 'buttons'      => [
                //     [
                //         'text' => '<i class="fa fa-plus"></i> Add New',
                //         'className' => 'btn btn-primary',
                //         'attr' => [
                //             'data-kt-action' => 'new_kternak'
                //         ]
                //     ],
                //     // ['extend' => 'excel', 'className' => 'btn btn-success', 'text' => '<i class="fa fa-file-excel"></i> Excel'],
                //     ['extend' => 'print', 'className' => 'btn btn-info', 'text' => '<i class="fa fa-print"></i> Print'],
                //     ['extend' => 'colvis', 'className' => 'btn btn-warning', 'text' => '<i class="fa fa-columns"></i> Columns']
                // ],
                'language' => [
                    'search' => 'Search:',
                    'searchPlaceholder' => 'Enter search term...'
                ],
            ])
            // ->dom('rt' . "<'row'<'col-sm-12 col-md-5'l><'col-sm-12 col-md-7'p>>",)
            // ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer text-gray-600 fw-semibold')
            ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer')
            ->setTableHeadClass('text-start text-muted fw-bold fs-7 text-uppercase gs-0')
            // ->orderBy(1)
            ->drawCallback("function() {" . file_get_contents(resource_path('views/pages/masterdata/feed/_mutation_draw-scripts.js')) . "}");
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::make('date')->title('Tanggal')->searchable(false),
            Column::make('from_farm_id')->title('Asal'),
            Column::make('to_farm_id')->title('Tujuan'),
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

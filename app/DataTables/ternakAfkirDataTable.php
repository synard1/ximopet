<?php

namespace App\DataTables;

use App\Models\TernakAfkir;
use App\Models\Kandang;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class ternakAfkirDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->rawColumns(['farm_id'])
            ->editColumn('total_berat', function (TernakAfkir $ternak) {
                if ($ternak->total_berat < 1000) {
                    return $ternak->total_berat . ' gram';
                } elseif ($ternak->total_berat < 1000000) {
                    return number_format($ternak->total_berat / 1000, 2) . ' Kg';
                } else {
                    return number_format($ternak->total_berat / 1000000, 2) . ' Ton';
                }
            })
            ->editColumn('tanggal', function (TernakAfkir $ternak) {
                return $ternak->tanggal->format('d M Y, h:i a');
            })
            ->editColumn('jumlah', function (TernakAfkir $ternak) {
                return $ternak->jumlah . ' Ekor';
            })
            ->editColumn('id', function (TernakAfkir $ternak) {
                return strtoupper(substr(strrchr($ternak->id, '-'), 4));
            })
            ->editColumn('kelompok_ternak_id', function (TernakAfkir $ternak) {
                return $ternak->kelompokTernaks->name;
            })
            ->editColumn('farm_id', function (TernakAfkir $ternak) {
                return $ternak->kelompokTernaks->transaksiBeli->farms->nama;
            })
            ->editColumn('coop_id', function (TernakAfkir $ternak) {
                return $ternak->kelompokTernaks->transaksiBeli->coops->nama;
            })
            ->editColumn('created_at', function (TernakAfkir $ternak) {
                return $ternak->created_at->format('d M Y, h:i a');
            })
            ->filterColumn('kelompok_ternak_id', function ($query, $keyword) {
                $query->whereHas('kelompokTernaks', function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%");
                });
            })
            // ->filterColumn('farm_id', function($query, $keyword) {
            //     $query->whereHas('farm', function($q) use ($keyword) {
            //         $q->where('nama', 'like', "%{$keyword}%");
            //     });
            // })
            // ->filterColumn('coop_id', function($query, $keyword) {
            //     $query->whereHas('kandang', function($q) use ($keyword) {
            //         $q->where('nama', 'like', "%{$keyword}%");
            //     });
            // })
            ->addColumn('action', function (TernakAfkir $transaksi) {
                if (auth()->user()->hasRole('Operator')) {

                    return view('pages/ternak.afkir._actions', compact('transaksi'));
                };
            })
            ->setRowId('id');
    }


    /**
     * Get the query source of dataTable.
     */
    public function query(TernakAfkir $model): QueryBuilder
    {
        if (auth()->user()->hasRole('Operator')) {
            return $model->newQuery()->whereHas('kelompokTernaks.transaksiBeli.farms', function ($query) {
                $query->whereHas('farmOperators', function ($q) {
                    $q->where('user_id', auth()->id());
                });
            });
        }
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('ternakAfkir-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('Bfrtip')
            ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer text-gray-600 fw-semibold')
            ->setTableHeadClass('text-start text-muted fw-bold fs-7 text-uppercase gs-0')
            ->orderBy(1)
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
            ->drawCallback("function() {" . file_get_contents(resource_path('views/pages/ternak/afkir/_draw-scripts.js')) . "}");
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::make('id')->visible(false),
            Column::make('tanggal'),
            Column::make('kelompok_ternak_id'),
            Column::computed('farm_id')->title('Farm')->visible(false),
            Column::computed('coop_id')->title('Kandang')->visible(false),
            Column::make('jumlah'),
            Column::make('total_berat'),
            Column::make('kondisi')->visible(false),
            Column::make('tindakan')->visible(false),
            Column::make('status'),
            Column::make('created_at')->title('Created Date')->addClass('text-nowrap')->searchable(false)->visible(false),
            Column::computed('action')
                // ->addClass('text-end text-nowrap')
                ->exportable(false)
                ->printable(false)
                ->width(60)
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'TernakAfkirs_' . date('YmdHis');
    }
}

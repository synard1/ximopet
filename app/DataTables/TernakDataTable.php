<?php

namespace App\DataTables;

use App\Models\Ternak;
use App\Models\Kandang;;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class TernakDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->rawColumns(['name'])
            ->editColumn('berat_beli', function (Ternak $ternak) {
                if ($ternak->berat_beli < 1000) {
                    return $ternak->berat_beli . ' gram';
                } elseif ($ternak->berat_beli < 1000000) {
                    return number_format($ternak->berat_beli / 1000, 2) . ' Kg';
                } else {
                    return number_format($ternak->berat_beli / 1000000, 2) . ' Ton';
                }
            })
            // ->addColumn('kapasitas', function (Ternak $ternak) {
            //     $jumlah = Kandang::where('ternak_id',$ternak->id)->sum('kapasitas');
            //     return $jumlah;
            // })
            ->editColumn('name', function (Ternak $ternak) {
                return '<a href="#" class="text-gray-800 text-hover-primary mb-1" data-bs-toggle="modal" data-bs-target="#kt_modal_ternak_details" data-farm-id="' . $ternak->id . '">' . $ternak->name . '</a>';
            })
            ->editColumn('start_date', function (Ternak $ternak) {
                return $ternak->start_date->format('d M Y, h:i a');
            })
            ->editColumn('created_at', function (Ternak $ternak) {
                return $ternak->created_at->format('d M Y, h:i a');
            })
            // ->addColumn('action', function (Ternak $ternak) {
            //     return view('pages/masterdata.ternak._actions', compact('ternak'));
            // })
            ->setRowId('id');
    }


    /**
     * Get the query source of dataTable.
     */
    public function query(Ternak $model): QueryBuilder
    {
        if (auth()->user()->hasRole('Operator')) {
            return $model->newQuery()->whereHas('kandang.farms.farmOperators', function ($query) {
                $query->where('user_id', auth()->id());
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
            ->setTableId('ternaks-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('Bfrtip')
            // ->dom('rt' . "<'row'<'col-sm-12 col-md-5'l><'col-sm-12 col-md-7'p>>",)
            ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer text-gray-600 fw-semibold')
            ->setTableHeadClass('text-start text-muted fw-bold fs-7 text-uppercase gs-0')
            ->orderBy(1)
            ->parameters([
                'scrollX'      =>  true,
                'searching'       =>  false,
                // 'responsive'       =>  true,
                'lengthMenu' => [
                        [ 10, 25, 50, -1 ],
                        [ '10 rows', '25 rows', '50 rows', 'Show all' ]
                ],
                'buttons'      => ['export', 'print', 'reload','colvis'],
            ])
            ->drawCallback("function() {" . file_get_contents(resource_path('views/pages/masterdata/ternak/_draw-scripts.js')) . "}");
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::make('name'),
            Column::make('start_date'),
            Column::make('initial_quantity'),
            Column::make('berat_beli'),
            Column::make('death_quantity')->title('Ternak Mati'),
            Column::make('remaining_quantity')->title('Sisa Ternak'),
            Column::make('status'),
            Column::make('created_at')->title('Created Date')->addClass('text-nowrap')->searchable(false)->visible(false),
            // Column::computed('action')
            //     // ->addClass('text-end text-nowrap')
            //     ->exportable(false)
            //     ->printable(false)
                // ->width(60)
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Ternaks_' . date('YmdHis');
    }
}

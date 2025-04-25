<?php

namespace App\DataTables;

use App\Models\LivestockBreedStandard as StandarBobot;
// use App\Models\StandarBobot;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class StandarBobotDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->rawColumns(['standarBobot'])
            // ->editColumn('standarBobot', function (StandarBobot $standarBobot) {
            //     return view('pages/apps.standarBobot-management.standarBobots.columns._standarBobot', compact('standarBobot'));
            // })
            ->editColumn('created_at', function (StandarBobot $standarBobot) {
                return $standarBobot->created_at->format('d M Y, h:i a');
            })
            ->addColumn('action', function (StandarBobot $standarBobot) {
                return view('pages/masterdata.standar-bobot._actions', compact('standarBobot'));
            })
            ->setRowId('id');
    }


    /**
     * Get the query source of dataTable.
     */
    public function query(StandarBobot $model): QueryBuilder
    {
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('standarBobots-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            // ->dom('rt' . "<'row'<'col-sm-12 col-md-5'l><'col-sm-12 col-md-7'p>>",)
            ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer text-gray-600 fw-semibold')
            ->setTableHeadClass('text-start text-muted fw-bold fs-7 text-uppercase gs-0')
            ->orderBy(1)
            ->drawCallback("function() {" . file_get_contents(resource_path('views/pages/masterdata/standar-bobot/_draw-scripts.js')) . "}");
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::make('id')->searchable(false),
            Column::make('breed'),
            Column::make('description'),
            Column::make('status'),
            Column::make('created_at')->title('Created Date')->addClass('text-nowrap')->searchable(false),
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
        return 'StandarBobots_' . date('YmdHis');
    }
}

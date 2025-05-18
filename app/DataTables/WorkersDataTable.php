<?php

namespace App\DataTables;

use App\Models\Worker;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class WorkersDataTable extends DataTable
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
            ->rawColumns(['worker'])
            // ->editColumn('worker', function (Worker $worker) {
            //     return view('pages/apps.worker-management.workers.columns._worker', compact('worker'));
            // })
            ->editColumn('created_at', function (Worker $worker) {
                return $worker->created_at->format('d M Y, h:i a');
            })
            ->addColumn('action', function (Worker $worker) {
                return view('pages/masterdata.worker._actions', compact('worker'));
            })
            ->setRowId('id');
    }


    /**
     * Get the query source of dataTable.
     */
    public function query(Worker $model): QueryBuilder
    {
        $query = $model->newQuery();
        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('workers-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            // ->dom('rt' . "<'row'<'col-sm-12 col-md-5'l><'col-sm-12 col-md-7'p>>",)
            ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer text-gray-600 fw-semibold')
            ->setTableHeadClass('text-start text-muted fw-bold fs-7 text-uppercase gs-0')
            ->orderBy(1)
            ->drawCallback("function() {" . file_get_contents(resource_path('views/pages/masterdata/worker/_draw-scripts.js')) . "}");
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
            Column::make('name'),
            Column::make('status'),
            Column::make('created_at')->title('Created Date')->visible(false)->addClass('text-nowrap')->searchable(false),
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
        return 'Workers_' . date('YmdHis');
    }
}

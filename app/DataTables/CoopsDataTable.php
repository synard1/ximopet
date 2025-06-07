<?php

namespace App\DataTables;

use App\Models\Coop;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class CoopsDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('farm', function (Coop $coop) {
                return $coop->farm->name;
            })
            ->editColumn('created_at', function (Coop $coop) {
                return $coop->created_at->format('d M Y, h:i a');
            })
            ->editColumn('capacity', function (Coop $coop) {
                return intval($coop->capacity)  ?? '';
            })
            ->editColumn('status', function (Coop $coop) {
                $status = trans('content.status.' . $coop->status, [], 'id');
                return $status;
            })
            ->editColumn('notes', function (Coop $coop) {
                return $coop->notes ?? '';
            })
            ->addColumn('action', function (Coop $coop) {
                return view('pages.masterdata.coop._actions', compact('coop'));
            })
            ->filterColumn('farm', function ($query, $keyword) {
                $query->whereHas('farm', function ($query) use ($keyword) { // Assuming you have a 'farm' relationship on your model
                    $query->where('name', 'like', "%{$keyword}%");
                });
            })
            ->setRowId('id')
            ->rawColumns(['farm']);
        // ->make(true);
    }


    /**
     * Get the query source of dataTable.
     */
    public function query(Coop $model): QueryBuilder
    {
        $query = $model->newQuery();

        if (auth()->user()->hasRole('Operator')) {
            $query = $model::with('farm')
                ->whereHas('farm', function ($query) {
                    $query->whereHas('farmOperators', function ($query) {
                        $query->where('user_id', auth()->id());
                    });
                })
                ->orderBy('name', 'DESC');
        } else {
            $query = $model::with('farm')
                ->orderBy('name', 'DESC');
        }

        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('coops-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('Bfrtip')

            // ->dom('Bfrtip')
            // ->dom('rt' . "<'row'<'col-sm-12 col-md-5'l><'col-sm-12 col-md-7'p>>",)
            // ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer text-gray-600 fw-semibold')
            ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer')
            ->setTableHeadClass('text-start text-muted fw-bold fs-7 text-uppercase gs-0')
            ->orderBy(1)
            ->drawCallback("function() {" . file_get_contents(resource_path('views/pages/masterdata/coop/_draw-scripts.js')) . "}");
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::make('DT_RowIndex')->title('No')->addClass('text-nowrap')->searchable(false)->visible(true),
            Column::make('code')->searchable(true),
            Column::computed('farm')->searchable(true),
            Column::make('name'),
            Column::make('status')->title('Status')->addClass('text-nowrap'),
            Column::make('capacity'),
            Column::make('notes')->searchable(false),
            Column::make('created_at')->title('Created Date')->addClass('text-nowrap')->searchable(false)->visible(false),
            Column::computed('action')
                // ->addClass('text-end text-nowrap')
                ->exportable(false)
                ->printable(false)
                // ->width(60)
                ->visible(auth()->user()->hasRole(['Supervisor', 'Admin']))
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Coops_' . date('YmdHis');
    }
}

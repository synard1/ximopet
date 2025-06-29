<?php

namespace App\DataTables;

use App\Models\Feed;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class FeedsDataTable extends DataTable
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
            ->rawColumns(['feed'])
            // ->editColumn('feed', function (Feed $feed) {
            //     return view('pages/apps.feed-management.feeds.columns._feed', compact('feed'));
            // })
            ->editColumn('created_at', function (Feed $feed) {
                return $feed->created_at->format('d M Y, h:i a');
            })
            ->addColumn('action', function (Feed $feed) {
                return view('pages.masterdata.feed._actions', compact('feed'));
            })
            ->setRowId('id');
    }


    /**
     * Get the query source of dataTable.
     */
    public function query(Feed $model): QueryBuilder
    {
        $query = $model->newQuery();

        if (auth()->user()->hasRole(['Administrator', 'Manager', 'Supervisor'])) {
            $query->where('company_id', auth()->user()->company_id);
        }

        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('feeds-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('Bfrtip')
            // ->dom('rt' . "<'row'<'col-sm-12 col-md-5'l><'col-sm-12 col-md-7'p>>",)
            ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer text-gray-600 fw-semibold')
            ->setTableHeadClass('text-start text-muted fw-bold fs-7 text-uppercase gs-0')
            ->orderBy(1)
            ->drawCallback("function() {" . file_get_contents(resource_path('views/pages/masterdata/feed/_draw-scripts.js')) . "}");
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
            Column::make('code')->searchable(true),
            Column::make('name')->searchable(true),
            // Column::make('unit'),
            Column::make('status')->searchable(true),
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
        return 'Feeds_' . date('YmdHis');
    }
}

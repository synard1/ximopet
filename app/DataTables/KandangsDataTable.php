<?php

namespace App\DataTables;

use App\Models\Kandang;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class KandangsDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('farm', function (Kandang $kandang) {
                return $kandang->farms->nama;
            })
            ->editColumn('created_at', function (Kandang $kandang) {
                return $kandang->created_at->format('d M Y, h:i a');
            })
            ->addColumn('action', function (Kandang $kandang) {
                return view('pages/masterdata.kandang._actions', compact('kandang'));
            })
            ->filterColumn('farm', function($query, $keyword) {
                $query->whereHas('farms', function($query) use ($keyword) { // Assuming you have a 'farm' relationship on your model
                    $query->where('nama', 'like', "%{$keyword}%");
                });
            })
            ->setRowId('id')
            ->rawColumns(['farm']);
            // ->make(true);
    }


    /**
     * Get the query source of dataTable.
     */
    public function query(Kandang $model): QueryBuilder
    {
        $query = $model->newQuery();

        // return $model->newQuery();
        $query = $model::with('farms')
            ->orderBy('nama', 'DESC')
            ->newQuery();

        return $query;

    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('kandangs-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            // ->dom('Bfrtip')
            // ->dom('rt' . "<'row'<'col-sm-12 col-md-5'l><'col-sm-12 col-md-7'p>>",)
            // ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer text-gray-600 fw-semibold')
            ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer')
            ->setTableHeadClass('text-start text-muted fw-bold fs-7 text-uppercase gs-0')
            ->orderBy(1)
            ->drawCallback("function() {" . file_get_contents(resource_path('views/pages/masterdata/kandang/_draw-scripts.js')) . "}");
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::make('kode')->searchable(false),
            Column::computed('farm')->searchable(true),
            Column::make('nama'),
            Column::make('status'),
            Column::make('jumlah'),
            Column::make('kapasitas'),
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
        return 'Kandangs_' . date('YmdHis');
    }
}

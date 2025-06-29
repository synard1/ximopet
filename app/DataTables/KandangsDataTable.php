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
                return $kandang->farm->name;
            })
            ->editColumn('created_at', function (Kandang $kandang) {
                return $kandang->created_at->format('d M Y, h:i a');
            })
            ->editColumn('jumlah', function (Kandang $kandang) {
                return intval($kandang->jumlah)  ?? '';
            })
            ->editColumn('kapasitas', function (Kandang $kandang) {
                return intval($kandang->kapasitas)  ?? '';
            })
            ->editColumn('berat', function (Kandang $kandang) {
                $beratGram = floatval($kandang->berat);
                if ($beratGram >= 1000000) {
                    return number_format($beratGram / 1000000, 2) . ' Ton';
                } else {
                    return number_format($beratGram / 1000, 2) . ' Kg';
                }
            })
            ->editColumn('tanggal_masuk', function (Kandang $kandang) {
                return $kandang->livestock && $kandang->livestock->start_date
                    ? $kandang->livestock->start_date->format('Y-m-d')
                    : '';
            })
            ->addColumn('action', function (Kandang $kandang) {
                return view('pages.masterdata.kandang._actions', compact('kandang'));
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
    public function query(Kandang $model): QueryBuilder
    {
        $query = $model->newQuery();

        if (auth()->user()->hasRole('Operator')) {
            $query = $model::with('farm')
                ->whereHas('farms.farmOperators', function ($query) {
                    $query->where('user_id', auth()->id());
                })
                ->orderBy('nama', 'DESC');
        } else if (auth()->user()->hasRole(['Administrator', 'Manager', 'Supervisor'])) {
            $query = $model::with('farm')
                ->orderBy('nama', 'DESC')
                ->where('company_id', auth()->user()->company_id);
        } else {
            $query = $model::with('farm')
                ->orderBy('nama', 'DESC')
                ->where('company_id', auth()->user()->company_id);
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
            Column::make('berat')->searchable(false),
            Column::make('kapasitas'),
            Column::computed('tanggal_masuk')->title('Tanggal Masuk DOC'),
            Column::make('created_at')->title('Created Date')->addClass('text-nowrap')->searchable(false)->visible(false),
            Column::computed('action')
                // ->addClass('text-end text-nowrap')
                ->exportable(false)
                ->printable(false)
                // ->width(60)
                ->visible(auth()->user()->hasRole(['Supervisor']))
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

<?php

namespace App\DataTables;

use App\Models\Farm;
use App\Models\Kandang;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class FarmsDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->rawColumns(['farm', 'name'])
            ->editColumn('name', function (Farm $farm) {
                return '<a href="#" class="farm-detail" data-farm-id="' . $farm->id . '">' . $farm->name . '</a>';
            })
            // ->addColumn('kapasitas', function (Farm $farm) {
            //     $jumlah = Kandang::where('farm_id', $farm->id)->sum('kapasitas');
            //     return $jumlah;
            // })
            ->editColumn('created_at', function (Farm $farm) {
                return $farm->created_at->format('d M Y, h:i a');
            })
            ->addColumn('action', function (Farm $farm) {
                return view('pages.masterdata.farm._actions', compact('farm'));
            })
            ->setRowId('id');
    }


    /**
     * Get the query source of dataTable.
     */
    public function query(Farm $model): QueryBuilder
    {
        if (auth()->user()->hasRole('Operator')) {
            return $model->newQuery()->whereHas('farmOperators', function ($query) {
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
            ->setTableId('farms-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('Bfrtip')
            ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer text-gray-600 fw-semibold')
            ->setTableHeadClass('text-start text-muted fw-bold fs-7 text-uppercase gs-0')
            ->orderBy(1)
            ->drawCallback("function() {" . file_get_contents(resource_path('views/pages/masterdata/farm/_draw-scripts.js')) . "}")
            ->buttons([
                'colvis'
            ])
            ->columnDefs([
                ['visible' => true, 'targets' => '_all']
            ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::make('code')->searchable(false),
            Column::make('name'),
            Column::make('address')->visible(false),
            Column::make('phone_number')->visible(false),
            Column::make('contact_person')->visible(false),
            // Column::computed('capacity'),
            Column::make('status'),
            Column::make('created_at')->title('Created Date')->addClass('text-nowrap')->searchable(false),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->visible(auth()->user()->hasRole(['Supervisor']))
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Farms_' . date('YmdHis');
    }
}

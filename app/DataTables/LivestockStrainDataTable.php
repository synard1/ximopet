<?php

namespace App\DataTables;

use App\Models\CurrentTernak;
use App\Models\TernakDepletion;
use App\Models\KematianTernak;
// use App\Models\KelompokTernak as Ternak;
use App\Models\LivestockStrain;
use App\Models\TernakAfkir;
use App\Models\Kandang;
use App\Models\LivestockDepletion;
use App\Models\TernakJual;
use App\Models\TransaksiJual;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class LivestockStrainDataTable extends DataTable
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
            ->rawColumns(['name'])
            ->editColumn('code', function (LivestockStrain $livestockStrain) {
                return $livestockStrain->code ?? '-';
            })
            ->editColumn('name', function (LivestockStrain $livestockStrain) {
                if (Auth::user()->can('read records management')) {
                    return '<a href="#" class="text-gray-800 text-hover-primary mb-1" data-kt-action="view_detail_livestock" data-kt-livestock-id="' . $livestockStrain->id . '">' . $livestockStrain->name . '</a>';
                } else {
                    return $livestockStrain->name;
                }
            })
            ->editColumn('created_at', function (LivestockStrain $livestockStrain) {
                return $livestockStrain->created_at->format('d M Y, h:i a');
            })
            ->addColumn('action', function (LivestockStrain $livestockStrain) {
                return view('pages.masterdata.livestock-strain._actions', compact('livestockStrain'));
            })
            ->setRowId('id');
    }


    /**
     * Get the query source of dataTable.
     */
    public function query(LivestockStrain $model): QueryBuilder
    {
        $query = $model->newQuery();

        if (Auth::user()->hasRole('Operator')) {
            $query->whereHas('farm.farmOperators', function ($query) {
                $query->where('user_id', Auth::id());
            });
        }

        if (Auth::user()->hasRole(['Administrator', 'Manager', 'Supervisor'])) {
            $query->where('company_id', Auth::user()->company_id);
        }

        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('livestocks-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('Bfrtip')
            // ->dom('rt' . "<'row'<'col-sm-12 col-md-5'l><'col-sm-12 col-md-7'p>>",)
            ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer text-gray-600 fw-semibold')
            ->setTableHeadClass('text-start text-muted fw-bold fs-7 text-uppercase gs-0')
            ->orderBy(2)
            ->parameters([
                'scrollX'      =>  true,
                'searching'       =>  true,
                // 'responsive'       =>  true,
                'lengthMenu' => [
                    [10, 25, 50, -1],
                    ['10 rows', '25 rows', '50 rows', 'Show all']
                ],
                'buttons'      => ['export', 'print', 'reload', 'colvis'],
            ])
            ->drawCallback("function() {" . file_get_contents(resource_path('views/pages/masterdata/livestock-strain/_draw-scripts.js')) . "}");
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
            Column::make('code'),
            Column::make('name'),
            Column::make('description'),
            Column::make('status'),
            Column::make('created_at')->title('Created Date')->addClass('text-nowrap')->searchable(false)->visible(false),
            Column::computed('action')
                // ->addClass('text-end text-nowrap')
                ->exportable(false)
                ->printable(false)
                ->width(60)
                ->orderable(true),
            // Column::computed('jumlah_afkir')->title(trans('content.livestock_strain', [], 'id') . ' Afkir'),
            // Column::computed('jumlah_terjual')->title(trans('content.ternak',[],'id').' Terjual'),
            // Column::computed('stok_akhir')->title('Sisa '.trans('content.ternak',[],'id')),
            // Column::make('status'),
            // Column::make('created_at')->title('Created Date')->addClass('text-nowrap')->searchable(false)->visible(false),
            // Column::computed('action')
            //     // ->addClass('text-end text-nowrap')
            //     ->exportable(false)
            //     ->printable(false)
            //     ->width(60)
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Livestocks_' . date('YmdHis');
    }
}

<?php

namespace App\DataTables;

use App\Models\Partner as Expedition;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class ExpeditionDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->rawColumns(['expedition'])
            // ->editColumn('supplier', function (Supplier $supplier) {
            //     return view('pages/apps.supplier-management.suppliers.columns._supplier', compact('supplier'));
            // })
            ->editColumn('created_at', function (Expedition $expedition) {
                return $expedition->created_at->format('d M Y, h:i a');
            })
            ->addColumn('action', function (Expedition $expedition) {
                return view('pages.masterdata.expedition._actions', compact('expedition'));
            })
            ->setRowId('id');
    }


    /**
     * Get the query source of dataTable.
     */
    public function query(Expedition $model): QueryBuilder
    {
        return $model->where('type', '=', 'Expedition')->newQuery();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('suppliers-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            // ->dom('rt' . "<'row'<'col-sm-12 col-md-5'l><'col-sm-12 col-md-7'p>>",)
            ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer text-gray-600 fw-semibold')
            ->setTableHeadClass('text-start text-muted fw-bold fs-7 text-uppercase gs-0')
            ->orderBy(1)
            ->drawCallback("function() {" . file_get_contents(resource_path('views/pages/masterdata/expedition/_draw-scripts.js')) . "}");
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::make('code')->title('Kode'),
            Column::make('name')->title('Nama'),
            Column::make('address')->title('Alamat'),
            Column::make('phone_number')->title('Telp'),
            Column::make('contact_person')->title('Kontak Person'),
            Column::make('email')->title('Email'),
            Column::make('status')->title('Status'),
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
        return 'Suppliers_' . date('YmdHis');
    }
}

<?php

namespace App\DataTables;

use App\Models\OVKRecord;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class OVKRecordDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function ($query) {
                return view('livewire.ovk._actions', [
                    'record' => $query
                ]);
            })
            ->addColumn('farm_name', function ($query) {
                return $query->farm->name ?? '-';
            })
            ->addColumn('kandang_name', function ($query) {
                return $query->kandang->nama ?? '-';
            })
            ->addColumn('items_count', function ($query) {
                return $query->items->count();
            })
            ->addColumn('items_summary', function ($query) {
                return $query->items->map(function ($item) {
                    return $item->supply->name . ' (' . number_format($item->quantity, 2) . ' ' . $item->unit->name . ')';
                })->join('<br>');
            })
            ->editColumn('usage_date', function ($query) {
                return $query->usage_date ? date('d/m/Y', strtotime($query->usage_date)) : '-';
            })
            ->rawColumns(['action', 'items_summary'])
            ->setRowId('id');
    }

    public function query(OVKRecord $model): QueryBuilder
    {
        return $model->newQuery()
            ->with(['farm', 'kandang', 'items.supply', 'items.unit'])
            ->latest('usage_date');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('ovk-records-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('rt' . "<'row'<'col-sm-12 col-md-5'l><'col-sm-12 col-md-7'p>>",)
            ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer text-gray-600 fw-semibold')
            ->setTableHeadClass('text-start text-muted fw-bold fs-7 text-uppercase gs-0')
            ->orderBy(0, 'desc')
            ->pageLength(10)
            ->drawCallback("function() {
                KTMenu.createInstances();
            }");
    }

    protected function getColumns(): array
    {
        return [
            Column::make('usage_date')
                ->title('Tanggal')
                ->addClass('min-w-100px'),
            Column::make('farm_name')
                ->title('Farm')
                ->addClass('min-w-100px'),
            Column::make('kandang_name')
                ->title('Kandang')
                ->addClass('min-w-100px'),
            Column::make('items_count')
                ->title('Jumlah Item')
                ->addClass('min-w-100px'),
            Column::make('items_summary')
                ->title('Detail Item')
                ->addClass('min-w-300px'),
            Column::make('notes')
                ->title('Catatan')
                ->addClass('min-w-200px'),
            Column::computed('action')
                ->title('Aksi')
                ->exportable(false)
                ->printable(false)
                ->addClass('text-end')
                ->width(100),
        ];
    }
}

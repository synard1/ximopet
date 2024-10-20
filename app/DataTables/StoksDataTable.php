<?php

namespace App\DataTables;

use App\Models\Item;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class StoksDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->editColumn('kode', function (Item $item) {
                return '<a href="#" class="text-gray-800 text-hover-primary mb-1" data-kt-action="view_detail_stok" data-item-id="' . $item->id . '"><span class="menu-icon">
							<i class="ki-outline ki-package fs-4 text-success"></i>
						</span>' . $item->kode . '</a>';
            })
            ->editColumn('jumlah', function (Item $stok) {
                if (auth()->user()->farmOperators()->exists()) {
                    $farmIds = auth()->user()->farmOperators()->pluck('farm_id')->toArray();
                    $masuk = $stok->stokHistory()
                        ->whereIn('farm_id', $farmIds)
                        ->where('jenis','Masuk')
                        ->sum('stok_akhir');
                    $terpakai = $stok->stokHistory()
                        ->whereIn('farm_id', $farmIds)
                        ->where('jenis','Pemakaian')
                        ->sum('stok_keluar');

                    $stokAkhir = $masuk - $terpakai;

                } else {
                    $stokAkhir = $stok->stokHistory->sum('stok_akhir');
                }
                return number_format($stokAkhir / $stok->konversi, 2);
            })
            // ->editColumn('created_at', function (Item $stok) {
            //     return $stok->created_at->format('d M Y, h:i a');
            // })
            ->addColumn('action', function (Item $stok) {
                return view('pages/masterdata.stok._actions', compact('stok'));
            })
            // ->filterColumn('farm', function($query, $keyword) {
            //     $query->whereHas('farms', function($query) use ($keyword) { // Assuming you have a 'farm' relationship on your model
            //         $query->where('nama', 'like', "%{$keyword}%");
            //     });
            // })
            ->setRowId('id')
            ->rawColumns(['kode']);
            // ->make(true);
    }


    /**
     * Get the query source of dataTable.
     */
    public function query(Item $model): QueryBuilder
    {
        $query = $model->newQuery();

        

        // return $model->newQuery();
        if (auth()->user()->hasRole('Operator')) {
            // Get farm IDs for current user from farmOperators
            $farmIds = auth()->user()->farmOperators()->pluck('farm_id')->toArray();

            // dd($farmIds);
            
            // Add a condition to filter items based on farm IDs
            $query->whereHas('stokHistory', function ($q) use ($farmIds) {
                $q->whereIn('farm_id', $farmIds);
            });
            // $query = $model::with(['stokHistory', 'transaksiDetail'])
            //     ->where('jenis', '!=', 'DOC')
            //     // ->whereHas('farmOperator', function ($q) {
            //     //     $q->where('user_id', auth()->id());
            //     // })
            //     ->orderBy('name', 'DESC')
            //     ->newQuery();
        } else {
            $query = $model::with(['stokHistory', 'transaksiDetail'])
                ->where('jenis', '!=', 'DOC')
                ->orderBy('name', 'DESC')
                ->newQuery();
        }

        return $query;

    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('stoks-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            // ->dom('Bfrtip')
            // ->dom('rt' . "<'row'<'col-sm-12 col-md-5'l><'col-sm-12 col-md-7'p>>",)
            // ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer text-gray-600 fw-semibold')
            ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer')
            ->setTableHeadClass('text-start text-muted fw-bold fs-7 text-uppercase gs-0')
            ->orderBy(1)
            ->drawCallback("function() {" . file_get_contents(resource_path('views/pages/masterdata/stok/_draw-scripts.js')) . "}");
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::make('kode')->searchable(false),
            // Column::computed('farm')->searchable(true),
            Column::make('name'),
            Column::make('status')
                ->visible(false),
            Column::make('satuan_kecil')
                ->visible(false),
            Column::make('konversi')
                ->visible(false),
            Column::computed('jumlah')
                ->visible(true),
            Column::make('satuan_besar')
                ->visible(true),
            Column::make('created_at')->title('Created Date')->addClass('text-nowrap')
                ->searchable(false)
                ->visible(false),
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
        return 'Stoks_' . date('YmdHis');
    }
}

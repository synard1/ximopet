<?php

namespace App\DataTables;

use App\Models\LivestockPurchase as Transaksi;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class LivestockPurchaseDataTable extends DataTable
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
            ->editColumn('created_at', function (Transaksi $transaksi) {
                return $transaksi->created_at->format('d M Y, h:i a');
            })
            ->editColumn('farm_id', function (Transaksi $transaksi) {
                $detail = $transaksi->details->first();
                return $detail && $detail->livestock && $detail->livestock->farm
                    ? $detail->livestock->farm->name
                    : 'N/A';
            })
            ->editColumn('coop_id', function (Transaksi $transaksi) {
                $detail = $transaksi->details->first();
                return $detail && $detail->livestock && $detail->livestock->coop
                    ? $detail->livestock->coop->name
                    : 'N/A';
            })
            ->filterColumn('farm_id', function ($query, $keyword) {
                $query->whereHas('details.livestock.farm', function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('coop_id', function ($query, $keyword) {
                $query->whereHas('details.livestock.coop', function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%");
                });
            })
            ->editColumn('tanggal', function (Transaksi $transaksi) {
                return $transaksi->tanggal->format('d-m-Y');
            })
            ->editColumn('vendor_id', function (Transaksi $transaksi) {
                return $transaksi->vendor->name ?? '';
            })
            ->editColumn('expedition_id', function (Transaksi $transaksi) {
                return $transaksi->expedition->name ?? '';
            })
            ->editColumn('details.jumlah', function (Transaksi $transaksi) {
                return formatNumber($transaksi->details->sum('quantity'), 0) ?? 0;
            })
            ->editColumn('details.harga_per_ekor', function (Transaksi $transaksi) {
                return formatRupiah($transaksi->details->sum('price_per_unit'), 0) ?? 0;
            })
            ->addColumn('action', function (Transaksi $transaksi) {
                return view('pages.transaction.livestock-purchases._actions', compact('transaksi'));
            })
            // ->editColumn('payload.doc.nama', function (Transaksi $transaksi) {
            //     if ($transaksi->payload) {
            //         if (isset($transaksi->payload['doc']) && !empty($transaksi->payload['doc'])) {
            //             // The array exists and is not empty
            //             return $transaksi->payload['doc']['kode'] . ' - ' . $transaksi->payload['doc']['nama'] ?? '';
            //         }
            //     } else {
            //         return '';
            //     }
            // })
            // ->editColumn('farm_id', function (Transaksi $transaksi) {
            //     return $transaksi->farms->nama ?? '';
            // })
            // ->editColumn('coop_id', function (Transaksi $transaksi) {
            //     return $transaksi->coops->nama ?? '';
            // })
            // ->editColumn('kelompok_ternak_id', function (Transaksi $transaksi) {
            //     return $transaksi->kelompokTernak->name ?? '';
            // })
            // ->editColumn('harga', function (Transaksi $transaksi) {
            //     return formatRupiah($transaksi->harga, 0);
            // })
            // ->editColumn('sub_total', function (Transaksi $transaksi) {
            //     return formatRupiah($transaksi->sub_total, 0);
            // })
            ->setRowId('id')
            ->rawColumns(['']);
        // ->filterColumn('rekanan_id', function ($query, $keyword) {
        //     $query->whereHas('rekanans', function ($q) use ($keyword) {
        //         $q->where('nama', 'like', "%{$keyword}%");
        //     });
        // })
        // ->filterColumn('farm_id', function ($query, $keyword) {
        //     $query->whereHas('farms', function ($q) use ($keyword) {
        //         $q->where('nama', 'like', "%{$keyword}%");
        //     });
        // })

        // })
        // ->filterColumn('kelompok_ternak_id', function ($query, $keyword) {
        //     $query->whereHas('kelompokTernak', function ($q) use ($keyword) {
        //         $q->where('name', 'like', "%{$keyword}%");
        //     });
        // });
    }


    /**
     * Get the query source of dataTable.
     */
    public function query(Transaksi $model): QueryBuilder
    {
        return $model->newQuery()
            ->with(['details.livestock.farm', 'details.livestock.coop', 'vendor'])
            ->orderBy('tanggal', 'ASC');
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('livestock-purchases-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('Bfrtip')
            // ->dom('rt' . "<'row'<'col-sm-12 col-md-5'l><'col-sm-12 col-md-7'p>>",)
            // ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer text-gray-600 fw-semibold')
            ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer')
            ->setTableHeadClass('text-start text-muted fw-bold fs-7 text-uppercase gs-0')
            ->orderBy(1)
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
            ->drawCallback("function() {" . file_get_contents(resource_path('views/pages/transaction/livestock-purchases/_draw-scripts.js')) . "}");
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::make('DT_RowIndex')->title('No')->searchable(false),
            Column::make('id')->title('ID')->searchable(true)->visible(env('APP_ENV') === 'local'),
            Column::make('invoice_number')->searchable(true),
            Column::make('tanggal')->title('Tanggal Pembelian')->searchable(true),
            Column::computed('farm_id')->title('Farm')->searchable(true)->visible(true),
            Column::computed('coop_id')->title('Kandang')->searchable(true)->visible(true),
            Column::make('vendor_id')->title('Supplier')->searchable(true)->visible(true),
            Column::make('expedition_id')->title('Ekspedisi')->searchable(true)->visible(false),
            Column::make('details.jumlah')->title('Jumlah')->searchable(false),
            Column::make('details.harga_per_ekor')->title('Harga Per Ekor')->searchable(false),
            Column::make('created_at')->title('Created Date')
                ->visible(false)
                ->searchable(false)
                ->addClass('text-nowrap details-control'),
            Column::computed('action')
                // ->addClass('text-end text-nowrap')
                ->exportable(false)
                ->printable(false)
                ->width(60)
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Docs_' . date('YmdHis');
    }
}
